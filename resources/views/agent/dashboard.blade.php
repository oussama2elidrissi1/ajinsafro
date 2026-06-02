@extends('layouts.master-ajinsafro')

@section('title', 'Tableau de bord')

@section('hidePageFooter', '1')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
@php
    use App\Models\Reservation;
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $displayName = $user?->name ?: 'Agent';
    $agencyLabel = $user?->branch?->name ?: 'Ajinsafro';

    $catalogueVoyageUrl = route('agent.catalogue');
@endphp

<div class="aj-agent-dashboard">
    <div class="aj-agent-page-head">
        <div class="aj-agent-page-title">
            <h1>Tableau de bord</h1>
            <p>Bienvenue, {{ $displayName }} — {{ $agencyLabel }}.</p>
        </div>
        <a href="{{ $catalogueVoyageUrl }}" class="aj-agent-primary-btn">
            <i class="bx bx-map-alt" aria-hidden="true"></i>
            <span>Catalogue de voyage</span>
        </a>
    </div>

    <section class="aj-agent-kpi-grid">
        <div class="aj-agent-kpi-card aj-agent-kpi-blue">
            <div class="aj-agent-kpi-icon"><i class="bx bx-briefcase-alt-2"></i></div>
            <div>
                <span>Réservations</span>
                <strong>{{ number_format((int) ($stats['reservations_total'] ?? 0), 0, ',', ' ') }}</strong>
            </div>
        </div>
        <div class="aj-agent-kpi-card aj-agent-kpi-green">
            <div class="aj-agent-kpi-icon"><i class="bx bx-check-shield"></i></div>
            <div>
                <span>Confirmées</span>
                <strong>{{ number_format((int) ($stats['reservations_validees'] ?? 0), 0, ',', ' ') }}</strong>
            </div>
        </div>
        <div class="aj-agent-kpi-card aj-agent-kpi-purple">
            <div class="aj-agent-kpi-icon"><i class="bx bx-time-five"></i></div>
            <div>
                <span>En attente</span>
                <strong>{{ number_format((int) ($stats['reservations_en_cours'] ?? 0), 0, ',', ' ') }}</strong>
            </div>
        </div>
        <div class="aj-agent-kpi-card aj-agent-kpi-orange">
            <div class="aj-agent-kpi-icon"><i class="bx bx-wallet"></i></div>
            <div>
                <span>Revenus</span>
                <strong>{{ number_format((float) ($stats['revenue_generated'] ?? 0), 0, ',', ' ') }} DH</strong>
            </div>
        </div>
    </section>

    <section class="aj-agent-content-grid">
        <div class="aj-agent-panel">
            <div class="aj-agent-panel-header">
                <div>
                    <h2>Aujourd’hui</h2>
                    <p>Résumé rapide de l’activité.</p>
                </div>
            </div>
            <div class="aj-agent-panel-body">
                <div class="aj-agent-today-item">
                    <span>Réservations du jour</span>
                    <small>{{ number_format((int) ($todayStats['reservations_today'] ?? 0), 0, ',', ' ') }}</small>
                </div>
                <div class="aj-agent-today-item">
                    <span>En attente aujourd’hui</span>
                    <small>{{ number_format((int) ($todayStats['pending_today'] ?? 0), 0, ',', ' ') }}</small>
                </div>

                @if(!empty($todayStats['notifications']))
                    @foreach(($todayStats['notifications'] ?? []) as $notification)
                        <div class="aj-agent-alert-box">{{ $notification }}</div>
                    @endforeach
                @else
                    <div class="aj-agent-alert-box">Aucune alerte prioritaire aujourd’hui.</div>
                @endif

                <div class="aj-agent-quick-actions">
                    <a href="{{ $catalogueVoyageUrl }}" class="aj-agent-action-btn">
                        <i class="bx bx-plus-circle"></i>
                        <span>Créer une réservation</span>
                    </a>
                    <a href="{{ $catalogueVoyageUrl }}" class="aj-agent-action-btn">
                        <i class="bx bx-map-alt"></i>
                        <span>Voir les voyages disponibles</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="aj-agent-panel aj-agent-panel-wide">
            <div class="aj-agent-panel-header">
                <div>
                    <h2>Mes dernières réservations</h2>
                    <p>Vue rapide sur les dossiers les plus récents.</p>
                </div>
                <form method="GET" action="{{ route('agent.dashboard') }}" class="aj-agent-table-actions">
                    <select name="scope" id="scope" class="aj-agent-select" {{ $isManager ? '' : 'disabled' }}>
                        <option value="mine" {{ ($scope ?? 'mine') === 'mine' ? 'selected' : '' }}>Mes réservations</option>
                        @if($isManager)
                            <option value="team" {{ ($scope ?? 'mine') === 'team' ? 'selected' : '' }}>Mon équipe</option>
                        @endif
                    </select>
                    @unless($isManager)
                        <input type="hidden" name="scope" value="mine">
                    @endunless
                    <button type="submit" class="aj-agent-small-btn">Filtrer</button>
                </form>
            </div>

            <div class="aj-agent-table-wrap">
                <table class="aj-agent-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Voyage</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentReservations as $reservation)
                        @php
                            $clientName = trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? ''));
                            $status = $reservation->status;
                            $badgeClass = $status === Reservation::STATUS_VALIDEE
                                ? 'aj-agent-status-green'
                                : ($status === Reservation::STATUS_ANNULEE ? 'aj-agent-status-red' : 'aj-agent-status-orange');
                            $detailUrl = Route::has('admin.reservation-dossiers.show') && $reservation->reservation_dossier_id
                                ? route('admin.reservation-dossiers.show', $reservation->reservation_dossier_id)
                                : '#';
                            $displayDate = optional($reservation->travelDate?->date)->format('d/m/Y') ?: optional($reservation->created_at)->format('d/m/Y');
                        @endphp
                        <tr>
                            <td>{{ $clientName !== '' ? $clientName : 'Client non renseigné' }}</td>
                            <td>{{ $reservation->tour?->name ?: 'Voyage non renseigné' }}</td>
                            <td>{{ $displayDate }}</td>
                            <td><span class="aj-agent-status {{ $badgeClass }}">{{ $status }}</span></td>
                            <td class="aj-agent-td-actions">
                                <a href="{{ $detailUrl }}" class="aj-agent-small-btn">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="aj-agent-empty-state">
                                    <div class="aj-agent-empty-icon"><i class="bx bx-briefcase-alt-2"></i></div>
                                    <h3>Aucune réservation récente</h3>
                                    <p>Commencez par consulter le catalogue ou créer une nouvelle réservation.</p>
                                    <a href="{{ $catalogueVoyageUrl }}" class="aj-agent-primary-btn">Voir le catalogue</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <footer class="aj-agent-footer">
        <div>© Ajinsafro SARL AU</div>
        <div>Licence N° 489117 | RC: 18989 | IF: 15254892</div>
    </footer>
</div>
@endsection
