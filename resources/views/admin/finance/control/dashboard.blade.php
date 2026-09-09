@extends('layouts.admin-v6')

@section('title', 'Finance - vue d\'ensemble')

@php
    // Formatage monetaire unique pour tout le module.
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1">Finance &amp; Controle</h4>
            <p class="text-muted mb-0 small">Vue d'ensemble de la periode : ventes, encaissements, charges et resultat de gestion.</p>
        </div>
        <a href="{{ route('admin.finance.control.exports.index') }}" class="btn btn-sm btn-outline-secondary">Exports comptables</a>
    </div>

    @include('admin.finance.control.partials._flash')

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Du</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Au</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Voyage</label>
                    <select name="voyage_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($voyages as $voyage)
                            <option value="{{ $voyage->id }}" @selected($filters['voyage_id'] === $voyage->id)>{{ $voyage->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Agence</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary">Filtrer</button>
                    <a href="{{ route('admin.finance.control.dashboard') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-3">
        @include('admin.finance.control.partials._kpi', ['label' => 'CA vendu', 'value' => $money($totals['sold_amount'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Encaisse', 'value' => $money($totals['collected_amount'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Reste clients', 'value' => $money($totals['client_remaining'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Charges voyages', 'value' => $money($totals['real_charges'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Charges structure', 'value' => $money($structural['amount'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Reste fournisseurs', 'value' => $money($totals['supplier_remaining'])])
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        @include('admin.finance.control.partials._kpi', ['label' => 'Marge voyages', 'value' => $money($totals['real_margin']), 'tone' => 'auto', 'raw' => $totals['real_margin']])
        @include('admin.finance.control.partials._kpi', ['label' => 'Resultat de gestion', 'value' => $money($managementResult), 'tone' => 'auto', 'raw' => $managementResult, 'hint' => 'Marge voyages - charges structure'])
        @include('admin.finance.control.partials._kpi', ['label' => 'Voyages rentables', 'value' => $totals['profitable_count']])
        @include('admin.finance.control.partials._kpi', ['label' => 'Voyages deficitaires', 'value' => $totals['deficit_count']])
        @include('admin.finance.control.partials._kpi', ['label' => 'Justificatifs manquants', 'value' => $missingDocuments, 'tone' => $missingDocuments > 0 ? 'negative' : null])
    </div>

    <div class="alert alert-light border small text-muted">
        Le resultat de gestion est un indicateur de pilotage interne. Il ne constitue pas le resultat comptable
        fiscal definitif : amortissements, provisions et retraitements fiscaux n'y sont pas integres.
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-3">Encaissements par mois</h6>
                    @include('admin.finance.control.partials._bars', ['rows' => $revenueByMonth, 'labelKey' => 'period', 'valueKey' => 'amount'])
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-3">Charges par categorie</h6>
                    @include('admin.finance.control.partials._bars', ['rows' => $chargesByCategory, 'labelKey' => 'label', 'valueKey' => 'amount'])
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-3">Encaissements par agence</h6>
                    @include('admin.finance.control.partials._bars', ['rows' => $collectionsByBranch, 'labelKey' => 'label', 'valueKey' => 'amount'])
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-3">Marge par voyage</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Voyage</th><th>Depart</th><th class="text-end">Marge</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($topMargins as $row)
                                    <tr>
                                        <td>{{ $row['departure']->voyage?->name ?: '-' }}</td>
                                        <td class="text-nowrap">{{ $row['departure']->start_date?->format('d/m/Y') ?: '-' }}</td>
                                        <td class="text-end {{ $row['real_margin'] < 0 ? 'text-danger' : '' }}">{{ $money($row['real_margin']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Aucune donnee.</td></tr>
                                @endforelse
                                @foreach ($worstMargins->where('real_margin', '<', 0)->take(4) as $row)
                                    <tr class="table-warning">
                                        <td>{{ $row['departure']->voyage?->name ?: '-' }}</td>
                                        <td class="text-nowrap">{{ $row['departure']->start_date?->format('d/m/Y') ?: '-' }}</td>
                                        <td class="text-end text-danger">{{ $money($row['real_margin']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
