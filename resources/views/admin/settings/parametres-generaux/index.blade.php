@extends('layouts.master-ajinsafro')
@section('title')
    Paramètres généraux
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Paramètres généraux</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Paramètres généraux</li>
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

    <form action="{{ route('admin.settings.parametres-generaux.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- A) Branding --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">A) Branding</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label for="brand_name" class="col-md-3 col-form-label">Nom de la marque <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="brand_name" id="brand_name" value="{{ old('brand_name', $settings['brand_name'] ?? 'Ajinsafro.ma') }}" required>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="brand_logo" class="col-md-3 col-form-label">Logo (image)</label>
                            <div class="col-md-9">
                                @if(!empty($settings['brand_logo']))
                                    @php $logoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($settings['brand_logo']); @endphp
                                    <div class="mb-2">
                                        <img src="{{ $logoUrl }}" alt="Logo" class="img-thumbnail" style="max-height: 60px;">
                                        <span class="text-muted small d-block">Logo actuel</span>
                                    </div>
                                @endif
                                <input class="form-control" type="file" name="brand_logo" id="brand_logo" accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp">
                                <small class="text-muted">Laisser vide pour conserver l’image actuelle. Stockage : storage/app/public/front/brand/</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- B) Topbar Contacts --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">B) Topbar – Contacts</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label for="topbar_phone" class="col-md-3 col-form-label">Téléphone</label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="topbar_phone" id="topbar_phone" value="{{ old('topbar_phone', $settings['topbar_phone'] ?? '(000) 999 - 656 - 888') }}" placeholder="(000) 999 - 656 - 888">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="topbar_email" class="col-md-3 col-form-label">Email</label>
                            <div class="col-md-9">
                                <input class="form-control" type="email" name="topbar_email" id="topbar_email" value="{{ old('topbar_email', $settings['topbar_email'] ?? 'contact@ajinsafro.ma') }}" placeholder="contact@ajinsafro.ma">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="social_facebook" class="col-md-3 col-form-label">Facebook (URL)</label>
                            <div class="col-md-9">
                                <input class="form-control" type="url" name="social_facebook" id="social_facebook" value="{{ old('social_facebook', $settings['social_facebook'] ?? '') }}" placeholder="https://facebook.com/...">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="social_twitter" class="col-md-3 col-form-label">Twitter (URL)</label>
                            <div class="col-md-9">
                                <input class="form-control" type="url" name="social_twitter" id="social_twitter" value="{{ old('social_twitter', $settings['social_twitter'] ?? '') }}" placeholder="https://twitter.com/...">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="social_instagram" class="col-md-3 col-form-label">Instagram (URL)</label>
                            <div class="col-md-9">
                                <input class="form-control" type="url" name="social_instagram" id="social_instagram" value="{{ old('social_instagram', $settings['social_instagram'] ?? '') }}" placeholder="https://instagram.com/...">
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="social_youtube" class="col-md-3 col-form-label">YouTube (URL)</label>
                            <div class="col-md-9">
                                <input class="form-control" type="url" name="social_youtube" id="social_youtube" value="{{ old('social_youtube', $settings['social_youtube'] ?? '') }}" placeholder="https://youtube.com/...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- C) Hero (Homepage) --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">C) Hero (page d’accueil publique)</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label for="hero_type" class="col-md-3 col-form-label">Type de fond <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <select class="form-select" name="hero_type" id="hero_type" required>
                                    <option value="image" {{ old('hero_type', $settings['hero_type'] ?? 'image') === 'image' ? 'selected' : '' }}>Image</option>
                                    <option value="video" {{ old('hero_type', $settings['hero_type'] ?? 'image') === 'video' ? 'selected' : '' }}>Vidéo</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row" id="hero_image_row">
                            <label for="hero_image" class="col-md-3 col-form-label">Image hero</label>
                            <div class="col-md-9">
                                @if(!empty($settings['hero_image']))
                                    @php $heroImgUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($settings['hero_image']); @endphp
                                    <div class="mb-2">
                                        <img src="{{ $heroImgUrl }}" alt="Hero" class="img-thumbnail" style="max-height: 120px;">
                                        <span class="text-muted small d-block">Image actuelle</span>
                                    </div>
                                @endif
                                <input class="form-control" type="file" name="hero_image" id="hero_image" accept="image/jpeg,image/png,image/gif,image/webp">
                                <small class="text-muted">Utilisée si type = Image. Stockage : storage/app/public/front/hero/</small>
                            </div>
                        </div>
                        <div class="mb-3 row" id="hero_video_row">
                            <label for="hero_video" class="col-md-3 col-form-label">Vidéo hero</label>
                            <div class="col-md-9">
                                @if(!empty($settings['hero_video']))
                                    <p class="text-muted small">Vidéo actuelle enregistrée. Téléversez un nouveau fichier pour remplacer.</p>
                                @endif
                                <input class="form-control" type="file" name="hero_video" id="hero_video" accept="video/mp4,video/webm,video/ogg">
                                <small class="text-muted">Utilisée si type = Vidéo. mp4, webm, ogg. Stockage : storage/app/public/front/hero/</small>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="hero_overlay_opacity" class="col-md-3 col-form-label">Opacité overlay (0–1) <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input class="form-control" type="number" name="hero_overlay_opacity" id="hero_overlay_opacity" value="{{ old('hero_overlay_opacity', $settings['hero_overlay_opacity'] ?? '0.45') }}" step="0.01" min="0" max="1" required>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="hero_title" class="col-md-3 col-form-label">Titre hero <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="hero_title" id="hero_title" value="{{ old('hero_title', $settings['hero_title'] ?? 'Let the journey begin') }}" required>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="hero_subtitle" class="col-md-3 col-form-label">Sous-titre hero</label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="hero_subtitle" id="hero_subtitle" value="{{ old('hero_subtitle', $settings['hero_subtitle'] ?? 'Get the best prices on 2,000,000+ properties, worldwide') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary waves-effect waves-light">Enregistrer les paramètres</button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary waves-effect waves-light ms-2">Annuler</a>
            </div>
        </div>
    </form>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        (function() {
            var heroType = document.getElementById('hero_type');
            var imageRow = document.getElementById('hero_image_row');
            var videoRow = document.getElementById('hero_video_row');
            function toggle() {
                var isImage = heroType.value === 'image';
                imageRow.style.display = isImage ? '' : 'none';
                videoRow.style.display = isImage ? 'none' : '';
            }
            heroType.addEventListener('change', toggle);
            toggle();
        })();
    </script>
@endpush
