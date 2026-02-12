@php
    $opt = $option ?? null;
    $isNew = !$opt;
    $depDate = $opt && $opt->depart_at ? $fmtDate($opt->depart_at) : ($dash ?? '—');
@endphp
<div class="flight-opt-card" data-flight-opt-index="{{ $index }}">
    <div class="flight-opt-view">
        <div class="flight-opt-header">
            <span class="flight-opt-title">
                @if($type === 'segment')
                    Jour {{ $opt ? $opt->day_number : '' }} • 
                @endif
                <span class="flight-opt-route">{{ $opt ? ($opt->from_label . ' → ' . $opt->to_label) : ($dash . ' → ' . $dash) }}</span>
            </span>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 flight-opt-remove" title="Supprimer">REMOVE</button>
        </div>
        <div class="flight-opt-body">
            <div class="flight-opt-icon"><i class="bx bx-trip"></i></div>
            <div class="flight-opt-center">
                <div><div class="small text-muted">Date</div><div class="flight-opt-dep-date">{{ $depDate }}</div><div class="flight-opt-from">{{ $opt ? $opt->from_label : $dash }}</div></div>
                <div class="text-muted">→</div>
                <div><div class="small text-muted">Date</div><div class="flight-opt-arr-date">{{ $depDate }}</div><div class="flight-opt-to">{{ $opt ? $opt->to_label : $dash }}</div></div>
            </div>
            <div class="small text-muted">
                <div>Cabin: <span class="flight-opt-cabin-bag">{{ $opt ? $opt->cabin_baggage_display : $dash }}</span></div>
                <div>Check-in: <span class="flight-opt-checkin-bag">{{ $opt ? $opt->checkin_baggage_display : $dash }}</span></div>
            </div>
        </div>
        @if($opt && $opt->is_tentative)
            <div class="flight-opt-badge"><span class="badge bg-warning text-dark">Tentative</span></div>
        @endif
        <div class="px-3 pb-2"><button type="button" class="btn btn-sm btn-outline-primary flight-opt-edit-btn">Modifier</button></div>
    </div>
    <div class="flight-opt-edit p-3 bg-light rounded border" style="display:none;">
        <input type="hidden" name="flight_options[{{ $index }}][id]" value="{{ $opt ? $opt->id : '' }}" {{ $index === -1 ? 'disabled' : '' }}>
        <input type="hidden" name="flight_options[{{ $index }}][type]" value="{{ $type }}" {{ $index === -1 ? 'disabled' : '' }}>
        @if($type === 'segment')
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="form-label small">Jour</label>
                    <select name="flight_options[{{ $index }}][day_number]" class="form-select form-select-sm flight-opt-day" {{ $index === -1 ? 'disabled' : '' }}>
                        @for($d = 1; $d <= ($lastDayNumber ?? 6); $d++)
                            <option value="{{ $d }}" {{ $opt && $opt->day_number == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        @else
            <input type="hidden" name="flight_options[{{ $index }}][day_number]" value="{{ $type === 'outbound' ? 1 : ($lastDayNumber ?? 1) }}" {{ $index === -1 ? 'disabled' : '' }}>
        @endif
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label small">Compagnie</label>
                <select name="flight_options[{{ $index }}][airline_id]" class="form-select form-select-sm" {{ $index === -1 ? 'disabled' : '' }}>
                    <option value="">—</option>
                    @foreach($airlines as $a)
                        <option value="{{ $a->id }}" {{ $opt && $opt->airline_id == $a->id ? 'selected' : '' }}>{{ $a->name }} @if($a->code_iata)({{ $a->code_iata }})@endif</option>
                    @endforeach
                </select></div>
            <div class="col-md-6"><label class="form-label small">Cabine</label>
                <select name="flight_options[{{ $index }}][cabin]" class="form-select form-select-sm" {{ $index === -1 ? 'disabled' : '' }}>
                    @foreach(\App\Models\VoyageFlightOption::cabinOptions() as $v => $l)
                        <option value="{{ $v }}" {{ ($opt ? $opt->cabin : 'economy') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select></div>
            <div class="col-md-6"><label class="form-label small">From (ville)</label>
                <input type="text" class="form-control form-control-sm" name="flight_options[{{ $index }}][from_city]" value="{{ $opt ? $opt->from_city : '' }}" placeholder="ex. Paris" {{ $index === -1 ? 'disabled' : '' }}></div>
            <div class="col-md-6"><label class="form-label small">To (ville)</label>
                <input type="text" class="form-control form-control-sm" name="flight_options[{{ $index }}][to_city]" value="{{ $opt ? $opt->to_city : '' }}" placeholder="ex. Rome" {{ $index === -1 ? 'disabled' : '' }}></div>
            <div class="col-md-6"><label class="form-label small">Date départ</label>
                <input type="date" class="form-control form-control-sm" name="flight_options[{{ $index }}][departure_date]" value="{{ $opt && $opt->depart_at ? $opt->depart_at->format('Y-m-d') : '' }}" {{ $index === -1 ? 'disabled' : '' }}></div>
            <div class="col-md-6"><label class="form-label small">N° vol</label>
                <input type="text" class="form-control form-control-sm" name="flight_options[{{ $index }}][flight_number]" value="{{ $opt ? $opt->flight_number : '' }}" placeholder="AF1234" {{ $index === -1 ? 'disabled' : '' }}></div>
            <div class="col-md-4"><label class="form-label small">Cabine (kg)</label>
                <input type="number" class="form-control form-control-sm" name="flight_options[{{ $index }}][baggage_cabin_kg]" value="{{ $opt ? $opt->baggage_cabin_kg : '' }}" min="0" placeholder="7" {{ $index === -1 ? 'disabled' : '' }}></div>
            <div class="col-md-4"><label class="form-label small">Check-in (kg)</label>
                <input type="number" class="form-control form-control-sm" name="flight_options[{{ $index }}][baggage_checkin_kg]" value="{{ $opt ? $opt->baggage_checkin_kg : '' }}" min="0" placeholder="20" {{ $index === -1 ? 'disabled' : '' }}></div>
            <div class="col-md-4"><label class="form-label small">&nbsp;</label>
                <div class="form-check mt-1"><input type="checkbox" name="flight_options[{{ $index }}][is_tentative]" value="1" class="form-check-input" {{ $opt && $opt->is_tentative ? 'checked' : '' }} {{ $index === -1 ? 'disabled' : '' }}><label class="form-check-label small">Tentative</label></div></div>
            <div class="col-12"><button type="button" class="btn btn-sm btn-primary flight-opt-save-btn">Enregistrer</button> <button type="button" class="btn btn-sm btn-secondary flight-opt-cancel-btn">Annuler</button></div>
        </div>
    </div>
</div>
