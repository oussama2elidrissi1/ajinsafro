<?php

namespace App\Services;

use App\Models\Reservation;
use App\Services\Booking\DepartureRoomStockService;

/**
 * Façade rétrocompatible — la logique métier est dans {@see DepartureRoomStockService}.
 */
class DepartureStockService
{
    public function __construct(
        private readonly DepartureRoomStockService $roomStock,
    ) {}

    public function releaseReservationStock(Reservation $reservation): void
    {
        $this->roomStock->releaseReservationCommitment($reservation, null, 'departure_stock_service_release');
    }

    public function applyReservationStock(Reservation $reservation): void
    {
        $this->roomStock->commitReservationIfApplicable($reservation, null, 'departure_stock_service_apply');
    }

    public function assertEnoughStockForReservation(Reservation $reservation): void
    {
        $this->roomStock->assertAvailabilityForLines($reservation);
    }
}
