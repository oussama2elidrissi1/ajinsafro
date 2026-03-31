@php
    $useAgentPortal = \App\Services\View\AgentPortalLayout::shouldUse(auth()->user());
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

    @stack('styles')
</head>
<body class="partner-v2 text-gray-800 antialiased font-sans">
    @include('partner_v2.partials.header', ['portalLogoutUsesPartner' => false])

    <main class="flex-grow w-full relative">
        <div class="w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 mt-4 sm:mt-8 mb-16 fade-in">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                @include('agent_v2.partials.sidebar')
                <div class="flex-1 min-w-0 agent-portal-main">
                    @yield('content')
                </div>
            </div>
        </div>
    </main>

    @include('partner_v2.partials.footer')

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
    @stack('styles')
</head>

<body data-layout="detached" data-topbar="colored">
    <!-- Begin page -->
    <div class="container-fluid">
        <div id="layout-wrapper">
            @include('layouts.partials.topbar-ajinsafro')
            @include('layouts.partials.sidebar-ajinsafro')
            <div class="main-content">
                <div class="page-content">
                    @yield('content')
                </div>
                @include('layouts.footer')
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
