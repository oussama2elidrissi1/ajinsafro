<?php
    use Illuminate\Support\Str;

    $pageTitle = 'Catalogue Hébergements';
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
        $activeFilters[] = 'Statut : Publiés';
    } elseif (($filters['status'] ?? '') === 'draft') {
        $activeFilters[] = 'Statut : Brouillons';
    }
    if (filled($filters['star'] ?? null)) {
        $activeFilters[] = 'Étoiles : '.(int) $filters['star'];
    }
    if (($filters['featured'] ?? '') === '1') {
        $activeFilters[] = 'Sélection : À la une';
    }
    if (filled($filters['destination'] ?? null)) {
        $activeFilters[] = 'Destination : '.Str::limit($filters['destination'], 28);
    }
    $activeFilterCount = count($activeFilters);
?>

<?php $__env->startSection('title', $pageTitle); ?>

<?php $__env->startPush('styles'); ?>
    <link href="<?php echo e(URL::asset('css/admin-catalog-premium.css')); ?>" rel="stylesheet" type="text/css" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="aj-catalog-page">
        <div class="aj-shell">
            <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => $pageTitle,'subtitle' => 'Gérez, filtrez et consultez les hébergements WordPress synchronisés sans modifier la logique métier existante.','breadcrumbs' => [
                    ['label' => 'Admin', 'url' => route('admin.dashboard')],
                    ['label' => 'Hébergements', 'url' => '#'],
                    ['label' => 'Catalogue'],
                ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pageTitle),'subtitle' => 'Gérez, filtrez et consultez les hébergements WordPress synchronisés sans modifier la logique métier existante.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                    ['label' => 'Admin', 'url' => route('admin.dashboard')],
                    ['label' => 'Hébergements', 'url' => '#'],
                    ['label' => 'Catalogue'],
                ])]); ?>
                 <?php $__env->slot('actions', null, []); ?> 
                    <a href="<?php echo e(route('admin.wordpress.hotels.create')); ?>" class="aj-btn aj-btn-primary">
                        <i class="bx bx-plus"></i>
                        <span>Créer un hébergement</span>
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

            <?php if (isset($component)) { $__componentOriginaldc8ea6d1c156289736a271a64b9dc41b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldc8ea6d1c156289736a271a64b9dc41b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.kpi-cards','data' => ['kpis' => [
                    ['label' => 'Total hébergements', 'value' => number_format($totalHotels, 0, ',', ' '), 'icon' => 'bx bx-buildings', 'color' => '-blue', 'note' => 'Résultats sur le catalogue courant'],
                    ['label' => 'Publiés', 'value' => $publishedCount, 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Sur la page affichée'],
                    ['label' => 'Brouillons', 'value' => $draftCount, 'icon' => 'bx bx-edit-alt', 'color' => '-orange', 'note' => 'À compléter ou publier'],
                    ['label' => 'À la une', 'value' => $featuredCount, 'icon' => 'bx bx-star', 'color' => '-violet', 'note' => 'Mis en avant dans cette vue'],
                ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.kpi-cards'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['kpis' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                    ['label' => 'Total hébergements', 'value' => number_format($totalHotels, 0, ',', ' '), 'icon' => 'bx bx-buildings', 'color' => '-blue', 'note' => 'Résultats sur le catalogue courant'],
                    ['label' => 'Publiés', 'value' => $publishedCount, 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Sur la page affichée'],
                    ['label' => 'Brouillons', 'value' => $draftCount, 'icon' => 'bx bx-edit-alt', 'color' => '-orange', 'note' => 'À compléter ou publier'],
                    ['label' => 'À la une', 'value' => $featuredCount, 'icon' => 'bx bx-star', 'color' => '-violet', 'note' => 'Mis en avant dans cette vue'],
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.filter-panel','data' => ['action' => route('admin.wordpress.hotels.index'),'method' => 'GET','resetUrl' => route('admin.wordpress.hotels.index'),'gridClass' => '-compact']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.filter-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.wordpress.hotels.index')),'method' => 'GET','reset-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.wordpress.hotels.index')),'grid-class' => '-compact']); ?>
                 <?php $__env->slot('fields', null, []); ?> 
                    <div class="aj-field aj-search-wrap">
                        <label for="search">Recherche</label>
                        <span class="aj-search-icon"><i class="bx bx-search"></i></span>
                        <input id="search" type="text" name="search" class="aj-control" value="<?php echo e($filters['search'] ?? ''); ?>" placeholder="Nom, slug, résumé ou adresse">
                    </div>
                    <div class="aj-field">
                        <label for="status">Statut</label>
                        <select id="status" name="status" class="aj-control">
                            <option value="">Tous les statuts</option>
                            <option value="publish" <?php if(($filters['status'] ?? '') === 'publish'): echo 'selected'; endif; ?>>Publié</option>
                            <option value="draft" <?php if(($filters['status'] ?? '') === 'draft'): echo 'selected'; endif; ?>>Brouillon</option>
                        </select>
                    </div>
                    <div class="aj-field">
                        <label for="hotel_star">Étoiles</label>
                        <select id="hotel_star" name="hotel_star" class="aj-control">
                            <option value="">Toutes les étoiles</option>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo e($i); ?>" <?php if((string) ($filters['star'] ?? '') === (string) $i): echo 'selected'; endif; ?>><?php echo e($i); ?> étoile(s)</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="aj-field">
                        <label for="featured">Sélection</label>
                        <select id="featured" name="featured" class="aj-control">
                            <option value="">Tous les hébergements</option>
                            <option value="1" <?php if(($filters['featured'] ?? '') === '1'): echo 'selected'; endif; ?>>À la une</option>
                        </select>
                    </div>
                    <div class="aj-field">
                        <label for="destination">Destination</label>
                        <input id="destination" type="text" name="destination" class="aj-control" value="<?php echo e($filters['destination'] ?? ''); ?>" placeholder="Ville ou adresse">
                    </div>
                 <?php $__env->endSlot(); ?>

                 <?php $__env->slot('chips', null, []); ?> 
                    <?php $__empty_1 = true; $__currentLoopData = $activeFilters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filterLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <span class="aj-chip"><?php echo e($filterLabel); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <span class="text-muted">Aucun filtre actif.</span>
                    <?php endif; ?>
                    <?php if($activeFilterCount > 0): ?>
                        <a href="<?php echo e(route('admin.wordpress.hotels.index')); ?>" class="ms-auto fw-bold text-decoration-none" style="color:#0468c8;">Tout effacer</a>
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
                            <label for="hotelSortSelect" class="mb-0">Trier par :</label>
                            <select id="hotelSortSelect" class="aj-mini-btn aj-mini-select">
                                <option value="recent">Plus récents</option>
                                <option value="price_asc">Prix croissant</option>
                                <option value="price_desc">Prix décroissant</option>
                                <option value="title_asc">Titre A-Z</option>
                            </select>
                        </div>
                        <button type="button" class="aj-mini-btn" id="hotelExportBtn">
                            <i class="bx bx-export"></i>
                            <span>Exporter la vue</span>
                        </button>
                        <span><?php echo e($hotels->firstItem() ?? 0); ?> - <?php echo e($hotels->lastItem() ?? 0); ?> sur <?php echo e($totalHotels); ?> hébergements</span>
                    </div>
                    <div class="aj-result-meta">
                        <span>Vue :</span>
                        <div class="aj-view-toggle" role="tablist" aria-label="Changer la vue">
                            <button type="button" class="is-active" data-view="table" aria-pressed="true"><i class="bx bx-list-ul"></i></button>
                            <button type="button" data-view="grid" aria-pressed="false"><i class="bx bx-grid-alt"></i></button>
                        </div>
                    </div>
                </div>

                <?php if($hotels->isEmpty()): ?>
                    <?php if (isset($component)) { $__componentOriginal99089f8e2ef4184d7d35db81d60c6521 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal99089f8e2ef4184d7d35db81d60c6521 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.empty-state','data' => ['title' => 'Aucun hébergement trouvé','message' => 'Ajustez vos filtres ou créez un nouvel hébergement pour alimenter le catalogue.','actionUrl' => route('admin.wordpress.hotels.create'),'actionLabel' => 'Créer un hébergement']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Aucun hébergement trouvé','message' => 'Ajustez vos filtres ou créez un nouvel hébergement pour alimenter le catalogue.','action-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.wordpress.hotels.create')),'action-label' => 'Créer un hébergement']); ?>
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
                    <div class="aj-table-wrap" data-hotel-view="table">
                        <table class="aj-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Hébergement</th>
                                    <th>Localisation</th>
                                    <th>Statut</th>
                                    <th>Étoiles</th>
                                    <th>Prix min</th>
                                    <th>Modifié le</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $hotels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hotel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $thumbUrl = $media->getFeaturedImageUrlVerified($hotel->ID);
                                        $stHotel = $hotel->stHotel;
                                        $isPublished = $hotel->post_status === 'publish';
                                        $isFeatured = optional($stHotel)->is_featured === 'on';
                                        $stars = (int) ($stHotel->hotel_star ?? 0);
                                        $price = $stHotel->min_price;
                                        $address = trim((string) ($stHotel->address ?? ''));
                                    ?>
                                    <tr
                                        data-title="<?php echo e(Str::lower($hotel->post_title)); ?>"
                                        data-price="<?php echo e(is_numeric($price) ? (float) $price : 0); ?>"
                                        data-modified="<?php echo e($hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->timestamp : 0); ?>"
                                    >
                                        <td>
                                            <?php if (isset($component)) { $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.image-thumb','data' => ['src' => $thumbUrl,'alt' => $hotel->post_title,'size' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.image-thumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($thumbUrl),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hotel->post_title),'size' => 'md']); ?>
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
                                                <a href="<?php echo e(route('admin.wordpress.hotels.edit', $hotel)); ?>"><?php echo e($hotel->post_title); ?></a>
                                                <?php if($isFeatured): ?>
                                                    <span class="aj-badge -info">À la une</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="aj-meta-text">ID #<?php echo e($hotel->ID); ?></div>
                                            <?php if($hotel->post_excerpt): ?>
                                                <div class="aj-meta-text mt-1"><?php echo e(Str::limit(strip_tags($hotel->post_excerpt), 70)); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="aj-location">
                                                <strong><?php echo e($address !== '' ? $address : 'Adresse non renseignée'); ?></strong>
                                                <span><?php echo e($hotel->post_name ?: 'Slug non renseigné'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($isPublished): ?>
                                                <span class="aj-badge -success">Publié</span>
                                            <?php else: ?>
                                                <span class="aj-badge -warning">Brouillon</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($stars > 0): ?>
                                                <span class="aj-stars"><?php echo e(str_repeat('★', $stars)); ?><span><?php echo e($stars); ?></span></span>
                                            <?php else: ?>
                                                <span class="aj-meta-text">Non renseigné</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="aj-price">
                                                <?php echo e(is_numeric($price) ? number_format((float) $price, 0, ',', ' ') . ' DH' : '—'); ?>

                                            </span>
                                        </td>
                                        <td>
                                            <span class="aj-date">
                                                <?php echo e($hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->format('d/m/Y') : '—'); ?>

                                                <small><?php echo e($hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->format('H:i') : ''); ?></small>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="aj-actions">
                                                <?php if($wpSiteUrl): ?>
                                                    <a href="<?php echo e($wpSiteUrl); ?>/?post_type=st_hotel&p=<?php echo e($hotel->ID); ?>" target="_blank" class="aj-icon-btn" title="Voir sur le site">
                                                        <i class="bx bx-link-external"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?php echo e(route('admin.wordpress.hotels.edit', $hotel)); ?>" class="aj-icon-btn" title="Modifier">
                                                    <i class="bx bx-pencil"></i>
                                                </a>
                                                <form action="<?php echo e(route('admin.wordpress.hotels.destroy', $hotel)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Déplacer cet hôtel dans la corbeille ?');">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="aj-icon-btn -danger" title="Supprimer">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                                <a href="<?php echo e(route('admin.wordpress.hotels.edit', $hotel)); ?>" class="aj-icon-btn" title="Plus">
                                                    <i class="bx bx-dots-horizontal-rounded"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="aj-grid" data-hotel-view="grid">
                        <?php $__currentLoopData = $hotels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hotel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $thumbUrl = $media->getFeaturedImageUrlVerified($hotel->ID);
                                $stHotel = $hotel->stHotel;
                                $isPublished = $hotel->post_status === 'publish';
                                $isFeatured = optional($stHotel)->is_featured === 'on';
                                $stars = (int) ($stHotel->hotel_star ?? 0);
                                $price = $stHotel->min_price;
                            ?>
                            <article
                                class="aj-card"
                                data-title="<?php echo e(Str::lower($hotel->post_title)); ?>"
                                data-price="<?php echo e(is_numeric($price) ? (float) $price : 0); ?>"
                                data-modified="<?php echo e($hotel->post_modified ? \Carbon\Carbon::parse($hotel->post_modified)->timestamp : 0); ?>"
                            >
                                <div class="aj-card-cover">
                                    <?php if($thumbUrl): ?>
                                        <img src="<?php echo e($thumbUrl); ?>" alt="<?php echo e($hotel->post_title); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                        <div class="aj-thumb-placeholder" style="display:none; height:100%; border-radius:0;">
                                            <img src="<?php echo e(asset('images/admin-placeholder.svg')); ?>" alt="Ajinsafro" style="width:100%;height:100%;object-fit:cover;">
                                        </div>
                                    <?php else: ?>
                                        <div class="aj-thumb-placeholder" style="display:grid; height:100%; border-radius:0;">
                                            <img src="<?php echo e(asset('images/admin-placeholder.svg')); ?>" alt="Ajinsafro" style="width:100%;height:100%;object-fit:cover;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="aj-card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <h4 class="aj-card-title"><a href="<?php echo e(route('admin.wordpress.hotels.edit', $hotel)); ?>"><?php echo e($hotel->post_title); ?></a></h4>
                                            <div class="aj-meta-text">ID #<?php echo e($hotel->ID); ?></div>
                                        </div>
                                        <?php if($isFeatured): ?>
                                            <span class="aj-badge -info">À la une</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="aj-meta-text mb-3"><?php echo e(trim((string) ($stHotel->address ?? '')) !== '' ? $stHotel->address : 'Adresse non renseignée'); ?></div>

                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <?php if($isPublished): ?>
                                            <span class="aj-badge -success">Publié</span>
                                        <?php else: ?>
                                            <span class="aj-badge -warning">Brouillon</span>
                                        <?php endif; ?>
                                        <?php if($stars > 0): ?>
                                            <span class="aj-badge -neutral"><?php echo e($stars); ?> étoile(s)</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="aj-card-actions">
                                        <span class="aj-price"><?php echo e(is_numeric($price) ? number_format((float) $price, 0, ',', ' ') . ' DH' : '—'); ?></span>
                                        <div class="aj-actions">
                                            <?php if($wpSiteUrl): ?>
                                                <a href="<?php echo e($wpSiteUrl); ?>/?post_type=st_hotel&p=<?php echo e($hotel->ID); ?>" target="_blank" class="aj-icon-btn" title="Voir sur le site">
                                                    <i class="bx bx-link-external"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo e(route('admin.wordpress.hotels.edit', $hotel)); ?>" class="aj-icon-btn" title="Modifier">
                                                <i class="bx bx-pencil"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <?php if (isset($component)) { $__componentOriginalef886446d0d494c63255f0af1f6da7a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef886446d0d494c63255f0af1f6da7a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.pagination-footer','data' => ['paginator' => $hotels]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.pagination-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hotels)]); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\wordpress\hotels\index.blade.php ENDPATH**/ ?>