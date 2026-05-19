

<?php $__env->startSection('title', 'Packs hÃ©bergement'); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => 'Packs hÃ©bergement','subtitle' => 'GÃ©rez les packs d\'hÃ©bergement affichÃ©s sur le site.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Packs hÃ©bergement'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Packs hÃ©bergement','subtitle' => 'GÃ©rez les packs d\'hÃ©bergement affichÃ©s sur le site.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Packs hÃ©bergement'],
        ])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.accommodation-packages.create')); ?>" class="aj-btn aj-btn-primary">
                <i class="bx bx-plus"></i>
                <span>Nouveau pack</span>
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

    <?php if (isset($component)) { $__componentOriginaldb1b157d84f8f63332f3508c9e385c0a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldb1b157d84f8f63332f3508c9e385c0a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.flash-messages','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.flash-messages'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldb1b157d84f8f63332f3508c9e385c0a)): ?>
<?php $attributes = $__attributesOriginaldb1b157d84f8f63332f3508c9e385c0a; ?>
<?php unset($__attributesOriginaldb1b157d84f8f63332f3508c9e385c0a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldb1b157d84f8f63332f3508c9e385c0a)): ?>
<?php $component = $__componentOriginaldb1b157d84f8f63332f3508c9e385c0a; ?>
<?php unset($__componentOriginaldb1b157d84f8f63332f3508c9e385c0a); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginaldc8ea6d1c156289736a271a64b9dc41b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldc8ea6d1c156289736a271a64b9dc41b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.kpi-cards','data' => ['kpis' => [
            ['label' => 'Total packs', 'value' => number_format($packages->total(), 0, ',', ' '), 'icon' => 'bx bx-buildings', 'color' => '-blue', 'note' => 'Base complÃ¨te'],
            ['label' => 'Actifs', 'value' => number_format($packages->where('is_active', true)->count(), 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Visibles sur le site'],
            ['label' => 'En vedette', 'value' => number_format($packages->where('is_featured', true)->count(), 0, ',', ' '), 'icon' => 'bx bx-star', 'color' => '-orange', 'note' => 'Mis en avant'],
            ['label' => 'Prix moyen', 'value' => number_format($packages->avg('price_from') ?? 0, 0, ',', ' ') . ' DH', 'icon' => 'bx bx-wallet', 'color' => '-violet', 'note' => 'Moyenne base'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.kpi-cards'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['kpis' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Total packs', 'value' => number_format($packages->total(), 0, ',', ' '), 'icon' => 'bx bx-buildings', 'color' => '-blue', 'note' => 'Base complÃ¨te'],
            ['label' => 'Actifs', 'value' => number_format($packages->where('is_active', true)->count(), 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Visibles sur le site'],
            ['label' => 'En vedette', 'value' => number_format($packages->where('is_featured', true)->count(), 0, ',', ' '), 'icon' => 'bx bx-star', 'color' => '-orange', 'note' => 'Mis en avant'],
            ['label' => 'Prix moyen', 'value' => number_format($packages->avg('price_from') ?? 0, 0, ',', ' ') . ' DH', 'icon' => 'bx bx-wallet', 'color' => '-violet', 'note' => 'Moyenne base'],
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

    <section class="aj-panel">
        <?php if($packages->isEmpty()): ?>
            <?php if (isset($component)) { $__componentOriginal99089f8e2ef4184d7d35db81d60c6521 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal99089f8e2ef4184d7d35db81d60c6521 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.empty-state','data' => ['title' => 'Aucun pack hÃ©bergement','message' => 'CrÃ©ez votre premier pack pour l\'afficher sur le site.','actionUrl' => route('admin.accommodation-packages.create'),'actionLabel' => 'Nouveau pack']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Aucun pack hÃ©bergement','message' => 'CrÃ©ez votre premier pack pour l\'afficher sur le site.','action-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.accommodation-packages.create')),'action-label' => 'Nouveau pack']); ?>
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
            <div class="table-responsive" style="overflow-x:auto;">
                <table class="aj-table" style="width:100%;border-collapse:separate;border-spacing:0;">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Titre</th>
                            <th>Destination</th>
                            <th>DurÃ©e</th>
                            <th>Pension</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Vedette</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $packages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $package): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <?php if (isset($component)) { $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.image-thumb','data' => ['src' => $package->image_url,'alt' => $package->title,'size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.image-thumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($package->image_url),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($package->title),'size' => 'sm']); ?>
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
                                    <div style="font-weight:800;color:#102340;"><?php echo e($package->title); ?></div>
                                    <div style="font-size:12px;font-weight:700;color:#7a879a;">#<?php echo e($package->id); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight:700;color:#253754;font-size:13px;"><?php echo e($package->city ?? 'Ville non renseignÃ©e'); ?></div>
                                    <div style="font-size:12px;font-weight:600;color:#7a879a;"><?php echo e($package->country ?? ''); ?></div>
                                </td>
                                <td><?php echo e($package->duration_days); ?>j / <?php echo e($package->nights); ?>n</td>
                                <td><?php echo e($package->pension_type ?? 'â€”'); ?></td>
                                <td><?php echo e($package->accommodation_type ?? 'â€”'); ?></td>
                                <td>
                                    <span style="color:var(--ajp-ink);font-size:15px;font-weight:900;white-space:nowrap;">
                                        <?php echo e(number_format($package->price_from, 0, ',', ' ')); ?> <?php echo e($package->currency); ?>

                                    </span>
                                </td>
                                <td>
                                    <?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['type' => $package->is_featured ? 'warning' : 'neutral','label' => $package->is_featured ? 'Oui' : 'Non']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($package->is_featured ? 'warning' : 'neutral'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($package->is_featured ? 'Oui' : 'Non')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $attributes = $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $component = $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['type' => $package->is_active ? 'success' : 'neutral','label' => $package->is_active ? 'Actif' : 'Inactif']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($package->is_active ? 'success' : 'neutral'),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($package->is_active ? 'Actif' : 'Inactif')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $attributes = $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $component = $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (isset($component)) { $__componentOriginala07abf6c4ac26573367cdce79eb1edd5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala07abf6c4ac26573367cdce79eb1edd5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.action-buttons','data' => ['editUrl' => route('admin.accommodation-packages.edit', $package),'deleteUrl' => route('admin.accommodation-packages.destroy', $package),'deleteConfirm' => 'Supprimer ce pack ?']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.action-buttons'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['edit-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.accommodation-packages.edit', $package)),'delete-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.accommodation-packages.destroy', $package)),'delete-confirm' => 'Supprimer ce pack ?']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala07abf6c4ac26573367cdce79eb1edd5)): ?>
<?php $attributes = $__attributesOriginala07abf6c4ac26573367cdce79eb1edd5; ?>
<?php unset($__attributesOriginala07abf6c4ac26573367cdce79eb1edd5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala07abf6c4ac26573367cdce79eb1edd5)): ?>
<?php $component = $__componentOriginala07abf6c4ac26573367cdce79eb1edd5; ?>
<?php unset($__componentOriginala07abf6c4ac26573367cdce79eb1edd5); ?>
<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            <?php if (isset($component)) { $__componentOriginalef886446d0d494c63255f0af1f6da7a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef886446d0d494c63255f0af1f6da7a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.pagination-footer','data' => ['paginator' => $packages]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.pagination-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($packages)]); ?>
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
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\accommodation-packages\index.blade.php ENDPATH**/ ?>