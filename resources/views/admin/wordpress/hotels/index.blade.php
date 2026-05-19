@extends('layouts.admin-v6')

@php
    use Illuminate\Support\Str;

    $pageTitle = 'Catalogue HÃ©bergements';
    $currentHotels = $hotels->getCollection();
    $totalHotels = $hotels->total();
    $publishedCount = $currentHotels->where('post_status', 'publish')->count();
    $draftCount = $currentHotels->where('post_status', 'draft')->count();
    $featuredCount = $currentHotels->filter(fn ($hotel) => optional($hotel->stHotel)->is_featured === 'on')->count();
    $activeFilterCount = collect([
        filled($filters['search'] ?? null),
        filled($filters['status'] ?? null),
        filled($filters['star'] ?? null),
        filled($filters['featured'] ?? null),
        filled($filters['destination'] ?? null),
    ])->filter()->count();

    $activeFilters = [];
    if (filled($filters['search'] ?? null)) {
        $activeFilters[] = 'Recherche : '.Str::limit($filters['search'], 28);
    }
    if (($filters['status'] ?? '') === 'publish') {
        $activeFilters[] = 'Statut : PubliÃ©s';
    } elseif (($filters['status'] ?? '') === 'draft') {
        $activeFilters[] = 'Statut : Brouillons';
    }
    if (filled($filters['star'] ?? null)) {
        $activeFilters[] = 'Ã‰toiles : '.(int) $filters['star'];
    }
    if (($filters['featured'] ?? '') === '1') {
        $activeFilters[] = 'SÃ©lection : Ã€ la une';
    }
    if (filled($filters['destination'] ?? null)) {
        $activeFilters[] = 'Destination : '.Str::limit($filters['destination'], 28);
    }
    $activeFilterCount = count($activeFilters);
@endphp

@section('title', $pageTitle)

@push('styles')
    <link href="{{ URL::asset('css/admin-catalog-premium.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
    <div class="aj-catalog-page">
        <div class="aj-shell">
            <x-admin.page-header
                :title="$pageTitle"
                subtitle="GÃ©rez, filtrez et consultez les hÃ©bergements WordPress synchronisÃ©s sans modifier la logique mÃ©tier existante."
                :breadcrumbs="[
                    ['label' => 'Admin', 'url' => route('admin.dashboard')],
                    ['label' => 'HÃ©bergements', 'url' => '#'],
                    ['label' => 'Catalogue'],
                ]"
            >
                <x-slot name="actions">
                    <a href="{{ route('admin.wordpress.hotels.create') }}" class="aj-btn aj-btn-primary">
                        <i class="bx bx-plus"></i>
                        <span>CrÃ©er un hÃ©bergement</span>
                    </a>
                </x-slot>
            </x-admin.page-header>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <x-admin.kpi-cards
                :kpis="[
                    ['label' => 'Total hÃ©bergements', 'value' => number_format($totalHotels, 0, ',', ' '), 'icon' => 'bx bx-buildings', 'color' => '-blue', 'note' => 'RÃ©sultats sur le catalogue courant'],
                    ['label' => 'PubliÃ©s', 'value' => $publishedCount, 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Sur la page affichÃ©e'],
                    ['label' => 'Brouillons', 'value' => $draftCount, 'icon' => 'bx bx-edit-alt', 'color' => '-orange', 'note' => 'Ã€ complÃ©ter ou publier'],
                    ['label' => 'Ã€ la une', 'value' => $featuredCount, 'icon' => 'bx bx-star', 'color' => '-violet', 'note' => 'Mis en avant dans cette vue'],
                ]"
            />

            <x-admin.filter-panel
                :action="route('admin.wordpress.hotels.index')"
                method="GET"
                :reset-url="route('admin.wordpress.hotels.index')"
                grid-class="-compact"
            >
                <x-slot name="fields">
                    <div class="aj-field aj-search-wrap">
                        <label for="search">Recherche</label>
                        <span class="aj-search-icon"><i class="bx bx-search"></i></span>
                        <input id="search" type="text" name="search" class="aj-control" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, slug, rÃ©sumÃ© ou adresse">
                    </div>
                    <div class="aj-field">
                        <label for="status">Statut</label>
                        <select id="status" name="status" class="aj-control">
                            <option value="">Tous les statuts</option>
                            <option value="publish" @selected(($filters['status'] ?? '') === 'publish')>PubliÃ©</option>
                            <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Brouillon</option>
                        </select>
                    </div>
                    <div class="aj-field">
                        <label for="hotel_star">Ã‰toiles</label>
                        <select id="hotel_star" name="hotel_star" class="aj-control">
                            <option value="">Toutes les Ã©toiles</option>
                            @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" @selected((string) ($filters['star'] ?? '') === (string) $i)>{{ $i }} Ã©toile(s)</option>
                            @endfor
                        </select>
                    </div>
                    <div class="aj-field">
                        <label for="featured">SÃ©lection</label>
                        <select id="featured" name="featured" class="aj-control">
                            <option value="">Tous les hÃ©bergements</option>
                            <option value="1" @selected(($filters['featured'] ?? '') === '1')>Ã€ la une</option>
                        </select>
                    </div>
                    <div class="aj-field">
                        <label for="destination">Destination</label>
                        <input id="destination" type="text" name="destination" class="aj-control" value="{{ $filters['destination'] ?? '' }}" placeholder="Ville ou adresse">
                    </div>
                </x-slot>

                <x-slot name="chips">
                    @forelse ($activeFilters as $filterLabel)
                        <span class="aj-chip">{{ $filterLabel }}</span>
                    @empty
                        <span class="text-muted">Aucun filtre actif.</span>
                    @endforelse
                    @if ($activeFilterCount > 0)
                        <a href="{{ route('admin.wordpress.hotels.index') }}" class="ms-auto fw-bold text-decoration-none" style="color:#0468c8;">Tout effacer</a>
                    @endif
                </x-slot>
            </x-admin.filter-panel>

            <section class="aj-panel">
                <div class="aj-toolbar">
                    <div class="aj-result-meta">
                        <div class="d-flex align-items-center gap-2">
                            <label for="hotelSortSelect" class="mb-0">Trier par :</label>
                            <select id="hotelSortSelect" class="aj-mini-btn aj-mini-select">
                                <option value="recent">Plus rÃ©cents</option>
                                <option value="price_asc">Prix croissant</option>
                                <option value="price_desc">Prix dÃ©croissant</option>
                                <option value="title_asc">Titre A-Z</option>
                            </select>
                        </div>
                        <button type="button" class="aj-mini-btn" id="hotelExportBtn">
                            <i class="bx bx-export"></i>
                            <span>Exporter la vue</span>
                        </button>
                        <span>{{ $hotels->firstItem() ?? 0 }} - {{ $hotels->lastItem() ?? 0 }} sur {{ $totalHotels }} hÃ©bergements</span>
                    </div>
                    <div class="aj-result-meta">
                        <span>Vue :</span>
                        <div class="aj-view-toggle" role="tablist" aria-label="Changer la vue">
                            <button type="button" class="is-active" data-view="table" aria-pressed="true"><i class="bx bx-list-ul"></i></button>
                            <button type="button" data-view="grid" aria-pressed="false"><i class="bx bx-grid-alt"></i></button>
                        </div>
                    </div>
                </div>

                @if($hotels->isEmpty())
                    <x-admin.empty-state
                        title="Aucun hÃ©bergement trouvÃ©"
                        message="Ajustez vos filtres ou crÃ©ez un nouvel hÃ©bergement pour alimenter le catalogue."
                        :action-url="route('admin.wordpress.hotels.create')"
                        action-label="CrÃ©er un hÃ©bergement"
                    />
                @else
                    <div class="aj-table-wrap" data-hotel-view="table">
                        <table class="aj-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>HÃ©bergement</th>
                                    <th>Localisation</th>
                                    <th>Statut</th>
                                    <th>Ã‰toiles</th>
                                    <th>Prix min</th>
                                    <th>ModifiÃ© le</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hotels as $hotel)
                                    @php
                                        $thumbUrl = $media->getFeaturedImageUrlVerified($hotel->ID);
                                        $stHotel = $hotel->stHotel;
                                        $isPublished = $hotel->post_status === 'publish';
                                        $isFeatured = optional($stHotel)->is_featured === 'on';
                                        $stars = (int) ($stHotel->hotel_star ?? 0);
                                        $price = $stHotel->min_price;
                                        $address = trim((string) ($stHotel->address ?? ''));
                                    @endphp
                                    <tr
                                        data-title="{{ Str::lower($hotel->post_title) }}"
                                        data-price="{{ is_numeric($price) ? (float) $price : 0 }}"
                                        data-modified="{{ $hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->timestamp : 0 }}"
                                    >
                                        <td>
                                            <x-admin.image-thumb :src="$thumbUrl" :alt="$hotel->post_title" size="md" />
                                        </td>
                                        <td>
                                            <div class="aj-item-title">
                                                <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}">{{ $hotel->post_title }}</a>
                                                @if($isFeatured)
                                                    <span class="aj-badge -info">Ã€ la une</span>
                                                @endif
                                            </div>
                                            <div class="aj-meta-text">ID #{{ $hotel->ID }}</div>
                                            @if($hotel->post_excerpt)
                                                <div class="aj-meta-text mt-1">{{ Str::limit(strip_tags($hotel->post_excerpt), 70) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="aj-location">
                                                <strong>{{ $address !== '' ? $address : 'Adresse non renseignÃ©e' }}</strong>
                                                <span>{{ $hotel->post_name ?: 'Slug non renseignÃ©' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($isPublished)
                                                <span class="aj-badge -success">PubliÃ©</span>
                                            @else
                                                <span class="aj-badge -warning">Brouillon</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($stars > 0)
                                                <span class="aj-stars">{{ str_repeat('â˜…', $stars) }}<span>{{ $stars }}</span></span>
                                            @else
                                                <span class="aj-meta-text">Non renseignÃ©</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="aj-price">
                                                {{ is_numeric($price) ? number_format((float) $price, 0, ',', ' ') . ' DH' : 'â€”' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="aj-date">
                                                {{ $hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->format('d/m/Y') : 'â€”' }}
                                                <small>{{ $hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->format('H:i') : '' }}</small>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="aj-actions">
                                                @if($wpSiteUrl)
                                                    <a href="{{ $wpSiteUrl }}/?post_type=st_hotel&p={{ $hotel->ID }}" target="_blank" class="aj-icon-btn" title="Voir sur le site">
                                                        <i class="bx bx-link-external"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="aj-icon-btn" title="Modifier">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                                <form action="{{ route('admin.wordpress.hotels.destroy', $hotel) }}" method="POST" class="d-inline" onsubmit="return confirm('DÃ©placer cet hÃ´tel dans la corbeille ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="aj-icon-btn -danger" title="Supprimer">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                                <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="aj-icon-btn" title="Plus">
                                                    <i class="bx bx-dots-horizontal-rounded"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="aj-grid" data-hotel-view="grid">
                        @foreach($hotels as $hotel)
                            @php
                                $thumbUrl = $media->getFeaturedImageUrlVerified($hotel->ID);
                                $stHotel = $hotel->stHotel;
                                $isPublished = $hotel->post_status === 'publish';
                                $isFeatured = optional($stHotel)->is_featured === 'on';
                                $stars = (int) ($stHotel->hotel_star ?? 0);
                                $price = $stHotel->min_price;
                            @endphp
                            <article
                                class="aj-card"
                                data-title="{{ Str::lower($hotel->post_title) }}"
                                data-price="{{ is_numeric($price) ? (float) $price : 0 }}"
                                data-modified="{{ $hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->timestamp : 0 }}"
                            >
                                <div class="aj-card-cover">
                                    @if($thumbUrl)
                                        <img src="{{ $thumbUrl }}" alt="{{ $hotel->post_title }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                        <div class="aj-thumb-placeholder" style="display:none; height:100%; border-radius:0;">
                                            <img src="{{ asset('images/admin-placeholder.svg') }}" alt="Ajinsafro" style="width:100%;height:100%;object-fit:cover;">
                                        </div>
                                    @else
                                        <div class="aj-thumb-placeholder" style="display:grid; height:100%; border-radius:0;">
                                            <img src="{{ asset('images/admin-placeholder.svg') }}" alt="Ajinsafro" style="width:100%;height:100%;object-fit:cover;">
                                        </div>
                                    @endif
                                </div>
                                <div class="aj-card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <h4 class="aj-card-title"><a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}">{{ $hotel->post_title }}</a></h4>
                                            <div class="aj-meta-text">ID #{{ $hotel->ID }}</div>
                                        </div>
                                        @if($isFeatured)
                                            <span class="aj-badge -info">Ã€ la une</span>
                                        @endif
                                    </div>

                                    <div class="aj-meta-text mb-3">{{ trim((string) ($stHotel->address ?? '')) !== '' ? $stHotel->address : 'Adresse non renseignÃ©e' }}</div>

                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        @if($isPublished)
                                            <span class="aj-badge -success">PubliÃ©</span>
                                        @else
                                            <span class="aj-badge -warning">Brouillon</span>
                                        @endif
                                        @if($stars > 0)
                                            <span class="aj-badge -neutral">{{ $stars }} Ã©toile(s)</span>
                                        @endif
                                    </div>

                                    <div class="aj-card-actions">
                                        <span class="aj-price">{{ is_numeric($price) ? number_format((float) $price, 0, ',', ' ') . ' DH' : 'â€”' }}</span>
                                        <div class="aj-actions">
                                            @if($wpSiteUrl)
                                                <a href="{{ $wpSiteUrl }}/?post_type=st_hotel&p={{ $hotel->ID }}" target="_blank" class="aj-icon-btn" title="Voir sur le site">
                                                    <i class="bx bx-link-external"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('admin.wordpress.hotels.edit', $hotel) }}" class="aj-icon-btn" title="Modifier">
                                                <i class="bx bx-pencil"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <x-admin.pagination-footer :paginator="$hotels" />
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleButtons = document.querySelectorAll('.aj-view-toggle button');
            const tableView = document.querySelector('[data-hotel-view="table"]');
            const gridView = document.querySelector('[data-hotel-view="grid"]');
            const exportBtn = document.getElementById('hotelExportBtn');
            const sortSelect = document.getElementById('hotelSortSelect');

            function setView(mode) {
                toggleButtons.forEach((button) => {
                    const isActive = button.dataset.view === mode;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
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

                    if (mode === 'price_asc') {
                        return priceA - priceB;
                    }

                    if (mode === 'price_desc') {
                        return priceB - priceA;
                    }

                    if (mode === 'title_asc') {
                        return titleA.localeCompare(titleB, 'fr');
                    }

                    return modifiedB - modifiedA;
                };
            }

            function sortCurrentView(mode) {
                const rowContainer = tableView ? tableView.querySelector('tbody') : null;
                const cardContainer = gridView;

                if (rowContainer) {
                    [...rowContainer.querySelectorAll('tr')]
                        .sort(compareNodes(mode))
                        .forEach((row) => rowContainer.appendChild(row));
                }

                if (cardContainer) {
                    [...cardContainer.querySelectorAll('.aj-card')]
                        .sort(compareNodes(mode))
                        .forEach((card) => cardContainer.appendChild(card));
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

