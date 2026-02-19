@php
    $lastDayNumber = isset($lastDayNumber) ? $lastDayNumber : (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1)));
    // Fusionner arrivals et departures en une seule liste unifiée
    $allTransfers = collect();
    foreach ($transferArrivals as $arr) {
        $allTransfers->push($arr);
    }
    foreach ($transferDepartures as $dep) {
        $allTransfers->push($dep);
    }
    $transfersList = $allTransfers->isEmpty() ? [null] : $allTransfers->values()->all();
@endphp
<div id="tour-transfers-wrapper">
    <div class="mb-3">
        <strong>Transferts</strong> (plusieurs par jour possible)
        <div id="tour-transfers-container" class="mt-2">
            @foreach($transfersList as $ti => $transfer)
            @php 
                $transferId = optional($transfer)->id;
                $transferImgId = 'tour_transfer_image_id_' . $ti;
                $transferImg = optional($transfer)->image_id;
                $transferImgUrl = $transferImg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int)$transferImg) : '';
                // Compatibilité : utiliser day_number si check_in_day/check_out_day n'existent pas
                $transferDayNumber = old("tour_transfers.{$ti}.day_number", optional($transfer)->day_number ?? 1);
            @endphp
            <div class="card mb-2 tour-transfer-row" data-index="{{ $ti }}" data-transfer-id="{{ $transferId ?? '' }}">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <strong>Transfert {{ $ti + 1 }}</strong>
                    @if($ti > 0)<button type="button" class="btn btn-sm btn-outline-danger tour-remove-transfer" aria-label="Supprimer">×</button>@endif
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small">Jour</label>
                            <select class="form-select form-select-sm" name="tour_transfers[{{ $ti }}][day_number]">
                                @for($d = 1; $d <= $lastDayNumber; $d++)
                                    <option value="{{ $d }}" {{ $transferDayNumber == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="tour_transfers[{{ $ti }}][is_optional]" value="1" {{ old("tour_transfers.{$ti}.is_optional", optional($transfer)->is_optional ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label small">Option client</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">De (ex. aéroport, hôtel)</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfers[{{ $ti }}][from_label]" value="{{ old("tour_transfers.{$ti}.from_label", optional($transfer)->from_label ?? '') }}" placeholder="Ex. Aéroport, Hôtel">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">À (ex. hôtel, aéroport)</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfers[{{ $ti }}][to_label]" value="{{ old("tour_transfers.{$ti}.to_label", optional($transfer)->to_label ?? '') }}" placeholder="Ex. Hôtel, Aéroport">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">Prise en charge</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfers[{{ $ti }}][pickup_time]" value="{{ old("tour_transfers.{$ti}.pickup_time", optional($transfer)->pickup_time ?? '') }}" placeholder="14:00">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">Arrivée</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfers[{{ $ti }}][dropoff_time]" value="{{ old("tour_transfers.{$ti}.dropoff_time", optional($transfer)->dropoff_time ?? '') }}" placeholder="15:00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Véhicule</label>
                            <input type="text" class="form-control form-control-sm" name="tour_transfers[{{ $ti }}][vehicle_type]" value="{{ old("tour_transfers.{$ti}.vehicle_type", optional($transfer)->vehicle_type ?? '') }}" placeholder="Minivan">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Notes</label>
                            <textarea class="form-control form-control-sm" name="tour_transfers[{{ $ti }}][notes]" rows="1">{{ old("tour_transfers.{$ti}.notes", optional($transfer)->notes ?? '') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Image</label>
                            <input type="hidden" name="tour_transfers[{{ $ti }}][image_id]" id="{{ $transferImgId }}" value="{{ old("tour_transfers.{$ti}.image_id", optional($transfer)->image_id ?? '') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div id="{{ $transferImgId }}_preview_wrap" class="border rounded overflow-hidden bg-light" style="width: 80px; height: 56px; display: {{ $transferImgUrl ? 'flex' : 'none' }};">
                                    <img id="{{ $transferImgId }}_preview" src="{{ $transferImgUrl }}" alt="" style="max-width:100%; max-height:100%; object-fit: cover;">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary ajtb-logistique-media-btn" data-target="transfer" data-input="{{ $transferImgId }}" data-preview="{{ $transferImgId }}_preview" data-preview-wrap="{{ $transferImgId }}_preview_wrap"><i class="bx bx-image"></i> Choisir</button>
                                <button type="button" class="btn btn-sm btn-outline-danger ajtb-logistique-media-remove" data-input="{{ $transferImgId }}" data-preview="{{ $transferImgId }}_preview" data-preview-wrap="{{ $transferImgId }}_preview_wrap">×</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-soft-primary mb-4" id="tour-add-transfer"><i class="bx bx-plus"></i> Ajouter un transfert</button>
    </div>
</div>
<script>
(function(){
    var lastDayNum = {{ (int) $lastDayNumber }};
    var container = document.getElementById('tour-transfers-container');
    var addBtn = document.getElementById('tour-add-transfer');
    if (!container || !addBtn) return;

    if (container.dataset.initialized === 'true') return;
    container.dataset.initialized = 'true';

    addBtn.addEventListener('click', function(){
        var rows = container.querySelectorAll('.tour-transfer-row');
        var last = rows[rows.length - 1];
        if (!last) return;
        var nextIdx = parseInt(last.getAttribute('data-index'), 10) + 1;
        var clone = last.cloneNode(true);
        clone.setAttribute('data-index', nextIdx);
        clone.removeAttribute('data-transfer-id'); // nouveau row, pas d'id
        clone.querySelector('.card-header strong').textContent = 'Transfert ' + (nextIdx + 1);
        if (!clone.querySelector('.tour-remove-transfer')) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-danger tour-remove-transfer';
            btn.setAttribute('aria-label', 'Supprimer');
            btn.textContent = '×';
            clone.querySelector('.card-header').appendChild(btn);
        }
        clone.querySelectorAll('[name^="tour_transfers["]').forEach(function(inp){
            inp.name = inp.name.replace(/tour_transfers\[\d+\]/, 'tour_transfers[' + nextIdx + ']');
            if (inp.name.indexOf('[day_number]') !== -1) inp.value = '1';
            if (inp.name.indexOf('[is_optional]') !== -1) inp.checked = false;
            if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
            if (inp.tagName === 'TEXTAREA') inp.value = '';
        });
        clone.querySelectorAll('[id^="tour_transfer_image_id_"]').forEach(function(el){
            var newId = el.id.replace(/tour_transfer_image_id_\d+/, 'tour_transfer_image_id_' + nextIdx);
            el.id = newId;
            if (el.id.indexOf('_preview_wrap') !== -1) el.style.display = 'none';
        });
        clone.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
            var inp = btn.getAttribute('data-input');
            if (inp && inp.indexOf('tour_transfer_image_id_') === 0) {
                btn.setAttribute('data-input', 'tour_transfer_image_id_' + nextIdx);
                btn.setAttribute('data-preview', 'tour_transfer_image_id_' + nextIdx + '_preview');
                btn.setAttribute('data-preview-wrap', 'tour_transfer_image_id_' + nextIdx + '_preview_wrap');
            }
        });
        container.appendChild(clone);
    });

    container.addEventListener('click', function(e){
        if (e.target.classList.contains('tour-remove-transfer')) {
            var row = e.target.closest('.tour-transfer-row');
            if (row && container.querySelectorAll('.tour-transfer-row').length > 1) {
                row.remove();
                container.querySelectorAll('.tour-transfer-row').forEach(function(r, i){
                    r.setAttribute('data-index', i);
                    r.querySelector('.card-header strong').textContent = 'Transfert ' + (i + 1);
                    r.querySelectorAll('[name^="tour_transfers["]').forEach(function(inp){ 
                        inp.name = inp.name.replace(/tour_transfers\[\d+\]/, 'tour_transfers[' + i + ']'); 
                    });
                    r.querySelectorAll('[id^="tour_transfer_image_id_"]').forEach(function(el){ 
                        el.id = el.id.replace(/tour_transfer_image_id_\d+/, 'tour_transfer_image_id_' + i); 
                    });
                    r.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                        var inp = btn.getAttribute('data-input');
                        if (inp && inp.indexOf('tour_transfer_image_id_') === 0) {
                            btn.setAttribute('data-input', 'tour_transfer_image_id_' + i);
                            btn.setAttribute('data-preview', 'tour_transfer_image_id_' + i + '_preview');
                            btn.setAttribute('data-preview-wrap', 'tour_transfer_image_id_' + i + '_preview_wrap');
                        }
                    });
                });
            }
        }
    });
})();
</script>
