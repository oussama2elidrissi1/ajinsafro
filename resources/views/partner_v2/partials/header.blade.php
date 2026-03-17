@php
    /** Header settings are managed in Laravel admin: /admin/settings/home-page (tab Header) */
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
        'login_url' => '/login',
        'signup_url' => '/register',
        'menu_source' => 'laravel_links',
        'links' => [],
        'lowcost_enabled' => true,
        'lowcost_text' => 'Formule low cost',
        'lowcost_url' => '#',
    ];

    $raw = \App\Models\Setting::getValue('wp_header');
    $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    $hdr = is_array($decoded) ? array_replace_recursive($defaults, $decoded) : $defaults;

    $socialIcons = [
        'facebook'  => '<i class="fab fa-facebook-f"></i>',
        'twitter'   => '<i class="fab fa-twitter"></i>',
        'youtube'   => '<i class="fab fa-youtube"></i>',
        'instagram' => '<i class="fab fa-instagram"></i>',
        'linkedin'  => '<i class="fab fa-linkedin-in"></i>',
    ];

    $navLinks = is_array(data_get($hdr, 'links')) ? data_get($hdr, 'links') : [];
    if (empty($navLinks)) {
        // Default public menu (anchors kept, as on public homepage)
        $navLinks = [
            ['label' => 'Voyages', 'url' => url('/voyages'), 'icon' => 'fas fa-suitcase-rolling', 'children' => []],
            ['label' => 'Hébergement', 'url' => '#hebergement', 'icon' => 'fas fa-hotel', 'children' => []],
            ['label' => 'Activités', 'url' => '#activites', 'icon' => 'fas fa-camera', 'children' => []],
            ['label' => 'Transfert', 'url' => '#transfert', 'icon' => 'fas fa-car-side', 'children' => []],
            ['label' => 'Hajj & Omra', 'url' => '#hajj-omra', 'icon' => 'fas fa-kaaba', 'children' => []],
            ['label' => 'Votre guide', 'url' => '#guide', 'icon' => 'fas fa-map-signs', 'children' => []],
        ];
    }

    $user = request()->user();
@endphp

@if(!empty($hdr['enabled']))
<header class="aj-header" id="aj-header">
    @if(!empty($hdr['topbar_enabled']))
        <div class="aj-topbar">
            <div class="aj-container aj-topbar__inner">
                <div class="aj-topbar__left">
                    <div class="aj-topbar__socials">
                        @foreach($socialIcons as $key => $icon)
                            @php $url = (string) data_get($hdr, "socials.$key", ''); @endphp
                            @if($url !== '' && $url !== '#')
                                <a href="{{ $url }}" class="aj-topbar__social-link" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($key) }}">
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

                    {{-- Partner user block (keeps public look, adds partner context) --}}
                    @if($user)
                        <div class="aj-topbar__partner">
                            <img src="{{ $user->avatar_url }}" alt="Avatar" class="aj-topbar__partner-avatar">
                            <span class="aj-topbar__partner-name">{{ $user->name }}</span>
                            <form action="{{ route('logout') }}" method="POST" class="aj-topbar__partner-logout">
                                @csrf
                                <button type="submit" class="aj-topbar__auth-link aj-topbar__auth-link--signup">Déconnexion</button>
                            </form>
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
                    @php $logo = (string) data_get($hdr, 'logo_url', ''); @endphp
                    <a href="{{ url('/') }}">
                        <img
                            src="{{ $logo !== '' ? $logo : URL::asset('build/images/logo-dark.png') }}"
                            alt="AjinSafro"
                            class="aj-navbar__logo-img"
                            loading="eager"
                            fetchpriority="high"
                        >
                    </a>
                </div>

                <button type="button" class="aj-navbar__burger aj-header__toggle" id="aj-burger" aria-label="Menu" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="aj-drawer aj-header__drawer" id="aj-drawer" aria-hidden="true">
                    <div class="aj-drawer__header">
                        <span class="aj-drawer__title">Menu</span>
                        <button type="button" class="aj-drawer__close" id="aj-drawer-close" aria-label="Fermer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="aj-navbar__menu" id="aj-nav-menu">
                        <ul class="aj-nav-list">
                            @foreach($navLinks as $link)
                                @php
                                    $label = (string) data_get($link, 'label', '');
                                    $url = (string) data_get($link, 'url', '#');
                                    $icon = (string) data_get($link, 'icon', '');
                                    $children = is_array(data_get($link, 'children')) ? data_get($link, 'children') : [];
                                    $hasSub = !empty($children);
                                    $highlight = (bool) data_get($link, 'highlight', false);
                                @endphp
                                <li class="{{ $hasSub ? 'aj-has-sub' : '' }}{{ $highlight ? ' aj-highlight' : '' }}">
                                    <a href="{{ $url }}" class="{{ $highlight ? 'aj-nav-highlight' : '' }}">
                                        @if($icon !== '')
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
                                                    <a href="{{ data_get($child, 'url', '#') }}">
                                                        @if(!empty($child['icon']))
                                                            <i class="{{ $child['icon'] }}"></i>
                                                        @endif
                                                        {{ data_get($child, 'label', '') }}
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
                        <div class="aj-navbar__lowcost aj-header__lowcost--desktop">
                            <a href="{{ data_get($hdr, 'lowcost_url', '#') }}" class="aj-lowcost-btn aj-lowcost-btn--animate">
                                <i class="fas fa-fire aj-lowcost-btn__icon"></i>
                                <span>{{ data_get($hdr, 'lowcost_text', 'Formule low cost') }}</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </nav>
    @endif
</header>
@endif

