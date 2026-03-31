<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\Reservation;
use App\Models\TourHotel;
use App\Models\TravelDate;
use App\Models\User;
use App\Models\Voyage;
use App\Models\VoyageExtra;
use App\Models\VoyageFlight;
use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use App\Support\TourPlacesCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Catalogue workspace : packages WordPress, packages Laravel sans wp_post_id (code LVL-*), vols, hébergements.
 * Stats / passagers : {@see ReservationLinkResolver::workspaceStatsTourIdUniverse} + agrégation par ids voyage étendus.
 * Vols : tous les {@see VoyageFlight}. Dates {@see TravelDate} : travel_id = wp_post_id ou id fiche Laravel si pas de WP.
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

        $wpTours = collect();
        try {
            $wpTours = AdminWpTourCatalogQuery::allToursOrdered();
        } catch (\Throwable $e) {
            Log::warning('ReservationWorkspaceCatalog: WP indisponible — poursuite avec voyages Laravel / vols uniquement', ['error' => $e->getMessage()]);
            $meta['wp_connection_failed'] = true;
            $wpTours = collect();
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
        $statsTourUniverse = ReservationLinkResolver::workspaceStatsTourIdUniverse($voyagesByWp);
        $statsByTour = $this->batchReservationStatsByTour($user, $statsTourUniverse);
        $passengersByTour = $this->batchPassengersBookedByTour($user, $statsTourUniverse);

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

        $this->pushLaravelNativePackageRows($rows, $today, $statsByTour, $passengersByTour);

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
     * Retrouve la ligne catalogue pour un voyage Laravel + type de prestation (même agrégat que la page workspace).
     *
     * @return array<string, mixed>|null
     */
    public function findCatalogRowForBooking(Voyage $voyage, string $prestationType, User $user): ?array
    {
        $tid = (int) $voyage->id;
        $type = match ($prestationType) {
            'vol' => 'vol',
            'hebergement' => 'hebergement',
            default => 'package',
        };

        // Package « LVL-* » : ligne construite sans dépendre du catalogue WordPress ni de buildRows() complet.
        if ($type === 'package') {
            $fresh = Voyage::query()->find($tid);
            if ($fresh) {
                $nativeRow = $this->buildLaravelNativePackageRowForVoyage($fresh, $user);
                if ($nativeRow !== null) {
                    return $nativeRow;
                }
            }
        }

        $built = $this->buildRows($user);
        $match = $built['rows']->first(function ($r) use ($tid, $type) {
            return (int) ($r['voyage_id'] ?? 0) === $tid && ($r['type'] ?? '') === $type;
        });
        if ($match !== null) {
            return $match;
        }
        if ($type !== 'package') {
            return $built['rows']->first(function ($r) use ($tid) {
                return (int) ($r['voyage_id'] ?? 0) === $tid && ($r['type'] ?? '') === 'package';
            });
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVoyageReservationDataPayload(Voyage $voyage, string $prestationType, User $user, ?int $travelDateId = null): ?array
    {
        $row = $this->findCatalogRowForBooking($voyage, $prestationType, $user);
        if ($row === null) {
            return null;
        }

        $formPrefill = $row['form_prefill'] ?? null;
        if (is_array($formPrefill) && $travelDateId !== null && $travelDateId > 0) {
            $formPrefill = $this->refineFormPrefillPlacesForTravelDate($formPrefill, $voyage, $travelDateId, $user);
        }

        return [
            'voyage' => [
                'id' => (int) $voyage->id,
                'name' => $voyage->name,
                'wp_post_id' => $voyage->wp_post_id ? (int) $voyage->wp_post_id : null,
                'destination' => $voyage->destination,
            ],
            'catalog_code' => $row['code'] ?? null,
            'catalog_type' => $row['type'] ?? null,
            'form_prefill' => $formPrefill,
            'modal_detail' => $row['modal_detail'] ?? null,
        ];
    }

    /**
     * Recalcule places réservées / restantes pour une date de départ (même logique que {@see ReservationWorkspaceBookingService::resolveRemainingSeats}).
     *
     * @param  array<string, mixed>  $prefill
     * @return array<string, mixed>
     */
    private function refineFormPrefillPlacesForTravelDate(array $prefill, Voyage $voyage, int $travelDateId, User $user): array
    {
        if (! $voyage->wp_post_id) {
            return $prefill;
        }
        $td = TravelDate::query()->find($travelDateId);
        if (! $td || (int) $td->travel_id !== (int) $voyage->wp_post_id) {
            return $prefill;
        }

        $places = $prefill['places'] ?? [];
        $state = $places['state'] ?? '';
        if ($state !== 'ok') {
            return $prefill;
        }
        $total = isset($places['total']) ? (int) $places['total'] : null;
        if ($total === null || $total <= 0) {
            return $prefill;
        }

        $q = Reservation::query()
            ->whereIn('tour_id', Voyage::allIdsSharingWpTour((int) $voyage->id))
            ->whereIn('status', [Reservation::STATUS_EN_COURS, Reservation::STATUS_VALIDEE])
            ->where('travel_date_id', $travelDateId);
        $this->branchScope->scopeReservations($q, $user);
        $booked = (int) (clone $q)->sum('passengers_count');

        $remaining = max(0, $total - $booked);
        $pct = $total > 0 ? min(100, (int) round(($booked / $total) * 100)) : null;

        $placesPayload = ['state' => 'ok', 'total' => $total];
        $band = $this->computeWorkspaceAvailabilityBand($placesPayload, $booked);

        $tds = $prefill['travel_dates'] ?? [];
        $hasPastOnly = $tds !== [] && collect($tds)->every(fn ($d) => ! empty($d['is_past']));

        $prefill['places'] = array_merge($places, [
            'reserved' => $booked,
            'remaining' => $remaining,
            'fill_pct' => $pct,
        ]);
        $prefill['availability'] = array_merge(
            $this->availabilityUiFromBand($band, $hasPastOnly),
            ['band' => $band]
        );

        return $prefill;
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

        $physicalToCanonical = [];
        $allPhysical = [];
        foreach ($tourIds as $tid) {
            $tid = (int) $tid;
            foreach (Voyage::allIdsSharingWpTour($tid) as $pid) {
                $pid = (int) $pid;
                $allPhysical[$pid] = true;
                $physicalToCanonical[$pid] = $tid;
            }
        }
        $allPhysicalIds = array_keys($allPhysical);

        $q = Reservation::query()->whereIn('tour_id', $allPhysicalIds);
        $this->branchScope->scopeReservations($q, $user);

        $aggregates = (clone $q)
            ->selectRaw('tour_id, status, COUNT(*) as aggregate')
            ->groupBy('tour_id', 'status')
            ->get();

        foreach ($aggregates as $row) {
            $physicalTid = (int) $row->tour_id;
            $canonical = $physicalToCanonical[$physicalTid] ?? null;
            if ($canonical === null || ! isset($base[$canonical])) {
                continue;
            }
            $n = (int) $row->aggregate;
            match ($row->status) {
                Reservation::STATUS_VALIDEE => $base[$canonical]['validee'] += $n,
                Reservation::STATUS_EN_COURS => $base[$canonical]['en_cours'] += $n,
                Reservation::STATUS_ANNULEE => $base[$canonical]['annulee'] += $n,
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

        $physicalToCanonical = [];
        $allPhysical = [];
        foreach ($tourIds as $tid) {
            $tid = (int) $tid;
            foreach (Voyage::allIdsSharingWpTour($tid) as $pid) {
                $pid = (int) $pid;
                $allPhysical[$pid] = true;
                $physicalToCanonical[$pid] = $tid;
            }
        }
        $allPhysicalIds = array_keys($allPhysical);

        $q = Reservation::query()
            ->whereIn('tour_id', $allPhysicalIds)
            ->whereIn('status', [Reservation::STATUS_VALIDEE, Reservation::STATUS_EN_COURS]);
        $this->branchScope->scopeReservations($q, $user);

        $out = array_fill_keys(array_map('intval', $tourIds), 0);
        foreach ((clone $q)
            ->selectRaw('tour_id, COALESCE(SUM(COALESCE(passengers_count, 0)), 0) as total_pax')
            ->groupBy('tour_id')
            ->get() as $row) {
            $physical = (int) $row->tour_id;
            $canonical = $physicalToCanonical[$physical] ?? null;
            if ($canonical === null || ! array_key_exists($canonical, $out)) {
                continue;
            }
            $out[$canonical] += (int) $row->total_pax;
        }

        return $out;
    }

    /**
     * Libellé date long français (ex. 01 avril 2026) — évite les artefacts ICU (MMM / mois dupliqués).
     */
    private function formatFrenchLongDate(Carbon $date): string
    {
        return $date->copy()->locale('fr')->translatedFormat('d F Y');
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    private function enrichRoomLinesForDisplay(array $lines): array
    {
        $out = [];
        foreach ($lines as $line) {
            $rc = (int) ($line['room_count'] ?? 0);
            $cu = (int) ($line['capacity_used'] ?? 0);
            $pr = (int) ($line['product'] ?? 0);
            $rt = (string) ($line['room_type'] ?? '');
            $detail = ($rc > 0 && $cu > 0)
                ? sprintf('%d ch. %s × %d pl. = %d places', $rc, $rt, $cu, $pr)
                : ($rt !== '' ? $rt.' · '.$pr.' pl.' : '—');
            $out[] = array_merge($line, ['detail_label' => $detail]);
        }

        return $out;
    }

    /**
     * Extras proposés dans le formulaire workspace (alignés sur les grilles historiques ; pilotés par le catalogue).
     *
     * @return list<array{id: string, name: string, desc: string, price_adult: float, price_child: float, icon: string}>
     */
    private function defaultWorkspaceExtrasCatalog(string $kind): array
    {
        return match ($kind) {
            'vol' => [
                ['id' => 'ext4', 'name' => 'Bagage soute 23kg', 'desc' => 'Ancillary', 'price_adult' => 450, 'price_child' => 450, 'icon' => 'fa-suitcase'],
                ['id' => 'ext5', 'name' => 'Siège', 'desc' => 'SSR', 'price_adult' => 100, 'price_child' => 50, 'icon' => 'fa-chair'],
                ['id' => 'ext6', 'name' => 'Repas bord', 'desc' => 'Halal / végétarien', 'price_adult' => 150, 'price_child' => 100, 'icon' => 'fa-hamburger'],
            ],
            'hebergement' => [
                ['id' => 'ext7', 'name' => 'Vue mer', 'desc' => 'Supplément', 'price_adult' => 200, 'price_child' => 200, 'icon' => 'fa-water'],
                ['id' => 'ext8', 'name' => 'Transfert aéroport', 'desc' => 'A/R', 'price_adult' => 300, 'price_child' => 150, 'icon' => 'fa-taxi'],
                ['id' => 'ext9', 'name' => 'Spa', 'desc' => '45 min', 'price_adult' => 400, 'price_child' => 0, 'icon' => 'fa-spa'],
            ],
            default => [
                ['id' => 'ext1', 'name' => 'Visite historique', 'desc' => 'Guide', 'price_adult' => 150, 'price_child' => 100, 'icon' => 'fa-map-marked-alt'],
                ['id' => 'ext2', 'name' => 'Assurance multirisque', 'desc' => 'Annulation & santé', 'price_adult' => 350, 'price_child' => 200, 'icon' => 'fa-shield-alt'],
                ['id' => 'ext3', 'name' => 'Demi-pension', 'desc' => 'PD + dîner', 'price_adult' => 1200, 'price_child' => 600, 'icon' => 'fa-utensils'],
            ],
        };
    }

    /**
     * Extras workspace : {@see VoyageExtra} si le voyage Laravel existe et a des lignes actives, sinon grille historique (sans voyage Laravel).
     *
     * @return list<array<string, mixed>>
     */
    private function resolveExtrasCatalogForVoyage(?int $laravelVoyageId, string $kindFallback): array
    {
        if ($laravelVoyageId === null || $laravelVoyageId <= 0) {
            return $this->defaultWorkspaceExtrasCatalog($kindFallback);
        }
        $rows = VoyageExtra::query()
            ->where('voyage_id', $laravelVoyageId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        if ($rows->isEmpty()) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => 've_'.$r->id,
                'name' => $r->name,
                'desc' => (string) ($r->description ?? ''),
                'price_adult' => (float) $r->price_adult,
                'price_child' => (float) $r->price_child,
                'icon' => $r->icon ?: 'fa-plus-circle',
                'extra_type' => $r->extra_type,
            ];
        }

        return $out;
    }

    private function availabilityUiFromBand(string $band, bool $hasPastOnlyDates): array
    {
        if ($hasPastOnlyDates) {
            return ['key' => 'past', 'label' => 'Départs passés', 'tone' => 'amber'];
        }

        return match ($band) {
            'full' => ['key' => 'full', 'label' => 'Complet', 'tone' => 'red'],
            'low' => ['key' => 'low', 'label' => 'Peu de places', 'tone' => 'orange'],
            'ok' => ['key' => 'ok', 'label' => 'Disponible', 'tone' => 'emerald'],
            default => ['key' => 'unknown', 'label' => 'Capacité N/A', 'tone' => 'slate'],
        };
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
        $band = (string) ($modalDetail['availability_band'] ?? 'unknown');
        $hasPastOnly = $tds !== [] && collect($tds)->every(fn ($d) => ! empty($d['is_past']));

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
            'rooms' => $this->enrichRoomLinesForDisplay($modalDetail['rooms'] ?? []),
            'availability' => array_merge(
                $this->availabilityUiFromBand($band, $hasPastOnly),
                ['band' => $band]
            ),
            'extras_catalog' => $this->resolveExtrasCatalogForVoyage(
                isset($modalDetail['laravel_voyage_id']) ? (int) $modalDetail['laravel_voyage_id'] : null,
                'package'
            ),
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
                'date_label' => $this->formatFrenchLongDate($d),
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
            'availability' => array_merge(
                $this->availabilityUiFromBand('unknown', false),
                ['band' => 'na']
            ),
            'extras_catalog' => $this->resolveExtrasCatalogForVoyage(
                isset($modalDetail['laravel_voyage_id']) ? (int) $modalDetail['laravel_voyage_id'] : null,
                $kind === 'vol' ? 'vol' : 'hebergement'
            ),
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
                'date_label' => $this->formatFrenchLongDate($td->date),
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
                'reservations' => $laravelId ? route('admin.reservations.index', array_filter([
                    'voyage_id' => $laravelId,
                    'travel_date_id' => $preferredTravelDateId,
                ], fn ($v) => $v !== null && $v !== '')) : null,
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
                'reservations' => route('admin.reservations.index', array_filter([
                    'voyage_id' => $tourId,
                    'travel_date_id' => $travelDateId,
                ], fn ($v) => $v !== null && $v !== '')),
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
        if ($date === null) {
            return null;
        }
        $travelKey = $voyage->wp_post_id ? (int) $voyage->wp_post_id : (int) $voyage->id;
        if ($travelKey <= 0) {
            return null;
        }
        $d = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return TravelDate::query()
            ->where('travel_id', $travelKey)
            ->whereDate('date', $d)
            ->where('is_active', true)
            ->value('id');
    }

    /**
     * Packages pour fiches {@see Voyage} sans wp_post_id (réservables hors catalogue WordPress).
     *
     * @param  array<int, array{validee: int, en_cours: int, annulee: int}>  $statsByTour
     * @param  array<int, int>  $passengersByTour
     */
    /**
     * Une ligne catalogue package pour une fiche {@see Voyage} sans wp_post_id (store / API sans rebuild WP).
     *
     * @return array<string, mixed>|null
     */
    public function buildLaravelNativePackageRowForVoyage(Voyage $voyage, User $user): ?array
    {
        if ((int) ($voyage->wp_post_id ?? 0) !== 0) {
            return null;
        }
        $today = Carbon::today();
        $universe = ReservationLinkResolver::workspaceStatsTourIdUniverse(collect([$voyage]));
        $statsByTour = $this->batchReservationStatsByTour($user, $universe);
        $passengersByTour = $this->batchPassengersBookedByTour($user, $universe);

        return $this->composeLaravelNativePackageRow($voyage, $statsByTour, $passengersByTour, $today);
    }

    /**
     * @param  array<int, array{validee: int, en_cours: int, annulee: int}>  $statsByTour
     * @param  array<int, int>  $passengersByTour
     * @return array<string, mixed>|null
     */
    private function composeLaravelNativePackageRow(
        Voyage $voyage,
        array $statsByTour,
        array $passengersByTour,
        Carbon $today,
    ): ?array {
        $tid = (int) $voyage->id;
        if ((int) ($voyage->wp_post_id ?? 0) !== 0) {
            return null;
        }
        if (VoyageFlight::query()->where('voyage_id', $tid)->exists()) {
            return null;
        }

        $rowStats = $statsByTour[$tid] ?? $this->emptyStats();
        $paxBooked = (int) ($passengersByTour[$tid] ?? 0);
        $packageModalDetail = $this->buildLaravelNativePackageModalPayload($voyage, $rowStats, $paxBooked, $today);
        $travelDateId = $packageModalDetail['form']['travel_date_id'] ?? null;
        $travelDatesList = $packageModalDetail['travel_dates'] ?? [];
        $hasFuture = $travelDatesList !== [] && collect($travelDatesList)->contains(fn ($d) => empty($d['is_past']));

        return [
            'type' => 'package',
            'code' => 'LVL-'.$tid,
            'name' => $voyage->name ?: 'Voyage #'.$tid,
            'subtitle' => 'Fiche Laravel (sans tour WordPress)',
            'voyage_id' => $tid,
            'wp_post_id' => null,
            'laravel_synced' => true,
            'departure_id' => null,
            'flight_id' => null,
            'tour_hotel_wp_id' => null,
            'travel_date_id' => $travelDateId,
            'departure_date' => null,
            'departure_is_past' => false,
            'departure_is_canceled' => false,
            'price_label' => $this->formatVoyagePriceLabel($voyage),
            'voyage_destination' => $voyage->destination && trim((string) $voyage->destination) !== ''
                ? trim((string) $voyage->destination)
                : null,
            'stats' => $rowStats,
            'places_state' => 'na',
            'places_total' => null,
            'places_lines' => [],
            'places_ignored' => [],
            'ws_avail' => $hasFuture ? 'ok' : 'na',
            'ws_has_future' => $hasFuture,
            'modal_detail' => $packageModalDetail,
            'form_prefill' => $this->buildPackageFormPrefill(
                'LVL-'.$tid,
                $packageModalDetail,
                $voyage->price_from !== null ? (float) $voyage->price_from : null,
                null
            ),
        ];
    }

    private function pushLaravelNativePackageRows(
        Collection $rows,
        Carbon $today,
        array $statsByTour,
        array $passengersByTour,
    ): void {
        foreach (
            Voyage::query()
                ->whereNull('wp_post_id')
                ->orderBy('name')
                ->limit(200)
                ->get() as $voyage
        ) {
            $tid = (int) $voyage->id;
            if ($rows->contains(fn ($r) => ($r['type'] ?? '') === 'package' && (int) ($r['voyage_id'] ?? 0) === $tid)) {
                continue;
            }
            $rec = $this->composeLaravelNativePackageRow($voyage, $statsByTour, $passengersByTour, $today);
            if ($rec !== null) {
                $rows->push($rec);
            }
        }
    }

    /**
     * @param  array{validee: int, en_cours: int, annulee: int}  $stats
     * @return array<string, mixed>
     */
    private function buildLaravelNativePackageModalPayload(Voyage $voyage, array $stats, int $paxBooked, Carbon $today): array
    {
        $tid = (int) $voyage->id;
        $tdColl = TravelDate::query()
            ->where('travel_id', $tid)
            ->where('is_active', true)
            ->orderBy('date')
            ->get();

        $pickTd = $this->pickTravelDateForPackageDisplay($tdColl, $today);
        $pickedTd = $pickTd['travelDate'];
        $preferredTravelDateId = $pickedTd?->id;

        $travelDates = [];
        foreach ($tdColl->filter(fn ($d) => $d instanceof TravelDate && $d->date) as $td) {
            $travelDates[] = [
                'id' => $td->id,
                'date_iso' => $td->date->format('Y-m-d'),
                'date_label' => $this->formatFrenchLongDate($td->date),
                'is_past' => $td->date->lt($today),
            ];
        }

        $currency = trim((string) ($voyage->currency ?? '')) !== ''
            ? trim((string) $voyage->currency)
            : 'MAD';
        $priceLabel = $this->formatVoyagePriceLabel($voyage);
        $band = $this->computeWorkspaceAvailabilityBand(['state' => 'na', 'total' => null], $paxBooked);

        return [
            'kind' => 'package',
            'title' => $voyage->name ?: 'Voyage #'.$tid,
            'wp_post_id' => null,
            'laravel_voyage_id' => $tid,
            'post_status' => null,
            'post_status_label' => 'Laravel',
            'destination' => $voyage->destination && trim((string) $voyage->destination) !== ''
                ? trim((string) $voyage->destination)
                : null,
            'duration' => $this->resolveDurationLabel($voyage, null),
            'travel_dates' => $travelDates,
            'places' => [
                'state' => 'na',
                'total' => null,
                'reserved' => $paxBooked,
                'remaining' => null,
                'fill_pct' => null,
            ],
            'rooms' => [],
            'prices' => [
                'adult_label' => $priceLabel,
                'child_label' => null,
                'currency' => $currency,
            ],
            'stats' => $stats,
            'stats_total' => ($stats['validee'] ?? 0) + ($stats['en_cours'] ?? 0) + ($stats['annulee'] ?? 0),
            'availability_band' => $band,
            'laravel_synced' => true,
            'prestation_type' => 'package',
            'form' => [
                'tour_id' => $tid,
                'travel_date_id' => $preferredTravelDateId,
                'prestation_type' => 'package',
                'label' => ($voyage->name ?: 'Voyage').' · Laravel',
            ],
            'routes' => [
                'reservations' => route('admin.reservations.index', array_filter([
                    'voyage_id' => $tid,
                    'travel_date_id' => $preferredTravelDateId,
                ], fn ($v) => $v !== null && $v !== '')),
                'create' => route('admin.reservations.create', array_filter([
                    'tour_id' => $tid,
                    'travel_date_id' => $preferredTravelDateId,
                ], fn ($v) => $v !== null && $v !== '')),
                'edit_voyage' => null,
            ],
        ];
    }
}
