

<?php $__env->startSection('title', $isEdit ? 'Editer le compte point de vente' : 'Creer un compte point de vente'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $selectedRole = old('role_name', $account->roles->first()?->name ?? '');
?>

<div class="aj-page-head" style="margin-bottom:18px;">
    <div>
        <h1><?php echo e($isEdit ? 'Editer le compte point de vente' : 'Creer un compte point de vente'); ?></h1>
        <p>Creer, lier ou modifier un compte utilisateur rattache a un point de vente.</p>
    </div>
    <?php if(Route::has('admin.agency-accounts.index')): ?>
        <a href="<?php echo e(route('admin.agency-accounts.index')); ?>" class="aj-btn"><i class="bx bx-arrow-back"></i> Retour</a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="aj-card">
            <div class="aj-card-body">
                <form method="POST" action="<?php echo e($isEdit ? route('admin.agency-accounts.update', $account) : route('admin.agency-accounts.store')); ?>">
                    <?php echo csrf_field(); ?>
                    <?php if($isEdit): ?>
                        <?php echo method_field('PUT'); ?>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nom</label>
                            <input type="text" name="name" class="aj-form-control" value="<?php echo e(old('name', $account->name)); ?>" placeholder="Nom complet">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="aj-form-control" value="<?php echo e(old('email', $account->email)); ?>" placeholder="email@domaine.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Téléphone</label>
                            <input type="text" name="phone" class="aj-form-control" value="<?php echo e(old('phone', $account->phone)); ?>" placeholder="0600000000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Point de vente</label>
                            <select name="branch_id" class="aj-select">
                                <option value="">Selectionner un point de vente</option>
                                <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($branch->id); ?>" <?php if((int) old('branch_id', $account->branch_id) === $branch->id): echo 'selected'; endif; ?>><?php echo e($branch->display_name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Employe lie</label>
                            <select name="employee_id" class="aj-select">
                                <option value="">Aucun employe</option>
                                <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employeeOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($employeeOption->id); ?>" <?php if((int) old('employee_id', $employee?->id) === $employeeOption->id): echo 'selected'; endif; ?>><?php echo e($employeeOption->full_name); ?> <?php if($employeeOption->branch): ?> �?" <?php echo e($employeeOption->branch->display_name); ?> <?php endif; ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Compte existant</label>
                            <select name="existing_user_id" class="aj-select">
                                <option value="">Créer un nouveau compte</option>
                                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($user->id); ?>" <?php if((int) old('existing_user_id') === $user->id || $account->id === $user->id): echo 'selected'; endif; ?>><?php echo e($user->name); ?> �?" <?php echo e($user->email); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rôle</label>
                            <select name="role_name" class="aj-select">
                                <option value="">Sélectionner un rôle</option>
                                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($role->name); ?>" <?php if($selectedRole === $role->name): echo 'selected'; endif; ?>><?php echo e($role->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fonction</label>
                            <input type="text" name="job_title" class="aj-form-control" value="<?php echo e(old('job_title', $account->job_title)); ?>" placeholder="Manager, Agent réservation...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mot de passe <?php echo e($isEdit ? '(laisser vide pour conserver)' : ''); ?></label>
                            <input type="password" name="password" class="aj-form-control" placeholder="Mot de passe temporaire">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Confirmation mot de passe</label>
                            <input type="password" name="password_confirmation" class="aj-form-control" placeholder="Répéter le mot de passe">
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" <?php if(old('is_active', $account->is_active ?? true)): echo 'checked'; endif; ?>>
                                <label class="form-check-label fw-bold">Compte actif</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="can_login" value="1" <?php if(old('can_login', $account->agencyEmployee?->can_login ?? true)): echo 'checked'; endif; ?>>
                                <label class="form-check-label fw-bold">Autoriser la connexion</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="send_invitation" value="1" checked>
                                <label class="form-check-label fw-bold">Envoyer une invitation</label>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;flex-wrap:wrap;">
                        <button type="submit" class="aj-btn primary"><i class="bx bx-save"></i> <?php echo e($isEdit ? 'Mettre à jour' : 'Créer le compte'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="aj-card mb-3">
            <div class="aj-card-body">
                <strong style="display:block;margin-bottom:10px;">Notes</strong>
                <div class="aj-subtle">Le compte peut etre lie a un employe existant ou cree a partir d'un employe de point de vente. Le role Spatie est synchronise automatiquement.</div>
            </div>
        </div>

        <?php if($isEdit): ?>
            <div class="aj-card mb-3">
                <div class="aj-card-body">
                    <strong style="display:block;margin-bottom:10px;">Actions rapides</strong>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <?php if(Route::has('admin.agency-accounts.disable')): ?>
                            <form method="POST" action="<?php echo e(route('admin.agency-accounts.disable', $account)); ?>">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <button type="submit" class="aj-btn">Désactiver</button>
                            </form>
                        <?php endif; ?>
                        <?php if(Route::has('admin.agency-accounts.reset-password')): ?>
                            <form method="POST" action="<?php echo e(route('admin.agency-accounts.reset-password', $account)); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="aj-btn">Reset password</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\agency-accounts\form.blade.php ENDPATH**/ ?>