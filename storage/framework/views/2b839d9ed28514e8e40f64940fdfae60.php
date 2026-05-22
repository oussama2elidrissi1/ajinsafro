<?php
    $departureService = app(\App\Services\DepartureManagementService::class);
    $roomTypeSuggestions = ['Single', 'Double', 'Twin', 'Triple', 'Quadruple', 'Family', 'Suite'];
    $allocationBootstrapRows = [];
    $oldAllocationRows = old('departure_allocations');
    $tourHotelsOrdered = collect($tourHotels ?? [])->values();
    $hotelIndexById = $tourHotelsOrdered
        ->filter(fn ($hotel) => isset($hotel->id))
        ->mapWithKeys(fn ($hotel, $index) => [(int) $hotel->id => (int) $index])
        ->all();

    if (is_array($oldAllocationRows)) {
        foreach (array_values($oldAllocationRows) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rooms = [];
            foreach (array_values($row['rooms'] ?? []) as $roomRow) {
                if (! is_array($roomRow)) {
                    continue;
                }

                $rooms[] = [
                    'room_type' => (string) ($roomRow['room_type'] ?? ''),
                    'quantity' => (int) ($roomRow['quantity'] ?? 0),
                    'capacity_per_room' => (int) ($roomRow['capacity_per_room'] ?? 1),
                    'supplement' => (float) ($roomRow['supplement'] ?? 0),
                    'hotel_id' => isset($roomRow['hotel_id']) && $roomRow['hotel_id'] !== '' ? (int) $roomRow['hotel_id'] : null,
                    'hotel_index' => isset($roomRow['hotel_index']) && $roomRow['hotel_index'] !== '' ? (int) $roomRow['hotel_index'] : null,
                ];
            }

            $allocationBootstrapRows[] = [
                'departure_id' => isset($row['departure_id']) && $row['departure_id'] !== '' ? (int) $row['departure_id'] : null,
                'travel_date_id' => isset($row['travel_date_id']) && $row['travel_date_id'] !== '' ? (int) $row['travel_date_id'] : null,
                'date' => (string) ($row['date'] ?? ''),
                'rooms' => $rooms,
                'manual' => true,
            ];
        }
    } elseif (isset($laravelVoyage) && $laravelVoyage) {
        $departureRows = $laravelVoyage->departures()->with('roomAllocations')->orderBy('start_date')->get();
        $departureMetricsById = $departureService
            ->buildDepartureMetrics($departureRows)
            ->keyBy('id');

        foreach ($departureRows as $departureRow) {
            $metric = (array) ($departureMetricsById->get((int) $departureRow->id) ?? []);
            $allocationBootstrapRows[] = [
                'departure_id' => (int) $departureRow->id,
                'travel_date_id' => $departureRow->wp_travel_date_id ? (int) $departureRow->wp_travel_date_id : null,
                'date' => optional($departureRow->start_date)->format('Y-m-d'),
                'target_capacity' => (int) ($metric['total_capacity'] ?? $departureRow->total_capacity ?? 0),
                'reserved_capacity' => (int) ($metric['reserved_capacity'] ?? $departureRow->reserved_capacity ?? 0),
                'available_capacity' => (int) ($metric['available_capacity'] ?? $departureRow->available_capacity ?? 0),
                'status' => (string) ($metric['status'] ?? $departureRow->status ?? ''),
                'status_label' => (string) ($metric['status_label'] ?? $departureRow->status_label ?? ''),
                'rooms' => $departureRow->roomAllocations->map(fn ($allocation) => [
                    'id' => (int) $allocation->id,
                    'room_type' => (string) ($allocation->room_type ?? ''),
                    'quantity' => (int) ($allocation->quantity ?? 0),
                    'capacity_per_room' => (int) ($allocation->capacity_per_room ?? 1),
                    'supplement' => (float) ($allocation->supplement ?? 0),
                    'hotel_id' => $allocation->hotel_id ? (int) $allocation->hotel_id : null,
                    'hotel_index' => $allocation->hotel_id && isset($hotelIndexById[(int) $allocation->hotel_id])
                        ? (int) $hotelIndexById[(int) $allocation->hotel_id]
                        : null,
                ])->values()->all(),
                'manual' => $departureRow->roomAllocations->isNotEmpty(),
            ];
        }
    }
?>

<div class="card border mb-4" id="departure-room-allocation-manager">
    <div class="card-body">
        <h5 class="mb-3"><i class="bx bx-bed"></i> Repartition des chambres par depart</h5>

        <div class="mb-4">
            <h6 class="mb-2">Departs</h6>
            <div id="departure-room-allocation-summary" class="row g-3"></div>
        </div>

        <h6 class="mb-2">Chambres</h6>
        <div id="departure-room-allocation-list"></div>
    </div>

    <script type="application/json" id="departure-room-allocation-bootstrap"><?php echo json_encode($allocationBootstrapRows, 15, 512) ?></script>
    <script type="application/json" id="departure-room-allocation-room-types"><?php echo json_encode($roomTypeSuggestions, 15, 512) ?></script>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\_departure_room_allocations.blade.php ENDPATH**/ ?>