<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Titre <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $package->title) }}" required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Slug <span class="text-danger">*</span></label>
        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $package->slug) }}" required>
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Pays <span class="text-danger">*</span></label>
        <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', $package->country) }}" required>
        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Ville <span class="text-danger">*</span></label>
        <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $package->city) }}" required>
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Jours <span class="text-danger">*</span></label>
        <input type="number" name="duration_days" min="1" class="form-control @error('duration_days') is-invalid @enderror" value="{{ old('duration_days', $package->duration_days) }}" required>
        @error('duration_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Nuits <span class="text-danger">*</span></label>
        <input type="number" name="nights" min="0" class="form-control @error('nights') is-invalid @enderror" value="{{ old('nights', $package->nights) }}" required>
        @error('nights') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Type de pension</label>
        <input type="text" name="pension_type" class="form-control @error('pension_type') is-invalid @enderror" value="{{ old('pension_type', $package->pension_type) }}">
        @error('pension_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Type d'hébergement</label>
        <input type="text" name="accommodation_type" class="form-control @error('accommodation_type') is-invalid @enderror" value="{{ old('accommodation_type', $package->accommodation_type) }}">
        @error('accommodation_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Badge</label>
        <input type="text" name="badge" class="form-control @error('badge') is-invalid @enderror" value="{{ old('badge', $package->badge) }}">
        @error('badge') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Description courte</label>
        <textarea name="short_description" rows="3" class="form-control @error('short_description') is-invalid @enderror">{{ old('short_description', $package->short_description) }}</textarea>
        @error('short_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Inclus (un par ligne)</label>
        <textarea name="includes" rows="4" class="form-control @error('includes') is-invalid @enderror">{{ old('includes', implode("\n", $package->includes ?? [])) }}</textarea>
        @error('includes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8">
        <label class="form-label">URL image</label>
        <input type="text" name="image_url" class="form-control @error('image_url') is-invalid @enderror" value="{{ old('image_url', $package->image_url) }}">
        @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Prix <span class="text-danger">*</span></label>
        <input type="number" step="0.01" name="price_from" class="form-control @error('price_from') is-invalid @enderror" value="{{ old('price_from', $package->price_from) }}" required>
        @error('price_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Devise <span class="text-danger">*</span></label>
        <input type="text" name="currency" class="form-control @error('currency') is-invalid @enderror" value="{{ old('currency', $package->currency) }}" required>
        @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured', $package->is_featured) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_featured">En vedette</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Actif</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Ordre de tri</label>
        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $package->sort_order) }}">
        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

