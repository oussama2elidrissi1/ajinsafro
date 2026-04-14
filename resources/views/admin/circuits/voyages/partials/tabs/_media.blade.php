<div class="tab-pane" id="media" role="tabpanel" data-ve-pane-title="Médias">
                <div class="card ve-pane-card">
                    <div class="card-body">
                        <h4 class="card-title mb-2">Images & VidÃ©os</h4>
                        <p class="text-muted small mb-4">Hero, Ã  la une WordPress et galerie.</p>

                        {{-- Section 1 : Image principale (Hero / Cover) --}}
                        <div class="mb-4 p-3 p-md-4 border rounded-3 bg-light ve-media-section">
                            <h5 class="mb-3">Image principale (Hero / Cover)</h5>
                            <input type="hidden" name="hero_image_id" id="hero_image_id" value="{{ old('hero_image_id', $meta['hero_image_id'] ?? '') }}">
                            <div class="row g-4 align-items-start ve-media-hero-grid">
                                <div class="col-auto">
                                <div id="hero-image-preview-wrap" class="border rounded-3 overflow-hidden bg-white shadow-sm ve-media-thumb" style="width: 220px; min-height: 140px; display: {{ ($heroImageUrl ?? '') ? 'block' : 'none' }};">
                                    <img id="hero-image-preview" src="{{ $heroImageUrl ?? '' }}" alt="Hero" class="img-fluid w-100" style="max-height: 220px; object-fit: cover;">
                                </div>
                                </div>
                                <div class="col min-w-0">
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2" id="hero-upload-btn">
                                            <i class="bx bx-upload"></i> Uploader une image
                                        </button>
                                        <input type="file" id="hero_image_file" accept="image/jpeg,image/png,image/webp" class="d-none">
                                        <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="hero-choose-media-btn">
                                            <i class="bx bx-images"></i> Choisir depuis la mÃ©diathÃ¨que
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="hero-remove-btn">
                                            <i class="bx bx-trash"></i> Supprimer
                                        </button>
                                    </div>
                                    <small class="text-muted d-block">JPG, PNG ou WebP "â€ max 5 Mo.</small>
                                    <div id="hero-upload-error" class="alert alert-danger mt-2 mb-0 d-none" role="alert"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Option : utiliser l'image principale comme image ÃƒÂ  la une WP --}}
                        <div class="mb-3">
                            <div class="form-check">
                                @php $useHeroAsThumb = old('hero_use_as_thumbnail') !== null ? (bool) old('hero_use_as_thumbnail') : (isset($meta['hero_image_id']) && isset($meta['thumbnail_id']) && (string)$meta['hero_image_id'] === (string)$meta['thumbnail_id']); @endphp
                                <input class="form-check-input" type="checkbox" name="hero_use_as_thumbnail" value="1" id="hero_use_as_thumbnail" {{ $useHeroAsThumb ? 'checked' : '' }}>
                                <label class="form-check-label" for="hero_use_as_thumbnail">Utiliser l'image principale comme image ÃƒÂ  la une WordPress</label>
                            </div>
                        </div>

                        {{-- Section 2 : Image Ã  la une WordPress (Featured Image) --}}
                        @php
                            $wpFeaturedImageId = old('thumbnail_id', $meta['thumbnail_id'] ?? '');
                            $wpFeaturedImageUrl = $wpFeaturedImageId ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int) $wpFeaturedImageId) : '';
                        @endphp
                        <div class="mb-4 p-3 border rounded bg-light">
                            <h5 class="mb-2">Image Ã  la une WordPress (Featured Image)</h5>
                            <input type="hidden" id="thumbnail_id" name="thumbnail_id" value="{{ $wpFeaturedImageId }}">
                            <div class="d-flex flex-wrap align-items-start gap-3">
                                <div id="wp-featured-preview-wrap" class="border rounded overflow-hidden bg-white" style="width: 180px; height: 120px; display: {{ $wpFeaturedImageUrl ? 'block' : 'none' }};">
                                    <img id="wp-featured-preview" src="{{ $wpFeaturedImageUrl }}" alt="Featured image" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="mb-2 d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="wp-featured-choose-btn">
                                            <i class="bx bx-images"></i> Choisir depuis la mÃ©diathÃ¨que WP
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="wp-featured-upload-btn">
                                            <i class="bx bx-upload"></i> Uploader vers WP
                                        </button>
                                        <input type="file" id="wp_featured_image_file" class="d-none" accept="image/jpeg,image/png,image/webp">
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="wp-featured-remove-btn" {{ $wpFeaturedImageId ? '' : 'disabled' }}>
                                            <i class="bx bx-trash"></i> Supprimer
                                        </button>
                                    </div>
                                    <div class="mb-2" style="max-width: 320px;">
                                        <label for="wp_featured_image_id" class="form-label mb-1">ID Attachment WP</label>
                                        <input type="text" class="form-control form-control-sm" id="wp_featured_image_id" value="{{ $wpFeaturedImageId }}" readonly>
                                    </div>
                                    <small class="text-muted d-block">JPG / PNG / WebP - max 5MB.</small>
                                    <div id="wp-featured-error" class="alert alert-danger mt-2 mb-0 d-none" role="alert"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Section 3 : Galerie Hero (5 images pour la galerie hero) --}}
                        <div class="mb-4 p-3 border rounded bg-light">
                            <h5 class="mb-2">Galerie Hero (5 images)</h5>
                            @php
                                $hero_gallery_ids = old('hero_gallery_ids', isset($meta['hero_gallery_ids']) && $meta['hero_gallery_ids'] !== null ? explode(',', (string) $meta['hero_gallery_ids']) : []);
                                if (!is_array($hero_gallery_ids)) {
                                    $hero_gallery_ids = is_string($hero_gallery_ids) ? explode(',', $hero_gallery_ids) : [];
                                }
                                $hero_gallery_ids = array_filter(array_map('trim', $hero_gallery_ids));
                                $hero_gallery_ids = array_slice($hero_gallery_ids, 0, 5); // Max 5
                                while (count($hero_gallery_ids) < 5) {
                                    $hero_gallery_ids[] = '';
                                }
                            @endphp
                            <input type="hidden" name="hero_gallery_ids" id="hero_gallery_ids" value="{{ implode(',', array_filter($hero_gallery_ids)) }}">
                            <div id="hero-gallery-container" class="row g-3">
                                @for($i = 0; $i < 5; $i++)
                                    @php
                                        $img_id = $hero_gallery_ids[$i] ?? '';
                                        $img_url = $img_id ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int) $img_id) : '';
                                    @endphp
                                    <div class="col-md-6 col-lg-4">
                                        <div class="hero-gallery-item border rounded p-2 bg-white" data-index="{{ $i }}">
                                            <label class="form-label small mb-1">
                                                Image {{ $i === 0 ? 'Principale' : ($i + 1) }}
                                            </label>
                                            <div class="hero-gallery-preview-wrap mb-2" style="width: 100%; height: 120px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; background: #f8f9fa; display: {{ $img_url ? 'block' : 'none' }};">
                                                <img src="{{ $img_url }}" alt="Preview {{ $i + 1 }}" class="hero-gallery-preview" style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                            <div class="hero-gallery-placeholder mb-2" style="width: 100%; height: 120px; border: 2px dashed #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; {{ $img_url ? 'display: none;' : '' }}">
                                                <span class="text-muted small">Aucune image</span>
                                            </div>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <button type="button" class="btn btn-outline-primary btn-sm hero-gallery-upload-btn" data-index="{{ $i }}" style="font-size: 11px;">
                                                    <i class="bx bx-upload"></i> Upload
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm hero-gallery-choose-btn" data-index="{{ $i }}" style="font-size: 11px;">
                                                    <i class="bx bx-images"></i> Choisir
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm hero-gallery-remove-btn" data-index="{{ $i }}" style="font-size: 11px;" {{ !$img_id ? 'disabled' : '' }}>
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" class="hero-gallery-id-input" data-index="{{ $i }}" value="{{ $img_id }}">
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="gallery_ids" class="form-label">Galerie gÃ©nÃ©rale (images supplÃ©mentaires)</label>
                            <input type="text" class="form-control" id="gallery_ids" name="gallery_ids" value="{{ old('gallery_ids', $gallery_csv ?? '') }}" placeholder="14435,14436,14437">
                        </div>
                        
                        <div class="mb-3">
                            <label for="video" class="form-label">URL VidÃ©o</label>
                            <input type="text" class="form-control" id="video" name="video" value="{{ old('video', $meta['video'] ?? '') }}" placeholder="https://www.youtube.com/watch?v=...">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal MÃ©diathÃ¨que WP (choix image hero) --}}
            <div class="modal fade" id="hero-media-modal" tabindex="-1" aria-labelledby="hero-media-modal-label" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="hero-media-modal-label">Choisir une image depuis la mÃ©diathÃ¨que</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <input type="search" class="form-control" id="hero-media-search" placeholder="Rechercher...">
                            </div>
                            <div id="hero-media-results" class="row g-2" style="min-height: 200px;"></div>
                            <div id="hero-media-loading" class="text-center py-4 text-muted d-none">Chargement...</div>
                            <nav id="hero-media-pagination" class="mt-2 d-none"></nav>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="wp-featured-media-modal" tabindex="-1" aria-labelledby="wp-featured-media-modal-label" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="wp-featured-media-modal-label">MÃ©diathÃ¨que WordPress - Image Ã  la une</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <input type="search" class="form-control" id="wp-featured-media-search" placeholder="Rechercher un mÃ©dia...">
                            </div>
                            <div id="wp-featured-media-results" class="row g-3" style="min-height: 220px;"></div>
                            <div id="wp-featured-media-loading" class="text-center py-4 text-muted d-none">Chargement...</div>
                            <nav id="wp-featured-media-pagination" class="mt-3 d-none"></nav>
                        </div>
                    </div>
                </div>
            </div>


