<div id="day-builder-transfers-manager">
    <p class="text-muted small mb-3">Sélectionnez les transferts pour ce jour (0..n transferts par jour)</p>
    
    <div class="mb-3">
        <label class="form-label">Transferts (multi-sélection)</label>
        <div id="transfers-manager-list" class="border rounded p-2" style="max-height: 320px; overflow-y: auto;">
            <!-- Rempli dynamiquement -->
        </div>
    </div>

    <div id="transfers-manager-details" style="display: none;">
        <div class="card bg-light">
            <div class="card-body">
                <div id="transfers-manager-selected-list">
                    <!-- Affiche les transferts sélectionnés -->
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="programme_days[__DAY_INDEX__][transfer_ids]" value="">
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!window.tourTransfersData) {
        window.tourTransfersData = { arrival: [], departure: [] };
    }
});

// Appelé quand le drawer s'ouvre (depuis edit.blade.php)
document.addEventListener('day-builder:context-changed', function(e) {
    const detail = e.detail || {};
    const dayIndex = String(detail.dayIndex || '');
    
    const transfersInput = document.querySelector('input[name^="programme_days["][name$="[transfer_ids]"]');
    
    if (!transfersInput) return;
    
    // Mettre à jour le name de l'input
    transfersInput.name = 'programme_days[' + dayIndex + '][transfer_ids]';
    
    // Charger et afficher les transferts
    loadTransfersForManager();
    
    // Restaurer les sélections depuis programDayHotelsTransfers ou depuis l'input précédent
    let transferIdsToSelect = [];
    
    // D'abord chercher dans window.programDayHotelsTransfers avec la clé dayId (detail.dayId si disponible)
    if (window.programDayHotelsTransfers && detail.dayId) {
        const dayHotelsTransfers = window.programDayHotelsTransfers[String(detail.dayId)];
        if (dayHotelsTransfers && Array.isArray(dayHotelsTransfers.transfer_ids) && dayHotelsTransfers.transfer_ids.length > 0) {
            transferIdsToSelect = dayHotelsTransfers.transfer_ids.map(id => parseInt(id, 10));
        }
    }
    
    // Sinon utiliser la valeur de l'input
    if (transferIdsToSelect.length === 0 && transfersInput.value) {
        transferIdsToSelect = transfersInput.value.split(',').map(id => parseInt(id.trim(), 10)).filter(id => !isNaN(id) && id > 0);
    }
    
    updateTransfersSelection(transferIdsToSelect);
});

function loadTransfersForManager() {
    const list = document.getElementById('transfers-manager-list');
    if (!list || !window.tourTransfersData) return;
    
    list.innerHTML = '';
    
    // Ajouter les transferts d'arrivée
    if (window.tourTransfersData.arrival && window.tourTransfersData.arrival.length > 0) {
        const arrivalLabel = document.createElement('div');
        arrivalLabel.className = 'fw-bold text-success mb-2 mt-2';
        arrivalLabel.textContent = 'Arrivée :';
        list.appendChild(arrivalLabel);
        
        window.tourTransfersData.arrival.forEach(transfer => {
            const checkContainer = document.createElement('div');
            checkContainer.className = 'form-check mb-2';
            
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'form-check-input transfer-checkbox';
            input.value = transfer.id;
            input.id = 'transfer-' + transfer.id;
            input.dataset.direction = 'arrival';
            
            const label = document.createElement('label');
            label.className = 'form-check-label small';
            label.htmlFor = 'transfer-' + transfer.id;
            label.textContent = (transfer.from_label || '?') + ' → ' + (transfer.to_label || '?');
            
            checkContainer.appendChild(input);
            checkContainer.appendChild(label);
            list.appendChild(checkContainer);
        });
    }
    
    // Ajouter les transferts de départ
    if (window.tourTransfersData.departure && window.tourTransfersData.departure.length > 0) {
        const departureLabel = document.createElement('div');
        departureLabel.className = 'fw-bold text-danger mb-2 mt-2';
        departureLabel.textContent = 'Départ :';
        list.appendChild(departureLabel);
        
        window.tourTransfersData.departure.forEach(transfer => {
            const checkContainer = document.createElement('div');
            checkContainer.className = 'form-check mb-2';
            
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.className = 'form-check-input transfer-checkbox';
            input.value = transfer.id;
            input.id = 'transfer-' + transfer.id;
            input.dataset.direction = 'departure';
            
            const label = document.createElement('label');
            label.className = 'form-check-label small';
            label.htmlFor = 'transfer-' + transfer.id;
            label.textContent = (transfer.from_label || '?') + ' → ' + (transfer.to_label || '?');
            
            checkContainer.appendChild(input);
            checkContainer.appendChild(label);
            list.appendChild(checkContainer);
        });
    }
}

function updateTransfersSelection(selectedIds) {
    const transfersInput = document.querySelector('input[name^="programme_days["][name$="[transfer_ids]"]');
    const transferCheckboxes = document.querySelectorAll('.transfer-checkbox');
    
    if (!transfersInput) return;
    
    // Décocher tous les checkboxes d'abord
    transferCheckboxes.forEach(cb => cb.checked = false);
    
    // Cocher les sélectionnés
    selectedIds.forEach(id => {
        const cb = document.getElementById('transfer-' + id);
        if (cb) cb.checked = true;
    });
    
    updateTransfersInput();
    updateTransfersDetails();
}

function updateTransfersInput() {
    const transfersInput = document.querySelector('input[name^="programme_days["][name$="[transfer_ids]"]');
    const checked = document.querySelectorAll('.transfer-checkbox:checked');
    
    if (!transfersInput) return;
    
    const ids = Array.from(checked).map(cb => cb.value).join(',');
    transfersInput.value = ids;
}

function updateTransfersDetails() {
    const checked = document.querySelectorAll('.transfer-checkbox:checked');
    const detailsDiv = document.getElementById('transfers-manager-details');
    const selectedList = document.getElementById('transfers-manager-selected-list');
    
    if (!selectedList) return;
    
    if (checked.length === 0) {
        detailsDiv.style.display = 'none';
        return;
    }
    
    selectedList.innerHTML = '';
    let arrivalCount = 0, departureCount = 0;
    
    checked.forEach(cb => {
        const transferId = parseInt(cb.value, 10);
        const direction = cb.dataset.direction;
        const allTransfers = direction === 'arrival' ? window.tourTransfersData.arrival : window.tourTransfersData.departure;
        const transfer = allTransfers.find(t => t.id === transferId);
        
        if (!transfer) return;
        
        if (direction === 'arrival') arrivalCount++;
        else departureCount++;
        
        const row = document.createElement('div');
        row.className = 'mb-2 small';
        row.innerHTML = '<strong>' + transfer.from_label + ' → ' + transfer.to_label + '</strong><br>' +
            '<span class="text-muted">' + (transfer.pickup_time || '—') + ' → ' + (transfer.dropoff_time || '—') + '</span>';
        selectedList.appendChild(row);
    });
    
    detailsDiv.style.display = '';
}

// Listener sur les checkboxes de transferts
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('transfer-checkbox')) {
        updateTransfersInput();
        updateTransfersDetails();
    }
});
</script>
