@php
    $opt = $option ?? null;
    $isNew = !$opt;
    $departurePlaces = $departurePlaces ?? collect();
    $showDeparturePlace = in_array($type ?? '', ['outbound', 'return'], true);
@endphp

<div class="flight-opt-card" data-flight-opt-index="{{ $index }}">
    {{-- Hidden ID and Type for form submission --}}
    <input type="hidden" name="flight_options[{{ $index }}][id]" value="{{ $opt ? $opt->id : '' }}">
    <input type="hidden" name="flight_options[{{ $index }}][type]" value="{{ $type }}">
    @if($type === 'segment')
        <input type="hidden" name="flight_options[{{ $index }}][day_number]" value="{{ $opt ? $opt->day_number : 1 }}">
    @else
        <input type="hidden" name="flight_options[{{ $index }}][day_number]" value="{{ $type === 'outbound' ? 1 : ($lastDayNumber ?? 1) }}">
    @endif

    {{-- Card Header: Route (From �?' To) with delete button --}}
    <div class="flight-opt-header">
        <div class="flight-opt-route-display">

            <div style="flex: 1; display: flex; align-items: center; gap: 12px;">
                <div style="flex: 1;">
                    <input type="text" 
                           class="form-control form-control-sm flight-opt-from-city" 
                           name="flight_options[{{ $index }}][from_city]" 
                           value="{{ $opt ? $opt->from_city : '' }}" 
                           placeholder="From (ex: Paris)"
                           style="font-weight: 600;">
                </div>
                <div style="color: #999; font-size: 14px;">�?'</div>
                <div style="flex: 1;">
                    <input type="text" 
                           class="form-control form-control-sm flight-opt-to-city" 
                           name="flight_options[{{ $index }}][to_city]" 
                           value="{{ $opt ? $opt->to_city : '' }}" 
                           placeholder="To (ex: Rome)"
                           style="font-weight: 600;">
                </div>
            </div>

            <button type="button" class="btn btn-sm btn-icon btn-outline-danger flight-opt-remove ms-2" title="Supprimer ce vol" style="min-width: 40px;">
                <i class="bx bx-trash"></i>
            </button>
        </div>
    </div>

    {{-- Card Body: All form fields directly editable --}}
    <div class="flight-opt-body p-3">
        <div class="row g-3">
            {{-- Section 1: Airline & Cabin --}}
            <div class="col-md-6">
                <label class="form-label small">Compagnie aérienne</label>
                <select name="flight_options[{{ $index }}][airline_id]" class="form-select form-select-sm">
                    <option value="">�?" Pas de compagnie �?"</option>
                    @foreach($airlines as $a)
                        <option value="{{ $a->id }}" {{ $opt && $opt->airline_id == $a->id ? 'selected' : '' }}>
                            {{ $a->name }} @if($a->code_iata)({{ $a->code_iata }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Classe cabine</label>
                <select name="flight_options[{{ $index }}][cabin]" class="form-select form-select-sm">
                    @foreach(\App\Models\VoyageFlightOption::cabinOptions() as $v => $l)
                        <option value="{{ $v }}" {{ ($opt ? $opt->cabin : 'economy') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Section 2: Dates & Times --}}
            <div class="col-md-4">
                <label class="form-label small">Date départ</label>
                <input type="date" class="form-control form-control-sm" 
                       name="flight_options[{{ $index }}][departure_date]" 
                       value="{{ $opt && $opt->depart_at ? $opt->depart_at->format('Y-m-d') : '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Heure départ</label>
                <input type="time" class="form-control form-control-sm" 
                       name="flight_options[{{ $index }}][departure_time]" 
                       value="{{ $opt && $opt->depart_at ? $opt->depart_at->format('H:i') : '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small">Heure arrivée</label>
                <input type="time" class="form-control form-control-sm" 
                       name="flight_options[{{ $index }}][arrival_time]" 
                       value="{{ $opt && $opt->arrive_at ? $opt->arrive_at->format('H:i') : '' }}">
            </div>

            {{-- Section 3: Bagages --}}
            <div class="col-md-6">
                <label class="form-label small">Bagages cabine (kg)</label>
                <input type="number" class="form-control form-control-sm" 
                       name="flight_options[{{ $index }}][baggage_cabin_kg]" 
                       value="{{ $opt ? $opt->baggage_cabin_kg : '' }}" 
                       min="0" placeholder="ex: 7">
            </div>
            <div class="col-md-6">
                <label class="form-label small">Bagages check-in (kg)</label>
                <input type="number" class="form-control form-control-sm" 
                       name="flight_options[{{ $index }}][baggage_checkin_kg]" 
                       value="{{ $opt ? $opt->baggage_checkin_kg : '' }}" 
                       min="0" placeholder="ex: 20">
            </div>

            {{-- Section 4: Flight Number --}}
            <div class="col-md-6">
                <label class="form-label small">N° de vol</label>
                <input type="text" class="form-control form-control-sm" 
                       name="flight_options[{{ $index }}][flight_number]" 
                       value="{{ $opt ? $opt->flight_number : '' }}" 
                       placeholder="ex: AF1234">
            </div>
            <div class="col-md-6">
                <label class="form-label small">&nbsp;</label>
                <div class="form-check mt-2">
                    <input type="checkbox" class="form-check-input" 
                           name="flight_options[{{ $index }}][is_tentative]" 
                           value="1" 
                           id="tentative-{{ $index }}"
                           {{ $opt && $opt->is_tentative ? 'checked' : '' }}>
                    <label class="form-check-label small" for="tentative-{{ $index }}">
                        Tentative / Sous confirmation
                    </label>
                </div>
            </div>

            {{-- Section 5: Departure Place (for outbound/return only) --}}
            @if($showDeparturePlace)
                <div class="col-12">
                    <label class="form-label small">Lieu de départ <span class="text-muted">(pour affichage client)</span></label>
                    @php
                        $dpSorted = $departurePlaces->values();
                        $selDp = (string) old('flight_options.'.$index.'.departure_place_id', $opt ? (string) ($opt->departure_place_id ?? '') : '');
                    @endphp
                    <select name="flight_options[{{ $index }}][departure_place_id]" class="form-select form-select-sm ve-flight-departure-place-select">
                        <option value="">�?" Aucun �?"</option>
                        @foreach($dpSorted as $dpPos => $place)
                            @php
                                $optVal = $place->id ? (string) $place->id : 'NEW_'.$dpPos;
                            @endphp
                            <option value="{{ $optVal }}" @selected($selDp !== '' && $selDp === (string) $optVal)>
                                {{ $place->name ?? '' }}{{ isset($place->code) && $place->code !== '' ? ' (' . $place->code . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Section 6: Day Selection (for segment flights) --}}
            @if($type === 'segment')
                <div class="col-md-4">
                    <label class="form-label small">Jour du programme</label>
                    <select name="flight_options[{{ $index }}][day_number_edit]" class="form-select form-select-sm flight-opt-day">
                        @for($d = 1; $d <= ($lastDayNumber ?? 6); $d++)
                            <option value="{{ $d }}" {{ $opt && $opt->day_number == $d ? 'selected' : '' }}>
                                Jour {{ $d }}
                            </option>
                        @endfor
                    </select>
                </div>
            @endif
        </div>
    </div>
</div>

