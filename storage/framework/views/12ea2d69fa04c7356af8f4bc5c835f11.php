<?php
    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int) ($meta['duration_day'] ?? 1));
?>
<div class="card ve-pane-card mb-3">
    <div class="card-body">
        <h5 class="card-title mb-3"><i class="bx bx-car"></i> Transferts (plusieurs par jour possibles)</h5>
        <div id="tour-transfers-anchor">
            <?php echo $__env->make('admin.circuits.voyages.partials._tour_transfers_section', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_transfers_content.blade.php ENDPATH**/ ?>