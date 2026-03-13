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

        {{-- Informations générales --}}
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3">Informations générales</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Voyage à réserver *</label>
                        <select name="tour_id" class="form-select" required>
                            <option value="">Sélectionner un voyage…</option>
                            @foreach($voyages as $voyage)
                                <option value="{{ $voyage->id }}" {{ old('tour_id') == $voyage->id ? 'selected' : '' }}>
                                    [#{{ $voyage->id }}] {{ $voyage->name ?? $voyage->slug }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Statut</label>
                        <input type="text" class="form-control" value="EN_COURS" disabled>
                    </div>

                    <div class="col-md-3 mb-3">
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

        {{-- Identité client (nouveau client simple) --}}
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3">Identité du client</h5>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="client_first_name" class="form-control" value="{{ old('client_first_name') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="client_last_name" class="form-control" value="{{ old('client_last_name') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="client_phone" class="form-control" value="{{ old('client_phone') }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="client_email" class="form-control" value="{{ old('client_email') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">CIN / Passeport</label>
                        <input type="text" name="client_document_number" class="form-control" value="{{ old('client_document_number') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type document</label>
                        <input type="text" name="client_document_type" class="form-control" value="{{ old('client_document_type') }}" placeholder="CIN, Passeport…">
                    </div>
                </div>
            </div>
        </div>

        {{-- Passagers accompagnants --}}
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3 d-flex justify-content-between align-items-center">
                    <span>Passagers accompagnants</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-companion">
                        Ajouter compagnon
                    </button>
                </h5>

                <p class="text-muted small">
                    Laissez vide si le client voyage seul. Ajoutez un compagnon par personne supplémentaire.
                </p>

                <div id="companions-container">
                    {{-- Les lignes de compagnons seront ajoutées dynamiquement ici --}}
                </div>
            </div>
        </div>

        {{-- Paiement : reçu --}}
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3">Paiement</h5>
                <div class="mb-3">
                    <label class="form-label">Reçu / justificatif</label>
                    <input type="file" name="payment_receipt" class="form-control">
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.reservations.index') }}" class="btn btn-secondary">Annuler</a>
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
    </script>
@endpush

