@extends('layouts.admin-v2')
@section('title', 'Détail hôtel')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">{{ $hotel->name }}</h4>
                <div>
                    <a href="{{ route('admin.hotels.edit', $hotel) }}" class="btn btn-outline-primary btn-sm">Modifier</a>
                    <a href="{{ route('admin.hotels.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- Fiche hôtel : infos complètes --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light d-flex align-items-center">
                    <h5 class="mb-0">Informations générales</h5>
                    <span class="badge bg-{{ $hotel->is_active ? 'success' : 'secondary' }} ms-auto">
                        {{ $hotel->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3 align-items-start">
                        @if($hotel->main_image_path)
                            <img src="{{ asset('storage/'.$hotel->main_image_path) }}" alt=""
                                 class="rounded flex-shrink-0" style="width:180px;height:120px;object-fit:cover;">
                        @endif
                        <div class="flex-grow-1 min-w-0">
                            <h4 class="mb-2">{{ $hotel->name }}</h4>
                            <table class="table table-sm table-borderless mb-0 text-muted small">
                                <tbody>
                                    @if($hotel->address)
                                        <tr><td class="text-nowrap pe-2 fw-medium text-dark">Adresse</td><td>{{ $hotel->address }}</td></tr>
                                    @endif
                                    <tr><td class="text-nowrap pe-2 fw-medium text-dark">Ville</td><td>{{ $hotel->city ?? '—' }}</td></tr>
                                    <tr><td class="text-nowrap pe-2 fw-medium text-dark">Pays</td><td>{{ $hotel->country ?? '—' }}</td></tr>
                                    @if($hotel->latitude && $hotel->longitude)
                                        <tr><td class="text-nowrap pe-2 fw-medium text-dark">Coordonnées</td><td>{{ $hotel->latitude }}, {{ $hotel->longitude }}</td></tr>
                                    @endif
                                    <tr><td class="text-nowrap pe-2 fw-medium text-dark">Note</td><td>
                                        @if($hotel->rating_average > 0)
                                            <span class="badge bg-warning text-dark">{{ $hotel->rating_average }}/5</span>
                                            <span class="ms-1">({{ $hotel->reviews_count }} avis)</span>
                                        @else
                                            Aucune note
                                        @endif
                                    </td></tr>
                                    <tr><td class="text-nowrap pe-2 fw-medium text-dark">Galerie</td><td>{{ $hotel->images->count() }} image(s)</td></tr>
                                    <tr><td class="text-nowrap pe-2 fw-medium text-dark">Types de chambres</td><td>{{ $hotel->roomTypes->count() }} type(s)</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($hotel->description)
                        <hr>
                        <p class="mb-0">{{ $hotel->description }}</p>
                    @endif
                </div>
            </div>

            @if($hotel->images->isNotEmpty())
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Galerie</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @foreach($hotel->images as $img)
                                <div class="col-md-3 col-4">
                                    <img src="{{ asset('storage/'.$img->file_path) }}" alt="" class="rounded w-100"
                                         style="height:90px;object-fit:cover;">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if($hotel->amenities->isNotEmpty())
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Équipements</h5>
                    </div>
                    <div class="card-body">
                        @foreach($hotel->amenities as $amenity)
                            <span class="badge bg-light text-dark border me-1 mb-1">
                                @if($amenity->icon)<i class="{{ $amenity->icon }} me-1"></i>@endif
                                {{ $amenity->label }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            @if($hotel->reviews->isNotEmpty())
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Derniers avis</h5>
                    </div>
                    <div class="card-body">
                        @foreach($hotel->reviews as $review)
                            <div class="mb-2">
                                <span class="badge bg-warning text-dark">{{ $review->rating }}/5</span>
                                <span class="text-muted small ms-1">{{ $review->author_name ?? 'Client' }}</span>
                                @if($review->comment)
                                    <div class="small mt-1">{{ $review->comment }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Tableau détaillé des types de chambres --}}
    @if($hotel->roomTypes->isNotEmpty())
        <div class="row mt-2">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Types de chambres – détail</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Code</th>
                                        <th>Capacité</th>
                                        <th>Quantité</th>
                                        <th>Prix</th>
                                        <th>Description</th>
                                        <th>Options chambre</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hotel->roomTypes as $rt)
                                        <tr>
                                            <td><strong>{{ $rt->name }}</strong></td>
                                            <td><code class="small">{{ $rt->code ?? '—' }}</code></td>
                                            <td>{{ $rt->capacity_adults }} adulte(s) / {{ $rt->capacity_children }} enfant(s)</td>
                                            <td>{{ $rt->quantity }}</td>
                                            <td>
                                                @if($rt->base_price !== null)
                                                    {{ number_format((float) $rt->base_price, 0, ',', ' ') }} {{ $rt->currency ?? 'MAD' }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="small text-muted" style="max-width:200px;">
                                                {{ $rt->description ? \Str::limit($rt->description, 60) : '—' }}
                                            </td>
                                            <td class="small">
                                                @if(is_array($rt->amenities) && count($rt->amenities) > 0)
                                                    @foreach($rt->amenities as $opt)
                                                        <span class="badge bg-light text-dark border me-1">{{ is_array($opt) ? ($opt['label'] ?? $opt['name'] ?? json_encode($opt)) : $opt }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
