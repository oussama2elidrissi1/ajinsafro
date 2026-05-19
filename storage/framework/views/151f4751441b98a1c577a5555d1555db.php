

<?php $__env->startSection('title', 'DÃ©part â€” '.$voyage->name); ?>

<?php $__env->startSection('content'); ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="page-title mb-1 font-size-18">Gestion du dÃ©part</h4>
                <p class="text-muted mb-0 small">
                    <a href="<?php echo e(route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id)); ?>">â† Retour au voyage</a>
                    <?php if($departure->wp_travel_date_id): ?>
                        <span class="ms-2">Â· WP travel_date_id : <?php echo e($departure->wp_travel_date_id); ?></span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo e(route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id)); ?>#availability" class="btn btn-soft-secondary btn-sm">Liste des dÃ©parts</a>
            </div>
        </div>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success border-0"><?php echo e(session('success')); ?></div>
<?php endif; ?>

<?php echo $__env->make('admin.circuits.voyages.departures.partials._settings_card', compact('voyage', 'departure', 'statuses'), \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<div id="hotels" class="mt-4">
    <?php echo $__env->make('admin.circuits.voyages.departures.partials._hotels_section', compact('voyage', 'departure', 'hotelsCatalog', 'roomStatuses'), \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\departures\show.blade.php ENDPATH**/ ?>