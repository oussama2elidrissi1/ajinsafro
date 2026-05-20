<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'src' => null,
    'alt' => '',
    'size' => 'md',
    'placeholder' => 'images/admin-placeholder.svg',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'src' => null,
    'alt' => '',
    'size' => 'md',
    'placeholder' => 'images/admin-placeholder.svg',
]); ?>
<?php foreach (array_filter(([
    'src' => null,
    'alt' => '',
    'size' => 'md',
    'placeholder' => 'images/admin-placeholder.svg',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $sizeClass = match($size) {
        'sm' => '--sm',
        'md' => '--md',
        'lg' => '--lg',
        'tour' => '--tour',
        'card-cover' => '--card-cover',
        default => '--md',
    };
?>

<div class="aj-thumb <?php echo e($sizeClass); ?>">
    <?php if($src): ?>
        <img src="<?php echo e($src); ?>" alt="<?php echo e($alt); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
        <div class="aj-thumb-placeholder" style="display:none;">
            <img src="<?php echo e(asset($placeholder)); ?>" alt="Ajinsafro" style="width:100%;height:100%;object-fit:cover;">
        </div>
    <?php else: ?>
        <div class="aj-thumb-placeholder" style="display:grid;">
            <img src="<?php echo e(asset($placeholder)); ?>" alt="Ajinsafro" style="width:100%;height:100%;object-fit:cover;">
        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\admin\image-thumb.blade.php ENDPATH**/ ?>