@extends('layouts.admin-v6')

@section('title', 'Créer un tour WordPress')
@section('page_title', 'Créer un tour WordPress')

@php
    $breadcrumbs = [ ['label' => 'Accueil', 'url' => (\Illuminate\Support\Facades\Route::has('admin.dashboard.v6') ? route('admin.dashboard.v6') : (\Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin')))], ['label' => 'WordPress', 'url' => route('admin.wordpress.tours.index')], ['label' => 'Créer'] ];
@endphp


@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Nouveau Tour WordPress</h4>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.wordpress.tours.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Titre du tour <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slug (URL)</label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" placeholder="laissez vide pour générer automatiquement">
                                <small class="text-muted">Sera visible sur : ajinsafro.net/tours/<strong>votre-slug</strong></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="content" class="form-control" rows="10">{{ old('content') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Extrait / Accroche</label>
                                <textarea name="excerpt" class="form-control" rows="3">{{ old('excerpt') }}</textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="post_status" class="form-select">
                                    <option value="publish" {{ old('post_status') === 'publish' ? 'selected' : '' }}>Publié</option>
                                    <option value="draft" {{ old('post_status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Destination</label>
                                <input type="text" name="destination" class="form-control" value="{{ old('destination') }}" placeholder="ex: Dubaï, EAU">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Durée</label>
                                <input type="text" name="duration_text" class="form-control" value="{{ old('duration_text') }}" placeholder="ex: 7 jours / 6 nuits">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Adulte (MAD)</label>
                                <input type="number" name="adult_price" class="form-control" value="{{ old('adult_price') }}" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Enfant (MAD)</label>
                                <input type="number" name="child_price" class="form-control" value="{{ old('child_price') }}" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Minimum (MAD)</label>
                                <input type="number" name="min_price" class="form-control" value="{{ old('min_price') }}" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nombre minimum de personnes</label>
                                <input type="number" name="min_people" class="form-control" value="{{ old('min_people') }}" min="1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Image à la une (ID)</label>
                                <input type="number" name="thumbnail_id" class="form-control" value="{{ old('thumbnail_id') }}" placeholder="ID de l'image WP">
                                <small class="text-muted">ID de l'image dans la médiathèque WordPress</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Galerie (IDs séparés par virgule)</label>
                                <input type="text" name="gallery_ids" class="form-control" value="{{ old('gallery_ids') }}" placeholder="14435,14436,14437">
                                <small class="text-muted">IDs des images de la galerie</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-content-save me-1"></i> Créer le tour
                                </button>
                                <a href="{{ route('admin.wordpress.tours.index') }}" class="btn btn-secondary">
                                    Annuler
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection






