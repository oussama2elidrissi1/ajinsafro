
<?php $__env->startSection('title'); ?>
    Transferts des circuits
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Transferts des circuits</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.circuits.index')); ?>">Circuits</a></li>
                        <li class="breadcrumb-item active">Transferts</li>
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

    <?php if(!empty($wpConnectionFailed)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Connexion WordPress indisponible.</strong> Vérifiez la configuration de la base WP.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Transfert aller (Jour 1) : Aéroport �?' Hôtel. Transfert retour (dernier jour) : Hôtel �?' Aéroport.
                    </p>
                    <?php if($tours->isEmpty()): ?>
                        <p class="text-muted mb-0">Aucun tour. <a href="<?php echo e(route('admin.circuits.voyages.create')); ?>">Créer un tour</a> puis revenir ici pour définir les transferts.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th>Titre du circuit</th>
                                        <th>Transfert aller</th>
                                        <th>Transfert retour</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php $tr = $transfersByTour[$tour->ID] ?? ['arrival' => null, 'departure' => null]; ?>
                                        <tr>
                                            <td><strong><?php echo e($tour->ID); ?></strong></td>
                                            <td>
                                                <a href="<?php echo e(route('admin.circuits.voyages.edit', $tour->ID)); ?>" class="text-body"><?php echo e($tour->post_title); ?></a>
                                            </td>
                                            <td>
                                                <?php if($tr['arrival'] && ($tr['arrival']->from_label || $tr['arrival']->to_label)): ?>
                                                    <?php echo e($tr['arrival']->from_label ?? '�?"'); ?> �?' <?php echo e($tr['arrival']->to_label ?? '�?"'); ?>

                                                <?php else: ?>
                                                    <span class="text-muted">�?"</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($tr['departure'] && ($tr['departure']->from_label || $tr['departure']->to_label)): ?>
                                                    <?php echo e($tr['departure']->from_label ?? '�?"'); ?> �?' <?php echo e($tr['departure']->to_label ?? '�?"'); ?>

                                                <?php else: ?>
                                                    <span class="text-muted">�?"</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?php echo e(route('admin.circuits.voyages.edit', $tour->ID)); ?>?tab=flights" class="btn btn-sm btn-soft-primary waves-effect waves-light">Gérer (dans le voyage)</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            <?php echo e($tours->links()); ?>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\tour-transfers\index.blade.php ENDPATH**/ ?>