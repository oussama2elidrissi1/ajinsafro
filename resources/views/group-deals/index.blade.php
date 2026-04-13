@php
    $listingUrl = url('/group-deals');
    $f = $filters ?? [];
    $heroImagePath = \App\Models\Setting::getValue('hero_image');
    $heroImageUrl = $heroImagePath ? \App\Models\Setting::storageUrl($heroImagePath) : asset('front/images/hero.jpg');
    $heroOverlay = max(0.45, (float) (\App\Models\Setting::getValue('hero_overlay_opacity', '0.5')));
@endphp
@extends('layouts.front')

@section('title', 'Group Deals – AjiNsafro.ma')

@section('content')
    <x-front.navbar />

    <header class="relative overflow-hidden border-b border-gray-200/80">
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ $heroImageUrl }}');" aria-hidden="true"></div>
        <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(0,0,0,0.78) 0%, rgba(0,0,0,{{ number_format(min(0.85, $heroOverlay + 0.08), 2, '.', '') }}) 45%, rgba(0,0,0,0.82) 100%);" aria-hidden="true"></div>
        <div class="relative z-10 container mx-auto px-4 max-w-7xl pt-8 pb-10 md:pt-11 md:pb-12">
            <nav class="text-sm text-white/80 mb-5" aria-label="Fil d'Ariane">
                <a href="{{ url('/') }}" class="hover:text-white transition-colors">Accueil</a>
                <span class="mx-2 text-white/50" aria-hidden="true">/</span>
                <span class="text-white font-medium">Group Deals</span>
            </nav>
            <h1 class="text-3xl md:text-4xl lg:text-[2.65rem] font-bold text-white tracking-tight leading-tight max-w-4xl">
                Group Deals
            </h1>
            <p class="mt-4 text-base md:text-lg text-white/90 leading-relaxed max-w-2xl">
                Offres de groupe sélectionnées depuis le back-office, avec logique dédiée pour devis et réservation collective.
            </p>
        </div>
    </header>

    <main class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-16">
            <div class="flex flex-col lg:flex-row lg:items-start gap-8 lg:gap-10">
                <aside class="w-full lg:w-80 xl:w-[20rem] shrink-0 lg:sticky lg:top-24 lg:z-10">
                    <form method="get" action="{{ $listingUrl }}" class="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-sm space-y-5">
                        <h2 class="font-semibold text-gray-900 text-sm uppercase tracking-wide border-b border-gray-100 pb-3">Filtres Group Deals</h2>

                        <div>
                            <label for="filter-q" class="block text-sm font-medium text-gray-700 mb-1.5">Recherche</label>
                            <input type="text" name="q" id="filter-q" value="{{ $f['q'] ?? '' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand focus:border-brand" placeholder="Nom, destination…" autocomplete="off">
                        </div>

                        <div>
                            <label for="filter-destination" class="block text-sm font-medium text-gray-700 mb-1.5">Destination</label>
                            <select name="destination" id="filter-destination" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-brand focus:border-brand">
                                <option value="">Toutes les destinations</option>
                                @foreach ($destinations as $destination)
                                    <option value="{{ $destination }}" @selected(($f['destination'] ?? '') === $destination)>{{ $destination }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="filter-group-size" class="block text-sm font-medium text-gray-700 mb-1.5">Taille du groupe</label>
                            <input type="number" min="2" max="100" id="filter-group-size" name="group_size" value="{{ $f['group_size'] ?? 6 }}" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand focus:border-brand">
                        </div>

                        <div class="flex flex-col gap-2.5 pt-1">
                            <button type="submit" class="w-full rounded-lg bg-brand text-white font-medium py-2.5 text-sm hover:opacity-95 transition">Appliquer les filtres</button>
                            <a href="{{ $listingUrl }}" class="w-full text-center rounded-lg border border-gray-300 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">Réinitialiser</a>
                        </div>
                    </form>
                </aside>

                <div class="flex-1 min-w-0">
                    <div class="rounded-2xl border border-gray-200/90 bg-white p-4 md:p-6 lg:p-8 shadow-sm">
                        <div class="flex items-center justify-between gap-4 mb-6 pb-5 border-b border-gray-100">
                            <h2 class="text-lg md:text-xl font-semibold text-gray-900">Offres Group Deals disponibles</h2>
                            <p class="text-sm text-gray-600"><span class="font-semibold text-gray-900">{{ $deals->total() }}</span> résultat{{ $deals->total() > 1 ? 's' : '' }}</p>
                        </div>

                        @if($deals->isEmpty())
                            <div class="text-center py-14 text-gray-500">Aucune offre Group Deals ne correspond à ces critères.</div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                                @foreach($deals as $deal)
                                    <article class="rounded-xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all bg-white">
                                        <div class="p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-brand">{{ $deal->destination ?: 'Destination à définir' }}</p>
                                            <h3 class="font-semibold text-gray-900 mt-1">{{ $deal->name }}</h3>
                                            <p class="text-sm text-gray-500 mt-1">{{ $deal->duration_text ?: 'Durée à confirmer' }}</p>
                                            <p class="mt-3 font-semibold text-brand">
                                                {{ number_format((float) ($deal->price_from ?? 0), 0, ',', ' ') }} {{ $deal->currency_symbol ?? ($deal->currency ?: 'MAD') }}
                                            </p>
                                            @if(!empty($deal->slug))
                                                <a href="{{ url('/voyages/'.$deal->slug) }}" class="inline-flex items-center mt-3 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Voir le circuit</a>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            @if($deals->hasPages())
                                <div class="mt-8 flex justify-center border-t border-gray-100 pt-6">
                                    {{ $deals->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm">
            &copy; {{ date('Y') }} AjiNsafro.ma. All rights reserved.
        </div>
    </footer>
@endsection
