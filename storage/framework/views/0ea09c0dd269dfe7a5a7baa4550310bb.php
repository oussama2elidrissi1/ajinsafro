

<?php
    use Illuminate\Support\Str;

    $pageTitle = 'Prospects';
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
?>

<?php $__env->startSection('title', $pageTitle); ?>

<?php $__env->startPush('styles'); ?>
    <link href="<?php echo e(URL::asset('css/admin-catalog-premium.css')); ?>" rel="stylesheet" type="text/css" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="aj-catalog-page">
        <div class="aj-catalog-shell">
            <div class="aj-catalog-head">
                <div>
                    <h1 class="aj-catalog-title"><?php echo e($pageTitle); ?></h1>
                    <p class="aj-catalog-subtitle">Liste des prospects : clients liés à des réservations non confirmées (en attente, option, brouillon...).</p>
                </div>
                <div>
                    <div class="aj-catalog-breadcrumb">
                        <span>Admin</span>
                        <span>/</span>
                        <span>Clients</span>
                        <span>/</span>
                        <strong style="color:#0b1f3a">Prospects</strong>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo e(route('admin.customers.clients.index')); ?>" class="aj-btn aj-btn-soft">
                            <i class="bx bx-list-ul"></i>
                            <span>Vue complète</span>
                        </a>
                        <a href="<?php echo e(route('admin.customers.clients.create')); ?>" class="aj-btn aj-btn-primary">
                            <i class="bx bx-plus"></i>
                            <span>Nouveau prospect</span>
                        </a>
                    </div>
                </div>
            </div>

            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section class="aj-kpis">
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -blue"><i class="bx bx-user"></i></div>
                        <div>
                            <span class="aj-kpi-label">Total voyageurs</span>
                            <strong class="aj-kpi-value"><?php echo e(number_format($totalClients, 0, ',', ' ')); ?></strong>
                            <span class="aj-kpi-note">Base clients filtrée</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -green"><i class="bx bx-badge-check"></i></div>
                        <div>
                            <span class="aj-kpi-label">Actifs</span>
                            <strong class="aj-kpi-value"><?php echo e($activeCount); ?></strong>
                            <span class="aj-kpi-note">Sur la page affichée</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -violet"><i class="bx bx-crown"></i></div>
                        <div>
                            <span class="aj-kpi-label">VIP</span>
                            <strong class="aj-kpi-value"><?php echo e($vipCount); ?></strong>
                            <span class="aj-kpi-note">Clients premium</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -orange"><i class="bx bx-receipt"></i></div>
                        <div>
                            <span class="aj-kpi-label">Avec réservations</span>
                            <strong class="aj-kpi-value"><?php echo e($withReservationsCount); ?></strong>
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
                            <input id="search" type="text" name="search" class="aj-control" placeholder="Code, nom, téléphone, email, CIN, passeport..." value="<?php echo e(request('search')); ?>">
                        </div>
                        <div class="aj-field aj-col-2">
                            <label for="status">Statut</label>
                            <select id="status" name="status" class="aj-control">
                                <option value="">Tous</option>
                                <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Actif</option>
                                <option value="inactive" <?php echo e(request('status') === 'inactive' ? 'selected' : ''); ?>>Inactif</option>
                                <option value="blocked" <?php echo e(request('status') === 'blocked' ? 'selected' : ''); ?>>Bloqué</option>
                                <option value="vip" <?php echo e(request('status') === 'vip' ? 'selected' : ''); ?>>VIP</option>
                            </select>
                        </div>
                        <div class="aj-field aj-col-2">
                            <label for="per_page">Par page</label>
                            <select id="per_page" name="per_page" class="aj-control">
                                <?php $__currentLoopData = [20, 50, 100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($pp); ?>" <?php echo e((int)request('per_page', 20) === $pp ? 'selected' : ''); ?>><?php echo e($pp); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="aj-col-2 d-flex flex-wrap gap-2">
                            <button type="submit" class="aj-btn aj-btn-primary w-100">
                                <i class="bx bx-filter-alt"></i>
                                <span>Filtrer</span>
                            </button>
                        </div>
                        <div class="aj-col-2 d-flex flex-wrap gap-2">
                            <a href="<?php echo e(route('admin.customers.prospects')); ?>" class="aj-btn aj-btn-soft w-100">
                                <i class="bx bx-reset"></i>
                                <span>Réinitialiser</span>
                            </a>
                        </div>
                    </div>
                </form>

                <div class="aj-filter-chips">
                    <span>Filtres actifs :</span>
                    <?php $__empty_1 = true; $__currentLoopData = $activeFilters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filterLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <span class="aj-chip"><?php echo e($filterLabel); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <span class="text-muted">Aucun filtre actif.</span>
                    <?php endif; ?>
                </div>
            </section>

            <section class="aj-panel">
                <div class="aj-toolbar">
                    <div class="aj-result-meta">
                        <div class="d-flex align-items-center gap-2">
                            <label for="voyageurSortSelect" class="mb-0">Trier localement :</label>
                            <select id="voyageurSortSelect" class="aj-mini-select">
                                <option value="recent">Plus récents</option>
                                <option value="name_asc">Nom A-Z</option>
                                <option value="reservations_desc">Réservations</option>
                            </select>
                        </div>
                        <button type="button" class="aj-mini-btn" id="voyageurExportBtn">
                            <i class="bx bx-export"></i>
                            <span>Exporter la vue</span>
                        </button>
                        <span><?php echo e($clients->firstItem() ?? 0); ?> - <?php echo e($clients->lastItem() ?? 0); ?> sur <?php echo e($totalClients); ?> prospects</span>
                    </div>
                    <div class="aj-result-meta">
                        <div class="aj-view-toggle">
                            <button type="button" class="is-active" data-view="table" aria-pressed="true"><i class="bx bx-list-ul"></i></button>
                            <button type="button" data-view="grid" aria-pressed="false"><i class="bx bx-grid-alt"></i></button>
                        </div>
                    </div>
                </div>

                <?php if($clients->isEmpty()): ?>
                    <div class="aj-empty">
                        <h5 class="mb-2">Aucun prospect trouvé</h5>
                        <p class="text-muted mb-3">Créez un prospect ou enregistrez une réservation non confirmée.</p>
                        <a href="<?php echo e(route('admin.customers.clients.create')); ?>" class="aj-btn aj-btn-primary">
                            <i class="bx bx-plus"></i>
                            <span>Créer un prospect</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="aj-table-wrap" data-catalog-view="table">
                        <table class="aj-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Prospect</th>
                                    <th>Téléphone</th>
                                    <th>Email</th>
                                    <th>Login</th>
                                    <th>Réservations</th>
                                    <th>Ville</th>
                                    <th>Identité</th>
                                    <th>Statut</th>
                                    <th>Créé le</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $idDoc = $c->national_id_number ?: ($c->passport_number ?: null);
                                        $fullName = $c->full_name ?: trim(($c->first_name ?? '').' '.($c->last_name ?? '')) ?: '�?"';
                                        $createdTimestamp = $c->created_at?->timestamp ?? 0;
                                        $reservationCount = (int) ($c->reservations_count ?? 0);
                                    ?>
                                    <tr
                                        data-title="<?php echo e(Str::lower($fullName)); ?>"
                                        data-reservations="<?php echo e($reservationCount); ?>"
                                        data-created="<?php echo e($createdTimestamp); ?>"
                                    >
                                        <td><code><?php echo e($c->client_code); ?></code></td>
                                        <td>
                                            <div class="aj-item-title">
                                                <a href="<?php echo e(route('admin.customers.clients.show', $c)); ?>"><?php echo e($fullName); ?></a>
                                                <?php if($c->status === 'vip'): ?>
                                                    <span class="aj-badge -info">VIP</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="aj-meta-text"><?php echo e($c->whatsapp_number ?: 'WhatsApp non renseigné'); ?></div>
                                        </td>
                                        <td><?php echo e($c->phone ?? '�?"'); ?></td>
                                        <td><?php echo e($c->email ?? '�?"'); ?></td>
                                        <td>
                                            <?php if($c->portal_username): ?>
                                                <code><?php echo e($c->portal_username); ?></code>
                                            <?php else: ?>
                                                <span class="aj-meta-text">�?"</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="aj-badge -neutral"><?php echo e($reservationCount); ?></span></td>
                                        <td><?php echo e($c->city ?? '�?"'); ?></td>
                                        <td><?php echo e($idDoc ?: '�?"'); ?></td>
                                        <td>
                                            <?php if($c->status === 'active'): ?>
                                                <span class="aj-badge -success">Actif</span>
                                            <?php elseif($c->status === 'inactive'): ?>
                                                <span class="aj-badge -warning">Inactif</span>
                                            <?php elseif($c->status === 'blocked'): ?>
                                                <span class="aj-badge -danger">Bloqué</span>
                                            <?php elseif($c->status === 'vip'): ?>
                                                <span class="aj-badge -info">VIP</span>
                                            <?php else: ?>
                                                <span class="aj-badge -neutral"><?php echo e($c->status ?? '�?"'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($c->created_at?->format('d/m/Y') ?? '�?"'); ?></td>
                                        <td class="text-end">
                                            <div class="aj-actions">
                                                <a href="<?php echo e(route('admin.customers.clients.show', $c)); ?>" class="aj-icon-btn" title="Voir">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="<?php echo e(route('admin.customers.clients.edit', $c)); ?>" class="aj-icon-btn" title="Modifier">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="aj-grid" data-catalog-view="grid">
                        <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $fullName = $c->full_name ?: trim(($c->first_name ?? '').' '.($c->last_name ?? '')) ?: '�?"';
                                $reservationCount = (int) ($c->reservations_count ?? 0);
                            ?>
                            <article
                                class="aj-card"
                                data-title="<?php echo e(Str::lower($fullName)); ?>"
                                data-reservations="<?php echo e($reservationCount); ?>"
                                data-created="<?php echo e($c->created_at?->timestamp ?? 0); ?>"
                            >
                                <div class="aj-card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <h4 class="aj-card-title"><a href="<?php echo e(route('admin.customers.clients.show', $c)); ?>"><?php echo e($fullName); ?></a></h4>
                                            <div class="aj-meta-text"><?php echo e($c->client_code); ?></div>
                                        </div>
                                        <?php if($c->status === 'vip'): ?>
                                            <span class="aj-badge -info">VIP</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="aj-meta-text mb-2"><?php echo e($c->email ?? 'Email non renseigné'); ?></div>
                                    <div class="aj-meta-text mb-3"><?php echo e($c->phone ?? 'Téléphone non renseigné'); ?></div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="aj-badge -neutral"><?php echo e($reservationCount); ?> réservation(s)</span>
                                        <span class="aj-badge <?php echo e($c->status === 'active' ? '-success' : ($c->status === 'inactive' ? '-warning' : ($c->status === 'blocked' ? '-danger' : '-neutral'))); ?>">
                                            <?php echo e($c->status ?? '�?"'); ?>

                                        </span>
                                    </div>
                                    <div class="aj-card-actions">
                                        <span class="aj-meta-text"><?php echo e($c->city ?? 'Ville non renseignée'); ?></span>
                                        <div class="aj-actions">
                                            <a href="<?php echo e(route('admin.customers.clients.show', $c)); ?>" class="aj-icon-btn" title="Voir"><i class="bx bx-show"></i></a>
                                            <a href="<?php echo e(route('admin.customers.clients.edit', $c)); ?>" class="aj-icon-btn" title="Modifier"><i class="bx bx-pencil"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div class="aj-footer">
                        <div>Affichage de <?php echo e($clients->firstItem() ?? 0); ?> à <?php echo e($clients->lastItem() ?? 0); ?> sur <?php echo e($totalClients); ?> résultats</div>
                        <div><?php echo e($clients->links()); ?></div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
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
<?php $__env->stopPush(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\customers\prospects\index.blade.php ENDPATH**/ ?>