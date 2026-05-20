<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title><?php echo $__env->yieldContent('title'); ?> | Espace partenaire</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="shortcut icon" href="<?php echo e(URL::asset('build/images/favicon.ico')); ?>">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/partner-v2.css', 'resources/js/partner-v2.js']); ?>
    <?php echo $__env->make('layouts.head-css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <link href="<?php echo e(URL::asset('css/internal-v2-layout.css')); ?>" rel="stylesheet" type="text/css" />
</head>
<?php ($hideInternalV2Topbar = \App\Services\View\InternalV2Topbar::shouldHide(auth()->user())); ?>
<body data-layout="detached" data-topbar="colored" class="partner-v2 text-gray-800 antialiased font-sans<?php echo e($hideInternalV2Topbar ? ' internal-v2-topbar-hidden' : ''); ?>">
    <div class="container-fluid">
        <div id="layout-wrapper">
            <?php if($hideInternalV2Topbar): ?>
                <?php echo $__env->make('layouts.partials.internal-v2-topbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php else: ?>
                <?php echo $__env->make('partner_v2.partials.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
            <?php echo $__env->make('layouts.partials.sidebar-partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php if($hideInternalV2Topbar): ?>
                <button type="button" class="btn btn-primary d-lg-none internal-v2-menu-toggle" id="vertical-menu-btn" aria-label="Ouvrir le menu">
                    <i class="fa fa-fw fa-bars"></i>
                </button>
            <?php endif; ?>
            <div class="main-content">
                <div class="page-content">
                    <?php echo $__env->yieldContent('content'); ?>
                </div>
                <?php echo $__env->make('layouts.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </div>
    <?php echo $__env->make('layouts.vendor-scripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</body>
</html>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\partner.blade.php ENDPATH**/ ?>