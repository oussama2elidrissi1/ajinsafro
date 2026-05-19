<div class="tab-pane" id="flights" role="tabpanel">
                <?php 
                    $lastDayNumber = $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1); 
                ?>

                <p class="text-muted small mb-3">Les lieux de depart se gerent dans l'etape Disponibilites.</p>

                
                <?php echo $__env->make('admin.circuits.voyages.partials._flight_manager', [
                    'mode' => 'full',
                    'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
                    'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
                    'lastDayNumber' => $lastDayNumber,
                    'airlines' => $airlines ?? collect(),
                    'departurePlaces' => $departurePlaces ?? collect(),
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_flights.blade.php ENDPATH**/ ?>