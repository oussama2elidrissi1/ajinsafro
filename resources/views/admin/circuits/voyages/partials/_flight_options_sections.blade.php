{{-- Multi-flight options: Vols Aller / Vols Retour / Vols pendant le circuit --}}
@php
    $flightOptionsWithIndex = $flightOptionsWithIndex ?? [];
    $nextFlightOptionIndex = $nextFlightOptionIndex ?? 0;
    $lastDayNumber = $lastDayNumber ?? 1;
    $airlines = $airlines ?? collect();
    $fmtDate = function($d) {
        if (!$d) return null;
        return $d instanceof \Carbon\Carbon ? $d->format('D, d M') : \Carbon\Carbon::parse($d)->format('D, d M');
    };
    $dash = '—';
@endphp
<style>
.flight-opt-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e9ecef; overflow: hidden; margin-bottom: 12px; }
.flight-opt-card .flight-opt-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8f9fa; border-bottom: 1px solid #e9ecef; }
.flight-opt-card .flight-opt-title { font-size: 13px; font-weight: 600; color: #495057; }
.flight-opt-card .flight-opt-body { display: flex; align-items: stretch; padding: 16px; gap: 16px; }
.flight-opt-card .flight-opt-icon { width: 48px; height: 48px; border-radius: 50%; background: #e7f1ff; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.flight-opt-card .flight-opt-center { flex: 1; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.flight-opt-card .flight-opt-badge { padding: 0 16px 12px; }
.flight-opt-section { margin-bottom: 28px; }
.flight-opt-section h6 { margin-bottom: 12px; }
</style>

<div class="flight-opt-section" data-flight-section="outbound">
    <h6><i class="bx bx-trip"></i> Vols Aller (options) — Jour 1</h6>
    <div class="flight-opt-cards-outbound">
        @foreach($flightOptionsWithIndex as $entry)
            @if($entry['type'] === 'outbound')
                @include('admin.circuits.voyages.partials._flight_option_card', ['index' => $entry['index'], 'option' => $entry['option'], 'type' => 'outbound', 'dayLabel' => 'Jour 1', 'airlines' => $airlines, 'fmtDate' => $fmtDate, 'dash' => $dash])
            @endif
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary btn-add-flight-opt" data-type="outbound" data-day="{{ $lastDayNumber }}"><i class="bx bx-plus"></i> Ajouter un vol Aller</button>
</div>

<div class="flight-opt-section" data-flight-section="return">
    <h6><i class="bx bx-trip"></i> Vols Retour (options) — Jour {{ $lastDayNumber }}</h6>
    <div class="flight-opt-cards-return">
        @foreach($flightOptionsWithIndex as $entry)
            @if($entry['type'] === 'return')
                @include('admin.circuits.voyages.partials._flight_option_card', ['index' => $entry['index'], 'option' => $entry['option'], 'type' => 'return', 'dayLabel' => 'Jour ' . $lastDayNumber, 'airlines' => $airlines, 'fmtDate' => $fmtDate, 'dash' => $dash])
            @endif
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary btn-add-flight-opt" data-type="return" data-day="{{ $lastDayNumber }}"><i class="bx bx-plus"></i> Ajouter un vol Retour</button>
</div>

<div class="flight-opt-section" data-flight-section="segment">
    <h6><i class="bx bx-trip"></i> Vols pendant le circuit (par jour)</h6>
    <div class="flight-opt-cards-segment">
        @foreach($flightOptionsWithIndex as $entry)
            @if($entry['type'] === 'segment')
                @include('admin.circuits.voyages.partials._flight_option_card', ['index' => $entry['index'], 'option' => $entry['option'], 'type' => 'segment', 'dayLabel' => 'Jour ' . ($entry['option']->day_number ?? ''), 'airlines' => $airlines, 'fmtDate' => $fmtDate, 'dash' => $dash, 'lastDayNumber' => $lastDayNumber])
            @endif
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary btn-add-flight-opt" data-type="segment" data-day="{{ $lastDayNumber }}"><i class="bx bx-plus"></i> Ajouter un vol (segment)</button>
</div>

<input type="hidden" id="flight-opt-next-index" value="{{ $nextFlightOptionIndex }}">

{{-- Templates for "Ajouter un vol" (cloned by JS; inputs disabled so not submitted) --}}
<div id="flight-opt-templates" style="display:none" aria-hidden="true">
    <div data-flight-tpl="outbound">
        @include('admin.circuits.voyages.partials._flight_option_card', ['index' => -1, 'option' => null, 'type' => 'outbound', 'dayLabel' => 'Jour 1', 'airlines' => $airlines, 'fmtDate' => $fmtDate, 'dash' => $dash])
    </div>
    <div data-flight-tpl="return">
        @include('admin.circuits.voyages.partials._flight_option_card', ['index' => -1, 'option' => null, 'type' => 'return', 'dayLabel' => 'Jour ' . $lastDayNumber, 'airlines' => $airlines, 'fmtDate' => $fmtDate, 'dash' => $dash])
    </div>
    <div data-flight-tpl="segment">
        @include('admin.circuits.voyages.partials._flight_option_card', ['index' => -1, 'option' => null, 'type' => 'segment', 'dayLabel' => '', 'airlines' => $airlines, 'fmtDate' => $fmtDate, 'dash' => $dash, 'lastDayNumber' => $lastDayNumber])
    </div>
</div>
