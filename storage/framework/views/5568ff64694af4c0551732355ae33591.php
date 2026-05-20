
<?php $__env->startSection('title', 'Nouvel hôtel'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Nouvel hôtel</h4>
                <a href="<?php echo e(route('admin.hotels.index')); ?>" class="btn btn-outline-secondary btn-sm">Retour</a>
            </div>
        </div>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($e); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="<?php echo e(route('admin.hotels.store')); ?>" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php echo $__env->make('admin.hotels._form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <div class="mb-4">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="<?php echo e(route('admin.hotels.index')); ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\hotels\create.blade.php ENDPATH**/ ?>