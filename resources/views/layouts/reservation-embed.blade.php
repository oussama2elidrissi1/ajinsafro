<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Ajinsafro</title>
    <link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ URL::asset('build/css/app.min.css') }}" rel="stylesheet" type="text/css">
    @stack('styles')
</head>
<body class="bg-light">
    <div class="container-fluid py-3">
        @yield('content')
    </div>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    @stack('script')
</body>
</html>
