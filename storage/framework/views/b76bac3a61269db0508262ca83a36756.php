<?php $__env->startSection('title', 'Performance points de vente'); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => 'Performance points de vente','subtitle' => 'Comparatif des réservations, du chiffre d’affaires et des commissions estimées par point de vente.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente', 'url' => route('admin.agencies.index')],
            ['label' => 'Performance'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Performance points de vente','subtitle' => 'Comparatif des réservations, du chiffre d’affaires et des commissions estimées par point de vente.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente', 'url' => route('admin.agencies.index')],
            ['label' => 'Performance'],
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

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="aj-filter-grid" style="grid-template-columns:repeat(5,minmax(0,1fr)) auto;">
                    <div class="aj-field">
                        <select name="period" class="aj-control">
                            <option value="7" <?php if($filters['period'] === '7'): echo 'selected'; endif; ?>>7 jours</option>
                            <option value="30" <?php if($filters['period'] === '30'): echo 'selected'; endif; ?>>30 jours</option>
                            <option value="90" <?php if($filters['period'] === '90'): echo 'selected'; endif; ?>>90 jours</option>
                            <option value="365" <?php if($filters['period'] === '365'): echo 'selected'; endif; ?>>12 mois</option>
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="agency_id" class="aj-control">
                            <option value="">Tous les points de vente</option>
                            <?php $__currentLoopData = $agencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($agency->id); ?>" <?php if($filters['agencyId'] === $agency->id): echo 'selected'; endif; ?>><?php echo e($agency->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
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
                        <input type="text" name="prestation_type" class="aj-control" placeholder="Type produit" value="<?php echo e($filters['prestationType']); ?>">
                    </div>
                    <div></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="aj-btn aj-btn-primary">Filtrer</button>
                        <a href="<?php echo e(route('admin.agencies.performance')); ?>" class="aj-btn aj-btn-soft">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Comparatif par point de vente</h5>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Point de vente</th>
                                    <th>Réservations</th>
                                    <th>Validées</th>
                                    <th>En attente</th>
                                    <th>Annulées</th>
                                    <th>CA</th>
                                    <th>Commission</th>
                                    <th>Conversion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><a href="<?php echo e(route('admin.agencies.show', $row['agency'])); ?>"><?php echo e($row['agency']->name); ?></a></td>
                                        <td><?php echo e($row['total']); ?></td>
                                        <td><?php echo e($row['confirmed']); ?></td>
                                        <td><?php echo e($row['pending']); ?></td>
                                        <td><?php echo e($row['cancelled']); ?></td>
                                        <td><?php echo e(number_format($row['revenue'], 0, ',', ' ')); ?> DH</td>
                                        <td><?php echo e(number_format($row['estimated_commission'], 0, ',', ' ')); ?> DH</td>
                                        <td><?php echo e(number_format($row['conversion_rate'], 1, ',', ' ')); ?>%</td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="8" class="text-center text-muted">Aucune donnée disponible.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Top employés</h5>
                    <div class="d-flex flex-column gap-3">
                        <?php $__empty_1 = true; $__currentLoopData = $topEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="d-flex justify-content-between align-items-center border rounded-3 px-3 py-2">
                                <div>
                                    <div class="fw-semibold"><?php echo e($row['employee']->full_name); ?></div>
                                    <div class="text-muted small">
                                        <?php echo e($row['employee']->branch?->name ?: '—'); ?> · <?php echo e($row['employee']->position ?: '—'); ?>

                                    </div>
                                </div>
                                <span class="aj-badge aj-badge-info"><?php echo e($row['count']); ?> résa</span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-muted mb-0">Aucun employé disponible pour cette période.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\agencies\performance.blade.php ENDPATH**/ ?>