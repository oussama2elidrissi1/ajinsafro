@extends('layouts.admin-v2')

@section('title', 'Group Deals — Participants')

@section('content')
<div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">Participants / inscriptions</h4>
            <p class="text-muted mb-0">Tous les inscrits aux offres Group Deal.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="get" class="row g-3 mb-4">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, email ou téléphone">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-outline-primary flex-fill">Filtrer</button>
                    <a href="{{ route('admin.group-deals.participants.index') }}" class="btn btn-light">Réinitialiser</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Participant</th>
                        <th>Offre</th>
                        <th>Personnes</th>
                        <th>Statut</th>
                        <th>Paiement</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($participants as $participant)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $participant->full_name }}</div>
                                <div class="text-muted small">{{ $participant->email ?: '—' }} · {{ $participant->phone ?: '—' }}</div>
                            </td>
                            <td>
                                @if($participant->groupDeal)
                                    <a href="{{ route('admin.group-deals.show', $participant->groupDeal) }}" class="text-decoration-none">{{ $participant->groupDeal->title }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $participant->participants_count }}</td>
                            <td>
                                <span class="badge bg-{{ $participant->status === 'confirmed' ? 'success' : ($participant->status === 'paid' ? 'primary' : ($participant->status === 'cancelled' ? 'danger' : 'warning text-dark')) }}">
                                    {{ ucfirst($participant->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{ ucfirst($participant->payment_status) }}</span>
                            </td>
                            <td class="small text-muted">{{ optional($participant->joined_at)->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="text-end">
                                @if($participant->groupDeal)
                                    <a href="{{ route('admin.group-deals.show', $participant->groupDeal) }}" class="btn btn-sm btn-primary">Ouvrir</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aucun participant trouvé.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{ $participants->links() }}
        </div>
    </div>
</div>
@endsection
