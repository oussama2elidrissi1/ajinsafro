@php
    $roomPriceRows = old('room_prices', $package->roomPrices->map(fn($item) => [
        'room_type' => $item->room_type,
        'price' => $item->price,
        'stock' => $item->stock,
    ])->all());
    $departureRows = old('departures', $package->departures->map(fn($item) => [
        'departure_date' => optional($item->departure_date)->format('Y-m-d'),
        'return_date' => optional($item->return_date)->format('Y-m-d'),
        'status' => $item->status,
        'available_places' => $item->available_places,
        'reserved_places' => $item->reserved_places,
        'price_from' => $item->price_from,
        'internal_notes' => $item->internal_notes,
    ])->all());
    $programRows = old('program_days', $package->programDays->map(fn($item) => [
        'day_number' => $item->day_number,
        'title' => $item->title,
        'description' => $item->description,
        'city' => $item->city,
        'existing_image_path' => $item->image_path,
    ])->all());

    if ($roomPriceRows === []) {
        $roomPriceRows = [['room_type' => 'quadruple', 'price' => '', 'stock' => '']];
    }
    if ($departureRows === []) {
        $departureRows = [['departure_date' => '', 'return_date' => '', 'status' => 'published', 'available_places' => '', 'reserved_places' => '', 'price_from' => '', 'internal_notes' => '']];
    }
    if ($programRows === []) {
        $programRows = [['day_number' => 1, 'title' => '', 'description' => '', 'city' => '', 'existing_image_path' => '']];
    }
@endphp

<div class="row g-4">
    <div class="col-12">
        <x-admin.form-section title="1. Informations generales" subtitle="Champs principaux de l offre.">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Titre de l offre <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $package->title) }}" class="form-control @error('title') is-invalid @enderror" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $package->slug) }}" class="form-control @error('slug') is-invalid @enderror">
                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordre d affichage</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $package->sort_order ?? 0) }}" class="form-control @error('sort_order') is-invalid @enderror">
                    @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $package->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $package->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ville de depart</label>
                    <input type="text" name="departure_city" value="{{ old('departure_city', $package->departure_city) }}" class="form-control @error('departure_city') is-invalid @enderror">
                    @error('departure_city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Destination</label>
                    <input type="text" name="destination" value="{{ old('destination', $package->destination) }}" class="form-control @error('destination') is-invalid @enderror">
                    @error('destination') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label">Jours</label>
                    <input type="number" min="0" name="duration_days" value="{{ old('duration_days', $package->duration_days) }}" class="form-control @error('duration_days') is-invalid @enderror">
                    @error('duration_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nuits</label>
                    <input type="number" min="0" name="duration_nights" value="{{ old('duration_nights', $package->duration_nights) }}" class="form-control @error('duration_nights') is-invalid @enderror">
                    @error('duration_nights') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date depart</label>
                    <input type="date" name="start_date" value="{{ old('start_date', optional($package->start_date)->format('Y-m-d')) }}" class="form-control @error('start_date') is-invalid @enderror">
                    @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date retour</label>
                    <input type="date" name="return_date" value="{{ old('return_date', optional($package->return_date)->format('Y-m-d')) }}" class="form-control @error('return_date') is-invalid @enderror">
                    @error('return_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Devise</label>
                    <input type="text" name="currency" value="{{ old('currency', $package->currency ?: 'DH') }}" class="form-control @error('currency') is-invalid @enderror">
                    @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mise en avant</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $package->is_featured))>
                        <label class="form-check-label">Activer</label>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Prix adulte</label>
                    <input type="number" step="0.01" name="adult_price" value="{{ old('adult_price', $package->adult_price) }}" class="form-control @error('adult_price') is-invalid @enderror">
                    @error('adult_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prix enfant</label>
                    <input type="number" step="0.01" name="child_price" value="{{ old('child_price', $package->child_price) }}" class="form-control @error('child_price') is-invalid @enderror">
                    @error('child_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prix bebe</label>
                    <input type="number" step="0.01" name="baby_price" value="{{ old('baby_price', $package->baby_price) }}" class="form-control @error('baby_price') is-invalid @enderror">
                    @error('baby_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Places disponibles</label>
                    <input type="number" min="0" name="available_places" value="{{ old('available_places', $package->available_places) }}" class="form-control @error('available_places') is-invalid @enderror">
                    @error('available_places') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Places reservees</label>
                    <input type="number" min="0" name="reserved_places" value="{{ old('reserved_places', $package->reserved_places) }}" class="form-control @error('reserved_places') is-invalid @enderror">
                    @error('reserved_places') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type chambre principal</label>
                    <select name="room_type" class="form-select @error('room_type') is-invalid @enderror">
                        <option value="">Choisir</option>
                        @foreach($roomTypeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('room_type', $package->room_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('room_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Description courte</label>
                    <textarea name="short_description" rows="3" class="form-control @error('short_description') is-invalid @enderror">{{ old('short_description', $package->short_description) }}</textarea>
                    @error('short_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Description detaillee</label>
                    <textarea name="description" rows="6" class="form-control @error('description') is-invalid @enderror">{{ old('description', $package->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </x-admin.form-section>
    </div>

    <div class="col-12">
        <x-admin.form-section title="2. Prix & chambres" subtitle="Configurez les tarifs par chambre et les infos hotelieres.">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Hotel Makkah</label>
                    <input type="text" name="makkah_hotel" value="{{ old('makkah_hotel', $package->makkah_hotel) }}" class="form-control @error('makkah_hotel') is-invalid @enderror">
                    @error('makkah_hotel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Distance Haram Makkah</label>
                    <input type="text" name="makkah_haram_distance" value="{{ old('makkah_haram_distance', $package->makkah_haram_distance) }}" class="form-control @error('makkah_haram_distance') is-invalid @enderror">
                    @error('makkah_haram_distance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hotel Madinah</label>
                    <input type="text" name="madinah_hotel" value="{{ old('madinah_hotel', $package->madinah_hotel) }}" class="form-control @error('madinah_hotel') is-invalid @enderror">
                    @error('madinah_hotel') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Distance Haram Madinah</label>
                    <input type="text" name="madinah_haram_distance" value="{{ old('madinah_haram_distance', $package->madinah_haram_distance) }}" class="form-control @error('madinah_haram_distance') is-invalid @enderror">
                    @error('madinah_haram_distance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Restauration</label>
                    <select name="meal_plan" class="form-select @error('meal_plan') is-invalid @enderror">
                        <option value="">Non precise</option>
                        @foreach($mealPlanOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('meal_plan', $package->meal_plan) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('meal_plan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4 pt-2">
                        <input class="form-check-input" type="checkbox" name="transport_included" value="1" id="transport_included" @checked(old('transport_included', $package->transport_included))>
                        <label class="form-check-label" for="transport_included">Transport inclus</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4 pt-2">
                        <input class="form-check-input" type="checkbox" name="visa_included" value="1" id="visa_included" @checked(old('visa_included', $package->visa_included))>
                        <label class="form-check-label" for="visa_included">Visa inclus</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4 pt-2">
                        <input class="form-check-input" type="checkbox" name="guidance_included" value="1" id="guidance_included" @checked(old('guidance_included', $package->guidance_included))>
                        <label class="form-check-label" for="guidance_included">Encadrement inclus</label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Tarifs par chambre</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="room-prices">Ajouter une ligne</button>
            </div>
            <div data-repeater="room-prices">
                @foreach($roomPriceRows as $index => $row)
                    <div class="border rounded-3 p-3 mb-3" data-row>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Type chambre</label>
                                <select name="room_prices[{{ $index }}][room_type]" class="form-select">
                                    @foreach($roomTypeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($row['room_type'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Prix</label>
                                <input type="number" step="0.01" name="room_prices[{{ $index }}][price]" value="{{ $row['price'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock / places</label>
                                <input type="number" min="0" name="room_prices[{{ $index }}][stock]" value="{{ $row['stock'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>×</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.form-section>
    </div>

    <div class="col-12">
        <x-admin.form-section title="3. Departs" subtitle="Une offre peut porter plusieurs dates de depart.">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Departs multiples</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="departures">Ajouter un depart</button>
            </div>
            <div data-repeater="departures">
                @foreach($departureRows as $index => $row)
                    <div class="border rounded-3 p-3 mb-3" data-row>
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Date depart</label>
                                <input type="date" name="departures[{{ $index }}][departure_date]" value="{{ $row['departure_date'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date retour</label>
                                <input type="date" name="departures[{{ $index }}][return_date]" value="{{ $row['return_date'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Statut</label>
                                <select name="departures[{{ $index }}][status]" class="form-select">
                                    @foreach($departureStatusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($row['status'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Places dispo</label>
                                <input type="number" min="0" name="departures[{{ $index }}][available_places]" value="{{ $row['available_places'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Places reservees</label>
                                <input type="number" min="0" name="departures[{{ $index }}][reserved_places]" value="{{ $row['reserved_places'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Prix a partir de</label>
                                <input type="number" step="0.01" name="departures[{{ $index }}][price_from]" value="{{ $row['price_from'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-11">
                                <label class="form-label">Notes internes</label>
                                <textarea name="departures[{{ $index }}][internal_notes]" rows="2" class="form-control">{{ $row['internal_notes'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>×</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.form-section>
    </div>

    <div class="col-12">
        <x-admin.form-section title="4. Programme" subtitle="Construisez un jour par jour lisible par les agents et le site public.">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Programme jour par jour</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="program-days">Ajouter un jour</button>
            </div>
            <div data-repeater="program-days">
                @foreach($programRows as $index => $row)
                    <div class="border rounded-3 p-3 mb-3" data-row>
                        <input type="hidden" name="program_days[{{ $index }}][existing_image_path]" value="{{ $row['existing_image_path'] ?? '' }}">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Jour</label>
                                <input type="number" min="1" name="program_days[{{ $index }}][day_number]" value="{{ $row['day_number'] ?? ($index + 1) }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Titre du jour</label>
                                <input type="text" name="program_days[{{ $index }}][title]" value="{{ $row['title'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ville</label>
                                <input type="text" name="program_days[{{ $index }}][city]" value="{{ $row['city'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Image</label>
                                <input type="file" name="program_day_images[{{ $index }}]" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>×</button>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="program_days[{{ $index }}][description]" rows="3" class="form-control">{{ $row['description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.form-section>
    </div>

    <div class="col-12">
        <x-admin.form-section title="5. Medias" subtitle="Image principale et galerie.">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Image principale</label>
                    <input type="file" name="main_image_file" class="form-control @error('main_image_file') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                    @error('main_image_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if($package->main_image_url)
                        <div class="mt-3">
                            <img src="{{ $package->main_image_url }}" alt="{{ $package->title }}" style="max-width:220px;border-radius:18px;">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_main_image" value="1" id="remove_main_image">
                                <label class="form-check-label" for="remove_main_image">Supprimer l image principale actuelle</label>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Galerie images</label>
                    <input type="file" name="gallery_images[]" class="form-control @error('gallery_images.*') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp" multiple>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="replace_gallery" value="1" id="replace_gallery">
                        <label class="form-check-label" for="replace_gallery">Remplacer la galerie existante</label>
                    </div>
                    @error('gallery_images.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    @if($package->images->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            @foreach($package->images as $image)
                                <img src="{{ $image->image_url }}" alt="{{ $package->title }}" style="width:92px;height:72px;object-fit:cover;border-radius:14px;">
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-admin.form-section>
    </div>

    <div class="col-12">
        <x-admin.form-section title="6. SEO" subtitle="Contenu visible dans les moteurs et infos contractuelles.">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Meta title SEO</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $package->meta_title) }}" class="form-control @error('meta_title') is-invalid @enderror">
                    @error('meta_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Meta description SEO</label>
                    <textarea name="meta_description" rows="3" class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $package->meta_description) }}</textarea>
                    @error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ce qui est inclus (une ligne = un element)</label>
                    <textarea name="included_items_text" rows="6" class="form-control @error('included_items_text') is-invalid @enderror">{{ old('included_items_text', implode("\n", $package->included_items ?? [])) }}</textarea>
                    @error('included_items_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ce qui n est pas inclus (une ligne = un element)</label>
                    <textarea name="excluded_items_text" rows="6" class="form-control @error('excluded_items_text') is-invalid @enderror">{{ old('excluded_items_text', implode("\n", $package->excluded_items ?? [])) }}</textarea>
                    @error('excluded_items_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Conditions de reservation</label>
                    <textarea name="booking_conditions" rows="5" class="form-control @error('booking_conditions') is-invalid @enderror">{{ old('booking_conditions', $package->booking_conditions) }}</textarea>
                    @error('booking_conditions') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Documents necessaires</label>
                    <textarea name="required_documents" rows="5" class="form-control @error('required_documents') is-invalid @enderror">{{ old('required_documents', $package->required_documents) }}</textarea>
                    @error('required_documents') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </x-admin.form-section>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('admin.hajj-omra.index') }}" class="aj-btn aj-btn-soft">Annuler</a>
        <button type="submit" class="aj-btn aj-btn-primary">
            <i class="bx bx-save"></i>
            <span>{{ $package->exists ? 'Enregistrer les modifications' : 'Creer l offre' }}</span>
        </button>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const templates = {
                'room-prices': function (index) {
                    return `
                        <div class="border rounded-3 p-3 mb-3" data-row>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Type chambre</label>
                                    <select name="room_prices[${index}][room_type]" class="form-select">
                                        @foreach($roomTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Prix</label>
                                    <input type="number" step="0.01" name="room_prices[${index}][price]" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Stock / places</label>
                                    <input type="number" min="0" name="room_prices[${index}][stock]" class="form-control">
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>×</button>
                                </div>
                            </div>
                        </div>`;
                },
                'departures': function (index) {
                    return `
                        <div class="border rounded-3 p-3 mb-3" data-row>
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Date depart</label>
                                    <input type="date" name="departures[${index}][departure_date]" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Date retour</label>
                                    <input type="date" name="departures[${index}][return_date]" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Statut</label>
                                    <select name="departures[${index}][status]" class="form-select">
                                        @foreach($departureStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Places dispo</label>
                                    <input type="number" min="0" name="departures[${index}][available_places]" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Places reservees</label>
                                    <input type="number" min="0" name="departures[${index}][reserved_places]" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Prix a partir de</label>
                                    <input type="number" step="0.01" name="departures[${index}][price_from]" class="form-control">
                                </div>
                                <div class="col-md-11">
                                    <label class="form-label">Notes internes</label>
                                    <textarea name="departures[${index}][internal_notes]" rows="2" class="form-control"></textarea>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>×</button>
                                </div>
                            </div>
                        </div>`;
                },
                'program-days': function (index) {
                    return `
                        <div class="border rounded-3 p-3 mb-3" data-row>
                            <input type="hidden" name="program_days[${index}][existing_image_path]" value="">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Jour</label>
                                    <input type="number" min="1" name="program_days[${index}][day_number]" value="${index + 1}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Titre du jour</label>
                                    <input type="text" name="program_days[${index}][title]" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ville</label>
                                    <input type="text" name="program_days[${index}][city]" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Image</label>
                                    <input type="file" name="program_day_images[${index}]" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" data-remove-row>×</button>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="program_days[${index}][description]" rows="3" class="form-control"></textarea>
                                </div>
                            </div>
                        </div>`;
                }
            };

            document.addEventListener('click', function (event) {
                const addButton = event.target.closest('[data-add-row]');
                if (addButton) {
                    const key = addButton.getAttribute('data-add-row');
                    const container = document.querySelector(`[data-repeater="${key}"]`);
                    if (!container || !templates[key]) {
                        return;
                    }

                    const index = container.querySelectorAll('[data-row]').length;
                    container.insertAdjacentHTML('beforeend', templates[key](index));
                    return;
                }

                const removeButton = event.target.closest('[data-remove-row]');
                if (removeButton) {
                    const row = removeButton.closest('[data-row]');
                    const container = row ? row.parentElement : null;
                    if (row && container && container.querySelectorAll('[data-row]').length > 1) {
                        row.remove();
                    }
                }
            });
        })();
    </script>
@endpush
