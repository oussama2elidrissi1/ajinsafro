

<?php $__env->startSection('title', $agency->name); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => $agency->name,'subtitle' => 'Vue détaillée du point de vente, de ses équipes et de son activité.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente', 'url' => route('admin.agencies.index')],
            ['label' => $agency->name],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($agency->name),'subtitle' => 'Vue détaillée du point de vente, de ses équipes et de son activité.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente', 'url' => route('admin.agencies.index')],
            ['label' => $agency->name],
        ])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.agency-employees.create', ['agency_id' => $agency->id])); ?>" class="aj-btn aj-btn-soft">
                <i class="bx bx-user-plus"></i>
                <span>Ajouter un employe</span>
            </a>
            <a href="<?php echo e(route('admin.agencies.performance', ['agency_id' => $agency->id])); ?>" class="aj-btn aj-btn-soft">
                <i class="bx bx-bar-chart-alt-2"></i>
                <span>Performance</span>
            </a>
            <a href="<?php echo e(route('admin.agencies.edit', $agency)); ?>" class="aj-btn aj-btn-primary">
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

    <?php if (isset($component)) { $__componentOriginaldc8ea6d1c156289736a271a64b9dc41b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldc8ea6d1c156289736a271a64b9dc41b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.kpi-cards','data' => ['kpis' => [
        ['label' => 'Réservations', 'value' => number_format($totals['reservations_total'], 0, ',', ' '), 'icon' => 'bx bx-calendar-check', 'color' => '-blue', 'note' => 'Depuis l�?Touverture'],
        ['label' => 'Ce mois', 'value' => number_format($totals['reservations_month'], 0, ',', ' '), 'icon' => 'bx bx-time-five', 'color' => '-green', 'note' => 'Activité mensuelle'],
        ['label' => 'CA', 'value' => number_format($totals['revenue_total'], 0, ',', ' ') . ' DH', 'icon' => 'bx bx-line-chart', 'color' => '-orange', 'note' => 'Montant estimé'],
        ['label' => 'Commission', 'value' => number_format($totals['estimated_commission'], 0, ',', ' ') . ' DH', 'icon' => 'bx bx-wallet', 'color' => '-violet', 'note' => 'Projection'],
        ['label' => 'Employés actifs', 'value' => number_format($totals['employees_active'], 0, ',', ' '), 'icon' => 'bx bx-user-check', 'color' => '-blue', 'note' => 'Comptes opérationnels'],
        ['label' => 'Clients traités', 'value' => number_format($totals['clients_handled'], 0, ',', ' '), 'icon' => 'bx bx-group', 'color' => '-green', 'note' => 'Clients distincts'],
    ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.kpi-cards'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['kpis' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
        ['label' => 'Réservations', 'value' => number_format($totals['reservations_total'], 0, ',', ' '), 'icon' => 'bx bx-calendar-check', 'color' => '-blue', 'note' => 'Depuis l�?Touverture'],
        ['label' => 'Ce mois', 'value' => number_format($totals['reservations_month'], 0, ',', ' '), 'icon' => 'bx bx-time-five', 'color' => '-green', 'note' => 'Activité mensuelle'],
        ['label' => 'CA', 'value' => number_format($totals['revenue_total'], 0, ',', ' ') . ' DH', 'icon' => 'bx bx-line-chart', 'color' => '-orange', 'note' => 'Montant estimé'],
        ['label' => 'Commission', 'value' => number_format($totals['estimated_commission'], 0, ',', ' ') . ' DH', 'icon' => 'bx bx-wallet', 'color' => '-violet', 'note' => 'Projection'],
        ['label' => 'Employés actifs', 'value' => number_format($totals['employees_active'], 0, ',', ' '), 'icon' => 'bx bx-user-check', 'color' => '-blue', 'note' => 'Comptes opérationnels'],
        ['label' => 'Clients traités', 'value' => number_format($totals['clients_handled'], 0, ',', ' '), 'icon' => 'bx bx-group', 'color' => '-green', 'note' => 'Clients distincts'],
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

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Informations générales</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Code</dt><dd class="col-sm-7"><?php echo e($agency->code); ?></dd>
                        <dt class="col-sm-5">Type</dt><dd class="col-sm-7"><?php echo e(\App\Models\Branch::agencyTypeLabels()[$agency->agency_type] ?? $agency->agency_type); ?></dd>
                        <dt class="col-sm-5">Statut</dt><dd class="col-sm-7"><?php echo e(\App\Models\Branch::statusLabels()[$agency->status] ?? $agency->status); ?></dd>
                        <dt class="col-sm-5">Ville</dt><dd class="col-sm-7"><?php echo e($agency->city ?: '�?"'); ?></dd>
                        <dt class="col-sm-5">Pays</dt><dd class="col-sm-7"><?php echo e($agency->country ?: '�?"'); ?></dd>
                        <dt class="col-sm-5">Téléphone</dt><dd class="col-sm-7"><?php echo e($agency->phone ?: '�?"'); ?></dd>
                        <dt class="col-sm-5">Email</dt><dd class="col-sm-7"><?php echo e($agency->email ?: '�?"'); ?></dd>
                        <dt class="col-sm-5">Manager</dt><dd class="col-sm-7"><?php echo e($agency->manager?->name ?: '�?"'); ?></dd>
                        <dt class="col-sm-5">Commission</dt><dd class="col-sm-7"><?php echo e($agency->default_commission_value ? number_format((float) $agency->default_commission_value, 2, ',', ' ') . ' ' . (\App\Models\Branch::commissionTypeLabels()[$agency->default_commission_type] ?? '') : ($agency->default_commission_rate ? number_format($agency->default_commission_rate, 2, ',', ' ') . '%' : '�?"')); ?></dd>
                        <dt class="col-sm-5">Devise</dt><dd class="col-sm-7"><?php echo e($agency->currency ?: 'MAD'); ?></dd>
                        <dt class="col-sm-5">Objectif CA</dt><dd class="col-sm-7"><?php echo e($agency->monthly_revenue_target ? number_format((float) $agency->monthly_revenue_target, 0, ',', ' ') . ' ' . ($agency->currency ?: 'MAD') : '�?"'); ?></dd>
                        <dt class="col-sm-5">Objectif reservations</dt><dd class="col-sm-7"><?php echo e($agency->monthly_reservations_target ?: '�?"'); ?></dd>
                    </dl>
                    <?php if($agency->address): ?>
                        <div class="mt-3">
                            <strong>Adresse</strong>
                            <div class="text-muted"><?php echo e($agency->address); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if($agency->business_hours): ?>
                        <div class="mt-3">
                            <strong>Horaires</strong>
                            <div class="text-muted" style="white-space:pre-line;"><?php echo e($agency->business_hours); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Documents & notes</h5>
                    <?php if(!empty($agency->documents)): ?>
                        <div class="d-flex flex-column gap-2 mb-3">
                            <?php $__currentLoopData = $agency->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e(asset('storage/' . $document['path'])); ?>" target="_blank" class="aj-btn aj-btn-soft justify-content-start">
                                    <i class="bx bx-file"></i>
                                    <span><?php echo e($document['name']); ?></span>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-3">Aucun document chargé.</p>
                    <?php endif; ?>
                    <strong>Notes internes</strong>
                    <p class="text-muted mb-0" style="white-space:pre-line;"><?php echo e($agency->internal_notes ?: 'Aucune note interne.'); ?></p>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Performance mensuelle</h5>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Mois</th>
                                    <th>Réservations</th>
                                    <th>CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $monthlySeries['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($label); ?></td>
                                        <td><?php echo e($monthlySeries['reservations'][$index]); ?></td>
                                        <td><?php echo e(number_format($monthlySeries['revenue'][$index], 0, ',', ' ')); ?> DH</td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Employes du point de vente</h5>
                        <a href="<?php echo e(route('admin.agency-employees.index', ['branch_id' => $agency->id])); ?>" class="aj-btn aj-btn-soft">Voir tous</a>
                    </div>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Poste</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><a href="<?php echo e(route('admin.agency-employees.show', $employee)); ?>"><?php echo e($employee->full_name); ?></a></td>
                                        <td><?php echo e($employee->position ?: '�?"'); ?></td>
                                        <td><?php echo e($employee->user?->roles->pluck('name')->join(', ') ?: '�?"'); ?></td>
                                        <td><?php echo e(\App\Models\AgencyEmployee::statusLabels()[$employee->status] ?? $employee->status); ?></td>
                                        <td><?php echo e($employee->email ?: '�?"'); ?></td>
                                        <td><?php echo e($employee->phone ?: '�?"'); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="6" class="text-center text-muted">Aucun employé rattaché.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Dernières réservations</h5>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Statut</th>
                                    <th>Paiement</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $recentReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>#<?php echo e($reservation->id); ?></td>
                                        <td><?php echo e(trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '�?"'); ?></td>
                                        <td><?php echo e($reservation->status); ?></td>
                                        <td><?php echo e($reservation->payment_type ?: '�?"'); ?></td>
                                        <td><?php echo e(number_format((float) $reservation->paid_amount, 0, ',', ' ')); ?> DH</td>
                                        <td><?php echo e($reservation->created_at?->format('d/m/Y H:i') ?: '�?"'); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="6" class="text-center text-muted">Aucune réservation liée.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Commissions estimées</h5>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Réservation</th>
                                    <th>CA</th>
                                    <th>Taux</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $recentReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $reservationRevenue = (float) ($reservation->paid_amount ?? 0);
                                        $rate = (float) ($agency->default_commission_rate ?? 0);
                                    ?>
                                    <tr>
                                        <td>#<?php echo e($reservation->id); ?></td>
                                        <td><?php echo e(number_format($reservationRevenue, 0, ',', ' ')); ?> DH</td>
                                        <td><?php echo e($rate ? number_format($rate, 2, ',', ' ') . '%' : '�?"'); ?></td>
                                        <td><?php echo e($rate ? number_format($reservationRevenue * ($rate / 100), 0, ',', ' ') . ' DH' : '�?"'); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="4" class="text-center text-muted">Aucune donnée de commission.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\agencies\show.blade.php ENDPATH**/ ?>