{{-- Tous les pays du monde (select) + panneau dynamique Villes (WP) — compatible locations[] / multi_location --}}
@php
    $selectedIds = $selectedLocationIds ?? [];
    $worldCountries = $worldCountries ?? [];
    $countryCitiesData = $countryCitiesData ?? [];
@endphp

<div class="destination-country-cities">
    <div class="mb-3">
        <label for="locationCountrySelect" class="form-label fw-medium">Pays</label>
        <select id="locationCountrySelect" class="form-select destination-country-select">
            <option value="">— Choisir un pays —</option>
            @foreach($worldCountries as $code => $name)
                <option value="{{ $code }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="destination-cities-panel destination-cities-panel-dynamic" id="destination-cities-panel-dynamic" style="display: none;">
        <div class="destination-cities-panel-header">
            <span class="destination-cities-panel-title" id="destination-cities-panel-title">Villes</span>
        </div>
        <div class="destination-cities-list" id="destination-cities-list">
            {{-- Rempli par JS à partir de countryCitiesData --}}
        </div>
    </div>
</div>

<script>
(function() {
    window.DESTINATION_COUNTRY_CITIES_DATA = @json($countryCitiesData);
    window.DESTINATION_SELECTED_IDS = @json($selectedIds);
})();
</script>
