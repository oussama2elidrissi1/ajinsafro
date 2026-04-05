@php
    $roomTypeSuggestions = ['Single', 'Double', 'Twin', 'Triple', 'Quadruple', 'Family', 'Suite'];
    $allocationBootstrapRows = [];
    $oldAllocationRows = old('departure_allocations');

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
                    'hotel_id' => isset($roomRow['hotel_id']) && $roomRow['hotel_id'] !== '' ? (int) $roomRow['hotel_id'] : null,
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
        foreach ($departureRows as $departureRow) {
            $allocationBootstrapRows[] = [
                'departure_id' => (int) $departureRow->id,
                'travel_date_id' => $departureRow->wp_travel_date_id ? (int) $departureRow->wp_travel_date_id : null,
                'date' => optional($departureRow->start_date)->format('Y-m-d'),
                'rooms' => $departureRow->roomAllocations->map(fn ($allocation) => [
                    'id' => (int) $allocation->id,
                    'room_type' => (string) ($allocation->room_type ?? ''),
                    'quantity' => (int) ($allocation->quantity ?? 0),
                    'capacity_per_room' => (int) ($allocation->capacity_per_room ?? 1),
                    'hotel_id' => $allocation->hotel_id ? (int) $allocation->hotel_id : null,
                ])->values()->all(),
                'manual' => $departureRow->roomAllocations->isNotEmpty(),
            ];
        }
    }
@endphp

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

    <script type="application/json" id="departure-room-allocation-bootstrap">@json($allocationBootstrapRows)</script>
    <script type="application/json" id="departure-room-allocation-room-types">@json($roomTypeSuggestions)</script>
</div>
