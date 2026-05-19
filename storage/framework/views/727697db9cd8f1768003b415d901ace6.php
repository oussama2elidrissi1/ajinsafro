<div class="tab-pane" id="voyage-extras" role="tabpanel" data-ve-pane-title="Extras">
    <div class="card ve-pane-card">
        <div class="card-body">
            <p class="ve-section-kicker mb-2">Services additionnels</p>
            <h4 class="card-title mb-2">Extras de reservation</h4>
            <p class="text-muted small mb-3">Configurez les options, supplements et activites proposes au moment de la reservation pour ce voyage.</p>

            <?php echo $__env->make('admin.circuits.voyages.partials._voyage_extras', ['voyageExtras' => $voyageExtras ?? collect()], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_extras.blade.php ENDPATH**/ ?>