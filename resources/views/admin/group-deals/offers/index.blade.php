@extends('layouts.master-ajinsafro')

@section('title', 'Group Deals')

@section('content')
<div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">Offres de voyage de groupe</h4>
            <p class="text-muted mb-0">Créez des offres autonomes avec progression, garantie et prix par paliers.</p>
        </div>
        <a href="{{ route('admin.group-deals.create') }}" class="btn btn-primary">Nouvelle offre</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="get" class="row g-3 mb-4">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Titre ou destination">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-outline-primary flex-fill">Filtrer</button>
                    <a href="{{ route('admin.group-deals.index') }}" class="btn btn-light">Réinitialiser</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Offre</th>
                        <th>Dates</th>
                        <th>Progression</th>
                        <th>Prix actuel</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($groupDeals as $deal)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $deal->title }}</div>
                                <div class="text-muted small">{{ $deal->destination ?: 'Destination non renseignée' }}</div>
                            </td>
                            <td class="small text-muted">
                                {{ optional($deal->start_date)->format('d/m/Y') ?: 'N/A' }}
                                @if($deal->end_date)
                                    → {{ $deal->end_date->format('d/m/Y') }}
                                @endif
                            </td>
                            <td>
                                <div class="small">{{ $deal->current_participants }}/{{ $deal->max_participants }} inscrits</div>
                                <div class="progress mt-2" style="height:8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $deal->progress_percent }}%"></div>
                                </div>
                                <div class="small text-muted mt-1">
                                    @if($deal->remaining_to_guarantee > 0)
                                        Il reste {{ $deal->remaining_to_guarantee }} personne(s) pour garantir
                                    @else
                                        Voyage garanti
                                    @endif
                                </div>
                            </td>
                            <td class="fw-semibold">{{ $deal->current_price ? number_format((float) $deal->current_price, 0, ',', ' ') . ' DH' : 'N/A' }}</td>
                            <td><span class="badge bg-light text-dark">{{ $deal->status_label }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.group-deals.show', $deal) }}" class="btn btn-sm btn-primary">Ouvrir</a>
                                <a href="{{ route('admin.group-deals.edit', $deal) }}" class="btn btn-sm btn-light">Éditer</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Aucune offre Group Deal trouvée.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $groupDeals->links() }}
        </div>
    </div>
</div>
@endsection
