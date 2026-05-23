<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'action' => '',
    'method' => 'GET',
    'resetUrl' => '',
    'gridClass' => '',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'action' => '',
    'method' => 'GET',
    'resetUrl' => '',
    'gridClass' => '',
]); ?>
<?php foreach (array_filter(([
    'action' => '',
    'method' => 'GET',
    'resetUrl' => '',
    'gridClass' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<section class="aj-panel" style="margin-bottom:20px;padding:20px;border-radius:24px;background:rgba(255,255,255,0.96);border:1px solid var(--ajp-line);box-shadow:var(--ajp-shadow);">
    <form method="<?php echo e($method); ?>" action="<?php echo e($action); ?>">
        <div class="aj-filter-grid <?php echo e($gridClass); ?>">
            <?php echo e($fields); ?>


            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="aj-btn aj-btn-primary w-100">
                    <i class="bx bx-filter-alt"></i>
                    <span>Filtrer</span>
                </button>
            </div>
            <?php if($resetUrl): ?>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo e($resetUrl); ?>" class="aj-btn aj-btn-soft w-100">
                        <i class="bx bx-reset"></i>
                        <span>Réinitialiser</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if(isset($chips) && trim($chips) !== ''): ?>
            <div class="aj-filter-chips" style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:16px;color:var(--ajp-muted);font-size:13px;font-weight:700;">
                <span>Filtres actifs :</span>
                <?php echo e($chips); ?>

                <?php if(isset($clearFiltersUrl) && $clearFiltersUrl): ?>
                    <a href="<?php echo e($clearFiltersUrl); ?>" class="ms-auto fw-bold text-decoration-none" style="color:#0468c8;">Tout effacer</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </form>
</section>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\admin\filter-panel.blade.php ENDPATH**/ ?>