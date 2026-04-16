@extends('layouts.master-ajinsafro')
@section('title')
    Voyages
@endsection
@push('css')
    <link href="{{ URL::asset('css/voyage-index.css') }}" rel="stylesheet" type="text/css" />
@endpush
@section('content')
    <div class="voyage-index-page">
        <div class="vi-page-header">
            <ul class="vi-breadcrumb">
                <li><a href="{{ route('admin.dashboard') }}"><i class="bx bx-home-alt"></i> Admin</a></li>
                <li><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                <li class="active">Voyages</li>
            </ul>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="vi-page-title">Catalogue des voyages</h1>
                    <p class="vi-page-subtitle">Pilotez vos offres, disponibilités et actions commerciales depuis une vue unique.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.circuits.voyages.create') }}" class="btn btn-outline-primary waves-effect waves-light vi-header-cta">
                        <i class="bx bx-plus me-1"></i> Créer un tour
                    </a>
                    <a href="{{ route('admin.circuits.voyages.create-v2') }}" class="btn btn-primary waves-effect waves-light vi-header-cta">
                        <i class="bx bx-plus me-1"></i> Créer en V2
                    </a>
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
                <strong>Connexion catalogue indisponible.</strong>
                Le chargement des voyages est temporairement indisponible. Veuillez réessayer dans quelques instants.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (!empty($wpCatalogErrorMessage))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Chargement de la liste impossible.</strong> {{ $wpCatalogErrorMessage }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card vi-main-card">
            <div class="card-body">
                <div class="vi-stats-grid mb-4">
                    <div class="vi-stat-card">
                        <span class="vi-stat-label">Voyages trouvés</span>
                        <strong class="vi-stat-value">{{ $catalogSummary['total'] ?? $tours->total() }}</strong>
                    </div>
                    <div class="vi-stat-card">
                        <span class="vi-stat-label">Publié</span>
                        <strong class="vi-stat-value">{{ $catalogSummary['published'] ?? 0 }}</strong>
                    </div>
                    <div class="vi-stat-card">
                        <span class="vi-stat-label">Brouillon</span>
                        <strong class="vi-stat-value">{{ $catalogSummary['draft'] ?? 0 }}</strong>
                    </div>
                    <div class="vi-stat-card">
                        <span class="vi-stat-label">Avec départs actifs</span>
                        <strong class="vi-stat-value">{{ $catalogSummary['with_departures'] ?? 0 }}</strong>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h4 class="card-title mb-0">Liste des voyages</h4>
                    <span class="vi-count-chip"><i class="bx bx-collection me-1"></i>{{ $tours->total() }} résultat(s) affiché(s)</span>
                </div>

                <form method="get" action="{{ route('admin.circuits.voyages.index') }}" class="vi-filters card border mb-4">
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-3 col-xl-2">
                                <label class="form-label small mb-1">Statut</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <option value="publish" @selected(request('status') === 'publish')>Publié</option>
                                    <option value="draft" @selected(request('status') === 'draft')>Brouillon</option>
                                    <option value="private" @selected(request('status') === 'private')>Archivé</option>
                                    <option value="pending" @selected(request('status') === 'pending')>En attente</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3 col-xl-2">
                                <label class="form-label small mb-1">Type / thème</label>
                                <select name="tour_type" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    @foreach($filterTourTypes ?? [] as $tt)
                                        <option value="{{ $tt['term_id'] }}" @selected((string) request('tour_type') === (string) $tt['term_id'])>{{ $tt['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3 col-xl-2">
                                <label class="form-label small mb-1">Destination</label>
                                <input type="text" name="destination" class="form-control form-control-sm" value="{{ request('destination') }}" placeholder="Ville, pays…">
                            </div>
                            <div class="col-6 col-md-3 col-xl-1">
                                <label class="form-label small mb-1">Prix min</label>
                                <input type="number" step="0.01" name="price_min" class="form-control form-control-sm" value="{{ request('price_min') }}">
                            </div>
                            <div class="col-6 col-md-3 col-xl-1">
                                <label class="form-label small mb-1">Prix max</label>
                                <input type="number" step="0.01" name="price_max" class="form-control form-control-sm" value="{{ request('price_max') }}">
                            </div>
                            <div class="col-6 col-md-3 col-xl-1">
                                <label class="form-label small mb-1">Durée min</label>
                                <input type="number" min="1" name="duration_min" class="form-control form-control-sm" value="{{ request('duration_min') }}">
                            </div>
                            <div class="col-6 col-md-3 col-xl-1">
                                <label class="form-label small mb-1">Durée max</label>
                                <input type="number" min="1" name="duration_max" class="form-control form-control-sm" value="{{ request('duration_max') }}">
                            </div>
                            <div class="col-12 col-md-3 col-xl-2">
                                <label class="form-label small mb-1">Modifié du</label>
                                <input type="date" name="modified_from" class="form-control form-control-sm" value="{{ request('modified_from') }}">
                            </div>
                            <div class="col-12 col-md-3 col-xl-2">
                                <label class="form-label small mb-1">au</label>
                                <input type="date" name="modified_to" class="form-control form-control-sm" value="{{ request('modified_to') }}">
                            </div>
                            <div class="col-12 col-md-4 col-xl-3">
                                <label class="form-label small mb-1">Recherche</label>
                                <input type="search" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Titre, slug…">
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <label class="form-label small mb-1">Départs actifs</label>
                                <select name="has_departures" class="form-select form-select-sm">
                                    <option value="">Indifférent</option>
                                    <option value="1" @selected(request('has_departures') === '1')>Oui</option>
                                    <option value="0" @selected(request('has_departures') === '0')>Non</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <label class="form-label small mb-1">Page Laravel publique</label>
                                <select name="has_laravel_public" class="form-select form-select-sm">
                                    <option value="">Indifférent</option>
                                    <option value="1" @selected(request('has_laravel_public') === '1')>Oui</option>
                                    <option value="0" @selected(request('has_laravel_public') === '0')>Non</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4 col-xl-3">
                                <div class="d-flex gap-2 vi-filter-actions">
                                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filtrer</button>
                                    <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-sm btn-outline-secondary flex-grow-1">Réinitialiser</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                @if($tours->isEmpty())
                    <p class="text-muted mb-0">Aucun tour trouvé. <a href="{{ route('admin.circuits.voyages.create') }}">Créer un tour</a> pour commencer.</p>
                @else
                    <div class="table-responsive vi-table-wrap">
                        <table class="table table-hover table-centered mb-0 vi-table">
                            <thead>
                                <tr>
                                    <th width="70">ID</th>
                                    <th>Titre</th>
                                    <th>Destination</th>
                                    <th>Durée</th>
                                    <th>Prix Adulte</th>
                                    <th>Statut</th>
                                    <th>Modifié le</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tours as $tour)
                                    <tr>
                                        <td><strong>#{{ $tour->ID }}</strong></td>
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
                                                <span class="badge vi-status vi-status-publish">Publié</span>
                                            @elseif($tour->post_status === 'draft')
                                                <span class="badge vi-status vi-status-draft">Brouillon</span>
                                            @elseif($tour->post_status === 'private')
                                                <span class="badge bg-secondary">Archivé</span>
                                            @else
                                                <span class="badge vi-status vi-status-pending">{{ $tour->post_status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($tour->post_modified)->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td class="text-end vi-actions">
                                            <a href="https://ajinsafro.net/tours/{{ $tour->post_name }}" target="_blank" class="btn btn-sm btn-soft-info waves-effect waves-light me-1" title="Voir la fiche publique">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            @if(!empty($tour->laravel_slug))
                                                <a href="{{ url('/voyages/'.$tour->laravel_slug) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-soft-success waves-effect waves-light me-1" title="Voir la page commerciale">
                                                    <i class="bx bx-link-external"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="btn btn-sm btn-soft-primary waves-effect waves-light me-1">Modifier</a>
                                            <a href="{{ route('admin.circuits.voyages.edit-v2', $tour->ID) }}" class="btn btn-sm btn-soft-secondary waves-effect waves-light me-1" title="Ouvrir dans l'éditeur V2">V2</a>
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

                    <div class="d-flex justify-content-center mt-4">
                        {{ $tours->onEachSide(1)->links('pagination::bootstrap-5') }}
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
