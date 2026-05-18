<?php
    // Source of truth: wp-plugin/ajinsafro-traveler-home/parts/header.php
    $defaults = [
        'enabled' => true,
        'topbar_enabled' => true,
        'phone' => '+212 5 39 32 38 74',
        'email' => 'contact@ajinsafro.ma',
        'socials' => [
            'facebook' => '#',
            'twitter' => '#',
            'instagram' => '#',
            'youtube' => '#',
            'linkedin' => '#',
        ],
        'navbar_enabled' => true,
        'logo_url' => '',
        'show_auth_links' => true,
        'login_url' => rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/login',
        'signup_url' => rtrim((string) config('app.public_url', 'https://ajinsafro.net'), '/') . '/register',
        'menu_source' => 'laravel_links',
        'links' => [],
        'lowcost_enabled' => true,
        'lowcost_text' => 'Formule low cost',
        'lowcost_url' => '#',
    ];

    $raw = \App\Models\Setting::getValue('wp_header');
    $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
    $hdr = is_array($decoded) ? array_replace_recursive($defaults, $decoded) : $defaults;

    $socials = isset($hdr['socials']) && is_array($hdr['socials']) ? $hdr['socials'] : [];
    $socialIcons = [
        'facebook' => '<i class="fab fa-facebook-f"></i>',
        'twitter' => '<i class="fab fa-twitter"></i>',
        'youtube' => '<i class="fab fa-youtube"></i>',
        'instagram' => '<i class="fab fa-instagram"></i>',
        'linkedin' => '<i class="fab fa-linkedin-in"></i>',
    ];

    $user = request()->user();
    $voyagesPageUrl = url('/voyages');
    $defaultMenuItems = [
        ['label' => 'Voyages', 'url' => $voyagesPageUrl, 'icon' => 'fas fa-suitcase-rolling', 'active' => false, 'children' => []],
        ['label' => 'Hebergement', 'url' => '#hebergement', 'icon' => 'fas fa-hotel', 'active' => false, 'children' => []],
        ['label' => 'Activites', 'url' => '#activites', 'icon' => 'fas fa-camera', 'active' => false, 'children' => []],
        ['label' => 'Transfert', 'url' => '#transfert', 'icon' => 'fas fa-car-side', 'active' => false, 'children' => []],
        ['label' => 'Hajj & Omra', 'url' => '#hajj-omra', 'icon' => 'fas fa-kaaba', 'active' => false, 'children' => []],
        ['label' => 'Votre guide', 'url' => '#guide', 'icon' => 'fas fa-map-signs', 'active' => false, 'children' => []],
    ];

    $portalLogoutUsesPartner = $portalLogoutUsesPartner ?? true;
    $profileUrl = null;

    if ($user) {
        if (\Illuminate\Support\Facades\Route::has('admin.profile.edit')) {
            $profileUrl = route('admin.profile.edit');
        } elseif (\Illuminate\Support\Facades\Route::has('partner.profile.show')) {
            $profileUrl = route('partner.profile.show');
        }
    }

    $navLinks = !empty($hdr['links']) && is_array($hdr['links']) ? $hdr['links'] : [];
    if (empty($navLinks)) {
        $navLinks = $defaultMenuItems;
    }
?>

<?php if(!empty($hdr['enabled'])): ?>
<header class="aj-header" id="aj-header">
    <?php if(!empty($hdr['topbar_enabled'])): ?>
    <div class="aj-topbar">
        <div class="aj-container aj-topbar__inner">
            <div class="aj-topbar__left">
                <div class="aj-topbar__socials">
                    <?php $__currentLoopData = $socialIcons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $icon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $socialUrl = !empty($socials[$key]) ? (string) $socials[$key] : ''; ?>
                        <?php if($socialUrl !== ''): ?>
                            <a href="<?php echo e($socialUrl); ?>" class="aj-topbar__social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php echo e(ucfirst($key)); ?>">
                                <?php echo $icon; ?>

                            </a>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="aj-topbar__contact">
                    <?php if(!empty($hdr['email'])): ?>
                        <span class="aj-topbar__item">
                            <i class="far fa-envelope"></i>
                            <?php echo e($hdr['email']); ?>

                        </span>
                    <?php endif; ?>
                    <?php if(!empty($hdr['phone'])): ?>
                        <span class="aj-topbar__item">
                            <i class="fas fa-phone"></i>
                            <?php echo e($hdr['phone']); ?>

                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="aj-topbar__right">
                <?php if(!empty($hdr['show_auth_links'])): ?>
                <div class="aj-topbar__auth">
                    <?php if($user): ?>
                        <span class="aj-topbar__auth-link d-inline-flex align-items-center gap-2">
                            <img src="<?php echo e($user->avatar_url); ?>" alt="Avatar" class="rounded-circle" style="width:24px;height:24px;object-fit:cover;">
                            <span><?php echo e($user->name); ?></span>
                        </span>
                        <?php if($portalLogoutUsesPartner): ?>
                            <form method="POST" action="<?php echo e(route('partner.logout')); ?>" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="aj-topbar__auth-link aj-topbar__auth-link--signup border-0">
                                    <?php echo e(__('SE DÉCONNECTER')); ?>

                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?php echo e(route('logout.get')); ?>" class="aj-topbar__auth-link aj-topbar__auth-link--signup">
                                <?php echo e(__('SE DÉCONNECTER')); ?>

                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?php echo e($hdr['login_url']); ?>" class="aj-topbar__auth-link">
                            <?php echo e(__('SE CONNECTER')); ?>

                        </a>
                        <a href="<?php echo e($hdr['signup_url']); ?>" class="aj-topbar__auth-link aj-topbar__auth-link--signup">
                            <?php echo e(__("S'INSCRIRE")); ?>

                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(!empty($hdr['navbar_enabled'])): ?>
    <nav class="aj-navbar" id="aj-navbar">
        <div class="aj-container aj-navbar__inner">
            <div class="aj-navbar__logo">
                <?php if(!empty($hdr['logo_url'])): ?>
                    <a href="<?php echo e(url('/')); ?>">
                        <img src="<?php echo e($hdr['logo_url']); ?>" alt="<?php echo e(config('app.name', 'Ajinsafro')); ?>" class="aj-navbar__logo-img" loading="eager" fetchpriority="high">
                    </a>
                <?php else: ?>
                    <a href="<?php echo e(url('/')); ?>">
                        <img src="<?php echo e(URL::asset('build/images/logo-dark.png')); ?>" alt="<?php echo e(config('app.name', 'Ajinsafro')); ?>" class="aj-navbar__logo-img" loading="eager" fetchpriority="high">
                    </a>
                <?php endif; ?>
            </div>

            <button type="button" class="aj-navbar__burger aj-header__toggle" id="aj-burger" aria-label="Menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>

            <div class="aj-drawer aj-header__drawer" id="aj-drawer" aria-hidden="true">
                <div class="aj-drawer__header">
                    <span class="aj-drawer__title"><?php echo e(__('Menu')); ?></span>
                    <button type="button" class="aj-drawer__close" id="aj-drawer-close" aria-label="<?php echo e(__('Fermer')); ?>">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <?php if(!empty($hdr['show_auth_links'])): ?>
                <div class="aj-drawer__auth aj-header__auth--mobile">
                    <?php if($user): ?>
                        <div class="aj-auth-link aj-auth-link--block d-inline-flex align-items-center gap-2">
                            <img src="<?php echo e($user->avatar_url); ?>" alt="Avatar" class="rounded-circle" style="width:24px;height:24px;object-fit:cover;">
                            <span><?php echo e($user->name); ?></span>
                        </div>
                        <?php if($profileUrl): ?>
                            <a href="<?php echo e($profileUrl); ?>" class="aj-auth-link aj-auth-link--block"><?php echo e(__('Mon profil')); ?></a>
                        <?php endif; ?>
                        <?php if($portalLogoutUsesPartner): ?>
                            <form method="POST" action="<?php echo e(route('partner.logout')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="aj-auth-link aj-auth-link--block border-0 bg-transparent text-start w-100">
                                    <?php echo e(__('Deconnexion')); ?>

                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?php echo e(route('logout.get')); ?>" class="aj-auth-link aj-auth-link--block"><?php echo e(__('Deconnexion')); ?></a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="<?php echo e($hdr['login_url']); ?>" class="aj-auth-link aj-auth-link--block"><?php echo e(__('Se connecter')); ?></a>
                        <a href="<?php echo e($hdr['signup_url']); ?>" class="aj-auth-link aj-auth-link--signup aj-auth-link--block"><?php echo e(__("S'inscrire")); ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="aj-navbar__menu" id="aj-nav-menu">
                    <ul class="aj-nav-list">
                        <?php $__currentLoopData = $navLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $label = !empty($link['label']) ? (string) $link['label'] : '';
                                $url = !empty($link['url']) ? (string) $link['url'] : '#';
                                $icon = !empty($link['icon']) ? (string) $link['icon'] : '';
                                $children = !empty($link['children']) && is_array($link['children']) ? $link['children'] : [];
                                $hasSub = !empty($children);
                                $isActive = !empty($link['active']);
                                $isHighlight = !empty($link['highlight']);
                            ?>
                            <li class="<?php echo e($hasSub ? 'aj-has-sub' : ''); ?><?php echo e($isActive ? ' aj-active' : ''); ?><?php echo e($isHighlight ? ' aj-highlight' : ''); ?>">
                                <a href="<?php echo e($url); ?>" class="<?php echo e($isHighlight ? 'aj-nav-highlight' : ''); ?>">
                                    <?php if($icon): ?>
                                        <i class="<?php echo e($icon); ?>"></i>
                                    <?php endif; ?>
                                    <span><?php echo e($label); ?></span>
                                    <?php if($hasSub): ?>
                                        <i class="fas fa-chevron-down aj-caret"></i>
                                    <?php endif; ?>
                                </a>
                                <?php if($hasSub): ?>
                                    <ul class="aj-sub-menu">
                                        <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li>
                                                <a href="<?php echo e(!empty($child['url']) ? $child['url'] : '#'); ?>">
                                                    <?php if(!empty($child['icon'])): ?>
                                                        <i class="<?php echo e($child['icon']); ?>"></i>
                                                    <?php endif; ?>
                                                    <?php echo e(!empty($child['label']) ? $child['label'] : ''); ?>

                                                </a>
                                            </li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>

                <?php if(!empty($hdr['lowcost_enabled'])): ?>
                <div class="aj-drawer__lowcost">
                    <a href="<?php echo e(!empty($hdr['lowcost_url']) ? $hdr['lowcost_url'] : '#'); ?>" class="aj-lowcost-btn">
                        <i class="fas fa-fire"></i>
                        <span><?php echo e(!empty($hdr['lowcost_text']) ? $hdr['lowcost_text'] : 'Formule low cost'); ?></span>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if(!empty($hdr['lowcost_enabled'])): ?>
            <div class="aj-navbar__lowcost aj-header__lowcost--desktop">
                <a href="<?php echo e(!empty($hdr['lowcost_url']) ? $hdr['lowcost_url'] : '#'); ?>" class="aj-lowcost-btn aj-lowcost-btn--animate">
                    <i class="fas fa-fire aj-lowcost-btn__icon"></i>
                    <span><?php echo e(!empty($hdr['lowcost_text']) ? $hdr['lowcost_text'] : 'Formule low cost'); ?></span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    <?php endif; ?>
</header>
<?php endif; ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views/partner_v2/partials/header.blade.php ENDPATH**/ ?>