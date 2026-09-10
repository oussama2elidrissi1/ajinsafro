@extends('layouts.finance-control')

@section('title', 'Projets de voyage')

@php
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
    $short = fn ($value) => number_format((float) $value, 0, ',', ' ').' DH';
    $pct = fn ($value) => number_format((float) $value, 1, ',', ' ').' %';

    $states = [
        'profitable' => ['label' => 'Rentable', 'class' => 'is-ok'],
        'deficit' => ['label' => 'Déficitaire', 'class' => 'is-bad'],
        'neutral' => ['label' => 'À l\'équilibre', 'class' => 'is-warn'],
        'empty' => ['label' => 'À compléter', 'class' => 'is-warn'],
    ];

    $sortOptions = [
        \App\Http\Controllers\Admin\Finance\Control\TravelProjectController::SORT_MARGIN => 'Marge décroissante',
        \App\Http\Controllers\Admin\Finance\Control\TravelProjectController::SORT_DATE => 'Date de départ',
        \App\Http\Controllers\Admin\Finance\Control\TravelProjectController::SORT_CLIENT_REMAINING => 'Reste clients',
    ];

    $canManageExpenses = auth()->user()?->can('finance.expenses.manage');
@endphp

@section('finance_content')

    <div class="fc-head">
        <div style="min-width:0;">
            <div class="fc-crumb">
                <span>Administration</span><i>/</i>
                <a href="{{ route('admin.finance.control.dashboard') }}">Finance &amp; contrôle</a><i>/</i>
                <b>Projets de voyage</b>
            </div>
            <h1 class="fc-head-title">Projets de voyage</h1>
            <p class="fc-head-sub">
                Un projet financier correspond à un départ réel. Ventes, encaissements, charges et marge sont calculés séparément.
            </p>
        </div>
        <div class="fc-head-actions">
            <a href="{{ route('admin.finance.control.exports.index') }}" class="ea-btn-outline">Exports comptables</a>
            @if ($canManageExpenses)
                <a href="{{ route('admin.finance.control.travel-expenses.create') }}" class="ea-btn-accent">+ Ajouter une charge</a>
            @endif
        </div>
    </div>

    @include('admin.finance.control.partials._tabs', ['current' => 'projects'])

    @include('admin.finance.control.partials._filters', [
        'action' => route('admin.finance.control.travel-projects.index'),
        'filters' => $filters,
        'voyages' => $voyages,
        'branches' => $branches,
        'advanced' => true,
        'departureStatuses' => $departureStatuses,
        'sort' => $sort,
    ])

    <section class="fc-stats">
        <div class="fc-stat">
            <div class="fc-stat-label">Projets suivis</div>
            <div class="fc-stat-value">{{ $totals['projects_count'] }}</div>
            <div class="fc-stat-note">départs réels</div>
        </div>
        <div class="fc-stat">
            <div class="fc-stat-label">Avec ventes</div>
            <div class="fc-stat-value is-green">{{ $totals['projects_count'] - ($totals['empty_count'] ?? 0) }}</div>
            <div class="fc-stat-note">CA ou charges enregistrés</div>
        </div>
        <div class="fc-stat {{ ($totals['empty_count'] ?? 0) > 0 ? 'is-warn' : '' }}">
            <div class="fc-stat-label">Sans données</div>
            <div class="fc-stat-value">{{ $totals['empty_count'] ?? 0 }}</div>
            <div class="fc-stat-note">à compléter</div>
        </div>
        <div class="fc-stat">
            <div class="fc-stat-label">Marge réelle</div>
            <div class="fc-stat-value is-primary">
                {{ number_format($totals['real_margin'], 0, ',', ' ') }}<span class="fc-stat-unit">DH</span>
            </div>
            <div class="fc-stat-note">{{ $pct($totals['margin_rate']) }} de marge</div>
        </div>
    </section>

    <section class="fc-panel">
        <div class="fc-panel-head">
            <div style="min-width:0;">
                <h2 class="ea-card-title">{{ $rows->total() }} projet{{ $rows->total() > 1 ? 's' : '' }} de voyage</h2>
                <p class="ea-card-sub">Un projet = un départ réel. Dépliez une ligne pour le détail financier.</p>
            </div>
            <form method="GET" action="{{ route('admin.finance.control.travel-projects.index') }}" class="fc-filter-actions">
                @foreach (['date_from', 'date_to', 'voyage_id', 'branch_id', 'status', 'profitability', 'search'] as $carry)
                    @if (!empty($filters[$carry]))
                        <input type="hidden" name="{{ $carry }}" value="{{ $filters[$carry] }}">
                    @endif
                @endforeach
                <label class="fc-field is-narrow" style="flex-basis:180px;">
                    <span>Trier</span>
                    <select name="sort" class="fc-input">
                        @foreach ($sortOptions as $key => $label)
                            <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="fc-btn-ghost" style="align-self:flex-end;">Appliquer</button>
            </form>
        </div>

        <div class="fc-cols">
            <span class="fc-c-name">Voyage / départ</span>
            <span class="fc-c-files">Dossiers</span>
            <span class="fc-c-sold">CA vendu</span>
            <span class="fc-c-rec">Recouvrement</span>
            <span class="fc-c-margin">Marge</span>
            <span class="fc-c-state">État</span>
        </div>

        @forelse ($rows as $row)
            @php
                $departure = $row['departure'];
                $state = $states[$row['state']] ?? $states['empty'];
                $recovery = $row['sold_amount'] > 0 ? $row['collected_amount'] / $row['sold_amount'] * 100 : 0;
                $marginClass = match ($row['state']) {
                    'profitable' => 'fc-value-green',
                    'deficit' => 'fc-value-red',
                    default => 'fc-value-muted',
                };
            @endphp
            <details class="fc-project">
                <summary>
                    <div class="fc-project-row">
                        <div class="fc-c-name">
                            <div class="fc-project-title">{{ $departure->voyage?->name ?: 'Voyage non renseigné' }}</div>
                            <span class="fc-project-ref">DEP-{{ $departure->id }} · {{ $departure->start_date?->format('d/m/Y') ?: '—' }}</span>
                        </div>
                        <span class="fc-c-files">{{ $row['reservations_count'] }}</span>
                        <span class="fc-c-sold">{{ $short($row['sold_amount']) }}</span>
                        <div class="fc-c-rec">
                            <div class="fc-track is-thin">
                                <span class="{{ $recovery > 0 ? 'is-green' : 'is-empty' }}" style="width:{{ min(100, round($recovery, 1)) }}%;"></span>
                            </div>
                            <div class="fc-rec-value">{{ number_format($recovery, 0, ',', ' ') }} %</div>
                        </div>
                        <span class="fc-c-margin {{ $marginClass }}">{{ $short($row['real_margin']) }}</span>
                        <span class="fc-c-state"><span class="fc-state {{ $state['class'] }}">{{ $state['label'] }}</span></span>
                    </div>
                </summary>

                <div class="fc-project-detail">
                    <div class="fc-tri">
                        <div class="fc-mini">
                            <div class="fc-mini-title">Cycle client</div>
                            <div class="fc-lines">
                                <div class="fc-line"><span>CA vendu</span><b>{{ $money($row['sold_amount']) }}</b></div>
                                <div class="fc-line"><span>Encaissé</span><b>{{ $money($row['collected_amount']) }}</b></div>
                                <div class="fc-line"><span>Reste clients</span><b class="{{ $row['client_remaining'] > 0 ? 'is-orange' : '' }}">{{ $money($row['client_remaining']) }}</b></div>
                            </div>
                        </div>
                        <div class="fc-mini">
                            <div class="fc-mini-title is-orange">Cycle fournisseur</div>
                            <div class="fc-lines">
                                <div class="fc-line"><span>Charges prévues</span><b>{{ $money($row['planned_charges']) }}</b></div>
                                <div class="fc-line"><span>Charges réelles</span><b>{{ $money($row['real_charges']) }}</b></div>
                                <div class="fc-line"><span>Reste fournisseurs</span><b class="{{ $row['supplier_remaining'] > 0 ? 'is-orange' : '' }}">{{ $money($row['supplier_remaining']) }}</b></div>
                            </div>
                        </div>
                        <div class="fc-mini is-result">
                            <div class="fc-mini-title is-green">Résultat</div>
                            <div class="fc-mini-figure {{ $marginClass }}">{{ $money($row['real_margin']) }}</div>
                            <div class="fc-mini-rate">Taux de marge {{ $row['sold_amount'] > 0 ? $pct($row['margin_rate']) : '—' }}</div>
                            <div class="fc-mini-actions">
                                <a href="{{ route('admin.finance.control.travel-projects.show', $departure) }}" class="fc-mini-btn">Ouvrir le projet</a>
                                @if ($canManageExpenses)
                                    <a href="{{ route('admin.finance.control.travel-expenses.create', ['departure_id' => $departure->id]) }}" class="fc-mini-btn is-outline">+ Charge</a>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($row['state'] === 'empty')
                        <div class="fc-hint is-orange" style="margin-top:12px;">
                            Ni ventes ni charges sur ce départ : l'état « à compléter » n'indique pas une perte, mais des données absentes.
                        </div>
                    @endif
                </div>
            </details>
        @empty
            <p class="ea-empty" style="padding:44px 20px;">Aucun projet de voyage pour ces critères.</p>
        @endforelse

        @if ($rows->total() > 0)
            <div class="fc-panel-foot">
                <span class="fc-count">{{ $rows->count() }} projet{{ $rows->count() > 1 ? 's' : '' }} sur {{ $rows->total() }} affiché{{ $rows->count() > 1 ? 's' : '' }}</span>
                <div class="fc-pagination">{{ $rows->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </section>

@endsection
