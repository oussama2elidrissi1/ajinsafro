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
        <strong>WordPress ID: {{ $voyage->ID }}</strong> - Modifications immédiatement visibles sur 
        <a href="https://ajinsafro.net/tours/{{ $voyage->post_name }}" target="_blank" class="alert-link">ajinsafro.net/tours/{{ $voyage->post_name }}</a>
    </div>

    <div class="mb-3">
        <a href="https://ajinsafro.net/tours/{{ $voyage->post_name }}" target="_blank" class="btn btn-soft-info waves-effect waves-light me-2">
            <i class="bx bx-show me-1"></i> Voir sur WordPress
        </a>
        <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-secondary waves-effect">Retour à la liste</a>
    </div>

    <form action="{{ route('admin.circuits.voyages.update', $voyage->ID) }}" method="POST">
        @csrf
        @method('PATCH')
        
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
                            <small class="text-muted">Visible sur : ajinsafro.net/tours/<strong>{{ old('slug', $voyage->post_name) }}</strong></small>
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
                        <h4 class="card-title mb-4">Paramètres & Prix</h4>

                        <div class="mb-3">
                            <label for="post_status" class="form-label">Statut</label>
                            <select class="form-select" id="post_status" name="post_status">
                                <option value="publish" {{ old('post_status', $voyage->post_status) === 'publish' ? 'selected' : '' }}>Publié</option>
                                <option value="draft" {{ old('post_status', $voyage->post_status) === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                <option value="pending" {{ old('post_status', $voyage->post_status) === 'pending' ? 'selected' : '' }}>En attente</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="destination" class="form-label">Destination</label>
                            <input type="text" class="form-control" id="destination" name="destination" value="{{ old('destination', $meta['address'] ?? '') }}" placeholder="Ex : Dubaï, EAU">
                        </div>

                        <div class="mb-3">
                            <label for="duration_text" class="form-label">Durée</label>
                            <input type="text" class="form-control" id="duration_text" name="duration_text" value="{{ old('duration_text', $meta['duration_day'] ?? '') }}" placeholder="Ex : 7 jours / 6 nuits">
                        </div>

                        <div class="mb-3">
                            <label for="adult_price" class="form-label">Prix Adulte (MAD)</label>
                            <input type="number" class="form-control" id="adult_price" name="adult_price" value="{{ old('adult_price', $meta['adult_price'] ?? '') }}" step="0.01" min="0" placeholder="5000">
                        </div>

                        <div class="mb-3">
                            <label for="child_price" class="form-label">Prix Enfant (MAD)</label>
                            <input type="number" class="form-control" id="child_price" name="child_price" value="{{ old('child_price', $meta['child_price'] ?? '') }}" step="0.01" min="0" placeholder="3000">
                        </div>

                        <div class="mb-3">
                            <label for="min_price" class="form-label">Prix Minimum (MAD)</label>
                            <input type="number" class="form-control" id="min_price" name="min_price" value="{{ old('min_price', $meta['min_price'] ?? '') }}" step="0.01" min="0">
                        </div>

                        <div class="mb-3">
                            <label for="min_people" class="form-label">Nombre min. de personnes</label>
                            <input type="number" class="form-control" id="min_people" name="min_people" value="{{ old('min_people', $meta['min_people'] ?? '') }}" min="1" placeholder="2">
                        </div>
                        
                        <div class="mb-3">
                            <label for="max_people" class="form-label">Nombre max. de personnes</label>
                            <input type="number" class="form-control" id="max_people" name="max_people" value="{{ old('max_people', $meta['max_people'] ?? '') }}" min="1" placeholder="15">
                        </div>

                        <div class="mb-3">
                            <label for="thumbnail_id" class="form-label">Image à la une (ID WP)</label>
                            <input type="number" class="form-control" id="thumbnail_id" name="thumbnail_id" value="{{ old('thumbnail_id', $meta['thumbnail_id'] ?? '') }}" placeholder="14434">
                            <small class="text-muted">ID de l'image dans la médiathèque WordPress</small>
                        </div>

                        <div class="mb-3">
                            <label for="gallery_ids" class="form-label">Galerie (IDs séparés par virgule)</label>
                            <input type="text" class="form-control" id="gallery_ids" name="gallery_ids" value="{{ old('gallery_ids', $gallery_csv ?? '') }}" placeholder="14435,14436,14437">
                            <small class="text-muted">IDs des images de la galerie WordPress</small>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $meta['is_featured'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_featured">
                                Tour à la une (Featured)
                            </label>
                        </div>

                        <div class="alert alert-secondary">
                            <small>
                                <strong>Créé :</strong> {{ $voyage->post_date->format('d/m/Y H:i') }}<br>
                                <strong>Modifié :</strong> {{ $voyage->post_modified->format('d/m/Y H:i') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- NEW: Traveler Advanced Fields --}}
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Paramètres Traveler</h4>
                        
                        <div class="mb-3">
                            <label for="tour_price_by" class="form-label">Tarification par</label>
                            <select class="form-select" id="tour_price_by" name="tour_price_by">
                                <option value="">-- Sélectionner --</option>
                                <option value="person" {{ old('tour_price_by', $meta['tour_price_by'] ?? '') === 'person' ? 'selected' : '' }}>Par personne</option>
                                <option value="group" {{ old('tour_price_by', $meta['tour_price_by'] ?? '') === 'group' ? 'selected' : '' }}>Par groupe</option>
                                <option value="fixed" {{ old('tour_price_by', $meta['tour_price_by'] ?? '') === 'fixed' ? 'selected' : '' }}>Prix fixe</option>
                            </select>
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
                            <label for="discount_by_people_type" class="form-label">Réduction selon type de personne</label>
                            <input type="text" class="form-control" id="discount_by_people_type" name="discount_by_people_type" value="{{ old('discount_by_people_type', $meta['discount_by_people_type'] ?? '') }}" placeholder="adult,child">
                        </div>
                        
                        <div class="mb-3">
                            <label for="calculator_discount_by_people_type" class="form-label">Calculateur réduction par personne</label>
                            <input type="text" class="form-control" id="calculator_discount_by_people_type" name="calculator_discount_by_people_type" value="{{ old('calculator_discount_by_people_type', $meta['calculator_discount_by_people_type'] ?? '') }}">
                        </div>
                        
                        <div class="mb-3">
                            <label for="multi_location" class="form-label">Multi-localisation</label>
                            <input type="text" class="form-control" id="multi_location" name="multi_location" value="{{ old('multi_location', $meta['multi_location'] ?? '') }}" placeholder="on/off">
                        </div>
                        
                        <div class="mb-3">
                            <label for="tours_program_style" class="form-label">Style du programme</label>
                            <select class="form-select" id="tours_program_style" name="tours_program_style">
                                <option value="">-- Défaut --</option>
                                <option value="tab" {{ old('tours_program_style', $meta['tours_program_style'] ?? '') === 'tab' ? 'selected' : '' }}>Onglets</option>
                                <option value="accordion" {{ old('tours_program_style', $meta['tours_program_style'] ?? '') === 'accordion' ? 'selected' : '' }}>Accordéon</option>
                                <option value="list" {{ old('tours_program_style', $meta['tours_program_style'] ?? '') === 'list' ? 'selected' : '' }}>Liste</option>
                            </select>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="hide_adult_in_booking_form" name="hide_adult_in_booking_form" value="1" {{ old('hide_adult_in_booking_form', $meta['hide_adult_in_booking_form'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="hide_adult_in_booking_form">
                                Masquer champ adulte dans formulaire réservation
                            </label>
                        </div>
                        
                        <div class="mb-3">
                            <label for="st_tour_external_booking" class="form-label">Lien réservation externe</label>
                            <input type="text" class="form-control" id="st_tour_external_booking" name="st_tour_external_booking" value="{{ old('st_tour_external_booking', $meta['st_tour_external_booking'] ?? '') }}" placeholder="https://...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="st_google_map" class="form-label">Google Map (iframe ou code)</label>
                            <textarea class="form-control" id="st_google_map" name="st_google_map" rows="3">{{ old('st_google_map', $meta['st_google_map'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Contenu Tour</h4>
                        
                        <div class="mb-3">
                            <label for="tours_include" class="form-label">Inclus dans le prix</label>
                            <textarea class="form-control" id="tours_include" name="tours_include" rows="5" placeholder="- Hébergement&#10;- Petit-déjeuner&#10;- Guide">{{ old('tours_include', $meta['tours_include'] ?? '') }}</textarea>
                            <small class="text-muted">Une ligne par élément</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tours_exclude" class="form-label">Non inclus</label>
                            <textarea class="form-control" id="tours_exclude" name="tours_exclude" rows="5" placeholder="- Vol international&#10;- Assurance&#10;- Boissons">{{ old('tours_exclude', $meta['tours_exclude'] ?? '') }}</textarea>
                            <small class="text-muted">Une ligne par élément</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tours_highlight" class="form-label">Points forts</label>
                            <textarea class="form-control" id="tours_highlight" name="tours_highlight" rows="5" placeholder="- Vue panoramique&#10;- Expérience unique&#10;- Soirée culturelle">{{ old('tours_highlight', $meta['tours_highlight'] ?? '') }}</textarea>
                            <small class="text-muted">Une ligne par élément</small>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Moyens de paiement</h4>
                        <small class="text-muted d-block mb-3">Cocher les passerelles activées</small>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_paypal" name="is_meta_payment_gateway_st_paypal" value="1" {{ old('is_meta_payment_gateway_st_paypal', $meta['is_meta_payment_gateway_st_paypal'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_meta_payment_gateway_st_paypal">PayPal</label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_onepay" name="is_meta_payment_gateway_st_onepay" value="1" {{ old('is_meta_payment_gateway_st_onepay', $meta['is_meta_payment_gateway_st_onepay'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_meta_payment_gateway_st_onepay">OnePay</label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_onepay_atm" name="is_meta_payment_gateway_st_onepay_atm" value="1" {{ old('is_meta_payment_gateway_st_onepay_atm', $meta['is_meta_payment_gateway_st_onepay_atm'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_meta_payment_gateway_st_onepay_atm">OnePay ATM</label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_payu" name="is_meta_payment_gateway_st_payu" value="1" {{ old('is_meta_payment_gateway_st_payu', $meta['is_meta_payment_gateway_st_payu'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_meta_payment_gateway_st_payu">PayU</label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_payulatam" name="is_meta_payment_gateway_st_payulatam" value="1" {{ old('is_meta_payment_gateway_st_payulatam', $meta['is_meta_payment_gateway_st_payulatam'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_meta_payment_gateway_st_payulatam">PayU Latam</label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_payumoney" name="is_meta_payment_gateway_st_payumoney" value="1" {{ old('is_meta_payment_gateway_st_payumoney', $meta['is_meta_payment_gateway_st_payumoney'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_meta_payment_gateway_st_payumoney">PayUmoney</label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_meta_payment_gateway_st_razor" name="is_meta_payment_gateway_st_razor" value="1" {{ old('is_meta_payment_gateway_st_razor', $meta['is_meta_payment_gateway_st_razor'] ?? '') === 'on' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_meta_payment_gateway_st_razor">Razorpay</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- NEW: Taxonomies --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Catégories & Taxonomies</h4>
                        
                        <div class="row">
                            @if(isset($availableTaxonomies['st_tour_type']) && $availableTaxonomies['st_tour_type']->isNotEmpty())
                            <div class="col-lg-3">
                                <label class="form-label">Type de tour</label>
                                @foreach($availableTaxonomies['st_tour_type'] as $term)
                                <div class="form-check">
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
                                <label class="form-label">Durée</label>
                                @foreach($availableTaxonomies['durations'] as $term)
                                <div class="form-check">
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
                                <label class="form-label">Langue (language)</label>
                                @foreach($availableTaxonomies['language'] as $term)
                                <div class="form-check">
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
                                <label class="form-label">Langues (languages)</label>
                                @foreach($availableTaxonomies['languages'] as $term)
                                <div class="form-check">
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
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div>
                            <button type="submit" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-save me-1"></i> Enregistrer les modifications
                            </button>
                            <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-secondary waves-effect">Annuler</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Formulaire de suppression séparé --}}
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
@endpush
