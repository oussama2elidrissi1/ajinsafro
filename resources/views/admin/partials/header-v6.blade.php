@php
    $adminV2User          = $adminV2User          ?? auth()->user();
    $adminV2UserName      = $adminV2UserName      ?? ($adminV2User?->name ?? 'Admin');
    $adminV2UserRole      = $adminV2UserRole      ?? ($adminV2User?->getRoleNames()->first() ?? 'Administrateur');
    $adminV2Initials      = $adminV2Initials      ?? 'AD';
    $adminV2AvatarUrl     = $adminV2AvatarUrl     ?? null;
    $adminV2UnreadCount   = $adminV2UnreadCount   ?? 0;
    $adminV2PendingCount  = $adminV2PendingCount  ?? 0;

    $v6Title = $pageTitle ?? trim((string) View::yieldContent('page_title', View::yieldContent('title', 'Espace Admin')));
    $v6Breadcrumbs = $breadcrumbs ?? null;
    $primaryActionLabel = $primaryActionLabel ?? null;
    $primaryActionRoute = $primaryActionRoute ?? null;
    $v6DateLabel = now('Africa/Casablanca')->locale('fr')->translatedFormat('l d F Y');
@endphp

<header class="aj-v6-topbar" id="aj-admin-v2-topbar">
    <div class="aj-v6-topbar-left">
        <button type="button" class="aj-hamburger" id="aj-admin-v2-hamburger" aria-label="Ouvrir le menu">
            <i class="bx bx-menu"></i>
        </button>

        <div class="aj-v6-page-meta">
            <h1 class="aj-v6-page-title">{{ $v6Title }}</h1>
            @if(is_array($v6Breadcrumbs) && count($v6Breadcrumbs))
                <div class="aj-v6-page-breadcrumb">
                    @foreach($v6Breadcrumbs as $index => $crumb)
                        @if($index > 0)<span>›</span>@endif
                        @if(!empty($crumb['url']))
                            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] ?? '' }}</a>
                        @else
                            {{ $crumb['label'] ?? '' }}
                        @endif
                    @endforeach
                </div>
            @else
                <div class="aj-v6-page-breadcrumb">Accueil <span>›</span> Tableau de bord</div>
            @endif
        </div>
    </div>

    <div class="aj-v6-topbar-center"></div>

    <div class="aj-v6-topbar-actions">
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
