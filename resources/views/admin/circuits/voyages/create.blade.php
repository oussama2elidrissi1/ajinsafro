@extends('layouts.master-ajinsafro')
@section('title')
    Créer un voyage
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Créer un voyage</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.voyages.index') }}">Voyages</a></li>
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

    <form action="{{ route('admin.circuits.voyages.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Informations du voyage</h4>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom du voyage <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required placeholder="Ex : Circuit Maroc 8 jours">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Description générale du voyage">{{ old('description') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Image à la une (couverture)</label>
                            <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            <small class="text-muted">Image principale du voyage. Max 5 Mo. Stockage : storage/app/public/travels/featured/</small>
                        </div>
                        <div class="mb-3">
                            <label for="gallery_images" class="form-label">Galerie d’images (optionnel)</label>
                            <input type="file" class="form-control" id="gallery_images" name="gallery_images[]" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" multiple>
                            <small class="text-muted">Plusieurs images possibles. Max 5 Mo par fichier. Stockage : storage/app/public/travels/gallery/</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary waves-effect waves-light">Créer le voyage</button>
                        <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-secondary waves-effect">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <p class="text-muted mt-2">Après création, vous pourrez ajouter le programme jour par jour sur la page d’édition du voyage.</p>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
