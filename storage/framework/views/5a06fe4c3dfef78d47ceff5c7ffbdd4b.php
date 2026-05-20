<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['hotel' => null, 'stHotel' => null, 'meta' => [], 'galleryUrls' => [], 'featuredUrl' => null]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['hotel' => null, 'stHotel' => null, 'meta' => [], 'galleryUrls' => [], 'featuredUrl' => null]); ?>
<?php foreach (array_filter((['hotel' => null, 'stHotel' => null, 'meta' => [], 'galleryUrls' => [], 'featuredUrl' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $isEdit = $hotel !== null;
    $postTitle = old('post_title', $hotel->post_title ?? '');
    $postExcerpt = old('post_excerpt', $hotel->post_excerpt ?? '');
    $postContent = old('post_content', $hotel->post_content ?? '');
    $postStatus = old('post_status', $hotel->post_status ?? 'publish');
    $postName = old('post_name', $hotel->post_name ?? '');
    $address = old('address', $stHotel->address ?? '');
    $hotelStar = old('hotel_star', $stHotel->hotel_star ?? '');
    $minPrice = old('min_price', $stHotel->min_price ?? '');
    $mapLat = old('map_lat', $stHotel->map_lat ?? '');
    $mapLng = old('map_lng', $stHotel->map_lng ?? '');
    $isFeatured = old('is_featured', $stHotel->is_featured ?? 'off');
    $hotelAmenities = old('hotel_amenities', $meta['hotel_amenities'] ?? '');
    $hotelPolicies = old('hotel_policies', $meta['hotel_policies'] ?? '');
    $hotelPhone = old('hotel_phone', $meta['hotel_phone'] ?? '');
    $hotelEmail = old('hotel_email', $meta['hotel_email'] ?? '');
?>

<div class="mb-3">
    <label for="post_title" class="form-label">Titre <span class="text-danger">*</span></label>
    <input type="text" class="form-control <?php $__errorArgs = ['post_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="post_title" name="post_title" value="<?php echo e($postTitle); ?>" required maxlength="255">
    <?php $__errorArgs = ['post_title'];
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

<div class="mb-3">
    <label for="post_excerpt" class="form-label">Resume court</label>
    <textarea class="form-control <?php $__errorArgs = ['post_excerpt'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="post_excerpt" name="post_excerpt" rows="3" maxlength="500"><?php echo e($postExcerpt); ?></textarea>
    <?php $__errorArgs = ['post_excerpt'];
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

<div class="mb-3">
    <label for="post_content" class="form-label">Contenu</label>
    <textarea class="form-control <?php $__errorArgs = ['post_content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="post_content" name="post_content" rows="4"><?php echo e($postContent); ?></textarea>
    <?php $__errorArgs = ['post_content'];
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

<div class="mb-3">
    <label for="post_status" class="form-label">Statut <span class="text-danger">*</span></label>
    <select class="form-select <?php $__errorArgs = ['post_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="post_status" name="post_status" required>
        <option value="publish" <?php echo e($postStatus === 'publish' ? 'selected' : ''); ?>>Publié</option>
        <option value="draft" <?php echo e($postStatus === 'draft' ? 'selected' : ''); ?>>Brouillon</option>
    </select>
    <?php $__errorArgs = ['post_status'];
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

<div class="mb-3">
    <label for="post_name" class="form-label">Slug (optionnel)</label>
    <input type="text" class="form-control <?php $__errorArgs = ['post_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="post_name" name="post_name" value="<?php echo e($postName); ?>" placeholder="Auto si vide" maxlength="200">
    <small class="text-muted">Laissez vide pour générer automatiquement à partir du titre.</small>
    <?php $__errorArgs = ['post_name'];
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

<div class="mb-3">
    <label for="featured_image" class="form-label">Image à la une</label>
    <?php if($featuredUrl): ?>
        <div class="mb-2">
            <img src="<?php echo e($featuredUrl); ?>" alt="Image à la une" class="img-thumbnail" style="max-height: 120px;">
            <span class="text-muted small d-block">Remplacer en choisissant un nouveau fichier.</span>
        </div>
        <?php if($isEdit): ?>
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" name="remove_featured_image" id="remove_featured_image" value="1" <?php if(old('remove_featured_image')): echo 'checked'; endif; ?>>
                <label class="form-check-label" for="remove_featured_image">Retirer l'image à la une</label>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <input type="file" class="form-control <?php $__errorArgs = ['featured_image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="featured_image" name="featured_image" accept="image/jpeg,image/png,image/webp">
    <small class="text-muted">JPG, PNG, WebP. Max 5 Mo. Stocké dans wp-content/uploads/Y/m/</small>
    <?php $__errorArgs = ['featured_image'];
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

<div class="mb-3">
    <label for="gallery_images" class="form-label">Galerie d'images</label>
    <?php if(!empty($galleryUrls)): ?>
        <div class="d-flex flex-wrap gap-2 mb-2" id="gallery-current">
            <?php $__currentLoopData = $galleryUrls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="gallery-item position-relative d-inline-block" data-id="<?php echo e($item['id']); ?>">
                    <img src="<?php echo e($item['url']); ?>" alt="Galerie" class="img-thumbnail" style="height: 80px; width: auto;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 gallery-remove" style="transform: translate(50%, -50%);" title="Retirer de la galerie"><i class="bx bx-trash font-size-12"></i></button>
                    <input type="hidden" name="gallery_keep_ids[]" value="<?php echo e($item['id']); ?>">
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
    <input type="file" class="form-control <?php $__errorArgs = ['gallery_images'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="gallery_images" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small class="text-muted">Max 10 fichiers. JPG, PNG, WebP. 5 Mo par fichier.</small>
    <?php $__errorArgs = ['gallery_images'];
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

<div class="mb-3">
    <label for="address" class="form-label">Adresse</label>
    <input type="text" class="form-control <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="address" name="address" value="<?php echo e($address); ?>">
    <?php $__errorArgs = ['address'];
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

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="hotel_star" class="form-label">�?toiles (1�?"5)</label>
        <select class="form-select <?php $__errorArgs = ['hotel_star'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="hotel_star" name="hotel_star">
            <option value="">�?"</option>
            <?php for($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo e($i); ?>" <?php echo e((string)$i === (string)$hotelStar ? 'selected' : ''); ?>><?php echo e($i); ?> étoile(s)</option>
            <?php endfor; ?>
        </select>
        <?php $__errorArgs = ['hotel_star'];
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
    <div class="col-md-6 mb-3">
        <label for="min_price" class="form-label">Prix minimum</label>
        <input type="number" step="0.01" min="0" class="form-control <?php $__errorArgs = ['min_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="min_price" name="min_price" value="<?php echo e($minPrice); ?>">
        <?php $__errorArgs = ['min_price'];
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

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="map_lat" class="form-label">Latitude</label>
        <input type="text" class="form-control <?php $__errorArgs = ['map_lat'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="map_lat" name="map_lat" value="<?php echo e($mapLat); ?>">
        <?php $__errorArgs = ['map_lat'];
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
    <div class="col-md-6 mb-3">
        <label for="map_lng" class="form-label">Longitude</label>
        <input type="text" class="form-control <?php $__errorArgs = ['map_lng'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="map_lng" name="map_lng" value="<?php echo e($mapLng); ?>">
        <?php $__errorArgs = ['map_lng'];
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

<div class="mb-3">
    <div class="form-check form-switch">
        <input type="hidden" name="is_featured" value="off">
        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="on" <?php echo e($isFeatured === 'on' ? 'checked' : ''); ?>>
        <label class="form-check-label" for="is_featured">�? la une</label>
    </div>
</div>

<hr class="my-4">
<h5 class="mb-3">Meta (postmeta)</h5>

<div class="mb-3">
    <label for="hotel_amenities" class="form-label">�?quipements (amenities)</label>
    <textarea class="form-control <?php $__errorArgs = ['hotel_amenities'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="hotel_amenities" name="hotel_amenities" rows="3" placeholder="JSON ou texte libre"><?php echo e($hotelAmenities); ?></textarea>
    <?php $__errorArgs = ['hotel_amenities'];
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

<div class="mb-3">
    <label for="hotel_policies" class="form-label">Politiques</label>
    <textarea class="form-control <?php $__errorArgs = ['hotel_policies'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="hotel_policies" name="hotel_policies" rows="3" placeholder="JSON ou texte libre"><?php echo e($hotelPolicies); ?></textarea>
    <?php $__errorArgs = ['hotel_policies'];
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

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="hotel_phone" class="form-label">Téléphone</label>
        <input type="text" class="form-control <?php $__errorArgs = ['hotel_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="hotel_phone" name="hotel_phone" value="<?php echo e($hotelPhone); ?>" maxlength="100">
        <?php $__errorArgs = ['hotel_phone'];
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
    <div class="col-md-6 mb-3">
        <label for="hotel_email" class="form-label">Email</label>
        <input type="email" class="form-control <?php $__errorArgs = ['hotel_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="hotel_email" name="hotel_email" value="<?php echo e($hotelEmail); ?>" maxlength="255">
        <?php $__errorArgs = ['hotel_email'];
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

<?php if(!empty($galleryUrls)): ?>
<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.gallery-remove').forEach(function(btn) {
        btn.addEventListener('click', function() { this.closest('.gallery-item').remove(); });
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\wordpress\hotels\_form.blade.php ENDPATH**/ ?>