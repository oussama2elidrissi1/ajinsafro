@extends('layouts.admin-v6')
@section('title', 'HÃ´tel du circuit â€” ' . $tour->post_title)

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">HÃ´tel du circuit</h4>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.circuits.tour-hotels.edit', $tour->ID) }}" class="btn btn-outline-primary btn-sm">Modifier</a>
                    <a href="{{ route('admin.circuits.tour-hotels.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
                </div>
            </div>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.circuits.tour-hotels.index') }}">HÃ´tels</a></li>
                <li class="breadcrumb-item active">{{ \Str::limit($tour->post_title, 40) }}</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- Voyage liÃ© --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light d-flex align-items-center">
                    <h5 class="mb-0">Voyage liÃ©</h5>
                    <span class="badge bg-primary ms-auto">ID voyage {{ $tour->ID }}</span>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>{{ $tour->post_title }}</strong>
                    </p>
                    <p class="small text-muted mb-2">Cet hÃ´tel circuit est rattachÃ© Ã  ce voyage (tour_id = {{ $tour->ID }}).</p>
                    <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="btn btn-soft-primary btn-sm me-1">Modifier le voyage</a>
                    <a href="{{ route('admin.circuits.voyages.show', $tour->ID) }}" class="btn btn-outline-secondary btn-sm">Voir la fiche voyage</a>
                </div>
            </div>

            @if($hotel)
                {{-- Infos hÃ´tel --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light d-flex align-items-center">
                        <h5 class="mb-0">Informations hÃ´tel</h5>
                        @if($hotel->stars)
                            <span class="badge bg-warning text-dark ms-auto">â˜… {{ $hotel->stars }} Ã©toiles</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr><td class="text-nowrap pe-3 fw-medium text-muted" style="width:140px;">ID hÃ´tel circuit</td><td><code>{{ $hotel->id }}</code></td></tr>
                                <tr><td class="pe-3 fw-medium text-muted">Nom</td><td>{{ $hotel->hotel_name ?? 'â€”' }}</td></tr>
                                <tr><td class="pe-3 fw-medium text-muted">Adresse</td><td>{{ $hotel->address ?? 'â€”' }}</td></tr>
                                <tr><td class="pe-3 fw-medium text-muted">Type de chambre</td><td>{{ $hotel->room_type ?? 'â€”' }}</td></tr>
                                <tr><td class="pe-3 fw-medium text-muted">Formule repas</td><td>{{ $hotel->meal_plan ?? 'â€”' }}</td></tr>
                                @if($hotel->check_in_day !== null || $hotel->check_out_day !== null)
                                    <tr><td class="pe-3 fw-medium text-muted">Jours</td><td>Check-in jour {{ $hotel->check_in_day ?? 'â€”' }} / Check-out jour {{ $hotel->check_out_day ?? 'â€”' }}</td></tr>
                                @endif
                                <tr><td class="pe-3 fw-medium text-muted">Optionnel</td><td>{{ $hotel->is_optional ? 'Oui' : 'Non' }}</td></tr>
                                <tr><td class="pe-3 fw-medium text-muted">Ordre</td><td>{{ $hotel->sort_order ?? 0 }}</td></tr>
                            </tbody>
                        </table>
                        @if($hotel->notes)
                            <hr>
                            <p class="mb-0 small"><strong>Notes :</strong> {{ $hotel->notes }}</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="card shadow-sm mb-3 border-warning">
                    <div class="card-body text-center text-muted py-4">
                        <i class="bx bxs-hotel display-6 d-block mb-2"></i>
                        Aucun hÃ´tel renseignÃ© pour ce circuit.
                        <a href="{{ route('admin.circuits.tour-hotels.edit', $tour->ID) }}" class="btn btn-primary btn-sm mt-2">Renseigner l'hÃ´tel</a>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-lg-4">
            @if($hotel)
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">RÃ©sumÃ©</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>Voyage</strong> : {{ \Str::limit($tour->post_title, 35) }}</li>
                            <li><strong>ID voyage</strong> : {{ $tour->ID }}</li>
                            <li><strong>HÃ´tel</strong> : {{ $hotel->hotel_name ?: 'â€”' }}</li>
                            <li><strong>Types de chambres</strong> : {{ $hotel->rooms->count() }}</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Tableau des types de chambres --}}
    @if($hotel && $hotel->rooms->isNotEmpty())
        <div class="row mt-2">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Types de chambres</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>LibellÃ©</th>
                                        <th>Code</th>
                                        <th>QuantitÃ©</th>
                                        <th>CapacitÃ©</th>
                                        <th>SupplÃ©ment</th>
                                        <th>Description</th>
                                        <th>DÃ©faut</th>
                                        <th>Actif</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hotel->rooms as $room)
                                        <tr>
                                            <td><strong>{{ $room->room_type }}</strong></td>
                                            <td>{{ $room->room_label ?? 'â€”' }}</td>
                                            <td><code class="small">{{ $room->room_code ?? 'â€”' }}</code></td>
                                            <td>{{ $room->room_count }}</td>
                                            <td>{{ $room->capacity_adults }}A / {{ $room->capacity_children }}E ({{ $room->capacity_total }} total)</td>
                                            <td>{{ $room->supplement ? number_format((float) $room->supplement, 0, ',', ' ') . ' DH' : 'â€”' }}</td>
                                            <td class="small text-muted" style="max-width:180px;">{{ $room->description ? \Str::limit($room->description, 50) : 'â€”' }}</td>
                                            <td>@if($room->is_default)<span class="badge bg-success">Oui</span>@else<span class="text-muted">â€”</span>@endif</td>
                                            <td>@if($room->is_active)<span class="badge bg-success">Oui</span>@else<span class="badge bg-secondary">Non</span>@endif</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <p class="small text-muted mt-2 mb-0">La gestion dÃ©taillÃ©e des chambres se fait dans lâ€™Ã©dition du voyage (onglet programme / hÃ´tels).</p>
            </div>
        </div>
    @endif
@endsection

