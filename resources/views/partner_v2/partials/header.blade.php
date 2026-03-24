@php
    // Source of truth: wp-plugin/ajinsafro-traveler-home/parts/header.php
    $defaults = [
        'enabled' => true,
        'topbar_enabled' => true,
        'phone' => '+212 5 39 32 38 74',
        'email' => 'contact@ajinsafro.ma',
        'socials' => [
            'facebook' => '#',
            'twitter' => '#',
            'instagram' => '#',
            'youtube' => '#',
            'linkedin' => '#',
        ],
        'navbar_enabled' => true,
        'logo_url' => '',
        'show_auth_links' => true,
        'login_url' => rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/login',
        'signup_url' => rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/register',
        'menu_source' => 'laravel_links',
        'links' => [],
        'lowcost_enabled' => true,
        'lowcost_text' => 'Formule low cost',
        'lowcost_url' => '#',
    ];

    $raw = \App\Models\Setting::getValue('wp_header');
    $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    $hdr = is_array($decoded) ? array_replace_recursive($defaults, $decoded) : $defaults;

    $socials = isset($hdr['socials']) && is_array($hdr['socials']) ? $hdr['socials'] : [];
    $socialIcons = [
        'facebook'  => '<i class="fab fa-facebook-f"></i>',
        'twitter'   => '<i class="fab fa-twitter"></i>',
        'youtube'   => '<i class="fab fa-youtube"></i>',
        'instagram' => '<i class="fab fa-instagram"></i>',
        'linkedin'  => '<i class="fab fa-linkedin-in"></i>',
    ];

    $user = request()->user();
    $voyagesPageUrl = url('/voyages');
    $defaultMenuItems = [
        ['label' => 'Voyages', 'url' => $voyagesPageUrl, 'icon' => 'fas fa-suitcase-rolling', 'active' => false, 'children' => []],
        ['label' => 'Hébergement', 'url' => '#hebergement', 'icon' => 'fas fa-hotel', 'active' => false, 'children' => []],
        ['label' => 'Activités', 'url' => '#activites', 'icon' => 'fas fa-camera', 'active' => false, 'children' => []],
        ['label' => 'Transfert', 'url' => '#transfert', 'icon' => 'fas fa-car-side', 'active' => false, 'children' => []],
        ['label' => 'Hajj & Omra', 'url' => '#hajj-omra', 'icon' => 'fas fa-kaaba', 'active' => false, 'children' => []],
        ['label' => 'Votre guide', 'url' => '#guide', 'icon' => 'fas fa-map-signs', 'active' => false, 'children' => []],
    ];

    /** When false (e.g. espace agent sur booking), use logout GET route instead of partner.logout. */
    $portalLogoutUsesPartner = $portalLogoutUsesPartner ?? true;
@endphp

@if(!empty($hdr['enabled']))
<header class="aj-header" id="aj-header">
    @if(!empty($hdr['topbar_enabled']))
    <div class="aj-topbar">
        <div class="aj-container aj-topbar__inner">
            <div class="aj-topbar__left">
                <div class="aj-topbar__socials">
                    @foreach($socialIcons as $key => $icon)
                        @php $socialUrl = !empty($socials[$key]) ? (string) $socials[$key] : ''; @endphp
                        @if($socialUrl !== '')
                            <a href="{{ $socialUrl }}" class="aj-topbar__social-link" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($key) }}">
                                {!! $icon !!}
                            </a>
                        @endif
                    @endforeach
                </div>
                <div class="aj-topbar__contact">
                    @if(!empty($hdr['email']))
                        <span class="aj-topbar__item">
                            <i class="far fa-envelope"></i>
                            {{ $hdr['email'] }}
                        </span>
                    @endif
                    @if(!empty($hdr['phone']))
                        <span class="aj-topbar__item">
                            <i class="fas fa-phone"></i>
                            {{ $hdr['phone'] }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="aj-topbar__right">
                <div class="aj-topbar__selector" id="aj-lang-selector">
                    <img src="https://upload.wikimedia.org/wikipedia/en/c/c3/Flag_of_France.svg" alt="FR" class="aj-topbar__flag">
                    <span>FR</span>
                    <i class="fas fa-chevron-down aj-topbar__caret"></i>
                </div>

                <div class="aj-topbar__selector" id="aj-currency-selector">
                    <span>MAD</span>
                    <i class="fas fa-chevron-down aj-topbar__caret"></i>
                </div>

                @if(!empty($hdr['show_auth_links']))
                <div class="aj-topbar__auth">
                    @if($user)
                        <span class="aj-topbar__auth-link d-inline-flex align-items-center gap-2">
                            <img src="{{ $user->avatar_url }}" alt="Avatar" class="rounded-circle" style="width:24px;height:24px;object-fit:cover;">
                            <span>{{ $user->name }}</span>
                        </span>
                        @if($portalLogoutUsesPartner)
                            <form method="POST" action="{{ route('partner.logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="aj-topbar__auth-link aj-topbar__auth-link--signup border-0">
                                    {{ __('SE DÉCONNECTER') }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('logout.get') }}" class="aj-topbar__auth-link aj-topbar__auth-link--signup">
                                {{ __('SE DÉCONNECTER') }}
                            </a>
                        @endif
                    @else
                        <a href="{{ !empty($hdr['login_url']) ? $hdr['login_url'] : (rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/login') }}" class="aj-topbar__auth-link">
                            {{ __('SE CONNECTER') }}
                        </a>
                        <a href="{{ !empty($hdr['signup_url']) ? $hdr['signup_url'] : (rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/register') }}" class="aj-topbar__auth-link aj-topbar__auth-link--signup">
                            {{ __("S'INSCRIRE") }}
                        </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if(!empty($hdr['navbar_enabled']))
    <nav class="aj-navbar" id="aj-navbar">
        <div class="aj-container aj-navbar__inner">
            <div class="aj-navbar__logo">
                @if(!empty($hdr['logo_url']))
                    <a href="{{ url('/') }}">
                        <img src="{{ $hdr['logo_url'] }}" alt="{{ config('app.name', 'Ajinsafro') }}" class="aj-navbar__logo-img" loading="eager" fetchpriority="high">
                    </a>
                @else
                    <a href="{{ url('/') }}">
                        <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="{{ config('app.name', 'Ajinsafro') }}" class="aj-navbar__logo-img" loading="eager" fetchpriority="high">
                    </a>
                @endif
            </div>

            <button type="button" class="aj-navbar__burger aj-header__toggle" id="aj-burger" aria-label="Menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>

            <div class="aj-drawer aj-header__drawer" id="aj-drawer" aria-hidden="true">
                <div class="aj-drawer__header">
                    <span class="aj-drawer__title">{{ __('Menu') }}</span>
                    <button type="button" class="aj-drawer__close" id="aj-drawer-close" aria-label="{{ __('Fermer') }}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                @if(!empty($hdr['show_auth_links']))
                <div class="aj-drawer__auth aj-header__auth--mobile">
                    @if($user)
                        <div class="aj-auth-link aj-auth-link--block d-inline-flex align-items-center gap-2">
                            <img src="{{ $user->avatar_url }}" alt="Avatar" class="rounded-circle" style="width:24px;height:24px;object-fit:cover;">
                            <span>{{ $user->name }}</span>
                        </div>
                        @if($portalLogoutUsesPartner)
                            <form method="POST" action="{{ route('partner.logout') }}">
                                @csrf
                                <button type="submit" class="aj-auth-link aj-auth-link--block border-0 bg-transparent text-start w-100">
                                    {{ __('Se déconnecter') }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('logout.get') }}" class="aj-auth-link aj-auth-link--block">{{ __('Se déconnecter') }}</a>
                        @endif
                    @else
                        <a href="{{ !empty($hdr['login_url']) ? $hdr['login_url'] : (rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/login') }}" class="aj-auth-link aj-auth-link--block">{{ __('Se connecter') }}</a>
                        <a href="{{ !empty($hdr['signup_url']) ? $hdr['signup_url'] : (rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/register') }}" class="aj-auth-link aj-auth-link--signup aj-auth-link--block">{{ __("S'inscrire") }}</a>
                    @endif
                </div>
                @endif

                <div class="aj-navbar__menu" id="aj-nav-menu">
                    @php
                        $navLinks = !empty($hdr['links']) && is_array($hdr['links']) ? $hdr['links'] : [];
                        if (empty($navLinks)) {
                            $navLinks = $defaultMenuItems;
                        }
                    @endphp
                    <ul class="aj-nav-list">
                        @foreach($navLinks as $link)
                            @php
                                $label = !empty($link['label']) ? (string) $link['label'] : '';
                                $url = !empty($link['url']) ? (string) $link['url'] : '#';
                                $icon = !empty($link['icon']) ? (string) $link['icon'] : '';
                                $children = !empty($link['children']) && is_array($link['children']) ? $link['children'] : [];
                                $hasSub = !empty($children);
                                $isActive = !empty($link['active']);
                                $isHighlight = !empty($link['highlight']);
                            @endphp
                            <li class="{{ $hasSub ? 'aj-has-sub' : '' }}{{ $isActive ? ' aj-active' : '' }}{{ $isHighlight ? ' aj-highlight' : '' }}">
                                <a href="{{ $url }}" class="{{ $isHighlight ? 'aj-nav-highlight' : '' }}">
                                    @if($icon)
                                        <i class="{{ $icon }}"></i>
                                    @endif
                                    <span>{{ $label }}</span>
                                    @if($hasSub)
                                        <i class="fas fa-chevron-down aj-caret"></i>
                                    @endif
                                </a>
                                @if($hasSub)
                                    <ul class="aj-sub-menu">
                                        @foreach($children as $child)
                                            <li>
                                                <a href="{{ !empty($child['url']) ? $child['url'] : '#' }}">
                                                    @if(!empty($child['icon']))
                                                        <i class="{{ $child['icon'] }}"></i>
                                                    @endif
                                                    {{ !empty($child['label']) ? $child['label'] : '' }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if(!empty($hdr['lowcost_enabled']))
                <div class="aj-drawer__lowcost">
                    <a href="{{ !empty($hdr['lowcost_url']) ? $hdr['lowcost_url'] : '#' }}" class="aj-lowcost-btn">
                        <i class="fas fa-fire"></i>
                        <span>{{ !empty($hdr['lowcost_text']) ? $hdr['lowcost_text'] : 'Formule low cost' }}</span>
                    </a>
                </div>
                @endif
            </div>

            @if(!empty($hdr['lowcost_enabled']))
            <div class="aj-navbar__lowcost aj-header__lowcost--desktop">
                <a href="{{ !empty($hdr['lowcost_url']) ? $hdr['lowcost_url'] : '#' }}" class="aj-lowcost-btn aj-lowcost-btn--animate">
                    <i class="fas fa-fire aj-lowcost-btn__icon"></i>
                    <span>{{ !empty($hdr['lowcost_text']) ? $hdr['lowcost_text'] : 'Formule low cost' }}</span>
                </a>
            </div>
            @endif
        </div>
    </nav>
    @endif
</header>
@endif

