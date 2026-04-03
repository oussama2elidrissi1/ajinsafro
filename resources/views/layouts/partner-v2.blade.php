<!DOCTYPE html>
<html lang="fr">
@php($hideInternalV2Topbar = \App\Services\View\InternalV2Topbar::shouldHide(auth()->user()))
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portail partenaire') | Ajinsafro</title>
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    @vite(['resources/css/partner-v2.css', 'resources/js/partner-v2.js'])
    @stack('css')
</head>
<body class="partner-v2 text-gray-800 antialiased font-sans{{ $hideInternalV2Topbar ? ' internal-v2-topbar-hidden' : '' }}">
    @if($hideInternalV2Topbar)
        @include('layouts.partials.internal-v2-topbar')
    @else
        @include('partner.v2.partials.topbar')
    @endif
    @unless($hideInternalV2Topbar)
        @include('partner.v2.partials.navbar')
    @endunless

    <main class="flex-grow w-full z-10 relative">
        <div class="w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 mt-4 sm:mt-8 mb-16 fade-in">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                @include('partner.v2.partials.sidebar')
                <div class="flex-1 min-w-0">
                    @yield('content')
                </div>
            </div>
        </div>
    </main>

    @include('partner.v2.partials.footer')

    @stack('script')
</body>
</html>
