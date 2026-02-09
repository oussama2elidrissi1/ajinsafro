@extends('layouts.master-ajinsafro')
@section('title')
    Catalogue des activités
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Catalogue des activités</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item active">Activités</li>
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

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0">Activités (réutilisables dans le programme des tours)</h4>
                        <a href="{{ route('admin.circuits.activities.create') }}" class="btn btn-primary waves-effect waves-light">
                            <i class="bx bx-plus me-1"></i> Nouvelle activité
                        </a>
                    </div>
                    @if($activities->isEmpty())
                        <p class="text-muted mb-0">Aucune activité. <a href="{{ route('admin.circuits.activities.create') }}">Créer une activité</a> pour l’utiliser dans le programme jour par jour.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th width="80">Image</th>
                                        <th>Titre</th>
                                        <th>Slug</th>
                                        <th>Prix</th>
                                        <th>Durée déf.</th>
                                        <th>Lieu</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activities as $activity)
                                        @php
                                            $imageUrl = null;
                                            if ($activity->image_id) {
                                                $attachment = \App\Models\WpPostmeta::where('post_id', $activity->image_id)
                                                    ->where('meta_key', '_wp_attached_file')
                                                    ->first();
                                                if ($attachment && $attachment->meta_value) {
                                                    $uploadsUrl = config('wordpress.uploads_url', url('/wp-content/uploads'));
                                                    $imageUrl = rtrim($uploadsUrl, '/') . '/' . $attachment->meta_value;
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $activity->id }}</strong></td>
                                            <td>
                                                @if($imageUrl)
                                                    <img src="{{ $imageUrl }}" alt="{{ $activity->title }}" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $activity->title }}</td>
                                            <td><code>{{ $activity->slug }}</code></td>
                                            <td>{{ $activity->base_price ? number_format($activity->base_price, 2, ',', ' ') . ' DH' : '-' }}</td>
                                            <td>{{ $activity->default_duration_minutes ? $activity->default_duration_minutes . ' min' : '-' }}</td>
                                            <td>{{ $activity->location_text ?? '-' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.circuits.activities.edit', $activity) }}" class="btn btn-sm btn-soft-primary">Modifier</a>
                                                <form action="{{ route('admin.circuits.activities.destroy', $activity) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette activité ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-soft-danger">Supprimer</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            {{ $activities->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
