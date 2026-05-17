<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu paiement <?php echo e($payment->id); ?></title>
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
    <div class="muted">Dossier : <?php echo e($reservation->dossier_number ?: 'RES-'.$reservation->id); ?></div>
    <div class="muted">Date d'édition : <?php echo e(now()->format('d/m/Y H:i')); ?></div>

    <table>
        <tr>
            <th width="40%">Client</th>
            <td><?php echo e($reservation->client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—'); ?></td>
        </tr>
        <tr>
            <th>Voyage</th>
            <td><?php echo e($reservation->offer?->name ?? '—'); ?></td>
        </tr>
        <tr>
            <th>Départ</th>
            <td><?php echo e($reservation->departure?->start_date?->format('d/m/Y') ?? '—'); ?></td>
        </tr>
    </table>

    <table>
        <tr>
            <th>Date paiement</th>
            <td><?php echo e($payment->payment_date?->format('d/m/Y') ?? '—'); ?></td>
        </tr>
        <tr>
            <th>Mode de paiement</th>
            <td><?php echo e($payment->payment_method ?: '—'); ?></td>
        </tr>
        <tr>
            <th>Référence</th>
            <td><?php echo e($payment->reference ?: '—'); ?></td>
        </tr>
        <tr>
            <th>Montant</th>
            <td class="right"><?php echo e(number_format((float) $payment->amount, 2, ',', ' ')); ?> DH</td>
        </tr>
        <tr>
            <th>Note</th>
            <td><?php echo e($payment->note ?: '—'); ?></td>
        </tr>
    </table>

    <table>
        <tr>
            <th>Total dossier</th>
            <td class="right"><?php echo e(number_format((float) ($reservation->effective_total_amount ?? $reservation->total_amount ?? 0), 2, ',', ' ')); ?> DH</td>
        </tr>
        <tr>
            <th>Total payé</th>
            <td class="right"><?php echo e(number_format((float) ($reservation->effective_paid_amount ?? $reservation->paid_amount ?? 0), 2, ',', ' ')); ?> DH</td>
        </tr>
        <tr>
            <th>Reste à payer</th>
            <td class="right"><?php echo e(number_format((float) ($reservation->effective_remaining_amount ?? $reservation->remaining_amount ?? 0), 2, ',', ' ')); ?> DH</td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\pdf\payment-receipt.blade.php ENDPATH**/ ?>