@php
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

    $makeLeaf = function (string $key, string $label, ?string $route = null, ?string $icon = null, array $activePatterns = [], array $query = [], ?int $badge = null): ?array {
        if (!$route || !\Illuminate\Support\Facades\Route::has($route)) {
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
        $makeLeaf('reservations_workspace', 'Toutes les réservations', 'admin.reservations.workspace', 'bx bx-calendar-check', ['admin.reservations.workspace*']),
        $makeLeaf('reservations_clients', 'Réservations clients', 'admin.reservations.clients', 'bx bx-user', ['admin.reservations.clients']),
        $makeLeaf('reservations_pending', 'Demandes en attente', 'admin.reservations.en-attente', 'bx bx-time-five', ['admin.reservations.en-attente'], [], $pendingReservationsCount > 0 ? $pendingReservationsCount : null),
        $makeLeaf('assignments_index', 'Affectations', 'admin.assignments.index', 'bx bx-transfer', ['admin.assignments.*']),
        $makeLeaf('messagerie_index', 'Messagerie', 'admin.messagerie.index', 'bx bx-envelope', ['admin.messagerie.*'], [], $unreadCount > 0 ? $unreadCount : null),
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
    $customersChildren[] = $makeLeaf('customers_clients_index', 'Clients', 'admin.customers.clients.index', 'bx bx-user', ['admin.customers.clients.*']);
    $customersChildren[] = $makeLeaf('customers_travelers', 'Voyageurs', 'admin.customers.voyageurs', 'bx bx-id-card', ['admin.customers.voyageurs']);
    if ($agenciesNode) {
        foreach ($agenciesNode['children'] ?? [] as $child) {
            if (in_array($child['label'] ?? '', ['Liste des agences', 'Employés des agences', 'Comptes agences', 'Dashboard agence', 'Performance agences'], true)) {
                $customersChildren[] = $child;
            }
        }
    }
    $customersChildren[] = $makeLeaf('agency_accounts_index', 'Comptes agences', 'admin.agency-accounts.index', 'bx bx-id-card', ['admin.agency-accounts.*']);
    $customersChildren[] = $makeLeaf('partners_list', 'Partenaires', 'admin.partners.partenaires', 'bx bx-group', ['admin.partners.partenaires']);
    $customersChildren[] = $makeLeaf('partners_suppliers', 'Fournisseurs', 'admin.partners.fournisseurs', 'bx bx-briefcase', ['admin.partners.fournisseurs']);
    $customersChildren = collect($customersChildren)->filter()->unique('key')->values()->all();

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
        $makeGroup('grp_customers', 'Clients & Agences', $customersChildren, 'bx bx-group'),
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
            $itemClasses = ['aj-sidebar-v2__item'];

            if ($hasChildren) {
                $itemClasses[] = 'has-children';
            }
            if (!empty($node['active'])) {
                $itemClasses[] = 'is-active';
            }
            if (!empty($node['open'])) {
                $itemClasses[] = 'is-open';
            }

            $html .= '<li class="' . e(implode(' ', $itemClasses)) . '" data-group-key="' . e((string) ($node['key'] ?? '')) . '">';

            if ($hasChildren) {
                $html .= '<button type="button" class="aj-sidebar-v2__link aj-sidebar-v2__toggle" data-aj-sidebar-toggle aria-expanded="' . (!empty($node['open']) ? 'true' : 'false') . '">';
                if (!empty($node['icon'])) {
                    $html .= '<span class="aj-sidebar-v2__icon"><i class="' . e((string) $node['icon']) . '"></i></span>';
                }
                $html .= '<span class="aj-sidebar-v2__label">' . e((string) ($node['label'] ?? '')) . '</span>';
                $html .= '<span class="aj-sidebar-v2__chevron"><i class="bx bx-chevron-down"></i></span>';
                $html .= '</button>';
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
                            <span class="aj-sidebar-v2__label">Déconnexion</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
</div>
