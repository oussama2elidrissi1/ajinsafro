@extends('layouts.master-ajinsafro')
@section('title', 'Hôtels des circuits')

@push('styles')
<style>
    .circuit-hotel-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        border: 1px solid rgba(0,0,0,.06);
        overflow: hidden;
        animation: circuit-card-in 0.4s ease backwards;
    }
    .circuit-hotel-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 24px rgba(0,0,0,.1);
    }
    .circuit-hotel-card .card-body { padding: 1.25rem; }
    .circuit-hotel-card .card-title {
        font-size: 1.05rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }
    .circuit-hotel-card .circuit-meta {
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 0.75rem;
    }
    .circuit-hotel-card .hotel-name {
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    .circuit-hotel-card .card-actions {
        padding-top: 0.75rem;
        border-top: 1px solid rgba(0,0,0,.06);
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    @keyframes circuit-card-in {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .circuit-hotel-card:nth-child(1) { animation-delay: 0.05s; }
    .circuit-hotel-card:nth-child(2) { animation-delay: 0.1s; }
    .circuit-hotel-card:nth-child(3) { animation-delay: 0.15s; }
    .circuit-hotel-card:nth-child(4) { animation-delay: 0.2s; }
    .circuit-hotel-card:nth-child(5) { animation-delay: 0.25s; }
    .circuit-hotel-card:nth-child(6) { animation-delay: 0.3s; }
    .circuit-hotel-card:nth-child(n+7) { animation-delay: 0.35s; }
</style>
@endpush

@section('content')
    <div class="row mb-3">
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

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(!empty($wpConnectionFailed))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Connexion WordPress indisponible.</strong> Vérifiez la configuration de la base WP.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="mb-0">
                <div class="row g-2 align-items-end">
                    <div class="col-auto flex-grow-1">
                        <label class="form-label small">Recherche</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Titre du circuit..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($tours->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bx bx-trip display-4 d-block mb-2"></i>
                Aucun circuit. <a href="{{ route('admin.circuits.voyages.index') }}">Gérer les voyages</a> puis revenir ici pour définir les hôtels.
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($tours as $index => $tour)
                @php $hotel = $hotelsByTour[$tour->ID] ?? null; @endphp
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="card circuit-hotel-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-2">
                                <span class="badge bg-primary me-2">Voyage #{{ $tour->ID }}</span>
                                <h5 class="card-title mb-0 flex-grow-1">
                                    <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="text-body text-decoration-none">{{ \Str::limit($tour->post_title, 45) }}</a>
                                </h5>
                            </div>
                            <div class="circuit-meta mb-2">
                                <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="text-muted small">Modifier le voyage</a>
                                <span class="text-muted small mx-1">·</span>
                                <a href="{{ route('admin.circuits.voyages.show', $tour->ID) }}" class="text-muted small">Fiche voyage</a>
                            </div>
                            @if($hotel && $hotel->hotel_name)
                                <div class="hotel-name">
                                    <i class="bx bxs-hotel text-primary me-1"></i>{{ $hotel->hotel_name }}
                                    @if($hotel->stars)
                                        <span class="text-warning small">★{{ $hotel->stars }}</span>
                                    @endif
                                </div>
                                @if($hotel->address)
                                    <p class="small text-muted mb-0">{{ \Str::limit($hotel->address, 50) }}</p>
                                @endif
                                @if(isset($hotel->rooms_count) && $hotel->rooms_count > 0)
                                    <span class="badge bg-light text-dark border mt-1">{{ $hotel->rooms_count }} type(s) de chambre</span>
                                @endif
                            @else
                                <p class="text-muted small mb-0"><i class="bx bx-info-circle me-1"></i>Hôtel non renseigné</p>
                            @endif
                            <div class="card-actions">
                                @if($hotel)
                                    <a href="{{ route('admin.circuits.tour-hotels.show', $tour->ID) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                                @endif
                                <a href="{{ route('admin.circuits.tour-hotels.edit', $tour->ID) }}" class="btn btn-sm btn-outline-secondary">Gérer l'hôtel</a>
                                <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="btn btn-sm btn-soft-primary">Voyage</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($tours->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $tours->links() }}
            </div>
        @endif
    @endif
@endsection
