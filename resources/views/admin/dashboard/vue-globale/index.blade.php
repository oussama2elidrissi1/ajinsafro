@extends('layouts.master-ajinsafro')

@section('title', 'Tableau de bord')

@push('css')
<style>
    /* CSS Variables - Match dashboard.html design system */
    :root {
        --dash-blue: #0b68d1;
        --dash-blue-dark: #073b74;
        --dash-blue-soft: #eef6ff;
        --dash-orange: #ff8a00;
        --dash-green: #19b982;
        --dash-red: #ef4d45;
        --dash-yellow: #f7b500;
        --dash-purple: #8b5cf6;
        --dash-text: #172b4d;
        --dash-muted: #71829a;
        --dash-border: #e6edf5;
        --dash-bg: #f6f9fc;
        --dash-white: #ffffff;
        --dash-shadow: 0 12px 35px rgba(15, 45, 75, 0.08);
        --dash-radius: 18px;
    }

    /* Dashboard container */
    .aj-dashboard-view {
        background: var(--dash-bg);
        padding: 0;
    }

    /* Page title box */
    .aj-dashboard-view .page-title-box {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 28px;
    }

    .aj-dashboard-view .page-title-box > div:first-child {
        flex: 1;
    }

    .aj-dashboard-view .page-title {
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .aj-dashboard-view .page-title h4 {
        font-size: 34px;
        font-weight: 900;
        color: var(--dash-text);
        margin-bottom: 6px;
        margin: 0;
    }

    .aj-dashboard-view .page-title p {
        color: var(--dash-muted);
        font-weight: 600;
        margin: 0;
    }

    /* Cards styling */
    .aj-dashboard-view .card {
        background: var(--dash-white);
        border: 1px solid var(--dash-border);
        border-radius: var(--dash-radius);
        box-shadow: var(--dash-shadow);
    }

    .aj-dashboard-view .dashboard-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .aj-dashboard-view .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 45px rgba(15, 45, 75, 0.12);
    }

    .aj-dashboard-view .dashboard-card .card-body {
        position: relative;
        overflow: hidden;
        padding: 24px;
        min-height: 166px;
        display: grid;
        align-content: space-between;
    }

    .aj-dashboard-view .dashboard-card .card-body::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        opacity: 0.06;
    }

    /* KPI Cards */
    .aj-dashboard-view .kpi-card-content {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .aj-dashboard-view .kpi-icon-wrap {
        width: 58px;
        height: 58px;
        border-radius: 17px;
        display: grid;
        place-items: center;
        font-size: 26px;
        flex-shrink: 0;
    }

    .aj-dashboard-view .bg-primary { background: #e8f1ff; color: var(--dash-blue); }
    .aj-dashboard-view .bg-success { background: #e7fff4; color: var(--dash-green); }
    .aj-dashboard-view .bg-warning { background: #fff0df; color: var(--dash-orange); }
    .aj-dashboard-view .bg-purple { background: #f1eaff; color: var(--dash-purple); }

    .aj-dashboard-view .kpi-data {
        flex: 1;
    }

    .aj-dashboard-view .kpi-label {
        color: #61728a;
        font-size: 13px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-top: 18px;
        margin-bottom: 8px;
    }

    .aj-dashboard-view .kpi-value {
        font-size: 36px;
        font-weight: 900;
        color: var(--dash-text);
        margin: 8px 0;
    }

    .aj-dashboard-view .kpi-footer-text {
        font-size: 13px;
        color: var(--dash-muted);
        font-weight: 700;
        padding-top: 14px;
        border-top: 1px solid #edf2f7;
    }

    /* Widget styling */
    .aj-dashboard-view .widget-box {
        padding: 24px;
    }

    .aj-dashboard-view .widget-title {
        font-size: 18px;
        font-weight: 900;
        color: var(--dash-text);
        margin-bottom: 18px;
        margin: 0 0 18px 0;
    }

    .aj-dashboard-view .widget-head {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
    }

    .aj-dashboard-view .widget-head h3 {
        font-size: 18px;
        font-weight: 900;
        color: var(--dash-text);
    }

    .aj-dashboard-view .widget-icon {
        width: 34px;
        height: 34px;
        border-radius: 11px;
        display: grid;
        place-items: center;
        background: var(--dash-blue-soft);
        color: var(--dash-blue);
    }

    /* Progress bars */
    .aj-dashboard-view .stat-progress {
        height: 8px;
        border-radius: 999px;
        background: #edf2f7;
        overflow: hidden;
        margin-bottom: 12px;
    }

    .aj-dashboard-view .stat-progress .progress-bar {
        border-radius: inherit;
        transition: width 0.3s ease;
        height: 100%;
    }

    /* Metric rows */
    .aj-dashboard-view .metric-list {
        display: grid;
        gap: 14px;
        margin-bottom: 18px;
    }

    .aj-dashboard-view .metric-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f3f7;
        font-size: 14px;
        color: var(--dash-muted);
        font-weight: 700;
    }

    .aj-dashboard-view .metric-row strong {
        color: var(--dash-text);
        font-weight: 700;
    }

    .aj-dashboard-view .metric-row:last-child {
        border-bottom: none;
    }

    /* Status grid */
    .aj-dashboard-view .status-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 28px;
    }

    .aj-dashboard-view .status-item {
        display: grid;
        gap: 9px;
    }

    .aj-dashboard-view .status-line {
        display: flex;
        justify-content: space-between;
        font-weight: 800;
        color: var(--dash-muted);
        font-size: 14px;
    }

    .aj-dashboard-view .activity-dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        margin-right: 8px;
        flex-shrink: 0;
    }

    /* Charts */
    .aj-dashboard-view .chart-container {
        min-height: 320px;
    }

    .aj-dashboard-view #vue-globale-line-chart,
    .aj-dashboard-view #vue-globale-donut-chart,
    .aj-dashboard-view #vue-globale-payment-chart {
        width: 100%;
        height: 100%;
    }

    /* Table styling */
    .aj-dashboard-view .table-dashboard {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin-bottom: 0;
    }

    .aj-dashboard-view .table-dashboard thead {
        background: #f7fbff;
    }

    .aj-dashboard-view .table-dashboard th {
        text-align: left;
        padding: 14px 16px;
        background: #f7fbff;
        color: #66758a;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 900;
        border-bottom: 1px solid var(--dash-border);
    }

    .aj-dashboard-view .table-dashboard td {
        padding: 14px 16px;
        border-bottom: 1px solid #edf2f7;
        color: #42556d;
        font-weight: 700;
        vertical-align: middle;
    }

    .aj-dashboard-view .table-dashboard tbody tr {
        transition: background 0.2s ease;
    }

    .aj-dashboard-view .table-dashboard tbody tr:hover {
        background: rgba(11, 104, 209, 0.04);
    }

    .aj-dashboard-view .table-dashboard tbody tr:last-child td {
        border-bottom: none;
    }

    .aj-dashboard-view .client-name {
        font-weight: 900;
        color: var(--dash-text);
        display: block;
        margin-bottom: 4px;
    }

    .aj-dashboard-view .client-email {
        color: var(--dash-muted);
        font-size: 12px;
        display: block;
        max-width: 150px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 600;
    }

    /* Tags/Badges */
    .aj-dashboard-view .tag {
        display: inline-flex;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
    }

    .aj-dashboard-view .tag.pending { background: #fff4d8; color: #9a6b00; }
    .aj-dashboard-view .tag.valid { background: #e8fff4; color: var(--dash-green); }
    .aj-dashboard-view .tag.cancel { background: #fff0ef; color: var(--dash-red); }

    .aj-dashboard-view .badge {
        display: inline-flex;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        align-items: center;
        gap: 4px;
    }

    .aj-dashboard-view .bg-success { background: #e9fff4; color: var(--dash-green); }
    .aj-dashboard-view .bg-danger { background: #fff0ef; color: var(--dash-red); }

    /* Button styling */
    .aj-dashboard-view .btn {
        border-radius: 13px;
        font-weight: 800;
        cursor: pointer;
        padding: 10px 16px;
        border: none;
        transition: 0.2s ease;
    }

    .aj-dashboard-view .btn-primary {
        background: var(--dash-blue);
        color: #fff;
        border: 1px solid var(--dash-blue);
    }

    .aj-dashboard-view .btn-primary:hover {
        background: var(--dash-blue-dark);
        border-color: var(--dash-blue-dark);
    }

    .aj-dashboard-view .btn-soft-primary {
        background: var(--dash-blue-soft);
        color: var(--dash-blue);
        border: 1px solid var(--dash-blue-soft);
    }

    .aj-dashboard-view .btn-sm {
        padding: 8px 12px;
        font-size: 12px;
    }

    /* Spacing utilities */
    .aj-dashboard-view .row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
        gap: 20px;
    }

    .aj-dashboard-view .g-3 {
        --bs-gutter-x: 20px;
        --bs-gutter-y: 20px;
        gap: 20px;
    }

    .aj-dashboard-view .mb-3 { margin-bottom: 18px; }
    .aj-dashboard-view .mb-4 { margin-bottom: 22px; }
    .aj-dashboard-view .mb-1 { margin-bottom: 6px; }
    .aj-dashboard-view .me-1 { margin-right: 4px; }
    .aj-dashboard-view .ms-2 { margin-left: 8px; }
    .aj-dashboard-view .mt-3 { margin-top: 18px; }
    .aj-dashboard-view .mt-1 { margin-top: 6px; }

    /* Column sizing */
    .aj-dashboard-view .col-12 { grid-column: 1 / -1; }
    .aj-dashboard-view .col-xl-3 { @media (min-width: 1200px) { grid-column: span 3; } }
    .aj-dashboard-view .col-xl-4 { @media (min-width: 1200px) { grid-column: span 4; } }
    .aj-dashboard-view .col-xl-6 { @media (min-width: 1200px) { grid-column: span 6; } }
    .aj-dashboard-view .col-xl-8 { @media (min-width: 1200px) { grid-column: span 8; } }
    .aj-dashboard-view .col-md-6 { @media (max-width: 991px) { grid-column: span 6; } }

    /* Animations */
    .aj-dashboard-view .animate-fade-in {
        animation: dashFadeIn 0.5s ease forwards;
        opacity: 0;
    }

    .aj-dashboard-view .animate-fade-in.delay-1 { animation-delay: 0.08s; }
    .aj-dashboard-view .animate-fade-in.delay-2 { animation-delay: 0.16s; }
    .aj-dashboard-view .animate-fade-in.delay-3 { animation-delay: 0.24s; }
    .aj-dashboard-view .animate-fade-in.delay-4 { animation-delay: 0.32s; }
    .aj-dashboard-view .animate-fade-in.delay-5 { animation-delay: 0.40s; }
    .aj-dashboard-view .animate-fade-in.delay-6 { animation-delay: 0.48s; }

    @keyframes dashFadeIn {
        from {
            opacity: 0;
            transform: translateY(12px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Text utilities */
    .aj-dashboard-view .text-muted { color: var(--dash-muted); }
    .aj-dashboard-view .text-danger { color: var(--dash-red); }
    .aj-dashboard-view .text-center { text-align: center; }
    .aj-dashboard-view .text-body { color: var(--dash-text); }

    .aj-dashboard-view .h-100 { height: 100%; }
    .aj-dashboard-view .fw-medium { font-weight: 500; }
    .aj-dashboard-view .border-bottom { border-bottom: 1px solid var(--dash-border); }
    .aj-dashboard-view .border-start { border-left: 3px solid var(--dash-blue); }
    .aj-dashboard-view .border-3 { border-width: 3px; }
    .aj-dashboard-view .border-primary { border-color: var(--dash-blue); }

    .aj-dashboard-view .d-flex { display: flex; }
    .aj-dashboard-view .align-items-center { align-items: center; }
    .aj-dashboard-view .justify-content-between { justify-content: space-between; }
    .aj-dashboard-view .gap-4 { gap: 16px; }

    .aj-dashboard-view .stretched-link { position: relative; }
    .aj-dashboard-view .stretched-link::after { content: ''; position: absolute; inset: 0; }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .aj-dashboard-view .status-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .aj-dashboard-view .col-xl-3 { grid-column: span 6; }
        .aj-dashboard-view .col-xl-4 { grid-column: span 6; }
        .aj-dashboard-view .col-xl-8 { grid-column: span 1 / -1; }
        .aj-dashboard-view .col-xl-6 { grid-column: span 1 / -1; }
    }

    @media (max-width: 768px) {
        .aj-dashboard-view .page-title-box {
            flex-direction: column;
            align-items: flex-start;
        }

        .aj-dashboard-view .page-title h4 {
            font-size: 24px;
        }

        .aj-dashboard-view .status-grid {
            grid-template-columns: 1fr;
        }

        .aj-dashboard-view .col-md-6 { grid-column: 1 / -1; }

        .aj-dashboard-view .table-dashboard {
            font-size: 12px;
        }

        .aj-dashboard-view .table-dashboard th,
        .aj-dashboard-view .table-dashboard td {
            padding: 10px 12px;
        }
    }
</style>
@endpush

@section('content')
<div class="aj-dashboard-view">
<div class="row mb-3">
    <div class="col-12">
        <div class="page-title-box">
            <div>
                <h4 class="page-title mb-1">Tableau de bord</h4>
                <p class="text-muted mb-0">{{ now()->locale('fr')->translatedFormat('l d F Y · H:i') }}</p>
            </div>
            <ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                <li class="breadcrumb-item active">Vue globale</li>
            </ol>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 animate-fade-in">
        <div class="card dashboard-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="kpi-card-content">
                    <div class="kpi-icon-wrap bg-primary" style="flex-shrink: 0;">
                        <i class="bx bx-map font-size-24"></i>
                    </div>
                    <div class="kpi-data">
                        <p class="kpi-label">Voyages</p>
                        <h3 class="kpi-value">{{ $stats['voyages_count'] }}</h3>
                        @if($stats['voyages_featured'] > 0)
                            <small class="kpi-footer-text">{{ $stats['voyages_featured'] }} en vedette</small>
                        @endif
                    </div>
                </div>
                <a href="{{ route('admin.circuits.voyages.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 animate-fade-in" style="--animation-delay: 0.08s;">
        <div class="card dashboard-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="kpi-card-content">
                    <div class="kpi-icon-wrap bg-success" style="flex-shrink: 0;">
                        <i class="bx bx-building-house font-size-24"></i>
                    </div>
                    <div class="kpi-data">
                        <p class="kpi-label">Agences</p>
                        <h3 class="kpi-value">{{ $stats['branches_count'] }}</h3>
                        <small class="kpi-footer-text">{{ $stats['branches_active'] }} actives</small>
                    </div>
                </div>
                <a href="{{ route('admin.branches.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 animate-fade-in" style="--animation-delay: 0.16s;">
        <div class="card dashboard-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="kpi-card-content">
                    <div class="kpi-icon-wrap bg-info" style="flex-shrink: 0;">
                        <i class="bx bx-calendar-check font-size-24"></i>
                    </div>
                    <div class="kpi-data">
                        <p class="kpi-label">Réservations</p>
                        <h3 class="kpi-value">{{ $stats['reservations_total'] }}</h3>
                        <span class="badge {{ $stats['reservations_month_evolution'] >= 0 ? 'bg-success' : 'bg-danger' }} mt-1">
                            <i class="bx {{ $stats['reservations_month_evolution'] >= 0 ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' }}"></i>
                            {{ $stats['reservations_month_evolution'] >= 0 ? '+' : '' }}{{ $stats['reservations_month_evolution'] }}%
                        </span>
                    </div>
                </div>
                <a href="{{ route('admin.reservations.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 animate-fade-in" style="--animation-delay: 0.24s;">
        <div class="card dashboard-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="kpi-card-content">
                    <div class="kpi-icon-wrap bg-warning" style="flex-shrink: 0;">
                        <i class="bx bx-user font-size-24"></i>
                    </div>
                    <div class="kpi-data">
                        <p class="kpi-label">Clients</p>
                        <h3 class="kpi-value">{{ $stats['clients_count'] }}</h3>
                    </div>
                </div>
                <a href="{{ route('admin.customers.clients.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>
</div>

<!-- Top Widgets: Activity + Revenue + Messages -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6 animate-fade-in" style="--animation-delay: 0.32s;">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body widget-box">
                <h6 class="widget-title mb-3">Activité récente</h6>
                <div class="metric-row">
                    <span>Aujourd'hui</span>
                    <strong>{{ $stats['reservations_today'] }}</strong>
                </div>
                <div class="stat-progress mb-3">
                    @php $maxDay = max(1, $stats['reservations_this_week']); $pct = min(100, ($stats['reservations_today'] / $maxDay) * 100); @endphp
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%"></div>
                </div>

                <div class="metric-row">
                    <span>Cette semaine</span>
                    <strong>{{ $stats['reservations_this_week'] }}</strong>
                </div>
                <div class="stat-progress mb-3">
                    @php $maxWeek = max(1, $stats['reservations_this_month']); $pctW = min(100, ($stats['reservations_this_week'] / $maxWeek) * 100); @endphp
                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pctW }}%"></div>
                </div>

                <div class="metric-row">
                    <span>Ce mois</span>
                    <strong>{{ $stats['reservations_this_month'] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 animate-fade-in" style="--animation-delay: 0.32s;">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-3">
            <div class="card-body widget-box">
                <h6 class="widget-title mb-3">Chiffre d'affaires</h6>
                <p class="text-muted small mb-1">Total validé</p>
                <p style="font-size: 32px; font-weight: 900; color: #0b68d1; margin: 8px 0;">
                    {{ number_format($stats['revenue_total'], 0, ',', ' ') }} <small style="font-weight: normal; color: #71829a; font-size: 14px;">€</small>
                </p>
                <p class="text-muted small mb-1">Ce mois</p>
                <p style="font-size: 20px; font-weight: 700; margin-bottom: 8px;">{{ number_format($stats['revenue_this_month'], 0, ',', ' ') }} €</p>
                <span class="badge {{ $stats['revenue_month_evolution'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                    {{ $stats['revenue_month_evolution'] >= 0 ? '+' : '' }}{{ $stats['revenue_month_evolution'] }}% vs mois dernier
                </span>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 animate-fade-in" style="--animation-delay: 0.32s;">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body widget-box d-flex align-items: center justify-content-between">
                <div>
                    <h6 class="widget-title mb-2">Messages</h6>
                    <p class="text-muted small mb-1">Boîte Réservations</p>
                    <h3 class="kpi-value mb-0">{{ $stats['messages_count'] }}</h3>
                </div>
                <a href="{{ route('admin.messagerie.index') }}" class="btn btn-primary btn-sm" style="height: fit-content;">
                    <i class="bx bx-envelope me-1"></i>Ouvrir
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Status Distribution -->
<div class="row g-3 mb-4">
    <div class="col-12 animate-fade-in" style="--animation-delay: 0.4s;">
        <div class="card border-0 shadow-sm">
            <div class="card-body widget-box">
                <h6 class="widget-title mb-3">Répartition des réservations</h6>
                <div class="status-grid">
                    @php $total = $stats['reservations_total'] ?: 1; @endphp
                    <div class="status-item">
                        <div class="status-line">
                            <span class="d-flex align-items-center">
                                <span class="activity-dot" style="background: #f7b500;"></span>
                                En attente
                            </span>
                            <strong>{{ $stats['reservations_en_cours'] }}</strong>
                        </div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="background: #f7b500; width: {{ ($stats['reservations_en_cours'] / $total) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="status-item">
                        <div class="status-line">
                            <span class="d-flex align-items-center">
                                <span class="activity-dot" style="background: #19b982;"></span>
                                Validées
                            </span>
                            <strong>{{ $stats['reservations_validees'] }}</strong>
                        </div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="background: #19b982; width: {{ ($stats['reservations_validees'] / $total) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="status-item">
                        <div class="status-line">
                            <span class="d-flex align-items-center">
                                <span class="activity-dot" style="background: #ef4d45;"></span>
                                Annulées
                            </span>
                            <strong>{{ $stats['reservations_annulees'] }}</strong>
                        </div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="background: #ef4d45; width: {{ ($stats['reservations_annulees'] / $total) * 100 }}%"></div>
                        </div>
                    </div>

                    <div class="status-item">
                        <div class="status-line">
                            <span class="d-flex align-items-center">
                                <span class="activity-dot" style="background: #aab6c5;"></span>
                                Total
                            </span>
                            <strong>{{ $total }}</strong>
                        </div>
                        <div class="stat-progress">
                            <div class="progress-bar" style="background: #e6edf5; width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-xl-8 animate-fade-in" style="--animation-delay: 0.48s;">
        <div class="card border-0 shadow-sm">
            <div class="card-body widget-box">
                <h5 class="widget-title mb-3">Réservations & Chiffre d'affaires (6 mois)</h5>
                <div id="vue-globale-line-chart" class="chart-container"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 animate-fade-in" style="--animation-delay: 0.48s;">
        <div class="card border-0 shadow-sm">
            <div class="card-body widget-box">
                <h5 class="widget-title mb-3">Statut des réservations</h5>
                <div id="vue-globale-donut-chart" class="chart-container"></div>
            </div>
        </div>
    </div>
</div>

<!-- Payments + Recent Reservations -->
<div class="row g-3 mb-4">
    @if(count($stats['payment_labels']) > 0)
    <div class="col-xl-4 animate-fade-in" style="--animation-delay: 0.56s;">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body widget-box">
                <h5 class="widget-title mb-3">Paiements (validées)</h5>
                <div id="vue-globale-payment-chart" class="chart-container" style="min-height: 220px;"></div>
            </div>
        </div>
    </div>
    @endif

    <div class="{{ count($stats['payment_labels']) > 0 ? 'col-xl-8' : 'col-xl-12' }} animate-fade-in" style="--animation-delay: 0.56s;">
        <div class="card border-0 shadow-sm">
            <div class="card-body widget-box">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="widget-title mb-0">Dernières réservations</h5>
                    <a href="{{ route('admin.reservations.index') }}" class="btn btn-sm btn-outline-primary">
                        Toutes <i class="bx bx-right-arrow-alt"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-dashboard table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Voyage</th>
                                <th>Statut</th>
                                <th>Paiement</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lastReservations as $r)
                                <tr>
                                    <td><span class="text-muted">#{{ $r->id }}</span></td>
                                    <td>
                                        <span class="client-name">{{ $r->client_first_name }} {{ $r->client_last_name }}</span>
                                        @if($r->client_email)
                                            <span class="client-email">{{ Str::limit($r->client_email, 25) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($r->tour)
                                            <span>{{ Str::limit($r->tour->name, 28) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($r->status === \App\Models\Reservation::STATUS_VALIDEE)
                                            <span class="badge bg-success">Validée</span>
                                        @elseif($r->status === \App\Models\Reservation::STATUS_ANNULEE)
                                            <span class="badge bg-danger">Annulée</span>
                                        @else
                                            <span class="badge bg-warning text-dark">En cours</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $r->payment_type ?: '—' }}</small></td>
                                    <td>
                                        @php $tot = ($r->base_price ?? 0) + ($r->room_supplement_total ?? 0); @endphp
                                        @if($tot > 0)
                                            <strong>{{ number_format($tot, 0, ',', ' ') }} €</strong>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><small>{{ $r->created_at->format('d/m/Y H:i') }}</small></td>
                                    <td><a href="{{ route('admin.reservations.edit', $r) }}" class="btn btn-sm btn-soft-primary">Voir</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">Aucune réservation.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom: Top Voyages + Active Agencies -->
<div class="row g-3">
    <div class="col-xl-6 animate-fade-in" style="--animation-delay: 0.64s;">
        <div class="card border-0 shadow-sm">
            <div class="card-body widget-box">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="widget-title mb-0">Voyages les plus réservés</h5>
                    <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-sm btn-outline-primary">Tous</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-dashboard table-hover">
                        <thead>
                            <tr>
                                <th>Voyage</th>
                                <th class="text-end">Réservations</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topVoyages as $row)
                                <tr>
                                    <td>{{ Str::limit($row['voyage']->name, 50) }}</td>
                                    <td class="text-end"><strong>{{ $row['total'] }}</strong></td>
                                    <td><a href="{{ route('admin.circuits.voyages.edit', $row['voyage']->id) }}" class="btn btn-sm btn-soft-primary">Voir</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">Aucune donnée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 animate-fade-in" style="--animation-delay: 0.64s;">
        <div class="card border-0 shadow-sm">
            <div class="card-body widget-box">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="widget-title mb-0">Agences actives</h5>
                    <a href="{{ route('admin.branches.index') }}" class="btn btn-sm btn-outline-primary">Toutes</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($recentBranches as $b)
                        <div class="list-group-item d-flex align-items-center px-0">
                            <span class="kpi-icon-wrap bg-success" style="width: 40px; height: 40px; flex-shrink: 0;">
                                <i class="bx bx-building-house"></i>
                            </span>
                            <div class="flex-grow-1 ms-3">
                                <span class="client-name">{{ $b->name }}</span>
                                @if($b->city)
                                    <span class="client-email">{{ $b->city }} · {{ $b->code ?? 'N/A' }}</span>
                                @endif
                            </div>
                            <a href="{{ route('admin.branches.edit', $b) }}" class="btn btn-sm btn-soft-primary ms-2">Voir</a>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">Aucune agence.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div><!-- End .aj-dashboard-view -->
@endsection

@push('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Line Chart - Reservations & Revenue
        var lineOptions = {
            series: [
                { name: 'Réservations', type: 'column', data: @json($stats['chart_reservations']) },
                { name: 'Chiffre d\'affaires (€)', type: 'line', data: @json($stats['chart_revenue']) }
            ],
            chart: {
                height: 320,
                type: 'line',
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: { enabled: true, speed: 600 }
            },
            colors: ['#405189', '#19b982'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: [0, 2] },
            fill: { type: 'solid', opacity: [0.85, 0] },
            xaxis: { categories: @json($stats['chart_months']) },
            yaxis: [
                {
                    title: { text: 'Réservations' },
                    labels: { formatter: function(v) { return Math.round(v); } }
                },
                {
                    opposite: true,
                    title: { text: 'CA (€)' },
                    labels: { formatter: function(v) { return v >= 1000 ? (v/1000)+'k' : v; } }
                }
            ],
            legend: { position: 'top', horizontalAlign: 'right' },
            plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } }
        };
        new ApexCharts(document.querySelector("#vue-globale-line-chart"), lineOptions).render();

        // Donut Chart - Reservation Status
        var donutOptions = {
            series: @json($stats['donut_series']),
            chart: { height: 280, type: 'donut', animations: { enabled: true, speed: 600 } },
            labels: @json($stats['donut_labels']),
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce(function(a,b){ return a+b; }, 0);
                                }
                            }
                        }
                    }
                }
            },
            legend: { position: 'bottom' },
            colors: ['#f7b500', '#19b982', '#ef4d45']
        };
        new ApexCharts(document.querySelector("#vue-globale-donut-chart"), donutOptions).render();

        // Payment Chart
        @if(count($stats['payment_labels']) > 0)
        var paymentOptions = {
            series: [{ name: 'Paiements', data: @json($stats['payment_series']) }],
            chart: { type: 'bar', height: 220, toolbar: { show: false }, animations: { enabled: true, speed: 600 } },
            plotOptions: { bar: { horizontal: true, barHeight: '60%', borderRadius: 4 } },
            dataLabels: { enabled: true },
            xaxis: { categories: @json($stats['payment_labels']) },
            colors: ['#0b68d1'],
            legend: { show: false }
        };
        new ApexCharts(document.querySelector("#vue-globale-payment-chart"), paymentOptions).render();
        @endif
    });
</script>
@endpush
