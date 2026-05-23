

<?php $__env->startSection('title', 'Detail commission'); ?>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Detail commission agent</h4>
                <p class="text-muted mb-0">Reservation #<?php echo e($entry->reservation_id); ?> - <?php echo e($entry->agent?->name ?: 'Agent non renseigne'); ?></p>
            </div>
            <a href="<?php echo e(route('admin.finance.commissions')); ?>" class="btn btn-outline-secondary">Retour</a>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="mb-4">Synthese</h5>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Agent</dt><dd class="col-sm-8 fw-semibold"><?php echo e($entry->agent?->name ?: 'Agent non renseigne'); ?></dd>
                            <dt class="col-sm-4 text-muted">Point de vente</dt><dd class="col-sm-8 fw-semibold"><?php echo e($entry->branch?->name ?: 'Non renseigne'); ?></dd>
                            <dt class="col-sm-4 text-muted">Voyage</dt><dd class="col-sm-8 fw-semibold"><?php echo e($entry->voyage?->name ?: 'Voyage non renseigne'); ?></dd>
                            <dt class="col-sm-4 text-muted">Date depart</dt><dd class="col-sm-8 fw-semibold"><?php echo e($entry->departureDateLabel() ?: '�?"'); ?></dd>
                            <dt class="col-sm-4 text-muted">Client</dt><dd class="col-sm-8 fw-semibold"><?php echo e($entry->client_name ?: 'Client non renseigne'); ?></dd>
                            <dt class="col-sm-4 text-muted">Montant reservation</dt><dd class="col-sm-8 fw-semibold"><?php echo e(number_format((float) $entry->reservation_total, 2, ',', ' ')); ?> DH</dd>
                            <dt class="col-sm-4 text-muted">Base commission</dt><dd class="col-sm-8 fw-semibold"><?php echo e(number_format((float) $entry->commission_base_amount, 2, ',', ' ')); ?> DH</dd>
                            <dt class="col-sm-4 text-muted">Commission adulte</dt><dd class="col-sm-8 fw-semibold"><?php echo e(number_format((float) $entry->commission_adult, 2, ',', ' ')); ?> DH</dd>
                            <dt class="col-sm-4 text-muted">Commission enfant</dt><dd class="col-sm-8 fw-semibold"><?php echo e(number_format((float) $entry->commission_child, 2, ',', ' ')); ?> DH</dd>
                            <dt class="col-sm-4 text-muted">Commission bebe</dt><dd class="col-sm-8 fw-semibold"><?php echo e(number_format((float) $entry->commission_baby, 2, ',', ' ')); ?> DH</dd>
                            <dt class="col-sm-4 text-muted">Commission totale</dt><dd class="col-sm-8 fw-semibold"><?php echo e(number_format((float) $entry->commission_total, 2, ',', ' ')); ?> DH</dd>
                            <dt class="col-sm-4 text-muted">Statut commission</dt><dd class="col-sm-8 fw-semibold"><?php echo e($entry->statusLabelFr()); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Actions finance</h5>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <form method="POST" action="<?php echo e(route('admin.finance.commissions.confirm', $entry)); ?>"><?php echo csrf_field(); ?><button class="btn btn-outline-primary">Marquer confirme</button></form>
                            <form method="POST" action="<?php echo e(route('admin.finance.commissions.payable', $entry)); ?>"><?php echo csrf_field(); ?><button class="btn btn-outline-info">Marquer payable</button></form>
                            <form method="POST" action="<?php echo e(route('admin.finance.commissions.paid', $entry)); ?>"><?php echo csrf_field(); ?><button class="btn btn-outline-success">Marquer paye</button></form>
                            <form method="POST" action="<?php echo e(route('admin.finance.commissions.cancel', $entry)); ?>"><?php echo csrf_field(); ?><button class="btn btn-outline-danger">Annuler</button></form>
                            <form method="POST" action="<?php echo e(route('admin.finance.commissions.reverse', $entry)); ?>"><?php echo csrf_field(); ?><button class="btn btn-outline-dark">Reverser</button></form>
                        </div>

                        <form method="POST" action="<?php echo e(route('admin.finance.commissions.adjust', $entry)); ?>" class="row g-3">
                            <?php echo csrf_field(); ?>
                            <div class="col-12"><label class="form-label">Commission totale</label><input type="number" step="0.01" min="0" name="commission_total" class="form-control" value="<?php echo e(old('commission_total', $entry->commission_total)); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Adulte</label><input type="number" step="0.01" min="0" name="commission_adult" class="form-control" value="<?php echo e(old('commission_adult', $entry->commission_adult)); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Enfant</label><input type="number" step="0.01" min="0" name="commission_child" class="form-control" value="<?php echo e(old('commission_child', $entry->commission_child)); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Bebe</label><input type="number" step="0.01" min="0" name="commission_baby" class="form-control" value="<?php echo e(old('commission_baby', $entry->commission_baby)); ?>"></div>
                            <div class="col-12"><label class="form-label">Note</label><textarea name="notes" rows="3" class="form-control"><?php echo e(old('notes', $entry->notes)); ?></textarea></div>
                            <div class="col-12 d-grid"><button class="btn btn-primary">Ajouter ajustement manuel</button></div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Historique</h5>
                        <?php $__empty_1 = true; $__currentLoopData = $entry->logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="border-start border-3 ps-3 mb-3">
                                <div class="fw-semibold"><?php echo e(ucfirst(str_replace('_', ' ', $log->action))); ?></div>
                                <div class="small text-muted"><?php echo e(optional($log->created_at)->format('d/m/Y H:i')); ?> <?php if($log->creator): ?> - <?php echo e($log->creator->name); ?> <?php endif; ?></div>
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
        </div>
    </div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\finance\commissions\show.blade.php ENDPATH**/ ?>