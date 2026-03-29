@extends('layouts.master-ajinsafro')

@section('title', 'Réservations')

@php
    $hubStats = $hubStats ?? ['total' => 0, 'en_cours' => 0, 'validee' => 0, 'annulee' => 0];
    $filterTourId = $filterTourId ?? null;
    $filterTravelDateId = $filterTravelDateId ?? null;
    $filterSearch = $filterSearch ?? null;
    $filterStatus = $filterStatus ?? null;
    $voyageOptions = $voyageOptions ?? collect();
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

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('admin.reservations.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Voyage</label>
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
                    <div class="h4 mb-0 fw-bold text-primary">{{ $hubStats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-3">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size:0.65rem;letter-spacing:.06em;">En attente</div>
                    <div class="h4 mb-0 fw-bold">{{ $hubStats['en_cours'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-3">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size:0.65rem;letter-spacing:.06em;">Confirmées</div>
                    <div class="h4 mb-0 fw-bold text-success">{{ $hubStats['validee'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-3">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size:0.65rem;letter-spacing:.06em;">Annulées</div>
                    <div class="h4 mb-0 fw-bold text-danger">{{ $hubStats['annulee'] }}</div>
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
                        <th>Voyage</th>
                        <th>Départ</th>
                        <th>Passagers</th>
                        <th>Paiement</th>
                        <th>Statut</th>
                        <th>Créée le</th>
                        <th class="text-end pe-3" style="min-width:200px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($reservations as $reservation)
                        <tr>
                            <td class="ps-3 text-muted small">{{ $reservation->id }}</td>
                            <td>
                                @if($reservation->client)
                                    <strong>{{ $reservation->client->full_name }}</strong>
                                    <span class="text-muted small d-block">{{ $reservation->client->client_code }}</span>
                                @else
                                    {{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}
                                @endif
                            </td>
                            <td>{{ $reservation->tour?->name ?? '—' }}</td>
                            <td class="small">
                                @if($reservation->travelDate?->date)
                                    {{ $reservation->travelDate->date->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $names = $reservation->passengers->map(fn($p) => trim(($p->first_name ?? '').' '.($p->last_name ?? '')))->filter()->values();
                                @endphp
                                @if($names->isEmpty())
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="text-break small">{{ $names->take(3)->join(', ') }}{{ $names->count() > 3 ? '…' : '' }}</span>
                                @endif
                            </td>
                            <td>
                                @if($reservation->payment_type)
                                    <span class="badge bg-light text-dark">{{ $reservation->payment_type }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClass = match($reservation->status) {
                                        \App\Models\Reservation::STATUS_EN_COURS => 'badge bg-warning text-dark',
                                        \App\Models\Reservation::STATUS_VALIDEE => 'badge bg-success',
                                        \App\Models\Reservation::STATUS_ANNULEE => 'badge bg-danger',
                                        default => 'badge bg-secondary',
                                    };
                                @endphp
                                <span class="{{ $statusClass }}">{{ $reservation->status }}</span>
                            </td>
                            <td class="small">{{ optional($reservation->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary btn-res-hub-detail" title="Détails"
                                            data-res-id="{{ $reservation->id }}"><i class="bx bx-info-circle"></i></button>
                                    <button type="button" class="btn btn-outline-secondary btn-res-hub-pax" title="Participants"
                                            data-res-id="{{ $reservation->id }}"><i class="bx bx-group"></i></button>
                                    <button type="button" class="btn btn-outline-primary btn-res-hub-edit" title="Modifier"
                                            data-res-id="{{ $reservation->id }}"><i class="bx bx-pencil"></i></button>
                                </div>
                                @if($reservation->status !== \App\Models\Reservation::STATUS_VALIDEE)
                                    <form action="{{ route('admin.reservations.validate', $reservation) }}" method="post" class="d-inline ms-1">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Valider"><i class="bx bx-check"></i></button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.reservations.destroy', $reservation) }}" method="post" class="d-inline ms-1" onsubmit="return confirm('Supprimer cette réservation ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">Aucune réservation trouvée.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($reservations, 'links'))
                <div class="px-3 py-2 border-top">{{ $reservations->links() }}</div>
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
                                <th>#</th><th>Client</th><th>tour_id</th><th>Voyage</th><th>wp tour</th><th>catalog</th><th>vol id</th><th>prest.</th><th>td id</th><th>Départ</th><th>Statut</th><th>Créée</th><th>Pax</th>
                            </tr></thead>
                            <tbody id="resHubDebugTbody"><tr><td colspan="13" class="text-muted">Ouvrez le modal pour charger…</td></tr></tbody>
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
    </style>
@endsection

@push('script')
<script>
(function () {
    var root = document.getElementById('res-hub-root');
    if (!root) return;
    var base = root.getAttribute('data-res-base') || '';
    var hubDebugUrl = root.getAttribute('data-hub-debug-url') || '';
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

    document.querySelectorAll('.btn-res-hub-detail').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-res-id');
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
                h += '<dt class="col-sm-4">Voyage</dt><dd class="col-sm-8">' + esc(d.tour_name || '—') + '</dd>';
                h += '<dt class="col-sm-4">Départ</dt><dd class="col-sm-8">' + esc(d.travel_date_label || '—') + (d.travel_date_id ? ' <code class="small">id ' + esc(String(d.travel_date_id)) + '</code>' : '') + '</dd>';
                h += '<dt class="col-sm-4">Type prestation</dt><dd class="col-sm-8">' + esc(d.prestation_type || '—') + '</dd>';
                h += '<dt class="col-sm-4">Montants</dt><dd class="col-sm-8">Total : ' + esc(String(d.base_price ?? '—')) + ' · Payé : ' + esc(String(d.paid_amount ?? '—')) + '</dd>';
                h += '<dt class="col-sm-4">Paiement</dt><dd class="col-sm-8">' + esc(d.payment_type || '—') + '</dd>';
                h += '<dt class="col-sm-4">Créée</dt><dd class="col-sm-8">' + esc(d.created_at || '—') + '</dd>';
                if (d.branch) h += '<dt class="col-sm-4">Agence</dt><dd class="col-sm-8">' + esc(d.branch) + '</dd>';
                h += '</dl>';
                el.innerHTML = h;
            });
        });
    });

    document.querySelectorAll('.btn-res-hub-pax').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-res-id');
            var modal = new bootstrap.Modal(document.getElementById('resHubPaxModal'));
            document.getElementById('resHubPaxTitle').textContent = 'Participants · #' + id;
            document.getElementById('resHubPaxBody').innerHTML = '<p class="text-muted p-3 mb-0">Chargement…</p>';
            modal.show();
            fetchPanel(id, function (d) {
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
        });
    });

    var offEl = document.getElementById('resHubEditOffcanvas');
    var frame = document.getElementById('resHubEditFrame');
    document.querySelectorAll('.btn-res-hub-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-res-id');
            if (frame) frame.src = editUrl(id);
            var oc = bootstrap.Offcanvas.getOrCreateInstance(offEl);
            oc.show();
        });
    });
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
            if (tbody) tbody.innerHTML = '<tr><td colspan="13" class="text-muted">Chargement…</td></tr>';
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
                            tbody.innerHTML = '<tr><td colspan="13" class="text-muted">Aucune réservation.</td></tr>';
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
                    if (tbody) tbody.innerHTML = '<tr><td colspan="13" class="text-danger">Échec du chargement.</td></tr>';
                });
        });
    }
})();
</script>
@endpush
