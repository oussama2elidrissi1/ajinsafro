@extends('layouts.master-ajinsafro')

@section('title', 'Demande à la carte '.$customRequest->request_number)

@php
    $latestQuote = $customRequest->latestQuote;
    $canRespondToQuote = $latestQuote && in_array($customRequest->status, ['quote_prepared', 'quote_sent', 'waiting_customer', 'modification_requested'], true);
@endphp

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-request-show { padding:0 18px 28px; display:grid; gap:18px; }
        .aj-agent-request-head,
        .aj-agent-request-card { background:#fff; border:1px solid #e2e8f0; border-radius:20px; box-shadow:0 10px 26px rgba(15,23,42,.06); }
        .aj-agent-request-head { padding:20px 22px; display:flex; justify-content:space-between; gap:18px; align-items:flex-start; }
        .aj-agent-request-head h1 { margin:0; color:#0e3a5a; font-weight:800; font-size:30px; line-height:1.05; }
        .aj-agent-request-sub { margin-top:8px; color:#64748b; font-size:14px; }
        .aj-agent-request-badges { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
        .aj-agent-chip { display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; font-size:12px; font-weight:800; }
        .aj-agent-chip-blue { background:#e0f2fe; color:#075985; }
        .aj-agent-chip-slate { background:#eef2ff; color:#334155; }
        .aj-agent-chip-emerald { background:#dcfce7; color:#166534; }
        .aj-agent-request-grid { display:grid; grid-template-columns:minmax(0,1.55fr) minmax(320px,.9fr); gap:18px; align-items:start; }
        .aj-agent-request-stack { display:grid; gap:18px; }
        .aj-agent-request-card { padding:20px 22px; }
        .aj-agent-request-card h2 { margin:0 0 14px; color:#0e3a5a; font-size:18px; font-weight:800; }
        .aj-agent-info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .aj-agent-info-item { background:#f8fbff; border:1px solid #e2e8f0; border-radius:14px; padding:12px 14px; }
        .aj-agent-info-item span { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; margin-bottom:6px; }
        .aj-agent-info-item strong { display:block; color:#0f172a; font-size:14px; line-height:1.45; }
        .aj-agent-note { margin-top:12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; border-radius:14px; padding:14px; font-size:14px; }
        .aj-agent-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .aj-agent-actions form { margin:0; }
        .aj-agent-quote-table { width:100%; border-collapse:collapse; }
        .aj-agent-quote-table th,
        .aj-agent-quote-table td { padding:10px 12px; border-bottom:1px solid #e2e8f0; vertical-align:top; text-align:left; }
        .aj-agent-quote-table th { color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; }
        .aj-agent-quote-table td { color:#0f172a; font-size:13px; }
        .aj-agent-finance { display:grid; gap:10px; }
        .aj-agent-finance-row { display:flex; justify-content:space-between; gap:12px; padding:10px 0; border-bottom:1px solid #e2e8f0; }
        .aj-agent-finance-row span { color:#64748b; }
        .aj-agent-finance-row strong { color:#0f172a; font-size:16px; }
        .aj-agent-finance-row.is-main strong { color:#0b7ad1; font-size:22px; }
        .aj-agent-doc-list,
        .aj-agent-timeline { display:grid; gap:10px; }
        .aj-agent-doc-item,
        .aj-agent-timeline-item { border:1px solid #e2e8f0; border-radius:14px; padding:12px 14px; background:#fbfdff; }
        .aj-agent-doc-title { color:#0f172a; font-weight:700; }
        .aj-agent-doc-meta,
        .aj-agent-timeline-meta { color:#64748b; font-size:12px; margin-top:4px; }
        .aj-agent-timeline-item strong { color:#0e3a5a; }
        .aj-agent-form textarea { width:100%; min-height:120px; border:1px solid #dbe4ef; border-radius:14px; padding:12px 14px; resize:vertical; }
        .aj-agent-form small { display:block; color:#64748b; margin-top:8px; }
        .aj-agent-empty-block { color:#64748b; font-size:14px; }
        @media (max-width: 1100px) {
            .aj-agent-request-grid { grid-template-columns:1fr; }
        }
        @media (max-width: 700px) {
            .aj-agent-request-show { padding:0 12px 22px; }
            .aj-agent-request-head { padding:18px; display:grid; }
            .aj-agent-request-card { padding:18px; }
            .aj-agent-info-grid { grid-template-columns:1fr; }
        }
    </style>
@endpush

@section('content')
<div class="aj-agent-request-show">
    <div class="aj-agent-request-head">
        <div>
            <h1>{{ $customRequest->request_number }}</h1>
            <div class="aj-agent-request-sub">
                {{ $customRequest->customer_full_name }} • {{ $customRequest->desired_destination }} •
                {{ $customRequest->desired_departure_date ? $customRequest->desired_departure_date->format('d/m/Y') : 'Date flexible' }}
            </div>
            <div class="aj-agent-request-badges">
                <span class="aj-agent-chip aj-agent-chip-blue">{{ $customRequest->statusLabel() }}</span>
                <span class="aj-agent-chip aj-agent-chip-slate">{{ $customRequest->priorityLabel() }}</span>
                <span class="aj-agent-chip aj-agent-chip-emerald">{{ $customRequest->travelers_count }} voyageur(s)</span>
            </div>
        </div>
        <div class="aj-agent-actions">
            <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn">Retour</a>
            @if($latestQuote && $latestQuote->pdf_path)
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

    <div class="aj-agent-request-grid">
        <div class="aj-agent-request-stack">
            <section class="aj-agent-request-card">
                <h2>Informations de la demande</h2>
                <div class="aj-agent-info-grid">
                    @if($customRequest->client)
                        <div class="aj-agent-info-item">
                            <span>Client lié</span>
                            <strong>{{ $customRequest->client->client_code }} - {{ $customRequest->client->full_name }}</strong>
                        </div>
                    @endif
                    <div class="aj-agent-info-item">
                        <span>Téléphone</span>
                        <strong>{{ $customRequest->customer_phone ?: '-' }}</strong>
                    </div>
                    <div class="aj-agent-info-item">
                        <span>Email</span>
                        <strong>{{ $customRequest->customer_email ?: '-' }}</strong>
                    </div>
                    <div class="aj-agent-info-item">
                        <span>Ville / pays</span>
                        <strong>{{ ($customRequest->customer_city ?: '-') }} / {{ ($customRequest->customer_country ?: '-') }}</strong>
                    </div>
                    <div class="aj-agent-info-item">
                        <span>Départ</span>
                        <strong>{{ $customRequest->departure_city }} - {{ $customRequest->desired_departure_date ? $customRequest->desired_departure_date->format('d/m/Y') : 'Flexible' }}</strong>
                    </div>
                    <div class="aj-agent-info-item">
                        <span>Retour / durée</span>
                        <strong>{{ $customRequest->desired_return_date ? $customRequest->desired_return_date->format('d/m/Y') : '-' }} / {{ $customRequest->desired_duration ?: '-' }}</strong>
                    </div>
                    <div class="aj-agent-info-item">
                        <span>Type de voyage</span>
                        <strong>{{ $travelTypeOptions[$customRequest->travel_type] ?? $customRequest->travel_type }}</strong>
                    </div>
                    <div class="aj-agent-info-item">
                        <span>Agent offline</span>
                        <strong>{{ $customRequest->assignedAgent?->name ?: 'En attente de prise en charge' }}</strong>
                    </div>
                </div>
                <div class="aj-agent-note">
                    <strong>Programme / préférences</strong><br>
                    {{ $customRequest->requested_services_details ?: 'Aucun détail complémentaire saisi.' }}
                </div>
                @if($customRequest->customer_notes)
                    <div class="aj-agent-note">
                        <strong>Contraintes / remarques client</strong><br>
                        {{ $customRequest->customer_notes }}
                    </div>
                @endif
            </section>

            <section class="aj-agent-request-card">
                <h2>Dernier devis reçu</h2>
                @if($latestQuote)
                    <div class="aj-agent-info-grid" style="margin-bottom:14px;">
                        <div class="aj-agent-info-item">
                            <span>Référence</span>
                            <strong>{{ $latestQuote->quote_number }} v{{ $latestQuote->version }}</strong>
                        </div>
                        <div class="aj-agent-info-item">
                            <span>Statut devis</span>
                            <strong>{{ $quoteStatusOptions[$latestQuote->status] ?? $latestQuote->statusLabel() }}</strong>
                        </div>
                        <div class="aj-agent-info-item">
                            <span>Préparé le</span>
                            <strong>{{ optional($latestQuote->prepared_at ?? $latestQuote->updated_at)->format('d/m/Y H:i') ?: '-' }}</strong>
                        </div>
                        <div class="aj-agent-info-item">
                            <span>Validité</span>
                            <strong>{{ $latestQuote->valid_until?->format('d/m/Y') ?: '-' }}</strong>
                        </div>
                    </div>

                    @if($latestQuote->items->count())
                        <div style="overflow:auto;">
                            <table class="aj-agent-quote-table">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Description</th>
                                        <th>Qté</th>
                                        <th>Prix unitaire</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($latestQuote->items as $item)
                                        <tr>
                                            <td>{{ \App\Models\CustomRequestQuote::itemServiceOptions()[$item->service_type] ?? $item->service_type }}</td>
                                            <td>{{ $item->description }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ number_format((float) $item->unit_sale_price, 2, ',', ' ') }} {{ $latestQuote->currency }}</td>
                                            <td>{{ number_format((float) $item->total_sale, 2, ',', ' ') }} {{ $latestQuote->currency }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="aj-agent-empty-block">Devis de synthèse généré sans lignes tarifaires détaillées.</div>
                    @endif

                    @if($canRespondToQuote)
                        <div class="aj-agent-actions" style="margin-top:16px;">
                            @can('custom_requests.confirm')
                                <form method="POST" action="{{ route('admin.custom-requests.confirm', $customRequest) }}">
                                    @csrf
                                    <button type="submit" class="aj-agent-primary-btn">
                                        <i class="bx bx-check-circle"></i>
                                        <span>Confirmer</span>
                                    </button>
                                </form>
                            @endcan

                            @can('custom_requests.cancel')
                                <form method="POST" action="{{ route('admin.custom-requests.cancel', $customRequest) }}">
                                    @csrf
                                    <input type="hidden" name="note" value="Demande annulée depuis l’espace agent commercial.">
                                    <button type="submit" class="aj-agent-action-btn" style="border-color:#fecaca;color:#b91c1c;">
                                        <i class="bx bx-x-circle"></i>
                                        <span>Annuler</span>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    @endif
                @else
                    <div class="aj-agent-empty-block">Aucun devis n’a encore été généré par l’agent offline.</div>
                @endif
            </section>

            @if($latestQuote && $canRespondToQuote)
                <section class="aj-agent-request-card">
                    <h2>Demander une modification</h2>
                    <form method="POST" action="{{ route('admin.custom-requests.request-modification', $customRequest) }}" class="aj-agent-form">
                        @csrf
                        <textarea name="message" placeholder="Précisez les changements demandés sur le devis..." required></textarea>
                        <small>Cette action notifie l’agent offline et historise la demande.</small>
                        <div class="aj-agent-actions" style="margin-top:12px;">
                            <button type="submit" class="aj-agent-primary-btn">
                                <i class="bx bx-revision"></i>
                                <span>Envoyer la demande de modification</span>
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            <section class="aj-agent-request-card">
                <h2>Documents</h2>
                <div class="aj-agent-doc-list">
                    @forelse($customRequest->documents as $document)
                        <div class="aj-agent-doc-item">
                            <div class="aj-agent-doc-title">{{ $document->title ?: $document->original_name }}</div>
                            <div class="aj-agent-doc-meta">
                                {{ $document->document_type }}{{ $document->is_auto_generated ? ' • généré automatiquement' : '' }}
                            </div>
                            <div class="aj-agent-actions" style="margin-top:10px;">
                                @if($document->quote_id && $latestQuote && (int) $document->quote_id === (int) $latestQuote->id && $latestQuote->pdf_path)
                                    <a href="{{ route('agent.custom-reservations.quote.download', [$customRequest, $latestQuote]) }}" class="aj-agent-action-btn">Télécharger</a>
                                @else
                                    <a href="{{ $document->url() }}" target="_blank" class="aj-agent-action-btn">Ouvrir</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="aj-agent-empty-block">Aucun document rattaché à cette demande.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="aj-agent-request-stack">
            <section class="aj-agent-request-card">
                <h2>Résumé financier</h2>
                @if($latestQuote)
                    <div class="aj-agent-finance">
                        <div class="aj-agent-finance-row is-main">
                            <span>Total devis</span>
                            <strong>{{ number_format((float) $latestQuote->total_sale, 2, ',', ' ') }} {{ $latestQuote->currency }}</strong>
                        </div>
                        <div class="aj-agent-finance-row">
                            <span>Acompte demandé</span>
                            <strong>{{ $latestQuote->requested_deposit ? number_format((float) $latestQuote->requested_deposit, 2, ',', ' ').' '.$latestQuote->currency : '-' }}</strong>
                        </div>
                        <div class="aj-agent-finance-row">
                            <span>Montant payé</span>
                            <strong>{{ number_format((float) $latestQuote->paid_amount, 2, ',', ' ') }} {{ $latestQuote->currency }}</strong>
                        </div>
                        <div class="aj-agent-finance-row">
                            <span>Reste à payer</span>
                            <strong>{{ number_format((float) $latestQuote->remaining_amount, 2, ',', ' ') }} {{ $latestQuote->currency }}</strong>
                        </div>
                    </div>
                @else
                    <div class="aj-agent-empty-block">Le résumé financier apparaîtra dès qu’un devis sera généré.</div>
                @endif
            </section>

            <section class="aj-agent-request-card">
                <h2>Historique des statuts</h2>
                <div class="aj-agent-timeline">
                    @forelse($customRequest->statusLogs as $log)
                        <div class="aj-agent-timeline-item">
                            <strong>{{ $statusOptions[$log->old_status] ?? $log->old_status ?? 'Création' }} → {{ $statusOptions[$log->new_status] ?? $log->new_status }}</strong>
                            <div class="aj-agent-timeline-meta">{{ $log->user?->name ?: 'Système' }} • {{ $log->created_at?->format('d/m/Y H:i') }}</div>
                            @if($log->note)
                                <div style="margin-top:8px;color:#0f172a;">{{ $log->note }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="aj-agent-empty-block">Aucun historique disponible.</div>
                    @endforelse
                </div>
            </section>

            <section class="aj-agent-request-card">
                <h2>Commentaires et échanges</h2>
                <div class="aj-agent-timeline">
                    @forelse($customRequest->comments as $comment)
                        <div class="aj-agent-timeline-item">
                            <strong>{{ $comment->user?->name ?: 'Système' }}</strong>
                            <div class="aj-agent-timeline-meta">{{ $comment->comment_type }} • {{ $comment->created_at?->format('d/m/Y H:i') }}</div>
                            <div style="margin-top:8px;color:#0f172a;">{{ $comment->message }}</div>
                        </div>
                    @empty
                        <div class="aj-agent-empty-block">Aucun commentaire pour le moment.</div>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
