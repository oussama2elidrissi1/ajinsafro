@extends('layouts.finance-control')

@section('title', 'Finance — vue d\'ensemble')

@php
    // Formatage monetaire unique pour tout le module.
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
    $short = fn ($value) => number_format((float) $value, 0, ',', ' ');
    $pct = fn ($value) => number_format((float) $value, 1, ',', ' ').' %';

    $collected = (float) $totals['collected_amount'];
    $sold = (float) $totals['sold_amount'];
    $clientRemaining = (float) $totals['client_remaining'];
    $collectionRate = $totals['collection_rate'] ?? 0.0;

    $travelCharges = (float) $totals['real_charges'];
    $structuralCharges = (float) $structural['amount'];
    $totalCharges = $travelCharges + $structuralCharges;
    $noCharges = $totalCharges <= 0;

    $emptyCount = $totals['empty_count'] ?? 0;

    $months = $revenueByMonth->take(-12);
    $monthsMax = (float) ($months->max('amount') ?: 0);
    $collectionsMax = (float) ($collectionsByBranch->max('amount') ?: 0);
    $collectionsTotal = (float) $collectionsByBranch->sum('amount');
    $chargesMax = (float) ($chargesByCategory->max('amount') ?: 0);

    $tops = $topMargins->where('real_margin', '>', 0)->take(6)->values();
    $topsMax = (float) ($tops->max('real_margin') ?: 0);
    $marginTotal = (float) $totals['real_margin'];

    $watchlist = $worstMargins->where('real_margin', '<', 0)->take(6)->values();
@endphp

@section('finance_content')

    <div class="fc-head">
        <div style="min-width:0;">
            <div class="fc-crumb">
                <span>Administration</span><i>/</i><span>Finance &amp; contrôle</span><i>/</i><b>Vue d'ensemble</b>
            </div>
            <h1 class="fc-head-title">Finance &amp; contrôle</h1>
            <p class="fc-head-sub">Ventes, encaissements, charges et résultat de gestion sur la période sélectionnée.</p>
        </div>
        <div class="fc-head-actions">
            <a href="{{ route('admin.finance.control.exports.index') }}" class="ea-btn-outline">Exports comptables</a>
            @can('finance.expenses.manage')
                <a href="{{ route('admin.finance.control.travel-expenses.create') }}" class="ea-btn-accent">+ Ajouter une charge</a>
            @endcan
        </div>
    </div>

    @include('admin.finance.control.partials._tabs', ['current' => 'overview'])

    @include('admin.finance.control.partials._filters', [
        'action' => route('admin.finance.control.dashboard'),
        'filters' => $filters,
        'voyages' => $voyages,
        'branches' => $branches,
    ])

    @if ($emptyCount > 0)
        <section class="fc-notice">
            <span class="fc-notice-mark" aria-hidden="true">!</span>
            <div class="fc-notice-body">
                <div class="fc-notice-title">{{ $emptyCount }} départ{{ $emptyCount > 1 ? 's' : '' }} sans aucune donnée financière</div>
                <p class="fc-notice-text">
                    Ni vente ni charge n'est enregistrée sur ces départs : ils ne sont pas déficitaires, ils sont incomplets.
                    @if ($noCharges)
                        Aucune charge n'étant saisie sur la période, la marge affichée ({{ $pct($totals['margin_rate']) }}) est mécanique, pas réelle.
                    @endif
                    Saisissez les charges fournisseurs pour obtenir un résultat de gestion exploitable.
                </p>
            </div>
            <div class="fc-notice-actions">
                @can('finance.expenses.manage')
                    <a href="{{ route('admin.finance.control.travel-expenses.create') }}" class="fc-notice-btn is-solid">Saisir les charges</a>
                @endcan
                <a href="{{ route('admin.finance.control.travel-projects.index') }}" class="fc-notice-btn">Voir les projets concernés</a>
            </div>
        </section>
    @endif

    <section class="fc-cycles">
        {{-- Cycle client : ce qui est vendu et ce qui est reellement rentre. --}}
        <div class="fc-cycle">
            <div class="fc-cycle-head">
                <span class="fc-cycle-dot" aria-hidden="true"></span>
                <span class="fc-cycle-label">Cycle client</span>
            </div>
            <div class="fc-figure">
                <span class="fc-figure-value">{{ $short($sold) }}</span>
                <span class="fc-figure-unit">DH vendus</span>
            </div>
            @if ($sold > 0)
                <div class="fc-split">
                    <span style="flex:{{ max(0, $collected) }};"></span>
                    <span style="flex:{{ max(0, $sold - $collected) }};"></span>
                </div>
            @else
                <div class="fc-split is-empty"></div>
            @endif
            <div class="fc-lines">
                <div class="fc-line">
                    <span><i class="fc-dot" aria-hidden="true"></i>Encaissé</span>
                    <b>{{ $money($collected) }}</b>
                </div>
                <div class="fc-line">
                    <span><i class="fc-dot is-grey" aria-hidden="true"></i>Reste à encaisser</span>
                    <b class="{{ $clientRemaining > 0 ? 'is-orange' : '' }}">{{ $money($clientRemaining) }}</b>
                </div>
            </div>
            <div class="fc-cycle-foot">Taux de recouvrement <b>{{ $pct($collectionRate) }}</b></div>
        </div>

        {{-- Cycle fournisseur : charges voyages + charges de structure. --}}
        <div class="fc-cycle">
            <div class="fc-cycle-head">
                <span class="fc-cycle-dot is-accent" aria-hidden="true"></span>
                <span class="fc-cycle-label is-orange">Cycle fournisseur</span>
            </div>
            <div class="fc-figure">
                <span class="fc-figure-value {{ $noCharges ? 'is-muted' : '' }}">{{ $short($totalCharges) }}</span>
                <span class="fc-figure-unit">DH de charges</span>
            </div>
            @if ($noCharges)
                <div class="fc-split is-empty"></div>
            @else
                <div class="fc-split is-supplier">
                    <span style="flex:{{ max(0, $travelCharges) }};"></span>
                    <span style="flex:{{ max(0, $structuralCharges) }};"></span>
                </div>
            @endif
            <div class="fc-lines">
                <div class="fc-line">
                    <span>Charges voyages</span>
                    <b class="{{ $travelCharges > 0 ? '' : 'is-muted' }}">{{ $money($travelCharges) }}</b>
                </div>
                <div class="fc-line">
                    <span>Charges de structure</span>
                    <b class="{{ $structuralCharges > 0 ? '' : 'is-muted' }}">{{ $money($structuralCharges) }}</b>
                </div>
                <div class="fc-line">
                    <span>Reste dû fournisseurs</span>
                    <b class="{{ $totals['supplier_remaining'] > 0 ? 'is-orange' : 'is-muted' }}">{{ $money($totals['supplier_remaining']) }}</b>
                </div>
            </div>
            @if ($noCharges)
                <div class="fc-cycle-foot is-orange">Aucune charge sur la période</div>
            @else
                <div class="fc-cycle-foot">Charges structure payées <b>{{ $money($structural['paid']) }}</b></div>
            @endif
        </div>

        {{-- Resultat de gestion : marge voyages moins charges de structure. --}}
        <div class="fc-cycle is-navy">
            <div class="fc-cycle-head">
                <span class="fc-cycle-dot is-accent" aria-hidden="true"></span>
                <span class="fc-cycle-label">Résultat de gestion</span>
            </div>
            <div class="fc-figure">
                <span class="fc-figure-value">{{ $short($managementResult) }}</span>
                <span class="fc-figure-unit">DH</span>
            </div>
            @if ($noCharges)
                <div class="fc-flag">Provisoire — charges non saisies</div>
            @endif
            <div class="fc-lines">
                <div class="fc-line"><span>Marge voyages</span><b>{{ $money($totals['real_margin']) }}</b></div>
                <div class="fc-line"><span>− Charges structure</span><b>{{ $money($structuralCharges) }}</b></div>
                <div class="fc-line"><span>Taux de marge</span><b class="is-accent">{{ $pct($totals['margin_rate']) }}</b></div>
            </div>
            <p class="fc-cycle-note">
                Indicateur de pilotage interne. Amortissements, provisions et retraitements fiscaux ne sont pas intégrés.
            </p>
        </div>
    </section>

    <section class="fc-duo">
        <div class="ea-card">
            <div class="ea-card-head">
                <div style="min-width:0;">
                    <h2 class="ea-card-title">Encaissements par mois</h2>
                    <p class="ea-card-sub">Total encaissé sur la période : {{ $money($collected) }}</p>
                </div>
            </div>
            @if ($months->isEmpty())
                <p class="ea-empty">Aucun encaissement sur la période.</p>
            @else
                <div class="fc-months">
                    @foreach ($months as $month)
                        <div class="fc-month">
                            <span class="fc-month-value">{{ $short($month['amount']) }}</span>
                            <span class="fc-month-bar" style="height:{{ $monthsMax > 0 ? max(2, round($month['amount'] / $monthsMax * 100)) : 2 }}%;"></span>
                            <span class="fc-month-label">{{ $month['period'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="ea-card">
            <h2 class="ea-card-title">Encaissements par agence</h2>
            <p class="ea-card-sub" style="margin-bottom:16px;">Répartition du recouvrement</p>
            @if ($collectionsByBranch->isEmpty())
                <p class="ea-empty">Aucun encaissement sur la période.</p>
            @else
                <div class="fc-shares">
                    @foreach ($collectionsByBranch as $line)
                        @php($unassigned = $line['label'] === 'Non rattache')
                        @php($share = $collectionsTotal > 0 ? $line['amount'] / $collectionsTotal * 100 : 0)
                        <div>
                            <div class="fc-share-head">
                                <span>{{ $unassigned ? 'Non rattaché' : $line['label'] }}</span>
                                <span>{{ $money($line['amount']) }}</span>
                            </div>
                            <div class="fc-track">
                                <span class="{{ $unassigned ? 'is-accent' : '' }}"
                                      style="width:{{ $collectionsMax > 0 ? round($line['amount'] / $collectionsMax * 100, 1) : 0 }}%;"></span>
                            </div>
                            <div class="fc-share-note {{ $unassigned ? 'is-orange' : '' }}">
                                {{ number_format($share, 0, ',', ' ') }} % {{ $unassigned ? 'à rattacher à une agence' : 'des encaissements' }}
                            </div>
                        </div>
                    @endforeach
                </div>
                @if ($collectionsByBranch->contains('label', 'Non rattache'))
                    <div class="fc-hint is-orange" style="margin-top:16px;">
                        Rattacher les encaissements « non rattaché » permet de comparer la performance réelle des agences.
                    </div>
                @endif
            @endif
        </div>
    </section>

    <section class="fc-duo">
        <div class="ea-card">
            <h2 class="ea-card-title">Charges par catégorie</h2>
            <p class="ea-card-sub" style="margin-bottom:16px;">Charges voyages comptabilisées sur la période</p>
            @if ($chargesByCategory->isEmpty())
                <p class="ea-empty">Aucune charge saisie sur la période.</p>
            @else
                <div class="fc-shares">
                    @foreach ($chargesByCategory as $line)
                        <div>
                            <div class="fc-share-head">
                                <span>{{ $line['label'] }}</span>
                                <span>{{ $money($line['amount']) }}</span>
                            </div>
                            <div class="fc-track">
                                <span class="is-accent" style="width:{{ $chargesMax > 0 ? round($line['amount'] / $chargesMax * 100, 1) : 0 }}%;"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="ea-card">
            <div class="ea-card-head">
                <div style="min-width:0;">
                    <h2 class="ea-card-title">Projets à surveiller</h2>
                    <p class="ea-card-sub">Départs dont les charges dépassent les ventes</p>
                </div>
                <span class="ea-tag {{ $watchlist->isEmpty() ? 'is-green' : 'is-red' }}">{{ $watchlist->count() }}</span>
            </div>
            @if ($watchlist->isEmpty())
                <p class="ea-empty">Aucun projet en perte sur la période.</p>
            @else
                <div class="fc-top">
                    @foreach ($watchlist as $row)
                        <a href="{{ route('admin.finance.control.travel-projects.show', $row['departure']) }}" class="fc-top-row">
                            <div class="fc-top-body">
                                <div class="fc-top-title">{{ $row['departure']->voyage?->name ?: 'Voyage non renseigné' }}</div>
                                <div class="fc-top-sub">DEP-{{ $row['departure']->id }} · départ {{ $row['departure']->start_date?->format('d/m/Y') ?: '—' }}</div>
                            </div>
                            <span class="fc-top-amount is-negative">{{ $money($row['real_margin']) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="ea-card">
        <div class="ea-card-head">
            <div style="min-width:0;">
                <h2 class="ea-card-title">Top marges par voyage</h2>
                <p class="ea-card-sub">Départs contribuant le plus au résultat de la période</p>
            </div>
            <a href="{{ route('admin.finance.control.travel-projects.index') }}" class="ea-link">Tous les projets →</a>
        </div>
        @if ($tops->isEmpty())
            <p class="ea-empty">Aucune marge positive sur la période.</p>
        @else
            <div class="fc-top">
                @foreach ($tops as $index => $row)
                    <a href="{{ route('admin.finance.control.travel-projects.show', $row['departure']) }}" class="fc-top-row">
                        <span class="fc-rank">{{ $index + 1 }}</span>
                        <div class="fc-top-body">
                            <div class="fc-top-title">{{ $row['departure']->voyage?->name ?: 'Voyage non renseigné' }}</div>
                            <div class="fc-top-sub">départ {{ $row['departure']->start_date?->format('d/m/Y') ?: '—' }}</div>
                        </div>
                        <div class="fc-top-bar">
                            <div class="fc-track is-thin">
                                <span class="is-green" style="width:{{ $topsMax > 0 ? round($row['real_margin'] / $topsMax * 100, 1) : 0 }}%;"></span>
                            </div>
                            <div class="fc-share-note">
                                {{ $marginTotal > 0 ? number_format($row['real_margin'] / $marginTotal * 100, 0, ',', ' ').' % du résultat' : '—' }}
                            </div>
                        </div>
                        <span class="fc-top-amount">{{ $money($row['real_margin']) }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

@endsection
