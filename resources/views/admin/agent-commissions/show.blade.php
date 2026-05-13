@extends('layouts.master-ajinsafro')

@section('title', 'Detail commission')

@push('styles')
    <style>
        .commission-detail-grid { display: grid; grid-template-columns: 1.2fr .8fr; gap: 1.25rem; }
        .commission-card { background: #fff; border: 1px solid #e5eef5; border-radius: 18px; padding: 1.25rem; box-shadow: 0 10px 26px rgba(15, 35, 95, .05); }
        .commission-card dt { color: #6b7280; font-size: .82rem; text-transform: uppercase; letter-spacing: .06em; }
        .commission-card dd { color: #0e3a5a; font-weight: 700; margin-bottom: .9rem; }
        .timeline-item { position: relative; padding-left: 1.25rem; margin-bottom: 1rem; }
        .timeline-item::before { content: ''; position: absolute; left: 0; top: .4rem; width: 8px; height: 8px; border-radius: 999px; background: #0083c4; }
        @media (max-width: 991px) { .commission-detail-grid { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
            <div>
                <h2 class="mb-1">Detail de commission</h2>
                <p class="text-muted mb-0">Reservation #{{ $entry->reservation_id }} - {{ $entry->client_name ?: 'Client non renseigne' }}</p>
            </div>
            <a href="{{ route('admin.agent.commissions.index') }}" class="btn btn-outline-secondary">Retour</a>
        </div>

        <div class="commission-detail-grid">
            <div class="commission-card">
                <h5 class="mb-4">Synthese</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Voyage</dt><dd class="col-sm-8">{{ $entry->voyage?->name ?: 'Voyage non renseigne' }}</dd>
                    <dt class="col-sm-4">Date depart</dt><dd class="col-sm-8">{{ $entry->departureDateLabel() ?: '—' }}</dd>
                    <dt class="col-sm-4">Client</dt><dd class="col-sm-8">{{ $entry->client_name ?: 'Client non renseigne' }}</dd>
                    <dt class="col-sm-4">Montant reservation</dt><dd class="col-sm-8">{{ number_format((float) $entry->reservation_total, 2, ',', ' ') }} DH</dd>
                    <dt class="col-sm-4">Commission adulte</dt><dd class="col-sm-8">{{ number_format((float) $entry->commission_adult, 2, ',', ' ') }} DH</dd>
                    <dt class="col-sm-4">Commission enfant</dt><dd class="col-sm-8">{{ number_format((float) $entry->commission_child, 2, ',', ' ') }} DH</dd>
                    <dt class="col-sm-4">Commission bebe</dt><dd class="col-sm-8">{{ number_format((float) $entry->commission_baby, 2, ',', ' ') }} DH</dd>
                    <dt class="col-sm-4">Commission totale</dt><dd class="col-sm-8">{{ number_format((float) $entry->commission_total, 2, ',', ' ') }} DH</dd>
                    <dt class="col-sm-4">Statut actuel</dt><dd class="col-sm-8">{{ $entry->statusLabelFr() }}</dd>
                    <dt class="col-sm-4">Statut paiement</dt><dd class="col-sm-8">{{ $entry->reservation?->paymentStatusLabelFr() ?? ucfirst((string) $entry->payment_status) }}</dd>
                </dl>
            </div>

            <div class="commission-card">
                <h5 class="mb-4">Historique</h5>
                @forelse($entry->logs as $log)
                    <div class="timeline-item">
                        <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</div>
                        <div class="text-muted small">{{ optional($log->created_at)->format('d/m/Y H:i') }} @if($log->creator) - {{ $log->creator->name }} @endif</div>
                        @if($log->description)
                            <div class="mt-1">{{ $log->description }}</div>
                        @endif
                        <div class="small text-muted mt-1">
                            @if($log->old_status || $log->new_status)
                                {{ $log->old_status ?: '—' }} → {{ $log->new_status ?: '—' }}
                            @endif
                            @if($log->old_amount !== null || $log->new_amount !== null)
                                | {{ number_format((float) ($log->old_amount ?? 0), 2, ',', ' ') }} DH → {{ number_format((float) ($log->new_amount ?? 0), 2, ',', ' ') }} DH
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Aucun historique disponible.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
