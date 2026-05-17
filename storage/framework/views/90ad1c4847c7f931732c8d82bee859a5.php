<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title'); ?> | Ajinsafro</title>
    <link href="<?php echo e(URL::asset('build/css/bootstrap.min.css')); ?>" rel="stylesheet" type="text/css">
    <link href="<?php echo e(URL::asset('build/css/icons.min.css')); ?>" rel="stylesheet" type="text/css">
    <link href="<?php echo e(URL::asset('build/css/app.min.css')); ?>" rel="stylesheet" type="text/css">
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="bg-light">
    <div class="container-fluid py-3">
        <?php echo $__env->yieldContent('content'); ?>
    </div>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
    <?php echo $__env->yieldPushContent('script'); ?>
</body>
</html>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\reservation-embed.blade.php ENDPATH**/ ?>