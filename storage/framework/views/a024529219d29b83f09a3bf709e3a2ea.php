<?php $__env->startSection('title', $isEdit ? 'Modifier point de vente' : 'Creer point de vente'); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => $isEdit ? 'Modifier point de vente' : 'Creer un point de vente','subtitle' => 'Structure, coordonnees, commission, responsable et parametres metier du point de vente.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente', 'url' => route('admin.agencies.index')],
            ['label' => $isEdit ? 'Modifier' : 'Créer'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isEdit ? 'Modifier point de vente' : 'Creer un point de vente'),'subtitle' => 'Structure, coordonnees, commission, responsable et parametres metier du point de vente.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente', 'url' => route('admin.agencies.index')],
            ['label' => $isEdit ? 'Modifier' : 'Créer'],
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

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" action="<?php echo e($isEdit ? route('admin.agencies.update', $agency) : route('admin.agencies.store')); ?>">
                <?php echo csrf_field(); ?>
                <?php if($isEdit): ?>
                    <?php echo method_field('PUT'); ?>
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom du point de vente</label>
                        <input type="text" name="name" class="form-control" value="<?php echo e(old('name', $agency->name)); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" value="<?php echo e(old('code', $agency->code)); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Structure</label>
                        <select name="type" class="form-select">
                            <option value="<?php echo e(\App\Models\Branch::TYPE_BRANCH); ?>" <?php if(old('type', $agency->type) === \App\Models\Branch::TYPE_BRANCH): echo 'selected'; endif; ?>>Point de vente</option>
                            <option value="<?php echo e(\App\Models\Branch::TYPE_HEAD_OFFICE); ?>" <?php if(old('type', $agency->type) === \App\Models\Branch::TYPE_HEAD_OFFICE): echo 'selected'; endif; ?>>Siège</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type de point de vente</label>
                        <select name="agency_type" class="form-select">
                            <?php $__currentLoopData = $agencyTypeLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php if(old('agency_type', $agency->agency_type) === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php if(old('status', $agency->status) === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Manager</label>
                        <select name="manager_user_id" class="form-select">
                            <option value="">Aucun</option>
                            <?php $__currentLoopData = $managerOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $manager): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($manager->id); ?>" <?php if((int) old('manager_user_id', $agency->manager_user_id) === (int) $manager->id): echo 'selected'; endif; ?>>
                                    <?php echo e($manager->name); ?><?php echo e($manager->email ? ' · ' . $manager->email : ''); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ville</label>
                        <input type="text" name="city" class="form-control" value="<?php echo e(old('city', $agency->city)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pays</label>
                        <input type="text" name="country" class="form-control" value="<?php echo e(old('country', $agency->country)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Devise</label>
                        <input type="text" name="currency" class="form-control" value="<?php echo e(old('currency', $agency->currency ?: 'MAD')); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="address" class="form-control" value="<?php echo e(old('address', $agency->address)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo e(old('phone', $agency->phone)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo e(old('email', $agency->email)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Commission par defaut (%)</label>
                        <input type="number" step="0.01" min="0" name="default_commission_rate" class="form-control" value="<?php echo e(old('default_commission_rate', $agency->default_commission_rate)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type commission par defaut</label>
                        <select name="default_commission_type" class="form-select">
                            <option value="">Selectionner</option>
                            <?php $__currentLoopData = \App\Models\Branch::commissionTypeLabels(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php if(old('default_commission_type', $agency->default_commission_type) === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valeur commission par defaut</label>
                        <input type="number" step="0.01" min="0" name="default_commission_value" class="form-control" value="<?php echo e(old('default_commission_value', $agency->default_commission_value)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Objectif mensuel CA</label>
                        <input type="number" step="0.01" min="0" name="monthly_revenue_target" class="form-control" value="<?php echo e(old('monthly_revenue_target', $agency->monthly_revenue_target)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Objectif mensuel reservations</label>
                        <input type="number" min="0" name="monthly_reservations_target" class="form-control" value="<?php echo e(old('monthly_reservations_target', $agency->monthly_reservations_target)); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Documents administratifs</label>
                        <input type="file" name="documents[]" class="form-control" multiple>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Horaires</label>
                        <textarea name="business_hours" class="form-control" rows="4"><?php echo e(old('business_hours', $agency->business_hours)); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Notes internes</label>
                        <textarea name="internal_notes" class="form-control" rows="4"><?php echo e(old('internal_notes', $agency->internal_notes)); ?></textarea>
                    </div>
                </div>

                <?php if($isEdit && !empty($agency->documents)): ?>
                    <div class="mt-4">
                        <label class="form-label d-block">Documents existants</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php $__currentLoopData = $agency->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e(asset('storage/' . $document['path'])); ?>" target="_blank" class="aj-btn aj-btn-soft">
                                    <i class="bx bx-file"></i>
                                    <span><?php echo e($document['name']); ?></span>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="aj-btn aj-btn-primary"><?php echo e($isEdit ? 'Mettre a jour' : 'Creer'); ?></button>
                    <a href="<?php echo e(route('admin.agencies.index')); ?>" class="aj-btn aj-btn-soft">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\agencies\form.blade.php ENDPATH**/ ?>