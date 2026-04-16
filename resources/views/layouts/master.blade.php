<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | AJINSAFRO - Admin & Dashboard Template</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesdesign" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">

    @include('layouts.head-css')
</head>

<body class="internal-v2-topbar-hidden" data-layout="detached" data-topbar="colored">
    <!-- Begin page -->
    <div class="container-fluid">
        <div id="layout-wrapper">
            @include('layouts.partials.internal-v2-topbar')
            @include('layouts.sidebar')
            <button type="button" class="btn btn-primary d-lg-none internal-v2-menu-toggle" id="vertical-menu-btn" aria-label="Ouvrir le menu">
                <i class="fa fa-fw fa-bars"></i>
            </button>
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
</body>

</html>
