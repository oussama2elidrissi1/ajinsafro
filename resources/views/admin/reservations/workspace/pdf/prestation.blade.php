<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; color: #0e3a5a; margin-bottom: 4px; }
        .muted { color: #666; font-size: 10px; }
        .box { background: #f7f9fc; border: 1px solid #e6f3fa; padding: 10px; margin: 12px 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #e6f3fa; color: #0e3a5a; }
    </style>
</head>
<body>
    <h1>Prestation — {{ $voyage->name }}</h1>
    <p class="muted">Réf. voyage Laravel #{{ $voyage->id }}@if($voyage->wp_post_id) · WP post {{ $voyage->wp_post_id }}@endif</p>
    @if($travelDateId)
        <p class="muted">Filtre date (travel_date_id) : {{ $travelDateId }}</p>
    @endif
    <p>Généré le {{ $generatedAt->format('d/m/Y H:i') }}</p>

    @if($reservations->isEmpty())
        <div class="box">
            Aucune réservation enregistrée pour cette prestation avec les filtres appliqués. Document de fiche catalogue (workspace).
        </div>
    @endif

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
