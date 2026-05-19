<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'editUrl' => null,
    'deleteUrl' => null,
    'viewUrl' => null,
    'deleteConfirm' => 'Supprimer cet élément ?',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'editUrl' => null,
    'deleteUrl' => null,
    'viewUrl' => null,
    'deleteConfirm' => 'Supprimer cet élément ?',
]); ?>
<?php foreach (array_filter(([
    'editUrl' => null,
    'deleteUrl' => null,
    'viewUrl' => null,
    'deleteConfirm' => 'Supprimer cet élément ?',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="aj-actions" style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
    <?php if($viewUrl): ?>
        <a href="<?php echo e($viewUrl); ?>" target="_blank" class="aj-icon-btn" title="Voir sur le site" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;transition:.16s ease;">
            <i class="bx bx-link-external"></i>
        </a>
    <?php endif; ?>

    <?php if($editUrl): ?>
        <a href="<?php echo e($editUrl); ?>" class="aj-icon-btn" title="Modifier" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;transition:.16s ease;">
            <i class="bx bx-pencil"></i>
        </a>
    <?php endif; ?>

    <?php if($deleteUrl): ?>
        <form action="<?php echo e($deleteUrl); ?>" method="POST" class="d-inline" onsubmit="return confirm('<?php echo e($deleteConfirm); ?>');">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit" class="aj-icon-btn -danger" title="Supprimer" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;transition:.16s ease;">
                <i class="bx bx-trash"></i>
            </button>
        </form>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\admin\action-buttons.blade.php ENDPATH**/ ?>