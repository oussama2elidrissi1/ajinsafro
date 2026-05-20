

<?php $__env->startSection('title', $isEdit ? "Modifier employe du point de vente" : "Creer employe du point de vente"); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalcb19cb35a534439097b02b8af91726ee = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcb19cb35a534439097b02b8af91726ee = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.page-header','data' => ['title' => $isEdit ? 'Modifier employe' : 'Creer un employe','subtitle' => 'Rattachement point de vente, poste, statut et eventuel acces login.','breadcrumbs' => [
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Employes des points de vente', 'url' => route('admin.agency-employees.index')],
            ['label' => $isEdit ? 'Modifier' : 'Créer'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('admin.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isEdit ? 'Modifier employe' : 'Creer un employe'),'subtitle' => 'Rattachement point de vente, poste, statut et eventuel acces login.','breadcrumbs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Employes des points de vente', 'url' => route('admin.agency-employees.index')],
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
            <form method="POST" enctype="multipart/form-data" action="<?php echo e($isEdit ? route('admin.agency-employees.update', $employee) : route('admin.agency-employees.store')); ?>">
                <?php echo csrf_field(); ?>
                <?php if($isEdit): ?>
                    <?php echo method_field('PUT'); ?>
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo e(old('first_name', $employee->first_name)); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nom</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo e(old('last_name', $employee->last_name)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Point de vente</label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Sélectionner</option>
                            <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($branch->id); ?>" <?php if((int) old('branch_id', $employee->branch_id) === (int) $branch->id): echo 'selected'; endif; ?>><?php echo e($branch->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo e(old('email', $employee->email)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo e(old('phone', $employee->phone)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Avatar</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Poste</label>
                        <select name="position" class="form-select">
                            <option value="">Sélectionner</option>
                            <?php $__currentLoopData = $positionOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($option); ?>" <?php if(old('position', $employee->position) === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php if(old('status', $employee->status) === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Role systeme</label>
                        <select name="role_name" class="form-select">
                            <option value="">Aucun</option>
                            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($role->name); ?>" <?php if(old('role_name', $employee->user?->roles->first()?->name) === $role->name): echo 'selected'; endif; ?>><?php echo e($role->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="can_login" value="1" id="can_login" <?php if(old('can_login', $employee->can_login)): echo 'checked'; endif; ?>>
                            <label class="form-check-label" for="can_login">Peut se connecter a l'admin</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Departement</label>
                        <input type="text" name="department" class="form-control" value="<?php echo e(old('department', $employee->department)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type employe</label>
                        <input type="text" name="employee_type" class="form-control" value="<?php echo e(old('employee_type', $employee->employee_type)); ?>" placeholder="point_de_vente, central, it...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type contrat</label>
                        <input type="text" name="contract_type" class="form-control" value="<?php echo e(old('contract_type', $employee->contract_type)); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date entree</label>
                        <input type="date" name="hire_date" class="form-control" value="<?php echo e(old('hire_date', optional($employee->hire_date)->format('Y-m-d'))); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date sortie</label>
                        <input type="date" name="exit_date" class="form-control" value="<?php echo e(old('exit_date', optional($employee->exit_date)->format('Y-m-d'))); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Salaire fixe</label>
                        <input type="number" step="0.01" min="0" name="fixed_salary" class="form-control" value="<?php echo e(old('fixed_salary', $employee->fixed_salary)); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Devise salaire</label>
                        <input type="text" name="salary_currency" class="form-control" value="<?php echo e(old('salary_currency', $employee->salary_currency ?: 'MAD')); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut RH</label>
                        <input type="text" name="hr_status" class="form-control" value="<?php echo e(old('hr_status', $employee->hr_status)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Identifiant national</label>
                        <input type="text" name="national_id" class="form-control" value="<?php echo e(old('national_id', $employee->national_id)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact urgence</label>
                        <input type="text" name="emergency_contact" class="form-control" value="<?php echo e(old('emergency_contact', $employee->emergency_contact)); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo e(old('address', $employee->address)); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mot de passe temporaire</label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirmation mot de passe</label>
                        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Note interne</label>
                        <textarea name="notes" class="form-control" rows="4"><?php echo e(old('notes', $employee->notes)); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes RH internes</label>
                        <textarea name="internal_hr_notes" class="form-control" rows="3"><?php echo e(old('internal_hr_notes', $employee->internal_hr_notes)); ?></textarea>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="aj-btn aj-btn-primary"><?php echo e($isEdit ? 'Mettre à jour' : 'Créer'); ?></button>
                    <a href="<?php echo e(route('admin.agency-employees.index')); ?>" class="aj-btn aj-btn-soft">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\agency-employees\form.blade.php ENDPATH**/ ?>