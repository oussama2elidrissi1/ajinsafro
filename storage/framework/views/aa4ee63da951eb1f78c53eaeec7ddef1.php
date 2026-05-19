

<?php $__env->startSection('title', 'Modifier Group Deal'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">Modifier l'offre</h4>
            <p class="text-muted mb-0"><?php echo e($groupDeal->title); ?></p>
        </div>
        <a href="<?php echo e(route('admin.group-deals.show', $groupDeal)); ?>" class="btn btn-light">Retour Ã  la fiche</a>
    </div>

    <form method="POST" action="<?php echo e(route('admin.group-deals.update', $groupDeal)); ?>" enctype="multipart/form-data">
        <?php echo $__env->make('admin.group-deals.offers._form', ['isEdit' => true], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </form>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\group-deals\offers\edit.blade.php ENDPATH**/ ?>