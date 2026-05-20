<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'user',
    'managerTeamPreview' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'user',
    'managerTeamPreview' => null,
]); ?>
<?php foreach (array_filter(([
    'user',
    'managerTeamPreview' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php if(!empty($managerTeamPreview)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="rounded-2xl border border-gray-200 shadow-sm overflow-hidden" style="font-family: 'Poppins', system-ui, sans-serif;">
            <div class="px-4 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: linear-gradient(90deg, #e6f3fa 0%, #fff 100%); border-color: #e5e7eb !important;">
                <div>
                    <h5 class="mb-0 fw-bold" style="color: #0e3a5a;">Profil manager</h5>
                    <p class="mb-0 small text-muted">�?quipe rattachée (même agence) · <?php echo e($managerTeamPreview['count']); ?> membre(s)</p>
                </div>
                <?php if(Route::has('agent.dashboard')): ?>
                    <a href="<?php echo e(route('agent.dashboard')); ?>" class="btn btn-sm fw-bold text-white border-0" style="background: #0083c4;">
                        <i class="fas fa-chart-line me-1"></i> Tableau de bord
                    </a>
                <?php endif; ?>
            </div>
            <div class="p-3 bg-white">
                <div class="row g-2">
                    <?php $__currentLoopData = $managerTeamPreview['members']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="d-flex align-items-center gap-2 p-2 rounded border border-light bg-light-subtle">
                                <img src="<?php echo e($member->avatar_url); ?>" alt="" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate" style="color: #0e3a5a;"><?php echo e($member->name); ?></div>
                                    <div class="small text-muted text-truncate"><?php echo e($member->email ?: '�?"'); ?></div>
                                    <?php if($member->job_title): ?>
                                        <div class="small" style="color: #0083c4;"><?php echo e($member->job_title); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php if($managerTeamPreview['count'] === 0): ?>
                    <p class="text-muted small mb-0">Aucun utilisateur n�?Ta <code>manager_id</code> pointant vers vous. Affectez un manager sur la fiche utilisateur (paramètres).</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\profile\partials\manager-portal-summary.blade.php ENDPATH**/ ?>