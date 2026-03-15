@extends('layouts.master-ajinsafro')
@section('title')
    Vue globale
@endsection

@push('css')
<style>
    .dashboard-card { transition: transform 0.25s ease, box-shadow 0.25s ease; }
    .dashboard-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,.08); }
    .dashboard-card .card-body { position: relative; overflow: hidden; }
    .dashboard-card .card-body::before { content: ''; position: absolute; top: 0; right: 0; width: 120px; height: 120px; border-radius: 50%; opacity: 0.06; }
    .kpi-icon-wrap { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
    .trend-badge { font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 6px; }
    .trend-up { background: rgba(10, 179, 156, 0.15); color: #0ab39c; }
    .trend-down { background: rgba(240, 101, 72, 0.15); color: #f06548; }
    .animate-fade-in { animation: dashFadeIn 0.5s ease forwards; }
    .animate-fade-in.delay-1 { animation-delay: 0.08s; opacity: 0; }
    .animate-fade-in.delay-2 { animation-delay: 0.16s; opacity: 0; }
    .animate-fade-in.delay-3 { animation-delay: 0.24s; opacity: 0; }
    .animate-fade-in.delay-4 { animation-delay: 0.32s; opacity: 0; }
    .animate-fade-in.delay-5 { animation-delay: 0.4s; opacity: 0; }
    .animate-fade-in.delay-6 { animation-delay: 0.48s; opacity: 0; }
    @keyframes dashFadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .table-dashboard tbody tr { transition: background 0.2s; }
    .table-dashboard tbody tr:hover { background: rgba(64, 81, 137, 0.04); }
    .stat-progress { height: 6px; border-radius: 3px; }
    .section-title { font-size: 1rem; font-weight: 600; color: #495057; border-left: 3px solid #405189; padding-left: 0.75rem; }
    .revenue-big { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; }
    .activity-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
</style>
@endpush

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="page-title mb-1 font-size-18">Vue globale</h4>
                    <p class="text-muted mb-0 small">{{ now()->locale('fr')->translatedFormat('l d F Y · H:i') }}</p>
                </div>
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item active">Vue globale</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- KPIs principaux (animés) --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 animate-fade-in">
            <div class="card dashboard-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrap bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bx bx-map font-size-24"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-0 small text-uppercase fw-medium">Voyages</p>
                            <h3 class="mb-0 mt-1">{{ $stats['voyages_count'] }}</h3>
                            @if($stats['voyages_featured'] > 0)
                                <small class="text-muted">{{ $stats['voyages_featured'] }} en vedette</small>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('admin.circuits.voyages.index') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 animate-fade-in delay-1">
            <div class="card dashboard-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrap bg-success bg-opacity-10 text-success me-3">
                            <i class="bx bx-building-house font-size-24"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-0 small text-uppercase fw-medium">Agences</p>
                            <h3 class="mb-0 mt-1">{{ $stats['branches_count'] }}</h3>
                            <small class="text-muted">{{ $stats['branches_active'] }} actives</small>
                        </div>
                    </div>
                    <a href="{{ route('admin.branches.index') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 animate-fade-in delay-2">
            <div class="card dashboard-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrap bg-info bg-opacity-10 text-info me-3">
                            <i class="bx bx-calendar-check font-size-24"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-0 small text-uppercase fw-medium">Réservations</p>
                            <h3 class="mb-0 mt-1">{{ $stats['reservations_total'] }}</h3>
                            <span class="trend-badge {{ $stats['reservations_month_evolution'] >= 0 ? 'trend-up' : 'trend-down' }}">
                                <i class="bx {{ $stats['reservations_month_evolution'] >= 0 ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' }}"></i>
                                {{ $stats['reservations_month_evolution'] >= 0 ? '+' : '' }}{{ $stats['reservations_month_evolution'] }}% ce mois
                            </span>
                        </div>
                    </div>
                    <a href="{{ route('admin.reservations.index') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 animate-fade-in delay-3">
            <div class="card dashboard-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrap bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bx bx-user font-size-24"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-0 small text-uppercase fw-medium">Clients</p>
                            <h3 class="mb-0 mt-1">{{ $stats['clients_count'] }}</h3>
                        </div>
                    </div>
                    <a href="{{ route('admin.customers.clients.index') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>

    {{-- Activité + Chiffre d'affaires + Messages --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-4 animate-fade-in delay-4">
            <div class="card dashboard-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="section-title mb-3">Activité récente</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Aujourd'hui</span>
                        <strong>{{ $stats['reservations_today'] }}</strong> résa
                    </div>
                    <div class="progress stat-progress mb-3">
                        @php $maxDay = max(1, $stats['reservations_this_week']); $pct = min(100, ($stats['reservations_today'] / $maxDay) * 100); @endphp
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Cette semaine</span>
                        <strong>{{ $stats['reservations_this_week'] }}</strong> résa
                    </div>
                    <div class="progress stat-progress mb-2">
                        @php $maxWeek = max(1, $stats['reservations_this_month']); $pctW = min(100, ($stats['reservations_this_week'] / $maxWeek) * 100); @endphp
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pctW }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Ce mois</span>
                        <strong>{{ $stats['reservations_this_month'] }}</strong> réservations
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4 animate-fade-in delay-4">
            <div class="card dashboard-card border-0 shadow-sm h-100 border-start border-primary border-3">
                <div class="card-body">
                    <h6 class="section-title mb-3">Chiffre d'affaires</h6>
                    <p class="text-muted small mb-1">Total validé</p>
                    <p class="revenue-big text-primary mb-2">{{ number_format($stats['revenue_total'], 0, ',', ' ') }} <small class="fw-normal text-muted">€</small></p>
                    <p class="text-muted small mb-1">Ce mois</p>
                    <p class="h5 mb-0">{{ number_format($stats['revenue_this_month'], 0, ',', ' ') }} €</p>
                    <span class="trend-badge {{ $stats['revenue_month_evolution'] >= 0 ? 'trend-up' : 'trend-down' }}">
                        {{ $stats['revenue_month_evolution'] >= 0 ? '+' : '' }}{{ $stats['revenue_month_evolution'] }}% vs mois dernier
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-4 animate-fade-in delay-4">
            <div class="card dashboard-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="section-title mb-2">Messages</h6>
                        <p class="text-muted small mb-0">Boîte Réservations</p>
                        <h3 class="mb-0 mt-1">{{ $stats['messages_count'] }}</h3>
                    </div>
                    <a href="{{ route('admin.reservations.messages') }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-envelope me-1"></i> Ouvrir
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Statuts réservations (barres) --}}
    <div class="row g-3 mb-4">
        <div class="col-12 animate-fade-in delay-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="section-title mb-3">Répartition des réservations</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <span class="activity-dot bg-warning me-2"></span>
                                <span class="flex-grow-1">En attente</span>
                                <strong>{{ $stats['reservations_en_cours'] }}</strong>
                            </div>
                            <div class="progress stat-progress mt-1">
                                @php $total = $stats['reservations_total'] ?: 1; @endphp
                                <div class="progress-bar bg-warning" style="width: {{ ($stats['reservations_en_cours'] / $total) * 100 }}%"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <span class="activity-dot bg-success me-2"></span>
                                <span class="flex-grow-1">Validées</span>
                                <strong>{{ $stats['reservations_validees'] }}</strong>
                            </div>
                            <div class="progress stat-progress mt-1">
                                <div class="progress-bar bg-success" style="width: {{ ($stats['reservations_validees'] / $total) * 100 }}%"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <span class="activity-dot bg-danger me-2"></span>
                                <span class="flex-grow-1">Annulées</span>
                                <strong>{{ $stats['reservations_annulees'] }}</strong>
                            </div>
                            <div class="progress stat-progress mt-1">
                                <div class="progress-bar bg-danger" style="width: {{ ($stats['reservations_annulees'] / $total) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Graphiques --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-8 animate-fade-in delay-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Réservations & Chiffre d'affaires (6 mois)</h5>
                    <div id="vue-globale-line-chart" class="apex-charts" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 animate-fade-in delay-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Statut des réservations</h5>
                    <div id="vue-globale-donut-chart" class="apex-charts" style="min-height: 280px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Paiements (bar) + Dernières réservations --}}
    <div class="row g-3 mb-4">
        @if(count($stats['payment_labels']) > 0)
        <div class="col-xl-4 animate-fade-in delay-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">Paiements (validées)</h5>
                    <div id="vue-globale-payment-chart" class="apex-charts" style="min-height: 220px;"></div>
                </div>
            </div>
        </div>
        @endif
        <div class="{{ count($stats['payment_labels']) > 0 ? 'col-xl-8' : 'col-xl-12' }} animate-fade-in delay-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="card-title mb-0">Dernières réservations</h5>
                        <a href="{{ route('admin.reservations.index') }}" class="btn btn-sm btn-outline-primary">Toutes <i class="bx bx-right-arrow-alt"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dashboard table-hover align-middle mb-0">
                            <thead class="table-light">
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
                                            <span class="fw-medium">{{ $r->client_first_name }} {{ $r->client_last_name }}</span>
                                            @if($r->client_email)<br><small class="text-muted">{{ Str::limit($r->client_email, 25) }}</small>@endif
                                        </td>
                                        <td>@if($r->tour)<span>{{ Str::limit($r->tour->name, 28) }}</span>@else<span class="text-muted">—</span>@endif</td>
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
                                            @if($tot > 0)<strong>{{ number_format($tot, 0, ',', ' ') }} €</strong>@else<span class="text-muted">—</span>@endif
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

    {{-- Voyages populaires + Agences récentes --}}
    <div class="row g-3">
        <div class="col-xl-6 animate-fade-in delay-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="card-title mb-0">Voyages les plus réservés</h5>
                        <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-sm btn-outline-primary">Tous</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dashboard table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Voyage</th><th class="text-end">Réservations</th><th></th></tr></thead>
                            <tbody>
                                @forelse($topVoyages as $row)
                                    <tr>
                                        <td>{{ Str::limit($row['voyage']->name, 50) }}</td>
                                        <td class="text-end"><strong>{{ $row['total'] }}</strong></td>
                                        <td><a href="{{ route('admin.circuits.voyages.edit', $row['voyage']->id) }}" class="btn btn-sm btn-soft-primary">Voir</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">Aucune donnée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 animate-fade-in delay-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="card-title mb-0">Agences actives</h5>
                        <a href="{{ route('admin.branches.index') }}" class="btn btn-sm btn-outline-primary">Toutes</a>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse($recentBranches as $b)
                            <li class="list-group-item d-flex align-items-center px-0">
                                <span class="kpi-icon-wrap bg-success bg-opacity-10 text-success me-2" style="width:40px;height:40px;"><i class="bx bx-building-house"></i></span>
                                <div class="flex-grow-1">
                                    <span class="fw-medium">{{ $b->name }}</span>
                                    @if($b->city)<br><small class="text-muted">{{ $b->city }} · {{ $b->code }}</small>@endif
                                </div>
                                <a href="{{ route('admin.branches.edit', $b) }}" class="btn btn-sm btn-soft-primary">Voir</a>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">Aucune agence.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var lineOptions = {
                series: [
                    { name: 'Réservations', type: 'column', data: @json($stats['chart_reservations']) },
                    { name: 'Chiffre d\'affaires (€)', type: 'line', data: @json($stats['chart_revenue']) }
                ],
                chart: { height: 320, type: 'line', toolbar: { show: false }, zoom: { enabled: false }, animations: { enabled: true, speed: 600 } },
                colors: ['#405189', '#0ab39c'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: [0, 2] },
                fill: { type: 'solid', opacity: [0.85, 0] },
                xaxis: { categories: @json($stats['chart_months']) },
                yaxis: [
                    { title: { text: 'Réservations' }, labels: { formatter: function(v) { return Math.round(v); } } },
                    { opposite: true, title: { text: 'CA (€)' }, labels: { formatter: function(v) { return v >= 1000 ? (v/1000)+'k' : v; } } }
                ],
                legend: { position: 'top', horizontalAlign: 'right' },
                plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } }
            };
            new ApexCharts(document.querySelector("#vue-globale-line-chart"), lineOptions).render();

            var donutOptions = {
                series: @json($stats['donut_series']),
                chart: { height: 280, type: 'donut', animations: { enabled: true, speed: 600 } },
                labels: @json($stats['donut_labels']),
                plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', formatter: function(w) { return w.globals.seriesTotals.reduce(function(a,b){ return a+b; }, 0); } } } } } },
                legend: { position: 'bottom' },
                colors: ['#f7b84b', '#0ab39c', '#f06548']
            };
            new ApexCharts(document.querySelector("#vue-globale-donut-chart"), donutOptions).render();

            @if(count($stats['payment_labels']) > 0)
            var paymentOptions = {
                series: [{ name: 'Paiements', data: @json($stats['payment_series']) }],
                chart: { type: 'bar', height: 220, toolbar: { show: false }, animations: { enabled: true, speed: 600 } },
                plotOptions: { bar: { horizontal: true, barHeight: '60%', borderRadius: 4 } },
                dataLabels: { enabled: true },
                xaxis: { categories: @json($stats['payment_labels']) },
                colors: ['#405189'],
                legend: { show: false }
            };
            new ApexCharts(document.querySelector("#vue-globale-payment-chart"), paymentOptions).render();
            @endif
        });
    </script>
@endpush
