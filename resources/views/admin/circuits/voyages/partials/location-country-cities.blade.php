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
    $preselectedCountryIds = [];
    $preselectedIncludeCountryIds = [];
    $preselectedCityIds = [];
    $unknownSelectedIds = [];

    foreach ($selectedIds as $id) {
        if (isset($countryIdToCode[$id])) {
            $code = $countryIdToCode[$id];
            $preselectedCountryCodes[$code] = true;
            $preselectedCountryIds[$id] = true;
            $preselectedIncludeCountryIds[$id] = true;
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
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <label for="destinationCountrySelect" class="form-label fw-semibold">Pays</label>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                        <input type="text" id="country-filter" class="form-control" style="max-width: 320px;" placeholder="Rechercher un pays...">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="country-filter-reset">Réinitialiser</button>
                        <span class="small text-muted ms-auto" id="country-filter-count">0 résultats</span>
                    </div>
                    <select id="destinationCountrySelect" class="form-select" multiple>
                        @foreach($worldCountries as $code => $name)
                            <option value="{{ $code }}" data-country-id="{{ $countryCitiesData[$code]['id'] ?? '' }}" {{ isset($preselectedCountryCodes[$code]) ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="destinationSelectAllCountriesModern">Tout sélectionner</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="destinationDeselectAllCountriesModern">Tout désélectionner</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
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
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0">Sélections actuelles</h6>
                <span class="small text-muted" id="destinationSelectedGlobalCountModern">0 destination(s) sélectionnée(s)</span>
            </div>
            <div class="mb-2">
                <div class="small text-muted mb-1">Pays sélectionnés</div>
                <div id="destinationSelectedCountriesModern" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div>
                <div class="small text-muted mb-1">Villes sélectionnées <span id="destinationSelectedCountModern">(0)</span></div>
                <div id="destinationSelectedCitiesModern" class="d-flex flex-wrap gap-2"></div>
            </div>
        </div>
    </div>

    <div id="destinationHiddenLegacyLocationsModern">
        @foreach($unknownSelectedIds as $unknownId)
            <input type="hidden" name="locations[]" value="{{ $unknownId }}">
        @endforeach
    </div>
    <div id="destinationHiddenDynamicLocationsModern"></div>
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
    var countryFilterInput = document.getElementById('country-filter');
    var countryFilterResetBtn = document.getElementById('country-filter-reset');
    var countryFilterCount = document.getElementById('country-filter-count');
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
    var selectedGlobalCount = document.getElementById('destinationSelectedGlobalCountModern');
    var hiddenDynamicLocations = document.getElementById('destinationHiddenDynamicLocationsModern');
    var hiddenCountries = document.getElementById('destinationHiddenCountriesModern');
    var hiddenCities = document.getElementById('destinationHiddenCitiesModern');
    var hiddenIncludeCountries = document.getElementById('destinationHiddenIncludeCountriesModern');

    var worldCountries = @json($worldCountries);
    var countryCitiesData = @json($countryCitiesData);
    var mergedCitiesByCode = @json($mergedCitiesByCode);
    var preselectedCityIds = @json(array_map('intval', array_keys($preselectedCityIds)));
    var preselectedIncludeCountryIds = @json(array_map('intval', array_keys($preselectedIncludeCountryIds)));
    var ensureLocationUrl = @json($ensureLocationUrl);
    var citiesSearchUrl = @json($citiesSearchUrl);

    var state = {
        selectedCountries: new Set(Array.from(countrySelect.options).filter(function(opt) { return opt.selected; }).map(function(opt) { return opt.value; })),
        includeCountry: {},
        selectedCities: {},
        countryCodeToId: {},
        page: 1,
        lastPage: 1,
        q: '',
    };

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function normalizeText(str) {
        return String(str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function applyCountryFilter() {
        var term = normalizeText(countryFilterInput ? countryFilterInput.value : '');
        var visibleCount = 0;

        Array.from(countrySelect.options).forEach(function(opt) {
            var label = normalizeText(opt.textContent || opt.innerText || '');
            var match = !term || label.indexOf(term) !== -1;
            opt.hidden = !match;
            if (match) {
                visibleCount += 1;
            }
        });

        if (countryFilterCount) {
            countryFilterCount.textContent = visibleCount + ' résultat' + (visibleCount > 1 ? 's' : '');
        }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(countrySelect).trigger('change.select2');
        }
    }

    function getCountryId(code) {
        if (state.countryCodeToId[code]) return parseInt(state.countryCodeToId[code], 10);
        var option = Array.from(countrySelect.options).find(function(opt) { return opt.value === code; });
        if (option) {
            var fromOption = option.getAttribute('data-country-id');
            if (fromOption) {
                state.countryCodeToId[code] = parseInt(fromOption, 10);
                return state.countryCodeToId[code];
            }
        }
        if (countryCitiesData[code] && countryCitiesData[code].id) {
            state.countryCodeToId[code] = parseInt(countryCitiesData[code].id, 10);
            return state.countryCodeToId[code];
        }
        return null;
    }

    function getSelectedCountryCodes() {
        return Array.from(state.selectedCountries);
    }

    function getSelectedCountryIds() {
        var ids = [];
        getSelectedCountryCodes().forEach(function(code) {
            var id = getCountryId(code);
            if (id) ids.push(id);
        });
        return ids;
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
                        country_name: worldCountries[code] || code,
                    };
                }
            });
        });

        (preselectedIncludeCountryIds || []).forEach(function(countryId) {
            var foundCode = null;
            Array.from(countrySelect.options).some(function(opt) {
                var id = opt.getAttribute('data-country-id');
                if (id && parseInt(id, 10) === parseInt(countryId, 10)) {
                    foundCode = opt.value;
                    return true;
                }
                return false;
            });
            if (foundCode) {
                state.includeCountry[foundCode] = true;
                state.countryCodeToId[foundCode] = parseInt(countryId, 10);
            }
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

    function ensureSelectedCountriesExist() {
        var jobs = [];
        getSelectedCountryCodes().forEach(function(code) {
            if (getCountryId(code)) return;
            jobs.push(
                ensureLocation(code, null).then(function(res) {
                    if (!res || !res.id) return;
                    var id = parseInt(res.id, 10);
                    if (!id) return;
                    state.countryCodeToId[code] = id;
                    if (!countryCitiesData[code]) countryCitiesData[code] = { title: worldCountries[code] || code, cities: [] };
                    countryCitiesData[code].id = id;
                    Array.from(countrySelect.options).forEach(function(opt) {
                        if (opt.value === code) opt.setAttribute('data-country-id', String(id));
                    });
                }).catch(function() {})
            );
        });

        return Promise.all(jobs).then(function() {
            renderAllSelections();
        });
    }

    function renderHiddenInputs() {
        hiddenDynamicLocations.innerHTML = '';
        hiddenCountries.innerHTML = '';
        hiddenCities.innerHTML = '';
        hiddenIncludeCountries.innerHTML = '';

        getSelectedCountryCodes().forEach(function(code) {
            var countryId = getCountryId(code);
            if (!countryId) return;

            var countryInput = document.createElement('input');
            countryInput.type = 'hidden';
            countryInput.name = 'countries[]';
            countryInput.value = String(countryId);
            hiddenCountries.appendChild(countryInput);

            if (state.includeCountry[code]) {
                var includeInput = document.createElement('input');
                includeInput.type = 'hidden';
                includeInput.name = 'include_country[]';
                includeInput.value = String(countryId);
                hiddenIncludeCountries.appendChild(includeInput);

                var countryLocInput = document.createElement('input');
                countryLocInput.type = 'hidden';
                countryLocInput.name = 'locations[]';
                countryLocInput.value = String(countryId);
                hiddenDynamicLocations.appendChild(countryLocInput);
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
            hiddenDynamicLocations.appendChild(locInput);
        });
    }

    function renderSelectedCountries() {
        selectedCountriesWrap.innerHTML = '';

        getSelectedCountryCodes().sort().forEach(function(code) {
            var countryId = getCountryId(code);
            var wrapper = document.createElement('div');
            wrapper.className = 'badge bg-light text-dark border p-2 d-flex align-items-center gap-2';
            wrapper.innerHTML =
                '<span>' + escapeHtml(worldCountries[code] || code) + (state.includeCountry[code] ? ' <span class="badge bg-warning text-dark ms-1">Pays entier</span>' : '') + '</span>' +
                '<label class="form-check form-switch mb-0" title="Inclure le pays entier">' +
                    '<input class="form-check-input include-country-toggle" type="checkbox" data-country-code="' + escapeHtml(code) + '" data-country-id="' + escapeHtml(countryId || '') + '" ' + (state.includeCountry[code] ? 'checked' : '') + '>' +
                '</label>' +
                '<button type="button" class="btn btn-sm btn-link p-0 text-danger remove-country-chip" data-country-code="' + escapeHtml(code) + '">×</button>';
            selectedCountriesWrap.appendChild(wrapper);
        });
    }

    function renderSelectedCities() {
        selectedCitiesWrap.innerHTML = '';
        var cityCount = 0;

        Object.keys(state.selectedCities).forEach(function(cityId) {
            var city = state.selectedCities[cityId];
            if (!city || state.includeCountry[city.country_code]) return;

            cityCount += 1;
            var chip = document.createElement('div');
            chip.className = 'badge bg-info-subtle text-info border p-2 d-flex align-items-center gap-2';
            chip.innerHTML =
                '<span>' + escapeHtml(city.name) + ' (' + escapeHtml(city.country_name || city.country_code || '') + ')</span>' +
                '<button type="button" class="btn btn-sm btn-link p-0 text-danger remove-city-chip" data-city-id="' + escapeHtml(cityId) + '">×</button>';
            selectedCitiesWrap.appendChild(chip);
        });

        selectedCount.textContent = '(' + cityCount + ')';
        var destinationCount = getSelectedCountryCodes().length + cityCount;
        selectedGlobalCount.textContent = destinationCount + ' destination(s) sélectionnée(s)';
    }

    function renderAllSelections() {
        renderSelectedCountries();
        renderSelectedCities();
        renderHiddenInputs();
    }

    function renderCityResults(items, meta) {
        cityResults.innerHTML = '';

        if (!Array.isArray(items) || !items.length) {
            cityResults.innerHTML = '<div class="list-group-item text-muted">Aucune ville trouvée.</div>';
        } else {
            items.forEach(function(item) {
                var countryCode = item.country_code || '';
                var countryName = item.country_name || item.country || countryCode;
                var isIncludedByCountry = !!state.includeCountry[countryCode];
                var alreadySelected = item.id && !!state.selectedCities[item.id];
                var disabled = isIncludedByCountry ? 'disabled' : '';
                var label = isIncludedByCountry ? 'Pays inclus' : (alreadySelected ? 'Ajoutée' : 'Ajouter');

                var row = document.createElement('div');
                row.className = 'list-group-item d-flex align-items-center justify-content-between gap-2';
                row.innerHTML =
                    '<div>' +
                        '<div class="fw-medium">' + escapeHtml(item.name) + '</div>' +
                        '<div class="small text-muted">🌍 ' + escapeHtml(countryName) + '</div>' +
                    '</div>' +
                    '<button type="button" class="btn btn-sm btn-outline-primary add-city-btn" ' + disabled +
                        ' data-city-id="' + escapeHtml(item.id || '') + '" data-city-name="' + escapeHtml(item.name || '') + '"' +
                        ' data-country-code="' + escapeHtml(countryCode) + '" data-country-id="' + escapeHtml(item.country_id || '') + '" data-country-name="' + escapeHtml(countryName) + '">' + label + '</button>';
                cityResults.appendChild(row);
            });
        }

        var total = meta && meta.total ? meta.total : 0;
        var page = meta && meta.page ? meta.page : 1;
        var totalPages = meta && (meta.total_pages || meta.last_page) ? (meta.total_pages || meta.last_page) : 1;

        cityMeta.textContent = total + ' résultat(s) • Page ' + page + '/' + totalPages;
        prevBtn.disabled = page <= 1;
        nextBtn.disabled = page >= totalPages;
        state.page = page;
        state.lastPage = totalPages;
    }

    function fetchCities() {
        var countryIds = getSelectedCountryIds();
        if (!countryIds.length) {
            renderCityResults([], { total: 0, page: 1, total_pages: 1 });
            cityMeta.textContent = 'Sélectionnez un ou plusieurs pays.';
            return;
        }

        var params = new URLSearchParams();
        countryIds.forEach(function(id) { params.append('country_ids[]', id); });
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
                renderCityResults([], { total: 0, page: 1, total_pages: 1 });
                cityMeta.textContent = 'Erreur de chargement des villes.';
            });
    }

    function triggerCountryChange() {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(countrySelect).trigger('change');
        } else {
            refreshCountrySelectionFromSelect();
            state.page = 1;
            ensureSelectedCountriesExist().then(function() {
                fetchCities();
            });
        }
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
                ensureSelectedCountriesExist().then(function() {
                    fetchCities();
                });
            });
            return;
        }

        countrySelect.addEventListener('change', function() {
            refreshCountrySelectionFromSelect();
            state.page = 1;
            ensureSelectedCountriesExist().then(function() {
                fetchCities();
            });
        });
    }

    selectedCountriesWrap.addEventListener('change', function(e) {
        var toggle = e.target.closest('.include-country-toggle');
        if (!toggle) return;

        var countryCode = toggle.getAttribute('data-country-code');
        var countryId = parseInt(toggle.getAttribute('data-country-id') || '0', 10) || null;
        if (!countryCode) return;

        if (toggle.checked) {
            state.includeCountry[countryCode] = true;
            if (countryId) {
                state.countryCodeToId[countryCode] = countryId;
            }
            Object.keys(state.selectedCities).forEach(function(cityId) {
                var city = state.selectedCities[cityId];
                if (city && city.country_code === countryCode) {
                    delete state.selectedCities[cityId];
                }
            });

            if (!getCountryId(countryCode)) {
                ensureLocation(countryCode, null).then(function(res) {
                    if (res && res.id) {
                        state.countryCodeToId[countryCode] = parseInt(res.id, 10);
                        if (!countryCitiesData[countryCode]) countryCitiesData[countryCode] = { title: worldCountries[countryCode] || countryCode, cities: [] };
                        countryCitiesData[countryCode].id = parseInt(res.id, 10);
                        Array.from(countrySelect.options).forEach(function(opt) {
                            if (opt.value === countryCode) opt.setAttribute('data-country-id', String(res.id));
                        });
                        renderAllSelections();
                    }
                });
            }
        } else {
            delete state.includeCountry[countryCode];
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
            if (opt.value === code) {
                opt.selected = false;
            }
        });
        delete state.includeCountry[code];
        triggerCountryChange();
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
        var countryId = parseInt(addBtn.getAttribute('data-country-id') || '0', 10) || null;

        if (!countryCode || state.includeCountry[countryCode]) return;
        if (countryId) {
            state.countryCodeToId[countryCode] = countryId;
        }

        var numericCityId = cityIdRaw ? parseInt(cityIdRaw, 10) : null;
        if (numericCityId) {
            state.selectedCities[numericCityId] = {
                id: numericCityId,
                name: cityName,
                country_code: countryCode,
                country_name: countryName,
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
                country_name: countryName,
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

    if (countryFilterInput) {
        countryFilterInput.addEventListener('keyup', applyCountryFilter);
    }

    if (countryFilterResetBtn) {
        countryFilterResetBtn.addEventListener('click', function() {
            if (countryFilterInput) {
                countryFilterInput.value = '';
            }
            applyCountryFilter();
        });
    }

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
        triggerCountryChange();
    });

    deselectAllBtn.addEventListener('click', function() {
        Array.from(countrySelect.options).forEach(function(opt) { opt.selected = false; });
        state.selectedCities = {};
        state.includeCountry = {};
        triggerCountryChange();
    });

    clearCitiesBtn.addEventListener('click', function() {
        state.selectedCities = {};
        renderAllSelections();
        fetchCities();
    });

    preloadSelectedCities();
    enhanceCountrySelect();
    refreshCountrySelectionFromSelect();
    applyCountryFilter();
    ensureSelectedCountriesExist().then(function() {
        renderAllSelections();
        fetchCities();
    });
})();
</script>
