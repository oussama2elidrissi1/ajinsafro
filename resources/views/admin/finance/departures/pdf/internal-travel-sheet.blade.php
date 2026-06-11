<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Fiche de voyage interne DEP-{{ $departure->id }}</title>
    <style>
        @page { margin: 150px 30px 100px 30px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1e293b; line-height: 1.35; margin: 0; padding: 0; }
        .invoice-header { position: fixed; top: -140px; left: -30px; right: -30px; width: auto; margin: 0; padding: 0; }
        .invoice-header img, .invoice-footer img { width: 100%; height: auto; display: block; }
        .invoice-footer { position: fixed; bottom: -90px; left: -30px; right: -30px; width: auto; margin: 0; padding: 0; }
        h1 { font-size: 20px; color: #07598f; text-align: center; margin: 0 0 6px; text-transform: uppercase; letter-spacing: .8px; }
        .meta { text-align: center; color: #64748b; margin-bottom: 14px; }
        .info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info td { border: 1px solid #dbe4ee; padding: 8px 10px; background: #f8fafc; }
        .info .label { background: #eff6ff; color: #07598f; font-weight: 800; width: 16%; text-transform: uppercase; font-size: 9.5px; }
        .section { margin: 14px 0 8px; color: #07598f; font-weight: 900; text-transform: uppercase; border-bottom: 2px solid #dbe4ee; padding-bottom: 4px; }
        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.lines th { background: #07598f; color: #fff; border: 1px solid #07598f; padding: 7px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.lines td { border: 1px solid #dbe4ee; padding: 7px 8px; vertical-align: middle; }
        table.lines tbody tr:nth-child(even) td { background: #f8fafc; }
        .right { text-align: right; }
        .total td { font-weight: 800; background: #fff7ed !important; color: #9a3412; }
        .grand td { font-weight: 900; background: #07598f !important; color: #fff; font-size: 11px; }
        .ok { color: #15803d; font-weight: 800; }
        .ko { color: #b91c1c; font-weight: 800; }
    </style>
</head>
<body>
    @include('admin.reservations.pdf.partials.invoice-branding')

    <h1>Fiche de voyage interne</h1>
    <div class="meta">Generee le {{ $generatedAt->format('d/m/Y H:i') }} - DEP-{{ $departure->id }}</div>

    <table class="info">
        <tr>
            <td class="label">Voyage N</td><td>DEP-{{ $departure->id }}</td>
            <td class="label">Date voyage</td><td>{{ $departure->start_date?->format('d/m/Y') ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Voyageurs</td><td>{{ $summary['travelers_count'] }}</td>
            <td class="label">Nom voyage</td><td>{{ $voyage?->name ?: '-' }}</td>
        </tr>
    </table>

    <div class="section">Entree</div>
    <table class="lines">
        <thead><tr><th>Mode</th><th class="right">Nombre personnes</th><th class="right">Montant</th></tr></thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td>{{ $entry['label'] }}</td>
                    <td class="right">{{ $entry['people'] }}</td>
                    <td class="right">{{ number_format((float) $entry['amount'], 2, ',', ' ') }} DH</td>
                </tr>
            @endforeach
            <tr class="total"><td colspan="2">Total entree</td><td class="right">{{ number_format((float) $summary['total_entries'], 2, ',', ' ') }} DH</td></tr>
        </tbody>
    </table>

    <div class="section">Sortie</div>
    <table class="lines">
        <thead>
            <tr>
                <th>Type</th>
                @foreach($chargeMethods as $label)
                    <th class="right">{{ $label }}</th>
                @endforeach
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($chargesByType as $row)
                <tr>
                    <td>{{ $row['type_name'] }}</td>
                    @foreach(array_keys($chargeMethods) as $method)
                        <td class="right">{{ number_format((float) ($row['methods'][$method] ?? 0), 2, ',', ' ') }}</td>
                    @endforeach
                    <td class="right">{{ number_format((float) $row['total'], 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ count($chargeMethods) + 2 }}">Aucune charge saisie.</td></tr>
            @endforelse
            <tr class="total">
                <td>Total par mode</td>
                @foreach(array_keys($chargeMethods) as $method)
                    <td class="right">{{ number_format((float) ($totalChargesByMethod[$method] ?? 0), 2, ',', ' ') }}</td>
                @endforeach
                <td class="right">{{ number_format((float) $summary['total_charges'], 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="lines">
        <tbody>
            <tr><td>Total entree</td><td class="right">{{ number_format((float) $summary['total_entries'], 2, ',', ' ') }} DH</td></tr>
            <tr><td>Total sortie</td><td class="right">{{ number_format((float) $summary['total_charges'], 2, ',', ' ') }} DH</td></tr>
            <tr class="grand"><td>Solde</td><td class="right">{{ number_format((float) $summary['balance'], 2, ',', ' ') }} DH</td></tr>
            <tr><td>Rentable</td><td class="right {{ $summary['is_profitable'] ? 'ok' : 'ko' }}">{{ $summary['is_profitable'] ? 'Oui' : 'Non' }}</td></tr>
        </tbody>
    </table>
</body>
</html>
