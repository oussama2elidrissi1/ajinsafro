<?php $__env->startSection('title', 'Catalogue voyages'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title mb-0 font-size-18">Catalogue voyages</h4>
            </div>
        </div>
    </div>
    <p class="text-muted">Voyages que vous pouvez proposer et vendre. Prix public et commission applicable selon les règles définies par Ajinsafro.</p>

    <div class="row">
        <?php $__empty_1 = true; $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title"><?php echo e($voyage->name); ?></h6>
                        <?php if($voyage->destination): ?>
                            <p class="text-muted small mb-1"><?php echo e($voyage->destination); ?></p>
                        <?php endif; ?>
                        <p class="mb-2">
                            <strong>Prix public :</strong>
                            <?php echo e($voyage->catalog_public_price_display ?? '—'); ?>

                        </p>
                        <p class="mb-0">
                            <strong>Commission :</strong>
                            <span class="text-success"><?php echo e($voyage->catalog_commission_display ?? '—'); ?></span>
                        </p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="<?php echo e(route('partner.reservations.create')); ?>?tour_id=<?php echo e($voyage->id); ?>" class="btn btn-sm btn-primary">Réserver</a>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-12">
                <div class="alert alert-info">Aucun voyage disponible pour le moment.</div>
            </div>
        <?php endif; ?>
    </div>
    <?php if(method_exists($voyages, 'links')): ?>
        <div class="d-flex justify-content-center mt-3"><?php echo e($voyages->links()); ?></div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\catalogue\index.blade.php ENDPATH**/ ?>