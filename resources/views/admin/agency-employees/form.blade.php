@extends('layouts.admin-v2')

@section('title', $isEdit ? "Modifier employe du point de vente" : "Creer employe du point de vente")

@section('content')
    <x-admin.page-header
        :title="$isEdit ? 'Modifier employe' : 'Creer un employe'"
        subtitle="Rattachement point de vente, poste, statut et eventuel acces login."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Employes des points de vente', 'url' => route('admin.agency-employees.index')],
            ['label' => $isEdit ? 'Modifier' : 'Créer'],
        ]"
    />

    <x-admin.flash-messages />

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('admin.agency-employees.update', $employee) : route('admin.agency-employees.store') }}">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $employee->first_name) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nom</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $employee->last_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Point de vente</label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Sélectionner</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $employee->branch_id) === (int) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Avatar</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Poste</label>
                        <select name="position" class="form-select">
                            <option value="">Sélectionner</option>
                            @foreach($positionOptions as $option)
                                <option value="{{ $option }}" @selected(old('position', $employee->position) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            @foreach($statusLabels as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $employee->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Role systeme</label>
                        <select name="role_name" class="form-select">
                            <option value="">Aucun</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(old('role_name', $employee->user?->roles->first()?->name) === $role->name)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="can_login" value="1" id="can_login" @checked(old('can_login', $employee->can_login))>
                            <label class="form-check-label" for="can_login">Peut se connecter a l'admin</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Departement</label>
                        <input type="text" name="department" class="form-control" value="{{ old('department', $employee->department) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type employe</label>
                        <input type="text" name="employee_type" class="form-control" value="{{ old('employee_type', $employee->employee_type) }}" placeholder="point_de_vente, central, it...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type contrat</label>
                        <input type="text" name="contract_type" class="form-control" value="{{ old('contract_type', $employee->contract_type) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date entree</label>
                        <input type="date" name="hire_date" class="form-control" value="{{ old('hire_date', optional($employee->hire_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date sortie</label>
                        <input type="date" name="exit_date" class="form-control" value="{{ old('exit_date', optional($employee->exit_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Salaire fixe</label>
                        <input type="number" step="0.01" min="0" name="fixed_salary" class="form-control" value="{{ old('fixed_salary', $employee->fixed_salary) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Devise salaire</label>
                        <input type="text" name="salary_currency" class="form-control" value="{{ old('salary_currency', $employee->salary_currency ?: 'MAD') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Statut RH</label>
                        <input type="text" name="hr_status" class="form-control" value="{{ old('hr_status', $employee->hr_status) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Identifiant national</label>
                        <input type="text" name="national_id" class="form-control" value="{{ old('national_id', $employee->national_id) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact urgence</label>
                        <input type="text" name="emergency_contact" class="form-control" value="{{ old('emergency_contact', $employee->emergency_contact) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $employee->address) }}</textarea>
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
                        <textarea name="notes" class="form-control" rows="4">{{ old('notes', $employee->notes) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes RH internes</label>
                        <textarea name="internal_hr_notes" class="form-control" rows="3">{{ old('internal_hr_notes', $employee->internal_hr_notes) }}</textarea>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="aj-btn aj-btn-primary">{{ $isEdit ? 'Mettre à jour' : 'Créer' }}</button>
                    <a href="{{ route('admin.agency-employees.index') }}" class="aj-btn aj-btn-soft">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
