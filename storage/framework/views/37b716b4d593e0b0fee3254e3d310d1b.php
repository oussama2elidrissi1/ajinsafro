<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; line-height: 1.35; }
        h1 { font-size: 15px; color: #0e3a5a; margin: 0 0 6px 0; }
        h2 { font-size: 11px; color: #0083c4; margin: 12px 0 6px 0; border-bottom: 1px solid #e6f3fa; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ddd; padding: 5px 7px; text-align: left; }
        th { background: #e6f3fa; }
        .meta { font-size: 9px; color: #666; margin: 4px 0; }
    </style>
</head>
<body>
    <?php
        $showFinancial = $reservation->total_price !== null || $reservation->paid_amount !== null;
        $showPassengerSensitive = $reservation->passengers->contains(fn ($p) => !empty($p->document_number) || !empty($p->birth_date));
    ?>
    <h1>Réservation #<?php echo e($reservation->id); ?></h1>
    <p class="meta">Généré le <?php echo e($generatedAt->format('d/m/Y H:i')); ?></p>

    <p><strong>Statut :</strong> <?php echo e($reservation->status); ?></p>
    <p><strong>Type de prestation :</strong> <?php echo e($reservation->prestation_type ?? '�?"'); ?></p>
    <p><strong>Voyage (Laravel) :</strong> <?php echo e($reservation->tour?->name ?? '�?"'); ?> <?php if($reservation->tour?->wp_post_id): ?> (WP #<?php echo e($reservation->tour->wp_post_id); ?>) <?php endif; ?></p>
    <?php if($reservation->travelDate): ?>
        <p><strong>Date de départ (calendrier) :</strong> <?php echo e(optional($reservation->travelDate->date)->format('d/m/Y') ?? '�?"'); ?></p>
    <?php endif; ?>
    <p><strong>Client :</strong> <?php echo e(trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? ''))); ?></p>
    <?php if($reservation->client?->full_name): ?>
        <p class="meta">Fiche client : <?php echo e($reservation->client->full_name); ?> <?php if($reservation->client->client_code): ?> (<?php echo e($reservation->client->client_code); ?>) <?php endif; ?></p>
    <?php endif; ?>
    <p><strong>Total :</strong> <?php echo e($reservation->total_price !== null ? number_format((float) $reservation->total_price, 2, ',', ' ').' MAD' : '�?"'); ?></p>
    <p><strong>Montant payé :</strong> <?php echo e($reservation->paid_amount !== null ? number_format((float) $reservation->paid_amount, 2, ',', ' ').' MAD' : '�?"'); ?></p>

    <h2>Participants</h2>
    <table>
        <thead>
        <tr>
            <th>Nom</th>
            <th>Type</th>
            <th>Naissance</th>
            <th>Document</th>
        </tr>
        </thead>
        <tbody>
        <?php $__currentLoopData = $reservation->passengers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e(trim(($p->first_name ?? '').' '.($p->last_name ?? ''))); ?></td>
                <td><?php echo e($p->type); ?></td>
                <td><?php echo e(optional($p->birth_date)->format('d/m/Y') ?? '�?"'); ?></td>
                <td><?php echo e($p->document_number ?? '�?"'); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <?php if($reservation->extras->isNotEmpty()): ?>
        <h2>Extras</h2>
        <table>
            <thead>
            <tr><th>Libellé</th><th>Prix</th></tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $reservation->extras; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($e->name); ?></td>
                    <td><?php echo e(number_format((float) $e->price, 2, ',', ' ')); ?> MAD</td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\workspace\pdf\reservation.blade.php ENDPATH**/ ?>