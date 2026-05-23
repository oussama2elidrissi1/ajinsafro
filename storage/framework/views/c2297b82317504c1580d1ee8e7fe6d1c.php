<?php
    $listingUrl = route('front.voyages.index');
    $f = $filters ?? [];
    $pageTitle = $pageTitle ?? (($hasFilters ?? false) ? 'Offres correspondantes' : 'Tous les voyages');
    $pageSubtitle = $pageSubtitle ?? 'Parcourez nos circuits et séjours. Affinez par thème, destination ou date de départ.';
    $themes = $themeOptions ?? collect();
    $heroImagePath = \App\Models\Setting::getValue('hero_image');
    $heroImageUrl = $heroImagePath ? \App\Models\Setting::storageUrl($heroImagePath) : asset('front/images/hero.jpg');
    $heroOverlay = max(0.45, (float) (\App\Models\Setting::getValue('hero_overlay_opacity', '0.5')));
?>


<?php $__env->startSection('title', 'Voyages – AjiNsafro.ma'); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginal0783a137f0e506a7088ffbc77deaba0d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0783a137f0e506a7088ffbc77deaba0d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.front.navbar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('front.navbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0783a137f0e506a7088ffbc77deaba0d)): ?>
<?php $attributes = $__attributesOriginal0783a137f0e506a7088ffbc77deaba0d; ?>
<?php unset($__attributesOriginal0783a137f0e506a7088ffbc77deaba0d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0783a137f0e506a7088ffbc77deaba0d)): ?>
<?php $component = $__componentOriginal0783a137f0e506a7088ffbc77deaba0d; ?>
<?php unset($__componentOriginal0783a137f0e506a7088ffbc77deaba0d); ?>
<?php endif; ?>

    
    <header class="relative overflow-hidden border-b border-gray-200/80">
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('<?php echo e($heroImageUrl); ?>');" aria-hidden="true"></div>
        <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(0,0,0,0.78) 0%, rgba(0,0,0,<?php echo e(number_format(min(0.85, $heroOverlay + 0.08), 2, '.', '')); ?>) 45%, rgba(0,0,0,0.82) 100%);" aria-hidden="true"></div>
        <div class="relative z-10 container mx-auto px-4 max-w-7xl pt-8 pb-10 md:pt-11 md:pb-12">
            <nav class="text-sm text-white/80 mb-5" aria-label="Fil d'Ariane">
                <a href="<?php echo e(route('front.home')); ?>" class="hover:text-white transition-colors">Accueil</a>
                <span class="mx-2 text-white/50" aria-hidden="true">/</span>
                <span class="text-white font-medium">Voyages</span>
            </nav>
            <h1 class="text-3xl md:text-4xl lg:text-[2.65rem] font-bold text-white tracking-tight text-balance leading-tight max-w-4xl">
                <?php echo e($pageTitle); ?>

            </h1>
            <p class="mt-4 text-base md:text-lg text-white/90 leading-relaxed max-w-2xl">
                <?php echo e($pageSubtitle); ?>

            </p>
        </div>
    </header>

    <main class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-16">
            <div class="flex flex-col lg:flex-row lg:items-start gap-8 lg:gap-10">
                
                <aside class="w-full lg:w-80 xl:w-[20rem] shrink-0 lg:sticky lg:top-24 lg:z-10">
                    <form method="get" action="<?php echo e($listingUrl); ?>" class="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-sm space-y-5">
                        <h2 class="font-semibold text-gray-900 text-sm uppercase tracking-wide border-b border-gray-100 pb-3">
                            Filtres
                        </h2>

                        <div>
                            <label for="filter-q" class="block text-sm font-medium text-gray-700 mb-1.5">Recherche</label>
                            <input type="text" name="q" id="filter-q" value="<?php echo e($f['q'] ?? ''); ?>"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand focus:border-brand"
                                placeholder="Nom, destination…" autocomplete="off">
                        </div>

                        <div>
                            <label for="filter-theme" class="block text-sm font-medium text-gray-700 mb-1.5">Type de voyage / thème</label>
                            <select name="theme" id="filter-theme" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-brand focus:border-brand">
                                <option value="">Tous les thèmes</option>
                                <?php $__empty_1 = true; $__currentLoopData = $themes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $th): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $optVal = ($th->slug !== null && $th->slug !== '') ? $th->slug : (string) $th->id;
                                    ?>
                                    <option value="<?php echo e($optVal); ?>" <?php if((string) ($f['theme'] ?? '') === (string) $optVal): echo 'selected'; endif; ?>><?php echo e($th->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <option value="" disabled>Aucun thème en base — créez des thèmes dans l’admin ou exécutez les migrations</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div>
                            <label for="filter-destination" class="block text-sm font-medium text-gray-700 mb-1.5">Destination</label>
                            <select name="destination" id="filter-destination" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-brand focus:border-brand">
                                <option value="">Toutes les destinations</option>
                                <?php $__currentLoopData = $destinationOptions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($d); ?>" <?php if(($f['destination'] ?? '') === $d): echo 'selected'; endif; ?>><?php echo e($d); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div>
                            <label for="filter-depart" class="block text-sm font-medium text-gray-700 mb-1.5">Date de départ</label>
                            <input type="date" name="depart_date" id="filter-depart" value="<?php echo e($f['depart_date'] ?? ''); ?>"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand focus:border-brand">
                        </div>

                        <input type="hidden" name="catalog_orderby" value="<?php echo e($f['catalog_orderby'] ?? 'date'); ?>">

                        <div class="flex flex-col gap-2.5 pt-1">
                            <button type="submit" class="w-full rounded-lg bg-brand text-white font-medium py-2.5 text-sm hover:opacity-95 transition">
                                Appliquer les filtres
                            </button>
                            <a href="<?php echo e($listingUrl); ?>" class="w-full text-center rounded-lg border border-gray-300 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition">
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </aside>

                
                <div class="flex-1 min-w-0">
                    <div class="rounded-2xl border border-gray-200/90 bg-white p-4 md:p-6 lg:p-8 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-5 border-b border-gray-100">
                            <p class="text-sm text-gray-600">
                                <?php if(($voyages->total() ?? 0) > 0): ?>
                                    <span class="font-semibold text-gray-900"><?php echo e($voyages->total()); ?></span> résultat<?php echo e($voyages->total() > 1 ? 's' : ''); ?>

                                <?php else: ?>
                                    Aucun résultat pour ces critères
                                <?php endif; ?>
                            </p>
                            <form method="get" action="<?php echo e($listingUrl); ?>" class="flex items-center gap-2 text-sm shrink-0">
                                <?php $__currentLoopData = ['q', 'theme', 'destination', 'depart_date']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $keep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if(!empty($f[$keep] ?? '')): ?>
                                        <input type="hidden" name="<?php echo e($keep); ?>" value="<?php echo e($f[$keep]); ?>">
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <label for="toolbar-sort" class="text-gray-600 whitespace-nowrap">Trier par</label>
                                <select name="catalog_orderby" id="toolbar-sort" onchange="this.form.submit()"
                                    class="rounded-lg border border-gray-300 px-2.5 py-2 text-sm bg-white min-w-[11rem]">
                                    <option value="date" <?php if(($f['catalog_orderby'] ?? 'date') === 'date'): echo 'selected'; endif; ?>>Plus récents</option>
                                    <option value="title" <?php if(($f['catalog_orderby'] ?? '') === 'title'): echo 'selected'; endif; ?>>Titre (A–Z)</option>
                                    <option value="title_desc" <?php if(($f['catalog_orderby'] ?? '') === 'title_desc'): echo 'selected'; endif; ?>>Titre (Z–A)</option>
                                </select>
                            </form>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                            <?php $__empty_1 = true; $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $imgSrc = $voyage->featured_image_url;
                                    if (!$imgSrc) {
                                        $imgSrc = "data:image/svg+xml," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect fill="#667eea" width="400" height="300"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="white" font-family="sans-serif" font-size="18">Voyage</text></svg>');
                                    }
                                    $detailUrl = route('front.voyages.show', ['slug' => $voyage->slug]);
                                ?>
                                <a href="<?php echo e($detailUrl); ?>" class="group block rounded-xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all bg-white">
                                    <div class="aspect-[4/3] relative overflow-hidden bg-gray-200">
                                        <img
                                            src="<?php echo e($imgSrc); ?>"
                                            alt="<?php echo e(e($voyage->name)); ?>"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            loading="lazy"
                                            onerror="this.onerror=null; this.style.background='linear-gradient(135deg,#667eea 0%,#764ba2 100%)'; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23667eea%22 width=%22400%22 height=%22300%22/%3E%3C/svg%3E';"
                                        >
                                        <?php if($voyage->old_price && $voyage->old_price > $voyage->price_from && $voyage->discount_percent): ?>
                                            <span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">
                                                -<?php echo e($voyage->discount_percent); ?>%
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-4">
                                        <?php if($voyage->themes->isNotEmpty()): ?>
                                            <div class="flex flex-wrap gap-1 mb-2">
                                                <?php $__currentLoopData = $voyage->themes->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-brand/10 text-brand"><?php echo e($t->name); ?></span>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        <?php endif; ?>
                                        <h2 class="font-semibold text-gray-900 group-hover:text-brand transition line-clamp-2"><?php echo e(e($voyage->name)); ?></h2>
                                        <?php if($voyage->destination): ?>
                                            <p class="text-sm text-gray-500 mt-1"><?php echo e(e($voyage->destination)); ?></p>
                                        <?php endif; ?>
                                        <?php if($voyage->duration_text): ?>
                                            <p class="text-sm text-gray-500"><?php echo e(e($voyage->duration_text)); ?></p>
                                        <?php endif; ?>
                                        <p class="mt-2 font-semibold text-brand">
                                            <?php if($voyage->price_from !== null): ?>
                                                <?php echo e(number_format($voyage->price_from, 0, ',', ' ')); ?> <?php echo e($voyage->currency_symbol); ?>

                                                <?php if($voyage->old_price && $voyage->old_price > $voyage->price_from): ?>
                                                    <span class="text-gray-400 line-through text-sm font-normal"><?php echo e(number_format($voyage->old_price, 0, ',', ' ')); ?> <?php echo e($voyage->currency_symbol); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Sur demande
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="col-span-full text-center py-14 text-gray-500">
                                    Aucun voyage ne correspond à ces critères.
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if($voyages->hasPages()): ?>
                            <div class="mt-8 flex justify-center border-t border-gray-100 pt-6">
                                <?php echo e($voyages->links()); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm">
            &copy; <?php echo e(date('Y')); ?> AjiNsafro.ma. All rights reserved.
        </div>
    </footer>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\front\voyages\index.blade.php ENDPATH**/ ?>