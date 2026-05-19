<?php
    $adminV2User          = $adminV2User          ?? auth()->user();
    $adminV2UserName      = $adminV2UserName      ?? ($adminV2User?->name ?? 'Admin');
    $adminV2UserRole      = $adminV2UserRole      ?? ($adminV2User?->getRoleNames()->first() ?? 'Administrateur');
    $adminV2Initials      = $adminV2Initials      ?? 'AD';
    $adminV2AvatarUrl     = $adminV2AvatarUrl     ?? null;
    $adminV2UnreadCount   = $adminV2UnreadCount   ?? 0;
    $adminV2PendingCount  = $adminV2PendingCount  ?? 0;

    $v6Title = $pageTitle ?? trim((string) View::yieldContent('page_title', View::yieldContent('title', 'Espace Admin')));
    $v6Breadcrumbs = $breadcrumbs ?? null;
    $primaryActionLabel = $primaryActionLabel ?? null;
    $primaryActionRoute = $primaryActionRoute ?? null;
    $v6DateLabel = now('Africa/Casablanca')->locale('fr')->translatedFormat('l d F Y');
?>

<header class="aj-v6-topbar" id="aj-admin-v2-topbar">
    <div class="aj-v6-topbar-left">
        <button type="button" class="aj-hamburger" id="aj-admin-v2-hamburger" aria-label="Ouvrir le menu">
            <i class="bx bx-menu"></i>
        </button>

        <div class="aj-v6-page-meta">
            <h1 class="aj-v6-page-title"><?php echo e($v6Title); ?></h1>
            <?php if(is_array($v6Breadcrumbs) && count($v6Breadcrumbs)): ?>
                <div class="aj-v6-page-breadcrumb">
                    <?php $__currentLoopData = $v6Breadcrumbs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $crumb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($index > 0): ?><span>›</span><?php endif; ?>
                        <?php if(!empty($crumb['url'])): ?>
                            <a href="<?php echo e($crumb['url']); ?>"><?php echo e($crumb['label'] ?? ''); ?></a>
                        <?php else: ?>
                            <?php echo e($crumb['label'] ?? ''); ?>

                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <div class="aj-v6-page-breadcrumb">Accueil <span>›</span> Tableau de bord</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="aj-v6-topbar-center"></div>

    <div class="aj-v6-topbar-actions">
        <?php if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index')): ?>
            <a href="<?php echo e(route('admin.messagerie.index')); ?>" class="aj-topbar-notif" title="Messagerie">
                <i class="bx bx-envelope"></i>
                <?php if($adminV2UnreadCount > 0): ?>
                    <b class="aj-notif-badge"><?php echo e(min($adminV2UnreadCount, 99)); ?></b>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php if(\Illuminate\Support\Facades\Route::has('admin.reservations.en-attente')): ?>
            <a href="<?php echo e(route('admin.reservations.en-attente')); ?>" class="aj-topbar-notif" title="Réservations en attente">
                <i class="bx bx-bell"></i>
                <?php if($adminV2PendingCount > 0): ?>
                    <b class="aj-notif-badge"><?php echo e(min($adminV2PendingCount, 99)); ?></b>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php if(\Illuminate\Support\Facades\Route::has('admin.profile.edit')): ?>
            <a href="<?php echo e(route('admin.profile.edit')); ?>" class="aj-topbar-profile" aria-label="Mon profil">
        <?php else: ?>
            <span class="aj-topbar-profile">
        <?php endif; ?>
            <span class="aj-topbar-profile-avatar-shell">
                <img src="<?php echo e($adminV2AvatarUrl); ?>"
                     alt="<?php echo e($adminV2UserName); ?>"
                     class="aj-topbar-profile-avatar"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                <span class="aj-topbar-avatar-fallback"><?php echo e($adminV2Initials); ?></span>
            </span>
            <div class="aj-topbar-profile-info">
                <div class="aj-topbar-profile-name"><?php echo e($adminV2UserName); ?></div>
                <div class="aj-topbar-profile-role"><?php echo e($adminV2UserRole); ?></div>
            </div>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.profile.edit')): ?>
            </a>
        <?php else: ?>
            </span>
        <?php endif; ?>
    </div>
</header>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partials\header-v6.blade.php ENDPATH**/ ?>