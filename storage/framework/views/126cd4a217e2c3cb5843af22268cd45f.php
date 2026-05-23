<?php
    $user = auth()->user();
    $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
    $branchLabel = $user?->branch?->name;
    $roleLabel = $user?->isManager() ? 'Manager' : 'Agent';

    $navItems = collect([
        ['label' => 'Dashboard', 'icon' => 'bx bx-home-circle', 'route' => 'agent.dashboard', 'match' => ['agent.dashboard']],
        ['label' => 'Réservations', 'icon' => 'bx bx-calendar-check', 'route' => 'admin.reservations.index', 'match' => ['admin.reservations.']],
        ['label' => 'Clients', 'icon' => 'bx bx-user', 'route' => 'admin.customers.clients.index', 'match' => ['admin.customers.clients.']],
        ['label' => 'Voyages', 'icon' => 'bx bx-map-alt', 'route' => 'admin.circuits.voyages.index', 'match' => ['admin.circuits.voyages.']],
        ['label' => 'Messagerie', 'icon' => 'bx bx-envelope', 'route' => 'agent.messagerie.index', 'match' => ['agent.messagerie.']],
    ])->filter(fn ($item) => !empty($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']));
?>

<div class="vertical-menu">
    <div class="h-100">
        <div class="user-wid text-center py-4 px-3">
            <div class="user-img">
                <img src="<?php echo e($user?->avatar_url); ?>" alt="" class="avatar-md mx-auto rounded-circle">
            </div>
            <div class="mt-3">
                <a href="<?php echo e(route('agent.dashboard')); ?>" class="text-body fw-semibold font-size-16"><?php echo e($user?->name); ?></a>
                <p class="text-muted mt-1 mb-0 font-size-13"><?php echo e($roleLabel); ?></p>
                <?php if($branchLabel): ?>
                    <p class="text-muted mt-1 mb-0 font-size-12"><?php echo e($branchLabel); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Navigation</li>

                <?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $isActive = collect($item['match'] ?? [])
                            ->contains(fn ($prefix) => $currentRoute === $prefix || str_starts_with((string) $currentRoute, $prefix));
                    ?>
                    <li>
                        <a href="<?php echo e(route($item['route'])); ?>" class="waves-effect <?php echo e($isActive ? 'active' : ''); ?>">
                            <i class="<?php echo e($item['icon']); ?>"></i>
                            <span><?php echo e($item['label']); ?></span>
                            <?php if($item['route'] === 'agent.messagerie.index' && ($unreadCount ?? 0) > 0): ?>
                                <span class="badge rounded-pill bg-primary float-end"><?php echo e($unreadCount); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\partials\sidebar-agent.blade.php ENDPATH**/ ?>