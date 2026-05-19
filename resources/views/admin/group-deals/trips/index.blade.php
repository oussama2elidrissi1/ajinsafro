@extends('layouts.admin-v6')
@section('title') Group Deals â€” Voyages @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="page-title mb-0 font-size-18">Group Deals â€” Voyages</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item active">Group Deals</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- Filtres --}}
        <form method="get" class="row g-2 align-items-end mb-4">
            <div class="col-md-4">
                <label class="form-label small mb-1">Recherche</label>
                <input type="search" name="q" class="form-control form-control-sm"
                       value="{{ request('q') }}" placeholder="Nom du voyageâ€¦">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                <a href="{{ route('admin.group-deals.trips.index') }}" class="btn btn-outline-secondary btn-sm ms-1">RÃ©initialiser</a>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">{{ $voyages->total() }} voyage(s) avec Group Deal activÃ©</h5>
            <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bx bx-link-external me-1"></i> GÃ©rer les voyages
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Visuel</th>
                        <th>Voyage</th>
                        <th>Destination</th>
                        <th class="text-center">Paliers</th>
                        <th class="text-center">DÃ©parts GD</th>
                        <th class="text-center">Garantis</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($voyages as $voyage)
                    @php
                        $gdDepartures = $voyage->departures;
                        $guaranteed   = $gdDepartures->where('is_guaranteed', true)->count();
                    @endphp
                    <tr>
                        <td>
                            <div class="aj-thumb">
                                @if($voyage->featured_image_url)
                                    <img src="{{ $voyage->featured_image_url }}" alt="{{ $voyage->name }}" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                                @else
                                    <div class="aj-thumb-placeholder" style="width:48px;height:48px;border-radius:6px;">Ajinsafro</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <strong>{{ $voyage->name }}</strong>
                            @if($voyage->price_from)
                                <br><small class="text-muted">Ã  partir de {{ number_format($voyage->price_from, 0, ',', ' ') }} â‚¬</small>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $voyage->destination ?? 'â€”' }}</td>
                        <td class="text-center">
                            @if($voyage->pricingTiers->count())
                                <span class="badge bg-info text-dark">{{ $voyage->pricingTiers->count() }} palier(s)</span>
                            @else
                                <span class="badge bg-warning text-dark">Aucun</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $gdDepartures->count() }}</td>
                        <td class="text-center">
                            @if($guaranteed > 0)
                                <span class="badge bg-success">{{ $guaranteed }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>
                            @php $s = $voyage->status ?? 'publish'; @endphp
                            <span class="badge bg-{{ $s === 'publish' ? 'success' : ($s === 'draft' ? 'secondary' : 'warning text-dark') }}">
                                {{ $s === 'publish' ? 'PubliÃ©' : ($s === 'draft' ? 'Brouillon' : $s) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.group-deals.trips.show', $voyage) }}"
                               class="btn btn-sm btn-outline-primary">GÃ©rer</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Aucun voyage avec Group Deal activÃ©.
                            <a href="{{ route('admin.circuits.voyages.index') }}">Activer le Group Deal sur un voyage</a>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($voyages->hasPages())
            <div class="mt-3">{{ $voyages->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

