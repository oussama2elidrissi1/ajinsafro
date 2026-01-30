@props(['hotel' => null, 'stHotel' => null])

@php
    $isEdit = $hotel !== null;
    $postTitle = old('post_title', $hotel->post_title ?? '');
    $postContent = old('post_content', $hotel->post_content ?? '');
    $postStatus = old('post_status', $hotel->post_status ?? 'publish');
    $postName = old('post_name', $hotel->post_name ?? '');
    $address = old('address', $stHotel->address ?? '');
    $hotelStar = old('hotel_star', $stHotel->hotel_star ?? '');
    $minPrice = old('min_price', $stHotel->min_price ?? '');
    $mapLat = old('map_lat', $stHotel->map_lat ?? '');
    $mapLng = old('map_lng', $stHotel->map_lng ?? '');
    $isFeatured = old('is_featured', $stHotel->is_featured ?? 'off');
@endphp

<div class="mb-3">
    <label for="post_title" class="form-label">Titre <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('post_title') is-invalid @enderror" id="post_title" name="post_title" value="{{ $postTitle }}" required maxlength="255">
    @error('post_title')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="post_content" class="form-label">Contenu</label>
    <textarea class="form-control @error('post_content') is-invalid @enderror" id="post_content" name="post_content" rows="4">{{ $postContent }}</textarea>
    @error('post_content')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="post_status" class="form-label">Statut <span class="text-danger">*</span></label>
    <select class="form-select @error('post_status') is-invalid @enderror" id="post_status" name="post_status" required>
        <option value="publish" {{ $postStatus === 'publish' ? 'selected' : '' }}>Publié</option>
        <option value="draft" {{ $postStatus === 'draft' ? 'selected' : '' }}>Brouillon</option>
    </select>
    @error('post_status')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="post_name" class="form-label">Slug (optionnel)</label>
    <input type="text" class="form-control @error('post_name') is-invalid @enderror" id="post_name" name="post_name" value="{{ $postName }}" placeholder="Auto si vide" maxlength="200">
    <small class="text-muted">Laissez vide pour générer automatiquement à partir du titre.</small>
    @error('post_name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="address" class="form-label">Adresse</label>
    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ $address }}">
    @error('address')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="hotel_star" class="form-label">Étoiles (1–5)</label>
        <select class="form-select @error('hotel_star') is-invalid @enderror" id="hotel_star" name="hotel_star">
            <option value="">—</option>
            @for ($i = 1; $i <= 5; $i++)
                <option value="{{ $i }}" {{ (string)$i === (string)$hotelStar ? 'selected' : '' }}>{{ $i }} étoile(s)</option>
            @endfor
        </select>
        @error('hotel_star')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="min_price" class="form-label">Prix minimum</label>
        <input type="number" step="0.01" min="0" class="form-control @error('min_price') is-invalid @enderror" id="min_price" name="min_price" value="{{ $minPrice }}">
        @error('min_price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="map_lat" class="form-label">Latitude</label>
        <input type="text" class="form-control @error('map_lat') is-invalid @enderror" id="map_lat" name="map_lat" value="{{ $mapLat }}">
        @error('map_lat')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="map_lng" class="form-label">Longitude</label>
        <input type="text" class="form-control @error('map_lng') is-invalid @enderror" id="map_lng" name="map_lng" value="{{ $mapLng }}">
        @error('map_lng')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <div class="form-check form-switch">
        <input type="hidden" name="is_featured" value="off">
        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="on" {{ $isFeatured === 'on' ? 'checked' : '' }}>
        <label class="form-check-label" for="is_featured">À la une</label>
    </div>
</div>
