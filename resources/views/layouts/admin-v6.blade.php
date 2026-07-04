@php
    $adminUser = auth()->user();
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');

    $adminUserName = $adminUser?->name ?? 'Admin';
    $adminUserRole = $adminUser?->getRoleNames()->first() ?? 'Administrateur';
    $adminInitials = strtoupper(
        collect(preg_split('/\s+/', trim((string) $adminUserName)))
            ->filter()
            ->take(2)
            ->map(fn ($s) => mb_substr((string) $s, 0, 1))
            ->implode('')
    );
    if ($adminInitials === '') { $adminInitials = 'AD'; }
    $adminAvatarUrl = $adminUser?->avatar_url;

    $unreadCount = 0;
    $adminNotifications = collect();
    $adminNotificationsUnread = 0;
    try {
        if ($adminUser && \Illuminate\Support\Facades\Schema::hasTable('messages')) {
            $unreadCount = \App\Models\Message::query()
                ->where('recipient_id', $adminUser->id)
                ->where('folder_recipient', 'inbox')
                ->where('read', false)
                ->count();
        }
    } catch (Throwable $e) {
        $unreadCount = 0;
    }
    try {
        if ($adminUser && \Illuminate\Support\Facades\Schema::hasTable('notifications')) {
            $adminNotifications = \App\Models\ClientNotification::query()
                ->where('user_id', $adminUser->id)
                ->latest()
                ->limit(8)
                ->get();
            $adminNotificationsUnread = \App\Models\ClientNotification::query()
                ->where('user_id', $adminUser->id)
                ->where('is_read', false)
                ->count();
        }
    } catch (Throwable $e) {
        $adminNotifications = collect();
        $adminNotificationsUnread = 0;
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $brandName) - {{ $brandName }} Admin</title>

    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/css/app.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-branding.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-premium.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-sidebar-v2.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-v2.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-v6.css') }}" rel="stylesheet">
    @stack('styles')
</head>
@php
    $isWorkspaceRoute = request()->routeIs('admin.reservations.workspace') || request()->routeIs('admin.vente.catalogue');
@endphp
<body class="aj-admin-v2-body aj-admin admin-v6 aj-admin-v6{{ $isWorkspaceRoute ? ' admin-v6-compact aj-admin-compact' : '' }}">
    <div class="aj-admin-v2-layout admin-v6-shell" id="admin-v6-root">
        <aside class="aj-admin-v2-sidebar admin-v6-sidebar" id="aj-admin-v2-sidebar">
            <div class="admin-v6-sidebar-head">
                <button type="button" class="admin-v6-sidebar-toggle" id="adminV6SidebarToggle" aria-label="Reduire / ouvrir la sidebar">
                    <i class="bx bx-menu"></i>
                </button>
            </div>
            @include('admin.partials.sidebar-v2', ['sidebarContext' => 'admin-vue-globale'])
        </aside>

        <div class="aj-admin-v2-overlay admin-v6-overlay" id="aj-admin-v2-overlay"></div>

        <div class="aj-admin-v2-main admin-v6-page">
            @include('admin.partials.header-v6', [
                'adminUser'      => $adminUser,
                'adminUserName'  => $adminUserName,
                'adminUserRole'  => $adminUserRole,
                'adminInitials'  => $adminInitials,
                'adminAvatarUrl' => $adminAvatarUrl,
                'unreadCount'    => $unreadCount,
                'adminNotifications' => $adminNotifications,
                'adminNotificationsUnread' => $adminNotificationsUnread,
            ])

            <main class="admin-v6-content aj-admin-v2-content">
                @yield('content')
            </main>
        </div>
    </div>

    @include('support.reclamations._floating_button')
    <script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ URL::asset('js/admin-sidebar-v2.js') }}"></script>
    <script src="{{ URL::asset('js/admin-v6.js') }}"></script>
    @stack('scripts')
</body>
</html>
