<?php
    $useAgentPortal = \App\Services\View\AgentPortalLayout::shouldUse(auth()->user());
    $voyageLayoutPage = request()->routeIs('admin.circuits.voyages.create', 'admin.circuits.voyages.edit');
    $hideInternalV2Topbar = true;
?>

<?php if($useAgentPortal): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title'); ?> | Ajinsafro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="shortcut icon" href="<?php echo e(URL::asset('build/images/favicon.ico')); ?>">

    <?php echo $__env->yieldPushContent('css'); ?>

    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/partner-v2.css', 'resources/js/partner-v2.js']); ?>

    <link href="<?php echo e(URL::asset('build/css/bootstrap.min.css')); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo e(URL::asset('build/css/icons.min.css')); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo e(URL::asset('build/css/app.min.css')); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo e(URL::asset('css/admin-branding.css')); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo e(URL::asset('css/agent-portal-bootstrap-bridge.css')); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo e(URL::asset('css/internal-v2-layout.css')); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo e(URL::asset('css/admin-premium.css')); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo e(URL::asset('css/admin-compact.css')); ?>?v=nano-compact-4" rel="stylesheet" type="text/css" />

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="partner-v2 admin-premium-ui aj-admin aj-admin-compact text-gray-800 antialiased font-sans<?php echo e($voyageLayoutPage ? ' voyage-layout-page' : ''); ?><?php echo e($hideInternalV2Topbar ? ' internal-v2-topbar-hidden' : ''); ?>">
    <?php if($hideInternalV2Topbar): ?>
        <?php echo $__env->make('layouts.partials.internal-v2-topbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php else: ?>
        <?php echo $__env->make('partner_v2.partials.header', ['portalLogoutUsesPartner' => false], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <main class="flex-grow w-full relative">
        <div class="w-full px-0 mt-0 mb-16 fade-in">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                <?php echo $__env->make('agent_v2.partials.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <div class="flex-1 min-w-0 agent-portal-main">
                    <?php echo $__env->yieldContent('content'); ?>
                </div>
            </div>
        </div>
    </main>

    <?php if(trim($__env->yieldContent('hidePageFooter')) !== '1' && !request()->routeIs('admin.reservations.workspace')): ?>
        <?php echo $__env->make('partner_v2.partials.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <?php echo $__env->make('layouts.vendor-scripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <?php echo $__env->yieldPushContent('body-end'); ?>
</body>
</html>
<?php else: ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?php echo $__env->yieldContent('title'); ?> | AJINSAFRO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesdesign" name="author" />
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <!-- App favicon -->
    <link rel="shortcut icon" href="<?php echo e(URL::asset('build/images/favicon.ico')); ?>">

    <?php echo $__env->make('layouts.head-css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <link href="<?php echo e(URL::asset('css/admin-compact.css')); ?>?v=nano-compact-4" rel="stylesheet" type="text/css" />
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>

<body class="admin-premium-ui aj-admin aj-admin-compact<?php echo e($voyageLayoutPage ? ' voyage-layout-page' : ''); ?><?php echo e($hideInternalV2Topbar ? ' internal-v2-topbar-hidden' : ''); ?>" data-layout="detached" data-topbar="colored">
    <!-- Begin page -->
    <div class="container-fluid">
        <div id="layout-wrapper">
            <!-- DIAG_MARKER: MASTER_LAYOUT_TRADITIONAL_PATH file=resources/views/layouts/master-ajinsafro.blade.php -->
            <?php echo $__env->make('layouts.partials.admin-topbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php echo $__env->make('layouts.partials.sidebar-ajinsafro', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <div class="main-content">
                <div class="page-content">
                    <?php echo $__env->yieldContent('content'); ?>
                </div>
                <?php if(trim($__env->yieldContent('hidePageFooter')) !== '1' && !request()->routeIs('admin.reservations.workspace')): ?>
                    <?php echo $__env->make('layouts.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php echo $__env->make('layouts.right-sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('layouts.vendor-scripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <?php echo $__env->yieldPushContent('body-end'); ?>
</body>

</html>
<?php endif; ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\master-ajinsafro.blade.php ENDPATH**/ ?>