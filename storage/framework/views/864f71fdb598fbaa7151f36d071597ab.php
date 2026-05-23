<?php $__env->startSection('title', 'Modifier demande a la carte'); ?>
<?php $__env->startSection('page_title', 'Modifier la demande'); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('admin.reservations.custom-requests.form', [
        'customRequest' => $customRequest,
        'formAction' => route('admin.reservations.custom-requests.update', $customRequest),
        'formMethod' => 'PUT',
        'submitLabel' => 'Enregistrer les modifications',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\custom-requests\edit.blade.php ENDPATH**/ ?>