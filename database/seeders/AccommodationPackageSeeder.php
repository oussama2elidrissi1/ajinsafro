<?php

namespace Database\Seeders;

use App\Models\AccommodationPackage;
use Illuminate\Database\Seeder;

class AccommodationPackageSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'title' => 'Pack 4 jours / 3 nuits à Marrakech avec petit-déjeuner',
                'slug' => 'pack-marrakech-4j-3n-petit-dejeuner',
                'country' => 'Maroc',
                'city' => 'Marrakech',
                'duration_days' => 4,
                'nights' => 3,
                'pension_type' => 'Petit-déjeuner inclus',
                'accommodation_type' => 'Riad',
                'badge' => 'Pack Ajinsafro',
                'short_description' => 'Séjour prêt à réserver à Marrakech avec hébergement sélectionné, petit-déjeuner et assistance Ajinsafro.',
                'includes' => ['Hébergement', 'Petit-déjeuner', 'Assistance Ajinsafro', 'Transfert optionnel'],
                'image_url' => 'https://images.unsplash.com/photo-1597212618440-806262de4f6b?auto=format&fit=crop&w=1200&q=80',
                'price_from' => 1900,
                'currency' => 'MAD',
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Pack Dakhla 5 jours / 4 nuits avec demi-pension',
                'slug' => 'pack-dakhla-5j-4n-demi-pension',
                'country' => 'Maroc',
                'city' => 'Dakhla',
                'duration_days' => 5,
                'nights' => 4,
                'pension_type' => 'Demi-pension',
                'accommodation_type' => 'Hôtel',
                'badge' => 'Demi-pension',
                'short_description' => 'Pack idéal pour profiter de Dakhla avec hébergement, demi-pension et assistance pendant le séjour.',
                'includes' => ['Hébergement', 'Demi-pension', 'Assistance Ajinsafro', 'Activité optionnelle'],
                'image_url' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80',
                'price_from' => 3980,
                'currency' => 'MAD',
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Pack Tanger Weekend 3 jours / 2 nuits',
                'slug' => 'pack-tanger-weekend-3j-2n',
                'country' => 'Maroc',
                'city' => 'Tanger',
                'duration_days' => 3,
                'nights' => 2,
                'pension_type' => 'Petit-déjeuner inclus',
                'accommodation_type' => 'Hôtel',
                'badge' => 'Weekend',
                'short_description' => 'Offre courte durée à Tanger avec hôtel, petit-déjeuner et assistance Ajinsafro.',
                'includes' => ['Hébergement', 'Petit-déjeuner', 'Assistance Ajinsafro', 'Support réservation'],
                'image_url' => 'https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&w=1200&q=80',
                'price_from' => 1200,
                'currency' => 'MAD',
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Pack Agadir Famille 4 jours / 3 nuits en pension complète',
                'slug' => 'pack-agadir-famille-4j-3n-pension-complete',
                'country' => 'Maroc',
                'city' => 'Agadir',
                'duration_days' => 4,
                'nights' => 3,
                'pension_type' => 'Pension complète',
                'accommodation_type' => 'Hôtel',
                'badge' => 'Famille',
                'short_description' => 'Pack famille à Agadir avec pension complète, hébergement confortable et accompagnement Ajinsafro.',
                'includes' => ['Hébergement', 'Pension complète', 'Assistance Ajinsafro', 'Offre famille'],
                'image_url' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80',
                'price_from' => 2700,
                'currency' => 'MAD',
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'title' => 'Pack Chefchaouen 3 jours / 2 nuits charme et détente',
                'slug' => 'pack-chefchaouen-3j-2n-charme-detente',
                'country' => 'Maroc',
                'city' => 'Chefchaouen',
                'duration_days' => 3,
                'nights' => 2,
                'pension_type' => 'Petit-déjeuner inclus',
                'accommodation_type' => 'Maison d’hôtes',
                'badge' => 'Nature',
                'short_description' => 'Séjour détente à Chefchaouen avec maison d’hôtes sélectionnée et petit-déjeuner inclus.',
                'includes' => ['Hébergement', 'Petit-déjeuner', 'Assistance Ajinsafro', 'Conseils locaux'],
                'image_url' => 'https://images.unsplash.com/photo-1523531294919-4bcd7c65e216?auto=format&fit=crop&w=1200&q=80',
                'price_from' => 1450,
                'currency' => 'MAD',
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'title' => 'Pack Fès Culture 4 jours / 3 nuits',
                'slug' => 'pack-fes-culture-4j-3n',
                'country' => 'Maroc',
                'city' => 'Fès',
                'duration_days' => 4,
                'nights' => 3,
                'pension_type' => 'Petit-déjeuner inclus',
                'accommodation_type' => 'Riad',
                'badge' => 'Culture',
                'short_description' => 'Pack culturel à Fès avec riad sélectionné, petit-déjeuner et assistance Ajinsafro.',
                'includes' => ['Hébergement', 'Petit-déjeuner', 'Assistance Ajinsafro', 'Guide optionnel'],
                'image_url' => 'https://images.unsplash.com/photo-1539650116574-75c0c6d73f56?auto=format&fit=crop&w=1200&q=80',
                'price_from' => 1750,
                'currency' => 'MAD',
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($rows as $row) {
            AccommodationPackage::updateOrCreate(
                ['slug' => $row['slug']],
                $row
            );
        }
    }
}
