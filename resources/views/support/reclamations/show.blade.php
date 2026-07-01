@extends((auth()->user() && method_exists(auth()->user(), 'isClientPortal') && auth()->user()->isClientPortal()) ? 'client.layout' : ((auth()->user() && method_exists(auth()->user(), 'isPartner') && auth()->user()->isPartner()) ? 'layouts.partner-v2' : 'layouts.master-ajinsafro'))

@section('title', 'Reclamation')
@section('page_title', 'Reclamation')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ $reclamation->subject ?: 'Reclamation' }}</h4>
            <p class="text-muted mb-0">Envoyee le {{ $reclamation->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('support.reclamations.index') }}" class="btn btn-outline-secondary">
            Retour
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <strong>Message envoye</strong>
                    <span class="badge {{ $reclamation->status === 'traitee' ? 'bg-success' : ($reclamation->status === 'en_cours' ? 'bg-warning text-dark' : 'bg-info') }}">
                        {{ $reclamation->status_label }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="mb-3" style="white-space: pre-line">{{ $reclamation->message }}</p>
                    @if($reclamation->page_url)
                        <p class="small text-muted mb-3">Page concernee : {{ $reclamation->page_url }}</p>
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
            <div class="card h-100">
                <div class="card-header">
                    <strong>Traitement dev</strong>
                </div>
                <div class="card-body">
                    @if($reclamation->dev_response)
                        <p style="white-space: pre-line">{{ $reclamation->dev_response }}</p>
                        <p class="text-muted small mb-0">
                            Traitee par {{ $reclamation->handler?->name ?? 'Dev' }}
                            @if($reclamation->handled_at)
                                le {{ $reclamation->handled_at->format('d/m/Y H:i') }}
                            @endif
                        </p>
                    @else
                        <p class="text-muted mb-0">Le dev n'a pas encore ajoute de reponse.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
