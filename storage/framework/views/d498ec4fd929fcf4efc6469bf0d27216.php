
<?php
    $selectedIds = $selectedLocationIds ?? [];
    $worldCountries = $worldCountries ?? [];
    $countryCitiesData = $countryCitiesData ?? [];
    $mergedCitiesByCode = $mergedCitiesByCode ?? [];
    $ensureLocationUrl = route('admin.circuits.voyages.ensure-location');
    $selectedIdStrings = collect($selectedIds)->map(fn ($id) => (string) $id)->filter()->values()->all();
    $selectedLocationLabels = [];

    foreach ($countryCitiesData as $countryData) {
        $countryId = isset($countryData['id']) ? (string) $countryData['id'] : null;
        if ($countryId && in_array($countryId, $selectedIdStrings, true)) {
            $selectedLocationLabels[$countryId] = (string) ($countryData['title'] ?? $countryId);
        }

        foreach (($countryData['cities'] ?? []) as $cityData) {
            $cityId = isset($cityData['id']) ? (string) $cityData['id'] : null;
            if ($cityId && in_array($cityId, $selectedIdStrings, true)) {
                $selectedLocationLabels[$cityId] = (string) ($cityData['title'] ?? $cityId);
            }
        }
    }

    foreach ($mergedCitiesByCode as $cities) {
        foreach (($cities ?? []) as $cityData) {
            $cityId = isset($cityData['id']) ? (string) $cityData['id'] : null;
            if ($cityId && in_array($cityId, $selectedIdStrings, true) && !isset($selectedLocationLabels[$cityId])) {
                $selectedLocationLabels[$cityId] = (string) ($cityData['title'] ?? $cityId);
            }
        }
    }
?>

<div class="destination-country-cities">
    <div id="destinationSelectedLocationsFallback" class="d-none" aria-hidden="true">
        <?php $__currentLoopData = $selectedIdStrings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $selectedLocationId): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <input type="hidden" name="locations[]" value="<?php echo e($selectedLocationId); ?>" data-destination-selected-fallback="1">
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <div class="destination-modal-actions mb-3">
        <button type="button" class="btn btn-sm btn-outline-primary destination-open-countries" data-bs-toggle="modal" data-bs-target="#destinationCountriesModal">
            <i class="bx bx-world me-1"></i> Choisir les pays
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary destination-open-cities" data-bs-toggle="modal" data-bs-target="#destinationCitiesModal">
            <i class="bx bx-map-alt me-1"></i> Choisir les villes
        </button>
    </div>
</div>

<div class="modal fade destination-modal edit-v2-taxonomy-modal edit-v2-taxonomy-modal--countries" id="destinationCountriesModal" tabindex="-1" aria-labelledby="destinationCountriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
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
                    <div class="destination-country-multi-wrap">
                        <div class="taxonomy-toolbar">
                            <div class="taxonomy-section-label">Pays disponibles</div>
                            <div class="taxonomy-toolbar-row">
                                <div class="destination-country-add-wrap position-relative">
                                    <input type="text" class="form-control form-control-sm destination-country-add-search" id="destinationCountryAddSearch" placeholder="Rechercher et ajouter des pays" autocomplete="off">
                                    <div class="destination-country-autocomplete-dropdown" id="destinationCountryAutocompleteDropdown"></div>
                                </div>
                                <input type="text" class="form-control form-control-sm destination-country-search" id="destinationCountrySearch" placeholder="Filtrer la liste des pays" autocomplete="off">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="destinationSelectAllCountries">Tout sélectionner</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="destinationDeselectAllCountries">Tout désélectionner</button>
                            </div>
                        </div>
                        <div class="destination-country-list taxonomy-scroll taxonomy-grid" id="destinationCountryList">
                            <?php $__currentLoopData = $worldCountries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="destination-country-option-label taxonomy-option">
                                    <input type="checkbox" class="destination-country-option" value="<?php echo e($code); ?>" data-country-name="<?php echo e(e($name)); ?>">
                                    <span><?php echo e($name); ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="taxonomy-selection-count" id="destinationCountriesSelectedCount">0 pays sélectionné</span>
                <span class="taxonomy-footer-spacer"></span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Valider la sélection</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade destination-modal edit-v2-taxonomy-modal edit-v2-taxonomy-modal--cities" id="destinationCitiesModal" tabindex="-1" aria-labelledby="destinationCitiesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
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
                    <div class="taxonomy-toolbar destination-cities-panel-header">
                        <div class="taxonomy-section-label" id="destination-cities-panel-title">Villes disponibles</div>
                        <div class="taxonomy-toolbar-row destination-cities-panel-actions">
                            <div class="destination-city-autocomplete-wrap position-relative">
                                <input type="text" class="form-control form-control-sm destination-city-add-search" id="destinationCityAddSearch" placeholder="Rechercher et ajouter des villes" autocomplete="off">
                                <div class="destination-city-autocomplete-dropdown" id="destinationCityAutocompleteDropdown"></div>
                            </div>
                            <input type="text" class="form-control form-control-sm destination-city-search" id="destinationCitySearch" placeholder="Filtrer la liste" autocomplete="off">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="destinationSelectAllCities">Tout sélectionner</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="destinationDeselectAllCities">Tout désélectionner</button>
                        </div>
                    </div>
                    <div class="destination-cities-list-wrapper taxonomy-scroll">
                        <div class="destination-cities-list" id="destination-cities-list"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="taxonomy-selection-count" id="destinationCitiesSelectedCount">0 ville sélectionnée</span>
                <span class="taxonomy-footer-spacer"></span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Valider la sélection</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    window.DESTINATION_COUNTRY_CITIES_DATA = <?php echo json_encode($countryCitiesData, 15, 512) ?>;
    window.DESTINATION_MERGED_CITIES = <?php echo json_encode($mergedCitiesByCode, 15, 512) ?>;
    window.DESTINATION_SELECTED_IDS = <?php echo json_encode($selectedIds, 15, 512) ?>;
    window.DESTINATION_SELECTED_LABELS = <?php echo json_encode($selectedLocationLabels, 15, 512) ?>;
    window.DESTINATION_WORLD_COUNTRIES = <?php echo json_encode($worldCountries, 15, 512) ?>;
    window.DESTINATION_ENSURE_LOCATION_URL = <?php echo json_encode($ensureLocationUrl, 15, 512) ?>;

    document.addEventListener('DOMContentLoaded', function () {
        function cleanTaxonomyLabel(label) {
            if (!label) return '';

            return String(label)
                .replace(/�+/g, '')
                .replace(/Âº/g, '-')
                .replace(/º/g, '-')
                .replace(/[»«]/g, '')
                .replace(/\s*[-–—]\s*/g, ' - ')
                .replace(/\s{2,}/g, ' ')
                .trim();
        }

        document.querySelectorAll('#destinationCountryList .taxonomy-option span').forEach(function (label) {
            label.textContent = cleanTaxonomyLabel(label.textContent);
        });

        window.setTimeout(function () {
            var chipsContainer = document.getElementById('locationChipsContainer');
            var countText = document.getElementById('locationCountText');
            var labels = window.DESTINATION_SELECTED_LABELS || {};
            var entries = Object.keys(labels).map(function (id) {
                return { id: id, title: cleanTaxonomyLabel(labels[id]) };
            }).filter(function (item) {
                return item.title;
            });

            if (!chipsContainer || chipsContainer.children.length || !entries.length) return;

            chipsContainer.innerHTML = '';
            entries.forEach(function (item) {
                var chip = document.createElement('span');
                chip.className = 'destination-ux-chip destination-ux-chip--readonly';
                chip.textContent = item.title;
                chipsContainer.appendChild(chip);
            });
            if (countText) {
                countText.textContent = entries.length + ' location(s) sélectionnée(s)';
            }
        }, 600);
    });
})();
</script>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\location-country-cities.blade.php ENDPATH**/ ?>