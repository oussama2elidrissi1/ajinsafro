@extends('layouts.master-ajinsafro')

@section('title', 'Catalogue de voyage')

@php
    use Illuminate\Support\Facades\Route;

    $agentIdentity = \Illuminate\Support\Str::lower(trim((auth()->user()->name ?? '') . ' ' . (auth()->user()->email ?? '')));
    $agentCanCreateVoyages = Route::has('agent.voyages.create')
        && \Illuminate\Support\Str::contains($agentIdentity, ['oumaima', 'oumayma']);
@endphp

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-catalogue { padding: 0 18px 28px; }
        .aj-agent-catalogue-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px; }
        .aj-agent-catalogue-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
        .aj-agent-catalogue-head h1 { margin:0; color:#0e3a5a; font-weight:800; font-size:28px; }
        .aj-agent-catalogue-head p { margin:6px 0 0; color:#64748b; font-size:14px; }
        .aj-agent-filter-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 6px 16px rgba(15,23,42,.05); padding:16px; margin-bottom:18px; }
        .aj-agent-filter-grid { display:grid; grid-template-columns:minmax(220px,2fr) minmax(170px,1fr) minmax(150px,1fr) minmax(150px,1fr) auto auto; gap:12px; align-items:end; }
        .aj-agent-field label { display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#64748b; margin-bottom:6px; }
        .aj-agent-field input, .aj-agent-field select { width:100%; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px; font-size:13px; color:#0f172a; background:#fff; }
        .aj-agent-catalogue-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
        .aj-agent-trip-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; overflow:hidden; box-shadow:0 6px 16px rgba(15,23,42,.05); display:flex; flex-direction:column; min-height:100%; }
        .aj-agent-trip-media { height:176px; background:linear-gradient(135deg,#e6f3fa,#f8fafc); position:relative; }
        .aj-agent-trip-media img { width:100%; height:100%; object-fit:cover; display:block; }
        .aj-agent-trip-placeholder { height:100%; display:flex; align-items:center; justify-content:center; color:#0083c4; font-size:34px; }
        .aj-agent-trip-body { padding:16px; display:flex; flex-direction:column; gap:12px; flex:1; }
        .aj-agent-trip-title { margin:0; color:#0e3a5a; font-size:17px; font-weight:800; line-height:1.3; }
        .aj-agent-trip-meta { display:flex; flex-wrap:wrap; gap:8px; color:#64748b; font-size:12px; }
        .aj-agent-chip { display:inline-flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:999px; padding:6px 9px; }
        .aj-agent-trip-kpis { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .aj-agent-trip-kpi { border:1px solid #e2e8f0; border-radius:12px; padding:10px; background:#fbfdff; }
        .aj-agent-trip-kpi span { display:block; color:#64748b; font-size:11px; font-weight:700; }
        .aj-agent-trip-kpi strong { display:block; color:#0f172a; font-size:14px; margin-top:3px; }
        .aj-agent-trip-actions { margin-top:auto; display:flex; gap:8px; flex-wrap:wrap; }
        .aj-agent-trip-actions .btn-view { width:100%; min-height:42px; }
        .aj-agent-empty { background:#fff; border:1px dashed #cbd5e1; border-radius:18px; padding:34px; text-align:center; color:#64748b; grid-column:1/-1; }
        .aj-agent-pagination { margin-top:18px; }
        @media (max-width: 1180px) { .aj-agent-filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .aj-agent-catalogue-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width: 720px) { .aj-agent-catalogue { padding:0 12px 24px; } .aj-agent-catalogue-head { flex-direction:column; } .aj-agent-filter-grid, .aj-agent-catalogue-grid { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
<div class="aj-agent-catalogue">
    <div class="aj-agent-catalogue-head">
        <div>
            <h1>Catalogue de voyage</h1>
            <p>Voyages actifs et départs disponibles dans l’espace Agent.</p>
        </div>
        <div class="aj-agent-catalogue-actions">
            @if($agentCanCreateVoyages)
                <a href="{{ route('agent.voyages.create') }}" class="aj-agent-primary-btn">
                    <i class="bx bx-plus"></i>
                    <span>Creer un voyage</span>
                </a>
            @endif
            <a href="{{ route('agent.dashboard') }}" class="aj-agent-action-btn">
                <i class="bx bx-arrow-back"></i>
                <span>Tableau de bord</span>
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('agent.catalogue') }}" class="aj-agent-filter-card">
        <div class="aj-agent-filter-grid">
            <div class="aj-agent-field">
                <label for="search">Recherche</label>
                <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, destination...">
            </div>
            <div class="aj-agent-field">
                <label for="destination">Destination</label>
                <select id="destination" name="destination">
                    <option value="">Toutes</option>
                    @foreach($destinationOptions as $destination)
                        <option value="{{ $destination }}" @selected(($filters['destination'] ?? '') === $destination)>{{ $destination }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aj-agent-field">
                <label for="date">Date min.</label>
                <input id="date" type="date" name="date" value="{{ $filters['date'] ?? '' }}">
            </div>
            <div class="aj-agent-field">
                <label for="budget_max">Budget max.</label>
                <input id="budget_max" type="number" min="0" step="100" name="budget_max" value="{{ (int) ($filters['budget_max'] ?? 0) ?: '' }}" placeholder="MAD">
            </div>
            <button type="submit" class="aj-agent-primary-btn">Filtrer</button>
            <a href="{{ route('agent.catalogue') }}" class="aj-agent-action-btn">Réinitialiser</a>
        </div>
    </form>

    <div class="aj-agent-catalogue-grid">
        @forelse($voyages as $voyage)
            @php
                $departure = $voyage->agent_catalogue_next_departure;
                $remaining = $departure ? (int) ($departure->available_capacity ?? 0) : 0;
                $capacity = $departure ? (int) (($departure->capacity ?? null) ?: ($departure->total_capacity ?? 0)) : 0;
                $modalCode = 'agent-voyage-'.$voyage->id;
            @endphp
            <article class="aj-agent-trip-card">
                <div class="aj-agent-trip-media">
                    @if($voyage->agent_catalogue_image_url)
                        <img src="{{ $voyage->agent_catalogue_image_url }}" alt="{{ $voyage->name }}">
                    @else
                        <div class="aj-agent-trip-placeholder"><i class="bx bx-map-alt"></i></div>
                    @endif
                </div>
                <div class="aj-agent-trip-body">
                    <div>
                        <h2 class="aj-agent-trip-title">{{ $voyage->name }}</h2>
                        <div class="aj-agent-trip-meta">
                            <span class="aj-agent-chip"><i class="bx bx-map-pin"></i>{{ $voyage->destination ?: 'Destination à confirmer' }}</span>
                            <span class="aj-agent-chip"><i class="bx bx-calendar"></i>{{ $voyage->agent_catalogue_future_departures_count }} départ(s)</span>
                        </div>
                    </div>

                    <div class="aj-agent-trip-kpis">
                        <div class="aj-agent-trip-kpi">
                            <span>Départ proche</span>
                            <strong>{{ $departure?->start_date ? $departure->start_date->format('d/m/Y') : 'À confirmer' }}</strong>
                        </div>
                        <div class="aj-agent-trip-kpi">
                            <span>Prix à partir de</span>
                            <strong>{{ $voyage->agent_catalogue_price_label }}</strong>
                        </div>
                        <div class="aj-agent-trip-kpi">
                            <span>Capacité</span>
                            <strong>{{ $capacity > 0 ? $capacity.' places' : '—' }}</strong>
                        </div>
                        <div class="aj-agent-trip-kpi">
                            <span>Restant</span>
                            <strong>{{ $remaining > 0 ? $remaining.' places' : 'À vérifier' }}</strong>
                        </div>
                    </div>

                    <div class="aj-agent-trip-actions">
                        <button type="button"
                                class="aj-agent-action-btn btn-ws-open-detail btn-view"
                                data-row-code="{{ $modalCode }}"
                                @if($departure)
                                    data-travel-date-id="{{ $departure->wp_travel_date_id ?: $departure->id }}"
                                @endif
                                title="Voir">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                            <span>Voir</span>
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="aj-agent-empty">
                <h2>Aucun voyage disponible</h2>
                <p>Ajustez les filtres ou vérifiez les départs actifs du catalogue.</p>
            </div>
        @endforelse
    </div>

    <div class="aj-agent-pagination">
        {{ $voyages->links() }}
    </div>
</div>

<script type="application/json" id="ws-modal-detail-json">{!! json_encode($agentCatalogueDetailMap ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@include('agent.partials.catalogue-detail-modal')
@endsection
