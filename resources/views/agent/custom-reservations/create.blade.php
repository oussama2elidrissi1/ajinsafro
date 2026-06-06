@extends('layouts.master-ajinsafro')

@section('title', 'Nouvelle demande à la carte')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="aj-agent-dashboard">
    <div class="aj-agent-page-head">
        <div class="aj-agent-page-title">
            <h1>Nouvelle demande à la carte</h1>
            <p>Créez une demande personnalisée dans l’espace Agent.</p>
        </div>
        <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">Retour</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">Vérifiez les champs du formulaire.</div>
    @endif

    <form method="POST" action="{{ route('agent.custom-reservations.store') }}" class="aj-agent-panel">
        @csrf
        <div class="aj-agent-panel-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;">
            <div><label>Client</label><input class="aj-agent-select" name="customer_full_name" value="{{ old('customer_full_name') }}" required></div>
            <div><label>Téléphone</label><input class="aj-agent-select" name="customer_phone" value="{{ old('customer_phone') }}" required></div>
            <div><label>Email</label><input class="aj-agent-select" type="email" name="customer_email" value="{{ old('customer_email') }}"></div>
            <div><label>Destination</label><input class="aj-agent-select" name="desired_destination" value="{{ old('desired_destination') }}" required></div>
            <div><label>Ville de départ</label><input class="aj-agent-select" name="departure_city" value="{{ old('departure_city') }}" required></div>
            <div><label>Type voyage</label><select class="aj-agent-select" name="travel_type" required><option value="">Choisir</option>@foreach($travelTypeOptions as $key => $label)<option value="{{ $key }}" @selected(old('travel_type') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div><label>Date départ</label><input class="aj-agent-select" type="date" name="desired_departure_date" value="{{ old('desired_departure_date') }}" required></div>
            <div><label>Date retour</label><input class="aj-agent-select" type="date" name="desired_return_date" value="{{ old('desired_return_date') }}"></div>
            <div><label>Total voyageurs</label><input class="aj-agent-select" type="number" min="1" name="travelers_count" value="{{ old('travelers_count', 1) }}" required></div>
            <div><label>Adultes</label><input class="aj-agent-select" type="number" min="1" name="adults_count" value="{{ old('adults_count', 1) }}" required></div>
            <div><label>Enfants</label><input class="aj-agent-select" type="number" min="0" name="children_count" value="{{ old('children_count', 0) }}"></div>
            <div><label>Bébés</label><input class="aj-agent-select" type="number" min="0" name="babies_count" value="{{ old('babies_count', 0) }}"></div>
            <div style="grid-column:1/-1;"><label>Notes client</label><textarea class="aj-agent-select" name="client_notes" rows="4">{{ old('client_notes') }}</textarea></div>
            <div style="grid-column:1/-1;display:flex;gap:10px;flex-wrap:wrap;">
                <button class="aj-agent-primary-btn" type="submit">Créer la demande</button>
                <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">Annuler</a>
            </div>
        </div>
    </form>
</div>
@endsection
