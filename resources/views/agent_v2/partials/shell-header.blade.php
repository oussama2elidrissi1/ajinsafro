@php
    /**
     * Espace Agent — barre de service + en-tête à navigation horizontale.
     * Les entrées viennent du même menu que l'ancienne barre latérale
     * ($agentPortalAdminMenu, construit par AppServiceProvider), donc les mêmes
     * permissions s'appliquent. La fenêtre des notifications reste celle du
     * partial internal-v2-topbar, dont seule la barre est masquée en CSS.
     */
    $eagUser = auth()->user();
    $eagBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $eagBrandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $eagIsAgentRoute = request()->routeIs('agent.*');

    // Coordonnées : mêmes réglages que l'en-tête public.
    $eagDefaults = ['email' => 'contact@ajinsafro.ma', 'phone' => '+212 5 39 32 38 74'];
    $eagRaw = \App\Models\Setting::getValue('wp_header');
    $eagDecoded = is_string($eagRaw) && $eagRaw !== '' ? json_decode($eagRaw, true) : null;
    $eagHeaderData = is_array($eagDecoded) ? array_replace_recursive($eagDefaults, $eagDecoded) : $eagDefaults;

    $eagUnread = 0;
    if ($eagUser && $eagIsAgentRoute) {
        try {
            $eagUnread = \App\Models\ClientNotification::query()
                ->where('user_id', $eagUser->id)
                ->where('is_read', false)
                ->count();
        } catch (\Throwable $e) {
            $eagUnread = 0;
        }
    }

    $eagName = $eagUser?->name ?: 'Agent';
    $eagInitials = strtoupper(
        collect(preg_split('/\s+/', trim((string) $eagName)))
            ->filter()
            ->take(2)
            ->map(fn ($s) => mb_substr((string) $s, 0, 1))
            ->implode('')
    );
    if ($eagInitials === '') { $eagInitials = 'AG'; }
    $eagRole = $eagUser?->canQuoteCustomRequests()
        ? 'Agent Offline'
        : ($eagUser?->getRoleNames()->first() ?? ($eagUser?->is_admin ? 'Admin' : 'Utilisateur'));
    $eagRole = \Illuminate\Support\Str::title(\Illuminate\Support\Str::replace('_', ' ', (string) $eagRole));
    $eagBranch = $eagUser?->branch?->name;

    // Entrées de navigation : tableau de bord, menu du portail, ajout de voyage.
    $eagItems = [];
    if ($eagUser && $eagUser->can('dashboard.view') && \Illuminate\Support\Facades\Route::has('agent.dashboard')) {
        $eagItems[] = ['label' => 'Dashboard', 'href' => route('agent.dashboard'), 'active' => request()->routeIs('agent.dashboard')];
    }
    foreach (($agentPortalAdminMenu ?? []) as $eagNode) {
        $eagHref = (string) ($eagNode['href'] ?? '');
        if ($eagHref === '' || empty($eagNode['is_clickable'] ?? true)) {
            continue;
        }
        $eagItems[] = ['label' => (string) $eagNode['label'], 'href' => $eagHref, 'active' => !empty($eagNode['active'])];
    }
    $eagIdentity = \Illuminate\Support\Str::lower(trim(($eagUser?->name ?? '') . ' ' . ($eagUser?->email ?? '')));
    if (\Illuminate\Support\Facades\Route::has('agent.voyages.index')
        && \Illuminate\Support\Str::contains($eagIdentity, ['oumaima', 'oumayma'])) {
        $eagItems[] = ['label' => 'Ajouter voyage', 'href' => route('agent.voyages.index'), 'active' => request()->routeIs('agent.voyages.*')];
    }

    $eagProfileUrl = \Illuminate\Support\Facades\Route::has('agent.profile') ? route('agent.profile') : null;
    $eagLogoutUrl = \Illuminate\Support\Facades\Route::has('logout.get') ? route('logout.get') : null;
    $eagHomeUrl = \Illuminate\Support\Facades\Route::has('agent.dashboard') ? route('agent.dashboard') : url('/');
@endphp

{{-- Barre de service --}}
<div class="eag-utility">
    <span>{{ data_get($eagHeaderData, 'email') }}</span>
    <span class="eag-utility__sep" aria-hidden="true">·</span>
    <span class="eag-utility__phone">{{ data_get($eagHeaderData, 'phone') }}</span>
    <div class="eag-utility__right">
        @if($eagUser && $eagIsAgentRoute)
            <button type="button" class="eag-utility__notif" data-bs-toggle="modal" data-bs-target="#agentNotificationsModal">
                Notifications
                @if($eagUnread > 0)
                    <b class="eag-utility__badge">{{ $eagUnread > 99 ? '99+' : $eagUnread }}</b>
                @endif
            </button>
        @endif
        @if($eagProfileUrl)
            <a href="{{ $eagProfileUrl }}" class="eag-utility__link">Profil</a>
        @endif
        @if($eagLogoutUrl)
            <a href="{{ $eagLogoutUrl }}" class="eag-utility__link">Déconnexion</a>
        @endif
    </div>
</div>

{{-- En-tête --}}
<header class="eag-header" data-eag-header>
    <a href="{{ $eagHomeUrl }}" class="eag-brand" aria-label="{{ $eagBrandName }}">
        <img src="{{ $eagBrandLogo }}" alt="{{ $eagBrandName }}">
    </a>
    <span class="eag-badge">Espace agent</span>

    <nav class="eag-nav" aria-label="Navigation de l'espace agent">
        @foreach($eagItems as $eagItem)
            <a href="{{ $eagItem['href'] }}" class="eag-nav__link {{ $eagItem['active'] ? 'is-active' : '' }}">{{ $eagItem['label'] }}</a>
        @endforeach
    </nav>

    <div class="eag-user">
        <span class="eag-avatar" aria-hidden="true">
            <span>{{ $eagInitials }}</span>
            @if($eagUser?->avatar_url)
                <img src="{{ $eagUser->avatar_url }}" alt="" onerror="this.remove();">
            @endif
        </span>
        <span class="eag-user__meta">
            <span class="eag-user__name">{{ $eagName }}</span>
            <span class="eag-user__role">{{ $eagRole }}{{ $eagBranch ? ' · ' . $eagBranch : '' }}</span>
        </span>
    </div>

    <button type="button" class="eag-burger" data-eag-drawer-toggle aria-expanded="false" aria-controls="eag-drawer" aria-label="Ouvrir le menu">
        <span></span><span></span><span></span>
    </button>

    <div class="eag-drawer" id="eag-drawer" data-eag-drawer>
        @foreach($eagItems as $eagItem)
            <a href="{{ $eagItem['href'] }}" class="eag-drawer__link {{ $eagItem['active'] ? 'is-active' : '' }}"><span>{{ $eagItem['label'] }}</span><span class="eag-drawer__chev" aria-hidden="true">›</span></a>
        @endforeach
        @if($eagProfileUrl)
            <a href="{{ $eagProfileUrl }}" class="eag-drawer__link {{ request()->routeIs('agent.profile') ? 'is-active' : '' }}"><span>Mon profil</span><span class="eag-drawer__chev" aria-hidden="true">›</span></a>
        @endif
        @if($eagLogoutUrl)
            <a href="{{ $eagLogoutUrl }}" class="eag-drawer__link is-danger"><span>Se déconnecter</span><span class="eag-drawer__chev" aria-hidden="true">›</span></a>
        @endif
    </div>
</header>

<div class="eag-backdrop" data-eag-backdrop></div>
