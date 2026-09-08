@php
    /**
     * Espace Admin v2 — coque unique de l'administration (navigation horizontale + méga-menus).
     * Tous les layouts admin historiques (admin-v6, admin-v2, master-ajinsafro côté admin) délèguent ici.
     *
     * Points d'extension pour les vues et layouts intermédiaires :
     *   @section('title')          titre de l'onglet
     *   @section('body_class')     classes body supplémentaires
     *   @section('core_scripts')   remplace le socle jQuery + Bootstrap (ex. layouts.vendor-scripts)
     *   @section('hidePageFooter') '1' pour masquer le pied de page
     *   @push('styles') / @push('css') / @push('scripts') / @push('body-end')
     */
    $eaUser = auth()->user();
    $eaBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $eaBrandLogo = \App\Models\Setting::brandLogoUrl('dark');

    $eaUserName = $eaUser?->name ?? 'Admin';
    $eaInitials = strtoupper(
        collect(preg_split('/\s+/', trim((string) $eaUserName)))
            ->filter()
            ->take(2)
            ->map(fn ($s) => mb_substr((string) $s, 0, 1))
            ->implode('')
    );
    if ($eaInitials === '') { $eaInitials = 'AD'; }
    $eaAvatarUrl = $eaUser?->avatar_url;

    $eaUnreadMessages = 0;
    $eaNotifications = collect();
    $eaNotificationsUnread = 0;
    try {
        if ($eaUser && \Illuminate\Support\Facades\Schema::hasTable('messages')) {
            $eaUnreadMessages = \App\Models\Message::query()
                ->where('recipient_id', $eaUser->id)
                ->where('folder_recipient', 'inbox')
                ->where('read', false)
                ->count();
        }
    } catch (\Throwable $e) {
        $eaUnreadMessages = 0;
    }
    try {
        if ($eaUser && \Illuminate\Support\Facades\Schema::hasTable('notifications')) {
            $eaNotifications = \App\Models\ClientNotification::query()
                ->where('user_id', $eaUser->id)
                ->latest()
                ->limit(8)
                ->get();
            $eaNotificationsUnread = \App\Models\ClientNotification::query()
                ->where('user_id', $eaUser->id)
                ->where('is_read', false)
                ->count();
        }
    } catch (\Throwable $e) {
        $eaNotifications = collect();
        $eaNotificationsUnread = 0;
    }

    $eaProfileHref = \Illuminate\Support\Facades\Route::has('admin.profile.edit') ? route('admin.profile.edit') : null;
    $eaLogoutHref = \Illuminate\Support\Facades\Route::has('logout.get') ? route('logout.get') : null;
    $eaDashboardHref = route('admin.dashboard');

    // Classes body héritées : elles pilotent les styles de contenu (cartes, tableaux, formulaires) des pages existantes.
    $eaIsWorkspaceRoute = request()->routeIs('admin.reservations.workspace') || request()->routeIs('admin.vente.catalogue');
    $eaIsVoyageStudio = request()->routeIs(
        'admin.circuits.voyages.create',
        'admin.circuits.voyages.edit',
        'admin.circuits.voyages.create-v2',
        'admin.circuits.voyages.edit-v2'
    );
    $eaBodyClass = trim(implode(' ', array_filter([
        'ea-body aj-admin-v2-body aj-admin admin-v6 aj-admin-v6',
        $eaIsWorkspaceRoute ? 'admin-v6-compact aj-admin-compact' : '',
        $eaIsVoyageStudio ? 'voyage-layout-page' : '',
        trim($__env->yieldContent('body_class')),
    ])));
    $eaHideFooter = trim($__env->yieldContent('hidePageFooter')) === '1' || $eaIsWorkspaceRoute;
    $eaCounts = array_merge(
        $eaUnreadMessages > 0 ? ['admin.messagerie.index' => $eaUnreadMessages] : [],
        is_array($eaCounts ?? null) ? $eaCounts : []
    );
@endphp
<!DOCTYPE html>
<html lang="fr" class="ea-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace Admin') — {{ $eaBrandName }}</title>

    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    @stack('css')
    {{-- Socle Bootstrap + Qovex : styles des contenus (cartes, tableaux, formulaires, badges). --}}
    <link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/css/app.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-branding.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-premium.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-v2.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-v6.css') }}" rel="stylesheet">
    @stack('styles')
    {{-- Coque Espace Admin v2 — chargée en dernier pour primer sur les règles de mise en page héritées. --}}
    <link href="{{ URL::asset('css/espace-admin-v2.css') }}?v=2" rel="stylesheet">
</head>
<body class="{{ $eaBodyClass }}">

    <header class="ea-header" data-ea-header>
        <a href="{{ $eaDashboardHref }}" class="ea-brand" aria-label="{{ $eaBrandName }}">
            <img src="{{ $eaBrandLogo }}" alt="{{ $eaBrandName }}">
        </a>

        @include('admin.partials.nav-espace-v2', ['eaCounts' => $eaCounts])

        <div class="ea-header-actions">
            <label class="ea-search">
                <span class="ea-search-icon" aria-hidden="true"></span>
                <input type="search" placeholder="Rechercher…" aria-label="Rechercher" autocomplete="off">
            </label>

            @if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index'))
                <a href="{{ route('admin.messagerie.index') }}" class="ea-icon-btn" title="Messagerie" aria-label="Messagerie">
                    <i class="bx bx-envelope"></i>
                    @if($eaUnreadMessages > 0)
                        <span class="ea-badge">{{ min($eaUnreadMessages, 99) }}</span>
                    @endif
                </a>
            @endif

            <div class="dropdown">
                <button type="button" class="ea-icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Notifications" aria-label="Notifications">
                    <i class="bx bx-bell"></i>
                    @if($eaNotificationsUnread > 0)
                        <span class="ea-badge">{{ min($eaNotificationsUnread, 99) }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end ea-notif-menu">
                    <div class="ea-notif-head">
                        <div>
                            <strong>Notifications</strong>
                            <span>{{ $eaNotificationsUnread }} non lue(s)</span>
                        </div>
                        @if($eaNotificationsUnread > 0 && \Illuminate\Support\Facades\Route::has('admin.notifications.read-all'))
                            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="ea-btn-sm">Tout marquer lu</button>
                            </form>
                        @endif
                    </div>
                    <div class="ea-notif-list">
                        @forelse($eaNotifications as $notification)
                            <div class="ea-notif-item {{ $notification->is_read ? '' : 'is-unread' }}">
                                <div>
                                    <div class="ea-notif-title">{{ $notification->title }}</div>
                                    <div class="ea-notif-text">{{ \Illuminate\Support\Str::limit($notification->message, 130) }}</div>
                                    <div class="ea-notif-time">{{ $notification->created_at?->diffForHumans() }}</div>
                                </div>
                                <div>
                                    @if(! $notification->is_read && \Illuminate\Support\Facades\Route::has('admin.notifications.read'))
                                        <form method="POST" action="{{ route('admin.notifications.read', $notification) }}">
                                            @csrf
                                            <button type="submit" class="ea-btn-sm is-primary">{{ $notification->link ? 'Ouvrir' : 'Lu' }}</button>
                                        </form>
                                    @elseif($notification->link)
                                        <a href="{{ $notification->link }}" class="ea-btn-sm">Ouvrir</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="ea-notif-empty">
                                <strong>Aucune notification</strong>
                                <span>Les alertes de votre compte apparaîtront ici.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="ea-user">
                <span class="ea-avatar" aria-hidden="true">
                    <span>{{ $eaInitials }}</span>
                    @if($eaAvatarUrl)
                        <img src="{{ $eaAvatarUrl }}" alt="" onerror="this.remove();">
                    @endif
                </span>
                <div class="ea-user-meta">
                    <span class="ea-user-name">{{ $eaUserName }}</span>
                    <span class="ea-user-links">
                        @if($eaProfileHref)
                            <a href="{{ $eaProfileHref }}" class="ea-user-link">Mon profil</a>
                        @endif
                        @if($eaProfileHref && $eaLogoutHref)
                            <span class="ea-user-sep" aria-hidden="true">·</span>
                        @endif
                        @if($eaLogoutHref)
                            <a href="{{ $eaLogoutHref }}" class="ea-user-link">Déconnexion</a>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </header>

    <div class="ea-backdrop" data-ea-backdrop></div>

    <main class="ea-content admin-v6-content aj-admin-v2-content">
        @yield('content')
    </main>

    @unless($eaHideFooter)
        <footer class="ea-footer">
            <div>© {{ now()->year }} {{ $eaBrandName }} — Tous droits réservés.</div>
            <div>Espace Admin v2</div>
        </footer>
    @endunless

    @include('support.reclamations._floating_button')

    @hasSection('core_scripts')
        @yield('core_scripts')
    @else
        <script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>
        <script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @endif
    <script src="{{ URL::asset('js/espace-admin-v2.js') }}?v=2"></script>
    @stack('scripts')
    @stack('body-end')
</body>
</html>
