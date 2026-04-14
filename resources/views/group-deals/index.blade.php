@php
    $listingUrl = url('/group-deals');
    $f          = $filters ?? [];

    $heroImagePath = \App\Models\Setting::getValue('hero_image');
    $heroImageUrl  = $heroImagePath
        ? \App\Models\Setting::storageUrl($heroImagePath)
        : asset('front/images/hero.jpg');
    $heroOverlay = max(0.45, (float) (\App\Models\Setting::getValue('hero_overlay_opacity', '0.5')));

    $publicBase = rtrim((string) config('app.public_url', config('app.url', '')), '/');

    // Format price helper
    $priceLabel = function ($price, $currency) {
        $p = (float) ($price ?? 0);
        if ($p <= 0) return null;
        $sym = $currency ?: 'MAD';
        return number_format($p, 0, ',', ' ') . ' ' . $sym;
    };

    // Featured image URL helper
    $imageUrl = function ($path) {
        if (!$path) return null;
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($path)
            : null;
    };

    $hasFilters = !empty(trim($f['q'] ?? '')) || !empty(trim($f['destination'] ?? ''));
    $pageTitle    = $hasFilters ? 'Offres correspondantes' : 'Group Deals';
    $pageSubtitle = 'Voyagez en groupe et profitez de tarifs exclusifs. Plus vous êtes nombreux, plus vous économisez.';
@endphp
@extends('layouts.front')

@section('title', 'Group Deals – Voyages organisés en groupe · AjiNsafro')

@section('content')
<x-front.navbar />

{{-- Bandeau page identique à la page Voyages --}}
<header class="relative overflow-hidden border-b border-gray-200/80">
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
         style="background-image: url('{{ $heroImageUrl }}');" aria-hidden="true"></div>
    <div class="absolute inset-0"
         style="background: linear-gradient(180deg, rgba(0,0,0,0.78) 0%, rgba(0,0,0,{{ number_format(min(0.85, $heroOverlay + 0.08), 2, '.', '') }}) 45%, rgba(0,0,0,0.82) 100%);"
         aria-hidden="true"></div>
    <div class="relative z-10 container mx-auto px-4 max-w-7xl pt-8 pb-10 md:pt-11 md:pb-12">
        <nav class="text-sm text-white/80 mb-5" aria-label="Fil d'Ariane">
            <a href="{{ \Illuminate\Support\Facades\Route::has('front.home') ? route('front.home') : url('/') }}"
               class="hover:text-white transition-colors">Accueil</a>
            <span class="mx-2 text-white/50" aria-hidden="true">/</span>
            <span class="text-white font-medium">Group Deals</span>
        </nav>
        <h1 class="text-3xl md:text-4xl lg:text-[2.65rem] font-bold text-white tracking-tight text-balance leading-tight max-w-4xl">
            {{ $pageTitle }}
        </h1>
        <p class="mt-4 text-base md:text-lg text-white/90 leading-relaxed max-w-2xl">
            {{ $pageSubtitle }}
        </p>
    </div>
</header>

<main class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-16">
        <div class="flex flex-col lg:flex-row lg:items-start gap-8 lg:gap-10">

            {{-- ── Filtres sidebar ──────────────────────────────────────────────── --}}
            <aside class="w-full lg:w-80 xl:w-[20rem] shrink-0 lg:sticky lg:top-24 lg:z-10">
                <form method="get" action="{{ $listingUrl }}"
                      class="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-sm space-y-5">
                    <h2 class="font-semibold text-gray-900 text-sm uppercase tracking-wide border-b border-gray-100 pb-3">
                        Filtres
                    </h2>

                    <div>
                        <label for="filter-q" class="block text-sm font-medium text-gray-700 mb-1.5">Recherche</label>
                        <input type="text" name="q" id="filter-q" value="{{ $f['q'] ?? '' }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand focus:border-brand"
                               placeholder="Nom, destination…" autocomplete="off">
                    </div>

                    <div>
                        <label for="filter-destination" class="block text-sm font-medium text-gray-700 mb-1.5">Destination</label>
                        <select name="destination" id="filter-destination"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-brand focus:border-brand">
                            <option value="">Toutes les destinations</option>
                            @foreach ($destinations as $dest)
                                <option value="{{ $dest }}" @selected(($f['destination'] ?? '') === $dest)>{{ $dest }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="filter-group-size" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Taille du groupe
                        </label>
                        <input type="number" name="group_size" id="filter-group-size"
                               min="2" max="200" value="{{ $f['group_size'] ?? 6 }}"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand focus:border-brand"
                               placeholder="ex: 10">
                    </div>

                    <div class="flex flex-col gap-2.5 pt-1">
                        <button type="submit"
                                class="w-full rounded-lg bg-brand text-white font-medium py-2.5 text-sm hover:opacity-95 transition">
                            Appliquer les filtres
                        </button>
                        <a href="{{ $listingUrl }}"
                           class="w-full text-center rounded-lg border border-gray-300 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                            Réinitialiser
                        </a>
                    </div>
                </form>
            </aside>

            {{-- ── Catalogue ────────────────────────────────────────────────────── --}}
            <div class="flex-1 min-w-0">
                <div class="rounded-2xl border border-gray-200/90 bg-white p-4 md:p-6 lg:p-8 shadow-sm">

                    {{-- Résultats header --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-5 border-b border-gray-100">
                        <p class="text-sm text-gray-600">
                            @if (($deals->total() ?? 0) > 0)
                                <span class="font-semibold text-gray-900">{{ $deals->total() }}</span>
                                résultat{{ $deals->total() > 1 ? 's' : '' }}
                            @else
                                Aucun résultat pour ces critères
                            @endif
                        </p>
                        {{-- Chips filtres actifs --}}
                        @if($hasFilters)
                            <div class="flex flex-wrap items-center gap-2">
                                @if(!empty(trim($f['q'] ?? '')))
                                    <span class="inline-flex items-center gap-1 text-xs font-medium bg-brand/10 text-brand rounded-full px-3 py-1">
                                        "{{ $f['q'] }}"
                                        <a href="{{ $listingUrl . '?' . http_build_query(array_merge($f, ['q' => ''])) }}"
                                           class="ml-0.5 opacity-70 hover:opacity-100">×</a>
                                    </span>
                                @endif
                                @if(!empty(trim($f['destination'] ?? '')))
                                    <span class="inline-flex items-center gap-1 text-xs font-medium bg-brand/10 text-brand rounded-full px-3 py-1">
                                        {{ $f['destination'] }}
                                        <a href="{{ $listingUrl . '?' . http_build_query(array_merge($f, ['destination' => ''])) }}"
                                           class="ml-0.5 opacity-70 hover:opacity-100">×</a>
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Grid cards --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        @forelse($deals as $deal)
                            @php
                                $imgSrc        = $imageUrl($deal->featured_image ?? null);
                                $formattedPrice = $priceLabel($deal->price_from, $deal->currency);
                                $hasSlug       = !empty(trim($deal->slug ?? ''));
                                $circuitUrl    = $hasSlug ? ($publicBase !== '' ? $publicBase . '/voyages/' . $deal->slug : url('/voyages/' . $deal->slug)) : null;
                                $dest          = trim($deal->destination ?? '');
                                $duration      = trim($deal->duration_text ?? '');
                                $metrics       = $deal->groupDealMetrics ?? [];
                                $hasCapacity   = ($metrics['total_capacity'] ?? 0) > 0;
                                $progress      = (int) ($metrics['progress_percent'] ?? 0);
                                $isGuaranteed  = (bool) ($metrics['is_guaranteed'] ?? false);
                                $isFull        = (bool) ($metrics['is_full'] ?? false);
                                $isAlmostFull  = (bool) ($metrics['is_almost_full'] ?? false);
                                $confirmed     = (int) ($metrics['confirmed_count'] ?? 0);
                                $remaining     = (int) ($metrics['remaining_places'] ?? 0);
                                $statusLabel   = $metrics['status_label'] ?? '';

                                // Placeholder SVG if no image
                                if (!$imgSrc) {
                                    $imgSrc = "data:image/svg+xml," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect fill="#2563eb" width="400" height="300"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="white" font-family="sans-serif" font-size="18">Group Deal</text></svg>');
                                }
                            @endphp

                            {{-- Card --}}
                            <article class="group block rounded-xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all bg-white flex flex-col">
                                {{-- Image --}}
                                <div class="aspect-[4/3] relative overflow-hidden bg-gray-200">
                                    <img
                                        src="{{ $imgSrc }}"
                                        alt="{{ e($deal->name) }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                        onerror="this.onerror=null; this.style.background='linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%)'; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%232563eb%22 width=%22400%22 height=%22300%22/%3E%3C/svg%3E';"
                                    >
                                    {{-- Badge "Group Deal" --}}
                                    <span class="absolute top-2 left-2 inline-flex items-center gap-1 bg-brand text-white text-xs font-semibold px-2 py-1 rounded-full shadow">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                                        Group Deal
                                    </span>
                                    {{-- Status badge --}}
                                    @if($hasCapacity)
                                        @if($isFull)
                                            <span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">Complet</span>
                                        @elseif($isAlmostFull)
                                            <span class="absolute top-2 right-2 bg-amber-500 text-white text-xs font-semibold px-2 py-1 rounded">Presque complet</span>
                                        @elseif($isGuaranteed)
                                            <span class="absolute top-2 right-2 bg-emerald-500 text-white text-xs font-semibold px-2 py-1 rounded">Garanti</span>
                                        @endif
                                    @endif
                                </div>

                                {{-- Body --}}
                                <div class="p-4 flex flex-col flex-1">
                                    {{-- Destination --}}
                                    @if($dest)
                                        <p class="text-xs font-semibold text-brand/80 uppercase tracking-wide mb-1">{{ e($dest) }}</p>
                                    @endif

                                    {{-- Title --}}
                                    <h2 class="font-semibold text-gray-900 group-hover:text-brand transition line-clamp-2 mb-1">
                                        {{ e($deal->name) }}
                                    </h2>

                                    {{-- Duration + group size --}}
                                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mb-2">
                                        @if($duration)
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                {{ e($duration) }}
                                            </span>
                                        @endif
                                        @php
                                            $minP = (int)($deal->min_people ?? 0);
                                            $maxP = (int)($deal->max_people ?? 0);
                                        @endphp
                                        @if($minP > 0)
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                                                {{ $minP }}{{ $maxP > 0 ? '–' . $maxP : '+' }} pers.
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Participation progress (si données dispo) --}}
                                    @if($hasCapacity && !$isFull)
                                        <div class="mb-3 p-2.5 rounded-lg bg-gray-50 border border-gray-100">
                                            <div class="flex justify-between items-center text-xs text-gray-600 mb-1.5">
                                                <span class="font-medium">{{ $confirmed }} participant{{ $confirmed > 1 ? 's' : '' }}</span>
                                                @if($remaining > 0)
                                                    <span>{{ $remaining }} place{{ $remaining > 1 ? 's' : '' }} restante{{ $remaining > 1 ? 's' : '' }}</span>
                                                @endif
                                            </div>
                                            <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-500 {{ $isGuaranteed ? 'bg-emerald-500' : ($isAlmostFull ? 'bg-amber-500' : 'bg-brand') }}"
                                                     style="width: {{ min(100, $progress) }}%"></div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Prix --}}
                                    <p class="mt-auto pt-2 font-semibold text-brand text-base">
                                        @if($formattedPrice)
                                            À partir de {{ $formattedPrice }}
                                        @else
                                            Sur demande
                                        @endif
                                    </p>

                                    {{-- CTA --}}
                                    @if($circuitUrl)
                                        <a href="{{ $circuitUrl }}"
                                           class="mt-3 w-full text-center rounded-lg bg-brand text-white font-medium py-2.5 text-sm hover:opacity-95 transition block">
                                            Voir le voyage
                                        </a>
                                    @else
                                        <span class="mt-3 w-full text-center rounded-lg bg-gray-100 text-gray-400 font-medium py-2.5 text-sm block cursor-default">
                                            Lien non disponible
                                        </span>
                                    @endif
                                </div>
                            </article>

                        @empty
                            <div class="col-span-full text-center py-14 text-gray-500">
                                @if($hasFilters)
                                    Aucun Group Deal ne correspond à ces critères.
                                    <div class="mt-4">
                                        <a href="{{ $listingUrl }}" class="text-brand hover:underline font-medium text-sm">
                                            Voir toutes les offres
                                        </a>
                                    </div>
                                @else
                                    Aucun voyage Group Deal n'est disponible pour le moment.
                                @endif
                            </div>
                        @endforelse
                    </div>

                    {{-- Pagination --}}
                    @if($deals->hasPages())
                        <div class="mt-8 flex justify-center border-t border-gray-100 pt-6">
                            {{ $deals->links() }}
                        </div>
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
