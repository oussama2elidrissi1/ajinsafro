<!DOCTYPE html>
<html lang="fr">
<?php ($hideInternalV2Topbar = \App\Services\View\InternalV2Topbar::shouldHide(auth()->user())); ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Portail partenaire'); ?> | Ajinsafro</title>
    <link rel="shortcut icon" href="<?php echo e(URL::asset('build/images/favicon.ico')); ?>">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/partner-v2.css', 'resources/js/partner-v2.js']); ?>
    <?php echo $__env->yieldPushContent('css'); ?>
</head>
<body class="partner-v2 text-gray-800 antialiased font-sans<?php echo e($hideInternalV2Topbar ? ' internal-v2-topbar-hidden' : ''); ?>">
    <?php if($hideInternalV2Topbar): ?>
        <?php echo $__env->make('layouts.partials.internal-v2-topbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php else: ?>
        <?php echo $__env->make('partner.v2.partials.topbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>
    <?php if (! ($hideInternalV2Topbar)): ?>
        <?php echo $__env->make('partner.v2.partials.navbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <main class="flex-grow w-full z-10 relative">
        <div class="w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 mt-4 sm:mt-8 mb-16 fade-in">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                <?php echo $__env->make('partner.v2.partials.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <div class="flex-1 min-w-0">
                    <?php echo $__env->yieldContent('content'); ?>
                </div>
            </div>
        </div>
    </main>

    <?php echo $__env->make('partner.v2.partials.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php echo $__env->yieldPushContent('script'); ?>
</body>
</html>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\partner-v2.blade.php ENDPATH**/ ?>