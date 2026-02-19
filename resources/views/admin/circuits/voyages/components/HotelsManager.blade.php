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

    {{-- Actions : "+ Ajouter" si vide, "Choisir / Modifier" + "Retirer" si déjà un hôtel (jour imposé, pas de select Jour) --}}
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

    {{-- Formulaire complet (comme onglet Hôtels) : pas de champ Jour, enregistré pour le jour courant --}}
    <div id="hotels-manager-form-wrap" class="border rounded p-3 mb-3" style="display: none;">
        <p class="small text-muted mb-2" id="hotels-form-hint">Sera enregistré automatiquement pour le jour courant.</p>
        <div class="row g-2">
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="day-builder-hotel-is_optional" value="1">
                    <label class="form-check-label small">Option client</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label small">Nom de l'hôtel</label>
                <input type="text" class="form-control form-control-sm" id="day-builder-hotel-name" placeholder="Ex. Hôtel Les Almoravides">
            </div>
            <div class="col-6">
                <label class="form-label small">Étoiles (0–5)</label>
                <input type="number" class="form-control form-control-sm" id="day-builder-hotel-stars" min="0" max="5" placeholder="3">
            </div>
            <div class="col-6">
                <label class="form-label small">Type de chambre</label>
                <input type="text" class="form-control form-control-sm" id="day-builder-hotel-room" placeholder="Ex. Chambre double">
            </div>
            <div class="col-12">
                <label class="form-label small">Adresse</label>
                <input type="text" class="form-control form-control-sm" id="day-builder-hotel-address" placeholder="Ville, pays">
            </div>
            <div class="col-12">
                <label class="form-label small">Repas (formule)</label>
                <input type="text" class="form-control form-control-sm" id="day-builder-hotel-meal" placeholder="Ex. Petit-déjeuner inclus">
            </div>
            <div class="col-12">
                <label class="form-label small">Notes</label>
                <textarea class="form-control form-control-sm" id="day-builder-hotel-notes" rows="2" placeholder="Notes"></textarea>
            </div>
            <div class="col-12">
                <label class="form-label small">Image</label>
                <input type="hidden" id="day_builder_hotel_image" value="">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div id="day_builder_hotel_image_preview_wrap" class="border rounded overflow-hidden bg-light" style="width: 100px; height: 70px; display: none;">
                        <img id="day_builder_hotel_image_preview" src="" alt="" class="img-fluid" style="width:100%; height:100%; object-fit: cover;">
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary ajtb-logistique-media-btn" data-target="tour_hotel" data-input="day_builder_hotel_image" data-preview="day_builder_hotel_image_preview" data-preview-wrap="day_builder_hotel_image_preview_wrap"><i class="bx bx-images"></i> Choisir</button>
                        <button type="button" class="btn btn-sm btn-outline-danger ajtb-logistique-media-remove" data-input="day_builder_hotel_image" data-preview="day_builder_hotel_image_preview" data-preview-wrap="day_builder_hotel_image_preview_wrap">×</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm" id="hotels-manager-confirm-btn">
                <i class="bx bx-check"></i> Confirmer
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="hotels-manager-cancel-btn">Annuler</button>
        </div>
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
    var formWrap = document.getElementById('hotels-manager-form-wrap');
    var formHint = document.getElementById('hotels-form-hint');
    var confirmBtn = document.getElementById('hotels-manager-confirm-btn');
    var cancelBtn = document.getElementById('hotels-manager-cancel-btn');

    var formFields = {
        is_optional: document.getElementById('day-builder-hotel-is_optional'),
        hotel_name: document.getElementById('day-builder-hotel-name'),
        stars: document.getElementById('day-builder-hotel-stars'),
        room_type: document.getElementById('day-builder-hotel-room'),
        address: document.getElementById('day-builder-hotel-address'),
        meal_plan: document.getElementById('day-builder-hotel-meal'),
        notes: document.getElementById('day-builder-hotel-notes'),
        image_id: document.getElementById('day_builder_hotel_image')
    };

    function getDrawerDay() {
        var drawer = document.getElementById('day-builder-drawer');
        if (!drawer) return { index: '', number: 1 };
        return {
            index: drawer.getAttribute('data-day-index') || '',
            number: parseInt(drawer.getAttribute('data-day-number') || '1', 10) || 1
        };
    }

    function clearForm() {
        if (formFields.is_optional) formFields.is_optional.checked = false;
        if (formFields.hotel_name) formFields.hotel_name.value = '';
        if (formFields.stars) formFields.stars.value = '';
        if (formFields.room_type) formFields.room_type.value = '';
        if (formFields.address) formFields.address.value = '';
        if (formFields.meal_plan) formFields.meal_plan.value = '';
        if (formFields.notes) formFields.notes.value = '';
        if (formFields.image_id) formFields.image_id.value = '';
        var wrap = document.getElementById('day_builder_hotel_image_preview_wrap');
        var prev = document.getElementById('day_builder_hotel_image_preview');
        if (wrap) wrap.style.display = 'none';
        if (prev) prev.src = '';
    }

    function fillFormFromHotel(hotelId) {
        clearForm();
        var h = window.tourHotelsData && hotelId ? window.tourHotelsData[hotelId] : null;
        if (!h) return;
        if (formFields.is_optional) formFields.is_optional.checked = !!h.is_optional;
        if (formFields.hotel_name) formFields.hotel_name.value = h.hotel_name || '';
        if (formFields.stars) formFields.stars.value = h.stars !== undefined && h.stars !== null ? h.stars : '';
        if (formFields.room_type) formFields.room_type.value = h.room_type || '';
        if (formFields.address) formFields.address.value = h.address || '';
        if (formFields.meal_plan) formFields.meal_plan.value = h.meal_plan || '';
        if (formFields.notes) formFields.notes.value = h.notes || '';
        if (formFields.image_id && h.image_id) formFields.image_id.value = h.image_id;
        if (h.image_url) {
            var prev = document.getElementById('day_builder_hotel_image_preview');
            var wrap = document.getElementById('day_builder_hotel_image_preview_wrap');
            if (prev) prev.src = h.image_url;
            if (wrap) wrap.style.display = 'flex';
        }
    }

    function getFormData() {
        return {
            is_optional: formFields.is_optional ? formFields.is_optional.checked : false,
            hotel_name: formFields.hotel_name ? formFields.hotel_name.value.trim() : '',
            stars: formFields.stars ? formFields.stars.value : '',
            room_type: formFields.room_type ? formFields.room_type.value.trim() : '',
            address: formFields.address ? formFields.address.value.trim() : '',
            meal_plan: formFields.meal_plan ? formFields.meal_plan.value.trim() : '',
            notes: formFields.notes ? formFields.notes.value.trim() : '',
            image_id: formFields.image_id ? formFields.image_id.value : ''
        };
    }

    function refreshUI() {
        var day = getDrawerDay();
        currentDayIndex = day.index;
        if (titleEl) titleEl.textContent = 'Hôtels – Jour ' + day.number;
        if (descEl) descEl.textContent = 'Configurez l\'hôtel pour ce jour. Un seul hôtel par jour. Pas de champ "Jour" : le jour est imposé par le contexte.';
        if (addBtnLabel) addBtnLabel.textContent = '+ Ajouter un hôtel (Jour ' + day.number + ')';
        if (chooseBtnLabel) chooseBtnLabel.textContent = 'Choisir / Modifier l\'hôtel (Jour ' + day.number + ')';
        if (formHint) formHint.textContent = 'Sera enregistré automatiquement pour le Jour ' + day.number + '.';

        var hotelId = (window.dayItemsManager && day.index !== '') ? window.dayItemsManager.getHotel(day.index) : null;
        var isEmpty = !hotelId || !window.tourHotelsData || !window.tourHotelsData[hotelId];
        if (summaryEl) {
            summaryEl.innerHTML = '';
            if (isEmpty) {
                summaryEl.textContent = 'Aucun hôtel configuré';
            } else {
                var h = window.tourHotelsData[hotelId];
                var card = document.createElement('div');
                card.className = 'card mb-0 border';
                card.style.fontSize = '13px';
                var cardBody = document.createElement('div');
                cardBody.className = 'card-body p-2 d-flex justify-content-between align-items-start';
                var infoDiv = document.createElement('div');
                infoDiv.className = 'flex-grow-1';
                var titleDiv = document.createElement('div');
                titleDiv.className = 'fw-semibold mb-1';
                titleDiv.textContent = h.hotel_name || 'Hôtel';
                infoDiv.appendChild(titleDiv);
                var details = [];
                if (h.stars !== null && h.stars !== undefined) {
                    var starsText = '';
                    for (var i = 0; i < parseInt(h.stars, 10); i++) starsText += '★';
                    details.push(starsText || 'Non classé');
                }
                if (h.room_type) details.push('Chambre: ' + h.room_type);
                if (h.address) details.push(h.address);
                if (h.meal_plan) details.push('Repas: ' + h.meal_plan);
                if (h.is_optional) details.push('<span class="badge bg-warning">Option client</span>');
                if (details.length > 0) {
                    var detailsEl = document.createElement('div');
                    detailsEl.className = 'mt-1 text-muted';
                    detailsEl.style.fontSize = '11px';
                    detailsEl.innerHTML = details.join(' • ');
                    infoDiv.appendChild(detailsEl);
                }
                if (h.notes) {
                    var notesEl = document.createElement('div');
                    notesEl.className = 'mt-1 text-muted';
                    notesEl.style.fontSize = '11px';
                    notesEl.style.fontStyle = 'italic';
                    notesEl.textContent = h.notes.substring(0, 60) + (h.notes.length > 60 ? '...' : '');
                    infoDiv.appendChild(notesEl);
                }
                if (h.image_url) {
                    var imgDiv = document.createElement('div');
                    imgDiv.className = 'mt-2';
                    var img = document.createElement('img');
                    img.src = h.image_url;
                    img.style.maxWidth = '100px';
                    img.style.maxHeight = '60px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px';
                    img.className = 'border';
                    imgDiv.appendChild(img);
                    infoDiv.appendChild(imgDiv);
                }
                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger';
                removeBtn.innerHTML = '<i class="bx bx-trash"></i>';
                removeBtn.title = 'Retirer cet hôtel';
                removeBtn.addEventListener('click', function() {
                    if (confirm('Retirer cet hôtel du Jour ' + day.number + ' ?')) {
                        window.dayItemsManager.setHotel(day.index, null);
                        window.dayItemsManager.syncToForm(day.index);
                        document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: day.index } }));
                        refreshUI();
                    }
                });
                cardBody.appendChild(infoDiv);
                cardBody.appendChild(removeBtn);
                card.appendChild(cardBody);
                summaryEl.appendChild(card);
            }
        }
        if (addBtn) addBtn.classList.toggle('d-none', !isEmpty);
        if (chooseBtn) chooseBtn.classList.toggle('d-none', isEmpty);
        if (removeBtn) removeBtn.classList.toggle('d-none', isEmpty);
    }

    document.addEventListener('day-builder:context-changed', function(e) {
        var detail = e.detail || {};
        currentDayIndex = String(detail.dayIndex || '');
        if (window.dayItemsManager) window.dayItemsManager.loadFromForm(currentDayIndex);
        refreshUI();
        if (formWrap) formWrap.style.display = 'none';
    });

    function openForm(isNew) {
        var day = getDrawerDay();
        if (formHint) formHint.textContent = 'Sera enregistré automatiquement pour le Jour ' + day.number + '.';
        var hotelId = (window.dayItemsManager && day.index !== '') ? window.dayItemsManager.getHotel(day.index) : null;
        var existingRow = getTourHotelRowForDay(day.number);
        if (!isNew && (hotelId || existingRow)) {
            if (hotelId && window.tourHotelsData && window.tourHotelsData[hotelId]) {
                fillFormFromHotel(hotelId);
            } else if (existingRow) {
                var idx = existingRow.getAttribute('data-index');
                var optInp = existingRow.querySelector('input[name="tour_hotels[' + idx + '][is_optional]"]');
                if (formFields.is_optional) formFields.is_optional.checked = optInp ? !!optInp.checked : false;
                var nameInp = existingRow.querySelector('input[name="tour_hotels[' + idx + '][hotel_name]"]');
                if (formFields.hotel_name) formFields.hotel_name.value = nameInp ? nameInp.value : '';
                var starsInp = existingRow.querySelector('input[name="tour_hotels[' + idx + '][stars]"]');
                if (formFields.stars) formFields.stars.value = starsInp ? starsInp.value : '';
                var roomInp = existingRow.querySelector('input[name="tour_hotels[' + idx + '][room_type]"]');
                if (formFields.room_type) formFields.room_type.value = roomInp ? roomInp.value : '';
                var addrInp = existingRow.querySelector('input[name="tour_hotels[' + idx + '][address]"]');
                if (formFields.address) formFields.address.value = addrInp ? addrInp.value : '';
                var mealInp = existingRow.querySelector('input[name="tour_hotels[' + idx + '][meal_plan]"]');
                if (formFields.meal_plan) formFields.meal_plan.value = mealInp ? mealInp.value : '';
                var notesTa = existingRow.querySelector('textarea[name="tour_hotels[' + idx + '][notes]"]');
                if (formFields.notes) formFields.notes.value = notesTa ? notesTa.value : '';
                var imgInp = existingRow.querySelector('input[name^="tour_hotels["][name$="[image_id]"]');
                if (formFields.image_id && imgInp) formFields.image_id.value = imgInp.value || '';
            }
        } else {
            clearForm();
        }
        if (formWrap) formWrap.style.display = 'block';
    }

    function getTourHotelRowForDay(dayNumber) {
        var container = document.getElementById('tour-hotels-container');
        if (!container) return null;
        var rows = container.querySelectorAll('.tour-hotel-row');
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var sel = row.querySelector('select[name^="tour_hotels["][name$="[day_number]"]');
            if (sel && sel.value === String(dayNumber)) return row;
        }
        return null;
    }

    function setRowData(row, idx, dayNumber, data) {
        var daySel = row.querySelector('select[name="tour_hotels[' + idx + '][day_number]"]');
        if (daySel) daySel.value = String(dayNumber);
        var opt = row.querySelector('input[name="tour_hotels[' + idx + '][is_optional]"]');
        if (opt) opt.checked = !!data.is_optional;
        var nameInp = row.querySelector('input[name="tour_hotels[' + idx + '][hotel_name]"]');
        if (nameInp) nameInp.value = data.hotel_name || '';
        var starsInp = row.querySelector('input[name="tour_hotels[' + idx + '][stars]"]');
        if (starsInp) starsInp.value = data.stars || '';
        var roomInp = row.querySelector('input[name="tour_hotels[' + idx + '][room_type]"]');
        if (roomInp) roomInp.value = data.room_type || '';
        var addrInp = row.querySelector('input[name="tour_hotels[' + idx + '][address]"]');
        if (addrInp) addrInp.value = data.address || '';
        var mealInp = row.querySelector('input[name="tour_hotels[' + idx + '][meal_plan]"]');
        if (mealInp) mealInp.value = data.meal_plan || '';
        var notesInp = row.querySelector('textarea[name="tour_hotels[' + idx + '][notes]"]');
        if (notesInp) notesInp.value = data.notes || '';
        var imgInput = row.querySelector('input[name="tour_hotels[' + idx + '][image_id]"]');
        if (imgInput && data.image_id) imgInput.value = data.image_id;
    }

    function addTourHotelRowAndLinkDay(dayNumber, data) {
        var container = document.getElementById('tour-hotels-container');
        var addBtnPage = document.getElementById('tour-add-hotel');
        if (!container || !addBtnPage) return;
        addBtnPage.click();
        var rows = container.querySelectorAll('.tour-hotel-row');
        var newRow = rows[rows.length - 1];
        if (!newRow) return;
        var idx = newRow.getAttribute('data-index');
        setRowData(newRow, idx, dayNumber, data);
        // programme_days[X][hotel_id] reste vide : le backend liera au TourHotel créé pour ce jour
    }

    function updateTourHotelRowByHotelId(hotelId, data) {
        var container = document.getElementById('tour-hotels-container');
        if (!container) return;
        var row = container.querySelector('.tour-hotel-row[data-hotel-id="' + hotelId + '"]');
        if (!row) return;
        var idx = row.getAttribute('data-index');
        var dayNum = getDrawerDay().number;
        setRowData(row, idx, dayNum, data);
    }

    if (addBtn && formWrap) addBtn.addEventListener('click', function() { openForm(true); });
    if (chooseBtn && formWrap) chooseBtn.addEventListener('click', function() {
        if (formWrap.style.display === 'none') openForm(false);
        else formWrap.style.display = 'none';
    });

    if (cancelBtn && formWrap) cancelBtn.addEventListener('click', function() {
        formWrap.style.display = 'none';
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            if (!window.dayItemsManager || currentDayIndex === '') return;
            window.dayItemsManager.setHotel(currentDayIndex, null);
            window.dayItemsManager.syncToForm(currentDayIndex);
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: currentDayIndex } }));
            refreshUI();
            if (formWrap) formWrap.style.display = 'none';
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            var day = getDrawerDay();
            if (day.index === '') return;
            var data = getFormData();
            var hotelId = window.dayItemsManager ? window.dayItemsManager.getHotel(day.index) : null;
            var existingRow = getTourHotelRowForDay(day.number);
            if (hotelId && window.tourHotelsData && window.tourHotelsData[hotelId]) {
                updateTourHotelRowByHotelId(String(hotelId), data);
            } else if (existingRow) {
                var idx = existingRow.getAttribute('data-index');
                setRowData(existingRow, idx, day.number, data);
            } else {
                addTourHotelRowAndLinkDay(day.number, data);
            }
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: day.index } }));
            refreshUI();
            formWrap.style.display = 'none';
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
