@extends('layouts.master-ajinsafro')
@section('title')
    Départs & Dates
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h4 class="page-title mb-0 font-size-18">Gestion Départs, Capacités & Chambres</h4>
                    <p class="text-muted mb-0 small">Pilotage métier des départs: dates, places, disponibilité et cohérence des chambres.</p>
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

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label mb-1">Recherche voyage</label>
                    <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Nom du voyage">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Statut départ</label>
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" @selected($status === $st)>{{ \App\Models\Departure::make(['status' => $st])->status_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    <a href="{{ route('admin.circuits.departs-dates') }}" class="btn btn-light w-100">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    @forelse($voyages as $voyage)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">{{ $voyage->name }}</h5>
                    <small class="text-muted">{{ $voyage->departures->count() }} départ(s)</small>
                </div>
                <a href="{{ route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id) }}#availability" class="btn btn-sm btn-outline-secondary">Ouvrir la fiche voyage</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 departs-table">
                        <thead class="table-light">
                            <tr>
                                <th>Départ</th>
                                <th>Capacité</th>
                                <th>Réservées</th>
                                <th>Disponibles</th>
                                <th>Statut</th>
                                <th>Chambres (synthèse)</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($voyage->departures as $departure)
                                @php
                                    $reserved = (int) ($departure->reserved_passengers_sum ?? 0);
                                    $total = (int) ($departure->total_capacity ?? 0);
                                    $available = max(0, $total - $reserved);
                                    $hotels = $departure->departureHotels ?? collect();
                                    $rooms = $hotels->flatMap(fn($h) => $h->rooms ?? collect());
                                    $roomTotalPlaces = (int) $rooms->sum('total_places');
                                    $roomAvailablePlaces = (int) $rooms->sum('available_places');
                                    $roomReservedPlaces = (int) $rooms->sum('reserved_places');
                                    $isRoomMismatch = $roomTotalPlaces > 0 && $roomTotalPlaces !== $total;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ optional($departure->start_date)->format('d/m/Y') }}</div>
                                        <div class="text-muted small">Retour: {{ $departure->end_date ? optional($departure->end_date)->format('d/m/Y') : '—' }}</div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $total }}</span>
                                        @if($isRoomMismatch)
                                            <div class="small text-warning">Stock chambres: {{ $roomTotalPlaces }}</div>
                                        @endif
                                    </td>
                                    <td><span class="fw-semibold">{{ $reserved }}</span></td>
                                    <td><span class="fw-semibold {{ $available <= 0 ? 'text-danger' : 'text-success' }}">{{ $available }}</span></td>
                                    <td>
                                        @php
                                            $badgeClass = match($departure->status) {
                                                'open' => 'bg-success',
                                                'limited' => 'bg-warning text-dark',
                                                'full' => 'bg-danger',
                                                'closed' => 'bg-secondary',
                                                'canceled', 'cancelled' => 'bg-dark',
                                                default => 'bg-light text-dark',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $departure->status_label }}</span>
                                    </td>
                                    <td>
                                        @if($rooms->isEmpty())
                                            <span class="text-muted small">Aucune chambre</span>
                                        @else
                                            <div class="small">{{ $rooms->count() }} type(s)</div>
                                            <div class="small text-muted">Total {{ $roomTotalPlaces }} · Réservées {{ $roomReservedPlaces }} · Dispo {{ $roomAvailablePlaces }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.circuits.voyages.departures.show', [$voyage, $departure]) }}" class="btn btn-sm btn-soft-primary mb-1">Gérer chambres</a>
                                        <button class="btn btn-sm btn-outline-secondary mb-1" type="button" data-bs-toggle="collapse" data-bs-target="#edit-departure-{{ $departure->id }}">Modifier</button>
                                        <form action="{{ route('admin.circuits.voyages.departures.destroy', [$voyage, $departure]) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce départ ?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger mb-1">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="collapse" id="edit-departure-{{ $departure->id }}">
                                    <td colspan="7" class="bg-light">
                                        <form class="row g-2 align-items-end" action="{{ route('admin.circuits.voyages.departures.update', [$voyage, $departure]) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Date départ</label>
                                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ optional($departure->start_date)->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Date retour</label>
                                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ optional($departure->end_date)->format('Y-m-d') }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Statut</label>
                                                <select name="status" class="form-select form-select-sm" required>
                                                    @foreach($statuses as $st)
                                                        <option value="{{ $st }}" @selected($departure->status === $st)>{{ \App\Models\Departure::make(['status' => $st])->status_label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Capacité totale</label>
                                                <input type="number" min="0" name="total_capacity" class="form-control form-control-sm" value="{{ (int) $departure->total_capacity }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Prix de base</label>
                                                <input type="number" step="0.01" min="0" name="base_price" class="form-control form-control-sm" value="{{ $departure->base_price }}">
                                            </div>
                                            <div class="col-md-2 text-end">
                                                <button type="submit" class="btn btn-sm btn-primary w-100">Enregistrer</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Aucun départ trouvé pour ce voyage.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border rounded bg-light p-3 mt-3">
                    <h6 class="small text-uppercase text-muted mb-2">Ajouter un départ</h6>
                    <form action="{{ route('admin.circuits.voyages.departures.store', $voyage) }}" method="POST" class="row g-2 align-items-end">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Date départ</label>
                            <input type="date" class="form-control form-control-sm" name="start_date" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Date retour</label>
                            <input type="date" class="form-control form-control-sm" name="end_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Statut</label>
                            <select class="form-select form-select-sm" name="status" required>
                                <option value="open">Ouvert</option>
                                <option value="limited">Limité</option>
                                <option value="closed">Fermé</option>
                                <option value="draft">Brouillon</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Capacité totale</label>
                            <input type="number" min="0" class="form-control form-control-sm" name="total_capacity" value="0" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Prix de base</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="base_price" placeholder="Optionnel">
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="submit" class="btn btn-sm btn-primary w-100">Ajouter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                Aucun voyage avec départ trouvé.
            </div>
        </div>
    @endforelse

    <div class="mt-3 d-flex justify-content-center">
        {{ $voyages->links() }}
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
