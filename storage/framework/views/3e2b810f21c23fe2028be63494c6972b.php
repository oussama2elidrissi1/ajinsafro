<?php $__env->startSection('title'); ?> Tours WordPress <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Admin <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Tours WordPress (TravelerWP) <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Liste des Tours WordPress</h4>
                <a href="<?php echo e(route('admin.wordpress.tours.create')); ?>" class="btn btn-primary">
                    <i class="mdi mdi-plus me-1"></i> Créer un tour
                </a>
            </div>
            <div class="card-body">
                <?php if(session('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo e(session('success')); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo e(session('error')); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <i class="mdi mdi-information me-2"></i>
                    <strong>CRUD Direct WordPress</strong> - Modifications immédiatement visibles sur <a href="https://ajinsafro.net" target="_blank">ajinsafro.net</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="80">ID</th>
                                <th>Titre</th>
                                <th>Slug</th>
                                <th>Destination</th>
                                <th width="100">Durée</th>
                                <th width="120">Prix Adulte</th>
                                <th width="100">Status</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><strong><?php echo e($tour->ID); ?></strong></td>
                                    <td>
                                        <a href="<?php echo e(route('admin.wordpress.tours.edit', $tour->ID)); ?>">
                                            <?php echo e($tour->post_title); ?>

                                        </a>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo e($tour->post_name); ?></small>
                                    </td>
                                    <td><?php echo e($tour->address ?? '-'); ?></td>
                                    <td><?php echo e($tour->duration_day ?? '-'); ?></td>
                                    <td>
                                        <?php if($tour->adult_price): ?>
                                            <strong><?php echo e(number_format($tour->adult_price, 0, ',', ' ')); ?> MAD</strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($tour->post_status === 'publish'): ?>
                                            <span class="badge bg-success">Publié</span>
                                        <?php elseif($tour->post_status === 'draft'): ?>
                                            <span class="badge bg-secondary">Brouillon</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><?php echo e($tour->post_status); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo e(route('admin.wordpress.tours.edit', $tour->ID)); ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="Éditer">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            <a href="https://ajinsafro.net/tours/<?php echo e($tour->post_name); ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-info" 
                                               title="Voir sur WordPress">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <form action="<?php echo e(route('admin.wordpress.tours.destroy', $tour->ID)); ?>" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Supprimer ce tour ?');"
                                                  style="display:inline">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Aucun tour trouvé
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    <?php echo e($tours->links()); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\wp-tours\index.blade.php ENDPATH**/ ?>