

<?php $__env->startSection('title', 'Dashboard V3'); ?>

<?php
    $revenueData = [
        ['month' => 'Jan', 'ca' => 180000, 'reservations' => 45],
        ['month' => 'Fev', 'ca' => 220000, 'reservations' => 53],
        ['month' => 'Mar', 'ca' => 310000, 'reservations' => 71],
        ['month' => 'Avr', 'ca' => 285000, 'reservations' => 64],
        ['month' => 'Mai', 'ca' => 390000, 'reservations' => 88],
        ['month' => 'Juin', 'ca' => 470000, 'reservations' => 104],
        ['month' => 'Juil', 'ca' => 620000, 'reservations' => 137],
    ];

    $destinationData = [
        ['name' => 'Dakhla', 'value' => 36, 'color' => '#0f6fb5'],
        ['name' => 'Istanbul', 'value' => 24, 'color' => '#f28c28'],
        ['name' => 'Marrakech', 'value' => 18, 'color' => '#18a66a'],
        ['name' => 'Omra', 'value' => 14, 'color' => '#1f2937'],
        ['name' => 'Autres', 'value' => 8, 'color' => '#94a3b8'],
    ];

    $departures = [
        ['name' => 'Dakhla Premium', 'date' => '23 Mai 2026', 'sold' => 18, 'capacity' => 24, 'status' => 'Ouvert', 'city' => 'Tanger', 'price' => '3 980 DH'],
        ['name' => 'Omra Ramadan', 'date' => '01 Juin 2026', 'sold' => 42, 'capacity' => 50, 'status' => 'Urgent', 'city' => 'Casablanca', 'price' => '16 900 DH'],
        ['name' => 'Istanbul Express', 'date' => '08 Juin 2026', 'sold' => 29, 'capacity' => 32, 'status' => 'Presque complet', 'city' => 'Rabat', 'price' => '7 800 DH'],
        ['name' => 'Marrakech Groupe', 'date' => '15 Juin 2026', 'sold' => 11, 'capacity' => 20, 'status' => 'Ouvert', 'city' => 'Tanger', 'price' => '2 450 DH'],
    ];

    $recentReservations = [
        ['client' => 'Nadia El Amrani', 'trip' => 'Dakhla Premium', 'amount' => '7 960 DH', 'status' => 'Confirmee', 'agent' => 'Oumayma', 'time' => 'Il y a 12 min'],
        ['client' => 'Youssef Berrada', 'trip' => 'Omra Ramadan', 'amount' => '16 900 DH', 'status' => 'En attente', 'agent' => 'Agence Tanger', 'time' => 'Il y a 28 min'],
        ['client' => 'Salma Bennis', 'trip' => 'Istanbul Express', 'amount' => '15 600 DH', 'status' => 'Acompte', 'agent' => 'Karim', 'time' => 'Il y a 41 min'],
        ['client' => 'Mohamed Alaoui', 'trip' => 'Marrakech Groupe', 'amount' => '4 900 DH', 'status' => 'Client web', 'agent' => 'Direct', 'time' => 'Il y a 1 h'],
    ];

    $salesChannels = [
        ['name' => 'Agence', 'value' => 48],
        ['name' => 'Commercial', 'value' => 63],
        ['name' => 'Client web', 'value' => 31],
        ['name' => 'Group deals', 'value' => 19],
    ];

    $maxRevenue = max(array_column($revenueData, 'ca'));
    $totalRevenue = array_sum(array_column($revenueData, 'ca'));
    $totalReservations = array_sum(array_column($revenueData, 'reservations'));
    $maxSalesChannel = max(array_column($salesChannels, 'value'));

    $statusClasses = [
        'Confirmee' => 'is-ok',
        'En attente' => 'is-warn',
        'Acompte' => 'is-info',
        'Client web' => 'is-web',
        'Ouvert' => 'is-ok',
        'Urgent' => 'is-danger',
        'Presque complet' => 'is-warn',
    ];
?>

<?php $__env->startPush('styles'); ?>
<style>
    body.aj-admin-compact .dashboard-v3-page {
        max-width: 1460px;
        margin: 0 auto;
        display: grid;
        gap: 18px;
    }

    body.aj-admin-compact .dashboard-v3-header,
    body.aj-admin-compact .dashboard-v3-card {
        background: #fff;
        border: 1px solid #e5edf6;
        border-radius: 18px;
        box-shadow: 0 12px 24px rgba(15, 45, 75, 0.06);
    }

    body.aj-admin-compact .dashboard-v3-header {
        padding: 18px 20px;
    }

    body.aj-admin-compact .dashboard-v3-header-top {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        align-items: center;
    }

    body.aj-admin-compact .dashboard-v3-breadcrumb {
        color: #6b7a90;
        font-size: 12px;
        font-weight: 700;
    }

    body.aj-admin-compact .dashboard-v3-title {
        margin: 4px 0 0;
        color: #0f243d;
        font-size: clamp(22px, 2.5vw, 32px);
        line-height: 1.1;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    body.aj-admin-compact .dashboard-v3-toolbar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    body.aj-admin-compact .dashboard-v3-btn {
        border-radius: 12px;
        border: 1px solid #dbe6f2;
        background: #fff;
        color: #27445f;
        font-size: 13px;
        font-weight: 700;
        padding: 8px 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    body.aj-admin-compact .dashboard-v3-btn-primary {
        border-color: #0f6fb5;
        background: #0f6fb5;
        color: #fff;
    }

    body.aj-admin-compact .dashboard-v3-grid-kpi {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    body.aj-admin-compact .dashboard-v3-kpi {
        padding: 16px;
        display: grid;
        gap: 8px;
    }

    body.aj-admin-compact .dashboard-v3-kpi-label {
        color: #6b7a90;
        font-size: 12px;
        font-weight: 700;
    }

    body.aj-admin-compact .dashboard-v3-kpi-value {
        color: #0f243d;
        font-size: 26px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    body.aj-admin-compact .dashboard-v3-kpi-sub {
        color: #6b7a90;
        font-size: 12px;
    }

    body.aj-admin-compact .dashboard-v3-layout-3 {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 12px;
    }

    body.aj-admin-compact .dashboard-v3-layout-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    body.aj-admin-compact .dashboard-v3-card-head {
        padding: 16px 16px 0;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
    }

    body.aj-admin-compact .dashboard-v3-card-title {
        margin: 0;
        color: #0f243d;
        font-size: 17px;
        font-weight: 800;
    }

    body.aj-admin-compact .dashboard-v3-card-subtitle {
        margin: 4px 0 0;
        color: #6b7a90;
        font-size: 12px;
    }

    body.aj-admin-compact .dashboard-v3-card-body {
        padding: 16px;
    }

    body.aj-admin-compact .dashboard-v3-revenue-bars {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
        align-items: end;
        min-height: 240px;
    }

    body.aj-admin-compact .dashboard-v3-revenue-col {
        display: grid;
        gap: 6px;
        justify-items: center;
    }

    body.aj-admin-compact .dashboard-v3-bar {
        width: 100%;
        border-radius: 10px 10px 4px 4px;
        background: linear-gradient(180deg, #0f6fb5, #0b5d99);
        min-height: 20px;
    }

    body.aj-admin-compact .dashboard-v3-revenue-label {
        font-size: 11px;
        color: #6b7a90;
        font-weight: 700;
    }

    body.aj-admin-compact .dashboard-v3-revenue-val {
        font-size: 11px;
        color: #0f243d;
        font-weight: 700;
    }

    body.aj-admin-compact .dashboard-v3-donut {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        margin: 0 auto 14px;
        background: conic-gradient(#0f6fb5 0% 36%, #f28c28 36% 60%, #18a66a 60% 78%, #1f2937 78% 92%, #94a3b8 92% 100%);
        position: relative;
    }

    body.aj-admin-compact .dashboard-v3-donut::after {
        content: '';
        position: absolute;
        inset: 38px;
        border-radius: 50%;
        background: #fff;
        border: 1px solid #e5edf6;
    }

    body.aj-admin-compact .dashboard-v3-legend {
        display: grid;
        gap: 8px;
    }

    body.aj-admin-compact .dashboard-v3-legend-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        color: #425a74;
    }

    body.aj-admin-compact .dashboard-v3-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        display: inline-block;
        margin-right: 6px;
    }

    body.aj-admin-compact .dashboard-v3-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    body.aj-admin-compact .dashboard-v3-table th,
    body.aj-admin-compact .dashboard-v3-table td {
        border-bottom: 1px solid #e8eef6;
        padding: 10px;
        text-align: left;
        vertical-align: middle;
    }

    body.aj-admin-compact .dashboard-v3-table th {
        color: #6b7a90;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    body.aj-admin-compact .dashboard-v3-status {
        border-radius: 999px;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
    }

    body.aj-admin-compact .dashboard-v3-status.is-ok { background: #e8fff4; color: #138052; }
    body.aj-admin-compact .dashboard-v3-status.is-warn { background: #fff4de; color: #a15d00; }
    body.aj-admin-compact .dashboard-v3-status.is-danger { background: #fff0ef; color: #c73b33; }
    body.aj-admin-compact .dashboard-v3-status.is-info { background: #e9f4ff; color: #0f6fb5; }
    body.aj-admin-compact .dashboard-v3-status.is-web { background: #f4ecff; color: #7a4bb3; }

    body.aj-admin-compact .dashboard-v3-progress {
        height: 8px;
        border-radius: 999px;
        overflow: hidden;
        background: #edf2f7;
    }

    body.aj-admin-compact .dashboard-v3-progress > span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #0f6fb5, #18a66a, #f28c28);
    }

    body.aj-admin-compact .dashboard-v3-channel-row {
        display: grid;
        grid-template-columns: 120px 1fr 40px;
        align-items: center;
        gap: 10px;
        margin-bottom: 9px;
        font-size: 13px;
    }

    body.aj-admin-compact .dashboard-v3-channel-row:last-child {
        margin-bottom: 0;
    }

    body.aj-admin-compact .dashboard-v3-channel-track {
        height: 12px;
        border-radius: 999px;
        overflow: hidden;
        background: #ecf2f8;
    }

    body.aj-admin-compact .dashboard-v3-channel-track > span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: #0f6fb5;
    }

    @media (max-width: 1199.98px) {
        body.aj-admin-compact .dashboard-v3-grid-kpi {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        body.aj-admin-compact .dashboard-v3-layout-3,
        body.aj-admin-compact .dashboard-v3-layout-2 {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        body.aj-admin-compact .dashboard-v3-grid-kpi {
            grid-template-columns: 1fr;
        }

        body.aj-admin-compact .dashboard-v3-revenue-bars {
            min-height: 180px;
        }

        body.aj-admin-compact .dashboard-v3-channel-row {
            grid-template-columns: 95px 1fr 36px;
            font-size: 12px;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="dashboard-v3-page">
        <section class="dashboard-v3-header">
            <div class="dashboard-v3-header-top">
                <div>
                    <div class="dashboard-v3-breadcrumb">Admin / Vue globale / Live</div>
                    <h1 class="dashboard-v3-title">Dashboard commercial Ajinsafro V3</h1>
                </div>
                <div class="dashboard-v3-toolbar">
                    <button type="button" class="dashboard-v3-btn"><i class="bx bx-search"></i> Rechercher</button>
                    <button type="button" class="dashboard-v3-btn"><i class="bx bx-filter-alt"></i> Ce mois</button>
                    <button type="button" class="dashboard-v3-btn dashboard-v3-btn-primary"><i class="bx bx-plus"></i> Nouvelle reservation</button>
                </div>
            </div>
        </section>

        <section class="dashboard-v3-grid-kpi">
            <article class="dashboard-v3-card dashboard-v3-kpi">
                <span class="dashboard-v3-kpi-label">Chiffre d'affaires</span>
                <strong class="dashboard-v3-kpi-value"><?php echo e(number_format($totalRevenue, 0, ',', ' ')); ?> DH</strong>
                <span class="dashboard-v3-kpi-sub">CA consolide ventes + web</span>
            </article>
            <article class="dashboard-v3-card dashboard-v3-kpi">
                <span class="dashboard-v3-kpi-label">Reservations</span>
                <strong class="dashboard-v3-kpi-value"><?php echo e(number_format($totalReservations, 0, ',', ' ')); ?></strong>
                <span class="dashboard-v3-kpi-sub">Confirmees, acomptes et web</span>
            </article>
            <article class="dashboard-v3-card dashboard-v3-kpi">
                <span class="dashboard-v3-kpi-label">Departs actifs</span>
                <strong class="dashboard-v3-kpi-value">38</strong>
                <span class="dashboard-v3-kpi-sub">Voyages avec disponibilite</span>
            </article>
            <article class="dashboard-v3-card dashboard-v3-kpi">
                <span class="dashboard-v3-kpi-label">Agents actifs</span>
                <strong class="dashboard-v3-kpi-value">14</strong>
                <span class="dashboard-v3-kpi-sub">Commerciaux + agences</span>
            </article>
        </section>

        <section class="dashboard-v3-layout-3">
            <article class="dashboard-v3-card">
                <div class="dashboard-v3-card-head">
                    <div>
                        <h2 class="dashboard-v3-card-title">Performance commerciale</h2>
                        <p class="dashboard-v3-card-subtitle">CA et volume de reservations par mois</p>
                    </div>
                </div>
                <div class="dashboard-v3-card-body">
                    <div class="dashboard-v3-revenue-bars">
                        <?php $__currentLoopData = $revenueData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $height = (int) round(($row['ca'] / max(1, $maxRevenue)) * 190);
                            ?>
                            <div class="dashboard-v3-revenue-col">
                                <span class="dashboard-v3-revenue-val"><?php echo e(number_format($row['ca'] / 1000, 0, ',', ' ')); ?>k</span>
                                <div class="dashboard-v3-bar" style="height: <?php echo e(max(24, $height)); ?>px;"></div>
                                <span class="dashboard-v3-revenue-label"><?php echo e($row['month']); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </article>

            <article class="dashboard-v3-card">
                <div class="dashboard-v3-card-head">
                    <div>
                        <h2 class="dashboard-v3-card-title">Destinations vendues</h2>
                        <p class="dashboard-v3-card-subtitle">Repartition du mois</p>
                    </div>
                </div>
                <div class="dashboard-v3-card-body">
                    <div class="dashboard-v3-donut"></div>
                    <div class="dashboard-v3-legend">
                        <?php $__currentLoopData = $destinationData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="dashboard-v3-legend-row">
                                <span><i class="dashboard-v3-dot" style="background: <?php echo e($row['color']); ?>;"></i><?php echo e($row['name']); ?></span>
                                <strong><?php echo e($row['value']); ?>%</strong>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </article>
        </section>

        <section class="dashboard-v3-layout-3">
            <article class="dashboard-v3-card">
                <div class="dashboard-v3-card-head">
                    <div>
                        <h2 class="dashboard-v3-card-title">Departs a piloter</h2>
                        <p class="dashboard-v3-card-subtitle">Suivi capacite, ventes, urgence et disponibilite</p>
                    </div>
                </div>
                <div class="dashboard-v3-card-body">
                    <div class="table-responsive">
                        <table class="dashboard-v3-table">
                            <thead>
                                <tr>
                                    <th>Voyage</th>
                                    <th>Depart</th>
                                    <th>Statut</th>
                                    <th>Capacite</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $departures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $percentage = (int) round(($row['sold'] / max(1, $row['capacity'])) * 100);
                                        $statusClass = $statusClasses[$row['status']] ?? 'is-info';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($row['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo e($row['city']); ?> �?� <?php echo e($row['price']); ?></small>
                                        </td>
                                        <td><?php echo e($row['date']); ?></td>
                                        <td><span class="dashboard-v3-status <?php echo e($statusClass); ?>"><?php echo e($row['status']); ?></span></td>
                                        <td>
                                            <div class="small text-muted mb-1"><?php echo e($row['sold']); ?>/<?php echo e($row['capacity']); ?> vendus</div>
                                            <div class="dashboard-v3-progress"><span style="width: <?php echo e(min(100, $percentage)); ?>%;"></span></div>
                                        </td>
                                        <td><a href="#" class="dashboard-v3-btn">Details</a></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>

            <article class="dashboard-v3-card">
                <div class="dashboard-v3-card-head">
                    <div>
                        <h2 class="dashboard-v3-card-title">Qualite operationnelle</h2>
                        <p class="dashboard-v3-card-subtitle">Indicateurs de controle</p>
                    </div>
                </div>
                <div class="dashboard-v3-card-body">
                    <div class="d-grid gap-2">
                        <div class="d-flex justify-content-between align-items-center border rounded-3 p-3">
                            <span>Dossiers valides</span>
                            <strong>82%</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border rounded-3 p-3">
                            <span>Acomptes a suivre</span>
                            <strong>17</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border rounded-3 p-3">
                            <span>Rooming incomplet</span>
                            <strong>6</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border rounded-3 p-3">
                            <span>Commissions a approuver</span>
                            <strong>12</strong>
                        </div>
                    </div>
                    <div class="mt-3 p-3 rounded-4" style="background: linear-gradient(135deg, #0f6fb5, #084c87); color: #fff;">
                        <div class="small">Objectif mensuel</div>
                        <div style="font-size: 30px; line-height: 1; font-weight: 800;">74%</div>
                        <div class="dashboard-v3-progress mt-2" style="background: rgba(255,255,255,0.25);"><span style="width:74%; background:#f28c28;"></span></div>
                        <div class="small mt-2">Encore 186 000 DH pour atteindre l'objectif.</div>
                    </div>
                </div>
            </article>
        </section>

        <section class="dashboard-v3-layout-2">
            <article class="dashboard-v3-card">
                <div class="dashboard-v3-card-head">
                    <div>
                        <h2 class="dashboard-v3-card-title">Reservations recentes</h2>
                        <p class="dashboard-v3-card-subtitle">Flux live commercial et client web</p>
                    </div>
                </div>
                <div class="dashboard-v3-card-body">
                    <div class="d-grid gap-2">
                        <?php $__currentLoopData = $recentReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $statusClass = $statusClasses[$row['status']] ?? 'is-info';
                            ?>
                            <div class="border rounded-3 p-3">
                                <div class="d-flex justify-content-between gap-2 align-items-center flex-wrap">
                                    <div>
                                        <strong><?php echo e($row['client']); ?></strong>
                                        <div class="small text-muted"><?php echo e($row['trip']); ?> �?� <?php echo e($row['agent']); ?></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="dashboard-v3-status <?php echo e($statusClass); ?>"><?php echo e($row['status']); ?></span>
                                        <strong><?php echo e($row['amount']); ?></strong>
                                    </div>
                                </div>
                                <div class="small text-muted mt-1"><?php echo e($row['time']); ?></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </article>

            <article class="dashboard-v3-card">
                <div class="dashboard-v3-card-head">
                    <div>
                        <h2 class="dashboard-v3-card-title">Ventes par canal</h2>
                        <p class="dashboard-v3-card-subtitle">Agence, commercial, client web</p>
                    </div>
                </div>
                <div class="dashboard-v3-card-body">
                    <?php $__currentLoopData = $salesChannels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $channelPercentage = (int) round(($row['value'] / max(1, $maxSalesChannel)) * 100);
                        ?>
                        <div class="dashboard-v3-channel-row">
                            <span><?php echo e($row['name']); ?></span>
                            <div class="dashboard-v3-channel-track"><span style="width: <?php echo e(min(100, $channelPercentage)); ?>%;"></span></div>
                            <strong><?php echo e($row['value']); ?></strong>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </article>
        </section>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\dashboard\v3\index.blade.php ENDPATH**/ ?>