<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'type' => 'neutral',
    'label',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'type' => 'neutral',
    'label',
]); ?>
<?php foreach (array_filter(([
    'type' => 'neutral',
    'label',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $classes = match($type) {
        'success' => 'background:#ecfdf3;color:#067647;',
        'warning' => 'background:#fff7e8;color:#b54708;',
        'danger' => 'background:#fff2f0;color:#d92d20;',
        'info' => 'background:#edf6ff;color:#0550a7;',
        default => 'background:#f2f4f7;color:#475467;',
    };
?>

<span class="aj-badge" style="display:inline-flex;align-items:center;gap:8px;min-height:28px;padding:0 11px;border-radius:999px;font-size:12px;font-weight:800;<?php echo e($classes); ?>"><?php echo e($label); ?></span>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\admin\badge.blade.php ENDPATH**/ ?>