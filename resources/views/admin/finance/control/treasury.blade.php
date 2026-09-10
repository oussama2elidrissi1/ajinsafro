@extends('layouts.finance-control')

@section('title', 'Tresorerie')

@php
    // Formatage monetaire unique pour tout le module.
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
@endphp

@section('finance_content')
<div class="container-fluid">
    <div class="mb-3">
        <h4 class="mb-1">Tresorerie</h4>
        <p class="text-muted mb-0 small">Flux reels : encaissements clients en entree, paiements fournisseurs et charges de structure en sortie.</p>
    </div>

    <div class="row row-cols-2 row-cols-md-5 g-3 mb-3">
        @include('admin.finance.control.partials._kpi', ['label' => 'Encaissements clients', 'value' => $money($summary['inflows']), 'hint' => $summary['inflows_count'].' mouvement(s)'])
        @include('admin.finance.control.partials._kpi', ['label' => 'Paiements fournisseurs', 'value' => $money($summary['supplier_outflows'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Charges de structure', 'value' => $money($summary['structural_outflows'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Total sorties', 'value' => $money($summary['outflows'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Solde theorique', 'value' => $money($summary['balance']), 'tone' => 'auto', 'raw' => $summary['balance']])
    </div>

    <div class="alert alert-light border small text-muted">
        Le solde theorique est la difference entre les flux enregistres dans ce module sur la periode filtree.
        Ce n'est pas un solde bancaire, et un solde positif n'est pas un benefice : il contient des sommes encore dues aux fournisseurs.
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
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Granularite</label>
                    <select name="granularity" class="form-select form-select-sm">
                        <option value="jour" @selected($granularity === 'jour')>Jour</option>
                        <option value="semaine" @selected($granularity === 'semaine')>Semaine</option>
                        <option value="mois" @selected($granularity === 'mois')>Mois</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted">Agence</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted">Moyen de paiement</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($paymentMethods as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['payment_method'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-sm btn-primary">Filtrer</button>
                    <a href="{{ route('admin.finance.control.treasury.index') }}" class="btn btn-sm btn-outline-secondary">Reinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3"><h6 class="mb-0">Synthese par periode</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th class="px-3">Periode</th><th class="text-end">Entrees</th><th class="text-end">Sorties</th><th class="text-end">Solde</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($byPeriod as $period)
                                <tr>
                                    <td class="px-3">{{ $period['period'] }}</td>
                                    <td class="text-end">{{ $money($period['inflows']) }}</td>
                                    <td class="text-end">{{ $money($period['outflows']) }}</td>
                                    <td class="text-end fw-semibold {{ $period['balance'] < 0 ? 'text-danger' : '' }}">{{ $money($period['balance']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Aucun mouvement.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3"><h6 class="mb-0">Derniers mouvements</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th class="px-3">Date</th><th>Sens</th><th>Nature</th><th>Libelle</th><th>Mode</th><th>Agence</th><th class="text-end px-3">Montant</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr>
                                    <td class="px-3 text-nowrap">{{ $movement['date'] ? \Illuminate\Support\Carbon::parse($movement['date'])->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        <span class="badge {{ $movement['direction'] === 'entree' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                            {{ $movement['direction'] === 'entree' ? 'Entree' : 'Sortie' }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $movement['kind'] }}</td>
                                    <td class="small">{{ $movement['label'] }}</td>
                                    <td class="small">{{ $movement['method'] ?: '-' }}</td>
                                    <td class="small">{{ $movement['agency'] ?: '-' }}</td>
                                    <td class="text-end px-3">{{ $money($movement['amount']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Aucun mouvement sur la periode.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
