

<?php $__env->startSection('title', 'Group Deals �?" Tarifs par palier'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">Tarifs par palier</h4>
            <p class="text-muted mb-0">Tous les paliers de prix des offres Group Deal.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="get" class="row g-3 mb-4">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Offre Group Deal">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-outline-primary flex-fill">Filtrer</button>
                    <a href="<?php echo e(route('admin.group-deals.tiers.index')); ?>" class="btn btn-light">Réinitialiser</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Offre</th>
                        <th>Label</th>
                        <th>Min. participants</th>
                        <th>Max. participants</th>
                        <th>Prix / personne</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $tiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <?php if($tier->groupDeal): ?>
                                    <a href="<?php echo e(route('admin.group-deals.show', $tier->groupDeal)); ?>" class="text-decoration-none fw-semibold"><?php echo e($tier->groupDeal->title); ?></a>
                                <?php elseif($tier->voyage): ?>
                                    <span class="fw-semibold"><?php echo e($tier->voyage->name); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">�?"</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($tier->label ?: '�?"'); ?></td>
                            <td><?php echo e($tier->min_participants ?? $tier->min_people ?? '�?"'); ?></td>
                            <td><?php echo e($tier->max_people ?: '�?"'); ?></td>
                            <td class="fw-semibold"><?php echo e($tier->price_per_person ? number_format((float) $tier->price_per_person, 2, ',', ' ') . ' DH' : '�?"'); ?></td>
                            <td class="text-end">
                                <?php if($tier->groupDeal): ?>
                                    <a href="<?php echo e(route('admin.group-deals.show', $tier->groupDeal)); ?>" class="btn btn-sm btn-primary">Ouvrir</a>
                                <?php elseif($tier->voyage): ?>
                                    <a href="<?php echo e(route('admin.group-deals.trips.show', $tier->voyage)); ?>" class="btn btn-sm btn-primary">Ouvrir</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Aucun palier trouvé.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php echo e($tiers->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\group-deals\tiers\index.blade.php ENDPATH**/ ?>