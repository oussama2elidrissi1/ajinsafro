<?php
    $availabilityStatuses = [
        'available' => 'Disponible',
        'limited' => 'Limité',
        'full' => 'Complet',
        'closed' => 'Fermé',
    ];
    $travelDatesList = $travelDates ?? collect();
    $dateAvailabilitiesInput = old("tour_hotels.{$hi}.rooms.{$ri}.date_availabilities");
    $dateAvailabilitiesInput = is_array($dateAvailabilitiesInput) ? $dateAvailabilitiesInput : [];
    $dateAvailabilityObjects = collect(optional($room)->dateAvailabilities ?? [])->keyBy('travel_date_id');
    $roomCapacityPerUnit = \App\Support\TourPlacesCalculator::effectiveCapacity((int) $capTotalVal, (int) $capAdultsVal, (int) $capChildrenVal);
    $roomAvailabilityOpen = false;
?>

<details class="tour-room-date-availability-panel mt-3" <?php echo e($roomAvailabilityOpen ? 'open' : ''); ?>>
    <summary class="small fw-semibold text-primary">
        Disponibilité par date
        <span class="text-muted fw-normal">(<?php echo e($travelDatesList->count()); ?> départ<?php echo e($travelDatesList->count() > 1 ? 's' : ''); ?>)</span>
    </summary>

    <?php if($travelDatesList->isEmpty()): ?>
        <div class="alert alert-warning py-2 px-3 small mt-2 mb-0">
            Ajoutez d'abord des dates dans l'onglet Disponibilité pour gérer le stock hôtel par départ.
        </div>
    <?php else: ?>
        <div class="table-responsive mt-2">
            <table class="table table-sm align-middle mb-0 tour-room-date-availability-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Chambres</th>
                        <th>Places</th>
                        <th>Statut</th>
                        <th>Supplément</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $travelDatesList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dateIndex => $travelDate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $dateId = (int) ($travelDate->id ?? 0);
                            $oldDateRow = $dateAvailabilitiesInput[$dateIndex] ?? null;
                            $dbDateRow = $dateId > 0 ? $dateAvailabilityObjects->get($dateId) : null;
                            $defaultAvailableRooms = max(0, (int) $roomCountVal);
                            $defaultAvailablePlaces = max(0, $defaultAvailableRooms * $roomCapacityPerUnit);
                            $defaultStatus = $defaultAvailableRooms > 0 ? 'available' : 'full';
                            $dateAvailabilityId = old("tour_hotels.{$hi}.rooms.{$ri}.date_availabilities.{$dateIndex}.id", optional($oldDateRow)['id'] ?? optional($dbDateRow)->id ?? '');
                            $availableRoomsValue = old("tour_hotels.{$hi}.rooms.{$ri}.date_availabilities.{$dateIndex}.available_rooms", optional($oldDateRow)['available_rooms'] ?? optional($dbDateRow)->available_rooms ?? $defaultAvailableRooms);
                            $availablePlacesValue = old("tour_hotels.{$hi}.rooms.{$ri}.date_availabilities.{$dateIndex}.available_places", optional($oldDateRow)['available_places'] ?? optional($dbDateRow)->available_places ?? $defaultAvailablePlaces);
                            $statusValue = old("tour_hotels.{$hi}.rooms.{$ri}.date_availabilities.{$dateIndex}.status", optional($oldDateRow)['status'] ?? optional($dbDateRow)->status ?? $defaultStatus);
                            $dateSupplementValue = old("tour_hotels.{$hi}.rooms.{$ri}.date_availabilities.{$dateIndex}.supplement", optional($oldDateRow)['supplement'] ?? optional($dbDateRow)->supplement ?? $supplementVal);
                            $dateValue = optional($travelDate->date)->format('Y-m-d');
                        ?>
                        <tr class="tour-room-date-availability-row"
                            data-date-index="<?php echo e($dateIndex); ?>"
                            data-travel-date-id="<?php echo e($dateId); ?>"
                            data-date="<?php echo e($dateValue); ?>">
                            <td>
                                <div class="fw-semibold small"><?php echo e(optional($travelDate->date)->format('d/m/Y')); ?></div>
                                <div class="text-muted x-small"><?php echo e($dateValue); ?></div>
                                <?php if($dateAvailabilityId !== ''): ?>
                                    <input type="hidden" name="tour_hotels[<?php echo e($hi); ?>][rooms][<?php echo e($ri); ?>][date_availabilities][<?php echo e($dateIndex); ?>][id]" value="<?php echo e($dateAvailabilityId); ?>">
                                <?php endif; ?>
                                <input type="hidden" name="tour_hotels[<?php echo e($hi); ?>][rooms][<?php echo e($ri); ?>][date_availabilities][<?php echo e($dateIndex); ?>][travel_date_id]" value="<?php echo e($dateId); ?>">
                                <input type="hidden" name="tour_hotels[<?php echo e($hi); ?>][rooms][<?php echo e($ri); ?>][date_availabilities][<?php echo e($dateIndex); ?>][date]" value="<?php echo e($dateValue); ?>">
                            </td>
                            <td>
                                <input type="number"
                                    class="form-control form-control-sm tour-room-date-available-rooms"
                                    name="tour_hotels[<?php echo e($hi); ?>][rooms][<?php echo e($ri); ?>][date_availabilities][<?php echo e($dateIndex); ?>][available_rooms]"
                                    value="<?php echo e($availableRoomsValue); ?>"
                                    min="0">
                            </td>
                            <td>
                                <input type="number"
                                    class="form-control form-control-sm tour-room-date-available-places"
                                    name="tour_hotels[<?php echo e($hi); ?>][rooms][<?php echo e($ri); ?>][date_availabilities][<?php echo e($dateIndex); ?>][available_places]"
                                    value="<?php echo e($availablePlacesValue); ?>"
                                    min="0">
                            </td>
                            <td>
                                <select class="form-select form-select-sm tour-room-date-status"
                                    name="tour_hotels[<?php echo e($hi); ?>][rooms][<?php echo e($ri); ?>][date_availabilities][<?php echo e($dateIndex); ?>][status]">
                                    <?php $__currentLoopData = $availabilityStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $statusKey => $statusLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($statusKey); ?>" <?php echo e($statusValue === $statusKey ? 'selected' : ''); ?>><?php echo e($statusLabel); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </td>
                            <td>
                                <input type="number"
                                    class="form-control form-control-sm tour-room-date-supplement"
                                    name="tour_hotels[<?php echo e($hi); ?>][rooms][<?php echo e($ri); ?>][date_availabilities][<?php echo e($dateIndex); ?>][supplement]"
                                    value="<?php echo e($dateSupplementValue); ?>"
                                    min="0"
                                    step="0.01">
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</details>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\_tour_hotel_room_date_availability.blade.php ENDPATH**/ ?>