<?php
    $adminV2User          = $adminV2User          ?? auth()->user();
    $adminV2UserName      = $adminV2UserName      ?? ($adminV2User?->name ?? 'Admin');
    $adminV2UserRole      = $adminV2UserRole      ?? ($adminV2User?->getRoleNames()->first() ?? 'Administrateur');
    $adminV2Initials      = $adminV2Initials      ?? 'AD';
    $adminV2AvatarUrl     = $adminV2AvatarUrl     ?? null;
    $adminV2UnreadCount   = $adminV2UnreadCount   ?? 0;
    $adminV2PendingCount  = $adminV2PendingCount  ?? 0;

    $v6Title = trim((string) View::yieldContent('page_title', View::yieldContent('title', 'Espace Admin')));
    $v6DateLabel = now('Africa/Casablanca')->locale('fr')->translatedFormat('l d F Y');
?>

<header class="aj-v6-topbar" id="aj-admin-v2-topbar">
    <div class="aj-v6-topbar-left">
        <button type="button" class="aj-hamburger" id="aj-admin-v2-hamburger" aria-label="Ouvrir le menu">
            <i class="bx bx-menu"></i>
        </button>

        <div class="aj-v6-page-meta">
            <h1 class="aj-v6-page-title"><?php echo e($v6Title); ?></h1>
            <div class="aj-v6-page-breadcrumb">Accueil <span>›</span> Tableau de bord</div>
        </div>
    </div>

    <div class="aj-v6-topbar-center">
        <div class="aj-v6-search">
            <span class="aj-search-icon"><i class="bx bx-search"></i></span>
            <input type="text" placeholder="Rechercher (voyage, agence, réservation, client...)">
        </div>
    </div>

    <div class="aj-v6-topbar-actions">
        <div class="aj-v6-chip aj-v6-date-chip">
            <i class="bx bx-calendar"></i>
            <span><?php echo e($v6DateLabel); ?></span>
        </div>
        <button type="button" class="aj-v6-chip">
            <i class="bx bx-slider-alt"></i>
            <span>Filtres</span>
        </button>

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

        <?php if (! empty(trim($__env->yieldContent('header_primary_action')))): ?>
            <?php echo $__env->yieldContent('header_primary_action'); ?>
        <?php elseif(\Illuminate\Support\Facades\Route::has('admin.reservations.create')): ?>
            <a href="<?php echo e(route('admin.reservations.create')); ?>" class="aj-v6-primary-btn">
                <i class="bx bx-plus"></i>
                <span>Réservations</span>
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