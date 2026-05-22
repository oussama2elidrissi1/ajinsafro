

<?php
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
                    <a href="<?php echo e(route('admin.circuits.activities.create')); ?>" class="aj-btn aj-btn-primary">
                        <i class="bx bx-plus"></i>
                        <span>Nouvelle activité</span>
                    </a>
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
                        <div class="aj-kpi-icon -blue"><i class="bx bx-camera-movie"></i></div>
                        <div>
                            <span class="aj-kpi-label">Total activités</span>
                            <strong class="aj-kpi-value"><?php echo e(number_format($totalActivities, 0, ',', ' ')); ?></strong>
                            <span class="aj-kpi-note">Catalogue courant</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -green"><i class="bx bx-badge-check"></i></div>
                        <div>
                            <span class="aj-kpi-label">Actives</span>
                            <strong class="aj-kpi-value"><?php echo e($activeCount); ?></strong>
                            <span class="aj-kpi-note">Sur la page affichée</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -orange"><i class="bx bx-pause-circle"></i></div>
                        <div>
                            <span class="aj-kpi-label">Inactives</span>
                            <strong class="aj-kpi-value"><?php echo e($inactiveCount); ?></strong>
                            <span class="aj-kpi-note">�? vérifier</span>
                        </div>
                    </div>
                </article>
                <article class="aj-kpi">
                    <div class="aj-kpi-head">
                        <div class="aj-kpi-icon -violet"><i class="bx bx-images"></i></div>
                        <div>
                            <span class="aj-kpi-label">Avec galerie</span>
                            <strong class="aj-kpi-value"><?php echo e($withGalleryCount); ?></strong>
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
                        <span><?php echo e($activities->firstItem() ?? 0); ?> - <?php echo e($activities->lastItem() ?? 0); ?> sur <?php echo e($totalActivities); ?> activités</span>
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
                <?php if($activities->isEmpty()): ?>
                    <div class="aj-empty">
                        <h5 class="mb-2">Aucune activité disponible</h5>
                        <p class="text-muted mb-3">Créez la première activité pour alimenter le catalogue.</p>
                        <a href="<?php echo e(route('admin.circuits.activities.create')); ?>" class="aj-btn aj-btn-primary">
                            <i class="bx bx-plus"></i>
                            <span>Créer une activité</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="aj-table-wrap" data-catalog-view="table">
                        <table class="aj-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Visuel</th>
                                    <th>Activité</th>
                                    <th>Région</th>
                                    <th>Tarifs</th>
                                    <th>�,ges</th>
                                    <th>Durée</th>
                                    <th>Galerie</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
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
                                    ?>
                                    <tr
                                        data-title="<?php echo e(Str::lower($activity->title)); ?>"
                                        data-filter="<?php echo e(Str::lower(($activity->title ?? '') . ' ' . ($activity->region_name ?? '') . ' ' . ($activity->activity_type ?? ''))); ?>"
                                        data-price="<?php echo e($adultPrice); ?>"
                                        data-duration="<?php echo e($duration); ?>"
                                    >
                                        <td><strong>#<?php echo e($activity->id); ?></strong></td>
                                        <td>
                                            <div class="aj-thumb">
                                                <?php if($coverUrl): ?>
                                                    <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($activity->title); ?>">
                                                <?php else: ?>
                                                    <div class="aj-thumb-placeholder">Ajinsafro</div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="aj-item-title">
                                                <a href="<?php echo e(route('admin.circuits.activities.edit', $activity)); ?>"><?php echo e($activity->title); ?></a>
                                                <?php if($activity->is_active): ?>
                                                    <span class="aj-badge -success">Active</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="aj-meta-text"><?php echo e($activity->activity_type ?: 'Type non renseigné'); ?></div>
                                            <div class="aj-meta-text"><code><?php echo e($activity->slug); ?></code></div>
                                        </td>
                                        <td><?php echo e($activity->region_name ?: $activity->location_text ?: '-'); ?></td>
                                        <td>
                                            <div class="aj-price"><?php echo e(number_format($adultPrice, 2, ',', ' ')); ?> MAD</div>
                                            <div class="aj-meta-text">Enfant : <?php echo e(number_format((float) ($activity->child_price ?? 0), 2, ',', ' ')); ?> MAD</div>
                                        </td>
                                        <td>
                                            <div class="aj-meta-text">Min : <?php echo e($activity->min_age ?? '-'); ?> ans</div>
                                            <div class="aj-meta-text">Max : <?php echo e($activity->max_age ?? '-'); ?> ans</div>
                                        </td>
                                        <td><?php echo e($duration ? $duration . ' min' : '-'); ?></td>
                                        <td><span class="aj-badge -neutral"><?php echo e($galleryIds->count()); ?> image(s)</span></td>
                                        <td>
                                            <?php if($activity->is_active): ?>
                                                <span class="aj-badge -success">Active</span>
                                            <?php else: ?>
                                                <span class="aj-badge -neutral">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="aj-actions">
                                                <a href="<?php echo e(route('admin.circuits.activities.edit', $activity)); ?>" class="aj-icon-btn" title="Modifier">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                                <form action="<?php echo e(route('admin.circuits.activities.destroy', $activity)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette activite ?');">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="aj-icon-btn -danger" title="Supprimer">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="aj-grid" data-catalog-view="grid">
                        <?php $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
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
                            ?>
                            <article
                                class="aj-card"
                                data-title="<?php echo e(Str::lower($activity->title)); ?>"
                                data-filter="<?php echo e(Str::lower(($activity->title ?? '') . ' ' . ($activity->region_name ?? '') . ' ' . ($activity->activity_type ?? ''))); ?>"
                                data-price="<?php echo e($adultPrice); ?>"
                                data-duration="<?php echo e($duration); ?>"
                            >
                                <div class="aj-card-cover">
                                    <?php if($coverUrl): ?>
                                        <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($activity->title); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="aj-card-body">
                                    <h4 class="aj-card-title"><a href="<?php echo e(route('admin.circuits.activities.edit', $activity)); ?>"><?php echo e($activity->title); ?></a></h4>
                                    <div class="aj-meta-text mb-2"><?php echo e($activity->region_name ?: $activity->location_text ?: 'Région non renseignée'); ?></div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="aj-badge -neutral"><?php echo e($activity->activity_type ?: 'Type libre'); ?></span>
                                        <?php if($activity->is_active): ?>
                                            <span class="aj-badge -success">Active</span>
                                        <?php else: ?>
                                            <span class="aj-badge -neutral">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="aj-card-actions">
                                        <span class="aj-price"><?php echo e(number_format($adultPrice, 2, ',', ' ')); ?> MAD</span>
                                        <div class="aj-actions">
                                            <a href="<?php echo e(route('admin.circuits.activities.edit', $activity)); ?>" class="aj-icon-btn" title="Modifier"><i class="bx bx-pencil"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div class="aj-footer">
                        <div>Affichage de <?php echo e($activities->firstItem() ?? 0); ?> à <?php echo e($activities->lastItem() ?? 0); ?> sur <?php echo e($totalActivities); ?> résultats</div>
                        <div><?php echo e($activities->links()); ?></div>
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
<?php $__env->stopPush(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\activities\index.blade.php ENDPATH**/ ?>