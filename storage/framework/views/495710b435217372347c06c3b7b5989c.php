<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture proforma <?php echo e($reservation->dossier_number ?: $reservation->id); ?></title>
    <style>
        @page { margin: 150px 30px 100px 30px; }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        .invoice-header {
            position: fixed;
            top: -140px;
            left: -30px;
            right: -30px;
            width: auto;
            margin: 0;
            padding: 0;
        }

        .invoice-header img {
            width: 100%;
            height: auto;
            display: block;
        }

        .invoice-footer {
            position: fixed;
            bottom: -90px;
            left: -30px;
            right: -30px;
            width: auto;
            margin: 0;
            padding: 0;
        }

        .invoice-footer img {
            width: 100%;
            height: auto;
            display: block;
        }

        h1.doc-title {
            font-size: 20px;
            font-weight: 800;
            color: #07598f;
            margin: 0 0 6px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .doc-meta {
            text-align: center;
            color: #64748b;
            font-size: 11px;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 800;
            color: #07598f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 4px;
            margin: 14px 0 10px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .info-grid td {
            vertical-align: top;
            padding: 10px 12px;
            border: 1px solid #dbe4ee;
            background: #f8fafc;
        }

        .info-grid td.label {
            background: #eff6ff;
            font-weight: 700;
            color: #07598f;
            width: 1%;
            white-space: nowrap;
            font-size: 10px;
            text-transform: uppercase;
        }

        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.lines th {
            background: #eff6ff;
            color: #07598f;
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
        }

        table.lines td {
            border: 1px solid #dbe4ee;
            padding: 8px 10px;
            vertical-align: middle;
        }

        table.lines tbody tr:nth-child(even) td { background: #f8fafc; }

        .right { text-align: right; }

        .totals-row td {
            font-weight: 800;
            background: #fff7ed !important;
            color: #9a3412;
            border-top: 2px solid #f97316;
        }

        .grand-total td {
            font-weight: 900;
            background: #07598f !important;
            color: #fff;
            font-size: 12px;
        }

        .muted { color: #64748b; }
    </style>
</head>
<body>

    <?php
        $invoiceHeaderPath = \App\Models\Setting::getValue('invoice_header_image');
        $invoiceFooterPath = \App\Models\Setting::getValue('invoice_footer_image');
        $invoiceHeaderLocal = $invoiceHeaderPath ? public_path('storage/' . $invoiceHeaderPath) : null;
        $invoiceFooterLocal = $invoiceFooterPath ? public_path('storage/' . $invoiceFooterPath) : null;
    ?>

    <?php if($invoiceHeaderLocal && file_exists($invoiceHeaderLocal)): ?>
        <div class="invoice-header">
            <img src="<?php echo e($invoiceHeaderLocal); ?>" alt="En-tête">
        </div>
    <?php endif; ?>

    <?php if($invoiceFooterLocal && file_exists($invoiceFooterLocal)): ?>
        <div class="invoice-footer">
            <img src="<?php echo e($invoiceFooterLocal); ?>" alt="Pied de page">
        </div>
    <?php endif; ?>

    <!-- ===== TITLE ===== -->
    <h1 class="doc-title">Facture proforma</h1>
    <div class="doc-meta">
        Dossier : <?php echo e($reservation->dossier_number ?: 'RES-'.$reservation->id); ?> &nbsp;|&nbsp;
        &Eacute;dit&eacute;e le <?php echo e(now()->format('d/m/Y H:i')); ?>

    </div>

    <!-- ===== CLIENT / OFFRE ===== -->
    <table class="info-grid">
        <tr>
            <td class="label">Client</td>
            <td>
                <div><strong><?php echo e($reservation->client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '&mdash;'); ?></strong></div>
                <div><?php echo e($reservation->client?->phone ?: $reservation->client_phone ?: '&mdash;'); ?></div>
                <div><?php echo e($reservation->client?->email ?: $reservation->client_email ?: '&mdash;'); ?></div>
            </td>
            <td class="label">Offre</td>
            <td>
                <div><strong><?php echo e($reservation->offer?->name ?? '&mdash;'); ?></strong></div>
                <div>D&eacute;part : <?php echo e($reservation->departure?->start_date?->format('d/m/Y') ?? '&mdash;'); ?></div>
                <div>Retour : <?php echo e($reservation->departure?->end_date?->format('d/m/Y') ?? '&mdash;'); ?></div>
            </td>
        </tr>
    </table>

    <!-- ===== DETAIL FINANCIER ===== -->
    <div class="section-title">D&eacute;tail financier</div>
    <table class="lines">
        <tbody>
            <tr>
                <td>Total base</td>
                <td class="right"><?php echo e(number_format((float) ($reservation->total_base ?? 0), 2, ',', ' ')); ?> DH</td>
            </tr>
            <tr>
                <td>Suppl&eacute;ments chambres</td>
                <td class="right"><?php echo e(number_format((float) ($reservation->room_supplement_total ?? 0), 2, ',', ' ')); ?> DH</td>
            </tr>
            <tr>
                <td>Extras</td>
                <td class="right"><?php echo e(number_format((float) ($reservation->extras_total ?? 0), 2, ',', ' ')); ?> DH</td>
            </tr>
            <tr class="totals-row">
                <td>Total dossier</td>
                <td class="right"><?php echo e(number_format((float) ($reservation->total_amount ?? 0), 2, ',', ' ')); ?> DH</td>
            </tr>
            <tr>
                <td>D&eacute;j&agrave; pay&eacute;</td>
                <td class="right"><?php echo e(number_format((float) ($reservation->paid_amount ?? 0), 2, ',', ' ')); ?> DH</td>
            </tr>
            <tr class="grand-total">
                <td>Reste &agrave; payer</td>
                <td class="right"><?php echo e(number_format((float) ($reservation->remaining_amount ?? 0), 2, ',', ' ')); ?> DH</td>
            </tr>
        </tbody>
    </table>

    <!-- ===== PAIEMENTS ===== -->
    <div class="section-title">Paiements enregistr&eacute;s</div>
    <table class="lines">
        <thead>
            <tr>
                <th>Date</th>
                <th>Mode</th>
                <th>R&eacute;f&eacute;rence</th>
                <th class="right">Montant</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $reservation->payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($payment->payment_date?->format('d/m/Y') ?? '&mdash;'); ?></td>
                    <td><?php echo e($payment->payment_method ?: '&mdash;'); ?></td>
                    <td><?php echo e($payment->reference ?: '&mdash;'); ?></td>
                    <td class="right"><?php echo e(number_format((float) $payment->amount, 2, ',', ' ')); ?> DH</td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="4" class="muted">Aucun paiement enregistr&eacute;.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\pdf\invoice.blade.php ENDPATH**/ ?>