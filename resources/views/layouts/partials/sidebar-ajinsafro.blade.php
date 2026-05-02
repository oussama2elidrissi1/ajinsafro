<!-- ========== Left Sidebar Start (AjinsAfro) ========== -->
@php
    $adminBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $adminBrandLogoSmUrl = \App\Models\Setting::brandLogoUrl('sm');
    $adminBrandLogoDarkUrl = \App\Models\Setting::brandLogoUrl('dark');
    $sidebarUser = Auth::user();
    $currentRoute = Route::currentRouteName();

    $routeIsActive = function (?string $routeName) use ($currentRoute): bool {
        if (! $routeName || ! $currentRoute) {
            return false;
        }
        if ($currentRoute === $routeName) {
            return true;
        }
        return str_starts_with($currentRoute, $routeName . '.');
    };

    $filterItems = function (array $items) use ($sidebarUser): array {
        $out = [];
        foreach ($items as $item) {
            if (! empty($item['permission']) && ! $sidebarUser->can($item['permission'])) {
                continue;
            }
            if (! empty($item['roles']) && ! $sidebarUser->hasRole($item['roles'])) {
                continue;
            }
            if (! empty($item['route']) && ! Route::has($item['route'])) {
                continue;
            }
            $children = [];
            if (! empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    if (! empty($child['permission']) && ! $sidebarUser->can($child['permission'])) {
                        continue;
                    }
                    if (! empty($child['roles']) && ! $sidebarUser->hasRole($child['roles'])) {
                        continue;
                    }
                    if (! empty($child['route']) && ! Route::has($child['route'])) {
                        continue;
                    }
                    if (! empty($child['children'])) {
                        $deep = [];
                        foreach ($child['children'] as $c) {
                            if (! empty($c['permission']) && ! $sidebarUser->can($c['permission'])) {
                                continue;
                            }
                            if (! empty($c['roles']) && ! $sidebarUser->hasRole($c['roles'])) {
                                continue;
                            }
                            if (! empty($c['route']) && ! Route::has($c['route'])) {
                                continue;
                            }
                            $deep[] = $c;
                        }
                        $child['children'] = $deep;
                    }
                    $children[] = $child;
                }
            }
            if (! empty($children)) {
                $item['children'] = $children;
            } elseif (empty($item['route'])) {
                continue;
            }
            $out[] = $item;
        }
        return $out;
    };

    $menuItems = $filterItems(config('admin_menu.items', []));

    $hasActiveChild = function (array $item) use (&$hasActiveChild, $routeIsActive): bool {
        if (! empty($item['route']) && $routeIsActive($item['route'])) {
            return true;
        }
        if (! empty($item['children'])) {
            foreach ($item['children'] as $child) {
                if ($hasActiveChild($child)) {
                    return true;
                }
            }
        }
        return false;
    };
@endphp

<div class="vertical-menu">

    <div class="h-100">
        <div class="sidebar-brand-box text-center py-4 px-3 border-bottom">
            <a href="{{ route('admin.dashboard') }}" class="d-inline-flex align-items-center justify-content-center w-100">
                <img src="{{ $adminBrandLogoDarkUrl }}" alt="{{ $adminBrandName }}" class="admin-sidebar-logo">
            </a>
        </div>

        <div class="user-wid text-center py-4">
            <div class="user-img">
                <img src="{{ $sidebarUser->avatar_url }}" alt="" class="avatar-md mx-auto rounded-circle">
            </div>

            <div class="mt-3">

                <a href="{{ route('admin.dashboard') }}" class="text-body fw-medium font-size-16">{{ $sidebarUser->name }}</a>
                <p class="text-muted mt-1 mb-0 font-size-13">{{ $sidebarUser->getRoleNames()->first() ?? 'Admin' }}</p>

            </div>
        </div>

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>

                @foreach($menuItems as $section)
                    @php
                        $sectionActive = $hasActiveChild($section);
                        $sectionRoute = !empty($section['route']) && Route::has($section['route']) ? $section['route'] : null;
                        $sectionChildren = $section['children'] ?? [];
                        $hasChildren = !empty($sectionChildren);
                    @endphp
                    <li>
                        @if($hasChildren)
                            <a href="javascript: void(0);" class="has-arrow waves-effect {{ $sectionActive ? 'mm-active' : '' }}">
                                <i class="{{ $section['icon'] ?? 'bx bx-circle' }}"></i>
                                <span>{{ $section['label'] }}</span>
                            </a>
                            <ul class="sub-menu mm-collapse {{ $sectionActive ? 'mm-show' : '' }}" aria-expanded="{{ $sectionActive ? 'true' : 'false' }}">
                                @foreach($sectionChildren as $child)
                                    @php
                                        $childActive = $hasActiveChild($child);
                                        $childRoute = !empty($child['route']) && Route::has($child['route']) ? $child['route'] : null;
                                        $childChildren = $child['children'] ?? [];
                                        $childHasChildren = !empty($childChildren);
                                    @endphp
                                    <li class="{{ $childActive ? 'mm-active' : '' }}">
                                        @if($childHasChildren)
                                            <a href="javascript: void(0);" class="has-arrow {{ $childActive ? 'mm-active' : '' }}">
                                                @if(!empty($child['icon']))
                                                    <i class="{{ $child['icon'] }}"></i>
                                                @endif
                                                <span>{{ $child['label'] }}</span>
                                            </a>
                                            <ul class="sub-menu mm-collapse {{ $childActive ? 'mm-show' : '' }}" aria-expanded="{{ $childActive ? 'true' : 'false' }}">
                                                @foreach($childChildren as $grandChild)
                                                    @php
                                                        $gcRoute = !empty($grandChild['route']) && Route::has($grandChild['route']) ? $grandChild['route'] : null;
                                                        $gcActive = $routeIsActive($gcRoute);
                                                        $gcHref = !empty($grandChild['query']) && $gcRoute ? route($gcRoute, $grandChild['query']) : ($gcRoute ? route($gcRoute) : 'javascript:void(0);');
                                                    @endphp
                                                    <li>
                                                        <a href="{{ $gcHref }}" class="{{ $gcActive ? 'active' : '' }}">
                                                            {{ $grandChild['label'] }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            @php
                                                $childHref = !empty($child['query']) && $childRoute ? route($childRoute, $child['query']) : ($childRoute ? route($childRoute) : 'javascript:void(0);');
                                                $isActive = $routeIsActive($childRoute);
                                            @endphp
                                            <a href="{{ $childHref }}" class="{{ $isActive ? 'active' : '' }}">
                                                @if(!empty($child['icon']))
                                                    <i class="{{ $child['icon'] }}"></i>
                                                @endif
                                                {{ $child['label'] }}
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            @php
                                $sectionHref = !empty($section['query']) && $sectionRoute ? route($sectionRoute, $section['query']) : route($sectionRoute);
                                $isActive = $routeIsActive($sectionRoute);
                            @endphp
                            <a href="{{ $sectionHref }}" class="waves-effect {{ $isActive ? 'mm-active active' : '' }}">
                                <i class="{{ $section['icon'] ?? 'bx bx-circle' }}"></i>
                                <span>{{ $section['label'] }}</span>
                                @if(($section['key'] ?? null) === 'messagerie' && ($unreadCount ?? 0) > 0)
                                    <span class="badge rounded-pill bg-primary float-end">{{ $unreadCount }}</span>
                                @endif
                            </a>
                        @endif
                    </li>
                @endforeach

                <li class="menu-title mt-3">Compte</li>
                @can('dashboard.view')
                    <li>
                        <a href="{{ route('admin.profile.edit') }}" class="waves-effect">
                            <i class="bx bx-user-circle"></i>
                            <span>Mon profil</span>
                        </a>
                    </li>
                @endcan

                <li>
                    <a href="{{ route('logout.get') }}" class="waves-effect text-danger">
                        <i class="bx bx-power-off"></i>
                        <span>Déconnexion</span>
                    </a>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
