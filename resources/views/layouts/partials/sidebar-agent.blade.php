@php
    $user = auth()->user();
    $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
    $roleLabel = $user?->getRoleNames()->first() ?? ($user?->is_admin ? 'admin' : 'utilisateur');
    $roleLabel = \Illuminate\Support\Str::replace('_', ' ', (string) $roleLabel);
    $roleLabel = \Illuminate\Support\Str::title($roleLabel);
    $branchLabel = $user?->branch?->name;

    $allowedSections = ['reservations', 'customers', 'circuits', 'operations', 'visa', 'messagerie'];
    $menuItems = collect(config('admin_menu.items', []))
        ->filter(fn ($section) => in_array($section['key'] ?? '', $allowedSections, true))
        ->values();
    $isManagerPortal = $user?->hasRole([\App\Services\BranchScopeService::ROLE_MANAGER, 'Manager']);
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

<div class="vertical-menu">
    <div class="h-100">
        <div class="user-wid text-center py-4">
            <div class="user-img">
                <img src="{{ $user?->avatar_url }}" alt="" class="avatar-md mx-auto rounded-circle">
            </div>
            <div class="mt-3">
                <a href="{{ route('agent.dashboard') }}" class="text-body fw-medium font-size-16">{{ $user?->name }}</a>
                <p class="text-muted mt-1 mb-0 font-size-13">{{ $roleLabel }}</p>
                @if($branchLabel)
                    <p class="text-muted mt-1 mb-0 font-size-12">{{ $branchLabel }}</p>
                @endif
            </div>
        </div>

        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">{{ $isManagerPortal ? 'Espace Manager' : 'Espace Agent' }}</li>

                <li>
                    <a href="{{ route('agent.dashboard') }}" class="waves-effect {{ $currentRoute === 'agent.dashboard' ? 'active' : '' }}">
                        <i class="bx bx-home-circle"></i><span>Dashboard</span>
                    </a>
                </li>

                @foreach($menuItems as $section)
                    @php
                        $sectionRoute = !empty($section['route']) && \Illuminate\Support\Facades\Route::has($section['route']) ? $section['route'] : null;
                        $children = collect($section['children'] ?? [])
                            ->filter(function ($child) use ($user) {
                                if (! empty($child['route']) && ! \Illuminate\Support\Facades\Route::has($child['route'])) {
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
                        $sectionActive = $children->contains(fn ($child) => ($child['route'] ?? null) === $currentRoute) || $navActive($sectionRoute);
                    @endphp

                    @if($hasSectionPermission && ($children->isNotEmpty() || $sectionRoute))
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
                                            <a href="{{ $childHref }}" class="{{ ($child['route'] ?? null) === $currentRoute ? 'active' : '' }}">
                                                {{ $child['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                @php
                                    $sectionHref = !empty($section['query']) ? route($sectionRoute, $section['query']) : route($sectionRoute);
                                @endphp
                                <a href="{{ $sectionHref }}" class="waves-effect {{ $sectionActive ? 'active' : '' }}">
                                    <i class="{{ $section['icon'] ?? 'bx bx-circle' }}"></i><span>{{ $section['label'] }}</span>
                                </a>
                            @endif
                        </li>
                    @endif
                @endforeach

                @if(\Illuminate\Support\Facades\Route::has('agent.messagerie.index'))
                    <li>
                        <a href="{{ route('agent.messagerie.index') }}" class="waves-effect {{ $navActive('agent.messagerie.index') ? 'active' : '' }}">
                            <i class="bx bx-envelope"></i>
                            <span>Messagerie</span>
                            @if(($unreadCount ?? 0) > 0)
                                <span class="badge rounded-pill bg-primary float-end">{{ $unreadCount }}</span>
                            @endif
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
