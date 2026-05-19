<?php $__env->startSection('title'); ?> Éditer Tour WordPress <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> <a href="<?php echo e(route('admin.wordpress.tours.index')); ?>">Tours WordPress</a> <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Éditer : <?php echo e($tour['title']); ?> <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Éditer Tour #<?php echo e($tour['id']); ?></h4>
                <a href="https://ajinsafro.net/tours/<?php echo e($tour['slug']); ?>" target="_blank" class="btn btn-sm btn-info">
                    <i class="mdi mdi-eye me-1"></i> Voir sur WordPress
                </a>
            </div>
            <div class="card-body">
                <?php if(session('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo e(session('success')); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if($errors->any()): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e(route('admin.wordpress.tours.update', $tour['id'])); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Titre du tour <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="<?php echo e(old('title', $tour['title'])); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slug (URL)</label>
                                <input type="text" name="slug" class="form-control" value="<?php echo e(old('slug', $tour['slug'])); ?>">
                                <small class="text-muted">Visible sur : ajinsafro.net/tours/<strong><?php echo e(old('slug', $tour['slug'])); ?></strong></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="content" class="form-control" rows="10"><?php echo e(old('content', $tour['content'])); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Extrait / Accroche</label>
                                <textarea name="excerpt" class="form-control" rows="3"><?php echo e(old('excerpt', $tour['excerpt'])); ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="post_status" class="form-select">
                                    <option value="publish" <?php echo e(old('post_status', $tour['status']) === 'publish' ? 'selected' : ''); ?>>Publié</option>
                                    <option value="draft" <?php echo e(old('post_status', $tour['status']) === 'draft' ? 'selected' : ''); ?>>Brouillon</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Destination</label>
                                <input type="text" name="destination" class="form-control" value="<?php echo e(old('destination', $tour['destination'])); ?>" placeholder="ex: Dubaï, EAU">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Durée</label>
                                <input type="text" name="duration_text" class="form-control" value="<?php echo e(old('duration_text', $tour['duration_text'])); ?>" placeholder="ex: 7 jours / 6 nuits">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Adulte (MAD)</label>
                                <input type="number" name="adult_price" class="form-control" value="<?php echo e(old('adult_price', $tour['adult_price'])); ?>" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Enfant (MAD)</label>
                                <input type="number" name="child_price" class="form-control" value="<?php echo e(old('child_price', $tour['child_price'])); ?>" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Minimum (MAD)</label>
                                <input type="number" name="min_price" class="form-control" value="<?php echo e(old('min_price', $tour['min_price'])); ?>" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nombre minimum de personnes</label>
                                <input type="number" name="min_people" class="form-control" value="<?php echo e(old('min_people', $tour['min_people'])); ?>" min="1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Image à la une (ID)</label>
                                <input type="number" name="thumbnail_id" class="form-control" value="<?php echo e(old('thumbnail_id', $tour['thumbnail_id'])); ?>" placeholder="ID de l'image WP">
                                <small class="text-muted">ID de l'image dans la médiathèque WordPress</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Galerie (IDs séparés par virgule)</label>
                                <input type="text" name="gallery_ids" class="form-control" value="<?php echo e(old('gallery_ids', is_array($tour['gallery']) ? implode(',', $tour['gallery']) : $tour['gallery'])); ?>" placeholder="14435,14436,14437">
                                <small class="text-muted">IDs des images de la galerie</small>
                            </div>

                            <div class="alert alert-info">
                                <small>
                                    <strong>Créé :</strong> <?php echo e($tour['created_at']->format('d/m/Y H:i')); ?><br>
                                    <strong>Modifié :</strong> <?php echo e($tour['updated_at']->format('d/m/Y H:i')); ?>

                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-content-save me-1"></i> Enregistrer
                                </button>
                                <a href="<?php echo e(route('admin.wordpress.tours.index')); ?>" class="btn btn-secondary">
                                    Retour à la liste
                                </a>
                                <form action="<?php echo e(route('admin.wordpress.tours.destroy', $tour['id'])); ?>" 
                                      method="POST" 
                                      onsubmit="return confirm('Supprimer définitivement ce tour de WordPress ?');"
                                      class="ms-auto">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="mdi mdi-delete me-1"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\wp-tours\edit.blade.php ENDPATH**/ ?>