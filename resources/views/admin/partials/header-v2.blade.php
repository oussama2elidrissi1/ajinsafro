@php
    $adminV2User = $adminV2User ?? auth()->user();
    $adminV2UserName = $adminV2UserName ?? ($adminV2User?->name ?? 'Admin');
    $adminV2UserRole = $adminV2UserRole ?? ($adminV2User?->getRoleNames()->first() ?? 'Administrateur');
    $adminV2Initials = $adminV2Initials ?? 'AD';
    $adminV2AvatarUrl = $adminV2AvatarUrl ?? null;
    $adminV2UnreadCount = $adminV2UnreadCount ?? 0;
    $adminNotifications = $adminNotifications ?? collect();
    $adminNotificationsUnread = (int) ($adminNotificationsUnread ?? 0);
@endphp

<header class="aj-topbar" id="aj-admin-v2-topbar">
    <div class="aj-topbar-left">
        <button type="button" class="aj-hamburger" id="aj-admin-v2-hamburger" aria-label="Ouvrir le menu">
            <i class="bx bx-menu"></i>
        </button>

        <div class="aj-topbar-search">
            <span class="aj-search-icon"><i class="bx bx-search"></i></span>
            <input type="text" placeholder="Rechercher (voyage, agence, reservation...)">
            <span class="aj-search-shortcut">Ctrl + K</span>
        </div>
    </div>

    <div class="aj-topbar-actions">
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

        @if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index'))
            <a href="{{ route('admin.messagerie.index') }}" class="aj-topbar-notif" title="Messagerie">
                <i class="bx bx-message-rounded-dots"></i>
                @if($adminV2UnreadCount > 0)
                    <b class="aj-notif-badge">{{ min($adminV2UnreadCount, 99) }}</b>
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
            <span class="aj-topbar-profile-caret"><i class="bx bx-chevron-down"></i></span>
        @if(\Illuminate\Support\Facades\Route::has('admin.profile.edit'))
            </a>
        @else
            </span>
        @endif
    </div>
</header>
