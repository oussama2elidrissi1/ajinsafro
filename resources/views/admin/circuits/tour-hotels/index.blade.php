@extends('layouts.master-ajinsafro')
@section('title')
    Hôtels des circuits
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Hôtels des circuits</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item active">Hôtels</li>
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
            <strong>Connexion WordPress indisponible.</strong> Vérifiez la configuration de la base WP.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Un seul hôtel principal par circuit (check-in jour 1, check-out dernier jour). Cliquez sur « Gérer l'hôtel » pour le renseigner.
                    </p>
                    @if($tours->isEmpty())
                        <p class="text-muted mb-0">Aucun tour. <a href="{{ route('admin.circuits.voyages.create') }}">Créer un tour</a> puis revenir ici pour définir l'hôtel.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th>Titre du circuit</th>
                                        <th>Hôtel</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tours as $tour)
                                        @php $hotel = $hotelsByTour[$tour->ID] ?? null; @endphp
                                        <tr>
                                            <td><strong>{{ $tour->ID }}</strong></td>
                                            <td>
                                                <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="text-body">{{ $tour->post_title }}</a>
                                            </td>
                                            <td>
                                                @if($hotel && $hotel->hotel_name)
                                                    {{ $hotel->hotel_name }}
                                                    @if($hotel->stars)
                                                        <span class="text-warning">★{{ $hotel->stars }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.circuits.tour-hotels.edit', $tour->ID) }}" class="btn btn-sm btn-soft-primary waves-effect waves-light">Gérer l'hôtel</a>
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
