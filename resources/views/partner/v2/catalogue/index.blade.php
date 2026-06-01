@extends('partner_v2.layouts.app')
@section('title', 'Catalogue voyages')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Catalogue voyages</h1>
    <p class="text-sm text-gray-500 mt-1">Voyages que vous pouvez proposer et vendre. Prix public et commission applicables.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
    @forelse($voyages as $voyage)
        <div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden flex flex-col">
            <div class="p-6 flex-1">
                <h3 class="font-bold text-[#0e3a5a] text-base line-clamp-2">{{ $voyage->name }}</h3>
                @if($voyage->destination)
                    <p class="text-xs text-gray-500 mt-1">{{ $voyage->destination }}</p>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Prix public</div>
                        <div class="text-sm font-black text-[#0e3a5a] mt-1">{{ $voyage->catalog_public_price_display ?? '—' }}</div>
                    </div>
                    <div class="bg-[#e6f3fa]/60 border border-[#e6f3fa] rounded-xl p-3">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Commission</div>
                        <div class="text-sm font-black text-green-700 mt-1">{{ $voyage->catalog_commission_display ?? '—' }}</div>
                    </div>
                </div>
            </div>
            <div class="p-5 border-t border-gray-100 bg-gray-50/40 flex items-center justify-between">
                <button type="button"
                        class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-5 py-2 rounded-xl text-xs font-bold transition-colors js-open-booking"
                        data-tour-id="{{ $voyage->id }}"
                        data-tour-name="{{ e($voyage->name) }}">
                    Réserver
                </button>
                <span class="text-[10px] font-bold text-gray-400">ID: {{ $voyage->id }}</span>
            </div>
        </div>
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

{{-- Modal réservation : choix du départ (comme le catalogue ventes admin) --}}
<div id="partner-booking-modal" class="fixed inset-0 z-[9999] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative w-full h-full flex items-end sm:items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-[#0e3a5a] text-lg">Choisir un départ</h3>
                    <p class="text-xs text-gray-500 mt-0.5" id="partner-booking-modal-subtitle">—</p>
                </div>
                <button type="button" class="text-gray-500 hover:text-gray-900 text-2xl leading-none" id="partner-booking-close" aria-label="Fermer">&times;</button>
            </div>
            <div class="p-6">
                <div id="partner-booking-loading" class="text-sm text-gray-500">Chargement des départs…</div>
                <div id="partner-booking-empty" class="text-sm text-gray-500 hidden">Aucun départ disponible.</div>
                <div id="partner-booking-list" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-end gap-2">
                <button type="button" class="px-4 py-2 rounded-xl text-sm font-bold border border-gray-200 hover:bg-white" id="partner-booking-cancel">Annuler</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('partner-booking-modal');
    var closeBtn = document.getElementById('partner-booking-close');
    var cancelBtn = document.getElementById('partner-booking-cancel');
    var subtitle = document.getElementById('partner-booking-modal-subtitle');
    var list = document.getElementById('partner-booking-list');
    var loading = document.getElementById('partner-booking-loading');
    var empty = document.getElementById('partner-booking-empty');

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
        return (Math.round((Number(value) || 0) * 100) / 100).toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' DH';
    }

    function buildCreateUrl(tourId, departure) {
        var params = new URLSearchParams();
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

                if (departures.length === 1) {
                    window.location.href = buildCreateUrl(tourId, departures[0]);
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

    document.querySelectorAll('.js-open-booking').forEach(function (btn) {
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
