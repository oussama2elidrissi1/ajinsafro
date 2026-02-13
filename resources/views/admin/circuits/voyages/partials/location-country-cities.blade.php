{{-- Pays (select) + Villes par pays (checkboxes) — compatible locations[] / multi_location --}}
@php
    $selectedIds = $selectedLocationIds ?? [];
    $countries = $locationsTree ?? [];
@endphp

<div class="destination-country-cities">
    <div class="mb-3">
        <label for="locationCountrySelect" class="form-label fw-medium">Pays</label>
        <select id="locationCountrySelect" class="form-select destination-country-select">
            <option value="">— Choisir un pays —</option>
            @foreach($countries as $country)
                <option value="{{ $country['id'] }}">{{ $country['title'] }}</option>
            @endforeach
        </select>
    </div>

    @foreach($countries as $country)
        @php
            $children = $country['children'] ?? [];
            $countrySelected = in_array($country['id'], $selectedIds);
        @endphp
        <div class="destination-cities-panel" id="cities-panel-{{ $country['id'] }}" data-country-id="{{ $country['id'] }}" style="display: none;">
            <div class="destination-cities-panel-header">
                <span class="destination-cities-panel-title">Villes — {{ $country['title'] }}</span>
            </div>
            <div class="destination-cities-list">
                <label class="destination-country-checkbox-label">
                    <input type="checkbox" name="locations[]" value="{{ $country['id'] }}" class="location-checkbox destination-checkbox" {{ $countrySelected ? 'checked' : '' }} data-loc-id="{{ $country['id'] }}" data-loc-title="{{ e($country['title']) }}">
                    <span>Inclure le pays entier ({{ $country['title'] }})</span>
                </label>
                @foreach($children as $city)
                    @php $citySelected = in_array($city['id'], $selectedIds); @endphp
                    <label class="destination-city-checkbox-label">
                        <input type="checkbox" name="locations[]" value="{{ $city['id'] }}" class="location-checkbox destination-checkbox" {{ $citySelected ? 'checked' : '' }} data-loc-id="{{ $city['id'] }}" data-loc-title="{{ e($city['title']) }}">
                        <span>{{ $city['title'] }}</span>
                    </label>
                @endforeach
                @if(empty($children))
                    <p class="text-muted small mb-0 mt-1">Aucune ville enregistrée pour ce pays.</p>
                @endif
            </div>
        </div>
    @endforeach
</div>
