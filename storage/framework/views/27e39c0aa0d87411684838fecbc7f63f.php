
<?php $__env->startSection('title'); ?>
    <?php echo e($label); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="page-title mb-1 font-size-18"><?php echo e($label); ?></h4>
                    <p class="text-muted small mb-0"><code><?php echo e($groupKey); ?></code></p>
                </div>
                <a href="<?php echo e(route('admin.settings.referentiels-metier')); ?>" class="btn btn-light btn-sm">â† Toutes les familles</a>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><strong>Ajouter une valeur</strong></div>
        <div class="card-body">
            <form action="<?php echo e(route('admin.settings.referentiels-metier.store', ['groupKey' => $groupKey])); ?>" method="POST" class="row g-3">
                <?php echo csrf_field(); ?>
                <?php if($groupKey === 'payment_methods'): ?>
                    <div class="col-12">
                        <label class="form-label">Meta (JSON) â€” doit contenir <code>meta_key</code></label>
                        <textarea name="meta_json" class="form-control font-monospace" rows="2" required placeholder='{"meta_key":"is_meta_payment_gateway_st_xxx"}'><?php echo e(old('meta_json', '{"meta_key":""}')); ?></textarea>
                    </div>
                <?php else: ?>
                    <div class="col-md-4">
                        <label class="form-label">Valeur (slug)</label>
                        <input type="text" name="value" class="form-control" value="<?php echo e(old('value')); ?>" required>
                    </div>
                <?php endif; ?>
                <div class="col-md-4">
                    <label class="form-label">LibellÃ©</label>
                    <input type="text" name="label" class="form-control" value="<?php echo e(old('label')); ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tri</label>
                    <input type="number" name="sort_order" class="form-control" value="<?php echo e(old('sort_order', 0)); ?>" min="0">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="na" checked>
                        <label class="form-check-label" for="na">Actif</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:22%">Valeur</th>
                            <th>Modification</th>
                            <th style="width:110px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><code><?php echo e($item->value); ?></code></td>
                                <td>
                                    <form action="<?php echo e(route('admin.settings.referentiels-metier.update', ['groupKey' => $groupKey, 'item' => $item])); ?>" method="POST" class="row g-2 align-items-end">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <div class="col-md-4">
                                            <label class="form-label small mb-0">LibellÃ©</label>
                                            <input type="text" name="label" class="form-control form-control-sm" value="<?php echo e($item->label); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Tri</label>
                                            <input type="number" name="sort_order" class="form-control form-control-sm" value="<?php echo e($item->sort_order); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Actif</label>
                                            <div>
                                                <input type="hidden" name="is_active" value="0">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" <?php if($item->is_active): echo 'checked'; endif; ?>>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small mb-0">Meta (JSON)</label>
                                            <textarea name="meta_json" class="form-control form-control-sm font-monospace" rows="2"><?php echo e($item->meta ? json_encode($item->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : ''); ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form action="<?php echo e(route('admin.settings.referentiels-metier.destroy', ['groupKey' => $groupKey, 'item' => $item])); ?>" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Aucune valeur.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\settings\business_references\group.blade.php ENDPATH**/ ?>