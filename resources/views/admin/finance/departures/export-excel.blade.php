<table>
    <tr><th colspan="4">FICHE DE VOYAGE INTERNE</th></tr>
    <tr><td>Voyage N</td><td>DEP-{{ $departure->id }}</td><td>Date voyage</td><td>{{ $departure->start_date?->format('d/m/Y') }}</td></tr>
    <tr><td>Nom voyage</td><td>{{ $voyage?->name }}</td><td>Voyageurs</td><td>{{ $summary['travelers_count'] }}</td></tr>
    <tr></tr>
    <tr><th>ENTREE</th><th>Dossiers</th><th>Personnes</th><th>Montant</th></tr>
    @foreach($entries as $entry)
        <tr><td>{{ $entry['label'] }}</td><td>{{ $entry['dossiers'] }}</td><td>{{ $entry['people'] }}</td><td>{{ $entry['amount'] }}</td></tr>
    @endforeach
    <tr><td>Total entree</td><td></td><td></td><td>{{ $summary['total_entries'] }}</td></tr>
    <tr></tr>
    <tr>
        <th>SORTIE</th>
        @foreach($chargeMethods as $label)<th>{{ $label }}</th>@endforeach
        <th>Total</th>
    </tr>
    @foreach($chargesByType as $row)
        <tr>
            <td>{{ $row['type_name'] }}</td>
            @foreach(array_keys($chargeMethods) as $method)<td>{{ $row['methods'][$method] ?? 0 }}</td>@endforeach
            <td>{{ $row['total'] }}</td>
        </tr>
    @endforeach
    <tr>
        <td>Total sortie</td>
        @foreach(array_keys($chargeMethods) as $method)<td>{{ $totalChargesByMethod[$method] ?? 0 }}</td>@endforeach
        <td>{{ $summary['total_charges'] }}</td>
    </tr>
    <tr></tr>
    <tr><td>Solde</td><td>{{ $summary['balance'] }}</td></tr>
    <tr><td>Rentable</td><td>{{ $summary['is_profitable'] ? 'Oui' : 'Non' }}</td></tr>
</table>
