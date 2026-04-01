@extends('layouts.partner')
@section('title', 'Modifier la réservation')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier la réservation</h4>
                <a href="{{ route('partner.reservations.show', $reservation) }}" class="btn btn-outline-secondary btn-sm">Retour</a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="post" action="{{ route('partner.reservations.update', $reservation) }}">
        @csrf
        @method('PUT')
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Offre</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Offre / voyage <span class="text-danger">*</span></label>
                        <select name="tour_id" class="form-select" required>
                            @foreach($voyages as $voyage)
                                <option value="{{ $voyage->id }}" {{ old('tour_id', $reservation->tour_id) == $voyage->id ? 'selected' : '' }}>{{ $voyage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date de départ</label>
                        <select name="travel_date_id" class="form-select">
                            <option value="">—</option>
                            @foreach($travelDates as $td)
                                <option value="{{ $td->id }}" {{ old('travel_date_id', $reservation->travel_date_id) == $td->id ? 'selected' : '' }}>{{ $td->date?->format('d/m/Y') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="EN_COURS" {{ old('status', $reservation->status) === 'EN_COURS' ? 'selected' : '' }}>En cours</option>
                            <option value="VALIDEE" {{ old('status', $reservation->status) === 'VALIDEE' ? 'selected' : '' }}>Validée</option>
                            <option value="ANNULEE" {{ old('status', $reservation->status) === 'ANNULEE' ? 'selected' : '' }}>Annulée</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type de paiement</label>
                        <select name="payment_type" class="form-select">
                            <option value="">—</option>
                            <option value="CASHPLUS" {{ old('payment_type', $reservation->payment_type) === 'CASHPLUS' ? 'selected' : '' }}>CashPlus</option>
                            <option value="VIREMENT" {{ old('payment_type', $reservation->payment_type) === 'VIREMENT' ? 'selected' : '' }}>Virement</option>
                            <option value="ESPECE" {{ old('payment_type', $reservation->payment_type) === 'ESPECE' ? 'selected' : '' }}>Espèce</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Client</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="client_first_name" class="form-control" value="{{ old('client_first_name', $reservation->client_first_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <input type="text" name="client_last_name" class="form-control" value="{{ old('client_last_name', $reservation->client_last_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="client_email" class="form-control" value="{{ old('client_email', $reservation->client_email) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="client_phone" class="form-control" value="{{ old('client_phone', $reservation->client_phone) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $reservation->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-4">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('partner.reservations.index') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
@endsection
