{{-- Tous les pays + catalogue villes (world_cities + WP). Recherche, Tout sélectionner/désélectionner, création WP à la volée. --}}
@php
    $selectedIds = $selectedLocationIds ?? [];
    $worldCountries = $worldCountries ?? [];
    $countryCitiesData = $countryCitiesData ?? [];
    $mergedCitiesByCode = $mergedCitiesByCode ?? [];
    $ensureLocationUrl = route('admin.circuits.voyages.ensure-location');
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
        <div class="destination-cities-panel-header d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="destination-cities-panel-title" id="destination-cities-panel-title">Villes</span>
            <div class="destination-cities-panel-actions ms-auto d-flex flex-wrap align-items-center gap-2">
                <input type="text" class="form-control form-control-sm destination-city-search" id="destinationCitySearch" placeholder="Rechercher une ville…" style="max-width: 200px;" autocomplete="off">
                <button type="button" class="btn btn-sm btn-outline-primary" id="destinationSelectAllCities">Tout sélectionner</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="destinationDeselectAllCities">Tout désélectionner</button>
            </div>
        </div>
        <div class="destination-cities-list-wrapper">
            <div class="destination-cities-list" id="destination-cities-list"></div>
        </div>
    </div>
</div>

<script>
(function() {
    window.DESTINATION_COUNTRY_CITIES_DATA = @json($countryCitiesData);
    window.DESTINATION_MERGED_CITIES = @json($mergedCitiesByCode);
    window.DESTINATION_SELECTED_IDS = @json($selectedIds);
    window.DESTINATION_WORLD_COUNTRIES = @json($worldCountries);
    window.DESTINATION_ENSURE_LOCATION_URL = @json($ensureLocationUrl);
})();
</script>
