@extends('layouts.master-ajinsafro')

@section('title', 'Détail réservation')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
@php
    $clientName = trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? ''));
    $date = $reservation->travelDate?->date ?? $reservation->departure?->start_date ?? $reservation->created_at;
@endphp
<div class="aj-agent-dashboard">
    <div class="aj-agent-page-head">
        <div class="aj-agent-page-title">
            <h1>Détail réservation</h1>
            <p>{{ $reservation->dossier_number ?: 'RES-'.$reservation->id }}</p>
        </div>
        <a href="{{ route('agent.reservations.index') }}" class="aj-agent-action-btn">Retour</a>
    </div>

    <section class="aj-agent-content-grid">
        <div class="aj-agent-panel aj-agent-panel-wide">
            <div class="aj-agent-panel-body">
                <div class="aj-agent-today-item"><span>Client</span><small>{{ $clientName !== '' ? $clientName : 'Client non renseigné' }}</small></div>
                <div class="aj-agent-today-item"><span>Voyage</span><small>{{ $reservation->tour?->name ?: 'Voyage non renseigné' }}</small></div>
                <div class="aj-agent-today-item"><span>Date</span><small>{{ $date ? $date->format('d/m/Y') : '—' }}</small></div>
                <div class="aj-agent-today-item"><span>Statut</span><small>{{ $reservation->status ?: '—' }}</small></div>
                <div class="aj-agent-today-item"><span>Montant</span><small>{{ number_format((float) ($reservation->total_amount ?? 0), 0, ',', ' ') }} DH</small></div>
            </div>
        </div>
    </section>
</div>
@endsection
