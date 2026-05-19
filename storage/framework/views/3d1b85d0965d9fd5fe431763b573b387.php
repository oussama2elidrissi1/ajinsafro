<?php $__env->startSection('title', 'Dashboard V6'); ?>

<?php
    $dashboardUser = auth()->user();
    $dashboardUserName = $dashboardUser?->name ?? 'Admin';
    $dashboardUserRole = $dashboardUser?->getRoleNames()->first() ?? 'Administrateur';
    $dashboardInitials = strtoupper(collect(preg_split('/\s+/', trim((string) $dashboardUserName)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    if ($dashboardInitials === '') { $dashboardInitials = 'AD'; }
    $dashboardBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $dashboardBrandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $dashboardDateLabel = now('Africa/Casablanca')->locale('fr')->translatedFormat('l d F Y');

    $source = file_get_contents(resource_path('views/admin/dashboard/dashboard_v6_ajinsafro_kpi_v4.html'));
    preg_match('/<style>([\s\S]*?)<\/style>/i', $source, $styleMatch);
    preg_match('/<body[^>]*>([\s\S]*?)<\/body>/i', $source, $bodyMatch);
    $v6Css = trim($styleMatch[1] ?? '');
    $v6Body = $bodyMatch[1] ?? '';

    $v6Body = preg_replace('/<script>[\s\S]*?<\/script>\s*$/i', '', $v6Body);

    $v6Body = str_replace(
        '<div class="brand-mark" aria-hidden="true">▰</div>',
        '<div class="brand-mark" aria-hidden="true"><img src="' . e($dashboardBrandLogo) . '" alt="' . e($dashboardBrandName) . '" style="width:28px;height:28px;object-fit:contain;filter:brightness(0) invert(1)"></div>',
        $v6Body
    );
    $v6Body = str_replace('<strong>AjinSafro.ma</strong>', '<strong>' . e($dashboardBrandName) . '</strong>', $v6Body);
    $v6Body = str_replace('<div class="avatar">A</div>', '<div class="avatar">' . e($dashboardInitials) . '</div>', $v6Body);
    $v6Body = str_replace('<strong>Admin</strong>', '<strong>' . e($dashboardUserName) . '</strong>', $v6Body);
    $v6Body = str_replace('<span>Administrateur</span>', '<span>' . e($dashboardUserRole) . '</span>', $v6Body);
    $v6Body = str_replace('<h1>Dashboard V6</h1>', '<h1>Dashboard V6</h1>', $v6Body);
    $v6Body = str_replace('📅 mardi 19 mai 2026', '📅 ' . e($dashboardDateLabel), $v6Body);
    $v6Body = str_replace('<button class="primary-btn" type="button">+ Réservations</button>', '<a class="primary-btn" href="' . e(route('admin.reservations.create')) . '">+ Réservations</a>', $v6Body);
    $v6Body = str_replace('<a href="#" class="active">Dashboard V6</a>', '<a href="' . e(route('admin.dashboard.v6')) . '" class="active">Dashboard V6</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V5</a>', '<a href="' . e(route('admin.dashboard.v5')) . '">Dashboard V5</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V4</a>', '<a href="' . e(route('admin.dashboard.v4')) . '">Dashboard V4</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V3</a>', '<a href="' . e(route('admin.dashboard.v3')) . '">Dashboard V3</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V2</a>', '<a href="' . e(route('admin.dashboard.v2')) . '">Dashboard V2</a>', $v6Body);
?>

<?php $__env->startPush('styles'); ?>
<style>
<?php echo e($v6Css); ?>

</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $v6Body; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }

    const root = document.documentElement;
    const saved = localStorage.getItem('aj-v6-sidebar');
    if (saved === 'collapsed' || saved === 'expanded') {
        root.dataset.sidebar = saved;
    }

    const toggle = function () {
        const next = root.dataset.sidebar === 'collapsed' ? 'expanded' : 'collapsed';
        root.dataset.sidebar = next;
        localStorage.setItem('aj-v6-sidebar', next);
    };

    document.getElementById('sidebarToggle')?.addEventListener('click', toggle);
    document.getElementById('mobileToggle')?.addEventListener('click', toggle);
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.dashboard-v5', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\dashboard\v6\index.blade.php ENDPATH**/ ?>