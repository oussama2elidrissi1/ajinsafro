<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $reservation->dossier_number ?: $reservation->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1, h2, h3 { margin: 0 0 8px; }
        .muted { color: #64748b; }
        .section { margin-bottom: 18px; }
        .grid { width: 100%; margin-bottom: 18px; border-collapse: collapse; }
        .grid td { vertical-align: top; padding: 10px 12px; border: 1px solid #dbe4ee; }
        table.lines { width: 100%; border-collapse: collapse; }
        table.lines th, table.lines td { border: 1px solid #dbe4ee; padding: 8px 10px; }
        table.lines th { background: #eff6ff; text-align: left; }
        .right { text-align: right; }
        .totals td { font-weight: bold; }
    </style>
</head>
<body>
    <div class="section">
        <h1>Facture dossier</h1>
        <div class="muted">Dossier : {{ $reservation->dossier_number ?: 'RES-'.$reservation->id }}</div>
        <div class="muted">Éditée le {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table class="grid">
        <tr>
            <td width="50%">
                <h3>Client</h3>
                <div>{{ $reservation->client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}</div>
                <div>{{ $reservation->client?->phone ?: $reservation->client_phone ?: '—' }}</div>
                <div>{{ $reservation->client?->email ?: $reservation->client_email ?: '—' }}</div>
            </td>
            <td width="50%">
                <h3>Offre</h3>
                <div>{{ $reservation->offer?->name ?? '—' }}</div>
                <div>Départ : {{ $reservation->departure?->start_date?->format('d/m/Y') ?? '—' }}</div>
                <div>Retour : {{ $reservation->departure?->end_date?->format('d/m/Y') ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <h3>Détail financier</h3>
        <table class="lines">
            <thead>
                <tr>
                    <th>Libellé</th>
                    <th class="right">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total base</td>
                    <td class="right">{{ number_format((float) ($reservation->total_base ?? 0), 2, ',', ' ') }} DH</td>
                </tr>
                <tr>
                    <td>Suppléments chambres</td>
                    <td class="right">{{ number_format((float) ($reservation->room_supplement_total ?? 0), 2, ',', ' ') }} DH</td>
                </tr>
                <tr>
                    <td>Extras</td>
                    <td class="right">{{ number_format((float) ($reservation->extras_total ?? 0), 2, ',', ' ') }} DH</td>
                </tr>
                <tr class="totals">
                    <td>Total dossier</td>
                    <td class="right">{{ number_format((float) ($reservation->total_amount ?? 0), 2, ',', ' ') }} DH</td>
                </tr>
                <tr>
                    <td>Déjà payé</td>
                    <td class="right">{{ number_format((float) ($reservation->paid_amount ?? 0), 2, ',', ' ') }} DH</td>
                </tr>
                <tr class="totals">
                    <td>Reste à payer</td>
                    <td class="right">{{ number_format((float) ($reservation->remaining_amount ?? 0), 2, ',', ' ') }} DH</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Paiements enregistrés</h3>
        <table class="lines">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Mode</th>
                    <th>Référence</th>
                    <th class="right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservation->payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $payment->payment_method ?: '—' }}</td>
                        <td>{{ $payment->reference ?: '—' }}</td>
                        <td class="right">{{ number_format((float) $payment->amount, 2, ',', ' ') }} DH</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Aucun paiement enregistré.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
