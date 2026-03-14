@extends('layouts.master-ajinsafro')

@section('title', 'Modifier la réservation')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier la réservation</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
                        <li class="breadcrumb-item active">Modifier</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="{{ route('admin.reservations.update', $reservation) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary"><i class="bx bx-trip me-1"></i>Informations générales</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Voyage à réserver <span class="text-danger">*</span></label>
                        <select name="tour_id" class="form-select" required>
                            <option value="">Sélectionner un voyage…</option>
                            @foreach($voyages as $voyage)
                                <option value="{{ $voyage->id }}" {{ old('tour_id', $reservation->tour_id) == $voyage->id ? 'selected' : '' }}>
                                    {{ $voyage->name ?? $voyage->slug }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <input type="text" class="form-control" value="{{ $reservation->status }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type de paiement</label>
                        <select name="payment_type" class="form-select">
                            <option value="">Sélectionner…</option>
                            <option value="CASHPLUS" {{ old('payment_type', $reservation->payment_type) === 'CASHPLUS' ? 'selected' : '' }}>CashPlus</option>
                            <option value="VIREMENT" {{ old('payment_type', $reservation->payment_type) === 'VIREMENT' ? 'selected' : '' }}>Virement</option>
                            <option value="ESPECE"   {{ old('payment_type', $reservation->payment_type) === 'ESPECE'   ? 'selected' : '' }}>Espèce</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary"><i class="bx bx-user me-1"></i>Client</h6>
                @php $clientMode = old('client_mode', $reservation->client_mode ?? 'new'); @endphp
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
                            <option value="{{ $client->id }}" {{ old('client_external_id', $reservation->client_external_id) == $client->id ? 'selected' : '' }}>
                                [{{ $client->client_code }}] {{ $client->full_name }}
                                @if($client->phone) — {{ $client->phone }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="new-client-block" style="{{ $clientMode === 'new' ? '' : 'display:none;' }}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="client_first_name" class="form-control" value="{{ old('client_first_name', $reservation->client_first_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="client_last_name" class="form-control" value="{{ old('client_last_name', $reservation->client_last_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="client_phone" class="form-control" value="{{ old('client_phone', $reservation->client_phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="client_email" class="form-control" value="{{ old('client_email', $reservation->client_email) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CIN / Passeport</label>
                            <input type="text" name="client_document_number" class="form-control" value="{{ old('client_document_number', $reservation->client_document_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type document</label>
                            <input type="text" name="client_document_type" class="form-control" value="{{ old('client_document_type', $reservation->client_document_type) }}" placeholder="CIN, Passeport…">
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
                <div id="companions-container">
                    @php
                        $passengersData = old('passengers');
                        if ($passengersData === null) {
                            $passengersData = $reservation->passengers ?? collect();
                        }
                    @endphp
                    @foreach($passengersData as $idx => $p)
                        <div class="row g-2 mb-2 companion-row">
                            <input type="hidden" name="passengers[{{ $idx }}][id]" value="{{ is_array($p) ? ($p['id'] ?? '') : ($p->id ?? '') }}">
                            <div class="col-md-3"><input type="text" name="passengers[{{ $idx }}][first_name]" class="form-control" placeholder="Prénom" value="{{ is_array($p) ? ($p['first_name'] ?? '') : ($p->first_name ?? '') }}"></div>
                            <div class="col-md-3"><input type="text" name="passengers[{{ $idx }}][last_name]" class="form-control" placeholder="Nom" value="{{ is_array($p) ? ($p['last_name'] ?? '') : ($p->last_name ?? '') }}"></div>
                            <div class="col-md-2">
                                @php $typeVal = is_array($p) ? ($p['type'] ?? '') : ($p->type ?? ''); @endphp
                                <select name="passengers[{{ $idx }}][type]" class="form-select">
                                    <option value="">Type</option>
                                    <option value="adult" {{ $typeVal === 'adult' ? 'selected' : '' }}>Adulte</option>
                                    <option value="child" {{ $typeVal === 'child' ? 'selected' : '' }}>Enfant</option>
                                    <option value="infant" {{ $typeVal === 'infant' ? 'selected' : '' }}>Bébé</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                @php
                                    $birthVal = '';
                                    if (is_array($p) && !empty($p['birth_date'])) $birthVal = $p['birth_date'];
                                    elseif (is_object($p) && isset($p->birth_date) && $p->birth_date) $birthVal = $p->birth_date instanceof \DateTimeInterface ? $p->birth_date->format('Y-m-d') : $p->birth_date;
                                @endphp
                                <input type="date" name="passengers[{{ $idx }}][birth_date]" class="form-control" value="{{ $birthVal }}">
                            </div>
                            <div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-companion">&times;</button></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @include('admin.reservations.partials._hotel_rooms', [
            'tourHotelsWithRooms' => $tourHotelsWithRooms ?? collect(),
            'reservation' => $reservation,
            'hotelsRoomsUrl' => route('admin.reservations.hotels-rooms'),
        ])

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary"><i class="bx bx-receipt me-1"></i>Paiement</h6>
                @if($reservation->payment_receipt_path)
                    @php
                        $path = str_replace('\\', '/', trim($reservation->payment_receipt_path, '/'));
                        $receiptUrl = route('admin.reservations.receipt', ['path' => $path]);
                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
                    @endphp
                    <div class="mb-3">
                        <span class="text-muted small">Reçu actuel :</span>
                        @if($isImage)
                            <a href="{{ $receiptUrl }}" target="_blank" class="d-inline-block border rounded overflow-hidden receipt-thumb">
                                <img src="{{ $receiptUrl }}" alt="Reçu" style="width:80px;height:60px;object-fit:cover;">
                            </a>
                        @else
                            <a href="{{ $receiptUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bx bx-file"></i> Voir le reçu</a>
                        @endif
                    </div>
                @endif
                <label class="form-label">Remplacer le reçu (optionnel)</label>
                <input type="file" name="payment_receipt" class="form-control" accept="image/*,.pdf">
            </div>
        </div>

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary"><i class="bx bx-id-card me-1"></i>Visa</h6>
                @php $visaOk = old('visa_ok', $reservation->visa_ok ?? true); @endphp
                <div class="mb-3">
                    <input type="hidden" name="visa_ok" value="0">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="visa_ok" id="visa_ok" value="1" {{ $visaOk ? 'checked' : '' }}>
                        <label class="form-check-label" for="visa_ok">Visa OK (pas d’assistance nécessaire)</label>
                    </div>
                </div>
                <div id="assistant-visa-block" style="{{ $visaOk ? 'display:none;' : '' }}">
                    <h6 class="text-secondary mb-2">Assistant visa</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-12">
                            <label class="form-label">Statut visa</label>
                            <select name="visa_status" class="form-select">
                                <option value="">—</option>
                                <option value="not_required" {{ old('visa_status', $reservation->visa_status) === 'not_required' ? 'selected' : '' }}>Non requis</option>
                                <option value="pending" {{ old('visa_status', $reservation->visa_status) === 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="approved" {{ old('visa_status', $reservation->visa_status) === 'approved' ? 'selected' : '' }}>Approuvé</option>
                                <option value="rejected" {{ old('visa_status', $reservation->visa_status) === 'rejected' ? 'selected' : '' }}>Refusé</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes visa</label>
                            <textarea name="visa_notes" class="form-control" rows="2" placeholder="Remarques sur le visa…">{{ old('visa_notes', $reservation->visa_notes) }}</textarea>
                        </div>
                        @if($reservation->visa_document_path)
                            @php
                                $vpath = str_replace('\\', '/', trim($reservation->visa_document_path, '/'));
                                $visaDocUrl = route('admin.reservations.receipt', ['path' => $vpath]);
                            @endphp
                            <div class="col-12">
                                <span class="text-muted small">Document actuel :</span>
                                <a href="{{ $visaDocUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-file"></i> Voir</a>
                            </div>
                        @endif
                        <div class="col-12">
                            <label class="form-label">Remplacer document visa (optionnel)</label>
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

    @push('script')
    <script>
        (function () {
            var container = document.getElementById('companions-container');
            var addBtn = document.getElementById('btn-add-companion');
            if (!container || !addBtn) return;
            function nextIndex() { return container.querySelectorAll('.companion-row').length; }
            addBtn.addEventListener('click', function () {
                var i = nextIndex();
                var row = document.createElement('div');
                row.className = 'row g-2 mb-2 companion-row';
                row.innerHTML = '<div class="col-md-3"><input type="text" name="passengers['+i+'][first_name]" class="form-control" placeholder="Prénom"></div><div class="col-md-3"><input type="text" name="passengers['+i+'][last_name]" class="form-control" placeholder="Nom"></div><div class="col-md-2"><select name="passengers['+i+'][type]" class="form-select"><option value="">Type</option><option value="adult">Adulte</option><option value="child">Enfant</option><option value="infant">Bébé</option></select></div><div class="col-md-3"><input type="date" name="passengers['+i+'][birth_date]" class="form-control"></div><div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-companion">&times;</button></div>';
                container.appendChild(row);
            });
            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('btn-remove-companion')) { var r = e.target.closest('.companion-row'); if (r) r.remove(); }
            });
        })();
        (function () {
            var modeNew = document.getElementById('client_mode_new');
            var modeExisting = document.getElementById('client_mode_existing');
            var blockNew = document.getElementById('new-client-block');
            var blockExisting = document.getElementById('existing-client-block');
            function refresh() {
                if (modeExisting && modeExisting.checked) { if (blockExisting) blockExisting.style.display = ''; if (blockNew) blockNew.style.display = 'none'; }
                else { if (blockExisting) blockExisting.style.display = 'none'; if (blockNew) blockNew.style.display = ''; }
            }
            if (modeNew) modeNew.addEventListener('change', refresh);
            if (modeExisting) modeExisting.addEventListener('change', refresh);
            refresh();
        })();
        (function () {
            var visaOk = document.getElementById('visa_ok');
            var assistantBlock = document.getElementById('assistant-visa-block');
            if (!visaOk || !assistantBlock) return;
            function refreshVisa() { assistantBlock.style.display = visaOk.checked ? 'none' : ''; }
            visaOk.addEventListener('change', refreshVisa);
            refreshVisa();
        })();
    </script>
    @endpush
@endsection
