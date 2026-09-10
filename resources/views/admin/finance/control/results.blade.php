@extends('layouts.finance-control')

@section('title', 'Resultats & marges')

@php
    // Formatage monetaire unique pour tout le module.
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
@endphp

@section('finance_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1">Resultats &amp; marges</h4>
            <p class="text-muted mb-0 small">Resultat voyages, charges de structure et indicateur de gestion, strictement separes.</p>
        </div>
        <a href="{{ route('admin.finance.control.exports.index') }}" class="btn btn-sm btn-outline-secondary">Exports</a>
    </div>

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
                    <a href="{{ route('admin.finance.control.results.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-3">Compte de gestion de la periode</h6>
                    <table class="table table-sm mb-0">
                        <tr><td>CA vendu</td><td class="text-end">{{ $money($totals['sold_amount']) }}</td></tr>
                        <tr><td>Charges voyages</td><td class="text-end">- {{ $money($totals['real_charges']) }}</td></tr>
                        <tr class="table-light">
                            <td class="fw-semibold">Resultat voyages</td>
                            <td class="text-end fw-semibold {{ $totals['real_margin'] < 0 ? 'text-danger' : 'text-success' }}">{{ $money($totals['real_margin']) }}</td>
                        </tr>
                        <tr><td>Charges de structure</td><td class="text-end">- {{ $money($structural['amount']) }}</td></tr>
                        <tr class="table-light">
                            <td class="fw-semibold">Resultat de gestion</td>
                            <td class="text-end fw-semibold {{ $managementResult < 0 ? 'text-danger' : 'text-success' }}">{{ $money($managementResult) }}</td>
                        </tr>
                    </table>
                    <div class="alert alert-light border small text-muted mt-3 mb-0">
                        Ce resultat de gestion est un indicateur de pilotage interne. Il ne constitue pas le resultat
                        comptable fiscal definitif : amortissements, provisions et retraitements fiscaux n'y figurent pas.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small mb-3">Charges de structure par categorie</h6>
                    @include('admin.finance.control.partials._bars', ['rows' => $structuralByCategory, 'labelKey' => 'label', 'valueKey' => 'amount'])
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 pt-3"><h6 class="mb-0">Marge par projet de voyage</h6></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">Voyage</th><th>Depart</th>
                        <th class="text-end">CA vendu</th><th class="text-end">Charges reelles</th>
                        <th class="text-end">Marge reelle</th><th class="text-end">Taux</th><th>Statut</th>
                        <th class="text-end px-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-3">{{ $row['departure']->voyage?->name ?: '-' }}</td>
                            <td class="text-nowrap">{{ $row['departure']->start_date?->format('d/m/Y') ?: '-' }}</td>
                            <td class="text-end">{{ $money($row['sold_amount']) }}</td>
                            <td class="text-end">{{ $money($row['real_charges']) }}</td>
                            <td class="text-end fw-semibold {{ $row['real_margin'] < 0 ? 'text-danger' : '' }}">{{ $money($row['real_margin']) }}</td>
                            <td class="text-end">{{ number_format($row['margin_rate'], 2, ',', ' ') }} %</td>
                            <td>
                                <span class="badge {{ $row['is_profitable'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $row['is_profitable'] ? 'Rentable' : 'Deficitaire' }}
                                </span>
                            </td>
                            <td class="text-end px-3">
                                <a href="{{ route('admin.finance.control.travel-projects.show', [$row['departure'], 'tab' => 'result']) }}" class="btn btn-sm btn-outline-secondary">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">Aucun projet sur la periode.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
