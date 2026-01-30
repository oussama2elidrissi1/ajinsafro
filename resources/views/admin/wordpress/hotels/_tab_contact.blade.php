@props(['meta' => []])

@php
    $hotelPhone = old('hotel_phone', $meta['hotel_phone'] ?? '');
    $hotelEmail = old('hotel_email', $meta['hotel_email'] ?? '');
@endphp

<h5 class="mb-3">Contact</h5>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="hotel_phone" class="form-label">Téléphone</label>
        <input type="text" class="form-control @error('hotel_phone') is-invalid @enderror" id="hotel_phone" name="hotel_phone" value="{{ $hotelPhone }}" maxlength="100">
        @error('hotel_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="hotel_email" class="form-label">Email</label>
        <input type="email" class="form-control @error('hotel_email') is-invalid @enderror" id="hotel_email" name="hotel_email" value="{{ $hotelEmail }}" maxlength="255">
        @error('hotel_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
