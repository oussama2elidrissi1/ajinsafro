@php
    $sidebarContext = $sidebarContext ?? 'default';
    $sidebarUser = auth()->user();
    $sidebarBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $sidebarBrandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $sidebarBrandHref = route('admin.dashboard');
    if (request()->routeIs('admin.dashboard.v2') && \Illuminate\Support\Facades\Route::has('admin.dashboard.v2')) {
        $sidebarBrandHref = route('admin.dashboard.v2');
    } elseif (request()->routeIs('admin.dashboard.v4') && \Illuminate\Support\Facades\Route::has('admin.dashboard.v4')) {
        $sidebarBrandHref = route('admin.dashboard.v4');
    } elseif (request()->routeIs('admin.dashboard.v5') && \Illuminate\Support\Facades\Route::has('admin.dashboard.v5')) {
        $sidebarBrandHref = route('admin.dashboard.v5');
    } elseif (request()->routeIs('admin.dashboard.v6') && \Illuminate\Support\Facades\Route::has('admin.dashboard.v6')) {
        $sidebarBrandHref = route('admin.dashboard.v6');
    }
    $sidebarRole = $sidebarUser?->getRoleNames()->first() ?? 'Administrateur';
    $sidebarInitials = strtoupper(collect(preg_split('/\s+/', trim((string) ($sidebarUser?->name ?? 'Admin'))))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    if ($sidebarInitials === '') {
        $sidebarInitials = 'AD';
    }

    $menuService = app(\App\Services\Admin\AdminMenuService::class);
    $baseMenuItems = $sidebarUser ? $menuService->buildForUser($sidebarUser) : [];
    $menuByKey = collect($baseMenuItems)->keyBy('key');

    $userCanAccessPermissions = function ($permission) use ($sidebarUser): bool {
        if (!$sidebarUser) {
            return false;
        }

        if (is_string($permission) && $permission !== '') {
            return $sidebarUser->can($permission);
        }

        if (is_array($permission)) {
            foreach ($permission as $permissionName) {
                if (is_string($permissionName) && $permissionName !== '' && $sidebarUser->can($permissionName)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    };

    $makeLeaf = function (string $key, string $label, ?string $route = null, ?string $icon = null, array $activePatterns = [], array $query = [], ?int $badge = null, $permission = null) use ($userCanAccessPermissions): ?array {
        if (!$route || !\Illuminate\Support\Facades\Route::has($route)) {
            return null;
        }

        if (!$userCanAccessPermissions($permission)) {
            return null;
        }

        $href = route($route, $query);
        $patterns = $activePatterns !== [] ? $activePatterns : [$route, $route . '.*'];
        $active = request()->routeIs(...$patterns);

        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'href' => $href,
            'children' => [],
            'active' => $active,
            'open' => false,
            'badge' => $badge,
        ];
    };

    $makeGroup = function (string $key, string $label, array $children, ?string $icon = null): ?array {
        $children = array_values(array_filter($children));
        if ($children === []) {
            return null;
        }

        $active = collect($children)->contains(fn ($child) => !empty($child['active']) || !empty($child['open']));

        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'href' => null,
            'children' => $children,
            'active' => $active,
            'open' => $active,
            'badge' => null,
        ];
    };

    $unreadCount = 0;
    $pendingReservationsCount = 0;
    if ($sidebarUser && \Illuminate\Support\Facades\Schema::hasTable('messages')) {
        $unreadCount = \App\Models\Message::query()
            ->where('recipient_id', $sidebarUser->id)
            ->where('folder_recipient', 'inbox')
            ->where('read', false)
            ->count();
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('reservations')) {
        $pendingReservationsCount = \App\Models\Reservation::query()
            ->where('status', \App\Models\Reservation::STATUS_PENDING)
            ->count();
    }

    $dashboardNode = $menuByKey->get('dashboard');
    $dashboardChildren = $dashboardNode['children'] ?? [];
    if (\Illuminate\Support\Facades\Route::has('admin.dashboard.v3')) {
        $dashboardV3Exists = collect($dashboardChildren)->contains(function ($child) {
            return ($child['route'] ?? null) === 'admin.dashboard.v3' || (($child['href'] ?? null) === route('admin.dashboard.v3'));
        });

        if (! $dashboardV3Exists) {
            $dashboardV3Leaf = $makeLeaf(
                'dashboard_v3',
                'Dashboard V3',
                'admin.dashboard.v3',
                null,
                ['admin.dashboard.v3'],
                [],
                null,
                'dashboard.overview.view'
            );

            if ($dashboardV3Leaf) {
                $dashboardChildren[] = $dashboardV3Leaf;
            }
        }
    }
    if (\Illuminate\Support\Facades\Route::has('admin.dashboard.v4')) {
        $dashboardV4Exists = collect($dashboardChildren)->contains(function ($child) {
            return ($child['route'] ?? null) === 'admin.dashboard.v4' || (($child['href'] ?? null) === route('admin.dashboard.v4'));
        });

        if (! $dashboardV4Exists) {
            $dashboardV4Leaf = $makeLeaf(
                'dashboard_v4',
                'Dashboard V4',
                'admin.dashboard.v4',
                null,
                ['admin.dashboard.v4'],
                [],
                null,
                'dashboard.overview.view'
            );

            if ($dashboardV4Leaf) {
                $dashboardChildren[] = $dashboardV4Leaf;
            }
        }
    }
    if (\Illuminate\Support\Facades\Route::has('admin.dashboard.v5')) {
        $dashboardV5Exists = collect($dashboardChildren)->contains(function ($child) {
            return ($child['route'] ?? null) === 'admin.dashboard.v5' || (($child['href'] ?? null) === route('admin.dashboard.v5'));
        });

        if (! $dashboardV5Exists) {
            $dashboardV5Leaf = $makeLeaf(
                'dashboard_v5',
                'Dashboard V5',
                'admin.dashboard.v5',
                null,
                ['admin.dashboard.v5'],
                [],
                null,
                'dashboard.overview.view'
            );

            if ($dashboardV5Leaf) {
                $dashboardChildren[] = $dashboardV5Leaf;
            }
        }
    }
    if (\Illuminate\Support\Facades\Route::has('admin.dashboard.v6')) {
        $dashboardV6Exists = collect($dashboardChildren)->contains(function ($child) {
            return ($child['route'] ?? null) === 'admin.dashboard.v6' || (($child['href'] ?? null) === route('admin.dashboard.v6'));
        });

        if (! $dashboardV6Exists) {
            $dashboardV6Leaf = $makeLeaf(
                'dashboard_v6',
                'Dashboard V6',
                'admin.dashboard.v6',
                null,
                ['admin.dashboard.v6'],
                [],
                null,
                'dashboard.overview.view'
            );

            if ($dashboardV6Leaf) {
                $dashboardChildren[] = $dashboardV6Leaf;
            }
        }
    }
    $dashboardChildren = array_values(array_filter([
        $makeLeaf('dashboard_overview', 'Vue d ensemble', 'admin.dashboard.vue-globale', 'bx bx-home-circle', ['admin.dashboard', 'admin.dashboard.vue-globale', 'admin.dashboard.v6'], [], null, 'dashboard.overview.view'),
        $makeLeaf('dashboard_stats', 'Statistiques', 'admin.dashboard.statistiques', 'bx bx-bar-chart-alt-2', ['admin.dashboard.statistiques'], [], null, 'dashboard.stats.view'),
        $makeLeaf('dashboard_alerts', 'Alertes', 'admin.dashboard.alertes', 'bx bx-bell', ['admin.dashboard.alertes'], [], null, 'dashboard.alerts.view'),
    ]));

    $reservationsChildren = array_values(array_filter([
        $makeLeaf('reservations_index', 'Toutes les reservations', 'admin.reservations.index', 'bx bx-calendar-check', ['admin.reservations.index']),
        $makeLeaf('reservations_workspace', 'Vente', 'admin.reservations.workspace', 'bx bx-briefcase-alt', ['admin.reservations.workspace*']),
        $makeLeaf('reservations_clients', 'Reservation en ligne', 'admin.reservations.clients', 'bx bx-user-check', ['admin.reservations.clients']),
        $makeLeaf('tailor_made_requests', 'Demande a la carte', 'admin.tailor-made-requests.index', 'bx bx-edit-alt', ['admin.tailor-made-requests.*'], [], null, 'reservations.view'),
        $makeLeaf('messagerie_index', 'Messagerie', 'admin.messagerie.index', 'bx bx-envelope', ['admin.messagerie.*'], [], $unreadCount > 0 ? $unreadCount : null, 'dashboard.view'),
    ]));

    $productsChildren = array_values(array_filter([
        $makeLeaf('products_voyages', 'Voyage', 'admin.circuits.voyages.index', 'bx bx-map-alt', ['admin.circuits.voyages.*'], [], null, ['circuits.view', 'products-services.view']),
        $makeLeaf('products_billetterie', 'Billetrie', 'admin.menu-hubs.billetterie', 'bx bx-ticket', ['admin.menu-hubs.billetterie'], [], null, 'products-services.view'),
        $makeLeaf('products_hebergement', 'Hebergement', 'admin.menu-hubs.hebergement', 'bx bx-bed', ['admin.menu-hubs.hebergement', 'admin.wordpress.hotels.*', 'admin.accommodation-packages.*'], [], null, ['accommodations.view', 'products-services.view']),
        $makeLeaf('products_hajj_omra', 'Hajj & Omra', 'admin.menu-hubs.hajj-omra', 'bx bx-moon', ['admin.menu-hubs.hajj-omra', 'admin.hajj-omra.*'], [], null, 'hajj-omra.view'),
        $makeLeaf('products_low_cost', 'Formule low coast', 'admin.menu-hubs.low-cost', 'bx bx-wallet-alt', ['admin.menu-hubs.low-cost', 'admin.economic-offers.*'], [], null, 'economic-offers.view'),
        $makeLeaf('products_activities', 'Activite', 'admin.menu-hubs.activites', 'bx bx-run', ['admin.menu-hubs.activites', 'admin.activity-offers.*', 'admin.circuits.activities.*', 'admin.activities.*'], [], null, 'activities.view'),
        $makeLeaf('products_transfers', 'Transfer', 'admin.menu-hubs.transfers', 'bx bx-transfer-alt', ['admin.menu-hubs.transfers', 'admin.circuits.tour-transfers.*', 'admin.transfers.*'], [], null, 'transfers.view'),
        $makeLeaf('products_visa', 'Visa', 'admin.menu-hubs.visa', 'bx bx-id-card', ['admin.menu-hubs.visa', 'admin.visa.*'], [], null, 'visa.view'),
    ]));

    $customersChildren = [];
    $customersChildren[] = $makeLeaf('customers_clients_index', 'Prospet', 'admin.customers.clients.index', 'bx bx-user-plus', ['admin.customers.clients.*'], [], null, 'customers.clients.view');
    $customersChildren[] = $makeLeaf('customers_travelers', 'Voyageur', 'admin.customers.voyageurs', 'bx bx-id-card', ['admin.customers.voyageurs'], [], null, 'customers.travelers.view');
    $customersChildren[] = $makeLeaf('partners_list', 'Partenaire', 'admin.partners.partenaires', 'bx bx-group', ['admin.partners.partenaires'], [], null, 'partners.list.view');
    $customersChildren[] = $makeLeaf('partners_suppliers', 'Fournisseure', 'admin.partners.fournisseurs', 'bx bx-briefcase', ['admin.partners.fournisseurs'], [], null, 'partners.suppliers.view');
    $customersChildren = collect($customersChildren)->filter()->unique('key')->values()->all();

    $pointsOfSaleChildren = array_values(array_filter([
        $makeLeaf('points_of_sale_index', 'Liste des points de vente', 'admin.points-of-sale.index', 'bx bx-buildings', ['admin.points-of-sale.*', 'admin.agencies.index', 'admin.agencies.show'], [], null, ['points_of_sale.view', 'agencies.view']),
        $makeLeaf('pos_employees_index', 'Employes des points de vente', 'admin.agency-employees.index', 'bx bx-user-pin', ['admin.agency-employees.*'], [], null, ['pos_employees.view', 'agency_employees.view']),
        $makeLeaf('agency_accounts_pos_index', 'Comptes points de vente', 'admin.agency-accounts.index', 'bx bx-id-card', ['admin.agency-accounts.*'], [], null, 'agency_accounts.view'),
        $makeLeaf('assignments_index_secondary', 'Affectations', 'admin.assignments.index', 'bx bx-transfer', ['admin.assignments.*'], [], null, 'assignments.view'),
        $makeLeaf('points_of_sale_performance', 'Performance points de vente', 'admin.points-of-sale.performance', 'bx bx-bar-chart-alt-2', ['admin.points-of-sale.performance', 'admin.agencies.performance'], [], null, ['points_of_sale.performance', 'agency_performance.view']),
    ]));

    $financeChildren = array_values(array_filter([
        $makeLeaf('finance_my_commissions', 'Mes comission', 'admin.agent.commissions.index', 'bx bx-line-chart', ['admin.agent.commissions.*'], [], null, 'agent.commissions.view'),
        $makeLeaf('finance_factures', 'Facture', 'admin.finance.factures', 'bx bx-receipt', ['admin.finance.factures'], [], null, 'finance.view'),
        $makeLeaf('finance_paiements', 'Paeiment', 'admin.finance.paiements', 'bx bx-credit-card', ['admin.finance.paiements'], [], null, 'finance.view'),
        $makeLeaf('finance_depenses', 'Depennse', 'admin.finance.depenses', 'bx bx-money-withdraw', ['admin.finance.depenses'], [], null, 'finance.view'),
        $makeLeaf('finance_commissions', 'Comission', 'admin.finance.commissions', 'bx bx-pie-chart-alt-2', ['admin.finance.commissions'], [], null, 'finance.view'),
    ]));

    $adminGroups = array_values(array_filter([
        $makeGroup('grp_dashboard', 'Tableau de board', $dashboardChildren, 'bx bx-home-circle'),
        $makeGroup('grp_reservations', 'Reservation', $reservationsChildren, 'bx bx-calendar-check'),
        $makeGroup('grp_products', 'Produit et service', $productsChildren, 'bx bx-layer'),
        $makeGroup('grp_customers', 'Client', $customersChildren, 'bx bx-group'),
        $makeGroup('grp_points_of_sale', 'Points de vente', $pointsOfSaleChildren, 'bx bx-buildings'),
        $makeLeaf('grp_rh', 'Gestion Rh', 'admin.menu-hubs.rh', 'bx bx-user-voice', ['admin.menu-hubs.rh', 'admin.settings.utilisateurs', 'admin.settings.roles-permissions'], [], null, 'settings.users.manage'),
        $makeGroup('grp_finance', 'Finace reporting', $financeChildren, 'bx bx-wallet'),
        $makeGroup('grp_admin', 'Administration', $settingsNode['children'] ?? [], 'bx bx-cog'),
    ]));

    if ($sidebarUser?->hasRole(\App\Services\BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY)) {
        $reservationsOnlyChildren = array_values(array_filter([
            $makeLeaf(
                'reservations_index_only',
                'RÃ©servations',
                'admin.reservations.index',
                'bx bx-calendar-check',
                ['admin.reservations.*', 'admin.reservation-dossiers.*'],
                [],
                null,
                'reservations.view'
            ),
        ]));

        $adminGroups = array_values(array_filter([
            $makeGroup('grp_reservations', 'RÃ©servations', $reservationsOnlyChildren, 'bx bx-calendar-check'),
        ]));
    }

    if ($sidebarUser?->hasRole(\App\Services\BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY)) {
        $adminGroups = array_values(array_filter([
            $makeLeaf(
                'sales_workspace_only',
                'Vente',
                'admin.reservations.workspace',
                'bx bx-briefcase-alt',
                [
                    'admin.reservations.workspace*',
                    'admin.reservations.create',
                    'admin.reservations.store',
                ],
                [],
                null,
                'reservations.view'
            ),
            $makeLeaf(
                'reservations_index_only_flat',
                'RÃƒÂ©servations',
                'admin.reservations.index',
                'bx bx-calendar-check',
                [
                    'admin.reservations.index',
                    'admin.reservation-dossiers.*',
                    'admin.reservations.show',
                    'admin.reservations.edit',
                    'admin.reservations.update',
                    'admin.reservations.destroy',
                ],
                [],
                null,
                'reservations.view'
            ),
        ]));
    }

    if ($sidebarUser?->hasRole(\App\Services\BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY)) {
        $adminGroups = array_values(array_filter([
            $makeLeaf(
                'sales_workspace_only_final',
                'Vente',
                'admin.reservations.workspace',
                'bx bx-briefcase-alt',
                ['admin.reservations.workspace*', 'admin.reservations.create', 'admin.reservations.store'],
                [],
                null,
                'reservations.view'
            ),
            $makeLeaf(
                'reservations_index_only_final',
                'Reservations',
                'admin.reservations.index',
                'bx bx-calendar-check',
                ['admin.reservations.index', 'admin.reservation-dossiers.*', 'admin.reservations.show', 'admin.reservations.edit', 'admin.reservations.update', 'admin.reservations.destroy'],
                [],
                null,
                'reservations.view'
            ),
        ]));
    }

    $renderNodes = function (array $nodes, int $depth = 0) use (&$renderNodes): string {
        if ($nodes === []) {
            return '';
        }

        $html = '<ul class="aj-sidebar-v2__list aj-sidebar-v2__list--depth-' . $depth . '">';

        foreach ($nodes as $node) {
            $children = is_array($node['children'] ?? null) ? $node['children'] : [];
            $hasChildren = $children !== [];
            $hasDirectLink = $hasChildren && !empty($node['href']);
            $itemClasses = ['aj-sidebar-v2__item'];

            if ($hasChildren) {
                $itemClasses[] = 'has-children';
            }
            if ($hasDirectLink) {
                $itemClasses[] = 'has-direct-link';
            }
            if (!empty($node['active'])) {
                $itemClasses[] = 'is-active';
            }
            if (!empty($node['open'])) {
                $itemClasses[] = 'is-open';
            }

            $html .= '<li class="' . e(implode(' ', $itemClasses)) . '" data-group-key="' . e((string) ($node['key'] ?? '')) . '">';

            if ($hasChildren) {
                if ($hasDirectLink) {
                    $html .= '<div class="aj-sidebar-v2__link-group">';
                    $html .= '<a href="' . e((string) $node['href']) . '" class="aj-sidebar-v2__link aj-sidebar-v2__link--parent">';
                    if (!empty($node['icon'])) {
                        $html .= '<span class="aj-sidebar-v2__icon"><i class="' . e((string) $node['icon']) . '"></i></span>';
                    }
                    $html .= '<span class="aj-sidebar-v2__label">' . e((string) ($node['label'] ?? '')) . '</span>';
                    $html .= '</a>';
                    $html .= '<button type="button" class="aj-sidebar-v2__link aj-sidebar-v2__toggle" data-aj-sidebar-toggle aria-expanded="' . (!empty($node['open']) ? 'true' : 'false') . '" aria-label="Afficher le sous-menu ' . e((string) ($node['label'] ?? '')) . '">';
                    $html .= '<span class="aj-sidebar-v2__chevron"><i class="bx bx-chevron-down"></i></span>';
                    $html .= '</button>';
                    $html .= '</div>';
                } else {
                    $html .= '<button type="button" class="aj-sidebar-v2__link aj-sidebar-v2__toggle" data-aj-sidebar-toggle aria-expanded="' . (!empty($node['open']) ? 'true' : 'false') . '">';
                    if (!empty($node['icon'])) {
                        $html .= '<span class="aj-sidebar-v2__icon"><i class="' . e((string) $node['icon']) . '"></i></span>';
                    }
                    $html .= '<span class="aj-sidebar-v2__label">' . e((string) ($node['label'] ?? '')) . '</span>';
                    $html .= '<span class="aj-sidebar-v2__chevron"><i class="bx bx-chevron-down"></i></span>';
                    $html .= '</button>';
                }
                $html .= '<div class="aj-sidebar-v2__submenu">' . $renderNodes($children, $depth + 1) . '</div>';
            } else {
                $html .= '<a href="' . e((string) ($node['href'] ?? 'javascript:void(0);')) . '" class="aj-sidebar-v2__link">';
                if (!empty($node['icon'])) {
                    $html .= '<span class="aj-sidebar-v2__icon"><i class="' . e((string) $node['icon']) . '"></i></span>';
                }
                $html .= '<span class="aj-sidebar-v2__label">' . e((string) ($node['label'] ?? '')) . '</span>';
                if (!empty($node['badge'])) {
                    $html .= '<span class="aj-sidebar-v2__badge">' . e((string) min((int) $node['badge'], 99)) . '</span>';
                }
                $html .= '</a>';
            }

            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    };
@endphp

<div class="aj-sidebar-v2" data-aj-sidebar-v2 data-sidebar-context="{{ $sidebarContext }}">
    <div class="aj-sidebar-v2__brand">
        <a href="{{ $sidebarBrandHref }}" class="aj-sidebar-v2__brand-link" aria-label="{{ $sidebarBrandName }}">
            <img src="{{ $sidebarBrandLogo }}" alt="{{ $sidebarBrandName }}" class="aj-sidebar-v2__brand-logo">
        </a>
    </div>

    <div class="aj-sidebar-v2__profile">
        <div class="aj-sidebar-v2__avatar-wrap">
            <img src="{{ $sidebarUser?->avatar_url }}" alt="{{ $sidebarUser?->name ?? 'Admin' }}" class="aj-sidebar-v2__avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
            <span class="aj-sidebar-v2__avatar-fallback">{{ $sidebarInitials }}</span>
        </div>
        <div class="aj-sidebar-v2__profile-name">{{ $sidebarUser?->name ?? 'Admin' }}</div>
        <div class="aj-sidebar-v2__profile-role">{{ $sidebarRole }}</div>
        @if(\Illuminate\Support\Facades\Route::has('admin.profile.edit'))
            <a href="{{ route('admin.profile.edit') }}" class="aj-sidebar-v2__profile-link">
                <i class="bx bx-user-circle"></i>
                <span>Mon profil</span>
            </a>
        @endif
    </div>

    <nav class="aj-sidebar-v2__nav" aria-label="Navigation administration">
        {!! $renderNodes($adminGroups) !!}

        <div class="aj-sidebar-v2__account">
            <div class="aj-sidebar-v2__section-title">Compte</div>
            <ul class="aj-sidebar-v2__list aj-sidebar-v2__list--depth-0">
                @if(\Illuminate\Support\Facades\Route::has('admin.profile.edit'))
                    <li class="aj-sidebar-v2__item {{ request()->routeIs('admin.profile.*') ? 'is-active' : '' }}">
                        <a href="{{ route('admin.profile.edit') }}" class="aj-sidebar-v2__link">
                            <span class="aj-sidebar-v2__icon"><i class="bx bx-user-circle"></i></span>
                            <span class="aj-sidebar-v2__label">Mon profil</span>
                        </a>
                    </li>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('logout.get'))
                    <li class="aj-sidebar-v2__item is-danger">
                        <a href="{{ route('logout.get') }}" class="aj-sidebar-v2__link">
                            <span class="aj-sidebar-v2__icon"><i class="bx bx-power-off"></i></span>
                            <span class="aj-sidebar-v2__label">DÃ©connexion</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
</div>

