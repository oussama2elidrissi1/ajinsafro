{{-- Choix multiple de pays + catalogue villes (world_cities + WP). Recherche pays et villes, Tout sélectionner/désélectionner. --}}
@php
    $selectedIds = $selectedLocationIds ?? [];
    $worldCountries = $worldCountries ?? [];
    $countryCitiesData = $countryCitiesData ?? [];
    $mergedCitiesByCode = $mergedCitiesByCode ?? [];
    $ensureLocationUrl = route('admin.circuits.voyages.ensure-location');
@endphp

<div class="destination-country-cities">
    <div class="mb-3">
        <label class="form-label fw-medium">Pays (choix multiple)</label>
        <div class="destination-country-multi-wrap">
            <div class="destination-country-add-wrap position-relative mb-2">
                <input type="text" class="form-control form-control-sm destination-country-add-search" id="destinationCountryAddSearch" placeholder="Rechercher et ajouter des pays…" autocomplete="off">
                <div class="destination-country-autocomplete-dropdown" id="destinationCountryAutocompleteDropdown"></div>
            </div>
            <input type="text" class="form-control form-control-sm destination-country-search mb-2" id="destinationCountrySearch" placeholder="Filtrer la liste des pays…" autocomplete="off">
            <div class="destination-country-multi-actions mb-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="destinationSelectAllCountries">Tout sélectionner</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="destinationDeselectAllCountries">Tout désélectionner</button>
            </div>
            <div class="destination-country-list" id="destinationCountryList">
                @foreach($worldCountries as $code => $name)
                    <label class="destination-country-option-label">
                        <input type="checkbox" class="destination-country-option" value="{{ $code }}" data-country-name="{{ e($name) }}">
                        <span>{{ $name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="destination-cities-panel destination-cities-panel-dynamic" id="destination-cities-panel-dynamic" style="display: none;">
        <div class="destination-cities-panel-header d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="destination-cities-panel-title" id="destination-cities-panel-title">Villes (choix multiple)</span>
            <div class="destination-cities-panel-actions ms-auto d-flex flex-wrap align-items-center gap-2">
                <div class="destination-city-autocomplete-wrap position-relative">
                    <input type="text" class="form-control form-control-sm destination-city-add-search" id="destinationCityAddSearch" placeholder="Rechercher et ajouter des villes…" style="min-width: 220px;" autocomplete="off">
                    <div class="destination-city-autocomplete-dropdown" id="destinationCityAutocompleteDropdown"></div>
                </div>
                <input type="text" class="form-control form-control-sm destination-city-search" id="destinationCitySearch" placeholder="Filtrer la liste…" style="max-width: 160px;" autocomplete="off">
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
