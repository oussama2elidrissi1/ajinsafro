<?php
    $rooms = $departureHotel->rooms ?? collect();
    $modalAjax = $modalAjax ?? false;
?>

<div data-departure-hotel-section="<?php echo e($departureHotel->id); ?>">
<div class="mb-2">
    <p class="text-muted small mb-0">
        <i class="bx bx-layer me-1"></i>
        Stock <strong>propre à ce départ</strong> �?" total physique, réservé (engagements + attentes comptées dans « dispo »), et disponible réel.
    </p>
</div>

<?php $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <form id="room-update-form-<?php echo e($r->id); ?>" method="post" action="<?php echo e(route('admin.circuits.voyages.departures.rooms.update', [$voyage, $r])); ?>" class="d-none <?php echo e($modalAjax ? 'ra-modal-ajax-form' : ''); ?>">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <?php echo $__env->make('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </form>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<div class="table-responsive border rounded">
    <table class="table table-hover table-sm align-middle mb-0">
        <thead class="table-light">
            <tr class="text-nowrap">
                <th scope="col">Type de chambre</th>
                <th scope="col" class="text-center">Chambres (total / rés. / dispo)</th>
                <th scope="col" class="text-center">Capacité / chambre</th>
                <th scope="col" class="text-center">Places (total / rés. / dispo)</th>
                <th scope="col" class="text-center">Supplément</th>
                <th scope="col" class="text-center">Statut</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $capR = max(1, (int) $r->capacity_total);
                    $totRooms = (int) ($r->total_rooms ?? $r->available_rooms);
                    $manualPlaces = ((int) $r->total_places) !== ($totRooms * $capR);
                ?>
                <tr id="room-row-<?php echo e($r->id); ?>" class="room-stock-row">
                    <td>
                        <label class="form-label small text-muted mb-0 d-md-none">Type</label>
                        <input type="text" name="room_type" form="room-update-form-<?php echo e($r->id); ?>" class="form-control form-control-sm" value="<?php echo e($r->room_type); ?>" required maxlength="120">
                    </td>
                    <td class="text-center" style="min-width:140px">
                        <label class="form-label small text-muted mb-0 d-md-none">Chambres</label>
                        <input type="number" name="total_rooms" form="room-update-form-<?php echo e($r->id); ?>" class="form-control form-control-sm text-center js-total-rooms-input" value="<?php echo e($totRooms); ?>" min="0" required data-row="<?php echo e($r->id); ?>">
                        <div class="small text-muted mt-1">
                            Rés. <span class="fw-medium text-body"><?php echo e((int) $r->reserved_rooms); ?></span>
                            · Dispo <span class="fw-medium text-success"><?php echo e((int) $r->available_rooms); ?></span>
                        </div>
                    </td>
                    <td class="text-center" style="max-width:100px">
                        <label class="form-label small text-muted mb-0 d-md-none">Cap./ch.</label>
                        <input type="number" name="capacity_total" form="room-update-form-<?php echo e($r->id); ?>" class="form-control form-control-sm text-center js-capacity-input" value="<?php echo e($r->capacity_total); ?>" min="1" required data-row="<?php echo e($r->id); ?>">
                    </td>
                    <td class="text-center" style="min-width:140px">
                        <label class="form-label small text-muted mb-0 d-md-none">Places</label>
                        <input type="number" name="available_places" form="room-update-form-<?php echo e($r->id); ?>" id="places-<?php echo e($r->id); ?>" class="form-control form-control-sm text-center js-places-input" value="<?php echo e((int) $r->total_places); ?>" min="0" data-row="<?php echo e($r->id); ?>">
                        <div class="small text-muted mt-1">
                            Rés. <span class="fw-medium text-body"><?php echo e((int) $r->reserved_places); ?></span>
                            · Dispo <span class="fw-medium text-success"><?php echo e((int) $r->available_places); ?></span>
                        </div>
                        <div class="form-check form-check-inline mt-1 mb-0">
                            <input class="form-check-input js-manual-places" type="checkbox" name="manual_places" form="room-update-form-<?php echo e($r->id); ?>" value="1" id="manual-<?php echo e($r->id); ?>" data-row="<?php echo e($r->id); ?>" <?php echo e($manualPlaces ? 'checked' : ''); ?>>
                            <label class="form-check-label small text-muted" for="manual-<?php echo e($r->id); ?>" title="Si coché, les places totales ne suivent pas chambres �- capacité">Manuel</label>
                        </div>
                    </td>
                    <td class="text-center" style="max-width:100px">
                        <label class="form-label small text-muted mb-0 d-md-none">Supp.</label>
                        <input type="number" name="supplement" form="room-update-form-<?php echo e($r->id); ?>" class="form-control form-control-sm text-center" value="<?php echo e($r->supplement); ?>" min="0" step="0.01">
                    </td>
                    <td style="min-width:130px">
                        <label class="form-label small text-muted mb-0 d-md-none">Statut</label>
                        <select name="status" form="room-update-form-<?php echo e($r->id); ?>" class="form-select form-select-sm">
                            <?php $__currentLoopData = $roomStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($rs); ?>" <?php echo e($r->status === $rs ? 'selected' : ''); ?>><?php echo e(\App\Models\DepartureHotelRoom::statusLabel($rs)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </td>
                    <td class="text-end text-nowrap">
                        <button type="submit" form="room-update-form-<?php echo e($r->id); ?>" class="btn btn-sm btn-primary">Enregistrer</button>
                        <form method="post" action="<?php echo e(route('admin.circuits.voyages.departures.rooms.destroy', [$voyage, $r])); ?>" class="d-inline ra-modal-ajax-form" data-confirm-msg="Supprimer ce type de chambre pour ce départ ?">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <?php echo $__env->make('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" class="text-muted text-center py-4">Aucun type de chambre. Ajoutez une ligne ci-dessous.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card border-0 bg-light mt-3">
    <div class="card-body py-3">
        <h6 class="small fw-semibold text-uppercase text-muted mb-3">Ajouter un type de chambre</h6>
        <form method="post" action="<?php echo e(route('admin.circuits.voyages.departures.rooms.store', [$voyage, $departureHotel])); ?>" class="row g-2 align-items-end js-add-room-form ra-modal-ajax-form">
            <?php echo csrf_field(); ?>
            <?php echo $__env->make('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <div class="col-md-2">
                <label class="form-label small mb-0">Type de chambre</label>
                <input type="text" name="room_type" class="form-control form-control-sm" required placeholder="ex. Double" maxlength="120">
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-0">Chambres tot.</label>
                <input type="number" name="total_rooms" class="form-control form-control-sm js-add-rooms" min="0" value="0" required>
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-0">Cap./ch.</label>
                <input type="number" name="capacity_total" class="form-control form-control-sm js-add-cap" min="1" value="2" required>
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-0">Places tot.</label>
                <input type="number" name="available_places" class="form-control form-control-sm js-add-places" min="0" placeholder="auto">
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-0">Supp.</label>
                <input type="number" name="supplement" class="form-control form-control-sm" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Statut</label>
                <select name="status" class="form-select form-select-sm">
                    <?php $__currentLoopData = $roomStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($rs); ?>" <?php echo e($rs === 'available' ? 'selected' : ''); ?>><?php echo e(\App\Models\DepartureHotelRoom::statusLabel($rs)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="manual_places" value="1" id="manual_new_<?php echo e($departureHotel->id); ?>">
                    <label class="form-check-label small" for="manual_new_<?php echo e($departureHotel->id); ?>">Places manuelles</label>
                </div>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Ajouter</button>
            </div>
        </form>
    </div>
</div>
</div>

<script>
(function() {
    var section = document.querySelector('[data-departure-hotel-section="<?php echo e($departureHotel->id); ?>"]');
    function recalcPlaces(rowId) {
        var manual = document.getElementById('manual-' + rowId);
        if (manual && manual.checked) return;
        var row = document.getElementById('room-row-' + rowId);
        if (!row) return;
        var rooms = parseInt(row.querySelector('.js-total-rooms-input')?.value || '0', 10) || 0;
        var cap = parseInt(row.querySelector('.js-capacity-input')?.value || '1', 10) || 1;
        var placesEl = document.getElementById('places-' + rowId);
        if (placesEl) placesEl.value = rooms * cap;
    }
    if (!section) return;
    section.querySelectorAll('.room-stock-row').forEach(function(tr) {
        var id = tr.id.replace('room-row-', '');
        tr.querySelectorAll('.js-total-rooms-input, .js-capacity-input').forEach(function(inp) {
            inp.addEventListener('input', function() { recalcPlaces(id); });
        });
        var manual = document.getElementById('manual-' + id);
        if (manual) {
            manual.addEventListener('change', function() {
                if (!this.checked) recalcPlaces(id);
            });
        }
    });
    var addRooms = section.querySelector('.js-add-rooms');
    var addCap = section.querySelector('.js-add-cap');
    var addPlaces = section.querySelector('.js-add-places');
    var addManual = section.querySelector('#manual_new_<?php echo e($departureHotel->id); ?>');
    function recalcAdd() {
        if (addManual && addManual.checked) return;
        var r = parseInt(addRooms?.value || '0', 10) || 0;
        var c = parseInt(addCap?.value || '1', 10) || 1;
        if (addPlaces) addPlaces.placeholder = String(r * c);
    }
    if (addRooms) addRooms.addEventListener('input', recalcAdd);
    if (addCap) addCap.addEventListener('input', recalcAdd);
    if (addManual) addManual.addEventListener('change', recalcAdd);
})();
</script>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\departures\partials\_rooms_table.blade.php ENDPATH**/ ?>