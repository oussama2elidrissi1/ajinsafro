<?php $__env->startSection('title', 'Nouvelle réservation'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Nouvelle réservation</h4>
                <a href="<?php echo e(route('partner.reservations.index')); ?>" class="btn btn-outline-secondary btn-sm">Retour</a>
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

    <form method="post" action="<?php echo e(route('partner.reservations.store')); ?>">
        <?php echo csrf_field(); ?>
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Offre</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Offre / voyage <span class="text-danger">*</span></label>
                        <select name="tour_id" class="form-select" required>
                            <option value="">Sélectionner un voyage…</option>
                            <?php $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($voyage->id); ?>" <?php echo e(old('tour_id') == $voyage->id ? 'selected' : ''); ?>><?php echo e($voyage->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date de départ</label>
                        <select name="travel_date_id" class="form-select">
                            <option value="">—</option>
                            <?php $__currentLoopData = $travelDates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $td): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($td->id); ?>" <?php echo e(old('travel_date_id') == $td->id ? 'selected' : ''); ?>>
                                    <?php echo e($td->date?->format('d/m/Y')); ?> — Voyage #<?php echo e($td->travel_id ?? ''); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type de paiement</label>
                        <select name="payment_type" class="form-select">
                            <option value="">—</option>
                            <option value="CASHPLUS" <?php echo e(old('payment_type') === 'CASHPLUS' ? 'selected' : ''); ?>>CashPlus</option>
                            <option value="VIREMENT" <?php echo e(old('payment_type') === 'VIREMENT' ? 'selected' : ''); ?>>Virement</option>
                            <option value="ESPECE" <?php echo e(old('payment_type') === 'ESPECE' ? 'selected' : ''); ?>>Espèce</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Client</h6>
            </div>
            <div class="card-body">
                <?php $clientMode = old('client_mode', 'new'); ?>
                <div class="mb-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="client_mode" id="client_mode_new" value="new" <?php echo e($clientMode === 'new' ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="client_mode_new">Nouveau client</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="client_mode" id="client_mode_existing" value="existing" <?php echo e($clientMode === 'existing' ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="client_mode_existing">Client existant</label>
                    </div>
                </div>
                <div id="existing-client-block" class="mb-3" style="<?php echo e($clientMode === 'existing' ? '' : 'display:none;'); ?>">
                    <label class="form-label">Client</label>
                    <select name="client_external_id" class="form-select">
                        <option value="">— Choisir —</option>
                        <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($client->id); ?>" <?php echo e(old('client_external_id') == $client->id ? 'selected' : ''); ?>>
                                [<?php echo e($client->client_code); ?>] <?php echo e($client->full_name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div id="new-client-block" style="<?php echo e($clientMode === 'new' ? '' : 'display:none;'); ?>">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="client_first_name" class="form-control" value="<?php echo e(old('client_first_name')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="client_last_name" class="form-control" value="<?php echo e(old('client_last_name')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="client_email" class="form-control" value="<?php echo e(old('client_email')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="client_phone" class="form-control" value="<?php echo e(old('client_phone')); ?>">
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?php echo e(old('notes')); ?></textarea>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="<?php echo e(route('partner.reservations.index')); ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
<script>
(function () {
    var modeNew = document.getElementById('client_mode_new');
    var modeExisting = document.getElementById('client_mode_existing');
    var blockNew = document.getElementById('new-client-block');
    var blockExisting = document.getElementById('existing-client-block');
    function refresh() {
        if (modeExisting && modeExisting.checked) {
            if (blockExisting) blockExisting.style.display = '';
            if (blockNew) blockNew.style.display = 'none';
        } else {
            if (blockExisting) blockExisting.style.display = 'none';
            if (blockNew) blockNew.style.display = '';
        }
    }
    if (modeNew) modeNew.addEventListener('change', refresh);
    if (modeExisting) modeExisting.addEventListener('change', refresh);
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\reservations\create.blade.php ENDPATH**/ ?>