@extends('layouts.admin-v6')

@section('title', $mode === 'create' ? 'Nouvelle charge voyage' : 'Modifier la charge')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <h4 class="mb-1">{{ $mode === 'create' ? 'Nouvelle charge de voyage' : 'Modifier la charge' }}</h4>
        <p class="text-muted mb-0 small">Le montant prevu sert a la marge previsionnelle, le montant reel a la marge reelle, le montant paye a la tresorerie.</p>
    </div>

    @include('admin.finance.control.partials._flash')

    <form method="POST"
          action="{{ $mode === 'create' ? route('admin.finance.control.travel-expenses.store') : route('admin.finance.control.travel-expenses.update', $charge) }}"
          enctype="multipart/form-data">
        @csrf
        @if ($mode !== 'create')
            @method('PUT')
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Depart <span class="text-danger">*</span></label>
                        <select name="departure_id" class="form-select" required>
                            <option value="">Selectionner un depart</option>
                            @foreach ($departures as $departure)
                                <option value="{{ $departure->id }}" @selected((int) old('departure_id', $charge->departure_id) === (int) $departure->id)>
                                    {{ $departure->voyage?->name ?: 'Voyage' }} - {{ $departure->start_date?->format('d/m/Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Categorie</label>
                        <select name="charge_type_id" class="form-select">
                            <option value="">Non classee</option>
                            @foreach ($chargeTypes as $type)
                                <option value="{{ $type->id }}" @selected((int) old('charge_type_id', $charge->charge_type_id) === (int) $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agence</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Non rattachee</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $charge->branch_id) === (int) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Libelle <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required maxlength="190" value="{{ old('title', $charge->title) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fournisseur</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">Aucun</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $charge->supplier_id) === (int) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fournisseur (texte libre)</label>
                        <input type="text" name="supplier_name" class="form-control" maxlength="190" value="{{ old('supplier_name', $charge->supplier_name) }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" maxlength="2000">{{ old('description', $charge->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-3">Montants et reglement</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Montant prevu</label>
                        <input type="number" step="0.01" min="0" name="planned_amount" class="form-control" value="{{ old('planned_amount', $charge->planned_amount) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Montant reel <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" required value="{{ old('amount', $charge->amount) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Montant paye</label>
                        <input type="number" step="0.01" min="0" name="paid_amount" class="form-control" value="{{ old('paid_amount', $charge->paid_amount ?? 0) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Devise</label>
                        <input type="text" name="currency" class="form-control" maxlength="8" value="{{ old('currency', $charge->currency ?: 'MAD') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Statut <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach ($statusLabels as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $charge->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method }}" @selected(old('payment_method', $charge->payment_method) === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date de charge</label>
                        <input type="date" name="charge_date" class="form-control" value="{{ old('charge_date', $charge->charge_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Echeance</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $charge->due_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date de paiement</label>
                        <input type="date" name="paid_at" class="form-control" value="{{ old('paid_at', $charge->paid_at?->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Reference facture</label>
                        <input type="text" name="invoice_reference" class="form-control" maxlength="120" value="{{ old('invoice_reference', $charge->invoice_reference) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Justificatif (PDF ou image)</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        @if ($charge->attachment)
                            <div class="form-text">Un justificatif est deja rattache. Un nouvel envoi le remplacera.</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Notes internes</label>
                        <textarea name="notes" class="form-control" rows="1" maxlength="2000">{{ old('notes', $charge->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">{{ $mode === 'create' ? 'Enregistrer la charge' : 'Mettre a jour' }}</button>
            <a href="{{ route('admin.finance.control.travel-expenses.index') }}" class="btn btn-outline-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
