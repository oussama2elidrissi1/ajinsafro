<?php $__env->startSection('title', 'Gérer l\'hôtel — ' . $tour->post_title); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Gérer l'hôtel du circuit</h4>
                <div class="d-flex align-items-center gap-2">
                    <?php if($hotel): ?>
    <?php if(\Illuminate\Support\Facades\Route::has('admin.circuits.tour-hotels.show')): ?>
        <a href="<?php echo e(route('admin.circuits.tour-hotels.show', $tour->ID)); ?>" class="btn btn-outline-secondary btn-sm">Voir</a>
    <?php endif; ?>
<?php endif; ?>
                    <a href="<?php echo e(route('admin.circuits.tour-hotels.index')); ?>" class="btn btn-outline-secondary btn-sm">Retour</a>
                </div>
            </div>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                <li class="breadcrumb-item"><a href="<?php echo e(route('admin.circuits.index')); ?>">Circuits</a></li>
                <li class="breadcrumb-item"><a href="<?php echo e(route('admin.circuits.tour-hotels.index')); ?>">Hôtels</a></li>
                <li class="breadcrumb-item active"><?php echo e(\Str::limit($tour->post_title, 35)); ?></li>
            </ol>
        </div>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Informations hôtel</h5>
                    <span class="text-muted small">Voyage : <?php echo e(\Str::limit($tour->post_title, 50)); ?> (ID <?php echo e($tour->ID); ?>)</span>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('admin.circuits.tour-hotels.update', $tour->ID)); ?>" method="POST" id="tour-hotel-form">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="hotel_name" class="form-label">Nom de l'hôtel</label>
                                <input type="text" class="form-control" id="hotel_name" name="hotel_name" value="<?php echo e(old('hotel_name', $hotel?->hotel_name ?? '')); ?>" placeholder="Ex. Hôtel Les Almoravides">
                            </div>
                            <div class="col-md-4">
                                <label for="stars" class="form-label">Étoiles (0–5)</label>
                                <input type="number" class="form-control" id="stars" name="stars" value="<?php echo e(old('stars', $hotel?->stars ?? '')); ?>" min="0" max="5" placeholder="3">
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo e(old('address', $hotel?->address ?? '')); ?>" placeholder="Ville, pays">
                            </div>
                            <div class="col-md-6">
                                <label for="room_type" class="form-label">Type de chambre (résumé)</label>
                                <input type="text" class="form-control" id="room_type" name="room_type" value="<?php echo e(old('room_type', $hotel?->room_type ?? '')); ?>" placeholder="Ex. Chambre double">
                            </div>
                            <div class="col-md-6">
                                <label for="meal_plan" class="form-label">Formule repas</label>
                                <input type="text" class="form-control" id="meal_plan" name="meal_plan" value="<?php echo e(old('meal_plan', $hotel?->meal_plan ?? '')); ?>" placeholder="Ex. Petit-déjeuner inclus">
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Informations complémentaires…"><?php echo e(old('notes', $hotel?->notes ?? '')); ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3">Types de chambres</h5>
                        <p class="text-muted small mb-3">Définir les types de chambres (nom, capacité, prix, supplément, nombre de personnes, etc.).</p>

                        <div id="tour-hotel-rooms-container">
                            <?php
                                $roomTypes = ['Single' => 'Single', 'Double' => 'Double', 'Twin' => 'Twin', 'Triple' => 'Triple', 'Quadruple' => 'Quadruple', 'Suite' => 'Suite', 'Family Room' => 'Family Room', 'Chambre communicante' => 'Chambre communicante', 'Autre' => 'Autre'];
                                $roomsList = old('rooms', $hotel && $hotel->rooms->isNotEmpty() ? $hotel->rooms->all() : [null]);
                                if (empty($roomsList)) $roomsList = [null];
                            ?>
                            <?php $__currentLoopData = $roomsList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ri => $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $room = is_object($room) ? $room : (is_array($room) ? (object)$room : null);
                                    $roomId = optional($room)->id ?? '';
                                    $roomTypeVal = old("rooms.{$ri}.room_type", optional($room)->room_type ?? 'Double');
                                    $roomLabelVal = old("rooms.{$ri}.room_label", optional($room)->room_label ?? '');
                                    $roomCodeVal = old("rooms.{$ri}.room_code", optional($room)->room_code ?? '');
                                    $roomCountVal = old("rooms.{$ri}.room_count", optional($room)->room_count ?? 1);
                                    $capAdultsVal = old("rooms.{$ri}.capacity_adults", optional($room)->capacity_adults ?? 0);
                                    $capChildrenVal = old("rooms.{$ri}.capacity_children", optional($room)->capacity_children ?? 0);
                                    $capTotalVal = old("rooms.{$ri}.capacity_total", optional($room)->capacity_total ?? 1);
                                    $supplementVal = old("rooms.{$ri}.supplement", optional($room)->supplement ?? 0);
                                    $descVal = old("rooms.{$ri}.description", optional($room)->description ?? '');
                                    $notesVal = old("rooms.{$ri}.notes", optional($room)->notes ?? '');
                                    $isActiveVal = old("rooms.{$ri}.is_active", optional($room)->is_active ?? true);
                                    $isDefaultVal = old("rooms.{$ri}.is_default", optional($room)->is_default ?? false);
                                ?>
                                <div class="card mb-2 tour-hotel-room-row" data-room-index="<?php echo e($ri); ?>">
                                    <div class="card-body py-2">
                                        <?php if($roomId): ?><input type="hidden" name="rooms[<?php echo e($ri); ?>][id]" value="<?php echo e($roomId); ?>"><?php endif; ?>
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-2">
                                                <label class="form-label small">Type</label>
                                                <select class="form-select form-select-sm" name="rooms[<?php echo e($ri); ?>][room_type]">
                                                    <?php $__currentLoopData = $roomTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($k); ?>" <?php echo e($roomTypeVal == $k ? 'selected' : ''); ?>><?php echo e($v); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small">Nb ch.</label>
                                                <input type="number" class="form-control form-control-sm" name="rooms[<?php echo e($ri); ?>][room_count]" value="<?php echo e($roomCountVal); ?>" min="1">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small">Cap. ad.</label>
                                                <input type="number" class="form-control form-control-sm" name="rooms[<?php echo e($ri); ?>][capacity_adults]" value="<?php echo e($capAdultsVal); ?>" min="0">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small">Cap. enf.</label>
                                                <input type="number" class="form-control form-control-sm" name="rooms[<?php echo e($ri); ?>][capacity_children]" value="<?php echo e($capChildrenVal); ?>" min="0">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label small">Cap. tot.</label>
                                                <input type="number" class="form-control form-control-sm" name="rooms[<?php echo e($ri); ?>][capacity_total]" value="<?php echo e($capTotalVal); ?>" min="1">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Suppl. (DH)</label>
                                                <input type="number" class="form-control form-control-sm" name="rooms[<?php echo e($ri); ?>][supplement]" value="<?php echo e($supplementVal); ?>" min="0" step="0.01">
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" name="rooms[<?php echo e($ri); ?>][is_default]" value="1" <?php echo e($isDefaultVal ? 'checked' : ''); ?>>
                                                    <label class="form-check-label small">Défaut</label>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-sm btn-outline-danger tour-hotel-remove-room" data-room-index="<?php echo e($ri); ?>" aria-label="Supprimer">×</button>
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-1">
                                            <div class="col-md-2">
                                                <label class="form-label small">Code</label>
                                                <input type="text" class="form-control form-control-sm" name="rooms[<?php echo e($ri); ?>][room_code]" value="<?php echo e($roomCodeVal); ?>" placeholder="Ex. DBL-STD">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Libellé</label>
                                                <input type="text" class="form-control form-control-sm" name="rooms[<?php echo e($ri); ?>][room_label]" value="<?php echo e($roomLabelVal); ?>" placeholder="Optionnel">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Description</label>
                                                <input type="text" class="form-control form-control-sm" name="rooms[<?php echo e($ri); ?>][description]" value="<?php echo e($descVal); ?>" placeholder="Courte description">
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-check mt-2">
                                                    <input type="checkbox" class="form-check-input" name="rooms[<?php echo e($ri); ?>][is_active]" value="1" <?php echo e($isActiveVal ? 'checked' : ''); ?>>
                                                    <label class="form-check-label small">Actif</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-1">
                                            <div class="col-12">
                                                <label class="form-label small">Notes</label>
                                                <input type="text" class="form-control form-control-sm" name="rooms[<?php echo e($ri); ?>][notes]" value="<?php echo e($notesVal); ?>" placeholder="Optionnel">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-soft-primary mb-3" id="tour-hotel-add-room"><i class="bx bx-plus"></i> Ajouter un type de chambre</button>

                        <hr>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="<?php echo e(route('admin.circuits.tour-hotels.index')); ?>" class="btn btn-secondary">Annuler</a>
                            <a href="<?php echo e(route('admin.circuits.voyages.edit', $tour->ID)); ?>" class="btn btn-soft-primary">Modifier le voyage</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Voyage lié</h5>
                </div>
                <div class="card-body small">
                    <p class="mb-1"><strong><?php echo e(\Str::limit($tour->post_title, 40)); ?></strong></p>
                    <p class="text-muted mb-2">ID <?php echo e($tour->ID); ?></p>
                    <a href="<?php echo e(route('admin.circuits.voyages.edit', $tour->ID)); ?>" class="btn btn-outline-primary btn-sm me-1">Modifier le voyage</a>
                    <a href="<?php echo e(route('admin.circuits.voyages.show', $tour->ID)); ?>" class="btn btn-outline-secondary btn-sm">Fiche voyage</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function(){
        var container = document.getElementById('tour-hotel-rooms-container');
        var addBtn = document.getElementById('tour-hotel-add-room');
        if (!container || !addBtn) return;

        function reindexRooms() {
            container.querySelectorAll('.tour-hotel-room-row').forEach(function(row, i) {
                row.setAttribute('data-room-index', i);
                row.querySelectorAll('[name^="rooms["]').forEach(function(inp) {
                    inp.name = inp.name.replace(/rooms\[\d+\]/, 'rooms[' + i + ']');
                });
                row.querySelectorAll('.tour-hotel-remove-room').forEach(function(btn) { btn.setAttribute('data-room-index', i); });
            });
        }

        addBtn.addEventListener('click', function() {
            var rows = container.querySelectorAll('.tour-hotel-room-row');
            var last = rows[rows.length - 1];
            if (!last) return;
            var nextIndex = rows.length;
            var clone = last.cloneNode(true);
            clone.setAttribute('data-room-index', nextIndex);
            clone.querySelectorAll('input[name^="rooms["][name*="[id]"]').forEach(function(inp) { inp.remove(); });
            clone.querySelectorAll('[name]').forEach(function(inp) {
                if (inp.name && inp.name.indexOf('rooms[') === 0) {
                    inp.name = inp.name.replace(/rooms\[\d+\]/, 'rooms[' + nextIndex + ']');
                    if (inp.name.indexOf('[id]') !== -1) return;
                    if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
                    if (inp.tagName === 'TEXTAREA') inp.value = '';
                    if (inp.type === 'checkbox') inp.checked = (inp.name.indexOf('is_default') !== -1 ? false : true);
                }
            });
            clone.querySelectorAll('.tour-hotel-remove-room').forEach(function(btn) { btn.setAttribute('data-room-index', nextIndex); });
            container.appendChild(clone);
            reindexRooms();
        });

        container.addEventListener('click', function(e) {
            if (!e.target.classList.contains('tour-hotel-remove-room')) return;
            var row = e.target.closest('.tour-hotel-room-row');
            if (!row || container.querySelectorAll('.tour-hotel-room-row').length <= 1) return;
            row.remove();
            reindexRooms();
        });
    })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\tour-hotels\edit.blade.php ENDPATH**/ ?>