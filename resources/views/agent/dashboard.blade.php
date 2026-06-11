@extends('layouts.master-ajinsafro')

@section('title', 'Tableau de bord')

@section('hidePageFooter', '1')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
@php
    use App\Models\CustomRequest;
    use App\Models\Reservation;
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $displayName = $user?->name ?: 'Agent';
    $agencyLabel = $user?->branch?->name ?: 'Ajinsafro';
    $catalogueVoyageUrl = route('agent.catalogue');
    $reservationsUrl = route('agent.reservations.index');
@endphp

<div class="aj-agent-dashboard">
    <div class="aj-agent-page-head">
        <div class="aj-agent-page-title">
            <h1>{{ $isManager ? 'Tableau de bord agence' : 'Tableau de bord' }}</h1>
            <p>
                {{ $isManager ? 'Pilotage equipe, reservations et chiffre agence.' : 'Bienvenue,' }}
                {{ $displayName }} - {{ $agencyLabel }}.
            </p>
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
                <span>{{ $isManager ? 'Portefeuille' : 'Reservations' }}</span>
                <strong>{{ number_format((int) ($stats['reservations_total'] ?? 0), 0, ',', ' ') }}</strong>
            </div>
        </div>
        <div class="aj-agent-kpi-card aj-agent-kpi-green">
            <div class="aj-agent-kpi-icon"><i class="bx bx-check-shield"></i></div>
            <div>
                <span>Confirmees</span>
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
                <span>{{ $isManager ? 'Total ventes' : 'Revenus' }}</span>
                <strong>{{ number_format((float) ($stats['revenue_generated'] ?? 0), 0, ',', ' ') }} DH</strong>
            </div>
        </div>
    </section>

    @if($isManager)
        @php
            $personal = $managerStats['personal'] ?? [];
            $teamOnly = $managerStats['team_only'] ?? [];
            $agentRows = $managerStats['agents'] ?? collect();
        @endphp
        <section class="aj-agent-manager-board">
            <div class="aj-agent-manager-card aj-agent-manager-card-dark">
                <div>
                    <span class="aj-agent-manager-kicker">Responsable agence</span>
                    <h2>{{ $agencyLabel }}</h2>
                    <p>{{ ($directReports ?? collect())->count() }} agent(s) rattache(s) a votre equipe.</p>
                </div>
                <a href="{{ $reservationsUrl }}" class="aj-agent-manager-link">Voir toutes les reservations</a>
            </div>

            <div class="aj-agent-manager-card">
                <span class="aj-agent-manager-kicker">Mes dossiers</span>
                <div class="aj-agent-manager-metrics">
                    <div><strong>{{ number_format((int) ($personal['reservations_total'] ?? 0), 0, ',', ' ') }}</strong><small>Total</small></div>
                    <div><strong>{{ number_format((int) ($personal['reservations_en_cours'] ?? 0), 0, ',', ' ') }}</strong><small>En attente</small></div>
                    <div><strong>{{ number_format((float) ($personal['revenue_generated'] ?? 0), 0, ',', ' ') }} DH</strong><small>Ventes</small></div>
                </div>
            </div>

            <div class="aj-agent-manager-card">
                <span class="aj-agent-manager-kicker">Equipe</span>
                <div class="aj-agent-manager-metrics">
                    <div><strong>{{ number_format((int) ($teamOnly['reservations_total'] ?? 0), 0, ',', ' ') }}</strong><small>Total</small></div>
                    <div><strong>{{ number_format((int) ($teamOnly['reservations_en_cours'] ?? 0), 0, ',', ' ') }}</strong><small>En attente</small></div>
                    <div><strong>{{ number_format((float) ($teamOnly['revenue_generated'] ?? 0), 0, ',', ' ') }} DH</strong><small>Ventes</small></div>
                </div>
            </div>
        </section>

        <section class="aj-agent-team-strip">
            <div class="aj-agent-team-strip-head">
                <div>
                    <h2>Equipe Tanger</h2>
                    <p>Suivi des agents rattaches et de leur volume de reservations.</p>
                </div>
            </div>
            <div class="aj-agent-team-list">
                @forelse($agentRows as $row)
                    @php $agent = $row['user']; @endphp
                    <div class="aj-agent-team-row">
                        <div class="aj-agent-team-person">
                            <img src="{{ $agent->avatar_url }}" alt="Avatar" class="aj-agent-team-avatar">
                            <div>
                                <strong>{{ $agent->name }}</strong>
                                <span>{{ $agent->email }}</span>
                            </div>
                        </div>
                        <div class="aj-agent-team-stat"><strong>{{ $row['reservations_total'] }}</strong><span>Reservations</span></div>
                        <div class="aj-agent-team-stat"><strong>{{ $row['reservations_en_cours'] }}</strong><span>En attente</span></div>
                        <div class="aj-agent-team-stat"><strong>{{ number_format((float) $row['revenue_generated'], 0, ',', ' ') }} DH</strong><span>Ventes</span></div>
                    </div>
                @empty
                    <div class="aj-agent-alert-box">Aucun agent rattache pour le moment.</div>
                @endforelse
            </div>
        </section>

        <section class="aj-agent-custom-board">
            <div class="aj-agent-custom-summary">
                <div>
                    <span class="aj-agent-manager-kicker">Demandes personnalisees</span>
                    <h2>Reservations a la carte</h2>
                    <p>Dossiers transmis par les agents et suivis par le service cotation.</p>
                </div>
                <div class="aj-agent-custom-metrics">
                    <div><strong>{{ number_format((int) ($customRequestStats['total'] ?? 0), 0, ',', ' ') }}</strong><span>Total</span></div>
                    <div><strong>{{ number_format((int) ($customRequestStats['new'] ?? 0), 0, ',', ' ') }}</strong><span>Nouvelles</span></div>
                    <div><strong>{{ number_format((int) ($customRequestStats['quoted'] ?? 0), 0, ',', ' ') }}</strong><span>Devis</span></div>
                    <div><strong>{{ number_format((int) ($customRequestStats['confirmed'] ?? 0), 0, ',', ' ') }}</strong><span>Confirmees</span></div>
                </div>
            </div>
            <div class="aj-agent-custom-list">
                @forelse($recentCustomRequests as $customRequest)
                    @php
                        $customUrl = route('agent.custom-reservations.show', $customRequest);
                        $customOwner = $customRequest->creator ?: $customRequest->assignedAgent;
                    @endphp
                    <a href="{{ $customUrl }}" class="aj-agent-custom-row">
                        <div>
                            <strong>{{ $customRequest->customer_full_name ?: 'Client non renseigne' }}</strong>
                            <span>{{ $customRequest->request_number }}{{ $customOwner ? ' - '.$customOwner->name : '' }}</span>
                        </div>
                        <div>
                            <strong>{{ $customRequest->desired_destination ?: 'Destination a definir' }}</strong>
                            <span>{{ optional($customRequest->desired_departure_date)->format('d/m/Y') ?: 'Date a definir' }}</span>
                        </div>
                        <div class="aj-agent-custom-tags">
                            <span>{{ $customRequest->travelers_count ?: 1 }} voyageur(s)</span>
                            <span>{{ CustomRequest::statusOptions()[$customRequest->status] ?? $customRequest->status }}</span>
                        </div>
                    </a>
                @empty
                    <div class="aj-agent-alert-box">Aucune demande a la carte visible pour votre equipe.</div>
                @endforelse
            </div>
        </section>
    @endif

    <section class="aj-agent-content-grid">
        <div class="aj-agent-panel aj-agent-panel-summary">
            <div class="aj-agent-panel-header">
                <div>
                    <h2>Aujourd'hui</h2>
                    <p>{{ $isManager ? 'Activite de votre agence.' : 'Resume rapide de l'activite.' }}</p>
                </div>
            </div>
            <div class="aj-agent-panel-body">
                <div class="aj-agent-section-kicker">Suivi operationnel</div>

                <div class="aj-agent-today-item">
                    <span>Reservations du jour</span>
                    <small>{{ number_format((int) ($todayStats['reservations_today'] ?? 0), 0, ',', ' ') }}</small>
                </div>

                <div class="aj-agent-today-item">
                    <span>En attente aujourd'hui</span>
                    <small>{{ number_format((int) ($todayStats['pending_today'] ?? 0), 0, ',', ' ') }}</small>
                </div>

                @if(!empty($todayStats['notifications']))
                    @foreach(($todayStats['notifications'] ?? []) as $notification)
                        <div class="aj-agent-alert-box">{{ $notification }}</div>
                    @endforeach
                @else
                    <div class="aj-agent-alert-box">Aucune alerte prioritaire aujourd'hui.</div>
                @endif

                <div class="aj-agent-quick-actions">
                    <a href="{{ $catalogueVoyageUrl }}" class="aj-agent-action-btn">
                        <i class="bx bx-plus-circle"></i>
                        <span>Creer une reservation</span>
                    </a>
                    <a href="{{ $catalogueVoyageUrl }}" class="aj-agent-action-btn">
                        <i class="bx bx-map-alt"></i>
                        <span>Voir les voyages disponibles</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="aj-agent-panel aj-agent-panel-wide">
            <div class="aj-agent-panel-header aj-agent-table-header">
                <div>
                    <h2>{{ $isManager ? 'Dernieres reservations agence' : 'Mes dernieres reservations' }}</h2>
                    <p>{{ $isManager ? 'Dossiers recents de votre equipe.' : 'Vue operationnelle sur les dossiers les plus recents.' }}</p>
                </div>
                <form method="GET" action="{{ route('agent.dashboard') }}" class="aj-agent-table-actions">
                    <select name="scope" id="scope" class="aj-agent-select" {{ $isManager ? '' : 'disabled' }}>
                        <option value="mine" {{ ($scope ?? 'mine') === 'mine' ? 'selected' : '' }}>Mes reservations</option>
                        @if($isManager)
                            <option value="team" {{ ($scope ?? 'team') === 'team' ? 'selected' : '' }}>Mon equipe</option>
                        @endif
                    </select>
                    @unless($isManager)
                        <input type="hidden" name="scope" value="mine">
                    @endunless
                    <button type="submit" class="aj-agent-small-btn aj-agent-small-btn-primary">Filtrer</button>
                </form>
            </div>

            <div class="aj-agent-table-wrap">
                <table class="aj-agent-table aj-agent-table-pro">
                    <colgroup>
                        <col style="width: 24%;">
                        <col style="width: 44%;">
                        <col style="width: 12%;">
                        <col style="width: 12%;">
                        <col style="width: 8%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Voyage</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th class="aj-agent-th-actions">Actions</th>
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
                            $detailUrl = Route::has('agent.reservations.show')
                                ? route('agent.reservations.show', $reservation)
                                : '#';
                            $displayDate = optional($reservation->travelDate?->date)->format('d/m/Y') ?: optional($reservation->created_at)->format('d/m/Y');
                            $owner = $reservation->agent ?: ($reservation->creator ?: $reservation->createdBy);
                        @endphp
                        <tr>
                            <td>
                                <div class="aj-agent-cell-main">{{ $clientName !== '' ? $clientName : 'Client non renseigne' }}</div>
                                <div class="aj-agent-cell-sub">
                                    {{ $reservation->dossier_number ?: 'Dossier #'.$reservation->id }}
                                    @if($isManager && $owner)
                                        - {{ $owner->name }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="aj-agent-cell-main aj-agent-cell-title">{{ $reservation->tour?->name ?: 'Voyage non renseigne' }}</div>
                                <div class="aj-agent-cell-sub">
                                    {{ $reservation->travelers_count ? $reservation->travelers_count.' voyageur(s)' : 'Dossier en cours' }}
                                    @if((float) ($reservation->total_amount ?? 0) > 0)
                                        - {{ number_format((float) $reservation->total_amount, 0, ',', ' ') }} DH
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="aj-agent-cell-date">{{ $displayDate }}</div>
                            </td>
                            <td>
                                <span class="aj-agent-status {{ $badgeClass }}">{{ $status }}</span>
                            </td>
                            <td class="aj-agent-td-actions">
                                <a href="{{ $detailUrl }}" class="aj-agent-small-btn">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="aj-agent-empty-state">
                                    <div class="aj-agent-empty-icon"><i class="bx bx-briefcase-alt-2"></i></div>
                                    <h3>Aucune reservation recente</h3>
                                    <p>Commencez par consulter le catalogue ou creer une nouvelle reservation.</p>
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
