<?php

namespace App\Services\Booking;

use App\Models\Departure;
use App\Models\DepartureHotelRoom;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stock par départ / hôtel / type de chambre : total, réservé, disponible.
 * Les mouvements sont journalisés dans {@see StockMovement}.
 */
class DepartureRoomStockService
{
    public function __construct(
        private readonly DepartureLifecycleService $departureLifecycle,
    ) {}

    public function statusConsumesStock(string $status): bool
    {
        $hold = config('booking_lifecycle.option_holds_stock', false)
            && in_array($status, config('booking_lifecycle.stock_hold_statuses', []), true);

        return $hold || in_array($status, config('booking_lifecycle.stock_consuming_statuses', []), true);
    }

    public function statusReleasesStock(string $status): bool
    {
        if (config('booking_lifecycle.refund_releases_stock', true) && $status === 'refunded') {
            return true;
        }

        return in_array($status, config('booking_lifecycle.stock_release_statuses', []), true);
    }

    /**
     * Libère le stock précédemment engagé pour cette réservation (statut consommateur).
     */
    public function releaseReservationCommitment(Reservation $reservation, ?int $userId, string $reason): void
    {
        if (! $this->statusConsumesStock((string) $reservation->status)) {
            return;
        }

        $lines = ReservationRoom::query()
            ->where('reservation_id', $reservation->id)
            ->whereNotNull('departure_hotel_room_id')
            ->get();

        if ($lines->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($lines, $reservation, $userId, $reason) {
            foreach ($lines as $line) {
                $this->applyDelta(
                    (int) $line->departure_hotel_room_id,
                    $reservation,
                    -((int) $line->room_count),
                    StockMovement::TYPE_RELEASE,
                    $userId,
                    $reason
                );
            }
            $reservation->departure_id && Departure::query()->find($reservation->departure_id)
                ?->recomputeAvailableCapacity(true);
            $this->departureLifecycle->recomputeDepartureAggregates((int) $reservation->departure_id);
        });
    }

    /**
     * Engage le stock pour les lignes courantes si le statut consomme.
     */
    public function commitReservationIfApplicable(Reservation $reservation, ?int $userId, string $reason): void
    {
        if (! $this->statusConsumesStock((string) $reservation->status)) {
            return;
        }

        $lines = ReservationRoom::query()
            ->where('reservation_id', $reservation->id)
            ->whereNotNull('departure_hotel_room_id')
            ->get();

        if ($lines->isEmpty()) {
            return;
        }

        $this->assertLinesFitInventory($reservation, $lines);

        DB::transaction(function () use ($lines, $reservation, $userId, $reason) {
            foreach ($lines as $line) {
                $this->applyDelta(
                    (int) $line->departure_hotel_room_id,
                    $reservation,
                    (int) $line->room_count,
                    StockMovement::TYPE_CONSUME,
                    $userId,
                    $reason
                );
            }
            $reservation->departure_id && Departure::query()->find($reservation->departure_id)
                ?->recomputeAvailableCapacity(true);
            $this->departureLifecycle->recomputeDepartureAggregates((int) $reservation->departure_id);
        });
    }

    /**
     * Vérifie la disponibilité sans muter (réservations draft / pending).
     */
    public function assertAvailabilityForLines(Reservation $reservation, $lines = null): void
    {
        $lines ??= ReservationRoom::query()
            ->where('reservation_id', $reservation->id)
            ->whereNotNull('departure_hotel_room_id')
            ->get();

        $this->assertLinesFitInventory($reservation, $lines);
    }

    private function assertLinesFitInventory(Reservation $reservation, $lines): void
    {
        foreach ($lines as $line) {
            $dhrId = (int) $line->departure_hotel_room_id;
            $need = (int) $line->room_count;
            if ($need < 1) {
                continue;
            }

            $dhr = DepartureHotelRoom::query()->lockForUpdate()->find($dhrId);
            if (! $dhr) {
                throw ValidationException::withMessages(['hotel_rooms' => ['Type de chambre départ introuvable.']]);
            }

            $cap = max(1, (int) $dhr->capacity_total);
            $pendingOthers = $this->pendingRoomDemandForDhrExcluding($dhrId, (int) $reservation->id);
            $freeRooms = max(0, (int) $dhr->total_rooms - (int) $dhr->reserved_rooms - $pendingOthers);
            $pendingPlaces = $pendingOthers * $cap;
            $freePlaces = max(0, (int) $dhr->total_places - (int) $dhr->reserved_places - $pendingPlaces);

            if ($need > $freeRooms) {
                throw ValidationException::withMessages([
                    'hotel_rooms' => ["Stock insuffisant pour « {$dhr->room_type} » : {$need} chambre(s) demandée(s), {$freeRooms} libre(s) (hors engagements confirmés et autres dossiers en attente)."],
                ]);
            }
            if ($need * $cap > $freePlaces) {
                throw ValidationException::withMessages([
                    'hotel_rooms' => ['Places insuffisantes pour « '.$dhr->room_type.' ».'],
                ]);
            }
        }
    }

    /**
     * Chambres « bloquées » par des réservations en attente (pending/draft) — hors la réservation courante.
     */
    public function pendingRoomDemandForDhrExcluding(int $departureHotelRoomId, ?int $excludeReservationId): int
    {
        $neutral = config('booking_lifecycle.stock_neutral_statuses', ['pending', 'draft']);

        $q = ReservationRoom::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->where('reservation_rooms.departure_hotel_room_id', $departureHotelRoomId)
            ->whereIn('reservations.status', $neutral);

        if ($excludeReservationId) {
            $q->where('reservations.id', '!=', $excludeReservationId);
        }

        return (int) $q->sum('reservation_rooms.room_count');
    }

    private function applyDelta(
        int $departureHotelRoomId,
        Reservation $reservation,
        int $roomDeltaSigned,
        string $movementType,
        ?int $userId,
        string $reason
    ): void {
        if ($roomDeltaSigned === 0) {
            return;
        }

        $dhr = DepartureHotelRoom::query()->lockForUpdate()->find($departureHotelRoomId);
        if (! $dhr) {
            return;
        }

        $departure = $dhr->departureHotel?->departure;
        $departureId = (int) ($departure?->id ?? $reservation->departure_id ?? 0);

        $cap = max(1, (int) $dhr->capacity_total);
        $placesDelta = $roomDeltaSigned * $cap;

        $before = $this->snapshotDhr($dhr);

        $reservedRooms = (int) $dhr->reserved_rooms;
        $reservedPlaces = (int) $dhr->reserved_places;

        if ($roomDeltaSigned > 0) {
            $reservedRooms += $roomDeltaSigned;
            $reservedPlaces += $placesDelta;
        } else {
            $release = -$roomDeltaSigned;
            $reservedRooms = max(0, $reservedRooms - $release);
            $reservedPlaces = max(0, $reservedPlaces - ($release * $cap));
        }

        $totalRooms = max(0, (int) $dhr->total_rooms);
        $totalPlaces = max(0, (int) $dhr->total_places);

        if ($reservedRooms > $totalRooms) {
            throw ValidationException::withMessages([
                'hotel_rooms' => ['Dépassement de stock (chambres) — opération refusée.'],
            ]);
        }
        if ($reservedPlaces > $totalPlaces) {
            throw ValidationException::withMessages([
                'hotel_rooms' => ['Dépassement de stock (places) — opération refusée.'],
            ]);
        }

        $dhr->reserved_rooms = $reservedRooms;
        $dhr->reserved_places = $reservedPlaces;
        $dhr->save();

        $this->refreshDerivedAvailability($dhr->fresh());

        $dhr = $dhr->fresh();

        $this->departureLifecycle->recalculateRoomRowStatus($dhr);

        $after = $this->snapshotDhr($dhr->fresh());

        StockMovement::query()->create([
            'reservation_id' => $reservation->id,
            'departure_id' => $departureId,
            'departure_hotel_room_id' => $dhr->id,
            'movement_type' => $movementType,
            'rooms_delta' => $roomDeltaSigned,
            'places_delta' => $placesDelta,
            'before_state' => $before,
            'after_state' => $after,
            'reason' => $reason,
            'created_by' => $userId,
        ]);
    }

    /**
     * Disponible = total − réservé confirmé − demande des dossiers en attente (pending/draft).
     */
    public function refreshDerivedAvailability(DepartureHotelRoom $dhr): void
    {
        $pending = $this->pendingRoomDemandForDhrExcluding((int) $dhr->id, null);
        $cap = max(1, (int) $dhr->capacity_total);
        $pendingPlaces = $pending * $cap;

        $dhr->available_rooms = max(0, (int) $dhr->total_rooms - (int) $dhr->reserved_rooms - $pending);
        $dhr->available_places = max(0, (int) $dhr->total_places - (int) $dhr->reserved_places - $pendingPlaces);
        $dhr->save();
    }

    public function refreshDerivedForEntireDeparture(int $departureId): void
    {
        DepartureHotelRoom::query()
            ->whereHas('departureHotel', fn ($q) => $q->where('departure_id', $departureId))
            ->orderBy('id')
            ->each(function (DepartureHotelRoom $dhr) {
                $this->refreshDerivedAvailability($dhr->fresh());
                $this->departureLifecycle->recalculateRoomRowStatus($dhr->fresh());
            });

        Departure::query()->find($departureId)?->recomputeAvailableCapacity(true);
        $this->departureLifecycle->recomputeDepartureAggregates($departureId);
    }

    private function snapshotDhr(DepartureHotelRoom $d): array
    {
        return [
            'total_rooms' => (int) $d->total_rooms,
            'reserved_rooms' => (int) $d->reserved_rooms,
            'available_rooms' => (int) $d->available_rooms,
            'total_places' => (int) $d->total_places,
            'reserved_places' => (int) $d->reserved_places,
            'available_places' => (int) $d->available_places,
            'status' => $d->status,
        ];
    }
}
