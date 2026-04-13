<?php

namespace Database\Seeders;

use App\Models\Departure;
use App\Models\Reservation;
use App\Models\ReservationPassenger;
use App\Models\Voyage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GroupDealTestSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer le premier voyage Group Deal
        $voyage = Voyage::where('is_group_deal', true)
            ->first();

        if (!$voyage) {
            echo "No Group Deal voyage found. Skipping seeder.\n";
            return;
        }

        echo "Found voyage: {$voyage->name} (ID: {$voyage->id})\n";

        // Créer ou mettre à jour un départ de test
        $departure = Departure::updateOrCreate(
            [
                'voyage_id' => $voyage->id,
                'start_date' => Carbon::now()->addDays(30)->toDateString(),
            ],
            [
                'end_date' => Carbon::now()->addDays(35)->toDateString(),
                'status' => 'open',
                'total_capacity' => 30,
                'reserved_capacity' => 0, // Sera calculé automatiquement
                'available_capacity' => 30,
                'base_price' => 1500,
                'sale_price' => 1200,
                'group_deal_enabled' => true,
                'min_participants' => 2,
                'guaranteed_threshold' => 10,
                'is_guaranteed' => false,
            ]
        );

        echo "Departure: {$departure->id} (Status: {$departure->status})\n";
        echo "Date: {$departure->start_date}\n";
        echo "Total Capacity: {$departure->total_capacity}\n";
        echo "Group Deal Enabled: " . ($departure->group_deal_enabled ? 'Yes' : 'No') . "\n";
        echo "Guaranteed Threshold: {$departure->guaranteed_threshold}\n";

        // Créer des réservations confirmées avec passagers
        // On en crée 8 pour avoir 8 participants confirmés
        for ($i = 1; $i <= 8; $i++) {
            $existing = Reservation::where('departure_id', $departure->id)
                ->where('client_external_id', 9000 + $i)
                ->first();

            if (!$existing) {
                $reservation = Reservation::create([
                    'departure_id' => $departure->id,
                    'voyage_id' => $voyage->id,
                    'client_external_id' => 9000 + $i,
                    'status' => 'confirmed',
                    'passengers_count' => 1,
                    'paid_amount' => 1200,
                ]);

                // Créer un passager pour chaque réservation
                ReservationPassenger::create([
                    'reservation_id' => $reservation->id,
                    'first_name' => 'Test',
                    'last_name' => 'Passenger ' . $i,
                    'type' => 'adult',
                ]);

                echo "Created reservation #{$i} with passenger\n";
            }
        }

        // Recalculer la capacité réservée
        $departure->refresh();
        echo "\nFinal reserved_capacity: {$departure->reserved_capacity}\n";
        echo "Final available_capacity: {$departure->available_capacity}\n";
    }
}

