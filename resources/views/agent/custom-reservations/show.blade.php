@extends('layouts.master-ajinsafro')

@section('title', 'Demande à la carte '.$customRequest->request_number)

@php
    $latestQuote = $customRequest->latestQuote;
    $user = auth()->user();
    $isOfflineAgent = $user?->can('custom_requests.quote');
    $canRespondToQuote = $latestQuote && in_array($customRequest->status, ['quote_prepared', 'quote_sent', 'waiting_customer', 'modification_requested'], true);
    $serviceLabels = $customRequest->services->pluck('service_label')->filter()->implode(', ');
    $programSummary = trim(implode(' ', array_filter([
        'Programme de voyage personnalisé',
        'Type de programme : '.($travelTypeOptions[$customRequest->travel_type] ?? $customRequest->travel_type),
        'Destination : '.$customRequest->desired_destination,
        'Départ : '.$customRequest->departure_city,
        $customRequest->desired_departure_date ? 'départ le '.$customRequest->desired_departure_date->format('Y-m-d') : null,
        $customRequest->desired_return_date ? 'retour le '.$customRequest->desired_return_date->format('Y-m-d') : null,
        $customRequest->desired_duration ? 'durée '.$customRequest->desired_duration : null,
        'Voyageurs : '.$customRequest->travelers_count.' adulte(s)',
        $serviceLabels !== '' ? 'Services à intégrer : '.$serviceLabels : null,
        $customRequest->requested_services_details,
    ])));
@endphp

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .dac-agent-page { padding:0 18px 28px; display:grid; gap:14px; }
        .dac-agent-header,
        .dac-agent-card,
        .dac-agent-side-card {
            background:#fff;
            border:1px solid #dbe5f0;
            border-radius:20px;
            box-shadow:0 10px 24px rgba(15,23,42,.05);
        }
        .dac-agent-header { padding:18px 22px; display:flex; justify-content:space-between; gap:16px; align-items:flex-start; }
        .dac-agent-title { margin:0; color:#0c3656; font-size:30px; font-weight:900; line-height:1.05; }
        .dac-agent-subtitle { margin-top:6px; color:#5f748c; font-size:14px; }
        .dac-agent-badges { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
        .dac-chip {
            display:inline-flex; align-items:center; border-radius:999px; padding:6px 11px;
            font-size:12px; font-weight:800; border:1px solid transparent;
        }
        .dac-chip-blue { background:#e7f3ff; color:#0b6fb8; }
        .dac-chip-slate { background:#eef2f7; color:#42556d; }
        .dac-chip-green { background:#dcfce7; color:#1b7a3f; }
        .dac-agent-actions { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
        .dac-agent-layout { display:grid; grid-template-columns:minmax(0,1.6fr) 250px; gap:14px; align-items:start; }
        .dac-agent-stack { display:grid; gap:14px; }
        .dac-agent-card { padding:14px 16px 16px; }
        .dac-agent-side-card { padding:14px 16px; }
        .dac-agent-card h2,
        .dac-agent-side-card h2 { margin:0 0 12px; color:#0c3656; font-size:24px; font-weight:900; letter-spacing:0; }
        .dac-agent-card h3,
        .dac-agent-side-card h3 { margin:0 0 12px; color:#0c3656; font-size:20px; font-weight:900; letter-spacing:0; }
        .dac-agent-info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .dac-agent-info {
            background:#f8fbff;
            border:1px solid #dbe5f0;
            border-radius:14px;
            padding:10px 12px;
            min-height:58px;
        }
        .dac-agent-info span { display:block; color:#70819a; font-size:11px; font-weight:900; text-transform:uppercase; margin-bottom:6px; }
        .dac-agent-info strong { display:block; color:#0f172a; font-size:14px; line-height:1.35; word-break:break-word; }
        .dac-agent-note {
            margin-top:10px;
            background:#fff7ed;
            border:1px solid #fdba74;
            color:#b45309;
            border-radius:14px;
            padding:12px 14px;
            font-size:13px;
            line-height:1.35;
        }
        .dac-agent-note strong { display:block; margin-bottom:4px; color:#9a3412; }
        .dac-agent-summary-row {
            display:flex; justify-content:space-between; gap:10px; padding:10px 0;
            border-bottom:1px solid #e8eef5; align-items:flex-start;
        }
        .dac-agent-summary-row:last-child { border-bottom:0; }
        .dac-agent-summary-row span { color:#718198; font-size:12px; line-height:1.3; }
        .dac-agent-summary-row strong { color:#0f172a; font-size:14px; text-align:right; line-height:1.25; }
        .dac-agent-summary-row.total strong { color:#0b6fb8; font-size:18px; }
        .dac-agent-timeline { display:grid; gap:10px; }
        .dac-agent-timeline-item {
            background:#fbfdff; border:1px solid #e2e8f0; border-radius:14px; padding:10px 12px;
        }
        .dac-agent-timeline-item strong { display:block; color:#0c3656; font-size:13px; line-height:1.25; }
        .dac-agent-timeline-meta { margin-top:4px; color:#718198; font-size:11px; line-height:1.35; }
        .dac-agent-timeline-text { margin-top:7px; color:#0f172a; font-size:13px; line-height:1.35; }
        .dac-agent-doc {
            border:1px solid #dbe5f0; border-radius:14px; padding:10px 12px; background:#fbfdff;
        }
        .dac-agent-doc-title { color:#0f172a; font-weight:800; font-size:13px; }
        .dac-agent-doc-meta { color:#718198; font-size:11px; margin-top:4px; }
        .dac-agent-doc-actions { margin-top:10px; }
        .dac-agent-empty { color:#718198; font-size:13px; }
        .dac-agent-program-line { color:#0f172a; font-size:13px; }
        .dac-agent-quote-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
        @media (max-width: 1120px) {
            .dac-agent-layout { grid-template-columns:1fr; }
        }
        @media (max-width: 700px) {
            .dac-agent-page { padding:0 12px 24px; }
            .dac-agent-header { padding:16px; display:grid; }
            .dac-agent-card, .dac-agent-side-card { padding:14px; }
            .dac-agent-info-grid { grid-template-columns:1fr; }
            .dac-agent-title { font-size:26px; }
            .dac-agent-card h2, .dac-agent-side-card h2 { font-size:20px; }
            .dac-agent-card h3, .dac-agent-side-card h3 { font-size:18px; }
        }
    </style>
@endpush

@section('content')
<div class="dac-agent-page">
    <div class="dac-agent-header">
        <div>
            <h1 class="dac-agent-title">{{ $customRequest->request_number }}</h1>
            <div class="dac-agent-subtitle">
                {{ $customRequest->customer_full_name }} • {{ $customRequest->desired_destination }} •
                {{ $customRequest->desired_departure_date ? $customRequest->desired_departure_date->format('d/m/Y') : 'Flexible' }}
            </div>
            <div class="dac-agent-badges">
                <span class="dac-chip dac-chip-blue">{{ $customRequest->statusLabel() }}</span>
                <span class="dac-chip dac-chip-slate">{{ $customRequest->priorityLabel() }}</span>
                <span class="dac-chip dac-chip-green">{{ $customRequest->travelers_count }} voyageur(s)</span>
            </div>
        </div>
        <div class="dac-agent-actions">
            <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">Retour</a>
            @if($isOfflineAgent && !$customRequest->assigned_to)
                <form method="POST" action="{{ route('admin.custom-requests.take', $customRequest) }}">
                    @csrf
                    <button type="submit" class="aj-agent-action-btn">Prendre en charge</button>
                </form>
            @endif
            @if($isOfflineAgent && ($customRequest->assigned_to === null || (int) $customRequest->assigned_to === (int) $user?->id))
                <a href="{{ route('admin.custom-requests.quote', $customRequest) }}" class="aj-agent-primary-btn">
                    <i class="bx bx-calculator"></i>
                    <span>Ouvrir la cotation</span>
                </a>
            @elseif($latestQuote && $latestQuote->pdf_path)
                <a href="{{ route('agent.custom-reservations.quote.download', [$customRequest, $latestQuote]) }}" class="aj-agent-primary-btn">
                    <i class="bx bx-download"></i>
                    <span>Télécharger le devis</span>
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="dac-agent-layout">
        <div class="dac-agent-stack">
            <section class="dac-agent-card">
                <h3>Informations de la demande</h3>
                <div class="dac-agent-info-grid">
                    <div class="dac-agent-info">
                        <span>Téléphone</span>
                        <strong>{{ $customRequest->customer_phone ?: '-' }}</strong>
                    </div>
                    <div class="dac-agent-info">
                        <span>Email</span>
                        <strong>{{ $customRequest->customer_email ?: '-' }}</strong>
                    </div>
                    <div class="dac-agent-info">
                        <span>Ville / pays</span>
                        <strong>{{ ($customRequest->customer_city ?: '-') }} / {{ ($customRequest->customer_country ?: '-') }}</strong>
                    </div>
                    <div class="dac-agent-info">
                        <span>Départ</span>
                        <strong>{{ $customRequest->departure_city }} - {{ $customRequest->desired_departure_date ? $customRequest->desired_departure_date->format('d/m/Y') : '-' }}</strong>
                    </div>
                    <div class="dac-agent-info">
                        <span>Retour / durée</span>
                        <strong>{{ $customRequest->desired_return_date ? $customRequest->desired_return_date->format('d/m/Y') : '-' }} / {{ $customRequest->desired_duration ?: '-' }}</strong>
                    </div>
                    <div class="dac-agent-info">
                        <span>Type de voyage</span>
                        <strong>{{ $travelTypeOptions[$customRequest->travel_type] ?? $customRequest->travel_type }}</strong>
                    </div>
                    <div class="dac-agent-info">
                        <span>Agent offline</span>
                        <strong>{{ $customRequest->assignedAgent?->name ?: 'En attente de prise en charge' }}</strong>
                    </div>
                    @if($customRequest->client)
                        <div class="dac-agent-info">
                            <span>Client lié</span>
                            <strong>{{ $customRequest->client->client_code }} - {{ $customRequest->client->full_name }}</strong>
                        </div>
                    @endif
                </div>
                <div class="dac-agent-note">
                    <strong>Programme / préférences</strong>
                    <div class="dac-agent-program-line">{{ $programSummary !== '' ? $programSummary : 'Aucun détail complémentaire saisi.' }}</div>
                    @if($customRequest->customer_notes)
                        <div class="dac-agent-program-line" style="margin-top:6px;">{{ $customRequest->customer_notes }}</div>
                    @endif
                </div>
            </section>

            <section class="dac-agent-card">
                <h3>Dernier devis reçu</h3>
                @if($latestQuote)
                    <div class="dac-agent-info-grid">
                        <div class="dac-agent-info">
                            <span>Référence</span>
                            <strong>{{ $latestQuote->quote_number }} v{{ $latestQuote->version }}</strong>
                        </div>
                        <div class="dac-agent-info">
                            <span>Statut devis</span>
                            <strong>{{ $quoteStatusOptions[$latestQuote->status] ?? $latestQuote->statusLabel() }}</strong>
                        </div>
                        <div class="dac-agent-info">
                            <span>Préparé le</span>
                            <strong>{{ optional($latestQuote->prepared_at ?? $latestQuote->updated_at)->format('d/m/Y H:i') ?: '-' }}</strong>
                        </div>
                        <div class="dac-agent-info">
                            <span>Validité</span>
                            <strong>{{ $latestQuote->valid_until?->format('d/m/Y') ?: '-' }}</strong>
                        </div>
                    </div>

                    @if($latestQuote->items->isEmpty())
                        <div class="dac-agent-empty" style="margin-top:10px;">Devis de synthèse généré sans lignes tarifaires détaillées.</div>
                    @endif

                    @if($latestQuote->pdf_path)
                        <div class="dac-agent-quote-actions">
                            <a href="{{ route('agent.custom-reservations.quote.download', [$customRequest, $latestQuote]) }}" class="aj-agent-action-btn">Télécharger le PDF</a>
                            @if($isOfflineAgent && ($customRequest->assigned_to === null || (int) $customRequest->assigned_to === (int) $user?->id))
                                <a href="{{ route('admin.custom-requests.quote', $customRequest) }}" class="aj-agent-action-btn">Modifier la cotation</a>
                            @endif
                        </div>
                    @endif

                    @if(!$isOfflineAgent && $canRespondToQuote)
                        <div class="dac-agent-quote-actions">
                            @can('custom_requests.confirm')
                                <form method="POST" action="{{ route('admin.custom-requests.confirm', $customRequest) }}">
                                    @csrf
                                    <button type="submit" class="aj-agent-primary-btn">Confirmer</button>
                                </form>
                            @endcan
                            @can('custom_requests.cancel')
                                <form method="POST" action="{{ route('admin.custom-requests.cancel', $customRequest) }}">
                                    @csrf
                                    <input type="hidden" name="note" value="Demande annulée depuis l’espace agent commercial.">
                                    <button type="submit" class="aj-agent-action-btn">Annuler</button>
                                </form>
                            @endcan
                        </div>
                    @endif
                @else
                    <div class="dac-agent-empty">Aucun devis n’a encore été généré.</div>
                @endif
            </section>

            @if(!$isOfflineAgent && $latestQuote && $canRespondToQuote)
                <section class="dac-agent-card">
                    <h3>Demander une modification</h3>
                    <form method="POST" action="{{ route('admin.custom-requests.request-modification', $customRequest) }}" class="aj-agent-form">
                        @csrf
                        <textarea name="message" placeholder="Précisez les changements demandés..." required></textarea>
                        <div class="dac-agent-quote-actions">
                            <button type="submit" class="aj-agent-primary-btn">Envoyer la demande</button>
                        </div>
                    </form>
                </section>
            @endif

            <section class="dac-agent-card">
                <h3>Documents</h3>
                <div class="dac-agent-stack">
                    @forelse($customRequest->documents as $document)
                        <div class="dac-agent-doc">
                            <div class="dac-agent-doc-title">{{ $document->title ?: $document->original_name }}</div>
                            <div class="dac-agent-doc-meta">
                                {{ $document->document_type }}{{ $document->is_auto_generated ? ' • généré automatiquement' : '' }}
                            </div>
                            <div class="dac-agent-doc-actions">
                                @if($document->quote_id && $latestQuote && (int) $document->quote_id === (int) $latestQuote->id && $latestQuote->pdf_path)
                                    <a href="{{ route('agent.custom-reservations.quote.download', [$customRequest, $latestQuote]) }}" class="aj-agent-action-btn">Télécharger</a>
                                @else
                                    <a href="{{ $document->url() }}" target="_blank" class="aj-agent-action-btn">Ouvrir</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="dac-agent-empty">Aucun document rattaché à cette demande.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="dac-agent-stack">
            <section class="dac-agent-side-card">
                <h3>Résumé financier</h3>
                <div class="dac-agent-summary-row total">
                    <span>Total devis</span>
                    <strong>{{ $latestQuote ? number_format((float) $latestQuote->total_sale, 2, ',', ' ').' '.$latestQuote->currency : '0,00 MAD' }}</strong>
                </div>
                <div class="dac-agent-summary-row">
                    <span>Acompte demandé</span>
                    <strong>{{ $latestQuote && $latestQuote->requested_deposit ? number_format((float) $latestQuote->requested_deposit, 2, ',', ' ').' '.$latestQuote->currency : '-' }}</strong>
                </div>
                <div class="dac-agent-summary-row">
                    <span>Montant payé</span>
                    <strong>{{ $latestQuote ? number_format((float) $latestQuote->paid_amount, 2, ',', ' ').' '.$latestQuote->currency : '0,00 MAD' }}</strong>
                </div>
                <div class="dac-agent-summary-row">
                    <span>Reste à payer</span>
                    <strong>{{ $latestQuote ? number_format((float) $latestQuote->remaining_amount, 2, ',', ' ').' '.$latestQuote->currency : '0,00 MAD' }}</strong>
                </div>
            </section>

            <section class="dac-agent-side-card">
                <h3>Historique des statuts</h3>
                <div class="dac-agent-timeline">
                    @forelse($customRequest->statusLogs as $log)
                        <div class="dac-agent-timeline-item">
                            <strong>{{ $statusOptions[$log->old_status] ?? $log->old_status ?? 'Création' }} → {{ $statusOptions[$log->new_status] ?? $log->new_status }}</strong>
                            <div class="dac-agent-timeline-meta">{{ $log->user?->name ?: 'Système' }} • {{ $log->created_at?->format('d/m/Y H:i') }}</div>
                            @if($log->note)
                                <div class="dac-agent-timeline-text">{{ $log->note }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="dac-agent-empty">Aucun historique disponible.</div>
                    @endforelse
                </div>
            </section>

            <section class="dac-agent-side-card">
                <h3>Commentaires et échanges</h3>
                <div class="dac-agent-timeline">
                    @forelse($customRequest->comments as $comment)
                        <div class="dac-agent-timeline-item">
                            <strong>{{ $comment->user?->name ?: 'Système' }}</strong>
                            <div class="dac-agent-timeline-meta">{{ $comment->comment_type }} • {{ $comment->created_at?->format('d/m/Y H:i') }}</div>
                            <div class="dac-agent-timeline-text">{{ $comment->message }}</div>
                        </div>
                    @empty
                        <div class="dac-agent-empty">Aucun commentaire pour le moment.</div>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
