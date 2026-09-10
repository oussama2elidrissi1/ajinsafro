<?php

namespace App\Services\HajjOmra;

use App\Models\HajjOmraDeparture;
use App\Models\HajjOmraPackage;
use App\Models\HajjOmraPackageHotel;
use App\Models\HajjOmraPackageImage;
use App\Models\HajjOmraProgramDay;
use App\Models\HajjOmraRoomPrice;
use App\Models\HajjOmraServiceItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Enregistrement d'une offre Hajj & Omra et de toutes ses collections.
 *
 * Principe central : synchronisation PAR IDENTIFIANT, jamais par « delete puis recreate ».
 * L'ancien controleur supprimait et recreait les departs a chaque enregistrement ; comme
 * `hajj_omra_booking_requests.departure_id` est en nullOnDelete, chaque sauvegarde
 * detachait silencieusement les demandes de reservation deja recues. On met donc a jour
 * les lignes existantes, on cree les nouvelles, et on ne supprime que celles reellement
 * retirees dans le formulaire.
 */
class HajjOmraPackageService
{
    /**
     * @param  array<string, mixed>  $data  donnees validees
     */
    public function save(HajjOmraPackage $package, array $data): HajjOmraPackage
    {
        return DB::transaction(function () use ($package, $data) {
            $retainedFormulas = ! array_key_exists('formulas', $data) && $package->exists
                ? $package->formulas()->with(['stays', 'tariffs'])->get() : collect();
            $package->fill($this->packageAttributes($data));
            $package->save();

            $maps['room_prices'] = $this->syncRoomPrices($package, $data['room_prices'] ?? []);
            $maps['departures'] = $this->syncDepartures($package, $data['departures'] ?? []);
            $maps['hotels'] = $this->syncHotels($package, $data['hotels'] ?? []);
            $maps['program_days'] = $this->syncProgramDays($package, $data['program_days'] ?? []);
            // Old clients must not silently detach formulas when removing a linked source.
            foreach ($retainedFormulas as $formula) {
                $links = ['departures' => [$formula->departure_id], 'room_prices' => $formula->tariffs->modelKeys(),
                    'hotels' => $formula->stays->pluck('hotel_id')->all(), 'program_days' => $formula->stays->pluck('program_day_id')->all()];
                foreach ($links as $collection => $ids) {
                    foreach (array_filter($ids) as $id) {
                        if (! isset($maps[$collection][(string) $id])) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['formulas' => 'Un élément retiré est lié à une formule. Modifiez d’abord ses liaisons dans les formules commerciales.']);
                        }
                    }
                }
            }
            $this->syncServiceItems($package, $data['service_items'] ?? []);
            app(HajjOmraFormulaService::class)->sync($package, $data['formulas'] ?? null, $maps);
            $this->syncGallery($package, $data['gallery'] ?? []);

            $this->syncLegacyMirrors($package);

            return $package->fresh([
                'images', 'departures', 'roomPrices', 'programDays', 'hotels', 'serviceItems', 'formulas.tariffs', 'formulas.stays',
            ]);
        });
    }

    /**
     * Genere les jours manquants pour couvrir la duree de l'offre.
     *
     * Ne touche jamais un jour deja saisi : seuls les numeros absents sont ajoutes.
     * Les jours au-dela de la duree ne sont pas supprimes automatiquement (le contenu
     * redige ne doit pas disparaitre parce qu'on a corrige la duree a la baisse).
     *
     * @return int nombre de jours crees
     */
    public function generateProgramDays(HajjOmraPackage $package, ?int $days = null): int
    {
        $days = (int) ($days ?? $package->duration_days ?? 0);

        if ($days < 1) {
            return 0;
        }

        $existing = $package->programDays()->pluck('day_number')->map(fn ($n) => (int) $n)->all();
        $created = 0;

        for ($dayNumber = 1; $dayNumber <= $days; $dayNumber++) {
            if (in_array($dayNumber, $existing, true)) {
                continue;
            }

            $package->programDays()->create([
                'day_number' => $dayNumber,
                'sort_order' => $dayNumber,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function packageAttributes(array $data): array
    {
        $keys = [
            'title', 'title_ar', 'slug', 'type', 'status',
            'short_description', 'short_description_ar', 'description', 'description_ar',
            'departure_city', 'destination', 'duration_days', 'duration_nights',
            'start_date', 'return_date',
            'adult_price', 'old_price', 'discount_amount', 'child_price', 'baby_price', 'currency',
            'available_places', 'reserved_places',
            'meal_plan',
            'booking_conditions', 'booking_conditions_ar',
            'required_documents', 'required_documents_ar',
            'meta_title', 'meta_title_ar', 'meta_description', 'meta_description_ar',
            'sort_order', 'main_image',
        ];

        $attributes = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $attributes[$key] = $key === 'title' ? (string) $data[$key] : ($data[$key] === '' ? null : $data[$key]);
            }
        }

        foreach (['is_featured', 'transport_included', 'visa_included', 'guidance_included'] as $flag) {
            $attributes[$flag] = (bool) ($data[$flag] ?? false);
        }

        $attributes['available_places'] = (int) ($data['available_places'] ?? 0);
        $attributes['reserved_places'] = (int) ($data['reserved_places'] ?? 0);
        $attributes['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $attributes;
    }

    /**
     * Applique une collection du formulaire sur une relation, par identifiant.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>, int): (array<string, mixed>|null)  $mapper
     *         retourne les attributs a ecrire, ou null pour ignorer la ligne
     * @param  callable(array<string, mixed>): (string|null)|null  $naturalKey
     *         cle metier de repli quand la ligne arrive sans identifiant (ex. la date d'un
     *         depart, protegee par un index unique). Sans elle, un formulaire poste sans id
     *         — cas des offres creees avant la refonte — provoquerait une violation
     *         de contrainte unique au lieu de mettre a jour la ligne existante.
     */
    private function syncCollection(HasMany $relation, array $rows, callable $mapper, ?callable $naturalKey = null): array
    {
        /** @var Collection $existing */
        $existing = $relation->get()->keyBy('id');

        $byNaturalKey = [];
        if ($naturalKey !== null) {
            foreach ($existing as $model) {
                $key = $naturalKey($model->getAttributes());
                if ($key !== null && ! isset($byNaturalKey[$key])) {
                    $byNaturalKey[$key] = $model;
                }
            }
        }

        $keptIds = [];
        $references = [];
        $position = 0;

        foreach (array_values($rows) as $row) {
            $attributes = $mapper($row, $position);

            if ($attributes === null) {
                continue;
            }

            $position++;
            $id = (int) ($row['id'] ?? 0);
            $target = null;

            if ($id > 0 && $existing->has($id)) {
                $target = $existing->get($id);
            } elseif ($naturalKey !== null) {
                $key = $naturalKey($attributes);
                if ($key !== null && isset($byNaturalKey[$key]) && ! in_array((int) $byNaturalKey[$key]->id, $keptIds, true)) {
                    $target = $byNaturalKey[$key];
                }
            }

            if ($target !== null) {
                $target->fill($attributes);
                $target->save();
                $keptIds[] = (int) $target->id;
                $references[(string) $target->id] = (int) $target->id;
                if (! empty($row['client_key'])) {
                    $references['new:'.$row['client_key']] = (int) $target->id;
                }
                continue;
            }

            $created = $relation->create($attributes);
            $keptIds[] = (int) $created->id;
            $references[(string) $created->id] = (int) $created->id;
            if (! empty($row['client_key'])) {
                $references['new:'.$row['client_key']] = (int) $created->id;
            }
        }

        // Seules les lignes reellement retirees du formulaire sont supprimees.
        $removed = $existing->keys()->map(fn ($id) => (int) $id)->diff($keptIds);

        if ($removed->isNotEmpty()) {
            $relation->whereIn('id', $removed->all())->delete();
        }

        return $references;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncRoomPrices(HajjOmraPackage $package, array $rows): array
    {
        return $this->syncCollection($package->roomPrices(), $rows, function (array $row, int $position): ?array {
            $type = trim((string) ($row['room_type'] ?? ''));
            $price = $row['price'] ?? null;

            if ($type === '' || $price === null || $price === '') {
                return null;
            }

            return [
                'room_type' => $type,
                'price' => $price,
                'old_price' => ($row['old_price'] ?? '') !== '' ? $row['old_price'] : null,
                'capacity' => ($row['capacity'] ?? '') !== '' ? (int) $row['capacity'] : $this->defaultCapacity($type),
                'stock' => (int) ($row['stock'] ?? 0),
                'is_active' => (bool) ($row['is_active'] ?? true),
                'sort_order' => $position + 1,
            ];
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncDepartures(HajjOmraPackage $package, array $rows): array
    {
        return $this->syncCollection($package->departures(), $rows, function (array $row, int $position): ?array {
            $date = $row['departure_date'] ?? null;

            if (! $date) {
                return null;
            }

            return [
                'departure_date' => $date,
                'return_date' => ($row['return_date'] ?? '') !== '' ? $row['return_date'] : null,
                'departure_city' => trim((string) ($row['departure_city'] ?? '')) ?: null,
                'status' => $row['status'] ?? HajjOmraDeparture::STATUS_PUBLISHED,
                'available_places' => (int) ($row['available_places'] ?? 0),
                'reserved_places' => (int) ($row['reserved_places'] ?? 0),
                'price_from' => ($row['price_from'] ?? '') !== '' ? $row['price_from'] : null,
                'internal_notes' => trim((string) ($row['internal_notes'] ?? '')) ?: null,
                'sort_order' => $position + 1,
            ];
        }, static function (array $attributes): ?string {
            $date = $attributes['departure_date'] ?? null;

            if ($date instanceof \DateTimeInterface) {
                return $date->format('Y-m-d');
            }

            return $date ? substr((string) $date, 0, 10) : null;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncHotels(HajjOmraPackage $package, array $rows): array
    {
        return $this->syncCollection($package->hotels(), $rows, function (array $row, int $position): ?array {
            $name = trim((string) ($row['name'] ?? ''));
            $distance = trim((string) ($row['haram_distance'] ?? ''));

            // Un bloc entierement vide n'est pas enregistre.
            if ($name === '' && trim((string) ($row['name_ar'] ?? '')) === '' && $distance === '') {
                return null;
            }

            return [
                'city' => $row['city'] ?? HajjOmraPackageHotel::CITY_MAKKAH,
                'name' => $name ?: null,
                'name_ar' => trim((string) ($row['name_ar'] ?? '')) ?: null,
                'location_ar' => trim((string) ($row['location_ar'] ?? '')) ?: null,
                'stars' => ($row['stars'] ?? '') !== '' ? (int) $row['stars'] : null,
                'haram_distance' => $distance ?: null,
                'location' => trim((string) ($row['location'] ?? '')) ?: null,
                'nights' => ($row['nights'] ?? '') !== '' ? (int) $row['nights'] : null,
                'meal_plan' => trim((string) ($row['meal_plan'] ?? '')) ?: null,
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'description_ar' => trim((string) ($row['description_ar'] ?? '')) ?: null,
                'image_path' => trim((string) ($row['image_path'] ?? '')) ?: null,
                'sort_order' => $position + 1,
            ];
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncProgramDays(HajjOmraPackage $package, array $rows): array
    {
        return $this->syncCollection($package->programDays(), $rows, function (array $row, int $position): ?array {
            $dayNumber = (int) ($row['day_number'] ?? 0);

            if ($dayNumber <= 0) {
                $dayNumber = $position + 1;
            }

            return [
                'day_number' => $dayNumber,
                'title' => trim((string) ($row['title'] ?? '')) ?: null,
                'title_ar' => trim((string) ($row['title_ar'] ?? '')) ?: null,
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'description_ar' => trim((string) ($row['description_ar'] ?? '')) ?: null,
                'city' => trim((string) ($row['city'] ?? '')) ?: null,
                'image_path' => trim((string) ($row['image_path'] ?? '')) ?: null,
                'sort_order' => $position + 1,
            ];
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncServiceItems(HajjOmraPackage $package, array $rows): void
    {
        $this->syncCollection($package->serviceItems(), $rows, function (array $row, int $position): ?array {
            $label = trim((string) ($row['label'] ?? ''));
            $labelAr = trim((string) ($row['label_ar'] ?? ''));

            if ($label === '' && $labelAr === '') {
                return null;
            }

            $kind = $row['kind'] ?? HajjOmraServiceItem::KIND_INCLUDED;

            return [
                'kind' => in_array($kind, HajjOmraServiceItem::KINDS, true) ? $kind : HajjOmraServiceItem::KIND_INCLUDED,
                'label' => $label ?: null,
                'label_ar' => $labelAr ?: null,
                'sort_order' => $position + 1,
            ];
        });
    }

    /**
     * Galerie : les fichiers ont deja ete televerses via l'uploader existant
     * (admin.local-media.upload), le formulaire ne renvoie que des chemins ordonnes.
     * Les images retirees sont supprimees du disque, celles conservees gardent leur id.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncGallery(HajjOmraPackage $package, array $rows): void
    {
        $existing = $package->images()->get()->keyBy('id');
        $keptIds = [];
        $position = 0;

        foreach (array_values($rows) as $row) {
            $path = trim((string) ($row['image_path'] ?? ''));

            if ($path === '') {
                continue;
            }

            $position++;
            $id = (int) ($row['id'] ?? 0);
            $attributes = [
                'image_path' => $path,
                'alt_text' => trim((string) ($row['alt_text'] ?? '')) ?: $package->title,
                'sort_order' => $position,
            ];

            if ($id > 0 && $existing->has($id)) {
                $existing->get($id)->fill($attributes)->save();
                $keptIds[] = $id;

                continue;
            }

            $keptIds[] = $package->images()->create($attributes)->id;
        }

        /** @var Collection<int, HajjOmraPackageImage> $removed */
        $removed = $existing->reject(fn (HajjOmraPackageImage $image) => in_array((int) $image->id, $keptIds, true));

        foreach ($removed as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }

            $image->delete();
        }
    }

    /**
     * Maintient les anciens champs plats en phase avec les nouvelles structures.
     *
     * Ils alimentent encore l'API publique consommee par WordPress
     * (`makkah_hotel`, `madinah_hotel`, `included_items`, `excluded_items`) : les
     * resynchroniser evite d'avoir a modifier le front dans le meme mouvement.
     */
    private function syncLegacyMirrors(HajjOmraPackage $package): void
    {
        $hotels = $package->hotels()->get();

        $makkah = $hotels->firstWhere('city', HajjOmraPackageHotel::CITY_MAKKAH);
        $madinah = $hotels->firstWhere('city', HajjOmraPackageHotel::CITY_MADINAH);

        $items = $package->serviceItems()->get();

        $package->forceFill([
            'makkah_hotel' => $makkah?->name,
            'makkah_haram_distance' => $makkah?->haram_distance,
            'madinah_hotel' => $madinah?->name,
            'madinah_haram_distance' => $madinah?->haram_distance,
            'included_items' => $this->labelsFor($items, HajjOmraServiceItem::KIND_INCLUDED),
            'excluded_items' => $this->labelsFor($items, HajjOmraServiceItem::KIND_EXCLUDED),
        ])->saveQuietly();
    }

    /**
     * @param  Collection<int, HajjOmraServiceItem>  $items
     * @return list<string>|null
     */
    private function labelsFor(Collection $items, string $kind): ?array
    {
        $labels = $items
            ->where('kind', $kind)
            ->sortBy('sort_order')
            ->map(fn (HajjOmraServiceItem $item) => trim((string) $item->label))
            ->filter()
            ->values()
            ->all();

        return $labels !== [] ? $labels : null;
    }

    private function defaultCapacity(string $roomType): ?int
    {
        return match ($roomType) {
            HajjOmraRoomPrice::ROOM_QUINTUPLE => 5,
            HajjOmraRoomPrice::ROOM_QUADRUPLE => 4,
            HajjOmraRoomPrice::ROOM_TRIPLE => 3,
            HajjOmraRoomPrice::ROOM_DOUBLE => 2,
            HajjOmraRoomPrice::ROOM_SINGLE => 1,
            default => null,
        };
    }

    /**
     * Supprime les fichiers physiques d'une offre avant sa suppression definitive.
     */
    public function deleteFiles(HajjOmraPackage $package): void
    {
        $package->loadMissing(['images', 'programDays', 'hotels']);

        $paths = [$package->main_image];

        foreach ($package->images as $image) {
            $paths[] = $image->image_path;
        }

        foreach ($package->programDays as $day) {
            $paths[] = $day->image_path;
        }

        foreach ($package->hotels as $hotel) {
            $paths[] = $hotel->image_path;
        }

        foreach (array_filter($paths) as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
