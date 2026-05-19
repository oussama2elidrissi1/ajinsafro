<?php $__env->startSection('title', 'Dashboard V6'); ?>

<?php
    $dashboardUser = auth()->user();
    $dashboardUserName = $dashboardUser?->name ?? 'Admin';
    $dashboardUserRole = $dashboardUser?->getRoleNames()->first() ?? 'Administrateur';
    $dashboardInitials = strtoupper(collect(preg_split('/\s+/', trim((string) $dashboardUserName)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    if ($dashboardInitials === '') { $dashboardInitials = 'AD'; }
    $brandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $dashboardV5 = $dashboardV5 ?? [];
    $stats = $dashboardV5['stats'] ?? [];
    $recentActivity = $dashboardV5['recentActivity'] ?? [];
    $reservationBreakdown = $dashboardV5['reservationBreakdown'] ?? [];
    $paymentMethods = $dashboardV5['paymentMethods'] ?? [];
    $latestReservations = $dashboardV5['latestReservations'] ?? [];
    $topTours = $dashboardV5['topTours'] ?? [];
    $activeAgencies = $dashboardV5['activeAgencies'] ?? [];
    $monthlyEvolution = $dashboardV5['monthlyEvolution'] ?? [];
?>

<?php $__env->startPush('styles'); ?>
<style>
    .dashboard-v6{height:100vh;display:flex;background:#f8fafc;color:#0f172a}
    .dashboard-v6-sidebar{position:fixed;inset:0 auto 0 0;width:286px;background:linear-gradient(180deg,#05172b,#0c4a6e);color:#fff;padding:16px 12px;overflow:auto;transition:.25s;width:286px}
    .dashboard-v6-main{margin-left:286px;width:calc(100% - 286px);display:flex;flex-direction:column;transition:.25s}
    .dashboard-v6.is-sidebar-collapsed .dashboard-v6-sidebar{width:78px;padding:16px 8px}
    .dashboard-v6.is-sidebar-collapsed .dashboard-v6-main{margin-left:78px;width:calc(100% - 78px)}
    .dashboard-v6.is-sidebar-collapsed .v6-label,.dashboard-v6.is-sidebar-collapsed .v6-user-meta,.dashboard-v6.is-sidebar-collapsed .v6-subnav{display:none}
    .v6-topbar{height:68px;background:#0c4a6e;color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 16px}
    .v6-search{width:min(520px,100%);display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.12);padding:0 12px;border-radius:10px}
    .v6-search input{height:38px;background:transparent;border:0;outline:none;color:#fff;width:100%}
    .v6-content{padding:18px 22px 26px;overflow:auto}
    .v6-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
    .v6-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:16px;display:grid;grid-template-columns:46px 1fr 90px;align-items:center;gap:10px}
    .v6-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:14px}
    .v6-grid-a{display:grid;grid-template-columns:1.55fr .9fr .75fr;gap:14px;margin-top:14px}
    .v6-grid-b{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:14px;margin-top:14px}
    @media (max-width:1200px){.v6-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.v6-grid-a,.v6-grid-b{grid-template-columns:1fr}}
    @media (max-width:700px){.v6-kpi-grid{grid-template-columns:1fr}}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="dashboard-v6" id="dashboardV6Page">
    <aside class="dashboard-v6-sidebar">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
            <img src="<?php echo e($brandLogo); ?>" alt="Ajinsafro" style="height:30px;max-width:170px;filter:brightness(0) invert(1)">
            <button id="dashboardV6SidebarToggle" style="width:34px;height:34px;border-radius:10px;border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.12);color:#fff"><i data-lucide="panel-left-close" style="width:16px"></i></button>
        </div>
        <div style="margin:16px 0;padding:10px;border:1px solid rgba(255,255,255,.2);border-radius:14px;display:flex;gap:10px;align-items:center">
            <div style="width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.18);font-weight:800"><?php echo e($dashboardInitials); ?></div>
            <div class="v6-user-meta"><strong><?php echo e($dashboardUserName); ?></strong><div style="font-size:12px;opacity:.8"><?php echo e($dashboardUserRole); ?></div></div>
        </div>
        <div style="font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#f1cf7a;margin:10px 4px">Tableau de bord</div>
        <nav style="display:grid;gap:6px">
            <a href="<?php echo e(route('admin.dashboard.vue-globale')); ?>" style="padding:9px 10px;border-radius:10px;color:#fff;text-decoration:none"><i data-lucide="home" style="width:15px"></i> <span class="v6-label">Vue d'ensemble</span></a>
            <a href="<?php echo e(route('admin.dashboard.v4')); ?>" style="padding:9px 10px;border-radius:10px;color:#fff;text-decoration:none"><i data-lucide="layout-panel-left" style="width:15px"></i> <span class="v6-label">Dashboard V4</span></a>
            <a href="<?php echo e(route('admin.dashboard.v5')); ?>" style="padding:9px 10px;border-radius:10px;color:#fff;text-decoration:none"><i data-lucide="layout-grid" style="width:15px"></i> <span class="v6-label">Dashboard V5</span></a>
            <a href="<?php echo e(route('admin.dashboard.v6')); ?>" style="padding:9px 10px;border-radius:10px;background:rgba(255,255,255,.15);color:#fff;text-decoration:none"><i data-lucide="sparkles" style="width:15px"></i> <span class="v6-label">Dashboard V6</span></a>
        </nav>
    </aside>
    <main class="dashboard-v6-main">
        <header class="v6-topbar">
            <div style="display:flex;align-items:center;gap:10px">
                <h1 style="font-size:20px;font-weight:800;margin:0">Dashboard V6</h1>
                <div class="v6-search"><i data-lucide="search" style="width:14px"></i><input type="text" placeholder="Rechercher..."></div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
                <button style="width:36px;height:36px;border:0;border-radius:10px;background:rgba(255,255,255,.15);color:#fff"><i data-lucide="bell" style="width:16px"></i></button>
                <a href="<?php echo e(route('admin.reservations.create')); ?>" style="height:36px;padding:0 12px;display:inline-flex;align-items:center;border-radius:10px;background:#f47b20;color:#fff;text-decoration:none;font-size:12px;font-weight:700">Nouvelle réservation</a>
                <div style="font-size:12px"><?php echo e($dashboardInitials); ?></div>
            </div>
        </header>
        <div class="v6-content">
            <section class="v6-kpi-grid">
                <article class="v6-kpi"><div style="width:46px;height:46px;border-radius:14px;background:#e0f2fe;display:grid;place-items:center;color:#0369a1"><i data-lucide="map"></i></div><div><div style="font-size:12px;color:#64748b">Voyages</div><div style="font-size:24px;font-weight:800"><?php echo e((int)($stats['voyages'] ?? 0)); ?></div><div style="font-size:11px;color:#16a34a">Catalogue actif</div></div><svg viewBox="0 0 90 30" width="90" height="30"><polyline fill="none" stroke="#0ea5e9" stroke-width="2" points="2,24 18,20 32,22 46,14 64,11 88,7"/></svg></article>
                <article class="v6-kpi"><div style="width:46px;height:46px;border-radius:14px;background:#ecfdf5;display:grid;place-items:center;color:#15803d"><i data-lucide="building-2"></i></div><div><div style="font-size:12px;color:#64748b">Agences</div><div style="font-size:24px;font-weight:800"><?php echo e((int)($stats['agencies'] ?? 0)); ?></div><div style="font-size:11px;color:#16a34a">Points de vente actifs</div></div><svg viewBox="0 0 90 30" width="90" height="30"><polyline fill="none" stroke="#22c55e" stroke-width="2" points="2,24 18,21 30,18 48,15 66,11 88,8"/></svg></article>
                <article class="v6-kpi"><div style="width:46px;height:46px;border-radius:14px;background:#fff7ed;display:grid;place-items:center;color:#ea580c"><i data-lucide="calendar-check-2"></i></div><div><div style="font-size:12px;color:#64748b">Réservations</div><div style="font-size:24px;font-weight:800"><?php echo e((int)($stats['reservations'] ?? 0)); ?></div><div style="font-size:11px;color:#16a34a">Total enregistré</div></div><svg viewBox="0 0 90 30" width="90" height="30"><polyline fill="none" stroke="#f97316" stroke-width="2" points="2,26 20,21 34,17 54,14 70,12 88,9"/></svg></article>
                <article class="v6-kpi"><div style="width:46px;height:46px;border-radius:14px;background:#eff6ff;display:grid;place-items:center;color:#1d4ed8"><i data-lucide="users"></i></div><div><div style="font-size:12px;color:#64748b">Clients</div><div style="font-size:24px;font-weight:800"><?php echo e((int)($stats['clients'] ?? 0)); ?></div><div style="font-size:11px;color:#16a34a">Clients enregistrés</div></div><svg viewBox="0 0 90 30" width="90" height="30"><polyline fill="none" stroke="#2563eb" stroke-width="2" points="2,25 18,22 30,18 48,16 66,13 88,10"/></svg></article>
            </section>

            <section class="v6-grid-a">
                <article class="v6-card"><h3 style="margin:0 0 10px;font-size:14px">Performance commerciale</h3><div style="height:220px;background:linear-gradient(180deg,#fff,#f8fbff);border:1px dashed #cbd5e1;border-radius:12px;padding:10px;font-size:12px;color:#64748b">Évolution 6 mois: <?php echo e(count($monthlyEvolution) ?: 0); ?> points</div></article>
                <article class="v6-card"><h3 style="margin:0 0 10px;font-size:14px">Réservations par destination</h3><div style="display:grid;gap:8px"><?php $__empty_1 = true; $__currentLoopData = ($dashboardV5['reservationBreakdown']['by_destination'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><div style="display:flex;justify-content:space-between;font-size:12px"><span><?php echo e($item['label'] ?? 'Destination'); ?></span><strong><?php echo e($item['count'] ?? 0); ?></strong></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div style="font-size:12px;color:#64748b">Aucun résultat</div><?php endif; ?></div></article>
                <article class="v6-card"><h3 style="margin:0 0 10px;font-size:14px">Taux de confirmation</h3><div style="font-size:28px;font-weight:800;color:#0f4f8f"><?php echo e((int)($reservationBreakdown['confirmed_percentage'] ?? 0)); ?>%</div></article>
            </section>
            <section class="v6-grid-b">
                <article class="v6-card"><h3 style="margin:0 0 10px;font-size:14px">Réservations récentes</h3><div style="display:grid;gap:9px"><?php $__empty_1 = true; $__currentLoopData = $latestReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><div style="display:flex;justify-content:space-between;font-size:12px"><span><?php echo e($r['client_name'] ?? 'Client'); ?></span><strong><?php echo e($r['amount_label'] ?? '0 DH'); ?></strong></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div style="font-size:12px;color:#64748b">Aucun résultat</div><?php endif; ?></div></article>
                <article class="v6-card"><h3 style="margin:0 0 10px;font-size:14px">Départs à venir</h3><div style="font-size:12px;color:#64748b"><?php echo e((int)($recentActivity['week'] ?? 0)); ?> départs suivis cette semaine</div></article>
                <article class="v6-card"><h3 style="margin:0 0 10px;font-size:14px">Alertes & tâches</h3><div style="font-size:12px;color:#64748b">Paiements validés: <?php echo e(count($paymentMethods)); ?> méthodes</div></article>
            </section>
            <section class="v6-grid-b">
                <article class="v6-card"><h3 style="margin:0 0 10px;font-size:14px">Ventes par canal</h3><div style="display:grid;gap:8px"><?php $__empty_1 = true; $__currentLoopData = $topTours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><div style="display:flex;justify-content:space-between;font-size:12px"><span><?php echo e($tour['label'] ?? 'Canal'); ?></span><strong><?php echo e($tour['count'] ?? 0); ?></strong></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div style="font-size:12px;color:#64748b">Aucun résultat</div><?php endif; ?></div></article>
                <article class="v6-card"><h3 style="margin:0 0 10px;font-size:14px">Qualité opérationnelle</h3><div style="font-size:12px;color:#64748b"><?php echo e(count($activeAgencies)); ?> agences actives</div></article>
                <article class="v6-card"><h3 style="margin:0 0 10px;font-size:14px">Chiffre d’affaires / objectif mensuel</h3><div style="font-size:22px;font-weight:800;color:#16a34a"><?php echo e(number_format((float)($stats['revenue_month'] ?? 0),0,',',' ')); ?> DH</div></article>
            </section>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();
    const page = document.getElementById('dashboardV6Page');
    const btn = document.getElementById('dashboardV6SidebarToggle');
    const key = 'aj-dashboard-v6-sidebar-collapsed';
    if (localStorage.getItem(key) === '1') page.classList.add('is-sidebar-collapsed');
    btn?.addEventListener('click', function () {
        page.classList.toggle('is-sidebar-collapsed');
        localStorage.setItem(key, page.classList.contains('is-sidebar-collapsed') ? '1' : '0');
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.dashboard-v5', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\dashboard\v6\index.blade.php ENDPATH**/ ?>