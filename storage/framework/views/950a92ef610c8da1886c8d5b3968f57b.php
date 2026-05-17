<?php $__env->startSection('title'); ?>
    Offres activités
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Offres activités</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item active">Offres activités</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 text-end">
            <a href="<?php echo e(route('admin.activity-offers.create')); ?>" class="btn btn-success">
                <i class="bx bx-plus"></i> Nouvelle offre
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <?php if(session('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show"><?php echo e(session('success')); ?>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if(session('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show"><?php echo e(session('error')); ?>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Titre</th>
                                    <th>Pays</th>
                                    <th>Ville</th>
                                    <th>Catégorie</th>
                                    <th>Durée</th>
                                    <th>Prix</th>
                                    <th>Dispo</th>
                                    <th>Vedette</th>
                                    <th>Actif</th>
                                    <th>Ordre</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $offers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $offer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($offer->id); ?></td>
                                        <td><?php echo e($offer->title); ?></td>
                                        <td><?php echo e($offer->country); ?></td>
                                        <td><?php echo e($offer->city); ?></td>
                                        <td><?php echo e($offer->category); ?></td>
                                        <td><?php echo e($offer->duration_label ?? '-'); ?></td>
                                        <td><?php echo e(number_format($offer->price_from, 2)); ?> <?php echo e($offer->currency); ?></td>
                                        <td><?php echo e($offer->availability_label); ?></td>
                                        <td>
                                            <?php if($offer->is_featured): ?>
                                                <span class="badge bg-warning">Oui</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Non</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($offer->is_active): ?>
                                                <span class="badge bg-success">Oui</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Non</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($offer->sort_order); ?></td>
                                        <td class="d-flex gap-1">
                                            <a href="<?php echo e(route('admin.activity-offers.edit', $offer)); ?>" class="btn btn-sm btn-primary">Modifier</a>
                                            <form method="POST" action="<?php echo e(route('admin.activity-offers.destroy', $offer)); ?>" onsubmit="return confirm('Supprimer cette offre ?');">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="12" class="text-center text-muted">Aucune offre activité.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if($offers->hasPages()): ?>
                        <div class="d-flex justify-content-end mt-2">
                            <?php echo e($offers->links()); ?>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\activity-offers\index.blade.php ENDPATH**/ ?>