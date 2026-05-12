@php
    $hotelsRoomsUrl = $hotelsRoomsUrl ?? route('admin.reservations.hotels-rooms');
    $voyageDeparturesUrl = $voyageDeparturesUrl ?? route('admin.reservations.voyage-departures');
    $departureHotelsRoomsUrl = $departureHotelsRoomsUrl ?? route('admin.reservations.departure-hotels-rooms');
    $hotelsRoomsPath = parse_url($hotelsRoomsUrl, PHP_URL_PATH) ?: $hotelsRoomsUrl;
    $voyageDeparturesPath = parse_url($voyageDeparturesUrl, PHP_URL_PATH) ?: $voyageDeparturesUrl;
    $departureHotelsRoomsPath = parse_url($departureHotelsRoomsUrl, PHP_URL_PATH) ?: $departureHotelsRoomsUrl;
    $tourHotelsWithRooms = $tourHotelsWithRooms ?? collect();
    $reservation = $reservation ?? null;
    $selectedTravelDate = $selectedTravelDate ?? null;

    $initialDepartureRoomCounts = [];
    if ($reservation) {
        $initialDepartureRoomCounts = $reservation->reservationRooms
            ->whereNotNull('departure_hotel_room_id')
            ->mapWithKeys(fn ($row) => [(string) $row->departure_hotel_room_id => (int) $row->room_count])
            ->all();
    }

    $initialTravelDateId = old('travel_date_id', $reservation?->travel_date_id ?? ($selectedTravelDate?->id ?? ''));
    $selectedDepartureId = $selectedDepartureId ?? null;
    $initialDepartureId = old('departure_id', $reservation?->departure_id ?? $selectedDepartureId ?? '');

    $legacyEdit = $reservation && ! $reservation->departure_id && $tourHotelsWithRooms->isNotEmpty();
    $reservationRoomsByKey = $reservation
        ? $reservation->reservationRooms->keyBy(fn ($row) => $row->tour_hotel_id.'_'.$row->tour_hotel_room_id)
        : collect();
@endphp

<div class="card mb-3 border" id="reservation-hotel-card">
    <div class="card-body">
        <h6 class="card-title mb-3 text-secondary"><i class="bx bx-hotel me-1"></i>Hôtel et chambres</h6>

        @if(! $legacyEdit)
            <p class="text-muted small mb-3">
                Sélectionnez d'abord le <strong>départ</strong> du voyage. Le prix unitaire, le stock chambres et la capacité viennent de cette date de départ.
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
                    <label class="form-label" for="reservation-base-price">Prix unitaire par voyageur (DH)</label>
                    <input type="number" id="reservation-base-price" name="base_price" class="form-control" value="{{ old('base_price', $reservation?->base_price ?? '') }}" min="0" step="0.01" placeholder="0.00">
                </div>
            </div>
        @else
            <div class="alert alert-warning py-2 small mb-3">
                <i class="bx bx-info-circle me-1"></i>
                Cette réservation utilise encore l'ancien mode catalogue. Le recalcul financier reste actif mais le stock départ n'est pas disponible.
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="reservation-base-price">Prix unitaire par voyageur (DH)</label>
                    <input type="number" id="reservation-base-price" name="base_price" class="form-control" value="{{ old('base_price', $reservation?->base_price ?? '') }}" min="0" step="0.01" placeholder="0.00">
                </div>
            </div>
        @endif

        <div id="reservation-travelers-summary" class="alert alert-light border mb-3 py-2">
            <strong>Total voyageurs :</strong> <span id="reservation-total-travelers">1</span> (client principal inclus)
        </div>

        <div id="reservation-hotel-rooms-container">
            @if($legacyEdit)
                @php $roomFieldIndex = 0; @endphp
                @foreach($tourHotelsWithRooms as $hotel)
                    <div class="card mb-2 reservation-hotel-block" data-tour-hotel-id="{{ $hotel->id }}">
                        <div class="card-header bg-light py-2">
                            <strong>{{ $hotel->hotel_name ?: 'Hôtel J'.($hotel->check_in_day ?? '?') }}</strong>
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
                                            <th class="text-end">Sous-total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($hotel->rooms->where('is_active', true) as $room)
                                            @php
                                                $key = $hotel->id.'_'.$room->id;
                                                $selected = $reservationRoomsByKey->get($key);
                                                $roomCount = $selected ? (int) $selected->room_count : 0;
                                                $idx = $roomFieldIndex++;
                                            @endphp
                                            <tr class="reservation-room-row">
                                                <td>
                                                    {{ $room->room_type }}{{ $room->room_label ? ' — '.$room->room_label : '' }}
                                                </td>
                                                <td class="text-center">{{ $room->capacity_total }} pers.</td>
                                                <td class="text-center">{{ number_format((float) $room->supplement, 0, ',', ' ') }} DH</td>
                                                <td class="text-center">
                                                    <input type="hidden" name="hotel_rooms[{{ $idx }}][tour_hotel_id]" value="{{ $hotel->id }}">
                                                    <input type="hidden" name="hotel_rooms[{{ $idx }}][tour_hotel_room_id]" value="{{ $room->id }}">
                                                    <input
                                                        type="number"
                                                        name="hotel_rooms[{{ $idx }}][room_count]"
                                                        class="form-control form-control-sm text-center reservation-room-count"
                                                        value="{{ $roomCount }}"
                                                        min="0"
                                                        data-room-supplement="{{ $room->supplement }}"
                                                        data-room-capacity="{{ $room->capacity_total }}"
                                                    >
                                                </td>
                                                <td class="text-end reservation-room-total">{{ $roomCount > 0 ? number_format($roomCount * (float) $room->supplement, 0, ',', ' ').' DH' : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-muted mb-0" id="reservation-hotel-placeholder">Sélectionnez un voyage puis un départ pour charger les chambres du dossier.</p>
            @endif
        </div>

        <div id="reservation-hotel-summary" class="mt-3 {{ $legacyEdit && $tourHotelsWithRooms->isNotEmpty() ? '' : 'd-none' }}">
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <strong>Capacité chambres sélectionnées :</strong> <span id="reservation-total-capacity">0</span> pers.
                </div>
                <div class="col-md-4">
                    <strong>Suppléments chambres :</strong> <span id="reservation-total-supplement">0 DH</span>
                </div>
                <div class="col-md-4">
                    <strong>Total provisoire :</strong> <span id="reservation-grand-total">0 DH</span>
                </div>
            </div>
            <div id="reservation-capacity-error" class="alert alert-danger mt-2 d-none" role="alert">
                La capacité des chambres sélectionnées est insuffisante pour le nombre de voyageurs.
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="reservation-initial-departure-rooms">{!! json_encode($initialDepartureRoomCounts) !!}</script>

<script>
(function () {
    // Ensure calls to setAccommodationMode from inline templates don't fail
    // if the main reservation-create.js hasn't loaded yet. Queue calls.
    window._reservation_setAccommodationMode_pending = window._reservation_setAccommodationMode_pending || [];
    if (typeof window.setAccommodationMode !== 'function') {
        window.setAccommodationMode = function (mode) {
            window._reservation_setAccommodationMode_pending.push(mode);
        };
    }
    var legacyEdit = @json($legacyEdit);
    var voyageDeparturesUrl = @json($voyageDeparturesPath);
    var departureHotelsRoomsUrl = @json($departureHotelsRoomsPath);
    var initialRooms = {};

    try {
        initialRooms = JSON.parse(document.getElementById('reservation-initial-departure-rooms').textContent || '{}') || {};
    } catch (error) {
        initialRooms = {};
    }

    var tourSelect = document.getElementById('select-tour-id');
    var departureSelect = document.getElementById('reservation-departure-select');
    var roomsContainer = document.getElementById('reservation-hotel-rooms-container');
    var summaryBlock = document.getElementById('reservation-hotel-summary');
    var basePriceInput = document.getElementById('reservation-base-price');
    var inputDepartureId = document.getElementById('input-departure-id');
    var inputTravelDateId = document.getElementById('input-travel-date-id');

    function parseNumber(value) {
        var parsed = parseFloat(value || '0');
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Provide a safe local formatMoney fallback in case reservation-create.js
    // hasn't loaded yet and `formatMoney` is not available globally.
    function formatMoney(value) {
        if (typeof window !== 'undefined' && typeof window.formatMoney === 'function') {
            try { return window.formatMoney(value); } catch (e) { /* ignore */ }
        }
        var n = Math.round((Number(value) || 0) * 100) / 100;
        return n.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' DH';
    }

    function travelerCount() {
        var count = 1;
        var container = document.getElementById('companions-container');
        if (!container) {
            return count;
        }

        container.querySelectorAll('.companion-row').forEach(function (row) {
            var first = row.querySelector('input[name*="[first_name]"]');
            var last = row.querySelector('input[name*="[last_name]"]');
            if (String(first && first.value || '').trim() !== '' || String(last && last.value || '').trim() !== '') {
                count += 1;
            }
        });

        return count;
    }

    function updateRoomLine(input) {
        var row = input.closest('tr');
        if (!row) return;

        var count = parseInt(input.value || '0', 10) || 0;
        var max = parseInt(input.getAttribute('max') || '0', 10) || 0;
        if (max > 0 && count > max) {
            input.value = String(max);
            count = max;
        }
        if (count < 0) {
            input.value = '0';
            count = 0;
        }

        var total = count * parseNumber(input.getAttribute('data-room-supplement'));
        var target = row.querySelector('.reservation-room-total');
        if (target) {
            target.textContent = count > 0 ? total.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' DH' : '—';
        }
    }

    function selectedRoomSummary() {
        var capacity = 0;
        var supplement = 0;

        document.querySelectorAll('.reservation-room-count').forEach(function (input) {
            var count = parseInt(input.value || '0', 10) || 0;
            if (count < 1) {
                return;
            }
            capacity += count * Math.max(1, parseInt(input.getAttribute('data-room-capacity') || '0', 10));
            supplement += count * parseNumber(input.getAttribute('data-room-supplement'));
        });

        return {
            capacity: capacity,
            supplement: supplement
        };
    }

    function syncSummary() {
        var travelers = travelerCount();
        var baseUnitPrice = parseNumber(basePriceInput && basePriceInput.value);
        var selected = selectedRoomSummary();
        var extrasTotal = typeof window.reservationCreateGetExtrasTotal === 'function'
            ? parseNumber(window.reservationCreateGetExtrasTotal())
            : 0;
        var total = (baseUnitPrice * travelers) + selected.supplement + extrasTotal;

        var travelersEl = document.getElementById('reservation-total-travelers');
        var capacityEl = document.getElementById('reservation-total-capacity');
        var supplementEl = document.getElementById('reservation-total-supplement');
        var totalEl = document.getElementById('reservation-grand-total');
        var errorEl = document.getElementById('reservation-capacity-error');

        if (travelersEl) travelersEl.textContent = String(travelers);
        if (capacityEl) capacityEl.textContent = String(selected.capacity);
        if (supplementEl) supplementEl.textContent = selected.supplement.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' DH';
        if (totalEl) totalEl.textContent = total.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' DH';
        if (errorEl) {
            errorEl.classList.toggle('d-none', selected.capacity === 0 || selected.capacity >= travelers);
        }
        if (summaryBlock && document.querySelector('.reservation-hotel-block')) {
            summaryBlock.classList.remove('d-none');
        }
    }

    function getSelectedTripOption() {
        var select = document.getElementById('select-tour-id');
        return select && select.selectedOptions.length ? select.selectedOptions[0] : null;
    }

    function departureUnitPrice(option) {
        if (!option) return 0;
        var departurePrice = parseNumber(option.getAttribute('data-sale-price')) || parseNumber(option.getAttribute('data-base-price'));
        if (departurePrice > 0) {
            return departurePrice;
        }

        var tripOption = getSelectedTripOption();
        return parseNumber(tripOption && tripOption.getAttribute('data-price-from'));
    }

    function syncDepartureHidden() {
        if (!departureSelect || !inputDepartureId) {
            return;
        }

        var option = departureSelect.options[departureSelect.selectedIndex];
        inputDepartureId.value = departureSelect.value || '';
        if (inputTravelDateId) {
            inputTravelDateId.value = option ? (option.getAttribute('data-wp-travel-date-id') || '') : '';
        }
        if (basePriceInput && option && departureSelect.value) {
            basePriceInput.value = departureUnitPrice(option).toFixed(2);
        }
    }

    function renderDepartureRooms(payload) {
        if (!roomsContainer) return;
        if (!payload || payload.success === false) {
            // Try a graceful client-side fallback: if departure option contains capacity
            // and a fallback price exists on the trip, render a places_only card instead
            var option = departureSelect && departureSelect.options[departureSelect.selectedIndex];
            var fallbackUnit = option ? departureUnitPrice(option) : 0;
            var avail = option ? parseInt(option.getAttribute('data-available-capacity') || '0', 10) || 0 : 0;

            if (avail > 0 && fallbackUnit > 0) {
                // render places_only fallback
                roomsContainer.setAttribute('data-room-mode', 'places_only');
                setAccommodationMode('places_only');
                var travelers = travelerCount();
                // safe label fallback: prefer global helper, then option text, then travel date from payload
                var selectedLabel = '';
                if (typeof window.getSelectedDepartureLabel === 'function') {
                    try { selectedLabel = String(window.getSelectedDepartureLabel() || '').trim(); } catch (e) { selectedLabel = ''; }
                }
                if (!selectedLabel) {
                    var opt = departureSelect && departureSelect.options[departureSelect.selectedIndex];
                    if (opt && opt.textContent) selectedLabel = opt.textContent.trim();
                    else if (typeof payload !== 'undefined' && payload && payload.departure && payload.departure.start_date) {
                        selectedLabel = (payload.departure.start_date || '') + (payload.departure.end_date ? ' → ' + payload.departure.end_date : '');
                    }
                }

                roomsContainer.innerHTML = '' +
                    '<div class="card mb-2 reservation-hotel-block reservation-hotel-block--stock-only">' +
                        '<div class="card-body py-3"><div class="d-flex flex-column gap-2">' +
                            '<div class="alert alert-info mb-0">Stock de places disponible : ' + avail + ' place' + (avail > 1 ? 's' : '') + '. Aucune chambre détaillée configurée, réservation sur stock de places.</div>' +
                            '<div class="reservation-create__grid reservation-create__grid--two">' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Départ</label><input class="reservation-create__input" type="text" value="' + escapeHtml(selectedLabel) + '" readonly></div>' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Places restantes</label><input class="reservation-create__input" type="text" value="' + avail + '" readonly></div>' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Nombre voyageurs</label><input class="reservation-create__input" type="text" value="' + travelers + '" readonly></div>' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Prix unitaire</label><input class="reservation-create__input" type="text" value="' + formatMoney(fallbackUnit) + '" readonly></div>' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Total base</label><input class="reservation-create__input" type="text" value="' + formatMoney(fallbackUnit * travelers) + '" readonly></div>' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Supplément chambres</label><input class="reservation-create__input" type="text" value="0 DH" readonly></div>' +
                            '</div>' +
                        '</div></div></div>';
                syncSummary();
                if (typeof window.reservationCreateRecomputeTotals === 'function') {
                    window.reservationCreateRecomputeTotals();
                }
                return;
            }

            roomsContainer.setAttribute('data-room-mode', 'blocked');
            setAccommodationMode('blocked');
            roomsContainer.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml((payload && payload.message) ? payload.message : 'Erreur de chargement des chambres.') + '</div>';
            console.error('departure rooms payload error', payload);
            syncSummary();
            if (typeof window.reservationCreateRecomputeTotals === 'function') {
                window.reservationCreateRecomputeTotals();
            }
            return;
        }

        var hotels = payload && payload.rooms ? payload.rooms : (payload && payload.hotels ? payload.hotels : []);
        var currency = payload && payload.currency ? payload.currency : 'DH';
        var departureData = payload && payload.departure ? payload.departure : {};
        var availableCapacity = parseInt(departureData.available_places || departureData.available_capacity || '0', 10) || 0;
        var configureUrl = departureData.configure_url ? String(departureData.configure_url) : '';
        var pricing = payload && payload.pricing ? payload.pricing : {};
        var unitPrice = parseNumber(pricing.unit_price);
        var travelers = travelerCount();
        var html = '';
        var index = 0;

        if (basePriceInput && unitPrice > 0) {
            basePriceInput.value = unitPrice.toFixed(2);
        }

        setAccommodationMode(payload.mode || 'rooms');

        if (payload.mode === 'rooms' && hotels.length) {
            roomsContainer.setAttribute('data-room-mode', 'rooms');

            hotels.forEach(function (hotel) {
                html += '<div class="card mb-2 reservation-hotel-block">' +
                    '<div class="card-header bg-light py-2"><strong>' + (hotel.hotel_name || 'Hôtel') + '</strong></div>' +
                    '<div class="card-body py-2"><div class="table-responsive"><table class="table table-sm table-bordered mb-0">' +
                    '<thead><tr><th>Type</th><th class="text-center">Places dispo</th><th class="text-center">Chambres dispo</th><th class="text-center">Cap.</th><th class="text-center">Supplément</th><th class="text-center">Nb chambres</th><th class="text-end">Sous-total</th></tr></thead><tbody>';

                (hotel.rooms || []).forEach(function (room) {
                    var roomId = room.departure_hotel_room_id == null ? '' : String(room.departure_hotel_room_id);
                    var count = initialRooms[roomId] != null ? parseInt(initialRooms[roomId], 10) || 0 : 0;
                    var supplement = parseNumber(room.supplement);
                    var subtotal = count * supplement;

                    html += '<tr class="reservation-room-row">' +
                        '<td>' + (room.room_type || '') + '</td>' +
                        '<td class="text-center">' + (room.available_places || 0) + '</td>' +
                        '<td class="text-center">' + (room.available_rooms || 0) + '</td>' +
                        '<td class="text-center">' + (room.capacity_total || 0) + '</td>' +
                        '<td class="text-center">' + supplement.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' ' + currency + '</td>' +
                        '<td class="text-center">' +
                            '<input type="hidden" name="hotel_rooms[' + index + '][departure_hotel_room_id]" value="' + roomId + '">' +
                            '<input type="number" name="hotel_rooms[' + index + '][room_count]" class="form-control form-control-sm text-center reservation-room-count" value="' + count + '" min="0" max="' + (room.available_rooms || 0) + '" data-room-supplement="' + supplement + '" data-room-capacity="' + (room.capacity_total || 0) + '">' +
                        '</td>' +
                        '<td class="text-end reservation-room-total">' + (count > 0 ? subtotal.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' DH' : '—') + '</td>' +
                    '</tr>';
                    index += 1;
                });

                html += '</tbody></table></div></div></div>';
            });
        } else if (payload.mode === 'places_only') {
            roomsContainer.setAttribute('data-room-mode', 'places_only');
            // safe label fallback: prefer global helper, then departure JSON data, then option text
            var selectedLabel = '';
            if (typeof window.getSelectedDepartureLabel === 'function') {
                try { selectedLabel = String(window.getSelectedDepartureLabel() || '').trim(); } catch (e) { selectedLabel = ''; }
            }
            if (!selectedLabel && departureData && (departureData.start_date || departureData.end_date)) {
                selectedLabel = (departureData.start_date || '') + (departureData.end_date ? ' → ' + departureData.end_date : '');
            }
            if (!selectedLabel) {
                var opt = departureSelect && departureSelect.options[departureSelect.selectedIndex];
                if (opt && opt.textContent) selectedLabel = opt.textContent.trim();
            }

            html = '' +
                '<div class="card mb-2 reservation-hotel-block reservation-hotel-block--stock-only">' +
                    '<div class="card-body py-3">' +
                        '<div class="d-flex flex-column gap-2">' +
                            '<div class="alert alert-info mb-0">Stock de places disponible : ' + availableCapacity + ' place' + (availableCapacity > 1 ? 's' : '') + '. Aucune chambre détaillée configurée, réservation sur stock de places.</div>' +
                            '<div class="reservation-create__grid reservation-create__grid--two">' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Départ</label><input class="reservation-create__input" type="text" value="' + escapeHtml(selectedLabel) + '" readonly></div>' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Places restantes</label><input class="reservation-create__input" type="text" value="' + availableCapacity + '" readonly></div>' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Nombre voyageurs</label><input class="reservation-create__input" type="text" value="' + travelers + '" readonly></div>' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Prix unitaire</label><input class="reservation-create__input" type="text" value="' + formatMoney(unitPrice) + '" readonly></div>' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Total base</label><input class="reservation-create__input" type="text" value="' + formatMoney(unitPrice * travelers) + '" readonly></div>' +
                                '<div class="reservation-create__field"><label class="reservation-create__label">Supplément chambres</label><input class="reservation-create__input" type="text" value="0 DH" readonly></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        } else if (payload.mode === 'blocked' && payload.message) {
            roomsContainer.setAttribute('data-room-mode', 'blocked');
            setAccommodationMode('blocked');
            html = '<div class="alert alert-warning mb-0">' + escapeHtml(payload.message) + (configureUrl ? ' <a class="btn btn-sm btn-outline-primary ms-2" href="' + configureUrl + '" target="_blank" rel="noopener">Configurer les chambres</a>' : '') + '</div>';
        } else {
            roomsContainer.setAttribute('data-room-mode', 'blocked');
            setAccommodationMode('blocked');
            html = payload.message
                ? '<div class="alert alert-warning mb-0">' + escapeHtml(payload.message) + '</div>'
                : '<div class="alert alert-secondary mb-0">Ce départ n’a plus de places disponibles.</div>';
        }

        roomsContainer.innerHTML = html;
        syncSummary();
        if (typeof window.reservationCreateRecomputeTotals === 'function') {
            window.reservationCreateRecomputeTotals();
        }
    }

    function loadDepartureRooms(departureId) {
        if (!departureId && departureSelect && departureSelect.value) {
            departureId = departureSelect.value;
        }
        if (!departureId && inputDepartureId && inputDepartureId.value) {
            departureId = inputDepartureId.value;
        }
        if (!roomsContainer || !departureId) {
            return;
        }

        var tourSelect = document.getElementById('select-tour-id');
        var travelDateInput = document.getElementById('input-travel-date-id');
        var query = [
            'departure_id=' + encodeURIComponent(departureId),
            'tour_id=' + encodeURIComponent(tourSelect && tourSelect.value ? tourSelect.value : ''),
            'travel_date_id=' + encodeURIComponent(travelDateInput && travelDateInput.value ? travelDateInput.value : '')
        ].join('&');

        roomsContainer.innerHTML = '<p class="text-muted mb-0">Chargement des chambres…</p>';
        fetch(departureHotelsRoomsUrl + '?' + query, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                var ct = response.headers.get('content-type') || '';
                if (!ct.includes('application/json')) throw new Error('Invalid response content-type');
                return response.json();
            })
            .then(renderDepartureRooms)
            .catch(function (err) {
                console.error('departure rooms load error', err);
                roomsContainer.innerHTML = '<p class="text-danger mb-0">Erreur de chargement des chambres. ' + escapeHtml(String(err && err.message ? err.message : err)) + '</p>';
            });
    }

    function loadDeparturesForTour(tourId, selectedDepartureId) {
        if (!departureSelect) return;

        departureSelect.innerHTML = '<option value="">— Choisir un départ —</option>';
        if (!tourId) {
            if (roomsContainer) {
                roomsContainer.innerHTML = '<p class="text-muted mb-0" id="reservation-hotel-placeholder">Sélectionnez un voyage puis un départ pour charger les chambres du dossier.</p>';
                roomsContainer.setAttribute('data-room-mode', 'unknown');
            }
            if (summaryBlock) {
                summaryBlock.classList.add('d-none');
            }
            setAccommodationMode('rooms');
            syncSummary();
            return;
        }

        var travelDateValue = inputTravelDateId ? String(inputTravelDateId.value || '') : '';
        fetch(voyageDeparturesUrl + '?tour_id=' + encodeURIComponent(tourId), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                var ct = response.headers.get('content-type') || '';
                if (!ct.includes('application/json')) throw new Error('Invalid response content-type');
                return response.json();
            })
            .then(function (payload) {
                var departures = payload && payload.departures ? payload.departures : [];

                departures.forEach(function (departure) {
                    var option = document.createElement('option');
                    option.value = departure.id;
                    option.textContent = departure.label + (departure.available_capacity != null ? ' · ' + departure.available_capacity + ' pl.' : '');
                    option.setAttribute('data-wp-travel-date-id', departure.wp_travel_date_id || '');
                    option.setAttribute('data-base-price', departure.base_price || 0);
                    option.setAttribute('data-sale-price', departure.sale_price || 0);
                    option.setAttribute('data-available-capacity', departure.available_capacity || 0);
                    departureSelect.appendChild(option);
                });

                if (!selectedDepartureId && travelDateValue) {
                    var matchedDeparture = departures.find(function (departure) {
                        return String(departure.wp_travel_date_id || '') === travelDateValue;
                    });
                    if (matchedDeparture) {
                        selectedDepartureId = matchedDeparture.id;
                    }
                }

                if (!selectedDepartureId && departures.length === 1) {
                    selectedDepartureId = departures[0].id;
                }

                if (selectedDepartureId) {
                    departureSelect.value = String(selectedDepartureId);
                    syncDepartureHidden();
                    loadDepartureRooms(selectedDepartureId);
                } else if (departureSelect.options.length > 1 && departureSelect.selectedIndex > 0) {
                    syncDepartureHidden();
                    loadDepartureRooms(departureSelect.value);
                }

                syncSummary();
            })
            .catch(function () {
                departureSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }

    if (roomsContainer) {
        roomsContainer.addEventListener('input', function (event) {
            if (!event.target.classList.contains('reservation-room-count')) {
                return;
            }
            updateRoomLine(event.target);
            syncSummary();
            if (typeof window.reservationCreateRecomputeTotals === 'function') {
                window.reservationCreateRecomputeTotals();
            }
        });
    }

    if (basePriceInput) {
        basePriceInput.addEventListener('input', function () {
            syncSummary();
            if (typeof window.reservationCreateRecomputeTotals === 'function') {
                window.reservationCreateRecomputeTotals();
            }
        });
    }

    if (departureSelect) {
        departureSelect.addEventListener('change', function () {
            syncDepartureHidden();
            if (this.value) {
                loadDepartureRooms(this.value);
            } else if (roomsContainer) {
                roomsContainer.innerHTML = '<p class="text-muted mb-0">Choisissez un départ.</p>';
                if (summaryBlock) summaryBlock.classList.add('d-none');
            }
        });
    }

    if (tourSelect && !legacyEdit) {
        tourSelect.addEventListener('change', function () {
            loadDeparturesForTour(this.value, '');
        });

        if (tourSelect.value) {
            loadDeparturesForTour(tourSelect.value, '{{ $initialDepartureId }}');
        }
    } else {
        syncSummary();
    }

    document.addEventListener('input', function (event) {
        if (event.target && event.target.closest('#companions-container')) {
            syncSummary();
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target && event.target.closest('#companions-container')) {
            syncSummary();
        }
    });

    window.reservationCreateRecomputeTotals = window.reservationCreateRecomputeTotals || syncSummary;
})();
</script>
