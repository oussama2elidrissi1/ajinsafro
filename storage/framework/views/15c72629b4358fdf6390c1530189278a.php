<?php
    $lastDayNumber = $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1);
?>

<?php echo $__env->make('admin.circuits.voyages.partials._departure_places_inline', ['departurePlaces' => $departurePlaces ?? collect()], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php echo $__env->make('admin.circuits.voyages.partials._flight_manager', [
    'mode' => 'full',
    'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
    'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
    'lastDayNumber' => $lastDayNumber,
    'airlines' => $airlines ?? collect(),
    'departurePlaces' => $departurePlaces ?? collect(),
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_flights_content.blade.php ENDPATH**/ ?>