(function () {
    'use strict';

    var currentStep = 1;
    var extrasMap = {};
    var roomingAllocations = [];
    var availableRoomTypes = [];
    var companionIdCounter = 0;
    window.reservationState = window.reservationState || {
        currentStep: 1,
        selectedTourId: null,
        selectedDepartureId: null,
        selectedTravelDateId: null,
        pricing: {},
        availableRooms: [],
        roomsMode: null,
        travelers: [],
        roomAllocations: [],
        extras: [],
        payment: {}
    };
    window.currentStep = currentStep;
    console.log('[Reservation Create] JS loaded');

    function parseJsonScript(id, fallback) {
        var el = document.getElementById(id);
        if (!el) return fallback;
        try {
            return JSON.parse(el.textContent || '') || fallback;
        } catch (error) {
            return fallback;
        }
    }

    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(null, args); }, ms);
        };
    }

    function allPanels() {
        return Array.prototype.slice.call(document.querySelectorAll('.reservation-create__panel[data-create-step]'));
    }

    function currentPanel() {
        return document.querySelector('.reservation-create__panel.is-active');
    }

    function parseNumber(value) {
        var parsed = parseFloat(value || '0');
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatMoney(value) {
        return (Math.round((Number(value) || 0) * 100) / 100).toLocaleString('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }) + ' DH';
    }

    function getTravelerCount() {
        return travelerRows().length;
    }

    function principalTravelerLabel() {
        var existingMode = document.getElementById('client_mode_existing');
        var hiddenId = document.getElementById('client_external_id');
        var selectedLabel = document.getElementById('client-search-selected-label');
        if (existingMode && existingMode.checked && hiddenId && hiddenId.value && selectedLabel) {
            return selectedLabel.textContent || 'Client principal';
        }

        var first = document.getElementById('client_first_name');
        var last = document.getElementById('client_last_name');
        var label = [first ? String(first.value || '').trim() : '', last ? String(last.value || '').trim() : '']
            .filter(Boolean)
            .join(' ');

        return label || 'Client principal';
    }

    function consumesBedForType(type) {
        return type !== 'infant';
    }

    function normalizeTravelerType(rawType) {
        var type = String(rawType || '').toLowerCase().trim();
        if (!type) return 'adult';
        if (['adult', 'adulte'].indexOf(type) !== -1) return 'adult';
        if (['child', 'children', 'enfant'].indexOf(type) !== -1) return 'child';
        if (['infant', 'baby', 'bebe', 'bébé'].indexOf(type) !== -1) return 'infant';
        return 'adult';
    }

    function normalizeGender(rawGender) {
        var g = String(rawGender || '').toLowerCase().trim();
        if (!g) return '';
        if (['m', 'male', 'homme', 'h'].indexOf(g) !== -1) return 'male';
        if (['f', 'female', 'femme'].indexOf(g) !== -1) return 'female';
        if (g === '1') return 'male';
        if (g === '2') return 'female';
        return g;
    }

    function travelerRows() {
        var principalType = normalizeTravelerType(document.getElementById('client_traveler_type') && document.getElementById('client_traveler_type').value || 'adult');
        var principalGender = normalizeGender(document.getElementById('client_gender') && document.getElementById('client_gender').value || '');
        var rows = [{
            id: 'main',
            label: principalTravelerLabel(),
            type: principalType,
            travelerType: principalType,
            gender: principalGender,
            relationship: 'main',
            consumesBed: consumesBedForType(principalType),
            priceType: principalType === 'child' ? 'child' : 'adult',
            isMain: true
        }];

        document.querySelectorAll('#companions-container .companion-row').forEach(function (row, index) {
            var first = row.querySelector('input[name*="[first_name]"]');
            var last = row.querySelector('input[name*="[last_name]"]');
            var typeSelect = row.querySelector('select[name*="[type]"]');
            var genderSelect = row.querySelector('select[name*="[gender]"]');
            var relationSelect = row.querySelector('select[name*="[relationship_to_main]"]');
            var travelerKeyInput = row.querySelector('input[name*="[traveler_key]"]');
            var firstName = String(first && first.value || '').trim();
            var lastName = String(last && last.value || '').trim();
            if (firstName === '' && lastName === '') {
                return;
            }

            var stableId = travelerKeyInput && travelerKeyInput.value
                ? String(travelerKeyInput.value)
                : (row.getAttribute('data-companion-id') || row.getAttribute('data-traveler-key') || ('companion_' + index));
            var type = normalizeTravelerType(typeSelect && typeSelect.value || 'adult');
            rows.push({
                id: stableId,
                label: [firstName, lastName].filter(Boolean).join(' ') || ('Accompagnant #' + (index + 1)),
                type: type,
                travelerType: type,
                gender: normalizeGender(genderSelect && genderSelect.value || ''),
                relationship: String(relationSelect && relationSelect.value || 'group'),
                consumesBed: consumesBedForType(type),
                priceType: type === 'child' ? 'child' : 'adult',
                isMain: false
            });
        });

        window.reservationState.travelers = rows;
        var travelersHidden = document.getElementById('reservation-travelers-json');
        if (travelersHidden) {
            travelersHidden.value = JSON.stringify(rows);
        }
        return rows;
    }

    function getSelectedTripLabel() {
        var select = document.getElementById('select-tour-id');
        return select && select.selectedOptions.length ? (select.selectedOptions[0].textContent || 'Aucune sÃ©lection') : 'Aucune sÃ©lection';
    }

    function getSelectedTripOption() {
        var select = document.getElementById('select-tour-id');
        return select && select.selectedOptions.length ? select.selectedOptions[0] : null;
    }

    function getSelectedTripFallbackPrice() {
        var option = getSelectedTripOption();
        return parseNumber(option && option.getAttribute('data-price-from'));
    }

    function getSelectedDepartureUnitPrice() {
        var option = getSelectedDepartureOption();
        if (!option || !option.value) {
            return 0;
        }

        var explicitUnitPrice = parseNumber(option.getAttribute('data-unit-price'));
        if (explicitUnitPrice > 0) {
            return explicitUnitPrice;
        }

        var departurePrice = parseNumber(option.getAttribute('data-sale-price')) || parseNumber(option.getAttribute('data-base-price'));
        if (departurePrice > 0) {
            return departurePrice;
        }

        var travelDatePrice = parseNumber(option.getAttribute('data-price-override'));
        if (travelDatePrice > 0) {
            return travelDatePrice;
        }

        return 0;
    }

    function getSelectedDepartureLabel() {
        var select = document.getElementById('reservation-departure-select');
        if (select && select.selectedOptions.length && select.value) {
            var fullText = select.selectedOptions[0].textContent || '—';
            return fullText.split(' - ')[0].trim();
        }
        return '—';
    }

    function getSelectedDepartureOption() {
        var select = document.getElementById('reservation-departure-select');
        return select && select.selectedOptions.length ? select.selectedOptions[0] : null;
    }

    function getAvailableDepartureCapacity() {
        var option = getSelectedDepartureOption();
        return parseInt(option && option.getAttribute('data-available-capacity') || '0', 10) || 0;
    }

    function getRoomMode() {
        var container = document.getElementById('reservation-hotel-rooms-container');
        return container && container.getAttribute('data-room-mode') ? String(container.getAttribute('data-room-mode')) : 'unknown';
    }

    function setAccommodationMode(mode) {
        var hidden = document.getElementById('reservation-accommodation-mode');
        if (hidden) {
            hidden.value = mode || 'rooms';
        }
    }

    function hotelRoomSummary() {
        var selectedRoomCount = 0;
        var selectedRoomCapacity = 0;
        var roomSupplementTotal = 0;
        var stockExceeded = false;

        document.querySelectorAll('.reservation-room-count').forEach(function (input) {
            var count = parseInt(input.value || '0', 10) || 0;
            var max = parseInt(input.getAttribute('max') || '0', 10) || 0;
            var supplement = parseNumber(input.getAttribute('data-room-supplement'));
            var capacity = parseInt(input.getAttribute('data-room-capacity') || '0', 10) || 0;

            if (max > 0 && count > max) {
                stockExceeded = true;
            }

            if (count > 0) {
                selectedRoomCount += count;
                selectedRoomCapacity += count * Math.max(capacity, 1);
                roomSupplementTotal += count * supplement;
            }
        });

        return {
            selectedRoomCount: selectedRoomCount,
            selectedRoomCapacity: selectedRoomCapacity,
            roomSupplementTotal: roomSupplementTotal,
            stockExceeded: stockExceeded
        };
    }

    function getBaseUnitPrice() {
        var input = document.querySelector('input[name="base_price"]');
        var departureUnitPrice = getSelectedDepartureUnitPrice();
        if (departureUnitPrice > 0) {
            if (input && parseNumber(input.value) !== departureUnitPrice) {
                input.value = departureUnitPrice.toFixed(2);
            }
            window.reservationState.pricing = window.reservationState.pricing || {};
            window.reservationState.pricing.unit_price = departureUnitPrice;

            return departureUnitPrice;
        }

        var fromInput = parseNumber(input && input.value);
        if (fromInput > 0) {
            return fromInput;
        }

        return getSelectedTripFallbackPrice();
    }

    function discountSummary() {
        var unitPrice = getBaseUnitPrice();
        var typeInput = document.getElementById('reservation-discount-type');
        var valueInput = document.getElementById('reservation-discount-value');
        var type = typeInput ? String(typeInput.value || 'percentage') : 'percentage';
        var value = Math.max(0, parseNumber(valueInput && valueInput.value));
        var amount = 0;

        if (value > 0) {
            if (type === 'percentage') {
                value = Math.min(100, value);
                if (valueInput && parseNumber(valueInput.value) > 100) valueInput.value = '100';
                amount = unitPrice * (value / 100);
            } else {
                amount = Math.min(unitPrice, value);
                if (valueInput && value > unitPrice) valueInput.value = String(unitPrice.toFixed(2));
            }
        }

        var after = Math.max(0, unitPrice - amount);

        return {
            unitPrice: unitPrice,
            type: type,
            value: value,
            amount: amount,
            priceAfterDiscount: after,
            label: value > 0 ? (type === 'percentage' ? value + '%' : formatMoney(value)) : 'Aucune'
        };
    }

    function derivePaymentStatus(totalAmount, paidAmount) {
        if (paidAmount <= 0) {
            return 'Non payÃ©';
        }
        if (Math.abs(paidAmount - totalAmount) < 0.01) {
            return 'PayÃ©';
        }
        if (paidAmount < totalAmount / 2) {
            return 'Acompte';
        }
        return 'PayÃ© partiellement';
    }

    function captureExtrasSelections() {
        var snapshot = {};
        document.querySelectorAll('.reservation-create__extra-card').forEach(function (card) {
            var key = String(card.getAttribute('data-extra-key') || '');
            if (!key) return;
            snapshot[key] = {
                quantity: parseInt(card.querySelector('[data-extra-quantity]') && card.querySelector('[data-extra-quantity]').value || '0', 10) || 0,
                scope: String(card.querySelector('[data-extra-scope]') && card.querySelector('[data-extra-scope]').value || 'dossier'),
                travelers: Array.prototype.slice.call(card.querySelectorAll('.reservation-create-extra-cb:checked')).map(function (cb) {
                    return String(cb.getAttribute('data-traveler-id') || '');
                })
            };
        });
        return snapshot;
    }

    function renderExtras() {
        var select = document.getElementById('select-tour-id');
        var container = document.getElementById('reservation-create-extras-container');
        var emptyState = document.getElementById('reservation-create-extras-empty');
        if (!select || !container || !emptyState) return;

        var preserved = captureExtrasSelections();
        var extras = extrasMap[String(select.value || '')] || [];
        var travelers = travelerRows();
        container.innerHTML = '';

        if (!extras.length) {
            emptyState.classList.remove('d-none');
            syncFinancialSummary();
            return;
        }

        emptyState.classList.add('d-none');

        extras.forEach(function (extra) {
            var sourceType = String(extra.source_type || extra.type || 'voyage_extra');
            var sourceId = sourceType === 'activity' ? String(extra.source_id || '') : String(extra.id || '');
            var extraKey = sourceType + ':' + sourceId;
            var snapshot = preserved[extraKey] || { quantity: 0, scope: 'dossier', travelers: [] };
            var travelerHtml = travelers.map(function (traveler) {
                var unitPrice = traveler.priceType === 'child'
                    ? parseNumber(extra.price_child)
                    : parseNumber(extra.price_adult);
                return '' +
                    '<label class="reservation-create__extra-traveler">' +
                        '<span>' +
                            '<input type="checkbox" class="reservation-create-extra-cb" data-traveler-id="' + traveler.id + '" data-traveler-type="' + traveler.type + '" data-price="' + unitPrice + '"' + (snapshot.travelers.indexOf(traveler.id) !== -1 ? ' checked' : '') + '>' +
                            '<span>' + traveler.label + '<small>' + traveler.type + '</small></span>' +
                        '</span>' +
                        '<strong class="reservation-create__extra-traveler-price">' + formatMoney(unitPrice) + '</strong>' +
                    '</label>';
            }).join('');

            var card = document.createElement('div');
            card.className = 'reservation-create__extra-card';
            card.setAttribute('data-extra-key', extraKey);
            card.setAttribute('data-extra-source-type', sourceType);
            card.setAttribute('data-extra-source-id', sourceId);
            card.setAttribute('data-extra-voyage-extra-id', sourceType === 'voyage_extra' ? String(extra.id) : '');
            card.setAttribute('data-extra-name', String(extra.name || 'Extra'));
            card.setAttribute('data-extra-description', String(extra.description || ''));
            card.setAttribute('data-extra-adult-price', String(parseNumber(extra.price_adult)));
            card.setAttribute('data-extra-child-price', String(parseNumber(extra.price_child)));
            var maxAllowed = 1;
            if (snapshot.scope === 'traveler_selection') {
                maxAllowed = snapshot.travelers.length || 1;
            } else if (snapshot.scope === 'per_traveler') {
                maxAllowed = travelers.length;
            }
            card.innerHTML =
                '<div class="reservation-create__extra-head">' +
                    '<div>' +
                        '<h4 class="reservation-create__extra-title">' + (extra.name || 'Extra') + '</h4>' +
                        ((sourceType === 'activity' || extra.extra_type === 'activity_optional') ? '<div class="reservation-create__extra-badge">Activité optionnelle</div>' : '') +
                        '<p class="reservation-create__extra-desc">' + (extra.description || 'Option supplÃ©mentaire pour ce dossier.') + '</p>' +
                    '</div>' +
                    '<div class="reservation-create__extra-price">' +
                        '<strong>' + formatMoney(parseNumber(extra.price_adult)) + '</strong>' +
                        '<span>prix unitaire adulte</span>' +
                        ((parseNumber(extra.price_child) > 0 && parseNumber(extra.price_child) !== parseNumber(extra.price_adult)) ? ('<span class="reservation-create__extra-price-child">Enfant: ' + formatMoney(parseNumber(extra.price_child)) + '</span>') : '') +
                    '</div>' +
                '</div>' +
                '<div class="reservation-create__grid reservation-create__grid--two reservation-create__extra-controls">' +
                    '<div class="reservation-create__field">' +
                        '<label class="reservation-create__label">Application</label>' +
                        '<select class="reservation-create__input" data-extra-scope>' +
                            '<option value="dossier"' + (snapshot.scope === 'dossier' ? ' selected' : '') + '>Tout le dossier</option>' +
                            '<option value="traveler_selection"' + (snapshot.scope === 'traveler_selection' ? ' selected' : '') + '>Voyageurs sÃ©lectionnÃ©s</option>' +
                        '</select>' +
                    '</div>' +
                    '<div class="reservation-create__field">' +
                        '<label class="reservation-create__label">QuantitÃ©</label>' +
                        '<input type="number" class="reservation-create__input" data-extra-quantity min="0" step="1" max="' + maxAllowed + '" value="' + Math.min(snapshot.quantity, maxAllowed) + '">' +
                    '</div>' +
                '</div>' +
                '<div class="reservation-create__extra-travelers' + (snapshot.scope === 'traveler_selection' ? '' : ' d-none') + '" data-extra-travelers>' +
                    travelerHtml +
                '</div>' +
                '<div class="reservation-create__extra-total">Total extra : <strong data-extra-total>0 DH</strong></div>';

            container.appendChild(card);
        });

        bindExtrasEvents();
        syncFinancialSummary();
    }

    function bindExtrasEvents() {
        document.querySelectorAll('.reservation-create__extra-card').forEach(function (card) {
            var scope = card.querySelector('[data-extra-scope]');
            var quantity = card.querySelector('[data-extra-quantity]');
            var travelersBlock = card.querySelector('[data-extra-travelers]');

            function refreshCard() {
                if (scope && travelersBlock) {
                    travelersBlock.classList.toggle('d-none', scope.value !== 'traveler_selection');
                }
                if (quantity) {
                    var currentScope = scope ? String(scope.value || 'dossier') : 'dossier';
                    var maxAllowed = 1;
                    if (currentScope === 'traveler_selection') {
                        maxAllowed = card.querySelectorAll('.reservation-create-extra-cb:checked').length || 1;
                    } else if (currentScope === 'per_traveler') {
                        maxAllowed = travelerRows().length;
                    }
                    quantity.setAttribute('max', maxAllowed);
                    var currentQty = parseInt(quantity.value || '0', 10) || 0;
                    if (currentQty > maxAllowed) {
                        quantity.value = maxAllowed;
                    }
                }
                var totalEl = card.querySelector('[data-extra-total]');
                if (totalEl) {
                    totalEl.textContent = formatMoney(extraCardTotal(card));
                }
                syncFinancialSummary();
            }

            if (scope) scope.addEventListener('change', refreshCard);
            if (quantity) quantity.addEventListener('input', refreshCard);
            card.querySelectorAll('.reservation-create-extra-cb').forEach(function (cb) {
                cb.addEventListener('change', refreshCard);
            });

            refreshCard();
        });
    }

    function extraCardTotal(card) {
        var quantity = Math.max(0, parseInt(card.querySelector('[data-extra-quantity]') && card.querySelector('[data-extra-quantity]').value || '0', 10) || 0);
        if (quantity < 1) {
            return 0;
        }

        var scope = String(card.querySelector('[data-extra-scope]') && card.querySelector('[data-extra-scope]').value || 'dossier');
        if (scope === 'traveler_selection') {
            return Array.prototype.slice.call(card.querySelectorAll('.reservation-create-extra-cb:checked')).reduce(function (sum, cb) {
                return sum + (parseNumber(cb.getAttribute('data-price')) * quantity);
            }, 0);
        }

        return parseNumber(card.getAttribute('data-extra-adult-price')) * quantity;
    }

    function collectExtras() {
        var selected = [];
        document.querySelectorAll('.reservation-create__extra-card').forEach(function (card) {
            var quantity = Math.max(0, parseInt(card.querySelector('[data-extra-quantity]') && card.querySelector('[data-extra-quantity]').value || '0', 10) || 0);
            if (quantity < 1) {
                return;
            }

            var scope = String(card.querySelector('[data-extra-scope]') && card.querySelector('[data-extra-scope]').value || 'dossier');
            var travelerKeys = [];
            var unitPrice = parseNumber(card.getAttribute('data-extra-adult-price'));
            var totalPrice = 0;
            var name = String(card.getAttribute('data-extra-name') || 'Extra');
            var sourceType = String(card.getAttribute('data-extra-source-type') || 'voyage_extra');
            var sourceId = parseInt(card.getAttribute('data-extra-source-id') || '0', 10) || null;
            var voyageExtraId = parseInt(card.getAttribute('data-extra-voyage-extra-id') || '0', 10) || null;

            if (scope === 'traveler_selection') {
                var checked = Array.prototype.slice.call(card.querySelectorAll('.reservation-create-extra-cb:checked'));
                if (!checked.length) {
                    return;
                }
                travelerKeys = checked.map(function (cb) { return String(cb.getAttribute('data-traveler-id') || ''); });
                totalPrice = checked.reduce(function (sum, cb) {
                    return sum + (parseNumber(cb.getAttribute('data-price')) * quantity);
                }, 0);
            } else {
                totalPrice = unitPrice * quantity;
            }

            selected.push({
                voyage_extra_id: sourceType === 'voyage_extra' ? voyageExtraId : null,
                source_type: sourceType,
                source_id: sourceType === 'activity' ? sourceId : (voyageExtraId || null),
                name: name,
                description: String(card.getAttribute('data-extra-description') || ''),
                unit_price: unitPrice,
                quantity: quantity,
                total_price: totalPrice,
                application_scope: scope,
                traveler_keys: travelerKeys
            });
        });

        var hidden = document.getElementById('reservation-create-extras-json');
        if (hidden) {
            hidden.value = JSON.stringify(selected);
        }

        return selected;
    }

    function extrasTotal() {
        return collectExtras().reduce(function (sum, item) {
            return sum + parseNumber(item.total_price);
        }, 0);
    }

    function travelerStats() {
        var rows = travelerRows();
        return rows.reduce(function (stats, traveler) {
            stats.total += 1;
            if (traveler.consumesBed) stats.beds += 1;
            if (traveler.type === 'child') stats.child += 1;
            else if (traveler.type === 'infant') stats.infant += 1;
            else stats.adult += 1;
            if (traveler.gender === 'male') stats.male += 1;
            else if (traveler.gender === 'female') stats.female += 1;
            else {
                stats.genderUnknown += 1;
                if (traveler.type === 'child' || traveler.type === 'infant') stats.genderUnknownChildren += 1;
                else stats.genderUnknownAdults += 1;
            }
            return stats;
        }, { total: 0, adult: 0, child: 0, infant: 0, male: 0, female: 0, genderUnknown: 0, genderUnknownAdults: 0, genderUnknownChildren: 0, beds: 0 });
    }

    function setStat(selector, value) {
        document.querySelectorAll(selector).forEach(function (el) { el.textContent = String(value); });
    }

    function syncTravelerStats() {
        var stats = travelerStats();
        setStat('[data-traveler-stat="total"]', stats.total);
        setStat('[data-traveler-stat="adult"]', stats.adult);
        setStat('[data-traveler-stat="child"]', stats.child);
        setStat('[data-traveler-stat="infant"]', stats.infant);
        setStat('[data-traveler-stat="male"]', stats.male);
        setStat('[data-traveler-stat="female"]', stats.female);
        setStat('[data-traveler-stat="gender_unknown"]', stats.genderUnknownAdults);
        setStat('[data-traveler-stat="gender_unknown_children"]', stats.genderUnknownChildren);
        setStat('[data-rooming-stat="total"]', stats.total);
        setStat('[data-rooming-stat="adult"]', stats.adult);
        setStat('[data-rooming-stat="child"]', stats.child);
        setStat('[data-rooming-stat="infant"]', stats.infant);
        setStat('[data-rooming-stat="male"]', stats.male);
        setStat('[data-rooming-stat="female"]', stats.female);
        setStat('[data-rooming-stat="gender_unknown"]', stats.genderUnknownAdults);
        setStat('[data-rooming-stat="gender_unknown_children"]', stats.genderUnknownChildren);
        setStat('[data-rooming-stat="beds"]', stats.beds);
    }

    function flattenAvailableRooms(groups) {
        var rows = [];
        console.log('[Rooming] flattenAvailableRooms input', { groups: groups, isArray: Array.isArray(groups) });
        
        (Array.isArray(groups) ? groups : []).forEach(function (hotel, hotelIdx) {
            console.log('[Rooming] flattenAvailableRooms processing hotel', { hotelIdx: hotelIdx, hotel: hotel });
            
            var hotelRooms = Array.isArray(hotel.rooms) ? hotel.rooms : [hotel];
            console.log('[Rooming] flattenAvailableRooms hotel rooms', { hotelRoomsLength: hotelRooms.length, hotelRooms: hotelRooms });
            
            hotelRooms.forEach(function (room, roomIdx) {
                const sourceId =
                    room.departure_hotel_room_id ||
                    room.tour_hotel_room_id ||
                    room.room_source_id ||
                    room.source_id ||
                    room.id ||
                    null;

                const sourceType =
                    room.room_source_type ||
                    (room.departure_hotel_room_id ? 'departure_hotel_room' : null) ||
                    (room.tour_hotel_room_id ? 'tour_hotel_room' : null) ||
                    room.source_type ||
                    'unknown';

                var capacity = parseInt(room.capacity || room.capacity_total || '0', 10) || 0;
                var availableRooms = parseInt(room.available_rooms || '0', 10) || 0;
                var availablePlaces = parseInt(room.available_places || '0', 10) || availableRooms * capacity;
                var unitSupplement = parseNumber(room.unit_supplement != null ? room.unit_supplement : room.supplement);
                
                console.log('[Rooming] flattenAvailableRooms room check', {
                    roomIdx: roomIdx,
                    sourceId: sourceId,
                    capacity: capacity,
                    availableRooms: availableRooms,
                    willInclude: !!(sourceId && capacity > 0 && availableRooms > 0),
                    room: room
                });
                
                if (!sourceId || capacity <= 0 || availableRooms <= 0) {
                    if (capacity > 0 && availableRooms > 0) {
                        console.error('[Rooming] Room skipped because sourceId is missing', room);
                    }
                    return;
                }
                rows.push({
                  source_id: sourceId,
                  room_source_id: sourceId,
                  room_source_type: sourceType,
                  departure_hotel_room_id: room.departure_hotel_room_id || null,
                  tour_hotel_room_id: room.tour_hotel_room_id || null,
                  room_type: room.room_type || room.type || 'Chambre',
                  hotel_name: hotel.hotel_name || hotel.name || room.hotel_name || 'HÃ´tel',
                  available_rooms: availableRooms,
                  capacity: capacity,
                  available_places: availablePlaces,
                  unit_supplement: unitSupplement
                });
            });
        });
        
        console.log('[Rooming] flattenAvailableRooms output', { rowsLength: rows.length, rows: rows });
        return rows;
    }

    function setAvailableRoomTypes(groups) {
        console.log('[Rooming] setAvailableRoomTypes called', {
            groupsArg: groups,
            groupsLength: groups ? groups.length : 0,
            windowReservationStateAvailableRooms: window.reservationState && window.reservationState.availableRooms ? window.reservationState.availableRooms : undefined
        });
        
        if ((!groups || !groups.length) && window.reservationState && Array.isArray(window.reservationState.availableRooms)) {
            groups = window.reservationState.availableRooms;
            console.log('[Rooming] setAvailableRoomTypes - Fallback to window.reservationState.availableRooms', groups);
        }
        
        availableRoomTypes = flattenAvailableRooms(groups);
        console.log('[Rooming] setAvailableRoomTypes - After flatten', {
            availableRoomTypesLength: availableRoomTypes ? availableRoomTypes.length : 0,
            availableRoomTypes: availableRoomTypes
        });

        window.availableRoomTypes = availableRoomTypes;
        
        window.reservationState.availableRooms = groups || [];
        window.reservationState.availableRoomTypes = availableRoomTypes;
        console.log('[Rooming] setAvailableRoomTypes - State updated, now calling renderAvailableRooms and renderRooming');
        
        renderAvailableRooms();
        renderRooming();
    }

    function renderAvailableRooms() {
        var target = document.getElementById('rooming-available-rooms');
        if (!target) return;
        
        console.log('[Rooming] renderAvailableRooms called', {
            availableRoomTypesLength: availableRoomTypes ? availableRoomTypes.length : 0,
            availableRoomTypes: availableRoomTypes,
            windowReservationState: window.reservationState
        });
        
        if (!availableRoomTypes.length) {
            target.innerHTML = '<div class="reservation-create__placeholder">Aucune chambre detaillee chargee pour ce depart.</div>' +
                '<button type="button" class="reservation-create__button reservation-create__button--secondary mt-2" id="btn-reload-rooms">Recharger les chambres</button>';
            return;
        }
        target.innerHTML = availableRoomTypes.map(function (room) {
            return '<div class="reservation-create__available-room">' +
                '<strong>' + room.room_type + '</strong>' +
                '<span>' + room.available_rooms + ' chambres, capacite ' + room.capacity + ', ' + formatMoney(room.unit_supplement) + '</span>' +
            '</div>';
        }).join('');
    }

    function roomingSummary() {
        var usedByType = {};
        var assigned = {};
        var supplement = 0;
        var occupiedBeds = 0;
        var partial = false;
        var invalid = false;
        var errors = [];
        var travelers = travelerRows();
        var bedTravelerIds = travelers.filter(function (t) { return t.consumesBed; }).map(function (t) { return t.id; });

        var travelerNamesById = {};
        var travelerGenderById = {};
        travelers.forEach(function (t) {
            travelerNamesById[t.id] = t.label;
            travelerGenderById[t.id] = t.gender;
        });

        roomingAllocations.forEach(function (allocation) {
            var key = String(allocation.room_source_id || allocation.room_type || '');
            usedByType[key] = (usedByType[key] || 0) + 1;
            var assignedCount = (allocation.traveler_keys || []).length;
            occupiedBeds += assignedCount;
            supplement += parseNumber(allocation.unit_supplement);
            if (assignedCount === 0) {
                invalid = true;
                errors.push('Cette chambre ne contient aucun voyageur.');
            }
            if (assignedCount > allocation.capacity) {
                invalid = true;
                errors.push('Une chambre depasse sa capacite.');
            }
            if (allocation.occupancy_mode === 'half_male') {
                var hasNonMale = (allocation.traveler_keys || []).some(function (id) {
                    return travelerGenderById[id] !== 'male';
                });
                if (hasNonMale) {
                    invalid = true;
                    errors.push('Demi-double homme incompatible : tous les voyageurs doivent etre des hommes.');
                }
            }
            if (allocation.occupancy_mode === 'half_female') {
                var hasNonFemale = (allocation.traveler_keys || []).some(function (id) {
                    return travelerGenderById[id] !== 'female';
                });
                if (hasNonFemale) {
                    invalid = true;
                    errors.push('Demi-double femme incompatible : tous les voyageurs doivent etre des femmes.');
                }
            }
            if (allocation.status === 'partial') partial = true;
            (allocation.traveler_keys || []).forEach(function (id) {
                if (assigned[id]) {
                    invalid = true;
                    errors.push('Un voyageur est affecte deux fois.');
                }
                assigned[id] = true;
            });
        });

        availableRoomTypes.forEach(function (room) {
            var key = String(room.room_source_id || room.room_type || '');
            if ((usedByType[key] || 0) > room.available_rooms) {
                invalid = true;
                errors.push('Stock depasse pour ' + room.room_type + '.');
            }
        });

        if (roomingAllocations.length > 0) {
            var missingNames = [];
            bedTravelerIds.forEach(function (id) {
                if (!assigned[id]) {
                    invalid = true;
                    missingNames.push(travelerNamesById[id] || id);
                }
            });
            if (missingNames.length) {
                errors.push('Voyageurs non affectes a une chambre : ' + missingNames.join(', ') + '.');
            }
        }

        var status = roomingAllocations.length === 0 ? 'pending' : (invalid ? 'invalid' : (partial ? 'partial' : 'complete'));
        return {
            roomSupplementTotal: supplement,
            occupiedBeds: occupiedBeds,
            status: status,
            errors: Array.from(new Set(errors))
        };
    }

    function roomTypeForCapacity(capacity, preferredType, strictPreferred) {
        var roomPool = Array.isArray(window.availableRoomTypes) && window.availableRoomTypes.length ? window.availableRoomTypes : availableRoomTypes;
        if ((!roomPool || !roomPool.length) && window.reservationState && Array.isArray(window.reservationState.availableRoomTypes)) {
            roomPool = window.reservationState.availableRoomTypes;
        }
        var rooms = roomPool.filter(function (room) {
            return room.capacity >= capacity && (!preferredType || String(room.room_type).toLowerCase().indexOf(preferredType) !== -1);
        });
        if (rooms[0]) return rooms[0];
        if (preferredType && strictPreferred) return null;
        return roomPool.filter(function (room) { return room.capacity >= capacity; })[0] || roomPool[0] || null;
    }

    function makeAllocation(room, travelers, mode) {
        var occupied = travelers.filter(function (t) { return t.consumesBed; }).length;
        return {
            local_id: 'room_' + Date.now() + '_' + Math.random().toString(16).slice(2),
            room_source_type: room.room_source_type,
            room_source_id: room.room_source_id,
            room_type: room.room_type,
            occupancy_mode: mode,
            capacity: room.capacity,
            traveler_keys: travelers.filter(function (t) { return t.consumesBed; }).map(function (t) { return t.id; }),
            occupied_count: occupied,
            status: occupied >= room.capacity || mode === 'single' || mode === 'family' || mode === 'full' ? 'complete' : 'partial',
            unit_supplement: room.unit_supplement,
            supplement_total: room.unit_supplement
        };
    }

    function autoRooming() {
        var travelers = travelerRows().filter(function (t) { return t.consumesBed; });
        var stats = travelerStats();
        var roomPool = Array.isArray(window.availableRoomTypes) && window.availableRoomTypes.length ? window.availableRoomTypes : availableRoomTypes;
        if ((!roomPool || !roomPool.length) && window.reservationState && Array.isArray(window.reservationState.availableRoomTypes)) {
            roomPool = window.reservationState.availableRoomTypes;
        }
        if (roomPool !== availableRoomTypes) {
            availableRoomTypes = roomPool || [];
        }
        window.availableRoomTypes = roomPool || [];
        console.log('[Rooming] runAutoRooming', { travelers: travelers, rooms: roomPool });
        if (!travelers.length) {
            showRoomingAlert('Ajoutez au moins un voyageur avant la repartition.');
            return;
        }
        if (!roomPool.length) {
            showRoomingAlert('Aucune chambre disponible chargee pour ce depart.');
            return;
        }
        if (stats.genderUnknownAdults > 0) {
            roomingAllocations = [];
            renderRooming();
            showRoomingAlert('Veuillez renseigner le sexe des adultes pour faire la repartition des chambres.');
            return;
        }
        var result = [];
        var used = {};
        function mark(list) { list.forEach(function (t) { used[t.id] = true; }); }
        function unused(filter) { return travelers.filter(function (t) { return !used[t.id] && (!filter || filter(t)); }); }

        var family = unused(function (t) { return t.isMain || ['spouse', 'child', 'parent'].indexOf(t.relationship) !== -1; });
        if (family.length >= 3) {
            var familyRoom = roomTypeForCapacity(family.length, 'triple') || roomTypeForCapacity(family.length, '');
            if (familyRoom) {
                result.push(makeAllocation(familyRoom, family, 'family'));
                mark(family);
            }
        }

        var spouse = unused(function (t) { return t.relationship === 'spouse'; })[0];
        var main = unused(function (t) { return t.isMain; })[0];
        if (main && spouse && main.gender && spouse.gender && main.gender !== spouse.gender) {
            var coupleRoom = roomTypeForCapacity(2, 'double');
            if (coupleRoom) {
                result.push(makeAllocation(coupleRoom, [main, spouse], 'full'));
                mark([main, spouse]);
            }
        }

        ['male', 'female'].forEach(function (gender) {
            var pool = unused(function (t) { return t.gender === gender; });
            while (pool.length >= 2) {
                var room = roomTypeForCapacity(2, 'double');
                if (!room) break;
                result.push(makeAllocation(room, pool.slice(0, 2), 'full'));
                mark(pool.slice(0, 2));
                pool = unused(function (t) { return t.gender === gender; });
            }
        });

        function isChildTraveler(t) { return t && (t.type === 'child' || t.type === 'infant'); }
        function isAdultTraveler(t) { return t && t.type === 'adult'; }

        // Pair remaining children with any remaining adult first (gender not required for children)
        (function pairChildrenWithAdults() {
            var children = unused(isChildTraveler);
            if (!children.length) return;

            children.forEach(function (child) {
                var adult = unused(isAdultTraveler)[0];
                if (!adult) return;
                var room = roomTypeForCapacity(2, 'double') || roomTypeForCapacity(2, '');
                if (!room) return;
                result.push(makeAllocation(room, [adult, child], 'full'));
                mark([adult, child]);
            });
        })();

        // Remaining travelers (adults or children)
        unused().forEach(function (traveler) {
            var double = roomTypeForCapacity(2, 'double') || roomTypeForCapacity(2, '');
            var strictSingle = roomTypeForCapacity(1, 'single', true);

            if (isAdultTraveler(traveler) && strictSingle) {
                result.push(makeAllocation(strictSingle, [traveler], 'single'));
                mark([traveler]);
                return;
            }

            if (double) {
                if (isAdultTraveler(traveler)) {
                    result.push(makeAllocation(double, [traveler], traveler.gender === 'female' ? 'half_female' : 'half_male'));
                } else {
                    // child/infant alone: never force half_male/half_female
                    result.push(makeAllocation(double, [traveler], 'full'));
                }
                mark([traveler]);
            }
        });

        roomingAllocations = result;
        window.reservationState.roomAllocations = roomingAllocations;
        renderRooming();
    }

    function showRoomingAlert(message) {
        var alerts = document.getElementById('rooming-alerts');
        if (!alerts) {
            showInlineError(message);
            return;
        }
        alerts.classList.remove('d-none');
        alerts.innerHTML = '<strong>Alertes rooming</strong><ul><li>' + message + '</li></ul>';
    }

    function addManualRoomAllocation() {
        var roomPool = Array.isArray(window.availableRoomTypes) && window.availableRoomTypes.length ? window.availableRoomTypes : availableRoomTypes;
        if ((!roomPool || !roomPool.length) && window.reservationState && Array.isArray(window.reservationState.availableRoomTypes)) {
            roomPool = window.reservationState.availableRoomTypes;
        }
        if (roomPool !== availableRoomTypes) {
            availableRoomTypes = roomPool || [];
        }
        window.availableRoomTypes = roomPool || [];
        console.log('[Rooming] Add room clicked', { rooms: roomPool });
        if (!roomPool.length) {
            showRoomingAlert('Aucune chambre disponible chargee pour ce depart.');
            return;
        }
        var room = roomPool[0];
        roomingAllocations.push(makeAllocation(room, [], room.capacity === 1 ? 'single' : 'full'));
        window.reservationState.roomAllocations = roomingAllocations;
        renderRooming();
        syncFinancialSummary();
    }

    function resetRooming() {
        console.log('[Rooming] Reset clicked');
        roomingAllocations = [];
        window.reservationState.roomAllocations = [];
        renderRooming();
        syncFinancialSummary();
    }

    function renderRooming() {
        syncTravelerStats();
        var board = document.getElementById('rooming-allocation-board');
        var pool = document.getElementById('rooming-unassigned-travelers');
        var hidden = document.getElementById('reservation-room-allocations-json');
        if (!board || !pool) return;

        var assigned = {};
        roomingAllocations.forEach(function (allocation) {
            (allocation.traveler_keys || []).forEach(function (id) { assigned[id] = true; });
        });
        var travelers = travelerRows();
        var byId = {};
        travelers.forEach(function (t) { byId[t.id] = t; });
        var unassigned = travelers.filter(function (t) { return t.consumesBed && !assigned[t.id]; });
        pool.innerHTML = unassigned.length ? unassigned.map(function (t) {
            return '<span class="reservation-create__traveler-chip">' + t.label + ' - ' + (t.gender || '-') + ' - ' + t.type + '</span>';
        }).join('') : '<span class="reservation-create__muted">Tous les voyageurs avec lit sont affectes.</span>';

        board.innerHTML = roomingAllocations.length ? roomingAllocations.map(function (allocation, index) {
            var travelerList = (allocation.traveler_keys || []).map(function (id) {
                var t = byId[id] || { label: id, gender: '-', type: '-' };
                return '<li>' + t.label + ' - ' + (t.gender || '-') + ' - ' + t.type + '</li>';
            }).join('');
            var travelerControls = travelers.filter(function (t) { return t.consumesBed; }).map(function (t) {
                var checked = (allocation.traveler_keys || []).indexOf(t.id) !== -1 ? ' checked' : '';
                return '<label class="reservation-create__room-traveler"><input type="checkbox" data-rooming-toggle="' + index + '" value="' + t.id + '"' + checked + '> ' + t.label + '</label>';
            }).join('');
            var roomOptions = availableRoomTypes.map(function (room) {
                var selected = String(room.room_source_id) === String(allocation.room_source_id) ? ' selected' : '';
                return '<option value="' + room.room_source_id + '"' + selected + '>' + room.room_type + ' (' + room.available_rooms + ' dispo)</option>';
            }).join('');
            var modeOptions = [
                ['full', 'Chambre complete'],
                ['half_male', 'Demi-double homme'],
                ['half_female', 'Demi-double femme'],
                ['single', 'Chambre single'],
                ['family', 'Chambre famille']
            ].map(function (mode) {
                return '<option value="' + mode[0] + '"' + (allocation.occupancy_mode === mode[0] ? ' selected' : '') + '>' + mode[1] + '</option>';
            }).join('');
            var remaining = Math.max(0, allocation.capacity - (allocation.traveler_keys || []).length);
            var modeLabel = { full: 'Complete', half_male: 'Demi-double H', half_female: 'Demi-double F', single: 'Single', family: 'Famille' }[allocation.occupancy_mode] || allocation.occupancy_mode;
            var waitingBadge = ((allocation.occupancy_mode === 'half_male' || allocation.occupancy_mode === 'half_female') && (allocation.traveler_keys || []).length === 1)
                ? '<span class="reservation-create__room-badge reservation-create__room-badge--warn">En attente de jumelage</span>'
                : '';
            return '<article class="reservation-create__room-card">' +
                '<div class="reservation-create__room-head"><strong>Chambre ' + (index + 1) + ' - ' + allocation.room_type + '</strong><span>' + modeLabel + '</span>' + waitingBadge + '</div>' +
                '<p>Capacite: ' + allocation.capacity + ' | Occupes: ' + (allocation.traveler_keys || []).length + '/' + allocation.capacity + ' | Statut: ' + allocation.status + '</p>' +
                '<div class="reservation-create__room-controls"><select class="reservation-create__input" data-rooming-room-type="' + index + '">' + roomOptions + '</select><select class="reservation-create__input" data-rooming-mode="' + index + '">' + modeOptions + '</select></div>' +
                '<ul>' + travelerList + '</ul>' +
                '<div class="reservation-create__room-travelers">' + travelerControls + '</div>' +
                (remaining ? '<p class="reservation-create__room-warning">Place restante: ' + remaining + '</p>' : '') +
            '</article>';
        }).join('') : '<div class="reservation-create__placeholder">Aucune repartition faite. Lancez la repartition automatique ou ajoutez une chambre.</div>';

        var summary = roomingSummary();
        var alerts = document.getElementById('rooming-alerts');
        var pill = document.getElementById('rooming-status-pill');
        if (pill) pill.textContent = 'Rooming ' + summary.status;
        if (alerts) {
            var warnings = summary.errors.slice();
            if (summary.status === 'partial') warnings.push('Cette demi-double n est pas encore completee.');
            alerts.classList.toggle('d-none', warnings.length === 0);
            alerts.innerHTML = warnings.length ? '<strong>Alertes rooming</strong><ul><li>' + warnings.join('</li><li>') + '</li></ul>' : '';
        }
        if (hidden) hidden.value = JSON.stringify(roomingAllocations.map(function (allocation) {
            return {
                room_source_type: allocation.room_source_type,
                room_source_id: allocation.room_source_id,
                room_type: allocation.room_type,
                occupancy_mode: allocation.occupancy_mode,
                capacity: allocation.capacity,
                traveler_keys: allocation.traveler_keys || [],
                occupied_count: (allocation.traveler_keys || []).length,
                status: allocation.status,
                supplement_total: allocation.supplement_total || 0
            };
        }));
        window.reservationState.roomAllocations = roomingAllocations;
    }

    function financialSummary() {
        var travelerCount = getTravelerCount();
        var room = hotelRoomSummary();
        var rooming = roomingSummary();
        var discount = discountSummary();
        var unitPrice = discount.priceAfterDiscount;
        var totalBase = unitPrice * travelerCount;
        var extras = extrasTotal();
        var effectiveRoomSupplement = rooming.roomSupplementTotal > 0 ? rooming.roomSupplementTotal : room.roomSupplementTotal;
        var totalAmount = totalBase + effectiveRoomSupplement + extras;
        var paidAmount = parseNumber(document.getElementById('payment_amount') && document.getElementById('payment_amount').value);
        var remainingAmount = Math.max(0, totalAmount - paidAmount);

        return {
            travelerCount: travelerCount,
            unitPrice: unitPrice,
            unitPriceBeforeDiscount: discount.unitPrice,
            discountType: discount.type,
            discountValue: discount.value,
            discountAmount: discount.amount,
            discountLabel: discount.label,
            priceAfterDiscount: discount.priceAfterDiscount,
            totalBase: totalBase,
            roomSupplementTotal: effectiveRoomSupplement,
            extrasTotal: extras,
            totalAmount: totalAmount,
            paidAmount: paidAmount,
            remainingAmount: remainingAmount,
            paymentStatus: derivePaymentStatus(totalAmount, paidAmount),
            selectedRoomCapacity: room.selectedRoomCapacity,
            selectedRoomCount: room.selectedRoomCount,
            roomingStatus: rooming.status,
            roomingErrors: rooming.errors,
            stockExceeded: room.stockExceeded,
            availableDepartureCapacity: getAvailableDepartureCapacity(),
            priceMissing: discount.unitPrice <= 0,
            roomMode: getRoomMode()
        };
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function syncFinancialSummary() {
        syncTravelerStats();
        var summary = financialSummary();

        setText('create-summary-trip', getSelectedTripLabel());
        setText('create-final-trip', getSelectedTripLabel());
        setText('create-summary-departure', getSelectedDepartureLabel());
        setText('create-final-departure', getSelectedDepartureLabel());
        setText('create-summary-travelers', String(summary.travelerCount));
        setText('create-final-travelers', String(summary.travelerCount));
        setText('create-travelers-badge', String(summary.travelerCount));
        setText('create-summary-unit-price', summary.priceMissing ? '—' : formatMoney(summary.unitPriceBeforeDiscount));
        setText('create-summary-discount', summary.discountLabel);
        setText('create-summary-price-after-discount', summary.priceMissing ? '—' : formatMoney(summary.priceAfterDiscount));
        setText('create-summary-total', formatMoney(summary.totalAmount));
        setText('create-summary-paid', formatMoney(summary.paidAmount));
        setText('create-summary-remaining', formatMoney(summary.remainingAmount));
        setText('create-final-total', formatMoney(summary.totalAmount));
        setText('create-final-extras', formatMoney(summary.extrasTotal));
        setText('create-final-remaining', formatMoney(summary.remainingAmount));
        setText('create-financial-total-base', formatMoney(summary.totalBase));
        setText('create-financial-room-supplement', formatMoney(summary.roomSupplementTotal));
        setText('create-financial-extras', formatMoney(summary.extrasTotal));
        setText('create-financial-total-amount', formatMoney(summary.totalAmount));
        setText('create-financial-paid-amount', formatMoney(summary.paidAmount));
        setText('create-financial-remaining-amount', formatMoney(summary.remainingAmount));
        setText('create-financial-payment-status', summary.paymentStatus);
        setText('create-dossier-status-preview', summary.paidAmount > 0 ? 'En attente' : 'Brouillon');

        setText('reservation-total-travelers', String(summary.travelerCount));
        setText('reservation-total-capacity', String(summary.selectedRoomCapacity));
        setText('reservation-total-supplement', formatMoney(summary.roomSupplementTotal));
        setText('reservation-grand-total', formatMoney(summary.totalAmount));
        setText('reservation-price-after-discount', summary.priceMissing ? '—' : formatMoney(summary.priceAfterDiscount));

        var totalBaseInput = document.getElementById('reservation-total-base-input');
        var roomSupplementInput = document.getElementById('reservation-room-supplement-total-input');
        var extrasTotalInput = document.getElementById('reservation-extras-total-input');
        var totalAmountInput = document.getElementById('reservation-total-amount-input');

        if (totalBaseInput) totalBaseInput.value = summary.totalBase.toFixed(2);
        if (roomSupplementInput) roomSupplementInput.value = summary.roomSupplementTotal.toFixed(2);
        if (extrasTotalInput) extrasTotalInput.value = summary.extrasTotal.toFixed(2);
        if (totalAmountInput) totalAmountInput.value = summary.totalAmount.toFixed(2);

        var paymentHelp = document.getElementById('create-payment-help');
        if (paymentHelp) {
            paymentHelp.textContent = summary.paidAmount > summary.totalAmount
                ? 'Le montant payÃ© dÃ©passe le total du dossier.'
                : 'Le montant payÃ© ne peut pas dÃ©passer le total du dossier.';
            paymentHelp.classList.toggle('is-error', summary.paidAmount > summary.totalAmount);
        }

        setAccommodationMode(summary.roomMode === 'places_only' ? 'places_only' : (summary.roomMode === 'blocked' ? 'blocked' : 'rooms'));

        var capacityError = document.getElementById('reservation-capacity-error');
        if (capacityError) {
            var hasCapacityIssue = summary.selectedRoomCapacity > 0 && summary.selectedRoomCapacity < summary.travelerCount;
            capacityError.classList.toggle('d-none', !hasCapacityIssue);
        }

        return summary;
    }

    function syncClientMode() {
        var newMode = document.getElementById('client_mode_new');
        var existingMode = document.getElementById('client_mode_existing');
        var newBlock = document.getElementById('new-client-block');
        var existingBlock = document.getElementById('existing-client-block');

        if (!newMode || !existingMode || !newBlock || !existingBlock) return;

        var useExisting = existingMode.checked;
        existingBlock.classList.toggle('d-none', !useExisting);
        newBlock.classList.toggle('d-none', useExisting);
    }

    function searchClients(query) {
        var resultsContainer = document.getElementById('client-search-results');
        var emptyMsg = document.getElementById('reservation-client-search-empty');
        if (!resultsContainer) return;

        clearStepErrors(2);
        clearInlineError();
        unblockContinueButton();

        if (!query || query.length < 2) {
            resultsContainer.hidden = true;
            resultsContainer.innerHTML = '';
            if (emptyMsg) emptyMsg.classList.add('d-none');
            return;
        }

        var url = '/admin/customers/clients/search?q=' + encodeURIComponent(query);
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                if (!r.ok) {
                    throw new Error('HTTP ' + r.status);
                }
                return r.json();
            })
            .then(function (data) {
                var items = data && Array.isArray(data.items) ? data.items : [];
                renderClientResults(items, query);
                if (emptyMsg) emptyMsg.classList.toggle('d-none', items.length > 0);
            })
            .catch(function (err) {
                renderClientResults([], query, true);
                if (emptyMsg) emptyMsg.classList.remove('d-none');
            });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function renderClientResults(items, query, isError) {
        var container = document.getElementById('client-search-results');
        if (!container) return;

        if (!items.length) {
            var title = isError ? 'Erreur lors de la recherche' : 'Aucun client trouvÃ©';
            var noResultHtml = '<div class="reservation-create__search-result reservation-create__search-result--empty">' +
                '<span><strong>' + title + '</strong><br><span class="reservation-create__search-result-meta">Pour "' + escapeHtml(query) + '"</span></span>' +
                '</div>' +
                '<div class="reservation-create__search-result reservation-create__search-result--action" id="client-search-create-new">' +
                '<span>CrÃ©er un nouveau client avec cette recherche</span>' +
                '<span class="reservation-create__search-result-code">+</span>' +
                '</div>';
            container.innerHTML = noResultHtml;
            container.hidden = false;
            return;
        }

        container.innerHTML = items.map(function (item) {
            var name = item.full_name || (item.first_name + ' ' + item.last_name).trim();
            var meta = [];
            if (item.phone) meta.push(item.phone);
            if (item.email) meta.push(item.email);
            if (item.document) meta.push(item.document);
            return '<div class="reservation-create__search-result" data-client-id="' + item.id + '" data-client-label="[' + (item.client_code || '') + '] ' + name + '">' +
                '<span><span class="reservation-create__search-result-name">' + name + '</span>' +
                '<span class="reservation-create__search-result-meta">' + meta.join(' Â· ') + '</span></span>' +
                '<span class="reservation-create__search-result-code">' + (item.client_code || '') + '</span>' +
                '</div>';
        }).join('');
        container.hidden = false;
    }

    function selectClient(id, label) {
        var hidden = document.getElementById('client_external_id');
        var selectedWrap = document.getElementById('client-search-selected');
        var selectedLabel = document.getElementById('client-search-selected-label');
        var searchInput = document.getElementById('reservation-client-search');
        var results = document.getElementById('client-search-results');

        if (hidden) hidden.value = id;
        if (selectedLabel) selectedLabel.textContent = label;
        if (selectedWrap) selectedWrap.classList.remove('d-none');
        if (searchInput) {
            searchInput.value = '';
            searchInput.blur();
        }
        if (results) {
            results.innerHTML = '';
            results.hidden = true;
        }
        syncFinancialSummary();
    }

    function clearClientSelection() {
        var hidden = document.getElementById('client_external_id');
        var selectedWrap = document.getElementById('client-search-selected');
        var selectedLabel = document.getElementById('client-search-selected-label');

        if (hidden) hidden.value = '';
        if (selectedLabel) selectedLabel.textContent = '';
        if (selectedWrap) selectedWrap.classList.add('d-none');
        syncFinancialSummary();
    }

    var debouncedSearchClients = debounce(searchClients, 300);

    function filterExistingClients() {
        // Legacy: replaced by live AJAX search
        var search = document.getElementById('reservation-client-search');
        if (search) {
            debouncedSearchClients(String(search.value || '').trim());
        }
    }

    function quickStoreClient(callback) {
        var firstNameEl = document.getElementById('client_first_name');
        var lastNameEl = document.getElementById('client_last_name');
        var phoneEl = document.getElementById('client_phone');
        var emailEl = document.getElementById('client_email');
        var genderEl = document.getElementById('client_gender');
        var birthDateEl = document.getElementById('client_birth_date');
        var nationalityEl = document.getElementById('client_nationality');
        var docTypeEl = document.getElementById('client_document_type');
        var docNumEl = document.getElementById('client_document_number');

        var payload = {
            first_name: String(firstNameEl && firstNameEl.value || '').trim(),
            last_name: String(lastNameEl && lastNameEl.value || '').trim(),
            phone: String(phoneEl && phoneEl.value || '').trim(),
            email: String(emailEl && emailEl.value || '').trim() || null,
            gender: String(genderEl && genderEl.value || '') || null,
            date_of_birth: String(birthDateEl && birthDateEl.value || '') || null,
            nationality: String(nationalityEl && nationalityEl.value || '').trim() || null,
            national_id_number: (String(docTypeEl && docTypeEl.value || '') === 'cin' ? String(docNumEl && docNumEl.value || '').trim() : null) || null,
            passport_number: (String(docTypeEl && docTypeEl.value || '') === 'passport' ? String(docNumEl && docNumEl.value || '').trim() : null) || null,
        };

        if (!payload.first_name || !payload.last_name || !payload.phone) {
            showInlineError('Le prénom, le nom et le téléphone sont obligatoires.');
            if (typeof callback === 'function') callback(false);
            return;
        }

        var url = '/admin/customers/clients/quick-store';
        fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content') || ''
            },
            body: JSON.stringify(payload)
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success && data.client && data.client.id) {
                    var hidden = document.getElementById('client_external_id');
                    if (hidden) hidden.value = data.client.id;
                    var label = data.client.full_name || (data.client.first_name + ' ' + data.client.last_name).trim();
                    var existingMode = document.getElementById('client_mode_existing');
                    if (existingMode) existingMode.checked = true;
                    syncClientMode();
                    clearInlineError();
                    clearStepErrors(2);
                    unblockContinueButton();
                    if (typeof callback === 'function') callback(true);
                } else if (data && data.duplicate) {
                    var dupLabel = data.duplicate.full_name || ('Client #' + data.duplicate.id);
                    showInlineError('Ce client existe dÃ©jÃ  : ' + dupLabel + '. Veuillez le sÃ©lectionner dans la liste.');
                    blockContinueButton();
                    if (typeof callback === 'function') callback(false);
                } else if (data && data.errors) {
                    var fieldMap = {
                        first_name: 'client_first_name',
                        last_name: 'client_last_name',
                        phone: 'client_phone',
                        email: 'client_email',
                        gender: 'client_gender',
                        date_of_birth: 'client_birth_date',
                        nationality: 'client_nationality',
                        national_id_number: 'client_document_number',
                        passport_number: 'client_document_number',
                    };
                    var stepErrors = [];
                    Object.keys(data.errors).forEach(function (key) {
                        var messages = data.errors[key];
                        if (Array.isArray(messages)) {
                            messages.forEach(function (msg) {
                                stepErrors.push({ field: fieldMap[key] || key, message: msg });
                            });
                        } else {
                            stepErrors.push({ field: fieldMap[key] || key, message: String(messages) });
                        }
                    });
                    renderStepErrors(2, stepErrors);
                    blockContinueButton();
                    if (typeof callback === 'function') callback(false);
                } else if (data && data.message) {
                    showInlineError(data.message);
                    blockContinueButton();
                    if (typeof callback === 'function') callback(false);
                } else {
                    showInlineError('Erreur lors de la crÃ©ation du client. Veuillez rÃ©essayer.');
                    blockContinueButton();
                    if (typeof callback === 'function') callback(false);
                }
            })
            .catch(function () {
                showInlineError('Erreur rÃ©seau lors de la crÃ©ation du client.');
                blockContinueButton();
                if (typeof callback === 'function') callback(false);
            });
    }

    function syncVisaMode() {
        var checkbox = document.getElementById('visa_ok');
        var block = document.getElementById('assistant-visa-block');
        if (!checkbox || !block) return;
        block.classList.toggle('d-none', checkbox.checked);
    }

    function getStepErrorContainer(step) {
        return document.getElementById('step-' + step + '-errors');
    }

    function clearStepErrors(step) {
        var container = getStepErrorContainer(step);
        if (container) {
            container.innerHTML = '';
            container.hidden = true;
        }
        var panel = currentPanel();
        if (panel) {
            panel.querySelectorAll('.reservation-create__field.is-invalid').forEach(function (field) {
                field.classList.remove('is-invalid');
            });
            panel.querySelectorAll('.reservation-create__field-error').forEach(function (msg) {
                msg.remove();
            });
        }
    }

    function renderStepErrors(step, errors) {
        var container = getStepErrorContainer(step);
        if (!container) return;
        if (!errors || !errors.length) {
            container.hidden = true;
            return;
        }
        container.innerHTML = errors.map(function (err) {
            return '<div>' + (err.message || err) + '</div>';
        }).join('');
        container.hidden = false;

        var panel = currentPanel();
        if (panel) {
            errors.forEach(function (err) {
                if (err.field) {
                    var input = panel.querySelector('[name="' + err.field + '"], [id="' + err.field + '"]');
                    if (input) {
                        var fieldWrap = input.closest('.reservation-create__field');
                        if (fieldWrap && !fieldWrap.querySelector('.reservation-create__field-error')) {
                            fieldWrap.classList.add('is-invalid');
                            var msgEl = document.createElement('div');
                            msgEl.className = 'reservation-create__field-error';
                            msgEl.textContent = err.message;
                            fieldWrap.appendChild(msgEl);
                        }
                    }
                }
            });
        }
    }

    function blockContinueButton() {
        var panel = currentPanel();
        if (!panel) return;
        var btn = panel.querySelector('.reservation-create__button--primary[data-create-next]');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('is-disabled');
        }
    }

    function unblockContinueButton() {
        var panel = currentPanel();
        if (!panel) return;
        var btn = panel.querySelector('.reservation-create__button--primary[data-create-next]');
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('is-disabled');
        }
    }

    function scrollToFirstError() {
        var panel = currentPanel();
        if (!panel) return;
        var firstInvalid = panel.querySelector('.reservation-create__field.is-invalid, .reservation-create__step-errors:not([hidden])');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function bindLiveValidationClear() {
        document.querySelectorAll('.reservation-create__panel').forEach(function (panel) {
            panel.addEventListener('input', function (e) {
                var fieldWrap = e.target.closest('.reservation-create__field');
                if (fieldWrap) {
                    fieldWrap.classList.remove('is-invalid');
                    var msg = fieldWrap.querySelector('.reservation-create__field-error');
                    if (msg) msg.remove();
                }
                var step = Number(panel.getAttribute('data-create-step')) || 0;
                if (step === currentStep) {
                    clearStepErrors(step);
                    clearInlineError();
                    unblockContinueButton();
                }
            });
            panel.addEventListener('change', function (e) {
                var fieldWrap = e.target.closest('.reservation-create__field');
                if (fieldWrap) {
                    fieldWrap.classList.remove('is-invalid');
                    var msg = fieldWrap.querySelector('.reservation-create__field-error');
                    if (msg) msg.remove();
                }
                var step = Number(panel.getAttribute('data-create-step')) || 0;
                if (step === currentStep) {
                    clearStepErrors(step);
                    clearInlineError();
                }
            });
        });
    }

    var StepValidator = {
        validateStep2: function () {
            var errors = [];
            var existingMode = document.getElementById('client_mode_existing');
            var clientIdInput = document.getElementById('client_external_id');
            var hasClientId = clientIdInput && !!clientIdInput.value;

            if (existingMode && existingMode.checked) {
                if (!hasClientId) {
                    errors.push({ field: 'client_external_id', message: 'Sélectionnez un client existant.' });
                }
            } else {
                if (!String(document.getElementById('client_first_name') && document.getElementById('client_first_name').value || '').trim()) {
                    errors.push({ field: 'client_first_name', message: 'Le prénom du client principal est obligatoire.' });
                }
                if (!String(document.getElementById('client_last_name') && document.getElementById('client_last_name').value || '').trim()) {
                    errors.push({ field: 'client_last_name', message: 'Le nom du client principal est obligatoire.' });
                }
                if (!String(document.getElementById('client_phone') && document.getElementById('client_phone').value || '').trim()) {
                    errors.push({ field: 'client_phone', message: 'Le téléphone du client principal est obligatoire.' });
                }
            }

            document.querySelectorAll('#companions-container .companion-row').forEach(function (row, index) {
                var first = row.querySelector('input[name*="[first_name]"]');
                var last = row.querySelector('input[name*="[last_name]"]');
                var firstName = String(first && first.value || '').trim();
                var lastName = String(last && last.value || '').trim();
                var hasAny = firstName !== '' || lastName !== '';
                var hasBoth = firstName !== '' && lastName !== '';
                if (hasAny && !hasBoth) {
                    errors.push({ field: null, message: 'Accompagnant #' + (index + 1) + ' : prénom et nom sont tous les deux obligatoires.' });
                }
            });

            return { valid: errors.length === 0, errors: errors };
        },

        validateStep3: function () {
            renderRooming();
            var errors = [];
            var summary = financialSummary();
            var stats = travelerStats();

        if (stats.genderUnknownAdults > 0) {
                errors.push({ field: null, message: 'Veuillez renseigner le sexe des adultes pour faire la répartition des chambres.' });
            }
            if (summary.roomingStatus === 'pending') {
                errors.push({ field: null, message: 'Lancez une répartition automatique ou ajoutez une répartition manuelle.' });
            }
            if (summary.roomingStatus === 'invalid') {
                (summary.roomingErrors || ['Répartition chambres invalide.']).forEach(function (msg) {
                    errors.push({ field: null, message: msg });
                });
            }
            if (summary.selectedRoomCapacity > 0 && summary.travelerCount > summary.selectedRoomCapacity) {
                errors.push({ field: null, message: 'Le nombre de voyageurs dépasse la capacité des chambres sélectionnées.' });
            }
            if (summary.availableDepartureCapacity > 0 && summary.travelerCount > summary.availableDepartureCapacity) {
                errors.push({ field: null, message: 'Le nombre de voyageurs dépasse la capacité disponible de ce départ.' });
            }
            return { valid: errors.length === 0, errors: errors };
        },

        validateStep4: function () {
            var errors = [];
            var travelers = travelerRows();
            var travelerCount = travelers.length;

            document.querySelectorAll('.reservation-create__extra-card').forEach(function (card) {
                var name = String(card.getAttribute('data-extra-name') || 'Extra');
                var quantityInput = card.querySelector('[data-extra-quantity]');
                var scopeInput = card.querySelector('[data-extra-scope]');
                var quantity = Math.max(0, parseInt(quantityInput && quantityInput.value || '0', 10) || 0);
                var scope = String(scopeInput && scopeInput.value || 'dossier');

                if (quantity < 1) return;

                var maxAllowed = 1;
                if (scope === 'traveler_selection') {
                    maxAllowed = card.querySelectorAll('.reservation-create-extra-cb:checked').length;
                } else if (scope === 'per_traveler') {
                    maxAllowed = travelerCount;
                }

                if (quantity > maxAllowed && maxAllowed > 0) {
                    errors.push({ field: null, message: 'Extra "' + name + '" : quantité max autorisée = ' + maxAllowed + '.' });
                }
            });

            return { valid: errors.length === 0, errors: errors };
        }
    };

    function inlineErrorTarget() {
        var panel = currentPanel();
        if (!panel) return null;
        var alert = panel.querySelector('.reservation-create__inline-error');
        if (!alert) {
            alert = document.createElement('div');
            alert.className = 'reservation-create__alert reservation-create__alert--error reservation-create__inline-error';
            panel.insertBefore(alert, panel.firstChild);
        }
        return alert;
    }

    function showInlineError(message) {
        var alert = inlineErrorTarget();
        if (!alert) return;
        alert.innerHTML = '<strong>Validation</strong><p>' + message + '</p>';
    }

    function clearInlineError() {
        var panel = currentPanel();
        if (!panel) return;
        var alert = panel.querySelector('.reservation-create__inline-error');
        if (alert) {
            alert.remove();
        }
    }

    function validateStep(step) {
        var summary = syncFinancialSummary();
        clearStepErrors(step);
        clearInlineError();
        unblockContinueButton();

        var result = { valid: true, errors: [] };

        if (step === 1) {
            var tripSelect = document.getElementById('select-tour-id');
            var departureSelect = document.getElementById('reservation-departure-select');

            if (!tripSelect || !tripSelect.value) {
                result.errors.push({ field: 'select-tour-id', message: 'Sélectionnez un voyage avant de continuer.' });
            }
            if (!departureSelect || !departureSelect.value) {
                result.errors.push({ field: 'reservation-departure-select', message: 'Sélectionnez un départ avant de continuer.' });
            }
            if (summary.priceMissing) {
                result.errors.push({ field: null, message: 'Aucun prix configurÃ© pour ce voyage/dÃ©part.' });
            }
            if (summary.availableDepartureCapacity > 0 && summary.travelerCount > summary.availableDepartureCapacity) {
                result.errors.push({ field: null, message: 'Le nombre de voyageurs dÃ©passe le stock disponible sur ce dÃ©part.' });
            }

            if (summary.roomMode === 'places_only') {
                if (summary.availableDepartureCapacity <= 0) {
                    result.errors.push({ field: null, message: 'Ce dÃ©part nâ€™a plus de places disponibles.' });
                }
                if (summary.travelerCount > summary.availableDepartureCapacity) {
                    result.errors.push({ field: null, message: 'Stock insuffisant : il reste seulement ' + summary.availableDepartureCapacity + ' places.' });
                }
            }
            result.valid = result.errors.length === 0;
        }

        if (step === 2) {
            result = StepValidator.validateStep2();
        }

        if (step === 3) {
            result = StepValidator.validateStep3();
        }

        if (step === 4) {
            result = StepValidator.validateStep4();
        }

        if (step === 5) {
            if (summary.paidAmount > summary.totalAmount) {
                result.errors.push({ field: 'payment_amount', message: 'Le montant payÃ© ne peut pas dÃ©passer le total du dossier.' });
                result.valid = false;
            }
        }

        if (!result.valid) {
            renderStepErrors(step, result.errors);
            blockContinueButton();
            scrollToFirstError();
            return false;
        }

        return true;
    }

    function setStep(step) {
        var panels = allPanels();
        if (!panels.length) return;

        var max = panels.length;
        var next = Math.max(1, Math.min(Number(step) || 1, max));
        currentStep = next;
        window.currentStep = next;
        window.reservationState.currentStep = next;
        console.log('[Workflow] goToStep', next);

        panels.forEach(function (panel) {
            var isActive = Number(panel.getAttribute('data-create-step')) === next;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
            panel.classList.toggle('d-none', !isActive);
        });

        document.querySelectorAll('[data-create-step-nav]').forEach(function (button) {
            var stepNumber = Number(button.getAttribute('data-create-step-nav'));
            button.classList.toggle('is-active', stepNumber === next);
            button.classList.toggle('is-complete', stepNumber < next);
        });

        syncFinancialSummary();
        clearInlineError();
        clearStepErrors(next);
        unblockContinueButton();
        if (next === 3) {
            console.log('[Rooming Step Render]', {
                selectedTourId: window.reservationState.selectedTourId,
                selectedDepartureId: window.reservationState.selectedDepartureId,
                selectedTravelDateId: window.reservationState.selectedTravelDateId,
                availableRooms: window.reservationState.availableRooms,
                travelers: window.reservationState.travelers
            });
            setAvailableRoomTypes(window.reservationState.availableRooms || window.reservationAvailableRooms || []);
            renderRooming();
        }
    }

    function goToStep(step) {
        var target = Number(step) || currentStep;
        console.log('[Workflow] Next step:', target);
        if (target > currentStep) {
            // Async validation path for step 2 new-client quick-store
            if (currentStep === 2) {
                var newMode = document.getElementById('client_mode_new');
                var clientIdInput = document.getElementById('client_external_id');
                var hasClientId = clientIdInput && !!clientIdInput.value;
                if (newMode && newMode.checked && !hasClientId) {
                    quickStoreClient(function (success) {
                        if (success && validateStep(currentStep)) {
                            setStep(target);
                        }
                    });
                    return false;
                }
            }
            if (!validateStep(currentStep)) {
                console.warn('[Workflow] Step validation failed', currentStep);
                return false;
            }
        }
        setStep(target);
        return true;
    }

    function addCompanion() {
        var container = document.getElementById('companions-container');
        if (!container) return;

        var index = container.querySelectorAll('.companion-row').length;
        var stableId = 'companion_' + (++companionIdCounter);
        var row = document.createElement('div');
        row.className = 'companion-row reservation-create__companion';
        row.setAttribute('data-companion-id', stableId);
        row.setAttribute('data-traveler-key', stableId);
        row.innerHTML =
            '<div class="reservation-create__companion-head">' +
                '<h4 class="reservation-create__companion-title">Accompagnant #' + (index + 1) + '</h4>' +
                '<button type="button" class="btn-remove-companion reservation-create__remove" aria-label="Supprimer">Ã—</button>' +
            '</div>' +
            '<div class="reservation-create__grid reservation-create__grid--two">' +
                '<input type="hidden" name="passengers[' + stableId + '][traveler_key]" value="' + stableId + '">' +
                '<div class="reservation-create__field"><label class="reservation-create__label">PrÃ©nom</label><input type="text" name="passengers[' + stableId + '][first_name]" class="reservation-create__input"></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Nom</label><input type="text" name="passengers[' + stableId + '][last_name]" class="reservation-create__input"></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Type</label><select name="passengers[' + stableId + '][type]" class="reservation-create__input"><option value="adult">Adulte</option><option value="child">Enfant</option><option value="infant">BÃ©bÃ©</option></select></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Sexe</label><select name="passengers[' + stableId + '][gender]" class="reservation-create__input"><option value="">Selectionner...</option><option value="male">Homme</option><option value="female">Femme</option></select></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Relation</label><select name="passengers[' + stableId + '][relationship_to_main]" class="reservation-create__input"><option value="spouse">Conjoint / conjointe</option><option value="child">Enfant</option><option value="parent">Parent</option><option value="friend">Ami</option><option value="group" selected>Groupe</option><option value="solo">Seul</option></select></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Date de naissance</label><input type="date" name="passengers[' + stableId + '][birth_date]" class="reservation-create__input"></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Type document</label><input type="text" name="passengers[' + stableId + '][document_type]" class="reservation-create__input"></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">NÂ° document</label><input type="text" name="passengers[' + stableId + '][document_number]" class="reservation-create__input"></div>' +
            '</div>';
        container.appendChild(row);
        syncTravelersEmptyState();
        renderExtras();
        syncFinancialSummary();
    }

    function syncTravelersEmptyState() {
        var empty = document.getElementById('create-no-companions');
        if (!empty) return;
        empty.classList.toggle('d-none', document.querySelectorAll('#companions-container .companion-row').length > 0);
    }

    function bindDelegatedEvents() {
        var form = document.getElementById('reservation-create-form');
        if (!form) return;

        form.addEventListener('input', function (event) {
            var target = event.target;
            if (!target) return;

            if (target.matches('#client_first_name, #client_last_name, #client_phone, #client_email, #client_external_id, #payment_amount, input[name="base_price"], #reservation-discount-value, .reservation-room-count')) {
                syncFinancialSummary();
            }
            if (target.matches('#reservation-client-search')) {
                filterExistingClients();
            }

            if (target.closest('#companions-container')) {
                syncTravelersEmptyState();
                renderExtras();
                renderRooming();
            }
            if (target.matches('#client_first_name, #client_last_name, #client_traveler_type, #client_gender')) {
                renderRooming();
            }
        });

        form.addEventListener('change', function (event) {
            var target = event.target;
            if (!target) return;

            if (target.matches('#client_mode_new, #client_mode_existing')) {
                syncClientMode();
                clearStepErrors(2);
                clearInlineError();
                unblockContinueButton();
                renderExtras();
            }
            if (target.matches('#visa_ok')) {
                syncVisaMode();
            }
            if (target.matches('#select-tour-id, #reservation-departure-select, #client_external_id, #reservation-discount-type, .reservation-room-count, #payment_type, #payment_date')) {
                syncFinancialSummary();
                if (target.id === 'select-tour-id') {
                    renderExtras();
                }
            }
            if (target.closest('#companions-container')) {
                syncTravelersEmptyState();
                renderExtras();
                renderRooming();
            }
            if (target.matches('#client_traveler_type, #client_gender')) {
                renderRooming();
            }
            if (target.hasAttribute('data-rooming-toggle')) {
                var roomIndex = parseInt(target.getAttribute('data-rooming-toggle') || '-1', 10);
                if (roomingAllocations[roomIndex]) {
                    var keys = roomingAllocations[roomIndex].traveler_keys || [];
                    if (target.checked && keys.indexOf(target.value) === -1) {
                        keys.push(target.value);
                    }
                    if (!target.checked) {
                        keys = keys.filter(function (key) { return key !== target.value; });
                    }
                    roomingAllocations[roomIndex].traveler_keys = keys;
                    roomingAllocations[roomIndex].occupied_count = keys.length;
                    roomingAllocations[roomIndex].status = keys.length >= roomingAllocations[roomIndex].capacity ? 'complete' : 'partial';
                    renderRooming();
                    syncFinancialSummary();
                }
            }
            if (target.hasAttribute('data-rooming-room-type')) {
                var typeIndex = parseInt(target.getAttribute('data-rooming-room-type') || '-1', 10);
                var selectedRoom = availableRoomTypes.find(function (room) { return String(room.room_source_id) === String(target.value); });
                if (roomingAllocations[typeIndex] && selectedRoom) {
                    roomingAllocations[typeIndex].room_source_type = selectedRoom.room_source_type;
                    roomingAllocations[typeIndex].room_source_id = selectedRoom.room_source_id;
                    roomingAllocations[typeIndex].room_type = selectedRoom.room_type;
                    roomingAllocations[typeIndex].capacity = selectedRoom.capacity;
                    roomingAllocations[typeIndex].unit_supplement = selectedRoom.unit_supplement;
                    roomingAllocations[typeIndex].supplement_total = selectedRoom.unit_supplement;
                    roomingAllocations[typeIndex].status = (roomingAllocations[typeIndex].traveler_keys || []).length >= selectedRoom.capacity ? 'complete' : 'partial';
                    renderRooming();
                    syncFinancialSummary();
                }
            }
            if (target.hasAttribute('data-rooming-mode')) {
                var modeIndex = parseInt(target.getAttribute('data-rooming-mode') || '-1', 10);
                if (roomingAllocations[modeIndex]) {
                    roomingAllocations[modeIndex].occupancy_mode = target.value;
                    roomingAllocations[modeIndex].status = (roomingAllocations[modeIndex].traveler_keys || []).length >= roomingAllocations[modeIndex].capacity || ['single', 'family', 'full'].indexOf(target.value) !== -1 ? 'complete' : 'partial';
                    renderRooming();
                    syncFinancialSummary();
                }
            }
        });

        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!target || !target.closest('#reservation-create-form')) return;

            var searchResult = target.closest('.reservation-create__search-result[data-client-id]');
            if (searchResult) {
                event.preventDefault();
                selectClient(
                    searchResult.getAttribute('data-client-id'),
                    searchResult.getAttribute('data-client-label')
                );
                return;
            }

            var createNewFromSearch = target.closest('#client-search-create-new');
            if (createNewFromSearch) {
                event.preventDefault();
                var query = document.getElementById('reservation-client-search');
                var parts = query ? String(query.value || '').trim().split(/\s+/) : ['', ''];
                document.getElementById('client_mode_new').checked = true;
                syncClientMode();
                clearStepErrors(2);
                clearInlineError();
                unblockContinueButton();
                if (parts.length > 1) {
                    document.getElementById('client_first_name').value = parts.slice(0, -1).join(' ');
                    document.getElementById('client_last_name').value = parts[parts.length - 1];
                } else {
                    document.getElementById('client_first_name').value = parts[0] || '';
                    document.getElementById('client_last_name').value = '';
                }
                if (query) query.value = '';
                var results = document.getElementById('client-search-results');
                if (results) {
                    results.innerHTML = '';
                    results.hidden = true;
                }
                return;
            }

            var clearSelected = target.closest('#client-search-clear');
            if (clearSelected) {
                event.preventDefault();
                clearClientSelection();
                return;
            }

            var addCompanionBtn = target.closest('#btn-add-companion');
            if (addCompanionBtn) {
                event.preventDefault();
                console.log('[Travelers] Add companion clicked');
                addCompanion();
                renderRooming();
                return;
            }

            var autoBtn = target.closest('#btn-rooming-auto, #btn-auto-rooming');
            if (autoBtn) {
                event.preventDefault();
                console.log('[Rooming] Auto clicked');
                autoRooming();
                syncFinancialSummary();
                return;
            }

            var resetBtn = target.closest('#btn-rooming-reset, #btn-reset-rooming');
            if (resetBtn) {
                event.preventDefault();
                resetRooming();
                return;
            }

            var addRoomBtn = target.closest('#btn-rooming-add, #btn-add-room-allocation');
            if (addRoomBtn) {
                event.preventDefault();
                addManualRoomAllocation();
                return;
            }

            var reloadBtn = target.closest('#btn-reload-rooms');
            if (reloadBtn) {
                event.preventDefault();
                console.log('[Rooming] Reload rooms clicked');
                console.log('[Rooming Step Render]', {
                    selectedTourId: window.reservationState.selectedTourId,
                    selectedDepartureId: window.reservationState.selectedDepartureId,
                    selectedTravelDateId: window.reservationState.selectedTravelDateId,
                    availableRooms: window.reservationState.availableRooms,
                    travelers: window.reservationState.travelers
                });
                console.log('[Rooming] Reload rooms payload', {
                    tour_id: window.reservationState.selectedTourId,
                    departure_id: window.reservationState.selectedDepartureId,
                    travel_date_id: window.reservationState.selectedTravelDateId
                });
                if (typeof window.reservationCreateReloadDepartureRooms === 'function') {
                    window.reservationCreateReloadDepartureRooms();
                    return;
                }
                setAvailableRoomTypes(window.reservationAvailableRooms || window.reservationState.availableRooms || []);
                if (!availableRoomTypes.length) {
                    showRoomingAlert('Aucune chambre n est encore chargee. Retournez a l etape Prestation et choisissez un depart.');
                }
                return;
            }

            var removeCompanionBtn = target.closest('.btn-remove-companion');
            if (removeCompanionBtn) {
                event.preventDefault();
                console.log('[Travelers] Remove companion clicked');
                var row = removeCompanionBtn.closest('.companion-row');
                if (row) {
                    var removedId = row.getAttribute('data-companion-id') || row.getAttribute('data-traveler-key');
                    if (removedId) {
                        roomingAllocations = roomingAllocations.map(function (allocation) {
                            var keys = (allocation.traveler_keys || []).filter(function (k) { return k !== removedId; });
                            allocation.traveler_keys = keys;
                            allocation.occupied_count = keys.length;
                            allocation.status = keys.length >= allocation.capacity ? 'complete' : 'partial';
                            return allocation;
                        }).filter(function (allocation) {
                            return (allocation.traveler_keys || []).length > 0;
                        });
                        window.reservationState.roomAllocations = roomingAllocations;
                    }
                    row.remove();
                    syncTravelersEmptyState();
                    renderExtras();
                    renderRooming();
                    syncFinancialSummary();
                }
                return;
            }

            var nextBtn = target.closest('[data-create-next], [data-step-next]');
            if (nextBtn) {
                event.preventDefault();
                var nextStep = nextBtn.hasAttribute('data-step-next')
                    ? parseInt(nextBtn.getAttribute('data-step-next') || '0', 10)
                    : currentStep + 1;
                goToStep(nextStep);
                return;
            }

            var prevBtn = target.closest('[data-create-prev], [data-step-back]');
            if (prevBtn) {
                event.preventDefault();
                var prevStep = prevBtn.hasAttribute('data-step-back')
                    ? parseInt(prevBtn.getAttribute('data-step-back') || '0', 10)
                    : currentStep - 1;
                console.log('[Workflow] Back step:', prevStep);
                setStep(prevStep);
                return;
            }

            var navBtn = target.closest('[data-create-step-nav]');
            if (navBtn) {
                event.preventDefault();
                var requested = Number(navBtn.getAttribute('data-create-step-nav'));
                goToStep(requested);
            }
        });

        // Close client search dropdown when clicking outside
        document.addEventListener('click', function (event) {
            var results = document.getElementById('client-search-results');
            var searchInput = document.getElementById('reservation-client-search');
            if (!results || results.hidden) return;
            var inside = (event.target && (event.target.closest('#client-search-results') || event.target === searchInput));
            if (!inside) {
                results.hidden = true;
            }
        });

        form.addEventListener('submit', function (event) {
            if (!validateStep(currentStep)) {
                event.preventDefault();
                return;
            }
            collectExtras();
            renderRooming();
            syncFinancialSummary();
            // If we're in places_only mode, remove any hotel_rooms inputs so null IDs
            // are not submitted and backend treats this as places-only booking.
            try {
                var roomingHidden = document.getElementById('reservation-room-allocations-json');
                var hasRooming = roomingHidden && String(roomingHidden.value || '[]') !== '[]';
                if (hasRooming || (typeof getRoomMode === 'function' && getRoomMode() === 'places_only')) {
                    document.querySelectorAll('[name^="hotel_rooms"]').forEach(function (el) { el.remove(); });
                }
            } catch (e) {
                // ignore
            }
        });
    }

    function initializeReservationState() {
        var tourSelect = document.getElementById('select-tour-id');
        var departureSelect = document.getElementById('reservation-departure-select');
        var departureIdInput = document.getElementById('input-departure-id');
        var travelDateInput = document.getElementById('input-travel-date-id');
        var urlParams = new URLSearchParams(window.location.search);
        
        if (tourSelect && tourSelect.value) {
            window.reservationState.selectedTourId = tourSelect.value;
        }

        if (!window.reservationState.selectedTourId) {
            window.reservationState.selectedTourId = urlParams.get('tour_id');
        }

        if (departureSelect && departureSelect.value) {
            var selectedOption = departureSelect.options[departureSelect.selectedIndex];
            window.reservationState.selectedDepartureId = departureSelect.value;
            if (selectedOption) {
                window.reservationState.selectedTravelDateId = selectedOption.getAttribute('data-wp-travel-date-id') || selectedOption.getAttribute('data-travel-date-id');
            }
        }

        if (!window.reservationState.selectedDepartureId && departureIdInput && departureIdInput.value) {
            window.reservationState.selectedDepartureId = departureIdInput.value;
        }

        if (!window.reservationState.selectedTravelDateId && travelDateInput && travelDateInput.value) {
            window.reservationState.selectedTravelDateId = travelDateInput.value;
        }

        // If values are still null, try to get them from URL params
        if (!window.reservationState.selectedTravelDateId) {
            window.reservationState.selectedTravelDateId = urlParams.get('travel_date_id');
        }

        window.availableRoomTypes = Array.isArray(window.availableRoomTypes) ? window.availableRoomTypes : [];
        window.reservationState.availableRoomTypes = Array.isArray(window.reservationState.availableRoomTypes)
            ? window.reservationState.availableRoomTypes
            : window.availableRoomTypes;

        console.log('[Reservation Create] Initial state loaded:', window.reservationState);

        if (typeof window.reservationCreateReloadDepartureRooms === 'function' && window.reservationState.selectedDepartureId) {
            console.log('[Reservation Create] Calling reservationCreateReloadDepartureRooms on init.');
            window.reservationCreateReloadDepartureRooms();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        console.log('[Reservation Create] DOMContentLoaded started');

        // Seed companionIdCounter from existing rows so new companions don't collide
        document.querySelectorAll('#companions-container .companion-row').forEach(function (row) {
            var id = row.getAttribute('data-companion-id') || row.getAttribute('data-traveler-key') || '';
            var match = id.match(/^companion_(\d+)$/);
            if (match) {
                companionIdCounter = Math.max(companionIdCounter, parseInt(match[1], 10));
            }
        });

        // Register event listener FIRST before any room loading happens
        document.addEventListener('reservation:rooms-loaded', function (event) {
            console.log('[Reservation Create] Event reservation:rooms-loaded received', event.detail);
            setAvailableRoomTypes(event && event.detail ? event.detail.rooms : []);
        });

        extrasMap = parseJsonScript('reservation-create-extras-map', {});
        bindDelegatedEvents();
        bindLiveValidationClear();
        syncClientMode();
        filterExistingClients();
        syncVisaMode();
        syncTravelersEmptyState();
        renderExtras();
        initializeReservationState();
        
        // Now safely call setAvailableRoomTypes with existing rooms
        console.log('[Reservation Create] Setting initial available rooms', {
            windowReservationAvailableRooms: window.reservationAvailableRooms,
            windowReservationState: window.reservationState
        });
        setAvailableRoomTypes(window.reservationAvailableRooms || []);
        setStep(1);
        console.log('[Reservation Create] Current step:', window.currentStep);
        console.log('[Reservation Create] State:', window.reservationState);

        window.reservationCreateCollectExtras = collectExtras;
        window.reservationCreateGetExtrasTotal = function () {
            return financialSummary().extrasTotal;
        };
        window.reservationCreateGetHotelSummary = hotelRoomSummary;
        window.reservationCreateRecomputeTotals = syncFinancialSummary;

        window.resetReservationDownstream = function (options) {
            options = options || {};
            if (options.tourChanged) {
                var companionsContainer = document.getElementById('companions-container');
                if (companionsContainer) {
                    companionsContainer.innerHTML = '';
                }
                syncTravelersEmptyState();
                var paymentInput = document.getElementById('payment_amount');
                if (paymentInput) {
                    paymentInput.value = '';
                }
                renderExtras();
            }
            roomingAllocations = [];
            window.reservationState.roomAllocations = [];
            renderRooming();
            syncFinancialSummary();
        };

        // Expose setAccommodationMode globally and flush any queued calls
        if (typeof window.setAccommodationMode !== 'function') {
            window.setAccommodationMode = setAccommodationMode;
        } else {
            var _pending = window._reservation_setAccommodationMode_pending || [];
            window.setAccommodationMode = setAccommodationMode;
            if (Array.isArray(_pending) && _pending.length) {
                _pending.forEach(function (m) { try { setAccommodationMode(m); } catch (e) { /* ignore */ } });
                delete window._reservation_setAccommodationMode_pending;
            }
        }
        // Expose helper to allow inline templates to read selected departure label safely
        if (typeof window.getSelectedDepartureLabel !== 'function') {
            window.getSelectedDepartureLabel = getSelectedDepartureLabel;
        }
        if (typeof window.getAvailableDepartureCapacity !== 'function') {
            window.getAvailableDepartureCapacity = getAvailableDepartureCapacity;
        }
        if (typeof window.getRoomMode !== 'function') {
            window.getRoomMode = getRoomMode;
        }
    });
})();


