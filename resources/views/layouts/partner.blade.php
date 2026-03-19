<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title>@yield('title') | Espace partenaire</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    @vite(['resources/css/partner-v2.css', 'resources/js/partner-v2.js'])
    @include('layouts.head-css')
</head>
<body data-layout="detached" data-topbar="colored" class="partner-v2 text-gray-800 antialiased font-sans">
    <div class="container-fluid">
        <div id="layout-wrapper">
            @include('partner_v2.partials.header')
            @include('layouts.partials.sidebar-partner')
            <div class="main-content">
                <div class="page-content">
                    @yield('content')
                </div>
                @include('layouts.footer')
            </div>
        </div>
    </div>
    @include('layouts.vendor-scripts')
</body>
</html>
