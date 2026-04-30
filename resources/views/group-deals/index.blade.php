@php
    $listingUrl = route('front.group-deals.index');
    $f = $filters ?? [];
    $heroImagePath = \App\Models\Setting::getValue('hero_image');
    $heroImageUrl = $heroImagePath ? \App\Models\Setting::storageUrl($heroImagePath) : asset('front/images/hero.jpg');
    $heroOverlay = max(0.45, (float) (\App\Models\Setting::getValue('hero_overlay_opacity', '0.5')));
@endphp
@extends('layouts.front')

@section('title', 'Group Deals · Ajinsafro')

@section('content')
<x-front.navbar />

<header class="relative overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image:url('{{ $heroImageUrl }}')"></div>
    <div class="absolute inset-0" style="background:linear-gradient(180deg, rgba(8,27,51,0.75) 0%, rgba(8,27,51,{{ number_format(min(0.9, $heroOverlay + 0.18), 2, '.', '') }}) 100%);"></div>
    <div class="relative max-w-7xl mx-auto px-4 pt-16 pb-20 md:pt-24 md:pb-24 text-white">
        <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm backdrop-blur">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                Voyages de groupe à prix évolutif
            </div>
            <h1 class="mt-5 text-4xl md:text-5xl font-bold leading-tight">Offres de voyage de groupe Ajinsafro</h1>
            <p class="mt-4 text-lg text-white/85">Plus le groupe grandit, plus le prix par personne peut baisser. Suivez la progression, le seuil de garantie et les meilleurs paliers en direct.</p>
        </div>
    </div>
</header>

<main class="bg-slate-50 min-h-screen">
    <section class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid gap-8 lg:grid-cols-[320px,minmax(0,1fr)]">
            <aside class="lg:sticky lg:top-24 self-start rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-slate-900 font-semibold text-lg">Filtrer les offres</h2>
                <form method="get" action="{{ $listingUrl }}" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Recherche</label>
                        <input type="text" name="q" value="{{ $f['q'] ?? '' }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Titre, destination, ambiance">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Destination</label>
                        <select name="destination" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm bg-white">
                            <option value="">Toutes les destinations</option>
                            @foreach($destinations as $destination)
                                <option value="{{ $destination }}" @selected(($f['destination'] ?? '') === $destination)>{{ $destination }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Statut</label>
                        <select name="status" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm bg-white">
                            <option value="">Tous</option>
                            <option value="published" @selected(($f['status'] ?? '') === 'published')>Publié</option>
                            <option value="guaranteed" @selected(($f['status'] ?? '') === 'guaranteed')>Garanti</option>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button class="flex-1 rounded-2xl bg-[#f28c28] px-5 py-3 text-sm font-semibold text-white shadow hover:bg-[#df7a18]">Rechercher</button>
                        <a href="{{ $listingUrl }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium text-slate-700">Réinitialiser</a>
                    </div>
                </form>
            </aside>

            <div>
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold text-[#123b69]">Catalogue Group Deals</h2>
                        <p class="text-sm text-slate-600">{{ $deals->total() }} offre(s) trouvée(s)</p>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($deals as $deal)
                        @php
                            $activeTier = $deal->activePricingTier();
                            $bestTier = $deal->bestPricingTier();
                            $nextTier = $deal->nextPricingTier();
                        @endphp
                        <article class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-[#f28c28]/40 hover:shadow-lg">
                            <div class="relative h-60 bg-slate-200">
                                @if($deal->image_url)
                                    <img src="{{ $deal->image_url }}" alt="{{ $deal->title }}" class="h-full w-full object-cover">
                                @endif
                                <div class="absolute inset-x-0 top-0 flex items-center justify-between p-4">
                                    <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-[#123b69] shadow">Group Deal</span>
                                    <span class="rounded-full {{ $deal->is_guaranteed ? 'bg-emerald-500' : 'bg-[#123b69]' }} px-3 py-1 text-xs font-semibold text-white shadow">
                                        {{ $deal->is_guaranteed ? 'Garanti' : 'Bientôt garanti' }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#123b69]/70">{{ $deal->destination ?: 'Destination à définir' }}</p>
                                        <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ $deal->title }}</h3>
                                    </div>
                                    <div class="rounded-2xl bg-[#123b69]/5 px-3 py-2 text-right">
                                        <div class="text-xs text-slate-500">Prix actuel</div>
                                        <div class="text-lg font-bold text-[#f28c28]">{{ $deal->current_price ? number_format((float) $deal->current_price, 0, ',', ' ') . ' DH' : 'N/A' }}</div>
                                    </div>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit(strip_tags((string) $deal->description), 130) }}</p>

                                <div class="mt-4 rounded-2xl bg-slate-50 p-4">
                                    <div class="flex items-center justify-between text-sm text-slate-700">
                                        <span>{{ $deal->current_participants }}/{{ $deal->max_participants }} participants</span>
                                        <span>Minimum: {{ $deal->min_participants }}</span>
                                    </div>
                                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full {{ $deal->is_guaranteed ? 'bg-emerald-500' : 'bg-[#f28c28]' }}" style="width: {{ $deal->progress_percent }}%"></div>
                                    </div>
                                    <p class="mt-3 text-sm text-slate-600">
                                        @if($deal->remaining_to_guarantee > 0)
                                            Il reste {{ $deal->remaining_to_guarantee }} personne(s) pour garantir ce voyage.
                                        @else
                                            Le voyage est garanti.
                                        @endif
                                    </p>
                                </div>

                                @if($nextTier)
                                    <p class="mt-4 text-sm text-slate-600">Si le groupe atteint {{ $nextTier->min_participants }} personnes, le prix passera à <span class="font-semibold text-[#123b69]">{{ number_format((float) $nextTier->price_per_person, 0, ',', ' ') }} DH</span>.</p>
                                @elseif($bestTier)
                                    <p class="mt-4 text-sm text-slate-600">Meilleur prix possible: <span class="font-semibold text-[#123b69]">{{ number_format((float) $bestTier->price_per_person, 0, ',', ' ') }} DH</span>.</p>
                                @endif

                                <div class="mt-5 flex gap-3">
                                    <a href="{{ route('front.group-deals.show', $deal->slug) }}" class="flex-1 rounded-2xl bg-[#f28c28] px-4 py-3 text-center text-sm font-semibold text-white shadow hover:bg-[#df7a18]">Voir l'offre</a>
                                    <span class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-[#123b69]">{{ $activeTier?->label ?: 'Palier actif' }}</span>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="md:col-span-2 xl:col-span-3 rounded-[28px] border border-dashed border-slate-300 bg-white p-10 text-center text-slate-600">
                            Aucune offre Group Deal ne correspond à ces critères.
                        </div>
                    @endforelse
                </div>

                @if($deals->hasPages())
                    <div class="mt-8">{{ $deals->links() }}</div>
                @endif
            </div>
        </div>
    </section>
</main>
@endsection
