@php
    $lastDayNumber = $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1);
@endphp

@include('admin.circuits.voyages.partials._departure_places_inline', ['departurePlaces' => $departurePlaces ?? collect()])

@include('admin.circuits.voyages.partials._flight_manager', [
    'mode' => 'full',
    'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
    'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
    'lastDayNumber' => $lastDayNumber,
    'airlines' => $airlines ?? collect(),
    'departurePlaces' => $departurePlaces ?? collect(),
    'without_flight' => empty($flightOptionsWithIndex),
])
