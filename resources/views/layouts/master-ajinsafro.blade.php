@php
    $useAgentPortal = \App\Services\View\AgentPortalLayout::shouldUse(auth()->user());
    $voyageLayoutPage = request()->routeIs('admin.circuits.voyages.create', 'admin.circuits.voyages.edit');
    $hideInternalV2Topbar = true;
@endphp

@if($useAgentPortal)
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Ajinsafro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">

    @stack('css')

    {{-- Tailwind shell (sidebar / header) first; Bootstrap + app after so admin widgets keep expected styling. --}}
    @vite(['resources/css/partner-v2.css', 'resources/js/partner-v2.js'])

    <link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/admin-branding.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/agent-portal-bootstrap-bridge.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/internal-v2-layout.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/admin-premium.css') }}" rel="stylesheet" type="text/css" />

    <link href="{{ URL::asset('css/admin-compact.css') }}?v=workspace-fixed-v7" rel="stylesheet" type="text/css" />
    @stack('styles')
</head>
<body class="partner-v2 admin-premium-ui aj-admin aj-admin-compact text-gray-800 antialiased font-sans{{ $voyageLayoutPage ? ' voyage-layout-page' : '' }}{{ $hideInternalV2Topbar ? ' internal-v2-topbar-hidden' : '' }}">
    @if($hideInternalV2Topbar)
        @include('layouts.partials.internal-v2-topbar')
    @else
        @include('partner_v2.partials.header', ['portalLogoutUsesPartner' => false])
    @endif

    <main class="flex-grow w-full relative">
        <div class="w-full px-0 mt-0 mb-16 fade-in">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                @include('agent_v2.partials.sidebar')
                <div class="flex-1 min-w-0 agent-portal-main">
                    @yield('content')
                </div>
            </div>
        </div>
    </main>

    @if(trim($__env->yieldContent('hidePageFooter')) !== '1' && !request()->routeIs('admin.reservations.workspace'))
        @include('partner_v2.partials.footer')
    @endif

    @include('layouts.vendor-scripts')
    @stack('scripts')
    @stack('body-end')
</body>
</html>
@else
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | AJINSAFRO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesdesign" name="author" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">

    @include('layouts.head-css')
    <link href="{{ URL::asset('css/admin-compact.css') }}?v=workspace-fixed-v7" rel="stylesheet" type="text/css" />
    @stack('styles')
</head>

<body class="admin-premium-ui aj-admin aj-admin-compact{{ $voyageLayoutPage ? ' voyage-layout-page' : '' }}{{ $hideInternalV2Topbar ? ' internal-v2-topbar-hidden' : '' }}" data-layout="detached" data-topbar="colored">
    <!-- Begin page -->
    <div class="container-fluid">
        <div id="layout-wrapper">
            <!-- DIAG_MARKER: MASTER_LAYOUT_TRADITIONAL_PATH file=resources/views/layouts/master-ajinsafro.blade.php -->
            @include('layouts.partials.admin-topbar')
            @include('layouts.partials.sidebar-ajinsafro')
            <div class="main-content">
                <div class="page-content">
                    @yield('content')
                </div>
                @if(trim($__env->yieldContent('hidePageFooter')) !== '1' && !request()->routeIs('admin.reservations.workspace'))
                    @include('layouts.footer')
                @endif
            </div>

        </div>
    </div>

    @include('layouts.right-sidebar')
    @include('layouts.vendor-scripts')
    @stack('scripts')
    @stack('body-end')
</body>

</html>
@endif
