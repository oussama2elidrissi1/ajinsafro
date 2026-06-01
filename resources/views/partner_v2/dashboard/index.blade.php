@extends('partner_v2.layouts.app')
@section('title', 'Tableau de bord')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('css/partner-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
@php
    $partnerName = $partner?->display_name ?? auth()->user()->name;
    $catalogueUrl = route('partner.catalogue.index');
@endphp

<div class="agent-dashboard-page partner-dashboard-page">
    <section class="agent-dashboard-shell agent-dashboard-hero">
        <div class="agent-dashboard-hero__content">
            <div>
                <span class="agent-dashboard-badge">{{ $partnerName }}</span>
                <h1 class="agent-dashboard-title">Bienvenue, {{ $partnerName }}</h1>
                <p class="agent-dashboard-subtitle">Votre activité, vos commissions et vos dernières réservations au même endroit.</p>
            </div>
            <div class="agent-dashboard-actions">
                <a href="{{ $catalogueUrl }}" class="btn agent-btn agent-btn-primary agent-dashboard-actions__cta">
                    <i class="bx bx-map-alt align-middle" aria-hidden="true"></i>
                    <span>Catalogue de voyage</span>
                </a>
            </div>
        </div>
    </section>

    <section class="agent-dashboard-grid agent-dashboard-grid--kpis">
        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-briefcase-alt-2"></i></div>
            <div class="agent-kpi-label">Réservations (mois)</div>
            <div class="agent-kpi-value">{{ number_format((int) $reservationsThisMonth, 0, ',', ' ') }}</div>
        </article>

        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-user"></i></div>
            <div class="agent-kpi-label">Clients</div>
            <div class="agent-kpi-value">{{ number_format((int) $clientsCount, 0, ',', ' ') }}</div>
        </article>

        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-wallet"></i></div>
            <div class="agent-kpi-label">Commissions (validées + payées)</div>
            <div class="agent-kpi-value">{{ number_format((float) $commissionsTotal, 0, ',', ' ') }} DH</div>
        </article>

        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-time-five"></i></div>
            <div class="agent-kpi-label">En attente</div>
            <div class="agent-kpi-value">{{ number_format((float) $commissionsPending, 0, ',', ' ') }} DH</div>
        </article>
    </section>

    <section class="agent-dashboard-grid agent-dashboard-grid--content">
        <div class="agent-panel agent-panel--wide">
            <div class="agent-panel-header">
                <div>
                    <h2>Dernières réservations</h2>
                    <p>Une vue rapide sur les dossiers les plus récents.</p>
                </div>
                <div class="agent-dashboard-actions">
                    <a href="{{ route('partner.reservations.index') }}" class="btn agent-btn agent-btn-secondary">Voir tout</a>
                </div>
            </div>

            @if(($recentReservations ?? collect())->count() === 0)
                <div class="agent-empty-state">
                    <div class="agent-empty-state__icon"><i class="bx bx-receipt" aria-hidden="true"></i></div>
                    <div class="agent-empty-state__title">Aucune réservation récente</div>
                    <div class="agent-empty-state__text">Les nouveaux dossiers apparaîtront ici dès qu'ils seront créés.</div>
                    <div class="agent-empty-state__actions">
                        @if(\Illuminate\Support\Facades\Route::has('partner.reservations.create'))
                            <a href="{{ route('partner.reservations.create') }}" class="btn agent-btn agent-btn-primary agent-empty-state__cta">
                                <i class="bx bx-plus-circle align-middle" aria-hidden="true"></i>
                                <span>Créer une réservation</span>
                            </a>
                        @endif
                        <a href="{{ $catalogueUrl }}" class="btn agent-btn agent-btn-secondary agent-empty-state__cta">
                            <i class="bx bx-map-alt align-middle" aria-hidden="true"></i>
                            <span>Voir le catalogue</span>
                        </a>
                    </div>
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
                                        \App\Models\Reservation::STATUS_VALIDEE => 'Confirmée',
                                        \App\Models\Reservation::STATUS_EN_COURS => 'En attente',
                                        \App\Models\Reservation::STATUS_ANNULEE => 'Annulée',
                                        default => (string) $status,
                                    };
                                    $statusClass = match ($status) {
                                        \App\Models\Reservation::STATUS_VALIDEE => 'is-confirmed',
                                        \App\Models\Reservation::STATUS_EN_COURS => 'is-pending',
                                        \App\Models\Reservation::STATUS_ANNULEE => 'is-cancelled',
                                        default => 'is-neutral',
                                    };
                                    $detailUrl = route('partner.reservations.show', $reservation);
                                    $displayDate = optional($reservation->created_at)->format('d/m/Y');
                                @endphp
                                <tr>
                                    <td data-label="Client">{{ $clientName !== '' ? $clientName : 'Client non renseigné' }}</td>
                                    <td data-label="Voyage">{{ $reservation->tour?->name ?: 'Voyage non renseigné' }}</td>
                                    <td data-label="Date">{{ $displayDate }}</td>
                                    <td data-label="Statut"><span class="agent-status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td data-label="Action" class="text-end">
                                        <a href="{{ $detailUrl }}" class="agent-table-link">Ouvrir</a>
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
                    <h2>Top voyages</h2>
                    <p>Les voyages les plus demandés par vos clients.</p>
                </div>
            </div>

            <div class="agent-notifications">
                @if(empty($topVoyages) || $topVoyages->isEmpty())
                    <div class="agent-notification-item">Aucune donnée.</div>
                @else
                    @foreach($topVoyages as $item)
                        <div class="agent-notification-item partner-dashboard-topline">
                            <span class="partner-dashboard-topline__name">
                                {{ $item->tour?->name ?? ('Voyage #' . $item->tour_id) }}
                            </span>
                            <span class="partner-dashboard-topline__count">{{ (int) $item->cnt }}</span>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="agent-dashboard-actions partner-dashboard-actions">
                <a href="{{ $catalogueUrl }}" class="btn agent-btn agent-btn-secondary">Voir le catalogue</a>
            </div>
        </aside>
    </section>
</div>
@endsection
