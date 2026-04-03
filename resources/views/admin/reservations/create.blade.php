@extends('layouts.master-ajinsafro')

@section('title', 'Nouvelle réservation')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Nouvelle réservation</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
                        <li class="breadcrumb-item active">Nouvelle réservation</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="{{ route('admin.reservations.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary"><i class="bx bx-trip me-1"></i>Informations générales</h6>
                @if(isset($travelDateIncoherent) && $travelDateIncoherent)
                    <div class="alert alert-warning py-2 mb-3 small">
                        <i class="bx bx-error-circle me-1"></i> La date de départ fournie ne correspond pas au voyage sélectionné. Elle a été ignorée.
                    </div>
                @endif
                @if(isset($selectedTravelDate) && $selectedTravelDate)
                    <div class="alert alert-info py-2 mb-3 small">
                        <i class="bx bx-calendar me-1"></i> <strong>Date de départ choisie :</strong> {{ $selectedTravelDate->date->translatedFormat('l j F Y') }}
                    </div>
                @endif
                @php
                    $selectedTourId = (int) ($preselectedTourId ?? old('tour_id'));
                    $wpTitles = $wpTitles ?? collect();
                @endphp
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Offre / voyage <span class="text-danger">*</span></label>
                        <select name="tour_id" class="form-select" required id="select-tour-id">
                            <option value="">Sélectionner un voyage…</option>
                            @foreach($voyages as $voyage)
                                @php
                                    $label = $voyage->wp_post_id && $wpTitles->has($voyage->wp_post_id)
                                        ? ($wpTitles->get($voyage->wp_post_id)->post_title ?? $voyage->name ?? $voyage->slug)
                                        : ($voyage->name ?? $voyage->slug ?? 'Voyage #' . $voyage->id);
                                @endphp
                                <option value="{{ $voyage->id }}" {{ $selectedTourId === (int) $voyage->id ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <input type="text" class="form-control" value="{{ \App\Models\Reservation::STATUS_PENDING }} (en attente)" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type de paiement</label>
                        <select name="payment_type" class="form-select">
                            <option value="">Sélectionner…</option>
                            <option value="CASHPLUS" {{ old('payment_type') === 'CASHPLUS' ? 'selected' : '' }}>CashPlus</option>
                            <option value="VIREMENT" {{ old('payment_type') === 'VIREMENT' ? 'selected' : '' }}>Virement</option>
                            <option value="ESPECE"   {{ old('payment_type') === 'ESPECE'   ? 'selected' : '' }}>Espèce</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary"><i class="bx bx-user me-1"></i>Client</h6>
                @php $clientMode = old('client_mode', 'new'); @endphp
                <div class="mb-3">
                    <label class="form-label d-block">Type de client</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="client_mode" id="client_mode_new" value="new" {{ $clientMode === 'new' ? 'checked' : '' }}>
                        <label class="form-check-label" for="client_mode_new">Nouveau client</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="client_mode" id="client_mode_existing" value="existing" {{ $clientMode === 'existing' ? 'checked' : '' }}>
                        <label class="form-check-label" for="client_mode_existing">Client existant</label>
                    </div>
                </div>

                <div id="existing-client-block" class="mb-0" style="{{ $clientMode === 'existing' ? '' : 'display:none;' }}">
                    <label class="form-label">Sélectionner un client <span class="text-danger">*</span></label>
                    <select name="client_external_id" class="form-select">
                        <option value="">— Choisir un client —</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_external_id') == $client->id ? 'selected' : '' }}>
                                [{{ $client->client_code }}] {{ $client->full_name }}
                                @if($client->phone) — {{ $client->phone }} @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-muted small mt-1">Les informations du client seront reprises depuis sa fiche.</p>
                </div>

                <div id="new-client-block" style="{{ $clientMode === 'new' ? '' : 'display:none;' }}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="client_first_name" class="form-control" value="{{ old('client_first_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="client_last_name" class="form-control" value="{{ old('client_last_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="client_phone" class="form-control" value="{{ old('client_phone') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="client_email" class="form-control" value="{{ old('client_email') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CIN / Passeport</label>
                            <input type="text" name="client_document_number" class="form-control" value="{{ old('client_document_number') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type document</label>
                            <input type="text" name="client_document_type" class="form-control" value="{{ old('client_document_type') }}" placeholder="CIN, Passeport…">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary d-flex justify-content-between align-items-center">
                    <span><i class="bx bx-group me-1"></i>Passagers accompagnants</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-companion">Ajouter compagnon</button>
                </h6>
                <p class="text-muted small mb-2">Laissez vide si le client voyage seul.</p>
                <div id="companions-container"></div>
            </div>
        </div>

        @include('admin.reservations.partials._hotel_rooms', [
            'tourHotelsWithRooms' => collect(),
            'reservation' => null,
            'hotelsRoomsUrl' => route('admin.reservations.hotels-rooms'),
            'voyageDeparturesUrl' => route('admin.reservations.voyage-departures'),
            'departureHotelsRoomsUrl' => route('admin.reservations.departure-hotels-rooms'),
            'selectedTravelDate' => $selectedTravelDate ?? null,
        ])

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary"><i class="bx bx-receipt me-1"></i>Paiement</h6>
                <label class="form-label">Reçu / justificatif (optionnel)</label>
                <input type="file" name="payment_receipt" class="form-control" accept="image/*,.pdf">
            </div>
        </div>

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary"><i class="bx bx-id-card me-1"></i>Visa</h6>
                <div class="mb-3">
                    <input type="hidden" name="visa_ok" value="0">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="visa_ok" id="visa_ok" value="1" {{ old('visa_ok', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="visa_ok">Visa OK (pas d’assistance nécessaire)</label>
                    </div>
                </div>
                <div id="assistant-visa-block" style="{{ old('visa_ok', true) ? 'display:none;' : '' }}">
                    <h6 class="text-secondary mb-2">Assistant visa</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-12">
                            <label class="form-label">Statut visa</label>
                            <select name="visa_status" class="form-select">
                                <option value="">—</option>
                                <option value="not_required" {{ old('visa_status') === 'not_required' ? 'selected' : '' }}>Non requis</option>
                                <option value="pending" {{ old('visa_status') === 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="approved" {{ old('visa_status') === 'approved' ? 'selected' : '' }}>Approuvé</option>
                                <option value="rejected" {{ old('visa_status') === 'rejected' ? 'selected' : '' }}>Refusé</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes visa</label>
                            <textarea name="visa_notes" class="form-control" rows="2" placeholder="Remarques sur le visa…">{{ old('visa_notes') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Document visa (optionnel)</label>
                            <input type="file" name="visa_document" class="form-control" accept="image/*,.pdf">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mb-3">
            <a href="{{ route('admin.reservations.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
@endsection

@push('script')
    <script>
        (function () {
            var container = document.getElementById('companions-container');
            var addBtn = document.getElementById('btn-add-companion');

            if (!container || !addBtn) return;

            function nextIndex() {
                return container.querySelectorAll('.companion-row').length;
            }

            addBtn.addEventListener('click', function () {
                var i = nextIndex();
                var row = document.createElement('div');
                row.className = 'row g-2 mb-2 companion-row';
                row.innerHTML =
                    '<div class="col-md-3">' +
                        '<input type="text" name="passengers[' + i + '][first_name]" class="form-control" placeholder="Prénom">' +
                    '</div>' +
                    '<div class="col-md-3">' +
                        '<input type="text" name="passengers[' + i + '][last_name]" class="form-control" placeholder="Nom">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                        '<select name="passengers[' + i + '][type]" class="form-select">' +
                            '<option value="">Type</option>' +
                            '<option value="adult">Adulte</option>' +
                            '<option value="child">Enfant</option>' +
                            '<option value="infant">Bébé</option>' +
                        '</select>' +
                    '</div>' +
                    '<div class="col-md-3">' +
                        '<input type="date" name="passengers[' + i + '][birth_date]" class="form-control" placeholder="Date de naissance">' +
                    '</div>' +
                    '<div class="col-md-1 d-flex align-items-center justify-content-end">' +
                        '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-companion">&times;</button>' +
                    '</div>';
                container.appendChild(row);
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('btn-remove-companion')) {
                    var row = e.target.closest('.companion-row');
                    if (row) row.remove();
                }
            });
        })();

        (function () {
            var modeNew = document.getElementById('client_mode_new');
            var modeExisting = document.getElementById('client_mode_existing');
            var blockNew = document.getElementById('new-client-block');
            var blockExisting = document.getElementById('existing-client-block');

            function refreshClientMode() {
                if (modeExisting && modeExisting.checked) {
                    if (blockExisting) blockExisting.style.display = '';
                    if (blockNew) blockNew.style.display = 'none';
                } else {
                    if (blockExisting) blockExisting.style.display = 'none';
                    if (blockNew) blockNew.style.display = '';
                }
            }

            if (modeNew && modeExisting) {
                modeNew.addEventListener('change', refreshClientMode);
                modeExisting.addEventListener('change', refreshClientMode);
                refreshClientMode();
            }
        })();

        (function () {
            var visaOk = document.getElementById('visa_ok');
            var assistantBlock = document.getElementById('assistant-visa-block');
            if (!visaOk || !assistantBlock) return;
            function refreshVisa() {
                assistantBlock.style.display = visaOk.checked ? 'none' : '';
            }
            visaOk.addEventListener('change', refreshVisa);
            refreshVisa();
        })();
    </script>
@endpush
