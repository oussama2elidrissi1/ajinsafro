@extends('layouts.admin-v6')
@section('title', 'Reservations partenaire')

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex align-items-center justify-content-between">
        <h4 class="page-title mb-0 font-size-18">Reservations - {{ $partner->display_name }}</h4>
        <a href="{{ route('admin.partners.show', $partner) }}" class="btn btn-outline-secondary btn-sm">Retour</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dossier</th>
                        <th>Voyage</th>
                        <th>Client</th>
                        <th>Agent</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reservations as $reservation)
                        <tr>
                            <td>{{ $reservation->dossier_number ?? ('#' . $reservation->id) }}</td>
                            <td>{{ $reservation->offer?->name ?? '-' }}</td>
                            <td>{{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '-' }}</td>
                            <td>{{ $reservation->agent?->name ?? $reservation->createdBy?->name ?? $reservation->creator?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $reservation->effective_total_amount, 2, ',', ' ') }} DH</td>
                            <td><span class="badge bg-light text-dark">{{ $reservation->statusLabelFr() }}</span></td>
                            <td>{{ $reservation->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucune reservation partenaire.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $reservations->links() }}</div>
    </div>
</div>
@endsection
