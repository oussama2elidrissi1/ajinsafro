@extends('layouts.admin-v6')
@section('title')
    {{ $isEdit ? 'Modifier utilisateur' : 'Créer utilisateur' }}
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">{{ $isEdit ? 'Modifier utilisateur' : 'Créer utilisateur' }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.utilisateurs') }}">Utilisateurs</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Modifier' : 'Créer' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ $isEdit ? route('admin.settings.utilisateurs.update', $userModel) : route('admin.settings.utilisateurs.store') }}">
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif

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
                                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $userModel->name) }}" required>
                                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $userModel->email) }}" required>
                                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Téléphone</label>
                                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $userModel->phone) }}">
                                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Adresse</label>
                                        <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $userModel->address) }}">
                                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Agence</label>
                                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                            <option value="">— Aucune —</option>
                                            @foreach($branches ?? [] as $b)
                                                <option value="{{ $b->id }}" {{ old('branch_id', $userModel->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                                            @endforeach
                                        </select>
                                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Responsable (manager)</label>
                                        <select name="manager_id" class="form-select @error('manager_id') is-invalid @enderror">
                                            <option value="">— Aucun —</option>
                                            @foreach($managers ?? [] as $m)
                                                @if($isEdit && $m->id == $userModel->id) @continue @endif
                                                <option value="{{ $m->id }}" {{ old('manager_id', $userModel->manager_id) == $m->id ? 'selected' : '' }}>{{ $m->name }} ({{ $m->email }})</option>
                                            @endforeach
                                        </select>
                                        @error('manager_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Poste</label>
                                        <input type="text" name="job_title" class="form-control @error('job_title') is-invalid @enderror" value="{{ old('job_title', $userModel->job_title) }}" placeholder="ex: Agent commercial">
                                        @error('job_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Type utilisateur</label>
                                        <select name="user_type" class="form-select @error('user_type') is-invalid @enderror">
                                            <option value="">—</option>
                                            <option value="agent" {{ old('user_type', $userModel->user_type) === 'agent' ? 'selected' : '' }}>Agent</option>
                                            <option value="commercial" {{ old('user_type', $userModel->user_type) === 'commercial' ? 'selected' : '' }}>Commercial</option>
                                            <option value="chef_commercial" {{ old('user_type', $userModel->user_type) === 'chef_commercial' ? 'selected' : '' }}>Chef Commercial</option>
                                            <option value="branch_admin" {{ old('user_type', $userModel->user_type) === 'branch_admin' ? 'selected' : '' }}>Admin Agence</option>
                                            <option value="comptable" {{ old('user_type', $userModel->user_type) === 'comptable' ? 'selected' : '' }}>Comptable</option>
                                            <option value="siege_admin" {{ old('user_type', $userModel->user_type) === 'siege_admin' ? 'selected' : '' }}>Admin Siège</option>
                                            <option value="super_admin" {{ old('user_type', $userModel->user_type) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                        </select>
                                        @error('user_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mot de passe {{ $isEdit ? '(laisser vide pour conserver)' : '' }}</label>
                                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ $isEdit ? '' : 'required' }}>
                                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirmer mot de passe</label>
                                        <input type="password" name="password_confirmation" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="is_admin" value="1" id="is_admin" {{ old('is_admin', $userModel->is_admin ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_admin">Acces global agences</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $userModel->is_active ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">Compte actif</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane" id="tab-role" role="tabpanel">
                                <div class="mb-3">
                                    <label class="form-label">Mode d'accès</label>
                                    <select name="access_mode" id="access_mode" class="form-select @error('access_mode') is-invalid @enderror">
                                        @php $oldMode = old('access_mode', $userModel->access_mode ?: 'role'); @endphp
                                        <option value="role" {{ $oldMode === 'role' ? 'selected' : '' }}>Hériter d'un rôle</option>
                                        <option value="custom" {{ $oldMode === 'custom' ? 'selected' : '' }}>Permissions personnalisées</option>
                                    </select>
                                    @error('access_mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div id="role-wrapper" class="mb-3">
                                    <label class="form-label">Rôle</label>
                                    <select name="role_name" class="form-select @error('role_name') is-invalid @enderror">
                                        <option value="">-- Choisir --</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}" {{ old('role_name', $selectedRole) === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('role_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="tab-pane" id="tab-access" role="tabpanel">
                                <div class="alert alert-info py-2 small mb-3" id="permissions-mode-alert">
                                    La permission <strong>Acces interface admin</strong> controle l'entree dans /admin.
                                    En mode <strong>Hériter d'un rôle</strong>, les permissions suivent uniquement le rôle sélectionné.
                                    Passez en <strong>Permissions personnalisées</strong> pour définir une sélection manuelle.
                                </div>

                                <p class="text-muted small mb-3">
                                    Chaque case liste les pages du menu qu'elle ouvre. Les cases proviennent directement du
                                    menu admin : une page ajoutée ou retirée du menu apparaît ou disparaît ici sans intervention.
                                    Quand plusieurs pages partagent une permission, elles s'accordent ensemble.
                                </p>

                                <div class="d-flex gap-2 mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="check-all">Tout cocher</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="uncheck-all">Tout décocher</button>
                                    <button type="button" class="btn btn-sm btn-outline-info" id="apply-role-defaults">Réinitialiser selon rôle</button>
                                    <span class="badge bg-soft-primary text-primary align-self-center" id="permissions-count">0 sélectionnée(s)</span>
                                </div>

                                @error('permissions')
                                    <div class="alert alert-danger py-2">{{ $message }}</div>
                                @enderror
                                @error('permissions.*')
                                    <div class="alert alert-danger py-2">{{ $message }}</div>
                                @enderror

                                @php
                                    $selectedPermissions = array_values(array_unique(old('permissions', $selectedPermissions ?? [])));
                                @endphp

                                {{--
                                    Une ligne par permission, et sous chaque ligne la liste exacte des pages du menu
                                    qu'elle ouvre : aucune page du menu n'est plus invisible ici, y compris quand
                                    plusieurs pages partagent la meme permission.
                                --}}
                                @foreach($permissionGroups as $group)
                                    @php
                                        $groupPages = collect($group['permissions'])->sum(fn ($permission) => count($permission['pages'] ?? []));
                                        $groupUnmanaged = $group['unmanaged'] ?? [];
                                    @endphp
                                    <div class="border rounded p-3 mb-3 permission-group" data-group="{{ $group['key'] }}">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                            <h6 class="mb-0">
                                                {{ $group['label'] }}
                                                @if($groupPages > 0)
                                                    <span class="badge bg-light text-muted fw-normal ms-1">{{ $groupPages }} page{{ $groupPages > 1 ? 's' : '' }}</span>
                                                @endif
                                            </h6>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-light check-section" data-group="{{ $group['key'] }}">Cocher section</button>
                                                <button type="button" class="btn btn-sm btn-light uncheck-section" data-group="{{ $group['key'] }}">Décocher section</button>
                                            </div>
                                        </div>
                                        <div class="row">
                                            @foreach($group['permissions'] as $permission)
                                                @php $pages = $permission['pages'] ?? []; @endphp
                                                <div class="col-md-6 col-xl-4 mb-3 permission-entry"
                                                     data-search="{{ mb_strtolower($group['label'].' '.$permission['label'].' '.$permission['name'].' '.collect($pages)->pluck('label')->implode(' ')) }}">
                                                    <div class="form-check">
                                                        <input
                                                            class="form-check-input permission-checkbox"
                                                            type="checkbox"
                                                            data-group="{{ $group['key'] }}"
                                                            name="permissions[]"
                                                            value="{{ $permission['name'] }}"
                                                            id="perm_{{ md5($permission['name']) }}"
                                                            {{ in_array($permission['name'], $selectedPermissions, true) ? 'checked' : '' }}
                                                        >
                                                        <label class="form-check-label d-block" for="perm_{{ md5($permission['name']) }}">
                                                            {{ $permission['label'] }}
                                                            @if(count($pages) > 1)
                                                                <span class="badge bg-soft-primary text-primary ms-1">{{ count($pages) }} pages</span>
                                                            @endif
                                                            {{-- La cle technique distingue deux libelles identiques (permissions multiples d'un meme module). --}}
                                                            <span class="d-block text-muted" style="font-size:11px;">{{ $permission['name'] }}</span>
                                                        </label>
                                                        @if($pages !== [])
                                                            <ul class="list-unstyled text-muted small mb-0 mt-1 ps-1">
                                                                @foreach($pages as $page)
                                                                    <li>&middot; {{ $page['label'] }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <div class="text-muted small mt-1">&middot; Accès au module, sans page dédiée</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($groupUnmanaged !== [])
                                            <div class="border-top pt-2 mt-1">
                                                <div class="text-muted small fw-semibold mb-1">Pages sans permission dédiée</div>
                                                @foreach($groupUnmanaged as $page)
                                                    <div class="text-muted small">&middot; {{ $page['label'] }} — {{ $page['reason'] }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Mettre à jour' : 'Créer' }}</button>
                            <a href="{{ route('admin.settings.utilisateurs') }}" class="btn btn-light">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
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
            const rolePermissionsMap = @json($rolePermissionsMap);
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
                        ? 'La permission <strong>Acces interface admin</strong> controle l entree dans /admin. En mode <strong>Héritage rôle</strong>, les permissions ci-dessous sont en lecture seule et suivent le rôle sélectionné.'
                        : 'La permission <strong>Acces interface admin</strong> controle l entree dans /admin. En mode <strong>Permissions personnalisées</strong>, les cases cochées sont exactement celles enregistrées pour cet utilisateur.';
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

            updateSelectedCount();
            updateRoleVisibility();
        })();
    </script>
@endpush
