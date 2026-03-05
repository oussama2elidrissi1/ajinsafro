@extends('layouts.master-ajinsafro')
@section('title') Home page @endsection

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

    {{-- ═══════ TABS ═══════ --}}
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ ($tab ?? 'header') === 'header' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-header" role="tab">Header</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ ($tab ?? 'header') === 'content' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-content" role="tab">Contenu</a>
        </li>
    </ul>

    <div class="tab-content">

    {{-- ═══════════════════════════════════════════
         TAB 1 — HEADER (topbar + navbar)
         ═══════════════════════════════════════════ --}}
    <div class="tab-pane fade {{ ($tab ?? 'header') === 'header' ? 'show active' : '' }}" id="tab-header" role="tabpanel">
        <form action="{{ route('admin.settings.home-page.update-header') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Global enabled --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Header global</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="header[enabled]" value="1" id="hdr_enabled"
                               {{ old('header.enabled', data_get($header, 'enabled')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hdr_enabled">Activer le header personnalisé</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="header[show_header_sitewide]" value="1" id="hdr_sitewide"
                               {{ old('header.show_header_sitewide', data_get($header, 'show_header_sitewide')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hdr_sitewide">Appliquer le header à toutes les pages WordPress</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="header[show_footer_sitewide]" value="1" id="hdr_footer_sitewide"
                               {{ old('header.show_footer_sitewide', data_get($header, 'show_footer_sitewide', true)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hdr_footer_sitewide">Appliquer le footer personnalisé à toutes les pages</label>
                    </div>
                    <p class="small text-muted mt-1 mb-0">Le header et le footer personnalisés remplacent ceux du thème WordPress sur toutes les pages.</p>
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
                            <label class="form-label">Téléphone</label>
                            <input type="text" class="form-control" name="header[phone]"
                                   value="{{ old('header.phone', data_get($header, 'phone')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="header[email]"
                                   value="{{ old('header.email', data_get($header, 'email')) }}">
                        </div>
                    </div>
                    <h6 class="mt-3 mb-2">Réseaux sociaux</h6>
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
                            <small class="text-muted"><i class="fas fa-arrows-alt"></i> Utilisez les flèches pour réorganiser l'ordre</small>
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
                                            <label class="form-label small mb-0">Icône FA</label>
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
                                        <div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-link">×</button></div>
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
                                                <div class="col-2"><input class="form-control form-control-sm" name="header[links][{{ $li }}][children][{{ $ci }}][icon]" value="{{ data_get($child, 'icon', '') }}" placeholder="Icône FA"></div>
                                                <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-child py-0 px-1">×</button></div>
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
                    <p class="small text-muted mb-3">Ce bouton s'affiche à droite de la navbar avec un effet de gradient orange/rouge et une animation.</p>
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

    {{-- ═══════════════════════════════════════════
         TAB 2 — CONTENU (hero, sections, regions…)
         ═══════════════════════════════════════════ --}}
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
                            <input type="file" class="form-control" name="hero_video_file" accept="video/mp4">
                            <small class="text-muted d-block mt-1">Max 50MB. Si l'upload échoue, utilisez un lien vidéo.</small>
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

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Sections</h5></div>
                <div class="card-body row g-3">
                    @foreach(['search' => 'Search', 'last_minute' => 'Tendances du moment', 'accommodations' => 'Séjours uniques', 'regions' => 'Destinations', 'good_spots' => 'Bons coins', 'promotions' => 'Promotions'] as $sKey => $sLabel)
                    <div class="col-md-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="sections[{{ $sKey }}]" value="1"
                                   {{ old("sections.$sKey", data_get($settings, "sections.$sKey")) ? 'checked' : '' }}>
                            <label class="form-check-label">{{ $sLabel }}</label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Search</h5></div>
                <div class="card-body">
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
                <div class="card-header"><h5 class="card-title mb-0">Découvrez des séjours uniques</h5></div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Titre</label>
                        <input type="text" class="form-control" name="accommodations[title]" value="{{ old('accommodations.title', data_get($settings, 'accommodations.title', 'Découvrez des séjours uniques')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nombre d'items</label>
                        <input type="number" class="form-control" min="1" max="20" name="accommodations[count]" value="{{ old('accommodations.count', data_get($settings, 'accommodations.count', 4)) }}">
                    </div>
                    <div class="col-12">
                        <p class="text-muted small mb-0">Affiche les hôtels et locations (post types: st_hotel, st_rental) les plus récents.</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Destinations par région</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="dbr-add-item">Ajouter une région</button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Grille 2×4 côté WordPress. Label obligatoire ; image et lien optionnels.</p>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="destinations_by_region[enabled]" value="1" id="dbr-enabled"
                               {{ old('destinations_by_region.enabled', data_get($destinationsByRegion, 'enabled', true)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="dbr-enabled">Activer la section Destinations par région</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre de la section</label>
                        <input type="text" class="form-control" name="destinations_by_region[title]" value="{{ old('destinations_by_region.title', data_get($destinationsByRegion, 'title', 'Destinations par région')) }}" placeholder="Destinations par région">
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
                                        <button type="button" class="btn btn-sm btn-outline-secondary dbr-move-up" title="Monter">↑</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary dbr-move-down" title="Descendre">↓</button>
                                    </div>
                                    <div class="col"><input type="hidden" name="destinations_by_region[items][{{ $idx }}][order]" class="dbr-order" value="{{ data_get($item, 'order', $idx + 1) }}"><label class="form-label small mb-0">Ordre</label><span class="dbr-order-display">{{ data_get($item, 'order', $idx + 1) }}</span></div>
                                    <div class="col"><label class="form-label small mb-0">Label <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][{{ $idx }}][label]" value="{{ data_get($item, 'label') }}" placeholder="Ex: CAP NORD" required></div>
                                    <div class="col"><label class="form-label small mb-0">Image URL</label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][{{ $idx }}][image_url]" value="{{ data_get($item, 'image_url') }}" placeholder="https://..."></div>
                                    <div class="col-auto"><label class="form-label small mb-0">Choisir</label><input type="file" class="form-control form-control-sm dbr-file" name="destinations_by_region_files[{{ $idx }}]" accept="image/*" data-index="{{ $idx }}"></div>
                                    <div class="col"><label class="form-label small mb-0">Lien URL</label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][{{ $idx }}][link_url]" value="{{ data_get($item, 'link_url') }}" placeholder="https://..."></div>
                                    <div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger dbr-remove">×</button></div>
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
                                <div class="col-md-3"><input class="form-control" name="good_spots[{{ $idx }}][icon]" value="{{ data_get($spot, 'icon') }}" placeholder="Icône FA (ex: fas fa-utensils)"></div>
                                <div class="col-md-3"><input class="form-control" name="good_spots[{{ $idx }}][link_url]" value="{{ data_get($spot, 'link_url') }}" placeholder="Link URL"></div>
                                <div class="col-md-6"><input class="form-control" name="good_spots[{{ $idx }}][image_url]" value="{{ data_get($spot, 'image_url') }}" placeholder="Image URL"></div>
                                <div class="col-md-6"><input type="file" class="form-control" name="good_spots_files[{{ $idx }}]" accept="image/*"></div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Promotions --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Destinations de ce mois (Promotions)</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Titre de la section</label>
                        <input type="text" class="form-control" name="promotions[title]" value="{{ old('promotions.title', data_get($settings, 'promotions.title', 'Destinations de ce mois')) }}">
                    </div>
                    <p class="text-muted small mb-3">3 bannières promotionnelles avec un style gradient. Laissez vide pour utiliser le contenu par défaut.</p>
                    @php
                        $defaultPromos = [
                            ['badge_text' => 'Profitez', 'badge_bg' => '#ef4444', 'badge_color' => '#fff', 'title' => "Cartes de\nfidélités", 'text' => "Plus d'espace de voyages pour vous et nos fidèles.", 'style' => 'blue', 'url' => '#'],
                            ['badge_text' => 'Profitez', 'badge_bg' => '#fff', 'badge_color' => '#f37a1f', 'title' => "Programme\nBztam e-Sfar", 'text' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.', 'style' => 'orange', 'url' => '#'],
                            ['badge_text' => 'احجز الآن', 'badge_bg' => '#ffb300', 'badge_color' => '#0e3a5a', 'title' => 'الحجز بكري', 'text' => 'تجمع الان الودائع للمسافرين إلى وجهاتك و تمتع بخصم إضافي.', 'style' => 'dark-blue', 'url' => '#'],
                        ];
                        $promos = old('promotions.items', data_get($settings, 'promotions.items', $defaultPromos));
                        if (empty($promos)) $promos = $defaultPromos;
                    @endphp
                    <div class="vstack gap-3">
                        @foreach($promos as $pi => $promo)
                        <div class="border rounded p-3">
                            <h6 class="mb-2">Promo {{ $pi + 1 }}</h6>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label small mb-0">Titre</label>
                                    <input class="form-control form-control-sm" name="promotions[items][{{ $pi }}][title]" value="{{ data_get($promo, 'title') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-0">Texte badge</label>
                                    <input class="form-control form-control-sm" name="promotions[items][{{ $pi }}][badge_text]" value="{{ data_get($promo, 'badge_text') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Style</label>
                                    <select class="form-select form-select-sm" name="promotions[items][{{ $pi }}][style]">
                                        <option value="blue" {{ data_get($promo, 'style') === 'blue' ? 'selected' : '' }}>Bleu</option>
                                        <option value="orange" {{ data_get($promo, 'style') === 'orange' ? 'selected' : '' }}>Orange</option>
                                        <option value="dark-blue" {{ data_get($promo, 'style') === 'dark-blue' ? 'selected' : '' }}>Bleu foncé</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">URL</label>
                                    <input class="form-control form-control-sm" name="promotions[items][{{ $pi }}][url]" value="{{ data_get($promo, 'url') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-0">Badge BG</label>
                                    <input type="color" class="form-control form-control-sm form-control-color" name="promotions[items][{{ $pi }}][badge_bg]" value="{{ data_get($promo, 'badge_bg', '#ef4444') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-0">Description</label>
                                    <input class="form-control form-control-sm" name="promotions[items][{{ $pi }}][text]" value="{{ data_get($promo, 'text') }}">
                                </div>
                                <input type="hidden" name="promotions[items][{{ $pi }}][badge_color]" value="{{ data_get($promo, 'badge_color', '#fff') }}">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Footer</h5></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Colonne 1 — Titre</label>
                        <input type="text" class="form-control" name="footer[col1_heading]" value="{{ old('footer.col1_heading', data_get($settings, 'footer.col1_heading', 'En savoir plus')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Colonne 2 — Titre</label>
                        <input type="text" class="form-control" name="footer[col2_heading]" value="{{ old('footer.col2_heading', data_get($settings, 'footer.col2_heading', 'Société')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mentions légales</label>
                        <textarea class="form-control" name="footer[legal_text]" rows="3" placeholder="Licence N° ... | RC: ...">{{ old('footer.legal_text', data_get($settings, 'footer.legal_text', "Licence N° 489117 | RC: 18989\nPatente: 50411316 | I.C.E: 001585417000035\nAjinSafro Recreation SARL AU")) }}</textarea>
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

@push('script')
<script>
(function () {
    /* ── Header tab JS ─────────────────────────── */
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
            '<div class="col-md-2"><label class="form-label small mb-0">Icône FA</label><input class="form-control form-control-sm" name="header[links][' + idx + '][icon]" placeholder="fas fa-hotel"></div>' +
            '<div class="col-auto d-flex align-items-end gap-2"><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="header[links][' + idx + '][active]" value="1"><label class="form-check-label small">Actif</label></div></div>' +
            '<div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-primary hdr-add-child">+ Sous-menu</button></div>' +
            '<div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-link">×</button></div>' +
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
            '<div class="col-2"><input class="form-control form-control-sm" name="header[links][' + linkIdx + '][children][' + childIdx + '][icon]" placeholder="Icône FA"></div>' +
            '<div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-child py-0 px-1">×</button></div>' +
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

    /* ── Content tab JS (existing) ────────────── */
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

    /* ── Destinations par région (DBR) ─────────── */
    var dbrContainer = document.getElementById('dbr-items-container');
    var dbrAddBtn = document.getElementById('dbr-add-item');
    function dbrRowHtml(idx, order, label, imageUrl, linkUrl) {
        label = label || ''; imageUrl = imageUrl || ''; linkUrl = linkUrl || ''; order = order == null ? idx + 1 : order;
        return '<div class="border rounded p-2 dbr-row align-items-center" data-index="' + idx + '">' +
            '<div class="row g-2 align-items-center">' +
            '<div class="col-auto d-flex flex-column gap-0"><button type="button" class="btn btn-sm btn-outline-secondary dbr-move-up" title="Monter">↑</button><button type="button" class="btn btn-sm btn-outline-secondary dbr-move-down" title="Descendre">↓</button></div>' +
            '<div class="col"><input type="hidden" name="destinations_by_region[items][' + idx + '][order]" class="dbr-order" value="' + order + '"><label class="form-label small mb-0">Ordre</label><span class="dbr-order-display">' + order + '</span></div>' +
            '<div class="col"><label class="form-label small mb-0">Label <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][' + idx + '][label]" value="' + (label.replace(/"/g, '&quot;')) + '" placeholder="Ex: CAP NORD" required></div>' +
            '<div class="col"><label class="form-label small mb-0">Image URL</label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][' + idx + '][image_url]" value="' + (imageUrl.replace(/"/g, '&quot;')) + '" placeholder="https://..."></div>' +
            '<div class="col-auto"><label class="form-label small mb-0">Choisir</label><input type="file" class="form-control form-control-sm dbr-file" name="destinations_by_region_files[' + idx + ']" accept="image/*" data-index="' + idx + '"></div>' +
            '<div class="col"><label class="form-label small mb-0">Lien URL</label><input type="text" class="form-control form-control-sm" name="destinations_by_region[items][' + idx + '][link_url]" value="' + (linkUrl.replace(/"/g, '&quot;')) + '" placeholder="https://..."></div>' +
            '<div class="col-auto d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger dbr-remove">×</button></div>' +
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
