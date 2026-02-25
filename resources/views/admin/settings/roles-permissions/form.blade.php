@extends('layouts.master-ajinsafro')
@section('title')
    {{ $isEdit ? 'Modifier rôle' : 'Créer rôle' }}
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">{{ $isEdit ? 'Modifier rôle' : 'Créer rôle' }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.roles-permissions') }}">Rôles & Permissions</a></li>
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
                    <form method="POST" action="{{ $isEdit ? route('admin.settings.roles-permissions.update', $roleModel) : route('admin.settings.roles-permissions.store') }}">
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Nom du rôle</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $roleModel->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="check-all">Tout cocher</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="uncheck-all">Tout décocher</button>
                        </div>

                        @php
                            $selectedPermissions = old('permissions', $selectedPermissions ?? []);
                        @endphp

                        @foreach($permissionGroups as $group)
                            <div class="border rounded p-3 mb-3">
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
                                                    id="perm_{{ md5($group['key'] . '_' . $permission['name']) }}"
                                                    {{ in_array($permission['name'], $selectedPermissions, true) ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label" for="perm_{{ md5($group['key'] . '_' . $permission['name']) }}">{{ $permission['label'] }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Mettre à jour' : 'Créer' }}</button>
                            <a href="{{ route('admin.settings.roles-permissions') }}" class="btn btn-light">Annuler</a>
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
            const checkboxes = Array.from(document.querySelectorAll('.permission-checkbox'));

            function setAll(state) {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = state;
                });
            }

            document.getElementById('check-all').addEventListener('click', () => setAll(true));
            document.getElementById('uncheck-all').addEventListener('click', () => setAll(false));

            document.querySelectorAll('.check-section').forEach((button) => {
                button.addEventListener('click', () => {
                    const group = button.dataset.group;
                    document.querySelectorAll('.permission-checkbox[data-group="' + group + '"]').forEach((checkbox) => {
                        checkbox.checked = true;
                    });
                });
            });
        })();
    </script>
@endpush
