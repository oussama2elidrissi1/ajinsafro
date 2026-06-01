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
            $firstDeparture = $departures->first();
            $firstDateIso = data_get($firstDeparture, 'date_iso');
            $isNear = false;
            try {
                if ($firstDateIso) {
                    $depDay = \Carbon\Carbon::parse($firstDateIso)->startOfDay();
                    $today = \Carbon\Carbon::today();
                    $isNear = $depDay->gte($today) && $depDay->lte($today->copy()->addDays(30));
                }
            } catch (\Throwable $e) {}
            $firstCap = data_get($firstDeparture, 'available_capacity');
            $firstPrice = (float) data_get($firstDeparture, 'unit_price', 0);
        @endphp
        <article class="ws-offer-card ws-offer-card--compact bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden"
                 data-search="{{ Str::lower(trim(($row['name'] ?? '').' '.($row['voyage_destination'] ?? ''))) }}">
            {{-- Image header (comme admin vente/catalogue) --}}
            <div class="relative">
                @if($imageUrl)
                    <img src="{{ $imageUrl }}" alt="" class="w-full h-[150px] object-cover">
                @else
                    <div class="w-full h-[150px] bg-gradient-to-r from-[#e6f3fa] to-white"></div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/35 via-black/0 to-black/0"></div>
                <div class="absolute top-3 left-3 flex items-center gap-2">
                    @if($isNear)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-black bg-[#e6f3fa] text-[#0e3a5a] border border-[#cfe9f7]">DÉPART PROCHE</span>
                    @endif
                </div>
            </div>

            <div class="ws-offer-card__body ws-offer-card__body--compact p-5 flex flex-col gap-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-black bg-white text-[#0e3a5a] border border-gray-100 shadow-sm">CIRCUIT</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-black bg-white text-gray-600 border border-gray-100 shadow-sm">
                                #{{ (int) ($row['wp_post_id'] ?? 0) ?: (int) ($row['voyage_id'] ?? 0) }}
                            </span>
                        </div>
                        <h3 class="mt-2 font-extrabold text-[#0e3a5a] text-[15px] leading-snug line-clamp-2">{{ $row['name'] ?? 'Voyage' }}</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            WP #{{ (int) ($row['wp_post_id'] ?? 0) }} · Laravel #{{ (int) ($row['voyage_id'] ?? 0) }}
                        </p>
                        @if(!empty($row['voyage_destination']))
                            <p class="text-sm text-[#0e3a5a] font-bold mt-2 flex items-center gap-2">
                                <i class="fa-solid fa-location-dot text-[#0083c4]"></i>
                                <span class="truncate">{{ $row['voyage_destination'] }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Prix & capacité (style admin) --}}
                <div class="grid grid-cols-1 gap-2">
                    <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                        <div class="flex items-center gap-2 text-xs font-extrabold text-[#0e3a5a] uppercase tracking-wider">
                            <i class="fa-solid fa-coins text-[#0083c4]"></i>
                            <span>Prix à partir de</span>
                        </div>
                        <div class="font-black text-[#0e3a5a]">{{ $row['price_label'] ?? '—' }}</div>
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                        <div class="flex items-center gap-2 text-xs font-extrabold text-[#0e3a5a] uppercase tracking-wider">
                            <i class="fa-solid fa-users text-[#0083c4]"></i>
                            <span>Capacité</span>
                        </div>
                        <div class="font-black text-[#0e3a5a]">
                            {{ $firstCap !== null ? ((int) $firstCap . ' restantes') : 'À configurer' }}
                        </div>
                    </div>
                </div>

                {{-- Stats (simplifiées) --}}
                <div class="rounded-xl border border-gray-100 bg-white px-4 py-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-black text-[#0e3a5a]">0 vendues</span>
                        <span class="font-black text-green-700">{{ $firstCap !== null ? (int) $firstCap : 0 }} restantes</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-2 bg-[#e6f3fa]" style="width: 0%"></div>
                    </div>
                    <div class="mt-1 text-right text-xs text-gray-400 font-bold">0%</div>
                </div>

                {{-- Prochain départ (comme admin) --}}
                <div class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wider text-[#0e3a5a]">Prochain départ</div>
                    @if($firstDeparture)
                        <div class="mt-2 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-extrabold text-[#0e3a5a] text-sm truncate">{{ data_get($firstDeparture, 'label') }}</div>
                                <div class="mt-2 flex items-center gap-2 text-xs text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-green-100 text-green-800 font-black">
                                        {{ $firstCap !== null ? ((int) $firstCap . ' places') : '—' }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs text-gray-500">Prix / pers</div>
                                <div class="font-black text-[#0e3a5a]">{{ $firstPrice > 0 ? number_format($firstPrice, 0, ',', ' ') . ' MAD' : '—' }}</div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <button type="button"
                                    class="text-sm font-extrabold text-[#0083c4] hover:text-[#0e3a5a] js-open-departures"
                                    data-tour-id="{{ (int) ($row['voyage_id'] ?? 0) }}"
                                    data-tour-name="{{ e($row['name'] ?? 'Voyage') }}">
                                Voir tous les départs ({{ (int) data_get($row, 'ws_future_count', 0) }})
                            </button>
                            @if(data_get($firstDeparture, 'routes.reserve'))
                                <a href="{{ data_get($firstDeparture, 'routes.reserve') }}"
                                   class="inline-flex items-center justify-center bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-4 py-2 rounded-xl text-xs font-black transition-colors">
                                    Réserver
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="mt-2 text-sm text-gray-500">Aucun départ futur disponible.</div>
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

{{-- Modal liste départs (identique concept admin : "voir tous les départs") --}}
<div id="partner-departures-modal" class="fixed inset-0 z-[9999] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative w-full h-full flex items-end sm:items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-[#0e3a5a] text-lg">Tous les départs</h3>
                    <p class="text-xs text-gray-500 mt-0.5" id="partner-departures-subtitle">—</p>
                </div>
                <button type="button" class="text-gray-500 hover:text-gray-900 text-2xl leading-none" id="partner-departures-close" aria-label="Fermer">&times;</button>
            </div>
            <div class="p-6">
                <div id="partner-departures-loading" class="text-sm text-gray-500">Chargement…</div>
                <div id="partner-departures-empty" class="text-sm text-gray-500 hidden">Aucun départ disponible.</div>
                <div id="partner-departures-list" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-end gap-2">
                <button type="button" class="px-4 py-2 rounded-xl text-sm font-bold border border-gray-200 hover:bg-white" id="partner-departures-cancel">Fermer</button>
            </div>
        </div>
    </div>
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
<script>
(function () {
    var modal = document.getElementById('partner-departures-modal');
    var closeBtn = document.getElementById('partner-departures-close');
    var cancelBtn = document.getElementById('partner-departures-cancel');
    var subtitle = document.getElementById('partner-departures-subtitle');
    var list = document.getElementById('partner-departures-list');
    var loading = document.getElementById('partner-departures-loading');
    var empty = document.getElementById('partner-departures-empty');

    var departuresEndpoint = @json(route('partner.reservations.voyage-departures'));
    var createBaseUrl = @json(route('partner.reservations.create'));

    function openModal() {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        list.innerHTML = '';
        loading.classList.remove('hidden');
        empty.classList.add('hidden');
    }

    function formatMoney(value) {
        return (Math.round((Number(value) || 0) * 100) / 100).toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' MAD';
    }

    function buildCreateUrl(tourId, departure) {
        var params = new URLSearchParams();
        params.set('voyage_id', String(tourId));
        params.set('tour_id', String(tourId));
        if (departure && departure.id) params.set('departure_id', String(departure.id));
        if (departure && (departure.wp_travel_date_id || departure.travel_date_id)) {
            params.set('travel_date_id', String(departure.wp_travel_date_id || departure.travel_date_id));
        }
        return createBaseUrl + '?' + params.toString();
    }

    function loadDepartures(tourId, tourName) {
        subtitle.textContent = tourName || ('Voyage #' + tourId);
        list.innerHTML = '';
        loading.classList.remove('hidden');
        empty.classList.add('hidden');

        fetch(departuresEndpoint + '?tour_id=' + encodeURIComponent(tourId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var departures = (data && data.departures) ? data.departures : [];
                loading.classList.add('hidden');

                if (!departures.length) {
                    empty.classList.remove('hidden');
                    return;
                }

                departures.forEach(function (d) {
                    var cap = (d.available_capacity !== undefined && d.available_capacity !== null) ? d.available_capacity : '—';
                    var price = d.unit_price || 0;
                    var card = document.createElement('a');
                    card.href = buildCreateUrl(tourId, d);
                    card.className = 'block rounded-2xl border border-gray-100 hover:border-[#0083c4] hover:shadow-sm transition p-4';
                    card.innerHTML =
                        '<div class=\"flex items-start justify-between gap-3\">' +
                            '<div class=\"min-w-0\">' +
                                '<div class=\"font-extrabold text-[#0e3a5a] text-sm truncate\">' + (d.label || 'Départ') + '</div>' +
                                '<div class=\"text-xs text-gray-500 mt-1\">Places: <span class=\"font-bold text-gray-700\">' + cap + '</span></div>' +
                            '</div>' +
                            '<div class=\"text-right\">' +
                                '<div class=\"text-xs text-gray-500\">Prix / pers</div>' +
                                '<div class=\"font-black text-[#0e3a5a]\">' + (price > 0 ? formatMoney(price) : '—') + '</div>' +
                            '</div>' +
                        '</div>';
                    list.appendChild(card);
                });
            })
            .catch(function () {
                loading.classList.add('hidden');
                empty.classList.remove('hidden');
            });
    }

    document.querySelectorAll('.js-open-departures').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tourId = btn.getAttribute('data-tour-id');
            var tourName = btn.getAttribute('data-tour-name') || '';
            if (!tourId) return;
            openModal();
            loadDepartures(tourId, tourName);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal.firstElementChild) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    }
})();
</script>
@endpush
