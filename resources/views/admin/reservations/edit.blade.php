@extends(!empty($reservationEmbed) ? 'layouts.reservation-embed' : 'layouts.master-ajinsafro')

@section('title', 'Dossier de réservation')

@section('content')
    @php
        $clientMode = old('client_mode', $reservation->client_mode ?? ($reservation->client_external_id ? 'existing' : 'new'));
        $clientLabel = $reservation->client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? ''));
        $paymentStatusLabel = method_exists($reservation, 'paymentStatusLabelFr') ? $reservation->paymentStatusLabelFr() : ucfirst((string) $reservation->payment_status);
        $dossierStatusLabel = method_exists($reservation, 'dossierStatusLabelFr') ? $reservation->dossierStatusLabelFr() : ucfirst((string) $reservation->dossier_status);
        $displayTotal = (float) ($reservation->effective_total_amount ?? $reservation->total_amount ?? $reservation->base_price ?? 0);
        $displayPaid = (float) ($reservation->effective_paid_amount ?? $reservation->paid_amount ?? 0);
        $displayRemaining = (float) ($reservation->effective_remaining_amount ?? $reservation->remaining_amount ?? max(0, $displayTotal - $displayPaid));
        $existingExtrasPayload = $reservation->extras->map(fn ($extra) => [
            'voyage_extra_id' => $extra->voyage_extra_id,
            'name' => $extra->name,
            'description' => $extra->description,
            'unit_price' => $extra->unit_price ?: $extra->price,
            'quantity' => $extra->quantity ?: 1,
            'total_price' => $extra->total_price ?: $extra->price,
            'application_scope' => $extra->application_scope,
            'traveler_keys' => $extra->traveler_keys,
        ])->values()->all();
        $passengersData = old('passengers');
        if ($passengersData === null) {
            $passengersData = $reservation->passengers ?? collect();
        }
    @endphp

    @unless(!empty($reservationEmbed))
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="page-title mb-1 font-size-18">Dossier de réservation</h4>
                        <div class="text-muted small">{{ $reservation->dossier_number ?: 'Numéro généré à la confirmation' }}</div>
                    </div>
                    <div class="page-title-right d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('admin.messagerie.index') }}?reservation_id={{ $reservation->id }}" class="btn btn-outline-primary btn-sm">
                            <i class="bx bx-message-dots me-1"></i> Messagerie
                        </a>
                        <a href="{{ route('admin.reservations.dossier.pdf', $reservation) }}" target="_blank" class="btn btn-outline-dark btn-sm">
                            <i class="bx bx-printer me-1"></i> Imprimer dossier
                        </a>
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
                            <li class="breadcrumb-item active">Dossier</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mb-3">
            <h5 class="mb-0 fw-bold">Dossier · Réservation #{{ $reservation->id }}</h5>
            <p class="text-muted small mb-0">Enregistrement : retour automatique à la liste.</p>
        </div>
    @endunless

    <div class="card mb-3 border shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="text-muted text-uppercase small fw-semibold mb-2">Statuts</div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge bg-primary-subtle text-primary">{{ $dossierStatusLabel }}</span>
                        <span class="badge {{ $displayRemaining > 0 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">{{ $paymentStatusLabel }}</span>
                        <span class="badge bg-light text-dark">{{ $reservation->status_label ?? $reservation->status }}</span>
                    </div>
                    <div class="small text-muted">
                        Client principal : <strong>{{ $clientLabel ?: '—' }}</strong>
                        · Voyage : <strong>{{ $reservation->offer?->name ?? '—' }}</strong>
                        · Départ : <strong>{{ $reservation->departure?->start_date?->format('d/m/Y') ?? '—' }}</strong>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="#modify-dossier" class="btn btn-outline-primary btn-sm">
                        <i class="bx bx-edit me-1"></i> Modifier dossier
                    </a>
                    <a href="#reservation-add-payment" class="btn btn-outline-success btn-sm">
                        <i class="bx bx-credit-card me-1"></i> Ajouter paiement
                    </a>
                    <a href="#reservation-add-document" class="btn btn-outline-info btn-sm">
                        <i class="bx bx-paperclip me-1"></i> Ajouter justificatif
                    </a>
                    @if($reservation->client_email)
                        <a href="mailto:{{ $reservation->client_email }}?subject={{ rawurlencode('Dossier '.$reservation->dossier_number) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-envelope me-1"></i> Envoyer par email
                        </a>
                    @endif
                    @if($reservation->status !== \App\Models\Reservation::STATUS_CANCELLED)
                        <form method="post" action="{{ route('admin.reservations.cancel', $reservation) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bx bx-x-circle me-1"></i> Annuler réservation
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="card border h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Récapitulatif financier</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase mb-1">Total base</div>
                                <div class="fs-5 fw-semibold">{{ number_format((float) ($reservation->effective_total_base ?? $reservation->total_base ?? 0), 2, ',', ' ') }} DH</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase mb-1">Suppléments chambres</div>
                                <div class="fs-5 fw-semibold">{{ number_format((float) ($reservation->room_supplement_total ?? 0), 2, ',', ' ') }} DH</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase mb-1">Extras</div>
                                <div class="fs-5 fw-semibold">{{ number_format((float) ($reservation->effective_extras_total ?? $reservation->extras_total ?? 0), 2, ',', ' ') }} DH</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="text-muted small text-uppercase mb-1">Total dossier</div>
                                <div class="fs-4 fw-bold">{{ number_format($displayTotal, 2, ',', ' ') }} DH</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-success-subtle">
                                <div class="text-muted small text-uppercase mb-1">Total payé</div>
                                <div class="fs-4 fw-bold text-success">{{ number_format($displayPaid, 2, ',', ' ') }} DH</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 {{ $displayRemaining > 0 ? 'bg-warning-subtle' : 'bg-light' }}">
                                <div class="text-muted small text-uppercase mb-1">Reste à payer</div>
                                <div class="fs-4 fw-bold {{ $displayRemaining > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($displayRemaining, 2, ',', ' ') }} DH</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Contenu du dossier</h5>
                    <div class="mb-2"><span class="text-muted">Voyageurs :</span> <strong>{{ max(1, (int) ($reservation->passengers_count ?? $reservation->passengers->count() ?: 1)) }}</strong></div>
                    <div class="mb-2"><span class="text-muted">Chambres :</span> <strong>{{ (int) $reservation->reservationRooms->sum('room_count') }}</strong></div>
                    <div class="mb-2"><span class="text-muted">Extras :</span> <strong>{{ $reservation->extras->count() }}</strong></div>
                    <div class="mb-2"><span class="text-muted">Paiements :</span> <strong>{{ $reservation->payments->count() }}</strong></div>
                    <div class="mb-0"><span class="text-muted">Documents :</span> <strong>{{ $reservation->documents->count() }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card border h-100" id="reservation-add-payment">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Paiements</h5>
                        <span class="badge bg-light text-dark">{{ $reservation->payments->count() }} ligne(s)</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Mode</th>
                                    <th>Référence</th>
                                    <th class="text-end">Montant</th>
                                    <th>Reçu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reservation->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date?->format('d/m/Y') ?? '—' }}</td>
                                        <td>{{ $payment->payment_method ?: '—' }}</td>
                                        <td>{{ $payment->reference ?: '—' }}</td>
                                        <td class="text-end fw-semibold">{{ number_format((float) $payment->amount, 2, ',', ' ') }} DH</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <a href="{{ route('admin.reservations.payments.receipt.pdf', [$reservation, $payment]) }}" target="_blank" class="btn btn-outline-secondary btn-sm">PDF</a>
                                                @if($payment->proof_file)
                                                    <a href="{{ route('admin.reservations.receipt', ['path' => str_replace('\\', '/', trim($payment->proof_file, '/'))]) }}" target="_blank" class="btn btn-outline-primary btn-sm">Justificatif</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Aucun paiement enregistré.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <hr>

                    <form method="post" action="{{ route('admin.reservations.payments.store', $reservation) }}" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Date paiement</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mode de paiement</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">Sélectionner…</option>
                                <option value="Espèces">Espèces</option>
                                <option value="Virement bancaire">Virement bancaire</option>
                                <option value="Carte bancaire">Carte bancaire</option>
                                <option value="Chèque">Chèque</option>
                                <option value="TPE">TPE</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Montant payé</label>
                            <input type="number" name="amount" class="form-control" min="0.01" step="0.01" max="{{ $displayRemaining > 0 ? number_format($displayRemaining, 2, '.', '') : number_format($displayTotal, 2, '.', '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Référence</label>
                            <input type="text" name="reference" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Justificatif</label>
                            <input type="file" name="proof_file" class="form-control" accept="image/*,.pdf">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note interne</label>
                            <textarea name="note" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-success">Ajouter paiement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card border h-100" id="reservation-add-document">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Documents</h5>
                        <span class="badge bg-light text-dark">{{ $reservation->documents->count() }} document(s)</span>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Titre</th>
                                    <th>Fichier</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reservation->documents as $document)
                                    <tr>
                                        <td>{{ $document->type ?: 'other' }}</td>
                                        <td>{{ $document->title }}</td>
                                        <td>
                                            <a href="{{ route('admin.reservations.receipt', ['path' => str_replace('\\', '/', trim($document->file_path, '/'))]) }}" target="_blank" class="btn btn-outline-primary btn-sm">Ouvrir</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Aucun document sur ce dossier.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <form method="post" action="{{ route('admin.reservations.documents.store', $reservation) }}" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="contract">Contrat de voyage</option>
                                <option value="proforma">Facture proforma</option>
                                <option value="payment_receipt">Reçu de paiement</option>
                                <option value="payment_proof">Justificatif de paiement</option>
                                <option value="passport">Passeport / CIN</option>
                                <option value="other">Autre document</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Titre</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Fichier</label>
                            <input type="file" name="file" class="form-control" accept="image/*,.pdf" required>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Ajouter document</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 border shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Extras et voyageurs</h5>
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Extra</th>
                                    <th class="text-center">Qté</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reservation->extras as $extra)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $extra->name }}</div>
                                            @if($extra->description)
                                                <div class="text-muted small">{{ $extra->description }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $extra->quantity ?: 1 }}</td>
                                        <td class="text-end">{{ number_format((float) ($extra->total_price ?: $extra->price), 2, ',', ' ') }} DH</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Aucun extra sur ce dossier.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Voyageur</th>
                                    <th>Type</th>
                                    <th>Document</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $clientLabel ?: 'Client principal' }}</td>
                                    <td>Principal</td>
                                    <td>{{ $reservation->client_document_type ?: '—' }} {{ $reservation->client_document_number ?: '' }}</td>
                                </tr>
                                @foreach($reservation->passengers as $passenger)
                                    <tr>
                                        <td>{{ trim(($passenger->first_name ?? '').' '.($passenger->last_name ?? '')) ?: '—' }}</td>
                                        <td>{{ $passenger->type ?: '—' }}</td>
                                        <td>{{ $passenger->document_type ?: '—' }} {{ $passenger->document_number ?: '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 border shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Historique dossier</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Utilisateur</th>
                            <th>Détail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reservation->histories->sortByDesc('created_at') as $history)
                            <tr>
                                <td>{{ $history->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $history->action }}</td>
                                <td>{{ $history->user?->name ?? 'Système' }}</td>
                                <td class="small text-muted">
                                    @if($history->note)
                                        {{ $history->note }}
                                    @elseif($history->new_value)
                                        {{ \Illuminate\Support\Str::limit($history->new_value, 160) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Aucun historique disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="post" action="{{ route('admin.reservations.update', $reservation) }}" enctype="multipart/form-data" id="modify-dossier">
        @csrf
        @method('PUT')
        @if(!empty($reservationEmbed))
            <input type="hidden" name="_embed" value="1">
            @foreach($embedReturn ?? [] as $rk => $rv)
                <input type="hidden" name="_return_{{ $rk }}" value="{{ $rv }}">
            @endforeach
        @endif
        <input type="hidden" name="extras_json" value='@json($existingExtrasPayload, JSON_UNESCAPED_UNICODE)'>

        <div class="card mb-3 border">
            <div class="card-body">
                <h5 class="card-title mb-3">Modifier dossier</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Offre / voyage <span class="text-danger">*</span></label>
                        <select name="tour_id" id="select-tour-id" class="form-select" required>
                            <option value="">Sélectionner un voyage…</option>
                            @foreach($voyages as $voyage)
                                <option value="{{ $voyage->id }}" {{ old('tour_id', $reservation->tour_id) == $voyage->id ? 'selected' : '' }}>
                                    {{ $voyage->name ?? $voyage->slug }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut dossier</label>
                        <input type="text" class="form-control" value="{{ $dossierStatusLabel }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mode de paiement</label>
                        <select name="payment_type" class="form-select">
                            <option value="">Sélectionner…</option>
                            @foreach(['Espèces', 'Virement bancaire', 'Carte bancaire', 'Chèque', 'TPE', 'Autre'] as $paymentType)
                                <option value="{{ $paymentType }}" {{ old('payment_type', $reservation->payment_type) === $paymentType ? 'selected' : '' }}>{{ $paymentType }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary"><i class="bx bx-user me-1"></i>Client principal</h6>
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

                <div id="existing-client-block" style="{{ $clientMode === 'existing' ? '' : 'display:none;' }}">
                    <label class="form-label">Sélectionner un client <span class="text-danger">*</span></label>
                    <select name="client_external_id" id="client_external_id" class="form-select">
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
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="client_first_name" id="client_first_name" class="form-control" value="{{ old('client_first_name', $reservation->client_first_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="client_last_name" id="client_last_name" class="form-control" value="{{ old('client_last_name', $reservation->client_last_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                            <input type="text" name="client_phone" id="client_phone" class="form-control" value="{{ old('client_phone', $reservation->client_phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="client_email" class="form-control" value="{{ old('client_email', $reservation->client_email) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type document</label>
                            <select name="client_document_type" class="form-select">
                                <option value="">Sélectionner…</option>
                                <option value="cin" {{ old('client_document_type', $reservation->client_document_type) === 'cin' ? 'selected' : '' }}>CIN</option>
                                <option value="passport" {{ old('client_document_type', $reservation->client_document_type) === 'passport' ? 'selected' : '' }}>Passeport</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Numéro document</label>
                            <input type="text" name="client_document_number" class="form-control" value="{{ old('client_document_number', $reservation->client_document_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nationalité</label>
                            <input type="text" name="client_nationality" class="form-control" value="{{ old('client_nationality', $reservation->client?->nationality) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="client_address" class="form-control" value="{{ old('client_address', $reservation->client?->address_line_1) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary d-flex justify-content-between align-items-center">
                    <span><i class="bx bx-group me-1"></i>Accompagnants</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-companion">Ajouter accompagnant</button>
                </h6>
                <div id="companions-container">
                    @foreach($passengersData as $idx => $passenger)
                        <div class="row g-2 mb-2 companion-row">
                            <input type="hidden" name="passengers[{{ $idx }}][id]" value="{{ is_array($passenger) ? ($passenger['id'] ?? '') : ($passenger->id ?? '') }}">
                            <div class="col-md-3"><input type="text" name="passengers[{{ $idx }}][first_name]" class="form-control" placeholder="Prénom" value="{{ is_array($passenger) ? ($passenger['first_name'] ?? '') : ($passenger->first_name ?? '') }}"></div>
                            <div class="col-md-3"><input type="text" name="passengers[{{ $idx }}][last_name]" class="form-control" placeholder="Nom" value="{{ is_array($passenger) ? ($passenger['last_name'] ?? '') : ($passenger->last_name ?? '') }}"></div>
                            <div class="col-md-2">
                                @php $typeVal = is_array($passenger) ? ($passenger['type'] ?? '') : ($passenger->type ?? ''); @endphp
                                <select name="passengers[{{ $idx }}][type]" class="form-select">
                                    <option value="adult" {{ $typeVal === 'adult' ? 'selected' : '' }}>Adulte</option>
                                    <option value="child" {{ $typeVal === 'child' ? 'selected' : '' }}>Enfant</option>
                                    <option value="infant" {{ $typeVal === 'infant' ? 'selected' : '' }}>Bébé</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                @php
                                    $birthVal = '';
                                    if (is_array($passenger) && !empty($passenger['birth_date'])) $birthVal = $passenger['birth_date'];
                                    elseif (is_object($passenger) && isset($passenger->birth_date) && $passenger->birth_date) $birthVal = $passenger->birth_date instanceof \DateTimeInterface ? $passenger->birth_date->format('Y-m-d') : $passenger->birth_date;
                                @endphp
                                <input type="date" name="passengers[{{ $idx }}][birth_date]" class="form-control" value="{{ $birthVal }}">
                            </div>
                            <div class="col-md-1 d-flex align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-companion">&times;</button>
                            </div>
                            <div class="col-md-3"><input type="text" name="passengers[{{ $idx }}][document_type]" class="form-control" placeholder="Type document" value="{{ is_array($passenger) ? ($passenger['document_type'] ?? '') : ($passenger->document_type ?? '') }}"></div>
                            <div class="col-md-3"><input type="text" name="passengers[{{ $idx }}][document_number]" class="form-control" placeholder="Numéro document" value="{{ is_array($passenger) ? ($passenger['document_number'] ?? '') : ($passenger->document_number ?? '') }}"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @include('admin.reservations.partials._hotel_rooms', [
            'tourHotelsWithRooms' => $tourHotelsWithRooms ?? collect(),
            'reservation' => $reservation,
            'hotelsRoomsUrl' => route('admin.reservations.hotels-rooms'),
            'voyageDeparturesUrl' => route('admin.reservations.voyage-departures'),
            'departureHotelsRoomsUrl' => route('admin.reservations.departure-hotels-rooms'),
        ])

        <div class="card mb-3 border">
            <div class="card-body">
                <h6 class="card-title mb-3 text-secondary"><i class="bx bx-file me-1"></i>Reçu et visa</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Remplacer le reçu principal</label>
                        <input type="file" name="payment_receipt" class="form-control" accept="image/*,.pdf">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Remplacer document visa</label>
                        <input type="file" name="visa_document" class="form-control" accept="image/*,.pdf">
                    </div>
                    <div class="col-md-4">
                        <input type="hidden" name="visa_ok" value="0">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="visa_ok" id="visa_ok" value="1" {{ old('visa_ok', $reservation->visa_ok ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="visa_ok">Visa OK</label>
                        </div>
                    </div>
                    <div class="col-md-4" id="assistant-visa-block" style="{{ old('visa_ok', $reservation->visa_ok ?? true) ? 'display:none;' : '' }}">
                        <label class="form-label">Statut visa</label>
                        <select name="visa_status" class="form-select">
                            <option value="">—</option>
                            <option value="not_required" {{ old('visa_status', $reservation->visa_status) === 'not_required' ? 'selected' : '' }}>Non requis</option>
                            <option value="pending" {{ old('visa_status', $reservation->visa_status) === 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="approved" {{ old('visa_status', $reservation->visa_status) === 'approved' ? 'selected' : '' }}>Approuvé</option>
                            <option value="rejected" {{ old('visa_status', $reservation->visa_status) === 'rejected' ? 'selected' : '' }}>Refusé</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Notes visa</label>
                        <textarea name="visa_notes" class="form-control" rows="3">{{ old('visa_notes', $reservation->visa_notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mb-3">
            <a href="{{ route('admin.reservations.index') }}" class="btn btn-secondary">Retour liste</a>
            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                var container = document.getElementById('companions-container');
                var addBtn = document.getElementById('btn-add-companion');
                if (container && addBtn) {
                    function nextIndex() { return container.querySelectorAll('.companion-row').length; }
                    addBtn.addEventListener('click', function () {
                        var i = nextIndex();
                        var row = document.createElement('div');
                        row.className = 'row g-2 mb-2 companion-row';
                        row.innerHTML =
                            '<div class="col-md-3"><input type="text" name="passengers[' + i + '][first_name]" class="form-control" placeholder="Prénom"></div>' +
                            '<div class="col-md-3"><input type="text" name="passengers[' + i + '][last_name]" class="form-control" placeholder="Nom"></div>' +
                            '<div class="col-md-2"><select name="passengers[' + i + '][type]" class="form-select"><option value="adult">Adulte</option><option value="child">Enfant</option><option value="infant">Bébé</option></select></div>' +
                            '<div class="col-md-2"><input type="date" name="passengers[' + i + '][birth_date]" class="form-control"></div>' +
                            '<div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-companion">&times;</button></div>' +
                            '<div class="col-md-3"><input type="text" name="passengers[' + i + '][document_type]" class="form-control" placeholder="Type document"></div>' +
                            '<div class="col-md-3"><input type="text" name="passengers[' + i + '][document_number]" class="form-control" placeholder="Numéro document"></div>';
                        container.appendChild(row);
                    });
                    container.addEventListener('click', function (event) {
                        if (event.target.classList.contains('btn-remove-companion')) {
                            var row = event.target.closest('.companion-row');
                            if (row) row.remove();
                        }
                    });
                }
            })();

            (function () {
                var modeNew = document.getElementById('client_mode_new');
                var modeExisting = document.getElementById('client_mode_existing');
                var blockNew = document.getElementById('new-client-block');
                var blockExisting = document.getElementById('existing-client-block');
                function refresh() {
                    if (!modeNew || !modeExisting || !blockNew || !blockExisting) return;
                    blockExisting.style.display = modeExisting.checked ? '' : 'none';
                    blockNew.style.display = modeNew.checked ? '' : 'none';
                }
                if (modeNew) modeNew.addEventListener('change', refresh);
                if (modeExisting) modeExisting.addEventListener('change', refresh);
                refresh();
            })();

            (function () {
                var visaOk = document.getElementById('visa_ok');
                var assistantBlock = document.getElementById('assistant-visa-block');
                if (!visaOk || !assistantBlock) return;
                function refresh() { assistantBlock.style.display = visaOk.checked ? 'none' : ''; }
                visaOk.addEventListener('change', refresh);
                refresh();
            })();
        </script>
    @endpush
@endsection
