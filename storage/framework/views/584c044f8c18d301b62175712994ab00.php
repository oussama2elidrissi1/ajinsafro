
<?php $__env->startSection('title', 'WordPress - Activités'); ?>
<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Catalogue activités</h4>
                <a href="<?php echo e(route('admin.wordpress.activities.create')); ?>" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Nouvelle activité
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
                    <input type="text" name="search" class="form-control" value="<?php echo e($filters['search']); ?>" placeholder="Nom, slug, lieu, type">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="publish" <?php if($filters['status'] === 'publish'): echo 'selected'; endif; ?>>Publié</option>
                        <option value="draft" <?php if($filters['status'] === 'draft'): echo 'selected'; endif; ?>>Brouillon</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type_activity" class="form-select">
                        <option value="">Tous les types</option>
                        <?php $__currentLoopData = $typeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($option); ?>" <?php if($filters['type'] === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
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
                            <th>Nom</th>
                            <th>Lieu</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Durée</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $detail = $activity->stActivity;
                                $thumb = $media->getFeaturedImageUrlVerified($activity->ID);
                            ?>
                            <tr>
                                <td>
                                    <?php if($thumb): ?>
                                        <img src="<?php echo e($thumb); ?>" alt="" class="rounded" style="width:50px;height:50px;object-fit:cover;">
                                    <?php else: ?>
                                        <span class="text-muted">�?"</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo e($activity->post_title); ?></div>
                                    <div class="text-muted small"><?php echo e($activity->post_name); ?></div>
                                </td>
                                <td><?php echo e($detail->address ?? ($activity->getMeta('aj_activity_place_text') ?: '�?"')); ?></td>
                                <td><?php echo e($detail->type_activity ?: ($activity->getMeta('aj_activity_category') ?: '�?"')); ?></td>
                                <td>
                                    <?php if($detail && ($detail->adult_price || $detail->min_price)): ?>
                                        <?php echo e(number_format((float) ($detail->adult_price ?: $detail->min_price), 0, ',', ' ')); ?> MAD
                                    <?php else: ?>
                                        �?"
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($detail->duration ?? '�?"'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo e($activity->post_status === 'publish' ? 'success' : 'secondary'); ?>">
                                        <?php echo e($activity->post_status === 'publish' ? 'Publié' : 'Brouillon'); ?>

                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('admin.wordpress.activities.edit', $activity)); ?>" class="btn btn-sm btn-soft-primary">Modifier</a>
                                    <form action="<?php echo e(route('admin.wordpress.activities.destroy', $activity)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Déplacer cette activité dans la corbeille ?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-soft-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Aucune activité trouvée.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3"><?php echo e($activities->links()); ?></div>
        </div>
    </div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\wordpress\activities\index.blade.php ENDPATH**/ ?>