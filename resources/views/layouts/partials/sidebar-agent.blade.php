@php
    $user = auth()->user();
    $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
    $branchLabel = $user?->branch?->name;
    $roleLabel = $user?->isManager() ? 'Manager' : 'Agent';

    $navItems = collect([
        ['label' => 'Tableau de bord', 'icon' => 'bx bx-home-circle', 'route' => 'agent.dashboard', 'match' => ['agent.dashboard'], 'permission' => null],
        ['label' => 'Catalogue de voyage', 'icon' => 'bx bx-map-alt', 'route' => 'agent.catalogue', 'match' => ['agent.catalogue'], 'permission' => 'reservations.view'],
        ['label' => 'Mes rÃ©servations', 'icon' => 'bx bx-calendar-check', 'route' => 'admin.reservation-dossiers.index', 'match' => ['admin.reservation-dossiers.'], 'permission' => 'reservations.view'],
        ['label' => 'RÃ©servations Ã  la carte', 'icon' => 'bx bx-edit-alt', 'route' => 'admin.reservations.custom-requests.index', 'match' => ['admin.reservations.custom-requests.', 'admin.tailor-made-requests.'], 'permission' => 'reservations.view'],
        ['label' => 'Mon profil', 'icon' => 'bx bx-user', 'route' => 'admin.profile.edit', 'match' => ['admin.profile.'], 'permission' => ['dashboard.view', 'reservations.view']],
        ['label' => 'Messagerie', 'icon' => 'bx bx-envelope', 'route' => 'agent.messagerie.index', 'match' => ['agent.messagerie.'], 'permission' => null],
    ])->filter(function ($item) use ($user) {
        if (empty($item['route']) || ! \Illuminate\Support\Facades\Route::has($item['route'])) {
            return false;
        }

        $permission = $item['permission'] ?? null;
        if ($permission === null) {
            return true;
        }

        foreach ((array) $permission as $permissionName) {
            if ($user?->can($permissionName)) {
                return true;
            }
        }

        return false;
    });
@endphp

<div class="vertical-menu">
    <div class="h-100">
        <div class="user-wid text-center py-4 px-3">
            <div class="user-img">
                <img src="{{ $user?->avatar_url }}" alt="" class="avatar-md mx-auto rounded-circle">
            </div>
            <div class="mt-3">
                <a href="{{ route('agent.dashboard') }}" class="text-body fw-semibold font-size-16">{{ $user?->name }}</a>
                <p class="text-muted mt-1 mb-0 font-size-13">{{ $roleLabel }}</p>
                @if($branchLabel)
                    <p class="text-muted mt-1 mb-0 font-size-12">{{ $branchLabel }}</p>
                @endif
            </div>
        </div>

        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Navigation</li>

                @foreach($navItems as $item)
                    @php
                        $isActive = collect($item['match'] ?? [])
                            ->contains(fn ($prefix) => $currentRoute === $prefix || str_starts_with((string) $currentRoute, $prefix));
                    @endphp
                    <li>
                        <a href="{{ route($item['route']) }}" class="waves-effect {{ $isActive ? 'active' : '' }}">
                            <i class="{{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                            @if($item['route'] === 'agent.messagerie.index' && ($unreadCount ?? 0) > 0)
                                <span class="badge rounded-pill bg-primary float-end">{{ $unreadCount }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
