@extends('layouts.master-ajinsafro')
@section('title')
    Modifier l’activité
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier l’activité</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.activities.index') }}">Activités</a></li>
                        <li class="breadcrumb-item active">{{ $activity->title }}</li>
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

    <form action="{{ route('admin.circuits.activities.update', $activity) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $activity->title) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $activity->slug) }}">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5">{{ old('description', $activity->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="image" class="form-label">Image</label>
                            @php
                                $currentImageUrl = null;
                                if ($activity->image_id) {
                                    // Get image URL from WordPress attachment
                                    $attachment = \App\Models\WpPostmeta::where('post_id', $activity->image_id)
                                        ->where('meta_key', '_wp_attached_file')
                                        ->first();
                                    if ($attachment && $attachment->meta_value) {
                                        $uploadsUrl = config('wordpress.uploads_url', url('/wp-content/uploads'));
                                        $currentImageUrl = rtrim($uploadsUrl, '/') . '/' . $attachment->meta_value;
                                    }
                                }
                            @endphp
                            @if($currentImageUrl)
                                <div class="mb-2">
                                    <img src="{{ $currentImageUrl }}" alt="Image actuelle" class="img-fluid rounded" style="max-height: 200px; object-fit: cover; width: 100%;">
                                    <small class="text-muted d-block mt-1">Image actuelle</small>
                                </div>
                            @endif
                            <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this)">
                            <small class="text-muted d-block mt-1">Laisser vide pour conserver l'image actuelle. Formats acceptés: JPEG, PNG, WebP (max 5MB)</small>
                            <div id="image-preview-wrap" class="mt-3" style="display: none;">
                                <img id="image-preview" src="" alt="Nouvelle image" class="img-fluid rounded" style="max-height: 200px; object-fit: cover;">
                                <small class="text-muted d-block mt-1">Nouvelle image</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="base_price" class="form-label">Prix de base</label>
                            <input type="number" class="form-control" id="base_price" name="base_price" value="{{ old('base_price', $activity->base_price) }}" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label for="icon" class="form-label">Icône</label>
                            <input type="text" class="form-control" id="icon" name="icon" value="{{ old('icon', $activity->icon) }}">
                        </div>
                        <div class="mb-3">
                            <label for="default_duration_minutes" class="form-label">Durée par défaut (minutes)</label>
                            <input type="number" class="form-control" id="default_duration_minutes" name="default_duration_minutes" value="{{ old('default_duration_minutes', $activity->default_duration_minutes) }}" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="location_text" class="form-label">Lieu (texte)</label>
                            <input type="text" class="form-control" id="location_text" name="location_text" value="{{ old('location_text', $activity->location_text) }}">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                        <a href="{{ route('admin.circuits.activities.index') }}" class="btn btn-secondary w-100 mt-2">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewWrap = document.getElementById('image-preview-wrap');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewWrap.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        previewWrap.style.display = 'none';
    }
}
</script>
@endpush
