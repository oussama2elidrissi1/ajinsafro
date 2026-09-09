@extends('layouts.admin-v6')

@section('title', 'Charges voyages')

@php
    // Formatage monetaire unique pour tout le module.
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1">Charges voyages</h4>
            <p class="text-muted mb-0 small">Charges rattachees a un depart. Les charges annulees restent visibles mais sortent de tous les totaux.</p>
        </div>
        @can('finance.expenses.manage')
            <a href="{{ route('admin.finance.control.travel-expenses.create') }}" class="btn btn-sm btn-primary">Nouvelle charge</a>
        @endcan
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
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted">Voyage</label>
                    <select name="voyage_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($voyages as $voyage)
                            <option value="{{ $voyage->id }}" @selected($filters['voyage_id'] === $voyage->id)>{{ $voyage->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Categorie</label>
                    <select name="charge_type_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach ($chargeTypes as $type)
                            <option value="{{ $type->id }}" @selected($filters['charge_type_id'] === $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Fournisseur</label>
                    <select name="supplier_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected($filters['supplier_id'] === $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Statut</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($statusLabels as $key => $label)
                            <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Agence</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Recherche</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] }}" placeholder="Libelle">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-sm btn-primary">Filtrer</button>
                    <a href="{{ route('admin.finance.control.travel-expenses.index') }}" class="btn btn-sm btn-outline-secondary">Reinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">Date</th>
                        <th>Voyage / depart</th>
                        <th>Categorie</th>
                        <th>Libelle</th>
                        <th>Fournisseur</th>
                        <th class="text-end">Prevu</th>
                        <th class="text-end">Reel</th>
                        <th class="text-end">Paye</th>
                        <th class="text-end">Reste</th>
                        <th>Echeance</th>
                        <th>Statut</th>
                        <th>Agence</th>
                        <th class="text-end px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($charges as $charge)
                        <tr class="{{ $charge->status === \App\Models\DepartureCharge::STATUS_CANCELLED ? 'opacity-50' : '' }}">
                            <td class="px-3 text-nowrap">{{ $charge->charge_date?->format('d/m/Y') ?: '-' }}</td>
                            <td>
                                {{ $charge->departure?->voyage?->name ?: '-' }}
                                <span class="text-muted d-block">{{ $charge->departure?->start_date?->format('d/m/Y') }}</span>
                            </td>
                            <td>{{ $charge->type?->name ?: '-' }}</td>
                            <td>{{ $charge->title }}</td>
                            <td>{{ $charge->supplier?->name ?: ($charge->supplier_name ?: '-') }}</td>
                            <td class="text-end">{{ $money($charge->effective_planned_amount) }}</td>
                            <td class="text-end">{{ $money($charge->amount) }}</td>
                            <td class="text-end">{{ $money($charge->paid_amount) }}</td>
                            <td class="text-end fw-semibold">{{ $money($charge->remaining_amount) }}</td>
                            <td class="text-nowrap">{{ $charge->due_date?->format('d/m/Y') ?: '-' }}</td>
                            <td><span class="badge bg-light text-body">{{ $charge->status_label }}</span></td>
                            <td>{{ $charge->branch?->name ?: '-' }}</td>
                            <td class="text-end px-3 text-nowrap">
                                <a href="{{ route('admin.finance.control.travel-projects.show', [$charge->departure_id, 'tab' => 'charges']) }}" class="btn btn-sm btn-outline-secondary">Projet</a>
                                @can('finance.expenses.manage')
                                    <a href="{{ route('admin.finance.control.travel-expenses.edit', $charge) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="text-center text-muted py-5">Aucune charge pour ces criteres.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $charges->links() }}</div>
</div>
@endsection
