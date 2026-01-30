@extends('layouts.front')

@section('title', e($voyage->name) . ' – AjiNsafro.ma')

@section('content')
    <x-front.navbar />

    <main class="min-h-screen bg-gray-50">
        @php
            $heroSrc = $voyage->featured_image_url;
            if (!$heroSrc) {
                $heroSrc = "data:image/svg+xml," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="480"><rect fill="#1e3a5f" width="1200" height="480"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="rgba(255,255,255,0.9)" font-family="sans-serif" font-size="20">Voyage</text></svg>');
            }
        @endphp

        {{-- 1. Hero / Cover --}}
        <section class="relative h-72 md:h-96 lg:h-[28rem] bg-gray-900 overflow-hidden">
            <img
                src="{{ $heroSrc }}"
                alt=""
                class="absolute inset-0 w-full h-full object-cover"
                onerror="this.onerror=null; this.style.background='linear-gradient(135deg,#1e3a5f 0%,#2d5a87 100%)'; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%221200%22 height=%22480%22%3E%3Crect fill=%22%231e3a5f%22 width=%221200%22 height=%22480%22/%3E%3C/svg%3E';"
            >
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="absolute inset-0 flex flex-col justify-end">
                <div class="container mx-auto px-4 pb-8 max-w-6xl">
                    <nav class="text-sm text-white/80 mb-4" aria-label="Fil d'Ariane">
                        <a href="{{ route('front.home') }}" class="hover:text-white">Accueil</a>
                        <span class="mx-1">›</span>
                        <a href="{{ route('front.voyages.index') }}" class="hover:text-white">Voyages</a>
                        <span class="mx-1">›</span>
                        <span class="text-white">{{ e($voyage->name) }}</span>
                    </nav>
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white drop-shadow-lg">{{ e($voyage->name) }}</h1>
                    @if($voyage->destination)
                        <p class="mt-2 text-lg text-white/90">{{ e($voyage->destination) }}</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- 2. Image Gallery (hidden if no images) --}}
        @if($voyage->images->isNotEmpty())
            <section class="py-8 md:py-10 bg-white border-b border-gray-200">
                <div class="container mx-auto px-4 max-w-6xl">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Photos</h2>
                        <a href="#gallery-full" id="view-all-photos" class="text-sm font-medium text-brand hover:underline">
                            Voir toutes les photos
                        </a>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        @foreach($voyage->images->take(5) as $image)
                            <a
                                href="{{ $image->url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 shadow-sm hover:shadow-md transition-shadow"
                            >
                                <img src="{{ $image->url }}" alt="" class="w-full h-full object-cover" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- 3. Main two-column layout --}}
        <div class="container mx-auto px-4 py-10 md:py-14 max-w-6xl">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">
                {{-- LEFT COLUMN (main content) --}}
                <div class="lg:col-span-2 space-y-10">
                    {{-- A. Trip Summary --}}
                    <section>
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            @if($voyage->duration_text)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-700">{{ e($voyage->duration_text) }}</span>
                            @endif
                            @if($voyage->price_from !== null)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-brand/10 text-brand">
                                    À partir de {{ number_format($voyage->price_from, 0, ',', ' ') }} {{ $voyage->currency_symbol }}
                                </span>
                                @if($voyage->old_price && $voyage->old_price > $voyage->price_from)
                                    <span class="text-sm text-gray-500 line-through">{{ number_format($voyage->old_price, 0, ',', ' ') }} {{ $voyage->currency_symbol }}</span>
                                    <span class="text-sm font-medium text-green-600">-{{ $voyage->discount_percent }}%</span>
                                @endif
                            @endif
                        </div>
                        @if($voyage->accroche)
                            <p class="text-gray-700 leading-relaxed">{{ e($voyage->accroche) }}</p>
                        @endif
                    </section>

                    {{-- B. About This Trip (hidden if empty) --}}
                    @if(!empty($voyage->description))
                        <section>
                            <h2 class="text-xl font-bold text-gray-900 mb-4">À propos de ce voyage</h2>
                            <div class="prose prose-gray max-w-none text-gray-700 leading-relaxed">
                                {!! $voyage->description !!}
                            </div>
                        </section>
                    @endif

                    {{-- C. Highlights (only if data exists) --}}
                    @if(!empty($voyage->highlights))
                        <section>
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Points forts</h2>
                            <ul class="space-y-2 text-gray-700">
                                @if(is_array($voyage->highlights))
                                    @foreach($voyage->highlights as $item)
                                        <li class="flex items-start gap-2"><span class="text-brand mt-0.5">✓</span> {{ e($item) }}</li>
                                    @endforeach
                                @else
                                    <li class="flex items-start gap-2"><span class="text-brand mt-0.5">✓</span> {{ e($voyage->highlights) }}</li>
                                @endif
                            </ul>
                        </section>
                    @endif

                    {{-- D. Included / Excluded (show only side that has data) --}}
                    @php
                        $hasIncluded = !empty($voyage->included_html);
                        $hasExcluded = !empty($voyage->excluded_html);
                    @endphp
                    @if($hasIncluded || $hasExcluded)
                        <section>
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Inclus / Non inclus</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @if($hasIncluded)
                                    <div class="rounded-lg border border-gray-200 bg-green-50/30 p-4">
                                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">✔ Inclus</h3>
                                        <div class="prose prose-sm max-w-none text-gray-700">{!! $voyage->included_html !!}</div>
                                    </div>
                                @endif
                                @if($hasExcluded)
                                    <div class="rounded-lg border border-gray-200 bg-red-50/30 p-4">
                                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">✖ Non inclus</h3>
                                        <div class="prose prose-sm max-w-none text-gray-700">{!! $voyage->excluded_html !!}</div>
                                    </div>
                                @endif
                            </div>
                        </section>
                    @endif

                    {{-- E. Itinerary / Program --}}
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Programme détaillé</h2>
                        @if($voyage->programDays->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($voyage->programDays as $index => $day)
                                    <x-front.itinerary-day :day="$day" :isFirst="$index === 0" />
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-600 rounded-lg bg-gray-100 px-4 py-3">L’itinéraire détaillé sera bientôt disponible.</p>
                        @endif
                    </section>

                    {{-- F. Notes / Conditions (hidden if empty) --}}
                    @if(!empty($voyage->notes_html))
                        <section class="rounded-xl bg-amber-50/50 border border-amber-200/50 p-5">
                            <h2 class="text-lg font-bold text-gray-900 mb-3">Notes et conditions</h2>
                            <div class="prose prose-sm max-w-none text-gray-700">{!! $voyage->notes_html !!}</div>
                        </section>
                    @endif
                </div>

                {{-- RIGHT COLUMN (sticky sidebar) --}}
                <aside class="lg:col-span-1">
                    <x-front.price-box :voyage="$voyage" :nextDeparture="$nextDeparture ?? null" />

                    {{-- Extra Info (only if any field exists) --}}
                    @if($voyage->min_people || !empty($voyage->language) || !empty($voyage->trip_type))
                        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <h3 class="font-semibold text-gray-900 mb-3">Informations pratiques</h3>
                            <dl class="space-y-2 text-sm text-gray-700">
                                @if($voyage->min_people)
                                    <div><dt class="font-medium text-gray-500">Taille du groupe</dt><dd>{{ $voyage->min_people }} pers. min.</dd></div>
                                @endif
                                @if(!empty($voyage->language))
                                    <div><dt class="font-medium text-gray-500">Langue</dt><dd>{{ e($voyage->language) }}</dd></div>
                                @endif
                                @if(!empty($voyage->trip_type))
                                    <div><dt class="font-medium text-gray-500">Type de voyage</dt><dd>{{ e($voyage->trip_type) }}</dd></div>
                                @endif
                            </dl>
                        </div>
                    @endif
                </aside>
            </div>
        </div>

        {{-- Full gallery anchor (for "View all photos") --}}
        @if($voyage->images->isNotEmpty())
            <section id="gallery-full" class="scroll-mt-28 py-10 bg-white border-t border-gray-200">
                <div class="container mx-auto px-4 max-w-6xl">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Toutes les photos</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($voyage->images as $image)
                            <a href="{{ $image->url }}" target="_blank" rel="noopener noreferrer" class="block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 shadow-sm hover:shadow-md transition-shadow">
                                <img src="{{ $image->url }}" alt="" class="w-full h-full object-cover" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- 4. Optional sections (structure only, hidden if no data) --}}
        @if(!empty($voyage->faq_json) || !empty($voyage->reviews_enabled))
            {{-- FAQ: only if faq_json exists --}}
            @if(!empty($voyage->faq_json))
                <section class="py-10 bg-gray-50 border-t border-gray-200">
                    <div class="container mx-auto px-4 max-w-6xl">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">FAQ</h2>
                        <div class="space-y-3">
                            @foreach((array) $voyage->faq_json as $faq)
                                @if(!empty($faq['q']) || !empty($faq['question']))
                                    <details class="rounded-lg border border-gray-200 bg-white overflow-hidden">
                                        <summary class="px-4 py-3 cursor-pointer list-none font-medium text-gray-900">{{ e($faq['q'] ?? $faq['question'] ?? '') }}</summary>
                                        <div class="px-4 pb-3 text-gray-600 text-sm">{{ e($faq['a'] ?? $faq['answer'] ?? '') }}</div>
                                    </details>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- Reviews: placeholder structure only --}}
            @if(!empty($voyage->reviews_enabled))
                <section class="py-10 bg-white border-t border-gray-200">
                    <div class="container mx-auto px-4 max-w-6xl">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Avis</h2>
                        <p class="text-gray-500 text-sm">Les avis seront affichés ici lorsqu’ils seront disponibles.</p>
                    </div>
                </section>
            @endif
        @endif

        @if(!empty($voyage->map_embed) || !empty($voyage->map_url))
            <section class="py-10 bg-gray-50 border-t border-gray-200">
                <div class="container mx-auto px-4 max-w-6xl">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Carte</h2>
                    @if(!empty($voyage->map_embed))
                        <div class="aspect-video rounded-xl overflow-hidden bg-gray-200">{!! $voyage->map_embed !!}</div>
                    @elseif(!empty($voyage->map_url))
                        <a href="{{ e($voyage->map_url) }}" target="_blank" rel="noopener noreferrer" class="text-brand hover:underline">Voir sur la carte</a>
                    @endif
                </div>
            </section>
        @endif

        {{-- Similar trips: would need a controller variable; structure only, hidden for now unless we pass $similarVoyages --}}
        @if(!empty($similarVoyages) && $similarVoyages->isNotEmpty())
            <section class="py-10 bg-white border-t border-gray-200">
                <div class="container mx-auto px-4 max-w-6xl">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Voyages similaires</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($similarVoyages as $v)
                            <a href="{{ route('front.voyages.show', $v->slug) }}" class="block rounded-xl overflow-hidden shadow hover:shadow-md transition-shadow">
                                @if($v->featured_image_url)
                                    <img src="{{ $v->featured_image_url }}" alt="" class="aspect-[4/3] w-full object-cover">
                                @else
                                    <div class="aspect-[4/3] w-full bg-gray-200"></div>
                                @endif
                                <div class="p-4"><h3 class="font-semibold text-gray-900">{{ e($v->name) }}</h3></div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8 mt-12">
        <div class="container mx-auto px-4 text-center text-sm">
            <a href="{{ route('front.voyages.index') }}" class="text-brand hover:underline">← Retour aux voyages</a>
            <span class="mx-2">·</span>
            &copy; {{ date('Y') }} AjiNsafro.ma
        </div>
    </footer>
@endsection
