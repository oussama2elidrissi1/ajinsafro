{{-- Etape 1 : l'essentiel commercial de l'offre. --}}
<div class="ho-panel" data-panel="offre">

    <h6 class="text-uppercase text-muted small mb-3">Informations principales</h6>

    @include('admin.hajj-omra.form._field', [
        'name' => 'title_fr',
        'nameAr' => 'title_ar',
        'label' => 'Titre de l\'offre',
        'labelAr' => 'عنوان العرض',
        'value' => $package->title,
        'valueAr' => $package->title_ar,
        'required' => false,
        'maxlength' => 255,
        'hint' => 'Exemple : Omra Ramadan 1448 — 14 jours',
    ])

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="type">Type d'offre <span class="text-danger">*</span></label>
            <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                @foreach ($typeOptions as $key => $label)
                    <option value="{{ $key }}" @selected(old('type', $package->type) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label" for="status">Statut <span class="text-danger">*</span></label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach ($statusOptions as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $package->status) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label" for="departure_city">Ville de départ</label>
            <input type="text" id="departure_city" name="departure_city" class="form-control"
                   value="{{ old('departure_city', $package->departure_city) }}" placeholder="Casablanca">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="destination">Destination</label>
            <input type="text" id="destination" name="destination" class="form-control"
                   value="{{ old('destination', $package->destination) }}" placeholder="Makkah / Madinah">
        </div>

        <div class="col-md-3">
            <label class="form-label" for="duration_days">Nombre de jours <span class="text-danger">*</span></label>
            <input type="number" min="1" max="365" id="duration_days" name="duration_days"
                   class="form-control @error('duration_days') is-invalid @enderror"
                   value="{{ old('duration_days', $package->duration_days) }}" required
                   data-role="duration-days">
            <div class="form-text">Pilote la génération automatique du programme.</div>
            @error('duration_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label" for="duration_nights">Nombre de nuits</label>
            <input type="number" min="0" max="365" id="duration_nights" name="duration_nights" class="form-control"
                   value="{{ old('duration_nights', $package->duration_nights) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="start_date">Date principale de départ</label>
            <input type="date" id="start_date" name="start_date" class="form-control"
                   value="{{ old('start_date', optional($package->start_date)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="return_date">Date principale de retour</label>
            <input type="date" id="return_date" name="return_date"
                   class="form-control @error('return_date') is-invalid @enderror"
                   value="{{ old('return_date', optional($package->return_date)->format('Y-m-d')) }}">
            @error('return_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <hr class="my-4">
    <h6 class="text-uppercase text-muted small mb-3">Prix principal</h6>

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="adult_price">Prix à partir de</label>
            <div class="input-group">
                <input type="number" step="0.01" min="0" id="adult_price" name="adult_price" class="form-control"
                       value="{{ old('adult_price', $package->adult_price) }}" data-role="price-current">
                <span class="input-group-text" data-role="currency-label">{{ old('currency', $package->currency ?: 'DH') }}</span>
            </div>
            <p class="form-text" data-from-price-note hidden data-ho-fr="Calculé depuis les tarifs actifs liés aux formules, ou les tarifs actifs de l’offre sans formule." data-ho-ar="يُحسب من الأسعار المفعّلة المرتبطة بالباقات، أو من أسعار العرض المفعّلة عند عدم وجود باقات.">Calculé depuis les tarifs actifs liés aux formules, ou les tarifs actifs de l’offre sans formule.</p>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="old_price">Ancien prix / valeur</label>
            <input type="number" step="0.01" min="0" id="old_price" name="old_price" class="form-control"
                   value="{{ old('old_price', $package->old_price) }}" data-role="price-old">
            <div class="form-text">Affiché barré sur le site public.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="discount_amount">Économie / remise</label>
            <input type="number" step="0.01" min="0" id="discount_amount" name="discount_amount" class="form-control"
                   value="{{ old('discount_amount', $package->discount_amount) }}" data-role="price-discount">
            <div class="form-text" data-role="discount-hint">Calculée automatiquement si laissée vide.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="currency">Devise <span class="text-danger">*</span></label>
            <select id="currency" name="currency" class="form-select" required data-role="currency">
                @foreach (['DH' => 'DH', 'MAD' => 'MAD', 'EUR' => 'EUR', 'USD' => 'USD'] as $key => $label)
                    <option value="{{ $key }}" @selected(old('currency', $package->currency ?: 'DH') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="child_price">Prix enfant</label>
            <input type="number" step="0.01" min="0" id="child_price" name="child_price" class="form-control"
                   value="{{ old('child_price', $package->child_price) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="baby_price">Prix bébé</label>
            <input type="number" step="0.01" min="0" id="baby_price" name="baby_price" class="form-control"
                   value="{{ old('baby_price', $package->baby_price) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="available_places">Places disponibles</label>
            <input type="number" min="0" id="available_places" name="available_places"
                   class="form-control @error('available_places') is-invalid @enderror"
                   value="{{ old('available_places', $package->available_places) }}">
            @error('available_places')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label" for="reserved_places">Places réservées</label>
            <input type="number" min="0" id="reserved_places" name="reserved_places"
                   class="form-control @error('reserved_places') is-invalid @enderror"
                   value="{{ old('reserved_places', $package->reserved_places) }}">
            @error('reserved_places')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <div class="form-check">
                <input type="hidden" name="is_featured" value="0">
                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                       @checked(old('is_featured', $package->is_featured))>
                <label class="form-check-label" for="is_featured">Mettre cette offre en avant</label>
            </div>
        </div>
    </div>

    <hr class="my-4">
    <h6 class="text-uppercase text-muted small mb-3">Description</h6>

    @include('admin.hajj-omra.form._field', [
        'name' => 'short_description_fr',
        'nameAr' => 'short_description_ar',
        'label' => 'Description courte',
        'labelAr' => 'وصف موجز',
        'value' => $package->short_description,
        'valueAr' => $package->short_description_ar,
        'type' => 'textarea',
        'rows' => 3,
        'maxlength' => 1200,
        'hint' => 'Reprise dans les listes et les cartes du site public.',
    ])

    @include('admin.hajj-omra.form._field', [
        'name' => 'description_fr',
        'nameAr' => 'description_ar',
        'label' => 'Description détaillée',
        'labelAr' => 'الوصف التفصيلي',
        'value' => $package->description,
        'valueAr' => $package->description_ar,
        'type' => 'editor',
        'rows' => 8,
    ])
</div>
