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
    <div class="meta">Genere le <?php echo e($generatedAt->format('d/m/Y H:i')); ?></div>
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
            <?php $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e(optional($entry->calculated_at)->format('d/m/Y')); ?></td>
                    <td><?php echo e($entry->agent?->name); ?></td>
                    <td><?php echo e($entry->branch?->name); ?></td>
                    <td><?php echo e($entry->voyage?->name); ?></td>
                    <td><?php echo e($entry->reservation?->departure?->start_date?->format('d/m/Y') ?? $entry->travelDate?->date?->format('d/m/Y')); ?></td>
                    <td><?php echo e($entry->client_name); ?></td>
                    <td><?php echo e(number_format((float) $entry->reservation_total, 2, ',', ' ')); ?> DH</td>
                    <td><?php echo e(number_format((float) $entry->commission_total, 2, ',', ' ')); ?> DH</td>
                    <td><?php echo e($entry->statusLabelFr()); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</body>
</html>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\finance\commissions\export-pdf.blade.php ENDPATH**/ ?>