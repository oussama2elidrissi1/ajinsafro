@extends('layouts.admin-v6')

@section('title', $customRequest->reference)
@section('page_title', 'Détail demande à la carte')

@php
    $serviceLabels = $serviceOptions ?? [];
    $statusClass = match ($customRequest->status) {
        'new' => 'info', 'in_review' => 'primary', 'quoted' => 'warning', 'accepted', 'converted' => 'success', 'cancelled' => 'danger', default => 'secondary',
    };
@endphp

@push('styles')
<style>
    .crq-show { display:grid;gap:18px; }
    .crq-show-head { background:linear-gradient(135deg,#073b5c,#0f5f8f);color:#fff;border-radius:20px;padding:22px;display:flex;justify-content:space-between;gap:16px;align-items:center;box-shadow:0 14px 30px rgba(15,39,66,.16); }
    .crq-show-head h2 { margin:0;font-size:24px;font-weight:900; }
    .crq-show-head p { margin:6px 0 0;color:rgba(255,255,255,.78);font-weight:700; }
    .crq-actions { display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end; }
    .crq-btn { border:0;border-radius:12px;padding:10px 14px;font-weight:900;display:inline-flex;align-items:center;gap:8px;text-decoration:none;white-space:nowrap; }
    .crq-btn-primary { background:#ff7a1a;color:#fff; }
    .crq-btn-soft { background:#eef6fb;color:#0f5f8f;border:1px solid #d9e8f2; }
    .crq-grid { display:grid;grid-template-columns:1.1fr .9fr;gap:16px; }
    .crq-card { background:#fff;border:1px solid #dce8f1;border-radius:18px;padding:18px;box-shadow:0 8px 22px rgba(15,39,66,.05); }
    .crq-card h3 { margin:0 0 14px;color:#0f2742;font-size:16px;font-weight:900; }
    .crq-dl { display:grid;grid-template-columns:170px 1fr;gap:10px 14px;margin:0; }
    .crq-dl dt { color:#64748b;font-size:12px;font-weight:900;text-transform:uppercase; }
    .crq-dl dd { margin:0;color:#0f2742;font-weight:750; }
    .crq-service-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px; }
    .crq-service { border:1px solid #e6edf5;border-radius:14px;padding:14px;background:#fbfdff; }
    .crq-service strong { display:block;color:#0f5f8f;margin-bottom:8px; }
    .crq-service pre { margin:0;white-space:pre-wrap;font:inherit;color:#334155; }
    .crq-note { white-space:pre-wrap;color:#334155;font-weight:650;line-height:1.65; }
    .crq-status-form { display:flex;gap:8px;flex-wrap:wrap;align-items:end; }
    .crq-status-form select,.crq-status-form input,.crq-status-form textarea { border:1px solid #dce8f1;border-radius:12px;padding:10px;font-weight:700; }
    @media(max-width:900px){ .crq-show-head,.crq-grid{grid-template-columns:1fr;flex-direction:column;align-items:stretch}.crq-service-grid{grid-template-columns:1fr}.crq-dl{grid-template-columns:1fr} }
</style>
@endpush

@section('content')
<div class="crq-show">
    <div class="crq-show-head">
        <div>
            <h2>{{ $customRequest->reference }}</h2>
            <p>{{ $customRequest->client_name }} · {{ $customRequest->destination_text ?: 'Destination libre' }}</p>
            <div class="mt-2">
                <span class="badge bg-{{ $statusClass }}">{{ $customRequest->statusLabel() }}</span>
                <span class="badge bg-light text-dark">{{ $customRequest->priorityLabel() }}</span>
            </div>
        </div>
        <div class="crq-actions">
            <a href="{{ route('admin.reservations.custom-requests.index') }}" class="crq-btn crq-btn-soft"><i class="bx bx-arrow-back"></i> Retour</a>
            <a href="{{ route('admin.reservations.custom-requests.edit', $customRequest) }}" class="crq-btn crq-btn-soft"><i class="bx bx-edit"></i> Modifier</a>
            <form method="POST" action="{{ route('admin.reservations.custom-requests.convert-to-reservation', $customRequest) }}">@csrf<button class="crq-btn crq-btn-primary" type="submit"><i class="bx bx-transfer"></i> Convertir en réservation</button></form>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success mb-0">{{ session('success') }}</div>@endif

    <div class="crq-grid">
        <div class="crq-card">
            <h3>Informations client</h3>
            <dl class="crq-dl">
                <dt>Type</dt><dd>{{ ucfirst($customRequest->client_type) }}</dd>
                <dt>Nom</dt><dd>{{ $customRequest->client_name }}</dd>
                <dt>Téléphone</dt><dd>{{ $customRequest->client_phone }}</dd>
                <dt>WhatsApp</dt><dd>{{ $customRequest->client_whatsapp ?: '-' }}</dd>
                <dt>Email</dt><dd>{{ $customRequest->client_email ?: '-' }}</dd>
                <dt>Canaux</dt><dd>{{ implode(', ', $customRequest->preferred_channels ?: []) ?: '-' }}</dd>
            </dl>
        </div>
        <div class="crq-card">
            <h3>Suivi</h3>
            <dl class="crq-dl">
                <dt>Créée par</dt><dd>{{ $customRequest->createdBy?->name ?: '-' }}</dd>
                <dt>Assignée à</dt><dd>{{ $customRequest->assignedTo?->name ?: '-' }}</dd>
                <dt>Source</dt><dd>{{ $customRequest->source ?: '-' }}</dd>
                <dt>Création</dt><dd>{{ optional($customRequest->created_at)->format('d/m/Y H:i') }}</dd>
                <dt>Devis</dt><dd>{{ $customRequest->quoted_amount ? number_format((float)$customRequest->quoted_amount, 2, ',', ' ').' '.$customRequest->currency : '-' }}</dd>
                <dt>Réservation liée</dt><dd>{{ $customRequest->convertedReservation ? '#'.$customRequest->convertedReservation->id : '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="crq-card">
        <h3>Voyageurs, dates et budget</h3>
        <dl class="crq-dl">
            <dt>Adultes</dt><dd>{{ $customRequest->adults }}</dd>
            <dt>Enfants</dt><dd>{{ count($customRequest->children ?: []) }}</dd>
            <dt>Bébés</dt><dd>{{ count($customRequest->infants ?: []) }}</dd>
            <dt>Départ</dt><dd>{{ $customRequest->departure_city_text ?: '-' }}</dd>
            <dt>Destination</dt><dd>{{ $customRequest->destination_text ?: '-' }}</dd>
            <dt>Dates</dt><dd>{{ optional($customRequest->departure_date)->format('d/m/Y') ?: '-' }} au {{ optional($customRequest->return_date)->format('d/m/Y') ?: '-' }} @if($customRequest->flexible_dates)<span class="badge bg-info">flexibles</span>@endif</dd>
            <dt>Budget</dt><dd>{{ $customRequest->budget_min ? number_format((float)$customRequest->budget_min, 0, ',', ' ') : '-' }} - {{ $customRequest->budget_max ? number_format((float)$customRequest->budget_max, 0, ',', ' ') : '-' }} {{ $customRequest->currency }}</dd>
            <dt>Note voyageurs</dt><dd>{{ $customRequest->passengers_note ?: '-' }}</dd>
        </dl>
    </div>

    <div class="crq-card">
        <h3>Services demandés</h3>
        <div class="crq-service-grid">
            @forelse(($customRequest->services ?: []) as $key => $config)
                <div class="crq-service">
                    <strong>{{ $serviceLabels[$key] ?? $key }}</strong>
                    <pre>{{ json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @empty
                <p class="mb-0 text-muted fw-bold">Aucun service sélectionné.</p>
            @endforelse
        </div>
    </div>

    <div class="crq-grid">
        <div class="crq-card"><h3>Notes client</h3><div class="crq-note">{{ $customRequest->client_notes ?: '-' }}</div></div>
        <div class="crq-card"><h3>Notes internes</h3><div class="crq-note">{{ $customRequest->internal_notes ?: '-' }}</div></div>
    </div>

    <div class="crq-card">
        <h3>Réponse commerciale / changement statut</h3>
        <form method="POST" action="{{ route('admin.reservations.custom-requests.status', $customRequest) }}" class="crq-status-form">
            @csrf @method('PATCH')
            <select name="status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected($customRequest->status === $value)>{{ $label }}</option>@endforeach</select>
            <input type="number" step="0.01" name="quoted_amount" value="{{ $customRequest->quoted_amount }}" placeholder="Montant devis">
            <textarea name="admin_response" rows="2" placeholder="Réponse commerciale">{{ $customRequest->admin_response }}</textarea>
            <button class="crq-btn crq-btn-primary" type="submit">Mettre à jour</button>
        </form>
    </div>
</div>
@endsection
