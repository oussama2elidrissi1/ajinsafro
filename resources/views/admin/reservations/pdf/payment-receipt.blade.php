<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu paiement {{ $payment->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1, h2, h3 { margin: 0 0 10px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #dbe4ee; padding: 8px; text-align: left; }
        th { background: #eff6ff; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Reçu de paiement</h1>
    <div class="muted">Dossier : {{ $reservation->dossier_number ?: 'RES-'.$reservation->id }}</div>
    <div class="muted">Date d'édition : {{ now()->format('d/m/Y H:i') }}</div>

    <table>
        <tr>
            <th width="40%">Client</th>
            <td>{{ $reservation->client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '�?"' }}</td>
        </tr>
        <tr>
            <th>Voyage</th>
            <td>{{ $reservation->offer?->name ?? '�?"' }}</td>
        </tr>
        <tr>
            <th>Départ</th>
            <td>{{ $reservation->departure?->start_date?->format('d/m/Y') ?? '�?"' }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <th>Date paiement</th>
            <td>{{ $payment->payment_date?->format('d/m/Y') ?? '�?"' }}</td>
        </tr>
        <tr>
            <th>Mode de paiement</th>
            <td>{{ $payment->payment_method ?: '�?"' }}</td>
        </tr>
        <tr>
            <th>Référence</th>
            <td>{{ $payment->reference ?: '�?"' }}</td>
        </tr>
        <tr>
            <th>Montant</th>
            <td class="right">{{ number_format((float) $payment->amount, 2, ',', ' ') }} DH</td>
        </tr>
        <tr>
            <th>Note</th>
            <td>{{ $payment->note ?: '�?"' }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <th>Total dossier</th>
            <td class="right">{{ number_format((float) ($reservation->effective_total_amount ?? $reservation->total_amount ?? 0), 2, ',', ' ') }} DH</td>
        </tr>
        <tr>
            <th>Total payé</th>
            <td class="right">{{ number_format((float) ($reservation->effective_paid_amount ?? $reservation->paid_amount ?? 0), 2, ',', ' ') }} DH</td>
        </tr>
        <tr>
            <th>Reste à payer</th>
            <td class="right">{{ number_format((float) ($reservation->effective_remaining_amount ?? $reservation->remaining_amount ?? 0), 2, ',', ' ') }} DH</td>
        </tr>
    </table>
</body>
</html>

