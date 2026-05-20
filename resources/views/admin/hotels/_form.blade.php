@php
    /** @var \App\Models\Hotel|null $hotel */
    $hotel = $hotel ?? null;
@endphp

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required
                       value="{{ old('name', $hotel->name ?? '') }}">
            </div>
            <div class="col-md-4 d-flex align-items-center">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', $hotel->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Actif</label>
                </div>
            </div>
            <div class="col-md-8">
                <label class="form-label">Adresse</label>
                <input type="text" name="address" class="form-control"
                       value="{{ old('address', $hotel->address ?? '') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ville</label>
                <input type="text" name="city" class="form-control"
                       value="{{ old('city', $hotel->city ?? '') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Pays</label>
                <input type="text" name="country" class="form-control"
                       value="{{ old('country', $hotel->country ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-control">{{ old('description', $hotel->description ?? '') }}</textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Latitude</label>
                <input type="text" name="latitude" class="form-control"
                       value="{{ old('latitude', $hotel->latitude ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Longitude</label>
                <input type="text" name="longitude" class="form-control"
                       value="{{ old('longitude', $hotel->longitude ?? '') }}">
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">Galerie images</h5>
    </div>
    <div class="card-body">
        @if(isset($hotel) && $hotel->images->isNotEmpty())
            <div class="row g-2 mb-3">
                @foreach($hotel->images as $img)
                    <div class="col-auto text-center">
                        <div class="position-relative">
                            <img src="{{ asset('storage/'.$img->file_path) }}" alt="" class="rounded mb-1"
                                 style="width:80px;height:60px;object-fit:cover;">
                            <div class="form-check small">
                                <input class="form-check-input" type="checkbox" name="keep_image_ids[]"
                                       value="{{ $img->id }}" id="keep-img-{{ $img->id }}" checked>
                                <label class="form-check-label" for="keep-img-{{ $img->id }}">Garder</label>
                            </div>
                            <div class="form-check small">
                                <input class="form-check-input" type="radio" name="primary_image_id"
                                       value="{{ $img->id }}" id="primary-{{ $img->id }}"
                                       {{ $img->is_primary ? 'checked' : '' }}>
                                <label class="form-check-label" for="primary-{{ $img->id }}">Principale</label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        <div class="mb-2">
            <label class="form-label">Nouvelles images</label>
            <input type="file" name="images[]" class="form-control" multiple accept="image/*">
            <small class="text-muted">JPG/PNG/WebP, max 5 Mo par image.</small>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-light">
        <h5 class="mb-0">�?quipements</h5>
    </div>
    <div class="card-body">
        <div class="row g-2">
            @foreach($amenities as $amenity)
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="amenities[]"
                               value="{{ $amenity->id }}" id="amenity-{{ $amenity->id }}"
                               {{ in_array($amenity->id, old('amenities', isset($hotel) ? $hotel->amenities->pluck('id')->all() : [])) ? 'checked' : '' }}>
                        <label class="form-check-label" for="amenity-{{ $amenity->id }}">
                            @if($amenity->icon)<i class="{{ $amenity->icon }} me-1"></i>@endif
                            {{ $amenity->label }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Types de chambres</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-room-type">
            <i class="bx bx-plus me-1"></i> Ajouter un type
        </button>
    </div>
    <div class="card-body">
        <div id="room-types-container" class="row g-2">
            @php
                $oldRoomTypes = old('room_types', isset($hotel) ? $hotel->roomTypes->toArray() : []);
            @endphp
            @foreach($oldRoomTypes as $idx => $rt)
                <div class="col-12 room-type-row mb-2">
                    <div class="border rounded p-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Type</strong>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-room-type">&times;</button>
                        </div>
                        <div class="row g-2">
                            <input type="hidden" name="room_types[{{ $idx }}][id]" value="{{ $rt['id'] ?? '' }}">
                            <div class="col-md-4">
                                <input type="text" name="room_types[{{ $idx }}][name]" class="form-control"
                                       placeholder="Nom (Suite, Double...)" value="{{ $rt['name'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <input type="number" min="1" name="room_types[{{ $idx }}][capacity_adults]" class="form-control"
                                       placeholder="Adultes" value="{{ $rt['capacity_adults'] ?? 2 }}">
                            </div>
                            <div class="col-md-2">
                                <input type="number" min="0" name="room_types[{{ $idx }}][capacity_children]" class="form-control"
                                       placeholder="Enfants" value="{{ $rt['capacity_children'] ?? 0 }}">
                            </div>
                            <div class="col-md-2">
                                <input type="number" min="0" name="room_types[{{ $idx }}][quantity]" class="form-control"
                                       placeholder="Qté" value="{{ $rt['quantity'] ?? 0 }}">
                            </div>
                            <div class="col-md-2">
                                <input type="number" step="0.01" min="0" name="room_types[{{ $idx }}][base_price]" class="form-control"
                                       placeholder="Prix" value="{{ $rt['base_price'] ?? '' }}">
                            </div>
                            <div class="col-12">
                                <input type="text" name="room_types[{{ $idx }}][description]" class="form-control"
                                       placeholder="Description" value="{{ $rt['description'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            var container = document.getElementById('room-types-container');
            var addBtn = document.getElementById('btn-add-room-type');
            if (!container || !addBtn) return;

            function nextIndex() {
                var rows = container.querySelectorAll('.room-type-row');
                return rows.length;
            }

            addBtn.addEventListener('click', function () {
                var i = nextIndex();
                var wrapper = document.createElement('div');
                wrapper.className = 'col-12 room-type-row mb-2';
                wrapper.innerHTML =
                    '<div class="border rounded p-2 bg-light">' +
                    '<div class="d-flex justify-content-between align-items-center mb-2">' +
                    '<strong>Type</strong>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-room-type">&times;</button>' +
                    '</div>' +
                    '<div class="row g-2">' +
                    '<div class="col-md-4">' +
                    '<input type="text" name="room_types[' + i + '][name]" class="form-control" placeholder="Nom (Suite, Double...)">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                    '<input type="number" min="1" name="room_types[' + i + '][capacity_adults]" class="form-control" placeholder="Adultes" value="2">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                    '<input type="number" min="0" name="room_types[' + i + '][capacity_children]" class="form-control" placeholder="Enfants" value="0">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                    '<input type="number" min="0" name="room_types[' + i + '][quantity]" class="form-control" placeholder="Qté" value="0">' +
                    '</div>' +
                    '<div class="col-md-2">' +
                    '<input type="number" step="0.01" min="0" name="room_types[' + i + '][base_price]" class="form-control" placeholder="Prix">' +
                    '</div>' +
                    '<div class="col-12">' +
                    '<input type="text" name="room_types[' + i + '][description]" class="form-control" placeholder="Description">' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                container.appendChild(wrapper);
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('btn-remove-room-type')) {
                    var row = e.target.closest('.room-type-row');
                    if (row) row.remove();
                }
            });
        })();
    </script>
@endpush


