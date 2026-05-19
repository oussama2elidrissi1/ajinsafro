@extends('layouts.admin-v6')
@section('title')
    ParamÃ¨tres gÃ©nÃ©raux
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">ParamÃ¨tres gÃ©nÃ©raux</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">ParamÃ¨tres gÃ©nÃ©raux</li>
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
                                @php
                                    $hasCustomLogo = !empty($settings['brand_logo']) && \App\Models\Setting::storageUrl($settings['brand_logo']);
                                    $logoUrl = $hasCustomLogo ? \App\Models\Setting::storageUrl($settings['brand_logo']) : \App\Models\Setting::brandLogoUrl();
                                @endphp
                                <div class="mb-2">
                                    <img src="{{ $logoUrl }}" alt="Logo" class="img-thumbnail" style="max-height: 60px;">
                                    <span class="text-muted small d-block">{{ $hasCustomLogo ? 'Logo actuel' : 'Logo par dÃ©faut' }}</span>
                                </div>
                                <input class="form-control" type="file" name="brand_logo" id="brand_logo" accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp">
                                <small class="text-muted">Laisser vide pour conserver lâ€™image actuelle. Stockage : storage/app/public/front/brand/</small>
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
                        <h5 class="card-title mb-0">B) Topbar â€“ Contacts</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label for="topbar_phone" class="col-md-3 col-form-label">TÃ©lÃ©phone</label>
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
                        <h5 class="card-title mb-0">C) Hero (page dâ€™accueil publique)</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label for="hero_type" class="col-md-3 col-form-label">Type de fond <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <select class="form-select" name="hero_type" id="hero_type" required>
                                    <option value="image" {{ old('hero_type', $settings['hero_type'] ?? 'image') === 'image' ? 'selected' : '' }}>Image</option>
                                    <option value="video" {{ old('hero_type', $settings['hero_type'] ?? 'image') === 'video' ? 'selected' : '' }}>VidÃ©o</option>
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
                                <small class="text-muted">UtilisÃ©e si type = Image. Stockage : storage/app/public/front/hero/</small>
                            </div>
                        </div>
                        <div class="mb-3 row" id="hero_video_row">
                            <label for="hero_video" class="col-md-3 col-form-label">VidÃ©o hero</label>
                            <div class="col-md-9">
                                @if(!empty($settings['hero_video']))
                                    <p class="text-muted small">VidÃ©o actuelle enregistrÃ©e. TÃ©lÃ©versez un nouveau fichier pour remplacer.</p>
                                @endif
                                <input class="form-control" type="file" name="hero_video" id="hero_video" accept="video/mp4,video/webm,video/ogg">
                                <small class="text-muted">UtilisÃ©e si type = VidÃ©o. mp4, webm, ogg. Stockage : storage/app/public/front/hero/</small>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="hero_overlay_opacity" class="col-md-3 col-form-label">OpacitÃ© overlay (0â€“1) <span class="text-danger">*</span></label>
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

        {{-- D) ParamÃ¨tres des factures --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">D) ParamÃ¨tres des factures</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label for="invoice_header_image" class="col-md-3 col-form-label">En-tÃªte facture</label>
                            <div class="col-md-9">
                                @if(!empty($settings['invoice_header_image_url']))
                                    <div class="mb-2">
                                        <img src="{{ $settings['invoice_header_image_url'] }}" alt="En-tÃªte facture" class="img-thumbnail" style="max-height: 120px;">
                                        <span class="text-muted small d-block">Image actuelle</span>
                                    </div>
                                @endif
                                <input class="form-control" type="file" name="invoice_header_image" id="invoice_header_image" accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp">
                                <small class="text-muted">Image affichÃ©e en haut des factures PDF. Largeur recommandÃ©e : 1200px environ. Stockage : storage/app/public/settings/invoices/</small>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="invoice_footer_image" class="col-md-3 col-form-label">Pied de page facture</label>
                            <div class="col-md-9">
                                @if(!empty($settings['invoice_footer_image_url']))
                                    <div class="mb-2">
                                        <img src="{{ $settings['invoice_footer_image_url'] }}" alt="Pied de page facture" class="img-thumbnail" style="max-height: 120px;">
                                        <span class="text-muted small d-block">Image actuelle</span>
                                    </div>
                                @endif
                                <input class="form-control" type="file" name="invoice_footer_image" id="invoice_footer_image" accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp">
                                <small class="text-muted">Image affichÃ©e en bas des factures PDF. Largeur recommandÃ©e : 1200px environ. Stockage : storage/app/public/settings/invoices/</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- E) Workspace commercial â€” modal dÃ©part --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">E) Workspace commercial â€” modal dÃ©part</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label class="col-md-3 col-form-label">Afficher le rapport du dÃ©part</label>
                            <div class="col-md-9">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="ws_modal_show_departure_report" id="ws_modal_show_departure_report" value="1" {{ old('ws_modal_show_departure_report', $settings['ws_modal_show_departure_report'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ws_modal_show_departure_report">Afficher la section "Rapport du dÃ©part" dans le modal</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-md-3 col-form-label">Commission commerciale</label>
                            <div class="col-md-9">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="ws_modal_show_commission" id="ws_modal_show_commission" value="1" {{ old('ws_modal_show_commission', $settings['ws_modal_show_commission'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ws_modal_show_commission">Afficher la section commission dans le modal</label>
                                </div>
                                <div class="ms-4">
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" name="ws_modal_show_commission_type" id="ws_modal_show_commission_type" value="1" {{ old('ws_modal_show_commission_type', $settings['ws_modal_show_commission_type'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ws_modal_show_commission_type">Afficher le type de commission</label>
                                    </div>
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" name="ws_modal_show_commission_amount" id="ws_modal_show_commission_amount" value="1" {{ old('ws_modal_show_commission_amount', $settings['ws_modal_show_commission_amount'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ws_modal_show_commission_amount">Afficher le montant estimÃ©</label>
                                    </div>
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" name="ws_modal_show_commission_percentage" id="ws_modal_show_commission_percentage" value="1" {{ old('ws_modal_show_commission_percentage', $settings['ws_modal_show_commission_percentage'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ws_modal_show_commission_percentage">Afficher le pourcentage</label>
                                    </div>
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" name="ws_modal_show_commission_fixed" id="ws_modal_show_commission_fixed" value="1" {{ old('ws_modal_show_commission_fixed', $settings['ws_modal_show_commission_fixed'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ws_modal_show_commission_fixed">Afficher la commission fixe</label>
                                    </div>
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" name="ws_modal_show_commission_agent" id="ws_modal_show_commission_agent" value="1" {{ old('ws_modal_show_commission_agent', $settings['ws_modal_show_commission_agent'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ws_modal_show_commission_agent">Afficher l'agent concernÃ©</label>
                                    </div>
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" name="ws_modal_show_commission_branch" id="ws_modal_show_commission_branch" value="1" {{ old('ws_modal_show_commission_branch', $settings['ws_modal_show_commission_branch'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ws_modal_show_commission_branch">Afficher le point de vente</label>
                                    </div>
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" name="ws_modal_show_commission_help" id="ws_modal_show_commission_help" value="1" {{ old('ws_modal_show_commission_help', $settings['ws_modal_show_commission_help'] ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ws_modal_show_commission_help">Afficher le message d'aide</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <button type="submit" class="btn btn-primary waves-effect waves-light">Enregistrer les paramÃ¨tres</button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary waves-effect waves-light ms-2">Annuler</a>
            </div>
        </div>
    </form>
@endsection
@push('scripts')
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

