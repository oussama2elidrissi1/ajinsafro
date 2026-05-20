
<?php $__env->startSection('title'); ?> Group Deals �?" Voyages <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="page-title mb-0 font-size-18">Group Deals �?" Voyages</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                    <li class="breadcrumb-item active">Group Deals</li>
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

<div class="card border-0 shadow-sm">
    <div class="card-body">
        
        <form method="get" class="row g-2 align-items-end mb-4">
            <div class="col-md-4">
                <label class="form-label small mb-1">Recherche</label>
                <input type="search" name="q" class="form-control form-control-sm"
                       value="<?php echo e(request('q')); ?>" placeholder="Nom du voyage�?�">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                <a href="<?php echo e(route('admin.group-deals.trips.index')); ?>" class="btn btn-outline-secondary btn-sm ms-1">Réinitialiser</a>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><?php echo e($voyages->total()); ?> voyage(s) avec Group Deal activé</h5>
            <a href="<?php echo e(route('admin.circuits.voyages.index')); ?>" class="btn btn-outline-primary btn-sm">
                <i class="bx bx-link-external me-1"></i> Gérer les voyages
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Visuel</th>
                        <th>Voyage</th>
                        <th>Destination</th>
                        <th class="text-center">Paliers</th>
                        <th class="text-center">Départs GD</th>
                        <th class="text-center">Garantis</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $gdDepartures = $voyage->departures;
                        $guaranteed   = $gdDepartures->where('is_guaranteed', true)->count();
                    ?>
                    <tr>
                        <td>
                            <div class="aj-thumb">
                                <?php if($voyage->featured_image_url): ?>
                                    <img src="<?php echo e($voyage->featured_image_url); ?>" alt="<?php echo e($voyage->name); ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                                <?php else: ?>
                                    <div class="aj-thumb-placeholder" style="width:48px;height:48px;border-radius:6px;">Ajinsafro</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo e($voyage->name); ?></strong>
                            <?php if($voyage->price_from): ?>
                                <br><small class="text-muted">à partir de <?php echo e(number_format($voyage->price_from, 0, ',', ' ')); ?> �,�</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?php echo e($voyage->destination ?? '�?"'); ?></td>
                        <td class="text-center">
                            <?php if($voyage->pricingTiers->count()): ?>
                                <span class="badge bg-info text-dark"><?php echo e($voyage->pricingTiers->count()); ?> palier(s)</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Aucun</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?php echo e($gdDepartures->count()); ?></td>
                        <td class="text-center">
                            <?php if($guaranteed > 0): ?>
                                <span class="badge bg-success"><?php echo e($guaranteed); ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $s = $voyage->status ?? 'publish'; ?>
                            <span class="badge bg-<?php echo e($s === 'publish' ? 'success' : ($s === 'draft' ? 'secondary' : 'warning text-dark')); ?>">
                                <?php echo e($s === 'publish' ? 'Publié' : ($s === 'draft' ? 'Brouillon' : $s)); ?>

                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?php echo e(route('admin.group-deals.trips.show', $voyage)); ?>"
                               class="btn btn-sm btn-outline-primary">Gérer</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Aucun voyage avec Group Deal activé.
                            <a href="<?php echo e(route('admin.circuits.voyages.index')); ?>">Activer le Group Deal sur un voyage</a>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($voyages->hasPages()): ?>
            <div class="mt-3"><?php echo e($voyages->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\group-deals\trips\index.blade.php ENDPATH**/ ?>