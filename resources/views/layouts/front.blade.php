<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AjiNsafro.ma – Let the journey begin')</title>
    <meta name="description" content="Get the best prices on 2,000,000+ properties, worldwide.">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    {{-- Tailwind CSS CDN – front only, no admin assets --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand': { DEFAULT: '#2563eb', dark: '#1d4ed8' },
                        'topbar': '#1f2937',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .hero-overlay { background: linear-gradient(to bottom, rgba(0,0,0,0.4), rgba(0,0,0,0.5)); }
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased text-gray-800 bg-white">
    @yield('content')
    @stack('scripts')
</body>
</html>
