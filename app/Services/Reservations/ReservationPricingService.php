<?php

namespace App\Services\Reservations;

use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\TourHotel;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Models\VoyageExtra;
use App\Models\Wp\TourDayActivity;
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
        $unitPriceBeforeDiscount = $this->resolveBasePrice($payload, $voyage, $departure, $travelDate);

        if ($unitPriceBeforeDiscount <= 0) {
            throw ValidationException::withMessages([
                'tour_id' => ['Aucun prix n?a ?t? trouv? pour ce voyage ou ce d?part.'],
            ]);
        }

        $roomSummary = $this->resolveRoomSelection($payload, $departure, $travelersCount);
        $extrasSummary = $this->resolveExtras($payload, $voyage);
        $paidAmount = round(max(0, (float) ($payload['payment_amount'] ?? 0)), 2);

        $discountScope = $this->normalizeDiscountScope($payload['discount_scope'] ?? null);
        $grossTotalBase = round($unitPriceBeforeDiscount * $travelersCount, 2);
        $grossTotalAmount = round($grossTotalBase + $roomSummary['room_supplement_total'] + $extrasSummary['extras_total'], 2);
        $discount = $this->resolveDiscount(
            $payload,
            $discountScope === 'total' ? $grossTotalAmount : $unitPriceBeforeDiscount,
            $discountScope
        );

        if ($discountScope === 'total') {
            $totalAmount = round(max(0, $grossTotalAmount - $discount['discount_amount']), 2);
            $discountPerTraveler = $travelersCount > 0 ? round($discount['discount_amount'] / $travelersCount, 2) : 0.0;
            $basePrice = round(max(0, $unitPriceBeforeDiscount - $discountPerTraveler), 2);
            $totalBase = $grossTotalBase;
        } else {
            $basePrice = $discount['unit_price_after_discount'];
            $totalBase = round($basePrice * $travelersCount, 2);
            $totalAmount = round($totalBase + $roomSummary['room_supplement_total'] + $extrasSummary['extras_total'], 2);
        }

        if ($paidAmount > $totalAmount + 0.009) {
            throw ValidationException::withMessages([
                'payment_amount' => ['Le montant pay? ne peut pas d?passer le total du dossier.'],
            ]);
        }

        $remainingAmount = round(max(0, $totalAmount - $paidAmount), 2);

        return [
            'base_price' => round($basePrice, 2),
            'unit_price_before_discount' => round($unitPriceBeforeDiscount, 2),
            'discount_type' => $discount['discount_type'],
            'discount_scope' => $discount['discount_scope'],
            'discount_value' => $discount['discount_value'],
            'unit_price_after_discount' => round($basePrice, 2),
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
                'discount' => $discount,
            ],
        ];
    }

    /**
          * R?sout le prix unitaire officiel de r?servation ? partir de la fiche produit uniquement.
     * Priorit? : wp_base_price (Prix de base MAD) > wp_adult_price > wp_min_price > voyage_price_from.
     *
     * @return array{unit_price: float, source: string, sources: array<string, mixed>}
     */
    public function resolveReservationUnitPrice(Voyage $voyage): array
    {
        $wpPrices = $this->resolveWordPressTourPrices($voyage, null);

        $sources = [
            'wp_base_price'   => $wpPrices['base_price'],
            'wp_adult_price'  => $wpPrices['adult_price'],
            'wp_min_price'    => $wpPrices['min_price'],
            'voyage_price_from' => $voyage->price_from !== null ? (float) $voyage->price_from : null,
        ];

        foreach (['wp_base_price', 'wp_adult_price', 'wp_min_price', 'voyage_price_from'] as $source) {
            $value = $sources[$source] ?? null;
            if ($value !== null && (float) $value > 0) {
                return [
                    'unit_price' => round((float) $value, 2),
                    'source'     => $source,
                    'sources'    => $sources,
                ];
            }
        }

        return [
            'unit_price' => 0.0,
            'source'     => 'none',
            'sources'    => $sources,
        ];
    }

    /**
     * @return array{unit_price: float, source: string, sources: array<string, mixed>}
     */
    public function resolveUnitPrice(Voyage $voyage, ?Departure $departure = null, ?TravelDate $travelDate = null): array
    {
        // 1) Prix officiel de la fiche produit (r?gle m?tier)
        $productPrice = $this->resolveReservationUnitPrice($voyage);
        if ($productPrice['unit_price'] > 0) {
            $sources = $productPrice['sources'];
            $sources['departure_sale_price'] = $departure?->sale_price !== null ? (float) $departure->sale_price : null;
            $sources['departure_base_price'] = $departure?->base_price !== null ? (float) $departure->base_price : null;
            $sources['travel_date_price_override'] = $travelDate?->price_override !== null ? (float) $travelDate->price_override : null;

            return [
                'unit_price' => $productPrice['unit_price'],
                'source'     => $productPrice['source'],
                'sources'    => $sources,
            ];
        }

        // 2) Fallback absolu : sources d?part / travel-date
        $wpPrices = $this->resolveWordPressTourPrices($voyage, $travelDate);

        $sources = [
            'departure_sale_price' => $departure?->sale_price !== null ? (float) $departure->sale_price : null,
            'departure_base_price' => $departure?->base_price !== null ? (float) $departure->base_price : null,
            'travel_date_price_override' => $travelDate?->price_override !== null ? (float) $travelDate->price_override : null,
            'wp_adult_price' => $wpPrices['adult_price'],
            'wp_min_price' => $wpPrices['min_price'],
            'wp_base_price' => $wpPrices['base_price'],
            'voyage_price_from' => $voyage->price_from !== null ? (float) $voyage->price_from : null,
        ];

        foreach ([
            'departure_sale_price',
            'departure_base_price',
            'travel_date_price_override',
            'wp_adult_price',
            'wp_min_price',
            'wp_base_price',
            'voyage_price_from',
        ] as $source) {
            $value = $sources[$source] ?? null;
            if ($value !== null && (float) $value > 0) {
                return [
                    'unit_price' => round((float) $value, 2),
                    'source' => $source,
                    'sources' => $sources,
                ];
            }
        }

        return [
            'unit_price' => 0.0,
            'source' => 'none',
            'sources' => $sources,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{discount_type: string|null, discount_value: float, discount_amount: float, unit_price_after_discount: float, discount_scope: string}
     */
    private function resolveDiscount(array $payload, float $basisAmount, string $scope = 'per_unit'): array
    {
        $type = $this->normalizeDiscountType($payload['discount_type'] ?? null);
        $value = round(max(0, (float) ($payload['discount_value'] ?? 0)), 2);
        $basisAmount = round(max(0, $basisAmount), 2);

        if ($type === null || $value <= 0) {
            return [
                'discount_type' => null,
                'discount_value' => 0.0,
                'discount_amount' => 0.0,
                'unit_price_after_discount' => $basisAmount,
                'discount_scope' => $scope,
            ];
        }

        $discountAmount = $type === 'percentage'
            ? round($basisAmount * (min(100, $value) / 100), 2)
            : min(round($value, 2), $basisAmount);

        return [
            'discount_type' => $type,
            'discount_value' => $type === 'percentage' ? min(100, $value) : $value,
            'discount_amount' => $discountAmount,
            'unit_price_after_discount' => round(max(0, $basisAmount - $discountAmount), 2),
            'discount_scope' => $scope,
        ];
    }

    private function normalizeDiscountScope(mixed $scope): string
    {
        $scope = strtolower(trim((string) $scope));

        return match ($scope) {
            'total', 'dossier', 'reservation' => 'total',
            default => 'per_unit',
        };
    }

    private function normalizeDiscountType(mixed $type): ?string
    {
        $type = strtolower(trim((string) $type));

        return match ($type) {
            'percentage', 'percent', '%' => 'percentage',
            'fixed', 'amount', 'dh', 'mad' => 'fixed',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function previewDepartureRooms(array $payload): array
    {
        $voyage = $this->resolveVoyage($payload);
        $departure = $this->resolveDepartureForPreview($payload, $voyage);
        $travelDate = $this->resolveTravelDate($payload, $departure);

        return $this->buildDepartureRoomPreviewPayload($voyage, $departure, $travelDate);
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
                'tour_id' => ['Aucun prix configur? pour ce voyage ou ce d?part.'],
            ]);
        }

        $roomPreview = $this->buildDepartureRoomPreviewPayload($voyage, $departure, $travelDate);
        $mode = (string) ($roomPreview['mode'] ?? 'blocked');
        $message = $roomPreview['message'] ?? null;
        $roomsPayload = $roomPreview['rooms'] ?? [];
        $roomsSource = $roomPreview['rooms_source'] ?? null;
        $departureRoomsCount = (int) ($roomPreview['meta']['departure_rooms_count'] ?? 0);
        $tourHotelsCount = $roomPreview['meta']['tour_hotels_count'] ?? null;
        $tourHotelRoomsCount = $roomPreview['meta']['tour_hotel_rooms_count'] ?? null;
        $availabilityCount = $roomPreview['meta']['availability_count'] ?? null;

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
            'departure' => $roomPreview['departure'] ?? null,
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

        $departure->loadMissing(['departureHotels.rooms', 'roomAllocations']);
        $rooms = $departure->departureHotels
            ->filter(fn ($hotel) => (bool) ($hotel->is_active ?? false))
            ->flatMap(function ($hotel) {
                return $hotel->rooms->filter(fn ($room) => !in_array($room->status ?? null, ['inactive', 'closed'], true));
            })
            ->values();
        $departureRoomsCount = $rooms->count();

        $hasAssociatedHotels = $departure->departureHotels->filter(fn ($hotel) => (bool) ($hotel->is_active ?? false))->isNotEmpty();
        Log::info('URGENT ROOM SERVICE rooms after filter', [
            'departure_id' => $departure->id,
            'rooms_count_raw' => $departure->departureHotels->flatMap(fn ($h) => $h->rooms)->count(),
            'rooms_count_after_filter' => $rooms->count(),
            'filter_used' => "status not in ['inactive','closed']",
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
                        'hotel_name' => $hotel->hotel_name ?: 'H?tel',
                        'rooms' => $hotel->rooms
                            ->filter(fn ($room) => !in_array($room->status ?? null, ['inactive', 'closed'], true))
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
                                'hotel_name' => (string) ($hotel->hotel_name ?? 'H?tel'),
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
                            'hotel_name' => (string) ($hotel->hotel_name ?? 'H?tel'),
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
                    $message = 'Configuration incompl?te : des h?tels sont li?s ? ce d?part mais aucune chambre n?est configur?e.';
                } else {
                    $message = 'Ce d?part n?a plus de places disponibles.';
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
     * @return array<string, mixed>
     */
    private function buildDepartureRoomPreviewPayload(Voyage $voyage, Departure $departure, ?TravelDate $travelDate): array
    {
        $departure->loadMissing(['departureHotels.rooms', 'roomAllocations']);
        $rooms = $departure->departureHotels
            ->filter(fn ($hotel) => (bool) ($hotel->is_active ?? false))
            ->flatMap(function ($hotel) {
                return $hotel->rooms->filter(fn ($room) => ! in_array($room->status ?? null, ['inactive', 'closed'], true));
            })
            ->values();
        $departureRoomsCount = $rooms->count();

        $hasAssociatedHotels = $departure->departureHotels->filter(fn ($hotel) => (bool) ($hotel->is_active ?? false))->isNotEmpty();
        Log::info('URGENT ROOM SERVICE rooms after filter', [
            'departure_id' => $departure->id,
            'rooms_count_raw' => $departure->departureHotels->flatMap(fn ($h) => $h->rooms)->count(),
            'rooms_count_after_filter' => $rooms->count(),
            'filter_used' => "status not in ['inactive','closed']",
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
                    $roomLines = $hotel->rooms
                        ->filter(fn ($room) => ! in_array($room->status ?? null, ['inactive', 'closed'], true))
                        ->map(function ($room) {
                            $totalRooms = max(0, (int) ($room->total_rooms ?? 0));
                            $remainingRooms = max(0, (int) ($room->available_rooms ?? 0));
                            $usedRooms = max(0, $totalRooms - $remainingRooms);
                            $capacity = max(0, (int) ($room->capacity_total ?? 0));
                            $totalPlaces = max(0, (int) ($room->total_places ?? ($totalRooms * $capacity)));
                            $remainingPlaces = max(0, (int) ($room->available_places ?? ($remainingRooms * $capacity)));
                            $usedPlaces = max(0, $totalPlaces - $remainingPlaces);

                            return [
                                'departure_hotel_room_id' => (int) $room->id,
                                'room_type' => (string) $room->room_type,
                                'capacity' => $capacity,
                                'capacity_total' => $capacity,
                                'capacity_per_room' => $capacity,
                                'total_rooms' => $totalRooms,
                                'used_rooms' => $usedRooms,
                                'reserved_rooms' => $usedRooms,
                                'available_rooms' => $remainingRooms,
                                'remaining_rooms' => $remainingRooms,
                                'total_places' => $totalPlaces,
                                'used_places' => $usedPlaces,
                                'reserved_places' => $usedPlaces,
                                'available_places' => $remainingPlaces,
                                'remaining_places' => $remainingPlaces,
                                'unit_supplement' => (float) ($room->supplement ?? 0),
                                'supplement' => (float) ($room->supplement ?? 0),
                                'room_count' => 0,
                                'subtotal' => 0,
                                'status' => $room->status,
                            ];
                        })
                        ->values()
                        ->all();

                    return $roomLines !== [] ? [
                        'departure_hotel_id' => (int) $hotel->id,
                        'hotel_name' => $hotel->hotel_name ?: 'Hôtel',
                        'rooms' => $roomLines,
                    ] : null;
                })
                ->filter()
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
                    $message = 'Configuration incomplète : des hôtels sont liés à ce départ mais aucune chambre n’est configurée.';
                } else {
                    $message = 'Ce départ n’a plus de places disponibles.';
                }
            }
        }

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
            'rooms' => $roomsPayload,
            'meta' => [
                'departure_rooms_count' => $departureRoomsCount,
                'tour_hotels_count' => $tourHotelsCount,
                'tour_hotel_rooms_count' => $tourHotelRoomsCount,
                'availability_count' => $availabilityCount,
            ],
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
                    'capacity_per_room' => $capacity,
                    'total_rooms' => $quantity,
                    'used_rooms' => 0,
                    'available_rooms' => $quantity,
                    'remaining_rooms' => $quantity,
                    'total_places' => $availablePlaces,
                    'used_places' => 0,
                    'available_places' => $availablePlaces,
                    'remaining_places' => $availablePlaces,
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
            'capacity' => $capacity,
            'capacity_total' => $capacity,
            'capacity_per_room' => $capacity,
            'total_rooms' => $quantity,
            'used_rooms' => 0,
            'available_rooms' => $quantity,
            'remaining_rooms' => $quantity,
            'total_places' => $quantity * $capacity,
            'used_places' => 0,
            'available_places' => $quantity * $capacity,
            'remaining_places' => $quantity * $capacity,
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
                'tour_id' => ['Le voyage s?lectionn? est introuvable.'],
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
                'departure_id' => ['Le d?part s?lectionn? est introuvable.'],
            ]);
        }
        if ((int) $departure->voyage_id !== (int) $voyage->id) {
            throw ValidationException::withMessages([
                'departure_id' => ['Le d?part s?lectionn? ne correspond pas au voyage demand?.'],
            ]);
        }

        return $departure;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveBasePrice(array $payload, Voyage $voyage, Departure $departure, ?TravelDate $travelDate = null): float
    {
        $resolved = $this->resolveUnitPrice($voyage, $departure, $travelDate);

        // Debug log to compare sources when troubleshooting
        try {
            Log::info('Reservation pricing debug', array_merge([
                'voyage_id' => $voyage->id ?? null,
                'departure_id' => $departure->id ?? null,
                'travel_date_id' => $travelDate?->id ?? null,
                'unit_price_final' => $resolved['unit_price'],
                'unit_price_source' => $resolved['source'],
            ], $resolved['sources']));
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        return (float) $resolved['unit_price'];
    }

    /**
     * @return array{adult_price: float|null, min_price: float|null, base_price: float|null}
     */
    private function resolveWordPressTourPrices(Voyage $voyage, ?TravelDate $travelDate = null): array
    {
        $wpId = $travelDate?->travel_id ?? $voyage->wp_post_id ?? null;
        if (! $wpId) {
            return ['adult_price' => null, 'min_price' => null, 'base_price' => null];
        }

        try {
            $wp = WpPost::query()->find((int) $wpId);
            if (! $wp) {
                return ['adult_price' => null, 'min_price' => null, 'base_price' => null];
            }

            return [
                'adult_price' => $this->parsePositivePrice($wp->getMeta('adult_price')),
                'min_price' => $this->parsePositivePrice($wp->getMeta('min_price')),
                'base_price' => $this->parsePositivePrice($wp->getMeta('base_price')),
            ];
        } catch (\Throwable $e) {
            return ['adult_price' => null, 'min_price' => null, 'base_price' => null];
        }
    }

    private function parsePositivePrice(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_int($raw) || is_float($raw)) {
            $value = (float) $raw;

            return $value > 0 ? $value : null;
        }

        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^[aOs]:/i', $value)) {
            $unserialized = @unserialize($value, ['allowed_classes' => false]);
            if (is_numeric($unserialized)) {
                $amount = (float) $unserialized;

                return $amount > 0 ? $amount : null;
            }
            if (is_array($unserialized)) {
                foreach (['price', 'adult_price', 'adult', 'value'] as $key) {
                    if (isset($unserialized[$key]) && is_numeric($unserialized[$key])) {
                        $amount = (float) $unserialized[$key];

                        return $amount > 0 ? $amount : null;
                    }
                }
            }

            return null;
        }

        $value = str_replace(["\xc2\xa0", ' ', 'MAD', 'DH', 'mad', 'dh'], '', $value);
        $value = str_replace(',', '.', $value);
        if (! is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;

        return $amount > 0 ? $amount : null;
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
                'departure_id' => ['Le d?part s?lectionn? est introuvable.'],
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
            ->filter(fn ($room) => !in_array($room->status ?? null, ['inactive', 'closed'], true))
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
                        'hotel_rooms' => ['Configuration incompl?te : ajoutez les chambres pour ce d?part.'],
                    ]);
                }

                if ((int) ($departure->available_capacity ?? 0) <= 0) {
                    throw ValidationException::withMessages([
                        'hotel_rooms' => ['Ce d?part n?a plus de places disponibles.'],
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
                'hotel_rooms' => ['S?lectionnez au moins une chambre pour continuer.'],
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
                    "hotel_rooms.$index.departure_hotel_room_id" => ['Chaque ligne chambre doit r?f?rencer une vraie chambre de d?part ou une chambre du catalogue.'],
                ]);
            }

            $room = $rooms->first(function ($candidate) use ($roomId) {
                return (int) data_get($candidate, 'id') === $roomId;
            });

            if (! $room) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.departure_hotel_room_id" => ['La chambre s?lectionn?e est introuvable pour ce d?part.'],
                ]);
            }

            $roomCount = max(0, (int) ($row['room_count'] ?? 0));
            $availableRooms = max(0, (int) data_get($room, 'available_rooms', 0));
            $availablePlaces = max(0, (int) data_get($room, 'available_places', 0));
            $capacity = max(1, (int) data_get($room, 'capacity_total', 1));
            $supplement = round(max(0, (float) data_get($room, 'supplement', 0)), 2);

            if ($roomCount > $availableRooms) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.room_count" => ['Le nombre de chambres demand? d?passe le stock disponible.'],
                ]);
            }

            $lineCapacity = $roomCount * $capacity;
            if ($lineCapacity > $availablePlaces) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.room_count" => ['Le nombre de places demand? d?passe le stock disponible pour cette chambre.'],
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
                'hotel_rooms' => ['La capacit? des chambres s?lectionn?es est insuffisante pour le nombre de voyageurs.'],
            ]);
        }

        if ((int) ($departure->available_capacity ?? 0) > 0 && $travelersCount > (int) $departure->available_capacity) {
            throw ValidationException::withMessages([
                'hotel_rooms' => ['Le nombre de voyageurs d?passe la capacit? disponible sur ce d?part.'],
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
            $voyageIds = [(int) $voyage->id];
            if ((int) ($voyage->wp_post_id ?? 0) > 0) {
                $siblingVoyageIds = Voyage::query()
                    ->where('wp_post_id', (int) $voyage->wp_post_id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $voyageIds = array_values(array_unique(array_merge($voyageIds, $siblingVoyageIds)));
            }

            $extras = VoyageExtra::query()
                ->whereIn('voyage_id', $voyageIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');
        }

        $details = [];
        $total = 0.0;
        $travelerTypes = $this->travelerTypesByKey($payload['passengers'] ?? []);

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $sourceType = (string) ($row['source_type'] ?? ($row['type'] ?? 'voyage_extra'));
            $extraId = (int) ($row['voyage_extra_id'] ?? 0);
            $sourceId = (int) ($row['source_id'] ?? 0);
            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $scope = (string) ($row['application_scope'] ?? 'dossier');
            $travelerKeys = is_array($row['traveler_keys'] ?? null) ? array_values(array_filter($row['traveler_keys'])) : [];

            if ($sourceType === 'activity') {
                if ($sourceId <= 0) {
                    throw ValidationException::withMessages([
                        "extras_json.$index.source_id" => ['Activité optionnelle invalide.'],
                    ]);
                }

                $dayActivity = TourDayActivity::query()
                    ->with('activity')
                    ->where('id', $sourceId)
                    ->where('tour_id', (int) ($voyage->wp_post_id ?? 0))
                    ->where('is_included', 0)
                    ->first();

                if (! $dayActivity || ! $dayActivity->activity) {
                    throw ValidationException::withMessages([
                        "extras_json.$index.source_id" => ['L’activité optionnelle sélectionnée n’est pas disponible pour ce voyage.'],
                    ]);
                }

                $activity = $dayActivity->activity;
                $adultPrice = round($dayActivity->custom_price !== null
                    ? (float) $dayActivity->custom_price
                    : (float) ($activity->adult_price ?? $activity->base_price ?? 0), 2);
                $childPrice = round((float) ($activity->child_price ?? 0), 2);
                if ($childPrice <= 0) {
                    $childPrice = $adultPrice;
                }
                $name = trim((string) ($dayActivity->custom_title ?: ($activity->title ?? 'Activité optionnelle')));
                $description = trim((string) ($dayActivity->custom_description ?: ($activity->description ?? 'Activité proposée au client comme option.')));

                if ($scope === 'traveler_selection') {
                    if ($travelerKeys === []) {
                        throw ValidationException::withMessages([
                            "extras_json.$index.traveler_keys" => ['Sélectionnez au moins un voyageur pour cet extra.'],
                        ]);
                    }

                    $lineTotal = round($quantity * collect($travelerKeys)->sum(function ($travelerKey) use ($travelerTypes, $adultPrice, $childPrice) {
                        return ($travelerTypes[(string) $travelerKey] ?? 'adult') === 'child' ? $childPrice : $adultPrice;
                    }), 2);
                } elseif ($scope === 'per_traveler') {
                    $lineTotal = round(collect($travelerTypes)->sum(fn ($type) => $type === 'child' ? $childPrice : $adultPrice), 2);
                    $quantity = max(1, count($travelerTypes));
                } else {
                    $lineTotal = round($quantity * max($adultPrice, 0), 2);
                }

                $total += $lineTotal;
                $details[] = [
                    'voyage_extra_id' => null,
                    'source_type' => 'activity',
                    'source_id' => $sourceId,
                    'name' => $name !== '' ? $name : 'Activité optionnelle',
                    'description' => $description,
                    'quantity' => $quantity,
                    'application_scope' => $scope,
                    'traveler_keys' => $travelerKeys,
                    'unit_price_adult' => $adultPrice,
                    'unit_price_child' => $childPrice,
                    'total_price' => $lineTotal,
                ];

                continue;
            }

            if ($extraId <= 0) {
                throw ValidationException::withMessages([
                    "extras_json.$index.voyage_extra_id" => ['Chaque extra doit r?f?rencer un extra actif du voyage.'],
                ]);
            }

            $extra = $extras->get($extraId);
            if (! $extra) {
                throw ValidationException::withMessages([
                    "extras_json.$index.voyage_extra_id" => ['L?extra s?lectionn? n?est pas disponible pour ce voyage.'],
                ]);
            }

            $adultPrice = round((float) ($extra->price_adult ?? 0), 2);
            $childPrice = round((float) ($extra->price_child ?? 0), 2);

            if ($scope === 'traveler_selection') {
                if ($travelerKeys === []) {
                    throw ValidationException::withMessages([
                        "extras_json.$index.traveler_keys" => ['S?lectionnez au moins un voyageur pour cet extra.'],
                    ]);
                }

                $lineTotal = round($quantity * collect($travelerKeys)->sum(function ($travelerKey) use ($travelerTypes, $adultPrice, $childPrice) {
                    if (($travelerTypes[(string) $travelerKey] ?? 'adult') === 'child') {
                        return $childPrice > 0 ? $childPrice : $adultPrice;
                    }

                    return $adultPrice;
                }), 2);
            } elseif ($scope === 'per_traveler') {
                $lineTotal = round(collect($travelerTypes)->sum(function ($type) use ($adultPrice, $childPrice) {
                    return $type === 'child' ? ($childPrice > 0 ? $childPrice : $adultPrice) : $adultPrice;
                }), 2);
                $quantity = max(1, count($travelerTypes));
            } else {
                $lineTotal = round($quantity * max($adultPrice, 0), 2);
            }

            $total += $lineTotal;
            $details[] = [
                'voyage_extra_id' => $extraId,
                'source_type' => 'voyage_extra',
                'source_id' => $extraId,
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

    /**
     * @return array<string, string>
     */
    private function travelerTypesByKey(mixed $passengers): array
    {
        $types = [];
        if (! is_iterable($passengers)) {
            return ['main' => 'adult'];
        }

        foreach ($passengers as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $firstName = trim((string) ($row['first_name'] ?? ''));
            $lastName = trim((string) ($row['last_name'] ?? ''));
            if ($firstName === '' && $lastName === '') {
                continue;
            }

            $travelerKey = (string) ($row['traveler_key'] ?? ($key === '__main' ? 'main' : $key));
            $type = (string) ($row['type'] ?? 'adult');
            $types[$travelerKey] = $type === 'child' ? 'child' : 'adult';
        }

        return $types !== [] ? $types : ['main' => 'adult'];
    }
}
