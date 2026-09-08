@php
    /**
     * Navigation horizontale « Espace Admin v2 » : méga-menus desktop + tiroir mobile.
     *
     * Source unique : config/admin_menu.php, filtré par permissions et routes via AdminMenuService.
     * Les modules du menu sont regroupés dans les quatre sections du design.
     * $eaCounts (optionnel) : compteurs affichés à droite des entrées, indexés par nom de route.
     */
    $eaUser = auth()->user();
    $eaCounts = is_array($eaCounts ?? null) ? $eaCounts : [];
    $eaDashboardHref = route('admin.dashboard');
    $eaIsDashboard = request()->routeIs('admin.dashboard', 'admin.dashboard.vue-globale', 'admin.dashboard.espace-v2', 'admin.dashboard.v*');

    $eaMenu = [];
    if ($eaUser) {
        try {
            $eaMenu = app(\App\Services\Admin\AdminMenuService::class)->buildForUser($eaUser);
        } catch (\Throwable $e) {
            $eaMenu = [];
        }
    }
    $eaByKey = collect($eaMenu)->keyBy('key');

    // Transforme une liste de nœuds cliquables en entrées (dédoublonnées par URL).
    $eaItems = function (array $nodes) use ($eaCounts): array {
        $items = [];
        $seen = [];
        foreach ($nodes as $node) {
            $href = (string) ($node['href'] ?? '');
            if ($href === '' || empty($node['is_clickable']) || isset($seen[$href])) {
                continue;
            }
            $seen[$href] = true;
            $meta = isset($node['route']) ? ($eaCounts[$node['route']] ?? null) : null;
            $items[] = [
                'label' => (string) $node['label'],
                'href' => $href,
                'active' => !empty($node['active']),
                'meta' => $meta === null || $meta === '' ? '' : (string) $meta,
            ];
        }
        return $items;
    };

    // Un module devient : un groupe pour ses feuilles directes + un groupe par sous-module.
    $eaGroupsFromNode = function (?array $node, ?string $leafTitle = null) use ($eaItems): array {
        if (!$node) {
            return [];
        }
        $groups = [];
        $leaves = [];
        foreach ($node['children'] ?? [] as $child) {
            if (!empty($child['children'])) {
                $items = $eaItems(array_merge([$child], $child['children']));
                if ($items !== []) {
                    $groups[] = ['title' => (string) $child['label'], 'items' => $items];
                }
            } else {
                $leaves[] = $child;
            }
        }
        $leafItems = $eaItems($leaves);
        if ($leafItems !== []) {
            array_unshift($groups, ['title' => $leafTitle ?? (string) $node['label'], 'items' => $leafItems]);
        }
        return $groups;
    };

    // Plusieurs modules « feuille » de premier niveau regroupés sous un même titre.
    $eaLeafGroup = function (string $title, array $keys) use ($eaByKey, $eaItems): array {
        $nodes = [];
        foreach ($keys as $key) {
            $node = $eaByKey->get($key);
            if ($node) {
                $nodes[] = $node;
            }
        }
        $items = $eaItems($nodes);
        return $items === [] ? [] : [['title' => $title, 'items' => $items]];
    };

    $eaSections = [
        'produits' => [
            'title' => 'Produits & services',
            'sub' => 'Catalogue, prestations et offres commerciales',
            'groups' => array_merge(
                $eaGroupsFromNode($eaByKey->get('products_services'), 'Catalogue'),
                $eaGroupsFromNode($eaByKey->get('products'), 'Grille commerciale'),
                $eaGroupsFromNode($eaByKey->get('visa'), 'Visa')
            ),
        ],
        'resa' => [
            'title' => 'Réservations',
            'sub' => 'Dossiers, demandes et paiements',
            'groups' => array_merge(
                $eaGroupsFromNode($eaByKey->get('reservations'), 'Dossiers'),
                $eaLeafGroup('Communication', ['messagerie', 'dev_reclamations']),
                $eaGroupsFromNode($eaByKey->get('finance'), 'Finance')
            ),
        ],
        'clients' => [
            'title' => 'Clients',
            'sub' => 'Base clients et relation commerciale',
            'groups' => array_merge(
                $eaGroupsFromNode($eaByKey->get('customers'), 'Base clients'),
                $eaGroupsFromNode($eaByKey->get('partners'), 'Partenaires')
            ),
        ],
        'admin' => [
            'title' => 'Administration',
            'sub' => 'Réseau, opérations, pilotage et paramètres',
            'groups' => array_merge(
                $eaGroupsFromNode($eaByKey->get('agencies'), 'Points de vente'),
                $eaGroupsFromNode($eaByKey->get('operations'), 'Opérations terrain'),
                $eaGroupsFromNode($eaByKey->get('dashboard'), 'Pilotage'),
                $eaGroupsFromNode($eaByKey->get('reporting'), 'Reporting'),
                $eaGroupsFromNode($eaByKey->get('settings'), 'Paramètres')
            ),
        ],
    ];

    // Une section sans entrée visible n'est pas affichée ; une section est « active » si l'une de ses entrées l'est.
    $eaSections = array_filter($eaSections, fn (array $section) => $section['groups'] !== []);
    foreach ($eaSections as $key => $section) {
        $active = false;
        foreach ($section['groups'] as $group) {
            foreach ($group['items'] as $item) {
                if ($item['active']) {
                    $active = true;
                    break 2;
                }
            }
        }
        $eaSections[$key]['active'] = $active && !$eaIsDashboard;
    }

    $eaProfileHref = \Illuminate\Support\Facades\Route::has('admin.profile.edit') ? route('admin.profile.edit') : null;
    $eaLogoutHref = \Illuminate\Support\Facades\Route::has('logout.get') ? route('logout.get') : null;
@endphp

{{-- Navigation desktop --}}
<nav class="ea-nav" aria-label="Navigation principale">
    <a href="{{ $eaDashboardHref }}" class="ea-nav-link {{ $eaIsDashboard ? 'is-active' : '' }}">Tableau de bord</a>
    @foreach($eaSections as $key => $section)
        <button type="button" class="ea-nav-btn {{ $section['active'] ? 'is-current' : '' }}" data-ea-menu-toggle="{{ $key }}" aria-expanded="false" aria-controls="ea-mega-{{ $key }}">
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
