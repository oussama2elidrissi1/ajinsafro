@extends('layouts.master-ajinsafro')

@section('title', 'Mes réservations')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-list-page { padding: 0 18px 28px; }
        .aj-agent-list-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px; }
        .aj-agent-list-head h1 { margin:0; color:#0e3a5a; font-weight:800; font-size:28px; }
        .aj-agent-list-head p { margin:6px 0 0; color:#64748b; font-size:14px; }
        .aj-agent-filter-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 6px 16px rgba(15,23,42,.05); padding:16px; margin-bottom:18px; }
        .aj-agent-filter-grid { display:grid; grid-template-columns:minmax(220px,2fr) minmax(150px,1fr) minmax(150px,1fr) auto auto; gap:12px; align-items:end; }
        .aj-agent-field label { display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#64748b; margin-bottom:6px; }
        .aj-agent-field input, .aj-agent-field select { width:100%; border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px; font-size:13px; color:#0f172a; background:#fff; }
        .aj-agent-table-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 6px 16px rgba(15,23,42,.05); overflow:hidden; }
        .aj-agent-table-responsive { width:100%; overflow-x:auto; }
        .aj-agent-res-table { width:100%; border-collapse:collapse; min-width:900px; }
        .aj-agent-res-table th { background:#f8fafc; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:.04em; padding:13px 14px; text-align:left; border-bottom:1px solid #e2e8f0; }
        .aj-agent-res-table td { padding:14px; border-bottom:1px solid #eef2f7; color:#0f172a; font-size:13px; vertical-align:middle; }
        .aj-agent-res-table tr:last-child td { border-bottom:0; }
        .aj-agent-ref { color:#0e3a5a; font-weight:800; }
        .aj-agent-muted { color:#64748b; font-size:12px; }
        .aj-agent-empty { padding:36px; text-align:center; color:#64748b; }
        .aj-agent-pagination { padding:16px; }
        @media (max-width: 900px) { .aj-agent-filter-grid { grid-template-columns:1fr 1fr; } }
        @media (max-width: 640px) { .aj-agent-list-page { padding:0 12px 24px; } .aj-agent-list-head { flex-direction:column; } .aj-agent-filter-grid { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
<div class="aj-agent-list-page">
    <div class="aj-agent-list-head">
        <div>
            <h1>Mes réservations</h1>
            <p>Suivi des dossiers créés, assignés ou portés par votre compte Agent.</p>
        </div>
        <a href="{{ route('agent.catalogue') }}" class="aj-agent-primary-btn">
            <i class="bx bx-map-alt"></i>
            <span>Catalogue</span>
        </a>
    </div>

    <form method="GET" action="{{ route('agent.reservations.index') }}" class="aj-agent-filter-card">
        <div class="aj-agent-filter-grid">
            <div class="aj-agent-field">
                <label for="search">Recherche client</label>
                <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Client, téléphone, dossier...">
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
            <a href="{{ route('agent.reservations.index') }}" class="aj-agent-action-btn">Réinitialiser</a>
        </div>
    </form>

    <div class="aj-agent-table-card">
        @if($reservations->count())
            <div class="aj-agent-table-responsive">
                <table class="aj-agent-res-table">
                    <thead>
                        <tr>
                            <th>Référence / dossier</th>
                            <th>Client</th>
                            <th>Voyage</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Montant</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reservations as $reservation)
                            @php
                                $clientName = trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? ''));
                                $date = $reservation->travelDate?->date ?? $reservation->departure?->start_date ?? $reservation->created_at;
                            @endphp
                            <tr>
                                <td>
                                    <div class="aj-agent-ref">{{ $reservation->dossier_number ?: 'RES-'.$reservation->id }}</div>
                                    <div class="aj-agent-muted">#{{ $reservation->id }}</div>
                                </td>
                                <td>
                                    <div>{{ $clientName !== '' ? $clientName : 'Client non renseigné' }}</div>
                                    <div class="aj-agent-muted">{{ $reservation->client_phone ?: '—' }}</div>
                                </td>
                                <td>{{ $reservation->tour?->name ?: 'Voyage non renseigné' }}</td>
                                <td>{{ $date ? $date->format('d/m/Y') : '—' }}</td>
                                <td><span class="aj-agent-status aj-agent-status-orange">{{ $statusOptions[$reservation->status] ?? $reservation->status }}</span></td>
                                <td>{{ number_format((float) ($reservation->total_amount ?? 0), 0, ',', ' ') }} DH</td>
                                <td><a href="{{ route('agent.reservations.show', $reservation) }}" class="aj-agent-action-btn">Voir détails</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="aj-agent-pagination">{{ $reservations->links() }}</div>
        @else
            <div class="aj-agent-empty">
                <h2>Aucune réservation trouvée</h2>
                <p>Vos réservations créées ou assignées apparaîtront ici.</p>
                <a href="{{ route('agent.catalogue') }}" class="aj-agent-primary-btn">Consulter le catalogue</a>
            </div>
        @endif
    </div>
</div>
@endsection
