@php
    $lastDayNumber = isset($lastDayNumber) ? $lastDayNumber : (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1)));
    $hotelsList = $tourHotels->isEmpty() ? [null] : $tourHotels->all();
@endphp
<div id="tour-hotels-wrapper">
    <div id="tour-hotels-container">
        @foreach($hotelsList as $hi => $h)
        @php $hid = 'tour_hotel_image_id_' . $hi; $himg = optional($h)->image_id; $himgUrl = $himg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int)$himg) : ''; @endphp
        <div class="card mb-3 tour-hotel-row" data-index="{{ $hi }}">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <strong>Hôtel {{ $hi + 1 }}</strong>
                @if($hi > 0)<button type="button" class="btn btn-sm btn-outline-danger tour-remove-row" data-target=".tour-hotel-row" aria-label="Supprimer">×</button>@endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Jour</label>
                        <select class="form-select" name="tour_hotels[{{ $hi }}][day_number]">
                            @for($d = 1; $d <= $lastDayNumber; $d++)
                                <option value="{{ $d }}" {{ old("tour_hotels.{$hi}.day_number", optional($h)->day_number ?? 1) == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                            @endfor
                        </select>
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
        clone.querySelector('.card-header strong').textContent = 'Hôtel ' + (nextIndex + 1);
        clone.querySelectorAll('[name]').forEach(function(inp){
            if (inp.name && inp.name.indexOf('tour_hotels[') === 0)
                inp.name = inp.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + nextIndex + ']');
            if (inp.name && inp.name.indexOf('[day_number]') !== -1) { inp.value = '1'; return; }
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
    });

    container.addEventListener('click', function(e){
        if (e.target.classList.contains('tour-remove-row')) {
            var row = e.target.closest('.tour-hotel-row');
            if (row && container.querySelectorAll('.tour-hotel-row').length > 1) {
                row.remove();
                container.querySelectorAll('.tour-hotel-row').forEach(function(r, i){
                    r.setAttribute('data-index', i);
                    r.querySelector('.card-header strong').textContent = 'Hôtel ' + (i + 1);
                    r.querySelectorAll('[name^="tour_hotels["]').forEach(function(inp){ inp.name = inp.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + i + ']'); });
                    r.querySelectorAll('[id^="tour_hotel_image_id_"]').forEach(function(el){ el.id = el.id.replace(/tour_hotel_image_id_\d+/, 'tour_hotel_image_id_' + i); });
                    r.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                        if (btn.getAttribute('data-input')) btn.setAttribute('data-input', 'tour_hotel_image_id_' + i);
                        if (btn.getAttribute('data-preview')) btn.setAttribute('data-preview', 'tour_hotel_image_id_' + i + '_preview');
                        if (btn.getAttribute('data-preview-wrap')) btn.setAttribute('data-preview-wrap', 'tour_hotel_image_id_' + i + '_preview_wrap');
                    });
                });
            }
        }
    });
})();
</script>
