<div class="vertical-menu">
    <div class="h-100">
        <div class="user-wid text-center py-4">
            <div class="user-img">
                <img src="<?php echo e(Auth::user()->avatar_url); ?>" alt="" class="avatar-md mx-auto rounded-circle">
            </div>
            <div class="mt-3">
                <a href="<?php echo e(route('partner.dashboard')); ?>" class="text-body fw-medium font-size-16"><?php echo e(Auth::user()->name); ?></a>
                <p class="text-muted mt-1 mb-0 font-size-13">Partenaire</p>
            </div>
        </div>
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>
                <?php
                    $menuItems = config('partner_menu.items', []);
                    $currentRoute = Route::currentRouteName();
                ?>
                <?php $__currentLoopData = $menuItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $route = $item['route'] ?? null;
                        $routePrefix = $route && substr_count($route, '.') >= 2 ? substr($route, 0, strrpos($route, '.')) : '';
                        $active = $route === $currentRoute || ($routePrefix && str_starts_with($currentRoute, $routePrefix));
                    ?>
                    <li>
                        <a href="<?php echo e($route ? route($route) : 'javascript:void(0);'); ?>" class="waves-effect <?php echo e($active ? 'active' : ''); ?>">
                            <i class="<?php echo e($item['icon'] ?? 'bx bx-circle'); ?>"></i>
                            <span><?php echo e($item['label']); ?></span>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\partials\sidebar-partner.blade.php ENDPATH**/ ?>