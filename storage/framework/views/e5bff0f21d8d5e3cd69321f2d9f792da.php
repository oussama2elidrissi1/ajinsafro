<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['kpis' => []]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['kpis' => []]); ?>
<?php foreach (array_filter((['kpis' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php if(!empty($kpis)): ?>
    <section class="aj-kpis">
        <?php $__currentLoopData = $kpis; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kpi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="aj-kpi">
                <div class="aj-kpi-head">
                    <div class="aj-kpi-icon <?php echo e($kpi['color'] ?? '-blue'); ?>">
                        <i class="<?php echo e($kpi['icon'] ?? 'bx bx-buildings'); ?>"></i>
                    </div>
                    <div>
                        <span class="aj-kpi-label"><?php echo e($kpi['label'] ?? ''); ?></span>
                        <strong class="aj-kpi-value"><?php echo e($kpi['value'] ?? '0'); ?></strong>
                        <?php if(!empty($kpi['note'])): ?>
                            <span class="aj-kpi-note"><?php echo e($kpi['note']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </section>
<?php endif; ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\admin\kpi-cards.blade.php ENDPATH**/ ?>