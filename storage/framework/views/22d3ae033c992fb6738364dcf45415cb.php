<?php $__env->startSection('title', 'Dashboard V5'); ?>

<?php
    $dashboardUser = auth()->user();
    $dashboardUserName = $dashboardUser?->name ?? 'Manager';
    $dashboardUserRole = $dashboardUser?->getRoleNames()->first() ?? 'chef_commercial';
    $dashboardInitials = strtoupper(collect(preg_split('/\s+/', trim((string) $dashboardUserName)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    if ($dashboardInitials === '') {
        $dashboardInitials = 'MC';
    }

    $dashboardBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro.ma');
    $dashboardBrandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $dashboardDateLabel = now('Africa/Casablanca')->locale('fr')->translatedFormat('l d F Y');
    $dashboardDateTimeLabel = now('Africa/Casablanca')->locale('fr')->translatedFormat('l d F Y - H:i');

    $sourceHtml = file_get_contents(resource_path('views/admin/dashboard/index ajinsafro.html'));
    preg_match('/<body[^>]*>(.*)<\/body>/is', $sourceHtml, $matches);
    $bodyHtml = $matches[1] ?? '';
    $bodyHtml = preg_replace('/<script>[\s\S]*?<\/script>\s*$/', '', $bodyHtml);

    $activeLinkClasses = 'block py-2 px-3 text-xs font-bold rounded-lg bg-[#e8f4fc] text-[#0b548b] shadow-sm';
    $inactiveLinkClasses = 'sidebar-item block py-2 px-3 text-xs font-semibold text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900';
    $dashboardLink = function (string $route, string $label) use ($activeLinkClasses, $inactiveLinkClasses): string {
        $active = request()->routeIs($route);
        $classes = $active ? $activeLinkClasses : $inactiveLinkClasses;

        return '<a href="' . e(route($route)) . '" class="' . $classes . '"><span class="dashboard-v5-nav-label">' . e($label) . '</span></a>';
    };

    $dashboardMenuBlock = <<<'HTML'
                <!-- Dashboard Collapsible Group -->
                <div class="space-y-1">
                    <button class="w-full flex items-center justify-between py-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <span class="flex items-center space-x-2">
                            <i data-lucide="layout-dashboard" class="w-4 h-4 text-[#0081bc]"></i>
                            <span class="dashboard-v5-nav-label">Tableau de bord</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 dashboard-v5-nav-caret"></i>
                    </button>
                    <div class="ml-2 pl-4 border-l border-slate-200 space-y-1 dashboard-v5-submenu">
                        __VUE_GLOBALE__
                        __STATS__
                        __ALERTES__
                        __V2__
                        __V3__
                        __V4__
                        __V5__
                    </div>
                </div>
HTML;

    $dashboardMenuBlock = str_replace(
        ['__VUE_GLOBALE__', '__STATS__', '__ALERTES__', '__V2__', '__V3__', '__V4__', '__V5__'],
        [
            $dashboardLink('admin.dashboard.vue-globale', "Vue d'ensemble"),
            $dashboardLink('admin.dashboard.statistiques', 'Statistiques'),
            $dashboardLink('admin.dashboard.alertes', 'Alertes'),
            $dashboardLink('admin.dashboard.v2', 'Dashboard V2'),
            $dashboardLink('admin.dashboard.v3', 'Dashboard V3'),
            $dashboardLink('admin.dashboard.v4', 'Dashboard V4'),
            $dashboardLink('admin.dashboard.v5', 'Dashboard V5'),
        ],
        $dashboardMenuBlock
    );

    $bodyHtml = str_replace(
        '<div class="flex h-screen w-screen overflow-hidden">',
        '<div id="dashboardV5Page" class="dashboard-v5-page flex h-screen w-screen overflow-hidden">',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 hidden lg:flex h-full z-30">',
        '<aside class="dashboard-v5-sidebar w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 hidden lg:flex h-full z-30">',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="flex-1 flex flex-col overflow-hidden h-full bg-[#f8fafc]">',
        '<div class="dashboard-v5-main flex-1 flex flex-col overflow-hidden h-full bg-[#f8fafc]">',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="h-14 bg-white flex items-center justify-center px-4 shrink-0 border-b border-slate-200 shadow-sm">
                <a href="#" class="flex items-center justify-center shrink-0">
                    <img src="https://booking.ajinsafro.net/storage/home-settings/header/6hZldTmcYICYg6eP8O52lt8GnBe8iuHSi5xiWrdQ.png" alt="Ajinsafro.ma" class="h-8 w-auto max-w-[170px] object-contain transition-opacity duration-300 hover:opacity-95" onerror="this.onerror=null; this.src=\'https://via.placeholder.com/150x32/0b548b/ffffff?text=Ajinsafro.ma\';">
                </a>
            </div>',
        '<div class="h-14 bg-white flex items-center justify-between px-4 shrink-0 border-b border-slate-200 shadow-sm">
                <a href="' . e(route('admin.dashboard.v5')) . '" class="flex items-center justify-center shrink-0">
                    <img src="' . e($dashboardBrandLogo) . '" alt="' . e($dashboardBrandName) . '" class="dashboard-v5-logo-text h-8 w-auto max-w-[170px] object-contain transition-opacity duration-300 hover:opacity-95">
                </a>
                <button id="dashboardV5SidebarToggle" type="button" class="dashboard-v5-sidebar-toggle inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700" aria-label="Basculer la sidebar">
                    <i data-lucide="panel-left-close" class="w-4 h-4"></i>
                </button>
            </div>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="p-5 border-b border-slate-100 flex flex-col items-center text-center bg-white">
                <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-[#0081bc] to-[#005a84] flex items-center justify-center text-base font-bold text-white mb-2 shadow-md">
                    M
                </div>
                <div class="text-sm font-bold text-slate-800">Manager</div>
                <div class="text-[11px] font-semibold text-slate-400">chef_commercial</div>
            </div>',
        '<div class="dashboard-v5-desktop-profile p-5 border-b border-slate-100 flex flex-col items-center text-center bg-white">
                <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-[#0081bc] to-[#005a84] flex items-center justify-center text-base font-bold text-white mb-2 shadow-md">' . e($dashboardInitials) . '</div>
                <div class="dashboard-v5-profile-info">
                    <div class="text-sm font-bold text-slate-800">' . e($dashboardUserName) . '</div>
                    <div class="text-[11px] font-semibold text-slate-400">' . e($dashboardUserRole) . '</div>
                </div>
            </div>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<!-- Dashboard Collapsible Group -->
                <div class="space-y-1">
                    <button class="w-full flex items-center justify-between py-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <span class="flex items-center space-x-2">
                            <i data-lucide="layout-dashboard" class="w-4 h-4 text-[#0081bc]"></i>
                            <span>Tableau de bord</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i>
                    </button>
                    <div class="ml-2 pl-4 border-l border-slate-200 space-y-1">
                        <a href="#" class="block py-2 px-3 text-xs font-bold rounded-lg bg-[#e8f4fc] text-[#0b548b] shadow-sm">
                            Vue d\'ensemble
                        </a>
                        <a href="#" class="sidebar-item block py-2 px-3 text-xs font-semibold text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                            Statistiques
                        </a>
                        <a href="#" class="sidebar-item block py-2 px-3 text-xs font-semibold text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                            Alertes
                        </a>
                        <a href="#" class="sidebar-item block py-2 px-3 text-xs font-semibold text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                            Dashboard V2
                        </a>
                    </div>
                </div>',
        $dashboardMenuBlock,
        $bodyHtml
    );

    $bodyHtml = str_replace(
        'placeholder="Rechercher (voyage, agence, réservation...)"',
        'placeholder="Rechercher (voyage, agence, réservation...)"',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<img src="https://booking.ajinsafro.net/storage/home-settings/header/6hZldTmcYICYg6eP8O52lt8GnBe8iuHSi5xiWrdQ.png" alt="Ajinsafro" class="h-6 w-auto object-contain bg-white px-2 py-0.5 rounded border border-slate-100">',
        '<img src="' . e($dashboardBrandLogo) . '" alt="' . e($dashboardBrandName) . '" class="h-6 w-auto object-contain bg-white px-2 py-0.5 rounded border border-slate-100">',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="text-xs text-slate-500 font-medium">Mardi 19 mai 2026</div>',
        '<div class="text-xs text-slate-500 font-medium">' . e($dashboardDateLabel) . '</div>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<p class="text-xs font-semibold text-slate-400 mt-1">Vue d\'ensemble de votre activité — mardi 19 mai 2026 - 09:53</p>',
        '<p class="text-xs font-semibold text-slate-400 mt-1">Vue d\'ensemble de votre activité — ' . e($dashboardDateTimeLabel) . '</p>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<span>Mardi 19 mai 2026</span>',
        '<span>' . e($dashboardDateLabel) . '</span>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="text-xs font-semibold">Manager</div>
                            <div class="text-[10px] text-white/70">chef_commercial</div>',
        '<div class="text-xs font-semibold">' . e($dashboardUserName) . '</div>
                            <div class="text-[10px] text-white/70">' . e($dashboardUserRole) . '</div>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="w-8 h-8 rounded-full bg-cyan-600 border border-white/30 flex items-center justify-center text-xs font-bold text-white shadow shrink-0">
                            MC
                        </div>',
        '<div class="w-8 h-8 rounded-full bg-cyan-600 border border-white/30 flex items-center justify-center text-xs font-bold text-white shadow shrink-0">' . e($dashboardInitials) . '</div>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<img src="https://booking.ajinsafro.net/storage/home-settings/header/6hZldTmcYICYg6eP8O52lt8GnBe8iuHSi5xiWrdQ.png" alt="Ajinsafro.ma" class="h-7 w-auto object-contain">',
        '<img src="' . e($dashboardBrandLogo) . '" alt="' . e($dashboardBrandName) . '" class="h-7 w-auto object-contain">',
        $bodyHtml
    );
?>

<?php $__env->startPush('styles'); ?>
<style>
    body {
        font-family: 'Montserrat', sans-serif;
    }
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .sidebar-item {
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .sidebar-item:hover {
        transform: translateX(4px);
    }
    @media (min-width: 1024px) {
        .dashboard-v5-page .dashboard-v5-sidebar {
            width: 16rem !important;
            min-width: 16rem !important;
            max-width: 16rem !important;
            transition: width .25s ease;
        }
        .dashboard-v5-page .dashboard-v5-main {
            transition: width .25s ease;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar {
            width: 78px !important;
            min-width: 78px !important;
            max-width: 78px !important;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-logo-text,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-profile-info,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-nav-label,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-nav-caret,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-submenu,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .sidebar-item > span:last-child,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-desktop-profile {
            display: none !important;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar nav > .space-y-1 > button {
            justify-content: center;
            padding-left: .5rem;
            padding-right: .5rem;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar nav .sidebar-item,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar nav > a {
            justify-content: center;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .h-14 {
            justify-content: center !important;
            gap: .5rem;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar-toggle [data-lucide="panel-left-close"] {
            display: none;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar-toggle::before {
            content: ">";
            font-size: 14px;
            font-weight: 700;
            color: #475569;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $bodyHtml; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const page = document.getElementById('dashboardV5Page');
    const toggle = document.getElementById('dashboardV5SidebarToggle');
    const key = 'aj-dashboard-v5-sidebar-collapsed';

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    if (page && toggle && localStorage.getItem(key) === '1') {
        page.classList.add('is-sidebar-collapsed');
    }

    if (page && toggle) {
        toggle.addEventListener('click', function () {
            page.classList.toggle('is-sidebar-collapsed');
            localStorage.setItem(
                key,
                page.classList.contains('is-sidebar-collapsed') ? '1' : '0'
            );
        });
    }

    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const closeMobileMenuBtn = document.getElementById('closeMobileMenuBtn');
    const mobileSidebar = document.getElementById('mobileSidebar');
    const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');
    const mobileMenuContainer = document.getElementById('mobileMenuContainer');
    const desktopSidebarNav = document.querySelector('aside nav');

    if (desktopSidebarNav && mobileMenuContainer) {
        mobileMenuContainer.innerHTML = desktopSidebarNav.innerHTML;
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    window.toggleDropdown = function (menuId, arrowId) {
        const menu = document.getElementById(menuId);
        const arrow = document.getElementById(arrowId);

        if (!menu || !arrow) {
            return;
        }

        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            arrow.classList.add('rotate-180');
        } else {
            menu.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }
    };

    const toggleMobileMenu = function (isOpen) {
        if (!mobileSidebar || !mobileSidebarOverlay) {
            return;
        }

        if (isOpen) {
            mobileSidebarOverlay.classList.remove('hidden');
            setTimeout(function () {
                mobileSidebarOverlay.classList.add('opacity-100');
            }, 10);
            mobileSidebar.classList.remove('-translate-x-full');
        } else {
            mobileSidebarOverlay.classList.remove('opacity-100');
            mobileSidebarOverlay.classList.add('hidden');
            mobileSidebar.classList.add('-translate-x-full');
        }
    };

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function () { toggleMobileMenu(true); });
    }
    if (closeMobileMenuBtn) {
        closeMobileMenuBtn.addEventListener('click', function () { toggleMobileMenu(false); });
    }
    if (mobileSidebarOverlay) {
        mobileSidebarOverlay.addEventListener('click', function () { toggleMobileMenu(false); });
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.dashboard-v5', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\dashboard\v5\index.blade.php ENDPATH**/ ?>