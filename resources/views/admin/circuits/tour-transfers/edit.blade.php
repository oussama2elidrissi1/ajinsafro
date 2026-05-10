@extends('layouts.admin-v2')
@section('title')
    Transferts du circuit — {{ $tour->post_title }}
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Transferts du circuit</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.tour-transfers.index') }}">Transferts</a></li>
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

    <form action="{{ route('admin.circuits.tour-transfers.update', $tour->ID) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Transfert aller — Jour 1 (Aéroport → Hôtel)</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="arrival_from_label" class="form-label">De (lieu)</label>
                            <input type="text" class="form-control" id="arrival_from_label" name="arrival[from_label]" value="{{ old('arrival.from_label', $arrival->from_label ?? '') }}" placeholder="Ex. Aéroport Marrakech">
                        </div>
                        <div class="mb-3">
                            <label for="arrival_to_label" class="form-label">À (lieu)</label>
                            <input type="text" class="form-control" id="arrival_to_label" name="arrival[to_label]" value="{{ old('arrival.to_label', $arrival?->to_label ?? '') }}" placeholder="Ex. Hôtel">
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="arrival_pickup_time" class="form-label">Heure prise en charge</label>
                                    <input type="text" class="form-control" id="arrival_pickup_time" name="arrival[pickup_time]" value="{{ old('arrival.pickup_time', $arrival?->pickup_time ?? '') }}" placeholder="14:00">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="arrival_dropoff_time" class="form-label">Heure arrivée</label>
                                    <input type="text" class="form-control" id="arrival_dropoff_time" name="arrival[dropoff_time]" value="{{ old('arrival.dropoff_time', $arrival?->dropoff_time ?? '') }}" placeholder="15:00">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="arrival_vehicle_type" class="form-label">Véhicule</label>
                            <input type="text" class="form-control" id="arrival_vehicle_type" name="arrival[vehicle_type]" value="{{ old('arrival.vehicle_type', $arrival?->vehicle_type ?? '') }}" placeholder="Ex. Minivan">
                        </div>
                        <div class="mb-3">
                            <label for="arrival_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="arrival_notes" name="arrival[notes]" rows="2">{{ old('arrival.notes', $arrival?->notes ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Transfert retour — Dernier jour (Hôtel → Aéroport)</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="departure_from_label" class="form-label">De (lieu)</label>
                            <input type="text" class="form-control" id="departure_from_label" name="departure[from_label]" value="{{ old('departure.from_label', $departure?->from_label ?? '') }}" placeholder="Ex. Hôtel">
                        </div>
                        <div class="mb-3">
                            <label for="departure_to_label" class="form-label">À (lieu)</label>
                            <input type="text" class="form-control" id="departure_to_label" name="departure[to_label]" value="{{ old('departure.to_label', $departure?->to_label ?? '') }}" placeholder="Ex. Aéroport Marrakech">
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="departure_pickup_time" class="form-label">Heure prise en charge</label>
                                    <input type="text" class="form-control" id="departure_pickup_time" name="departure[pickup_time]" value="{{ old('departure.pickup_time', $departure->pickup_time ?? '') }}" placeholder="10:00">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="departure_dropoff_time" class="form-label">Heure arrivée</label>
                                    <input type="text" class="form-control" id="departure_dropoff_time" name="departure[dropoff_time]" value="{{ old('departure.dropoff_time', $departure?->dropoff_time ?? '') }}" placeholder="11:00">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="departure_vehicle_type" class="form-label">Véhicule</label>
                            <input type="text" class="form-control" id="departure_vehicle_type" name="departure[vehicle_type]" value="{{ old('departure.vehicle_type', $departure->vehicle_type ?? '') }}" placeholder="Ex. Minivan">
                        </div>
                        <div class="mb-3">
                            <label for="departure_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="departure_notes" name="departure[notes]" rows="2">{{ old('departure.notes', $departure?->notes ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('admin.circuits.tour-transfers.index') }}" class="btn btn-secondary">Annuler</a>
                    <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="btn btn-soft-primary">Modifier le voyage</a>
                </div>
            </div>
        </div>
    </form>
@endsection
