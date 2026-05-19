<?php
    $user = auth()->user();
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $brandLogoSm = \App\Models\Setting::brandLogoUrl('sm');
    $currentRoute = Route::currentRouteName();
?>

<div class="admin-topbar" role="banner">
    <div class="admin-topbar__inner">
        <div class="admin-topbar__left">
            <button type="button" class="btn btn-sm btn-light d-lg-none admin-topbar__toggle" id="vertical-menu-btn" aria-label="Ouvrir le menu">
                <i class="bx bx-menu"></i>
            </button>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="admin-topbar__brand d-none d-lg-inline-flex">
                <img src="<?php echo e($brandLogoSm); ?>" alt="<?php echo e($brandName); ?>" class="admin-topbar__logo">
            </a>
            <?php if (! empty(trim($__env->yieldContent('topbar-title')))): ?>
                <div class="admin-topbar__title d-none d-md-block">
                    <h1 class="admin-topbar__page-title"><?php echo $__env->yieldContent('topbar-title'); ?></h1>
                    <?php if (! empty(trim($__env->yieldContent('topbar-subtitle')))): ?>
                        <p class="admin-topbar__page-subtitle"><?php echo $__env->yieldContent('topbar-subtitle'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-topbar__right">
            <div class="admin-topbar__actions">
                <?php if (! empty(trim($__env->yieldContent('topbar-action')))): ?>
                    <div class="admin-topbar__action-slot">
                        <?php echo $__env->yieldContent('topbar-action'); ?>
                    </div>
                <?php endif; ?>

                <div class="dropdown admin-topbar__user">
                    <button class="btn btn-link dropdown-toggle admin-topbar__user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo e($user->avatar_url); ?>" alt="<?php echo e($user->name); ?>" class="admin-topbar__avatar">
                        <span class="admin-topbar__user-name d-none d-md-inline"><?php echo e($user->name); ?></span>
                        <i class="bx bx-chevron-down d-none d-md-inline"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end admin-topbar__dropdown">
                        <li class="dropdown-header"><?php echo e($user->getRoleNames()->first() ?? 'Admin'); ?></li>
                        <li><a class="dropdown-item" href="<?php echo e(route('admin.profile.edit')); ?>"><i class="bx bx-user-circle me-2"></i>Mon profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo e(route('logout.get')); ?>">
                                <i class="bx bx-power-off me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\partials\admin-topbar.blade.php ENDPATH**/ ?>