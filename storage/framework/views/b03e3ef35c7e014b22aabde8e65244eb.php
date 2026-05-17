<?php $__env->startSection('title'); ?>
    Utilisateurs
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Utilisateurs</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.settings.index')); ?>">Paramètres</a></li>
                        <li class="breadcrumb-item active">Utilisateurs</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" action="<?php echo e(route('admin.settings.utilisateurs')); ?>" class="d-flex gap-2">
                <input type="text" name="q" value="<?php echo e($search); ?>" class="form-control" placeholder="Rechercher par nom ou email...">
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </form>
        </div>
        <div class="col-md-4 text-md-end mt-2 mt-md-0">
            <a href="<?php echo e(route('admin.settings.utilisateurs.create')); ?>" class="btn btn-success">
                <i class="bx bx-user-plus"></i> Ajouter un utilisateur
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <?php if(session('success')): ?>
                        <div class="alert alert-success"><?php echo e(session('success')); ?></div>
                    <?php endif; ?>
                    <?php if(session('error')): ?>
                        <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Mode</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($user->id); ?></td>
                                        <td><?php echo e($user->name); ?></td>
                                        <td><?php echo e($user->email); ?></td>
                                        <td><?php echo e($user->roles->first()->name ?? $user->base_role ?? '—'); ?></td>
                                        <td>
                                            <?php if($user->access_mode === 'custom'): ?>
                                                <span class="badge bg-warning">Personnalisé</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Rôle</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($user->is_active): ?>
                                                <span class="badge bg-success">Actif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Désactivé</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="d-flex gap-1">
                                            <a href="<?php echo e(route('admin.settings.utilisateurs.edit', $user)); ?>" class="btn btn-sm btn-primary">Modifier</a>
                                            <form method="POST" action="<?php echo e(route('admin.settings.utilisateurs.toggle-active', $user)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-warning"><?php echo e($user->is_active ? 'Désactiver' : 'Activer'); ?></button>
                                            </form>
                                            <?php if(auth()->id() !== $user->id): ?>
                                                <form method="POST" action="<?php echo e(route('admin.settings.utilisateurs.destroy', $user)); ?>" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Aucun utilisateur trouvé.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <?php echo e($users->links()); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\settings\utilisateurs\index.blade.php ENDPATH**/ ?>