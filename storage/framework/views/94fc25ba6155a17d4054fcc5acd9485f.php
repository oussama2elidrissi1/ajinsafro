<div class="tab-pane" id="program-days" role="tabpanel" data-ve-pane-title="Programme">
    <div class="card ve-programme-tab-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <p class="ve-section-kicker mb-2">Programme</p>
                    <h4 class="card-title mb-1">Jour par jour</h4>
                    <p class="text-muted mb-0 small">
                        Structurez les etapes, les villes, les repas et les activites du voyage.
                        <?php if(Route::has('admin.circuits.activities.index')): ?>
                            <a href="<?php echo e(route('admin.circuits.activities.index')); ?>" target="_blank">Voir le catalogue d'activites</a>.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <span class="badge ve-count-badge fs-6" id="program-days-badge">0 jours</span>
                    <button type="button" class="btn btn-primary" id="btn-add-program-day">
                        <i class="bx bx-plus"></i> Ajouter un jour
                    </button>
                </div>
            </div>

            <div class="accordion" id="accordionProgrammeDays">
                <?php $__empty_1 = true; $__currentLoopData = $programDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayIndex => $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php echo $__env->make('admin.circuits.voyages.partials.programme._day_card', [
                        'entry' => $entry,
                        'dayIndex' => $dayIndex,
                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2" id="program-no-days-alert">
                        <span><i class="bx bx-info-circle"></i> Aucun jour pour le moment. Ajoutez une etape pour construire le programme.</span>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-add-program-day-empty"><i class="bx bx-plus"></i> Ajouter un jour</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_programme.blade.php ENDPATH**/ ?>