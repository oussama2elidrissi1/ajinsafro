<?php

namespace Tests\Unit;

use App\Models\AgentCommissionEntry;
use App\Models\Reservation;
use App\Services\AgentCommissionService;
use Tests\TestCase;

class AgentCommissionServiceTest extends TestCase
{
    public function test_it_marks_paid_reservations_as_payable(): void
    {
        $service = new AgentCommissionService;
        $reservation = new Reservation([
            'status' => Reservation::STATUS_CONFIRMED,
            'payment_status' => Reservation::PAYMENT_STATUS_PAID,
        ]);

        $this->assertSame(
            AgentCommissionEntry::STATUS_PAYABLE,
            $service->resolveStatusForReservation($reservation)
        );
    }

    public function test_it_reverses_paid_commissions_when_reservation_is_cancelled(): void
    {
        $service = new AgentCommissionService;
        $reservation = new Reservation([
            'status' => Reservation::STATUS_CANCELLED,
            'payment_status' => Reservation::PAYMENT_STATUS_PAID,
        ]);
        $entry = new AgentCommissionEntry([
            'commission_status' => AgentCommissionEntry::STATUS_PAID,
        ]);

        $this->assertSame(
            AgentCommissionEntry::STATUS_REVERSED,
            $service->resolveStatusForReservation($reservation, $entry)
        );
    }

    public function test_it_does_not_downgrade_a_payable_commission_back_to_confirmed(): void
    {
        $service = new AgentCommissionService;
        $reservation = new Reservation([
            'status' => Reservation::STATUS_CONFIRMED,
            'payment_status' => Reservation::PAYMENT_STATUS_PARTIAL,
        ]);
        $entry = new AgentCommissionEntry([
            'commission_status' => AgentCommissionEntry::STATUS_PAYABLE,
        ]);

        $this->assertSame(
            AgentCommissionEntry::STATUS_PAYABLE,
            $service->resolveStatusForReservation($reservation, $entry)
        );
    }
}
