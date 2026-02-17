@extends('layouts.master-ajinsafro')
@section('title')
    Modifier le tour WordPress
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier : {{ $voyage->post_title ?? $voyage->name }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.voyages.index') }}">Tours</a></li>
                        <li class="breadcrumb-item active">{{ $voyage->post_title ?? $voyage->name }}</li>
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

    <div class="alert alert-info mb-3">
        <i class="mdi mdi-information me-2"></i>
        <strong>Admin aligné avec WordPress Traveler</strong> - Tous les champs ci-dessous écrivent directement dans la DB WordPress (cFdgeZ_postmeta + taxonomies).
    </div>

    <form action="{{ route('admin.circuits.voyages.update', $voyage->ID) }}" method="POST" id="edit-voyage-form" data-voyage-id="{{ $voyage->ID }}">
        @csrf
        @method('PUT')

        {{-- NAVIGATION TABS --}}
        <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#basic" role="tab">
                    <i class="bx bx-edit-alt"></i> Basique
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#localisation" role="tab">
                    <i class="bx bx-map"></i> Localisation
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tarification" role="tab">
                    <i class="bx bx-dollar-circle"></i> Tarification
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#disponibilite" role="tab">
                    <i class="bx bx-calendar-check"></i> Disponibilité
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#departs-vols-dates" role="tab">
                    <i class="bx bx-trip"></i> Départs • Vols • Dates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#logistique" role="tab">
                    <i class="bx bx-package"></i> Logistique
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#programme" role="tab">
                    <i class="bx bx-calendar-week"></i> Programme
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#brouillon" role="tab">
                    <i class="bx bx-archive"></i> Draft
                </a>
            </li>
        </ul>

        <div class="tab-content p-3 border border-top-0">
            {{-- TAB 1: BASIC --}}
            <div class="tab-pane active" id="basic" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Informations principales</h4>
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Titre du tour <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $voyage->post_title) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="slug" class="form-label">Slug (URL)</label>
                                    <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $voyage->post_name) }}">
                                    <small class="text-muted">URL: ajinsafro.net/tours/<strong>{{ $voyage->post_name }}</strong></small>
                                </div>

                                <div class="mb-3">
                                    <label for="content" class="form-label">Description complète</label>
                                    <textarea class="form-control" id="content" name="content" rows="10">{{ old('content', $voyage->post_content) }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="excerpt" class="form-label">Extrait / Accroche</label>
                                    <textarea class="form-control" id="excerpt" name="excerpt" rows="3">{{ old('excerpt', $voyage->post_excerpt) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">Paramètres généraux</h4>

                                <div class="mb-3">
                                    <label for="post_status" class="form-label">Statut</label>
                                    <select class="form-select" id="post_status" name="post_status">
                                        <option value="publish" {{ old('post_status', $voyage->post_status) === 'publish' ? 'selected' : '' }}>Publié</option>
                                        <option value="draft" {{ old('post_status', $voyage->post_status) === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                        <option value="pending" {{ old('post_status', $voyage->post_status) === 'pending' ? 'selected' : '' }}>En attente</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="duration_day" class="form-label">Durée (jours)</label>
                                    <input type="number" class="form-control" id="duration_day" name="duration_day" value="{{ old('duration_day', $meta['duration_day'] ?? '') }}" min="1" readonly tabindex="-1" title="Calculé automatiquement depuis le Programme (onglet Programme)">
                                </div>

                                <div class="mb-3">
                                    <label for="min_people" class="form-label">Nombre min. personnes</label>
                                    <input type="number" class="form-control" id="min_people" name="min_people" value="{{ old('min_people', $meta['min_people'] ?? '') }}" min="1">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="max_people" class="form-label">Nombre max. personnes</label>
                                    <input type="number" class="form-control" id="max_people" name="max_people" value="{{ old('max_people', $meta['max_people'] ?? '') }}" min="1">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tour_price_by" class="form-label">Tarification par</label>
                                    <select class="form-select" id="tour_price_by" name="tour_price_by">
                                        <option value="">-- Sélectionner --</option>
                                        <option value="person" {{ old('tour_price_by', $meta['tour_price_by'] ?? '') === 'person' ? 'selected' : '' }}>Par personne</option>
                                        <option value="group" {{ old('tour_price_by', $meta['tour_price_by'] ?? '') === 'group' ? 'selected' : '' }}>Par groupe</option>
                                        <option value="fixed" {{ old('tour_price_by', $meta['tour_price_by'] ?? '') === 'fixed' ? 'selected' : '' }}>Prix fixe</option>
                                    </select>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $meta['is_featured'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_featured">
                                        Tour à la une (Featured)
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="hide_adult_in_booking_form" name="hide_adult_in_booking_form" value="1" {{ old('hide_adult_in_booking_form', $meta['hide_adult_in_booking_form'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="hide_adult_in_booking_form">
                                        Masquer champ adulte dans formulaire
                                    </label>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="st_tour_external_booking" class="form-label">Lien réservation externe</label>
                                    <input type="text" class="form-control" id="st_tour_external_booking" name="st_tour_external_booking" value="{{ old('st_tour_external_booking', $meta['st_tour_external_booking'] ?? '') }}" placeholder="https://...">
                                    <small class="text-muted">Optionnel</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: LOCALISATION — Destination UX + Coordonnées --}}
            <div class="tab-pane" id="localisation" role="tabpanel">
                <style>
                .destination-ux-card { border: 1px solid #dee2e6; border-radius: 6px; }
                .destination-ux-body { padding: 1rem 1.25rem; }
                .destination-ux-header { margin-bottom: 1rem; }
                .destination-ux-title { font-size: 1.1rem; font-weight: 600; color: #212529; margin: 0 0 0.25rem 0; }
                .destination-ux-helper { font-size: 0.8125rem; color: #6c757d; margin: 0 0 0.5rem 0; }
                .destination-ux-badge-wrap { margin-top: 0.5rem; }
                .destination-ux-badge { font-size: 0.75rem; font-weight: 500; }
                .destination-ux-chips-section { margin-bottom: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid #eee; }
                .destination-ux-chips-label { font-size: 0.75rem; font-weight: 600; color: #495057; margin-bottom: 0.35rem; }
                .destination-ux-chips { display: flex; flex-wrap: wrap; gap: 0.35rem; min-height: 1.5rem; }
                .destination-ux-chip { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.2rem 0.5rem; font-size: 0.75rem; background: #e7f1ff; color: #0d6efd; border-radius: 4px; border: 1px solid #b6d4fe; }
                .destination-ux-chip-remove { background: none; border: none; padding: 0 0.15rem; cursor: pointer; color: #0d6efd; font-size: 1rem; line-height: 1; opacity: 0.8; }
                .destination-ux-chip-remove:hover { opacity: 1; color: #0a58ca; }
                .destination-ux-chips-clear { margin-top: 0.35rem; }
                .destination-ux-search-wrap { margin-bottom: 0.5rem; }
                .destination-ux-search { max-width: 320px; }
                .destination-ux-actions { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-bottom: 0.75rem; }
                .destination-ux-btn { font-size: 0.75rem; }
                .destination-ux-tree-wrap { border: 1px solid #dee2e6; background: #fff; padding: 0.75rem 1rem; max-height: 320px; overflow-y: auto; border-radius: 6px; }
                .destination-tree-list { padding-left: 0; }
                .destination-tree-item { margin: 0; padding: 0; list-style: none; }
                .destination-tree-row { display: flex; align-items: center; gap: 0.25rem; padding: 0.2rem 0; min-height: 1.6rem; }
                .destination-tree-toggle { width: 1rem; height: 1rem; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #6c757d; font-size: 0.7rem; user-select: none; flex-shrink: 0; }
                .destination-tree-toggle--empty { cursor: default; opacity: 0; }
                .destination-tree-toggle::before { content: '\25BC'; }
                .destination-tree-item.has-children.collapsed > .destination-tree-row .destination-tree-toggle::before { transform: rotate(-90deg); }
                .destination-tree-item.collapsed > .destination-tree-list { display: none; }
                .destination-tree-item.has-children.collapsed > .destination-tree-toggle::before { transform: rotate(-90deg); }
                .destination-tree-item.has-children > .destination-tree-list { margin-left: 0.5rem; }
                .destination-tree-label { display: flex; align-items: center; gap: 0.4rem; cursor: pointer; margin: 0; font-size: 0.875rem; flex: 1; min-width: 0; }
                .destination-tree-label input[type=checkbox] { margin: 0; flex-shrink: 0; cursor: pointer; }
                .destination-tree-title { flex: 1; min-width: 0; }
                .destination-tree-title mark { background: #fff3cd; padding: 0 0.1em; border-radius: 2px; }
                .destination-tree-item.indeterminate > .destination-tree-row .location-checkbox { opacity: 0.85; }
                .destination-tree-item.destination-search-path .destination-tree-title[data-path]::after { content: ' › ' attr(data-path); font-size: 0.7rem; color: #6c757d; margin-left: 0.35rem; display: inline; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: bottom; }
                .destination-country-cities .destination-country-select { max-width: 100%; min-width: 280px; }
                .destination-country-multi-wrap { border: 1px solid #dee2e6; border-radius: 6px; padding: 0.75rem; background: #fafafa; }
                .destination-country-list { max-height: 220px; overflow-y: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 0.2rem 1rem; }
                @media (max-width: 768px) { .destination-country-list { grid-template-columns: 1fr; } }
                .destination-country-option-label { display: flex; align-items: center; gap: 0.35rem; cursor: pointer; margin: 0; font-size: 0.8125rem; }
                .destination-country-option-label input { margin: 0; flex-shrink: 0; }
                .destination-country-option-label:hover { color: #0d6efd; }
                .destination-country-add-wrap { display: inline-block; }
                .destination-country-autocomplete-dropdown { position: absolute; left: 0; right: 0; top: 100%; z-index: 1050; max-height: 260px; overflow-y: auto; background: #fff; border: 1px solid #dee2e6; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-top: 2px; display: none; }
                .destination-country-autocomplete-dropdown.is-open { display: block; }
                .destination-country-autocomplete-item { padding: 0.4rem 0.75rem; cursor: pointer; font-size: 0.875rem; border-bottom: 1px solid #f0f0f0; }
                .destination-country-autocomplete-item:hover { background: #e7f1ff; }
                .destination-country-autocomplete-item:last-child { border-bottom: none; }
                .destination-country-block { margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #eee; }
                .destination-country-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
                .destination-city-autocomplete-wrap { display: inline-block; }
                .destination-city-autocomplete-dropdown { position: absolute; left: 0; right: 0; top: 100%; z-index: 1050; max-height: 280px; overflow-y: auto; background: #fff; border: 1px solid #dee2e6; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-top: 2px; display: none; }
                .destination-city-autocomplete-dropdown.is-open { display: block; }
                .destination-city-autocomplete-item { padding: 0.4rem 0.75rem; cursor: pointer; font-size: 0.875rem; border-bottom: 1px solid #f0f0f0; }
                .destination-city-autocomplete-item:hover { background: #e7f1ff; }
                .destination-city-autocomplete-item:last-child { border-bottom: none; }
                .destination-cities-panel { border: 1px solid #dee2e6; border-radius: 8px; background: #f8f9fa; padding: 1rem; margin-top: 0.75rem; max-height: 380px; overflow-y: auto; }
                .destination-cities-panel.destination-cities-panel-dynamic { max-height: 420px; }
                .destination-cities-panel-header { margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #dee2e6; }
                .destination-cities-panel-title { font-size: 0.9375rem; font-weight: 600; color: #495057; }
                .destination-cities-list { display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem 1.5rem; }
                @media (max-width: 576px) { .destination-cities-list { grid-template-columns: 1fr; } }
                .destination-country-checkbox-label { grid-column: 1 / -1; margin-bottom: 0.25rem; }
                .destination-country-checkbox-label, .destination-city-checkbox-label { display: flex; align-items: center; gap: 0.4rem; cursor: pointer; margin: 0; font-size: 0.875rem; }
                .destination-country-checkbox-label input, .destination-city-checkbox-label input { margin: 0; flex-shrink: 0; }
                .destination-country-checkbox-label { font-weight: 500; color: #0d6efd; }
                .destination-city-checkbox-label:hover { color: #0d6efd; }
                </style>
                <div class="card destination-ux-card">
                    <div class="card-body destination-ux-body">
                        <div class="destination-ux-header">
                            <h4 class="destination-ux-title">Tour location</h4>
                            <p class="destination-ux-helper">Sélectionnez une ou plusieurs destinations pour ce circuit.</p>
                            <div class="destination-ux-badge-wrap">
                                <span class="badge bg-primary destination-ux-badge" id="locationCountBadge">
                                    <span id="locationCountText">{{ count($selectedLocationIds ?? []) }} destination(s) sélectionnée(s)</span>
                                </span>
                            </div>
                        </div>

                        {{-- Sélections actuelles (chips) --}}
                        <div class="destination-ux-chips-section">
                            <div class="destination-ux-chips-label">Sélections actuelles</div>
                            <div class="destination-ux-chips" id="locationChipsContainer"></div>
                            <button type="button" class="btn btn-sm btn-outline-secondary destination-ux-chips-clear" id="locationChipsClear" style="display: none;">Effacer tout</button>
                        </div>

                        {{-- Tous les pays du monde + catalogue villes (world_cities + WP, création à la volée) --}}
                        <div id="locationTreeContainer">
                            @include('admin.circuits.voyages.partials.location-country-cities', [
                                'worldCountries' => $worldCountries ?? [],
                                'countryCitiesData' => $countryCitiesData ?? [],
                                'mergedCitiesByCode' => $mergedCitiesByCode ?? [],
                                'selectedLocationIds' => $selectedLocationIds ?? []
                            ])
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Localisation & Carte</h4>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Adresse complète</label>
                                    <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $meta['address'] ?? '') }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="id_location" class="form-label">ID Location</label>
                                    <input type="number" class="form-control" id="id_location" name="id_location" value="{{ old('id_location', $meta['id_location'] ?? '') }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="location_id" class="form-label">Location ID (alias)</label>
                                    <input type="number" class="form-control" id="location_id" name="location_id" value="{{ old('location_id', $meta['location_id'] ?? '') }}">
                                    <small class="text-muted">Champ optionnel, peut rester vide</small>
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="map_lat" class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="map_lat" name="map_lat" value="{{ old('map_lat', $meta['map_lat'] ?? '') }}" placeholder="Ex: 33.5731">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="map_lng" class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="map_lng" name="map_lng" value="{{ old('map_lng', $meta['map_lng'] ?? '') }}" placeholder="Ex: -7.5898">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="map_zoom" class="form-label">Zoom carte</label>
                                    <input type="number" class="form-control" id="map_zoom" name="map_zoom" value="{{ old('map_zoom', $meta['map_zoom'] ?? '14') }}" min="1" max="20">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="map_type" class="form-label">Type de carte</label>
                                    <select class="form-select" id="map_type" name="map_type">
                                        <option value="roadmap" {{ old('map_type', $meta['map_type'] ?? '') === 'roadmap' ? 'selected' : '' }}>Roadmap</option>
                                        <option value="satellite" {{ old('map_type', $meta['map_type'] ?? '') === 'satellite' ? 'selected' : '' }}>Satellite</option>
                                        <option value="hybrid" {{ old('map_type', $meta['map_type'] ?? '') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                        <option value="terrain" {{ old('map_type', $meta['map_type'] ?? '') === 'terrain' ? 'selected' : '' }}>Terrain</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="st_google_map" class="form-label">Google Map (iframe code)</label>
                            <textarea class="form-control" id="st_google_map" name="st_google_map" rows="4">{{ old('st_google_map', $meta['st_google_map'] ?? '') }}</textarea>
                            <small class="text-muted">Collez le code iframe complet de Google Maps</small>
                        </div>
                        
                        <h5 class="mb-3 mt-4">Informations de contact</h5>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">Email de contact</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="{{ old('contact_email', $meta['contact_email'] ?? '') }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $meta['phone'] ?? '') }}">
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="fax" class="form-label">Fax</label>
                                    <input type="text" class="form-control" id="fax" name="fax" value="{{ old('fax', $meta['fax'] ?? '') }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="website" class="form-label">Site web</label>
                                    <input type="text" class="form-control" id="website" name="website" value="{{ old('website', $meta['website'] ?? '') }}" placeholder="https://...">
                                    <small class="text-muted">Optionnel</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 3: TARIFICATION — Prix et catégories --}}
            <div class="tab-pane" id="tarification" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Paramètres de prix</h4>
                        
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="min_price" class="form-label">Prix minimum (MAD)</label>
                                    <input type="number" class="form-control" id="min_price" name="min_price" value="{{ old('min_price', $meta['min_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="base_price" class="form-label">Prix de base (MAD)</label>
                                    <input type="number" class="form-control" id="base_price" name="base_price" value="{{ old('base_price', $meta['base_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sale_price" class="form-label">Prix soldé (MAD)</label>
                                    <input type="number" class="form-control" id="sale_price" name="sale_price" value="{{ old('sale_price', $meta['sale_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="adult_price" class="form-label">Prix Adulte (MAD)</label>
                                    <input type="number" class="form-control" id="adult_price" name="adult_price" value="{{ old('adult_price', $meta['adult_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="child_price" class="form-label">Prix Enfant (MAD)</label>
                                    <input type="number" class="form-control" id="child_price" name="child_price" value="{{ old('child_price', $meta['child_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="infant_price" class="form-label">Prix Bébé (MAD)</label>
                                    <input type="number" class="form-control" id="infant_price" name="infant_price" value="{{ old('infant_price', $meta['infant_price'] ?? '') }}" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label for="discount" class="form-label">Réduction</label>
                                    <input type="text" class="form-control" id="discount" name="discount" value="{{ old('discount', $meta['discount'] ?? '') }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="discount_type" class="form-label">Type de réduction</label>
                                    <select class="form-select" id="discount_type" name="discount_type">
                                        <option value="">Aucune</option>
                                        <option value="percent" {{ old('discount_type', $meta['discount_type'] ?? '') === 'percent' ? 'selected' : '' }}>Pourcentage</option>
                                        <option value="fixed" {{ old('discount_type', $meta['discount_type'] ?? '') === 'fixed' ? 'selected' : '' }}>Montant fixe</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="discount_by_people_type" class="form-label">Réduction selon type personne</label>
                                    <input type="text" class="form-control" id="discount_by_people_type" name="discount_by_people_type" value="{{ old('discount_by_people_type', $meta['discount_by_people_type'] ?? '') }}" placeholder="adult,child,infant">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="calculator_discount_by_people_type" class="form-label">Calculateur réduction</label>
                                    <input type="text" class="form-control" id="calculator_discount_by_people_type" name="calculator_discount_by_people_type" value="{{ old('calculator_discount_by_people_type', $meta['calculator_discount_by_people_type'] ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <h4 class="card-title mb-4 mt-5">Catégories & Taxonomies</h4>
                        <p class="text-muted">Ces catégories sont synchronisées avec WordPress</p>
                        
                        <div class="row">
                            @if(isset($availableTaxonomies['st_tour_type']) && $availableTaxonomies['st_tour_type']->isNotEmpty())
                            <div class="col-lg-3">
                                <h5 class="mb-3">Type de tour</h5>
                                @foreach($availableTaxonomies['st_tour_type'] as $term)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="st_tour_type[]" value="{{ $term->term_id }}" id="st_tour_type_{{ $term->term_id }}" {{ in_array($term->term_id, $assignedTaxonomies['st_tour_type'] ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="st_tour_type_{{ $term->term_id }}">
                                        {{ $term->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            
                            @if(isset($availableTaxonomies['durations']) && $availableTaxonomies['durations']->isNotEmpty())
                            <div class="col-lg-3">
                                <h5 class="mb-3">Durée</h5>
                                @foreach($availableTaxonomies['durations'] as $term)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="durations[]" value="{{ $term->term_id }}" id="durations_{{ $term->term_id }}" {{ in_array($term->term_id, $assignedTaxonomies['durations'] ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="durations_{{ $term->term_id }}">
                                        {{ $term->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            
                            @if(isset($availableTaxonomies['language']) && $availableTaxonomies['language']->isNotEmpty())
                            <div class="col-lg-3">
                                <h5 class="mb-3">Langue (language)</h5>
                                @foreach($availableTaxonomies['language'] as $term)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="language[]" value="{{ $term->term_id }}" id="language_{{ $term->term_id }}" {{ in_array($term->term_id, $assignedTaxonomies['language'] ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="language_{{ $term->term_id }}">
                                        {{ $term->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            
                            @if(isset($availableTaxonomies['languages']) && $availableTaxonomies['languages']->isNotEmpty())
                            <div class="col-lg-3">
                                <h5 class="mb-3">Langues (languages)</h5>
                                @foreach($availableTaxonomies['languages'] as $term)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="languages[]" value="{{ $term->term_id }}" id="languages_{{ $term->term_id }}" {{ in_array($term->term_id, $assignedTaxonomies['languages'] ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="languages_{{ $term->term_id }}">
                                        {{ $term->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 4: DISPONIBILITÉ — Contenu & Réservation --}}
            <div class="tab-pane" id="disponibilite" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Contenu du tour</h4>
                        
                        <div class="mb-3">
                            <label for="tours_include" class="form-label">Ce qui est inclus</label>
                            <textarea class="form-control" id="tours_include" name="tours_include" rows="6">{{ old('tours_include', $meta['tours_include'] ?? '') }}</textarea>
                            <small class="text-muted">HTML accepté</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tours_exclude" class="form-label">Ce qui n'est pas inclus</label>
                            <textarea class="form-control" id="tours_exclude" name="tours_exclude" rows="6">{{ old('tours_exclude', $meta['tours_exclude'] ?? '') }}</textarea>
                            <small class="text-muted">HTML accepté</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tours_highlight" class="form-label">Points forts</label>
                            <textarea class="form-control" id="tours_highlight" name="tours_highlight" rows="6">{{ old('tours_highlight', $meta['tours_highlight'] ?? '') }}</textarea>
                            <small class="text-muted">HTML accepté</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tours_faq" class="form-label">FAQ</label>
                            <textarea class="form-control" id="tours_faq" name="tours_faq" rows="6">{{ old('tours_faq', $meta['tours_faq'] ?? '') }}</textarea>
                            <small class="text-muted">HTML accepté</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tours_program_style" class="form-label">Style du programme</label>
                            <select class="form-select" id="tours_program_style" name="tours_program_style">
                                <option value="">Défaut</option>
                                <option value="tab" {{ old('tours_program_style', $meta['tours_program_style'] ?? '') === 'tab' ? 'selected' : '' }}>Onglets</option>
                                <option value="accordion" {{ old('tours_program_style', $meta['tours_program_style'] ?? '') === 'accordion' ? 'selected' : '' }}>Accordéon</option>
                                <option value="list" {{ old('tours_program_style', $meta['tours_program_style'] ?? '') === 'list' ? 'selected' : '' }}>Liste</option>
                            </select>
                        </div>

                        <h4 class="card-title mb-4 mt-5">Disponibilité & Réservation</h4>
                        
                        <div class="mb-3">
                            <label for="tours_booking_period" class="form-label">Période de réservation</label>
                            <input type="text" class="form-control" id="tours_booking_period" name="tours_booking_period" value="{{ old('tours_booking_period', $meta['tours_booking_period'] ?? '') }}">
                        </div>
                        
                        <div class="mb-3">
                            <label for="st_booking_option_type" class="form-label">Type d'option de réservation</label>
                            <input type="text" class="form-control" id="st_booking_option_type" name="st_booking_option_type" value="{{ old('st_booking_option_type', $meta['st_booking_option_type'] ?? '') }}">
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="check_in" class="form-label">Check-in (heure)</label>
                                    <input type="time" class="form-control" id="check_in" name="check_in" value="{{ old('check_in', $meta['check_in'] ?? '') }}">
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="check_out" class="form-label">Check-out (heure)</label>
                                    <input type="time" class="form-control" id="check_out" name="check_out" value="{{ old('check_out', $meta['check_out'] ?? '') }}">
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="mb-3 mt-4">Politique d'annulation</h5>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="st_allow_cancel" name="st_allow_cancel" value="1" {{ old('st_allow_cancel', $meta['st_allow_cancel'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="st_allow_cancel">
                                Autoriser l'annulation
                            </label>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="st_cancel_percent" class="form-label">% de remboursement</label>
                                    <input type="number" class="form-control" id="st_cancel_percent" name="st_cancel_percent" value="{{ old('st_cancel_percent', $meta['st_cancel_percent'] ?? '') }}" min="0" max="100">
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="st_cancel_number_day" class="form-label">Nombre de jours avant départ</label>
                                    <input type="number" class="form-control" id="st_cancel_number_day" name="st_cancel_number_day" value="{{ old('st_cancel_number_day', $meta['st_cancel_number_day'] ?? '') }}" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <h5 class="mb-3 mt-4">iCal Sync</h5>
                        
                        <div class="mb-3">
                            <label for="ical_url" class="form-label">URL calendrier iCal</label>
                            <input type="text" class="form-control" id="ical_url" name="ical_url" value="{{ old('ical_url', $meta['ical_url'] ?? '') }}" placeholder="https://...">
                            <small class="text-muted">Optionnel</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 5: DÉPARTS•VOLS•DATES — Consolidated logistics --}}
            <div class="tab-pane" id="departs-vols-dates" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4"><i class="bx bx-trip"></i> Configuration des départs, vols et dates</h4>
                        
                        {{-- Section 1: Dates disponibles --}}
                        <h5 class="mb-3"><i class="bx bx-calendar-check"></i> Dates disponibles (Travelling on)</h5>
                        <p class="alert alert-info py-2 mb-3 small">
                            <i class="bx bx-info-circle"></i> <strong>Configuration des dates</strong> — 
                            Ajoutez les dates disponibles pour ce voyage. Seules ces dates seront sélectionnables dans le calendrier sur la page du tour.
                        </p>

                        <div id="travel-dates-container">
                            @php $datesList = $travelDates ?? collect(); @endphp
                            @forelse($datesList as $di => $dateItem)
                            <div class="card mb-2 bg-light travel-date-row" data-index="{{ $di }}">
                                <div class="card-body py-2">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3">
                                            <label class="form-label small mb-1">Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control form-control-sm" name="travel_dates[{{ $di }}][date]" value="{{ old("travel_dates.{$di}.date", optional($dateItem)->date ? $dateItem->date->format('Y-m-d') : '') }}" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-1">Places</label>
                                            <input type="number" class="form-control form-control-sm" name="travel_dates[{{ $di }}][seats]" value="{{ old("travel_dates.{$di}.seats", $dateItem->seats ?? '') }}" min="0" placeholder="Illimité">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-1">Prix spécifique</label>
                                            <input type="number" step="0.01" class="form-control form-control-sm" name="travel_dates[{{ $di }}][price_override]" value="{{ old("travel_dates.{$di}.price_override", $dateItem->price_override ?? '') }}" placeholder="Prix">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end pb-2">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="travel_dates[{{ $di }}][is_active]" value="1" {{ old("travel_dates.{$di}.is_active", $dateItem->is_active ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label small">Actif</label>
                                            </div>
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end pb-2">
                                            @if($di > 0)<button type="button" class="btn btn-sm btn-outline-danger remove-travel-date" aria-label="Supprimer">×</button>@endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="alert alert-warning">
                                Aucune date disponible configurée. Cliquez sur "Ajouter une date" pour commencer.
                            </div>
                            @endforelse
                        </div>
                        <button type="button" class="btn btn-sm btn-soft-primary mb-4" id="add-travel-date">
                            <i class="bx bx-plus"></i> Ajouter une date
                        </button>

                        {{-- Section 2: Départ & Vol --}}
                        <h5 class="mb-3 mt-5"><i class="bx bx-trip"></i> Départ & Vol principal</h5>
                        @php
                            $depPlaceIdVal = old('departure_place_id', $voyage->departure_place_id ?? $meta['departure_place_id'] ?? '');
                            $depDateVal = old('departure_date');
                            if ($depDateVal === null || $depDateVal === '') {
                                $rawDepDate = $voyage->departure_date ?? $meta['departure_date'] ?? $meta['start_date'] ?? '';
                                $depDateVal = '';
                                if ($rawDepDate !== '' && $rawDepDate !== null) {
                                    try {
                                        $depDateVal = \Carbon\Carbon::parse($rawDepDate)->format('Y-m-d\TH:i');
                                    } catch (\Exception $e) {
                                        $depDateVal = '';
                                    }
                                }
                            }
                            $flightIdVal = old('flight_id', $voyage->flight_id ?? $meta['flight_id'] ?? '');
                            $departurePlacesForSelect = $departurePlaces ?? collect();
                        @endphp

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="departure_place_id" class="form-label">Lieu de départ</label>
                                <select class="form-select @error('departure_place_id') is-invalid @enderror" id="departure_place_id" name="departure_place_id" aria-describedby="departure_place_id_help">
                                    <option value="">— Sélectionner —</option>
                                    @foreach($departurePlacesForSelect as $place)
                                        <option value="{{ $place->id ?? '' }}" {{ (string)($place->id ?? '') === (string)$depPlaceIdVal ? 'selected' : '' }}>
                                            {{ $place->name ?? '' }}{{ isset($place->code) && $place->code !== '' ? ' (' . $place->code . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small id="departure_place_id_help" class="form-text text-muted">Aéroport / ville de départ.</small>
                                @error('departure_place_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="departure_date" class="form-label">Date et heure de départ</label>
                                <input type="datetime-local" class="form-control @error('departure_date') is-invalid @enderror" id="departure_date" name="departure_date" value="{{ $depDateVal }}" aria-describedby="departure_date_help">
                                <small id="departure_date_help" class="form-text text-muted">Format date et heure.</small>
                                @error('departure_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label for="flight_id" class="form-label">Vol</label>
                                <select class="form-select @error('flight_id') is-invalid @enderror" id="flight_id" name="flight_id" aria-describedby="flight_id_help">
                                    <option value="">— Sélectionner —</option>
                                    @foreach($departurePlacesForSelect as $place)
                                        @foreach($place->flights ?? [] as $fl)
                                            @if(isset($fl->id))
                                                <option value="{{ $fl->id }}" {{ (string)$fl->id === (string)$flightIdVal ? 'selected' : '' }}>
                                                    {{ $place->name ?? '' }} — {{ $fl->airline ?? '' }} {{ $fl->flight_number ?? '' }}{{ isset($fl->from_airport) || isset($fl->to_airport) ? ' (' . ($fl->from_airport ?? '') . ' → ' . ($fl->to_airport ?? '') . ')' : '' }}
                                                </option>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </select>
                                <small id="flight_id_help" class="form-text text-muted">Vol associé au départ. Laisser vide pour « Vol à confirmer ».</small>
                                @error('flight_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Section 3: Lieux de départ (Starting from) --}}
                        <h5 class="mb-3 mt-5"><i class="bx bx-map-pin"></i> Lieux de départ (Starting from)</h5>

                        <div id="departure-places-container">
                            @php $placesList = $departurePlaces->isEmpty() ? [] : $departurePlaces->all(); @endphp
                            @forelse($placesList as $pi => $place)
                            <div class="card mb-3 departure-place-row" data-index="{{ $pi }}">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <strong>Lieu de départ {{ $pi + 1 }}</strong>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-departure-place" aria-label="Supprimer">×</button>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nom du lieu <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="departure_places[{{ $pi }}][name]" value="{{ old("departure_places.{$pi}.name", $place->name ?? '') }}" placeholder="Ex. Casablanca" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Code (IATA)</label>
                                            <input type="text" class="form-control" name="departure_places[{{ $pi }}][code]" value="{{ old("departure_places.{$pi}.code", $place->code ?? '') }}" placeholder="Ex. CMN">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end pb-2">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="departure_places[{{ $pi }}][is_active]" value="1" {{ old("departure_places.{$pi}.is_active", $place->is_active ?? true) ? 'checked' : '' }}>
                                                <label class="form-check-label">Actif</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="alert alert-warning">
                                Aucun lieu de départ configuré. Cliquez sur "Ajouter un lieu de départ" pour commencer.
                            </div>
                            @endforelse
                        </div>
                        <button type="button" class="btn btn-sm btn-soft-primary mb-4" id="add-departure-place">
                            <i class="bx bx-plus"></i> Ajouter un lieu de départ
                        </button>

                        <script>
                        // Travel dates management
                        (function(){
                            var container = document.getElementById('travel-dates-container');
                            var addBtn = document.getElementById('add-travel-date');
                            if (!container || !addBtn) return;
                            if (container.dataset.initialized === 'true') return;
                            container.dataset.initialized = 'true';

                            addBtn.addEventListener('click', function(){
                                var rows = container.querySelectorAll('.travel-date-row');
                                var nextIndex = rows.length;
                                var html = `<div class="card mb-2 bg-light travel-date-row" data-index="${nextIndex}">
                                    <div class="card-body py-2">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1">Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control form-control-sm" name="travel_dates[${nextIndex}][date]" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Places</label>
                                                <input type="number" class="form-control form-control-sm" name="travel_dates[${nextIndex}][seats]" min="0" placeholder="Illimité">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Prix spécifique</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm" name="travel_dates[${nextIndex}][price_override]" placeholder="Prix">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end pb-2">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" name="travel_dates[${nextIndex}][is_active]" value="1" checked>
                                                    <label class="form-check-label small">Actif</label>
                                                </div>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end pb-2">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-travel-date" aria-label="Supprimer">×</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                                container.insertAdjacentHTML('beforeend', html);
                            });

                            container.addEventListener('click', function(e){
                                if (e.target.classList.contains('remove-travel-date')) {
                                    var row = e.target.closest('.travel-date-row');
                                    if (row) row.remove();
                                }
                            });
                        })();

                        // Departure places management
                        (function(){
                            var container = document.getElementById('departure-places-container');
                            var addPlaceBtn = document.getElementById('add-departure-place');
                            if (!container || !addPlaceBtn) return;
                            if (container.dataset.initialized === 'true') return;
                            container.dataset.initialized = 'true';

                            addPlaceBtn.addEventListener('click', function(){
                                var alert = container.querySelector('.alert-warning');
                                if (alert) alert.remove();
                                
                                var rows = container.querySelectorAll('.departure-place-row');
                                var nextIndex = rows.length;
                                var html = `<div class="card mb-3 departure-place-row" data-index="${nextIndex}">
                                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                        <strong>Lieu de départ ${nextIndex + 1}</strong>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-departure-place" aria-label="Supprimer">×</button>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Nom du lieu <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="departure_places[${nextIndex}][name]" placeholder="Ex. Casablanca" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Code (IATA)</label>
                                                <input type="text" class="form-control" name="departure_places[${nextIndex}][code]" placeholder="Ex. CMN">
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end pb-2">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" name="departure_places[${nextIndex}][is_active]" value="1" checked>
                                                    <label class="form-check-label">Actif</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                                container.insertAdjacentHTML('beforeend', html);
                            });

                            container.addEventListener('click', function(e){
                                if (e.target.classList.contains('remove-departure-place')) {
                                    var row = e.target.closest('.departure-place-row');
                                    if (row) {
                                        row.remove();
                                        var remainingRows = container.querySelectorAll('.departure-place-row');
                                        if (remainingRows.length === 0) {
                                            var alertHtml = '<div class="alert alert-warning">Aucun lieu de départ configuré. Cliquez sur "Ajouter un lieu de départ" pour commencer.</div>';
                                            container.insertAdjacentHTML('beforeend', alertHtml);
                                        }
                                    }
                                }
                            });
                        })();
                        </script>
                    </div>
                </div>
            </div>

            <div class="tab-pane" id="logistique" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Images & Vidéos</h4>

                        {{-- Section 1 : Image principale (Hero / Cover) — Upload ou médiathèque --}}
                        <div class="mb-4 p-3 border rounded bg-light">
                            <h5 class="mb-2">Image principale du voyage (Hero / Cover)</h5>
                            <p class="text-muted small mb-2">Cette image est utilisée comme image principale du voyage (hero, cartes, partage social). Une seule image.</p>
                            <input type="hidden" name="hero_image_id" id="hero_image_id" value="{{ old('hero_image_id', $meta['hero_image_id'] ?? '') }}">
                            <div class="d-flex flex-wrap align-items-start gap-3">
                                <div id="hero-image-preview-wrap" class="border rounded overflow-hidden bg-white" style="width: 200px; min-height: 120px; display: {{ ($heroImageUrl ?? '') ? 'block' : 'none' }};">
                                    <img id="hero-image-preview" src="{{ $heroImageUrl ?? '' }}" alt="Hero" class="img-fluid" style="max-height: 200px; object-fit: cover;">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm me-2" id="hero-upload-btn">
                                            <i class="bx bx-upload"></i> Uploader une image
                                        </button>
                                        <input type="file" id="hero_image_file" accept="image/jpeg,image/png,image/webp" class="d-none">
                                        <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="hero-choose-media-btn">
                                            <i class="bx bx-images"></i> Choisir depuis la médiathèque
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="hero-remove-btn">
                                            <i class="bx bx-trash"></i> Supprimer
                                        </button>
                                    </div>
                                    <small class="text-muted d-block">JPG, PNG ou WebP — max 5 Mo.</small>
                                    <div id="hero-upload-error" class="alert alert-danger mt-2 mb-0 d-none" role="alert"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="gallery_ids" class="form-label">Galerie générale (images supplémentaires)</label>
                            <input type="text" class="form-control" id="gallery_ids" name="gallery_ids" value="{{ old('gallery_ids', $gallery_csv ?? '') }}" placeholder="14435,14436,14437">
                            <small class="text-muted">IDs séparés par des virgules. Images supplémentaires pour la section galerie complète (optionnel).</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="video" class="form-label">URL Vidéo</label>
                            <input type="text" class="form-control" id="video" name="video" value="{{ old('video', $meta['video'] ?? '') }}" placeholder="https://www.youtube.com/watch?v=...">
                            <small class="text-muted">YouTube, Vimeo, etc. (Optionnel)</small>
                        </div>

                        <h4 class="card-title mb-4 mt-5">Moyens de paiement</h4>
                        <p class="text-muted">Cochez les passerelles de paiement disponibles pour ce tour</p>
                        
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_paypal" name="is_meta_payment_gateway_st_paypal" value="1" {{ old('is_meta_payment_gateway_st_paypal', $meta['is_meta_payment_gateway_st_paypal'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_paypal">
                                        <i class="bx bxl-paypal"></i> PayPal
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_onepay" name="is_meta_payment_gateway_st_onepay" value="1" {{ old('is_meta_payment_gateway_st_onepay', $meta['is_meta_payment_gateway_st_onepay'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_onepay">
                                        OnePay
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_onepay_atm" name="is_meta_payment_gateway_st_onepay_atm" value="1" {{ old('is_meta_payment_gateway_st_onepay_atm', $meta['is_meta_payment_gateway_st_onepay_atm'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_onepay_atm">
                                        OnePay ATM
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_payu" name="is_meta_payment_gateway_st_payu" value="1" {{ old('is_meta_payment_gateway_st_payu', $meta['is_meta_payment_gateway_st_payu'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_payu">
                                        PayU
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_payulatam" name="is_meta_payment_gateway_st_payulatam" value="1" {{ old('is_meta_payment_gateway_st_payulatam', $meta['is_meta_payment_gateway_st_payulatam'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_payulatam">
                                        PayU Latam
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_payumoney" name="is_meta_payment_gateway_st_payumoney" value="1" {{ old('is_meta_payment_gateway_st_payumoney', $meta['is_meta_payment_gateway_st_payumoney'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_payumoney">
                                        PayUmoney
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_razor" name="is_meta_payment_gateway_st_razor" value="1" {{ old('is_meta_payment_gateway_st_razor', $meta['is_meta_payment_gateway_st_razor'] ?? '') === 'on' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_meta_payment_gateway_st_razor">
                                        <i class="bx bx-credit-card"></i> Razorpay
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Hôtels consolidés --}}
                        <h4 class="card-title mb-4 mt-5">Hôtels</h4>
                        @php
                            $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                        @endphp
                        <div id="tour-hotels-container">
                            @php $hotelsList = isset($tourHotels) && $tourHotels->isNotEmpty() ? $tourHotels->all() : [null]; @endphp
                            @foreach($hotelsList as $hi => $h)
                            <div class="card mb-3 tour-hotel-row" data-index="{{ $hi }}">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <strong>Hôtel {{ $hi + 1 }}</strong>
                                    @if($hi > 0)<button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.tour-hotel-row').remove()">×</button>@endif
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nom de l'hôtel</label>
                                            <input type="text" class="form-control" name="tour_hotels[{{ $hi }}][hotel_name]" value="{{ old("tour_hotels.{$hi}.hotel_name", optional($h)->hotel_name ?? '') }}" placeholder="Ex. Hôtel Les Almoravides">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Étoiles (0–5)</label>
                                            <input type="number" class="form-control" name="tour_hotels[{{ $hi }}][stars]" value="{{ old("tour_hotels.{$hi}.stars", optional($h)->stars ?? '') }}" min="0" max="5" placeholder="3">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Jour</label>
                                            <select class="form-select" name="tour_hotels[{{ $hi }}][day_number]">
                                                @for($d = 1; $d <= $lastDayNumber; $d++)
                                                    <option value="{{ $d }}" {{ old("tour_hotels.{$hi}.day_number", optional($h)->day_number ?? 1) == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Adresse</label>
                                            <input type="text" class="form-control" name="tour_hotels[{{ $hi }}][address]" value="{{ old("tour_hotels.{$hi}.address", optional($h)->address ?? '') }}" placeholder="Ville, pays">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane" id="programme" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Programme du voyage</h4>
                        
                        {{-- Programme par jours --}}
                        <div class="form-group mb-5">
                            <div class="row mb-3">
                                <div class="col">
                                    <h5 class="mb-0">Jours et activités détaillées</h5>
                                    <p class="text-muted">Créez le programme jour par jour avec les activités correspondantes.</p>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-success btn-sm" id="add-program-day">
                                        <i class="bx bx-plus"></i> Ajouter un jour
                                    </button>
                                </div>
                            </div>

                            <div id="program-days-container">
                                @php
                                    $programDaysList = isset($programDays) && $programDays->isNotEmpty() ? $programDays : collect([null]);
                                @endphp
                                @foreach($programDaysList as $index => $dayData)
                                <div class="program-day-item border rounded p-3 mb-3" data-day-index="{{ $index }}">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">
                                            <i class="bx bx-calendar"></i> 
                                            Jour <span class="day-number">{{ $index + 1 }}</span>
                                        </h6>
                                        @if($index > 0)
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-day" onclick="removeDay(this)">
                                            <i class="bx bx-trash"></i> Supprimer jour
                                        </button>
                                        @endif
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-8">
                                            <label class="form-label">Titre du jour</label>
                                            <input type="text" class="form-control" name="program_days[{{ $index }}][title]" 
                                                   value="{{ old("program_days.{$index}.title", optional($dayData)->title ?? '') }}" 
                                                   placeholder="Ex. Arrivée à Marrakech - Découverte de la médina">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Ville - Pays</label>
                                            <input type="text" class="form-control" name="program_days[{{ $index }}][location]" 
                                                   value="{{ old("program_days.{$index}.location", optional($dayData)->location ?? '') }}" 
                                                   placeholder="Marrakech - Maroc">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description du jour</label>
                                        <textarea class="form-control" rows="3" name="program_days[{{ $index }}][description]" 
                                                  placeholder="Description détaillée des activités et événements de cette journée...">{{ old("program_days.{$index}.description", optional($dayData)->description ?? '') }}</textarea>
                                    </div>

                                    {{-- Section Activités du jour --}}
                                    <div class="bg-light p-3 rounded">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Activités de ce jour</strong>
                                            <button type="button" class="btn btn-sm btn-outline-success add-activity" data-day-index="{{ $index }}">
                                                <i class="bx bx-plus"></i> Ajouter activité
                                            </button>
                                        </div>

                                        <div class="activities-container" data-day-index="{{ $index }}">
                                            @php
                                                $dayActivities = isset($activitiesByDay[$index]) ? $activitiesByDay[$index] : [null];
                                            @endphp
                                            @foreach($dayActivities as $actIndex => $activity)
                                            <div class="activity-item border rounded p-2 mb-2 bg-white" data-activity-index="{{ $actIndex }}">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Nom de l'activité</label>
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="program_days[{{ $index }}][activities][{{ $actIndex }}][name]" 
                                                               value="{{ old("program_days.{$index}.activities.{$actIndex}.name", optional($activity)->name ?? '') }}" 
                                                               placeholder="Ex. Visite des Jardins Majorelle">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small">Heure</label>
                                                        <input type="time" class="form-control form-control-sm" 
                                                               name="program_days[{{ $index }}][activities][{{ $actIndex }}][time]" 
                                                               value="{{ old("program_days.{$index}.activities.{$actIndex}.time", optional($activity)->time ?? '') }}">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small">Durée (min)</label>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="program_days[{{ $index }}][activities][{{ $actIndex }}][duration]" 
                                                               value="{{ old("program_days.{$index}.activities.{$actIndex}.duration", optional($activity)->duration ?? '') }}" 
                                                               placeholder="120">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small">Description courte</label>
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="program_days[{{ $index }}][activities][{{ $actIndex }}][description]" 
                                                               value="{{ old("program_days.{$index}.activities.{$actIndex}.description", optional($activity)->description ?? '') }}" 
                                                               placeholder="Activité culturelle">
                                                    </div>
                                                    <div class="col-md-1">
                                                        @if($actIndex > 0 || count($dayActivities) > 1)
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeActivity(this)">×</button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Section Notes et informations --}}
                        <div class="row">
                            <div class="col-md-6">
                                <label for="inclusions" class="form-label">Inclusions</label>
                                <textarea class="form-control" id="inclusions" name="inclusions" rows="4" placeholder="Liste des éléments inclus dans le tour...">{{ old('inclusions', $meta['inclusions'] ?? '') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="exclusions" class="form-label">Exclusions</label>
                                <textarea class="form-control" id="exclusions" name="exclusions" rows="4" placeholder="Liste des éléments non inclus...">{{ old('exclusions', $meta['exclusions'] ?? '') }}</textarea>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="additional_information" class="form-label">Informations complémentaires</label>
                                <textarea class="form-control" id="additional_information" name="additional_information" rows="3" placeholder="Informations importantes à communiquer...">{{ old('additional_information', $meta['additional_information'] ?? '') }}</textarea>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="note" class="form-label">Note</label>
                                <textarea class="form-control" id="note" name="note" rows="3" placeholder="Note interne ou recommandations...">{{ old('note', $meta['note'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 8: BROUILLON --}}
            <div class="tab-pane" id="brouillon" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Brouillon et statut</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_draft" name="is_draft" value="1" {{ old('is_draft', $voyage->post_status ?? 'publish') === 'draft' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_draft">
                                        <i class="bx bx-edit"></i> Enregistrer comme brouillon
                                    </label>
                                    <small class="form-text text-muted d-block">Si coché, ce voyage ne sera pas visible publiquement.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        // Gestion des onglets Bootstrap 5
        document.addEventListener('DOMContentLoaded', function() {
            // Activation des onglets
            var tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    var tab = new bootstrap.Tab(this);
                    tab.show();
                });
            });
            
            // Ouvrir un onglet spécifique via URL (?tab=nom)
            var params = new URLSearchParams(window.location.search);
            if (params.get('tab')) {
                var tabName = params.get('tab');
                var tabEl = document.querySelector('a[href="#' + tabName + '"]');
                if (tabEl) {
                    var tab = new bootstrap.Tab(tabEl);
                    tab.show();
                }
            }
        });
    </script>
@endpush
