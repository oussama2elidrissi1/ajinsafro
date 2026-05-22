<?php
    /** @var \App\Models\Hotel|null $hotel */
    $hotel = $hotel ?? null;
?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required
                       value="<?php echo e(old('name', $hotel->name ?? '')); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-center">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                           <?php echo e(old('is_active', $hotel->is_active ?? true) ? 'checked' : ''); ?>>
                    <label class="form-check-label" for="is_active">Actif</label>
                </div>
            </div>
            <div class="col-md-8">
                <label class="form-label">Adresse</label>
                <input type="text" name="address" class="form-control"
                       value="<?php echo e(old('address', $hotel->address ?? '')); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ville</label>
                <input type="text" name="city" class="form-control"
                       value="<?php echo e(old('city', $hotel->city ?? '')); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Pays</label>
                <input type="text" name="country" class="form-control"
                       value="<?php echo e(old('country', $hotel->country ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-control"><?php echo e(old('description', $hotel->description ?? '')); ?></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Latitude</label>
                <input type="text" name="latitude" class="form-control"
                       value="<?php echo e(old('latitude', $hotel->latitude ?? '')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Longitude</label>
                <input type="text" name="longitude" class="form-control"
                       value="<?php echo e(old('longitude', $hotel->longitude ?? '')); ?>">
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Galerie images</h5>
    </div>
    <div class="card-body">
        <?php if(isset($hotel) && $hotel->images->isNotEmpty()): ?>
            <div class="row g-2 mb-3">
                <?php $__currentLoopData = $hotel->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-auto text-center">
                        <div class="position-relative">
                            <img src="<?php echo e(asset('storage/'.$img->file_path)); ?>" alt="" class="rounded mb-1"
                                 style="width:80px;height:60px;object-fit:cover;">
                            <div class="form-check small">
                                <input class="form-check-input" type="checkbox" name="keep_image_ids[]"
                                       value="<?php echo e($img->id); ?>" id="keep-img-<?php echo e($img->id); ?>" checked>
                                <label class="form-check-label" for="keep-img-<?php echo e($img->id); ?>">Garder</label>
                            </div>
                            <div class="form-check small">
                                <input class="form-check-input" type="radio" name="primary_image_id"
                                       value="<?php echo e($img->id); ?>" id="primary-<?php echo e($img->id); ?>"
                                       <?php echo e($img->is_primary ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="primary-<?php echo e($img->id); ?>">Principale</label>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
        <div class="mb-2">
            <label class="form-label">Nouvelles images</label>
            <input type="file" name="images[]" class="form-control" multiple accept="image/*">
            <small class="text-muted">JPG/PNG/WebP, max 5 Mo par image.</small>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">�?quipements</h5>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <?php $__currentLoopData = $amenities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amenity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="amenities[]"
                               value="<?php echo e($amenity->id); ?>" id="amenity-<?php echo e($amenity->id); ?>"
                               <?php echo e(in_array($amenity->id, old('amenities', isset($hotel) ? $hotel->amenities->pluck('id')->all() : [])) ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="amenity-<?php echo e($amenity->id); ?>">
                            <?php if($amenity->icon): ?><i class="<?php echo e($amenity->icon); ?> me-1"></i><?php endif; ?>
                            <?php echo e($amenity->label); ?>

                        </label>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Types de chambres</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-room-type">
            <i class="bx bx-plus me-1"></i> Ajouter un type
        </button>
    </div>
    <div class="card-body">
        <div id="room-types-container" class="row g-2">
            <?php
                $oldRoomTypes = old('room_types', isset($hotel) ? $hotel->roomTypes->toArray() : []);
            ?>
            <?php $__currentLoopData = $oldRoomTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $rt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-12 room-type-row mb-2">
                    <div class="border rounded p-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Type</strong>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-room-type">&times;</button>
                        </div>
                        <div class="row g-2">
                            <input type="hidden" name="room_types[<?php echo e($idx); ?>][id]" value="<?php echo e($rt['id'] ?? ''); ?>">
                            <div class="col-md-4">
                                <input type="text" name="room_types[<?php echo e($idx); ?>][name]" class="form-control"
                                       placeholder="Nom (Suite, Double...)" value="<?php echo e($rt['name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="number" min="1" name="room_types[<?php echo e($idx); ?>][capacity_adults]" class="form-control"
                                       placeholder="Adultes" value="<?php echo e($rt['capacity_adults'] ?? 2); ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="number" min="0" name="room_types[<?php echo e($idx); ?>][capacity_children]" class="form-control"
                                       placeholder="Enfants" value="<?php echo e($rt['capacity_children'] ?? 0); ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="number" min="0" name="room_types[<?php echo e($idx); ?>][quantity]" class="form-control"
                                       placeholder="Qté" value="<?php echo e($rt['quantity'] ?? 0); ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="number" step="0.01" min="0" name="room_types[<?php echo e($idx); ?>][base_price]" class="form-control"
                                       placeholder="Prix" value="<?php echo e($rt['base_price'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <input type="text" name="room_types[<?php echo e($idx); ?>][description]" class="form-control"
                                       placeholder="Description" value="<?php echo e($rt['description'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
    <script>
        (function () {
            var container = document.getElementById('room-types-container');
            var addBtn = document.getElementById('btn-add-room-type');
            if (!container || !addBtn) return;

            function nextIndex() {
                var rows = container.querySelectorAll('.room-type-row');
                return rows.length;
            }

            addBtn.addEventListener('click', function () {
                var i = nextIndex();
                var wrapper = document.createElement('div');
                wrapper.className = 'col-12 room-type-row mb-2';
                wrapper.innerHTML =
                    '<div class="border rounded p-2 bg-light">' +
                    '<div class="d-flex justify-content-between align-items-center mb-2">' +
                    '<strong>Type</strong>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-room-type">&times;</button>' +
                    '</div>' +
                    '<div class="row g-2">' +
                    '<div class="col-md-4">' +
                    '<input type="text" name="room_types[' + i + '][name]" class="form-control" placeholder="Nom (Suite, Double...)">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                    '<input type="number" min="1" name="room_types[' + i + '][capacity_adults]" class="form-control" placeholder="Adultes" value="2">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                    '<input type="number" min="0" name="room_types[' + i + '][capacity_children]" class="form-control" placeholder="Enfants" value="0">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                    '<input type="number" min="0" name="room_types[' + i + '][quantity]" class="form-control" placeholder="Qté" value="0">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                    '<input type="number" step="0.01" min="0" name="room_types[' + i + '][base_price]" class="form-control" placeholder="Prix">' +
                    '</div>' +
                    '<div class="col-12">' +
                    '<input type="text" name="room_types[' + i + '][description]" class="form-control" placeholder="Description">' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                container.appendChild(wrapper);
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('btn-remove-room-type')) {
                    var row = e.target.closest('.room-type-row');
                    if (row) row.remove();
                }
            });
        })();
    </script>
<?php $__env->stopPush(); ?>


<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\hotels\_form.blade.php ENDPATH**/ ?>