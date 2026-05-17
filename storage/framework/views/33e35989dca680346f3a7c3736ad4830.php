<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['hotel' => null, 'stHotel' => null, 'featuredUrl' => null]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['hotel' => null, 'stHotel' => null, 'featuredUrl' => null]); ?>
<?php foreach (array_filter((['hotel' => null, 'stHotel' => null, 'featuredUrl' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $postTitle = old('post_title', $hotel->post_title ?? '');
    $postContent = old('post_content', $hotel->post_content ?? '');
    $postStatus = old('post_status', $hotel->post_status ?? 'publish');
    $postName = old('post_name', $hotel->post_name ?? '');
    $address = old('address', $stHotel->address ?? '');
    $hotelStar = old('hotel_star', $stHotel->hotel_star ?? '');
    $minPrice = old('min_price', $stHotel->min_price ?? '');
    $mapLat = old('map_lat', $stHotel->map_lat ?? '');
    $mapLng = old('map_lng', $stHotel->map_lng ?? '');
    $isFeatured = old('is_featured', $stHotel->is_featured ?? 'off');
?>

<h5 class="mb-3">General &amp; Location</h5>

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
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
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
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
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
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
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
    <?php $__errorArgs = ['post_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
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
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="hotel_star" class="form-label">Étoiles (1–5)</label>
        <select class="form-select <?php $__errorArgs = ['hotel_star'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="hotel_star" name="hotel_star">
            <option value="">—</option>
            <?php for($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo e($i); ?>" <?php echo e((string)$i === (string)$hotelStar ? 'selected' : ''); ?>><?php echo e($i); ?> étoile(s)</option>
            <?php endfor; ?>
        </select>
        <?php $__errorArgs = ['hotel_star'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
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
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
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
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
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
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
</div>
<div class="mb-3">
    <div class="form-check form-switch">
        <input type="hidden" name="is_featured" value="off">
        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="on" <?php echo e($isFeatured === 'on' ? 'checked' : ''); ?>>
        <label class="form-check-label" for="is_featured">À la une (st_hotel)</label>
    </div>
</div>
<div class="mb-3">
    <label for="featured_image" class="form-label">Image à la une (thumbnail)</label>
    <?php if($featuredUrl): ?>
        <div class="mb-2 position-relative d-inline-block">
            <img src="<?php echo e($featuredUrl); ?>" alt="Image à la une" class="img-thumbnail featured-img" style="max-height: 120px;" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
            <div class="d-none featured-placeholder img-thumbnail bg-light text-muted small d-flex align-items-center justify-content-center text-center" style="max-height: 120px; min-width: 120px;">Image introuvable</div>
            <span class="text-muted small d-block">Remplacer en choisissant un nouveau fichier.</span>
        </div>
    <?php else: ?>
        <div class="bg-light border rounded d-inline-block p-2 text-muted small mb-2">Aucune image à la une</div>
    <?php endif; ?>
    <input type="file" class="form-control <?php $__errorArgs = ['featured_image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="featured_image" name="featured_image" accept="image/jpeg,image/png,image/webp">
    <small class="text-muted">JPG, PNG, WebP. Max 5 Mo.</small>
    <?php $__errorArgs = ['featured_image'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\wordpress\hotels\_tab_general.blade.php ENDPATH**/ ?>