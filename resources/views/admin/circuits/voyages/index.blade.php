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
                    <h1 class="vi-page-title">Voyages WordPress</h1>
                    <p class="vi-page-subtitle">Gestion centralisée des tours synchronisés avec WordPress</p>
                </div>
                <a href="{{ route('admin.circuits.voyages.create') }}" class="btn btn-primary waves-effect waves-light vi-header-cta">
                    <i class="bx bx-plus me-1"></i> Créer un tour
                </a>
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
                <strong>Connexion WordPress indisponible.</strong>
                Vérifiez <code>WP_DB_*</code> dans <code>.env</code> (nom de base exact, hôte, identifiants), puis sur le serveur :
                <code>php artisan config:clear</code> et <code>php artisan cache:clear</code> — une config en cache peut conserver d’anciennes valeurs.
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
                <div class="vi-info-banner mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bx bx-info-circle"></i>
                        <div>
                            <strong>CRUD Direct WordPress</strong> - {{ $tours->total() }} tours affichés depuis la DB WordPress.
                            Modifications immédiatement visibles sur <a href="https://ajinsafro.net" target="_blank" class="alert-link">ajinsafro.net</a>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h4 class="card-title mb-0">Liste des tours WordPress (st_tours)</h4>
                    <span class="vi-count-chip"><i class="bx bx-collection me-1"></i>{{ $tours->total() }} résultat(s)</span>
                </div>

                <form method="get" action="{{ route('admin.circuits.voyages.index') }}" class="vi-filters card border mb-4">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-6 col-md-2">
                                <label class="form-label small mb-1">Statut</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <option value="publish" @selected(request('status') === 'publish')>Publié</option>
                                    <option value="draft" @selected(request('status') === 'draft')>Brouillon</option>
                                    <option value="private" @selected(request('status') === 'private')>Archivé</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small mb-1">Type / thème</label>
                                <select name="tour_type" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    @foreach($filterTourTypes ?? [] as $tt)
                                        <option value="{{ $tt->term_id }}" @selected((string) request('tour_type') === (string) $tt->term_id)>{{ $tt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Destination</label>
                                <input type="text" name="destination" class="form-control form-control-sm" value="{{ request('destination') }}" placeholder="Ville, pays…">
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label small mb-1">Prix min</label>
                                <input type="number" step="0.01" name="price_min" class="form-control form-control-sm" value="{{ request('price_min') }}">
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label small mb-1">Prix max</label>
                                <input type="number" step="0.01" name="price_max" class="form-control form-control-sm" value="{{ request('price_max') }}">
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label small mb-1">Durée min</label>
                                <input type="number" min="1" name="duration_min" class="form-control form-control-sm" value="{{ request('duration_min') }}">
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label small mb-1">Durée max</label>
                                <input type="number" min="1" name="duration_max" class="form-control form-control-sm" value="{{ request('duration_max') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Modifié du</label>
                                <input type="date" name="modified_from" class="form-control form-control-sm" value="{{ request('modified_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">au</label>
                                <input type="date" name="modified_to" class="form-control form-control-sm" value="{{ request('modified_to') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Recherche</label>
                                <input type="search" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Titre, slug…">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small mb-1">Départs actifs</label>
                                <select name="has_departures" class="form-select form-select-sm">
                                    <option value="">Indifférent</option>
                                    <option value="1" @selected(request('has_departures') === '1')>Oui</option>
                                    <option value="0" @selected(request('has_departures') === '0')>Non</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small mb-1">Page Laravel publique</label>
                                <select name="has_laravel_public" class="form-select form-select-sm">
                                    <option value="">Indifférent</option>
                                    <option value="1" @selected(request('has_laravel_public') === '1')>Oui</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-auto d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>
                                <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
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
                                    <th>Status</th>
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
                                        <td>{{ $tour->post_modified->format('d/m/Y H:i') }}</td>
                                        <td class="text-end vi-actions">
                                            <a href="https://ajinsafro.net/tours/{{ $tour->post_name }}" target="_blank" class="btn btn-sm btn-soft-info waves-effect waves-light me-1" title="Voir sur WordPress">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            @if(!empty($tour->laravel_slug))
                                                <a href="{{ url('/voyages/'.$tour->laravel_slug) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-soft-success waves-effect waves-light me-1" title="Voir la page client">
                                                    <i class="bx bx-link-external"></i>
                                                </a>
                                            @endif
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
