@php
    $selectedIds = collect($selectedLocationIds ?? [])->map(fn ($v) => (int) $v)->filter()->values()->all();
    $worldCountries = $worldCountries ?? [];
    $countryCitiesData = $countryCitiesData ?? [];
    $mergedCitiesByCode = $mergedCitiesByCode ?? [];
    $ensureLocationUrl = route('admin.circuits.voyages.ensure-location');
    $citiesSearchUrl = route('admin.locations.cities');

    $countryIdToCode = [];
    $cityIdToCode = [];
    foreach ($countryCitiesData as $code => $countryData) {
        if (!empty($countryData['id'])) {
            $countryIdToCode[(int) $countryData['id']] = $code;
        }
        foreach (($countryData['cities'] ?? []) as $city) {
            if (!empty($city['id'])) {
                $cityIdToCode[(int) $city['id']] = $code;
            }
        }
    }

    $preselectedCountryCodes = [];
    $preselectedIncludeCountry = [];
    $preselectedCityIds = [];
    $unknownSelectedIds = [];
    foreach ($selectedIds as $id) {
        if (isset($countryIdToCode[$id])) {
            $code = $countryIdToCode[$id];
            $preselectedCountryCodes[$code] = true;
            $preselectedIncludeCountry[$code] = true;
            continue;
        }
        if (isset($cityIdToCode[$id])) {
            $code = $cityIdToCode[$id];
            $preselectedCountryCodes[$code] = true;
            $preselectedCityIds[$id] = true;
            continue;
        }
        $unknownSelectedIds[] = $id;
    }
@endphp

@once
    @push('css')
        <link href="{{ URL::asset('build/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush
    @push('script')
        <script src="{{ URL::asset('build/libs/select2/js/select2.min.js') }}"></script>
    @endpush
@endonce

<div id="modern-location-selector" class="destination-modern-selector">
    <div class="mb-3">
        <label for="destinationCountrySelect" class="form-label fw-semibold">Pays</label>
        <select id="destinationCountrySelect" class="form-select" multiple>
            @foreach($worldCountries as $code => $name)
                <option value="{{ $code }}" {{ isset($preselectedCountryCodes[$code]) ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        <div class="d-flex gap-2 mt-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="destinationSelectAllCountriesModern">Tout sélectionner</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="destinationDeselectAllCountriesModern">Tout désélectionner</button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <input type="text" id="destinationCitySearchModern" class="form-control" style="max-width: 320px;" placeholder="Rechercher une ville...">
                <button type="button" class="btn btn-sm btn-primary" id="destinationCitySearchBtnModern">Rechercher</button>
                <button type="button" class="btn btn-sm btn-outline-danger ms-auto" id="destinationClearCitiesModern">Vider les villes</button>
            </div>
            <div class="small text-muted mb-2" id="destinationCitiesMetaModern">Sélectionnez un ou plusieurs pays.</div>
            <div class="list-group" id="destinationCityResultsModern"></div>
            <div class="d-flex justify-content-between mt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="destinationCityPrevModern" disabled>Précédent</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="destinationCityNextModern" disabled>Suivant</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0">Sélections actuelles</h6>
                <span class="small text-muted" id="destinationSelectedCountModern">Villes (0) sélectionnées</span>
            </div>
            <div class="mb-2">
                <div class="small text-muted mb-1">Pays sélectionnés</div>
                <div id="destinationSelectedCountriesModern" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div>
                <div class="small text-muted mb-1">Villes sélectionnées</div>
                <div id="destinationSelectedCitiesModern" class="d-flex flex-wrap gap-2"></div>
            </div>
        </div>
    </div>

    <div id="destinationHiddenLocationsModern">
        @foreach($unknownSelectedIds as $unknownId)
            <input type="hidden" name="locations[]" value="{{ $unknownId }}">
        @endforeach
    </div>
    <div id="destinationHiddenCountriesModern"></div>
    <div id="destinationHiddenCitiesModern"></div>
    <div id="destinationHiddenIncludeCountriesModern"></div>
</div>

<script>
(function() {
    window.DESTINATION_MODERN_UI_ACTIVE = true;

    var root = document.getElementById('modern-location-selector');
    if (!root) return;

    var countrySelect = document.getElementById('destinationCountrySelect');
    var selectAllBtn = document.getElementById('destinationSelectAllCountriesModern');
    var deselectAllBtn = document.getElementById('destinationDeselectAllCountriesModern');
    var citySearchInput = document.getElementById('destinationCitySearchModern');
    var citySearchBtn = document.getElementById('destinationCitySearchBtnModern');
    var clearCitiesBtn = document.getElementById('destinationClearCitiesModern');
    var cityResults = document.getElementById('destinationCityResultsModern');
    var cityMeta = document.getElementById('destinationCitiesMetaModern');
    var prevBtn = document.getElementById('destinationCityPrevModern');
    var nextBtn = document.getElementById('destinationCityNextModern');
    var selectedCountriesWrap = document.getElementById('destinationSelectedCountriesModern');
    var selectedCitiesWrap = document.getElementById('destinationSelectedCitiesModern');
    var selectedCount = document.getElementById('destinationSelectedCountModern');
    var hiddenLocations = document.getElementById('destinationHiddenLocationsModern');
    var hiddenCountries = document.getElementById('destinationHiddenCountriesModern');
    var hiddenCities = document.getElementById('destinationHiddenCitiesModern');
    var hiddenIncludeCountries = document.getElementById('destinationHiddenIncludeCountriesModern');

    var worldCountries = @json($worldCountries);
    var countryCitiesData = @json($countryCitiesData);
    var mergedCitiesByCode = @json($mergedCitiesByCode);
    var preselectedCityIds = @json(array_map('intval', array_keys($preselectedCityIds)));
    var preselectedIncludeCountry = @json(array_keys($preselectedIncludeCountry));
    var ensureLocationUrl = @json($ensureLocationUrl);
    var citiesSearchUrl = @json($citiesSearchUrl);

    var state = {
        selectedCountries: new Set(Array.from(countrySelect.options).filter(function(opt) { return opt.selected; }).map(function(opt) { return opt.value; })),
        includeCountry: {},
        selectedCities: {},
        page: 1,
        lastPage: 1,
        q: '',
    };

    preselectedIncludeCountry.forEach(function(code) {
        state.includeCountry[code] = true;
    });

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function getCountryId(code) {
        return countryCitiesData[code] && countryCitiesData[code].id ? parseInt(countryCitiesData[code].id, 10) : null;
    }

    function preloadSelectedCities() {
        var cityIdSet = new Set((preselectedCityIds || []).map(function(v) { return parseInt(v, 10); }));
        Object.keys(mergedCitiesByCode || {}).forEach(function(code) {
            (mergedCitiesByCode[code] || []).forEach(function(city) {
                var cityId = city && city.id ? parseInt(city.id, 10) : null;
                if (cityId && cityIdSet.has(cityId)) {
                    state.selectedCities[cityId] = {
                        id: cityId,
                        name: city.title || ('Ville #' + cityId),
                        country_code: code,
                        country: worldCountries[code] || code,
                    };
                }
            });
        });
    }

    function refreshCountrySelectionFromSelect() {
        state.selectedCountries = new Set(Array.from(countrySelect.options).filter(function(opt) { return opt.selected; }).map(function(opt) { return opt.value; }));
        Object.keys(state.includeCountry).forEach(function(code) {
            if (!state.selectedCountries.has(code)) {
                delete state.includeCountry[code];
            }
        });
        Object.keys(state.selectedCities).forEach(function(cityId) {
            var city = state.selectedCities[cityId];
            if (!state.selectedCountries.has(city.country_code) || state.includeCountry[city.country_code]) {
                delete state.selectedCities[cityId];
            }
        });
    }

    function renderHiddenInputs() {
        hiddenLocations.innerHTML = hiddenLocations.querySelectorAll('input[name="locations[]"]').length ? hiddenLocations.innerHTML : '';
        hiddenCountries.innerHTML = '';
        hiddenCities.innerHTML = '';
        hiddenIncludeCountries.innerHTML = '';

        Array.from(state.selectedCountries).forEach(function(code) {
            var countryInput = document.createElement('input');
            countryInput.type = 'hidden';
            countryInput.name = 'countries[]';
            countryInput.value = code;
            hiddenCountries.appendChild(countryInput);

            if (state.includeCountry[code]) {
                var includeInput = document.createElement('input');
                includeInput.type = 'hidden';
                includeInput.name = 'include_country[' + code + ']';
                includeInput.value = '1';
                hiddenIncludeCountries.appendChild(includeInput);

                var countryId = getCountryId(code);
                if (countryId) {
                    var locInput = document.createElement('input');
                    locInput.type = 'hidden';
                    locInput.name = 'locations[]';
                    locInput.value = String(countryId);
                    hiddenLocations.appendChild(locInput);
                }
            }
        });

        Object.keys(state.selectedCities).forEach(function(cityId) {
            var city = state.selectedCities[cityId];
            if (!city || state.includeCountry[city.country_code]) return;

            var cityInput = document.createElement('input');
            cityInput.type = 'hidden';
            cityInput.name = 'cities[]';
            cityInput.value = String(city.id);
            hiddenCities.appendChild(cityInput);

            var locInput = document.createElement('input');
            locInput.type = 'hidden';
            locInput.name = 'locations[]';
            locInput.value = String(city.id);
            hiddenLocations.appendChild(locInput);
        });
    }

    function renderSelectedCountries() {
        selectedCountriesWrap.innerHTML = '';
        Array.from(state.selectedCountries).sort().forEach(function(code) {
            var wrapper = document.createElement('div');
            wrapper.className = 'badge bg-light text-dark border p-2 d-flex align-items-center gap-2';
            wrapper.innerHTML =
                '<span>' + escapeHtml(worldCountries[code] || code) + '</span>' +
                '<label class="form-check form-switch mb-0" title="Inclure tout le pays">' +
                    '<input class="form-check-input include-country-toggle" type="checkbox" data-country-code="' + escapeHtml(code) + '" ' + (state.includeCountry[code] ? 'checked' : '') + '>' +
                '</label>' +
                '<button type="button" class="btn btn-sm btn-link p-0 text-danger remove-country-chip" data-country-code="' + escapeHtml(code) + '">×</button>';
            selectedCountriesWrap.appendChild(wrapper);
        });
    }

    function renderSelectedCities() {
        selectedCitiesWrap.innerHTML = '';
        var count = 0;
        Object.keys(state.selectedCities).forEach(function(cityId) {
            var city = state.selectedCities[cityId];
            if (!city || state.includeCountry[city.country_code]) return;
            count += 1;
            var chip = document.createElement('div');
            chip.className = 'badge bg-info-subtle text-info border p-2 d-flex align-items-center gap-2';
            chip.innerHTML =
                '<span>' + escapeHtml(city.name) + ' (' + escapeHtml(city.country) + ')</span>' +
                '<button type="button" class="btn btn-sm btn-link p-0 text-danger remove-city-chip" data-city-id="' + escapeHtml(cityId) + '">×</button>';
            selectedCitiesWrap.appendChild(chip);
        });
        selectedCount.textContent = 'Villes (' + count + ') sélectionnées';
    }

    function renderAllSelections() {
        renderSelectedCountries();
        renderSelectedCities();
        renderHiddenInputs();
    }

    function getSelectedCountryCodes() {
        return Array.from(state.selectedCountries);
    }

    function ensureLocation(countryCode, cityName) {
        var csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
        var fd = new FormData();
        fd.append('_token', csrf || '');
        fd.append('country_code', countryCode);
        if (cityName) {
            fd.append('city_name', cityName);
        }
        return fetch(ensureLocationUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function(r) { return r.json(); });
    }

    function renderCityResults(items, meta) {
        cityResults.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            cityResults.innerHTML = '<div class="list-group-item text-muted">Aucune ville trouvée.</div>';
        } else {
            items.forEach(function(item) {
                var isIncludedByCountry = !!state.includeCountry[item.country_code];
                var alreadySelected = item.id && !!state.selectedCities[item.id];
                var disabled = isIncludedByCountry ? 'disabled' : '';
                var label = isIncludedByCountry ? 'Pays inclus' : (alreadySelected ? 'Ajoutée' : 'Ajouter');

                var row = document.createElement('div');
                row.className = 'list-group-item d-flex align-items-center justify-content-between gap-2';
                row.innerHTML =
                    '<div>' +
                        '<div class="fw-medium">' + escapeHtml(item.name) + '</div>' +
                        '<div class="small text-muted">' + escapeHtml(item.country || item.country_code || '') + '</div>' +
                    '</div>' +
                    '<button type="button" class="btn btn-sm btn-outline-primary add-city-btn" ' + disabled +
                        ' data-city-id="' + escapeHtml(item.id || '') + '" data-city-name="' + escapeHtml(item.name || '') +
                        '" data-country-code="' + escapeHtml(item.country_code || '') + '" data-country-name="' + escapeHtml(item.country || '') + '">' + label + '</button>';
                cityResults.appendChild(row);
            });
        }

        var total = meta && meta.total ? meta.total : 0;
        var page = meta && meta.page ? meta.page : 1;
        var lastPage = meta && meta.last_page ? meta.last_page : 1;
        cityMeta.textContent = total + ' résultat(s) • Page ' + page + '/' + lastPage;
        prevBtn.disabled = page <= 1;
        nextBtn.disabled = page >= lastPage;
        state.page = page;
        state.lastPage = lastPage;
    }

    function fetchCities() {
        var countries = getSelectedCountryCodes();
        if (!countries.length) {
            renderCityResults([], { total: 0, page: 1, last_page: 1 });
            cityMeta.textContent = 'Sélectionnez un ou plusieurs pays.';
            return;
        }

        var params = new URLSearchParams();
        countries.forEach(function(code) { params.append('country_ids[]', code); });
        params.append('q', state.q || '');
        params.append('page', String(state.page || 1));

        fetch(citiesSearchUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function(r) { return r.json(); })
            .then(function(payload) {
                renderCityResults(payload.data || [], payload.meta || {});
            })
            .catch(function() {
                renderCityResults([], { total: 0, page: 1, last_page: 1 });
                cityMeta.textContent = 'Erreur de chargement des villes.';
            });
    }

    function enhanceCountrySelect() {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            var $select = window.jQuery(countrySelect);
            $select.select2({
                width: '100%',
                placeholder: 'Sélectionnez un ou plusieurs pays',
            });
            $select.on('change', function() {
                refreshCountrySelectionFromSelect();
                state.page = 1;
                renderAllSelections();
                fetchCities();
            });
            return;
        }
        countrySelect.addEventListener('change', function() {
            refreshCountrySelectionFromSelect();
            state.page = 1;
            renderAllSelections();
            fetchCities();
        });
    }

    selectedCountriesWrap.addEventListener('change', function(e) {
        var toggle = e.target.closest('.include-country-toggle');
        if (!toggle) return;
        var code = toggle.getAttribute('data-country-code');
        if (!code) return;

        if (toggle.checked) {
            state.includeCountry[code] = true;
            Object.keys(state.selectedCities).forEach(function(cityId) {
                if (state.selectedCities[cityId] && state.selectedCities[cityId].country_code === code) {
                    delete state.selectedCities[cityId];
                }
            });

            var countryId = getCountryId(code);
            if (!countryId) {
                ensureLocation(code, null).then(function(res) {
                    if (res && res.id) {
                        if (!countryCitiesData[code]) countryCitiesData[code] = { title: worldCountries[code] || code, cities: [] };
                        countryCitiesData[code].id = parseInt(res.id, 10);
                        renderAllSelections();
                    }
                });
            }
        } else {
            delete state.includeCountry[code];
        }

        renderAllSelections();
        fetchCities();
    });

    selectedCountriesWrap.addEventListener('click', function(e) {
        var removeBtn = e.target.closest('.remove-country-chip');
        if (!removeBtn) return;
        var code = removeBtn.getAttribute('data-country-code');
        if (!code) return;

        Array.from(countrySelect.options).forEach(function(opt) {
            if (opt.value === code) opt.selected = false;
        });
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(countrySelect).trigger('change');
        } else {
            refreshCountrySelectionFromSelect();
            delete state.includeCountry[code];
            renderAllSelections();
            fetchCities();
        }
    });

    selectedCitiesWrap.addEventListener('click', function(e) {
        var removeBtn = e.target.closest('.remove-city-chip');
        if (!removeBtn) return;
        var cityId = removeBtn.getAttribute('data-city-id');
        if (!cityId) return;
        delete state.selectedCities[cityId];
        renderAllSelections();
        fetchCities();
    });

    cityResults.addEventListener('click', function(e) {
        var addBtn = e.target.closest('.add-city-btn');
        if (!addBtn || addBtn.disabled) return;

        var cityIdRaw = addBtn.getAttribute('data-city-id');
        var cityName = addBtn.getAttribute('data-city-name') || '';
        var countryCode = addBtn.getAttribute('data-country-code') || '';
        var countryName = addBtn.getAttribute('data-country-name') || (worldCountries[countryCode] || countryCode);
        if (!countryCode || state.includeCountry[countryCode]) return;

        var numericCityId = cityIdRaw ? parseInt(cityIdRaw, 10) : null;
        if (numericCityId) {
            state.selectedCities[numericCityId] = {
                id: numericCityId,
                name: cityName,
                country_code: countryCode,
                country: countryName,
            };
            renderAllSelections();
            fetchCities();
            return;
        }

        ensureLocation(countryCode, cityName).then(function(res) {
            if (!res || !res.id) return;
            var newId = parseInt(res.id, 10);
            if (!newId) return;
            state.selectedCities[newId] = {
                id: newId,
                name: res.title || cityName,
                country_code: countryCode,
                country: countryName,
            };
            renderAllSelections();
            fetchCities();
        });
    });

    citySearchBtn.addEventListener('click', function() {
        state.q = citySearchInput.value || '';
        state.page = 1;
        fetchCities();
    });

    citySearchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            state.q = citySearchInput.value || '';
            state.page = 1;
            fetchCities();
        }
    });

    prevBtn.addEventListener('click', function() {
        if (state.page <= 1) return;
        state.page -= 1;
        fetchCities();
    });

    nextBtn.addEventListener('click', function() {
        if (state.page >= state.lastPage) return;
        state.page += 1;
        fetchCities();
    });

    selectAllBtn.addEventListener('click', function() {
        Array.from(countrySelect.options).forEach(function(opt) { opt.selected = true; });
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(countrySelect).trigger('change');
        } else {
            refreshCountrySelectionFromSelect();
            state.page = 1;
            renderAllSelections();
            fetchCities();
        }
    });

    deselectAllBtn.addEventListener('click', function() {
        Array.from(countrySelect.options).forEach(function(opt) { opt.selected = false; });
        state.selectedCities = {};
        state.includeCountry = {};
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(countrySelect).trigger('change');
        } else {
            refreshCountrySelectionFromSelect();
            state.page = 1;
            renderAllSelections();
            fetchCities();
        }
    });

    clearCitiesBtn.addEventListener('click', function() {
        state.selectedCities = {};
        renderAllSelections();
        fetchCities();
    });

    preloadSelectedCities();
    enhanceCountrySelect();
    refreshCountrySelectionFromSelect();
    renderAllSelections();
    fetchCities();
})();
</script>
