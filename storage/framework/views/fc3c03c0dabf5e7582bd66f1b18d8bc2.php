<?php $__env->startSection('title', 'Commissions'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title mb-0 font-size-18">Mes commissions</h4>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h6 class="text-muted">Validées (en attente de paiement)</h6>
                    <h4 class="text-primary"><?php echo e(number_format($totalValidated, 0, ',', ' ')); ?> DH</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <h6 class="text-muted">Payées</h6>
                    <h4 class="text-success"><?php echo e(number_format($totalPaid, 0, ',', ' ')); ?> DH</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <h6 class="text-muted">En attente (résa. non confirmée)</h6>
                    <h4 class="text-warning"><?php echo e(number_format($totalPending, 0, ',', ' ')); ?> DH</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <select name="status" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tous les statuts</option>
                            <option value="calculated" <?php echo e(request('status') === 'calculated' ? 'selected' : ''); ?>>Calculée</option>
                            <option value="pending" <?php echo e(request('status') === 'pending' ? 'selected' : ''); ?>>En attente</option>
                            <option value="validated" <?php echo e(request('status') === 'validated' ? 'selected' : ''); ?>>Validée</option>
                            <option value="paid" <?php echo e(request('status') === 'paid' ? 'selected' : ''); ?>>Payée</option>
                            <option value="cancelled" <?php echo e(request('status') === 'cancelled' ? 'selected' : ''); ?>>Annulée</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Réservation / Voyage</th>
                            <th>Montant résa.</th>
                            <th>Commission</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $commissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="ps-3">
                                    <a href="<?php echo e(route('partner.reservations.show', $c->reservation)); ?>">#<?php echo e($c->reservation_id); ?></a>
                                    <?php if($c->reservation && $c->reservation->tour): ?>
                                        <br><span class="text-muted small"><?php echo e($c->reservation->tour->name); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e(number_format($c->reservation_total, 0, ',', ' ')); ?> DH</td>
                                <td><strong><?php echo e(number_format($c->amount, 0, ',', ' ')); ?> DH</strong></td>
                                <td>
                                    <?php
                                        $badge = match($c->status) {
                                            'validated' => 'badge bg-primary',
                                            'paid' => 'badge bg-success',
                                            'cancelled' => 'badge bg-danger',
                                            default => 'badge bg-warning text-dark',
                                        };
                                    ?>
                                    <span class="<?php echo e($badge); ?>"><?php echo e($c->status); ?></span>
                                </td>
                                <td><?php echo e($c->created_at?->format('d/m/Y')); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Aucune commission.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(method_exists($commissions, 'links')): ?>
                <div class="d-flex justify-content-center mt-3"><?php echo e($commissions->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\commissions\index.blade.php ENDPATH**/ ?>