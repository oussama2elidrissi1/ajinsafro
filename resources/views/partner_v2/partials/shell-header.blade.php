@php
    /**
     * Portail Agence Partenaire — barre de service + en-tête à navigation horizontale.
     * Les entrées reprennent celles de l'ancienne barre latérale (partner_v2.partials.sidebar),
     * donc la même distinction admin partenaire / agent partenaire s'applique.
     */
    $ppUser = auth()->user();
    $ppPartner = $ppUser?->partner ?: $ppUser?->ownedPartner;
    $ppBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $ppBrandLogo = \App\Models\Setting::brandLogoUrl('dark');

    // Coordonnées : mêmes réglages que l'en-tête public.
    $ppDefaults = ['email' => 'contact@ajinsafro.ma', 'phone' => '+212 539 323 874'];
    $ppRaw = \App\Models\Setting::getValue('wp_header');
    $ppDecoded = is_string($ppRaw) && $ppRaw !== '' ? json_decode($ppRaw, true) : null;
    $ppHeaderData = is_array($ppDecoded) ? array_replace_recursive($ppDefaults, $ppDecoded) : $ppDefaults;

    $ppIsAdmin = (bool) $ppUser?->isPartnerAdmin();
    $ppAgencyName = $ppPartner?->display_name ?: ($ppUser?->name ?? 'Agence partenaire');
    $ppRole = $ppIsAdmin ? 'Admin partenaire' : 'Agent partenaire';

    $ppInitials = strtoupper(
        collect(preg_split('/\s+/', trim((string) $ppAgencyName)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_substr((string) $part, 0, 1))
            ->implode('')
    );
    if ($ppInitials === '') { $ppInitials = 'AP'; }

    // [libellé, nom de route, motif d'activation]
    $ppCandidates = $ppIsAdmin
        ? [
            ['Tableau de bord', 'partner.dashboard', 'partner.dashboard'],
            ['Catalogue de voyage', 'partner.catalogue.index', 'partner.catalogue.*'],
            ['Mes réservations', 'partner.reservations.index', 'partner.reservations.*'],
            ['Mes agents', 'partner.agents.index', 'partner.agents.*'],
            ['Wallet', 'partner.wallet.index', 'partner.wallet.*'],
            ['Profil agence', 'partner.profile-agency.edit', 'partner.profile-agency.*'],
        ]
        : [
            ['Tableau de bord', 'partner.dashboard', 'partner.dashboard'],
            ['Catalogue de voyage', 'partner.catalogue.index', 'partner.catalogue.*'],
            ['Mes réservations', 'partner.reservations.index', 'partner.reservations.*'],
            ['Réservations à la carte', 'partner.reservations-a-la-carte', 'partner.reservations-a-la-carte'],
            ['Mon profil', 'partner.profile.show', 'partner.profile.*'],
        ];

    $ppItems = [];
    foreach ($ppCandidates as [$ppLabel, $ppRoute, $ppPattern]) {
        if (! \Illuminate\Support\Facades\Route::has($ppRoute)) {
            continue;
        }
        $ppItems[] = [
            'label' => $ppLabel,
            'href' => route($ppRoute),
            'active' => request()->routeIs($ppPattern),
        ];
    }

    $ppProfileUrl = \Illuminate\Support\Facades\Route::has('partner.profile.show') ? route('partner.profile.show') : null;
    $ppLogoutUrl = \Illuminate\Support\Facades\Route::has('partner.logout') ? route('partner.logout') : null;
    $ppHomeUrl = \Illuminate\Support\Facades\Route::has('partner.dashboard') ? route('partner.dashboard') : url('/');
@endphp

{{-- Barre de service --}}
<div class="pp-utility">
    <span>{{ data_get($ppHeaderData, 'email') }}</span>
    <span class="pp-utility__sep" aria-hidden="true">·</span>
    <span class="pp-utility__phone">{{ data_get($ppHeaderData, 'phone') }}</span>
    <div class="pp-utility__right">
        @if($ppProfileUrl)
            <a href="{{ $ppProfileUrl }}" class="pp-utility__link">Profil</a>
        @endif
        @if($ppLogoutUrl)
            {{-- La route de déconnexion est en POST : un formulaire porte le jeton CSRF. --}}
            <form method="POST" action="{{ $ppLogoutUrl }}" class="pp-utility__form">
                @csrf
                <button type="submit" class="pp-utility__logout">Déconnexion</button>
            </form>
        @endif
    </div>
</div>

{{-- En-tête --}}
<header class="pp-header" data-pp-header>
    <a href="{{ $ppHomeUrl }}" class="pp-brand" aria-label="{{ $ppBrandName }}">
        <img src="{{ $ppBrandLogo }}" alt="{{ $ppBrandName }}">
    </a>
    <span class="pp-badge">Portail partenaire</span>

    <nav class="pp-nav" aria-label="Navigation du portail partenaire">
        @foreach($ppItems as $ppItem)
            <a href="{{ $ppItem['href'] }}" class="pp-nav__link {{ $ppItem['active'] ? 'is-active' : '' }}">{{ $ppItem['label'] }}</a>
        @endforeach
    </nav>

    <div class="pp-user">
        <span class="pp-avatar" aria-hidden="true">
            <span>{{ $ppInitials }}</span>
            @if($ppPartner?->logo_url)
                <img src="{{ $ppPartner->logo_url }}" alt="" onerror="this.remove();">
            @endif
        </span>
        <span class="pp-user__meta">
            <span class="pp-user__name">{{ $ppAgencyName }}</span>
            <span class="pp-user__role">{{ $ppRole }}</span>
        </span>
    </div>

    <button type="button" class="pp-burger" data-pp-drawer-toggle aria-expanded="false" aria-controls="pp-drawer" aria-label="Ouvrir le menu">
        <span></span><span></span><span></span>
    </button>

    <div class="pp-drawer" id="pp-drawer" data-pp-drawer>
        @foreach($ppItems as $ppItem)
            <a href="{{ $ppItem['href'] }}" class="pp-drawer__link {{ $ppItem['active'] ? 'is-active' : '' }}"><span>{{ $ppItem['label'] }}</span><span class="pp-drawer__chev" aria-hidden="true">›</span></a>
        @endforeach
        @if($ppLogoutUrl)
            <form method="POST" action="{{ $ppLogoutUrl }}">
                @csrf
                <button type="submit" class="pp-drawer__link is-danger"><span>Déconnexion</span><span class="pp-drawer__chev" aria-hidden="true">›</span></button>
            </form>
        @endif
    </div>
</header>

<div class="pp-backdrop" data-pp-backdrop></div>
