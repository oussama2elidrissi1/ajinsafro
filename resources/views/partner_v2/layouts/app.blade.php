@php
    /**
     * Portail Agence Partenaire — coque unique du portail (maquette « Portail Agence Partenaire »).
     * L'ancienne barre latérale est remplacée par une navigation horizontale ; les feuilles
     * de style du contenu (Tailwind partner-v2, Bootstrap, Qovex) restent chargées à
     * l'identique pour ne pas casser les pages existantes du portail.
     *
     * Les anciens layouts (layouts.partner, layouts.partner-v2) délèguent ici : les quatre
     * piles historiques (css/styles, script/scripts) sont donc toutes rendues.
     */
    $ppCss = file_exists(public_path('css/portail-partenaire.css')) ? (string) filemtime(public_path('css/portail-partenaire.css')) : '1';
    $ppJs = file_exists(public_path('js/portail-partenaire.js')) ? (string) filemtime(public_path('js/portail-partenaire.js')) : '1';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portail partenaire') | Ajinsafro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">

    {{-- Tailwind du portail d'abord ; Bootstrap et Qovex ensuite pour les pages héritées.
         head-css rend aussi la pile `css`. --}}
    @vite(['resources/css/partner-v2.css', 'resources/js/partner-v2.js'])
    @include('layouts.head-css')
    <link href="{{ URL::asset('css/agent-portal-bootstrap-bridge.css') }}" rel="stylesheet" type="text/css" />

    @stack('styles')

    {{-- Coque Portail Partenaire — chargée en dernier pour primer sur les règles héritées. --}}
    <link href="{{ URL::asset('css/portail-partenaire.css') }}?v={{ $ppCss }}" rel="stylesheet" type="text/css" />
</head>
<body class="partner-v2 pp-body text-gray-800 antialiased font-sans">
<div class="pp-shell">

    @include('partner_v2.partials.shell-header')

    {{-- Deux niveaux conservés : certaines feuilles du portail ciblent `main > div`,
         le contenu ne doit donc pas être enfant direct de <main>. --}}
    <main class="pp-main">
        <div class="pp-main__inner">
            <div class="pp-content partner-portal-main">
                @yield('content')
            </div>
        </div>
    </main>

    @if(trim($__env->yieldContent('hidePageFooter')) !== '1')
        @include('partner_v2.partials.footer')
    @endif

</div>

    @include('support.reclamations._floating_button')
    {{-- vendor-scripts rend aussi la pile `script`. --}}
    @include('layouts.vendor-scripts')
    <script src="{{ URL::asset('js/portail-partenaire.js') }}?v={{ $ppJs }}"></script>
    @stack('scripts')
    @stack('body-end')
</body>
</html>
