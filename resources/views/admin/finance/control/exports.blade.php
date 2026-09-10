@extends('layouts.finance-control')

@section('title', 'Exports comptables')

@php
    $labels = [
        'encaissements' => ['Encaissements clients', 'Tous les paiements clients de la periode, avec mode, agence et presence de justificatif.'],
        'charges-voyages' => ['Charges voyages', 'Charges rattachees aux departs : prevu, reel, paye, reste, fournisseur et reference facture.'],
        'charges-structure' => ['Charges de structure', 'Charges de fonctionnement par agence et par categorie, avec echeances.'],
        'projets' => ['Projets de voyage', 'Une ligne par depart : CA vendu, encaisse, charges et marges.'],
    ];
@endphp

@section('finance_content')
<div class="container-fluid">
    <div class="mb-3">
        <h4 class="mb-1">Exports comptables</h4>
        <p class="text-muted mb-0 small">Fichiers CSV (separateur point-virgule, UTF-8) directement exploitables dans Excel et par votre comptable.</p>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="text-muted text-uppercase small mb-3">Perimetre de l'export</h6>
            <form method="GET" id="exportFilters" class="row g-2 align-items-end">
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
                <div class="col-12 col-md-2">
                    <button class="btn btn-sm btn-primary">Appliquer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 g-3">
        @foreach ($exports as $type)
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h6 class="mb-1">{{ $labels[$type][0] ?? $type }}</h6>
                        <p class="text-muted small flex-grow-1">{{ $labels[$type][1] ?? '' }}</p>
                        <a href="{{ route('admin.finance.control.exports.download', array_merge(['type' => $type], array_filter($filters, fn ($value) => $value !== null && $value !== ''))) }}"
                           class="btn btn-sm btn-outline-primary align-self-start">Telecharger le CSV</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
