

<?php $__env->startSection('title'); ?>
    <?php echo e($title ?? 'Module'); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18"><?php echo e($title ?? 'Module'); ?></h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item">Produits et services</li>
                        <li class="breadcrumb-item active"><?php echo e($title ?? 'Module'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card border shadow-sm">
                <div class="card-body text-center py-5 px-4">
                    <div class="avatar-sm mx-auto mb-4">
                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-24">
                            <i class="bx bx-wrench"></i>
                        </span>
                    </div>
                    <h5 class="mb-3"><?php echo e($title ?? 'Module'); ?></h5>
                    <p class="text-muted mb-4"><?php echo e($intro ?? 'Cette section est en cours de construction.'); ?></p>
                    <a href="<?php echo e(route('admin.circuits.voyages.index')); ?>" class="btn btn-outline-primary me-2">Vers les voyages</a>
                    <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-primary">Tableau de bord</a>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\products-services\wip.blade.php ENDPATH**/ ?>