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

            {{-- TAB VOLS — Vol Aller = toujours Jour 1, Vol Retour = toujours Dernier jour (N) — Laravel voyage_flights --}}
            @php
                $fOutbound = $outboundFlight ?? null;
                $fInbound = $inboundFlight ?? null;
                $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                $flightDash = '—';
                $fmtDate = function($d) { return $d ? (\Carbon\Carbon::parse($d)->format('D, d M')) : null; };
            @endphp
            <div class="tab-pane" id="flights" role="tabpanel">
                <style>
                .flight-card-admin { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e9ecef; overflow: hidden; }
                .flight-card-admin .flight-card-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8f9fa; border-bottom: 1px solid #e9ecef; }
                .flight-card-admin .flight-card-title { font-size: 13px; font-weight: 600; color: #495057; }
                .flight-card-admin .flight-remove-btn { background: none; border: none; color: #dc3545; font-size: 12px; font-weight: 600; cursor: pointer; padding: 0 4px; }
                .flight-card-admin .flight-remove-btn:hover { text-decoration: underline; }
                .flight-card-admin .flight-card-body { display: flex; align-items: stretch; padding: 16px; gap: 16px; }
                .flight-card-admin .flight-card-col { display: flex; flex-direction: column; justify-content: center; }
                .flight-card-admin .flight-icon-circle { width: 48px; height: 48px; border-radius: 50%; background: #e7f1ff; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-size: 20px; }
                .flight-card-admin .flight-card-center { flex: 1; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
                .flight-card-admin .flight-dep, .flight-card-admin .flight-arr { text-align: center; }
                .flight-card-admin .flight-date { font-size: 12px; color: #6c757d; margin-bottom: 2px; }
                .flight-card-admin .flight-place { font-size: 14px; font-weight: 500; color: #212529; }
                .flight-card-admin .flight-arrow { color: #adb5bd; font-size: 18px; }
                .flight-card-admin .flight-card-baggage { font-size: 12px; color: #6c757d; }
                .flight-card-admin .flight-card-baggage div { margin-bottom: 4px; }
                .flight-card-admin .flight-card-badge-wrap { padding: 0 16px 12px; }
                .flight-card-admin .flight-badge-tentative { display: inline-block; padding: 4px 10px; border-radius: 20px; background: #f5e6d3; color: #856404; font-size: 11px; font-weight: 600; }
                .flight-block { margin-bottom: 20px; }
                .flight-block .flight-card-view { display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
                .flight-block .flight-card-view .flight-edit-btn { margin-top: 8px; }
                .flight-block .flight-card-edit { padding: 16px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; }
                </style>
                <p class="alert alert-info py-2 mb-3 small"><i class="bx bx-info-circle"></i> <strong>Vols Aller / Retour / Segments</strong> (plusieurs options possibles). Les hôtels et transferts sont dans leurs propres onglets.</p>
                @if(Route::has('admin.circuits.airlines.index'))
                <div class="mb-3">
                    <a href="{{ route('admin.circuits.airlines.index') }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bx bx-list-ul me-1"></i> Gérer les compagnies aériennes</a>
                    @if(($airlines ?? collect())->isEmpty())
                    <span class="text-muted ms-2">— Aucune compagnie. <a href="{{ route('admin.circuits.airlines.create') }}">Créer une compagnie</a></span>
                    @endif
                </div>
                @endif
                @php $lastDayNumber = $lastDayNumber ?? (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : 1); @endphp
                @include('admin.circuits.voyages.partials._flight_options_sections', ['flightOptionsWithIndex' => $flightOptionsWithIndex ?? [], 'nextFlightOptionIndex' => $nextFlightOptionIndex ?? 0, 'lastDayNumber' => $lastDayNumber, 'airlines' => $airlines ?? collect()])
                <p class="text-muted small mt-2">Enregistrez le voyage pour sauvegarder les vols.</p>
            </div>

            {{-- TAB HÔTELS — Hôtels par jour (multi-lignes) --}}
            <div class="tab-pane" id="hotels" role="tabpanel">
                @php
                    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                @endphp
                <p class="alert alert-info py-2 mb-3 small"><i class="bx bx-info-circle"></i> <strong>Hôtels</strong> — Vous pouvez ajouter plusieurs hôtels et les associer à un jour spécifique du circuit.</p>
                <h5 class="mb-3"><i class="bx bx-hotel"></i> Hôtel(s) (séjour — check-in J1, check-out J{{ $lastDayNumber }})</h5>
                <div id="tour-hotels-container">
                    @php $hotelsList = $tourHotels->isEmpty() ? [null] : $tourHotels->all(); @endphp
                    @foreach($hotelsList as $hi => $h)
                    @php $hid = 'tour_hotel_image_id_' . $hi; $himg = optional($h)->image_id; $himgUrl = $himg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int)$himg) : ''; @endphp
                    <div class="card mb-3 tour-hotel-row" data-index="{{ $hi }}">
                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                            <strong>Hôtel {{ $hi + 1 }}</strong>
                            @if($hi > 0)<button type="button" class="btn btn-sm btn-outline-danger tour-remove-row" data-target=".tour-hotel-row" aria-label="Supprimer">×</button>@endif
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Jour</label>
                                    <select class="form-select" name="tour_hotels[{{ $hi }}][day_number]">
                                        @for($d = 1; $d <= $lastDayNumber; $d++)
                                            <option value="{{ $d }}" {{ old("tour_hotels.{$hi}.day_number", optional($h)->day_number ?? 1) == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end pb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="tour_hotels[{{ $hi }}][is_optional]" value="1" {{ old("tour_hotels.{$hi}.is_optional", optional($h)->is_optional ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label">Option client</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nom de l'hôtel</label>
                                    <input type="text" class="form-control" name="tour_hotels[{{ $hi }}][hotel_name]" value="{{ old("tour_hotels.{$hi}.hotel_name", optional($h)->hotel_name ?? '') }}" placeholder="Ex. Hôtel Les Almoravides">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Étoiles (0–5)</label>
                                    <input type="number" class="form-control" name="tour_hotels[{{ $hi }}][stars]" value="{{ old("tour_hotels.{$hi}.stars", optional($h)->stars ?? '') }}" min="0" max="5" placeholder="3">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type de chambre</label>
                                    <input type="text" class="form-control" name="tour_hotels[{{ $hi }}][room_type]" value="{{ old("tour_hotels.{$hi}.room_type", optional($h)->room_type ?? '') }}" placeholder="Ex. Chambre double">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" class="form-control" name="tour_hotels[{{ $hi }}][address]" value="{{ old("tour_hotels.{$hi}.address", optional($h)->address ?? '') }}" placeholder="Ville, pays">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Repas (formule)</label>
                                    <input type="text" class="form-control" name="tour_hotels[{{ $hi }}][meal_plan]" value="{{ old("tour_hotels.{$hi}.meal_plan", optional($h)->meal_plan ?? '') }}" placeholder="Ex. Petit-déjeuner inclus">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="tour_hotels[{{ $hi }}][notes]" rows="2">{{ old("tour_hotels.{$hi}.notes", optional($h)->notes ?? '') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Image</label>
                                    <input type="hidden" name="tour_hotels[{{ $hi }}][image_id]" id="{{ $hid }}" value="{{ old("tour_hotels.{$hi}.image_id", optional($h)->image_id ?? '') }}">
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div id="{{ $hid }}_preview_wrap" class="border rounded overflow-hidden bg-light" style="width: 120px; height: 80px; display: {{ $himgUrl ? 'flex' : 'none' }};">
                                            <img id="{{ $hid }}_preview" src="{{ $himgUrl }}" alt="" class="img-fluid" style="max-width:100%; max-height:100%; object-fit: cover;">
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary ajtb-logistique-media-btn" data-target="tour_hotel" data-input="{{ $hid }}" data-preview="{{ $hid }}_preview" data-preview-wrap="{{ $hid }}_preview_wrap"><i class="bx bx-images"></i> Choisir</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger ajtb-logistique-media-remove" data-input="{{ $hid }}" data-preview="{{ $hid }}_preview" data-preview-wrap="{{ $hid }}_preview_wrap">×</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-soft-primary mb-4" id="tour-add-hotel"><i class="bx bx-plus"></i> Ajouter un hôtel</button>
                <script>
                (function(){
                    var container = document.getElementById('tour-hotels-container');
                    var addBtn = document.getElementById('tour-add-hotel');
                    if (!container || !addBtn) return;
                    
                    // Éviter les duplications d'event listeners - check unique pour tout le script
                    if (container.dataset.initialized === 'true') return;
                    container.dataset.initialized = 'true';
                    
                    addBtn.addEventListener('click', function(){
                        var rows = container.querySelectorAll('.tour-hotel-row');
                        var last = rows[rows.length - 1];
                        if (!last) return;
                        var prevIndex = parseInt(last.getAttribute('data-index'), 10);
                        var nextIndex = prevIndex + 1;
                        var clone = last.cloneNode(true);
                        clone.setAttribute('data-index', nextIndex);
                        clone.querySelector('.card-header strong').textContent = 'Hôtel ' + (nextIndex + 1);
                        clone.querySelectorAll('[name]').forEach(function(inp){
                            if (inp.name && inp.name.indexOf('tour_hotels[') === 0)
                                inp.name = inp.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + nextIndex + ']');
                            if (inp.name && inp.name.indexOf('[day_number]') !== -1) { inp.value = '1'; return; }
                            if (inp.name && inp.name.indexOf('[is_optional]') !== -1) { inp.checked = false; return; }
                            if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
                            if (inp.tagName === 'TEXTAREA') inp.value = '';
                        });
                        clone.querySelectorAll('[id]').forEach(function(el){
                            if (el.id && el.id.indexOf('tour_hotel_image_id_') === 0)
                                el.id = el.id.replace(/tour_hotel_image_id_\d+/, 'tour_hotel_image_id_' + nextIndex);
                        });
                        clone.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                            if (btn.getAttribute('data-input')) btn.setAttribute('data-input', 'tour_hotel_image_id_' + nextIndex);
                            if (btn.getAttribute('data-preview')) btn.setAttribute('data-preview', 'tour_hotel_image_id_' + nextIndex + '_preview');
                            if (btn.getAttribute('data-preview-wrap')) btn.setAttribute('data-preview-wrap', 'tour_hotel_image_id_' + nextIndex + '_preview_wrap');
                        });
                        var wrap = clone.querySelector('[id$="_preview_wrap"]');
                        if (wrap) wrap.style.display = 'none';
                        if (!clone.querySelector('.tour-remove-row')) {
                            var header = clone.querySelector('.card-header');
                            var rm = document.createElement('button');
                            rm.type = 'button';
                            rm.className = 'btn btn-sm btn-outline-danger tour-remove-row';
                            rm.setAttribute('aria-label', 'Supprimer');
                            rm.textContent = '×';
                            header.appendChild(rm);
                        }
                        container.appendChild(clone);
                    });
                    
                    container.addEventListener('click', function(e){
                        if (e.target.classList.contains('tour-remove-row')) {
                            var row = e.target.closest('.tour-hotel-row');
                            if (row && container.querySelectorAll('.tour-hotel-row').length > 1) {
                                row.remove();
                                container.querySelectorAll('.tour-hotel-row').forEach(function(r, i){
                                    r.setAttribute('data-index', i);
                                    r.querySelector('.card-header strong').textContent = 'Hôtel ' + (i + 1);
                                    r.querySelectorAll('[name^="tour_hotels["]').forEach(function(inp){ inp.name = inp.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + i + ']'); });
                                    r.querySelectorAll('[id^="tour_hotel_image_id_"]').forEach(function(el){ el.id = el.id.replace(/tour_hotel_image_id_\d+/, 'tour_hotel_image_id_' + i); });
                                    r.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                                        if (btn.getAttribute('data-input')) btn.setAttribute('data-input', 'tour_hotel_image_id_' + i);
                                        if (btn.getAttribute('data-preview')) btn.setAttribute('data-preview', 'tour_hotel_image_id_' + i + '_preview');
                                        if (btn.getAttribute('data-preview-wrap')) btn.setAttribute('data-preview-wrap', 'tour_hotel_image_id_' + i + '_preview_wrap');
                                    });
                                });
                            }
                        }
                    });
                })();
                </script>
                <p class="text-muted small mt-3">Les images s'affichent sur la fiche circuit (site WordPress).</p>
            </div>

            {{-- TAB TRANSFERTS — Transferts par jour (multi-lignes) --}}
            <div class="tab-pane" id="transfers" role="tabpanel">
                @php
                    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                @endphp
                <p class="alert alert-info py-2 mb-3 small"><i class="bx bx-info-circle"></i> <strong>Transferts</strong> — Vous pouvez ajouter plusieurs transferts (arrivée et départ) et les associer à un jour spécifique du circuit.</p>
                <p class="text-muted small mb-2">Vous pouvez ajouter une <strong>image</strong> pour chaque transfert ; elles s'affichent sur la fiche circuit (site WordPress).</p>
                <h5 class="mb-3"><i class="bx bx-car"></i> Transferts (Aéroport ↔ Hôtel — plusieurs par jour possible)</h5>

                <div class="mb-3">
                    <strong>Transferts arrivée</strong> (Aéroport → Hôtel)
                    <div id="tour-transfer-arrivals-container" class="mt-2">
                        @php $arrivalsList = $transferArrivals->isEmpty() ? [null] : $transferArrivals->values()->all(); @endphp
                        @foreach($arrivalsList as $ai => $arr)
                        @php
                            $arrImgId = 'tour_transfer_arrival_image_id_' . $ai;
                            $arrImg = optional($arr)->image_id;
                            $arrImgUrl = $arrImg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int)$arrImg) : '';
                        @endphp
                        <div class="card mb-2 tour-transfer-arrival-row" data-index="{{ $ai }}">
                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                <strong>Transfert arrivée {{ $ai + 1 }}</strong>
                                @if($ai > 0)<button type="button" class="btn btn-sm btn-outline-danger tour-remove-transfer-arrival" aria-label="Supprimer">×</button>@endif
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <label class="form-label small">Jour</label>
                                        <select class="form-select form-select-sm" name="tour_transfer_arrivals[{{ $ai }}][day_number]">
                                            @for($d = 1; $d <= $lastDayNumber; $d++)
                                                <option value="{{ $d }}" {{ old("tour_transfer_arrivals.{$ai}.day_number", optional($arr)->day_number ?? 1) == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input" name="tour_transfer_arrivals[{{ $ai }}][is_optional]" value="1" {{ old("tour_transfer_arrivals.{$ai}.is_optional", optional($arr)->is_optional ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label small">Option client</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">De (ex. aéroport)</label>
                                        <input type="text" class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][from_label]" value="{{ old("tour_transfer_arrivals.{$ai}.from_label", optional($arr)->from_label ?? $suggestedArrivalFrom ?? '') }}" placeholder="{{ $suggestedArrivalFrom ?: 'Aéroport arrivée' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">À (ex. hôtel)</label>
                                        <input type="text" class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][to_label]" value="{{ old("tour_transfer_arrivals.{$ai}.to_label", optional($arr)->to_label ?? $suggestedArrivalTo ?? '') }}" placeholder="{{ $suggestedArrivalTo ?: 'Hôtel' }}">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small">Prise en charge</label>
                                        <input type="text" class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][pickup_time]" value="{{ old("tour_transfer_arrivals.{$ai}.pickup_time", optional($arr)->pickup_time ?? '') }}" placeholder="14:00">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small">Arrivée</label>
                                        <input type="text" class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][dropoff_time]" value="{{ old("tour_transfer_arrivals.{$ai}.dropoff_time", optional($arr)->dropoff_time ?? '') }}" placeholder="15:00">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Véhicule</label>
                                        <input type="text" class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][vehicle_type]" value="{{ old("tour_transfer_arrivals.{$ai}.vehicle_type", optional($arr)->vehicle_type ?? '') }}" placeholder="Minivan">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">Notes</label>
                                        <textarea class="form-control form-control-sm" name="tour_transfer_arrivals[{{ $ai }}][notes]" rows="1">{{ old("tour_transfer_arrivals.{$ai}.notes", optional($arr)->notes ?? '') }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">Image</label>
                                        <input type="hidden" name="tour_transfer_arrivals[{{ $ai }}][image_id]" id="{{ $arrImgId }}" value="{{ old("tour_transfer_arrivals.{$ai}.image_id", optional($arr)->image_id ?? '') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <div id="{{ $arrImgId }}_preview_wrap" class="border rounded overflow-hidden bg-light" style="width: 80px; height: 56px; display: {{ $arrImgUrl ? 'flex' : 'none' }};">
                                                <img id="{{ $arrImgId }}_preview" src="{{ $arrImgUrl }}" alt="" style="max-width:100%; max-height:100%; object-fit: cover;">
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary ajtb-logistique-media-btn" data-target="transfer_arrival" data-input="{{ $arrImgId }}" data-preview="{{ $arrImgId }}_preview" data-preview-wrap="{{ $arrImgId }}_preview_wrap"><i class="bx bx-image"></i> Choisir</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger ajtb-logistique-media-remove" data-input="{{ $arrImgId }}" data-preview="{{ $arrImgId }}_preview" data-preview-wrap="{{ $arrImgId }}_preview_wrap">×</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-soft-primary mb-3" id="tour-add-transfer-arrival"><i class="bx bx-plus"></i> Ajouter un transfert arrivée</button>
                </div>

                <div class="mb-3">
                    <strong>Transferts départ</strong> (Hôtel → Aéroport)
                    <div id="tour-transfer-departures-container" class="mt-2">
                        @php $departuresList = $transferDepartures->isEmpty() ? [null] : $transferDepartures->values()->all(); @endphp
                        @foreach($departuresList as $di => $dep)
                        @php
                            $depImgId = 'tour_transfer_departure_image_id_' . $di;
                            $depImg = optional($dep)->image_id;
                            $depImgUrl = $depImg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int)$depImg) : '';
                        @endphp
                        <div class="card mb-2 tour-transfer-departure-row" data-index="{{ $di }}">
                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                <strong>Transfert départ {{ $di + 1 }}</strong>
                                @if($di > 0)<button type="button" class="btn btn-sm btn-outline-danger tour-remove-transfer-departure" aria-label="Supprimer">×</button>@endif
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <label class="form-label small">Jour</label>
                                        <select class="form-select form-select-sm" name="tour_transfer_departures[{{ $di }}][day_number]">
                                            @for($d = 1; $d <= $lastDayNumber; $d++)
                                                <option value="{{ $d }}" {{ old("tour_transfer_departures.{$di}.day_number", optional($dep)->day_number ?? $lastDayNumber) == $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input" name="tour_transfer_departures[{{ $di }}][is_optional]" value="1" {{ old("tour_transfer_departures.{$di}.is_optional", optional($dep)->is_optional ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label small">Option client</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">De (ex. hôtel)</label>
                                        <input type="text" class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][from_label]" value="{{ old("tour_transfer_departures.{$di}.from_label", optional($dep)->from_label ?? $suggestedDepartureFrom ?? '') }}" placeholder="{{ $suggestedDepartureFrom ?: 'Hôtel' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">À (ex. aéroport)</label>
                                        <input type="text" class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][to_label]" value="{{ old("tour_transfer_departures.{$di}.to_label", optional($dep)->to_label ?? $suggestedDepartureTo ?? '') }}" placeholder="{{ $suggestedDepartureTo ?: 'Aéroport départ' }}">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small">Prise en charge</label>
                                        <input type="text" class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][pickup_time]" value="{{ old("tour_transfer_departures.{$di}.pickup_time", optional($dep)->pickup_time ?? '') }}" placeholder="10:00">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label small">Arrivée</label>
                                        <input type="text" class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][dropoff_time]" value="{{ old("tour_transfer_departures.{$di}.dropoff_time", optional($dep)->dropoff_time ?? '') }}" placeholder="11:00">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Véhicule</label>
                                        <input type="text" class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][vehicle_type]" value="{{ old("tour_transfer_departures.{$di}.vehicle_type", optional($dep)->vehicle_type ?? '') }}" placeholder="Minivan">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">Notes</label>
                                        <textarea class="form-control form-control-sm" name="tour_transfer_departures[{{ $di }}][notes]" rows="1">{{ old("tour_transfer_departures.{$di}.notes", optional($dep)->notes ?? '') }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">Image</label>
                                        <input type="hidden" name="tour_transfer_departures[{{ $di }}][image_id]" id="{{ $depImgId }}" value="{{ old("tour_transfer_departures.{$di}.image_id", optional($dep)->image_id ?? '') }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <div id="{{ $depImgId }}_preview_wrap" class="border rounded overflow-hidden bg-light" style="width: 80px; height: 56px; display: {{ $depImgUrl ? 'flex' : 'none' }};">
                                                <img id="{{ $depImgId }}_preview" src="{{ $depImgUrl }}" alt="" style="max-width:100%; max-height:100%; object-fit: cover;">
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary ajtb-logistique-media-btn" data-target="transfer_departure" data-input="{{ $depImgId }}" data-preview="{{ $depImgId }}_preview" data-preview-wrap="{{ $depImgId }}_preview_wrap"><i class="bx bx-image"></i> Choisir</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger ajtb-logistique-media-remove" data-input="{{ $depImgId }}" data-preview="{{ $depImgId }}_preview" data-preview-wrap="{{ $depImgId }}_preview_wrap">×</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-soft-primary mb-3" id="tour-add-transfer-departure"><i class="bx bx-plus"></i> Ajouter un transfert départ</button>
                </div>

                <script>
                (function(){
                    var lastDayNum = {{ (int) $lastDayNumber }};
                    var arrContainer = document.getElementById('tour-transfer-arrivals-container');
                    var addArrBtn = document.getElementById('tour-add-transfer-arrival');
                    if (!arrContainer || !addArrBtn) return;
                    
                    // Éviter les duplications d'event listeners - check unique pour tout le script
                    if (arrContainer.dataset.initialized === 'true') return;
                    arrContainer.dataset.initialized = 'true';
                    
                    addArrBtn.addEventListener('click', function(){
                            var rows = arrContainer.querySelectorAll('.tour-transfer-arrival-row');
                            var last = rows[rows.length - 1];
                            if (!last) return;
                            var nextIdx = parseInt(last.getAttribute('data-index'), 10) + 1;
                            var clone = last.cloneNode(true);
                            clone.setAttribute('data-index', nextIdx);
                            clone.querySelector('.card-header strong').textContent = 'Transfert arrivée ' + (nextIdx + 1);
                            if (!clone.querySelector('.tour-remove-transfer-arrival')) {
                                var btn = document.createElement('button');
                                btn.type = 'button'; btn.className = 'btn btn-sm btn-outline-danger tour-remove-transfer-arrival'; btn.setAttribute('aria-label', 'Supprimer'); btn.textContent = '×';
                                clone.querySelector('.card-header').appendChild(btn);
                            }
                            clone.querySelectorAll('[name^="tour_transfer_arrivals["]').forEach(function(inp){
                                inp.name = inp.name.replace(/tour_transfer_arrivals\[\d+\]/, 'tour_transfer_arrivals[' + nextIdx + ']');
                                if (inp.name.indexOf('[day_number]') !== -1) inp.value = '1';
                                if (inp.name.indexOf('[is_optional]') !== -1) inp.checked = false;
                                if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
                                if (inp.tagName === 'TEXTAREA') inp.value = '';
                            });
                            clone.querySelectorAll('[id^="tour_transfer_arrival_image_id_"]').forEach(function(el){
                                var newId = el.id.replace(/tour_transfer_arrival_image_id_\d+/, 'tour_transfer_arrival_image_id_' + nextIdx);
                                el.id = newId;
                                if (el.id.indexOf('_preview_wrap') !== -1) el.style.display = 'none';
                            });
                            clone.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                                var inp = btn.getAttribute('data-input');
                                if (inp && inp.indexOf('tour_transfer_arrival_image_id_') === 0) {
                                    btn.setAttribute('data-input', 'tour_transfer_arrival_image_id_' + nextIdx);
                                    btn.setAttribute('data-preview', 'tour_transfer_arrival_image_id_' + nextIdx + '_preview');
                                    btn.setAttribute('data-preview-wrap', 'tour_transfer_arrival_image_id_' + nextIdx + '_preview_wrap');
                                }
                            });
                            arrContainer.appendChild(clone);
                        });
                        
                        arrContainer.addEventListener('click', function(e){
                            if (e.target.classList.contains('tour-remove-transfer-arrival')) {
                                var row = e.target.closest('.tour-transfer-arrival-row');
                                if (row && arrContainer.querySelectorAll('.tour-transfer-arrival-row').length > 1) {
                                    row.remove();
                                    arrContainer.querySelectorAll('.tour-transfer-arrival-row').forEach(function(r, i){
                                        r.setAttribute('data-index', i);
                                        r.querySelector('.card-header strong').textContent = 'Transfert arrivée ' + (i + 1);
                                        r.querySelectorAll('[name^="tour_transfer_arrivals["]').forEach(function(inp){ inp.name = inp.name.replace(/tour_transfer_arrivals\[\d+\]/, 'tour_transfer_arrivals[' + i + ']'); });
                                        r.querySelectorAll('[id^="tour_transfer_arrival_image_id_"]').forEach(function(el){ el.id = el.id.replace(/tour_transfer_arrival_image_id_\d+/, 'tour_transfer_arrival_image_id_' + i); });
                                        r.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                                            var inp = btn.getAttribute('data-input');
                                            if (inp && inp.indexOf('tour_transfer_arrival_image_id_') === 0) {
                                                btn.setAttribute('data-input', 'tour_transfer_arrival_image_id_' + i);
                                                btn.setAttribute('data-preview', 'tour_transfer_arrival_image_id_' + i + '_preview');
                                                btn.setAttribute('data-preview-wrap', 'tour_transfer_arrival_image_id_' + i + '_preview_wrap');
                                            }
                                        });
                                    });
                                }
                            }
                        });
                    }
                    var depContainer = document.getElementById('tour-transfer-departures-container');
                    var addDepBtn = document.getElementById('tour-add-transfer-departure');
                    if (!depContainer || !addDepBtn) return;
                    
                    // Éviter les duplications d'event listeners - check unique pour tout le script
                    if (depContainer.dataset.initialized === 'true') return;
                    depContainer.dataset.initialized = 'true';
                    
                    addDepBtn.addEventListener('click', function(){
                        var rows = depContainer.querySelectorAll('.tour-transfer-departure-row');
                        var last = rows[rows.length - 1];
                        if (!last) return;
                        var nextIdx = parseInt(last.getAttribute('data-index'), 10) + 1;
                        var clone = last.cloneNode(true);
                        clone.setAttribute('data-index', nextIdx);
                        clone.querySelector('.card-header strong').textContent = 'Transfert départ ' + (nextIdx + 1);
                        if (!clone.querySelector('.tour-remove-transfer-departure')) {
                            var btn = document.createElement('button');
                            btn.type = 'button'; btn.className = 'btn btn-sm btn-outline-danger tour-remove-transfer-departure'; btn.setAttribute('aria-label', 'Supprimer'); btn.textContent = '×';
                            clone.querySelector('.card-header').appendChild(btn);
                        }
                        clone.querySelectorAll('[name^="tour_transfer_departures["]').forEach(function(inp){
                            inp.name = inp.name.replace(/tour_transfer_departures\[\d+\]/, 'tour_transfer_departures[' + nextIdx + ']');
                            if (inp.name.indexOf('[day_number]') !== -1) inp.value = String(lastDayNum);
                            if (inp.name.indexOf('[is_optional]') !== -1) inp.checked = false;
                            if (inp.type !== 'hidden' && inp.tagName !== 'TEXTAREA') inp.value = '';
                            if (inp.tagName === 'TEXTAREA') inp.value = '';
                        });
                        clone.querySelectorAll('[id^="tour_transfer_departure_image_id_"]').forEach(function(el){
                            el.id = el.id.replace(/tour_transfer_departure_image_id_\d+/, 'tour_transfer_departure_image_id_' + nextIdx);
                            if (el.id.indexOf('_preview_wrap') !== -1) el.style.display = 'none';
                        });
                        clone.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                            var inp = btn.getAttribute('data-input');
                            if (inp && inp.indexOf('tour_transfer_departure_image_id_') === 0) {
                                btn.setAttribute('data-input', 'tour_transfer_departure_image_id_' + nextIdx);
                                btn.setAttribute('data-preview', 'tour_transfer_departure_image_id_' + nextIdx + '_preview');
                                btn.setAttribute('data-preview-wrap', 'tour_transfer_departure_image_id_' + nextIdx + '_preview_wrap');
                            }
                        });
                        depContainer.appendChild(clone);
                    });
                    
                    depContainer.addEventListener('click', function(e){
                        if (e.target.classList.contains('tour-remove-transfer-departure')) {
                            var row = e.target.closest('.tour-transfer-departure-row');
                            if (row && depContainer.querySelectorAll('.tour-transfer-departure-row').length > 1) {
                                row.remove();
                                depContainer.querySelectorAll('.tour-transfer-departure-row').forEach(function(r, i){
                                    r.setAttribute('data-index', i);
                                    r.querySelector('.card-header strong').textContent = 'Transfert départ ' + (i + 1);
                                    r.querySelectorAll('[name^="tour_transfer_departures["]').forEach(function(inp){ inp.name = inp.name.replace(/tour_transfer_departures\[\d+\]/, 'tour_transfer_departures[' + i + ']'); });
                                    r.querySelectorAll('[id^="tour_transfer_departure_image_id_"]').forEach(function(el){ el.id = el.id.replace(/tour_transfer_departure_image_id_\d+/, 'tour_transfer_departure_image_id_' + i); });
                                    r.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function(btn){
                                        var inp = btn.getAttribute('data-input');
                                        if (inp && inp.indexOf('tour_transfer_departure_image_id_') === 0) {
                                            btn.setAttribute('data-input', 'tour_transfer_departure_image_id_' + i);
                                            btn.setAttribute('data-preview', 'tour_transfer_departure_image_id_' + i + '_preview');
                                            btn.setAttribute('data-preview-wrap', 'tour_transfer_departure_image_id_' + i + '_preview_wrap');
                                        }
                                    });
                                });
                            }
                        }
                    });
                }
                })();
                </script>

                <p class="text-muted small">Les champs « De / À » sont préremplis avec l'aéroport du vol et l'hôtel. Vous pouvez ajouter plusieurs transferts (arrivée et départ) et les associer à un jour.</p>
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

            {{-- Legacy single outbound/inbound blocks kept below for fallback; remove when fully migrated
                {{-- Vol Aller (Jour 1) --}}
                <div class="flight-block d-none" data-flight-type="outbound" style="display:none !important;">
                    <div class="flight-card-view" id="flight-outbound-card-view">
                        <div class="flight-card-admin" style="min-width: 320px;">
                            <div class="flight-card-header">
                                <span class="flight-card-title">✈ Vol Aller — Jour 1 • <span id="flight-outbound-dep-label">{{ old('flights.outbound.from_city', optional($fOutbound)->from_label ?? '') ?: $flightDash }}</span> → <span id="flight-outbound-arr-label">{{ old('flights.outbound.to_city', optional($fOutbound)->to_label ?? '') ?: $flightDash }}</span></span>
                                <button type="button" class="flight-remove-btn flight-reset-btn" data-flight-type="outbound" title="Vider les champs">REMOVE</button>
                            </div>
                            <div class="flight-card-body">
                                <div class="flight-card-col"><div class="flight-icon-circle"><i class="bx bx-trip"></i></div></div>
                                <div class="flight-card-col flight-card-center">
                                    <div class="flight-dep"><div class="flight-date" id="flight-outbound-dep-date">{{ $fOutbound ? $fmtDate($fOutbound->departure_date) : $flightDash }}</div><div class="flight-place" id="flight-outbound-dep-place">{{ old('flights.outbound.from_city', optional($fOutbound)->from_label ?? '') ?: $flightDash }}</div></div>
                                    <div class="flight-arrow">→</div>
                                    <div class="flight-arr"><div class="flight-date" id="flight-outbound-arr-date">{{ $fOutbound ? $fmtDate($fOutbound->departure_date) : $flightDash }}</div><div class="flight-place" id="flight-outbound-arr-place">{{ old('flights.outbound.to_city', optional($fOutbound)->to_label ?? '') ?: $flightDash }}</div></div>
                                </div>
                                <div class="flight-card-col flight-card-baggage">
                                    <div>Cabin: <span id="flight-outbound-cabin-bag">{{ $fOutbound ? $fOutbound->cabin_baggage_display : (old('flights.outbound.baggage_cabin_kg') !== null ? old('flights.outbound.baggage_cabin_kg') . ' KGS' : $flightDash) }}</span></div>
                                    <div>Check-in: <span id="flight-outbound-checkin-bag">{{ $fOutbound ? $fOutbound->checkin_baggage_display : (old('flights.outbound.baggage_checkin_kg') !== null ? old('flights.outbound.baggage_checkin_kg') . ' KGS' : $flightDash) }}</span></div>
                                </div>
                            </div>
                            <div class="flight-card-badge-wrap">
                                @php $tOut = old('flights.outbound.is_tentative', optional($fOutbound)->is_tentative ?? false); @endphp
                                <span class="flight-badge-tentative" id="flight-outbound-tentative-badge" style="display:{{ $tOut ? 'inline-block' : 'none' }}">Tentative Flight</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary flight-edit-btn" data-flight-type="outbound">Modifier</button>
                    </div>
                    <div class="flight-card-edit" id="flight-outbound-edit" style="display:none;">
                        <h6 class="mb-3">Vol Aller (Jour 1)</h6>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Compagnie aérienne</label>
                                @if(Route::has('admin.circuits.airlines.create'))<a href="{{ route('admin.circuits.airlines.create') }}" class="float-end small" target="_blank">+ Ajouter</a>@endif
                                <select class="form-select" name="flights[outbound][airline_id]"><option value="">— Choisir —</option>
                                    @foreach($airlines ?? [] as $airline)
                                        <option value="{{ $airline->id }}" {{ old('flights.outbound.airline_id', optional($fOutbound)->airline_id ?? '') == $airline->id ? 'selected' : '' }}>{{ $airline->name }} @if($airline->code_iata)({{ $airline->code_iata }})@endif</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-6"><label class="form-label">Type de cabine</label>
                                <select class="form-select" name="flights[outbound][cabin]">
                                    @foreach(\App\Models\VoyageFlight::cabinOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('flights.outbound.cabin', optional($fOutbound)->cabin ?? 'economy') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-6"><label class="form-label">From (ville)</label><input type="text" class="form-control" name="flights[outbound][from_city]" value="{{ old('flights.outbound.from_city', optional($fOutbound)->from_city ?? '') }}" placeholder="ex. Casablanca"></div>
                            <div class="col-md-6"><label class="form-label">To (ville)</label><input type="text" class="form-control" name="flights[outbound][to_city]" value="{{ old('flights.outbound.to_city', optional($fOutbound)->to_city ?? '') }}" placeholder="ex. Paris"></div>
                            <div class="col-md-6"><label class="form-label">Date de départ</label><input type="date" class="form-control" name="flights[outbound][departure_date]" value="{{ old('flights.outbound.departure_date', $fOutbound && $fOutbound->departure_date ? $fOutbound->departure_date->format('Y-m-d') : '') }}"></div>
                            <div class="col-md-6"><label class="form-label">Numéro de vol</label><input type="text" class="form-control" name="flights[outbound][flight_number]" value="{{ old('flights.outbound.flight_number', optional($fOutbound)->flight_number ?? '') }}" placeholder="ex. AF1234"></div>
                            <div class="col-md-4"><label class="form-label">Cabine (kg)</label><input type="number" class="form-control" name="flights[outbound][baggage_cabin_kg]" value="{{ old('flights.outbound.baggage_cabin_kg', optional($fOutbound)->baggage_cabin_kg ?? '') }}" min="0" placeholder="7"></div>
                            <div class="col-md-4"><label class="form-label">Check-in (kg)</label><input type="number" class="form-control" name="flights[outbound][baggage_checkin_kg]" value="{{ old('flights.outbound.baggage_checkin_kg', optional($fOutbound)->baggage_checkin_kg ?? '') }}" min="0" placeholder="20"></div>
                            <div class="col-md-4"><label class="form-label">&nbsp;</label><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="flights[outbound][is_tentative]" value="1" id="flights_outbound_is_tentative" {{ old('flights.outbound.is_tentative', optional($fOutbound)->is_tentative ?? false) ? 'checked' : '' }}><label class="form-check-label" for="flights_outbound_is_tentative">Vol tentative</label></div></div>
                            <div class="col-12"><button type="button" class="btn btn-sm btn-primary flight-save-btn me-2" data-flight-type="outbound">Enregistrer</button><button type="button" class="btn btn-sm btn-secondary flight-cancel-btn" data-flight-type="outbound">Annuler</button></div>
                        </div>
                    </div>
                </div>

                {{-- Vol Retour (Dernier jour) --}}
                <div class="flight-block" data-flight-type="inbound">
                    <div class="flight-card-view" id="flight-inbound-card-view">
                        <div class="flight-card-admin" style="min-width: 320px;">
                            <div class="flight-card-header">
                                <span class="flight-card-title">✈ Vol Retour — Jour {{ $lastDayNumber }} • <span id="flight-inbound-dep-label">{{ old('flights.inbound.from_city', optional($fInbound)->from_label ?? '') ?: $flightDash }}</span> → <span id="flight-inbound-arr-label">{{ old('flights.inbound.to_city', optional($fInbound)->to_label ?? '') ?: $flightDash }}</span></span>
                                <button type="button" class="flight-remove-btn flight-reset-btn" data-flight-type="inbound" title="Vider les champs">REMOVE</button>
                            </div>
                            <div class="flight-card-body">
                                <div class="flight-card-col"><div class="flight-icon-circle"><i class="bx bx-trip"></i></div></div>
                                <div class="flight-card-col flight-card-center">
                                    <div class="flight-dep"><div class="flight-date" id="flight-inbound-dep-date">{{ $fInbound ? $fmtDate($fInbound->departure_date) : $flightDash }}</div><div class="flight-place" id="flight-inbound-dep-place">{{ old('flights.inbound.from_city', optional($fInbound)->from_label ?? '') ?: $flightDash }}</div></div>
                                    <div class="flight-arrow">→</div>
                                    <div class="flight-arr"><div class="flight-date" id="flight-inbound-arr-date">{{ $fInbound ? $fmtDate($fInbound->departure_date) : $flightDash }}</div><div class="flight-place" id="flight-inbound-arr-place">{{ old('flights.inbound.to_city', optional($fInbound)->to_label ?? '') ?: $flightDash }}</div></div>
                                </div>
                                <div class="flight-card-col flight-card-baggage">
                                    <div>Cabin: <span id="flight-inbound-cabin-bag">{{ $fInbound ? $fInbound->cabin_baggage_display : (old('flights.inbound.baggage_cabin_kg') !== null ? old('flights.inbound.baggage_cabin_kg') . ' KGS' : $flightDash) }}</span></div>
                                    <div>Check-in: <span id="flight-inbound-checkin-bag">{{ $fInbound ? $fInbound->checkin_baggage_display : (old('flights.inbound.baggage_checkin_kg') !== null ? old('flights.inbound.baggage_checkin_kg') . ' KGS' : $flightDash) }}</span></div>
                                </div>
                            </div>
                            <div class="flight-card-badge-wrap">
                                @php $tIn = old('flights.inbound.is_tentative', optional($fInbound)->is_tentative ?? false); @endphp
                                <span class="flight-badge-tentative" id="flight-inbound-tentative-badge" style="display:{{ $tIn ? 'inline-block' : 'none' }}">Tentative Flight</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary flight-edit-btn" data-flight-type="inbound">Modifier</button>
                    </div>
                    <div class="flight-card-edit" id="flight-inbound-edit" style="display:none;">
                        <h6 class="mb-3">Vol Retour (Jour {{ $lastDayNumber }})</h6>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Compagnie aérienne</label>
                                @if(Route::has('admin.circuits.airlines.create'))<a href="{{ route('admin.circuits.airlines.create') }}" class="float-end small" target="_blank">+ Ajouter</a>@endif
                                <select class="form-select" name="flights[inbound][airline_id]"><option value="">— Choisir —</option>
                                    @foreach($airlines ?? [] as $airline)
                                        <option value="{{ $airline->id }}" {{ old('flights.inbound.airline_id', optional($fInbound)->airline_id ?? '') == $airline->id ? 'selected' : '' }}>{{ $airline->name }} @if($airline->code_iata)({{ $airline->code_iata }})@endif</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-6"><label class="form-label">Type de cabine</label>
                                <select class="form-select" name="flights[inbound][cabin]">
                                    @foreach(\App\Models\VoyageFlight::cabinOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('flights.inbound.cabin', optional($fInbound)->cabin ?? 'economy') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-6"><label class="form-label">From (ville)</label><input type="text" class="form-control" name="flights[inbound][from_city]" value="{{ old('flights.inbound.from_city', optional($fInbound)->from_city ?? '') }}" placeholder="ex. Paris"></div>
                            <div class="col-md-6"><label class="form-label">To (ville)</label><input type="text" class="form-control" name="flights[inbound][to_city]" value="{{ old('flights.inbound.to_city', optional($fInbound)->to_city ?? '') }}" placeholder="ex. Casablanca"></div>
                            <div class="col-md-6"><label class="form-label">Date de départ</label><input type="date" class="form-control" name="flights[inbound][departure_date]" value="{{ old('flights.inbound.departure_date', $fInbound && $fInbound->departure_date ? $fInbound->departure_date->format('Y-m-d') : '') }}"></div>
                            <div class="col-md-6"><label class="form-label">Numéro de vol</label><input type="text" class="form-control" name="flights[inbound][flight_number]" value="{{ old('flights.inbound.flight_number', optional($fInbound)->flight_number ?? '') }}" placeholder="ex. AF1234"></div>
                            <div class="col-md-4"><label class="form-label">Cabine (kg)</label><input type="number" class="form-control" name="flights[inbound][baggage_cabin_kg]" value="{{ old('flights.inbound.baggage_cabin_kg', optional($fInbound)->baggage_cabin_kg ?? '') }}" min="0" placeholder="7"></div>
                            <div class="col-md-4"><label class="form-label">Check-in (kg)</label><input type="number" class="form-control" name="flights[inbound][baggage_checkin_kg]" value="{{ old('flights.inbound.baggage_checkin_kg', optional($fInbound)->baggage_checkin_kg ?? '') }}" min="0" placeholder="20"></div>
                            <div class="col-md-4"><label class="form-label">&nbsp;</label><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="flights[inbound][is_tentative]" value="1" id="flights_inbound_is_tentative" {{ old('flights.inbound.is_tentative', optional($fInbound)->is_tentative ?? false) ? 'checked' : '' }}><label class="form-check-label" for="flights_inbound_is_tentative">Vol tentative</label></div></div>
                            <div class="col-12"><button type="button" class="btn btn-sm btn-primary flight-save-btn me-2" data-flight-type="inbound">Enregistrer</button><button type="button" class="btn btn-sm btn-secondary flight-cancel-btn" data-flight-type="inbound">Annuler</button></div>
                        </div>
                    </div>
                </div>

                <p class="text-muted small mt-3">Vol Aller = Jour 1, Vol Retour = Dernier jour ({{ $lastDayNumber }}). REMOVE vide les champs sans supprimer le voyage. Enregistrez le voyage pour sauvegarder.</p>
            </div>

            <script>
            (function() {
                var dash = '—';
                function formatCardDate(dStr) {
                    if (!dStr) return dash;
                    var d = new Date(dStr + 'T12:00:00');
                    if (isNaN(d.getTime())) return dash;
                    var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    return days[d.getDay()] + ', ' + (d.getDate() < 10 ? '0' : '') + d.getDate() + ' ' + months[d.getMonth()];
                }
                function updateCard(type) {
                    var block = document.querySelector('.flight-block[data-flight-type="' + type + '"]');
                    if (!block) return;
                    var from = (block.querySelector('input[name="flights[' + type + '][from_city]"]') || {}).value;
                    var to = (block.querySelector('input[name="flights[' + type + '][to_city]"]') || {}).value;
                    var depDate = (block.querySelector('input[name="flights[' + type + '][departure_date]"]') || {}).value;
                    var cabinKg = (block.querySelector('input[name="flights[' + type + '][baggage_cabin_kg]"]') || {}).value;
                    var checkinKg = (block.querySelector('input[name="flights[' + type + '][baggage_checkin_kg]"]') || {}).value;
                    var tentative = (block.querySelector('input[name="flights[' + type + '][is_tentative]"]') || {}).checked;
                    document.getElementById('flight-' + type + '-dep-label').textContent = from || dash;
                    document.getElementById('flight-' + type + '-arr-label').textContent = to || dash;
                    document.getElementById('flight-' + type + '-dep-place').textContent = from || dash;
                    document.getElementById('flight-' + type + '-arr-place').textContent = to || dash;
                    document.getElementById('flight-' + type + '-dep-date').textContent = depDate ? formatCardDate(depDate) : dash;
                    document.getElementById('flight-' + type + '-arr-date').textContent = depDate ? formatCardDate(depDate) : dash;
                    document.getElementById('flight-' + type + '-cabin-bag').textContent = cabinKg ? cabinKg + ' KGS' : dash;
                    document.getElementById('flight-' + type + '-checkin-bag').textContent = checkinKg ? checkinKg + ' KGS' : dash;
                    document.getElementById('flight-' + type + '-tentative-badge').style.display = tentative ? 'inline-block' : 'none';
                }
                document.querySelectorAll('.flight-edit-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var t = this.getAttribute('data-flight-type');
                        document.getElementById('flight-' + t + '-card-view').style.display = 'none';
                        document.getElementById('flight-' + t + '-edit').style.display = 'block';
                    });
                });
                document.querySelectorAll('.flight-save-btn, .flight-cancel-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var t = this.getAttribute('data-flight-type');
                        document.getElementById('flight-' + t + '-edit').style.display = 'none';
                        document.getElementById('flight-' + t + '-card-view').style.display = 'flex';
                        if (this.classList.contains('flight-save-btn')) updateCard(t);
                    });
                });
                document.querySelectorAll('.flight-reset-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var t = this.getAttribute('data-flight-type');
                        var block = document.querySelector('.flight-block[data-flight-type="' + t + '"]');
                        if (block) block.querySelectorAll('input, select, textarea').forEach(function(el) {
                            if (el.name && el.name.indexOf('flights[' + t + ']') !== -1) {
                                if (el.type === 'checkbox') el.checked = false; else if (el.type !== 'submit') el.value = '';
                            }
                        });
                        updateCard(t);
                    });
                });

                // Flight options (multi): Add / Remove / Edit / Save / Cancel
                (function() {
                    var nextEl = document.getElementById('flight-opt-next-index');
                    var templatesEl = document.getElementById('flight-opt-templates');
                    if (!nextEl || !templatesEl) return;
                    function nextIndex() {
                        var n = parseInt(nextEl.value, 10) || 0;
                        nextEl.value = n + 1;
                        return n;
                    }
                    document.querySelectorAll('.btn-add-flight-opt').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var type = this.getAttribute('data-type');
                            var tpl = templatesEl.querySelector('[data-flight-tpl="' + type + '"]');
                            if (!tpl) return;
                            var clone = tpl.querySelector('.flight-opt-card').cloneNode(true);
                            var idx = nextIndex();
                            clone.setAttribute('data-flight-opt-index', idx);
                            clone.querySelectorAll('[name^="flight_options[-1]"]').forEach(function(el) {
                                el.name = el.name.replace('flight_options[-1]', 'flight_options[' + idx + ']');
                                el.removeAttribute('disabled');
                            });
                            var container = document.querySelector('.flight-opt-cards-' + type);
                            if (container) container.appendChild(clone);
                        });
                    });
                    document.getElementById('flights').addEventListener('click', function(e) {
                        if (e.target.closest('.flight-opt-remove')) {
                            var card = e.target.closest('.flight-opt-card');
                            if (card && confirm('Supprimer ce vol du formulaire ?')) card.remove();
                        }
                        if (e.target.closest('.flight-opt-edit-btn')) {
                            var card = e.target.closest('.flight-opt-card');
                            if (card) {
                                card.querySelector('.flight-opt-view').style.display = 'none';
                                card.querySelector('.flight-opt-edit').style.display = 'block';
                            }
                        }
                        if (e.target.closest('.flight-opt-cancel-btn')) {
                            var card = e.target.closest('.flight-opt-card');
                            if (card) {
                                card.querySelector('.flight-opt-edit').style.display = 'none';
                                card.querySelector('.flight-opt-view').style.display = '';
                            }
                        }
                        if (e.target.closest('.flight-opt-save-btn')) {
                            var card = e.target.closest('.flight-opt-card');
                            if (!card) return;
                            var from = card.querySelector('input[name*="[from_city]"]');
                            var to = card.querySelector('input[name*="[to_city]"]');
                            var dep = card.querySelector('input[name*="[departure_date]"]');
                            var cabin = card.querySelector('input[name*="[baggage_cabin_kg]"]');
                            var checkin = card.querySelector('input[name*="[baggage_checkin_kg]"]');
                            var dash = '—';
                            card.querySelector('.flight-opt-route').textContent = (from && from.value ? from.value.trim() : dash) + ' → ' + (to && to.value ? to.value.trim() : dash);
                            card.querySelector('.flight-opt-from').textContent = from && from.value ? from.value.trim() : dash;
                            card.querySelector('.flight-opt-to').textContent = to && to.value ? to.value.trim() : dash;
                            if (dep && dep.value) {
                                var d = new Date(dep.value);
                                card.querySelector('.flight-opt-dep-date').textContent = d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' });
                                card.querySelector('.flight-opt-arr-date').textContent = card.querySelector('.flight-opt-dep-date').textContent;
                            }
                            card.querySelector('.flight-opt-cabin-bag').textContent = cabin && cabin.value ? cabin.value + ' KGS' : dash;
                            card.querySelector('.flight-opt-checkin-bag').textContent = checkin && checkin.value ? checkin.value + ' KGS' : dash;
                            card.querySelector('.flight-opt-edit').style.display = 'none';
                            card.querySelector('.flight-opt-view').style.display = '';
                        }
                    });
                })();

                // Remplir automatiquement les transferts depuis vol + hôtel (si champs vides ou non modifiés)
                var hotelNameEl = document.getElementById('tour_hotel_name');
                var arrFrom = document.getElementById('transfer_arrival_from');
                var arrTo = document.getElementById('transfer_arrival_to');
                var depFrom = document.getElementById('transfer_departure_from');
                var depTo = document.getElementById('transfer_departure_to');
                var outboundTo = document.querySelector('input[name="flights[outbound][to_city]"]');
                var inboundFrom = document.querySelector('input[name="flights[inbound][from_city]"]');
                function applyHotelToTransfers() {
                    var h = hotelNameEl && hotelNameEl.value ? hotelNameEl.value.trim() : '';
                    if (h && arrTo) arrTo.value = arrTo.value.trim() === '' ? h : (arrTo.dataset.auto === '1' ? h : arrTo.value);
                    if (h && depFrom) depFrom.value = depFrom.value.trim() === '' ? h : (depFrom.dataset.auto === '1' ? h : depFrom.value);
                }
                function applyVolToTransfers() {
                    if (outboundTo && outboundTo.value.trim() && arrFrom) arrFrom.value = arrFrom.value.trim() === '' ? outboundTo.value.trim() : (arrFrom.dataset.auto === '1' ? outboundTo.value.trim() : arrFrom.value);
                    if (inboundFrom && inboundFrom.value.trim() && depTo) depTo.value = depTo.value.trim() === '' ? inboundFrom.value.trim() : (depTo.dataset.auto === '1' ? inboundFrom.value.trim() : depTo.value);
                }
                if (hotelNameEl) { hotelNameEl.addEventListener('blur', applyHotelToTransfers); }
                if (outboundTo) { outboundTo.addEventListener('blur', applyVolToTransfers); }
                if (inboundFrom) { inboundFrom.addEventListener('blur', applyVolToTransfers); }
            })();
            </script>

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
                                        <button type="button" class="btn btn-outline-primary btn-add-element-to-day" data-day-index="{{ $dayIndex }}" data-day-id="{{ $day->id }}" data-bs-toggle="modal" data-bs-target="#programme-add-element-modal">
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

            {{-- Modal Ajouter un élément (Vols / Transferts / Hôtels / Activités) --}}
            <div class="modal fade" id="programme-add-element-modal" tabindex="-1" aria-labelledby="programme-add-element-modal-label" aria-hidden="true" data-day-index="" data-day-id="">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="programme-add-element-modal-label">Ajouter un élément au jour</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-activities" type="button">Activités</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-flights" type="button">Vols</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-transfers" type="button">Transferts</button></li>
                                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-hotels" type="button">Hôtels</button></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="tab-activities">
                                    <div class="row g-3" id="programme-modal-activities">
                                        @foreach($activitiesCatalog as $act)
                                            <div class="col-md-6 col-lg-4">
                                                <div class="card h-100 programme-catalog-card">
                                                    <div class="card-body d-flex flex-column">
                                                        <h6 class="card-title">{{ $act->title }}</h6>
                                                        <p class="card-text small text-muted flex-grow-1">{{ \Illuminate\Support\Str::limit($act->description ?? '', 80) }}</p>
                                                        <button type="button" class="btn btn-sm btn-primary programme-modal-add-activity" data-activity-id="{{ $act->id }}" data-activity-title="{{ e($act->title) }}">Ajouter au jour</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                        @if($activitiesCatalog->isEmpty())
                                            <div class="col-12 text-muted">Aucune activité dans le catalogue.</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-flights"><p class="text-muted">Vols : à configurer (vol aller Jour 1, retour dernier jour).</p></div>
                                <div class="tab-pane fade" id="tab-transfers"><p class="text-muted">Transferts : à configurer.</p></div>
                                <div class="tab-pane fade" id="tab-hotels"><p class="text-muted">Hôtels : à configurer.</p></div>
                            </div>
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
                    '<p class="small text-muted mb-2 programme-day-inclus" data-day-index="' + index + '">INCLUS : 0 Activité</p>' +
                    '<h6 class="mt-3 mb-2">Éléments du jour</h6>' +
                    '<div class="programme-activities-list mb-3" data-day-index="' + index + '" data-day-id="">' + '</div>' +
                    '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                    '<button type="button" class="btn btn-outline-primary btn-add-element-to-day" data-day-index="' + index + '" data-day-id="" data-bs-toggle="modal" data-bs-target="#programme-add-element-modal"><i class="bx bx-plus"></i> Ajouter un élément</button>' +
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

        (function programmeModalAndAutosave() {
            var modal = document.getElementById('programme-add-element-modal');
            if (!modal) return;
            modal.addEventListener('show.bs.modal', function(e) {
                var btn = e.relatedTarget;
                if (btn && btn.classList && btn.classList.contains('btn-add-element-to-day')) {
                    modal.setAttribute('data-day-index', btn.getAttribute('data-day-index') || '');
                    modal.setAttribute('data-day-id', btn.getAttribute('data-day-id') || '');
                }
            });
            modal.addEventListener('click', function(e) {
                var addBtn = e.target.closest('.programme-modal-add-activity');
                if (!addBtn) return;
                e.preventDefault();
                var dayIndex = modal.getAttribute('data-day-index');
                var dayId = modal.getAttribute('data-day-id');
                var activityId = addBtn.getAttribute('data-activity-id');
                var activityTitle = addBtn.getAttribute('data-activity-title') || 'Activité';
                if (!dayIndex || !activityId) return;
                var card = document.querySelector('.programme-day-card[data-day-index="' + dayIndex + '"]');
                var list = card && card.querySelector('.programme-activities-list');
                if (!list) return;
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
                var inclusEl = card.querySelector('.programme-day-inclus');
                if (inclusEl) { var n = list.querySelectorAll('.programme-activity-row').length; inclusEl.textContent = 'INCLUS : ' + n + (n > 1 ? ' Activités' : ' Activité'); }
                if (window.bootstrap && bootstrap.Modal) { var m = bootstrap.Modal.getInstance(modal); if (m) m.hide(); }
                if (window.autosaveProgram) window.autosaveProgram();
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
                var list = card ? card.querySelector('.programme-activities-list') : null;
                if (!list) return;
                var k = list.querySelectorAll('.programme-activity-row').length;
                var prefix = 'programme_days[' + dayIndex + '][activities][' + k + ']';
                var row = document.createElement('div');
                row.className = 'programme-activity-row card mb-2';
                row.setAttribute('data-day-activity-id', '0');
                row.setAttribute('draggable', 'true');
                row.innerHTML = '<div class="card-body py-2">' +
                    '<div class="d-flex flex-wrap align-items-start gap-2">' +
                    '<span class="programme-activity-drag-handle text-muted cursor-grab me-1" title="Réordonner"><i class="bx bx-dots-vertical-rounded"></i></span>' +
                    '<input type="hidden" name="' + prefix + '[day_activity_id]" value="">' +
                    '<input type="hidden" name="' + prefix + '[activity_id]" value="' + activityId + '">' +
                    '<input type="hidden" name="' + prefix + '[sort_order]" value="' + k + '">' +
                    '<span class="fw-medium">' + (activityTitle || 'Activité').replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</span>' +
                    '<span class="form-check form-check-inline mb-0"><input type="hidden" name="' + prefix + '[is_included]" value="0"><input class="form-check-input" type="checkbox" name="' + prefix + '[is_included]" value="1" checked><label class="form-check-label small">Inclus</label></span>' +
                    '<span class="form-check form-check-inline mb-0"><input type="hidden" name="' + prefix + '[is_mandatory]" value="0"><input class="form-check-input" type="checkbox" name="' + prefix + '[is_mandatory]" value="1"><label class="form-check-label small">Obligatoire</label></span>' +
                    '<input type="text" class="form-control form-control-sm d-inline-block" style="max-width:200px" name="' + prefix + '[custom_title]" placeholder="Titre personnalisé">' +
                    '<textarea class="form-control form-control-sm" name="' + prefix + '[custom_description]" rows="1" placeholder="Description personnalisée"></textarea>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-programme-activity"><i class="bx bx-trash"></i></button>' +
                    '</div></div>';
                list.appendChild(row);
                select.value = '';
                if (window.autosaveProgram) window.autosaveProgram();
            }
            if (e.target.closest('.remove-programme-activity')) {
                var row = e.target.closest('.programme-activity-row');
                if (row && confirm('Retirer cette activité du jour ?')) {
                    var card = row.closest('.programme-day-card');
                    row.remove();
                    var inclus = card && card.querySelector('.programme-day-inclus');
                    var list = card && card.querySelector('.programme-activities-list');
                    if (inclus && list) { var n = list.querySelectorAll('.programme-activity-row').length; inclus.textContent = 'INCLUS : ' + n + (n > 1 ? ' Activités' : ' Activité'); }
                    if (window.autosaveProgram) window.autosaveProgram();
                }
            }
        });
    </script>
@endpush
