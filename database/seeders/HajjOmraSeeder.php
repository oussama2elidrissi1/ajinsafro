<?php

namespace Database\Seeders;

use App\Models\HajjOmraDeparture;
use App\Models\HajjOmraPackage;
use App\Models\HajjOmraRoomPrice;
use Illuminate\Database\Seeder;

class HajjOmraSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            [
                'title' => 'Omra Ramadan 15 jours',
                'slug' => 'omra-ramadan-15-jours',
                'type' => HajjOmraPackage::TYPE_RAMADAN,
                'status' => HajjOmraPackage::STATUS_PUBLISHED,
                'main_image' => null,
                'short_description' => 'Formule Ramadan avec encadrement, hotels proches et programme spirituel structure.',
                'description' => 'Une formule Omra Ramadan complete au depart du Maroc avec accompagnement Ajinsafro, sejour a Medine puis a Makkah et organisation fluide sur tout le parcours.',
                'departure_city' => 'Casablanca',
                'destination' => 'Makkah / Madinah',
                'duration_days' => 15,
                'duration_nights' => 14,
                'start_date' => '2026-11-28',
                'return_date' => '2026-12-12',
                'adult_price' => 21900,
                'child_price' => 18900,
                'baby_price' => 5900,
                'currency' => 'DH',
                'available_places' => 42,
                'reserved_places' => 18,
                'makkah_hotel' => 'Emaar Al Khalil',
                'makkah_haram_distance' => '850 m',
                'madinah_hotel' => 'Saja Al Madinah',
                'madinah_haram_distance' => '400 m',
                'room_type' => HajjOmraRoomPrice::ROOM_QUADRUPLE,
                'transport_included' => true,
                'visa_included' => true,
                'guidance_included' => true,
                'meal_plan' => 'breakfast',
                'included_items' => ['Vol aller-retour', 'Visa Omra', 'Hotels Makkah et Madinah', 'Transferts internes', 'Encadrement Ajinsafro'],
                'excluded_items' => ['Depenses personnelles', 'Repas hors formule', 'Assurances complementaires'],
                'booking_conditions' => 'Acompte de 30% a la confirmation. Solde avant depart. Passeport valide 6 mois minimum.',
                'required_documents' => 'Passeport valide, photos d identite, copie CIN, carnet de vaccination si requis.',
                'meta_title' => 'Omra Ramadan 15 jours avec Ajinsafro',
                'meta_description' => 'Offre Omra Ramadan 15 jours depart Casablanca avec hotels, visa, transferts et encadrement Ajinsafro.',
                'sort_order' => 1,
                'is_featured' => true,
                'departures' => [
                    ['departure_date' => '2026-11-28', 'return_date' => '2026-12-12', 'status' => HajjOmraDeparture::STATUS_PUBLISHED, 'available_places' => 24, 'reserved_places' => 10, 'price_from' => 21900, 'internal_notes' => 'Depart principal Ramadan'],
                    ['departure_date' => '2026-12-05', 'return_date' => '2026-12-19', 'status' => HajjOmraDeparture::STATUS_PUBLISHED, 'available_places' => 18, 'reserved_places' => 8, 'price_from' => 22500, 'internal_notes' => 'Depart second groupe'],
                ],
                'room_prices' => [
                    ['room_type' => HajjOmraRoomPrice::ROOM_QUADRUPLE, 'price' => 21900, 'stock' => 20],
                    ['room_type' => HajjOmraRoomPrice::ROOM_TRIPLE, 'price' => 22900, 'stock' => 12],
                    ['room_type' => HajjOmraRoomPrice::ROOM_DOUBLE, 'price' => 24500, 'stock' => 8],
                    ['room_type' => HajjOmraRoomPrice::ROOM_SINGLE, 'price' => 27900, 'stock' => 2],
                ],
                'program_days' => [
                    ['day_number' => 1, 'title' => 'Depart du Maroc', 'description' => 'Convocation aeroport, formalites et vol vers l Arabie Saoudite.', 'city' => 'Casablanca'],
                    ['day_number' => 2, 'title' => 'Installation a Medine', 'description' => 'Accueil, transfert hotel et premiers reperes du sejour.', 'city' => 'Madinah'],
                    ['day_number' => 6, 'title' => 'Transfert vers Makkah', 'description' => 'Trajet organise avec assistance et installation a l hotel.', 'city' => 'Makkah'],
                    ['day_number' => 15, 'title' => 'Retour', 'description' => 'Check-out, transfert aeroport et vol retour.', 'city' => 'Jeddah'],
                ],
            ],
            [
                'title' => 'Omra economique 12 jours',
                'slug' => 'omra-economique-12-jours',
                'type' => HajjOmraPackage::TYPE_LOW_COST,
                'status' => HajjOmraPackage::STATUS_PUBLISHED,
                'short_description' => 'Offre accessible avec essentiels inclus et budget optimise.',
                'description' => 'Cette formule economique garde l essentiel: vol, visa, transferts, hotels propres et accompagnement Ajinsafro.',
                'departure_city' => 'Marrakech',
                'destination' => 'Makkah / Madinah',
                'duration_days' => 12,
                'duration_nights' => 11,
                'start_date' => '2026-09-10',
                'return_date' => '2026-09-21',
                'adult_price' => 15900,
                'child_price' => 13900,
                'baby_price' => 4500,
                'currency' => 'DH',
                'available_places' => 36,
                'reserved_places' => 9,
                'makkah_hotel' => 'Al Massa',
                'makkah_haram_distance' => '1.2 km',
                'madinah_hotel' => 'Odst Al Madinah',
                'madinah_haram_distance' => '650 m',
                'room_type' => HajjOmraRoomPrice::ROOM_QUADRUPLE,
                'transport_included' => true,
                'visa_included' => true,
                'guidance_included' => true,
                'meal_plan' => 'breakfast',
                'included_items' => ['Vol', 'Visa', 'Hotels', 'Transferts', 'Assistance'],
                'excluded_items' => ['Repas principaux', 'Depenses personnelles'],
                'booking_conditions' => 'Acompte obligatoire a l inscription. Dernier delai de paiement 21 jours avant depart.',
                'required_documents' => 'Passeport, photos d identite, copie CIN.',
                'meta_title' => 'Omra economique 12 jours Ajinsafro',
                'meta_description' => 'Formule Omra low cost depart Marrakech avec vol, visa, hotels et assistance.',
                'sort_order' => 2,
                'is_featured' => false,
                'departures' => [
                    ['departure_date' => '2026-09-10', 'return_date' => '2026-09-21', 'status' => HajjOmraDeparture::STATUS_PUBLISHED, 'available_places' => 20, 'reserved_places' => 5, 'price_from' => 15900, 'internal_notes' => 'Depart principal'],
                    ['departure_date' => '2026-10-01', 'return_date' => '2026-10-12', 'status' => HajjOmraDeparture::STATUS_PUBLISHED, 'available_places' => 16, 'reserved_places' => 4, 'price_from' => 16200, 'internal_notes' => 'Depart renfort'],
                ],
                'room_prices' => [
                    ['room_type' => HajjOmraRoomPrice::ROOM_QUADRUPLE, 'price' => 15900, 'stock' => 18],
                    ['room_type' => HajjOmraRoomPrice::ROOM_TRIPLE, 'price' => 16600, 'stock' => 10],
                    ['room_type' => HajjOmraRoomPrice::ROOM_DOUBLE, 'price' => 17600, 'stock' => 6],
                    ['room_type' => HajjOmraRoomPrice::ROOM_SINGLE, 'price' => 19800, 'stock' => 2],
                ],
                'program_days' => [
                    ['day_number' => 1, 'title' => 'Vol et arrivee', 'description' => 'Accueil et transfert organise.', 'city' => 'Jeddah'],
                    ['day_number' => 2, 'title' => 'Debut du sejour a Medine', 'description' => 'Installation et accompagnement du groupe.', 'city' => 'Madinah'],
                    ['day_number' => 5, 'title' => 'Depart pour Makkah', 'description' => 'Transfert collectif et installation.', 'city' => 'Makkah'],
                ],
            ],
            [
                'title' => 'Omra Premium 10 jours',
                'slug' => 'omra-premium-10-jours',
                'type' => HajjOmraPackage::TYPE_PREMIUM,
                'status' => HajjOmraPackage::STATUS_PUBLISHED,
                'short_description' => 'Sejour premium avec hotels proches, confort superieur et service renforce.',
                'description' => 'Une formule premium courte avec prestations soignees, depart rapide et hotels bien situes pour maximiser le confort du pelerin.',
                'departure_city' => 'Rabat',
                'destination' => 'Makkah / Madinah',
                'duration_days' => 10,
                'duration_nights' => 9,
                'start_date' => '2026-08-18',
                'return_date' => '2026-08-27',
                'adult_price' => 24900,
                'child_price' => 21900,
                'baby_price' => 6500,
                'currency' => 'DH',
                'available_places' => 24,
                'reserved_places' => 16,
                'makkah_hotel' => 'Swissotel Makkah',
                'makkah_haram_distance' => '150 m',
                'madinah_hotel' => 'Anwar Al Madinah Movenpick',
                'madinah_haram_distance' => '120 m',
                'room_type' => HajjOmraRoomPrice::ROOM_DOUBLE,
                'transport_included' => true,
                'visa_included' => true,
                'guidance_included' => true,
                'meal_plan' => 'half_board',
                'included_items' => ['Vol direct', 'Visa', 'Hotels premium', 'Demi-pension', 'Transferts VIP', 'Accompagnement'],
                'excluded_items' => ['Depenses personnelles'],
                'booking_conditions' => 'Paiement en 2 echeances. Confirmation apres validation dossier.',
                'required_documents' => 'Passeport, CIN, photos.',
                'meta_title' => 'Omra Premium 10 jours Ajinsafro',
                'meta_description' => 'Offre premium Omra avec hotels proches des harams, demi-pension et encadrement Ajinsafro.',
                'sort_order' => 3,
                'is_featured' => true,
                'departures' => [
                    ['departure_date' => '2026-08-18', 'return_date' => '2026-08-27', 'status' => HajjOmraDeparture::STATUS_FULL, 'available_places' => 12, 'reserved_places' => 12, 'price_from' => 24900, 'internal_notes' => 'Complet'],
                    ['departure_date' => '2026-09-03', 'return_date' => '2026-09-12', 'status' => HajjOmraDeparture::STATUS_PUBLISHED, 'available_places' => 12, 'reserved_places' => 4, 'price_from' => 25200, 'internal_notes' => 'Places restantes'],
                ],
                'room_prices' => [
                    ['room_type' => HajjOmraRoomPrice::ROOM_DOUBLE, 'price' => 24900, 'stock' => 10],
                    ['room_type' => HajjOmraRoomPrice::ROOM_SINGLE, 'price' => 28900, 'stock' => 4],
                ],
                'program_days' => [
                    ['day_number' => 1, 'title' => 'Vol direct', 'description' => 'Depart Rabat et accueil personnalise.', 'city' => 'Rabat'],
                    ['day_number' => 2, 'title' => 'Madinah premium', 'description' => 'Installation hotel proche Haram.', 'city' => 'Madinah'],
                    ['day_number' => 5, 'title' => 'Makkah', 'description' => 'Transfert prive et assistance.', 'city' => 'Makkah'],
                ],
            ],
            [
                'title' => 'Hajj 2026 package standard',
                'slug' => 'hajj-2026-package-standard',
                'type' => HajjOmraPackage::TYPE_HAJJ,
                'status' => HajjOmraPackage::STATUS_PUBLISHED,
                'short_description' => 'Programme Hajj standard 2026 avec encadrement complet et logistique encadree.',
                'description' => 'Une offre Hajj 2026 standard pensee pour allier accompagnement, hebergement organise et parcours spirituel clair pour les pelerins.',
                'departure_city' => 'Casablanca',
                'destination' => 'Makkah / Mina / Arafat / Madinah',
                'duration_days' => 21,
                'duration_nights' => 20,
                'start_date' => '2026-05-22',
                'return_date' => '2026-06-11',
                'adult_price' => 68900,
                'child_price' => 0,
                'baby_price' => 0,
                'currency' => 'DH',
                'available_places' => 60,
                'reserved_places' => 22,
                'makkah_hotel' => 'Hilton Suites Jabal Omar',
                'makkah_haram_distance' => '300 m',
                'madinah_hotel' => 'Pullman Zamzam Madinah',
                'madinah_haram_distance' => '180 m',
                'room_type' => HajjOmraRoomPrice::ROOM_QUADRUPLE,
                'transport_included' => true,
                'visa_included' => true,
                'guidance_included' => true,
                'meal_plan' => 'full_board',
                'included_items' => ['Vol', 'Visa Hajj', 'Hebergements', 'Transport rites', 'Pension complete', 'Equipe encadrante'],
                'excluded_items' => ['Depenses personnelles', 'Prestations non mentionnees'],
                'booking_conditions' => 'Attribution sous reserve des formalites et des quotas 2026. Acompte non remboursable apres emission.',
                'required_documents' => 'Passeport, photos, dossier administratif Hajj complet.',
                'meta_title' => 'Hajj 2026 package standard Ajinsafro',
                'meta_description' => 'Programme Hajj 2026 standard avec visa, logement, restauration et accompagnement Ajinsafro.',
                'sort_order' => 4,
                'is_featured' => true,
                'departures' => [
                    ['departure_date' => '2026-05-22', 'return_date' => '2026-06-11', 'status' => HajjOmraDeparture::STATUS_PUBLISHED, 'available_places' => 60, 'reserved_places' => 22, 'price_from' => 68900, 'internal_notes' => 'Sous reserve quotas 2026'],
                ],
                'room_prices' => [
                    ['room_type' => HajjOmraRoomPrice::ROOM_QUADRUPLE, 'price' => 68900, 'stock' => 30],
                    ['room_type' => HajjOmraRoomPrice::ROOM_TRIPLE, 'price' => 72900, 'stock' => 18],
                    ['room_type' => HajjOmraRoomPrice::ROOM_DOUBLE, 'price' => 78900, 'stock' => 10],
                    ['room_type' => HajjOmraRoomPrice::ROOM_SINGLE, 'price' => 89900, 'stock' => 2],
                ],
                'program_days' => [
                    ['day_number' => 1, 'title' => 'Depart du Maroc', 'description' => 'Regroupement et vol organise.', 'city' => 'Casablanca'],
                    ['day_number' => 4, 'title' => 'Installation a Makkah', 'description' => 'Orientation et accompagnement du groupe.', 'city' => 'Makkah'],
                    ['day_number' => 10, 'title' => 'Rites du Hajj', 'description' => 'Parcours Mina, Arafat et Muzdalifah avec encadrement.', 'city' => 'Mina'],
                    ['day_number' => 18, 'title' => 'Sejour a Medinah', 'description' => 'Temps de recueillement et visites organisees.', 'city' => 'Madinah'],
                ],
            ],
        ];

        foreach ($catalog as $payload) {
            $departures = $payload['departures'];
            $roomPrices = $payload['room_prices'];
            $programDays = $payload['program_days'];

            unset($payload['departures'], $payload['room_prices'], $payload['program_days']);

            $package = HajjOmraPackage::updateOrCreate(
                ['slug' => $payload['slug']],
                $payload
            );

            $package->departures()->delete();
            foreach ($departures as $index => $departure) {
                $package->departures()->create(array_merge($departure, [
                    'sort_order' => $index + 1,
                ]));
            }

            $package->roomPrices()->delete();
            foreach ($roomPrices as $index => $roomPrice) {
                $package->roomPrices()->create(array_merge($roomPrice, [
                    'sort_order' => $index + 1,
                ]));
            }

            $package->programDays()->delete();
            foreach ($programDays as $index => $programDay) {
                $package->programDays()->create(array_merge($programDay, [
                    'sort_order' => $index + 1,
                ]));
            }
        }
    }
}
