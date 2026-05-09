@php
    $priceRows = old('prices', $offer->prices->map(fn($item) => [
        'label' => $item->label,
        'type' => $item->type,
        'price' => $item->price,
        'old_price' => $item->old_price,
        'stock' => $item->stock,
        'condition' => $item->condition,
    ])->all());
    $departureRows = old('departures', $offer->departures->map(fn($item) => [
        'departure_date' => optional($item->departure_date)->format('Y-m-d'),
        'return_date' => optional($item->return_date)->format('Y-m-d'),
        'price_from' => $item->price_from,
        'total_places' => $item->total_places,
        'available_places' => $item->available_places,
        'reserved_places' => $item->reserved_places,
        'status' => $item->status,
        'internal_notes' => $item->internal_notes,
    ])->all());
    if ($priceRows === []) {
        $priceRows = [['label' => 'Adulte', 'type' => 'personne', 'price' => '', 'old_price' => '', 'stock' => '', 'condition' => '']];
    }
    if ($departureRows === []) {
        $departureRows = [['departure_date' => '', 'return_date' => '', 'price_from' => '', 'total_places' => '', 'available_places' => '', 'reserved_places' => '', 'status' => 'published', 'internal_notes' => '']];
    }
@endphp

<div class="row g-4">
    <div class="col-12">
        <x-admin.form-section title="1. Informations générales" subtitle="Structure principale de l’offre économique.">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Titre <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $offer->title) }}" class="form-control @error('title') is-invalid @enderror" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $offer->slug) }}" class="form-control @error('slug') is-invalid @enderror">
                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Référence interne</label>
                    <input type="text" name="internal_reference" value="{{ old('internal_reference', $offer->internal_reference) }}" class="form-control @error('internal_reference') is-invalid @enderror">
                    @error('internal_reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Type d’offre <span class="text-danger">*</span></label>
                    <select name="offer_type" class="form-select @error('offer_type') is-invalid @enderror" required>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('offer_type', $offer->offer_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('offer_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                    <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                        @foreach($categoryOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $offer->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $offer->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ordre</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $offer->sort_order ?? 0) }}" class="form-control @error('sort_order') is-invalid @enderror">
                    @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mise en avant</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $offer->is_featured))>
                        <label class="form-check-label">Activer</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Description courte</label>
                    <textarea name="short_description" rows="3" class="form-control @error('short_description') is-invalid @enderror">{{ old('short_description', $offer->short_description) }}</textarea>
                    @error('short_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Description détaillée</label>
                    <textarea name="description" rows="6" class="form-control @error('description') is-invalid @enderror">{{ old('description', $offer->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </x-admin.form-section>
    </div>

    <div class="col-12">
        <x-admin.form-section title="2. Prix & services" subtitle="Tarification principale, options et services inclus.">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Prix à partir de</label>
                    <input type="number" step="0.01" name="price_from" value="{{ old('price_from', $offer->price_from) }}" class="form-control @error('price_from') is-invalid @enderror">
                    @error('price_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ancien prix</label>
                    <input type="number" step="0.01" name="old_price" value="{{ old('old_price', $offer->old_price) }}" class="form-control @error('old_price') is-invalid @enderror">
                    @error('old_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Devise</label>
                    <input type="text" name="currency" value="{{ old('currency', $offer->currency ?: 'DH') }}" class="form-control @error('currency') is-invalid @enderror">
                    @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type de prix</label>
                    <select name="price_type" class="form-select @error('price_type') is-invalid @enderror">
                        <option value="">Choisir</option>
                        @foreach($priceTypeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('price_type', $offer->price_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('price_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Acompte</label>
                    <input type="number" step="0.01" name="deposit_amount" value="{{ old('deposit_amount', $offer->deposit_amount) }}" class="form-control @error('deposit_amount') is-invalid @enderror">
                    @error('deposit_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Total places</label>
                    <input type="number" min="0" name="total_places" value="{{ old('total_places', $offer->total_places) }}" class="form-control @error('total_places') is-invalid @enderror">
                    @error('total_places') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Places disponibles</label>
                    <input type="number" min="0" name="available_places" value="{{ old('available_places', $offer->available_places) }}" class="form-control @error('available_places') is-invalid @enderror">
                    @error('available_places') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Places réservées</label>
                    <input type="number" min="0" name="reserved_places" value="{{ old('reserved_places', $offer->reserved_places) }}" class="form-control @error('reserved_places') is-invalid @enderror">
                    @error('reserved_places') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3"><div class="form-check mt-4 pt-2"><input class="form-check-input" type="checkbox" name="transport_included" value="1" id="transport_included" @checked(old('transport_included', $offer->transport_included))><label class="form-check-label" for="transport_included">Transport inclus</label></div></div>
                <div class="col-md-3"><div class="form-check mt-4 pt-2"><input class="form-check-input" type="checkbox" name="flight_included" value="1" id="flight_included" @checked(old('flight_included', $offer->flight_included))><label class="form-check-label" for="flight_included">Vol inclus</label></div></div>
                <div class="col-md-3"><div class="form-check mt-4 pt-2"><input class="form-check-input" type="checkbox" name="hotel_included" value="1" id="hotel_included" @checked(old('hotel_included', $offer->hotel_included))><label class="form-check-label" for="hotel_included">Hôtel inclus</label></div></div>
                <div class="col-md-3"><div class="form-check mt-4 pt-2"><input class="form-check-input" type="checkbox" name="meals_included" value="1" id="meals_included" @checked(old('meals_included', $offer->meals_included))><label class="form-check-label" for="meals_included">Repas inclus</label></div></div>
                <div class="col-md-3"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="guide_included" value="1" id="guide_included" @checked(old('guide_included', $offer->guide_included))><label class="form-check-label" for="guide_included">Guide inclus</label></div></div>
                <div class="col-md-3"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="insurance_included" value="1" id="insurance_included" @checked(old('insurance_included', $offer->insurance_included))><label class="form-check-label" for="insurance_included">Assurance incluse</label></div></div>
                <div class="col-md-3"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="transfer_included" value="1" id="transfer_included" @checked(old('transfer_included', $offer->transfer_included))><label class="form-check-label" for="transfer_included">Transfert inclus</label></div></div>
                <div class="col-md-3">
                    <label class="form-label">Repas</label>
                    <select name="meal_plan" class="form-select @error('meal_plan') is-invalid @enderror">
                        <option value="">Non précisé</option>
                        @foreach($mealPlanOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('meal_plan', $offer->meal_plan) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('meal_plan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Type d’hébergement</label>
                    <input type="text" name="accommodation_type" value="{{ old('accommodation_type', $offer->accommodation_type) }}" class="form-control @error('accommodation_type') is-invalid @enderror">
                    @error('accommodation_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nom hôtel</label>
                    <input type="text" name="hotel_name" value="{{ old('hotel_name', $offer->hotel_name) }}" class="form-control @error('hotel_name') is-invalid @enderror">
                    @error('hotel_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Catégorie hôtel</label>
                    <input type="text" name="hotel_category" value="{{ old('hotel_category', $offer->hotel_category) }}" class="form-control @error('hotel_category') is-invalid @enderror">
                    @error('hotel_category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Type de chambre</label>
                    <input type="text" name="room_type" value="{{ old('room_type', $offer->room_type) }}" class="form-control @error('room_type') is-invalid @enderror">
                    @error('room_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Résumé programme</label>
                    <input type="text" name="program_summary" value="{{ old('program_summary', $offer->program_summary) }}" class="form-control @error('program_summary') is-invalid @enderror">
                    @error('program_summary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Inclus dans le prix</label>
                    <textarea name="included_items_text" rows="5" class="form-control @error('included_items_text') is-invalid @enderror">{{ old('included_items_text', collect($offer->included_items ?? [])->implode("\n")) }}</textarea>
                    @error('included_items_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Non inclus</label>
                    <textarea name="excluded_items_text" rows="5" class="form-control @error('excluded_items_text') is-invalid @enderror">{{ old('excluded_items_text', collect($offer->excluded_items ?? [])->implode("\n")) }}</textarea>
                    @error('excluded_items_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Conditions de paiement</label>
                    <textarea name="payment_conditions" rows="4" class="form-control @error('payment_conditions') is-invalid @enderror">{{ old('payment_conditions', $offer->payment_conditions) }}</textarea>
                    @error('payment_conditions') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Conditions d’annulation</label>
                    <textarea name="cancellation_conditions" rows="4" class="form-control @error('cancellation_conditions') is-invalid @enderror">{{ old('cancellation_conditions', $offer->cancellation_conditions) }}</textarea>
                    @error('cancellation_conditions') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Prix variables</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="prices">Ajouter une ligne</button>
            </div>
            <div data-repeater="prices">
                @foreach($priceRows as $index => $row)
                    <div class="border rounded-3 p-3 mb-3" data-row>
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Libellé</label><input type="text" name="prices[{{ $index }}][label]" value="{{ $row['label'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Type</label><input type="text" name="prices[{{ $index }}][type]" value="{{ $row['type'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Prix</label><input type="number" step="0.01" name="prices[{{ $index }}][price]" value="{{ $row['price'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Ancien prix</label><input type="number" step="0.01" name="prices[{{ $index }}][old_price]" value="{{ $row['old_price'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Stock</label><input type="number" min="0" name="prices[{{ $index }}][stock]" value="{{ $row['stock'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>×</button></div>
                            <div class="col-12"><label class="form-label">Condition</label><input type="text" name="prices[{{ $index }}][condition]" value="{{ $row['condition'] ?? '' }}" class="form-control"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.form-section>
    </div>

    <div class="col-12">
        <x-admin.form-section title="3. Départs" subtitle="Une offre économique peut avoir plusieurs dates et prix.">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Date départ</label><input type="date" name="departure_date" value="{{ old('departure_date', optional($offer->departure_date)->format('Y-m-d')) }}" class="form-control @error('departure_date') is-invalid @enderror">@error('departure_date') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-3"><label class="form-label">Date retour</label><input type="date" name="return_date" value="{{ old('return_date', optional($offer->return_date)->format('Y-m-d')) }}" class="form-control @error('return_date') is-invalid @enderror">@error('return_date') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-2"><label class="form-label">Jours</label><input type="number" min="0" name="duration_days" value="{{ old('duration_days', $offer->duration_days) }}" class="form-control @error('duration_days') is-invalid @enderror">@error('duration_days') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-2"><label class="form-label">Nuits</label><input type="number" min="0" name="duration_nights" value="{{ old('duration_nights', $offer->duration_nights) }}" class="form-control @error('duration_nights') is-invalid @enderror">@error('duration_nights') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-2">
                    <label class="form-label">Disponibilité</label>
                    <select class="form-select" disabled>
                        <option>{{ $availabilityOptions[$offer->availability_status] ?? 'Calcul automatique' }}</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label">Ville de départ</label><input type="text" name="departure_city" value="{{ old('departure_city', $offer->departure_city) }}" class="form-control @error('departure_city') is-invalid @enderror">@error('departure_city') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-3"><label class="form-label">Destination</label><input type="text" name="destination" value="{{ old('destination', $offer->destination) }}" class="form-control @error('destination') is-invalid @enderror">@error('destination') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-2"><label class="form-label">Pays</label><input type="text" name="country" value="{{ old('country', $offer->country) }}" class="form-control @error('country') is-invalid @enderror">@error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-2"><label class="form-label">Ville d’arrivée</label><input type="text" name="arrival_city" value="{{ old('arrival_city', $offer->arrival_city) }}" class="form-control @error('arrival_city') is-invalid @enderror">@error('arrival_city') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-2"><label class="form-label">Distance clé</label><input type="text" name="key_distance" value="{{ old('key_distance', $offer->key_distance) }}" class="form-control @error('key_distance') is-invalid @enderror">@error('key_distance') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-12"><label class="form-label">Adresse / zone</label><input type="text" name="address_zone" value="{{ old('address_zone', $offer->address_zone) }}" class="form-control @error('address_zone') is-invalid @enderror">@error('address_zone') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Départs multiples</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="departures">Ajouter un départ</button>
            </div>
            <div data-repeater="departures">
                @foreach($departureRows as $index => $row)
                    <div class="border rounded-3 p-3 mb-3" data-row>
                        <div class="row g-3">
                            <div class="col-md-2"><label class="form-label">Départ</label><input type="date" name="departures[{{ $index }}][departure_date]" value="{{ $row['departure_date'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Retour</label><input type="date" name="departures[{{ $index }}][return_date]" value="{{ $row['return_date'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Prix</label><input type="number" step="0.01" name="departures[{{ $index }}][price_from]" value="{{ $row['price_from'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Places totales</label><input type="number" min="0" name="departures[{{ $index }}][total_places]" value="{{ $row['total_places'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-2"><label class="form-label">Places dispo</label><input type="number" min="0" name="departures[{{ $index }}][available_places]" value="{{ $row['available_places'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-1"><label class="form-label">Réservées</label><input type="number" min="0" name="departures[{{ $index }}][reserved_places]" value="{{ $row['reserved_places'] ?? '' }}" class="form-control"></div>
                            <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>×</button></div>
                            <div class="col-md-3">
                                <label class="form-label">Statut</label>
                                <select name="departures[{{ $index }}][status]" class="form-select">
                                    @foreach($departureStatusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($row['status'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-9"><label class="form-label">Note interne</label><input type="text" name="departures[{{ $index }}][internal_notes]" value="{{ $row['internal_notes'] ?? '' }}" class="form-control"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.form-section>
    </div>

    <div class="col-12">
        <x-admin.form-section title="4. Médias" subtitle="Images principales, galerie et médias SEO.">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Image principale</label>
                    <input type="file" name="main_image_file" class="form-control @error('main_image_file') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                    @error('main_image_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if($offer->main_image_url)
                        <div class="mt-2"><x-admin.image-thumb :src="$offer->main_image_url" :alt="$offer->title" size="sm" /></div>
                        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_main_image" value="1" id="remove_main_image"><label class="form-check-label" for="remove_main_image">Supprimer</label></div>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label">Image fallback</label>
                    <input type="file" name="fallback_image_file" class="form-control @error('fallback_image_file') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                    @error('fallback_image_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if($offer->fallback_image_url)
                        <div class="mt-2"><x-admin.image-thumb :src="$offer->fallback_image_url" :alt="$offer->title" size="sm" /></div>
                        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_fallback_image" value="1" id="remove_fallback_image"><label class="form-check-label" for="remove_fallback_image">Supprimer</label></div>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vidéo</label>
                    <input type="url" name="video_url" value="{{ old('video_url', $offer->video_url) }}" class="form-control @error('video_url') is-invalid @enderror" placeholder="https://">
                    @error('video_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Galerie images</label>
                    <input type="file" name="gallery_images[]" class="form-control @error('gallery_images') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp" multiple>
                    @error('gallery_images') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    @if($offer->images->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            @foreach($offer->images as $image)
                                <x-admin.image-thumb :src="$image->image_url" :alt="$offer->title" size="sm" />
                            @endforeach
                        </div>
                        <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="replace_gallery" value="1" id="replace_gallery"><label class="form-check-label" for="replace_gallery">Remplacer toute la galerie</label></div>
                    @endif
                </div>
            </div>
        </x-admin.form-section>
    </div>

    <div class="col-12">
        <x-admin.form-section title="5. SEO" subtitle="Balises SEO, documents et contenu public complémentaire.">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Meta title</label><input type="text" name="meta_title" value="{{ old('meta_title', $offer->meta_title) }}" class="form-control @error('meta_title') is-invalid @enderror">@error('meta_title') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-6"><label class="form-label">Image SEO</label><input type="file" name="seo_image_file" class="form-control @error('seo_image_file') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">@error('seo_image_file') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-6"><label class="form-label">Meta description</label><textarea name="meta_description" rows="4" class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $offer->meta_description) }}</textarea>@error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-6"><label class="form-label">Keywords</label><textarea name="seo_keywords_text" rows="4" class="form-control @error('seo_keywords_text') is-invalid @enderror">{{ old('seo_keywords_text', collect($offer->seo_keywords ?? [])->implode("\n")) }}</textarea>@error('seo_keywords_text') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                <div class="col-md-6"><label class="form-label">Documents nécessaires</label><textarea name="required_documents" rows="5" class="form-control @error('required_documents') is-invalid @enderror">{{ old('required_documents', $offer->required_documents) }}</textarea>@error('required_documents') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
            </div>
        </x-admin.form-section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const createPriceRow = function (index) {
        return `
            <div class="border rounded-3 p-3 mb-3" data-row>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Libellé</label><input type="text" name="prices[${index}][label]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Type</label><input type="text" name="prices[${index}][type]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Prix</label><input type="number" step="0.01" name="prices[${index}][price]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Ancien prix</label><input type="number" step="0.01" name="prices[${index}][old_price]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Stock</label><input type="number" min="0" name="prices[${index}][stock]" class="form-control"></div>
                    <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>×</button></div>
                    <div class="col-12"><label class="form-label">Condition</label><input type="text" name="prices[${index}][condition]" class="form-control"></div>
                </div>
            </div>
        `;
    };

    const createDepartureRow = function (index) {
        return `
            <div class="border rounded-3 p-3 mb-3" data-row>
                <div class="row g-3">
                    <div class="col-md-2"><label class="form-label">Départ</label><input type="date" name="departures[${index}][departure_date]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Retour</label><input type="date" name="departures[${index}][return_date]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Prix</label><input type="number" step="0.01" name="departures[${index}][price_from]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Places totales</label><input type="number" min="0" name="departures[${index}][total_places]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Places dispo</label><input type="number" min="0" name="departures[${index}][available_places]" class="form-control"></div>
                    <div class="col-md-1"><label class="form-label">Réservées</label><input type="number" min="0" name="departures[${index}][reserved_places]" class="form-control"></div>
                    <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>×</button></div>
                    <div class="col-md-3">
                        <label class="form-label">Statut</label>
                        <select name="departures[${index}][status]" class="form-select">
                            @foreach($departureStatusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-9"><label class="form-label">Note interne</label><input type="text" name="departures[${index}][internal_notes]" class="form-control"></div>
                </div>
            </div>
        `;
    };

    document.querySelectorAll('[data-add-row]').forEach(function (button) {
        button.addEventListener('click', function () {
            const key = button.getAttribute('data-add-row');
            const container = document.querySelector(`[data-repeater="${key}"]`);
            if (!container) {
                return;
            }
            const index = container.querySelectorAll('[data-row]').length;
            container.insertAdjacentHTML('beforeend', key === 'prices' ? createPriceRow(index) : createDepartureRow(index));
        });
    });

    document.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-remove-row]');
        if (!removeButton) {
            return;
        }
        const row = removeButton.closest('[data-row]');
        if (row) {
            row.remove();
        }
    });
});
</script>
