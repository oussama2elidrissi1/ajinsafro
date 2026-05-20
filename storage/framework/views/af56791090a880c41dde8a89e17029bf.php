<?php $__env->startSection('title', 'Réservation'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Réservation</h4>
                <div>
                    <a href="<?php echo e(route('partner.reservations.edit', $reservation)); ?>" class="btn btn-outline-primary btn-sm">Modifier</a>
                    <a href="<?php echo e(route('partner.reservations.index')); ?>" class="btn btn-outline-secondary btn-sm">Retour</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Informations</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6"><strong>Offre</strong><br><?php echo e($reservation->offer?->name ?? '—'); ?></div>
                        <div class="col-md-6"><strong>Créée par</strong><br><?php echo e($reservation->creator?->name ?? '—'); ?></div>
                        <div class="col-md-6"><strong>Agence</strong><br><?php echo e($reservation->agency_label ?? '—'); ?></div>
                        <div class="col-md-6"><strong>Statut</strong><br><span class="badge bg-<?php echo e($reservation->status === \App\Models\Reservation::STATUS_VALIDEE ? 'success' : ($reservation->status === \App\Models\Reservation::STATUS_ANNULEE ? 'danger' : 'warning text-dark')); ?>"><?php echo e($reservation->status); ?></span></div>
                        <div class="col-md-6"><strong>Client</strong><br><?php echo e(trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—'); ?></div>
                        <div class="col-md-6"><strong>Email</strong><br><?php echo e($reservation->client_email ?? '—'); ?></div>
                        <div class="col-md-6"><strong>Téléphone</strong><br><?php echo e($reservation->client_phone ?? '—'); ?></div>
                        <div class="col-md-6"><strong>Type de paiement</strong><br><?php echo e($reservation->payment_type ?? '—'); ?></div>
                        <div class="col-12"><strong>Notes</strong><br><?php echo e($reservation->notes ?? '—'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p><strong>Créée le</strong><br><?php echo e($reservation->created_at?->format('d/m/Y H:i')); ?></p>
                    <form action="<?php echo e(route('partner.reservations.destroy', $reservation)); ?>" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette réservation ?');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\reservations\show.blade.php ENDPATH**/ ?>