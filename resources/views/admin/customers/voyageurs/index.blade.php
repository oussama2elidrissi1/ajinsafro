@extends('layouts.admin-v6')

@php
    use Illuminate\Support\Str;

    $pageTitle = 'Voyageurs';
    $currentClients = $clients->getCollection();
    $totalClients = $clients->total();
    $activeCount = $currentClients->where('status', 'active')->count();
    $vipCount = $currentClients->where('status', 'vip')->count();
    $withReservationsCount = $currentClients->filter(fn ($client) => (int) ($client->reservations_count ?? 0) > 0)->count();
    $activeFilters = [];
    if (filled(request('search'))) {
        $activeFilters[] = 'Recherche : '.Str::limit(request('search'), 30);
    }
    if (filled(request('status'))) {
        $activeFilters[] = 'Statut : '.request('status');
    }
    if (filled(request('per_page'))) {
        $activeFilters[] = 'Par page : '.request('per_page');
    }
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
                    <p class="aj-catalog-subtitle">Base voyageurs issue de la table <code>clients</code>, utilisÃ©e par les rÃ©servations et la relation client.</p>
                </div>
                <div>
                    <div class="aj-catalog-breadcrumb">
                        <span>Admin</span>
                        <span>/</span>
                        <span>Clients</span>
                        <span>/</span>
                        <strong style="color:#0b1f3a">Voyageurs</strong>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.customers.clients.index') }}" class="aj-btn aj-btn-soft">
                            <i class="bx bx-list-ul"></i>
                            <span>Vue complÃ¨te</span>
                        </a>
                        <a href="{{ route('admin.customers.clients.create') }}" class="aj-btn aj-btn-primary">
                            <i class="bx bx-plus"></i>
                            <span>Nouveau voyageur</span>
                        </a>
                    </div>
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
                        <div class="aj-kpi-icon -blue"><i class="bx bx-user"></i></div>
                        <div>
                            <span class="aj-kpi-label">Total voyageurs</span>
                            <strong class="aj-kpi-value">{{ number_format($totalClients, 0, ',', ' ') }}</strong>
                            <span class="aj-kpi-note">Base clients filtrÃ©e</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -green"><i class="bx bx-badge-check"></i></div>
                        <div>
                            <span class="aj-kpi-label">Actifs</span>
                            <strong class="aj-kpi-value">{{ $activeCount }}</strong>
                            <span class="aj-kpi-note">Sur la page affichÃ©e</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -violet"><i class="bx bx-crown"></i></div>
                        <div>
                            <span class="aj-kpi-label">VIP</span>
                            <strong class="aj-kpi-value">{{ $vipCount }}</strong>
                            <span class="aj-kpi-note">Clients premium</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -orange"><i class="bx bx-receipt"></i></div>
                        <div>
                            <span class="aj-kpi-label">Avec rÃ©servations</span>
                            <strong class="aj-kpi-value">{{ $withReservationsCount }}</strong>
                            <span class="aj-kpi-note">Historique actif</span>
                        </div>
                    </div>
                </article>
            </section>

            <section class="aj-panel">
                <form method="GET">
                    <div class="aj-filter-grid">
                        <div class="aj-field aj-search-wrap aj-col-4">
                            <label for="search">Recherche</label>
                            <span class="aj-search-icon"><i class="bx bx-search"></i></span>
                            <input id="search" type="text" name="search" class="aj-control" placeholder="Code, nom, tÃ©lÃ©phone, email, CIN, passeport..." value="{{ request('search') }}">
                        </div>
                        <div class="aj-field aj-col-2">
                            <label for="status">Statut</label>
                            <select id="status" name="status" class="aj-control">
                                <option value="">Tous</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                                <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>BloquÃ©</option>
                                <option value="vip" {{ request('status') === 'vip' ? 'selected' : '' }}>VIP</option>
                            </select>
                        </div>
                        <div class="aj-field aj-col-2">
                            <label for="per_page">Par page</label>
                            <select id="per_page" name="per_page" class="aj-control">
                                @foreach([20, 50, 100] as $pp)
                                    <option value="{{ $pp }}" {{ (int)request('per_page', 20) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="aj-col-2 d-flex flex-wrap gap-2">
                            <button type="submit" class="aj-btn aj-btn-primary w-100">
                                <i class="bx bx-filter-alt"></i>
                                <span>Filtrer</span>
                            </button>
                        </div>
                        <div class="aj-col-2 d-flex flex-wrap gap-2">
                            <a href="{{ route('admin.customers.voyageurs') }}" class="aj-btn aj-btn-soft w-100">
                                <i class="bx bx-reset"></i>
                                <span>RÃ©initialiser</span>
                            </a>
                        </div>
                    </div>
                </form>

                <div class="aj-filter-chips">
                    <span>Filtres actifs :</span>
                    @forelse($activeFilters as $filterLabel)
                        <span class="aj-chip">{{ $filterLabel }}</span>
                    @empty
                        <span class="text-muted">Aucun filtre actif.</span>
                    @endforelse
                </div>
            </section>

            <section class="aj-panel">
                <div class="aj-toolbar">
                    <div class="aj-result-meta">
                        <div class="d-flex align-items-center gap-2">
                            <label for="voyageurSortSelect" class="mb-0">Trier localement :</label>
                            <select id="voyageurSortSelect" class="aj-mini-select">
                                <option value="recent">Plus rÃ©cents</option>
                                <option value="name_asc">Nom A-Z</option>
                                <option value="reservations_desc">RÃ©servations</option>
                            </select>
                        </div>
                        <button type="button" class="aj-mini-btn" id="voyageurExportBtn">
                            <i class="bx bx-export"></i>
                            <span>Exporter la vue</span>
                        </button>
                        <span>{{ $clients->firstItem() ?? 0 }} - {{ $clients->lastItem() ?? 0 }} sur {{ $totalClients }} voyageurs</span>
                    </div>
                    <div class="aj-result-meta">
                        <div class="aj-view-toggle">
                            <button type="button" class="is-active" data-view="table" aria-pressed="true"><i class="bx bx-list-ul"></i></button>
                            <button type="button" data-view="grid" aria-pressed="false"><i class="bx bx-grid-alt"></i></button>
                        </div>
                    </div>
                </div>

                @if($clients->isEmpty())
                    <div class="aj-empty">
                        <h5 class="mb-2">Aucun voyageur trouvÃ©</h5>
                        <p class="text-muted mb-3">CrÃ©ez un voyageur pour enrichir la base clients.</p>
                        <a href="{{ route('admin.customers.clients.create') }}" class="aj-btn aj-btn-primary">
                            <i class="bx bx-plus"></i>
                            <span>CrÃ©er un voyageur</span>
                        </a>
                    </div>
                @else
                    <div class="aj-table-wrap" data-catalog-view="table">
                        <table class="aj-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Voyageur</th>
                                    <th>TÃ©lÃ©phone</th>
                                    <th>Email</th>
                                    <th>Login</th>
                                    <th>RÃ©servations</th>
                                    <th>Ville</th>
                                    <th>IdentitÃ©</th>
                                    <th>Statut</th>
                                    <th>CrÃ©Ã© le</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clients as $c)
                                    @php
                                        $idDoc = $c->national_id_number ?: ($c->passport_number ?: null);
                                        $fullName = $c->full_name ?: trim(($c->first_name ?? '').' '.($c->last_name ?? '')) ?: 'â€”';
                                        $createdTimestamp = $c->created_at?->timestamp ?? 0;
                                        $reservationCount = (int) ($c->reservations_count ?? 0);
                                    @endphp
                                    <tr
                                        data-title="{{ Str::lower($fullName) }}"
                                        data-reservations="{{ $reservationCount }}"
                                        data-created="{{ $createdTimestamp }}"
                                    >
                                        <td><code>{{ $c->client_code }}</code></td>
                                        <td>
                                            <div class="aj-item-title">
                                                <a href="{{ route('admin.customers.clients.show', $c) }}">{{ $fullName }}</a>
                                                @if($c->status === 'vip')
                                                    <span class="aj-badge -info">VIP</span>
                                                @endif
                                            </div>
                                            <div class="aj-meta-text">{{ $c->whatsapp_number ?: 'WhatsApp non renseignÃ©' }}</div>
                                        </td>
                                        <td>{{ $c->phone ?? 'â€”' }}</td>
                                        <td>{{ $c->email ?? 'â€”' }}</td>
                                        <td>
                                            @if($c->portal_username)
                                                <code>{{ $c->portal_username }}</code>
                                            @else
                                                <span class="aj-meta-text">â€”</span>
                                            @endif
                                        </td>
                                        <td><span class="aj-badge -neutral">{{ $reservationCount }}</span></td>
                                        <td>{{ $c->city ?? 'â€”' }}</td>
                                        <td>{{ $idDoc ?: 'â€”' }}</td>
                                        <td>
                                            @if($c->status === 'active')
                                                <span class="aj-badge -success">Actif</span>
                                            @elseif($c->status === 'inactive')
                                                <span class="aj-badge -warning">Inactif</span>
                                            @elseif($c->status === 'blocked')
                                                <span class="aj-badge -danger">BloquÃ©</span>
                                            @elseif($c->status === 'vip')
                                                <span class="aj-badge -info">VIP</span>
                                            @else
                                                <span class="aj-badge -neutral">{{ $c->status ?? 'â€”' }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $c->created_at?->format('d/m/Y') ?? 'â€”' }}</td>
                                        <td class="text-end">
                                            <div class="aj-actions">
                                                <a href="{{ route('admin.customers.clients.show', $c) }}" class="aj-icon-btn" title="Voir">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('admin.customers.clients.edit', $c) }}" class="aj-icon-btn" title="Modifier">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="aj-grid" data-catalog-view="grid">
                        @foreach($clients as $c)
                            @php
                                $fullName = $c->full_name ?: trim(($c->first_name ?? '').' '.($c->last_name ?? '')) ?: 'â€”';
                                $reservationCount = (int) ($c->reservations_count ?? 0);
                            @endphp
                            <article
                                class="aj-card"
                                data-title="{{ Str::lower($fullName) }}"
                                data-reservations="{{ $reservationCount }}"
                                data-created="{{ $c->created_at?->timestamp ?? 0 }}"
                            >
                                <div class="aj-card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <h4 class="aj-card-title"><a href="{{ route('admin.customers.clients.show', $c) }}">{{ $fullName }}</a></h4>
                                            <div class="aj-meta-text">{{ $c->client_code }}</div>
                                        </div>
                                        @if($c->status === 'vip')
                                            <span class="aj-badge -info">VIP</span>
                                        @endif
                                    </div>
                                    <div class="aj-meta-text mb-2">{{ $c->email ?? 'Email non renseignÃ©' }}</div>
                                    <div class="aj-meta-text mb-3">{{ $c->phone ?? 'TÃ©lÃ©phone non renseignÃ©' }}</div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="aj-badge -neutral">{{ $reservationCount }} rÃ©servation(s)</span>
                                        <span class="aj-badge {{ $c->status === 'active' ? '-success' : ($c->status === 'inactive' ? '-warning' : ($c->status === 'blocked' ? '-danger' : '-neutral')) }}">
                                            {{ $c->status ?? 'â€”' }}
                                        </span>
                                    </div>
                                    <div class="aj-card-actions">
                                        <span class="aj-meta-text">{{ $c->city ?? 'Ville non renseignÃ©e' }}</span>
                                        <div class="aj-actions">
                                            <a href="{{ route('admin.customers.clients.show', $c) }}" class="aj-icon-btn" title="Voir"><i class="bx bx-show"></i></a>
                                            <a href="{{ route('admin.customers.clients.edit', $c) }}" class="aj-icon-btn" title="Modifier"><i class="bx bx-pencil"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="aj-footer">
                        <div>Affichage de {{ $clients->firstItem() ?? 0 }} Ã  {{ $clients->lastItem() ?? 0 }} sur {{ $totalClients }} rÃ©sultats</div>
                        <div>{{ $clients->links() }}</div>
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
            const sortSelect = document.getElementById('voyageurSortSelect');
            const exportBtn = document.getElementById('voyageurExportBtn');

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
                    const reservationsA = Number(a.dataset.reservations || 0);
                    const reservationsB = Number(b.dataset.reservations || 0);
                    const createdA = Number(a.dataset.created || 0);
                    const createdB = Number(b.dataset.created || 0);

                    if (mode === 'name_asc') return titleA.localeCompare(titleB, 'fr');
                    if (mode === 'reservations_desc') return reservationsB - reservationsA;
                    return createdB - createdA;
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

            toggleButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    setView(this.dataset.view || 'table');
                });
            });

            if (sortSelect) {
                sortSelect.addEventListener('change', function () {
                    sortNodes(this.value || 'recent');
                });
            }

            if (exportBtn) {
                exportBtn.addEventListener('click', function () {
                    window.print();
                });
            }

            setView('table');
            sortNodes(sortSelect ? sortSelect.value : 'recent');
        });
    </script>
@endpush

