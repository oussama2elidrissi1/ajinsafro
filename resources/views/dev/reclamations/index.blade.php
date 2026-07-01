@extends('layouts.master-ajinsafro')

@section('title', 'Reclamations dev')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Reclamations au dev</h4>
            <p class="text-muted mb-0">File de problemes envoyes par les agents et utilisateurs.</p>
        </div>
        <form method="GET" action="{{ route('dev.reclamations.index') }}" class="d-flex gap-2">
            <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Recherche">
            <button class="btn btn-primary" type="submit">Filtrer</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('dev.reclamations.index') }}" class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-primary' }}">Toutes ({{ $counts['all'] }})</a>
        @foreach(\App\Models\DevReclamation::statuses() as $key => $label)
            <a href="{{ route('dev.reclamations.index', ['status' => $key]) }}" class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Utilisateur</th>
                            <th>Sujet</th>
                            <th>Statut</th>
                            <th>Image</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reclamations as $reclamation)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $reclamation->user?->name ?? 'Utilisateur' }}</div>
                                    <div class="text-muted small">{{ $reclamation->user?->email }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $reclamation->subject ?: 'Sans sujet' }}</div>
                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($reclamation->message, 80) }}</div>
                                </td>
                                <td><span class="badge bg-{{ $reclamation->status === 'traitee' ? 'success' : ($reclamation->status === 'en_cours' ? 'warning text-dark' : 'info') }}">{{ $reclamation->status_label }}</span></td>
                                <td>{{ $reclamation->attachment_path ? 'Oui' : 'Non' }}</td>
                                <td>{{ $reclamation->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('dev.reclamations.show', $reclamation) }}" class="btn btn-sm btn-primary">Traiter</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Aucune reclamation.</td></tr>
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

