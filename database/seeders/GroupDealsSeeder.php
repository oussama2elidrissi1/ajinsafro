<?php

namespace Database\Seeders;

use App\Models\GroupDeal;
use App\Models\GroupDealCategory;
use App\Models\GroupDealParticipant;
use App\Models\GroupDealPricingTier;
use App\Models\GroupDealServiceItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GroupDealsSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = $this->catalog();

        foreach ($catalog as $dealIndex => $payload) {
            $tiers = $payload['tiers'];
            $currentParticipants = (int) $payload['current_participants'];
            $startingPrice = (float) $tiers[0]['price_per_person'];
            $currentPrice = (float) $this->activeTierPrice($tiers, $currentParticipants);
            $discountPercent = $startingPrice > 0 && $currentPrice > 0 && $currentPrice < $startingPrice
                ? (int) round((($startingPrice - $currentPrice) / $startingPrice) * 100)
                : 0;

            $deal = GroupDeal::updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'title' => $payload['title'],
                    'destination' => $payload['destination'],
                    'country' => $payload['country'],
                    'city' => $payload['city'],
                    'description' => $payload['description'],
                    'short_description' => $payload['short_description'],
                    'image' => $payload['image'],
                    'duration_days' => $payload['duration_days'],
                    'duration_nights' => $payload['duration_nights'],
                    'min_participants' => $payload['min_participants'],
                    'max_participants' => $payload['max_participants'],
                    'current_participants' => $currentParticipants,
                    'starting_price' => $startingPrice,
                    'current_price' => $currentPrice,
                    'discount_percent' => $discountPercent,
                    'status' => $payload['status'],
                    'badge_label' => $payload['badge_label'],
                    'departure_date' => $payload['departure_date'],
                    'return_date' => $payload['return_date'],
                    'registration_deadline' => $payload['registration_deadline'],
                    'is_featured' => $payload['is_featured'],
                    'is_active' => true,
                    'sort_order' => $payload['sort_order'] ?? ($dealIndex + 1),
                    'program' => $payload['program'],
                    'services_included' => $payload['services_included'],
                    'services_excluded' => $payload['services_excluded'],
                    'share_enabled' => true,
                ]
            );

            $deal->priceTiers()->delete();
            foreach ($tiers as $tierIndex => $tier) {
                $deal->priceTiers()->create([
                    'min_people' => $tier['min_people'],
                    'max_people' => $tier['max_people'],
                    'price_per_person' => $tier['price_per_person'],
                    'label' => $tier['label'],
                    'sort_order' => $tier['sort_order'] ?? ($tierIndex + 1),
                ]);
            }

            $deal->services()->delete();
            foreach ($payload['services_included'] as $serviceIndex => $serviceName) {
                $deal->services()->create([
                    'name' => $serviceName,
                    'type' => GroupDealServiceItem::TYPE_INCLUDED,
                    'sort_order' => $serviceIndex + 1,
                ]);
            }
            foreach ($payload['services_excluded'] as $serviceIndex => $serviceName) {
                $deal->services()->create([
                    'name' => $serviceName,
                    'type' => GroupDealServiceItem::TYPE_NOT_INCLUDED,
                    'sort_order' => count($payload['services_included']) + $serviceIndex + 1,
                ]);
            }

            $categoryIds = collect($payload['categories'])
                ->map(function (array $category, int $categoryIndex) {
                    return GroupDealCategory::updateOrCreate(
                        ['slug' => $category['slug']],
                        [
                            'name' => $category['name'],
                            'sort_order' => $category['sort_order'] ?? ($categoryIndex + 1),
                            'is_active' => true,
                        ]
                    )->id;
                })
                ->all();
            $deal->categories()->sync($categoryIds);

            $deal->participants()
                ->whereNull('user_id')
                ->where('email', 'seed+' . $deal->slug . '@ajinsafro.test')
                ->delete();

            $deal->participants()->create([
                'full_name' => 'Seeded Group ' . Str::title($deal->destination ?? $deal->title),
                'email' => 'seed+' . $deal->slug . '@ajinsafro.test',
                'phone' => '+212600000000',
                'participants_count' => $currentParticipants,
                'status' => GroupDealParticipant::STATUS_CONFIRMED,
                'selected_price' => $currentPrice,
                'payment_status' => GroupDealParticipant::PAYMENT_PENDING,
                'joined_at' => now()->subDays(2),
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function catalog(): array
    {
        return [
            [
                'title' => 'Sejour 5 jours / 4 nuits a Barcelone',
                'slug' => 'sejour-5-jours-4-nuits-barcelone',
                'destination' => 'Barcelone',
                'country' => 'Espagne',
                'city' => 'Barcelone',
                'short_description' => 'City break mediterraneen en groupe avec vols, hotel central et visites essentielles.',
                'description' => 'Explorez Barcelone en petit groupe avec une formule complete: vols, hotel, transferts et immersion entre Gaudi, tapas et front de mer.',
                'program' => "Jour 1: arrivee et installation.\nJour 2: Sagrada Familia et quartier gothique.\nJour 3: Montjuic et temps libre.\nJour 4: Costa et shopping.\nJour 5: retour.",
                'image' => 'https://images.unsplash.com/photo-1543783207-ec64e4d95325?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 5,
                'duration_nights' => 4,
                'min_participants' => 4,
                'max_participants' => 20,
                'current_participants' => 11,
                'status' => GroupDeal::STATUS_PUBLISHED,
                'badge_label' => 'Presque garanti',
                'departure_date' => '2026-06-18',
                'return_date' => '2026-06-22',
                'registration_deadline' => '2026-06-05',
                'is_featured' => true,
                'sort_order' => 1,
                'services_included' => ['Vol aller-retour', 'Hotel 4* centre-ville', 'Petit-dejeuner', 'Transferts aeroport'],
                'services_excluded' => ['Dejeuners et diners', 'Visa si necessaire', 'Assurance voyage'],
                'categories' => [['name' => 'Amis', 'slug' => 'amis'], ['name' => 'City Break', 'slug' => 'city-break']],
                'tiers' => [
                    ['min_people' => 4, 'max_people' => 8, 'price_per_person' => 9000, 'label' => 'Lancement'],
                    ['min_people' => 9, 'max_people' => 14, 'price_per_person' => 8500, 'label' => 'Croissance'],
                    ['min_people' => 15, 'max_people' => 20, 'price_per_person' => 8000, 'label' => 'Meilleur prix'],
                ],
            ],
            [
                'title' => 'Marrakech & Atlas en groupe',
                'slug' => 'marrakech-atlas-groupe',
                'destination' => 'Marrakech & Atlas',
                'country' => 'Maroc',
                'city' => 'Marrakech',
                'short_description' => 'Riad, vallees et soiree marocaine pour un groupe loisir ou famille.',
                'description' => 'Une escapade marocaine complete entre Marrakech, vallee de l Ourika et immersion dans l Atlas avec activites partagees et logistique Ajinsafro.',
                'program' => "Jour 1: Marrakech.\nJour 2: Medina et jardins.\nJour 3: Atlas et Ourika.\nJour 4: soiree folklorique.",
                'image' => 'https://images.unsplash.com/photo-1597212618440-806262de4f6b?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 4,
                'duration_nights' => 3,
                'min_participants' => 6,
                'max_participants' => 20,
                'current_participants' => 14,
                'status' => GroupDeal::STATUS_GUARANTEED,
                'badge_label' => 'Garanti',
                'departure_date' => '2026-06-12',
                'return_date' => '2026-06-15',
                'registration_deadline' => '2026-05-30',
                'is_featured' => true,
                'sort_order' => 2,
                'services_included' => ['Transport prive', 'Riad 4*', 'Petit-dejeuner', 'Guide local'],
                'services_excluded' => ['Vol domestique', 'Depenses personnelles'],
                'categories' => [['name' => 'Famille', 'slug' => 'famille'], ['name' => 'Culture', 'slug' => 'culture']],
                'tiers' => [
                    ['min_people' => 6, 'max_people' => 9, 'price_per_person' => 4200, 'label' => 'Base groupe'],
                    ['min_people' => 10, 'max_people' => 14, 'price_per_person' => 3900, 'label' => 'Palier confort'],
                    ['min_people' => 15, 'max_people' => 20, 'price_per_person' => 3600, 'label' => 'Tarif maximal'],
                ],
            ],
            [
                'title' => 'Chefchaouen & Tetouan',
                'slug' => 'chefchaouen-tetouan-groupe',
                'destination' => 'Chefchaouen',
                'country' => 'Maroc',
                'city' => 'Chefchaouen',
                'short_description' => 'Circuit nord du Maroc avec medinas, panorama rifain et nuits boutique.',
                'description' => 'Voyage de groupe au nord du Maroc avec deux villes a forte identite, rythme doux et experience photo ideale.',
                'program' => "Jour 1: Tetouan.\nJour 2: Chefchaouen.\nJour 3: cascades d Akchour.\nJour 4: shopping et retour.\nJour 5: depart.",
                'image' => 'https://images.unsplash.com/photo-1548013146-72479768bada?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 5,
                'duration_nights' => 4,
                'min_participants' => 8,
                'max_participants' => 16,
                'current_participants' => 8,
                'status' => GroupDeal::STATUS_PUBLISHED,
                'badge_label' => 'Presque garanti',
                'departure_date' => '2026-07-03',
                'return_date' => '2026-07-07',
                'registration_deadline' => '2026-06-22',
                'is_featured' => false,
                'sort_order' => 3,
                'services_included' => ['Hotel de charme', 'Transport climatise', 'Guide', 'Petit-dejeuner'],
                'services_excluded' => ['Repas hors petit-dejeuner', 'Assurance'],
                'categories' => [['name' => 'Amis', 'slug' => 'amis'], ['name' => 'Culture', 'slug' => 'culture']],
                'tiers' => [
                    ['min_people' => 8, 'max_people' => 10, 'price_per_person' => 4800, 'label' => 'Ouverture'],
                    ['min_people' => 11, 'max_people' => 13, 'price_per_person' => 4500, 'label' => 'Palier bleu'],
                    ['min_people' => 14, 'max_people' => 16, 'price_per_person' => 4200, 'label' => 'Palier premium'],
                ],
            ],
            [
                'title' => 'Dakhla Surf & Desert',
                'slug' => 'dakhla-surf-desert',
                'destination' => 'Dakhla',
                'country' => 'Maroc',
                'city' => 'Dakhla',
                'short_description' => 'Lagune, surf camp et bivouac desert pour un groupe aventure.',
                'description' => 'Un sejour energique a Dakhla avec sessions glisse, lodge au bord de la lagune et sortie coucher de soleil dans le desert.',
                'program' => "Jour 1: arrivee lodge.\nJour 2: surf et lagune.\nJour 3: excursion desert.\nJour 4: ocean et retour.",
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 4,
                'duration_nights' => 3,
                'min_participants' => 10,
                'max_participants' => 16,
                'current_participants' => 15,
                'status' => GroupDeal::STATUS_GUARANTEED,
                'badge_label' => 'Dernieres places',
                'departure_date' => '2026-07-17',
                'return_date' => '2026-07-20',
                'registration_deadline' => '2026-07-01',
                'is_featured' => true,
                'sort_order' => 4,
                'services_included' => ['Vol domestique', 'Lodge lagune', 'Petit-dejeuner', 'Transferts'],
                'services_excluded' => ['Location materiel complet', 'Boissons'],
                'categories' => [['name' => 'Aventure', 'slug' => 'aventure'], ['name' => 'Surf', 'slug' => 'surf']],
                'tiers' => [
                    ['min_people' => 10, 'max_people' => 12, 'price_per_person' => 6900, 'label' => 'Depart aventure'],
                    ['min_people' => 13, 'max_people' => 14, 'price_per_person' => 6500, 'label' => 'Lagune active'],
                    ['min_people' => 15, 'max_people' => 16, 'price_per_person' => 6200, 'label' => 'Derniere vague'],
                ],
            ],
            [
                'title' => 'Istanbul City Break',
                'slug' => 'istanbul-city-break',
                'destination' => 'Istanbul',
                'country' => 'Turquie',
                'city' => 'Istanbul',
                'short_description' => 'Mosquees, Bosphore et shopping dans un format court, efficace et premium.',
                'description' => 'Istanbul en groupe avec city tour, croisiere Bosphore, hotel bien place et accompagnement francophone.',
                'program' => "Jour 1: arrivee.\nJour 2: Sultanahmet.\nJour 3: Bosphore et bazar.\nJour 4: retour.",
                'image' => 'https://images.unsplash.com/photo-1527838832700-5059252407fa?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 4,
                'duration_nights' => 3,
                'min_participants' => 8,
                'max_participants' => 22,
                'current_participants' => 18,
                'status' => GroupDeal::STATUS_GUARANTEED,
                'badge_label' => 'Garanti',
                'departure_date' => '2026-09-10',
                'return_date' => '2026-09-13',
                'registration_deadline' => '2026-08-18',
                'is_featured' => true,
                'sort_order' => 5,
                'services_included' => ['Vol aller-retour', 'Hotel 4*', 'Petit-dejeuner', 'Transferts', 'Guide'],
                'services_excluded' => ['Repas libres', 'Assurance multirisque'],
                'categories' => [['name' => 'City Break', 'slug' => 'city-break'], ['name' => 'Amis', 'slug' => 'amis']],
                'tiers' => [
                    ['min_people' => 8, 'max_people' => 11, 'price_per_person' => 7900, 'label' => 'Ville ouverte'],
                    ['min_people' => 12, 'max_people' => 17, 'price_per_person' => 7400, 'label' => 'Bosphore'],
                    ['min_people' => 18, 'max_people' => 22, 'price_per_person' => 6900, 'label' => 'Grand groupe'],
                ],
            ],
            [
                'title' => 'Omra Groupe Economique',
                'slug' => 'omra-groupe-economique',
                'destination' => 'Mecque & Medine',
                'country' => 'Arabie Saoudite',
                'city' => 'La Mecque',
                'short_description' => 'Formule Omra accompagnee, economique et structuree pour groupe spirituel.',
                'description' => 'Une Omra en groupe avec encadrement, logistique simplifiee et hotels bien positionnes selon le budget choisi.',
                'program' => "Depart Maroc.\nSequence Medine.\nTransfert Mecque.\nRites et temps spirituel.\nRetour.",
                'image' => 'https://images.unsplash.com/photo-1564769625905-50e93615e769?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 10,
                'duration_nights' => 9,
                'min_participants' => 20,
                'max_participants' => 40,
                'current_participants' => 23,
                'status' => GroupDeal::STATUS_GUARANTEED,
                'badge_label' => 'Depart garanti',
                'departure_date' => '2026-10-05',
                'return_date' => '2026-10-14',
                'registration_deadline' => '2026-09-01',
                'is_featured' => true,
                'sort_order' => 6,
                'services_included' => ['Vol', 'Hotel', 'Visa', 'Transferts', 'Accompagnateur Omra'],
                'services_excluded' => ['Depenses personnelles', 'Repas supplements'],
                'categories' => [['name' => 'Spirituel', 'slug' => 'spirituel']],
                'tiers' => [
                    ['min_people' => 20, 'max_people' => 24, 'price_per_person' => 18900, 'label' => 'Base Omra'],
                    ['min_people' => 25, 'max_people' => 32, 'price_per_person' => 18100, 'label' => 'Palier baraka'],
                    ['min_people' => 33, 'max_people' => 40, 'price_per_person' => 17400, 'label' => 'Palier groupe complet'],
                ],
            ],
            [
                'title' => 'Andalousie en groupe',
                'slug' => 'andalousie-groupe',
                'destination' => 'Andalousie',
                'country' => 'Espagne',
                'city' => 'Seville',
                'short_description' => 'Seville, Cordoue et Grenade en version culture et soleil.',
                'description' => 'Circuit andalou en groupe avec patrimoine, ambiance hispanique et prix optimises a mesure que le groupe grandit.',
                'program' => "Jour 1: Seville.\nJour 2: Cordoue.\nJour 3: Grenade.\nJour 4: Alhambra.\nJour 5: libre.\nJour 6: retour.",
                'image' => 'https://images.unsplash.com/photo-1509840841025-9088ba78a826?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 6,
                'duration_nights' => 5,
                'min_participants' => 10,
                'max_participants' => 24,
                'current_participants' => 12,
                'status' => GroupDeal::STATUS_PUBLISHED,
                'badge_label' => 'Promo groupe',
                'departure_date' => '2026-09-24',
                'return_date' => '2026-09-29',
                'registration_deadline' => '2026-09-05',
                'is_featured' => false,
                'sort_order' => 7,
                'services_included' => ['Vol', 'Hotels 4*', 'Transferts', 'Guide', 'Petit-dejeuner'],
                'services_excluded' => ['Taxe locale', 'Repas principaux'],
                'categories' => [['name' => 'Culture', 'slug' => 'culture'], ['name' => 'Amis', 'slug' => 'amis']],
                'tiers' => [
                    ['min_people' => 10, 'max_people' => 13, 'price_per_person' => 9300, 'label' => 'Circuit base'],
                    ['min_people' => 14, 'max_people' => 18, 'price_per_person' => 8900, 'label' => 'Circuit soleil'],
                    ['min_people' => 19, 'max_people' => 24, 'price_per_person' => 8400, 'label' => 'Circuit premium'],
                ],
            ],
            [
                'title' => 'Paris Week-end Groupe',
                'slug' => 'paris-weekend-groupe',
                'destination' => 'Paris',
                'country' => 'France',
                'city' => 'Paris',
                'short_description' => 'Week-end express entre monuments, shopping et ambiance parisienne.',
                'description' => 'Escapade parisienne courte, tres demandee par les groupes d amis, associations et incentives urbains.',
                'program' => "Jour 1: arrivee.\nJour 2: tour panoramique.\nJour 3: libre et retour.",
                'image' => 'https://images.unsplash.com/photo-1431274172761-fca41d930114?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 3,
                'duration_nights' => 2,
                'min_participants' => 10,
                'max_participants' => 18,
                'current_participants' => 10,
                'status' => GroupDeal::STATUS_GUARANTEED,
                'badge_label' => 'Garanti',
                'departure_date' => '2026-11-13',
                'return_date' => '2026-11-15',
                'registration_deadline' => '2026-10-20',
                'is_featured' => false,
                'sort_order' => 8,
                'services_included' => ['Vol', 'Hotel 3*', 'Petit-dejeuner', 'Transferts'],
                'services_excluded' => ['Bagage supplementaire', 'Tickets attractions'],
                'categories' => [['name' => 'City Break', 'slug' => 'city-break'], ['name' => 'Association', 'slug' => 'association']],
                'tiers' => [
                    ['min_people' => 10, 'max_people' => 12, 'price_per_person' => 7600, 'label' => 'Paris base'],
                    ['min_people' => 13, 'max_people' => 15, 'price_per_person' => 7200, 'label' => 'Paris plus'],
                    ['min_people' => 16, 'max_people' => 18, 'price_per_person' => 6800, 'label' => 'Paris max'],
                ],
            ],
            [
                'title' => 'Zanzibar Evasion Groupe',
                'slug' => 'zanzibar-evasion-groupe',
                'destination' => 'Zanzibar',
                'country' => 'Tanzanie',
                'city' => 'Stone Town',
                'short_description' => 'Plage, dhow sunset et detente tropicale pour groupe en quete d evasion.',
                'description' => 'Une parenthese tropicale avec hotel bord de mer, activites douces et experience premium ajustee a la taille du groupe.',
                'program' => "Jour 1: arrivee.\nJour 2: Stone Town.\nJour 3: plage.\nJour 4: safari bleu.\nJour 5: libre.\nJour 6: retour.",
                'image' => 'https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 6,
                'duration_nights' => 5,
                'min_participants' => 12,
                'max_participants' => 20,
                'current_participants' => 9,
                'status' => GroupDeal::STATUS_PUBLISHED,
                'badge_label' => 'En cours',
                'departure_date' => '2026-12-04',
                'return_date' => '2026-12-09',
                'registration_deadline' => '2026-11-01',
                'is_featured' => true,
                'sort_order' => 9,
                'services_included' => ['Vol', 'Resort 4*', 'Petit-dejeuner', 'Transferts', 'Excursion sunset'],
                'services_excluded' => ['Assurance annulation', 'Taxes locales'],
                'categories' => [['name' => 'Famille', 'slug' => 'famille'], ['name' => 'Plage', 'slug' => 'plage']],
                'tiers' => [
                    ['min_people' => 6, 'max_people' => 11, 'price_per_person' => 14500, 'label' => 'Ile base'],
                    ['min_people' => 12, 'max_people' => 16, 'price_per_person' => 13600, 'label' => 'Ile soleil'],
                    ['min_people' => 17, 'max_people' => 20, 'price_per_person' => 12900, 'label' => 'Ile premium'],
                ],
            ],
            [
                'title' => 'Cappadoce & Istanbul',
                'slug' => 'cappadoce-istanbul',
                'destination' => 'Cappadoce & Istanbul',
                'country' => 'Turquie',
                'city' => 'Nevsehir',
                'short_description' => 'Double experience Turquie entre montgolfieres et Bosphore.',
                'description' => 'Un itineraire iconique pour groupes qui veulent allier paysage, culture et shopping dans une seule offre.',
                'program' => "Jour 1: Istanbul.\nJour 2: Bosphore.\nJour 3: Cappadoce.\nJour 4: vallees.\nJour 5: montgolfiere option.\nJour 6: retour.",
                'image' => 'https://images.unsplash.com/photo-1641128322298-4f0d4f1c3ed5?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 7,
                'duration_nights' => 6,
                'min_participants' => 12,
                'max_participants' => 24,
                'current_participants' => 16,
                'status' => GroupDeal::STATUS_GUARANTEED,
                'badge_label' => 'Garanti',
                'departure_date' => '2026-10-22',
                'return_date' => '2026-10-28',
                'registration_deadline' => '2026-10-01',
                'is_featured' => true,
                'sort_order' => 10,
                'services_included' => ['Vol', 'Hotels 4*', 'Transferts', 'Guide', 'Petit-dejeuner'],
                'services_excluded' => ['Montgolfiere optionnelle', 'Repas libres'],
                'categories' => [['name' => 'Culture', 'slug' => 'culture'], ['name' => 'Amis', 'slug' => 'amis']],
                'tiers' => [
                    ['min_people' => 12, 'max_people' => 15, 'price_per_person' => 11900, 'label' => 'Turquie base'],
                    ['min_people' => 16, 'max_people' => 19, 'price_per_person' => 11200, 'label' => 'Turquie plus'],
                    ['min_people' => 20, 'max_people' => 24, 'price_per_person' => 10500, 'label' => 'Turquie max'],
                ],
            ],
            [
                'title' => 'Agadir Team Building',
                'slug' => 'agadir-team-building',
                'destination' => 'Agadir',
                'country' => 'Maroc',
                'city' => 'Agadir',
                'short_description' => 'Offre entreprise avec hebergement, ateliers et activites de cohesion.',
                'description' => 'Un format corporate souple pour entreprises et equipes avec ateliers, plage, detente et logistique simplifiee.',
                'program' => "Jour 1: installation et session d ouverture.\nJour 2: ateliers et activites nautiques.\nJour 3: bilan et retour.",
                'image' => 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 3,
                'duration_nights' => 2,
                'min_participants' => 15,
                'max_participants' => 50,
                'current_participants' => 21,
                'status' => GroupDeal::STATUS_GUARANTEED,
                'badge_label' => 'Entreprise',
                'departure_date' => '2026-06-26',
                'return_date' => '2026-06-28',
                'registration_deadline' => '2026-06-10',
                'is_featured' => false,
                'sort_order' => 11,
                'services_included' => ['Hotel 4*', 'Salles de reunion', 'Coffee break', 'Transferts', 'Animation equipe'],
                'services_excluded' => ['Vols', 'Extras minibar'],
                'categories' => [['name' => 'Entreprise', 'slug' => 'entreprise']],
                'tiers' => [
                    ['min_people' => 15, 'max_people' => 24, 'price_per_person' => 3200, 'label' => 'Equipe base'],
                    ['min_people' => 25, 'max_people' => 35, 'price_per_person' => 2900, 'label' => 'Equipe boost'],
                    ['min_people' => 36, 'max_people' => 50, 'price_per_person' => 2600, 'label' => 'Equipe XXL'],
                ],
            ],
            [
                'title' => 'Egypte Pyramides & Nil',
                'slug' => 'egypte-pyramides-nil',
                'destination' => 'Le Caire & Nil',
                'country' => 'Egypte',
                'city' => 'Le Caire',
                'short_description' => 'Pyramides, croisiere et heritage pharaonique dans un circuit groupe complet.',
                'description' => 'Circuit signature Egypte avec Le Caire, croisiere sur le Nil et visites majeures optimisees pour groupes loisirs ou culture.',
                'program' => "Jour 1: arrivee Caire.\nJour 2: pyramides.\nJour 3: vol interieur.\nJour 4-6: croisiere Nil.\nJour 7: retour.",
                'image' => 'https://images.unsplash.com/photo-1539768942893-daf53e448371?auto=format&fit=crop&w=1200&q=80',
                'duration_days' => 7,
                'duration_nights' => 6,
                'min_participants' => 10,
                'max_participants' => 28,
                'current_participants' => 28,
                'status' => GroupDeal::STATUS_CLOSED,
                'badge_label' => 'Complet',
                'departure_date' => '2026-08-14',
                'return_date' => '2026-08-20',
                'registration_deadline' => '2026-07-20',
                'is_featured' => true,
                'sort_order' => 12,
                'services_included' => ['Vol international', 'Vol interieur', 'Croisiere 5*', 'Guide', 'Transferts'],
                'services_excluded' => ['Visa Egypte', 'Boissons', 'Pourboires'],
                'categories' => [['name' => 'Culture', 'slug' => 'culture'], ['name' => 'Association', 'slug' => 'association']],
                'tiers' => [
                    ['min_people' => 10, 'max_people' => 15, 'price_per_person' => 13900, 'label' => 'Nil base'],
                    ['min_people' => 16, 'max_people' => 22, 'price_per_person' => 13100, 'label' => 'Nil plus'],
                    ['min_people' => 23, 'max_people' => 28, 'price_per_person' => 12400, 'label' => 'Nil complet'],
                ],
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tiers
     */
    protected function activeTierPrice(array $tiers, int $currentParticipants): float
    {
        $matching = collect($tiers)
            ->filter(function (array $tier) use ($currentParticipants) {
                return $currentParticipants >= (int) $tier['min_people']
                    && ($tier['max_people'] === null || $currentParticipants <= (int) $tier['max_people']);
            })
            ->sortByDesc('min_people')
            ->first();

        if ($matching) {
            return (float) $matching['price_per_person'];
        }

        return (float) $tiers[0]['price_per_person'];
    }
}
