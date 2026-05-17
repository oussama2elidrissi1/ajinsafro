<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'title' => 'Aucun résultat',
    'message' => 'Ajustez vos filtres ou créez un nouvel élément.',
    'actionUrl' => null,
    'actionLabel' => null,
    'icon' => 'bx bx-folder-open',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'title' => 'Aucun résultat',
    'message' => 'Ajustez vos filtres ou créez un nouvel élément.',
    'actionUrl' => null,
    'actionLabel' => null,
    'icon' => 'bx bx-folder-open',
]); ?>
<?php foreach (array_filter(([
    'title' => 'Aucun résultat',
    'message' => 'Ajustez vos filtres ou créez un nouvel élément.',
    'actionUrl' => null,
    'actionLabel' => null,
    'icon' => 'bx bx-folder-open',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="aj-empty">
    <div class="mb-3">
        <i class="<?php echo e($icon); ?>" style="font-size: 2.5rem; color: var(--ajp-primary);"></i>
    </div>
    <h5 class="mb-2" style="color: var(--ajp-ink); font-weight: 800;"><?php echo e($title); ?></h5>
    <p class="text-muted mb-3" style="font-weight: 600;"><?php echo e($message); ?></p>
    <?php if($actionUrl && $actionLabel): ?>
        <a href="<?php echo e($actionUrl); ?>" class="aj-btn aj-btn-primary">
            <i class="bx bx-plus"></i>
            <span><?php echo e($actionLabel); ?></span>
        </a>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\admin\empty-state.blade.php ENDPATH**/ ?>