@extends('layouts.master-ajinsafro')
@section('title', 'Gérer l\'hôtel — ' . $tour->post_title)

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Gérer l'hôtel du circuit</h4>
                <div class="d-flex align-items-center gap-2">
                    @if($hotel)
                        <a href="{{ route('admin.circuits.tour-hotels.show', $tour->ID) }}" class="btn btn-outline-secondary btn-sm">Voir</a>
                    @endif
                    <a href="{{ route('admin.circuits.tour-hotels.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
                </div>
            </div>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.circuits.tour-hotels.index') }}">Hôtels</a></li>
                <li class="breadcrumb-item active">{{ \Str::limit($tour->post_title, 35) }}</li>
            </ol>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Circuit : {{ \Str::limit($tour->post_title, 50) }}</h5>
                    <span class="text-muted small">ID {{ $tour->ID }} — Check-in jour 1, check-out dernier jour (ou défini dans le programme du voyage).</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.circuits.tour-hotels.update', $tour->ID) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="hotel_name" class="form-label">Nom de l'hôtel <span class="text-muted">*</span></label>
                                <input type="text" class="form-control" id="hotel_name" name="hotel_name" value="{{ old('hotel_name', $hotel?->hotel_name ?? '') }}" placeholder="Ex. Hôtel Les Almoravides">
                            </div>
                            <div class="col-md-4">
                                <label for="stars" class="form-label">Étoiles (0–5)</label>
                                <input type="number" class="form-control" id="stars" name="stars" value="{{ old('stars', $hotel?->stars ?? '') }}" min="0" max="5" placeholder="3">
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $hotel?->address ?? '') }}" placeholder="Ville, pays">
                            </div>
                            <div class="col-md-6">
                                <label for="room_type" class="form-label">Type de chambre</label>
                                <input type="text" class="form-control" id="room_type" name="room_type" value="{{ old('room_type', $hotel?->room_type ?? '') }}" placeholder="Ex. Chambre double">
                            </div>
                            <div class="col-md-6">
                                <label for="meal_plan" class="form-label">Formule repas</label>
                                <input type="text" class="form-control" id="meal_plan" name="meal_plan" value="{{ old('meal_plan', $hotel?->meal_plan ?? '') }}" placeholder="Ex. Petit-déjeuner inclus">
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Informations complémentaires…">{{ old('notes', $hotel?->notes ?? '') }}</textarea>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="{{ route('admin.circuits.tour-hotels.index') }}" class="btn btn-secondary">Annuler</a>
                            <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="btn btn-soft-primary">Modifier le voyage (programme, chambres)</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Voyage lié</h5>
                </div>
                <div class="card-body small">
                    <p class="mb-1"><strong>{{ \Str::limit($tour->post_title, 40) }}</strong></p>
                    <p class="text-muted mb-2">ID {{ $tour->ID }} — L’hôtel enregistré ici sera rattaché à ce voyage.</p>
                    <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="btn btn-outline-primary btn-sm me-1">Modifier le voyage</a>
                    <a href="{{ route('admin.circuits.voyages.show', $tour->ID) }}" class="btn btn-outline-secondary btn-sm">Voir la fiche</a>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Aide</h5>
                </div>
                <div class="card-body small text-muted">
                    <p class="mb-2">Renseignez ici l’hôtel principal du circuit (nom, étoiles, adresse, type de chambre, formule repas).</p>
                    <p class="mb-0">Pour définir plusieurs nuits ou plusieurs hôtels par circuit, ainsi que les types de chambres (suppléments, capacités), utilisez l’édition du voyage et l’onglet programme / hôtels.</p>
                </div>
            </div>
            @if($hotel && $hotel->rooms->isNotEmpty())
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Types de chambres ({{ $hotel->rooms->count() }})</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($hotel->rooms as $room)
                                <li class="list-group-item d-flex justify-content-between align-items-center small">
                                    {{ $room->room_type }} @if($room->room_label)({{ $room->room_label }})@endif
                                    @if($room->supplement)
                                        <span class="badge bg-light text-dark">{{ number_format((float) $room->supplement, 0, ',', ' ') }} DH</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
