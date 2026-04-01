@extends('layouts.partner')
@section('title', 'Mes réservations')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Mes réservations</h4>
                <a href="{{ route('partner.reservations.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Nouvelle réservation</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <select name="status" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">Tous les statuts</option>
                                    <option value="EN_COURS" {{ request('status') === 'EN_COURS' ? 'selected' : '' }}>En cours</option>
                                    <option value="VALIDEE" {{ request('status') === 'VALIDEE' ? 'selected' : '' }}>Validée</option>
                                    <option value="ANNULEE" {{ request('status') === 'ANNULEE' ? 'selected' : '' }}>Annulée</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Offre</th>
                                    <th>Créée par</th>
                                    <th>Agence</th>
                                    <th>Client</th>
                                    <th>Statut</th>
                                    <th>Créée le</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reservations as $reservation)
                                    <tr>
                                        <td class="ps-3">{{ $reservation->offer?->name ?? '—' }}</td>
                                        <td>{{ $reservation->creator?->name ?? '—' }}</td>
                                        <td>{{ $reservation->agency_label ?? '—' }}</td>
                                        <td>{{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $reservation->status === \App\Models\Reservation::STATUS_VALIDEE ? 'success' : ($reservation->status === \App\Models\Reservation::STATUS_ANNULEE ? 'danger' : 'warning text-dark') }}">{{ $reservation->status }}</span>
                                        </td>
                                        <td>{{ $reservation->created_at?->format('d/m/Y H:i') }}</td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('partner.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-primary" title="Voir"><i class="bx bx-show"></i></a>
                                            <a href="{{ route('partner.reservations.edit', $reservation) }}" class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="bx bx-pencil"></i></a>
                                            <form action="{{ route('partner.reservations.destroy', $reservation) }}" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette réservation ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Aucune réservation.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($reservations, 'links'))
                        <div class="d-flex justify-content-center mt-3">{{ $reservations->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
