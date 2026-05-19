@php
    $dashboardV4BrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $dashboardV4BrandName) — {{ $dashboardV4BrandName }}</title>

    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('build/css/app.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-branding.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/admin-premium.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body class="aj-dashboard-v4-body">
    @yield('content')

    <script src="{{ URL::asset('build/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ URL::asset('js/admin-v2.js') }}"></script>
    @stack('scripts')
</body>
</html>
