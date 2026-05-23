{{-- Choix multiple de pays + catalogue villes (world_cities + WP). Recherche pays et villes, Tout sélectionner/désélectionner. --}}
@php
    $selectedIds = $selectedLocationIds ?? [];
    $worldCountries = $worldCountries ?? [];
    $countryCitiesData = $countryCitiesData ?? [];
    $mergedCitiesByCode = $mergedCitiesByCode ?? [];
    $ensureLocationUrl = route('admin.circuits.voyages.ensure-location');
@endphp

<div class="destination-country-cities">
    <div class="destination-modal-actions mb-3">
        <button type="button" class="btn btn-sm btn-outline-primary destination-open-countries" data-bs-toggle="modal" data-bs-target="#destinationCountriesModal">
            <i class="bx bx-world me-1"></i> Choisir les pays
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary destination-open-cities" data-bs-toggle="modal" data-bs-target="#destinationCitiesModal">
            <i class="bx bx-map-alt me-1"></i> Choisir les villes
        </button>
    </div>
</div>

<div class="modal fade destination-modal" id="destinationCountriesModal" tabindex="-1" aria-labelledby="destinationCountriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="destinationCountriesModalLabel">Sélection des pays</h5>
                    <div class="text-muted small">Choisissez un ou plusieurs pays pour filtrer les villes.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="destinationCountriesModalBody">
                <div class="mb-3 destination-country-modal-panel">
                    <label class="form-label fw-medium">Pays (choix multiple)</label>
                    <div class="destination-country-multi-wrap">
                        <div class="destination-country-add-wrap position-relative mb-2">
                            <input type="text" class="form-control form-control-sm destination-country-add-search" id="destinationCountryAddSearch" placeholder="Rechercher et ajouter des pays�?�" autocomplete="off">
                            <div class="destination-country-autocomplete-dropdown" id="destinationCountryAutocompleteDropdown"></div>
                        </div>
                        <input type="text" class="form-control form-control-sm destination-country-search mb-2" id="destinationCountrySearch" placeholder="Filtrer la liste des pays�?�" autocomplete="off">
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade destination-modal" id="destinationCitiesModal" tabindex="-1" aria-labelledby="destinationCitiesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="destinationCitiesModalLabel">Sélection des villes</h5>
                    <div class="text-muted small">Sélection multiple, recherche rapide, actions de masse.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="destinationCitiesModalBody">
                <div class="destination-cities-panel destination-cities-panel-dynamic" id="destination-cities-panel-dynamic" style="display: none;">
                    <div class="destination-cities-panel-header d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="destination-cities-panel-title" id="destination-cities-panel-title">Villes (choix multiple)</span>
                        <div class="destination-cities-panel-actions ms-auto d-flex flex-wrap align-items-center gap-2">
                            <div class="destination-city-autocomplete-wrap position-relative">
                                <input type="text" class="form-control form-control-sm destination-city-add-search" id="destinationCityAddSearch" placeholder="Rechercher et ajouter des villes�?�" style="min-width: 220px;" autocomplete="off">
                                <div class="destination-city-autocomplete-dropdown" id="destinationCityAutocompleteDropdown"></div>
                            </div>
                            <input type="text" class="form-control form-control-sm destination-city-search" id="destinationCitySearch" placeholder="Filtrer la liste�?�" style="max-width: 160px;" autocomplete="off">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="destinationSelectAllCities">Tout sélectionner</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="destinationDeselectAllCities">Tout désélectionner</button>
                        </div>
                    </div>
                    <div class="destination-cities-list-wrapper">
                        <div class="destination-cities-list" id="destination-cities-list"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
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

