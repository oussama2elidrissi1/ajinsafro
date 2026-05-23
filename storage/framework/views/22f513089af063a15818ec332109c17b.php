<!DOCTYPE html>
<html lang="fr">
<?php ($hideInternalV2Topbar = \App\Services\View\InternalV2Topbar::shouldHide(auth()->user())); ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Portail partenaire'); ?> | Ajinsafro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="shortcut icon" href="<?php echo e(URL::asset('build/images/favicon.ico')); ?>">

    
    <link href="<?php echo e(URL::asset('build/css/icons.min.css')); ?>" rel="stylesheet" type="text/css" />

    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/partner-v2.css', 'resources/js/partner-v2.js']); ?>

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="partner-v2 text-gray-800 antialiased font-sans<?php echo e($hideInternalV2Topbar ? ' internal-v2-topbar-hidden' : ''); ?>">
    <?php if($hideInternalV2Topbar): ?>
        <?php echo $__env->make('layouts.partials.internal-v2-topbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php else: ?>
        <?php echo $__env->make('partner_v2.partials.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <main class="flex-grow w-full z-10 relative">
        <div class="w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 mt-4 sm:mt-8 mb-16 fade-in">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                <?php echo $__env->make('partner_v2.partials.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <div class="flex-1 min-w-0">
                    <?php echo $__env->yieldContent('content'); ?>
                </div>
            </div>
        </div>
    </main>

    <?php echo $__env->make('partner_v2.partials.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner_v2\layouts\app.blade.php ENDPATH**/ ?>