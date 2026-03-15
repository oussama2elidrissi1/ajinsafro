@extends('layouts.partner')
@section('title', 'Réservation')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Réservation</h4>
                <div>
                    <a href="{{ route('partner.reservations.edit', $reservation) }}" class="btn btn-outline-primary btn-sm">Modifier</a>
                    <a href="{{ route('partner.reservations.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Informations</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6"><strong>Voyage</strong><br>{{ $reservation->tour?->name ?? '—' }}</div>
                        <div class="col-md-6"><strong>Statut</strong><br><span class="badge bg-{{ $reservation->status === \App\Models\Reservation::STATUS_VALIDEE ? 'success' : ($reservation->status === \App\Models\Reservation::STATUS_ANNULEE ? 'danger' : 'warning text-dark') }}">{{ $reservation->status }}</span></div>
                        <div class="col-md-6"><strong>Client</strong><br>{{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}</div>
                        <div class="col-md-6"><strong>Email</strong><br>{{ $reservation->client_email ?? '—' }}</div>
                        <div class="col-md-6"><strong>Téléphone</strong><br>{{ $reservation->client_phone ?? '—' }}</div>
                        <div class="col-md-6"><strong>Type de paiement</strong><br>{{ $reservation->payment_type ?? '—' }}</div>
                        <div class="col-12"><strong>Notes</strong><br>{{ $reservation->notes ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p><strong>Créée le</strong><br>{{ $reservation->created_at?->format('d/m/Y H:i') }}</p>
                    <form action="{{ route('partner.reservations.destroy', $reservation) }}" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette réservation ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
