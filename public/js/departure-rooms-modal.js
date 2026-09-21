(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var dialog = document.getElementById('departure-rooms-modal');
        var trigger = document.getElementById('btn-manage-departure-rooms');
        if (!dialog || !trigger) return;
        var form = document.getElementById('departure-rooms-form');
        var fields = document.getElementById('departure-rooms-fields');
        var rows = document.getElementById('departure-rooms-rows');
        var summary = document.getElementById('departure-rooms-summary');
        var status = document.getElementById('departure-rooms-status');
        var reload = dialog.querySelector('[data-rooms-reload]');
        var discard = dialog.querySelector('[data-rooms-discard]');
        var data, endpoint, selectedDeparture, revision, timer, saving;
        var dirty = false, version = 0, loadVersion = 0;
        function escape(value) {
            var el = document.createElement('span'); el.textContent = String(value == null ? '' : value); return el.innerHTML.replace(/"/g, '&quot;');
        }
        function notice(message, error) {
            status.textContent = message; status.dataset.error = error ? 'true' : 'false';
            discard.hidden = !error || !dirty;
        }
        function selection() {
            var state = window.reservationState || {};
            return {voyage: state.selectedTourId, departure: state.selectedDepartureId};
        }
        function updateTrigger() {
            var selected = selection(); trigger.disabled = !selected.voyage || !selected.departure;
        }
        async function request(url, options) {
            var response = await fetch(url, Object.assign({
                credentials: 'same-origin', signal: AbortSignal.timeout(20000),
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('#reservation-create-form [name="_token"]')?.value || ''}
            }, options));
            var result = await response.json().catch(function () { return {}; });
            if (!response.ok) {
                var errors = Object.values(result.errors || {}).flat();
                var error = new Error(errors.join(' ') || result.message || 'Impossible d’enregistrer les chambres. Réessayez.');
                error.conflict = response.status === 409; throw error;
            }
            return result;
        }
        function rowHtml(room) {
            var options = '<option value="">Circuit complet</option>' + data.hotels.map(function (hotel) {
                return '<option value="' + hotel.id + '"' + (Number(room.hotel_id) === Number(hotel.id) ? ' selected' : '') + '>' + escape(hotel.hotel_name) + '</option>';
            }).join('');
            function input(field, type, min, max, step) {
                return '<input data-field="' + field + '" aria-label="' + ({room_type:'Type de chambre',quantity:'Quantité',capacity_per_room:'Capacité par chambre',supplement:'Supplément par personne'}[field]) + '" type="' + type + '" required value="' + escape(room[field]) + '"' + (type === 'number' ? ' min="' + min + '" max="' + max + '" step="' + step + '"' : ' maxlength="100" list="departure-room-types"') + '>';
            }
            return '<tr data-room-id="' + (room.id || '') + '"><td>' + input('room_type', 'text') + '</td><td>' + input('quantity','number',0,10000,1) + '</td><td>' + input('capacity_per_room','number',1,50,1) + '</td><td>' + input('supplement','number',-9999999,9999999,'.01') + '</td><td><select aria-label="Application" data-field="hotel_id">' + options + '</select></td><td data-room-coverage></td><td><button type="button" data-rooms-remove aria-label="Supprimer ce type de chambre">×</button></td></tr>';
        }
        function collect() {
            return Array.from(rows.children).map(function (row) {
                var values = {id: row.dataset.roomId ? Number(row.dataset.roomId) : null};
                row.querySelectorAll('[data-field]').forEach(function (field) {
                    values[field.dataset.field] = field.dataset.field === 'room_type' ? field.value.trim() : (field.dataset.field === 'hotel_id' && !field.value ? null : Number(field.value));
                });
                return values;
            });
        }
        function coverage() {
            if (!data) return;
            var total = 0;
            collect().forEach(function (room, index) {
                var places = room.quantity * room.capacity_per_room; total += places;
                rows.children[index].querySelector('[data-room-coverage]').textContent = places;
            });
            var departure = data.departure, capacity = Number(departure.total_capacity || 0);
            var date = String(departure.start_date || '').slice(0, 10).split('-').reverse().join('/');
            summary.innerHTML = '<div><strong>Départ du ' + escape(date) + '</strong>Capacité : ' + capacity + ' places · Réservations : ' + Number(departure.reserved_capacity || 0) + ' · Disponibles : ' + Number(departure.available_capacity || 0) + '</div><span class="departure-rooms-modal__coverage" data-covered="' + (total === capacity) + '">Total départ : ' + total + '/' + capacity + ' · Écart ' + (total - capacity) + '</span>';
        }
        async function load() {
            var generation = ++loadVersion;
            fields.disabled = true; dialog.querySelector('[data-rooms-save]').disabled = true;
            notice('Chargement des chambres…'); reload.hidden = true;
            try {
                var loaded = await request(endpoint);
                if (generation !== loadVersion || !dialog.open) return;
                data = loaded; revision = data.revision;
                rows.innerHTML = data.rooms.map(rowHtml).join('');
                dirty = false; version = 0; coverage();
                fields.disabled = false; dialog.querySelector('[data-rooms-save]').disabled = false;
                notice('Modifiez une chambre : la disponibilité et le récapitulatif seront actualisés automatiquement.');
            } catch (error) { if (generation === loadVersion && dialog.open) { notice(error.message, true); reload.hidden = false; } }
        }
        function valid(report) {
            if (!rows.children.length) { notice('Ajoutez au moins un type de chambre. Une quantité de 0 désactive sa disponibilité.', true); return false; }
            return report ? form.reportValidity() : form.checkValidity();
        }
        function save() {
            clearTimeout(timer);
            if (saving) return saving;
            if (!dirty || !valid(false)) return Promise.resolve(!dirty);
            saving = (async function () {
                while (dirty) {
                    if (!valid(false)) return false;
                    var sentVersion = version, elements = Array.from(rows.children);
                    notice('Enregistrement…');
                    try {
                        var result = await request(endpoint, {method:'PUT', body:JSON.stringify({revision:revision, rooms:collect()})});
                        revision = result.revision;
                        result.rooms.forEach(function (room, index) {
                            if (elements[index]?.isConnected) elements[index].dataset.roomId = room.id;
                        });
                        dirty = version !== sentVersion;
                        if (String(selection().departure) === String(selectedDeparture) && typeof window.reservationCreateApplyDepartureRooms === 'function') {
                            window.reservationCreateApplyDepartureRooms(result.availability);
                        }
                        notice(dirty ? 'Enregistrement des dernières modifications…' : 'Enregistré · Chambres et récapitulatif mis à jour.');
                    } catch (error) {
                        notice(error.message, true); reload.hidden = false;
                        // A stale revision must be reloaded before another write.
                        if (error.conflict) fields.disabled = true;
                        return false;
                    }
                }
                return true;
            })().finally(function () { saving = null; });
            return saving;
        }
        function changed() {
            dirty = true; version++; coverage(); clearTimeout(timer);
            notice('Modifications en attente…');
            if (valid(false)) timer = setTimeout(save, 500);
            else notice('Complétez les valeurs des chambres pour les enregistrer.', true);
        }
        async function close() {
            if (!dirty || (valid(true) && await save())) dialog.close();
        }
        trigger.addEventListener('click', function () {
            var selected = selection(); if (!selected.voyage || !selected.departure) return;
            selectedDeparture = selected.departure;
            endpoint = dialog.dataset.endpoint.replace('__voyage__', encodeURIComponent(selected.voyage)).replace('__departure__', encodeURIComponent(selectedDeparture));
            rows.innerHTML = ''; summary.innerHTML = ''; dialog.showModal(); load();
        });
        form.addEventListener('submit', function (event) { event.preventDefault(); close(); });
        fields.addEventListener('input', changed);
        fields.addEventListener('change', changed);
        dialog.querySelector('[data-rooms-close]').addEventListener('click', close);
        dialog.addEventListener('cancel', function (event) { event.preventDefault(); close(); });
        dialog.addEventListener('close', function () { loadVersion++; clearTimeout(timer); trigger.focus(); });
        reload.addEventListener('click', function () { if (!saving) { clearTimeout(timer); load(); } });
        discard.addEventListener('click', function () {
            if (saving) return;
            clearTimeout(timer); dirty = false; dialog.close();
        });
        dialog.querySelector('[data-rooms-add]').addEventListener('click', function () {
            rows.insertAdjacentHTML('beforeend', rowHtml({room_type:'Double', quantity:1, capacity_per_room:2, supplement:0}));
            rows.lastElementChild.querySelector('input').focus(); changed();
        });
        dialog.querySelector('[data-rooms-default]').addEventListener('click', function () {
            var capacity = Number(data.departure.total_capacity || 0), defaults = [];
            defaults.push({room_type:'Double', quantity:Math.floor(capacity / 2), capacity_per_room:2, supplement:0, hotel_id:null});
            if (capacity % 2) defaults.push({room_type:'Single', quantity:1, capacity_per_room:1, supplement:0, hotel_id:null});
            var current = collect();
            defaults.forEach(function (room) { room.id = current.find(function (item) { return !item.hotel_id && item.room_type === room.room_type && item.capacity_per_room === room.capacity_per_room; })?.id; });
            rows.innerHTML = defaults.map(rowHtml).join(''); changed();
        });
        rows.addEventListener('click', function (event) {
            var button = event.target.closest('[data-rooms-remove]'); if (!button) return;
            button.closest('tr').remove(); changed();
        });
        document.addEventListener('reservation:rooms-loaded', updateTrigger);
        window.addEventListener('beforeunload', function (event) { if (dirty || saving) { event.preventDefault(); event.returnValue = ''; } });
        updateTrigger();
    });
})();
