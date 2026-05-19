@extends('layouts.admin-v6')
@section('title', 'HÃ´tels')

@push('styles')
<style>
    .hotel-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        border: 1px solid rgba(0,0,0,.06);
        overflow: hidden;
        animation: hotel-card-in 0.4s ease backwards;
    }
    .hotel-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 24px rgba(0,0,0,.1);
    }
    .hotel-card .card-img-wrap {
        height: 160px;
        background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
    }
    .hotel-card .card-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .hotel-card .card-body {
        padding: 1rem 1.25rem;
    }
    .hotel-card .card-title {
        font-size: 1.05rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
        line-height: 1.3;
    }
    .hotel-card .hotel-meta {
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 0.75rem;
    }
    .hotel-card .hotel-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-bottom: 0.75rem;
    }
    .hotel-card .card-actions {
        padding-top: 0.75rem;
        border-top: 1px solid rgba(0,0,0,.06);
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    @keyframes hotel-card-in {
        from {
            opacity: 0;
            transform: translateY(12px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .hotel-card:nth-child(1) { animation-delay: 0.05s; }
    .hotel-card:nth-child(2) { animation-delay: 0.1s; }
    .hotel-card:nth-child(3) { animation-delay: 0.15s; }
    .hotel-card:nth-child(4) { animation-delay: 0.2s; }
    .hotel-card:nth-child(5) { animation-delay: 0.25s; }
    .hotel-card:nth-child(6) { animation-delay: 0.3s; }
    .hotel-card:nth-child(n+7) { animation-delay: 0.35s; }
</style>
@endpush

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">HÃ´tels</h4>
                <a href="{{ route('admin.hotels.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> Nouvel hÃ´tel
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="mb-0">
                <div class="row g-2 align-items-end">
                    <div class="col-auto flex-grow-1">
                        <label class="form-label small">Recherche</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Nom, ville, pays..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-auto">
                        <label class="form-label small">Statut</label>
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="">Tous</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Actifs</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactifs</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        @forelse($hotels as $index => $hotel)
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="card hotel-card h-100">
                    <div class="card-img-wrap">
                        @if($hotel->main_image_path)
                            <img src="{{ asset('storage/'.$hotel->main_image_path) }}" alt="{{ $hotel->name }}">
                        @else
                            <i class="bx bxs-hotel" style="font-size: 3rem;"></i>
                        @endif
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">{{ $hotel->name }}</h5>
                        <div class="hotel-meta">
                            @if($hotel->city || $hotel->country)
                                <span><i class="bx bx-map-pin me-1"></i>{{ trim(implode(', ', array_filter([$hotel->city, $hotel->country]))) ?: 'â€”' }}</span>
                            @else
                                <span>â€”</span>
                            @endif
                        </div>
                        <div class="hotel-badges">
                            @if($hotel->rating_average > 0)
                                <span class="badge bg-warning text-dark"><i class="bx bx-star me-1"></i>{{ $hotel->rating_average }}/5</span>
                                <span class="badge bg-light text-dark border">{{ $hotel->reviews_count }} avis</span>
                            @endif
                            <span class="badge bg-light text-dark border"><i class="bx bx-bed me-1"></i>{{ $hotel->room_types_count ?? 0 }} type(s)</span>
                            <span class="badge bg-{{ $hotel->is_active ? 'success' : 'secondary' }}">{{ $hotel->is_active ? 'Actif' : 'Inactif' }}</span>
                        </div>
                        <div class="card-actions">
                            <a href="{{ route('admin.hotels.show', $hotel) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                            <a href="{{ route('admin.hotels.edit', $hotel) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                            <form action="{{ route('admin.hotels.destroy', $hotel) }}" method="post" class="d-inline"
                                  onsubmit="return confirm('Supprimer cet hÃ´tel ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center text-muted py-5">
                        <i class="bx bxs-hotel display-4 d-block mb-2"></i>
                        Aucun hÃ´tel.
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if(method_exists($hotels, 'links') && $hotels->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $hotels->links() }}
        </div>
    @endif
@endsection

