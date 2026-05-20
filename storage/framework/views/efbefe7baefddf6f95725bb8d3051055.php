<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'title',
    'subtitle' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'title',
    'subtitle' => null,
]); ?>
<?php foreach (array_filter(([
    'title',
    'subtitle' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="card mb-4">
    <?php if($title): ?>
        <div class="card-header">
            <h5 class="card-title mb-0" style="font-weight:800;letter-spacing:-0.02em;"><?php echo e($title); ?></h5>
            <?php if($subtitle): ?>
                <p class="card-title-desc mb-0 mt-1" style="color:var(--ajp-muted);font-size:.85rem;font-weight:500;"><?php echo e($subtitle); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="card-body">
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\admin\form-section.blade.php ENDPATH**/ ?>