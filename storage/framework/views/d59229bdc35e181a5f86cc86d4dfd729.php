

<?php $__env->startSection('title', 'Créer un tour WordPress'); ?>
<?php $__env->startSection('page_title', 'Créer un tour WordPress'); ?>

<?php
    $breadcrumbs = [ ['label' => 'Accueil', 'url' => (\Illuminate\Support\Facades\Route::has('admin.dashboard.v6') ? route('admin.dashboard.v6') : (\Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin')))], ['label' => 'WordPress', 'url' => route('admin.wordpress.tours.index')], ['label' => 'Créer'] ];
?>


<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Nouveau Tour WordPress</h4>
            </div>
            <div class="card-body">
                <?php if($errors->any()): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e(route('admin.wordpress.tours.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Titre du tour <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="<?php echo e(old('title')); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slug (URL)</label>
                                <input type="text" name="slug" class="form-control" value="<?php echo e(old('slug')); ?>" placeholder="laissez vide pour générer automatiquement">
                                <small class="text-muted">Sera visible sur : ajinsafro.net/tours/<strong>votre-slug</strong></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="content" class="form-control" rows="10"><?php echo e(old('content')); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Extrait / Accroche</label>
                                <textarea name="excerpt" class="form-control" rows="3"><?php echo e(old('excerpt')); ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="post_status" class="form-select">
                                    <option value="publish" <?php echo e(old('post_status') === 'publish' ? 'selected' : ''); ?>>Publié</option>
                                    <option value="draft" <?php echo e(old('post_status') === 'draft' ? 'selected' : ''); ?>>Brouillon</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Destination</label>
                                <input type="text" name="destination" class="form-control" value="<?php echo e(old('destination')); ?>" placeholder="ex: Dubaï, EAU">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Durée</label>
                                <input type="text" name="duration_text" class="form-control" value="<?php echo e(old('duration_text')); ?>" placeholder="ex: 7 jours / 6 nuits">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Adulte (MAD)</label>
                                <input type="number" name="adult_price" class="form-control" value="<?php echo e(old('adult_price')); ?>" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Enfant (MAD)</label>
                                <input type="number" name="child_price" class="form-control" value="<?php echo e(old('child_price')); ?>" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Minimum (MAD)</label>
                                <input type="number" name="min_price" class="form-control" value="<?php echo e(old('min_price')); ?>" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nombre minimum de personnes</label>
                                <input type="number" name="min_people" class="form-control" value="<?php echo e(old('min_people')); ?>" min="1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Image à la une (ID)</label>
                                <input type="number" name="thumbnail_id" class="form-control" value="<?php echo e(old('thumbnail_id')); ?>" placeholder="ID de l'image WP">
                                <small class="text-muted">ID de l'image dans la médiathèque WordPress</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Galerie (IDs séparés par virgule)</label>
                                <input type="text" name="gallery_ids" class="form-control" value="<?php echo e(old('gallery_ids')); ?>" placeholder="14435,14436,14437">
                                <small class="text-muted">IDs des images de la galerie</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-content-save me-1"></i> Créer le tour
                                </button>
                                <a href="<?php echo e(route('admin.wordpress.tours.index')); ?>" class="btn btn-secondary">
                                    Annuler
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>







<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\wp-tours\create.blade.php ENDPATH**/ ?>