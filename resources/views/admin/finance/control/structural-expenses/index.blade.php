@extends('layouts.finance-control')

@section('title', 'Charges de structure')

@php
    // Formatage monetaire unique pour tout le module.
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
@endphp

@section('finance_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1">Charges de structure</h4>
            <p class="text-muted mb-0 small">Charges de fonctionnement de l'agence. Elles n'entrent pas dans la marge des projets de voyage.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.finance.control.structural-expenses.recurrences') }}" class="btn btn-sm btn-outline-secondary">
                Charges recurrentes
                @if ($pendingRecurrences > 0)
                    <span class="badge bg-warning-subtle text-warning ms-1">{{ $pendingRecurrences }}</span>
                @endif
            </a>
            <a href="{{ route('admin.finance.control.structural-expenses.create') }}" class="btn btn-sm btn-primary">Nouvelle charge</a>
        </div>
    </div>

    <div class="row row-cols-2 row-cols-md-4 g-3 mb-3">
        @include('admin.finance.control.partials._kpi', ['label' => 'Total charges', 'value' => $money($totalAmount)])
        @include('admin.finance.control.partials._kpi', ['label' => 'Total paye', 'value' => $money($totalPaid)])
        @include('admin.finance.control.partials._kpi', ['label' => 'Reste a payer', 'value' => $money($totalAmount - $totalPaid)])
        @include('admin.finance.control.partials._kpi', ['label' => 'Occurrences a generer', 'value' => $pendingRecurrences, 'tone' => $pendingRecurrences > 0 ? 'negative' : null])
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
                    <label class="form-label small text-muted">Agence</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Categorie</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['category'] ?? null) === $key)>{{ $label }}</option>
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
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Recherche</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] }}" placeholder="Libelle">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-sm btn-primary">Filtrer</button>
                    <a href="{{ route('admin.finance.control.structural-expenses.index') }}" class="btn btn-sm btn-outline-secondary">Reinitialiser</a>
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
                        <th>Agence</th>
                        <th>Categorie</th>
                        <th>Libelle</th>
                        <th>Fournisseur</th>
                        <th class="text-end">Montant</th>
                        <th class="text-end">Paye</th>
                        <th class="text-end">Reste</th>
                        <th>Echeance</th>
                        <th>Statut</th>
                        <th>Recurrente</th>
                        <th class="text-end px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr class="{{ $expense->status === \App\Models\StructuralExpense::STATUS_CANCELLED ? 'opacity-50' : '' }}">
                            <td class="px-3 text-nowrap">{{ $expense->expense_date?->format('d/m/Y') ?: '-' }}</td>
                            <td>{{ $expense->branch?->name ?: 'Toutes' }}</td>
                            <td>{{ $expense->category_label }}</td>
                            <td>{{ $expense->label }}</td>
                            <td>{{ $expense->supplier?->name ?: '-' }}</td>
                            <td class="text-end">{{ $money($expense->amount) }}</td>
                            <td class="text-end">{{ $money($expense->paid_amount) }}</td>
                            <td class="text-end fw-semibold">{{ $money($expense->remaining_amount) }}</td>
                            <td class="text-nowrap">{{ $expense->due_date?->format('d/m/Y') ?: '-' }}</td>
                            <td><span class="badge bg-light text-body">{{ $expense->status_label }}</span></td>
                            <td>
                                @if ($expense->is_recurring)
                                    <span class="badge bg-info-subtle text-info">{{ \App\Models\StructuralExpense::RECURRENCE_LABELS[$expense->recurrence] ?? 'Oui' }}</span>
                                @elseif ($expense->recurrence_parent_id)
                                    <span class="text-muted small">Occurrence {{ $expense->recurrence_period }}</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-end px-3 text-nowrap">
                                <a href="{{ route('admin.finance.control.structural-expenses.edit', $expense) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                @if ($expense->status !== \App\Models\StructuralExpense::STATUS_CANCELLED)
                                    <form method="POST" action="{{ route('admin.finance.control.structural-expenses.cancel', $expense) }}" class="d-inline"
                                          onsubmit="return confirm('Annuler cette charge ? Elle sera conservee dans l\'historique.');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">Annuler</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted py-5">Aucune charge de structure pour ces criteres.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $expenses->links() }}</div>
</div>
@endsection
