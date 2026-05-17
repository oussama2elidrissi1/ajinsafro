<?php $__env->startSection('title', 'Mes réservations'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Mes réservations</h4>
                <a href="<?php echo e(route('partner.reservations.create')); ?>" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Nouvelle réservation</a>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <select name="status" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">Tous les statuts</option>
                                    <option value="EN_COURS" <?php echo e(request('status') === 'EN_COURS' ? 'selected' : ''); ?>>En cours</option>
                                    <option value="VALIDEE" <?php echo e(request('status') === 'VALIDEE' ? 'selected' : ''); ?>>Validée</option>
                                    <option value="ANNULEE" <?php echo e(request('status') === 'ANNULEE' ? 'selected' : ''); ?>>Annulée</option>
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
                                    <th class="ps-3">Offre</th>
                                    <th>Créée par</th>
                                    <th>Agence</th>
                                    <th>Client</th>
                                    <th>Statut</th>
                                    <th>Créée le</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $reservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="ps-3"><?php echo e($reservation->offer?->name ?? '—'); ?></td>
                                        <td><?php echo e($reservation->creator?->name ?? '—'); ?></td>
                                        <td><?php echo e($reservation->agency_label ?? '—'); ?></td>
                                        <td><?php echo e(trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo e($reservation->status === \App\Models\Reservation::STATUS_VALIDEE ? 'success' : ($reservation->status === \App\Models\Reservation::STATUS_ANNULEE ? 'danger' : 'warning text-dark')); ?>"><?php echo e($reservation->status); ?></span>
                                        </td>
                                        <td><?php echo e($reservation->created_at?->format('d/m/Y H:i')); ?></td>
                                        <td class="text-end pe-3">
                                            <a href="<?php echo e(route('partner.reservations.show', $reservation)); ?>" class="btn btn-sm btn-outline-primary" title="Voir"><i class="bx bx-show"></i></a>
                                            <a href="<?php echo e(route('partner.reservations.edit', $reservation)); ?>" class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="bx bx-pencil"></i></a>
                                            <form action="<?php echo e(route('partner.reservations.destroy', $reservation)); ?>" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette réservation ?');">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Aucune réservation.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if(method_exists($reservations, 'links')): ?>
                        <div class="d-flex justify-content-center mt-3"><?php echo e($reservations->links()); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\reservations\index.blade.php ENDPATH**/ ?>