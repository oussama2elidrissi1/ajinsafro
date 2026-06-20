@php
    $route = fn (string $key, array $params = []) => route($quoteRoutes[$key] ?? 'admin.custom-requests.'.$key, $params);
    $serviceFields = [
        'flight' => [
            'airline' => 'Compagnie aérienne', 'flight_number' => 'Numéro de vol', 'from' => 'Départ de', 'to' => 'Arrivée à',
            'departure_airport' => 'Aéroport départ', 'arrival_airport' => 'Aéroport arrivée', 'departure_date' => 'Date départ',
            'departure_time' => 'Heure départ', 'arrival_time' => 'Heure arrivée', 'baggage' => 'Bagage inclus', 'class' => 'Classe',
        ],
        'hotel' => [
            'hotel_name' => 'Nom hôtel', 'city' => 'Ville', 'stars' => 'Catégorie / étoiles', 'address' => 'Adresse',
            'room_type' => 'Type de chambre', 'board' => 'Pension', 'check_in' => 'Check-in', 'check_out' => 'Check-out',
            'nights' => 'Nombre de nuits', 'rooms' => 'Nombre de chambres', 'travelers' => 'Nombre de voyageurs',
        ],
        'transfer' => [
            'transfer_type' => 'Type de transfert', 'from' => 'Départ', 'to' => 'Arrivée', 'date' => 'Date',
            'time' => 'Heure', 'vehicle' => 'Véhicule', 'people' => 'Nombre de personnes',
        ],
        'activity' => [
            'name' => 'Nom activité', 'city' => 'Ville', 'date' => 'Date', 'start_time' => 'Heure début',
            'duration' => 'Durée', 'included' => 'Inclus / optionnel', 'people' => 'Nombre de personnes',
        ],
        'excursion' => [
            'name' => 'Nom excursion', 'city' => 'Ville', 'date' => 'Date', 'start_time' => 'Heure début',
            'duration' => 'Durée', 'included' => 'Inclus / optionnel', 'people' => 'Nombre de personnes',
        ],
        'guide' => [
            'language' => 'Langue du guide', 'city' => 'Ville', 'date' => 'Date', 'duration' => 'Durée', 'people' => 'Nombre de personnes',
        ],
        'visa' => ['name' => 'Nom du service', 'description' => 'Description', 'quantity' => 'Quantité'],
        'catering' => ['name' => 'Nom du service', 'description' => 'Description', 'quantity' => 'Quantité'],
        'insurance' => ['name' => 'Nom du service', 'description' => 'Description', 'quantity' => 'Quantité'],
        'transport' => ['name' => 'Nom du service', 'description' => 'Description', 'quantity' => 'Quantité'],
        'other' => ['name' => 'Nom du service', 'description' => 'Description', 'quantity' => 'Quantité'],
    ];

    $programDays = old('days');
    if ($programDays === null) {
        if ($quote->days->isNotEmpty()) {
            $programDays = $quote->days->map(function ($day) {
                return [
                    'day_number' => $day->day_number,
                    'id' => $day->id,
                    'date' => optional($day->date)->toDateString(),
                    'title' => $day->title,
                    'city' => $day->city,
                    'client_description' => $day->client_description,
                    'internal_notes' => $day->internal_notes,
                    'sort_order' => $day->sort_order,
                    'services' => $day->services->map(fn ($item) => $item->toArray())->all(),
                ];
            })->all();
        } else {
            $legacyItems = $quote->items->map(fn ($item) => $item->toArray())->all();
            $programDays = [[
                'day_number' => 1,
                'date' => optional($customRequest->desired_departure_date)->toDateString(),
                'title' => $customRequest->desired_destination ? 'Arrivée à '.$customRequest->desired_destination : 'Début du voyage',
                'city' => $customRequest->desired_destination,
                'client_description' => $customRequest->requested_services_details,
                'internal_notes' => null,
                'sort_order' => 0,
                'services' => $legacyItems ?: [[
                    'service_type' => 'hotel', 'title' => '', 'description' => '', 'supplier_name' => '',
                    'quantity' => 1, 'unit_purchase_price' => 0, 'margin_type' => 'amount', 'margin_value' => 0,
                    'unit_sale_price' => 0, 'is_optional' => false, 'data_json' => [],
                ]],
            ]];
        }
    }

    $returnDate = optional($customRequest->desired_return_date)->toDateString();
@endphp

@extends($quoteLayout ?? 'layouts.admin-v6')

@section('title', 'Cotation '.$customRequest->request_number)
@section('page_title', 'Cotation demande à la carte')

@push('styles')
@if(($quoteLayout ?? '') === 'layouts.master-ajinsafro')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endif
<style>
    .partner-v2 .aj-topbar__brand-logo-wrap { width:96px !important; height:30px !important; max-width:96px !important; padding:4px 7px !important; overflow:hidden !important; }
    .partner-v2 .aj-topbar__brand-logo { display:block !important; width:auto !important; height:auto !important; max-width:100% !important; max-height:22px !important; object-fit:contain !important; }
    .partner-v2 .agent-portal-main { padding:0 18px 32px !important; }
    .partner-v2 .agent-portal-main > .quote-page { max-width:1680px; margin:0 auto; }
    .partner-v2 aside.w-full { width:18rem !important; max-width:18rem !important; flex:0 0 18rem !important; }
    .partner-v2 aside .sticky { top:72px !important; }
    .quote-page { display:grid; gap:14px; }
    .quote-head,.quote-card { background:#fff; border:1px solid #d9e5f2; border-radius:10px; padding:16px; box-shadow:0 8px 22px rgba(15,23,42,.05); }
    .quote-head { display:flex; justify-content:space-between; align-items:flex-start; gap:14px; }
    .quote-head h2 { margin:0; font-size:20px; font-weight:700; color:#10233f; }
    .quote-meta { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
    .quote-layout { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:14px; align-items:start; }
    .quote-stack { display:grid; gap:14px; }
    .quote-side { position:sticky; top:86px; display:grid; gap:14px; }
    .quote-card h3 { margin:0 0 12px; font-size:16px; font-weight:700; color:#10233f; }
    .quote-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:11px; }
    .quote-grid-3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:11px; }
    .quote-field label { display:block; font-size:12px; color:#5b6b82; font-weight:700; margin-bottom:5px; }
    .quote-field input,.quote-field select,.quote-field textarea { width:100%; min-height:38px; border:1px solid #d7e2ee; border-radius:8px; padding:8px 9px; background:#fff; color:#14243c; }
    .quote-field textarea { min-height:76px; resize:vertical; }
    .quote-field.full { grid-column:1 / -1; }
    .quote-btn { border:0; border-radius:8px; padding:8px 11px; display:inline-flex; align-items:center; justify-content:center; gap:6px; font-weight:700; text-decoration:none; line-height:1.2; cursor:pointer; }
    .quote-btn-primary { background:#008bd2; color:#fff; box-shadow:0 8px 18px rgba(0,139,210,.22); }
    .quote-btn-soft { background:#eef5fb; color:#1f334f; border:1px solid #d7e2ee; }
    .quote-btn-danger { background:#dc3545; color:#fff; }
    .quote-day { border:1px solid #d9e5f2; border-radius:10px; padding:14px; background:#fbfdff; display:grid; gap:12px; }
    .quote-day-head { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .quote-day-title { font-weight:800; color:#10233f; }
    .quote-day-date { color:#5b6b82; font-weight:700; margin-left:6px; }
    .quote-day-toggle { width:34px; height:34px; padding:0; }
    .quote-day-body { display:grid; gap:12px; }
    .quote-day.is-collapsed .quote-day-body { display:none; }
    .quote-day.is-collapsed .quote-day-toggle i { transform:rotate(-90deg); }
    .quote-program-toolbar { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
    .quote-return-note { border:1px dashed #b8cbe0; background:#f7fbff; border-radius:10px; padding:10px 12px; color:#20324d; font-weight:700; }
    .quote-service { border:1px solid #dbe7f3; border-radius:10px; background:#fff; padding:12px; display:grid; gap:11px; }
    .quote-service-head { display:grid; grid-template-columns:180px minmax(180px,1fr) 110px auto; gap:10px; align-items:end; }
    .quote-extra-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .quote-money-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:10px; }
    .quote-summary { display:grid; gap:8px; }
    .quote-summary-row { display:flex; justify-content:space-between; gap:12px; border-bottom:1px solid #edf2f7; padding-bottom:8px; color:#20324d; }
    .quote-summary-row strong { font-weight:800; text-align:right; }
    .quote-history { display:grid; gap:8px; max-height:310px; overflow:auto; }
    .quote-log { border-left:3px solid #c8d7e8; padding-left:10px; color:#20324d; font-size:12px; }
    .partner-v2 .quote-page .badge { display:inline-flex; align-items:center; border-radius:999px; padding:5px 9px; font-size:11px; line-height:1; }
    @media(max-width:1400px){ .quote-layout{grid-template-columns:1fr}.quote-side{position:static}.quote-grid{grid-template-columns:repeat(2,1fr)}.quote-money-grid,.quote-extra-grid{grid-template-columns:repeat(2,1fr)} }
    @media(max-width:720px){ .quote-head,.quote-day-head{display:grid}.quote-grid,.quote-grid-3,.quote-service-head,.quote-money-grid,.quote-extra-grid{grid-template-columns:1fr}.quote-card{padding:14px} }
</style>
@endpush

@section('content')
<div class="quote-page">
    <div class="quote-head">
        <div>
            <h2>Cotation demande à la carte</h2>
            <div class="text-muted">{{ $customRequest->request_number }} - {{ $customRequest->customer_full_name }}</div>
            <div class="quote-meta">
                <span class="badge bg-primary">{{ $customRequest->statusLabel() }}</span>
                <span class="badge bg-secondary">{{ $customRequest->priorityLabel() }}</span>
                <span class="badge bg-light text-dark">Agent créateur : {{ $customRequest->creator?->name ?: '-' }}</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end">
            <a href="{{ route($quoteRoutes['show'] ?? 'admin.custom-requests.show', $customRequest) }}" class="quote-btn quote-btn-soft"><i class="bx bx-arrow-back"></i> Retour</a>
            @if(!$customRequest->assigned_to)
                <form method="POST" action="{{ route($quoteRoutes['take'] ?? 'admin.custom-requests.take', $customRequest) }}">@csrf<button class="quote-btn quote-btn-soft">Prendre en charge</button></form>
            @endif
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success mb-0">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger mb-0">Veuillez corriger les champs indiqués avant de continuer.</div> @endif

    <form method="POST" action="{{ route($quoteRoutes['store'] ?? 'admin.custom-requests.quote.store', $customRequest) }}" id="quoteForm" class="quote-layout">
        @csrf
        <div class="quote-stack">
            <section class="quote-card">
                <h3>Informations de cotation</h3>
                <div class="quote-grid">
                    <div class="quote-field"><label>Agent responsable</label><input value="{{ $quote->offlineAgent?->name ?: $customRequest->assignedAgent?->name ?: auth()->user()->name }}" readonly></div>
                    <div class="quote-field"><label>Fournisseur principal</label><input name="supplier_name" value="{{ old('supplier_name', $quote->supplier_name) }}"></div>
                    <div class="quote-field"><label>Statut de la cotation</label><input value="{{ $quote->statusLabel() }}" readonly></div>
                    <div class="quote-field"><label>Devise</label><select name="currency">@foreach(['MAD','EUR','USD'] as $currency)<option value="{{ $currency }}" @selected(old('currency', $quote->currency) === $currency)>{{ $currency }}</option>@endforeach</select></div>
                    <div class="quote-field"><label>Validité du devis</label><input type="date" name="valid_until" value="{{ old('valid_until', optional($quote->valid_until)->toDateString()) }}"></div>
                    <div class="quote-field"><label>Date limite de réponse</label><input type="date" name="response_deadline" value="{{ old('response_deadline', optional($quote->response_deadline)->toDateString()) }}"></div>
                    <div class="quote-field"><label>Acompte demandé</label><input type="number" step="0.01" min="0" name="requested_deposit" data-deposit value="{{ old('requested_deposit', $quote->requested_deposit) }}"></div>
                    <div class="quote-field"><label>Montant payé</label><input type="number" step="0.01" min="0" name="paid_amount" data-paid value="{{ old('paid_amount', $quote->paid_amount ?? 0) }}"></div>
                </div>
            </section>

            <section class="quote-card">
                <div class="quote-program-toolbar">
                    <div>
                        <h3 class="mb-0">Programme du voyage</h3>
                        <div class="text-muted small">{{ count($programDays) }} jour(s) généré(s) selon la durée de la demande.</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" name="sync_program_days" value="1" class="quote-btn quote-btn-primary"><i class="bx bx-refresh"></i> Synchroniser les jours avec la durée</button>
                        <button type="button" class="quote-btn quote-btn-soft" data-open-days>Tout ouvrir</button>
                        <button type="button" class="quote-btn quote-btn-soft" data-close-days>Tout fermer</button>
                        <button type="button" class="quote-btn quote-btn-soft" id="addQuoteDay"><i class="bx bx-plus"></i> Ajouter un jour extra</button>
                    </div>
                </div>
                <div id="quoteDays" class="quote-stack">
                    @foreach($programDays as $dayIndex => $day)
                        <article class="quote-day {{ $loop->first ? '' : 'is-collapsed' }}" data-day>
                            <div class="quote-day-head">
                                <div class="quote-day-title">
                                    Jour <span data-day-label>{{ $day['day_number'] ?? $loop->iteration }}</span>
                                    @if(!empty($day['date']))
                                        <span class="quote-day-date">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="quote-btn quote-btn-soft" data-add-service><i class="bx bx-plus"></i> Ajouter un service</button>
                                    <button type="button" class="quote-btn quote-btn-soft quote-day-toggle" data-toggle-day aria-label="Ouvrir ou fermer"><i class="bx bx-chevron-down"></i></button>
                                    <button type="button" class="quote-btn quote-btn-danger" data-remove-day><i class="bx bx-trash"></i></button>
                                </div>
                            </div>
                            <div class="quote-day-body">
                            <div class="quote-grid">
                                <input type="hidden" name="days[{{ $dayIndex }}][id]" value="{{ $day['id'] ?? '' }}" data-day-id>
                                <div class="quote-field"><label>Numéro du jour</label><input type="number" min="1" name="days[{{ $dayIndex }}][day_number]" data-day-number value="{{ $day['day_number'] ?? $loop->iteration }}"></div>
                                <div class="quote-field"><label>Date du jour</label><input type="date" name="days[{{ $dayIndex }}][date]" value="{{ $day['date'] ?? '' }}"></div>
                                <div class="quote-field"><label>Titre du jour</label><input name="days[{{ $dayIndex }}][title]" value="{{ $day['title'] ?? '' }}"></div>
                                <div class="quote-field"><label>Ville / destination</label><input name="days[{{ $dayIndex }}][city]" value="{{ $day['city'] ?? '' }}"></div>
                                <div class="quote-field full"><label>Description client</label><textarea name="days[{{ $dayIndex }}][client_description]">{{ $day['client_description'] ?? '' }}</textarea></div>
                                <div class="quote-field full"><label>Notes internes</label><textarea name="days[{{ $dayIndex }}][internal_notes]">{{ $day['internal_notes'] ?? '' }}</textarea></div>
                                <input type="hidden" name="days[{{ $dayIndex }}][sort_order]" value="{{ $day['sort_order'] ?? $dayIndex }}" data-day-sort>
                            </div>
                            <div class="quote-stack" data-services>
                                @foreach(($day['services'] ?? []) as $serviceIndex => $service)
                                    @php
                                        $serviceType = $service['service_type'] ?? 'hotel';
                                        $dataJson = is_array($service['data_json'] ?? null) ? $service['data_json'] : [];
                                    @endphp
                                    <div class="quote-service" data-service>
                                        <div class="quote-service-head">
                                            <div class="quote-field"><label>Type de service</label><select name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][service_type]" data-service-type>@foreach($serviceTypeOptions as $key=>$label)<option value="{{ $key }}" @selected($serviceType === $key)>{{ $label }}</option>@endforeach</select></div>
                                            <div class="quote-field"><label>Titre</label><input name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][title]" value="{{ $service['title'] ?? '' }}"></div>
                                            <div class="quote-field"><label>Optionnel</label><select name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][is_optional]"><option value="0" @selected(empty($service['is_optional']))>Inclus</option><option value="1" @selected(!empty($service['is_optional']))>Optionnel</option></select></div>
                                            <button type="button" class="quote-btn quote-btn-danger" data-remove-service><i class="bx bx-trash"></i></button>
                                        </div>
                                        <div class="quote-field"><label>Description pour le devis</label><textarea name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][description]">{{ $service['description'] ?? '' }}</textarea></div>
                                        <div class="quote-extra-grid" data-extra-container>
                                            @foreach(($serviceFields[$serviceType] ?? $serviceFields['other']) as $fieldKey => $fieldLabel)
                                                <div class="quote-field"><label>{{ $fieldLabel }}</label><input name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][data_json][{{ $fieldKey }}]" value="{{ $dataJson[$fieldKey] ?? '' }}"></div>
                                            @endforeach
                                        </div>
                                        <div class="quote-money-grid">
                                            <div class="quote-field"><label>Fournisseur</label><input name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][supplier_name]" value="{{ $service['supplier_name'] ?? '' }}"></div>
                                            <div class="quote-field"><label>Quantité</label><input type="number" min="1" name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][quantity]" data-qty value="{{ $service['quantity'] ?? 1 }}"></div>
                                            <div class="quote-field"><label>Prix achat</label><input type="number" step="0.01" min="0" name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][unit_purchase_price]" data-purchase value="{{ $service['unit_purchase_price'] ?? 0 }}"></div>
                                            <div class="quote-field"><label>Type marge</label><select name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][margin_type]" data-margin-type><option value="amount" @selected(($service['margin_type'] ?? 'amount') === 'amount')>Montant</option><option value="percent" @selected(($service['margin_type'] ?? 'amount') === 'percent')>%</option></select></div>
                                            <div class="quote-field"><label>Marge</label><input type="number" step="0.01" min="0" name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][margin_value]" data-margin value="{{ $service['margin_value'] ?? $service['unit_margin'] ?? 0 }}"></div>
                                            <div class="quote-field"><label>Prix vente U.</label><input type="number" step="0.01" min="0" name="days[{{ $dayIndex }}][services][{{ $serviceIndex }}][unit_sale_price]" data-sale readonly value="{{ $service['unit_sale_price'] ?? 0 }}"></div>
                                            <div class="quote-field"><label>Total vente</label><input data-line-total readonly value="0.00"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                @if($returnDate)
                    <div class="quote-return-note mt-3">Retour — {{ \Illuminate\Support\Carbon::parse($returnDate)->format('d/m/Y') }}</div>
                @endif
            </section>

            <section class="quote-card">
                <h3>Conditions et notes</h3>
                <div class="quote-grid">
                    <div class="quote-field full"><label>Conditions client</label><textarea name="customer_conditions">{{ old('customer_conditions', $quote->customer_conditions) }}</textarea></div>
                    <div class="quote-field full"><label>Notes internes</label><textarea name="internal_notes">{{ old('internal_notes', $quote->internal_notes) }}</textarea></div>
                </div>
            </section>

            <section class="quote-card">
                <h3>Documents</h3>
                <div class="d-flex gap-2 flex-wrap">
                    @if($quote->pdf_path)
                        <a href="{{ route($quoteRoutes['download'] ?? 'admin.custom-requests.quote.download', [$customRequest, $quote]) }}" class="quote-btn quote-btn-soft"><i class="bx bx-download"></i> Télécharger le devis</a>
                    @endif
                    <button type="submit" class="quote-btn quote-btn-soft"><i class="bx bx-save"></i> Sauvegarder le brouillon</button>
                    <button type="submit" formaction="{{ route($quoteRoutes['prepare'] ?? 'admin.custom-requests.quote.prepare', [$customRequest, $quote]) }}" class="quote-btn quote-btn-primary"><i class="bx bx-file"></i> Générer un devis automatique</button>
                </div>
            </section>
        </div>

        <aside class="quote-side">
            <section class="quote-card">
                <h3>Résumé financier</h3>
                <div class="quote-summary">
                    <div class="quote-summary-row"><span>Total achat</span><strong id="totalPurchase">0.00</strong></div>
                    <div class="quote-summary-row"><span>Total marge</span><strong id="totalMargin">0.00</strong></div>
                    <div class="quote-summary-row"><span>Total vente</span><strong id="totalSale">0.00</strong></div>
                    <div class="quote-summary-row"><span>Acompte demandé</span><strong id="deposit">0.00</strong></div>
                    <div class="quote-summary-row"><span>Montant payé</span><strong id="paid">0.00</strong></div>
                    <div class="quote-summary-row"><span>Reste à payer</span><strong id="remaining">0.00</strong></div>
                </div>
                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="quote-btn quote-btn-soft">Sauvegarder le brouillon</button>
                    <button type="submit" formaction="{{ route($quoteRoutes['prepare'] ?? 'admin.custom-requests.quote.prepare', [$customRequest, $quote]) }}" class="quote-btn quote-btn-primary">Générer le PDF</button>
                    @if($quote->pdf_path)
                        <button type="submit" formaction="{{ route($quoteRoutes['send'] ?? 'admin.custom-requests.quote.send', [$customRequest, $quote]) }}" class="quote-btn quote-btn-primary">Envoyer à l’agent créateur</button>
                    @endif
                </div>
            </section>

            <section class="quote-card">
                <h3>Résumé demande</h3>
                <div class="quote-summary">
                    <div class="quote-summary-row"><span>Client</span><strong>{{ $customRequest->customer_full_name }}</strong></div>
                    <div class="quote-summary-row"><span>Destination</span><strong>{{ $customRequest->desired_destination ?: '-' }}</strong></div>
                    <div class="quote-summary-row"><span>Date départ</span><strong>{{ $customRequest->desired_departure_date?->format('d/m/Y') ?: '-' }}</strong></div>
                    <div class="quote-summary-row"><span>Voyageurs</span><strong>{{ $customRequest->travelers_count ?: '-' }}</strong></div>
                    <div class="quote-summary-row"><span>Services</span><strong>{{ $customRequest->services->pluck('service_label')->implode(', ') ?: '-' }}</strong></div>
                </div>
            </section>

            <section class="quote-card">
                <h3>Historique</h3>
                <div class="quote-history">
                    @forelse($customRequest->statusLogs as $log)
                        <div class="quote-log">{{ $log->old_status ?: 'Création' }} → {{ $log->new_status }}<div class="text-muted small">{{ $log->user?->name ?: 'Système' }} - {{ $log->created_at?->format('d/m/Y H:i') }}</div>{{ $log->note }}</div>
                    @empty
                        <div class="text-muted small">Aucune action enregistrée.</div>
                    @endforelse
                    @foreach($customRequest->comments as $comment)
                        <div class="quote-log"><strong>{{ $comment->comment_type }}</strong><div class="text-muted small">{{ $comment->user?->name }} - {{ $comment->created_at?->format('d/m/Y H:i') }}</div>{{ $comment->message }}</div>
                    @endforeach
                </div>
            </section>
        </aside>
    </form>

    @can('custom_requests.documents')
        <section class="quote-card">
            <h3>Documents fournisseur / pièces complémentaires</h3>
            <form method="POST" action="{{ route($quoteRoutes['documents_store'] ?? 'admin.custom-requests.documents.store', $customRequest) }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="document_type" class="form-select"><option value="supplier_file">Fichier fournisseur</option><option value="other">Autre</option></select></div>
                <div class="col-md-4"><input name="title" class="form-control" placeholder="Titre"></div>
                <div class="col-md-3"><input type="file" name="document" class="form-control" required></div>
                <div class="col-md-2"><button class="quote-btn quote-btn-soft w-100">Uploader</button></div>
            </form>
        </section>
    @endcan
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const serviceOptions = @json($serviceTypeOptions);
    const serviceFields = @json($serviceFields);
    const daysContainer = document.getElementById('quoteDays');

    function money(value) {
        return (Number(value || 0)).toFixed(2);
    }

    function input(label, name, value = '') {
        return `<div class="quote-field"><label>${label}</label><input name="${name}" value="${String(value).replace(/"/g, '&quot;')}"></div>`;
    }

    function renderExtra(service) {
        const type = service.querySelector('[data-service-type]')?.value || 'other';
        const container = service.querySelector('[data-extra-container]');
        const dayIndex = [...document.querySelectorAll('[data-day]')].indexOf(service.closest('[data-day]'));
        const serviceIndex = [...service.closest('[data-services]').querySelectorAll('[data-service]')].indexOf(service);
        const fields = serviceFields[type] || serviceFields.other;
        container.innerHTML = Object.entries(fields).map(([key, label]) => input(label, `days[${dayIndex}][services][${serviceIndex}][data_json][${key}]`)).join('');
    }

    function serviceHtml(dayIndex, serviceIndex) {
        const options = Object.entries(serviceOptions).map(([key, label]) => `<option value="${key}">${label}</option>`).join('');
        return `<div class="quote-service" data-service>
            <div class="quote-service-head">
                <div class="quote-field"><label>Type de service</label><select name="days[${dayIndex}][services][${serviceIndex}][service_type]" data-service-type>${options}</select></div>
                <div class="quote-field"><label>Titre</label><input name="days[${dayIndex}][services][${serviceIndex}][title]"></div>
                <div class="quote-field"><label>Optionnel</label><select name="days[${dayIndex}][services][${serviceIndex}][is_optional]"><option value="0">Inclus</option><option value="1">Optionnel</option></select></div>
                <button type="button" class="quote-btn quote-btn-danger" data-remove-service><i class="bx bx-trash"></i></button>
            </div>
            <div class="quote-field"><label>Description pour le devis</label><textarea name="days[${dayIndex}][services][${serviceIndex}][description]"></textarea></div>
            <div class="quote-extra-grid" data-extra-container></div>
            <div class="quote-money-grid">
                <div class="quote-field"><label>Fournisseur</label><input name="days[${dayIndex}][services][${serviceIndex}][supplier_name]"></div>
                <div class="quote-field"><label>Quantité</label><input type="number" min="1" name="days[${dayIndex}][services][${serviceIndex}][quantity]" data-qty value="1"></div>
                <div class="quote-field"><label>Prix achat</label><input type="number" step="0.01" min="0" name="days[${dayIndex}][services][${serviceIndex}][unit_purchase_price]" data-purchase value="0"></div>
                <div class="quote-field"><label>Type marge</label><select name="days[${dayIndex}][services][${serviceIndex}][margin_type]" data-margin-type><option value="amount">Montant</option><option value="percent">%</option></select></div>
                <div class="quote-field"><label>Marge</label><input type="number" step="0.01" min="0" name="days[${dayIndex}][services][${serviceIndex}][margin_value]" data-margin value="0"></div>
                <div class="quote-field"><label>Prix vente U.</label><input type="number" step="0.01" min="0" name="days[${dayIndex}][services][${serviceIndex}][unit_sale_price]" data-sale readonly value="0"></div>
                <div class="quote-field"><label>Total vente</label><input data-line-total readonly value="0.00"></div>
            </div>
        </div>`;
    }

    function reindex() {
        document.querySelectorAll('[data-day]').forEach(function (day, dayIndex) {
            day.querySelector('[data-day-label]').textContent = dayIndex + 1;
            day.querySelector('[data-day-sort]').value = dayIndex;
            day.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(/days\[\d+\]/, `days[${dayIndex}]`);
            });
            day.querySelectorAll('[data-service]').forEach(function (service, serviceIndex) {
                service.querySelectorAll('[name]').forEach(function (field) {
                    field.name = field.name.replace(/services\[\d+\]/, `services[${serviceIndex}]`);
                });
            });
        });
    }

    function recalc() {
        let purchaseTotal = 0;
        let marginTotal = 0;
        let saleTotal = 0;

        document.querySelectorAll('[data-service]').forEach(function (service) {
            const qty = Number(service.querySelector('[data-qty]')?.value || 1);
            const purchase = Number(service.querySelector('[data-purchase]')?.value || 0);
            const marginValue = Number(service.querySelector('[data-margin]')?.value || 0);
            const marginType = service.querySelector('[data-margin-type]')?.value || 'amount';
            const unitMargin = marginType === 'percent' ? purchase * (marginValue / 100) : marginValue;
            const unitSale = purchase + unitMargin;
            const saleInput = service.querySelector('[data-sale]');
            const totalInput = service.querySelector('[data-line-total]');
            if (saleInput) saleInput.value = money(unitSale);
            if (totalInput) totalInput.value = money(unitSale * qty);
            purchaseTotal += purchase * qty;
            marginTotal += unitMargin * qty;
            saleTotal += unitSale * qty;
        });

        const deposit = Number(document.querySelector('[data-deposit]')?.value || 0);
        const paid = Number(document.querySelector('[data-paid]')?.value || 0);
        document.getElementById('totalPurchase').textContent = money(purchaseTotal);
        document.getElementById('totalMargin').textContent = money(marginTotal);
        document.getElementById('totalSale').textContent = money(saleTotal);
        document.getElementById('deposit').textContent = money(deposit);
        document.getElementById('paid').textContent = money(paid);
        document.getElementById('remaining').textContent = money(Math.max(0, saleTotal - paid));
    }

    document.getElementById('addQuoteDay')?.addEventListener('click', function () {
        const dayIndex = document.querySelectorAll('[data-day]').length;
        const day = document.createElement('article');
        day.className = 'quote-day';
        day.setAttribute('data-day', '');
        day.innerHTML = `<div class="quote-day-head"><div class="quote-day-title">Jour <span data-day-label>${dayIndex + 1}</span></div><div class="d-flex gap-2 flex-wrap"><button type="button" class="quote-btn quote-btn-soft" data-add-service><i class="bx bx-plus"></i> Ajouter un service</button><button type="button" class="quote-btn quote-btn-soft quote-day-toggle" data-toggle-day aria-label="Ouvrir ou fermer"><i class="bx bx-chevron-down"></i></button><button type="button" class="quote-btn quote-btn-danger" data-remove-day><i class="bx bx-trash"></i></button></div></div><div class="quote-day-body">
            <div class="quote-grid">
                <input type="hidden" name="days[${dayIndex}][id]" value="" data-day-id>
                <div class="quote-field"><label>Numéro du jour</label><input type="number" min="1" name="days[${dayIndex}][day_number]" data-day-number value="${dayIndex + 1}"></div>
                <div class="quote-field"><label>Date du jour</label><input type="date" name="days[${dayIndex}][date]"></div>
                <div class="quote-field"><label>Titre du jour</label><input name="days[${dayIndex}][title]"></div>
                <div class="quote-field"><label>Ville / destination</label><input name="days[${dayIndex}][city]"></div>
                <div class="quote-field full"><label>Description client</label><textarea name="days[${dayIndex}][client_description]"></textarea></div>
                <div class="quote-field full"><label>Notes internes</label><textarea name="days[${dayIndex}][internal_notes]"></textarea></div>
                <input type="hidden" name="days[${dayIndex}][sort_order]" value="${dayIndex}" data-day-sort>
            </div><div class="quote-stack" data-services>${serviceHtml(dayIndex, 0)}</div></div>`;
        daysContainer.appendChild(day);
        renderExtra(day.querySelector('[data-service]'));
        recalc();
    });

    document.addEventListener('click', function (event) {
        const addService = event.target.closest('[data-add-service]');
        const removeService = event.target.closest('[data-remove-service]');
        const removeDay = event.target.closest('[data-remove-day]');
        const toggleDay = event.target.closest('[data-toggle-day]');

        if (addService) {
            const day = addService.closest('[data-day]');
            day.classList.remove('is-collapsed');
            const dayIndex = [...document.querySelectorAll('[data-day]')].indexOf(day);
            const services = day.querySelector('[data-services]');
            const serviceIndex = services.querySelectorAll('[data-service]').length;
            services.insertAdjacentHTML('beforeend', serviceHtml(dayIndex, serviceIndex));
            renderExtra(services.lastElementChild);
            recalc();
        }

        if (removeService && document.querySelectorAll('[data-service]').length > 1) {
            removeService.closest('[data-service]').remove();
            reindex();
            recalc();
        }

        if (removeDay && document.querySelectorAll('[data-day]').length > 1) {
            removeDay.closest('[data-day]').remove();
            reindex();
            recalc();
        }

        if (toggleDay) {
            toggleDay.closest('[data-day]').classList.toggle('is-collapsed');
        }
    });

    document.querySelector('[data-open-days]')?.addEventListener('click', function () {
        document.querySelectorAll('[data-day]').forEach(day => day.classList.remove('is-collapsed'));
    });

    document.querySelector('[data-close-days]')?.addEventListener('click', function () {
        document.querySelectorAll('[data-day]').forEach(day => day.classList.add('is-collapsed'));
    });

    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-service-type]')) {
            renderExtra(event.target.closest('[data-service]'));
            reindex();
        }
        if (event.target.closest('#quoteForm')) recalc();
    });

    document.addEventListener('input', function (event) {
        if (event.target.closest('#quoteForm')) recalc();
    });

    document.querySelectorAll('[data-service]').forEach(function (service) {
        if (! service.querySelector('[data-extra-container]').children.length) {
            renderExtra(service);
        }
    });
    reindex();
    recalc();
});
</script>
@endpush
