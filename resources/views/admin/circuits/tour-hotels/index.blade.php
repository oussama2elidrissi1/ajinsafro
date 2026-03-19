@extends('layouts.master-ajinsafro')
@section('title', 'Hôtels (Circuit)')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Hôtels (Circuit)</h4>
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

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(!empty($wpConnectionFailed))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Connexion base indisponible.</strong> Vérifiez la configuration.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="mb-0">
                <div class="row g-2 align-items-end">
                    <div class="col-auto flex-grow-1">
                        <label class="form-label small">Recherche par nom d'hôtel</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Nom de l'hôtel..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($hotels->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bx bxs-hotel display-4 d-block mb-2"></i>
                Aucun hôtel. Définir les hôtels depuis la fiche d’un voyage (Hébergement) ou en créant un voyage puis en gérant son hôtel ici après liaison.
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom</th>
                                <th>Rating</th>
                                <th>Voyage / circuit lié</th>
                                <th>Adresse</th>
                                <th>Types de chambres</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hotels as $hotel)
                                @php $tourTitle = $tourTitles[$hotel->tour_id] ?? 'Voyage #' . $hotel->tour_id; @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $hotel->hotel_name ?: '—' }}</strong>
                                    </td>
                                    <td>
                                        @if($hotel->stars)
                                            <span class="text-warning">★ {{ $hotel->stars }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.circuits.voyages.edit', $hotel->tour_id) }}">{{ \Str::limit($tourTitle, 40) }}</a>
                                        <br><small class="text-muted">ID {{ $hotel->tour_id }}</small>
                                    </td>
                                    <td class="small">{{ \Str::limit($hotel->address ?? '—', 35) }}</td>
                                    <td>
                                        @if(isset($hotel->rooms_count) && $hotel->rooms_count > 0)
                                            <span class="badge bg-light text-dark border">{{ $hotel->rooms_count }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.circuits.tour-hotels.show', $hotel->tour_id) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                                        <a href="{{ route('admin.circuits.tour-hotels.edit', $hotel->tour_id) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                        <a href="{{ route('admin.circuits.voyages.edit', $hotel->tour_id) }}" class="btn btn-sm btn-soft-primary">Voyage</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($hotels->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $hotels->links() }}
            </div>
        @endif
    @endif
@endsection
