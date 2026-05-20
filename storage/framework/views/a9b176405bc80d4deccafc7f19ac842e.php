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
        <?php $__currentLoopData = $entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e(optional($entry->calculated_at)->format('d/m/Y')); ?></td>
                <td><?php echo e($entry->agent?->name); ?></td>
                <td><?php echo e($entry->branch?->name); ?></td>
                <td><?php echo e($entry->voyage?->name); ?></td>
                <td><?php echo e($entry->reservation?->departure?->start_date?->format('d/m/Y') ?? $entry->travelDate?->date?->format('d/m/Y')); ?></td>
                <td><?php echo e($entry->client_name); ?></td>
                <td><?php echo e(number_format((float) $entry->reservation_total, 2, '.', '')); ?></td>
                <td><?php echo e(number_format((float) $entry->commission_total, 2, '.', '')); ?></td>
                <td><?php echo e($entry->reservation_status); ?></td>
                <td><?php echo e($entry->payment_status); ?></td>
                <td><?php echo e($entry->commission_status); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\finance\commissions\export-excel.blade.php ENDPATH**/ ?>