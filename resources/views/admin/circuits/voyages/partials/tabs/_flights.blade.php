<div class="tab-pane" id="flights" role="tabpanel">
                @php 
                    $lastDayNumber = $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1); 
                @endphp

                {{-- Lieux de dÃ©part (Ã©ditables) "â€ source unique : vols gÃ©rÃ©s dans les options ci-dessous avec "Lieu de dÃ©part" --}}
                @include('admin.circuits.voyages.partials._departure_places_inline', ['departurePlaces' => $departurePlaces ?? collect()])

                {{-- Utilisation du Flight Manager rÃ©utilisable en mode complet --}}
                @include('admin.circuits.voyages.partials._flight_manager', [
                    'mode' => 'full',
                    'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
                    'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
                    'lastDayNumber' => $lastDayNumber,
                    'airlines' => $airlines ?? collect(),
                    'departurePlaces' => $departurePlaces ?? collect(),
                ])
            </div>

