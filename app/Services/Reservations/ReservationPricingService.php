<?php

namespace App\Services\Reservations;

use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\Voyage;
use App\Models\VoyageExtra;
use Illuminate\Support\Collection;
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
        $travelersCount = $this->countTravelers($payload['passengers'] ?? []);
        $basePrice = $this->resolveBasePrice($payload, $voyage, $departure);

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
                    'travel_date_id' => (int) ($departure->wp_travel_date_id ?? ($payload['travel_date_id'] ?? 0)),
                    'base_price' => $departure->base_price !== null ? (float) $departure->base_price : null,
                    'sale_price' => $departure->sale_price !== null ? (float) $departure->sale_price : null,
                    'available_capacity' => (int) ($departure->available_capacity ?? 0),
                ],
                'rooms' => $roomSummary['details'],
                'extras' => $extrasSummary['details'],
            ],
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
    private function resolveBasePrice(array $payload, Voyage $voyage, Departure $departure): float
    {
        $candidates = [
            $departure->sale_price,
            $departure->base_price,
            $voyage->price_from,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && (float) $candidate > 0) {
                return round((float) $candidate, 2);
            }
        }

        return 0.0;
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

        $configuredRooms = $payload['departure_hotel_rooms'] ?? null;
        if ($configuredRooms instanceof Collection) {
            $rooms = $configuredRooms;
        } elseif (is_array($configuredRooms)) {
            $rooms = collect($configuredRooms);
        } else {
            $rooms = DepartureHotelRoom::query()
                ->whereHas('departureHotel', fn ($query) => $query->where('departure_id', $departure->id))
                ->get();
        }

        if ($rooms->isEmpty()) {
            throw ValidationException::withMessages([
                'hotel_rooms' => ['Aucune chambre configurée pour ce départ.'],
            ]);
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
            if ($roomId <= 0) {
                throw ValidationException::withMessages([
                    "hotel_rooms.$index.departure_hotel_room_id" => ['Chaque ligne chambre doit référencer une vraie chambre de départ.'],
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
