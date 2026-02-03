<?php

namespace Database\Seeders;

use App\Models\TravelDayItem;
use App\Models\Voyage;
use Illuminate\Database\Seeder;

class TravelDayItemsSeeder extends Seeder
{
    /**
     * Seed travel day items from existing program days.
     * This is a demo seeder to populate items based on existing days.
     */
    public function run(): void
    {
        $voyages = Voyage::with('programDays')->get();

        foreach ($voyages as $voyage) {
            $this->seedItemsForVoyage($voyage);
        }

        $this->command->info('Travel day items seeded successfully!');
    }

    protected function seedItemsForVoyage(Voyage $voyage): void
    {
        foreach ($voyage->programDays as $day) {
            $dayNumber = $day->day_number;
            
            // Skip if items already exist for this day
            if (TravelDayItem::where('voyage_id', $voyage->id)->where('day_number', $dayNumber)->exists()) {
                continue;
            }

            $sortOrder = 0;

            // Add flight for day 1 (departure)
            if ($dayNumber === 1) {
                TravelDayItem::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'type' => 'flight',
                    'title' => 'Vol international vers ' . ($voyage->destination ?? 'la destination'),
                    'details' => 'Vol avec bagages inclus (23kg en soute + 10kg cabine)',
                    'included' => true,
                    'price_delta_per_person' => 0,
                    'sort_order' => $sortOrder++,
                    'meta_json' => [
                        'departure_time' => '08:00',
                        'arrival_time' => '14:30',
                    ],
                ]);

                // Airport transfer
                TravelDayItem::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'type' => 'transfer',
                    'title' => 'Transfert aéroport - hôtel',
                    'details' => 'Véhicule privé climatisé',
                    'included' => true,
                    'price_delta_per_person' => 0,
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Add hotel stay if nights > 0
            if ($day->nights > 0) {
                // Find consecutive nights
                $endDay = $dayNumber;
                $totalNights = $day->nights;
                
                // Check next days for consecutive nights
                $nextDay = $voyage->programDays->where('day_number', $dayNumber + 1)->first();
                while ($nextDay && $nextDay->nights > 0) {
                    $endDay = $nextDay->day_number;
                    $totalNights += $nextDay->nights;
                    $nextDay = $voyage->programDays->where('day_number', $endDay + 1)->first();
                }

                TravelDayItem::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'end_day' => $endDay,
                    'nights' => $totalNights,
                    'type' => 'hotel_stay',
                    'title' => 'Hébergement ' . ($day->city ? 'à ' . $day->city : ''),
                    'details' => 'Hôtel 4* en chambre double avec petit-déjeuner',
                    'included' => true,
                    'price_delta_per_person' => 0,
                    'sort_order' => $sortOrder++,
                    'options_json' => [
                        'upgrade_5star' => [
                            'title' => 'Upgrade hôtel 5*',
                            'price_delta' => 15000, // 150.00 in cents
                        ],
                        'single_room' => [
                            'title' => 'Supplément chambre individuelle',
                            'price_delta' => 8000, // 80.00 in cents
                        ],
                    ],
                ]);
            }

            // Add meals based on program day meals
            if ($day->hasMealBreakfast()) {
                TravelDayItem::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'type' => 'meal',
                    'title' => 'Petit-déjeuner',
                    'details' => 'Buffet petit-déjeuner à l\'hôtel',
                    'included' => true,
                    'price_delta_per_person' => 0,
                    'sort_order' => $sortOrder++,
                ]);
            }

            if ($day->hasMealLunch()) {
                TravelDayItem::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'type' => 'meal',
                    'title' => 'Déjeuner',
                    'details' => 'Repas dans un restaurant local',
                    'included' => true,
                    'price_delta_per_person' => 0,
                    'sort_order' => $sortOrder++,
                ]);
            }

            if ($day->hasMealDinner()) {
                TravelDayItem::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'type' => 'meal',
                    'title' => 'Dîner',
                    'details' => 'Dîner à l\'hôtel ou restaurant',
                    'included' => true,
                    'price_delta_per_person' => 0,
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Add an activity if it's a visit day
            if ($day->day_type === 'visite' && $day->city) {
                TravelDayItem::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'type' => 'activity',
                    'title' => 'Visite guidée de ' . $day->city,
                    'details' => 'Visite des principaux sites touristiques avec guide francophone',
                    'included' => true,
                    'price_delta_per_person' => 0,
                    'sort_order' => $sortOrder++,
                    'options_json' => [
                        'private_guide' => [
                            'title' => 'Guide privé (au lieu de groupe)',
                            'price_delta' => 5000, // 50.00 in cents
                        ],
                    ],
                ]);

                // Optional activity
                TravelDayItem::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'type' => 'activity',
                    'title' => 'Activité optionnelle - ' . $day->city,
                    'details' => 'Activité supplémentaire (musée, spectacle, etc.)',
                    'included' => false,
                    'price_delta_per_person' => 3000, // 30.00
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Add return flight for last day
            $lastDay = $voyage->programDays->max('day_number');
            if ($dayNumber === $lastDay) {
                TravelDayItem::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'type' => 'transfer',
                    'title' => 'Transfert hôtel - aéroport',
                    'details' => 'Véhicule privé climatisé',
                    'included' => true,
                    'price_delta_per_person' => 0,
                    'sort_order' => $sortOrder++,
                ]);

                TravelDayItem::create([
                    'voyage_id' => $voyage->id,
                    'day_number' => $dayNumber,
                    'start_day' => $dayNumber,
                    'type' => 'flight',
                    'title' => 'Vol retour vers le point de départ',
                    'details' => 'Vol international avec bagages inclus',
                    'included' => true,
                    'price_delta_per_person' => 0,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $this->command->info("Items created for voyage: {$voyage->name}");
    }
}
