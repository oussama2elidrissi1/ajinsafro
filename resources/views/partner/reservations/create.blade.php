@extends('layouts.partner')
@section('title', 'Nouvelle réservation')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Nouvelle réservation</h4>
                <a href="{{ route('partner.reservations.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
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

    <form method="post" action="{{ route('partner.reservations.store') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Voyage</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Voyage <span class="text-danger">*</span></label>
                        <select name="tour_id" class="form-select" required>
                            <option value="">Sélectionner un voyage…</option>
                            @foreach($voyages as $voyage)
                                <option value="{{ $voyage->id }}" {{ old('tour_id') == $voyage->id ? 'selected' : '' }}>{{ $voyage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date de départ</label>
                        <select name="travel_date_id" class="form-select">
                            <option value="">—</option>
                            @foreach($travelDates as $td)
                                <option value="{{ $td->id }}" {{ old('travel_date_id') == $td->id ? 'selected' : '' }}>
                                    {{ $td->date?->format('d/m/Y') }} — Voyage #{{ $td->travel_id ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type de paiement</label>
                        <select name="payment_type" class="form-select">
                            <option value="">—</option>
                            <option value="CASHPLUS" {{ old('payment_type') === 'CASHPLUS' ? 'selected' : '' }}>CashPlus</option>
                            <option value="VIREMENT" {{ old('payment_type') === 'VIREMENT' ? 'selected' : '' }}>Virement</option>
                            <option value="ESPECE" {{ old('payment_type') === 'ESPECE' ? 'selected' : '' }}>Espèce</option>
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
                @php $clientMode = old('client_mode', 'new'); @endphp
                <div class="mb-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="client_mode" id="client_mode_new" value="new" {{ $clientMode === 'new' ? 'checked' : '' }}>
                        <label class="form-check-label" for="client_mode_new">Nouveau client</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="client_mode" id="client_mode_existing" value="existing" {{ $clientMode === 'existing' ? 'checked' : '' }}>
                        <label class="form-check-label" for="client_mode_existing">Client existant</label>
                    </div>
                </div>
                <div id="existing-client-block" class="mb-3" style="{{ $clientMode === 'existing' ? '' : 'display:none;' }}">
                    <label class="form-label">Client</label>
                    <select name="client_external_id" class="form-select">
                        <option value="">— Choisir —</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_external_id') == $client->id ? 'selected' : '' }}>
                                [{{ $client->client_code }}] {{ $client->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="new-client-block" style="{{ $clientMode === 'new' ? '' : 'display:none;' }}">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="client_first_name" class="form-control" value="{{ old('client_first_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="client_last_name" class="form-control" value="{{ old('client_last_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="client_email" class="form-control" value="{{ old('client_email') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="client_phone" class="form-control" value="{{ old('client_phone') }}">
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('partner.reservations.index') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
@endsection

@push('script')
<script>
(function () {
    var modeNew = document.getElementById('client_mode_new');
    var modeExisting = document.getElementById('client_mode_existing');
    var blockNew = document.getElementById('new-client-block');
    var blockExisting = document.getElementById('existing-client-block');
    function refresh() {
        if (modeExisting && modeExisting.checked) {
            if (blockExisting) blockExisting.style.display = '';
            if (blockNew) blockNew.style.display = 'none';
        } else {
            if (blockExisting) blockExisting.style.display = 'none';
            if (blockNew) blockNew.style.display = '';
        }
    }
    if (modeNew) modeNew.addEventListener('change', refresh);
    if (modeExisting) modeExisting.addEventListener('change', refresh);
})();
</script>
@endpush
