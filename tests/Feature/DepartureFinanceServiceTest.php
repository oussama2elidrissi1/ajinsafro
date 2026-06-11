<?php

namespace Tests\Feature;

use App\Models\ChargeType;
use App\Models\Departure;
use App\Models\DepartureCharge;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\User;
use App\Models\Voyage;
use App\Services\DepartureFinanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;
use Tests\TestCase;

class DepartureFinanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is required for this isolated database test.');
        }

        parent::setUp();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    public function test_it_calculates_departure_finance_from_confirmed_reservation_payments_and_charges(): void
    {
        $user = User::factory()->create();
        $voyage = Voyage::query()->create(['name' => 'Circuit test', 'slug' => 'circuit-test']);
        $departure = Departure::query()->create([
            'voyage_id' => $voyage->id,
            'start_date' => '2026-08-10',
            'status' => Departure::STATUS_OPEN,
        ]);
        $type = ChargeType::query()->create(['name' => 'Hotel', 'slug' => 'hotel', 'is_active' => true]);

        $confirmed = Reservation::query()->create([
            'voyage_id' => $voyage->id,
            'tour_id' => $voyage->id,
            'departure_id' => $departure->id,
            'status' => Reservation::STATUS_CONFIRMED,
            'dossier_status' => Reservation::DOSSIER_CONFIRMED,
            'passengers_count' => 3,
        ]);
        ReservationPayment::query()->create([
            'reservation_id' => $confirmed->id,
            'payment_date' => '2026-07-01',
            'payment_method' => 'ESPECE',
            'amount' => 5000,
            'created_by' => $user->id,
        ]);

        Reservation::query()->create([
            'voyage_id' => $voyage->id,
            'tour_id' => $voyage->id,
            'departure_id' => $departure->id,
            'status' => Reservation::STATUS_CONFIRMED,
            'dossier_status' => Reservation::DOSSIER_CONFIRMED,
            'passengers_count' => 4,
        ]);

        $cancelled = Reservation::query()->create([
            'voyage_id' => $voyage->id,
            'tour_id' => $voyage->id,
            'departure_id' => $departure->id,
            'status' => Reservation::STATUS_CANCELLED,
            'dossier_status' => Reservation::DOSSIER_CANCELLED,
            'passengers_count' => 2,
        ]);
        ReservationPayment::query()->create([
            'reservation_id' => $cancelled->id,
            'payment_date' => '2026-07-02',
            'payment_method' => 'CHEQUE',
            'amount' => 9000,
            'created_by' => $user->id,
        ]);

        $charge = DepartureCharge::query()->create([
            'departure_id' => $departure->id,
            'voyage_id' => $voyage->id,
            'charge_type_id' => $type->id,
            'title' => 'Acompte hotel',
            'amount' => 1200,
            'payment_method' => 'cheque',
            'payment_status' => 'paye',
            'created_by' => $user->id,
        ]);

        $service = new DepartureFinanceService;

        $this->assertSame($departure->id, $charge->departure_id);
        $this->assertSame(1200.0, $service->getTotalCharges($departure));
        $this->assertSame(5000.0, $service->getTotalEntries($departure));
        $this->assertSame(3800.0, $service->getBalance($departure));
        $this->assertTrue($service->isProfitable($departure));
        $this->assertSame(7, $service->getTravelersCount($departure));
        $this->assertSame(2, $service->getReservationsCount($departure));

        $entries = $service->getEntriesByPaymentMethod($departure);
        $this->assertSame(5000.0, $entries['espece']['amount']);
        $this->assertSame(0.0, $entries['cheque']['amount']);

        Pdf::loadView('admin.finance.departures.pdf.internal-travel-sheet', $service->buildInternalTravelSheetData($departure))->output();
        $this->assertTrue(true);
    }
}
