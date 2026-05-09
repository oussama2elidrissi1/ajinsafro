<?php

namespace Database\Seeders;

use App\Models\EconomicOffer;
use Illuminate\Database\Seeder;

class EconomicOfferSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            [
                'title' => 'Omra economique 12 jours',
                'slug' => 'omra-economique-12-jours',
                'internal_reference' => 'ECO-OMRA-1201',
                'offer_type' => EconomicOffer::TYPE_OMRA,
                'category' => EconomicOffer::CATEGORY_ECONOMIC,
                'status' => EconomicOffer::STATUS_PUBLISHED,
                'short_description' => 'Omra budget maitrisé avec vol, hebergement et assistance Ajinsafro.',
                'description' => 'Une formule Omra economique complete au depart du Maroc, pensee pour les clients qui veulent l essentiel au meilleur prix.',
                'price_from' => 14900,
                'old_price' => 15900,
                'currency' => 'DH',
                'price_type' => 'per_person',
                'deposit_amount' => 4000,
                'payment_conditions' => 'Acompte a la confirmation puis solde 21 jours avant depart.',
                'included_items' => ['Vol aller-retour', 'Visa Omra', 'Hotel', 'Transferts', 'Assistance Ajinsafro'],
                'excluded_items' => ['Repas hors formule', 'Depenses personnelles'],
                'departure_date' => '2026-09-15',
                'return_date' => '2026-09-26',
                'duration_days' => 12,
                'duration_nights' => 11,
                'total_places' => 40,
                'available_places' => 22,
                'reserved_places' => 8,
                'departure_city' => 'Casablanca',
                'destination' => 'Makkah / Madinah',
                'country' => 'Arabie Saoudite',
                'arrival_city' => 'Jeddah',
                'hotel_included' => true,
                'transport_included' => true,
                'flight_included' => true,
                'guide_included' => true,
                'room_type' => 'Quadruple',
                'meal_plan' => 'breakfast',
                'program_summary' => 'Sejour entre Makkah et Madinah avec encadrement Ajinsafro.',
                'required_documents' => 'Passeport, CIN, photos.',
                'sort_order' => 1,
                'is_featured' => true,
                'departures' => [
                    ['departure_date' => '2026-09-15', 'return_date' => '2026-09-26', 'price_from' => 14900, 'total_places' => 20, 'available_places' => 16, 'reserved_places' => 4, 'status' => 'published', 'internal_notes' => 'Depart principal'],
                    ['departure_date' => '2026-10-04', 'return_date' => '2026-10-15', 'price_from' => 15200, 'total_places' => 20, 'available_places' => 12, 'reserved_places' => 3, 'status' => 'published', 'internal_notes' => 'Depart renfort'],
                ],
                'prices' => [
                    ['label' => 'Adulte', 'type' => 'adulte', 'price' => 14900, 'old_price' => 15900, 'stock' => 20, 'condition' => 'Par personne'],
                    ['label' => 'Enfant', 'type' => 'enfant', 'price' => 12900, 'old_price' => 13900, 'stock' => 8, 'condition' => '2-11 ans'],
                ],
            ],
            [
                'title' => 'Sejour Marrakech economique 4 jours',
                'slug' => 'sejour-marrakech-economique-4-jours',
                'internal_reference' => 'ECO-MRK-0401',
                'offer_type' => EconomicOffer::TYPE_TRAVEL,
                'category' => EconomicOffer::CATEGORY_PROMOTION,
                'status' => EconomicOffer::STATUS_PUBLISHED,
                'short_description' => 'Court sejour a Marrakech avec hotel et petit dejeuner.',
                'description' => 'Une escapade petit budget pour decouvrir Marrakech avec une formule simple et efficace.',
                'price_from' => 1490,
                'old_price' => 1890,
                'currency' => 'DH',
                'price_type' => 'per_person',
                'departure_date' => '2026-06-18',
                'return_date' => '2026-06-21',
                'duration_days' => 4,
                'duration_nights' => 3,
                'total_places' => 24,
                'available_places' => 14,
                'reserved_places' => 5,
                'departure_city' => 'Casablanca',
                'destination' => 'Marrakech',
                'country' => 'Maroc',
                'arrival_city' => 'Marrakech',
                'hotel_included' => true,
                'meals_included' => true,
                'accommodation_type' => 'Hotel',
                'hotel_name' => 'Hotel Atlas Budget',
                'hotel_category' => '3*',
                'meal_plan' => 'breakfast',
                'sort_order' => 2,
                'departures' => [
                    ['departure_date' => '2026-06-18', 'return_date' => '2026-06-21', 'price_from' => 1490, 'total_places' => 12, 'available_places' => 8, 'reserved_places' => 2, 'status' => 'published', 'internal_notes' => 'Promo ete'],
                ],
                'prices' => [
                    ['label' => 'Adulte', 'type' => 'adulte', 'price' => 1490, 'old_price' => 1890, 'stock' => 12, 'condition' => 'Petit dejeuner inclus'],
                    ['label' => 'Chambre double', 'type' => 'room', 'price' => 2990, 'old_price' => 3490, 'stock' => 6, 'condition' => '2 personnes'],
                ],
            ],
            [
                'title' => 'Pack Dakhla economique',
                'slug' => 'pack-dakhla-economique',
                'internal_reference' => 'ECO-DKH-0501',
                'offer_type' => EconomicOffer::TYPE_PACK,
                'category' => EconomicOffer::CATEGORY_ECONOMIC,
                'status' => EconomicOffer::STATUS_PUBLISHED,
                'short_description' => 'Pack petit budget pour un break a Dakhla.',
                'description' => 'Formule pack incluant sejour, assistance et options a la carte.',
                'price_from' => 3990,
                'currency' => 'DH',
                'price_type' => 'per_person',
                'departure_date' => '2026-07-10',
                'return_date' => '2026-07-14',
                'duration_days' => 5,
                'duration_nights' => 4,
                'total_places' => 18,
                'available_places' => 9,
                'reserved_places' => 4,
                'departure_city' => 'Casablanca',
                'destination' => 'Dakhla',
                'country' => 'Maroc',
                'arrival_city' => 'Dakhla',
                'sort_order' => 3,
                'departures' => [
                    ['departure_date' => '2026-07-10', 'return_date' => '2026-07-14', 'price_from' => 3990, 'total_places' => 18, 'available_places' => 9, 'reserved_places' => 4, 'status' => 'published', 'internal_notes' => 'Pack ocean'],
                ],
                'prices' => [
                    ['label' => 'Adulte', 'type' => 'adulte', 'price' => 3990, 'old_price' => 0, 'stock' => 18, 'condition' => 'Par personne'],
                ],
            ],
            [
                'title' => 'Activite quad Marrakech',
                'slug' => 'activite-quad-marrakech',
                'internal_reference' => 'ECO-ACT-0301',
                'offer_type' => EconomicOffer::TYPE_ACTIVITY,
                'category' => EconomicOffer::CATEGORY_PROMOTION,
                'status' => EconomicOffer::STATUS_PUBLISHED,
                'short_description' => 'Session quad a Marrakech a prix leger.',
                'description' => 'Activite courte accessible pour les petits budgets, avec guide local.',
                'price_from' => 350,
                'currency' => 'DH',
                'price_type' => 'per_person',
                'departure_date' => '2026-06-05',
                'return_date' => '2026-06-05',
                'duration_days' => 1,
                'duration_nights' => 0,
                'total_places' => 30,
                'available_places' => 20,
                'reserved_places' => 4,
                'departure_city' => 'Marrakech',
                'destination' => 'Palmeraie Marrakech',
                'country' => 'Maroc',
                'arrival_city' => 'Marrakech',
                'guide_included' => true,
                'sort_order' => 4,
                'departures' => [
                    ['departure_date' => '2026-06-05', 'return_date' => '2026-06-05', 'price_from' => 350, 'total_places' => 15, 'available_places' => 10, 'reserved_places' => 2, 'status' => 'published', 'internal_notes' => 'Session matin'],
                ],
                'prices' => [
                    ['label' => 'Participant', 'type' => 'participant', 'price' => 350, 'old_price' => 450, 'stock' => 30, 'condition' => 'Equipement de base inclus'],
                ],
            ],
            [
                'title' => 'Voyage Istanbul economique',
                'slug' => 'voyage-istanbul-economique',
                'internal_reference' => 'ECO-IST-0701',
                'offer_type' => EconomicOffer::TYPE_TRAVEL,
                'category' => EconomicOffer::CATEGORY_ECONOMIC,
                'status' => EconomicOffer::STATUS_PUBLISHED,
                'short_description' => 'Istanbul accessible avec vol et hotel.',
                'description' => 'Formule budget pour visiter Istanbul avec les incontournables et un logement simple.',
                'price_from' => 5900,
                'currency' => 'DH',
                'price_type' => 'per_person',
                'departure_date' => '2026-09-02',
                'return_date' => '2026-09-07',
                'duration_days' => 6,
                'duration_nights' => 5,
                'total_places' => 28,
                'available_places' => 11,
                'reserved_places' => 9,
                'departure_city' => 'Casablanca',
                'destination' => 'Istanbul',
                'country' => 'Turquie',
                'arrival_city' => 'Istanbul',
                'flight_included' => true,
                'hotel_included' => true,
                'sort_order' => 5,
                'departures' => [
                    ['departure_date' => '2026-09-02', 'return_date' => '2026-09-07', 'price_from' => 5900, 'total_places' => 14, 'available_places' => 7, 'reserved_places' => 4, 'status' => 'published', 'internal_notes' => 'Depart septembre'],
                ],
                'prices' => [
                    ['label' => 'Adulte', 'type' => 'adulte', 'price' => 5900, 'old_price' => 6500, 'stock' => 14, 'condition' => 'Base double'],
                ],
            ],
            [
                'title' => 'Offre derniere minute Agadir',
                'slug' => 'offre-derniere-minute-agadir',
                'internal_reference' => 'ECO-AGA-0201',
                'offer_type' => EconomicOffer::TYPE_ACCOMMODATION,
                'category' => EconomicOffer::CATEGORY_LAST_MINUTE,
                'status' => EconomicOffer::STATUS_PUBLISHED,
                'short_description' => 'Offre flash petit budget pour Agadir.',
                'description' => 'Depart rapide et budget maitrise pour une escapade balneaire.',
                'price_from' => 1200,
                'old_price' => 1700,
                'currency' => 'DH',
                'price_type' => 'per_room',
                'departure_date' => '2026-05-20',
                'return_date' => '2026-05-22',
                'duration_days' => 3,
                'duration_nights' => 2,
                'total_places' => 10,
                'available_places' => 5,
                'reserved_places' => 2,
                'departure_city' => 'Casablanca',
                'destination' => 'Agadir',
                'country' => 'Maroc',
                'arrival_city' => 'Agadir',
                'hotel_included' => true,
                'hotel_name' => 'Hotel Atlantic Eco',
                'hotel_category' => '3*',
                'sort_order' => 6,
                'is_featured' => true,
                'departures' => [
                    ['departure_date' => '2026-05-20', 'return_date' => '2026-05-22', 'price_from' => 1200, 'total_places' => 10, 'available_places' => 5, 'reserved_places' => 2, 'status' => 'published', 'internal_notes' => 'Flash sale'],
                ],
                'prices' => [
                    ['label' => 'Chambre double', 'type' => 'room', 'price' => 1200, 'old_price' => 1700, 'stock' => 5, 'condition' => '2 nuits'],
                ],
            ],
        ];

        foreach ($catalog as $payload) {
            $departures = $payload['departures'] ?? [];
            $prices = $payload['prices'] ?? [];

            unset($payload['departures'], $payload['prices']);

            $offer = EconomicOffer::updateOrCreate(
                ['slug' => $payload['slug']],
                $payload
            );

            $offer->departures()->delete();
            foreach ($departures as $index => $departure) {
                $offer->departures()->create(array_merge($departure, [
                    'sort_order' => $index + 1,
                ]));
            }

            $offer->prices()->delete();
            foreach ($prices as $index => $price) {
                $offer->prices()->create(array_merge($price, [
                    'sort_order' => $index + 1,
                ]));
            }

            $offer->refresh();
            $offer->save();
        }
    }
}
