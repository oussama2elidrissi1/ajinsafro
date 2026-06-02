@extends('layouts.master-ajinsafro')

@section('title', 'Réservations à la carte')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-custom-page { padding: 0 18px 28px; }
        .aj-agent-custom-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px; }
        .aj-agent-custom-head h1 { margin:0; color:#0e3a5a; font-weight:800; font-size:28px; }
        .aj-agent-custom-head p { margin:6px 0 0; color:#64748b; font-size:14px; }
        .aj-agent-filter-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 6px 16px rgba(15,23,42,.05); padding:16px; margin-bottom:18px; }
        .aj-agent-filter-grid { display:grid; grid-template-columns:minmax(180px,1fr) minmax(180px,1fr) minmax(140px,1fr) minmax(140px,1fr) auto auto; gap:12px; align-items:end; }
        .aj-agent-field label { display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#64748b; margin-bottom:6px; }
        .aj-agent-field input, .aj-agent-field select, .aj-agent-field textarea { width:100%; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px; font-size:13px; color:#0f172a; background:#fff; }
        .aj-agent-request-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .aj-agent-request-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 6px 16px rgba(15,23,42,.05); padding:16px; }
        .aj-agent-request-card h2 { margin:0; color:#0e3a5a; font-size:17px; font-weight:800; }
        .aj-agent-request-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin:14px 0; }
        .aj-agent-request-meta div { background:#fbfdff; border:1px solid #e2e8f0; border-radius:12px; padding:10px; }
        .aj-agent-request-meta span { display:block; color:#64748b; font-size:11px; font-weight:700; }
        .aj-agent-request-meta strong { display:block; color:#0f172a; font-size:13px; margin-top:3px; }
        .aj-agent-request-actions { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
        .aj-agent-empty { background:#fff; border:1px dashed #cbd5e1; border-radius:18px; padding:36px; text-align:center; color:#64748b; }
        .aj-agent-pagination { margin-top:16px; }
        @media (max-width: 1000px) { .aj-agent-filter-grid { grid-template-columns:1fr 1fr; } .aj-agent-request-grid { grid-template-columns:1fr; } }
        @media (max-width: 640px) { .aj-agent-custom-page { padding:0 12px 24px; } .aj-agent-custom-head { flex-direction:column; } .aj-agent-filter-grid, .aj-agent-request-meta { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
<div class="aj-agent-custom-page">
    <div class="aj-agent-custom-head">
        <div>
            <h1>Réservations à la carte</h1>
            <p>Demandes personnalisées créées ou assignées à votre compte Agent.</p>
        </div>
        <a href="{{ route('agent.custom-reservations.create') }}" class="aj-agent-primary-btn">
            <i class="bx bx-plus-circle"></i>
            <span>Nouvelle demande à la carte</span>
        </a>
    </div>

    <form method="GET" action="{{ route('agent.custom-reservations.index') }}" class="aj-agent-filter-card">
        <div class="aj-agent-filter-grid">
            <div class="aj-agent-field">
                <label for="client">Client</label>
                <input id="client" type="text" name="client" value="{{ $filters['client'] ?? '' }}" placeholder="Nom, téléphone, référence...">
            </div>
            <div class="aj-agent-field">
                <label for="destination">Destination</label>
                <input id="destination" type="text" name="destination" value="{{ $filters['destination'] ?? '' }}" placeholder="Destination souhaitée">
            </div>
            <div class="aj-agent-field">
                <label for="status">Statut</label>
                <select id="status" name="status">
                    <option value="">Tous</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aj-agent-field">
                <label for="date">Date</label>
                <input id="date" type="date" name="date" value="{{ $filters['date'] ?? '' }}">
            </div>
            <button type="submit" class="aj-agent-primary-btn">Filtrer</button>
            <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">Réinitialiser</a>
        </div>
    </form>

    @if($requests->count())
        <div class="aj-agent-request-grid">
            @foreach($requests as $requestRow)
                @php
                    $childrenCount = is_array($requestRow->children) ? count($requestRow->children) : 0;
                    $infantsCount = is_array($requestRow->infants) ? count($requestRow->infants) : 0;
                    $travelersCount = (int) $requestRow->adults + $childrenCount + $infantsCount;
                @endphp
                <article class="aj-agent-request-card">
                    <h2>{{ $requestRow->client_name }}</h2>
                    <p class="aj-agent-muted">{{ $requestRow->reference }}</p>
                    <div class="aj-agent-request-meta">
                        <div><span>Destination souhaitée</span><strong>{{ $requestRow->destination_text ?: 'À préciser' }}</strong></div>
                        <div><span>Date souhaitée</span><strong>{{ $requestRow->departure_date ? $requestRow->departure_date->format('d/m/Y') : 'Flexible' }}</strong></div>
                        <div><span>Voyageurs</span><strong>{{ $travelersCount }} personne(s)</strong></div>
                        <div><span>Statut</span><strong>{{ $requestRow->statusLabel() }}</strong></div>
                    </div>
                    <div class="aj-agent-request-actions">
                        <span class="aj-agent-muted">{{ $requestRow->client_phone }}</span>
                        <a href="{{ route('agent.custom-reservations.show', $requestRow) }}" class="aj-agent-action-btn">Voir</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="aj-agent-pagination">{{ $requests->links() }}</div>
    @else
        <div class="aj-agent-empty">
            <h2>Aucune demande à la carte</h2>
            <p>Les demandes personnalisées créées par votre compte apparaîtront ici.</p>
            <a href="{{ route('agent.custom-reservations.create') }}" class="aj-agent-primary-btn">Créer une demande</a>
        </div>
    @endif
</div>
@endsection
