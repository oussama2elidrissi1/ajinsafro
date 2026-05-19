@extends('layouts.admin-v2')

@section('title', 'Dashboard V4')

@php
    $revenueSeries = [
        ['month' => 'Jan', 'revenue' => 180000, 'reservations' => 45],
        ['month' => 'Fev', 'revenue' => 220000, 'reservations' => 53],
        ['month' => 'Mar', 'revenue' => 310000, 'reservations' => 71],
        ['month' => 'Avr', 'revenue' => 285000, 'reservations' => 64],
        ['month' => 'Mai', 'revenue' => 390000, 'reservations' => 88],
        ['month' => 'Juin', 'revenue' => 470000, 'reservations' => 104],
        ['month' => 'Juil', 'revenue' => 620000, 'reservations' => 137],
    ];

    $destinationShare = [
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

    $operationalAlerts = [
        ['label' => 'Dossiers validés', 'value' => '82%', 'icon' => 'bx bx-check-circle', 'tone' => 'ok'],
        ['label' => 'Acomptes à suivre', 'value' => '17', 'icon' => 'bx bx-time-five', 'tone' => 'warn'],
        ['label' => 'Rooming incomplet', 'value' => '6', 'icon' => 'bx bx-error-circle', 'tone' => 'danger'],
        ['label' => 'Commissions à approuver', 'value' => '12', 'icon' => 'bx bx-wallet', 'tone' => 'info'],
    ];

    $maxRevenue = max(array_column($revenueSeries, 'revenue'));
    $destinationConic = 'conic-gradient(' . implode(', ', array_map(function ($item, $index) use ($destinationShare) {
        $start = array_slice(array_column($destinationShare, 'value'), 0, $index);
        $from = array_sum($start);
        $to = $from + $item['value'];
        return sprintf('%s %s%% %s%%', $item['color'], $from, $to);
    }, $destinationShare, array_keys($destinationShare))) . ')';

    $stats = $stats ?? [];
    $revenueTotal = (float) ($stats['revenue_total'] ?? array_sum(array_column($revenueSeries, 'revenue')));
    $reservationsTotal = (int) ($stats['reservations_total'] ?? array_sum(array_column($revenueSeries, 'reservations')));
    $clientsCount = (int) ($stats['clients_count'] ?? 0);
    $departuresActive = (int) ($stats['branches_active'] ?? 38);
    $agentsActive = (int) ($stats['branches_count'] ?? 14);
    $confirmedRatio = (int) ($stats['reservations_validees'] ?? 82);
    $monthEvolution = (float) ($stats['revenue_month_evolution'] ?? 18.0);

    $statusClasses = [
        'Confirmee' => 'is-ok',
        'En attente' => 'is-warn',
        'Acompte' => 'is-info',
        'Client web' => 'is-web',
        'Ouvert' => 'is-ok',
        'Urgent' => 'is-danger',
        'Presque complet' => 'is-warn',
    ];
@endphp

@push('styles')
<style>
    body.aj-admin-compact .dashboard-v4-page {
        max-width: 1480px;
        margin: 0 auto;
        display: grid;
        gap: 18px;
    }

    body.aj-admin-compact .dashboard-v4-hero,
    body.aj-admin-compact .dashboard-v4-card {
        border: 1px solid #e5edf6;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 14px 30px rgba(15, 45, 75, 0.06);
    }

    body.aj-admin-compact .dashboard-v4-hero {
        background: linear-gradient(135deg, #0f6fb5 0%, #084c87 48%, #031d34 100%);
        color: #fff;
        padding: 22px;
        position: relative;
        overflow: hidden;
    }

    body.aj-admin-compact .dashboard-v4-hero::after {
        content: '';
        position: absolute;
        inset: -20% auto auto 62%;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        filter: blur(4px);
    }

    body.aj-admin-compact .dashboard-v4-hero-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(280px, 0.9fr);
        gap: 18px;
        align-items: center;
    }

    body.aj-admin-compact .dashboard-v4-brand {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        margin-bottom: 14px;
    }

    body.aj-admin-compact .dashboard-v4-brand img {
        display: block;
        height: 30px;
        width: auto;
        max-width: 180px;
        object-fit: contain;
        filter: brightness(0) invert(1);
    }

    body.aj-admin-compact .dashboard-v4-breadcrumb {
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.8;
    }

    body.aj-admin-compact .dashboard-v4-title {
        margin: 8px 0 0;
        font-size: clamp(24px, 3vw, 38px);
        line-height: 1.05;
        font-weight: 900;
        letter-spacing: -0.04em;
    }

    body.aj-admin-compact .dashboard-v4-lead {
        margin: 12px 0 0;
        color: rgba(255, 255, 255, 0.82);
        font-size: 14px;
        line-height: 1.7;
        max-width: 720px;
    }

    body.aj-admin-compact .dashboard-v4-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
    }

    body.aj-admin-compact .dashboard-v4-btn {
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

    body.aj-admin-compact .dashboard-v4-btn:hover {
        transform: translateY(-1px);
    }

    body.aj-admin-compact .dashboard-v4-btn.is-primary {
        background: #fff;
        color: #0b4f8a;
        box-shadow: 0 10px 22px rgba(0, 0, 0, 0.12);
    }

    body.aj-admin-compact .dashboard-v4-btn.is-secondary {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    body.aj-admin-compact .dashboard-v4-hero-side {
        display: grid;
        gap: 12px;
    }

    body.aj-admin-compact .dashboard-v4-side-card {
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.16);
        padding: 16px;
        backdrop-filter: blur(16px);
    }

    body.aj-admin-compact .dashboard-v4-side-label {
        margin: 0;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.78;
        font-weight: 800;
    }

    body.aj-admin-compact .dashboard-v4-side-value {
        margin: 8px 0 0;
        font-size: 34px;
        font-weight: 900;
        line-height: 1;
    }

    body.aj-admin-compact .dashboard-v4-side-meta {
        margin: 8px 0 0;
        color: rgba(255, 255, 255, 0.82);
        font-size: 13px;
        line-height: 1.6;
    }

    body.aj-admin-compact .dashboard-v4-metrics,
    body.aj-admin-compact .dashboard-v4-grid {
        display: grid;
        gap: 18px;
    }

    body.aj-admin-compact .dashboard-v4-metrics {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    body.aj-admin-compact .dashboard-v4-grid.grid-two {
        grid-template-columns: minmax(0, 1.7fr) minmax(320px, 0.9fr);
    }

    body.aj-admin-compact .dashboard-v4-grid.grid-three {
        grid-template-columns: minmax(0, 1.7fr) minmax(320px, 0.9fr);
    }

    body.aj-admin-compact .dashboard-v4-panel {
        padding: 20px;
    }

    body.aj-admin-compact .dashboard-v4-panel h2 {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
        font-weight: 900;
        color: #0f243d;
    }

    body.aj-admin-compact .dashboard-v4-panel p.dashboard-v4-subtitle {
        margin: 6px 0 0;
        color: #6b7a90;
        font-size: 13px;
    }

    body.aj-admin-compact .dashboard-v4-panel-head {
        display: flex;
        align-items: start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }

    body.aj-admin-compact .dashboard-v4-switches {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    body.aj-admin-compact .dashboard-v4-switch {
        border: 1px solid #dbe6f2;
        background: #fff;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 800;
        color: #49627d;
    }

    body.aj-admin-compact .dashboard-v4-switch.is-active {
        background: #0f6fb5;
        color: #fff;
        border-color: #0f6fb5;
    }

    body.aj-admin-compact .dashboard-v4-chart {
        height: 320px;
        display: grid;
        gap: 18px;
        align-items: end;
    }

    body.aj-admin-compact .dashboard-v4-bars {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 10px;
        align-items: end;
        height: 240px;
    }

    body.aj-admin-compact .dashboard-v4-bar-item {
        display: grid;
        gap: 8px;
        justify-items: center;
    }

    body.aj-admin-compact .dashboard-v4-bar-track {
        width: 100%;
        height: 210px;
        display: flex;
        align-items: end;
        justify-content: center;
        background: linear-gradient(180deg, rgba(15, 111, 181, 0.04) 0%, rgba(15, 111, 181, 0.0) 100%);
        border-radius: 20px 20px 12px 12px;
        padding: 10px 10px 0;
        border: 1px solid #edf2f8;
    }

    body.aj-admin-compact .dashboard-v4-bar {
        width: 100%;
        max-width: 42px;
        border-radius: 14px 14px 4px 4px;
        background: linear-gradient(180deg, #18a66a 0%, #0f6fb5 72%, #084c87 100%);
        box-shadow: 0 10px 18px rgba(15, 111, 181, 0.18);
    }

    body.aj-admin-compact .dashboard-v4-bar-label {
        font-size: 12px;
        font-weight: 800;
        color: #506178;
    }

    body.aj-admin-compact .dashboard-v4-bar-value {
        font-size: 12px;
        font-weight: 900;
        color: #0f243d;
    }

    body.aj-admin-compact .dashboard-v4-donut-wrap {
        display: grid;
        gap: 16px;
    }

    body.aj-admin-compact .dashboard-v4-donut {
        width: 210px;
        height: 210px;
        margin: 0 auto;
        border-radius: 50%;
        background: {{ $destinationConic }};
        position: relative;
        box-shadow: inset 0 0 0 14px #fff;
    }

    body.aj-admin-compact .dashboard-v4-donut::after {
        content: '';
        position: absolute;
        inset: 34px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 0 0 1px #eef3f8 inset;
    }

    body.aj-admin-compact .dashboard-v4-donut-center {
        position: absolute;
        inset: 34px;
        z-index: 1;
        display: grid;
        place-items: center;
        text-align: center;
        border-radius: 50%;
    }

    body.aj-admin-compact .dashboard-v4-donut-center strong {
        display: block;
        color: #0f243d;
        font-size: 28px;
        line-height: 1;
        font-weight: 900;
    }

    body.aj-admin-compact .dashboard-v4-donut-center span {
        margin-top: 6px;
        display: block;
        color: #6b7a90;
        font-size: 12px;
        font-weight: 700;
    }

    body.aj-admin-compact .dashboard-v4-legend {
        display: grid;
        gap: 10px;
    }

    body.aj-admin-compact .dashboard-v4-legend-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        color: #22354c;
        font-size: 13px;
        font-weight: 700;
    }

    body.aj-admin-compact .dashboard-v4-legend-left {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    body.aj-admin-compact .dashboard-v4-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        flex: none;
    }

    body.aj-admin-compact .dashboard-v4-table,
    body.aj-admin-compact .dashboard-v4-list {
        display: grid;
        gap: 12px;
    }

    body.aj-admin-compact .dashboard-v4-row {
        display: grid;
        gap: 14px;
        padding: 16px 0;
        border-top: 1px solid #edf2f8;
    }

    body.aj-admin-compact .dashboard-v4-row:first-child {
        border-top: 0;
        padding-top: 0;
    }

    body.aj-admin-compact .dashboard-v4-departure-row {
        grid-template-columns: minmax(0, 1.4fr) 120px 120px minmax(180px, 1fr) 110px;
        align-items: center;
    }

    body.aj-admin-compact .dashboard-v4-mini-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        color: #6b7a90;
        font-size: 12px;
        font-weight: 700;
        margin-top: 6px;
    }

    body.aj-admin-compact .dashboard-v4-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 7px 11px;
        font-size: 12px;
        font-weight: 800;
        border: 1px solid transparent;
        width: fit-content;
    }

    body.aj-admin-compact .dashboard-v4-status.is-ok {
        background: #ecfdf3;
        color: #128456;
        border-color: #c8f0d8;
    }

    body.aj-admin-compact .dashboard-v4-status.is-warn {
        background: #fff7e8;
        color: #c96e12;
        border-color: #ffd9a3;
    }

    body.aj-admin-compact .dashboard-v4-status.is-danger {
        background: #fef2f2;
        color: #b42318;
        border-color: #fecaca;
    }

    body.aj-admin-compact .dashboard-v4-status.is-info {
        background: #eff6ff;
        color: #2563eb;
        border-color: #bfdbfe;
    }

    body.aj-admin-compact .dashboard-v4-status.is-web {
        background: #f5f3ff;
        color: #6d28d9;
        border-color: #ddd6fe;
    }

    body.aj-admin-compact .dashboard-v4-progress {
        display: grid;
        gap: 8px;
    }

    body.aj-admin-compact .dashboard-v4-progress-meta {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        color: #6b7a90;
        font-size: 12px;
        font-weight: 700;
    }

    body.aj-admin-compact .dashboard-v4-progress-bar {
        height: 10px;
        background: #edf2f8;
        border-radius: 999px;
        overflow: hidden;
    }

    body.aj-admin-compact .dashboard-v4-progress-fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #0f6fb5 0%, #18a66a 100%);
    }

    body.aj-admin-compact .dashboard-v4-list-item {
        padding: 15px;
        border: 1px solid #edf2f8;
        border-radius: 18px;
        background: #fff;
    }

    body.aj-admin-compact .dashboard-v4-list-item + .dashboard-v4-list-item {
        margin-top: 12px;
    }

    body.aj-admin-compact .dashboard-v4-list-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    body.aj-admin-compact .dashboard-v4-list-title {
        margin: 0;
        font-size: 14px;
        font-weight: 900;
        color: #0f243d;
    }

    body.aj-admin-compact .dashboard-v4-list-subtitle {
        margin: 4px 0 0;
        color: #6b7a90;
        font-size: 12px;
        font-weight: 700;
    }

    body.aj-admin-compact .dashboard-v4-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e5edf6;
        padding: 7px 11px;
        font-size: 12px;
        font-weight: 800;
        color: #49627d;
    }

    body.aj-admin-compact .dashboard-v4-objective {
        padding: 18px;
        border-radius: 22px;
        background: linear-gradient(135deg, #0f6fb5 0%, #0c5f9b 56%, #084c87 100%);
        color: #fff;
    }

    body.aj-admin-compact .dashboard-v4-objective strong {
        font-size: 34px;
        line-height: 1;
        font-weight: 900;
    }

    body.aj-admin-compact .dashboard-v4-objective-bar {
        margin-top: 16px;
        height: 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        overflow: hidden;
    }

    body.aj-admin-compact .dashboard-v4-objective-fill {
        width: {{ max(35, min(95, (int) $confirmedRatio)) }}%;
        height: 100%;
        border-radius: inherit;
        background: #f28c28;
    }

    body.aj-admin-compact .dashboard-v4-objective-note {
        margin-top: 12px;
        color: rgba(255, 255, 255, 0.82);
        font-size: 13px;
        line-height: 1.6;
    }

    body.aj-admin-compact .dashboard-v4-footer-actions {
        position: sticky;
        bottom: 18px;
        z-index: 10;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        pointer-events: none;
    }

    body.aj-admin-compact .dashboard-v4-floating {
        pointer-events: auto;
        border-radius: 999px;
        padding: 12px 18px;
        border: 0;
        background: #0f6fb5;
        color: #fff;
        font-weight: 900;
        box-shadow: 0 16px 30px rgba(15, 111, 181, 0.25);
    }

    @media (max-width: 1280px) {
        body.aj-admin-compact .dashboard-v4-metrics,
        body.aj-admin-compact .dashboard-v4-grid.grid-two,
        body.aj-admin-compact .dashboard-v4-grid.grid-three,
        body.aj-admin-compact .dashboard-v4-hero-grid {
            grid-template-columns: 1fr;
        }

        body.aj-admin-compact .dashboard-v4-departure-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        body.aj-admin-compact .dashboard-v4-page {
            gap: 14px;
        }

        body.aj-admin-compact .dashboard-v4-hero,
        body.aj-admin-compact .dashboard-v4-panel {
            padding: 16px;
        }

        body.aj-admin-compact .dashboard-v4-bars {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            height: auto;
        }

        body.aj-admin-compact .dashboard-v4-bar-track {
            height: 180px;
        }

        body.aj-admin-compact .dashboard-v4-footer-actions {
            justify-content: stretch;
            width: 100%;
        }

        body.aj-admin-compact .dashboard-v4-floating {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-v4-page">
    <section class="dashboard-v4-hero">
        <div class="dashboard-v4-hero-grid">
            <div>
                <div class="dashboard-v4-brand">
                    <img src="{{ \App\Models\Setting::brandLogoUrl('dark') }}" alt="Ajinsafro">
                </div>
                <div class="dashboard-v4-breadcrumb">Admin / Dashboard V4</div>
                <h1 class="dashboard-v4-title">Vue commerciale, pilotage des départs et suivi des réservations</h1>
                <p class="dashboard-v4-lead">
                    Une page de pilotage rapide pour suivre le chiffre d'affaires, les départs à remplir, les dossiers en attente et la qualité opérationnelle, sans remplacer les dashboards existants.
                </p>
                <div class="dashboard-v4-actions">
                    <a href="{{ route('admin.reservations.workspace') }}" class="dashboard-v4-btn is-primary">
                        <i class="bx bx-calendar-check"></i>
                        Ouvrir le workspace
                    </a>
                    <a href="{{ route('admin.dashboard.v3') }}" class="dashboard-v4-btn is-secondary">
                        <i class="bx bx-grid-alt"></i>
                        Voir Dashboard V3
                    </a>
                </div>
            </div>

            <div class="dashboard-v4-hero-side">
                <div class="dashboard-v4-side-card">
                    <p class="dashboard-v4-side-label">Chiffre d'affaires</p>
                    <p class="dashboard-v4-side-value">{{ number_format((float) $revenueTotal, 0, ',', ' ') }} DH</p>
                    <p class="dashboard-v4-side-meta">Evolution mensuelle: +{{ number_format($monthEvolution, 1, ',', ' ') }}%</p>
                </div>
                <div class="dashboard-v4-side-card">
                    <p class="dashboard-v4-side-label">Réservations</p>
                    <p class="dashboard-v4-side-value">{{ number_format($reservationsTotal, 0, ',', ' ') }}</p>
                    <p class="dashboard-v4-side-meta">Dossiers confirmés, en cours et acompte.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-v4-metrics">
        <article class="dashboard-v4-card dashboard-v4-panel">
            <h2>Chiffre d'affaires</h2>
            <p class="dashboard-v4-subtitle">CA consolidé ventes + web</p>
            <div style="margin-top: 14px; font-size: 30px; font-weight: 900; color: #0f243d;">{{ number_format((float) $revenueTotal, 0, ',', ' ') }} DH</div>
            <div style="margin-top: 8px; color: #128456; font-size: 12px; font-weight: 800;">+18% vs mois précédent</div>
        </article>
        <article class="dashboard-v4-card dashboard-v4-panel">
            <h2>Réservations</h2>
            <p class="dashboard-v4-subtitle">Confirmées, acomptes et web</p>
            <div style="margin-top: 14px; font-size: 30px; font-weight: 900; color: #0f243d;">{{ number_format($reservationsTotal, 0, ',', ' ') }}</div>
            <div style="margin-top: 8px; color: #128456; font-size: 12px; font-weight: 800;">Flux en progression</div>
        </article>
        <article class="dashboard-v4-card dashboard-v4-panel">
            <h2>Départs actifs</h2>
            <p class="dashboard-v4-subtitle">Voyages avec disponibilité</p>
            <div style="margin-top: 14px; font-size: 30px; font-weight: 900; color: #0f243d;">{{ number_format($departuresActive, 0, ',', ' ') }}</div>
            <div style="margin-top: 8px; color: #c96e12; font-size: 12px; font-weight: 800;">A surveiller ce mois-ci</div>
        </article>
        <article class="dashboard-v4-card dashboard-v4-panel">
            <h2>Agents actifs</h2>
            <p class="dashboard-v4-subtitle">Commerciaux et agences</p>
            <div style="margin-top: 14px; font-size: 30px; font-weight: 900; color: #0f243d;">{{ number_format($agentsActive, 0, ',', ' ') }}</div>
            <div style="margin-top: 8px; color: #6d28d9; font-size: 12px; font-weight: 800;">Réseau opérationnel</div>
        </article>
    </section>

    <section class="dashboard-v4-grid grid-two">
        <article class="dashboard-v4-card dashboard-v4-panel">
            <div class="dashboard-v4-panel-head">
                <div>
                    <h2>Performance commerciale</h2>
                    <p class="dashboard-v4-subtitle">CA et volume de réservations par mois</p>
                </div>
                <div class="dashboard-v4-switches">
                    <span class="dashboard-v4-switch">7 jours</span>
                    <span class="dashboard-v4-switch is-active">30 jours</span>
                    <span class="dashboard-v4-switch">Année</span>
                </div>
            </div>

            <div class="dashboard-v4-chart">
                <div class="dashboard-v4-bars">
                    @foreach($revenueSeries as $item)
                        @php
                            $percent = $maxRevenue > 0 ? max(10, round(($item['revenue'] / $maxRevenue) * 100)) : 10;
                        @endphp
                        <div class="dashboard-v4-bar-item">
                            <div class="dashboard-v4-bar-track">
                                <div class="dashboard-v4-bar" style="height: {{ $percent }}%;"></div>
                            </div>
                            <div class="dashboard-v4-bar-label">{{ $item['month'] }}</div>
                            <div class="dashboard-v4-bar-value">{{ number_format($item['revenue'], 0, ',', ' ') }} DH</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="dashboard-v4-card dashboard-v4-panel">
            <div class="dashboard-v4-panel-head">
                <div>
                    <h2>Destinations vendues</h2>
                    <p class="dashboard-v4-subtitle">Répartition du mois</p>
                </div>
                <i class="bx bx-doughnut-chart" style="font-size: 22px; color: #8ba1ba;"></i>
            </div>

            <div class="dashboard-v4-donut-wrap">
                <div class="dashboard-v4-donut">
                    <div class="dashboard-v4-donut-center">
                        <div>
                            <strong>100%</strong>
                            <span>du portefeuille</span>
                        </div>
                    </div>
                </div>
                <div class="dashboard-v4-legend">
                    @foreach($destinationShare as $item)
                        <div class="dashboard-v4-legend-item">
                            <span class="dashboard-v4-legend-left">
                                <span class="dashboard-v4-dot" style="background: {{ $item['color'] }};"></span>
                                {{ $item['name'] }}
                            </span>
                            <span>{{ $item['value'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    </section>

    <section class="dashboard-v4-grid grid-three">
        <article class="dashboard-v4-card dashboard-v4-panel">
            <div class="dashboard-v4-panel-head">
                <div>
                    <h2>Départs à piloter</h2>
                    <p class="dashboard-v4-subtitle">Suivi capacité, ventes, urgence et disponibilité</p>
                </div>
                <a href="{{ route('admin.reservations.workspace') }}" class="dashboard-v4-pill">
                    <i class="bx bx-calendar-event"></i>
                    Calendrier
                </a>
            </div>

            <div class="dashboard-v4-table">
                @foreach($departures as $item)
                    @php
                        $progress = $item['capacity'] > 0 ? round(($item['sold'] / $item['capacity']) * 100) : 0;
                        $remaining = max(0, $item['capacity'] - $item['sold']);
                    @endphp
                    <div class="dashboard-v4-row dashboard-v4-departure-row">
                        <div>
                            <div style="font-weight: 900; color: #0f243d;">{{ $item['name'] }}</div>
                            <div class="dashboard-v4-mini-meta">
                                <span><i class="bx bx-map"></i> {{ $item['city'] }}</span>
                                <span>{{ $item['price'] }}</span>
                            </div>
                        </div>
                        <div style="font-weight: 800; color: #22354c;">{{ $item['date'] }}</div>
                        <div>
                            <span class="dashboard-v4-status {{ str_contains($item['status'], 'Urgent') ? 'is-danger' : (str_contains($item['status'], 'complet') ? 'is-warn' : 'is-ok') }}">
                                {{ $item['status'] }}
                            </span>
                        </div>
                        <div class="dashboard-v4-progress">
                            <div class="dashboard-v4-progress-meta">
                                <span>{{ $item['sold'] }}/{{ $item['capacity'] }} vendus</span>
                                <span>{{ $remaining }} restants</span>
                            </div>
                            <div class="dashboard-v4-progress-bar">
                                <div class="dashboard-v4-progress-fill" style="width: {{ min(100, $progress) }}%;"></div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: flex-end;">
                            <a href="{{ route('admin.reservations.workspace') }}" class="dashboard-v4-btn is-primary" style="padding: 9px 12px;">
                                <i class="bx bx-show"></i>
                                Détails
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="dashboard-v4-card dashboard-v4-panel">
            <div class="dashboard-v4-panel-head">
                <div>
                    <h2>Qualité opérationnelle</h2>
                    <p class="dashboard-v4-subtitle">Indicateurs de contrôle</p>
                </div>
            </div>

            <div class="dashboard-v4-list">
                @foreach($operationalAlerts as $alert)
                    <div class="dashboard-v4-list-item">
                        <div class="dashboard-v4-list-head">
                            <div>
                                <p class="dashboard-v4-list-title">{{ $alert['label'] }}</p>
                                <p class="dashboard-v4-list-subtitle">Suivi du pilotage quotidien</p>
                            </div>
                            <span class="dashboard-v4-pill">{{ $alert['value'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="dashboard-v4-objective" style="margin-top: 16px;">
                <p style="margin: 0; font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255, 255, 255, 0.78);">Objectif mensuel</p>
                <div style="margin-top: 10px; display: flex; align-items: end; gap: 10px;">
                    <strong>{{ $confirmedRatio }}%</strong>
                    <span style="padding-bottom: 4px; color: rgba(255, 255, 255, 0.78); font-size: 13px; font-weight: 700;">de confirmation</span>
                </div>
                <div class="dashboard-v4-objective-bar">
                    <div class="dashboard-v4-objective-fill"></div>
                </div>
                <p class="dashboard-v4-objective-note">Encore 186 000 DH pour atteindre l’objectif fixé sur le mois en cours.</p>
            </div>
        </article>
    </section>

    <section class="dashboard-v4-grid grid-two">
        <article class="dashboard-v4-card dashboard-v4-panel">
            <div class="dashboard-v4-panel-head">
                <div>
                    <h2>Réservations récentes</h2>
                    <p class="dashboard-v4-subtitle">Flux live commercial et client web</p>
                </div>
                <a href="{{ route('admin.reservations.index') }}" class="dashboard-v4-pill">Voir tout</a>
            </div>

            <div class="dashboard-v4-list">
                @foreach($recentReservations as $reservation)
                    <div class="dashboard-v4-list-item">
                        <div class="dashboard-v4-list-head">
                            <div>
                                <p class="dashboard-v4-list-title">{{ $reservation['client'] }}</p>
                                <p class="dashboard-v4-list-subtitle">{{ $reservation['trip'] }} - {{ $reservation['agent'] }}</p>
                            </div>
                            <div style="display: grid; justify-items: end; gap: 6px;">
                                <span class="dashboard-v4-status {{ $statusClasses[$reservation['status']] ?? 'is-ok' }}">{{ $reservation['status'] }}</span>
                                <span style="font-size: 14px; font-weight: 900; color: #0f243d;">{{ $reservation['amount'] }}</span>
                            </div>
                        </div>
                        <div class="dashboard-v4-list-subtitle" style="margin-top: 8px;">{{ $reservation['time'] }}</div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="dashboard-v4-card dashboard-v4-panel">
            <div class="dashboard-v4-panel-head">
                <div>
                    <h2>Ventes par canal</h2>
                    <p class="dashboard-v4-subtitle">Agence, commercial, client web</p>
                </div>
                <a href="{{ route('admin.dashboard.v3') }}" class="dashboard-v4-pill">Comparer V3</a>
            </div>

            <div class="dashboard-v4-list">
                @foreach($salesChannels as $channel)
                    @php
                        $channelPercent = max(10, min(100, (int) round(($channel['value'] / max(array_column($salesChannels, 'value'))) * 100)));
                    @endphp
                    <div class="dashboard-v4-list-item">
                        <div class="dashboard-v4-list-head">
                            <div>
                                <p class="dashboard-v4-list-title">{{ $channel['name'] }}</p>
                                <p class="dashboard-v4-list-subtitle">Répartition commerciale</p>
                            </div>
                            <span class="dashboard-v4-pill">{{ $channel['value'] }}</span>
                        </div>
                        <div class="dashboard-v4-progress" style="margin-top: 12px;">
                            <div class="dashboard-v4-progress-bar">
                                <div class="dashboard-v4-progress-fill" style="width: {{ $channelPercent }}%;"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <div class="dashboard-v4-footer-actions">
        <a href="{{ route('admin.reservations.workspace') }}" class="dashboard-v4-floating">
            <i class="bx bx-save"></i>
            Ouvrir le workspace
        </a>
    </div>
</div>
@endsection
