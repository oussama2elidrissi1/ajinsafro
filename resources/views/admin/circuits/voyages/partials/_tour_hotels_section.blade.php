@php
    $lastDayNumber = isset($lastDayNumber) ? $lastDayNumber : (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1)));
    $hotelsList = $tourHotels->isEmpty() ? [null] : $tourHotels->all();
@endphp
<div id="tour-hotels-wrapper">
    <div id="tour-hotels-container">
        @foreach($hotelsList as $hi => $h)
        @php $hid = 'tour_hotel_image_id_' . $hi; $himg = optional($h)->image_id; $himgUrl = $himg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int)$himg) : ''; @endphp
        <div class="card mb-3 tour-hotel-row" data-index="{{ $hi }}" data-hotel-id="{{ optional($h)->id ?? '' }}">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <strong>Hôtel {{ $hi + 1 }}</strong>
                @if($hi > 0)<button type="button" class="btn btn-sm btn-outline-danger tour-remove-row" data-target=".tour-hotel-row" aria-label="Supprimer">×</button>@endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @php
                        // Compatibilité avec anciennes données : si day_number existe et check_in/out vides, utiliser day_number pour les deux
                        $oldDayNumber = old("tour_hotels.{$hi}.day_number", optional($h)->day_number);
                        $checkInDay = old("tour_hotels.{$hi}.check_in_day", optional($h)->check_in_day);
                        $checkOutDay = old("tour_hotels.{$hi}.check_out_day", optional($h)->check_out_day);
                        if (!$checkInDay && $oldDayNumber) {
                            $checkInDay = $oldDayNumber;
                        }
                        if (!$checkOutDay && $oldDayNumber) {
                            $checkOutDay = $oldDayNumber;
                        }
                        if (!$checkInDay) $checkInDay = 1;
                        if (!$checkOutDay) $checkOutDay = 1;
                    @endphp
                    <div class="col-md-3">
                        <label class="form-label">Jour check-in</label>
                        <select class="form-select tour-hotel-check-in" name="tour_hotels[{{ $hi }}][check_in_day]" data-index="{{ $hi }}">
                            @for($d = 1; $d <= $lastDayNumber; $d++)
                                <option value="{{ $d }}" {{ $checkInDay == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                            @endfor
                        </select>
                        <small class="text-danger d-none tour-hotel-check-in-error" data-index="{{ $hi }}">Le check-out doit être >= check-in</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jour check-out</label>
                        <select class="form-select tour-hotel-check-out" name="tour_hotels[{{ $hi }}][check_out_day]" data-index="{{ $hi }}">
                            @for($d = 1; $d <= $lastDayNumber; $d++)
                                <option value="{{ $d }}" {{ $checkOutDay == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                            @endfor
                        </select>
                        <small class="text-danger d-none tour-hotel-check-out-error" data-index="{{ $hi }}">Le check-out doit être >= check-in</small>
                    </div>
                    <div class="col-md-2 d-flex align-items-end pb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="tour_hotels[{{ $hi }}][is_optional]" value="1" {{ old("tour_hotels.{$hi}.is_optional", optional($h)->is_optional ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label">Option client</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom de l'hôtel</label>
                        <input type="text" class="form-control" name="tour_hotels[{{ $hi }}][hotel_name]" value="{{ old("tour_hotels.{$hi}.hotel_name", optional($h)->hotel_name ?? '') }}" placeholder="Ex. Hôtel Les Almoravides">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Étoiles (0–5)</label>
                        <input type="number" class="form-control" name="tour_hotels[{{ $hi }}][stars]" value="{{ old("tour_hotels.{$hi}.stars", optional($h)->stars ?? '') }}" min="0" max="5" placeholder="3">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type de chambre</label>
                        <input type="text" class="form-control" name="tour_hotels[{{ $hi }}][room_type]" value="{{ old("tour_hotels.{$hi}.room_type", optional($h)->room_type ?? '') }}" placeholder="Ex. Chambre double">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <input type="text" class="form-control" name="tour_hotels[{{ $hi }}][address]" value="{{ old("tour_hotels.{$hi}.address", optional($h)->address ?? '') }}" placeholder="Ville, pays">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Repas (formule)</label>
                        <input type="text" class="form-control" name="tour_hotels[{{ $hi }}][meal_plan]" value="{{ old("tour_hotels.{$hi}.meal_plan", optional($h)->meal_plan ?? '') }}" placeholder="Ex. Petit-déjeuner inclus">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="tour_hotels[{{ $hi }}][notes]" rows="2">{{ old("tour_hotels.{$hi}.notes", optional($h)->notes ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Image</label>
                        <input type="hidden" name="tour_hotels[{{ $hi }}][image_id]" id="{{ $hid }}" value="{{ old("tour_hotels.{$hi}.image_id", optional($h)->image_id ?? '') }}">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div id="{{ $hid }}_preview_wrap" class="border rounded overflow-hidden bg-light" style="width: 120px; height: 80px; display: {{ $himgUrl ? 'flex' : 'none' }};">
                                <img id="{{ $hid }}_preview" src="{{ $himgUrl }}" alt="" class="img-fluid" style="max-width:100%; max-height:100%; object-fit: cover;">
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary ajtb-logistique-media-btn" data-target="tour_hotel" data-input="{{ $hid }}" data-preview="{{ $hid }}_preview" data-preview-wrap="{{ $hid }}_preview_wrap"><i class="bx bx-images"></i> Choisir</button>
                                <button type="button" class="btn btn-sm btn-outline-danger ajtb-logistique-media-remove" data-input="{{ $hid }}" data-preview="{{ $hid }}_preview" data-preview-wrap="{{ $hid }}_preview_wrap">×</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-soft-primary mb-4" id="tour-add-hotel"><i class="bx bx-plus"></i> Ajouter un hôtel</button>
</div>
<script>
(function(){
    var container = document.getElementById('tour-hotels-container');
    var addBtn = document.getElementById('tour-add-hotel');
    if (!container || !addBtn) return;

    if (container.dataset.initialized === 'true') return;
    container.dataset.initialized = 'true';

    addBtn.addEventListener('click', function(){
        var rows = container.querySelectorAll('.tour-hotel-row');
        var last = rows[rows.length - 1];
        if (!last) return;
        var prevIndex = parseInt(last.getAttribute('data-index'), 10);
        var nextIndex = prevIndex + 1;
        var clone = last.cloneNode(true);
        clone.setAttribute('data-index', nextIndex);
        clone.removeAttribute('data-hotel-id'); // nouveau row, pas d'id
        clone.querySelector('.card-header strong').textContent = 'Hôtel ' + (nextIndex + 1);
        clone.querySelectorAll('[name]').forEach(function(inp){
            if (inp.name && inp.name.indexOf('tour_hotels[') === 0)
                inp.name = inp.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + nextIndex + ']');
            if (inp.name && inp.name.indexOf('[check_in_day]') !== -1) { 
                inp.value = '1'; 
                inp.setAttribute('data-index', nextIndex);
                return; 
            }
            if (inp.name && inp.name.indexOf('[check_out_day]') !== -1) { 
                inp.value = '1'; 
                inp.setAttribute('data-index', nextIndex);
                return; 
            }
            if (inp.name && inp.name.indexOf('[day_number]') !== -1) { inp.value = '1'; return; } // Compatibilité ancien format
            if (inp.name && inp.name.indexOf('[is_optional]') !== -1) { inp.checked = false; return; }
            if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
            if (inp.tagName === 'TEXTAREA') inp.value = '';
        });
        clone.querySelectorAll('[id]').forEach(function(el){
            if (el.id && el.id.indexOf('tour_hotel_image_id_') === 0)
                el.id = el.id.replace(/tour_hotel_image_id_\d+/, 'tour_hotel_image_id_' + nextIndex);
        });
        clone.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
            if (btn.getAttribute('data-input')) btn.setAttribute('data-input', 'tour_hotel_image_id_' + nextIndex);
            if (btn.getAttribute('data-preview')) btn.setAttribute('data-preview', 'tour_hotel_image_id_' + nextIndex + '_preview');
            if (btn.getAttribute('data-preview-wrap')) btn.setAttribute('data-preview-wrap', 'tour_hotel_image_id_' + nextIndex + '_preview_wrap');
        });
        var wrap = clone.querySelector('[id$="_preview_wrap"]');
        if (wrap) wrap.style.display = 'none';
        if (!clone.querySelector('.tour-remove-row')) {
            var header = clone.querySelector('.card-header');
            var rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'btn btn-sm btn-outline-danger tour-remove-row';
            rm.setAttribute('aria-label', 'Supprimer');
            rm.textContent = '×';
            header.appendChild(rm);
        }
        container.appendChild(clone);
        // Initialiser la validation pour la nouvelle ligne
        initHotelCheckInOutValidation(clone);
        // Mettre à jour le titre
        updateHotelsTitle();
    });

    // Validation check-in / check-out
    function initHotelCheckInOutValidation(row) {
        if (!row) return;
        var checkInSel = row.querySelector('.tour-hotel-check-in');
        var checkOutSel = row.querySelector('.tour-hotel-check-out');
        var index = row.getAttribute('data-index');
        if (!checkInSel || !checkOutSel) return;
        
        function validateCheckInOut() {
            var checkIn = parseInt(checkInSel.value || '1', 10);
            var checkOut = parseInt(checkOutSel.value || '1', 10);
            var checkInError = row.querySelector('.tour-hotel-check-in-error[data-index="' + index + '"]');
            var checkOutError = row.querySelector('.tour-hotel-check-out-error[data-index="' + index + '"]');
            
            if (checkOut < checkIn) {
                // Ajuster automatiquement check-out si check-in est supérieur
                checkOutSel.value = checkIn;
                if (checkInError) checkInError.classList.remove('d-none');
                if (checkOutError) checkOutError.classList.remove('d-none');
                setTimeout(function() {
                    if (checkInError) checkInError.classList.add('d-none');
                    if (checkOutError) checkOutError.classList.add('d-none');
                }, 3000);
            } else {
                if (checkInError) checkInError.classList.add('d-none');
                if (checkOutError) checkOutError.classList.add('d-none');
            }
            // Mettre à jour le titre quand les valeurs changent
            updateHotelsTitle();
        }
        
        checkInSel.addEventListener('change', function() {
            var checkIn = parseInt(checkInSel.value || '1', 10);
            var checkOut = parseInt(checkOutSel.value || '1', 10);
            if (checkOut < checkIn) {
                checkOutSel.value = checkIn;
            }
            validateCheckInOut();
        });
        
        checkOutSel.addEventListener('change', validateCheckInOut);
    }

    // Fonction pour mettre à jour le titre de la section Hôtels
    function updateHotelsTitle() {
        var titleEl = document.getElementById('tour-hotels-period');
        if (!titleEl) return;
        var rows = container.querySelectorAll('.tour-hotel-row');
        if (rows.length === 0) {
            titleEl.textContent = '(aucun hôtel configuré)';
            return;
        }
        var minCheckIn = null;
        var maxCheckOut = null;
        rows.forEach(function(row) {
            var checkInSel = row.querySelector('select[name^="tour_hotels["][name$="[check_in_day]"]');
            var checkOutSel = row.querySelector('select[name^="tour_hotels["][name$="[check_out_day]"]');
            if (checkInSel && checkOutSel) {
                var checkIn = parseInt(checkInSel.value || '1', 10);
                var checkOut = parseInt(checkOutSel.value || '1', 10);
                if (minCheckIn === null || checkIn < minCheckIn) minCheckIn = checkIn;
                if (maxCheckOut === null || checkOut > maxCheckOut) maxCheckOut = checkOut;
            } else {
                // Compatibilité ancien format
                var daySel = row.querySelector('select[name^="tour_hotels["][name$="[day_number]"]');
                if (daySel) {
                    var day = parseInt(daySel.value || '1', 10);
                    if (minCheckIn === null || day < minCheckIn) minCheckIn = day;
                    if (maxCheckOut === null || day > maxCheckOut) maxCheckOut = day;
                }
            }
        });
        if (minCheckIn !== null && maxCheckOut !== null) {
            if (minCheckIn === maxCheckOut) {
                titleEl.textContent = '(séjour — Jour ' + minCheckIn + ')';
            } else {
                titleEl.textContent = '(séjour — check-in J' + minCheckIn + ', check-out J' + maxCheckOut + ')';
            }
        }
    }

    // Initialiser la validation pour les lignes existantes
    container.querySelectorAll('.tour-hotel-row').forEach(function(row) {
        initHotelCheckInOutValidation(row);
    });
    
    // Mettre à jour le titre au chargement
    updateHotelsTitle();
    
    // Mettre à jour le titre quand les champs check-in/check-out changent
    container.addEventListener('change', function(e) {
        if (e.target && (e.target.classList.contains('tour-hotel-check-in') || e.target.classList.contains('tour-hotel-check-out'))) {
            updateHotelsTitle();
        }
    });

    container.addEventListener('click', function(e){
        if (e.target.classList.contains('tour-remove-row')) {
            var row = e.target.closest('.tour-hotel-row');
            if (row && container.querySelectorAll('.tour-hotel-row').length > 1) {
                row.remove();
                container.querySelectorAll('.tour-hotel-row').forEach(function(r, i){
                    r.setAttribute('data-index', i);
                    r.querySelector('.card-header strong').textContent = 'Hôtel ' + (i + 1);
                    r.querySelectorAll('[name^="tour_hotels["]').forEach(function(inp){ 
                        inp.name = inp.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + i + ']');
                        // Mettre à jour data-index pour les selects check-in/check-out
                        if (inp.name.indexOf('[check_in_day]') !== -1 || inp.name.indexOf('[check_out_day]') !== -1) {
                            inp.setAttribute('data-index', i);
                        }
                    });
                    r.querySelectorAll('[id^="tour_hotel_image_id_"]').forEach(function(el){ el.id = el.id.replace(/tour_hotel_image_id_\d+/, 'tour_hotel_image_id_' + i); });
                    r.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                        if (btn.getAttribute('data-input')) btn.setAttribute('data-input', 'tour_hotel_image_id_' + i);
                        if (btn.getAttribute('data-preview')) btn.setAttribute('data-preview', 'tour_hotel_image_id_' + i + '_preview');
                        if (btn.getAttribute('data-preview-wrap')) btn.setAttribute('data-preview-wrap', 'tour_hotel_image_id_' + i + '_preview_wrap');
                    });
                    // Réinitialiser la validation pour cette ligne
                    initHotelCheckInOutValidation(r);
                });
                // Mettre à jour le titre après suppression
                updateHotelsTitle();
            }
        }
    });
})();
</script>
