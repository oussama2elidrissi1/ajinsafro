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
    <div class="alert alert-warning border-warning mb-3" role="alert">
        <h6 class="mb-2"><i class="bx bx-error me-1"></i>Section en cours de construction �?" ne pas modifier</h6>
        <p class="mb-1">Cette section n�?Test pas encore finalisée et ses champs ne sont pas pris en charge par la logique actuelle (enregistrement, validation, affichage).</p>
        <p class="mb-0">Merci de ne rien modifier ici pour le moment afin d�?Téviter incohérences, erreurs de sauvegarde ou comportements inattendus. Cette partie sera activée dès qu�?Telle sera prête.</p>
    </div>

    {{-- Bloc config du jour (même pattern que Vols) --}}
    <div class="day-builder-context">
        <div class="d-flex align-items-start gap-2">
            <i class="bx bx-car text-primary mt-1"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold text-primary" id="transfers-context-title">Transferts �?" Jour 1</div>
                <div class="small text-muted" id="transfers-context-description">Configurez les transferts (arrivée / départ) pour ce jour.</div>
            </div>
        </div>
    </div>

    {{-- �?tat / résumé --}}
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
                        <label class="form-label small">�?</label>
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
    var TRANSFERS_MAINTENANCE_MSG = 'Le module Transferts est temporairement en maintenance. Merci de réessayer dans quelques instants.';

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
        var drawer = document.getElementById('day-builder-root');
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
                var day = getDrawerDay();
                var isSelected = (window.dayItemsManager && day.index !== '' && window.dayItemsManager.getTransfers(day.index).indexOf(transfer.id) !== -1);
                var card = document.createElement('div');
                card.className = 'card mb-2';
                if (isSelected) {
                    card.style.borderColor = '#0d6efd';
                    card.style.backgroundColor = '#f0f7ff';
                }
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
                input.checked = isSelected;
                var labelDiv = document.createElement('div');
                labelDiv.className = 'flex-grow-1 small';
                var mainLabel = document.createElement('label');
                mainLabel.className = 'form-check-label fw-medium d-block';
                mainLabel.htmlFor = 'transfer-' + transfer.id;
                mainLabel.textContent = (transfer.from_label || '?') + ' �?' ' + (transfer.to_label || '?');
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
                    detailsEl.textContent = details.join(' �?� ');
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
                var day = getDrawerDay();
                var isSelected = (window.dayItemsManager && day.index !== '' && window.dayItemsManager.getTransfers(day.index).indexOf(transfer.id) !== -1);
                var card = document.createElement('div');
                card.className = 'card mb-2';
                if (isSelected) {
                    card.style.borderColor = '#0d6efd';
                    card.style.backgroundColor = '#f0f7ff';
                }
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
                input.checked = isSelected;
                var labelDiv = document.createElement('div');
                labelDiv.className = 'flex-grow-1 small';
                var mainLabel = document.createElement('label');
                mainLabel.className = 'form-check-label fw-medium d-block';
                mainLabel.htmlFor = 'transfer-' + transfer.id;
                mainLabel.textContent = (transfer.from_label || '?') + ' �?' ' + (transfer.to_label || '?');
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
                    detailsEl.textContent = details.join(' �?� ');
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

    function getTransfersForDay(dayIndex, dayNumber) {
        var transferIds = [];
        var transferData = [];
        
        // 1. Chercher dans dayItemsManager
        if (window.dayItemsManager && dayIndex !== '') {
            var ids = window.dayItemsManager.getTransfers(dayIndex);
            if (ids && ids.length > 0) {
                ids.forEach(function(id) {
                    if (window.tourTransfersData) {
                        var t = window.tourTransfersData.arrival.find(function(x) { return x.id === id; }) ||
                                window.tourTransfersData.departure.find(function(x) { return x.id === id; });
                        if (t) {
                            transferIds.push(id);
                            transferData.push(t);
                        }
                    }
                });
            }
        }
        
        // 2. Chercher aussi dans les lignes du formulaire principal (nouveau format unifié : tour_transfers)
        document.querySelectorAll('.tour-transfer-row').forEach(function(row) {
            var daySel = row.querySelector('select[name*="[day_number]"]');
            if (daySel && parseInt(daySel.value || '0', 10) === dayNumber) {
                var fromInp = row.querySelector('input[name*="[from_label]"]');
                var toInp = row.querySelector('input[name*="[to_label]"]');
                if (fromInp && toInp && (fromInp.value.trim() || toInp.value.trim())) {
                    var rowId = row.getAttribute('data-index');
                    var rowTransferId = row.getAttribute('data-transfer-id'); // Si existe
                    var existingId = null;
                    
                    // Vérifier si ce transfert a déjà un ID dans tourTransfersData
                    if (window.tourTransfersData && rowTransferId) {
                        var allTransfers = (window.tourTransfersData.arrival || []).concat(window.tourTransfersData.departure || []);
                        var found = allTransfers.find(function(t) {
                            return t.id == rowTransferId;
                        });
                        if (found) {
                            existingId = found.id;
                            // Si pas déjà dans la liste, l'ajouter
                            if (transferIds.indexOf(existingId) === -1) {
                                transferIds.push(existingId);
                                transferData.push(found);
                            }
                        }
                    }
                    
                    // Si pas déjà compté via dayItemsManager
                    if (!existingId && (!rowTransferId || transferIds.indexOf(parseInt(rowTransferId, 10)) === -1)) {
                        var vehicleInp = row.querySelector('input[name*="[vehicle_type]"]');
                        var pickupInp = row.querySelector('input[name*="[pickup_time]"]');
                        var dropoffInp = row.querySelector('input[name*="[dropoff_time]"]');
                        var notesTa = row.querySelector('textarea[name*="[notes]"]');
                        // Par défaut, on utilise 'arrival' comme direction (compatibilité avec le modèle)
                        var transfer = {
                            id: rowTransferId ? parseInt(rowTransferId, 10) : null,
                            direction: 'arrival', // Par défaut pour compatibilité
                            from_label: fromInp.value.trim() || '',
                            to_label: toInp.value.trim() || '',
                            vehicle_type: vehicleInp ? vehicleInp.value.trim() : '',
                            pickup_time: pickupInp ? pickupInp.value.trim() : '',
                            dropoff_time: dropoffInp ? dropoffInp.value.trim() : '',
                            notes: notesTa ? notesTa.value.trim() : '',
                            source: 'formRow'
                        };
                        transferData.push(transfer);
                        // Ne pas ajouter à transferIds si pas d'ID réel
                        if (rowTransferId) {
                            transferIds.push(parseInt(rowTransferId, 10));
                        }
                    }
                }
            }
        });
        // Compatibilité ancien format : tour-transfer-arrival-row / tour-transfer-departure-row
        document.querySelectorAll('.tour-transfer-arrival-row, .tour-transfer-departure-row').forEach(function(row) {
            var daySel = row.querySelector('select[name*="[day_number]"]');
            if (daySel && parseInt(daySel.value || '0', 10) === dayNumber) {
                var fromInp = row.querySelector('input[name*="[from_label]"]');
                var toInp = row.querySelector('input[name*="[to_label]"]');
                if (fromInp && toInp && (fromInp.value.trim() || toInp.value.trim())) {
                    var rowId = row.getAttribute('data-index');
                    var rowTransferId = row.getAttribute('data-transfer-id');
                    var existingId = null;
                    
                    if (window.tourTransfersData && rowTransferId) {
                        var allTransfers = (window.tourTransfersData.arrival || []).concat(window.tourTransfersData.departure || []);
                        var found = allTransfers.find(function(t) {
                            return t.id == rowTransferId;
                        });
                        if (found && transferIds.indexOf(found.id) === -1) {
                            transferIds.push(found.id);
                            transferData.push(found);
                            existingId = found.id;
                        }
                    }
                    
                    if (!existingId) {
                        var direction = row.classList.contains('tour-transfer-arrival-row') ? 'arrival' : 'departure';
                        var vehicleInp = row.querySelector('input[name*="[vehicle_type]"]');
                        var pickupInp = row.querySelector('input[name*="[pickup_time]"]');
                        var dropoffInp = row.querySelector('input[name*="[dropoff_time]"]');
                        var notesTa = row.querySelector('textarea[name*="[notes]"]');
                        var transfer = {
                            id: rowTransferId ? parseInt(rowTransferId, 10) : null,
                            direction: direction,
                            from_label: fromInp.value.trim() || '',
                            to_label: toInp.value.trim() || '',
                            vehicle_type: vehicleInp ? vehicleInp.value.trim() : '',
                            pickup_time: pickupInp ? pickupInp.value.trim() : '',
                            dropoff_time: dropoffInp ? dropoffInp.value.trim() : '',
                            notes: notesTa ? notesTa.value.trim() : '',
                            source: 'formRow'
                        };
                        transferData.push(transfer);
                        if (rowTransferId) {
                            transferIds.push(parseInt(rowTransferId, 10));
                        }
                    }
                }
            }
        });
        
        return { ids: transferIds, data: transferData };
    }

    function refreshUI() {
        var day = getDrawerDay();
        currentDayIndex = day.index;
        if (titleEl) titleEl.textContent = 'Transferts �?" Jour ' + day.number;
        if (descEl) descEl.textContent = 'Configurez les transferts (arrivée / départ) pour ce jour. Pas de champ "Jour" : le jour est imposé par le contexte.';
        if (addBtnLabel) addBtnLabel.textContent = '+ Ajouter des transferts (Jour ' + day.number + ')';
        if (chooseBtnLabel) chooseBtnLabel.textContent = 'Configurer les transferts (Jour ' + day.number + ')';
        if (pickerHint) pickerHint.textContent = 'Sera enregistré automatiquement pour le Jour ' + day.number + '.';

        var transfersInfo = getTransfersForDay(day.index, day.number);
        var ids = transfersInfo.ids;
        var transferData = transfersInfo.data;
        var count = transferData.length;
        var isEmpty = count === 0;
        if (summaryEl) {
            summaryEl.innerHTML = '';
            if (count === 0) {
                summaryEl.textContent = '0 transfert configuré';
            } else {
                var titleDiv = document.createElement('div');
                titleDiv.className = 'fw-semibold mb-2';
                titleDiv.textContent = count + ' transfert' + (count > 1 ? 's' : '') + ' configuré' + (count > 1 ? 's' : '');
                summaryEl.appendChild(titleDiv);
                
                transferData.forEach(function(t) {
                    var transferId = t.id;
                    var card = document.createElement('div');
                    card.className = 'card mb-2 border';
                    card.style.fontSize = '13px';
                    var cardBody = document.createElement('div');
                    cardBody.className = 'card-body p-2 d-flex justify-content-between align-items-start';
                    var infoDiv = document.createElement('div');
                    infoDiv.className = 'flex-grow-1';
                    var mainLabel = document.createElement('div');
                    mainLabel.className = 'fw-medium';
                    mainLabel.textContent = (t.from_label || '?') + ' �?' ' + (t.to_label || '?');
                    infoDiv.appendChild(mainLabel);
                    var details = [];
                    if (t.direction === 'arrival') details.push('<span class="badge bg-success">Arrivée</span>');
                    else details.push('<span class="badge bg-danger">Départ</span>');
                    if (t.vehicle_type) details.push('Véhicule: ' + t.vehicle_type);
                    if (t.pickup_time) details.push('Prise: ' + t.pickup_time);
                    if (t.dropoff_time) details.push('Arrivée: ' + t.dropoff_time);
                    if (details.length > 0) {
                        var detailsEl = document.createElement('div');
                        detailsEl.className = 'mt-1 text-muted';
                        detailsEl.style.fontSize = '11px';
                        detailsEl.innerHTML = details.join(' �?� ');
                        infoDiv.appendChild(detailsEl);
                    }
                    if (t.notes) {
                        var notesEl = document.createElement('div');
                        notesEl.className = 'mt-1 text-muted';
                        notesEl.style.fontSize = '11px';
                        notesEl.style.fontStyle = 'italic';
                        notesEl.textContent = t.notes.substring(0, 50) + (t.notes.length > 50 ? '...' : '');
                        infoDiv.appendChild(notesEl);
                    }
                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn-sm btn-outline-danger';
                    removeBtn.innerHTML = '<i class="bx bx-trash"></i>';
                    removeBtn.title = 'Retirer ce transfert';
                    removeBtn.addEventListener('click', function() {
                        if (confirm('Retirer ce transfert du Jour ' + day.number + ' ?')) {
                            if (transferId && window.dayItemsManager) {
                                var currentIds = window.dayItemsManager.getTransfers(day.index);
                                var newIds = currentIds.filter(function(x) { return x !== transferId; });
                                window.dayItemsManager.setTransfers(day.index, newIds);
                                window.dayItemsManager.syncToForm(day.index);
                            }
                            // Retirer aussi la ligne du formulaire principal si elle existe
                            if (t.source === 'formRow') {
                                // Nouveau format unifié
                                document.querySelectorAll('.tour-transfer-row').forEach(function(row) {
                                    var daySel = row.querySelector('select[name*="[day_number]"]');
                                    var rowFrom = row.querySelector('input[name*="[from_label]"]');
                                    var rowTo = row.querySelector('input[name*="[to_label]"]');
                                    var rowTransferId = row.getAttribute('data-transfer-id');
                                    if (daySel && parseInt(daySel.value || '0', 10) === day.number &&
                                        ((rowTransferId && rowTransferId == transferId) ||
                                         (rowFrom && rowFrom.value.trim() === t.from_label &&
                                          rowTo && rowTo.value.trim() === t.to_label))) {
                                        var removeBtnRow = row.querySelector('.tour-remove-transfer');
                                        if (removeBtnRow) removeBtnRow.click();
                                    }
                                });
                                // Compatibilité ancien format
                                document.querySelectorAll('.tour-transfer-arrival-row, .tour-transfer-departure-row').forEach(function(row) {
                                    var daySel = row.querySelector('select[name*="[day_number]"]');
                                    var rowFrom = row.querySelector('input[name*="[from_label]"]');
                                    var rowTo = row.querySelector('input[name*="[to_label]"]');
                                    if (daySel && parseInt(daySel.value || '0', 10) === day.number &&
                                        rowFrom && rowFrom.value.trim() === t.from_label &&
                                        rowTo && rowTo.value.trim() === t.to_label) {
                                        var removeBtnRow = row.querySelector('.tour-remove-transfer-arrival, .tour-remove-transfer-departure');
                                        if (removeBtnRow) removeBtnRow.click();
                                    }
                                });
                            }
                            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: day.index } }));
                            refreshUI();
                        }
                    });
                    cardBody.appendChild(infoDiv);
                    cardBody.appendChild(removeBtn);
                    card.appendChild(cardBody);
                    summaryEl.appendChild(card);
                });
            }
        }
        if (addBtn) addBtn.classList.toggle('d-none', !isEmpty);
        if (chooseBtn) chooseBtn.classList.toggle('d-none', isEmpty);
        if (removeAllBtn) removeAllBtn.classList.toggle('d-none', isEmpty);
        
        // Charger la liste des transferts et pré-cocher ceux qui sont sélectionnés
        loadTransfersList();
        if (day.index !== '' && window.dayItemsManager) {
            // Utiliser les IDs réels depuis dayItemsManager pour pré-cocher
            var realIds = window.dayItemsManager.getTransfers(day.index);
            setCheckboxesFromIds(realIds);
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
                        if (t) lines.push((t.from_label || '?') + ' �?' ' + (t.to_label || '?'));
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
        // Si le picker est ouvert, pré-cocher les transferts existants
        if (picker && picker.style.display !== 'none') {
            var day = getDrawerDay();
            if (day.index !== '' && window.dayItemsManager) {
                var realIds = window.dayItemsManager.getTransfers(day.index);
                setCheckboxesFromIds(realIds);
            }
        }
        if (newFormWrap) newFormWrap.style.display = 'none';
    });

    function openPicker() {
        var day = getDrawerDay();
        loadTransfersList();
        // Pré-cocher les transferts existants (depuis dayItemsManager ET formulaire principal)
        if (day.index !== '' && window.dayItemsManager) {
            var transfersInfo = getTransfersForDay(day.index, day.number);
            // Utiliser les IDs réels pour pré-cocher
            setCheckboxesFromIds(transfersInfo.ids);
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
        // Vérifier s'il y a des transferts (depuis dayItemsManager OU formulaire principal)
        var transfersInfo = getTransfersForDay(day.index, day.number);
        var hasTransfers = transfersInfo.data.length > 0;
        if (hasTransfers) openPicker(); // Ouvrir le picker avec les transferts pré-cochés
        else openNewForm(); // Ouvrir le formulaire de création
    });
    if (chooseBtn && picker) chooseBtn.addEventListener('click', function() {
        if (picker.style.display === 'none') {
            openPicker(); // Ouvre le picker avec les transferts pré-cochés
        } else {
            picker.style.display = 'none';
        }
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
            if (!tourId) { if (newFormError) { newFormError.textContent = TRANSFERS_MAINTENANCE_MSG; newFormError.style.display = 'block'; } return; }
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
                var msg = (err && err.message) || TRANSFERS_MAINTENANCE_MSG;
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
            var day = getDrawerDay();
            if (!window.dayItemsManager || day.index === '') return;
            if (!confirm('Retirer tous les transferts du Jour ' + day.number + ' ?')) return;
            
            // Retirer de dayItemsManager
            window.dayItemsManager.setTransfers(day.index, []);
            window.dayItemsManager.syncToForm(day.index);
            
            // Retirer aussi les lignes du formulaire principal pour ce jour
            // Nouveau format unifié
            document.querySelectorAll('.tour-transfer-row').forEach(function(row) {
                var daySel = row.querySelector('select[name*="[day_number]"]');
                if (daySel && parseInt(daySel.value || '0', 10) === day.number) {
                    var removeBtnRow = row.querySelector('.tour-remove-transfer');
                    if (removeBtnRow) {
                        removeBtnRow.click();
                    }
                }
            });
            // Compatibilité ancien format
            document.querySelectorAll('.tour-transfer-arrival-row, .tour-transfer-departure-row').forEach(function(row) {
                var daySel = row.querySelector('select[name*="[day_number]"]');
                if (daySel && parseInt(daySel.value || '0', 10) === day.number) {
                    var removeBtnRow = row.querySelector('.tour-remove-transfer-arrival, .tour-remove-transfer-departure');
                    if (removeBtnRow) {
                        removeBtnRow.click();
                    }
                }
            });
            
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: day.index } }));
            refreshUI();
            if (picker) picker.style.display = 'none';
        });
    }

    // Mettre à jour visuellement les cartes quand les checkboxes changent
    if (listEl) {
        listEl.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('transfer-checkbox')) {
                var card = e.target.closest('.card');
                if (card) {
                    if (e.target.checked) {
                        card.style.borderColor = '#0d6efd';
                        card.style.backgroundColor = '#f0f7ff';
                    } else {
                        card.style.borderColor = '';
                        card.style.backgroundColor = '';
                    }
                }
            }
        });
    }

    if (confirmBtn && listEl) {
        confirmBtn.addEventListener('click', function() {
            var day = getDrawerDay();
            if (!window.dayItemsManager || day.index === '') return;
            var ids = getCheckedTransferIds();
            window.dayItemsManager.setTransfers(day.index, ids);
            window.dayItemsManager.syncToForm(day.index);
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: day.index } }));
            refreshUI();
            if (picker) picker.style.display = 'none';
        });
    }

    if (newFormSubmit && newFormEl) {
        newFormSubmit.addEventListener('click', function() {
            var form = document.getElementById('edit-voyage-form');
            var tourId = form ? parseInt(form.getAttribute('data-voyage-id') || '0', 10) : 0;
            if (!tourId) { if (newFormError) { newFormError.textContent = TRANSFERS_MAINTENANCE_MSG; newFormError.style.display = 'block'; } return; }
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
                var msg = (err && err.message) || TRANSFERS_MAINTENANCE_MSG;
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

