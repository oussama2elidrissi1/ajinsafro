<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
]); ?>
<?php foreach (array_filter(([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $currentRoute = Route::currentRouteName();
?>

<div class="aj-page-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:22px;">
    <div>
        <h1 class="aj-title" style="margin:0;color:var(--ajp-ink);font-size:clamp(1.9rem, 2.2vw, 2.6rem);font-weight:800;letter-spacing:-0.04em;"><?php echo e($title); ?></h1>
        <?php if($subtitle): ?>
            <p class="aj-subtitle" style="margin:8px 0 0;color:var(--ajp-muted);font-weight:500;max-width:720px;"><?php echo e($subtitle); ?></p>
        <?php endif; ?>
    </div>
    <div>
        <?php if(!empty($breadcrumbs)): ?>
            <div class="aj-breadcrumb" style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;margin-bottom:14px;color:#718198;font-size:13px;font-weight:700;">
                <?php $__currentLoopData = $breadcrumbs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $crumb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($i > 0): ?>
                        <span>/</span>
                    <?php endif; ?>
                    <?php if(!empty($crumb['url'])): ?>
                        <a href="<?php echo e($crumb['url']); ?>" style="color:#718198;text-decoration:none;"><?php echo e($crumb['label']); ?></a>
                    <?php else: ?>
                        <strong style="color:#0b1f3a"><?php echo e($crumb['label']); ?></strong>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
        <?php if(isset($actions)): ?>
            <div class="d-flex flex-wrap gap-2"><?php echo e($actions); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\admin\page-header.blade.php ENDPATH**/ ?>