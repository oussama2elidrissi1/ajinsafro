<?php $__env->startSection('title', 'Modifier la règle de commission'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier la règle de commission</h4>
                <a href="<?php echo e(route('admin.partner-commission-rules.index')); ?>" class="btn btn-outline-secondary btn-sm">Retour</a>
            </div>
        </div>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($e); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="<?php echo e(route('admin.partner-commission-rules.update', $rule)); ?>" method="post">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Partenaire (vide = règle globale)</label>
                        <select name="partner_id" class="form-select">
                            <option value="">— Tous les partenaires</option>
                            <?php $__currentLoopData = $partners; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($p->id); ?>" <?php echo e(old('partner_id', $rule->partner_id) == $p->id ? 'selected' : ''); ?>><?php echo e($p->raison_sociale); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Voyage (vide = tous les voyages)</label>
                        <select name="voyage_id" class="form-select">
                            <option value="">— Tous les voyages</option>
                            <?php $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($v->id); ?>" <?php echo e(old('voyage_id', $rule->voyage_id) == $v->id ? 'selected' : ''); ?>><?php echo e($v->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="percent" <?php echo e(old('type', $rule->type) === 'percent' ? 'selected' : ''); ?>>Pourcentage</option>
                            <option value="fixed" <?php echo e(old('type', $rule->type) === 'fixed' ? 'selected' : ''); ?>>Montant fixe (DH)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valeur <span class="text-danger">*</span></label>
                        <input type="number" name="value" class="form-control" step="0.01" min="0" value="<?php echo e(old('value', $rule->value)); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Volume min. (optionnel)</label>
                        <input type="number" name="min_volume" class="form-control" min="0" value="<?php echo e(old('min_volume', $rule->min_volume)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valide du</label>
                        <input type="date" name="valid_from" class="form-control" value="<?php echo e(old('valid_from', $rule->valid_from?->format('Y-m-d'))); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valide au</label>
                        <input type="date" name="valid_until" class="form-control" value="<?php echo e(old('valid_until', $rule->valid_until?->format('Y-m-d'))); ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" <?php echo e(old('is_active', $rule->is_active) ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="is_active">Règle active</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?php echo e(route('admin.partner-commission-rules.index')); ?>" class="btn btn-secondary">Annuler</a>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partner-commission-rules\edit.blade.php ENDPATH**/ ?>