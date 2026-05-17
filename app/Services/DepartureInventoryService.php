<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voyage;
use App\Services\Reservations\ReservationPricingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DepartureInventoryService
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
        private readonly ReservationPricingService $reservationPricing,
    ) {}

    /**
     * @param  array<int, int>  $travelDateIds
     * @return array<int, array<string, mixed>>
     */
    public function buildForTravelDates(User $user, Voyage $voyage, array $travelDateIds): array
    {
        $travelDateIds = array_values(array_unique(array_filter(array_map('intval', $travelDateIds), fn (int $id) => $id > 0)));
        if ($travelDateIds === []) {
            return [];
        }

        $tourId = (int) ($voyage->id ?? 0);
        if ($tourId <= 0) {
            return [];
        }

        $departureMap = Departure::query()
            ->where('voyage_id', $tourId)
            ->whereIn('wp_travel_date_id', $travelDateIds)
            ->get()
            ->keyBy(fn (Departure $departure) => (int) ($departure->wp_travel_date_id ?? 0));

        $rows = [];
        foreach ($travelDateIds as $travelDateId) {
            $rows[$travelDateId] = [
                'travel_date_id' => $travelDateId,
                'departure_id' => (int) optional($departureMap->get($travelDateId))->id ?: null,
                'capacity_total' => 0,
                'confirmed_places' => 0,
                'pending_places' => 0,
                'cancelled_places' => 0,
                'consumed_places' => 0,
                'remaining_places' => 0,
                'occupancy_rate' => null,
                'rooms_total_by_type' => [],
                'rooms_used_by_type' => [],
                'rooms_remaining_by_type' => [],
                'reservations_count_confirmed' => 0,
                'reservations_count_pending' => 0,
                'reservations_count_cancelled' => 0,
                'room_lines' => [],
                'alerts' => [],
                'capacity_note' => optional($departureMap->get($travelDateId))->id ? 'Aucune chambre configurée' : 'Départ non synchronisé',
                'status_key' => 'unknown',
                'status_label' => optional($departureMap->get($travelDateId))->id ? 'Aucune chambre configurée' : 'Départ non synchronisé',
                'reservations' => [],
            ];
        }

        $reservationQuery = Reservation::query()
            ->whereIn('tour_id', Voyage::allIdsSharingWpTour($tourId))
            ->whereIn('travel_date_id', $travelDateIds)
            ->with([
                'reservationRooms:id,reservation_id,departure_hotel_room_id,room_type_snapshot,room_count,passenger_count',
            ])
            ->withCount(['passengers as passengers_records_count']);

        $this->branchScope->scopeReservations($reservationQuery, $user, [
            'tour_id' => $tourId,
        ]);

        foreach ($reservationQuery->get(['id', 'travel_date_id', 'status', 'passengers_count']) as $reservation) {
            $travelDateId = (int) ($reservation->travel_date_id ?? 0);
            if (! isset($rows[$travelDateId])) {
                continue;
            }

            $status = (string) ($reservation->status ?? '');
            $travelersCount = max(0, (int) ($reservation->passengers_count ?? 0));
            if ($travelersCount <= 0) {
                $travelersCount = max(0, (int) ($reservation->passengers_records_count ?? 0));
            }
            $roomsAssignedCount = $reservation->reservationRooms->sum(fn ($room) => max(0, (int) ($room->room_count ?? 0)));

            $rows[$travelDateId]['reservations'][] = [
                'id' => (int) $reservation->id,
                'status' => $status,
                'travelers_count' => $travelersCount,
                'rooms_assigned_count' => $roomsAssignedCount,
            ];

            if ($this->isConfirmedStatus($status)) {
                $rows[$travelDateId]['confirmed_places'] += $travelersCount;
                $rows[$travelDateId]['reservations_count_confirmed']++;
            } elseif ($this->isPendingStatus($status)) {
                $rows[$travelDateId]['pending_places'] += $travelersCount;
                $rows[$travelDateId]['reservations_count_pending']++;
            } elseif ($this->isCancelledStatus($status)) {
                $rows[$travelDateId]['cancelled_places'] += $travelersCount;
                $rows[$travelDateId]['reservations_count_cancelled']++;
            }

            if (! $this->isActiveStatus($status)) {
                continue;
            }

            if ($roomsAssignedCount <= 0) {
                $rows[$travelDateId]['alerts']['rooming_missing'] = 'Réservations sans affectation chambre';
            }

            foreach ($reservation->reservationRooms as $reservationRoom) {
                $roomCount = max(0, (int) ($reservationRoom->room_count ?? 0));
                if ($roomCount <= 0) {
                    continue;
                }

                $roomType = trim((string) ($reservationRoom->room_type_snapshot ?? ''));
                if ($roomType === '') {
                    $roomType = 'Chambre';
                }

                $roomKey = Str::lower($roomType);
                if (! isset($rows[$travelDateId]['rooms_used_by_type'][$roomKey])) {
                    $rows[$travelDateId]['rooms_used_by_type'][$roomKey] = [
                        'type' => $roomType,
                        'count' => 0,
                    ];
                }
                $rows[$travelDateId]['rooms_used_by_type'][$roomKey]['count'] += $roomCount;
            }
        }

        foreach ($rows as $travelDateId => $inventory) {
            $roomPreview = $this->resolveRoomPreview($tourId, $travelDateId, $inventory['departure_id']);
            $roomLines = $this->buildRoomLines(
                $roomPreview['room_lines'],
                $inventory['rooms_used_by_type']
            );

            $roomsTotalByType = [];
            $roomsRemainingByType = [];
            foreach ($roomLines as $roomLine) {
                $roomsTotalByType[$roomLine['type']] = (int) ($roomLine['total_rooms'] ?? 0);
                $roomsRemainingByType[$roomLine['type']] = (int) ($roomLine['remaining_rooms'] ?? 0);
            }

            $capacityTotal = array_sum(array_map(
                fn (array $roomLine): int => (int) ($roomLine['total_places'] ?? 0),
                $roomLines
            ));
            $confirmedPlaces = (int) $inventory['confirmed_places'];
            $pendingPlaces = (int) $inventory['pending_places'];
            $consumedPlaces = $confirmedPlaces + $pendingPlaces;
            $remainingPlaces = $capacityTotal > 0 ? max(0, $capacityTotal - $consumedPlaces) : 0;
            $occupancyRate = $capacityTotal > 0
                ? min(100, (int) round(($consumedPlaces / $capacityTotal) * 100))
                : null;

            $capacityNote = $inventory['capacity_note'];
            if ($roomLines !== []) {
                $capacityNote = null;
            }

            [$statusKey, $statusLabel] = $this->resolveStatus($capacityTotal, $remainingPlaces, $capacityNote);

            $rows[$travelDateId] = array_merge($inventory, [
                'capacity_total' => $capacityTotal,
                'consumed_places' => $consumedPlaces,
                'remaining_places' => $remainingPlaces,
                'occupancy_rate' => $occupancyRate,
                'rooms_total_by_type' => $roomsTotalByType,
                'rooms_used_by_type' => $this->formatRoomCountMap($inventory['rooms_used_by_type']),
                'rooms_remaining_by_type' => $roomsRemainingByType,
                'room_lines' => $roomLines,
                'alerts' => array_values($inventory['alerts']),
                'capacity_note' => $capacityNote,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
            ]);

            Log::info('Departure inventory debug', [
                'tour_id' => $tourId,
                'travel_date_id' => $travelDateId,
                'capacity_total' => $capacityTotal,
                'confirmed_places' => $confirmedPlaces,
                'pending_places' => $pendingPlaces,
                'remaining_places' => $remainingPlaces,
                'rooms_total' => $roomsTotalByType,
                'rooms_used' => $this->formatRoomCountMap($inventory['rooms_used_by_type']),
                'rooms_remaining' => $roomsRemainingByType,
                'reservations' => $rows[$travelDateId]['reservations'],
            ]);
        }

        return $rows;
    }

    private function isConfirmedStatus(string $status): bool
    {
        return in_array($status, [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_PARTIALLY_PAID,
            Reservation::STATUS_PAID,
            Reservation::STATUS_SHARED_ROOM_PAIRED,
        ], true);
    }

    private function isPendingStatus(string $status): bool
    {
        return in_array($status, [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_OPTION,
            Reservation::STATUS_SHARED_ROOM_PENDING,
        ], true);
    }

    private function isCancelledStatus(string $status): bool
    {
        return in_array($status, [
            Reservation::STATUS_CANCELLED,
            Reservation::STATUS_EXPIRED,
            Reservation::STATUS_REFUNDED,
            'refused',
            'deleted',
        ], true);
    }

    private function isActiveStatus(string $status): bool
    {
        return $this->isConfirmedStatus($status) || $this->isPendingStatus($status);
    }

    /**
     * @return array{room_lines: array<int, array<string, mixed>>}
     */
    private function resolveRoomPreview(int $tourId, int $travelDateId, ?int $departureId): array
    {
        try {
            $preview = $this->reservationPricing->previewDepartureRooms([
                'tour_id' => $tourId,
                'travel_date_id' => $travelDateId,
                'departure_id' => $departureId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Departure inventory room preview failed', [
                'tour_id' => $tourId,
                'travel_date_id' => $travelDateId,
                'departure_id' => $departureId,
                'message' => $e->getMessage(),
            ]);

            return ['room_lines' => []];
        }

        $roomLines = [];
        foreach ((array) ($preview['rooms'] ?? []) as $group) {
            if (! is_array($group)) {
                continue;
            }

            $hotelName = (string) ($group['hotel_name'] ?? '');
            $groupRooms = isset($group['rooms']) && is_array($group['rooms'])
                ? $group['rooms']
                : [$group];

            foreach ($groupRooms as $room) {
                if (! is_array($room)) {
                    continue;
                }

                $type = trim((string) ($room['room_type'] ?? $room['type'] ?? 'Chambre'));
                if ($type === '') {
                    $type = 'Chambre';
                }

                $roomKey = Str::lower($type);
                $totalRooms = max(0, (int) ($room['total_rooms'] ?? $room['quantity'] ?? $room['available_rooms'] ?? 0));
                $capacityPerRoom = max(0, (int) ($room['capacity_per_room'] ?? $room['capacity_total'] ?? $room['capacity'] ?? 0));
                $totalPlaces = max(0, (int) ($room['total_places'] ?? ($totalRooms * $capacityPerRoom)));
                $supplement = (float) ($room['supplement'] ?? $room['unit_supplement'] ?? 0);

                if (! isset($roomLines[$roomKey])) {
                    $roomLines[$roomKey] = [
                        'type' => $type,
                        'room_type' => $type,
                        'hotel_name' => $hotelName,
                        'quantity' => 0,
                        'total_rooms' => 0,
                        'capacity_per_room' => $capacityPerRoom,
                        'supplement' => $supplement,
                        'total_places' => 0,
                    ];
                }

                $roomLines[$roomKey]['quantity'] += $totalRooms;
                $roomLines[$roomKey]['total_rooms'] += $totalRooms;
                $roomLines[$roomKey]['total_places'] += $totalPlaces;
                $roomLines[$roomKey]['capacity_per_room'] = max($roomLines[$roomKey]['capacity_per_room'], $capacityPerRoom);
                $roomLines[$roomKey]['supplement'] = max((float) $roomLines[$roomKey]['supplement'], $supplement);
                if ($roomLines[$roomKey]['hotel_name'] === '' && $hotelName !== '') {
                    $roomLines[$roomKey]['hotel_name'] = $hotelName;
                }
            }
        }

        return [
            'room_lines' => array_values($roomLines),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $previewLines
     * @param  array<string, array{type: string, count: int}>  $usedRoomsByType
     * @return array<int, array<string, mixed>>
     */
    private function buildRoomLines(array $previewLines, array $usedRoomsByType): array
    {
        $roomLines = [];
        foreach ($previewLines as $roomLine) {
            $type = (string) ($roomLine['type'] ?? $roomLine['room_type'] ?? 'Chambre');
            $roomKey = Str::lower($type);
            $totalRooms = max(0, (int) ($roomLine['total_rooms'] ?? $roomLine['quantity'] ?? 0));
            $capacityPerRoom = max(0, (int) ($roomLine['capacity_per_room'] ?? 0));
            $totalPlaces = max(0, (int) ($roomLine['total_places'] ?? ($totalRooms * $capacityPerRoom)));
            $usedRooms = max(0, (int) data_get($usedRoomsByType, $roomKey.'.count', 0));
            $remainingRooms = max(0, $totalRooms - $usedRooms);
            $remainingPlaces = max(0, $remainingRooms * $capacityPerRoom);
            $usedPlaces = max(0, $totalPlaces - $remainingPlaces);

            $roomLines[] = [
                'type' => $type,
                'room_type' => $type,
                'hotel_name' => (string) ($roomLine['hotel_name'] ?? ''),
                'quantity' => $totalRooms,
                'total_rooms' => $totalRooms,
                'capacity_per_room' => $capacityPerRoom,
                'supplement' => (float) ($roomLine['supplement'] ?? 0),
                'used_rooms' => $usedRooms,
                'remaining_rooms' => $remainingRooms,
                'total_places' => $totalPlaces,
                'used_places' => $usedPlaces,
                'remaining_places' => $remainingPlaces,
                'status' => $remainingRooms <= 0 ? 'full' : 'available',
            ];
        }

        return $roomLines;
    }

    /**
     * @param  array<string, array{type: string, count: int}>  $roomCountMap
     * @return array<string, int>
     */
    private function formatRoomCountMap(array $roomCountMap): array
    {
        $formatted = [];
        foreach ($roomCountMap as $row) {
            $formatted[(string) ($row['type'] ?? 'Chambre')] = (int) ($row['count'] ?? 0);
        }

        return $formatted;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveStatus(int $capacityTotal, int $remainingPlaces, ?string $capacityNote): array
    {
        if ($capacityTotal <= 0) {
            return ['unknown', $capacityNote ?: 'Aucune capacité'];
        }

        if ($remainingPlaces <= 0) {
            return ['full', 'Complet'];
        }

        $threshold = max(1, (int) config('booking_lifecycle.departure_limited_threshold_places', 5));
        if ($remainingPlaces <= $threshold) {
            return ['almost_full', 'Presque complet'];
        }

        return ['available', 'Disponible'];
    }
}
