@extends('layouts.admin-v6')

@section('title', $customRequest->request_number)
@section('page_title', 'Détail demande à la carte')

@php
    $latestQuote = $customRequest->latestQuote;
    $statusBadge = match($customRequest->status) {
        'confirmed' => 'success', 'cancelled', 'refused' => 'danger',
        'missing_info', 'modification_requested' => 'warning',
        'new' => 'info', default => 'primary',
    };
@endphp

@push('styles')
<style>
    .dac-show { display:grid; gap:16px; }
    .dac-head,.dac-card { background:#fff; border:1px solid #dde7f0; border-radius:8px; padding:16px; }
    .dac-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
    .dac-head h2 { margin:0; font-size:22px; font-weight:600; color:#10233f; }
    .dac-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .dac-card h3 { margin:0 0 12px; font-size:15px; font-weight:600; color:#10233f; }
    .dac-dl { display:grid; grid-template-columns:150px 1fr; gap:8px 12px; margin:0; }
    .dac-dl dt { color:#64748b; font-size:12px; font-weight:600; }
    .dac-dl dd { margin:0; color:#20324d; }
    .dac-btn { border:0; border-radius:6px; padding:8px 11px; display:inline-flex; align-items:center; gap:6px; font-weight:600; text-decoration:none; }
    .dac-btn-primary { background:#1f6feb; color:#fff; }
    .dac-btn-soft { background:#eef3f8; color:#20324d; border:1px solid #d8e2ec; }
    .dac-btn-danger { background:#dc3545; color:#fff; }
    .dac-timeline { display:grid; gap:8px; }
    .dac-log { border-left:3px solid #d8e2ec; padding-left:10px; color:#20324d; }
    .dac-table { width:100%; border-collapse:collapse; }
    .dac-table th,.dac-table td { padding:9px; border-bottom:1px solid #edf2f7; vertical-align:top; }
    @media(max-width:900px){ .dac-head,.dac-grid{display:grid;grid-template-columns:1fr}.dac-dl{grid-template-columns:1fr} }
</style>
@endpush

@section('content')
<div class="dac-show">
    <div class="dac-head">
        <div>
            <h2>{{ $customRequest->request_number }}</h2>
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <span class="badge bg-{{ $statusBadge }}">{{ $customRequest->statusLabel() }}</span>
                <span class="badge bg-secondary">{{ $customRequest->priorityLabel() }}</span>
                <span class="badge bg-light text-dark">{{ $customRequest->currency }}</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end">
            <a href="{{ route('admin.custom-requests.index') }}" class="dac-btn dac-btn-soft"><i class="bx bx-arrow-back"></i> Retour</a>
            @if($customRequest->canBeEditedBy(auth()->user()))
                <a href="{{ route('admin.custom-requests.edit', $customRequest) }}" class="dac-btn dac-btn-soft"><i class="bx bx-edit"></i> Modifier</a>
            @endif
            @if($customRequest->canBeQuotedBy(auth()->user()) && (int) ($customRequest->assigned_to ?? 0) !== (int) auth()->id())
                <form method="POST" action="{{ route('admin.custom-requests.take', $customRequest) }}">
                    @csrf
                    <button type="submit" class="dac-btn dac-btn-primary"><i class="bx bx-user-check"></i> Prendre en charge</button>
                </form>
            @endif
            @if($customRequest->canBeQuotedBy(auth()->user()) && (int) ($customRequest->assigned_to ?? 0) === (int) auth()->id())
                <a href="{{ route('admin.custom-requests.quote', $customRequest) }}" class="dac-btn dac-btn-primary"><i class="bx bx-calculator"></i> Quotation</a>
            @endif
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success mb-0">{{ session('success') }}</div> @endif

    <div class="dac-grid">
        <section class="dac-card">
            <h3>Client</h3>
            <dl class="dac-dl">
                <dt>Nom</dt><dd>{{ $customRequest->customer_full_name }}</dd>
                <dt>Téléphone</dt><dd>{{ $customRequest->customer_phone }}</dd>
                <dt>Email</dt><dd>{{ $customRequest->customer_email ?: '-' }}</dd>
                <dt>Ville / pays</dt><dd>{{ trim(($customRequest->customer_city ?: '-').' / '.($customRequest->customer_country ?: '-')) }}</dd>
                <dt>Identité</dt><dd>{{ $customRequest->customer_identity ?: '-' }}</dd>
                <dt>Notes</dt><dd>{{ $customRequest->customer_notes ?: '-' }}</dd>
            </dl>
        </section>
        <section class="dac-card">
            <h3>Suivi</h3>
            <dl class="dac-dl">
                <dt>Créateur</dt><dd>{{ $customRequest->creator?->name ?: '-' }}</dd>
                <dt>Agent offline</dt><dd>{{ $customRequest->assignedAgent?->name ?: '-' }}</dd>
                <dt>Date limite</dt><dd>{{ $customRequest->response_deadline?->format('d/m/Y') ?: '-' }}</dd>
                <dt>Créée</dt><dd>{{ $customRequest->created_at?->format('d/m/Y H:i') }}</dd>
                <dt>Envoyé devis</dt><dd>{{ $customRequest->quote_sent_at?->format('d/m/Y H:i') ?: '-' }}</dd>
                <dt>Confirmée</dt><dd>{{ $customRequest->confirmed_at?->format('d/m/Y H:i') ?: '-' }}</dd>
            </dl>
        </section>
    </div>

    <section class="dac-card">
        <h3>Voyage</h3>
        <dl class="dac-dl">
            <dt>Destination</dt><dd>{{ $customRequest->desired_destination }}</dd>
            <dt>Départ</dt><dd>{{ $customRequest->departure_city }} le {{ $customRequest->desired_departure_date?->format('d/m/Y') }}</dd>
            <dt>Retour / durée</dt><dd>{{ $customRequest->desired_return_date?->format('d/m/Y') ?: '-' }} / {{ $customRequest->desired_duration ?: '-' }}</dd>
            <dt>Type</dt><dd>{{ $travelTypeOptions[$customRequest->travel_type] ?? $customRequest->travel_type }}</dd>
            <dt>Voyageurs</dt><dd>{{ $customRequest->travelers_count }} total, {{ $customRequest->adults_count }} adultes, {{ $customRequest->children_count }} enfants, {{ $customRequest->babies_count }} bébés</dd>
            <dt>Niveau / budget</dt><dd>{{ $customRequest->desired_level ?: '-' }} / {{ $customRequest->approximate_budget ? number_format((float)$customRequest->approximate_budget, 2, ',', ' ').' '.$customRequest->currency : '-' }}</dd>
            <dt>Services</dt><dd>{{ $customRequest->services->pluck('service_label')->implode(', ') ?: '-' }}</dd>
            <dt>Détails</dt><dd>{{ $customRequest->requested_services_details ?: '-' }}</dd>
        </dl>
    </section>

    <div class="dac-grid">
        <section class="dac-card">
            <h3>Documents</h3>
            <table class="dac-table">
                @forelse($customRequest->documents as $document)
                    <tr>
                        <td>{{ $document->title ?: $document->original_name }}<div class="text-muted small">{{ $document->document_type }} {{ $document->is_auto_generated ? '- généré automatiquement' : '' }}</div></td>
                        <td class="text-end"><a href="{{ Storage::disk('public')->url($document->file_path) }}" target="_blank" class="dac-btn dac-btn-soft">Ouvrir</a></td>
                    </tr>
                @empty
                    <tr><td class="text-muted">Aucun document.</td></tr>
                @endforelse
            </table>

            @can('custom_requests.documents')
                <form method="POST" action="{{ route('admin.custom-requests.documents.store', $customRequest) }}" enctype="multipart/form-data" class="row g-2 mt-3">
                    @csrf
                    <div class="col-md-4"><select name="document_type" class="form-select">@foreach(['identity'=>'Identité','payment_receipt'=>'Reçu paiement','tickets'=>'Billets','hotel_voucher'=>'Voucher hôtel','supplier_file'=>'Fichier fournisseur','other'=>'Autre'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="col-md-4"><input name="title" class="form-control" placeholder="Titre"></div>
                    <div class="col-md-4"><input type="file" name="document" class="form-control" required></div>
                    <div class="col-12"><button class="dac-btn dac-btn-soft" type="submit">Ajouter document</button></div>
                </form>
            @endcan
        </section>

        <section class="dac-card">
            <h3>Devis générés</h3>
            <table class="dac-table">
                @forelse($customRequest->quotes as $quote)
                    <tr>
                        <td>{{ $quote->quote_number }} v{{ $quote->version }}<div class="text-muted small">{{ $quote->statusLabel() }}</div></td>
                        <td>{{ number_format((float)$quote->total_sale, 2, ',', ' ') }} {{ $quote->currency }}</td>
                        <td class="text-end">
                            @if($quote->pdf_path)
                                <a href="{{ route('admin.custom-requests.quote.download', [$customRequest, $quote]) }}" class="dac-btn dac-btn-soft">PDF</a>
                            @endif
                            @if($quote->price_pdf_path)
                                <a href="{{ route('admin.custom-requests.quote.price.download', [$customRequest, $quote]) }}" class="dac-btn dac-btn-soft">Fiche prix</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-muted">Aucun devis.</td></tr>
                @endforelse
            </table>
        </section>
    </div>

    <div class="dac-grid">
        <section class="dac-card">
            <h3>Actions client</h3>
            @if($customRequest->status === 'draft')
                <form method="POST" action="{{ route('admin.custom-requests.submit', $customRequest) }}" class="mb-2">@csrf<button class="dac-btn dac-btn-primary">Soumettre la demande</button></form>
            @endif
            @if($latestQuote && in_array($customRequest->status, ['quote_prepared','quote_sent','waiting_customer','modification_requested'], true))
                @can('custom_requests.confirm')
                    <form method="POST" action="{{ route('admin.custom-requests.confirm', $customRequest) }}" class="d-inline">@csrf<button class="dac-btn dac-btn-primary">Confirmer</button></form>
                @endcan
                @can('custom_requests.cancel')
                    <form method="POST" action="{{ route('admin.custom-requests.cancel', $customRequest) }}" class="d-inline">@csrf<button class="dac-btn dac-btn-danger">Annuler</button></form>
                @endcan
                @can('custom_requests.edit')
                    <form method="POST" action="{{ route('admin.custom-requests.request-modification', $customRequest) }}" class="mt-3">
                        @csrf
                        <textarea name="message" class="form-control mb-2" placeholder="Modification demandée par le client" required></textarea>
                        <button class="dac-btn dac-btn-soft">Demander modification</button>
                    </form>
                @endcan
            @endif
            @can('custom_requests.assign')
                <form method="POST" action="{{ route('admin.custom-requests.assign', $customRequest) }}" class="row g-2 mt-3">
                    @csrf
                    <div class="col-md-8"><select name="assigned_to" class="form-select" required><option value="">Assigner à...</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected($customRequest->assigned_to === $agent->id)>{{ $agent->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><button class="dac-btn dac-btn-soft w-100">Assigner</button></div>
                </form>
            @endcan
        </section>

        <section class="dac-card">
            <h3>Commentaires</h3>
            <div class="dac-timeline mb-3">
                @forelse($customRequest->comments as $comment)
                    <div class="dac-log"><strong>{{ $comment->user?->name }}</strong> <span class="text-muted small">{{ $comment->comment_type }} - {{ $comment->created_at?->format('d/m/Y H:i') }}</span><br>{{ $comment->message }}</div>
                @empty
                    <div class="text-muted">Aucun commentaire.</div>
                @endforelse
            </div>
            <form method="POST" action="{{ route('admin.custom-requests.comments.store', $customRequest) }}">
                @csrf
                <select name="comment_type" class="form-select mb-2"><option value="internal">Interne</option><option value="agent_message">Message agent</option><option value="offline_message">Message offline</option><option value="missing_info">Informations manquantes</option></select>
                <textarea name="message" class="form-control mb-2" required></textarea>
                <button class="dac-btn dac-btn-soft">Ajouter commentaire</button>
            </form>
        </section>
    </div>

    <section class="dac-card">
        <h3>Historique statut</h3>
        <div class="dac-timeline">
            @foreach($customRequest->statusLogs as $log)
                <div class="dac-log">{{ $statusOptions[$log->old_status] ?? $log->old_status ?? '-' }} → {{ $statusOptions[$log->new_status] ?? $log->new_status }} <span class="text-muted small">par {{ $log->user?->name ?: 'Système' }} le {{ $log->created_at?->format('d/m/Y H:i') }}</span><br>{{ $log->note }}</div>
            @endforeach
        </div>
    </section>
</div>
@endsection
