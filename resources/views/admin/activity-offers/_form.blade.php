<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Titre <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $offer->title) }}" required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Slug <span class="text-danger">*</span></label>
        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $offer->slug) }}" required>
        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Pays <span class="text-danger">*</span></label>
        <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', $offer->country) }}" required>
        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Ville <span class="text-danger">*</span></label>
        <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $offer->city) }}" required>
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Catégorie <span class="text-danger">*</span></label>
        <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category', $offer->category) }}" required>
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Durée</label>
        <input type="text" name="duration_label" class="form-control @error('duration_label') is-invalid @enderror" value="{{ old('duration_label', $offer->duration_label) }}">
        @error('duration_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Badge</label>
        <input type="text" name="badge" class="form-control @error('badge') is-invalid @enderror" value="{{ old('badge', $offer->badge) }}">
        @error('badge') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Disponibilité</label>
        <input type="text" name="availability_label" class="form-control @error('availability_label') is-invalid @enderror" value="{{ old('availability_label', $offer->availability_label) }}">
        @error('availability_label') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Description courte</label>
        <textarea name="short_description" rows="3" class="form-control @error('short_description') is-invalid @enderror">{{ old('short_description', $offer->short_description) }}</textarea>
        @error('short_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">Inclus (un par ligne)</label>
        <textarea name="includes" rows="4" class="form-control @error('includes') is-invalid @enderror">{{ old('includes', implode("\n", $offer->includes ?? [])) }}</textarea>
        @error('includes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8">
        <label class="form-label">URL image</label>
        <input type="text" name="image_url" class="form-control @error('image_url') is-invalid @enderror" value="{{ old('image_url', $offer->image_url) }}">
        @error('image_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Prix <span class="text-danger">*</span></label>
        <input type="number" step="0.01" name="price_from" class="form-control @error('price_from') is-invalid @enderror" value="{{ old('price_from', $offer->price_from) }}" required>
        @error('price_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Devise <span class="text-danger">*</span></label>
        <input type="text" name="currency" class="form-control @error('currency') is-invalid @enderror" value="{{ old('currency', $offer->currency) }}" required>
        @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured', $offer->is_featured) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_featured">En vedette</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $offer->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Actif</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Ordre de tri</label>
        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $offer->sort_order) }}">
        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

