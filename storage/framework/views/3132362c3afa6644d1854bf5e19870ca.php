
<?php $__env->startSection('title'); ?>
    Agences / Points de vente
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Agences / Points de vente</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item active">Agences</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 text-end">
            <?php if(($canCreateBranch ?? false)): ?>
                <a href="<?php echo e(route('admin.branches.create')); ?>" class="btn btn-success">
                    <i class="bx bx-plus"></i> Nouvelle agence
                </a>
            <?php endif; ?>
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
                                    <th>Nom</th>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Ville</th>
                                    <th>Utilisateurs</th>
                                    <th>Reservations</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($branch->id); ?></td>
                                        <td><?php echo e($branch->name); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo e($branch->code); ?></span></td>
                                        <td>
                                            <?php if($branch->type === 'head_office'): ?>
                                                <span class="badge bg-primary">Siege</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Point de vente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($branch->city ?? '-'); ?></td>
                                        <td><?php echo e($branch->users_count); ?></td>
                                        <td><?php echo e($branch->reservations_count ?? 0); ?></td>
                                        <td>
                                            <?php if($branch->is_active): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="d-flex gap-1">
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('settings.view')): ?>
                                                <a href="<?php echo e(route('admin.branches.edit', $branch)); ?>" class="btn btn-sm btn-primary">Modifier</a>
                                                <?php if(($branch->users_count ?? 0) === 0): ?>
                                                    <form method="POST" action="<?php echo e(route('admin.branches.destroy', $branch)); ?>" onsubmit="return confirm('Supprimer cette agence ?');">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">Aucune agence.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if($branches->hasPages()): ?>
                        <div class="d-flex justify-content-end mt-2">
                            <?php echo e($branches->links()); ?>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\branches\index.blade.php ENDPATH**/ ?>