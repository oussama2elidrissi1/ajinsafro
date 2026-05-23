<?php
    /** @var \App\Models\Wp\Activity|null $activity */
    $activity = $activity ?? null;
    $mediaService = app(\App\Services\WordPressMediaService::class);

    $initialGalleryIds = old('existing_gallery_image_ids');
    if ($initialGalleryIds === null) {
        $initialGalleryIds = [];


        if ($activity) {
            $stored = $activity->gallery_image_ids;
            if (is_array($stored)) {
                $initialGalleryIds = $stored;
            } elseif (is_string($stored) && trim($stored) !== '') {
                $decoded = json_decode($stored, true);
                $initialGalleryIds = is_array($decoded) ? $decoded : explode(',', $stored);
            }

            if ($initialGalleryIds === [] && (int) ($activity->image_id ?? 0) > 0) {
                $initialGalleryIds[] = (int) $activity->image_id;
            }
        }
    }
    

    $initialGallery = collect($initialGalleryIds)
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->map(function (int $id) use ($mediaService) {
            return [
                'id' => $id,
                'url' => $mediaService->getAttachmentUrl($id),
            ];
        })
        ->filter(fn ($item) => ! empty($item['url']))
        ->values();

    $isActive = old('is_active', $activity?->is_active ?? true);
?>

<form action="<?php echo e($formAction); ?>" method="POST" enctype="multipart/form-data" data-activity-form>
    <?php echo csrf_field(); ?>
    <?php if(($formMethod ?? 'POST') !== 'POST'): ?>
        <?php echo method_field($formMethod); ?>
    <?php endif; ?>

    <input type="hidden" name="gallery_state_present" value="1">

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Informations principales</h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Nom de l activite <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="title"
                                name="title"
                                value="<?php echo e(old('title', $activity?->title)); ?>"
                                required
                            >
                            <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="activity_type" class="form-label">Type d activite <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control <?php $__errorArgs = ['activity_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="activity_type"
                                name="activity_type"
                                value="<?php echo e(old('activity_type', $activity?->activity_type)); ?>"
                                placeholder="Ex: randonnee, excursion, quad"
                                required
                            >
                            <?php $__errorArgs = ['activity_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="region_name" class="form-label">Region / destination <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control <?php $__errorArgs = ['region_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="region_name"
                                name="region_name"
                                value="<?php echo e(old('region_name', $activity?->region_name ?? $activity?->location_text)); ?>"
                                placeholder="Ex: Merzouga"
                                required
                            >
                            <?php $__errorArgs = ['region_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="slug" class="form-label">Slug</label>
                            <input
                                type="text"
                                class="form-control <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="slug"
                                name="slug"
                                value="<?php echo e(old('slug', $activity?->slug)); ?>"
                                placeholder="Genere automatiquement si vide"
                            >
                            <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                class="form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="description"
                                name="description"
                                rows="6"
                            ><?php echo e(old('description', $activity?->description)); ?></textarea>
                            <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Tarification et contraintes</h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="adult_price" class="form-label">Prix adulte <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    class="form-control <?php $__errorArgs = ['adult_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="adult_price"
                                    name="adult_price"
                                    value="<?php echo e(old('adult_price', $activity?->adult_price ?? $activity?->base_price)); ?>"
                                    min="0"
                                    step="0.01"
                                    required
                                >
                                <span class="input-group-text">MAD</span>
                            </div>
                            <?php $__errorArgs = ['adult_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="child_price" class="form-label">Prix enfant <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    class="form-control <?php $__errorArgs = ['child_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="child_price"
                                    name="child_price"
                                    value="<?php echo e(old('child_price', $activity?->child_price)); ?>"
                                    min="0"
                                    step="0.01"
                                    required
                                >
                                <span class="input-group-text">MAD</span>
                            </div>
                            <?php $__errorArgs = ['child_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-md-3">
                            <label for="min_age" class="form-label">Age minimum <span class="text-danger">*</span></label>
                            <input
                                type="number"
                                class="form-control <?php $__errorArgs = ['min_age'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="min_age"
                                name="min_age"
                                value="<?php echo e(old('min_age', $activity?->min_age)); ?>"
                                min="0"
                                max="120"
                                required
                            >
                            <?php $__errorArgs = ['min_age'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-md-3">
                            <label for="max_age" class="form-label">Age maximum <span class="text-danger">*</span></label>
                            <input
                                type="number"
                                class="form-control <?php $__errorArgs = ['max_age'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="max_age"
                                name="max_age"
                                value="<?php echo e(old('max_age', $activity?->max_age)); ?>"
                                min="0"
                                max="120"
                                required
                            >
                            <?php $__errorArgs = ['max_age'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="default_duration_minutes" class="form-label">Duree de l activite <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    class="form-control <?php $__errorArgs = ['default_duration_minutes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    id="default_duration_minutes"
                                    name="default_duration_minutes"
                                    value="<?php echo e(old('default_duration_minutes', $activity?->default_duration_minutes)); ?>"
                                    min="1"
                                    step="1"
                                    required
                                >
                                <span class="input-group-text">minutes</span>
                            </div>
                            <?php $__errorArgs = ['default_duration_minutes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="icon" class="form-label">Icone</label>
                            <input
                                type="text"
                                class="form-control <?php $__errorArgs = ['icon'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="icon"
                                name="icon"
                                value="<?php echo e(old('icon', $activity?->icon)); ?>"
                                placeholder="Ex: bx-map-pin"
                            >
                            <?php $__errorArgs = ['icon'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Galerie d images</h4>

                    <div class="mb-3">
                        <label for="gallery_images" class="form-label">Ajouter des images <span class="text-danger">*</span></label>
                        <input
                            type="file"
                            class="form-control <?php $__errorArgs = ['gallery_images'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php $__errorArgs = ['gallery_images.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            id="gallery_images"
                            name="gallery_images[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                        >
                        <div class="form-text">Upload multiple actif. La premiere image conservee sert de visuel principal.</div>
                        <?php $__errorArgs = ['gallery_images'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <?php $__errorArgs = ['gallery_images.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0">Images actuelles</h6>
                            <small class="text-muted" data-existing-count><?php echo e($initialGallery->count()); ?></small>
                        </div>

                        <div class="row g-3" id="activity-existing-gallery">
                            <?php $__empty_1 = true; $__currentLoopData = $initialGallery; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <div class="col-sm-6" data-existing-gallery-item>
                                    <input type="hidden" name="existing_gallery_image_ids[]" value="<?php echo e($image['id']); ?>">
                                    <div class="border rounded-3 overflow-hidden h-100 bg-light">
                                        <img
                                            src="<?php echo e($image['url']); ?>"
                                            alt="Image <?php echo e($loop->iteration); ?>"
                                            class="w-100"
                                            style="height: 120px; object-fit: cover;"
                                        >
                                        <div class="p-2">
                                            <div class="small text-muted mb-2">Image #<?php echo e($image['id']); ?></div>
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100" data-remove-existing-gallery>
                                                Retirer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="col-12 text-muted small" data-existing-gallery-empty>
                                    Aucune image enregistree pour le moment.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0">Nouvelles images</h6>
                            <small class="text-muted" data-new-count>0</small>
                        </div>
                        <div class="row g-3" id="activity-new-gallery-preview">
                            <div class="col-12 text-muted small" data-new-gallery-empty>
                                Aucune nouvelle image selectionnee.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Publication</h4>

                    <div class="form-check form-switch mb-4">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="is_active"
                            name="is_active"
                            value="1"
                            <?php echo e($isActive ? 'checked' : ''); ?>

                        >
                        <label class="form-check-label" for="is_active">Activite active</label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><?php echo e($submitLabel); ?></button>
                        <a href="<?php echo e(route('admin.circuits.activities.index')); ?>" class="btn btn-light">Retour a la liste</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    var form = document.querySelector('[data-activity-form]');
    if (!form || form.dataset.galleryReady === '1') {
        return;
    }
    form.dataset.galleryReady = '1';

    var input = form.querySelector('#gallery_images');
    var preview = form.querySelector('#activity-new-gallery-preview');
    var newEmpty = form.querySelector('[data-new-gallery-empty]');
    var newCount = form.querySelector('[data-new-count]');
    var existingWrap = form.querySelector('#activity-existing-gallery');
    var existingCount = form.querySelector('[data-existing-count]');

    function refreshExistingState() {
        if (!existingWrap) {
            return;
        }

        var items = existingWrap.querySelectorAll('[data-existing-gallery-item]');
        var empty = existingWrap.querySelector('[data-existing-gallery-empty]');

        if (existingCount) {
            existingCount.textContent = String(items.length);
        }

        if (!items.length && !empty) {
            var node = document.createElement('div');
            node.className = 'col-12 text-muted small';
            node.setAttribute('data-existing-gallery-empty', '1');
            node.textContent = 'Aucune image enregistree pour le moment.';
            existingWrap.appendChild(node);
        }

        if (items.length && empty) {
            empty.remove();
        }
    }

    function renderNewPreview() {
        if (!preview || !input) {
            return;
        }

        preview.querySelectorAll('[data-new-gallery-item]').forEach(function (node) {
            node.remove();
        });

        var files = Array.from(input.files || []);
        if (newCount) {
            newCount.textContent = String(files.length);
        }

        if (!files.length) {
            if (newEmpty) {
                newEmpty.classList.remove('d-none');
            }
            return;
        }

        if (newEmpty) {
            newEmpty.classList.add('d-none');
        }

        files.forEach(function (file, index) {
            var col = document.createElement('div');
            col.className = 'col-sm-6';
            col.setAttribute('data-new-gallery-item', String(index));

            var card = document.createElement('div');
            card.className = 'border rounded-3 overflow-hidden h-100 bg-light';

            var img = document.createElement('img');
            img.className = 'w-100';
            img.style.height = '120px';
            img.style.objectFit = 'cover';
            img.alt = file.name || ('Image ' + (index + 1));
            img.src = URL.createObjectURL(file);
            img.addEventListener('load', function () {
                URL.revokeObjectURL(img.src);
            });

            var meta = document.createElement('div');
            meta.className = 'p-2 small text-muted';
            meta.textContent = file.name || ('Image ' + (index + 1));

            card.appendChild(img);
            card.appendChild(meta);
            col.appendChild(card);
            preview.appendChild(col);
        });
    }

    if (input) {
        input.addEventListener('change', renderNewPreview);
    }

    form.addEventListener('click', function (event) {
        var removeBtn = event.target.closest('[data-remove-existing-gallery]');
        if (!removeBtn) {
            return;
        }

        var item = removeBtn.closest('[data-existing-gallery-item]');
        if (item) {
            item.remove();
            refreshExistingState();
        }
    });

    refreshExistingState();
    renderNewPreview();
})();
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\activities\_form.blade.php ENDPATH**/ ?>