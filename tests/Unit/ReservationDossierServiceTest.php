<?php

namespace Tests\Unit;

use App\Models\Reservation;
use App\Services\ReservationDossierService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReservationDossierServiceTest extends TestCase
{
    public function test_it_computes_total_for_one_traveler_without_extras(): void
    {
        $service = new ReservationDossierService;

        $summary = $service->computeFinancialSummary(1200, 0, 0, 0);

        $this->assertSame(1200.0, $summary['total_amount']);
        $this->assertSame(0.0, $summary['paid_amount']);
        $this->assertSame(1200.0, $summary['remaining_amount']);
        $this->assertSame(ReservationDossierService::PAYMENT_UNPAID, $summary['payment_status']);
    }

    public function test_it_computes_total_for_two_travelers_with_double_room_supplement(): void
    {
        $service = new ReservationDossierService;

        $summary = $service->computeFinancialSummary(2400, 300, 0, 0);

        $this->assertSame(2400.0, $summary['total_base']);
        $this->assertSame(300.0, $summary['room_supplement_total']);
        $this->assertSame(2700.0, $summary['total_amount']);
    }

    public function test_it_computes_extras_total_for_dossier_and_traveler_selection(): void
    {
        $service = new ReservationDossierService;

        $total = $service->computeExtrasTotalFromPayload([
            [
                'unit_price' => 100,
                'quantity' => 2,
                'application_scope' => 'dossier',
            ],
            [
                'unit_price' => 50,
                'quantity' => 1,
                'application_scope' => 'traveler_selection',
                'traveler_keys' => ['principal', 'companion_1'],
            ],
        ]);

        $this->assertSame(300.0, $total);
    }

    public function test_it_marks_partial_payment_and_remaining_amount(): void
    {
        $service = new ReservationDossierService;

        $summary = $service->computeFinancialSummary(2000, 250, 150, 600);

        $this->assertSame(2400.0, $summary['total_amount']);
        $this->assertSame(600.0, $summary['paid_amount']);
        $this->assertSame(1800.0, $summary['remaining_amount']);
        $this->assertSame(ReservationDossierService::PAYMENT_DEPOSIT, $summary['payment_status']);
    }

    public function test_it_marks_paid_when_payment_matches_total(): void
    {
        $service = new ReservationDossierService;

        $summary = $service->computeFinancialSummary(1800, 200, 0, 2000);

        $this->assertSame(0.0, $summary['remaining_amount']);
        $this->assertSame(ReservationDossierService::PAYMENT_PAID, $summary['payment_status']);
    }

    public function test_it_rejects_payment_above_total(): void
    {
        $this->expectException(ValidationException::class);

        $service = new ReservationDossierService;
        $service->computeFinancialSummary(1000, 0, 0, 1000.01);
    }

    public function test_it_applies_cancellation_state_to_reservation(): void
    {
        $service = new ReservationDossierService;
        $reservation = new Reservation;
        $reservation->status = Reservation::STATUS_PENDING;
        $reservation->dossier_status = Reservation::DOSSIER_PENDING;

        $service->applyCancellationState($reservation);

        $this->assertSame(Reservation::STATUS_CANCELLED, $reservation->status);
        $this->assertSame(ReservationDossierService::DOSSIER_CANCELLED, $reservation->dossier_status);
        $this->assertNotNull($reservation->cancelled_at);
    }
}
