@props(['meta' => []])

@php
    $hotelAmenities = old('hotel_amenities', $meta['hotel_amenities'] ?? '');
    $hotelPolicies = old('hotel_policies', $meta['hotel_policies'] ?? '');
@endphp

<h5 class="mb-3">Other options</h5>
<div class="mb-3">
    <label for="hotel_amenities" class="form-label">Équipements (amenities)</label>
    <textarea class="form-control @error('hotel_amenities') is-invalid @enderror" id="hotel_amenities" name="hotel_amenities" rows="3" placeholder="JSON ou texte libre">{{ $hotelAmenities }}</textarea>
    @error('hotel_amenities')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label for="hotel_policies" class="form-label">Politiques</label>
    <textarea class="form-control @error('hotel_policies') is-invalid @enderror" id="hotel_policies" name="hotel_policies" rows="3" placeholder="JSON ou texte libre">{{ $hotelPolicies }}</textarea>
    @error('hotel_policies')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
