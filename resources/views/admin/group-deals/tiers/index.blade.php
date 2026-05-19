@extends('layouts.admin-v6')

@section('title', 'Group Deals â€” Tarifs par palier')

@section('content')
<div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">Tarifs par palier</h4>
            <p class="text-muted mb-0">Tous les paliers de prix des offres Group Deal.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="get" class="row g-3 mb-4">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Offre Group Deal">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-outline-primary flex-fill">Filtrer</button>
                    <a href="{{ route('admin.group-deals.tiers.index') }}" class="btn btn-light">RÃ©initialiser</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Offre</th>
                        <th>Label</th>
                        <th>Min. participants</th>
                        <th>Max. participants</th>
                        <th>Prix / personne</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($tiers as $tier)
                        <tr>
                            <td>
                                @if($tier->groupDeal)
                                    <a href="{{ route('admin.group-deals.show', $tier->groupDeal) }}" class="text-decoration-none fw-semibold">{{ $tier->groupDeal->title }}</a>
                                @elseif($tier->voyage)
                                    <span class="fw-semibold">{{ $tier->voyage->name }}</span>
                                @else
                                    <span class="text-muted">â€”</span>
                                @endif
                            </td>
                            <td>{{ $tier->label ?: 'â€”' }}</td>
                            <td>{{ $tier->min_participants ?? $tier->min_people ?? 'â€”' }}</td>
                            <td>{{ $tier->max_people ?: 'â€”' }}</td>
                            <td class="fw-semibold">{{ $tier->price_per_person ? number_format((float) $tier->price_per_person, 2, ',', ' ') . ' DH' : 'â€”' }}</td>
                            <td class="text-end">
                                @if($tier->groupDeal)
                                    <a href="{{ route('admin.group-deals.show', $tier->groupDeal) }}" class="btn btn-sm btn-primary">Ouvrir</a>
                                @elseif($tier->voyage)
                                    <a href="{{ route('admin.group-deals.trips.show', $tier->voyage) }}" class="btn btn-sm btn-primary">Ouvrir</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Aucun palier trouvÃ©.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tiers->links() }}
        </div>
    </div>
</div>
@endsection

