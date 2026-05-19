@php
    $adminV2User          = $adminV2User          ?? auth()->user();
    $adminV2UserName      = $adminV2UserName      ?? ($adminV2User?->name ?? 'Admin');
    $adminV2UserRole      = $adminV2UserRole      ?? ($adminV2User?->getRoleNames()->first() ?? 'Administrateur');
    $adminV2Initials      = $adminV2Initials      ?? 'AD';
    $adminV2AvatarUrl     = $adminV2AvatarUrl     ?? null;
    $adminV2UnreadCount   = $adminV2UnreadCount   ?? 0;
    $adminV2PendingCount  = $adminV2PendingCount  ?? 0;

    $v6Title = trim((string) View::yieldContent('page_title', View::yieldContent('title', 'Espace Admin')));
    $v6DateLabel = now('Africa/Casablanca')->locale('fr')->translatedFormat('l d F Y');
@endphp

<header class="aj-v6-topbar" id="aj-admin-v2-topbar">
    <div class="aj-v6-topbar-left">
        <button type="button" class="aj-hamburger" id="aj-admin-v2-hamburger" aria-label="Ouvrir le menu">
            <i class="bx bx-menu"></i>
        </button>

        <div class="aj-v6-page-meta">
            <h1 class="aj-v6-page-title">{{ $v6Title }}</h1>
            <div class="aj-v6-page-breadcrumb">Accueil <span>›</span> Tableau de bord</div>
        </div>
    </div>

    <div class="aj-v6-topbar-center">
        <div class="aj-v6-search">
            <span class="aj-search-icon"><i class="bx bx-search"></i></span>
            <input type="text" placeholder="Rechercher (voyage, agence, réservation, client...)">
        </div>
    </div>

    <div class="aj-v6-topbar-actions">
        <div class="aj-v6-chip aj-v6-date-chip">
            <i class="bx bx-calendar"></i>
            <span>{{ $v6DateLabel }}</span>
        </div>
        <button type="button" class="aj-v6-chip">
            <i class="bx bx-slider-alt"></i>
            <span>Filtres</span>
        </button>

        @if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index'))
            <a href="{{ route('admin.messagerie.index') }}" class="aj-topbar-notif" title="Messagerie">
                <i class="bx bx-envelope"></i>
                @if($adminV2UnreadCount > 0)
                    <b class="aj-notif-badge">{{ min($adminV2UnreadCount, 99) }}</b>
                @endif
            </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.reservations.en-attente'))
            <a href="{{ route('admin.reservations.en-attente') }}" class="aj-topbar-notif" title="Réservations en attente">
                <i class="bx bx-bell"></i>
                @if($adminV2PendingCount > 0)
                    <b class="aj-notif-badge">{{ min($adminV2PendingCount, 99) }}</b>
                @endif
            </a>
        @endif

        @hasSection('header_primary_action')
            @yield('header_primary_action')
        @elseif(\Illuminate\Support\Facades\Route::has('admin.reservations.create'))
            <a href="{{ route('admin.reservations.create') }}" class="aj-v6-primary-btn">
                <i class="bx bx-plus"></i>
                <span>Réservations</span>
            </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.profile.edit'))
            <a href="{{ route('admin.profile.edit') }}" class="aj-topbar-profile" aria-label="Mon profil">
        @else
            <span class="aj-topbar-profile">
        @endif
            <span class="aj-topbar-profile-avatar-shell">
                <img src="{{ $adminV2AvatarUrl }}"
                     alt="{{ $adminV2UserName }}"
                     class="aj-topbar-profile-avatar"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                <span class="aj-topbar-avatar-fallback">{{ $adminV2Initials }}</span>
            </span>
            <div class="aj-topbar-profile-info">
                <div class="aj-topbar-profile-name">{{ $adminV2UserName }}</div>
                <div class="aj-topbar-profile-role">{{ $adminV2UserRole }}</div>
            </div>
        @if(\Illuminate\Support\Facades\Route::has('admin.profile.edit'))
            </a>
        @else
            </span>
        @endif
    </div>
</header>
