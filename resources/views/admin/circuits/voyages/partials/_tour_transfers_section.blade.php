@php
    $lastDayNumber = isset($lastDayNumber) ? $lastDayNumber : (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1)));
    $arrivalsList = $transferArrivals->isEmpty() ? [null] : $transferArrivals->values()->all();
    $departuresList = $transferDepartures->isEmpty() ? [null] : $transferDepartures->values()->all();
@endphp
<div id="tour-transfers-wrapper">
    <div class="mb-3">
        <strong>Transferts arrivée</strong> (Aéroport → Hôtel)
        <div id="tour-transfer-arrivals-container" class="mt-2">
            @foreach($arrivalsList as $ai => $arr)
            @php
                $arrImgId = 'tour_transfer_arrival_image_id_' . $ai;
                $arrImg = optional($arr)->image_id;
                $arrImgUrl = $arrImg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int)$arrImg) : '';
            @endphp
            <div class="card mb-2 tour-transfer-arrival-row" data-index="{{ $ai }}">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <strong>Transfert arrivée {{ $ai + 1 }}</strong>
                    @if($ai > 0)<button type="button" class="btn btn-sm btn-outline-danger tour-remove-transfer-arrival" aria-label="Supprimer">×</button>@endif
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small">Jour</label>
                            <select class="form-select form-select-sm" name="tour_transfer_arrivals[{{ $ai }}][day_number]">
                                @for($d = 1; $d <= $lastDayNumber; $d++)
                                    <option value="{{ $d }}" {{ old("tour_transfer_arrivals.{$ai}.day_number", optional($arr)->day_number ?? 1) == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="tour_transfer_arrivals[{{ $ai }}][is_optional]" value="1" {{ old("tour_transfer_arrivals.{$ai}.is_optional", optional($arr)->is_optional ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label small">Option client</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">De (ex. aéroport)</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][from_label]" value="{{ old("tour_transfer_arrivals.{$ai}.from_label", optional($arr)->from_label ?? $suggestedArrivalFrom ?? '') }}" placeholder="{{ $suggestedArrivalFrom ?: 'Aéroport arrivée' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">À (ex. hôtel)</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][to_label]" value="{{ old("tour_transfer_arrivals.{$ai}.to_label", optional($arr)->to_label ?? $suggestedArrivalTo ?? '') }}" placeholder="{{ $suggestedArrivalTo ?: 'Hôtel' }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">Prise en charge</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][pickup_time]" value="{{ old("tour_transfer_arrivals.{$ai}.pickup_time", optional($arr)->pickup_time ?? '') }}" placeholder="14:00">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">Arrivée</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][dropoff_time]" value="{{ old("tour_transfer_arrivals.{$ai}.dropoff_time", optional($arr)->dropoff_time ?? '') }}" placeholder="15:00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Véhicule</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][vehicle_type]" value="{{ old("tour_transfer_arrivals.{$ai}.vehicle_type", optional($arr)->vehicle_type ?? '') }}" placeholder="Minivan">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Notes</label>
                            <textarea class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][notes]" rows="1">{{ old("tour_transfer_arrivals.{$ai}.notes", optional($arr)->notes ?? '') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Image</label>
                            <input type="hidden" name="tour_transfer_arrivals[{{ $ai }}][image_id]" id="{{ $arrImgId }}" value="{{ old("tour_transfer_arrivals.{$ai}.image_id", optional($arr)->image_id ?? '') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div id="{{ $arrImgId }}_preview_wrap" class="border rounded overflow-hidden bg-light" style="width: 80px; height: 56px; display: {{ $arrImgUrl ? 'flex' : 'none' }};">
                                    <img id="{{ $arrImgId }}_preview" src="{{ $arrImgUrl }}" alt="" style="max-width:100%; max-height:100%; object-fit: cover;">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary ajtb-logistique-media-btn" data-target="transfer_arrival" data-input="{{ $arrImgId }}" data-preview="{{ $arrImgId }}_preview" data-preview-wrap="{{ $arrImgId }}_preview_wrap"><i class="bx bx-image"></i> Choisir</button>
                                <button type="button" class="btn btn-sm btn-outline-danger ajtb-logistique-media-remove" data-input="{{ $arrImgId }}" data-preview="{{ $arrImgId }}_preview" data-preview-wrap="{{ $arrImgId }}_preview_wrap">×</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-soft-primary mb-3" id="tour-add-transfer-arrival"><i class="bx bx-plus"></i> Ajouter un transfert arrivée</button>
    </div>

    <div class="mb-3">
        <strong>Transferts départ</strong> (Hôtel → Aéroport)
        <div id="tour-transfer-departures-container" class="mt-2">
            @foreach($departuresList as $di => $dep)
            @php
                $depImgId = 'tour_transfer_departure_image_id_' . $di;
                $depImg = optional($dep)->image_id;
                $depImgUrl = $depImg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int)$depImg) : '';
            @endphp
            <div class="card mb-2 tour-transfer-departure-row" data-index="{{ $di }}">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <strong>Transfert départ {{ $di + 1 }}</strong>
                    @if($di > 0)<button type="button" class="btn btn-sm btn-outline-danger tour-remove-transfer-departure" aria-label="Supprimer">×</button>@endif
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small">Jour</label>
                            <select class="form-select form-select-sm" name="tour_transfer_departures[{{ $di }}][day_number]">
                                @for($d = 1; $d <= $lastDayNumber; $d++)
                                    <option value="{{ $d }}" {{ old("tour_transfer_departures.{$di}.day_number", optional($dep)->day_number ?? $lastDayNumber) == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="tour_transfer_departures[{{ $di }}][is_optional]" value="1" {{ old("tour_transfer_departures.{$di}.is_optional", optional($dep)->is_optional ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label small">Option client</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">De (ex. hôtel)</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][from_label]" value="{{ old("tour_transfer_departures.{$di}.from_label", optional($dep)->from_label ?? $suggestedDepartureFrom ?? '') }}" placeholder="{{ $suggestedDepartureFrom ?: 'Hôtel' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">À (ex. aéroport)</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][to_label]" value="{{ old("tour_transfer_departures.{$di}.to_label", optional($dep)->to_label ?? $suggestedDepartureTo ?? '') }}" placeholder="{{ $suggestedDepartureTo ?: 'Aéroport départ' }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">Prise en charge</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][pickup_time]" value="{{ old("tour_transfer_departures.{$di}.pickup_time", optional($dep)->pickup_time ?? '') }}" placeholder="10:00">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">Arrivée</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][dropoff_time]" value="{{ old("tour_transfer_departures.{$di}.dropoff_time", optional($dep)->dropoff_time ?? '') }}" placeholder="11:00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Véhicule</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][vehicle_type]" value="{{ old("tour_transfer_departures.{$di}.vehicle_type", optional($dep)->vehicle_type ?? '') }}" placeholder="Minivan">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Notes</label>
                            <textarea class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][notes]" rows="1">{{ old("tour_transfer_departures.{$di}.notes", optional($dep)->notes ?? '') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Image</label>
                            <input type="hidden" name="tour_transfer_departures[{{ $di }}][image_id]" id="{{ $depImgId }}" value="{{ old("tour_transfer_departures.{$di}.image_id", optional($dep)->image_id ?? '') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div id="{{ $depImgId }}_preview_wrap" class="border rounded overflow-hidden bg-light" style="width: 80px; height: 56px; display: {{ $depImgUrl ? 'flex' : 'none' }};">
                                    <img id="{{ $depImgId }}_preview" src="{{ $depImgUrl }}" alt="" style="max-width:100%; max-height:100%; object-fit: cover;">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary ajtb-logistique-media-btn" data-target="transfer_departure" data-input="{{ $depImgId }}" data-preview="{{ $depImgId }}_preview" data-preview-wrap="{{ $depImgId }}_preview_wrap"><i class="bx bx-image"></i> Choisir</button>
                                <button type="button" class="btn btn-sm btn-outline-danger ajtb-logistique-media-remove" data-input="{{ $depImgId }}" data-preview="{{ $depImgId }}_preview" data-preview-wrap="{{ $depImgId }}_preview_wrap">×</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-soft-primary mb-3" id="tour-add-transfer-departure"><i class="bx bx-plus"></i> Ajouter un transfert départ</button>
    </div>
</div>
<script>
(function(){
    var lastDayNum = {{ (int) $lastDayNumber }};
    var arrContainer = document.getElementById('tour-transfer-arrivals-container');
    var addArrBtn = document.getElementById('tour-add-transfer-arrival');
    if (!arrContainer || !addArrBtn) return;

    if (arrContainer.dataset.initialized === 'true') return;
    arrContainer.dataset.initialized = 'true';

    addArrBtn.addEventListener('click', function(){
        var rows = arrContainer.querySelectorAll('.tour-transfer-arrival-row');
        var last = rows[rows.length - 1];
        if (!last) return;
        var nextIdx = parseInt(last.getAttribute('data-index'), 10) + 1;
        var clone = last.cloneNode(true);
        clone.setAttribute('data-index', nextIdx);
        clone.querySelector('.card-header strong').textContent = 'Transfert arrivée ' + (nextIdx + 1);
        if (!clone.querySelector('.tour-remove-transfer-arrival')) {
            var btn = document.createElement('button');
            btn.type = 'button'; btn.className = 'btn btn-sm btn-outline-danger tour-remove-transfer-arrival'; btn.setAttribute('aria-label', 'Supprimer'); btn.textContent = '×';
            clone.querySelector('.card-header').appendChild(btn);
        }
        clone.querySelectorAll('[name^="tour_transfer_arrivals["]').forEach(function(inp){
            inp.name = inp.name.replace(/tour_transfer_arrivals\[\d+\]/, 'tour_transfer_arrivals[' + nextIdx + ']');
            if (inp.name.indexOf('[day_number]') !== -1) inp.value = '1';
            if (inp.name.indexOf('[is_optional]') !== -1) inp.checked = false;
            if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
            if (inp.tagName === 'TEXTAREA') inp.value = '';
        });
        clone.querySelectorAll('[id^="tour_transfer_arrival_image_id_"]').forEach(function(el){
            var newId = el.id.replace(/tour_transfer_arrival_image_id_\d+/, 'tour_transfer_arrival_image_id_' + nextIdx);
            el.id = newId;
            if (el.id.indexOf('_preview_wrap') !== -1) el.style.display = 'none';
        });
        clone.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
            var inp = btn.getAttribute('data-input');
            if (inp && inp.indexOf('tour_transfer_arrival_image_id_') === 0) {
                btn.setAttribute('data-input', 'tour_transfer_arrival_image_id_' + nextIdx);
                btn.setAttribute('data-preview', 'tour_transfer_arrival_image_id_' + nextIdx + '_preview');
                btn.setAttribute('data-preview-wrap', 'tour_transfer_arrival_image_id_' + nextIdx + '_preview_wrap');
            }
        });
        arrContainer.appendChild(clone);
    });

    arrContainer.addEventListener('click', function(e){
        if (e.target.classList.contains('tour-remove-transfer-arrival')) {
            var row = e.target.closest('.tour-transfer-arrival-row');
            if (row && arrContainer.querySelectorAll('.tour-transfer-arrival-row').length > 1) {
                row.remove();
                arrContainer.querySelectorAll('.tour-transfer-arrival-row').forEach(function(r, i){
                    r.setAttribute('data-index', i);
                    r.querySelector('.card-header strong').textContent = 'Transfert arrivée ' + (i + 1);
                    r.querySelectorAll('[name^="tour_transfer_arrivals["]').forEach(function(inp){ inp.name = inp.name.replace(/tour_transfer_arrivals\[\d+\]/, 'tour_transfer_arrivals[' + i + ']'); });
                    r.querySelectorAll('[id^="tour_transfer_arrival_image_id_"]').forEach(function(el){ el.id = el.id.replace(/tour_transfer_arrival_image_id_\d+/, 'tour_transfer_arrival_image_id_' + i); });
                    r.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                        var inp = btn.getAttribute('data-input');
                        if (inp && inp.indexOf('tour_transfer_arrival_image_id_') === 0) {
                            btn.setAttribute('data-input', 'tour_transfer_arrival_image_id_' + i);
                            btn.setAttribute('data-preview', 'tour_transfer_arrival_image_id_' + i + '_preview');
                            btn.setAttribute('data-preview-wrap', 'tour_transfer_arrival_image_id_' + i + '_preview_wrap');
                        }
                    });
                });
            }
        }
    });

    var depContainer = document.getElementById('tour-transfer-departures-container');
    var addDepBtn = document.getElementById('tour-add-transfer-departure');
    if (!depContainer || !addDepBtn) return;

    if (depContainer.dataset.initialized === 'true') return;
    depContainer.dataset.initialized = 'true';

    addDepBtn.addEventListener('click', function(){
        var rows = depContainer.querySelectorAll('.tour-transfer-departure-row');
        var last = rows[rows.length - 1];
        if (!last) return;
        var nextIdx = parseInt(last.getAttribute('data-index'), 10) + 1;
        var clone = last.cloneNode(true);
        clone.setAttribute('data-index', nextIdx);
        clone.querySelector('.card-header strong').textContent = 'Transfert départ ' + (nextIdx + 1);
        if (!clone.querySelector('.tour-remove-transfer-departure')) {
            var btn = document.createElement('button');
            btn.type = 'button'; btn.className = 'btn btn-sm btn-outline-danger tour-remove-transfer-departure'; btn.setAttribute('aria-label', 'Supprimer'); btn.textContent = '×';
            clone.querySelector('.card-header').appendChild(btn);
        }
        clone.querySelectorAll('[name^="tour_transfer_departures["]').forEach(function(inp){
            inp.name = inp.name.replace(/tour_transfer_departures\[\d+\]/, 'tour_transfer_departures[' + nextIdx + ']');
            if (inp.name.indexOf('[day_number]') !== -1) inp.value = String(lastDayNum);
            if (inp.name.indexOf('[is_optional]') !== -1) inp.checked = false;
            if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
            if (inp.tagName === 'TEXTAREA') inp.value = '';
        });
        clone.querySelectorAll('[id^="tour_transfer_departure_image_id_"]').forEach(function(el){
            el.id = el.id.replace(/tour_transfer_departure_image_id_\d+/, 'tour_transfer_departure_image_id_' + nextIdx);
            if (el.id.indexOf('_preview_wrap') !== -1) el.style.display = 'none';
        });
        clone.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
            var inp = btn.getAttribute('data-input');
            if (inp && inp.indexOf('tour_transfer_departure_image_id_') === 0) {
                btn.setAttribute('data-input', 'tour_transfer_departure_image_id_' + nextIdx);
                btn.setAttribute('data-preview', 'tour_transfer_departure_image_id_' + nextIdx + '_preview');
                btn.setAttribute('data-preview-wrap', 'tour_transfer_departure_image_id_' + nextIdx + '_preview_wrap');
            }
        });
        depContainer.appendChild(clone);
    });

    depContainer.addEventListener('click', function(e){
        if (e.target.classList.contains('tour-remove-transfer-departure')) {
            var row = e.target.closest('.tour-transfer-departure-row');
            if (row && depContainer.querySelectorAll('.tour-transfer-departure-row').length > 1) {
                row.remove();
                depContainer.querySelectorAll('.tour-transfer-departure-row').forEach(function(r, i){
                    r.setAttribute('data-index', i);
                    r.querySelector('.card-header strong').textContent = 'Transfert départ ' + (i + 1);
                    r.querySelectorAll('[name^="tour_transfer_departures["]').forEach(function(inp){ inp.name = inp.name.replace(/tour_transfer_departures\[\d+\]/, 'tour_transfer_departures[' + i + ']'); });
                    r.querySelectorAll('[id^="tour_transfer_departure_image_id_"]').forEach(function(el){ el.id = el.id.replace(/tour_transfer_departure_image_id_\d+/, 'tour_transfer_departure_image_id_' + i); });
                    r.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                        var inp = btn.getAttribute('data-input');
                        if (inp && inp.indexOf('tour_transfer_departure_image_id_') === 0) {
                            btn.setAttribute('data-input', 'tour_transfer_departure_image_id_' + i);
                            btn.setAttribute('data-preview', 'tour_transfer_departure_image_id_' + i + '_preview');
                            btn.setAttribute('data-preview-wrap', 'tour_transfer_departure_image_id_' + i + '_preview_wrap');
                        }
                    });
                });
            }
        }
    });
})();
</script>
