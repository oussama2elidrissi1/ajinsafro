<?php

namespace App\Console\Commands;

use App\Models\Departure;
use App\Models\TourHotel;
use App\Models\TourTransfer;
use App\Models\TravelDate;
use App\Models\TravelDayItem;
use App\Models\TravelProgramDay;
use App\Models\Voyage;
use App\Models\VoyageExtra;
use App\Models\VoyageFlightOption;
use App\Models\VoyageTheme;
use App\Models\Wp\TourDay;
use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use App\Services\VoyageFlightOptionService;
use App\Services\Wp\TourProgramService;
use App\Services\Wp\WpTourRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Finalise les fiches importées du catalogue historique : complète chaque étape du CRUD v2
 * et, sur demande, publie.
 *
 * Les états d'étape (« x / 14 validées ») ne se lisent pas dans les colonnes Laravel mais dans
 * quatre magasins : les métas WordPress, les tables WordPress `aj_tour_*` / `aj_travel_dates`,
 * et les tables Laravel des vols, items de jour, extras et logistique. Cette commande écrit donc
 * **par les mêmes chemins que le formulaire** — `WpTourRepository::updateTour()`,
 * `TourProgramService`, `VoyageFlightOptionService`, les modèles `aj_*` — et jamais des métas
 * posées à la main.
 *
 * Données : ce que l'extraction fournit est utilisé tel quel — hôtels (catégorie et ville, sans
 * nom), suppléments, date d'expiration de l'ancienne offre. Le reste est renseigné de façon
 * volontairement générique et reconnaissable (« Hôtel 4 étoiles », « Vol à confirmer »), à
 * remplacer à la réactivation. Les dates anciennes sont conservées : le front les affiche en
 * « Offre expirée » et refuse de les réserver.
 *
 * Rien n'est écrasé : chaque bloc n'est créé que s'il est absent. Idempotente.
 *
 * Simulation par défaut ; `--execute` écrit ; `--publish` publie en plus (WordPress `publish`,
 * Laravel `actif`, badge « À compléter » levé).
 */
class LegacyFinalizeCommand extends Command
{
    protected $signature = 'legacy:finalize
        {--execute : Écrit réellement (sinon simulation)}
        {--publish : Publie les fiches finalisées (WordPress publish, Laravel actif)}
        {--id=* : Ne traiter que ces identifiants historiques}
        {--limit=0 : Nombre maximum de fiches traitées}';

    protected $description = 'Complète chaque étape du CRUD des fiches historiques avec les données extraites ou des valeurs génériques, et publie sur demande.';

    private const DEFAULT_MIN_PEOPLE = 1;

    private const DEFAULT_MAX_PEOPLE = 40;

    private const DEFAULT_STARS = 4;

    private const HOME_CITY = 'Casablanca';

    private const GENERIC_EXCLUDE = [
        'Vols et transferts non mentionnés au programme',
        'Assurance voyage et rapatriement',
        'Dépenses personnelles et pourboires',
        'Excursions et activités optionnelles',
    ];

    private const GENERIC_DEPARTURE_POLICY = 'Départ garanti à partir de 10 participants. Dates, horaires et hôtels susceptibles de modification à la réactivation de l’offre.';

    /** Correspondance catégorie historique → thème, pour les fiches sans thème. */
    private const THEME_BY_CATEGORY = [
        'hajj' => 'Hajj',
        'omra' => 'Omra',
        'voyage national' => 'voyage nationale',
        'hebergement' => 'Séjour',
        'voyage international' => 'Voyage organisé',
    ];

    /** @var array<string, int> titre normalisé => ID de lieu Traveler */
    private array $locations = [];

    /** @var array<string, int> */
    private array $stats = [];

    private bool $execute = false;

    public function handle(WpTourRepository $tours, TourProgramService $program, VoyageFlightOptionService $flights): int
    {
        $this->execute = (bool) $this->option('execute');
        $publish = (bool) $this->option('publish');
        $limit = max(0, (int) $this->option('limit'));
        $onlyIds = array_map('intval', (array) $this->option('id'));

        $voyages = Voyage::query()
            ->whereNotNull('wp_post_id')
            ->orderBy('id')
            ->get()
            ->filter(fn (Voyage $v) => $v->isLegacyImport())
            ->filter(fn (Voyage $v) => $onlyIds === [] || in_array($this->legacyId($v), $onlyIds, true));

        if ($voyages->isEmpty()) {
            $this->info('Aucune fiche historique liée à WordPress.');

            return self::SUCCESS;
        }

        $this->loadLocations();
        $this->line(sprintf('%d fiche(s) historique(s) à finaliser · lieux Traveler existants : %d', $voyages->count(), count($this->locations)));
        $this->newLine();

        $done = 0;
        foreach ($voyages as $voyage) {
            if ($limit > 0 && $done >= $limit) {
                break;
            }
            $done++;

            $post = WpPost::find((int) $voyage->wp_post_id);
            if ($post === null) {
                $this->bump('tour_wp_absent');
                $this->warn(sprintf('  legacy %-4d tour WordPress %d introuvable, ignorée', $this->legacyId($voyage), $voyage->wp_post_id));

                continue;
            }

            $actions = $this->finalizeOne($voyage, $post, $tours, $program, $flights, $publish);
            $this->line(sprintf('  legacy %-4d %-44s %s', $this->legacyId($voyage), Str::limit($voyage->slug, 43), $actions === [] ? 'déjà complète' : implode(', ', $actions)));
        }

        $this->newLine();
        ksort($this->stats);
        foreach ($this->stats as $key => $n) {
            $this->line(sprintf('  %-28s %d', $key, $n));
        }
        $this->newLine();
        $this->info($this->execute
            ? sprintf('Finalisation écrite sur %d fiche(s)%s.', $done, $publish ? ', publiées' : '')
            : 'Simulation. Relancez avec --execute' . ($publish ? ' --publish' : '') . ' pour appliquer.');

        return self::SUCCESS;
    }

    /**
     * @return list<string> libellés des blocs créés ou mis à jour
     */
    private function finalizeOne(Voyage $voyage, WpPost $post, WpTourRepository $tours, TourProgramService $program, VoyageFlightOptionService $flights, bool $publish): array
    {
        $li = (array) data_get($voyage->logistics_meta, 'legacy_import', []);
        $wp = (int) $post->ID;
        $actions = [];

        $days = TravelProgramDay::query()->where('voyage_id', $voyage->id)->orderBy('day_number')->get();
        if ($days->isEmpty()) {
            if ($this->execute) {
                TravelProgramDay::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => 1,
                    'title' => 'Jour 1 — Programme selon l’offre',
                    'description' => Str::limit(strip_tags((string) $voyage->description), 400) ?: 'Programme détaillé communiqué à la réactivation de l’offre.',
                    'day_type' => 'visite',
                ]);
                $days = TravelProgramDay::query()->where('voyage_id', $voyage->id)->orderBy('day_number')->get();
            }
            $actions[] = 'jour générique';
            $this->bump('jours_generiques');
        }
        $lastDay = max(1, (int) $days->max('day_number'));

        $hotels = $this->normalizedHotels($li);
        $city = $hotels[0]['ville'] ?? (string) $voyage->destination;
        $category = mb_strtolower((string) ($li['categorie'] ?? ''));
        $isNational = str_starts_with($category, 'voyage national') || mb_strtolower((string) $voyage->destination) === 'maroc';
        $isHajj = str_contains($category, 'hajj') || str_contains($category, 'omra');
        $durationDays = $this->durationDays($voyage, $lastDay);

        // 1. Infos, tarifs, destination, inclus/exclus — les métas que lit chaque étape.
        $locationId = $this->ensureLocation((string) $voyage->destination);
        $include = $this->lines($voyage->tours_include);
        $exclude = $this->lines($voyage->tours_exclude) ?: self::GENERIC_EXCLUDE;
        $highlights = $this->lines($voyage->tours_highlight) ?: $days->take(3)->pluck('title')->filter()->values()->all();

        $payload = [
            'title' => (string) $voyage->name,
            'excerpt' => (string) ($voyage->accroche ?: Str::limit(strip_tags((string) $voyage->description), 160)),
            'destination' => (string) $voyage->destination,
            'duration_day' => $durationDays,
            'min_people' => self::DEFAULT_MIN_PEOPLE,
            'max_people' => self::DEFAULT_MAX_PEOPLE,
            'places' => self::DEFAULT_MAX_PEOPLE,
            'tour_price_by' => (string) ($voyage->tour_price_by ?: 'person'),
            'adult_price' => (int) $voyage->price_from,
            'base_price' => (int) $voyage->price_from,
            'tours_include' => implode("\n", $include ?: ['Prestations selon programme']),
            'tours_exclude' => implode("\n", $exclude),
            'tours_highlight' => implode("\n", $highlights ?: ['Programme selon l’offre']),
        ];
        if (trim((string) $voyage->description) !== '' && trim((string) $post->post_content) === '') {
            $payload['content'] = (string) $voyage->description;
        }
        if ($locationId > 0) {
            $payload['locations'] = [$locationId];
        }
        if ($this->execute) {
            $tours->updateTour($wp, $payload);
            $voyage->forceFill([
                'min_people' => $voyage->min_people ?: self::DEFAULT_MIN_PEOPLE,
                'max_people' => $voyage->max_people ?: self::DEFAULT_MAX_PEOPLE,
                'departure_policy' => $voyage->departure_policy ?: self::GENERIC_DEPARTURE_POLICY,
                'duration_text' => $voyage->duration_text ?: $durationDays . ' jour' . ($durationDays > 1 ? 's' : ''),
                'tours_exclude' => $voyage->tours_exclude ?: $exclude,
                'tours_highlight' => $voyage->tours_highlight ?: $highlights,
            ])->save();
        }
        $actions[] = 'infos';
        $this->bump('infos_tarifs_destination');

        // 2. Programme : les jours Laravel versés dans aj_tour_days.
        if (TourDay::query()->where('tour_id', $wp)->count() < $days->count()) {
            if ($this->execute) {
                $program->ensureDaysExist($wp, $days->count());
                $byNumber = TourDay::query()->where('tour_id', $wp)->get()->keyBy('day_number');
                foreach ($days as $day) {
                    $wpDay = $byNumber->get((int) $day->day_number);
                    if ($wpDay === null) {
                        continue;
                    }
                    $hotel = $hotels[min(count($hotels), max(1, (int) $day->day_number)) - 1] ?? ($hotels[0] ?? null);
                    $program->updateDay((int) $wpDay->id, [
                        'title' => (string) $day->title,
                        'description' => (string) ($day->description ?: strip_tags((string) $day->content_html)),
                        'accommodation' => $hotel ? $this->hotelLabel($hotel) : null,
                        'mode' => 'program',
                    ]);
                }
            }
            $actions[] = 'programme';
            $this->bump('programme_jours_wp');
        }

        // 3. Hôtels : catégorie et ville extraites, jamais de nom.
        if (TourHotel::query()->where('tour_id', $wp)->doesntExist()) {
            $rows = $hotels ?: [['stars' => self::DEFAULT_STARS, 'ville' => $city]];
            if ($this->execute) {
                foreach (array_values($rows) as $i => $hotel) {
                    TourHotel::create([
                        'tour_id' => $wp,
                        'day_number' => 1,
                        'check_in_day' => 1,
                        'check_out_day' => $lastDay,
                        'sort_order' => $i,
                        'is_optional' => 0,
                        'hotel_name' => $this->hotelLabel($hotel),
                        'stars' => $hotel['stars'],
                        'address' => $hotel['ville'],
                        'notes' => 'Établissement générique : à préciser à la réactivation de l’offre.',
                    ]);
                }
            }
            $actions[] = sprintf('hôtels(%d)', count($rows));
            $this->bump($hotels ? 'hotels_extraits' : 'hotels_generiques');
        }

        // 4. Transferts : aéroport ↔ hôtel, arrivée et départ.
        if (TourTransfer::query()->where('tour_id', $wp)->doesntExist()) {
            if ($this->execute) {
                $vehicle = $isNational ? 'Autocar' : 'Minibus';
                TourTransfer::create(['tour_id' => $wp, 'direction' => TourTransfer::DIRECTION_ARRIVAL, 'day_number' => 1, 'sort_order' => 0, 'is_optional' => 0, 'from_label' => $isNational ? 'Point de rendez-vous' : 'Aéroport de ' . $city, 'to_label' => 'Hôtel', 'vehicle_type' => $vehicle]);
                TourTransfer::create(['tour_id' => $wp, 'direction' => TourTransfer::DIRECTION_DEPARTURE, 'day_number' => $lastDay, 'sort_order' => 1, 'is_optional' => 0, 'from_label' => 'Hôtel', 'to_label' => $isNational ? 'Point de rendez-vous' : 'Aéroport de ' . $city, 'vehicle_type' => $vehicle]);
            }
            $actions[] = 'transferts';
            $this->bump('transferts');
        }

        // 5. Vols : uniquement quand il y en a réellement (international, Hajj/Omra).
        $firstDeparture = Departure::query()->where('voyage_id', $voyage->id)->orderBy('start_date')->first();
        if (! $isNational && VoyageFlightOption::query()->where('voyage_id', $voyage->id)->doesntExist()) {
            if ($this->execute) {
                $start = $firstDeparture?->start_date ? Carbon::parse($firstDeparture->start_date) : null;
                $flights->syncOptions($voyage->id, [
                    ['type' => VoyageFlightOption::TYPE_OUTBOUND, 'day_number' => 1, 'from_city' => self::HOME_CITY, 'to_city' => $city, 'departure_date' => $start?->toDateString(), 'flight_number' => ''],
                    ['type' => VoyageFlightOption::TYPE_RETURN, 'day_number' => $lastDay, 'from_city' => $city, 'to_city' => self::HOME_CITY, 'departure_date' => $start?->copy()->addDays($lastDay - 1)->toDateString(), 'flight_number' => ''],
                ], $lastDay);
                VoyageFlightOption::query()->where('voyage_id', $voyage->id)->update(['is_tentative' => 1, 'notes' => 'Vol à confirmer à la réactivation de l’offre.']);
                $flights->syncOptionsToWp($voyage->id, $wp, $lastDay);
            }
            $actions[] = 'vols';
            $this->bump($isHajj ? 'vols_hajj_omra' : 'vols_international');
        } elseif ($isNational) {
            $this->bump('sans_vol_national');
        }

        // 6. Activités.
        if (TravelDayItem::query()->where('voyage_id', $voyage->id)->where('type', 'activity')->doesntExist()) {
            if ($this->execute) {
                TravelDayItem::create(['voyage_id' => $voyage->id, 'day_number' => 1, 'start_day' => 1, 'end_day' => $lastDay, 'nights' => 0, 'type' => 'activity', 'title' => 'Visites et excursions selon programme', 'details' => 'Détail des activités communiqué à la réactivation de l’offre.', 'included' => 1, 'price_delta_per_person' => 0, 'sort_order' => 0]);
            }
            $actions[] = 'activités';
            $this->bump('activites_generiques');
        }

        // 7. Extras : les suppléments extraits, sinon un générique inactif.
        if (VoyageExtra::query()->where('voyage_id', $voyage->id)->doesntExist()) {
            $supplements = array_values(array_filter(array_map('trim', (array) ($li['supplements'] ?? [])), fn ($s) => $s !== ''));
            if ($this->execute) {
                if ($supplements !== []) {
                    foreach (array_slice($supplements, 0, 10) as $i => $label) {
                        VoyageExtra::create(['voyage_id' => $voyage->id, 'name' => Str::limit($label, 120), 'description' => $label, 'price_adult' => $this->amountFrom($label), 'price_child' => 0, 'is_active' => 1, 'sort_order' => $i, 'extra_type' => 'supplement', 'icon' => 'fa-plus-circle']);
                    }
                } else {
                    VoyageExtra::create(['voyage_id' => $voyage->id, 'name' => 'Assurance voyage (sur demande)', 'description' => 'Tarif communiqué à la réactivation de l’offre.', 'price_adult' => 0, 'price_child' => 0, 'is_active' => 0, 'sort_order' => 0, 'extra_type' => 'supplement', 'icon' => 'fa-plus-circle']);
                }
            }
            $actions[] = sprintf('extras(%d)', max(1, min(10, count($supplements))));
            $this->bump($supplements ? 'extras_extraits' : 'extras_generiques');
        }

        // 8. Disponibilités : chaque départ Laravel devient une date WordPress ; sans départ, la
        //    date d'expiration de l'ancienne offre sert de date passée.
        $departures = Departure::query()->where('voyage_id', $voyage->id)->orderBy('start_date')->get();
        if ($departures->isEmpty()) {
            $expiry = trim((string) ($li['date_expiration'] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry)) {
                if ($this->execute) {
                    $departure = Departure::create(['voyage_id' => $voyage->id, 'start_date' => $expiry, 'end_date' => Carbon::parse($expiry)->addDays($lastDay - 1)->toDateString(), 'status' => Departure::STATUS_CLOSED, 'total_capacity' => 0, 'reserved_capacity' => 0, 'available_capacity' => 0, 'base_price' => $voyage->price_from, 'sale_price' => $voyage->price_from, 'notes' => 'Date de fin de validité de l’ancienne offre (ajinsafro.ma).']);
                    $departures = collect([$departure]);
                }
                $actions[] = 'date d’expiration';
                $this->bump('dates_depuis_expiration');
            } else {
                $this->bump('sans_aucune_date');
            }
        }
        $newDates = 0;
        foreach ($departures as $departure) {
            $date = Carbon::parse($departure->start_date)->toDateString();
            $exists = TravelDate::query()->where('travel_id', $wp)->where('date', $date)->first();
            if ($exists !== null) {
                if ($this->execute && ! $departure->wp_travel_date_id) {
                    $departure->forceFill(['wp_travel_date_id' => $exists->id])->save();
                }

                continue;
            }
            if ($this->execute) {
                $travelDate = TravelDate::create(['travel_id' => $wp, 'date' => $date, 'is_active' => 1, 'seats' => (int) $departure->total_capacity ?: self::DEFAULT_MAX_PEOPLE]);
                $departure->forceFill(['wp_travel_date_id' => $travelDate->id])->save();
            }
            $newDates++;
        }
        if ($newDates > 0) {
            $actions[] = sprintf('dates(%d)', $newDates);
            $this->stats['dates_wp_creees'] = ($this->stats['dates_wp_creees'] ?? 0) + $newDates;
        }

        // 9. Thèmes.
        if (! $voyage->themes()->exists()) {
            $theme = $this->themeFor($category);
            if ($theme !== null) {
                if ($this->execute) {
                    $voyage->themes()->syncWithoutDetaching([$theme->id]);
                }
                $actions[] = 'thème';
                $this->bump('themes');
            }
        }

        // 10. Logistique.
        $meta = is_array($voyage->logistics_meta) ? $voyage->logistics_meta : [];
        if (trim((string) ($meta['transport'] ?? '')) === '') {
            $meta['transport'] = $isNational ? 'Autocar' : 'Avion';
            $actions[] = 'logistique';
            $this->bump('logistique');
        }

        // 11. Médias : couverture empruntée à une fiche de même destination.
        if (trim((string) $post->getMeta('_thumbnail_id')) === '') {
            $donor = $this->thumbnailDonor($voyage);
            if ($donor > 0) {
                if ($this->execute) {
                    $post->setMeta('_thumbnail_id', (string) $donor);
                }
                $actions[] = 'couverture empruntée';
                $this->bump('couvertures_empruntees');
            } else {
                $this->bump('sans_aucune_photo');
            }
        }

        // 12. Publication.
        if ($publish) {
            if ($this->execute) {
                $tours->updateTour($wp, ['post_status' => 'publish']);
                $meta['completion']['status'] = 'finalized';
                $voyage->status = 'actif';
            }
            $actions[] = 'publiée';
            $this->bump('publiees');
        }

        if ($this->execute) {
            $voyage->logistics_meta = $meta;
            $voyage->save();
        }

        return $actions;
    }

    // ----------------------------------------------------------------------------------------

    private function legacyId(Voyage $voyage): int
    {
        return (int) data_get($voyage->logistics_meta, 'legacy_import.legacy_id');
    }

    private function bump(string $key): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + 1;
    }

    /** Lieux Traveler publiés, indexés par titre normalisé. */
    private function loadLocations(): void
    {
        $this->locations = [];
        foreach (WpPost::query()->where('post_type', 'st_location')->where('post_status', 'publish')->get(['ID', 'post_title']) as $row) {
            $this->locations[$this->norm((string) $row->post_title)] = (int) $row->ID;
        }
    }

    /** Un lieu Traveler par destination ; créé s'il manque (aucun n'existait à l'audit). */
    private function ensureLocation(string $title): int
    {
        $title = trim($title);
        if ($title === '') {
            return 0;
        }
        $key = $this->norm($title);
        if (isset($this->locations[$key])) {
            return $this->locations[$key];
        }
        if (! $this->execute) {
            $this->locations[$key] = -1; // compté une seule fois en simulation
            $this->bump('lieux_a_creer');

            return 0;
        }

        $now = Carbon::now();
        $post = new WpPost();
        $post->post_author = 1;
        $post->post_date = $now->format('Y-m-d H:i:s');
        $post->post_date_gmt = $now->utc()->format('Y-m-d H:i:s');
        $post->post_content = '';
        $post->post_title = $title;
        $post->post_excerpt = '';
        $post->post_status = 'publish';
        $post->comment_status = 'closed';
        $post->ping_status = 'closed';
        $post->post_password = '';
        $post->post_name = Str::slug($title);
        $post->to_ping = '';
        $post->pinged = '';
        $post->post_modified = $now->format('Y-m-d H:i:s');
        $post->post_modified_gmt = $now->utc()->format('Y-m-d H:i:s');
        $post->post_content_filtered = '';
        $post->post_parent = 0;
        $post->guid = '';
        $post->menu_order = 0;
        $post->post_type = 'st_location';
        $post->post_mime_type = '';
        $post->comment_count = 0;
        $post->save();

        $this->locations[$key] = (int) $post->ID;
        $this->bump('lieux_crees');

        return (int) $post->ID;
    }

    /**
     * @return list<array{stars:int, ville:string}>
     */
    private function normalizedHotels(array $li): array
    {
        $out = [];
        foreach ((array) ($li['hotels'] ?? []) as $h) {
            if (! is_array($h)) {
                continue;
            }
            $stars = (int) preg_replace('/\D/', '', (string) ($h['categorie'] ?? '')) ?: self::DEFAULT_STARS;
            $ville = trim((string) ($h['ville'] ?? ''));
            $out[] = ['stars' => max(1, min(5, $stars)), 'ville' => $ville];
        }

        return $out;
    }

    private function hotelLabel(array $hotel): string
    {
        $label = sprintf('Hôtel %d étoiles', $hotel['stars']);

        return $hotel['ville'] !== '' ? $label . ' — ' . $hotel['ville'] : $label;
    }

    private function durationDays(Voyage $voyage, int $lastDay): int
    {
        if (preg_match('/(\d+)\s*j/iu', (string) $voyage->duration_text, $m)) {
            return max(1, (int) $m[1]);
        }

        return $lastDay;
    }

    /**
     * @return list<string>
     */
    private function lines(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/\r?\n/', $value);
        }
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($v) => trim((string) (is_array($v) ? ($v['label'] ?? $v['title'] ?? '') : $v)), $value), fn ($v) => $v !== ''));
    }

    /** « Supp. Single : +4900 DHs » → 4900. */
    private function amountFrom(string $label): int
    {
        if (preg_match('/(\d[\d\s.,]*)\s*(?:DHS?|MAD)/iu', $label, $m)) {
            return (int) preg_replace('/\D/', '', $m[1]);
        }

        return 0;
    }

    private function themeFor(string $category): ?VoyageTheme
    {
        foreach (self::THEME_BY_CATEGORY as $needle => $name) {
            if (str_contains($category, $needle)) {
                return VoyageTheme::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            }
        }

        return VoyageTheme::query()->whereRaw('LOWER(name) = ?', ['voyage organisé'])->first();
    }

    /** Attachment de couverture d'une autre fiche historique de même destination. */
    private function thumbnailDonor(Voyage $voyage): int
    {
        $candidates = Voyage::query()
            ->where('id', '!=', $voyage->id)
            ->where('destination', $voyage->destination)
            ->whereNotNull('wp_post_id')
            ->pluck('wp_post_id')
            ->map('intval')
            ->all();
        if ($candidates === []) {
            return 0;
        }
        $meta = WpPostMeta::query()
            ->whereIn('post_id', $candidates)
            ->where('meta_key', '_thumbnail_id')
            ->where('meta_value', '!=', '')
            ->orderBy('post_id')
            ->first();

        return $meta ? (int) $meta->meta_value : 0;
    }

    private function norm(string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));
    }
}
