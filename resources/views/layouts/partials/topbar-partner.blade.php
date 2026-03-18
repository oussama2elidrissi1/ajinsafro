@php
    $adminBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $adminBrandLogo = \App\Models\Setting::getValue('brand_logo');
    $adminBrandLogoUrl = \App\Models\Setting::storageUrl($adminBrandLogo);
@endphp
<header id="page-topbar">
    <div class="navbar-header">
        <div class="container-fluid">
            <div class="float-end">
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img class="rounded-circle header-profile-user" src="{{ Auth::user()->avatar_url }}"
                            alt="Header Avatar">
                        <span class="d-none d-xl-inline-block ms-1">{{ Auth::user()->name }}</span>
                        <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item text-danger" href="{{ route('logout.get') }}"><i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
            <div>
                <div class="navbar-brand-box">
                    <a href="{{ route('partner.dashboard') }}" class="logo logo-dark">
                        <span class="logo-sm">
                            @if($adminBrandLogoUrl)
                                <img src="{{ $adminBrandLogoUrl }}" alt="{{ $adminBrandName }}" class="admin-brand-logo-sm">
                            @else
                                <img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" class="admin-brand-logo-sm">
                            @endif
                        </span>
                        <span class="logo-lg">
                            @if($adminBrandLogoUrl)
                                <img src="{{ $adminBrandLogoUrl }}" alt="{{ $adminBrandName }}" class="admin-brand-logo">
                            @else
                                <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" class="admin-brand-logo">
                            @endif
                        </span>
                    </a>
                    <a href="{{ route('partner.dashboard') }}" class="logo logo-light">
                        <span class="logo-sm">
                            @if($adminBrandLogoUrl)
                                <img src="{{ $adminBrandLogoUrl }}" alt="{{ $adminBrandName }}" class="admin-brand-logo-sm">
                            @else
                                <img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" class="admin-brand-logo-sm">
                            @endif
                        </span>
                        <span class="logo-lg">
                            @if($adminBrandLogoUrl)
                                <img src="{{ $adminBrandLogoUrl }}" alt="{{ $adminBrandName }}" class="admin-brand-logo">
                            @else
                                <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" class="admin-brand-logo">
                            @endif
                        </span>
                    </a>
                </div>
                <button type="button" class="btn btn-sm px-3 font-size-16 header-item toggle-btn waves-effect" id="vertical-menu-btn">
                    <i class="fa fa-fw fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</header>
