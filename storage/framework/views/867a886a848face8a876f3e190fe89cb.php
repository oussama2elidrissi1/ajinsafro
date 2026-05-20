<?php
    $adminUser      = $adminUser      ?? auth()->user();
    $adminUserName  = $adminUserName  ?? ($adminUser?->name ?? 'Admin');
    $adminUserRole  = $adminUserRole  ?? ($adminUser?->getRoleNames()->first() ?? 'Administrateur');
    $adminInitials  = $adminInitials  ?? 'AD';
    $adminAvatarUrl = $adminAvatarUrl ?? null;

    $unreadCount  = $unreadCount  ?? 0;
    $pendingCount = $pendingCount ?? 0;

    $v6Title = html_entity_decode(
        $pageTitle ?? trim((string) View::yieldContent('page_title', View::yieldContent('title', 'Espace Admin'))),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    $v6Breadcrumbs = $breadcrumbs ?? null;
    $primaryActionLabel = $primaryActionLabel ?? null;
    $primaryActionRoute = $primaryActionRoute ?? null;
?>

<header class="aj-v6-topbar" id="adminV6Header">
    <div class="aj-v6-topbar-left">
        <button type="button" class="aj-hamburger" id="adminV6Hamburger" aria-label="Ouvrir le menu">
            <i class="bx bx-menu"></i>
        </button>

        <div class="aj-v6-page-meta">
            <h1 class="aj-v6-page-title"><?php echo e($v6Title); ?></h1>
            <?php if(is_array($v6Breadcrumbs) && count($v6Breadcrumbs)): ?>
                <div class="aj-v6-page-breadcrumb">
                    <?php $__currentLoopData = $v6Breadcrumbs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $crumb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($index > 0): ?><span>&gt;</span><?php endif; ?>
                        <?php if(!empty($crumb['url'])): ?>
                            <a href="<?php echo e($crumb['url']); ?>"><?php echo e(html_entity_decode((string) ($crumb['label'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></a>
                        <?php else: ?>
                            <?php echo e(html_entity_decode((string) ($crumb['label'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>

                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <div class="aj-v6-page-breadcrumb">Accueil <span>&gt;</span> Tableau de bord</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="aj-v6-topbar-center">
        <div class="aj-v6-search">
            <i class="bx bx-search aj-search-icon" aria-hidden="true"></i>
            <input type="search" placeholder="Rechercher..." aria-label="Rechercher" autocomplete="off">
        </div>
    </div>

    <div class="aj-v6-topbar-actions">
        <?php if(is_string($primaryActionLabel) && $primaryActionLabel !== '' && is_string($primaryActionRoute) && $primaryActionRoute !== '' && \Illuminate\Support\Facades\Route::has($primaryActionRoute)): ?>
            <a href="<?php echo e(route($primaryActionRoute)); ?>" class="aj-v6-primary-btn">
                <i class="bx bx-plus" aria-hidden="true"></i>
                <span><?php echo e($primaryActionLabel); ?></span>
            </a>
        <?php endif; ?>

        <?php if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index')): ?>
            <a href="<?php echo e(route('admin.messagerie.index')); ?>" class="aj-topbar-notif" title="Messagerie">
                <i class="bx bx-envelope"></i>
                <?php if((int) $unreadCount > 0): ?>
                    <b class="aj-notif-badge"><?php echo e(min((int) $unreadCount, 99)); ?></b>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php if(\Illuminate\Support\Facades\Route::has('admin.reservations.en-attente')): ?>
            <a href="<?php echo e(route('admin.reservations.en-attente')); ?>" class="aj-topbar-notif" title="Reservations en attente">
                <i class="bx bx-bell"></i>
                <?php if((int) $pendingCount > 0): ?>
                    <b class="aj-notif-badge"><?php echo e(min((int) $pendingCount, 99)); ?></b>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php if(\Illuminate\Support\Facades\Route::has('admin.profile.edit')): ?>
            <a href="<?php echo e(route('admin.profile.edit')); ?>" class="aj-topbar-profile" aria-label="Mon profil">
        <?php else: ?>
            <span class="aj-topbar-profile">
        <?php endif; ?>
            <span class="aj-topbar-profile-avatar-shell">
                <img src="<?php echo e($adminAvatarUrl); ?>"
                     alt="<?php echo e($adminUserName); ?>"
                     class="aj-topbar-profile-avatar"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                <span class="aj-topbar-avatar-fallback"><?php echo e($adminInitials); ?></span>
            </span>
            <div class="aj-topbar-profile-info">
                <div class="aj-topbar-profile-name"><?php echo e($adminUserName); ?></div>
                <div class="aj-topbar-profile-role"><?php echo e($adminUserRole); ?></div>
            </div>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.profile.edit')): ?>
            </a>
        <?php else: ?>
            </span>
        <?php endif; ?>
    </div>
</header>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partials\header-v6.blade.php ENDPATH**/ ?>