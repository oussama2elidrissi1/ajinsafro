<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\Reservation;
use App\Models\TourHotel;
use App\Models\TravelDate;
use App\Models\TravelDayItem;
use App\Models\User;
use App\Models\Voyage;
use App\Models\VoyageExtra;
use App\Models\VoyageFlight;
use App\Models\Wp\Activity;
use App\Models\Wp\TourDayActivity;
use App\Models\Wp\WpPost;
use App\Models\Wp\WpPostMeta;
use App\Services\Wp\WpHeroImageService;
use App\Support\TourPlacesCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Catalogue workspace : packages WordPress, packages Laravel sans wp_post_id (code LVL-*), vols, h+�bergements.
 * Stats / passagers : {@see ReservationLinkResolver::workspaceStatsTourIdUniverse} + agr+�gation par ids voyage +�tendus.
 * Vols : tous les {@see VoyageFlight}. Dates {@see TravelDate} : travel_id = wp_post_id ou id fiche Laravel si pas de WP.
 */
class ReservationWorkspaceCatalogService
{
    public function __construct(
        protected BranchScopeService $branchScope,
        protected DepartureInventoryService $departureInventory,
        protected \App\Services\Wp\WpTourRepository $wpTourRepo,
    ) {}

    /**
     * Résout la destination réelle d’un voyage en priorisant :
     * 1) meta WordPress `address` (source de vérité fiche produit)
     * 2) taxonomies locations WordPress (`multi_location`)
     * 3) champ Laravel `voyage->destination` (fallback legacy)
     * 4) null si rien trouvé
     */
    public function resolveVoyageDestination(?Voyage $voyage, ?WpPost $wp = null, ?int $wpPostId = null): ?string
    {
        $wpId = $wpPostId ?? ($voyage?->wp_post_id ? (int) $voyage->wp_post_id : null);
        if (! $wpId) {
            $laravelDest = $voyage && trim((string) $voyage->destination) !== '' ? trim((string) $voyage->destination) : null;
            Log::info('Voyage destination debug', [
                'voyage_id' => $voyage?->id,
                'wp_post_id' => $wpId,
                'source' => 'laravel_fallback_no_wp_id',
                'destination' => $laravelDest,
            ]);
            return $laravelDest;
        }

        // 1) Meta WordPress `address`
        $address = null;
        try {
            if ($wp) {
                $address = $wp->getMeta('address');
            } else {
                $metaRow = WpPostMeta::query()
                    ->where('post_id', $wpId)
                    ->where('meta_key', 'address')
                    ->orderBy('meta_id')
                    ->first();
                $address = $metaRow?->meta_value;
            }
        } catch (\Throwable $e) {
            Log::warning('resolveVoyageDestination: failed reading address meta', ['wp_post_id' => $wpId, 'error' => $e->getMessage()]);
        }
        if (is_string($address) && trim($address) !== '') {
            $cleaned = trim(preg_split('/[,;|]/', $address)[0] ?? $address);
            if ($cleaned !== '') {
                Log::info('Voyage destination debug', [
                    'voyage_id' => $voyage?->id,
                    'wp_post_id' => $wpId,
                    'source' => 'wp_address_meta',
                    'destination' => $cleaned,
                ]);
                return $cleaned;
            }
        }

        // 2) Taxonomies locations WordPress (multi_location)
        try {
            $multiLocation = null;
            if ($wp) {
                $multiLocation = $wp->getMeta('multi_location');
            } else {
                $metaRow = WpPostMeta::query()
                    ->where('post_id', $wpId)
                    ->where('meta_key', 'multi_location')
                    ->orderBy('meta_id')
                    ->first();
                $multiLocation = $metaRow?->meta_value;
            }
            $locNames = $this->wpTourRepo->getLocationNamesFromMultiLocation($multiLocation);
            if ($locNames !== '') {
                Log::info('Voyage destination debug', [
                    'voyage_id' => $voyage?->id,
                    'wp_post_id' => $wpId,
                    'source' => 'wp_multi_location',
                    'destination' => $locNames,
                ]);
                return $locNames;
            }
        } catch (\Throwable $e) {
            Log::warning('resolveVoyageDestination: failed reading locations', ['wp_post_id' => $wpId, 'error' => $e->getMessage()]);
        }

        // 3) Fallback Laravel destination
        $laravelDest = $voyage && trim((string) $voyage->destination) !== '' ? trim((string) $voyage->destination) : null;
        Log::info('Voyage destination debug', [
            'voyage_id' => $voyage?->id,
            'wp_post_id' => $wpId,
            'source' => 'laravel_fallback',
            'destination' => $laravelDest,
        ]);
        if ($laravelDest) {
            return $laravelDest;
        }

        return null;
    }

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
            Log::warning('ReservationWorkspaceCatalog: WP indisponible ��� poursuite avec voyages Laravel / vols uniquement', ['error' => $e->getMessage()]);
            $meta['wp_connection_failed'] = true;
            $wpTours = collect();
        }

        $meta['wp_tour_count'] = $wpTours->count();

        $wpTourIdsOrdered = $wpTours->pluck('ID')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $wpIds = $wpTourIdsOrdered;

        $wpPostTitleById = $wpTours->keyBy(fn (WpPost $p) => (int) $p->ID)
            ->map(fn (WpPost $p) => trim((string) $p->post_title) ?: 'Tour #'.(int) $p->ID);

        /** M+�me cl+� que {@see VoyageController::index} : postmeta meta_key = adult_price */
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
                    'images' => function ($q) {
                        $q->orderBy('sort_order')->orderBy('id');
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
                Log::warning('ReservationWorkspaceCatalog: plusieurs lignes voyages pour le m+�me wp_post_id', [
                    'duplicates' => $laravelDuplicatesByWpPostId,
                    'note' => 'Une seule fiche Laravel est retenue par wp_post_id (priorit+� au plus petit voyages.id).',
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
            if ($tourId && $travelDateId) {
                $departureId = Departure::query()
                    ->where('voyage_id', $tourId)
                    ->where('wp_travel_date_id', $travelDateId)
                    ->value('id');
            }

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
                        ->map(fn (TravelDate $d) => Carbon::parse($d->date)->format('Y-m-d'))
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
            $hasFutureTravelDate = $tdColl->contains(fn (TravelDate $d) => $d->date && ! Carbon::parse($d->date)->lt($today));
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
                    : ('Pas de fiche Laravel ��� renseignez voyages.wp_post_id = '.$wpId.' pour r+�server / stats.'),
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
                'voyage_destination' => $this->resolveVoyageDestination($voyage, $wp, $wpId),
                'image_url' => $this->resolveCatalogRowImageUrl($voyage, $wp),
                'summary' => $this->resolveCatalogRowSummary($voyage, $wp),
                'is_featured' => $voyage && $voyage->is_featured,
                'has_promo' => $this->voyageHasPromoPrice($voyage),
                'stats' => $rowStats,
                'places_state' => $placesPayload['state'],
                'places_total' => $placesPayload['total'],
                'places_lines' => $placesPayload['lines'],
                'places_ignored' => $placesPayload['ignored'],
                'ws_avail' => $wsAvail,
                'ws_has_future' => $hasFutureTravelDate,
                'modal_detail' => ($packageModalDetail = $this->buildPackageModalDetail(
                    $user,
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
                    $adultPriceRaw,
                    $this->resolveVoyageDestination($voyage, $wp, $wpId),
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

        $this->pushLaravelNativePackageRows($rows, $user, $today, $statsByTour, $passengersByTour);

        if (config('app.debug')) {
            $meta['package_price_debug'] = $packagePriceDebug;
            $meta['package_departure_debug'] = $packageDepartureDebug;
            $meta['package_places_debug'] = $packagePlacesDebug;
            $meta['package_departure_source_doc'] = 'Disponibilit+� CRUD ��� request travel_dates ��� VoyageController::syncTravelDates ��� TravelDate (wp.aj_travel_dates)';
            $meta['package_places_source_doc'] = 'TourHotel (wp.aj_tour_hotels.tour_id = wp_post_id) ��� TourHotelRoom (wp.aj_tour_hotel_rooms) ; places = TourPlacesCalculator::explainFromDatabase (identique +�dition voyage).';
            Log::debug('Workspace package prices (align+�s sur VoyageController@index adult_price meta)', [
                'packages' => $packagePriceDebug,
            ]);
            Log::debug('Workspace package d+�parts (Disponibilit+� = aj_travel_dates)', [
                'packages' => $packageDepartureDebug,
            ]);
            Log::debug('Workspace package places (chambres / capacit+�)', [
                'sample' => $packagePlacesDebug,
            ]);
        }

        $meta['package_rows'] = $rows->count();

        $flightQuery = VoyageFlight::query()
            ->with([
                'voyage' => function ($q) {
                    $q->with([
                        'images' => function ($q2) {
                            $q2->orderBy('sort_order')->orderBy('id');
                        },
                    ]);
                },
            ])
            ->orderBy('departure_date');

        foreach ($flightQuery->get() as $flight) {
            $voyage = $flight->voyage;
            if (! $voyage) {
                continue;
            }
            $tourId = (int) $voyage->id;
            $label = trim(($flight->flight_number ?: 'Vol').' -� '.$flight->from_label.' ��� '.$flight->to_label);
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
            $volSummary = collect([trim((string) ($flight->from_label ?? '')), trim((string) ($flight->to_label ?? ''))])
                ->filter()
                ->implode(' ��� ');
            $rows->push([
                'type' => 'vol',
                'code' => 'VOL-'.$flight->id,
                'name' => $label,
                'subtitle' => $wpPid ? ($wpPostTitleById->get($wpPid) ?: $voyage->name) : $voyage->name,
                'image_url' => $this->resolveCatalogRowImageUrl($voyage, null),
                'summary' => $volSummary,
                'is_featured' => false,
                'has_promo' => $this->voyageHasPromoPrice($voyage),
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
                $hotTitle = ($hotel->hotel_name ?: 'H+�bergement').' ��� '.$tourWpTitle;
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
                $hotelImg = (int) ($hotel->image_id ?? 0) > 0
                    ? WpHeroImageService::publicUrlForAttachmentId((int) $hotel->image_id)
                    : null;
                $hotelSummary = trim((string) ($hotel->address ?? ''));
                if ($hotelSummary === '') {
                    $hotelSummary = trim(implode(' -� ', array_filter([
                        (string) ($hotel->meal_plan ?? ''),
                        (string) ($hotel->room_type ?? ''),
                    ])));
                }
                $rows->push([
                    'type' => 'hebergement',
                    'code' => 'HOT-'.$hotel->id,
                    'name' => $hotel->hotel_name ?: 'H+�bergement',
                    'subtitle' => $tourWpTitle,
                    'image_url' => $hotelImg ?: $this->resolveCatalogRowImageUrl($voyage, null),
                    'summary' => Str::limit($hotelSummary, 200),
                    'is_featured' => false,
                    'has_promo' => $this->voyageHasPromoPrice($voyage),
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
     * Filtre optionnel des lignes (param+�tre URL `catalog`).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function scopeCatalogRows(Collection $rows, string $scope): Collection
    {
        $scope = in_array($scope, ['upcoming', 'all', 'past', 'none'], true) ? $scope : 'upcoming';

        return match ($scope) {
            'all' => $rows->values(),
            'past' => $rows->filter(function (array $r) {
                return ! empty($r['departure_date']) && ! empty($r['departure_is_past']);
            })->values(),
            'none' => $rows->filter(function (array $r) {
                return empty($r['departure_date']);
            })->values(),
            default => $rows->filter(function (array $r) {
                if (empty($r['departure_date'])) {
                    return false;
                }
                if (! empty($r['departure_is_past'])) {
                    return false;
                }
                if (! empty($r['departure_is_canceled'])) {
                    return false;
                }

                return true;
            })->values(),
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array{search?: string, type?: string, destination?: string, date_from?: string, date_to?: string, budget_min?: int|null, budget_max?: int|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function filterWorkspaceRows(Collection $rows, array $filters): Collection
    {
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        $type = strtolower(trim((string) ($filters['type'] ?? '')));
        $destination = Str::lower(trim((string) ($filters['destination'] ?? '')));
        $dateFrom = $this->parseWorkspaceFilterDate($filters['date_from'] ?? null, false);
        $dateTo = $this->parseWorkspaceFilterDate($filters['date_to'] ?? null, true);
        $budgetMin = isset($filters['budget_min']) ? max(0, (int) $filters['budget_min']) : null;
        $budgetMax = isset($filters['budget_max']) ? max(0, (int) $filters['budget_max']) : null;

        if ($budgetMin !== null && $budgetMax !== null && $budgetMin > $budgetMax) {
            [$budgetMin, $budgetMax] = [$budgetMax, $budgetMin];
        }

        return $rows->map(function (array $row) use ($search, $type, $destination, $dateFrom, $dateTo, $budgetMin, $budgetMax): ?array {
            if ($type !== '' && $type !== 'all' && (string) ($row['type'] ?? '') !== $type) {
                return null;
            }

            $rowDestination = Str::lower(trim((string) ($row['voyage_destination'] ?? data_get($row, 'modal_detail.destination', ''))));
            if ($destination !== '' && $destination !== 'all' && ($rowDestination === '' || ! str_contains($rowDestination, $destination))) {
                return null;
            }

            if ($search !== '' && ! str_contains($this->workspaceRowSearchBlob($row), $search)) {
                return null;
            }

            $budgetAmount = $this->resolveWorkspaceRowBudgetAmount($row);
            if ($budgetMin !== null && ($budgetAmount === null || $budgetAmount < $budgetMin)) {
                return null;
            }
            if ($budgetMax !== null && ($budgetAmount === null || $budgetAmount > $budgetMax)) {
                return null;
            }

            $row = $this->filterWorkspaceRowDepartures($row, $dateFrom, $dateTo);

            return $row;
        })->filter()->values();
    }

    /**
     * Tri workspace (PHP, catalogue agr+�g+� hors d���une seule requ+�te SQL).
     *
     * Ordre :
     * 1) type : package (circuit) ��� h+�bergement ��� vol ��� autre
     * 2) d+�part : futur / aujourd���hui ��� sans date ��� pass+�
     * 3) departure_date ASC, puis code
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function sortCatalogRowsForWorkspaceDisplay(Collection $rows): Collection
    {
        $today = Carbon::today()->startOfDay();

        return $rows->sort(function (array $a, array $b) use ($today) {
            // 1. Sellable items first
            $sellableA = $a['commercial']['is_sellable'] ?? false;
            $sellableB = $b['commercial']['is_sellable'] ?? false;
            if ($sellableA !== $sellableB) {
                return $sellableA ? -1 : 1;
            }

            // 2. Commercial score DESC
            $scoreA = $a['commercial']['score'] ?? 0;
            $scoreB = $b['commercial']['score'] ?? 0;
            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }

            // 3. Days until departure ASC
            $daysA = $a['commercial']['jours_avant_depart'] ?? null;
            $daysB = $b['commercial']['jours_avant_depart'] ?? null;
            if ($daysA !== null && $daysB !== null && $daysA !== $daysB) {
                return $daysA <=> $daysB;
            }
            if ($daysA !== null && $daysB === null) {
                return -1;
            }
            if ($daysA === null && $daysB !== null) {
                return 1;
            }

            // 4. Remaining seats ASC
            $remainingA = $a['commercial']['places_restantes'] ?? null;
            $remainingB = $b['commercial']['places_restantes'] ?? null;
            if ($remainingA !== null && $remainingB !== null && $remainingA !== $remainingB) {
                return $remainingA <=> $remainingB;
            }
            if ($remainingA !== null && $remainingB === null) {
                return -1;
            }
            if ($remainingA === null && $remainingB !== null) {
                return 1;
            }

            // 5. Sold seats DESC
            $soldA = $a['commercial']['places_vendues'] ?? 0;
            $soldB = $b['commercial']['places_vendues'] ?? 0;
            if ($soldA !== $soldB) {
                return $soldB <=> $soldA;
            }

            // 6. Type tier
            $typeA = $this->workspaceCatalogTypeTier($a);
            $typeB = $this->workspaceCatalogTypeTier($b);
            if ($typeA !== $typeB) {
                return $typeA <=> $typeB;
            }

            $dateA = $this->normalizeCatalogRowDepartureDate($a);
            $dateB = $this->normalizeCatalogRowDepartureDate($b);
            if ($dateA !== null && $dateB !== null) {
                $cmp = $dateA->timestamp <=> $dateB->timestamp;
                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            return strcmp((string) ($a['code'] ?? ''), (string) ($b['code'] ?? ''));
        })->values();
    }

    /**
     * @return int 0 package (voyage/circuit), 1 h+�bergement, 2 vol, 3 autre
     */
    private function workspaceCatalogTypeTier(array $r): int
    {
        return match ((string) ($r['type'] ?? '')) {
            'package' => 0,
            'hebergement' => 1,
            'vol' => 2,
            default => 3,
        };
    }

    /**
     * @return int 0 futur / aujourd���hui, 1 sans date, 2 pass+�
     */
    private function workspaceCatalogSortTier(array $r, Carbon $today): int
    {
        $d = $this->normalizeCatalogRowDepartureDate($r);
        if ($d === null) {
            return 1;
        }

        return $d->gte($today) ? 0 : 2;
    }

    /**
     * Priorit+� commerciale pour le tri workspace.
     *
     * @return int 0 push_urgent, 1 almost_full, 2 high_potential, 3 promote, 4 standard, 5 watch
     */
    private function workspaceCatalogPriorityTier(array $r): int
    {
        $priority = $r['commercial']['priorite_vente'] ?? 'standard';

        return match ($priority) {
            'push_urgent' => 0,
            'almost_full' => 1,
            'high_potential' => 2,
            'promote' => 3,
            'standard' => 4,
            'watch' => 5,
            default => 4,
        };
    }

    private function normalizeCatalogRowDepartureDate(array $r): ?Carbon
    {
        if (empty($r['departure_date'])) {
            return null;
        }

        return Carbon::parse($r['departure_date'])->startOfDay();
    }

    /**
     * Retrouve la ligne catalogue pour un voyage Laravel + type de prestation (m+�me agr+�gat que la page workspace).
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

        // Package -� LVL-* -+ : ligne construite sans d+�pendre du catalogue WordPress ni de buildRows() complet.
        if ($type === 'package') {
            $fresh = Voyage::query()
                ->with([
                    'images' => function ($q) {
                        $q->orderBy('sort_order')->orderBy('id');
                    },
                ])
                ->find($tid);
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
                'destination' => $this->resolveVoyageDestination($voyage, null, $voyage->wp_post_id ? (int) $voyage->wp_post_id : null),
            ],
            'catalog_code' => $row['code'] ?? null,
            'catalog_type' => $row['type'] ?? null,
            'form_prefill' => $formPrefill,
            'modal_detail' => $row['modal_detail'] ?? null,
        ];
    }

    /**
     * Recalcule places r+�serv+�es / restantes pour une date de d+�part (m+�me logique que {@see ReservationWorkspaceBookingService::resolveRemainingSeats}).
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

        $inventory = $this->departureInventory->buildForTravelDates($user, $voyage, [$travelDateId])[$travelDateId] ?? null;
        $capacityTotal = max(0, (int) data_get($inventory, 'capacity_total', 0));
        $booked = max(0, (int) data_get($inventory, 'consumed_places', 0));
        $remaining = $capacityTotal > 0
            ? max(0, (int) data_get($inventory, 'remaining_places', 0))
            : max(0, $total - $booked);
        $pct = $capacityTotal > 0
            ? data_get($inventory, 'occupancy_rate')
            : ($total > 0 ? min(100, (int) round(($booked / $total) * 100)) : null);

        $placesPayload = ['state' => 'ok', 'total' => $capacityTotal > 0 ? $capacityTotal : $total];
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
        )->sortBy(fn (Departure $d) => Carbon::parse($d->start_date)->timestamp);

        $future = $eligible->first(fn (Departure $d) => ! Carbon::parse($d->start_date)->lt($today));
        if ($future) {
            return ['departure' => $future, 'is_past' => false];
        }

        $past = $eligible->filter(fn (Departure $d) => Carbon::parse($d->start_date)->lt($today))->sortByDesc(fn (Departure $d) => Carbon::parse($d->start_date)->timestamp)->first();

        return [
            'departure' => $past,
            'is_past' => $past !== null,
        ];
    }

    /**
     * Places affich+�es workspace = m+�me r+�gle que l���+�dition circuit/voyage {@see TourPlacesCalculator}.
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
     * Dates actives de la section Disponibilit+� (CRUD) : {@see TravelDate}.
     *
     * @return array{travelDate: ?TravelDate, is_past: bool}
     */
    private function pickTravelDateForPackageDisplay(Collection $travelDates, Carbon $today): array
    {
        $sorted = $travelDates->filter(fn ($d) => $d instanceof TravelDate && $d->date)
            ->sortBy(fn (TravelDate $d) => Carbon::parse($d->date)->timestamp);

        if ($sorted->isEmpty()) {
            return ['travelDate' => null, 'is_past' => false];
        }

        $future = $sorted->first(fn (TravelDate $d) => ! Carbon::parse($d->date)->lt($today));
        if ($future) {
            return ['travelDate' => $future, 'is_past' => false];
        }

        $past = $sorted->filter(fn (TravelDate $d) => Carbon::parse($d->date)->lt($today))
            ->sortByDesc(fn (TravelDate $d) => Carbon::parse($d->date)->timestamp)
            ->first();

        return [
            'travelDate' => $past,
            'is_past' => $past !== null,
        ];
    }

    /**
     * Colonne -� Prix Adulte -+ Circuits / voyages : {@see VoyageController::index} applique `$tour->getMeta('adult_price')`.
     * M+�me meta `postmeta.meta_key = adult_price`, format identique +� la vue (`number_format` + MAD).
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

    private function resolveCommercialCommissionPayload(WpPost $wp, ?Voyage $voyage, mixed $adultPriceRaw): array
    {
        $rawAdultCommission = $wp->getMeta('commission_adulte');
        $rawType = $wp->getMeta('commission_adulte_type', $wp->getMeta('commission_type', $wp->getMeta('commission_commerciale_type')));
        $commissionValue = $this->parseCommissionValue($rawAdultCommission);

        if ($commissionValue === null && $voyage && isset($voyage->commission_adulte)) {
            $commissionValue = $this->parseCommissionValue($voyage->commission_adulte);
        }

        if ($commissionValue === null || $commissionValue <= 0) {
            return [
                'configured' => false,
                'message' => 'Aucune commission configur+�e pour cette offre',
            ];
        }

        $type = $this->normalizeCommissionType($rawType, $rawAdultCommission);
        $unitPrice = $this->parseWpAdultPriceToFloat($adultPriceRaw)
            ?? ($voyage && $voyage->price_from ? (float) $voyage->price_from : 0.0);
        $estimated = $type === 'percentage'
            ? round($unitPrice * ($commissionValue / 100), 2)
            : round($commissionValue, 2);

        return [
            'configured' => true,
            'type' => $type,
            'type_label' => $type === 'percentage' ? 'Pourcentage' : 'Montant fixe',
            'value' => round($commissionValue, 2),
            'value_label' => $type === 'percentage'
                ? rtrim(rtrim(number_format($commissionValue, 2, ',', ' '), '0'), ',').'%'
                : number_format($commissionValue, 0, ',', ' ').' DH',
            'estimated_amount' => $estimated,
            'estimated_label' => number_format($estimated, 0, ',', ' ').' DH',
            'basis_unit_price' => round($unitPrice, 2),
            'currency' => trim((string) ($voyage?->currency ?? 'MAD')) ?: 'MAD',
        ];
    }

    private function parseCommissionValue(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return max(0, (float) $raw);
        }

        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        $value = str_replace(["\xc2\xa0", ' ', '%', 'DH', 'MAD', 'dh', 'mad'], '', $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? max(0, (float) $value) : null;
    }

    private function normalizeCommissionType(mixed $rawType, mixed $rawValue): string
    {
        $type = strtolower(trim((string) ($rawType ?? '')));
        if (in_array($type, ['percent', 'percentage', 'pourcentage', '%'], true)) {
            return 'percentage';
        }
        if (in_array($type, ['fixed', 'amount', 'montant', 'flat'], true)) {
            return 'fixed';
        }

        return str_contains((string) $rawValue, '%') ? 'percentage' : 'fixed';
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

    private function resolveWorkspaceRowBudgetAmount(array $row): ?int
    {
        $prefillAmount = data_get($row, 'form_prefill.prices.adult_amount');
        if (is_numeric($prefillAmount)) {
            return max(0, (int) round((float) $prefillAmount));
        }

        $priceLabel = trim((string) ($row['price_label'] ?? data_get($row, 'modal_detail.prices.adult_label', '')));
        if ($priceLabel === '') {
            return null;
        }

        $digits = preg_replace('/[^\d]/', '', $priceLabel);

        return $digits !== '' ? max(0, (int) $digits) : null;
    }

    private function workspaceRowSearchBlob(array $row): string
    {
        $summary = trim((string) ($row['summary'] ?? ''));
        $placesSearchBits = '';
        if (($row['type'] ?? null) === 'package' && ! empty($row['voyage_id']) && ($row['places_state'] ?? '') === 'ok' && ($row['places_total'] ?? null) !== null) {
            $placesSearchBits = ' places '.(int) $row['places_total'];
            foreach ((array) ($row['places_lines'] ?? []) as $line) {
                $placesSearchBits .= ' '.trim((string) ($line['room_type'] ?? '')).' '.trim((string) ($line['product'] ?? ''));
            }
        }

        return Str::lower(trim(
            (string) ($row['name'] ?? '')
            .' '.(string) ($row['code'] ?? '')
            .' '.(string) ($row['subtitle'] ?? '')
            .' '.(string) ($row['voyage_destination'] ?? data_get($row, 'modal_detail.destination', ''))
            .' '.(string) ($row['price_label'] ?? '')
            .' '.$summary
            .$placesSearchBits
        ));
    }

    private function parseWorkspaceFilterDate(mixed $raw, bool $endOfDay): ?Carbon
    {
        $value = trim((string) ($raw ?? ''));
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value);

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function filterWorkspaceRowDepartures(array $row, ?Carbon $dateFrom, ?Carbon $dateTo): ?array
    {
        if ($dateFrom === null && $dateTo === null) {
            return $row;
        }

        $departures = collect(data_get($row, 'modal_detail.departures', []))
            ->filter(fn ($departure) => is_array($departure))
            ->values();

        if ($departures->isNotEmpty()) {
            $matched = $departures->filter(function (array $departure) use ($dateFrom, $dateTo): bool {
                $dateIso = trim((string) ($departure['date_iso'] ?? ''));
                if ($dateIso === '') {
                    return false;
                }

                try {
                    $departureDate = Carbon::parse($dateIso)->startOfDay();
                } catch (\Throwable) {
                    return false;
                }

                if ($dateFrom !== null && $departureDate->lt($dateFrom->copy()->startOfDay())) {
                    return false;
                }
                if ($dateTo !== null && $departureDate->gt($dateTo->copy()->startOfDay())) {
                    return false;
                }

                return true;
            })->values();

            if ($matched->isEmpty()) {
                return null;
            }

            $first = $matched->first();
            $matchedIds = $matched->pluck('travel_date_id')->filter()->map(fn ($id) => (int) $id)->all();

            data_set($row, 'modal_detail.departures', $matched->all());
            if (is_array(data_get($row, 'modal_detail.travel_dates'))) {
                data_set($row, 'modal_detail.travel_dates', collect(data_get($row, 'modal_detail.travel_dates', []))
                    ->filter(fn ($travelDate) => in_array((int) ($travelDate['id'] ?? 0), $matchedIds, true))
                    ->values()
                    ->all());
            }
            if (is_array(data_get($row, 'form_prefill.travel_dates'))) {
                data_set($row, 'form_prefill.travel_dates', collect(data_get($row, 'form_prefill.travel_dates', []))
                    ->filter(fn ($travelDate) => in_array((int) ($travelDate['id'] ?? 0), $matchedIds, true))
                    ->values()
                    ->all());
            }
            if ($first !== null) {
                $row['travel_date_id'] = $first['travel_date_id'] ?? $row['travel_date_id'] ?? null;
                $row['departure_date'] = $first['date_iso'] ?? $row['departure_date'] ?? null;
                $row['departure_is_past'] = ! empty($first['is_past']);
                data_set($row, 'modal_detail.form.travel_date_id', $first['travel_date_id'] ?? data_get($row, 'modal_detail.form.travel_date_id'));
                data_set($row, 'form_prefill.default_travel_date_id', $first['travel_date_id'] ?? data_get($row, 'form_prefill.default_travel_date_id'));
            }

            return $row;
        }

        $rowDateRaw = $row['departure_date'] ?? data_get($row, 'modal_detail.departure_date');
        if (empty($rowDateRaw)) {
            return null;
        }

        try {
            $rowDate = Carbon::parse($rowDateRaw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($dateFrom !== null && $rowDate->lt($dateFrom->copy()->startOfDay())) {
            return null;
        }
        if ($dateTo !== null && $rowDate->gt($dateTo->copy()->startOfDay())) {
            return null;
        }

        return $row;
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
        $this->branchScope->scopeReservations($q, $user, ['shared_operational_aggregate' => true]);

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
     * Image catalogue : align+� sur {@see \App\Http\Controllers\Front\VoyageController::resolveHeroImages}
     * (m+�ta WP _tour_hero_image_id, _tour_hero_gallery_ids, _thumbnail_id + base uploads comme le front),
     * puis Laravel (featured / galerie), puis IDs galerie WP stock+�s sur {@see Voyage::$gallery_wp_ids}.
     */
    private function resolveCatalogRowImageUrl(?Voyage $voyage, ?WpPost $wp = null): ?string
    {
        $wpTourId = 0;
        if ($wp !== null) {
            $wpTourId = (int) $wp->ID;
        } elseif ($voyage !== null && (int) ($voyage->wp_post_id ?? 0) > 0) {
            $wpTourId = (int) $voyage->wp_post_id;
        }

        if ($wpTourId > 0) {
            $fromWp = $this->resolveWpTourFirstImageUrl($wpTourId);
            if ($fromWp !== null && $fromWp !== '') {
                return $fromWp;
            }
        }

        if ($voyage !== null) {
            $laravel = $voyage->featured_image_url;
            if ($laravel !== null && $laravel !== '') {
                return $laravel;
            }

            $fromGalleryIds = $this->resolveFirstUrlFromVoyageGalleryWpIds($voyage);
            if ($fromGalleryIds !== null && $fromGalleryIds !== '') {
                return $fromGalleryIds;
            }
        }

        return null;
    }

    /**
     * Premi+�re image utile pour un tour WordPress (m+�me ordre que la fiche voyage front).
     */
    private function resolveWpTourFirstImageUrl(int $wpTourId): ?string
    {
        $metas = WpPostMeta::query()
            ->where('post_id', $wpTourId)
            ->whereIn('meta_key', ['_tour_hero_image_id', '_tour_hero_gallery_ids', '_thumbnail_id'])
            ->pluck('meta_value', 'meta_key');

        $wpIds = [];
        if (! empty($metas['_tour_hero_image_id'])) {
            $wpIds[] = (int) $metas['_tour_hero_image_id'];
        }
        if (! empty($metas['_tour_hero_gallery_ids'])) {
            foreach (explode(',', (string) $metas['_tour_hero_gallery_ids']) as $id) {
                $id = (int) trim($id);
                if ($id > 0) {
                    $wpIds[] = $id;
                }
            }
        }
        if (! empty($metas['_thumbnail_id'])) {
            $wpIds[] = (int) $metas['_thumbnail_id'];
        }

        $wpIds = array_values(array_unique(array_filter($wpIds)));

        foreach ($wpIds as $aid) {
            $url = WpHeroImageService::publicUrlForAttachmentId((int) $aid);
            if ($url !== null && $url !== '') {
                return $url;
            }
        }

        return null;
    }

    private function resolveFirstUrlFromVoyageGalleryWpIds(Voyage $voyage): ?string
    {
        $raw = trim((string) ($voyage->gallery_wp_ids ?? ''));
        if ($raw === '') {
            return null;
        }
        foreach (explode(',', $raw) as $id) {
            $aid = (int) trim($id);
            if ($aid <= 0) {
                continue;
            }
            $url = WpHeroImageService::publicUrlForAttachmentId($aid);
            if ($url !== null && $url !== '') {
                return $url;
            }
        }

        return null;
    }

    /**
     * Texte court pour cartes workspace : accroche / description Laravel, sinon extrait WP.
     */
    private function resolveCatalogRowSummary(?Voyage $voyage, ?WpPost $wp = null): string
    {
        if ($voyage !== null) {
            $acc = trim((string) ($voyage->accroche ?? ''));
            if ($acc !== '') {
                return Str::limit(strip_tags($acc), 220);
            }
            $desc = trim((string) ($voyage->description ?? ''));
            if ($desc !== '') {
                $plain = preg_replace('/\s+/u', ' ', strip_tags($desc)) ?? '';

                return Str::limit(trim($plain), 220);
            }
        }
        if ($wp !== null) {
            $ex = trim((string) ($wp->post_excerpt ?? ''));
            if ($ex !== '') {
                return Str::limit(strip_tags($ex), 220);
            }
        }

        return '';
    }

    private function voyageHasPromoPrice(?Voyage $voyage): bool
    {
        if ($voyage === null) {
            return false;
        }
        $from = (int) ($voyage->price_from ?? 0);
        $old = (int) ($voyage->old_price ?? 0);

        return $old > 0 && $from > 0 && $old > $from;
    }

    /**
     * @return array{validee: int, en_cours: int, annulee: int}
     */
    private function emptyStats(): array
    {
        return ['validee' => 0, 'en_cours' => 0, 'annulee' => 0];
    }

    /**
     * Passagers r+�serv+�s (confirm+�s + en attente), m+�me p+�rim+�tre que les stats par statut.
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
        $this->branchScope->scopeReservations($q, $user, ['shared_operational_aggregate' => true]);

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
     * Libell+� date long fran+�ais (ex. 01 avril 2026) ��� +�vite les artefacts ICU (MMM / mois dupliqu+�s).
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
                ? sprintf('%d ch. %s +� %d pl. = %d places', $rc, $rt, $cu, $pr)
                : ($rt !== '' ? $rt.' -� '.$pr.' pl.' : '���');
            $out[] = array_merge($line, ['detail_label' => $detail]);
        }

        return $out;
    }

    /**
     * Extras propos+�s dans le formulaire workspace (align+�s sur les grilles historiques ; pilot+�s par le catalogue).
     *
     * @return list<array{id: string, name: string, desc: string, price_adult: float, price_child: float, icon: string}>
     */
    private function defaultWorkspaceExtrasCatalog(string $kind): array
    {
        return match ($kind) {
            'vol' => [
                ['id' => 'ext4', 'name' => 'Bagage soute 23kg', 'desc' => 'Ancillary', 'price_adult' => 450, 'price_child' => 450, 'icon' => 'fa-suitcase'],
                ['id' => 'ext5', 'name' => 'Si+�ge', 'desc' => 'SSR', 'price_adult' => 100, 'price_child' => 50, 'icon' => 'fa-chair'],
                ['id' => 'ext6', 'name' => 'Repas bord', 'desc' => 'Halal / v+�g+�tarien', 'price_adult' => 150, 'price_child' => 100, 'icon' => 'fa-hamburger'],
            ],
            'hebergement' => [
                ['id' => 'ext7', 'name' => 'Vue mer', 'desc' => 'Suppl+�ment', 'price_adult' => 200, 'price_child' => 200, 'icon' => 'fa-water'],
                ['id' => 'ext8', 'name' => 'Transfert a+�roport', 'desc' => 'A/R', 'price_adult' => 300, 'price_child' => 150, 'icon' => 'fa-taxi'],
                ['id' => 'ext9', 'name' => 'Spa', 'desc' => '45 min', 'price_adult' => 400, 'price_child' => 0, 'icon' => 'fa-spa'],
            ],
            default => [
                ['id' => 'ext1', 'name' => 'Visite historique', 'desc' => 'Guide', 'price_adult' => 150, 'price_child' => 100, 'icon' => 'fa-map-marked-alt'],
                ['id' => 'ext2', 'name' => 'Assurance multirisque', 'desc' => 'Annulation & sant+�', 'price_adult' => 350, 'price_child' => 200, 'icon' => 'fa-shield-alt'],
                ['id' => 'ext3', 'name' => 'Demi-pension', 'desc' => 'PD + d+�ner', 'price_adult' => 1200, 'price_child' => 600, 'icon' => 'fa-utensils'],
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

        $voyage = Voyage::query()->find($laravelVoyageId, ['id', 'name', 'slug', 'destination', 'wp_post_id']);
        if (! $voyage) {
            return [];
        }

        $activityRows = $this->resolveActivityExtrasCatalogForVoyage($voyage);
        $rows = VoyageExtra::query()
            ->where('voyage_id', $laravelVoyageId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
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
                'selection_mode' => 'per_pax',
                'pricing_type' => 'per_person',
            ];
        }

        return array_values(array_merge($out, $activityRows));
    }

    /**
     * Activit+�s li+�es au voyage pour la r+�servation workspace.
     * Priorit+�:
     * 1. travel_day_items type=activity (source voyage_activities_tab)
     * 2. wp.aj_tour_day_activities pour le wp_post_id du voyage
     * 3. fallback catalogue actif avec r+�gion/localisation tarif+�e quand aucun lien explicite n'existe
     *
     * @return list<array<string, mixed>>
     */
    private function resolveActivityExtrasCatalogForVoyage(Voyage $voyage): array
    {
        $out = [];
        $seen = [];

        $push = function (array $row) use (&$out, &$seen): void {
            $key = (string) ($row['id'] ?? '');
            if ($key === '' || isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $out[] = $row;
        };

        $inlineRows = TravelDayItem::query()
            ->where('voyage_id', (int) $voyage->id)
            ->where('type', 'activity')
            ->where('meta_json->source', 'voyage_activities_tab')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($inlineRows->isNotEmpty()) {
            $activityIds = $inlineRows
                ->map(fn (TravelDayItem $item) => (int) data_get($item->options_json, 'activity_id', 0))
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            $activityCatalog = $activityIds === []
                ? collect()
                : Activity::query()->whereIn('id', $activityIds)->get()->keyBy('id');

            foreach ($inlineRows as $row) {
                $activityId = (int) data_get($row->options_json, 'activity_id', 0);
                $activity = $activityId > 0 ? $activityCatalog->get($activityId) : null;
                $pricingType = data_get($row->options_json, 'pricing_type') === 'fixed' ? 'fixed' : 'per_person';
                $unitPrice = (float) data_get($row->options_json, 'unit_price', ((int) ($row->price_delta_per_person ?? 0)) / 100);
                if ($unitPrice <= 0 && $activity) {
                    $unitPrice = (float) ($activity->adult_price ?? $activity->base_price ?? 0);
                }
                $childPrice = $activity ? (float) ($activity->child_price ?? $unitPrice) : $unitPrice;
                $push([
                    'id' => 'act_tdi_'.$row->id,
                    'activity_id' => $activityId > 0 ? $activityId : null,
                    'name' => trim((string) ($row->title ?? $activity?->title ?? 'Activit+�')),
                    'desc' => 'Activit+� voyage',
                    'price_adult' => $unitPrice,
                    'price_child' => $childPrice > 0 ? $childPrice : $unitPrice,
                    'unit_price' => $unitPrice,
                    'quantity_default' => max(1, (int) data_get($row->options_json, 'quantity', 1)),
                    'selection_mode' => $pricingType === 'fixed' ? 'line_item' : 'per_pax',
                    'pricing_type' => $pricingType,
                    'extra_type' => 'activity',
                    'icon' => $activity?->icon ?: 'fa-person-hiking',
                ]);
            }
        }

        if ($voyage->wp_post_id) {
            $dayRows = TourDayActivity::query()
                ->with('activity')
                ->where('tour_id', (int) $voyage->wp_post_id)
                ->orderBy('day_id')
                ->orderBy('sort_order')
                ->get();

            foreach ($dayRows as $row) {
                $activity = $row->activity;
                if (! $activity) {
                    continue;
                }
                $unitPrice = (float) ($row->custom_price ?? $activity->adult_price ?? $activity->base_price ?? 0);
                $childPrice = (float) ($activity->child_price ?? $unitPrice);
                $push([
                    'id' => 'act_wp_'.$row->id,
                    'activity_id' => (int) $activity->id,
                    'name' => trim((string) ($row->custom_title ?: $activity->title ?: 'Activit+�')),
                    'desc' => trim((string) ($row->custom_description ?: ($activity->activity_type ?? 'Activit+� voyage'))),
                    'price_adult' => $unitPrice,
                    'price_child' => $childPrice > 0 ? $childPrice : $unitPrice,
                    'unit_price' => $unitPrice,
                    'quantity_default' => 1,
                    'selection_mode' => 'per_pax',
                    'pricing_type' => 'per_person',
                    'extra_type' => 'activity',
                    'icon' => $activity->icon ?: 'fa-person-hiking',
                ]);
            }
        }

        if ($out !== []) {
            return $out;
        }

        return $this->resolveFallbackActivityExtrasCatalog($voyage);
    }

    /**
     * Fallback prudent pour les bases o+� les liens explicites n'ont pas encore +�t+� persist+�s.
     * On retient d'abord les activit+�s dont la r+�gion/localisation correspond au voyage, sinon
     * les activit+�s tarif+�es avec r+�gion/localisation renseign+�e.
     *
     * @return list<array<string, mixed>>
     */
    private function resolveFallbackActivityExtrasCatalog(Voyage $voyage): array
    {
        $terms = $this->voyageActivitySearchTerms($voyage);
        $query = Activity::query()->where('is_active', true)->orderBy('title');
        if ($terms !== []) {
            $query->where(function ($sub) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%'.$term.'%';
                    $sub->orWhere('region_name', 'like', $like)
                        ->orWhere('location_text', 'like', $like)
                        ->orWhere('title', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                }
            });
        }
        $rows = $query->limit(12)->get();
        if ($rows->isEmpty()) {
            $rows = Activity::query()
                ->where('is_active', true)
                ->where(function ($sub) {
                    $sub->whereNotNull('region_name')->where('region_name', '!=', '')
                        ->orWhere(function ($q) {
                            $q->whereNotNull('location_text')->where('location_text', '!=', '');
                        });
                })
                ->where(function ($sub) {
                    $sub->where('adult_price', '>', 0);
                })
                ->orderBy('title')
                ->limit(12)
                ->get();
        }

        return $rows->map(function (Activity $activity) {
            $adult = (float) ($activity->adult_price ?? $activity->base_price ?? 0);
            $child = (float) ($activity->child_price ?? $adult);

            return [
                'id' => 'act_cat_'.$activity->id,
                'activity_id' => (int) $activity->id,
                'name' => trim((string) ($activity->title ?? 'Activit+�')),
                'desc' => trim((string) ($activity->region_name ?: $activity->location_text ?: ($activity->activity_type ?? 'Activit+� voyage'))),
                'price_adult' => $adult,
                'price_child' => $child > 0 ? $child : $adult,
                'unit_price' => $adult,
                'quantity_default' => 1,
                'selection_mode' => 'per_pax',
                'pricing_type' => 'per_person',
                'extra_type' => 'activity',
                'icon' => $activity->icon ?: 'fa-person-hiking',
            ];
        })->filter(fn (array $row) => (float) ($row['unit_price'] ?? 0) > 0)->values()->all();
    }

    /**
     * @return list<string>
     */
    private function voyageActivitySearchTerms(Voyage $voyage): array
    {
        $terms = [];
        foreach ([
            $this->resolveVoyageDestination($voyage, null, $voyage->wp_post_id ? (int) $voyage->wp_post_id : null),
            $voyage->name,
            $voyage->slug,
            $voyage->wp_post_id ? WpPost::query()->where('ID', (int) $voyage->wp_post_id)->value('post_title') : null,
            $voyage->wp_post_id ? WpPost::query()->where('ID', (int) $voyage->wp_post_id)->value('post_name') : null,
        ] as $value) {
            $value = trim((string) ($value ?? ''));
            if ($value === '') {
                continue;
            }
            $terms[] = mb_strtolower($value);
            foreach (preg_split('/[\s,;|\/_-]+/u', $value) ?: [] as $part) {
                $part = mb_strtolower(trim((string) $part));
                if ($part !== '' && mb_strlen($part) >= 4) {
                    $terms[] = $part;
                }
            }
        }

        return array_values(array_unique(array_filter($terms)));
    }

    private function availabilityUiFromBand(string $band, bool $hasPastOnlyDates): array
    {
        if ($hasPastOnlyDates) {
            return ['key' => 'past', 'label' => 'D+�parts pass+�s', 'tone' => 'amber'];
        }

        return match ($band) {
            'full' => ['key' => 'full', 'label' => 'Complet', 'tone' => 'red'],
            'low' => ['key' => 'low', 'label' => 'Peu de places', 'tone' => 'orange'],
            'ok' => ['key' => 'ok', 'label' => 'Disponible', 'tone' => 'emerald'],
            default => ['key' => 'unknown', 'label' => 'Capacit+� N/A', 'tone' => 'slate'],
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
     * Date par d+�faut pour le formulaire : prochain d+�part +� venir, sinon le plus r+�cent (pass+�).
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
     * Donn+�es pour pr+�remplissage du formulaire workspace (m+�mes sources que le catalogue / modal).
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
            'publish' => 'Publi+�',
            'draft' => 'Brouillon',
            'pending' => 'En attente de validation',
            'private' => 'Priv+�',
            'future' => 'Planifi+�',
            'trash' => 'Corbeille',
            default => $s ?: '���',
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
        User $user,
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
        mixed $adultPriceRaw,
        ?string $destination,
        ?int $preferredTravelDateId,
    ): array {
        $laravelId = $voyage ? (int) $voyage->id : null;
        $currency = $voyage && trim((string) ($voyage->currency ?? '')) !== ''
            ? trim((string) $voyage->currency)
            : 'MAD';

        $travelDates = [];
        foreach ($tdColl->filter(fn ($d) => $d instanceof TravelDate && $d->date)->sortBy(fn (TravelDate $d) => Carbon::parse($d->date)->timestamp) as $td) {
            $travelDate = Carbon::parse($td->date);
            $travelDates[] = [
                'id' => $td->id,
                'date_iso' => $travelDate->format('Y-m-d'),
                'date_label' => $this->formatFrenchLongDate($travelDate),
                'is_past' => $travelDate->lt($today),
            ];
        }

        $placesTotal = ($placesPayload['state'] ?? '') === 'ok' ? (int) ($placesPayload['total'] ?? 0) : null;
        $remaining = $placesTotal !== null ? max(0, $placesTotal - $passengersReserved) : null;
        $pct = ($placesTotal !== null && $placesTotal > 0)
            ? min(100, (int) round(($passengersReserved / $placesTotal) * 100))
            : null;

        $band = $this->computeWorkspaceAvailabilityBand($placesPayload, $passengersReserved);

        $departures = $this->buildPerDepartureAvailability($user, $voyage, $tdColl, $today, $placesPayload, $laravelId);

        $createRoute = $laravelId ? route('admin.reservations.create', array_filter([
            'tour_id' => $laravelId,
            'travel_date_id' => $preferredTravelDateId,
        ], fn ($v) => $v !== null && $v !== '')) : null;

        $publicShowUrl = ($voyage && ! empty($voyage->slug)) ? url('/voyages/'.$voyage->slug) : null;

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
            'departures' => $departures,
            'places' => [
                'state' => $placesPayload['state'],
                'total' => $placesTotal,
                'reserved' => $passengersReserved,
                'remaining' => $remaining,
                'fill_pct' => $pct,
                'scope' => 'all_dates',
            ],
            'rooms' => $placesPayload['lines'] ?? [],
            'prices' => [
                'adult_label' => $priceLabel,
                'child_label' => $this->formatChildPriceLabel($childPriceMetaRaw),
                'currency' => $currency,
            ],
            'commission' => $this->resolveCommercialCommissionPayload($wp, $voyage, $adultPriceRaw),
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
            'route_reserver' => $createRoute,
            'routes' => [
                'reservations' => $laravelId ? route('admin.reservations.index', array_filter([
                    'voyage_id' => $laravelId,
                ], fn ($v) => $v !== null && $v !== '')) : null,
                'create' => $createRoute,
                'edit_voyage' => route('admin.circuits.voyages.edit', $wpId),
                'public_show' => $publicShowUrl,
            ],
        ];
    }

    /**
     * Statistiques et places par date de d+�part (r+�servations filtr+�es par {@see TravelDate} id).
     *
     * @param  Collection<int, TravelDate>  $tdColl
     * @return list<array<string, mixed>>
     */
    private function buildPerDepartureAvailability(
        User $user,
        ?Voyage $voyage,
        Collection $tdColl,
        Carbon $today,
        array $placesPayload,
        ?int $laravelId,
    ): array {
        $sorted = $tdColl->filter(fn ($d) => $d instanceof TravelDate && $d->date)
            ->sortBy(fn (TravelDate $d) => $d->date->timestamp);

        $ids = $sorted->map(fn (TravelDate $d) => (int) $d->id)->values()->all();
        if ($ids === []) {
            return [];
        }

        $inventories = $voyage !== null && (int) ($voyage->id ?? 0) > 0
            ? $this->departureInventory->buildForTravelDates($user, $voyage, $ids)
            : [];

        $out = [];
        foreach ($sorted as $td) {
            $tid = (int) $td->id;
            $inventory = $inventories[$tid] ?? [
                'departure_id' => null,
                'capacity_total' => 0,
                'capacity_note' => 'Départ non synchronisé',
                'reservations_count_confirmed' => 0,
                'reservations_count_pending' => 0,
                'reservations_count_cancelled' => 0,
                'confirmed_places' => 0,
                'pending_places' => 0,
                'cancelled_places' => 0,
                'remaining_places' => 0,
                'occupancy_rate' => null,
                'status_key' => 'unknown',
                'status_label' => 'Départ non synchronisé',
                'room_lines' => [],
                'alerts' => [],
                'reservations' => [],
            ];

            $dossierTotal = (int) ($inventory['reservations_count_confirmed'] ?? 0)
                + (int) ($inventory['reservations_count_pending'] ?? 0)
                + (int) ($inventory['reservations_count_cancelled'] ?? 0);
            $confirmedPax = (int) ($inventory['confirmed_places'] ?? 0);
            $pendingPax = (int) ($inventory['pending_places'] ?? 0);
            $cancelledPax = (int) ($inventory['cancelled_places'] ?? 0);
            $departureId = (int) ($inventory['departure_id'] ?? 0) ?: null;
            $roomCap = max(0, (int) ($inventory['capacity_total'] ?? 0));
            $roomRemaining = max(0, (int) ($inventory['remaining_places'] ?? 0));
            $roomFillPct = isset($inventory['occupancy_rate']) ? (int) $inventory['occupancy_rate'] : null;
            $roomCapNote = $inventory['capacity_note'] ?? null;
            $roomStatusKey = (string) ($inventory['status_key'] ?? 'unknown');
            $roomStatusLabel = (string) ($inventory['status_label'] ?? ($roomCapNote ?: 'Disponible'));
            $roomLines = is_array($inventory['room_lines'] ?? null) ? $inventory['room_lines'] : [];
            $roomDebug = null;

            if (config('app.debug')) {
                $roomDebug = [
                    'departure_id' => $departureId,
                    'travel_date_id' => $tid,
                    'capacity_total' => $roomCap,
                    'confirmed_places' => $confirmedPax,
                    'pending_places' => $pendingPax,
                    'remaining_places' => $roomRemaining,
                    'room_lines' => $roomLines,
                    'alerts' => $inventory['alerts'] ?? [],
                ];
            }

            $reserveUrl = $laravelId ? route('admin.reservations.create', array_filter([
                'tour_id' => $laravelId,
                'travel_date_id' => $tid,
            ], fn ($v) => $v !== null && $v !== '')) : null;

            $listUrl = $laravelId ? route('admin.reservations.index', array_filter([
                'voyage_id' => $laravelId,
                'travel_date_id' => $tid,
            ], fn ($v) => $v !== null && $v !== '')) : null;

            $travelDateCarbon = Carbon::parse($td->date);
            $daysUntil = $today->diffInDays($travelDateCarbon, false);
            $out[] = [
                'travel_date_id' => $tid,
                'date_iso' => $travelDateCarbon->format('Y-m-d'),
                'date_label' => $this->formatFrenchLongDate($travelDateCarbon),
                'is_past' => $travelDateCarbon->lt($today),
                'days_until' => $daysUntil !== false ? (int) $daysUntil : null,
                'departure_id' => $departureId,
                'capacity' => $roomCap,
                'capacity_known' => true,
                'capacity_note' => $roomCapNote,
                'reservations' => [
                    'validee' => (int) ($inventory['reservations_count_confirmed'] ?? 0),
                    'en_cours' => (int) ($inventory['reservations_count_pending'] ?? 0),
                    'annulee' => (int) ($inventory['reservations_count_cancelled'] ?? 0),
                    'total' => $dossierTotal,
                ],
                'pax' => [
                    'validee' => $confirmedPax,
                    'en_cours' => $pendingPax,
                    'annulee' => $cancelledPax,
                ],
                'remaining' => $roomRemaining,
                'fill_pct' => $roomFillPct,
                'booked_pax' => $confirmedPax + $pendingPax,
                'status_key' => $roomStatusKey,
                'status_label' => $roomStatusLabel,
                'rooms' => $roomLines,
                'alerts' => array_values((array) ($inventory['alerts'] ?? [])),
                'inventory' => $inventory,
                'debug' => $roomDebug,
                'routes' => [
                    'reserve' => $reserveUrl,
                    'reservations' => $listUrl,
                ],
            ];
        }

        return $out;

        $metrics = $this->batchReservationMetricsByTravelDateIds($user, $voyage, $ids);

        // Source de v+�rit+� capacit+� : d+�part (r+�partition chambres) ��� departure_hotel_rooms.total_places.
        // Mapping : TravelDate(id) ��� Departure.wp_travel_date_id (pour les voyages WP li+�s).
        $departuresByTravelDateId = collect();
        if ($voyage !== null && (int) ($voyage->id ?? 0) > 0) {
            $depQuery = Departure::query()
                ->where('voyage_id', (int) $voyage->id)
                ->whereIn('wp_travel_date_id', $ids)
                ->get();
            $departuresByTravelDateId = $depQuery->keyBy(fn (Departure $d) => (int) ($d->wp_travel_date_id ?? 0));
        }

        $out = [];
        foreach ($sorted as $td) {
            $tid = (int) $td->id;
            $m = $metrics[$tid] ?? [
                'validee' => 0,
                'en_cours' => 0,
                'annulee' => 0,
                'pax_validee' => 0,
                'pax_en_cours' => 0,
                'pax_annulee' => 0,
            ];
            $dossierTotal = $m['validee'] + $m['en_cours'] + $m['annulee'];
            $confirmedPax = (int) ($m['pax_validee'] ?? 0);

            $departure = $departuresByTravelDateId->get($tid);
            $departureId = $departure instanceof Departure ? (int) $departure->id : null;

            $roomCap = 0;
            $roomRemaining = 0;
            $roomFillPct = null;
            $roomCapNote = $departureId ? 'Aucune chambre configurée' : 'Départ non synchronisé';
            $roomDebug = null;
            $roomLines = [];

            if ($laravelId) {
                try {
                    $roomPreview = $this->reservationPricing->previewDepartureRooms([
                        'tour_id' => $laravelId,
                        'travel_date_id' => $tid,
                        'departure_id' => $departureId,
                    ]);

                    $roomGroups = collect($roomPreview['rooms'] ?? []);
                    $roomLines = $roomGroups
                        ->flatMap(function ($group) {
                            $hotelName = (string) ($group['hotel_name'] ?? '');
                            $groupRooms = collect();

                            if (is_array($group) && isset($group['rooms']) && is_array($group['rooms'])) {
                                $groupRooms = collect($group['rooms']);
                            } elseif (is_array($group)) {
                                $groupRooms = collect([$group]);
                            }

                            return $groupRooms->map(function ($room) use ($hotelName) {
                                $type = (string) ($room['room_type'] ?? 'Chambre');
                                $quantity = max(0, (int) ($room['total_rooms'] ?? $room['available_rooms'] ?? 0));
                                $capacityPerRoom = max(0, (int) ($room['capacity_per_room'] ?? $room['capacity_total'] ?? $room['capacity'] ?? 0));
                                $remainingRooms = max(0, (int) ($room['remaining_rooms'] ?? $room['available_rooms'] ?? 0));
                                $usedRooms = max(0, (int) ($room['used_rooms'] ?? ($quantity - $remainingRooms)));
                                $remainingPlaces = max(0, (int) ($room['remaining_places'] ?? $room['available_places'] ?? ($remainingRooms * $capacityPerRoom)));
                                $totalPlaces = max(0, (int) ($room['total_places'] ?? ($quantity * $capacityPerRoom)));
                                $usedPlaces = max(0, (int) ($room['used_places'] ?? ($totalPlaces - $remainingPlaces)));

                                return [
                                    'type' => $type,
                                    'room_type' => $type,
                                    'hotel_name' => $hotelName,
                                    'quantity' => $quantity,
                                    'total_rooms' => $quantity,
                                    'capacity_per_room' => $capacityPerRoom,
                                    'supplement' => (float) ($room['supplement'] ?? $room['unit_supplement'] ?? 0),
                                    'used_rooms' => $usedRooms,
                                    'remaining_rooms' => $remainingRooms,
                                    'total_places' => $totalPlaces,
                                    'used_places' => $usedPlaces,
                                    'remaining_places' => $remainingPlaces,
                                    'status' => (string) ($room['status'] ?? ''),
                                ];
                            });
                        })
                        ->values()
                        ->all();

                    $roomCap = collect($roomLines)->sum(fn ($room) => (int) ($room['total_places'] ?? 0));
                    $roomRemaining = collect($roomLines)->sum(fn ($room) => (int) ($room['remaining_places'] ?? 0));
                    $usedPlaces = max(0, $roomCap - $roomRemaining);
                    $roomFillPct = $roomCap > 0 ? min(100, (int) round(($usedPlaces / $roomCap) * 100)) : null;

                    if ($roomLines !== []) {
                        $roomCapNote = null;
                    } elseif ($departureId === null) {
                        $roomCapNote = 'Départ non synchronisé';
                    }

                    Log::info('Workspace departure rooms debug', [
                        'tour_id' => $laravelId,
                        'travel_date_id' => $tid,
                        'departure_hotels_count' => $roomGroups->count(),
                        'rooms_count' => count($roomLines),
                        'rooms' => $roomLines,
                    ]);

                    if (config('app.debug')) {
                        $roomDebug = [
                            'departure_id' => $departureId,
                            'travel_date_id' => $tid,
                            'rooms_source' => $roomPreview['rooms_source'] ?? null,
                            'room_lines' => $roomLines,
                            'capacity' => $roomCap,
                            'remaining_places' => $roomRemaining,
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('Workspace departure rooms lookup failed', [
                        'tour_id' => $laravelId,
                        'travel_date_id' => $tid,
                        'departure_id' => $departureId,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $almostThreshold = 10;
            if ($roomCap <= 0) {
                $roomStatusKey = 'unknown';
                $roomStatusLabel = $roomCapNote ?: 'Aucune capacité';
            } elseif ($roomRemaining <= 0) {
                $roomStatusKey = 'full';
                $roomStatusLabel = 'Complet';
            } elseif ($roomRemaining < $almostThreshold) {
                $roomStatusKey = 'almost_full';
                $roomStatusLabel = 'Presque complet';
            } else {
                $roomStatusKey = 'available';
                $roomStatusLabel = 'Disponible';
            }

            $reserveUrl = $laravelId ? route('admin.reservations.create', array_filter([
                'tour_id' => $laravelId,
                'travel_date_id' => $tid,
            ], fn ($v) => $v !== null && $v !== '')) : null;

            $listUrl = $laravelId ? route('admin.reservations.index', array_filter([
                'voyage_id' => $laravelId,
                'travel_date_id' => $tid,
            ], fn ($v) => $v !== null && $v !== '')) : null;

            $travelDateCarbon = Carbon::parse($td->date);
            $daysUntil = $today->diffInDays($travelDateCarbon, false);
            $out[] = [
                'travel_date_id' => $tid,
                'date_iso' => $travelDateCarbon->format('Y-m-d'),
                'date_label' => $this->formatFrenchLongDate($travelDateCarbon),
                'is_past' => $travelDateCarbon->lt($today),
                'days_until' => $daysUntil !== false ? (int) $daysUntil : null,
                'departure_id' => $departureId,
                'capacity' => $roomCap,
                'capacity_known' => true,
                'capacity_note' => $roomCapNote,
                'reservations' => [
                    'validee' => $m['validee'],
                    'en_cours' => $m['en_cours'],
                    'annulee' => $m['annulee'],
                    'total' => $dossierTotal,
                ],
                'pax' => [
                    'validee' => $m['pax_validee'],
                    'en_cours' => $m['pax_en_cours'],
                    'annulee' => $m['pax_annulee'],
                ],
                'remaining' => $roomRemaining,
                'fill_pct' => $roomFillPct,
                'booked_pax' => $confirmedPax,
                'status_key' => $roomStatusKey,
                'status_label' => $roomStatusLabel,
                'rooms' => $roomLines,
                'debug' => $roomDebug,
                'routes' => [
                    'reserve' => $reserveUrl,
                    'reservations' => $listUrl,
                ],
            ];
            continue;

            $cap = 0;
            $capNote = null;
            $roomsDebug = null;
            $departureRooms = [];
            if ($departureId) {
                $roomRow = DB::connection($departure->getConnectionName() ?: config('database.default'))
                    ->table('departure_hotel_rooms as dhr')
                    ->join('departure_hotels as dh', 'dh.id', '=', 'dhr.departure_hotel_id')
                    ->where('dh.departure_id', $departureId)
                    ->where('dh.is_active', true)
                    ->whereNotIn('dhr.status', [DepartureHotelRoom::STATUS_CLOSED, DepartureHotelRoom::STATUS_INACTIVE])
                    ->selectRaw('COALESCE(SUM(dhr.total_places), 0) as tp, COUNT(*) as rooms_count')
                    ->first();

                $cap = max(0, (int) ($roomRow->tp ?? 0));
                $roomsCount = (int) ($roomRow->rooms_count ?? 0);

                // Fallback prudent : si des bases ont total_capacity renseign+� mais total_places non rempli.
                if ($cap === 0 && (int) ($departure->total_capacity ?? 0) > 0) {
                    $cap = (int) $departure->total_capacity;
                }

                if ($roomsCount <= 0) {
                    $capNote = 'Aucune chambre configur+�e';
                }

                // Charger le d�tail des chambres pour ce d�part
                $departureRooms = DB::connection($departure->getConnectionName() ?: config('database.default'))
                    ->table('departure_hotel_rooms as dhr')
                    ->join('departure_hotels as dh', 'dh.id', '=', 'dhr.departure_hotel_id')
                    ->where('dh.departure_id', $departureId)
                    ->where('dh.is_active', true)
                    ->whereNotIn('dhr.status', [DepartureHotelRoom::STATUS_CLOSED, DepartureHotelRoom::STATUS_INACTIVE])
                    ->select([
                        'dhr.room_type',
                        'dhr.capacity_total',
                        'dhr.total_rooms',
                        'dhr.reserved_rooms',
                        'dhr.available_rooms',
                        'dhr.total_places',
                        'dhr.reserved_places',
                        'dhr.available_places',
                        'dhr.supplement',
                        'dhr.status',
                    ])
                    ->orderBy('dhr.room_type')
                    ->get()
                    ->map(fn ($r) => [
                        'room_type' => $r->room_type,
                        'capacity_per_room' => (int) $r->capacity_total,
                        'total_rooms' => (int) $r->total_rooms,
                        'reserved_rooms' => (int) $r->reserved_rooms,
                        'available_rooms' => (int) $r->available_rooms,
                        'total_places' => (int) $r->total_places,
                        'reserved_places' => (int) $r->reserved_places,
                        'available_places' => (int) $r->available_places,
                        'supplement' => $r->supplement ? number_format((float) $r->supplement, 2, ',', ' ') . ' DH' : null,
                        'status' => $r->status,
                    ])
                    ->all();

                if (config('app.debug')) {
                    $roomsDebug = [
                        'departure_id' => $departureId,
                        'wp_travel_date_id' => (int) ($departure->wp_travel_date_id ?? 0),
                        'sum_total_places' => (int) ($roomRow->tp ?? 0),
                        'rooms_count' => $roomsCount,
                        'departure_total_capacity_field' => (int) ($departure->total_capacity ?? 0),
                        'capacity_final' => $cap,
                        'room_lines' => $departureRooms,
                    ];
                }
            } else {
                $capNote = 'D+�part non synchronis+�';
            }

            // IMPORTANT: places occup+�es = confirm+�es uniquement (pending n���occupe pas).
            $remaining = max(0, $cap - $confirmedPax);
            $fillPct = $cap > 0 ? min(100, (int) round(($confirmedPax / $cap) * 100)) : null;

            $almostThreshold = 10; // seuil m+�tier (modifiable)

            if ($cap <= 0) {
                $statusKey = 'unknown';
                $statusLabel = $capNote ?: 'Aucune capacit+�';
            } elseif ($remaining <= 0) {
                $statusKey = 'full';
                $statusLabel = 'Complet';
            } elseif ($remaining < $almostThreshold) {
                $statusKey = 'almost_full';
                $statusLabel = 'Presque complet';
            } else {
                $statusKey = 'available';
                $statusLabel = 'Disponible';
            }

            $reserveUrl = $laravelId ? route('admin.reservations.create', array_filter([
                'tour_id' => $laravelId,
                'travel_date_id' => $tid,
            ], fn ($v) => $v !== null && $v !== '')) : null;

            $listUrl = $laravelId ? route('admin.reservations.index', array_filter([
                'voyage_id' => $laravelId,
                'travel_date_id' => $tid,
            ], fn ($v) => $v !== null && $v !== '')) : null;

            $travelDateCarbon = Carbon::parse($td->date);
            $daysUntil = $today->diffInDays($travelDateCarbon, false);
            $out[] = [
                'travel_date_id' => $tid,
                'date_iso' => $travelDateCarbon->format('Y-m-d'),
                'date_label' => $this->formatFrenchLongDate($travelDateCarbon),
                'is_past' => $travelDateCarbon->lt($today),
                'days_until' => $daysUntil !== false ? (int) $daysUntil : null,
                'departure_id' => $departureId,
                'capacity' => $cap,
                'capacity_known' => true,
                'capacity_note' => $capNote,
                'reservations' => [
                    'validee' => $m['validee'],
                    'en_cours' => $m['en_cours'],
                    'annulee' => $m['annulee'],
                    'total' => $dossierTotal,
                ],
                'pax' => [
                    'validee' => $m['pax_validee'],
                    'en_cours' => $m['pax_en_cours'],
                    'annulee' => $m['pax_annulee'],
                ],
                'remaining' => $remaining,
                'fill_pct' => $fillPct,
                'booked_pax' => $confirmedPax,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'rooms' => $departureRooms,
                'debug' => $roomsDebug,
                'routes' => [
                    'reserve' => $reserveUrl,
                    'reservations' => $listUrl,
                ],
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, int>  $travelDateIds
     * @return array<int, array{validee: int, en_cours: int, annulee: int, pax_validee: int, pax_en_cours: int, pax_annulee: int}>
     */
    private function batchReservationMetricsByTravelDateIds(User $user, ?Voyage $voyage, array $travelDateIds): array
    {
        if ($voyage === null || $travelDateIds === []) {
            return [];
        }

        $travelDateIds = array_values(array_unique(array_map('intval', $travelDateIds)));
        $physicalIds = array_values(array_unique(array_map('intval', Voyage::allIdsSharingWpTour((int) $voyage->id))));

        $base = [];
        foreach ($travelDateIds as $tid) {
            $base[$tid] = [
                'validee' => 0,
                'en_cours' => 0,
                'annulee' => 0,
                'pax_validee' => 0,
                'pax_en_cours' => 0,
                'pax_annulee' => 0,
            ];
        }

        $q = Reservation::query()
            ->whereIn('tour_id', $physicalIds)
            ->whereIn('travel_date_id', $travelDateIds);
        $this->branchScope->scopeReservations($q, $user, [
            'tour_id' => (int) $voyage->id,
        ]);

        $aggregates = (clone $q)
            ->selectRaw('travel_date_id, status, COUNT(*) as aggregate, COALESCE(SUM(passengers_count), 0) as pax_sum')
            ->groupBy('travel_date_id', 'status')
            ->get();

        foreach ($aggregates as $row) {
            $tdId = (int) $row->travel_date_id;
            if (! isset($base[$tdId])) {
                continue;
            }
            $n = (int) $row->aggregate;
            $pax = (int) $row->pax_sum;
            match ($row->status) {
                Reservation::STATUS_VALIDEE => $base[$tdId]['validee'] += $n,
                Reservation::STATUS_EN_COURS => $base[$tdId]['en_cours'] += $n,
                Reservation::STATUS_ANNULEE => $base[$tdId]['annulee'] += $n,
                default => null,
            };
            match ($row->status) {
                Reservation::STATUS_VALIDEE => $base[$tdId]['pax_validee'] += $pax,
                Reservation::STATUS_EN_COURS => $base[$tdId]['pax_en_cours'] += $pax,
                Reservation::STATUS_ANNULEE => $base[$tdId]['pax_annulee'] += $pax,
                default => null,
            };
        }

        return $base;
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
     * Packages pour fiches {@see Voyage} sans wp_post_id (r+�servables hors catalogue WordPress).
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

        return $this->composeLaravelNativePackageRow($voyage, $statsByTour, $passengersByTour, $today, $user);
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
        User $user,
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
        $packageModalDetail = $this->buildLaravelNativePackageModalPayload($voyage, $rowStats, $paxBooked, $today, $user);
        $travelDateId = $packageModalDetail['form']['travel_date_id'] ?? null;
        $travelDatesList = $packageModalDetail['travel_dates'] ?? [];
        $hasFuture = $travelDatesList !== [] && collect($travelDatesList)->contains(fn ($d) => empty($d['is_past']));

        $resolvedDestination = $this->resolveVoyageDestination($voyage, null, $voyage->wp_post_id ? (int) $voyage->wp_post_id : null);

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
            'voyage_destination' => $resolvedDestination,
            'image_url' => $this->resolveCatalogRowImageUrl($voyage, null),
            'summary' => $this->resolveCatalogRowSummary($voyage, null),
            'is_featured' => (bool) $voyage->is_featured,
            'has_promo' => $this->voyageHasPromoPrice($voyage),
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
        User $user,
        Carbon $today,
        array $statsByTour,
        array $passengersByTour,
    ): void {
        foreach (
            Voyage::query()
                ->whereNull('wp_post_id')
                ->with([
                    'images' => function ($q) {
                        $q->orderBy('sort_order')->orderBy('id');
                    },
                ])
                ->orderBy('name')
                ->limit(200)
                ->get() as $voyage
        ) {
            $tid = (int) $voyage->id;
            if ($rows->contains(fn ($r) => ($r['type'] ?? '') === 'package' && (int) ($r['voyage_id'] ?? 0) === $tid)) {
                continue;
            }
            $rec = $this->composeLaravelNativePackageRow($voyage, $statsByTour, $passengersByTour, $today, $user);
            if ($rec !== null) {
                $rows->push($rec);
            }
        }
    }

    /**
     * @param  array{validee: int, en_cours: int, annulee: int}  $stats
     * @return array<string, mixed>
     */
    private function buildLaravelNativePackageModalPayload(Voyage $voyage, array $stats, int $paxBooked, Carbon $today, User $user): array
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

        $placesPayload = ['state' => 'na', 'total' => null, 'lines' => [], 'ignored' => []];
        $departures = $this->buildPerDepartureAvailability($user, $voyage, $tdColl, $today, $placesPayload, $tid);

        $createRoute = route('admin.reservations.create', array_filter([
            'tour_id' => $tid,
            'travel_date_id' => $preferredTravelDateId,
        ], fn ($v) => $v !== null && $v !== ''));

        return [
            'kind' => 'package',
            'title' => $voyage->name ?: 'Voyage #'.$tid,
            'wp_post_id' => null,
            'laravel_voyage_id' => $tid,
            'post_status' => null,
            'post_status_label' => 'Laravel',
            'destination' => $this->resolveVoyageDestination($voyage, null, $voyage->wp_post_id ? (int) $voyage->wp_post_id : null),
            'duration' => $this->resolveDurationLabel($voyage, null),
            'travel_dates' => $travelDates,
            'departures' => $departures,
            'places' => [
                'state' => 'na',
                'total' => null,
                'reserved' => $paxBooked,
                'remaining' => null,
                'fill_pct' => null,
                'scope' => 'all_dates',
            ],
            'rooms' => [],
            'prices' => [
                'adult_label' => $priceLabel,
                'child_label' => null,
                'currency' => $currency,
            ],
            'commission' => [
                'configured' => false,
                'message' => 'Aucune commission configur+�e pour cette offre',
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
                'label' => ($voyage->name ?: 'Voyage').' -� Laravel',
            ],
            'route_reserver' => $createRoute,
            'routes' => [
                'reservations' => route('admin.reservations.index', array_filter([
                    'voyage_id' => $tid,
                ], fn ($v) => $v !== null && $v !== '')),
                'create' => $createRoute,
                'edit_voyage' => null,
            ],
        ];
    }
}
