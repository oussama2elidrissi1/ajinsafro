<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; line-height: 1.35; }
        h1 { font-size: 15px; color: #0e3a5a; margin: 0 0 6px 0; }
        h2 { font-size: 11px; color: #0083c4; margin: 12px 0 6px 0; border-bottom: 1px solid #e6f3fa; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ddd; padding: 5px 7px; text-align: left; }
        th { background: #e6f3fa; }
        .meta { font-size: 9px; color: #666; margin: 4px 0; }
    </style>
</head>
<body>
    <h1>Réservation #{{ $reservation->id }}</h1>
    <p class="meta">Généré le {{ $generatedAt->format('d/m/Y H:i') }}</p>

    <p><strong>Statut :</strong> {{ $reservation->status }}</p>
    <p><strong>Type de prestation :</strong> {{ $reservation->prestation_type ?? '—' }}</p>
    <p><strong>Voyage (Laravel) :</strong> {{ $reservation->tour?->name ?? '—' }} @if($reservation->tour?->wp_post_id) (WP #{{ $reservation->tour->wp_post_id }}) @endif</p>
    @if($reservation->travelDate)
        <p><strong>Date de départ (calendrier) :</strong> {{ optional($reservation->travelDate->date)->format('d/m/Y') ?? '—' }}</p>
    @endif
    <p><strong>Client :</strong> {{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) }}</p>
    @if($reservation->client?->full_name)
        <p class="meta">Fiche client : {{ $reservation->client->full_name }} @if($reservation->client->client_code) ({{ $reservation->client->client_code }}) @endif</p>
    @endif
    <p><strong>Total :</strong> {{ $reservation->total_price !== null ? number_format((float) $reservation->total_price, 2, ',', ' ').' MAD' : '—' }}</p>
    <p><strong>Montant payé :</strong> {{ $reservation->paid_amount !== null ? number_format((float) $reservation->paid_amount, 2, ',', ' ').' MAD' : '—' }}</p>

    <h2>Participants</h2>
    <table>
        <thead>
        <tr>
            <th>Nom</th>
            <th>Type</th>
            <th>Naissance</th>
            <th>Document</th>
        </tr>
        </thead>
        <tbody>
        @foreach($reservation->passengers as $p)
            <tr>
                <td>{{ trim(($p->first_name ?? '').' '.($p->last_name ?? '')) }}</td>
                <td>{{ $p->type }}</td>
                <td>{{ optional($p->birth_date)->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $p->document_number ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if($reservation->extras->isNotEmpty())
        <h2>Extras</h2>
        <table>
            <thead>
            <tr><th>Libellé</th><th>Prix</th></tr>
            </thead>
            <tbody>
            @foreach($reservation->extras as $e)
                <tr>
                    <td>{{ $e->name }}</td>
                    <td>{{ number_format((float) $e->price, 2, ',', ' ') }} MAD</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
