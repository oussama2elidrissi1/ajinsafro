
<?php $__env->startSection('title'); ?>
    <?php echo e($isEdit ? 'Modifier utilisateur' : 'Créer utilisateur'); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18"><?php echo e($isEdit ? 'Modifier utilisateur' : 'Créer utilisateur'); ?></h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.settings.utilisateurs')); ?>">Utilisateurs</a></li>
                        <li class="breadcrumb-item active"><?php echo e($isEdit ? 'Modifier' : 'Créer'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?php echo e($isEdit ? route('admin.settings.utilisateurs.update', $userModel) : route('admin.settings.utilisateurs.store')); ?>">
                        <?php echo csrf_field(); ?>
                        <?php if($isEdit): ?>
                            <?php echo method_field('PUT'); ?>
                        <?php endif; ?>

                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tab-infos" role="tab">Infos</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-role" role="tab">Rôle</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-access" role="tab">Accès</a>
                            </li>
                        </ul>

                        <div class="tab-content p-3 border border-top-0 rounded-bottom">
                            <div class="tab-pane active" id="tab-infos" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nom</label>
                                        <input type="text" name="name" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('name', $userModel->name)); ?>" required>
                                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('email', $userModel->email)); ?>" required>
                                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Téléphone</label>
                                        <input type="text" name="phone" class="form-control <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('phone', $userModel->phone)); ?>">
                                        <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Adresse</label>
                                        <input type="text" name="address" class="form-control <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('address', $userModel->address)); ?>">
                                        <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Agence</label>
                                        <select name="branch_id" class="form-select <?php $__errorArgs = ['branch_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                            <option value="">�?" Aucune �?"</option>
                                            <?php $__currentLoopData = $branches ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($b->id); ?>" <?php echo e(old('branch_id', $userModel->branch_id) == $b->id ? 'selected' : ''); ?>><?php echo e($b->name); ?> (<?php echo e($b->code); ?>)</option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <?php $__errorArgs = ['branch_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Responsable (manager)</label>
                                        <select name="manager_id" class="form-select <?php $__errorArgs = ['manager_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                            <option value="">�?" Aucun �?"</option>
                                            <?php $__currentLoopData = $managers ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php if($isEdit && $m->id == $userModel->id): ?> <?php continue; ?> <?php endif; ?>
                                                <option value="<?php echo e($m->id); ?>" <?php echo e(old('manager_id', $userModel->manager_id) == $m->id ? 'selected' : ''); ?>><?php echo e($m->name); ?> (<?php echo e($m->email); ?>)</option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <?php $__errorArgs = ['manager_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Poste</label>
                                        <input type="text" name="job_title" class="form-control <?php $__errorArgs = ['job_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('job_title', $userModel->job_title)); ?>" placeholder="ex: Agent commercial">
                                        <?php $__errorArgs = ['job_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Type utilisateur</label>
                                        <select name="user_type" class="form-select <?php $__errorArgs = ['user_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                            <option value="">�?"</option>
                                            <option value="agent" <?php echo e(old('user_type', $userModel->user_type) === 'agent' ? 'selected' : ''); ?>>Agent</option>
                                            <option value="commercial" <?php echo e(old('user_type', $userModel->user_type) === 'commercial' ? 'selected' : ''); ?>>Commercial</option>
                                            <option value="chef_commercial" <?php echo e(old('user_type', $userModel->user_type) === 'chef_commercial' ? 'selected' : ''); ?>>Chef Commercial</option>
                                            <option value="branch_admin" <?php echo e(old('user_type', $userModel->user_type) === 'branch_admin' ? 'selected' : ''); ?>>Admin Agence</option>
                                            <option value="comptable" <?php echo e(old('user_type', $userModel->user_type) === 'comptable' ? 'selected' : ''); ?>>Comptable</option>
                                            <option value="siege_admin" <?php echo e(old('user_type', $userModel->user_type) === 'siege_admin' ? 'selected' : ''); ?>>Admin Siège</option>
                                            <option value="super_admin" <?php echo e(old('user_type', $userModel->user_type) === 'super_admin' ? 'selected' : ''); ?>>Super Admin</option>
                                        </select>
                                        <?php $__errorArgs = ['user_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mot de passe <?php echo e($isEdit ? '(laisser vide pour conserver)' : ''); ?></label>
                                        <input type="password" name="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" <?php echo e($isEdit ? '' : 'required'); ?>>
                                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirmer mot de passe</label>
                                        <input type="password" name="password_confirmation" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="is_admin" value="1" id="is_admin" <?php echo e(old('is_admin', $userModel->is_admin ?? true) ? 'checked' : ''); ?>>
                                            <label class="form-check-label" for="is_admin">Admin</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" <?php echo e(old('is_active', $userModel->is_active ?? true) ? 'checked' : ''); ?>>
                                            <label class="form-check-label" for="is_active">Compte actif</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane" id="tab-role" role="tabpanel">
                                <div class="mb-3">
                                    <label class="form-label">Mode d'accès</label>
                                    <select name="access_mode" id="access_mode" class="form-select <?php $__errorArgs = ['access_mode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                        <?php $oldMode = old('access_mode', $userModel->access_mode ?: 'role'); ?>
                                        <option value="role" <?php echo e($oldMode === 'role' ? 'selected' : ''); ?>>Hériter d'un rôle</option>
                                        <option value="custom" <?php echo e($oldMode === 'custom' ? 'selected' : ''); ?>>Permissions personnalisées</option>
                                    </select>
                                    <?php $__errorArgs = ['access_mode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div id="role-wrapper" class="mb-3">
                                    <label class="form-label">Rôle</label>
                                    <select name="role_name" class="form-select <?php $__errorArgs = ['role_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                        <option value="">-- Choisir --</option>
                                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($role->name); ?>" <?php echo e(old('role_name', $selectedRole) === $role->name ? 'selected' : ''); ?>><?php echo e($role->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['role_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <div class="tab-pane" id="tab-access" role="tabpanel">
                                <div class="alert alert-info py-2 small mb-3" id="permissions-mode-alert">
                                    En mode <strong>Hériter d'un rôle</strong>, les permissions suivent uniquement le rôle sélectionné.
                                    Passez en <strong>Permissions personnalisées</strong> pour définir une sélection manuelle.
                                </div>

                                <div class="d-flex gap-2 mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="check-all">Tout cocher</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="uncheck-all">Tout décocher</button>
                                    <button type="button" class="btn btn-sm btn-outline-info" id="apply-role-defaults">Réinitialiser selon rôle</button>
                                    <span class="badge bg-soft-primary text-primary align-self-center" id="permissions-count">0 sélectionnée(s)</span>
                                </div>

                                <?php $__errorArgs = ['permissions'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="alert alert-danger py-2"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                <?php $__errorArgs = ['permissions.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="alert alert-danger py-2"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                <?php
                                    $selectedPermissions = array_values(array_unique(old('permissions', $selectedPermissions ?? [])));
                                ?>

                                <?php $__currentLoopData = $permissionGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="border rounded p-3 mb-3 permission-group" data-group="<?php echo e($group['key']); ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0"><?php echo e($group['label']); ?></h6>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-light check-section" data-group="<?php echo e($group['key']); ?>">Cocher section</button>
                                                <button type="button" class="btn btn-sm btn-light uncheck-section" data-group="<?php echo e($group['key']); ?>">Décocher section</button>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <?php $__currentLoopData = $group['permissions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input permission-checkbox"
                                                            type="checkbox"
                                                            data-group="<?php echo e($group['key']); ?>"
                                                            name="permissions[]"
                                                            value="<?php echo e($permission['name']); ?>"
                                                            id="perm_<?php echo e(md5($permission['name'])); ?>"
                                                            <?php echo e(in_array($permission['name'], $selectedPermissions, true) ? 'checked' : ''); ?>

                                                        >
                                                        <label class="form-check-label" for="perm_<?php echo e(md5($permission['name'])); ?>"><?php echo e($permission['label']); ?></label>
                                                    </div>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><?php echo e($isEdit ? 'Mettre à jour' : 'Créer'); ?></button>
                            <a href="<?php echo e(route('admin.settings.utilisateurs')); ?>" class="btn btn-light">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
    <script>
        (function() {
            const formEl = document.querySelector('form');
            const accessModeEl = document.getElementById('access_mode');
            const roleWrapper = document.getElementById('role-wrapper');
            const modeAlert = document.getElementById('permissions-mode-alert');
            const checkAllBtn = document.getElementById('check-all');
            const uncheckAllBtn = document.getElementById('uncheck-all');
            const applyRoleDefaultsBtn = document.getElementById('apply-role-defaults');
            const selectedCountEl = document.getElementById('permissions-count');
            const checkboxes = Array.from(document.querySelectorAll('.permission-checkbox'));
            const rolePermissionsMap = <?php echo json_encode($rolePermissionsMap, 15, 512) ?>;
            const roleSelect = roleWrapper ? roleWrapper.querySelector('select[name="role_name"]') : null;

            const byPermissionValue = new Map();
            checkboxes.forEach((checkbox) => {
                const key = checkbox.value;
                if (!byPermissionValue.has(key)) {
                    byPermissionValue.set(key, []);
                }
                byPermissionValue.get(key).push(checkbox);
            });

            function currentModeIsCustom() {
                return accessModeEl.value === 'custom';
            }

            function updateRoleVisibility() {
                const isRoleMode = !currentModeIsCustom();
                roleWrapper.style.display = isRoleMode ? '' : 'none';
                if (modeAlert) {
                    modeAlert.classList.toggle('alert-info', isRoleMode);
                    modeAlert.classList.toggle('alert-success', !isRoleMode);
                    modeAlert.innerHTML = isRoleMode
                        ? 'En mode <strong>Héritage rôle</strong>, les permissions ci-dessous sont en lecture seule et suivent le rôle sélectionné.'
                        : 'En mode <strong>Permissions personnalisées</strong>, les cases cochées sont exactement celles enregistrées pour cet utilisateur.';
                }

                const disabled = isRoleMode;
                checkboxes.forEach((checkbox) => {
                    checkbox.disabled = disabled;
                });

                [checkAllBtn, uncheckAllBtn, applyRoleDefaultsBtn].forEach((btn) => {
                    if (!btn) return;
                    btn.disabled = disabled;
                });

                document.querySelectorAll('.check-section, .uncheck-section').forEach((btn) => {
                    btn.disabled = disabled;
                });

                updateSelectedCount();
            }

            function updateSelectedCount() {
                if (!selectedCountEl) return;
                const count = checkboxes.filter((checkbox) => checkbox.checked).length;
                selectedCountEl.textContent = count + ' sélectionnée(s)';
            }

            function setPermissionState(permissionName, state) {
                (byPermissionValue.get(permissionName) || []).forEach((checkbox) => {
                    checkbox.checked = state;
                });
            }

            function setAll(state) {
                byPermissionValue.forEach((_, permissionName) => {
                    setPermissionState(permissionName, state);
                });
                updateSelectedCount();
            }

            function applyRoleDefaults() {
                const roleName = roleSelect ? roleSelect.value : '';
                const allowed = new Set(rolePermissionsMap[roleName] || []);

                byPermissionValue.forEach((_, permissionName) => {
                    setPermissionState(permissionName, allowed.has(permissionName));
                });
                updateSelectedCount();
            }

            accessModeEl.addEventListener('change', updateRoleVisibility);
            checkAllBtn.addEventListener('click', () => setAll(true));
            uncheckAllBtn.addEventListener('click', () => setAll(false));
            applyRoleDefaultsBtn.addEventListener('click', applyRoleDefaults);

            if (roleSelect) {
                roleSelect.addEventListener('change', () => {
                    if (!currentModeIsCustom()) {
                        applyRoleDefaults();
                    }
                });
            }

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    setPermissionState(checkbox.value, checkbox.checked);
                    updateSelectedCount();
                });
            });

            document.querySelectorAll('.check-section').forEach((button) => {
                button.addEventListener('click', () => {
                    const group = button.dataset.group;
                    document.querySelectorAll('.permission-checkbox[data-group="' + group + '"]').forEach((checkbox) => {
                        setPermissionState(checkbox.value, true);
                    });
                    updateSelectedCount();
                });
            });

            document.querySelectorAll('.uncheck-section').forEach((button) => {
                button.addEventListener('click', () => {
                    const group = button.dataset.group;
                    document.querySelectorAll('.permission-checkbox[data-group="' + group + '"]').forEach((checkbox) => {
                        setPermissionState(checkbox.value, false);
                    });
                    updateSelectedCount();
                });
            });

            if (formEl) {
                formEl.addEventListener('submit', () => {
                    const selected = Array.from(byPermissionValue.keys()).filter((permissionName) => {
                        const refs = byPermissionValue.get(permissionName) || [];
                        return refs.some((checkbox) => checkbox.checked);
                    });

                    console.debug('UserAccess permissions submit payload', {
                        access_mode: accessModeEl.value,
                        role_name: roleSelect ? roleSelect.value : null,
                        permissions_count: selected.length,
                        permissions: selected,
                    });
                });
            }

            updateSelectedCount();
            updateRoleVisibility();
        })();
    </script>
<?php $__env->stopPush(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\settings\utilisateurs\form.blade.php ENDPATH**/ ?>