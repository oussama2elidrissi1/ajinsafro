@php
    $listingUrl = route('front.voyages.index');
    $f = $filters ?? [];
@endphp
@extends('layouts.front')

@section('title', 'Voyages – AjiNsafro.ma')

@section('content')
    <x-front.navbar />

    <main class="min-h-screen bg-gray-50">
        <section class="bg-white py-8 md:py-10 border-b border-gray-100">
            <div class="container mx-auto px-4">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">
                    {{ ($hasFilters ?? false) ? 'Offres correspondantes' : 'Nos voyages' }}
                </h1>
                <p class="text-gray-600">Filtrez par thème, destination ou date de départ.</p>
            </div>
        </section>

        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                <aside class="w-full lg:w-72 shrink-0">
                    <form method="get" action="{{ $listingUrl }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-4">
                        <h2 class="font-semibold text-gray-900 text-sm uppercase tracking-wide">Filtres</h2>

                        <div>
                            <label for="filter-q" class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                            <input type="text" name="q" id="filter-q" value="{{ $f['q'] ?? '' }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand"
                                placeholder="Nom, destination…" autocomplete="off">
                        </div>

                        <div>
                            <label for="filter-theme" class="block text-sm font-medium text-gray-700 mb-1">Thème du voyage</label>
                            <select name="theme" id="filter-theme" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                <option value="">Tous les thèmes</option>
                                @foreach ($themeOptions ?? [] as $th)
                                    <option value="{{ $th->slug }}" @selected(($f['theme'] ?? '') === $th->slug)>{{ $th->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="filter-destination" class="block text-sm font-medium text-gray-700 mb-1">Destination</label>
                            <select name="destination" id="filter-destination" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                <option value="">Toutes les destinations</option>
                                @foreach ($destinationOptions ?? [] as $d)
                                    <option value="{{ $d }}" @selected(($f['destination'] ?? '') === $d)>{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="filter-depart" class="block text-sm font-medium text-gray-700 mb-1">Date de départ</label>
                            <input type="date" name="depart_date" id="filter-depart" value="{{ $f['depart_date'] ?? '' }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>

                        <input type="hidden" name="catalog_orderby" value="{{ $f['catalog_orderby'] ?? 'date' }}">

                        <div class="flex flex-col gap-2 pt-2">
                            <button type="submit" class="w-full rounded-lg bg-brand text-white font-medium py-2.5 text-sm hover:opacity-95">
                                Appliquer
                            </button>
                            <a href="{{ $listingUrl }}" class="w-full text-center rounded-lg border border-gray-300 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </aside>

                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <p class="text-sm text-gray-600">
                            @if (($voyages->total() ?? 0) > 0)
                                {{ $voyages->total() }} résultat{{ $voyages->total() > 1 ? 's' : '' }}
                            @else
                                Aucun résultat
                            @endif
                        </p>
                        <form method="get" action="{{ $listingUrl }}" class="flex items-center gap-2 text-sm">
                            @foreach (['q', 'theme', 'destination', 'depart_date'] as $keep)
                                @if (!empty($f[$keep] ?? ''))
                                    <input type="hidden" name="{{ $keep }}" value="{{ $f[$keep] }}">
                                @endif
                            @endforeach
                            <label for="toolbar-sort" class="text-gray-600 whitespace-nowrap">Trier par</label>
                            <select name="catalog_orderby" id="toolbar-sort" onchange="this.form.submit()"
                                class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                                <option value="date" @selected(($f['catalog_orderby'] ?? 'date') === 'date')>Plus récents</option>
                                <option value="title" @selected(($f['catalog_orderby'] ?? '') === 'title')>Titre (A–Z)</option>
                                <option value="title_desc" @selected(($f['catalog_orderby'] ?? '') === 'title_desc')>Titre (Z–A)</option>
                            </select>
                        </form>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
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
                                    @if($voyage->themes->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 mb-2">
                                            @foreach ($voyage->themes->take(3) as $t)
                                                <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-brand/10 text-brand">{{ $t->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
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
                                Aucun voyage ne correspond à ces critères.
                            </div>
                        @endforelse
                    </div>

                    @if($voyages->hasPages())
                        <div class="mt-8 flex justify-center">
                            {{ $voyages->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="container mx-auto px-4 text-center text-sm">
            &copy; {{ date('Y') }} AjiNsafro.ma. All rights reserved.
        </div>
    </footer>
@endsection
