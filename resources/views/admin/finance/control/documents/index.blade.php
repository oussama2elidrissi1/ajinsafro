@extends('layouts.admin-v6')

@section('title', 'Justificatifs')

@php
    // Formatage monetaire unique pour tout le module.
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
@endphp

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <h4 class="mb-1">Justificatifs</h4>
        <p class="text-muted mb-0 small">Chaque mouvement financier doit porter une piece. Les pieces deja jointes aux paiements et aux charges sont prises en compte.</p>
    </div>

    @include('admin.finance.control.partials._flash')

    <ul class="nav nav-tabs mb-0">
        <li class="nav-item">
            <a class="nav-link {{ $view === 'documents' ? 'active' : '' }}" href="{{ route('admin.finance.control.documents.index') }}">Pieces enregistrees</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $view === 'missing' ? 'active' : '' }}" href="{{ route('admin.finance.control.documents.index', ['view' => 'missing']) }}">Operations sans justificatif</a>
        </li>
    </ul>

    <div class="card border-0 shadow-sm rounded-top-0">
        <div class="card-body">

            @if ($view === 'missing')
                <form method="GET" class="row g-2 align-items-end mb-3">
                    <input type="hidden" name="view" value="missing">
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted">Montant minimum (DH)</label>
                        <input type="number" step="0.01" min="0" name="min_amount" class="form-control form-control-sm" value="{{ $minAmount }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <button class="btn btn-sm btn-primary">Filtrer</button>
                        <a href="{{ route('admin.finance.control.documents.index', ['view' => 'missing']) }}" class="btn btn-sm btn-outline-secondary">Tout voir</a>
                    </div>
                    <div class="col-12 col-md-6 text-md-end">
                        <span class="text-muted small">{{ $missing->count() }} operation(s) sans piece justificative</span>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th class="px-3">Date</th><th>Nature</th><th>Libelle</th><th>Agence</th><th class="text-end">Montant</th><th class="text-end px-3">Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($missing as $row)
                                <tr>
                                    <td class="px-3 text-nowrap">{{ $row['date'] ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}</td>
                                    <td><span class="badge bg-light text-body">{{ $row['kind'] }}</span></td>
                                    <td class="small">{{ $row['label'] }}</td>
                                    <td class="small">{{ $row['agency'] ?: '-' }}</td>
                                    <td class="text-end fw-semibold">{{ $money($row['amount']) }}</td>
                                    <td class="text-end px-3">
                                        <button class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#addDocumentModal"
                                                data-movement-type="{{ $row['movement_type'] }}"
                                                data-movement-id="{{ $row['movement_id'] }}"
                                                data-amount="{{ $row['amount'] }}">Ajouter une piece</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Toutes les operations sont justifiees.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <form method="GET" class="row g-2 align-items-end mb-3">
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-muted">Du</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-muted">Au</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small text-muted">Type</label>
                        <select name="document_type" class="form-select form-select-sm">
                            <option value="">Tous</option>
                            @foreach ($typeLabels as $key => $label)
                                <option value="{{ $key }}" @selected(($filters['document_type'] ?? null) === $key)>{{ $label }}</option>
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
                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button class="btn btn-sm btn-primary">Filtrer</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addDocumentModal">Ajouter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Date</th><th>Type</th><th>Reference</th><th class="text-end">Montant</th>
                                <th>Client / fournisseur</th><th>Voyage / depart</th><th>Agence</th><th>Mouvement</th>
                                <th>Statut</th><th class="text-end px-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                <tr>
                                    <td class="px-3 text-nowrap">{{ $document->document_date?->format('d/m/Y') ?: '-' }}</td>
                                    <td class="small">{{ $document->type_label }}</td>
                                    <td class="small">{{ $document->reference ?: '-' }}</td>
                                    <td class="text-end">{{ $document->amount !== null ? $money($document->amount) : '-' }}</td>
                                    <td class="small">{{ $document->supplier?->name ?: ($document->client_name ?: '-') }}</td>
                                    <td class="small">
                                        {{ $document->departure?->voyage?->name ?: '-' }}
                                        @if ($document->departure?->start_date)
                                            <span class="text-muted d-block">{{ $document->departure->start_date->format('d/m/Y') }}</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $document->branch?->name ?: '-' }}</td>
                                    <td class="small text-muted">{{ $document->documentable_type ? class_basename($document->documentable_type) : '-' }}</td>
                                    <td><span class="badge bg-light text-body">{{ $document->status_label }}</span></td>
                                    <td class="text-end px-3 text-nowrap">
                                        @if ($document->file_path)
                                            <a href="{{ route('admin.finance.control.documents.download', $document) }}" class="btn btn-sm btn-outline-secondary">Fichier</a>
                                        @endif
                                        <form method="POST" action="{{ route('admin.finance.control.documents.status', $document) }}" class="d-inline">
                                            @csrf
                                            <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                @foreach ($statusLabels as $key => $label)
                                                    <option value="{{ $key }}" @selected($document->status === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-muted py-4">Aucun justificatif enregistre.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $documents->links() }}</div>
            @endif

        </div>
    </div>
</div>

{{-- Ajout d'une piece, eventuellement pre-rattachee a un mouvement depuis l'onglet « sans justificatif » --}}
<div class="modal fade" id="addDocumentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.finance.control.documents.store') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title">Ajouter un justificatif</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="movement_type" id="documentMovementType">
                <input type="hidden" name="movement_id" id="documentMovementId">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="document_type" class="form-select" required>
                            @foreach ($typeLabels as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach ($statusLabels as $key => $label)
                                <option value="{{ $key }}" @selected($key === \App\Models\FinancialDocument::STATUS_TO_CHECK)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="document_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Montant</label>
                        <input type="number" step="0.01" min="0" name="amount" id="documentAmount" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Agence</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Non rattachee</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fournisseur</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">Aucun</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Client</label>
                        <input type="text" name="client_name" class="form-control" maxlength="190">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fichier (PDF ou image)</label>
                        <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="1" maxlength="2000"></textarea>
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

@push('scripts')
<script>
    // Pre-remplit le mouvement a justifier quand on ouvre la fenetre depuis la liste des operations sans piece.
    document.getElementById('addDocumentModal')?.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var type = trigger && trigger.getAttribute('data-movement-type');
        var id = trigger && trigger.getAttribute('data-movement-id');
        var amount = trigger && trigger.getAttribute('data-amount');

        document.getElementById('documentMovementType').value = type || '';
        document.getElementById('documentMovementId').value = id || '';
        if (amount) {
            document.getElementById('documentAmount').value = amount;
        }
    });
</script>
@endpush
