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

    {{-- Actions --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-sm btn-outline-primary" id="transfers-manager-choose-btn">
            <i class="bx bx-edit-alt"></i> <span id="transfers-choose-btn-label">Configurer les transferts (Jour 1)</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger d-none" id="transfers-manager-remove-all-btn">
            <i class="bx bx-trash"></i> Tout retirer
        </button>
    </div>

    {{-- Picker (caché par défaut, affiché au clic "Configurer") --}}
    <div id="transfers-manager-picker" class="border rounded p-3 mb-3" style="display: none;">
        <label class="form-label small">Transferts (multi-sélection)</label>
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

    var titleEl = document.getElementById('transfers-context-title');
    var descEl = document.getElementById('transfers-context-description');
    var summaryEl = document.getElementById('transfers-summary-text');
    var chooseBtnLabel = document.getElementById('transfers-choose-btn-label');
    var chooseBtn = document.getElementById('transfers-manager-choose-btn');
    var removeAllBtn = document.getElementById('transfers-manager-remove-all-btn');
    var picker = document.getElementById('transfers-manager-picker');
    var listEl = document.getElementById('transfers-manager-list');
    var confirmBtn = document.getElementById('transfers-manager-confirm-btn');

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
        if (window.tourTransfersData.arrival && window.tourTransfersData.arrival.length > 0) {
            var arrivalLabel = document.createElement('div');
            arrivalLabel.className = 'fw-bold text-success small mb-2 mt-2';
            arrivalLabel.textContent = 'Arrivée :';
            listEl.appendChild(arrivalLabel);
            window.tourTransfersData.arrival.forEach(function(transfer) {
                var wrap = document.createElement('div');
                wrap.className = 'form-check mb-2';
                var input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'form-check-input transfer-checkbox';
                input.value = transfer.id;
                input.id = 'transfer-' + transfer.id;
                input.dataset.direction = 'arrival';
                var label = document.createElement('label');
                label.className = 'form-check-label small';
                label.htmlFor = 'transfer-' + transfer.id;
                label.textContent = (transfer.from_label || '?') + ' → ' + (transfer.to_label || '?');
                wrap.appendChild(input);
                wrap.appendChild(label);
                listEl.appendChild(wrap);
            });
        }
        if (window.tourTransfersData.departure && window.tourTransfersData.departure.length > 0) {
            var depLabel = document.createElement('div');
            depLabel.className = 'fw-bold text-danger small mb-2 mt-2';
            depLabel.textContent = 'Départ :';
            listEl.appendChild(depLabel);
            window.tourTransfersData.departure.forEach(function(transfer) {
                var wrap = document.createElement('div');
                wrap.className = 'form-check mb-2';
                var input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'form-check-input transfer-checkbox';
                input.value = transfer.id;
                input.id = 'transfer-' + transfer.id;
                input.dataset.direction = 'departure';
                var label = document.createElement('label');
                label.className = 'form-check-label small';
                label.htmlFor = 'transfer-' + transfer.id;
                label.textContent = (transfer.from_label || '?') + ' → ' + (transfer.to_label || '?');
                wrap.appendChild(input);
                wrap.appendChild(label);
                listEl.appendChild(wrap);
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
        if (titleEl) titleEl.textContent = 'Transferts – Jour ' + day.number;
        if (descEl) descEl.textContent = 'Configurez les transferts (arrivée / départ) pour ce jour.';
        if (chooseBtnLabel) chooseBtnLabel.textContent = 'Configurer les transferts (Jour ' + day.number + ')';

        var ids = (window.dayItemsManager && day.index !== '') ? window.dayItemsManager.getTransfers(day.index) : [];
        var count = ids.length;
        if (summaryEl) {
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
                        var div = document.createElement('div');
                        div.className = 'mt-2 text-muted';
                        div.innerHTML = lines.map(function(l) { return '<div class="small">' + l + '</div>'; }).join('');
                        summaryEl.appendChild(div);
                    }
                }
            }
        }
        if (removeAllBtn) removeAllBtn.classList.toggle('d-none', count === 0);
        loadTransfersList();
        if (day.index !== '' && window.dayItemsManager) {
            setCheckboxesFromIds(window.dayItemsManager.getTransfers(day.index));
        }
    }

    function refreshSummaryOnly() {
        var day = getDrawerDay();
        var ids = (window.dayItemsManager && day.index !== '') ? window.dayItemsManager.getTransfers(day.index) : [];
        var count = ids.length;
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
        if (removeAllBtn) removeAllBtn.classList.toggle('d-none', count === 0);
    }

    document.addEventListener('day-builder:context-changed', function(e) {
        var detail = e.detail || {};
        var dayIndex = String(detail.dayIndex || '');
        if (window.dayItemsManager) window.dayItemsManager.loadFromForm(dayIndex);
        refreshUI();
        if (picker) picker.style.display = 'none';
    });

    if (chooseBtn && picker) {
        chooseBtn.addEventListener('click', function() {
            var day = getDrawerDay();
            loadTransfersList();
            if (day.index !== '' && window.dayItemsManager) {
                setCheckboxesFromIds(window.dayItemsManager.getTransfers(day.index));
            }
            picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
        });
    }

    if (removeAllBtn) {
        removeAllBtn.addEventListener('click', function() {
            var day = getDrawerDay();
            if (!window.dayItemsManager || day.index === '') return;
            window.dayItemsManager.setTransfers(day.index, []);
            window.dayItemsManager.syncToForm(day.index);
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: day.index } }));
            refreshSummaryOnly();
            if (picker) picker.style.display = 'none';
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
            refreshSummaryOnly();
            picker.style.display = 'none';
            var drawer = document.getElementById('day-builder-drawer');
            if (drawer && typeof bootstrap !== 'undefined') {
                var offcanvas = bootstrap.Offcanvas.getInstance(drawer);
                if (offcanvas) offcanvas.hide();
            }
        });
    }

    refreshUI();
})();
</script>
