@extends((auth()->user() && method_exists(auth()->user(), 'isClientPortal') && auth()->user()->isClientPortal()) ? 'client.layout' : ((auth()->user() && method_exists(auth()->user(), 'isPartner') && auth()->user()->isPartner()) ? 'layouts.partner-v2' : 'layouts.master-ajinsafro'))

@section('title', 'Mes reclamations')
@section('page_title', 'Mes reclamations')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Mes reclamations au dev</h4>
            <p class="text-muted mb-0">Suivez les problemes envoyes et les reponses du dev.</p>
        </div>
        <button type="button" class="btn btn-primary" data-dev-reclamation-open>
            <i class="bx bx-message-square-error me-1"></i> Envoyer une reclamation
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sujet</th>
                            <th>Statut</th>
                            <th>Reponse dev</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reclamations as $reclamation)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $reclamation->subject ?: 'Sans sujet' }}</div>
                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($reclamation->message, 90) }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $reclamation->status === 'traitee' ? 'bg-success' : ($reclamation->status === 'en_cours' ? 'bg-warning text-dark' : 'bg-info') }}">
                                        {{ $reclamation->status_label }}
                                    </span>
                                </td>
                                <td>
                                    @if($reclamation->dev_response)
                                        <span class="text-success">Disponible</span>
                                    @else
                                        <span class="text-muted">En attente</span>
                                    @endif
                                </td>
                                <td>{{ $reclamation->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('support.reclamations.show', $reclamation) }}" class="btn btn-sm btn-outline-primary">
                                        Voir
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">Aucune reclamation envoyee.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($reclamations->hasPages())
            <div class="card-footer">{{ $reclamations->links() }}</div>
        @endif
    </div>
</div>
@endsection
