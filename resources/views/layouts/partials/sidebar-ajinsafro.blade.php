<!-- ========== Left Sidebar Start (AjinsAfro) ========== -->
@php
    $adminBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $adminBrandLogoSmUrl = \App\Models\Setting::brandLogoUrl('sm');
    $adminBrandLogoDarkUrl = \App\Models\Setting::brandLogoUrl('dark');
    $sidebarUser = Auth::user();
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
                @php
                    $menuItems = config('admin_menu.items', []);
                    $currentRoute = Route::currentRouteName();
                    $user = Auth::user();
                    $navActive = function (?string $routeName) use ($currentRoute): bool {
                        if (! $routeName) {
                            return false;
                        }
                        if ($currentRoute === $routeName) {
                            return true;
                        }
                        $parts = explode('.', $routeName);
                        if (count($parts) < 2) {
                            return false;
                        }
                        $prefix = $parts[0] . '.' . $parts[1];

                        return $currentRoute === $prefix || str_starts_with($currentRoute, $prefix . '.');
                    };
                @endphp

                @foreach($menuItems as $section)
                    @php
                        $sectionRoute = !empty($section['route']) && Route::has($section['route']) ? $section['route'] : null;
                        $children = collect($section['children'] ?? [])
                            ->filter(function ($child) use ($user) {
                                if (! empty($child['route']) && ! Route::has($child['route'])) {
                                    return false;
                                }
                                if (! empty($child['roles']) && ! $user->hasRole($child['roles'])) {
                                    return false;
                                }
                                if (! empty($child['permission']) && ! $user->can($child['permission'])) {
                                    return false;
                                }
                                return true;
                            })
                            ->values();

                        $hasSectionPermission = empty($section['permission']) || $user->can($section['permission']);
                        $showSection = $hasSectionPermission && ($children->isNotEmpty() || $sectionRoute !== null);

                        $sectionActive = $children->contains(function ($child) use ($currentRoute) {
                            return ($child['route'] ?? null) === $currentRoute;
                        }) || $navActive($sectionRoute);
                    @endphp

                    @if($showSection)
                        <li>
                            @if($children->isNotEmpty())
                                <a href="javascript: void(0);" class="has-arrow waves-effect {{ $sectionActive ? 'mm-active' : '' }}">
                                    <i class="{{ $section['icon'] ?? 'bx bx-circle' }}"></i><span>{{ $section['label'] }}</span>
                                </a>
                                <ul class="sub-menu" aria-expanded="{{ $sectionActive ? 'true' : 'false' }}">
                                    @foreach($children as $child)
                                        <li>
                                            @php
                                                $childHref = !empty($child['query']) ? route($child['route'], $child['query']) : route($child['route']);
                                            @endphp
                                            <a href="{{ $childHref }}" class="{{ ($child['route'] ?? null) === $currentRoute ? 'active' : '' }}">{{ $child['label'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                @php
                                    $sectionHref = !empty($section['query']) ? route($sectionRoute, $section['query']) : route($sectionRoute);
                                @endphp
                                <a href="{{ $sectionHref }}" class="waves-effect {{ $sectionActive ? 'mm-active active' : '' }}">
                                    <i class="{{ $section['icon'] ?? 'bx bx-circle' }}"></i><span>{{ $section['label'] }}</span>
                                </a>
                            @endif
                        </li>
                    @endif
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
                        <span>DÃ©connexion</span>
                    </a>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
