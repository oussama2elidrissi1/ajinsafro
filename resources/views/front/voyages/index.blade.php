@extends('layouts.front')

@section('title', 'Voyages – AjiNsafro.ma')

@section('content')
    <x-front.navbar />

    <main class="min-h-screen bg-gray-50">
        <section class="bg-white py-10 md:py-14">
            <div class="container mx-auto px-4">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Nos voyages</h1>
                <p class="text-gray-600 mb-8">Découvrez nos circuits et séjours.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @forelse($voyages as $voyage)
                        @php
                            $imgSrc = $voyage->featured_image_url;
                            if (!$imgSrc) {
                                $imgSrc = "data:image/svg+xml," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect fill="#667eea" width="400" height="300"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="white" font-family="sans-serif" font-size="18">Voyage</text></svg>');
                            }
                            $detailUrl = route('front.voyages.show', ['slug' => $voyage->slug]);
                        @endphp
                        <a href="{{ $detailUrl }}" class="group block rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-shadow bg-white">
                            <div class="aspect-[4/3] relative overflow-hidden bg-gray-200">
                                <img
                                    src="{{ $imgSrc }}"
                                    alt="{{ e($voyage->name) }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                    onerror="this.onerror=null; this.style.background='linear-gradient(135deg,#667eea 0%,#764ba2 100%)'; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23667eea%22 width=%22400%22 height=%22300%22/%3E%3C/svg%3E';"
                                >
                                @if($voyage->old_price && $voyage->old_price > $voyage->price_from && $voyage->discount_percent)
                                    <span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">
                                        -{{ $voyage->discount_percent }}%
                                    </span>
                                @endif
                            </div>
                            <div class="p-4">
                                <h2 class="font-semibold text-gray-900 group-hover:text-brand transition line-clamp-2">{{ e($voyage->name) }}</h2>
                                @if($voyage->destination)
                                    <p class="text-sm text-gray-500 mt-1">{{ e($voyage->destination) }}</p>
                                @endif
                                @if($voyage->duration_text)
                                    <p class="text-sm text-gray-500">{{ e($voyage->duration_text) }}</p>
                                @endif
                                <p class="mt-2 font-semibold text-brand">
                                    @if($voyage->price_from !== null)
                                        {{ number_format($voyage->price_from, 0, ',', ' ') }} {{ $voyage->currency_symbol }}
                                        @if($voyage->old_price && $voyage->old_price > $voyage->price_from)
                                            <span class="text-gray-400 line-through text-sm font-normal">{{ number_format($voyage->old_price, 0, ',', ' ') }} {{ $voyage->currency_symbol }}</span>
                                        @endif
                                    @else
                                        Sur demande
                                    @endif
                                </p>
                            </div>
                        </a>
                    @empty
                        <div class="col-span-full text-center py-12 text-gray-500">
                            Aucun voyage disponible pour le moment.
                        </div>
                    @endforelse
                </div>

                @if($voyages->hasPages())
                    <div class="mt-8 flex justify-center">
                        {{ $voyages->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="container mx-auto px-4 text-center text-sm">
            &copy; {{ date('Y') }} AjiNsafro.ma. All rights reserved.
        </div>
    </footer>
@endsection
