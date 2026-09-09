@extends('layouts.admin-v6')

@section('title', $mode === 'create' ? 'Nouvelle charge de structure' : 'Modifier la charge de structure')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <h4 class="mb-1">{{ $mode === 'create' ? 'Nouvelle charge de structure' : 'Modifier la charge de structure' }}</h4>
        <p class="text-muted mb-0 small">Ces charges sont rattachees a une agence, jamais a un depart.</p>
    </div>

    @include('admin.finance.control.partials._flash')

    <form method="POST"
          action="{{ $mode === 'create' ? route('admin.finance.control.structural-expenses.store') : route('admin.finance.control.structural-expenses.update', $expense) }}"
          enctype="multipart/form-data">
        @csrf
        @if ($mode !== 'create')
            @method('PUT')
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Agence</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Toutes / siege</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $expense->branch_id) === (int) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Categorie <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">Selectionner</option>
                            @foreach ($categories as $key => $label)
                                <option value="{{ $key }}" @selected(old('category', $expense->category) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Libelle <span class="text-danger">*</span></label>
                        <input type="text" name="label" class="form-control" required maxlength="190" value="{{ old('label', $expense->label) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Fournisseur</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">Aucun</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $expense->supplier_id) === (int) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="1" maxlength="2000">{{ old('description', $expense->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3">Montants et reglement</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Montant <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" required value="{{ old('amount', $expense->amount) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Montant paye</label>
                        <input type="number" step="0.01" min="0" name="paid_amount" class="form-control" value="{{ old('paid_amount', $expense->paid_amount ?? 0) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Devise</label>
                        <input type="text" name="currency" class="form-control" maxlength="8" value="{{ old('currency', $expense->currency ?: 'MAD') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach ($statusLabels as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $expense->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Date de charge <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" class="form-control" required value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Echeance</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $expense->due_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date de paiement</label>
                        <input type="date" name="paid_at" class="form-control" value="{{ old('paid_at', $expense->paid_at?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mode de paiement</label>
                        <input type="text" name="payment_method" class="form-control" maxlength="40" value="{{ old('payment_method', $expense->payment_method) }}" placeholder="virement, cheque...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3">Recurrence</h6>
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="hidden" name="is_recurring" value="0">
                            <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="isRecurring"
                                   @checked(old('is_recurring', $expense->is_recurring))>
                            <label class="form-check-label" for="isRecurring">Charge recurrente</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Periodicite</label>
                        <select name="recurrence" class="form-select">
                            <option value="">-</option>
                            @foreach ($recurrenceLabels as $key => $label)
                                <option value="{{ $key }}" @selected(old('recurrence', $expense->recurrence) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jusqu'au</label>
                        <input type="date" name="recurrence_until" class="form-control" value="{{ old('recurrence_until', $expense->recurrence_until?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Justificatif</label>
                        <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="col-12">
                        <p class="text-muted small mb-0">
                            Les occurrences ne sont jamais creees automatiquement : elles sont proposees dans l'ecran
                            « Charges recurrentes », ou vous les generez apres verification.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <label class="form-label">Notes internes</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes', $expense->notes) }}</textarea>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">{{ $mode === 'create' ? 'Enregistrer' : 'Mettre a jour' }}</button>
            <a href="{{ route('admin.finance.control.structural-expenses.index') }}" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
