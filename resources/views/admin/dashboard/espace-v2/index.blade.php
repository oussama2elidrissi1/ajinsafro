@extends('layouts.espace-admin-v2')

@section('title', 'Espace Admin')

@php
    use Illuminate\Support\Facades\Route;

    $stats = $dashboardV5['stats'] ?? [];
    $breakdown = $dashboardV5['reservationBreakdown'] ?? ['pending' => 0, 'confirmed' => 0, 'cancelled' => 0, 'total' => 0, 'pending_pct' => 0, 'confirmed_pct' => 0, 'cancelled_pct' => 0];
    $destinations = $dashboardV5['destinations'] ?? ['total' => 0, 'segments' => []];
    $upcomingDepartures = $dashboardV5['upcomingDepartures'] ?? [];
    $latestReservations = array_slice($dashboardV5['latestReservations'] ?? [], 0, 6);
    $alerts = $dashboardV5['alerts'] ?? [];
    $chart = $dashboardV5['performanceChart'] ?? ['has_data' => false];
    $monthly = $dashboardV5['monthlyEvolution'] ?? [];
    $confirmationWeekEvolution = (float) ($dashboardV5['confirmationWeekEvolution'] ?? 0);
    $currency = (string) ($stats['currency'] ?? 'DH');

    $fmtNumber = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $fmtCompact = function (float $amount): string {
        if ($amount >= 1000000) {
            return rtrim(rtrim(number_format($amount / 1000000, 1, ',', ' '), '0'), ',') . ' M';
        }
        if ($amount >= 1000) {
            return number_format($amount / 1000, 0, ',', ' ') . ' k';
        }
        return number_format($amount, 0, ',', ' ');
    };
    $fmtPercent = fn (float $v) => number_format(abs($v), $v == (int) $v ? 0 : 1, ',', ' ') . ' %';

    $voyagesUrl = Route::has('admin.circuits.voyages.index') ? route('admin.circuits.voyages.index') : null;
    $agenciesUrl = Route::has('admin.points-of-sale.index') ? route('admin.points-of-sale.index') : null;
    $reservationsUrl = Route::has('admin.reservation-dossiers.index') ? route('admin.reservation-dossiers.index') : null;
    $clientsUrl = Route::has('admin.customers.clients.index') ? route('admin.customers.clients.index') : null;
    $departuresUrl = Route::has('admin.circuits.departs-dates') ? route('admin.circuits.departs-dates') : null;
    $calendarUrl = Route::has('admin.reservations.calendrier') ? route('admin.reservations.calendrier') : null;
    $alertsUrl = Route::has('admin.dashboard.alertes') ? route('admin.dashboard.alertes') : null;
    $customRequestsUrl = Route::has('admin.custom-requests.index') ? route('admin.custom-requests.index') : null;

    $primaryAction = null;
    $canCreateCustomRequest = false;
    try {
        $canCreateCustomRequest = auth()->user()?->can('custom_requests.create') ?? false;
    } catch (\Throwable $e) {
        $canCreateCustomRequest = false;
    }
    if ($canCreateCustomRequest && Route::has('admin.custom-requests.create')) {
        $primaryAction = ['label' => 'Nouvelle demande', 'href' => route('admin.custom-requests.create')];
    } elseif (Route::has('admin.reservations.create')) {
        $primaryAction = ['label' => 'Nouvelle réservation', 'href' => route('admin.reservations.create')];
    }

    // ─── KPI : widgets « demandes à la carte » (selon rôle), sinon indicateurs généraux.
    $kpiNotes = [
        'Total demandes à la carte' => 'total reçu',
        'Nouvelles demandes' => 'à traiter aujourd\'hui',
        'Confirmées' => 'dossiers validés',
        'Annulées' => 'sur la période',
        'Taux confirmation' => 'demandes à la carte',
        'Devis générés' => 'documents envoyés',
        'Demandes urgentes' => 'priorité haute',
        'En traitement' => 'cotations en cours',
        'Modifications demandées' => 'retours client',
        'Devis envoyés aujourd’hui' => 'documents envoyés',
        'Mes demandes en cours' => 'à suivre',
        'Devis reçus' => 'à valider',
        'En attente client' => 'réponse attendue',
    ];
    $kpiTones = [
        'Nouvelles demandes' => 'is-accent',
        'Demandes urgentes' => 'is-accent',
        'Devis reçus' => 'is-accent',
        'Devis générés' => 'is-primary',
        'Devis envoyés aujourd’hui' => 'is-primary',
        'Mes demandes en cours' => 'is-primary',
    ];
    $kpis = [];
    foreach (($customRequestWidgets ?? []) as $widget) {
        $label = (string) ($widget['label'] ?? '');
        $kpis[] = [
            'label' => $label === 'Total demandes à la carte' ? 'Demandes à la carte' : $label,
            'value' => $widget['value'] ?? 0,
            'note' => $kpiNotes[$label] ?? '',
            'tone' => $kpiTones[$label] ?? '',
            'href' => $customRequestsUrl,
        ];
    }
    if ($kpis === []) {
        $reservationsEvolution = (float) ($stats['reservations_evolution'] ?? 0);
        $kpis = [
            ['label' => 'Voyages', 'value' => $fmtNumber($stats['voyages'] ?? 0), 'note' => 'publiés', 'tone' => '', 'href' => $voyagesUrl],
            ['label' => 'Réservations', 'value' => $fmtNumber($stats['reservations'] ?? 0), 'note' => ($reservationsEvolution >= 0 ? '+' : '-') . $fmtPercent($reservationsEvolution) . ' ce mois', 'tone' => 'is-accent', 'href' => $reservationsUrl],
            ['label' => 'En attente', 'value' => $fmtNumber($breakdown['pending']), 'note' => 'à confirmer', 'tone' => '', 'href' => $reservationsUrl],
            ['label' => 'Confirmées', 'value' => $fmtNumber($breakdown['confirmed']), 'note' => 'dossiers validés', 'tone' => '', 'href' => $reservationsUrl],
            ['label' => 'Clients', 'value' => $fmtNumber($stats['clients'] ?? 0), 'note' => 'base active', 'tone' => '', 'href' => $clientsUrl],
            ['label' => 'CA du mois', 'value' => $fmtCompact((float) ($stats['revenue_month'] ?? 0)) . ' ' . $currency, 'note' => 'encaissable', 'tone' => 'is-primary', 'href' => null],
        ];
    }

    // ─── Performance commerciale : géométrie fournie par le service (x 62→728, y 46→246).
    $chartHasData = (bool) ($chart['has_data'] ?? false);
    $revenues = array_map(fn (array $m) => (float) ($m['revenue'] ?? 0), $monthly);
    $maxRevenue = $revenues !== [] ? max($revenues) : 0.0;
    $peakIndex = $revenues !== [] ? (int) array_search($maxRevenue, $revenues, true) : 0;
    $peakMonth = (string) ($monthly[$peakIndex]['label'] ?? '');
    $monthShort = fn (string $label) => mb_strtolower(explode(' ', trim($label))[0] ?? $label);
    $gridLabels = [$fmtCompact($maxRevenue), $fmtCompact($maxRevenue * 2 / 3), $fmtCompact($maxRevenue / 3), '0'];
    $peakLeft = max(0, min(100, ((float) ($chart['peak']['x'] ?? 62) - 62) / 666 * 100));
    $peakTop = max(0, min(100, ((float) ($chart['peak']['y'] ?? 246) - 46) / 200 * 100));

    // ─── Réservations récentes.
    $statusMap = [
        'confirmed' => ['Confirmée', 'is-green'],
        'paid' => ['Payée', 'is-green'],
        'partially_paid' => ['Acompte', 'is-green'],
        'pending' => ['En attente', 'is-yellow'],
        'draft' => ['Brouillon', 'is-yellow'],
        'option' => ['Option', 'is-yellow'],
        'expired' => ['Expirée', 'is-orange'],
        'shared_room_pending' => ['Chambre à jumeler', 'is-orange'],
        'shared_room_paired' => ['Chambre jumelée', 'is-green'],
        'cancelled' => ['Annulée', 'is-red'],
        'refunded' => ['Remboursée', 'is-red'],
    ];

    // ─── Taux de confirmation (demi-jauge : longueur d'arc = π × 48 ≈ 150,8).
    $confirmedPct = (int) round(min(100, max(0, (float) $breakdown['confirmed_pct'])));
    $gaugeOffset = round(150.8 * (1 - $confirmedPct / 100), 1);

    $destinationPalette = ['#144e8c', '#2e86c9', '#21a179', '#f5821f', '#cdd5df'];
    $departureBar = ['red' => 'ea-bar-red', 'orange' => 'ea-bar-orange', 'green' => 'ea-bar-blue'];
    $departureStatus = ['red' => 'ea-status-red', 'orange' => 'ea-status-orange', 'green' => 'ea-status-green'];
@endphp

@section('content')
    <div class="ea-page-head">
        <div>
            <span class="ea-eyebrow">Espace admin</span>
            <h1 class="ea-page-title">Vue d'ensemble</h1>
        </div>
        <div class="ea-page-actions">
            <span class="ea-pill">6 derniers mois</span>
            @if($primaryAction)
                <a href="{{ $primaryAction['href'] }}" class="ea-btn-accent">{{ $primaryAction['label'] }}</a>
            @endif
        </div>
    </div>

    <section class="ea-kpis" aria-label="Indicateurs principaux">
        @foreach($kpis as $kpi)
            @if($kpi['href'])
                <a href="{{ $kpi['href'] }}" class="ea-kpi {{ $kpi['tone'] }}">
            @else
                <div class="ea-kpi {{ $kpi['tone'] }}">
            @endif
                <div class="ea-kpi-label">{{ $kpi['label'] }}</div>
                <div class="ea-kpi-value">{{ $kpi['value'] }}</div>
                @if($kpi['note'] !== '')
                    <div class="ea-kpi-note">{{ $kpi['note'] }}</div>
                @endif
            @if($kpi['href'])
                </a>
            @else
                </div>
            @endif
        @endforeach
    </section>

    <section class="ea-main">
        <div class="ea-col-main">

            {{-- Performance commerciale --}}
            <article class="ea-card">
                <div class="ea-card-head">
                    <div>
                        <h2 class="ea-card-title">Performance commerciale</h2>
                        <p class="ea-card-sub">Chiffre d'affaires et volume de réservations sur 6 mois</p>
                    </div>
                    <div class="ea-legend">
                        <span><i style="background:var(--ea-accent)"></i>CA</span>
                        <span><i style="background:var(--ea-blue)"></i>Réservations</span>
                    </div>
                </div>
                @if($chartHasData)
                    <div class="ea-chart-figure">
                        <span class="ea-chart-value">{{ $fmtNumber($maxRevenue) }} {{ $currency }}</span>
                        @if($maxRevenue > 0)
                            <span class="ea-chart-peak">pic de saison — {{ $peakMonth }}</span>
                        @else
                            <span class="ea-chart-peak" style="color:var(--ea-muted)">aucun CA confirmé sur la période</span>
                        @endif
                    </div>
                    <div class="ea-chart">
                        <div class="ea-chart-grid" aria-hidden="true">
                            @foreach($gridLabels as $gridLabel)
                                <span>{{ $gridLabel }}</span>
                            @endforeach
                        </div>
                        <div class="ea-chart-plot">
                            <svg class="ea-chart-svg" viewBox="62 46 666 200" preserveAspectRatio="none" role="img" aria-label="Courbe de performance commerciale">
                                <defs>
                                    <linearGradient id="eaRevenueArea" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#f5821f" stop-opacity="0.2"></stop>
                                        <stop offset="100%" stop-color="#f5821f" stop-opacity="0"></stop>
                                    </linearGradient>
                                </defs>
                                <path d="{{ $chart['revenue_area'] }}" fill="url(#eaRevenueArea)"></path>
                                <path class="ea-chart-line is-revenue" d="{{ $chart['revenue_line'] }}"></path>
                                <path class="ea-chart-line is-volume" d="{{ $chart['volume_line'] }}"></path>
                            </svg>
                            @if($maxRevenue > 0)
                                <span class="ea-chart-dot" style="left:{{ $peakLeft }}%; top:{{ $peakTop }}%;" aria-hidden="true"></span>
                            @endif
                        </div>
                        <div class="ea-chart-x" aria-hidden="true">
                            @foreach($monthly as $index => $month)
                                <span class="{{ $index === $peakIndex && $maxRevenue > 0 ? 'is-peak' : '' }}">{{ $monthShort((string) $month['label']) }}</span>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="ea-empty">Aucune réservation sur les 6 derniers mois.</div>
                @endif
            </article>

            {{-- Départs à venir --}}
            <article class="ea-card">
                <div class="ea-card-head">
                    <div>
                        <h2 class="ea-card-title">Départs à venir</h2>
                        <p class="ea-card-sub">Capacité, ventes et disponibilité</p>
                    </div>
                    @if($calendarUrl)
                        <a href="{{ $calendarUrl }}" class="ea-btn-outline">Calendrier</a>
                    @endif
                </div>
                @if($upcomingDepartures !== [])
                    <div class="ea-departures">
                        @foreach($upcomingDepartures as $departure)
                            @php
                                $total = (int) ($departure['total'] ?? 0);
                                $sold = (int) ($departure['sold'] ?? max(0, $total - (int) ($departure['available'] ?? 0)));
                                $fill = (int) ($departure['fill_pct'] ?? ($total > 0 ? round($sold / $total * 100) : 0));
                                $subtitle = implode(' — ', array_filter([
                                    trim((string) ($departure['duration'] ?? '')),
                                    ($departure['destination'] ?? '') !== '—' ? trim((string) ($departure['destination'] ?? '')) : '',
                                ]));
                                $color = (string) ($departure['status_color'] ?? 'green');
                            @endphp
                            <div class="ea-departure">
                                <div class="ea-date">
                                    @if(!empty($departure['date_day']))
                                        <div class="ea-date-day">{{ $departure['date_day'] }}</div>
                                        <div class="ea-date-month">{{ $departure['date_month'] }}</div>
                                    @else
                                        <div class="ea-date-month">{{ $departure['date'] }}</div>
                                    @endif
                                </div>
                                <div class="ea-departure-body">
                                    <div class="ea-departure-title">{{ $departure['voyage'] }}</div>
                                    @if($subtitle !== '')
                                        <div class="ea-departure-sub">{{ $subtitle }}</div>
                                    @endif
                                </div>
                                <div class="ea-departure-fill">
                                    <div class="ea-departure-fill-head">
                                        <span>{{ $sold }} / {{ $total }} vendus</span>
                                        <b class="{{ $departureStatus[$color] ?? 'ea-status-muted' }}">{{ mb_strtolower((string) $departure['status_label']) }}</b>
                                    </div>
                                    <div class="ea-bar {{ $departureBar[$color] ?? 'ea-bar-blue' }}"><span style="width:{{ min(100, max(0, $fill)) }}%"></span></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($departuresUrl)
                        <div style="margin-top:14px"><a href="{{ $departuresUrl }}" class="ea-link">Voir tous les départs →</a></div>
                    @endif
                @else
                    <div class="ea-empty">Aucun départ programmé à venir.</div>
                @endif
            </article>

            {{-- Réservations récentes --}}
            <article class="ea-card">
                <div class="ea-card-head" style="align-items:center">
                    <h2 class="ea-card-title">Réservations récentes</h2>
                    @if($reservationsUrl)
                        <a href="{{ $reservationsUrl }}" class="ea-link">Voir tout</a>
                    @endif
                </div>
                <p class="ea-card-sub">Dernières ventes et demandes client</p>
                @if($latestReservations !== [])
                    <div class="ea-resa-grid">
                        @foreach($latestReservations as $reservation)
                            @php
                                $initials = strtoupper(collect(preg_split('/\s+/', trim((string) $reservation['client_name'])))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: 'CL';
                                [$statusLabel, $statusClass] = $statusMap[$reservation['status']] ?? [ucfirst((string) $reservation['status']), 'is-blue'];
                                $subtitle = $reservation['tour_name'] !== '' ? $reservation['tour_name'] : 'Réservation #' . $reservation['id'];
                                $href = null;
                                if (!empty($reservation['dossier_id']) && Route::has('admin.reservation-dossiers.show')) {
                                    $href = route('admin.reservation-dossiers.show', $reservation['dossier_id']);
                                }
                            @endphp
                            @if($href)
                                <a href="{{ $href }}" class="ea-resa">
                            @else
                                <div class="ea-resa">
                            @endif
                                <div class="ea-resa-top">
                                    <div class="ea-resa-avatar">{{ $initials }}</div>
                                    <div style="min-width:0">
                                        <div class="ea-resa-name">{{ $reservation['client_name'] }}</div>
                                        <div class="ea-resa-sub">{{ $subtitle }}</div>
                                    </div>
                                </div>
                                <div class="ea-resa-bottom">
                                    <span class="ea-tag {{ $statusClass }}">{{ $statusLabel }}</span>
                                    <span class="ea-resa-amount">{{ $fmtNumber($reservation['amount']) }} {{ $reservation['currency'] }}</span>
                                </div>
                            @if($href)
                                </a>
                            @else
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="ea-empty">Aucune réservation récente.</div>
                @endif
            </article>
        </div>

        <div class="ea-col-side">

            {{-- Réseau & catalogue --}}
            <article class="ea-card is-navy">
                <h2 class="ea-card-title">Réseau &amp; catalogue</h2>
                <p class="ea-card-sub">État de la plateforme</p>
                @php
                    $reservationsEvolution = (float) ($stats['reservations_evolution'] ?? 0);
                    $networkCells = [
                        ['label' => 'Voyages', 'value' => $fmtNumber($stats['voyages'] ?? 0), 'note' => 'publiés', 'href' => $voyagesUrl, 'accent' => false],
                        ['label' => 'Agences', 'value' => $fmtNumber($stats['agencies'] ?? 0), 'note' => 'points de vente', 'href' => $agenciesUrl, 'accent' => false],
                        ['label' => 'Réservations', 'value' => $fmtNumber($stats['reservations'] ?? 0), 'note' => ($reservationsEvolution >= 0 ? '+' : '-') . $fmtPercent($reservationsEvolution) . ' ce mois', 'href' => $reservationsUrl, 'accent' => true],
                        ['label' => 'Clients', 'value' => $fmtNumber($stats['clients'] ?? 0), 'note' => 'base active', 'href' => $clientsUrl, 'accent' => false],
                    ];
                @endphp
                <div class="ea-network">
                    @foreach($networkCells as $cell)
                        @if($cell['href'])
                            <a href="{{ $cell['href'] }}" class="ea-network-cell">
                        @else
                            <div class="ea-network-cell">
                        @endif
                            <div class="ea-network-label">{{ $cell['label'] }}</div>
                            <div class="ea-network-value {{ $cell['accent'] ? 'is-accent' : '' }}">{{ $cell['value'] }}</div>
                            <div class="ea-network-note">{{ $cell['note'] }}</div>
                        @if($cell['href'])
                            </a>
                        @else
                            </div>
                        @endif
                    @endforeach
                </div>
            </article>

            {{-- Taux de confirmation --}}
            <article class="ea-card">
                <h2 class="ea-card-title">Taux de confirmation</h2>
                <p class="ea-card-sub">Confirmées / en attente</p>
                <div class="ea-gauge-wrap">
                    <svg class="ea-gauge" viewBox="0 0 120 70" role="img" aria-label="Taux de confirmation {{ $confirmedPct }} %">
                        <path d="M12,60 A48,48 0 0 1 108,60" fill="none" stroke="#e6eef6" stroke-width="14" stroke-linecap="round"></path>
                        <path d="M12,60 A48,48 0 0 1 108,60" fill="none" stroke="#f5821f" stroke-width="14" stroke-linecap="round" stroke-dasharray="150.8" stroke-dashoffset="{{ $gaugeOffset }}"></path>
                        <text x="60" y="56" text-anchor="middle" font-family="'JetBrains Mono', monospace" font-size="23" font-weight="500" fill="#10263d">{{ $confirmedPct }}%</text>
                    </svg>
                    <div class="ea-gauge-meta">
                        <span class="is-strong">{{ $fmtNumber($breakdown['confirmed']) }} dossier(s) confirmé(s)</span>
                        <span class="is-muted">{{ $fmtNumber($breakdown['pending']) }} en attente de validation</span>
                        @if($confirmationWeekEvolution > 0)
                            <span class="is-up">↗ {{ $fmtPercent($confirmationWeekEvolution) }} vs semaine précédente</span>
                        @elseif($confirmationWeekEvolution < 0)
                            <span class="is-down">↘ {{ $fmtPercent($confirmationWeekEvolution) }} vs semaine précédente</span>
                        @else
                            <span class="is-up">stable vs semaine précédente</span>
                        @endif
                    </div>
                </div>
            </article>

            {{-- Réservations par destination --}}
            <article class="ea-card">
                <h2 class="ea-card-title">Réservations par destination</h2>
                <p class="ea-card-sub">Répartition sur {{ $fmtNumber($destinations['total'] ?? 0) }} réservation(s)</p>
                @if(($destinations['total'] ?? 0) > 0)
                    <div class="ea-dest-list">
                        @foreach($destinations['segments'] as $index => $segment)
                            <div>
                                <div class="ea-dest-head"><span>{{ $segment['label'] }}</span><span>{{ (int) $segment['percent'] }} %</span></div>
                                <div class="ea-dest-bar"><span style="width:{{ min(100, max(0, (int) $segment['percent'])) }}%; background:{{ $destinationPalette[$index % count($destinationPalette)] }}"></span></div>
                            </div>
                        @endforeach
                        @if($reservationsUrl)
                            <a href="{{ $reservationsUrl }}" class="ea-link" style="margin-top:2px">Voir le détail des réservations →</a>
                        @endif
                    </div>
                @else
                    <div class="ea-empty">Aucune réservation par destination.</div>
                @endif
            </article>

            {{-- Alertes & tâches --}}
            <article class="ea-card">
                <div class="ea-card-head" style="align-items:center">
                    <h2 class="ea-card-title">Alertes &amp; tâches</h2>
                    @if($alertsUrl)
                        <a href="{{ $alertsUrl }}" class="ea-link">Voir tout</a>
                    @endif
                </div>
                <p class="ea-card-sub">Suivi du pilotage quotidien</p>
                @if($alerts !== [])
                    <div class="ea-alerts">
                        @foreach($alerts as $alert)
                            @php
                                $color = (string) ($alert['color'] ?? '');
                                $numeric = is_numeric($alert['value']) ? (float) $alert['value'] : 0.0;
                                $hot = $color === 'orange' && $numeric > 0;
                            @endphp
                            <div class="ea-alert {{ $hot ? 'is-hot' : '' }}">
                                <span class="ea-dot {{ in_array($color, ['orange', 'blue', 'green'], true) ? 'is-' . $color : '' }}" aria-hidden="true"></span>
                                <div class="ea-alert-body">
                                    <div class="ea-alert-title">{{ $alert['label'] }}</div>
                                    <div class="ea-alert-sub">{{ $alert['subtitle'] }}</div>
                                </div>
                                <span class="ea-alert-value">{{ $alert['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="ea-empty">Aucune alerte à traiter.</div>
                @endif
            </article>
        </div>
    </section>
@endsection
