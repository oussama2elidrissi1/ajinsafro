@extends('layouts.admin-v6')

@section('title', 'Demandes à la carte')
@section('page_title', 'Demandes à la carte')

@php
    $badge = fn($status) => match($status) {
        'draft' => 'secondary', 'new' => 'info', 'assigned', 'processing' => 'primary',
        'missing_info', 'modification_requested' => 'warning', 'quote_prepared', 'quote_sent', 'waiting_customer' => 'dark',
        'confirmed' => 'success', 'cancelled', 'refused' => 'danger', default => 'secondary',
    };
    $priorityBadge = fn($priority) => match($priority) {
        'very_urgent' => 'danger', 'urgent' => 'warning', default => 'secondary',
    };
@endphp

@push('styles')
<style>
    .dac-list { display:grid; gap:16px; }
    .dac-toolbar,.dac-panel { background:#fff; border:1px solid #dde7f0; border-radius:8px; padding:16px; }
    .dac-toolbar { display:flex; justify-content:space-between; gap:12px; align-items:center; }
    .dac-toolbar h2 { margin:0; font-size:20px; font-weight:600; color:#10233f; }
    .dac-btn { border:0; border-radius:6px; padding:8px 11px; display:inline-flex; align-items:center; gap:6px; font-weight:600; text-decoration:none; white-space:nowrap; }
    .dac-btn-primary { background:#1f6feb; color:#fff; }
    .dac-btn-soft { background:#eef3f8; color:#20324d; border:1px solid #d8e2ec; }
    .dac-kpis { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:10px; }
    .dac-kpi { background:#fff; border:1px solid #dde7f0; border-radius:8px; padding:14px; }
    .dac-kpi span { color:#6b7c8f; font-size:12px; font-weight:600; }
    .dac-kpi strong { display:block; color:#10233f; font-size:22px; font-weight:600; margin-top:4px; }
    .dac-filters { display:grid; grid-template-columns:1.4fr repeat(5,minmax(0,1fr)) auto; gap:10px; align-items:end; }
    .dac-field label { display:block; font-size:12px; font-weight:600; color:#536276; margin-bottom:5px; }
    .dac-field input,.dac-field select { width:100%; border:1px solid #d8e2ec; border-radius:6px; padding:8px 9px; }
    .dac-table-wrap { overflow:auto; border:1px solid #e7edf3; border-radius:8px; }
    .dac-table { min-width:1120px; width:100%; border-collapse:collapse; }
    .dac-table th { background:#f7fafc; color:#536276; font-size:12px; font-weight:600; padding:10px; border-bottom:1px solid #e7edf3; }
    .dac-table td { padding:11px 10px; border-bottom:1px solid #edf2f7; vertical-align:middle; color:#20324d; }
    .dac-muted { color:#718096; font-size:12px; }
    @media(max-width:1200px){ .dac-kpis{grid-template-columns:repeat(3,1fr)} .dac-filters{grid-template-columns:repeat(2,1fr)} }
    @media(max-width:720px){ .dac-toolbar{display:grid}.dac-kpis,.dac-filters{grid-template-columns:1fr} }
</style>
@endpush

@section('content')
<div class="dac-list">
    <div class="dac-toolbar">
        <div>
            <h2>Demandes à la carte</h2>
            <div class="dac-muted">Workflow commercial, quotation offline et devis PDF automatique.</div>
        </div>
        @can('custom_requests.create')
            <a href="{{ route('admin.custom-requests.create') }}" class="dac-btn dac-btn-primary"><i class="bx bx-plus"></i> Nouvelle demande</a>
        @endcan
    </div>

    @if(session('success')) <div class="alert alert-success mb-0">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger mb-0">{{ session('error') }}</div> @endif

    <div class="dac-kpis">
        <div class="dac-kpi"><span>Total</span><strong>{{ $stats['total'] }}</strong></div>
        <div class="dac-kpi"><span>Nouvelles</span><strong>{{ $stats['new'] }}</strong></div>
        <div class="dac-kpi"><span>Urgentes</span><strong>{{ $stats['urgent'] }}</strong></div>
        <div class="dac-kpi"><span>Modifications</span><strong>{{ $stats['modification_requested'] }}</strong></div>
        <div class="dac-kpi"><span>Devis envoyés</span><strong>{{ $stats['quote_sent'] }}</strong></div>
        <div class="dac-kpi"><span>Confirmées</span><strong>{{ $stats['confirmed'] }}</strong></div>
    </div>

    <div class="dac-panel">
        <form method="GET" class="dac-filters mb-3">
            <div class="dac-field"><label>Client / téléphone</label><input name="search" value="{{ $filters['search'] }}"></div>
            <div class="dac-field"><label>Statut</label><select name="status"><option value="">Tous</option>@foreach($statusOptions as $key=>$label)<option value="{{ $key }}" @selected($filters['status']===$key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Priorité</label><select name="priority"><option value="">Toutes</option>@foreach($priorityOptions as $key=>$label)<option value="{{ $key }}" @selected($filters['priority']===$key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Destination</label><input name="destination" value="{{ $filters['destination'] }}"></div>
            <div class="dac-field"><label>Date départ</label><input type="date" name="date" value="{{ $filters['date'] }}"></div>
            <div class="dac-field"><label>Agent offline</label><select name="assigned_to"><option value="">Tous</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected($filters['assigned_to']===$agent->id)>{{ $agent->name }}</option>@endforeach</select></div>
            <button type="submit" class="dac-btn dac-btn-soft"><i class="bx bx-filter"></i> Filtrer</button>
        </form>

        <div class="dac-table-wrap">
            <table class="dac-table">
                <thead>
                    <tr>
                        <th>Numéro</th><th>Client</th><th>Voyage</th><th>Départ</th><th>Voyageurs</th><th>Priorité</th><th>Statut</th><th>Créateur</th><th>Agent offline</th><th>Dernier devis</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($customRequests as $row)
                    <tr>
                        <td><a href="{{ route('admin.custom-requests.show', $row) }}" class="fw-semibold">{{ $row->request_number }}</a><div class="dac-muted">{{ $row->created_at?->format('d/m/Y') }}</div></td>
                        <td>{{ $row->customer_full_name }}<div class="dac-muted">{{ $row->customer_phone }}</div></td>
                        <td>{{ $row->desired_destination }}<div class="dac-muted">{{ $travelTypeOptions[$row->travel_type] ?? $row->travel_type }}</div></td>
                        <td>{{ $row->departure_city }}<div class="dac-muted">{{ $row->desired_departure_date?->format('d/m/Y') }}</div></td>
                        <td>{{ $row->travelers_count }}<div class="dac-muted">{{ $row->adults_count }}A / {{ $row->children_count }}E / {{ $row->babies_count }}B</div></td>
                        <td><span class="badge bg-{{ $priorityBadge($row->priority) }}">{{ $row->priorityLabel() }}</span></td>
                        <td><span class="badge bg-{{ $badge($row->status) }}">{{ $row->statusLabel() }}</span></td>
                        <td>{{ $row->creator?->name ?: '-' }}</td>
                        <td>{{ $row->assignedAgent?->name ?: '-' }}</td>
                        <td>{{ $row->latestQuote ? number_format((float) $row->latestQuote->total_sale, 2, ',', ' ').' '.$row->latestQuote->currency : '-' }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.custom-requests.show', $row) }}" class="dac-btn dac-btn-soft">Voir</a>
                                @if($row->canBeQuotedBy(auth()->user()))
                                    @if((int) ($row->assigned_to ?? 0) === (int) auth()->id())
                                        <a href="{{ route('admin.custom-requests.quote', $row) }}" class="dac-btn dac-btn-primary">Quotation</a>
                                    @else
                                        <form method="POST" action="{{ route('admin.custom-requests.take', $row) }}">
                                            @csrf
                                            <button type="submit" class="dac-btn dac-btn-primary">Prendre en charge</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">Aucune demande trouvée.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $customRequests->links() }}</div>
    </div>
</div>
@endsection
