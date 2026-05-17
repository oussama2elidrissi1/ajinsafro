<?php $__env->startSection('title', 'Documents'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title mb-0 font-size-18">Documents</h4>
            </div>
        </div>
    </div>
    <p class="text-muted">Contrat partenaire, grille de commission, conditions de vente et supports marketing.</p>

    <div class="row">
        <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar-md rounded bg-light me-3">
                            <i class="bx bx-file font-size-24 text-secondary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo e($typeLabels[$doc->type] ?? $doc->type); ?></h6>
                            <p class="text-muted small mb-0"><?php echo e($doc->name ?: 'Document'); ?></p>
                        </div>
                        <a href="<?php echo e(asset('storage/' . $doc->file_path)); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Télécharger</a>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-12">
                <div class="alert alert-info">Aucun document disponible pour le moment. Contactez le siège pour obtenir votre contrat et les grilles de commission.</div>
            </div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\documents\index.blade.php ENDPATH**/ ?>