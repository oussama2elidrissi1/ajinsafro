

<?php
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
?>

<?php $__env->startSection('title', 'Voyages'); ?>

<?php $__env->startPush('styles'); ?>
    <link href="<?php echo e(URL::asset('css/admin-catalog-premium.css')); ?>" rel="stylesheet" type="text/css" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="aj-catalog-page">
        <div class="aj-catalog-shell">
            <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => $pageTitle,'subtitle' => 'Pilotez les offres, départs, prix et publications depuis une vue unique, sans changer la logique métier existante.','breadcrumbs' => [
                    ['label' => 'Admin', 'url' => route('admin.dashboard')],
                    ['label' => 'Circuits', 'url' => '#'],
                    ['label' => 'Voyages'],
                ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pageTitle),'subtitle' => 'Pilotez les offres, départs, prix et publications depuis une vue unique, sans changer la logique métier existante.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                    ['label' => 'Admin', 'url' => route('admin.dashboard')],
                    ['label' => 'Circuits', 'url' => '#'],
                    ['label' => 'Voyages'],
                ])]); ?>
                 <?php $__env->slot('actions', null, []); ?> 
                    <a href="<?php echo e(route('admin.circuits.voyages.create')); ?>" class="aj-btn aj-btn-soft">
                        <i class="bx bx-plus"></i>
                        <span>Créer un tour</span>
                    </a>
                    <a href="<?php echo e(route('admin.circuits.voyages.create-v2')); ?>" class="aj-btn aj-btn-primary">
                        <i class="bx bx-plus"></i>
                        <span>Créer en V2</span>
                    </a>
                 <?php $__env->endSlot(); ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $attributes = $__attributesOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__attributesOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcb19cb35a534439097b02b8af91726ee)): ?>
<?php $component = $__componentOriginalcb19cb35a534439097b02b8af91726ee; ?>
<?php unset($__componentOriginalcb19cb35a534439097b02b8af91726ee); ?>
<?php endif; ?>

            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(!empty($wpConnectionFailed)): ?>
                <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                    <strong>Connexion catalogue indisponible.</strong>
                    Le chargement des voyages est temporairement indisponible. Veuillez réessayer dans quelques instants.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(!empty($wpCatalogErrorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <strong>Chargement de la liste impossible.</strong> <?php echo e($wpCatalogErrorMessage); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($component)) { $__componentOriginaldc8ea6d1c156289736a271a64b9dc41b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldc8ea6d1c156289736a271a64b9dc41b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.kpi-cards','data' => ['kpis' => [
                    ['label' => 'Voyages trouvés', 'value' => number_format($totalTours, 0, ',', ' '), 'icon' => 'bx bx-map-alt', 'color' => '-blue', 'note' => 'Catalogue courant'],
                    ['label' => 'Publiés', 'value' => $publishedTours, 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Visibles dans le catalogue'],
                    ['label' => 'Brouillons', 'value' => $draftTours, 'icon' => 'bx bx-edit-alt', 'color' => '-orange', 'note' => '�? finaliser'],
                    ['label' => 'Avec départs actifs', 'value' => $withDepartures, 'icon' => 'bx bx-calendar-check', 'color' => '-violet', 'note' => 'Basé sur les données catalogue'],
                ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.kpi-cards'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['kpis' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                    ['label' => 'Voyages trouvés', 'value' => number_format($totalTours, 0, ',', ' '), 'icon' => 'bx bx-map-alt', 'color' => '-blue', 'note' => 'Catalogue courant'],
                    ['label' => 'Publiés', 'value' => $publishedTours, 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Visibles dans le catalogue'],
                    ['label' => 'Brouillons', 'value' => $draftTours, 'icon' => 'bx bx-edit-alt', 'color' => '-orange', 'note' => '�? finaliser'],
                    ['label' => 'Avec départs actifs', 'value' => $withDepartures, 'icon' => 'bx bx-calendar-check', 'color' => '-violet', 'note' => 'Basé sur les données catalogue'],
                ])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldc8ea6d1c156289736a271a64b9dc41b)): ?>
<?php $attributes = $__attributesOriginaldc8ea6d1c156289736a271a64b9dc41b; ?>
<?php unset($__attributesOriginaldc8ea6d1c156289736a271a64b9dc41b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldc8ea6d1c156289736a271a64b9dc41b)): ?>
<?php $component = $__componentOriginaldc8ea6d1c156289736a271a64b9dc41b; ?>
<?php unset($__componentOriginaldc8ea6d1c156289736a271a64b9dc41b); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal775da4db6660fa0c0efa99eeb44c6fa5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal775da4db6660fa0c0efa99eeb44c6fa5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.filter-panel','data' => ['action' => route('admin.circuits.voyages.index'),'method' => 'GET','resetUrl' => route('admin.circuits.voyages.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.filter-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.circuits.voyages.index')),'method' => 'GET','reset-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.circuits.voyages.index'))]); ?>
                 <?php $__env->slot('fields', null, []); ?> 
                    <div class="aj-field aj-search-wrap aj-col-3">
                        <label for="q">Recherche</label>
                        <span class="aj-search-icon"><i class="bx bx-search"></i></span>
                        <input id="q" type="search" name="q" class="aj-control" value="<?php echo e(request('q')); ?>" placeholder="Titre, slug...">
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="status">Statut</label>
                        <select id="status" name="status" class="aj-control">
                            <option value="">Tous</option>
                            <option value="publish" <?php if(request('status') === 'publish'): echo 'selected'; endif; ?>>Publié</option>
                            <option value="draft" <?php if(request('status') === 'draft'): echo 'selected'; endif; ?>>Brouillon</option>
                            <option value="private" <?php if(request('status') === 'private'): echo 'selected'; endif; ?>>Archivé</option>
                            <option value="pending" <?php if(request('status') === 'pending'): echo 'selected'; endif; ?>>En attente</option>
                        </select>
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="tour_type">Type / thème</label>
                        <select id="tour_type" name="tour_type" class="aj-control">
                            <option value="">Tous</option>
                            <?php $__currentLoopData = $filterTourTypes ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($tt['term_id']); ?>" <?php if((string) request('tour_type') === (string) $tt['term_id']): echo 'selected'; endif; ?>><?php echo e($tt['name']); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="destination">Destination</label>
                        <input id="destination" type="text" name="destination" class="aj-control" value="<?php echo e(request('destination')); ?>" placeholder="Ville, pays...">
                    </div>
                    <div class="aj-field aj-col-1">
                        <label for="price_min">Prix min</label>
                        <input id="price_min" type="number" step="0.01" name="price_min" class="aj-control" value="<?php echo e(request('price_min')); ?>">
                    </div>
                    <div class="aj-field aj-col-1">
                        <label for="price_max">Prix max</label>
                        <input id="price_max" type="number" step="0.01" name="price_max" class="aj-control" value="<?php echo e(request('price_max')); ?>">
                    </div>
                    <div class="aj-field aj-col-1">
                        <label for="duration_min">Durée min</label>
                        <input id="duration_min" type="number" min="1" name="duration_min" class="aj-control" value="<?php echo e(request('duration_min')); ?>">
                    </div>
                    <div class="aj-field aj-col-1">
                        <label for="duration_max">Durée max</label>
                        <input id="duration_max" type="number" min="1" name="duration_max" class="aj-control" value="<?php echo e(request('duration_max')); ?>">
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="modified_from">Modifié du</label>
                        <input id="modified_from" type="date" name="modified_from" class="aj-control" value="<?php echo e(request('modified_from')); ?>">
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="modified_to">au</label>
                        <input id="modified_to" type="date" name="modified_to" class="aj-control" value="<?php echo e(request('modified_to')); ?>">
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="has_departures">Départs actifs</label>
                        <select id="has_departures" name="has_departures" class="aj-control">
                            <option value="">Indifférent</option>
                            <option value="1" <?php if(request('has_departures') === '1'): echo 'selected'; endif; ?>>Oui</option>
                            <option value="0" <?php if(request('has_departures') === '0'): echo 'selected'; endif; ?>>Non</option>
                        </select>
                    </div>
                    <div class="aj-field aj-col-2">
                        <label for="has_laravel_public">Page Laravel publique</label>
                        <select id="has_laravel_public" name="has_laravel_public" class="aj-control">
                            <option value="">Indifférent</option>
                            <option value="1" <?php if(request('has_laravel_public') === '1'): echo 'selected'; endif; ?>>Oui</option>
                            <option value="0" <?php if(request('has_laravel_public') === '0'): echo 'selected'; endif; ?>>Non</option>
                        </select>
                    </div>
                 <?php $__env->endSlot(); ?>

                 <?php $__env->slot('chips', null, []); ?> 
                    <?php $__empty_1 = true; $__currentLoopData = $activeFilters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filterLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <span class="aj-chip"><?php echo e($filterLabel); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <span class="text-muted">Aucun filtre actif.</span>
                    <?php endif; ?>
                 <?php $__env->endSlot(); ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal775da4db6660fa0c0efa99eeb44c6fa5)): ?>
<?php $attributes = $__attributesOriginal775da4db6660fa0c0efa99eeb44c6fa5; ?>
<?php unset($__attributesOriginal775da4db6660fa0c0efa99eeb44c6fa5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal775da4db6660fa0c0efa99eeb44c6fa5)): ?>
<?php $component = $__componentOriginal775da4db6660fa0c0efa99eeb44c6fa5; ?>
<?php unset($__componentOriginal775da4db6660fa0c0efa99eeb44c6fa5); ?>
<?php endif; ?>

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
                        <span><?php echo e($tours->firstItem() ?? 0); ?> - <?php echo e($tours->lastItem() ?? 0); ?> sur <?php echo e($tours->total()); ?> voyages</span>
                    </div>
                    <div class="aj-result-meta">
                        <span>Vue :</span>
                        <div class="aj-view-toggle">
                            <button type="button" class="is-active" data-view="table" aria-pressed="true"><i class="bx bx-list-ul"></i></button>
                            <button type="button" data-view="grid" aria-pressed="false"><i class="bx bx-grid-alt"></i></button>
                        </div>
                    </div>
                </div>

                <?php if($tours->isEmpty()): ?>
                    <?php if (isset($component)) { $__componentOriginal99089f8e2ef4184d7d35db81d60c6521 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal99089f8e2ef4184d7d35db81d60c6521 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.empty-state','data' => ['title' => 'Aucun voyage trouvé','message' => 'Ajustez vos filtres ou créez un nouveau voyage.','actionUrl' => route('admin.circuits.voyages.create'),'actionLabel' => 'Créer un voyage']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Aucun voyage trouvé','message' => 'Ajustez vos filtres ou créez un nouveau voyage.','action-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.circuits.voyages.create')),'action-label' => 'Créer un voyage']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal99089f8e2ef4184d7d35db81d60c6521)): ?>
<?php $attributes = $__attributesOriginal99089f8e2ef4184d7d35db81d60c6521; ?>
<?php unset($__attributesOriginal99089f8e2ef4184d7d35db81d60c6521); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal99089f8e2ef4184d7d35db81d60c6521)): ?>
<?php $component = $__componentOriginal99089f8e2ef4184d7d35db81d60c6521; ?>
<?php unset($__componentOriginal99089f8e2ef4184d7d35db81d60c6521); ?>
<?php endif; ?>
                <?php else: ?>
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
                                <?php $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $price = is_numeric($tour->adult_price ?? null) ? (float) $tour->adult_price : 0;
                                        $modifiedTimestamp = $tour->post_modified ? optional($tour->post_modified)->timestamp ?? strtotime((string) $tour->post_modified) : 0;
                                        $imageUrl = trim((string) ($tour->image_url ?? ''));
                                    ?>
                                    <tr data-title="<?php echo e(Str::lower($tour->post_title)); ?>" data-price="<?php echo e($price); ?>" data-modified="<?php echo e($modifiedTimestamp); ?>">
                                        <td><strong>#<?php echo e($tour->ID); ?></strong></td>
                                        <td>
                                            <?php if (isset($component)) { $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.image-thumb','data' => ['src' => $imageUrl !== '' ? $imageUrl : null,'alt' => $tour->post_title,'size' => 'tour']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.image-thumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($imageUrl !== '' ? $imageUrl : null),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($tour->post_title),'size' => 'tour']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d)): ?>
<?php $attributes = $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d; ?>
<?php unset($__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d)): ?>
<?php $component = $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d; ?>
<?php unset($__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d); ?>
<?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="aj-item-title">
                                                <a href="<?php echo e(route('admin.circuits.voyages.edit', $tour->ID)); ?>"><?php echo e($tour->post_title); ?></a>
                                                <?php if(!empty($tour->laravel_slug)): ?>
                                                    <span class="aj-badge -info">Laravel</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="aj-meta-text"><?php echo e($tour->post_name); ?></div>
                                        </td>
                                        <td><?php echo e($tour->address ?? '-'); ?></td>
                                        <td><?php echo e($tour->duration_day ?? '-'); ?></td>
                                        <td><span class="aj-price"><?php echo e($price > 0 ? number_format($price, 0, ',', ' ') . ' MAD' : '�?"'); ?></span></td>
                                        <td>
                                            <?php if($tour->post_status === 'publish'): ?>
                                                <span class="aj-badge -success">Publié</span>
                                            <?php elseif($tour->post_status === 'draft'): ?>
                                                <span class="aj-badge -warning">Brouillon</span>
                                            <?php elseif($tour->post_status === 'private'): ?>
                                                <span class="aj-badge -neutral">Archivé</span>
                                            <?php else: ?>
                                                <span class="aj-badge -info"><?php echo e($tour->post_status); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e(optional($tour->post_modified)->format('d/m/Y H:i') ?? '-'); ?></td>
                                        <td class="text-end">
                                            <div class="aj-actions">
                                                <a href="https://ajinsafro.net/tours/<?php echo e($tour->post_name); ?>" target="_blank" class="aj-icon-btn" title="Voir la fiche publique">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <?php if(!empty($tour->laravel_slug)): ?>
                                                    <a href="<?php echo e(url('/voyages/'.$tour->laravel_slug)); ?>" target="_blank" rel="noopener noreferrer" class="aj-icon-btn" title="Voir la page commerciale">
                                                        <i class="bx bx-link-external"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?php echo e(route('admin.circuits.voyages.edit', $tour->ID)); ?>" class="aj-icon-btn" title="Modifier">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                                <a href="<?php echo e(route('admin.circuits.voyages.edit-v2', $tour->ID)); ?>" class="aj-icon-btn" title="�?diteur V2">
                                                    <i class="bx bx-layer"></i>
                                                </a>
                                                <form action="<?php echo e(route('admin.circuits.voyages.destroy', $tour->ID)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce tour de WordPress ?');">
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
                        <?php $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $price = is_numeric($tour->adult_price ?? null) ? (float) $tour->adult_price : 0;
                                $modifiedTimestamp = $tour->post_modified ? optional($tour->post_modified)->timestamp ?? strtotime((string) $tour->post_modified) : 0;
                                $imageUrl = trim((string) ($tour->image_url ?? ''));
                            ?>
                            <article class="aj-card" data-title="<?php echo e(Str::lower($tour->post_title)); ?>" data-price="<?php echo e($price); ?>" data-modified="<?php echo e($modifiedTimestamp); ?>">
                                <div class="aj-card-cover d-flex align-items-end p-3" <?php if($imageUrl !== ''): ?> style="background-image:linear-gradient(180deg, rgba(9,32,63,0.06), rgba(9,32,63,0.42)), url('<?php echo e($imageUrl); ?>'); background-size:cover; background-position:center;" <?php endif; ?>>
                                    <span class="aj-badge -info">#<?php echo e($tour->ID); ?></span>
                                </div>
                                <div class="aj-card-body">
                                    <h4 class="aj-card-title"><a href="<?php echo e(route('admin.circuits.voyages.edit', $tour->ID)); ?>"><?php echo e($tour->post_title); ?></a></h4>
                                    <div class="aj-meta-text mb-2"><?php echo e($tour->post_name); ?></div>
                                    <div class="aj-meta-text mb-2"><?php echo e($tour->address ?? 'Destination non renseignée'); ?></div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="aj-badge -neutral"><?php echo e($tour->duration_day ?? '-'); ?> jours</span>
                                        <?php if($tour->post_status === 'publish'): ?>
                                            <span class="aj-badge -success">Publié</span>
                                        <?php elseif($tour->post_status === 'draft'): ?>
                                            <span class="aj-badge -warning">Brouillon</span>
                                        <?php else: ?>
                                            <span class="aj-badge -info"><?php echo e($tour->post_status); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="aj-card-actions">
                                        <span class="aj-price"><?php echo e($price > 0 ? number_format($price, 0, ',', ' ') . ' MAD' : '�?"'); ?></span>
                                        <div class="aj-actions">
                                            <a href="<?php echo e(route('admin.circuits.voyages.edit', $tour->ID)); ?>" class="aj-icon-btn" title="Modifier"><i class="bx bx-pencil"></i></a>
                                            <a href="<?php echo e(route('admin.circuits.voyages.edit-v2', $tour->ID)); ?>" class="aj-icon-btn" title="V2"><i class="bx bx-layer"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <?php if (isset($component)) { $__componentOriginalef886446d0d494c63255f0af1f6da7a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef886446d0d494c63255f0af1f6da7a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.pagination-footer','data' => ['paginator' => $tours,'linksView' => 'pagination::bootstrap-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.pagination-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($tours),'links-view' => 'pagination::bootstrap-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalef886446d0d494c63255f0af1f6da7a2)): ?>
<?php $attributes = $__attributesOriginalef886446d0d494c63255f0af1f6da7a2; ?>
<?php unset($__attributesOriginalef886446d0d494c63255f0af1f6da7a2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalef886446d0d494c63255f0af1f6da7a2)): ?>
<?php $component = $__componentOriginalef886446d0d494c63255f0af1f6da7a2; ?>
<?php unset($__componentOriginalef886446d0d494c63255f0af1f6da7a2); ?>
<?php endif; ?>
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
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\index.blade.php ENDPATH**/ ?>