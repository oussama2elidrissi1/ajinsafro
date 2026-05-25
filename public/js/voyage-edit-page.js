(function(){
                            var container = document.getElementById('travel-dates-container');
                            var addBtn = document.getElementById('add-travel-date');
                            if (!container || !addBtn) return;
                            if (container.dataset.initialized === 'true') return;
                            container.dataset.initialized = 'true';
                            addBtn.addEventListener('click', function(){
                                var rows = container.querySelectorAll('.travel-date-row');
                                var nextIndex = rows.length;
                                var html = `
                                <div class="card mb-2 travel-date-row border shadow-sm" data-index="${nextIndex}">
                                    <div class="card-body py-3">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-6 col-md-3">
                                                <label class="form-label small mb-1">Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control form-control-sm" name="travel_dates[${nextIndex}][date]" required>
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small mb-1">Nombre de places <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control form-control-sm" name="travel_dates[${nextIndex}][seats]" value="0" min="0" required>
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small mb-1">Supplément date</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm" name="travel_dates[${nextIndex}][price_override]" placeholder="—">
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <div class="form-check mb-0 pb-1">
                                                    <input type="checkbox" class="form-check-input" name="travel_dates[${nextIndex}][is_active]" value="1" checked>
                                                    <label class="form-check-label small">Actif</label>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-1 text-md-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-travel-date" aria-label="Supprimer">×</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                                container.insertAdjacentHTML('beforeend', html);
                            });
                            container.addEventListener('click', function(e){
                                if (e.target.classList.contains('remove-travel-date')) {
                                    var row = e.target.closest('.travel-date-row');
                                    if (row) row.remove();
                                }
                            });
})();

(function () {
    var manager = document.getElementById('departure-room-allocation-manager');
    var listEl = document.getElementById('departure-room-allocation-list');
    var summaryEl = document.getElementById('departure-room-allocation-summary');
    var travelDatesContainer = document.getElementById('travel-dates-container');
    var bootstrapEl = document.getElementById('departure-room-allocation-bootstrap');
    var roomTypesEl = document.getElementById('departure-room-allocation-room-types');

    if (!manager || !listEl || !summaryEl || !travelDatesContainer) return;
    console.log('[DepartureRoomAllocation] init ok', {
        hasManager: !!manager,
        hasTravelDates: !!travelDatesContainer,
        bootstrapLen: (bootstrapEl && bootstrapEl.textContent ? bootstrapEl.textContent.length : 0)
    });

    var roomTypeSuggestions = safeJsonParse(roomTypesEl, ['Single', 'Double', 'Twin', 'Triple', 'Quadruple', 'Family', 'Suite']);
    var bootstrapRows = safeJsonParse(bootstrapEl, []);
    var allocationState = {};
    var openState = {};
    var lastHotelsTopologyKey = '';

    bootstrapRows.forEach(function (row) {
        if (!row) return;
        var key = stateKey(row);
        if (!key) return;
        allocationState[key] = {
            departureId: row.departure_id || null,
            travelDateId: row.travel_date_id || null,
            date: row.date || '',
            targetCapacity: row.target_capacity == null ? null : Math.max(0, parseInt(String(row.target_capacity), 10) || 0),
            reservedCapacity: row.reserved_capacity == null ? null : Math.max(0, parseInt(String(row.reserved_capacity), 10) || 0),
            availableCapacity: row.available_capacity == null ? null : Math.max(0, parseInt(String(row.available_capacity), 10) || 0),
            departureStatusLabel: String(row.status_label || row.status || '').trim(),
            rooms: Array.isArray(row.rooms) ? row.rooms.map(normalizeRoomRow).filter(Boolean) : [],
            manual: !!row.manual
        };
    });

    function safeJsonParse(scriptEl, fallback) {
        if (!scriptEl) return fallback;
        try {
            return JSON.parse(scriptEl.textContent || '');
        } catch (e) {
            return fallback;
        }
    }

    function stateKey(row) {
        if (!row) return '';
        if (row.travel_date_id) return 'td:' + String(row.travel_date_id);
        if (row.date) return 'date:' + String(row.date);
        if (row.departure_id) return 'dep:' + String(row.departure_id);
        return '';
    }

    function normalizeRoomRow(row) {
        if (!row) return null;
        var roomType = String(row.room_type || '').trim();
        var quantity = parseInt(String(row.quantity || '0'), 10);
        var capacityPerRoom = parseInt(String(row.capacity_per_room || '1'), 10);
        var supplement = parseFloat(String(row.supplement || '0'));

        // Keep draft rows created by "Ajouter un type" even when empty,
        // otherwise they are immediately filtered out on re-render.
        if (!roomType && !quantity && !row.__draft) return null;

        return {
            __draft: !!row.__draft,
            room_type: roomType,
            quantity: isNaN(quantity) ? 0 : Math.max(0, quantity),
            capacity_per_room: isNaN(capacityPerRoom) ? 1 : Math.max(1, capacityPerRoom),
            supplement: isNaN(supplement) ? 0 : Math.max(0, supplement),
            hotel_id: row.hotel_id || '',
            hotel_index: row.hotel_index === '' || row.hotel_index == null ? '' : String(row.hotel_index)
        };
    }
    

    function buildFallbackRoomsForHotel(seats, hotel) {
        var safeSeats = Math.max(0, parseInt(String(seats || '0'), 10) || 0);
        if (safeSeats <= 0) return [];

        var hotelId = hotel && hotel.hotel_id ? String(hotel.hotel_id) : '';
        var hotelIndex = hotel && hotel.index != null ? String(hotel.index) : '';
        var rows = [];
        var doubles = Math.floor(safeSeats / 2);

        if (doubles > 0) {
            rows.push({
                room_type: 'Double',
                quantity: doubles,
                capacity_per_room: 2,
                hotel_id: hotelId,
                hotel_index: hotelIndex
            });
        }

        if (safeSeats % 2 === 1) {
            rows.push({
                room_type: 'Single',
                quantity: 1,
                capacity_per_room: 1,
                hotel_id: hotelId,
                hotel_index: hotelIndex
            });
        }

        return rows;
    }

    function defaultRooms(capacity, hotelsOverride) {
        var hotels = Array.isArray(hotelsOverride) ? hotelsOverride : collectHotels();
        var seats = Math.max(0, parseInt(String(capacity || '0'), 10) || 0);

        if (hotels.length) {
            var rows = [];

            hotels.forEach(function (hotel) {
                var hotelRows = [];
                var covered = 0;

                (hotel.rooms || []).forEach(function (room) {
                    var roomType = String(room.room_type || '').trim();
                    var quantity = Math.max(0, parseInt(String(room.room_count || '0'), 10) || 0);
                    var capacityPerRoom = Math.max(1, parseInt(String(room.capacity_per_room || '1'), 10) || 1);
                    if (!roomType || quantity <= 0 || capacityPerRoom <= 0) return;

                    covered += quantity * capacityPerRoom;
                    hotelRows.push({
                        room_type: roomType,
                        quantity: quantity,
                        capacity_per_room: capacityPerRoom,
                        hotel_id: hotel.hotel_id || '',
                        hotel_index: hotel.index
                    });
                });

                if (!hotelRows.length) {
                    hotelRows = buildFallbackRoomsForHotel(seats, hotel);
                } else if (seats > covered) {
                    hotelRows = hotelRows.concat(buildFallbackRoomsForHotel(seats - covered, hotel));
                }

                rows = rows.concat(hotelRows);
            });

            if (rows.length) {
                return rows;
            }
        }

        return buildFallbackRoomsForHotel(seats, null);
    }

    function buildHotelsTopologyKey(hotels) {
        return (hotels || []).map(function (hotel, index) {
            var hotelIndex = hotel && hotel.index != null ? String(hotel.index) : String(index);
            var hotelId = hotel && hotel.hotel_id != null ? String(hotel.hotel_id) : '';
            return hotelIndex + '::' + hotelId;
        }).join('|');
    }

    function buildUniqueHotelIndexByHotelId(hotels) {
        var counts = {};
        (hotels || []).forEach(function (hotel) {
            if (!hotel || hotel.hotel_id == null || String(hotel.hotel_id) === '') return;
            var key = String(hotel.hotel_id);
            counts[key] = (counts[key] || 0) + 1;
        });

        var unique = {};
        (hotels || []).forEach(function (hotel, idx) {
            if (!hotel || hotel.hotel_id == null || String(hotel.hotel_id) === '') return;
            var key = String(hotel.hotel_id);
            if ((counts[key] || 0) !== 1) return;
            var indexKey = hotel.index != null ? String(hotel.index) : String(idx);
            unique[key] = indexKey;
        });
        return unique;
    }

    function normalizeRoomsAssociations(rooms, hotels) {
        var normalized = Array.isArray(rooms) ? rooms.map(normalizeRoomRow).filter(Boolean) : [];
        if (!normalized.length || !Array.isArray(hotels) || !hotels.length) return normalized;

        var byHotelIndex = {};
        var uniqueHotelIndexByHotelId = buildUniqueHotelIndexByHotelId(hotels);
        hotels.forEach(function (hotel, idx) {
            if (!hotel) return;
            var hotelIndex = hotel.index != null ? String(hotel.index) : String(idx);
            var hotelId = hotel.hotel_id != null ? String(hotel.hotel_id) : '';
            byHotelIndex[hotelIndex] = { hotel_index: hotelIndex, hotel_id: hotelId };
        });

        var firstHotel = hotels[0];
        var firstIndex = firstHotel && firstHotel.index != null ? String(firstHotel.index) : '0';
        var firstId = firstHotel && firstHotel.hotel_id != null ? String(firstHotel.hotel_id) : '';

        return normalized.map(function (room) {
            var roomHotelId = room.hotel_id != null ? String(room.hotel_id) : '';
            var roomHotelIndex = room.hotel_index != null ? String(room.hotel_index) : '';

            if (roomHotelIndex !== '' && byHotelIndex[roomHotelIndex]) {
                room.hotel_index = byHotelIndex[roomHotelIndex].hotel_index;
                room.hotel_id = byHotelIndex[roomHotelIndex].hotel_id;
                return room;
            }

            if (roomHotelId !== '' && uniqueHotelIndexByHotelId[roomHotelId] && byHotelIndex[uniqueHotelIndexByHotelId[roomHotelId]]) {
                var resolved = byHotelIndex[uniqueHotelIndexByHotelId[roomHotelId]];
                room.hotel_index = resolved.hotel_index;
                room.hotel_id = resolved.hotel_id;
                return room;
            }

            if (hotels.length === 1) {
                room.hotel_index = firstIndex;
                room.hotel_id = firstId;
            } else {
                room.hotel_index = '';
                room.hotel_id = '';
            }

            return room;
        });
    }

    function collectTravelDates() {
        return Array.prototype.slice.call(travelDatesContainer.querySelectorAll('.travel-date-row')).map(function (row, index) {
            var idInput = row.querySelector('input[name$="[id]"]');
            var dateInput = row.querySelector('input[name$="[date]"]');
            var seatsInput = row.querySelector('input[name$="[seats]"]');
            var activeInput = row.querySelector('input[name$="[is_active]"]');
            var date = dateInput ? String(dateInput.value || '').trim() : '';
            var seats = seatsInput ? Math.max(0, parseInt(String(seatsInput.value || '0'), 10) || 0) : 0;
            var travelDateId = idInput && idInput.value !== '' ? parseInt(String(idInput.value), 10) || null : null;
            var isActive = activeInput ? !!activeInput.checked : true;
            return {
                index: index,
                travelDateId: travelDateId,
                date: date,
                seats: seats,
                isActive: isActive,
                label: formatDate(date)
            };
        }).filter(function (item) {
            return !!item.date;
        });
    }

    function formatDate(date) {
        if (!date) return 'Date sans valeur';
        var parts = String(date).split('-');
        if (parts.length !== 3) return date;
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function roomCoveredSeats(room) {
        var quantity = Math.max(0, parseInt(String((room || {}).quantity || '0'), 10) || 0);
        var capacityPerRoom = Math.max(1, parseInt(String((room || {}).capacity_per_room || '1'), 10) || 1);
        return quantity * capacityPerRoom;
    }

    function coverageStats(rooms) {
        var covered = 0;
        (rooms || []).forEach(function (room) {
            covered += roomCoveredSeats(room);
        });
        return covered;
    }

    function getStayCoverageState(expected, covered) {
        if (covered === expected) return 'exact';
        if (covered < expected) return 'under';
        return 'over';
    }

    function stayCoverageBadge(state) {
        if (state === 'exact') return 'bg-success-subtle text-success border border-success-subtle';
        if (state === 'under') return 'bg-warning-subtle text-warning border border-warning-subtle';
        return 'bg-danger-subtle text-danger border border-danger-subtle';
    }

    function buildDepartureCoverage(hotels, rooms, seats) {
        var expected = Math.max(0, parseInt(String(seats || '0'), 10) || 0);
        var byHotelIndex = {};
        var uniqueHotelIndexByHotelId = buildUniqueHotelIndexByHotelId(hotels);
        var stays = (hotels || []).map(function (hotel, idx) {
            var indexKey = hotel && hotel.index != null ? String(hotel.index) : String(idx);
            var stay = {
                index: indexKey,
                label: hotel && hotel.label ? String(hotel.label) : ('Sejour ' + (idx + 1)),
                expected: expected,
                covered: 0,
                diff: 0,
                state: 'exact'
            };
            byHotelIndex[indexKey] = stay;
            return stay;
        });

        var unassigned = 0;
        (rooms || []).forEach(function (room) {
            var covered = roomCoveredSeats(room);
            if (covered <= 0) return;

            var roomHotelId = room && room.hotel_id != null ? String(room.hotel_id) : '';
            var roomHotelIndex = room && room.hotel_index != null ? String(room.hotel_index) : '';
            var stay = null;
            if (roomHotelIndex !== '' && byHotelIndex[roomHotelIndex]) {
                stay = byHotelIndex[roomHotelIndex];
            } else if (roomHotelId !== '' && uniqueHotelIndexByHotelId[roomHotelId]) {
                var resolvedIndex = uniqueHotelIndexByHotelId[roomHotelId];
                stay = byHotelIndex[resolvedIndex] || null;
            }

            if (stay) {
                stay.covered += covered;
            } else {
                unassigned += covered;
            }
        });

        var exactCount = 0;
        var underCount = 0;
        var overCount = 0;
        stays.forEach(function (stay) {
            stay.diff = stay.covered - expected;
            stay.state = getStayCoverageState(expected, stay.covered);
            if (stay.state === 'exact') exactCount += 1;
            if (stay.state === 'under') underCount += 1;
            if (stay.state === 'over') overCount += 1;
        });

        var status = 'exact';
        if (!stays.length) {
            status = getStayCoverageState(expected, coverageStats(rooms));
        } else if (underCount > 0 || unassigned > 0) {
            status = 'under';
        } else if (overCount > 0) {
            status = 'over';
        }

        return {
            expected: expected,
            stays: stays,
            unassigned: unassigned,
            exactCount: exactCount,
            underCount: underCount,
            overCount: overCount,
            status: status,
            totalCovered: coverageStats(rooms)
        };
    }

    function departureCoverageStateClass(coverage) {
        if (!coverage) return 've-departure-status--under';
        if (coverage.status === 'exact') return 've-departure-status--exact';
        if (coverage.status === 'under') return 've-departure-status--under';
        return 've-departure-status--over';
    }

    function departureCoverageBadge(coverage) {
        if (!coverage) {
            return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Couverture a verifier</span>';
        }
        if (coverage.status === 'exact') {
            return '<span class="badge bg-success-subtle text-success border border-success-subtle">Sejours couverts</span>';
        }
        if (coverage.status === 'under') {
            return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Sejours incomplets</span>';
        }
        return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Sejours excedentaires</span>';
    }

    function renderStayCoverage(coverage) {
        if (!coverage || !coverage.stays || !coverage.stays.length) return '';

        var items = coverage.stays.map(function (stay, idx) {
            var title = escapeHtml(stay.label || ('Sejour ' + (idx + 1)));
            var diffText = stay.diff === 0 ? 'Ecart 0' : ('Ecart ' + (stay.diff > 0 ? '+' : '') + stay.diff);
            return '<span class="badge ' + stayCoverageBadge(stay.state) + '">' +
                title + ' : ' + stay.covered + '/' + stay.expected + ' (' + diffText + ')' +
            '</span>';
        });

        if (coverage.unassigned > 0) {
            items.push('<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Non associe : ' + coverage.unassigned + ' place(s)</span>');
        }

        return '<div class="d-flex flex-wrap gap-2 mb-2">' + items.join('') + '</div>';
    }

    function renderStayCoverageDetails(coverage) {
        if (!coverage || !coverage.stays || !coverage.stays.length) return '';

        var cards = coverage.stays.map(function (stay, idx) {
            var title = escapeHtml(stay.label || ('Sejour ' + (idx + 1)));
            var stateClass = stay.state === 'exact'
                ? 've-stay-breakdown__item--exact'
                : (stay.state === 'under' ? 've-stay-breakdown__item--under' : 've-stay-breakdown__item--over');
            var diffText = stay.diff === 0 ? 'Ecart 0' : ('Ecart ' + (stay.diff > 0 ? '+' : '') + stay.diff);

            return '' +
                '<article class="ve-stay-breakdown__item ' + stateClass + '">' +
                '  <header class="ve-stay-breakdown__head">' +
                '    <span class="ve-stay-breakdown__title">' + title + '</span>' +
                '    <strong class="ve-stay-breakdown__ratio">' + stay.covered + '/' + stay.expected + '</strong>' +
                '  </header>' +
                '  <div class="ve-stay-breakdown__meta">' + diffText + '</div>' +
                '</article>';
        });

        if (coverage.unassigned > 0) {
            cards.push(
                '<article class="ve-stay-breakdown__item ve-stay-breakdown__item--unassigned">' +
                '  <header class="ve-stay-breakdown__head">' +
                '    <span class="ve-stay-breakdown__title">Non associe</span>' +
                '    <strong class="ve-stay-breakdown__ratio">' + coverage.unassigned + '</strong>' +
                '  </header>' +
                '  <div class="ve-stay-breakdown__meta">Places sans sejour</div>' +
                '</article>'
            );
        }

        return '<div class="ve-stay-breakdown mb-3">' + cards.join('') + '</div>';
    }

    function collectHotels() {
        var bootstrapHotels = window.tourHotelsData || {};

        function effectiveCapacity(room) {
            var total = Math.max(0, parseInt(String((room || {}).capacity_total || '0'), 10) || 0);
            if (total > 0) return total;
            var adults = Math.max(0, parseInt(String((room || {}).capacity_adults || '0'), 10) || 0);
            var children = Math.max(0, parseInt(String((room || {}).capacity_children || '0'), 10) || 0);
            return Math.max(0, adults + children);
        }

        function roomsForRow(hotelId, index) {
            var key = hotelId ? String(hotelId) : '';
            var source = key && bootstrapHotels[key] ? bootstrapHotels[key] : null;
            if (!source) {
                var fallback = Object.values(bootstrapHotels || {});
                source = fallback[index] || null;
            }
            var sourceRooms = source && Array.isArray(source.rooms) ? source.rooms : [];
            return sourceRooms
                .filter(function (room) {
                    return !!room && (room.is_active === undefined || !!room.is_active);
                })
                .map(function (room) {
                    var availByTravelDateId = {};
                    if (room && Array.isArray(room.date_availabilities)) {
                        room.date_availabilities.forEach(function (a) {
                            if (!a) return;
                            var td = parseInt(String(a.travel_date_id || '0'), 10) || 0;
                            if (td <= 0) return;
                            availByTravelDateId[String(td)] = Math.max(0, parseInt(String(a.available_rooms || '0'), 10) || 0);
                        });
                    }
                    return {
                        room_type: String(room.room_type || '').trim(),
                        room_count: Math.max(0, parseInt(String(room.room_count || '0'), 10) || 0),
                        capacity_per_room: Math.max(1, effectiveCapacity(room)),
                        available_rooms_by_travel_date_id: availByTravelDateId
                    };
                })
                .filter(function (room) {
                    return room.room_type !== '' && room.room_count > 0 && room.capacity_per_room > 0;
                });
        }

        return Array.prototype.slice.call(document.querySelectorAll('#tour-hotels-container .tour-hotel-row')).map(function (row, index) {
            var linkedHotelIdInput = row.querySelector('input[name^="tour_hotels["][name$="[hotel_id]"]');
            var sourceHotelIdInput = row.querySelector('input[name^="tour_hotels["][name$="[source_hotel_id]"]');
            var nameInput = row.querySelector('.tour-hotel-name-input');
            var checkInInput = row.querySelector('.tour-hotel-check-in');
            var checkOutInput = row.querySelector('.tour-hotel-check-out');
            var hotelId = '';
            if (linkedHotelIdInput && linkedHotelIdInput.value !== '') {
                hotelId = String(linkedHotelIdInput.value);
            } else if (sourceHotelIdInput && sourceHotelIdInput.value !== '') {
                hotelId = String(sourceHotelIdInput.value);
            }
            var name = nameInput && nameInput.value.trim() ? nameInput.value.trim() : 'Hotel ' + (index + 1);
            var stay = '';
            if (checkInInput && checkOutInput) {
                var checkIn = parseInt(checkInInput.value || '1', 10);
                var checkOut = parseInt(checkOutInput.value || '1', 10);
                stay = checkIn === checkOut ? 'Jour ' + checkIn : 'J' + checkIn + ' a J' + checkOut;
            }
            return {
                index: String(row.getAttribute('data-index') || index),
                hotel_id: hotelId,
                label: stay ? name + ' - ' + stay : name,
                rooms: roomsForRow(hotelId, index)
            };
        });
    }

    function hotelSelectOptions(selectedIndex, selectedHotelId) {
        var hotels = collectHotels();
        var uniqueHotelIndexByHotelId = buildUniqueHotelIndexByHotelId(hotels);
        var hasSelection = false;
        var selectedLabel = '';
        var options = ['<option value="">Aucun hotel</option>'];
        var autoSelectSingle = selectedHotelId === '' && selectedIndex === '' && hotels.length === 1;
        var autoSelectedDone = false;
        var normalizedSelectedIndex = selectedIndex == null ? '' : String(selectedIndex);
        var normalizedSelectedHotelId = selectedHotelId == null ? '' : String(selectedHotelId);
        var selectedStayIndex = '';
        if (normalizedSelectedIndex !== '') {
            selectedStayIndex = normalizedSelectedIndex;
        } else if (normalizedSelectedHotelId !== '' && uniqueHotelIndexByHotelId[normalizedSelectedHotelId]) {
            selectedStayIndex = uniqueHotelIndexByHotelId[normalizedSelectedHotelId];
        }

        hotels.forEach(function (hotel) {
            var selected = selectedStayIndex !== '' && selectedStayIndex === String(hotel.index);
            if (!selected && autoSelectSingle && !autoSelectedDone) {
                selected = true;
                autoSelectedDone = true;
            }
            if (selected) {
                hasSelection = true;
                selectedLabel = hotel.label || '';
            }
            options.push('<option value="' + escapeAttr(hotel.index) + '" data-hotel-id="' + escapeAttr(hotel.hotel_id) + '"' + (selected ? ' selected' : '') + '>' + escapeHtml(hotel.label) + '</option>');
        });

        return {
            html: options.join(''),
            hasSelection: hasSelection,
            selectedLabel: selectedLabel
        };
    }

    function roomOptionsMarkup(selected) {
        return roomTypeSuggestions.map(function (type) {
            var isSelected = String(selected || '').trim().toLowerCase() === String(type).toLowerCase();
            return '<option value="' + escapeHtml(type) + '"' + (isSelected ? ' selected' : '') + '></option>';
        }).join('');
    }

    function captureOpenState() {
        listEl.querySelectorAll('details[data-allocation-key]').forEach(function (details) {
            openState[details.getAttribute('data-allocation-key')] = !!details.open;
        });
    }

    function render() {
        captureOpenState();
        var travelDates = collectTravelDates().filter(function (item) { return item.isActive; });
        var hotels = collectHotels();
        var hotelsTopologyKey = buildHotelsTopologyKey(hotels);
        var hotelsTopologyChanged = lastHotelsTopologyKey !== '' && hotelsTopologyKey !== lastHotelsTopologyKey;
        var activeKeys = {};

        if (!travelDates.length) {
            summaryEl.innerHTML = '';
            listEl.innerHTML = '<div class="alert alert-warning mb-0">Ajoutez au moins une date active avec un nombre de places dans l’onglet Disponibilité.</div>';
            return;
        }

        var viewRows = travelDates.map(function (travelDate, index) {
            var key = travelDate.travelDateId ? 'td:' + String(travelDate.travelDateId) : 'date:' + travelDate.date;
            activeKeys[key] = true;

            if (!allocationState[key]) {
                allocationState[key] = {
                    departureId: null,
                    travelDateId: travelDate.travelDateId,
                    date: travelDate.date,
                    targetCapacity: travelDate.seats,
                    reservedCapacity: null,
                    availableCapacity: null,
                    departureStatusLabel: '',
                    rooms: defaultRooms(travelDate.seats, hotels),
                    manual: false
                };
            } else {
                allocationState[key].travelDateId = travelDate.travelDateId;
                allocationState[key].date = travelDate.date;
                allocationState[key].targetCapacity = travelDate.seats;
                allocationState[key].rooms = normalizeRoomsAssociations(allocationState[key].rooms, hotels);
                if (hotelsTopologyChanged) {
                    allocationState[key].rooms = defaultRooms(travelDate.seats, hotels);
                    allocationState[key].manual = false;
                } else if (!allocationState[key].manual) {
                    allocationState[key].rooms = defaultRooms(travelDate.seats, hotels);
                }
            }

            var state = allocationState[key];
            var rooms = Array.isArray(state.rooms) ? state.rooms : [];
            var expectedCapacity = state.targetCapacity == null
                ? travelDate.seats
                : Math.max(0, parseInt(String(state.targetCapacity), 10) || 0);
            var coverage = buildDepartureCoverage(hotels, rooms, expectedCapacity);

            return {
                key: key,
                index: index,
                travelDate: travelDate,
                state: state,
                expectedCapacity: expectedCapacity,
                rooms: rooms,
                coverage: coverage
            };
        });

        renderSummary(viewRows);
        listEl.innerHTML = viewRows.map(function (row) {
            return '' +
                '<details class="card border mb-3 ve-departure-card ' + departureCoverageStateClass(row.coverage) + '" data-allocation-key="' + escapeHtml(row.key) + '"' + (openState[row.key] ? ' open' : '') + '>' +
                '  <summary class="card-body ve-departure-card__summary">' +
                '    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">' +
                '      <div>' +
                '        <h6 class="mb-1">Depart du ' + escapeHtml(row.travelDate.label) + '</h6>' +
                '        <div class="text-muted small">Capacite de depart (source): <strong>' + row.expectedCapacity + '</strong> place(s)</div>' +
                '        <div class="text-muted small">Reservations: <strong>' + (row.state.reservedCapacity == null ? '—' : row.state.reservedCapacity) + '</strong> · Disponibles: <strong>' + (row.state.availableCapacity == null ? '—' : row.state.availableCapacity) + '</strong>' + (row.state.departureStatusLabel ? (' · Statut: <strong>' + escapeHtml(row.state.departureStatusLabel) + '</strong>') : '') + '</div>' +
                '      </div>' +
                '      <div class="text-end">' +
                '        <div class="small mb-1">Sejours exacts : <strong>' + row.coverage.exactCount + '/' + row.coverage.stays.length + '</strong></div>' +
                '        ' + departureCoverageBadge(row.coverage) +
                (row.coverage.unassigned > 0 ? ('<div class="text-muted small mt-1">Non associe : ' + row.coverage.unassigned + ' place(s)</div>') : '') +
                '      </div>' +
                '    </div>' +
                '  </summary>' +
                '  <div class="card-body pt-0">' +
                '    <input type="hidden" name="departure_allocations[' + row.index + '][departure_id]" value="' + escapeAttr(row.state.departureId || '') + '">' +
                '    <input type="hidden" name="departure_allocations[' + row.index + '][travel_date_id]" value="' + escapeAttr(row.travelDate.travelDateId || '') + '">' +
                '    <input type="hidden" name="departure_allocations[' + row.index + '][date]" value="' + escapeAttr(row.travelDate.date) + '">' +
                renderStayCoverage(row.coverage) +
                renderStayCoverageDetails(row.coverage) +
                '    <div class="table-responsive">' +
                '      <table class="table table-sm align-middle mb-2">' +
                '        <thead><tr><th>Type</th><th>Quantite</th><th>Cap./chambre</th><th>Suppl. (DH/pers)</th><th>Sejour</th><th>Places couvertes</th><th></th></tr></thead>' +
                '        <tbody>' + renderRooms(row.index, row.rooms, row.travelDate) + '</tbody>' +
                '      </table>' +
                '    </div>' +
                '    <div class="d-flex flex-wrap gap-2">' +
                '      <button type="button" class="btn btn-sm btn-outline-primary" data-allocation-action="add-room" data-allocation-key="' + escapeAttr(row.key) + '">Ajouter un type</button>' +
                '      <button type="button" class="btn btn-sm btn-outline-secondary" data-allocation-action="regenerate" data-allocation-key="' + escapeAttr(row.key) + '">Generer la repartition par defaut</button>' +
                '    </div>' +
                '  </div>' +
                '</details>';
        }).join('');

        Object.keys(allocationState).forEach(function (key) {
            if (!activeKeys[key]) delete allocationState[key];
        });
        lastHotelsTopologyKey = hotelsTopologyKey;
    }

    function renderSummary(viewRows) {
        summaryEl.innerHTML = viewRows.map(function (row) {
            var summaryMeta = [];
            if (row.coverage.underCount > 0) summaryMeta.push('Incomplets ' + row.coverage.underCount);
            if (row.coverage.overCount > 0) summaryMeta.push('Excedentaires ' + row.coverage.overCount);
            if (row.coverage.unassigned > 0) summaryMeta.push('Non associe ' + row.coverage.unassigned);
            return '' +
                '<div class="col-xl-3 col-md-6">' +
                '  <div class="ve-departure-summary ' + departureCoverageStateClass(row.coverage) + '">' +
                '    <div class="ve-departure-summary__date">' + escapeHtml(row.travelDate.label) + '</div>' +
                '    <div class="ve-departure-summary__stats">' +
                '      <span>Capacite depart <strong>' + row.expectedCapacity + '</strong></span>' +
                '      <span>Exacts <strong>' + row.coverage.exactCount + '/' + row.coverage.stays.length + '</strong></span>' +
                '    </div>' +
                '    <div class="ve-departure-summary__meta">' +
                (summaryMeta.length ? summaryMeta.join(' | ') : 'Tous les sejours sont exacts') +
                (row.state.departureStatusLabel ? (' | Statut ' + row.state.departureStatusLabel) : '') +
                '</div>' +
                '    <div class="mt-2">' + departureCoverageBadge(row.coverage) + '</div>' +
                '  </div>' +
                '</div>';
        }).join('');
    }

    function renderRooms(cardIndex, rooms, travelDate) {
        if (!rooms.length) {
            return '<tr><td colspan="6" class="text-muted small">Aucune chambre configuree pour ce depart.</td></tr>';
        }

        return rooms.map(function (room, roomIndex) {
            var quantity = parseInt(String(room.quantity || '0'), 10) || 0;
            var capacityPerRoom = parseInt(String(room.capacity_per_room || '1'), 10) || 1;
            var supplement = parseFloat(String(room.supplement || '0')) || 0;
            var hotelId = room.hotel_id == null ? '' : String(room.hotel_id);
            var hotelIndex = room.hotel_index == null ? '' : String(room.hotel_index);
            var hotelOptions = hotelSelectOptions(hotelIndex, hotelId);
            var availableRoomsInfo = resolveRoomStockForSelection(travelDate, room, hotelOptions);
            var availableRooms = availableRoomsInfo.availableRooms;
            var stockLabel = availableRooms == null ? '—' : String(availableRooms);
            var stockHelp = availableRoomsInfo.sourceLabel ? ('<div class="small text-muted mt-1">' + escapeHtml(availableRoomsInfo.sourceLabel) + '</div>') : '';
            var invalidQty = availableRooms != null && quantity > availableRooms;
            return '' +
                '<tr data-room-index="' + roomIndex + '">' +
                '  <td>' +
                '    <input class="form-control form-control-sm" list="departure-room-type-options" name="departure_allocations[' + cardIndex + '][rooms][' + roomIndex + '][room_type]" value="' + escapeAttr(room.room_type || '') + '" placeholder="Double">' +
                '  </td>' +
                '  <td><input type="number" class="form-control form-control-sm ' + (invalidQty ? 'is-invalid' : '') + '" min="0"' + (availableRooms != null ? (' max="' + String(availableRooms) + '"') : '') + ' name="departure_allocations[' + cardIndex + '][rooms][' + roomIndex + '][quantity]" value="' + quantity + '">' +
                (invalidQty ? ('<div class="invalid-feedback">Max ' + escapeHtml(String(availableRooms)) + ' chambre(s) disponible(s).</div>') : '') +
                '  </td>' +
                '  <td><input type="number" class="form-control form-control-sm" min="1" name="departure_allocations[' + cardIndex + '][rooms][' + roomIndex + '][capacity_per_room]" value="' + capacityPerRoom + '"></td>' +
                '  <td><input type="number" class="form-control form-control-sm" min="0" step="0.01" name="departure_allocations[' + cardIndex + '][rooms][' + roomIndex + '][supplement]" value="' + escapeAttr(String(supplement)) + '" placeholder="0"></td>' +
                '  <td>' +
                '    <input type="hidden" name="departure_allocations[' + cardIndex + '][rooms][' + roomIndex + '][hotel_id]" value="' + escapeAttr(hotelId) + '">' +
                '    <select class="form-select form-select-sm" name="departure_allocations[' + cardIndex + '][rooms][' + roomIndex + '][hotel_index]">' + hotelOptions.html + '</select>' +
                (hotelOptions.hasSelection
                    ? '<div class="small text-muted mt-1">Sejour: ' + escapeHtml(hotelOptions.selectedLabel || '') + '</div>'
                    : '<div class="small text-warning mt-1">Associer un sejour.</div>') +
                '    <div class="small text-muted mt-1">Dispo: <strong>' + escapeHtml(stockLabel) + '</strong></div>' +
                stockHelp +
                '  </td>' +
                '  <td><span class="small fw-semibold">' + (quantity * capacityPerRoom) + '</span></td>' +
                '  <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-allocation-action="remove-room" data-room-index="' + roomIndex + '">×</button></td>' +
                '</tr>';
        }).join('');
    }

    function resolveRoomStockForSelection(travelDate, room, hotelOptions) {
        var hotels = collectHotels();
        var roomType = String((room || {}).room_type || '').trim().toLowerCase();
        if (!roomType) return { availableRooms: null, sourceLabel: '' };

        var stayIndex = (room && room.hotel_index != null && String(room.hotel_index) !== '') ? String(room.hotel_index) : '';
        if (!stayIndex && hotelOptions && hotelOptions.hasSelection) {
            // hotelSelectOptions already resolved selection; we can read it back from option HTML only via state,
            // so fallback is handled by matching hotel_id when unique.
        }

        var hotelId = room && room.hotel_id != null ? String(room.hotel_id) : '';
        var selectedHotel = null;
        if (stayIndex) {
            selectedHotel = hotels.find(function (h) { return String(h.index) === stayIndex; }) || null;
        } else if (hotelId) {
            var unique = buildUniqueHotelIndexByHotelId(hotels);
            var mapped = unique[hotelId] ? String(unique[hotelId]) : '';
            if (mapped) selectedHotel = hotels.find(function (h) { return String(h.index) === mapped; }) || null;
        }

        if (!selectedHotel || !Array.isArray(selectedHotel.rooms)) {
            return { availableRooms: null, sourceLabel: '' };
        }

        var matched = selectedHotel.rooms.find(function (r) {
            return String(r.room_type || '').trim().toLowerCase() === roomType;
        }) || null;
        if (!matched) return { availableRooms: null, sourceLabel: '' };

        var base = Math.max(0, parseInt(String(matched.room_count || '0'), 10) || 0);
        var tdId = travelDate && travelDate.travelDateId ? String(travelDate.travelDateId) : '';
        var byDate = matched.available_rooms_by_travel_date_id || {};
        if (tdId && Object.prototype.hasOwnProperty.call(byDate, tdId)) {
            return { availableRooms: Math.max(0, parseInt(String(byDate[tdId] || '0'), 10) || 0), sourceLabel: 'stock par date' };
        }
        return { availableRooms: base, sourceLabel: 'stock global' };
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escapeAttr(value) {
        return escapeHtml(value);
    }

    function markManual(key) {
        if (allocationState[key]) allocationState[key].manual = true;
    }

    manager.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-allocation-action]');
        if (!btn) return;
        event.preventDefault();
        event.stopPropagation();

        var key = btn.getAttribute('data-allocation-key') || btn.closest('[data-allocation-key]') && btn.closest('[data-allocation-key]').getAttribute('data-allocation-key');
        if (!key) {
            console.warn('[DepartureRoomAllocation] Click without key', btn);
            return;
        }
        if (!allocationState[key]) {
            console.warn('[DepartureRoomAllocation] Unknown allocation key', key, Object.keys(allocationState || {}));
            alert('Impossible d’ajouter un type: depart introuvable (rechargez la page).');
            return;
        }

        if (btn.getAttribute('data-allocation-action') === 'add-room') {
            var hotels = collectHotels();
            var defaultHotel = hotels.length ? hotels[0] : null;
            allocationState[key].rooms.push({
                __draft: true,
                room_type: '',
                quantity: 0,
                capacity_per_room: 1,
                hotel_id: defaultHotel && defaultHotel.hotel_id ? String(defaultHotel.hotel_id) : '',
                hotel_index: defaultHotel && defaultHotel.index != null ? String(defaultHotel.index) : ''
            });
            allocationState[key].manual = true;
            openState[key] = true;
            try {
                render();
            } catch (e) {
                console.error('[DepartureRoomAllocation] render failed after add-room', e);
                alert('Erreur JS lors de l’ajout d’un type (voir console).');
            }
            return;
        }

        if (btn.getAttribute('data-allocation-action') === 'regenerate') {
            var travelDate = collectTravelDates().find(function (item) {
                return (item.travelDateId && ('td:' + String(item.travelDateId)) === key) || ('date:' + item.date) === key;
            });
            allocationState[key].rooms = defaultRooms(travelDate ? travelDate.seats : 0, collectHotels());
            allocationState[key].manual = false;
            openState[key] = true;
            try {
                render();
            } catch (e) {
                console.error('[DepartureRoomAllocation] render failed after regenerate', e);
                alert('Erreur JS lors de la regeneration (voir console).');
            }
            return;
        }

        if (btn.getAttribute('data-allocation-action') === 'remove-room') {
            var roomIndex = parseInt(String(btn.getAttribute('data-room-index') || '-1'), 10);
            if (roomIndex >= 0) {
                allocationState[key].rooms.splice(roomIndex, 1);
                allocationState[key].manual = true;
                openState[key] = true;
                try {
                    render();
                } catch (e) {
                    console.error('[DepartureRoomAllocation] render failed after remove-room', e);
                    alert('Erreur JS lors de la suppression (voir console).');
                }
            }
        }
    });

    manager.addEventListener('change', function (event) {
        var field = event.target;
        var card = field.closest('[data-allocation-key]');
        if (!field.name || !card) return;
        var key = card.getAttribute('data-allocation-key');
        if (!key || !allocationState[key]) return;

        var roomMatch = field.name.match(/\[rooms\]\[(\d+)\]\[(room_type|quantity|capacity_per_room|supplement|hotel_index)\]$/);
        if (!roomMatch) return;

        var roomIndex = parseInt(roomMatch[1], 10);
        var prop = roomMatch[2];
        if (!allocationState[key].rooms[roomIndex]) return;

        allocationState[key].rooms[roomIndex][prop] = field.value;
        if (prop === 'hotel_index') {
            var selectedOption = field.options[field.selectedIndex];
            allocationState[key].rooms[roomIndex].hotel_id = selectedOption ? (selectedOption.getAttribute('data-hotel-id') || '') : '';
        }
        markManual(key);
        openState[key] = true;
        render();
    });

    manager.addEventListener('input', function (event) {
        var field = event.target;
        var card = field.closest('[data-allocation-key]');
        if (!field.name || !card) return;
        if (!/\[rooms\]\[\d+\]\[(room_type|quantity|capacity_per_room)\]$/.test(field.name)) return;
        var key = card.getAttribute('data-allocation-key');
        if (key) openState[key] = true;
        event.stopPropagation();
    });

    var pendingRender = null;
    function scheduleRender() {
        if (pendingRender) window.clearTimeout(pendingRender);
        pendingRender = window.setTimeout(function () {
            pendingRender = null;
            render();
        }, 0);
    }

    document.addEventListener('input', function (event) {
        if (event.target && event.target.name && event.target.name.indexOf('travel_dates[') === 0) {
            scheduleRender();
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target && event.target.name && event.target.name.indexOf('travel_dates[') === 0) {
            scheduleRender();
        }
        if (event.target && event.target.name && event.target.name.indexOf('tour_hotels[') === 0) {
            scheduleRender();
        }
    });

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target) return;
        if (target.id === 'add-travel-date' || target.classList.contains('remove-travel-date')) {
            scheduleRender();
        }
        if (target.id === 'tour-add-hotel' || target.classList.contains('tour-remove-row')) {
            scheduleRender();
        }
    });

    document.addEventListener('input', function (event) {
        if (event.target && event.target.name && event.target.name.indexOf('tour_hotels[') === 0) {
            scheduleRender();
        }
    });

    document.addEventListener('voyage-hotels-changed', scheduleRender);

    var datalist = document.createElement('datalist');
    datalist.id = 'departure-room-type-options';
    datalist.innerHTML = roomOptionsMarkup('');
    manager.appendChild(datalist);

render();
})();

(function() {
                var boot = window.VOYAGE_EDIT_BOOTSTRAP || {};
                var voyageForm = document.getElementById('edit-voyage-form');
                var v2Page = document.querySelector('.v2-page');
                var heroUploadUrl = boot.heroUploadUrl || '';
                var heroSelectUrl = boot.heroSelectUrl || '';
                var heroRemoveUrl = boot.heroRemoveUrl || '';
                var wpMediaSearchUrl = boot.wpMediaSearchUrl || '';
                var wpFeaturedMediaListUrl = boot.wpFeaturedMediaListUrl || '';
                var wpFeaturedMediaUploadUrl = boot.wpFeaturedMediaUploadUrl || '';
                var wpFeaturedMediaSelectUrl = boot.wpFeaturedMediaSelectUrl || '';
                var wpFeaturedMediaRemoveUrl = boot.wpFeaturedMediaRemoveUrl || '';
                var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
                var heroPreview = document.getElementById('hero-image-preview');
                var heroPreviewWrap = document.getElementById('hero-image-preview-wrap');
                var heroInput = document.getElementById('hero_image_id');
                var heroFileInput = document.getElementById('hero_image_file');
                var wpFeaturedHiddenInput = document.getElementById('thumbnail_id');
                var wpFeaturedReadonlyInput = document.getElementById('wp_featured_image_id');
                var wpFeaturedPreview = document.getElementById('wp-featured-preview');
                var wpFeaturedPreviewWrap = document.getElementById('wp-featured-preview-wrap');
                var wpFeaturedRemoveBtn = document.getElementById('wp-featured-remove-btn');
                function parsePositiveInt(value) {
                    var parsed = parseInt(String(value || '0'), 10) || 0;
                    return parsed > 0 ? parsed : 0;
                }

                function getCurrentWpTourId() {
                    var fromForm = voyageForm ? parsePositiveInt(voyageForm.getAttribute('data-voyage-id')) : 0;
                    var fromV2 = v2Page ? parsePositiveInt(v2Page.getAttribute('data-v2-initial-id')) : 0;
                    var fromBoot = parsePositiveInt(boot.wpTourId);
                    var id = fromForm || fromV2 || fromBoot || 0;

                    if (id > 0) {
                        boot.wpTourId = id;
                        window.PROGRAM_VOYAGE_ID = id;
                    }

                    return id;
                }

                function withCurrentTourId(url) {
                    var raw = String(url || '');
                    var currentId = getCurrentWpTourId();
                    if (!raw || currentId <= 0) return raw;

                    return raw.replace(/(\/circuits\/voyages\/)\d+(\/hero-image(?:\/select|\/remove)?)/, function (m, prefix, suffix) {
                        return prefix + currentId + suffix;
                    });
                }

                function notifySaveFirst() {
                    alert("Veuillez d'abord enregistrer le voyage avant de gerer les images.");
                }

                function setHeroPreview(url, id) {
                    if (heroInput) heroInput.value = id || '';
                    if (heroPreview) heroPreview.src = url || '';
                    if (heroPreviewWrap) heroPreviewWrap.style.display = (url ? 'block' : 'none');
                }

                if (document.getElementById('hero-upload-btn')) {
                    document.getElementById('hero-upload-btn').addEventListener('click', function() { heroFileInput && heroFileInput.click(); });
                }
                if (heroFileInput) {
                    heroFileInput.addEventListener('change', function() {
                        if (getCurrentWpTourId() <= 0) { heroFileInput.value = ''; notifySaveFirst(); return; }
                        if (!this.files || !this.files[0]) return;
                        var file = this.files[0];
                        var errEl = document.getElementById('hero-upload-error');
                        function showError(msg) {
                            if (errEl) { errEl.textContent = msg || 'Erreur lors de l\'upload.'; errEl.classList.remove('d-none'); }
                            else { alert(msg || 'Erreur lors de l\'upload.'); }
                        }
                        function hideError() { if (errEl) { errEl.textContent = ''; errEl.classList.add('d-none'); } }
                        if (!csrfToken) { showError('Token de sécurité manquant. Rechargez la page.'); heroFileInput.value = ''; return; }
                        hideError();
                        var formData = new FormData();
                        formData.append('hero_image', file);
                        formData.append('_token', csrfToken);
                        fetch(withCurrentTourId(heroUploadUrl), {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        }).then(function(res) {
                            return res.json().then(function(r) { return { ok: res.ok, status: res.status, data: r }; }).catch(function() {
                                return { ok: false, status: res.status, data: { message: res.status === 419 ? 'Session expirée. Rechargez la page puis réessayez.' : 'Réponse serveur invalide.' } };
                            });
                        }).then(function(result) {
                            heroFileInput.value = '';
                            if (result.ok && result.data && result.data.success) {
                                setHeroPreview(result.data.url, result.data.attachment_id);
                            } else {
                                var msg = (result.data && result.data.message) || (result.data && result.data.errors && result.data.errors.hero_image && result.data.errors.hero_image[0]) || 'Erreur lors de l\'upload.';
                                showError(msg);
                            }
                        }).catch(function() {
                            heroFileInput.value = '';
                            showError('Erreur réseau ou serveur. Vérifiez votre connexion.');
                        });
                    });
                }

                if (document.getElementById('hero-remove-btn')) {
                    document.getElementById('hero-remove-btn').addEventListener('click', function() {
                        if (getCurrentWpTourId() <= 0) { notifySaveFirst(); return; }
                        if (!confirm('Retirer l\'image principale ?')) return;
                        var fd = new FormData();
                        if (csrfToken) fd.append('_token', csrfToken);
                        fetch(withCurrentTourId(heroRemoveUrl), {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' }
                        }).then(function(r) { return r.json(); }).then(function(r) { if (r.success) setHeroPreview('', ''); });
                    });
                }

                var mediaModal = document.getElementById('hero-media-modal');
                var mediaSearch = document.getElementById('hero-media-search');
                var mediaResults = document.getElementById('hero-media-results');
                var mediaLoading = document.getElementById('hero-media-loading');
                var mediaPag = document.getElementById('hero-media-pagination');
                var mediaPage = 1;

                function getModalInstance(modalEl) {
                    if (!modalEl || !window.bootstrap || !bootstrap.Modal) {
                        return null;
                    }

                    return bootstrap.Modal.getOrCreateInstance(modalEl);
                }

                function hideModal(modalEl) {
                    var instance = getModalInstance(modalEl);
                    if (instance) {
                        instance.hide();
                    }
                }

                function loadMediaSearch(page) {
                    page = page || 1;
                    var q = (mediaSearch && mediaSearch.value) || '';
                    if (mediaLoading) mediaLoading.classList.remove('d-none');
                    if (mediaResults) mediaResults.innerHTML = '';
                    var url = wpMediaSearchUrl + '?page=' + page + '&per_page=24';
                    if (q) url += '&q=' + encodeURIComponent(q);
                    fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (mediaLoading) mediaLoading.classList.add('d-none');
                            if (!data.data || !data.data.length) {
                                if (mediaResults) mediaResults.innerHTML = '<div class="col-12 text-muted">Aucune image.</div>';
                            } else {
                                data.data.forEach(function(item) {
                                    var col = document.createElement('div');
                                    col.className = 'col-6 col-md-4 col-lg-3';
                                    col.innerHTML = '<div class="card h-100 cursor-pointer hero-media-item" data-id="' + item.id + '" data-url="' + (item.url || '') + '"><img src="' + (item.url || '') + '" class="card-img-top" style="height:120px;object-fit:cover" alt=""></div>';
                                    col.querySelector('.hero-media-item').addEventListener('click', function() {
                                        var id = this.getAttribute('data-id');
                                        var url = this.getAttribute('data-url');
                                        if (window.logistiqueMediaTarget) {
                                            var t = window.logistiqueMediaTarget;
                                            var inp = document.getElementById(t.inputId);
                                            var prev = document.getElementById(t.previewId);
                                            var wrap = document.getElementById(t.previewWrapId);
                                            if (inp) inp.value = id;
                                            if (prev) prev.src = url || '';
                                            if (wrap) wrap.style.display = 'flex';
                                            hideModal(mediaModal);
                                            window.logistiqueMediaTarget = null;
                                        } else {
                                            var fd = new FormData();
                                            fd.append('attachment_id', id);
                                            if (csrfToken) fd.append('_token', csrfToken);
                                            fetch(withCurrentTourId(heroSelectUrl), { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' } })
                                                .then(function(r) { return r.json(); })
                                                .then(function(r) {
                                                    if (r.success) { setHeroPreview(r.url, r.attachment_id); hideModal(mediaModal); }
                                                });
                                        }
                                    });
                                    mediaResults.appendChild(col);
                                });
                            }
                            if (data.last_page > 1 && mediaPag) {
                                mediaPag.classList.remove('d-none');
                                mediaPag.innerHTML = '<ul class="pagination pagination-sm mb-0"><li class="page-item' + (data.current_page <= 1 ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (data.current_page - 1) + '">Préc.</a></li><li class="page-item"><span class="page-link">' + data.current_page + ' / ' + data.last_page + '</span></li><li class="page-item' + (data.current_page >= data.last_page ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (data.current_page + 1) + '">Suiv.</a></li></ul>';
                                mediaPag.querySelectorAll('a[data-page]').forEach(function(a) {
                                    a.addEventListener('click', function(e) { e.preventDefault(); loadMediaSearch(parseInt(this.getAttribute('data-page'), 10)); });
                                });
                            } else if (mediaPag) mediaPag.classList.add('d-none');
                        })
                        .catch(function() { if (mediaLoading) mediaLoading.classList.add('d-none'); if (mediaResults) mediaResults.innerHTML = '<div class="col-12 text-danger">Erreur chargement.</div>'; });
                }

                window.logistiqueMediaTarget = null;
                if (document.getElementById('hero-choose-media-btn')) {
                    document.getElementById('hero-choose-media-btn').addEventListener('click', function() {
                        if (getCurrentWpTourId() <= 0) { notifySaveFirst(); return; }
                        window.logistiqueMediaTarget = null;
                        var mediaModalInstance = getModalInstance(mediaModal);
                        if (mediaModalInstance) {
                            mediaModalInstance.show();
                            loadMediaSearch(1);
                        }
                    });
                }
                function bindLogistiqueMediaButtons() {
                    document.querySelectorAll('.ajtb-logistique-media-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            window.logistiqueMediaTarget = {
                                inputId: this.getAttribute('data-input'),
                                previewId: this.getAttribute('data-preview'),
                                previewWrapId: this.getAttribute('data-preview-wrap')
                            };
                            var mediaModalInstance = getModalInstance(mediaModal);
                            if (mediaModalInstance) {
                                mediaModalInstance.show();
                                loadMediaSearch(1);
                            }
                        });
                    });
                    document.querySelectorAll('.ajtb-logistique-media-remove').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var inp = document.getElementById(this.getAttribute('data-input'));
                            var prev = document.getElementById(this.getAttribute('data-preview'));
                            var wrap = document.getElementById(this.getAttribute('data-preview-wrap'));
                            if (inp) inp.value = '';
                            if (prev) prev.src = '';
                            if (wrap) wrap.style.display = 'none';
                        });
                    });
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bindLogistiqueMediaButtons);
                } else {
                    bindLogistiqueMediaButtons();
                }
                if (mediaSearch) {
                    mediaSearch.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); loadMediaSearch(1); } });
                }

                function setFeaturedPreview(url, id) {
                    var value = id ? String(id) : '';
                    if (wpFeaturedHiddenInput) wpFeaturedHiddenInput.value = value;
                    if (wpFeaturedReadonlyInput) wpFeaturedReadonlyInput.value = value;
                    if (wpFeaturedPreview) wpFeaturedPreview.src = url || '';
                    if (wpFeaturedPreviewWrap) wpFeaturedPreviewWrap.style.display = url ? 'block' : 'none';
                    if (wpFeaturedRemoveBtn) wpFeaturedRemoveBtn.disabled = !value;
                }

                function setFeaturedError(message) {
                    var errorEl = document.getElementById('wp-featured-error');
                    if (!errorEl) {
                        if (message) alert(message);
                        return;
                    }
                    if (!message) {
                        errorEl.textContent = '';
                        errorEl.classList.add('d-none');
                        return;
                    }
                    errorEl.textContent = message;
                    errorEl.classList.remove('d-none');
                }

                var featuredModalEl = document.getElementById('wp-featured-media-modal');
                var featuredSearchEl = document.getElementById('wp-featured-media-search');
                var featuredResultsEl = document.getElementById('wp-featured-media-results');
                var featuredLoadingEl = document.getElementById('wp-featured-media-loading');
                var featuredPaginationEl = document.getElementById('wp-featured-media-pagination');

                function selectFeaturedAttachment(attachmentId) {
                    if (!attachmentId) return;
                    setFeaturedError('');
                    var fd = new FormData();
                    fd.append('attachment_id', attachmentId);
                    if (csrfToken) fd.append('_token', csrfToken);

                    fetch(wpFeaturedMediaSelectUrl, {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        }
                    }).then(function(res) {
                        return res.json().then(function(data) {
                            return { ok: res.ok, data: data };
                        }).catch(function() {
                            return { ok: false, data: { message: 'Réponse serveur invalide.' } };
                        });
                    }).then(function(result) {
                        if (!result.ok || !result.data || !result.data.success) {
                            setFeaturedError((result.data && result.data.message) || 'Impossible de sélectionner ce média.');
                            return;
                        }
                        setFeaturedPreview(result.data.url || '', result.data.attachment_id || '');
                        hideModal(featuredModalEl);
                    }).catch(function() {
                        setFeaturedError('Erreur réseau pendant la sélection.');
                    });
                }

                function renderFeaturedPagination(currentPage, lastPage) {
                    if (!featuredPaginationEl || !lastPage || lastPage <= 1) {
                        if (featuredPaginationEl) featuredPaginationEl.classList.add('d-none');
                        return;
                    }

                    featuredPaginationEl.classList.remove('d-none');
                    featuredPaginationEl.innerHTML =
                        '<ul class="pagination pagination-sm mb-0">' +
                            '<li class="page-item' + (currentPage <= 1 ? ' disabled' : '') + '">' +
                                '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '">Préc.</a>' +
                            '</li>' +
                            '<li class="page-item disabled"><span class="page-link">' + currentPage + ' / ' + lastPage + '</span></li>' +
                            '<li class="page-item' + (currentPage >= lastPage ? ' disabled' : '') + '">' +
                                '<a class="page-link" href="#" data-page="' + (currentPage + 1) + '">Suiv.</a>' +
                            '</li>' +
                        '</ul>';

                    featuredPaginationEl.querySelectorAll('a[data-page]').forEach(function(link) {
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            var page = parseInt(this.getAttribute('data-page'), 10);
                            if (page > 0) {
                                loadFeaturedMedia(page);
                            }
                        });
                    });
                }

                function loadFeaturedMedia(page) {
                    page = page || 1;
                    if (!featuredResultsEl) return;

                    setFeaturedError('');
                    featuredResultsEl.innerHTML = '';
                    if (featuredLoadingEl) featuredLoadingEl.classList.remove('d-none');

                    var search = featuredSearchEl ? featuredSearchEl.value : '';
                    var url = wpFeaturedMediaListUrl + '?page=' + page + '&per_page=24';
                    if (search) url += '&search=' + encodeURIComponent(search);

                    fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(function(res) {
                        return res.json().then(function(data) {
                            return { ok: res.ok, data: data };
                        }).catch(function() {
                            return { ok: false, data: { message: 'Réponse serveur invalide.' } };
                        });
                    }).then(function(result) {
                        if (featuredLoadingEl) featuredLoadingEl.classList.add('d-none');
                        if (!result.ok) {
                            setFeaturedError((result.data && result.data.message) || 'Erreur de chargement de la médiathèque.');
                            return;
                        }

                        var items = (result.data && result.data.data) || [];
                        if (!items.length) {
                            featuredResultsEl.innerHTML = '<div class="col-12 text-muted">Aucune image trouvée.</div>';
                        } else {
                            items.forEach(function(item) {
                                var col = document.createElement('div');
                                col.className = 'col-6 col-md-4 col-lg-3';
                                col.innerHTML =
                                    '<div class="card h-100">' +
                                        '<img src="' + (item.url || '') + '" alt="" class="card-img-top" style="height:140px;object-fit:cover;">' +
                                        '<div class="card-body p-2">' +
                                            '<div class="small text-muted mb-2 text-truncate">#' + item.id + ' ' + (item.title || '') + '</div>' +
                                            '<button type="button" class="btn btn-sm btn-primary w-100 wp-featured-select-item" data-id="' + item.id + '">Sélectionner</button>' +
                                        '</div>' +
                                    '</div>';
                                featuredResultsEl.appendChild(col);
                            });

                            featuredResultsEl.querySelectorAll('.wp-featured-select-item').forEach(function(button) {
                                button.addEventListener('click', function() {
                                    selectFeaturedAttachment(this.getAttribute('data-id'));
                                });
                            });
                        }

                        renderFeaturedPagination(result.data.current_page || 1, result.data.last_page || 1);
                    }).catch(function() {
                        if (featuredLoadingEl) featuredLoadingEl.classList.add('d-none');
                        setFeaturedError('Erreur réseau pendant le chargement de la médiathèque.');
                    });
                }

                var featuredChooseBtn = document.getElementById('wp-featured-choose-btn');
                if (featuredChooseBtn) {
                    featuredChooseBtn.addEventListener('click', function() {
                        setFeaturedError('');
                        var featuredModal = getModalInstance(featuredModalEl);
                        if (featuredModal) {
                            featuredModal.show();
                            loadFeaturedMedia(1);
                        }
                    });
                }

                if (featuredSearchEl) {
                    featuredSearchEl.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            loadFeaturedMedia(1);
                        }
                    });
                }

                var featuredUploadBtn = document.getElementById('wp-featured-upload-btn');
                var featuredFileInput = document.getElementById('wp_featured_image_file');
                if (featuredUploadBtn && featuredFileInput) {
                    featuredUploadBtn.addEventListener('click', function() {
                        featuredFileInput.click();
                    });

                    featuredFileInput.addEventListener('change', function() {
                        if (!this.files || !this.files[0]) return;
                        if (getCurrentWpTourId() <= 0) { this.value = ''; notifySaveFirst(); return; }
                        setFeaturedError('');
                        var file = this.files[0];

                        if (file.size > 5 * 1024 * 1024) {
                            setFeaturedError('Le fichier dépasse la limite de 5MB.');
                            this.value = '';
                            return;
                        }

                        var fd = new FormData();
                        fd.append('image', file);
                        fd.append('post_parent_id', String(getCurrentWpTourId()));
                        if (csrfToken) fd.append('_token', csrfToken);

                        fetch(wpFeaturedMediaUploadUrl, {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken || ''
                            }
                        }).then(function(res) {
                            return res.json().then(function(data) {
                                return { ok: res.ok, data: data };
                            }).catch(function() {
                                return { ok: false, data: { message: 'Réponse serveur invalide.' } };
                            });
                        }).then(function(result) {
                            featuredFileInput.value = '';
                            if (!result.ok || !result.data || !result.data.success) {
                                var message = (result.data && result.data.message)
                                    || (result.data && result.data.errors && result.data.errors.image && result.data.errors.image[0])
                                    || 'Erreur lors de l\'upload vers WordPress.';
                                setFeaturedError(message);
                                return;
                            }
                            setFeaturedPreview(result.data.url || '', result.data.attachment_id || '');
                        }).catch(function() {
                            featuredFileInput.value = '';
                            setFeaturedError('Erreur réseau pendant l\'upload.');
                        });
                    });
                }

                if (wpFeaturedRemoveBtn) {
                    wpFeaturedRemoveBtn.addEventListener('click', function() {
                        if (!confirm('Supprimer l\'image ? la une WordPress ?')) return;

                        setFeaturedError('');
                        var fd = new FormData();
                        if (csrfToken) fd.append('_token', csrfToken);

                        fetch(wpFeaturedMediaRemoveUrl, {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken || ''
                            }
                        }).then(function(res) {
                            return res.json().then(function(data) {
                                return { ok: res.ok, data: data };
                            }).catch(function() {
                                return { ok: false, data: { message: 'Réponse serveur invalide.' } };
                            });
                        }).then(function(result) {
                            if (!result.ok || !result.data || !result.data.success) {
                                setFeaturedError((result.data && result.data.message) || 'Impossible de supprimer l\'image ? la une.');
                                return;
                            }
                            setFeaturedPreview('', '');
                        }).catch(function() {
                            setFeaturedError('Erreur réseau pendant la suppression.');
                        });
                    });
                }

                // Hero Gallery (5 images) management
                var heroGalleryCurrentIndex = null;
                var heroGalleryUploadUrl = boot.heroGalleryUploadUrl || heroUploadUrl;
                var heroGallerySelectUrl = boot.heroGallerySelectUrl || heroSelectUrl;

                function updateHeroGalleryHidden() {
                    var ids = [];
                    document.querySelectorAll('.hero-gallery-id-input').forEach(function(input) {
                        var val = input.value.trim();
                        if (val) ids.push(val);
                    });
                    var hiddenInput = document.getElementById('hero_gallery_ids');
                    if (hiddenInput) hiddenInput.value = ids.join(',');
                }

                function setHeroGalleryPreview(index, url, id) {
                    var item = document.querySelector('.hero-gallery-item[data-index="' + index + '"]');
                    if (!item) return;
                    var input = item.querySelector('.hero-gallery-id-input');
                    var preview = item.querySelector('.hero-gallery-preview');
                    var previewWrap = item.querySelector('.hero-gallery-preview-wrap');
                    var placeholder = item.querySelector('.hero-gallery-placeholder');
                    var removeBtn = item.querySelector('.hero-gallery-remove-btn');
                    if (input) input.value = id || '';
                    if (preview) preview.src = url || '';
                    if (previewWrap) previewWrap.style.display = (url ? 'block' : 'none');
                    if (placeholder) placeholder.style.display = (url ? 'none' : 'flex');
                    if (removeBtn) removeBtn.disabled = !id;
                    updateHeroGalleryHidden();
                }

                // Upload buttons
                document.querySelectorAll('.hero-gallery-upload-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        if (getCurrentWpTourId() <= 0) { notifySaveFirst(); return; }
                        var index = this.getAttribute('data-index');
                        heroGalleryCurrentIndex = index;
                        var fileInput = document.createElement('input');
                        fileInput.type = 'file';
                        fileInput.accept = 'image/jpeg,image/png,image/webp';
                        fileInput.addEventListener('change', function() {
                            if (!this.files || !this.files[0]) return;
                            var file = this.files[0];
                            var formData = new FormData();
                            formData.append('hero_image', file);
                            if (csrfToken) formData.append('_token', csrfToken);
                            fetch(withCurrentTourId(heroGalleryUploadUrl), {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                }
                            }).then(function(res) {
                                return res.json().then(function(r) {
                                    return { ok: res.ok, data: r };
                                }).catch(function() {
                                    return { ok: false, data: { message: 'Erreur serveur.' } };
                                });
                            }).then(function(result) {
                                if (result.ok && result.data && result.data.success) {
                                    setHeroGalleryPreview(heroGalleryCurrentIndex, result.data.url, result.data.attachment_id);
                                } else {
                                    alert((result.data && result.data.message) || 'Erreur lors de l\'upload.');
                                }
                            }).catch(function() {
                                alert('Erreur réseau.');
                            });
                        });
                        fileInput.click();
                    });
                });

                // Choose from media library buttons
                document.querySelectorAll('.hero-gallery-choose-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        heroGalleryCurrentIndex = this.getAttribute('data-index');
                        window.logistiqueMediaTarget = null;
                        var mediaModalInstance = getModalInstance(mediaModal);
                        if (mediaModalInstance) {
                            mediaModalInstance.show();
                            loadMediaSearch(1);
                        }
                    });
                });

                // Remove buttons
                document.querySelectorAll('.hero-gallery-remove-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var index = this.getAttribute('data-index');
                        if (confirm('Retirer cette image de la galerie hero ?')) {
                            setHeroGalleryPreview(index, '', '');
                        }
                    });
                });

                // Override media selection to handle hero gallery
                var originalMediaClick = null;
                if (mediaResults) {
                    mediaResults.addEventListener('click', function(e) {
                        var item = e.target.closest('.hero-media-item');
                        if (item && heroGalleryCurrentIndex !== null) {
                            e.preventDefault();
                            e.stopPropagation();
                            var id = item.getAttribute('data-id');
                            var url = item.getAttribute('data-url');
                            if (id && heroGalleryCurrentIndex !== null) {
                                setHeroGalleryPreview(heroGalleryCurrentIndex, url, id);
                                hideModal(mediaModal);
                                heroGalleryCurrentIndex = null;
                            }
                        }
                    }, true);
                }

                // Initialize hidden input
                updateHeroGalleryHidden();
            })();

(function () {
            window.TOUR_PLACES_CALC_DEBUG = !!((window.VOYAGE_EDIT_BOOTSTRAP || {}).tourPlacesCalcDebug);
            window._tourPlacesRecalcPending = false;
            window.notifyVoyageTourPlacesChanged = function () {
                window._tourPlacesRecalcPending = true;
                if (typeof window.recalculateVoyageTourPlacesPreview === 'function') {
                    window.recalculateVoyageTourPlacesPreview();
                }
            };
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('edit-voyage-form');
                var wrap = document.getElementById('tour-hotels-wrapper');
                if (!form || !wrap) {
                    return;
                }
                function handler(ev) {
                    if (!ev.target || !ev.target.name || ev.target.name.indexOf('travel_dates[') !== 0) {
                        return;
                    }
                    window.recalculateVoyageTourPlacesPreview();
                }
                window.recalculateVoyageTourPlacesPreview = function () {
                    var mp = document.getElementById('max_people');
                    var pl = document.getElementById('places_display');
                    if (!mp) {
                        return;
                    }
                    window._tourPlacesRecalcPending = false;
                    window.__lastTourPlacesPreview = { total: mp.value || '0', lines: [], ignored: [], roomRowsDetected: 0 };
                    if (pl) {
                        pl.value = String(mp.value || '0');
                    }
                };
                form.addEventListener('input', handler, true);
                form.addEventListener('change', handler, true);
                form.addEventListener('submit', function () {
                    window.recalculateVoyageTourPlacesPreview();
                    if (window.TOUR_PLACES_CALC_DEBUG && typeof console !== 'undefined' && console.debug) {
                        var m = document.getElementById('max_people');
                        console.debug('[TourPlaces] submit', window.__lastTourPlacesPreview, 'max_people_field:', m ? m.value : null);
                    }
                });
                window.recalculateVoyageTourPlacesPreview();
                if (window._tourPlacesRecalcPending) {
                    window.recalculateVoyageTourPlacesPreview();
                }
            });
        })();

document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.voyage-edit-page .modal, .voyage-edit-page .offcanvas').forEach(function (overlay) {
                if (overlay.parentElement !== document.body) {
                    document.body.appendChild(overlay);
                }
            });

            if (window.__voyageOverlayStateInit) {
                return;
            }

            window.__voyageOverlayStateInit = true;

            var overlaySyncTimer = null;

            function visibleCount(selector) {
                return document.querySelectorAll(selector).length;
            }

            function trimBackdrops(selector, keepCount) {
                var nodes = Array.prototype.slice.call(document.querySelectorAll(selector));
                if (nodes.length <= keepCount) {
                    return;
                }
                nodes.slice(0, nodes.length - keepCount).forEach(function (node) {
                    node.remove();
                });
            }

            function syncOverlayState() {
                var openModals = visibleCount('.modal.show');
                var openOffcanvas = visibleCount('.offcanvas.show');

                trimBackdrops('.modal-backdrop', openModals > 0 ? 1 : 0);
                trimBackdrops('.offcanvas-backdrop', openOffcanvas > 0 ? 1 : 0);

                if (openModals === 0) {
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('padding-right');
                }

                if (openModals === 0 && openOffcanvas === 0) {
                    document.body.style.removeProperty('overflow');
                    document.body.classList.remove('day-builder-open');
                } else if (openOffcanvas > 0 && openModals === 0) {
                    document.body.style.overflow = 'hidden';
                    document.body.classList.add('day-builder-open');
                }
            }

            function scheduleOverlaySync() {
                if (overlaySyncTimer) {
                    window.clearTimeout(overlaySyncTimer);
                }
                overlaySyncTimer = window.setTimeout(syncOverlayState, 40);
            }

            [
                'shown.bs.modal',
                'hidden.bs.modal',
                'shown.bs.offcanvas',
                'hidden.bs.offcanvas'
            ].forEach(function (eventName) {
                document.addEventListener(eventName, scheduleOverlaySync, true);
            });

            scheduleOverlaySync();
        });

(function () {
            function ensureId(el) {
                if (el.id) return el.id;
                var base = 'rich-editor-' + Math.random().toString(36).slice(2);
                el.id = base;
                return base;
            }

            function editorHeightFromRows(el) {
                var rows = parseInt(el.getAttribute('rows') || '0', 10);
                if (!rows || rows <= 2) return 160;
                if (rows <= 4) return 220;
                return 300;
            }

            function initOne(el) {
                if (!el || el.tagName !== 'TEXTAREA') return;
                if (!el.classList.contains('rich-editor')) return;
                if (el.dataset.richEditorInitialized === 'true') return;
                if (!window.tinymce || typeof tinymce.init !== 'function') return;

                var id = ensureId(el);
                if (window.tinymce && tinymce.get(id)) {
                    el.dataset.richEditorInitialized = 'true';
                    return;
                }

                el.dataset.richEditorInitialized = 'true';

                tinymce.init({
                    target: el,
                    height: editorHeightFromRows(el),
                    plugins: [
                        "advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker",
                        "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                        "save table contextmenu directionality emoticons template paste textcolor"
                    ],
                    toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | print preview media fullpage | forecolor backcolor emoticons",
                    style_formats: [
                        {title: 'Bold text', inline: 'b'},
                        {title: 'Red text', inline: 'span', styles: {color: '#ff0000'}},
                        {title: 'Red header', block: 'h1', styles: {color: '#ff0000'}},
                        {title: 'Example 1', inline: 'span', classes: 'example1'},
                        {title: 'Example 2', inline: 'span', classes: 'example2'},
                        {title: 'Table styles'},
                        {title: 'Table row 1', selector: 'tr', classes: 'tablerow1'}
                    ]
                });
            }

            function initAll(root) {
                var scope = root && root.querySelectorAll ? root : document;
                scope.querySelectorAll('textarea.rich-editor').forEach(initOne);
            }

            function bootRichEditors() {
                initAll(document);

                var form = document.querySelector('form');
                if (form && window.MutationObserver) {
                    var mo = new MutationObserver(function (mutations) {
                        mutations.forEach(function (m) {
                            m.addedNodes && m.addedNodes.forEach(function (node) {
                                if (!node || node.nodeType !== 1) return;
                                if (node.matches && node.matches('textarea.rich-editor')) initOne(node);
                                if (node.querySelectorAll) initAll(node);
                            });
                        });
                    });
                    mo.observe(form, {childList: true, subtree: true});
                }

                if (form) {
                    form.addEventListener('submit', function () {
                        if (window.tinymce) tinymce.triggerSave();
                    });
                }

                document.addEventListener('shown.bs.tab', function (e) {
                    var target = e && e.target ? document.querySelector(e.target.getAttribute('href')) : null;
                    if (target) initAll(target);
                });

                document.addEventListener('shown.bs.collapse', function (e) {
                    var target = e && e.target ? e.target : null;
                    if (target) initAll(target);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootRichEditors);
            } else {
                bootRichEditors();
            }
        })();

        // Initialiser les données pour le modal "Ajouter un élément" (hotels & transfers par jour)
        (function hydrateVoyageEditData() {
            var boot = window.VOYAGE_EDIT_BOOTSTRAP || {};
            window.tourHotelsData = boot.tourHotelsData || {};
            window.tourTransfersData = boot.tourTransfersData || { arrival: [], departure: [] };
            window.programDayHotelsTransfers = boot.programDayHotelsTransfers || {};
        })();

        // Ouvrir l'onglet Vols si ?tab=flights (depuis Hôtels / Transferts sidebar)
        document.addEventListener('DOMContentLoaded', function() {
            if (window.VoyageEditorRuntime && window.VoyageEditorRuntime.ownsTabs) return;
            var params = new URLSearchParams(window.location.search);
            if (params.get('tab') === 'flights') {
                var tabEl = document.querySelector('a[href="#flights"][data-bs-toggle="tab"]');
                if (tabEl && window.bootstrap && bootstrap.Tab) {
                    new bootstrap.Tab(tabEl).show();
                }
            }
        });
        // Ouvrir un onglet donn? si ?tab=... ou si le hash cible un panneau.
        document.addEventListener('DOMContentLoaded', function() {
            if (window.VoyageEditorRuntime && window.VoyageEditorRuntime.ownsTabs) return;
            var params = new URLSearchParams(window.location.search);
            var targetTab = params.get('tab');
            if (!targetTab && window.location.hash) {
                targetTab = window.location.hash.replace(/^#/, '');
            }
            if (!targetTab) return;

            var normalizedTarget = targetTab.charAt(0) === '#' ? targetTab : ('#' + targetTab);
            var tabEl = document.querySelector('a[href="' + normalizedTarget + '"][data-bs-toggle="tab"]');
            if (tabEl && window.bootstrap && bootstrap.Tab) {
                bootstrap.Tab.getOrCreateInstance(tabEl).show();
            }
        });

        (function programmeUiVisibility() {
            if (window.VoyageEditorRuntime && window.VoyageEditorRuntime.ownsTabs) return;
            function ensureFirstProgramDayVisible() {
                var accordion = document.getElementById('accordionProgrammeDays');
                if (!accordion) return;

                var firstCard = accordion.querySelector('.programme-day-card');
                if (!firstCard) return;

                var collapseEl = firstCard.querySelector('.accordion-collapse');
                var toggleBtn = firstCard.querySelector('.accordion-button');
                if (!collapseEl || !toggleBtn) return;

                if (window.bootstrap && bootstrap.Collapse) {
                    bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).show();
                } else {
                    collapseEl.classList.add('show');
                }

                toggleBtn.classList.remove('collapsed');
                toggleBtn.setAttribute('aria-expanded', 'true');
                firstCard.scrollIntoView({ behavior: 'auto', block: 'start' });
            }

            function openProgrammeTabIfNeeded() {
                var params = new URLSearchParams(window.location.search);
                var hasProgramErrors = Array.from(document.querySelectorAll('.alert.alert-danger li')).some(function(item) {
                    return /programme_days/i.test(item.textContent || '');
                });
                var shouldOpenProgramme = params.get('tab') === 'program-days'
                    || window.location.hash === '#program-days'
                    || hasProgramErrors;

                if (!shouldOpenProgramme) return;

                var tabButton = document.querySelector('a[href="#program-days"][data-bs-toggle="tab"]');
                if (tabButton && window.bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(tabButton).show();
                }

                window.requestAnimationFrame(ensureFirstProgramDayVisible);
            }

            function bootProgrammeUiVisibility() {
                openProgrammeTabIfNeeded();
                window.setTimeout(function() {
                    ensureFirstProgramDayVisible();
                    ensureFirstProgramDayVisible();
                }, 250);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootProgrammeUiVisibility);
            } else {
                bootProgrammeUiVisibility();
            }
        })();

        // Destination UX: location tree (search, chips, actions, hierarchy, indeterminate)
        (function destinationUx() {
            var container = document.getElementById('locationTreeContainer');
            var searchInput = document.getElementById('locationSearch');
            var countText = document.getElementById('locationCountText');
            var chipsContainer = document.getElementById('locationChipsContainer');
            var chipsClearBtn = document.getElementById('locationChipsClear');
            var selectAllBtn = document.getElementById('locationSelectAll');
            var deselectAllBtn = document.getElementById('locationDeselectAll');
            var expandAllBtn = document.getElementById('locationExpandAll');
            var collapseAllBtn = document.getElementById('locationCollapseAll');
            var selectFilteredBtn = document.getElementById('locationSelectFiltered');

            function getCheckboxes() { return container ? container.querySelectorAll('.location-checkbox') : []; }
            function getVisibleItems() { return container ? container.querySelectorAll('.wp-location-item:not([style*="display: none"])') : []; }

            function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
            function highlightMatch(text, term) {
                if (!term) return escapeHtml(text);
                var r = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                return escapeHtml(text).replace(r, '<mark>$1</mark>');
            }

            function updateCount() {
                var n = document.querySelectorAll('.location-checkbox:checked').length;
                if (countText) countText.textContent = n + ' location(s) sélectionnée(s)';
            }

            function updateChips() {
                if (!chipsContainer) return;
                var boxes = Array.from(getCheckboxes()).filter(function(cb) { return cb.checked; });
                chipsContainer.innerHTML = '';
                boxes.forEach(function(cb) {
                    var id = cb.value;
                    var title = cleanTaxonomyLabel(cb.getAttribute('data-loc-title') || id);
                    var chip = document.createElement('span');
                    chip.className = 'destination-ux-chip';
                    chip.innerHTML = escapeHtml(title) + ' <button type="button" class="destination-ux-chip-remove" data-loc-id="' + escapeHtml(id) + '" aria-label="Retirer">×</button>';
                    chipsContainer.appendChild(chip);
                });
                if (chipsClearBtn) chipsClearBtn.style.display = boxes.length ? '' : 'none';
            }

            function syncChipsAndCount() {
                updateCount();
                updateChips();
                updateIndeterminate();
            }

            function updateIndeterminate() {
                container.querySelectorAll('.wp-location-item.has-children').forEach(function(item) {
                    var cb = item.querySelector(':scope > .destination-tree-row .location-checkbox');
                    if (!cb) return;
                    var childCbs = item.querySelectorAll('.destination-tree-list .location-checkbox');
                    var checked = Array.from(childCbs).filter(function(c) { return c.checked; }).length;
                    cb.indeterminate = checked > 0 && checked < childCbs.length;
                    item.classList.toggle('indeterminate', cb.indeterminate);
                });
                var panelList = document.getElementById('destination-cities-list');
                if (panelList) {
                    var countryCb = panelList.querySelector('.destination-country-checkbox-label input.location-checkbox');
                    if (countryCb) {
                        var cityCbs = panelList.querySelectorAll('.destination-city-checkbox-label input.location-checkbox');
                        var checked = Array.from(cityCbs).filter(function(c) { return c.checked; }).length;
                        countryCb.indeterminate = checked > 0 && checked < cityCbs.length;
                    }
                }
            }

            function applySearch(term) {
                term = (term || '').toLowerCase().trim();
                var items = container ? container.querySelectorAll('.wp-location-item') : [];
                var hasFilter = term.length > 0;
                if (selectFilteredBtn) selectFilteredBtn.style.display = hasFilter ? '' : 'none';

                items.forEach(function(item) {
                    var title = item.getAttribute('data-title') || '';
                    var path = item.getAttribute('data-path') || '';
                    var pathLower = path.toLowerCase();
                    var selfMatch = title.indexOf(term) !== -1;
                    var pathMatch = term && pathLower.indexOf(term) !== -1;
                    var childMatch = Array.from(item.querySelectorAll('.wp-location-item')).some(function(c) {
                        return (c.getAttribute('data-title') || '').indexOf(term) !== -1;
                    });
                    var show = !term || selfMatch || pathMatch || childMatch;
                    item.style.display = show ? '' : 'none';

                    var titleEl = item.querySelector('.destination-tree-title');
                    if (titleEl) {
                        var rawTitle = item.querySelector('.location-checkbox').getAttribute('data-loc-title') || item.getAttribute('data-title') || '';
                        if (term && show)
                            titleEl.innerHTML = highlightMatch(rawTitle, term);
                        else
                            titleEl.textContent = rawTitle;
                    }
                    if (item.classList) item.classList.toggle('destination-search-path', !!term && show && path);
                    var t = item.querySelector('.destination-tree-title');
                    if (t) {
                        if (term && show && path) t.setAttribute('data-path', path);
                        else t.removeAttribute('data-path');
                    }
                });
            }

            function expandAll() {
                container.querySelectorAll('.wp-location-item.has-children').forEach(function(item) {
                    item.classList.remove('collapsed');
                    item.querySelector('.destination-tree-toggle').setAttribute('aria-expanded', 'true');
                });
            }
            function collapseAll() {
                container.querySelectorAll('.wp-location-item.has-children').forEach(function(item) {
                    item.classList.add('collapsed');
                    item.querySelector('.destination-tree-toggle').setAttribute('aria-expanded', 'false');
                });
            }

            function selectAll() {
                getCheckboxes().forEach(function(cb) { cb.checked = true; });
                syncChipsAndCount();
            }
            function deselectAll() {
                getCheckboxes().forEach(function(cb) { cb.checked = false; });
                syncChipsAndCount();
            }
            function selectFilteredOnly() {
                getCheckboxes().forEach(function(cb) { cb.checked = false; });
                getVisibleItems().forEach(function(item) {
                    var cb = item.querySelector(':scope > .destination-tree-row .location-checkbox');
                    if (cb) cb.checked = true;
                });
                syncChipsAndCount();
            }

            function cascadeParent(checkbox) {
                var item = checkbox.closest('.wp-location-item');
                if (!item || !item.classList.contains('has-children')) return;
                var childCbs = item.querySelectorAll('.destination-tree-list .location-checkbox');
                var target = checkbox.checked;
                if (childCbs.length > 12 && !window.confirm('Appliquer ? ' + childCbs.length + ' sous-destinations ?')) return;
                childCbs.forEach(function(c) { c.checked = target; });
                syncChipsAndCount();
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() { applySearch(this.value); });
            }
            if (chipsContainer) {
                chipsContainer.addEventListener('click', function(e) {
                    var rm = e.target.closest('.destination-ux-chip-remove');
                    if (rm) {
                        e.preventDefault();
                        var id = rm.getAttribute('data-loc-id');
                        var cb = container && container.querySelector('.location-checkbox[value="' + id.replace(/"/g, '\\"') + '"]');
                        if (cb) { cb.checked = false; syncChipsAndCount(); }
                    }
                });
            }
            if (chipsClearBtn) chipsClearBtn.addEventListener('click', function() { deselectAll(); });

            if (selectAllBtn) selectAllBtn.addEventListener('click', selectAll);
            if (deselectAllBtn) deselectAllBtn.addEventListener('click', deselectAll);
            if (expandAllBtn) expandAllBtn.addEventListener('click', expandAll);
            if (collapseAllBtn) collapseAllBtn.addEventListener('click', collapseAll);
            if (selectFilteredBtn) selectFilteredBtn.addEventListener('click', selectFilteredOnly);

            container && container.addEventListener('change', function(e) {
                if (e.target.classList && e.target.classList.contains('location-checkbox')) {
                    syncChipsAndCount();
                    cascadeParent(e.target);
                }
            });

            container && container.addEventListener('click', function(e) {
                var toggle = e.target.closest('.destination-tree-toggle');
                if (toggle && !toggle.classList.contains('destination-tree-toggle--empty')) {
                    var item = toggle.closest('.wp-location-item.has-children');
                    if (item) {
                        item.classList.toggle('collapsed');
                        toggle.setAttribute('aria-expanded', item.classList.contains('collapsed') ? 'false' : 'true');
                    }
                }
            });

            updateChips();
            updateIndeterminate();

            // Pays (choix multiple) + catalogue villes : recherche, Tout s?lectionner/d?s?lectionner, ensureLocation ? la vol?e
            var panelDynamic = document.getElementById('destination-cities-panel-dynamic');
            var panelTitle = document.getElementById('destination-cities-panel-title');
            var panelList = document.getElementById('destination-cities-list');
            var citySearchInput = document.getElementById('destinationCitySearch');
            var selectAllCitiesBtn = document.getElementById('destinationSelectAllCities');
            var deselectAllCitiesBtn = document.getElementById('destinationDeselectAllCities');
            var countryCitiesData = window.DESTINATION_COUNTRY_CITIES_DATA || {};
            var mergedCities = window.DESTINATION_MERGED_CITIES || {};
            var worldCountries = window.DESTINATION_WORLD_COUNTRIES || {};
            var ensureLocationUrl = window.DESTINATION_ENSURE_LOCATION_URL || '';
            var selectedIds = (window.DESTINATION_SELECTED_IDS || []).map(function(id) { return String(id); });
            var selectedFallbackContainer = document.getElementById('destinationSelectedLocationsFallback');

            function cleanTaxonomyLabel(label) {
                if (!label) return '';
                return String(label)
                    // (ne pas stripper de caractères invalides ici : la source doit être UTF-8)
                    .replace(/Âº/g, '-')
                    .replace(/º/g, '-')
                    .replace(/[»«]/g, '')
                    .replace(/\s*[-–—]\s*/g, ' - ')
                    .replace(/\s{2,}/g, ' ')
                    .trim();
            }
            function escapeAttr(s) { return cleanTaxonomyLabel(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
            function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

            function ensureLocation(countryCode, cityName, cb) {
                var formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
                formData.append('country_code', countryCode);
                if (cityName) formData.append('city_name', cityName);
                fetch(ensureLocationUrl, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) { if (cb) cb(null, data); })
                    .catch(function(err) { if (cb) cb(err); });
            }

            function updateCountryIndeterminate() {
                if (!panelList) return;
                panelList.querySelectorAll('.destination-country-block').forEach(function(block) {
                    var countryCb = block.querySelector('.destination-country-checkbox-label input.location-checkbox');
                    if (!countryCb) return;
                    var cityCbs = block.querySelectorAll('.destination-city-checkbox-label input.location-checkbox');
                    var checked = Array.from(cityCbs).filter(function(c) { return c.checked; }).length;
                    countryCb.indeterminate = checked > 0 && checked < cityCbs.length;
                });
            }

            function updateCityCountryCounts() {
                if (!panelList) return;
                panelList.querySelectorAll('.city-country-group').forEach(function(group) {
                    var checked = group.querySelectorAll('.destination-city-row input.location-checkbox:checked').length;
                    var counter = group.querySelector('[data-country-city-count]');
                    if (counter) {
                        counter.textContent = checked + ' ville' + (checked > 1 ? 's' : '') + ' sélectionnée' + (checked > 1 ? 's' : '');
                    }
                });
            }

            function updateTaxonomySelectionCounts() {
                var countriesCounter = document.getElementById('destinationCountriesSelectedCount');
                if (countriesCounter) {
                    var selectedCountries = document.querySelectorAll('#destinationCountryList .destination-country-option:checked').length;
                    countriesCounter.textContent = selectedCountries + ' pays sélectionné' + (selectedCountries > 1 ? 's' : '');
                }

                var citiesCounter = document.getElementById('destinationCitiesSelectedCount');
                if (citiesCounter && panelList) {
                    var selectedCities = panelList.querySelectorAll('.destination-city-row input.location-checkbox:checked').length;
                    citiesCounter.textContent = selectedCities + ' ville' + (selectedCities > 1 ? 's' : '') + ' sélectionnée' + (selectedCities > 1 ? 's' : '');
                }

                updateCityCountryCounts();
            }

            function syncSelectedLocationFallbacks() {
                if (!selectedFallbackContainer) return;

                var selectedValues = [];
                document.querySelectorAll('.location-checkbox:checked').forEach(function(cb) {
                    var value = cb.value;
                    if (value && String(value).trim() !== '' && selectedValues.indexOf(String(value)) === -1) {
                        selectedValues.push(String(value));
                    }
                });

                selectedFallbackContainer.innerHTML = '';
                selectedValues.forEach(function(value) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'locations[]';
                    input.value = value;
                    input.setAttribute('data-destination-selected-fallback', '1');
                    selectedFallbackContainer.appendChild(input);
                });
            }

            function getSelectedCountryCodes() {
                var opts = document.querySelectorAll('#destinationCountryList .destination-country-option:checked');
                return Array.from(opts).map(function(o) { return o.value; }).filter(Boolean);
            }

            function onCheckboxChange() {
                syncSelectedLocationFallbacks();
                updateChips();
                updateCount();
                updateCountryIndeterminate();
                updateTaxonomySelectionCounts();
            }

            function handlePanelCheckboxChange(cb) {
                if (!cb || !cb.classList.contains('location-checkbox')) return;
                if (cb.classList.contains('destination-country-whole') && cb.getAttribute('data-needs-create') === '1' && cb.checked) {
                    var ccode = cb.getAttribute('data-country-code');
                    if (!ccode) return;
                    cb.disabled = true;
                    ensureLocation(ccode, null, function(err, res) {
                        cb.disabled = false;
                        if (err || !res || !res.id) { cb.checked = false; return; }
                        cb.value = res.id;
                        cb.setAttribute('data-loc-id', res.id);
                        cb.removeAttribute('data-needs-create');
                        cb.removeAttribute('data-country-code');
                        onCheckboxChange();
                    });
                    return;
                }
                if (cb.getAttribute('data-needs-create') === '1' && cb.checked) {
                    var ccode = cb.getAttribute('data-country-code');
                    var cname = cb.getAttribute('data-city-name');
                    if (!ccode || !cname) return;
                    cb.disabled = true;
                    ensureLocation(ccode, cname, function(err, res) {
                        cb.disabled = false;
                        if (err || !res || !res.id) { cb.checked = false; return; }
                        cb.value = res.id;
                        cb.setAttribute('data-loc-id', res.id);
                        cb.setAttribute('data-loc-title', res.title || cname);
                        cb.removeAttribute('data-needs-create');
                        cb.removeAttribute('data-country-code');
                        cb.removeAttribute('data-city-name');
                        onCheckboxChange();
                    });
                    return;
                }
                onCheckboxChange();
            }

            if (panelList) {
                panelList.addEventListener('change', function(e) {
                    if (e.target && e.target.classList && e.target.classList.contains('location-checkbox')) {
                        handlePanelCheckboxChange(e.target);
                    }
                });
            }

            function getCurrentSelectedLocationIds() {
                var ids = [];
                if (!panelList) return ids;
                panelList.querySelectorAll('.location-checkbox:checked').forEach(function(cb) {
                    var v = cb.value;
                    if (v && String(v).trim() !== '') {
                        var id = String(v);
                        if (ids.indexOf(id) === -1) ids.push(id);
                    }
                });
                return ids;
            }

            function fillCitiesPanel(selectedCodes, shouldSyncFallbacks) {
                if (!panelList) return;
                var selectedIdsForBuild = selectedIds.slice();
                if (panelList.querySelectorAll('.location-checkbox').length > 0) {
                    selectedIdsForBuild = getCurrentSelectedLocationIds();
                    if (selectedIdsForBuild.length === 0) selectedIdsForBuild = selectedIds.slice();
                }
                panelList.innerHTML = '';
                if (!selectedCodes || selectedCodes.length === 0) {
                    if (panelDynamic) panelDynamic.style.display = 'none';
                    if (citySearchInput) citySearchInput.value = '';
                    if (shouldSyncFallbacks) syncSelectedLocationFallbacks();
                    updateTaxonomySelectionCounts();
                    return;
                }
                if (panelDynamic) panelDynamic.style.display = 'block';
                panelTitle.textContent = 'Villes (' + selectedCodes.length + ' pays)';

                selectedCodes.forEach(function(code) {
                    var cities = mergedCities[code] || [];
                    var data = countryCitiesData[code];
                    var countryName = cleanTaxonomyLabel((data && data.title) ? data.title : (worldCountries[code] || code));

                    var block = document.createElement('div');
                    block.className = 'destination-country-block city-country-group';
                    block.setAttribute('data-country-code', code);

                    var header = document.createElement('div');
                    header.className = 'city-country-header';
                    header.innerHTML = '<strong>' + escapeHtml(countryName) + '</strong><span data-country-city-count>0 ville sélectionnée</span>';
                    block.appendChild(header);

                    var countryId = data && data.id ? data.id : null;
                    var countryChecked = countryId && selectedIdsForBuild.indexOf(String(countryId)) !== -1;
                    var countryLabel = document.createElement('label');
                    countryLabel.className = 'destination-country-checkbox-label taxonomy-option country-full-option';
                    if (countryId) {
                        countryLabel.innerHTML = '<input type="checkbox" name="locations[]" value="' + countryId + '" class="location-checkbox destination-checkbox destination-country-whole" ' + (countryChecked ? 'checked' : '') + ' data-loc-id="' + countryId + '" data-loc-title="' + escapeAttr(countryName) + '"> <span>Inclure le pays entier</span>';
                    } else {
                        countryLabel.innerHTML = '<input type="checkbox" name="locations[]" value="" class="location-checkbox destination-checkbox destination-country-whole" data-country-code="' + escapeAttr(code) + '" data-needs-create="1" data-loc-title="' + escapeAttr(countryName) + '"> <span>Inclure le pays entier</span>';
                    }
                    block.appendChild(countryLabel);

                    if (cities.length === 0) {
                        var p = document.createElement('p');
                        p.className = 'taxonomy-empty-state';
                        p.textContent = 'Aucune ville dans le catalogue pour ce pays.';
                        block.appendChild(p);
                    } else {
                        var cityGrid = document.createElement('div');
                        cityGrid.className = 'city-grid';
                        cities.forEach(function(city) {
                            var lid = city.id;
                            var title = cleanTaxonomyLabel(city.title || '');
                            var checked = lid && selectedIdsForBuild.indexOf(String(lid)) !== -1;
                            var path = cleanTaxonomyLabel(countryName + ' - ' + title);
                            var label = document.createElement('label');
                            label.className = 'destination-city-checkbox-label destination-city-row taxonomy-option';
                            label.setAttribute('data-city-title', title.toLowerCase());
                            label.setAttribute('data-path', path);
                            label.setAttribute('data-country-code', code);
                            if (lid) {
                                label.innerHTML = '<input type="checkbox" name="locations[]" value="' + lid + '" class="location-checkbox destination-checkbox" ' + (checked ? 'checked' : '') + ' data-loc-id="' + lid + '" data-loc-title="' + escapeAttr(title) + '"> <span class="destination-city-path">' + escapeHtml(title) + '</span>';
                            } else {
                                label.innerHTML = '<input type="checkbox" name="locations[]" value="" class="location-checkbox destination-checkbox" data-country-code="' + escapeAttr(code) + '" data-city-name="' + escapeAttr(title) + '" data-needs-create="1" data-loc-title="' + escapeAttr(title) + '"> <span class="destination-city-path">' + escapeHtml(title) + '</span>';
                            }
                            cityGrid.appendChild(label);
                        });
                        block.appendChild(cityGrid);
                    }
                    panelList.appendChild(block);
                });

                if (citySearchInput) {
                    citySearchInput.value = '';
                    citySearchInput.dispatchEvent(new Event('input'));
                }
                updateCountryIndeterminate();
                updateChips();
                updateCount();
                if (shouldSyncFallbacks) syncSelectedLocationFallbacks();
                updateTaxonomySelectionCounts();
            }

            function filterCitySearch(term) {
                term = (term || '').toLowerCase().trim();
                panelList.querySelectorAll('.destination-city-row').forEach(function(row) {
                    var title = row.getAttribute('data-city-title') || '';
                    var path = (row.getAttribute('data-path') || '').toLowerCase();
                    var show = !term || title.indexOf(term) !== -1 || path.indexOf(term) !== -1;
                    row.style.display = show ? '' : 'none';
                });
            }

            var countryListEl = document.getElementById('destinationCountryList');
            var countrySearchInput = document.getElementById('destinationCountrySearch');
            var selectAllCountriesBtn = document.getElementById('destinationSelectAllCountries');
            var deselectAllCountriesBtn = document.getElementById('destinationDeselectAllCountries');

            function filterCountrySearch(term) {
                term = (term || '').toLowerCase().trim();
                if (!countryListEl) return;
                countryListEl.querySelectorAll('.destination-country-option-label').forEach(function(label) {
                    var opt = label.querySelector('.destination-country-option');
                    var name = cleanTaxonomyLabel((opt ? opt.getAttribute('data-country-name') : '') || (label.textContent || '')).toLowerCase();
                    if (typeof name !== 'string') name = '';
                    var show = !term || name.indexOf(term) !== -1;
                    label.style.display = show ? '' : 'none';
                });
            }

            if (countryListEl) {
                countryListEl.querySelectorAll('.destination-country-option').forEach(function(opt) {
                    opt.addEventListener('change', function() { fillCitiesPanel(getSelectedCountryCodes(), true); });
                });
            }
            if (countrySearchInput) {
                countrySearchInput.addEventListener('input', function() { filterCountrySearch(this.value); });
            }
            if (selectAllCountriesBtn && countryListEl) {
                selectAllCountriesBtn.addEventListener('click', function() {
                    countryListEl.querySelectorAll('.destination-country-option-label:not([style*="display: none"]) .destination-country-option').forEach(function(o) { o.checked = true; });
                    fillCitiesPanel(getSelectedCountryCodes(), true);
                });
            }
            if (deselectAllCountriesBtn && countryListEl) {
                deselectAllCountriesBtn.addEventListener('click', function() {
                    countryListEl.querySelectorAll('.destination-country-option').forEach(function(o) { o.checked = false; });
                    fillCitiesPanel([], true);
                });
            }

            var countryAddSearchInput = document.getElementById('destinationCountryAddSearch');
            var countryAutocompleteDropdown = document.getElementById('destinationCountryAutocompleteDropdown');
            function buildCountryAutocompleteSuggestions(term) {
                var selectedCodes = getSelectedCountryCodes();
                var list = [];
                term = (term || '').toLowerCase().trim();
                for (var code in worldCountries) {
                    var name = cleanTaxonomyLabel(worldCountries[code] || '');
                    var searchableName = name.toLowerCase();
                    if (selectedCodes.indexOf(code) !== -1) continue;
                    if (term && searchableName.indexOf(term) === -1) continue;
                    list.push({ code: code, name: name });
                }
                return list;
            }
            function openCountryAutocomplete() {
                var term = countryAddSearchInput ? countryAddSearchInput.value : '';
                var list = buildCountryAutocompleteSuggestions(term);
                if (!countryAutocompleteDropdown) return;
                countryAutocompleteDropdown.classList.toggle('is-open', list.length > 0);
                countryAutocompleteDropdown.innerHTML = '';
                list.slice(0, 60).forEach(function(item) {
                    var div = document.createElement('div');
                    div.className = 'destination-country-autocomplete-item';
                    div.textContent = cleanTaxonomyLabel(item.name);
                    div.setAttribute('data-country-code', item.code);
                    countryAutocompleteDropdown.appendChild(div);
                });
                if (list.length === 0) countryAutocompleteDropdown.classList.remove('is-open');
            }
            function closeCountryAutocomplete() {
                if (countryAutocompleteDropdown) countryAutocompleteDropdown.classList.remove('is-open');
            }
            function addCountryFromSuggestion(code) {
                if (!code || !countryListEl) return;
                var opt = countryListEl.querySelector('.destination-country-option[value="' + code + '"]');
                if (opt && !opt.checked) {
                    opt.checked = true;
                    fillCitiesPanel(getSelectedCountryCodes(), true);
                }
                openCountryAutocomplete();
            }
            if (countryAddSearchInput) {
                countryAddSearchInput.addEventListener('input', openCountryAutocomplete);
                countryAddSearchInput.addEventListener('focus', openCountryAutocomplete);
            }
            if (countryAutocompleteDropdown) {
                countryAutocompleteDropdown.addEventListener('click', function(e) {
                    var item = e.target.closest('.destination-country-autocomplete-item');
                    if (!item) return;
                    addCountryFromSuggestion(item.getAttribute('data-country-code'));
                });
            }
            document.addEventListener('click', function(e) {
                if (countryAutocompleteDropdown && countryAddSearchInput && !countryAutocompleteDropdown.contains(e.target) && !countryAddSearchInput.contains(e.target)) closeCountryAutocomplete();
                if (cityAutocompleteDropdown && cityAddSearchInput && !cityAutocompleteDropdown.contains(e.target) && !cityAddSearchInput.contains(e.target)) closeCityAutocomplete();
            });

            (function preselectCountries() {
                var codesToSelect = [];
                selectedIds.forEach(function(id) {
                    for (var code in countryCitiesData) {
                        var d = countryCitiesData[code];
                        if (d && (d.id == id || (d.cities && d.cities.some(function(c) { return c.id == id; })))) {
                            if (codesToSelect.indexOf(code) === -1) codesToSelect.push(code);
                            break;
                        }
                    }
                    if (codesToSelect.length === 0) {
                        for (var code in mergedCities) {
                            if ((mergedCities[code] || []).some(function(c) { return c.id == id; })) {
                                if (codesToSelect.indexOf(code) === -1) codesToSelect.push(code);
                                break;
                            }
                        }
                    }
                });
                codesToSelect.forEach(function(code) {
                    var opt = countryListEl && countryListEl.querySelector('.destination-country-option[value="' + code + '"]');
                    if (opt) opt.checked = true;
                });
                if (codesToSelect.length) fillCitiesPanel(codesToSelect, false);
                else {
                    if (panelDynamic) panelDynamic.style.display = 'none';
                    updateTaxonomySelectionCounts();
                }
            })();

            if (citySearchInput) {
                citySearchInput.addEventListener('input', function() { filterCitySearch(this.value); });
            }

            var cityAddSearchInput = document.getElementById('destinationCityAddSearch');
            var cityAutocompleteDropdown = document.getElementById('destinationCityAutocompleteDropdown');
            function getSelectedCityPathsInPanel() {
                var paths = [];
                if (!panelList) return paths;
                panelList.querySelectorAll('.destination-city-row input.location-checkbox:checked').forEach(function(cb) {
                    var row = cb.closest('.destination-city-row');
                    if (row) { var p = row.getAttribute('data-path'); if (p) paths.push(p); }
                });
                return paths;
            }
            function buildCityAutocompleteSuggestions(term) {
                var codes = getSelectedCountryCodes();
                if (!codes.length) return [];
                var selectedPaths = getSelectedCityPathsInPanel();
                var list = [];
                term = (term || '').toLowerCase().trim();
                codes.forEach(function(code) {
                    var countryName = cleanTaxonomyLabel((countryCitiesData[code] && countryCitiesData[code].title) ? countryCitiesData[code].title : (worldCountries[code] || code));
                    (mergedCities[code] || []).forEach(function(city) {
                        var title = cleanTaxonomyLabel(city.title || '');
                        var path = cleanTaxonomyLabel(countryName + ' - ' + title);
                        if (selectedPaths.indexOf(path) !== -1) return;
                        if (term && path.toLowerCase().indexOf(term) === -1 && title.toLowerCase().indexOf(term) === -1) return;
                        list.push({ code: code, countryName: countryName, path: path, city: city });
                    });
                });
                return list;
            }
            function openCityAutocomplete() {
                var term = cityAddSearchInput ? cityAddSearchInput.value : '';
                var list = buildCityAutocompleteSuggestions(term);
                if (!cityAutocompleteDropdown) return;
                cityAutocompleteDropdown.classList.toggle('is-open', list.length > 0);
                cityAutocompleteDropdown.innerHTML = '';
                list.slice(0, 50).forEach(function(item) {
                    var div = document.createElement('div');
                    div.className = 'destination-city-autocomplete-item';
                    div.textContent = item.path;
                    div.setAttribute('data-path', item.path);
                    div.setAttribute('data-country-code', item.code);
                    div.setAttribute('data-city-name', cleanTaxonomyLabel(item.city.title || ''));
                    if (item.city.id) div.setAttribute('data-loc-id', item.city.id);
                    cityAutocompleteDropdown.appendChild(div);
                });
                if (list.length === 0) cityAutocompleteDropdown.classList.remove('is-open');
            }
            function closeCityAutocomplete() {
                if (cityAutocompleteDropdown) cityAutocompleteDropdown.classList.remove('is-open');
            }
            function addCityFromSuggestion(path, code, cityName, locId) {
                if (!path) return;
                var row = Array.from(panelList.querySelectorAll('.destination-city-row')).find(function(r) { return r.getAttribute('data-path') === path; });
                if (!row) return;
                var cb = row.querySelector('input.location-checkbox');
                if (!cb) return;
                if (cb.checked) { openCityAutocomplete(); return; }
                cb.checked = true;
                if (cb.getAttribute('data-needs-create') === '1') {
                    cb.disabled = true;
                    ensureLocation(code, cityName, function(err, res) {
                        cb.disabled = false;
                        if (!err && res && res.id) {
                            cb.value = res.id;
                            cb.setAttribute('data-loc-id', res.id);
                            cb.removeAttribute('data-needs-create');
                            cb.removeAttribute('data-country-code');
                            cb.removeAttribute('data-city-name');
                        } else cb.checked = false;
                        onCheckboxChange();
                        openCityAutocomplete();
                    });
                } else {
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                    onCheckboxChange();
                    openCityAutocomplete();
                }
            }
            if (cityAddSearchInput) {
                cityAddSearchInput.addEventListener('input', openCityAutocomplete);
                cityAddSearchInput.addEventListener('focus', function() { openCityAutocomplete(); });
            }
            if (cityAutocompleteDropdown) {
                cityAutocompleteDropdown.addEventListener('click', function(e) {
                    var item = e.target.closest('.destination-city-autocomplete-item');
                    if (!item) return;
                    var path = item.getAttribute('data-path');
                    var code = item.getAttribute('data-country-code');
                    var cityName = item.getAttribute('data-city-name');
                    addCityFromSuggestion(path, code, cityName, item.getAttribute('data-loc-id'));
                });
            }
            if (selectAllCitiesBtn) {
                selectAllCitiesBtn.addEventListener('click', function() {
                    var rows = panelList.querySelectorAll('.destination-city-row');
                    var toCreate = [];
                    rows.forEach(function(row) {
                        if (row.style.display === 'none') return;
                        var cb = row.querySelector('input.location-checkbox');
                        if (!cb) return;
                        if (cb.checked) return;
                        if (cb.getAttribute('data-needs-create') === '1') toCreate.push(cb);
                        else { cb.checked = true; onCheckboxChange(); }
                    });
                    function runNext(i) {
                        if (i >= toCreate.length) { updateCountryIndeterminate(); updateTaxonomySelectionCounts(); return; }
                        var cb = toCreate[i];
                        var ccode = cb.getAttribute('data-country-code');
                        var cname = cb.getAttribute('data-city-name');
                        cb.disabled = true;
                        ensureLocation(ccode, cname, function(err, res) {
                            cb.disabled = false;
                            if (!err && res && res.id) {
                                cb.value = res.id;
                                cb.setAttribute('data-loc-id', res.id);
                                cb.removeAttribute('data-needs-create');
                                cb.removeAttribute('data-country-code');
                                cb.removeAttribute('data-city-name');
                                cb.checked = true;
                            }
                            onCheckboxChange();
                            runNext(i + 1);
                        });
                    }
                    runNext(0);
                });
            }
            if (deselectAllCitiesBtn) {
                deselectAllCitiesBtn.addEventListener('click', function() {
                    panelList.querySelectorAll('.destination-city-checkbox-label input.location-checkbox').forEach(function(cb) { cb.checked = false; });
                    onCheckboxChange();
                });
            }
        })();
        
        (function hydrateActivityCatalogs() {
            var boot = window.VOYAGE_EDIT_BOOTSTRAP || {};
            window.ALL_PROGRAMME_ACTIVITIES_CATALOG = boot.programmeActivitiesCatalog || [];
            window.ALL_TOUR_ACTIVITIES_CATALOG = boot.tourActivitiesCatalog || [];
            window.PROGRAMME_ACTIVITIES_CATALOG = boot.programmeActivitiesCatalog || [];
            window.TOUR_ACTIVITIES_SELECTED = boot.tourActivitiesSelected || [];
            window.TOUR_ACTIVITIES_CATALOG = boot.tourActivitiesCatalog || [];
            window.PROGRAM_API_URL = boot.programApiUrl || '';
            window.PROGRAM_VOYAGE_ID = boot.programVoyageId || boot.wpTourId || 0;
        })();

        (function activityRegionCatalogFilter() {
            function normalizeTerm(value) {
                return String(value || '').toLowerCase().trim().replace(/\s+/g, ' ');
            }

            function splitTerms(value) {
                if (!value) return [];
                return String(value)
                    .split(/[,;\/|]+/)
                    .map(function (item) { return normalizeTerm(item); })
                    .filter(Boolean);
            }

            function uniqueTerms(values) {
                return Array.from(new Set(values.filter(Boolean)));
            }

            function currentVoyageRegionTerms() {
                var values = [];

                document.querySelectorAll('.location-checkbox:checked').forEach(function (checkbox) {
                    var title = checkbox.getAttribute('data-loc-title');
                    if (title) {
                        values.push(title);
                    }
                });

                var addressInput = document.getElementById('address');
                if (addressInput && addressInput.value.trim()) {
                    values.push(addressInput.value.trim());
                }

                return uniqueTerms(values.flatMap(splitTerms));
            }

            function activityRegionTerms(activity) {
                return uniqueTerms([
                    activity && activity.region_name,
                    activity && activity.location_text,
                    activity && activity.place_text,
                ].flatMap(splitTerms));
            }

            function matchesActivityRegion(activity, requestedTerms) {
                if (!requestedTerms.length) {
                    return true;
                }

                var activityTerms = activityRegionTerms(activity);
                if (!activityTerms.length) {
                    return false;
                }

                return activityTerms.some(function (activityTerm) {
                    return requestedTerms.some(function (requestedTerm) {
                        return activityTerm === requestedTerm
                            || activityTerm.indexOf(requestedTerm) !== -1
                            || requestedTerm.indexOf(activityTerm) !== -1;
                    });
                });
            }

            function filteredCatalog(sourceKey) {
                var source = Array.isArray(window[sourceKey]) ? window[sourceKey] : [];
                var terms = currentVoyageRegionTerms();

                return source.filter(function (activity) {
                    return matchesActivityRegion(activity, terms);
                });
            }

            function syncQuickActivitySelects() {
                var catalogue = filteredCatalog('ALL_PROGRAMME_ACTIVITIES_CATALOG');

                document.querySelectorAll('.add-activity-select').forEach(function (select) {
                    var currentValue = select.value;
                    select.innerHTML = '';

                    var placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = '-- Activite rapide --';
                    select.appendChild(placeholder);

                    catalogue.forEach(function (activity) {
                        var option = document.createElement('option');
                        option.value = String(activity.id);
                        option.textContent = activity.title || ('Activite #' + activity.id);
                        select.appendChild(option);
                    });

                    if (currentValue && select.querySelector('option[value="' + currentValue + '"]')) {
                        select.value = currentValue;
                    } else {
                        select.value = '';
                    }
                });
            }

            function apply() {
                window.PROGRAMME_ACTIVITIES_CATALOG = filteredCatalog('ALL_PROGRAMME_ACTIVITIES_CATALOG');
                window.TOUR_ACTIVITIES_CATALOG = filteredCatalog('ALL_TOUR_ACTIVITIES_CATALOG');
                syncQuickActivitySelects();

                document.dispatchEvent(new CustomEvent('voyage-activity-region-change', {
                    detail: {
                        terms: currentVoyageRegionTerms(),
                    }
                }));
            }

            window.AjinsafroActivityRegionFilter = {
                apply: apply,
                currentTerms: currentVoyageRegionTerms,
                matches: matchesActivityRegion,
                getFilteredCatalog: function (kind) {
                    return kind === 'programme'
                        ? filteredCatalog('ALL_PROGRAMME_ACTIVITIES_CATALOG')
                        : filteredCatalog('ALL_TOUR_ACTIVITIES_CATALOG');
                }
            };

            document.addEventListener('change', function (event) {
                if (event.target && event.target.classList && event.target.classList.contains('location-checkbox')) {
                    apply();
                }
            });

            document.addEventListener('input', function (event) {
                if (event.target && event.target.id === 'address') {
                    apply();
                }
            });

            apply();
        })();

        (function programmeDaysManager() {
            if (window.VoyageEditorRuntime && window.VoyageEditorRuntime.ownsProgrammeBuilder) return;
            var accordion = document.getElementById('accordionProgrammeDays');
            var badge = document.getElementById('program-days-badge');
            var durationInput = document.getElementById('duration_day');
            var noDaysAlert = document.getElementById('program-no-days-alert');

            function count() {
                return (accordion ? accordion.querySelectorAll('.programme-day-card').length : 0);
            }
            function updateBadge() {
                if (badge) {
                    var n = count();
                    badge.textContent = n === 1 ? '1 jour' : n + ' jours';
                }
            }
            function updateDuration() {
                if (durationInput) {
                    var n = count();
                    durationInput.value = n > 0 ? n : (durationInput.value || 1);
                }
            }
            function parseRequestedDuration() {
                if (!durationInput) return count() || 1;
                var parsed = parseInt(String(durationInput.value || '').trim(), 10);
                if (!Number.isFinite(parsed) || parsed < 1) parsed = 1;
                if (parsed > 365) parsed = 365;
                return parsed;
            }
            function dayCardHasMeaningfulData(card) {
                if (!card) return false;
                if (card.querySelectorAll('.programme-activity-row').length > 0) return true;

                var titleInput = card.querySelector('input[name$="[day_title]"]');
                if (titleInput && String(titleInput.value || '').trim() !== '') return true;

                var cityInput = card.querySelector('input[name$="[city]"]');
                if (cityInput && String(cityInput.value || '').trim() !== '') return true;

                var descriptionInput = card.querySelector('textarea[name$="[description]"]');
                if (descriptionInput && String(descriptionInput.value || '').trim() !== '') return true;

                var contentInput = card.querySelector('textarea[name$="[content_html]"]');
                if (contentInput && String(contentInput.value || '').trim() !== '') return true;

                var notesInput = card.querySelector('textarea[name$="[notes]"]');
                if (notesInput && String(notesInput.value || '').trim() !== '') return true;

                return false;
            }
            function syncDaysFromDuration(options) {
                if (!accordion) return;
                var opts = options || {};
                var desired = parseRequestedDuration();
                var current = count();

                if (desired === current) return;

                if (desired > current) {
                    while (count() < desired) {
                        addDay({
                            openNewDay: false,
                            skipExtrasRefresh: true,
                            skipDurationSync: true
                        });
                    }
                    renumber();

                    if (opts.openLast !== false) {
                        var lastCard = accordion.querySelector('.programme-day-card:last-child');
                        var collapseEl = lastCard ? lastCard.querySelector('.accordion-collapse') : null;
                        var toggleBtn = lastCard ? lastCard.querySelector('.accordion-button') : null;
                        if (collapseEl) {
                            accordion.querySelectorAll('.accordion-collapse.show').forEach(function(el) {
                                el.classList.remove('show');
                            });
                            accordion.querySelectorAll('.accordion-button').forEach(function(btn) {
                                btn.classList.add('collapsed');
                                btn.setAttribute('aria-expanded', 'false');
                            });
                            if (window.bootstrap && bootstrap.Collapse) {
                                new bootstrap.Collapse(collapseEl, { toggle: true });
                            } else {
                                collapseEl.classList.add('show');
                            }
                        }
                        if (toggleBtn) {
                            toggleBtn.classList.remove('collapsed');
                            toggleBtn.setAttribute('aria-expanded', 'true');
                        }
                    }
                    return;
                }

                var cards = Array.prototype.slice.call(accordion.querySelectorAll('.programme-day-card'));
                var toRemove = cards.slice(desired);
                if (!toRemove.length) return;

                var hasData = toRemove.some(dayCardHasMeaningfulData);
                if (hasData && opts.force !== true) {
                    var ok = confirm('La durée est inférieure au nombre de jours existants. Les derniers jours contiennent des données. Voulez-vous les supprimer ?');
                    if (!ok) {
                        durationInput.value = current;
                        return;
                    }
                }

                toRemove.forEach(function(card) { card.remove(); });
                if (count() === 0 && noDaysAlert) noDaysAlert.style.display = '';
                renumber();
            }
            function escapeDayTypeHtml(s) {
                var d = document.createElement('div');
                d.textContent = s != null ? String(s) : '';
                return d.innerHTML;
            }
            function buildProgramDayTypeOptionsHtml(selectedValue) {
                var list = (window.PROGRAM_DAY_TYPES && window.PROGRAM_DAY_TYPES.length)
                    ? window.PROGRAM_DAY_TYPES
                    : [
                        { value: 'arrivee', label: 'Arrivée' },
                        { value: 'visite', label: 'Visite' },
                        { value: 'transfert', label: 'Transfert' },
                        { value: 'libre', label: 'Libre' }
                    ];
                var sel = selectedValue || 'visite';
                var html = '';
                list.forEach(function (opt) {
                    var v = opt.value || '';
                    var l = opt.label || v;
                    html += '<option value="' + escapeDayTypeHtml(v) + '"' + (sel === v ? ' selected' : '') + '>' + escapeDayTypeHtml(l) + '</option>';
                });
                return html;
            }
            function renumber() {
                if (!accordion) return;
                var cards = accordion.querySelectorAll('.programme-day-card');
                cards.forEach(function(card, i) {
                    card.setAttribute('data-day-index', i);
                    var prefixOld = 'programme_days[' + (card.getAttribute('data-day-index') || i) + ']';
                    var prefixNew = 'programme_days[' + i + ']';
                    card.querySelectorAll('[name^="programme_days["]').forEach(function(el) {
                        el.name = el.name.replace(/^programme_days\[\d+\]/, 'programme_days[' + i + ']');
                    });
                    card.querySelectorAll('[data-day-index]').forEach(function(el) { el.setAttribute('data-day-index', i); });
                    card.querySelectorAll('.add-activity-select, .add-activity-to-day').forEach(function(el) { el.setAttribute('data-day-index', i); });
                    var label = card.querySelector('.programme-day-label');
                    var titleInput = card.querySelector('input[name$="[day_title]"]');
                    var dayNum = i + 1;
                    var title = (titleInput && titleInput.value.trim()) ? titleInput.value.trim() : ('Jour ' + dayNum);
                    if (label) label.textContent = 'JOUR ' + dayNum + ' — ' + title;
                });
                updateBadge();
                updateDuration();
                if (window.syncProgrammeDayTargetOptions) window.syncProgrammeDayTargetOptions();
                if (window.autosaveProgram) window.autosaveProgram();
            }
            function newDayHtml(index) {
                var collapseId = 'collapse-day-new-' + index + '-' + Date.now();
                return '<div class="accordion-item programme-day-card" data-day-id="" data-day-index="' + index + '">' +
                    '<h2 class="accordion-header programme-day-header">' +
                    '<span class="drag-handle me-2 text-muted cursor-grab" title="Déplacer"><i class="bx bx-dots-vertical-rounded"></i></span>' +
                    '<button class="accordion-button collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#' + collapseId + '" aria-expanded="false" aria-controls="' + collapseId + '">' +
                    '<span class="programme-day-label">JOUR ' + (index + 1) + ' — Jour ' + (index + 1) + '</span></button>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger me-2 btn-remove-program-day" title="Supprimer ce jour"><i class="bx bx-trash"></i></button></h2>' +
                    '<div id="' + collapseId + '" class="accordion-collapse collapse" data-bs-parent="#accordionProgrammeDays">' +
                    '<div class="accordion-body" data-day-index="' + index + '" data-day-id="">' +
                    '<input type="hidden" name="programme_days[' + index + '][id]" value="">' +
                    '<input type="hidden" name="programme_days[' + index + '][day_id]" value="">' +
                    '<div class="program-day-form" data-day-index="' + index + '">' +
                    '<div class="field-mode"><label class="form-label">Type / mode</label>' +
                    '<select name="programme_days[' + index + '][mode]" class="form-select programme-day-mode">' +
                    '<option value="program" selected>Visite / programme</option><option value="free">Libre</option></select></div>' +
                    '<div class="field-type"><label class="form-label">Type de jour</label>' +
                    '<select name="programme_days[' + index + '][day_type]" class="form-select">' +
                    buildProgramDayTypeOptionsHtml('visite') + '</select></div>' +
                    '<div class="field-title"><label class="form-label">Titre du jour</label>' +
                    '<input type="text" class="form-control" name="programme_days[' + index + '][day_title]" placeholder="Ex. : Jour ' + (index + 1) + ' — Arrivée"></div>' +
                    '<div class="field-ville"><label class="form-label">Ville</label>' +
                    '<input type="text" class="form-control" name="programme_days[' + index + '][city]" placeholder="Ex. : Marrakech"></div>' +
                    '<div class="field-resume ve-rich-field"><label class="form-label">Résumé</label>' +
                    '<textarea class="form-control programme-plain-editor" name="programme_days[' + index + '][description]" rows="3" placeholder="Résumé du jour"></textarea></div>' +
                    '<div class="field-description programme-day-detail ve-rich-field"><label class="form-label">Description détaillée</label>' +
                    '<textarea class="form-control programme-plain-editor" name="programme_days[' + index + '][content_html]" rows="5" placeholder="Programme détaillé du jour"></textarea></div>' +
                    '<div class="field-notes programme-day-notes ve-rich-field"><label class="form-label">Notes</label>' +
                    '<textarea class="form-control programme-plain-editor" name="programme_days[' + index + '][notes]" rows="4" placeholder="Notes du jour"></textarea></div>' +
                    '</div>' +
                    '<input type="hidden" name="programme_days[' + index + '][title]" value="Jour ' + (index + 1) + '">' +
                    '<input type="hidden" name="programme_days[' + index + '][flights]" value="">' +
                    '<input type="hidden" name="programme_days[' + index + '][hotel_id]" value="">' +
                    '<input type="hidden" name="programme_days[' + index + '][transfer_ids]" value="">' +
                    '<h6 class="mt-4 mb-2">Activités du jour</h6>' +
                    '<div class="programme-activities-list mb-3" data-day-index="' + index + '" data-day-id="">' + '</div>' +
                    '</div></div></div>';
            }
            function addDay(options) {
                var opts = options || {};
                if (!accordion) return;
                if (noDaysAlert) noDaysAlert.style.display = 'none';
                var n = count();
                var div = document.createElement('div');
                div.innerHTML = newDayHtml(n).trim();
                accordion.appendChild(div.firstChild);
                if (opts.skipDurationSync === true) {
                    updateBadge();
                } else {
                    renumber();
                }
                var lastCard = accordion.querySelector('.programme-day-card:last-child');
                if (lastCard && opts.openNewDay !== false) {
                    var collapseEl = lastCard.querySelector('.accordion-collapse');
                    var toggleBtn = lastCard.querySelector('.accordion-button');
                    if (collapseEl) {
                        accordion.querySelectorAll('.accordion-collapse.show').forEach(function(el) {
                            el.classList.remove('show');
                        });
                        accordion.querySelectorAll('.accordion-button').forEach(function(btn) {
                            btn.classList.add('collapsed');
                            btn.setAttribute('aria-expanded', 'false');
                        });
                        if (window.bootstrap && bootstrap.Collapse) {
                            new bootstrap.Collapse(collapseEl, { toggle: true });
                        } else {
                            collapseEl.classList.add('show');
                        }
                    }
                    if (toggleBtn) {
                        toggleBtn.classList.remove('collapsed');
                        toggleBtn.setAttribute('aria-expanded', 'true');
                    }
                }
                attachDragToCards();
                if (window.updateProgrammeDayExtras && opts.skipExtrasRefresh !== true) {
                    try {
                        window.updateProgrammeDayExtras(String(count() - 1));
                    } catch (e) {}
                }
            }
            function removeDay(btn) {
                var card = btn.closest('.programme-day-card');
                if (!card || !accordion) return;
                if (count() <= 1) {
                    alert('Il doit rester au moins un jour.');
                    return;
                }
                if (!confirm('Supprimer ce jour ? Les activités du jour seront supprimées. La sauvegarde sera effective au clic sur « Enregistrer ».')) return;
                card.remove();
                if (count() === 0 && noDaysAlert) noDaysAlert.style.display = '';
                renumber();
                var cards = accordion.querySelectorAll('.programme-day-card');
                if (window.dayItemsManager && window.updateProgrammeDayExtras) {
                    cards.forEach(function(c, i) {
                        window.dayItemsManager.loadFromForm(String(i));
                        window.updateProgrammeDayExtras(String(i));
                    });
                }
            }
            function bootProgrammeDaysManager() {
                updateBadge();
                updateDuration();
                document.getElementById('btn-add-program-day') && document.getElementById('btn-add-program-day').addEventListener('click', addDay);
                document.getElementById('btn-add-program-day-empty') && document.getElementById('btn-add-program-day-empty').addEventListener('click', function() { addDay(); });
                accordion && accordion.addEventListener('click', function(e) {
                    if (e.target.closest('.btn-remove-program-day')) {
                        e.preventDefault();
                        removeDay(e.target.closest('.btn-remove-program-day'));
                    }
                });
                accordion && accordion.addEventListener('input', function(e) {
                    if (e.target.matches('input[name$="[day_title]"]')) {
                        var card = e.target.closest('.programme-day-card');
                        var i = parseInt(card.getAttribute('data-day-index'), 10);
                        var label = card.querySelector('.programme-day-label');
                        var hiddenTitle = card.querySelector('input[name$="[title]"]');
                        var currentTitle = e.target.value.trim() || ('Jour ' + (i + 1));
                        if (hiddenTitle) hiddenTitle.value = currentTitle;
                        if (label) label.textContent = 'JOUR ' + (i + 1) + ' — ' + (e.target.value.trim() || ('Jour ' + (i + 1)));
                    }
                });
                document.getElementById('edit-voyage-form') && document.getElementById('edit-voyage-form').addEventListener('submit', function() {
                    if (durationInput) durationInput.value = count() || 1;
                });

                if (durationInput) {
                    durationInput.addEventListener('change', function () {
                        syncDaysFromDuration({ openLast: false });
                    });
                    durationInput.addEventListener('blur', function () {
                        syncDaysFromDuration({ openLast: false });
                    });
                }

                document.addEventListener('click', function (e) {
                    var trigger = e.target && e.target.closest
                        ? e.target.closest('[data-v2-nav="s-programme"], [data-v2-next="s-programme"], a[href="#program-days"]')
                        : null;
                    if (!trigger) return;
                    setTimeout(function () {
                        syncDaysFromDuration({ openLast: false });
                    }, 0);
                });

                document.addEventListener('shown.bs.tab', function (e) {
                    var href = e && e.target && e.target.getAttribute ? e.target.getAttribute('href') : '';
                    if (href === '#program-days') {
                        syncDaysFromDuration({ openLast: false });
                    }
                });

                attachDragToCards();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootProgrammeDaysManager);
            } else {
                bootProgrammeDaysManager();
            }
            function attachDragToCards() {
                if (!accordion) return;
                var cards = accordion.querySelectorAll('.programme-day-card');
                var dragged = null;
                cards.forEach(function(card) {
                    card.draggable = true;
                    card.ondragstart = function(e) {
                        dragged = card;
                        e.dataTransfer.setData('text/plain', '');
                        e.dataTransfer.effectAllowed = 'move';
                        card.classList.add('opacity-50');
                    };
                    card.ondragend = function() {
                        card.classList.remove('opacity-50');
                        dragged = null;
                    };
                    card.ondragover = function(e) {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                        if (dragged && dragged !== card) card.classList.add('border-primary');
                    };
                    card.ondragleave = function() { card.classList.remove('border-primary'); };
                    card.ondrop = function(e) {
                        e.preventDefault();
                        card.classList.remove('border-primary');
                        if (!dragged || dragged === card) return;
                        var next = card.nextElementSibling;
                        accordion.insertBefore(dragged, next);
                        renumber();
                    };
                });
            }
        })();

        window.buildProgrammeDaysPayload = function() {
            var accordion = document.getElementById('accordionProgrammeDays');
            if (!accordion) return [];
            var cards = accordion.querySelectorAll('.programme-day-card');
            var programmeDays = [];

            function fieldValue(scope, selector) {
                var field = scope.querySelector(selector);
                return field ? field.value : '';
            }

            function checkboxValue(scope, selector, fallback) {
                var field = scope.querySelector(selector);
                if (!field) {
                    return fallback;
                }
                return field.checked ? 1 : 0;
            }

            cards.forEach(function(card, i) {
                var dayId = fieldValue(card, 'input[name$="[id]"]') || card.getAttribute('data-day-id') || '';
                var dayTitle = fieldValue(card, 'input[name$="[day_title]"]').trim();
                var title = fieldValue(card, 'input[name$="[title]"]').trim();
                var notes = fieldValue(card, 'textarea[name$="[notes]"]').trim();
                var mode = fieldValue(card, 'select[name$="[mode]"]') === 'free' ? 'free' : 'program';
                var activities = [];

                card.querySelectorAll('.programme-activity-row').forEach(function(row, k) {
                    var activityId = parseInt(fieldValue(row, 'input[name$="[activity_id]"]') || '0', 10);
                    if (activityId <= 0) {
                        return;
                    }

                    activities.push({
                        day_activity_id: fieldValue(row, 'input[name$="[day_activity_id]"]'),
                        activity_id: activityId,
                        sort_order: k,
                        is_included: checkboxValue(row, 'input[type="checkbox"][name$="[is_included]"]', 1),
                        day_scope: fieldValue(row, 'select[name$="[day_scope]"]') || 'fixed',
                        is_mandatory: checkboxValue(row, 'input[type="checkbox"][name$="[is_mandatory]"]', 0),
                        custom_title: fieldValue(row, '[name$="[custom_title]"]'),
                        custom_description: fieldValue(row, '[name$="[custom_description]"]')
                    });
                });

                programmeDays.push({
                    id: dayId,
                    day_id: fieldValue(card, 'input[name$="[day_id]"]') || dayId,
                    mode: mode,
                    day_title: dayTitle,
                    city: fieldValue(card, 'input[name$="[city]"]'),
                    day_type: fieldValue(card, 'select[name$="[day_type]"]') || 'visite',
                    content_html: fieldValue(card, 'textarea[name$="[content_html]"]'),
                    notes: notes,
                    title: title || dayTitle,
                    description: fieldValue(card, 'textarea[name$="[description]"]'),
                    hotel_id: fieldValue(card, 'input[name$="[hotel_id]"]'),
                    transfer_ids: fieldValue(card, 'input[name$="[transfer_ids]"]'),
                    flights: fieldValue(card, 'input[name$="[flights]"]'),
                    activities: activities
                });
            });

            return programmeDays;
        };

        window.buildProgramFromForm = function() {
            var programmeDays = window.buildProgrammeDaysPayload ? window.buildProgrammeDaysPayload() : [];
            var programDays = programmeDays.map(function(day, i) {
                var dayId = day.id || day.day_id || '';
                var items = [];

                (day.activities || []).forEach(function(activity, k) {
                    if (!activity.activity_id) {
                        return;
                    }

                    items.push({
                        uid: 'act-' + k,
                        type: 'activity',
                        ref_id: parseInt(activity.activity_id, 10),
                        sort: k
                    });
                });

                return {
                    day_uid: dayId ? ('day-' + dayId) : ('new-' + i + '-' + Date.now()),
                    day_number: i + 1,
                    title: day.day_title || day.title || ('Jour ' + (i + 1)),
                    notes: day.notes || '',
                    mode: day.mode === 'free' ? 'free' : 'program',
                    items: items
                };
            });

            return { program_days: programDays };
        };

        window.syncProgrammeDaysPayload = function(disableProgrammeInputs) {
            var payloadField = document.getElementById('programme-days-payload');
            var form = document.getElementById('edit-voyage-form');
            var programmeDays = window.buildProgrammeDaysPayload ? window.buildProgrammeDaysPayload() : [];

            if (payloadField) {
                payloadField.value = JSON.stringify(programmeDays);
            }

            if (!disableProgrammeInputs || !form) {
                return programmeDays;
            }

            form.querySelectorAll('[name^="programme_days["]').forEach(function(field) {
                field.disabled = true;
            });

            return programmeDays;
        };

        window.autosaveProgram = function() {
            if (!window.PROGRAM_API_URL) return;
            var payload = window.buildProgramFromForm && window.buildProgramFromForm();
            if (!payload) return;
            var token = document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value;
            fetch(window.PROGRAM_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    var toast = document.createElement('div');
                    toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                    toast.style.cssText = 'top:16px;right:16px;z-index:9999;min-width:200px;';
                    toast.innerHTML = (data.message || 'Enregistré') + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    document.body.appendChild(toast);
                    setTimeout(function() { toast.remove(); }, 3000);
                }
            }).catch(function() {});
        };

        (function bindProgrammeDaysPayloadSync() {
            var form = document.getElementById('edit-voyage-form');
            if (!form) return;

            window.syncProgrammeDaysPayload(false);

            form.addEventListener('submit', function() {
                window.syncProgrammeDaysPayload(true);
            }, true);
        })();

        function updateProgrammeDayInclus(card) {
            if (!card) return;
            var list = card.querySelector('.programme-activities-list');
            if (!list) return;
            var dayIndex = card.getAttribute('data-day-index');
            if (dayIndex != null && window.updateProgrammeDayExtras) {
                window.updateProgrammeDayExtras(dayIndex);
            }
        }

        function updateProgrammeDayExtras(dayIndex) {
            var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
            if (!card) return;
            var extrasEl = card.querySelector('.programme-day-extras');
            if (!extrasEl) return;
            var day = window.dayItemsManager ? window.dayItemsManager.getDay(dayIndex) : { hotel_id: null, transfer_ids: [], flights: [] };
            var dayNumber = parseInt(dayIndex || '0', 10) + 1;
            
            // Collecter toutes les données
            var sections = {
                activities: [],
                hotels: null,
                transfers: { arrival: [], departure: [] },
                flights: { outbound: null, inbound: null, internal: [] }
            };
            
            // 1. ACTIVIT?S : depuis le DOM
            var activitiesList = card.querySelector('.programme-activities-list');
            if (activitiesList) {
                activitiesList.querySelectorAll('.programme-activity-row').forEach(function(row) {
                    var titleEl = row.querySelector('.fw-medium');
                    var customTitleInp = row.querySelector('input[name*="[custom_title]"]');
                    var isIncludedEl = row.querySelector('input[name*="[is_included]"]');
                    var statusEl = row.querySelector('select[name*="[status]"], input[name*="[status]"]');
                    var title = '';
                    if (customTitleInp && customTitleInp.value.trim()) {
                        title = customTitleInp.value.trim();
                    } else if (titleEl) {
                        title = titleEl.textContent.trim();
                    } else {
                        title = 'Activité';
                    }
                    var status = statusEl ? String(statusEl.value || '').trim() : '';
                    var isIncluded = status ? status === 'included' : (isIncludedEl ? isIncludedEl.checked : true);
                    sections.activities.push({ title: title, isIncluded: isIncluded, status: status || (isIncluded ? 'included' : 'optional') });
                });
            }
            
            // 2. H?TELS : depuis dayItemsManager OU depuis tour_hotels rows
            var hotelData = null;
            if (day.hotel_id && window.tourHotelsData && window.tourHotelsData[day.hotel_id]) {
                hotelData = window.tourHotelsData[day.hotel_id];
            } else {
                // Chercher dans tour_hotels rows (nouveau format : check_in_day / check_out_day)
                document.querySelectorAll('.tour-hotel-row').forEach(function(row) {
                    var checkInSel = row.querySelector('select[name^="tour_hotels["][name$="[check_in_day]"]');
                    var checkOutSel = row.querySelector('select[name^="tour_hotels["][name$="[check_out_day]"]');
                    var isInRange = false;
                    if (checkInSel && checkOutSel) {
                        var checkIn = parseInt(checkInSel.value || '1', 10);
                        var checkOut = parseInt(checkOutSel.value || '1', 10);
                        isInRange = (dayNumber >= checkIn && dayNumber <= checkOut);
                    } else {
                        // Compatibilit? ancien format : day_number
                        var daySel = row.querySelector('select[name^="tour_hotels["][name$="[day_number]"]');
                        if (daySel && parseInt(daySel.value || '0', 10) === dayNumber) {
                            isInRange = true;
                        }
                    }
                    if (isInRange) {
                        var nameInp = row.querySelector('input[name^="tour_hotels["][name$="[hotel_name]"]');
                        var starsInp = row.querySelector('input[name^="tour_hotels["][name$="[stars]"]');
                        var optionalInp = row.querySelector('input[name^="tour_hotels["][name$="[is_optional]"]');
                        if (nameInp && nameInp.value.trim()) {
                            hotelData = {
                                hotel_name: nameInp.value.trim(),
                                stars: starsInp ? starsInp.value : null,
                                is_optional: optionalInp ? optionalInp.checked : false
                            };
                        }
                    }
                });
            }
            sections.hotels = hotelData;
            
            // 3. TRANSFERTS : depuis dayItemsManager ET depuis tour_transfer rows
            var transferIds = day.transfer_ids || [];
            var transferMap = {};
            if (window.tourTransfersData) {
                (window.tourTransfersData.arrival || []).concat(window.tourTransfersData.departure || []).forEach(function(t) {
                    if (transferIds.indexOf(t.id) !== -1) {
                        transferMap[t.id] = t;
                    }
                });
            }
            // Chercher aussi dans les lignes du formulaire principal (nouveau format unifi?)
            document.querySelectorAll('.tour-transfer-row').forEach(function(row) {
                var daySel = row.querySelector('select[name*="[day_number]"]');
                if (daySel && parseInt(daySel.value || '0', 10) === dayNumber) {
                    var fromInp = row.querySelector('input[name*="[from_label]"]');
                    var toInp = row.querySelector('input[name*="[to_label]"]');
                    var vehicleInp = row.querySelector('input[name*="[vehicle_type]"]');
                    var pickupInp = row.querySelector('input[name*="[pickup_time]"]');
                    var dropoffInp = row.querySelector('input[name*="[dropoff_time]"]');
                    if (fromInp && toInp && (fromInp.value.trim() || toInp.value.trim())) {
                        // Par défaut, on utilise 'arrival' pour compatibilité avec le modèle
                        var transfer = {
                            from_label: fromInp.value.trim() || '',
                            to_label: toInp.value.trim() || '',
                            vehicle_type: vehicleInp ? vehicleInp.value.trim() : '',
                            pickup_time: pickupInp ? pickupInp.value.trim() : '',
                            dropoff_time: dropoffInp ? dropoffInp.value.trim() : '',
                            direction: 'arrival' // Par défaut pour compatibilité
                        };
                        sections.transfers.arrival.push(transfer);
                    }
                }
            });
            // Compatibilit? ancien format : tour-transfer-arrival-row / tour-transfer-departure-row
            document.querySelectorAll('.tour-transfer-arrival-row, .tour-transfer-departure-row').forEach(function(row) {
                var daySel = row.querySelector('select[name*="[day_number]"]');
                if (daySel && parseInt(daySel.value || '0', 10) === dayNumber) {
                    var fromInp = row.querySelector('input[name*="[from_label]"]');
                    var toInp = row.querySelector('input[name*="[to_label]"]');
                    var vehicleInp = row.querySelector('input[name*="[vehicle_type]"]');
                    var pickupInp = row.querySelector('input[name*="[pickup_time]"]');
                    var dropoffInp = row.querySelector('input[name*="[dropoff_time]"]');
                    if (fromInp && toInp && (fromInp.value.trim() || toInp.value.trim())) {
                        var direction = row.classList.contains('tour-transfer-arrival-row') ? 'arrival' : 'departure';
                        var transfer = {
                            from_label: fromInp.value.trim() || '',
                            to_label: toInp.value.trim() || '',
                            vehicle_type: vehicleInp ? vehicleInp.value.trim() : '',
                            pickup_time: pickupInp ? pickupInp.value.trim() : '',
                            dropoff_time: dropoffInp ? dropoffInp.value.trim() : '',
                            direction: direction
                        };
                        sections.transfers[direction].push(transfer);
                    }
                }
            });
            // Ajouter les transferts depuis tourTransfersData
            Object.keys(transferMap).forEach(function(id) {
                var t = transferMap[id];
                sections.transfers[t.direction].push(t);
            });
            
            // 4. VOLS : depuis flight_options dans le formulaire principal
            document.querySelectorAll('.flight-opt-card, .card').forEach(function(flightCard) {
                var dayInp = flightCard.querySelector('select[name*="[day_number]"], input[name*="[day_number]"]');
                if (dayInp && parseInt(dayInp.value || '0', 10) === dayNumber && !dayInp.disabled) {
                    var typeSel = flightCard.querySelector('select[name*="[type]"]');
                    var fromInp = flightCard.querySelector('input[name*="[from_city]"]');
                    var toInp = flightCard.querySelector('input[name*="[to_city]"]');
                    var dateInp = flightCard.querySelector('input[name*="[departure_date]"]');
                    var timeInp = flightCard.querySelector('input[name*="[departure_time]"]');
                    var type = typeSel ? typeSel.value : 'internal';
                    var flight = {
                        from: fromInp ? fromInp.value.trim() : '',
                        to: toInp ? toInp.value.trim() : '',
                        date: dateInp ? dateInp.value.trim() : '',
                        time: timeInp ? timeInp.value.trim() : ''
                    };
                    if (type === 'outbound') {
                        sections.flights.outbound = flight;
                    } else if (type === 'inbound') {
                        sections.flights.inbound = flight;
                    } else {
                        sections.flights.internal.push(flight);
                    }
                }
            });
            
            // Générer le HTML structuré
            var html = '<div class="day-summary-container mt-2">';
            var hasAnyContent = false;
            
            // Activités
            if (sections.activities.length > 0) {
                hasAnyContent = true;
                html += '<div class="day-summary-card mb-2 border rounded p-2 bg-light">';
                html += '<div class="d-flex justify-content-between align-items-center mb-1">';
                html += '<div class="d-flex align-items-center gap-2"><i class="bx bx-list-check text-primary"></i><strong class="small">Activités (' + sections.activities.length + ')</strong></div>';
                html += '<div class="d-flex gap-1">';
                html += '<button type="button" class="btn btn-xs btn-outline-primary btn-sm day-summary-config-btn" data-day-index="' + dayIndex + '" data-tab="activities" title="Configurer"><i class="bx bx-cog"></i></button>';
                html += '<button type="button" class="btn btn-xs btn-outline-danger btn-sm day-summary-remove-btn" data-day-index="' + dayIndex + '" data-type="activities" title="Retirer les activités optionnelles"><i class="bx bx-trash"></i></button>';
                html += '</div>';
                html += '</div>';
                var visibleActs = sections.activities.slice(0, 3);
                visibleActs.forEach(function(act) {
                    html += '<div class="small text-muted mb-1">"Â¢ ' + act.title;
                    if (act.status === 'proposition') html += ' <span class="badge bg-info text-dark">Proposition</span>';
                    else if (act.isIncluded) html += ' <span class="badge bg-success">Inclus</span>';
                    else html += ' <span class="badge bg-warning text-dark">Option client</span>';
                    html += '</div>';
                });
                if (sections.activities.length > 3) {
                    html += '<div class="small text-muted">... et ' + (sections.activities.length - 3) + ' autre(s)</div>';
                }
                html += '</div>';
            }
            
            // Hôtels
            if (sections.hotels) {
                hasAnyContent = true;
                html += '<div class="day-summary-card mb-2 border rounded p-2 bg-light">';
                html += '<div class="d-flex justify-content-between align-items-center mb-1">';
                html += '<div class="d-flex align-items-center gap-2"><i class="bx bx-hotel text-primary"></i><strong class="small">Hôtel</strong></div>';
                html += '<div class="d-flex gap-1">';
                html += '<button type="button" class="btn btn-xs btn-outline-primary btn-sm day-summary-config-btn" data-day-index="' + dayIndex + '" data-tab="hotels" title="Configurer"><i class="bx bx-cog"></i></button>';
                html += '<button type="button" class="btn btn-xs btn-outline-danger btn-sm day-summary-remove-btn" data-day-index="' + dayIndex + '" data-type="hotel" title="Retirer"><i class="bx bx-trash"></i></button>';
                html += '</div></div>';
                html += '<div class="small text-muted mb-1"><strong>' + sections.hotels.hotel_name + '</strong>';
                if (sections.hotels.stars) {
                    var stars = '';
                    for (var i = 0; i < parseInt(sections.hotels.stars, 10); i++) stars += '?Ëœ?';
                    html += ' <span class="badge bg-warning text-dark">' + stars + '</span>';
                }
                if (sections.hotels.is_optional) html += ' <span class="badge bg-warning text-dark">Option client</span>';
                html += '</div>';
                html += '</div>';
            }
            
            // Transferts
            var totalTransfers = sections.transfers.arrival.length + sections.transfers.departure.length;
            if (totalTransfers > 0) {
                hasAnyContent = true;
                html += '<div class="day-summary-card mb-2 border rounded p-2 bg-light">';
                html += '<div class="d-flex justify-content-between align-items-center mb-1">';
                html += '<div class="d-flex align-items-center gap-2"><i class="bx bx-car text-primary"></i><strong class="small">Transferts (' + totalTransfers + ')</strong></div>';
                html += '<div class="d-flex gap-1">';
                html += '<button type="button" class="btn btn-xs btn-outline-primary btn-sm day-summary-config-btn" data-day-index="' + dayIndex + '" data-tab="transfers" title="Configurer"><i class="bx bx-cog"></i></button>';
                html += '<button type="button" class="btn btn-xs btn-outline-danger btn-sm day-summary-remove-btn" data-day-index="' + dayIndex + '" data-type="transfers" title="Tout retirer"><i class="bx bx-trash"></i></button>';
                html += '</div></div>';
                if (sections.transfers.arrival.length > 0) {
                    html += '<div class="small mb-1"><span class="badge bg-success">Arrivée</span>';
                    sections.transfers.arrival.slice(0, 2).forEach(function(t) {
                        html += ' <span class="text-muted">' + (t.from_label || '?') + ' ? ' + (t.to_label || '?');
                        if (t.vehicle_type) html += ' <small>(' + t.vehicle_type + ')</small>';
                        html += '</span>';
                    });
                    if (sections.transfers.arrival.length > 2) html += ' <small class="text-muted">+ ' + (sections.transfers.arrival.length - 2) + ' autre(s)</small>';
                    html += '</div>';
                }
                if (sections.transfers.departure.length > 0) {
                    html += '<div class="small mb-1"><span class="badge bg-danger">Départ</span>';
                    sections.transfers.departure.slice(0, 2).forEach(function(t) {
                        html += ' <span class="text-muted">' + (t.from_label || '?') + ' ? ' + (t.to_label || '?');
                        if (t.vehicle_type) html += ' <small>(' + t.vehicle_type + ')</small>';
                        html += '</span>';
                    });
                    if (sections.transfers.departure.length > 2) html += ' <small class="text-muted">+ ' + (sections.transfers.departure.length - 2) + ' autre(s)</small>';
                    html += '</div>';
                }
                html += '</div>';
            }
            
            // Vols
            var totalFlights = (sections.flights.outbound ? 1 : 0) + (sections.flights.inbound ? 1 : 0) + sections.flights.internal.length;
            if (totalFlights > 0) {
                hasAnyContent = true;
                html += '<div class="day-summary-card mb-2 border rounded p-2 bg-light">';
                html += '<div class="d-flex justify-content-between align-items-center mb-1">';
                html += '<div class="d-flex align-items-center gap-2"><i class="bx bx-trip text-primary"></i><strong class="small">Vols (' + totalFlights + ')</strong></div>';
                html += '<div class="d-flex gap-1">';
                html += '<button type="button" class="btn btn-xs btn-outline-primary btn-sm day-summary-config-btn" data-day-index="' + dayIndex + '" data-tab="flights" title="Configurer"><i class="bx bx-cog"></i></button>';
                html += '<button type="button" class="btn btn-xs btn-outline-danger btn-sm day-summary-remove-btn" data-day-index="' + dayIndex + '" data-type="flights" title="Tout retirer"><i class="bx bx-trash"></i></button>';
                html += '</div></div>';
                if (sections.flights.outbound) {
                    html += '<div class="small mb-1"><span class="badge bg-info">Aller</span> <span class="text-muted">' + (sections.flights.outbound.from || '?') + ' ? ' + (sections.flights.outbound.to || '?');
                    if (sections.flights.outbound.date) html += ' <small>(' + sections.flights.outbound.date + ')</small>';
                    html += '</span></div>';
                }
                if (sections.flights.inbound) {
                    html += '<div class="small mb-1"><span class="badge bg-info">Retour</span> <span class="text-muted">' + (sections.flights.inbound.from || '?') + ' ? ' + (sections.flights.inbound.to || '?');
                    if (sections.flights.inbound.date) html += ' <small>(' + sections.flights.inbound.date + ')</small>';
                    html += '</span></div>';
                }
                if (sections.flights.internal.length > 0) {
                    html += '<div class="small mb-1"><span class="badge bg-secondary">Internes</span>';
                    sections.flights.internal.slice(0, 2).forEach(function(f) {
                        html += ' <span class="text-muted">' + (f.from || '?') + ' ? ' + (f.to || '?') + '</span>';
                    });
                    if (sections.flights.internal.length > 2) html += ' <small class="text-muted">+ ' + (sections.flights.internal.length - 2) + ' autre(s)</small>';
                    html += '</div>';
                }
                html += '</div>';
            }
            
            if (!hasAnyContent) {
                html += '<div class="day-summary-card mb-2 border rounded p-2 bg-light text-center">';
                html += '<div class="small text-muted mb-2">Aucun élément configuré</div>';
                html += '<button type="button" class="btn btn-sm btn-outline-primary day-summary-config-btn" data-day-index="' + dayIndex + '" data-tab="activities"><i class="bx bx-plus"></i> Configurer</button>';
                html += '</div>';
            }
            
            html += '</div>';
            extrasEl.innerHTML = html;
        }
        window.updateProgrammeDayExtras = updateProgrammeDayExtras;

        document.addEventListener('day-builder:item-count-changed', function(e) {
            var d = e.detail || {};
            if (d.dayIndex != null && window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(d.dayIndex);
        });
        
        // Gestionnaires pour les boutons du résumé du jour
        document.addEventListener('click', function(e) {
            // Bouton "Configurer" : ouvre le drawer sur l'onglet spécifié
            var configBtn = e.target.closest('.day-summary-config-btn');
            if (configBtn) {
                e.preventDefault();
                var dayIndex = configBtn.getAttribute('data-day-index');
                var tab = configBtn.getAttribute('data-tab') || 'activities';
                var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                if (!card) return;
                var dayNumber = parseInt(dayIndex || '0', 10) + 1;
                var dayId = card.getAttribute('data-day-id') || '';
                document.dispatchEvent(new CustomEvent('day-builder:set-day', {
                    detail: {
                        dayIndex: String(dayIndex),
                        dayId: dayId,
                        dayNumber: dayNumber,
                        tab: tab
                    }
                }));
                return;
            }
            
            // Bouton "Retirer" : retire l'?l?ment du jour
            var removeBtn = e.target.closest('.day-summary-remove-btn');
            if (removeBtn) {
                e.preventDefault();
                var dayIndex = removeBtn.getAttribute('data-day-index');
                var type = removeBtn.getAttribute('data-type');
                var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                if (!card) return;
                var dayNumber = parseInt(dayIndex || '0', 10) + 1;
                var confirmMsg = '';
                if (type === 'hotel') {
                    confirmMsg = 'Retirer l\'hôtel du Jour ' + dayNumber + ' ?';
                } else if (type === 'transfers') {
                    confirmMsg = 'Retirer tous les transferts du Jour ' + dayNumber + ' ?';
                } else if (type === 'flights') {
                    confirmMsg = 'Retirer tous les vols du Jour ' + dayNumber + ' ?';
                } else if (type === 'activities') {
                    confirmMsg = 'Retirer les activités optionnelles du Jour ' + dayNumber + ' ?';
                }
                if (!confirm(confirmMsg)) return;
                
                if (window.dayItemsManager) {
                    if (type === 'hotel') {
                        window.dayItemsManager.setHotel(dayIndex, null);
                    } else if (type === 'transfers') {
                        window.dayItemsManager.setTransfers(dayIndex, []);
                    } else if (type === 'flights') {
                        window.dayItemsManager.setFlights(dayIndex, []);
                    } else if (type === 'activities') {
                        var activitiesList = card.querySelector('.programme-activities-list');
                        if (activitiesList) {
                            var rows = Array.from(activitiesList.querySelectorAll('.programme-activity-row'));
                            var removedCount = 0;
                            var mandatoryCount = 0;

                            rows.forEach(function(row) {
                                var mandatoryCheckbox = row.querySelector('input[type="checkbox"][name$="[is_mandatory]"]');
                                var isMandatory = mandatoryCheckbox && mandatoryCheckbox.checked;
                                if (isMandatory) {
                                    mandatoryCount++;
                                    return;
                                }
                                row.remove();
                                removedCount++;
                            });

                            if (removedCount === 0) {
                                alert(mandatoryCount > 0
                                    ? 'Aucune activité supprimable : toutes les activités sont obligatoires.'
                                    : 'Aucune activité à supprimer.');
                                return;
                            }

                            reindexProgrammeActivities(card);
                            updateProgrammeDayInclus(card);

                            if (mandatoryCount > 0) {
                                alert(removedCount + ' activité(s) supprimée(s). ' + mandatoryCount + ' activité(s) obligatoire(s) conservée(s).');
                            }
                        }
                    }
                    window.dayItemsManager.syncToForm(dayIndex);
                    document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', {
                        detail: { dayIndex: dayIndex }
                    }));
                    if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIndex);
                }
                return;
            }
        });
        // Mettre ? jour les extras quand un vol change dans le formulaire principal (onglet Vols)
        document.addEventListener('change', function(e) {
            if (!e.target || !e.target.name) return;
            if (e.target.name.indexOf('flight_options[') === 0 && e.target.name.indexOf('[day_number]') !== -1) {
                var dayNumber = parseInt(e.target.value || '0', 10);
                if (dayNumber >= 1) {
                    var dayIndex = String(dayNumber - 1);
                    if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIndex);
                }
            }
            // Mettre à jour quand un hôtel change dans tour_hotels (onglet Hôtels)
            // Nouveau format : check_in_day / check_out_day
            if (e.target.name && e.target.name.indexOf('tour_hotels[') === 0 && 
                (e.target.name.indexOf('[check_in_day]') !== -1 || e.target.name.indexOf('[check_out_day]') !== -1)) {
                var hotelRow = e.target.closest('.tour-hotel-row');
                if (hotelRow) {
                    var checkInSel = hotelRow.querySelector('select[name^="tour_hotels["][name$="[check_in_day]"]');
                    var checkOutSel = hotelRow.querySelector('select[name^="tour_hotels["][name$="[check_out_day]"]');
                    if (checkInSel && checkOutSel) {
                        var checkIn = parseInt(checkInSel.value || '1', 10);
                        var checkOut = parseInt(checkOutSel.value || '1', 10);
                        var hotelId = hotelRow.getAttribute('data-hotel-id');
                        // Mettre ? jour tous les jours dans la plage check-in -> check-out
                        if (hotelId && window.dayItemsManager) {
                            for (var d = checkIn; d <= checkOut; d++) {
                                var dayIndex = String(d - 1);
                                window.dayItemsManager.setHotel(dayIndex, parseInt(hotelId, 10));
                                window.dayItemsManager.syncToForm(dayIndex);
                                if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIndex);
                            }
                            // Retirer l'hôtel des jours hors de la plage
                            var allDays = document.querySelectorAll('.programme-day-card');
                            allDays.forEach(function(card) {
                                var dayIdx = card.getAttribute('data-day-index');
                                var dayNum = parseInt(dayIdx || '0', 10) + 1;
                                if (dayNum < checkIn || dayNum > checkOut) {
                                    var currentHotelId = window.dayItemsManager.getHotel(dayIdx);
                                    if (currentHotelId == hotelId) {
                                        window.dayItemsManager.setHotel(dayIdx, null);
                                        window.dayItemsManager.syncToForm(dayIdx);
                                        if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIdx);
                                    }
                                }
                            });
                        }
                    }
                }
            }
            // Compatibilit? ancien format : day_number
            if (e.target.name && e.target.name.indexOf('tour_hotels[') === 0 && e.target.name.indexOf('[day_number]') !== -1) {
                var dayNumber = parseInt(e.target.value || '0', 10);
                if (dayNumber >= 1) {
                    var dayIndex = String(dayNumber - 1);
                    if (window.dayItemsManager) {
                        var hotelRow = e.target.closest('.tour-hotel-row');
                        if (hotelRow) {
                            var idx = hotelRow.getAttribute('data-index');
                            var hotelId = hotelRow.getAttribute('data-hotel-id');
                            if (hotelId) {
                                window.dayItemsManager.setHotel(dayIndex, parseInt(hotelId, 10));
                                window.dayItemsManager.syncToForm(dayIndex);
                                if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIndex);
                            }
                        }
                    }
                }
            }
        });

        function appendActivityToDay(dayIndex, activityId, activityTitle) {
            if (dayIndex === null || dayIndex === '' || !activityId) return false;
            var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
            var list = card && card.querySelector('.programme-activities-list');
            if (!list) return false;
            var k = list.querySelectorAll('.programme-activity-row').length;
            var prefix = 'programme_days[' + dayIndex + '][activities][' + k + ']';
            var row = document.createElement('div');
            row.className = 'programme-activity-row card mb-2';
            row.setAttribute('data-day-activity-id', '0');
            row.setAttribute('draggable', 'true');
            row.innerHTML = '<div class="card-body py-2"><div class="d-flex flex-wrap align-items-start gap-2">' +
                '<span class="programme-activity-drag-handle text-muted cursor-grab me-1" title="Réordonner"><i class="bx bx-dots-vertical-rounded"></i></span>' +
                '<input type="hidden" name="' + prefix + '[day_activity_id]" value="">' +
                '<input type="hidden" name="' + prefix + '[activity_id]" value="' + activityId + '">' +
                '<input type="hidden" name="' + prefix + '[sort_order]" value="' + k + '">' +
                '<span class="fw-medium">' + (activityTitle || 'Activité').replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</span>' +
                '<span class="form-check form-check-inline mb-0"><input type="hidden" name="' + prefix + '[is_included]" value="0"><input class="form-check-input" type="checkbox" name="' + prefix + '[is_included]" value="1" checked><label class="form-check-label small">Inclus</label></span>' +
                '<span class="form-check form-check-inline mb-0"><input type="hidden" name="' + prefix + '[is_mandatory]" value="0"><input class="form-check-input" type="checkbox" name="' + prefix + '[is_mandatory]" value="1"><label class="form-check-label small">Obligatoire</label></span>' +
                '<input type="text" class="form-control form-control-sm" style="max-width:200px" name="' + prefix + '[custom_title]" placeholder="Titre">' +
                '<textarea class="form-control form-control-sm programme-plain-editor" name="' + prefix + '[custom_description]" rows="2" placeholder="Description"></textarea>' +
                '<button type="button" class="btn btn-sm btn-outline-danger remove-programme-activity"><i class="bx bx-trash"></i></button></div></div>';
            list.appendChild(row);
            updateProgrammeDayInclus(card);
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', {
                detail: { dayIndex: String(dayIndex), count: list.querySelectorAll('.programme-activity-row').length }
            }));
            return true;
        }

        function reindexProgrammeActivities(card) {
            if (!card) return;
            var list = card.querySelector('.programme-activities-list');
            if (!list) return;
            var rows = list.querySelectorAll('.programme-activity-row');
            rows.forEach(function(row, idx) {
                row.querySelectorAll('[name^="programme_days["]').forEach(function(el) {
                    el.name = el.name.replace(/\]\[activities\]\[\d+\]/, '][activities][' + idx + ']');
                });
                var sortOrderInput = row.querySelector('input[name$="[sort_order]"]');
                if (sortOrderInput) sortOrderInput.value = idx;
            });
        }

        (function dayBuilderDrawerManager() {
            var drawer = document.getElementById('day-builder-root');
            if (!drawer) return;

            var titleEl = document.getElementById('day-builder-drawer-label');
            var summaryEl = document.getElementById('day-builder-day-summary');
            var contextEl = document.getElementById('day-builder-drawer-context');
            var flightsManager = document.getElementById('day-builder-flights-manager');
            var dayTargetSelect = document.getElementById('programme-day-target-select');

            // ===== GESTIONNAIRE D'?TAT UNIFI? POUR VOLS/H?TELS/TRANSFERTS PAR JOUR =====
            window.dayItemsManager = {
                // État interne : {dayIndex: {flights: [], hotel_id: null, transfer_ids: []}}
                state: {},

                // Initialiser depuis le formulaire (programme_days[X][...])
                // Puis charger l'état depuis les inputs hidden (pré-remplis par le serveur pour hotel/transferts)
                init: function() {
                    this.state = {};
                    var cards = document.querySelectorAll('.programme-day-card');
                    cards.forEach(function(card, idx) {
                        var dayId = card.getAttribute('data-day-id');
                        window.dayItemsManager.state[String(idx)] = {
                            dayId: dayId,
                            flights: [],
                            hotel_id: null,
                            transfer_ids: []
                        };
                    });
                    var self = this;
                    cards.forEach(function(card, idx) {
                        self.loadFromForm(String(idx));
                    });
                },

                // Obtenir l'état pour un jour
                getDay: function(dayIndex) {
                    var key = String(dayIndex);
                    if (!this.state[key]) {
                        this.state[key] = { dayId: null, flights: [], hotel_id: null, transfer_ids: [] };
                    }
                    return this.state[key];
                },

                // Défaut les vols pour un jour
                setFlights: function(dayIndex, flightIds) {
                    var day = this.getDay(dayIndex);
                    day.flights = Array.isArray(flightIds) ? flightIds : (flightIds ? [flightIds] : []);
                    this.syncToForm(dayIndex);
                },

                // Obtenir les vols pour un jour
                getFlights: function(dayIndex) {
                    return (this.getDay(dayIndex).flights || []).slice();
                },

                // D?faut l'hôtel pour un jour
                setHotel: function(dayIndex, hotelId) {
                    var day = this.getDay(dayIndex);
                    day.hotel_id = hotelId || null;
                    this.syncToForm(dayIndex);
                },

                // Obtenir l'hôtel pour un jour
                getHotel: function(dayIndex) {
                    return this.getDay(dayIndex).hotel_id;
                },

                // D?faut les transferts pour un jour
                setTransfers: function(dayIndex, transferIds) {
                    var day = this.getDay(dayIndex);
                    day.transfer_ids = Array.isArray(transferIds) ? transferIds : (transferIds ? [transferIds] : []);
                    this.syncToForm(dayIndex);
                },

                // Obtenir les transferts pour un jour
                getTransfers: function(dayIndex) {
                    return (this.getDay(dayIndex).transfer_ids || []).slice();
                },

                // Synchroniser l'état avec le formulaire (écrire dans les inputs hidden)
                syncToForm: function(dayIndex) {
                    var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                    if (!card) return;

                    var day = this.getDay(dayIndex);

                    // Synchroniser vols
                    var flightsInput = card.querySelector('input[name^="programme_days["][name$="[flights]"]');
                    if (flightsInput) {
                        flightsInput.value = day.flights.join(',');
                    }

                    // Synchroniser hôtel
                    var hotelInput = card.querySelector('input[name^="programme_days["][name$="[hotel_id]"]');
                    if (hotelInput) {
                        hotelInput.value = day.hotel_id || '';
                    }

                    // Synchroniser transferts
                    var transferInput = card.querySelector('input[name^="programme_days["][name$="[transfer_ids]"]');
                    if (transferInput) {
                        transferInput.value = day.transfer_ids.join(',');
                    }
                },

                // Charger depuis le formulaire (lire les inputs hidden existants)
                loadFromForm: function(dayIndex) {
                    var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                    if (!card) return;

                    var day = this.getDay(dayIndex);

                    var flightsInput = card.querySelector('input[name^="programme_days["][name$="[flights]"]');
                    if (flightsInput && flightsInput.value) {
                        day.flights = flightsInput.value.split(',').map(function(id) { return parseInt(id.trim(), 10); }).filter(function(id) { return id > 0; });
                    }

                    var hotelInput = card.querySelector('input[name^="programme_days["][name$="[hotel_id]"]');
                    if (hotelInput && hotelInput.value) {
                        day.hotel_id = parseInt(hotelInput.value, 10);
                    }

                    var transferInput = card.querySelector('input[name^="programme_days["][name$="[transfer_ids]"]');
                    if (transferInput && transferInput.value) {
                        day.transfer_ids = transferInput.value.split(',').map(function(id) { return parseInt(id.trim(), 10); }).filter(function(id) { return id > 0; });
                    }
                },

                // Compter tous les items (activités + vols + hôtel + transferts)
                countItems: function(dayIndex) {
                    var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                    var list = card && card.querySelector('.programme-activities-list');
                    var actCount = list ? list.querySelectorAll('.programme-activity-row').length : 0;
                    var day = this.getDay(dayIndex);
                    var otherCount = (day.flights ? day.flights.length : 0) + (day.hotel_id ? 1 : 0) + (day.transfer_ids ? day.transfer_ids.length : 0);
                    return actCount + otherCount;
                }
            };

            // Initialiser le gestionnaire au chargement
            window.dayItemsManager.init();
            // Charger les données depuis le formulaire et afficher Hôtel / Transferts / Vols dans chaque carte de jour
            var cards = document.querySelectorAll('.programme-day-card');
            cards.forEach(function(card) {
                var dayIndex = card.getAttribute('data-day-index');
                if (dayIndex != null) {
                    window.dayItemsManager.loadFromForm(dayIndex);
                    if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIndex);
                }
            });

            document.addEventListener('day-builder:item-count-changed', function(e) {
                var detail = (e && e.detail) ? e.detail : {};
                var activeDayIndex = drawer.getAttribute('data-day-index');
                if (String(detail.dayIndex) !== String(activeDayIndex)) return;
                var dayNumber = parseInt(drawer.getAttribute('data-day-number') || '1', 10) || 1;
                updateDrawerSummary(dayNumber, activeDayIndex);
            });

            function getDayItemsCount(dayIndex) {
                return window.dayItemsManager.countItems(dayIndex);
            }

            function updateDrawerSummary(dayNum, dayIndex) {
                if (!summaryEl) return;
                var count = getDayItemsCount(dayIndex);
                summaryEl.textContent = 'Jour ' + dayNum + ' — ' + count + ' élément' + (count > 1 ? 's' : '');
            }

            function setDrawerContext(dayIndex, dayId, dayNumber) {
                var dayNum = parseInt(dayNumber || '0', 10);
                if (!dayNum || dayNum < 1) {
                    var parsedIndex = parseInt(dayIndex || '0', 10);
                    dayNum = isNaN(parsedIndex) ? 1 : (parsedIndex + 1);
                }

                drawer.setAttribute('data-day-index', dayIndex || '');
                drawer.setAttribute('data-day-id', dayId || '');
                drawer.setAttribute('data-day-number', String(dayNum));

                if (titleEl) titleEl.textContent = 'Jour ' + dayNum + ' — configuration';
                updateDrawerSummary(dayNum, dayIndex || String(dayNum - 1));
                if (contextEl) contextEl.textContent = 'Gérez les activités, l’hôtel et les transferts pour le jour ' + dayNum + '.';

                if (flightsManager) {
                    var manager = flightsManager.querySelector('.flight-manager');
                    if (manager) manager.setAttribute('data-day-number', String(dayNum));
                }

                document.dispatchEvent(new CustomEvent('day-builder:context-changed', {
                    detail: {
                        dayIndex: dayIndex || '',
                        dayId: dayId || '',
                        dayNumber: dayNum
                    }
                }));
            }

            function openForButton(btn, forcedTab) {
                if (!btn) return;
                setDrawerContext(
                    btn.getAttribute('data-day-index') || '',
                    btn.getAttribute('data-day-id') || '',
                    btn.getAttribute('data-day-number') || ''
                );

                drawer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                var tab = forcedTab || 'activities';
                var tabButton = drawer.querySelector('[data-bs-target="#day-builder-tab-' + tab + '"]');
                if (tabButton && window.bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(tabButton).show();
                }
            }

            function syncProgrammeDayTargetOptions() {
                if (!dayTargetSelect) return;
                var cards = document.querySelectorAll('.programme-day-card');
                var prev = dayTargetSelect.value;
                dayTargetSelect.innerHTML = '';
                cards.forEach(function(card, idx) {
                    var opt = document.createElement('option');
                    opt.value = String(idx);
                    var titleInp = card.querySelector('input[name$="[day_title]"]');
                    var t = titleInp && titleInp.value.trim() ? titleInp.value.trim() : ('Jour ' + (idx + 1));
                    opt.textContent = 'Jour ' + (idx + 1) + ' — ' + t;
                    dayTargetSelect.appendChild(opt);
                });
                if (prev && dayTargetSelect.querySelector('option[value="' + prev + '"]')) {
                    dayTargetSelect.value = prev;
                } else if (dayTargetSelect.options.length) {
                    dayTargetSelect.selectedIndex = 0;
                }
                var sel = dayTargetSelect.value;
                if (sel !== '') {
                    var c = document.querySelector('.programme-day-card[data-day-index="' + sel + '"]');
                    if (c) {
                        setDrawerContext(sel, c.getAttribute('data-day-id') || '', String(parseInt(sel, 10) + 1));
                    }
                }
            }

            if (dayTargetSelect) {
                dayTargetSelect.addEventListener('change', function() {
                    var idx = dayTargetSelect.value;
                    var c = document.querySelector('.programme-day-card[data-day-index="' + idx + '"]');
                    if (!c) return;
                    setDrawerContext(idx, c.getAttribute('data-day-id') || '', String(parseInt(idx, 10) + 1));
                });
            }

            window.syncProgrammeDayTargetOptions = syncProgrammeDayTargetOptions;

            document.addEventListener('click', function(e) {
                var addBtn = e.target.closest('.day-builder-add-activity');
                if (!addBtn) return;

                e.preventDefault();
                var dayIndex = drawer.getAttribute('data-day-index');
                var activityId = addBtn.getAttribute('data-activity-id');
                var activityTitle = addBtn.getAttribute('data-activity-title') || 'Activité';
                if (!appendActivityToDay(dayIndex, activityId, activityTitle)) return;
                if (window.autosaveProgram) window.autosaveProgram();
            });

            document.addEventListener('day-builder:set-day', function(e) {
                var detail = (e && e.detail) ? e.detail : {};
                var dayIndex = detail.dayIndex != null ? String(detail.dayIndex) : '';
                if (dayIndex === '') {
                    var dayNumber = parseInt(detail.dayNumber || '0', 10);
                    if (dayNumber >= 1) dayIndex = String(dayNumber - 1);
                }
                var targetCard = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                if (!targetCard) return;
                if (dayTargetSelect) dayTargetSelect.value = dayIndex;
                setDrawerContext(
                    dayIndex,
                    detail.dayId || targetCard.getAttribute('data-day-id') || '',
                    detail.dayNumber || String(parseInt(dayIndex, 10) + 1)
                );
                drawer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                var tab = detail.tab || 'activities';
                var tabButton = drawer.querySelector('[data-bs-target="#day-builder-tab-' + tab + '"]');
                if (tabButton && window.bootstrap && bootstrap.Tab) {
                    setTimeout(function() {
                        bootstrap.Tab.getOrCreateInstance(tabButton).show();
                    }, 100);
                }
            });

            syncProgrammeDayTargetOptions();
        })();

        (function programmeActivityDragDrop() {
            var draggedActivityRow = null;
            document.addEventListener('dragstart', function(e) {
                var row = e.target.closest('.programme-activity-row');
                if (!row || e.target.closest('.remove-programme-activity')) return;
                draggedActivityRow = row;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', '');
                row.classList.add('opacity-50');
            });
            document.addEventListener('dragend', function(e) {
                if (draggedActivityRow) draggedActivityRow.classList.remove('opacity-50');
                draggedActivityRow = null;
            });
            document.addEventListener('dragover', function(e) {
                var list = e.target.closest('.programme-activities-list');
                if (list && draggedActivityRow && list.contains(draggedActivityRow)) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                }
            });
            document.addEventListener('drop', function(e) {
                var list = e.target.closest('.programme-activities-list');
                if (!list || !draggedActivityRow || !list.contains(draggedActivityRow)) return;
                e.preventDefault();
                var targetRow = e.target.closest('.programme-activity-row');
                if (targetRow === draggedActivityRow) { draggedActivityRow = null; return; }
                var card = list.closest('.programme-day-card');
                if (targetRow) {
                    if (targetRow.compareDocumentPosition(draggedActivityRow) === 4) list.insertBefore(draggedActivityRow, targetRow);
                    else list.insertBefore(draggedActivityRow, targetRow.nextSibling);
                } else {
                    list.appendChild(draggedActivityRow);
                }
                var rows = list.querySelectorAll('.programme-activity-row');
                rows.forEach(function(r, i) {
                    r.querySelectorAll('[name^="programme_days["]').forEach(function(el) {
                        el.name = el.name.replace(/\]\[activities\]\[\d+\]/, '][activities][' + i + ']');
                    });
                });
                if (window.autosaveProgram) window.autosaveProgram();
                draggedActivityRow = null;
            });
        })();

        // Programme (Jours): Add activity to day (ddélégation pour les jours ajoutés dynamiquement)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.add-activity-to-day')) {
                var btn = e.target.closest('.add-activity-to-day');
                var dayIndex = btn.getAttribute('data-day-index');
                var card = btn.closest('.programme-day-card') || btn.closest('.accordion-item');
                var select = card ? card.querySelector('.add-activity-select') : null;
                var activityId = select && select.value;
                var activityTitle = select && select.options[select.selectedIndex] && select.options[select.selectedIndex].text;
                if (!activityId || dayIndex === null) return;
                if (!appendActivityToDay(dayIndex, activityId, activityTitle)) return;
                select.value = '';
                if (window.autosaveProgram) window.autosaveProgram();
            }
            if (e.target.closest('.remove-programme-activity')) {
                var row = e.target.closest('.programme-activity-row');
                if (row && confirm('Retirer cette activité du jour ?')) {
                    var card = row.closest('.programme-day-card');
                    var dayIndex = card ? card.getAttribute('data-day-index') : null;
                    row.remove();
                    reindexProgrammeActivities(card);
                    updateProgrammeDayInclus(card);
                    if (dayIndex !== null) {
                        var list = card ? card.querySelector('.programme-activities-list') : null;
                        document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', {
                            detail: { dayIndex: String(dayIndex), count: list ? list.querySelectorAll('.programme-activity-row').length : 0 }
                        }));
                    }
                    if (window.autosaveProgram) window.autosaveProgram();
                }
            }
        });

        // "?"?"? Onglet Vols : boutons Ajouter / Modifier / Enregistrer / Annuler / REMOVE "?"?"?
        (function flightOptionsHandlers() {
            var templatesEl = document.getElementById('flight-opt-templates');
            var nextIndexEl = document.getElementById('flight-opt-next-index');
            var dash = '"?';

            function getNextIndex() {
                if (!nextIndexEl) return 0;
                var n = parseInt(nextIndexEl.value, 10) || 0;
                nextIndexEl.value = n + 1;
                return n;
            }

            document.addEventListener('click', function(e) {
                // Ajouter un vol (Aller / Retour / segment)
                if (e.target.closest('.btn-add-flight-opt')) {
                    var btn = e.target.closest('.btn-add-flight-opt');
                    var type = btn.getAttribute('data-type');
                    var lastDay = btn.getAttribute('data-day') || '1';
                    if (!templatesEl || !type) return;
                    var tpl = templatesEl.querySelector('[data-flight-tpl="' + type + '"]');
                    if (!tpl) return;
                    var card = tpl.querySelector('.flight-opt-card');
                    if (!card) return;
                    var idx = getNextIndex();
                    var clone = card.cloneNode(true);
                    clone.setAttribute('data-flight-opt-index', idx);
                    clone.querySelectorAll('[name^="flight_options["]').forEach(function(el) {
                        el.name = el.name.replace(/flight_options\[-1\]/, 'flight_options[' + idx + ']');
                        el.removeAttribute('disabled');
                    });
                    clone.querySelectorAll('.flight-opt-view .flight-opt-route, .flight-opt-dep-date, .flight-opt-arr-date, .flight-opt-dep-time, .flight-opt-arr-time, .flight-opt-from, .flight-opt-to, .flight-opt-cabin-bag, .flight-opt-checkin-bag').forEach(function(span) {
                        if (span && span.textContent !== undefined) span.textContent = dash;
                    });
                    var editPanel = clone.querySelector('.flight-opt-edit');
                    var viewPanel = clone.querySelector('.flight-opt-view');
                    if (editPanel) editPanel.style.display = 'none';
                    if (viewPanel) viewPanel.style.display = '';
                    var badgeWrap = clone.querySelector('.flight-opt-badge');
                    if (badgeWrap) badgeWrap.style.display = 'none';
                    var container = document.querySelector('.flight-opt-cards-' + type);
                    if (container) container.appendChild(clone);
                    return;
                }

                // Supprimer un vol (REMOVE)
                if (e.target.closest('.flight-opt-remove')) {
                    var card = e.target.closest('.flight-opt-card');
                    if (card && confirm('Supprimer ce vol ?')) card.remove();
                    return;
                }

                // Modifier
                if (e.target.closest('.flight-opt-edit-btn')) {
                    var card = e.target.closest('.flight-opt-card');
                    if (!card) return;
                    var view = card.querySelector('.flight-opt-view');
                    var edit = card.querySelector('.flight-opt-edit');
                    if (view) view.style.display = 'none';
                    if (edit) edit.style.display = 'block';
                    return;
                }

                // Enregistrer : met à jour l'affichage local uniquement.
                // La persistance serveur se fait une seule fois via le bouton principal "Enregistrer".
                if (e.target.closest('.flight-opt-save-btn')) {
                    var card = e.target.closest('.flight-opt-card');
                    if (!card) return;
                    var edit = card.querySelector('.flight-opt-edit');
                    var view = card.querySelector('.flight-opt-view');
                    var fromCity = edit && edit.querySelector('input[name*="[from_city]"]');
                    var toCity = edit && edit.querySelector('input[name*="[to_city]"]');
                    var depDate = edit && edit.querySelector('input[name*="[departure_date]"]');
                    var depTime = edit && edit.querySelector('input[name*="[departure_time]"]');
                    var arrTime = edit && edit.querySelector('input[name*="[arrival_time]"]');
                    var cabinKg = edit && edit.querySelector('input[name*="[baggage_cabin_kg]"]');
                    var checkinKg = edit && edit.querySelector('input[name*="[baggage_checkin_kg]"]');
                    var tentativeCb = edit && edit.querySelector('input[name*="[is_tentative]"]');
                    var route = view && view.querySelector('.flight-opt-route');
                    var depDateEl = view && view.querySelector('.flight-opt-dep-date');
                    var arrDateEl = view && view.querySelector('.flight-opt-arr-date');
                    var depTimeEl = view && view.querySelector('.flight-opt-dep-time');
                    var arrTimeEl = view && view.querySelector('.flight-opt-arr-time');
                    var fromEl = view && view.querySelector('.flight-opt-from');
                    var toEl = view && view.querySelector('.flight-opt-to');
                    var cabinBagEl = view && view.querySelector('.flight-opt-cabin-bag');
                    var checkinBagEl = view && view.querySelector('.flight-opt-checkin-bag');
                    var badgeWrap = view && view.querySelector('.flight-opt-badge');
                    if (route) route.textContent = (fromCity && fromCity.value ? fromCity.value : dash) + ' ? ' + (toCity && toCity.value ? toCity.value : dash);
                    var d = depDate && depDate.value ? depDate.value : dash;
                    if (depDateEl) depDateEl.textContent = d;
                    if (arrDateEl) arrDateEl.textContent = d;
                    if (depTimeEl) depTimeEl.textContent = (depTime && depTime.value) ? depTime.value : dash;
                    if (arrTimeEl) arrTimeEl.textContent = (arrTime && arrTime.value) ? arrTime.value : dash;
                    if (fromEl) fromEl.textContent = fromCity && fromCity.value ? fromCity.value : dash;
                    if (toEl) toEl.textContent = toCity && toCity.value ? toCity.value : dash;
                    if (cabinBagEl) cabinBagEl.textContent = cabinKg && cabinKg.value ? cabinKg.value + ' kg' : dash;
                    if (checkinBagEl) checkinBagEl.textContent = checkinKg && checkinKg.value ? checkinKg.value + ' kg' : dash;
                    if (badgeWrap) badgeWrap.style.display = (tentativeCb && tentativeCb.checked) ? '' : 'none';
                    if (view) view.style.display = '';
                    if (edit) edit.style.display = 'none';
                    return;
                }

                // Annuler
                if (e.target.closest('.flight-opt-cancel-btn')) {
                    var card = e.target.closest('.flight-opt-card');
                    if (!card) return;
                    var view = card.querySelector('.flight-opt-view');
                    var edit = card.querySelector('.flight-opt-edit');
                    if (view) view.style.display = '';
                    if (edit) edit.style.display = 'none';
                }
            });
        })();

        // "?"?"? Secours : bouton « Enregistrer toutes les modifications » (soumission forcée si le clic est intercept?) "?"?"?
        (function() {
            function initSaveButtonFallback() {
                var btn = document.getElementById('edit-voyage-submit-btn');
                var form = document.getElementById('edit-voyage-form');
                if (!btn || !form) return;

                // Anti double-submit: une seule requête active à la fois.
                if (form.dataset.singleSubmitBound !== '1') {
                    form.dataset.singleSubmitBound = '1';
                    form.addEventListener('submit', function(e) {
                        if (form.dataset.isSubmitting === '1') {
                            e.preventDefault();
                            return;
                        }

                        form.dataset.isSubmitting = '1';
                        if (btn) {
                            btn.setAttribute('disabled', 'disabled');
                            btn.classList.add('disabled');
                        }
                    }, true);
                }

                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    if (form.dataset.isSubmitting === '1') {
                        return;
                    }

                    var acc = document.getElementById('accordionProgrammeDays');
                    var durationInput = document.getElementById('duration_day');
                    if (acc && durationInput) {
                        var n = acc.querySelectorAll('.programme-day-card').length;
                        durationInput.value = n > 0 ? n : (durationInput.value || 1);
                    }
                    // Sécurité: tous les champs Hôtels (hôtel + chambres) doivent être soumis.
                    form.querySelectorAll('[name^="tour_hotels["]').forEach(function(el) {
                        if (el && el.hasAttribute && el.hasAttribute('disabled')) {
                            el.removeAttribute('disabled');
                        }
                    });
                    // requestSubmit() d?clenche la validation HTML5 (required, etc.) avant envoi
                    if (window.syncProgrammeDaysPayload) {
                        window.syncProgrammeDaysPayload(true);
                    }
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }, true);
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSaveButtonFallback);
            } else {
                initSaveButtonFallback();
            }
        })();

        (function inlineActivitiesManager() {
            var rowsContainer = document.getElementById('voyage-activities-rows');
            if (!rowsContainer) return;

            var emptyState = document.getElementById('voyage-activities-empty-state');
            var modalEl = document.getElementById('activitiesCatalogModal');
            var searchInput = document.getElementById('activities-catalog-search');
            var catalogBody = document.getElementById('activities-catalog-body');
            var prevBtn = document.getElementById('activities-catalog-prev');
            var nextBtn = document.getElementById('activities-catalog-next');
            var countLabel = document.getElementById('activities-catalog-count');
            var regionHint = document.getElementById('activities-catalog-region-hint');
            var boot = window.VOYAGE_EDIT_BOOTSTRAP || {};
            var ajaxListUrl = boot.ajaxListActivityUrl || '';

            var filteredCatalog = [];
            var page = 1;
            var pageSize = 8;
            var catalogLoading = false;
            var catalogLoadedFromServer = false;

            function fullCatalog() {
                return Array.isArray(window.ALL_TOUR_ACTIVITIES_CATALOG) ? window.ALL_TOUR_ACTIVITIES_CATALOG : [];
            }

            function getCatalog() {
                var all = fullCatalog();
                if (window.AjinsafroActivityRegionFilter && typeof window.AjinsafroActivityRegionFilter.getFilteredCatalog === 'function') {
                    var filtered = window.AjinsafroActivityRegionFilter.getFilteredCatalog('tour');
                    if (Array.isArray(filtered) && filtered.length > 0) {
                        return filtered;
                    }
                }

                if (Array.isArray(window.TOUR_ACTIVITIES_CATALOG) && window.TOUR_ACTIVITIES_CATALOG.length > 0) {
                    return window.TOUR_ACTIVITIES_CATALOG;
                }

                return all;
            }

            function esc(str) {
                return String(str || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function toNumber(value, fallback) {
                var num = parseFloat(value);
                return Number.isFinite(num) ? num : fallback;
            }

            function toInt(value, fallback) {
                var num = parseInt(value, 10);
                return Number.isFinite(num) ? num : fallback;
            }

            function normalizeActivity(raw) {
                raw = raw || {};
                return {
                    id: toInt(raw.id, 0),
                    title: raw.title || '',
                    description: raw.description || '',
                    activity_type: raw.activity_type || '',
                    region_name: raw.region_name || raw.location_text || raw.place_text || '',
                    location_text: raw.location_text || raw.region_name || raw.place_text || '',
                    place_text: raw.place_text || raw.region_name || raw.location_text || '',
                    adult_price: toNumber(raw.adult_price || raw.base_price || raw.price, 0),
                    child_price: toNumber(raw.child_price, 0),
                    base_price: toNumber(raw.base_price || raw.adult_price || raw.price, 0),
                    default_duration_minutes: toInt(raw.default_duration_minutes || raw.duration_minutes, 0),
                    min_age: toInt(raw.min_age, 0),
                    max_age: toInt(raw.max_age, 0),
                };
            }

            function currentRegionTerms() {
                if (window.AjinsafroActivityRegionFilter && typeof window.AjinsafroActivityRegionFilter.currentTerms === 'function') {
                    return window.AjinsafroActivityRegionFilter.currentTerms();
                }

                var addressInput = document.getElementById('address');
                return addressInput && addressInput.value.trim() ? [addressInput.value.trim()] : [];
            }

            function syncGlobalCatalog(items) {
                var normalized = Array.isArray(items)
                    ? items.map(normalizeActivity).filter(function(item) { return item.id > 0; })
                    : [];

                ['ALL_TOUR_ACTIVITIES_CATALOG', 'TOUR_ACTIVITIES_CATALOG'].forEach(function(key) {
                    if (!Array.isArray(window[key])) {
                        window[key] = [];
                    }

                    normalized.forEach(function(activity) {
                        var idx = window[key].findIndex(function(item) {
                            return Number(item.id) === Number(activity.id);
                        });
                        if (idx >= 0) {
                            window[key][idx] = Object.assign({}, window[key][idx], activity);
                        } else {
                            window[key].push(activity);
                        }
                    });
                });
            }

            async function loadCatalogFromServer(force) {
                if (!ajaxListUrl || catalogLoading || (catalogLoadedFromServer && !force)) {
                    return;
                }

                catalogLoading = true;
                if (catalogBody && !fullCatalog().length) {
                    catalogBody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">Chargement du catalogue...</td></tr>';
                }

                try {
                    var url = new URL(ajaxListUrl, window.location.origin);
                    currentRegionTerms().forEach(function(term) {
                        url.searchParams.append('regions[]', term);
                    });

                    var response = await fetch(url.toString(), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    var json = await response.json().catch(function() { return null; });
                    if (!response.ok || !json || json.success === false || !Array.isArray(json.data)) {
                        throw new Error((json && json.message) || 'Impossible de charger le catalogue.');
                    }

                    syncGlobalCatalog(json.data);
                    catalogLoadedFromServer = true;
                } catch (error) {
                    if (catalogBody && !fullCatalog().length) {
                        catalogBody.innerHTML = '<tr><td colspan="4" class="text-danger text-center">' + esc(error.message || 'Catalogue indisponible.') + '</td></tr>';
                    }
                } finally {
                    catalogLoading = false;
                }
            }

            function notifyActivitiesChanged() {
                try {
                    rowsContainer.dispatchEvent(new Event('change', { bubbles: true }));
                } catch (e) {
                    // ignore
                }
            }

            function updateEmptyState() {
                if (!emptyState) return;
                emptyState.style.display = rowsContainer.querySelectorAll('.voyage-activity-row').length ? 'none' : '';
            }

            function computeLineTotal(row) {
                var pricing = row.querySelector('.voyage-activity-pricing');
                var statusField = row.querySelector('[data-field="status"]');
                var includedHidden = row.querySelector('[data-field="included"]');
                var priceInput = row.querySelector('.voyage-activity-price');
                var childInput = row.querySelector('.voyage-activity-child-price');
                var lineTotal = row.querySelector('.voyage-activity-line-total');
                if (!pricing || !priceInput || !lineTotal) return;

                var unitPrice = Math.max(0, toNumber(priceInput.value, 0));
                var childPrice = childInput ? Math.max(0, toNumber(childInput.value, 0)) : 0;
                var pricingType = pricing.value === 'fixed' ? 'fixed' : 'per_person';
                var status = statusField ? String(statusField.value || 'included') : 'included';
                if (includedHidden) {
                    includedHidden.value = status === 'included' ? '1' : '0';
                }
                row.setAttribute('data-status', status);
                row.setAttribute('data-included', status === 'included' ? '1' : '0');

                if (childInput) {
                    if (pricingType === 'fixed') {
                        childInput.setAttribute('disabled', 'disabled');
                        childInput.classList.add('bg-light');
                    } else {
                        childInput.removeAttribute('disabled');
                        childInput.classList.remove('bg-light');
                    }
                }

                if (status === 'proposition') {
                    lineTotal.textContent = 'Proposition';
                    return;
                }
                if (status === 'optional') {
                    lineTotal.textContent = 'Option client';
                    return;
                }

                var total = pricingType === 'fixed' ? unitPrice : (unitPrice + childPrice);
                lineTotal.textContent = total.toFixed(2);
            }

            function reindexRows() {
                rowsContainer.querySelectorAll('.voyage-activity-row').forEach(function(row, index) {
                    var orderBadge = row.querySelector('.voyage-activity-order');
                    if (orderBadge) {
                        orderBadge.textContent = String(index + 1);
                    }
                    row.querySelectorAll('[data-field]').forEach(function(input) {
                        var field = input.getAttribute('data-field');
                        if (field) {
                            input.name = 'tour_activities[' + index + '][' + field + ']';
                            if (field === 'sort_order') {
                                input.value = String(index);
                            }
                        }
                    });
                    computeLineTotal(row);
                });
                updateEmptyState();
            }

            function focusActivityRow(row) {
                if (!row) return;

                row.classList.add('table-success');
                row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

                window.setTimeout(function() {
                    row.classList.remove('table-success');
                }, 1800);
            }

            function getDayOptions() {
                var fromDataset = [];
                var raw = rowsContainer.getAttribute('data-day-options');
                if (raw) {
                    try {
                        var parsed = JSON.parse(raw);
                        if (Array.isArray(parsed)) {
                            parsed.forEach(function (entry) {
                                var number = toInt(entry && entry.number, 0);
                                if (number <= 0) return;
                                var label = String((entry && entry.label) || ('Jour ' + number));
                                fromDataset.push({ value: String(number), label: label });
                            });
                        }
                    } catch (e) {
                        // ignore invalid JSON
                    }
                }

                if (fromDataset.length) {
                    return fromDataset;
                }

                var fromDom = [];
                rowsContainer.querySelectorAll('.voyage-activity-day-select option').forEach(function(opt) {
                    var value = String(opt.value || '');
                    if (!value) return;
                    fromDom.push({ value: value, label: String(opt.textContent || ('Jour ' + value)) });
                });

                if (fromDom.length) {
                    return fromDom;
                }

                return [{ value: '1', label: 'Jour 1' }];
            }

            function createGroupUuid() {
                return 'grp_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
            }

            function buildMultiDayCheckboxes(dayOptions, selectedDays) {
                return dayOptions.map(function(dayOpt) {
                    var checked = selectedDays.indexOf(String(dayOpt.value)) !== -1 ? ' checked' : '';
                    return '<label class="form-check form-check-inline mb-0 small">'
                        + '<input type="checkbox" class="form-check-input voyage-activity-day-checkbox" value="' + esc(dayOpt.value) + '"' + checked + '>'
                        + '<span class="form-check-label">J' + esc(dayOpt.value) + '</span>'
                        + '</label>';
                }).join('');
            }

            function buildRow(activity) {
                activity = normalizeActivity(activity);
                var title = esc(activity.title || ('Activite #' + activity.id));
                var type = esc(activity.activity_type || '');
                var description = esc(activity.description || '');
                var defaultPrice = toNumber(activity.adult_price || activity.base_price, 0).toFixed(2);
                var defaultChild = toNumber(activity.child_price, 0).toFixed(2);
                var pricingOpts = Array.isArray(window.VOYAGE_ACTIVITY_PRICING_TYPES) ? window.VOYAGE_ACTIVITY_PRICING_TYPES : [];
                var pricingSelect = '';
                if (pricingOpts.length) {
                    pricingOpts.forEach(function (pt, i) {
                        pricingSelect += '<option value="' + esc(pt.value) + '"' + (i === 0 ? ' selected' : '') + '>' + esc(pt.label) + '</option>';
                    });
                } else {
                    pricingSelect = '<option value="per_person" selected>Par personne</option><option value="fixed">Fixe</option>';
                }

                var dayOptions = getDayOptions();
                var firstDayValue = String((dayOptions[0] && dayOptions[0].value) || '1');
                var selectedDays = [firstDayValue];
                var groupUuid = createGroupUuid();

                var tr = document.createElement('tr');
                tr.className = 'voyage-activity-row';
                tr.setAttribute('data-activity-id', activity.id);
                tr.setAttribute('data-included', '1');
                tr.innerHTML =
                    '<td class="text-center">' +
                        '<span class="badge rounded-pill bg-light text-dark voyage-activity-order">1</span>' +
                        '<input type="hidden" data-field="sort_order" value="0">' +
                    '</td>' +
                    '<td>' +
                        '<input type="text" class="form-control form-control-sm fw-medium mb-2" data-field="activity_title" value="' + title + '" placeholder="Nom complet activite">' +
                        '<input type="text" class="form-control form-control-sm" data-field="activity_type" value="' + type + '" placeholder="Type activite">' +
                        '<input type="hidden" data-field="id" value="">' +
                        '<input type="hidden" data-field="activity_id" value="' + activity.id + '">' +
                        '<input type="hidden" data-field="group_uuid" value="' + esc(groupUuid) + '">' +
                    '</td>' +
                    '<td>' +
                        '<select class="form-select form-select-sm voyage-activity-visibility-mode" data-field="visibility_mode">' +
                            '<option value="single_day" selected>Jour unique</option>' +
                            '<option value="multiple_days">Plusieurs jours</option>' +
                            '<option value="all_days">Tous les jours</option>' +
                        '</select>' +
                        '<div class="mt-2 voyage-activity-single-day-wrap">' +
                            '<select class="form-select form-select-sm voyage-activity-day-select">'
                            + dayOptions.map(function(dayOpt, i) {
                                return '<option value="' + esc(dayOpt.value) + '"' + (i === 0 ? ' selected' : '') + '>' + esc(dayOpt.label) + '</option>';
                            }).join('')
                            + '</select>' +
                        '</div>' +
                        '<div class="mt-2 voyage-activity-multi-days-wrap d-none">' +
                            '<div class="d-flex flex-wrap gap-2">' + buildMultiDayCheckboxes(dayOptions, selectedDays) + '</div>' +
                        '</div>' +
                        '<input type="hidden" data-field="day_number" value="' + esc(firstDayValue) + '">' +
                        '<input type="hidden" data-field="days" value="' + esc(selectedDays.join(',')) + '">' +
                        '<input type="hidden" data-field="day_scope" value="fixed">' +
                        '<div class="small text-muted mt-1 voyage-activity-scope-text"></div>' +
                    '</td>' +
                    '<td>' +
                        '<select class="form-select form-select-sm voyage-activity-included" data-field="status">' +
                            '<option value="included" selected>Inclus</option>' +
                            '<option value="optional">Option client</option>' +
                            '<option value="proposition">Proposition</option>' +
                        '</select>' +
                        '<input type="hidden" data-field="included" value="1">' +
                        '<div class="small text-muted mt-1 voyage-activity-state-text">Activite incluse dans le programme.</div>' +
                    '</td>' +
                    '<td>' +
                        '<input type="text" class="form-control form-control-sm voyage-activity-title" data-field="title" value="' + title + '" placeholder="Titre affiche dans le voyage">' +
                    '</td>' +
                    '<td>' +
                        '<textarea class="form-control form-control-sm voyage-activity-description" data-field="description" rows="2" placeholder="-">' + description + '</textarea>' +
                    '</td>' +
                    '<td>' +
                        '<select class="form-select form-select-sm voyage-activity-pricing" data-field="pricing_type">' +
                            pricingSelect +
                        '</select>' +
                    '</td>' +
                    '<td><input type="number" class="form-control form-control-sm voyage-activity-price" data-field="unit_price" min="0" step="0.01" value="' + defaultPrice + '"></td>' +
                    '<td><input type="number" class="form-control form-control-sm voyage-activity-child-price" data-field="child_price" min="0" step="0.01" value="' + defaultChild + '"></td>' +
                    '<td><span class="voyage-activity-line-total fw-semibold">0.00</span></td>' +
                    '<td>' +
                        '<div class="d-flex gap-1">' +
                            '<button type="button" class="btn btn-sm btn-outline-secondary voyage-activity-duplicate" title="Dupliquer"><i class="bx bx-copy"></i></button>' +
                            '<button type="button" class="btn btn-sm btn-outline-primary voyage-activity-edit" title="Modifier activité"><i class="bx bx-pencil"></i></button>' +
                            '<button type="button" class="btn btn-sm btn-outline-info voyage-activity-image" title="Image"><i class="bx bx-image"></i></button>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger voyage-activity-remove"><i class="bx bx-trash"></i></button>' +
                        '</div>' +
                    '</td>';

                return tr;
            }

            function appendActivityToVoyage(activity) {
                if (!activity || !activity.id) {
                    return false;
                }

                var row = buildRow(activity);
                var emptyRow = rowsContainer.querySelector('.voyage-activities-empty-row');
                if (emptyRow) emptyRow.remove();
                rowsContainer.appendChild(row);
                try {
                    row.dispatchEvent(new Event('change', { bubbles: true }));
                } catch (e) {
                    // ignore
                }
                reindexRows();
                updateEmptyState();
                refreshCatalog();
                focusActivityRow(row);
                notifyActivitiesChanged();

                return true;
            }

            function refreshCatalog() {
                if (!catalogBody) return;

                var term = (searchInput && searchInput.value ? searchInput.value : '').toLowerCase().trim();
                filteredCatalog = getCatalog().filter(function(item) {
                    if (!term) {
                        return true;
                    }

                    return [
                        item.title,
                        item.activity_type,
                        item.region_name,
                        item.location_text,
                    ].some(function(value) {
                        return String(value || '').toLowerCase().indexOf(term) !== -1;
                    });
                });

                var total = filteredCatalog.length;
                var totalPages = Math.max(1, Math.ceil(total / pageSize));
                if (page > totalPages) page = totalPages;
                if (page < 1) page = 1;

                var start = (page - 1) * pageSize;
                var current = filteredCatalog.slice(start, start + pageSize);

                if (countLabel) {
                    countLabel.textContent = total + ' résultat' + (total > 1 ? 's' : '') + ' · Page ' + page + '/' + totalPages;
                }

                if (prevBtn) prevBtn.disabled = page <= 1;
                if (nextBtn) nextBtn.disabled = page >= totalPages;

                if (!current.length) {
                    catalogBody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">Aucune activité trouvée.</td></tr>';
                    return;
                }

                catalogBody.innerHTML = current.map(function(item) {
                    var highlightClass = Number(item.id) === Number(window.__voyageActivitiesLastCreatedId || 0) ? ' class="table-success"' : '';
                    return '<tr' + highlightClass + '>' +
                        '<td>' + item.id + '</td>' +
                        '<td>' + esc(item.title) + '</td>' +
                        '<td><div class="fw-medium">' + esc(item.activity_type || 'Non renseigné') + '</div><div class="small text-muted">' + esc(item.region_name || item.location_text || 'Non renseignée') + '</div></td>' +
                        '<td><button type="button" class="btn btn-sm btn-success add-catalog-activity" data-activity-id="' + item.id + '">Ajouter</button></td>' +
                    '</tr>';
                }).join('');
            }

            rowsContainer.addEventListener('click', function(e) {
                var duplicateBtn = e.target.closest('.voyage-activity-duplicate');
                if (duplicateBtn) {
                    var sourceRow = duplicateBtn.closest('.voyage-activity-row');
                    if (!sourceRow) {
                        return;
                    }
                    var clonedRow = sourceRow.cloneNode(true);
                    var idField = clonedRow.querySelector('[data-field="id"]');
                    if (idField) {
                        idField.value = '';
                    }
                    var groupUuidField = clonedRow.querySelector('[data-field="group_uuid"]');
                    if (groupUuidField) {
                        groupUuidField.value = createGroupUuid();
                    }
                    sourceRow.insertAdjacentElement('afterend', clonedRow);
                    reindexRows();
                    notifyActivitiesChanged();
                    focusActivityRow(clonedRow);
                    return;
                }

                var removeBtn = e.target.closest('.voyage-activity-remove');
                if (removeBtn) {
                    var row = removeBtn.closest('.voyage-activity-row');
                    if (row && confirm('Supprimer cette activité du voyage ?')) {
                        row.remove();
                        reindexRows();
                        refreshCatalog();
                        notifyActivitiesChanged();
                    }
                    return;
                }

                var editBtn = e.target.closest('.voyage-activity-edit');
                if (editBtn) {
                    var rowEdit = editBtn.closest('.voyage-activity-row');
                    var activityId = rowEdit ? toInt(rowEdit.getAttribute('data-activity-id') || (rowEdit.querySelector('[data-field="activity_id"]') && rowEdit.querySelector('[data-field="activity_id"]').value), 0) : 0;
                    var editBase = (window.VOYAGE_EDIT_BOOTSTRAP && window.VOYAGE_EDIT_BOOTSTRAP.activityEditUrlBase) ? window.VOYAGE_EDIT_BOOTSTRAP.activityEditUrlBase : '';
                    if (activityId && editBase) {
                        window.open(editBase.replace(/\/$/, '') + '/' + activityId + '/edit', '_blank', 'noopener');
                        return;
                    }
                    var focusTarget = rowEdit ? rowEdit.querySelector('[data-field="activity_title"]') : null;
                    if (focusTarget) {
                        focusTarget.focus();
                        focusTarget.select();
                    }
                }

                var imageBtn = e.target.closest('.voyage-activity-image');
                if (imageBtn) {
                    var rowImage = imageBtn.closest('.voyage-activity-row');
                    var imageActivityId = rowImage ? toInt(rowImage.getAttribute('data-activity-id') || (rowImage.querySelector('[data-field="activity_id"]') && rowImage.querySelector('[data-field="activity_id"]').value), 0) : 0;
                    var imageEditBase = (window.VOYAGE_EDIT_BOOTSTRAP && window.VOYAGE_EDIT_BOOTSTRAP.activityEditUrlBase) ? window.VOYAGE_EDIT_BOOTSTRAP.activityEditUrlBase : '';
                    if (imageActivityId && imageEditBase) {
                        window.open(imageEditBase.replace(/\/$/, '') + '/' + imageActivityId + '/edit', '_blank', 'noopener');
                    }
                }
            });

            rowsContainer.addEventListener('input', function(e) {
                if (e.target.closest('.voyage-activity-row')) {
                    computeLineTotal(e.target.closest('.voyage-activity-row'));
                }
            });

            rowsContainer.addEventListener('change', function(e) {
                if (e.target.closest('.voyage-activity-row')) {
                    computeLineTotal(e.target.closest('.voyage-activity-row'));
                }
            });

            if (catalogBody) {
                catalogBody.addEventListener('click', function(e) {
                    var addBtn = e.target.closest('.add-catalog-activity');
                    if (!addBtn) return;

                    var activityId = toInt(addBtn.getAttribute('data-activity-id'), 0);
                    if (!activityId) return;

                    var activity = getCatalog().find(function(item) { return Number(item.id) === Number(activityId); });
                    if (!activity) return;

                    if (!appendActivityToVoyage(activity)) return;

                    var bsModal = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getOrCreateInstance(modalEl) : null;
                    if (bsModal) {
                        bsModal.hide();
                    }
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    page = 1;
                    refreshCatalog();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    page -= 1;
                    refreshCatalog();
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    page += 1;
                    refreshCatalog();
                });
            }

            if (modalEl) {
                modalEl.addEventListener('shown.bs.modal', function() {
                    loadCatalogFromServer(!fullCatalog().length).then(function() {
                        refreshCatalog();
                    });
                    refreshCatalog();
                    if (regionHint) {
                        var filtered = Array.isArray(window.TOUR_ACTIVITIES_CATALOG) ? window.TOUR_ACTIVITIES_CATALOG : [];
                        var all = fullCatalog();
                        regionHint.textContent = filtered.length > 0
                            ? 'Le catalogue est filtré automatiquement selon la destination du voyage.'
                            : 'Aucune activité ne correspond au filtre destination. Le catalogue global est affiché.';
                    }
                    if (searchInput) searchInput.focus();
                });
            }

            document.addEventListener('voyage-activity-region-change', function() {
                page = 1;
                catalogLoadedFromServer = false;
                loadCatalogFromServer(true).then(function() {
                    refreshCatalog();
                });
            });

            window.__voyageActivitiesModalAdd = appendActivityToVoyage;
            window.__voyageActivitiesRefreshCatalog = refreshCatalog;

            reindexRows();
            refreshCatalog();
        })();

        (function inlineActivitiesQuickCreate() {
            var modalEl = document.getElementById('activitiesCatalogModal');
            var listView = document.getElementById('activities-catalog-list-view');
            var formView = document.getElementById('activities-catalog-form-view');
            if (!modalEl || !listView || !formView) return;

            var boot = window.VOYAGE_EDIT_BOOTSTRAP || {};
            var ajaxStoreUrl = boot.ajaxStoreActivityUrl || '';
            var csrfToken = boot.csrfToken || '';
            var searchInput = document.getElementById('activities-catalog-search');
            var regionHint = document.getElementById('activities-catalog-region-hint');
            var listAlert = document.getElementById('activities-catalog-list-alert');
            var formAlert = document.getElementById('activities-catalog-form-alert');
            var openCreateBtn = document.getElementById('activities-catalog-open-create');
            var backToListBtn = document.getElementById('activities-catalog-back-to-list');
            var submitBtn = document.getElementById('activities-catalog-form-submit');
            var resetBtn = document.getElementById('activities-catalog-form-reset');
            var titleInput = document.getElementById('activities-create-title');
            var typeInput = document.getElementById('activities-create-type');
            var regionInput = document.getElementById('activities-create-region');
            var adultPriceInput = document.getElementById('activities-create-adult-price');
            var childPriceInput = document.getElementById('activities-create-child-price');
            var minAgeInput = document.getElementById('activities-create-min-age');
            var maxAgeInput = document.getElementById('activities-create-max-age');
            var durationInput = document.getElementById('activities-create-duration');
            var galleryInput = document.getElementById('activities-create-gallery');

            function currentRegionTerms() {
                if (window.AjinsafroActivityRegionFilter && typeof window.AjinsafroActivityRegionFilter.currentTerms === 'function') {
                    return window.AjinsafroActivityRegionFilter.currentTerms();
                }

                var addressInput = document.getElementById('address');
                return addressInput && addressInput.value.trim() ? [addressInput.value.trim()] : [];
            }

            function normalizeActivity(raw) {
                return {
                    id: Number(raw.id || 0),
                    title: raw.title || '',
                    activity_type: raw.activity_type || '',
                    region_name: raw.region_name || raw.location_text || raw.place_text || '',
                    location_text: raw.location_text || raw.region_name || '',
                    place_text: raw.place_text || raw.region_name || '',
                    adult_price: raw.adult_price || raw.base_price || raw.price || 0,
                    child_price: raw.child_price || 0,
                    base_price: raw.base_price || raw.adult_price || raw.price || 0,
                    min_age: raw.min_age || 0,
                    max_age: raw.max_age || 0,
                    default_duration_minutes: raw.default_duration_minutes || raw.duration_minutes || 0,
                };
            }

            function setListAlert(type, message) {
                if (!listAlert) return;
                listAlert.className = 'alert alert-' + type + ' py-2';
                listAlert.textContent = message || '';
                listAlert.classList.remove('d-none');
            }

            function clearListAlert() {
                if (!listAlert) return;
                listAlert.classList.add('d-none');
            }

            function setFormAlert(type, message) {
                if (!formAlert) return;
                formAlert.className = 'alert alert-' + type;
                formAlert.textContent = message || '';
                formAlert.classList.remove('d-none');
            }

            function clearFormAlert() {
                if (!formAlert) return;
                formAlert.classList.add('d-none');
            }

            function clearFieldErrors() {
                formView.querySelectorAll('[data-error]').forEach(function(el) {
                    el.classList.add('d-none');
                    el.textContent = '';
                });

                [
                    titleInput,
                    typeInput,
                    regionInput,
                    adultPriceInput,
                    childPriceInput,
                    minAgeInput,
                    maxAgeInput,
                    durationInput,
                    galleryInput
                ].forEach(function(input) {
                    if (input) input.classList.remove('is-invalid');
                });
            }

            function applyFieldErrors(errors) {
                if (!errors) return;

                var inputMap = {
                    title: titleInput,
                    activity_type: typeInput,
                    region_name: regionInput,
                    adult_price: adultPriceInput,
                    child_price: childPriceInput,
                    min_age: minAgeInput,
                    max_age: maxAgeInput,
                    default_duration_minutes: durationInput,
                    gallery_images: galleryInput,
                    image: galleryInput,
                };

                Object.keys(errors).forEach(function(field) {
                    var normalizedField = field.indexOf('gallery_images.') === 0 ? 'gallery_images' : field;
                    var errorEl = formView.querySelector('[data-error="' + normalizedField + '"]');
                    if (errorEl) {
                        errorEl.textContent = Array.isArray(errors[field]) ? errors[field][0] : String(errors[field]);
                        errorEl.classList.remove('d-none');
                    }

                    if (inputMap[normalizedField]) {
                        inputMap[normalizedField].classList.add('is-invalid');
                    }
                });
            }

            function setFormLoading(loading) {
                var btnText = submitBtn && submitBtn.querySelector('.btn-text');
                var spinner = submitBtn && submitBtn.querySelector('.spinner-border');
                if (submitBtn) submitBtn.disabled = loading;
                if (resetBtn) resetBtn.disabled = loading;
                if (backToListBtn) backToListBtn.disabled = loading;
                if (btnText) btnText.classList.toggle('d-none', loading);
                if (spinner) spinner.classList.toggle('d-none', !loading);
            }

            function syncRegionHint() {
                if (!regionHint) return;

                var terms = currentRegionTerms();
                if (!terms.length) {
                    regionHint.textContent = 'Le catalogue est filtre automatiquement selon la destination du voyage.';
                    return;
                }

                regionHint.textContent = 'Catalogue filtre sur : ' + terms.join(', ');
            }

            function resetForm() {
                if (titleInput) titleInput.value = '';
                if (typeInput) typeInput.value = '';
                if (regionInput) regionInput.value = currentRegionTerms()[0] || '';
                if (adultPriceInput) adultPriceInput.value = '';
                if (childPriceInput) childPriceInput.value = '';
                if (minAgeInput) minAgeInput.value = '';
                if (maxAgeInput) maxAgeInput.value = '';
                if (durationInput) durationInput.value = '';
                if (galleryInput) galleryInput.value = '';
                clearFormAlert();
                clearFieldErrors();
            }

            function showListMode(type, message) {
                formView.classList.add('d-none');
                listView.classList.remove('d-none');
                clearFormAlert();
                clearFieldErrors();

                if (message) {
                    setListAlert(type || 'success', message);
                } else {
                    clearListAlert();
                }
            }

            function showFormMode() {
                listView.classList.add('d-none');
                formView.classList.remove('d-none');
                clearListAlert();
                clearFormAlert();
                clearFieldErrors();

                if (regionInput && !regionInput.value.trim()) {
                    regionInput.value = currentRegionTerms()[0] || '';
                }

                if (titleInput) {
                    window.setTimeout(function() {
                        titleInput.focus();
                    }, 60);
                }
            }

            function upsertCatalogEntry(activity) {
                ['ALL_PROGRAMME_ACTIVITIES_CATALOG', 'ALL_TOUR_ACTIVITIES_CATALOG', 'PROGRAMME_ACTIVITIES_CATALOG', 'TOUR_ACTIVITIES_CATALOG'].forEach(function(key) {
                    if (!Array.isArray(window[key])) {
                        window[key] = [];
                    }

                    var payload = {
                        id: Number(activity.id),
                        title: activity.title,
                        activity_type: activity.activity_type || '',
                        region_name: activity.region_name || activity.location_text || '',
                        location_text: activity.location_text || activity.region_name || '',
                        place_text: activity.place_text || activity.region_name || '',
                        base_price: activity.base_price || 0,
                        adult_price: activity.adult_price || activity.base_price || 0,
                        child_price: activity.child_price || 0,
                        default_duration_minutes: activity.default_duration_minutes || 0,
                        min_age: activity.min_age || 0,
                        max_age: activity.max_age || 0,
                    };

                    var idx = window[key].findIndex(function(item) {
                        return Number(item.id) === Number(activity.id);
                    });

                    if (idx >= 0) {
                        window[key][idx] = Object.assign({}, window[key][idx], payload);
                    } else {
                        window[key].unshift(payload);
                    }
                });

                if (window.AjinsafroActivityRegionFilter && typeof window.AjinsafroActivityRegionFilter.apply === 'function') {
                    window.AjinsafroActivityRegionFilter.apply();
                }
            }

            async function parseJsonResponse(response) {
                var json = await response.json().catch(function() { return null; });
                if (!response.ok || !json || json.success === false) {
                    var error = new Error((json && json.message) || 'Une erreur est survenue.');
                    error.status = response.status;
                    error.payload = json;
                    throw error;
                }

                return json;
            }

            function buildFormData() {
                var fd = new FormData();
                fd.append('_token', csrfToken);
                fd.append('allow_empty_gallery', '1');
                fd.append('title', (titleInput.value || '').trim());
                fd.append('activity_type', (typeInput.value || '').trim());
                fd.append('region_name', (regionInput.value || '').trim());
                fd.append('location_text', (regionInput.value || '').trim());
                fd.append('adult_price', adultPriceInput.value || '');
                fd.append('child_price', childPriceInput.value || '');
                fd.append('min_age', minAgeInput.value || '');
                fd.append('max_age', maxAgeInput.value || '');
                fd.append('default_duration_minutes', durationInput.value || '');

                if (galleryInput && galleryInput.files) {
                    Array.prototype.forEach.call(galleryInput.files, function(file) {
                        fd.append('gallery_images[]', file);
                    });
                }

                return fd;
            }

            async function submitForm() {
                clearFieldErrors();
                clearFormAlert();
                setFormLoading(true);

                try {
                    var response = await fetch(ajaxStoreUrl, {
                        method: 'POST',
                        body: buildFormData(),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    var json = await parseJsonResponse(response);
                    var activity = normalizeActivity(json.data || {});

                    window.__voyageActivitiesLastCreatedId = activity.id;
                    upsertCatalogEntry(activity);

                    if (typeof window.__voyageActivitiesModalAdd === 'function') {
                        window.__voyageActivitiesModalAdd(activity);
                    }

                    resetForm();
                    if (searchInput) {
                        searchInput.value = '';
                    }

                    if (typeof window.__voyageActivitiesRefreshCatalog === 'function') {
                        window.__voyageActivitiesRefreshCatalog();
                    }

                    showListMode('success', 'Activite creee et ajoutee au voyage.');
                } catch (error) {
                    if (error.status === 422 && error.payload && error.payload.errors) {
                        applyFieldErrors(error.payload.errors);
                        setFormAlert('warning', error.payload.message || 'Veuillez corriger les erreurs du formulaire.');
                    } else {
                        setFormAlert('danger', error.message || 'Impossible de creer l activite.');
                    }
                } finally {
                    setFormLoading(false);
                }
            }

            if (openCreateBtn) {
                openCreateBtn.addEventListener('click', function() {
                    resetForm();
                    showFormMode();
                });
            }

            if (backToListBtn) {
                backToListBtn.addEventListener('click', function() {
                    showListMode();
                    if (typeof window.__voyageActivitiesRefreshCatalog === 'function') {
                        window.__voyageActivitiesRefreshCatalog();
                    }
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    resetForm();
                });
            }

            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    submitForm();
                });
            }

            modalEl.addEventListener('shown.bs.modal', function() {
                syncRegionHint();
                if (!formView.classList.contains('d-none')) {
                    if (titleInput) titleInput.focus();
                    return;
                }

                if (searchInput) searchInput.focus();
            });

            document.addEventListener('voyage-activity-region-change', function() {
                syncRegionHint();

                if (!formView.classList.contains('d-none') && regionInput && !regionInput.value.trim()) {
                    regionInput.value = currentRegionTerms()[0] || '';
                }
            });

            syncRegionHint();
        })();

        // "?"?"? MODE DIAGNOSTIC: Forcer retrait des disabled + logs détaillés (À RETIRER en production) "?"?"?
        (function diagnosticMode() { return;
            console.log('OK:ht Options Persistence (v2 - Ignore Templates)');
            
            function removeDisabledFromFlightOptions() {
                var count = 0;
                var templatesContainer = document.getElementById('flight-opt-templates');
                var drawerContainer = document.getElementById('day-builder-root');
                
                document.querySelectorAll('[name^="flight_options"]').forEach(function(el) {
                    // SKIP les inputs dans le container de templates
                    if (templatesContainer && templatesContainer.contains(el)) {
                        return; // Ne PAS retirer disabled des templates
                    }
                    
                    // SKIP les inputs dans le panneau programme (day-builder-root) — doublons hors flux principal
                    if (drawerContainer && drawerContainer.contains(el)) {
                        return;
                    }
                    
                    // SKIP les inputs avec index -1 (templates clon?s)
                    if (el.name && el.name.includes('[-1]')) {
                        return;
                    }
                    
                    if (el.hasAttribute('disabled')) {
                        el.removeAttribute('disabled');
                        console.log('OK:', el.name);
                        count++;
                    }
                });
                if (count > 0) {
                    console.log('OK:rawer/templates exclus):', count);
                }
            }
            
            function interceptFormSubmission() {
                var form = document.getElementById('edit-voyage-form');
                if (!form) {
                    console.error('ERROR:form introuvable!');
                    return;
                }
                
                form.addEventListener('submit', function(e) {
                    console.log('OK:ntercept?)');
                    
                    // DÉSACTIVER le drawer pour éviter qu'il soumette ses duplications
                    var drawer = document.getElementById('day-builder-root');
                    var drawerInputsDisabled = [];
                    if (drawer) {
                        drawer.querySelectorAll('[name^="flight_options"]').forEach(function(el) {
                            if (!el.hasAttribute('disabled')) {
                                el.setAttribute('disabled', 'disabled');
                                el.setAttribute('data-was-enabled', '1');
                                drawerInputsDisabled.push(el);
                            }
                        });
                        if (drawerInputsDisabled.length > 0) {
                            console.warn('WARN:tivés temporairement:', drawerInputsDisabled.length);
                        }
                    }
                    
                    var fd = new FormData(this);
                    var flightOptionsData = {};
                    var count = 0;
                    var templatesCount = 0;
                    
                    // Filtrer les templates (index -1) du FormData
                    var entriesToRemove = [];
                    for (var pair of fd.entries()) {
                        if (pair[0].startsWith('flight_options')) {
                            if (pair[0].includes('[-1]')) {
                                entriesToRemove.push(pair[0]);
                                templatesCount++;
                            } else {
                                flightOptionsData[pair[0]] = pair[1];
                                console.log('OK:', pair[0], '=', pair[1]);
                                count++;
                            }
                        }
                    }
                    
                    if (templatesCount > 0) {
                        console.warn('WARN:(ignor?s):', templatesCount, 'champs');
                    }
                    
                    console.log('OK:alides:', count);
                    
                    if (count === 0) {
                        console.log('OK:cté: voyage consid?r? sans vol, soumission OK');
                    } else {
                        console.log('OK:soumission OK');
                    }
                    
                    // Note: Si soumission OK, la page va recharger donc pas besoin de réactiver
                }, true);
                
                console.log('OK:e installé');
            }
            
            // Ex?cuter au chargement
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    removeDisabledFromFlightOptions();
                    interceptFormSubmission();
                });
            } else {
                removeDisabledFromFlightOptions();
                interceptFormSubmission();
            }
            
            // Re-v?rifier apr?s 2 secondes (au cas où des inputs sont ajoutés dynamiquement)
            setTimeout(function() {
                console.log('OK: 2s...');
                removeDisabledFromFlightOptions();
            }, 2000);
        })();

// Toggle day_scope visibility based on is_included checkbox
(function () {
    function updateScopeVisibility(checkbox) {
        var row = checkbox.closest(".programme-activity-row");
        if (!row) { return; }
        var wrap = row.querySelector(".programme-act-scope-wrap");
        if (!wrap) { return; }
        if (checkbox.checked) {
            wrap.classList.add("d-none");
        } else {
            wrap.classList.remove("d-none");
        }
    }
    document.addEventListener("change", function (e) {
        if (e.target && e.target.classList.contains("programme-act-is-included")) {
            updateScopeVisibility(e.target);
        }
    });
})();
