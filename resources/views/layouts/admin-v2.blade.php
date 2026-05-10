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

    // Compute notification counts once — passed to header partial
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

    {{-- Bootstrap + Qovex base (provides content styles: tables, cards, forms, badges) --}}
    <link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/css/app.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-branding.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-premium.css') }}" rel="stylesheet">

    {{-- Admin V2 system — loaded last to override Qovex layout rules --}}
    <link href="{{ URL::asset('css/admin-sidebar-v2.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-v2.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body class="aj-admin-v2-body">

<div class="aj-admin-v2-layout" id="aj-admin-v2-root">

    {{-- ═══ SIDEBAR ═══ --}}
    <aside class="aj-admin-v2-sidebar" id="aj-admin-v2-sidebar">
        @include('admin.partials.sidebar-v2', ['sidebarContext' => 'admin-v2'])
    </aside>

    <div class="aj-admin-v2-overlay" id="aj-admin-v2-overlay"></div>

    {{-- ═══ MAIN ═══ --}}
    <div class="aj-admin-v2-main">

        @include('admin.partials.header-v2', [
            'adminV2User'         => $adminV2User,
            'adminV2UserName'     => $adminV2UserName,
            'adminV2UserRole'     => $adminV2UserRole,
            'adminV2Initials'     => $adminV2Initials,
            'adminV2AvatarUrl'    => $adminV2AvatarUrl,
            'adminV2UnreadCount'  => $adminV2UnreadCount,
            'adminV2PendingCount' => $adminV2PendingCount,
        ])

        <div class="aj-admin-v2-content">
            @yield('content')
        </div>

        @include('admin.partials.footer-v2', [
            'adminV2BrandName' => $adminV2BrandName,
        ])

    </div>

</div>

{{-- ═══ SCRIPTS ═══ --}}
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ URL::asset('js/admin-sidebar-v2.js') }}"></script>
<script src="{{ URL::asset('js/admin-v2.js') }}"></script>

@stack('scripts')

</body>
</html>
