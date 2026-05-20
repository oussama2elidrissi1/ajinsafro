@props([
    'transfer' => null,
    'stCar' => null,
    'meta' => [],
    'featuredUrl' => null,
])

@php
    $postTitle = old('post_title', $transfer->post_title ?? '');
    $postExcerpt = old('post_excerpt', $transfer->post_excerpt ?? '');
    $postContent = old('post_content', $transfer->post_content ?? '');
    $postStatus = old('post_status', $transfer->post_status ?? 'publish');
    $postName = old('post_name', $transfer->post_name ?? '');
    $carsAddress = old('cars_address', $stCar->cars_address ?? '');
    $carsPrice = old('cars_price', $stCar->cars_price ?? '');
    $minPrice = old('min_price', $stCar->min_price ?? '');
    $maxPrice = old('max_price', $stCar->max_price ?? '');
    $numberCar = old('number_car', $stCar->number_car ?? '');
    $isFeatured = old('is_featured', $stCar->is_featured ?? 'off');
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="post_title" class="form-label">Nom du service <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('post_title') is-invalid @enderror" id="post_title" name="post_title" value="{{ $postTitle }}" required maxlength="255">
                    @error('post_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="post_excerpt" class="form-label">Résumé</label>
                    <textarea class="form-control @error('post_excerpt') is-invalid @enderror" id="post_excerpt" name="post_excerpt" rows="3" maxlength="500">{{ $postExcerpt }}</textarea>
                    @error('post_excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="post_content" class="form-label">Description complète</label>
                    <textarea class="form-control @error('post_content') is-invalid @enderror" id="post_content" name="post_content" rows="6">{{ $postContent }}</textarea>
                    @error('post_content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="aj_transfer_from" class="form-label">Ville / point de départ</label>
                        <input type="text" class="form-control @error('aj_transfer_from') is-invalid @enderror" id="aj_transfer_from" name="aj_transfer_from" value="{{ old('aj_transfer_from', $meta['aj_transfer_from'] ?? '') }}" maxlength="255">
                        @error('aj_transfer_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="aj_transfer_to" class="form-label">Ville / point d'arrivée</label>
                        <input type="text" class="form-control @error('aj_transfer_to') is-invalid @enderror" id="aj_transfer_to" name="aj_transfer_to" value="{{ old('aj_transfer_to', $meta['aj_transfer_to'] ?? '') }}" maxlength="255">
                        @error('aj_transfer_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="aj_transfer_type" class="form-label">Type de transfert</label>
                        <input type="text" class="form-control @error('aj_transfer_type') is-invalid @enderror" id="aj_transfer_type" name="aj_transfer_type" value="{{ old('aj_transfer_type', $meta['aj_transfer_type'] ?? '') }}" maxlength="120">
                        @error('aj_transfer_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="aj_transfer_vehicle_type" class="form-label">Type de véhicule</label>
                        <input type="text" class="form-control @error('aj_transfer_vehicle_type') is-invalid @enderror" id="aj_transfer_vehicle_type" name="aj_transfer_vehicle_type" value="{{ old('aj_transfer_vehicle_type', $meta['aj_transfer_vehicle_type'] ?? '') }}" maxlength="120">
                        @error('aj_transfer_vehicle_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="aj_transfer_capacity" class="form-label">Capacité</label>
                        <input type="number" min="1" class="form-control @error('aj_transfer_capacity') is-invalid @enderror" id="aj_transfer_capacity" name="aj_transfer_capacity" value="{{ old('aj_transfer_capacity', $meta['aj_transfer_capacity'] ?? '') }}">
                        @error('aj_transfer_capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="cars_address" class="form-label">Ville catalogue</label>
                        <input type="text" class="form-control @error('cars_address') is-invalid @enderror" id="cars_address" name="cars_address" value="{{ $carsAddress }}" maxlength="255">
                        @error('cars_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cars_price" class="form-label">Prix</label>
                        <input type="number" step="0.01" min="0" class="form-control @error('cars_price') is-invalid @enderror" id="cars_price" name="cars_price" value="{{ $carsPrice }}">
                        @error('cars_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="number_car" class="form-label">Nombre de véhicules</label>
                        <input type="number" min="1" class="form-control @error('number_car') is-invalid @enderror" id="number_car" name="number_car" value="{{ $numberCar }}">
                        @error('number_car')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="min_price" class="form-label">Prix min</label>
                        <input type="number" step="0.01" min="0" class="form-control @error('min_price') is-invalid @enderror" id="min_price" name="min_price" value="{{ $minPrice }}">
                        @error('min_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="max_price" class="form-label">Prix max</label>
                        <input type="number" step="0.01" min="0" class="form-control @error('max_price') is-invalid @enderror" id="max_price" name="max_price" value="{{ $maxPrice }}">
                        @error('max_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Publication</h5>
                <div class="mb-3">
                    <label for="post_status" class="form-label">Statut</label>
                    <select class="form-select @error('post_status') is-invalid @enderror" id="post_status" name="post_status" required>
                        <option value="publish" @selected($postStatus === 'publish')>Publié</option>
                        <option value="draft" @selected($postStatus === 'draft')>Brouillon</option>
                    </select>
                    @error('post_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="post_name" class="form-label">Slug</label>
                    <input type="text" class="form-control @error('post_name') is-invalid @enderror" id="post_name" name="post_name" value="{{ $postName }}" maxlength="200" placeholder="Auto si vide">
                    @error('post_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="is_featured" value="off">
                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="on" @checked($isFeatured === 'on')>
                    <label class="form-check-label" for="is_featured">Mettre en avant</label>
                </div>

                <div class="mb-3">
                    <label for="featured_image" class="form-label">Image principale</label>
                    @if($featuredUrl)
                        <div class="mb-2">
                            <img src="{{ $featuredUrl }}" alt="" class="img-thumbnail" style="max-height:120px;">
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" name="remove_featured_image" id="remove_featured_image" value="1" @checked(old('remove_featured_image'))>
                            <label class="form-check-label" for="remove_featured_image">Retirer l'image à la une</label>
                        </div>
                    @endif
                    <input type="file" class="form-control @error('featured_image') is-invalid @enderror" id="featured_image" name="featured_image" accept="image/jpeg,image/png,image/webp">
                    @error('featured_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>

