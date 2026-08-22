@extends('layouts.admin-v6')

@section('title', 'Espace Admin')
@section('page_title', 'Espace Admin')

@php
    // Le fichier HTML de la maquette V6 reste la source du design (CSS uniquement).
    $source = file_get_contents(resource_path('views/admin/dashboard/dashboard_v6_ajinsafro_kpi_v4.html'));
    preg_match('/<style>([\s\S]*?)<\/style>/i', $source, $styleMatch);
    $v6Css = trim($styleMatch[1] ?? '');

    $stats = $dashboardV5['stats'] ?? [];
    $breakdown = $dashboardV5['reservationBreakdown'] ?? ['pending' => 0, 'confirmed' => 0, 'cancelled' => 0, 'total' => 0, 'pending_pct' => 0, 'confirmed_pct' => 0, 'cancelled_pct' => 0];
    $destinations = $dashboardV5['destinations'] ?? ['total' => 0, 'segments' => []];
    $upcomingDepartures = $dashboardV5['upcomingDepartures'] ?? [];
    $latestReservations = array_slice($dashboardV5['latestReservations'] ?? [], 0, 4);
    $alerts = $dashboardV5['alerts'] ?? [];
    $quality = $dashboardV5['quality'] ?? [];
    $channels = $dashboardV5['channels'] ?? [];
    $objective = $dashboardV5['objective'] ?? ['revenue_month' => 0.0, 'target' => 0.0, 'progress' => null, 'remaining' => null, 'currency' => 'DH'];
    $chart = $dashboardV5['performanceChart'] ?? ['has_data' => false];
    $paymentMethods = $dashboardV5['paymentMethods'] ?? [];
    $topTours = $dashboardV5['topTours'] ?? [];
    $activeAgencies = $dashboardV5['activeAgencies'] ?? [];
    $confirmationWeekEvolution = (float) ($dashboardV5['confirmationWeekEvolution'] ?? 0);

    $currency = (string) ($stats['currency'] ?? 'DH');
    $fmtMoney = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $fmtEvolution = function (float $value): string {
        $arrow = $value >= 0 ? '↗' : '↘';
        return $arrow . ' ' . number_format(abs($value), 1, ',', ' ') . '%';
    };

    $reservationStatusMap = [
        'confirmed' => ['Confirmée', 'green'],
        'paid' => ['Payée', 'green'],
        'partially_paid' => ['Acompte', 'green'],
        'pending' => ['En attente', 'orange'],
        'draft' => ['Brouillon', 'orange'],
        'option' => ['Option', 'orange'],
        'expired' => ['Expirée', 'orange'],
        'cancelled' => ['Annulée', 'red'],
        'refunded' => ['Remboursée', 'red'],
    ];

    // Donut destinations : segments cumulés du conic-gradient.
    $donutStops = [];
    $cursor = 0;
    foreach ($destinations['segments'] as $segment) {
        $end = min(100, $cursor + $segment['percent']);
        $donutStops[] = $segment['color'] . ' ' . $cursor . '% ' . $end . '%';
        $cursor = $end;
    }
    if ($cursor < 100) {
        $donutStops[] = '#cdd5df ' . $cursor . '% 100%';
    }
    $donutGradient = 'conic-gradient(' . implode(', ', $donutStops) . ')';

    $confirmedDeg = round(min(100, max(0, (float) $breakdown['confirmed_pct'])) * 1.8, 1);

    // Petit donut statuts : en cours (orange) / validés (vert) / annulés (rose).
    $statusPendingEnd = min(100, round((float) $breakdown['pending_pct']));
    $statusConfirmedEnd = min(100, $statusPendingEnd + round((float) $breakdown['confirmed_pct']));
    $statusDonutGradient = 'conic-gradient(var(--aj-orange) 0 ' . $statusPendingEnd . '%, var(--aj-green) ' . $statusPendingEnd . '% ' . $statusConfirmedEnd . '%, #ff4d7d ' . $statusConfirmedEnd . '% 100%)';

    $departuresUrl = \Illuminate\Support\Facades\Route::has('admin.circuits.departs-dates') ? route('admin.circuits.departs-dates') : null;
    $calendarUrl = \Illuminate\Support\Facades\Route::has('admin.reservations.calendrier') ? route('admin.reservations.calendrier') : null;
    $reservationsUrl = \Illuminate\Support\Facades\Route::has('admin.reservation-dossiers.index') ? route('admin.reservation-dossiers.index') : null;
    $alertsUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard.alertes') ? route('admin.dashboard.alertes') : null;
    $voyagesUrl = \Illuminate\Support\Facades\Route::has('admin.circuits.voyages.index') ? route('admin.circuits.voyages.index') : null;
    $agenciesUrl = \Illuminate\Support\Facades\Route::has('admin.points-of-sale.index') ? route('admin.points-of-sale.index') : null;
    $paymentsUrl = \Illuminate\Support\Facades\Route::has('admin.finance.paiements') ? route('admin.finance.paiements') : null;
@endphp

@push('styles')
<style>
{{ $v6Css }}

/* Dashboard V6 Laravel fixes: stable collapse + 1280/1366 responsive density. */
.app-shell {
  height: 100vh;
  overflow: hidden;
}

/* Jauge et donuts pilotés par les données réelles (le dégradé de la maquette est remplacé). */
.dashboard-v6 .gauge::before { display: none !important; }
.dashboard-v6 .gauge-fill {
  position: absolute;
  inset: 0;
  border-radius: 220px 220px 0 0;
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
}

/* Dashboard V6 compact mode (no zoom/scale, pure sizing compaction). */
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

.dac-dashboard-widgets {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
  margin: 12px 14px 0;
}
.dac-dashboard-widget {
  background: #fff;
  border: 1px solid #dde7f0;
  border-radius: 8px;
  padding: 12px;
}
.dac-dashboard-widget span {
  display: block;
  color: #66758a;
  font-size: 11px;
  font-weight: 600;
}
.dac-dashboard-widget strong {
  display: block;
  margin-top: 4px;
  color: #10233f;
  font-size: 20px;
  font-weight: 600;
}
@media (max-width: 1180px) { .dac-dashboard-widgets { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 760px) { .dac-dashboard-widgets { grid-template-columns: 1fr; margin: 10px; } }

@media (max-width: 1366px) {
  .dashboard-v6 .content { padding: 10px 10px 14px !important; gap: 9px !important; }
  .dashboard-v6 .kpi-grid { gap: 10px !important; }
  .dashboard-v6 .kpi-card { min-height: 78px !important; }
}
</style>
@endpush

@section('content')
@if(!empty($customRequestWidgets))
    <div class="dac-dashboard-widgets">
        @foreach($customRequestWidgets as $widget)
            <a href="{{ route('admin.custom-requests.index') }}" class="dac-dashboard-widget text-decoration-none">
                <span>{{ $widget['label'] }}</span>
                <strong>{{ $widget['value'] }}</strong>
            </a>
        @endforeach
    </div>
@endif

<div class="dashboard-v6 dashboard-v6-embedded">
<section class="content">
    {{-- ─── KPIs ──────────────────────────────────────────────────────── --}}
    <section class="kpi-grid" aria-label="Indicateurs principaux">
        <article class="kpi-card kpi-blue">
            <div class="kpi-left">
                <div class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 19V7.5A2.5 2.5 0 0 1 6.5 5H9l2-2h2l2 2h2.5A2.5 2.5 0 0 1 20 7.5V19a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><path d="M4 10h16"/><path d="M9 5v16"/><path d="M15 5v16"/></svg>
                </div>
                <div class="kpi-info">
                    <div class="kpi-title">Voyages</div>
                    <div class="kpi-number">{{ $fmtMoney($stats['voyages'] ?? 0) }}</div>
                    <div class="kpi-change">↗ Catalogue actif</div>
                    <div class="kpi-note">Voyages publiés</div>
                </div>
            </div>
            <svg class="kpi-sparkline" viewBox="0 0 100 42" aria-hidden="true">
                <path class="area" d="M2 34 C14 29 20 31 30 24 S48 27 58 19 S76 20 88 13 S96 18 100 15 L100 42 L2 42 Z"/>
                <path class="line" d="M2 34 C14 29 20 31 30 24 S48 27 58 19 S76 20 88 13 S96 18 100 15"/>
            </svg>
        </article>

        <article class="kpi-card kpi-green">
            <div class="kpi-left">
                <div class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 21V7l8-4 8 4v14"/><path d="M9 21v-7h6v7"/><path d="M8 9h.01"/><path d="M12 9h.01"/><path d="M16 9h.01"/></svg>
                </div>
                <div class="kpi-info">
                    <div class="kpi-title">Agences</div>
                    <div class="kpi-number">{{ $fmtMoney($stats['agencies'] ?? 0) }}</div>
                    <div class="kpi-change">↗ Réseau actif</div>
                    <div class="kpi-note">Points de vente actifs</div>
                </div>
            </div>
            <svg class="kpi-sparkline" viewBox="0 0 100 42" aria-hidden="true">
                <path class="area" d="M2 31 C12 28 20 29 30 26 S45 22 54 23 S70 14 80 18 S92 10 100 14 L100 42 L2 42 Z"/>
                <path class="line" d="M2 31 C12 28 20 29 30 26 S45 22 54 23 S70 14 80 18 S92 10 100 14"/>
            </svg>
        </article>

        <article class="kpi-card kpi-orange">
            <div class="kpi-left">
                <div class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3 4 7l8 4 8-4-8-4Z"/><path d="M4 12l8 4 8-4"/><path d="M4 17l8 4 8-4"/></svg>
                </div>
                <div class="kpi-info">
                    <div class="kpi-title">Réservations</div>
                    <div class="kpi-number">{{ $fmtMoney($stats['reservations'] ?? 0) }}</div>
                    <div class="kpi-change">{{ $fmtEvolution((float) ($stats['reservations_evolution'] ?? 0)) }} ce mois</div>
                    <div class="kpi-note">Total enregistré</div>
                </div>
            </div>
            <svg class="kpi-sparkline" viewBox="0 0 100 42" aria-hidden="true">
                <path class="area" d="M2 27 C15 26 22 25 33 24 S45 17 57 20 S68 10 80 12 S92 23 100 18 L100 42 L2 42 Z"/>
                <path class="line" d="M2 27 C15 26 22 25 33 24 S45 17 57 20 S68 10 80 12 S92 23 100 18"/>
            </svg>
        </article>

        <article class="kpi-card kpi-cyan">
            <div class="kpi-left">
                <div class="kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="kpi-info">
                    <div class="kpi-title">Clients</div>
                    <div class="kpi-number">{{ $fmtMoney($stats['clients'] ?? 0) }}</div>
                    <div class="kpi-change">↗ Base active</div>
                    <div class="kpi-note">Clients enregistrés</div>
                </div>
            </div>
            <svg class="kpi-sparkline" viewBox="0 0 100 42" aria-hidden="true">
                <path class="area" d="M2 26 C14 23 22 24 33 23 S48 27 58 19 S70 8 80 15 S92 27 100 17 L100 42 L2 42 Z"/>
                <path class="line" d="M2 26 C14 23 22 24 33 23 S48 27 58 19 S70 8 80 15 S92 27 100 17"/>
            </svg>
        </article>
    </section>

    {{-- ─── Ligne 1 : performance, destinations, taux de confirmation ─── --}}
    <section class="dashboard-grid">
        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Performance commerciale</h2>
                        <div class="panel-subtitle">CA (orange) et volume de réservations (bleu) sur 6 mois</div>
                    </div>
                </div>
                <div class="chart-wrap">
                    @if(($chart['has_data'] ?? false))
                        <svg class="chart-svg" viewBox="0 0 760 300" role="img" aria-label="Courbe de performance commerciale">
                            <defs>
                                <linearGradient id="orangeAreaV6" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#ff9300" stop-opacity="0.34" />
                                    <stop offset="100%" stop-color="#ff9300" stop-opacity="0.04" />
                                </linearGradient>
                            </defs>
                            <line class="grid-line" x1="62" y1="46" x2="728" y2="46" />
                            <line class="grid-line" x1="62" y1="96" x2="728" y2="96" />
                            <line class="grid-line" x1="62" y1="146" x2="728" y2="146" />
                            <line class="grid-line" x1="62" y1="196" x2="728" y2="196" />
                            <line class="grid-line" x1="62" y1="246" x2="728" y2="246" />
                            @foreach(($chart['y_labels'] ?? []) as $yLabel)
                                <text class="axis-label" x="14" y="{{ $yLabel['y'] }}">{{ $yLabel['label'] }}</text>
                            @endforeach
                            <text class="axis-label" x="35" y="250">0</text>
                            <path class="area-fill" d="{{ $chart['revenue_area'] }}" />
                            <path class="line-main" d="{{ $chart['revenue_line'] }}" />
                            <path class="line-blue" d="{{ $chart['volume_line'] }}" />
                            <circle cx="{{ $chart['peak']['x'] }}" cy="{{ $chart['peak']['y'] }}" r="7" fill="#fff" stroke="#ff9300" stroke-width="4" />
                            <rect class="tooltip-box" x="{{ min(670, $chart['peak']['x'] + 14) }}" y="{{ max(10, $chart['peak']['y'] - 22) }}" width="76" height="30" rx="7" />
                            <text class="tooltip-text" x="{{ min(682, $chart['peak']['x'] + 26) }}" y="{{ max(29, $chart['peak']['y'] - 3) }}">{{ $chart['peak']['label'] }}</text>
                            @foreach(($chart['month_labels'] ?? []) as $monthLabel)
                                <text class="axis-label" x="{{ max(20, $monthLabel['x'] - 14) }}" y="280">{{ $monthLabel['label'] }}</text>
                            @endforeach
                        </svg>
                    @else
                        <div class="empty-state">Aucune réservation sur les 6 derniers mois.</div>
                    @endif
                </div>
            </div>
        </article>

        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Réservations par destination</h2>
                        <div class="panel-subtitle">Répartition commerciale</div>
                    </div>
                </div>
                @if(($destinations['total'] ?? 0) > 0)
                    <div class="destination-layout">
                        <div class="donut" style="background: {{ $donutGradient }}">
                            <div class="donut-center"><strong>{{ $fmtMoney($destinations['total']) }}</strong><span>Réservations</span></div>
                        </div>
                        <div class="legend">
                            @foreach($destinations['segments'] as $segment)
                                <div class="legend-row"><span class="legend-dot" style="background:{{ $segment['color'] }}"></span><span>{{ $segment['label'] }}</span><span class="legend-value">{{ $segment['percent'] }}%</span></div>
                            @endforeach
                        </div>
                    </div>
                    @if($reservationsUrl)
                        <div style="margin-top:18px"><a class="link-orange" href="{{ $reservationsUrl }}">Voir le détail des réservations →</a></div>
                    @endif
                @else
                    <div class="empty-state">Aucune réservation par destination.</div>
                @endif
            </div>
        </article>

        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Taux de confirmation</h2>
                        <div class="panel-subtitle">Confirmées / en attente</div>
                    </div>
                </div>
                <div class="gauge-wrap">
                    <div>
                        <div class="gauge">
                            <div class="gauge-fill" style="background: conic-gradient(from 270deg at 50% 100%, var(--aj-orange) 0 {{ $confirmedDeg }}deg, #e5eaf0 {{ $confirmedDeg }}deg 180deg, transparent 180deg 360deg)"></div>
                            <div class="gauge-value"><strong>{{ round($breakdown['confirmed_pct']) }}%</strong><span>Confirmées</span></div>
                        </div>
                        <div class="success-note">{{ $fmtEvolution($confirmationWeekEvolution) }} vs semaine précédente</div>
                    </div>
                </div>
            </div>
        </article>
    </section>

    {{-- ─── Ligne 2 : départs, réservations récentes, alertes ─────────── --}}
    <section class="dashboard-grid middle">
        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Départs à venir</h2>
                        <div class="panel-subtitle">Suivi capacité, ventes, urgence et disponibilité</div>
                    </div>
                    @if($calendarUrl)
                        <a class="btn-mini" style="display:inline-flex;align-items:center;text-decoration:none" href="{{ $calendarUrl }}">Calendrier</a>
                    @endif
                </div>
                @if($upcomingDepartures !== [])
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Date</th><th>Destination</th><th>Circuit</th><th>Places dispo.</th><th>Statut</th></tr></thead>
                            <tbody>
                                @foreach($upcomingDepartures as $departure)
                                    <tr>
                                        <td>{{ $departure['date'] }}</td>
                                        <td>{{ $departure['destination'] }}</td>
                                        <td>{{ $departure['voyage'] }}</td>
                                        <td>{{ $departure['available'] }} / {{ $departure['total'] }}</td>
                                        <td><span class="status {{ $departure['status_color'] }}">{{ $departure['status_label'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($departuresUrl)
                        <div style="text-align:center;margin-top:14px"><a class="link-orange" href="{{ $departuresUrl }}">Voir tous les départs →</a></div>
                    @endif
                @else
                    <div class="empty-state">Aucun départ programmé à venir.</div>
                @endif
            </div>
        </article>

        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Réservations récentes</h2>
                        <div class="panel-subtitle">Dernières ventes et demandes client</div>
                    </div>
                    @if($reservationsUrl)
                        <a class="link-orange" href="{{ $reservationsUrl }}">Voir tout</a>
                    @endif
                </div>
                @if($latestReservations !== [])
                    <div class="reservation-list">
                        @foreach($latestReservations as $reservation)
                            @php
                                $initials = strtoupper(collect(preg_split('/\s+/', trim($reservation['client_name'])))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: 'CL';
                                [$statusLabel, $statusColor] = $reservationStatusMap[$reservation['status']] ?? [ucfirst($reservation['status']), 'blue'];
                                $subtitleParts = array_filter([$reservation['tour_name'] ?: null, $reservation['payment'] ?: null]);
                            @endphp
                            <div class="reservation-item">
                                <div class="mini-avatar">{{ $initials }}</div>
                                <div>
                                    <div class="item-title">{{ $reservation['client_name'] }}</div>
                                    <div class="item-subtitle">{{ implode(' · ', $subtitleParts) ?: 'Réservation #' . $reservation['id'] }}</div>
                                </div>
                                <div class="item-amount">{{ $fmtMoney($reservation['amount']) }} {{ $reservation['currency'] }}<br><span class="status {{ $statusColor }}">{{ $statusLabel }}</span></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Aucune réservation récente.</div>
                @endif
            </div>
        </article>

        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Alertes & tâches</h2>
                        <div class="panel-subtitle">Suivi du pilotage quotidien</div>
                    </div>
                    @if($alertsUrl)
                        <a class="link-orange" href="{{ $alertsUrl }}">Voir tout</a>
                    @endif
                </div>
                <div class="alert-list">
                    @foreach($alerts as $alert)
                        <div class="alert-item">
                            <div class="soft-icon {{ $alert['color'] }}">{{ $alert['icon'] }}</div>
                            <div>
                                <div class="item-title">{{ $alert['label'] }}</div>
                                <div class="item-subtitle">{{ $alert['subtitle'] }}</div>
                            </div>
                            <div class="metric-pill">{{ $alert['value'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top:16px" class="objective-card">
                    <div class="objective-label">Objectif mensuel</div>
                    <div class="objective-value"><strong>{{ round($breakdown['confirmed_pct']) }}%</strong><span>de confirmation</span></div>
                    <div class="progress-bar"><span style="width:{{ min(100, round($breakdown['confirmed_pct'])) }}%"></span></div>
                    <div class="objective-note">{{ $breakdown['pending'] }} réservation(s) en attente à confirmer sur le mois en cours.</div>
                </div>
            </div>
        </article>
    </section>

    {{-- ─── Ligne 3 : canaux, qualité, CA ─────────────────────────────── --}}
    <section class="dashboard-grid bottom">
        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Ventes par canal</h2>
                        <div class="panel-subtitle">Agence, commercial, client web et partenaires</div>
                    </div>
                </div>
                @if($channels !== [])
                    <div class="channel-list">
                        @foreach($channels as $channel)
                            <div class="channel-item">
                                <div class="channel-head">
                                    <div><strong>{{ $channel['label'] }}</strong><br><span>Réservations enregistrées</span></div>
                                    <div class="metric-pill">{{ $channel['count'] }}</div>
                                </div>
                                <div class="track"><span style="width:{{ $channel['percent'] }}%"></span></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Aucune vente enregistrée.</div>
                @endif
            </div>
        </article>

        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Qualité opérationnelle</h2>
                        <div class="panel-subtitle">Indicateurs de contrôle</div>
                    </div>
                </div>
                <div class="quality-list">
                    @foreach($quality as $indicator)
                        <div class="quality-item">
                            <div class="soft-icon {{ $indicator['color'] }}">{{ $indicator['icon'] }}</div>
                            <div>
                                <div class="item-title">{{ $indicator['label'] }}</div>
                                <div class="item-subtitle">{{ $indicator['subtitle'] }}</div>
                            </div>
                            <div class="metric-pill">{{ $indicator['value'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Chiffre d’affaires</h2>
                        <div class="panel-subtitle">Mois en cours et statuts</div>
                    </div>
                </div>
                <div class="objective-card">
                    <div class="objective-label">CA du mois</div>
                    <div class="objective-value"><strong>{{ $fmtMoney($objective['revenue_month']) }} {{ $currency }}</strong><span>encaissable</span></div>
                    @if(($objective['target'] ?? 0) > 0)
                        <div class="progress-bar"><span style="width:{{ $objective['progress'] }}%"></span></div>
                        <div class="objective-note">Encore {{ $fmtMoney($objective['remaining']) }} {{ $currency }} pour atteindre l’objectif de {{ $fmtMoney($objective['target']) }} {{ $currency }}.</div>
                    @else
                        <div class="progress-bar"><span style="width:{{ min(100, round($breakdown['confirmed_pct'])) }}%"></span></div>
                        <div class="objective-note">CA cumulé total : {{ $fmtMoney($stats['revenue_total'] ?? 0) }} {{ $currency }}.</div>
                    @endif
                </div>
                <div class="status-donut-small" style="background: {{ $statusDonutGradient }}">
                    <div><span>Total</span><strong>{{ $fmtMoney($breakdown['total']) }}</strong></div>
                </div>
                <div class="mini-legend">
                    <span><i style="background:var(--aj-orange)"></i>En attente ({{ $breakdown['pending'] }})</span>
                    <span><i style="background:var(--aj-green)"></i>Confirmées ({{ $breakdown['confirmed'] }})</span>
                    <span><i style="background:#ff4d7d"></i>Annulées ({{ $breakdown['cancelled'] }})</span>
                </div>
            </div>
        </article>
    </section>

    {{-- ─── Ligne 4 : paiements, top voyages, agences ─────────────────── --}}
    <section class="dashboard-grid bottom">
        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Paiements validés</h2>
                        <div class="panel-subtitle">Méthodes de paiement utilisées</div>
                    </div>
                    @if($paymentsUrl)
                        <a class="link-orange" href="{{ $paymentsUrl }}">Voir tout</a>
                    @endif
                </div>
                @if($paymentMethods !== [])
                    <div class="channel-list">
                        @foreach($paymentMethods as $method)
                            <div class="channel-item">
                                <div class="channel-head">
                                    <div><strong>{{ ucfirst($method['label']) }}</strong><br><span>{{ $method['percent'] }}% des paiements</span></div>
                                    <div class="metric-pill">{{ $method['count'] }}</div>
                                </div>
                                <div class="track"><span style="width:{{ $method['percent'] }}%"></span></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Aucune donnée de paiement disponible.</div>
                @endif
            </div>
        </article>

        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Voyages les plus réservés</h2>
                        <div class="panel-subtitle">Top destinations commerciales</div>
                    </div>
                    @if($voyagesUrl)
                        <a class="link-orange" href="{{ $voyagesUrl }}">Voir tous</a>
                    @endif
                </div>
                @if($topTours !== [])
                    <div class="agency-list">
                        @foreach($topTours as $tour)
                            <div class="agency-item">
                                <div class="soft-icon orange">★</div>
                                <div>
                                    <div class="item-title">{{ $tour['name'] }}</div>
                                    <div class="item-subtitle">{{ $tour['count'] }} réservation(s)</div>
                                </div>
                                <div class="metric-pill">{{ $tour['count'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Aucun voyage réservé.</div>
                @endif
            </div>
        </article>

        <article class="panel">
            <div class="panel-inner">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Agences actives</h2>
                        <div class="panel-subtitle">Points de vente avec activité</div>
                    </div>
                    @if($agenciesUrl)
                        <a class="link-orange" href="{{ $agenciesUrl }}">Voir toutes</a>
                    @endif
                </div>
                @if($activeAgencies !== [])
                    <div class="agency-list">
                        @foreach($activeAgencies as $agency)
                            <div class="agency-item">
                                <div class="soft-icon green">▦</div>
                                <div>
                                    <div class="item-title">{{ $agency['name'] }}</div>
                                    <div class="item-subtitle">{{ implode(' · ', array_filter([$agency['city'], $agency['code'], $agency['reservations_count'] . ' résa'])) }}</div>
                                </div>
                                <div class="status green">●</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">Aucune agence active.</div>
                @endif
            </div>
        </article>
    </section>
</section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
});
</script>
@endpush
