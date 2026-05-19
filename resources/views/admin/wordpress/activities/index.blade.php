@extends('layouts.admin-v6')
@section('title', 'WordPress - ActivitÃ©s')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Catalogue activitÃ©s</h4>
                <a href="{{ route('admin.wordpress.activities.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Nouvelle activitÃ©
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Nom, slug, lieu, type">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="publish" @selected($filters['status'] === 'publish')>PubliÃ©</option>
                        <option value="draft" @selected($filters['status'] === 'draft')>Brouillon</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type_activity" class="form-select">
                        <option value="">Tous les types</option>
                        @foreach($typeOptions as $option)
                            <option value="{{ $option }}" @selected($filters['type'] === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-light w-100">Filtrer</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Nom</th>
                            <th>Lieu</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>DurÃ©e</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            @php
                                $detail = $activity->stActivity;
                                $thumb = $media->getFeaturedImageUrlVerified($activity->ID);
                            @endphp
                            <tr>
                                <td>
                                    @if($thumb)
                                        <img src="{{ $thumb }}" alt="" class="rounded" style="width:50px;height:50px;object-fit:cover;">
                                    @else
                                        <span class="text-muted">â€”</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $activity->post_title }}</div>
                                    <div class="text-muted small">{{ $activity->post_name }}</div>
                                </td>
                                <td>{{ $detail->address ?? ($activity->getMeta('aj_activity_place_text') ?: 'â€”') }}</td>
                                <td>{{ $detail->type_activity ?: ($activity->getMeta('aj_activity_category') ?: 'â€”') }}</td>
                                <td>
                                    @if($detail && ($detail->adult_price || $detail->min_price))
                                        {{ number_format((float) ($detail->adult_price ?: $detail->min_price), 0, ',', ' ') }} MAD
                                    @else
                                        â€”
                                    @endif
                                </td>
                                <td>{{ $detail->duration ?? 'â€”' }}</td>
                                <td>
                                    <span class="badge bg-{{ $activity->post_status === 'publish' ? 'success' : 'secondary' }}">
                                        {{ $activity->post_status === 'publish' ? 'PubliÃ©' : 'Brouillon' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.wordpress.activities.edit', $activity) }}" class="btn btn-sm btn-soft-primary">Modifier</a>
                                    <form action="{{ route('admin.wordpress.activities.destroy', $activity) }}" method="POST" class="d-inline" onsubmit="return confirm('DÃ©placer cette activitÃ© dans la corbeille ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-soft-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Aucune activitÃ© trouvÃ©e.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $activities->links() }}</div>
        </div>
    </div>
@endsection

