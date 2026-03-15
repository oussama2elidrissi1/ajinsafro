@extends('layouts.partner')
@section('title', 'Documents')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title mb-0 font-size-18">Documents</h4>
            </div>
        </div>
    </div>
    <p class="text-muted">Contrat partenaire, grille de commission, conditions de vente et supports marketing.</p>

    <div class="row">
        @forelse($documents as $doc)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar-md rounded bg-light me-3">
                            <i class="bx bx-file font-size-24 text-secondary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $typeLabels[$doc->type] ?? $doc->type }}</h6>
                            <p class="text-muted small mb-0">{{ $doc->name ?: 'Document' }}</p>
                        </div>
                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Télécharger</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Aucun document disponible pour le moment. Contactez le siège pour obtenir votre contrat et les grilles de commission.</div>
            </div>
        @endforelse
    </div>
@endsection
