

<?php $__env->startSection('title', 'Group Deals'); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => 'Offres de voyage de groupe','subtitle' => 'Créez des offres autonomes avec progression, garantie et prix par paliers.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Group Deals'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Offres de voyage de groupe','subtitle' => 'Créez des offres autonomes avec progression, garantie et prix par paliers.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Group Deals'],
        ])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.group-deals.create')); ?>" class="aj-btn aj-btn-primary">
                <i class="bx bx-plus"></i>
                <span>Nouvelle offre</span>
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
            ['label' => 'Total offres', 'value' => number_format($groupDeals->total(), 0, ',', ' '), 'icon' => 'bx bx-group', 'color' => '-blue', 'note' => 'Base complète'],
            ['label' => 'Actives', 'value' => number_format($groupDeals->where('status', 'active')->count(), 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'En cours'],
            ['label' => 'Complètes', 'value' => number_format($groupDeals->where('status', 'completed')->count(), 0, ',', ' '), 'icon' => 'bx bx-check-double', 'color' => '-orange', 'note' => 'Garanties'],
            ['label' => 'En cours', 'value' => number_format($groupDeals->where('status', 'in_progress')->count(), 0, ',', ' '), 'icon' => 'bx bx-time', 'color' => '-violet', 'note' => 'Recrutement'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.kpi-cards'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['kpis' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Total offres', 'value' => number_format($groupDeals->total(), 0, ',', ' '), 'icon' => 'bx bx-group', 'color' => '-blue', 'note' => 'Base complète'],
            ['label' => 'Actives', 'value' => number_format($groupDeals->where('status', 'active')->count(), 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'En cours'],
            ['label' => 'Complètes', 'value' => number_format($groupDeals->where('status', 'completed')->count(), 0, ',', ' '), 'icon' => 'bx bx-check-double', 'color' => '-orange', 'note' => 'Garanties'],
            ['label' => 'En cours', 'value' => number_format($groupDeals->where('status', 'in_progress')->count(), 0, ',', ' '), 'icon' => 'bx bx-time', 'color' => '-violet', 'note' => 'Recrutement'],
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.filter-panel','data' => ['resetUrl' => route('admin.group-deals.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.filter-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['reset-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.group-deals.index'))]); ?>
         <?php $__env->slot('fields', null, []); ?> 
            <div class="aj-field aj-search-wrap">
                <span class="aj-search-icon"><i class="bx bx-search"></i></span>
                <input type="text" name="q" class="aj-control" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Titre ou destination">
            </div>
            <div class="aj-field">
                <select name="status" class="aj-control">
                    <option value="">Tous les statuts</option>
                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($status); ?>" <?php if(($filters['status'] ?? '') === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
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
        <?php if($groupDeals->isEmpty()): ?>
            <?php if (isset($component)) { $__componentOriginal99089f8e2ef4184d7d35db81d60c6521 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal99089f8e2ef4184d7d35db81d60c6521 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.empty-state','data' => ['title' => 'Aucune offre Group Deal','message' => 'Créez votre première offre pour lancer un voyage de groupe.','actionUrl' => route('admin.group-deals.create'),'actionLabel' => 'Nouvelle offre']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Aucune offre Group Deal','message' => 'Créez votre première offre pour lancer un voyage de groupe.','action-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.group-deals.create')),'action-label' => 'Nouvelle offre']); ?>
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
                            <th>Visuel</th>
                            <th>Offre</th>
                            <th>Dates</th>
                            <th>Progression</th>
                            <th>Prix actuel</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $groupDeals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <?php if (isset($component)) { $__componentOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf5efe4dc5fa3be8d4c5a3e56a4ce9c7d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.image-thumb','data' => ['src' => $deal->image_url,'alt' => $deal->title,'size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.image-thumb'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($deal->image_url),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($deal->title),'size' => 'sm']); ?>
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
                                    <div style="font-weight:800;color:#102340;"><?php echo e($deal->title); ?></div>
                                    <div style="font-size:12px;font-weight:700;color:#7a879a;"><?php echo e($deal->destination ?: 'Destination non renseignée'); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight:700;color:#253754;font-size:13px;"><?php echo e(optional($deal->start_date)->format('d/m/Y') ?: 'N/A'); ?></div>
                                    <?php if($deal->end_date): ?>
                                        <div style="font-size:12px;font-weight:600;color:#7a879a;">�?' <?php echo e($deal->end_date->format('d/m/Y')); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size:13px;font-weight:700;color:#31435c;"><?php echo e($deal->current_participants); ?>/<?php echo e($deal->max_participants); ?> inscrits</div>
                                    <div class="progress mt-2" style="height:8px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo e($deal->progress_percent); ?>%"></div>
                                    </div>
                                    <div style="font-size:12px;font-weight:600;color:#7a879a;margin-top:4px;">
                                        <?php if($deal->remaining_to_guarantee > 0): ?>
                                            Il reste <?php echo e($deal->remaining_to_guarantee); ?> personne(s) pour garantir
                                        <?php else: ?>
                                            Voyage garanti
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="color:var(--ajp-ink);font-size:15px;font-weight:900;white-space:nowrap;">
                                        <?php echo e($deal->current_price ? number_format((float) $deal->current_price, 0, ',', ' ') . ' DH' : 'N/A'); ?>

                                    </span>
                                </td>
                                <td>
                                    <?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['type' => 'neutral','label' => $deal->status_label]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'neutral','label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($deal->status_label)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.action-buttons','data' => ['viewUrl' => route('admin.group-deals.show', $deal),'editUrl' => route('admin.group-deals.edit', $deal)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.action-buttons'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['view-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.group-deals.show', $deal)),'edit-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.group-deals.edit', $deal))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.pagination-footer','data' => ['paginator' => $groupDeals]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.pagination-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($groupDeals)]); ?>
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



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\group-deals\offers\index.blade.php ENDPATH**/ ?>