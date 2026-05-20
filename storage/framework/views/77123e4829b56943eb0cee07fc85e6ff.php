<?php $__env->startSection('title', $groupDeal->title . ' · Group Deal Ajinsafro'); ?>

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

<?php
    $heroStatus = $groupDeal->status_label;
    $heroStatusClass = match ($groupDeal->status) {
        \App\Models\GroupDeal::STATUS_GUARANTEED => 'bg-emerald-500',
        \App\Models\GroupDeal::STATUS_CLOSED => 'bg-slate-700',
        \App\Models\GroupDeal::STATUS_CANCELLED => 'bg-rose-600',
        default => 'bg-[#f28c28]',
    };
    $summaryText = $groupDeal->short_description ?: $groupDeal->description;
?>

<main class="bg-slate-50 min-h-screen">
    <section class="relative overflow-hidden bg-[#0f2f52] text-white">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at top right, #f28c28 0, transparent 32%), radial-gradient(circle at bottom left, #2db783 0, transparent 30%);"></div>
        <div class="relative max-w-7xl mx-auto px-4 py-12 md:py-16">
            <div class="grid gap-8 lg:grid-cols-[1.25fr,0.95fr] items-center">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold"><?php echo e($groupDeal->destination ?: 'Destination a confirmer'); ?></span>
                        <span class="rounded-full <?php echo e($heroStatusClass); ?> px-4 py-2 text-sm font-semibold">
                            <?php echo e($heroStatus); ?>

                        </span>
                        <?php if($groupDeal->is_featured): ?>
                            <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold">Selection Ajinsafro</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="mt-5 text-4xl md:text-5xl font-bold leading-tight"><?php echo e($groupDeal->title); ?></h1>
                    <p class="mt-4 max-w-2xl text-lg text-white/85"><?php echo e($summaryText); ?></p>
                    <div class="mt-6 flex flex-wrap gap-5 text-sm text-white/85">
                        <?php if($groupDeal->duration_label): ?>
                            <span>Duree: <?php echo e($groupDeal->duration_label); ?></span>
                        <?php endif; ?>
                        <span>Depart: <?php echo e(optional($groupDeal->departure_date ?: $groupDeal->start_date)->format('d/m/Y') ?: 'N/A'); ?></span>
                        <span>Retour: <?php echo e(optional($groupDeal->return_date ?: $groupDeal->end_date)->format('d/m/Y') ?: 'N/A'); ?></span>
                        <span>Inscription jusqu'au <?php echo e(optional($groupDeal->registration_deadline)->format('d/m/Y') ?: 'N/A'); ?></span>
                        <?php if($groupDeal->country || $groupDeal->city): ?>
                            <span><?php echo e(collect([$groupDeal->city, $groupDeal->country])->filter()->implode(', ')); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-[32px] bg-white p-6 text-slate-900 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-medium text-slate-500">Prix actuel</div>
                            <div class="mt-2 text-4xl font-bold text-[#f28c28]"><?php echo e($stats['current_price'] ? number_format($stats['current_price'], 0, ',', ' ') . ' DH' : 'N/A'); ?></div>
                            <div class="mt-2 text-sm text-slate-500">par personne</div>
                            <?php if($groupDeal->starting_price && (float) $groupDeal->starting_price > (float) $stats['current_price']): ?>
                                <div class="mt-2 text-sm text-slate-400 line-through"><?php echo e(number_format((float) $groupDeal->starting_price, 0, ',', ' ')); ?> DH</div>
                            <?php endif; ?>
                        </div>
                        <div class="rounded-3xl bg-[#123b69]/5 px-4 py-3 text-right">
                            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Progression</div>
                            <div class="mt-2 text-2xl font-bold text-[#123b69]"><?php echo e($groupDeal->current_participants); ?>/<?php echo e($groupDeal->max_participants); ?></div>
                            <div class="text-sm text-slate-500">participants</div>
                        </div>
                    </div>
                    <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full <?php echo e($stats['is_guaranteed'] ? 'bg-emerald-500' : 'bg-[#f28c28]'); ?>" style="width: <?php echo e($stats['progress_percent']); ?>%"></div>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p><?php echo e($groupDeal->current_participants); ?> personne(s) sont deja inscrites.</p>
                        <p>Minimum requis pour garantir: <?php echo e($groupDeal->min_participants); ?>.</p>
                        <p>Places restantes: <?php echo e($stats['remaining_places']); ?>.</p>
                        <p>
                            <?php if($stats['remaining_to_guarantee'] > 0): ?>
                                Il reste <?php echo e($stats['remaining_to_guarantee']); ?> personne(s) pour garantir ce voyage.
                            <?php else: ?>
                                Le voyage est garanti.
                            <?php endif; ?>
                        </p>
                    </div>

                    <form method="POST" action="<?php echo e(route('front.group-deals.participate', $groupDeal->slug)); ?>" class="mt-6 space-y-3">
                        <?php echo csrf_field(); ?>
                        <div class="grid gap-3 md:grid-cols-2">
                            <input type="text" name="full_name" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Nom complet" value="<?php echo e(old('full_name', $client?->full_name ?: '')); ?>" required>
                            <input type="email" name="email" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Email" value="<?php echo e(old('email', $client?->email ?: '')); ?>" required>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <input type="text" name="phone" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Telephone" value="<?php echo e(old('phone', $client?->phone ?: '')); ?>">
                            <input type="number" min="1" name="participants_count" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" value="<?php echo e(old('participants_count', 1)); ?>">
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button class="flex-1 rounded-2xl bg-[#f28c28] px-5 py-4 text-sm font-semibold text-white shadow hover:bg-[#df7a18]">Je participe</button>
                            <?php if($groupDeal->share_enabled): ?>
                                <a href="https://wa.me/?text=<?php echo e(urlencode('Rejoins-moi sur ce group deal Ajinsafro: ' . $shareUrl)); ?>" target="_blank" rel="noopener" class="flex-1 rounded-2xl border border-[#123b69]/15 px-5 py-4 text-center text-sm font-semibold text-[#123b69]">Partager avec mes amis</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 py-10">
        <div class="grid gap-8 lg:grid-cols-[1.15fr,0.85fr]">
            <div class="space-y-8">
                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                    <?php if($groupDeal->image_url): ?>
                        <img src="<?php echo e($groupDeal->image_url); ?>" alt="<?php echo e($groupDeal->title); ?>" class="h-[360px] w-full object-cover">
                    <?php endif; ?>
                    <div class="p-6">
                        <h2 class="text-2xl font-semibold text-[#123b69]">Programme du voyage</h2>
                        <div class="mt-4 whitespace-pre-line leading-7 text-slate-700"><?php echo e($groupDeal->program ?: 'Programme detaille a venir.'); ?></div>
                    </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold text-[#123b69]">Services inclus</h3>
                        <ul class="mt-4 space-y-3 text-slate-700">
                            <?php $__empty_1 = true; $__currentLoopData = $groupDeal->included_services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <li class="flex gap-3"><span class="mt-2 h-2 w-2 rounded-full bg-emerald-500"></span><span><?php echo e($line); ?></span></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <li class="text-slate-500">A completer.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold text-[#123b69]">Non inclus</h3>
                        <ul class="mt-4 space-y-3 text-slate-700">
                            <?php $__empty_1 = true; $__currentLoopData = $groupDeal->excluded_services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <li class="flex gap-3"><span class="mt-2 h-2 w-2 rounded-full bg-[#f28c28]"></span><span><?php echo e($line); ?></span></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <li class="text-slate-500">A completer.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-[#123b69]">Paliers de prix</h2>
                    <div class="mt-5 space-y-4">
                        <?php $__currentLoopData = $groupDeal->pricingTiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="rounded-3xl border px-4 py-4 <?php echo e(optional($stats['active_tier'])->id === $tier->id ? 'border-[#f28c28] bg-[#fff5ea]' : 'border-slate-200 bg-slate-50'); ?>">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900"><?php echo e($tier->min_people); ?> a <?php echo e($tier->max_people ?: '∞'); ?> personnes</div>
                                        <div class="text-sm text-slate-500"><?php echo e($tier->label ?: 'Palier de groupe'); ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-[#123b69]"><?php echo e(number_format((float) $tier->price_per_person, 0, ',', ' ')); ?> DH</div>
                                        <?php if(optional($stats['active_tier'])->id === $tier->id): ?>
                                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[#f28c28]">Palier actif</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php if($stats['best_tier']): ?>
                        <p class="mt-5 rounded-2xl bg-[#123b69]/5 px-4 py-3 text-sm text-slate-700">
                            Meilleur prix possible: <strong><?php echo e(number_format((float) $stats['best_tier']->price_per_person, 0, ',', ' ')); ?> DH</strong>
                            si le groupe atteint <?php echo e($stats['best_tier']->min_people); ?> personnes.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-[#123b69]">Progression du groupe</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-3xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Participants confirmes</div>
                            <div class="mt-2 text-3xl font-bold text-[#123b69]"><?php echo e($stats['current_participants']); ?></div>
                        </div>
                        <div class="rounded-3xl bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Restants pour garantir</div>
                            <div class="mt-2 text-3xl font-bold text-[#f28c28]"><?php echo e($stats['remaining_to_guarantee']); ?></div>
                        </div>
                    </div>
                    <div class="mt-5 rounded-3xl bg-emerald-50 px-4 py-4 text-sm text-emerald-700">
                        <?php echo e($stats['is_guaranteed'] ? 'Bonne nouvelle, ce voyage est desormais garanti.' : 'Le voyage n est pas encore garanti. Invitez votre groupe pour atteindre le seuil plus vite.'); ?>

                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\group-deals\show.blade.php ENDPATH**/ ?>