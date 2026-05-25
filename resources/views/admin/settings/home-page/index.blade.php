@extends('layouts.admin-v6')
@section('title') Home page @endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Home page</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Param?tres</a></li>
                        <li class="breadcrumb-item active">Home page ilyass</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- â?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ð TABS â?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ð --}}
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ ($tab ?? 'header') === 'header' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-header" role="tab">Header</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ ($tab ?? 'header') === 'content' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-content" role="tab">Contenu</a>
        </li>
    </ul>

    <div class="tab-content">

    {{-- â?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ð
         TAB 1 ? HEADER (topbar + navbar)
         â?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ð --}}
    <div class="tab-pane fade {{ ($tab ?? 'header') === 'header' ? 'show active' : '' }}" id="tab-header" role="tabpanel">
        <form action="{{ url('/admin/settings/home-page/header') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Global enabled --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Header global</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="header[enabled]" value="1" id="hdr_enabled"
                               {{ old('header.enabled', data_get($header, 'enabled')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hdr_enabled">Activer le header personnalis?</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="header[show_header_sitewide]" value="1" id="hdr_sitewide"
                               {{ old('header.show_header_sitewide', data_get($header, 'show_header_sitewide')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hdr_sitewide">Appliquer le header ? toutes les pages WordPress</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="header[show_footer_sitewide]" value="1" id="hdr_footer_sitewide"
                               {{ old('header.show_footer_sitewide', data_get($header, 'show_footer_sitewide', true)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hdr_footer_sitewide">Appliquer le footer personnalis? ? toutes les pages</label>
                    </div>
                    <p class="small text-muted mt-1 mb-0">Le header et le footer personnalis?s remplacent ceux du th?me WordPress sur toutes les pages.</p>
                </div>
            </div>

            {{-- Topbar --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Topbar</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="header[topbar_enabled]" value="1" id="hdr_topbar"
                               {{ old('header.topbar_enabled', data_get($header, 'topbar_enabled')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hdr_topbar">Afficher la topbar</label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">T?l?phone</label>
                            <input type="text" class="form-control" name="header[phone]"
                                   value="{{ old('header.phone', data_get($header, 'phone')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="header[email]"
                                   value="{{ old('header.email', data_get($header, 'email')) }}">
                        </div>
                    </div>
                    <h6 class="mt-3 mb-2">R?seaux sociaux</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Facebook</label>
                            <input type="url" class="form-control" name="header[socials][facebook]"
                                   value="{{ old('header.socials.facebook', data_get($header, 'socials.facebook')) }}" placeholder="https://...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Twitter / X</label>
                            <input type="url" class="form-control" name="header[socials][twitter]"
                                   value="{{ old('header.socials.twitter', data_get($header, 'socials.twitter')) }}" placeholder="https://...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">YouTube</label>
                            <input type="url" class="form-control" name="header[socials][youtube]"
                                   value="{{ old('header.socials.youtube', data_get($header, 'socials.youtube')) }}" placeholder="https://...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Instagram</label>
                            <input type="url" class="form-control" name="header[socials][instagram]"
                                   value="{{ old('header.socials.instagram', data_get($header, 'socials.instagram')) }}" placeholder="https://...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">LinkedIn</label>
                            <input type="url" class="form-control" name="header[socials][linkedin]"
                                   value="{{ old('header.socials.linkedin', data_get($header, 'socials.linkedin')) }}" placeholder="https://...">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Navbar --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Navbar</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="header[navbar_enabled]" value="1" id="hdr_navbar"
                               {{ old('header.navbar_enabled', data_get($header, 'navbar_enabled')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hdr_navbar">Afficher la navbar</label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Logo URL</label>
                            <input type="url" class="form-control" name="header[logo_url]"
                                   value="{{ old('header.logo_url', data_get($header, 'logo_url')) }}" placeholder="https://...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload logo</label>
                            <input type="file" class="form-control" name="header[logo_file]" accept="image/*">
                        </div>
                        @if(data_get($header, 'logo_url'))
                            <div class="col-12">
                                <img src="{{ data_get($header, 'logo_url') }}" alt="Logo" style="max-height:50px">
                            </div>
                        @endif
                    </div>

                    <hr>
                    <h6>Authentification</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="header[show_auth_links]" value="1" id="hdr_auth"
                                       {{ old('header.show_auth_links', data_get($header, 'show_auth_links')) ? 'checked' : '' }}>
                                <label class="form-check-label" for="hdr_auth">Afficher Login / Sign Up</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Login URL</label>
                            <input type="text" class="form-control" name="header[login_url]"
                                   value="{{ old('header.login_url', data_get($header, 'login_url', '/login')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sign Up URL</label>
                            <input type="text" class="form-control" name="header[signup_url]"
                                   value="{{ old('header.signup_url', data_get($header, 'signup_url', '/register')) }}">
                        </div>
                    </div>

                    <hr>
                    <h6>Menu</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Source du menu</label>
                            <select class="form-select" name="header[menu_source]" id="hdr_menu_source">
                                <option value="wp_menu" {{ old('header.menu_source', data_get($header, 'menu_source')) === 'wp_menu' ? 'selected' : '' }}>Menu WordPress</option>
                                <option value="laravel_links" {{ old('header.menu_source', data_get($header, 'menu_source')) === 'laravel_links' ? 'selected' : '' }}>Liens manuels</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="hdr_wp_menu_wrap">
                            <label class="form-label">Emplacement WP</label>
                            <input type="text" class="form-control" name="header[wp_menu_location]"
                                   value="{{ old('header.wp_menu_location', data_get($header, 'wp_menu_location', 'primary')) }}" placeholder="primary">
                        </div>
                    </div>

                    {{-- Manual links repeater with sub-menus and drag/drop ordering --}}
                    <div id="hdr_links_wrap" class="mt-3" style="display:none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0">Liens du menu</label>
                            <small class="text-muted"><i class="fas fa-arrows-alt"></i> Utilisez les fl?ches pour r?organiser l'ordre</small>
                        </div>
                        <div id="hdr-links-container" class="vstack gap-3">
                            @foreach(old('header.links', data_get($header, 'links', [])) as $li => $link)
                                <div class="border rounded p-3 hdr-link-row bg-light" data-index="{{ $li }}">
                                    <input type="hidden" name="header[links][{{ $li }}][order]" class="hdr-link-order" value="{{ data_get($link, 'order', $li + 1) }}">
                                    <div class="row g-2 align-items-center mb-2">
                                        {{-- Move buttons --}}
                                        <div class="col-auto d-flex flex-column gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-secondary hdr-move-up py-0 px-1" title="Monter"><i class="fas fa-chevron-up"></i></button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary hdr-move-down py-0 px-1" title="Descendre"><i class="fas fa-chevron-down"></i></button>
                                        </div>
                                        {{-- Order display --}}
                                        <div class="col-auto text-center" style="min-width:40px">
                                            <span class="badge bg-primary hdr-order-display">{{ data_get($link, 'order', $li + 1) }}</span>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Texte</label>
                                            <input class="form-control form-control-sm" name="header[links][{{ $li }}][label]" value="{{ data_get($link, 'label') }}" placeholder="Ex: HOTEL">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">URL</label>
                                            <input class="form-control form-control-sm" name="header[links][{{ $li }}][url]" value="{{ data_get($link, 'url') }}" placeholder="Ex: /hotel">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Ic?ne FA</label>
                                            <input class="form-control form-control-sm" name="header[links][{{ $li }}][icon]" value="{{ data_get($link, 'icon') }}" placeholder="fas fa-hotel">
                                        </div>
                                        <div class="col-auto d-flex align-items-end gap-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="header[links][{{ $li }}][active]" value="1" {{ data_get($link, 'active') ? 'checked' : '' }}>
                                                <label class="form-check-label small">Actif</label>
                                            </div>
                                        </div>
                                        <div class="col-auto d-flex align-items-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary hdr-add-child">+ Sous-menu</button>
                                        </div>
                                        <div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-link">?</button></div>
                                    </div>
                                    {{-- Sub-menus with ordering --}}
                                    <div class="hdr-children-list ms-4 ps-3 border-start border-2 border-primary">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted fw-semibold">Sous-menus</small>
                                        </div>
                                        @foreach(data_get($link, 'children', []) as $ci => $child)
                                            <div class="row g-2 align-items-center mb-1 hdr-child-row bg-white rounded p-1" data-child-index="{{ $ci }}">
                                                <input type="hidden" name="header[links][{{ $li }}][children][{{ $ci }}][order]" class="hdr-child-order" value="{{ data_get($child, 'order', $ci + 1) }}">
                                                <div class="col-auto d-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary hdr-child-move-up py-0 px-1" title="Monter"><i class="fas fa-chevron-up fa-xs"></i></button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary hdr-child-move-down py-0 px-1" title="Descendre"><i class="fas fa-chevron-down fa-xs"></i></button>
                                                </div>
                                                <div class="col-auto"><span class="badge bg-secondary hdr-child-order-display">{{ data_get($child, 'order', $ci + 1) }}</span></div>
                                                <div class="col-3"><input class="form-control form-control-sm" name="header[links][{{ $li }}][children][{{ $ci }}][label]" value="{{ data_get($child, 'label') }}" placeholder="Label"></div>
                                                <div class="col-3"><input class="form-control form-control-sm" name="header[links][{{ $li }}][children][{{ $ci }}][url]" value="{{ data_get($child, 'url') }}" placeholder="URL"></div>
                                                <div class="col-2"><input class="form-control form-control-sm" name="header[links][{{ $li }}][children][{{ $ci }}][icon]" value="{{ data_get($child, 'icon', '') }}" placeholder="Ic?ne FA"></div>
                                                <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-child py-0 px-1">?</button></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="hdr-add-link"><i class="fas fa-plus me-1"></i>Ajouter un lien</button>
                    </div>
                </div>
            </div>

            {{-- Low Cost Button --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Bouton "Formule Low Cost"</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="header[lowcost_enabled]" value="1" id="hdr_lowcost"
                               {{ old('header.lowcost_enabled', data_get($header, 'lowcost_enabled', true)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hdr_lowcost">Afficher le bouton "Formule Low Cost"</label>
                    </div>
                    <p class="small text-muted mb-3">Ce bouton s'affiche ? droite de la navbar avec un effet de gradient orange/rouge et une animation.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Texte du bouton</label>
                            <input type="text" class="form-control" name="header[lowcost_text]"
                                   value="{{ old('header.lowcost_text', data_get($header, 'lowcost_text', 'Formule low cost')) }}" placeholder="Formule low cost">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">URL du bouton</label>
                            <input type="text" class="form-control" name="header[lowcost_url]"
                                   value="{{ old('header.lowcost_url', data_get($header, 'lowcost_url', '#')) }}" placeholder="/low-cost">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mb-4">Enregistrer le Header</button>
        </form>
    </div>

    {{-- â?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ð
         TAB 2 ? CONTENU (hero, sections, regions?)
         â?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ðâ?Ð --}}
    <div class="tab-pane fade {{ ($tab ?? 'header') === 'content' ? 'show active' : '' }}" id="tab-content" role="tabpanel">
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
                                <option value="video" {{ old('hero.type', data_get($settings, 'hero.type')) === 'video' ? 'selected' : '' }}>Vid?o</option>
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
                            <label class="form-label">Vid?o URL (YouTube/Vimeo/mp4)</label>
                            <input type="url" class="form-control" name="hero[video_url]" value="{{ old('hero.video_url', data_get($settings, 'hero.video_url')) }}" placeholder="https://...">
                        </div>
                        <div class="col-md-6" id="hero_video_file_wrap">
                            <label class="form-label">Upload mp4</label>
                            <input type="file" class="form-control" name="hero_video_file" accept="video/mp4">
                            <small class="text-muted d-block mt-1">Max 50MB. Si l'upload ?choue, utilisez un lien vid?o.</small>
                            @error('hero_video_file') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
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
                            <input type="range" class="form-range" min="0" max="1" step="0.01" name="hero[overlay]" id="hero_overlay"
                                   value="{{ old('hero.overlay', data_get($settings, 'hero.overlay', 0.35)) }}">
                            <small class="text-muted">Valeur: <span id="hero_overlay_value">{{ old('hero.overlay', data_get($settings, 'hero.overlay', 0.35)) }}</span></small>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $sectionLabels = [
                    'search' => 'Search',
                    'last_minute' => 'Tendances du moment',
                    'accommodations' => 'S?jours uniques',
                    'holiday_theme' => 'Voyages par th?me',
                    'regions' => 'Destinations',
                    'good_spots' => 'Bons coins',
                    'whatsapp_banner' => 'Banni?re WhatsApp',
                    'cruises' => 'Croisi?res',
                    'newsletter' => 'Newsletter',
                ];
                $sectionOrder = old('section_order', data_get($settings, 'section_order', ['last_minute', 'accommodations', 'holiday_theme', 'regions', 'good_spots', 'whatsapp_banner', 'cruises', 'newsletter']));
                $sectionOrder = is_array($sectionOrder) ? $sectionOrder : [];
                $customSections = old('custom_sections', data_get($settings, 'custom_sections', []));
                $customSections = is_array($customSections) ? $customSections : [];
            @endphp
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="card-title mb-0">Ordre et visibilit? des sections</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm" id="section-add-builtin" style="width:auto;">
                            <option value="">Ajouter une section?</option>
                            @foreach($sectionLabels as $sKey => $sLabel)
                                @if(!in_array($sKey, $sectionOrder))
                                    <option value="{{ $sKey }}">{{ $sLabel }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="section-add-custom">Section personnalis?e</button>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">R?ordonnez les sections avec les fl?ches. D?cochez pour masquer une section sur la page d'accueil.</p>
                    <div id="section-order-container" class="vstack gap-2">
                        @foreach($sectionOrder as $idx => $secKey)
                            @if(str_starts_with((string)$secKey, 'custom_'))
                                @php $custom = $customSections[$secKey] ?? ['title' => '', 'content' => '']; @endphp
                                <div class="border rounded p-3 section-order-row bg-light" data-section-key="{{ $secKey }}">
                                    <input type="hidden" name="section_order[]" value="{{ $secKey }}">
                                    <div class="d-flex align-items-start gap-2 flex-wrap">
                                        <div class="d-flex flex-column gap-0">
                                            <button type="button" class="btn btn-sm btn-outline-secondary section-move-up" title="Monter">?</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary section-move-down" title="Descendre">?</button>
                                        </div>
                                        <div class="form-check form-switch align-self-center">
                                            <input class="form-check-input" type="checkbox" name="sections[{{ $secKey }}]" value="1"
                                                   {{ old("sections.$secKey", data_get($settings, "sections.$secKey", true)) ? 'checked' : '' }}>
                                            <label class="form-check-label">Activer</label>
                                        </div>
                                        <div class="flex-grow-1">
                                            <label class="form-label small mb-0">Section personnalis?e ? Titre</label>
                                            <input type="text" class="form-control form-control-sm" name="custom_sections[{{ $secKey }}][title]" value="{{ data_get($custom, 'title') }}" placeholder="Titre de la section">
                                            <label class="form-label small mb-0 mt-1">Contenu (HTML / shortcodes)</label>
                                            <textarea class="form-control form-control-sm" name="custom_sections[{{ $secKey }}][content]" rows="3" placeholder="<p>...</p> ou [shortcode]">{{ data_get($custom, 'content') }}</textarea>
                                        </div>
                                        <div class="align-self-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger section-remove">?</button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="border rounded p-2 section-order-row d-flex align-items-center gap-2" data-section-key="{{ $secKey }}">
                                    <input type="hidden" name="section_order[]" value="{{ $secKey }}">
                                    <div class="d-flex flex-column gap-0">
                                        <button type="button" class="btn btn-sm btn-outline-secondary section-move-up" title="Monter">?</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary section-move-down" title="Descendre">?</button>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="sections[{{ $secKey }}]" value="1"
                                               {{ old("sections.$secKey", data_get($settings, "sections.$secKey", true)) ? 'checked' : '' }}>
                                        <label class="form-check-label">{{ $sectionLabels[$secKey] ?? $secKey }}</label>
                                    </div>
                                    <div class="ms-auto">
                                        <button type="button" class="btn btn-sm btn-outline-secondary section-remove" title="Retirer de la liste">?</button>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Search</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="sections[search]" value="1"
                               {{ old('sections.search', data_get($settings, 'sections.search', true)) ? 'checked' : '' }}>
                        <label class="form-check-label">Afficher la barre de recherche dans le hero</label>
                    </div>
                    <label class="form-label">Shortcode</label>
                    <input type="text" class="form-control" name="search[shortcode]" value="{{ old('search.shortcode', data_get($settings, 'search.shortcode', '[traveler_search]')) }}">
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Cap sur les tendances du moment</h5></div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Titre</label>
                        <input type="text" class="form-control" name="last_minute[title]" value="{{ old('last_minute.title', data_get($settings, 'last_minute.title', 'Cap sur les tendances du moment')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nombre d'items</label>
                        <input type="number" class="form-control" min="1" max="20" name="last_minute[count]" value="{{ old('last_minute.count', data_get($settings, 'last_minute.count', 4)) }}" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="last_minute[featured_only]" value="1"
                                   {{ old('last_minute.featured_only', data_get($settings, 'last_minute.featured_only')) ? 'checked' : '' }}>
                            <label class="form-check-label">Featured only</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Accommodations --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">D?couvrez des s?jours uniques</h5></div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Titre</label>
                        <input type="text" class="form-control" name="accommodations[title]" value="{{ old('accommodations.title', data_get($settings, 'accommodations.title', 'D?couvrez des s?jours uniques')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nombre d'items</label>
                        <input type="number" class="form-control" min="1" max="20" name="accommodations[count]" value="{{ old('accommodations.count', data_get($settings, 'accommodations.count', 4)) }}">
                    </div>
                    <div class="col-12">
                        <p class="text-muted small mb-0">Affiche les h?tels et locations (post types: st_hotel, st_rental) les plus r?cents.</p>
                    </div>
                </div>
            </div>

            {{-- Voyages par th?me --}}
            @php
                $holidayTheme = old('holiday_theme', data_get($settings, 'holiday_theme', []));
                $holidayItems = data_get($holidayTheme, 'items', []);
                $holidayItems = is_array($holidayItems) ? $holidayItems : [];
            @endphp
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Voyages par th?me</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="holiday-add-item">Ajouter une carte</button>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="holiday_theme[enabled]" value="1" id="holiday_enabled"
                               {{ old('holiday_theme.enabled', data_get($holidayTheme, 'enabled', true)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="holiday_enabled">Activer la section Voyages par th?me</label>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Sur-titre</label>
                            <input type="text" class="form-control" name="holiday_theme[eyebrow]" value="{{ old('holiday_theme.eyebrow', data_get($holidayTheme, 'eyebrow', 'Voyages par th?me')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Titre ligne 1</label>
                            <input type="text" class="form-control" name="holiday_theme[title_line_1]" value="{{ old('holiday_theme.title_line_1', data_get($holidayTheme, 'title_line_1')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Titre ligne 2</label>
                            <input type="text" class="form-control" name="holiday_theme[title_line_2]" value="{{ old('holiday_theme.title_line_2', data_get($holidayTheme, 'title_line_2')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Titre ligne 3</label>
                            <input type="text" class="form-control" name="holiday_theme[title_line_3]" value="{{ old('holiday_theme.title_line_3', data_get($holidayTheme, 'title_line_3')) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Sous-titre</label>
                            <input type="text" class="form-control" name="holiday_theme[subtitle]" value="{{ old('holiday_theme.subtitle', data_get($holidayTheme, 'subtitle')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image gauche URL</label>
                            <input type="text" class="form-control" name="holiday_theme[left_image_url]" value="{{ old('holiday_theme.left_image_url', data_get($holidayTheme, 'left_image_url')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload image gauche</label>
                            <input type="file" class="form-control" name="holiday_theme_left_image_file" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image d?co URL</label>
                            <input type="text" class="form-control" name="holiday_theme[deco_image_url]" value="{{ old('holiday_theme.deco_image_url', data_get($holidayTheme, 'deco_image_url')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload image d?co</label>
                            <input type="file" class="form-control" name="holiday_theme_deco_image_file" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Texte CTA</label>
                            <input type="text" class="form-control" name="holiday_theme[button_text]" value="{{ old('holiday_theme.button_text', data_get($holidayTheme, 'button_text')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">URL CTA</label>
                            <input type="text" class="form-control" name="holiday_theme[button_url]" value="{{ old('holiday_theme.button_url', data_get($holidayTheme, 'button_url')) }}">
                        </div>
                    </div>

                    <h6 class="mb-2">Cartes voyages par th?me</h6>
                    <div id="holiday-items-container" class="vstack gap-2">
                        @foreach($holidayItems as $idx => $item)
                            <div class="border rounded p-2 holiday-row" data-index="{{ $idx }}">
                                <div class="row g-2 align-items-center">
                                    <div class="col-auto d-flex flex-column gap-0">
                                        <button type="button" class="btn btn-sm btn-outline-secondary holiday-move-up" title="Monter">?</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary holiday-move-down" title="Descendre">?</button>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Titre</label>
                                        <input type="text" class="form-control form-control-sm" name="holiday_theme[items][{{ $idx }}][title]" value="{{ data_get($item, 'title') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Badge</label>
                                        <input type="text" class="form-control form-control-sm" name="holiday_theme[items][{{ $idx }}][badge]" value="{{ data_get($item, 'badge') }}" placeholder="Nouveau">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Description</label>
                                        <input type="text" class="form-control form-control-sm" name="holiday_theme[items][{{ $idx }}][description]" value="{{ data_get($item, 'description') }}" placeholder="Texte court">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Image URL</label>
                                        <input type="text" class="form-control form-control-sm" name="holiday_theme[items][{{ $idx }}][image_url]" value="{{ data_get($item, 'image_url') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Upload</label>
                                        <input type="file" class="form-control form-control-sm" name="holiday_theme_item_files[{{ $idx }}]" accept="image/*">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Btn texte</label>
                                        <input type="text" class="form-control form-control-sm" name="holiday_theme[items][{{ $idx }}][button_text]" value="{{ data_get($item, 'button_text', 'Voir plus') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Btn URL</label>
                                        <input type="text" class="form-control form-control-sm" name="holiday_theme[items][{{ $idx }}][button_url]" value="{{ data_get($item, 'button_url', '#') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Tags</label>
                                        <input type="text" class="form-control form-control-sm" name="holiday_theme[items][{{ $idx }}][tags]" value="{{ is_array(data_get($item, 'tags')) ? implode(', ', data_get($item, 'tags', [])) : data_get($item, 'tags') }}" placeholder="plage, famille, luxe">
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" name="holiday_theme[items][{{ $idx }}][active]" value="1" {{ data_get($item, 'active', true) ? 'checked' : '' }}>
                                            <label class="form-check-label small">Actif</label>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <input type="hidden" class="holiday-order" name="holiday_theme[items][{{ $idx }}][order]" value="{{ data_get($item, 'order', $idx) }}">
                                        <button type="button" class="btn btn-sm btn-outline-danger holiday-remove mt-4">?</button>
                                    </div>
                                    <div class="col-12">
                                        <div class="holiday-preview border rounded p-2 d-flex align-items-center gap-2">
                                            <div class="holiday-preview__img" style="width:64px;height:44px;border-radius:8px;background:#e7edf5 center/cover no-repeat; background-image:url('{{ e((string) data_get($item, 'image_url', '')) }}');"></div>
                                            <div>
                                                <div class="holiday-preview__title fw-bold small">{{ data_get($item, 'title', 'Titre') }}</div>
                                                <div class="holiday-preview__meta text-muted small">{{ data_get($item, 'badge', '') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Destinations par r?gion</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="dbr-add-item">Ajouter une r?gion</button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Grille 2?4 c?t? WordPress. Label obligatoire ; image et lien optionnels.</p>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="destinations_by_region[enabled]" value="1" id="dbr-enabled"
                               {{ old('destinations_by_region.enabled', data_get($destinationsByRegion, 'enabled', true)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="dbr-enabled">Activer la section Destinations par r?gion</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre de la section</label>
                        <input type="text" class="form-control" name="destinations_by_region[title]" value="{{ old('destinations_by_region.title', data_get($destinationsByRegion, 'title', 'Destinations par r?gion')) }}" placeholder="Destinations par r?gion">
                    </div>
                    <div id="dbr-items-container" class="vstack gap-2">
                        @php
                            $dbrItems = old('destinations_by_region.items', data_get($destinationsByRegion, 'items', []));
                            $dbrItems = is_array($dbrItems) ? $dbrItems : [];
                        @endphp
                        @foreach($dbrItems as $idx => $item)
                            <div class="border rounded p-2 dbr-row align-items-center" data-index="{{ $idx }}">
                                <div class="row g-2 align-items-center">
                                    <div class="col-auto d-flex flex-column gap-0">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dbr-move-up" title="Monter">?</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary dbr-move-down" title="Descendre">?</button>
                                    </div>
                                    <div class="col"><input type="hidden" name="destinations_by_region[items][{{ $idx }}][order]" class="dbr-order" value="{{ data_get($item, 'order', $idx + 1) }}"><label class="form-label small mb-0">Ordre</label><span class="dbr-order-display">{{ data_get($item, 'order', $idx + 1) }}</span></div>
                                    <div class="col"><label class="form-label small mb-0">Label <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][{{ $idx }}][label]" value="{{ data_get($item, 'label') }}" placeholder="Ex: CAP NORD" required></div>
                                    <div class="col"><label class="form-label small mb-0">Image URL</label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][{{ $idx }}][image_url]" value="{{ data_get($item, 'image_url') }}" placeholder="https://..."></div>
                                    <div class="col-auto"><label class="form-label small mb-0">Choisir</label><input type="file" class="form-control form-control-sm dbr-file" name="destinations_by_region_files[{{ $idx }}]" accept="image/*" data-index="{{ $idx }}"></div>
                                    <div class="col"><label class="form-label small mb-0">Lien URL</label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][{{ $idx }}][link_url]" value="{{ data_get($item, 'link_url') }}" placeholder="https://..."></div>
                                    <div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger dbr-remove">?</button></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Les bons coins sur votre destination</h5></div>
                <div class="card-body vstack gap-3">
                    <div class="mb-3">
                        <label class="form-label">Titre de la section</label>
                        <input type="text" class="form-control" name="good_spots_title" value="{{ old('good_spots_title', data_get($settings, 'good_spots_title', 'Les bons coins sur votre destination')) }}">
                    </div>
                    @foreach(old('good_spots', data_get($settings, 'good_spots', [])) as $idx => $spot)
                        @if($idx < 4)
                        <div class="border rounded p-3">
                            <h6 class="mb-2">Item {{ $idx + 1 }}</h6>
                            <div class="row g-2">
                                <div class="col-md-3"><input class="form-control" name="good_spots[{{ $idx }}][title]" value="{{ data_get($spot, 'title') }}" placeholder="Titre"></div>
                                <div class="col-md-3"><input class="form-control" name="good_spots[{{ $idx }}][subtitle]" value="{{ data_get($spot, 'subtitle') }}" placeholder="Sous-titre"></div>
                                <div class="col-md-3"><input class="form-control" name="good_spots[{{ $idx }}][icon]" value="{{ data_get($spot, 'icon') }}" placeholder="Ic?ne FA (ex: fas fa-utensils)"></div>
                                <div class="col-md-3"><input class="form-control" name="good_spots[{{ $idx }}][link_url]" value="{{ data_get($spot, 'link_url') }}" placeholder="Link URL"></div>
                                <div class="col-md-6"><input class="form-control" name="good_spots[{{ $idx }}][image_url]" value="{{ data_get($spot, 'image_url') }}" placeholder="Image URL"></div>
                                <div class="col-md-6"><input type="file" class="form-control" name="good_spots_files[{{ $idx }}]" accept="image/*"></div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>

            @php
                $accordionSlider = old('accordion_slider', data_get($settings, 'accordion_slider', []));
                $accordionSlides = data_get($accordionSlider, 'slides', []);
                $accordionSlides = is_array($accordionSlides) ? $accordionSlides : [];
            @endphp
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Accordion Slider / Promo Slider</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="accordion-slider-add-item">Ajouter un slide</button>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="accordion_slider[enabled]" value="1" id="accordion_slider_enabled"
                                       {{ old('accordion_slider.enabled', data_get($accordionSlider, 'enabled', true)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="accordion_slider_enabled">Activer la section accordéon</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="accordion_slider[autoplay]" value="1" id="accordion_slider_autoplay"
                                       {{ old('accordion_slider.autoplay', data_get($accordionSlider, 'autoplay', true)) ? 'checked' : '' }}>
                                <label class="form-check-label" for="accordion_slider_autoplay">Activer l'autoplay</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vitesse autoplay (ms)</label>
                            <input type="number" class="form-control" min="2000" step="500" name="accordion_slider[autoplay_speed]"
                                   value="{{ old('accordion_slider.autoplay_speed', data_get($accordionSlider, 'autoplay_speed', 5000)) }}">
                        </div>
                    </div>
                    <p class="text-muted small mb-3">Le design et le comportement front restent identiques. Les slides ci-dessous remplacent seulement les contenus dynamiques.</p>
                    <div id="accordion-slider-items-container" class="vstack gap-3">
                        @foreach($accordionSlides as $idx => $slide)
                            <div class="border rounded p-3 accordion-slider-row" data-index="{{ $idx }}">
                                <div class="row g-2 align-items-start">
                                    <div class="col-auto d-flex flex-column gap-0">
                                        <button type="button" class="btn btn-sm btn-outline-secondary accordion-slider-move-up" title="Monter">'</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary accordion-slider-move-down" title="Descendre">?</button>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-0">Titre vertical <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" name="accordion_slider[slides][{{ $idx }}][title]" value="{{ data_get($slide, 'title') }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-0">Sous-titre</label>
                                        <input type="text" class="form-control form-control-sm" name="accordion_slider[slides][{{ $idx }}][subtitle]" value="{{ data_get($slide, 'subtitle') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-0">Image URL</label>
                                        <input type="text" class="form-control form-control-sm accordion-slider-image-input" name="accordion_slider[slides][{{ $idx }}][image]" value="{{ data_get($slide, 'image') }}" placeholder="https://...">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-0">Upload image</label>
                                        <input type="file" class="form-control form-control-sm" name="accordion_slider_files[{{ $idx }}]" accept="image/*">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-0">Lien</label>
                                        <input type="text" class="form-control form-control-sm" name="accordion_slider[slides][{{ $idx }}][link]" value="{{ data_get($slide, 'link', '#') }}" placeholder="https://...">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Texte bouton</label>
                                        <input type="text" class="form-control form-control-sm" name="accordion_slider[slides][{{ $idx }}][button_text]" value="{{ data_get($slide, 'button_text') }}" placeholder="S'inscrire">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-0">Style bouton</label>
                                        <select class="form-select form-select-sm" name="accordion_slider[slides][{{ $idx }}][button_style]">
                                            @php $buttonStyle = data_get($slide, 'button_style', 'orange'); @endphp
                                            <option value="orange" {{ $buttonStyle === 'orange' ? 'selected' : '' }}>Orange</option>
                                            <option value="white" {{ $buttonStyle === 'white' ? 'selected' : '' }}>White</option>
                                            <option value="white-arabic" {{ $buttonStyle === 'white-arabic' ? 'selected' : '' }}>White Arabic</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-0">Overlay / gradient</label>
                                        <input type="text" class="form-control form-control-sm" name="accordion_slider[slides][{{ $idx }}][overlay_color]" value="{{ data_get($slide, 'overlay_color') }}" placeholder="linear-gradient(...)">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small mb-0">Ordre</label>
                                        <input type="hidden" class="accordion-slider-order" name="accordion_slider[slides][{{ $idx }}][order]" value="{{ data_get($slide, 'order', $idx + 1) }}">
                                        <div class="form-control form-control-sm accordion-slider-order-display">{{ data_get($slide, 'order', $idx + 1) }}</div>
                                    </div>
                                    <div class="col-auto d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger accordion-slider-remove">?</button>
                                    </div>
                                    <div class="col-12">
                                        <div class="promo-preview">
                                            <div class="promo-preview__img accordion-slider-preview-img" style="background-image:url('{{ e((string) data_get($slide, 'image', '')) }}');"></div>
                                            <div>
                                                <div class="promo-preview__title accordion-slider-preview-title">{{ data_get($slide, 'title', 'Titre') }}</div>
                                                <div class="promo-preview__subtitle accordion-slider-preview-subtitle">{{ data_get($slide, 'button_text', '') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- WhatsApp Banner --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Banni?re WhatsApp</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="whatsapp_banner[enabled]" value="1" id="whatsapp_enabled"
                               {{ old('whatsapp_banner.enabled', data_get($settings, 'whatsapp_banner.enabled')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="whatsapp_enabled">Activer la banni?re WhatsApp</label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Titre</label>
                            <input type="text" class="form-control" name="whatsapp_banner[title]" value="{{ old('whatsapp_banner.title', data_get($settings, 'whatsapp_banner.title', 'Rejoignez notre cha?ne WhatsApp pour suivre nos actualit?s voyage')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Texte court (description)</label>
                            <input type="text" class="form-control" name="whatsapp_banner[subtitle]" value="{{ old('whatsapp_banner.subtitle', data_get($settings, 'whatsapp_banner.subtitle', 'Restez inform? avec AjinSafro')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Segments meta conserv?s pour compatibilit?</label>
                            <p class="text-muted small mb-2">Ces champs restent stock?s mais ne sont plus affich?s sur la home.</p>
                            @php
                                $features = old('whatsapp_banner.features', data_get($settings, 'whatsapp_banner.features', []));
                                $features = is_array($features) ? $features : [];
                            @endphp
                            @foreach(range(0, 2) as $fi)
                            <input type="text" class="form-control mb-2" name="whatsapp_banner[features][]" value="{{ $features[$fi] ?? '' }}" placeholder="Segment {{ $fi + 1 }} (optionnel)">
                            @endforeach
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Texte du bouton</label>
                            <input type="text" class="form-control" name="whatsapp_banner[button_text]" value="{{ old('whatsapp_banner.button_text', data_get($settings, 'whatsapp_banner.button_text', 'Rejoindre')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">URL du bouton</label>
                            <input type="text" class="form-control" name="whatsapp_banner[button_url]" value="{{ old('whatsapp_banner.button_url', data_get($settings, 'whatsapp_banner.button_url', '#')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">QR Code URL</label>
                            <input type="text" class="form-control" name="whatsapp_banner[qr_code_url]" value="{{ old('whatsapp_banner.qr_code_url', data_get($settings, 'whatsapp_banner.qr_code_url')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload QR Code</label>
                            <input type="file" class="form-control" name="whatsapp_banner_qr_file" accept="image/*">
                        </div>
                        @if(data_get($settings, 'whatsapp_banner.qr_code_url'))
                            <div class="col-12">
                                <img src="{{ data_get($settings, 'whatsapp_banner.qr_code_url') }}" alt="QR Code" style="max-height:150px">
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Cruises --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Croisi?res</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="cruises[enabled]" value="1" id="cruises_enabled"
                               {{ old('cruises.enabled', data_get($settings, 'cruises.enabled')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="cruises_enabled">Activer la section Croisi?res</label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Titre</label>
                            <input type="text" class="form-control" name="cruises[title]" value="{{ old('cruises.title', data_get($settings, 'cruises.title', 'Croisi?res')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image URL</label>
                            <input type="text" class="form-control" name="cruises[image_url]" value="{{ old('cruises.image_url', data_get($settings, 'cruises.image_url')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload Image</label>
                            <input type="file" class="form-control" name="cruises_image_file" accept="image/*">
                        </div>
                        @if(data_get($settings, 'cruises.image_url'))
                            <div class="col-12">
                                <img src="{{ data_get($settings, 'cruises.image_url') }}" alt="Cruises" style="max-height:200px">
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label">Texte du bouton</label>
                            <input type="text" class="form-control" name="cruises[button_text]" value="{{ old('cruises.button_text', data_get($settings, 'cruises.button_text', 'D?couvrir')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">URL du bouton</label>
                            <input type="text" class="form-control" name="cruises[button_url]" value="{{ old('cruises.button_url', data_get($settings, 'cruises.button_url', '#')) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Footer</h5></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Colonne 1 ? Titre</label>
                        <input type="text" class="form-control" name="footer[col1_heading]" value="{{ old('footer.col1_heading', data_get($settings, 'footer.col1_heading', 'En savoir plus')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Colonne 2 ? Titre</label>
                        <input type="text" class="form-control" name="footer[col2_heading]" value="{{ old('footer.col2_heading', data_get($settings, 'footer.col2_heading', 'Soci?t')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mentions l?gales</label>
                        <textarea class="form-control" name="footer[legal_text]" rows="3" placeholder="Licence N? ... | RC: ...">{{ old('footer.legal_text', data_get($settings, 'footer.legal_text', "Licence N? 489117 | RC: 18989\nPatente: 50411316 | I.C.E: 001585417000035\nAjinSafro Recreation SARL AU")) }}</textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mb-4" id="save-home-settings-btn">
                <span class="save-label">Enregistrer</span>
                <span class="save-loading d-none">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>Upload en cours...
                </span>
            </button>
        </form>
    </div>

    </div>{{-- /.tab-content --}}
@endsection

@push('styles')
<style>
.holiday-preview__img {
    flex: 0 0 64px;
}
.promo-preview {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 84px;
    padding: 10px 12px;
    border: 1px solid #e9eef5;
    border-radius: 14px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}
.promo-preview__img {
    flex: 0 0 96px;
    width: 96px;
    height: 60px;
    border-radius: 12px;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
}
.promo-preview__title {
    font-weight: 700;
    color: #0e3a5a;
    line-height: 1.3;
}
.promo-preview__subtitle {
    color: #6b7280;
    font-size: .8125rem;
    margin-top: 4px;
    line-height: 1.4;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    /* â???,?â???,? Header tab JS â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,? */
    var menuSource = document.getElementById('hdr_menu_source');
    var wpMenuWrap = document.getElementById('hdr_wp_menu_wrap');
    var linksWrap = document.getElementById('hdr_links_wrap');

    function toggleMenuSource() {
        if (!menuSource) return;
        var isWp = menuSource.value === 'wp_menu';
        if (wpMenuWrap) wpMenuWrap.style.display = isWp ? '' : 'none';
        if (linksWrap) linksWrap.style.display = isWp ? 'none' : '';
    }
    if (menuSource) menuSource.addEventListener('change', toggleMenuSource);
    toggleMenuSource();

    var linksContainer = document.getElementById('hdr-links-container');
    var addLinkBtn = document.getElementById('hdr-add-link');

    function newLinkRowHtml(idx, order) {
        order = order || idx + 1;
        return '<div class="border rounded p-3 hdr-link-row bg-light" data-index="' + idx + '">' +
            '<input type="hidden" name="header[links][' + idx + '][order]" class="hdr-link-order" value="' + order + '">' +
            '<div class="row g-2 align-items-center mb-2">' +
            '<div class="col-auto d-flex flex-column gap-1"><button type="button" class="btn btn-sm btn-outline-secondary hdr-move-up py-0 px-1" title="Monter"><i class="fas fa-chevron-up"></i></button><button type="button" class="btn btn-sm btn-outline-secondary hdr-move-down py-0 px-1" title="Descendre"><i class="fas fa-chevron-down"></i></button></div>' +
            '<div class="col-auto text-center" style="min-width:40px"><span class="badge bg-primary hdr-order-display">' + order + '</span></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Texte</label><input class="form-control form-control-sm" name="header[links][' + idx + '][label]" placeholder="Ex: HOTEL"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">URL</label><input class="form-control form-control-sm" name="header[links][' + idx + '][url]" placeholder="Ex: /hotel"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Ic?ne FA</label><input class="form-control form-control-sm" name="header[links][' + idx + '][icon]" placeholder="fas fa-hotel"></div>' +
            '<div class="col-auto d-flex align-items-end gap-2"><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="header[links][' + idx + '][active]" value="1"><label class="form-check-label small">Actif</label></div></div>' +
            '<div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-primary hdr-add-child">+ Sous-menu</button></div>' +
            '<div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-link">?</button></div>' +
            '</div>' +
            '<div class="hdr-children-list ms-4 ps-3 border-start border-2 border-primary"><div class="d-flex justify-content-between align-items-center mb-1"><small class="text-muted fw-semibold">Sous-menus</small></div></div>' +
            '</div>';
    }

    function newChildRowHtml(linkIdx, childIdx, order) {
        order = order || childIdx + 1;
        return '<div class="row g-2 align-items-center mb-1 hdr-child-row bg-white rounded p-1" data-child-index="' + childIdx + '">' +
            '<input type="hidden" name="header[links][' + linkIdx + '][children][' + childIdx + '][order]" class="hdr-child-order" value="' + order + '">' +
            '<div class="col-auto d-flex gap-1"><button type="button" class="btn btn-sm btn-outline-secondary hdr-child-move-up py-0 px-1" title="Monter"><i class="fas fa-chevron-up fa-xs"></i></button><button type="button" class="btn btn-sm btn-outline-secondary hdr-child-move-down py-0 px-1" title="Descendre"><i class="fas fa-chevron-down fa-xs"></i></button></div>' +
            '<div class="col-auto"><span class="badge bg-secondary hdr-child-order-display">' + order + '</span></div>' +
            '<div class="col-3"><input class="form-control form-control-sm" name="header[links][' + linkIdx + '][children][' + childIdx + '][label]" placeholder="Label"></div>' +
            '<div class="col-3"><input class="form-control form-control-sm" name="header[links][' + linkIdx + '][children][' + childIdx + '][url]" placeholder="URL"></div>' +
            '<div class="col-2"><input class="form-control form-control-sm" name="header[links][' + linkIdx + '][children][' + childIdx + '][icon]" placeholder="Ic?ne FA"></div>' +
            '<div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-child py-0 px-1">?</button></div>' +
            '</div>';
    }

    /* Renumber menu items after reordering */
    function hdrRenumberLinks() {
        if (!linksContainer) return;
        var rows = linksContainer.querySelectorAll('.hdr-link-row');
        rows.forEach(function (row, i) {
            row.setAttribute('data-index', i);
            var orderInp = row.querySelector('.hdr-link-order');
            var orderDisp = row.querySelector('.hdr-order-display');
            if (orderInp) { orderInp.name = 'header[links][' + i + '][order]'; orderInp.value = i + 1; }
            if (orderDisp) orderDisp.textContent = i + 1;
            row.querySelectorAll('input[name^="header[links]"]').forEach(function (inp) {
                if (inp.classList.contains('hdr-link-order')) return;
                inp.name = inp.name.replace(/header\[links\]\[\d+\]/, 'header[links][' + i + ']');
            });
            hdrRenumberChildren(row, i);
        });
    }

    /* Renumber children within a menu item */
    function hdrRenumberChildren(linkRow, linkIdx) {
        var childrenList = linkRow.querySelector('.hdr-children-list');
        if (!childrenList) return;
        var children = childrenList.querySelectorAll('.hdr-child-row');
        children.forEach(function (child, ci) {
            child.setAttribute('data-child-index', ci);
            var orderInp = child.querySelector('.hdr-child-order');
            var orderDisp = child.querySelector('.hdr-child-order-display');
            if (orderInp) { orderInp.name = 'header[links][' + linkIdx + '][children][' + ci + '][order]'; orderInp.value = ci + 1; }
            if (orderDisp) orderDisp.textContent = ci + 1;
            child.querySelectorAll('input:not(.hdr-child-order)').forEach(function (inp) {
                inp.name = inp.name.replace(/header\[links\]\[\d+\]\[children\]\[\d+\]/, 'header[links][' + linkIdx + '][children][' + ci + ']');
            });
        });
    }

    if (addLinkBtn && linksContainer) {
        addLinkBtn.addEventListener('click', function () {
            var idx = linksContainer.querySelectorAll('.hdr-link-row').length;
            linksContainer.insertAdjacentHTML('beforeend', newLinkRowHtml(idx, idx + 1));
        });

        linksContainer.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn) return;

            /* Remove menu item */
            if (btn.classList.contains('hdr-remove-link')) {
                var row = btn.closest('.hdr-link-row');
                if (row) { row.remove(); hdrRenumberLinks(); }
            }
            /* Remove sub-menu item */
            if (btn.classList.contains('hdr-remove-child')) {
                var childRow = btn.closest('.hdr-child-row');
                var linkRow = btn.closest('.hdr-link-row');
                if (childRow) { childRow.remove(); }
                if (linkRow) { hdrRenumberChildren(linkRow, linkRow.getAttribute('data-index')); }
            }
            /* Add sub-menu */
            if (btn.classList.contains('hdr-add-child')) {
                var linkRow = btn.closest('.hdr-link-row');
                if (!linkRow) return;
                var linkIdx = linkRow.getAttribute('data-index');
                var childrenList = linkRow.querySelector('.hdr-children-list');
                if (!childrenList) return;
                var childIdx = childrenList.querySelectorAll('.hdr-child-row').length;
                childrenList.insertAdjacentHTML('beforeend', newChildRowHtml(linkIdx, childIdx, childIdx + 1));
            }
            /* Move menu item UP */
            if (btn.classList.contains('hdr-move-up')) {
                var row = btn.closest('.hdr-link-row');
                if (row && row.previousElementSibling && row.previousElementSibling.classList.contains('hdr-link-row')) {
                    linksContainer.insertBefore(row, row.previousElementSibling);
                    hdrRenumberLinks();
                }
            }
            /* Move menu item DOWN */
            if (btn.classList.contains('hdr-move-down')) {
                var row = btn.closest('.hdr-link-row');
                if (row && row.nextElementSibling && row.nextElementSibling.classList.contains('hdr-link-row')) {
                    linksContainer.insertBefore(row.nextElementSibling, row);
                    hdrRenumberLinks();
                }
            }
            /* Move sub-menu item UP */
            if (btn.classList.contains('hdr-child-move-up')) {
                var childRow = btn.closest('.hdr-child-row');
                var linkRow = btn.closest('.hdr-link-row');
                if (childRow && childRow.previousElementSibling && childRow.previousElementSibling.classList.contains('hdr-child-row')) {
                    childRow.parentNode.insertBefore(childRow, childRow.previousElementSibling);
                    if (linkRow) hdrRenumberChildren(linkRow, linkRow.getAttribute('data-index'));
                }
            }
            /* Move sub-menu item DOWN */
            if (btn.classList.contains('hdr-child-move-down')) {
                var childRow = btn.closest('.hdr-child-row');
                var linkRow = btn.closest('.hdr-link-row');
                if (childRow && childRow.nextElementSibling && childRow.nextElementSibling.classList.contains('hdr-child-row')) {
                    childRow.parentNode.insertBefore(childRow.nextElementSibling, childRow);
                    if (linkRow) hdrRenumberChildren(linkRow, linkRow.getAttribute('data-index'));
                }
            }
        });
    }

    /* â???,?â???,? Content tab JS (existing) â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,? */
    var heroType = document.getElementById('hero_type');
    var imageWraps = [document.getElementById('hero_image_url_wrap'), document.getElementById('hero_image_file_wrap')];
    var videoWraps = [document.getElementById('hero_video_url_wrap'), document.getElementById('hero_video_file_wrap')];
    var overlay = document.getElementById('hero_overlay');
    var overlayValue = document.getElementById('hero_overlay_value');
    var form = document.getElementById('home-page-settings-form');
    var saveButton = document.getElementById('save-home-settings-btn');

    function toggleHeroFields() {
        if (!heroType) return;
        var isImage = heroType.value === 'image';
        imageWraps.forEach(function (el) { if (el) el.style.display = isImage ? '' : 'none'; });
        videoWraps.forEach(function (el) { if (el) el.style.display = isImage ? 'none' : ''; });
    }
    function syncOverlayValue() {
        if (overlay && overlayValue) overlayValue.textContent = overlay.value;
    }

    if (heroType) heroType.addEventListener('change', toggleHeroFields);
    if (overlay) overlay.addEventListener('input', syncOverlayValue);
    toggleHeroFields();
    syncOverlayValue();

    /* â???,?â???,? Ordre des sections + sections personnalis?es â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,? */
    var sectionOrderContainer = document.getElementById('section-order-container');
    var sectionAddCustomBtn = document.getElementById('section-add-custom');
    var sectionLabels = {
        last_minute: 'Tendances du moment',
        accommodations: 'S?jours uniques',
        holiday_theme: 'Voyages par th?me',
        regions: 'Destinations',
        good_spots: 'Bons coins',
        whatsapp_banner: 'Banni?re WhatsApp',
        cruises: 'Croisi?res',
        newsletter: 'Newsletter'
    };
    function nextCustomId() {
        var max = 0;
        sectionOrderContainer.querySelectorAll('.section-order-row[data-section-key^="custom_"]').forEach(function (row) {
            var m = row.getAttribute('data-section-key').match(/^custom_(\d+)$/);
            if (m) max = Math.max(max, parseInt(m[1], 10));
        });
        return 'custom_' + (max + 1);
    }
    function customSectionRowHtml(secKey) {
        return '<div class="border rounded p-3 section-order-row bg-light" data-section-key="' + secKey + '">' +
            '<input type="hidden" name="section_order[]" value="' + secKey + '">' +
            '<div class="d-flex align-items-start gap-2 flex-wrap">' +
            '<div class="d-flex flex-column gap-0">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary section-move-up" title="Monter">?</button>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary section-move-down" title="Descendre">?</button></div>' +
            '<div class="form-check form-switch align-self-center">' +
            '<input class="form-check-input" type="checkbox" name="sections[' + secKey + ']" value="1" checked>' +
            '<label class="form-check-label">Activer</label></div>' +
            '<div class="flex-grow-1">' +
            '<label class="form-label small mb-0">Section personnalis?e ? Titre</label>' +
            '<input type="text" class="form-control form-control-sm" name="custom_sections[' + secKey + '][title]" placeholder="Titre de la section">' +
            '<label class="form-label small mb-0 mt-1">Contenu (HTML / shortcodes)</label>' +
            '<textarea class="form-control form-control-sm" name="custom_sections[' + secKey + '][content]" rows="3" placeholder="<p>...</p> ou [shortcode]"></textarea></div>' +
            '<div class="align-self-center"><button type="button" class="btn btn-sm btn-outline-danger section-remove">?</button></div></div></div>';
    }
    function builtinSectionRowHtml(secKey) {
        var label = sectionLabels[secKey] || secKey;
        return '<div class="border rounded p-2 section-order-row d-flex align-items-center gap-2" data-section-key="' + secKey + '">' +
            '<input type="hidden" name="section_order[]" value="' + secKey + '">' +
            '<div class="d-flex flex-column gap-0">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary section-move-up" title="Monter">?</button>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary section-move-down" title="Descendre">?</button></div>' +
            '<div class="form-check form-switch mb-0">' +
            '<input class="form-check-input" type="checkbox" name="sections[' + secKey + ']" value="1" checked>' +
            '<label class="form-check-label">' + label + '</label></div>' +
            '<div class="ms-auto"><button type="button" class="btn btn-sm btn-outline-secondary section-remove" title="Retirer de la liste">?</button></div></div>';
    }
    var sectionAddBuiltin = document.getElementById('section-add-builtin');
    if (sectionAddBuiltin) {
        sectionAddBuiltin.addEventListener('change', function () {
            var key = this.value;
            if (!key || !sectionOrderContainer) return;
            sectionOrderContainer.insertAdjacentHTML('beforeend', builtinSectionRowHtml(key));
            this.value = '';
            var opt = this.querySelector('option[value="' + key + '"]');
            if (opt) opt.remove();
        });
    }
    if (sectionAddCustomBtn && sectionOrderContainer) {
        sectionAddCustomBtn.addEventListener('click', function () {
            var id = nextCustomId();
            sectionOrderContainer.insertAdjacentHTML('beforeend', customSectionRowHtml(id));
        });
        sectionOrderContainer.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn) return;
            var row = btn.closest('.section-order-row');
            if (!row) return;
            if (btn.classList.contains('section-remove')) {
                var key = row.getAttribute('data-section-key');
                row.remove();
                if (key && key.indexOf('custom_') !== 0 && sectionAddBuiltin) {
                    var opt = document.createElement('option');
                    opt.value = key;
                    opt.textContent = sectionLabels[key] || key;
                    sectionAddBuiltin.appendChild(opt);
                }
                return;
            }
            if (btn.classList.contains('section-move-up') && row.previousElementSibling && row.previousElementSibling.classList.contains('section-order-row')) {
                sectionOrderContainer.insertBefore(row, row.previousElementSibling);
            }
            if (btn.classList.contains('section-move-down') && row.nextElementSibling && row.nextElementSibling.classList.contains('section-order-row')) {
                sectionOrderContainer.insertBefore(row.nextElementSibling, row);
            }
        });
    }

    /* â???,?â???,? Voyages par theme items repeater â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,? */
    var holidayContainer = document.getElementById('holiday-items-container');
    var holidayAddBtn = document.getElementById('holiday-add-item');
    function holidayRowHtml(idx) {
        return '<div class="border rounded p-2 holiday-row" data-index="' + idx + '">' +
            '<div class="row g-2 align-items-center">' +
            '<div class="col-auto d-flex flex-column gap-0"><button type="button" class="btn btn-sm btn-outline-secondary holiday-move-up" title="Monter">?</button><button type="button" class="btn btn-sm btn-outline-secondary holiday-move-down" title="Descendre">?</button></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Titre</label><input type="text" class="form-control form-control-sm" name="holiday_theme[items][' + idx + '][title]"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Badge</label><input type="text" class="form-control form-control-sm" name="holiday_theme[items][' + idx + '][badge]" placeholder="Nouveau"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Description</label><input type="text" class="form-control form-control-sm" name="holiday_theme[items][' + idx + '][description]" placeholder="Texte court"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Image URL</label><input type="text" class="form-control form-control-sm" name="holiday_theme[items][' + idx + '][image_url]"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Upload</label><input type="file" class="form-control form-control-sm" name="holiday_theme_item_files[' + idx + ']" accept="image/*"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Btn texte</label><input type="text" class="form-control form-control-sm" name="holiday_theme[items][' + idx + '][button_text]" value="Voir plus"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Btn URL</label><input type="text" class="form-control form-control-sm" name="holiday_theme[items][' + idx + '][button_url]" value="#"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Tags</label><input type="text" class="form-control form-control-sm" name="holiday_theme[items][' + idx + '][tags]" placeholder="plage, famille, luxe"></div>' +
            '<div class="col-auto"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="holiday_theme[items][' + idx + '][active]" value="1" checked><label class="form-check-label small">Actif</label></div></div>' +
            '<div class="col-auto"><input type="hidden" class="holiday-order" name="holiday_theme[items][' + idx + '][order]" value="' + idx + '"><button type="button" class="btn btn-sm btn-outline-danger holiday-remove mt-4">?</button></div>' +
            '<div class="col-12"><div class="holiday-preview border rounded p-2 d-flex align-items-center gap-2"><div class="holiday-preview__img" style="width:64px;height:44px;border-radius:8px;background:#e7edf5 center/cover no-repeat;"></div><div><div class="holiday-preview__title fw-bold small">Titre</div><div class="holiday-preview__meta text-muted small"></div></div></div></div>' +
            '</div></div>';
    }
    function updateHolidayPreview(row) {
        if (!row) return;
        var title = row.querySelector('input[name*="[title]"]');
        var badge = row.querySelector('input[name*="[badge]"]');
        var image = row.querySelector('input[name*="[image_url]"]');
        var previewTitle = row.querySelector('.holiday-preview__title');
        var previewMeta = row.querySelector('.holiday-preview__meta');
        var previewImg = row.querySelector('.holiday-preview__img');
        if (previewTitle) previewTitle.textContent = (title && title.value) ? title.value : 'Titre';
        if (previewMeta) previewMeta.textContent = (badge && badge.value) ? badge.value : '';
        if (previewImg) {
            var val = image && image.value ? image.value.trim() : '';
            previewImg.style.backgroundImage = val ? 'url("' + val.replace(/"/g, '\\"') + '")' : 'none';
        }
    }
    function holidayRenumber() {
        if (!holidayContainer) return;
        holidayContainer.querySelectorAll('.holiday-row').forEach(function (row, idx) {
            row.setAttribute('data-index', idx);
            row.querySelectorAll('[name^="holiday_theme[items]"]').forEach(function (inp) {
                inp.name = inp.name.replace(/holiday_theme\[items\]\[\d+\]/, 'holiday_theme[items][' + idx + ']');
            });
            row.querySelectorAll('[name^="holiday_theme_item_files"]').forEach(function (inp) {
                inp.name = 'holiday_theme_item_files[' + idx + ']';
            });
            var orderInput = row.querySelector('.holiday-order');
            if (orderInput) {
                orderInput.name = 'holiday_theme[items][' + idx + '][order]';
                orderInput.value = idx;
            }
        });
    }
    if (holidayAddBtn && holidayContainer) {
        holidayContainer.querySelectorAll('.holiday-row').forEach(function (row) { updateHolidayPreview(row); });
        holidayAddBtn.addEventListener('click', function () {
            var idx = holidayContainer.querySelectorAll('.holiday-row').length;
            holidayContainer.insertAdjacentHTML('beforeend', holidayRowHtml(idx));
            var rows = holidayContainer.querySelectorAll('.holiday-row');
            updateHolidayPreview(rows[rows.length - 1]);
        });
        holidayContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('holiday-remove')) {
                var row = e.target.closest('.holiday-row');
                if (row) { row.remove(); holidayRenumber(); }
            }
            if (e.target.classList.contains('holiday-move-up')) {
                var row = e.target.closest('.holiday-row');
                if (row && row.previousElementSibling) {
                    holidayContainer.insertBefore(row, row.previousElementSibling);
                    holidayRenumber();
                }
            }
            if (e.target.classList.contains('holiday-move-down')) {
                var row = e.target.closest('.holiday-row');
                if (row && row.nextElementSibling) {
                    holidayContainer.insertBefore(row.nextElementSibling, row);
                    holidayRenumber();
                }
            }
        });
        holidayContainer.addEventListener('input', function (e) {
            var row = e.target.closest('.holiday-row');
            if (row) updateHolidayPreview(row);
        });
    }

    /* â???,?â???,? Destinations par r?gion (DBR) â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,?â???,? */
    var accordionSliderContainer = document.getElementById('accordion-slider-items-container');
    var accordionSliderAddBtn = document.getElementById('accordion-slider-add-item');
    function accordionSliderRowHtml(idx) {
        return '<div class="border rounded p-3 accordion-slider-row" data-index="' + idx + '">' +
            '<div class="row g-2 align-items-start">' +
            '<div class="col-auto d-flex flex-column gap-0"><button type="button" class="btn btn-sm btn-outline-secondary accordion-slider-move-up" title="Monter">'</button><button type="button" class="btn btn-sm btn-outline-secondary accordion-slider-move-down" title="Descendre">?</button></div>' +
            '<div class="col-md-3"><label class="form-label small mb-0">Titre vertical <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm" name="accordion_slider[slides][' + idx + '][title]" required></div>' +
            '<div class="col-md-3"><label class="form-label small mb-0">Sous-titre</label><input type="text" class="form-control form-control-sm" name="accordion_slider[slides][' + idx + '][subtitle]"></div>' +
            '<div class="col-md-3"><label class="form-label small mb-0">Image URL</label><input type="text" class="form-control form-control-sm accordion-slider-image-input" name="accordion_slider[slides][' + idx + '][image]" placeholder="https://..."></div>' +
            '<div class="col-md-3"><label class="form-label small mb-0">Upload image</label><input type="file" class="form-control form-control-sm" name="accordion_slider_files[' + idx + ']" accept="image/*"></div>' +
            '<div class="col-md-3"><label class="form-label small mb-0">Lien</label><input type="text" class="form-control form-control-sm" name="accordion_slider[slides][' + idx + '][link]" value="#" placeholder="https://..."></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Texte bouton</label><input type="text" class="form-control form-control-sm" name="accordion_slider[slides][' + idx + '][button_text]" placeholder="S\\'inscrire"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Style bouton</label><select class="form-select form-select-sm" name="accordion_slider[slides][' + idx + '][button_style]"><option value="orange">Orange</option><option value="white">White</option><option value="white-arabic">White Arabic</option></select></div>' +
            '<div class="col-md-3"><label class="form-label small mb-0">Overlay / gradient</label><input type="text" class="form-control form-control-sm" name="accordion_slider[slides][' + idx + '][overlay_color]" placeholder="linear-gradient(...)"></div>' +
            '<div class="col-md-1"><label class="form-label small mb-0">Ordre</label><input type="hidden" class="accordion-slider-order" name="accordion_slider[slides][' + idx + '][order]" value="' + (idx + 1) + '"><div class="form-control form-control-sm accordion-slider-order-display">' + (idx + 1) + '</div></div>' +
            '<div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger accordion-slider-remove">?</button></div>' +
            '<div class="col-12"><div class="promo-preview"><div class="promo-preview__img accordion-slider-preview-img"></div><div><div class="promo-preview__title accordion-slider-preview-title">Titre</div><div class="promo-preview__subtitle accordion-slider-preview-subtitle"></div></div></div></div>' +
            '</div></div>';
    }
    function accordionSliderUpdatePreview(row) {
        if (!row) return;
        var title = row.querySelector('input[name*="[title]"]');
        var image = row.querySelector('.accordion-slider-image-input');
        var buttonText = row.querySelector('input[name*="[button_text]"]');
        var previewTitle = row.querySelector('.accordion-slider-preview-title');
        var previewSubtitle = row.querySelector('.accordion-slider-preview-subtitle');
        var previewImg = row.querySelector('.accordion-slider-preview-img');
        if (previewTitle) previewTitle.textContent = title && title.value ? title.value : 'Titre';
        if (previewSubtitle) previewSubtitle.textContent = buttonText && buttonText.value ? buttonText.value : '';
        if (previewImg) {
            var val = image && image.value ? image.value.trim() : '';
            previewImg.style.backgroundImage = val ? 'url("' + val.replace(/"/g, '\\"') + '")' : 'none';
        }
    }
    function accordionSliderRenumber() {
        if (!accordionSliderContainer) return;
        accordionSliderContainer.querySelectorAll('.accordion-slider-row').forEach(function (row, idx) {
            row.setAttribute('data-index', idx);
            row.querySelectorAll('[name^="accordion_slider[slides]"]').forEach(function (inp) {
                inp.name = inp.name.replace(/accordion_slider\[slides\]\[\d+\]/, 'accordion_slider[slides][' + idx + ']');
            });
            row.querySelectorAll('[name^="accordion_slider_files"]').forEach(function (inp) {
                inp.name = 'accordion_slider_files[' + idx + ']';
            });
            var orderInput = row.querySelector('.accordion-slider-order');
            var orderDisplay = row.querySelector('.accordion-slider-order-display');
            if (orderInput) {
                orderInput.name = 'accordion_slider[slides][' + idx + '][order]';
                orderInput.value = idx + 1;
            }
            if (orderDisplay) orderDisplay.textContent = idx + 1;
        });
    }
    if (accordionSliderAddBtn && accordionSliderContainer) {
        accordionSliderContainer.querySelectorAll('.accordion-slider-row').forEach(function (row) { accordionSliderUpdatePreview(row); });
        accordionSliderAddBtn.addEventListener('click', function () {
            var idx = accordionSliderContainer.querySelectorAll('.accordion-slider-row').length;
            accordionSliderContainer.insertAdjacentHTML('beforeend', accordionSliderRowHtml(idx));
            var rows = accordionSliderContainer.querySelectorAll('.accordion-slider-row');
            accordionSliderUpdatePreview(rows[rows.length - 1]);
        });
        accordionSliderContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('accordion-slider-remove')) {
                var row = e.target.closest('.accordion-slider-row');
                if (row) { row.remove(); accordionSliderRenumber(); }
            }
            if (e.target.classList.contains('accordion-slider-move-up')) {
                var row = e.target.closest('.accordion-slider-row');
                if (row && row.previousElementSibling) {
                    accordionSliderContainer.insertBefore(row, row.previousElementSibling);
                    accordionSliderRenumber();
                }
            }
            if (e.target.classList.contains('accordion-slider-move-down')) {
                var row = e.target.closest('.accordion-slider-row');
                if (row && row.nextElementSibling) {
                    accordionSliderContainer.insertBefore(row.nextElementSibling, row);
                    accordionSliderRenumber();
                }
            }
        });
        accordionSliderContainer.addEventListener('input', function (e) {
            var row = e.target.closest('.accordion-slider-row');
            if (row) accordionSliderUpdatePreview(row);
        });
    }

    var dbrContainer = document.getElementById('dbr-items-container');
    var dbrAddBtn = document.getElementById('dbr-add-item');
    function dbrRowHtml(idx, order, label, imageUrl, linkUrl) {
        label = label || ''; imageUrl = imageUrl || ''; linkUrl = linkUrl || ''; order = order == null ? idx + 1 : order;
        return '<div class="border rounded p-2 dbr-row align-items-center" data-index="' + idx + '">' +
            '<div class="row g-2 align-items-center">' +
            '<div class="col-auto d-flex flex-column gap-0"><button type="button" class="btn btn-sm btn-outline-secondary dbr-move-up" title="Monter">?</button><button type="button" class="btn btn-sm btn-outline-secondary dbr-move-down" title="Descendre">?</button></div>' +
            '<div class="col"><input type="hidden" name="destinations_by_region[items][' + idx + '][order]" class="dbr-order" value="' + order + '"><label class="form-label small mb-0">Ordre</label><span class="dbr-order-display">' + order + '</span></div>' +
            '<div class="col"><label class="form-label small mb-0">Label <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][' + idx + '][label]" value="' + (label.replace(/"/g, '&quot;')) + '" placeholder="Ex: CAP NORD" required></div>' +
            '<div class="col"><label class="form-label small mb-0">Image URL</label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][' + idx + '][image_url]" value="' + (imageUrl.replace(/"/g, '&quot;')) + '" placeholder="https://..."></div>' +
            '<div class="col-auto"><label class="form-label small mb-0">Choisir</label><input type="file" class="form-control form-control-sm dbr-file" name="destinations_by_region_files[' + idx + ']" accept="image/*" data-index="' + idx + '"></div>' +
            '<div class="col"><label class="form-label small mb-0">Lien URL</label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][' + idx + '][link_url]" value="' + (linkUrl.replace(/"/g, '&quot;')) + '" placeholder="https://..."></div>' +
            '<div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger dbr-remove">?</button></div>' +
            '</div></div>';
    }
    function dbrRenumber() {
        if (!dbrContainer) return;
        var rows = dbrContainer.querySelectorAll('.dbr-row');
        rows.forEach(function (row, i) {
            row.setAttribute('data-index', i);
            var orderInp = row.querySelector('.dbr-order');
            var orderDisp = row.querySelector('.dbr-order-display');
            if (orderInp) { orderInp.name = 'destinations_by_region[items][' + i + '][order]'; orderInp.value = i + 1; }
            if (orderDisp) orderDisp.textContent = i + 1;
            row.querySelectorAll('input[name^="destinations_by_region"]').forEach(function (inp) {
                inp.name = inp.name.replace(/destinations_by_region\[items\]\[\d+\]/, 'destinations_by_region[items][' + i + ']');
            });
            row.querySelectorAll('input[name^="destinations_by_region_files"]').forEach(function (inp) {
                inp.name = 'destinations_by_region_files[' + i + ']';
            });
        });
    }
    if (dbrAddBtn && dbrContainer) {
        dbrAddBtn.addEventListener('click', function () {
            var idx = dbrContainer.querySelectorAll('.dbr-row').length;
            dbrContainer.insertAdjacentHTML('beforeend', dbrRowHtml(idx, idx + 1, '', '', ''));
        });
        dbrContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('dbr-remove')) {
                var row = e.target.closest('.dbr-row');
                if (row) { row.remove(); dbrRenumber(); }
            }
            if (e.target.classList.contains('dbr-move-up')) {
                var row = e.target.closest('.dbr-row');
                if (row && row.previousElementSibling) {
                    dbrContainer.insertBefore(row, row.previousElementSibling);
                    dbrRenumber();
                }
            }
            if (e.target.classList.contains('dbr-move-down')) {
                var row = e.target.closest('.dbr-row');
                if (row && row.nextElementSibling) {
                    dbrContainer.insertBefore(row.nextElementSibling, row);
                    dbrRenumber();
                }
            }
        });
    }

    if (form && saveButton) {
        var saveLabel = saveButton.querySelector('.save-label');
        var saveLoading = saveButton.querySelector('.save-loading');
        form.addEventListener('submit', function () {
            saveButton.disabled = true;
            if (saveLabel) saveLabel.classList.add('d-none');
            if (saveLoading) saveLoading.classList.remove('d-none');
        });
        window.addEventListener('pageshow', function () {
            saveButton.disabled = false;
            if (saveLabel) saveLabel.classList.remove('d-none');
            if (saveLoading) saveLoading.classList.add('d-none');
        });
    }
})();
</script>
@endpush


