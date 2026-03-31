@extends('layouts.master-ajinsafro')

@section('title')
    Catalogue des activites
@endsection

@section('content')
    @php
        $mediaService = app(\App\Services\WordPressMediaService::class);
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Catalogue des activites</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item active">Activites</li>
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

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div>
                    <h4 class="card-title mb-1">Activites reutilisables</h4>
                    <p class="text-muted mb-0">Chaque activite est rattachee a une region pour filtrer le catalogue dans les voyages.</p>
                </div>
                <a href="{{ route('admin.circuits.activities.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> Nouvelle activite
                </a>
            </div>

            @if($activities->isEmpty())
                <div class="text-muted">
                    Aucune activite disponible. <a href="{{ route('admin.circuits.activities.create') }}">Creer la premiere activite</a>.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="72">ID</th>
                                <th width="96">Visuel</th>
                                <th>Activite</th>
                                <th>Region</th>
                                <th>Tarifs</th>
                                <th>Ages</th>
                                <th>Duree</th>
                                <th>Galerie</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                @php
                                    $galleryIds = collect($activity->gallery_image_ids ?? [])
                                        ->map(fn ($id) => (int) $id)
                                        ->filter(fn ($id) => $id > 0)
                                        ->values();

                                    if ($galleryIds->isEmpty() && (int) ($activity->image_id ?? 0) > 0) {
                                        $galleryIds = collect([(int) $activity->image_id]);
                                    }

                                    $coverUrl = $galleryIds->isNotEmpty()
                                        ? $mediaService->getAttachmentUrl((int) $galleryIds->first())
                                        : null;
                                @endphp
                                <tr>
                                    <td><strong>#{{ $activity->id }}</strong></td>
                                    <td>
                                        @if($coverUrl)
                                            <img src="{{ $coverUrl }}" alt="{{ $activity->title }}" class="img-thumbnail" style="width:72px;height:72px;object-fit:cover;">
                                        @else
                                            <div class="border rounded bg-light d-flex align-items-center justify-content-center text-muted" style="width:72px;height:72px;">
                                                <i class="bx bx-image font-size-24"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $activity->title }}</div>
                                        <div class="text-muted small">{{ $activity->activity_type ?: 'Type non renseigne' }}</div>
                                        <div class="text-muted small"><code>{{ $activity->slug }}</code></div>
                                    </td>
                                    <td>{{ $activity->region_name ?: $activity->location_text ?: '-' }}</td>
                                    <td>
                                        <div class="small">Adulte: <strong>{{ number_format((float) ($activity->adult_price ?? $activity->base_price ?? 0), 2, ',', ' ') }} MAD</strong></div>
                                        <div class="small text-muted">Enfant: {{ number_format((float) ($activity->child_price ?? 0), 2, ',', ' ') }} MAD</div>
                                    </td>
                                    <td>
                                        <div class="small">Min: {{ $activity->min_age ?? '-' }} ans</div>
                                        <div class="small text-muted">Max: {{ $activity->max_age ?? '-' }} ans</div>
                                    </td>
                                    <td>{{ $activity->default_duration_minutes ? $activity->default_duration_minutes . ' min' : '-' }}</td>
                                    <td>{{ $galleryIds->count() }}</td>
                                    <td>
                                        @if($activity->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.circuits.activities.edit', $activity) }}" class="btn btn-sm btn-soft-primary">Modifier</a>
                                        <form action="{{ route('admin.circuits.activities.destroy', $activity) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette activite ?');">
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

                <div class="d-flex justify-content-end mt-4">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
