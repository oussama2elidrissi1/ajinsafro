@extends('partner_v2.layouts.app')
@section('title', 'Tableau de bord')

@section('content')
@php
    /** Tableau de bord partenaire — maquette « Portail Agence Partenaire ». */
    $ppUser = auth()->user();
    $ppIsAdmin = (bool) $ppUser?->isPartnerAdmin();
    $ppAgencyName = $partner?->display_name ?: ($ppUser?->name ?? 'Agence partenaire');
    $ppRole = $ppIsAdmin ? 'Admin partenaire' : 'Agent partenaire';

    $ppMoney = fn ($value) => number_format((float) $value, 0, ',', ' ');
    $ppBalance = (float) ($partner?->wallet_balance ?? 0);
    $ppRecharged = (float) ($walletRechargedTotal ?? 0);

    $ppHasReservations = $reservationsCount > 0;
    $ppSummary = $ppHasReservations
        ? 'Compte actif · ' . $reservationsCount . ' réservation' . ($reservationsCount > 1 ? 's' : '') . ' enregistrée' . ($reservationsCount > 1 ? 's' : '')
        : 'Compte actif · aucune réservation enregistrée à ce jour';

    $ppCatalogueUrl = Route::has('partner.catalogue.index') ? route('partner.catalogue.index') : null;
    $ppReservationsUrl = Route::has('partner.reservations.index') ? route('partner.reservations.index') : null;
    $ppCreateUrl = Route::has('partner.reservations.create') ? route('partner.reservations.create') : null;
    $ppAgentsUrl = $ppIsAdmin && Route::has('partner.agents.index') ? route('partner.agents.index') : null;
    $ppWalletUrl = $ppIsAdmin && Route::has('partner.wallet.index') ? route('partner.wallet.index') : null;
    $ppProfileAgencyUrl = $ppIsAdmin && Route::has('partner.profile-agency.edit') ? route('partner.profile-agency.edit') : null;
    $ppProfileUrl = Route::has('partner.profile.show') ? route('partner.profile.show') : null;
    $ppLogoutUrl = Route::has('partner.logout') ? route('partner.logout') : null;

    // Statuts « validés » et « en attente » : mêmes ensembles que le contrôleur.
    $ppOkStatuses = [
        \App\Models\Reservation::STATUS_CONFIRMED,
        \App\Models\Reservation::STATUS_PARTIALLY_PAID,
        \App\Models\Reservation::STATUS_PAID,
    ];
    $ppWaitStatuses = [
        \App\Models\Reservation::STATUS_PENDING,
        \App\Models\Reservation::STATUS_DRAFT,
        \App\Models\Reservation::STATUS_OPTION,
    ];
@endphp

<div class="pp-page-head">
    <div class="pp-page-head__text">
        <span class="pp-eyebrow">Portail agence partenaire</span>
        <h1 class="pp-title">{{ $ppAgencyName }}</h1>
        <p class="pp-subtitle">{{ $ppSummary }}</p>
    </div>
    <div class="pp-page-head__actions">
        @if($ppAgentsUrl)
            <a href="{{ $ppAgentsUrl }}" class="pp-btn pp-btn--outline">Inviter un agent</a>
        @endif
        @if($ppCatalogueUrl)
            <a href="{{ $ppCatalogueUrl }}" class="pp-btn pp-btn--accent">Catalogue de voyage</a>
        @endif
    </div>
</div>

<section class="pp-row">
    <div class="pp-wallet">
        <div>
            <span class="pp-wallet__label">Solde wallet</span>
            <div class="pp-wallet__amount">
                <span class="pp-wallet__value">{{ $ppMoney($ppBalance) }}</span>
                <span class="pp-wallet__unit">DH</span>
            </div>
            <div class="pp-wallet__hint">après validations · {{ $ppMoney($ppRecharged) }} DH rechargés</div>
        </div>
        @if($ppWalletUrl)
            <div class="pp-wallet__actions">
                <a href="{{ $ppWalletUrl }}#recharge" class="pp-btn pp-btn--white">Recharger le wallet</a>
                <a href="{{ $ppWalletUrl }}" class="pp-btn pp-btn--ghost">Historique</a>
            </div>
        @endif
    </div>

    <div class="pp-kpis">
        <div class="pp-kpi">
            <div class="pp-kpi__label">Réservations</div>
            <div class="pp-kpi__value">{{ $reservationsCount }}</div>
            <div class="pp-kpi__hint">{{ $reservationsThisMonth }} ce mois</div>
        </div>
        <div class="pp-kpi">
            <div class="pp-kpi__label">Confirmées</div>
            <div class="pp-kpi__value">{{ $confirmedReservations }}</div>
            <div class="pp-kpi__hint">dossiers validés</div>
        </div>
        <div class="pp-kpi">
            <div class="pp-kpi__label">En attente</div>
            <div class="pp-kpi__value">{{ $pendingReservations }}</div>
            <div class="pp-kpi__hint">à suivre</div>
        </div>
        <div class="pp-kpi pp-kpi--soft">
            <div class="pp-kpi__label">Total ventes</div>
            <div class="pp-kpi__money">
                <b>{{ $ppMoney($salesTotal) }}</b>
                <i>DH</i>
            </div>
            <div class="pp-kpi__hint">réservations agence</div>
        </div>
    </div>
</section>

<section class="pp-row pp-row--split">
    <div class="pp-col-main">
        <div class="pp-panel">
            <div class="pp-panel__head">
                <h2 class="pp-panel__title">Dernières réservations</h2>
                @if($ppReservationsUrl)
                    <a href="{{ $ppReservationsUrl }}" class="pp-panel__link">Voir tout →</a>
                @endif
            </div>

            @forelse($recentReservations as $ppReservation)
                @if($loop->first)
                    <div class="pp-list">
                @endif
                @php
                    $ppClient = trim(($ppReservation->client_first_name ?? '') . ' ' . ($ppReservation->client_last_name ?? ''));
                    $ppStatusClass = in_array($ppReservation->status, $ppOkStatuses, true)
                        ? 'is-ok'
                        : (in_array($ppReservation->status, $ppWaitStatuses, true) ? 'is-wait' : '');
                @endphp
                <div class="pp-res">
                    <div class="pp-res__who">
                        <div class="pp-res__name">{{ $ppClient !== '' ? $ppClient : 'Client non renseigné' }}</div>
                        <div class="pp-res__ref">{{ $ppReservation->dossier_number ?: ('#' . $ppReservation->id) }}</div>
                    </div>
                    <div class="pp-res__tour">{{ $ppReservation->tour?->name ?? 'Voyage non renseigné' }}</div>
                    <div class="pp-res__amount">{{ $ppMoney($ppReservation->effective_total_amount) }} DH</div>
                    <span class="pp-res__status {{ $ppStatusClass }}">{{ $ppReservation->statusLabelFr() }}</span>
                    @if(Route::has('partner.reservations.show'))
                        <a href="{{ route('partner.reservations.show', $ppReservation) }}" class="pp-res__action">Ouvrir</a>
                    @endif
                </div>
                @if($loop->last)
                    </div>
                @endif
            @empty
                <div class="pp-empty">
                    <span class="pp-empty__icon" aria-hidden="true">+</span>
                    <div class="pp-empty__title">Aucune réservation pour le moment</div>
                    <p class="pp-empty__text">Parcourez le catalogue pour créer le premier dossier de l'agence. Les réservations de vos agents apparaîtront ici.</p>
                    <div class="pp-empty__actions">
                        @if($ppCreateUrl)
                            <a href="{{ $ppCreateUrl }}" class="pp-btn pp-btn--primary">Créer une réservation</a>
                        @endif
                        @if($ppCatalogueUrl)
                            <a href="{{ $ppCatalogueUrl }}" class="pp-btn pp-btn--outline">Voir le catalogue</a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="pp-col-side">
        @if($ppWalletUrl)
            <section class="pp-panel pp-panel--tight">
                <div class="pp-panel__head">
                    <h2 class="pp-panel__title">Opérations wallet</h2>
                    <a href="{{ $ppWalletUrl }}" class="pp-panel__link">Wallet →</a>
                </div>
                <div class="pp-ops">
                    @forelse($recentWalletTransactions as $ppTransaction)
                        @php
                            $ppOpState = $ppTransaction->status === \App\Models\PartnerWalletTransaction::STATUS_APPROVED
                                ? ''
                                : ($ppTransaction->status === \App\Models\PartnerWalletTransaction::STATUS_REJECTED ? 'is-ko' : 'is-wait');
                            $ppOpSign = $ppTransaction->type === \App\Models\PartnerWalletTransaction::TYPE_DEBIT ? '−' : '+';
                        @endphp
                        <div class="pp-op">
                            <span class="pp-op__icon {{ $ppOpState }}" aria-hidden="true">{{ $ppOpSign }}</span>
                            <div class="pp-op__body">
                                <div class="pp-op__label">{{ \Illuminate\Support\Str::ucfirst($ppTransaction->type) }}</div>
                                <div class="pp-op__date">{{ $ppTransaction->created_at?->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="pp-op__right">
                                <div class="pp-op__amount">{{ $ppMoney($ppTransaction->amount) }} DH</div>
                                <div class="pp-op__status {{ $ppOpState }}">{{ $ppTransaction->status }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="pp-empty__text" style="margin:6px 0 0;">Aucune opération wallet enregistrée.</p>
                    @endforelse
                </div>
                <div class="pp-ops__total">
                    <span class="pp-ops__total-label">Total rechargé</span>
                    <span class="pp-ops__total-value">{{ $ppMoney($ppRecharged) }} DH</span>
                </div>
            </section>
        @endif

        <section class="pp-panel pp-panel--tight">
            <h2 class="pp-panel__title">Mon agence</h2>
            <div class="pp-agency">
                <div class="pp-agency__logo">
                    @if($partner?->logo_url)
                        <img src="{{ $partner->logo_url }}" alt="{{ $ppAgencyName }}" onerror="this.remove();">
                    @endif
                </div>
                <div style="min-width:0;">
                    <div class="pp-agency__name">{{ $ppAgencyName }}</div>
                    <div class="pp-agency__role">{{ $ppRole }}</div>
                </div>
            </div>
            <div class="pp-links">
                @if($ppAgentsUrl)
                    <a href="{{ $ppAgentsUrl }}" class="pp-link">Mes agents<span class="pp-link__chev" aria-hidden="true">›</span></a>
                @endif
                @if($ppProfileAgencyUrl)
                    <a href="{{ $ppProfileAgencyUrl }}" class="pp-link">Profil agence<span class="pp-link__chev" aria-hidden="true">›</span></a>
                @elseif($ppProfileUrl)
                    <a href="{{ $ppProfileUrl }}" class="pp-link">Mon profil<span class="pp-link__chev" aria-hidden="true">›</span></a>
                @endif
                @if($ppLogoutUrl)
                    <form method="POST" action="{{ $ppLogoutUrl }}">
                        @csrf
                        <button type="submit" class="pp-link is-danger">Déconnexion<span class="pp-link__chev" aria-hidden="true">›</span></button>
                    </form>
                @endif
            </div>
        </section>
    </div>
</section>
@endsection
