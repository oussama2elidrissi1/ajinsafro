<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; line-height: 1.35; }
        h1 { font-size: 15px; color: #0e3a5a; margin: 0 0 6px 0; }
        h2 { font-size: 11px; color: #0083c4; margin: 14px 0 6px 0; border-bottom: 1px solid #e6f3fa; padding-bottom: 4px; }
        .muted { color: #666; font-size: 9px; }
        .box { background: #f7f9fc; border: 1px solid #e6f3fa; padding: 8px 10px; margin: 10px 0; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #e6f3fa; color: #0e3a5a; font-size: 9px; text-transform: uppercase; }
        .stat-ok { color: #047857; font-weight: bold; }
        .stat-wait { color: #b45309; font-weight: bold; }
        .stat-off { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Fiche prestation — {{ $prestationDisplayTitle }}</h1>
    <p class="muted">
        Réf. Laravel voyages #{{ $voyage->id }}
        @if($voyage->wp_post_id) · WordPress post #{{ $voyage->wp_post_id }} @endif
        @if($travelDateLabel) · Départ {{ $travelDateLabel }} @elseif($travelDateId) · travel_date_id {{ $travelDateId }} @endif
    </p>
    @if(!empty($wpTourTitle) && $wpTourTitle !== $voyage->name)
        <p class="muted">Nom fiche Laravel : {{ $voyage->name }}</p>
    @endif
    <p>Généré le {{ $generatedAt->format('d/m/Y H:i') }}</p>

    @if($reservations->isEmpty())
        <div class="box">
            Aucune réservation pour cette prestation avec les filtres appliqués.
        </div>
    @else
        <h2>Synthèse des dossiers</h2>
        <table>
            <thead>
            <tr>
                <th># Résa.</th>
                <th>Statut</th>
                <th>Client</th>
                <th>Type prestation</th>
                <th>Pax</th>
                <th>Total</th>
                <th>Payé</th>
            </tr>
            </thead>
            <tbody>
            @foreach($reservations as $r)
                @php
                    $st = $r->status;
                    $stClass = $st === \App\Models\Reservation::STATUS_VALIDEE ? 'stat-ok' : ($st === \App\Models\Reservation::STATUS_ANNULEE ? 'stat-off' : 'stat-wait');
                @endphp
                <tr>
                    <td>{{ $r->id }}</td>
                    <td class="{{ $stClass }}">{{ $st }}</td>
                    <td>{{ trim(($r->client_first_name ?? '').' '.($r->client_last_name ?? '')) ?: ($r->client?->full_name ?? '—') }}</td>
                    <td>{{ $r->prestation_type ?? '—' }}</td>
                    <td>{{ $r->passengers->count() }}</td>
                    <td>{{ $r->total_price !== null ? number_format((float) $r->total_price, 2, ',', ' ').' MAD' : '—' }}</td>
                    <td>{{ $r->paid_amount !== null ? number_format((float) $r->paid_amount, 2, ',', ' ').' MAD' : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <h2>Détail des participants</h2>
        <table>
            <thead>
            <tr>
                <th>Résa.</th>
                <th>Statut dossier</th>
                <th>Nom participant</th>
                <th>Type</th>
                <th>Naissance</th>
                <th>Document</th>
            </tr>
            </thead>
            <tbody>
            @foreach($reservations as $r)
                @forelse($r->passengers as $p)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td>{{ $r->status }}</td>
                        <td>{{ trim(($p->first_name ?? '').' '.($p->last_name ?? '')) }}</td>
                        <td>{{ $p->type }}</td>
                        <td>{{ optional($p->birth_date)->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $p->document_number ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td colspan="5" style="font-style: italic; color: #666;">Aucun passager enregistré sur ce dossier.</td>
                    </tr>
                @endforelse
            @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
