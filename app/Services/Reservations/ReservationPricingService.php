<?php

namespace App\Services\Reservations;

use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\TourHotel;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Models\VoyageExtra;
use App\Models\Wp\WpPost;
use App\Support\TourPlacesCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReservationPricingService
{
    public const PAYMENT_STATUS_NON_PAID = 'non_paid';
    public const PAYMENT_STATUS_PARTIAL = 'partial';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function calculate(array $payload): array
    {
        $voyage = $this->resolveVoyage($payload);
        $departure = $this->resolveDeparture($payload, $voyage);
        $travelDate = $this->resolveTravelDate($payload, $departure);
        $travelersCount = $this->countTravelers($payload['passengers'] ?? []);
        $basePrice = $this->resolveBasePrice($payload, $voyage, $departure, $travelDate);

        if ($basePrice <= 0) {
            throw ValidationException::withMessages([
                'tour_id' => ['Aucun prix nâ€™a Ã©tÃ© trouvÃ© pour ce voyage ou ce dÃ©part.'],
            ]);
        }

        $roomSummary = $this->resolveRoomSelection($payload, $departure, $travelersCount);
        $extrasSummary = $this->resolveExtras($payload, $voyage);
        $paidAmount = round(max(0, (float) ($payload['payment_amount'] ?? 0)), 2);

        $totalBase = round($basePrice * $travelersCount, 2);
        $totalAmount = round($totalBase + $roomSummary['room_supplement_total'] + $extrasSummary['extras_total'], 2);

        if ($paidAmount > $totalAmount + 0.009) {
            throw ValidationException::withMessages([
                'payment_amount' => ['Le montant payÃ© ne peut pas dÃ©passer le total du dossier.'],
            ]);
        }

        $remainingAmount = round(max(0, $totalAmount - $paidAmount), 2);

        return [
            'base_price' => round($basePrice, 2),
            'travelers_count' => $travelersCount,
            'total_base' => $totalBase,
            'room_supplement_total' => $roomSummary['room_supplement_total'],
            'extras_total' => $extrasSummary['extras_total'],
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $this->derivePaymentStatus($totalAmount, $paidAmount),
            'details' => [
                'voyage' => [
                    'id' => (int) $voyage->id,
                    'name' => (string) ($voyage->name ?? $voyage->slug ?? 'Voyage'),
                ],
                'departure' => [
                    'id' => (int) $departure->id,
                    'travel_date_id' => (int) ($travelDate?->id ?? ($departure->wp_travel_date_id ?? ($payload['travel_date_id'] ?? 0))),
                    'base_price' => $departure->base_price !== null ? (float) $departure->base_price : null,
                    'sale_price' => $departure->sale_price !== null ? (float) $departure->sale_price : null,
                    'available_capacity' => (int) ($departure->available_capacity ?? 0),
                ],
                'rooms' => $roomSummary['details'],
                'extras' => $extrasSummary['details'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function previewDepartureSelection(array $payload): array
    {
        $tourId = (int) ($payload['tour_id'] ?? 0);
        $travelDateId = (int) ($payload['travel_date_id'] ?? 0);
        $departureId = (int) ($payload['departure_id'] ?? 0);
        Log::info('URGENT PRICING PREVIEW INPUT', [
            'tour_id' => $tourId ?: null,
            'travel_date_id' => $travelDateId ?: null,
            'departure_id' => $departureId ?: null,
        ]);

        $voyage = $this->resolveVoyage($payload);
        $departure = $this->resolveDepartureForPreview($payload, $voyage);
        $travelersCount = max(1, $this->countTravelers($payload['passengers'] ?? []));
        $travelDate = $this->resolveTravelDate($payload, $departure);
        $unitPrice = $this->resolveBasePrice($payload, $voyage, $departure, $travelDate);

        if ($unitPrice <= 0) {
            throw ValidationException::withMessages([
                'tour_id' => ['Aucun prix configurÃ© pour ce voyage ou ce dÃ©part.'],
            ]);
        }

        $departure->loadMissing(['departureHotels.rooms', 'roomAllocations']);
        $rooms = $departure->departureHotels
            ->filter(fn ($hotel) => (bool) ($hotel->is_active ?? false))
            ->flatMap(function ($hotel) {
                return $hotel->rooms->filter(fn ($room) => ($room->status ?? null) !== 'inactive');
            })
            ->values();
        $departureRoomsCount = $rooms->count();

        $hasAssociatedHotels = $departure->departureHotels->filter(fn ($hotel) => (bool) ($hotel->is_active ?? false))->isNotEmpty();
        Log::info('URGENT ROOM SERVICE rooms after filter', [
            'departure_id' => $departure->id,
            'rooms_count_raw' => $departure->departureHotels->flatMap(fn ($h) => $h->rooms)->count(),
            'rooms_count_after_filter' => $rooms->count(),
            'filter_used' => 'status !== inactive',
        ]);
        $mode = 'blocked';
        $message = null;
        $roomsPayload = [];
        $roomsSource = null;
        $tourHotelsCount = null;
        $tourHotelRoomsCount = null;
        $availabilityCount = null;

        if ($rooms->isNotEmpty()) {
            $mode = 'rooms';
            $roomsSource = 'departure_hotel_rooms';
            $roomsPayload = $departure->departureHotels
                ->filter(fn ($hotel) => (bool) ($hotel->is_active ?? false))
                ->map(function ($hotel) {
                    return [
                        'departure_hotel_id' => (int) $hotel->id,
                        'hotel_name' => $hotel->hotel_name ?: 'HÃ´tel',
                        'rooms' => $hotel->rooms
                            ->filter(fn ($room) => ($room->status ?? null) !== 'inactive')
                            ->map(fn ($room) => [
                                'departure_hotel_room_id' => (int) $room->id,
                                'room_type' => (string) $room->room_type,
                                'capacity' => (int) $room->capacity_total,
                                'capacity_total' => (int) $room->capacity_total,
                                'available_rooms' => (int) $room->available_rooms,
                                'available_places' => (int) $room->available_places,
                                'unit_supplement' => (float) $room->supplement,
                                'supplement' => (float) $room->supplement,
                                'room_count' => 0,
                                'subtotal' => 0,
                                'status' => $room->status,
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all();
        } elseif (! $hasAssociatedHotels && (int) ($departure->available_capacity ?? 0) > 0) {
            $tourHotelLookup = $this->buildTourHotelAvailabilityPayload($voyage, $travelDate);
            $tourHotelsCount = $tourHotelLookup['tour_hotels_count'];
            $tourHotelRoomsCount = $tourHotelLookup['tour_hotel_rooms_count'];
            $availabilityCount = $tourHotelLookup['availability_count'];

            if ($tourHotelLookup['rooms'] !== []) {
                $mode = 'rooms';
                $roomsSource = 'tour_hotel_room_availabilities';
                $roomsPayload = $tourHotelLookup['rooms'];
            } else {
                $allocationLookup = $this->buildDepartureRoomAllocationPayload($departure);
                if ($allocationLookup['rooms'] !== []) {
                    $mode = 'rooms';
                    $roomsSource = 'departure_room_allocations';
                    $roomsPayload = $allocationLookup['rooms'];
                } else {
                    $mode = 'places_only';
                }
            }
        } else {
            $tourHotelLookup = $this->buildTourHotelAvailabilityPayload($voyage, $travelDate);
            $tourHotelsCount = $tourHotelLookup['tour_hotels_count'];
            $tourHotelRoomsCount = $tourHotelLookup['tour_hotel_rooms_count'];
            $availabilityCount = $tourHotelLookup['availability_count'];

            if ($tourHotelLookup['rooms'] !== []) {
                $mode = 'rooms';
                $roomsSource = 'tour_hotel_room_availabilities';
                $roomsPayload = $tourHotelLookup['rooms'];
            }

            // Fallback: try to use WP tour hotels / room availabilities for this travel_date
            try {
                $wpId = $voyage->wp_post_id ?? null;
                if ($mode !== 'rooms' && $wpId && $travelDate?->id) {
                    $tourHotels = \App\Models\TourHotel::getAllForTour($wpId)->load(['rooms', 'roomAvailabilities']);
                    $tourHotelsCount = $tourHotels->count();
                    $tourHotelRoomsCount = $tourHotels->flatMap(function ($hotel) {
                        return $hotel->rooms;
                    })->count();
                    $wpHotels = $tourHotels->map(function ($hotel) use ($travelDate) {
                        $rooms = [];
                        foreach ($hotel->rooms as $room) {
                            // find availability row for this travel_date
                            $avail = $room->dateAvailabilities->first(fn($a) => (int) ($a->travel_date_id ?? 0) === (int) $travelDate->id);
                            if (! $avail) {
                                continue;
                            }
                            $rooms[] = [
                                'departure_hotel_room_id' => null, // use tour_hotel_room_id below to reference WP room IDs
                                'tour_hotel_room_id' => (int) ($room->id ?? 0),
                                'departure_hotel_id' => (int) ($hotel->id ?? 0),
                                'hotel_name' => (string) ($hotel->hotel_name ?? 'HÃ´tel'),
                                'room_type' => (string) ($room->room_type ?? ''),
                                'capacity' => (int) ($room->capacity_total ?? $room->capacity_total ?? 0),
                                'capacity_total' => (int) ($room->capacity_total ?? 0),
                                'available_rooms' => (int) ($avail->available_rooms ?? ($room->room_count ?? 0)),
                                'available_places' => (int) ($avail->available_places ?? (($room->room_count ?? 0) * ($room->capacity_total ?? 0))),
                                'unit_supplement' => (float) ($avail->supplement ?? $room->supplement ?? 0),
                                'supplement' => (float) ($avail->supplement ?? $room->supplement ?? 0),
                                'room_count' => 0,
                                'subtotal' => 0,
                                'status' => $avail->status ?? null,
                            ];
                        }

                        return $rooms ? [
                            'departure_hotel_id' => (int) $hotel->id,
                            'hotel_name' => (string) ($hotel->hotel_name ?? 'HÃ´tel'),
                            'rooms' => $rooms,
                        ] : null;
                    })->filter()->values()->all();
                    $availabilityCount = collect($wpHotels)->sum(function ($hotel) {
                        return count($hotel['rooms'] ?? []);
                    });

                    if (! empty($wpHotels)) {
                        $mode = 'rooms';
                        $roomsSource = 'tour_hotel_room_availabilities';
                        $roomsPayload = $wpHotels;
                    }
                }
            } catch (\Throwable $e) {
                // ignore and keep previous mode
            }

            if ($mode !== 'rooms') {
                $allocationLookup = $this->buildDepartureRoomAllocationPayload($departure);
                if ($allocationLookup['rooms'] !== []) {
                    $mode = 'rooms';
                    $roomsSource = 'departure_room_allocations';
                    $roomsPayload = $allocationLookup['rooms'];
                }
            }

            if ($mode !== 'rooms') {
                if ($hasAssociatedHotels) {
                    $message = 'Configuration incomplÃ¨te : des hÃ´tels sont liÃ©s Ã  ce dÃ©part mais aucune chambre nâ€™est configurÃ©e.';
                } else {
                    $message = 'Ce dÃ©part nâ€™a plus de places disponibles.';
                }
            }
        }

        $totalBase = round($unitPrice * $travelersCount, 2);
        Log::info('URGENT PRICING PREVIEW ROOMS SOURCE', [
            'departure_rooms_count' => $departureRoomsCount,
            'tour_hotel_rooms_count' => $tourHotelRoomsCount,
            'availability_count' => $availabilityCount,
            'final_mode' => $mode,
            'rooms' => $roomsPayload,
        ]);
        Log::info('ROOM LOOKUP FINAL', [
            'tour_id' => $tourId ?: null,
            'travel_date_id' => $travelDateId ?: null,
            'departure_id' => $departureId ?: null,
            'departure_rooms_count' => $departureRoomsCount,
            'tour_hotels_count' => $tourHotelsCount,
            'tour_hotel_rooms_count' => $tourHotelRoomsCount,
            'tour_hotel_availabilities_count' => $availabilityCount,
            'final_mode' => $mode,
            'rooms_source' => $roomsSource,
            'rooms' => $roomsPayload,
        ]);

        return [
            'success' => true,
            'mode' => $mode,
            'message' => $message,
            'rooms_source' => $roomsSource,
            'departure' => [
                'id' => (int) $departure->id,
                'start_date' => $departure->getRawOriginal('start_date') ? date('Y-m-d', strtotime((string) $departure->getRawOriginal('start_date'))) : null,
                'end_date' => $departure->getRawOriginal('end_date') ? date('Y-m-d', strtotime((string) $departure->getRawOriginal('end_date'))) : null,
                'travel_date_id' => (int) ($travelDate?->id ?? ($departure->wp_travel_date_id ?? 0)),
                'available_places' => (int) ($departure->available_capacity ?? 0),
                'configure_url' => route('admin.circuits.voyages.departures.show', [$departure->voyage_id, $departure]),
            ],
            'pricing' => [
                'unit_price' => round($unitPrice, 2),
                'travelers_count' => $travelersCount,
                'total_base' => $totalBase,
                'room_supplement_total' => 0.0,
                'extras_total' => 0.0,
                'total_amount' => $totalBase,
            ],
            'rooms' => $roomsPayload,
        ];
    }

    /**
     * @return array{rooms: array<int, array<string, mixed>>, tour_hotels_count: int, tour_hotel_rooms_count: int, availability_count: int}
     */
    private function buildTourHotelAvailabilityPayload(Voyage $voyage, ?TravelDate $travelDate): array
    {
        $empty = [
            'rooms' => [],
            'tour_hotels_count' => 0,
            'tour_hotel_rooms_count' => 0,
            'availability_count' => 0,
        ];

        if (! $travelDate?->id) {
            return $empty;
        }

        $candidateTourIds = collect([
            (int) ($voyage->wp_post_id ?? 0),
            (int) ($voyage->id ?? 0),
        ])->filter(fn (int $id) => $id > 0)->unique()->values()->all();

        if ($candidateTourIds === []) {
            return $empty;
        }

        try {
            $tourHotels = TourHotel::query()
                ->whereIn('tour_id', $candidateTourIds)
                ->with(['rooms.dateAvailabilities' => function ($query) use ($travelDate) {
                    $query->where('travel_date_id', (int) $travelDate->id);
                }])
                ->orderByRaw('COALESCE(check_in_day, day_number, 1) ASC')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('ROOM LOOKUP tour hotel availability query failed', [
                'candidate_tour_ids' => $candidateTourIds,
                'travel_date_id' => (int) $travelDate->id,
                'message' => $e->getMessage(),
            ]);

            return $empty;
        }

        $roomsPayload = [];
        $tourHotelRoomsCount = 0;
        $availabilityCount = 0;

        foreach ($tourHotels as $hotel) {
            foreach ($hotel->rooms as $room) {
                $tourHotelRoomsCount++;

                if (TourPlacesCalculator::isDbRoomExplicitlyInactive($room)) {
                    continue;
                }

                $availability = $room->dateAvailabilities->first();
                if (! $availability) {
                    continue;
                }

                $availabilityCount++;
                $quantity = (int) ($availability->available_rooms ?? $room->room_count ?? 0);
                $capacity = TourPlacesCalculator::effectiveCapacity(
                    (int) ($room->capacity_total ?? 0),
                    (int) ($room->capacity_adults ?? 0),
                    (int) ($room->capacity_children ?? 0)
                );
                $availablePlaces = (int) ($availability->available_places ?? 0);
                if ($availablePlaces <= 0) {
                    $availablePlaces = $quantity * $capacity;
                }

                $roomType = trim((string) ($room->room_type ?? $room->room_label ?? ''));
                if ($roomType === '' || $quantity <= 0 || $capacity <= 0 || $availablePlaces <= 0) {
                    continue;
                }

                $roomPayload = [
                    'departure_hotel_room_id' => null,
                    'tour_hotel_room_id' => (int) $room->id,
                    'tour_hotel_id' => (int) $hotel->id,
                    'departure_hotel_id' => (int) $hotel->id,
                    'hotel_name' => (string) ($hotel->hotel_name ?? 'Hotel'),
                    'room_type' => $roomType,
                    'capacity' => $capacity,
                    'capacity_total' => $capacity,
                    'available_rooms' => $quantity,
                    'available_places' => $availablePlaces,
                    'unit_supplement' => (float) ($availability->supplement ?? $room->supplement ?? 0),
                    'supplement' => (float) ($availability->supplement ?? $room->supplement ?? 0),
                    'room_count' => 0,
                    'subtotal' => 0,
                    'status' => $availability->status ?? null,
                ];

                // One top-level item per room keeps diagnostics at rooms.length == actual room types
                // while preserving the existing nested hotel.rooms shape used by the UI.
                $roomsPayload[] = $roomPayload + [
                    'rooms' => [$roomPayload],
                ];
            }
        }

        return [
            'rooms' => $roomsPayload,
            'tour_hotels_count' => $tourHotels->count(),
            'tour_hotel_rooms_count' => $tourHotelRoomsCount,
            'availability_count' => $availabilityCount,
        ];
    }

    /**
     * @return array{rooms: array<int, array<string, mixed>>}
     */
    private function buildDepartureRoomAllocationPayload(Departure $departure): array
    {
        $departure->loadMissing('roomAllocations');

        if ($departure->roomAllocations->isEmpty()) {
            return ['rooms' => []];
        }

        $hotelNames = collect();
        $hotelIds = $departure->roomAllocations
            ->pluck('hotel_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->unique()
            ->values();

        if ($hotelIds->isNotEmpty()) {
            try {
                $hotelNames = TourHotel::query()
                    ->whereIn('id', $hotelIds->all())
                    ->pluck('hotel_name', 'id');
            } catch (\Throwable $e) {
                Log::warning('ROOM LOOKUP allocation hotel names query failed', [
                    'departure_id' => (int) $departure->id,
                    'hotel_ids' => $hotelIds->all(),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $roomsPayload = [];
        foreach ($departure->roomAllocations as $allocation) {
            $roomType = trim((string) ($allocation->room_type ?? ''));
            $quantity = (int) ($allocation->quantity ?? 0);
            $capacity = (int) ($allocation->capacity_per_room ?? 0);

            if ($roomType === '' || $quantity <= 0 || $capacity <= 0) {
                continue;
            }

            $hotelId = (int) ($allocation->hotel_id ?? 0);
        $roomsPayload[] = [
            'id' => (int) $allocation->id,
            'room_source_id' => (int) $allocation->id,
            'room_source_type' => 'departure_room_allocation',
            'departure_hotel_room_id' => (int) $allocation->id,
            'tour_hotel_room_id' => null,
            'departure_room_allocation_id' => (int) $allocation->id,
            'tour_hotel_id' => $hotelId ?: null,
            'departure_hotel_id' => $hotelId ?: null,
            'hotel_name' => (string) ($hotelNames->get($hotelId) ?: 'Hôtel'),
            'room_type' => (string) ($allocation->room_type ?? $allocation->type ?? 'Chambre'),
            'available_rooms' => $quantity,
            'capacity' => $capacity,
            'available_places' => $quantity * $capacity,
            'unit_supplement' => (float) ($allocation->unit_supplement ?? $allocation->supplement ?? 0),
        ];
        }

        return ['rooms' => $roomsPayload];
    }

    public function derivePaymentStatus(float $totalAmount, float $paidAmount): string
    {
        $totalAmount = round(max(0, $totalAmount), 2);
        $paidAmount = round(max(0, $paidAmount), 2);

        if ($paidAmount <= 0) {
            return self::PAYMENT_STATUS_NON_PAID;
        }

        if ($paidAmount >= $totalAmount - 0.009) {
            return self::PAYMENT_STATUS_PAID;
        }

        return self::PAYMENT_STATUS_PARTIAL;
    }

    /**
     * @param  mixed  $passengers
     */
    public function countTravelers(mixed $passengers): int
    {
        if (! is_iterable($passengers)) {
            return 1;
        }

        $passengersArray = is_array($passengers) ? $passengers : [];
        $count = isset($passengersArray['__main']) ? 0 : 1;
        foreach ($passengers as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $firstName = trim((string) ($row['first_name'] ?? ''));
            $lastName = trim((string) ($row['last_name'] ?? ''));
            if ($firstName !== '' || $lastName !== '') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveVoyage(array $payload): Voyage
    {
        if (($payload['voyage'] ?? null) instanceof Voyage) {
            return $payload['voyage'];
        }

        $voyageId = (int) ($payload['tour_id'] ?? 0);
        $voyage = $voyageId > 0 ? Voyage::query()->find($voyageId) : null;
        if (! $voyage) {
            throw ValidationException::withMessages([
                'tour_id' => ['Le voyage sÃ©lectionnÃ© est introuvable.'],
            ]);
        }

        return $voyage;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveDeparture(array $payload, Voyage $voyage): Departure
    {
        if (($payload['departure'] ?? null) instanceof Departure) {
            /** @var Departure $departure */
            $departure = $payload['departure'];
            return $departure;
        }

        $departureId = (int) ($payload['departure_id'] ?? 0);
        $departure = $departureId > 0 ? Departure::query()->find($departureId) : null;
        if (! $departure) {
            throw ValidationException::withMessages([
                'departure_id' => ['Le dÃ©part sÃ©lectionnÃ© est introuvable.'],
            ]);
        }
        if ((int) $departure->voyage_id !== (int) $voyage->id) {
            throw ValidationException::withMessages([
                'departure_id' => ['Le dÃ©part sÃ©lectionnÃ© ne correspond pas au voyage demandÃ©.'],
            ]);
        }

        return $departure;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveBasePrice(array $payload, Voyage $voyage, Departure $departure, ?TravelDate $travelDate = null): float
    {
        $priceFromTravelDate = $travelDate?->price_override;
        $priceFromDepartureSale = $departure->sale_price;
        $priceFromDepartureBase = $departure->base_price;
        $priceFromVoyage = $voyage->price_from;

        $candidates = [
            $priceFromTravelDate,
            $priceFromDepartureSale,
            $priceFromDepartureBase,
            $priceFromVoyage,
        ];

        // Try WordPress meta as a fallback (min_price / base_price)
        $wpPrice = null;
        $wpId = $travelDate?->travel_id ?? $voyage->wp_post_id ?? null;
        if ($wpId) {
            try {
                $wp = WpPost::query()->find((int) $wpId);
                if ($wp) {
                    $metaMin = $wp->getMeta('min_price');
                    $metaBase = $wp->getMeta('base_price');
                    $wpPrice = is_numeric($metaMin) ? (float) $metaMin : (is_numeric($metaBase) ? (float) $metaBase : null);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if ($wpPrice !== null) {
            $candidates[] = $wpPrice;
        }

        // Debug log to compare sources when troubleshooting
        try {
            Log::info('Reservation pricing debug', [
                'voyage_id' => $voyage->id ?? null,
                'departure_id' => $departure->id ?? null,
                'travel_date_id' => $travelDate?->id ?? null,
                'price_from_travel_date' => $priceFromTravelDate,
                'price_from_departure_sale' => $priceFromDepartureSale,
                'price_from_departure_base' => $priceFromDepartureBase,
                'price_from_voyage' => $priceFromVoyage,
                'price_from_wordpress' => $wpPrice,
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== null && (float) $candidate > 0) {
                return round((float) $candidate, 2);
            }
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveDepartureForPreview(array $payload, Voyage $voyage): Departure
    {
        $departureId = (int) ($payload['departure_id'] ?? 0);
        if ($departureId > 0) {
            $departure = Departure::query()->find($departureId);
            if ($departure && (int) $departure->voyage_id === (int) $voyage->id) {
                return $departure;
            }
        }

        $travelDateId = (int) ($payload['travel_date_id'] ?? 0);
        if ($travelDateId > 0) {
            $departure = Departure::query()->where('voyage_id', $voyage->id)->where('wp_travel_date_id', $travelDateId)->first();
            if ($departure) {
                return $departure;
            }

            $travelDate = TravelDate::query()->find($travelDateId);
            if ($travelDate) {
                $departure = Departure::query()->where('voyage_id', $voyage->id)->where('wp_travel_date_id', (int) $travelDate->id)->first();
                if ($departure) {
                    return $departure;
                }
            }

            // Fallback: try matching by start_date == travel_date.date (useful when wp_travel_date_id not populated)
            try {
                if (! isset($travelDate)) {
                    $travelDate = TravelDate::query()->find($travelDateId);
                }
                if ($travelDate && $travelDate->date) {
                    $maybe = Departure::query()
                        ->where('voyage_id', $voyage->id)
                        ->whereDate('start_date', $travelDate->date)
                        ->first();
                    if ($maybe) {
                        return $maybe;
                    }
                }
            } catch (\Throwable $e) {
                // ignore and continue to global fallback
            }
        }

        $departure = Departure::query()->where('voyage_id', $voyage->id)->orderBy('start_date')->orderBy('id')->first();
        if (! $departure) {
            throw ValidationException::withMessages([
                'departure_id' => ['Le dÃ©part sÃ©lectionnÃ© est introuvable.'],
            ]);
        }

        return $departure;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveTravelDate(array $payload, Departure $departure): ?TravelDate
    {
        $travelDateId = (int) ($payload['travel_date_id'] ?? 0);
        if ($travelDateId > 0) {
            $travelDate = TravelDate::query()->find($travelDateId);
            if ($travelDate) {
                return $travelDate;
            }
        }

        if ((int) ($departure->wp_travel_date_id ?? 0) > 0) {
            return TravelDate::query()->find((int) $departure->wp_travel_date_id);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{room_supplement_total: float,details: array<int, array<string, mixed>>}
     */
    private function resolveRoomSelection(array $payload, Departure $departure, int $travelersCount): array
    {
        $roomAllocations = is_array($payload['room_allocations'] ?? null) ? $payload['room_allocations'] : [];
        if ($roomAllocations !== []) {
            $totalCapacity = 0;
            $supplementTotal = 0.0;
            $details = [];

            foreach ($roomAllocations as $index => $allocation) {
                if (! is_array($allocation)) {
                    continue;
                }

                $capacity = max(0, (int) ($allocation['capacity'] ?? 0));
                $occupied = max(0, (int) ($allocation['occupied_count'] ?? count($allocation['traveler_keys'] ?? [])));
                $supplement = round(max(0, (float) ($allocation['supplement_total'] ?? 0)), 2);
                if ($capacity <= 0 || $occupied <= 0) {
                    throw ValidationException::withMessages([
                        "room_allocations.$index.capacity" => ['Allocation chambre invalide.'],
                    ]);
                }
                if ($occupied > $capacity) {
                    throw ValidationException::withMessages([
                        "room_allocations.$index.occupied_count" => ['Une chambre depasse sa capacite.'],
                    ]);
                }

                $totalCapacity += $capacity;
                $supplementTotal += $supplement;
                $details[] = [
                    'room_source_type' => $allocation['room_source_type'] ?? null,
                    'room_source_id' => $allocation['room_source_id'] ?? null,
                    'room_type' => (string) ($allocation['room_type'] ?? 'Chambre'),
                    'occupancy_mode' => $allocation['occupancy_mode'] ?? null,
                    'capacity' => $capacity,
                    'occupied_count' => $occupied,
                    'status' => $allocation['status'] ?? 'pending',
                    'traveler_keys' => is_array($allocation['traveler_keys'] ?? null) ? $allocation['traveler_keys'] : [],
                    'subtotal' => $supplement,
                ];
            }

            if ($totalCapacity < $travelersCount) {
                throw ValidationException::withMessages([
                    'room_allocations' => ['La capacite des chambres selectionnees est insuffisante pour le nombre de voyageurs.'],
                ]);
            }

            return [
                'room_supplement_total' => round($supplementTotal, 2),
                'details' => $details,
            ];
        }

        $selectedRows = collect(is_array($payload['hotel_rooms'] ?? null) ? $payload['hotel_rooms'] : [])
            ->filter(fn ($row) => is_array($row) && (int) ($row['room_count'] ?? 0) > 0)
            ->values();

        $departure->loadMissing(['departureHotels.rooms']);
        $rooms = $departure->departureHotels
            ->flatMap(fn ($hotel) => $hotel->rooms)
            ->filter(fn ($room) => ($room->status ?? null) !== 'inactive')
            ->values();

        if ($rooms->isEmpty()) {
            // Try WP tour hotels availability as fallback (tour_hotels -> tour_hotel_rooms -> date availabilities)
            $travelDateId = (int) ($payload['travel_date_id'] ?? 0);
            $voyage = $departure->voyage;
            $wpId = $voyage?->wp_post_id ?? null;

            if ($wpId && $travelDateId > 0) {
                try {
                    $tourHotels = \App\Models\TourHotel::getAllForTour($wpId)->load(['rooms', 'rooms.dateAvailabilities']);
                    $built = collect();
                    foreach ($tourHotels as $hotel) {
                        foreach ($hotel->rooms as $room) {
                            $avail = $room->dateAvailabilities->first(fn($a) => (int) ($a->travel_date_id ?? 0) === $travelDateId);
                            if (! $avail) continue;
                            $built->push((object) [
                                'id' => (int) $room->id,
                                'room_type' => $room->room_type,
                                'capacity_total' => (int) ($room->capacity_total ?? 0),
                                'available_rooms' => (int) ($avail->available_rooms ?? ($room->room_count ?? 0)),
                                'available_places' => (int) ($avail->available_places ?? (($room->room_count ?? 0) * ($room->capacity_total ?? 0))),
                                'supplement' => (float) ($avail->supplement ?? $room->supplement ?? 0),
                                'is_wp' => true,
                            ]);
                        }
                    }

                    if ($built->isNotEmpty()) {
                        $rooms = $built->values();
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            if ($rooms->isEmpty()) {
                if ($departure->departureHotels->where('is_active', true)->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'hotel_rooms' => ['Configuration incomplÃ¨te : ajoutez les chambres pour ce dÃ©part.'],
                    ]);
                }

                if ((int) ($departure->available_capacity ?? 0) <= 0) {
                    throw ValidationException::withMessages([
                        'hotel_rooms' => ['Ce dÃ©part nâ€™a plus de places disponibles.'],
                    ]);
                }

                if ($travelersCount > (int) $departure->available_capacity) {
                    throw ValidationException::withMessages([
                        'hotel_rooms' => ["Stock insuffisant : il reste seulement {$departure->available_capacity} places."],
                    ]);
                }

                return [
                    'room_supplement_total' => 0.0,
                    'details' => [],
                ];
            }
        }

        if ($selectedRows->isEmpty()) {
            throw ValidationException::withMessages([
                'hotel_rooms' => ['SÃ©lectionnez au moins une chambre pour continuer.'],
            ]);
        }

        $totalCapacity = 0;
        $supplementTotal = 0.0;
        $details = [];

        foreach ($selectedRows as $index => $row) {
            $roomId = (int) ($row['departure_hotel_room_id'] ?? 0);
            $tourRoomId = (int) ($row['tour_hotel_room_id'] ?? 0);
            $usingTourRoom = false;

            if ($roomId <= 0 && $tourRoomId > 0) {
                $roomId = $tourRoomId;
                $usingTourRoom = true;
            }

            if ($roomId <= 0) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.departure_hotel_room_id" => ['Chaque ligne chambre doit rÃ©fÃ©rencer une vraie chambre de dÃ©part ou une chambre du catalogue.'],
                ]);
            }

            $room = $rooms->first(function ($candidate) use ($roomId) {
                return (int) data_get($candidate, 'id') === $roomId;
            });

            if (! $room) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.departure_hotel_room_id" => ['La chambre sÃ©lectionnÃ©e est introuvable pour ce dÃ©part.'],
                ]);
            }

            $roomCount = max(0, (int) ($row['room_count'] ?? 0));
            $availableRooms = max(0, (int) data_get($room, 'available_rooms', 0));
            $availablePlaces = max(0, (int) data_get($room, 'available_places', 0));
            $capacity = max(1, (int) data_get($room, 'capacity_total', 1));
            $supplement = round(max(0, (float) data_get($room, 'supplement', 0)), 2);

            if ($roomCount > $availableRooms) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.room_count" => ['Le nombre de chambres demandÃ© dÃ©passe le stock disponible.'],
                ]);
            }

            $lineCapacity = $roomCount * $capacity;
            if ($lineCapacity > $availablePlaces) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.room_count" => ['Le nombre de places demandÃ© dÃ©passe le stock disponible pour cette chambre.'],
                ]);
            }

            $lineSubtotal = round($roomCount * $supplement, 2);
            $totalCapacity += $lineCapacity;
            $supplementTotal += $lineSubtotal;
            $details[] = [
                'departure_hotel_room_id' => $roomId,
                'room_type' => (string) data_get($room, 'room_type', 'Chambre'),
                'available_rooms' => $availableRooms,
                'available_places' => $availablePlaces,
                'capacity' => $capacity,
                'unit_supplement' => $supplement,
                'room_count' => $roomCount,
                'subtotal' => $lineSubtotal,
            ];
        }

        if ($totalCapacity < $travelersCount) {
            throw ValidationException::withMessages([
                'hotel_rooms' => ['La capacitÃ© des chambres sÃ©lectionnÃ©es est insuffisante pour le nombre de voyageurs.'],
            ]);
        }

        if ((int) ($departure->available_capacity ?? 0) > 0 && $travelersCount > (int) $departure->available_capacity) {
            throw ValidationException::withMessages([
                'hotel_rooms' => ['Le nombre de voyageurs dÃ©passe la capacitÃ© disponible sur ce dÃ©part.'],
            ]);
        }

        return [
            'room_supplement_total' => round($supplementTotal, 2),
            'details' => $details,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{extras_total: float,details: array<int, array<string, mixed>>}
     */
    private function resolveExtras(array $payload, Voyage $voyage): array
    {
        $rows = is_array($payload['extras_json'] ?? null)
            ? $payload['extras_json']
            : (is_array($payload['extras_payload'] ?? null) ? $payload['extras_payload'] : []);

        if ($rows === []) {
            return ['extras_total' => 0.0, 'details' => []];
        }

        $configuredExtras = $payload['voyage_extras'] ?? null;
        if ($configuredExtras instanceof Collection) {
            $extras = $configuredExtras->keyBy(fn ($item) => (int) data_get($item, 'id'));
        } elseif (is_array($configuredExtras)) {
            $extras = collect($configuredExtras)->keyBy(fn ($item) => (int) data_get($item, 'id'));
        } else {
            $extras = VoyageExtra::query()
                ->where('voyage_id', $voyage->id)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');
        }

        $details = [];
        $total = 0.0;

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $extraId = (int) ($row['voyage_extra_id'] ?? 0);
            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $scope = (string) ($row['application_scope'] ?? 'dossier');
            $travelerKeys = is_array($row['traveler_keys'] ?? null) ? array_values(array_filter($row['traveler_keys'])) : [];

            if ($extraId <= 0) {
                throw ValidationException::withMessages([
                    "extras_json.$index.voyage_extra_id" => ['Chaque extra doit rÃ©fÃ©rencer un extra actif du voyage.'],
                ]);
            }

            $extra = $extras->get($extraId);
            if (! $extra) {
                throw ValidationException::withMessages([
                    "extras_json.$index.voyage_extra_id" => ['Lâ€™extra sÃ©lectionnÃ© nâ€™est pas disponible pour ce voyage.'],
                ]);
            }

            $adultPrice = round((float) ($extra->price_adult ?? 0), 2);
            $childPrice = round((float) ($extra->price_child ?? 0), 2);

            if ($scope === 'traveler_selection') {
                if ($travelerKeys === []) {
                    throw ValidationException::withMessages([
                        "extras_json.$index.traveler_keys" => ['SÃ©lectionnez au moins un voyageur pour cet extra.'],
                    ]);
                }

                $lineTotal = round($quantity * count($travelerKeys) * max($adultPrice, $childPrice, 0), 2);
            } else {
                $lineTotal = round($quantity * max($adultPrice, 0), 2);
            }

            $total += $lineTotal;
            $details[] = [
                'voyage_extra_id' => $extraId,
                'name' => (string) $extra->name,
                'description' => (string) ($extra->description ?? ''),
                'quantity' => $quantity,
                'application_scope' => $scope,
                'traveler_keys' => $travelerKeys,
                'unit_price_adult' => $adultPrice,
                'unit_price_child' => $childPrice,
                'total_price' => $lineTotal,
            ];
        }

        return [
            'extras_total' => round($total, 2),
            'details' => $details,
        ];
    }
}
