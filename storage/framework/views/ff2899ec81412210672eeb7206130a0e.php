<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title mb-0 font-size-18">Tableau de bord</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md rounded bg-primary bg-opacity-10 me-3">
                            <i class="bx bx-calendar-check font-size-24 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Réservations (mois)</h6>
                            <h4 class="mb-0"><?php echo e($reservationsThisMonth); ?> <small class="text-muted">/ <?php echo e($reservationsCount); ?> total</small></h4>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-2">
                    <a href="<?php echo e(route('partner.reservations.index')); ?>" class="text-primary small">Voir tout <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md rounded bg-success bg-opacity-10 me-3">
                            <i class="bx bx-user font-size-24 text-success"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Mes clients</h6>
                            <h4 class="mb-0"><?php echo e($clientsCount); ?></h4>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-2">
                    <a href="<?php echo e(route('partner.clients.index')); ?>" class="text-primary small">Voir tout <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md rounded bg-info bg-opacity-10 me-3">
                            <i class="bx bx-wallet font-size-24 text-info"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Commissions (validées + payées)</h6>
                            <h4 class="mb-0"><?php echo e(number_format($commissionsTotal, 0, ',', ' ')); ?> DH</h4>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-2">
                    <a href="<?php echo e(route('partner.commissions.index')); ?>" class="text-primary small">Détail <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md rounded bg-warning bg-opacity-10 me-3">
                            <i class="bx bx-time-five font-size-24 text-warning"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">En attente</h6>
                            <h4 class="mb-0"><?php echo e(number_format($commissionsPending, 0, ',', ' ')); ?> DH</h4>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-2">
                    <a href="<?php echo e(route('partner.commissions.index')); ?>" class="text-primary small">Voir <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Dernières réservations</h5>
                </div>
                <div class="card-body p-0">
                    <?php if($recentReservations->isEmpty()): ?>
                        <p class="text-muted p-4 mb-0">Aucune réservation.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Voyage</th>
                                        <th>Client</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $recentReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="ps-3"><?php echo e($reservation->tour?->name ?? '—'); ?></td>
                                            <td><?php echo e(trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—'); ?></td>
                                            <td><span class="badge bg-<?php echo e($reservation->status === \App\Models\Reservation::STATUS_VALIDEE ? 'success' : ($reservation->status === \App\Models\Reservation::STATUS_ANNULEE ? 'danger' : 'warning text-dark')); ?>"><?php echo e($reservation->status); ?></span></td>
                                            <td><?php echo e($reservation->created_at?->format('d/m/Y')); ?></td>
                                            <td class="text-end pe-3">
                                                <a href="<?php echo e(route('partner.reservations.show', $reservation)); ?>" class="btn btn-sm btn-outline-primary"><i class="bx bx-show"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if(isset($topVoyages) && $topVoyages->isNotEmpty()): ?>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Top voyages vendus</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php $__currentLoopData = $topVoyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo e($item->tour?->name ?? 'Voyage #'.$item->tour_id); ?>

                                <span class="badge bg-primary rounded-pill"><?php echo e($item->cnt); ?> résa.</span>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\dashboard.blade.php ENDPATH**/ ?>