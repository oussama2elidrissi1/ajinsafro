<?php
    $sidebarContext = $sidebarContext ?? 'admin-v6';

    $user = auth()->user();
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $brandLogo = \App\Models\Setting::brandLogoUrl('dark');

    $initials = 'AD';
    if ($user && isset($user->name)) {
        $name = trim((string) $user->name);
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name) ?: [];
            $first = isset($parts[0]) ? strtoupper(mb_substr((string) $parts[0], 0, 1)) : '';
            $last = isset($parts[count($parts) - 1]) ? strtoupper(mb_substr((string) $parts[count($parts) - 1], 0, 1)) : '';
            $initials = trim($first . $last) ?: strtoupper(mb_substr($name, 0, 1));
        }
    }

    $roleLabel = 'Admin';
    try {
        $roleLabel = (string) ($user?->getRoleNames()?->first() ?? 'Admin');
    } catch (Throwable $e) {
        $roleLabel = 'Admin';
    }

    $brandHref = \Illuminate\Support\Facades\Route::has('admin.dashboard.v6')
        ? route('admin.dashboard.v6')
        : (\Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin'));

    $items = (array) (config('admin_menu.items') ?? []);

    $canSee = function ($permission) use ($user) {
        if ($permission === null) return true;
        if (!$user || !method_exists($user, 'can')) return false;

        $perms = is_array($permission) ? $permission : [$permission];
        foreach ($perms as $perm) {
            $perm = is_string($perm) ? trim($perm) : '';
            if ($perm === '') continue;
            try {
                if ($user->can($perm)) return true;
            } catch (Throwable $e) {
                // ignore
            }
        }
        return false;
    };

    $buildHref = function (array $item) {
        $route = $item['route'] ?? null;
        if (is_string($route) && $route !== '' && \Illuminate\Support\Facades\Route::has($route)) {
            $url = route($route);
            $query = $item['query'] ?? null;
            if (is_array($query) && count($query)) {
                $url .= ((strpos($url, '?') !== false) ? '&' : '?') . http_build_query($query);
            }
            return $url;
        }
        return 'javascript:void(0);';
    };

    $isActive = function (array $item) {
        $patterns = $item['active_patterns'] ?? [];
        $patterns = is_array($patterns) ? $patterns : [$patterns];
        foreach ($patterns as $p) {
            if (is_string($p) && $p !== '' && request()->routeIs($p)) return true;
        }
        $route = $item['route'] ?? null;
        if (is_string($route) && $route !== '' && request()->routeIs($route)) return true;
        return false;
    };

    $renderItems = function (array $items, int $depth = 0) use (&$renderItems, $canSee, $buildHref, $isActive) {
        $html = '<ul class="aj-sidebar-v2__list aj-sidebar-v2__list--depth-' . $depth . '">';

        foreach ($items as $item) {
            if (!is_array($item)) continue;

            $permission = $item['permission'] ?? null;
            if (!$canSee($permission)) continue;

            $children = $item['children'] ?? [];
            $children = is_array($children) ? $children : [];

            // Filter children by permissions too
            $filteredChildren = [];
            foreach ($children as $child) {
                if (!is_array($child)) continue;
                if (!$canSee($child['permission'] ?? null)) continue;
                $filteredChildren[] = $child;
            }

            $hasChildren = count($filteredChildren) > 0;
            $active = $isActive($item);
            if (!$active && $hasChildren) {
                foreach ($filteredChildren as $child) {
                    if ($isActive($child)) { $active = true; break; }
                }
            }

            $itemClasses = ['aj-sidebar-v2__item'];
            if ($active) $itemClasses[] = 'is-active';
            if ($hasChildren && $active) $itemClasses[] = 'is-open';

            $label = (string) ($item['label'] ?? '');
            $icon = (string) ($item['icon'] ?? '');
            $href = $buildHref($item);

            if ($hasChildren) {
                $html .= '<li class="' . e(implode(' ', $itemClasses)) . '">';
                $html .= '<div class="aj-sidebar-v2__link-group">';
                $html .= '<a href="' . e($href) . '" class="aj-sidebar-v2__link aj-sidebar-v2__link--parent">';
                if ($icon !== '') {
                    $html .= '<span class="aj-sidebar-v2__icon"><i class="' . e($icon) . '"></i></span>';
                }
                $html .= '<span class="aj-sidebar-v2__label">' . e($label) . '</span>';
                $html .= '</a>';
                $html .= '<button type="button" class="aj-sidebar-v2__link aj-sidebar-v2__toggle" data-aj-sidebar-toggle aria-expanded="' . ($active ? 'true' : 'false') . '" aria-label="Afficher le sous-menu ' . e($label) . '">';
                $html .= '<span class="aj-sidebar-v2__chevron"><i class="bx bx-chevron-down"></i></span>';
                $html .= '</button>';
                $html .= '</div>';
                $html .= '<div class="aj-sidebar-v2__submenu">' . $renderItems($filteredChildren, $depth + 1) . '</div>';
                $html .= '</li>';
            } else {
                $html .= '<li class="' . e(implode(' ', $itemClasses)) . '">';
                $html .= '<a href="' . e($href) . '" class="aj-sidebar-v2__link">';
                if ($icon !== '') {
                    $html .= '<span class="aj-sidebar-v2__icon"><i class="' . e($icon) . '"></i></span>';
                }
                $html .= '<span class="aj-sidebar-v2__label">' . e($label) . '</span>';
                $html .= '</a>';
                $html .= '</li>';
            }
        }

        $html .= '</ul>';
        return $html;
    };

    $menuHtml = '';
    try {
        $menuHtml = $renderItems($items);
    } catch (Throwable $e) {
        // Never break the whole admin UI because of a sidebar rendering issue.
        $menuHtml = '';
    }
?>

<aside class="admin-v6-sidebar" id="adminV6Sidebar" aria-label="Navigation Ajinsafro">
    <div class="admin-v6-sidebar-head">
        <button type="button" class="admin-v6-sidebar-toggle" id="adminV6SidebarToggle" aria-label="Réduire / ouvrir la sidebar">
            <i class="bx bx-menu"></i>
        </button>
    </div>

    <div class="aj-sidebar-v2" data-aj-sidebar-v2 data-sidebar-context="<?php echo e($sidebarContext); ?>">
        <div class="aj-sidebar-v2__brand">
            <a href="<?php echo e($brandHref); ?>" class="aj-sidebar-v2__brand-link" aria-label="<?php echo e($brandName); ?>">
                <img src="<?php echo e($brandLogo); ?>" alt="<?php echo e($brandName); ?>" class="aj-sidebar-v2__brand-logo">
            </a>
        </div>

        <div class="aj-sidebar-v2__profile">
            <div class="aj-sidebar-v2__avatar-wrap">
                <img src="<?php echo e($user?->avatar_url); ?>" alt="<?php echo e($user?->name ?? 'Admin'); ?>" class="aj-sidebar-v2__avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                <span class="aj-sidebar-v2__avatar-fallback"><?php echo e($initials); ?></span>
            </div>
            <div class="aj-sidebar-v2__profile-name"><?php echo e($user?->name ?? 'Admin'); ?></div>
            <div class="aj-sidebar-v2__profile-role"><?php echo e($roleLabel); ?></div>
            <?php if(\Illuminate\Support\Facades\Route::has('admin.profile.edit')): ?>
                <a href="<?php echo e(route('admin.profile.edit')); ?>" class="aj-sidebar-v2__profile-link">
                    <i class="bx bx-user-circle"></i>
                    <span>Mon profil</span>
                </a>
            <?php endif; ?>
        </div>

        <nav class="aj-sidebar-v2__nav" aria-label="Navigation administration">
            <?php echo $menuHtml; ?>


            <div class="aj-sidebar-v2__account">
                <div class="aj-sidebar-v2__section-title">Compte</div>
                <ul class="aj-sidebar-v2__list aj-sidebar-v2__list--depth-0">
                    <?php if(\Illuminate\Support\Facades\Route::has('admin.profile.edit')): ?>
                        <li class="aj-sidebar-v2__item <?php echo e(request()->routeIs('admin.profile.*') ? 'is-active' : ''); ?>">
                            <a href="<?php echo e(route('admin.profile.edit')); ?>" class="aj-sidebar-v2__link">
                                <span class="aj-sidebar-v2__icon"><i class="bx bx-user-circle"></i></span>
                                <span class="aj-sidebar-v2__label">Mon profil</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if(\Illuminate\Support\Facades\Route::has('logout.get')): ?>
                        <li class="aj-sidebar-v2__item is-danger">
                            <a href="<?php echo e(route('logout.get')); ?>" class="aj-sidebar-v2__link">
                                <span class="aj-sidebar-v2__icon"><i class="bx bx-power-off"></i></span>
                                <span class="aj-sidebar-v2__label">Déconnexion</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </div>
</aside>

<div class="admin-v6-overlay" id="adminV6Overlay" aria-hidden="true"></div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partials\sidebar-v6.blade.php ENDPATH**/ ?>