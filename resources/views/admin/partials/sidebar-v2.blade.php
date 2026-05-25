@php
    $sidebarContext = $sidebarContext ?? 'default';
    $sidebarUser = auth()->user();
    $sidebarInitials = 'AD';
    if ($sidebarUser && isset($sidebarUser->name)) {
        $name = trim((string) $sidebarUser->name);
        if ($name !== '') {
            $parts = preg_split('/\\s+/', $name) ?: [];
            $first = isset($parts[0]) ? strtoupper(substr((string) $parts[0], 0, 1)) : '';
            $last = isset($parts[count($parts) - 1]) ? strtoupper(substr((string) $parts[count($parts) - 1], 0, 1)) : '';
            $sidebarInitials = trim($first . $last) ?: strtoupper(substr($name, 0, 1));
        }
    }
    $sidebarRole = $sidebarRole ?? ($sidebarUser && method_exists($sidebarUser, 'getRoleNames') ? (string) ($sidebarUser->getRoleNames()->first() ?? 'Admin') : 'Admin');
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

    // Fallbacks when this sidebar is included without the shared menu builder context.
    // Keep them minimal and safe: permissions are enforced via $sidebarUser->can() when available.
    $makeLeaf = (isset($makeLeaf) && is_callable($makeLeaf)) ? $makeLeaf : function (
        string $key,
        string $label,
        ?string $routeName = null,
        ?string $icon = null,
        array $activeRoutes = [],
        array $children = [],
        $badge = null,
        $permission = null
    ) use ($sidebarUser) {
        if ($permission !== null) {
            if (!$sidebarUser || !method_exists($sidebarUser, 'can')) {
                return null;
            }
            $perms = is_array($permission) ? $permission : [$permission];
            $allowed = false;
            foreach ($perms as $perm) {
                $perm = is_string($perm) ? trim($perm) : '';
                if ($perm === '') {
                    continue;
                }
                try {
                    if ($sidebarUser->can($perm)) {
                        $allowed = true;
                        break;
                    }
                } catch (Throwable $e) {
                    // ignore and keep checking
                }
            }
            if (!$allowed) {
                return null;
            }
        }

        $href = 'javascript:void(0);';
        if (is_string($routeName) && $routeName !== '' && \Illuminate\Support\Facades\Route::has($routeName)) {
            $href = route($routeName);
        }

        $active = false;
        foreach ($activeRoutes as $pattern) {
            if (is_string($pattern) && $pattern !== '' && request()->routeIs($pattern)) {
                $active = true;
                break;
            }
        }
        if (!$active && is_string($routeName) && $routeName !== '' && request()->routeIs($routeName)) {
            $active = true;
        }

        return [
            'key' => $key,
            'label' => $label,
            'href' => $href,
            'icon' => $icon,
            'badge' => $badge,
            'children' => $children,
            'active' => $active,
            'open' => $active,
        ];
    };

    $makeGroup = (isset($makeGroup) && is_callable($makeGroup)) ? $makeGroup : function (
        string $key,
        string $label,
        array $children = [],
        ?string $icon = null
    ) {
        $children = array_values(array_filter($children));
        $active = false;
        foreach ($children as $child) {
            if (!empty($child['active'])) {
                $active = true;
                break;
            }
        }
        return [
            'key' => $key,
            'label' => $label,
            'href' => null,
            'icon' => $icon,
            'children' => $children,
            'active' => $active,
            'open' => $active,
        ];
    };

    if ($sidebarUser?->hasRole(\App\Services\BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY)) {
        $adminGroups = array_values(array_filter([
            $makeLeaf(
                'sales_workspace_only_final',
                'Vente / Catalogue',
                \Illuminate\Support\Facades\Route::has('admin.vente.catalogue') ? 'admin.vente.catalogue' : 'admin.reservations.workspace',
                'bx bx-briefcase-alt',
                ['admin.vente.catalogue', 'admin.reservations.workspace*', 'admin.reservations.create', 'admin.reservations.store'],
                [],
                null,
                'reservations.view'
            ),
            $makeLeaf(
                'reservations_index_only_final',
                'R?servations',
                'admin.reservation-dossiers.index',
                'bx bx-calendar-check',
                ['admin.reservation-dossiers.*', 'admin.reservations.index', 'admin.reservations.show', 'admin.reservations.edit', 'admin.reservations.update', 'admin.reservations.destroy'],
                [],
                null,
                'reservations.view'
            ),
        ]));
    }
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

    // Some contexts include this sidebar without the menu builder variables.
    // Keep a safe default to avoid crashing the whole admin layout.
    $menuByKey = (isset($menuByKey) && is_object($menuByKey) && method_exists($menuByKey, 'get'))
        ? $menuByKey
        : collect();

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
        $makeLeaf('reservations_workspace', 'Vente', \Illuminate\Support\Facades\Route::has('admin.vente.catalogue') ? 'admin.vente.catalogue' : 'admin.reservations.workspace', 'bx bx-briefcase-alt', ['admin.vente.catalogue', 'admin.reservations.workspace*']),
        $makeGroup('custom_reservation_requests_group', 'Demande a la carte', array_values(array_filter([
            $makeLeaf('custom_reservation_requests', 'Demande a la carte', 'admin.reservations.custom-requests.index', 'bx bx-message-square-detail', ['admin.reservations.custom-requests.*'], [], null, 'reservations.view'),
            $makeLeaf('tailor_made_requests_online', 'Demande a la carte en ligne', 'admin.tailor-made-requests.index', 'bx bx-globe', ['admin.tailor-made-requests.*'], [], null, 'reservations.view'),
        ])), 'bx bx-edit-alt'),
        $makeLeaf('reservations_clients', 'Reservation en ligne', 'admin.reservations.clients', 'bx bx-user-check', ['admin.reservations.clients']),
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
        $makeLeaf('products_group_deals', 'Deals', 'admin.group-deals.index', 'bx bx-purchase-tag', ['admin.group-deals.*'], [], null, 'group-deals.offers.view'),
    ]));

    $customersChildren = [];
    $customersChildren[] = $makeLeaf('customers_prospects', 'Prospect', 'admin.customers.prospects', 'bx bx-user-plus', ['admin.customers.prospects'], [], null, 'customers.clients.view');
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

    $administrationChildren = array_values(array_filter([
        $makeLeaf('admin_settings_index', 'Vue generale', 'admin.settings.index', 'bx bx-cog', ['admin.settings.index'], [], null, 'settings.view'),
        $makeLeaf('admin_users', 'Utilisateurs', 'admin.settings.utilisateurs', 'bx bx-user-pin', ['admin.settings.utilisateurs*'], [], null, 'settings.users.manage'),
        $makeLeaf('admin_roles', 'Roles & permissions', 'admin.settings.roles-permissions', 'bx bx-shield-quarter', ['admin.settings.roles-permissions*'], [], null, 'settings.roles.manage'),
        $makeLeaf('admin_general', 'Parametres generaux', 'admin.settings.parametres-generaux', 'bx bx-slider-alt', ['admin.settings.parametres-generaux*'], [], null, 'settings.general.manage'),
        $makeLeaf('admin_referentials', 'Referentiels metier', 'admin.settings.referentiels-metier', 'bx bx-list-ul', ['admin.settings.referentiels-metier*'], [], null, 'settings.general.manage'),
        $makeLeaf('admin_home_page', 'Home page', 'admin.settings.home-page.edit', 'bx bx-home-heart', ['admin.settings.home-page.*'], [], null, 'settings.general.manage'),
        $makeLeaf('admin_security', 'Securite', 'admin.settings.securite', 'bx bx-lock-alt', ['admin.settings.securite*'], [], null, 'settings.security.manage'),
    ]));

    $adminGroups = array_values(array_filter([
        $makeGroup('grp_dashboard', 'Tableau de board', $dashboardChildren, 'bx bx-home-circle'),
        $makeGroup('grp_reservations', 'Reservation', $reservationsChildren, 'bx bx-calendar-check'),
        $makeGroup('grp_products', 'Produit et service', $productsChildren, 'bx bx-layer'),
        $makeGroup('grp_customers', 'Client', $customersChildren, 'bx bx-group'),
        $makeGroup('grp_points_of_sale', 'Points de vente', $pointsOfSaleChildren, 'bx bx-buildings'),
        $makeLeaf('grp_rh', 'Gestion Rh', 'admin.menu-hubs.rh', 'bx bx-user-voice', ['admin.menu-hubs.rh', 'admin.settings.utilisateurs', 'admin.settings.roles-permissions'], [], null, 'settings.users.manage'),
        $makeGroup('grp_finance', 'Finace reporting', $financeChildren, 'bx bx-wallet'),
        $makeGroup('grp_admin', 'Administration', $administrationChildren, 'bx bx-cog'),
    ]));

    if ($sidebarUser?->hasRole(\App\Services\BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY)) {
        $reservationsOnlyChildren = array_values(array_filter([
            $makeLeaf(
                'reservations_index_only',
                'Réservations',
                'admin.reservations.index',
                'bx bx-calendar-check',
                ['admin.reservations.*', 'admin.reservation-dossiers.*'],
                [],
                null,
                'reservations.view'
            ),
        ]));

        $adminGroups = array_values(array_filter([
            $makeGroup('grp_reservations', 'Réservations', $reservationsOnlyChildren, 'bx bx-calendar-check'),
        ]));
    }

    if ($sidebarUser?->hasRole(\App\Services\BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY)) {
        $adminGroups = array_values(array_filter([
            $makeLeaf(
                'sales_workspace_only',
                'Vente',
                \Illuminate\Support\Facades\Route::has('admin.vente.catalogue') ? 'admin.vente.catalogue' : 'admin.reservations.workspace',
                'bx bx-briefcase-alt',
                [
                    'admin.vente.catalogue',
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
                'R?servations',
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
                \Illuminate\Support\Facades\Route::has('admin.vente.catalogue') ? 'admin.vente.catalogue' : 'admin.reservations.workspace',
                'bx bx-briefcase-alt',
                ['admin.vente.catalogue', 'admin.reservations.workspace*', 'admin.reservations.create', 'admin.reservations.store'],
                [],
                null,
                'reservations.view'
            ),
            $makeLeaf(
                'reservations_index_only_final',
                'Reservations',
                'admin.reservation-dossiers.index',
                'bx bx-calendar-check',
                ['admin.reservations.index', 'admin.reservation-dossiers.*', 'admin.reservations.show', 'admin.reservations.edit', 'admin.reservations.update', 'admin.reservations.destroy'],
                [],
                null,
                'reservations.view'
            ),
            $makeLeaf(
                'custom_requests_only_final',
                'Demandes a la carte',
                'admin.reservations.custom-requests.index',
                'bx bx-message-square-detail',
                ['admin.reservations.custom-requests.*'],
                [],
                null,
                'reservations.view'
            ),
            $makeLeaf(
                'tailor_made_requests_online_only_final',
                'Demande a la carte en ligne',
                'admin.tailor-made-requests.index',
                'bx bx-globe',
                ['admin.tailor-made-requests.*'],
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
                    $labelText = (string) ($node['label'] ?? '');
                    $safeLabel = e($labelText);
                    $html .= '<a href="' . e((string) $node['href']) . '" class="aj-sidebar-v2__link aj-sidebar-v2__link--parent" title="' . $safeLabel . '" aria-label="' . $safeLabel . '" data-aj-tooltip="' . $safeLabel . '">';
                    if (!empty($node['icon'])) {
                        $html .= '<span class="aj-sidebar-v2__icon"><i class="' . e((string) $node['icon']) . '"></i></span>';
                    }
                    $html .= '<span class="aj-sidebar-v2__label">' . $safeLabel . '</span>';
                    $html .= '</a>';
                    $html .= '<button type="button" class="aj-sidebar-v2__link aj-sidebar-v2__toggle" data-aj-sidebar-toggle aria-expanded="' . (!empty($node['open']) ? 'true' : 'false') . '" aria-label="Afficher le sous-menu ' . $safeLabel . '">';
                    $html .= '<span class="aj-sidebar-v2__chevron"><i class="bx bx-chevron-down"></i></span>';
                    $html .= '</button>';
                    $html .= '</div>';
                } else {
                    $labelText = (string) ($node['label'] ?? '');
                    $safeLabel = e($labelText);
                    $html .= '<button type="button" class="aj-sidebar-v2__link aj-sidebar-v2__toggle" data-aj-sidebar-toggle aria-expanded="' . (!empty($node['open']) ? 'true' : 'false') . '" title="' . $safeLabel . '" aria-label="' . $safeLabel . '" data-aj-tooltip="' . $safeLabel . '">';
                    if (!empty($node['icon'])) {
                        $html .= '<span class="aj-sidebar-v2__icon"><i class="' . e((string) $node['icon']) . '"></i></span>';
                    }
                    $html .= '<span class="aj-sidebar-v2__label">' . $safeLabel . '</span>';
                    $html .= '<span class="aj-sidebar-v2__chevron"><i class="bx bx-chevron-down"></i></span>';
                    $html .= '</button>';
                }
                $html .= '<div class="aj-sidebar-v2__submenu">' . $renderNodes($children, $depth + 1) . '</div>';
            } else {
                $labelText = (string) ($node['label'] ?? '');
                $safeLabel = e($labelText);
                $html .= '<a href="' . e((string) ($node['href'] ?? 'javascript:void(0);')) . '" class="aj-sidebar-v2__link" title="' . $safeLabel . '" aria-label="' . $safeLabel . '" data-aj-tooltip="' . $safeLabel . '">';
                if (!empty($node['icon'])) {
                    $html .= '<span class="aj-sidebar-v2__icon"><i class="' . e((string) $node['icon']) . '"></i></span>';
                }
                $html .= '<span class="aj-sidebar-v2__label">' . $safeLabel . '</span>';
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
                        <a href="{{ route('admin.profile.edit') }}" class="aj-sidebar-v2__link" title="Mon profil" aria-label="Mon profil" data-aj-tooltip="Mon profil">
                            <span class="aj-sidebar-v2__icon"><i class="bx bx-user-circle"></i></span>
                            <span class="aj-sidebar-v2__label">Mon profil</span>
                        </a>
                    </li>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('logout.get'))
                    <li class="aj-sidebar-v2__item is-danger">
                        <a href="{{ route('logout.get') }}" class="aj-sidebar-v2__link" title="Déconnexion" aria-label="Déconnexion" data-aj-tooltip="Déconnexion">
                            <span class="aj-sidebar-v2__icon"><i class="bx bx-power-off"></i></span>
                            <span class="aj-sidebar-v2__label">Déconnexion</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
</div>

