@extends('layouts.master-ajinsafro')

@section('title', 'Réserver ce départ')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-create-page{padding:0 18px 28px}
        .aj-agent-create-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}
        .aj-agent-create-head h1{margin:0;color:#0e3a5a;font-weight:800;font-size:28px}
        .aj-agent-create-head p{margin:6px 0 0;color:#64748b;font-size:14px}
        .aj-agent-reserve-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 6px 16px rgba(15,23,42,.05);padding:18px}
        .aj-agent-reserve-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:18px}
        .aj-agent-reserve-info{border:1px solid #e2e8f0;border-radius:14px;background:#fbfdff;padding:14px}
        .aj-agent-reserve-info span{display:block;color:#64748b;font-size:12px;font-weight:700}
        .aj-agent-reserve-info strong{display:block;color:#0f172a;font-size:15px;font-weight:700;margin-top:4px}
        .aj-agent-reserve-actions{display:flex;gap:10px;flex-wrap:wrap}
        .aj-agent-reserve-note{border:1px dashed #cbd5e1;border-radius:14px;background:#f8fafc;color:#64748b;padding:14px;margin-bottom:16px}
        @media(max-width:720px){.aj-agent-create-page{padding:0 12px 24px}.aj-agent-create-head{flex-direction:column}.aj-agent-reserve-grid{grid-template-columns:1fr}}
    </style>
@endpush

@section('content')
<div class="aj-agent-create-page">
    <div class="aj-agent-create-head">
        <div>
            <h1>Réserver ce départ</h1>
            <p>Départ sélectionné depuis le catalogue Agent.</p>
        </div>
        <a href="{{ route('agent.catalogue') }}" class="aj-agent-action-btn">
            <i class="bx bx-arrow-back"></i>
            <span>Retour catalogue</span>
        </a>
    </div>

    <div class="aj-agent-reserve-card">
        <div class="aj-agent-reserve-grid">
            <div class="aj-agent-reserve-info">
                <span>Voyage</span>
                <strong>{{ $voyage?->name ?: 'Voyage non renseigné' }}</strong>
            </div>
            <div class="aj-agent-reserve-info">
                <span>Destination</span>
                <strong>{{ $voyage?->destination ?: 'À confirmer' }}</strong>
            </div>
            <div class="aj-agent-reserve-info">
                <span>Date de départ</span>
                <strong>{{ $departure?->start_date ? $departure->start_date->format('d/m/Y') : 'À confirmer' }}</strong>
            </div>
            <div class="aj-agent-reserve-info">
                <span>Places restantes</span>
                <strong>{{ $departure ? (int) ($departure->available_capacity ?? 0).' place(s)' : 'À vérifier' }}</strong>
            </div>
        </div>

        <div class="aj-agent-reserve-note">
            Le tunnel complet de création réservation Agent n’est pas encore implémenté ici. Cette page Agent dédiée évite toute redirection Admin/Vente et conserve le contexte du départ sélectionné.
        </div>

        <div class="aj-agent-reserve-actions">
            <a href="{{ route('agent.custom-reservations.create', array_filter([
                'destination' => $voyage?->destination,
                'departure_date' => $departure?->start_date?->format('Y-m-d'),
                'voyage_id' => $voyage?->id,
                'departure_id' => $departure?->id,
                'travel_date_id' => $travelDateId,
            ])) }}" class="aj-agent-primary-btn">
                <i class="bx bx-edit-alt"></i>
                <span>Créer une demande Agent</span>
            </a>
            <a href="{{ route('agent.reservations.index') }}" class="aj-agent-action-btn">Mes réservations</a>
        </div>
    </div>
</div>
@endsection
