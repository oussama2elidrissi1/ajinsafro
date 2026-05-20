<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['paginator', 'linksView' => null]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['paginator', 'linksView' => null]); ?>
<?php foreach (array_filter((['paginator', 'linksView' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="aj-footer" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:16px;padding-top:18px;border-top:1px solid #eef3f8;color:#7a879a;font-size:13px;font-weight:600;">
    <div>
        Affichage de <?php echo e($paginator->firstItem() ?? 0); ?> à <?php echo e($paginator->lastItem() ?? 0); ?> sur <?php echo e($paginator->total()); ?> résultats
    </div>
    <div class="aj-pagination-wrap">
        <?php if($linksView): ?>
            <?php echo e($paginator->onEachSide(1)->links($linksView)); ?>

        <?php else: ?>
            <?php echo e($paginator->links()); ?>

        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\admin\pagination-footer.blade.php ENDPATH**/ ?>