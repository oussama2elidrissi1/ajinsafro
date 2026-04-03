<?php

namespace App\Services\Booking;

use App\Models\Reservation;

/**
 * Orchestration des transitions réservation ↔ stock départ.
 */
class ReservationLifecycleService
{
    public function __construct(
        private readonly DepartureRoomStockService $roomStock,
    ) {}

    public function releaseBeforeMutation(Reservation $reservation, ?int $userId = null): void
    {
        $this->roomStock->releaseReservationCommitment(
            $reservation,
            $userId,
            'before_reservation_mutation'
        );
    }

    public function commitAfterPersist(Reservation $reservation, ?int $userId = null): void
    {
        $this->roomStock->commitReservationIfApplicable(
            $reservation,
            $userId,
            'after_reservation_persist'
        );
    }

    public function validateAvailabilityIfNeeded(Reservation $reservation): void
    {
        $this->roomStock->assertAvailabilityForLines($reservation);
    }
}
