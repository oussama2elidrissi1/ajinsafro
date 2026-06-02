@extends('layouts.master-ajinsafro')

@section('title', 'Détail demande à la carte')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="aj-agent-dashboard">
    <div class="aj-agent-page-head">
        <div class="aj-agent-page-title">
            <h1>Détail demande à la carte</h1>
            <p>{{ $customRequest->reference }}</p>
        </div>
        <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">Retour</a>
    </div>

    <section class="aj-agent-content-grid">
        <div class="aj-agent-panel aj-agent-panel-wide">
            <div class="aj-agent-panel-body">
                <div class="aj-agent-today-item"><span>Client</span><small>{{ $customRequest->client_name }}</small></div>
                <div class="aj-agent-today-item"><span>Destination</span><small>{{ $customRequest->destination_text ?: 'À préciser' }}</small></div>
                <div class="aj-agent-today-item"><span>Date souhaitée</span><small>{{ $customRequest->departure_date ? $customRequest->departure_date->format('d/m/Y') : 'Flexible' }}</small></div>
                <div class="aj-agent-today-item"><span>Voyageurs</span><small>{{ (int) $customRequest->adults + count($customRequest->children ?? []) + count($customRequest->infants ?? []) }}</small></div>
                <div class="aj-agent-today-item"><span>Statut</span><small>{{ $customRequest->statusLabel() }}</small></div>
                <div class="aj-agent-alert-box">{{ $customRequest->client_notes ?: 'Aucune note client.' }}</div>
            </div>
        </div>
    </section>
</div>
@endsection
