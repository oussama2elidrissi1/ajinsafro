@extends('layouts.master-ajinsafro')
@section('title')
    WordPress – Hotels
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">WordPress – Hotels</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item">WordPress</li>
                        <li class="breadcrumb-item active">Hotels</li>
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
                        <h4 class="card-title mb-0">Liste des hôtels (TravelerWP)</h4>
                        <a href="{{ route('admin.wordpress.hotels.create') }}" class="btn btn-primary waves-effect waves-light">
                            <i class="bx bx-plus me-1"></i> Créer un hôtel
                        </a>
                    </div>
                    @if($hotels->isEmpty())
                        <p class="text-muted mb-0">Aucun hôtel. <a href="{{ route('admin.wordpress.hotels.create') }}">Créer un hôtel</a> pour commencer.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Image</th>
                                        <th>ID</th>
                                        <th>Titre</th>
                                        <th>Statut</th>
                                        <th>Étoiles</th>
                                        <th>Prix min</th>
                                        <th>Modifié le</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hotels as $hotel)
                                        @php $thumbUrl = $media->getFeaturedImageUrl($hotel->ID); @endphp
                                        <tr>
                                            <td>
                                                @if($thumbUrl)
                                                    <img src="{{ $thumbUrl }}" alt="" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $hotel->ID }}</td>
                                            <td>
                                                <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="text-body fw-medium">{{ $hotel->post_title }}</a>
                                                @if($hotel->stHotel && $hotel->stHotel->is_featured === 'on')
                                                    <span class="badge bg-info ms-1">À la une</span>
                                                @endif
                                            </td>
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
