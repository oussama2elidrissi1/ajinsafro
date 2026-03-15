@extends('layouts.master-ajinsafro')
@section('title', 'Détail partenaire')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Compte partenaire – {{ $partner->display_name }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner-accounts.index') }}">Revendeurs</a></li>
                        <li class="breadcrumb-item active">Détail</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Informations société</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6"><strong>Raison sociale</strong><br>{{ $partner->raison_sociale }}</div>
                        <div class="col-md-6"><strong>Nom commercial</strong><br>{{ $partner->nom_commercial ?? '—' }}</div>
                        <div class="col-md-6"><strong>Responsable</strong><br>{{ $partner->nom_responsable }}</div>
                        <div class="col-md-6"><strong>Email</strong><br>{{ $partner->email }}</div>
                        <div class="col-md-6"><strong>Téléphone</strong><br>{{ $partner->telephone ?? '—' }}</div>
                        <div class="col-12"><strong>Adresse</strong><br>{{ $partner->adresse ?? '—' }}, {{ $partner->code_postal ?? '' }} {{ $partner->ville ?? '' }}, {{ $partner->pays ?? '—' }}</div>
                        <div class="col-md-4"><strong>ICE</strong><br>{{ $partner->ice ?? '—' }}</div>
                        <div class="col-md-4"><strong>IF</strong><br>{{ $partner->if ?? '—' }}</div>
                        <div class="col-md-4"><strong>RC</strong><br>{{ $partner->rc ?? '—' }}</div>
                        @if($partner->partner_type ?? null)
                            <div class="col-md-6"><strong>Type partenaire</strong><br>{{ $partner->partner_type_label ?? $partner->partner_type }}</div>
                        @endif
                        @if($partner->rib_iban ?? null)
                            <div class="col-md-6"><strong>RIB / IBAN</strong><br>{{ $partner->rib_iban }}</div>
                        @endif
                        @if($partner->rib_bic ?? null)
                            <div class="col-md-6"><strong>BIC</strong><br>{{ $partner->rib_bic }}</div>
                        @endif
                        @if($partner->payment_mode ?? null)
                            <div class="col-md-6"><strong>Mode de paiement</strong><br>{{ $partner->payment_mode }}</div>
                        @endif
                        @if($partner->contract_path ?? null)
                            <div class="col-12">
                                <strong>Contrat</strong><br>
                                <a href="{{ asset('storage/' . $partner->contract_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bx bx-file"></i> Voir le contrat</a>
                            </div>
                        @endif
                        @if($partner->document_path)
                            <div class="col-12">
                                <strong>Pièce justificative</strong><br>
                                <a href="{{ asset('storage/' . $partner->document_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bx bx-file"></i> Voir le document</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Statut & validation</h5>
                </div>
                <div class="card-body">
                    <p><strong>Statut</strong><br>
                        @php
                            $badge = match($partner->status) {
                                'pending' => 'badge bg-warning text-dark',
                                'validated' => 'badge bg-success',
                                'rejected' => 'badge bg-danger',
                                'suspended' => 'badge bg-secondary',
                                default => 'badge bg-light text-dark',
                            };
                        @endphp
                        <span class="{{ $badge }}">{{ $partner->status }}</span>
                    </p>
                    <p><strong>Inscrit le</strong><br>{{ $partner->created_at?->format('d/m/Y H:i') }}</p>
                    @if($partner->validated_at)
                        <p><strong>Validé le</strong><br>{{ $partner->validated_at->format('d/m/Y H:i') }}</p>
                        @if($partner->validatedByUser)
                            <p><strong>Validé par</strong><br>{{ $partner->validatedByUser->name }}</p>
                        @endif
                    @endif
                    @if($partner->rejected_at)
                        <p><strong>Refusé le</strong><br>{{ $partner->rejected_at->format('d/m/Y H:i') }}</p>
                        @if($partner->rejected_reason)
                            <p><strong>Motif</strong><br>{{ $partner->rejected_reason }}</p>
                        @endif
                    @endif
                </div>
            </div>
            @if($partner->isPending())
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="{{ route('admin.partner-accounts.validate', $partner) }}" method="post" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100"><i class="bx bx-check me-1"></i> Valider le partenaire</button>
                        </form>
                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#reject-modal-{{ $partner->id }}">
                            <i class="bx bx-x me-1"></i> Refuser
                        </button>
                        @include('admin.partner-accounts._reject_modal', ['partner' => $partner])
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if(isset($voyages) && $partner->isValidated())
    <div class="row mt-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Accès voyages</h5>
                    <span class="small text-muted">Vide = tous les voyages</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.partner-accounts.voyage-access', $partner) }}" method="post">
                        @csrf
                        <div class="row g-2" style="max-height: 300px; overflow-y: auto;">
                            @foreach($voyages as $v)
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="voyage_ids[]" value="{{ $v->id }}" id="voyage-{{ $v->id }}"
                                            {{ $partner->voyageAccess->contains('id', $v->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="voyage-{{ $v->id }}">{{ $v->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="small text-muted mt-2">Ne cochez rien pour laisser l’accès à tous les voyages. Cochez des voyages pour restreindre l’accès.</p>
                        <button type="submit" class="btn btn-primary btn-sm">Enregistrer l’accès</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row mt-3">
        <div class="col-12">
            <a href="{{ route('admin.partner-accounts.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i> Retour à la liste</a>
        </div>
    </div>
@endsection
