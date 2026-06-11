@extends('layouts.admin-v6')

@section('title', $mode === 'edit' ? 'Modifier charge' : 'Ajouter charge')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">{{ $mode === 'edit' ? 'Modifier charge' : 'Ajouter charge' }}</h4>
                <p class="text-muted mb-0">{{ $departure->voyage?->name ?: 'Voyage non renseigne' }} - DEP-{{ $departure->id }} - {{ $departure->start_date?->format('d/m/Y') ?: '-' }}</p>
            </div>
            <a href="{{ route('admin.finance.departures.show', $departure) }}" class="btn btn-outline-secondary">Annuler</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" action="{{ $mode === 'edit' ? route('admin.finance.departures.charges.update', [$departure, $charge]) : route('admin.finance.departures.charges.store', $departure) }}" class="row g-3">
                    @csrf
                    @if($mode === 'edit') @method('PUT') @endif

                    <div class="col-md-4">
                        <label class="form-label">Type de charge</label>
                        <select name="charge_type_id" class="form-select">
                            <option value="">Autre</option>
                            @foreach($chargeTypes as $type)
                                <option value="{{ $type->id }}" @selected((int) old('charge_type_id', $charge->charge_type_id) === (int) $type->id)>{{ $type->name }}{{ $type->is_active ? '' : ' (inactif)' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $charge->title) }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4"><label class="form-label">Fournisseur</label><input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name', $charge->supplier_name) }}"></div>
                    <div class="col-md-4">
                        <label class="form-label">Montant <span class="text-danger">*</span></label>
                        <input type="number" min="0" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $charge->amount) }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4"><label class="form-label">Devise</label><input type="text" name="currency" class="form-control" value="{{ old('currency', $charge->currency ?: 'MAD') }}"></div>
                    <div class="col-md-4">
                        <label class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            @foreach($paymentMethodLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_method', $charge->payment_method) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut paiement</label>
                        <select name="payment_status" class="form-select">
                            @foreach($paymentStatusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_status', $charge->payment_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Date paiement</label><input type="date" name="paid_at" class="form-control" value="{{ old('paid_at', $charge->paid_at?->format('Y-m-d')) }}"></div>
                    <div class="col-md-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4">{{ old('description', $charge->description) }}</textarea></div>
                    <div class="col-md-12">
                        <label class="form-label">Justificatif</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        @if($charge->attachment)
                            <div class="form-text">Un justificatif existe deja. Un nouvel upload le remplacera.</div>
                        @endif
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.finance.departures.show', $departure) }}" class="btn btn-outline-secondary">Annuler</a>
                        <button class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
