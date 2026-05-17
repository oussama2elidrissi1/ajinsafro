<div
    id="day-builder-flights-manager"
    data-total-days="<?php echo e($lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1)); ?>"
>
    <?php echo $__env->make('admin.circuits.voyages.partials._flight_manager', [
        'mode' => 'drawer',
        'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
        'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
        'lastDayNumber' => $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1),
        'airlines' => $airlines ?? collect(),
        'dayNumber' => 1,
        'totalDays' => $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1)
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>
<script>
document.addEventListener('day-builder:context-changed', function(e) {
    const detail = e.detail || {};
    const dayIndex = String(detail.dayIndex || '');
    const dayNumber = parseInt(detail.dayNumber || '1', 10) || 1;
    
    // Charger depuis le gestionnaire d'état
    if (window.dayItemsManager) {
        window.dayItemsManager.loadFromForm(dayIndex);
    }
    
    // La gestion des vols est déléguée aux flight-manager qui sont dans _flight_manager
    // On vérifier que le jour correct est mis à jour dans la vue
    var manager = document.querySelector('#day-builder-flights-manager .flight-manager');
    if (manager) {
        manager.setAttribute('data-day-number', String(dayNumber));
        manager.setAttribute('data-day-index', String(dayIndex));
    }
});

// Mettre en oeuvre une fonction pour synchoniser les vols depuis la UI du flight-manager
document.addEventListener('change', function(e) {
    // Si un input de vol change dans le drawer, synchroniser avec dayItemsManager
    var drawer = document.getElementById('day-builder-root');
    if (!drawer) return;
    var dayIndex = drawer.getAttribute('data-day-index');
    if (!dayIndex) return;
    
    // Chercher les checkboxes de sélection de vols
    var flightCheckboxes = document.querySelectorAll('#day-builder-flights-manager input[type="checkbox"][name*="flight-option"]');
    var selectedFlightIds = [];
    flightCheckboxes.forEach(function(cb) {
        if (cb.checked) {
            var val = cb.value || cb.getAttribute('data-flight-id');
            if (val) selectedFlightIds.push(parseInt(val, 10));
        }
    });
    
    // Mettre à jour le gestionnaire d'état
    if (window.dayItemsManager) {
        window.dayItemsManager.setFlights(dayIndex, selectedFlightIds);
        window.dayItemsManager.syncToForm(dayIndex);
        document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: dayIndex } }));
    }
});
</script><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\components\FlightsManager.blade.php ENDPATH**/ ?>