@php
    $user = auth()->user();
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $brandLogoSm = \App\Models\Setting::brandLogoUrl('sm');
    $currentRoute = Route::currentRouteName();
@endphp

<div class="admin-topbar" role="banner">
    <div class="admin-topbar__inner">
        <div class="admin-topbar__left">
            <button type="button" class="btn btn-sm btn-light d-lg-none admin-topbar__toggle" id="vertical-menu-btn" aria-label="Ouvrir le menu">
                <i class="bx bx-menu"></i>
            </button>
            <a href="{{ route('admin.dashboard') }}" class="admin-topbar__brand d-none d-lg-inline-flex">
                <img src="{{ $brandLogoSm }}" alt="{{ $brandName }}" class="admin-topbar__logo">
            </a>
            @hasSection('topbar-title')
                <div class="admin-topbar__title d-none d-md-block">
                    <h1 class="admin-topbar__page-title">@yield('topbar-title')</h1>
                    @hasSection('topbar-subtitle')
                        <p class="admin-topbar__page-subtitle">@yield('topbar-subtitle')</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="admin-topbar__right">
            <div class="admin-topbar__actions">
                @hasSection('topbar-action')
                    <div class="admin-topbar__action-slot">
                        @yield('topbar-action')
                    </div>
                @endif

                <div class="dropdown admin-topbar__user">
                    <button class="btn btn-link dropdown-toggle admin-topbar__user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="admin-topbar__avatar">
                        <span class="admin-topbar__user-name d-none d-md-inline">{{ $user->name }}</span>
                        <i class="bx bx-chevron-down d-none d-md-inline"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end admin-topbar__dropdown">
                        <li class="dropdown-header">{{ $user->getRoleNames()->first() ?? 'Admin' }}</li>
                        <li><a class="dropdown-item" href="{{ route('admin.profile.edit') }}"><i class="bx bx-user-circle me-2"></i>Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="{{ route('logout.get') }}">
                                <i class="bx bx-power-off me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
