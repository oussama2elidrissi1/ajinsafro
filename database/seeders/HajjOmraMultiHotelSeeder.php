<?php

namespace Database\Seeders;

use App\Models\HajjOmraDeparture;
use App\Models\HajjOmraPackage;
use App\Models\HajjOmraPackageHotel;
use App\Models\HajjOmraRoomPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Offre de demonstration du cas « plusieurs hebergements par date ».
 *
 * Chaque depart propose deux options d'hebergement (Confort / Premium) avec leurs
 * propres tarifs : sur la page publique, choisir une date filtre les options, et choisir
 * une option met a jour le « prix a partir de ». Les prix different d'une date a l'autre,
 * ce qui impose une formule par (option x depart) — exactement ce que le bouton
 * « Dupliquer pour un autre depart » de l'editeur produit.
 *
 * Relance idempotente : l'offre est identifiee par son slug et ses collections sont
 * reconstruites a chaque passage.
 */
class HajjOmraMultiHotelSeeder extends Seeder
{
    private const SLUG = 'omra-ramadan-multi-hebergements';

    public function run(): void
    {
        $package = HajjOmraPackage::updateOrCreate(['slug' => self::SLUG], [
            'title' => 'Omra Ramadan — 2 hébergements au choix',
            'title_ar' => 'عمرة رمضان — إقامتان للاختيار',
            'type' => HajjOmraPackage::TYPE_RAMADAN,
            'status' => HajjOmraPackage::STATUS_PUBLISHED,
            'short_description' => 'Choisissez votre date, puis votre hebergement : le prix s ajuste automatiquement.',
            'description' => 'Deux niveaux d hebergement sur chaque depart, avec leurs propres tarifs par type de chambre.',
            'departure_city' => 'Casablanca',
            'destination' => 'Makkah / Madinah',
            'duration_days' => 15,
            'duration_nights' => 14,
            'start_date' => '2026-11-28',
            'return_date' => '2026-12-12',
            // Laisse vide : le « a partir de » est calcule depuis les tarifs des formules.
            'adult_price' => null,
            'child_price' => 18900,
            'currency' => 'DH',
            'available_places' => 48,
            'reserved_places' => 12,
            'transport_included' => true,
            'visa_included' => true,
            'guidance_included' => true,
            'meal_plan' => 'breakfast',
            'included_items' => ['Vol aller-retour', 'Visa Omra', 'Transferts internes', 'Encadrement Ajinsafro'],
            'excluded_items' => ['Depenses personnelles', 'Repas hors formule'],
            'sort_order' => 0,
            'is_featured' => true,
        ]);

        DB::transaction(function () use ($package) {
            $package->formulas()->delete();
            $package->roomPrices()->delete();
            $package->hotels()->delete();
            $package->departures()->delete();
            $package->programDays()->delete();

            $departures = [];
            foreach ([
                ['departure_date' => '2026-11-28', 'return_date' => '2026-12-12', 'available_places' => 24, 'reserved_places' => 6],
                ['departure_date' => '2026-12-05', 'return_date' => '2026-12-19', 'available_places' => 24, 'reserved_places' => 6],
            ] as $index => $row) {
                $departures[] = $package->departures()->create($row + [
                    'status' => HajjOmraDeparture::STATUS_PUBLISHED,
                    'sort_order' => $index + 1,
                ]);
            }

            $hotels = [];
            foreach ([
                'makkah_confort' => ['city' => HajjOmraPackageHotel::CITY_MAKKAH, 'name' => 'Emaar Al Khalil', 'stars' => 3, 'haram_distance' => '850 m', 'nights' => 9],
                'madinah_confort' => ['city' => HajjOmraPackageHotel::CITY_MADINAH, 'name' => 'Saja Al Madinah', 'stars' => 3, 'haram_distance' => '400 m', 'nights' => 5],
                'makkah_premium' => ['city' => HajjOmraPackageHotel::CITY_MAKKAH, 'name' => 'Swissotel Al Maqam', 'stars' => 5, 'haram_distance' => '150 m', 'nights' => 9],
                'madinah_premium' => ['city' => HajjOmraPackageHotel::CITY_MADINAH, 'name' => 'Dar Al Iman InterContinental', 'stars' => 5, 'haram_distance' => '120 m', 'nights' => 5],
            ] as $key => $row) {
                $hotels[$key] = $package->hotels()->create($row + ['meal_plan' => 'breakfast', 'sort_order' => count($hotels) + 1]);
            }

            // Une option = un niveau d'hebergement ; ses prix varient selon le depart.
            $options = [
                'confort' => [
                    'name_fr' => 'Confort — Emaar Al Khalil / Saja Al Madinah',
                    'name_ar' => 'إقامة مريحة — إعمار الخليل / سجى المدينة',
                    'stays' => ['madinah_confort', 'makkah_confort'],
                    'prices' => [
                        0 => [HajjOmraRoomPrice::ROOM_QUADRUPLE => 21900, HajjOmraRoomPrice::ROOM_TRIPLE => 22900, HajjOmraRoomPrice::ROOM_DOUBLE => 24500],
                        1 => [HajjOmraRoomPrice::ROOM_QUADRUPLE => 22900, HajjOmraRoomPrice::ROOM_TRIPLE => 23900, HajjOmraRoomPrice::ROOM_DOUBLE => 25500],
                    ],
                ],
                'premium' => [
                    'name_fr' => 'Premium — Swissotel Al Maqam / Dar Al Iman',
                    'name_ar' => 'إقامة مميزة — سويس أوتيل المقام / دار الإيمان',
                    'stays' => ['madinah_premium', 'makkah_premium'],
                    'prices' => [
                        0 => [HajjOmraRoomPrice::ROOM_QUADRUPLE => 25900, HajjOmraRoomPrice::ROOM_TRIPLE => 27500, HajjOmraRoomPrice::ROOM_DOUBLE => 29900],
                        1 => [HajjOmraRoomPrice::ROOM_QUADRUPLE => 26900, HajjOmraRoomPrice::ROOM_TRIPLE => 28500, HajjOmraRoomPrice::ROOM_DOUBLE => 30900],
                    ],
                ],
            ];

            $capacities = [
                HajjOmraRoomPrice::ROOM_QUADRUPLE => 4,
                HajjOmraRoomPrice::ROOM_TRIPLE => 3,
                HajjOmraRoomPrice::ROOM_DOUBLE => 2,
            ];
            $sort = 0;
            $position = 0;

            foreach ($options as $option) {
                foreach ($departures as $index => $departure) {
                    $date = $departure->departure_date->format('d/m/Y');
                    $label = explode(' — ', $option['name_fr'])[0].' · '.$date;

                    $tariffIds = [];
                    foreach ($option['prices'][$index] as $roomType => $price) {
                        $tariffIds[] = $package->roomPrices()->create([
                            'room_type' => $roomType,
                            'label' => $label,
                            'price' => $price,
                            'capacity' => $capacities[$roomType],
                            'stock' => $roomType === HajjOmraRoomPrice::ROOM_DOUBLE ? 6 : 12,
                            'is_active' => true,
                            'sort_order' => ++$position,
                        ])->id;
                    }

                    $formula = $package->formulas()->create([
                        'departure_id' => $departure->id,
                        'name_fr' => $option['name_fr'],
                        'name_ar' => $option['name_ar'],
                        'sort_order' => $sort++,
                        'is_active' => true,
                    ]);
                    $formula->tariffs()->sync(collect($tariffIds)->mapWithKeys(
                        fn ($id, $order) => [$id => ['sort_order' => $order]]
                    )->all());

                    foreach ($option['stays'] as $order => $hotelKey) {
                        $formula->stays()->create([
                            'hotel_id' => $hotels[$hotelKey]->id,
                            'sort_order' => $order,
                        ]);
                    }
                }
            }

            foreach ([
                ['day_number' => 1, 'title' => 'Depart du Maroc', 'city' => 'Casablanca'],
                ['day_number' => 2, 'title' => 'Installation a Medine', 'city' => 'Madinah'],
                ['day_number' => 6, 'title' => 'Transfert vers Makkah', 'city' => 'Makkah'],
                ['day_number' => 15, 'title' => 'Retour', 'city' => 'Jeddah'],
            ] as $index => $day) {
                $package->programDays()->create($day + ['sort_order' => $index + 1]);
            }
        });

        $this->command?->info('Offre de demonstration : /admin/products-services/hajj-omra/'.$package->id.'/edit');
    }
}
