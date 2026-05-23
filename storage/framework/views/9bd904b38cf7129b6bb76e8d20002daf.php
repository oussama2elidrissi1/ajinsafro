<?php $__env->startSection('title', 'Nouvelle demande a la carte'); ?>
<?php $__env->startSection('page_title', 'Nouvelle demande à la carte'); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('admin.reservations.custom-requests.form', [
        'customRequest' => $customRequest,
        'formAction' => route('admin.reservations.custom-requests.store'),
        'formMethod' => 'POST',
        'submitLabel' => 'Créer la demande',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\custom-requests\create.blade.php ENDPATH**/ ?>