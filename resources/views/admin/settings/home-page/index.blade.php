@extends('layouts.master-ajinsafro')
@section('title')
    Home page
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Home page</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Paramètres</a></li>
                        <li class="breadcrumb-item active">Home page</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('admin.settings.home-page.update') }}" method="POST" enctype="multipart/form-data" id="home-page-settings-form">
        @csrf

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Hero</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="hero[type]" id="hero_type" required>
                            <option value="image" {{ old('hero.type', data_get($settings, 'hero.type')) === 'image' ? 'selected' : '' }}>Image</option>
                            <option value="video" {{ old('hero.type', data_get($settings, 'hero.type')) === 'video' ? 'selected' : '' }}>Vidéo</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Titre</label>
                        <input type="text" class="form-control" name="hero[title]" value="{{ old('hero.title', data_get($settings, 'hero.title')) }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Sous-titre</label>
                        <input type="text" class="form-control" name="hero[subtitle]" value="{{ old('hero.subtitle', data_get($settings, 'hero.subtitle')) }}">
                    </div>

                    <div class="col-md-6" id="hero_image_url_wrap">
                        <label class="form-label">Image URL</label>
                        <input type="url" class="form-control" name="hero[image_url]" value="{{ old('hero.image_url', data_get($settings, 'hero.image_url')) }}" placeholder="https://...">
                    </div>
                    <div class="col-md-6" id="hero_image_file_wrap">
                        <label class="form-label">Upload image</label>
                        <input type="file" class="form-control" name="hero[image_file]" accept="image/*">
                    </div>

                    <div class="col-md-6" id="hero_video_url_wrap">
                        <label class="form-label">Vidéo URL (YouTube/Vimeo/mp4)</label>
                        <input type="url" class="form-control" name="hero[video_url]" value="{{ old('hero.video_url', data_get($settings, 'hero.video_url')) }}" placeholder="https://...">
                    </div>
                    <div class="col-md-6" id="hero_video_file_wrap">
                        <label class="form-label">Upload mp4</label>
                        <input type="file" class="form-control" name="hero[video_file]" accept="video/mp4">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">CTA texte</label>
                        <input type="text" class="form-control" name="hero[cta_text]" value="{{ old('hero.cta_text', data_get($settings, 'hero.cta_text')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CTA URL</label>
                        <input type="url" class="form-control" name="hero[cta_url]" value="{{ old('hero.cta_url', data_get($settings, 'hero.cta_url')) }}" placeholder="https://...">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Overlay</label>
                        <input type="range" class="form-range" min="0" max="1" step="0.01" name="hero[overlay]" id="hero_overlay" value="{{ old('hero.overlay', data_get($settings, 'hero.overlay', 0.35)) }}">
                        <small class="text-muted">Valeur: <span id="hero_overlay_value">{{ old('hero.overlay', data_get($settings, 'hero.overlay', 0.35)) }}</span></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Sections</h5></div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sections[search]" value="1" {{ old('sections.search', data_get($settings, 'sections.search')) ? 'checked' : '' }}>
                        <label class="form-check-label">Search</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sections[last_minute]" value="1" {{ old('sections.last_minute', data_get($settings, 'sections.last_minute')) ? 'checked' : '' }}>
                        <label class="form-check-label">Offres dernière minute</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sections[regions]" value="1" {{ old('sections.regions', data_get($settings, 'sections.regions')) ? 'checked' : '' }}>
                        <label class="form-check-label">Destinations</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sections[good_spots]" value="1" {{ old('sections.good_spots', data_get($settings, 'sections.good_spots')) ? 'checked' : '' }}>
                        <label class="form-check-label">Bons coins</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Search</h5></div>
            <div class="card-body">
                <label class="form-label">Shortcode</label>
                <input type="text" class="form-control" name="search[shortcode]" value="{{ old('search.shortcode', data_get($settings, 'search.shortcode', '[traveler_search]')) }}" placeholder="[traveler_search]">
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Offres dernière minute</h5></div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Titre</label>
                    <input type="text" class="form-control" name="last_minute[title]" value="{{ old('last_minute.title', data_get($settings, 'last_minute.title')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nombre d’items</label>
                    <input type="number" class="form-control" min="1" max="20" name="last_minute[count]" value="{{ old('last_minute.count', data_get($settings, 'last_minute.count', 6)) }}" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="last_minute[featured_only]" value="1" {{ old('last_minute.featured_only', data_get($settings, 'last_minute.featured_only')) ? 'checked' : '' }}>
                        <label class="form-check-label">Featured only</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Destinations par région</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-region">Ajouter</button>
            </div>
            <div class="card-body">
                <div id="regions-container" class="vstack gap-3">
                    @foreach(old('regions', data_get($settings, 'regions', [])) as $idx => $region)
                        <div class="border rounded p-3 region-row" data-index="{{ $idx }}">
                            <div class="row g-2">
                                <div class="col-md-4"><input class="form-control" name="regions[{{ $idx }}][title]" value="{{ data_get($region, 'title') }}" placeholder="Titre"></div>
                                <div class="col-md-4"><input class="form-control" name="regions[{{ $idx }}][image_url]" value="{{ data_get($region, 'image_url') }}" placeholder="Image URL"></div>
                                <div class="col-md-3"><input class="form-control" name="regions[{{ $idx }}][link_url]" value="{{ data_get($region, 'link_url') }}" placeholder="Link URL"></div>
                                <div class="col-md-1 d-grid"><button type="button" class="btn btn-outline-danger remove-region">×</button></div>
                                <div class="col-12"><input type="file" class="form-control" name="regions_files[{{ $idx }}]" accept="image/*"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Les bons coins (4 items)</h5></div>
            <div class="card-body vstack gap-3">
                @foreach(old('good_spots', data_get($settings, 'good_spots', [])) as $idx => $spot)
                    @if($idx < 4)
                        <div class="border rounded p-3">
                            <h6 class="mb-2">Item {{ $idx + 1 }}</h6>
                            <div class="row g-2">
                                <div class="col-md-4"><input class="form-control" name="good_spots[{{ $idx }}][title]" value="{{ data_get($spot, 'title') }}" placeholder="Titre"></div>
                                <div class="col-md-4"><input class="form-control" name="good_spots[{{ $idx }}][image_url]" value="{{ data_get($spot, 'image_url') }}" placeholder="Image URL"></div>
                                <div class="col-md-4"><input class="form-control" name="good_spots[{{ $idx }}][link_url]" value="{{ data_get($spot, 'link_url') }}" placeholder="Link URL"></div>
                                <div class="col-12"><input type="file" class="form-control" name="good_spots_files[{{ $idx }}]" accept="image/*"></div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
@endsection

@push('script')
<script>
(function () {
    var heroType = document.getElementById('hero_type');
    var imageWraps = [document.getElementById('hero_image_url_wrap'), document.getElementById('hero_image_file_wrap')];
    var videoWraps = [document.getElementById('hero_video_url_wrap'), document.getElementById('hero_video_file_wrap')];
    var overlay = document.getElementById('hero_overlay');
    var overlayValue = document.getElementById('hero_overlay_value');

    function toggleHeroFields() {
        var isImage = heroType.value === 'image';
        imageWraps.forEach(function (el) { if (el) el.style.display = isImage ? '' : 'none'; });
        videoWraps.forEach(function (el) { if (el) el.style.display = isImage ? 'none' : ''; });
    }

    function syncOverlayValue() {
        if (overlay && overlayValue) {
            overlayValue.textContent = overlay.value;
        }
    }

    heroType.addEventListener('change', toggleHeroFields);
    if (overlay) overlay.addEventListener('input', syncOverlayValue);
    toggleHeroFields();
    syncOverlayValue();

    var container = document.getElementById('regions-container');
    var addBtn = document.getElementById('add-region');

    function nextIndex() {
        return container.querySelectorAll('.region-row').length;
    }

    function regionRowTemplate(index) {
        return '' +
            '<div class="border rounded p-3 region-row" data-index="' + index + '">' +
            '  <div class="row g-2">' +
            '    <div class="col-md-4"><input class="form-control" name="regions[' + index + '][title]" placeholder="Titre"></div>' +
            '    <div class="col-md-4"><input class="form-control" name="regions[' + index + '][image_url]" placeholder="Image URL"></div>' +
            '    <div class="col-md-3"><input class="form-control" name="regions[' + index + '][link_url]" placeholder="Link URL"></div>' +
            '    <div class="col-md-1 d-grid"><button type="button" class="btn btn-outline-danger remove-region">×</button></div>' +
            '    <div class="col-12"><input type="file" class="form-control" name="regions_files[' + index + ']" accept="image/*"></div>' +
            '  </div>' +
            '</div>';
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            var index = nextIndex();
            container.insertAdjacentHTML('beforeend', regionRowTemplate(index));
        });
    }

    if (container) {
        container.addEventListener('click', function (event) {
            if (event.target.classList.contains('remove-region')) {
                var row = event.target.closest('.region-row');
                if (row) row.remove();
            }
        });
    }
})();
</script>
@endpush
