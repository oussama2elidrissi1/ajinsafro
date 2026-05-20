<?php $__env->startSection('title', 'Modifier la réservation'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier la réservation</h4>
                <a href="<?php echo e(route('partner.reservations.show', $reservation)); ?>" class="btn btn-outline-secondary btn-sm">Retour</a>
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

    <form method="post" action="<?php echo e(route('partner.reservations.update', $reservation)); ?>">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Offre</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Offre / voyage <span class="text-danger">*</span></label>
                        <select name="tour_id" class="form-select" required>
                            <?php $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($voyage->id); ?>" <?php echo e(old('tour_id', $reservation->tour_id) == $voyage->id ? 'selected' : ''); ?>><?php echo e($voyage->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date de départ</label>
                        <select name="travel_date_id" class="form-select">
                            <option value="">—</option>
                            <?php $__currentLoopData = $travelDates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $td): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($td->id); ?>" <?php echo e(old('travel_date_id', $reservation->travel_date_id) == $td->id ? 'selected' : ''); ?>><?php echo e($td->date?->format('d/m/Y')); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <?php
                        $statusCur = old('status', $reservation->status);
                        $statusNorm = match ($statusCur) {
                            'EN_COURS' => 'pending',
                            'VALIDEE' => 'confirmed',
                            'ANNULEE' => 'cancelled',
                            default => $statusCur,
                        };
                    ?>
                    <div class="col-md-6">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="pending" <?php echo e($statusNorm === 'pending' ? 'selected' : ''); ?>>En attente</option>
                            <option value="confirmed" <?php echo e($statusNorm === 'confirmed' ? 'selected' : ''); ?>>Confirmée</option>
                            <option value="cancelled" <?php echo e($statusNorm === 'cancelled' ? 'selected' : ''); ?>>Annulée</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type de paiement</label>
                        <select name="payment_type" class="form-select">
                            <option value="">—</option>
                            <option value="CASHPLUS" <?php echo e(old('payment_type', $reservation->payment_type) === 'CASHPLUS' ? 'selected' : ''); ?>>CashPlus</option>
                            <option value="VIREMENT" <?php echo e(old('payment_type', $reservation->payment_type) === 'VIREMENT' ? 'selected' : ''); ?>>Virement</option>
                            <option value="ESPECE" <?php echo e(old('payment_type', $reservation->payment_type) === 'ESPECE' ? 'selected' : ''); ?>>Espèce</option>
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
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="client_first_name" class="form-control" value="<?php echo e(old('client_first_name', $reservation->client_first_name)); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <input type="text" name="client_last_name" class="form-control" value="<?php echo e(old('client_last_name', $reservation->client_last_name)); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="client_email" class="form-control" value="<?php echo e(old('client_email', $reservation->client_email)); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="client_phone" class="form-control" value="<?php echo e(old('client_phone', $reservation->client_phone)); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?php echo e(old('notes', $reservation->notes)); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-4">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="<?php echo e(route('partner.reservations.index')); ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\reservations\edit.blade.php ENDPATH**/ ?>