<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Dossier {{ $reservation->dossier_number ?: $reservation->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1, h2, h3 { margin: 0 0 8px; }
        .muted { color: #64748b; }
        .grid { width: 100%; margin-bottom: 18px; }
        .grid td { vertical-align: top; padding: 6px 8px; border: 1px solid #dbe4ee; }
        .section { margin-bottom: 18px; }
        table.lines { width: 100%; border-collapse: collapse; }
        table.lines th, table.lines td { border: 1px solid #dbe4ee; padding: 6px 8px; }
        table.lines th { background: #eff6ff; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="section">
        <h1>Dossier de réservation</h1>
        <div class="muted">Numéro dossier : {{ $reservation->dossier_number ?: 'RES-'.$reservation->id }}</div>
        <div class="muted">?dité le {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table class="grid" cellspacing="0" cellpadding="0">
        <tr>
            <td width="50%">
                <h3>Client principal</h3>
                <div>{{ $reservation->client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '?' }}</div>
                <div>{{ $reservation->client_phone ?: '?' }}</div>
                <div>{{ $reservation->client_email ?: '?' }}</div>
            </td>
            <td width="50%">
                <h3>Voyage</h3>
                <div>{{ $reservation->offer?->name ?? '?' }}</div>
                <div>Départ : {{ $reservation->departure?->start_date?->format('d/m/Y') ?? '?' }}</div>
                <div>Retour : {{ $reservation->departure?->end_date?->format('d/m/Y') ?? '?' }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <h3>Récapitulatif financier</h3>
        <table class="lines" cellspacing="0" cellpadding="0">
            <tr><th>Total base</th><td class="right">{{ number_format((float) ($reservation->effective_total_base ?? $reservation->total_base ?? 0), 2, ',', ' ') }} DH</td></tr>
            <tr><th>Suppléments chambres</th><td class="right">{{ number_format((float) ($reservation->room_supplement_total ?? 0), 2, ',', ' ') }} DH</td></tr>
            <tr><th>Extras</th><td class="right">{{ number_format((float) ($reservation->effective_extras_total ?? $reservation->extras_total ?? 0), 2, ',', ' ') }} DH</td></tr>
            <tr><th>Total dossier</th><td class="right">{{ number_format((float) ($reservation->effective_total_amount ?? $reservation->total_amount ?? 0), 2, ',', ' ') }} DH</td></tr>
            <tr><th>Total payé</th><td class="right">{{ number_format((float) ($reservation->effective_paid_amount ?? $reservation->paid_amount ?? 0), 2, ',', ' ') }} DH</td></tr>
            <tr><th>Reste à payer</th><td class="right">{{ number_format((float) ($reservation->effective_remaining_amount ?? $reservation->remaining_amount ?? 0), 2, ',', ' ') }} DH</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Voyageurs</h3>
        <table class="lines" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Document</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $reservation->client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: 'Client principal' }}</td>
                    <td>Principal</td>
                    <td>{{ $reservation->client_document_type ?: '?' }} {{ $reservation->client_document_number ?: '' }}</td>
                </tr>
                @foreach($reservation->passengers as $passenger)
                    <tr>
                        <td>{{ trim(($passenger->first_name ?? '').' '.($passenger->last_name ?? '')) ?: '?' }}</td>
                        <td>{{ $passenger->type ?: '?' }}</td>
                        <td>{{ $passenger->document_type ?: '?' }} {{ $passenger->document_number ?: '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Extras</h3>
        <table class="lines" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th>Libellé</th>
                    <th class="right">Qté</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservation->extras as $extra)
                    <tr>
                        <td>{{ $extra->name }}</td>
                        <td class="right">{{ $extra->quantity ?: 1 }}</td>
                        <td class="right">{{ number_format((float) ($extra->total_price ?: $extra->price), 2, ',', ' ') }} DH</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Aucun extra.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Paiements</h3>
        <table class="lines" cellspacing="0" cellpadding="0">
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
                        <td>{{ $payment->payment_date?->format('d/m/Y') ?? '?' }}</td>
                        <td>{{ $payment->payment_method ?: '?' }}</td>
                        <td>{{ $payment->reference ?: '?' }}</td>
                        <td class="right">{{ number_format((float) $payment->amount, 2, ',', ' ') }} DH</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Aucun paiement.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>

