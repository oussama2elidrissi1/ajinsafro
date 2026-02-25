@extends('layouts.master-ajinsafro')
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
                                            <input class="form-check-input" type="checkbox" name="is_admin" value="1" id="is_admin" {{ old('is_admin', $userModel->is_admin ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_admin">Admin</label>
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
                                <div class="d-flex gap-2 mb-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="check-all">Tout cocher</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="uncheck-all">Tout décocher</button>
                                    <button type="button" class="btn btn-sm btn-outline-info" id="apply-role-defaults">Réinitialiser selon rôle</button>
                                </div>

                                @php
                                    $selectedPermissions = old('permissions', $selectedPermissions ?? []);
                                @endphp

                                @foreach($permissionGroups as $group)
                                    <div class="border rounded p-3 mb-3 permission-group" data-group="{{ $group['key'] }}">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">{{ $group['label'] }}</h6>
                                            <button type="button" class="btn btn-sm btn-light check-section" data-group="{{ $group['key'] }}">Cocher section</button>
                                        </div>
                                        <div class="row">
                                            @foreach($group['permissions'] as $permission)
                                                <div class="col-md-4 mb-2">
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
                                                        <label class="form-check-label" for="perm_{{ md5($permission['name']) }}">{{ $permission['label'] }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
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

@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        (function() {
            const accessModeEl = document.getElementById('access_mode');
            const roleWrapper = document.getElementById('role-wrapper');
            const checkAllBtn = document.getElementById('check-all');
            const uncheckAllBtn = document.getElementById('uncheck-all');
            const applyRoleDefaultsBtn = document.getElementById('apply-role-defaults');
            const checkboxes = Array.from(document.querySelectorAll('.permission-checkbox'));
            const rolePermissionsMap = @json($rolePermissionsMap);

            function updateRoleVisibility() {
                const isRoleMode = accessModeEl.value === 'role';
                roleWrapper.style.display = isRoleMode ? '' : 'none';
            }

            function setAll(state) {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = state;
                });
            }

            function applyRoleDefaults() {
                const roleSelect = roleWrapper.querySelector('select[name="role_name"]');
                const roleName = roleSelect ? roleSelect.value : '';
                const allowed = new Set(rolePermissionsMap[roleName] || []);

                checkboxes.forEach((checkbox) => {
                    checkbox.checked = allowed.has(checkbox.value);
                });
            }

            accessModeEl.addEventListener('change', updateRoleVisibility);
            checkAllBtn.addEventListener('click', () => setAll(true));
            uncheckAllBtn.addEventListener('click', () => setAll(false));
            applyRoleDefaultsBtn.addEventListener('click', applyRoleDefaults);

            document.querySelectorAll('.check-section').forEach((button) => {
                button.addEventListener('click', () => {
                    const group = button.dataset.group;
                    document.querySelectorAll('.permission-checkbox[data-group="' + group + '"]').forEach((checkbox) => {
                        checkbox.checked = true;
                    });
                });
            });

            updateRoleVisibility();
        })();
    </script>
@endpush
