@php
    /**
     * Espace Agent — coque du portail (design « Espace Agent »).
     * L'ancienne barre latérale est remplacée par une navigation horizontale ;
     * les feuilles de style du contenu (Tailwind partner-v2, Bootstrap, Qovex)
     * restent chargées à l'identique pour ne pas casser les pages du portail.
     */
    $voyageLayoutPage = request()->routeIs(
        'admin.circuits.voyages.create',
        'admin.circuits.voyages.edit',
        'admin.circuits.voyages.create-v2',
        'admin.circuits.voyages.edit-v2',
        'agent.voyages.*'
    );
    $eagCss = file_exists(public_path('css/espace-agent.css')) ? (string) filemtime(public_path('css/espace-agent.css')) : '1';
    $eagJs = file_exists(public_path('js/espace-agent.js')) ? (string) filemtime(public_path('js/espace-agent.js')) : '1';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Ajinsafro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">

    @stack('css')

    {{-- Tailwind du portail d'abord ; Bootstrap et Qovex ensuite pour les composants d'administration. --}}
    @vite(['resources/css/partner-v2.css', 'resources/js/partner-v2.js'])

    <link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/admin-branding.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/agent-portal-bootstrap-bridge.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/internal-v2-layout.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/admin-premium.css') }}" rel="stylesheet" type="text/css" />

    @stack('styles')
    <link href="{{ URL::asset('css/admin-compact.css') }}?v=workspace-fixed-v7" rel="stylesheet" type="text/css" />
    {{-- Coque Espace Agent — chargée en dernier pour primer sur les règles héritées. --}}
    <link href="{{ URL::asset('css/espace-agent.css') }}?v={{ $eagCss }}" rel="stylesheet" type="text/css" />
</head>
{{--
    La classe `internal-v2-topbar-hidden` n'est volontairement plus posée : elle compensait
    l'ancienne barre fixe (décalage du contenu, remise à zéro des marges de .agent-portal-main
    et de `main > div`). Cette coque n'affiche plus cette barre, et ces règles écrasaient
    la gouttière des pages du portail.
--}}
<body class="partner-v2 admin-premium-ui aj-admin aj-admin-compact ea-agent text-gray-800 antialiased font-sans{{ $voyageLayoutPage ? ' voyage-layout-page' : '' }}">
<div class="ea-agent-shell">

    {{-- Barre héritée masquée en CSS : elle fournit encore la fenêtre des notifications. --}}
    @include('layouts.partials.internal-v2-topbar')

    @include('agent_v2.partials.shell-header')

    {{-- Deux niveaux conservés : les pages du portail sont écrites pour ne pas être
         enfant direct de <main>, certaines feuilles ciblant `main > div`. --}}
    <main class="eag-main">
        <div class="eag-main__inner">
            <div class="eag-content agent-portal-main">
                @yield('content')
            </div>
        </div>
    </main>

    @if(trim($__env->yieldContent('hidePageFooter')) !== '1' && !request()->routeIs('admin.reservations.workspace'))
        @include('agent_v2.partials.footer')
    @endif

</div>

    @include('support.reclamations._floating_button')
    @include('layouts.vendor-scripts')
    <script src="{{ URL::asset('js/espace-agent.js') }}?v={{ $eagJs }}"></script>
    @stack('scripts')
    @stack('body-end')
</body>
</html>
