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
                            <label for="thumbnail_id" class="form-label">Image à la une (ID WP)</label>
                            <input type="number" class="form-control" id="thumbnail_id" name="thumbnail_id" value="{{ old('thumbnail_id', $meta['thumbnail_id'] ?? '') }}" placeholder="14434">
                            <small class="text-muted">ID de l'image dans la médiathèque WordPress</small>
                        </div>

                        <div class="mb-3">
                            <label for="gallery_ids" class="form-label">Galerie (IDs séparés par virgule)</label>
                            <input type="text" class="form-control" id="gallery_ids" name="gallery_ids" value="{{ old('gallery_ids', $gallery_csv ?? '') }}" placeholder="14435,14436,14437">
                            <small class="text-muted">IDs des images de la galerie WordPress</small>
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
