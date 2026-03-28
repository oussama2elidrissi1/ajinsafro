<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portail partenaire') | Ajinsafro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- FontAwesome (same as Ajinsafro public header/footer) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">

    {{-- Font icons (Qovex bundle). Includes common icon fonts. --}}
    <link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />

    {{-- Tailwind + Partner v2 JS (Flatpickr + Chart.js bundled) --}}
    @vite(['resources/css/partner-v2.css', 'resources/js/partner-v2.js'])

    @stack('styles')
</head>
<body class="partner-v2 text-gray-800 antialiased font-sans">
    @include('partner_v2.partials.header')

    <main class="flex-grow w-full z-10 relative">
        <div class="w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 mt-4 sm:mt-8 mb-16 fade-in">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                @include('partner_v2.partials.sidebar')
                <div class="flex-1 min-w-0">
                    @yield('content')
                </div>
            </div>
        </div>
    </main>

    @include('partner_v2.partials.footer')

    @stack('scripts')
</body>
</html>

