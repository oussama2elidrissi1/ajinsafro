@extends('layouts.master-ajinsafro')

@section('title', 'Tableau de bord')

@section('hidePageFooter', '1')

@section('content')
@php
    use App\Models\CustomRequest;
    use App\Models\Reservation;
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $displayName = $user?->name ?: 'Agent';
    $firstName = trim((string) \Illuminate\Support\Str::before($displayName, ' ')) ?: $displayName;
    $agencyLabel = $user?->branch?->name ?: 'Ajinsafro';
    $catalogueUrl = route('agent.catalogue');
    $reservationsUrl = route('agent.reservations.index');
    $createUrl = Route::has('agent.reservations.create') ? route('agent.reservations.create') : $catalogueUrl;
    $profileUrl = Route::has('agent.profile') ? route('agent.profile') : null;
    $customUrl = Route::has('agent.custom-reservations.index') ? route('agent.custom-reservations.index') : null;
    $logoutUrl = Route::has('logout.get') ? route('logout.get') : null;

    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $total = (int) ($stats['reservations_total'] ?? 0);
    $confirmed = (int) ($stats['reservations_validees'] ?? 0);
    $pending = (int) ($stats['reservations_en_cours'] ?? 0);
    $confirmedPct = $total > 0 ? round(($confirmed / $total) * 100) : 0;
    $others = max(0, $total - $confirmed - $pending);

    $initials = strtoupper(
        collect(preg_split('/\s+/', trim((string) $displayName)))->filter()->take(2)->map(fn ($s) => mb_substr($s, 0, 1))->implode('')
    ) ?: 'AG';

    // Dernier dossier en attente : le contrôleur le fournit sous forme de phrase.
    $pendingPrefix = 'Dernier dossier en attente : ';
    $pendingNote = collect($todayStats['notifications'] ?? [])
        ->first(fn ($n) => \Illuminate\Support\Str::startsWith($n, $pendingPrefix));
    $pendingClient = $pendingNote
        ? rtrim(\Illuminate\Support\Str::after($pendingNote, $pendingPrefix), '.')
        : null;

    // Correspondance statut → présentation, alignée sur les couleurs du design.
    $statusMap = [
        Reservation::STATUS_CONFIRMED => ['Confirmée', 'confirmed'],
        Reservation::STATUS_PAID => ['Payée', 'confirmed'],
        Reservation::STATUS_PARTIALLY_PAID => ['Acompte', 'confirmed'],
        Reservation::STATUS_PENDING => ['En attente', 'pending'],
        Reservation::STATUS_DRAFT => ['Brouillon', 'pending'],
        Reservation::STATUS_OPTION => ['Option', 'pending'],
        Reservation::STATUS_EXPIRED => ['Expirée', 'pending'],
        Reservation::STATUS_SHARED_ROOM_PENDING => ['Chambre à jumeler', 'shared'],
        Reservation::STATUS_SHARED_ROOM_PAIRED => ['Chambre jumelée', 'confirmed'],
        Reservation::STATUS_CANCELLED => ['Annulée', 'cancelled'],
        Reservation::STATUS_REFUNDED => ['Remboursée', 'cancelled'],
    ];

    $rows = collect($recentReservations)->map(function ($reservation) use ($statusMap) {
        $clientName = trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? ''));
        [$label, $tone] = $statusMap[$reservation->status] ?? [ucfirst((string) $reservation->status), 'pending'];

        return [
            'model' => $reservation,
            'client' => $clientName !== '' ? $clientName : 'Client non renseigné',
            'ref' => $reservation->dossier_number ?: 'RES-' . str_pad((string) $reservation->id, 6, '0', STR_PAD_LEFT),
            'trip' => $reservation->tour?->name ?: 'Voyage non renseigné',
            'pax' => $reservation->passengers_count ? $reservation->passengers_count . ' voyageur(s)' : 'Dossier en cours',
            'date' => optional($reservation->travelDate?->date)->format('d/m/Y') ?: optional($reservation->created_at)->format('d/m/Y'),
            'amount' => (float) ($reservation->total_amount ?? 0),
            'label' => $label,
            'tone' => $tone,
        ];
    });

    $filters = [
        ['key' => 'all', 'label' => 'Tous', 'count' => $rows->count()],
        ['key' => 'pending', 'label' => 'En attente', 'count' => $rows->where('tone', 'pending')->count()],
        ['key' => 'shared', 'label' => 'À jumeler', 'count' => $rows->where('tone', 'shared')->count()],
        ['key' => 'confirmed', 'label' => 'Confirmées', 'count' => $rows->where('tone', 'confirmed')->count()],
    ];
@endphp

<div class="eag-dashboard">

    {{-- ═══ En-tête de page ═══ --}}
    <div class="eag-page-head">
        <div style="min-width:0">
            <span class="eag-eyebrow">{{ $isManager ? 'Tableau de bord agence' : 'Tableau de bord' }}</span>
            <h1 class="eag-h1">Bonjour {{ $firstName }}</h1>
            <p class="eag-page-sub">{{ $agencyLabel }} · {{ $total }} dossier{{ $total > 1 ? 's' : '' }} suivi{{ $total > 1 ? 's' : '' }}</p>
        </div>
        <div class="eag-page-actions">
            <a href="{{ $createUrl }}" class="eag-btn eag-btn--outline">Créer une réservation</a>
            <a href="{{ $catalogueUrl }}" class="eag-btn eag-btn--accent">Catalogue de voyage</a>
        </div>
    </div>

    {{-- ═══ Indicateurs ═══ --}}
    <section class="eag-kpis" aria-label="Indicateurs">
        <a href="{{ $reservationsUrl }}" class="eag-kpi">
            <div class="eag-kpi__label">{{ $isManager ? 'Portefeuille' : 'Réservations' }}</div>
            <div class="eag-kpi__value">{{ $fmt($total) }}</div>
            @if($total > 0)
                <div class="eag-kpi__bar" aria-hidden="true">
                    <span class="is-green" style="flex:{{ max(0, $confirmed) }}"></span>
                    <span class="is-accent" style="flex:{{ max(0, $pending) }}"></span>
                    <span class="is-rest" style="flex:{{ max($others, $confirmed + $pending === 0 ? 1 : 0) }}"></span>
                </div>
            @else
                <div class="eag-kpi__note">aucun dossier pour l'instant</div>
            @endif
        </a>
        <a href="{{ $reservationsUrl }}" class="eag-kpi eag-kpi--green">
            <div class="eag-kpi__label">Confirmées</div>
            <div class="eag-kpi__value">{{ $fmt($confirmed) }}</div>
            <div class="eag-kpi__note">{{ $confirmedPct }} % du portefeuille</div>
        </a>
        <a href="{{ $reservationsUrl }}" class="eag-kpi eag-kpi--warn">
            <div class="eag-kpi__label">En attente</div>
            <div class="eag-kpi__value">{{ $fmt($pending) }}</div>
            <div class="eag-kpi__note">{{ $pending > 0 ? 'à relancer' : 'rien à relancer' }}</div>
        </a>
        <div class="eag-kpi eag-kpi--blue">
            <div class="eag-kpi__label">{{ $isManager ? 'Total ventes' : 'Revenus' }}</div>
            <div class="eag-kpi__money"><span>{{ $fmt($stats['revenue_generated'] ?? 0) }}</span><span>DH</span></div>
            <div class="eag-kpi__note">encaissé et engagé</div>
        </div>
    </section>

    {{-- ═══ Dossiers + colonne de droite ═══ --}}
    <section class="eag-main">

        <div class="eag-col-main">
            <div class="eag-card">
                <div class="eag-card__head">
                    <div style="min-width:0">
                        <h2 class="eag-card__title">{{ $isManager ? 'Dernières réservations agence' : 'Mes dernières réservations' }}</h2>
                        <p class="eag-card__sub">{{ $isManager ? 'Dossiers récents de votre équipe.' : 'Vue opérationnelle sur les dossiers les plus récents.' }}</p>
                    </div>
                    <a href="{{ $reservationsUrl }}" class="eag-link">Tous mes dossiers →</a>
                </div>

                @if($isManager)
                    <form method="GET" action="{{ route('agent.dashboard') }}" class="eag-filters" style="align-items:center">
                        <label class="eag-filter" style="cursor:default">Périmètre</label>
                        <select name="scope" class="eag-filter" onchange="this.form.submit()" style="cursor:pointer">
                            <option value="team" {{ ($scope ?? 'team') === 'team' ? 'selected' : '' }}>Mon équipe</option>
                            <option value="mine" {{ ($scope ?? 'team') === 'mine' ? 'selected' : '' }}>Mes réservations</option>
                        </select>
                    </form>
                @endif

                @if($rows->isNotEmpty())
                    <div class="eag-filters" role="group" aria-label="Filtrer les dossiers">
                        @foreach($filters as $filter)
                            <button type="button" class="eag-filter {{ $filter['key'] === 'all' ? 'is-active' : '' }}" data-eag-filter="{{ $filter['key'] }}">
                                {{ $filter['label'] }}<b>{{ $filter['count'] }}</b>
                            </button>
                        @endforeach
                    </div>

                    <div class="eag-rows" data-eag-rows>
                        @foreach($rows as $row)
                            @php
                                $detailUrl = Route::has('agent.reservations.show') ? route('agent.reservations.show', $row['model']) : null;
                            @endphp
                            <div class="eag-row eag-row--{{ $row['tone'] }}" data-eag-status="{{ $row['tone'] }}">
                                <div class="eag-row__client">
                                    <div class="eag-row__name">{{ $row['client'] }}</div>
                                    <div class="eag-row__ref">{{ $row['ref'] }}</div>
                                </div>
                                <div class="eag-row__trip">
                                    <div class="eag-row__trip-name">{{ \Illuminate\Support\Str::limit($row['trip'], 90) }}</div>
                                    <div class="eag-row__pax">{{ $row['pax'] }}</div>
                                </div>
                                <div class="eag-row__figures">
                                    <div class="eag-row__date">{{ $row['date'] }}</div>
                                    @if($row['amount'] > 0)
                                        <div class="eag-row__amount">{{ $fmt($row['amount']) }} DH</div>
                                    @endif
                                </div>
                                <span class="eag-status eag-status--{{ $row['tone'] }}">{{ $row['label'] }}</span>
                                @if($detailUrl)
                                    <a href="{{ $detailUrl }}" class="eag-row__action">Voir</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="eag-empty" data-eag-filter-empty hidden>Aucun dossier dans ce filtre.</div>
                @else
                    <div class="eag-empty">
                        <strong>Aucune réservation récente</strong>
                        Commencez par consulter le catalogue ou créer une nouvelle réservation.
                    </div>
                @endif
            </div>
        </div>

        <div class="eag-col-side">

            {{-- Aujourd'hui --}}
            <section class="eag-side-card eag-side-card--navy">
                <h2 class="eag-side-card__title">Aujourd'hui</h2>
                <p class="eag-side-card__sub">{{ $isManager ? 'Activité de votre agence.' : "Résumé rapide de l'activité" }}</p>
                <div class="eag-today__stats">
                    <div class="eag-today__stat">
                        <div class="eag-today__stat-label">Réservations</div>
                        <div class="eag-today__stat-value">{{ $fmt($todayStats['reservations_today'] ?? 0) }}</div>
                    </div>
                    <div class="eag-today__stat">
                        <div class="eag-today__stat-label">En attente</div>
                        <div class="eag-today__stat-value">{{ $fmt($todayStats['pending_today'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="eag-today__note">
                    @if($pendingClient)
                        Dernier dossier en attente : <b>{{ $pendingClient }}</b>
                    @else
                        Aucune alerte prioritaire aujourd'hui.
                    @endif
                </div>
                <div class="eag-today__actions">
                    <a href="{{ $createUrl }}" class="eag-today__action">Créer une réservation<span aria-hidden="true">+</span></a>
                    <a href="{{ $catalogueUrl }}" class="eag-today__action eag-today__action--ghost">Voir les voyages disponibles<span aria-hidden="true">→</span></a>
                </div>
            </section>

            {{-- Profil --}}
            <section class="eag-side-card">
                <div class="eag-profile">
                    <span class="eag-profile__avatar" aria-hidden="true">
                        <span>{{ $initials }}</span>
                        @if($user?->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="" onerror="this.remove();">
                        @endif
                    </span>
                    <div style="min-width:0">
                        <div class="eag-profile__name">{{ $displayName }}</div>
                        <div class="eag-profile__role">Agent · {{ $agencyLabel }}</div>
                    </div>
                </div>
                <div class="eag-profile__links">
                    @if($profileUrl)
                        <a href="{{ $profileUrl }}" class="eag-profile__link">Mon profil<span class="eag-profile__chev" aria-hidden="true">›</span></a>
                    @endif
                    @if($customUrl)
                        <a href="{{ $customUrl }}" class="eag-profile__link">Réservations à la carte<span class="eag-profile__chev" aria-hidden="true">›</span></a>
                    @endif
                    @if($logoutUrl)
                        <a href="{{ $logoutUrl }}" class="eag-profile__link is-danger">Se déconnecter<span class="eag-profile__chev" aria-hidden="true">›</span></a>
                    @endif
                </div>
            </section>

            {{-- Support --}}
            <section class="eag-side-card eag-side-card--dark">
                <div class="eag-support__title">Un souci sur un dossier ?</div>
                <p class="eag-support__text">L'équipe support répond sous 2 h ouvrées.</p>
                <button type="button" class="eag-support__btn" data-dev-reclamation-open>Signaler un problème</button>
            </section>
        </div>
    </section>

    {{-- ═══ Demandes à la carte (responsables d'agence) ═══ --}}
    @if($isManager)
        @php
            $agentRows = $managerStats['agents'] ?? collect();
        @endphp
        <section class="eag-card">
            <div class="eag-card__head">
                <div style="min-width:0">
                    <h2 class="eag-card__title">Équipe {{ $agencyLabel }}</h2>
                    <p class="eag-card__sub">Suivi des agents rattachés et de leur volume de réservations.</p>
                </div>
                <a href="{{ $reservationsUrl }}" class="eag-link">Voir toutes les réservations →</a>
            </div>
            <div class="eag-rows">
                @forelse($agentRows as $agentRow)
                    @php $agent = $agentRow['user']; @endphp
                    <div class="eag-row">
                        <div class="eag-row__client">
                            <div class="eag-row__name">{{ $agent->name }}</div>
                            <div class="eag-row__ref">{{ $agent->email }}</div>
                        </div>
                        <div class="eag-row__figures">
                            <div class="eag-row__date">Réservations</div>
                            <div class="eag-row__amount">{{ $fmt($agentRow['reservations_total']) }}</div>
                        </div>
                        <div class="eag-row__figures">
                            <div class="eag-row__date">En attente</div>
                            <div class="eag-row__amount">{{ $fmt($agentRow['reservations_en_cours']) }}</div>
                        </div>
                        <div class="eag-row__figures">
                            <div class="eag-row__date">Ventes</div>
                            <div class="eag-row__amount">{{ $fmt($agentRow['revenue_generated']) }} DH</div>
                        </div>
                    </div>
                @empty
                    <div class="eag-empty">Aucun agent rattaché pour le moment.</div>
                @endforelse
            </div>
        </section>

        <section class="eag-card">
            <div class="eag-card__head">
                <div style="min-width:0">
                    <h2 class="eag-card__title">Réservations à la carte</h2>
                    <p class="eag-card__sub">Dossiers transmis par les agents et suivis par le service quotation.</p>
                </div>
                @if($customUrl)
                    <a href="{{ $customUrl }}" class="eag-link">Voir tout →</a>
                @endif
            </div>
            <div class="eag-filters" aria-hidden="true">
                <span class="eag-filter">Total<b>{{ $fmt($customRequestStats['total'] ?? 0) }}</b></span>
                <span class="eag-filter">Nouvelles<b>{{ $fmt($customRequestStats['new'] ?? 0) }}</b></span>
                <span class="eag-filter">Devis<b>{{ $fmt($customRequestStats['quoted'] ?? 0) }}</b></span>
                <span class="eag-filter">Confirmées<b>{{ $fmt($customRequestStats['confirmed'] ?? 0) }}</b></span>
            </div>
            <div class="eag-rows">
                @forelse($recentCustomRequests as $customRequest)
                    @php
                        $customShowUrl = Route::has('agent.custom-reservations.show') ? route('agent.custom-reservations.show', $customRequest) : null;
                        $customOwner = $customRequest->creator ?: $customRequest->assignedAgent;
                    @endphp
                    <div class="eag-row">
                        <div class="eag-row__client">
                            <div class="eag-row__name">{{ $customRequest->customer_full_name ?: 'Client non renseigné' }}</div>
                            <div class="eag-row__ref">{{ $customRequest->request_number }}{{ $customOwner ? ' · ' . $customOwner->name : '' }}</div>
                        </div>
                        <div class="eag-row__trip">
                            <div class="eag-row__trip-name">{{ $customRequest->desired_destination ?: 'Destination à définir' }}</div>
                            <div class="eag-row__pax">{{ $customRequest->travelers_count ?: 1 }} voyageur(s)</div>
                        </div>
                        <div class="eag-row__figures">
                            <div class="eag-row__date">{{ optional($customRequest->desired_departure_date)->format('d/m/Y') ?: 'Date à définir' }}</div>
                        </div>
                        <span class="eag-status">{{ CustomRequest::statusOptions()[$customRequest->status] ?? $customRequest->status }}</span>
                        @if($customShowUrl)
                            <a href="{{ $customShowUrl }}" class="eag-row__action">Voir</a>
                        @endif
                    </div>
                @empty
                    <div class="eag-empty">Aucune demande à la carte visible pour votre équipe.</div>
                @endforelse
            </div>
        </section>
    @endif

    <footer class="eag-footer">
        <div class="eag-footer__legal">© Ajinsafro SARL AU</div>
        <div class="eag-footer__licence">Licence N° 489117 | RC: 18989 | IF: 15254892</div>
    </footer>
</div>
@endsection
