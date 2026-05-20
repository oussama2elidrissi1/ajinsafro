
<div class="ra-departure-panel" data-departure-id="<?php echo e($departure->id); ?>">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-3 bg-light border rounded">
        <span class="badge bg-secondary"><?php echo e($departure->status_label); ?></span>
        <span class="small text-nowrap">Cap. totale : <strong><?php echo e((int) ($departure->total_capacity ?? 0)); ?></strong></span>
        <span class="small text-nowrap">Réservé : <strong><?php echo e((int) ($departure->reserved_capacity ?? 0)); ?></strong></span>
        <span class="small text-nowrap">Restant : <strong class="text-success"><?php echo e((int) ($departure->available_capacity ?? 0)); ?></strong></span>
        <a href="<?php echo e(route('admin.circuits.voyages.departures.show', [$voyage, $departure])); ?>" class="btn btn-sm btn-outline-secondary ms-auto" target="_blank" rel="noopener">
            <i class="bx bx-link-external"></i> Page départ
        </a>
    </div>

    <div class="mb-3">
        <?php echo $__env->make('admin.circuits.voyages.departures.partials._settings_card', [
            'voyage' => $voyage,
            'departure' => $departure,
            'statuses' => $statuses,
            'modalAjax' => true,
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>

    <?php echo $__env->make('admin.circuits.voyages.departures.partials._hotels_section', [
        'voyage' => $voyage,
        'departure' => $departure,
        'hotelsCatalog' => $hotelsCatalog,
        'roomStatuses' => $roomStatuses,
        'modalAjax' => true,
        'layout' => 'accordion',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\room_availability\departure_panel.blade.php ENDPATH**/ ?>