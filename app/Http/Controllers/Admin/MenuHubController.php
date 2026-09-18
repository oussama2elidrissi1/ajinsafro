<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

class MenuHubController extends Controller
{
    public function show(string $page): View
    {
        $pages = $this->pages();

        abort_unless(isset($pages[$page]), 404);

        $config = $pages[$page];

        return view('admin.menu-hubs.show', [
            'title' => $config['title'],
            'subtitle' => $config['subtitle'],
            'status' => $config['status'] ?? null,
            'links' => $config['links'] ?? [],
            'pageTitle' => $config['title'],
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => route('admin.dashboard')],
                ['label' => 'Menu admin'],
                ['label' => $config['title']],
            ],
        ]);
    }

    private function pages(): array
    {
        return [
            'billetterie' => [
                'title' => 'Billetterie',
                'subtitle' => 'Module en cours de structuration. Cette page centralisera la gestion des billets et le suivi commercial.',
                'status' => 'En cours',
                'links' => [],
            ],
            'hebergement' => [
                'title' => 'Hébergement',
                'subtitle' => 'Accès rapide aux outils d’hébergement reliés à Ajinsafro.',
                'links' => $this->links([
                    ['label' => 'Tous les hôtels', 'route' => 'admin.wordpress.hotels.index', 'description' => 'Catalogue des hôtels et de leurs fiches.'],
                    ['label' => 'Packs hébergement', 'route' => 'admin.accommodation-packages.index', 'description' => 'Gestion des packs d’hébergement.'],
                ]),
            ],
            'hajj-omra' => [
                'title' => 'Hajj & Omra',
                'subtitle' => 'Gestion des offres et des demandes Hajj & Omra.',
                'links' => $this->links([
                    ['label' => 'Gestion des offres', 'route' => 'admin.hajj-omra.index', 'description' => 'Gestion complète des offres Hajj & Omra.'],
                    ['label' => 'Demandes clients', 'route' => 'admin.hajj-omra.requests.index', 'description' => 'Suivi et traitement des demandes.'],
                ]),
            ],
            'low-cost' => [
                'title' => 'Formule low cost',
                'subtitle' => 'Gestion des offres low cost et des demandes associées.',
                'links' => $this->links([
                    ['label' => 'Gestion des offres', 'route' => 'admin.economic-offers.index', 'description' => 'Gestion complète des offres low cost.'],
                    ['label' => 'Demandes clients', 'route' => 'admin.economic-offers.requests.index', 'description' => 'Suivi et traitement des demandes low cost.'],
                ]),
            ],
            'activites' => [
                'title' => 'Activité',
                'subtitle' => 'Gestion centralisée des activités et de leurs paramétrages.',
                'links' => $this->links([
                    ['label' => 'Offres activité', 'route' => 'admin.activity-offers.index', 'description' => 'Catalogue des offres d’activité.'],
                    ['label' => 'Base activités', 'route' => 'admin.circuits.activities.index', 'description' => 'Activités rattachées aux circuits.'],
                    ['label' => 'Catégories', 'route' => 'admin.activities.categories', 'description' => 'Organisation des catégories d’activité.'],
                    ['label' => 'Disponibilités', 'route' => 'admin.activities.availability', 'description' => 'Pilotage des disponibilités.'],
                ]),
            ],
            'transfers' => [
                'title' => 'Transfer',
                'subtitle' => 'Gestion des transferts, véhicules, tarifs et disponibilités.',
                'links' => $this->links([
                    ['label' => 'Transferts des circuits', 'route' => 'admin.circuits.tour-transfers.index', 'description' => 'Transferts aller et retour rattachés aux circuits.'],
                    ['label' => 'Véhicules', 'route' => 'admin.transfers.vehicles', 'description' => 'Gestion du parc de véhicules.'],
                    ['label' => 'Tarifs', 'route' => 'admin.transfers.pricing', 'description' => 'Paramétrage des grilles tarifaires.'],
                    ['label' => 'Disponibilités', 'route' => 'admin.transfers.availability', 'description' => 'Suivi de disponibilité des transferts.'],
                ]),
            ],
            'visa' => [
                'title' => 'Visa',
                'subtitle' => 'Module visa en évolution, avec accès aux pages actuelles.',
                'status' => 'En cours',
                'links' => $this->links([
                    ['label' => 'Vue générale visa', 'route' => 'admin.visa.index', 'description' => 'Page d’entrée du module visa.'],
                    ['label' => 'Demandes visa', 'route' => 'admin.visa.demandes-visa', 'description' => 'Suivi des demandes de visa.'],
                    ['label' => 'Documents', 'route' => 'admin.visa.documents', 'description' => 'Documents et pièces visa.'],
                ]),
            ],
            'rh' => [
                'title' => 'Gestion RH',
                'subtitle' => 'Gestion des comptes et des employés de votre point de vente.',
                'links' => $this->links([
                    ['label' => 'Utilisateurs', 'route' => 'admin.settings.utilisateurs', 'description' => 'Comptes et accès des utilisateurs.', 'permission' => 'settings.users.manage'],
                    ['label' => 'Employés du point de vente', 'route' => 'admin.agency-employees.index', 'description' => 'Fiches employés et création de comptes.', 'permission' => ['agency_employees.view', 'pos_employees.view']],
                    ['label' => 'Comptes du point de vente', 'route' => 'admin.agency-accounts.index', 'description' => 'Comptes de connexion des employés.', 'permission' => 'agency_accounts.view'],
                    ['label' => 'Rôles & permissions', 'route' => 'admin.settings.roles-permissions', 'description' => 'Paramétrage des droits et des rôles.', 'permission' => 'settings.roles.manage'],
                ]),
            ],
        ];
    }

    private function links(array $links): array
    {
        $user = auth()->user();

        return array_values(array_filter(array_map(function (array $link) use ($user): ?array {
            $route = $link['route'] ?? null;

            if (!is_string($route) || $route === '' || !Route::has($route)) {
                return null;
            }

            $permissions = $link['permission'] ?? null;
            if ($permissions !== null) {
                $permissions = is_array($permissions) ? $permissions : [$permissions];
                $allowed = false;
                foreach ($permissions as $permission) {
                    if ($user && is_string($permission) && $permission !== '' && $user->can($permission)) {
                        $allowed = true;
                        break;
                    }
                }
                if (!$allowed) {
                    return null;
                }
            }

            return [
                'label' => (string) $link['label'],
                'description' => (string) ($link['description'] ?? ''),
                'href' => route($route),
            ];
        }, $links)));
    }
}
