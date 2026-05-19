

<?php $__env->startSection('title', $employee->full_name); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => $employee->full_name,'subtitle' => 'Fiche employe point de vente, rattachement et activite liee aux reservations.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Employes des points de vente', 'url' => route('admin.agency-employees.index')],
            ['label' => $employee->full_name],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($employee->full_name),'subtitle' => 'Fiche employe point de vente, rattachement et activite liee aux reservations.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Employes des points de vente', 'url' => route('admin.agency-employees.index')],
            ['label' => $employee->full_name],
        ])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.agencies.show', $employee->branch_id)); ?>" class="aj-btn aj-btn-soft">
                <i class="bx bx-buildings"></i>
                <span>Voir le point de vente</span>
            </a>
            <?php if($employee->user_id): ?>
                <a href="<?php echo e(route('admin.agency-accounts.edit', $employee->user_id)); ?>" class="aj-btn aj-btn-soft">
                    <i class="bx bx-id-card"></i>
                    <span>Gerer le compte login</span>
                </a>
            <?php else: ?>
                <a href="<?php echo e(route('admin.agency-accounts.create', ['employee_id' => $employee->id])); ?>" class="aj-btn aj-btn-soft">
                    <i class="bx bx-user-plus"></i>
                    <span>Creer compte login</span>
                </a>
            <?php endif; ?>
            <a href="<?php echo e(route('admin.agency-employees.edit', $employee)); ?>" class="aj-btn aj-btn-primary">
                <i class="bx bx-pencil"></i>
                <span>Modifier</span>
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

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Informations</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Point de vente</dt><dd class="col-sm-7"><?php echo e($employee->branch?->name ?: 'â€”'); ?></dd>
                        <dt class="col-sm-5">Poste</dt><dd class="col-sm-7"><?php echo e($employee->position ?: 'â€”'); ?></dd>
                        <dt class="col-sm-5">Statut</dt><dd class="col-sm-7"><?php echo e(\App\Models\AgencyEmployee::statusLabels()[$employee->status] ?? $employee->status); ?></dd>
                        <dt class="col-sm-5">Email</dt><dd class="col-sm-7"><?php echo e($employee->email ?: 'â€”'); ?></dd>
                        <dt class="col-sm-5">TÃ©lÃ©phone</dt><dd class="col-sm-7"><?php echo e($employee->phone ?: 'â€”'); ?></dd>
                        <dt class="col-sm-5">Login</dt><dd class="col-sm-7"><?php echo e($employee->can_login ? 'Oui' : 'Non'); ?></dd>
                        <dt class="col-sm-5">RÃ´le</dt><dd class="col-sm-7"><?php echo e($employee->user?->roles->pluck('name')->join(', ') ?: 'â€”'); ?></dd>
                        <dt class="col-sm-5">DerniÃ¨re connexion</dt><dd class="col-sm-7"><?php echo e($employee->user?->last_login_at?->format('d/m/Y H:i') ?: 'â€”'); ?></dd>
                        <dt class="col-sm-5">Departement</dt><dd class="col-sm-7"><?php echo e($employee->department ?: 'â€”'); ?></dd>
                        <dt class="col-sm-5">Type employe</dt><dd class="col-sm-7"><?php echo e($employee->employee_type ?: 'â€”'); ?></dd>
                        <dt class="col-sm-5">Contrat</dt><dd class="col-sm-7"><?php echo e($employee->contract_type ?: 'â€”'); ?></dd>
                    </dl>
                    <div class="mt-3">
                        <strong>Note interne</strong>
                        <p class="text-muted mb-0"><?php echo e($employee->notes ?: 'Aucune note.'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">RÃ©servations liÃ©es</h5>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Statut</th>
                                    <th>Paiement</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $recentReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>#<?php echo e($reservation->id); ?></td>
                                        <td><?php echo e(trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: 'â€”'); ?></td>
                                        <td><?php echo e($reservation->status); ?></td>
                                        <td><?php echo e($reservation->payment_type ?: 'â€”'); ?></td>
                                        <td><?php echo e($reservation->created_at?->format('d/m/Y H:i') ?: 'â€”'); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="5" class="text-center text-muted">Aucune rÃ©servation liÃ©e Ã  ce collaborateur.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\agency-employees\show.blade.php ENDPATH**/ ?>