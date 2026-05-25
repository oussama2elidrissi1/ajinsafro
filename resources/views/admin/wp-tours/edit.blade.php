@extends('layouts.admin-v6')

@section('title', 'ÉÉditer tour WordPress')
@section('page_title', 'ÉÉditer tour WordPress')

@php
    $breadcrumbs = [ ['label' => 'Accueil', 'url' => (\Illuminate\Support\Facades\Route::has('admin.dashboard.v6') ? route('admin.dashboard.v6') : (\Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin')))], ['label' => 'WordPress', 'url' => route('admin.wordpress.tours.index')], ['label' => 'ÉÉditer'] ];
@endphp


@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">?Éditer Tour #{{ $tour['id'] }}</h4>
                <a href="https://ajinsafro.net/tours/{{ $tour['slug'] }}" target="_blank" class="btn btn-sm btn-info">
                    <i class="mdi mdi-eye me-1"></i> Voir sur WordPress
                </a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.wordpress.tours.update', $tour['id']) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Titre du tour <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $tour['title']) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slug (URL)</label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $tour['slug']) }}">
                                <small class="text-muted">Visible sur : ajinsafro.net/tours/<strong>{{ old('slug', $tour['slug']) }}</strong></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="content" class="form-control" rows="10">{{ old('content', $tour['content']) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Extrait / Accroche</label>
                                <textarea name="excerpt" class="form-control" rows="3">{{ old('excerpt', $tour['excerpt']) }}</textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="post_status" class="form-select">
                                    <option value="publish" {{ old('post_status', $tour['status']) === 'publish' ? 'selected' : '' }}>Publié</option>
                                    <option value="draft" {{ old('post_status', $tour['status']) === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Destination</label>
                                <input type="text" name="destination" class="form-control" value="{{ old('destination', $tour['destination']) }}" placeholder="ex: Dubaï, EAU">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Durée</label>
                                <input type="text" name="duration_text" class="form-control" value="{{ old('duration_text', $tour['duration_text']) }}" placeholder="ex: 7 jours / 6 nuits">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Adulte (MAD)</label>
                                <input type="number" name="adult_price" class="form-control" value="{{ old('adult_price', $tour['adult_price']) }}" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Enfant (MAD)</label>
                                <input type="number" name="child_price" class="form-control" value="{{ old('child_price', $tour['child_price']) }}" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prix Minimum (MAD)</label>
                                <input type="number" name="min_price" class="form-control" value="{{ old('min_price', $tour['min_price']) }}" step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nombre minimum de personnes</label>
                                <input type="number" name="min_people" class="form-control" value="{{ old('min_people', $tour['min_people']) }}" min="1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Image à la une (ID)</label>
                                <input type="number" name="thumbnail_id" class="form-control" value="{{ old('thumbnail_id', $tour['thumbnail_id']) }}" placeholder="ID de l'image WP">
                                <small class="text-muted">ID de l'image dans la médiathèque WordPress</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Galerie (IDs séparés par virgule)</label>
                                <input type="text" name="gallery_ids" class="form-control" value="{{ old('gallery_ids', is_array($tour['gallery']) ? implode(',', $tour['gallery']) : $tour['gallery']) }}" placeholder="14435,14436,14437">
                                <small class="text-muted">IDs des images de la galerie</small>
                            </div>

                            <div class="alert alert-info">
                                <small>
                                    <strong>Créé :</strong> {{ $tour['created_at']->format('d/m/Y H:i') }}<br>
                                    <strong>Modifié :</strong> {{ $tour['updated_at']->format('d/m/Y H:i') }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-content-save me-1"></i> Enregistrer
                                </button>
                                <a href="{{ route('admin.wordpress.tours.index') }}" class="btn btn-secondary">
                                    Retour à la liste
                                </a>
                                <form action="{{ route('admin.wordpress.tours.destroy', $tour['id']) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('Supprimer définitivement ce tour de WordPress ?');"
                                      class="ms-auto">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="mdi mdi-delete me-1"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection







