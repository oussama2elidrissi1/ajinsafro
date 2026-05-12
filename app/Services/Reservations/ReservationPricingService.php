<?php

namespace App\Services\Reservations;

use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Models\VoyageExtra;
use App\Models\Wp\WpPost;
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
                'tour_id' => ['Aucun prix n’a été trouvé pour ce voyage ou ce départ.'],
            ]);
        }

        $roomSummary = $this->resolveRoomSelection($payload, $departure, $travelersCount);
        $extrasSummary = $this->resolveExtras($payload, $voyage);
        $paidAmount = round(max(0, (float) ($payload['payment_amount'] ?? 0)), 2);

        $totalBase = round($basePrice * $travelersCount, 2);
        $totalAmount = round($totalBase + $roomSummary['room_supplement_total'] + $extrasSummary['extras_total'], 2);

        if ($paidAmount > $totalAmount + 0.009) {
            throw ValidationException::withMessages([
                'payment_amount' => ['Le montant payé ne peut pas dépasser le total du dossier.'],
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
        $voyage = $this->resolveVoyage($payload);
        $departure = $this->resolveDepartureForPreview($payload, $voyage);
        $travelersCount = max(1, $this->countTravelers($payload['passengers'] ?? []));
        $travelDate = $this->resolveTravelDate($payload, $departure);
        $unitPrice = $this->resolveBasePrice($payload, $voyage, $departure, $travelDate);

        if ($unitPrice <= 0) {
            throw ValidationException::withMessages([
                'tour_id' => ['Aucun prix configuré pour ce voyage ou ce départ.'],
            ]);
        }

        $departure->loadMissing(['departureHotels.rooms', 'roomAllocations']);
        $rooms = $departure->departureHotels
            ->filter(fn ($hotel) => (bool) ($hotel->is_active ?? false))
            ->flatMap(function ($hotel) {
                return $hotel->rooms->filter(fn ($room) => (int) ($room->is_active ?? 0) === 1);
            })
            ->values();

        $hasAssociatedHotels = $departure->departureHotels->filter(fn ($hotel) => (bool) ($hotel->is_active ?? false))->isNotEmpty();
        $mode = 'blocked';
        $message = null;
        $roomsPayload = [];

        if ($rooms->isNotEmpty()) {
            $mode = 'rooms';
            $roomsPayload = $departure->departureHotels
                ->filter(fn ($hotel) => (bool) ($hotel->is_active ?? false))
                ->map(function ($hotel) {
                    return [
                        'departure_hotel_id' => (int) $hotel->id,
                        'hotel_name' => $hotel->hotel_name ?: 'Hôtel',
                        'rooms' => $hotel->rooms
                            ->filter(fn ($room) => (int) ($room->is_active ?? 0) === 1)
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
            $mode = 'places_only';
        } else {
            // Fallback: try to use WP tour hotels / room availabilities for this travel_date
            try {
                $wpId = $voyage->wp_post_id ?? null;
                if ($wpId && $travelDate?->id) {
                    $tourHotels = \App\Models\TourHotel::getAllForTour($wpId)->load(['rooms', 'roomAvailabilities']);
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
                                'hotel_name' => (string) ($hotel->hotel_name ?? 'Hôtel'),
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
                            'hotel_name' => (string) ($hotel->hotel_name ?? 'Hôtel'),
                            'rooms' => $rooms,
                        ] : null;
                    })->filter()->values()->all();

                    if (! empty($wpHotels)) {
                        $mode = 'rooms';
                        $roomsPayload = $wpHotels;
                    }
                }
            } catch (\Throwable $e) {
                // ignore and keep previous mode
            }

            if ($mode !== 'rooms') {
                if ($hasAssociatedHotels) {
                    $message = 'Configuration incomplète : des hôtels sont liés à ce départ mais aucune chambre n’est configurée.';
                } else {
                    $message = 'Ce départ n’a plus de places disponibles.';
                }
            }
        }

        $totalBase = round($unitPrice * $travelersCount, 2);

        return [
            'success' => true,
            'mode' => $mode,
            'message' => $message,
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

        $count = 1;
        foreach ($passengers as $row) {
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
                'tour_id' => ['Le voyage sélectionné est introuvable.'],
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
                'departure_id' => ['Le départ sélectionné est introuvable.'],
            ]);
        }
        if ((int) $departure->voyage_id !== (int) $voyage->id) {
            throw ValidationException::withMessages([
                'departure_id' => ['Le départ sélectionné ne correspond pas au voyage demandé.'],
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
            if ($departure) {
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
                'departure_id' => ['Le départ sélectionné est introuvable.'],
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
        $selectedRows = collect(is_array($payload['hotel_rooms'] ?? null) ? $payload['hotel_rooms'] : [])
            ->filter(fn ($row) => is_array($row) && (int) ($row['room_count'] ?? 0) > 0)
            ->values();

        $departure->loadMissing(['departureHotels.rooms']);
        $rooms = $departure->departureHotels
            ->flatMap(fn ($hotel) => $hotel->rooms)
            ->filter(fn ($room) => (int) ($room->is_active ?? 0) === 1)
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
                        'hotel_rooms' => ['Configuration incomplète : ajoutez les chambres pour ce départ.'],
                    ]);
                }

                if ((int) ($departure->available_capacity ?? 0) <= 0) {
                    throw ValidationException::withMessages([
                        'hotel_rooms' => ['Ce départ n’a plus de places disponibles.'],
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
                'hotel_rooms' => ['Sélectionnez au moins une chambre pour continuer.'],
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
                    "hotel_rooms.$index.departure_hotel_room_id" => ['Chaque ligne chambre doit référencer une vraie chambre de départ ou une chambre du catalogue.'],
                ]);
            }

            $room = $rooms->first(function ($candidate) use ($roomId) {
                return (int) data_get($candidate, 'id') === $roomId;
            });

            if (! $room) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.departure_hotel_room_id" => ['La chambre sélectionnée est introuvable pour ce départ.'],
                ]);
            }

            $roomCount = max(0, (int) ($row['room_count'] ?? 0));
            $availableRooms = max(0, (int) data_get($room, 'available_rooms', 0));
            $availablePlaces = max(0, (int) data_get($room, 'available_places', 0));
            $capacity = max(1, (int) data_get($room, 'capacity_total', 1));
            $supplement = round(max(0, (float) data_get($room, 'supplement', 0)), 2);

            if ($roomCount > $availableRooms) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.room_count" => ['Le nombre de chambres demandé dépasse le stock disponible.'],
                ]);
            }

            $lineCapacity = $roomCount * $capacity;
            if ($lineCapacity > $availablePlaces) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.room_count" => ['Le nombre de places demandé dépasse le stock disponible pour cette chambre.'],
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
                'hotel_rooms' => ['La capacité des chambres sélectionnées est insuffisante pour le nombre de voyageurs.'],
            ]);
        }

        if ((int) ($departure->available_capacity ?? 0) > 0 && $travelersCount > (int) $departure->available_capacity) {
            throw ValidationException::withMessages([
                'hotel_rooms' => ['Le nombre de voyageurs dépasse la capacité disponible sur ce départ.'],
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
                    "extras_json.$index.voyage_extra_id" => ['Chaque extra doit référencer un extra actif du voyage.'],
                ]);
            }

            $extra = $extras->get($extraId);
            if (! $extra) {
                throw ValidationException::withMessages([
                    "extras_json.$index.voyage_extra_id" => ['L’extra sélectionné n’est pas disponible pour ce voyage.'],
                ]);
            }

            $adultPrice = round((float) ($extra->price_adult ?? 0), 2);
            $childPrice = round((float) ($extra->price_child ?? 0), 2);

            if ($scope === 'traveler_selection') {
                if ($travelerKeys === []) {
                    throw ValidationException::withMessages([
                        "extras_json.$index.traveler_keys" => ['Sélectionnez au moins un voyageur pour cet extra.'],
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
