
<?php $__env->startSection('title', 'RÃ¨gles de commission'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">RÃ¨gles de commission</h4>
                <a href="<?php echo e(route('admin.partner-commission-rules.create')); ?>" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Nouvelle rÃ¨gle</a>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small">Partenaire</label>
                        <select name="partner_id" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tous</option>
                            <?php $__currentLoopData = $partners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($p->id); ?>" <?php echo e(request('partner_id') == $p->id ? 'selected' : ''); ?>><?php echo e($p->raison_sociale); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small">Type</label>
                        <select name="type" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tous</option>
                            <option value="percent" <?php echo e(request('type') === 'percent' ? 'selected' : ''); ?>>%</option>
                            <option value="fixed" <?php echo e(request('type') === 'fixed' ? 'selected' : ''); ?>>Montant fixe</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Partenaire</th>
                            <th>Voyage</th>
                            <th>Type</th>
                            <th>Valeur</th>
                            <th>PÃ©riode</th>
                            <th>Actif</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $rules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e($rule->partner ? $rule->partner->display_name : 'â€” Global'); ?></td>
                                <td><?php echo e($rule->voyage ? $rule->voyage->name : 'â€” Tous'); ?></td>
                                <td><?php echo e($rule->type === 'percent' ? '%' : 'Fixe'); ?></td>
                                <td><?php echo e($rule->type === 'percent' ? $rule->value . ' %' : number_format($rule->value, 0, ',', ' ') . ' DH'); ?></td>
                                <td>
                                    <?php echo e($rule->valid_from?->format('d/m/Y') ?? 'â€”'); ?> â†’ <?php echo e($rule->valid_until?->format('d/m/Y') ?? 'â€”'); ?>

                                </td>
                                <td><span class="badge bg-<?php echo e($rule->is_active ? 'success' : 'secondary'); ?>"><?php echo e($rule->is_active ? 'Oui' : 'Non'); ?></span></td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('admin.partner-commission-rules.edit', $rule)); ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                                    <form action="<?php echo e(route('admin.partner-commission-rules.destroy', $rule)); ?>" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette rÃ¨gle ?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Aucune rÃ¨gle. CrÃ©ez une rÃ¨gle globale (sans partenaire ni voyage) ou par partenaire/voyage.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(method_exists($rules, 'links')): ?>
                <div class="d-flex justify-content-center mt-3"><?php echo e($rules->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partner-commission-rules\index.blade.php ENDPATH**/ ?>