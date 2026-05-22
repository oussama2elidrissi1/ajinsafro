

<?php $__env->startSection('title', 'Demandes Formule �?conomique'); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => 'Demandes Formule �?conomique','subtitle' => 'Suivez les demandes envoyées depuis la page publique.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule �?conomique', 'url' => route('admin.economic-offers.index')],
            ['label' => 'Demandes'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Demandes Formule �?conomique','subtitle' => 'Suivez les demandes envoyées depuis la page publique.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule �?conomique', 'url' => route('admin.economic-offers.index')],
            ['label' => 'Demandes'],
        ])]); ?>
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

    <?php if (isset($component)) { $__componentOriginal775da4db6660fa0c0efa99eeb44c6fa5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal775da4db6660fa0c0efa99eeb44c6fa5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.filter-panel','data' => ['action' => route('admin.economic-offers.requests.index'),'resetUrl' => route('admin.economic-offers.requests.index'),'gridClass' => 'row g-3 align-items-end']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.filter-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.economic-offers.requests.index')),'reset-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.economic-offers.requests.index')),'grid-class' => 'row g-3 align-items-end']); ?>
         <?php $__env->slot('fields', null, []); ?> 
            <div class="col-md-4">
                <label class="form-label">Recherche</label>
                <input type="text" name="q" value="<?php echo e($filters['q']); ?>" class="form-control" placeholder="Nom, email, téléphone, offre">
            </div>
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if($filters['status'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
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
        <?php if($requests->isEmpty()): ?>
            <?php if (isset($component)) { $__componentOriginal99089f8e2ef4184d7d35db81d60c6521 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal99089f8e2ef4184d7d35db81d60c6521 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.empty-state','data' => ['title' => 'Aucune demande','message' => 'Les demandes du site public apparaîtront ici.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Aucune demande','message' => 'Les demandes du site public apparaîtront ici.']); ?>
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
            <div class="table-responsive">
                <table class="aj-table" style="width:100%;">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Offre</th>
                        <th>Départ choisi</th>
                        <th>Statut</th>
                        <th>Agent</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requestItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($requestItem->created_at?->format('d/m/Y H:i')); ?></td>
                            <td>
                                <div class="fw-bold"><?php echo e($requestItem->full_name); ?></div>
                                <div class="text-muted small"><?php echo e($requestItem->phone); ?> · <?php echo e($requestItem->email); ?></div>
                            </td>
                            <td><?php echo e($requestItem->offer_title ?: optional($requestItem->offer)->title ?: 'Non associée'); ?></td>
                            <td><?php echo e($requestItem->selected_departure_date?->format('d/m/Y') ?: '�?"'); ?></td>
                            <td>
                                <?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['type' => match($requestItem->status){
                                        'new' => 'warning',
                                        'contacted' => 'info',
                                        'confirmed' => 'success',
                                        default => 'danger'
                                    },'label' => $requestItem->status_label]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(match($requestItem->status){
                                        'new' => 'warning',
                                        'contacted' => 'info',
                                        'confirmed' => 'success',
                                        default => 'danger'
                                    }),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($requestItem->status_label)]); ?>
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
                            <td><?php echo e($requestItem->responsible_agent ?: '�?"'); ?></td>
                            <td class="text-end">
                                <a href="<?php echo e(route('admin.economic-offers.requests.show', $requestItem)); ?>" class="aj-btn aj-btn-soft btn-sm">
                                    Voir le détail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <?php if (isset($component)) { $__componentOriginalef886446d0d494c63255f0af1f6da7a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef886446d0d494c63255f0af1f6da7a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.pagination-footer','data' => ['paginator' => $requests]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.pagination-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($requests)]); ?>
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



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\economic-offers\requests\index.blade.php ENDPATH**/ ?>