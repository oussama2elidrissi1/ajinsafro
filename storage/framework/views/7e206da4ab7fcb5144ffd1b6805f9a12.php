

<?php $__env->startSection('title', 'Tableau de bord â€” Vue globale'); ?>

<?php $__env->startPush('styles'); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* =========================================================
   AJ Dashboard â€” design system Ajinsafro
   â–¸ Tout est scopÃ© Ã  .aj-dash-2026
   â–¸ Aucun override du sidebar/topbar/layout Qovex
   â–¸ Aucun override de Bootstrap .row / .col
   ========================================================= */

.aj-dash-2026 {
    --d-blue: #0b68d1;
    --d-blue-dark: #073b74;
    --d-blue-soft: #eef6ff;
    --d-orange: #ff8a00;
    --d-green: #19b982;
    --d-red: #ef4d45;
    --d-yellow: #f7b500;
    --d-purple: #8b5cf6;
    --d-text: #172b4d;
    --d-muted: #71829a;
    --d-border: #e6edf5;
    --d-bg: #f6f9fc;
    --d-shadow: 0 12px 35px rgba(15, 45, 75, 0.08);
    --d-radius: 18px;

    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background: var(--d-bg);
    color: var(--d-text);
    font-size: 14px;
    margin: -1.5rem -0.75rem;
    padding: 22px 28px 40px;
    min-height: calc(100vh - 100px);
}

.aj-dash-2026 *,
.aj-dash-2026 *::before,
.aj-dash-2026 *::after {
    box-sizing: border-box;
}

.aj-dash-2026 a { text-decoration: none; color: inherit; }
.aj-dash-2026 button, .aj-dash-2026 input, .aj-dash-2026 select { font-family: inherit; }

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ PAGE HEAD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.aj-dash-2026 .page-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}

.aj-dash-2026 .page-title {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.aj-dash-2026 .page-title-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: var(--d-blue-soft);
    color: var(--d-blue);
    display: grid;
    place-items: center;
    font-size: 24px;
    flex-shrink: 0;
}

.aj-dash-2026 .page-title h1 {
    font-size: 30px;
    font-weight: 900;
    color: var(--d-text);
    margin: 0 0 6px 0;
    line-height: 1.1;
}

.aj-dash-2026 .page-title p {
    color: var(--d-muted);
    font-weight: 600;
    margin: 0;
    font-size: 14px;
}

.aj-dash-2026 .page-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.aj-dash-2026 .control-btn {
    height: 44px;
    padding: 0 16px;
    border-radius: 14px;
    border: 1px solid var(--d-border);
    background: #fff;
    color: var(--d-blue-dark);
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 6px 16px rgba(15,45,75,.04);
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: 0.2s ease;
}

.aj-dash-2026 .control-btn:hover {
    background: #f7fbff;
    color: var(--d-blue);
}

.aj-dash-2026 .control-btn.primary {
    background: var(--d-blue);
    color: #fff;
    border-color: var(--d-blue);
}

.aj-dash-2026 .control-btn.primary:hover {
    background: var(--d-blue-dark);
    color: #fff;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ CARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.aj-dash-2026 .d-card {
    background: #fff;
    border: 1px solid var(--d-border);
    border-radius: var(--d-radius);
    box-shadow: var(--d-shadow);
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ KPI GRID â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.aj-dash-2026 .kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 22px;
}

.aj-dash-2026 .kpi-card {
    padding: 24px;
    min-height: 166px;
    display: grid;
    align-content: space-between;
    position: relative;
    transition: transform 0.22s ease, box-shadow 0.22s ease;
}

.aj-dash-2026 .kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 45px rgba(15, 45, 75, 0.12);
}

.aj-dash-2026 .kpi-icon {
    width: 58px;
    height: 58px;
    border-radius: 17px;
    display: grid;
    place-items: center;
    font-size: 24px;
}

.aj-dash-2026 .kpi-icon.blue   { background: #e8f1ff; color: var(--d-blue); }
.aj-dash-2026 .kpi-icon.green  { background: #e7fff4; color: var(--d-green); }
.aj-dash-2026 .kpi-icon.orange { background: #fff0df; color: var(--d-orange); }
.aj-dash-2026 .kpi-icon.purple { background: #f1eaff; color: var(--d-purple); }

.aj-dash-2026 .kpi-label {
    color: #61728a;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-top: 18px;
}

.aj-dash-2026 .kpi-value {
    font-size: 34px;
    font-weight: 900;
    color: var(--d-text);
    margin: 8px 0;
    line-height: 1.05;
}

.aj-dash-2026 .kpi-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--d-muted);
    font-weight: 700;
    padding-top: 14px;
    border-top: 1px solid #edf2f7;
    font-size: 13px;
}

.aj-dash-2026 .kpi-footer .arrow {
    color: var(--d-blue);
    font-weight: 900;
}

.aj-dash-2026 .kpi-card .stretched {
    position: absolute;
    inset: 0;
    z-index: 1;
    border-radius: inherit;
}

.aj-dash-2026 .badge-red {
    background: #fff0ef;
    color: var(--d-red);
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
}

.aj-dash-2026 .badge-green {
    background: #e8fff4;
    color: var(--d-green);
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ TOP WIDGETS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.aj-dash-2026 .top-widgets {
    display: grid;
    grid-template-columns: 1fr 1fr 1.3fr;
    gap: 20px;
    margin-bottom: 22px;
}

.aj-dash-2026 .widget {
    padding: 24px;
}

.aj-dash-2026 .widget-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
}

.aj-dash-2026 .widget-head h3 {
    font-size: 17px;
    font-weight: 900;
    color: var(--d-text);
    margin: 0;
}

.aj-dash-2026 .widget-icon {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    background: var(--d-blue-soft);
    color: var(--d-blue);
    flex-shrink: 0;
}

.aj-dash-2026 .metric-list {
    display: grid;
    gap: 14px;
    margin-bottom: 18px;
}

.aj-dash-2026 .metric-row {
    display: flex;
    justify-content: space-between;
    color: var(--d-muted);
    font-weight: 700;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f3f7;
}

.aj-dash-2026 .metric-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.aj-dash-2026 .metric-row strong {
    color: var(--d-text);
    font-weight: 900;
}

.aj-dash-2026 .detail-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid var(--d-border);
    border-radius: 13px;
    padding: 13px 15px;
    color: var(--d-blue-dark);
    font-weight: 800;
    background: #fff;
    transition: 0.2s;
    font-size: 13px;
}

.aj-dash-2026 .detail-link:hover {
    background: var(--d-blue-soft);
    border-color: var(--d-blue);
    color: var(--d-blue);
}

.aj-dash-2026 .revenue-value {
    font-size: 30px;
    color: var(--d-blue);
    font-weight: 900;
    margin-bottom: 8px;
    line-height: 1.1;
}

.aj-dash-2026 .small-green {
    display: inline-flex;
    background: #e9fff4;
    color: var(--d-green);
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 900;
    margin-bottom: 18px;
    align-items: center;
    gap: 4px;
}

.aj-dash-2026 .small-red {
    display: inline-flex;
    background: #fff0ef;
    color: var(--d-red);
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 900;
    margin-bottom: 18px;
    align-items: center;
    gap: 4px;
}

.aj-dash-2026 .messages-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.aj-dash-2026 .message-number {
    font-size: 32px;
    font-weight: 900;
    margin: 6px 0 18px;
    color: var(--d-text);
}

.aj-dash-2026 .blue-btn {
    border: none;
    background: var(--d-blue);
    color: #fff;
    padding: 12px 16px;
    border-radius: 13px;
    font-weight: 900;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    text-decoration: none;
    transition: 0.2s;
}

.aj-dash-2026 .blue-btn:hover {
    background: var(--d-blue-dark);
    color: #fff;
}

.aj-dash-2026 .message-illu {
    font-size: 80px;
    opacity: .09;
    line-height: 1;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ STATUS CARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.aj-dash-2026 .status-card {
    padding: 24px;
    margin-bottom: 22px;
}

.aj-dash-2026 .status-card h3 {
    font-size: 17px;
    font-weight: 900;
    margin: 0 0 18px 0;
    color: var(--d-text);
}

.aj-dash-2026 .status-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 28px;
}

.aj-dash-2026 .status-item {
    display: grid;
    gap: 9px;
}

.aj-dash-2026 .status-line {
    display: flex;
    justify-content: space-between;
    font-weight: 800;
    color: var(--d-muted);
    font-size: 13px;
}

.aj-dash-2026 .dot {
    display: inline-block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    margin-right: 8px;
}

.aj-dash-2026 .dot.yellow { background: var(--d-yellow); }
.aj-dash-2026 .dot.green  { background: var(--d-green); }
.aj-dash-2026 .dot.red    { background: var(--d-red); }
.aj-dash-2026 .dot.gray   { background: #aab6c5; }
.aj-dash-2026 .dot.blue   { background: var(--d-blue); }

.aj-dash-2026 .progress-bar-d {
    height: 8px;
    border-radius: 999px;
    background: #edf2f7;
    overflow: hidden;
}

.aj-dash-2026 .progress-bar-d > span {
    display: block;
    height: 100%;
    border-radius: inherit;
    transition: width 0.5s ease;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ CHARTS GRID â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.aj-dash-2026 .charts-grid {
    display: grid;
    grid-template-columns: 1.55fr .9fr;
    gap: 20px;
    margin-bottom: 22px;
}

.aj-dash-2026 .chart-card {
    padding: 24px;
    min-height: 395px;
}

.aj-dash-2026 .card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
}

.aj-dash-2026 .card-head h3 {
    font-size: 17px;
    font-weight: 900;
    color: var(--d-text);
    margin: 0;
}

.aj-dash-2026 .pill-select {
    border: none;
    background: var(--d-blue-soft);
    color: var(--d-blue);
    font-weight: 900;
    padding: 9px 14px;
    border-radius: 12px;
    font-size: 12px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: 0.2s;
}

.aj-dash-2026 .pill-select:hover {
    background: #d8e9ff;
    color: var(--d-blue-dark);
}

.aj-dash-2026 .chart-area {
    width: 100%;
    min-height: 290px;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ LOWER GRID (paiements + derniÃ¨res rÃ©servations) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.aj-dash-2026 .lower-grid {
    display: grid;
    grid-template-columns: .75fr 1.55fr;
    gap: 20px;
    margin-bottom: 22px;
}

.aj-dash-2026 .payments-chart {
    display: grid;
    gap: 18px;
    margin-top: 12px;
}

.aj-dash-2026 .pay-row {
    display: grid;
    grid-template-columns: 110px 1fr 30px;
    align-items: center;
    gap: 12px;
    color: var(--d-muted);
    font-weight: 800;
    font-size: 13px;
}

.aj-dash-2026 .pay-row strong {
    color: var(--d-text);
    text-align: right;
    font-weight: 900;
}

.aj-dash-2026 .pay-bar {
    height: 14px;
    background: #edf2f7;
    border-radius: 999px;
    overflow: hidden;
}

.aj-dash-2026 .pay-bar > span {
    display: block;
    height: 100%;
    background: linear-gradient(90deg, #294c99, #2f7df4);
    border-radius: inherit;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ TABLE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.aj-dash-2026 .table-card {
    padding: 0;
    overflow: hidden;
}

.aj-dash-2026 .table-card .card-head {
    padding: 22px 22px 16px;
}

.aj-dash-2026 .d-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.aj-dash-2026 .d-table th {
    text-align: left;
    padding: 12px 16px;
    background: #f7fbff;
    color: #66758a;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .05em;
    font-weight: 900;
    border-bottom: 1px solid var(--d-border);
    white-space: nowrap;
}

.aj-dash-2026 .d-table td {
    padding: 13px 16px;
    border-bottom: 1px solid #edf2f7;
    color: #42556d;
    font-weight: 700;
    vertical-align: middle;
}

.aj-dash-2026 .d-table tbody tr:hover {
    background: #fafdff;
}

.aj-dash-2026 .d-table tbody tr:last-child td {
    border-bottom: none;
}

.aj-dash-2026 .client-name {
    font-weight: 900;
    color: var(--d-text);
    display: block;
}

.aj-dash-2026 .client-email {
    color: var(--d-muted);
    font-size: 11px;
    display: block;
    max-width: 180px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 600;
}

.aj-dash-2026 .tag {
    display: inline-flex;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
    white-space: nowrap;
}

.aj-dash-2026 .tag.pending { background: #fff4d8; color: #9a6b00; }
.aj-dash-2026 .tag.valid   { background: #e8fff4; color: var(--d-green); }
.aj-dash-2026 .tag.cancel  { background: #fff0ef; color: var(--d-red); }

.aj-dash-2026 .view-btn {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    border: 1px solid #dce8f3;
    background: #fff;
    color: var(--d-blue);
    cursor: pointer;
    font-weight: 900;
    display: inline-grid;
    place-items: center;
    text-decoration: none;
    transition: 0.2s;
}

.aj-dash-2026 .view-btn:hover {
    background: var(--d-blue-soft);
    border-color: var(--d-blue);
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ BOTTOM GRID (voyages + agences) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.aj-dash-2026 .bottom-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.aj-dash-2026 .trip-list,
.aj-dash-2026 .agency-list {
    padding: 0 22px 22px;
    display: grid;
    gap: 12px;
}

.aj-dash-2026 .trip-item,
.aj-dash-2026 .agency-item {
    display: grid;
    grid-template-columns: 56px 1fr auto;
    gap: 14px;
    align-items: center;
    padding: 11px 0;
    border-bottom: 1px solid #edf2f7;
}

.aj-dash-2026 .trip-item:last-child,
.aj-dash-2026 .agency-item:last-child {
    border-bottom: 0;
}

.aj-dash-2026 .trip-img {
    width: 56px;
    height: 44px;
    border-radius: 12px;
    object-fit: cover;
    background: var(--d-blue-soft);
    display: grid;
    place-items: center;
    color: var(--d-blue);
    font-size: 18px;
}

.aj-dash-2026 .trip-title,
.aj-dash-2026 .agency-title {
    color: var(--d-text);
    font-weight: 800;
    line-height: 1.35;
    font-size: 13px;
}

.aj-dash-2026 .trip-count,
.aj-dash-2026 .agency-action {
    color: var(--d-blue);
    font-weight: 900;
    font-size: 16px;
}

.aj-dash-2026 .agency-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: #e8fff4;
    color: var(--d-green);
    display: grid;
    place-items: center;
    font-size: 20px;
}

.aj-dash-2026 .agency-city {
    color: var(--d-muted);
    font-size: 11px;
    font-weight: 700;
    margin-top: 3px;
}

.aj-dash-2026 .empty-row {
    padding: 30px;
    color: var(--d-muted);
    text-align: center;
    font-weight: 700;
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ ANIMATIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.aj-dash-2026 .fade-in {
    animation: ajDashFadeIn 0.45s ease both;
}

.aj-dash-2026 .fade-in.d1 { animation-delay: 0.05s; }
.aj-dash-2026 .fade-in.d2 { animation-delay: 0.10s; }
.aj-dash-2026 .fade-in.d3 { animation-delay: 0.15s; }
.aj-dash-2026 .fade-in.d4 { animation-delay: 0.20s; }
.aj-dash-2026 .fade-in.d5 { animation-delay: 0.25s; }

@keyframes ajDashFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ RESPONSIVE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
@media (max-width: 1400px) {
    .aj-dash-2026 .top-widgets { grid-template-columns: 1fr 1fr; }
    .aj-dash-2026 .top-widgets > :nth-child(3) { grid-column: 1 / -1; }
    .aj-dash-2026 .lower-grid  { grid-template-columns: 1fr; }
}

@media (max-width: 1200px) {
    .aj-dash-2026 .kpi-grid    { grid-template-columns: repeat(2, 1fr); }
    .aj-dash-2026 .top-widgets { grid-template-columns: 1fr; }
    .aj-dash-2026 .top-widgets > :nth-child(3) { grid-column: auto; }
    .aj-dash-2026 .charts-grid { grid-template-columns: 1fr; }
    .aj-dash-2026 .bottom-grid { grid-template-columns: 1fr; }
    .aj-dash-2026 .status-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .aj-dash-2026 { padding: 16px; margin: -1rem -0.5rem; }
    .aj-dash-2026 .kpi-grid    { grid-template-columns: 1fr; }
    .aj-dash-2026 .status-grid { grid-template-columns: 1fr; }
    .aj-dash-2026 .page-title h1 { font-size: 24px; }
    .aj-dash-2026 .page-head { flex-direction: column; }
    .aj-dash-2026 .d-table   { min-width: 750px; }
    .aj-dash-2026 .table-card { overflow-x: auto; }
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="dashboard-global-page dashboard-v3">
        <link rel="stylesheet" href="<?php echo e(asset('css/dashboard-global.css')); ?>">

<?php
    $now      = \Carbon\Carbon::now('Africa/Casablanca')->locale('fr');
    $dateLong = $now->translatedFormat('l d F Y');
    $dateTime = $now->translatedFormat('l d F Y Â· H:i');
    $rsTotal  = max(1, (int)($stats['reservations_total'] ?? 0));
    $payments = $stats['payment_labels'] ?? [];
    $paySeries = $stats['payment_series'] ?? [];
    $payMax = (count($paySeries) > 0) ? max(max($paySeries), 1) : 1;
?>

<div class="aj-dash-2026">

    
    <div class="page-head dashboard-header fade-in d1">
        <div class="page-title">
            <div class="page-title-icon"><i class="bx bx-grid-alt"></i></div>
            <div>
                <h1 class="dashboard-title">Tableau de bord</h1>
                <p class="dashboard-subtitle">Vue d'ensemble de votre activitÃ© â€” <?php echo e($dateTime); ?></p>
            </div>
        </div>

        <div class="page-actions">
            <span class="control-btn">
                <i class="bx bx-calendar"></i> <?php echo e(ucfirst($dateLong)); ?>

            </span>
            <a href="<?php echo e(route('admin.reservations.index')); ?>" class="control-btn primary">
                <i class="bx bx-list-ul"></i> RÃ©servations
            </a>
        </div>
    </div>

    
    <div class="kpi-grid">

        <div class="d-card dashboard-card kpi-card fade-in d1">
            <div>
                <div class="kpi-icon blue"><i class="bx bx-map-alt"></i></div>
                <div class="kpi-label">Voyages</div>
                <div class="kpi-value"><?php echo e($stats['voyages_count'] ?? 0); ?></div>
            </div>
            <div class="kpi-footer">
                <span>Tous les voyages</span>
                <span class="arrow">â†’</span>
            </div>
            <a href="<?php echo e(route('admin.circuits.voyages.index')); ?>" class="stretched"></a>
        </div>

        <div class="d-card dashboard-card kpi-card fade-in d2">
            <div>
                <div class="kpi-icon green"><i class="bx bx-buildings"></i></div>
                <div class="kpi-label">Agences</div>
                <div class="kpi-value"><?php echo e($stats['branches_count'] ?? 0); ?></div>
            </div>
            <div class="kpi-footer">
                <span><?php echo e($stats['branches_active'] ?? 0); ?> actives</span>
                <span class="arrow">â†’</span>
            </div>
            <a href="<?php echo e(route('admin.agencies.index')); ?>" class="stretched"></a>
        </div>

        <div class="d-card dashboard-card kpi-card fade-in d3">
            <div>
                <div class="kpi-icon orange"><i class="bx bx-calendar-event"></i></div>
                <div class="kpi-label">RÃ©servations</div>
                <div class="kpi-value"><?php echo e($stats['reservations_total'] ?? 0); ?></div>
                <?php $evo = $stats['reservations_month_evolution'] ?? 0; ?>
                <?php if($evo < 0): ?>
                    <span class="badge-red">â†“ <?php echo e($evo); ?>% ce mois</span>
                <?php elseif($evo > 0): ?>
                    <span class="badge-green">â†‘ +<?php echo e($evo); ?>% ce mois</span>
                <?php endif; ?>
            </div>
            <div class="kpi-footer">
                <span>Total enregistrÃ©</span>
                <span class="arrow">â†’</span>
            </div>
            <a href="<?php echo e(route('admin.reservations.index')); ?>" class="stretched"></a>
        </div>

        <div class="d-card dashboard-card kpi-card fade-in d4">
            <div>
                <div class="kpi-icon purple"><i class="bx bx-group"></i></div>
                <div class="kpi-label">Clients</div>
                <div class="kpi-value"><?php echo e($stats['clients_count'] ?? 0); ?></div>
            </div>
            <div class="kpi-footer">
                <span>Clients enregistrÃ©s</span>
                <span class="arrow">â†’</span>
            </div>
            <a href="<?php echo e(route('admin.customers.clients.index')); ?>" class="stretched"></a>
        </div>

    </div>

    
    <div class="top-widgets ops-grid">

        
        <div class="d-card dashboard-card widget fade-in d2">
            <div class="widget-head">
                <div class="widget-icon"><i class="bx bx-time-five"></i></div>
                <h3 class="card-title">ActivitÃ© rÃ©cente</h3>
            </div>

            <div class="metric-list">
                <div class="metric-row">
                    <span>Aujourd'hui</span>
                    <strong><?php echo e($stats['reservations_today'] ?? 0); ?> rÃ©sa</strong>
                </div>
                <div class="metric-row">
                    <span>Cette semaine</span>
                    <strong><?php echo e($stats['reservations_this_week'] ?? 0); ?> rÃ©sa</strong>
                </div>
                <div class="metric-row">
                    <span>Ce mois</span>
                    <strong><?php echo e($stats['reservations_this_month'] ?? 0); ?> rÃ©servations</strong>
                </div>
            </div>

            <a href="<?php echo e(route('admin.reservations.index')); ?>" class="detail-link">
                Voir le dÃ©tail <span>â†’</span>
            </a>
        </div>

        
        <div class="d-card dashboard-card widget fade-in d3">
            <div class="widget-head">
                <div class="widget-icon"><i class="bx bx-euro"></i></div>
                <h3 class="card-title">Chiffre d'affaires</h3>
            </div>

            <p style="color:var(--d-muted);font-weight:700;margin:0 0 4px;font-size:12px;">Total validÃ©</p>
            <div class="revenue-value"><?php echo e(number_format($stats['revenue_total'] ?? 0, 0, ',', ' ')); ?> â‚¬</div>
            <p style="color:var(--d-muted);font-weight:700;margin:0 0 8px;font-size:12px;">
                Ce mois : <strong style="color:var(--d-text);"><?php echo e(number_format($stats['revenue_this_month'] ?? 0, 0, ',', ' ')); ?> â‚¬</strong>
            </p>

            <?php $revEvo = $stats['revenue_month_evolution'] ?? 0; ?>
            <?php if($revEvo >= 0): ?>
                <span class="small-green">â†‘ +<?php echo e($revEvo); ?>% vs mois dernier</span>
            <?php else: ?>
                <span class="small-red">â†“ <?php echo e($revEvo); ?>% vs mois dernier</span>
            <?php endif; ?>

            <a href="<?php echo e(route('admin.reservations.index')); ?>" class="detail-link">
                Voir le dÃ©tail <span>â†’</span>
            </a>
        </div>

        
        <div class="d-card dashboard-card widget fade-in d4">
            <div class="messages-box">
                <div style="flex:1;">
                    <div class="widget-head">
                        <div class="widget-icon"><i class="bx bx-envelope"></i></div>
                        <h3 class="card-title">Messages</h3>
                    </div>
                    <p style="color:var(--d-muted);font-weight:700;margin:0;font-size:13px;">BoÃ®te RÃ©servations</p>
                    <div class="message-number"><?php echo e($stats['messages_count'] ?? 0); ?></div>
                    <a href="<?php echo e(route('admin.messagerie.index')); ?>" class="blue-btn">
                        <i class="bx bx-envelope"></i> Ouvrir la messagerie
                    </a>
                </div>
                <div class="message-illu"><i class="bx bx-envelope"></i></div>
            </div>
        </div>

    </div>

    
    <div class="d-card dashboard-card status-card fade-in d3">
        <h3 class="card-title">RÃ©partition des rÃ©servations</h3>

        <div class="status-grid">
            <?php
                $waiting = (int)($stats['reservations_en_cours'] ?? 0);
                $valid   = (int)($stats['reservations_validees'] ?? 0);
                $cancel  = (int)($stats['reservations_annulees'] ?? 0);
                $total   = (int)($stats['reservations_total'] ?? 0);
            ?>

            <div class="status-item">
                <div class="status-line">
                    <span><i class="dot yellow"></i>En attente</span>
                    <strong><?php echo e($waiting); ?></strong>
                </div>
                <div class="progress-bar-d">
                    <span style="background:var(--d-yellow);width:<?php echo e(($waiting / $rsTotal) * 100); ?>%;"></span>
                </div>
            </div>

            <div class="status-item">
                <div class="status-line">
                    <span><i class="dot green"></i>ValidÃ©es</span>
                    <strong><?php echo e($valid); ?></strong>
                </div>
                <div class="progress-bar-d">
                    <span style="background:var(--d-green);width:<?php echo e(($valid / $rsTotal) * 100); ?>%;"></span>
                </div>
            </div>

            <div class="status-item">
                <div class="status-line">
                    <span><i class="dot red"></i>AnnulÃ©es</span>
                    <strong><?php echo e($cancel); ?></strong>
                </div>
                <div class="progress-bar-d">
                    <span style="background:var(--d-red);width:<?php echo e(($cancel / $rsTotal) * 100); ?>%;"></span>
                </div>
            </div>

            <div class="status-item">
                <div class="status-line">
                    <span><i class="dot gray"></i>Total</span>
                    <strong><?php echo e($total); ?></strong>
                </div>
                <div class="progress-bar-d">
                    <span style="background:#aab6c5;width:100%;"></span>
                </div>
            </div>
        </div>
    </div>

    
    <div class="charts-grid">

        <div class="d-card dashboard-card chart-card fade-in d4">
            <div class="card-head">
                <h3 class="card-title">Ã‰volution des rÃ©servations & du chiffre d'affaires</h3>
                <span class="pill-select">6 derniers mois</span>
            </div>
            <div id="aj-chart-line" class="chart-area"></div>
        </div>

        <div class="d-card dashboard-card chart-card fade-in d5">
            <div class="card-head">
                <h3 class="card-title">Statut des rÃ©servations</h3>
            </div>
            <div id="aj-chart-donut" class="chart-area"></div>
        </div>

    </div>

    
    <div class="lower-grid bottom-grid">

        
        <div class="d-card dashboard-card chart-card fade-in d4">
            <div class="card-head">
                <h3 class="card-title">Paiements validÃ©s</h3>
            </div>

            <div class="payments-chart">
                <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="pay-row">
                        <span><?php echo e($label); ?></span>
                        <div class="pay-bar"><span style="width:<?php echo e(($paySeries[$i] / $payMax) * 100); ?>%;"></span></div>
                        <strong><?php echo e($paySeries[$i]); ?></strong>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty-row">Aucun paiement enregistrÃ©.</div>
                <?php endif; ?>
            </div>

            <div style="margin-top:auto;padding-top:24px;">
                <a href="<?php echo e(route('admin.reservations.index')); ?>" class="detail-link">
                    Voir le dÃ©tail <span>â†’</span>
                </a>
            </div>
        </div>

        
        <div class="d-card dashboard-card table-card fade-in d5">
            <div class="card-head">
                <h3 class="card-title">DerniÃ¨res rÃ©servations</h3>
                <a href="<?php echo e(route('admin.reservations.index')); ?>" class="pill-select">Voir toutes â†’</a>
            </div>

            <div style="overflow-x:auto;">
                <table class="d-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Voyage</th>
                            <th>Statut</th>
                            <th>Paiement</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $lastReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><span style="color:var(--d-muted);">#<?php echo e($r->id); ?></span></td>
                                <td>
                                    <span class="client-name"><?php echo e(trim(($r->client_first_name ?? '').' '.($r->client_last_name ?? '')) ?: 'â€”'); ?></span>
                                    <?php if($r->client_email): ?>
                                        <span class="client-email"><?php echo e($r->client_email); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($r->tour): ?>
                                        <?php echo e(\Illuminate\Support\Str::limit($r->tour->name, 30)); ?>

                                    <?php else: ?>
                                        <span style="color:var(--d-muted);">â€”</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $st = strtolower($r->status ?? '');
                                        $cls = match(true) {
                                            in_array($st, ['valide','validee','validÃ©e','validated','confirmed']) => 'valid',
                                            in_array($st, ['annule','annulee','annulÃ©e','cancelled','canceled']) => 'cancel',
                                            default => 'pending',
                                        };
                                        $stLabel = match($cls) {
                                            'valid'  => 'ValidÃ©e',
                                            'cancel' => 'AnnulÃ©e',
                                            default  => 'En cours',
                                        };
                                    ?>
                                    <span class="tag <?php echo e($cls); ?>"><?php echo e($stLabel); ?></span>
                                </td>
                                <td><?php echo e(strtoupper($r->payment_type ?? 'â€”')); ?></td>
                                <td>
                                    <?php if(!empty($r->base_price)): ?>
                                        <?php echo e(number_format(($r->base_price ?? 0) + ($r->room_supplement_total ?? 0), 0, ',', ' ')); ?> â‚¬
                                    <?php else: ?>
                                        <span style="color:var(--d-muted);">â€”</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;font-size:12px;">
                                    <?php echo e($r->created_at ? $r->created_at->timezone('Africa/Casablanca')->format('d/m/Y H:i') : 'â€”'); ?>

                                </td>
                                <td>
                                    <a href="<?php echo e(route('admin.reservations.edit', $r)); ?>" class="view-btn" title="Voir">
                                        <i class="bx bx-show"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="empty-row">Aucune rÃ©servation rÃ©cente.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    
    <div class="bottom-grid lists-grid">

        
        <div class="d-card dashboard-card list-card table-card fade-in d4">
            <div class="card-head">
                <h3 class="card-title">Voyages les plus rÃ©servÃ©s</h3>
                <a href="<?php echo e(route('admin.circuits.voyages.index')); ?>" class="pill-select">Voir tous</a>
            </div>

            <div class="trip-list">
                <?php $__empty_1 = true; $__currentLoopData = $topVoyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $voyage = $tv['voyage'];
                        $count  = $tv['total'];
                        $img    = $voyage->main_image_url ?? null;
                    ?>
                    <div class="trip-item list-item">
                        <?php if($img): ?>
                            <img class="trip-img" src="<?php echo e($img); ?>" alt="">
                        <?php else: ?>
                            <div class="trip-img"><i class="bx bx-map"></i></div>
                        <?php endif; ?>
                        <div class="trip-title list-item-title"><?php echo e(\Illuminate\Support\Str::limit($voyage->name ?? 'Voyage', 50)); ?></div>
                        <strong class="trip-count list-item-value"><?php echo e($count); ?></strong>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty-row">Aucun voyage rÃ©servÃ© pour le moment.</div>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="d-card dashboard-card list-card table-card fade-in d5">
            <div class="card-head">
                <h3 class="card-title">Agences actives</h3>
                <a href="<?php echo e(route('admin.agencies.index')); ?>" class="pill-select">Voir toutes</a>
            </div>

            <div class="agency-list">
                <?php $__empty_1 = true; $__currentLoopData = $recentBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="agency-item list-item">
                        <div class="agency-icon"><i class="bx bx-buildings"></i></div>
                        <div>
                            <div class="agency-title list-item-title"><?php echo e($b->name); ?></div>
                            <?php if($b->city || $b->code): ?>
                                <div class="agency-city list-item-subtitle"><?php echo e($b->city); ?><?php echo e($b->code ? ' â€¢ '.$b->code : ''); ?></div>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo e(route('admin.agencies.show', $b)); ?>" class="agency-action list-item-value" title="Voir">â†’</a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty-row">Aucune agence Ã  afficher.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(URL::asset('build/libs/apexcharts/apexcharts.min.js')); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;

    // â”€â”€â”€ Combo : RÃ©servations (colonnes) + CA (ligne) â”€â”€â”€
    var elLine = document.querySelector('#aj-chart-line');
    if (elLine) {
        new ApexCharts(elLine, {
            series: [
                { name: 'RÃ©servations',           type: 'column', data: <?php echo json_encode($stats['chart_reservations'] ?? [], 15, 512) ?> },
                { name: "Chiffre d'affaires (â‚¬)", type: 'line',   data: <?php echo json_encode($stats['chart_revenue'] ?? [], 15, 512) ?> }
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
                    title: { text: 'RÃ©servations', style: { color: '#8c9aad', fontWeight: 700 } },
                    labels: { style: { colors: '#8c9aad' }, formatter: function (v) { return Math.round(v); } }
                },
                {
                    opposite: true,
                    title: { text: 'CA (â‚¬)', style: { color: '#8c9aad', fontWeight: 700 } },
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

    // â”€â”€â”€ Donut : Statut des rÃ©servations â”€â”€â”€
    var elDonut = document.querySelector('#aj-chart-donut');
    if (elDonut) {
        new ApexCharts(elDonut, {
            series: <?php echo json_encode($stats['donut_series'] ?? [0, 0, 0]) ?>,
            chart: {
                height: 320, type: 'donut',
                animations: { enabled: true, speed: 600 },
                fontFamily: 'Inter, sans-serif'
            },
            labels: <?php echo json_encode($stats['donut_labels'] ?? ['En cours', 'ValidÃ©es', 'AnnulÃ©es']) ?>,
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
});
</script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\dashboard\vue-globale\index.blade.php ENDPATH**/ ?>