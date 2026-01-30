@props(['hotelDetailMeta' => [], 'logoUrl' => null, 'galleryUrls' => []])

@php
    $metaIsFeatured = old('_is_featured', $hotelDetailMeta['_is_featured'] ?? '');
    $metaExternalBooking = old('_external_booking', $hotelDetailMeta['_external_booking'] ?? '');
    $externalLink = old('external_booking_link', $hotelDetailMeta['_external_booking_link'] ?? '');
    $singleLayout = old('_single_layout', $hotelDetailMeta['_single_layout'] ?? '');
@endphp

<h5 class="mb-3">Hotel detail</h5>

<div class="mb-3">
    <div class="form-check form-switch">
        <input type="hidden" name="_is_featured" value="0">
        <input class="form-check-input" type="checkbox" id="_is_featured_cb" name="_is_featured" value="1" {{ $metaIsFeatured === '1' ? 'checked' : '' }}>
        <label class="form-check-label" for="_is_featured_cb">Set hotel as feature</label>
    </div>
</div>

<div class="mb-3">
    <div class="form-check form-switch">
        <input type="hidden" name="_external_booking" value="0">
        <input class="form-check-input" type="checkbox" id="_external_booking" name="_external_booking" value="1" {{ $metaExternalBooking === '1' ? 'checked' : '' }}>
        <label class="form-check-label" for="_external_booking">Hotel external booking</label>
    </div>
    <div id="external-booking-link-wrap" class="mt-2 {{ $metaExternalBooking === '1' ? '' : 'd-none' }}">
        <label for="external_booking_link" class="form-label">External booking link</label>
        <input type="url" class="form-control @error('external_booking_link') is-invalid @enderror" id="external_booking_link" name="external_booking_link" value="{{ $externalLink }}" placeholder="https://...">
        @error('external_booking_link')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label for="hotel_logo" class="form-label">Hotel logo</label>
    <div id="hotel-logo-preview" class="mb-2">
        @if($logoUrl)
            <div class="d-flex align-items-center gap-2 hotel-logo-wrap">
                <div class="hotel-logo-img-wrap position-relative" style="width: 80px; height: 80px;">
                    <img src="{{ $logoUrl }}" alt="Logo" class="img-thumbnail hotel-logo-img" style="max-height: 80px; max-width: 80px; object-fit: contain;" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                    <div class="d-none hotel-logo-placeholder position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-light border rounded text-muted small text-center" style="font-size: 10px;">Image introuvable</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" id="hotel-logo-remove">Remove logo</button>
                <input type="hidden" name="hotel_logo_remove" id="hotel_logo_remove_input" value="0">
            </div>
        @else
            <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted small" style="width: 80px; height: 50px;">Aucun logo</div>
        @endif
    </div>
    <input type="file" class="form-control @error('hotel_logo') is-invalid @enderror" id="hotel_logo" name="hotel_logo" accept="image/jpeg,image/png,image/webp">
    <small class="text-muted">JPG, PNG, WebP. Max 5 Mo.</small>
    @error('hotel_logo')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Hotel single layout</label>
    <div class="d-flex gap-3">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="_single_layout" id="layout_1" value="layout-1" {{ $singleLayout === 'layout-1' ? 'checked' : '' }}>
            <label class="form-check-label" for="layout_1">Layout 1</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="_single_layout" id="layout_2" value="layout-2" {{ $singleLayout === 'layout-2' ? 'checked' : '' }}>
            <label class="form-check-label" for="layout_2">Layout 2</label>
        </div>
    </div>
</div>

<hr class="my-4">
<h6 class="mb-3">Images / Gallery</h6>
@if(!empty($galleryUrls))
    <div class="d-flex flex-wrap gap-2 mb-2" id="gallery-current">
        @foreach($galleryUrls as $item)
            <div class="gallery-item position-relative d-inline-block" data-id="{{ $item['id'] }}" style="width: 80px; height: 80px;">
                <div class="position-relative w-100 h-100">
                    <img src="{{ $item['url'] }}" alt="Galerie" class="img-thumbnail gallery-img" style="height: 80px; width: 80px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                    <div class="d-none gallery-placeholder position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-light border rounded text-muted small text-center" style="font-size: 9px;">Image introuvable</div>
                </div>
                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 gallery-remove" style="transform: translate(50%, -50%);" title="Retirer"><i class="bx bx-trash font-size-12"></i></button>
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
