@extends('partner_v2.layouts.app')
@section('title', 'Catalogue voyages')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/partner-catalogue.css') }}?v=1">
@endpush

@section('content')
@php
    $rows = $workspaceRows ?? collect();

    /**
     * Etat commercial d'une offre, deduit des donnees reelles :
     *   open = au moins un depart futur avec des places
     *   full = des departs, mais plus de place
     *   req  = aucune date programmee, vente sur demande
     */
    $statusOf = static function ($row): string {
        $departures = collect(data_get($row, 'modal_detail.departures', []));
        if ($departures->isEmpty()) {
            return 'req';
        }
        $capacity = data_get($departures->first(), 'available_capacity');

        return $capacity === null || (int) $capacity > 0 ? 'open' : 'full';
    };

    $statusCounts = ['all' => $rows->count(), 'open' => 0, 'full' => 0, 'req' => 0];
    foreach ($rows as $row) {
        $statusCounts[$statusOf($row)]++;
    }

    $openSeats = 0;
    foreach ($rows as $row) {
        if ($statusOf($row) !== 'open') {
            continue;
        }
        $openSeats += (int) data_get(collect(data_get($row, 'modal_detail.departures', []))->first(), 'available_capacity', 0);
    }

    $chips = [
        ['key' => 'all', 'label' => 'Tout le catalogue'],
        ['key' => 'open', 'label' => 'Départs ouverts'],
        ['key' => 'full', 'label' => 'Complets'],
        ['key' => 'req', 'label' => 'Sur demande'],
    ];
@endphp

<div class="pc-catalogue">

    <div class="pc-head">
        <div style="min-width:0;">
            <div class="pc-crumbs">
                <a href="{{ route('partner.dashboard') }}">Portail partenaire</a>
                <span class="pc-sep">/</span>
                <span>Catalogue</span>
            </div>
            <h1 class="pc-title">Voyages &amp; départs à la vente</h1>
            <p class="pc-lead">Choisissez un départ programmé pour réserver immédiatement, ou demandez une date pour les offres sur devis.</p>
        </div>
        <div class="pc-head-actions">
            @if(Route::has('partner.reservations-a-la-carte'))
                <a href="{{ route('partner.reservations-a-la-carte') }}" class="pc-btn -outline">Demande à la carte</a>
            @endif
            @if(Route::has('partner.reservations.create'))
                <a href="{{ route('partner.reservations.create') }}" class="pc-btn -accent">Nouvelle réservation</a>
            @endif
        </div>
    </div>

    <section class="pc-filters">
        <div class="pc-filters-top">
            <div class="pc-search">
                <span class="pc-search-dot" aria-hidden="true"></span>
                <label for="partner-catalog-search" class="visually-hidden" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);">Rechercher une offre</label>
                <input id="partner-catalog-search" type="search" autocomplete="off"
                       placeholder="Nom du voyage, référence, destination…">
            </div>
            <button type="button" class="pc-toggle" id="pc-toggle-filters" aria-expanded="false" aria-controls="pc-advanced">Filtres avancés</button>
            <label class="pc-sort">
                <span>Trier</span>
                <select id="pc-sort">
                    <option value="dep">Départs programmés d'abord</option>
                    <option value="price">Prix croissant</option>
                    <option value="az">Ordre alphabétique</option>
                </select>
            </label>
        </div>

        <div class="pc-advanced" id="pc-advanced" hidden>
            <label class="pc-field">
                <span>Type</span>
                <select id="partner-filter-type">
                    <option value="all">Tous</option>
                    <option value="package" selected>Circuit</option>
                </select>
            </label>
            <label class="pc-field">
                <span>Destination</span>
                <select id="partner-filter-destination">
                    <option value="">Toutes</option>
                    @foreach(($destinationOptions ?? []) as $d)
                        <option value="{{ $d }}">{{ $d }}</option>
                    @endforeach
                </select>
            </label>
            <label class="pc-field">
                <span>Départ du</span>
                <input id="partner-filter-date-from" type="date" class="pc-mono">
            </label>
            <label class="pc-field">
                <span>Départ au</span>
                <input id="partner-filter-date-to" type="date" class="pc-mono">
            </label>
            <label class="pc-field pc-field-range">
                <span>Budget par personne <b class="pc-mono" id="partner-filter-budget-label">jusqu'à 30 000 DH</b></span>
                <input id="partner-filter-budget" type="range" min="0" max="30000" step="500" value="30000">
            </label>
            <button type="button" id="partner-filter-apply" class="pc-btn -brand">Filtrer</button>
            <button type="button" id="partner-filter-reset" class="pc-btn -outline">Réinitialiser</button>
        </div>

        <div class="pc-chips" role="group" aria-label="Filtrer par état du départ">
            @foreach($chips as $chip)
                <button type="button" class="pc-chip" data-chip="{{ $chip['key'] }}"
                        aria-pressed="{{ $chip['key'] === 'all' ? 'true' : 'false' }}">
                    {{ $chip['label'] }}<b class="pc-mono">{{ $statusCounts[$chip['key']] }}</b>
                </button>
            @endforeach
        </div>
    </section>

    <div class="pc-resultline">
        <span><b class="pc-mono" id="pc-shown-count">{{ $rows->count() }}</b> offre{{ $rows->count() > 1 ? 's' : '' }} affichée{{ $rows->count() > 1 ? 's' : '' }}
            @if($statusCounts['open'] > 0)
                · {{ $openSeats }} place{{ $openSeats > 1 ? 's' : '' }} réservable{{ $openSeats > 1 ? 's' : '' }} sur {{ $statusCounts['open'] }} départ{{ $statusCounts['open'] > 1 ? 's' : '' }}
            @else
                · aucune place réservable dans cette sélection
            @endif
        </span>
        <span class="pc-note">Prix nets partenaire, par personne</span>
    </div>

    <div class="pc-grid" id="partner-catalog-grid">
        @forelse($rows as $row)
            @php
                $departures = collect(data_get($row, 'modal_detail.departures', []))->values();
                $firstDeparture = $departures->first();
                $imageUrl = data_get($row, 'image_url');
                $status = $statusOf($row);
                $capacity = data_get($firstDeparture, 'available_capacity');
                $futureCount = (int) data_get($row, 'ws_future_count', 0);
                $reference = (int) ($row['wp_post_id'] ?? 0) ?: (int) ($row['voyage_id'] ?? 0);

                $flagLabel = match ($status) {
                    'open' => 'RÉSERVABLE',
                    'full' => 'COMPLET',
                    default => 'SUR DEMANDE',
                };
                $seatLabel = match ($status) {
                    'open' => $capacity === null ? 'Places à confirmer' : (int) $capacity.' place'.((int) $capacity > 1 ? 's' : ''),
                    'full' => "Liste d'attente",
                    default => 'Capacité à définir',
                };
            @endphp
            <article class="pc-card{{ $status === 'open' ? ' is-open' : '' }}"
                     data-type="{{ $row['type'] ?? 'package' }}"
                     data-destination="{{ e((string) ($row['voyage_destination'] ?? '')) }}"
                     data-price="{{ (float) ($row['price_value'] ?? 0) }}"
                     data-next-date="{{ e((string) data_get($firstDeparture, 'date_iso', '')) }}"
                     data-status="{{ $status }}"
                     data-title="{{ e((string) ($row['name'] ?? '')) }}"
                     data-search="{{ Str::lower(trim(($row['name'] ?? '').' '.($row['voyage_destination'] ?? ''))) }}">

                <div class="pc-card-media">
                    @if($imageUrl)
                        {{-- Visuel injoignable : on retombe sur la trame plutot que sur une icone cassee. --}}
                        <img src="{{ $imageUrl }}" alt="" loading="lazy" onerror="this.remove();">
                    @else
                        <span class="pc-media-tag pc-mono">VISUEL À VENIR</span>
                    @endif
                    <span class="pc-flag -{{ $status }}">{{ $flagLabel }}</span>
                    @if($reference > 0)
                        <span class="pc-ref pc-mono">#{{ $reference }}</span>
                    @endif
                </div>

                <div class="pc-card-body">
                    <div>
                        <div class="pc-card-meta">
                            <span class="pc-type">CIRCUIT</span>
                            @if(!empty($row['voyage_destination']))
                                <span class="pc-place">{{ $row['voyage_destination'] }}</span>
                            @endif
                        </div>
                        <h2 class="pc-card-title">{{ $row['name'] ?? 'Voyage' }}</h2>
                    </div>

                    <div class="pc-price-row">
                        <div style="min-width:0;">
                            <div class="pc-price-label">À partir de</div>
                            @php
                                // « 8 600 DH » : le montant en chiffres tabulaires, l'unite plus discrete.
                                $priceLabel = trim((string) ($row['price_label'] ?? '—'));
                                $priceParts = preg_split('/\s+(?=\S+$)/u', $priceLabel);
                                // On ne detache le dernier mot que s'il ne contient aucun chiffre :
                                // « 8 600 DH » se scinde, « 8 600 » (devise absente) reste entier.
                                $hasUnit = count($priceParts) > 1 && ! preg_match('/\d/', $priceParts[1]);
                                $priceAmount = $hasUnit ? $priceParts[0] : $priceLabel;
                                $priceUnit = $hasUnit ? $priceParts[1] : '';
                            @endphp
                            <div class="pc-price">
                                <strong class="pc-mono">{{ $priceAmount }}</strong>
                                @if($priceUnit !== '')<span>{{ $priceUnit }}</span>@endif
                            </div>
                        </div>
                        <span class="pc-seats -{{ $status }}">{{ $seatLabel }}</span>
                    </div>

                    <div class="pc-card-foot">
                        @if($firstDeparture)
                            <div class="pc-foot-top">
                                <span>Prochain départ</span>
                                <span>{{ $futureCount > 1 ? $futureCount.' dates' : '1 date' }}</span>
                            </div>
                            <div class="pc-foot-date pc-mono">{{ data_get($firstDeparture, 'label') }}</div>
                            <div class="pc-foot-actions">
                                @if(data_get($firstDeparture, 'routes.reserve') && $status === 'open')
                                    <a href="{{ data_get($firstDeparture, 'routes.reserve') }}" class="pc-btn -accent">Réserver ce départ</a>
                                @elseif(data_get($firstDeparture, 'routes.reserve'))
                                    <a href="{{ data_get($firstDeparture, 'routes.reserve') }}" class="pc-btn -brand">Rejoindre la liste d'attente</a>
                                @endif
                                <button type="button" class="pc-link js-open-departures"
                                        data-tour-id="{{ (int) ($row['voyage_id'] ?? 0) }}"
                                        data-tour-name="{{ e($row['name'] ?? 'Voyage') }}">Tous les départs →</button>
                            </div>
                        @else
                            <div class="pc-foot-note">Aucune date programmée pour le moment.</div>
                            <div class="pc-foot-actions">
                                @if(Route::has('partner.reservations.create'))
                                    <a href="{{ route('partner.reservations.create') }}" class="pc-btn -outline">Demander une date</a>
                                @endif
                                <button type="button" class="pc-link js-open-departures"
                                        data-tour-id="{{ (int) ($row['voyage_id'] ?? 0) }}"
                                        data-tour-name="{{ e($row['name'] ?? 'Voyage') }}">Voir l'offre →</button>
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div style="grid-column:1/-1;">
                <div class="pc-empty">
                    <h2>Aucune offre au catalogue</h2>
                    <p>Aucun voyage n'est ouvert à la vente pour le moment. Revenez plus tard ou contactez votre conseiller Ajinsafro.</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Etat vide du filtrage cote client, affiche par le script. --}}
    <div class="pc-empty" id="pc-empty-filtered" hidden>
        <h2>Aucune offre pour ce filtre</h2>
        <p>Élargissez le budget ou revenez à l'ensemble du catalogue.</p>
        <button type="button" class="pc-btn -brand" id="pc-clear-filters">Voir tout le catalogue</button>
    </div>

    <div>{{ $voyages->links('pagination::bootstrap-5') }}</div>

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
    var grid = document.getElementById('partner-catalog-grid');
    if (!grid) return;

    var cards = Array.prototype.slice.call(grid.querySelectorAll('.pc-card'));
    var searchEl = document.getElementById('partner-catalog-search');
    var typeEl = document.getElementById('partner-filter-type');
    var destEl = document.getElementById('partner-filter-destination');
    var fromEl = document.getElementById('partner-filter-date-from');
    var toEl = document.getElementById('partner-filter-date-to');
    var budgetEl = document.getElementById('partner-filter-budget');
    var budgetLabel = document.getElementById('partner-filter-budget-label');
    var applyBtn = document.getElementById('partner-filter-apply');
    var resetBtn = document.getElementById('partner-filter-reset');
    var sortEl = document.getElementById('pc-sort');
    var toggleBtn = document.getElementById('pc-toggle-filters');
    var advanced = document.getElementById('pc-advanced');
    var chipBtns = Array.prototype.slice.call(document.querySelectorAll('.pc-chip'));
    var shownCount = document.getElementById('pc-shown-count');
    var emptyBox = document.getElementById('pc-empty-filtered');
    var clearBtn = document.getElementById('pc-clear-filters');
    var chip = 'all';

    function norm(v) { return String(v || '').toLowerCase().trim(); }

    function asDate(value) {
        if (!value) return null;
        var d = new Date(value + 'T00:00:00');
        return isNaN(d.getTime()) ? null : d;
    }

    function refreshBudgetLabel() {
        if (!budgetEl || !budgetLabel) return;
        var n = Number(budgetEl.value || 0);
        // Espace insecable fin remplace par une espace simple : meme rendu partout.
        budgetLabel.textContent = 'jusqu’à ' + n.toLocaleString('fr-FR').replace(/ | /g, ' ') + ' DH';
    }

    function apply() {
        var q = searchEl ? norm(searchEl.value) : '';
        var typeVal = typeEl ? String(typeEl.value || 'all') : 'all';
        var destVal = destEl ? String(destEl.value || '') : '';
        var dateFrom = fromEl ? asDate(fromEl.value) : null;
        var dateTo = toEl ? asDate(toEl.value) : null;
        var maxBudget = budgetEl ? parseFloat(budgetEl.value || '0') : null;
        var visible = 0;

        cards.forEach(function (card) {
            var show = true;

            if (q) {
                show = show && norm(card.getAttribute('data-search')).indexOf(q) !== -1;
            }
            if (chip !== 'all') {
                show = show && String(card.getAttribute('data-status') || '') === chip;
            }
            if (typeVal !== 'all') {
                show = show && String(card.getAttribute('data-type') || 'package') === typeVal;
            }
            if (destVal) {
                show = show && String(card.getAttribute('data-destination') || '') === destVal;
            }
            if (maxBudget !== null && maxBudget > 0) {
                var price = parseFloat(card.getAttribute('data-price') || '0') || 0;
                show = show && price <= maxBudget;
            }
            if (dateFrom || dateTo) {
                var next = asDate(String(card.getAttribute('data-next-date') || ''));
                if (!next) {
                    show = false;
                } else {
                    if (dateFrom) show = show && next.getTime() >= dateFrom.getTime();
                    if (dateTo) show = show && next.getTime() <= dateTo.getTime();
                }
            }

            card.hidden = !show;
            if (show) visible++;
        });

        if (shownCount) shownCount.textContent = String(visible);
        if (emptyBox) emptyBox.hidden = visible !== 0 || cards.length === 0;
    }

    function sortCards() {
        var mode = sortEl ? String(sortEl.value || 'dep') : 'dep';
        var rank = { open: 0, full: 1, req: 2 };
        var sorted = cards.slice().sort(function (a, b) {
            if (mode === 'price') {
                return (parseFloat(a.getAttribute('data-price')) || 0) - (parseFloat(b.getAttribute('data-price')) || 0);
            }
            if (mode === 'az') {
                return String(a.getAttribute('data-title') || '')
                    .localeCompare(String(b.getAttribute('data-title') || ''), 'fr');
            }
            var byStatus = (rank[a.getAttribute('data-status')] ?? 3) - (rank[b.getAttribute('data-status')] ?? 3);
            if (byStatus !== 0) return byStatus;
            return (parseFloat(a.getAttribute('data-price')) || 0) - (parseFloat(b.getAttribute('data-price')) || 0);
        });
        sorted.forEach(function (card) { grid.appendChild(card); });
    }

    function reset() {
        if (searchEl) searchEl.value = '';
        if (typeEl) typeEl.value = 'package';
        if (destEl) destEl.value = '';
        if (fromEl) fromEl.value = '';
        if (toEl) toEl.value = '';
        if (budgetEl) budgetEl.value = '30000';
        chip = 'all';
        chipBtns.forEach(function (b) {
            b.setAttribute('aria-pressed', b.getAttribute('data-chip') === 'all' ? 'true' : 'false');
        });
        refreshBudgetLabel();
        apply();
    }

    if (searchEl) searchEl.addEventListener('input', apply);
    if (budgetEl) budgetEl.addEventListener('input', function () { refreshBudgetLabel(); apply(); });
    [typeEl, destEl, fromEl, toEl].forEach(function (el) {
        if (el) el.addEventListener('change', apply);
    });
    if (applyBtn) applyBtn.addEventListener('click', apply);
    if (resetBtn) resetBtn.addEventListener('click', reset);
    if (clearBtn) clearBtn.addEventListener('click', reset);
    if (sortEl) sortEl.addEventListener('change', function () { sortCards(); apply(); });

    if (toggleBtn && advanced) {
        toggleBtn.addEventListener('click', function () {
            var open = toggleBtn.getAttribute('aria-expanded') === 'true';
            toggleBtn.setAttribute('aria-expanded', open ? 'false' : 'true');
            advanced.hidden = open;
        });
    }

    chipBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            chip = btn.getAttribute('data-chip') || 'all';
            chipBtns.forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
            apply();
        });
    });

    refreshBudgetLabel();
    sortCards();
    apply();
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
