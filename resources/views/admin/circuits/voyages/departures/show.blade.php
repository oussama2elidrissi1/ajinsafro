@extends('layouts.admin-v2')

@section('title', 'Départ — '.$voyage->name)

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="page-title mb-1 font-size-18">Gestion du départ</h4>
                <p class="text-muted mb-0 small">
                    <a href="{{ route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id) }}">← Retour au voyage</a>
                    @if($departure->wp_travel_date_id)
                        <span class="ms-2">· WP travel_date_id : {{ $departure->wp_travel_date_id }}</span>
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id) }}#availability" class="btn btn-soft-secondary btn-sm">Liste des départs</a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0">{{ session('success') }}</div>
@endif

@include('admin.circuits.voyages.departures.partials._settings_card', compact('voyage', 'departure', 'statuses'))

<div id="hotels" class="mt-4">
    @include('admin.circuits.voyages.departures.partials._hotels_section', compact('voyage', 'departure', 'hotelsCatalog', 'roomStatuses'))
</div>
@endsection
