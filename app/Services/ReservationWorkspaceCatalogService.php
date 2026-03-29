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
use App\Support\TourPlacesCalculator;
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
 *
 * Places / chambres : {@see TourHotel} (wp.aj_tour_hotels.tour_id = ID WordPress du tour) + {@see TourHotelRoom}
 * (wp.aj_tour_hotel_rooms). Calcul identique à l’édition voyage : {@see TourPlacesCalculator::explainFromDatabase()}.
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
        $passengersByTour = $this->batchPassengersBookedByTour($user, $laravelIdsForExtras);

        $extraWpMetaByPostId = [];
        if ($wpIds !== []) {
            $extraWpMetaByPostId = WpPostMeta::query()
                ->whereIn('post_id', $wpIds)
                ->whereIn('meta_key', ['child_price', 'duration_day'])
                ->orderBy('meta_id')
                ->get()
                ->groupBy(fn (WpPostMeta $m) => (int) $m->post_id)
                ->map(function (Collection $group) {
                    return [
                        'child_price' => $group->where('meta_key', 'child_price')->last()?->meta_value,
                        'duration_day' => $group->where('meta_key', 'duration_day')->last()?->meta_value,
                    ];
                })
                ->all();
        }

        $wpIdsWithLaravel = $voyagesByWp->keys()->map(fn ($k) => (int) $k)->values()->all();
        /** @var Collection<int, \Illuminate\Support\Collection<int, TourHotel>> $hotelsByWpTourId */
        $hotelsByWpTourId = collect();
        if ($wpIdsWithLaravel !== []) {
            $hotelsByWpTourId = TourHotel::query()
                ->whereIn('tour_id', $wpIdsWithLaravel)
                ->with(['rooms' => function ($q) {
                    $q->orderBy('sort_order')->orderBy('id');
                }])
                ->get()
                ->groupBy(fn (TourHotel $h) => (int) $h->tour_id)
                ->map(function (Collection $group) {
                    return $group->sortBy(function (TourHotel $h) {
                        return [
                            (int) ($h->check_in_day ?? $h->day_number ?? 1),
                            (int) ($h->sort_order ?? 0),
                            (int) $h->id,
                        ];
                    })->values();
                });
        }

        $travelDatesByWpTourId = collect();
        if ($wpIds !== []) {
            $travelDatesByWpTourId = TravelDate::query()
                ->whereIn('travel_id', $wpIds)
                ->where('is_active', true)
                ->orderBy('date')
                ->get()
                ->groupBy(fn (TravelDate $td) => (int) $td->travel_id);
        }

        $packagePriceDebug = [];
        $packageDepartureDebug = [];
        $packagePlacesDebug = [];

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
            } elseif ($voyage === null) {
                $tdColl = $travelDatesByWpTourId->get($wpId) ?? collect();
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

            $placesPayload = $this->resolvePackagePlacesPayload($wpId, $voyage, $hotelsByWpTourId);

            $extraMeta = $extraWpMetaByPostId[$wpId] ?? ['child_price' => null, 'duration_day' => null];
            $childRaw = $extraMeta['child_price'] ?? $wp->getMeta('child_price');
            $durationRaw = $extraMeta['duration_day'] ?? $wp->getMeta('duration_day');
            $paxBooked = $tourId ? (int) ($passengersByTour[$tourId] ?? 0) : 0;
            $rowStats = $tourId ? ($statsByTour[$tourId] ?? $this->emptyStats()) : $this->emptyStats();
            $hasFutureTravelDate = $tdColl->contains(fn (TravelDate $d) => $d->date && ! $d->date->lt($today));
            $wsAvail = $this->computeWorkspaceAvailabilityBand($placesPayload, $paxBooked);

            if (config('app.debug') && $voyage !== null && count($packagePlacesDebug) < 8) {
                $hotelsForDbg = $hotelsByWpTourId->get($wpId) ?? collect();
                $packagePlacesDebug[] = [
                    'wp_post_id' => $wpId,
                    'laravel_voyage_id' => $voyage->id,
                    'join_rule' => 'aj_tour_hotels.tour_id = voyages.wp_post_id (ID tour WordPress)',
                    'tables' => 'aj_tour_hotels + aj_tour_hotel_rooms (connection wp)',
                    'fields' => 'room_count, capacity_total, capacity_adults, capacity_children, room_type, is_active',
                    'calculator' => TourPlacesCalculator::class.'::explainFromDatabase',
                    'hotels_count' => $hotelsForDbg->count(),
                    'rooms_rows_count' => $hotelsForDbg->sum(fn (TourHotel $h) => $h->rooms->count()),
                    'total_places' => $placesPayload['total'],
                    'lines' => $placesPayload['lines'],
                    'ignored' => $placesPayload['ignored'],
                    'state' => $placesPayload['state'],
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
                'stats' => $rowStats,
                'places_state' => $placesPayload['state'],
                'places_total' => $placesPayload['total'],
                'places_lines' => $placesPayload['lines'],
                'places_ignored' => $placesPayload['ignored'],
                'ws_avail' => $wsAvail,
                'ws_has_future' => $hasFutureTravelDate,
                'modal_detail' => ($packageModalDetail = $this->buildPackageModalDetail(
                    $wp,
                    $voyage,
                    $wpId,
                    $wpDisplayTitle,
                    $tdColl,
                    $today,
                    $placesPayload,
                    $rowStats,
                    $paxBooked,
                    $childRaw,
                    $durationRaw,
                    $priceLabel,
                    $voyage && trim((string) $voyage->destination) !== '' ? trim((string) $voyage->destination) : null,
                    $travelDateId,
                )),
                'form_prefill' => $this->buildPackageFormPrefill(
                    '#'.$wpId,
                    $packageModalDetail,
                    $this->parseWpAdultPriceToFloat($adultPriceRaw),
                    $this->parseWpAdultPriceToFloat($childRaw)
                ),
            ]);
        }

        if (config('app.debug')) {
            $meta['package_price_debug'] = $packagePriceDebug;
            $meta['package_departure_debug'] = $packageDepartureDebug;
            $meta['package_places_debug'] = $packagePlacesDebug;
            $meta['package_departure_source_doc'] = 'Disponibilité CRUD → request travel_dates → VoyageController::syncTravelDates → TravelDate (wp.aj_travel_dates)';
            $meta['package_places_source_doc'] = 'TourHotel (wp.aj_tour_hotels.tour_id = wp_post_id) → TourHotelRoom (wp.aj_tour_hotel_rooms) ; places = TourPlacesCalculator::explainFromDatabase (identique édition voyage).';
            Log::debug('Workspace package prices (alignés sur VoyageController@index adult_price meta)', [
                'packages' => $packagePriceDebug,
            ]);
            Log::debug('Workspace package départs (Disponibilité = aj_travel_dates)', [
                'packages' => $packageDepartureDebug,
            ]);
            Log::debug('Workspace package places (chambres / capacité)', [
                'sample' => $packagePlacesDebug,
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
            $travelDateIdVol = $this->resolveTravelDateId($voyage, $flight->departure_date);
            $volStats = $statsByTour[$tourId] ?? $this->emptyStats();
            $volModalDetail = $this->buildExtraModalDetail(
                'vol',
                $label,
                $tourId,
                $wpPid,
                $volStats,
                $flight->departure_date,
                $travelDateIdVol,
            );
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
                'travel_date_id' => $travelDateIdVol,
                'departure_date' => $flight->departure_date,
                'departure_is_past' => $flight->departure_date && $flight->departure_date->lt($today),
                'stats' => $volStats,
                'ws_avail' => 'na',
                'ws_has_future' => $flight->departure_date && ! $flight->departure_date->lt($today),
                'modal_detail' => $volModalDetail,
                'form_prefill' => $this->buildExtraFormPrefill('VOL-'.$flight->id, 'vol', $volModalDetail),
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
                $travelDateIdHot = $this->resolveTravelDateId($voyage, $startDate);
                $hotTitle = ($hotel->hotel_name ?: 'Hébergement').' — '.$tourWpTitle;
                $hotStats = $statsByTour[$tourId] ?? $this->emptyStats();
                $hotFuture = $startDate instanceof Carbon
                    ? ! $startDate->lt($today)
                    : ($startDate ? ! Carbon::parse($startDate)->lt($today) : false);
                $hotModalDetail = $this->buildExtraModalDetail(
                    'hebergement',
                    $hotTitle,
                    $tourId,
                    $wpPidHotel,
                    $hotStats,
                    $startDate,
                    $travelDateIdHot,
                );
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
                    'travel_date_id' => $travelDateIdHot,
                    'departure_date' => $startDate,
                    'departure_is_past' => $pick['is_past'],
                    'stats' => $hotStats,
                    'ws_avail' => 'na',
                    'ws_has_future' => $hotFuture,
                    'modal_detail' => $hotModalDetail,
                    'form_prefill' => $this->buildExtraFormPrefill('HOT-'.$hotel->id, 'hebergement', $hotModalDetail),
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
     * Places affichées workspace = même règle que l’édition circuit/voyage {@see TourPlacesCalculator}.
     *
     * @param  Collection<int, \Illuminate\Support\Collection<int, TourHotel>>  $hotelsByWpTourId
     * @return array{state: string, total: ?int, lines: list<array<string, mixed>>, ignored: list<array<string, mixed>>}
     */
    private function resolvePackagePlacesPayload(int $wpPostId, ?Voyage $voyage, Collection $hotelsByWpTourId): array
    {
        if ($voyage === null) {
            return ['state' => 'no_laravel', 'total' => null, 'lines' => [], 'ignored' => []];
        }

        $hotels = $hotelsByWpTourId->get($wpPostId) ?? collect();
        if ($hotels->isEmpty()) {
            return ['state' => 'no_hotels', 'total' => null, 'lines' => [], 'ignored' => []];
        }

        $explain = TourPlacesCalculator::explainFromDatabase($hotels);
        $lines = [];
        foreach ($explain['lines'] as $l) {
            $lines[] = [
                'room_type' => (string) ($l['room_type'] ?? ''),
                'room_count' => (int) ($l['room_count'] ?? 0),
                'capacity_used' => (int) ($l['capacity_used'] ?? 0),
                'product' => (int) ($l['product'] ?? 0),
            ];
        }

        if ($explain['total'] === 0 && $lines === []) {
            return [
                'state' => 'no_valid_rooms',
                'total' => 0,
                'lines' => [],
                'ignored' => $explain['ignored'],
            ];
        }

        return [
            'state' => 'ok',
            'total' => (int) $explain['total'],
            'lines' => $lines,
            'ignored' => $explain['ignored'],
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

    /**
     * Passagers réservés (confirmés + en attente), même périmètre que les stats par statut.
     *
     * @param  array<int>  $tourIds
     * @return array<int, int>
     */
    private function batchPassengersBookedByTour(User $user, array $tourIds): array
    {
        if ($tourIds === []) {
            return [];
        }
        $q = Reservation::query()
            ->whereIn('tour_id', $tourIds)
            ->whereIn('status', [Reservation::STATUS_VALIDEE, Reservation::STATUS_EN_COURS]);
        $this->branchScope->scopeReservations($q, $user);
        $this->branchScope->constrainReservationQueryForPortalUser($q, $user);

        $out = [];
        foreach ((clone $q)
            ->selectRaw('tour_id, COALESCE(SUM(COALESCE(passengers_count, 0)), 0) as total_pax')
            ->groupBy('tour_id')
            ->get() as $row) {
            $out[(int) $row->tour_id] = (int) $row->total_pax;
        }

        return $out;
    }

    private function computeWorkspaceAvailabilityBand(array $placesPayload, int $reserved): string
    {
        if (($placesPayload['state'] ?? '') !== 'ok') {
            return 'unknown';
        }
        $total = (int) ($placesPayload['total'] ?? 0);
        if ($total <= 0) {
            return 'unknown';
        }
        $remaining = max(0, $total - $reserved);
        if ($remaining <= 0) {
            return 'full';
        }
        if (($remaining / $total) <= 0.15) {
            return 'low';
        }

        return 'ok';
    }

    /**
     * Date par défaut pour le formulaire : prochain départ à venir, sinon le plus récent (passé).
     *
     * @param  list<array{id: int, date_iso: string, date_label: string, is_past: bool}>  $travelDates
     */
    private function pickDefaultTravelDateIdForForm(array $travelDates): ?int
    {
        if ($travelDates === []) {
            return null;
        }
        foreach ($travelDates as $td) {
            if (empty($td['is_past']) && ! empty($td['id'])) {
                return (int) $td['id'];
            }
        }
        $last = $travelDates[count($travelDates) - 1];

        return ! empty($last['id']) ? (int) $last['id'] : null;
    }

    /**
     * Données pour préremplissage du formulaire workspace (mêmes sources que le catalogue / modal).
     *
     * @param  array<string, mixed>  $modalDetail
     * @return array<string, mixed>
     */
    private function buildPackageFormPrefill(string $rowCode, array $modalDetail, ?float $wpAdultAmount, ?float $wpChildAmount): array
    {
        $tds = $modalDetail['travel_dates'] ?? [];
        $defaultTdId = $this->pickDefaultTravelDateIdForForm($tds);
        $prices = $modalDetail['prices'] ?? [];

        return [
            'code' => $rowCode,
            'kind' => 'package',
            'title' => $modalDetail['title'] ?? '',
            'wp_post_id' => $modalDetail['wp_post_id'] ?? null,
            'laravel_voyage_id' => $modalDetail['laravel_voyage_id'] ?? null,
            'post_status_label' => $modalDetail['post_status_label'] ?? null,
            'destination' => $modalDetail['destination'] ?? null,
            'duration' => $modalDetail['duration'] ?? null,
            'travel_dates' => $tds,
            'default_travel_date_id' => $defaultTdId,
            'prices' => [
                'adult_label' => $prices['adult_label'] ?? null,
                'child_label' => $prices['child_label'] ?? null,
                'currency' => $prices['currency'] ?? 'MAD',
                'adult_amount' => $wpAdultAmount,
                'child_amount' => $wpChildAmount,
                'pricing_mode' => 'workspace_catalog',
            ],
            'places' => $modalDetail['places'] ?? [],
            'rooms' => $modalDetail['rooms'] ?? [],
            'stats' => $modalDetail['stats'] ?? [],
            'form' => $modalDetail['form'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $modalDetail
     * @return array<string, mixed>
     */
    private function buildExtraFormPrefill(string $rowCode, string $kind, array $modalDetail): array
    {
        $dep = $modalDetail['departure_date'] ?? null;
        $tdId = $modalDetail['form']['travel_date_id'] ?? null;
        $travelDates = [];
        if ($dep) {
            $d = Carbon::parse($dep);
            $travelDates[] = [
                'id' => $tdId,
                'date_iso' => $d->format('Y-m-d'),
                'date_label' => $d->locale('fr')->translatedFormat('d MMM yyyy'),
                'is_past' => $d->lt(Carbon::today()),
            ];
        }

        return [
            'code' => $rowCode,
            'kind' => $kind,
            'title' => $modalDetail['title'] ?? '',
            'wp_post_id' => $modalDetail['wp_post_id'] ?? null,
            'laravel_voyage_id' => $modalDetail['laravel_voyage_id'] ?? null,
            'post_status_label' => null,
            'destination' => null,
            'duration' => null,
            'travel_dates' => $travelDates,
            'default_travel_date_id' => $tdId ? (int) $tdId : null,
            'prices' => [
                'adult_label' => null,
                'child_label' => null,
                'currency' => 'MAD',
                'adult_amount' => null,
                'child_amount' => null,
                'pricing_mode' => 'manual',
            ],
            'places' => ['state' => 'na', 'total' => null, 'reserved' => null, 'remaining' => null],
            'rooms' => [],
            'stats' => $modalDetail['stats'] ?? [],
            'form' => $modalDetail['form'] ?? [],
        ];
    }

    private function labelWpPostStatus(?string $s): string
    {
        return match ($s) {
            'publish' => 'Publié',
            'draft' => 'Brouillon',
            'pending' => 'En attente de validation',
            'private' => 'Privé',
            'future' => 'Planifié',
            'trash' => 'Corbeille',
            default => $s ?: '—',
        };
    }

    private function formatChildPriceLabel(mixed $raw): ?string
    {
        $f = $this->parseWpAdultPriceToFloat($raw);
        if ($f === null || $f <= 0) {
            return null;
        }

        return number_format($f, 0, ',', ' ').' MAD';
    }

    private function resolveDurationLabel(?Voyage $voyage, mixed $durationMetaRaw): ?string
    {
        if ($voyage && trim((string) ($voyage->duration_text ?? '')) !== '') {
            return trim((string) $voyage->duration_text);
        }
        $f = $this->parseWpAdultPriceToFloat($durationMetaRaw);
        if ($f !== null && $f > 0) {
            $n = (int) round($f);

            return $n.' jour'.($n > 1 ? 's' : '');
        }
        $s = trim((string) ($durationMetaRaw ?? ''));

        return $s !== '' ? $s : null;
    }

    /**
     * @param  Collection<int, TravelDate>  $tdColl
     * @return array<string, mixed>
     */
    private function buildPackageModalDetail(
        WpPost $wp,
        ?Voyage $voyage,
        int $wpId,
        string $title,
        Collection $tdColl,
        Carbon $today,
        array $placesPayload,
        array $stats,
        int $passengersReserved,
        mixed $childPriceMetaRaw,
        mixed $durationMetaRaw,
        ?string $priceLabel,
        ?string $destination,
        ?int $preferredTravelDateId,
    ): array {
        $laravelId = $voyage ? (int) $voyage->id : null;
        $currency = $voyage && trim((string) ($voyage->currency ?? '')) !== ''
            ? trim((string) $voyage->currency)
            : 'MAD';

        $travelDates = [];
        foreach ($tdColl->filter(fn ($d) => $d instanceof TravelDate && $d->date)->sortBy(fn (TravelDate $d) => $d->date->timestamp) as $td) {
            $travelDates[] = [
                'id' => $td->id,
                'date_iso' => $td->date->format('Y-m-d'),
                'date_label' => $td->date->locale('fr')->translatedFormat('d MMM yyyy'),
                'is_past' => $td->date->lt($today),
            ];
        }

        $placesTotal = ($placesPayload['state'] ?? '') === 'ok' ? (int) ($placesPayload['total'] ?? 0) : null;
        $remaining = $placesTotal !== null ? max(0, $placesTotal - $passengersReserved) : null;
        $pct = ($placesTotal !== null && $placesTotal > 0)
            ? min(100, (int) round(($passengersReserved / $placesTotal) * 100))
            : null;

        $band = $this->computeWorkspaceAvailabilityBand($placesPayload, $passengersReserved);

        return [
            'kind' => 'package',
            'title' => $title,
            'wp_post_id' => $wpId,
            'laravel_voyage_id' => $laravelId,
            'post_status' => $wp->post_status,
            'post_status_label' => $this->labelWpPostStatus($wp->post_status),
            'destination' => $destination,
            'duration' => $this->resolveDurationLabel($voyage, $durationMetaRaw),
            'travel_dates' => $travelDates,
            'places' => [
                'state' => $placesPayload['state'],
                'total' => $placesTotal,
                'reserved' => $passengersReserved,
                'remaining' => $remaining,
                'fill_pct' => $pct,
            ],
            'rooms' => $placesPayload['lines'] ?? [],
            'prices' => [
                'adult_label' => $priceLabel,
                'child_label' => $this->formatChildPriceLabel($childPriceMetaRaw),
                'currency' => $currency,
            ],
            'stats' => $stats,
            'stats_total' => ($stats['validee'] ?? 0) + ($stats['en_cours'] ?? 0) + ($stats['annulee'] ?? 0),
            'availability_band' => $band,
            'laravel_synced' => $voyage !== null,
            'prestation_type' => 'package',
            'form' => [
                'tour_id' => $laravelId,
                'travel_date_id' => $preferredTravelDateId,
                'prestation_type' => 'package',
                'label' => $title.' (#'.$wpId.')',
            ],
            'routes' => [
                'reservations' => $laravelId ? route('admin.reservations.index', ['voyage_id' => $laravelId]) : null,
                'create' => $laravelId ? route('admin.reservations.create', array_filter([
                    'tour_id' => $laravelId,
                    'travel_date_id' => $preferredTravelDateId,
                ], fn ($v) => $v !== null && $v !== '')) : null,
                'edit_voyage' => route('admin.circuits.voyages.edit', $wpId),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildExtraModalDetail(
        string $kind,
        string $title,
        int $tourId,
        ?int $wpPostId,
        array $stats,
        $departureDate,
        ?int $travelDateId,
    ): array {
        $pt = $kind === 'vol' ? 'vol' : 'hebergement';

        return [
            'kind' => $kind,
            'title' => $title,
            'laravel_voyage_id' => $tourId,
            'wp_post_id' => $wpPostId,
            'stats' => $stats,
            'stats_total' => ($stats['validee'] ?? 0) + ($stats['en_cours'] ?? 0) + ($stats['annulee'] ?? 0),
            'departure_date' => $departureDate ? Carbon::parse($departureDate)->format('Y-m-d') : null,
            'prestation_type' => $pt,
            'laravel_synced' => true,
            'form' => [
                'tour_id' => $tourId,
                'travel_date_id' => $travelDateId,
                'prestation_type' => $pt,
                'label' => $title,
            ],
            'routes' => [
                'reservations' => route('admin.reservations.index', ['voyage_id' => $tourId]),
                'create' => route('admin.reservations.create', array_filter([
                    'tour_id' => $tourId,
                    'travel_date_id' => $travelDateId,
                ], fn ($v) => $v !== null && $v !== '')),
                'edit_voyage' => $wpPostId ? route('admin.circuits.voyages.edit', $wpPostId) : null,
            ],
        ];
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
