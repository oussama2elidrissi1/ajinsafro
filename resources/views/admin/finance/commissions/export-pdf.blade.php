<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .meta { margin-bottom: 16px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Commissions agents</h1>
    <div class="meta">Genere le {{ $generatedAt->format('d/m/Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Agent</th>
                <th>Point de vente</th>
                <th>Voyage</th>
                <th>Depart</th>
                <th>Client</th>
                <th>Reservation</th>
                <th>Commission</th>
                <th>Statut commission</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td>{{ optional($entry->calculated_at)->format('d/m/Y') }}</td>
                    <td>{{ $entry->agent?->name }}</td>
                    <td>{{ $entry->branch?->name }}</td>
                    <td>{{ $entry->voyage?->name }}</td>
                    <td>{{ $entry->reservation?->departure?->start_date?->format('d/m/Y') ?? $entry->travelDate?->date?->format('d/m/Y') }}</td>
                    <td>{{ $entry->client_name }}</td>
                    <td>{{ number_format((float) $entry->reservation_total, 2, ',', ' ') }} DH</td>
                    <td>{{ number_format((float) $entry->commission_total, 2, ',', ' ') }} DH</td>
                    <td>{{ $entry->statusLabelFr() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
