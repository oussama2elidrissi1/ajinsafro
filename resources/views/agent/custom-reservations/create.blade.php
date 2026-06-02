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

    <form method="POST" action="{{ route('agent.custom-reservations.store') }}" class="aj-agent-panel">
        @csrf
        <div class="aj-agent-panel-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;">
            <div><label>Client</label><input class="aj-agent-select" name="client_name" value="{{ old('client_name') }}" required></div>
            <div><label>Téléphone</label><input class="aj-agent-select" name="client_phone" value="{{ old('client_phone') }}" required></div>
            <div><label>Email</label><input class="aj-agent-select" type="email" name="client_email" value="{{ old('client_email') }}"></div>
            <div><label>Destination</label><input class="aj-agent-select" name="destination_text" value="{{ old('destination_text') }}"></div>
            <div><label>Date départ</label><input class="aj-agent-select" type="date" name="departure_date" value="{{ old('departure_date') }}"></div>
            <div><label>Date retour</label><input class="aj-agent-select" type="date" name="return_date" value="{{ old('return_date') }}"></div>
            <div><label>Adultes</label><input class="aj-agent-select" type="number" min="1" name="adults" value="{{ old('adults', 1) }}" required></div>
            <div><label>Enfants</label><input class="aj-agent-select" type="number" min="0" name="children_count" value="{{ old('children_count', 0) }}"></div>
            <div style="grid-column:1/-1;"><label>Notes client</label><textarea class="aj-agent-select" name="client_notes" rows="4">{{ old('client_notes') }}</textarea></div>
            <div style="grid-column:1/-1;display:flex;gap:10px;flex-wrap:wrap;">
                <button class="aj-agent-primary-btn" type="submit">Enregistrer</button>
                <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">Annuler</a>
            </div>
        </div>
    </form>
</div>
@endsection
