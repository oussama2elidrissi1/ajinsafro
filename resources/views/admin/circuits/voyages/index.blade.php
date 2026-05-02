@extends('layouts.master-ajinsafro')

@php
    use Illuminate\Support\Str;

    $pageTitle = 'Catalogue des voyages';
    $totalTours = $catalogSummary['total'] ?? $tours->total();
    $publishedTours = $catalogSummary['published'] ?? 0;
    $draftTours = $catalogSummary['draft'] ?? 0;
    $withDepartures = $catalogSummary['with_departures'] ?? 0;
    $activeFilters = [];

    foreach ([
        'status' => request('status'),
        'tour_type' => request('tour_type'),
        'destination' => request('destination'),
        'price_min' => request('price_min'),
        'price_max' => request('price_max'),
        'duration_min' => request('duration_min'),
        'duration_max' => request('duration_max'),
        'modified_from' => request('modified_from'),
        'modified_to' => request('modified_to'),
        'q' => request('q'),
        'has_departures' => request('has_departures'),
        'has_laravel_public' => request('has_laravel_public'),
    ] as $key => $value) {
        if ($value !== null && $value !== '') {
            $activeFilters[] = $key.' : '.$value;
        }
    }
@endphp

@section('title', 'Voyages')

@push('styles')
    <link href="{{ URL::asset('css/admin-catalog-premium.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
    <div class="aj-catalog-page">
        <div class="aj-catalog-shell">
            <x-admin.page-header
                :title="$pageTitle"
                subtitle="Pilotez les offres, départs, prix et publications depuis une vue unique, sans changer la logique métier existante."
                :breadcrumbs="[
                    ['label' => 'Admin', 'url' => route('admin.dashboard')],
                    ['label' => 'Circuits', 'url' => '#'],
                    ['label' => 'Voyages'],
                ]"
            >
                <x-slot name="actions">
                    <a href="{{ route('admin.circuits.voyages.create') }}" class="aj-btn aj-btn-soft">
                        <i class="bx bx-plus"></i>
                        <span>Créer un tour</span>
                    </a>
                    <a href="{{ route('admin.circuits.voyages.create-v2') }}" class="aj-btn aj-btn-primary">
                        <i class="bx bx-plus"></i>
                        <span>Créer en V2</span>
                    </a>
                </x-slot>
            </x-admin.page-header>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (!empty($wpConnectionFailed))
                <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                    <strong>Connexion catalogue indisponible.</strong>
                    Le chargement des voyages est temporairement indisponible. Veuillez réessayer dans quelques instants.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (!empty($wpCatalogErrorMessage))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <strong>Chargement de la liste impossible.</strong> {{ $wpCatalogErrorMessage }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <x-admin.kpi-cards
                :kpis="[
                    ['label' => 'Voyages trouvés', 'value' => number_format($totalTours, 0, ',', ' '), 'icon' => 'bx bx-map-alt', 'color' => '-blue', 'note' => 'Catalogue courant'],
                    ['label' => 'Publiés', 'value' => $publishedTours, 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Visibles dans le catalogue'],
                    ['label' => 'Brouillons', 'value' => $draftTours, 'icon' => 'bx bx-edit-alt', 'color' => '-orange', 'note' => 'À finaliser'],
                    ['label' => 'Avec départs actifs', 'value' => $withDepartures, 'icon' => 'bx bx-calendar-check', 'color' => '-violet', 'note' => 'Basé sur les données catalogue'],
                ]"
            />

            <x-admin.filter-panel
                :action="route('admin.circuits.voyages.index')"
                method="GET"
                :reset-url="route('admin.circuits.voyages.index')"
            >
                <x-slot name="fields">
                    <div class="aj-field aj-search-wrap aj-col-3">
                        <label for="q">Recherche</label>
                        <span class="aj-search-icon"><i class="bx bx-search"></i></span>
                        <input id="q" type="search" name="q" class="aj-control" value="{{ request('q') }}" placeholder="Titre, slug...">
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="status">Statut</label>
                        <select id="status" name="status" class="aj-control">
                            <option value="">Tous</option>
                            <option value="publish" @selected(request('status') === 'publish')>Publié</option>
                            <option value="draft" @selected(request('status') === 'draft')>Brouillon</option>
                            <option value="private" @selected(request('status') === 'private')>Archivé</option>
                            <option value="pending" @selected(request('status') === 'pending')>En attente</option>
                        </select>
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="tour_type">Type / thème</label>
                        <select id="tour_type" name="tour_type" class="aj-control">
                            <option value="">Tous</option>
                            @foreach($filterTourTypes ?? [] as $tt)
                                <option value="{{ $tt['term_id'] }}" @selected((string) request('tour_type') === (string) $tt['term_id'])>{{ $tt['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="destination">Destination</label>
                        <input id="destination" type="text" name="destination" class="aj-control" value="{{ request('destination') }}" placeholder="Ville, pays...">
                    </div>
                    <div class="aj-field aj-col-1">
                        <label for="price_min">Prix min</label>
                        <input id="price_min" type="number" step="0.01" name="price_min" class="aj-control" value="{{ request('price_min') }}">
                    </div>
                    <div class="aj-field aj-col-1">
                        <label for="price_max">Prix max</label>
                        <input id="price_max" type="number" step="0.01" name="price_max" class="aj-control" value="{{ request('price_max') }}">
                    </div>
                    <div class="aj-field aj-col-1">
                        <label for="duration_min">Durée min</label>
                        <input id="duration_min" type="number" min="1" name="duration_min" class="aj-control" value="{{ request('duration_min') }}">
                    </div>
                    <div class="aj-field aj-col-1">
                        <label for="duration_max">Durée max</label>
                        <input id="duration_max" type="number" min="1" name="duration_max" class="aj-control" value="{{ request('duration_max') }}">
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="modified_from">Modifié du</label>
                        <input id="modified_from" type="date" name="modified_from" class="aj-control" value="{{ request('modified_from') }}">
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="modified_to">au</label>
                        <input id="modified_to" type="date" name="modified_to" class="aj-control" value="{{ request('modified_to') }}">
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="has_departures">Départs actifs</label>
                        <select id="has_departures" name="has_departures" class="aj-control">
                            <option value="">Indifférent</option>
                            <option value="1" @selected(request('has_departures') === '1')>Oui</option>
                            <option value="0" @selected(request('has_departures') === '0')>Non</option>
                        </select>
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="has_laravel_public">Page Laravel publique</label>
                        <select id="has_laravel_public" name="has_laravel_public" class="aj-control">
                            <option value="">Indifférent</option>
                            <option value="1" @selected(request('has_laravel_public') === '1')>Oui</option>
                            <option value="0" @selected(request('has_laravel_public') === '0')>Non</option>
                        </select>
                    </div>
                </x-slot>

                <x-slot name="chips">
                    @forelse($activeFilters as $filterLabel)
                        <span class="aj-chip">{{ $filterLabel }}</span>
                    @empty
                        <span class="text-muted">Aucun filtre actif.</span>
                    @endforelse
                </x-slot>
            </x-admin.filter-panel>

            <section class="aj-panel">
                <div class="aj-toolbar">
                    <div class="aj-result-meta">
                        <div class="d-flex align-items-center gap-2">
                            <label for="voyageSortSelect" class="mb-0">Trier par :</label>
                            <select id="voyageSortSelect" class="aj-mini-select">
                                <option value="recent">Plus récents</option>
                                <option value="price_asc">Prix croissant</option>
                                <option value="price_desc">Prix décroissant</option>
                                <option value="title_asc">Titre A-Z</option>
                            </select>
                        </div>
                        <button type="button" class="aj-mini-btn" id="voyageExportBtn">
                            <i class="bx bx-export"></i>
                            <span>Exporter la vue</span>
                        </button>
                        <span>{{ $tours->firstItem() ?? 0 }} - {{ $tours->lastItem() ?? 0 }} sur {{ $tours->total() }} voyages</span>
                    </div>
                    <div class="aj-result-meta">
                        <span>Vue :</span>
                        <div class="aj-view-toggle">
                            <button type="button" class="is-active" data-view="table" aria-pressed="true"><i class="bx bx-list-ul"></i></button>
                            <button type="button" data-view="grid" aria-pressed="false"><i class="bx bx-grid-alt"></i></button>
                        </div>
                    </div>
                </div>

                @if($tours->isEmpty())
                    <x-admin.empty-state
                        title="Aucun voyage trouvé"
                        message="Ajustez vos filtres ou créez un nouveau voyage."
                        :action-url="route('admin.circuits.voyages.create')"
                        action-label="Créer un voyage"
                    />
                @else
                    <div class="aj-table-wrap" data-catalog-view="table">
                        <table class="aj-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Voyage</th>
                                    <th>Destination</th>
                                    <th>Durée</th>
                                    <th>Prix adulte</th>
                                    <th>Statut</th>
                                    <th>Modifié le</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tours as $tour)
                                    @php
                                        $price = is_numeric($tour->adult_price ?? null) ? (float) $tour->adult_price : 0;
                                        $modifiedTimestamp = $tour->post_modified ? optional($tour->post_modified)->timestamp ?? strtotime((string) $tour->post_modified) : 0;
                                        $imageUrl = trim((string) ($tour->image_url ?? ''));
                                    @endphp
                                    <tr data-title="{{ Str::lower($tour->post_title) }}" data-price="{{ $price }}" data-modified="{{ $modifiedTimestamp }}">
                                        <td><strong>#{{ $tour->ID }}</strong></td>
                                        <td>
                                            <x-admin.image-thumb :src="$imageUrl !== '' ? $imageUrl : null" :alt="$tour->post_title" size="tour" />
                                        </td>
                                        <td>
                                            <div class="aj-item-title">
                                                <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}">{{ $tour->post_title }}</a>
                                                @if(!empty($tour->laravel_slug))
                                                    <span class="aj-badge -info">Laravel</span>
                                                @endif
                                            </div>
                                            <div class="aj-meta-text">{{ $tour->post_name }}</div>
                                        </td>
                                        <td>{{ $tour->address ?? '-' }}</td>
                                        <td>{{ $tour->duration_day ?? '-' }}</td>
                                        <td><span class="aj-price">{{ $price > 0 ? number_format($price, 0, ',', ' ') . ' MAD' : '—' }}</span></td>
                                        <td>
                                            @if($tour->post_status === 'publish')
                                                <span class="aj-badge -success">Publié</span>
                                            @elseif($tour->post_status === 'draft')
                                                <span class="aj-badge -warning">Brouillon</span>
                                            @elseif($tour->post_status === 'private')
                                                <span class="aj-badge -neutral">Archivé</span>
                                            @else
                                                <span class="aj-badge -info">{{ $tour->post_status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($tour->post_modified)->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td class="text-end">
                                            <div class="aj-actions">
                                                <a href="https://ajinsafro.net/tours/{{ $tour->post_name }}" target="_blank" class="aj-icon-btn" title="Voir la fiche publique">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                @if(!empty($tour->laravel_slug))
                                                    <a href="{{ url('/voyages/'.$tour->laravel_slug) }}" target="_blank" rel="noopener noreferrer" class="aj-icon-btn" title="Voir la page commerciale">
                                                        <i class="bx bx-link-external"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="aj-icon-btn" title="Modifier">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                                <a href="{{ route('admin.circuits.voyages.edit-v2', $tour->ID) }}" class="aj-icon-btn" title="Éditeur V2">
                                                    <i class="bx bx-layer"></i>
                                                </a>
                                                <form action="{{ route('admin.circuits.voyages.destroy', $tour->ID) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce tour de WordPress ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="aj-icon-btn -danger" title="Supprimer">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="aj-grid" data-catalog-view="grid">
                        @foreach($tours as $tour)
                            @php
                                $price = is_numeric($tour->adult_price ?? null) ? (float) $tour->adult_price : 0;
                                $modifiedTimestamp = $tour->post_modified ? optional($tour->post_modified)->timestamp ?? strtotime((string) $tour->post_modified) : 0;
                                $imageUrl = trim((string) ($tour->image_url ?? ''));
                            @endphp
                            <article class="aj-card" data-title="{{ Str::lower($tour->post_title) }}" data-price="{{ $price }}" data-modified="{{ $modifiedTimestamp }}">
                                <div class="aj-card-cover d-flex align-items-end p-3" @if($imageUrl !== '') style="background-image:linear-gradient(180deg, rgba(9,32,63,0.06), rgba(9,32,63,0.42)), url('{{ $imageUrl }}'); background-size:cover; background-position:center;" @endif>
                                    <span class="aj-badge -info">#{{ $tour->ID }}</span>
                                </div>
                                <div class="aj-card-body">
                                    <h4 class="aj-card-title"><a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}">{{ $tour->post_title }}</a></h4>
                                    <div class="aj-meta-text mb-2">{{ $tour->post_name }}</div>
                                    <div class="aj-meta-text mb-2">{{ $tour->address ?? 'Destination non renseignée' }}</div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="aj-badge -neutral">{{ $tour->duration_day ?? '-' }} jours</span>
                                        @if($tour->post_status === 'publish')
                                            <span class="aj-badge -success">Publié</span>
                                        @elseif($tour->post_status === 'draft')
                                            <span class="aj-badge -warning">Brouillon</span>
                                        @else
                                            <span class="aj-badge -info">{{ $tour->post_status }}</span>
                                        @endif
                                    </div>
                                    <div class="aj-card-actions">
                                        <span class="aj-price">{{ $price > 0 ? number_format($price, 0, ',', ' ') . ' MAD' : '—' }}</span>
                                        <div class="aj-actions">
                                            <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="aj-icon-btn" title="Modifier"><i class="bx bx-pencil"></i></a>
                                            <a href="{{ route('admin.circuits.voyages.edit-v2', $tour->ID) }}" class="aj-icon-btn" title="V2"><i class="bx bx-layer"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <x-admin.pagination-footer :paginator="$tours" links-view="pagination::bootstrap-5" />
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableView = document.querySelector('[data-catalog-view="table"]');
            const gridView = document.querySelector('[data-catalog-view="grid"]');
            const toggleButtons = document.querySelectorAll('.aj-view-toggle button');
            const sortSelect = document.getElementById('voyageSortSelect');
            const exportBtn = document.getElementById('voyageExportBtn');

            function setView(mode) {
                toggleButtons.forEach((button) => {
                    const active = button.dataset.view === mode;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-pressed', active ? 'true' : 'false');
                });

                if (tableView) {
                    tableView.style.display = mode === 'table' ? 'block' : 'none';
                }

                if (gridView) {
                    gridView.classList.toggle('is-active', mode === 'grid');
                }
            }

            function compareNodes(mode) {
                return function (a, b) {
                    const titleA = a.dataset.title || '';
                    const titleB = b.dataset.title || '';
                    const priceA = Number(a.dataset.price || 0);
                    const priceB = Number(b.dataset.price || 0);
                    const modifiedA = Number(a.dataset.modified || 0);
                    const modifiedB = Number(b.dataset.modified || 0);

                    if (mode === 'price_asc') return priceA - priceB;
                    if (mode === 'price_desc') return priceB - priceA;
                    if (mode === 'title_asc') return titleA.localeCompare(titleB, 'fr');
                    return modifiedB - modifiedA;
                };
            }

            function sortCurrentView(mode) {
                const rowContainer = tableView ? tableView.querySelector('tbody') : null;
                if (rowContainer) {
                    [...rowContainer.querySelectorAll('tr')].sort(compareNodes(mode)).forEach((row) => rowContainer.appendChild(row));
                }

                if (gridView) {
                    [...gridView.querySelectorAll('.aj-card')].sort(compareNodes(mode)).forEach((card) => gridView.appendChild(card));
                }
            }

            toggleButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    setView(this.dataset.view || 'table');
                });
            });

            if (sortSelect) {
                sortSelect.addEventListener('change', function () {
                    sortCurrentView(this.value || 'recent');
                });
            }

            if (exportBtn) {
                exportBtn.addEventListener('click', function () {
                    window.print();
                });
            }

            setView('table');
            sortCurrentView(sortSelect ? sortSelect.value : 'recent');
        });
    </script>
@endpush
