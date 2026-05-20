<?php
    $adminV2User          = $adminV2User          ?? auth()->user();
    $adminV2UserName      = $adminV2UserName      ?? ($adminV2User?->name ?? 'Admin');
    $adminV2UserRole      = $adminV2UserRole      ?? ($adminV2User?->getRoleNames()->first() ?? 'Administrateur');
    $adminV2Initials      = $adminV2Initials      ?? 'AD';
    $adminV2AvatarUrl     = $adminV2AvatarUrl     ?? null;
    $adminV2UnreadCount   = $adminV2UnreadCount   ?? 0;
    $adminV2PendingCount  = $adminV2PendingCount  ?? 0;
?>

<header class="aj-topbar" id="aj-admin-v2-topbar">
    <div class="aj-topbar-left">
        <button type="button" class="aj-hamburger" id="aj-admin-v2-hamburger" aria-label="Ouvrir le menu">
            <i class="bx bx-menu"></i>
        </button>

        <div class="aj-topbar-search">
            <span class="aj-search-icon"><i class="bx bx-search"></i></span>
            <input type="text" placeholder="Rechercher (voyage, agence, réservation...)">
            <span class="aj-search-shortcut">�O~ K</span>
        </div>
    </div>

    <div class="aj-topbar-actions">

        <?php if(\Illuminate\Support\Facades\Route::has('admin.reservations.en-attente')): ?>
            <a href="<?php echo e(route('admin.reservations.en-attente')); ?>"
               class="aj-topbar-notif"
               title="Réservations en attente">
                <i class="bx bx-bell"></i>
                <?php if($adminV2PendingCount > 0): ?>
                    <b class="aj-notif-badge"><?php echo e(min($adminV2PendingCount, 99)); ?></b>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index')): ?>
            <a href="<?php echo e(route('admin.messagerie.index')); ?>"
               class="aj-topbar-notif"
               title="Messagerie">
                <i class="bx bx-message-rounded-dots"></i>
                <?php if($adminV2UnreadCount > 0): ?>
                    <b class="aj-notif-badge"><?php echo e(min($adminV2UnreadCount, 99)); ?></b>
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
            <span class="aj-topbar-profile-caret"><i class="bx bx-chevron-down"></i></span>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.profile.edit')): ?>
            </a>
        <?php else: ?>
            </span>
        <?php endif; ?>

    </div>
</header>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partials\header-v2.blade.php ENDPATH**/ ?>