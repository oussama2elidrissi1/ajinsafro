

<?php $__env->startSection('title', 'Espace Admin'); ?>
<?php $__env->startSection('page_title', 'Espace Admin'); ?>

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
    if (preg_match('/<section class="content">[\s\S]*?<\/section>\s*<\/main>/i', $v6Body, $contentMatch)) {
        $v6Body = '<div class="dashboard-v6 dashboard-v6-embedded">' . preg_replace('/<\/main>\s*$/i', '', $contentMatch[0]) . '</div>';
    } else {
        $v6Body = '<div class="dashboard-v6 dashboard-v6-embedded">' . $v6Body . '</div>';
    }

    $v6Body = str_replace(
        '<div class="brand-mark" aria-hidden="true">�-�</div>',
        '<div class="brand-mark" aria-hidden="true"><img src="' . e($dashboardBrandLogo) . '" alt="' . e($dashboardBrandName) . '" style="width:28px;height:28px;object-fit:contain;filter:brightness(0) invert(1)"></div>',
        $v6Body
    );
    $v6Body = str_replace('<strong>AjinSafro.ma</strong>', '<strong>' . e($dashboardBrandName) . '</strong>', $v6Body);
    $v6Body = str_replace('<div class="avatar">A</div>', '<div class="avatar">' . e($dashboardInitials) . '</div>', $v6Body);
    $v6Body = str_replace('<strong>Admin</strong>', '<strong>' . e($dashboardUserName) . '</strong>', $v6Body);
    $v6Body = str_replace('<span>Administrateur</span>', '<span>' . e($dashboardUserRole) . '</span>', $v6Body);
    $v6Body = str_replace('<h1>Dashboard V6</h1>', '<h1>Espace Admin</h1>', $v6Body);
    $v6Body = str_replace('�Y". mardi 19 mai 2026', '�Y". ' . e($dashboardDateLabel), $v6Body);
    $v6Body = str_replace('<button class="primary-btn" type="button">+ Réservations</button>', '<a class="primary-btn" href="' . e(route('admin.reservations.create')) . '">+ Réservations</a>', $v6Body);
    $v6Body = str_replace('<a href="#" class="active">Dashboard V6</a>', '<a href="' . e(route('admin.dashboard.v6')) . '" class="active">Espace Admin</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V5</a>', '<a href="' . e(route('admin.dashboard.v5')) . '">Dashboard V5</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V4</a>', '<a href="' . e(route('admin.dashboard.v4')) . '">Dashboard V4</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V3</a>', '<a href="' . e(route('admin.dashboard.v3')) . '">Dashboard V3</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V2</a>', '<a href="' . e(route('admin.dashboard.v2')) . '">Dashboard V2</a>', $v6Body);
    // Remove date + filters chips from the V6 template header.
    $v6Body = preg_replace('/<button class="chip"[^>]*>[\s\S]*?mardi[\s\S]*?<\/button>/i', '', $v6Body, 1);
    $v6Body = preg_replace('/<button class="chip"[^>]*>[\s\S]*?Filtres[\s\S]*?<\/button>/i', '', $v6Body, 1);
    // Remove "+ Réservations" primary action from the template header.
    $v6Body = preg_replace('/<(?:a|button)\s+[^>]*class="[^"]*primary-btn[^"]*"[^>]*>[\s\S]*?R�f©servations[\s\S]*?<\/(?:a|button)>/i', '', $v6Body, 1);
    // Keep V6 topbar structure (title + search + actions) as in the reference HTML.
?>

<?php $__env->startPush('styles'); ?>
<style>
<?php echo e($v6Css); ?>


/* Dashboard V6 Laravel fixes: stable collapse + 1280/1366 responsive density. */
.app-shell {
  height: 100vh;
  overflow: hidden;
}

.dashboard-v6 .topbar{
  display: grid !important;
  grid-template-columns: 1fr auto !important;
  align-items: center !important;
  gap: 12px !important;
}

.dashboard-v6 .topbar .page-title{
  min-width: 0 !important;
}

.dashboard-v6 .topbar .search-box{
  display: none !important;
}

.dashboard-v6 .topbar .actions{
  justify-self: end !important;
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  flex-wrap: nowrap !important;
}

/* Remove "date" + "Filtres" chips without affecting other actions */
.dashboard-v6 .topbar .actions .chip{
  display: none !important;
}
.dashboard-v6 .topbar .actions button.chip,
.dashboard-v6 .topbar .actions .date-chip{
  display: none !important;
}

@media (max-width: 900px){
  .dashboard-v6 .topbar{
    grid-template-columns: 1fr !important;
    row-gap: 10px !important;
  }
  .dashboard-v6 .topbar .actions{
    justify-self: start !important;
    overflow-x: auto !important;
    padding-bottom: 2px !important;
    -webkit-overflow-scrolling: touch;
  }
}

.topbar {
  overflow: visible;
}

html[data-sidebar="collapsed"] .content {
  max-width: none !important;
  width: 100% !important;
  margin: 0 !important;
  padding-left: 16px !important;
  padding-right: 16px !important;
}

html[data-sidebar="collapsed"] .dashboard-grid,
html[data-sidebar="collapsed"] .dashboard-grid.middle,
html[data-sidebar="collapsed"] .dashboard-grid.bottom {
  grid-template-columns: 1.35fr 1fr 1fr !important;
}

.kpi-card {
  grid-template-columns: 1fr 76px !important;
  gap: 10px !important;
}

.kpi-left {
  gap: 10px !important;
}

.kpi-icon {
  width: 44px !important;
  height: 44px !important;
  flex-basis: 44px !important;
  border-radius: 14px !important;
}

.kpi-sparkline {
  width: 76px !important;
}

.kpi-title,
.kpi-note {
  white-space: normal !important;
  overflow: visible !important;
  text-overflow: clip !important;
}

@media (max-width: 1380px) {
  .topbar {
    grid-template-columns: minmax(160px, 220px) minmax(220px, 1fr) auto !important;
    gap: 12px !important;
    padding: 0 16px !important;
  }

  .top-actions {
    gap: 7px !important;
  }

  .chip,
  .primary-btn {
    padding: 0 10px !important;
  }

  .kpi-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 12px !important;
  }

  .kpi-card {
    padding: 14px !important;
    min-height: 112px !important;
  }

  .dashboard-grid,
  .dashboard-grid.middle,
  .dashboard-grid.bottom {
    grid-template-columns: 1fr 1fr !important;
  }
}

@media (max-width: 1180px) {
  .kpi-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }

  .dashboard-grid,
  .dashboard-grid.middle,
  .dashboard-grid.bottom {
    grid-template-columns: 1fr !important;
  }
}

@media (max-width: 760px) {
  .topbar {
    grid-template-columns: 1fr !important;
    gap: 8px !important;
    padding: 8px 10px !important;
  }

  .top-actions {
    display: flex !important;
    flex-wrap: nowrap !important;
    gap: 6px !important;
    overflow-x: auto !important;
  }

  .top-actions .date-chip {
    display: none !important;
  }

  .kpi-grid {
    grid-template-columns: 1fr !important;
    gap: 10px !important;
  }

  .kpi-card {
    min-height: 86px !important;
    grid-template-columns: 46px 1fr !important;
    gap: 10px !important;
  }

  .kpi-sparkline {
    display: none !important;
  }

  .content {
    padding-left: 10px !important;
    padding-right: 10px !important;
  }

  html[data-sidebar="collapsed"] .content {
    padding-left: 12px !important;
    padding-right: 12px !important;
  }

  html[data-sidebar="collapsed"] .dashboard-grid,
  html[data-sidebar="collapsed"] .dashboard-grid.middle,
  html[data-sidebar="collapsed"] .dashboard-grid.bottom {
    grid-template-columns: 1fr !important;
  }

  html[data-sidebar="expanded"] .sidebar {
    width: var(--sidebar-open) !important;
    transform: translateX(0) !important;
  }

  html[data-sidebar="collapsed"] .sidebar,
  html[data-sidebar="collapsed"] .sidebar:hover {
    width: 0 !important;
    transform: translateX(-100%) !important;
  }
}

/* Dashboard V6 compact mode (no zoom/scale, pure sizing compaction). */
.dashboard-v6 { --topbar: 62px; }
.dashboard-v6 .topbar { padding: 0 14px !important; gap: 8px !important; }
.dashboard-v6 .chip,
.dashboard-v6 .icon-btn,
.dashboard-v6 .primary-btn { height: 34px !important; border-radius: 10px !important; font-size: 12px !important; }
.dashboard-v6 .search-box { height: 34px !important; border-radius: 10px !important; }
.dashboard-v6 .page-title h1 { font-size: 18px !important; line-height: 1.15 !important; }

.dashboard-v6 .content {
  max-width: none !important;
  padding: 12px 14px 18px !important;
  gap: 10px !important;
}

.dashboard-v6 .kpi-grid { gap: 10px !important; }
.dashboard-v6 .kpi-card {
  min-height: 82px !important;
  padding: 10px 12px !important;
  border-radius: 14px !important;
  grid-template-columns: 1fr 62px !important;
}
.dashboard-v6 .kpi-icon { width: 38px !important; height: 38px !important; flex-basis: 38px !important; border-radius: 10px !important; }
.dashboard-v6 .kpi-number { font-size: 24px !important; }
.dashboard-v6 .kpi-title { font-size: 11px !important; }
.dashboard-v6 .kpi-change, .dashboard-v6 .kpi-note { font-size: 10px !important; }
.dashboard-v6 .kpi-sparkline { width: 62px !important; height: 24px !important; }

.dashboard-v6 .dashboard-grid,
.dashboard-v6 .dashboard-grid.middle,
.dashboard-v6 .dashboard-grid.bottom { gap: 10px !important; }
.dashboard-v6 .panel { border-radius: 14px !important; }
.dashboard-v6 .panel-inner { padding: 12px !important; }
.dashboard-v6 .panel-header { margin-bottom: 8px !important; gap: 8px !important; }
.dashboard-v6 .panel-title { font-size: 14px !important; }
.dashboard-v6 .panel-subtitle { font-size: 10px !important; margin-top: 1px !important; }
.dashboard-v6 .select-mini, .dashboard-v6 .btn-mini { height: 28px !important; border-radius: 8px !important; font-size: 10px !important; }

.dashboard-v6 .chart-wrap { min-height: 214px !important; }
.dashboard-v6 .chart-svg { height: 214px !important; }
.dashboard-v6 .donut { width: 140px !important; height: 140px !important; }
.dashboard-v6 .donut::after { width: 78px !important; height: 78px !important; }
.dashboard-v6 .gauge-wrap { min-height: 144px !important; }
.dashboard-v6 .gauge { width: 156px !important; height: 86px !important; }
.dashboard-v6 .gauge::after { left: 24px !important; right: 24px !important; bottom: -62px !important; height: 126px !important; }
.dashboard-v6 .gauge-value strong { font-size: 24px !important; }

.dashboard-v6 table th,
.dashboard-v6 table td { padding: 8px 8px !important; font-size: 11px !important; }
.dashboard-v6 .status { height: 21px !important; padding: 0 7px !important; font-size: 10px !important; }

.dashboard-v6 .reservation-list,
.dashboard-v6 .alert-list,
.dashboard-v6 .quality-list,
.dashboard-v6 .channel-list,
.dashboard-v6 .agency-list { gap: 7px !important; }
.dashboard-v6 .reservation-item { grid-template-columns: 36px 1fr auto !important; gap: 10px !important; padding: 6px 0 9px !important; }
.dashboard-v6 .mini-avatar { width: 34px !important; height: 34px !important; flex-basis: 34px !important; font-size: 10px !important; }
.dashboard-v6 .item-title { font-size: 12px !important; }
.dashboard-v6 .item-subtitle { font-size: 10px !important; }
.dashboard-v6 .item-amount { font-size: 12px !important; }
.dashboard-v6 .alert-item,
.dashboard-v6 .quality-item,
.dashboard-v6 .agency-item,
.dashboard-v6 .channel-item { padding: 8px !important; border-radius: 12px !important; }
.dashboard-v6 .soft-icon { width: 32px !important; height: 32px !important; border-radius: 10px !important; font-size: 11px !important; }
.dashboard-v6 .metric-pill { height: 24px !important; min-width: 30px !important; font-size: 11px !important; }

.dashboard-v6 .objective-card { min-height: 120px !important; padding: 10px !important; border-radius: 14px !important; gap: 8px !important; }
.dashboard-v6 .objective-value strong { font-size: 24px !important; }
.dashboard-v6 .objective-note { font-size: 11px !important; }
.dashboard-v6 .status-donut-small { width: 132px !important; height: 132px !important; margin: 10px auto !important; }
.dashboard-v6 .status-donut-small::after { width: 80px !important; height: 80px !important; }

@media (max-width: 1366px) {
  .dashboard-v6 .content { padding: 10px 10px 14px !important; gap: 9px !important; }
  .dashboard-v6 .kpi-grid { gap: 10px !important; }
  .dashboard-v6 .kpi-card { min-height: 78px !important; }
}
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
});
</script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\dashboard\v6\index.blade.php ENDPATH**/ ?>