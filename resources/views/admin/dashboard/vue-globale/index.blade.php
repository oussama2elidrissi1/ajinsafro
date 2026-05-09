@extends('layouts.master-ajinsafro')

@section('title', 'Tableau de bord — Vue globale')

@push('css')
<style>
/* ─────────────────────────────────────────────────────────
   AJ Dashboard Redesign — styles scoped à .aj-dashboard-redesign
   ▸ Aucun override du layout Qovex global
   ▸ Aucun override de .row / Bootstrap grid
   ▸ Aucun style body / sidebar / main global
   ───────────────────────────────────────────────────────── */

:root {
    --dash-blue:       #0b68d1;
    --dash-blue-dark:  #073b74;
    --dash-blue-soft:  #eef6ff;
    --dash-orange:     #ff8a00;
    --dash-green:      #19b982;
    --dash-red:        #ef4d45;
    --dash-yellow:     #f7b500;
    --dash-purple:     #8b5cf6;
    --dash-text:       #172b4d;
    --dash-muted:      #71829a;
    --dash-border:     #e6edf5;
    --dash-shadow:     0 8px 28px rgba(15, 45, 75, 0.08);
    --dash-radius:     16px;
}

/* ── Wrapper ── */
.aj-dashboard-redesign {
    font-family: inherit;
}

/* ── Page header ── */
.aj-dashboard-redesign .dash-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
}

.aj-dashboard-redesign .dash-page-header h4 {
    font-size: 26px;
    font-weight: 900;
    color: var(--dash-text);
    margin: 0 0 4px 0;
}

.aj-dashboard-redesign .dash-page-header p {
    color: var(--dash-muted);
    font-size: 13px;
    font-weight: 600;
    margin: 0;
}

/* ── Cards ── */
.aj-dashboard-redesign .card {
    background: #fff;
    border: 1px solid var(--dash-border) !important;
    border-radius: var(--dash-radius) !important;
    box-shadow: var(--dash-shadow);
}

.aj-dashboard-redesign .dash-card {
    transition: transform 0.22s ease, box-shadow 0.22s ease;
}

.aj-dashboard-redesign .dash-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 40px rgba(15, 45, 75, 0.12);
}

/* ── KPI card body ── */
.aj-dashboard-redesign .kpi-body {
    padding: 22px;
    position: relative;
    overflow: hidden;
    min-height: 155px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.aj-dashboard-redesign .kpi-row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.aj-dashboard-redesign .kpi-icon {
    width: 54px;
    height: 54px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

/* Couleurs scoped — n'affectent pas Bootstrap global */
.aj-dashboard-redesign .kpi-icon.c-blue   { background: #e8f1ff; color: var(--dash-blue); }
.aj-dashboard-redesign .kpi-icon.c-green  { background: #e7fff4; color: var(--dash-green); }
.aj-dashboard-redesign .kpi-icon.c-cyan   { background: #e5f6ff; color: #0b9fd4; }
.aj-dashboard-redesign .kpi-icon.c-orange { background: #fff3cd; color: var(--dash-orange); }
.aj-dashboard-redesign .kpi-icon.c-purple { background: #f1eaff; color: var(--dash-purple); }

.aj-dashboard-redesign .kpi-label {
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #61728a;
    margin: 0 0 5px 0;
}

.aj-dashboard-redesign .kpi-value {
    font-size: 32px;
    font-weight: 900;
    color: var(--dash-text);
    line-height: 1.1;
    margin: 0;
}

.aj-dashboard-redesign .kpi-footer {
    font-size: 12px;
    color: var(--dash-muted);
    font-weight: 600;
    padding-top: 10px;
    border-top: 1px solid #edf2f7;
    margin-top: 10px;
    display: block;
}

/* ── Widget boxes ── */
.aj-dashboard-redesign .w-box {
    padding: 22px;
}

.aj-dashboard-redesign .w-title {
    font-size: 15px;
    font-weight: 900;
    color: var(--dash-text);
    margin: 0 0 16px 0;
}

/* ── Metric rows ── */
.aj-dashboard-redesign .m-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f2f5f9;
    font-size: 13px;
    color: var(--dash-muted);
    font-weight: 600;
}

.aj-dashboard-redesign .m-row:last-child {
    border-bottom: none;
}

.aj-dashboard-redesign .m-row strong {
    color: var(--dash-text);
    font-weight: 800;
}

/* ── Progress bars ── */
.aj-dashboard-redesign .dash-progress {
    height: 7px;
    border-radius: 999px;
    background: #edf2f7;
    overflow: hidden;
    margin: 7px 0 12px;
}

.aj-dashboard-redesign .dash-progress .dp-bar {
    height: 100%;
    border-radius: inherit;
    transition: width 0.5s ease;
}

/* ── Status grid ── */
.aj-dashboard-redesign .status-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.aj-dashboard-redesign .status-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.aj-dashboard-redesign .status-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-weight: 700;
    color: var(--dash-muted);
}

.aj-dashboard-redesign .s-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 7px;
    flex-shrink: 0;
}

/* ── Chart containers ── */
.aj-dashboard-redesign .chart-wrap {
    min-height: 290px;
}

/* ── Revenue highlight ── */
.aj-dashboard-redesign .rev-amount {
    font-size: 30px;
    font-weight: 900;
    color: var(--dash-blue);
    line-height: 1.1;
    margin: 4px 0 2px;
}

.aj-dashboard-redesign .rev-month {
    font-size: 18px;
    font-weight: 700;
    color: var(--dash-text);
    margin-bottom: 8px;
}

/* ── Table ── */
.aj-dashboard-redesign .dash-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.aj-dashboard-redesign .dash-table thead th {
    padding: 11px 14px;
    background: #f7fbff;
    color: #66758a;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    border-bottom: 1px solid var(--dash-border);
    border-top: none;
    white-space: nowrap;
}

.aj-dashboard-redesign .dash-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #edf2f7;
    color: #42556d;
    font-weight: 600;
    vertical-align: middle;
}

.aj-dashboard-redesign .dash-table tbody tr:hover {
    background: rgba(11, 104, 209, 0.025);
}

.aj-dashboard-redesign .dash-table tbody tr:last-child td {
    border-bottom: none;
}

.aj-dashboard-redesign .c-name {
    font-weight: 800;
    color: var(--dash-text);
    display: block;
    margin-bottom: 2px;
    white-space: nowrap;
}

.aj-dashboard-redesign .c-email {
    color: var(--dash-muted);
    font-size: 11px;
    display: block;
    max-width: 160px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 500;
}

/* ── Badges — scoped, n'affectent pas les badges globaux Bootstrap ── */
.aj-dashboard-redesign .dash-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
}

.aj-dashboard-redesign .dash-badge.ok     { background: #e8fff4; color: var(--dash-green); }
.aj-dashboard-redesign .dash-badge.ko     { background: #fff0ef; color: var(--dash-red); }
.aj-dashboard-redesign .dash-badge.wait   { background: #fff4d8; color: #9a6b00; }
.aj-dashboard-redesign .dash-badge.info   { background: var(--dash-blue-soft); color: var(--dash-blue); }

/* ── Bouton soft ── */
.aj-dashboard-redesign .btn-dash {
    display: inline-block;
    background: var(--dash-blue-soft);
    color: var(--dash-blue);
    border: 1px solid var(--dash-blue-soft);
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    padding: 5px 12px;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s, border-color 0.2s;
    white-space: nowrap;
}

.aj-dashboard-redesign .btn-dash:hover {
    background: #d8e9ff;
    border-color: #d8e9ff;
    color: var(--dash-blue-dark);
}

/* ── Agences list ── */
.aj-dashboard-redesign .branch-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f2f5f9;
}

.aj-dashboard-redesign .branch-row:last-child {
    border-bottom: none;
}

.aj-dashboard-redesign .branch-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
    background: #e7fff4;
    color: var(--dash-green);
}

/* ── Animations ── */
.aj-dashboard-redesign .fade-in {
    animation: dashFadeIn 0.45s ease both;
}

.aj-dashboard-redesign .fade-in.d1 { animation-delay: 0.06s; }
.aj-dashboard-redesign .fade-in.d2 { animation-delay: 0.12s; }
.aj-dashboard-redesign .fade-in.d3 { animation-delay: 0.18s; }
.aj-dashboard-redesign .fade-in.d4 { animation-delay: 0.24s; }
.aj-dashboard-redesign .fade-in.d5 { animation-delay: 0.30s; }

@keyframes dashFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ── */
@media (max-width: 1200px) {
    .aj-dashboard-redesign .status-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .aj-dashboard-redesign .dash-page-header { flex-direction: column; }
    .aj-dashboard-redesign .kpi-value        { font-size: 26px; }
    .aj-dashboard-redesign .status-grid      { grid-template-columns: 1fr; }
    .aj-dashboard-redesign .dash-table th,
    .aj-dashboard-redesign .dash-table td    { padding: 9px 10px; }
}
</style>
@endpush

@section('content')
<div class="aj-dashboard-redesign">

    {{-- ── En-tête de page ── --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="dash-page-header">
                <div>
                    <h4>Tableau de bord</h4>
                    <p>{{ now()->locale('fr')->translatedFormat('l d F Y · H:i') }}</p>
                </div>
                <ol class="breadcrumb mb-0 align-self-start">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item active">Vue globale</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- ── KPI Cards — 4 colonnes ── --}}
    <div class="row g-3 mb-4">

        {{-- Voyages --}}
        <div class="col-xl-3 col-md-6 col-sm-6 fade-in d1">
            <div class="card dash-card h-100">
                <div class="kpi-body">
                    <div class="kpi-row">
                        <div class="kpi-icon c-blue"><i class="bx bx-map"></i></div>
                        <div>
                            <p class="kpi-label">Voyages</p>
                            <h3 class="kpi-value">{{ $stats['voyages_count'] }}</h3>
                        </div>
                    </div>
                    @if(!empty($stats['voyages_featured']) && $stats['voyages_featured'] > 0)
                        <span class="kpi-footer">{{ $stats['voyages_featured'] }} en vedette</span>
                    @endif
                    <a href="{{ route('admin.circuits.voyages.index') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>

        {{-- Agences --}}
        <div class="col-xl-3 col-md-6 col-sm-6 fade-in d2">
            <div class="card dash-card h-100">
                <div class="kpi-body">
                    <div class="kpi-row">
                        <div class="kpi-icon c-green"><i class="bx bx-building-house"></i></div>
                        <div>
                            <p class="kpi-label">Agences</p>
                            <h3 class="kpi-value">{{ $stats['branches_count'] }}</h3>
                        </div>
                    </div>
                    <span class="kpi-footer">{{ $stats['branches_active'] }} actives</span>
                    <a href="{{ route('admin.branches.index') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>

        {{-- Réservations --}}
        <div class="col-xl-3 col-md-6 col-sm-6 fade-in d3">
            <div class="card dash-card h-100">
                <div class="kpi-body">
                    <div class="kpi-row">
                        <div class="kpi-icon c-cyan"><i class="bx bx-calendar-check"></i></div>
                        <div>
                            <p class="kpi-label">Réservations</p>
                            <h3 class="kpi-value">{{ $stats['reservations_total'] }}</h3>
                        </div>
                    </div>
                    <span class="kpi-footer">
                        @if($stats['reservations_month_evolution'] >= 0)
                            <i class="bx bx-up-arrow-alt" style="color:var(--dash-green);"></i>
                            <span style="color:var(--dash-green);">+{{ $stats['reservations_month_evolution'] }}%</span>
                        @else
                            <i class="bx bx-down-arrow-alt" style="color:var(--dash-red);"></i>
                            <span style="color:var(--dash-red);">{{ $stats['reservations_month_evolution'] }}%</span>
                        @endif
                        vs mois dernier
                    </span>
                    <a href="{{ route('admin.reservations.index') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>

        {{-- Clients --}}
        <div class="col-xl-3 col-md-6 col-sm-6 fade-in d4">
            <div class="card dash-card h-100">
                <div class="kpi-body">
                    <div class="kpi-row">
                        <div class="kpi-icon c-orange"><i class="bx bx-user"></i></div>
                        <div>
                            <p class="kpi-label">Clients</p>
                            <h3 class="kpi-value">{{ $stats['clients_count'] }}</h3>
                        </div>
                    </div>
                    <a href="{{ route('admin.customers.clients.index') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>

    </div>{{-- /KPI --}}

    {{-- ── Widgets : Activité + CA + Messages ── --}}
    <div class="row g-3 mb-4">

        {{-- Activité récente --}}
        <div class="col-xl-4 col-md-6 fade-in d2">
            <div class="card h-100">
                <div class="w-box">
                    <h6 class="w-title">Activité récente</h6>

                    <div class="m-row"><span>Aujourd'hui</span><strong>{{ $stats['reservations_today'] }}</strong></div>
                    <div class="dash-progress">
                        @php $pDay = min(100, ($stats['reservations_today'] / max(1, $stats['reservations_this_week'])) * 100); @endphp
                        <div class="dp-bar" style="background:var(--dash-blue); width:{{ $pDay }}%;"></div>
                    </div>

                    <div class="m-row"><span>Cette semaine</span><strong>{{ $stats['reservations_this_week'] }}</strong></div>
                    <div class="dash-progress">
                        @php $pWeek = min(100, ($stats['reservations_this_week'] / max(1, $stats['reservations_this_month'])) * 100); @endphp
                        <div class="dp-bar" style="background:#0dcaf0; width:{{ $pWeek }}%;"></div>
                    </div>

                    <div class="m-row"><span>Ce mois</span><strong>{{ $stats['reservations_this_month'] }}</strong></div>
                </div>
            </div>
        </div>

        {{-- Chiffre d'affaires --}}
        <div class="col-xl-4 col-md-6 fade-in d3">
            <div class="card h-100" style="border-left: 3px solid var(--dash-blue) !important;">
                <div class="w-box">
                    <h6 class="w-title">Chiffre d'affaires</h6>
                    <p class="text-muted mb-1" style="font-size:12px;">Total validé</p>
                    <p class="rev-amount">{{ number_format($stats['revenue_total'], 0, ',', ' ') }} <small style="font-size:13px; font-weight:500; color:var(--dash-muted);">€</small></p>
                    <p class="text-muted mb-1" style="font-size:12px;">Ce mois</p>
                    <p class="rev-month">{{ number_format($stats['revenue_this_month'], 0, ',', ' ') }} €</p>
                    @if($stats['revenue_month_evolution'] >= 0)
                        <span class="dash-badge ok"><i class="bx bx-trending-up"></i> +{{ $stats['revenue_month_evolution'] }}% vs mois dernier</span>
                    @else
                        <span class="dash-badge ko"><i class="bx bx-trending-down"></i> {{ $stats['revenue_month_evolution'] }}% vs mois dernier</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="col-xl-4 col-md-6 fade-in d4">
            <div class="card h-100">
                <div class="w-box d-flex justify-content-between align-items-center h-100">
                    <div>
                        <h6 class="w-title">Messages</h6>
                        <p class="text-muted mb-1" style="font-size:12px;">Boîte réservations</p>
                        <p class="kpi-value mb-0">{{ $stats['messages_count'] }}</p>
                    </div>
                    <a href="{{ route('admin.messagerie.index') }}" class="btn btn-primary btn-sm flex-shrink-0 ms-3">
                        <i class="bx bx-envelope me-1"></i>Ouvrir
                    </a>
                </div>
            </div>
        </div>

    </div>{{-- /Widgets --}}

    {{-- ── Répartition des réservations ── --}}
    <div class="row g-3 mb-4">
        <div class="col-12 fade-in d3">
            <div class="card">
                <div class="w-box">
                    <h6 class="w-title">Répartition des réservations</h6>
                    @php $rsTotal = $stats['reservations_total'] ?: 1; @endphp
                    <div class="status-grid">

                        <div class="status-item">
                            <div class="status-line">
                                <span><span class="s-dot" style="background:#f7b500;"></span>En attente</span>
                                <strong>{{ $stats['reservations_en_cours'] }}</strong>
                            </div>
                            <div class="dash-progress">
                                <div class="dp-bar" style="background:#f7b500; width:{{ ($stats['reservations_en_cours'] / $rsTotal) * 100 }}%;"></div>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-line">
                                <span><span class="s-dot" style="background:var(--dash-green);"></span>Validées</span>
                                <strong>{{ $stats['reservations_validees'] }}</strong>
                            </div>
                            <div class="dash-progress">
                                <div class="dp-bar" style="background:var(--dash-green); width:{{ ($stats['reservations_validees'] / $rsTotal) * 100 }}%;"></div>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-line">
                                <span><span class="s-dot" style="background:var(--dash-red);"></span>Annulées</span>
                                <strong>{{ $stats['reservations_annulees'] }}</strong>
                            </div>
                            <div class="dash-progress">
                                <div class="dp-bar" style="background:var(--dash-red); width:{{ ($stats['reservations_annulees'] / $rsTotal) * 100 }}%;"></div>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-line">
                                <span><span class="s-dot" style="background:#aab6c5;"></span>Total</span>
                                <strong>{{ $rsTotal }}</strong>
                            </div>
                            <div class="dash-progress">
                                <div class="dp-bar" style="background:#e6edf5; width:100%;"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>{{-- /Status --}}

    {{-- ── Graphiques ── --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-8 fade-in d4">
            <div class="card">
                <div class="w-box">
                    <h6 class="w-title">Réservations & CA (6 mois)</h6>
                    <div id="aj-chart-line" class="chart-wrap"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 fade-in d4">
            <div class="card">
                <div class="w-box">
                    <h6 class="w-title">Statut des réservations</h6>
                    <div id="aj-chart-donut" class="chart-wrap"></div>
                </div>
            </div>
        </div>
    </div>{{-- /Charts --}}

    {{-- ── Paiements + Dernières réservations ── --}}
    <div class="row g-3 mb-4">

        @if(count($stats['payment_labels']) > 0)
        <div class="col-xl-4 fade-in d4">
            <div class="card h-100">
                <div class="w-box">
                    <h6 class="w-title">Paiements (validées)</h6>
                    <div id="aj-chart-payment" class="chart-wrap" style="min-height:220px;"></div>
                </div>
            </div>
        </div>
        @endif

        <div class="{{ count($stats['payment_labels']) > 0 ? 'col-xl-8' : 'col-12' }} fade-in d4">
            <div class="card">
                <div class="w-box">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="w-title mb-0">Dernières réservations</h6>
                        <a href="{{ route('admin.reservations.index') }}" class="btn btn-sm btn-outline-primary">
                            Toutes <i class="bx bx-right-arrow-alt"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="dash-table">
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
                                        <span class="c-name">{{ $r->client_first_name }} {{ $r->client_last_name }}</span>
                                        @if($r->client_email)
                                            <span class="c-email">{{ Str::limit($r->client_email, 25) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($r->tour)
                                            {{ Str::limit($r->tour->name, 28) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($r->status === \App\Models\Reservation::STATUS_VALIDEE)
                                            <span class="dash-badge ok">Validée</span>
                                        @elseif($r->status === \App\Models\Reservation::STATUS_ANNULEE)
                                            <span class="dash-badge ko">Annulée</span>
                                        @else
                                            <span class="dash-badge wait">En cours</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $r->payment_type ?: '—' }}</small></td>
                                    <td>
                                        @php $tot = ($r->base_price ?? 0) + ($r->room_supplement_total ?? 0); @endphp
                                        @if($tot > 0)
                                            <strong>{{ number_format($tot, 0, ',', ' ') }} €</strong>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $r->created_at->format('d/m/Y H:i') }}</small></td>
                                    <td>
                                        <a href="{{ route('admin.reservations.edit', $r) }}" class="btn-dash">Voir</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Aucune réservation.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /Payments + Reservations --}}

    {{-- ── Voyages les plus réservés + Agences actives ── --}}
    <div class="row g-3 mb-4">

        <div class="col-xl-6 fade-in d5">
            <div class="card">
                <div class="w-box">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="w-title mb-0">Voyages les plus réservés</h6>
                        <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-sm btn-outline-primary">Tous</a>
                    </div>
                    <div class="table-responsive">
                        <table class="dash-table">
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
                                    <td><a href="{{ route('admin.circuits.voyages.edit', $row['voyage']->id) }}" class="btn-dash">Voir</a></td>
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

        <div class="col-xl-6 fade-in d5">
            <div class="card">
                <div class="w-box">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="w-title mb-0">Agences actives</h6>
                        <a href="{{ route('admin.branches.index') }}" class="btn btn-sm btn-outline-primary">Toutes</a>
                    </div>
                    @forelse($recentBranches as $b)
                    <div class="branch-row">
                        <div class="branch-icon"><i class="bx bx-building-house"></i></div>
                        <div class="flex-grow-1" style="min-width:0;">
                            <span class="c-name">{{ $b->name }}</span>
                            @if($b->city)
                                <span class="c-email">{{ $b->city }}{{ $b->code ? ' · '.$b->code : '' }}</span>
                            @endif
                        </div>
                        <a href="{{ route('admin.branches.edit', $b) }}" class="btn-dash flex-shrink-0">Voir</a>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">Aucune agence.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>{{-- /Bottom --}}

</div>{{-- /.aj-dashboard-redesign --}}
@endsection

@push('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Combo : Réservations (colonnes) + CA (ligne) ──
    new ApexCharts(document.querySelector('#aj-chart-line'), {
        series: [
            { name: 'Réservations',          type: 'column', data: @json($stats['chart_reservations']) },
            { name: "Chiffre d'affaires (€)", type: 'line',   data: @json($stats['chart_revenue']) }
        ],
        chart: {
            height: 290, type: 'line',
            toolbar: { show: false }, zoom: { enabled: false },
            animations: { enabled: true, speed: 500 }
        },
        colors: ['#405189', '#19b982'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: [0, 2] },
        fill:   { type: 'solid', opacity: [0.85, 0] },
        xaxis:  { categories: @json($stats['chart_months']) },
        yaxis: [
            { title: { text: 'Réservations' }, labels: { formatter: v => Math.round(v) } },
            { opposite: true, title: { text: 'CA (€)' }, labels: { formatter: v => v >= 1000 ? (v/1000).toFixed(0)+'k' : v } }
        ],
        legend: { position: 'top', horizontalAlign: 'right' },
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 3 } }
    }).render();

    // ── Donut : Statuts ──
    new ApexCharts(document.querySelector('#aj-chart-donut'), {
        series: @json($stats['donut_series']),
        chart: { height: 270, type: 'donut', animations: { enabled: true, speed: 500 } },
        labels: @json($stats['donut_labels']),
        plotOptions: {
            pie: { donut: { size: '62%', labels: { show: true,
                total: { show: true, label: 'Total',
                    formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0) }
            }}}
        },
        legend: { position: 'bottom' },
        colors: ['#f7b500', '#19b982', '#ef4d45']
    }).render();

    // ── Barres horizontales : Paiements ──
    @if(count($stats['payment_labels']) > 0)
    new ApexCharts(document.querySelector('#aj-chart-payment'), {
        series: [{ name: 'Paiements', data: @json($stats['payment_series']) }],
        chart: { type: 'bar', height: 220, toolbar: { show: false }, animations: { enabled: true, speed: 500 } },
        plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 4 } },
        dataLabels: { enabled: true },
        xaxis: { categories: @json($stats['payment_labels']) },
        colors: ['#0b68d1'],
        legend: { show: false }
    }).render();
    @endif

});
</script>
@endpush
