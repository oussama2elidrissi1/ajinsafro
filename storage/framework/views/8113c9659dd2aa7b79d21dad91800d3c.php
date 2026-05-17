<?php $__env->startSection('title', 'Mes clients'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Mes clients</h4>
                <a href="<?php echo e(route('partner.clients.create')); ?>" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Nouveau client</a>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-auto flex-grow-1" style="min-width: 200px;">
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Nom, email, tél..." value="<?php echo e(request('search')); ?>">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Code</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="ps-3"><?php echo e($client->client_code); ?></td>
                                        <td><?php echo e($client->full_name); ?></td>
                                        <td><?php echo e($client->email ?? '—'); ?></td>
                                        <td><?php echo e($client->phone ?? '—'); ?></td>
                                        <td class="text-end pe-3">
                                            <a href="<?php echo e(route('partner.clients.show', $client)); ?>" class="btn btn-sm btn-outline-primary" title="Voir"><i class="bx bx-show"></i></a>
                                            <a href="<?php echo e(route('partner.clients.edit', $client)); ?>" class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="bx bx-pencil"></i></a>
                                            <form action="<?php echo e(route('partner.clients.destroy', $client)); ?>" method="post" class="d-inline" onsubmit="return confirm('Supprimer ce client ?');">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Aucun client.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if(method_exists($clients, 'links')): ?>
                        <div class="d-flex justify-content-center mt-3"><?php echo e($clients->links()); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\clients\index.blade.php ENDPATH**/ ?>