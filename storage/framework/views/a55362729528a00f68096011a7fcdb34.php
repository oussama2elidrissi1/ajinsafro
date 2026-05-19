

<?php $__env->startSection('title', 'Dashboard V4'); ?>

<?php
    $dashboardUser = auth()->user();
    $dashboardUserName = $dashboardUser?->name ?? 'Admin Ajinsafro';
    $dashboardUserRole = $dashboardUser?->getRoleNames()->first() ?? 'Administrateur';
    $dashboardInitials = strtoupper(collect(preg_split('/\s+/', trim((string) $dashboardUserName)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    if ($dashboardInitials === '') {
        $dashboardInitials = 'AA';
    }

    $dashboardBrandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $dashboardBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $dashboardRouteIs = fn (string $route): bool => request()->routeIs($route) || request()->routeIs($route.'.*');

    $revenueSeries = [
        ['month' => 'Jan', 'revenue' => 180000, 'reservations' => 45],
        ['month' => 'Fev', 'revenue' => 220000, 'reservations' => 53],
        ['month' => 'Mar', 'revenue' => 310000, 'reservations' => 71],
        ['month' => 'Avr', 'revenue' => 285000, 'reservations' => 64],
        ['month' => 'Mai', 'revenue' => 390000, 'reservations' => 88],
        ['month' => 'Juin', 'revenue' => 470000, 'reservations' => 104],
        ['month' => 'Juil', 'revenue' => 620000, 'reservations' => 137],
    ];

    $departures = [
        ['name' => 'Dakhla Premium', 'date' => '23 Mai 2026', 'sold' => 18, 'capacity' => 24, 'status' => 'Ouvert', 'city' => 'Tanger', 'price' => '3 980 DH'],
        ['name' => 'Omra Ramadan', 'date' => '01 Juin 2026', 'sold' => 42, 'capacity' => 50, 'status' => 'Urgent', 'city' => 'Casablanca', 'price' => '16 900 DH'],
        ['name' => 'Istanbul Express', 'date' => '08 Juin 2026', 'sold' => 29, 'capacity' => 32, 'status' => 'Presque complet', 'city' => 'Rabat', 'price' => '7 800 DH'],
        ['name' => 'Marrakech Groupe', 'date' => '15 Juin 2026', 'sold' => 11, 'capacity' => 20, 'status' => 'Ouvert', 'city' => 'Tanger', 'price' => '2 450 DH'],
    ];

    $recentReservations = [
        ['client' => 'Nadia El Amrani', 'trip' => 'Dakhla Premium', 'amount' => '7 960 DH', 'status' => 'Confirmée', 'agent' => 'Oumayma', 'time' => 'Il y a 12 min'],
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

    $operationalAlerts = [
        ['label' => 'Dossiers validés', 'value' => '82%', 'icon' => 'bx bx-check-circle', 'tone' => 'ok'],
        ['label' => 'Acomptes à suivre', 'value' => '17', 'icon' => 'bx bx-time-five', 'tone' => 'warn'],
        ['label' => 'Rooming incomplet', 'value' => '6', 'icon' => 'bx bx-error-circle', 'tone' => 'danger'],
        ['label' => 'Commissions à approuver', 'value' => '12', 'icon' => 'bx bx-wallet', 'tone' => 'info'],
    ];

    $stats = $stats ?? [];
    $revenueTotal = (float) ($stats['revenue_total'] ?? array_sum(array_column($revenueSeries, 'revenue')));
    $reservationsTotal = (int) ($stats['reservations_total'] ?? array_sum(array_column($revenueSeries, 'reservations')));
    $agentsActive = (int) ($stats['branches_count'] ?? 14);
    $departuresActive = (int) ($stats['branches_active'] ?? 38);
    $confirmedRatio = (int) ($stats['reservations_validees'] ?? 92);
    $monthEvolution = (float) ($stats['revenue_month_evolution'] ?? 18.6);
    $maxRevenue = max(array_column($revenueSeries, 'revenue'));
    $maxChannel = max(array_column($salesChannels, 'value'));
    $dashboardBrandLogoEscaped = e($dashboardBrandLogo);
?>

<?php $__env->startPush('styles'); ?>
<style>
    body.aj-admin-v2-body .aj-admin-v2-sidebar,
    body.aj-admin-v2-body .aj-topbar,
    body.aj-admin-v2-body .aj-footer {
        display: none !important;
    }

    body.aj-admin-v2-body .aj-admin-v2-layout {
        display: block;
        min-height: 100vh;
        background:
            radial-gradient(circle at 80% 0%, rgba(244, 123, 32, 0.08), transparent 28%),
            linear-gradient(180deg, #fbfcfe 0%, #f6f8fb 100%);
    }

    body.aj-admin-v2-body .aj-admin-v2-main {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 0 !important;
        min-height: 100vh;
    }

    body.aj-admin-v2-body .aj-admin-v2-content {
        padding: 0 !important;
        margin: 0 !important;
    }

    body.aj-admin-v2-body .aj-admin-v2-overlay {
        display: none !important;
    }

    .aj-dashboard-v4,
    .aj-dashboard-v4 * {
        box-sizing: border-box;
    }

    .aj-dashboard-v4 {
        --aj-blue-950: #06192d;
        --aj-blue-900: #08213d;
        --aj-blue-800: #0b315c;
        --aj-blue-700: #0f4f8f;
        --aj-blue-600: #1268b3;
        --aj-orange-600: #f47b20;
        --aj-orange-500: #ff8f2b;
        --aj-orange-100: #fff1e5;
        --aj-green-600: #19a463;
        --aj-green-100: #e9f8f0;
        --aj-gold-500: #d8a43a;
        --aj-gold-300: #f1cf7a;
        --aj-gold-100: #fff6d9;
        --text: #101828;
        --muted: #667085;
        --border: #e8edf3;
        position: relative;
        min-height: 100vh;
        color: var(--text);
    }

    .aj-dashboard-v4 button,
    .aj-dashboard-v4 input,
    .aj-dashboard-v4 select {
        font: inherit;
    }

    .aj-dashboard-v4-layout {
        display: flex;
        min-height: 100vh;
    }

    .aj-dashboard-v4-sidebar {
        position: fixed;
        inset: 0 auto 0 0;
        width: 286px;
        padding: 28px 20px;
        color: #fff;
        background:
            radial-gradient(circle at 20% 10%, rgba(216, 164, 58, 0.22), transparent 25%),
            linear-gradient(180deg, #05172b 0%, #08213d 55%, #06192d 100%);
        overflow: hidden;
        z-index: 30;
    }

    .aj-dashboard-v4-sidebar::after {
        content: "";
        position: absolute;
        left: -80px;
        right: -80px;
        bottom: -120px;
        height: 310px;
        background:
            radial-gradient(circle at 30% 45%, rgba(216, 164, 58, 0.42) 0 3px, transparent 4px),
            radial-gradient(circle at 60% 25%, rgba(255, 143, 43, 0.30) 0 2px, transparent 3px),
            linear-gradient(150deg, rgba(18, 104, 179, 0.12), transparent 50%),
            radial-gradient(circle at 65% 62%, rgba(216, 164, 58, 0.34), transparent 18%);
        opacity: 0.9;
        pointer-events: none;
    }

    .aj-dashboard-v4-sidebar__brand {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .aj-dashboard-v4-sidebar__brand-badge {
        width: 42px;
        height: 42px;
        border: 1px solid rgba(241, 207, 122, 0.8);
        border-radius: 50%;
        display: grid;
        place-items: center;
        color: var(--aj-gold-300);
        box-shadow: 0 0 30px rgba(216, 164, 58, 0.24);
        flex: none;
    }

    .aj-dashboard-v4-sidebar__brand img {
        display: block;
        max-width: 152px;
        height: auto;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }

    .aj-dashboard-v4-sidebar__profile {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 30px 0 24px;
        padding: 14px;
        border: 1px solid rgba(255, 255, 255, 0.09);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.045);
        z-index: 1;
    }

    .aj-dashboard-v4-sidebar__avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: rgba(255, 255, 255, 0.12);
        font-weight: 800;
        color: #fff;
        flex: none;
    }

    .aj-dashboard-v4-sidebar__profile strong {
        display: block;
        font-size: 14px;
    }

    .aj-dashboard-v4-sidebar__profile span {
        display: block;
        margin-top: 3px;
        color: rgba(255, 255, 255, 0.62);
        font-size: 12px;
    }

    .aj-dashboard-v4-sidebar__nav-title {
        position: relative;
        z-index: 1;
        margin: 0 0 12px 4px;
        font-size: 11px;
        color: var(--aj-gold-300);
        font-weight: 800;
        letter-spacing: 1.2px;
        text-transform: uppercase;
    }

    .aj-dashboard-v4-sidebar__nav {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 7px;
    }

    .aj-dashboard-v4-sidebar__item,
    .aj-dashboard-v4-sidebar__subitem {
        display: flex;
        align-items: center;
        gap: 11px;
        width: 100%;
        border: 0;
        color: rgba(255, 255, 255, 0.86);
        background: transparent;
        border-radius: 15px;
        padding: 12px 13px;
        cursor: pointer;
        text-align: left;
        transition: 0.2s ease;
        text-decoration: none;
    }

    .aj-dashboard-v4-sidebar__item:hover,
    .aj-dashboard-v4-sidebar__subitem:hover {
        background: rgba(255, 255, 255, 0.07);
        color: #fff;
    }

    .aj-dashboard-v4-sidebar__item.is-active {
        background: linear-gradient(90deg, rgba(18, 104, 179, 0.96), rgba(8, 33, 61, 0.88));
        box-shadow: inset 3px 0 0 var(--aj-gold-500), 0 14px 30px rgba(3, 11, 24, 0.22);
        color: #fff;
    }

    .aj-dashboard-v4-sidebar__subnav {
        margin: 4px 0 8px 28px;
        padding-left: 14px;
        border-left: 2px solid rgba(216, 164, 58, 0.75);
        display: grid;
        gap: 4px;
    }

    .aj-dashboard-v4-sidebar__subitem {
        padding: 10px 12px;
        font-size: 13px;
    }

    .aj-dashboard-v4-sidebar__subitem.is-active {
        color: #fff;
        background: linear-gradient(90deg, rgba(244, 123, 32, 0.92), rgba(216, 164, 58, 0.80));
        box-shadow: 0 10px 28px rgba(244, 123, 32, 0.24);
    }

    .aj-dashboard-v4-sidebar__premium {
        position: relative;
        z-index: 1;
        margin-top: 32px;
        padding: 18px;
        border-radius: 22px;
        border: 1px solid rgba(216, 164, 58, 0.42);
        background: rgba(255, 255, 255, 0.055);
    }

    .aj-dashboard-v4-sidebar__premium h3 {
        margin: 0 0 8px;
        color: var(--aj-gold-300);
        font-size: 15px;
    }

    .aj-dashboard-v4-sidebar__premium p {
        margin: 0 0 14px;
        color: rgba(255, 255, 255, 0.70);
        font-size: 12px;
        line-height: 1.6;
    }

    .aj-dashboard-v4-sidebar__premium button {
        width: 100%;
        padding: 11px 14px;
        border: 0;
        border-radius: 14px;
        color: #111827;
        font-weight: 800;
        background: linear-gradient(135deg, var(--aj-gold-300), var(--aj-gold-500));
        cursor: pointer;
    }

    .aj-dashboard-v4-main {
        width: calc(100% - 286px);
        margin-left: 286px;
        padding: 28px 34px 38px;
    }

    .aj-dashboard-v4-topbar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 22px;
    }

    .aj-dashboard-v4-title h1 {
        margin: 0;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(30px, 3vw, 42px);
        letter-spacing: -0.8px;
        color: #0b1320;
    }

    .aj-dashboard-v4-breadcrumb {
        display: flex;
        gap: 9px;
        align-items: center;
        margin-top: 8px;
        font-size: 13px;
        color: var(--muted);
    }

    .aj-dashboard-v4-actions {
        display: grid;
        justify-items: end;
        gap: 14px;
        flex: 1;
    }

    .aj-dashboard-v4-row,
    .aj-dashboard-v4-row--controls {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        width: 100%;
    }

    .aj-dashboard-v4-search {
        position: relative;
        width: min(590px, 100%);
    }

    .aj-dashboard-v4-search input {
        width: 100%;
        height: 48px;
        padding: 0 18px 0 45px;
        border: 1px solid var(--border);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.78);
        outline: none;
        box-shadow: 0 8px 26px rgba(6, 25, 45, 0.06);
    }

    .aj-dashboard-v4-search i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #98a2b3;
    }

    .aj-dashboard-v4-icon-btn,
    .aj-dashboard-v4-control-btn,
    .aj-dashboard-v4-primary-btn {
        height: 44px;
        border: 1px solid var(--border);
        border-radius: 15px;
        background: #fff;
        color: var(--text);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 0 15px;
        box-shadow: 0 8px 26px rgba(6, 25, 45, 0.06);
        cursor: pointer;
        font-weight: 700;
        white-space: nowrap;
        text-decoration: none;
    }

    .aj-dashboard-v4-icon-btn {
        width: 44px;
        padding: 0;
        position: relative;
    }

    .aj-dashboard-v4-badge {
        position: absolute;
        top: -5px;
        right: -4px;
        width: 18px;
        height: 18px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        background: var(--aj-orange-600);
        color: white;
        font-size: 10px;
        font-weight: 900;
    }

    .aj-dashboard-v4-primary-btn {
        border-color: rgba(216, 164, 58, 0.55);
        color: white;
        background: linear-gradient(135deg, var(--aj-blue-950), var(--aj-blue-800));
    }

    .aj-dashboard-v4-user {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 800;
        font-size: 13px;
        white-space: nowrap;
    }

    .aj-dashboard-v4-user-photo {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0b315c, #d8a43a);
        display: grid;
        place-items: center;
        color: #fff;
        font-weight: 900;
        border: 3px solid #fff;
        box-shadow: 0 8px 26px rgba(6, 25, 45, 0.06);
    }

    .aj-dashboard-v4-hero {
        position: relative;
        min-height: 138px;
        border-radius: 28px;
        overflow: hidden;
        padding: 34px 38px;
        color: #fff;
        background:
            linear-gradient(90deg, rgba(6, 25, 45, 0.98) 0%, rgba(8, 33, 61, 0.92) 38%, rgba(6, 25, 45, 0.42) 100%),
            radial-gradient(circle at 82% 34%, rgba(255, 143, 43, 0.70), transparent 22%),
            linear-gradient(135deg, #08213d, #1268b3 55%, #f47b20);
        box-shadow: 0 16px 50px rgba(6, 25, 45, 0.10);
    }

    .aj-dashboard-v4-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 88% 35%, rgba(255, 255, 255, 0.22), transparent 3%),
            linear-gradient(160deg, transparent 0 55%, rgba(255, 255, 255, 0.14) 56%, transparent 57%),
            linear-gradient(14deg, transparent 0 70%, rgba(216, 164, 58, 0.35) 72%, transparent 73%);
        opacity: 0.8;
    }

    .aj-dashboard-v4-hero::after {
        content: "☼";
        position: absolute;
        right: 32px;
        top: 25px;
        font-size: 78px;
        color: rgba(216, 164, 58, 0.35);
        font-family: Georgia, serif;
    }

    .aj-dashboard-v4-hero__content {
        position: relative;
        z-index: 1;
        max-width: 660px;
    }

    .aj-dashboard-v4-hero__logo {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.10);
        border: 1px solid rgba(255, 255, 255, 0.16);
        margin-bottom: 14px;
    }

    .aj-dashboard-v4-hero__logo img {
        display: block;
        height: 30px;
        width: auto;
        max-width: 180px;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }

    .aj-dashboard-v4-hero h2 {
        margin: 0 0 8px;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(24px, 2vw, 33px);
        font-weight: 500;
    }

    .aj-dashboard-v4-hero p {
        margin: 0;
        color: rgba(255, 255, 255, 0.82);
        font-size: 15px;
    }

    .aj-dashboard-v4-hero__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    .aj-dashboard-v4-hero__btn {
        border: 0;
        border-radius: 14px;
        padding: 11px 14px;
        font-size: 13px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .aj-dashboard-v4-hero__btn:hover {
        transform: translateY(-1px);
    }

    .aj-dashboard-v4-hero__btn.is-primary {
        background: #fff;
        color: #0b4f8a;
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.12);
    }

    .aj-dashboard-v4-hero__btn.is-secondary {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .aj-dashboard-v4-hero__side {
        display: grid;
        gap: 12px;
        position: relative;
        z-index: 1;
    }

    .aj-dashboard-v4-hero__stat {
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.16);
        padding: 16px;
        backdrop-filter: blur(16px);
    }

    .aj-dashboard-v4-hero__stat-label {
        margin: 0;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.78;
        font-weight: 800;
    }

    .aj-dashboard-v4-hero__stat-value {
        margin: 8px 0 0;
        font-size: 34px;
        font-weight: 900;
        line-height: 1;
    }

    .aj-dashboard-v4-hero__stat-meta {
        margin: 8px 0 0;
        color: rgba(255, 255, 255, 0.82);
        font-size: 13px;
        line-height: 1.6;
    }

    .aj-dashboard-v4-kpis,
    .aj-dashboard-v4-grid {
        display: grid;
        gap: 18px;
    }

    .aj-dashboard-v4-kpis {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin: 22px 0;
    }

    .aj-dashboard-v4-card {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(232, 237, 243, 0.92);
        border-radius: 22px;
        box-shadow: 0 8px 26px rgba(6, 25, 45, 0.06);
    }

    .aj-dashboard-v4-kpi {
        min-height: 146px;
        padding: 20px;
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 16px;
        align-items: center;
    }

    .aj-dashboard-v4-kpi__icon {
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        border-radius: 18px;
        color: #fff;
        flex: 0 0 auto;
        font-size: 24px;
    }

    .aj-dashboard-v4-kpi__icon.blue { background: linear-gradient(135deg, var(--aj-blue-900), var(--aj-blue-600)); }
    .aj-dashboard-v4-kpi__icon.orange { background: linear-gradient(135deg, var(--aj-orange-600), #ffb34d); }
    .aj-dashboard-v4-kpi__icon.green { background: linear-gradient(135deg, var(--aj-green-600), #65c48e); }
    .aj-dashboard-v4-kpi__icon.gold { background: linear-gradient(135deg, #bd7a00, var(--aj-gold-300)); }

    .aj-dashboard-v4-kpi small {
        display: block;
        color: var(--muted);
        font-size: 13px;
    }

    .aj-dashboard-v4-kpi strong {
        display: block;
        margin: 7px 0 12px;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 27px;
        font-weight: 500;
        color: #0b1320;
    }

    .aj-dashboard-v4-trend {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: var(--aj-green-600);
        font-weight: 800;
        font-size: 12px;
    }

    .aj-dashboard-v4-sparkline {
        margin-top: 2px;
        width: 100%;
        height: 32px;
    }

    .aj-dashboard-v4-grid--top {
        grid-template-columns: 1.6fr 0.85fr 0.75fr;
        align-items: stretch;
    }

    .aj-dashboard-v4-grid--bottom {
        grid-template-columns: 1.35fr 1.05fr 1fr;
        margin-top: 18px;
    }

    .aj-dashboard-v4-panel {
        padding: 22px;
    }

    .aj-dashboard-v4-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
    }

    .aj-dashboard-v4-panel__title {
        margin: 0;
        font-size: 18px;
        font-weight: 900;
        letter-spacing: -0.2px;
        color: #0b1320;
    }

    .aj-dashboard-v4-panel__subtitle {
        margin: 6px 0 0;
        color: var(--muted);
        font-size: 13px;
    }

    .aj-dashboard-v4-controls {
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
        align-items: center;
    }

    .aj-dashboard-v4-select,
    .aj-dashboard-v4-chip {
        border: 1px solid var(--border);
        background: #fff;
        border-radius: 12px;
        color: #344054;
        padding: 9px 12px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
    }

    .aj-dashboard-v4-chart {
        height: 250px;
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.90), rgba(255, 248, 238, 0.35));
        overflow: hidden;
        padding: 12px 14px;
    }

    .aj-dashboard-v4-destination {
        min-height: 322px;
    }

    .aj-dashboard-v4-donut-wrap {
        display: grid;
        grid-template-columns: 170px 1fr;
        gap: 18px;
        align-items: center;
    }

    .aj-dashboard-v4-donut {
        width: 162px;
        height: 162px;
        border-radius: 50%;
        background: conic-gradient(#0b315c 0 28%, #8ecae6 28% 52%, #19a463 52% 70%, #f47b20 70% 84%, #d1d5db 84% 100%);
        position: relative;
        box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.04);
    }

    .aj-dashboard-v4-donut::after {
        content: "342\A Réservations";
        white-space: pre;
        position: absolute;
        inset: 34px;
        border-radius: 50%;
        background: #fff;
        display: grid;
        place-items: center;
        text-align: center;
        font-family: Georgia, serif;
        font-size: 27px;
        line-height: 1.1;
        color: #0b1320;
        box-shadow: inset 0 0 0 1px var(--border);
    }

    .aj-dashboard-v4-legend {
        display: grid;
        gap: 12px;
        font-size: 13px;
    }

    .aj-dashboard-v4-legend__row {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        color: #344054;
    }

    .aj-dashboard-v4-legend__name {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
    }

    .aj-dashboard-v4-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--aj-blue-800);
    }

    .aj-dashboard-v4-dot.sky { background: #8ecae6; }
    .aj-dashboard-v4-dot.green { background: var(--aj-green-600); }
    .aj-dashboard-v4-dot.orange { background: var(--aj-orange-500); }
    .aj-dashboard-v4-dot.gray { background: #d1d5db; }

    .aj-dashboard-v4-gauge {
        margin: 18px auto 8px;
        width: min(240px, 100%);
        height: 140px;
        position: relative;
    }

    .aj-dashboard-v4-gauge svg {
        width: 100%;
        height: 100%;
        overflow: visible;
    }

    .aj-dashboard-v4-gauge__center {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 3px;
        text-align: center;
    }

    .aj-dashboard-v4-gauge__center strong {
        display: block;
        font-family: Georgia, serif;
        font-size: 45px;
        font-weight: 500;
    }

    .aj-dashboard-v4-gauge__center span {
        color: var(--muted);
        font-weight: 700;
    }

    .aj-dashboard-v4-table-card,
    .aj-dashboard-v4-list-card,
    .aj-dashboard-v4-alerts-card {
        overflow: hidden;
    }

    .aj-dashboard-v4-table-scroll {
        overflow-x: auto;
    }

    .aj-dashboard-v4-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        min-width: 640px;
    }

    .aj-dashboard-v4-table thead th {
        padding: 12px 10px;
        background: #f8fafc;
        color: #667085;
        font-size: 11px;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .aj-dashboard-v4-table tbody td {
        padding: 12px 10px;
        border-top: 1px solid #eef2f6;
        color: #344054;
    }

    .aj-dashboard-v4-table tbody tr:hover td {
        background: #fcfdff;
    }

    .aj-dashboard-v4-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 900;
    }

    .aj-dashboard-v4-status.ok { color: #087443; background: #e7f8ef; }
    .aj-dashboard-v4-status.low { color: #b54708; background: #fff2d7; }
    .aj-dashboard-v4-status.full { color: #b42318; background: #fee4e2; }
    .aj-dashboard-v4-status.wait { color: #c25a00; background: #fff1e5; }

    .aj-dashboard-v4-link {
        color: var(--aj-orange-600);
        text-decoration: none;
        font-weight: 900;
        font-size: 13px;
        display: inline-flex;
        gap: 8px;
        align-items: center;
    }

    .aj-dashboard-v4-list {
        display: grid;
        gap: 12px;
    }

    .aj-dashboard-v4-reservation,
    .aj-dashboard-v4-alert {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 12px;
        align-items: center;
        padding: 11px 0;
        border-bottom: 1px solid #eef2f6;
    }

    .aj-dashboard-v4-reservation:last-child,
    .aj-dashboard-v4-alert:last-child {
        border-bottom: 0;
    }

    .aj-dashboard-v4-mini-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        color: #fff;
        font-size: 12px;
        font-weight: 900;
        background: linear-gradient(135deg, var(--aj-blue-800), var(--aj-gold-500));
    }

    .aj-dashboard-v4-item strong {
        display: block;
        font-size: 13px;
        color: #111827;
    }

    .aj-dashboard-v4-item span,
    .aj-dashboard-v4-amount small,
    .aj-dashboard-v4-alert-time {
        display: block;
        margin-top: 3px;
        color: var(--muted);
        font-size: 12px;
    }

    .aj-dashboard-v4-amount {
        text-align: right;
        font-weight: 900;
        color: #111827;
        font-size: 13px;
        white-space: nowrap;
    }

    .aj-dashboard-v4-alert-icon {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: var(--aj-green-100);
        color: var(--aj-green-600);
        font-weight: 900;
    }

    .aj-dashboard-v4-alert-icon.warning {
        color: var(--aj-orange-600);
        background: var(--aj-orange-100);
    }

    .aj-dashboard-v4-progress {
        display: grid;
        gap: 8px;
    }

    .aj-dashboard-v4-progress__bar {
        height: 10px;
        background: #edf2f8;
        border-radius: 999px;
        overflow: hidden;
    }

    .aj-dashboard-v4-progress__fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #0f6fb5 0%, #19a463 100%);
    }

    .aj-dashboard-v4-objective {
        padding: 18px;
        border-radius: 22px;
        background: linear-gradient(135deg, #0f6fb5 0%, #0c5f9b 56%, #084c87 100%);
        color: #fff;
    }

    .aj-dashboard-v4-objective strong {
        font-size: 34px;
        line-height: 1;
        font-weight: 900;
    }

    .aj-dashboard-v4-objective__bar {
        margin-top: 16px;
        height: 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        overflow: hidden;
    }

    .aj-dashboard-v4-objective__fill {
        width: <?php echo e(max(35, min(95, $confirmedRatio))); ?>%;
        height: 100%;
        border-radius: inherit;
        background: #f47b20;
    }

    .aj-dashboard-v4-objective__note {
        margin-top: 12px;
        color: rgba(255, 255, 255, 0.82);
        font-size: 13px;
        line-height: 1.6;
    }

    .aj-dashboard-v4-footer-actions {
        position: sticky;
        bottom: 18px;
        z-index: 10;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        pointer-events: none;
        margin-top: 18px;
    }

    .aj-dashboard-v4-footer-actions a {
        pointer-events: auto;
    }

    .aj-dashboard-v4-floating {
        border-radius: 999px;
        padding: 12px 18px;
        border: 0;
        background: #0f6fb5;
        color: #fff;
        font-weight: 900;
        box-shadow: 0 16px 30px rgba(15, 111, 181, 0.25);
        text-decoration: none;
    }

    @media (max-width: 1420px) {
        .aj-dashboard-v4-grid--top,
        .aj-dashboard-v4-grid--bottom {
            grid-template-columns: 1fr 1fr;
        }

        .aj-dashboard-v4-hero,
        .aj-dashboard-v4-alerts-card {
            grid-column: span 2;
        }
    }

    @media (max-width: 1180px) {
        .aj-dashboard-v4-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .aj-dashboard-v4-topbar {
            flex-direction: column;
        }

        .aj-dashboard-v4-actions {
            width: 100%;
            justify-items: stretch;
        }

        .aj-dashboard-v4-row,
        .aj-dashboard-v4-row--controls {
            justify-content: flex-start;
            flex-wrap: wrap;
        }
    }

    @media (max-width: 980px) {
        .aj-dashboard-v4-sidebar {
            transform: translateX(-100%);
        }

        .aj-dashboard-v4-main {
            width: 100%;
            margin-left: 0;
            padding: 18px 18px 28px;
        }

        .aj-dashboard-v4-grid--top,
        .aj-dashboard-v4-grid--bottom {
            grid-template-columns: 1fr;
        }

        .aj-dashboard-v4-hero {
            padding: 28px 24px;
        }

        .aj-dashboard-v4-donut-wrap {
            grid-template-columns: 1fr;
            justify-items: center;
        }

        .aj-dashboard-v4-hero,
        .aj-dashboard-v4-alerts-card {
            grid-column: auto;
        }
    }

    @media (max-width: 660px) {
        .aj-dashboard-v4-kpis {
            grid-template-columns: 1fr;
        }

        .aj-dashboard-v4-kpi {
            grid-template-columns: 1fr;
        }

        .aj-dashboard-v4-control-btn,
        .aj-dashboard-v4-primary-btn,
        .aj-dashboard-v4-select,
        .aj-dashboard-v4-chip {
            width: 100%;
        }

        .aj-dashboard-v4-search {
            width: 100%;
        }

        .aj-dashboard-v4-user span {
            display: none;
        }

        .aj-dashboard-v4-panel {
            padding: 18px;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="aj-dashboard-v4">
    <div class="aj-dashboard-v4-layout">
        <aside class="aj-dashboard-v4-sidebar" aria-label="Navigation principale">
            <div class="aj-dashboard-v4-sidebar__brand">
                <div class="aj-dashboard-v4-sidebar__brand-badge">☼</div>
                <img src="<?php echo e($dashboardBrandLogoEscaped); ?>" alt="<?php echo e($dashboardBrandName); ?>">
            </div>

            <div class="aj-dashboard-v4-sidebar__profile">
                <div class="aj-dashboard-v4-sidebar__avatar"><?php echo e($dashboardInitials); ?></div>
                <div>
                    <strong><?php echo e($dashboardUserName); ?></strong>
                    <span><?php echo e($dashboardUserRole); ?></span>
                </div>
                <span style="margin-left:auto;color:var(--aj-gold-300);">⌄</span>
            </div>

            <p class="aj-dashboard-v4-sidebar__nav-title">Navigation</p>
            <nav class="aj-dashboard-v4-sidebar__nav">
                <a href="<?php echo e(route('admin.dashboard.v4')); ?>" class="aj-dashboard-v4-sidebar__item is-active">
                    <span>⌂</span>
                    Tableau de bord
                    <span style="margin-left:auto;color:var(--aj-gold-300);">⌃</span>
                </a>
                <div class="aj-dashboard-v4-sidebar__subnav">
                    <a href="<?php echo e(route('admin.dashboard.vue-globale')); ?>" class="aj-dashboard-v4-sidebar__subitem <?php echo e($dashboardRouteIs('admin.dashboard.vue-globale') ? 'is-active' : ''); ?>">Vue d'ensemble</a>
                    <a href="<?php echo e(route('admin.dashboard.statistiques')); ?>" class="aj-dashboard-v4-sidebar__subitem <?php echo e($dashboardRouteIs('admin.dashboard.statistiques') ? 'is-active' : ''); ?>">Statistiques</a>
                    <a href="<?php echo e(route('admin.dashboard.alertes')); ?>" class="aj-dashboard-v4-sidebar__subitem <?php echo e($dashboardRouteIs('admin.dashboard.alertes') ? 'is-active' : ''); ?>">Alertes</a>
                    <a href="<?php echo e(route('admin.dashboard.v2')); ?>" class="aj-dashboard-v4-sidebar__subitem <?php echo e($dashboardRouteIs('admin.dashboard.v2') ? 'is-active' : ''); ?>">Dashboard V2</a>
                    <a href="<?php echo e(route('admin.dashboard.v3')); ?>" class="aj-dashboard-v4-sidebar__subitem <?php echo e($dashboardRouteIs('admin.dashboard.v3') ? 'is-active' : ''); ?>">Dashboard V3</a>
                    <a href="<?php echo e(route('admin.dashboard.v4')); ?>" class="aj-dashboard-v4-sidebar__subitem <?php echo e($dashboardRouteIs('admin.dashboard.v4') ? 'is-active' : ''); ?>">Dashboard V4</a>
                </div>

                <a href="<?php echo e(route('admin.reservations.workspace')); ?>" class="aj-dashboard-v4-sidebar__item">
                    <span>▣</span>
                    Réservations
                    <span style="margin-left:auto;color:var(--aj-gold-300);">⌄</span>
                </a>
                <a href="#" class="aj-dashboard-v4-sidebar__item"><span>✈</span> Départs <span style="margin-left:auto;color:var(--aj-gold-300);">⌄</span></a>
                <a href="#" class="aj-dashboard-v4-sidebar__item"><span>♙</span> Clients <span style="margin-left:auto;color:var(--aj-gold-300);">⌄</span></a>
                <a href="#" class="aj-dashboard-v4-sidebar__item"><span>⌖</span> Destinations</a>
                <a href="#" class="aj-dashboard-v4-sidebar__item"><span>▤</span> Rapports <span style="margin-left:auto;color:var(--aj-gold-300);">⌄</span></a>
                <a href="#" class="aj-dashboard-v4-sidebar__item"><span>⚙</span> Paramètres <span style="margin-left:auto;color:var(--aj-gold-300);">⌄</span></a>
            </nav>

            <div class="aj-dashboard-v4-sidebar__premium">
                <h3>♛ Service Premium</h3>
                <p>Accédez aux analyses avancées, au suivi commercial et au pilotage opérationnel.</p>
                <button type="button">Découvrir</button>
            </div>
        </aside>

        <main class="aj-dashboard-v4-main">
            <header class="aj-dashboard-v4-topbar">
                <div class="aj-dashboard-v4-title">
                    <h1>Tableau de bord</h1>
                    <div class="aj-dashboard-v4-breadcrumb">
                        <span>Accueil</span><span>›</span><span>Tableau de bord</span><span>›</span><strong>Dashboard V4</strong>
                    </div>
                </div>

                <div class="aj-dashboard-v4-actions">
                    <div class="aj-dashboard-v4-row">
                        <label class="aj-dashboard-v4-search">
                            <i class="bx bx-search"></i>
                            <input type="search" placeholder="Rechercher (réservations, clients, départs...)" />
                        </label>
                        <button type="button" class="aj-dashboard-v4-icon-btn" aria-label="Notifications">
                            <i class="bx bx-bell"></i>
                            <span class="aj-dashboard-v4-badge">3</span>
                        </button>
                        <div class="aj-dashboard-v4-user">
                            <div class="aj-dashboard-v4-user-photo"><?php echo e($dashboardInitials); ?></div>
                            <span><?php echo e($dashboardUserName); ?></span>
                            <span>⌄</span>
                        </div>
                    </div>
                    <div class="aj-dashboard-v4-row--controls">
                        <button type="button" class="aj-dashboard-v4-control-btn">📅 18 mai – 24 mai 2025 ⌄</button>
                        <button type="button" class="aj-dashboard-v4-control-btn">☷ Filtres ⌄</button>
                        <a href="<?php echo e(route('admin.reservations.create')); ?>" class="aj-dashboard-v4-primary-btn">＋ Nouvelle réservation</a>
                    </div>
                </div>
            </header>

            <section class="aj-dashboard-v4-hero">
                <div class="aj-dashboard-v4-hero__content">
                    <div class="aj-dashboard-v4-hero__logo">
                        <img src="<?php echo e($dashboardBrandLogoEscaped); ?>" alt="<?php echo e($dashboardBrandName); ?>">
                    </div>
                    <h2>Admin / Dashboard V4</h2>
                    <p>Vue commerciale, pilotage des départs et suivi des réservations</p>
                    <div class="aj-dashboard-v4-hero__actions">
                        <a href="<?php echo e(route('admin.reservations.workspace')); ?>" class="aj-dashboard-v4-hero__btn is-primary">Ouvrir le workspace</a>
                        <a href="<?php echo e(route('admin.dashboard.v3')); ?>" class="aj-dashboard-v4-hero__btn is-secondary">Voir Dashboard V3</a>
                    </div>
                </div>
            </section>

            <section class="aj-dashboard-v4-kpis" aria-label="Indicateurs principaux">
                <article class="aj-dashboard-v4-card aj-dashboard-v4-kpi">
                    <div class="aj-dashboard-v4-kpi__icon blue"><i class="bx bx-briefcase-alt-2"></i></div>
                    <div>
                        <small>Chiffre d'affaires</small>
                        <strong><?php echo e(number_format((float) $revenueTotal, 0, ',', ' ')); ?> DH</strong>
                        <span class="aj-dashboard-v4-trend">↗ <?php echo e(number_format($monthEvolution, 1, ',', ' ')); ?>% vs semaine précédente</span>
                        <svg class="aj-dashboard-v4-sparkline" viewBox="0 0 160 36" fill="none"><path d="M4 28 C22 24 24 16 40 20 C56 24 58 5 78 12 C96 19 102 2 119 9 C135 16 139 22 156 12" stroke="#0f4f8f" stroke-width="3"/><path d="M4 28 C22 24 24 16 40 20 C56 24 58 5 78 12 C96 19 102 2 119 9 C135 16 139 22 156 12 L156 36 L4 36 Z" fill="#0f4f8f" opacity="0.10"/></svg>
                    </div>
                </article>

                <article class="aj-dashboard-v4-card aj-dashboard-v4-kpi">
                    <div class="aj-dashboard-v4-kpi__icon orange"><i class="bx bx-layer"></i></div>
                    <div>
                        <small>Réservations</small>
                        <strong><?php echo e(number_format($reservationsTotal, 0, ',', ' ')); ?></strong>
                        <span class="aj-dashboard-v4-trend">↗ 12,4% vs semaine précédente</span>
                        <svg class="aj-dashboard-v4-sparkline" viewBox="0 0 160 36" fill="none"><path d="M4 27 C25 27 28 23 42 25 C58 27 59 12 75 16 C88 20 94 8 108 10 C124 12 123 26 138 24 C148 23 151 16 156 18" stroke="#f47b20" stroke-width="3"/><path d="M4 27 C25 27 28 23 42 25 C58 27 59 12 75 16 C88 20 94 8 108 10 C124 12 123 26 138 24 C148 23 151 16 156 18 L156 36 L4 36 Z" fill="#f47b20" opacity="0.10"/></svg>
                    </div>
                </article>

                <article class="aj-dashboard-v4-card aj-dashboard-v4-kpi">
                    <div class="aj-dashboard-v4-kpi__icon green"><i class="bx bx-plane-alt"></i></div>
                    <div>
                        <small>Départs actifs</small>
                        <strong><?php echo e(number_format($departuresActive, 0, ',', ' ')); ?></strong>
                        <span class="aj-dashboard-v4-trend">↗ 8,1% vs semaine précédente</span>
                        <svg class="aj-dashboard-v4-sparkline" viewBox="0 0 160 36" fill="none"><path d="M4 29 C19 26 24 22 36 24 C50 26 54 15 68 16 C82 17 87 8 100 12 C116 17 119 4 132 7 C144 10 149 18 156 15" stroke="#19a463" stroke-width="3"/><path d="M4 29 C19 26 24 22 36 24 C50 26 54 15 68 16 C82 17 87 8 100 12 C116 17 119 4 132 7 C144 10 149 18 156 15 L156 36 L4 36 Z" fill="#19a463" opacity="0.10"/></svg>
                    </div>
                </article>

                <article class="aj-dashboard-v4-card aj-dashboard-v4-kpi">
                    <div class="aj-dashboard-v4-kpi__icon gold"><i class="bx bx-group"></i></div>
                    <div>
                        <small>Agents actifs</small>
                        <strong><?php echo e(number_format($agentsActive, 0, ',', ' ')); ?></strong>
                        <span class="aj-dashboard-v4-trend">↗ 15,7% vs semaine précédente</span>
                        <svg class="aj-dashboard-v4-sparkline" viewBox="0 0 160 36" fill="none"><path d="M4 28 C22 17 31 23 45 20 C60 18 60 28 76 23 C91 18 94 5 110 9 C125 13 128 25 142 21 C151 19 153 14 156 15" stroke="#d8a43a" stroke-width="3"/><path d="M4 28 C22 17 31 23 45 20 C60 18 60 28 76 23 C91 18 94 5 110 9 C125 13 128 25 142 21 C151 19 153 14 156 15 L156 36 L4 36 Z" fill="#d8a43a" opacity="0.13"/></svg>
                    </div>
                </article>
            </section>

            <section class="aj-dashboard-v4-grid aj-dashboard-v4-grid--top">
                <article class="aj-dashboard-v4-card aj-dashboard-v4-panel">
                    <div class="aj-dashboard-v4-panel__head">
                        <div>
                            <h2 class="aj-dashboard-v4-panel__title">Performance commerciale</h2>
                            <p class="aj-dashboard-v4-panel__subtitle">CA et volume de réservations par mois</p>
                        </div>
                        <div class="aj-dashboard-v4-controls">
                            <select class="aj-dashboard-v4-select"><option>Chiffre d'affaires</option></select>
                            <select class="aj-dashboard-v4-select"><option>7 derniers jours</option></select>
                        </div>
                    </div>

                    <div class="aj-dashboard-v4-chart">
                        <svg viewBox="0 0 760 250" width="100%" height="100%" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="aj-dashboard-v4-orange-fill" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#f47b20" stop-opacity="0.28" />
                                    <stop offset="100%" stop-color="#f47b20" stop-opacity="0.02" />
                                </linearGradient>
                            </defs>
                            <g stroke="#e8edf3" stroke-width="1">
                                <line x1="70" y1="35" x2="730" y2="35"/><line x1="70" y1="82" x2="730" y2="82"/><line x1="70" y1="129" x2="730" y2="129"/><line x1="70" y1="176" x2="730" y2="176"/><line x1="70" y1="220" x2="730" y2="220"/>
                            </g>
                            <g fill="#667085" font-size="12">
                                <text x="20" y="39">40M</text><text x="20" y="86">30M</text><text x="20" y="133">20M</text><text x="20" y="180">10M</text><text x="30" y="224">0</text>
                                <text x="70" y="242">Jan</text><text x="178" y="242">Fev</text><text x="286" y="242">Mar</text><text x="394" y="242">Avr</text><text x="502" y="242">Mai</text><text x="610" y="242">Juin</text><text x="705" y="242">Juil</text>
                            </g>
                            <path d="M70 178 C130 154 152 120 188 128 C235 138 254 168 296 156 C342 142 365 95 408 92 C452 88 485 45 520 52 C568 62 585 129 632 128 C674 127 693 105 730 94 L730 220 L70 220 Z" fill="url(#aj-dashboard-v4-orange-fill)"/>
                            <path d="M70 178 C130 154 152 120 188 128 C235 138 254 168 296 156 C342 142 365 95 408 92 C452 88 485 45 520 52 C568 62 585 129 632 128 C674 127 693 105 730 94" fill="none" stroke="#f59e0b" stroke-width="4" stroke-linecap="round"/>
                            <circle cx="520" cy="52" r="7" fill="#d8a43a" stroke="#fff" stroke-width="3"/>
                            <rect x="532" y="28" width="82" height="28" rx="8" fill="#06192d"/>
                            <text x="545" y="47" fill="#fff" font-size="12" font-weight="700">34,6 M</text>
                        </svg>
                    </div>
                </article>

                <article class="aj-dashboard-v4-card aj-dashboard-v4-panel aj-dashboard-v4-destination">
                    <div class="aj-dashboard-v4-panel__head">
                        <div>
                            <h2 class="aj-dashboard-v4-panel__title">Réservations par destination</h2>
                        </div>
                    </div>
                    <div class="aj-dashboard-v4-donut-wrap">
                        <div class="aj-dashboard-v4-donut" aria-hidden="true"></div>
                        <div class="aj-dashboard-v4-legend">
                            <div class="aj-dashboard-v4-legend__row"><span class="aj-dashboard-v4-legend__name"><i class="aj-dashboard-v4-dot"></i>Dakhla</span><strong>28%</strong></div>
                            <div class="aj-dashboard-v4-legend__row"><span class="aj-dashboard-v4-legend__name"><i class="aj-dashboard-v4-dot sky"></i>Istanbul</span><strong>24%</strong></div>
                            <div class="aj-dashboard-v4-legend__row"><span class="aj-dashboard-v4-legend__name"><i class="aj-dashboard-v4-dot green"></i>Marrakech</span><strong>18%</strong></div>
                            <div class="aj-dashboard-v4-legend__row"><span class="aj-dashboard-v4-legend__name"><i class="aj-dashboard-v4-dot orange"></i>Omra</span><strong>14%</strong></div>
                            <div class="aj-dashboard-v4-legend__row"><span class="aj-dashboard-v4-legend__name"><i class="aj-dashboard-v4-dot gray"></i>Autres</span><strong>16%</strong></div>
                        </div>
                    </div>
                    <a href="#" class="aj-dashboard-v4-link" style="margin-top:20px;">Voir le détail des destinations →</a>
                </article>

                <article class="aj-dashboard-v4-card aj-dashboard-v4-panel">
                    <div class="aj-dashboard-v4-panel__head">
                        <div>
                            <h2 class="aj-dashboard-v4-panel__title">Taux de confirmation</h2>
                        </div>
                    </div>
                    <div class="aj-dashboard-v4-gauge">
                        <svg viewBox="0 0 260 150">
                            <path d="M35 125 A95 95 0 0 1 225 125" fill="none" stroke="#e6e8eb" stroke-width="18" stroke-linecap="round" />
                            <path d="M35 125 A95 95 0 0 1 207 72" fill="none" stroke="url(#aj-dashboard-v4-gauge-grad)" stroke-width="18" stroke-linecap="round" />
                            <defs>
                                <linearGradient id="aj-dashboard-v4-gauge-grad" x1="0" x2="1" y1="0" y2="0">
                                    <stop offset="0%" stop-color="#d8a43a" />
                                    <stop offset="70%" stop-color="#f59e0b" />
                                    <stop offset="100%" stop-color="#19a463" />
                                </linearGradient>
                            </defs>
                            <circle cx="207" cy="72" r="8" fill="#d8a43a" />
                        </svg>
                        <div class="aj-dashboard-v4-gauge__center"><strong><?php echo e($confirmedRatio); ?>%</strong><span>Confirmées</span></div>
                    </div>
                    <div class="aj-dashboard-v4-trend" style="justify-content:center;margin-top:18px;">↗ 6% vs semaine précédente</div>
                </article>
            </section>

            <section class="aj-dashboard-v4-grid aj-dashboard-v4-grid--bottom">
                <article class="aj-dashboard-v4-card aj-dashboard-v4-panel aj-dashboard-v4-table-card">
                    <div class="aj-dashboard-v4-panel__head"><h2 class="aj-dashboard-v4-panel__title">✈ Départs à venir</h2></div>
                    <div class="aj-dashboard-v4-table-scroll">
                        <table class="aj-dashboard-v4-table">
                            <thead>
                                <tr><th>Date</th><th>Destination</th><th>Circuit</th><th>Places dispo.</th><th>Statut</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>20 mai 2025</td><td>Zanzibar</td><td>Évasion à Zanzibar</td><td>8 / 20</td><td><span class="aj-dashboard-v4-status ok">Disponible</span></td></tr>
                                <tr><td>22 mai 2025</td><td>Maurice</td><td>Luxe & Détente</td><td>3 / 25</td><td><span class="aj-dashboard-v4-status low">Faible</span></td></tr>
                                <tr><td>25 mai 2025</td><td>Sénégal</td><td>Terre de Teranga</td><td>0 / 20</td><td><span class="aj-dashboard-v4-status full">Complet</span></td></tr>
                                <tr><td>28 mai 2025</td><td>Maroc</td><td>Trésors du Maroc</td><td>5 / 18</td><td><span class="aj-dashboard-v4-status ok">Disponible</span></td></tr>
                                <tr><td>30 mai 2025</td><td>Égypte</td><td>Merveilles d'Égypte</td><td>6 / 22</td><td><span class="aj-dashboard-v4-status ok">Disponible</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align:center;margin-top:18px;"><a href="#" class="aj-dashboard-v4-link">Voir tous les départs →</a></div>
                </article>

                <article class="aj-dashboard-v4-card aj-dashboard-v4-panel aj-dashboard-v4-list-card">
                    <div class="aj-dashboard-v4-panel__head">
                        <h2 class="aj-dashboard-v4-panel__title">▦ Réservations récentes</h2>
                        <a href="#" class="aj-dashboard-v4-link">Voir tout</a>
                    </div>
                    <div class="aj-dashboard-v4-list">
                        <?php $__currentLoopData = $recentReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="aj-dashboard-v4-reservation">
                                <div class="aj-dashboard-v4-mini-avatar"><?php echo e(mb_strtoupper(mb_substr($reservation['client'], 0, 2))); ?></div>
                                <div class="aj-dashboard-v4-item">
                                    <strong><?php echo e($reservation['client']); ?></strong>
                                    <span><?php echo e($reservation['trip']); ?> • <?php echo e($reservation['agent']); ?></span>
                                </div>
                                <div class="aj-dashboard-v4-amount"><?php echo e($reservation['amount']); ?><small class="aj-dashboard-v4-status <?php echo e($dashboardRouteIs('admin.dashboard.v4') ? 'ok' : 'wait'); ?>"><?php echo e($reservation['status']); ?></small></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </article>

                <article class="aj-dashboard-v4-card aj-dashboard-v4-panel aj-dashboard-v4-alerts-card">
                    <div class="aj-dashboard-v4-panel__head">
                        <h2 class="aj-dashboard-v4-panel__title">♙ Alertes & tâches</h2>
                        <a href="#" class="aj-dashboard-v4-link">Voir tout</a>
                    </div>
                    <div class="aj-dashboard-v4-list">
                        <?php $__currentLoopData = $operationalAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="aj-dashboard-v4-alert">
                                <div class="aj-dashboard-v4-alert-icon <?php echo e($alert['tone'] === 'warn' ? 'warning' : ''); ?>"><i class="<?php echo e($alert['icon']); ?>"></i></div>
                                <div class="aj-dashboard-v4-item">
                                    <strong><?php echo e($alert['label']); ?></strong>
                                    <span>Suivi du pilotage quotidien</span>
                                </div>
                                <div class="aj-dashboard-v4-alert-time"><?php echo e($alert['value']); ?></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="aj-dashboard-v4-objective" style="margin-top:16px;">
                        <p style="margin:0;font-size:12px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:rgba(255,255,255,0.78);">Objectif mensuel</p>
                        <div style="margin-top:10px;display:flex;align-items:end;gap:10px;">
                            <strong><?php echo e($confirmedRatio); ?>%</strong>
                            <span style="padding-bottom:4px;color:rgba(255,255,255,0.78);font-size:13px;font-weight:700;">de confirmation</span>
                        </div>
                        <div class="aj-dashboard-v4-objective__bar"><div class="aj-dashboard-v4-objective__fill"></div></div>
                        <p class="aj-dashboard-v4-objective__note">Encore 186 000 DH pour atteindre l’objectif fixé sur le mois en cours.</p>
                    </div>
                </article>
            </section>

            <section class="aj-dashboard-v4-grid aj-dashboard-v4-grid--top" style="margin-top:18px;">
                <article class="aj-dashboard-v4-card aj-dashboard-v4-panel aj-dashboard-v4-list-card">
                    <div class="aj-dashboard-v4-panel__head">
                        <div>
                            <h2 class="aj-dashboard-v4-panel__title">Ventes par canal</h2>
                            <p class="aj-dashboard-v4-panel__subtitle">Agence, commercial, client web</p>
                        </div>
                        <a href="<?php echo e(route('admin.dashboard.v3')); ?>" class="aj-dashboard-v4-chip">Comparer V3</a>
                    </div>

                    <div class="aj-dashboard-v4-list">
                        <?php $__currentLoopData = $salesChannels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $channel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $channelPercent = max(10, min(100, (int) round(($channel['value'] / $maxChannel) * 100))); ?>
                            <div class="aj-dashboard-v4-card" style="padding:15px; background:#fff; border-color:#edf2f8; box-shadow:none;">
                                <div class="aj-dashboard-v4-panel__head" style="margin-bottom:0;">
                                    <div>
                                        <p class="aj-dashboard-v4-panel__title" style="font-size:14px;"><?php echo e($channel['name']); ?></p>
                                        <p class="aj-dashboard-v4-panel__subtitle">Répartition commerciale</p>
                                    </div>
                                    <span class="aj-dashboard-v4-chip"><?php echo e($channel['value']); ?></span>
                                </div>
                                <div class="aj-dashboard-v4-progress" style="margin-top:12px;">
                                    <div class="aj-dashboard-v4-progress__bar"><div class="aj-dashboard-v4-progress__fill" style="width: <?php echo e($channelPercent); ?>%;"></div></div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </article>

                <article class="aj-dashboard-v4-card aj-dashboard-v4-panel" style="display:grid; align-content:start;">
                    <div class="aj-dashboard-v4-panel__head">
                        <div>
                            <h2 class="aj-dashboard-v4-panel__title">Qualité opérationnelle</h2>
                            <p class="aj-dashboard-v4-panel__subtitle">Indicateurs de contrôle</p>
                        </div>
                    </div>
                    <div class="aj-dashboard-v4-list">
                        <?php $__currentLoopData = $operationalAlerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="aj-dashboard-v4-card" style="padding:14px; background:#fff; border-color:#edf2f8; box-shadow:none;">
                                <div class="aj-dashboard-v4-panel__head" style="margin-bottom:0;">
                                    <div style="display:flex; gap:12px; align-items:center;">
                                        <div class="aj-dashboard-v4-alert-icon <?php echo e($alert['tone'] === 'warn' ? 'warning' : ''); ?>"><i class="<?php echo e($alert['icon']); ?>"></i></div>
                                        <div>
                                            <p class="aj-dashboard-v4-panel__title" style="font-size:14px;"><?php echo e($alert['label']); ?></p>
                                            <p class="aj-dashboard-v4-panel__subtitle">Suivi du pilotage quotidien</p>
                                        </div>
                                    </div>
                                    <span class="aj-dashboard-v4-chip"><?php echo e($alert['value']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </article>

                <article class="aj-dashboard-v4-card aj-dashboard-v4-panel" style="display:grid; align-content:start;">
                    <div class="aj-dashboard-v4-panel__head">
                        <div>
                            <h2 class="aj-dashboard-v4-panel__title">Chiffre d'affaires</h2>
                            <p class="aj-dashboard-v4-panel__subtitle">Objectif mensuel</p>
                        </div>
                    </div>
                    <div class="aj-dashboard-v4-objective">
                        <p style="margin:0;font-size:12px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:rgba(255,255,255,0.78);">Objectif mensuel</p>
                        <div style="margin-top:10px;display:flex;align-items:end;gap:10px;">
                            <strong><?php echo e(number_format((float) $revenueTotal, 0, ',', ' ')); ?> DH</strong>
                            <span style="padding-bottom:4px;color:rgba(255,255,255,0.78);font-size:13px;font-weight:700;">CA cumulé</span>
                        </div>
                        <div class="aj-dashboard-v4-objective__bar"><div class="aj-dashboard-v4-objective__fill"></div></div>
                        <p class="aj-dashboard-v4-objective__note">Encore 186 000 DH pour atteindre l’objectif fixé sur le mois en cours.</p>
                    </div>
                </article>
            </section>

            <div class="aj-dashboard-v4-footer-actions">
                <a href="<?php echo e(route('admin.reservations.workspace')); ?>" class="aj-dashboard-v4-floating">
                    <i class="bx bx-save"></i>
                    Ouvrir le workspace
                </a>
            </div>
        </main>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\dashboard\v4\index.blade.php ENDPATH**/ ?>