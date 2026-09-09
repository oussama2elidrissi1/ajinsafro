{{-- Etape 4 : hebergement detaille, un bloc par ville. --}}
@php
    $existingHotels = $package->hotels->keyBy('city');

    // Makkah et Madinah sont toujours proposes ; les autres etapes sont ajoutables.
    $defaultCities = [
        \App\Models\HajjOmraPackageHotel::CITY_MAKKAH,
        \App\Models\HajjOmraPackageHotel::CITY_MADINAH,
    ];

    $hotelRows = old('hotels');

    if ($hotelRows === null) {
        $hotelRows = [];
        foreach ($defaultCities as $city) {
            $hotel = $existingHotels->get($city);
            $hotelRows[] = [
                'id' => $hotel?->id,
                'city' => $city,
                'name' => $hotel?->name,
                'stars' => $hotel?->stars,
                'haram_distance' => $hotel?->haram_distance,
                'location' => $hotel?->location,
                'nights' => $hotel?->nights,
                'meal_plan' => $hotel?->meal_plan,
                'description' => $hotel?->description,
                'description_ar' => $hotel?->description_ar,
                'image_path' => $hotel?->image_path,
            ];
        }

        foreach ($package->hotels as $hotel) {
            if (! in_array($hotel->city, $defaultCities, true)) {
                $hotelRows[] = [
                    'id' => $hotel->id,
                    'city' => $hotel->city,
                    'name' => $hotel->name,
                    'stars' => $hotel->stars,
                    'haram_distance' => $hotel->haram_distance,
                    'location' => $hotel->location,
                    'nights' => $hotel->nights,
                    'meal_plan' => $hotel->meal_plan,
                    'description' => $hotel->description,
                    'description_ar' => $hotel->description_ar,
                    'image_path' => $hotel->image_path,
                ];
            }
        }
    }
@endphp

<div class="ho-panel" data-panel="hebergement">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h6 class="text-uppercase text-muted small mb-1">Hébergement</h6>
            <p class="text-muted small mb-0">Un bloc par ville. Un bloc laissé entièrement vide n'est pas enregistré.</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" data-repeat-add="hotel">+ Ajouter une étape</button>
    </div>

    <div data-repeat-list="hotel">
        @foreach ($hotelRows as $i => $row)
            <div class="ho-row" data-repeat-item>
                <input type="hidden" name="hotels[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Ville</label>
                        <select name="hotels[{{ $i }}][city]" class="form-select form-select-sm">
                            @foreach ($hotelCityOptions as $key => $label)
                                <option value="{{ $key }}" @selected(($row['city'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Nom de l'hôtel</label>
                        <input type="text" name="hotels[{{ $i }}][name]" class="form-control form-control-sm" value="{{ $row['name'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Catégorie</label>
                        <select name="hotels[{{ $i }}][stars]" class="form-select form-select-sm">
                            <option value="">—</option>
                            @for ($s = 5; $s >= 1; $s--)
                                <option value="{{ $s }}" @selected((int) ($row['stars'] ?? 0) === $s)>{{ $s }} étoiles</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <label class="form-label small text-muted d-block">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>Retirer</button>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small text-muted">Distance du Haram</label>
                        <input type="text" name="hotels[{{ $i }}][haram_distance]" class="form-control form-control-sm" value="{{ $row['haram_distance'] ?? '' }}" placeholder="300 m">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Localisation</label>
                        <input type="text" name="hotels[{{ $i }}][location]" class="form-control form-control-sm" value="{{ $row['location'] ?? '' }}" placeholder="Quartier Ajyad">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Nombre de nuits</label>
                        <input type="number" min="0" max="365" name="hotels[{{ $i }}][nights]" class="form-control form-control-sm" value="{{ $row['nights'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Pension</label>
                        <select name="hotels[{{ $i }}][meal_plan]" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach ($mealPlanOptions as $key => $label)
                                <option value="{{ $key }}" @selected(($row['meal_plan'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8">
                        <div data-lang-pane="fr">
                            <label class="form-label small text-muted">Description <span class="ho-lang-badge">FR</span></label>
                            <textarea name="hotels[{{ $i }}][description]" rows="3" class="form-control form-control-sm">{{ $row['description'] ?? '' }}</textarea>
                        </div>
                        <div data-lang-pane="ar">
                            <label class="form-label small text-muted">الوصف <span class="ho-lang-badge">AR</span></label>
                            <textarea name="hotels[{{ $i }}][description_ar]" rows="3" dir="rtl" lang="ar" class="form-control form-control-sm">{{ $row['description_ar'] ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Photo de l'hôtel</label>
                        @include('admin.hajj-omra.form._image-picker', [
                            'inputName' => "hotels[{$i}][image_path]",
                            'value' => $row['image_path'] ?? null,
                            'context' => 'hajj-omra-hotel',
                        ])
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <template data-repeat-template="hotel">
        <div class="ho-row" data-repeat-item>
            <input type="hidden" name="hotels[__INDEX__][id]" value="">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Ville</label>
                    <select name="hotels[__INDEX__][city]" class="form-select form-select-sm">
                        @foreach ($hotelCityOptions as $key => $label)
                            <option value="{{ $key }}" @selected($key === 'other')>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small text-muted">Nom de l'hôtel</label>
                    <input type="text" name="hotels[__INDEX__][name]" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Catégorie</label>
                    <select name="hotels[__INDEX__][stars]" class="form-select form-select-sm">
                        <option value="">—</option>
                        @for ($s = 5; $s >= 1; $s--)<option value="{{ $s }}">{{ $s }} étoiles</option>@endfor
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <label class="form-label small text-muted d-block">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-repeat-remove>Retirer</button>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Distance du Haram</label>
                    <input type="text" name="hotels[__INDEX__][haram_distance]" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Localisation</label>
                    <input type="text" name="hotels[__INDEX__][location]" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Nombre de nuits</label>
                    <input type="number" min="0" max="365" name="hotels[__INDEX__][nights]" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Pension</label>
                    <select name="hotels[__INDEX__][meal_plan]" class="form-select form-select-sm">
                        <option value="">—</option>
                        @foreach ($mealPlanOptions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <div data-lang-pane="fr">
                        <label class="form-label small text-muted">Description <span class="ho-lang-badge">FR</span></label>
                        <textarea name="hotels[__INDEX__][description]" rows="3" class="form-control form-control-sm"></textarea>
                    </div>
                    <div data-lang-pane="ar">
                        <label class="form-label small text-muted">الوصف <span class="ho-lang-badge">AR</span></label>
                        <textarea name="hotels[__INDEX__][description_ar]" rows="3" dir="rtl" lang="ar" class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Photo de l'hôtel</label>
                    @include('admin.hajj-omra.form._image-picker', [
                        'inputName' => 'hotels[__INDEX__][image_path]',
                        'value' => null,
                        'context' => 'hajj-omra-hotel',
                    ])
                </div>
            </div>
        </div>
    </template>
</div>
