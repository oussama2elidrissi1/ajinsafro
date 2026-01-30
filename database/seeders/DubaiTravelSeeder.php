<?php

namespace Database\Seeders;

use App\Models\Departure;
use App\Models\TravelProgramDay;
use App\Models\Voyage;
use Illuminate\Database\Seeder;

class DubaiTravelSeeder extends Seeder
{
    public function run(): void
    {
        $voyage = Voyage::updateOrCreate(
            ['slug' => 'sejour-dubai-7-jours-6-nuits'],
            [
                'name' => 'Séjour Dubaï 7 jours (6 nuits)',
                'description' => "✔ Inclus\nAccueil et assistance\nTransferts aéroport\nBus climatisés\nGuide touristique\nHébergement 6 nuits avec petit-déjeuner\nExcursions mentionnées\n\n✖ Non inclus\nBillets d'avion internationaux\nRepas dans malls et restaurants\nTaxe touristique\nVisa EAU : +1800 DH\n\nℹ Notes\nL'ordre des jours peut changer\nDisponibilité à vérifier avant paiement\nPasseport valide 6 mois\nConditions sujettes à changement",
                'accroche' => 'Partez à la découverte de Dubaï lors d\'un séjour inoubliable de 7 jours (6 nuits) à partir de 10900 DHs!',
                'destination' => 'Dubaï, Émirats Arabes Unis',
                'duration_text' => '7 jours / 6 nuits',
                'price_from' => 10900,
                'old_price' => 14300,
                'currency' => 'MAD',
                'min_people' => 2,
                'status' => 'actif',
                'departure_policy' => "Possibilité de choisir un départ selon vos envies à partir de 2 personnes.\nDéparts disponibles chaque samedi selon la disponibilité du vol.",
            ]
        );

        Departure::updateOrCreate(
            ['voyage_id' => $voyage->id, 'start_date' => '2026-09-30'],
            ['status' => 'open']
        );

        $days = [
            [
                'title' => 'Casablanca ✈ Dubaï',
                'city' => 'Dubaï',
                'day_label' => null,
                'nights' => 1,
                'day_type' => 'arrivee',
                'meals_json' => ['breakfast' => false, 'lunch' => false, 'dinner' => false],
                'content_html' => '<ul><li>Arrivée à l\'aéroport international de Dubaï</li><li>Accueil par notre représentant</li><li>Transfert à l\'hôtel et nuitée</li></ul>',
            ],
            [
                'title' => 'Demi-journée Tour de Dubaï – Inclus',
                'city' => 'Dubaï',
                'day_label' => 'inclus',
                'nights' => 1,
                'day_type' => 'visite',
                'meals_json' => ['breakfast' => true, 'lunch' => false, 'dinner' => false],
                'content_html' => '<ul><li>Petit déjeuner (Inclus)</li><li>Rencontre avec le guide</li><li>Tour panoramique incluant :<ul><li>Dubai Marina</li><li>Jumeirah</li><li>Burj Al Arab (vue extérieure)</li><li>Souk de l\'Or et des Épices</li><li>Downtown Dubaï</li></ul></li><li>Retour à l\'hôtel, temps libre, nuitée</li></ul>',
            ],
            [
                'title' => 'Safari Désert – Inclus',
                'city' => null,
                'day_label' => 'inclus',
                'nights' => 1,
                'day_type' => 'visite',
                'meals_json' => ['breakfast' => false, 'lunch' => false, 'dinner' => true],
                'content_html' => '<ul><li>Dune bashing en 4x4</li><li>Balade à dos de chameau</li><li>Dîner barbecue</li><li>Spectacle oriental</li><li>Boissons gazeuses uniquement</li></ul>',
            ],
            [
                'title' => 'Excursion Abou Dhabi – Inclus',
                'city' => 'Abou Dhabi',
                'day_label' => 'inclus',
                'nights' => 1,
                'day_type' => 'visite',
                'meals_json' => ['breakfast' => false, 'lunch' => true, 'dinner' => false],
                'content_html' => '<ul><li>Mosquée Sheikh Zayed</li><li>Corniche d\'Abou Dhabi</li><li>Emirates Palace (extérieur)</li><li>Qasr Al Watan (extérieur)</li><li>Yas Island</li><li>Déjeuner inclus</li></ul>',
            ],
            [
                'title' => 'Journée libre',
                'city' => null,
                'day_label' => 'libre',
                'nights' => 1,
                'day_type' => 'libre',
                'meals_json' => ['breakfast' => true, 'lunch' => false, 'dinner' => false],
                'content_html' => '<ul><li>Petit déjeuner inclus</li><li>Loisirs ou programme optionnel</li></ul>',
            ],
            [
                'title' => 'Journée libre',
                'city' => null,
                'day_label' => 'libre',
                'nights' => 1,
                'day_type' => 'libre',
                'meals_json' => ['breakfast' => true, 'lunch' => false, 'dinner' => false],
                'content_html' => '<ul><li>Petit déjeuner inclus</li><li>Loisirs ou programme optionnel</li></ul>',
            ],
            [
                'title' => 'Dubaï ✈ Casablanca',
                'city' => 'Dubaï',
                'day_label' => null,
                'nights' => 0,
                'day_type' => 'transfert',
                'meals_json' => ['breakfast' => true, 'lunch' => false, 'dinner' => false],
                'content_html' => '<ul><li>Petit déjeuner inclus</li><li>Check-out</li><li>Transfert aéroport</li><li>Vol retour</li></ul>',
            ],
        ];

        TravelProgramDay::where('voyage_id', $voyage->id)->delete();
        foreach ($days as $i => $data) {
            TravelProgramDay::create([
                'voyage_id' => $voyage->id,
                'day_number' => $i + 1,
                'title' => $data['title'],
                'city' => $data['city'],
                'day_label' => $data['day_label'],
                'nights' => $data['nights'],
                'day_type' => $data['day_type'],
                'meals_json' => $data['meals_json'],
                'content_html' => $data['content_html'],
                'meal_breakfast' => $data['meals_json']['breakfast'],
                'meal_lunch' => $data['meals_json']['lunch'],
                'meal_dinner' => $data['meals_json']['dinner'],
            ]);
        }
    }
}
