<table border="1">
    <thead>
        <tr>
            <th>Date</th>
            <th>Agent</th>
            <th>Point de vente</th>
            <th>Voyage</th>
            <th>Depart</th>
            <th>Client</th>
            <th>Montant reservation</th>
            <th>Commission</th>
            <th>Statut reservation</th>
            <th>Statut paiement</th>
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
                <td>{{ number_format((float) $entry->reservation_total, 2, '.', '') }}</td>
                <td>{{ number_format((float) $entry->commission_total, 2, '.', '') }}</td>
                <td>{{ $entry->reservation_status }}</td>
                <td>{{ $entry->payment_status }}</td>
                <td>{{ $entry->commission_status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
