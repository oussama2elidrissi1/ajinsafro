<?php
    $sidebarContext = $sidebarContext ?? 'default';
    $sidebarUser = auth()->user();
    $sidebarBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $sidebarBrandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $sidebarBrandHref = request()->routeIs('admin.dashboard.v2') && \Illuminate\Support\Facades\Route::has('admin.dashboard.v2')
        ? route('admin.dashboard.v2')
        : route('admin.dashboard');
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
    $productsNode = $menuByKey->get('products');
    $productsServicesNode = $menuByKey->get('products_services');
    $customersNode = $menuByKey->get('customers');
    $agenciesNode = $menuByKey->get('agencies');
    $partnersNode = $menuByKey->get('partners');
    $operationsNode = $menuByKey->get('operations');
    $visaNode = $menuByKey->get('visa');
    $financeNode = $menuByKey->get('finance');
    $reportingNode = $menuByKey->get('reporting');
    $settingsNode = $menuByKey->get('settings');

    $reservationsChildren = array_values(array_filter([
        $makeLeaf('reservations_index', 'Toutes les réservations', 'admin.reservations.index', 'bx bx-calendar-check', ['admin.reservations.index']),
        $makeLeaf('reservations_workspace', 'Vente', 'admin.reservations.workspace', 'bx bx-briefcase-alt', ['admin.reservations.workspace*']),
        $makeLeaf('reservations_clients', 'Réservation en ligne', 'admin.reservations.clients', 'bx bx-user', ['admin.reservations.clients']),
        $makeLeaf('tailor_made_requests', 'Demande à la carte', 'admin.tailor-made-requests.index', 'bx bx-edit-alt', ['admin.tailor-made-requests.*'], [], null, 'reservations.view'),
        $makeLeaf('assignments_index', 'Affectations', 'admin.assignments.index', 'bx bx-transfer', ['admin.assignments.*'], [], null, 'assignments.view'),
        $makeLeaf('messagerie_index', 'Messagerie', 'admin.messagerie.index', 'bx bx-envelope', ['admin.messagerie.*'], [], $unreadCount > 0 ? $unreadCount : null, 'dashboard.view'),
    ]));

    $productsChildren = [];
    if ($productsNode) {
        $productsChildren[] = array_merge($productsNode, ['label' => 'Grille commerciale']);
    }
    if ($productsServicesNode) {
        $allowedKeys = ['voyages', 'accommodations', 'activities', 'group_deals', 'hajj_omra', 'economic_offers', 'transfers'];
        foreach ($productsServicesNode['children'] ?? [] as $child) {
            if (in_array($child['key'] ?? '', $allowedKeys, true)) {
                $productsChildren[] = $child;
            }
        }
    }
    $productsChildren[] = $makeLeaf('products_services_extras', 'Extras / Services additionnels', 'admin.products.services', 'bx bx-plus-medical', ['admin.products.services']);
    $productsChildren = array_values(array_filter($productsChildren));

    $customersChildren = [];
    $customersChildren[] = $makeLeaf('customers_clients_index', 'Clients', 'admin.customers.clients.index', 'bx bx-user', ['admin.customers.clients.*'], [], null, 'customers.clients.view');
    $customersChildren[] = $makeLeaf('customers_travelers', 'Voyageurs', 'admin.customers.voyageurs', 'bx bx-id-card', ['admin.customers.voyageurs'], [], null, 'customers.travelers.view');
    $customersChildren[] = $makeLeaf('partners_list', 'Partenaires', 'admin.partners.partenaires', 'bx bx-group', ['admin.partners.partenaires'], [], null, 'partners.list.view');
    $customersChildren[] = $makeLeaf('partners_suppliers', 'Fournisseurs', 'admin.partners.fournisseurs', 'bx bx-briefcase', ['admin.partners.fournisseurs'], [], null, 'partners.suppliers.view');
    $customersChildren = collect($customersChildren)->filter()->unique('key')->values()->all();

    $pointsOfSaleChildren = array_values(array_filter([
        $makeLeaf('points_of_sale_index', 'Liste des points de vente', 'admin.points-of-sale.index', 'bx bx-buildings', ['admin.points-of-sale.*', 'admin.agencies.index', 'admin.agencies.show'], [], null, ['points_of_sale.view', 'agencies.view']),
        $makeLeaf('pos_employees_index', 'Employes des points de vente', 'admin.agency-employees.index', 'bx bx-user-pin', ['admin.agency-employees.*'], [], null, ['pos_employees.view', 'agency_employees.view']),
        $makeLeaf('agency_accounts_pos_index', 'Comptes points de vente', 'admin.agency-accounts.index', 'bx bx-id-card', ['admin.agency-accounts.*'], [], null, 'agency_accounts.view'),
        $makeLeaf('assignments_index_secondary', 'Affectations', 'admin.assignments.index', 'bx bx-transfer', ['admin.assignments.*'], [], null, 'assignments.view'),
        $makeLeaf('points_of_sale_performance', 'Performance points de vente', 'admin.points-of-sale.performance', 'bx bx-bar-chart-alt-2', ['admin.points-of-sale.performance', 'admin.agencies.performance'], [], null, ['points_of_sale.performance', 'agency_performance.view']),
    ]));

    $operationsChildren = [];
    if ($operationsNode) {
        $operationsChildren[] = $operationsNode;
    }
    if ($visaNode) {
        $operationsChildren[] = $visaNode;
    }
    $operationsChildren = array_values(array_filter($operationsChildren));

    $financeChildren = array_values(array_filter([
        $financeNode,
        $reportingNode,
    ]));

    $adminGroups = array_values(array_filter([
        $makeGroup('grp_dashboard', 'Tableau de bord', $dashboardNode['children'] ?? [], 'bx bx-home-circle'),
        $makeGroup('grp_reservations', 'Réservations', $reservationsChildren, 'bx bx-calendar-check'),
        $makeGroup('grp_products', 'Produits & Services', $productsChildren, 'bx bx-layer'),
        $makeGroup('grp_customers', 'Clients', $customersChildren, 'bx bx-group'),
        $makeGroup('grp_points_of_sale', 'Points de vente', $pointsOfSaleChildren, 'bx bx-buildings'),
        $makeGroup('grp_operations', 'Opérations', $operationsChildren, 'bx bx-briefcase-alt-2'),
        $makeGroup('grp_finance', 'Finance & Reporting', $financeChildren, 'bx bx-wallet'),
        $makeGroup('grp_admin', 'Administration', $settingsNode['children'] ?? [], 'bx bx-cog'),
    ]));

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
?>

<div class="aj-sidebar-v2" data-aj-sidebar-v2 data-sidebar-context="<?php echo e($sidebarContext); ?>">
    <div class="aj-sidebar-v2__brand">
        <a href="<?php echo e($sidebarBrandHref); ?>" class="aj-sidebar-v2__brand-link" aria-label="<?php echo e($sidebarBrandName); ?>">
            <img src="<?php echo e($sidebarBrandLogo); ?>" alt="<?php echo e($sidebarBrandName); ?>" class="aj-sidebar-v2__brand-logo">
        </a>
    </div>

    <div class="aj-sidebar-v2__profile">
        <div class="aj-sidebar-v2__avatar-wrap">
            <img src="<?php echo e($sidebarUser?->avatar_url); ?>" alt="<?php echo e($sidebarUser?->name ?? 'Admin'); ?>" class="aj-sidebar-v2__avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
            <span class="aj-sidebar-v2__avatar-fallback"><?php echo e($sidebarInitials); ?></span>
        </div>
        <div class="aj-sidebar-v2__profile-name"><?php echo e($sidebarUser?->name ?? 'Admin'); ?></div>
        <div class="aj-sidebar-v2__profile-role"><?php echo e($sidebarRole); ?></div>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.profile.edit')): ?>
            <a href="<?php echo e(route('admin.profile.edit')); ?>" class="aj-sidebar-v2__profile-link">
                <i class="bx bx-user-circle"></i>
                <span>Mon profil</span>
            </a>
        <?php endif; ?>
    </div>

    <nav class="aj-sidebar-v2__nav" aria-label="Navigation administration">
        <?php echo $renderNodes($adminGroups); ?>


        <div class="aj-sidebar-v2__account">
            <div class="aj-sidebar-v2__section-title">Compte</div>
            <ul class="aj-sidebar-v2__list aj-sidebar-v2__list--depth-0">
                <?php if(\Illuminate\Support\Facades\Route::has('admin.profile.edit')): ?>
                    <li class="aj-sidebar-v2__item <?php echo e(request()->routeIs('admin.profile.*') ? 'is-active' : ''); ?>">
                        <a href="<?php echo e(route('admin.profile.edit')); ?>" class="aj-sidebar-v2__link">
                            <span class="aj-sidebar-v2__icon"><i class="bx bx-user-circle"></i></span>
                            <span class="aj-sidebar-v2__label">Mon profil</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if(\Illuminate\Support\Facades\Route::has('logout.get')): ?>
                    <li class="aj-sidebar-v2__item is-danger">
                        <a href="<?php echo e(route('logout.get')); ?>" class="aj-sidebar-v2__link">
                            <span class="aj-sidebar-v2__icon"><i class="bx bx-power-off"></i></span>
                            <span class="aj-sidebar-v2__label">Déconnexion</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partials\sidebar-v2.blade.php ENDPATH**/ ?>