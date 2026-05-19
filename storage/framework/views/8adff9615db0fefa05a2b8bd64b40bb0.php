
<?php $__env->startSection('title'); ?>
    <?php echo e(isset($trashed) && $trashed ? 'Clients supprimÃ©s' : 'Clients'); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => isset($trashed) && $trashed ? 'Corbeille â€“ Clients' : 'Liste des clients','subtitle' => 'GÃ©rez, filtrez et consultez la base clients.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Clients', 'url' => route('admin.customers.index')],
            ['label' => isset($trashed) && $trashed ? 'Corbeille' : 'Liste clients'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(isset($trashed) && $trashed ? 'Corbeille â€“ Clients' : 'Liste des clients'),'subtitle' => 'GÃ©rez, filtrez et consultez la base clients.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Clients', 'url' => route('admin.customers.index')],
            ['label' => isset($trashed) && $trashed ? 'Corbeille' : 'Liste clients'],
        ])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <?php if(!isset($trashed) || !$trashed): ?>
                <a href="<?php echo e(route('admin.customers.clients.create')); ?>" class="aj-btn aj-btn-primary">
                    <i class="bx bx-plus"></i>
                    <span>Nouveau client</span>
                </a>
                <a href="<?php echo e(route('admin.customers.clients.trashed')); ?>" class="aj-btn aj-btn-soft">
                    <i class="bx bx-trash"></i>
                    <span>Corbeille</span>
                </a>
            <?php else: ?>
                <a href="<?php echo e(route('admin.customers.clients.index')); ?>" class="aj-btn aj-btn-soft">
                    <i class="bx bx-list-ul"></i>
                    <span>Retour Ã  la liste</span>
                </a>
            <?php endif; ?>
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

    <?php if(!isset($trashed) || !$trashed): ?>
        <?php if (isset($component)) { $__componentOriginaldc8ea6d1c156289736a271a64b9dc41b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldc8ea6d1c156289736a271a64b9dc41b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.kpi-cards','data' => ['kpis' => [
                ['label' => 'Total clients', 'value' => number_format($clients->total(), 0, ',', ' '), 'icon' => 'bx bx-group', 'color' => '-blue', 'note' => 'Base complÃ¨te'],
                ['label' => 'Actifs', 'value' => number_format($clients->where('status', 'active')->count(), 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'En cours'],
                ['label' => 'VIP', 'value' => number_format($clients->where('status', 'vip')->count(), 0, ',', ' '), 'icon' => 'bx bx-star', 'color' => '-orange', 'note' => 'Prioritaires'],
                ['label' => 'Nouveaux ce mois', 'value' => number_format($clients->where('created_at', '>=', now()->startOfMonth())->count(), 0, ',', ' '), 'icon' => 'bx bx-user-plus', 'color' => '-violet', 'note' => 'Inscriptions'],
            ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.kpi-cards'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['kpis' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                ['label' => 'Total clients', 'value' => number_format($clients->total(), 0, ',', ' '), 'icon' => 'bx bx-group', 'color' => '-blue', 'note' => 'Base complÃ¨te'],
                ['label' => 'Actifs', 'value' => number_format($clients->where('status', 'active')->count(), 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'En cours'],
                ['label' => 'VIP', 'value' => number_format($clients->where('status', 'vip')->count(), 0, ',', ' '), 'icon' => 'bx bx-star', 'color' => '-orange', 'note' => 'Prioritaires'],
                ['label' => 'Nouveaux ce mois', 'value' => number_format($clients->where('created_at', '>=', now()->startOfMonth())->count(), 0, ',', ' '), 'icon' => 'bx bx-user-plus', 'color' => '-violet', 'note' => 'Inscriptions'],
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
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <?php if(isset($trashed) && $trashed): ?>
                        <p class="text-muted mb-3">Clients supprimÃ©s (corbeille). Vous pouvez restaurer ou supprimer dÃ©finitivement.</p>
                        <a href="<?php echo e(route('admin.customers.clients.index')); ?>" class="aj-btn aj-btn-soft mb-3"><i class="bx bx-list-ul me-1"></i> Retour Ã  la liste</a>
                    <?php endif; ?>

                    <form method="GET" class="mb-4">
                        <?php if(isset($trashed) && $trashed): ?>
                            <input type="hidden" name="trashed" value="1">
                        <?php endif; ?>
                        <div class="aj-filter-grid" style="grid-template-columns: minmax(200px, 1.4fr) repeat(4, minmax(0, .8fr)) minmax(160px, auto) auto;">
                            <div class="aj-field aj-search-wrap">
                                <span class="aj-search-icon"><i class="bx bx-search"></i></span>
                                <input type="text" name="search" class="aj-control" placeholder="Code, nom, email, tÃ©l..." value="<?php echo e(request('search')); ?>">
                            </div>
                            <?php if(!isset($trashed) || !$trashed): ?>
                                <div class="aj-field">
                                    <select name="status" class="aj-control">
                                        <option value="">Tous les statuts</option>
                                        <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Actif</option>
                                        <option value="inactive" <?php echo e(request('status') === 'inactive' ? 'selected' : ''); ?>>Inactif</option>
                                        <option value="blocked" <?php echo e(request('status') === 'blocked' ? 'selected' : ''); ?>>BloquÃ©</option>
                                        <option value="vip" <?php echo e(request('status') === 'vip' ? 'selected' : ''); ?>>VIP</option>
                                    </select>
                                </div>
                                <div class="aj-field">
                                    <select name="client_type" class="aj-control">
                                        <option value="">Tous les types</option>
                                        <option value="individual" <?php echo e(request('client_type') === 'individual' ? 'selected' : ''); ?>>Particulier</option>
                                        <option value="company" <?php echo e(request('client_type') === 'company' ? 'selected' : ''); ?>>SociÃ©tÃ©</option>
                                        <option value="agency" <?php echo e(request('client_type') === 'agency' ? 'selected' : ''); ?>>Agence</option>
                                    </select>
                                </div>
                                <div class="aj-field">
                                    <select name="source" class="aj-control">
                                        <option value="">Toutes les sources</option>
                                        <option value="website" <?php echo e(request('source') === 'website' ? 'selected' : ''); ?>>Site web</option>
                                        <option value="whatsapp" <?php echo e(request('source') === 'whatsapp' ? 'selected' : ''); ?>>WhatsApp</option>
                                        <option value="phone" <?php echo e(request('source') === 'phone' ? 'selected' : ''); ?>>TÃ©lÃ©phone</option>
                                        <option value="admin" <?php echo e(request('source') === 'admin' ? 'selected' : ''); ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="aj-field">
                                    <select name="assigned_to" class="aj-control">
                                        <option value="">Tous les agents</option>
                                        <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($u->id); ?>" <?php echo e(request('assigned_to') == $u->id ? 'selected' : ''); ?>><?php echo e($u->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="aj-btn aj-btn-primary w-100">
                                    <i class="bx bx-filter-alt"></i>
                                    <span>Filtrer</span>
                                </button>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?php echo e(isset($trashed) && $trashed ? route('admin.customers.clients.trashed') : route('admin.customers.clients.index')); ?>" class="aj-btn aj-btn-soft w-100">
                                    <i class="bx bx-reset"></i>
                                    <span>RÃ©initialiser</span>
                                </a>
                            </div>
                        </div>
                    </form>

                    <?php if(isset($trashed) && $trashed): ?>
                        <form action="<?php echo e(route('admin.customers.clients.bulk')); ?>" method="POST" id="bulk-form-trashed">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="ids" value="" id="bulk-ids-trashed">
                        </form>
                        <form action="<?php echo e(route('admin.customers.clients.bulk')); ?>" method="POST" id="bulk-form-force">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="force_delete">
                            <input type="hidden" name="ids" value="" id="bulk-ids-force">
                        </form>
                    <?php else: ?>
                        <form action="<?php echo e(route('admin.customers.clients.bulk')); ?>" method="POST" id="bulk-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="" id="bulk-action">
                        </form>
                    <?php endif; ?>

                    <?php if($clients->isEmpty()): ?>
                        <?php if (isset($component)) { $__componentOriginal99089f8e2ef4184d7d35db81d60c6521 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal99089f8e2ef4184d7d35db81d60c6521 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.empty-state','data' => ['title' => isset($trashed) && $trashed ? 'Aucun client dans la corbeille' : 'Aucun client','message' => isset($trashed) && $trashed ? 'La corbeille est vide.' : 'Aucun client ne correspond Ã  vos critÃ¨res. CrÃ©ez votre premier client.','actionUrl' => (!isset($trashed) || !$trashed) ? route('admin.customers.clients.create') : null,'actionLabel' => 'Nouveau client']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(isset($trashed) && $trashed ? 'Aucun client dans la corbeille' : 'Aucun client'),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(isset($trashed) && $trashed ? 'La corbeille est vide.' : 'Aucun client ne correspond Ã  vos critÃ¨res. CrÃ©ez votre premier client.'),'action-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((!isset($trashed) || !$trashed) ? route('admin.customers.clients.create') : null),'action-label' => 'Nouveau client']); ?>
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
                                        <?php if(!isset($trashed) || !$trashed): ?>
                                            <th width="40"><input type="checkbox" id="select-all" aria-label="Tout sÃ©lectionner"></th>
                                        <?php endif; ?>
                                        <th>Code</th>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Email</th>
                                        <th>TÃ©lÃ©phone</th>
                                        <th>WhatsApp</th>
                                        <th>NationalitÃ©</th>
                                        <th>Ville</th>
                                        <th>CatÃ©gorie</th>
                                        <th>Budget</th>
                                        <th>Statut</th>
                                        <th>AssignÃ©</th>
                                        <th>Dernier contact</th>
                                        <th>CrÃ©Ã© le</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <?php if(!isset($trashed) || !$trashed): ?>
                                                <td><input type="checkbox" class="row-select" value="<?php echo e($c->id); ?>" form="bulk-form"></td>
                                            <?php endif; ?>
                                            <td><code><?php echo e($c->client_code); ?></code></td>
                                            <td>
                                                <a href="<?php echo e(route('admin.customers.clients.show', $c)); ?>"><?php echo e($c->full_name); ?></a>
                                            </td>
                                            <td>
                                                <?php
                                                    $typeLabel = $c->client_type === 'individual' ? 'Particulier' : ($c->client_type === 'company' ? 'SociÃ©tÃ©' : 'Agence');
                                                    $typeColor = match($c->client_type) {
                                                        'company' => 'info',
                                                        'agency' => 'neutral',
                                                        default => 'neutral',
                                                    };
                                                ?>
                                                <?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['type' => $typeColor,'label' => $typeLabel]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($typeColor),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($typeLabel)]); ?>
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
                                            <td><?php echo e($c->email ?? 'â€”'); ?></td>
                                            <td><?php echo e($c->phone ?? 'â€”'); ?></td>
                                            <td><?php echo e($c->whatsapp_number ?? 'â€”'); ?></td>
                                            <td><?php echo e($c->nationality ?? 'â€”'); ?></td>
                                            <td><?php echo e($c->city ?? 'â€”'); ?></td>
                                            <td><?php echo e($c->traveler_category ?? 'â€”'); ?></td>
                                            <td><?php echo e($c->budget_display ?? 'â€”'); ?></td>
                                            <td>
                                                <?php
                                                    $statusColor = match($c->status) {
                                                        'active' => 'success',
                                                        'inactive' => 'warning',
                                                        'blocked' => 'danger',
                                                        'vip' => 'info',
                                                        default => 'neutral',
                                                    };
                                                ?>
                                                <?php if (isset($component)) { $__componentOriginal92e51077c3bdcbfa01c516c134fd0f33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92e51077c3bdcbfa01c516c134fd0f33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.badge','data' => ['type' => $statusColor,'label' => strtoupper($c->status)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusColor),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(strtoupper($c->status))]); ?>
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
                                            <td><?php echo e($c->assignedTo?->name ?? 'â€”'); ?></td>
                                            <td><?php echo e($c->last_contacted_at?->format('d/m/Y') ?? 'â€”'); ?></td>
                                            <td><?php echo e($c->created_at->format('d/m/Y')); ?></td>
                                            <td class="text-end">
                                                <?php if(isset($trashed) && $trashed): ?>
                                                    <form action="<?php echo e(route('admin.customers.clients.restore', $c->id)); ?>" method="POST" class="d-inline">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit" class="aj-btn aj-btn-soft" style="min-height:32px;padding:0 10px;font-size:12px;">Restaurer</button>
                                                    </form>
                                                    <form action="<?php echo e(route('admin.customers.clients.force', $c->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Supprimer dÃ©finitivement ce client ?');">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button type="submit" class="aj-btn aj-btn-soft" style="min-height:32px;padding:0 10px;font-size:12px;color:#d92d20;">Supprimer</button>
                                                    </form>
                                                <?php else: ?>
                                                    <?php if (isset($component)) { $__componentOriginala07abf6c4ac26573367cdce79eb1edd5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala07abf6c4ac26573367cdce79eb1edd5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.action-buttons','data' => ['viewUrl' => route('admin.customers.clients.show', $c),'editUrl' => route('admin.customers.clients.edit', $c),'deleteUrl' => route('admin.customers.clients.destroy', $c),'deleteConfirm' => 'Mettre ce client en corbeille ?']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.action-buttons'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['view-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.customers.clients.show', $c)),'edit-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.customers.clients.edit', $c)),'delete-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.customers.clients.destroy', $c)),'delete-confirm' => 'Mettre ce client en corbeille ?']); ?>
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
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if(!isset($trashed) || !$trashed): ?>
                            <div class="d-flex flex-wrap gap-2 mt-3 mb-2">
                                <span class="me-2" style="font-size:13px;font-weight:700;color:#5f6f85;">Actions groupÃ©es :</span>
                                <button type="button" class="aj-mini-btn" data-bulk-action="activate" style="color:#067647;">Activer</button>
                                <button type="button" class="aj-mini-btn" data-bulk-action="deactivate" style="color:#b54708;">DÃ©sactiver</button>
                                <button type="button" class="aj-mini-btn" data-bulk-action="block" style="color:#d92d20;">Bloquer</button>
                                <button type="button" class="aj-mini-btn" data-bulk-action="vip" style="color:#0550a7;">VIP</button>
                                <button type="button" class="aj-mini-btn" data-bulk-action="delete">Supprimer</button>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalef886446d0d494c63255f0af1f6da7a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef886446d0d494c63255f0af1f6da7a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.pagination-footer','data' => ['paginator' => $clients]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.pagination-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($clients)]); ?>
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
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function() {
    var selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row-select').forEach(function(cb) { cb.checked = selectAll.checked; });
        });
    }
    document.querySelectorAll('[data-bulk-action]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var ids = [];
            document.querySelectorAll('.row-select:checked').forEach(function(cb) { ids.push(cb.value); });
            if (ids.length === 0) { alert('SÃ©lectionnez au moins un client.'); return; }
            var action = this.getAttribute('data-bulk-action');
            if (action === 'delete' && !confirm('Mettre les clients sÃ©lectionnÃ©s en corbeille ?')) return;
            var form = document.getElementById('bulk-form');
            form.querySelector('#bulk-action').value = action;
            form.querySelectorAll('[name="ids[]"]').forEach(function(el) { el.remove(); });
            ids.forEach(function(id) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = id;
                form.appendChild(inp);
            });
            form.submit();
        });
    });
})();
</script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\customers\clients\index.blade.php ENDPATH**/ ?>