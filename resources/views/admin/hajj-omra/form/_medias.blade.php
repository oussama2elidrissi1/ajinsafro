{{-- Etape 7 : image principale et galerie. --}}
@php
    $galleryRows = old('gallery', $package->images->map(fn ($img) => [
        'id' => $img->id,
        'image_path' => $img->image_path,
        'alt_text' => $img->alt_text,
    ])->values()->all());

    $mainImage = old('main_image', $package->main_image);
    $mainImageUrl = $mainImage
        ? (\Illuminate\Support\Str::startsWith($mainImage, ['http://', 'https://'])
            ? $mainImage
            : \Illuminate\Support\Facades\Storage::disk('public')->url($mainImage))
        : null;
@endphp

<div class="ho-panel" data-panel="medias">
    <h6 class="text-uppercase text-muted small mb-3">Image principale</h6>

    <div class="row g-3 align-items-start mb-4">
        <div class="col-md-5">
            <div class="ho-dropzone" data-main-dropzone>
                <div class="mb-2">Glissez une image ici, ou cliquez pour parcourir</div>
                <div class="small">JPG, PNG ou WEBP — 5 Mo maximum</div>
                <input type="file" accept="image/*" class="d-none" data-role="file">
            </div>
            <input type="hidden" name="main_image" value="{{ $mainImage }}" data-role="main-path">
            <div class="form-text mt-1" data-role="main-status"></div>
        </div>
        <div class="col-md-4">
            <div class="position-relative d-inline-block" data-role="main-preview-wrap" @if (! $mainImageUrl) hidden @endif>
                <img src="{{ $mainImageUrl }}" alt="" class="img-fluid ho-main-preview" data-role="main-preview">
                <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-block" data-role="main-clear">Retirer l'image</button>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h6 class="text-uppercase text-muted small mb-1">Galerie</h6>
            <p class="text-muted small mb-0">Glissez les vignettes pour les réordonner. La suppression est immédiate à l'enregistrement.</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" data-role="gallery-browse">+ Ajouter des images</button>
        <input type="file" accept="image/*" multiple class="d-none" data-role="gallery-file">
    </div>

    <div class="ho-gallery" data-gallery-list data-sortable="gallery">
        @foreach ($galleryRows as $i => $row)
            @php
                $url = ($row['image_path'] ?? null)
                    ? (\Illuminate\Support\Str::startsWith($row['image_path'], ['http://', 'https://'])
                        ? $row['image_path']
                        : \Illuminate\Support\Facades\Storage::disk('public')->url($row['image_path']))
                    : null;
            @endphp
            <div class="ho-thumb" data-gallery-item>
                <input type="hidden" name="gallery[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                <input type="hidden" name="gallery[{{ $i }}][image_path]" value="{{ $row['image_path'] }}">
                <input type="hidden" name="gallery[{{ $i }}][alt_text]" value="{{ $row['alt_text'] }}">
                <img src="{{ $url }}" alt="{{ $row['alt_text'] }}">
                <button type="button" class="ho-thumb-remove" data-role="gallery-remove" title="Retirer">&times;</button>
            </div>
        @endforeach
    </div>

    <p class="text-muted small mt-2" data-role="gallery-empty" @if (count($galleryRows)) hidden @endif>
        Aucune image dans la galerie.
    </p>

    <template data-gallery-template>
        <div class="ho-thumb" data-gallery-item>
            <input type="hidden" name="gallery[__INDEX__][id]" value="">
            <input type="hidden" name="gallery[__INDEX__][image_path]" value="">
            <input type="hidden" name="gallery[__INDEX__][alt_text]" value="">
            <img src="" alt="">
            <button type="button" class="ho-thumb-remove" data-role="gallery-remove" title="Retirer">&times;</button>
        </div>
    </template>
</div>
