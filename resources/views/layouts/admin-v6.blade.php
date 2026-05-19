@php
    $adminV2User     = auth()->user();
    $adminV2BrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');

    $adminV2UserName = $adminV2User?->name ?? 'Admin';
    $adminV2UserRole = $adminV2User?->getRoleNames()->first() ?? 'Administrateur';
    $adminV2Initials = strtoupper(
        collect(preg_split('/\s+/', trim($adminV2UserName)))
            ->filter()
            ->take(2)
            ->map(fn ($s) => mb_substr($s, 0, 1))
            ->implode('')
    );
    if ($adminV2Initials === '') { $adminV2Initials = 'AD'; }
    $adminV2AvatarUrl = $adminV2User?->avatar_url;

    $adminV2UnreadCount  = 0;
    $adminV2PendingCount = 0;
    if ($adminV2User && \Illuminate\Support\Facades\Schema::hasTable('messages')) {
        $adminV2UnreadCount = \App\Models\Message::query()
            ->where('recipient_id', $adminV2User->id)
            ->where('folder_recipient', 'inbox')
            ->where('read', false)
            ->count();
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('reservations')) {
        $adminV2PendingCount = \App\Models\Reservation::query()
            ->where('status', \App\Models\Reservation::STATUS_PENDING)
            ->count();
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $adminV2BrandName) — {{ $adminV2BrandName }} Admin</title>

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
    $isWorkspaceRoute = request()->routeIs('admin.reservations.workspace');
    $isCommercialWorkspaceOnly = $adminV2User
        && method_exists($adminV2User, 'hasRole')
        && $adminV2User->hasRole('commercial_reservations_only')
        && $isWorkspaceRoute;
@endphp
<body class="aj-admin-v2-body aj-admin aj-admin-v6 admin-v6{{ $isWorkspaceRoute ? ' aj-admin-compact' : '' }}{{ $isCommercialWorkspaceOnly ? ' commercial-reservations-only aj-admin-v6-no-chrome' : '' }}">
<div class="aj-admin-v2-layout admin-v6-shell" id="aj-admin-v2-root">
    @if($isCommercialWorkspaceOnly)
        <div class="aj-admin-v2-main admin-v6-page" style="margin-left:0 !important;width:100% !important;">
            <main class="aj-admin-v2-content admin-v6-content" style="padding:0 !important;">
                @yield('content')
            </main>
        </div>
    @else
        <aside class="aj-admin-v2-sidebar admin-v6-sidebar" id="aj-admin-v2-sidebar">
            @include('admin.partials.sidebar-v6', ['sidebarContext' => 'admin-v6'])
        </aside>

        <div class="aj-admin-v2-overlay" id="aj-admin-v2-overlay"></div>

        <div class="aj-admin-v2-main admin-v6-page">
            @include('admin.partials.header-v6', [
                'adminV2User'         => $adminV2User,
                'adminV2UserName'     => $adminV2UserName,
                'adminV2UserRole'     => $adminV2UserRole,
                'adminV2Initials'     => $adminV2Initials,
                'adminV2AvatarUrl'    => $adminV2AvatarUrl,
                'adminV2UnreadCount'  => $adminV2UnreadCount,
                'adminV2PendingCount' => $adminV2PendingCount,
            ])

            <main class="aj-admin-v2-content admin-v6-content">
                @yield('content')
            </main>

            @unless(View::hasSection('hide_admin_footer'))
                @include('admin.partials.footer-v2', [
                    'adminV2BrandName' => $adminV2BrandName,
                ])
            @endunless
        </div>
    @endif
</div>

<script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ URL::asset('js/admin-sidebar-v2.js') }}"></script>
<script src="{{ URL::asset('js/admin-v2.js') }}"></script>
<script src="{{ URL::asset('js/admin-v6.js') }}"></script>
@stack('scripts')
</body>
</html>
