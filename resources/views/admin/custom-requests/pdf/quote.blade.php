@php
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $phone = \App\Models\Setting::getValue('topbar_phone', '');
    $email = \App\Models\Setting::getValue('topbar_email', '');
    $logo = \App\Models\Setting::brandLogoUrl('dark');
    $serviceLabels = \App\Models\CustomRequestQuote::itemServiceOptions();
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $quote->quote_number }}</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#16233a; font-size:12px; line-height:1.45; }
        .header { display:table; width:100%; border-bottom:2px solid #1f6feb; padding-bottom:14px; margin-bottom:18px; }
        .brand,.quote-meta { display:table-cell; vertical-align:top; width:50%; }
        .brand img { max-height:48px; max-width:150px; margin-bottom:8px; }
        .brand h1 { margin:0; font-size:18px; font-weight:700; }
        .muted { color:#66758a; }
        .quote-meta { text-align:right; }
        .quote-meta h2 { margin:0 0 8px; font-size:22px; color:#1f6feb; }
        .block { margin-bottom:16px; }
        .grid { display:table; width:100%; }
        .col { display:table-cell; width:50%; vertical-align:top; padding-right:12px; }
        h3 { margin:0 0 8px; color:#10233f; font-size:14px; font-weight:700; }
        dl { margin:0; }
        dt { float:left; clear:left; width:120px; color:#66758a; }
        dd { margin:0 0 4px 128px; }
        table { width:100%; border-collapse:collapse; margin-top:8px; }
        th { background:#eef4fb; color:#10233f; text-align:left; padding:8px; border:1px solid #d9e5f2; font-weight:700; }
        td { padding:8px; border:1px solid #d9e5f2; vertical-align:top; }
        .text-right { text-align:right; }
        .totals { width:46%; margin-left:auto; margin-top:12px; }
        .totals td { border:0; border-bottom:1px solid #e4ebf3; padding:6px 0; }
        .total-main td { font-size:15px; font-weight:700; color:#1f6feb; }
        .conditions { background:#f8fafc; border:1px solid #d9e5f2; padding:12px; }
        .footer { position:fixed; bottom:-12px; left:0; right:0; text-align:center; color:#66758a; font-size:10px; border-top:1px solid #d9e5f2; padding-top:8px; }
        .page:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <img src="{{ $logo }}" alt="{{ $brandName }}">
            <h1>{{ $brandName }}</h1>
            <div class="muted">{{ $phone }}</div>
            <div class="muted">{{ $email }}</div>
        </div>
        <div class="quote-meta">
            <h2>DEVIS</h2>
            <div><strong>N° devis:</strong> {{ $quote->quote_number }}</div>
            <div><strong>Version:</strong> {{ $quote->version }}</div>
            <div><strong>Date:</strong> {{ optional($quote->prepared_at ?? $quote->updated_at)->format('d/m/Y') }}</div>
            <div><strong>Valable jusqu’au:</strong> {{ $quote->valid_until?->format('d/m/Y') ?: '-' }}</div>
        </div>
    </div>

    <div class="grid block">
        <div class="col">
            <h3>Informations client</h3>
            <dl>
                <dt>Nom</dt><dd>{{ $customRequest->customer_full_name }}</dd>
                <dt>Téléphone</dt><dd>{{ $customRequest->customer_phone }}</dd>
                <dt>Email</dt><dd>{{ $customRequest->customer_email ?: '-' }}</dd>
                <dt>Ville / pays</dt><dd>{{ $customRequest->customer_city ?: '-' }} / {{ $customRequest->customer_country ?: '-' }}</dd>
                <dt>CIN / passeport</dt><dd>{{ $customRequest->customer_identity ?: '-' }}</dd>
            </dl>
        </div>
        <div class="col">
            <h3>Informations voyage</h3>
            <dl>
                <dt>Destination</dt><dd>{{ $customRequest->desired_destination }}</dd>
                <dt>Départ</dt><dd>{{ $customRequest->departure_city }}</dd>
                <dt>Date départ</dt><dd>{{ $customRequest->desired_departure_date?->format('d/m/Y') }}</dd>
                <dt>Date retour</dt><dd>{{ $customRequest->desired_return_date?->format('d/m/Y') ?: '-' }}</dd>
                <dt>Durée</dt><dd>{{ $customRequest->desired_duration ?: '-' }}</dd>
                <dt>Voyageurs</dt><dd>{{ $customRequest->travelers_count }} ({{ $customRequest->adults_count }} adultes, {{ $customRequest->children_count }} enfants, {{ $customRequest->babies_count }} bébés)</dd>
                <dt>Niveau</dt><dd>{{ $customRequest->desired_level ?: '-' }}</dd>
            </dl>
        </div>
    </div>

    <div class="block">
        <h3>Prestations</h3>
        <table>
            <thead>
                <tr>
                    <th style="width:18%">Service</th>
                    <th>Description</th>
                    <th style="width:10%" class="text-right">Qté</th>
                    <th style="width:16%" class="text-right">Prix unitaire</th>
                    <th style="width:16%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $serviceLabels[$item->service_type] ?? $item->service_type }}</td>
                        <td>{!! nl2br(e($item->description)) !!}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format((float) $item->unit_sale_price, 2, ',', ' ') }} {{ $quote->currency }}</td>
                        <td class="text-right">{{ number_format((float) $item->total_sale, 2, ',', ' ') }} {{ $quote->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr class="total-main"><td>Total devis</td><td class="text-right">{{ number_format((float) $quote->total_sale, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
            <tr><td>Acompte demandé</td><td class="text-right">{{ $quote->requested_deposit ? number_format((float) $quote->requested_deposit, 2, ',', ' ').' '.$quote->currency : '-' }}</td></tr>
            <tr><td>Montant payé</td><td class="text-right">{{ number_format((float) $quote->paid_amount, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
            <tr><td>Reste à payer</td><td class="text-right">{{ number_format((float) $quote->remaining_amount, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
        </table>
    </div>

    <div class="block conditions">
        <h3>Conditions</h3>
        <div>{!! nl2br(e($quote->customer_conditions ?: 'Ce devis est valable jusqu’à la date indiquée, sous réserve de disponibilité.')) !!}</div>
        <p class="muted">Ce devis est valable jusqu’à la date indiquée, sous réserve de disponibilité.</p>
    </div>

    <div class="footer">
        {{ $brandName }} - Merci pour votre confiance - Page <span class="page"></span>
    </div>
</body>
</html>
