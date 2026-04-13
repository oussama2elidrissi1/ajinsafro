@extends('layouts.front')

@section('title', 'GROUP DEALS – AjiNsafro.ma')

@section('content')
    <x-front.navbar />

    <main class="min-h-screen bg-slate-50">
        <section class="bg-gradient-to-r from-sky-700 to-cyan-600 text-white">
            <div class="container mx-auto px-4 py-14 md:py-16">
                <p class="text-sm uppercase tracking-[0.18em] font-semibold text-white/80">Ajinsafro</p>
                <h1 class="mt-2 text-3xl md:text-4xl font-bold">GROUP DEALS</h1>
                <p class="mt-4 max-w-3xl text-white/90">
                    Offres dédiées aux voyages en groupe, prêtes à évoluer vers un moteur complet de devis, disponibilité et réservation.
                </p>
            </div>
        </section>

        <section class="container mx-auto px-4 -mt-8 md:-mt-10 relative z-10">
            <form method="GET" action="{{ route('front.group-deals.index') }}" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 md:p-6 grid grid-cols-1 md:grid-cols-4 gap-3">
                <label class="block">
                    <span class="text-sm text-slate-600">Recherche</span>
                    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Destination, circuit, mot-clé" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                </label>
                <label class="block">
                    <span class="text-sm text-slate-600">Destination</span>
                    <select name="destination" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                        <option value="">Toutes</option>
                        @foreach($destinations as $destination)
                            <option value="{{ $destination }}" @selected($filters['destination'] === $destination)>{{ $destination }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm text-slate-600">Taille du groupe</span>
                    <input type="number" min="2" max="100" name="group_size" value="{{ $filters['group_size'] }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-sky-500 focus:ring-sky-500">
                </label>
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full inline-flex justify-center items-center rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-semibold px-4 py-2.5">
                        Filtrer
                    </button>
                </div>
            </form>
        </section>

        <section class="container mx-auto px-4 py-10 md:py-12">
            <div class="flex items-center justify-between gap-3 mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-slate-900">Offres disponibles</h2>
                <p class="text-sm text-slate-500">{{ $deals->total() }} offre(s)</p>
            </div>

            @if($deals->isEmpty())
                <div class="bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-600">
                    Aucune offre de groupe ne correspond aux critères actuels.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6">
                    @foreach($deals as $deal)
                        <article class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">{{ $deal->destination ?: 'Destination à définir' }}</p>
                            <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ $deal->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $deal->duration_text ?: 'Durée à confirmer' }}</p>
                            <div class="mt-4 flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-slate-500">À partir de</p>
                                    <p class="text-xl font-bold text-slate-900">
                                        {{ number_format((float) ($deal->price_from ?? 0), 0, ',', ' ') }} {{ $deal->currency ?: 'MAD' }}
                                    </p>
                                </div>
                                @if(!empty($deal->slug))
                                    <a href="{{ route('front.voyages.show', $deal->slug) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                                        Voir le circuit
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $deals->links() }}
                </div>
            @endif
        </section>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="container mx-auto px-4 text-center text-sm">
            &copy; {{ date('Y') }} AjiNsafro.ma. All rights reserved.
        </div>
    </footer>
@endsection
