@php
    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int) ($meta['duration_day'] ?? 1));
@endphp
<div class="card ve-pane-card mb-3">
    <div class="card-body">
        <h5 class="card-title mb-3"><i class="bx bx-car"></i> Transferts (plusieurs par jour possibles)</h5>
        <div id="tour-transfers-anchor">
            @include('admin.circuits.voyages.partials._tour_transfers_section')
        </div>
    </div>
</div>
