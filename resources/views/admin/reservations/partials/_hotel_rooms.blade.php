@php
    $hotelsRoomsUrl = $hotelsRoomsUrl ?? route('admin.reservations.hotels-rooms');
    $voyageDeparturesUrl = $voyageDeparturesUrl ?? route('admin.reservations.voyage-departures');
    $departureHotelsRoomsUrl = $departureHotelsRoomsUrl ?? route('admin.reservations.departure-hotels-rooms');
    $tourHotelsWithRooms = $tourHotelsWithRooms ?? collect();
    $reservation = $reservation ?? null;
    $selectedTravelDate = $selectedTravelDate ?? null;

    $initialDepartureRoomCounts = [];
    if ($reservation) {
        $initialDepartureRoomCounts = $reservation->reservationRooms
            ->whereNotNull('departure_hotel_room_id')
            ->mapWithKeys(fn ($r) => [(string) $r->departure_hotel_room_id => (int) $r->room_count])
            ->all();
    }

    $initialTravelDateId = old(
        'travel_date_id',
        $reservation?->travel_date_id ?? ($selectedTravelDate?->id ?? '')
    );
    $selectedDepartureId = $selectedDepartureId ?? null;
    $initialDepartureId = old('departure_id', $reservation?->departure_id ?? $selectedDepartureId ?? '');

    $legacyEdit = $reservation && ! $reservation->departure_id && $tourHotelsWithRooms->isNotEmpty();
    $reservationRoomsByKey = $reservation
        ? $reservation->reservationRooms->keyBy(fn ($r) => $r->tour_hotel_id . '_' . $r->tour_hotel_room_id)
        : collect();
@endphp
<div class="card mb-3 border" id="reservation-hotel-card">
    <div class="card-body">
        <h6 class="card-title mb-3 text-secondary"><i class="bx bx-hotel me-1"></i>Hôtel et chambres</h6>

        @if(! $legacyEdit)
            <p class="text-muted small mb-3">
                Sélectionnez le <strong>départ</strong> du voyage : le stock chambres et places est celui configuré pour ce départ (pas le stock global du catalogue).
            </p>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="reservation-departure-select">Départ <span class="text-danger">*</span></label>
                    <select class="form-select" id="reservation-departure-select">
                        <option value="">— Choisir un départ —</option>
                    </select>
                    <input type="hidden" name="departure_id" id="input-departure-id" value="{{ $initialDepartureId }}">
                    <input type="hidden" name="travel_date_id" id="input-travel-date-id" value="{{ $initialTravelDateId }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prix de base voyage (DH)</label>
                    <input type="number" name="base_price" class="form-control" value="{{ old('base_price', $reservation?->base_price ?? '') }}" min="0" step="0.01" placeholder="Optionnel">
                </div>
            </div>
        @else
            <div class="alert alert-warning py-2 small mb-3">
                <i class="bx bx-info-circle me-1"></i>
                Cette réservation utilise encore l’ancien mode (chambres catalogue). Enregistrez après avoir créé des départs sur le voyage pour basculer vers le stock par départ.
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Prix de base voyage (DH)</label>
                    <input type="number" name="base_price" class="form-control" value="{{ old('base_price', $reservation?->base_price ?? '') }}" min="0" step="0.01" placeholder="Optionnel">
                </div>
            </div>
        @endif

        <div id="reservation-travelers-summary" class="alert alert-light border mb-3 py-2">
            <strong>Total voyageurs :</strong> <span id="reservation-total-travelers">1</span> (voyageur principal + accompagnants)
        </div>

        <div id="reservation-hotel-rooms-container">
            @if($legacyEdit)
                @php $roomFieldIndex = 0; @endphp
                @foreach($tourHotelsWithRooms as $hotel)
                    <div class="card mb-2 reservation-hotel-block" data-tour-hotel-id="{{ $hotel->id }}">
                        <div class="card-header bg-light py-2">
                            <strong>{{ $hotel->hotel_name ?: 'Hôtel J' . ($hotel->check_in_day ?? '?') }}</strong>
                            @if($hotel->check_in_day || $hotel->check_out_day)
                                <span class="text-muted small">— J{{ $hotel->check_in_day ?? '?' }} à J{{ $hotel->check_out_day ?? '?' }}</span>
                            @endif
                        </div>
                        <div class="card-body py-2">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Type de chambre</th>
                                            <th class="text-center">Capacité</th>
                                            <th class="text-center">Supplément unitaire</th>
                                            <th class="text-center">Nombre de chambres</th>
                                            <th class="text-end">Supplément total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($hotel->rooms->where('is_active', true) as $room)
                                            @php
                                                $key = $hotel->id . '_' . $room->id;
                                                $selected = $reservationRoomsByKey->get($key);
                                                $roomCount = $selected ? (int) $selected->room_count : 0;
                                                $idx = $roomFieldIndex++;
                                            @endphp
                                            <tr class="reservation-room-row" data-capacity="{{ $room->capacity_total }}" data-supplement="{{ $room->supplement }}">
                                                <td>
                                                    {{ $room->room_type }}{{ $room->room_label ? ' — ' . $room->room_label : '' }}
                                                    @if((float) $room->supplement == 0)<span class="badge bg-success ms-1">Standard</span>@endif
                                                </td>
                                                <td class="text-center">{{ $room->capacity_total }} pers.</td>
                                                <td class="text-center">{{ number_format((float) $room->supplement, 0, ',', ' ') }} DH</td>
                                                <td class="text-center">
                                                    <input type="hidden" name="hotel_rooms[{{ $idx }}][tour_hotel_id]" value="{{ $hotel->id }}">
                                                    <input type="hidden" name="hotel_rooms[{{ $idx }}][tour_hotel_room_id]" value="{{ $room->id }}">
                                                    <input type="number"
                                                           name="hotel_rooms[{{ $idx }}][room_count]"
                                                           class="form-control form-control-sm text-center reservation-room-count"
                                                           value="{{ $roomCount }}"
                                                           min="0"
                                                           readonly
                                                           data-room-supplement="{{ $room->supplement }}"
                                                           data-room-capacity="{{ $room->capacity_total }}">
                                                </td>
                                                <td class="text-end reservation-room-total">@if($roomCount > 0){{ number_format($roomCount * (float) $room->supplement, 0, ',', ' ') }} DH@else—@endif</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if($tourHotelsWithRooms->isEmpty())
                    <p class="text-muted mb-0">Aucun hôtel configuré pour ce voyage.</p>
                @endif
            @else
                <p class="text-muted mb-0" id="reservation-hotel-placeholder">Sélectionnez un voyage puis un départ pour charger les chambres (stock départ).</p>
            @endif
        </div>

        <div id="reservation-hotel-summary" class="mt-3 {{ $legacyEdit && $tourHotelsWithRooms->isNotEmpty() ? '' : 'd-none' }}">
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <strong>Capacité totale chambres :</strong> <span id="reservation-total-capacity">0</span> pers.
                </div>
                <div class="col-md-4">
                    <strong>Supplément chambres total :</strong> <span id="reservation-total-supplement">0</span> DH
                </div>
                <div class="col-md-4">
                    <strong>Prix total (base + suppléments) :</strong> <span id="reservation-grand-total">—</span>
                </div>
            </div>
            <div id="reservation-capacity-error" class="alert alert-danger mt-2 d-none" role="alert">
                La capacité sur ce départ est insuffisante pour le nombre de voyageurs.
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="reservation-initial-departure-rooms">{!! json_encode($initialDepartureRoomCounts) !!}</script>

<script>
(function() {
    var legacyEdit = @json($legacyEdit);
    var hotelsRoomsUrl = @json($hotelsRoomsUrl);
    var voyageDeparturesUrl = @json($voyageDeparturesUrl);
    var departureHotelsRoomsUrl = @json($departureHotelsRoomsUrl);
    var initialDepartureRooms = {};
    try {
        initialDepartureRooms = JSON.parse(document.getElementById('reservation-initial-departure-rooms').textContent || '{}') || {};
    } catch (e) { initialDepartureRooms = {}; }

    var tourIdSelect = document.querySelector('select[name="tour_id"]');
    var container = document.getElementById('reservation-hotel-rooms-container');
    var placeholder = document.getElementById('reservation-hotel-placeholder');
    var summaryBlock = document.getElementById('reservation-hotel-summary');
    var departureSelect = document.getElementById('reservation-departure-select');
    var inputDepartureId = document.getElementById('input-departure-id');
    var inputTravelDateId = document.getElementById('input-travel-date-id');
    var initialDepartureId = inputDepartureId ? String(inputDepartureId.value || '') : '';
    var initialTravelDateHidden = inputTravelDateId ? String(inputTravelDateId.value || '') : '';

    function countTravelers() {
        var c = 1;
        var compContainer = document.getElementById('companions-container');
        if (compContainer) {
            compContainer.querySelectorAll('.companion-row').forEach(function(row) {
                var fn = (row.querySelector('input[name*="[first_name]"]') || {}).value || '';
                var ln = (row.querySelector('input[name*="[last_name]"]') || {}).value || '';
                if (String(fn).trim() !== '' || String(ln).trim() !== '') c++;
            });
        }
        return c;
    }

    function updateTravelersSummary() {
        var el = document.getElementById('reservation-total-travelers');
        if (el) el.textContent = countTravelers();
        updateSummary();
    }

    function updateSummary() {
        var capEl = document.getElementById('reservation-total-capacity');
        var supEl = document.getElementById('reservation-total-supplement');
        var gtEl = document.getElementById('reservation-grand-total');
        if (capEl) capEl.textContent = '—';
        if (supEl) supEl.textContent = '—';

        var basePrice = parseFloat(document.querySelector('input[name="base_price"]')?.value || '0') || 0;
        var extrasTotal = 0;
        if (typeof window.reservationCreateGetExtrasTotal === 'function') {
            extrasTotal = Number(window.reservationCreateGetExtrasTotal() || 0);
        }
        if (gtEl) {
            var total = basePrice + extrasTotal;
            gtEl.textContent = total > 0 ? total.toLocaleString('fr-FR', {maximumFractionDigits: 0}) + ' DH' : '—';
        }

        var errEl = document.getElementById('reservation-capacity-error');
        if (errEl) errEl.classList.add('d-none');

        if (summaryBlock && document.querySelector('.reservation-hotel-block')) summaryBlock.classList.remove('d-none');
    }
    window.reservationCreateRecomputeTotals = updateSummary;

    document.querySelector('input[name="base_price"]')?.addEventListener('input', updateSummary);

    var companionsContainer = document.getElementById('companions-container');
    if (companionsContainer) {
        companionsContainer.addEventListener('input', function() { updateTravelersSummary(); });
        companionsContainer.addEventListener('change', function() { updateTravelersSummary(); });
    }
    setInterval(function() { updateTravelersSummary(); }, 2000);

    function renderDepartureRooms(data) {
        var html = '';
        var idx = 0;
        var currency = (data && data.currency) ? data.currency : 'DH';
        var hotels = data.hotels || [];
        hotels.forEach(function(h) {
            html += '<div class="card mb-2 reservation-hotel-block" data-departure-hotel-id="' + h.departure_hotel_id + '"><div class="card-header bg-light py-2"><strong>' + (h.hotel_name || 'Hôtel') + '</strong></div><div class="card-body py-2"><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Type</th><th class="text-center">Places dispo</th><th class="text-center">Chambres dispo</th><th class="text-center">Cap.</th><th class="text-center">Suppl. unit.</th><th class="text-center">Nb chambres</th><th class="text-end">Suppl. total</th></tr></thead><tbody>';
            (h.rooms || []).forEach(function(room) {
                var dhrId = room.departure_hotel_room_id == null ? '' : String(room.departure_hotel_room_id);
                var cnt = initialDepartureRooms[dhrId] != null ? parseInt(initialDepartureRooms[dhrId], 10) : 0;
                if (isNaN(cnt)) cnt = 0;
                var supUnit = parseFloat(room.supplement) || 0;
                var supTot = cnt * supUnit;
                html += '<tr class="reservation-room-row" data-departure-hotel-room-id="' + dhrId + '"><td>' + (room.room_type || '') + '</td>';
                html += '<td class="text-center">' + room.available_places + '</td><td class="text-center">' + room.available_rooms + '</td>';
                html += '<td class="text-center">' + room.capacity_total + '</td><td class="text-center">' + supUnit + ' ' + currency + '</td>';
                html += '<td class="text-center"><input type="hidden" name="hotel_rooms[' + idx + '][departure_hotel_room_id]" value="' + dhrId + '">';
                html += '<input type="number" name="hotel_rooms[' + idx + '][room_count]" class="form-control form-control-sm text-center reservation-room-count" value="' + cnt + '" min="0" max="' + (room.available_rooms || 999) + '" data-room-supplement="' + supUnit + '" data-room-capacity="' + (room.capacity_total || 0) + '"></td>';
                html += '<td class="text-end reservation-room-total">' + (cnt > 0 ? supTot.toLocaleString('fr-FR', {maximumFractionDigits: 0}) + ' DH' : '—') + '</td></tr>';
                idx++;
            });
            html += '</tbody></table></div></div></div>';
        });
        if (!html) html = '<p class="text-muted mb-0">Aucune chambre configurée pour ce départ. Gérez les hôtels et chambres sur la fiche départ.</p>';
        if (container) container.innerHTML = html;
        if (placeholder) placeholder.style.display = 'none';
        container.querySelectorAll('.reservation-room-count').forEach(function(inp) {
            inp.addEventListener('input', function() {
                var tr = inp.closest('tr');
                var c = parseInt(inp.value, 10) || 0;
                var su = parseFloat(inp.getAttribute('data-room-supplement')) || 0;
                var tot = tr.querySelector('.reservation-room-total');
                if (tot) tot.textContent = c > 0 ? (c * su).toLocaleString('fr-FR', {maximumFractionDigits: 0}) + ' DH' : '—';
            });
        });
        if (summaryBlock && html.indexOf('reservation-hotel-block') !== -1) summaryBlock.classList.remove('d-none');
        updateSummary();
    }

    function loadDepartureRooms(depId) {
        if (!container || !depId) return;
        container.innerHTML = '<p class="text-muted mb-0">Chargement des chambres…</p>';
        fetch(departureHotelsRoomsUrl + '?departure_id=' + encodeURIComponent(depId))
            .then(function(r) { return r.json(); })
            .then(renderDepartureRooms)
            .catch(function() {
                if (container) container.innerHTML = '<p class="text-danger mb-0">Erreur de chargement des chambres.</p>';
            });
    }

    function loadDeparturesForTour(tourId, thenSelectDepartureId) {
        if (!departureSelect) return;
        departureSelect.innerHTML = '<option value="">— Choisir un départ —</option>';
        if (!tourId) {
            if (inputDepartureId) inputDepartureId.value = '';
            if (inputTravelDateId) inputTravelDateId.value = '';
            if (container) container.innerHTML = '<p class="text-muted mb-0" id="reservation-hotel-placeholder">Sélectionnez un voyage puis un départ.</p>';
            if (summaryBlock) summaryBlock.classList.add('d-none');
            return;
        }
        fetch(voyageDeparturesUrl + '?tour_id=' + tourId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var list = data.departures || [];
                list.forEach(function(d) {
                    var opt = document.createElement('option');
                    opt.value = d.id;
                    opt.setAttribute('data-wp-travel-date-id', d.wp_travel_date_id || '');
                    opt.textContent = d.label + ' · ' + (d.available_capacity != null ? d.available_capacity + ' pl.' : '');
                    departureSelect.appendChild(opt);
                });
                var pick = thenSelectDepartureId || initialDepartureId;
                if (!pick && initialTravelDateHidden) {
                    for (var i = 0; i < departureSelect.options.length; i++) {
                        var o = departureSelect.options[i];
                        if (String(o.getAttribute('data-wp-travel-date-id') || '') === String(initialTravelDateHidden)) {
                            pick = o.value;
                            break;
                        }
                    }
                }
                if (pick) {
                    departureSelect.value = pick;
                    syncDepartureHidden();
                    loadDepartureRooms(pick);
                }
            })
            .catch(function() {
                departureSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }

    function syncDepartureHidden() {
        if (!departureSelect || !inputDepartureId) return;
        var opt = departureSelect.options[departureSelect.selectedIndex];
        var depId = departureSelect.value || '';
        inputDepartureId.value = depId;
        var wpTd = opt ? opt.getAttribute('data-wp-travel-date-id') : '';
        if (inputTravelDateId && wpTd) inputTravelDateId.value = wpTd;
    }

    if (!legacyEdit && tourIdSelect && departureSelect && container) {
        tourIdSelect.addEventListener('change', function() {
            var tid = parseInt(this.value, 10);
            initialDepartureId = '';
            initialTravelDateHidden = '';
            loadDeparturesForTour(tid, null);
        });
        departureSelect.addEventListener('change', function() {
            syncDepartureHidden();
            var depId = parseInt(this.value, 10);
            if (depId) loadDepartureRooms(depId);
            else {
                container.innerHTML = '<p class="text-muted mb-0" id="reservation-hotel-placeholder">Choisissez un départ.</p>';
                if (summaryBlock) summaryBlock.classList.add('d-none');
            }
        });
        if (tourIdSelect.value && parseInt(tourIdSelect.value, 10) > 0) {
            loadDeparturesForTour(parseInt(tourIdSelect.value, 10), initialDepartureId);
        }
    } else if (legacyEdit) {
        updateSummary();
        updateTravelersSummary();
    } else {
        updateSummary();
        updateTravelersSummary();
    }
})();
</script>
