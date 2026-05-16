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
    $compactAvailabilityOnly = (bool) ($compactAvailabilityOnly ?? false);

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
    $roomsDebugEnabled = config('app.debug') || request()->boolean('debug');

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
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0" for="reservation-departure-select">Départ <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-toggle-departure">Modifier</button>
                    </div>
                    <select class="form-select" id="reservation-departure-select" disabled>
                        <option value="">— Choisir un départ —</option>
                    </select>
                    <input type="hidden" name="departure_id" id="input-departure-id" value="{{ $initialDepartureId }}">
                    <input type="hidden" name="travel_date_id" id="input-travel-date-id" value="{{ $initialTravelDateId }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="reservation-base-price">Prix unitaire par voyageur (DH)</label>
                    <input type="number" id="reservation-base-price" name="base_price" class="form-control" value="{{ old('base_price', $reservation?->base_price ?? '') }}" min="0" step="0.01" placeholder="0.00" readonly>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="reservation-discount-value">Réduction</label>
                    <div class="input-group">
                        <input type="number" id="reservation-discount-value" name="discount_value" class="form-control" value="{{ old('discount_value', $reservation?->discount_value ?? '') }}" min="0" step="0.01" placeholder="0">
                        <select id="reservation-discount-type" name="discount_type" class="form-select" style="max-width: 96px;">
                            <option value="percentage" @selected(old('discount_type', $reservation?->discount_type ?? 'percentage') === 'percentage')>%</option>
                            <option value="fixed" @selected(old('discount_type', $reservation?->discount_type ?? '') === 'fixed')>DH</option>
                        </select>
                    </div>
                    <div class="form-text">Prix après réduction : <strong id="reservation-price-after-discount">—</strong></div>
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
                    <input type="number" id="reservation-base-price" name="base_price" class="form-control" value="{{ old('base_price', $reservation?->base_price ?? '') }}" min="0" step="0.01" placeholder="0.00" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="reservation-discount-value">Réduction</label>
                    <div class="input-group">
                        <input type="number" id="reservation-discount-value" name="discount_value" class="form-control" value="{{ old('discount_value', $reservation?->discount_value ?? '') }}" min="0" step="0.01" placeholder="0">
                        <select id="reservation-discount-type" name="discount_type" class="form-select" style="max-width: 96px;">
                            <option value="percentage" @selected(old('discount_type', $reservation?->discount_type ?? 'percentage') === 'percentage')>%</option>
                            <option value="fixed" @selected(old('discount_type', $reservation?->discount_type ?? '') === 'fixed')>DH</option>
                        </select>
                    </div>
                    <div class="form-text">Prix après réduction : <strong id="reservation-price-after-discount">—</strong></div>
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
        @if($roomsDebugEnabled && ! $compactAvailabilityOnly)
            <div id="reservation-rooms-debug-panel" class="alert alert-secondary small mt-3 mb-0">
                <strong>Debug chambres</strong>
                <div id="reservation-rooms-debug-content" class="mt-2">Waiting for request...</div>
            </div>
        @endif

        <div id="reservation-hotel-summary" class="mt-3 {{ $legacyEdit && $tourHotelsWithRooms->isNotEmpty() ? '' : 'd-none' }}">
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <strong id="reservation-capacity-label">Capacité chambres sélectionnées :</strong> <span id="reservation-total-capacity">0</span> pers.
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
    var compactAvailabilityOnly = @json($compactAvailabilityOnly);
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
    var roomsDebugEnabled = @json($roomsDebugEnabled);
    var roomsDebugPanel = document.getElementById('reservation-rooms-debug-content');
    var roomsDebugRequestSeq = 0;
    if (compactAvailabilityOnly) {
        var cardTitle = document.querySelector('#reservation-hotel-card .card-title');
        if (cardTitle) {
            cardTitle.textContent = 'Disponibilites du depart';
        }
    }
    window.reservationState = window.reservationState || {
        selectedTourId: null,
        selectedDepartureId: null,
        selectedTravelDateId: null,
        pricing: {},
        availableRooms: [],
        travelers: [],
        roomAllocations: []
    };

    var tourPreviousValue = tourSelect ? tourSelect.value : '';
    var departurePreviousValue = departureSelect ? departureSelect.value : '';
    var tourHidden = document.getElementById('tour_id_hidden');
    if (tourSelect && tourHidden) {
        tourHidden.value = tourSelect.value || '';
    }

    function hasDownstreamData() {
        var companionsContainer = document.getElementById('companions-container');
        var hasCompanionData = false;
        if (companionsContainer) {
            var companionRows = companionsContainer.querySelectorAll('.companion-row');
            for (var i = 0; i < companionRows.length; i++) {
                var first = companionRows[i].querySelector('input[name*="[first_name]"]');
                var last = companionRows[i].querySelector('input[name*="[last_name]"]');
                if ((first && String(first.value || '').trim() !== '') || (last && String(last.value || '').trim() !== '')) {
                    hasCompanionData = true;
                    break;
                }
            }
        }
        var hasRooming = window.reservationState && Array.isArray(window.reservationState.roomAllocations) && window.reservationState.roomAllocations.length > 0;
        var hasLegacyRooms = false;
        document.querySelectorAll('.reservation-room-count').forEach(function (input) {
            if ((parseInt(input.value || '0', 10) || 0) > 0) {
                hasLegacyRooms = true;
            }
        });
        var paymentAmount = parseFloat(document.getElementById('payment_amount') && document.getElementById('payment_amount').value || '0') || 0;
        var extrasTotal = typeof window.reservationCreateGetExtrasTotal === 'function' ? window.reservationCreateGetExtrasTotal() : 0;
        return hasCompanionData || hasRooming || hasLegacyRooms || paymentAmount > 0 || extrasTotal > 0;
    }

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

    function flattenRoomIds(rooms) {
        var ids = [];
        if (!Array.isArray(rooms)) {
            return ids;
        }

        rooms.forEach(function (hotel) {
            var hotelRooms = hotel && Array.isArray(hotel.rooms) ? hotel.rooms : [];
            hotelRooms.forEach(function (room) {
                var id = room && (room.departure_hotel_room_id || room.tour_hotel_room_id || room.id || null);
                if (id !== null && id !== undefined && id !== '') {
                    ids.push(String(id));
                }
            });
        });

        return ids;
    }

    function updateRoomsDebugPanel(data) {
        if (!roomsDebugEnabled || !roomsDebugPanel) {
            return;
        }

        var mode = data && data.mode ? String(data.mode) : 'unknown';
        var endpoint = data && data.url ? String(data.url) : '-';
        var rooms = data && Array.isArray(data.rooms) ? data.rooms : [];
        var ids = flattenRoomIds(rooms);
        var source = data && data.source ? String(data.source) : 'unknown';
        var lines = [
            'Endpoint called: ' + endpoint,
            'Mode received: ' + mode,
            'Rooms groups received: ' + rooms.length,
            'Room IDs received: ' + (ids.length ? ids.join(', ') : '-'),
            'Source: ' + source,
            'Timestamp: ' + (data && data.timestamp ? String(data.timestamp) : new Date().toISOString())
        ];

        roomsDebugPanel.innerHTML = lines.map(function (line) {
            return '<div>' + escapeHtml(line) + '</div>';
        }).join('');
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

    function getRoomMode() {
        var container = document.getElementById('reservation-hotel-rooms-container');
        return container ? (container.getAttribute('data-room-mode') || 'rooms') : 'rooms';
    }

    function syncSummary() {
        var travelers = travelerCount();
        var baseUnitPrice = discountedUnitPrice();
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

        var roomMode = getRoomMode();
        if (travelersEl) travelersEl.textContent = String(travelers);

        if (roomMode === 'places_only') {
            var avail = 0;
            try { avail = parseInt((roomsContainer && roomsContainer.getAttribute('data-available-capacity')) || '0', 10) || 0; } catch (e) { avail = 0; }
            var capLabel = document.getElementById('reservation-capacity-label');
            if (capLabel) capLabel.textContent = 'Capacité validée par stock de places :';
            if (capacityEl) capacityEl.textContent = String(avail) + ' place' + (avail > 1 ? 's' : '');
            if (supplementEl) supplementEl.textContent = '0 DH';
            if (errorEl) errorEl.classList.add('d-none');
        } else {
            if (capacityEl) capacityEl.textContent = String(selected.capacity);
            if (supplementEl) supplementEl.textContent = selected.supplement.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' DH';
            if (errorEl) {
                errorEl.classList.toggle('d-none', selected.capacity === 0 || selected.capacity >= travelers);
            }
            var capLabel = document.getElementById('reservation-capacity-label');
            if (capLabel) capLabel.textContent = 'Capacité chambres sélectionnées :';
        }
        if (totalEl) totalEl.textContent = total.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' DH';
        var afterDiscountEl = document.getElementById('reservation-price-after-discount');
        if (afterDiscountEl) afterDiscountEl.textContent = formatMoney(baseUnitPrice);
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

    function discountedUnitPrice() {
        var unitPrice = parseNumber(basePriceInput && basePriceInput.value);
        var typeInput = document.getElementById('reservation-discount-type');
        var valueInput = document.getElementById('reservation-discount-value');
        var type = typeInput ? String(typeInput.value || 'percentage') : 'percentage';
        var value = Math.max(0, parseNumber(valueInput && valueInput.value));
        var discountAmount = 0;

        if (value > 0) {
            if (type === 'percentage') {
                value = Math.min(100, value);
                if (valueInput && parseNumber(valueInput.value) > 100) valueInput.value = '100';
                discountAmount = unitPrice * (value / 100);
            } else {
                discountAmount = Math.min(unitPrice, value);
                if (valueInput && value > unitPrice) valueInput.value = String(unitPrice.toFixed(2));
            }
        }

        return Math.max(0, unitPrice - discountAmount);
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
        console.group('[Reservation Rooms Render]');
        console.log('Timestamp:', new Date().toISOString());
        console.log('Mode reçu:', payload && payload.mode ? payload.mode : null);
        console.log('Rooms reçues:', payload && payload.rooms ? payload.rooms : null);
        console.log('Doit afficher rooms ?', !!(payload && payload.mode === 'rooms'));
        console.log('Doit afficher places_only ?', !!(payload && payload.mode === 'places_only'));
        console.groupEnd();
        if (!payload || payload.success === false) {
            updateRoomsDebugPanel({
                url: payload && payload.__debug ? payload.__debug.url : '',
                mode: payload && payload.mode ? payload.mode : 'blocked',
                rooms: payload && Array.isArray(payload.rooms) ? payload.rooms : [],
                source: payload && payload.rooms_source ? payload.rooms_source : 'unknown',
                timestamp: payload && payload.__debug ? payload.__debug.timestamp : new Date().toISOString()
            });
            // Try a graceful client-side fallback: if departure option contains capacity
            // and a fallback price exists on the trip, render a places_only card instead
            var option = departureSelect && departureSelect.options[departureSelect.selectedIndex];
            var fallbackUnit = option ? departureUnitPrice(option) : 0;
            var avail = option ? parseInt(option.getAttribute('data-available-capacity') || '0', 10) || 0 : 0;

            if (avail > 0 && fallbackUnit > 0) {
                // render places_only fallback
                roomsContainer.setAttribute('data-room-mode', 'places_only');
                roomsContainer.setAttribute('data-available-capacity', String(avail));
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

        window.reservationAvailableRooms = Array.isArray(hotels) ? hotels : [];
        window.reservationDepartureRoomsPayload = payload || {};
        window.reservationState.availableRooms = window.reservationAvailableRooms;
        window.reservationState.pricing = pricing || {};
        window.reservationState.roomsMode = payload && payload.mode ? payload.mode : null;
        window.reservationState.selectedTourId = tourSelect && tourSelect.value ? tourSelect.value : null;
        window.reservationState.selectedDepartureId = inputDepartureId && inputDepartureId.value ? inputDepartureId.value : null;
        window.reservationState.selectedTravelDateId = inputTravelDateId && inputTravelDateId.value ? inputTravelDateId.value : null;
        
        console.log('[Rooming] renderDepartureRooms - State updated:', {
            hotelsLength: Array.isArray(hotels) ? hotels.length : 'not-array',
            hotels: hotels,
            availableRooms: window.reservationState.availableRooms,
            roomsMode: window.reservationState.roomsMode,
            selectedTourId: window.reservationState.selectedTourId,
            selectedDepartureId: window.reservationState.selectedDepartureId,
            selectedTravelDateId: window.reservationState.selectedTravelDateId
        });
        
        document.dispatchEvent(new CustomEvent('reservation:rooms-loaded', { detail: { payload: payload || {}, rooms: window.reservationAvailableRooms } }));

        setAccommodationMode(payload.mode || 'rooms');
        updateRoomsDebugPanel({
            url: payload && payload.__debug ? payload.__debug.url : '',
            mode: payload && payload.mode ? payload.mode : 'unknown',
            rooms: Array.isArray(hotels) ? hotels : [],
            source: payload && payload.rooms_source ? payload.rooms_source : 'unknown',
            timestamp: payload && payload.__debug ? payload.__debug.timestamp : new Date().toISOString()
        });

        if (compactAvailabilityOnly) {
            var typeNames = [];
            hotels.forEach(function (hotel) {
                (hotel.rooms || [hotel]).forEach(function (room) {
                    var label = String(room.room_type || '').trim();
                    if (label && typeNames.indexOf(label) === -1) {
                        typeNames.push(label);
                    }
                });
            });
            var departureLabel = '';
            if (departureData && (departureData.start_date || departureData.end_date)) {
                departureLabel = (departureData.start_date || '') + (departureData.end_date ? ' -> ' + departureData.end_date : '');
            } else if (typeof window.getSelectedDepartureLabel === 'function') {
                departureLabel = window.getSelectedDepartureLabel();
            }
            roomsContainer.setAttribute('data-room-mode', payload.mode || 'rooms');
            roomsContainer.setAttribute('data-available-capacity', String(availableCapacity));
            roomsContainer.innerHTML = '' +
                '<div class="reservation-create__availability-summary">' +
                    '<div><span>Depart</span><strong>' + escapeHtml(departureLabel || '-') + '</strong></div>' +
                    '<div><span>Places restantes</span><strong>' + availableCapacity + '</strong></div>' +
                    '<div><span>Types disponibles</span><strong>' + escapeHtml(typeNames.length ? typeNames.join(', ') : 'Aucun type detaille') + '</strong></div>' +
                    '<div><span>Prix unitaire</span><strong>' + formatMoney(unitPrice) + '</strong></div>' +
                    '<p>La repartition des chambres se fait a l etape 3 apres la saisie des voyageurs.</p>' +
                '</div>';
            if (summaryBlock) {
                summaryBlock.classList.add('d-none');
            }
            syncSummary();
            if (typeof window.reservationCreateRecomputeTotals === 'function') {
                window.reservationCreateRecomputeTotals();
            }
            return;
        }

        if (payload.mode === 'rooms' && hotels.length) {
            console.info('[Reservation Rooms Render] chambres affichées', payload.rooms);
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
                            (room.tour_hotel_room_id ? '<input type="hidden" name="hotel_rooms[' + index + '][tour_hotel_room_id]" value="' + (room.tour_hotel_room_id || '') + '">' : '') +
                            '<input type="number" name="hotel_rooms[' + index + '][room_count]" class="form-control form-control-sm text-center reservation-room-count" value="' + count + '" min="0" max="' + (room.available_rooms || 0) + '" data-room-supplement="' + supplement + '" data-room-capacity="' + (room.capacity_total || 0) + '">' +
                        '</td>' +
                        '<td class="text-end reservation-room-total">' + (count > 0 ? subtotal.toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' DH' : '—') + '</td>' +
                    '</tr>';
                    index += 1;
                });

                html += '</tbody></table></div></div></div>';
            });
        } else if (payload.mode === 'places_only') {
            console.warn('[Reservation Rooms Render] places_only affiché', {
                mode: payload.mode,
                rooms: payload.rooms,
                reason: 'Aucune chambre détectée ou mode places_only reçu'
            });
            roomsContainer.setAttribute('data-room-mode', 'places_only');
            roomsContainer.setAttribute('data-available-capacity', String(availableCapacity));
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
        roomsContainer.querySelectorAll('.reservation-room-count').forEach(function (input) {
            input.disabled = true;
            input.closest('td').innerHTML = '<span class="badge bg-light text-dark">Lecture seule</span>';
        });
        syncSummary();
        if (typeof window.reservationCreateRecomputeTotals === 'function') {
            window.reservationCreateRecomputeTotals();
        }
    }

    function loadDepartureRooms(departureId, tourId, travelDateId) {
        console.log('[Rooming] loadDepartureRooms called', { departureId: departureId, tourId: tourId, travelDateId: travelDateId });
        
        // Try to get values from DOM if not provided
        if (!departureId && departureSelect && departureSelect.value) {
            departureId = departureSelect.value;
        }
        if (!departureId && inputDepartureId && inputDepartureId.value) {
            departureId = inputDepartureId.value;
        }
        // Fallback to state global if DOM elements not accessible (e.g., at step 3)
        if (!departureId && window.reservationState && window.reservationState.selectedDepartureId) {
            departureId = window.reservationState.selectedDepartureId;
        }
        
        if (!tourId) {
            var tourSelectElem = document.getElementById('select-tour-id');
            tourId = tourSelectElem && tourSelectElem.value ? tourSelectElem.value : '';
        }
        if (!tourId && window.reservationState && window.reservationState.selectedTourId) {
            tourId = window.reservationState.selectedTourId;
        }
        
        if (!travelDateId) {
            var travelDateInput = document.getElementById('input-travel-date-id');
            travelDateId = travelDateInput && travelDateInput.value ? travelDateInput.value : '';
        }
        if (!travelDateId && window.reservationState && window.reservationState.selectedTravelDateId) {
            travelDateId = window.reservationState.selectedTravelDateId;
        }
        
        if (!roomsContainer || !departureId) {
            console.warn('[Rooming] loadDepartureRooms: missing departureId or roomsContainer', {
                departureId: departureId,
                roomsContainer: !!roomsContainer
            });
            return;
        }

        var selectedOption = departureSelect && departureSelect.selectedOptions.length ? departureSelect.selectedOptions[0] : null;
        if (departureSelect && departureSelect.options && departureSelect.options.length) {
            for (var optionIndex = 0; optionIndex < departureSelect.options.length; optionIndex++) {
                if (String(departureSelect.options[optionIndex].value) === String(departureId)) {
                    selectedOption = departureSelect.options[optionIndex];
                    break;
                }
            }
        }
        var selectedDate = selectedOption && selectedOption.textContent ? selectedOption.textContent.trim() : '';
        var requestTimestamp = new Date().toISOString();
        var requestSeq = ++roomsDebugRequestSeq;
        var query = [
            'departure_id=' + encodeURIComponent(departureId),
            'tour_id=' + encodeURIComponent(tourId),
            'travel_date_id=' + encodeURIComponent(travelDateId)
        ].join('&');
        var url = departureHotelsRoomsUrl + '?' + query;

        console.log('[Rooming] Reload rooms clicked');
        console.log('[Rooming] Reload rooms payload', {
            tour_id: tourId,
            departure_id: departureId,
            travel_date_id: travelDateId
        });

        roomsContainer.innerHTML = '<p class="text-muted mb-0">Chargement des chambres…</p>';
        fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                var ct = response.headers.get('content-type') || '';
                if (!ct.includes('application/json')) throw new Error('Invalid response content-type');
                return response.json();
            })
            .then(function (response) {
                console.group('[Rooming] Reload rooms response');
                console.log('Timestamp:', requestTimestamp);
                console.log('Request #:', requestSeq);
                console.log('URL appelée:', url);
                console.log('Payload envoyé:', {
                    tour_id: tourId,
                    departure_id: departureId,
                    travel_date_id: travelDateId,
                    selectedDate: selectedDate || null
                });
                console.log('[Rooming] Reload rooms response', response);
                console.log('success:', response ? response.success : undefined);
                console.log('mode:', response ? response.mode : undefined);
                console.log('rooms:', response ? response.rooms : undefined);
                console.log('rooms count:', Array.isArray(response && response.rooms) ? response.rooms.length : 'not array');
                console.log('pricing:', response ? response.pricing : undefined);
                console.log('departure:', response ? response.departure : undefined);
                console.groupEnd();

                if (response && typeof response === 'object') {
                    response.__debug = {
                        url: url,
                        timestamp: requestTimestamp,
                        requestSeq: requestSeq,
                        selectedDate: selectedDate || null
                    };
                }

                return response;
            })
            .then(function (response) {
                console.log('[Rooming] availableRooms BEFORE renderDepartureRooms', window.reservationState.availableRooms);
                renderDepartureRooms(response);
                console.log('[Rooming] availableRooms AFTER renderDepartureRooms', window.reservationState.availableRooms);
            })
            .catch(function (error) {
                console.error('[Rooming] Reload rooms Error', {
                    error: error,
                    message: error && error.message ? error.message : undefined,
                    stack: error && error.stack ? error.stack : undefined,
                    url: url,
                    payload: {
                        tour_id: tourId,
                        departure_id: departureId,
                        travel_date_id: travelDateId
                    }
                });
                updateRoomsDebugPanel({
                    url: url,
                    mode: 'error',
                    rooms: [],
                    source: 'request_error',
                    timestamp: requestTimestamp
                });
                roomsContainer.innerHTML = '<p class="text-danger mb-0">Erreur de chargement des chambres. ' + escapeHtml(String(error && error.message ? error.message : error)) + '</p>';
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
                    option.textContent = String(departure.label || '').replace(/â†’|→/g, ' -> ') + (departure.available_capacity != null ? ' - ' + departure.available_capacity + ' pl.' : '');
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
                    window.reservationState.selectedDepartureId = String(selectedDepartureId);
                    window.reservationState.selectedTourId = String(tourId || window.reservationState.selectedTourId || '');
                    window.reservationState.selectedTravelDateId = String(travelDateValue || window.reservationState.selectedTravelDateId || '');
                    syncDepartureHidden();
                    loadDepartureRooms(selectedDepartureId, tourId, travelDateValue);
                } else if (departureSelect.options.length > 1 && departureSelect.selectedIndex > 0) {
                    syncDepartureHidden();
                    loadDepartureRooms(departureSelect.value, tourId, travelDateValue);
                }

                syncSummary();
                departurePreviousValue = departureSelect ? departureSelect.value : '';
            })
            .catch(function (error) {
                console.error('[HotelRooms] loadDeparturesForTour failed', error);
                departureSelect.innerHTML = '<option value="">— Erreur de chargement —</option>';
                var wrapper = departureSelect.closest('.col-md-6') || departureSelect.parentElement;
                if (wrapper && !wrapper.querySelector('.departure-load-error')) {
                    var errDiv = document.createElement('div');
                    errDiv.className = 'departure-load-error alert alert-danger mt-2 small mb-0';
                    errDiv.innerHTML = 'Impossible de charger les départs. Vérifiez la connexion ou réessayez.';
                    wrapper.appendChild(errDiv);
                }
            });
    }

    window.reservationReloadDeparturesForTour = function(tourId, selectedDepartureId) {
        if (typeof loadDeparturesForTour === 'function') {
            loadDeparturesForTour(tourId, selectedDepartureId);
        }
    };

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
    ['reservation-discount-value', 'reservation-discount-type'].forEach(function (id) {
        var discountEl = document.getElementById(id);
        if (!discountEl) return;
        discountEl.addEventListener(id === 'reservation-discount-type' ? 'change' : 'input', function () {
            syncSummary();
            if (typeof window.reservationCreateRecomputeTotals === 'function') {
                window.reservationCreateRecomputeTotals();
            }
        });
    });

    if (departureSelect) {
        departureSelect.addEventListener('change', function () {
            if (departureSelect.disabled) {
                return;
            }
            var newValue = this.value;
            if (newValue === departurePreviousValue) {
                syncDepartureHidden();
                return;
            }
            if (hasDownstreamData()) {
                if (!confirm('Changer la date de départ peut modifier les chambres, prix et disponibilités. Continuer ?')) {
                    this.value = departurePreviousValue;
                    syncDepartureHidden();
                    return;
                }
            }
            departurePreviousValue = newValue;
            syncDepartureHidden();
            if (typeof window.resetReservationDownstream === 'function') {
                window.resetReservationDownstream({ tourChanged: false });
            }
            if (newValue) {
                window.reservationState.selectedDepartureId = newValue;
                loadDepartureRooms(newValue, window.reservationState.selectedTourId || null, window.reservationState.selectedTravelDateId || null);
            } else if (roomsContainer) {
                roomsContainer.innerHTML = '<p class="text-muted mb-0">Choisissez un départ.</p>';
                if (summaryBlock) summaryBlock.classList.add('d-none');
            }
        });
    }

    if (tourSelect && !legacyEdit) {
        tourSelect.addEventListener('change', function () {
            if (tourSelect.disabled) {
                return;
            }
            var newValue = this.value;
            if (newValue === tourPreviousValue) {
                if (tourHidden) tourHidden.value = newValue;
                return;
            }
            if (hasDownstreamData()) {
                if (!confirm('Changer le voyage va réinitialiser les voyageurs, chambres, extras et paiement déjà saisis. Continuer ?')) {
                    this.value = tourPreviousValue;
                    if (tourHidden) tourHidden.value = tourPreviousValue;
                    return;
                }
            }
            tourPreviousValue = newValue;
            if (tourHidden) tourHidden.value = newValue;
            if (typeof window.resetReservationDownstream === 'function') {
                window.resetReservationDownstream({ tourChanged: true });
            }
            departurePreviousValue = '';
            loadDeparturesForTour(newValue, '');
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

    var btnToggleTour = document.getElementById('btn-toggle-tour');
    if (btnToggleTour && tourSelect) {
        btnToggleTour.addEventListener('click', function () {
            var isLocked = tourSelect.disabled;
            tourSelect.disabled = !isLocked;
            btnToggleTour.textContent = isLocked ? 'Verrouiller' : 'Modifier';
            btnToggleTour.classList.toggle('btn-outline-primary', !isLocked);
            btnToggleTour.classList.toggle('btn-outline-secondary', isLocked);
        });
    }

    var btnToggleDeparture = document.getElementById('btn-toggle-departure');
    if (btnToggleDeparture && departureSelect) {
        btnToggleDeparture.addEventListener('click', function () {
            var isLocked = departureSelect.disabled;
            departureSelect.disabled = !isLocked;
            btnToggleDeparture.textContent = isLocked ? 'Verrouiller' : 'Modifier';
            btnToggleDeparture.classList.toggle('btn-outline-primary', !isLocked);
            btnToggleDeparture.classList.toggle('btn-outline-secondary', isLocked);
        });
    }

    // Global wrapper function that uses state global if DOM elements not accessible
    window.reservationReloadRoomsFromState = function() {
        console.log('[Rooming Step Render]', {
            selectedTourId: window.reservationState && window.reservationState.selectedTourId,
            selectedDepartureId: window.reservationState && window.reservationState.selectedDepartureId,
            selectedTravelDateId: window.reservationState && window.reservationState.selectedTravelDateId,
            availableRooms: window.reservationState && window.reservationState.availableRooms,
            travelers: window.reservationState && window.reservationState.travelers
        });
        if (window.reservationState && window.reservationState.selectedDepartureId) {
            loadDepartureRooms(
                window.reservationState.selectedDepartureId,
                window.reservationState.selectedTourId || null,
                window.reservationState.selectedTravelDateId || null
            );
        } else {
            console.warn('[Rooming] No departure ID in state to reload');
        }
    };
    
    window.reservationCreateReloadDepartureRooms = window.reservationReloadRoomsFromState;
    window.reservationCreateRecomputeTotals = window.reservationCreateRecomputeTotals || syncSummary;
})();
</script>
