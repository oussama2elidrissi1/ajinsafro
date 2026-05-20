<?php
    $dashboardV4BrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', $dashboardV4BrandName); ?> — <?php echo e($dashboardV4BrandName); ?></title>

    <link rel="shortcut icon" href="<?php echo e(URL::asset('build/images/favicon.ico')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="<?php echo e(URL::asset('build/css/bootstrap.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(URL::asset('build/css/icons.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(URL::asset('build/css/app.min.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(URL::asset('css/admin-branding.css')); ?>" rel="stylesheet">
    <link href="<?php echo e(URL::asset('css/admin-premium.css')); ?>" rel="stylesheet">

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="aj-dashboard-v4-body">
    <?php echo $__env->yieldContent('content'); ?>

    <script src="<?php echo e(URL::asset('build/libs/jquery/jquery.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('js/admin-v2.js')); ?>"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\dashboard-v4.blade.php ENDPATH**/ ?>