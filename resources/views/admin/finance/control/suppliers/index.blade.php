@extends('layouts.admin-v6')

@section('title', 'Fournisseurs')

@php
    // Formatage monetaire unique pour tout le module.
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1">Fournisseurs</h4>
            <p class="text-muted mb-0 small">Referentiel utilise pour rattacher les charges de voyage, les charges de structure et les justificatifs.</p>
        </div>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">Nouveau fournisseur</button>
    </div>

    @include('admin.finance.control.partials._flash')

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted">Recherche</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] }}" placeholder="Nom du fournisseur">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button class="btn btn-sm btn-primary">Filtrer</button>
                    <a href="{{ route('admin.finance.control.suppliers.index') }}" class="btn btn-sm btn-outline-secondary">Reinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">Fournisseur</th>
                        <th>Type</th>
                        <th>Contact</th>
                        <th class="text-end">Charges voyage</th>
                        <th class="text-end">Total facture</th>
                        <th class="text-end">Total paye</th>
                        <th class="text-end">Reste du</th>
                        <th class="text-end">Charges structure</th>
                        <th>Etat</th>
                        <th class="text-end px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        @php
                            $billed = (float) ($supplier->billed_amount ?? 0);
                            $paid = (float) ($supplier->paid_amount_total ?? 0);
                        @endphp
                        <tr class="{{ $supplier->is_active ? '' : 'opacity-50' }}">
                            <td class="px-3">{{ $supplier->name }}</td>
                            <td>{{ $supplier->type_label }}</td>
                            <td class="small text-muted">
                                {{ $supplier->contact_name ?: '-' }}
                                @if ($supplier->phone)<span class="d-block">{{ $supplier->phone }}</span>@endif
                            </td>
                            <td class="text-end">{{ $supplier->charges_count ?? 0 }}</td>
                            <td class="text-end">{{ $money($billed) }}</td>
                            <td class="text-end">{{ $money($paid) }}</td>
                            <td class="text-end fw-semibold">{{ $money($billed - $paid) }}</td>
                            <td class="text-end">{{ $money($supplier->structural_amount ?? 0) }}</td>
                            <td>
                                <span class="badge {{ $supplier->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ $supplier->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="text-end px-3">
                                <form method="POST" action="{{ route('admin.finance.control.suppliers.destroy', $supplier) }}" class="d-inline"
                                      onsubmit="return confirm('Retirer ce fournisseur ? Il sera desactive s\'il porte des mouvements.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Retirer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-5">Aucun fournisseur enregistre.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $suppliers->links() }}</div>
</div>

<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.finance.control.suppliers.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title">Nouveau fournisseur</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="190">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach ($typeLabels as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact</label>
                        <input type="text" name="contact_name" class="form-control" maxlength="190">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" maxlength="190">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Telephone</label>
                        <input type="text" name="phone" class="form-control" maxlength="40">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ville</label>
                        <input type="text" name="city" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pays</label>
                        <input type="text" name="country" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Identifiant fiscal / ICE</label>
                        <input type="text" name="tax_id" class="form-control" maxlength="60">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="address" class="form-control" maxlength="255">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="2000"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
@endsection
