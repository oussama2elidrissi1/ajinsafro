<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\Reservation;
use App\Models\TourHotel;
use App\Models\TravelDate;
use App\Models\User;
use App\Models\Voyage;
use App\Models\VoyageFlight;
use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Catalogue workspace : une ligne « package » par tour WordPress (même liste que /admin/circuits/voyages).
 * Titre / code = WordPress. Prix package = même source que Circuits / voyages : meta WP {@see WpPost::getMeta('adult_price')}.
 * Si absent, repli {@see Voyage::price_from}.
 * Date de départ package = section « Disponibilité » du CRUD voyage : {@see TravelDate} (wp.aj_travel_dates, travel_id = wp_post_id),
 * synchronisée par {@see VoyageController::syncTravelDates()} depuis le formulaire travel_dates[*].
 * Vols / hébergements : {@see Voyage::departures()} inchangé pour l’alignement date ↔ travel_date_id.
 */
class ReservationWorkspaceCatalogService
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, meta: array<string, int|bool|array>}
     */
    public function buildRows(User $user): array
    {
        $today = Carbon::today();
        $rows = collect();
        $meta = [
            'wp_connection_failed' => false,
            'wp_tour_count' => 0,
            'laravel_voyage_matched' => 0,
            'package_rows' => 0,
            'vol_rows' => 0,
            'hebergement_rows' => 0,
            'total_rows' => 0,
        ];

        try {
            $wpTours = AdminWpTourCatalogQuery::allToursOrdered();
        } catch (\Throwable $e) {
            Log::warning('ReservationWorkspaceCatalog: WP indisponible', ['error' => $e->getMessage()]);
            $meta['wp_connection_failed'] = true;

            return ['rows' => collect(), 'meta' => $meta];
        }

        $meta['wp_tour_count'] = $wpTours->count();

        $wpTourIdsOrdered = $wpTours->pluck('ID')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $wpIds = $wpTourIdsOrdered;

        $wpPostTitleById = $wpTours->keyBy(fn (WpPost $p) => (int) $p->ID)
            ->map(fn (WpPost $p) => trim((string) $p->post_title) ?: 'Tour #'.(int) $p->ID);

        /** Même clé que {@see VoyageController::index} : postmeta meta_key = adult_price */
        $adultPriceMetaByPostId = [];
        if ($wpIds !== []) {
            $adultPriceMetaByPostId = WpPostMeta::query()
                ->whereIn('post_id', $wpIds)
                ->where('meta_key', 'adult_price')
                ->orderBy('meta_id')
                ->get()
                ->groupBy(fn (WpPostMeta $m) => (int) $m->post_id)
                ->map(fn ($group) => $group->last()->meta_value)
                ->all();
        }

        $voyagesByWp = collect();
        $laravelDuplicatesByWpPostId = [];
        if ($wpIds !== []) {
            $voyagesRaw = Voyage::query()
                ->whereIn('wp_post_id', $wpIds)
                ->with([
                    'departures' => function ($q) {
                        $q->orderBy('start_date');
                    },
                ])
                ->orderBy('id')
                ->get();

            $laravelDuplicatesByWpPostId = $voyagesRaw
                ->groupBy(fn (Voyage $v) => (int) $v->wp_post_id)
                ->filter(fn ($group) => $group->count() > 1)
                ->map(fn ($group) => $group->pluck('id')->map(fn ($id) => (int) $id)->values()->all())
                ->all();

            if ($laravelDuplicatesByWpPostId !== []) {
                Log::warning('ReservationWorkspaceCatalog: plusieurs lignes voyages pour le même wp_post_id', [
                    'duplicates' => $laravelDuplicatesByWpPostId,
                    'note' => 'Une seule fiche Laravel est retenue par wp_post_id (priorité au plus petit voyages.id).',
                ]);
            }

            $voyagesByWp = $voyagesRaw
                ->unique('wp_post_id')
                ->keyBy(fn (Voyage $v) => (int) $v->wp_post_id);
        }

        $meta['laravel_voyage_matched'] = $voyagesByWp->count();

        if (config('app.debug')) {
            $laravelWpIds = $voyagesByWp->keys()->map(fn ($k) => (int) $k)->values()->all();
            sort($laravelWpIds);
            $meta['wp_tour_ids'] = $wpTourIdsOrdered;
            $meta['laravel_wp_post_ids_matched'] = $laravelWpIds;
            $meta['wp_tour_ids_without_laravel'] = array_values(array_diff($wpTourIdsOrdered, $laravelWpIds));
            $meta['laravel_duplicates_wp_post_id'] = $laravelDuplicatesByWpPostId;
        }

        $laravelIdsForExtras = $voyagesByWp->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $statsByTour = $this->batchReservationStatsByTour($user, $laravelIdsForExtras);

        $wpIdsWithLaravel = $voyagesByWp->keys()->map(fn ($k) => (int) $k)->values()->all();
        $travelDatesByWpTourId = collect();
        if ($wpIdsWithLaravel !== []) {
            $travelDatesByWpTourId = TravelDate::query()
                ->whereIn('travel_id', $wpIdsWithLaravel)
                ->where('is_active', true)
                ->orderBy('date')
                ->get()
                ->groupBy(fn (TravelDate $td) => (int) $td->travel_id);
        }

        $packagePriceDebug = [];
        $packageDepartureDebug = [];

        foreach ($wpTours as $wp) {
            if (! $wp instanceof WpPost) {
                continue;
            }
            $wpId = (int) $wp->ID;
            /** @var Voyage|null $voyage */
            $voyage = $voyagesByWp->get($wpId);

            $tdColl = collect();
            if ($voyage && (int) $voyage->wp_post_id > 0) {
                $tdColl = $travelDatesByWpTourId->get((int) $voyage->wp_post_id) ?? collect();
            }
            $pickTd = $this->pickTravelDateForPackageDisplay($tdColl, $today);
            $pickedTd = $pickTd['travelDate'];

            $departureId = null;
            $startDate = $pickedTd?->date;
            $travelDateId = $pickedTd?->id;
            $tourId = $voyage ? (int) $voyage->id : null;

            $wpDisplayTitle = $wpPostTitleById->get($wpId) ?? (trim((string) $wp->post_title) ?: 'Tour #'.$wpId);

            $adultPriceRaw = $adultPriceMetaByPostId[$wpId] ?? $wp->getMeta('adult_price');
            $priceLabel = $this->formatPackagePriceLabel($adultPriceRaw, $voyage);

            if (config('app.debug')) {
                $packagePriceDebug[] = [
                    'wp_post_id' => $wpId,
                    'title' => mb_substr($wpDisplayTitle, 0, 80),
                    'adult_price_meta_raw' => $adultPriceRaw,
                    'parsed_wp_adult' => $this->parseWpAdultPriceToFloat($adultPriceRaw),
                    'laravel_price_from' => $voyage?->price_from,
                    'price_label_final' => $priceLabel,
                    'price_source' => $this->resolvePackagePriceSource($adultPriceRaw, $voyage, $priceLabel),
                ];
                $packageDepartureDebug[] = [
                    'wp_post_id' => $wpId,
                    'laravel_voyage_id' => $voyage?->id,
                    'storage' => 'wp.aj_travel_dates (model TravelDate), travel_id = voyages.wp_post_id',
                    'active_travel_dates_ymd' => $tdColl
                        ->map(fn (TravelDate $d) => $d->date?->format('Y-m-d'))
                        ->filter()
                        ->values()
                        ->all(),
                    'picked_travel_date_id' => $pickedTd?->id,
                    'picked_date_ymd' => $startDate?->format('Y-m-d'),
                    'workspace_display_is_past' => $pickTd['is_past'],
                    'no_laravel_voyage' => $voyage === null,
                    'no_availability_rows' => $voyage !== null && $tdColl->isEmpty(),
                ];
            }

            $rows->push([
                'type' => 'package',
                'code' => '#'.$wpId,
                'name' => $wpDisplayTitle,
                'subtitle' => $voyage
                    ? null
                    : ('Pas de fiche Laravel — renseignez voyages.wp_post_id = '.$wpId.' pour réserver / stats.'),
                'voyage_id' => $tourId,
                'wp_post_id' => $wpId,
                'laravel_synced' => $voyage !== null,
                'departure_id' => $departureId,
                'flight_id' => null,
                'tour_hotel_wp_id' => null,
                'travel_date_id' => $travelDateId,
                'departure_date' => $startDate,
                'departure_is_past' => $pickTd['is_past'],
                'departure_is_canceled' => false,
                'price_label' => $priceLabel,
                'voyage_destination' => $voyage && trim((string) $voyage->destination) !== '' ? trim((string) $voyage->destination) : null,
                'stats' => $tourId ? ($statsByTour[$tourId] ?? $this->emptyStats()) : $this->emptyStats(),
            ]);
        }

        if (config('app.debug')) {
            $meta['package_price_debug'] = $packagePriceDebug;
            $meta['package_departure_debug'] = $packageDepartureDebug;
            $meta['package_departure_source_doc'] = 'Disponibilité CRUD → request travel_dates → VoyageController::syncTravelDates → TravelDate (wp.aj_travel_dates)';
            Log::debug('Workspace package prices (alignés sur VoyageController@index adult_price meta)', [
                'packages' => $packagePriceDebug,
            ]);
            Log::debug('Workspace package départs (Disponibilité = aj_travel_dates)', [
                'packages' => $packageDepartureDebug,
            ]);
        }

        $meta['package_rows'] = $rows->count();

        $flightQuery = VoyageFlight::query()
            ->with('voyage')
            ->whereIn('voyage_id', $laravelIdsForExtras ?: [0])
            ->orderBy('departure_date');

        foreach ($flightQuery->get() as $flight) {
            $voyage = $flight->voyage;
            if (! $voyage) {
                continue;
            }
            $tourId = (int) $voyage->id;
            $label = trim(($flight->flight_number ?: 'Vol').' · '.$flight->from_label.' → '.$flight->to_label);
            $wpPid = $voyage->wp_post_id ? (int) $voyage->wp_post_id : null;
            $rows->push([
                'type' => 'vol',
                'code' => 'VOL-'.$flight->id,
                'name' => $label,
                'subtitle' => $wpPid ? ($wpPostTitleById->get($wpPid) ?: $voyage->name) : $voyage->name,
                'voyage_id' => $tourId,
                'wp_post_id' => $voyage->wp_post_id ? (int) $voyage->wp_post_id : null,
                'laravel_synced' => true,
                'departure_id' => null,
                'flight_id' => (int) $flight->id,
                'tour_hotel_wp_id' => null,
                'travel_date_id' => $this->resolveTravelDateId($voyage, $flight->departure_date),
                'departure_date' => $flight->departure_date,
                'departure_is_past' => $flight->departure_date && $flight->departure_date->lt($today),
                'stats' => $statsByTour[$tourId] ?? $this->emptyStats(),
            ]);
        }

        foreach ($voyagesByWp as $voyage) {
            if (! $voyage->wp_post_id) {
                continue;
            }
            $tourId = (int) $voyage->id;
            $departures = $voyage->departures instanceof EloquentCollection
                ? $voyage->departures
                : collect($voyage->departures ?? []);
            $pick = $this->pickDepartureForDisplay($departures, $today);
            $startDate = $pick['departure']?->start_date;

            $wpPidHotel = (int) $voyage->wp_post_id;
            $tourWpTitle = $wpPostTitleById->get($wpPidHotel) ?: $voyage->name;
            foreach (TourHotel::getAllForTour($wpPidHotel) as $hotel) {
                $rows->push([
                    'type' => 'hebergement',
                    'code' => 'HOT-'.$hotel->id,
                    'name' => $hotel->hotel_name ?: 'Hébergement',
                    'subtitle' => $tourWpTitle,
                    'voyage_id' => $tourId,
                    'wp_post_id' => (int) $voyage->wp_post_id,
                    'laravel_synced' => true,
                    'departure_id' => $pick['departure']?->id,
                    'flight_id' => null,
                    'tour_hotel_wp_id' => (int) $hotel->id,
                    'travel_date_id' => $this->resolveTravelDateId($voyage, $startDate),
                    'departure_date' => $startDate,
                    'departure_is_past' => $pick['is_past'],
                    'stats' => $statsByTour[$tourId] ?? $this->emptyStats(),
                ]);
            }
        }

        $finalRows = $rows->unique(fn ($r) => $r['type'].'-'.$r['code'])->values();

        $meta['vol_rows'] = $finalRows->where('type', 'vol')->count();
        $meta['hebergement_rows'] = $finalRows->where('type', 'hebergement')->count();
        $meta['total_rows'] = $finalRows->count();

        if (config('app.debug')) {
            Log::debug('ReservationWorkspaceCatalog built', [
                'wp_tour_count' => $meta['wp_tour_count'],
                'wp_tour_ids' => $meta['wp_tour_ids'] ?? [],
                'laravel_wp_post_ids_matched' => $meta['laravel_wp_post_ids_matched'] ?? [],
                'wp_tour_ids_without_laravel' => $meta['wp_tour_ids_without_laravel'] ?? [],
                'laravel_duplicates_wp_post_id' => $meta['laravel_duplicates_wp_post_id'] ?? [],
                'laravel_voyage_matched' => $meta['laravel_voyage_matched'],
                'package_rows' => $meta['package_rows'],
                'vol_rows' => $meta['vol_rows'],
                'hebergement_rows' => $meta['hebergement_rows'],
                'total_rows' => $meta['total_rows'],
            ]);
        }

        return ['rows' => $finalRows, 'meta' => $meta];
    }

    /**
     * @return array{departure: ?Departure, is_past: bool}
     */
    private function pickDepartureForDisplay(Collection $departures, Carbon $today): array
    {
        $eligible = $departures->filter(fn ($d) => $d instanceof Departure
            && in_array($d->status, [Departure::STATUS_OPEN, Departure::STATUS_FULL], true)
            && $d->start_date
        )->sortBy(fn (Departure $d) => $d->start_date->timestamp);

        $future = $eligible->first(fn (Departure $d) => ! $d->start_date->lt($today));
        if ($future) {
            return ['departure' => $future, 'is_past' => false];
        }

        $past = $eligible->filter(fn (Departure $d) => $d->start_date->lt($today))->sortByDesc(fn (Departure $d) => $d->start_date->timestamp)->first();

        return [
            'departure' => $past,
            'is_past' => $past !== null,
        ];
    }

    /**
     * Dates actives de la section Disponibilité (CRUD) : {@see TravelDate}.
     *
     * @return array{travelDate: ?TravelDate, is_past: bool}
     */
    private function pickTravelDateForPackageDisplay(Collection $travelDates, Carbon $today): array
    {
        $sorted = $travelDates->filter(fn ($d) => $d instanceof TravelDate && $d->date)
            ->sortBy(fn (TravelDate $d) => $d->date->timestamp);

        if ($sorted->isEmpty()) {
            return ['travelDate' => null, 'is_past' => false];
        }

        $future = $sorted->first(fn (TravelDate $d) => ! $d->date->lt($today));
        if ($future) {
            return ['travelDate' => $future, 'is_past' => false];
        }

        $past = $sorted->filter(fn (TravelDate $d) => $d->date->lt($today))
            ->sortByDesc(fn (TravelDate $d) => $d->date->timestamp)
            ->first();

        return [
            'travelDate' => $past,
            'is_past' => $past !== null,
        ];
    }

    /**
     * Colonne « Prix Adulte » Circuits / voyages : {@see VoyageController::index} applique `$tour->getMeta('adult_price')`.
     * Même meta `postmeta.meta_key = adult_price`, format identique à la vue (`number_format` + MAD).
     */
    private function formatPackagePriceLabel(mixed $adultPriceMetaRaw, ?Voyage $voyage): ?string
    {
        $wpAmount = $this->parseWpAdultPriceToFloat($adultPriceMetaRaw);
        if ($wpAmount !== null && $wpAmount > 0) {
            return number_format($wpAmount, 0, ',', ' ').' MAD';
        }

        return $this->formatVoyagePriceLabel($voyage);
    }

    private function parseWpAdultPriceToFloat(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        if (is_int($raw) || is_float($raw)) {
            $f = (float) $raw;

            return $f > 0 ? $f : null;
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^[aOs]:/i', $s)) {
            $u = @unserialize($s, ['allowed_classes' => false]);
            if (is_numeric($u)) {
                $f = (float) $u;

                return $f > 0 ? $f : null;
            }
            if (is_array($u)) {
                foreach (['price', 'adult_price', 'adult', 'value'] as $k) {
                    if (isset($u[$k]) && is_numeric($u[$k])) {
                        $f = (float) $u[$k];

                        return $f > 0 ? $f : null;
                    }
                }
            }

            return null;
        }
        $s = str_replace("\xc2\xa0", '', $s);
        $s = preg_replace('/\s+/', '', $s);
        $s = str_replace(',', '.', $s);
        if (! is_numeric($s)) {
            return null;
        }
        $f = (float) $s;

        return $f > 0 ? $f : null;
    }

    private function resolvePackagePriceSource(mixed $adultPriceRaw, ?Voyage $voyage, ?string $finalLabel): string
    {
        $wp = $this->parseWpAdultPriceToFloat($adultPriceRaw);
        if ($wp !== null && $wp > 0) {
            return 'wp_postmeta.adult_price';
        }
        if ($voyage && $voyage->price_from !== null && (int) $voyage->price_from > 0 && $finalLabel !== null) {
            return 'laravel.voyages.price_from';
        }

        return 'none';
    }

    private function formatVoyagePriceLabel(?Voyage $voyage): ?string
    {
        if (! $voyage || $voyage->price_from === null || (int) $voyage->price_from <= 0) {
            return null;
        }

        $cur = trim((string) ($voyage->currency ?? ''));
        if ($cur === '') {
            $cur = 'MAD';
        }

        return number_format((float) $voyage->price_from, 0, ',', ' ').' '.$cur;
    }

    /**
     * @param  array<int>  $tourIds
     * @return array<int, array{validee: int, en_cours: int, annulee: int}>
     */
    private function batchReservationStatsByTour(User $user, array $tourIds): array
    {
        $base = array_fill_keys($tourIds, $this->emptyStats());
        if ($tourIds === []) {
            return $base;
        }

        $q = Reservation::query()->whereIn('tour_id', $tourIds);
        $this->branchScope->scopeReservations($q, $user);
        $this->branchScope->constrainReservationQueryForPortalUser($q, $user);

        $aggregates = (clone $q)
            ->selectRaw('tour_id, status, COUNT(*) as aggregate')
            ->groupBy('tour_id', 'status')
            ->get();

        foreach ($aggregates as $row) {
            $tid = (int) $row->tour_id;
            if (! isset($base[$tid])) {
                continue;
            }
            $n = (int) $row->aggregate;
            match ($row->status) {
                Reservation::STATUS_VALIDEE => $base[$tid]['validee'] += $n,
                Reservation::STATUS_EN_COURS => $base[$tid]['en_cours'] += $n,
                Reservation::STATUS_ANNULEE => $base[$tid]['annulee'] += $n,
                default => null,
            };
        }

        return $base;
    }

    /**
     * @return array{validee: int, en_cours: int, annulee: int}
     */
    private function emptyStats(): array
    {
        return ['validee' => 0, 'en_cours' => 0, 'annulee' => 0];
    }

    private function resolveTravelDateId(Voyage $voyage, $date): ?int
    {
        if (! $voyage->wp_post_id || $date === null) {
            return null;
        }
        $d = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return TravelDate::query()
            ->where('travel_id', (int) $voyage->wp_post_id)
            ->whereDate('date', $d)
            ->where('is_active', true)
            ->value('id');
    }
}
