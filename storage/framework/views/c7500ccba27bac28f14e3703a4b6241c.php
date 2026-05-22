
<?php $__env->startSection('title', 'Créer une activité'); ?>
<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Créer une activité</h4>
                <a href="<?php echo e(route('admin.wordpress.activities.index')); ?>" class="btn btn-secondary">Retour</a>
            </div>
        </div>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?php echo e(route('admin.wordpress.activities.store')); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php echo $__env->make('admin.wordpress.activities._form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="<?php echo e(route('admin.wordpress.activities.index')); ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\wordpress\activities\create.blade.php ENDPATH**/ ?>