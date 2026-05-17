<div class="tab-pane" id="taxonomies" role="tabpanel" data-ve-pane-title="Classement">
                <?php echo $__env->make('admin.circuits.voyages.partials._voyage_laravel_themes', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <div class="card ve-pane-card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Catégories & Taxonomies</h4>
                        <p class="text-muted small">Gérez les catégories (Type de tour, Durée, Langue). Les cases à cocher assignent les catégories au voyage.</p>
                        <?php echo $__env->make('admin.circuits.voyages.partials._taxonomies_crud', [
                            'availableTaxonomies' => $availableTaxonomies ?? [],
                            'assignedTaxonomies' => $assignedTaxonomies ?? [],
                        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

                    </div>
                </div>

            </div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_taxonomies.blade.php ENDPATH**/ ?>