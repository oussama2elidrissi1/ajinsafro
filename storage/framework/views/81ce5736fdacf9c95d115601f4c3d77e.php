<?php
    $now      = \Carbon\Carbon::now('Africa/Casablanca')->locale('fr');
    $dateLong = ucfirst($now->translatedFormat('l d F Y'));
    $dateTime = ucfirst($now->translatedFormat('l d F Y · H:i'));
    $rsTotal  = max(1, (int)($stats['reservations_total'] ?? 0));
    $payments = $stats['payment_labels'] ?? [];
    $paySer   = $stats['payment_series'] ?? [];
    $payMax   = (count($paySer) > 0) ? max(max($paySer), 1) : 1;
?>



<?php $__env->startSection('title', 'Dashboard V2'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    /* �"?�"?�"? DASHBOARD-SPECIFIC CSS VARIABLES (back-compat) �"?�"?�"? */
    :root {
        --blue:      #005792;
        --blue-dark: #06345c;
        --blue-soft: #eaf4fb;
        --orange:    #ff6b00;
        --green:     #19b982;
        --red:       #ef4d45;
        --yellow:    #f7b500;
        --purple:    #8b5cf6;
        --text:      #172b4d;
        --muted:     #71829a;
        --border:    #e6edf5;
        --bg:        #f6f9fc;
        --shadow:    0 12px 35px rgba(15, 45, 75, 0.08);
        --radius:    18px;
    }

    /* Reset scoped au dashboard pour préserver l'apparence d'origine (Bootstrap Reboot ajoute des marges par défaut) */
    .dv2-card, .dv2-card *, .kpi-grid, .kpi-grid *,
    .top-widgets, .top-widgets *, .status-card, .status-card *,
    .charts-grid, .charts-grid *, .lower-grid, .lower-grid *,
    .bottom-grid, .bottom-grid *, .page-head, .page-head * {
        box-sizing: border-box;
    }
    .widget-head h3, .card-head h3, .status-card h3 { margin: 0; }
    .metric-row, .pay-row { margin: 0; }
    .kpi-label, .kpi-value, .kpi-footer { margin-top: revert; }
    a { text-decoration: none; color: inherit; }

    /* �"?�"?�"? PAGE HEAD �"?�"?�"? */
    .page-head {
        display: flex; justify-content: space-between; align-items: flex-start;
        gap: 20px; margin-bottom: 26px; flex-wrap: wrap;
    }
    .page-title { display: flex; align-items: flex-start; gap: 14px; }
    .page-title-icon {
        width: 48px; height: 48px; border-radius: 14px;
        background: var(--blue-soft); color: var(--blue);
        display: grid; place-items: center; font-size: 22px;
    }
    .page-title h1 { font-size: 30px; font-weight: 900; color: var(--text); margin-bottom: 4px; line-height: 1.1; }
    .page-title p { color: var(--muted); font-weight: 600; font-size: 13px; }
    .page-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .control-btn {
        height: 42px; padding: 0 16px; border-radius: 13px;
        border: 1px solid var(--border); background: #fff;
        color: var(--blue-dark); font-weight: 800; cursor: pointer;
        box-shadow: 0 6px 16px rgba(15, 45, 75, 0.04);
        font-size: 13px; display: inline-flex; align-items: center; gap: 8px;
        text-decoration: none; transition: 0.2s;
    }
    .control-btn:hover { background: #f7fbff; color: var(--blue); }
    .control-btn.primary { background: var(--blue); color: #fff; border-color: var(--blue); }
    .control-btn.primary:hover { background: var(--blue-dark); color: #fff; }

    /* �"?�"?�"? CARD BASE �"?�"?�"? */
    .dv2-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }

    /* �"?�"?�"? KPI GRID �"?�"?�"? */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 22px;
    }
    .kpi-card {
        padding: 24px; min-height: 166px;
        display: grid; align-content: space-between;
        position: relative; transition: transform 0.22s, box-shadow 0.22s;
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 18px 45px rgba(15, 45, 75, 0.12); }
    .kpi-card a.kpi-stretched { position: absolute; inset: 0; z-index: 1; border-radius: inherit; }
    .kpi-icon { width: 58px; height: 58px; border-radius: 17px; display: grid; place-items: center; font-size: 24px; }
    .kpi-icon.blue   { background: #e8f1ff; color: var(--blue); }
    .kpi-icon.green  { background: #e7fff4; color: var(--green); }
    .kpi-icon.orange { background: #fff0df; color: var(--orange); }
    .kpi-icon.purple { background: #f1eaff; color: var(--purple); }
    .kpi-label {
        color: #61728a; font-size: 12px; font-weight: 900;
        text-transform: uppercase; letter-spacing: .04em; margin-top: 18px;
    }
    .kpi-value { font-size: 34px; font-weight: 900; color: var(--text); margin: 8px 0; line-height: 1.05; }
    .kpi-footer {
        display: flex; justify-content: space-between; align-items: center;
        color: var(--muted); font-weight: 700; padding-top: 14px;
        border-top: 1px solid #edf2f7; font-size: 13px;
    }
    .arrow { color: var(--blue); font-weight: 900; }
    .badge-red, .badge-green {
        padding: 5px 10px; border-radius: 999px;
        font-size: 11px; font-weight: 900;
        display: inline-flex; align-items: center; gap: 4px; margin-top: 6px;
    }
    .badge-red   { background: #fff0ef; color: var(--red); }
    .badge-green { background: #e8fff4; color: var(--green); }

    /* �"?�"?�"? TOP WIDGETS �"?�"?�"? */
    .top-widgets {
        display: grid;
        grid-template-columns: 1fr 1fr 1.3fr;
        gap: 20px; margin-bottom: 22px;
    }
    .widget { padding: 24px; }
    .widget-head { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; }
    .widget-head h3 { font-size: 17px; font-weight: 900; color: var(--text); }
    .widget-icon {
        width: 34px; height: 34px; border-radius: 11px;
        display: grid; place-items: center;
        background: var(--blue-soft); color: var(--blue);
    }
    .metric-list { display: grid; gap: 12px; margin-bottom: 18px; }
    .metric-row {
        display: flex; justify-content: space-between;
        color: var(--muted); font-weight: 700;
        padding-bottom: 12px; border-bottom: 1px solid #f0f3f7;
    }
    .metric-row:last-child { border-bottom: none; padding-bottom: 0; }
    .metric-row strong { color: var(--text); font-weight: 900; }
    .detail-link {
        display: flex; justify-content: space-between; align-items: center;
        border: 1px solid var(--border); border-radius: 13px;
        padding: 13px 15px; color: var(--blue-dark);
        font-weight: 800; background: #fff; font-size: 13px;
        transition: 0.2s;
    }
    .detail-link:hover { background: var(--blue-soft); border-color: var(--blue); color: var(--blue); }
    .revenue-value { font-size: 30px; color: var(--blue); font-weight: 900; margin-bottom: 8px; line-height: 1.1; }
    .small-green, .small-red {
        display: inline-flex; padding: 6px 10px; border-radius: 999px;
        font-size: 12px; font-weight: 900; margin-bottom: 18px;
        align-items: center; gap: 4px;
    }
    .small-green { background: #e9fff4; color: var(--green); }
    .small-red   { background: #fff0ef; color: var(--red); }
    .messages-box { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .message-number { font-size: 32px; font-weight: 900; margin: 6px 0 18px; color: var(--text); }
    .blue-btn {
        border: none; background: var(--blue); color: #fff;
        padding: 12px 16px; border-radius: 13px; font-weight: 900; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px; font-size: 13px;
        text-decoration: none; transition: 0.2s;
    }
    .blue-btn:hover { background: var(--blue-dark); color: #fff; }
    .message-illu { font-size: 80px; opacity: .09; line-height: 1; color: var(--blue); }

    /* �"?�"?�"? STATUS CARD �"?�"?�"? */
    .status-card { padding: 24px; margin-bottom: 22px; }
    .status-card h3 { font-size: 17px; font-weight: 900; margin-bottom: 18px; color: var(--text); }
    .status-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
    .status-item { display: grid; gap: 9px; }
    .status-line { display: flex; justify-content: space-between; font-weight: 800; color: var(--muted); font-size: 13px; }
    .dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 8px; }
    .dot.yellow { background: var(--yellow); }
    .dot.green  { background: var(--green); }
    .dot.red    { background: var(--red); }
    .dot.gray   { background: #aab6c5; }
    .progress { height: 8px; border-radius: 999px; background: #edf2f7; overflow: hidden; }
    .progress > span { display: block; height: 100%; border-radius: inherit; transition: width 0.5s; }

    /* �"?�"?�"? CHARTS �"?�"?�"? */
    .charts-grid {
        display: grid; grid-template-columns: 1.55fr .9fr;
        gap: 20px; margin-bottom: 22px;
    }
    .chart-card { padding: 24px; min-height: 395px; }
    .card-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
    .card-head h3 { font-size: 17px; font-weight: 900; color: var(--text); }
    .pill-select {
        border: none; background: var(--blue-soft); color: var(--blue);
        font-weight: 900; padding: 8px 14px; border-radius: 12px;
        font-size: 12px; cursor: pointer; text-decoration: none;
        display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;
    }
    .pill-select:hover { background: #d8e9ff; color: var(--blue-dark); }
    .chart-area { width: 100%; min-height: 290px; }

    /* �"?�"?�"? LOWER GRID �"?�"?�"? */
    .lower-grid {
        display: grid; grid-template-columns: .75fr 1.55fr;
        gap: 20px; margin-bottom: 22px;
    }
    .payments-chart { display: grid; gap: 18px; margin-top: 12px; }
    .pay-row {
        display: grid; grid-template-columns: 110px 1fr 30px;
        align-items: center; gap: 12px;
        color: var(--muted); font-weight: 800; font-size: 13px;
    }
    .pay-row strong { color: var(--text); text-align: right; font-weight: 900; }
    .pay-bar { height: 14px; background: #edf2f7; border-radius: 999px; overflow: hidden; }
    .pay-bar > span { display: block; height: 100%; background: linear-gradient(90deg, #294c99, #2f7df4); border-radius: inherit; }

    /* �"?�"?�"? TABLE CARD �"?�"?�"? */
    .table-card { padding: 0; overflow: hidden; }
    .table-card .card-head { padding: 22px 22px 16px; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th {
        text-align: left; padding: 12px 16px;
        background: #f7fbff; color: #66758a;
        font-size: 11px; text-transform: uppercase; letter-spacing: .05em;
        font-weight: 900; border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }
    td { padding: 13px 16px; border-bottom: 1px solid #edf2f7; color: #42556d; font-weight: 700; vertical-align: middle; }
    tbody tr:hover { background: #fafdff; }
    tbody tr:last-child td { border-bottom: none; }
    .client-name { font-weight: 900; color: var(--text); display: block; }
    .client-email {
        color: var(--muted); font-size: 11px; display: block;
        max-width: 180px; white-space: nowrap; overflow: hidden;
        text-overflow: ellipsis; font-weight: 600;
    }
    .tag { display: inline-flex; padding: 5px 10px; border-radius: 999px; font-size: 11px; font-weight: 900; white-space: nowrap; }
    .tag.pending { background: #fff4d8; color: #9a6b00; }
    .tag.valid   { background: #e8fff4; color: var(--green); }
    .tag.cancel  { background: #fff0ef; color: var(--red); }
    .view-btn {
        width: 32px; height: 32px; border-radius: 10px;
        border: 1px solid #dce8f3; background: #fff;
        color: var(--blue); cursor: pointer; font-weight: 900;
        display: inline-grid; place-items: center; text-decoration: none;
        transition: 0.2s;
    }
    .view-btn:hover { background: var(--blue-soft); border-color: var(--blue); }

    /* �"?�"?�"? BOTTOM GRID �"?�"?�"? */
    .bottom-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .trip-list, .agency-list { padding: 0 22px 22px; display: grid; gap: 12px; }
    .trip-item, .agency-item {
        display: grid; grid-template-columns: 56px 1fr auto;
        gap: 14px; align-items: center;
        padding: 11px 0; border-bottom: 1px solid #edf2f7;
    }
    .trip-item:last-child, .agency-item:last-child { border-bottom: 0; }
    .trip-img {
        width: 56px; height: 44px; border-radius: 12px; object-fit: cover;
        background: var(--blue-soft); display: grid; place-items: center;
        color: var(--blue); font-size: 18px;
    }
    .trip-title, .agency-title { color: var(--text); font-weight: 800; line-height: 1.35; font-size: 13px; }
    .trip-count { color: var(--blue); font-weight: 900; font-size: 16px; }
    .agency-action {
        color: var(--blue); font-weight: 900; font-size: 16px;
        width: 30px; height: 30px; border-radius: 8px;
        display: grid; place-items: center; transition: 0.2s;
    }
    .agency-action:hover { background: var(--blue-soft); }
    .agency-icon {
        width: 46px; height: 46px; border-radius: 14px;
        background: #e8fff4; color: var(--green);
        display: grid; place-items: center; font-size: 20px;
    }
    .agency-city { color: var(--muted); font-size: 11px; font-weight: 700; margin-top: 3px; }
    .empty-row { padding: 30px; color: var(--muted); text-align: center; font-weight: 700; }

    /* �"?�"?�"? ANIMATIONS �"?�"?�"? */
    .fade-in { animation: fadeIn 0.45s ease both; }
    .fade-in.d1 { animation-delay: 0.05s; }
    .fade-in.d2 { animation-delay: 0.10s; }
    .fade-in.d3 { animation-delay: 0.15s; }
    .fade-in.d4 { animation-delay: 0.20s; }
    .fade-in.d5 { animation-delay: 0.25s; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* �"?�"?�"? DASHBOARD RESPONSIVE �"?�"?�"? */
    @media (max-width: 1400px) {
        .top-widgets { grid-template-columns: 1fr 1fr; }
        .top-widgets > :nth-child(3) { grid-column: 1 / -1; }
        .lower-grid  { grid-template-columns: 1fr; }
    }
    @media (max-width: 1200px) {
        .kpi-grid    { grid-template-columns: repeat(2, 1fr); }
        .top-widgets { grid-template-columns: 1fr; }
        .top-widgets > :nth-child(3) { grid-column: auto; }
        .charts-grid { grid-template-columns: 1fr; }
        .bottom-grid { grid-template-columns: 1fr; }
        .status-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 900px) {
        .page-head { flex-direction: column; align-items: stretch; }
        .kpi-grid { grid-template-columns: 1fr; }
        .status-grid { grid-template-columns: 1fr; }
        table { min-width: 750px; }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>


<div class="page-head fade-in d1">
    <div class="page-title">
        <div class="page-title-icon"><i class="bx bx-grid-alt"></i></div>
        <div>
            <h1>Tableau de bord</h1>
            <p>Vue d'ensemble de votre activité �?" <?php echo e($dateTime); ?></p>
        </div>
    </div>

    <div class="page-actions">
        <span class="control-btn">
            <i class="bx bx-calendar"></i> <?php echo e($dateLong); ?>

        </span>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.reservations.index')): ?>
            <a href="<?php echo e(route('admin.reservations.index')); ?>" class="control-btn primary">
                <i class="bx bx-list-ul"></i> Réservations
            </a>
        <?php endif; ?>
    </div>
</div>


<div class="kpi-grid">

    <div class="dv2-card kpi-card fade-in d1">
        <div>
            <div class="kpi-icon blue"><i class="bx bx-map-alt"></i></div>
            <div class="kpi-label">Voyages</div>
            <div class="kpi-value"><?php echo e($stats['voyages_count'] ?? 0); ?></div>
        </div>
        <div class="kpi-footer">
            <span>Tous les voyages</span>
            <span class="arrow">�?'</span>
        </div>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.circuits.voyages.index')): ?>
            <a href="<?php echo e(route('admin.circuits.voyages.index')); ?>" class="kpi-stretched"></a>
        <?php endif; ?>
    </div>

    <div class="dv2-card kpi-card fade-in d2">
        <div>
            <div class="kpi-icon green"><i class="bx bx-buildings"></i></div>
            <div class="kpi-label">Points de vente</div>
            <div class="kpi-value"><?php echo e($stats['branches_count'] ?? 0); ?></div>
        </div>
        <div class="kpi-footer">
            <span><?php echo e($stats['branches_active'] ?? 0); ?> actives</span>
            <span class="arrow">�?'</span>
        </div>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.agencies.index')): ?>
            <a href="<?php echo e(route('admin.agencies.index')); ?>" class="kpi-stretched"></a>
        <?php endif; ?>
    </div>

    <div class="dv2-card kpi-card fade-in d3">
        <div>
            <div class="kpi-icon orange"><i class="bx bx-calendar-event"></i></div>
            <div class="kpi-label">Réservations</div>
            <div class="kpi-value"><?php echo e($stats['reservations_total'] ?? 0); ?></div>
            <?php $evo = $stats['reservations_month_evolution'] ?? 0; ?>
            <?php if($evo < 0): ?>
                <span class="badge-red">�?" <?php echo e($evo); ?>% ce mois</span>
            <?php elseif($evo > 0): ?>
                <span class="badge-green">�?' +<?php echo e($evo); ?>% ce mois</span>
            <?php endif; ?>
        </div>
        <div class="kpi-footer">
            <span>Total enregistré</span>
            <span class="arrow">�?'</span>
        </div>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.reservations.index')): ?>
            <a href="<?php echo e(route('admin.reservations.index')); ?>" class="kpi-stretched"></a>
        <?php endif; ?>
    </div>

    <div class="dv2-card kpi-card fade-in d4">
        <div>
            <div class="kpi-icon purple"><i class="bx bx-group"></i></div>
            <div class="kpi-label">Clients</div>
            <div class="kpi-value"><?php echo e($stats['clients_count'] ?? 0); ?></div>
        </div>
        <div class="kpi-footer">
            <span>Clients enregistrés</span>
            <span class="arrow">�?'</span>
        </div>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.customers.clients.index')): ?>
            <a href="<?php echo e(route('admin.customers.clients.index')); ?>" class="kpi-stretched"></a>
        <?php endif; ?>
    </div>

</div>


<div class="top-widgets">

    <div class="dv2-card widget fade-in d2">
        <div class="widget-head">
            <div class="widget-icon"><i class="bx bx-time-five"></i></div>
            <h3>Activité récente</h3>
        </div>
        <div class="metric-list">
            <div class="metric-row"><span>Aujourd'hui</span><strong><?php echo e($stats['reservations_today'] ?? 0); ?> résa</strong></div>
            <div class="metric-row"><span>Cette semaine</span><strong><?php echo e($stats['reservations_this_week'] ?? 0); ?> résa</strong></div>
            <div class="metric-row"><span>Ce mois</span><strong><?php echo e($stats['reservations_this_month'] ?? 0); ?> réservations</strong></div>
        </div>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.reservations.index')): ?>
            <a href="<?php echo e(route('admin.reservations.index')); ?>" class="detail-link">Voir le détail <span>�?'</span></a>
        <?php endif; ?>
    </div>

    <div class="dv2-card widget fade-in d3">
        <div class="widget-head">
            <div class="widget-icon"><i class="bx bx-euro"></i></div>
            <h3>Chiffre d'affaires</h3>
        </div>
        <p style="color:var(--muted);font-weight:700;margin:0 0 4px;font-size:12px;">Total validé</p>
        <div class="revenue-value"><?php echo e(number_format($stats['revenue_total'] ?? 0, 0, ',', ' ')); ?> �,�</div>
        <p style="color:var(--muted);font-weight:700;margin:0 0 8px;font-size:12px;">
            Ce mois : <strong style="color:var(--text);"><?php echo e(number_format($stats['revenue_this_month'] ?? 0, 0, ',', ' ')); ?> �,�</strong>
        </p>
        <?php $revEvo = $stats['revenue_month_evolution'] ?? 0; ?>
        <?php if($revEvo >= 0): ?>
            <span class="small-green">�?' +<?php echo e($revEvo); ?>% vs mois dernier</span>
        <?php else: ?>
            <span class="small-red">�?" <?php echo e($revEvo); ?>% vs mois dernier</span>
        <?php endif; ?>
        <?php if(\Illuminate\Support\Facades\Route::has('admin.reservations.index')): ?>
            <a href="<?php echo e(route('admin.reservations.index')); ?>" class="detail-link">Voir le détail <span>�?'</span></a>
        <?php endif; ?>
    </div>

    <div class="dv2-card widget fade-in d4">
        <div class="messages-box">
            <div style="flex:1;">
                <div class="widget-head">
                    <div class="widget-icon"><i class="bx bx-envelope"></i></div>
                    <h3>Messages</h3>
                </div>
                <p style="color:var(--muted);font-weight:700;margin:0;font-size:13px;">Boîte Réservations</p>
                <div class="message-number"><?php echo e($stats['messages_count'] ?? 0); ?></div>
                <?php if(\Illuminate\Support\Facades\Route::has('admin.messagerie.index')): ?>
                    <a href="<?php echo e(route('admin.messagerie.index')); ?>" class="blue-btn">
                        <i class="bx bx-envelope"></i> Ouvrir la messagerie
                    </a>
                <?php endif; ?>
            </div>
            <div class="message-illu"><i class="bx bx-envelope"></i></div>
        </div>
    </div>

</div>


<div class="dv2-card status-card fade-in d3">
    <h3>Répartition des réservations</h3>
    <div class="status-grid">
        <?php
            $waiting = (int)($stats['reservations_en_cours'] ?? 0);
            $valid   = (int)($stats['reservations_validees'] ?? 0);
            $cancel  = (int)($stats['reservations_annulees'] ?? 0);
            $total   = (int)($stats['reservations_total'] ?? 0);
        ?>
        <div class="status-item">
            <div class="status-line"><span><i class="dot yellow"></i>En attente</span><strong><?php echo e($waiting); ?></strong></div>
            <div class="progress"><span style="background:var(--yellow);width:<?php echo e(($waiting / $rsTotal) * 100); ?>%;"></span></div>
        </div>
        <div class="status-item">
            <div class="status-line"><span><i class="dot green"></i>Validées</span><strong><?php echo e($valid); ?></strong></div>
            <div class="progress"><span style="background:var(--green);width:<?php echo e(($valid / $rsTotal) * 100); ?>%;"></span></div>
        </div>
        <div class="status-item">
            <div class="status-line"><span><i class="dot red"></i>Annulées</span><strong><?php echo e($cancel); ?></strong></div>
            <div class="progress"><span style="background:var(--red);width:<?php echo e(($cancel / $rsTotal) * 100); ?>%;"></span></div>
        </div>
        <div class="status-item">
            <div class="status-line"><span><i class="dot gray"></i>Total</span><strong><?php echo e($total); ?></strong></div>
            <div class="progress"><span style="background:#aab6c5;width:100%;"></span></div>
        </div>
    </div>
</div>


<div class="charts-grid">
    <div class="dv2-card chart-card fade-in d4">
        <div class="card-head">
            <h3>�?volution des réservations &amp; du chiffre d'affaires</h3>
            <span class="pill-select">6 derniers mois</span>
        </div>
        <div id="dashv2-chart-line" class="chart-area"></div>
    </div>
    <div class="dv2-card chart-card fade-in d5">
        <div class="card-head">
            <h3>Statut des réservations</h3>
        </div>
        <div id="dashv2-chart-donut" class="chart-area"></div>
    </div>
</div>


<div class="lower-grid">

    <div class="dv2-card chart-card fade-in d4">
        <div class="card-head"><h3>Paiements validés</h3></div>
        <div class="payments-chart">
            <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="pay-row">
                    <span><?php echo e($label); ?></span>
                    <div class="pay-bar"><span style="width:<?php echo e(($paySer[$i] / $payMax) * 100); ?>%;"></span></div>
                    <strong><?php echo e($paySer[$i]); ?></strong>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="empty-row">Aucun paiement enregistré.</div>
            <?php endif; ?>
        </div>
        <div style="margin-top:auto;padding-top:24px;">
            <?php if(\Illuminate\Support\Facades\Route::has('admin.reservations.index')): ?>
                <a href="<?php echo e(route('admin.reservations.index')); ?>" class="detail-link">Voir le détail <span>�?'</span></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dv2-card table-card fade-in d5">
        <div class="card-head">
            <h3>Dernières réservations</h3>
            <?php if(\Illuminate\Support\Facades\Route::has('admin.reservations.index')): ?>
                <a href="<?php echo e(route('admin.reservations.index')); ?>" class="pill-select">Voir toutes �?'</a>
            <?php endif; ?>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Client</th><th>Voyage</th>
                        <th>Statut</th><th>Paiement</th><th>Montant</th>
                        <th>Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $lastReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><span style="color:var(--muted);">#<?php echo e($r->id); ?></span></td>
                            <td>
                                <span class="client-name"><?php echo e(trim(($r->client_first_name ?? '').' '.($r->client_last_name ?? '')) ?: '�?"'); ?></span>
                                <?php if($r->client_email): ?>
                                    <span class="client-email"><?php echo e($r->client_email); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($r->tour): ?>
                                    <?php echo e(\Illuminate\Support\Str::limit($r->tour->name, 30)); ?>

                                <?php else: ?>
                                    <span style="color:var(--muted);">�?"</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $st = strtolower($r->status ?? '');
                                    $cls = match(true) {
                                        in_array($st, ['valide','validee','validée','validated','confirmed']) => 'valid',
                                        in_array($st, ['annule','annulee','annulée','cancelled','canceled'])  => 'cancel',
                                        default => 'pending',
                                    };
                                    $stLabel = match($cls) {
                                        'valid'  => 'Validée',
                                        'cancel' => 'Annulée',
                                        default  => 'En cours',
                                    };
                                ?>
                                <span class="tag <?php echo e($cls); ?>"><?php echo e($stLabel); ?></span>
                            </td>
                            <td><?php echo e(strtoupper($r->payment_type ?? '�?"')); ?></td>
                            <td>
                                <?php if(!empty($r->base_price)): ?>
                                    <?php echo e(number_format(($r->base_price ?? 0) + ($r->room_supplement_total ?? 0), 0, ',', ' ')); ?> �,�
                                <?php else: ?>
                                    <span style="color:var(--muted);">�?"</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space:nowrap;font-size:12px;">
                                <?php echo e($r->created_at ? $r->created_at->timezone('Africa/Casablanca')->format('d/m/Y H:i') : '�?"'); ?>

                            </td>
                            <td>
                                <?php if(\Illuminate\Support\Facades\Route::has('admin.reservations.edit')): ?>
                                    <a href="<?php echo e(route('admin.reservations.edit', $r)); ?>" class="view-btn" title="Voir"><i class="bx bx-show"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="8" class="empty-row">Aucune réservation récente.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>


<div class="bottom-grid">

    <div class="dv2-card table-card fade-in d4">
        <div class="card-head">
            <h3>Voyages les plus réservés</h3>
            <?php if(\Illuminate\Support\Facades\Route::has('admin.circuits.voyages.index')): ?>
                <a href="<?php echo e(route('admin.circuits.voyages.index')); ?>" class="pill-select">Voir tous</a>
            <?php endif; ?>
        </div>
        <div class="trip-list">
            <?php $__empty_1 = true; $__currentLoopData = $topVoyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $voyage = $tv['voyage'];
                    $count  = $tv['total'];
                    $img    = $voyage->main_image_url ?? null;
                ?>
                <div class="trip-item">
                    <?php if($img): ?>
                        <img class="trip-img" src="<?php echo e($img); ?>" alt="">
                    <?php else: ?>
                        <div class="trip-img"><i class="bx bx-map"></i></div>
                    <?php endif; ?>
                    <div class="trip-title"><?php echo e(\Illuminate\Support\Str::limit($voyage->name ?? 'Voyage', 50)); ?></div>
                    <strong class="trip-count"><?php echo e($count); ?></strong>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="empty-row">Aucun voyage réservé pour le moment.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dv2-card table-card fade-in d5">
        <div class="card-head">
            <h3>Points de vente actifs</h3>
            <?php if(\Illuminate\Support\Facades\Route::has('admin.agencies.index')): ?>
                <a href="<?php echo e(route('admin.agencies.index')); ?>" class="pill-select">Voir toutes</a>
            <?php endif; ?>
        </div>
        <div class="agency-list">
            <?php $__empty_1 = true; $__currentLoopData = $recentBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="agency-item">
                    <div class="agency-icon"><i class="bx bx-buildings"></i></div>
                    <div>
                        <div class="agency-title"><?php echo e($b->name); ?></div>
                        <?php if($b->city || $b->code): ?>
                            <div class="agency-city"><?php echo e($b->city); ?><?php echo e($b->code ? ' �?� '.$b->code : ''); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if(\Illuminate\Support\Facades\Route::has('admin.agencies.show')): ?>
                        <a href="<?php echo e(route('admin.agencies.show', $b)); ?>" class="agency-action" title="Voir">�?'</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="empty-row">Aucun point de vente à afficher.</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(URL::asset('build/libs/apexcharts/apexcharts.min.js')); ?>"></script>
<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;

    var elLine = document.querySelector('#dashv2-chart-line');
    if (elLine) {
        new ApexCharts(elLine, {
            series: [
                { name: 'Réservations',           type: 'column', data: <?php echo json_encode($stats['chart_reservations'] ?? [], 15, 512) ?> },
                { name: "Chiffre d'affaires (�,�)", type: 'line',   data: <?php echo json_encode($stats['chart_revenue'] ?? [], 15, 512) ?> }
            ],
            chart: {
                height: 320, type: 'line',
                toolbar: { show: false }, zoom: { enabled: false },
                animations: { enabled: true, speed: 600 },
                fontFamily: 'Inter, sans-serif'
            },
            colors: ['#0b68d1', '#ff8a00'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: [0, 3] },
            fill: { type: ['solid', 'solid'], opacity: [0.95, 1] },
            xaxis: {
                categories: <?php echo json_encode($stats['chart_months'] ?? [], 15, 512) ?>,
                labels: { style: { colors: '#8c9aad', fontWeight: 700, fontSize: '11px' } },
                axisBorder: { color: '#edf2f7' },
                axisTicks: { color: '#edf2f7' }
            },
            yaxis: [
                {
                    title: { text: 'Réservations', style: { color: '#8c9aad', fontWeight: 700 } },
                    labels: { style: { colors: '#8c9aad' }, formatter: function (v) { return Math.round(v); } }
                },
                {
                    opposite: true,
                    title: { text: 'CA (�,�)', style: { color: '#8c9aad', fontWeight: 700 } },
                    labels: {
                        style: { colors: '#8c9aad' },
                        formatter: function (v) { return v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v; }
                    }
                }
            ],
            legend: { position: 'top', horizontalAlign: 'right', fontWeight: 700, fontFamily: 'Inter, sans-serif' },
            plotOptions: { bar: { columnWidth: '42%', borderRadius: 6 } },
            grid: { borderColor: '#edf2f7', strokeDashArray: 4 }
        }).render();
    }

    var elDonut = document.querySelector('#dashv2-chart-donut');
    if (elDonut) {
        new ApexCharts(elDonut, {
            series: <?php echo json_encode($stats['donut_series'] ?? [0, 0, 0]) ?>,
            chart: {
                height: 320, type: 'donut',
                animations: { enabled: true, speed: 600 },
                fontFamily: 'Inter, sans-serif'
            },
            labels: <?php echo json_encode($stats['donut_labels'] ?? ['En cours', 'Validées', 'Annulées']) ?>,
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name: { fontWeight: 700, color: '#71829a' },
                            value: { fontSize: '26px', fontWeight: 900, color: '#172b4d' },
                            total: {
                                show: true, label: 'Total',
                                fontWeight: 700, color: '#71829a',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0);
                                }
                            }
                        }
                    }
                }
            },
            legend: { position: 'bottom', fontWeight: 700, fontFamily: 'Inter, sans-serif' },
            colors: ['#f7b500', '#19b982', '#ef4d45'],
            stroke: { width: 0 },
            dataLabels: { enabled: false }
        }).render();
    }
})();
</script>
<?php $__env->stopPush(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\dashboard\v2\index.blade.php ENDPATH**/ ?>