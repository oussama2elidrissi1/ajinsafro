@php
    $adminUser      = $adminUser      ?? auth()->user();
    $adminUserName  = $adminUserName  ?? ($adminUser?->name ?? 'Admin');
    $adminUserRole  = $adminUserRole  ?? ($adminUser?->getRoleNames()->first() ?? 'Administrateur');
    $adminInitials  = $adminInitials  ?? 'AD';
    $adminAvatarUrl = $adminAvatarUrl ?? null;

    $unreadCount = $unreadCount ?? 0;
    $adminNotifications = $adminNotifications ?? collect();
    $adminNotificationsUnread = (int) ($adminNotificationsUnread ?? 0);

    $v6Title = html_entity_decode(
        $pageTitle ?? trim((string) View::yieldContent('page_title', View::yieldContent('title', 'Espace Admin'))),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    $v6Breadcrumbs = $breadcrumbs ?? null;
    $primaryActionLabel = $primaryActionLabel ?? null;
    $primaryActionRoute = $primaryActionRoute ?? null;
@endphp

<header class="aj-v6-topbar" id="adminV6Header">
    <div class="aj-v6-topbar-left">
        <button type="button" class="aj-hamburger" id="adminV6Hamburger" aria-label="Ouvrir le menu">
            <i class="bx bx-menu"></i>
        </button>

        <div class="aj-v6-page-meta">
            <h1 class="aj-v6-page-title">{{ $v6Title }}</h1>
            @if(is_array($v6Breadcrumbs) && count($v6Breadcrumbs))
                <div class="aj-v6-page-breadcrumb">
                    @foreach($v6Breadcrumbs as $index => $crumb)
                        @if($index > 0)<span>&gt;</span>@endif
                        @if(!empty($crumb['url']))
                            <a href="{{ $crumb['url'] }}">{{ html_entity_decode((string) ($crumb['label'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') }}</a>
                        @else
                            {{ html_entity_decode((string) ($crumb['label'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') }}
                        @endif
                    @endforeach
                </div>
            @else
                <div class="aj-v6-page-breadcrumb">Accueil <span>&gt;</span> Tableau de bord</div>
            @endif
        </div>
    </div>

    <div class="aj-v6-topbar-center">
        <div class="aj-v6-search">
            <i class="bx bx-search aj-search-icon" aria-hidden="true"></i>
            <input type="search" placeholder="Rechercher..." aria-label="Rechercher" autocomplete="off">
        </div>
    </div>

    <div class="aj-v6-topbar-actions">
        @if(is_string($primaryActionLabel) && $primaryActionLabel !== '' && is_string($primaryActionRoute) && $primaryActionRoute !== '' && \Illuminate\Support\Facades\Route::has($primaryActionRoute))
            <a href="{{ route($primaryActionRoute) }}" class="aj-v6-primary-btn">
                <i class="bx bx-plus" aria-hidden="true"></i>
                <span>{{ $primaryActionLabel }}</span>
            </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index'))
            <a href="{{ route('admin.messagerie.index') }}" class="aj-topbar-notif" title="Messagerie">
                <i class="bx bx-envelope"></i>
                @if((int) $unreadCount > 0)
                    <b class="aj-notif-badge">{{ min((int) $unreadCount, 99) }}</b>
                @endif
            </a>
        @endif

        <div class="dropdown">
            <button type="button" class="aj-topbar-notif border-0" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Notifications">
                <i class="bx bx-bell"></i>
                @if($adminNotificationsUnread > 0)
                    <b class="aj-notif-badge">{{ min($adminNotificationsUnread, 99) }}</b>
                @endif
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg" style="width:min(420px, calc(100vw - 24px)); border:1px solid #dbe8f5; border-radius:16px; overflow:hidden;">
                <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-3 border-bottom" style="background:#fbfdff;">
                    <div>
                        <div class="fw-bold text-dark">Notifications</div>
                        <div class="small text-muted">{{ $adminNotificationsUnread }} non lue(s)</div>
                    </div>
                    @if($adminNotificationsUnread > 0 && \Illuminate\Support\Facades\Route::has('admin.notifications.read-all'))
                        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light border fw-semibold">Tout marquer lu</button>
                        </form>
                    @endif
                </div>
                <div style="max-height:360px; overflow:auto;">
                    @forelse($adminNotifications as $notification)
                        <div class="d-flex align-items-start justify-content-between gap-3 px-3 py-3 border-bottom {{ $notification->is_read ? '' : 'bg-light' }}">
                            <div class="min-w-0">
                                <div class="fw-bold text-dark text-truncate">{{ $notification->title }}</div>
                                <div class="small text-muted" style="line-height:1.45;">{{ \Illuminate\Support\Str::limit($notification->message, 130) }}</div>
                                <div class="small text-muted mt-1">{{ $notification->created_at?->diffForHumans() }}</div>
                            </div>
                            <div class="flex-shrink-0">
                                @if(! $notification->is_read && \Illuminate\Support\Facades\Route::has('admin.notifications.read'))
                                    <form method="POST" action="{{ route('admin.notifications.read', $notification) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">{{ $notification->link ? 'Ouvrir' : 'Lu' }}</button>
                                    </form>
                                @elseif($notification->link)
                                    <a href="{{ $notification->link }}" class="btn btn-sm btn-light border">Ouvrir</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center px-4 py-5">
                            <div class="fw-bold text-dark">Aucune notification</div>
                            <div class="small text-muted mt-1">Les alertes de votre compte apparaitront ici.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        @if(\Illuminate\Support\Facades\Route::has('admin.profile.edit'))
            <a href="{{ route('admin.profile.edit') }}" class="aj-topbar-profile" aria-label="Mon profil">
        @else
            <span class="aj-topbar-profile">
        @endif
            <span class="aj-topbar-profile-avatar-shell">
                <img src="{{ $adminAvatarUrl }}"
                     alt="{{ $adminUserName }}"
                     class="aj-topbar-profile-avatar"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                <span class="aj-topbar-avatar-fallback">{{ $adminInitials }}</span>
            </span>
            <div class="aj-topbar-profile-info">
                <div class="aj-topbar-profile-name">{{ $adminUserName }}</div>
                <div class="aj-topbar-profile-role">{{ $adminUserRole }}</div>
            </div>
        @if(\Illuminate\Support\Facades\Route::has('admin.profile.edit'))
            </a>
        @else
            </span>
        @endif
    </div>
</header>
