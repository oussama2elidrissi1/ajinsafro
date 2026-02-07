@extends('layouts.master-ajinsafro')
@section('title')
    Hôtel du circuit — {{ $tour->post_title }}
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Hôtel du circuit</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.tour-hotels.index') }}">Hôtels</a></li>
                        <li class="breadcrumb-item active">{{ $tour->post_title }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        <strong>Circuit :</strong> {{ $tour->post_title }} (ID {{ $tour->ID }}). Check-in = Jour 1, check-out = dernier jour.
                    </p>
                    <form action="{{ route('admin.circuits.tour-hotels.update', $tour->ID) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="hotel_name" class="form-label">Nom de l'hôtel</label>
                            <input type="text" class="form-control" id="hotel_name" name="hotel_name" value="{{ old('hotel_name', $hotel?->hotel_name ?? '') }}" placeholder="Ex. Hôtel Les Almoravides">
                        </div>
                        <div class="mb-3">
                            <label for="stars" class="form-label">Étoiles (0–5)</label>
                            <input type="number" class="form-control" id="stars" name="stars" value="{{ old('stars', $hotel->stars ?? '') }}" min="0" max="5" placeholder="3">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $hotel?->address ?? '') }}" placeholder="Ville, pays">
                        </div>
                        <div class="mb-3">
                            <label for="room_type" class="form-label">Type de chambre</label>
                            <input type="text" class="form-control" id="room_type" name="room_type" value="{{ old('room_type', $hotel?->room_type ?? '') }}" placeholder="Ex. Chambre double">
                        </div>
                        <div class="mb-3">
                            <label for="meal_plan" class="form-label">Repas (formule)</label>
                            <input type="text" class="form-control" id="meal_plan" name="meal_plan" value="{{ old('meal_plan', $hotel?->meal_plan ?? '') }}" placeholder="Ex. Petit-déjeuner inclus">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $hotel?->notes ?? '') }}</textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="{{ route('admin.circuits.tour-hotels.index') }}" class="btn btn-secondary">Annuler</a>
                            <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="btn btn-soft-primary">Modifier le voyage</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
