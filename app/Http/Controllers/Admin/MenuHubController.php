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
                'title' => 'Hebergement',
                'subtitle' => 'Acces rapide aux outils d hebergement relies a Ajinsafro.',
                'links' => $this->links([
                    ['label' => 'Hotels WordPress', 'route' => 'admin.wordpress.hotels.index', 'description' => 'Catalogue et CRUD des hotels WordPress.'],
                    ['label' => 'Packs hebergement', 'route' => 'admin.accommodation-packages.index', 'description' => 'Gestion des packs hebergement.'],
                ]),
            ],
            'hajj-omra' => [
                'title' => 'Hajj & Omra',
                'subtitle' => 'Gestion des offres et des demandes Hajj & Omra.',
                'links' => $this->links([
                    ['label' => 'Gestion des offres', 'route' => 'admin.hajj-omra.index', 'description' => 'CRUD complet des offres Hajj & Omra.'],
                    ['label' => 'Demandes clients', 'route' => 'admin.hajj-omra.requests.index', 'description' => 'Suivi et traitement des demandes.'],
                ]),
            ],
            'low-cost' => [
                'title' => 'Formule low cost',
                'subtitle' => 'Gestion des offres low cost et des demandes associees.',
                'links' => $this->links([
                    ['label' => 'Gestion des offres', 'route' => 'admin.economic-offers.index', 'description' => 'CRUD complet des offres low cost.'],
                    ['label' => 'Demandes clients', 'route' => 'admin.economic-offers.requests.index', 'description' => 'Suivi et traitement des demandes low cost.'],
                ]),
            ],
            'activites' => [
                'title' => 'Activite',
                'subtitle' => 'Gestion centralisee des activites et de leurs parametrages.',
                'links' => $this->links([
                    ['label' => 'Offres activite', 'route' => 'admin.activity-offers.index', 'description' => 'Catalogue et CRUD des offres activite.'],
                    ['label' => 'Base activites', 'route' => 'admin.circuits.activities.index', 'description' => 'Gestion des activites rattachees aux circuits.'],
                    ['label' => 'Categories', 'route' => 'admin.activities.categories', 'description' => 'Organisation des categories activite.'],
                    ['label' => 'Disponibilites', 'route' => 'admin.activities.availability', 'description' => 'Pilotage des disponibilites.'],
                ]),
            ],
            'transfers' => [
                'title' => 'Transfer',
                'subtitle' => 'Gestion des transferts, vehicules, tarifs et disponibilites.',
                'links' => $this->links([
                    ['label' => 'Offres transfer', 'route' => 'admin.circuits.tour-transfers.index', 'description' => 'CRUD des transferts rattaches aux voyages.'],
                    ['label' => 'Vehicules', 'route' => 'admin.transfers.vehicles', 'description' => 'Gestion du parc vehicules.'],
                    ['label' => 'Tarifs', 'route' => 'admin.transfers.pricing', 'description' => 'Parametrage des grilles tarifaires.'],
                    ['label' => 'Disponibilites', 'route' => 'admin.transfers.availability', 'description' => 'Suivi de disponibilite des transferts.'],
                ]),
            ],
            'visa' => [
                'title' => 'Visa',
                'subtitle' => 'Module visa en evolution avec acces aux pages actuelles.',
                'status' => 'En cours',
                'links' => $this->links([
                    ['label' => 'Vue generale visa', 'route' => 'admin.visa.index', 'description' => 'Page d entree du module visa.'],
                    ['label' => 'Demandes visa', 'route' => 'admin.visa.demandes-visa', 'description' => 'Suivi des demandes de visa.'],
                    ['label' => 'Documents', 'route' => 'admin.visa.documents', 'description' => 'Documents et pieces visa.'],
                ]),
            ],
            'rh' => [
                'title' => 'Gestion RH',
                'subtitle' => 'Gestion des comptes et des employes de votre point de vente.',
                'links' => $this->links([
                    ['label' => 'Utilisateurs', 'route' => 'admin.settings.utilisateurs', 'description' => 'Comptes et acces des utilisateurs.', 'permission' => 'settings.users.manage'],
                    ['label' => 'Employes du point de vente', 'route' => 'admin.agency-employees.index', 'description' => 'Fiches employes et creation de comptes.', 'permission' => ['agency_employees.view', 'pos_employees.view']],
                    ['label' => 'Comptes du point de vente', 'route' => 'admin.agency-accounts.index', 'description' => 'Comptes de connexion des employes.', 'permission' => 'agency_accounts.view'],
                    ['label' => 'Roles & permissions', 'route' => 'admin.settings.roles-permissions', 'description' => 'Parametrage des droits et roles.', 'permission' => 'settings.roles.manage'],
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
