{{-- Lieux de départ (éditables) en haut de l'onglet Vols. Gestion AJAX si travelId présent. --}}
@php
    $departurePlaces = $departurePlaces ?? collect();
    $placesList = $departurePlaces->isEmpty() ? [] : $departurePlaces->all();
    $travelId = (int) ($travelId ?? 0);
@endphp
<div class="card mb-4" id="departure-places-card" data-travel-id="{{ $travelId }}">
    <div class="card-body">
        <h5 class="card-title mb-2"><i class="bx bx-map-pin"></i> Lieux de départ (Starting from)</h5>
        <p class="text-muted small mb-3">Définissez les lieux (ex. Casablanca, Paris). Associez ensuite chaque vol Aller/Retour à un lieu via le champ « Lieu de départ » dans chaque carte vol.</p>
        <div id="departure-places-inline-container">
            @foreach($placesList as $pi => $place)
            <div class="card mb-2 departure-place-inline-row" data-index="{{ $pi }}">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-center">
                        @if(!empty($place->id))
                        <input type="hidden" name="departure_places[{{ $pi }}][id]" value="{{ $place->id }}">
                        @endif
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" name="departure_places[{{ $pi }}][name]" value="{{ old("departure_places.{$pi}.name", $place->name ?? '') }}" placeholder="Ex. Casablanca" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm" name="departure_places[{{ $pi }}][code]" value="{{ old("departure_places.{$pi}.code", $place->code ?? '') }}" placeholder="CMN">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mb-0">
                                <input type="checkbox" class="form-check-input" name="departure_places[{{ $pi }}][is_active]" value="1" {{ old("departure_places.{$pi}.is_active", $place->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label small">Actif</label>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-departure-place-inline" aria-label="Supprimer">×</button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-soft-primary" id="add-departure-place-inline"><i class="bx bx-plus"></i> Ajouter un lieu</button>
    </div>
</div>
{{-- Script legacy (add row in DOM only) — désactivé sur Edit quand travelId > 0 (géré par API) --}}
<script>
(function(){
    var container = document.getElementById('departure-places-inline-container');
    var addBtn = document.getElementById('add-departure-place-inline');
    if (!container || !addBtn) return;
    var tab = document.querySelector('#flights.tab-pane');
    var travelId = tab ? parseInt(tab.getAttribute('data-travel-id') || '0', 10) : 0;
    if (travelId > 0) return;
    function nextIndex() {
        var rows = container.querySelectorAll('.departure-place-inline-row');
        var max = -1;
        rows.forEach(function(r){ var i = parseInt(r.getAttribute('data-index'), 10); if (!isNaN(i) && i > max) max = i; });
        return max + 1;
    }
    addBtn.addEventListener('click', function(){
        if (addBtn.disabled) return;
        var idx = nextIndex();
        var div = document.createElement('div');
        div.className = 'card mb-2 departure-place-inline-row';
        div.setAttribute('data-index', idx);
        div.innerHTML = '<div class="card-body py-2"><div class="row g-2 align-items-center">' +
            '<div class="col-md-4"><input type="text" class="form-control form-control-sm" name="departure_places[' + idx + '][name]" placeholder="Ex. Casablanca" required></div>' +
            '<div class="col-md-2"><input type="text" class="form-control form-control-sm" name="departure_places[' + idx + '][code]" placeholder="CMN"></div>' +
            '<div class="col-md-2"><div class="form-check mb-0"><input type="checkbox" class="form-check-input" name="departure_places[' + idx + '][is_active]" value="1" checked><label class="form-check-label small">Actif</label></div></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-departure-place-inline" aria-label="Supprimer">×</button></div></div></div>';
        container.appendChild(div);
    });
    container.addEventListener('click', function(e){
        if (!e.target.classList.contains('remove-departure-place-inline')) return;
        var row = e.target.closest('.departure-place-inline-row');
        if (row) row.remove();
    });
})();
</script>
