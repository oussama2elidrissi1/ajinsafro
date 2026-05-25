<div class="tab-pane" id="flights" role="tabpanel">
                @php 
                    $lastDayNumber = $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1); 
                @endphp

                <p class="text-muted small mb-3">Les lieux de depart se gerent dans l'etape Disponibilites.</p>

                {{-- Utilisation du Flight Manager réutilisable en mode complet --}}
                @include('admin.circuits.voyages.partials._flight_manager', [
                    'mode' => 'full',
                    'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
                    'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
                    'lastDayNumber' => $lastDayNumber,
                    'airlines' => $airlines ?? collect(),
                    'departurePlaces' => $departurePlaces ?? collect(),
                ])
            </div>


