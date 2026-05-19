<?php $__env->startSection('title'); ?>
    Référence métier
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h4 class="page-title mb-0 font-size-18">Référence métier</h4>
                <div class="d-flex gap-2">
                    <form action="<?php echo e(route('admin.settings.referentiels-metier.import-legacy')); ?>" method="POST" class="d-inline"
                          onsubmit="return confirm('Fusionner les valeurs depuis l’ancien JSON (settings) vers la base ?');">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Importer ancien JSON</button>
                    </form>
                </div>
            </div>
            <p class="text-muted">Listes dynamiques utilisées dans les formulaires voyage (types de jour, réductions, paiements, etc.).</p>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title font-size-15"><?php echo e($g['label']); ?></h5>
                        <p class="text-muted small mb-2">Clé : <code><?php echo e($g['key']); ?></code></p>
                        <p class="mb-3"><span class="badge bg-light text-dark border"><?php echo e($g['count']); ?> valeur(s)</span></p>
                        <a href="<?php echo e(route('admin.settings.referentiels-metier.group', ['groupKey' => $g['key']])); ?>" class="btn btn-primary btn-sm mt-auto">Gérer</a>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\settings\business_references\index.blade.php ENDPATH**/ ?>