<div
    id="day-builder-flights-manager"
    data-total-days="{{ $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1) }}"
>
    @include('admin.circuits.voyages.partials._flight_manager', [
        'mode' => 'drawer',
        'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
        'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
        'lastDayNumber' => $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1),
        'airlines' => $airlines ?? collect(),
        'dayNumber' => 1,
        'totalDays' => $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1)
    ])
</div>
