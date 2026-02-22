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
                <a class="nav-link" data-bs-toggle="tab" href="#location" role="tab">
                    <i class="bx bx-map"></i> Location
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#price" role="tab">
                    <i class="bx bx-dollar"></i> Prix
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#information" role="tab">
                    <i class="bx bx-info-circle"></i> Information & Paiement
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#availability" role="tab">
                    <i class="bx bx-calendar"></i> Disponibilité
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#media" role="tab">
                    <i class="bx bx-image"></i> Médias
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#taxonomies" role="tab">
                    <i class="bx bx-category"></i> Catégories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#flights" role="tab">
                    <i class="bx bx-trip"></i> Vols
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#hotels" role="tab">
                    <i class="bx bx-hotel"></i> Hôtels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#transfers" role="tab">
                    <i class="bx bx-car"></i> Transferts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#departure-places" role="tab">
                    <i class="bx bx-map-pin"></i> Lieux de départ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#departure-and-flight" role="tab">
                    <i class="bx bx-trip"></i> Départ & Vol
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#travel-dates" role="tab">
                    <i class="bx bx-calendar-check"></i> Dates disponibles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#activities" role="tab">
                    <i class="bx bx-list-check"></i> Activités
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#program-days" role="tab">
                    <i class="bx bx-calendar-week"></i> Programme
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

            {{-- TAB 2: LOCATION — Destination UX (search, tree, chips, actions) --}}
            <div class="tab-pane" id="location" role="tabpanel">
                <style>
                .destination-ux-card { border: 1px solid #dee2e6; border-radius: 6px; }
                .destination-ux-body { padding: 1rem 1.25rem; }
                .destination-ux-header { margin-bottom: 1rem; }
                .destination-ux-title { font-size: 1.1rem; font-weight: 600; color: #212529; margin: 0 0 0.25rem 0; }
                .destination-ux-helper { font-size: 0.8125rem; color: #6c757d; margin: 0 0 0.5rem 0; }
                
                /* Styles pour le résumé du jour */
                .day-summary-container { margin-top: 0.5rem; }
                .day-summary-card {
                    border: 1px solid #dee2e6;
                    border-radius: 6px;
                    padding: 0.75rem;
                    background-color: #f8f9fa;
                    transition: all 0.2s ease;
                }
                .day-summary-card:hover {
                    border-color: #adb5bd;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .day-summary-card .btn-xs {
                    padding: 0.125rem 0.375rem;
                    font-size: 0.75rem;
                    line-height: 1.2;
                }
                .day-summary-card .badge {
                    font-size: 0.7rem;
                    padding: 0.2em 0.4em;
                }
                .day-summary-card strong {
                    font-size: 0.875rem;
                    font-weight: 600;
                }
                .day-summary-card .small {
                    font-size: 0.8125rem;
                }
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
                
                /* Styles pour Flight Manager Modal */
                .modal-lg { max-width: 900px; }
                .modal-dialog-scrollable .modal-body { max-height: calc(100vh - 200px); }
                .modal-footer.sticky-bottom { position: sticky; bottom: 0; background: white; z-index: 1055; margin: 0; }
                .flight-manager[data-mode="modal"] .modal-flight-context { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .flight-manager[data-mode="modal"] .flight-section-focused { margin-bottom: 16px; }
                .flight-manager[data-mode="modal"] .flight-opt-card { transition: all 0.2s ease; }
                .flight-manager[data-mode="modal"] .flight-opt-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
                .flight-manager .flight-option-toggle { padding: 12px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; }
                .flight-manager .quick-access-flights { font-size: 11px; }
                #day-builder-drawer .nav-pills .nav-link { font-size: 13px; padding: 8px 12px; }
                #day-builder-drawer .nav-pills .nav-link.active { background: #e7f1ff; border-color: #b6d7ff; color: #0d6efd; }
                .modal-flight-validation .alert-sm { padding: 8px 12px; font-size: 12px; }
                .flight-section-focused[data-type="outbound"] { border-left: 4px solid #28a745; }
                .flight-section-focused[data-type="return"] { border-left: 4px solid #dc3545; }
                .flight-section-focused[data-type="segment"] { border-left: 4px solid #ffc107; }
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

            {{-- TAB 3: PRICE --}}
            <div class="tab-pane" id="price" role="tabpanel">
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
                    </div>
                </div>
            </div>

            {{-- TAB 4: INFORMATION --}}
            <div class="tab-pane" id="information" role="tabpanel">
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
                    </div>
                </div>
                
                {{-- Section Paiement fusionnée --}}
                <div class="card mt-3">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Moyens de paiement</h4>
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
                    </div>
                </div>
            </div>

            {{-- TAB 5: AVAILABILITY --}}
            <div class="tab-pane" id="availability" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Disponibilité & Réservation</h4>
                        
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

            {{-- TAB 6: MEDIA --}}
            <div class="tab-pane" id="media" role="tabpanel">
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

                        {{-- Option : utiliser l'image principale comme image à la une WP --}}
                        <div class="mb-3">
                            <div class="form-check">
                                @php $useHeroAsThumb = old('hero_use_as_thumbnail') !== null ? (bool) old('hero_use_as_thumbnail') : (isset($meta['hero_image_id']) && isset($meta['thumbnail_id']) && (string)$meta['hero_image_id'] === (string)$meta['thumbnail_id']); @endphp
                                <input class="form-check-input" type="checkbox" name="hero_use_as_thumbnail" value="1" id="hero_use_as_thumbnail" {{ $useHeroAsThumb ? 'checked' : '' }}>
                                <label class="form-check-label" for="hero_use_as_thumbnail">Utiliser l'image principale comme image à la une WordPress</label>
                            </div>
                        </div>

                        {{-- Section 2 : Image à la une (WP standard) + Galerie --}}
                        <div class="mb-3">
                            <label for="thumbnail_id" class="form-label">Image à la une (ID WP)</label>
                            <input type="number" class="form-control" id="thumbnail_id" name="thumbnail_id" value="{{ old('thumbnail_id', $meta['thumbnail_id'] ?? '') }}" placeholder="14434">
                            <small class="text-muted">Utilisée en secours si aucune image principale. Peut être synchronisée via la case ci-dessus.</small>
                        </div>

                        {{-- Section 3 : Galerie Hero (5 images pour la galerie hero) --}}
                        <div class="mb-4 p-3 border rounded bg-light">
                            <h5 class="mb-2">Galerie Hero (5 images)</h5>
                            <p class="text-muted small mb-3">Sélectionnez exactement 5 images pour la galerie hero (1 principale + 4 secondaires). Ces images seront affichées dans la section hero de la page détail.</p>
                            @php
                                $hero_gallery_ids = old('hero_gallery_ids', isset($meta['hero_gallery_ids']) ? explode(',', $meta['hero_gallery_ids']) : []);
                                if (is_string($hero_gallery_ids)) {
                                    $hero_gallery_ids = explode(',', $hero_gallery_ids);
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
                            <small class="text-muted d-block mt-2">
                                <i class="bx bx-info-circle"></i> 
                                L'image principale sera affichée en grand à gauche, les 4 autres en grille 2x2 à droite.
                            </small>
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
                    </div>
                </div>
            </div>

            {{-- Modal Médiathèque WP (choix image hero) --}}
            <div class="modal fade" id="hero-media-modal" tabindex="-1" aria-labelledby="hero-media-modal-label" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="hero-media-modal-label">Choisir une image depuis la médiathèque</h5>
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

            <script>
            (function() {
                var heroUploadUrl = "{{ route('admin.circuits.voyages.hero-image.upload', ['id' => $voyage->ID]) }}";
                var heroSelectUrl = "{{ route('admin.circuits.voyages.hero-image.select', ['id' => $voyage->ID]) }}";
                var heroRemoveUrl = "{{ route('admin.circuits.voyages.hero-image.remove', ['id' => $voyage->ID]) }}";
                var wpMediaSearchUrl = "{{ url('admin/wp-media/search') }}";
                var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
                var heroPreview = document.getElementById('hero-image-preview');
                var heroPreviewWrap = document.getElementById('hero-image-preview-wrap');
                var heroInput = document.getElementById('hero_image_id');
                var heroFileInput = document.getElementById('hero_image_file');

                function setHeroPreview(url, id) {
                    if (heroInput) heroInput.value = id || '';
                    if (heroPreview) heroPreview.src = url || '';
                    if (heroPreviewWrap) heroPreviewWrap.style.display = (url ? 'block' : 'none');
                }

                if (document.getElementById('hero-upload-btn')) {
                    document.getElementById('hero-upload-btn').addEventListener('click', function() { heroFileInput && heroFileInput.click(); });
                }
                if (heroFileInput) {
                    heroFileInput.addEventListener('change', function() {
                        if (!this.files || !this.files[0]) return;
                        var file = this.files[0];
                        var errEl = document.getElementById('hero-upload-error');
                        function showError(msg) {
                            if (errEl) { errEl.textContent = msg || 'Erreur lors de l\'upload.'; errEl.classList.remove('d-none'); }
                            else { alert(msg || 'Erreur lors de l\'upload.'); }
                        }
                        function hideError() { if (errEl) { errEl.textContent = ''; errEl.classList.add('d-none'); } }
                        if (!csrfToken) { showError('Token de sécurité manquant. Rechargez la page.'); heroFileInput.value = ''; return; }
                        hideError();
                        var formData = new FormData();
                        formData.append('hero_image', file);
                        formData.append('_token', csrfToken);
                        fetch(heroUploadUrl, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            }
                        }).then(function(res) {
                            return res.json().then(function(r) { return { ok: res.ok, status: res.status, data: r }; }).catch(function() {
                                return { ok: false, status: res.status, data: { message: res.status === 419 ? 'Session expirée. Rechargez la page puis réessayez.' : 'Réponse serveur invalide.' } };
                            });
                        }).then(function(result) {
                            heroFileInput.value = '';
                            if (result.ok && result.data && result.data.success) {
                                setHeroPreview(result.data.url, result.data.attachment_id);
                            } else {
                                var msg = (result.data && result.data.message) || (result.data && result.data.errors && result.data.errors.hero_image && result.data.errors.hero_image[0]) || 'Erreur lors de l\'upload.';
                                showError(msg);
                            }
                        }).catch(function() {
                            heroFileInput.value = '';
                            showError('Erreur réseau ou serveur. Vérifiez votre connexion.');
                        });
                    });
                }

                if (document.getElementById('hero-remove-btn')) {
                    document.getElementById('hero-remove-btn').addEventListener('click', function() {
                        if (!confirm('Retirer l\'image principale ?')) return;
                        var fd = new FormData();
                        if (csrfToken) fd.append('_token', csrfToken);
                        fetch(heroRemoveUrl, {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' }
                        }).then(function(r) { return r.json(); }).then(function(r) { if (r.success) setHeroPreview('', ''); });
                    });
                }

                var mediaModal = document.getElementById('hero-media-modal');
                var mediaSearch = document.getElementById('hero-media-search');
                var mediaResults = document.getElementById('hero-media-results');
                var mediaLoading = document.getElementById('hero-media-loading');
                var mediaPag = document.getElementById('hero-media-pagination');
                var mediaPage = 1;

                function loadMediaSearch(page) {
                    page = page || 1;
                    var q = (mediaSearch && mediaSearch.value) || '';
                    if (mediaLoading) mediaLoading.classList.remove('d-none');
                    if (mediaResults) mediaResults.innerHTML = '';
                    var url = wpMediaSearchUrl + '?page=' + page + '&per_page=24';
                    if (q) url += '&q=' + encodeURIComponent(q);
                    fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (mediaLoading) mediaLoading.classList.add('d-none');
                            if (!data.data || !data.data.length) {
                                if (mediaResults) mediaResults.innerHTML = '<div class="col-12 text-muted">Aucune image.</div>';
                            } else {
                                data.data.forEach(function(item) {
                                    var col = document.createElement('div');
                                    col.className = 'col-6 col-md-4 col-lg-3';
                                    col.innerHTML = '<div class="card h-100 cursor-pointer hero-media-item" data-id="' + item.id + '" data-url="' + (item.url || '') + '"><img src="' + (item.url || '') + '" class="card-img-top" style="height:120px;object-fit:cover" alt=""></div>';
                                    col.querySelector('.hero-media-item').addEventListener('click', function() {
                                        var id = this.getAttribute('data-id');
                                        var url = this.getAttribute('data-url');
                                        if (window.logistiqueMediaTarget) {
                                            var t = window.logistiqueMediaTarget;
                                            var inp = document.getElementById(t.inputId);
                                            var prev = document.getElementById(t.previewId);
                                            var wrap = document.getElementById(t.previewWrapId);
                                            if (inp) inp.value = id;
                                            if (prev) prev.src = url || '';
                                            if (wrap) wrap.style.display = 'flex';
                                            if (mediaModal && window.bootstrap) { var m = bootstrap.Modal.getInstance(mediaModal); if (m) m.hide(); }
                                            window.logistiqueMediaTarget = null;
                                        } else {
                                            var fd = new FormData();
                                            fd.append('attachment_id', id);
                                            if (csrfToken) fd.append('_token', csrfToken);
                                            fetch(heroSelectUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' } })
                                                .then(function(r) { return r.json(); })
                                                .then(function(r) {
                                                    if (r.success) { setHeroPreview(r.url, r.attachment_id); if (window.bootstrap && mediaModal) { var m = bootstrap.Modal.getInstance(mediaModal); if (m) m.hide(); } }
                                                });
                                        }
                                    });
                                    mediaResults.appendChild(col);
                                });
                            }
                            if (data.last_page > 1 && mediaPag) {
                                mediaPag.classList.remove('d-none');
                                mediaPag.innerHTML = '<ul class="pagination pagination-sm mb-0"><li class="page-item' + (data.current_page <= 1 ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (data.current_page - 1) + '">Préc.</a></li><li class="page-item"><span class="page-link">' + data.current_page + ' / ' + data.last_page + '</span></li><li class="page-item' + (data.current_page >= data.last_page ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (data.current_page + 1) + '">Suiv.</a></li></ul>';
                                mediaPag.querySelectorAll('a[data-page]').forEach(function(a) {
                                    a.addEventListener('click', function(e) { e.preventDefault(); loadMediaSearch(parseInt(this.getAttribute('data-page'), 10)); });
                                });
                            } else if (mediaPag) mediaPag.classList.add('d-none');
                        })
                        .catch(function() { if (mediaLoading) mediaLoading.classList.add('d-none'); if (mediaResults) mediaResults.innerHTML = '<div class="col-12 text-danger">Erreur chargement.</div>'; });
                }

                window.logistiqueMediaTarget = null;
                if (document.getElementById('hero-choose-media-btn')) {
                    document.getElementById('hero-choose-media-btn').addEventListener('click', function() {
                        window.logistiqueMediaTarget = null;
                        if (mediaModal && window.bootstrap) {
                            var m = new bootstrap.Modal(mediaModal);
                            m.show();
                            loadMediaSearch(1);
                        }
                    });
                }
                function bindLogistiqueMediaButtons() {
                    document.querySelectorAll('.ajtb-logistique-media-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            window.logistiqueMediaTarget = {
                                inputId: this.getAttribute('data-input'),
                                previewId: this.getAttribute('data-preview'),
                                previewWrapId: this.getAttribute('data-preview-wrap')
                            };
                            if (mediaModal && window.bootstrap) {
                                var m = new bootstrap.Modal(mediaModal);
                                m.show();
                                loadMediaSearch(1);
                            }
                        });
                    });
                    document.querySelectorAll('.ajtb-logistique-media-remove').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var inp = document.getElementById(this.getAttribute('data-input'));
                            var prev = document.getElementById(this.getAttribute('data-preview'));
                            var wrap = document.getElementById(this.getAttribute('data-preview-wrap'));
                            if (inp) inp.value = '';
                            if (prev) prev.src = '';
                            if (wrap) wrap.style.display = 'none';
                        });
                    });
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', bindLogistiqueMediaButtons);
                } else {
                    bindLogistiqueMediaButtons();
                }
                if (mediaSearch) {
                    mediaSearch.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); loadMediaSearch(1); } });
                }

                // Hero Gallery (5 images) management
                var heroGalleryCurrentIndex = null;
                var heroGalleryUploadUrl = "{{ route('admin.circuits.voyages.hero-image.upload', ['id' => $voyage->ID]) }}";
                var heroGallerySelectUrl = "{{ route('admin.circuits.voyages.hero-image.select', ['id' => $voyage->ID]) }}";

                function updateHeroGalleryHidden() {
                    var ids = [];
                    document.querySelectorAll('.hero-gallery-id-input').forEach(function(input) {
                        var val = input.value.trim();
                        if (val) ids.push(val);
                    });
                    var hiddenInput = document.getElementById('hero_gallery_ids');
                    if (hiddenInput) hiddenInput.value = ids.join(',');
                }

                function setHeroGalleryPreview(index, url, id) {
                    var item = document.querySelector('.hero-gallery-item[data-index="' + index + '"]');
                    if (!item) return;
                    var input = item.querySelector('.hero-gallery-id-input');
                    var preview = item.querySelector('.hero-gallery-preview');
                    var previewWrap = item.querySelector('.hero-gallery-preview-wrap');
                    var placeholder = item.querySelector('.hero-gallery-placeholder');
                    var removeBtn = item.querySelector('.hero-gallery-remove-btn');
                    if (input) input.value = id || '';
                    if (preview) preview.src = url || '';
                    if (previewWrap) previewWrap.style.display = (url ? 'block' : 'none');
                    if (placeholder) placeholder.style.display = (url ? 'none' : 'flex');
                    if (removeBtn) removeBtn.disabled = !id;
                    updateHeroGalleryHidden();
                }

                // Upload buttons
                document.querySelectorAll('.hero-gallery-upload-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var index = this.getAttribute('data-index');
                        heroGalleryCurrentIndex = index;
                        var fileInput = document.createElement('input');
                        fileInput.type = 'file';
                        fileInput.accept = 'image/jpeg,image/png,image/webp';
                        fileInput.addEventListener('change', function() {
                            if (!this.files || !this.files[0]) return;
                            var file = this.files[0];
                            var formData = new FormData();
                            formData.append('hero_image', file);
                            if (csrfToken) formData.append('_token', csrfToken);
                            fetch(heroGalleryUploadUrl, {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                }
                            }).then(function(res) {
                                return res.json().then(function(r) {
                                    return { ok: res.ok, data: r };
                                }).catch(function() {
                                    return { ok: false, data: { message: 'Erreur serveur.' } };
                                });
                            }).then(function(result) {
                                if (result.ok && result.data && result.data.success) {
                                    setHeroGalleryPreview(heroGalleryCurrentIndex, result.data.url, result.data.attachment_id);
                                } else {
                                    alert((result.data && result.data.message) || 'Erreur lors de l\'upload.');
                                }
                            }).catch(function() {
                                alert('Erreur réseau.');
                            });
                        });
                        fileInput.click();
                    });
                });

                // Choose from media library buttons
                document.querySelectorAll('.hero-gallery-choose-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        heroGalleryCurrentIndex = this.getAttribute('data-index');
                        window.logistiqueMediaTarget = null;
                        if (mediaModal && window.bootstrap) {
                            var m = new bootstrap.Modal(mediaModal);
                            m.show();
                            loadMediaSearch(1);
                        }
                    });
                });

                // Remove buttons
                document.querySelectorAll('.hero-gallery-remove-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var index = this.getAttribute('data-index');
                        if (confirm('Retirer cette image de la galerie hero ?')) {
                            setHeroGalleryPreview(index, '', '');
                        }
                    });
                });

                // Override media selection to handle hero gallery
                var originalMediaClick = null;
                if (mediaResults) {
                    mediaResults.addEventListener('click', function(e) {
                        var item = e.target.closest('.hero-media-item');
                        if (item && heroGalleryCurrentIndex !== null) {
                            e.preventDefault();
                            e.stopPropagation();
                            var id = item.getAttribute('data-id');
                            var url = item.getAttribute('data-url');
                            if (id && heroGalleryCurrentIndex !== null) {
                                setHeroGalleryPreview(heroGalleryCurrentIndex, url, id);
                                if (mediaModal && window.bootstrap) {
                                    var m = bootstrap.Modal.getInstance(mediaModal);
                                    if (m) m.hide();
                                }
                                heroGalleryCurrentIndex = null;
                            }
                        }
                    }, true);
                }

                // Initialize hidden input
                updateHeroGalleryHidden();
            })();
            </script>



            {{-- TAB 8: TAXONOMIES --}}
            <div class="tab-pane" id="taxonomies" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Catégories & Taxonomies</h4>
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

            {{-- TAB VOLS — Vol Aller = toujours Jour 1, Vol Retour = toujours Dernier jour (N) — Laravel voyage_flights --}}
            @php
                $fOutbound = $outboundFlight ?? null;
                $fInbound = $inboundFlight ?? null;
                $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                $flightDash = '—';
                $fmtDate = function($d) { return $d ? (\Carbon\Carbon::parse($d)->format('D, d M')) : null; };
            @endphp
            <div class="tab-pane" id="flights" role="tabpanel">
                @php 
                    $lastDayNumber = $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1); 
                @endphp

                {{-- Lieux de départ (éditables) — source unique : vols gérés dans les options ci-dessous avec "Lieu de départ" --}}
                @include('admin.circuits.voyages.partials._departure_places_inline', ['departurePlaces' => $departurePlaces ?? collect()])

                {{-- Utilisation du Flight Manager réutilisable en mode complet --}}
                @include('admin.circuits.voyages.partials._flight_manager', [
                    'mode' => 'full',
                    'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
                    'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
                    'lastDayNumber' => $lastDayNumber,
                    'airlines' => $airlines ?? collect(),
                    'departurePlaces' => $departurePlaces ?? collect()
                ])
            </div>

            {{-- TAB HÔTELS — Hôtels par jour (multi-lignes) — même contenu affiché dans le drawer Jour X — Ajouter --}}
            <div class="tab-pane" id="hotels" role="tabpanel">
                @php
                    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                @endphp
                <p class="alert alert-info py-2 mb-3 small"><i class="bx bx-info-circle"></i> <strong>Hôtels</strong> — Vous pouvez ajouter plusieurs hôtels et les associer à un jour spécifique du circuit.</p>
                <h5 class="mb-3" id="tour-hotels-title"><i class="bx bx-hotel"></i> Hôtel(s) <span id="tour-hotels-period">(séjour — check-in J1, check-out J{{ $lastDayNumber }})</span></h5>
                <div id="tour-hotels-anchor">
                    @include('admin.circuits.voyages.partials._tour_hotels_section')
                </div>
                <p class="text-muted small mt-3">Les images s'affichent sur la fiche circuit (site WordPress).</p>
            </div>

            {{-- TAB TRANSFERTS — Transferts par jour (multi-lignes) — même contenu affiché dans le drawer Jour X — Ajouter --}}
            <div class="tab-pane" id="transfers" role="tabpanel">
                @php
                    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                @endphp
                <p class="alert alert-info py-2 mb-3 small"><i class="bx bx-info-circle"></i> <strong>Transferts</strong> — Vous pouvez ajouter plusieurs transferts (arrivée et départ) et les associer à un jour spécifique du circuit.</p>
                <p class="text-muted small mb-2">Vous pouvez ajouter une <strong>image</strong> pour chaque transfert ; elles s'affichent sur la fiche circuit (site WordPress).</p>
                <h5 class="mb-3"><i class="bx bx-car"></i> Transferts (plusieurs par jour possible)</h5>

                <div id="tour-transfers-anchor">
                    @include('admin.circuits.voyages.partials._tour_transfers_section')
                </div>

                <p class="text-muted small">Vous pouvez ajouter plusieurs transferts et les associer à un jour. Chaque transfert peut être configuré avec ses détails (De, À, heures, véhicule, notes, image).</p>
            </div>

            {{-- TAB LIEUX DE DÉPART — Lecture seule ; édition dans l'onglet Vols --}}
            <div class="tab-pane" id="departure-places" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3"><i class="bx bx-map-pin"></i> Lieux de départ (Starting from)</h4>
                        <p class="alert alert-info py-2 mb-3 small">
                            <i class="bx bx-info-circle"></i> <strong>Affichage en lecture seule</strong> — 
                            Les lieux et les vols sont gérés dans l’onglet <strong>Vols</strong>. Les vols associés à chaque lieu proviennent de la même source (vols Aller/Retour liés au lieu).
                        </p>
                        <p class="mb-3">
                            <a href="#flights" class="btn btn-primary btn-sm" data-bs-toggle="tab" role="tab" >
                                <i class="bx bx-trip"></i> Gérer les lieux et les vols dans l’onglet Vols
                            </a>
                        </p>

                        <div id="departure-places-readonly-container">
                            @php $departurePlaceFlightsFromTour = $departurePlaceFlightsFromTour ?? collect(); @endphp
                            @forelse(($departurePlaces ?? collect()) as $place)
                            <div class="card mb-3">
                                <div class="card-header bg-light py-2">
                                    <strong>{{ $place->name ?? 'Lieu' }}{{ isset($place->code) && $place->code !== '' ? ' (' . $place->code . ')' : '' }}</strong>
                                    @if(!($place->is_active ?? true))<span class="badge bg-secondary ms-2">Inactif</span>@endif
                                </div>
                                <div class="card-body py-2">
                                    @php $placeFlights = $departurePlaceFlightsFromTour->get($place->id, collect()); @endphp
                                    @if($placeFlights->isEmpty())
                                        <p class="text-muted small mb-0">Aucun vol associé. Lieu à associer aux vols Aller/Retour dans l’onglet Vols.</p>
                                    @else
                                        <p class="small mb-2"><strong>Vols associés ({{ $placeFlights->count() }})</strong></p>
                                        <ul class="small mb-0">
                                            @foreach($placeFlights as $f)
                                                <li>{{ $f->from_city ?? '—' }} → {{ $f->to_city ?? '—' }} ({{ $f->flight_type ?? '—' }})</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="alert alert-warning mb-0">
                                Aucun lieu de départ. Ajoutez des lieux dans l’onglet <strong>Vols</strong>.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB DÉPART & VOL — Lieu de départ, date/heure de départ, vol (nouvelle logique) --}}
            <div class="tab-pane" id="departure-and-flight" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3"><i class="bx bx-trip"></i> Départ & Vol</h4>
                        <p class="alert alert-info py-2 mb-3 small">
                            <i class="bx bx-info-circle"></i> <strong>Départ et vol associé</strong> — Lieu de départ, date/heure de départ et vol (optionnel : laisser vide pour « Vol à confirmer »).
                        </p>

                        @php
                            $depPlaceIdVal = old('departure_place_id');
                            if ($depPlaceIdVal === null || $depPlaceIdVal === '') {
                                $depPlaceIdVal = $voyage->departure_place_id ?? $meta['departure_place_id'] ?? '';
                            }
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
                            $flightIdVal = old('flight_id');
                            if ($flightIdVal === null || $flightIdVal === '') {
                                $flightIdVal = $voyage->flight_id ?? $meta['flight_id'] ?? '';
                            }
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
                    </div>
                </div>
            </div>

            {{-- TAB DATES DISPONIBLES — "Travelling on" --}}
            <div class="tab-pane" id="travel-dates" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3"><i class="bx bx-calendar-check"></i> Dates disponibles (Travelling on)</h4>
                        <p class="alert alert-info py-2 mb-3 small">
                            <i class="bx bx-info-circle"></i> <strong>Configuration des dates</strong> — 
                            Ajoutez les dates disponibles pour ce voyage. Seules ces dates seront sélectionnables dans le calendrier sur la page du tour. 
                            Si aucune date n'est configurée, un message "No dates available" sera affiché.
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

                        <script>
                        (function(){
                            var container = document.getElementById('travel-dates-container');
                            var addBtn = document.getElementById('add-travel-date');
                            if (!container || !addBtn) return;
                            if (container.dataset.initialized === 'true') return;
                            container.dataset.initialized = 'true';

                            addBtn.addEventListener('click', function(){
                                var rows = container.querySelectorAll('.travel-date-row');
                                var nextIndex = rows.length;
                                var html = `
                                <div class="card mb-2 bg-light travel-date-row" data-index="${nextIndex}">
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
                        </script>
                    </div>
                </div>
            </div>

            {{-- TAB ACTIVITÉS — Gestion du catalogue d'activités --}}
            <div class="tab-pane" id="activities" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Activités</h4>
                        <p class="text-muted mb-3">Les activités sont gérées dans l'onglet <strong>Programme</strong> où vous pouvez les associer à chaque jour du circuit.</p>
                        @if(Route::has('admin.circuits.activities.index'))
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Catalogue d'activités</strong> — Gérez le catalogue complet des activités disponibles pour tous les circuits.
                            <div class="mt-2">
                                <a href="{{ route('admin.circuits.activities.index') }}" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="bx bx-list-ul me-1"></i> Ouvrir le catalogue d'activités
                                </a>
                            </div>
                        </div>
                        @endif
                        <div class="alert alert-warning">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note :</strong> Pour ajouter des activités à un jour spécifique du circuit, utilisez l'onglet <strong>Programme</strong>.
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB PROGRAMME (unique) — Jours + notes + activités --}}
            <div class="tab-pane" id="program-days" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                            <div>
                                <h4 class="card-title mb-1">Programme</h4>
                                <p class="text-muted mb-0 small">Chaque jour : mode, titre, notes, activités. @if(Route::has('admin.circuits.activities.index'))<a href="{{ route('admin.circuits.activities.index') }}" target="_blank">Catalogue d’activités</a>.@endif</p>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-primary fs-6" id="program-days-badge">0 jours</span>
                                <button type="button" class="btn btn-success" id="btn-add-program-day">
                                    <i class="bx bx-plus"></i> Ajouter un jour
                                </button>
                            </div>
                        </div>

                        <div class="accordion" id="accordionProgrammeDays">
                        @forelse($programDays as $dayIndex => $entry)
                            @php
                                $day = $entry['day'];
                                $activities = $entry['activities'];
                                $collapseId = 'collapse-day-' . $day->id;
                                $isFirst = ($dayIndex === 0);
                                $dayTitleDisplay = $day->day_title ?? $day->title ?? ('Jour ' . $day->day_number);
                            @endphp
                            <div class="accordion-item programme-day-card" data-day-id="{{ $day->id }}" data-day-index="{{ $dayIndex }}">
                                <h2 class="accordion-header d-flex align-items-center">
                                    <span class="drag-handle me-2 text-muted cursor-grab" title="Déplacer" aria-hidden="true"><i class="bx bx-dots-vertical-rounded"></i></span>
                                    <button class="accordion-button flex-grow-1 {{ $isFirst ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $isFirst ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                                        <span class="programme-day-label">JOUR {{ $day->day_number }} – {{ $dayTitleDisplay }}</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger me-2 btn-remove-program-day" title="Supprimer ce jour" data-day-id="{{ $day->id }}">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </h2>
                                <div id="{{ $collapseId }}" class="accordion-collapse collapse {{ $isFirst ? 'show' : '' }}" data-bs-parent="#accordionProgrammeDays">
                                    <div class="accordion-body" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}">
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][id]" value="{{ $day->id }}">
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][day_id]" value="{{ $day->id }}">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Mode</label>
                                            <select name="programme_days[{{ $dayIndex }}][mode]" class="form-select programme-day-mode">
                                                <option value="program" {{ ($day->mode ?? 'program') === 'program' ? 'selected' : '' }}>Programme</option>
                                                <option value="free" {{ ($day->mode ?? '') === 'free' ? 'selected' : '' }}>Libre</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Titre du jour</label>
                                            <input type="text" class="form-control" name="programme_days[{{ $dayIndex }}][day_title]" value="{{ old('programme_days.'.$dayIndex.'.day_title', $day->day_title ?? $day->title) }}" placeholder="Ex: Jour 1 - Arrivée">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description / Notes</label>
                                        <textarea class="form-control" name="programme_days[{{ $dayIndex }}][notes]" rows="2" placeholder="Notes ou description du jour">{{ old('programme_days.'.$dayIndex.'.notes', $day->notes ?? $day->description) }}</textarea>
                                    </div>
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][title]" value="{{ $day->title ?? '' }}">
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][description]" value="{{ $day->description ?? '' }}">
                                    
                                    {{-- Inputs hidden pour lignage par jour: vols/hôtel/transferts (pré-remplis depuis programDayHotelsTransfers pour le modal Programme) --}}
                                    @php $dayHotelsTransfers = ($programDayHotelsTransfers ?? [])[$dayIndex] ?? []; @endphp
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][flights]" value="">
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][hotel_id]" value="{{ $dayHotelsTransfers['hotel_id'] ?? '' }}">
                                    <input type="hidden" name="programme_days[{{ $dayIndex }}][transfer_ids]" value="{{ implode(',', $dayHotelsTransfers['transfer_ids'] ?? []) }}">

                                    <div class="programme-day-extras mb-3" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}"></div>
                                    <p class="small text-muted mb-2 programme-day-inclus" data-day-index="{{ $dayIndex }}">
                                        INCLUS : {{ $activities->count() }} {{ $activities->count() > 1 ? 'Activités' : 'Activité' }}
                                    </p>

                                    <h6 class="mt-3 mb-2">Éléments du jour</h6>
                                    <div class="programme-activities-list mb-3" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}">
                                        @foreach($activities as $actIndex => $da)
                                            <div class="programme-activity-row card mb-2" data-day-activity-id="{{ $da->id }}" draggable="true">
                                                <div class="card-body py-2">
                                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                                        <span class="programme-activity-drag-handle text-muted cursor-grab me-1" title="Réordonner"><i class="bx bx-dots-vertical-rounded"></i></span>
                                                        <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][day_activity_id]" value="{{ $da->id }}">
                                                        <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][activity_id]" value="{{ $da->activity_id }}">
                                                        <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][sort_order]" value="{{ $actIndex }}">
                                                        <span class="fw-medium">{{ $da->activity->title ?? 'Activité #'.$da->activity_id }}</span>
                                                        <span class="form-check form-check-inline mb-0">
                                                            <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][is_included]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][is_included]" value="1" {{ $da->is_included ? 'checked' : '' }}>
                                                            <label class="form-check-label small">Inclus</label>
                                                        </span>
                                                        <span class="form-check form-check-inline mb-0">
                                                            <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][is_mandatory]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][is_mandatory]" value="1" {{ $da->is_mandatory ? 'checked' : '' }} {{ $da->is_mandatory ? 'readonly' : '' }}>
                                                            <label class="form-check-label small">Obligatoire</label>
                                                        </span>
                                                        @if($da->is_editable)
                                                        <input type="text" class="form-control form-control-sm d-inline-block" style="max-width:200px" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][custom_title]" value="{{ $da->custom_title }}" placeholder="Titre personnalisé">
                                                        <textarea class="form-control form-control-sm" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][custom_description]" rows="1" placeholder="Description personnalisée">{{ $da->custom_description }}</textarea>
                                                        @else
                                                        <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][custom_title]" value="{{ $da->custom_title }}">
                                                        <input type="hidden" name="programme_days[{{ $dayIndex }}][activities][{{ $actIndex }}][custom_description]" value="{{ $da->custom_description }}">
                                                        @endif
                                                        @if(!$da->is_mandatory)
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-programme-activity"><i class="bx bx-trash"></i></button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <button type="button" class="btn btn-outline-primary btn-add-element-to-day" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}" data-day-number="{{ $day->day_number }}">
                                            <i class="bx bx-plus"></i> Ajouter un élément
                                        </button>
                                        <span class="small text-muted">ou</span>
                                        <select class="form-select form-select-sm add-activity-select" style="max-width:240px" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}">
                                            <option value="">-- Activité rapide --</option>
                                            @foreach($activitiesCatalog as $act)
                                                <option value="{{ $act->id }}">{{ $act->title }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-sm btn-success add-activity-to-day" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}"><i class="bx bx-plus"></i> Ajouter</button>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2" id="program-no-days-alert">
                                <span><i class="bx bx-info-circle"></i> Aucun jour. Cliquez sur « Ajouter un jour » pour définir le programme.</span>
                                <button type="button" class="btn btn-sm btn-success" id="btn-add-program-day-empty"><i class="bx bx-plus"></i> Ajouter un jour</button>
                            </div>
                        @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Drawer Ajouter un élément (Vols / Transferts / Hôtels / Activités) --}}
            @include('admin.circuits.voyages.components.DayBuilderDrawer', [
                'activitiesCatalog' => $activitiesCatalog,
                'flightOptionsWithIndex' => $flightOptionsWithIndex ?? [],
                'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0,
                'lastDayNumber' => $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1),
                'airlines' => $airlines ?? collect(),
                'programDays' => $programDays ?? collect()
            ])
        </div>

        {{-- SAVE BUTTON (Fixed bottom) --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-footer d-flex justify-content-between align-items-center bg-light">
                        <div>
                            <button type="submit" form="edit-voyage-form" class="btn btn-primary btn-lg waves-effect waves-light" id="edit-voyage-submit-btn">
                                <i class="bx bx-save me-1"></i> Enregistrer toutes les modifications
                            </button>
                            <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-secondary waves-effect">Annuler</a>
                        </div>
                        <div class="text-muted">
                            <small><i class="bx bx-info-circle"></i> Modifications instantanées dans WordPress</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Plus de formulaire séparé pour ajout/suppression de jour : tout est géré en JS, sauvegardé au submit du formulaire principal --}}

    {{-- DELETE ZONE --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger">Zone dangereuse</h5>
                    <p class="text-muted">Cette action supprimera définitivement le tour de WordPress.</p>
                    <form action="{{ route('admin.circuits.voyages.destroy', $voyage->ID) }}" 
                          method="POST" 
                          onsubmit="return confirm('⚠️ ATTENTION : Supprimer définitivement ce tour de WordPress ?\n\nCette action est irréversible.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger waves-effect waves-light">
                            <i class="bx bx-trash me-1"></i> Supprimer ce tour définitivement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        // Initialiser les données pour le modal "Ajouter un élément" (hotels & transfers par jour)
        window.tourHotelsData = {};
        window.tourTransfersData = { arrival: [], departure: [] };

        // Charger tous les hôtels du tour (disponibles pour sélection par jour)
        @foreach($tourHotels as $hotel)
            @php $hotelImgUrl = !empty($hotel->image_id) ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int)$hotel->image_id) : ''; @endphp
            window.tourHotelsData[{{ $hotel->id }}] = {
                id: {{ $hotel->id }},
                hotel_name: @json($hotel->hotel_name),
                address: @json($hotel->address),
                room_type: @json($hotel->room_type),
                meal_plan: @json($hotel->meal_plan),
                stars: {{ $hotel->stars ?? 'null' }},
                image_id: {{ $hotel->image_id ?? 'null' }},
                image_url: @json($hotelImgUrl ?? ''),
                check_in_day: {{ $hotel->check_in_day ?? ($hotel->day_number ?? 'null') }},
                check_out_day: {{ $hotel->check_out_day ?? ($hotel->day_number ?? 'null') }},
                day_number: {{ $hotel->day_number ?? 'null' }} // Compatibilité
            };
        @endforeach

        // Charger tous les transferts du tour (disponibles pour sélection par jour) avec tous les détails
        @foreach($transferArrivals as $transfer)
            window.tourTransfersData.arrival.push({
                id: {{ $transfer->id }},
                direction: 'arrival',
                from_label: @json($transfer->from_label),
                to_label: @json($transfer->to_label),
                pickup_time: @json($transfer->pickup_time),
                dropoff_time: @json($transfer->dropoff_time),
                vehicle_type: @json($transfer->vehicle_type),
                notes: @json($transfer->notes),
                day_number: {{ $transfer->day_number ?? 'null' }},
                is_optional: {{ $transfer->is_optional ? 'true' : 'false' }},
                image_id: {{ $transfer->image_id ?? 'null' }}
            });
        @endforeach

        @foreach($transferDepartures as $transfer)
            window.tourTransfersData.departure.push({
                id: {{ $transfer->id }},
                direction: 'departure',
                from_label: @json($transfer->from_label),
                to_label: @json($transfer->to_label),
                pickup_time: @json($transfer->pickup_time),
                dropoff_time: @json($transfer->dropoff_time),
                vehicle_type: @json($transfer->vehicle_type),
                notes: @json($transfer->notes),
                day_number: {{ $transfer->day_number ?? 'null' }},
                is_optional: {{ $transfer->is_optional ? 'true' : 'false' }},
                image_id: {{ $transfer->image_id ?? 'null' }}
            });
        @endforeach

        // Structure pour pré-remplir le modal par jour (programme_days[$i] => { hotel_id: x, transfer_ids: [...] })
        window.programDayHotelsTransfers = @json($programDayHotelsTransfers ?? []);

        // Ouvrir l'onglet Vols si ?tab=flights (depuis Hôtels / Transferts sidebar)
        document.addEventListener('DOMContentLoaded', function() {
            var params = new URLSearchParams(window.location.search);
            if (params.get('tab') === 'flights') {
                var tabEl = document.querySelector('a[href="#flights"][data-bs-toggle="tab"]');
                if (tabEl && window.bootstrap && bootstrap.Tab) {
                    new bootstrap.Tab(tabEl).show();
                }
            }
        });
        // Destination UX: location tree (search, chips, actions, hierarchy, indeterminate)
        (function destinationUx() {
            var container = document.getElementById('locationTreeContainer');
            var searchInput = document.getElementById('locationSearch');
            var countText = document.getElementById('locationCountText');
            var chipsContainer = document.getElementById('locationChipsContainer');
            var chipsClearBtn = document.getElementById('locationChipsClear');
            var selectAllBtn = document.getElementById('locationSelectAll');
            var deselectAllBtn = document.getElementById('locationDeselectAll');
            var expandAllBtn = document.getElementById('locationExpandAll');
            var collapseAllBtn = document.getElementById('locationCollapseAll');
            var selectFilteredBtn = document.getElementById('locationSelectFiltered');

            function getCheckboxes() { return container ? container.querySelectorAll('.location-checkbox') : []; }
            function getVisibleItems() { return container ? container.querySelectorAll('.wp-location-item:not([style*="display: none"])') : []; }

            function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
            function highlightMatch(text, term) {
                if (!term) return escapeHtml(text);
                var r = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                return escapeHtml(text).replace(r, '<mark>$1</mark>');
            }

            function updateCount() {
                var n = document.querySelectorAll('.location-checkbox:checked').length;
                if (countText) countText.textContent = n + ' destination(s) sélectionnée(s)';
            }

            function updateChips() {
                if (!chipsContainer) return;
                var boxes = Array.from(getCheckboxes()).filter(function(cb) { return cb.checked; });
                chipsContainer.innerHTML = '';
                boxes.forEach(function(cb) {
                    var id = cb.value;
                    var title = cb.getAttribute('data-loc-title') || id;
                    var chip = document.createElement('span');
                    chip.className = 'destination-ux-chip';
                    chip.innerHTML = escapeHtml(title) + ' <button type="button" class="destination-ux-chip-remove" data-loc-id="' + escapeHtml(id) + '" aria-label="Retirer">×</button>';
                    chipsContainer.appendChild(chip);
                });
                if (chipsClearBtn) chipsClearBtn.style.display = boxes.length ? '' : 'none';
            }

            function syncChipsAndCount() {
                updateCount();
                updateChips();
                updateIndeterminate();
            }

            function updateIndeterminate() {
                container.querySelectorAll('.wp-location-item.has-children').forEach(function(item) {
                    var cb = item.querySelector(':scope > .destination-tree-row .location-checkbox');
                    if (!cb) return;
                    var childCbs = item.querySelectorAll('.destination-tree-list .location-checkbox');
                    var checked = Array.from(childCbs).filter(function(c) { return c.checked; }).length;
                    cb.indeterminate = checked > 0 && checked < childCbs.length;
                    item.classList.toggle('indeterminate', cb.indeterminate);
                });
                var panelList = document.getElementById('destination-cities-list');
                if (panelList) {
                    var countryCb = panelList.querySelector('.destination-country-checkbox-label input.location-checkbox');
                    if (countryCb) {
                        var cityCbs = panelList.querySelectorAll('.destination-city-checkbox-label input.location-checkbox');
                        var checked = Array.from(cityCbs).filter(function(c) { return c.checked; }).length;
                        countryCb.indeterminate = checked > 0 && checked < cityCbs.length;
                    }
                }
            }

            function applySearch(term) {
                term = (term || '').toLowerCase().trim();
                var items = container ? container.querySelectorAll('.wp-location-item') : [];
                var hasFilter = term.length > 0;
                if (selectFilteredBtn) selectFilteredBtn.style.display = hasFilter ? '' : 'none';

                items.forEach(function(item) {
                    var title = item.getAttribute('data-title') || '';
                    var path = item.getAttribute('data-path') || '';
                    var pathLower = path.toLowerCase();
                    var selfMatch = title.indexOf(term) !== -1;
                    var pathMatch = term && pathLower.indexOf(term) !== -1;
                    var childMatch = Array.from(item.querySelectorAll('.wp-location-item')).some(function(c) {
                        return (c.getAttribute('data-title') || '').indexOf(term) !== -1;
                    });
                    var show = !term || selfMatch || pathMatch || childMatch;
                    item.style.display = show ? '' : 'none';

                    var titleEl = item.querySelector('.destination-tree-title');
                    if (titleEl) {
                        var rawTitle = item.querySelector('.location-checkbox').getAttribute('data-loc-title') || item.getAttribute('data-title') || '';
                        if (term && show)
                            titleEl.innerHTML = highlightMatch(rawTitle, term);
                        else
                            titleEl.textContent = rawTitle;
                    }
                    if (item.classList) item.classList.toggle('destination-search-path', !!term && show && path);
                    var t = item.querySelector('.destination-tree-title');
                    if (t) {
                        if (term && show && path) t.setAttribute('data-path', path);
                        else t.removeAttribute('data-path');
                    }
                });
            }

            function expandAll() {
                container.querySelectorAll('.wp-location-item.has-children').forEach(function(item) {
                    item.classList.remove('collapsed');
                    item.querySelector('.destination-tree-toggle').setAttribute('aria-expanded', 'true');
                });
            }
            function collapseAll() {
                container.querySelectorAll('.wp-location-item.has-children').forEach(function(item) {
                    item.classList.add('collapsed');
                    item.querySelector('.destination-tree-toggle').setAttribute('aria-expanded', 'false');
                });
            }

            function selectAll() {
                getCheckboxes().forEach(function(cb) { cb.checked = true; });
                syncChipsAndCount();
            }
            function deselectAll() {
                getCheckboxes().forEach(function(cb) { cb.checked = false; });
                syncChipsAndCount();
            }
            function selectFilteredOnly() {
                getCheckboxes().forEach(function(cb) { cb.checked = false; });
                getVisibleItems().forEach(function(item) {
                    var cb = item.querySelector(':scope > .destination-tree-row .location-checkbox');
                    if (cb) cb.checked = true;
                });
                syncChipsAndCount();
            }

            function cascadeParent(checkbox) {
                var item = checkbox.closest('.wp-location-item');
                if (!item || !item.classList.contains('has-children')) return;
                var childCbs = item.querySelectorAll('.destination-tree-list .location-checkbox');
                var target = checkbox.checked;
                if (childCbs.length > 12 && !window.confirm('Appliquer à ' + childCbs.length + ' sous-destinations ?')) return;
                childCbs.forEach(function(c) { c.checked = target; });
                syncChipsAndCount();
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() { applySearch(this.value); });
            }
            if (chipsContainer) {
                chipsContainer.addEventListener('click', function(e) {
                    var rm = e.target.closest('.destination-ux-chip-remove');
                    if (rm) {
                        e.preventDefault();
                        var id = rm.getAttribute('data-loc-id');
                        var cb = container && container.querySelector('.location-checkbox[value="' + id.replace(/"/g, '\\"') + '"]');
                        if (cb) { cb.checked = false; syncChipsAndCount(); }
                    }
                });
            }
            if (chipsClearBtn) chipsClearBtn.addEventListener('click', function() { deselectAll(); });

            if (selectAllBtn) selectAllBtn.addEventListener('click', selectAll);
            if (deselectAllBtn) deselectAllBtn.addEventListener('click', deselectAll);
            if (expandAllBtn) expandAllBtn.addEventListener('click', expandAll);
            if (collapseAllBtn) collapseAllBtn.addEventListener('click', collapseAll);
            if (selectFilteredBtn) selectFilteredBtn.addEventListener('click', selectFilteredOnly);

            container && container.addEventListener('change', function(e) {
                if (e.target.classList && e.target.classList.contains('location-checkbox')) {
                    syncChipsAndCount();
                    cascadeParent(e.target);
                }
            });

            container && container.addEventListener('click', function(e) {
                var toggle = e.target.closest('.destination-tree-toggle');
                if (toggle && !toggle.classList.contains('destination-tree-toggle--empty')) {
                    var item = toggle.closest('.wp-location-item.has-children');
                    if (item) {
                        item.classList.toggle('collapsed');
                        toggle.setAttribute('aria-expanded', item.classList.contains('collapsed') ? 'false' : 'true');
                    }
                }
            });

            updateChips();
            updateIndeterminate();

            // Pays (choix multiple) + catalogue villes : recherche, Tout sélectionner/désélectionner, ensureLocation à la volée
            var panelDynamic = document.getElementById('destination-cities-panel-dynamic');
            var panelTitle = document.getElementById('destination-cities-panel-title');
            var panelList = document.getElementById('destination-cities-list');
            var citySearchInput = document.getElementById('destinationCitySearch');
            var selectAllCitiesBtn = document.getElementById('destinationSelectAllCities');
            var deselectAllCitiesBtn = document.getElementById('destinationDeselectAllCities');
            var countryCitiesData = window.DESTINATION_COUNTRY_CITIES_DATA || {};
            var mergedCities = window.DESTINATION_MERGED_CITIES || {};
            var worldCountries = window.DESTINATION_WORLD_COUNTRIES || {};
            var ensureLocationUrl = window.DESTINATION_ENSURE_LOCATION_URL || '';
            var selectedIds = window.DESTINATION_SELECTED_IDS || [];

            function escapeAttr(s) { return (s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
            function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

            function ensureLocation(countryCode, cityName, cb) {
                var formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
                formData.append('country_code', countryCode);
                if (cityName) formData.append('city_name', cityName);
                fetch(ensureLocationUrl, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) { if (cb) cb(null, data); })
                    .catch(function(err) { if (cb) cb(err); });
            }

            function updateCountryIndeterminate() {
                if (!panelList) return;
                panelList.querySelectorAll('.destination-country-block').forEach(function(block) {
                    var countryCb = block.querySelector('.destination-country-checkbox-label input.location-checkbox');
                    if (!countryCb) return;
                    var cityCbs = block.querySelectorAll('.destination-city-checkbox-label input.location-checkbox');
                    var checked = Array.from(cityCbs).filter(function(c) { return c.checked; }).length;
                    countryCb.indeterminate = checked > 0 && checked < cityCbs.length;
                });
            }

            function getSelectedCountryCodes() {
                var opts = document.querySelectorAll('#destinationCountryList .destination-country-option:checked');
                return Array.from(opts).map(function(o) { return o.value; }).filter(Boolean);
            }

            function onCheckboxChange() {
                updateChips();
                updateCount();
                updateCountryIndeterminate();
            }

            function handlePanelCheckboxChange(cb) {
                if (!cb || !cb.classList.contains('location-checkbox')) return;
                if (cb.classList.contains('destination-country-whole') && cb.getAttribute('data-needs-create') === '1' && cb.checked) {
                    var ccode = cb.getAttribute('data-country-code');
                    if (!ccode) return;
                    cb.disabled = true;
                    ensureLocation(ccode, null, function(err, res) {
                        cb.disabled = false;
                        if (err || !res || !res.id) { cb.checked = false; return; }
                        cb.value = res.id;
                        cb.setAttribute('data-loc-id', res.id);
                        cb.removeAttribute('data-needs-create');
                        cb.removeAttribute('data-country-code');
                        onCheckboxChange();
                    });
                    return;
                }
                if (cb.getAttribute('data-needs-create') === '1' && cb.checked) {
                    var ccode = cb.getAttribute('data-country-code');
                    var cname = cb.getAttribute('data-city-name');
                    if (!ccode || !cname) return;
                    cb.disabled = true;
                    ensureLocation(ccode, cname, function(err, res) {
                        cb.disabled = false;
                        if (err || !res || !res.id) { cb.checked = false; return; }
                        cb.value = res.id;
                        cb.setAttribute('data-loc-id', res.id);
                        cb.setAttribute('data-loc-title', res.title || cname);
                        cb.removeAttribute('data-needs-create');
                        cb.removeAttribute('data-country-code');
                        cb.removeAttribute('data-city-name');
                        onCheckboxChange();
                    });
                    return;
                }
                onCheckboxChange();
            }

            if (panelList) {
                panelList.addEventListener('change', function(e) {
                    if (e.target && e.target.classList && e.target.classList.contains('location-checkbox')) {
                        handlePanelCheckboxChange(e.target);
                    }
                });
            }

            function getCurrentSelectedLocationIds() {
                var ids = [];
                if (!panelList) return ids;
                panelList.querySelectorAll('.location-checkbox:checked').forEach(function(cb) {
                    var v = cb.value;
                    if (v && String(v).trim() !== '') {
                        var id = parseInt(v, 10);
                        if (!isNaN(id) && ids.indexOf(id) === -1) ids.push(id);
                    }
                });
                return ids;
            }

            function fillCitiesPanel(selectedCodes) {
                if (!panelList) return;
                var selectedIdsForBuild = selectedIds.slice();
                if (panelList.querySelectorAll('.location-checkbox').length > 0) {
                    selectedIdsForBuild = getCurrentSelectedLocationIds();
                    if (selectedIdsForBuild.length === 0) selectedIdsForBuild = selectedIds.slice();
                }
                panelList.innerHTML = '';
                if (!selectedCodes || selectedCodes.length === 0) {
                    if (panelDynamic) panelDynamic.style.display = 'none';
                    if (citySearchInput) citySearchInput.value = '';
                    return;
                }
                if (panelDynamic) panelDynamic.style.display = 'block';
                panelTitle.textContent = 'Villes (' + selectedCodes.length + ' pays)';

                selectedCodes.forEach(function(code) {
                    var cities = mergedCities[code] || [];
                    var data = countryCitiesData[code];
                    var countryName = (data && data.title) ? data.title : (worldCountries[code] || code);

                    var block = document.createElement('div');
                    block.className = 'destination-country-block';
                    block.setAttribute('data-country-code', code);

                    var countryId = data && data.id ? data.id : null;
                    var countryChecked = countryId && selectedIdsForBuild.indexOf(countryId) !== -1;
                    var countryLabel = document.createElement('label');
                    countryLabel.className = 'destination-country-checkbox-label';
                    if (countryId) {
                        countryLabel.innerHTML = '<input type="checkbox" name="locations[]" value="' + countryId + '" class="location-checkbox destination-checkbox destination-country-whole" ' + (countryChecked ? 'checked' : '') + ' data-loc-id="' + countryId + '" data-loc-title="' + escapeAttr(countryName) + '"> <span>Inclure le pays entier (' + escapeHtml(countryName) + ')</span>';
                    } else {
                        countryLabel.innerHTML = '<input type="checkbox" name="locations[]" value="" class="location-checkbox destination-checkbox destination-country-whole" data-country-code="' + escapeAttr(code) + '" data-needs-create="1" data-loc-title="' + escapeAttr(countryName) + '"> <span>Inclure le pays entier (' + escapeHtml(countryName) + ')</span>';
                    }
                    block.appendChild(countryLabel);

                    if (cities.length === 0) {
                        var p = document.createElement('p');
                        p.className = 'text-muted small mb-0 mt-1';
                        p.textContent = 'Aucune ville dans le catalogue pour ce pays.';
                        block.appendChild(p);
                    } else {
                        cities.forEach(function(city) {
                            var lid = city.id;
                            var title = city.title || '';
                            var checked = lid && selectedIdsForBuild.indexOf(lid) !== -1;
                            var label = document.createElement('label');
                            label.className = 'destination-city-checkbox-label destination-city-row';
                            label.setAttribute('data-city-title', title.toLowerCase());
                            label.setAttribute('data-path', countryName + ' › ' + title);
                            label.setAttribute('data-country-code', code);
                            if (lid) {
                                label.innerHTML = '<input type="checkbox" name="locations[]" value="' + lid + '" class="location-checkbox destination-checkbox" ' + (checked ? 'checked' : '') + ' data-loc-id="' + lid + '" data-loc-title="' + escapeAttr(title) + '"> <span class="destination-city-path">' + escapeHtml(countryName) + ' › ' + escapeHtml(title) + '</span>';
                            } else {
                                label.innerHTML = '<input type="checkbox" name="locations[]" value="" class="location-checkbox destination-checkbox" data-country-code="' + escapeAttr(code) + '" data-city-name="' + escapeAttr(title) + '" data-needs-create="1" data-loc-title="' + escapeAttr(title) + '"> <span class="destination-city-path">' + escapeHtml(countryName) + ' › ' + escapeHtml(title) + '</span>';
                            }
                            block.appendChild(label);
                        });
                    }
                    panelList.appendChild(block);
                });

                if (citySearchInput) {
                    citySearchInput.value = '';
                    citySearchInput.dispatchEvent(new Event('input'));
                }
                updateCountryIndeterminate();
                updateChips();
                updateCount();
            }

            function filterCitySearch(term) {
                term = (term || '').toLowerCase().trim();
                panelList.querySelectorAll('.destination-city-row').forEach(function(row) {
                    var title = row.getAttribute('data-city-title') || '';
                    var path = (row.getAttribute('data-path') || '').toLowerCase();
                    var show = !term || title.indexOf(term) !== -1 || path.indexOf(term) !== -1;
                    row.style.display = show ? '' : 'none';
                });
            }

            var countryListEl = document.getElementById('destinationCountryList');
            var countrySearchInput = document.getElementById('destinationCountrySearch');
            var selectAllCountriesBtn = document.getElementById('destinationSelectAllCountries');
            var deselectAllCountriesBtn = document.getElementById('destinationDeselectAllCountries');

            function filterCountrySearch(term) {
                term = (term || '').toLowerCase().trim();
                if (!countryListEl) return;
                countryListEl.querySelectorAll('.destination-country-option-label').forEach(function(label) {
                    var opt = label.querySelector('.destination-country-option');
                    var name = (opt ? opt.getAttribute('data-country-name') : '') || (label.textContent || '').toLowerCase();
                    if (typeof name !== 'string') name = '';
                    name = name.toLowerCase();
                    var show = !term || name.indexOf(term) !== -1;
                    label.style.display = show ? '' : 'none';
                });
            }

            if (countryListEl) {
                countryListEl.querySelectorAll('.destination-country-option').forEach(function(opt) {
                    opt.addEventListener('change', function() { fillCitiesPanel(getSelectedCountryCodes()); });
                });
            }
            if (countrySearchInput) {
                countrySearchInput.addEventListener('input', function() { filterCountrySearch(this.value); });
            }
            if (selectAllCountriesBtn && countryListEl) {
                selectAllCountriesBtn.addEventListener('click', function() {
                    countryListEl.querySelectorAll('.destination-country-option-label:not([style*="display: none"]) .destination-country-option').forEach(function(o) { o.checked = true; });
                    fillCitiesPanel(getSelectedCountryCodes());
                });
            }
            if (deselectAllCountriesBtn && countryListEl) {
                deselectAllCountriesBtn.addEventListener('click', function() {
                    countryListEl.querySelectorAll('.destination-country-option').forEach(function(o) { o.checked = false; });
                    fillCitiesPanel([]);
                });
            }

            var countryAddSearchInput = document.getElementById('destinationCountryAddSearch');
            var countryAutocompleteDropdown = document.getElementById('destinationCountryAutocompleteDropdown');
            function buildCountryAutocompleteSuggestions(term) {
                var selectedCodes = getSelectedCountryCodes();
                var list = [];
                term = (term || '').toLowerCase().trim();
                for (var code in worldCountries) {
                    var name = (worldCountries[code] || '').toLowerCase();
                    if (selectedCodes.indexOf(code) !== -1) continue;
                    if (term && name.indexOf(term) === -1) continue;
                    list.push({ code: code, name: worldCountries[code] });
                }
                return list;
            }
            function openCountryAutocomplete() {
                var term = countryAddSearchInput ? countryAddSearchInput.value : '';
                var list = buildCountryAutocompleteSuggestions(term);
                if (!countryAutocompleteDropdown) return;
                countryAutocompleteDropdown.classList.toggle('is-open', list.length > 0);
                countryAutocompleteDropdown.innerHTML = '';
                list.slice(0, 60).forEach(function(item) {
                    var div = document.createElement('div');
                    div.className = 'destination-country-autocomplete-item';
                    div.textContent = item.name;
                    div.setAttribute('data-country-code', item.code);
                    countryAutocompleteDropdown.appendChild(div);
                });
                if (list.length === 0) countryAutocompleteDropdown.classList.remove('is-open');
            }
            function closeCountryAutocomplete() {
                if (countryAutocompleteDropdown) countryAutocompleteDropdown.classList.remove('is-open');
            }
            function addCountryFromSuggestion(code) {
                if (!code || !countryListEl) return;
                var opt = countryListEl.querySelector('.destination-country-option[value="' + code + '"]');
                if (opt && !opt.checked) {
                    opt.checked = true;
                    fillCitiesPanel(getSelectedCountryCodes());
                }
                openCountryAutocomplete();
            }
            if (countryAddSearchInput) {
                countryAddSearchInput.addEventListener('input', openCountryAutocomplete);
                countryAddSearchInput.addEventListener('focus', openCountryAutocomplete);
            }
            if (countryAutocompleteDropdown) {
                countryAutocompleteDropdown.addEventListener('click', function(e) {
                    var item = e.target.closest('.destination-country-autocomplete-item');
                    if (!item) return;
                    addCountryFromSuggestion(item.getAttribute('data-country-code'));
                });
            }
            document.addEventListener('click', function(e) {
                if (countryAutocompleteDropdown && countryAddSearchInput && !countryAutocompleteDropdown.contains(e.target) && !countryAddSearchInput.contains(e.target)) closeCountryAutocomplete();
                if (cityAutocompleteDropdown && cityAddSearchInput && !cityAutocompleteDropdown.contains(e.target) && !cityAddSearchInput.contains(e.target)) closeCityAutocomplete();
            });

            (function preselectCountries() {
                var codesToSelect = [];
                selectedIds.forEach(function(id) {
                    for (var code in countryCitiesData) {
                        var d = countryCitiesData[code];
                        if (d && (d.id == id || (d.cities && d.cities.some(function(c) { return c.id == id; })))) {
                            if (codesToSelect.indexOf(code) === -1) codesToSelect.push(code);
                            break;
                        }
                    }
                    if (codesToSelect.length === 0) {
                        for (var code in mergedCities) {
                            if ((mergedCities[code] || []).some(function(c) { return c.id == id; })) {
                                if (codesToSelect.indexOf(code) === -1) codesToSelect.push(code);
                                break;
                            }
                        }
                    }
                });
                codesToSelect.forEach(function(code) {
                    var opt = countryListEl && countryListEl.querySelector('.destination-country-option[value="' + code + '"]');
                    if (opt) opt.checked = true;
                });
                if (codesToSelect.length) fillCitiesPanel(codesToSelect);
                else if (panelDynamic) panelDynamic.style.display = 'none';
            })();

            if (citySearchInput) {
                citySearchInput.addEventListener('input', function() { filterCitySearch(this.value); });
            }

            var cityAddSearchInput = document.getElementById('destinationCityAddSearch');
            var cityAutocompleteDropdown = document.getElementById('destinationCityAutocompleteDropdown');
            function getSelectedCityPathsInPanel() {
                var paths = [];
                if (!panelList) return paths;
                panelList.querySelectorAll('.destination-city-row input.location-checkbox:checked').forEach(function(cb) {
                    var row = cb.closest('.destination-city-row');
                    if (row) { var p = row.getAttribute('data-path'); if (p) paths.push(p); }
                });
                return paths;
            }
            function buildCityAutocompleteSuggestions(term) {
                var codes = getSelectedCountryCodes();
                if (!codes.length) return [];
                var selectedPaths = getSelectedCityPathsInPanel();
                var list = [];
                term = (term || '').toLowerCase().trim();
                codes.forEach(function(code) {
                    var countryName = (countryCitiesData[code] && countryCitiesData[code].title) ? countryCitiesData[code].title : (worldCountries[code] || code);
                    (mergedCities[code] || []).forEach(function(city) {
                        var title = city.title || '';
                        var path = countryName + ' › ' + title;
                        if (selectedPaths.indexOf(path) !== -1) return;
                        if (term && path.toLowerCase().indexOf(term) === -1 && title.toLowerCase().indexOf(term) === -1) return;
                        list.push({ code: code, countryName: countryName, path: path, city: city });
                    });
                });
                return list;
            }
            function openCityAutocomplete() {
                var term = cityAddSearchInput ? cityAddSearchInput.value : '';
                var list = buildCityAutocompleteSuggestions(term);
                if (!cityAutocompleteDropdown) return;
                cityAutocompleteDropdown.classList.toggle('is-open', list.length > 0);
                cityAutocompleteDropdown.innerHTML = '';
                list.slice(0, 50).forEach(function(item) {
                    var div = document.createElement('div');
                    div.className = 'destination-city-autocomplete-item';
                    div.textContent = item.path;
                    div.setAttribute('data-path', item.path);
                    div.setAttribute('data-country-code', item.code);
                    div.setAttribute('data-city-name', item.city.title || '');
                    if (item.city.id) div.setAttribute('data-loc-id', item.city.id);
                    cityAutocompleteDropdown.appendChild(div);
                });
                if (list.length === 0) cityAutocompleteDropdown.classList.remove('is-open');
            }
            function closeCityAutocomplete() {
                if (cityAutocompleteDropdown) cityAutocompleteDropdown.classList.remove('is-open');
            }
            function addCityFromSuggestion(path, code, cityName, locId) {
                if (!path) return;
                var row = Array.from(panelList.querySelectorAll('.destination-city-row')).find(function(r) { return r.getAttribute('data-path') === path; });
                if (!row) return;
                var cb = row.querySelector('input.location-checkbox');
                if (!cb) return;
                if (cb.checked) { openCityAutocomplete(); return; }
                cb.checked = true;
                if (cb.getAttribute('data-needs-create') === '1') {
                    cb.disabled = true;
                    ensureLocation(code, cityName, function(err, res) {
                        cb.disabled = false;
                        if (!err && res && res.id) {
                            cb.value = res.id;
                            cb.setAttribute('data-loc-id', res.id);
                            cb.removeAttribute('data-needs-create');
                            cb.removeAttribute('data-country-code');
                            cb.removeAttribute('data-city-name');
                        } else cb.checked = false;
                        onCheckboxChange();
                        openCityAutocomplete();
                    });
                } else {
                    cb.dispatchEvent(new Event('change', { bubbles: true }));
                    onCheckboxChange();
                    openCityAutocomplete();
                }
            }
            if (cityAddSearchInput) {
                cityAddSearchInput.addEventListener('input', openCityAutocomplete);
                cityAddSearchInput.addEventListener('focus', function() { openCityAutocomplete(); });
            }
            if (cityAutocompleteDropdown) {
                cityAutocompleteDropdown.addEventListener('click', function(e) {
                    var item = e.target.closest('.destination-city-autocomplete-item');
                    if (!item) return;
                    var path = item.getAttribute('data-path');
                    var code = item.getAttribute('data-country-code');
                    var cityName = item.getAttribute('data-city-name');
                    addCityFromSuggestion(path, code, cityName, item.getAttribute('data-loc-id'));
                });
            }
            if (selectAllCitiesBtn) {
                selectAllCitiesBtn.addEventListener('click', function() {
                    var rows = panelList.querySelectorAll('.destination-city-row');
                    var toCreate = [];
                    rows.forEach(function(row) {
                        if (row.style.display === 'none') return;
                        var cb = row.querySelector('input.location-checkbox');
                        if (!cb) return;
                        if (cb.checked) return;
                        if (cb.getAttribute('data-needs-create') === '1') toCreate.push(cb);
                        else { cb.checked = true; onCheckboxChange(); }
                    });
                    function runNext(i) {
                        if (i >= toCreate.length) { updateCountryIndeterminate(); return; }
                        var cb = toCreate[i];
                        var ccode = cb.getAttribute('data-country-code');
                        var cname = cb.getAttribute('data-city-name');
                        cb.disabled = true;
                        ensureLocation(ccode, cname, function(err, res) {
                            cb.disabled = false;
                            if (!err && res && res.id) {
                                cb.value = res.id;
                                cb.setAttribute('data-loc-id', res.id);
                                cb.removeAttribute('data-needs-create');
                                cb.removeAttribute('data-country-code');
                                cb.removeAttribute('data-city-name');
                                cb.checked = true;
                            }
                            onCheckboxChange();
                            runNext(i + 1);
                        });
                    }
                    runNext(0);
                });
            }
            if (deselectAllCitiesBtn) {
                deselectAllCitiesBtn.addEventListener('click', function() {
                    panelList.querySelectorAll('.destination-city-checkbox-label input.location-checkbox').forEach(function(cb) { cb.checked = false; });
                    onCheckboxChange();
                });
            }
        })();
        
        window.PROGRAMME_ACTIVITIES_CATALOG = @json($activitiesCatalog->map(fn($a) => ['id' => $a->id, 'title' => $a->title])->values()->all());
        window.PROGRAM_API_URL = @json($programApiUrl ?? '');
        window.PROGRAM_VOYAGE_ID = @json($voyage->ID ?? 0);

        (function programmeDaysManager() {
            var accordion = document.getElementById('accordionProgrammeDays');
            var badge = document.getElementById('program-days-badge');
            var durationInput = document.getElementById('duration_day');
            var noDaysAlert = document.getElementById('program-no-days-alert');

            function count() {
                return (accordion ? accordion.querySelectorAll('.programme-day-card').length : 0);
            }
            function updateBadge() {
                if (badge) {
                    var n = count();
                    badge.textContent = n === 1 ? '1 jour' : n + ' jours';
                }
            }
            function updateDuration() {
                if (durationInput) {
                    var n = count();
                    durationInput.value = n > 0 ? n : (durationInput.value || 1);
                }
            }
            function renumber() {
                if (!accordion) return;
                var cards = accordion.querySelectorAll('.programme-day-card');
                cards.forEach(function(card, i) {
                    card.setAttribute('data-day-index', i);
                    var prefixOld = 'programme_days[' + (card.getAttribute('data-day-index') || i) + ']';
                    var prefixNew = 'programme_days[' + i + ']';
                    card.querySelectorAll('[name^="programme_days["]').forEach(function(el) {
                        el.name = el.name.replace(/^programme_days\[\d+\]/, 'programme_days[' + i + ']');
                    });
                    card.querySelectorAll('[data-day-index]').forEach(function(el) { el.setAttribute('data-day-index', i); });
                    card.querySelectorAll('.add-activity-select, .add-activity-to-day').forEach(function(el) { el.setAttribute('data-day-index', i); });
                    card.querySelectorAll('.btn-add-element-to-day').forEach(function(el) { el.setAttribute('data-day-number', i + 1); });
                    var label = card.querySelector('.programme-day-label');
                    var titleInput = card.querySelector('input[name$="[day_title]"]');
                    var dayNum = i + 1;
                    var title = (titleInput && titleInput.value.trim()) ? titleInput.value.trim() : ('Jour ' + dayNum);
                    if (label) label.textContent = 'JOUR ' + dayNum + ' – ' + title;
                });
                updateBadge();
                updateDuration();
                if (window.autosaveProgram) window.autosaveProgram();
            }
            function newDayHtml(index) {
                var collapseId = 'collapse-day-new-' + index + '-' + Date.now();
                var actOpts = (window.PROGRAMME_ACTIVITIES_CATALOG || []).map(function(a) {
                    return '<option value="' + a.id + '">' + (a.title || '').replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</option>';
                }).join('');
                return '<div class="accordion-item programme-day-card" data-day-id="" data-day-index="' + index + '">' +
                    '<h2 class="accordion-header d-flex align-items-center">' +
                    '<span class="drag-handle me-2 text-muted cursor-grab" title="Déplacer"><i class="bx bx-dots-vertical-rounded"></i></span>' +
                    '<button class="accordion-button collapsed flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#' + collapseId + '" aria-expanded="false" aria-controls="' + collapseId + '">' +
                    '<span class="programme-day-label">JOUR ' + (index + 1) + ' – Jour ' + (index + 1) + '</span></button>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger me-2 btn-remove-program-day" title="Supprimer ce jour"><i class="bx bx-trash"></i></button></h2>' +
                    '<div id="' + collapseId + '" class="accordion-collapse collapse" data-bs-parent="#accordionProgrammeDays">' +
                    '<div class="accordion-body" data-day-index="' + index + '" data-day-id="">' +
                    '<input type="hidden" name="programme_days[' + index + '][id]" value="">' +
                    '<input type="hidden" name="programme_days[' + index + '][day_id]" value="">' +
                    '<div class="row mb-3"><div class="col-md-6"><label class="form-label">Mode</label>' +
                    '<select name="programme_days[' + index + '][mode]" class="form-select programme-day-mode">' +
                    '<option value="program" selected>Programme</option><option value="free">Libre</option></select></div>' +
                    '<div class="col-md-6"><label class="form-label">Titre du jour</label>' +
                    '<input type="text" class="form-control" name="programme_days[' + index + '][day_title]" placeholder="Ex: Jour ' + (index + 1) + ' - Arrivée"></div></div>' +
                    '<div class="mb-3"><label class="form-label">Description / Notes</label>' +
                    '<textarea class="form-control" name="programme_days[' + index + '][notes]" rows="2" placeholder="Notes ou description du jour"></textarea></div>' +
                    '<input type="hidden" name="programme_days[' + index + '][title]" value="">' +
                    '<input type="hidden" name="programme_days[' + index + '][description]" value="">' +
                    '<input type="hidden" name="programme_days[' + index + '][flights]" value="">' +
                    '<input type="hidden" name="programme_days[' + index + '][hotel_id]" value="">' +
                    '<input type="hidden" name="programme_days[' + index + '][transfer_ids]" value="">' +
                    '<div class="programme-day-extras small text-muted mb-2" data-day-index="' + index + '" data-day-id=""></div>' +
                    '<p class="small text-muted mb-2 programme-day-inclus" data-day-index="' + index + '">INCLUS : 0 Activité</p>' +
                    '<h6 class="mt-3 mb-2">Éléments du jour</h6>' +
                    '<div class="programme-activities-list mb-3" data-day-index="' + index + '" data-day-id="">' + '</div>' +
                    '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                    '<button type="button" class="btn btn-outline-primary btn-add-element-to-day" data-day-index="' + index + '" data-day-id="" data-day-number="' + (index + 1) + '"><i class="bx bx-plus"></i> Ajouter un élément</button>' +
                    '<span class="small text-muted">ou</span>' +
                    '<select class="form-select form-select-sm add-activity-select" style="max-width:240px" data-day-index="' + index + '" data-day-id="">' +
                    '<option value="">-- Activité rapide --</option>' + actOpts + '</select>' +
                    '<button type="button" class="btn btn-sm btn-success add-activity-to-day" data-day-index="' + index + '" data-day-id=""><i class="bx bx-plus"></i> Ajouter</button></div>' +
                    '</div></div></div>';
            }
            function addDay() {
                if (!accordion) return;
                if (noDaysAlert) noDaysAlert.style.display = 'none';
                var n = count();
                var div = document.createElement('div');
                div.innerHTML = newDayHtml(n).trim();
                accordion.appendChild(div.firstChild);
                renumber();
                var lastCard = accordion.querySelector('.programme-day-card:last-child');
                if (lastCard && window.bootstrap && bootstrap.Collapse) {
                    var collapseEl = lastCard.querySelector('.accordion-collapse');
                    if (collapseEl) new bootstrap.Collapse(collapseEl, { toggle: true });
                }
                attachDragToCards();
                if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(String(count() - 1));
            }
            function removeDay(btn) {
                var card = btn.closest('.programme-day-card');
                if (!card || !accordion) return;
                if (count() <= 1) {
                    alert('Il doit rester au moins un jour.');
                    return;
                }
                if (!confirm('Supprimer ce jour ? Les activités du jour seront supprimées. La sauvegarde sera effective au clic sur « Enregistrer ».')) return;
                card.remove();
                if (count() === 0 && noDaysAlert) noDaysAlert.style.display = '';
                renumber();
                var cards = accordion.querySelectorAll('.programme-day-card');
                if (window.dayItemsManager && window.updateProgrammeDayExtras) {
                    cards.forEach(function(c, i) {
                        window.dayItemsManager.loadFromForm(String(i));
                        window.updateProgrammeDayExtras(String(i));
                    });
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                updateBadge();
                updateDuration();
                document.getElementById('btn-add-program-day') && document.getElementById('btn-add-program-day').addEventListener('click', addDay);
                document.getElementById('btn-add-program-day-empty') && document.getElementById('btn-add-program-day-empty').addEventListener('click', function() { addDay(); });
                accordion && accordion.addEventListener('click', function(e) {
                    if (e.target.closest('.btn-remove-program-day')) {
                        e.preventDefault();
                        removeDay(e.target.closest('.btn-remove-program-day'));
                    }
                });
                accordion && accordion.addEventListener('input', function(e) {
                    if (e.target.matches('input[name$="[day_title]"]')) {
                        var card = e.target.closest('.programme-day-card');
                        var i = parseInt(card.getAttribute('data-day-index'), 10);
                        var label = card.querySelector('.programme-day-label');
                        if (label) label.textContent = 'JOUR ' + (i + 1) + ' – ' + (e.target.value.trim() || ('Jour ' + (i + 1)));
                    }
                });
                document.getElementById('edit-voyage-form') && document.getElementById('edit-voyage-form').addEventListener('submit', function() {
                    if (durationInput) durationInput.value = count() || 1;
                });
                attachDragToCards();
            });
            function attachDragToCards() {
                if (!accordion) return;
                var cards = accordion.querySelectorAll('.programme-day-card');
                var dragged = null;
                cards.forEach(function(card) {
                    card.draggable = true;
                    card.ondragstart = function(e) {
                        dragged = card;
                        e.dataTransfer.setData('text/plain', '');
                        e.dataTransfer.effectAllowed = 'move';
                        card.classList.add('opacity-50');
                    };
                    card.ondragend = function() {
                        card.classList.remove('opacity-50');
                        dragged = null;
                    };
                    card.ondragover = function(e) {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                        if (dragged && dragged !== card) card.classList.add('border-primary');
                    };
                    card.ondragleave = function() { card.classList.remove('border-primary'); };
                    card.ondrop = function(e) {
                        e.preventDefault();
                        card.classList.remove('border-primary');
                        if (!dragged || dragged === card) return;
                        var next = card.nextElementSibling;
                        accordion.insertBefore(dragged, next);
                        renumber();
                    };
                });
            }
        })();

        window.buildProgramFromForm = function() {
            var accordion = document.getElementById('accordionProgrammeDays');
            if (!accordion) return null;
            var cards = accordion.querySelectorAll('.programme-day-card');
            var program_days = [];
            cards.forEach(function(card, i) {
                var dayId = card.getAttribute('data-day-id') || '';
                var dayUid = dayId ? ('day-' + dayId) : ('new-' + i + '-' + Date.now());
                var titleInput = card.querySelector('input[name$="[day_title]"]');
                var notesInput = card.querySelector('textarea[name$="[notes]"]');
                var modeSelect = card.querySelector('select[name$="[mode]"]');
                var title = titleInput ? titleInput.value.trim() : '';
                var notes = notesInput ? notesInput.value.trim() : '';
                var mode = (modeSelect && modeSelect.value === 'free') ? 'free' : 'program';
                var items = [];
                card.querySelectorAll('.programme-activity-row').forEach(function(row, k) {
                    var actId = row.querySelector('input[name$="[activity_id]"]');
                    if (actId && actId.value) items.push({ uid: 'act-' + k, type: 'activity', ref_id: parseInt(actId.value, 10), sort: k });
                });
                program_days.push({ day_uid: dayUid, day_number: i + 1, title: title, notes: notes, mode: mode, items: items });
            });
            return { program_days: program_days };
        };

        window.autosaveProgram = function() {
            if (!window.PROGRAM_API_URL) return;
            var payload = window.buildProgramFromForm && window.buildProgramFromForm();
            if (!payload) return;
            var token = document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value;
            fetch(window.PROGRAM_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    var toast = document.createElement('div');
                    toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                    toast.style.cssText = 'top:16px;right:16px;z-index:9999;min-width:200px;';
                    toast.innerHTML = (data.message || 'Enregistré') + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    document.body.appendChild(toast);
                    setTimeout(function() { toast.remove(); }, 3000);
                }
            }).catch(function() {});
        };

        function updateProgrammeDayInclus(card) {
            if (!card) return;
            var list = card.querySelector('.programme-activities-list');
            var inclusEl = card.querySelector('.programme-day-inclus');
            if (!list || !inclusEl) return;
            var count = list.querySelectorAll('.programme-activity-row').length;
            inclusEl.textContent = 'INCLUS : ' + count + (count > 1 ? ' Activités' : ' Activité');
            // Mettre à jour aussi le résumé du jour
            var dayIndex = card.getAttribute('data-day-index');
            if (dayIndex != null && window.updateProgrammeDayExtras) {
                window.updateProgrammeDayExtras(dayIndex);
            }
        }

        function updateProgrammeDayExtras(dayIndex) {
            var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
            if (!card) return;
            var extrasEl = card.querySelector('.programme-day-extras');
            if (!extrasEl) return;
            var day = window.dayItemsManager ? window.dayItemsManager.getDay(dayIndex) : { hotel_id: null, transfer_ids: [], flights: [] };
            var dayNumber = parseInt(dayIndex || '0', 10) + 1;
            
            // Collecter toutes les données
            var sections = {
                activities: [],
                hotels: null,
                transfers: { arrival: [], departure: [] },
                flights: { outbound: null, inbound: null, internal: [] }
            };
            
            // 1. ACTIVITÉS : depuis le DOM
            var activitiesList = card.querySelector('.programme-activities-list');
            if (activitiesList) {
                activitiesList.querySelectorAll('.programme-activity-row').forEach(function(row) {
                    var titleEl = row.querySelector('.fw-medium');
                    var customTitleInp = row.querySelector('input[name*="[custom_title]"]');
                    var isIncludedEl = row.querySelector('input[name*="[is_included]"]');
                    var title = '';
                    if (customTitleInp && customTitleInp.value.trim()) {
                        title = customTitleInp.value.trim();
                    } else if (titleEl) {
                        title = titleEl.textContent.trim();
                    } else {
                        title = 'Activité';
                    }
                    var isIncluded = isIncludedEl ? isIncludedEl.checked : true;
                    sections.activities.push({ title: title, isIncluded: isIncluded });
                });
            }
            
            // 2. HÔTELS : depuis dayItemsManager OU depuis tour_hotels rows
            var hotelData = null;
            if (day.hotel_id && window.tourHotelsData && window.tourHotelsData[day.hotel_id]) {
                hotelData = window.tourHotelsData[day.hotel_id];
            } else {
                // Chercher dans tour_hotels rows (nouveau format : check_in_day / check_out_day)
                document.querySelectorAll('.tour-hotel-row').forEach(function(row) {
                    var checkInSel = row.querySelector('select[name^="tour_hotels["][name$="[check_in_day]"]');
                    var checkOutSel = row.querySelector('select[name^="tour_hotels["][name$="[check_out_day]"]');
                    var isInRange = false;
                    if (checkInSel && checkOutSel) {
                        var checkIn = parseInt(checkInSel.value || '1', 10);
                        var checkOut = parseInt(checkOutSel.value || '1', 10);
                        isInRange = (dayNumber >= checkIn && dayNumber <= checkOut);
                    } else {
                        // Compatibilité ancien format : day_number
                        var daySel = row.querySelector('select[name^="tour_hotels["][name$="[day_number]"]');
                        if (daySel && parseInt(daySel.value || '0', 10) === dayNumber) {
                            isInRange = true;
                        }
                    }
                    if (isInRange) {
                        var nameInp = row.querySelector('input[name^="tour_hotels["][name$="[hotel_name]"]');
                        var starsInp = row.querySelector('input[name^="tour_hotels["][name$="[stars]"]');
                        var roomInp = row.querySelector('input[name^="tour_hotels["][name$="[room_type]"]');
                        var optionalInp = row.querySelector('input[name^="tour_hotels["][name$="[is_optional]"]');
                        if (nameInp && nameInp.value.trim()) {
                            hotelData = {
                                hotel_name: nameInp.value.trim(),
                                stars: starsInp ? starsInp.value : null,
                                room_type: roomInp ? roomInp.value.trim() : '',
                                is_optional: optionalInp ? optionalInp.checked : false
                            };
                        }
                    }
                });
            }
            sections.hotels = hotelData;
            
            // 3. TRANSFERTS : depuis dayItemsManager ET depuis tour_transfer rows
            var transferIds = day.transfer_ids || [];
            var transferMap = {};
            if (window.tourTransfersData) {
                (window.tourTransfersData.arrival || []).concat(window.tourTransfersData.departure || []).forEach(function(t) {
                    if (transferIds.indexOf(t.id) !== -1) {
                        transferMap[t.id] = t;
                    }
                });
            }
            // Chercher aussi dans les lignes du formulaire principal (nouveau format unifié)
            document.querySelectorAll('.tour-transfer-row').forEach(function(row) {
                var daySel = row.querySelector('select[name*="[day_number]"]');
                if (daySel && parseInt(daySel.value || '0', 10) === dayNumber) {
                    var fromInp = row.querySelector('input[name*="[from_label]"]');
                    var toInp = row.querySelector('input[name*="[to_label]"]');
                    var vehicleInp = row.querySelector('input[name*="[vehicle_type]"]');
                    var pickupInp = row.querySelector('input[name*="[pickup_time]"]');
                    var dropoffInp = row.querySelector('input[name*="[dropoff_time]"]');
                    if (fromInp && toInp && (fromInp.value.trim() || toInp.value.trim())) {
                        // Par défaut, on utilise 'arrival' pour compatibilité avec le modèle
                        var transfer = {
                            from_label: fromInp.value.trim() || '',
                            to_label: toInp.value.trim() || '',
                            vehicle_type: vehicleInp ? vehicleInp.value.trim() : '',
                            pickup_time: pickupInp ? pickupInp.value.trim() : '',
                            dropoff_time: dropoffInp ? dropoffInp.value.trim() : '',
                            direction: 'arrival' // Par défaut pour compatibilité
                        };
                        sections.transfers.arrival.push(transfer);
                    }
                }
            });
            // Compatibilité ancien format : tour-transfer-arrival-row / tour-transfer-departure-row
            document.querySelectorAll('.tour-transfer-arrival-row, .tour-transfer-departure-row').forEach(function(row) {
                var daySel = row.querySelector('select[name*="[day_number]"]');
                if (daySel && parseInt(daySel.value || '0', 10) === dayNumber) {
                    var fromInp = row.querySelector('input[name*="[from_label]"]');
                    var toInp = row.querySelector('input[name*="[to_label]"]');
                    var vehicleInp = row.querySelector('input[name*="[vehicle_type]"]');
                    var pickupInp = row.querySelector('input[name*="[pickup_time]"]');
                    var dropoffInp = row.querySelector('input[name*="[dropoff_time]"]');
                    if (fromInp && toInp && (fromInp.value.trim() || toInp.value.trim())) {
                        var direction = row.classList.contains('tour-transfer-arrival-row') ? 'arrival' : 'departure';
                        var transfer = {
                            from_label: fromInp.value.trim() || '',
                            to_label: toInp.value.trim() || '',
                            vehicle_type: vehicleInp ? vehicleInp.value.trim() : '',
                            pickup_time: pickupInp ? pickupInp.value.trim() : '',
                            dropoff_time: dropoffInp ? dropoffInp.value.trim() : '',
                            direction: direction
                        };
                        sections.transfers[direction].push(transfer);
                    }
                }
            });
            // Ajouter les transferts depuis tourTransfersData
            Object.keys(transferMap).forEach(function(id) {
                var t = transferMap[id];
                sections.transfers[t.direction].push(t);
            });
            
            // 4. VOLS : depuis flight_options dans le formulaire principal
            document.querySelectorAll('.flight-opt-card, .card').forEach(function(flightCard) {
                var dayInp = flightCard.querySelector('select[name*="[day_number]"], input[name*="[day_number]"]');
                if (dayInp && parseInt(dayInp.value || '0', 10) === dayNumber && !dayInp.disabled) {
                    var typeSel = flightCard.querySelector('select[name*="[type]"]');
                    var fromInp = flightCard.querySelector('input[name*="[from_city]"]');
                    var toInp = flightCard.querySelector('input[name*="[to_city]"]');
                    var dateInp = flightCard.querySelector('input[name*="[departure_date]"]');
                    var timeInp = flightCard.querySelector('input[name*="[departure_time]"]');
                    var type = typeSel ? typeSel.value : 'internal';
                    var flight = {
                        from: fromInp ? fromInp.value.trim() : '',
                        to: toInp ? toInp.value.trim() : '',
                        date: dateInp ? dateInp.value.trim() : '',
                        time: timeInp ? timeInp.value.trim() : ''
                    };
                    if (type === 'outbound') {
                        sections.flights.outbound = flight;
                    } else if (type === 'inbound') {
                        sections.flights.inbound = flight;
                    } else {
                        sections.flights.internal.push(flight);
                    }
                }
            });
            
            // Générer le HTML structuré
            var html = '<div class="day-summary-container mt-2">';
            var hasAnyContent = false;
            
            // Activités
            if (sections.activities.length > 0) {
                hasAnyContent = true;
                html += '<div class="day-summary-card mb-2 border rounded p-2 bg-light">';
                html += '<div class="d-flex justify-content-between align-items-center mb-1">';
                html += '<div class="d-flex align-items-center gap-2"><i class="bx bx-list-check text-primary"></i><strong class="small">Activités (' + sections.activities.length + ')</strong></div>';
                html += '<button type="button" class="btn btn-xs btn-outline-primary btn-sm day-summary-config-btn" data-day-index="' + dayIndex + '" data-tab="activities" title="Configurer"><i class="bx bx-cog"></i></button>';
                html += '</div>';
                var visibleActs = sections.activities.slice(0, 3);
                visibleActs.forEach(function(act) {
                    html += '<div class="small text-muted mb-1">• ' + act.title;
                    if (act.isIncluded) html += ' <span class="badge bg-success">Inclus</span>';
                    else html += ' <span class="badge bg-warning text-dark">Optionnel</span>';
                    html += '</div>';
                });
                if (sections.activities.length > 3) {
                    html += '<div class="small text-muted">... et ' + (sections.activities.length - 3) + ' autre(s)</div>';
                }
                html += '</div>';
            }
            
            // Hôtels
            if (sections.hotels) {
                hasAnyContent = true;
                html += '<div class="day-summary-card mb-2 border rounded p-2 bg-light">';
                html += '<div class="d-flex justify-content-between align-items-center mb-1">';
                html += '<div class="d-flex align-items-center gap-2"><i class="bx bx-hotel text-primary"></i><strong class="small">Hôtel</strong></div>';
                html += '<div class="d-flex gap-1">';
                html += '<button type="button" class="btn btn-xs btn-outline-primary btn-sm day-summary-config-btn" data-day-index="' + dayIndex + '" data-tab="hotels" title="Configurer"><i class="bx bx-cog"></i></button>';
                html += '<button type="button" class="btn btn-xs btn-outline-danger btn-sm day-summary-remove-btn" data-day-index="' + dayIndex + '" data-type="hotel" title="Retirer"><i class="bx bx-trash"></i></button>';
                html += '</div></div>';
                html += '<div class="small text-muted mb-1"><strong>' + sections.hotels.hotel_name + '</strong>';
                if (sections.hotels.stars) {
                    var stars = '';
                    for (var i = 0; i < parseInt(sections.hotels.stars, 10); i++) stars += '★';
                    html += ' <span class="badge bg-warning text-dark">' + stars + '</span>';
                }
                if (sections.hotels.room_type) html += ' • ' + sections.hotels.room_type;
                if (sections.hotels.is_optional) html += ' <span class="badge bg-warning text-dark">Option client</span>';
                html += '</div>';
                html += '</div>';
            }
            
            // Transferts
            var totalTransfers = sections.transfers.arrival.length + sections.transfers.departure.length;
            if (totalTransfers > 0) {
                hasAnyContent = true;
                html += '<div class="day-summary-card mb-2 border rounded p-2 bg-light">';
                html += '<div class="d-flex justify-content-between align-items-center mb-1">';
                html += '<div class="d-flex align-items-center gap-2"><i class="bx bx-car text-primary"></i><strong class="small">Transferts (' + totalTransfers + ')</strong></div>';
                html += '<div class="d-flex gap-1">';
                html += '<button type="button" class="btn btn-xs btn-outline-primary btn-sm day-summary-config-btn" data-day-index="' + dayIndex + '" data-tab="transfers" title="Configurer"><i class="bx bx-cog"></i></button>';
                html += '<button type="button" class="btn btn-xs btn-outline-danger btn-sm day-summary-remove-btn" data-day-index="' + dayIndex + '" data-type="transfers" title="Tout retirer"><i class="bx bx-trash"></i></button>';
                html += '</div></div>';
                if (sections.transfers.arrival.length > 0) {
                    html += '<div class="small mb-1"><span class="badge bg-success">Arrivée</span>';
                    sections.transfers.arrival.slice(0, 2).forEach(function(t) {
                        html += ' <span class="text-muted">' + (t.from_label || '?') + ' → ' + (t.to_label || '?');
                        if (t.vehicle_type) html += ' <small>(' + t.vehicle_type + ')</small>';
                        html += '</span>';
                    });
                    if (sections.transfers.arrival.length > 2) html += ' <small class="text-muted">+ ' + (sections.transfers.arrival.length - 2) + ' autre(s)</small>';
                    html += '</div>';
                }
                if (sections.transfers.departure.length > 0) {
                    html += '<div class="small mb-1"><span class="badge bg-danger">Départ</span>';
                    sections.transfers.departure.slice(0, 2).forEach(function(t) {
                        html += ' <span class="text-muted">' + (t.from_label || '?') + ' → ' + (t.to_label || '?');
                        if (t.vehicle_type) html += ' <small>(' + t.vehicle_type + ')</small>';
                        html += '</span>';
                    });
                    if (sections.transfers.departure.length > 2) html += ' <small class="text-muted">+ ' + (sections.transfers.departure.length - 2) + ' autre(s)</small>';
                    html += '</div>';
                }
                html += '</div>';
            }
            
            // Vols
            var totalFlights = (sections.flights.outbound ? 1 : 0) + (sections.flights.inbound ? 1 : 0) + sections.flights.internal.length;
            if (totalFlights > 0) {
                hasAnyContent = true;
                html += '<div class="day-summary-card mb-2 border rounded p-2 bg-light">';
                html += '<div class="d-flex justify-content-between align-items-center mb-1">';
                html += '<div class="d-flex align-items-center gap-2"><i class="bx bx-trip text-primary"></i><strong class="small">Vols (' + totalFlights + ')</strong></div>';
                html += '<div class="d-flex gap-1">';
                html += '<button type="button" class="btn btn-xs btn-outline-primary btn-sm day-summary-config-btn" data-day-index="' + dayIndex + '" data-tab="flights" title="Configurer"><i class="bx bx-cog"></i></button>';
                html += '<button type="button" class="btn btn-xs btn-outline-danger btn-sm day-summary-remove-btn" data-day-index="' + dayIndex + '" data-type="flights" title="Tout retirer"><i class="bx bx-trash"></i></button>';
                html += '</div></div>';
                if (sections.flights.outbound) {
                    html += '<div class="small mb-1"><span class="badge bg-info">Aller</span> <span class="text-muted">' + (sections.flights.outbound.from || '?') + ' → ' + (sections.flights.outbound.to || '?');
                    if (sections.flights.outbound.date) html += ' <small>(' + sections.flights.outbound.date + ')</small>';
                    html += '</span></div>';
                }
                if (sections.flights.inbound) {
                    html += '<div class="small mb-1"><span class="badge bg-info">Retour</span> <span class="text-muted">' + (sections.flights.inbound.from || '?') + ' → ' + (sections.flights.inbound.to || '?');
                    if (sections.flights.inbound.date) html += ' <small>(' + sections.flights.inbound.date + ')</small>';
                    html += '</span></div>';
                }
                if (sections.flights.internal.length > 0) {
                    html += '<div class="small mb-1"><span class="badge bg-secondary">Internes</span>';
                    sections.flights.internal.slice(0, 2).forEach(function(f) {
                        html += ' <span class="text-muted">' + (f.from || '?') + ' → ' + (f.to || '?') + '</span>';
                    });
                    if (sections.flights.internal.length > 2) html += ' <small class="text-muted">+ ' + (sections.flights.internal.length - 2) + ' autre(s)</small>';
                    html += '</div>';
                }
                html += '</div>';
            }
            
            if (!hasAnyContent) {
                html += '<div class="day-summary-card mb-2 border rounded p-2 bg-light text-center">';
                html += '<div class="small text-muted mb-2">Aucun élément configuré</div>';
                html += '<button type="button" class="btn btn-sm btn-outline-primary day-summary-config-btn" data-day-index="' + dayIndex + '" data-tab="activities"><i class="bx bx-plus"></i> Configurer</button>';
                html += '</div>';
            }
            
            html += '</div>';
            extrasEl.innerHTML = html;
        }
        window.updateProgrammeDayExtras = updateProgrammeDayExtras;

        document.addEventListener('day-builder:item-count-changed', function(e) {
            var d = e.detail || {};
            if (d.dayIndex != null && window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(d.dayIndex);
        });
        
        // Gestionnaires pour les boutons du résumé du jour
        document.addEventListener('click', function(e) {
            // Bouton "Configurer" : ouvre le drawer sur l'onglet spécifié
            var configBtn = e.target.closest('.day-summary-config-btn');
            if (configBtn) {
                e.preventDefault();
                var dayIndex = configBtn.getAttribute('data-day-index');
                var tab = configBtn.getAttribute('data-tab');
                var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                if (!card) return;
                var dayNumber = parseInt(dayIndex || '0', 10) + 1;
                var dayId = card.getAttribute('data-day-id') || '';
                // Trouver le bouton "Ajouter un élément" pour ce jour et l'utiliser pour ouvrir le drawer
                var addBtn = card.querySelector('.btn-add-element-to-day');
                if (addBtn) {
                    // Déclencher l'événement pour ouvrir le drawer avec le bon contexte
                    document.dispatchEvent(new CustomEvent('day-builder:set-day', {
                        detail: {
                            dayIndex: String(dayIndex),
                            dayId: dayId,
                            dayNumber: dayNumber
                        }
                    }));
                    // Ouvrir le drawer via la fonction existante
                    var drawer = document.getElementById('day-builder-drawer');
                    if (drawer && window.bootstrap && bootstrap.Offcanvas) {
                        var offcanvas = bootstrap.Offcanvas.getOrCreateInstance(drawer);
                        offcanvas.show();
                        // Activer l'onglet demandé après un court délai
                        setTimeout(function() {
                            var tabButton = drawer.querySelector('[data-bs-target="#day-builder-tab-' + tab + '"]');
                            if (tabButton && bootstrap.Tab) {
                                bootstrap.Tab.getOrCreateInstance(tabButton).show();
                            }
                        }, 150);
                    }
                }
                return;
            }
            
            // Bouton "Retirer" : retire l'élément du jour
            var removeBtn = e.target.closest('.day-summary-remove-btn');
            if (removeBtn) {
                e.preventDefault();
                var dayIndex = removeBtn.getAttribute('data-day-index');
                var type = removeBtn.getAttribute('data-type');
                var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                if (!card) return;
                var dayNumber = parseInt(dayIndex || '0', 10) + 1;
                var confirmMsg = '';
                if (type === 'hotel') {
                    confirmMsg = 'Retirer l\'hôtel du Jour ' + dayNumber + ' ?';
                } else if (type === 'transfers') {
                    confirmMsg = 'Retirer tous les transferts du Jour ' + dayNumber + ' ?';
                } else if (type === 'flights') {
                    confirmMsg = 'Retirer tous les vols du Jour ' + dayNumber + ' ?';
                }
                if (!confirm(confirmMsg)) return;
                
                if (window.dayItemsManager) {
                    if (type === 'hotel') {
                        window.dayItemsManager.setHotel(dayIndex, null);
                    } else if (type === 'transfers') {
                        window.dayItemsManager.setTransfers(dayIndex, []);
                    } else if (type === 'flights') {
                        window.dayItemsManager.setFlights(dayIndex, []);
                    }
                    window.dayItemsManager.syncToForm(dayIndex);
                    document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', {
                        detail: { dayIndex: dayIndex }
                    }));
                    if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIndex);
                }
                return;
            }
        });
        // Mettre à jour les extras quand un vol change dans le formulaire principal (onglet Vols)
        document.addEventListener('change', function(e) {
            if (!e.target || !e.target.name) return;
            if (e.target.name.indexOf('flight_options[') === 0 && e.target.name.indexOf('[day_number]') !== -1) {
                var dayNumber = parseInt(e.target.value || '0', 10);
                if (dayNumber >= 1) {
                    var dayIndex = String(dayNumber - 1);
                    if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIndex);
                }
            }
            // Mettre à jour quand un hôtel change dans tour_hotels (onglet Hôtels)
            // Nouveau format : check_in_day / check_out_day
            if (e.target.name && e.target.name.indexOf('tour_hotels[') === 0 && 
                (e.target.name.indexOf('[check_in_day]') !== -1 || e.target.name.indexOf('[check_out_day]') !== -1)) {
                var hotelRow = e.target.closest('.tour-hotel-row');
                if (hotelRow) {
                    var checkInSel = hotelRow.querySelector('select[name^="tour_hotels["][name$="[check_in_day]"]');
                    var checkOutSel = hotelRow.querySelector('select[name^="tour_hotels["][name$="[check_out_day]"]');
                    if (checkInSel && checkOutSel) {
                        var checkIn = parseInt(checkInSel.value || '1', 10);
                        var checkOut = parseInt(checkOutSel.value || '1', 10);
                        var hotelId = hotelRow.getAttribute('data-hotel-id');
                        // Mettre à jour tous les jours dans la plage check-in -> check-out
                        if (hotelId && window.dayItemsManager) {
                            for (var d = checkIn; d <= checkOut; d++) {
                                var dayIndex = String(d - 1);
                                window.dayItemsManager.setHotel(dayIndex, parseInt(hotelId, 10));
                                window.dayItemsManager.syncToForm(dayIndex);
                                if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIndex);
                            }
                            // Retirer l'hôtel des jours hors de la plage
                            var allDays = document.querySelectorAll('.programme-day-card');
                            allDays.forEach(function(card) {
                                var dayIdx = card.getAttribute('data-day-index');
                                var dayNum = parseInt(dayIdx || '0', 10) + 1;
                                if (dayNum < checkIn || dayNum > checkOut) {
                                    var currentHotelId = window.dayItemsManager.getHotel(dayIdx);
                                    if (currentHotelId == hotelId) {
                                        window.dayItemsManager.setHotel(dayIdx, null);
                                        window.dayItemsManager.syncToForm(dayIdx);
                                        if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIdx);
                                    }
                                }
                            });
                        }
                    }
                }
            }
            // Compatibilité ancien format : day_number
            if (e.target.name && e.target.name.indexOf('tour_hotels[') === 0 && e.target.name.indexOf('[day_number]') !== -1) {
                var dayNumber = parseInt(e.target.value || '0', 10);
                if (dayNumber >= 1) {
                    var dayIndex = String(dayNumber - 1);
                    if (window.dayItemsManager) {
                        var hotelRow = e.target.closest('.tour-hotel-row');
                        if (hotelRow) {
                            var idx = hotelRow.getAttribute('data-index');
                            var hotelId = hotelRow.getAttribute('data-hotel-id');
                            if (hotelId) {
                                window.dayItemsManager.setHotel(dayIndex, parseInt(hotelId, 10));
                                window.dayItemsManager.syncToForm(dayIndex);
                                if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIndex);
                            }
                        }
                    }
                }
            }
        });

        function appendActivityToDay(dayIndex, activityId, activityTitle) {
            if (dayIndex === null || dayIndex === '' || !activityId) return false;
            var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
            var list = card && card.querySelector('.programme-activities-list');
            if (!list) return false;
            var k = list.querySelectorAll('.programme-activity-row').length;
            var prefix = 'programme_days[' + dayIndex + '][activities][' + k + ']';
            var row = document.createElement('div');
            row.className = 'programme-activity-row card mb-2';
            row.setAttribute('data-day-activity-id', '0');
            row.setAttribute('draggable', 'true');
            row.innerHTML = '<div class="card-body py-2"><div class="d-flex flex-wrap align-items-start gap-2">' +
                '<span class="programme-activity-drag-handle text-muted cursor-grab me-1" title="Réordonner"><i class="bx bx-dots-vertical-rounded"></i></span>' +
                '<input type="hidden" name="' + prefix + '[day_activity_id]" value="">' +
                '<input type="hidden" name="' + prefix + '[activity_id]" value="' + activityId + '">' +
                '<input type="hidden" name="' + prefix + '[sort_order]" value="' + k + '">' +
                '<span class="fw-medium">' + (activityTitle || 'Activité').replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</span>' +
                '<span class="form-check form-check-inline mb-0"><input type="hidden" name="' + prefix + '[is_included]" value="0"><input class="form-check-input" type="checkbox" name="' + prefix + '[is_included]" value="1" checked><label class="form-check-label small">Inclus</label></span>' +
                '<span class="form-check form-check-inline mb-0"><input type="hidden" name="' + prefix + '[is_mandatory]" value="0"><input class="form-check-input" type="checkbox" name="' + prefix + '[is_mandatory]" value="1"><label class="form-check-label small">Obligatoire</label></span>' +
                '<input type="text" class="form-control form-control-sm" style="max-width:200px" name="' + prefix + '[custom_title]" placeholder="Titre">' +
                '<textarea class="form-control form-control-sm" name="' + prefix + '[custom_description]" rows="1" placeholder="Description"></textarea>' +
                '<button type="button" class="btn btn-sm btn-outline-danger remove-programme-activity"><i class="bx bx-trash"></i></button></div></div>';
            list.appendChild(row);
            updateProgrammeDayInclus(card);
            document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', {
                detail: { dayIndex: String(dayIndex), count: list.querySelectorAll('.programme-activity-row').length }
            }));
            return true;
        }

        (function dayBuilderDrawerManager() {
            var drawer = document.getElementById('day-builder-drawer');
            if (!drawer || !window.bootstrap || !bootstrap.Offcanvas) return;

            var titleEl = document.getElementById('day-builder-drawer-label');
            var summaryEl = document.getElementById('day-builder-day-summary');
            var contextEl = document.getElementById('day-builder-drawer-context');
            var flightsManager = document.getElementById('day-builder-flights-manager');
            var offcanvas = bootstrap.Offcanvas.getOrCreateInstance(drawer);

            // ===== GESTIONNAIRE D'ÉTAT UNIFIÉ POUR VOLS/HÔTELS/TRANSFERTS PAR JOUR =====
            window.dayItemsManager = {
                // État interne : {dayIndex: {flights: [], hotel_id: null, transfer_ids: []}}
                state: {},

                // Initialiser depuis le formulaire (programme_days[X][...])
                // Puis charger l'état depuis les inputs hidden (pré-remplis par le serveur pour hotel/transferts)
                init: function() {
                    this.state = {};
                    var cards = document.querySelectorAll('.programme-day-card');
                    cards.forEach(function(card, idx) {
                        var dayId = card.getAttribute('data-day-id');
                        window.dayItemsManager.state[String(idx)] = {
                            dayId: dayId,
                            flights: [],
                            hotel_id: null,
                            transfer_ids: []
                        };
                    });
                    var self = this;
                    cards.forEach(function(card, idx) {
                        self.loadFromForm(String(idx));
                    });
                },

                // Obtenir l'état pour un jour
                getDay: function(dayIndex) {
                    var key = String(dayIndex);
                    if (!this.state[key]) {
                        this.state[key] = { dayId: null, flights: [], hotel_id: null, transfer_ids: [] };
                    }
                    return this.state[key];
                },

                // Défaut les vols pour un jour
                setFlights: function(dayIndex, flightIds) {
                    var day = this.getDay(dayIndex);
                    day.flights = Array.isArray(flightIds) ? flightIds : (flightIds ? [flightIds] : []);
                    this.syncToForm(dayIndex);
                },

                // Obtenir les vols pour un jour
                getFlights: function(dayIndex) {
                    return (this.getDay(dayIndex).flights || []).slice();
                },

                // Défaut l'hôtel pour un jour
                setHotel: function(dayIndex, hotelId) {
                    var day = this.getDay(dayIndex);
                    day.hotel_id = hotelId || null;
                    this.syncToForm(dayIndex);
                },

                // Obtenir l'hôtel pour un jour
                getHotel: function(dayIndex) {
                    return this.getDay(dayIndex).hotel_id;
                },

                // Défaut les transferts pour un jour
                setTransfers: function(dayIndex, transferIds) {
                    var day = this.getDay(dayIndex);
                    day.transfer_ids = Array.isArray(transferIds) ? transferIds : (transferIds ? [transferIds] : []);
                    this.syncToForm(dayIndex);
                },

                // Obtenir les transferts pour un jour
                getTransfers: function(dayIndex) {
                    return (this.getDay(dayIndex).transfer_ids || []).slice();
                },

                // Synchroniser l'état avec le formulaire (écrire dans les inputs hidden)
                syncToForm: function(dayIndex) {
                    var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                    if (!card) return;

                    var day = this.getDay(dayIndex);

                    // Synchroniser vols
                    var flightsInput = card.querySelector('input[name^="programme_days["][name$="[flights]"]');
                    if (flightsInput) {
                        flightsInput.value = day.flights.join(',');
                    }

                    // Synchroniser hôtel
                    var hotelInput = card.querySelector('input[name^="programme_days["][name$="[hotel_id]"]');
                    if (hotelInput) {
                        hotelInput.value = day.hotel_id || '';
                    }

                    // Synchroniser transferts
                    var transferInput = card.querySelector('input[name^="programme_days["][name$="[transfer_ids]"]');
                    if (transferInput) {
                        transferInput.value = day.transfer_ids.join(',');
                    }
                },

                // Charger depuis le formulaire (lire les inputs hidden existants)
                loadFromForm: function(dayIndex) {
                    var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                    if (!card) return;

                    var day = this.getDay(dayIndex);

                    var flightsInput = card.querySelector('input[name^="programme_days["][name$="[flights]"]');
                    if (flightsInput && flightsInput.value) {
                        day.flights = flightsInput.value.split(',').map(function(id) { return parseInt(id.trim(), 10); }).filter(function(id) { return id > 0; });
                    }

                    var hotelInput = card.querySelector('input[name^="programme_days["][name$="[hotel_id]"]');
                    if (hotelInput && hotelInput.value) {
                        day.hotel_id = parseInt(hotelInput.value, 10);
                    }

                    var transferInput = card.querySelector('input[name^="programme_days["][name$="[transfer_ids]"]');
                    if (transferInput && transferInput.value) {
                        day.transfer_ids = transferInput.value.split(',').map(function(id) { return parseInt(id.trim(), 10); }).filter(function(id) { return id > 0; });
                    }
                },

                // Compter tous les items (activités + vols + hôtel + transferts)
                countItems: function(dayIndex) {
                    var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                    var list = card && card.querySelector('.programme-activities-list');
                    var actCount = list ? list.querySelectorAll('.programme-activity-row').length : 0;
                    var day = this.getDay(dayIndex);
                    var otherCount = (day.flights ? day.flights.length : 0) + (day.hotel_id ? 1 : 0) + (day.transfer_ids ? day.transfer_ids.length : 0);
                    return actCount + otherCount;
                }
            };

            // Initialiser le gestionnaire au chargement
            window.dayItemsManager.init();
            // Charger les données depuis le formulaire et afficher Hôtel / Transferts / Vols dans chaque carte de jour
            var cards = document.querySelectorAll('.programme-day-card');
            cards.forEach(function(card) {
                var dayIndex = card.getAttribute('data-day-index');
                if (dayIndex != null) {
                    window.dayItemsManager.loadFromForm(dayIndex);
                    if (window.updateProgrammeDayExtras) window.updateProgrammeDayExtras(dayIndex);
                }
            });

            function getDayItemsCount(dayIndex) {
                return window.dayItemsManager.countItems(dayIndex);
            }

            function updateDrawerSummary(dayNum, dayIndex) {
                if (!summaryEl) return;
                var count = getDayItemsCount(dayIndex);
                summaryEl.textContent = 'Jour ' + dayNum + ' — Ajouter (' + count + (count > 1 ? ' éléments)' : ' élément)');
            }

            function setDrawerContext(dayIndex, dayId, dayNumber) {
                var dayNum = parseInt(dayNumber || '0', 10);
                if (!dayNum || dayNum < 1) {
                    var parsedIndex = parseInt(dayIndex || '0', 10);
                    dayNum = isNaN(parsedIndex) ? 1 : (parsedIndex + 1);
                }

                drawer.setAttribute('data-day-index', dayIndex || '');
                drawer.setAttribute('data-day-id', dayId || '');
                drawer.setAttribute('data-day-number', String(dayNum));

                if (titleEl) titleEl.textContent = 'Jour ' + dayNum + ' — Ajouter';
                updateDrawerSummary(dayNum, dayIndex || String(dayNum - 1));
                if (contextEl) contextEl.textContent = 'Ajout direct dans les éléments du Jour ' + dayNum + '.';

                if (flightsManager) {

            drawer.addEventListener('shown.bs.offcanvas', function() {
                document.body.style.overflow = 'hidden';
                document.body.classList.add('day-builder-open');
            });

            drawer.addEventListener('hidden.bs.offcanvas', function() {
                document.body.style.overflow = '';
                document.body.classList.remove('day-builder-open');
            });

            document.addEventListener('day-builder:item-count-changed', function(e) {
                var detail = (e && e.detail) ? e.detail : {};
                var activeDayIndex = drawer.getAttribute('data-day-index');
                if (String(detail.dayIndex) !== String(activeDayIndex)) return;
                var dayNumber = parseInt(drawer.getAttribute('data-day-number') || '1', 10) || 1;
                updateDrawerSummary(dayNumber, activeDayIndex);
            });
                    var manager = flightsManager.querySelector('.flight-manager');
                    if (manager) manager.setAttribute('data-day-number', String(dayNum));
                }

                document.dispatchEvent(new CustomEvent('day-builder:context-changed', {
                    detail: {
                        dayIndex: dayIndex || '',
                        dayId: dayId || '',
                        dayNumber: dayNum
                    }
                }));
            }

            function openForButton(btn, forcedTab) {
                if (!btn) return;
                setDrawerContext(
                    btn.getAttribute('data-day-index') || '',
                    btn.getAttribute('data-day-id') || '',
                    btn.getAttribute('data-day-number') || ''
                );

                offcanvas.show();

                if (forcedTab) {
                    var tabButton = drawer.querySelector('[data-bs-target="#day-builder-tab-' + forcedTab + '"]');
                    if (tabButton && bootstrap.Tab) {
                        bootstrap.Tab.getOrCreateInstance(tabButton).show();
                    }
                }
            }

            document.addEventListener('click', function(e) {
                var openBtn = e.target.closest('.btn-add-element-to-day');
                if (openBtn) {
                    e.preventDefault();
                    openForButton(openBtn);
                    return;
                }

                var addBtn = e.target.closest('.day-builder-add-activity');
                if (!addBtn) return;

                e.preventDefault();
                var dayIndex = drawer.getAttribute('data-day-index');
                var activityId = addBtn.getAttribute('data-activity-id');
                var activityTitle = addBtn.getAttribute('data-activity-title') || 'Activité';
                if (!appendActivityToDay(dayIndex, activityId, activityTitle)) return;
                if (window.autosaveProgram) window.autosaveProgram();
            });

            document.addEventListener('day-builder:set-day', function(e) {
                var detail = (e && e.detail) ? e.detail : {};
                var dayNumber = parseInt(detail.dayNumber || '0', 10);
                if (!dayNumber || dayNumber < 1) return;
                var targetCard = document.querySelector('.programme-day-card[data-day-index="' + (dayNumber - 1) + '"]');
                if (!targetCard) return;
                var helperBtn = targetCard.querySelector('.btn-add-element-to-day');
                if (!helperBtn) return;
                openForButton(helperBtn, detail.tab || 'flights');
            });
        })();

        (function programmeActivityDragDrop() {
            var draggedActivityRow = null;
            document.addEventListener('dragstart', function(e) {
                var row = e.target.closest('.programme-activity-row');
                if (!row || e.target.closest('.remove-programme-activity')) return;
                draggedActivityRow = row;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', '');
                row.classList.add('opacity-50');
            });
            document.addEventListener('dragend', function(e) {
                if (draggedActivityRow) draggedActivityRow.classList.remove('opacity-50');
                draggedActivityRow = null;
            });
            document.addEventListener('dragover', function(e) {
                var list = e.target.closest('.programme-activities-list');
                if (list && draggedActivityRow && list.contains(draggedActivityRow)) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                }
            });
            document.addEventListener('drop', function(e) {
                var list = e.target.closest('.programme-activities-list');
                if (!list || !draggedActivityRow || !list.contains(draggedActivityRow)) return;
                e.preventDefault();
                var targetRow = e.target.closest('.programme-activity-row');
                if (targetRow === draggedActivityRow) { draggedActivityRow = null; return; }
                var card = list.closest('.programme-day-card');
                if (targetRow) {
                    if (targetRow.compareDocumentPosition(draggedActivityRow) === 4) list.insertBefore(draggedActivityRow, targetRow);
                    else list.insertBefore(draggedActivityRow, targetRow.nextSibling);
                } else {
                    list.appendChild(draggedActivityRow);
                }
                var rows = list.querySelectorAll('.programme-activity-row');
                rows.forEach(function(r, i) {
                    r.querySelectorAll('[name^="programme_days["]').forEach(function(el) {
                        el.name = el.name.replace(/\]\[activities\]\[\d+\]/, '][activities][' + i + ']');
                    });
                });
                if (window.autosaveProgram) window.autosaveProgram();
                draggedActivityRow = null;
            });
        })();

        // Programme (Jours): Add activity to day (délégation pour les jours ajoutés dynamiquement)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.add-activity-to-day')) {
                var btn = e.target.closest('.add-activity-to-day');
                var dayIndex = btn.getAttribute('data-day-index');
                var card = btn.closest('.programme-day-card') || btn.closest('.accordion-item');
                var select = card ? card.querySelector('.add-activity-select') : null;
                var activityId = select && select.value;
                var activityTitle = select && select.options[select.selectedIndex] && select.options[select.selectedIndex].text;
                if (!activityId || dayIndex === null) return;
                if (!appendActivityToDay(dayIndex, activityId, activityTitle)) return;
                select.value = '';
                if (window.autosaveProgram) window.autosaveProgram();
            }
            if (e.target.closest('.remove-programme-activity')) {
                var row = e.target.closest('.programme-activity-row');
                if (row && confirm('Retirer cette activité du jour ?')) {
                    var card = row.closest('.programme-day-card');
                    var dayIndex = card ? card.getAttribute('data-day-index') : null;
                    row.remove();
                    updateProgrammeDayInclus(card);
                    if (dayIndex !== null) {
                        var list = card ? card.querySelector('.programme-activities-list') : null;
                        document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', {
                            detail: { dayIndex: String(dayIndex), count: list ? list.querySelectorAll('.programme-activity-row').length : 0 }
                        }));
                    }
                    if (window.autosaveProgram) window.autosaveProgram();
                }
            }
        });

        // ——— Onglet Vols : boutons Ajouter / Modifier / Enregistrer / Annuler / REMOVE ———
        (function flightOptionsHandlers() {
            var templatesEl = document.getElementById('flight-opt-templates');
            var nextIndexEl = document.getElementById('flight-opt-next-index');
            var dash = '—';

            function getNextIndex() {
                if (!nextIndexEl) return 0;
                var n = parseInt(nextIndexEl.value, 10) || 0;
                nextIndexEl.value = n + 1;
                return n;
            }

            document.addEventListener('click', function(e) {
                // Ajouter un vol (Aller / Retour / segment)
                if (e.target.closest('.btn-add-flight-opt')) {
                    var btn = e.target.closest('.btn-add-flight-opt');
                    var type = btn.getAttribute('data-type');
                    var lastDay = btn.getAttribute('data-day') || '1';
                    if (!templatesEl || !type) return;
                    var tpl = templatesEl.querySelector('[data-flight-tpl="' + type + '"]');
                    if (!tpl) return;
                    var card = tpl.querySelector('.flight-opt-card');
                    if (!card) return;
                    var idx = getNextIndex();
                    var clone = card.cloneNode(true);
                    clone.setAttribute('data-flight-opt-index', idx);
                    clone.querySelectorAll('[name^="flight_options["]').forEach(function(el) {
                        el.name = el.name.replace(/flight_options\[-1\]/, 'flight_options[' + idx + ']');
                        el.removeAttribute('disabled');
                    });
                    clone.querySelectorAll('.flight-opt-view .flight-opt-route, .flight-opt-dep-date, .flight-opt-arr-date, .flight-opt-dep-time, .flight-opt-arr-time, .flight-opt-from, .flight-opt-to, .flight-opt-cabin-bag, .flight-opt-checkin-bag').forEach(function(span) {
                        if (span && span.textContent !== undefined) span.textContent = dash;
                    });
                    var editPanel = clone.querySelector('.flight-opt-edit');
                    var viewPanel = clone.querySelector('.flight-opt-view');
                    if (editPanel) editPanel.style.display = 'none';
                    if (viewPanel) viewPanel.style.display = '';
                    var badgeWrap = clone.querySelector('.flight-opt-badge');
                    if (badgeWrap) badgeWrap.style.display = 'none';
                    var container = document.querySelector('.flight-opt-cards-' + type);
                    if (container) container.appendChild(clone);
                    return;
                }

                // Supprimer un vol (REMOVE)
                if (e.target.closest('.flight-opt-remove')) {
                    var card = e.target.closest('.flight-opt-card');
                    if (card && confirm('Supprimer ce vol ?')) card.remove();
                    return;
                }

                // Modifier
                if (e.target.closest('.flight-opt-edit-btn')) {
                    var card = e.target.closest('.flight-opt-card');
                    if (!card) return;
                    var view = card.querySelector('.flight-opt-view');
                    var edit = card.querySelector('.flight-opt-edit');
                    if (view) view.style.display = 'none';
                    if (edit) edit.style.display = 'block';
                    return;
                }

                // Enregistrer : mise à jour des libellés en vue puis soumission du formulaire pour sauvegarder côté serveur
                if (e.target.closest('.flight-opt-save-btn')) {
                    var card = e.target.closest('.flight-opt-card');
                    if (!card) return;
                    var edit = card.querySelector('.flight-opt-edit');
                    var view = card.querySelector('.flight-opt-view');
                    var fromCity = edit && edit.querySelector('input[name*="[from_city]"]');
                    var toCity = edit && edit.querySelector('input[name*="[to_city]"]');
                    var depDate = edit && edit.querySelector('input[name*="[departure_date]"]');
                    var depTime = edit && edit.querySelector('input[name*="[departure_time]"]');
                    var arrTime = edit && edit.querySelector('input[name*="[arrival_time]"]');
                    var cabinKg = edit && edit.querySelector('input[name*="[baggage_cabin_kg]"]');
                    var checkinKg = edit && edit.querySelector('input[name*="[baggage_checkin_kg]"]');
                    var tentativeCb = edit && edit.querySelector('input[name*="[is_tentative]"]');
                    var route = view && view.querySelector('.flight-opt-route');
                    var depDateEl = view && view.querySelector('.flight-opt-dep-date');
                    var arrDateEl = view && view.querySelector('.flight-opt-arr-date');
                    var depTimeEl = view && view.querySelector('.flight-opt-dep-time');
                    var arrTimeEl = view && view.querySelector('.flight-opt-arr-time');
                    var fromEl = view && view.querySelector('.flight-opt-from');
                    var toEl = view && view.querySelector('.flight-opt-to');
                    var cabinBagEl = view && view.querySelector('.flight-opt-cabin-bag');
                    var checkinBagEl = view && view.querySelector('.flight-opt-checkin-bag');
                    var badgeWrap = view && view.querySelector('.flight-opt-badge');
                    if (route) route.textContent = (fromCity && fromCity.value ? fromCity.value : dash) + ' → ' + (toCity && toCity.value ? toCity.value : dash);
                    var d = depDate && depDate.value ? depDate.value : dash;
                    if (depDateEl) depDateEl.textContent = d;
                    if (arrDateEl) arrDateEl.textContent = d;
                    if (depTimeEl) depTimeEl.textContent = (depTime && depTime.value) ? depTime.value : dash;
                    if (arrTimeEl) arrTimeEl.textContent = (arrTime && arrTime.value) ? arrTime.value : dash;
                    if (fromEl) fromEl.textContent = fromCity && fromCity.value ? fromCity.value : dash;
                    if (toEl) toEl.textContent = toCity && toCity.value ? toCity.value : dash;
                    if (cabinBagEl) cabinBagEl.textContent = cabinKg && cabinKg.value ? cabinKg.value + ' kg' : dash;
                    if (checkinBagEl) checkinBagEl.textContent = checkinKg && checkinKg.value ? checkinKg.value + ' kg' : dash;
                    if (badgeWrap) badgeWrap.style.display = (tentativeCb && tentativeCb.checked) ? '' : 'none';
                    if (view) view.style.display = '';
                    if (edit) edit.style.display = 'none';
                    // Soumettre le formulaire principal pour enregistrer les flight_options (lieu de départ, heures, etc.) côté serveur
                    var form = document.getElementById('edit-voyage-form');
                    if (form) {
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    }
                    return;
                }

                // Annuler
                if (e.target.closest('.flight-opt-cancel-btn')) {
                    var card = e.target.closest('.flight-opt-card');
                    if (!card) return;
                    var view = card.querySelector('.flight-opt-view');
                    var edit = card.querySelector('.flight-opt-edit');
                    if (view) view.style.display = '';
                    if (edit) edit.style.display = 'none';
                }
            });
        })();

        // ——— Secours : bouton « Enregistrer toutes les modifications » (soumission forcée si le clic est intercepté) ———
        (function() {
            function initSaveButtonFallback() {
                var btn = document.getElementById('edit-voyage-submit-btn');
                var form = document.getElementById('edit-voyage-form');
                if (!btn || !form) return;
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    var acc = document.getElementById('accordionProgrammeDays');
                    var durationInput = document.getElementById('duration_day');
                    if (acc && durationInput) {
                        var n = acc.querySelectorAll('.programme-day-card').length;
                        durationInput.value = n > 0 ? n : (durationInput.value || 1);
                    }
                    // requestSubmit() déclenche la validation HTML5 (required, etc.) avant envoi
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }, true);
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSaveButtonFallback);
            } else {
                initSaveButtonFallback();
            }
        })();

        // ——— MODE DIAGNOSTIC: Forcer retrait des disabled + logs détaillés (À RETIRER en production) ———
        (function diagnosticMode() {
            console.log('🔧 DIAGNOSTIC MODE - Flight Options Persistence (v2 - Ignore Templates)');
            
            function removeDisabledFromFlightOptions() {
                var count = 0;
                var templatesContainer = document.getElementById('flight-opt-templates');
                var drawerContainer = document.getElementById('day-builder-drawer');
                
                document.querySelectorAll('[name^="flight_options"]').forEach(function(el) {
                    // SKIP les inputs dans le container de templates
                    if (templatesContainer && templatesContainer.contains(el)) {
                        return; // Ne PAS retirer disabled des templates
                    }
                    
                    // SKIP les inputs dans le DayBuilderDrawer (duplicate data!)
                    if (drawerContainer && drawerContainer.contains(el)) {
                        return; // Le drawer ne doit PAS soumettre ses données
                    }
                    
                    // SKIP les inputs avec index -1 (templates clonés)
                    if (el.name && el.name.includes('[-1]')) {
                        return;
                    }
                    
                    if (el.hasAttribute('disabled')) {
                        el.removeAttribute('disabled');
                        console.log('  🔓 Disabled retiré:', el.name);
                        count++;
                    }
                });
                if (count > 0) {
                    console.log('✅ Total disabled retirés (drawer/templates exclus):', count);
                }
            }
            
            function interceptFormSubmission() {
                var form = document.getElementById('edit-voyage-form');
                if (!form) {
                    console.error('❌ Formulaire #edit-voyage-form introuvable!');
                    return;
                }
                
                form.addEventListener('submit', function(e) {
                    console.log('🚀 FORMULAIRE SOUMIS (intercepté)');
                    
                    // DÉSACTIVER le drawer pour éviter qu'il soumette ses duplications
                    var drawer = document.getElementById('day-builder-drawer');
                    var drawerInputsDisabled = [];
                    if (drawer) {
                        drawer.querySelectorAll('[name^="flight_options"]').forEach(function(el) {
                            if (!el.hasAttribute('disabled')) {
                                el.setAttribute('disabled', 'disabled');
                                el.setAttribute('data-was-enabled', '1');
                                drawerInputsDisabled.push(el);
                            }
                        });
                        if (drawerInputsDisabled.length > 0) {
                            console.warn('⚠️  Drawer inputs désactivés temporairement:', drawerInputsDisabled.length);
                        }
                    }
                    
                    var fd = new FormData(this);
                    var flightOptionsData = {};
                    var count = 0;
                    var templatesCount = 0;
                    
                    // Filtrer les templates (index -1) du FormData
                    var entriesToRemove = [];
                    for (var pair of fd.entries()) {
                        if (pair[0].startsWith('flight_options')) {
                            if (pair[0].includes('[-1]')) {
                                entriesToRemove.push(pair[0]);
                                templatesCount++;
                            } else {
                                flightOptionsData[pair[0]] = pair[1];
                                console.log('  📦', pair[0], '=', pair[1]);
                                count++;
                            }
                        }
                    }
                    
                    if (templatesCount > 0) {
                        console.warn('⚠️  Templates détectés (ignorés):', templatesCount, 'champs');
                    }
                    
                    console.log('📊 Total flight_options valides:', count);
                    
                    if (count === 0) {
                        console.error('❌ AUCUN flight_options détecté dans le FormData!');
                        console.log('Vérifications:');
                        console.log('  1. Les inputs ont-ils les bons attributs name?');
                        console.log('  2. Les inputs sont-ils dans le formulaire #edit-voyage-form?');
                        console.log('  3. Les inputs sont-ils disabled?');
                        
                        if (!confirm('⚠️ ATTENTION: Aucun flight_options détecté!\n\nVoulez-vous quand même envoyer le formulaire?\n(Cliquez sur Cancel pour déboguer)')) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            // Réactiver les inputs du drawer
                            drawerInputsDisabled.forEach(function(el) {
                                el.removeAttribute('disabled');
                                el.removeAttribute('data-was-enabled');
                            });
                        }
                    } else {
                        console.log('✅ Flight options detectés, soumission OK');
                    }
                    
                    // Note: Si soumission OK, la page va recharger donc pas besoin de réactiver
                }, true);
                
                console.log('✅ Intercepteur de formulaire installé');
            }
            
            // Exécuter au chargement
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    removeDisabledFromFlightOptions();
                    interceptFormSubmission();
                });
            } else {
                removeDisabledFromFlightOptions();
                interceptFormSubmission();
            }
            
            // Re-vérifier après 2 secondes (au cas où des inputs sont ajoutés dynamiquement)
            setTimeout(function() {
                console.log('🔄 Re-vérification après 2s...');
                removeDisabledFromFlightOptions();
            }, 2000);
        })();
    </script>
@endpush