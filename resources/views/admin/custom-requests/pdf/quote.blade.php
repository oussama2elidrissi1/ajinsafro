@php
    $settings = $invoiceSettings ?? [];
    $brandName = $settings['brand_name'] ?? 'Ajinsafro.ma';
    $logo = $settings['logo_url'] ?? \App\Models\Setting::brandLogoUrl('dark');
    $headerImage = $settings['header_image_src'] ?? $settings['header_image_file'] ?? $settings['header_image_url'] ?? null;
    $footerImage = $settings['footer_image_src'] ?? $settings['footer_image_file'] ?? $settings['footer_image_url'] ?? null;
    $hasConfiguredHeader = !empty($settings['header_image_path']);
    $hasConfiguredFooter = !empty($settings['footer_image_path']);
    $serviceLabels = \App\Models\CustomRequestQuote::itemServiceOptions();
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' '.$quote->currency;
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $quote->quote_number }}</title>
    <style>
        @page { margin: 18px 30px 58px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#16233a; font-size:12px; line-height:1.45; }
        .invoice-header-image { display:block; width:100%; height:auto; margin:0 0 14px; }
        .header { display:table; width:100%; border-bottom:2px solid #008bd2; padding-bottom:14px; margin-bottom:16px; }
        .brand,.quote-meta { display:table-cell; vertical-align:top; width:50%; }
        .brand img { max-height:52px; max-width:160px; margin-bottom:8px; }
        .brand h1 { margin:0; font-size:18px; font-weight:700; }
        .muted { color:#66758a; }
        .quote-meta { text-align:right; }
        .quote-meta h2 { margin:0 0 8px; font-size:22px; color:#008bd2; }
        .quote-strip { text-align:right; border-bottom:2px solid #008bd2; padding-bottom:10px; margin-bottom:16px; }
        .quote-strip h2 { margin:0 0 6px; font-size:22px; color:#008bd2; }
        .block { margin-bottom:16px; }
        .grid { display:table; width:100%; }
        .col { display:table-cell; width:50%; vertical-align:top; padding-right:12px; }
        h3 { margin:0 0 8px; color:#10233f; font-size:14px; font-weight:700; }
        dl { margin:0; }
        dt { float:left; clear:left; width:128px; color:#66758a; }
        dd { margin:0 0 4px 136px; }
        table { width:100%; border-collapse:collapse; margin-top:8px; }
        th { background:#eef4fb; color:#10233f; text-align:left; padding:8px; border:1px solid #d9e5f2; font-weight:700; }
        td { padding:8px; border:1px solid #d9e5f2; vertical-align:top; }
        .text-right { text-align:right; }
        .day { border:1px solid #d9e5f2; border-radius:6px; padding:10px; margin-bottom:9px; }
        .day-title { font-size:13px; font-weight:700; color:#008bd2; margin-bottom:5px; }
        .service-list { margin:5px 0 0 16px; padding:0; }
        .service-list li { margin-bottom:4px; }
        .totals { width:48%; margin-left:auto; margin-top:12px; }
        .totals td { border:0; border-bottom:1px solid #e4ebf3; padding:6px 0; }
        .total-main td { font-size:15px; font-weight:700; color:#008bd2; }
        .conditions { background:#f8fafc; border:1px solid #d9e5f2; padding:12px; }
        .footer { position:fixed; bottom:-44px; left:0; right:0; text-align:center; color:#66758a; font-size:10px; border-top:1px solid #d9e5f2; padding-top:7px; }
        .footer img { display:block; width:100%; height:auto; max-height:58px; object-fit:contain; }
        .page:after { content: counter(page); }
    </style>
</head>
<body>
    @if($headerImage)
        <img src="{{ $headerImage }}" class="invoice-header-image" alt="En-tête facture">
        <div class="quote-strip">
            <h2>DEVIS</h2>
            <div><strong>N° devis :</strong> {{ $quote->quote_number }}</div>
            <div><strong>Version :</strong> {{ $quote->version }}</div>
            <div><strong>Date :</strong> {{ optional($quote->prepared_at ?? $quote->updated_at)->format('d/m/Y') }}</div>
            <div><strong>Valable jusqu’au :</strong> {{ $quote->valid_until?->format('d/m/Y') ?: '-' }}</div>
        </div>
    @elseif(!$hasConfiguredHeader)
        <div class="header">
            <div class="brand">
                <img src="{{ $logo }}" alt="{{ $brandName }}">
                <h1>{{ $brandName }}</h1>
                @if(!empty($settings['phone'])) <div class="muted">{{ $settings['phone'] }}</div> @endif
                @if(!empty($settings['email'])) <div class="muted">{{ $settings['email'] }}</div> @endif
                @if(!empty($settings['company_address'])) <div class="muted">{{ $settings['company_address'] }}</div> @endif
            </div>
            <div class="quote-meta">
                <h2>DEVIS</h2>
                <div><strong>N° devis :</strong> {{ $quote->quote_number }}</div>
                <div><strong>Version :</strong> {{ $quote->version }}</div>
                <div><strong>Date :</strong> {{ optional($quote->prepared_at ?? $quote->updated_at)->format('d/m/Y') }}</div>
                <div><strong>Valable jusqu’au :</strong> {{ $quote->valid_until?->format('d/m/Y') ?: '-' }}</div>
            </div>
        </div>
    @else
        <div class="quote-strip">
            <h2>DEVIS</h2>
            <div><strong>N° devis :</strong> {{ $quote->quote_number }}</div>
            <div><strong>Version :</strong> {{ $quote->version }}</div>
            <div><strong>Date :</strong> {{ optional($quote->prepared_at ?? $quote->updated_at)->format('d/m/Y') }}</div>
            <div><strong>Valable jusqu’au :</strong> {{ $quote->valid_until?->format('d/m/Y') ?: '-' }}</div>
        </div>
    @endif

    <div class="grid block">
        <div class="col">
            <h3>Informations client</h3>
            <dl>
                <dt>Nom</dt><dd>{{ $customRequest->customer_full_name }}</dd>
                <dt>Téléphone</dt><dd>{{ $customRequest->customer_phone ?: '-' }}</dd>
                <dt>Email</dt><dd>{{ $customRequest->customer_email ?: '-' }}</dd>
                <dt>Ville / pays</dt><dd>{{ $customRequest->customer_city ?: '-' }} / {{ $customRequest->customer_country ?: '-' }}</dd>
                <dt>CIN / passeport</dt><dd>{{ $customRequest->customer_identity ?: '-' }}</dd>
            </dl>
        </div>
        <div class="col">
            <h3>Résumé voyage</h3>
            <dl>
                <dt>Destination</dt><dd>{{ $customRequest->desired_destination ?: '-' }}</dd>
                <dt>Départ</dt><dd>{{ $customRequest->departure_city ?: '-' }}</dd>
                <dt>Date départ</dt><dd>{{ $customRequest->desired_departure_date?->format('d/m/Y') ?: '-' }}</dd>
                <dt>Date retour</dt><dd>{{ $customRequest->desired_return_date?->format('d/m/Y') ?: '-' }}</dd>
                <dt>Durée</dt><dd>{{ $customRequest->desired_duration ?: '-' }}</dd>
                <dt>Voyageurs</dt><dd>{{ $customRequest->travelers_count ?: '-' }}</dd>
                <dt>Niveau</dt><dd>{{ $customRequest->desired_level ?: '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="block">
        <h3>Programme détaillé</h3>
        @forelse($days as $day)
            <div class="day">
                <div class="day-title">Jour {{ $day->day_number }} — {{ $day->title ?: ($day->city ?: 'Programme') }}</div>
                @if($day->date || $day->city)
                    <div class="muted">{{ $day->date?->format('d/m/Y') ?: '' }}{{ $day->date && $day->city ? ' · ' : '' }}{{ $day->city ?: '' }}</div>
                @endif
                @if($day->client_description)
                    <div style="margin-top:5px;">{!! nl2br(e($day->client_description)) !!}</div>
                @endif
                <ul class="service-list">
                    @foreach($day->services as $service)
                        <li>
                            <strong>{{ $serviceLabels[$service->service_type] ?? $service->service_type }} :</strong>
                            {{ $service->title ?: $service->description }}
                            @if($service->description && $service->title)
                                — {{ $service->description }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="conditions">Aucun programme détaillé n’a été saisi.</div>
        @endforelse
        @if($customRequest->desired_return_date)
            <div class="day">
                <div class="day-title">Retour — {{ $customRequest->desired_return_date->format('d/m/Y') }}</div>
            </div>
        @endif
    </div>

    @if($showAmounts && $items->isNotEmpty())
        <div class="block">
            <h3>Tableau des prix</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width:16%">Service</th>
                        <th>Description</th>
                        <th style="width:9%" class="text-right">Qté</th>
                        <th style="width:15%" class="text-right">Prix unitaire</th>
                        <th style="width:15%" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $serviceLabels[$item->service_type] ?? $item->service_type }}</td>
                            <td>{{ $item->title ?: $item->description }}</td>
                            <td class="text-right">{{ $item->quantity }}</td>
                            <td class="text-right">{{ $money($item->unit_sale_price) }}</td>
                            <td class="text-right">{{ $money($item->total_sale) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals">
                <tr class="total-main"><td>Total devis</td><td class="text-right">{{ $money($quote->total_sale) }}</td></tr>
                <tr><td>Acompte demandé</td><td class="text-right">{{ $quote->requested_deposit ? $money($quote->requested_deposit) : '-' }}</td></tr>
                <tr><td>Montant payé</td><td class="text-right">{{ $money($quote->paid_amount) }}</td></tr>
                <tr><td>Reste à payer</td><td class="text-right">{{ $money($quote->remaining_amount) }}</td></tr>
            </table>
        </div>
    @endif

    <div class="block conditions">
        <h3>Conditions client</h3>
        <div>{!! nl2br(e($quote->customer_conditions ?: ($settings['default_conditions'] ?: 'Ce devis est valable jusqu’à la date indiquée, sous réserve de disponibilité.'))) !!}</div>
    </div>

    @if(!empty($settings['legal_information']) || !empty($settings['company_ice']) || !empty($settings['company_if']) || !empty($settings['company_rc']))
        <div class="block muted">
            {{ $settings['legal_information'] ?? '' }}
            @if(!empty($settings['company_ice'])) ICE : {{ $settings['company_ice'] }} @endif
            @if(!empty($settings['company_if'])) IF : {{ $settings['company_if'] }} @endif
            @if(!empty($settings['company_rc'])) RC : {{ $settings['company_rc'] }} @endif
        </div>
    @endif

    <div class="footer">
        @if($footerImage)
            <img src="{{ $footerImage }}" alt="Pied de page facture">
        @elseif(!$hasConfiguredFooter)
            {{ $brandName }} - Merci pour votre confiance - Page <span class="page"></span>
        @endif
    </div>
</body>
</html>
