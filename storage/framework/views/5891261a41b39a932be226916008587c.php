
<?php
    $departurePlaces = $departurePlaces ?? collect();
    $placesList = $departurePlaces->isEmpty() ? [] : $departurePlaces->all();
?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-2"><i class="bx bx-map-pin"></i> Lieux de départ (Starting from)</h5>
        <p class="text-muted small mb-3">Definissez ici les lieux proposes au depart ; chaque option de vol peut referencer un lieu ci-dessous.</p>
        <div id="departure-places-inline-container">
            <?php $__currentLoopData = $placesList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pi => $place): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="card mb-2 departure-place-inline-row" data-index="<?php echo e($pi); ?>">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-center">
                        <?php if(!empty($place->id)): ?>
                        <input type="hidden" name="departure_places[<?php echo e($pi); ?>][id]" value="<?php echo e($place->id); ?>">
                        <?php endif; ?>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" name="departure_places[<?php echo e($pi); ?>][name]" value="<?php echo e(old("departure_places.{$pi}.name", $place->name ?? '')); ?>" placeholder="Ex. Casablanca" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm" name="departure_places[<?php echo e($pi); ?>][code]" value="<?php echo e(old("departure_places.{$pi}.code", $place->code ?? '')); ?>" placeholder="CMN">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-0 d-block">Prix (MAD)</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="departure_places[<?php echo e($pi); ?>][price]" value="<?php echo e(old("departure_places.{$pi}.price", $place->price ?? '')); ?>" placeholder="0">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mb-0">
                                <input type="checkbox" class="form-check-input" name="departure_places[<?php echo e($pi); ?>][is_active]" value="1" <?php echo e(old("departure_places.{$pi}.is_active", $place->is_active ?? true) ? 'checked' : ''); ?>>
                                <label class="form-check-label small">Actif</label>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-departure-place-inline" aria-label="Supprimer">�-</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <button type="button" class="btn btn-sm btn-soft-primary" id="add-departure-place-inline"><i class="bx bx-plus"></i> Ajouter un lieu</button>
    </div>
</div>
<script>
(function(){
    var container = document.getElementById('departure-places-inline-container');
    var addBtn = document.getElementById('add-departure-place-inline');
    if (!container || !addBtn) return;

    function refreshDeparturePlaceSelects() {
        var rows = Array.from(container.querySelectorAll('.departure-place-inline-row'));
        rows.sort(function (a, b) {
            return parseInt(a.getAttribute('data-index'), 10) - parseInt(b.getAttribute('data-index'), 10);
        });
        var options = [{ value: '', label: '�?" Aucun �?"' }];
        rows.forEach(function (row, pos) {
            var idInput = row.querySelector('input[name*="[id]"]');
            var nameInput = row.querySelector('input[name*="[name]"]');
            var codeInput = row.querySelector('input[name*="[code]"]');
            var id = idInput && idInput.value ? String(idInput.value).trim() : '';
            var name = nameInput ? String(nameInput.value).trim() : '';
            var code = codeInput ? String(codeInput.value).trim() : '';
            var label = name || ('Lieu ' + (pos + 1));
            if (code) { label += ' (' + code + ')'; }
            var val = id ? id : ('NEW_' + pos);
            options.push({ value: val, label: label });
        });
        document.querySelectorAll('select.ve-flight-departure-place-select').forEach(function (sel) {
            var cur = sel.value;
            sel.innerHTML = '';
            options.forEach(function (o) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.label;
                sel.appendChild(opt);
            });
            if (options.some(function (o) { return o.value === cur; })) {
                sel.value = cur;
            }
        });
    }
    window.refreshDeparturePlaceSelects = refreshDeparturePlaceSelects;
    refreshDeparturePlaceSelects();

    function nextIndex() {
        var rows = container.querySelectorAll('.departure-place-inline-row');
        var max = -1;
        rows.forEach(function(r){ var i = parseInt(r.getAttribute('data-index'), 10); if (!isNaN(i) && i > max) max = i; });
        return max + 1;
    }
    addBtn.addEventListener('click', function(){
        var idx = nextIndex();
        var div = document.createElement('div');
        div.className = 'card mb-2 departure-place-inline-row';
        div.setAttribute('data-index', idx);
        div.innerHTML = '<div class="card-body py-2"><div class="row g-2 align-items-center">' +
            '<div class="col-md-3"><input type="text" class="form-control form-control-sm" name="departure_places[' + idx + '][name]" placeholder="Ex. Casablanca" required></div>' +
            '<div class="col-md-2"><input type="text" class="form-control form-control-sm" name="departure_places[' + idx + '][code]" placeholder="CMN"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0 d-block">Prix (MAD)</label><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="departure_places[' + idx + '][price]" placeholder="0"></div>' +
            '<div class="col-md-2"><div class="form-check mb-0"><input type="checkbox" class="form-check-input" name="departure_places[' + idx + '][is_active]" value="1" checked><label class="form-check-label small">Actif</label></div></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-departure-place-inline" aria-label="Supprimer">�-</button></div></div></div>';
        container.appendChild(div);
        refreshDeparturePlaceSelects();
    });
    container.addEventListener('click', function(e){
        if (!e.target.classList.contains('remove-departure-place-inline')) return;
        var row = e.target.closest('.departure-place-inline-row');
        if (row) row.remove();
        refreshDeparturePlaceSelects();
    });
    container.addEventListener('input', function (e) {
        if (e.target && e.target.matches && e.target.matches('input[name*="[name]"], input[name*="[code]"]')) {
            refreshDeparturePlaceSelects();
        }
    });
})();
</script>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\_departure_places_inline.blade.php ENDPATH**/ ?>