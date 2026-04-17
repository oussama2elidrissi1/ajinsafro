@extends('layouts.master-ajinsafro')
@section('title')
    Catalogue Hébergements
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Catalogue Hébergements</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item">Hébergements</li>
                        <li class="breadcrumb-item active">Catalogue</li>
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
                        <h4 class="card-title mb-0">Liste des hébergements</h4>
                        <a href="{{ route('admin.wordpress.hotels.create') }}" class="btn btn-primary waves-effect waves-light">
                            <i class="bx bx-plus me-1"></i> Créer un hébergement
                        </a>
                    </div>
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, slug, resume, adresse">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="publish" @selected(($filters['status'] ?? '') === 'publish')>Publie</option>
                                <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Brouillon</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="hotel_star" class="form-select">
                                <option value="">Toutes les etoiles</option>
                                @for ($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" @selected((string) ($filters['star'] ?? '') === (string) $i)>{{ $i }} etoile(s)</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="featured" class="form-select">
                                <option value="">Tous</option>
                                <option value="1" @selected(($filters['featured'] ?? '') === '1')>A la une</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-light w-100">Filtrer</button>
                        </div>
                    </form>
                    @if($hotels->isEmpty())
                        <p class="text-muted mb-0">Aucun hébergement. <a href="{{ route('admin.wordpress.hotels.create') }}">Créer un hébergement</a> pour commencer.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Image</th>
                                        <th>ID</th>
                                        <th>Titre</th>
                                        <th>Adresse</th>
                                        <th>Statut</th>
                                        <th>Étoiles</th>
                                        <th>Prix min</th>
                                        <th>Modifié le</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hotels as $hotel)
                                        @php $thumbUrl = $media->getFeaturedImageUrlVerified($hotel->ID); @endphp
                                        <tr>
                                            <td>
                                                @if($thumbUrl)
                                                    <div class="position-relative" style="width: 50px; height: 50px;">
                                                        <img src="{{ $thumbUrl }}" alt="" class="rounded hotel-thumb-img" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                                                        <span class="d-none hotel-thumb-placeholder rounded bg-light text-muted small d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 10px;">—</span>
                                                    </div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $hotel->ID }}</td>
                                            <td>
                                                <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="text-body fw-medium">{{ $hotel->post_title }}</a>
                                                @if($hotel->post_excerpt)
                                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($hotel->post_excerpt, 70) }}</div>
                                                @endif
                                                @if($hotel->stHotel && $hotel->stHotel->is_featured === 'on')
                                                    <span class="badge bg-info ms-1">À la une</span>
                                                @endif
                                            </td>
                                            <td>{{ $hotel->stHotel->address ?? '-' }}</td>
                                            <td>
                                                @if($hotel->post_status === 'publish')
                                                    <span class="badge bg-success">Publié</span>
                                                @else
                                                    <span class="badge bg-secondary">Brouillon</span>
                                                @endif
                                            </td>
                                            <td>{{ $hotel->stHotel->hotel_star ?? '—' }}</td>
                                            <td>{{ $hotel->stHotel->min_price ?? '—' }}</td>
                                            <td>{{ $hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->format('d/m/Y H:i') : '—' }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="btn btn-sm btn-soft-primary waves-effect waves-light me-1">Modifier</a>
                                                <form action="{{ route('admin.wordpress.hotels.destroy', $hotel) }}" method="POST" class="d-inline" onsubmit="return confirm('Déplacer cet hôtel dans la corbeille ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-soft-danger waves-effect waves-light">Supprimer</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $hotels->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
