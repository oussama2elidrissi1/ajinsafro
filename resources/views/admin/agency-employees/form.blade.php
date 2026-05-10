@extends('layouts.admin-v2')

@section('title', $isEdit ? "Modifier employé d'agence" : "Créer employé d'agence")

@section('content')
    <x-admin.page-header
        :title="$isEdit ? 'Modifier employé' : 'Créer un employé'"
        subtitle="Rattachement agence, poste, statut et éventuel accès login."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Employés des agences', 'url' => route('admin.agency-employees.index')],
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
                        <label class="form-label">Agence</label>
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
                        <label class="form-label">Rôle système</label>
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
                            <label class="form-check-label" for="can_login">Peut se connecter à l’admin</label>
                        </div>
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
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="aj-btn aj-btn-primary">{{ $isEdit ? 'Mettre à jour' : 'Créer' }}</button>
                    <a href="{{ route('admin.agency-employees.index') }}" class="aj-btn aj-btn-soft">Annuler</a>
                </div>
            </form>
        </div>
    </div>
@endsection
