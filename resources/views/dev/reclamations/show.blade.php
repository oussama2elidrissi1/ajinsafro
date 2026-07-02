@extends('layouts.admin-v6')

@section('title', 'Traitement reclamation')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ $reclamation->subject ?: 'Reclamation' }}</h4>
            <p class="text-muted mb-0">Envoyee par {{ $reclamation->user?->name ?? 'Utilisateur' }} le {{ $reclamation->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('admin.dev.reclamations.index') }}" class="btn btn-outline-secondary">Retour</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><strong>Probleme signale</strong></div>
                <div class="card-body">
                    <p style="white-space: pre-line">{{ $reclamation->message }}</p>
                    @if($reclamation->page_url)
                        <p class="small text-muted">Page : {{ $reclamation->page_url }}</p>
                    @endif
                    @if($reclamation->attachment_url)
                        <a href="{{ $reclamation->attachment_url }}" target="_blank" rel="noopener">
                            <img src="{{ $reclamation->attachment_url }}" alt="Capture jointe" class="img-fluid rounded border">
                        </a>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><strong>Reponse et statut</strong></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.dev.reclamations.update', $reclamation) }}" class="d-grid gap-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="form-label" for="status">Statut</label>
                            <select id="status" name="status" class="form-select">
                                @foreach(\App\Models\DevReclamation::statuses() as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', $reclamation->status) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="dev_response">Message affiche a l'utilisateur</label>
                            <textarea id="dev_response" name="dev_response" rows="8" class="form-control" placeholder="Expliquez la correction ou la suite a faire...">{{ old('dev_response', $reclamation->dev_response) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-success">
                            Enregistrer le traitement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
