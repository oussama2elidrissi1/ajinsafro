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

    <form action="{{ route('admin.circuits.voyages.update', $voyage->ID) }}" method="POST">
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
                    <i class="bx bx-info-circle"></i> Information
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
                <a class="nav-link" data-bs-toggle="tab" href="#payment" role="tab">
                    <i class="bx bx-credit-card"></i> Paiement
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#taxonomies" role="tab">
                    <i class="bx bx-category"></i> Catégories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#program" role="tab">
                    <i class="bx bx-list-ul"></i> Programme
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#program-days" role="tab">
                    <i class="bx bx-calendar-week"></i> Programme (Jours)
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
                                    <input type="number" class="form-control" id="duration_day" name="duration_day" value="{{ old('duration_day', $meta['duration_day'] ?? '') }}" min="1">
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

            {{-- TAB 2: LOCATION --}}
            <div class="tab-pane" id="location" role="tabpanel">
                {{-- Tour location (WordPress Traveler style) --}}
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-2" style="font-size: 18px; font-weight: 600; color: #23282d;">Tour location</h4>
                        <p class="text-muted mb-3" style="font-size: 13px;">Select one or more location for your tour</p>
                        
                        <div class="mb-3">
                            <input 
                                type="text" 
                                id="locationSearch" 
                                class="form-control" 
                                placeholder="Type to search"
                                style="font-size: 14px; padding: 6px 12px; border: 1px solid #ddd; border-radius: 3px;"
                            >
                        </div>
                        
                        <div class="wp-location-box" id="locationTreeContainer" style="border: 1px solid #ccd0d4; background: #fff; padding: 12px; max-height: 360px; overflow-y: auto; border-radius: 3px;">
                            @if(!empty($locationsTree))
                                @include('admin.circuits.voyages.partials.location-tree', [
                                    'locations' => $locationsTree, 
                                    'selectedIds' => $selectedLocationIds ?? []
                                ])
                            @else
                                <p class="text-muted mb-0" style="font-size: 13px; color: #646970;">Aucune location disponible. Créez des locations dans WordPress d'abord.</p>
                            @endif
                        </div>
                        
                        <small class="text-muted d-block mt-2" id="locationCount" style="font-size: 12px; color: #646970;">
                            <i class="bx bx-info-circle"></i> 
                            <span id="locationCountText">{{ count($selectedLocationIds ?? []) }} location(s) sélectionnée(s)</span>
                        </small>
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
                        
                        <div class="mb-3">
                            <label for="thumbnail_id" class="form-label">Image à la une (ID WP)</label>
                            <input type="number" class="form-control" id="thumbnail_id" name="thumbnail_id" value="{{ old('thumbnail_id', $meta['thumbnail_id'] ?? '') }}" placeholder="14434">
                            <small class="text-muted">ID de l'attachment dans la médiathèque WordPress</small>
                        </div>

                        <div class="mb-3">
                            <label for="gallery_ids" class="form-label">Galerie (IDs séparés par virgule)</label>
                            <input type="text" class="form-control" id="gallery_ids" name="gallery_ids" value="{{ old('gallery_ids', $gallery_csv ?? '') }}" placeholder="14435,14436,14437">
                            <small class="text-muted">IDs des images de la galerie WordPress (format: 123,456,789)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="video" class="form-label">URL Vidéo</label>
                            <input type="text" class="form-control" id="video" name="video" value="{{ old('video', $meta['video'] ?? '') }}" placeholder="https://www.youtube.com/watch?v=...">
                            <small class="text-muted">YouTube, Vimeo, etc. (Optionnel)</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 7: PAYMENT --}}
            <div class="tab-pane" id="payment" role="tabpanel">
                <div class="card">
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

            {{-- TAB 9: PROGRAMME --}}
            <div class="tab-pane" id="program" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Tour Program</h4>
                        <p class="text-muted">Définissez le programme jour par jour de votre tour (identique à WordPress Traveler)</p>
                        
                        {{-- Program Style --}}
                        <div class="mb-4">
                            <label for="tours_program_style" class="form-label">Program Style</label>
                            <select name="tours_program_style" id="tours_program_style" class="form-select">
                                <option value="style1" {{ ($tourProgram['style'] ?? 'style1') === 'style1' ? 'selected' : '' }}>Style 1</option>
                                <option value="style2" {{ ($tourProgram['style'] ?? '') === 'style2' ? 'selected' : '' }}>Style 2</option>
                                <option value="style3" {{ ($tourProgram['style'] ?? '') === 'style3' ? 'selected' : '' }}>Style 3</option>
                            </select>
                            <small class="text-muted">Choisissez le style d'affichage du programme sur le front-end</small>
                        </div>
                        
                        {{-- Program Items --}}
                        <div id="programItemsContainer">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Programme items</h5>
                                <button type="button" class="btn btn-success btn-sm" id="addProgramItem">
                                    <i class="bx bx-plus"></i> Add new
                                </button>
                            </div>
                            
                            <div id="programItemsList">
                                @if(!empty($tourProgram['items']))
                                    @foreach($tourProgram['items'] as $index => $item)
                                    <div class="program-item card mb-3" data-index="{{ $index }}">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0">Item {{ $index + 1 }}</h6>
                                                <button type="button" class="btn btn-danger btn-sm remove-program-item">
                                                    <i class="bx bx-trash"></i> Remove
                                                </button>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Title</label>
                                                <input type="text" 
                                                       name="tours_program[{{ $index }}][title]" 
                                                       class="form-control" 
                                                       value="{{ $item['title'] ?? '' }}"
                                                       placeholder="Ex: 08:00 - Départ de l'hôtel">
                                            </div>
                                            
                                            <div class="mb-0">
                                                <label class="form-label">Description</label>
                                                <textarea name="tours_program[{{ $index }}][desc]" 
                                                          class="form-control" 
                                                          rows="3"
                                                          placeholder="Description détaillée de cette étape du programme">{{ $item['desc'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                            
                            @if(empty($tourProgram['items']))
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle"></i> Aucun item de programme. Cliquez sur "Add new" pour ajouter un élément.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 10: PROGRAMME (JOURS) — Accordion Jours + Activités Laravel --}}
            <div class="tab-pane" id="program-days" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Programme par jours</h4>
                        <p class="text-muted">Chaque jour peut être en mode <strong>Libre</strong> ou <strong>Programme</strong>. @if(Route::has('admin.circuits.activities.index'))Ajoutez des activités depuis le <a href="{{ route('admin.circuits.activities.index') }}" target="_blank">catalogue d’activités</a>.@else Ajoutez des activités depuis le catalogue (module Activités). @endif</p>

                        <div class="accordion" id="accordionProgrammeDays">
                        @forelse($programDays as $dayIndex => $entry)
                            @php
                                $day = $entry['day'];
                                $activities = $entry['activities'];
                                $collapseId = 'collapse-day-' . $day->id;
                                $isFirst = ($dayIndex === 0);
                                $dayTitleDisplay = $day->day_title ?? $day->title ?? ('Jour ' . $day->day_number);
                            @endphp
                            <div class="accordion-item programme-day-card" data-day-id="{{ $day->id }}">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $isFirst ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="{{ $isFirst ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                                        JOUR {{ $day->day_number }} – {{ $dayTitleDisplay }}
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

                                    <h6 class="mt-3 mb-2">Activités incluses</h6>
                                    <div class="programme-activities-list mb-3" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}">
                                        @foreach($activities as $actIndex => $da)
                                            <div class="programme-activity-row card mb-2" data-day-activity-id="{{ $da->id }}">
                                                <div class="card-body py-2">
                                                    <div class="d-flex flex-wrap align-items-start gap-2">
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
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="form-label mb-0">Ajouter une activité:</label>
                                        <select class="form-select form-select-sm add-activity-select" style="max-width:280px" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}">
                                            <option value="">-- Choisir --</option>
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
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle"></i> Aucun jour de programme. Vérifiez la <strong>Durée (nombre de jours)</strong> dans l’onglet Basique / General, enregistrez le tour, puis revenez ici.
                            </div>
                        @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAVE BUTTON (Fixed bottom) --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-footer d-flex justify-content-between align-items-center bg-light">
                        <div>
                            <button type="submit" class="btn btn-primary btn-lg waves-effect waves-light">
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
        // Location search filter (WordPress Traveler behavior)
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('locationSearch');
            const locationItems = document.querySelectorAll('.wp-location-item');
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    
                    if (searchTerm === '') {
                        // Show all
                        locationItems.forEach(function(item) {
                            item.style.display = '';
                        });
                    } else {
                        // Filter: show item if title matches OR if any descendant matches
                        locationItems.forEach(function(item) {
                            const title = item.getAttribute('data-title');
                            
                            // Check if this item or any child matches
                            const selfMatches = title.includes(searchTerm);
                            const childMatches = Array.from(item.querySelectorAll('.wp-location-item')).some(function(child) {
                                return child.getAttribute('data-title').includes(searchTerm);
                            });
                            
                            if (selfMatches || childMatches) {
                                item.style.display = '';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    }
                });
            }
            
            // Update selected count
            const checkboxes = document.querySelectorAll('.location-checkbox');
            const updateCount = function() {
                const checked = document.querySelectorAll('.location-checkbox:checked').length;
                const countText = document.getElementById('locationCountText');
                if (countText) {
                    countText.textContent = checked + ' location(s) sélectionnée(s)';
                }
            };
            
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateCount);
            });
        });
        
        // Tour Program: Add/Remove items
        document.addEventListener('DOMContentLoaded', function() {
            let programItemIndex = {{ count($tourProgram['items'] ?? []) }};
            
            // Add new program item
            document.getElementById('addProgramItem').addEventListener('click', function() {
                const container = document.getElementById('programItemsList');
                const newItem = document.createElement('div');
                newItem.className = 'program-item card mb-3';
                newItem.setAttribute('data-index', programItemIndex);
                newItem.innerHTML = `
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">Item ${programItemIndex + 1}</h6>
                            <button type="button" class="btn btn-danger btn-sm remove-program-item">
                                <i class="bx bx-trash"></i> Remove
                            </button>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" 
                                   name="tours_program[${programItemIndex}][title]" 
                                   class="form-control" 
                                   placeholder="Ex: 08:00 - Départ de l'hôtel">
                        </div>
                        
                        <div class="mb-0">
                            <label class="form-label">Description</label>
                            <textarea name="tours_program[${programItemIndex}][desc]" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Description détaillée de cette étape du programme"></textarea>
                        </div>
                    </div>
                `;
                container.appendChild(newItem);
                programItemIndex++;
                
                // Hide empty message if exists
                const emptyAlert = container.parentElement.querySelector('.alert-info');
                if (emptyAlert) {
                    emptyAlert.style.display = 'none';
                }
            });
            
            // Remove program item (delegated event)
            document.getElementById('programItemsList').addEventListener('click', function(e) {
                if (e.target.closest('.remove-program-item')) {
                    const item = e.target.closest('.program-item');
                    if (confirm('Supprimer cet item du programme ?')) {
                        item.remove();
                        
                        // Update item numbers
                        const items = document.querySelectorAll('.program-item');
                        items.forEach(function(item, index) {
                            const title = item.querySelector('h6');
                            if (title) {
                                title.textContent = 'Item ' + (index + 1);
                            }
                        });
                        
                        // Show empty message if no items left
                        if (items.length === 0) {
                            const emptyAlert = document.querySelector('#programItemsContainer .alert-info');
                            if (emptyAlert) {
                                emptyAlert.style.display = 'block';
                            }
                        }
                    }
                }
            });
        });

        // Programme (Jours): Add activity to day — names: programme_days[i][activities][k][...]; accordion-body = card content
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.add-activity-to-day').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var dayIndex = this.getAttribute('data-day-index');
                    var dayId = this.getAttribute('data-day-id');
                    var card = this.closest('.programme-day-card') || this.closest('.accordion-item');
                    var select = card ? card.querySelector('.add-activity-select') : null;
                    var activityId = select && select.value;
                    var activityTitle = select && select.options[select.selectedIndex] && select.options[select.selectedIndex].text;
                    if (!activityId || dayIndex === null) {
                        return;
                    }
                    var list = card ? card.querySelector('.programme-activities-list') : null;
                    if (!list) return;
                    var k = list.querySelectorAll('.programme-activity-row').length;
                    var prefix = 'programme_days[' + dayIndex + '][activities][' + k + ']';
                    var row = document.createElement('div');
                    row.className = 'programme-activity-row card mb-2';
                    row.setAttribute('data-day-activity-id', '0');
                    row.innerHTML = '<div class="card-body py-2">' +
                        '<div class="d-flex flex-wrap align-items-start gap-2">' +
                        '<input type="hidden" name="' + prefix + '[day_activity_id]" value="">' +
                        '<input type="hidden" name="' + prefix + '[activity_id]" value="' + activityId + '">' +
                        '<input type="hidden" name="' + prefix + '[sort_order]" value="' + k + '">' +
                        '<span class="fw-medium">' + (activityTitle || 'Activité') + '</span>' +
                        '<span class="form-check form-check-inline mb-0"><input type="hidden" name="' + prefix + '[is_included]" value="0"><input class="form-check-input" type="checkbox" name="' + prefix + '[is_included]" value="1" checked><label class="form-check-label small">Inclus</label></span>' +
                        '<span class="form-check form-check-inline mb-0"><input type="hidden" name="' + prefix + '[is_mandatory]" value="0"><input class="form-check-input" type="checkbox" name="' + prefix + '[is_mandatory]" value="1"><label class="form-check-label small">Obligatoire</label></span>' +
                        '<input type="text" class="form-control form-control-sm d-inline-block" style="max-width:200px" name="' + prefix + '[custom_title]" placeholder="Titre personnalisé">' +
                        '<textarea class="form-control form-control-sm" name="' + prefix + '[custom_description]" rows="1" placeholder="Description personnalisée"></textarea>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger remove-programme-activity"><i class="bx bx-trash"></i></button>' +
                        '</div></div>';
                    list.appendChild(row);
                    select.value = '';
                });
            });

            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-programme-activity')) {
                    var row = e.target.closest('.programme-activity-row');
                    if (row && confirm('Retirer cette activité du jour ?')) {
                        row.remove();
                    }
                }
            });
        });
    </script>
@endpush
