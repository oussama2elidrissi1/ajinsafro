<style>
#day-builder-transfers-manager .day-builder-context {
    background: #e7f1ff;
    border: 1px solid #b6d7ff;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 16px;
}
#day-builder-transfers-manager .day-builder-summary-block {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 12px;
}
</style>
<div id="day-builder-transfers-manager">
    {{-- Bloc config du jour (même pattern que Vols) --}}
    <div class="day-builder-context">
        <div class="d-flex align-items-start gap-2">
            <i class="bx bx-car text-primary mt-1"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold text-primary" id="transfers-context-title">Transferts – Jour 1</div>
                <div class="small text-muted" id="transfers-context-description">Configurez les transferts (arrivée / départ) pour ce jour.</div>
            </div>
        </div>
    </div>

    {{-- État / résumé --}}
    <div class="day-builder-summary-block">
        <div id="transfers-summary-text" class="small">0 transfert configuré</div>
    </div>

    {{-- Actions : "+ Ajouter" si vide, "Configurer" + "Tout retirer" si déjà des transferts (jour imposé, pas de select Jour) --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-sm btn-primary d-none" id="transfers-manager-add-btn">
            <i class="bx bx-plus"></i> <span id="transfers-add-btn-label">+ Ajouter des transferts (Jour 1)</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary d-none" id="transfers-manager-choose-btn">
            <i class="bx bx-edit-alt"></i> <span id="transfers-choose-btn-label">Configurer les transferts (Jour 1)</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger d-none" id="transfers-manager-remove-all-btn">
            <i class="bx bx-trash"></i> Tout retirer
        </button>
    </div>

    {{-- Créer un nouveau transfert (comme activités) --}}
    <div class="card border-primary mb-3" id="transfers-new-form-wrap" style="display: none;">
        <div class="card-header bg-light py-2">
            <strong><i class="bx bx-plus-circle"></i> Créer un nouveau transfert</strong>
        </div>
        <div class="card-body">
            {{-- Pas de <form> : dans #edit-voyage-form, éviter les conflits --}}
            <div id="transfers-new-form-el" data-action="{{ route('admin.circuits.tour-transfers.store') }}">
                @csrf
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Direction</label>
                        <select class="form-select form-select-sm" id="transfers-new-direction">
                            <option value="arrival">Arrivée</option>
                            <option value="departure">Départ</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">De</label>
                        <input type="text" class="form-control form-control-sm" id="transfers-new-from" placeholder="Ex. Aéroport">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">À</label>
                        <input type="text" class="form-control form-control-sm" id="transfers-new-to" placeholder="Ex. Hôtel">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Heure prise en charge</label>
                        <input type="time" class="form-control form-control-sm" id="transfers-new-pickup">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Heure arrivée</label>
                        <input type="time" class="form-control form-control-sm" id="transfers-new-dropoff">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Type de véhicule</label>
                        <input type="text" class="form-control form-control-sm" id="transfers-new-vehicle" placeholder="Ex. Minibus">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Notes</label>
                        <textarea class="form-control form-control-sm" id="transfers-new-notes" rows="2" placeholder="Notes"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-sm btn-primary" id="transfers-new-submit">
                            <span class="btn-text">Créer</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="transfers-new-cancel">Annuler</button>
                    </div>
                    <div id="transfers-new-error" class="small text-danger mt-2" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Picker : aucun champ "Jour", enregistré automatiquement pour le jour courant --}}
    <div id="transfers-manager-picker" class="border rounded p-3 mb-3" style="display: none;">
        <p class="small text-muted mb-2" id="transfers-picker-hint">Sera enregistré pour le jour courant.</p>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label small mb-0">Transferts (multi-sélection)</label>
            <button type="button" class="btn btn-sm btn-outline-success" id="transfers-picker-new-btn">
                <i class="bx bx-plus"></i> Créer un nouveau
            </button>
        </div>
        <div id="transfers-manager-list" class="border rounded p-2 bg-white" style="max-height: 280px; overflow-y: auto;">
            <!-- Rempli dynamiquement -->
        </div>
        <button type="button" class="btn btn-primary btn-sm mt-2" id="transfers-manager-confirm-btn">
            <i class="bx bx-check"></i> Confirmer
        </button>
    </div>
</div>

<script>
(function() {
    if (!window.tourTransfersData) window.tourTransfersData = { arrival: [], departure: [] };

    var currentDayIndex = '';
    var titleEl = document.getElementById('transfers-context-title');
    var descEl = document.getElementById('transfers-context-description');
    var summaryEl = document.getElementById('transfers-summary-text');
    var addBtn = document.getElementById('transfers-manager-add-btn');
    var addBtnLabel = document.getElementById('transfers-add-btn-label');
    var chooseBtnLabel = document.getElementById('transfers-choose-btn-label');
    var chooseBtn = document.getElementById('transfers-manager-choose-btn');
    var removeAllBtn = document.getElementById('transfers-manager-remove-all-btn');
    var picker = document.getElementById('transfers-manager-picker');
    var pickerHint = document.getElementById('transfers-picker-hint');
    var listEl = document.getElementById('transfers-manager-list');
    var confirmBtn = document.getElementById('transfers-manager-confirm-btn');
    var newFormWrap = document.getElementById('transfers-new-form-wrap');
    var newFormEl = document.getElementById('transfers-new-form-el');
    var newFormSubmit = document.getElementById('transfers-new-submit');
    var newFormCancel = document.getElementById('transfers-new-cancel');
    var newFormError = document.getElementById('transfers-new-error');

    function getDrawerDay() {
        var drawer = document.getElementById('day-builder-drawer');
        if (!drawer) return { index: '', number: 1 };
        return {
            index: drawer.getAttribute('data-day-index') || '',
            number: parseInt(drawer.getAttribute('data-day-number') || '1', 10) || 1
        };
    }

    function loadTransfersList() {
        if (!listEl || !window.tourTransfersData) return;
        listEl.innerHTML = '';
        var allTransfers = [];
        if (window.tourTransfersData.arrival && window.tourTransfersData.arrival.length > 0) {
            window.tourTransfersData.arrival.forEach(function(t) { allTransfers.push(t); });
        }
        if (window.tourTransfersData.departure && window.tourTransfersData.departure.length > 0) {
            window.tourTransfersData.departure.forEach(function(t) { allTransfers.push(t); });
        }
        if (allTransfers.length === 0) {
            var emptyMsg = document.createElement('div');
            emptyMsg.className = 'text-muted small text-center py-3';
            emptyMsg.textContent = 'Aucun transfert disponible. Créez-en un ci-dessus.';
            listEl.appendChild(emptyMsg);
            return;
        }
        // Arrivée
        var arrivals = allTransfers.filter(function(t) { return t.direction === 'arrival'; });
        if (arrivals.length > 0) {
            var arrivalLabel = document.createElement('div');
            arrivalLabel.className = 'fw-bold text-success small mb-2 mt-2';
            arrivalLabel.textContent = 'Arrivée (' + arrivals.length + ') :';
            listEl.appendChild(arrivalLabel);
            arrivals.forEach(function(transfer) {
                var card = document.createElement('div');
                card.className = 'card mb-2';
                var cardBody = document.createElement('div');
                cardBody.className = 'card-body p-2';
                var checkWrap = document.createElement('div');
                checkWrap.className = 'form-check d-flex align-items-start gap-2';
                var input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'form-check-input transfer-checkbox mt-1';
                input.value = transfer.id;
                input.id = 'transfer-' + transfer.id;
                input.dataset.direction = 'arrival';
                var labelDiv = document.createElement('div');
                labelDiv.className = 'flex-grow-1 small';
                var mainLabel = document.createElement('label');
                mainLabel.className = 'form-check-label fw-medium d-block';
                mainLabel.htmlFor = 'transfer-' + transfer.id;
                mainLabel.textContent = (transfer.from_label || '?') + ' → ' + (transfer.to_label || '?');
                labelDiv.appendChild(mainLabel);
                var details = [];
                if (transfer.pickup_time) details.push('Prise: ' + transfer.pickup_time);
                if (transfer.dropoff_time) details.push('Arrivée: ' + transfer.dropoff_time);
                if (transfer.vehicle_type) details.push('Véhicule: ' + transfer.vehicle_type);
                if (transfer.day_number) details.push('Jour ' + transfer.day_number);
                if (transfer.is_optional) details.push('Option client');
                if (details.length > 0) {
                    var detailsEl = document.createElement('div');
                    detailsEl.className = 'text-muted mt-1';
                    detailsEl.style.fontSize = '11px';
                    detailsEl.textContent = details.join(' • ');
                    labelDiv.appendChild(detailsEl);
                }
                if (transfer.notes) {
                    var notesEl = document.createElement('div');
                    notesEl.className = 'text-muted mt-1';
                    notesEl.style.fontSize = '11px';
                    notesEl.style.fontStyle = 'italic';
                    notesEl.textContent = transfer.notes.substring(0, 60) + (transfer.notes.length > 60 ? '...' : '');
                    labelDiv.appendChild(notesEl);
                }
                checkWrap.appendChild(input);
                checkWrap.appendChild(labelDiv);
                cardBody.appendChild(checkWrap);
                card.appendChild(cardBody);
                listEl.appendChild(card);
            });
        }
        // Départ
        var departures = allTransfers.filter(function(t) { return t.direction === 'departure'; });
        if (departures.length > 0) {
            var depLabel = document.createElement('div');
            depLabel.className = 'fw-bold text-danger small mb-2 mt-2';
            depLabel.textContent = 'Départ (' + departures.length + ') :';
            listEl.appendChild(depLabel);
            departures.forEach(function(transfer) {
                var card = document.createElement('div');
                card.className = 'card mb-2';
                var cardBody = document.createElement('div');
                cardBody.className = 'card-body p-2';
                var checkWrap = document.createElement('div');
                checkWrap.className = 'form-check d-flex align-items-start gap-2';
                var input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'form-check-input transfer-checkbox mt-1';
                input.value = transfer.id;
                input.id = 'transfer-' + transfer.id;
                input.dataset.direction = 'departure';
                var labelDiv = document.createElement('div');
                labelDiv.className = 'flex-grow-1 small';
                var mainLabel = document.createElement('label');
                mainLabel.className = 'form-check-label fw-medium d-block';
                mainLabel.htmlFor = 'transfer-' + transfer.id;
                mainLabel.textContent = (transfer.from_label || '?') + ' → ' + (transfer.to_label || '?');
                labelDiv.appendChild(mainLabel);
                var details = [];
                if (transfer.pickup_time) details.push('Prise: ' + transfer.pickup_time);
                if (transfer.dropoff_time) details.push('Arrivée: ' + transfer.dropoff_time);
                if (transfer.vehicle_type) details.push('Véhicule: ' + transfer.vehicle_type);
                if (transfer.day_number) details.push('Jour ' + transfer.day_number);
                if (transfer.is_optional) details.push('Option client');
                if (details.length > 0) {
                    var detailsEl = document.createElement('div');
                    detailsEl.className = 'text-muted mt-1';
                    detailsEl.style.fontSize = '11px';
                    detailsEl.textContent = details.join(' • ');
                    labelDiv.appendChild(detailsEl);
                }
                if (transfer.notes) {
                    var notesEl = document.createElement('div');
                    notesEl.className = 'text-muted mt-1';
                    notesEl.style.fontSize = '11px';
                    notesEl.style.fontStyle = 'italic';
                    notesEl.textContent = transfer.notes.substring(0, 60) + (transfer.notes.length > 60 ? '...' : '');
                    labelDiv.appendChild(notesEl);
                }
                checkWrap.appendChild(input);
                checkWrap.appendChild(labelDiv);
                cardBody.appendChild(checkWrap);
                card.appendChild(cardBody);
                listEl.appendChild(card);
            });
        }
    }

    function getCheckedTransferIds() {
        var boxes = document.querySelectorAll('#day-builder-transfers-manager .transfer-checkbox:checked');
        return Array.from(boxes).map(function(cb) { return parseInt(cb.value, 10); });
    }

    function setCheckboxesFromIds(ids) {
        document.querySelectorAll('#day-builder-transfers-manager .transfer-checkbox').forEach(function(cb) {
            cb.checked = ids.indexOf(parseInt(cb.value, 10)) !== -1;
        });
    }

    function refreshUI() {
        var day = getDrawerDay();
        currentDayIndex = day.index;
        if (titleEl) titleEl.textContent = 'Transferts – Jour ' + day.number;
        if (descEl) descEl.textContent = 'Configurez les transferts (arrivée / départ) pour ce jour. Pas de champ "Jour" : le jour est imposé par le contexte.';
        if (addBtnLabel) addBtnLabel.textContent = '+ Ajouter des transferts (Jour ' + day.number + ')';
        if (chooseBtnLabel) chooseBtnLabel.textContent = 'Configurer les transferts (Jour ' + day.number + ')';
        if (pickerHint) pickerHint.textContent = 'Sera enregistré automatiquement pour le Jour ' + day.number + '.';

        var ids = (window.dayItemsManager && day.index !== '') ? window.dayItemsManager.getTransfers(day.index) : [];
        var count = ids.length;
        var isEmpty = count === 0;
        if (summaryEl) {
            summaryEl.textContent = '';
            if (count === 0) {
                summaryEl.textContent = '0 transfert configuré';
            } else {
                summaryEl.textContent = count + ' transfert' + (count > 1 ? 's' : '') + ' configuré' + (count > 1 ? 's' : '');
                if (window.tourTransfersData) {
                    var lines = [];
                    ids.forEach(function(id) {
                        var t = window.tourTransfersData.arrival.find(function(x) { return x.id === id; }) ||
                                window.tourTransfersData.departure.find(function(x) { return x.id === id; });
                        if (t) {
                            var line = (t.from_label || '?') + ' → ' + (t.to_label || '?');
                            var details = [];
                            if (t.vehicle_type) details.push(t.vehicle_type);
                            if (t.pickup_time) details.push('Prise: ' + t.pickup_time);
                            if (t.dropoff_time) details.push('Arrivée: ' + t.dropoff_time);
                            if (details.length > 0) line += ' (' + details.join(', ') + ')';
                            lines.push(line);
                        }
                    });
                    if (lines.length) {
                        var div = document.createElement('div');
                        div.className = 'mt-2 text-muted';
                        div.innerHTML = lines.map(function(l) { return '<div class="small">' + l + '</div>'; }).join('');
                        summaryEl.appendChild(div);
                    }
                }
            }
        }
        if (addBtn) addBtn.classList.toggle('d-none', !isEmpty);
        if (chooseBtn) chooseBtn.classList.toggle('d-none', isEmpty);
        if (removeAllBtn) removeAllBtn.classList.toggle('d-none', isEmpty);
        loadTransfersList();
        if (day.index !== '' && window.dayItemsManager) {
            setCheckboxesFromIds(window.dayItemsManager.getTransfers(day.index));
        }
    }

    function refreshSummaryOnly() {
        var day = getDrawerDay();
        var ids = (window.dayItemsManager && day.index !== '') ? window.dayItemsManager.getTransfers(day.index) : [];
        var count = ids.length;
        var isEmpty = count === 0;
        if (summaryEl) {
            summaryEl.textContent = '';
            if (count === 0) {
                summaryEl.textContent = '0 transfert configuré';
            } else {
                summaryEl.textContent = count + ' transfert' + (count > 1 ? 's' : '') + ' configuré' + (count > 1 ? 's' : '');
                if (window.tourTransfersData) {
                    var lines = [];
                    ids.forEach(function(id) {
                        var t = window.tourTransfersData.arrival.find(function(x) { return x.id === id; }) ||
                                window.tourTransfersData.departure.find(function(x) { return x.id === id; });
                        if (t) lines.push((t.from_label || '?') + ' → ' + (t.to_label || '?'));
                    });
                    if (lines.length) {
                        lines.forEach(function(l) {
                            var d = document.createElement('div');
                            d.className = 'small text-muted mt-1';
                            d.textContent = l;
                            summaryEl.appendChild(d);
                        });
                    }
                }
            }
        }
        if (addBtn) addBtn.classList.toggle('d-none', !isEmpty);
        if (chooseBtn) chooseBtn.classList.toggle('d-none', isEmpty);
        if (removeAllBtn) removeAllBtn.classList.toggle('d-none', isEmpty);
    }

    document.addEventListener('day-builder:context-changed', function(e) {
        var detail = e.detail || {};
        currentDayIndex = String(detail.dayIndex || '');
        if (window.dayItemsManager) window.dayItemsManager.loadFromForm(currentDayIndex);
        refreshUI();
        if (picker) picker.style.display = 'none';
        if (newFormWrap) newFormWrap.style.display = 'none';
    });

    function openPicker() {
        var day = getDrawerDay();
        loadTransfersList();
        if (day.index !== '' && window.dayItemsManager) {
            setCheckboxesFromIds(window.dayItemsManager.getTransfers(day.index));
        }
        if (picker) picker.style.display = 'block';
        if (newFormWrap) newFormWrap.style.display = 'none';
    }
    function openNewForm() {
        if (newFormWrap) newFormWrap.style.display = 'block';
        if (picker) picker.style.display = 'none';
        if (newFormError) { newFormError.style.display = 'none'; newFormError.textContent = ''; }
        var dirInp = document.getElementById('transfers-new-direction');
        var fromInp = document.getElementById('transfers-new-from');
        var toInp = document.getElementById('transfers-new-to');
        var pickupInp = document.getElementById('transfers-new-pickup');
        var dropoffInp = document.getElementById('transfers-new-dropoff');
        var vehicleInp = document.getElementById('transfers-new-vehicle');
        var notesInp = document.getElementById('transfers-new-notes');
        if (dirInp) dirInp.value = 'arrival';
        if (fromInp) fromInp.value = '';
        if (toInp) toInp.value = '';
        if (pickupInp) pickupInp.value = '';
        if (dropoffInp) dropoffInp.value = '';
        if (vehicleInp) vehicleInp.value = '';
        if (notesInp) notesInp.value = '';
    }
    if (addBtn && picker) addBtn.addEventListener('click', function() {
        var day = getDrawerDay();
        if (day.index === '') { openPicker(); return; }
        var hasTransfers = window.dayItemsManager && window.dayItemsManager.getTransfers(day.index).length > 0;
        if (hasTransfers) openPicker();
        else openNewForm();
    });
    if (chooseBtn && picker) chooseBtn.addEventListener('click', function() {
        if (picker.style.display === 'none') openPicker();
        else picker.style.display = 'none';
    });
    var pickerNewBtn = document.getElementById('transfers-picker-new-btn');
    if (pickerNewBtn) pickerNewBtn.addEventListener('click', openNewForm);
    if (newFormCancel && newFormWrap) newFormCancel.addEventListener('click', function() {
        newFormWrap.style.display = 'none';
    });

    if (newFormSubmit && newFormEl) {
        newFormSubmit.addEventListener('click', function() {
            var form = document.getElementById('edit-voyage-form');
            var tourId = form ? parseInt(form.getAttribute('data-voyage-id') || '0', 10) : 0;
            if (!tourId) { if (newFormError) { newFormError.textContent = 'Tour ID manquant.'; newFormError.style.display = 'block'; } return; }
            var day = getDrawerDay();
            var direction = document.getElementById('transfers-new-direction');
            var fromInp = document.getElementById('transfers-new-from');
            var toInp = document.getElementById('transfers-new-to');
            if (!fromInp || !fromInp.value.trim()) { if (fromInp) fromInp.focus(); return; }
            if (!toInp || !toInp.value.trim()) { if (toInp) toInp.focus(); return; }
            if (newFormError) { newFormError.style.display = 'none'; newFormError.textContent = ''; }
            var btnText = newFormSubmit.querySelector('.btn-text');
            var spinner = newFormSubmit.querySelector('.spinner-border');
            newFormSubmit.disabled = true;
            if (btnText) btnText.classList.add('d-none');
            if (spinner) spinner.classList.remove('d-none');
            var formData = new FormData();
            var token = newFormEl.querySelector('input[name="_token"]');
            if (token) formData.append('_token', token.value);
            formData.append('tour_id', tourId);
            formData.append('direction', direction ? direction.value : 'arrival');
            formData.append('from_label', fromInp.value.trim());
            formData.append('to_label', toInp.value.trim());
            var pickupInp = document.getElementById('transfers-new-pickup');
            var dropoffInp = document.getElementById('transfers-new-dropoff');
            var vehicleInp = document.getElementById('transfers-new-vehicle');
            var notesInp = document.getElementById('transfers-new-notes');
            if (pickupInp && pickupInp.value) formData.append('pickup_time', pickupInp.value);
            if (dropoffInp && dropoffInp.value) formData.append('dropoff_time', dropoffInp.value);
            if (vehicleInp && vehicleInp.value) formData.append('vehicle_type', vehicleInp.value.trim());
            if (notesInp && notesInp.value) formData.append('notes', notesInp.value.trim());
            if (day.index !== '') formData.append('day_number', day.number);
            var url = newFormEl.getAttribute('data-action') || '';
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) {
                if (r.ok) return r.json();
                return r.json().then(function(data) { throw data; });
            })
            .then(function(data) {
                if (!data.transfer) return;
                var t = data.transfer;
                var dir = t.direction === 'arrival' ? 'arrival' : 'departure';
                if (!window.tourTransfersData[dir]) window.tourTransfersData[dir] = [];
                window.tourTransfersData[dir].push({
                    id: t.id,
                    direction: dir,
                    from_label: t.from_label || '',
                    to_label: t.to_label || '',
                    pickup_time: t.pickup_time || '',
                    dropoff_time: t.dropoff_time || '',
                    vehicle_type: t.vehicle_type || '',
                    notes: t.notes || '',
                    day_number: day.number || null,
                    is_optional: t.is_optional || false,
                    image_id: null
                });
                loadTransfersList();
                if (day.index !== '' && window.dayItemsManager) {
                    var currentIds = window.dayItemsManager.getTransfers(day.index);
                    currentIds.push(t.id);
                    window.dayItemsManager.setTransfers(day.index, currentIds);
                    window.dayItemsManager.syncToForm(day.index);
                    document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: day.index } }));
                }
                refreshUI();
                newFormWrap.style.display = 'none';
            })
            .catch(function(err) {
                var msg = (err && err.message) || 'Erreur lors de la création.';
                if (err && err.errors) {
                    var first = Object.keys(err.errors).map(function(k) { return err.errors[k][0]; })[0];
                    if (first) msg = first;
                }
                if (newFormError) { newFormError.textContent = msg; newFormError.style.display = 'block'; }
            })
            .finally(function() {
                newFormSubmit.disabled = false;
                if (btnText) btnText.classList.remove('d-none');
                if (spinner) spinner.classList.add('d-none');
            });
        });
    }

    if (removeAllBtn) {
        removeAllBtn.addEventListener('click', function() {
            if (!window.dayItemsManager || currentDayIndex === '') return;
            window.dayItemsManager.setTransfers(currentDayIndex, []);
            window.dayItemsManager.syncToForm(currentDayIndex);
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: currentDayIndex } }));
            refreshSummaryOnly();
            if (picker) picker.style.display = 'none';
        });
    }

    if (confirmBtn && listEl) {
        confirmBtn.addEventListener('click', function() {
            if (!window.dayItemsManager || currentDayIndex === '') return;
            var ids = getCheckedTransferIds();
            window.dayItemsManager.setTransfers(currentDayIndex, ids);
            window.dayItemsManager.syncToForm(currentDayIndex);
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: currentDayIndex } }));
            refreshSummaryOnly();
            picker.style.display = 'none';
            var drawer = document.getElementById('day-builder-drawer');
            if (drawer && typeof bootstrap !== 'undefined') {
                var offcanvas = bootstrap.Offcanvas.getInstance(drawer);
                if (offcanvas) offcanvas.hide();
            }
        });
    }

    if (newFormSubmit && newFormEl) {
        newFormSubmit.addEventListener('click', function() {
            var form = document.getElementById('edit-voyage-form');
            var tourId = form ? parseInt(form.getAttribute('data-voyage-id') || '0', 10) : 0;
            if (!tourId) { if (newFormError) { newFormError.textContent = 'Tour ID manquant.'; newFormError.style.display = 'block'; } return; }
            var day = getDrawerDay();
            var direction = document.getElementById('transfers-new-direction');
            var fromInp = document.getElementById('transfers-new-from');
            var toInp = document.getElementById('transfers-new-to');
            if (!fromInp || !fromInp.value.trim()) { if (fromInp) fromInp.focus(); return; }
            if (!toInp || !toInp.value.trim()) { if (toInp) toInp.focus(); return; }
            if (newFormError) { newFormError.style.display = 'none'; newFormError.textContent = ''; }
            var btnText = newFormSubmit.querySelector('.btn-text');
            var spinner = newFormSubmit.querySelector('.spinner-border');
            newFormSubmit.disabled = true;
            if (btnText) btnText.classList.add('d-none');
            if (spinner) spinner.classList.remove('d-none');
            var formData = new FormData();
            var token = newFormEl.querySelector('input[name="_token"]');
            if (token) formData.append('_token', token.value);
            formData.append('tour_id', tourId);
            formData.append('direction', direction ? direction.value : 'arrival');
            formData.append('from_label', fromInp.value.trim());
            formData.append('to_label', toInp.value.trim());
            var pickupInp = document.getElementById('transfers-new-pickup');
            var dropoffInp = document.getElementById('transfers-new-dropoff');
            var vehicleInp = document.getElementById('transfers-new-vehicle');
            var notesInp = document.getElementById('transfers-new-notes');
            if (pickupInp && pickupInp.value) formData.append('pickup_time', pickupInp.value);
            if (dropoffInp && dropoffInp.value) formData.append('dropoff_time', dropoffInp.value);
            if (vehicleInp && vehicleInp.value) formData.append('vehicle_type', vehicleInp.value.trim());
            if (notesInp && notesInp.value) formData.append('notes', notesInp.value.trim());
            if (day.index !== '') formData.append('day_number', day.number);
            var url = newFormEl.getAttribute('data-action') || '';
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) {
                if (r.ok) return r.json();
                return r.json().then(function(data) { throw data; });
            })
            .then(function(data) {
                if (!data.transfer) return;
                var t = data.transfer;
                var dir = t.direction === 'arrival' ? 'arrival' : 'departure';
                if (!window.tourTransfersData[dir]) window.tourTransfersData[dir] = [];
                window.tourTransfersData[dir].push({
                    id: t.id,
                    direction: dir,
                    from_label: t.from_label || '',
                    to_label: t.to_label || '',
                    pickup_time: t.pickup_time || '',
                    dropoff_time: t.dropoff_time || '',
                    vehicle_type: t.vehicle_type || '',
                    notes: t.notes || '',
                    day_number: day.number || null,
                    is_optional: t.is_optional || false,
                    image_id: null
                });
                loadTransfersList();
                if (day.index !== '' && window.dayItemsManager) {
                    var currentIds = window.dayItemsManager.getTransfers(day.index);
                    currentIds.push(t.id);
                    window.dayItemsManager.setTransfers(day.index, currentIds);
                    window.dayItemsManager.syncToForm(day.index);
                    document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: day.index } }));
                }
                refreshUI();
                newFormWrap.style.display = 'none';
            })
            .catch(function(err) {
                var msg = (err && err.message) || 'Erreur lors de la création.';
                if (err && err.errors) {
                    var first = Object.keys(err.errors).map(function(k) { return err.errors[k][0]; })[0];
                    if (first) msg = first;
                }
                if (newFormError) { newFormError.textContent = msg; newFormError.style.display = 'block'; }
            })
            .finally(function() {
                newFormSubmit.disabled = false;
                if (btnText) btnText.classList.remove('d-none');
                if (spinner) spinner.classList.add('d-none');
            });
        });
    }

    refreshUI();
})();
</script>
