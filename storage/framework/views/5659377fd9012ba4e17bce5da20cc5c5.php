<div class="tab-pane" id="transfers" role="tabpanel">
                <?php
                    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                ?>
                <h5 class="mb-3"><i class="bx bx-car"></i> Transferts (plusieurs par jour possible)</h5>

                <div id="tour-transfers-anchor">
                    <?php echo $__env->make('admin.circuits.voyages.partials._tour_transfers_section', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            </div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_transfers.blade.php ENDPATH**/ ?>