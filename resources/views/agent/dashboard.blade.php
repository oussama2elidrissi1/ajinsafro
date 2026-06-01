@extends('layouts.master-ajinsafro')

@section('title', 'Tableau de bord')

@push('css')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
@php
    use App\Models\Reservation;
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $displayName = $user?->name ?: 'Agent';
    $agencyLabel = $user?->branch?->name ?: 'Ajinsafro Tanger';

    $catalogueVoyageUrl = Route::has('admin.reservations.workspace')
        ? route('admin.reservations.workspace')
        : url('/admin/reservations/workspace');
@endphp

<div class="agent-dashboard-page">
    <section class="agent-dashboard-shell agent-dashboard-hero">
        <div class="agent-dashboard-hero__content">
            <div>
                <span class="agent-dashboard-badge">{{ $agencyLabel }}</span>
                <h1 class="agent-dashboard-title">Bienvenue, {{ $displayName }}</h1>
                <p class="agent-dashboard-subtitle">Votre activité du jour, vos réservations et les actions prioritaires au même endroit.</p>
            </div>
            <div class="agent-dashboard-actions">
                <a href="{{ $catalogueVoyageUrl }}" class="btn agent-btn agent-btn-primary agent-dashboard-actions__cta">
                    <i class="bx bx-map-alt align-middle" aria-hidden="true"></i>
                    <span>Catalogue de voyage</span>
                </a>
            </div>
        </div>
    </section>

    <section class="agent-dashboard-grid agent-dashboard-grid--kpis">
        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-briefcase-alt-2"></i></div>
            <div class="agent-kpi-label">Réservations</div>
            <div class="agent-kpi-value">{{ number_format((int) ($stats['reservations_total'] ?? 0), 0, ',', ' ') }}</div>
        </article>

        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-check-shield"></i></div>
            <div class="agent-kpi-label">Confirmées</div>
            <div class="agent-kpi-value">{{ number_format((int) ($stats['reservations_validees'] ?? 0), 0, ',', ' ') }}</div>
        </article>

        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-time-five"></i></div>
            <div class="agent-kpi-label">En attente</div>
            <div class="agent-kpi-value">{{ number_format((int) ($stats['reservations_en_cours'] ?? 0), 0, ',', ' ') }}</div>
        </article>

        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-wallet"></i></div>
            <div class="agent-kpi-label">Revenus</div>
            <div class="agent-kpi-value">{{ number_format((float) ($stats['revenue_generated'] ?? 0), 0, ',', ' ') }} DH</div>
        </article>
    </section>

    <section class="agent-dashboard-grid agent-dashboard-grid--content">
        <div class="agent-panel agent-panel--wide">
            <div class="agent-panel-header">
                <div>
                    <h2>Mes dernières réservations</h2>
                    <p>Une vue rapide sur les dossiers les plus récents.</p>
                </div>

                <form method="GET" action="{{ route('agent.dashboard') }}" class="agent-filter-form">
                    <label for="scope" class="visually-hidden">Filtrer les réservations</label>
                    <select name="scope" id="scope" class="form-select agent-select" {{ $isManager ? '' : 'disabled' }}>
                        <option value="mine" {{ ($scope ?? 'mine') === 'mine' ? 'selected' : '' }}>Mes réservations</option>
                        @if($isManager)
                            <option value="team" {{ ($scope ?? 'mine') === 'team' ? 'selected' : '' }}>Mon équipe</option>
                        @endif
                    </select>
                    @unless($isManager)
                        <input type="hidden" name="scope" value="mine">
                    @endunless
                    <button type="submit" class="btn agent-btn agent-btn-secondary">Filtrer</button>
                </form>
            </div>

            @if(($recentReservations ?? collect())->count() === 0)
                <div class="agent-empty-state">
                    <div class="agent-empty-state__icon"><i class="bx bx-receipt" aria-hidden="true"></i></div>
                    <div class="agent-empty-state__title">Aucune réservation récente</div>
                    <div class="agent-empty-state__text">Les nouveaux dossiers apparaîtront ici dès qu'ils seront créés.</div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table agent-table mb-0">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Voyage</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentReservations as $reservation)
                                @php
                                    $clientName = trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? ''));
                                    $status = $reservation->status;
                                    $statusLabel = match ($status) {
                                        Reservation::STATUS_VALIDEE => 'Confirmée',
                                        Reservation::STATUS_EN_COURS => 'En attente',
                                        Reservation::STATUS_ANNULEE => 'Annulée',
                                        default => (string) $status,
                                    };
                                    $statusClass = match ($status) {
                                        Reservation::STATUS_VALIDEE => 'is-confirmed',
                                        Reservation::STATUS_EN_COURS => 'is-pending',
                                        Reservation::STATUS_ANNULEE => 'is-cancelled',
                                        default => 'is-neutral',
                                    };
                                    $detailUrl = Route::has('admin.reservations.show') ? route('admin.reservations.show', $reservation) : '#';
                                    $displayDate = optional($reservation->travelDate?->date)->format('d/m/Y') ?: optional($reservation->created_at)->format('d/m/Y');
                                @endphp
                                <tr>
                                    <td data-label="Client">{{ $clientName !== '' ? $clientName : 'Client non renseigné' }}</td>
                                    <td data-label="Voyage">{{ $reservation->tour?->name ?: 'Voyage non renseigné' }}</td>
                                    <td data-label="Date">{{ $displayDate }}</td>
                                    <td data-label="Statut"><span class="agent-status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td data-label="Action" class="text-end">
                                        <a href="{{ $detailUrl }}" class="agent-table-link">Voir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <aside class="agent-panel agent-panel--side">
            <div class="agent-panel-header agent-panel-header--stacked">
                <div>
                    <h2>Aujourd'hui</h2>
                    <p>Les chiffres à surveiller en priorité.</p>
                </div>
            </div>

            <div class="agent-today-metrics">
                <div class="agent-today-metric">
                    <span>Réservations du jour</span>
                    <strong>{{ number_format((int) ($todayStats['reservations_today'] ?? 0), 0, ',', ' ') }}</strong>
                </div>
                <div class="agent-today-metric">
                    <span>En attente aujourd'hui</span>
                    <strong>{{ number_format((int) ($todayStats['pending_today'] ?? 0), 0, ',', ' ') }}</strong>
                </div>
            </div>

            <div class="agent-notifications">
                @foreach(($todayStats['notifications'] ?? []) as $notification)
                    <div class="agent-notification-item">{{ $notification }}</div>
                @endforeach
            </div>
        </aside>
    </section>
</div>
@endsection
