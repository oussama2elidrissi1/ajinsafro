@extends('layouts.master-ajinsafro')
@section('title')
    Calendrier des départs
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Calendrier des départs</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
                        <li class="breadcrumb-item active">Calendrier</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres avancés --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2">
                    <h5 class="card-title mb-0 font-size-14"><i class="bx bx-filter-alt me-1"></i> Filtres</h5>
                </div>
                <div class="card-body">
                    <form id="calendar-filters" class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label for="filter-voyage" class="form-label small mb-0">Voyage</label>
                            <select id="filter-voyage" name="voyage" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                @foreach($voyages as $v)
                                    <option value="{{ $v->id }}" {{ (int)($selectedVoyageId ?? 0) === $v->id ? 'selected' : '' }}>{{ Str::limit($v->name, 30) }}</option>
                                @endforeach
                            </select>

                        </div>
                        <div class="col-md-2">
                            <label for="filter-destination" class="form-label small mb-0">Destination</label>
                            <select id="filter-destination" name="destination" class="form-select form-select-sm">
                                <option value="">Toutes</option>
                                @foreach($destinations ?? [] as $d)
                                    <option value="{{ $d }}" {{ ($selectedDestination ?? '') === $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filter-status" class="form-label small mb-0">Statut</label>
                            <select id="filter-status" name="status" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                @foreach($statuses ?? [] as $s)
                                    <option value="{{ $s }}" {{ ($selectedStatus ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filter-budget-min" class="form-label small mb-0">Budget min (DH)</label>
                            <input type="number" id="filter-budget-min" name="budget_min" class="form-control form-control-sm" placeholder="Min" min="0" value="{{ $budgetMin ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="filter-budget-max" class="form-label small mb-0">Budget max (DH)</label>
                            <input type="number" id="filter-budget-max" name="budget_max" class="form-control form-control-sm" placeholder="Max" min="0" value="{{ $budgetMax ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="filter-month" class="form-label small mb-0">Mois</label>
                            <input type="month" id="filter-month" name="month" class="form-control form-control-sm" value="{{ $month ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="filter-search" class="form-label small mb-0">Mot-clé / Nom</label>
                            <input type="text" id="filter-search" name="search" class="form-control form-control-sm" placeholder="Recherche…" value="{{ $search ?? '' }}">
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="button" id="btn-apply-filters" class="btn btn-primary btn-sm flex-grow-1"><i class="bx bx-search"></i> Filtrer</button>
                            <a href="{{ route('admin.reservations.calendrier') }}" class="btn btn-outline-secondary btn-sm" title="Réinitialiser">Réinitialiser</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="reservations-calendar"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal détail offre --}}
    <div class="modal fade" id="event-detail-modal" tabindex="-1" aria-labelledby="event-detail-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="event-detail-modal-label">Détail de l'offre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body" id="event-detail-body">
                    <div class="text-center py-4 text-muted" id="event-detail-loading">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div> Chargement…
                    </div>
                    <div id="event-detail-content" class="event-detail-content" style="display: none;"></div>
                </div>
                <div class="modal-footer" id="event-detail-footer" style="display: none;">
                    <button type="button" class="btn btn-outline-secondary" id="btn-modal-print"><i class="bx bx-printer me-1"></i> Imprimer</button>
                    <a href="#" id="btn-modal-consulter" class="btn btn-primary" target="_blank"><i class="bx bx-show me-1"></i> Consulter</a>
                    <a href="#" id="btn-modal-reserver" class="btn btn-success"><i class="bx bx-calendar-check me-1"></i> Réserver</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Zone cachée pour l'impression --}}
    <div id="event-print-area" style="display: none;"></div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/libs/moment/min/moment.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/jquery-ui-dist/jquery-ui.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/fullcalendar/index.global.min.js') }}"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        function escapeHtml(s) {
            if (s == null) return '';
            var div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML;
        }
        var calendarEl = document.getElementById('reservations-calendar');
        if (!calendarEl || typeof FullCalendar === 'undefined') return;

        var eventsUrl = '{{ route('admin.reservations.calendrier.events') }}';
        var detailsUrl = '{{ route('admin.reservations.calendrier.event-details') }}';
        var modal = document.getElementById('event-detail-modal');
        var modalBody = document.getElementById('event-detail-body');
        var detailLoading = document.getElementById('event-detail-loading');
        var detailContent = document.getElementById('event-detail-content');
        var detailFooter = document.getElementById('event-detail-footer');
        var printArea = document.getElementById('event-print-area');

        function getFilterParams() {
            var f = document.getElementById('calendar-filters');
            if (!f) return {};
            var fd = new FormData(f);
            var o = {};
            fd.forEach(function(v, k) { if (v !== '') o[k] = v; });
            return o;
        }

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'fr',
            firstDay: 1,
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek,listWeek'
            },
            events: {
                url: eventsUrl,
                method: 'GET',
                extraParams: getFilterParams,
                failure: function () { console.error('Impossible de charger les événements.'); }
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                var p = info.event.extendedProps || {};
                var travelDateId = p.travel_date_id;
                var voyageId = p.voyage_id;
                var wpTravelId = p.wp_travel_id;
                var date = p.departure_date;
                if (!date) return;
                var params = { date: date };
                if (travelDateId) params.travel_date_id = travelDateId;
                else if (voyageId) params.voyage_id = voyageId;
                else if (wpTravelId) params.wp_travel_id = wpTravelId;
                else return;
                var qs = Object.keys(params).map(function(k) { return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); }).join('&');
                detailContent.style.display = 'none';
                detailFooter.style.display = 'none';
                detailLoading.style.display = 'block';
                var modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
                fetch(detailsUrl + '?' + qs, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        detailLoading.style.display = 'none';
                        if (data.error) {
                            detailContent.innerHTML = '<p class="text-danger">' + (data.error || 'Erreur') + '</p>';
                        } else {
                            document.getElementById('event-detail-modal-label').textContent = data.name || 'Détail de l\'offre';
                            var html = '';
                            if (data.featured_image_url) {
                                html += '<div class="mb-3"><img src="' + data.featured_image_url + '" alt="" class="img-fluid rounded" style="max-height: 200px; object-fit: cover; width: 100%;"></div>';
                            }
                            html += '<div class="mb-3">';
                            html += '<p class="text-muted small mb-1">Date de départ : <strong>' + (data.departure_date_formatted || data.departure_date) + '</strong></p>';
                            if (data.destination) html += '<p class="mb-1"><span class="text-muted">Destination :</span> ' + escapeHtml(data.destination) + '</p>';
                            if (data.duration_text) html += '<p class="mb-1"><span class="text-muted">Durée :</span> ' + escapeHtml(data.duration_text) + '</p>';
                            if (data.display_price != null && data.display_price !== '') html += '<p class="mb-1"><span class="text-muted">Prix :</span> <strong>' + (typeof data.display_price === 'number' ? data.display_price.toLocaleString('fr-FR') : escapeHtml(String(data.display_price))) + ' ' + (data.currency_symbol || 'DH') + '</strong></p>';
                            if (data.status) html += '<p class="mb-1"><span class="text-muted">Statut :</span> <span class="badge bg-secondary">' + escapeHtml(data.status) + '</span></p>';
                            html += '</div>';
                            if (data.accroche) html += '<p class="small mb-3">' + escapeHtml(data.accroche) + '</p>';
                            if (data.description) html += '<div class="small text-muted mb-3">' + escapeHtml(data.description.substring(0, 400)) + (data.description.length > 400 ? '…' : '') + '</div>';

                            var hotels = data.hotels_with_rooms || [];
                            var hasAnyRoom = hotels.some(function(h) { return (h.rooms && h.rooms.length); });
                            html += '<div class="border-top pt-3 mt-3">';
                            html += '<h6 class="fw-semibold mb-2">Chambres disponibles</h6>';
                            if (!hasAnyRoom) {
                                html += '<p class="text-muted small mb-0">Aucune chambre configurée pour cette offre.</p>';
                            } else {
                                hotels.forEach(function(hotel) {
                                    if (!hotel.rooms || !hotel.rooms.length) return;
                                    html += '<div class="card mb-2">';
                                    html += '<div class="card-header py-1 px-2 bg-light small fw-semibold">' + escapeHtml(hotel.hotel_name || 'Hôtel') + '</div>';
                                    html += '<div class="card-body py-2 px-2">';
                                    html += '<table class="table table-sm table-bordered mb-0 small">';
                                    html += '<thead><tr><th>Type</th><th class="text-center">Capacité</th><th class="text-center">Suppl. (DH)</th></tr></thead><tbody>';
                                    hotel.rooms.forEach(function(r) {
                                        html += '<tr><td>' + escapeHtml(r.room_type) + (r.room_label ? ' <span class="text-muted">' + escapeHtml(r.room_label) + '</span>' : '') + '</td>';
                                        html += '<td class="text-center">' + r.capacity_adults + ' ad. / ' + r.capacity_children + ' enf. — ' + r.capacity_total + ' pers.</td>';
                                        html += '<td class="text-center">' + (r.supplement != null ? r.supplement : '0') + '</td></tr>';
                                    });
                                    html += '</tbody></table></div></div>';
                                });
                            }
                            html += '</div>';

                            detailContent.innerHTML = html;
                            detailContent.style.display = 'block';
                            document.getElementById('btn-modal-consulter').href = data.route_consulter || '#';
                            document.getElementById('btn-modal-reserver').href = data.route_reserver || '#';
                            document.getElementById('btn-modal-reserver').removeAttribute('target');
                            detailFooter.style.display = 'flex';
                            detailContent.dataset.printTitle = (data.name || 'Offre') + ' - ' + (data.departure_date_formatted || data.departure_date);
                        }
                    })
                    .catch(function () {
                        detailLoading.style.display = 'none';
                        detailContent.innerHTML = '<p class="text-danger">Impossible de charger les détails.</p>';
                        detailContent.style.display = 'block';
                    });
            },
            eventDidMount: function (info) {
                var p = info.event.extendedProps || {};
                var parts = [];
                if (p.destination) parts.push(p.destination);
                if (p.price_from != null && p.currency_symbol) parts.push('Dès ' + p.price_from + ' ' + p.currency_symbol);
                if (parts.length) info.el.title = parts.join(' · ');
            }
        });

        calendar.render();

        document.getElementById('btn-apply-filters').addEventListener('click', function () {
            var params = getFilterParams();
            var qs = new URLSearchParams(params).toString();
            var base = '{{ route('admin.reservations.calendrier') }}';
            window.location.href = qs ? base + '?' + qs : base;
        });

        document.getElementById('btn-modal-print').addEventListener('click', function () {
            var content = document.getElementById('event-detail-content');
            if (!content || !content.innerHTML) return;
            var title = (content.dataset && content.dataset.printTitle) ? content.dataset.printTitle : 'Offre';
            printArea.innerHTML = '<div style="padding: 20px; font-family: sans-serif;">' +
                '<h2>' + title + '</h2>' +
                content.innerHTML + '</div>';
            printArea.style.display = 'block';
            window.print();
            printArea.style.display = 'none';
            printArea.innerHTML = '';
        });
    });
    </script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

@push('css')
<style>
    @media print {
        body * { visibility: hidden; }
        #event-print-area, #event-print-area * { visibility: visible; }
        #event-print-area { position: absolute; left: 0; top: 0; width: 100%; display: block !important; }
    }
</style>
@endpush
