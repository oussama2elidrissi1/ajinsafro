@extends('partner_v2.layouts.app')
@section('title', 'Catalogue voyages')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/reservation-workspace.css') }}?v=partner-catalogue-v1">
@endpush

@section('content')
@php
    $rows = $workspaceRows ?? collect();
@endphp

<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Catalogue des voyages & départs</h1>
            <p class="text-sm text-gray-500 mt-1">Même présentation que le back-office : sélection rapide du départ puis réservation.</p>
        </div>
        <div class="flex items-center gap-2">
            <input id="partner-catalog-search" type="search" class="w-full sm:w-[320px] rounded-xl border border-gray-200 px-4 py-2 text-sm"
                   placeholder="Rechercher (nom, destination)…" autocomplete="off">
        </div>
    </div>
</div>

<div class="ws-catalog-grid ws-catalog-grid--compact grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6" id="partner-catalog-grid">
    @forelse($rows as $row)
        @php
            $departures = collect(data_get($row, 'modal_detail.departures', []))->values();
            $extraCount = max(0, (int) data_get($row, 'ws_future_count', 0) - $departures->count());
            $imageUrl = data_get($row, 'image_url');
        @endphp
        <article class="ws-offer-card ws-offer-card--compact bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden"
                 data-search="{{ Str::lower(trim(($row['name'] ?? '').' '.($row['voyage_destination'] ?? ''))) }}">
            <div class="ws-offer-card__body ws-offer-card__body--compact p-6 flex flex-col gap-3">
                <div class="flex items-start gap-4">
                    <div class="shrink-0">
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}" alt="" class="w-14 h-14 rounded-2xl object-cover border border-gray-100">
                        @else
                            <div class="w-14 h-14 rounded-2xl bg-[#e6f3fa] border border-[#e6f3fa] flex items-center justify-center text-[#0e3a5a] font-black">
                                <i class="fa-solid fa-suitcase-rolling"></i>
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-extrabold text-[#0e3a5a] text-base line-clamp-2">{{ $row['name'] ?? 'Voyage' }}</h3>
                        @if(!empty($row['voyage_destination']))
                            <p class="text-xs text-gray-500 mt-1">{{ $row['voyage_destination'] }}</p>
                        @endif
                        <p class="ws-offer-card__refs mt-2">{{ ($row['wp_post_id'] ?? 0) ? ('WP #'.(int)$row['wp_post_id']) : ('Laravel #'.(int)($row['voyage_id'] ?? 0)) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Prix public</div>
                        <div class="text-sm font-black text-[#0e3a5a] mt-1">{{ $row['price_label'] ?? '—' }}</div>
                    </div>
                    <div class="bg-[#e6f3fa]/60 border border-[#e6f3fa] rounded-xl p-3">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Commission</div>
                        <div class="text-sm font-black text-green-700 mt-1">{{ $row['commission_label'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="ws-offer-card__departures">
                    <div class="ws-offer-card__section-label">Prochains départs</div>
                    @if($departures->isEmpty())
                        <div class="text-sm text-gray-500">Aucun départ futur disponible.</div>
                    @else
                        <ul class="ws-offer-card__departure-list">
                            @foreach($departures as $dep)
                                @php
                                    $reserveUrl = data_get($dep, 'routes.reserve');
                                    $cap = data_get($dep, 'available_capacity', null);
                                    $unitPrice = (float) data_get($dep, 'unit_price', 0);
                                @endphp
                                <li class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-bold text-[#0e3a5a] text-sm truncate">{{ data_get($dep, 'label', 'Départ') }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            Places: <span class="font-bold text-gray-700">{{ $cap !== null ? (int) $cap : '—' }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-[11px] text-gray-500">Prix / pers</div>
                                        <div class="font-black text-[#0e3a5a]">{{ $unitPrice > 0 ? number_format($unitPrice, 0, ',', ' ') . ' DH' : '—' }}</div>
                                        @if($reserveUrl)
                                            <a href="{{ $reserveUrl }}" class="inline-flex mt-2 items-center justify-center bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-3 py-1.5 rounded-xl text-[11px] font-black transition-colors">
                                                Réserver
                                            </a>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if($extraCount > 0)
                            <div class="text-xs text-gray-500 mt-3">+ {{ $extraCount }} autre(s) départ(s)…</div>
                        @endif
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="col-span-full">
            <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-6 text-gray-600">
                Aucun voyage disponible pour le moment.
            </div>
        </div>
    @endforelse
</div>

<div class="mt-6">
    {{ $voyages->links() }}
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('partner-catalog-search');
    var grid = document.getElementById('partner-catalog-grid');
    if (!input || !grid) return;
    function norm(v){ return String(v||'').toLowerCase().trim(); }
    input.addEventListener('input', function () {
        var q = norm(input.value);
        grid.querySelectorAll('[data-search]').forEach(function (card) {
            var blob = norm(card.getAttribute('data-search'));
            card.style.display = (q === '' || blob.indexOf(q) !== -1) ? '' : 'none';
        });
    });
})();
</script>
@endpush

