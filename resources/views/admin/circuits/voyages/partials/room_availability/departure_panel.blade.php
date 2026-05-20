{{-- Panneau chargé en AJAX : hôtels + stock chambres pour un départ (modal). --}}
<div class="ra-departure-panel" data-departure-id="{{ $departure->id }}">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-3 bg-light border rounded">
        <span class="badge bg-secondary">{{ $departure->status_label }}</span>
        <span class="small text-nowrap">Cap. totale : <strong>{{ (int) ($departure->total_capacity ?? 0) }}</strong></span>
        <span class="small text-nowrap">Réservé : <strong>{{ (int) ($departure->reserved_capacity ?? 0) }}</strong></span>
        <span class="small text-nowrap">Restant : <strong class="text-success">{{ (int) ($departure->available_capacity ?? 0) }}</strong></span>
        <a href="{{ route('admin.circuits.voyages.departures.show', [$voyage, $departure]) }}" class="btn btn-sm btn-outline-secondary ms-auto" target="_blank" rel="noopener">
            <i class="bx bx-link-external"></i> Page départ
        </a>
    </div>

    <div class="mb-3">
        @include('admin.circuits.voyages.departures.partials._settings_card', [
            'voyage' => $voyage,
            'departure' => $departure,
            'statuses' => $statuses,
            'modalAjax' => true,
        ])
    </div>

    @include('admin.circuits.voyages.departures.partials._hotels_section', [
        'voyage' => $voyage,
        'departure' => $departure,
        'hotelsCatalog' => $hotelsCatalog,
        'roomStatuses' => $roomStatuses,
        'modalAjax' => true,
        'layout' => 'accordion',
    ])
</div>

