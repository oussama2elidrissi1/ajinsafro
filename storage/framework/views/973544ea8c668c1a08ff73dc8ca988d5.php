

<?php $__env->startSection('title', 'Detail commission'); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        .commission-detail-grid { display: grid; grid-template-columns: 1.2fr .8fr; gap: 1.25rem; }
        .commission-card { background: #fff; border: 1px solid #e5eef5; border-radius: 18px; padding: 1.25rem; box-shadow: 0 10px 26px rgba(15, 35, 95, .05); }
        .commission-card dt { color: #6b7280; font-size: .82rem; text-transform: uppercase; letter-spacing: .06em; }
        .commission-card dd { color: #0e3a5a; font-weight: 700; margin-bottom: .9rem; }
        .timeline-item { position: relative; padding-left: 1.25rem; margin-bottom: 1rem; }
        .timeline-item::before { content: ''; position: absolute; left: 0; top: .4rem; width: 8px; height: 8px; border-radius: 999px; background: #0083c4; }
        @media (max-width: 991px) { .commission-detail-grid { grid-template-columns: 1fr; } }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
            <div>
                <h2 class="mb-1">Detail de commission</h2>
                <p class="text-muted mb-0">Reservation #<?php echo e($entry->reservation_id); ?> - <?php echo e($entry->client_name ?: 'Client non renseigne'); ?></p>
            </div>
            <a href="<?php echo e(route('admin.agent.commissions.index')); ?>" class="btn btn-outline-secondary">Retour</a>
        </div>

        <div class="commission-detail-grid">
            <div class="commission-card">
                <h5 class="mb-4">Synthese</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Voyage</dt><dd class="col-sm-8"><?php echo e($entry->voyage?->name ?: 'Voyage non renseigne'); ?></dd>
                    <dt class="col-sm-4">Date depart</dt><dd class="col-sm-8"><?php echo e($entry->departureDateLabel() ?: '�?"'); ?></dd>
                    <dt class="col-sm-4">Client</dt><dd class="col-sm-8"><?php echo e($entry->client_name ?: 'Client non renseigne'); ?></dd>
                    <dt class="col-sm-4">Montant reservation</dt><dd class="col-sm-8"><?php echo e(number_format((float) $entry->reservation_total, 2, ',', ' ')); ?> DH</dd>
                    <dt class="col-sm-4">Commission adulte</dt><dd class="col-sm-8"><?php echo e(number_format((float) $entry->commission_adult, 2, ',', ' ')); ?> DH</dd>
                    <dt class="col-sm-4">Commission enfant</dt><dd class="col-sm-8"><?php echo e(number_format((float) $entry->commission_child, 2, ',', ' ')); ?> DH</dd>
                    <dt class="col-sm-4">Commission bebe</dt><dd class="col-sm-8"><?php echo e(number_format((float) $entry->commission_baby, 2, ',', ' ')); ?> DH</dd>
                    <dt class="col-sm-4">Commission totale</dt><dd class="col-sm-8"><?php echo e(number_format((float) $entry->commission_total, 2, ',', ' ')); ?> DH</dd>
                    <dt class="col-sm-4">Statut actuel</dt><dd class="col-sm-8"><?php echo e($entry->statusLabelFr()); ?></dd>
                    <dt class="col-sm-4">Statut paiement</dt><dd class="col-sm-8"><?php echo e($entry->reservation?->paymentStatusLabelFr() ?? ucfirst((string) $entry->payment_status)); ?></dd>
                </dl>
            </div>

            <div class="commission-card">
                <h5 class="mb-4">Historique</h5>
                <?php $__empty_1 = true; $__currentLoopData = $entry->logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="timeline-item">
                        <div class="fw-semibold"><?php echo e(ucfirst(str_replace('_', ' ', $log->action))); ?></div>
                        <div class="text-muted small"><?php echo e(optional($log->created_at)->format('d/m/Y H:i')); ?> <?php if($log->creator): ?> - <?php echo e($log->creator->name); ?> <?php endif; ?></div>
                        <?php if($log->description): ?>
                            <div class="mt-1"><?php echo e($log->description); ?></div>
                        <?php endif; ?>
                        <div class="small text-muted mt-1">
                            <?php if($log->old_status || $log->new_status): ?>
                                <?php echo e($log->old_status ?: '�?"'); ?> �?' <?php echo e($log->new_status ?: '�?"'); ?>

                            <?php endif; ?>
                            <?php if($log->old_amount !== null || $log->new_amount !== null): ?>
                                | <?php echo e(number_format((float) ($log->old_amount ?? 0), 2, ',', ' ')); ?> DH �?' <?php echo e(number_format((float) ($log->new_amount ?? 0), 2, ',', ' ')); ?> DH
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-muted">Aucun historique disponible.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.master-ajinsafro', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\agent-commissions\show.blade.php ENDPATH**/ ?>