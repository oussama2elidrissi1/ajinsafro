<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | AJINSAFRO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    @include('layouts.head-css')
</head>

<body data-layout="detached" data-topbar="colored">
    <div class="container-fluid">
        <div id="layout-wrapper">
            @include('layouts.partials.topbar-agent')
            @include('layouts.partials.sidebar-agent')
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
