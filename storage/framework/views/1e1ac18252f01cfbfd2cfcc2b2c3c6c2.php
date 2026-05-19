<?php $__env->startSection('title', 'WordPress - Transferts'); ?>
<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Catalogue transferts</h4>
                <a href="<?php echo e(route('admin.wordpress.transfers.create')); ?>" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Nouveau transfert
                </a>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" value="<?php echo e($filters['search']); ?>" placeholder="Nom, départ, arrivée, type, ville">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="publish" <?php if($filters['status'] === 'publish'): echo 'selected'; endif; ?>>Publié</option>
                        <option value="draft" <?php if($filters['status'] === 'draft'): echo 'selected'; endif; ?>>Brouillon</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="city" class="form-select">
                        <option value="">Toutes les villes</option>
                        <?php $__currentLoopData = $cityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($option); ?>" <?php if($filters['city'] === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-light w-100">Filtrer</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Service</th>
                            <th>Ville</th>
                            <th>Trajet</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $transfers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transfer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $detail = $transfer->stCar;
                                $thumb = $media->getFeaturedImageUrlVerified($transfer->ID);
                            ?>
                            <tr>
                                <td>
                                    <?php if($thumb): ?>
                                        <img src="<?php echo e($thumb); ?>" alt="" class="rounded" style="width:50px;height:50px;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo e($transfer->post_title); ?></div>
                                    <div class="text-muted small"><?php echo e($transfer->post_name); ?></div>
                                </td>
                                <td><?php echo e($detail->cars_address ?: '—'); ?></td>
                                <td>
                                    <?php echo e($transfer->getMeta('aj_transfer_from') ?: '—'); ?>

                                    →
                                    <?php echo e($transfer->getMeta('aj_transfer_to') ?: '—'); ?>

                                </td>
                                <td><?php echo e($transfer->getMeta('aj_transfer_vehicle_type') ?: ($transfer->getMeta('aj_transfer_type') ?: '—')); ?></td>
                                <td>
                                    <?php if($detail && ($detail->cars_price || $detail->min_price)): ?>
                                        <?php echo e(number_format((float) ($detail->cars_price ?: $detail->min_price), 0, ',', ' ')); ?> MAD
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo e($transfer->post_status === 'publish' ? 'success' : 'secondary'); ?>">
                                        <?php echo e($transfer->post_status === 'publish' ? 'Publié' : 'Brouillon'); ?>

                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('admin.wordpress.transfers.edit', $transfer)); ?>" class="btn btn-sm btn-soft-primary">Modifier</a>
                                    <form action="<?php echo e(route('admin.wordpress.transfers.destroy', $transfer)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Déplacer ce transfert dans la corbeille ?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-soft-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Aucun transfert trouvé.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3"><?php echo e($transfers->links()); ?></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\wordpress\transfers\index.blade.php ENDPATH**/ ?>