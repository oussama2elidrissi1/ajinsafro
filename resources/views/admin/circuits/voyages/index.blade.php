@extends('layouts.master-ajinsafro')
@section('title')
    Voyages
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Voyages</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item active">Voyages</li>
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

    @if (!empty($wpConnectionFailed))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Connexion WordPress indisponible.</strong> Vérifiez dans <code>.env</code> que <code>WP_DB_DATABASE</code> (et éventuellement <code>WP_DB_HOST</code>, <code>WP_DB_USERNAME</code>, <code>WP_DB_PASSWORD</code>) pointent vers une base existante. Erreur typique : <code>Unknown database 'ajinsafronet_wp_tkrpc'</code>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="mdi mdi-information me-2"></i>
                        <strong>CRUD Direct WordPress</strong> - {{ $tours->total() }} tours affichés depuis la DB WordPress. Modifications immédiatement visibles sur <a href="https://ajinsafro.net" target="_blank" class="alert-link">ajinsafro.net</a>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0">Liste des tours WordPress (st_tours)</h4>
                        <a href="{{ route('admin.circuits.voyages.create') }}" class="btn btn-primary waves-effect waves-light">
                            <i class="bx bx-plus me-1"></i> Créer un tour
                        </a>
                    </div>
                    @if($tours->isEmpty())
                        <p class="text-muted mb-0">Aucun tour trouvé. <a href="{{ route('admin.circuits.voyages.create') }}">Créer un tour</a> pour commencer.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th>Titre</th>
                                        <th>Destination</th>
                                        <th>Durée</th>
                                        <th>Prix Adulte</th>
                                        <th>Status</th>
                                        <th>Modifié le</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tours as $tour)
                                        <tr>
                                            <td><strong>{{ $tour->ID }}</strong></td>
                                            <td>
                                                <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="text-body fw-medium">{{ $tour->post_title }}</a>
                                                <br><small class="text-muted">{{ $tour->post_name }}</small>
                                            </td>
                                            <td>{{ $tour->address ?? '-' }}</td>
                                            <td>{{ $tour->duration_day ?? '-' }}</td>
                                            <td>
                                                @if($tour->adult_price)
                                                    <strong>{{ number_format($tour->adult_price, 0, ',', ' ') }} MAD</strong>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if($tour->post_status === 'publish')
                                                    <span class="badge bg-success">Publié</span>
                                                @elseif($tour->post_status === 'draft')
                                                    <span class="badge bg-secondary">Brouillon</span>
                                                @else
                                                    <span class="badge bg-warning">{{ $tour->post_status }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $tour->post_modified->format('d/m/Y H:i') }}</td>
                                            <td class="text-end">
                                                <a href="https://ajinsafro.net/tours/{{ $tour->post_name }}" target="_blank" class="btn btn-sm btn-soft-info waves-effect waves-light me-1" title="Voir sur WordPress">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="btn btn-sm btn-soft-primary waves-effect waves-light me-1">Modifier</a>
                                                <form action="{{ route('admin.circuits.voyages.destroy', $tour->ID) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce tour de WordPress ?');">
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
                            {{ $tours->links() }}
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
