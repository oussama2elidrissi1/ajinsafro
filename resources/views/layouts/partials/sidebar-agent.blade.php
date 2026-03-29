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
                        $sectionActive = $children->contains(fn ($child) => ($child['route'] ?? null) === $currentRoute);
                    @endphp

                    @if($hasSectionPermission && $children->isNotEmpty())
                        <li>
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
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</div>
