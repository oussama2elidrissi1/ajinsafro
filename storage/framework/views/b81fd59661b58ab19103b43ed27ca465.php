<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'AjiNsafro.ma – Let the journey begin'); ?></title>
    <meta name="description" content="Get the best prices on 2,000,000+ properties, worldwide.">
    <link rel="icon" href="<?php echo e(asset('favicon.ico')); ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand': { DEFAULT: '#2563eb', dark: '#1d4ed8' },
                        'topbar': '#1f2937',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .hero-overlay { background: linear-gradient(to bottom, rgba(0,0,0,0.4), rgba(0,0,0,0.5)); }
        }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="antialiased text-gray-800 bg-white">
    <?php echo $__env->yieldContent('content'); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\front.blade.php ENDPATH**/ ?>