<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; color: #0e3a5a; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #e6f3fa; color: #0e3a5a; }
    </style>
</head>
<body>
    <h1>Dossiers — {{ $voyage->name }}</h1>
    @if($travelDateId)
        <p>Date de voyage (WP travel_date_id) : {{ $travelDateId }}</p>
    @endif
    <p>Généré le {{ $generatedAt->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Client</th>
            <th>Statut</th>
            <th>Pax</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($reservations as $r)
            <tr>
                <td>{{ $r->id }}</td>
                <td>{{ trim(($r->client_first_name ?? '').' '.($r->client_last_name ?? '')) ?: ($r->client?->full_name ?? '—') }}</td>
                <td>{{ $r->status }}</td>
                <td>{{ $r->passengers->count() }}</td>
                <td>{{ $r->total_price !== null ? number_format((float) $r->total_price, 2, ',', ' ').' DH' : '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
