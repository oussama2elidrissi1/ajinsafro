<?php

namespace App\Services\HajjOmra;

use App\Models\HajjOmraPackage;
use Illuminate\Validation\ValidationException;

class HajjOmraFormulaService
{
    /** Resolve submitted references only against rows belonging to this offer. */
    public function sync(HajjOmraPackage $package, ?array $rows, array $maps): void
    {
        if ($rows !== null) {
            $existing = $package->formulas()->get()->keyBy('id');
            $kept = [];
            foreach (array_values($rows) as $index => $row) {
                $key = "formulas.{$index}";
                $id = (int) ($row['id'] ?? 0);
                if ($id && ! $existing->has($id)) {
                    throw ValidationException::withMessages([$key.'.id' => 'Cette formule ne fait pas partie de cette offre.']);
                }
                $attributes = [
                    'name_fr' => trim((string) ($row['name_fr'] ?? '')) ?: null,
                    'name_ar' => trim((string) ($row['name_ar'] ?? '')) ?: null,
                    'description_fr' => trim((string) ($row['description_fr'] ?? '')) ?: null,
                    'description_ar' => trim((string) ($row['description_ar'] ?? '')) ?: null,
                    'departure_id' => $this->resolve($row['departure_id'] ?? null, $maps['departures'], $key.'.departure_id'),
                    'sort_order' => (int) ($row['sort_order'] ?? $index),
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ];
                $formula = $id ? $existing->get($id) : $package->formulas()->make();
                $formula->fill($attributes)->save();
                $kept[] = $formula->id;

                $tariffs = [];
                foreach ((array) ($row['tariff_ids'] ?? []) as $position => $ref) {
                    $tariffId = $this->resolve($ref, $maps['room_prices'], $key.'.tariff_ids', false);
                    $tariffs[$tariffId] = ['sort_order' => $position];
                }
                $formula->tariffs()->sync($tariffs);

                $hotels = [];
                foreach ((array) ($row['hotels'] ?? []) as $position => $stay) {
                    if (empty($stay['hotel_id'])) {
                        continue;
                    }
                    $hotelId = $this->resolve($stay['hotel_id'], $maps['hotels'], $key.'.hotels', false);
                    if (in_array($hotelId, $hotels, true)) {
                        throw ValidationException::withMessages([$key.'.hotels' => 'Un hébergement ne peut être lié qu’une fois par formule.']);
                    }
                    $hotels[] = $hotelId;
                    $formula->stays()->updateOrCreate(['hotel_id' => $hotelId], [
                        'program_day_id' => $this->resolve($stay['program_day_id'] ?? null, $maps['program_days'], $key.'.hotels'),
                        'nights_override' => isset($stay['nights_override']) && $stay['nights_override'] !== '' ? (int) $stay['nights_override'] : null,
                        'sort_order' => $position,
                    ]);
                }
                $formula->stays()->whereNotIn('hotel_id', $hotels)->delete();
            }
            $package->formulas()->whereNotIn('id', $kept)->delete();
        }

        $package->load('formulas.tariffs', 'formulas.stays.hotel', 'formulas.stays.programDay', 'formulas.departure');
        foreach ($package->formulas as $index => $formula) {
            $key = "formulas.{$index}";
            $types = $formula->tariffs->pluck('room_type');
            if ($types->unique()->count() !== $types->count()) {
                throw ValidationException::withMessages([$key.'.tariff_ids' => 'Choisissez un seul tarif par type de chambre dans chaque formule.']);
            }
            if ($package->status !== HajjOmraPackage::STATUS_PUBLISHED || ! $formula->is_active) {
                continue;
            }
            $errors = [];
            if (! $formula->name_fr && ! $formula->name_ar) {
                $errors[$key.'.name_fr'] = 'Donnez un nom français ou arabe à la formule active.';
            }
            if ($formula->stays->isEmpty()) {
                $errors[$key.'.hotels'] = 'Liez au moins un hébergement à la formule active.';
            }
            if ($formula->tariffs->where('is_active', true)->isEmpty()) {
                $errors[$key.'.tariff_ids'] = 'Liez au moins un tarif actif à la formule avant de publier.';
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        }
    }

    private function resolve($reference, array $map, string $field, bool $nullable = true): ?int
    {
        if (($reference === null || $reference === '') && $nullable) {
            return null;
        }
        if (! isset($map[(string) $reference])) {
            throw ValidationException::withMessages([$field => 'La sélection n’existe plus dans cette offre. Sélectionnez à nouveau l’hébergement, le tarif ou le départ.']);
        }
        return (int) $map[(string) $reference];
    }
}
