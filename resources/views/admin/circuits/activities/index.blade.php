@extends('layouts.master-ajinsafro')

@php
    use Illuminate\Support\Str;

    $mediaService = app(\App\Services\WordPressMediaService::class);
    $pageTitle = 'Catalogue des activités';
    $currentActivities = $activities->getCollection();
    $totalActivities = $activities->total();
    $activeCount = $currentActivities->where('is_active', true)->count();
    $inactiveCount = $currentActivities->where('is_active', false)->count();
    $withGalleryCount = $currentActivities->filter(function ($activity) {
        $galleryIds = collect($activity->gallery_image_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0);

        return $galleryIds->isNotEmpty() || (int) ($activity->image_id ?? 0) > 0;
    })->count();
@endphp

@section('title', $pageTitle)

@push('styles')
    <link href="{{ URL::asset('css/admin-catalog-premium.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
    <div class="aj-catalog-page">
        <div class="aj-catalog-shell">
            <div class="aj-catalog-head">
                <div>
                    <h1 class="aj-catalog-title">{{ $pageTitle }}</h1>
                    <p class="aj-catalog-subtitle">Gérez les activités réutilisables par région avec une présentation admin cohérente et plus propre.</p>
                </div>
                <div>
                    <div class="aj-catalog-breadcrumb">
                        <span>Admin</span>
                        <span>/</span>
                        <span>Circuits</span>
                        <span>/</span>
                        <strong style="color:#0b1f3a">Activités</strong>
                    </div>
                    <a href="{{ route('admin.circuits.activities.create') }}" class="aj-btn aj-btn-primary">
                        <i class="bx bx-plus"></i>
                        <span>Nouvelle activité</span>
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <section class="aj-kpis">
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -blue"><i class="bx bx-camera-movie"></i></div>
                        <div>
                            <span class="aj-kpi-label">Total activités</span>
                            <strong class="aj-kpi-value">{{ number_format($totalActivities, 0, ',', ' ') }}</strong>
                            <span class="aj-kpi-note">Catalogue courant</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -green"><i class="bx bx-badge-check"></i></div>
                        <div>
                            <span class="aj-kpi-label">Actives</span>
                            <strong class="aj-kpi-value">{{ $activeCount }}</strong>
                            <span class="aj-kpi-note">Sur la page affichée</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -orange"><i class="bx bx-pause-circle"></i></div>
                        <div>
                            <span class="aj-kpi-label">Inactives</span>
                            <strong class="aj-kpi-value">{{ $inactiveCount }}</strong>
                            <span class="aj-kpi-note">À vérifier</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -violet"><i class="bx bx-images"></i></div>
                        <div>
                            <span class="aj-kpi-label">Avec galerie</span>
                            <strong class="aj-kpi-value">{{ $withGalleryCount }}</strong>
                            <span class="aj-kpi-note">Visuels renseignés</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="aj-panel">
                <div class="aj-toolbar mb-0">
                    <div class="aj-result-meta">
                        <div class="d-flex align-items-center gap-2">
                            <label for="activityFilterInput" class="mb-0">Recherche locale :</label>
                            <input id="activityFilterInput" type="search" class="aj-mini-select" placeholder="Titre, région, type...">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <label for="activitySortSelect" class="mb-0">Trier par :</label>
                            <select id="activitySortSelect" class="aj-mini-select">
                                <option value="title_asc">Titre A-Z</option>
                                <option value="price_asc">Prix croissant</option>
                                <option value="price_desc">Prix décroissant</option>
                                <option value="duration_desc">Durée longue</option>
                            </select>
                        </div>
                        <span>{{ $activities->firstItem() ?? 0 }} - {{ $activities->lastItem() ?? 0 }} sur {{ $totalActivities }} activités</span>
                    </div>
                    <div class="aj-result-meta">
                        <button type="button" class="aj-mini-btn" id="activityExportBtn">
                            <i class="bx bx-export"></i>
                            <span>Exporter la vue</span>
                        </button>
                        <div class="aj-view-toggle">
                            <button type="button" class="is-active" data-view="table" aria-pressed="true"><i class="bx bx-list-ul"></i></button>
                            <button type="button" data-view="grid" aria-pressed="false"><i class="bx bx-grid-alt"></i></button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="aj-panel">
                @if($activities->isEmpty())
                    <div class="aj-empty">
                        <h5 class="mb-2">Aucune activité disponible</h5>
                        <p class="text-muted mb-3">Créez la première activité pour alimenter le catalogue.</p>
                        <a href="{{ route('admin.circuits.activities.create') }}" class="aj-btn aj-btn-primary">
                            <i class="bx bx-plus"></i>
                            <span>Créer une activité</span>
                        </a>
                    </div>
                @else
                    <div class="aj-table-wrap" data-catalog-view="table">
                        <table class="aj-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Visuel</th>
                                    <th>Activité</th>
                                    <th>Région</th>
                                    <th>Tarifs</th>
                                    <th>Âges</th>
                                    <th>Durée</th>
                                    <th>Galerie</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activities as $activity)
                                    @php
                                        $galleryIds = collect($activity->gallery_image_ids ?? [])
                                            ->map(fn ($id) => (int) $id)
                                            ->filter(fn ($id) => $id > 0)
                                            ->values();

                                        if ($galleryIds->isEmpty() && (int) ($activity->image_id ?? 0) > 0) {
                                            $galleryIds = collect([(int) $activity->image_id]);
                                        }

                                        $coverUrl = $galleryIds->isNotEmpty()
                                            ? $mediaService->getAttachmentUrl((int) $galleryIds->first())
                                            : null;

                                        $adultPrice = (float) ($activity->adult_price ?? $activity->base_price ?? 0);
                                        $duration = (int) ($activity->default_duration_minutes ?? 0);
                                    @endphp
                                    <tr
                                        data-title="{{ Str::lower($activity->title) }}"
                                        data-filter="{{ Str::lower(($activity->title ?? '') . ' ' . ($activity->region_name ?? '') . ' ' . ($activity->activity_type ?? '')) }}"
                                        data-price="{{ $adultPrice }}"
                                        data-duration="{{ $duration }}"
                                    >
                                        <td><strong>#{{ $activity->id }}</strong></td>
                                        <td>
                                            <div class="aj-thumb">
                                                @if($coverUrl)
                                                    <img src="{{ $coverUrl }}" alt="{{ $activity->title }}">
                                                @else
                                                    <div class="aj-thumb-placeholder">Ajinsafro</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="aj-item-title">
                                                <a href="{{ route('admin.circuits.activities.edit', $activity) }}">{{ $activity->title }}</a>
                                                @if($activity->is_active)
                                                    <span class="aj-badge -success">Active</span>
                                                @endif
                                            </div>
                                            <div class="aj-meta-text">{{ $activity->activity_type ?: 'Type non renseigné' }}</div>
                                            <div class="aj-meta-text"><code>{{ $activity->slug }}</code></div>
                                        </td>
                                        <td>{{ $activity->region_name ?: $activity->location_text ?: '-' }}</td>
                                        <td>
                                            <div class="aj-price">{{ number_format($adultPrice, 2, ',', ' ') }} MAD</div>
                                            <div class="aj-meta-text">Enfant : {{ number_format((float) ($activity->child_price ?? 0), 2, ',', ' ') }} MAD</div>
                                        </td>
                                        <td>
                                            <div class="aj-meta-text">Min : {{ $activity->min_age ?? '-' }} ans</div>
                                            <div class="aj-meta-text">Max : {{ $activity->max_age ?? '-' }} ans</div>
                                        </td>
                                        <td>{{ $duration ? $duration . ' min' : '-' }}</td>
                                        <td><span class="aj-badge -neutral">{{ $galleryIds->count() }} image(s)</span></td>
                                        <td>
                                            @if($activity->is_active)
                                                <span class="aj-badge -success">Active</span>
                                            @else
                                                <span class="aj-badge -neutral">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="aj-actions">
                                                <a href="{{ route('admin.circuits.activities.edit', $activity) }}" class="aj-icon-btn" title="Modifier">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                                <form action="{{ route('admin.circuits.activities.destroy', $activity) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette activite ?');">
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
                        @foreach($activities as $activity)
                            @php
                                $galleryIds = collect($activity->gallery_image_ids ?? [])
                                    ->map(fn ($id) => (int) $id)
                                    ->filter(fn ($id) => $id > 0)
                                    ->values();

                                if ($galleryIds->isEmpty() && (int) ($activity->image_id ?? 0) > 0) {
                                    $galleryIds = collect([(int) $activity->image_id]);
                                }

                                $coverUrl = $galleryIds->isNotEmpty()
                                    ? $mediaService->getAttachmentUrl((int) $galleryIds->first())
                                    : null;

                                $adultPrice = (float) ($activity->adult_price ?? $activity->base_price ?? 0);
                                $duration = (int) ($activity->default_duration_minutes ?? 0);
                            @endphp
                            <article
                                class="aj-card"
                                data-title="{{ Str::lower($activity->title) }}"
                                data-filter="{{ Str::lower(($activity->title ?? '') . ' ' . ($activity->region_name ?? '') . ' ' . ($activity->activity_type ?? '')) }}"
                                data-price="{{ $adultPrice }}"
                                data-duration="{{ $duration }}"
                            >
                                <div class="aj-card-cover">
                                    @if($coverUrl)
                                        <img src="{{ $coverUrl }}" alt="{{ $activity->title }}">
                                    @endif
                                </div>
                                <div class="aj-card-body">
                                    <h4 class="aj-card-title"><a href="{{ route('admin.circuits.activities.edit', $activity) }}">{{ $activity->title }}</a></h4>
                                    <div class="aj-meta-text mb-2">{{ $activity->region_name ?: $activity->location_text ?: 'Région non renseignée' }}</div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="aj-badge -neutral">{{ $activity->activity_type ?: 'Type libre' }}</span>
                                        @if($activity->is_active)
                                            <span class="aj-badge -success">Active</span>
                                        @else
                                            <span class="aj-badge -neutral">Inactive</span>
                                        @endif
                                    </div>
                                    <div class="aj-card-actions">
                                        <span class="aj-price">{{ number_format($adultPrice, 2, ',', ' ') }} MAD</span>
                                        <div class="aj-actions">
                                            <a href="{{ route('admin.circuits.activities.edit', $activity) }}" class="aj-icon-btn" title="Modifier"><i class="bx bx-pencil"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="aj-footer">
                        <div>Affichage de {{ $activities->firstItem() ?? 0 }} à {{ $activities->lastItem() ?? 0 }} sur {{ $totalActivities }} résultats</div>
                        <div>{{ $activities->links() }}</div>
                    </div>
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
            const sortSelect = document.getElementById('activitySortSelect');
            const filterInput = document.getElementById('activityFilterInput');
            const exportBtn = document.getElementById('activityExportBtn');

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
                    const durationA = Number(a.dataset.duration || 0);
                    const durationB = Number(b.dataset.duration || 0);

                    if (mode === 'price_asc') return priceA - priceB;
                    if (mode === 'price_desc') return priceB - priceA;
                    if (mode === 'duration_desc') return durationB - durationA;
                    return titleA.localeCompare(titleB, 'fr');
                };
            }

            function sortNodes(mode) {
                const rowContainer = tableView ? tableView.querySelector('tbody') : null;
                if (rowContainer) {
                    [...rowContainer.querySelectorAll('tr')].sort(compareNodes(mode)).forEach((row) => rowContainer.appendChild(row));
                }

                if (gridView) {
                    [...gridView.querySelectorAll('.aj-card')].sort(compareNodes(mode)).forEach((card) => gridView.appendChild(card));
                }
            }

            function applyFilter(query) {
                const normalized = (query || '').trim().toLowerCase();
                const rows = tableView ? tableView.querySelectorAll('tbody tr') : [];
                const cards = gridView ? gridView.querySelectorAll('.aj-card') : [];

                rows.forEach((row) => {
                    const match = !normalized || (row.dataset.filter || '').includes(normalized);
                    row.style.display = match ? '' : 'none';
                });

                cards.forEach((card) => {
                    const match = !normalized || (card.dataset.filter || '').includes(normalized);
                    card.style.display = match ? '' : 'none';
                });
            }

            toggleButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    setView(this.dataset.view || 'table');
                });
            });

            if (sortSelect) {
                sortSelect.addEventListener('change', function () {
                    sortNodes(this.value || 'title_asc');
                });
            }

            if (filterInput) {
                filterInput.addEventListener('input', function () {
                    applyFilter(this.value || '');
                });
            }

            if (exportBtn) {
                exportBtn.addEventListener('click', function () {
                    window.print();
                });
            }

            setView('table');
            sortNodes(sortSelect ? sortSelect.value : 'title_asc');
        });
    </script>
@endpush
