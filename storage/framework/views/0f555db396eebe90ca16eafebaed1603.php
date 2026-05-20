<?php $__env->startSection('title', 'Modifier le client'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier le client</h4>
                <a href="<?php echo e(route('partner.clients.show', $client)); ?>" class="btn btn-outline-secondary btn-sm">Retour</a>
            </div>
        </div>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(route('partner.clients.update', $client)); ?>">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo e(old('first_name', $client->first_name)); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo e(old('last_name', $client->last_name)); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo e(old('email', $client->email)); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo e(old('phone', $client->phone)); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ville</label>
                        <input type="text" name="city" class="form-control" value="<?php echo e(old('city', $client->city)); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Code postal</label>
                        <input type="text" name="postal_code" class="form-control" value="<?php echo e(old('postal_code', $client->postal_code)); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="address_line_1" class="form-control" value="<?php echo e(old('address_line_1', $client->address_line_1)); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nationalité</label>
                        <input type="text" name="nationality" class="form-control" value="<?php echo e(old('nationality', $client->nationality)); ?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-4">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="<?php echo e(route('partner.clients.index')); ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\clients\edit.blade.php ENDPATH**/ ?>