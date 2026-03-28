<!-- ========== Left Sidebar Start (AjinsAfro) ========== -->
<div class="vertical-menu">

    <div class="h-100">

        <div class="user-wid text-center py-4">
            <div class="user-img">
                <img src="{{ Auth::user()->avatar_url }}" alt="" class="avatar-md mx-auto rounded-circle">
            </div>

            <div class="mt-3">

                <a href="{{ route('admin.dashboard') }}" class="text-body fw-medium font-size-16">{{ Auth::user()->name }}</a>
                <p class="text-muted mt-1 mb-0 font-size-13">Admin</p>

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
                @endphp

                @foreach($menuItems as $section)
                    @php
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
                        $showSection = $hasSectionPermission && ($children->isNotEmpty() || empty($section['children']));

                        $sectionActive = $children->contains(function ($child) use ($currentRoute) {
                            return ($child['route'] ?? null) === $currentRoute;
                        });
                    @endphp

                    @if($showSection)
                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect {{ $sectionActive ? 'mm-active' : '' }}">
                                <i class="{{ $section['icon'] ?? 'bx bx-circle' }}"></i><span>{{ $section['label'] }}</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="{{ $sectionActive ? 'true' : 'false' }}">
                                @foreach($children as $child)
                                    <li>
                                        <a href="{{ route($child['route']) }}" class="{{ ($child['route'] ?? null) === $currentRoute ? 'active' : '' }}">{{ $child['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
