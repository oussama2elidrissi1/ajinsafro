@props(['hotel' => null, 'stHotel' => null, 'meta' => [], 'galleryUrls' => [], 'featuredUrl' => null])

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
    $hotelAmenities = old('hotel_amenities', $meta['hotel_amenities'] ?? '');
    $hotelPolicies = old('hotel_policies', $meta['hotel_policies'] ?? '');
    $hotelPhone = old('hotel_phone', $meta['hotel_phone'] ?? '');
    $hotelEmail = old('hotel_email', $meta['hotel_email'] ?? '');
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
    <label for="featured_image" class="form-label">Image à la une</label>
    @if($featuredUrl)
        <div class="mb-2">
            <img src="{{ $featuredUrl }}" alt="Image à la une" class="img-thumbnail" style="max-height: 120px;">
            <span class="text-muted small d-block">Remplacer en choisissant un nouveau fichier.</span>
        </div>
    @endif
    <input type="file" class="form-control @error('featured_image') is-invalid @enderror" id="featured_image" name="featured_image" accept="image/jpeg,image/png,image/webp">
    <small class="text-muted">JPG, PNG, WebP. Max 5 Mo. Stocké dans wp-content/uploads/Y/m/</small>
    @error('featured_image')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="gallery_images" class="form-label">Galerie d'images</label>
    @if(!empty($galleryUrls))
        <div class="d-flex flex-wrap gap-2 mb-2" id="gallery-current">
            @foreach($galleryUrls as $item)
                <div class="gallery-item position-relative d-inline-block" data-id="{{ $item['id'] }}">
                    <img src="{{ $item['url'] }}" alt="Galerie" class="img-thumbnail" style="height: 80px; width: auto;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 gallery-remove" style="transform: translate(50%, -50%);" title="Retirer de la galerie"><i class="bx bx-trash font-size-12"></i></button>
                    <input type="hidden" name="gallery_keep_ids[]" value="{{ $item['id'] }}">
                </div>
            @endforeach
        </div>
    @endif
    <input type="file" class="form-control @error('gallery_images') is-invalid @enderror" id="gallery_images" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
    <small class="text-muted">Max 10 fichiers. JPG, PNG, WebP. 5 Mo par fichier.</small>
    @error('gallery_images')
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

<hr class="my-4">
<h5 class="mb-3">Meta (postmeta)</h5>

<div class="mb-3">
    <label for="hotel_amenities" class="form-label">Équipements (amenities)</label>
    <textarea class="form-control @error('hotel_amenities') is-invalid @enderror" id="hotel_amenities" name="hotel_amenities" rows="3" placeholder="JSON ou texte libre">{{ $hotelAmenities }}</textarea>
    @error('hotel_amenities')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="hotel_policies" class="form-label">Politiques</label>
    <textarea class="form-control @error('hotel_policies') is-invalid @enderror" id="hotel_policies" name="hotel_policies" rows="3" placeholder="JSON ou texte libre">{{ $hotelPolicies }}</textarea>
    @error('hotel_policies')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="hotel_phone" class="form-label">Téléphone</label>
        <input type="text" class="form-control @error('hotel_phone') is-invalid @enderror" id="hotel_phone" name="hotel_phone" value="{{ $hotelPhone }}" maxlength="100">
        @error('hotel_phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="hotel_email" class="form-label">Email</label>
        <input type="email" class="form-control @error('hotel_email') is-invalid @enderror" id="hotel_email" name="hotel_email" value="{{ $hotelEmail }}" maxlength="255">
        @error('hotel_email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

@if(!empty($galleryUrls))
@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.gallery-remove').forEach(function(btn) {
        btn.addEventListener('click', function() { this.closest('.gallery-item').remove(); });
    });
});
</script>
@endpush
@endif
