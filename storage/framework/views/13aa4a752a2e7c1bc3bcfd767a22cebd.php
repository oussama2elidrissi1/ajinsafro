<?php $__env->startSection('title', 'Group Deals — Participants'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">Participants / inscriptions</h4>
            <p class="text-muted mb-0">Tous les inscrits aux offres Group Deal.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="get" class="row g-3 mb-4">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Nom, email ou téléphone">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-outline-primary flex-fill">Filtrer</button>
                    <a href="<?php echo e(route('admin.group-deals.participants.index')); ?>" class="btn btn-light">Réinitialiser</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Participant</th>
                        <th>Offre</th>
                        <th>Personnes</th>
                        <th>Statut</th>
                        <th>Paiement</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $participants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $participant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo e($participant->full_name); ?></div>
                                <div class="text-muted small"><?php echo e($participant->email ?: '—'); ?> · <?php echo e($participant->phone ?: '—'); ?></div>
                            </td>
                            <td>
                                <?php if($participant->groupDeal): ?>
                                    <a href="<?php echo e(route('admin.group-deals.show', $participant->groupDeal)); ?>" class="text-decoration-none"><?php echo e($participant->groupDeal->title); ?></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($participant->participants_count); ?></td>
                            <td>
                                <span class="badge bg-<?php echo e($participant->status === 'confirmed' ? 'success' : ($participant->status === 'paid' ? 'primary' : ($participant->status === 'cancelled' ? 'danger' : 'warning text-dark'))); ?>">
                                    <?php echo e(ucfirst($participant->status)); ?>

                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark"><?php echo e(ucfirst($participant->payment_status)); ?></span>
                            </td>
                            <td class="small text-muted"><?php echo e(optional($participant->joined_at)->format('d/m/Y H:i') ?: '—'); ?></td>
                            <td class="text-end">
                                <?php if($participant->groupDeal): ?>
                                    <a href="<?php echo e(route('admin.group-deals.show', $participant->groupDeal)); ?>" class="btn btn-sm btn-primary">Ouvrir</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aucun participant trouvé.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php echo e($participants->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\group-deals\participants\index.blade.php ENDPATH**/ ?>