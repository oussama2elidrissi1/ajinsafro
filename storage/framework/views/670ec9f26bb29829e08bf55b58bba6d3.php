<?php $__env->startSection('title'); ?>
    Compagnies aériennes
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Compagnies aériennes</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.circuits.index')); ?>">Circuits</a></li>
                        <li class="breadcrumb-item active">Compagnies aériennes</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0">Compagnies aériennes (vols des voyages)</h4>
                        <a href="<?php echo e(route('admin.circuits.airlines.create')); ?>" class="btn btn-primary waves-effect waves-light">
                            <i class="bx bx-plus me-1"></i> Nouvelle compagnie
                        </a>
                    </div>
                    <?php if($airlines->isEmpty()): ?>
                        <p class="text-muted mb-0">Aucune compagnie. <a href="<?php echo e(route('admin.circuits.airlines.create')); ?>">Créer une compagnie</a> pour l’utiliser dans les vols des voyages.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th>Nom</th>
                                        <th>Code IATA</th>
                                        <th>Statut</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $airlines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $airline): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($airline->id); ?></td>
                                        <td><?php echo e($airline->name); ?></td>
                                        <td><?php echo e($airline->code_iata ?? '—'); ?></td>
                                        <td>
                                            <?php if($airline->is_active): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?php echo e(route('admin.circuits.airlines.edit', $airline)); ?>" class="btn btn-sm btn-soft-primary">Modifier</a>
                                            <form action="<?php echo e(route('admin.circuits.airlines.destroy', $airline)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette compagnie ?');">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-sm btn-soft-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            <?php echo e($airlines->links()); ?>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\airlines\index.blade.php ENDPATH**/ ?>