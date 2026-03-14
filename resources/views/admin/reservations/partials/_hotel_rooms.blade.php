@php
    $hotelsRoomsUrl = $hotelsRoomsUrl ?? route('admin.reservations.hotels-rooms');
    $tourHotelsWithRooms = $tourHotelsWithRooms ?? collect();
    $reservation = $reservation ?? null;
    $isEdit = $reservation && $tourHotelsWithRooms->isNotEmpty();
    $reservationRoomsByKey = $reservation
        ? $reservation->reservationRooms->keyBy(fn ($r) => $r->tour_hotel_id . '_' . $r->tour_hotel_room_id)
        : collect();
@endphp
<div class="card mb-3 border" id="reservation-hotel-card">
    <div class="card-body">
        <h6 class="card-title mb-3 text-secondary"><i class="bx bx-hotel me-1"></i>Hôtel et chambres</h6>
        <p class="text-muted small mb-3">Choisissez le type et le nombre de chambres pour chaque hôtel du voyage. La capacité totale des chambres doit couvrir le nombre de voyageurs.</p>

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <label class="form-label">Prix de base voyage (DH)</label>
                <input type="number" name="base_price" class="form-control" value="{{ old('base_price', $reservation->base_price ?? '') }}" min="0" step="0.01" placeholder="Optionnel">
            </div>
        </div>

        <div id="reservation-travelers-summary" class="alert alert-light border mb-3 py-2">
            <strong>Total voyageurs :</strong> <span id="reservation-total-travelers">1</span> (voyageur principal + accompagnants)
        </div>

        <div id="reservation-hotel-rooms-container">
            @if($isEdit)
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
                                                    @if((float)$room->supplement == 0)<span class="badge bg-success ms-1">Standard</span>@endif
                                                </td>
                                                <td class="text-center">{{ $room->capacity_total }} pers.</td>
                                                <td class="text-center">{{ number_format((float)$room->supplement, 0, ',', ' ') }} DH</td>
                                                <td class="text-center">
                                                    <input type="hidden" name="hotel_rooms[{{ $idx }}][tour_hotel_id]" value="{{ $hotel->id }}">
                                                    <input type="hidden" name="hotel_rooms[{{ $idx }}][tour_hotel_room_id]" value="{{ $room->id }}">
                                                    <input type="number" name="hotel_rooms[{{ $idx }}][room_count]" class="form-control form-control-sm text-center reservation-room-count" value="{{ $roomCount }}" min="0" data-room-supplement="{{ $room->supplement }}" data-room-capacity="{{ $room->capacity_total }}">
                                                </td>
                                                <td class="text-end reservation-room-total">@if($roomCount > 0){{ number_format($roomCount * (float)$room->supplement, 0, ',', ' ') }} DH@else—@endif</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if($tourHotelsWithRooms->isEmpty())
                    <p class="text-muted mb-0">Aucun hôtel configuré pour ce voyage. Configurez les hôtels et chambres dans l’édition du voyage.</p>
                @endif
            @else
                <p class="text-muted mb-0" id="reservation-hotel-placeholder">Sélectionnez un voyage ci-dessus pour charger les hôtels et chambres.</p>
            @endif
        </div>

        <div id="reservation-hotel-summary" class="mt-3 {{ $isEdit && $tourHotelsWithRooms->isNotEmpty() ? '' : 'd-none' }}">
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
                La capacité totale des chambres est insuffisante pour le nombre de voyageurs. Ajoutez des chambres ou des types à plus grande capacité.
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    var hotelsRoomsUrl = "{{ $hotelsRoomsUrl }}";
    var tourIdSelect = document.querySelector('select[name="tour_id"]');
    var container = document.getElementById('reservation-hotel-rooms-container');
    var placeholder = document.getElementById('reservation-hotel-placeholder');
    var summaryBlock = document.getElementById('reservation-hotel-summary');

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
        var totalCapacity = 0, totalSupplement = 0;
        document.querySelectorAll('.reservation-room-row').forEach(function(tr) {
            var count = parseInt(tr.querySelector('.reservation-room-count')?.value || '0', 10) || 0;
            var cap = parseInt(tr.getAttribute('data-capacity') || '0', 10) || 0;
            var sup = parseFloat(tr.getAttribute('data-supplement') || '0') || 0;
            totalCapacity += count * cap;
            totalSupplement += count * sup;
            var totalCell = tr.querySelector('.reservation-room-total');
            if (totalCell) totalCell.textContent = count > 0 ? (count * sup).toLocaleString('fr-FR', {maximumFractionDigits: 0}) + ' DH' : '—';
        });
        var capEl = document.getElementById('reservation-total-capacity');
        var supEl = document.getElementById('reservation-total-supplement');
        if (capEl) capEl.textContent = totalCapacity;
        if (supEl) supEl.textContent = totalSupplement.toLocaleString('fr-FR', {maximumFractionDigits: 0});
        var basePrice = parseFloat(document.querySelector('input[name="base_price"]')?.value || '0') || 0;
        var grandTotal = basePrice + totalSupplement;
        var gtEl = document.getElementById('reservation-grand-total');
        if (gtEl) gtEl.textContent = grandTotal > 0 ? grandTotal.toLocaleString('fr-FR', {maximumFractionDigits: 0}) + ' DH' : '—';
        var travelers = countTravelers();
        var errEl = document.getElementById('reservation-capacity-error');
        if (errEl) {
            if (totalCapacity > 0 && totalCapacity < travelers) errEl.classList.remove('d-none');
            else errEl.classList.add('d-none');
        }
        if (summaryBlock && document.querySelector('.reservation-hotel-block')) summaryBlock.classList.remove('d-none');
    }

    if (container) {
        container.addEventListener('input', function(e) {
            if (e.target.classList.contains('reservation-room-count')) {
                updateSummary();
            }
        });
        container.addEventListener('change', function(e) {
            if (e.target.classList.contains('reservation-room-count')) updateSummary();
        });
    }
    document.querySelector('input[name="base_price"]')?.addEventListener('input', updateSummary);

    var companionsContainer = document.getElementById('companions-container');
    if (companionsContainer) {
        companionsContainer.addEventListener('input', function() { updateTravelersSummary(); });
        companionsContainer.addEventListener('change', function() { updateTravelersSummary(); });
    }
    setInterval(function() { updateTravelersSummary(); }, 2000);

    if (tourIdSelect && placeholder) {
        tourIdSelect.addEventListener('change', function() {
            var tid = parseInt(this.value, 10);
            if (!tid) {
                container.innerHTML = '<p class="text-muted mb-0" id="reservation-hotel-placeholder">Sélectionnez un voyage ci-dessus pour charger les hôtels et chambres.</p>';
                if (summaryBlock) summaryBlock.classList.add('d-none');
                return;
            }
            placeholder.textContent = 'Chargement…';
            fetch(hotelsRoomsUrl + '?tour_id=' + tid)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var html = '';
                    var idx = 0;
                    (data.hotels || []).forEach(function(h) {
                        html += '<div class="card mb-2 reservation-hotel-block" data-tour-hotel-id="' + h.id + '"><div class="card-header bg-light py-2"><strong>' + (h.hotel_name || 'Hôtel') + '</strong></div><div class="card-body py-2"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Type de chambre</th><th class="text-center">Capacité</th><th class="text-center">Supplément unitaire</th><th class="text-center">Nombre de chambres</th><th class="text-end">Supplément total</th></tr></thead><tbody>';
                        (h.rooms || []).forEach(function(room) {
                            html += '<tr class="reservation-room-row" data-capacity="' + room.capacity_total + '" data-supplement="' + room.supplement + '"><td>' + room.room_type + (room.room_label ? ' — ' + room.room_label : '') + (room.supplement === 0 ? ' <span class="badge bg-success ms-1">Standard</span>' : '') + '</td><td class="text-center">' + room.capacity_total + ' pers.</td><td class="text-center">' + room.supplement + ' DH</td><td class="text-center"><input type="hidden" name="hotel_rooms[' + idx + '][tour_hotel_id]" value="' + h.id + '"><input type="hidden" name="hotel_rooms[' + idx + '][tour_hotel_room_id]" value="' + room.id + '"><input type="number" name="hotel_rooms[' + idx + '][room_count]" class="form-control form-control-sm text-center reservation-room-count" value="0" min="0" data-room-supplement="' + room.supplement + '" data-room-capacity="' + room.capacity_total + '"></td><td class="text-end reservation-room-total">—</td></tr>';
                            idx++;
                        });
                        html += '</tbody></table></div></div>';
                    });
                    if (!html) html = '<p class="text-muted mb-0">Aucun hôtel ou chambre configuré pour ce voyage.</p>';
                    container.innerHTML = html;
                    if (summaryBlock && html.indexOf('reservation-hotel-block') !== -1) summaryBlock.classList.remove('d-none');
                    updateSummary();
                })
                .catch(function() {
                    placeholder.textContent = 'Erreur lors du chargement. Réessayez.';
                });
        });
    } else {
        updateSummary();
        updateTravelersSummary();
    }
})();
</script>
