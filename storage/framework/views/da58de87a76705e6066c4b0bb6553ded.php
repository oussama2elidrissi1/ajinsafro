

<?php $__env->startSection('title', 'Points de vente'); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => 'Points de vente','subtitle' => 'Pilotage des points de vente Ajinsafro, de leurs responsables et de leur activitÃ© commerciale.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Points de vente','subtitle' => 'Pilotage des points de vente Ajinsafro, de leurs responsables et de leur activitÃ© commerciale.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente'],
        ])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.agencies.performance')); ?>" class="aj-btn aj-btn-soft">
                <i class="bx bx-bar-chart-alt-2"></i>
                <span>Performance</span>
            </a>
            <a href="<?php echo e(route('admin.agencies.create')); ?>" class="aj-btn aj-btn-primary">
                <i class="bx bx-plus"></i>
                <span>Nouveau point de vente</span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.kpi-cards','data' => ['kpis' => $kpis]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.kpi-cards'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['kpis' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($kpis)]); ?>
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

    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="aj-filter-grid" style="grid-template-columns:minmax(220px,1.4fr) repeat(5,minmax(0,.8fr)) auto;">
                    <div class="aj-field">
                        <input type="text" name="search" class="aj-control" placeholder="Nom, code, ville, email..." value="<?php echo e($filters['search']); ?>">
                    </div>
                    <div class="aj-field">
                        <select name="city" class="aj-control">
                            <option value="">Toutes les villes</option>
                            <?php $__currentLoopData = $cityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($option); ?>" <?php if($filters['city'] === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="country" class="aj-control">
                            <option value="">Tous les pays</option>
                            <?php $__currentLoopData = $countryOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($option); ?>" <?php if($filters['country'] === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="status" class="aj-control">
                            <option value="">Tous les statuts</option>
                            <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php if($filters['status'] === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="agency_type" class="aj-control">
                            <option value="">Tous les types</option>
                            <?php $__currentLoopData = $agencyTypeLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php if($filters['agencyType'] === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="manager_id" class="aj-control">
                            <option value="">Tous les managers</option>
                            <?php $__currentLoopData = $managerOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $manager): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($manager->id); ?>" <?php if($filters['managerId'] === $manager->id): echo 'selected'; endif; ?>><?php echo e($manager->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="aj-btn aj-btn-primary">
                            <i class="bx bx-filter-alt"></i>
                            <span>Filtrer</span>
                        </button>
                        <a href="<?php echo e(route('admin.agencies.index')); ?>" class="aj-btn aj-btn-soft">
                            <i class="bx bx-reset"></i>
                            <span>Reset</span>
                        </a>
                    </div>
                </div>
            </form>

            <?php if($agencies->isEmpty()): ?>
                <?php if (isset($component)) { $__componentOriginal99089f8e2ef4184d7d35db81d60c6521 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal99089f8e2ef4184d7d35db81d60c6521 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.empty-state','data' => ['title' => 'Aucun point de vente','message' => 'Aucun point de vente ne correspond aux filtres actuels.','actionUrl' => route('admin.agencies.create'),'actionLabel' => 'CrÃ©er un point de vente']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Aucun point de vente','message' => 'Aucun point de vente ne correspond aux filtres actuels.','action-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.agencies.create')),'action-label' => 'CrÃ©er un point de vente']); ?>
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
                    <table class="aj-table" style="width:100%;border-collapse:separate;border-spacing:0;">
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Point de vente</th>
                                <th>Ville</th>
                                <th>Pays</th>
                                <th>TÃ©lÃ©phone</th>
                                <th>Email</th>
                                <th>Manager</th>
                                <th>EmployÃ©s</th>
                                <th>RÃ©servations</th>
                                <th>CA</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $agencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $statusType = match($agency->status) {
                                        \App\Models\Branch::STATUS_ACTIVE => 'success',
                                        \App\Models\Branch::STATUS_SUSPENDED => 'danger',
                                        default => 'warning',
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <?php if($agency->logo_url): ?>
                                            <img src="<?php echo e($agency->logo_url); ?>" alt="<?php echo e($agency->name); ?>" style="width:42px;height:42px;border-radius:12px;object-fit:cover;">
                                        <?php else: ?>
                                            <span class="aj-badge aj-badge-info" style="display:inline-flex;min-width:42px;justify-content:center;"><?php echo e(strtoupper(substr($agency->code, 0, 2))); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo e(route('admin.agencies.show', $agency)); ?>" class="fw-semibold text-decoration-none"><?php echo e($agency->name); ?></a>
                                        <div class="text-muted small"><?php echo e($agency->code); ?> Â· <?php echo e($agencyTypeLabels[$agency->agency_type] ?? $agency->agency_type); ?></div>
                                    </td>
                                    <td><?php echo e($agency->city ?: 'â€”'); ?></td>
                                    <td><?php echo e($agency->country ?: 'â€”'); ?></td>
                                    <td><?php echo e($agency->phone ?: 'â€”'); ?></td>
                                    <td><?php echo e($agency->email ?: 'â€”'); ?></td>
                                    <td><?php echo e($agency->manager?->name ?: 'â€”'); ?></td>
                                    <td><?php echo e($agency->agency_employees_count); ?></td>
                                    <td><?php echo e($agency->reservations_count); ?></td>
                                    <td><?php echo e(number_format((float) ($agency->revenue_total ?? 0), 0, ',', ' ')); ?> DH</td>
                                    <td><?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['type' => $statusType,'label' => $statusLabels[$agency->status] ?? $agency->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusType),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusLabels[$agency->status] ?? $agency->status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $attributes = $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33)): ?>
<?php $component = $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33; ?>
<?php unset($__componentOriginal92e51077c3bdcbfa01c516c134fd0f33); ?>
<?php endif; ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <form method="POST" action="<?php echo e(route('admin.agencies.toggle-status', $agency)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <button type="submit" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;">
                                                    <?php echo e($agency->status === \App\Models\Branch::STATUS_ACTIVE ? 'DÃ©sactiver' : 'Activer'); ?>

                                                </button>
                                            </form>
                                            <a href="<?php echo e(route('admin.agencies.show', $agency)); ?>" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;">Voir</a>
                                            <a href="<?php echo e(route('admin.agencies.edit', $agency)); ?>" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;">Modifier</a>
                                            <form method="POST" action="<?php echo e(route('admin.agencies.destroy', $agency)); ?>" onsubmit="return confirm('Archiver ce point de vente ?');">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;color:#d92d20;">Archiver</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php if (isset($component)) { $__componentOriginalef886446d0d494c63255f0af1f6da7a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef886446d0d494c63255f0af1f6da7a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.pagination-footer','data' => ['paginator' => $agencies]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.pagination-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($agencies)]); ?>
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
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\agencies\index.blade.php ENDPATH**/ ?>