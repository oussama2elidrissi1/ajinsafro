<div class="tab-pane" id="hotels" role="tabpanel" data-ve-pane-title="Hôtels">
                <?php
                    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                ?>
                <h5 class="mb-3" id="tour-hotels-title"><i class="bx bx-hotel"></i> Hôtels du voyage <span id="tour-hotels-period">(séjour �?" check-in J1, check-out J<?php echo e($lastDayNumber); ?>)</span></h5>
                <div id="tour-hotels-anchor">
                    <?php echo $__env->make('admin.circuits.voyages.partials._tour_hotels_section', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
                <?php echo $__env->make('admin.circuits.voyages.partials._departure_room_allocations', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_hotels.blade.php ENDPATH**/ ?>