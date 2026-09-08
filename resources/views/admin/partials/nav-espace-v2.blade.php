@php
    /**
     * Navigation horizontale « Espace Admin v2 » : méga-menus desktop + tiroir mobile.
     * Les entrées sont filtrées par existence de route et par permission ($user->can()).
     * $eaCounts (optionnel) : compteurs affichés à droite des entrées, indexés par clé d'entrée.
     */
    $eaUser = auth()->user();
    $eaCounts = is_array($eaCounts ?? null) ? $eaCounts : [];
    $eaDashboardHref = \Illuminate\Support\Facades\Route::has('admin.dashboard.espace-v2')
        ? route('admin.dashboard.espace-v2')
        : route('admin.dashboard');

    $eaCan = function ($permission) use ($eaUser): bool {
        if ($permission === null) {
            return true;
        }
        if (!$eaUser || !method_exists($eaUser, 'can')) {
            return false;
        }
        foreach ((array) $permission as $perm) {
            if (!is_string($perm) || trim($perm) === '') {
                continue;
            }
            try {
                if ($eaUser->can($perm)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // permission inconnue : on continue
            }
        }
        return false;
    };

    $eaItem = function (string $key, string $label, string $routeName, array $activePatterns = [], $permission = null) use ($eaCan, $eaCounts): ?array {
        if (!\Illuminate\Support\Facades\Route::has($routeName) || !$eaCan($permission)) {
            return null;
        }
        $active = request()->routeIs($routeName);
        foreach ($activePatterns as $pattern) {
            if (request()->routeIs($pattern)) {
                $active = true;
                break;
            }
        }
        $meta = $eaCounts[$key] ?? null;

        return [
            'key' => $key,
            'label' => $label,
            'href' => route($routeName),
            'active' => $active,
            'meta' => $meta === null || $meta === '' ? '' : (string) $meta,
        ];
    };

    $eaGroup = function (string $title, array $items): ?array {
        $items = array_values(array_filter($items));
        return $items === [] ? null : ['title' => $title, 'items' => $items];
    };

    $eaCanSeeAdministration = false;
    if ($eaUser) {
        try {
            $eaCanSeeAdministration = app(\App\Services\BranchScopeService::class)->canSeeAllBranches($eaUser)
                || (method_exists($eaUser, 'isDevAdmin') && $eaUser->isDevAdmin());
        } catch (\Throwable $e) {
            $eaCanSeeAdministration = false;
        }
    }

    $eaSections = [
        'produits' => [
            'title' => 'Produits & services',
            'sub' => 'Catalogue, prestations et offres commerciales',
            'groups' => array_values(array_filter([
                $eaGroup('Voyages', [
                    $eaItem('voyages', 'Voyage', 'admin.circuits.voyages.index', ['admin.circuits.voyages.*'], ['circuits.view', 'products-services.view']),
                    $eaItem('low_cost', 'Formule low cost', 'admin.menu-hubs.low-cost', ['admin.menu-hubs.low-cost', 'admin.economic-offers.*'], 'economic-offers.view'),
                    $eaItem('hajj_omra', 'Hajj & Omra', 'admin.menu-hubs.hajj-omra', ['admin.menu-hubs.hajj-omra', 'admin.hajj-omra.*'], 'hajj-omra.view'),
                    $eaItem('deals', 'Deals', 'admin.group-deals.index', ['admin.group-deals.*'], 'group-deals.offers.view'),
                ]),
                $eaGroup('Prestations', [
                    $eaItem('billetterie', 'Billetterie', 'admin.menu-hubs.billetterie', ['admin.menu-hubs.billetterie'], 'products-services.view'),
                    $eaItem('hebergement', 'Hébergement', 'admin.menu-hubs.hebergement', ['admin.menu-hubs.hebergement', 'admin.wordpress.hotels.*', 'admin.accommodation-packages.*'], ['accommodations.view', 'products-services.view']),
                    $eaItem('transferts', 'Transferts', 'admin.menu-hubs.transfers', ['admin.menu-hubs.transfers', 'admin.transfers.*'], 'transfers.view'),
                    $eaItem('activites', 'Activités', 'admin.menu-hubs.activites', ['admin.menu-hubs.activites', 'admin.activity-offers.*', 'admin.activities.*'], 'activities.view'),
                ]),
                $eaGroup('Services', [
                    $eaItem('visa', 'Visa', 'admin.menu-hubs.visa', ['admin.menu-hubs.visa', 'admin.visa.*'], 'visa.view'),
                    $eaItem('departures', 'Dates de départ', 'admin.circuits.departs-dates', ['admin.circuits.departs-dates'], ['circuits.view', 'products-services.view']),
                    $eaItem('custom_requests_products', 'Groupes & sur-mesure', 'admin.custom-requests.index', [], 'custom_requests.view'),
                ]),
            ])),
        ],
        'resa' => [
            'title' => 'Réservations',
            'sub' => 'Dossiers, demandes et paiements',
            'groups' => array_values(array_filter([
                $eaGroup('Dossiers', [
                    $eaItem('reservations_all', 'Toutes les réservations', 'admin.reservation-dossiers.index', ['admin.reservation-dossiers.*', 'admin.reservations.index'], 'reservations.view'),
                    $eaItem('catalogue', 'Catalogue de produits', \Illuminate\Support\Facades\Route::has('admin.vente.catalogue') ? 'admin.vente.catalogue' : 'admin.reservations.workspace', ['admin.vente.catalogue', 'admin.reservations.workspace*'], 'reservations.view'),
                    $eaItem('custom_requests', 'Demandes à la carte', 'admin.custom-requests.index', ['admin.custom-requests.*'], 'custom_requests.view'),
                    $eaItem('tailor_made', 'Demandes à la carte en ligne', 'admin.tailor-made-requests.index', ['admin.tailor-made-requests.*'], 'reservations.view'),
                ]),
                $eaGroup('Suivi', [
                    $eaItem('reservations_agents', 'Réservations agents', 'admin.reservations.agents', [], 'reservations.view'),
                    $eaItem('reservations_partners', 'Réservations partenaires', 'admin.reservations.partners', ['admin.reservations.partners'], 'reservations.view'),
                    $eaItem('reservations_clients', 'Réservations en ligne', 'admin.reservations.clients', ['admin.reservations.clients'], 'reservations.view'),
                    $eaItem('calendrier', 'Calendrier des départs', 'admin.reservations.calendrier', ['admin.reservations.calendrier*'], 'reservations.view'),
                    $eaItem('messagerie', 'Messagerie', 'admin.messagerie.index', ['admin.messagerie.*'], 'messagerie.view'),
                ]),
                $eaGroup('Finance', [
                    $eaItem('paiements', 'Paiements', 'admin.finance.paiements', ['admin.finance.paiements'], ['finance.payments.view', 'finance.view']),
                    $eaItem('factures', 'Factures', 'admin.finance.factures', ['admin.finance.factures'], ['finance.invoices.view', 'finance.view']),
                    $eaItem('depenses', 'Dépenses', 'admin.finance.depenses', ['admin.finance.depenses'], ['finance.expenses.view', 'finance.view']),
                    $eaItem('finance_departures', 'Finances départs', 'admin.finance.departures.index', ['admin.finance.departures.*'], 'departures_finance.view'),
                    $eaItem('commissions', 'Commissions', 'admin.finance.commissions', ['admin.finance.commissions*'], 'finance.view'),
                    $eaItem('my_commissions', 'Mes commissions', 'admin.agent.commissions.index', ['admin.agent.commissions.*'], ['commissions.view-own', 'commissions.view-team', 'commissions.view-all']),
                ]),
            ])),
        ],
        'clients' => [
            'title' => 'Clients',
            'sub' => 'Base clients et relation commerciale',
            'groups' => array_values(array_filter([
                $eaGroup('Base', [
                    $eaItem('clients_all', 'Tous les clients', 'admin.customers.clients.index', ['admin.customers.clients.*'], 'customers.clients.view'),
                    $eaItem('prospects', 'Prospects', 'admin.customers.prospects', ['admin.customers.prospects'], 'customers.clients.view'),
                    $eaItem('voyageurs', 'Voyageurs', 'admin.customers.voyageurs', ['admin.customers.voyageurs'], 'customers.travelers.view'),
                    $eaItem('historique', 'Historique', 'admin.customers.historique', ['admin.customers.historique'], 'customers.history.view'),
                ]),
                $eaGroup('Relation', [
                    $eaItem('avis', 'Avis clients', 'admin.customers.avis-clients', ['admin.customers.avis-clients'], 'customers.view'),
                    $eaItem('fidelite', 'Fidélité', 'admin.customers.fidelite', ['admin.customers.fidelite'], 'customers.loyalty.view'),
                    $eaItem('partenaires', 'Partenaires', 'admin.partners.partenaires', ['admin.partners.partenaires*'], 'partners.list.view'),
                    $eaItem('fournisseurs', 'Fournisseurs', 'admin.partners.fournisseurs', ['admin.partners.fournisseurs'], 'partners.suppliers.view'),
                ]),
            ])),
        ],
        'admin' => [
            'title' => 'Administration',
            'sub' => 'Réseau, équipes et reporting',
            'groups' => array_values(array_filter([
                $eaGroup('Réseau', [
                    $eaItem('points_of_sale', 'Points de vente', 'admin.points-of-sale.index', ['admin.points-of-sale.index', 'admin.points-of-sale.show', 'admin.agencies.*'], ['points_of_sale.view', 'agencies.view']),
                    $eaItem('pos_employees', 'Employés des points de vente', 'admin.agency-employees.index', ['admin.agency-employees.*'], ['pos_employees.view', 'agency_employees.view']),
                    $eaItem('pos_accounts', 'Comptes points de vente', 'admin.agency-accounts.index', ['admin.agency-accounts.*'], 'agency_accounts.view'),
                    $eaItem('assignments', 'Affectations', 'admin.assignments.index', ['admin.assignments.*'], 'assignments.view'),
                    $eaItem('pos_performance', 'Performance points de vente', 'admin.points-of-sale.performance', ['admin.points-of-sale.performance'], ['points_of_sale.performance', 'agency_performance.view']),
                ]),
                $eaGroup('Équipe', [
                    $eaItem('rh', 'Gestion RH', 'admin.menu-hubs.rh', ['admin.menu-hubs.rh'], 'settings.users.manage'),
                    $eaCanSeeAdministration ? $eaItem('users', 'Utilisateurs', 'admin.settings.utilisateurs', ['admin.settings.utilisateurs*'], 'settings.users.manage') : null,
                    $eaCanSeeAdministration ? $eaItem('roles', 'Rôles & permissions', 'admin.settings.roles-permissions', ['admin.settings.roles-permissions*'], 'settings.roles.manage') : null,
                ]),
                $eaGroup('Pilotage', [
                    $eaItem('stats', 'Statistiques', 'admin.dashboard.statistiques', ['admin.dashboard.statistiques'], 'dashboard.stats.view'),
                    $eaItem('alerts', 'Alertes', 'admin.dashboard.alertes', ['admin.dashboard.alertes'], 'dashboard.alerts.view'),
                    $eaItem('reports', 'Rapports financiers', 'admin.finance.rapports-financiers', ['admin.finance.rapports-financiers'], ['finance.reports.view', 'finance.view']),
                    $eaCanSeeAdministration ? $eaItem('settings', 'Paramètres', 'admin.settings.index', ['admin.settings.index', 'admin.settings.parametres-generaux*', 'admin.settings.securite*'], 'settings.view') : null,
                    $eaCanSeeAdministration ? $eaItem('home_page', 'Home page', 'admin.settings.home-page.edit', ['admin.settings.home-page.*'], 'settings.general.manage') : null,
                ]),
            ])),
        ],
    ];

    // Une section sans aucune entrée visible n'est pas affichée.
    $eaSections = array_filter($eaSections, fn (array $section) => $section['groups'] !== []);
    $eaIsDashboard = request()->routeIs('admin.dashboard', 'admin.dashboard.espace-v2', 'admin.dashboard.vue-globale', 'admin.dashboard.v*');
    $eaProfileHref = \Illuminate\Support\Facades\Route::has('admin.profile.edit') ? route('admin.profile.edit') : null;
    $eaLogoutHref = \Illuminate\Support\Facades\Route::has('logout.get') ? route('logout.get') : null;
@endphp

{{-- Navigation desktop --}}
<nav class="ea-nav" aria-label="Navigation principale">
    <a href="{{ $eaDashboardHref }}" class="ea-nav-link {{ $eaIsDashboard ? 'is-active' : '' }}">Tableau de bord</a>
    @foreach($eaSections as $key => $section)
        <button type="button" class="ea-nav-btn" data-ea-menu-toggle="{{ $key }}" aria-expanded="false" aria-controls="ea-mega-{{ $key }}">
            {{ $section['title'] }}<span class="ea-caret" aria-hidden="true"></span>
        </button>
    @endforeach
</nav>

{{-- Méga-menus --}}
@foreach($eaSections as $key => $section)
    <div class="ea-mega" id="ea-mega-{{ $key }}" data-ea-menu-panel="{{ $key }}">
        <div class="ea-mega-head">
            <div>
                <div class="ea-mega-title">{{ $section['title'] }}</div>
                <div class="ea-mega-sub">{{ $section['sub'] }}</div>
            </div>
            <button type="button" class="ea-mega-close" data-ea-menu-close>Fermer ×</button>
        </div>
        <div class="ea-mega-grid">
            @foreach($section['groups'] as $group)
                <div class="ea-mega-group">
                    <div class="ea-group-title">{{ $group['title'] }}</div>
                    <div class="ea-mega-list">
                        @foreach($group['items'] as $item)
                            <a href="{{ $item['href'] }}" class="ea-mega-item {{ $item['active'] ? 'is-active' : '' }}">
                                <span>{{ $item['label'] }}</span>
                                @if($item['meta'] !== '')<span class="ea-meta">{{ $item['meta'] }}</span>@endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

{{-- Bouton hamburger + tiroir mobile --}}
<button type="button" class="ea-burger" data-ea-drawer-toggle aria-expanded="false" aria-controls="ea-drawer" aria-label="Ouvrir le menu">
    <span></span><span></span><span></span>
</button>
<div class="ea-drawer" id="ea-drawer" data-ea-drawer>
    <a href="{{ $eaDashboardHref }}" class="ea-drawer-home">Tableau de bord</a>
    <label class="ea-drawer-search">
        <span class="ea-search-icon" aria-hidden="true"></span>
        <input type="search" placeholder="Rechercher…" aria-label="Rechercher" autocomplete="off">
    </label>
    @foreach($eaSections as $section)
        <div class="ea-drawer-section">
            <div class="ea-group-title">{{ $section['title'] }}</div>
            @foreach($section['groups'] as $group)
                <div class="ea-drawer-group">
                    <div class="ea-drawer-group-title">{{ $group['title'] }}</div>
                    <div class="ea-chips">
                        @foreach($group['items'] as $item)
                            <a href="{{ $item['href'] }}" class="ea-chip {{ $item['active'] ? 'is-active' : '' }}">
                                <span>{{ $item['label'] }}</span>
                                @if($item['meta'] !== '')<span class="ea-meta">{{ $item['meta'] }}</span>@endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
    <div class="ea-drawer-account">
        @if($eaProfileHref)
            <a href="{{ $eaProfileHref }}" class="ea-chip">Mon profil</a>
        @endif
        @if($eaLogoutHref)
            <a href="{{ $eaLogoutHref }}" class="ea-chip">Déconnexion</a>
        @endif
    </div>
</div>
