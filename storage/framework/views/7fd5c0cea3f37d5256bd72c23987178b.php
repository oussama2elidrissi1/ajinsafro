<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'sortOptions' => [],
    'resultText' => '',
    'viewModes' => [],
    'exportLabel' => 'Exporter la vue',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'sortOptions' => [],
    'resultText' => '',
    'viewModes' => [],
    'exportLabel' => 'Exporter la vue',
]); ?>
<?php foreach (array_filter(([
    'sortOptions' => [],
    'resultText' => '',
    'viewModes' => [],
    'exportLabel' => 'Exporter la vue',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="aj-toolbar" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
    <div class="aj-result-meta" style="display:flex;align-items:center;flex-wrap:wrap;gap:12px;color:#5f6f85;font-size:13px;font-weight:700;">
        <?php if(!empty($sortOptions)): ?>
            <div class="d-flex align-items-center gap-2">
                <label for="sortSelect" class="mb-0">Trier par :</label>
                <select id="sortSelect" class="aj-mini-btn aj-mini-select" style="width:auto;">
                    <?php $__currentLoopData = $sortOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if($exportLabel): ?>
            <button type="button" class="aj-mini-btn" onclick="window.print()">
                <i class="bx bx-export"></i>
                <span><?php echo e($exportLabel); ?></span>
            </button>
        <?php endif; ?>

        <?php if($resultText): ?>
            <span><?php echo e($resultText); ?></span>
        <?php endif; ?>
    </div>

    <?php if(!empty($viewModes)): ?>
        <div class="aj-result-meta">
            <span>Vue :</span>
            <div class="aj-view-toggle" style="display:inline-flex;padding:4px;border-radius:14px;border:1px solid var(--ajp-line);background:#fff;">
                <?php $__currentLoopData = $viewModes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button type="button" data-view="<?php echo e($mode['value']); ?>" class="<?php echo e(($mode['active'] ?? false) ? 'is-active' : ''); ?>" style="min-width:42px;height:38px;border:0;border-radius:10px;background:transparent;color:#516278;font-weight:900;">
                        <i class="<?php echo e($mode['icon']); ?>"></i>
                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\admin\table-toolbar.blade.php ENDPATH**/ ?>