@php
    $lastDayNumber = isset($lastDayNumber) ? $lastDayNumber : (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1)));
    $hotelsList = $tourHotels->isEmpty() ? [null] : $tourHotels->all();
    $otherHotels = $otherTourHotelsForCopy ?? collect();
    $otherTitles = $otherTourTitles ?? [];
@endphp
<div id="tour-hotels-wrapper">
    @if($otherHotels->isNotEmpty())
    <div class="mb-3 p-3 bg-light rounded">
        <label class="form-label small mb-1">Choisir un hôtel existant</label>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <select class="form-select form-select-sm" id="copy-from-hotel-select" style="max-width: 380px;">
                <option value="">— Créer un nouvel hôtel / modifier ci-dessous —</option>
                @foreach($otherHotels as $oh)
                    <option value="{{ $oh->tour_id }}">{{ \Str::limit($oh->hotel_name ?: 'Hôtel #'.$oh->tour_id, 40) }} — {{ \Str::limit($otherTitles[$oh->tour_id] ?? 'Voyage '.$oh->tour_id, 35) }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-sm btn-outline-primary" id="copy-from-hotel-btn">Charger</button>
        </div>
        <small class="text-muted">Charger les données d’un hôtel existant pour les réutiliser sur ce voyage (sans quitter la page).</small>
    </div>
    @endif
    <div id="tour-hotels-container">
        @foreach($hotelsList as $hi => $h)
        @php $hid = 'tour_hotel_image_id_' . $hi; $himg = optional($h)->image_id; $himgUrl = $himg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int)$himg) : ''; @endphp
        <div class="card mb-3 tour-hotel-row" data-index="{{ $hi }}" data-hotel-id="{{ optional($h)->id ?? '' }}">
            @if(optional($h)->id)
            <input type="hidden" name="tour_hotels[{{ $hi }}][id]" value="{{ $h->id }}">
            @endif
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

                    {{-- Chambres de l'hôtel --}}
                    <div class="col-12 mt-3 border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-semibold">Chambres de l'hôtel</label>
                            <button type="button" class="btn btn-sm btn-soft-primary tour-add-room" data-hotel-index="{{ $hi }}"><i class="bx bx-plus"></i> Ajouter une chambre</button>
                        </div>
                        <div class="tour-hotel-rooms-container" data-hotel-index="{{ $hi }}">
                            @php $roomTypes = ['Single' => 'Single', 'Double' => 'Double', 'Twin' => 'Twin', 'Triple' => 'Triple', 'Quadruple' => 'Quadruple', 'Suite' => 'Suite', 'Family Room' => 'Family Room', 'Chambre communicante' => 'Chambre communicante', 'Autre' => 'Autre']; $roomsList = old("tour_hotels.{$hi}.rooms", $h && $h->rooms ? $h->rooms->all() : []); if (empty($roomsList)) $roomsList = [null]; @endphp
                            @foreach($roomsList as $ri => $room)
                            @php
                                $room = is_object($room) ? $room : (is_array($room) ? (object)$room : null);
                                $roomId = optional($room)->id ?? '';
                                $roomTypeVal = old("tour_hotels.{$hi}.rooms.{$ri}.room_type", optional($room)->room_type ?? '');
                                $roomLabelVal = old("tour_hotels.{$hi}.rooms.{$ri}.room_label", optional($room)->room_label ?? '');
                                $roomCodeVal = old("tour_hotels.{$hi}.rooms.{$ri}.room_code", optional($room)->room_code ?? '');
                                $roomCountVal = old("tour_hotels.{$hi}.rooms.{$ri}.room_count", optional($room)->room_count ?? 1);
                                $capAdultsVal = old("tour_hotels.{$hi}.rooms.{$ri}.capacity_adults", optional($room)->capacity_adults ?? 0);
                                $capChildrenVal = old("tour_hotels.{$hi}.rooms.{$ri}.capacity_children", optional($room)->capacity_children ?? 0);
                                $capTotalVal = old("tour_hotels.{$hi}.rooms.{$ri}.capacity_total", optional($room)->capacity_total ?? 1);
                                $supplementVal = old("tour_hotels.{$hi}.rooms.{$ri}.supplement", optional($room)->supplement ?? 0);
                                $descVal = old("tour_hotels.{$hi}.rooms.{$ri}.description", optional($room)->description ?? '');
                                $notesVal = old("tour_hotels.{$hi}.rooms.{$ri}.notes", optional($room)->notes ?? '');
                                $isActiveVal = old("tour_hotels.{$hi}.rooms.{$ri}.is_active", optional($room)->is_active ?? true);
                                $isDefaultVal = old("tour_hotels.{$hi}.rooms.{$ri}.is_default", optional($room)->is_default ?? false);
                            @endphp
                            <div class="card mb-2 tour-room-row" data-hotel-index="{{ $hi }}" data-room-index="{{ $ri }}">
                                <div class="card-body py-2">
                                    @if($roomId)<input type="hidden" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][id]" value="{{ $roomId }}">@endif
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label small">Type</label>
                                            <select class="form-select form-select-sm" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][room_type]">
                                                @foreach($roomTypes as $k => $v)
                                                <option value="{{ $k }}" {{ $roomTypeVal == $k ? 'selected' : '' }}>{{ $v }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label small">Nb ch.</label>
                                            <input type="number" class="form-control form-control-sm" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][room_count]" value="{{ $roomCountVal }}" min="1">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label small">Cap. ad.</label>
                                            <input type="number" class="form-control form-control-sm" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][capacity_adults]" value="{{ $capAdultsVal }}" min="0">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label small">Cap. enf.</label>
                                            <input type="number" class="form-control form-control-sm" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][capacity_children]" value="{{ $capChildrenVal }}" min="0">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label small">Cap. tot.</label>
                                            <input type="number" class="form-control form-control-sm" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][capacity_total]" value="{{ $capTotalVal }}" min="1">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Supplément (DH)</label>
                                            <input type="number" class="form-control form-control-sm tour-room-supplement" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][supplement]" value="{{ $supplementVal }}" min="0" step="0.01" data-room-index="{{ $ri }}">
                                            @if((float)$supplementVal == 0)<span class="badge bg-success ms-1">Standard</span>@endif
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][is_default]" value="1" {{ $isDefaultVal ? 'checked' : '' }}>
                                                <label class="form-check-label small">Défaut</label>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger tour-remove-room" data-hotel-index="{{ $hi }}" data-room-index="{{ $ri }}" aria-label="Supprimer la chambre">×</button>
                                        </div>
                                    </div>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-2">
                                            <label class="form-label small">Code / Réf.</label>
                                            <input type="text" class="form-control form-control-sm" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][room_code]" value="{{ $roomCodeVal }}" placeholder="Ex. DBL-STD">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small">Libellé</label>
                                            <input type="text" class="form-control form-control-sm" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][room_label]" value="{{ $roomLabelVal }}" placeholder="Optionnel">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Description</label>
                                            <input type="text" class="form-control form-control-sm" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][description]" value="{{ $descVal }}" placeholder="Courte description">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check mt-2">
                                                <input type="checkbox" class="form-check-input" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][is_active]" value="1" {{ $isActiveVal ? 'checked' : '' }}>
                                                <label class="form-check-label small">Actif</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col-12">
                                            <label class="form-label small">Notes internes</label>
                                            <textarea class="form-control form-control-sm" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][notes]" rows="1" placeholder="Optionnel">{{ $notesVal }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
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
        // Supprimer l'id hôtel caché (nouvel hôtel)
        clone.querySelectorAll('input[name^="tour_hotels["][name*="[id]"]').forEach(function(inp){ if (inp.name.indexOf('[rooms]') === -1) inp.remove(); });
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
            if (inp.name && inp.name.indexOf('[rooms]') !== -1) {
                // Chambres : garder le même index room pour le nouveau hi, mais supprimer les id chambres
                if (inp.name.indexOf('[id]') !== -1 && inp.name.indexOf('[rooms]') !== -1) { inp.remove(); return; }
                if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
                if (inp.tagName === 'TEXTAREA') inp.value = '';
                if (inp.type === 'checkbox') { inp.checked = (inp.name.indexOf('is_default') !== -1 ? false : true); }
                return;
            }
            if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
            if (inp.tagName === 'TEXTAREA') inp.value = '';
        });
        // Mettre à jour data-hotel-index dans la section chambres du clone
        var roomsContainer = clone.querySelector('.tour-hotel-rooms-container');
        if (roomsContainer) {
            roomsContainer.setAttribute('data-hotel-index', nextIndex);
            clone.querySelector('.tour-add-room').setAttribute('data-hotel-index', nextIndex);
            roomsContainer.querySelectorAll('.tour-room-row').forEach(function(rr){
                rr.setAttribute('data-hotel-index', nextIndex);
                rr.querySelectorAll('.tour-remove-room').forEach(function(btn){ btn.setAttribute('data-hotel-index', nextIndex); });
            });
        }
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
                    var roomsCont = r.querySelector('.tour-hotel-rooms-container');
                    if (roomsCont) {
                        roomsCont.setAttribute('data-hotel-index', i);
                        r.querySelector('.tour-add-room').setAttribute('data-hotel-index', i);
                        roomsCont.querySelectorAll('.tour-room-row').forEach(function(rr, ri){
                            rr.setAttribute('data-hotel-index', i);
                            rr.setAttribute('data-room-index', ri);
                            rr.querySelectorAll('[name]').forEach(function(inp){
                                if (inp.name && inp.name.indexOf('tour_hotels[') === 0 && inp.name.indexOf('[rooms]') !== -1)
                                    inp.name = inp.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + i + ']').replace(/\[rooms\]\[\d+\]/, '[rooms][' + ri + ']');
                            });
                            rr.querySelectorAll('.tour-remove-room').forEach(function(btn){ btn.setAttribute('data-hotel-index', i); btn.setAttribute('data-room-index', ri); });
                            rr.querySelectorAll('.tour-room-supplement').forEach(function(s){ s.setAttribute('data-room-index', ri); });
                        });
                    }
                    initHotelCheckInOutValidation(r);
                });
                updateHotelsTitle();
            }
        }
        // Ajouter une chambre
        if (e.target.classList.contains('tour-add-room')) {
            var hotelRow = e.target.closest('.tour-hotel-row');
            var roomsCont = hotelRow ? hotelRow.querySelector('.tour-hotel-rooms-container') : null;
            if (!roomsCont) return;
            var hi = roomsCont.getAttribute('data-hotel-index');
            var rows = roomsCont.querySelectorAll('.tour-room-row');
            var last = rows[rows.length - 1];
            if (!last) return;
            var nextRi = rows.length;
            var clone = last.cloneNode(true);
            clone.setAttribute('data-room-index', nextRi);
            clone.querySelectorAll('[name]').forEach(function(inp){
                if (inp.name && inp.name.indexOf('[rooms]') !== -1) {
                    inp.name = inp.name.replace(/\[rooms\]\[\d+\]/, '[rooms][' + nextRi + ']');
                    if (inp.name.indexOf('[id]') !== -1) { inp.remove(); return; }
                    if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
                    if (inp.tagName === 'TEXTAREA') inp.value = '';
                    if (inp.type === 'checkbox') inp.checked = (inp.name.indexOf('is_default') !== -1 ? false : true);
                }
            });
            clone.querySelectorAll('.tour-remove-room').forEach(function(btn){ btn.setAttribute('data-room-index', nextRi); });
            clone.querySelectorAll('.tour-room-supplement').forEach(function(s){ s.setAttribute('data-room-index', nextRi); });
            clone.querySelectorAll('.badge.bg-success').forEach(function(b){ b.remove(); });
            roomsCont.appendChild(clone);
        }
        // Supprimer une chambre
        if (e.target.classList.contains('tour-remove-room')) {
            var roomRow = e.target.closest('.tour-room-row');
            if (!roomRow) return;
            var roomsCont = roomRow.closest('.tour-hotel-rooms-container');
            if (!roomsCont || roomsCont.querySelectorAll('.tour-room-row').length <= 1) return;
            roomRow.remove();
            var hi = roomsCont.getAttribute('data-hotel-index');
            roomsCont.querySelectorAll('.tour-room-row').forEach(function(rr, ri){
                rr.setAttribute('data-room-index', ri);
                rr.querySelectorAll('[name]').forEach(function(inp){
                    if (inp.name && inp.name.indexOf('[rooms]') !== -1)
                        inp.name = inp.name.replace(/\[rooms\]\[\d+\]/, '[rooms][' + ri + ']');
                });
                rr.querySelectorAll('.tour-remove-room').forEach(function(btn){ btn.setAttribute('data-room-index', ri); });
                rr.querySelectorAll('.tour-room-supplement').forEach(function(s){ s.setAttribute('data-room-index', ri); });
            });
        }
    });

    // Choisir un hôtel existant : charger les données dans la première ligne
    var copySelect = document.getElementById('copy-from-hotel-select');
    var copyBtn = document.getElementById('copy-from-hotel-btn');
    if (copySelect && copyBtn) {
        var dataUrlBase = '{{ url("admin/circuits/tour-hotels") }}';
        copyBtn.addEventListener('click', function(){
            var tourId = copySelect.value;
            if (!tourId) return;
            fetch(dataUrlBase + '/' + tourId + '/data', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    var firstRow = container.querySelector('.tour-hotel-row[data-index="0"]');
                    if (!firstRow) return;
                    var setVal = function(nameSuffix, val) {
                        var inp = firstRow.querySelector('[name="tour_hotels[0][' + nameSuffix + ']"]');
                        if (inp && val !== undefined && val !== null) {
                            if (inp.type === 'checkbox') inp.checked = !!val;
                            else inp.value = val;
                        }
                    };
                    var h = data.hotel || {};
                    setVal('hotel_name', h.hotel_name);
                    setVal('stars', h.stars);
                    setVal('address', h.address);
                    setVal('room_type', h.room_type);
                    setVal('meal_plan', h.meal_plan);
                    setVal('notes', h.notes);
                    setVal('image_id', h.image_id);
                    setVal('check_in_day', h.check_in_day);
                    setVal('check_out_day', h.check_out_day);
                    setVal('is_optional', h.is_optional ? '1' : '');
                    var roomsCont = firstRow.querySelector('.tour-hotel-rooms-container');
                    if (roomsCont && Array.isArray(data.rooms)) {
                        var roomRows = roomsCont.querySelectorAll('.tour-room-row');
                        var need = data.rooms.length;
                        if (need < 1) need = 1;
                        while (roomRows.length > need) {
                            if (roomRows.length <= 1) break;
                            roomRows[roomRows.length - 1].remove();
                            roomRows = roomsCont.querySelectorAll('.tour-room-row');
                        }
                        var addRoomBtn = firstRow.querySelector('.tour-add-room');
                        while (roomRows.length < need && addRoomBtn) {
                            addRoomBtn.click();
                            roomRows = roomsCont.querySelectorAll('.tour-room-row');
                        }
                        roomRows = roomsCont.querySelectorAll('.tour-room-row');
                        data.rooms.forEach(function(room, ri){
                            var row = roomRows[ri];
                            if (!row) return;
                            var idx = row.getAttribute('data-room-index');
                            if (idx === null) idx = String(ri);
                            var setRoom = function(suffix, val) {
                                var inps = row.querySelectorAll('[name]');
                                for (var n = 0; n < inps.length; n++) {
                                    var name = inps[n].name || '';
                                    if (name.indexOf('[rooms][' + idx + '][' + suffix + ']') !== -1) {
                                        if (inps[n].tagName === 'SELECT') inps[n].value = val || '';
                                        else if (inps[n].type === 'checkbox') inps[n].checked = !!val;
                                        else inps[n].value = val !== undefined && val !== null ? val : '';
                                        return;
                                    }
                                }
                            };
                            setRoom('room_type', room.room_type);
                            setRoom('room_label', room.room_label);
                            setRoom('room_code', room.room_code);
                            setRoom('room_count', room.room_count);
                            setRoom('capacity_adults', room.capacity_adults);
                            setRoom('capacity_children', room.capacity_children);
                            setRoom('capacity_total', room.capacity_total);
                            setRoom('supplement', room.supplement);
                            setRoom('description', room.description);
                            setRoom('notes', room.notes);
                            var defInp = row.querySelector('[name*="[is_default]"]');
                            if (defInp) defInp.checked = !!room.is_default;
                            var actInp = row.querySelector('[name*="[is_active]"]');
                            if (actInp) actInp.checked = room.is_active !== false;
                        });
                    }
                    copySelect.value = '';
                })
                .catch(function(){ alert('Impossible de charger l\'hôtel.'); });
        });
    }
})();
</script>
