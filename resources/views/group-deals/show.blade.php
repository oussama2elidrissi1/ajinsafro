@extends('layouts.front')

@section('title', $groupDeal->title . ' · Group Deal Ajinsafro')

@section('content')
<x-front.navbar />

<main class="bg-slate-50 min-h-screen">
    <section class="relative overflow-hidden bg-[#0f2f52] text-white">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at top right, #f28c28 0, transparent 32%), radial-gradient(circle at bottom left, #2db783 0, transparent 30%);"></div>
        <div class="relative max-w-7xl mx-auto px-4 py-12 md:py-16">
            <div class="grid gap-8 lg:grid-cols-[1.25fr,0.95fr] items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold">{{ $groupDeal->destination ?: 'Destination à confirmer' }}</span>
                        <span class="rounded-full {{ $stats['is_guaranteed'] ? 'bg-emerald-500' : 'bg-[#f28c28]' }} px-4 py-2 text-sm font-semibold">
                            {{ $stats['is_guaranteed'] ? 'Voyage garanti' : 'Voyage bientôt garanti' }}
                        </span>
                    </div>
                    <h1 class="mt-5 text-4xl md:text-5xl font-bold leading-tight">{{ $groupDeal->title }}</h1>
                    <p class="mt-4 max-w-2xl text-lg text-white/85">{{ $groupDeal->description }}</p>
                    <div class="mt-6 flex flex-wrap gap-5 text-sm text-white/85">
                        <span>Départ: {{ optional($groupDeal->start_date)->format('d/m/Y') ?: 'N/A' }}</span>
                        <span>Retour: {{ optional($groupDeal->end_date)->format('d/m/Y') ?: 'N/A' }}</span>
                        <span>Inscription jusqu'au {{ optional($groupDeal->registration_deadline)->format('d/m/Y') ?: 'N/A' }}</span>
                    </div>
                </div>

                <div class="rounded-[32px] bg-white p-6 text-slate-900 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-medium text-slate-500">Prix actuel</div>
                            <div class="mt-2 text-4xl font-bold text-[#f28c28]">{{ $stats['current_price'] ? number_format($stats['current_price'], 0, ',', ' ') . ' DH' : 'N/A' }}</div>
                            <div class="mt-2 text-sm text-slate-500">par personne</div>
                        </div>
                        <div class="rounded-3xl bg-[#123b69]/5 px-4 py-3 text-right">
                            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Progression</div>
                            <div class="mt-2 text-2xl font-bold text-[#123b69]">{{ $groupDeal->current_participants }}/{{ $groupDeal->max_participants }}</div>
                            <div class="text-sm text-slate-500">participants</div>
                        </div>
                    </div>
                    <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full {{ $stats['is_guaranteed'] ? 'bg-emerald-500' : 'bg-[#f28c28]' }}" style="width: {{ $stats['progress_percent'] }}%"></div>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p>{{ $groupDeal->current_participants }} personne(s) sont déjà inscrites.</p>
                        <p>Minimum requis pour garantir: {{ $groupDeal->min_participants }}.</p>
                        <p>Places restantes: {{ $stats['remaining_places'] }}.</p>
                        <p>
                            @if($stats['remaining_to_guarantee'] > 0)
                                Il reste {{ $stats['remaining_to_guarantee'] }} personne(s) pour garantir ce voyage.
                            @else
                                Le voyage est garanti.
                            @endif
                        </p>
                    </div>

                    <form method="POST" action="{{ route('front.group-deals.participate', $groupDeal->slug) }}" class="mt-6 space-y-3">
                        @csrf
                        <div class="grid gap-3 md:grid-cols-2">
                            <input type="text" name="full_name" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Nom complet" value="{{ old('full_name', $client?->full_name ?: '') }}" required>
                            <input type="email" name="email" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Email" value="{{ old('email', $client?->email ?: '') }}" required>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <input type="text" name="phone" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Téléphone" value="{{ old('phone', $client?->phone ?: '') }}">
                            <input type="number" min="1" name="participants_count" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" value="{{ old('participants_count', 1) }}">
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button class="flex-1 rounded-2xl bg-[#f28c28] px-5 py-4 text-sm font-semibold text-white shadow hover:bg-[#df7a18]">Je participe</button>
                            @if($groupDeal->share_enabled)
                                <a href="https://wa.me/?text={{ urlencode('Rejoins-moi sur ce group deal Ajinsafro: ' . $shareUrl) }}" target="_blank" rel="noopener" class="flex-1 rounded-2xl border border-[#123b69]/15 px-5 py-4 text-center text-sm font-semibold text-[#123b69]">Partager avec mes amis</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 py-10">
        <div class="grid gap-8 lg:grid-cols-[1.15fr,0.85fr]">
            <div class="space-y-8">
                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                    @if($groupDeal->image_url)
                        <img src="{{ $groupDeal->image_url }}" alt="{{ $groupDeal->title }}" class="h-[360px] w-full object-cover">
                    @endif
                    <div class="p-6">
                        <h2 class="text-2xl font-semibold text-[#123b69]">Programme du voyage</h2>
                        <div class="mt-4 whitespace-pre-line leading-7 text-slate-700">{{ $groupDeal->program ?: 'Programme détaillé à venir.' }}</div>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold text-[#123b69]">Services inclus</h3>
                        <ul class="mt-4 space-y-3 text-slate-700">
                            @forelse($groupDeal->services_included ?? [] as $line)
                                <li class="flex gap-3"><span class="mt-2 h-2 w-2 rounded-full bg-emerald-500"></span><span>{{ $line }}</span></li>
                            @empty
                                <li class="text-slate-500">À compléter.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold text-[#123b69]">Non inclus</h3>
                        <ul class="mt-4 space-y-3 text-slate-700">
                            @forelse($groupDeal->services_excluded ?? [] as $line)
                                <li class="flex gap-3"><span class="mt-2 h-2 w-2 rounded-full bg-[#f28c28]"></span><span>{{ $line }}</span></li>
                            @empty
                                <li class="text-slate-500">À compléter.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-[#123b69]">Paliers de prix</h2>
                    <div class="mt-5 space-y-4">
                        @foreach($groupDeal->pricingTiers as $tier)
                            <div class="rounded-3xl border px-4 py-4 {{ optional($stats['active_tier'])->id === $tier->id ? 'border-[#f28c28] bg-[#fff5ea]' : 'border-slate-200 bg-slate-50' }}">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $tier->min_participants }} à {{ $tier->max_people ?: '∞' }} personnes</div>
                                        <div class="text-sm text-slate-500">{{ $tier->label ?: 'Palier de groupe' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-[#123b69]">{{ number_format((float) $tier->price_per_person, 0, ',', ' ') }} DH</div>
                                        @if(optional($stats['active_tier'])->id === $tier->id)
                                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[#f28c28]">Palier actif</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($stats['best_tier'])
                        <p class="mt-5 rounded-2xl bg-[#123b69]/5 px-4 py-3 text-sm text-slate-700">
                            Meilleur prix possible: <strong>{{ number_format((float) $stats['best_tier']->price_per_person, 0, ',', ' ') }} DH</strong>
                            si le groupe atteint {{ $stats['best_tier']->min_participants }} personnes.
                        </p>
                    @endif
                </div>

                <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-[#123b69]">Progression du groupe</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-3xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Participants confirmés</div>
                            <div class="mt-2 text-3xl font-bold text-[#123b69]">{{ $stats['current_participants'] }}</div>
                        </div>
                        <div class="rounded-3xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Restants pour garantir</div>
                            <div class="mt-2 text-3xl font-bold text-[#f28c28]">{{ $stats['remaining_to_guarantee'] }}</div>
                        </div>
                    </div>
                    <div class="mt-5 rounded-3xl bg-emerald-50 px-4 py-4 text-sm text-emerald-700">
                        {{ $stats['is_guaranteed'] ? 'Bonne nouvelle, ce voyage est désormais garanti.' : 'Le voyage n’est pas encore garanti. Invitez votre groupe pour atteindre le seuil plus vite.' }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
