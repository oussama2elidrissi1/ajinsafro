<style>
#day-builder-hotels-manager .day-builder-context {
    background: #e7f1ff;
    border: 1px solid #b6d7ff;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 16px;
}
#day-builder-hotels-manager .day-builder-summary-block {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 12px;
}
</style>
<div id="day-builder-hotels-manager">
    {{-- Bloc config du jour (même pattern que Vols) --}}
    <div class="day-builder-context">
        <div class="d-flex align-items-start gap-2">
            <i class="bx bx-hotel text-primary mt-1"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold text-primary" id="hotels-context-title">Hôtels – Jour 1</div>
                <div class="small text-muted" id="hotels-context-description">Configurez l'hôtel pour ce jour. Un seul hôtel par jour.</div>
            </div>
        </div>
    </div>

    {{-- État / résumé --}}
    <div class="day-builder-summary-block">
        <div id="hotels-summary-text" class="small">Aucun hôtel configuré</div>
    </div>

    {{-- Actions : "+ Ajouter" si vide, "Choisir / Modifier" + "Retirer" si déjà un hôtel (jour imposé par le contexte, pas de select Jour) --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-sm btn-primary d-none" id="hotels-manager-add-btn">
            <i class="bx bx-plus"></i> <span id="hotels-add-btn-label">+ Ajouter un hôtel (Jour 1)</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary d-none" id="hotels-manager-choose-btn">
            <i class="bx bx-edit-alt"></i> <span id="hotels-choose-btn-label">Choisir / Modifier l'hôtel (Jour 1)</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger d-none" id="hotels-manager-remove-btn">
            <i class="bx bx-trash"></i> Retirer l'hôtel
        </button>
    </div>

    {{-- Picker : aucun champ "Jour", enregistré automatiquement pour le jour courant --}}
    <div id="hotels-manager-picker" class="border rounded p-3 mb-3" style="display: none;">
        <p class="small text-muted mb-2" id="hotels-picker-hint">Sera enregistré pour le jour courant.</p>
        <label for="hotels-manager-select" class="form-label small">Hôtel</label>
        <select id="hotels-manager-select" class="form-select form-select-sm">
            <option value="">— Aucun hôtel</option>
        </select>
        <button type="button" class="btn btn-primary btn-sm mt-2" id="hotels-manager-confirm-btn">
            <i class="bx bx-check"></i> Confirmer
        </button>
    </div>
</div>

<script>
(function() {
    if (!window.tourHotelsData) window.tourHotelsData = {};

    var currentDayIndex = '';
    var titleEl = document.getElementById('hotels-context-title');
    var descEl = document.getElementById('hotels-context-description');
    var summaryEl = document.getElementById('hotels-summary-text');
    var addBtn = document.getElementById('hotels-manager-add-btn');
    var addBtnLabel = document.getElementById('hotels-add-btn-label');
    var chooseBtnLabel = document.getElementById('hotels-choose-btn-label');
    var chooseBtn = document.getElementById('hotels-manager-choose-btn');
    var removeBtn = document.getElementById('hotels-manager-remove-btn');
    var picker = document.getElementById('hotels-manager-picker');
    var pickerHint = document.getElementById('hotels-picker-hint');
    var select = document.getElementById('hotels-manager-select');
    var confirmBtn = document.getElementById('hotels-manager-confirm-btn');

    function getDrawerDay() {
        var drawer = document.getElementById('day-builder-drawer');
        if (!drawer) return { index: '', number: 1 };
        return {
            index: drawer.getAttribute('data-day-index') || '',
            number: parseInt(drawer.getAttribute('data-day-number') || '1', 10) || 1
        };
    }

    function refreshUI() {
        var day = getDrawerDay();
        currentDayIndex = day.index;
        if (titleEl) titleEl.textContent = 'Hôtels – Jour ' + day.number;
        if (descEl) descEl.textContent = 'Configurez l\'hôtel pour ce jour. Un seul hôtel par jour. Pas de champ "Jour" : le jour est imposé par le contexte.';
        if (addBtnLabel) addBtnLabel.textContent = '+ Ajouter un hôtel (Jour ' + day.number + ')';
        if (chooseBtnLabel) chooseBtnLabel.textContent = 'Choisir / Modifier l\'hôtel (Jour ' + day.number + ')';
        if (pickerHint) pickerHint.textContent = 'Sera enregistré automatiquement pour le Jour ' + day.number + '.';

        var hotelId = (window.dayItemsManager && day.index !== '') ? window.dayItemsManager.getHotel(day.index) : null;
        var isEmpty = !hotelId || !window.tourHotelsData || !window.tourHotelsData[hotelId];
        if (summaryEl) {
            if (isEmpty) {
                summaryEl.textContent = 'Aucun hôtel configuré';
            } else {
                summaryEl.textContent = 'Hôtel sélectionné : ' + (window.tourHotelsData[hotelId].hotel_name || '—');
            }
        }
        if (addBtn) addBtn.classList.toggle('d-none', !isEmpty);
        if (chooseBtn) chooseBtn.classList.toggle('d-none', isEmpty);
        if (removeBtn) removeBtn.classList.toggle('d-none', isEmpty);
        if (select && window.tourHotelsData) {
            while (select.options.length > 1) select.remove(1);
            Object.values(window.tourHotelsData).forEach(function(h) {
                var opt = document.createElement('option');
                opt.value = h.id;
                opt.textContent = h.hotel_name || 'Hôtel (ID: ' + h.id + ')';
                select.appendChild(opt);
            });
            if (hotelId) select.value = hotelId;
        }
    }

    document.addEventListener('day-builder:context-changed', function(e) {
        var detail = e.detail || {};
        currentDayIndex = String(detail.dayIndex || '');
        if (window.dayItemsManager) window.dayItemsManager.loadFromForm(currentDayIndex);
        refreshUI();
        if (picker) picker.style.display = 'none';
    });

    function openPicker() {
        if (picker) picker.style.display = 'block';
    }
    if (addBtn && picker) addBtn.addEventListener('click', openPicker);
    if (chooseBtn && picker) chooseBtn.addEventListener('click', function() {
        picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            if (!window.dayItemsManager || currentDayIndex === '') return;
            window.dayItemsManager.setHotel(currentDayIndex, null);
            window.dayItemsManager.syncToForm(currentDayIndex);
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: currentDayIndex } }));
            refreshUI();
            if (picker) picker.style.display = 'none';
        });
    }

    if (confirmBtn && select) {
        confirmBtn.addEventListener('click', function() {
            if (!window.dayItemsManager || currentDayIndex === '') return;
            var hotelId = select.value ? parseInt(select.value, 10) : null;
            window.dayItemsManager.setHotel(currentDayIndex, hotelId);
            window.dayItemsManager.syncToForm(currentDayIndex);
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: currentDayIndex } }));
            refreshUI();
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
