@extends('layouts.master-ajinsafro')

@section('title', 'Réservations')

@php
    $hubStats = $hubStats ?? ['total' => 0, 'en_cours' => 0, 'validee' => 0, 'annulee' => 0];
    $filterTourId = $filterTourId ?? null;
    $filterTravelDateId = $filterTravelDateId ?? null;
    $filterSearch = $filterSearch ?? null;
    $filterStatus = $filterStatus ?? null;
    $highlightReservationId = $highlightReservationId ?? 0;
    $voyageOptions = $voyageOptions ?? collect();
    $reservationCreated = isset($reservationCreated) && is_array($reservationCreated)
        ? $reservationCreated
        : (session('reservation_created') ?: null);
    $baseQuery = array_filter([
        'voyage_id' => $filterTourId,
        'travel_date_id' => $filterTravelDateId,
        'search' => $filterSearch,
    ], fn ($v) => $v !== null && $v !== '');
@endphp

@section('content')
    <div id="res-hub-root"
         class="d-none"
         data-res-base="{{ rtrim(url('/admin/reservations'), '/') }}"
         data-csrf="{{ csrf_token() }}"
         @can('reservations.view')
         data-hub-refresh-url="{{ route('admin.reservations.hub-refresh') }}"
         @endcan
         @if(config('app.debug') && auth()->user()->can('reservations.view'))
         data-hub-debug-url="{{ route('admin.reservations.hub-debug') }}"
         @endif
    ></div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="page-title mb-0 font-size-18">Réservations</h4>
                    <p class="text-muted small mb-0">Une seule vue : filtres, statistiques et liste sont synchronisés.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('reservations.view')
                        <a href="{{ route('admin.reservations.workspace') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-grid-alt me-1"></i> Catalogue (réserver)
                        </a>
                    @endcan
                    <a href="{{ route('admin.reservations.create') }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-plus me-1"></i> Nouvelle réservation
                    </a>
                    @if(config('app.debug') && auth()->user()->can('reservations.view'))
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn-res-hub-debug" data-bs-toggle="modal" data-bs-target="#resHubDebugModal" title="APP_DEBUG : liste brute des réservations (filtres actuels)">
                            <i class="bx bx-bug me-1"></i> Debug réservations
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success') && empty($reservationCreated))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(is_array($reservationCreated))
        <div class="alert alert-success fade show shadow-sm border border-success mb-3" role="status" id="res-hub-created-banner">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div class="flex-grow-1 min-w-0">
                    <strong class="d-block mb-2"><i class="bx bx-check-circle me-1"></i> Réservation créée avec succès</strong>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-1">N° réservation</dt>
                        <dd class="col-sm-8 col-md-9 mb-1"><strong>#{{ $reservationCreated['id'] ?? '—' }}</strong></dd>
                        <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-1">Offre liée</dt>
                        <dd class="col-sm-8 col-md-9 mb-1">{{ $reservationCreated['voyage_name'] ?? '—' }}</dd>
                        <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-1">Date de départ</dt>
                        <dd class="col-sm-8 col-md-9 mb-1">{{ $reservationCreated['departure_label'] ?? '—' }}</dd>
                        <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-1">Personnes</dt>
                        <dd class="col-sm-8 col-md-9 mb-1">{{ $reservationCreated['pax_count'] ?? '—' }}</dd>
                        <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-1">Total</dt>
                        <dd class="col-sm-8 col-md-9 mb-1">{{ $reservationCreated['total_label'] ?? '—' }}</dd>
                        <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-0">Statut</dt>
                        <dd class="col-sm-8 col-md-9 mb-0">{{ $reservationCreated['status_label'] ?? '—' }}</dd>
                    </dl>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center flex-shrink-0">
                    <a href="{{ $reservationCreated['urls']['edit'] ?? '#' }}" class="btn btn-primary btn-sm">Voir la réservation</a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="alert">Fermer</button>
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('admin.reservations.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Offre</label>
                    <select name="voyage_id" class="form-select form-select-sm">
                        <option value="">— Tous —</option>
                        @foreach($voyageOptions as $v)
                            <option value="{{ $v->id }}" @selected((string)$filterTourId === (string)$v->id)>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Départ (TravelDate id)</label>
                    <input type="number" name="travel_date_id" class="form-control form-control-sm" placeholder="ex. 234"
                           value="{{ $filterTravelDateId }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Statut</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="{{ \App\Models\Reservation::STATUS_EN_COURS }}" @selected($filterStatus === \App\Models\Reservation::STATUS_EN_COURS)>En attente</option>
                        <option value="{{ \App\Models\Reservation::STATUS_VALIDEE }}" @selected($filterStatus === \App\Models\Reservation::STATUS_VALIDEE)>Confirmée</option>
                        <option value="{{ \App\Models\Reservation::STATUS_ANNULEE }}" @selected($filterStatus === \App\Models\Reservation::STATUS_ANNULEE)>Annulée</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Recherche client</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Nom, email, téléphone…"
                           value="{{ $filterSearch }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filtrer</button>
                    <a href="{{ route('admin.reservations.index') }}" class="btn btn-outline-secondary btn-sm">Réinit.</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-3">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size:0.65rem;letter-spacing:.06em;">Total (filtré)</div>
                    <div class="h4 mb-0 fw-bold text-primary" id="res-hub-stat-total">{{ $hubStats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-3">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size:0.65rem;letter-spacing:.06em;">En attente</div>
                    <div class="h4 mb-0 fw-bold" id="res-hub-stat-en-cours">{{ $hubStats['en_cours'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-3">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size:0.65rem;letter-spacing:.06em;">Confirmées</div>
                    <div class="h4 mb-0 fw-bold text-success" id="res-hub-stat-validee">{{ $hubStats['validee'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-3">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size:0.65rem;letter-spacing:.06em;">Annulées</div>
                    <div class="h4 mb-0 fw-bold text-danger" id="res-hub-stat-annulee">{{ $hubStats['annulee'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 reservations-table">
                    <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Client</th>
                        <th>Offre</th>
                        <th>Créée par</th>
                        <th>Agence</th>
                        <th>Départ</th>
                        <th>Passagers</th>
                        <th>Paiement</th>
                        <th>Statut</th>
                        <th>Créée le</th>
                        <th class="text-end pe-3" style="min-width:200px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="res-hub-tbody">
                    @include('admin.reservations.partials.hub-table-rows', ['reservations' => $reservations, 'highlightReservationId' => $highlightReservationId])
                    </tbody>
                </table>
            </div>
            @if(method_exists($reservations, 'links'))
                <div class="px-3 py-2 border-top" id="res-hub-pagination">{{ $reservations->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Modal détails --}}
    <div class="modal fade" id="resHubDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resHubDetailTitle">Détails</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small" id="resHubDetailBody">
                    <p class="text-muted mb-0">Chargement…</p>
                </div>
            </div>
        </div>
    </div>

    @if(config('app.debug') && auth()->user()->can('reservations.view'))
    {{-- Modal debug : toutes les réservations (même périmètre que le hub, max 500) --}}
    <div class="modal fade" id="resHubDebugModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Debug — réservations (filtres page actuels)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">Données JSON + tableau. Même requête que la liste (agence / portail + filtres GET). Max 500 lignes. Visible uniquement si <code>APP_DEBUG=true</code>.</p>
                    <ul class="small mb-3" id="resHubDebugMeta"></ul>
                    <pre class="bg-light border rounded p-2 small mb-3" style="max-height:220px;overflow:auto;" id="resHubDebugJson"></pre>
                    <div class="table-responsive" style="max-height:50vh;">
                        <table class="table table-sm table-bordered align-middle mb-0" id="resHubDebugTable">
                            <thead class="table-light"><tr>
                                <th>#</th><th>Client</th><th>tour_id</th><th>Offre</th><th>Créée par</th><th>Agence</th><th>wp tour</th><th>catalog</th><th>vol id</th><th>prest.</th><th>td id</th><th>Départ</th><th>Statut</th><th>Créée</th><th>Pax</th>
                            </tr></thead>
                            <tbody id="resHubDebugTbody"><tr><td colspan="15" class="text-muted">Ouvrez le modal pour charger…</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal participants --}}
    <div class="modal fade" id="resHubPaxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resHubPaxTitle">Participants</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" id="resHubPaxBody">
                    <p class="text-muted p-3 mb-0">Chargement…</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tiroir édition (iframe) --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="resHubEditOffcanvas" style="width:min(960px, 100vw);">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Modifier la réservation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0" style="height:calc(100vh - 56px);">
            <iframe id="resHubEditFrame" class="w-100 h-100 border-0" title="Édition réservation"></iframe>
        </div>
    </div>

    <style>
        .reservations-table th { font-weight: 600; white-space: nowrap; font-size: 0.8rem; }
        .reservations-table td { vertical-align: middle; }
        .res-hub-row-highlight { --res-hub-highlight-rgb: 25, 135, 84; background-color: rgba(var(--res-hub-highlight-rgb), 0.08); box-shadow: inset 3px 0 0 0 rgb(var(--res-hub-highlight-rgb)); }
    </style>
@endsection

@push('script')
<script>
(function () {
    var root = document.getElementById('res-hub-root');
    if (!root) return;
    var base = root.getAttribute('data-res-base') || '';
    var hubDebugUrl = root.getAttribute('data-hub-debug-url') || '';
    var hubRefreshUrl = root.getAttribute('data-hub-refresh-url') || '';

    function applyHubRefreshPayload(payload) {
        if (!payload || !payload.hub_stats) return;
        var hs = payload.hub_stats;
        var pairs = [
            ['res-hub-stat-total', hs.total],
            ['res-hub-stat-en-cours', hs.en_cours],
            ['res-hub-stat-validee', hs.validee],
            ['res-hub-stat-annulee', hs.annulee]
        ];
        pairs.forEach(function (p) {
            var el = document.getElementById(p[0]);
            if (el) el.textContent = String(p[1] != null ? p[1] : '0');
        });
        var tbody = document.getElementById('res-hub-tbody');
        if (tbody && payload.tbody_html) tbody.innerHTML = payload.tbody_html;
        var pag = document.getElementById('res-hub-pagination');
        if (pag && typeof payload.pagination_html === 'string') pag.innerHTML = payload.pagination_html;
    }

    function scrollToHighlightedReservationRow() {
        var row = document.getElementById('res-hub-highlight-row');
        if (!row) return;
        setTimeout(function () {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    function fetchAndApplyHubRefresh() {
        if (!hubRefreshUrl) return Promise.resolve();
        var full = hubRefreshUrl + (window.location.search || '');
        return fetch(full, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.ok ? r.json() : Promise.reject(); }).then(applyHubRefreshPayload).catch(function () {});
    }
    function panelUrl(id) { return base + '/' + encodeURIComponent(id) + '/panel'; }
    function editUrl(id) {
        var u = base + '/' + encodeURIComponent(id) + '/edit?embed=1';
        var loc = new URL(window.location.href);
        var q = loc.searchParams;
        ['voyage_id', 'travel_date_id', 'status', 'search'].forEach(function (k) {
            var v = q.get(k);
            if (v) u += '&rq_' + k + '=' + encodeURIComponent(v);
        });
        return u;
    }
    function fetchPanel(id, cb) {
        fetch(panelUrl(id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.ok ? r.json() : Promise.reject(); }).then(cb).catch(function () {
            cb(null);
        });
    }
    function esc(s) {
        if (s == null) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function typeLabel(t) {
        if (t === 'child') return 'Enfant';
        if (t === 'infant') return 'Bébé';
        return 'Adulte';
    }

    var hubTable = document.querySelector('table.reservations-table');
    if (hubTable) {
        hubTable.addEventListener('click', function (e) {
            var detailBtn = e.target.closest('.btn-res-hub-detail');
            if (detailBtn) {
                var id = detailBtn.getAttribute('data-res-id');
                var modal = new bootstrap.Modal(document.getElementById('resHubDetailModal'));
                document.getElementById('resHubDetailTitle').textContent = 'Réservation #' + id;
                document.getElementById('resHubDetailBody').innerHTML = '<p class="text-muted mb-0">Chargement…</p>';
                modal.show();
                fetchPanel(id, function (d) {
                    var el = document.getElementById('resHubDetailBody');
                    if (!d) {
                        el.innerHTML = '<p class="text-danger mb-0">Impossible de charger les détails.</p>';
                        return;
                    }
                    var h = '<dl class="row mb-0">';
                    h += '<dt class="col-sm-4">Statut</dt><dd class="col-sm-8">' + esc(d.status) + '</dd>';
                    h += '<dt class="col-sm-4">Client</dt><dd class="col-sm-8">' + esc(d.client_label || '—') + (d.client_code ? ' <span class="text-muted">(' + esc(d.client_code) + ')</span>' : '') + '</dd>';
                    h += '<dt class="col-sm-4">Offre liée</dt><dd class="col-sm-8">' + esc(d.tour_name || '—') + '</dd>';
                    h += '<dt class="col-sm-4">Créée par</dt><dd class="col-sm-8">' + esc(d.creator_name || '—') + (d.creator_email ? ' <span class="text-muted">(' + esc(d.creator_email) + ')</span>' : '') + '</dd>';
                    h += '<dt class="col-sm-4">Départ</dt><dd class="col-sm-8">' + esc(d.travel_date_label || '—') + (d.travel_date_id ? ' <code class="small">id ' + esc(String(d.travel_date_id)) + '</code>' : '') + '</dd>';
                    h += '<dt class="col-sm-4">Type prestation</dt><dd class="col-sm-8">' + esc(d.prestation_type || '—') + '</dd>';
                    h += '<dt class="col-sm-4">Montants</dt><dd class="col-sm-8">Total : ' + esc(String(d.base_price ?? '—')) + ' · Payé : ' + esc(String(d.paid_amount ?? '—')) + '</dd>';
                    h += '<dt class="col-sm-4">Paiement</dt><dd class="col-sm-8">' + esc(d.payment_type || '—') + '</dd>';
                    h += '<dt class="col-sm-4">Créée</dt><dd class="col-sm-8">' + esc(d.created_at || '—') + '</dd>';
                    if (d.agency || d.branch) h += '<dt class="col-sm-4">Agence</dt><dd class="col-sm-8">' + esc(d.agency || d.branch) + '</dd>';
                    h += '</dl>';
                    el.innerHTML = h;
                });
                return;
            }
            var paxBtn = e.target.closest('.btn-res-hub-pax');
            if (paxBtn) {
                var idP = paxBtn.getAttribute('data-res-id');
                var paxModal = new bootstrap.Modal(document.getElementById('resHubPaxModal'));
                document.getElementById('resHubPaxTitle').textContent = 'Participants · #' + idP;
                document.getElementById('resHubPaxBody').innerHTML = '<p class="text-muted p-3 mb-0">Chargement…</p>';
                paxModal.show();
                fetchPanel(idP, function (d) {
                    var el = document.getElementById('resHubPaxBody');
                    if (!d || !d.passengers || !d.passengers.length) {
                        el.innerHTML = '<p class="text-muted p-3 mb-0">Aucun participant enregistré.</p>';
                        return;
                    }
                    var h = '<table class="table table-sm mb-0"><thead><tr><th>Nom</th><th>Type</th><th>Document</th></tr></thead><tbody>';
                    d.passengers.forEach(function (p) {
                        var name = esc((p.first_name || '') + ' ' + (p.last_name || '')).trim() || '—';
                        h += '<tr><td>' + name + '</td><td>' + esc(typeLabel(p.type)) + '</td><td class="small">' + esc(p.document_number || '—') + '</td></tr>';
                    });
                    h += '</tbody></table>';
                    el.innerHTML = h;
                });
                return;
            }
            var editBtn = e.target.closest('.btn-res-hub-edit');
            if (editBtn) {
                var idE = editBtn.getAttribute('data-res-id');
                if (frame) frame.src = editUrl(idE);
                var oc = bootstrap.Offcanvas.getOrCreateInstance(offEl);
                oc.show();
            }
        });
    }

    var offEl = document.getElementById('resHubEditOffcanvas');
    var frame = document.getElementById('resHubEditFrame');
    if (offEl) {
        offEl.addEventListener('hidden.bs.offcanvas', function () {
            if (frame) frame.src = 'about:blank';
        });
    }

    var debugModal = document.getElementById('resHubDebugModal');
    if (debugModal && hubDebugUrl) {
        debugModal.addEventListener('show.bs.modal', function () {
            var metaEl = document.getElementById('resHubDebugMeta');
            var jsonEl = document.getElementById('resHubDebugJson');
            var tbody = document.getElementById('resHubDebugTbody');
            if (metaEl) metaEl.innerHTML = '<li>Chargement…</li>';
            if (jsonEl) jsonEl.textContent = '';
            if (tbody) tbody.innerHTML = '<tr><td colspan="15" class="text-muted">Chargement…</td></tr>';
            var u = hubDebugUrl + (window.location.search || '');
            fetch(u, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)); })
                .then(function (data) {
                    if (jsonEl) jsonEl.textContent = JSON.stringify(data, null, 2);
                    if (metaEl) {
                        var hs = data.hub_stats || {};
                        var f = data.filters || {};
                        metaEl.innerHTML =
                            '<li><strong>Total filtré (stats)</strong> : ' + esc(String(hs.total ?? '—')) + ' · en cours ' + esc(String(hs.en_cours ?? '—')) + ' · validées ' + esc(String(hs.validee ?? '—')) + ' · annulées ' + esc(String(hs.annulee ?? '—')) + '</li>' +
                            '<li><strong>Réservations renvoyées</strong> : ' + esc(String(data.count ?? 0)) + ' (plafond ' + esc(String(data.limit ?? 500)) + ')</li>' +
                            '<li><strong>Filtres GET</strong> : voyage_id=' + esc(String(f.voyage_id || '—')) + ', travel_date_id=' + esc(String(f.travel_date_id || '—')) + ', status=' + esc(String(f.status || '—')) + ', search=' + esc(String(f.search || '—')) + '</li>';
                    }
                    if (tbody) {
                        var list = data.reservations || [];
                        if (!list.length) {
                            tbody.innerHTML = '<tr><td colspan="15" class="text-muted">Aucune réservation.</td></tr>';
                            return;
                        }
                        var h = '';
                        list.forEach(function (row) {
                            var pax = (row.passengers_preview || []).join(', ') || '—';
                            h += '<tr>' +
                                '<td class="text-nowrap">' + esc(String(row.id)) + '</td>' +
                                '<td class="small">' + esc(row.client_snapshot || '—') + '</td>' +
                                '<td>' + esc(String(row.tour_id ?? '—')) + '</td>' +
                                '<td class="small">' + esc(row.tour_name || '—') + '</td>' +
                                '<td class="small">' + esc(row.creator_name || '—') + '</td>' +
                                '<td class="small">' + esc(row.agency_name || '—') + '</td>' +
                                '<td class="small">' + esc(String(row.tour_wp_post_id ?? '—')) + ' / ' + esc(String(row.wp_tour_post_id ?? '—')) + '</td>' +
                                '<td class="small">' + esc(row.catalog_source_code || '—') + '</td>' +
                                '<td>' + esc(String(row.voyage_flight_id ?? '—')) + '</td>' +
                                '<td>' + esc(String(row.prestation_type || '—')) + '</td>' +
                                '<td>' + esc(String(row.travel_date_id ?? '—')) + '</td>' +
                                '<td class="small">' + esc(row.travel_date || '—') + '</td>' +
                                '<td>' + esc(String(row.status || '—')) + '</td>' +
                                '<td class="small text-nowrap">' + esc((row.created_at || '').replace('T', ' ').slice(0, 19)) + '</td>' +
                                '<td class="small">' + esc(String(row.passengers_count ?? 0)) + ' · ' + esc(pax) + '</td>' +
                                '</tr>';
                        });
                        tbody.innerHTML = h;
                    }
                })
                .catch(function () {
                    if (metaEl) metaEl.innerHTML = '<li class="text-danger">Erreur de chargement (vérifiez APP_DEBUG et la route).</li>';
                    if (tbody) tbody.innerHTML = '<tr><td colspan="15" class="text-danger">Échec du chargement.</td></tr>';
                });
        });
    }

    var createdBanner = document.getElementById('res-hub-created-banner');
    var needsHubRefresh = (hubRefreshUrl && createdBanner) || (hubRefreshUrl && /(?:^|[?&])highlight=/.test(window.location.search));
    if (needsHubRefresh) {
        setTimeout(function () {
            fetchAndApplyHubRefresh().then(function () { scrollToHighlightedReservationRow(); });
        }, 150);
    } else if (document.getElementById('res-hub-highlight-row')) {
        scrollToHighlightedReservationRow();
    }
})();
</script>
@endpush
