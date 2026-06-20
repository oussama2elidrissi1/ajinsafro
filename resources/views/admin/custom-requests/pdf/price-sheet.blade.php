@php
    $settings = $invoiceSettings ?? [];
    $brandName = $settings['brand_name'] ?? 'Ajinsafro.ma';
    $serviceLabels = \App\Models\CustomRequestQuote::itemServiceOptions();
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' '.$quote->currency;
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche prix {{ $quote->quote_number }}</title>
    <style>
        @page { margin: 24px 30px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#16233a; font-size:11px; line-height:1.4; }
        .header { border-bottom:2px solid #008bd2; padding-bottom:12px; margin-bottom:14px; }
        .header h1 { margin:0; font-size:19px; color:#008bd2; }
        .muted { color:#66758a; }
        .grid { display:table; width:100%; margin-bottom:14px; }
        .col { display:table-cell; width:50%; vertical-align:top; }
        h2 { margin:0 0 8px; font-size:14px; color:#10233f; }
        table { width:100%; border-collapse:collapse; margin-top:8px; }
        th { background:#eef4fb; color:#10233f; text-align:left; padding:7px; border:1px solid #d9e5f2; font-weight:700; }
        td { padding:7px; border:1px solid #d9e5f2; vertical-align:top; }
        .text-right { text-align:right; }
        .totals { width:50%; margin-left:auto; margin-top:12px; }
        .totals td { border:0; border-bottom:1px solid #e4ebf3; padding:6px 0; }
        .total-main td { font-size:14px; font-weight:700; color:#008bd2; }
        .warning { background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; padding:10px; margin-bottom:12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Fiche prix interne</h1>
        <div><strong>{{ $quote->quote_number }}</strong> - Version {{ $quote->version }}</div>
        <div class="muted">{{ $brandName }} | Demande {{ $customRequest->request_number }} | {{ optional($quote->updated_at)->format('d/m/Y H:i') }}</div>
    </div>

    <div class="warning">
        Document interne agent offline. Ne pas envoyer au client sans validation.
    </div>

    <div class="grid">
        <div class="col">
            <h2>Demande</h2>
            <div><strong>Client :</strong> {{ $customRequest->customer_full_name }}</div>
            <div><strong>Destination :</strong> {{ $customRequest->desired_destination ?: '-' }}</div>
            <div><strong>Dates :</strong> {{ $customRequest->desired_departure_date?->format('d/m/Y') ?: '-' }} - {{ $customRequest->desired_return_date?->format('d/m/Y') ?: '-' }}</div>
        </div>
        <div class="col">
            <h2>Cotation</h2>
            <div><strong>Agent offline :</strong> {{ $quote->offlineAgent?->name ?: '-' }}</div>
            <div><strong>Fournisseur principal :</strong> {{ $quote->supplier_name ?: '-' }}</div>
            <div><strong>Devise :</strong> {{ $quote->currency }}</div>
        </div>
    </div>

    <h2>Tableau des prix</h2>
    <table>
        <thead>
            <tr>
                <th>Jour</th>
                <th>Service</th>
                <th>Description</th>
                <th class="text-right">Qté</th>
                <th class="text-right">Achat U.</th>
                <th class="text-right">Marge U.</th>
                <th class="text-right">Vente U.</th>
                <th class="text-right">Total vente</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->day ? 'Jour '.$item->day->day_number : '-' }}</td>
                    <td>{{ $serviceLabels[$item->service_type] ?? $item->service_type }}</td>
                    <td>{{ $item->title ?: $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ $money($item->unit_purchase_price) }}</td>
                    <td class="text-right">{{ $money($item->unit_margin) }}</td>
                    <td class="text-right">{{ $money($item->unit_sale_price) }}</td>
                    <td class="text-right">{{ $money($item->total_sale) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Total achat</td><td class="text-right">{{ $money($quote->total_purchase) }}</td></tr>
        <tr><td>Total marge</td><td class="text-right">{{ $money($quote->total_margin) }}</td></tr>
        <tr class="total-main"><td>Total vente</td><td class="text-right">{{ $money($quote->total_sale) }}</td></tr>
        <tr><td>Acompte demandé</td><td class="text-right">{{ $quote->requested_deposit ? $money($quote->requested_deposit) : '-' }}</td></tr>
        <tr><td>Montant payé</td><td class="text-right">{{ $money($quote->paid_amount) }}</td></tr>
        <tr><td>Reste à payer</td><td class="text-right">{{ $money($quote->remaining_amount) }}</td></tr>
    </table>

    @if($quote->internal_notes)
        <h2>Notes internes</h2>
        <div>{!! nl2br(e($quote->internal_notes)) !!}</div>
    @endif
</body>
</html>
