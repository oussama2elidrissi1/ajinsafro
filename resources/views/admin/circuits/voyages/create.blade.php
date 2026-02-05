@extends('layouts.master-ajinsafro')
@section('title')
    Créer un tour WordPress
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Créer un tour WordPress</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.voyages.index') }}">Tours</a></li>
                        <li class="breadcrumb-item active">Créer</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

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

    <form action="{{ route('admin.circuits.voyages.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Informations principales</h4>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre du tour <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required placeholder="Ex : Séjour Dubaï 7 jours (6 nuits)">
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug (URL)</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug') }}" placeholder="laissez vide pour générer automatiquement">
                            <small class="text-muted">Sera visible sur : ajinsafro.net/tours/<strong>votre-slug</strong></small>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Description complète</label>
                            <textarea class="form-control" id="content" name="content" rows="10" placeholder="Description détaillée du tour">{{ old('content') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Extrait / Accroche</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3" placeholder="Texte court pour l'aperçu">{{ old('excerpt') }}</textarea>
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
                                <option value="publish" {{ old('post_status') === 'publish' ? 'selected' : '' }}>Publié</option>
                                <option value="draft" {{ old('post_status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                <option value="pending" {{ old('post_status') === 'pending' ? 'selected' : '' }}>En attente</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="destination" class="form-label">Destination</label>
                            <input type="text" class="form-control" id="destination" name="destination" value="{{ old('destination') }}" placeholder="Ex : Dubaï, EAU">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-2" style="font-size: 16px; font-weight: 600; color: #23282d;">Tour location</h5>
                        <p class="text-muted mb-3" style="font-size: 13px;">Select one or more location for your tour</p>
                        
                        <div class="mb-3">
                            <input 
                                type="text" 
                                id="locationSearchCreate" 
                                class="form-control" 
                                placeholder="Type to search"
                                style="font-size: 14px; padding: 6px 12px; border: 1px solid #ddd; border-radius: 3px;"
                            >
                        </div>
                        
                        <div class="wp-location-box" id="locationTreeContainer" style="border: 1px solid #ccd0d4; background: #fff; padding: 12px; max-height: 300px; overflow-y: auto; border-radius: 3px;">
                            @if(!empty($locationsTree))
                                @include('admin.circuits.voyages.partials.location-tree', [
                                    'locations' => $locationsTree, 
                                    'selectedIds' => $selectedLocationIds ?? []
                                ])
                            @else
                                <p class="text-muted mb-0" style="font-size: 13px; color: #646970;">Aucune location disponible</p>
                            @endif
                        </div>
                        
                        <small class="text-muted d-block mt-2" style="font-size: 12px; color: #646970;">
                            <i class="bx bx-info-circle"></i> 
                            <span id="locationCountTextCreate">0 location(s) sélectionnée(s)</span>
                        </small>

                        <div class="mb-3">
                            <label for="duration_text" class="form-label">Durée</label>
                            <input type="text" class="form-control" id="duration_text" name="duration_text" value="{{ old('duration_text') }}" placeholder="Ex : 7 jours / 6 nuits">
                        </div>

                        <div class="mb-3">
                            <label for="adult_price" class="form-label">Prix Adulte (MAD)</label>
                            <input type="number" class="form-control" id="adult_price" name="adult_price" value="{{ old('adult_price') }}" step="0.01" min="0" placeholder="5000">
                        </div>

                        <div class="mb-3">
                            <label for="child_price" class="form-label">Prix Enfant (MAD)</label>
                            <input type="number" class="form-control" id="child_price" name="child_price" value="{{ old('child_price') }}" step="0.01" min="0" placeholder="3000">
                        </div>

                        <div class="mb-3">
                            <label for="min_price" class="form-label">Prix Minimum (MAD)</label>
                            <input type="number" class="form-control" id="min_price" name="min_price" value="{{ old('min_price') }}" step="0.01" min="0">
                        </div>

                        <div class="mb-3">
                            <label for="min_people" class="form-label">Nombre min. de personnes</label>
                            <input type="number" class="form-control" id="min_people" name="min_people" value="{{ old('min_people') }}" min="1" placeholder="2">
                        </div>
                        
                        <div class="mb-3">
                            <label for="max_people" class="form-label">Nombre max. de personnes</label>
                            <input type="number" class="form-control" id="max_people" name="max_people" value="{{ old('max_people') }}" min="1" placeholder="15">
                        </div>

                        <div class="mb-3">
                            <label for="thumbnail_id" class="form-label">Image à la une (ID WP)</label>
                            <input type="number" class="form-control" id="thumbnail_id" name="thumbnail_id" value="{{ old('thumbnail_id') }}" placeholder="14434">
                            <small class="text-muted">ID de l'image dans la médiathèque WordPress</small>
                        </div>

                        <div class="mb-3">
                            <label for="gallery_ids" class="form-label">Galerie (IDs séparés par virgule)</label>
                            <input type="text" class="form-control" id="gallery_ids" name="gallery_ids" value="{{ old('gallery_ids') }}" placeholder="14435,14436,14437">
                            <small class="text-muted">IDs des images de la galerie WordPress</small>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_featured">
                                Tour à la une (Featured)
                            </label>
                        </div>
                        
                        <hr>
                        <p class="text-muted small"><i class="bx bx-info-circle"></i> Les champs Traveler avancés (tarification, réductions, taxonomies...) sont modifiables après création.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary waves-effect waves-light">
                            <i class="bx bx-save me-1"></i> Créer le tour dans WordPress
                        </button>
                        <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-secondary waves-effect">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <div class="alert alert-info mt-3">
        <i class="mdi mdi-information me-2"></i>
        <strong>Note :</strong> Le tour sera créé directement dans la base de données WordPress et sera immédiatement visible sur ajinsafro.net après publication.
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        // Location search filter for create form (WordPress Traveler behavior)
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('locationSearchCreate');
            const locationItems = document.querySelectorAll('.wp-location-item');
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    
                    if (searchTerm === '') {
                        locationItems.forEach(function(item) {
                            item.style.display = '';
                        });
                    } else {
                        locationItems.forEach(function(item) {
                            const title = item.getAttribute('data-title');
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
            
            // Update count
            const checkboxes = document.querySelectorAll('.location-checkbox');
            const updateCount = function() {
                const checked = document.querySelectorAll('.location-checkbox:checked').length;
                const countText = document.getElementById('locationCountTextCreate');
                if (countText) {
                    countText.textContent = checked + ' location(s) sélectionnée(s)';
                }
            };
            
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateCount);
            });
        });
    </script>
@endpush
