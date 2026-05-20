<style>
#day-builder-root .day-builder-summary { font-size: 0.875rem; color: #6c757d; }
#day-builder-root .day-builder-tabs .nav-link { font-size: 0.92rem; font-weight: 600; }
</style>

<div
    class="card border shadow-sm mt-4 ve-day-builder-panel"
    id="day-builder-root"
    data-day-index=""
    data-day-id=""
    data-day-number=""
>
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div class="flex-grow-1">
                <p class="text-uppercase text-muted small fw-bold mb-1">Programme �?" éléments par jour</p>
                <h5 class="mb-1" id="day-builder-drawer-label">Jour �?" configuration</h5>
                <div class="day-builder-summary" id="day-builder-day-summary">Sélectionnez le jour cible ci-dessous.</div>
                <div class="small text-muted" id="day-builder-drawer-context">Ajoutez des activités, un hôtel ou des transferts pour le jour choisi �?" sans panneau latéral.</div>
            </div>
            <div style="min-width:220px">
                <label for="programme-day-target-select" class="form-label small mb-1">Jour cible</label>
                <select id="programme-day-target-select" class="form-select form-select-sm" aria-label="Choisir le jour pour le catalogue"></select>
            </div>
        </div>
    </div>

    <div class="card-body">
        <ul class="nav nav-pills nav-justified gap-2 mb-3 day-builder-tabs" id="day-builder-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#day-builder-tab-activities" type="button" role="tab">Activités</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#day-builder-tab-hotels" type="button" role="tab">Hôtels</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#day-builder-tab-transfers" type="button" role="tab">Transferts</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="day-builder-tab-activities" role="tabpanel">
                <?php echo $__env->make('admin.circuits.voyages.components.ActivitiesManager', ['activitiesCatalog' => $activitiesCatalog], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
            <div class="tab-pane fade" id="day-builder-tab-hotels" role="tabpanel">
                <?php echo $__env->make('admin.circuits.voyages.components.HotelsManager', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
            <div class="tab-pane fade" id="day-builder-tab-transfers" role="tabpanel">
                <?php echo $__env->make('admin.circuits.voyages.components.TransfersManager', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\components\DayBuilderPanel.blade.php ENDPATH**/ ?>