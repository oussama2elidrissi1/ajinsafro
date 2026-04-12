@props([
    'activity' => null,
    'stActivity' => null,
    'meta' => [],
    'galleryUrls' => [],
    'featuredUrl' => null,
])

@php
    $postTitle = old('post_title', $activity->post_title ?? '');
    $postExcerpt = old('post_excerpt', $activity->post_excerpt ?? '');
    $postContent = old('post_content', $activity->post_content ?? '');
    $postStatus = old('post_status', $activity->post_status ?? 'publish');
    $postName = old('post_name', $activity->post_name ?? '');
    $address = old('address', $stActivity->address ?? '');
    $typeActivity = old('type_activity', $stActivity->type_activity ?? '');
    $adultPrice = old('adult_price', $stActivity->adult_price ?? '');
    $childPrice = old('child_price', $stActivity->child_price ?? '');
    $minPrice = old('min_price', $stActivity->min_price ?? '');
    $duration = old('duration', $stActivity->duration ?? '');
    $maxPeople = old('max_people', $stActivity->max_people ?? '');
    $rateReview = old('rate_review', $stActivity->rate_review ?? '');
    $isFeatured = old('is_featured', $stActivity->is_featured ?? 'off');
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="post_title" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('post_title') is-invalid @enderror" id="post_title" name="post_title" value="{{ $postTitle }}" required maxlength="255">
                    @error('post_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="post_excerpt" class="form-label">Résumé court</label>
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
                        <label for="address" class="form-label">Lieu / destination</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ $address }}" maxlength="255">
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="type_activity" class="form-label">Type d'activité</label>
                        <input type="text" class="form-control @error('type_activity') is-invalid @enderror" id="type_activity" name="type_activity" value="{{ $typeActivity }}" maxlength="120">
                        @error('type_activity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="adult_price" class="form-label">Prix adulte</label>
                        <input type="number" step="0.01" min="0" class="form-control @error('adult_price') is-invalid @enderror" id="adult_price" name="adult_price" value="{{ $adultPrice }}">
                        @error('adult_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="child_price" class="form-label">Prix enfant</label>
                        <input type="number" step="0.01" min="0" class="form-control @error('child_price') is-invalid @enderror" id="child_price" name="child_price" value="{{ $childPrice }}">
                        @error('child_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="min_price" class="form-label">Prix min</label>
                        <input type="number" step="0.01" min="0" class="form-control @error('min_price') is-invalid @enderror" id="min_price" name="min_price" value="{{ $minPrice }}">
                        @error('min_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="duration" class="form-label">Durée</label>
                        <input type="text" class="form-control @error('duration') is-invalid @enderror" id="duration" name="duration" value="{{ $duration }}" maxlength="120">
                        @error('duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="max_people" class="form-label">Participants max</label>
                        <input type="number" min="1" class="form-control @error('max_people') is-invalid @enderror" id="max_people" name="max_people" value="{{ $maxPeople }}">
                        @error('max_people')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="rate_review" class="form-label">Note</label>
                        <input type="number" min="0" max="5" step="0.1" class="form-control @error('rate_review') is-invalid @enderror" id="rate_review" name="rate_review" value="{{ $rateReview }}">
                        @error('rate_review')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <label for="aj_activity_category" class="form-label">Catégorie catalogue</label>
                    <input type="text" class="form-control @error('aj_activity_category') is-invalid @enderror" id="aj_activity_category" name="aj_activity_category" value="{{ old('aj_activity_category', $meta['aj_activity_category'] ?? '') }}" maxlength="120">
                    @error('aj_activity_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="aj_activity_place_text" class="form-label">Libellé lieu</label>
                    <input type="text" class="form-control @error('aj_activity_place_text') is-invalid @enderror" id="aj_activity_place_text" name="aj_activity_place_text" value="{{ old('aj_activity_place_text', $meta['aj_activity_place_text'] ?? '') }}" maxlength="255">
                    @error('aj_activity_place_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="aj_activity_min_age" class="form-label">Âge min</label>
                        <input type="number" min="0" class="form-control @error('aj_activity_min_age') is-invalid @enderror" id="aj_activity_min_age" name="aj_activity_min_age" value="{{ old('aj_activity_min_age', $meta['aj_activity_min_age'] ?? '') }}">
                        @error('aj_activity_min_age')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 mb-3">
                        <label for="aj_activity_max_age" class="form-label">Âge max</label>
                        <input type="number" min="0" class="form-control @error('aj_activity_max_age') is-invalid @enderror" id="aj_activity_max_age" name="aj_activity_max_age" value="{{ old('aj_activity_max_age', $meta['aj_activity_max_age'] ?? '') }}">
                        @error('aj_activity_max_age')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Images</h5>
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

                <div class="mb-3">
                    <label for="gallery_images" class="form-label">Galerie</label>
                    @if(!empty($galleryUrls))
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @foreach($galleryUrls as $item)
                                <div class="gallery-item position-relative">
                                    <img src="{{ $item['url'] }}" alt="" class="img-thumbnail" style="height:80px;">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 gallery-remove" style="transform: translate(50%, -50%);">
                                        <i class="bx bx-trash font-size-12"></i>
                                    </button>
                                    <input type="hidden" name="gallery_keep_ids[]" value="{{ $item['id'] }}">
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <input type="file" class="form-control @error('gallery_images') is-invalid @enderror" id="gallery_images" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
                    @error('gallery_images')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.gallery-remove').forEach(function (button) {
        button.addEventListener('click', function () {
            button.closest('.gallery-item').remove();
        });
    });
});
</script>
@endpush
