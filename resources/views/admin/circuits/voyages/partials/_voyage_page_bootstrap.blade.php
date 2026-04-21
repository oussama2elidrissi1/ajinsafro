@php
    $voyageId = (int) ($voyage->ID ?? 0);
    $tourHotelsBootstrap = collect($tourHotels ?? [])->mapWithKeys(function ($hotel) {
        $hotelImgUrl = !empty($hotel->image_id)
            ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int) $hotel->image_id)
            : '';

        return [
            (string) $hotel->id => [
                'id' => (int) $hotel->id,
                'hotel_name' => $hotel->hotel_name,
                'address' => $hotel->address,
                'room_type' => $hotel->room_type,
                'meal_plan' => $hotel->meal_plan,
                'stars' => $hotel->stars !== null ? (int) $hotel->stars : null,
                'image_id' => $hotel->image_id !== null ? (int) $hotel->image_id : null,
                'image_url' => $hotelImgUrl,
                'check_in_day' => $hotel->check_in_day ?? $hotel->day_number,
                'check_out_day' => $hotel->check_out_day ?? $hotel->day_number,
                'day_number' => $hotel->day_number,
                'rooms' => collect($hotel->rooms ?? [])->map(function ($room) {
                    return [
                        'id' => (int) ($room->id ?? 0),
                        'room_type' => (string) ($room->room_type ?? ''),
                        'room_count' => (int) ($room->room_count ?? 0),
                        'capacity_total' => (int) ($room->capacity_total ?? 0),
                        'capacity_adults' => (int) ($room->capacity_adults ?? 0),
                        'capacity_children' => (int) ($room->capacity_children ?? 0),
                        'is_active' => (bool) ($room->is_active ?? true),
                        'date_availabilities' => collect($room->dateAvailabilities ?? [])->map(function ($availability) {
                            return [
                                'id' => (int) ($availability->id ?? 0),
                                'travel_date_id' => (int) ($availability->travel_date_id ?? 0),
                                'available_rooms' => (int) ($availability->available_rooms ?? 0),
                                'available_places' => (int) ($availability->available_places ?? 0),
                                'status' => (string) ($availability->status ?? ''),
                                'supplement' => (float) ($availability->supplement ?? 0),
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
            ],
        ];
    })->all();

    $transferArrivalBootstrap = collect($transferArrivals ?? [])->map(function ($transfer) {
        return [
            'id' => (int) $transfer->id,
            'direction' => 'arrival',
            'from_label' => $transfer->from_label,
            'to_label' => $transfer->to_label,
            'pickup_time' => $transfer->pickup_time,
            'dropoff_time' => $transfer->dropoff_time,
            'vehicle_type' => $transfer->vehicle_type,
            'notes' => $transfer->notes,
            'day_number' => $transfer->day_number,
            'is_optional' => (bool) $transfer->is_optional,
            'image_id' => $transfer->image_id !== null ? (int) $transfer->image_id : null,
        ];
    })->values()->all();

    $transferDepartureBootstrap = collect($transferDepartures ?? [])->map(function ($transfer) {
        return [
            'id' => (int) $transfer->id,
            'direction' => 'departure',
            'from_label' => $transfer->from_label,
            'to_label' => $transfer->to_label,
            'pickup_time' => $transfer->pickup_time,
            'dropoff_time' => $transfer->dropoff_time,
            'vehicle_type' => $transfer->vehicle_type,
            'notes' => $transfer->notes,
            'day_number' => $transfer->day_number,
            'is_optional' => (bool) $transfer->is_optional,
            'image_id' => $transfer->image_id !== null ? (int) $transfer->image_id : null,
        ];
    })->values()->all();

    $programmeActivitiesCatalog = collect($activitiesCatalog ?? [])->map(function ($activity) {
        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'activity_type' => $activity->activity_type,
            'region_name' => $activity->region_name ?: $activity->location_text,
            'location_text' => $activity->location_text,
            'place_text' => $activity->location_text,
            'base_price' => (float) ($activity->adult_price ?? $activity->base_price ?? 0),
        ];
    })->values()->all();

    $tourActivitiesCatalog = collect($activitiesCatalog ?? [])->map(function ($activity) {
        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'description' => $activity->description,
            'activity_type' => $activity->activity_type,
            'region_name' => $activity->region_name ?: $activity->location_text,
            'location_text' => $activity->location_text,
            'place_text' => $activity->location_text,
            'base_price' => (float) ($activity->adult_price ?? $activity->base_price ?? 0),
            'adult_price' => (float) ($activity->adult_price ?? $activity->base_price ?? 0),
            'child_price' => (float) ($activity->child_price ?? 0),
            'default_duration_minutes' => (int) ($activity->default_duration_minutes ?? 0),
            'min_age' => (int) ($activity->min_age ?? 0),
            'max_age' => (int) ($activity->max_age ?? 0),
        ];
    })->values()->all();

    $tourActivitiesSelected = collect($tourActivities ?? [])->map(function ($activity) {
        return [
            'id' => data_get($activity, 'activity_id'),
            'title' => data_get($activity, 'title'),
        ];
    })->values()->all();

    $bootstrap = [
        'programDayTypes' => \App\Services\BusinessReferentialService::programDayTypes(),
        'voyageActivityPricingTypes' => \App\Services\BusinessReferentialService::voyageActivityPricingTypes(),
        'tourPlacesCalcDebug' => (bool) config('app.debug'),
        'wpTourId' => $voyageId,
        'csrfToken' => csrf_token(),
        'heroUploadUrl' => route('admin.circuits.voyages.hero-image.upload', ['id' => $voyageId]),
        'heroSelectUrl' => route('admin.circuits.voyages.hero-image.select', ['id' => $voyageId]),
        'heroRemoveUrl' => route('admin.circuits.voyages.hero-image.remove', ['id' => $voyageId]),
        'heroGalleryUploadUrl' => route('admin.circuits.voyages.hero-image.upload', ['id' => $voyageId]),
        'heroGallerySelectUrl' => route('admin.circuits.voyages.hero-image.select', ['id' => $voyageId]),
        'wpMediaSearchUrl' => url('admin/wp-media/search'),
        'wpFeaturedMediaListUrl' => route('admin.wp-media.list'),
        'wpFeaturedMediaUploadUrl' => route('admin.wp-media.upload'),
        'wpFeaturedMediaSelectUrl' => route('admin.wp-media.select'),
        'wpFeaturedMediaRemoveUrl' => route('admin.wp-media.remove'),
        'ajaxListActivityUrl' => route('admin.circuits.activities.ajax.list'),
        'ajaxStoreActivityUrl' => route('admin.circuits.activities.ajax.store'),
        'tourHotelsData' => $tourHotelsBootstrap,
        'tourTransfersData' => [
            'arrival' => $transferArrivalBootstrap,
            'departure' => $transferDepartureBootstrap,
        ],
        'programDayHotelsTransfers' => $programDayHotelsTransfers ?? [],
        'programmeActivitiesCatalog' => $programmeActivitiesCatalog,
        'tourActivitiesCatalog' => $tourActivitiesCatalog,
        'tourActivitiesSelected' => $tourActivitiesSelected,
        'programApiUrl' => $programApiUrl ?? '',
        'programVoyageId' => $voyageId,
    ];
@endphp
<script>
    (function () {
        window.VOYAGE_EDIT_BOOTSTRAP = @json($bootstrap);
        var boot = window.VOYAGE_EDIT_BOOTSTRAP || {};
        window.TOUR_PLACES_CALC_DEBUG = !!boot.tourPlacesCalcDebug;
        window.tourHotelsData = boot.tourHotelsData || {};
        window.tourTransfersData = boot.tourTransfersData || { arrival: [], departure: [] };
        window.programDayHotelsTransfers = boot.programDayHotelsTransfers || {};
        window.ALL_PROGRAMME_ACTIVITIES_CATALOG = boot.programmeActivitiesCatalog || [];
        window.ALL_TOUR_ACTIVITIES_CATALOG = boot.tourActivitiesCatalog || [];
        window.PROGRAMME_ACTIVITIES_CATALOG = boot.programmeActivitiesCatalog || [];
        window.TOUR_ACTIVITIES_SELECTED = boot.tourActivitiesSelected || [];
        window.TOUR_ACTIVITIES_CATALOG = boot.tourActivitiesCatalog || [];
        window.PROGRAM_API_URL = boot.programApiUrl || '';
        window.PROGRAM_VOYAGE_ID = boot.programVoyageId || boot.wpTourId || 0;
        window.VOYAGE_ACTIVITY_PRICING_TYPES = boot.voyageActivityPricingTypes || [];
        window.PROGRAM_DAY_TYPES = boot.programDayTypes || [];
    })();
</script>
