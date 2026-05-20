@php
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
@endphp

<details class="tour-room-date-availability-panel mt-3" {{ $roomAvailabilityOpen ? 'open' : '' }}>
    <summary class="small fw-semibold text-primary">
        Disponibilité par date
        <span class="text-muted fw-normal">({{ $travelDatesList->count() }} départ{{ $travelDatesList->count() > 1 ? 's' : '' }})</span>
    </summary>

    @if($travelDatesList->isEmpty())
        <div class="alert alert-warning py-2 px-3 small mt-2 mb-0">
            Ajoutez d'abord des dates dans l'onglet Disponibilité pour gérer le stock hôtel par départ.
        </div>
    @else
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
                    @foreach($travelDatesList as $dateIndex => $travelDate)
                        @php
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
                        @endphp
                        <tr class="tour-room-date-availability-row"
                            data-date-index="{{ $dateIndex }}"
                            data-travel-date-id="{{ $dateId }}"
                            data-date="{{ $dateValue }}">
                            <td>
                                <div class="fw-semibold small">{{ optional($travelDate->date)->format('d/m/Y') }}</div>
                                <div class="text-muted x-small">{{ $dateValue }}</div>
                                @if($dateAvailabilityId !== '')
                                    <input type="hidden" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][date_availabilities][{{ $dateIndex }}][id]" value="{{ $dateAvailabilityId }}">
                                @endif
                                <input type="hidden" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][date_availabilities][{{ $dateIndex }}][travel_date_id]" value="{{ $dateId }}">
                                <input type="hidden" name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][date_availabilities][{{ $dateIndex }}][date]" value="{{ $dateValue }}">
                            </td>
                            <td>
                                <input type="number"
                                    class="form-control form-control-sm tour-room-date-available-rooms"
                                    name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][date_availabilities][{{ $dateIndex }}][available_rooms]"
                                    value="{{ $availableRoomsValue }}"
                                    min="0">
                            </td>
                            <td>
                                <input type="number"
                                    class="form-control form-control-sm tour-room-date-available-places"
                                    name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][date_availabilities][{{ $dateIndex }}][available_places]"
                                    value="{{ $availablePlacesValue }}"
                                    min="0">
                            </td>
                            <td>
                                <select class="form-select form-select-sm tour-room-date-status"
                                    name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][date_availabilities][{{ $dateIndex }}][status]">
                                    @foreach($availabilityStatuses as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}" {{ $statusValue === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number"
                                    class="form-control form-control-sm tour-room-date-supplement"
                                    name="tour_hotels[{{ $hi }}][rooms][{{ $ri }}][date_availabilities][{{ $dateIndex }}][supplement]"
                                    value="{{ $dateSupplementValue }}"
                                    min="0"
                                    step="0.01">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</details>

