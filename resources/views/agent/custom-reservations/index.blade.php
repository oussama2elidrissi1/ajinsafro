@extends('layouts.master-ajinsafro')

@section('title', 'Reservations a la carte')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-custom-page { padding: 0 18px 28px; }
        .aj-agent-custom-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px; }
        .aj-agent-custom-head h1 { margin:0; color:#0e3a5a; font-weight:800; font-size:28px; }
        .aj-agent-custom-head p { margin:6px 0 0; color:#64748b; font-size:14px; }
        .aj-agent-filter-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 6px 16px rgba(15,23,42,.05); padding:16px; margin-bottom:18px; }
        .aj-agent-filter-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; }
        .aj-agent-filter-head strong { color:#0e3a5a; font-size:15px; font-weight:800; }
        .aj-agent-filter-grid { display:grid; grid-template-columns:minmax(180px,1fr) minmax(180px,1fr) minmax(140px,1fr) minmax(140px,1fr) auto auto; gap:12px; align-items:end; }
        .aj-agent-field label { display:block; font-size:11px; font-weight:800; text-transform:uppercase; color:#64748b; margin-bottom:6px; }
        .aj-agent-field input, .aj-agent-field select { width:100%; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px; font-size:13px; color:#0f172a; background:#fff; }
        .aj-agent-request-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .aj-agent-request-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 6px 16px rgba(15,23,42,.05); padding:16px; }
        .aj-agent-request-card h2 { margin:0; color:#0e3a5a; font-size:17px; font-weight:800; }
        .aj-agent-request-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; margin:14px 0; }
        .aj-agent-request-meta div { background:#fbfdff; border:1px solid #e2e8f0; border-radius:12px; padding:10px; }
        .aj-agent-request-meta span { display:block; color:#64748b; font-size:11px; font-weight:700; }
        .aj-agent-request-meta strong { display:block; color:#0f172a; font-size:13px; margin-top:3px; }
        .aj-agent-request-actions { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
        .aj-agent-empty { background:#fff; border:1px dashed #cbd5e1; border-radius:18px; padding:36px; text-align:center; color:#64748b; }
        .aj-agent-badge { display:inline-flex; align-items:center; border-radius:999px; padding:5px 9px; background:#e8f4fd; color:#0570b8; font-size:12px; font-weight:800; }
        @media (max-width: 1000px) { .aj-agent-filter-grid { grid-template-columns:1fr 1fr; } .aj-agent-request-grid { grid-template-columns:1fr; } }
        @media (max-width: 640px) { .aj-agent-custom-page { padding:0 12px 24px; } .aj-agent-custom-head, .aj-agent-filter-head { flex-direction:column; align-items:stretch; } .aj-agent-filter-grid, .aj-agent-request-meta { grid-template-columns:1fr; } .aj-agent-filter-head .aj-agent-primary-btn { width:100%; justify-content:center; } }
    </style>
@endpush

@section('content')
<div class="aj-agent-custom-page">
    <div class="aj-agent-custom-head">
        <div>
            <h1>Reservations a la carte</h1>
            <p>Demandes personnalisees creees par vous ou avec un devis recu.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('agent.custom-reservations.index') }}" class="aj-agent-filter-card">
        <div class="aj-agent-filter-head">
            <strong>Liste des demandes</strong>
            @if($canCreateRequest ?? false)
                <a href="{{ route('agent.custom-reservations.create') }}" class="aj-agent-primary-btn">
                    <i class="bx bx-plus-circle"></i>
                    <span>Creer une reservation a la carte</span>
                </a>
            @endif
        </div>
        <div class="aj-agent-filter-grid">
            <div class="aj-agent-field">
                <label for="client">Client</label>
                <input id="client" type="text" name="client" value="{{ $filters['client'] ?? '' }}" placeholder="Nom, telephone, reference...">
            </div>
            <div class="aj-agent-field">
                <label for="destination">Destination</label>
                <input id="destination" type="text" name="destination" value="{{ $filters['destination'] ?? '' }}" placeholder="Destination souhaitee">
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
            <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">Reinitialiser</a>
        </div>
    </form>

    @if($requests->count())
        <div class="aj-agent-request-grid">
            @foreach($requests as $requestRow)
                <article class="aj-agent-request-card">
                    <h2>{{ $requestRow->customer_full_name }}</h2>
                    <p class="aj-agent-muted">{{ $requestRow->request_number }}</p>
                    <div class="aj-agent-request-meta">
                        <div><span>Destination souhaitee</span><strong>{{ $requestRow->desired_destination }}</strong></div>
                        <div><span>Date souhaitee</span><strong>{{ $requestRow->desired_departure_date ? $requestRow->desired_departure_date->format('d/m/Y') : 'Flexible' }}</strong></div>
                        <div><span>Voyageurs</span><strong>{{ $requestRow->travelers_count }} personne(s)</strong></div>
                        <div><span>Statut</span><strong>{{ $requestRow->statusLabel() }}</strong></div>
                        <div><span>Agent offline</span><strong>{{ $requestRow->assignedAgent?->name ?: 'En attente' }}</strong></div>
                        <div><span>Dernier devis</span><strong>{{ $requestRow->latestQuote ? number_format((float) $requestRow->latestQuote->total_sale, 2, ',', ' ').' '.$requestRow->latestQuote->currency : '-' }}</strong></div>
                    </div>
                    <div class="aj-agent-request-actions">
                        <span class="aj-agent-badge">{{ $requestRow->priorityLabel() }}</span>
                        <a href="{{ route('agent.custom-reservations.show', $requestRow) }}" class="aj-agent-action-btn">Voir</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="aj-agent-pagination">{{ $requests->links() }}</div>
    @else
        <div class="aj-agent-empty">
            <h2>Aucune demande a la carte</h2>
            <p>Les demandes personnalisees creees par votre compte apparaitront ici.</p>
            @if($canCreateRequest ?? false)
                <a href="{{ route('agent.custom-reservations.create') }}" class="aj-agent-primary-btn">Creer une demande</a>
            @endif
        </div>
    @endif
</div>
@endsection
