
<?php $__env->startSection('title'); ?>
    Nouveau pack hÃ©bergement
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Nouveau pack hÃ©bergement</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.accommodation-packages.index')); ?>">Packs hÃ©bergement</a></li>
                        <li class="breadcrumb-item active">CrÃ©er</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?php echo e(route('admin.accommodation-packages.store')); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo $__env->make('admin.accommodation-packages._form', ['package' => $package], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">CrÃ©er</button>
                            <a href="<?php echo e(route('admin.accommodation-packages.index')); ?>" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\accommodation-packages\create.blade.php ENDPATH**/ ?>