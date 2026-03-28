<div class="vertical-menu">
    <div class="h-100">
        <div class="user-wid text-center py-4">
            <div class="user-img">
                <img src="{{ Auth::user()->avatar_url }}" alt="" class="avatar-md mx-auto rounded-circle">
            </div>
            <div class="mt-3">
                <a href="{{ route('partner.dashboard') }}" class="text-body fw-medium font-size-16">{{ Auth::user()->name }}</a>
                <p class="text-muted mt-1 mb-0 font-size-13">Partenaire</p>
            </div>
        </div>
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>
                @php
                    $menuItems = config('partner_menu.items', []);
                    $currentRoute = Route::currentRouteName();
                @endphp
                @foreach($menuItems as $item)
                    @php
                        $route = $item['route'] ?? null;
                        $routePrefix = $route && substr_count($route, '.') >= 2 ? substr($route, 0, strrpos($route, '.')) : '';
                        $active = $route === $currentRoute || ($routePrefix && str_starts_with($currentRoute, $routePrefix));
                    @endphp
                    <li>
                        <a href="{{ $route ? route($route) : 'javascript:void(0);' }}" class="waves-effect {{ $active ? 'active' : '' }}">
                            <i class="{{ $item['icon'] ?? 'bx bx-circle' }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
