<?php
    $listingUrl = route('front.group-deals.index');
    $f = $filters ?? [];
    $heroImagePath = \App\Models\Setting::getValue('hero_image');
    $heroImageUrl = $heroImagePath ? \App\Models\Setting::storageUrl($heroImagePath) : asset('front/images/hero.jpg');
    $heroOverlay = max(0.45, (float) (\App\Models\Setting::getValue('hero_overlay_opacity', '0.5')));
?>


<?php $__env->startSection('title', 'Group Deals · Ajinsafro'); ?>

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

<header class="relative overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image:url('<?php echo e($heroImageUrl); ?>')"></div>
    <div class="absolute inset-0" style="background:linear-gradient(180deg, rgba(8,27,51,0.75) 0%, rgba(8,27,51,<?php echo e(number_format(min(0.9, $heroOverlay + 0.18), 2, '.', '')); ?>) 100%);"></div>
    <div class="relative max-w-7xl mx-auto px-4 pt-16 pb-20 md:pt-24 md:pb-24 text-white">
        <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm backdrop-blur">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                Voyages de groupe à prix évolutif
            </div>
            <h1 class="mt-5 text-4xl md:text-5xl font-bold leading-tight">Offres de voyage de groupe Ajinsafro</h1>
            <p class="mt-4 text-lg text-white/85">Plus le groupe grandit, plus le prix par personne peut baisser. Suivez la progression, le seuil de garantie et les meilleurs paliers en direct.</p>
        </div>
    </div>
</header>

<main class="bg-slate-50 min-h-screen">
    <section class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid gap-8 lg:grid-cols-[320px,minmax(0,1fr)]">
            <aside class="lg:sticky lg:top-24 self-start rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-slate-900 font-semibold text-lg">Filtrer les offres</h2>
                <form method="get" action="<?php echo e($listingUrl); ?>" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Recherche</label>
                        <input type="text" name="q" value="<?php echo e($f['q'] ?? ''); ?>" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Titre, destination, ambiance">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Destination</label>
                        <select name="destination" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm bg-white">
                            <option value="">Toutes les destinations</option>
                            <?php $__currentLoopData = $destinations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $destination): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($destination); ?>" <?php if(($f['destination'] ?? '') === $destination): echo 'selected'; endif; ?>><?php echo e($destination); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <?php if(($categories ?? collect())->isNotEmpty()): ?>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Type de groupe</label>
                            <select name="category" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm bg-white">
                                <option value="">Tous les types</option>
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($category->slug); ?>" <?php if(($f['category'] ?? '') === $category->slug): echo 'selected'; endif; ?>><?php echo e($category->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Statut</label>
                        <select name="status" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm bg-white">
                            <option value="">Tous</option>
                            <option value="published" <?php if(($f['status'] ?? '') === 'published'): echo 'selected'; endif; ?>>Publié</option>
                            <option value="guaranteed" <?php if(($f['status'] ?? '') === 'guaranteed'): echo 'selected'; endif; ?>>Garanti</option>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button class="flex-1 rounded-2xl bg-[#f28c28] px-5 py-3 text-sm font-semibold text-white shadow hover:bg-[#df7a18]">Rechercher</button>
                        <a href="<?php echo e($listingUrl); ?>" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-medium text-slate-700">Réinitialiser</a>
                    </div>
                </form>
            </aside>

            <div>
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold text-[#123b69]">Catalogue Group Deals</h2>
                        <p class="text-sm text-slate-600"><?php echo e($deals->total()); ?> offre(s) trouvée(s)</p>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <?php $__empty_1 = true; $__currentLoopData = $deals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $activeTier = $deal->activePricingTier();
                            $bestTier = $deal->bestPricingTier();
                            $nextTier = $deal->nextPricingTier();
                        ?>
                        <article class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-[#f28c28]/40 hover:shadow-lg">
                            <div class="relative h-60 bg-slate-200">
                                <?php if($deal->image_url): ?>
                                    <img src="<?php echo e($deal->image_url); ?>" alt="<?php echo e($deal->title); ?>" class="h-full w-full object-cover">
                                <?php endif; ?>
                                <div class="absolute inset-x-0 top-0 flex items-center justify-between p-4">
                                    <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-[#123b69] shadow">Group Deal</span>
                                    <span class="rounded-full <?php echo e($deal->is_guaranteed ? 'bg-emerald-500' : 'bg-[#123b69]'); ?> px-3 py-1 text-xs font-semibold text-white shadow">
                                        <?php echo e($deal->is_guaranteed ? 'Garanti' : 'Bientôt garanti'); ?>

                                    </span>
                                </div>
                            </div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#123b69]/70"><?php echo e($deal->destination ?: 'Destination à définir'); ?></p>
                                        <h3 class="mt-2 text-xl font-semibold text-slate-900"><?php echo e($deal->title); ?></h3>
                                    </div>
                                    <div class="rounded-2xl bg-[#123b69]/5 px-3 py-2 text-right">
                                        <div class="text-xs text-slate-500">Prix actuel</div>
                                        <div class="text-lg font-bold text-[#f28c28]"><?php echo e($deal->current_price ? number_format((float) $deal->current_price, 0, ',', ' ') . ' DH' : 'N/A'); ?></div>
                                    </div>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600"><?php echo e(\Illuminate\Support\Str::limit(strip_tags((string) ($deal->short_description ?: $deal->description)), 130)); ?></p>

                                <div class="mt-4 rounded-2xl bg-slate-50 p-4">
                                    <div class="flex items-center justify-between text-sm text-slate-700">
                                        <span><?php echo e($deal->current_participants); ?>/<?php echo e($deal->max_participants); ?> participants</span>
                                        <span>Minimum: <?php echo e($deal->min_participants); ?></span>
                                    </div>
                                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full <?php echo e($deal->is_guaranteed ? 'bg-emerald-500' : 'bg-[#f28c28]'); ?>" style="width: <?php echo e($deal->progress_percent); ?>%"></div>
                                    </div>
                                    <p class="mt-3 text-sm text-slate-600">
                                        <?php if($deal->remaining_to_guarantee > 0): ?>
                                            Il reste <?php echo e($deal->remaining_to_guarantee); ?> personne(s) pour garantir ce voyage.
                                        <?php else: ?>
                                            Le voyage est garanti.
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <?php if($nextTier): ?>
                                    <p class="mt-4 text-sm text-slate-600">Si le groupe atteint <?php echo e($nextTier->min_people); ?> personnes, le prix passera à <span class="font-semibold text-[#123b69]"><?php echo e(number_format((float) $nextTier->price_per_person, 0, ',', ' ')); ?> DH</span>.</p>
                                <?php elseif($bestTier): ?>
                                    <p class="mt-4 text-sm text-slate-600">Meilleur prix possible: <span class="font-semibold text-[#123b69]"><?php echo e(number_format((float) $bestTier->price_per_person, 0, ',', ' ')); ?> DH</span>.</p>
                                <?php endif; ?>

                                <div class="mt-5 flex gap-3">
                                    <a href="<?php echo e(route('front.group-deals.show', $deal->slug)); ?>" class="flex-1 rounded-2xl bg-[#f28c28] px-4 py-3 text-center text-sm font-semibold text-white shadow hover:bg-[#df7a18]">Voir l'offre</a>
                                    <span class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-[#123b69]"><?php echo e($activeTier?->label ?: 'Palier actif'); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="md:col-span-2 xl:col-span-3 rounded-[28px] border border-dashed border-slate-300 bg-white p-10 text-center text-slate-600">
                            Aucune offre Group Deal ne correspond à ces critères.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if($deals->hasPages()): ?>
                    <div class="mt-8"><?php echo e($deals->links()); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\group-deals\index.blade.php ENDPATH**/ ?>