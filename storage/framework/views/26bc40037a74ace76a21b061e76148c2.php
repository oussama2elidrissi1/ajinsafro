<?php $__env->startSection('title', 'Revendeurs'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Revendeurs</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.partners.index')); ?>">Réseau partenaires</a></li>
                        <li class="breadcrumb-item active">Revendeurs</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo e(session('error')); ?>

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
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Raison sociale, responsable, email..." value="<?php echo e(request('search')); ?>" style="min-width: 220px;">
                            </div>
                            <div class="col-auto">
                                <select name="status" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">Tous les statuts</option>
                                    <option value="pending" <?php echo e(request('status') === 'pending' ? 'selected' : ''); ?>>En attente</option>
                                    <option value="validated" <?php echo e(request('status') === 'validated' ? 'selected' : ''); ?>>Validé</option>
                                    <option value="rejected" <?php echo e(request('status') === 'rejected' ? 'selected' : ''); ?>>Refusé</option>
                                    <option value="suspended" <?php echo e(request('status') === 'suspended' ? 'selected' : ''); ?>>Suspendu</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search-alt"></i> Filtrer</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Raison sociale / Nom</th>
                                    <th>Responsable</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Date d'inscription</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $partners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $partner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($partner->id); ?></td>
                                        <td>
                                            <strong><?php echo e($partner->raison_sociale); ?></strong>
                                            <?php if($partner->nom_commercial && $partner->nom_commercial !== $partner->raison_sociale): ?>
                                                <span class="text-muted small d-block"><?php echo e($partner->nom_commercial); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($partner->nom_responsable); ?></td>
                                        <td><?php echo e($partner->email); ?></td>
                                        <td><?php echo e($partner->telephone ?? '—'); ?></td>
                                        <td><?php echo e($partner->created_at?->format('d/m/Y H:i')); ?></td>
                                        <td>
                                            <?php
                                                $badge = match($partner->status) {
                                                    'pending' => 'badge bg-warning text-dark',
                                                    'validated' => 'badge bg-success',
                                                    'rejected' => 'badge bg-danger',
                                                    'suspended' => 'badge bg-secondary',
                                                    default => 'badge bg-light text-dark',
                                                };
                                            ?>
                                            <span class="<?php echo e($badge); ?>"><?php echo e($partner->status); ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?php echo e(route('admin.partner-accounts.show', $partner)); ?>" class="btn btn-sm btn-outline-primary" title="Voir"><i class="bx bx-show"></i></a>
                                            <?php if($partner->isPending()): ?>
                                                <form action="<?php echo e(route('admin.partner-accounts.validate', $partner)); ?>" method="post" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-success" title="Valider"><i class="bx bx-check"></i></button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Refuser" data-bs-toggle="modal" data-bs-target="#reject-modal-<?php echo e($partner->id); ?>"><i class="bx bx-x"></i></button>
                                                <?php echo $__env->make('admin.partner-accounts._reject_modal', ['partner' => $partner], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Aucun compte partenaire.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if(method_exists($partners, 'links')): ?>
                        <div class="d-flex justify-content-center mt-3"><?php echo e($partners->links()); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partner-accounts\index.blade.php ENDPATH**/ ?>