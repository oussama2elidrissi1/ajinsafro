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
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="header[show_header_sitewide]" value="1" id="hdr_sitewide"
                               {{ old('header.show_header_sitewide', data_get($header, 'show_header_sitewide')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hdr_sitewide">Appliquer le header à toutes les pages WordPress</label>
                    </div>
                    <p class="small text-muted mt-1 mb-0">Si coché, le header s'affiche sur tout le site (pas seulement la page d'accueil). Le header du thème sera masqué.</p>
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

                    {{-- Manual links repeater with sub-menus --}}
                    <div id="hdr_links_wrap" class="mt-3" style="display:none">
                        <label class="form-label fw-bold">Liens du menu</label>
                        <div id="hdr-links-container" class="vstack gap-3">
                            @foreach(old('header.links', data_get($header, 'links', [])) as $li => $link)
                                <div class="border rounded p-3 hdr-link-row" data-index="{{ $li }}">
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Texte</label>
                                            <input class="form-control form-control-sm" name="header[links][{{ $li }}][label]" value="{{ data_get($link, 'label') }}" placeholder="Ex: HOTEL">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small mb-0">URL</label>
                                            <input class="form-control form-control-sm" name="header[links][{{ $li }}][url]" value="{{ data_get($link, 'url') }}" placeholder="Ex: /hotel">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Icône FA</label>
                                            <input class="form-control form-control-sm" name="header[links][{{ $li }}][icon]" value="{{ data_get($link, 'icon') }}" placeholder="fas fa-hotel">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end gap-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="header[links][{{ $li }}][active]" value="1" {{ data_get($link, 'active') ? 'checked' : '' }}>
                                                <label class="form-check-label small">Actif</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary hdr-add-child">+ Sous-menu</button>
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-link">×</button></div>
                                    </div>
                                    <div class="hdr-children-list ms-3 ps-2 border-start border-2 border-light">
                                        <small class="text-muted d-block mb-1">Sous-menus</small>
                                        @foreach(data_get($link, 'children', []) as $ci => $child)
                                            <div class="row g-2 align-items-center mb-1 hdr-child-row">
                                                <div class="col-4"><input class="form-control form-control-sm" name="header[links][{{ $li }}][children][{{ $ci }}][label]" value="{{ data_get($child, 'label') }}" placeholder="Label"></div>
                                                <div class="col-4"><input class="form-control form-control-sm" name="header[links][{{ $li }}][children][{{ $ci }}][url]" value="{{ data_get($child, 'url') }}" placeholder="URL"></div>
                                                <div class="col-2"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-child">×</button></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="hdr-add-link">+ Ajouter un lien</button>
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
                    @foreach(['search' => 'Search', 'last_minute' => 'Offres dernière minute', 'regions' => 'Destinations', 'good_spots' => 'Bons coins'] as $sKey => $sLabel)
                    <div class="col-md-3">
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
                <div class="card-header"><h5 class="card-title mb-0">Offres dernière minute</h5></div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Titre</label>
                        <input type="text" class="form-control" name="last_minute[title]" value="{{ old('last_minute.title', data_get($settings, 'last_minute.title')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nombre d'items</label>
                        <input type="number" class="form-control" min="1" max="20" name="last_minute[count]" value="{{ old('last_minute.count', data_get($settings, 'last_minute.count', 6)) }}" required>
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
    function newLinkRowHtml(idx) {
        return '<div class="border rounded p-3 hdr-link-row" data-index="' + idx + '">' +
            '<div class="row g-2 align-items-center mb-2">' +
            '<div class="col-md-2"><label class="form-label small mb-0">Texte</label><input class="form-control form-control-sm" name="header[links][' + idx + '][label]" placeholder="Ex: HOTEL"></div>' +
            '<div class="col-md-3"><label class="form-label small mb-0">URL</label><input class="form-control form-control-sm" name="header[links][' + idx + '][url]" placeholder="Ex: /hotel"></div>' +
            '<div class="col-md-2"><label class="form-label small mb-0">Icône FA</label><input class="form-control form-control-sm" name="header[links][' + idx + '][icon]" placeholder="fas fa-hotel"></div>' +
            '<div class="col-md-2 d-flex align-items-end gap-2"><div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="header[links][' + idx + '][active]" value="1"><label class="form-check-label small">Actif</label></div></div>' +
            '<div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-primary hdr-add-child">+ Sous-menu</button></div>' +
            '<div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-link">×</button></div>' +
            '</div>' +
            '<div class="hdr-children-list ms-3 ps-2 border-start border-2 border-light"><small class="text-muted d-block mb-1">Sous-menus</small></div>' +
            '</div>';
    }
    function newChildRowHtml(linkIdx, childIdx) {
        return '<div class="row g-2 align-items-center mb-1 hdr-child-row">' +
            '<div class="col-4"><input class="form-control form-control-sm" name="header[links][' + linkIdx + '][children][' + childIdx + '][label]" placeholder="Label"></div>' +
            '<div class="col-4"><input class="form-control form-control-sm" name="header[links][' + linkIdx + '][children][' + childIdx + '][url]" placeholder="URL"></div>' +
            '<div class="col-2"><button type="button" class="btn btn-sm btn-outline-danger hdr-remove-child">×</button></div>' +
            '</div>';
    }
    if (addLinkBtn && linksContainer) {
        addLinkBtn.addEventListener('click', function () {
            var idx = linksContainer.querySelectorAll('.hdr-link-row').length;
            linksContainer.insertAdjacentHTML('beforeend', newLinkRowHtml(idx));
        });
        linksContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('hdr-remove-link')) {
                var row = e.target.closest('.hdr-link-row');
                if (row) row.remove();
            }
            if (e.target.classList.contains('hdr-remove-child')) {
                var row = e.target.closest('.hdr-child-row');
                if (row) row.remove();
            }
            if (e.target.classList.contains('hdr-add-child')) {
                var linkRow = e.target.closest('.hdr-link-row');
                if (!linkRow) return;
                var linkIdx = linkRow.getAttribute('data-index');
                var childrenList = linkRow.querySelector('.hdr-children-list');
                if (!childrenList) return;
                var childIdx = childrenList.querySelectorAll('.hdr-child-row').length;
                childrenList.insertAdjacentHTML('beforeend', newChildRowHtml(linkIdx, childIdx));
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
