@extends('layouts.front')

@section('title', 'Search – AjiNsafro.ma')

@section('content')
    <x-front.navbar />

    <main class="min-h-screen">
        <x-front.hero-search
            :activeTab="request()->get('type', 'Hotel')"
            :location="request()->get('location')"
            :checkIn="request()->get('check_in')"
            :checkOut="request()->get('check_out')"
            :guests="request()->get('guests', '1 guest, 1 room')"
        />

        <section class="bg-white py-12 md:py-16">
            <div class="container mx-auto px-4">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-6">Search results</h2>
                <p class="text-gray-600">
                    @if(request()->hasAny(['location', 'check_in', 'check_out', 'guests']))
                        Results for: <strong>{{ request()->get('location', 'Any location') }}</strong>
                        @if(request()->get('check_in')) · Check-in {{ request()->get('check_in') }} @endif
                        @if(request()->get('check_out')) · Check-out {{ request()->get('check_out') }} @endif
                        @if(request()->get('guests')) · {{ request()->get('guests') }} @endif
                    @else
                        Enter search criteria above to see results.
                    @endif
                </p>
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    {{-- Placeholder for future result cards --}}
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="container mx-auto px-4 text-center text-sm">
            &copy; {{ date('Y') }} AjiNsafro.ma. All rights reserved.
        </div>
    </footer>
@endsection
