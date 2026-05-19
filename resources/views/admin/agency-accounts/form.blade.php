@extends('layouts.admin-v6')

@section('title', $isEdit ? 'Editer le compte point de vente' : 'Creer un compte point de vente')

@section('content')
@php
    $selectedRole = old('role_name', $account->roles->first()?->name ?? '');
@endphp

<div class="aj-page-head" style="margin-bottom:18px;">
    <div>
        <h1>{{ $isEdit ? 'Editer le compte point de vente' : 'Creer un compte point de vente' }}</h1>
        <p>Creer, lier ou modifier un compte utilisateur rattache a un point de vente.</p>
    </div>
    @if(Route::has('admin.agency-accounts.index'))
        <a href="{{ route('admin.agency-accounts.index') }}" class="aj-btn"><i class="bx bx-arrow-back"></i> Retour</a>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="aj-card">
            <div class="aj-card-body">
                <form method="POST" action="{{ $isEdit ? route('admin.agency-accounts.update', $account) : route('admin.agency-accounts.store') }}">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nom</label>
                            <input type="text" name="name" class="aj-form-control" value="{{ old('name', $account->name) }}" placeholder="Nom complet">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="aj-form-control" value="{{ old('email', $account->email) }}" placeholder="email@domaine.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">TÃ©lÃ©phone</label>
                            <input type="text" name="phone" class="aj-form-control" value="{{ old('phone', $account->phone) }}" placeholder="0600000000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Point de vente</label>
                            <select name="branch_id" class="aj-select">
                                <option value="">Selectionner un point de vente</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((int) old('branch_id', $account->branch_id) === $branch->id)>{{ $branch->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Employe lie</label>
                            <select name="employee_id" class="aj-select">
                                <option value="">Aucun employe</option>
                                @foreach($employees as $employeeOption)
                                    <option value="{{ $employeeOption->id }}" @selected((int) old('employee_id', $employee?->id) === $employeeOption->id)>{{ $employeeOption->full_name }} @if($employeeOption->branch) â€” {{ $employeeOption->branch->display_name }} @endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Compte existant</label>
                            <select name="existing_user_id" class="aj-select">
                                <option value="">CrÃ©er un nouveau compte</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected((int) old('existing_user_id') === $user->id || $account->id === $user->id)>{{ $user->name }} â€” {{ $user->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">RÃ´le</label>
                            <select name="role_name" class="aj-select">
                                <option value="">SÃ©lectionner un rÃ´le</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fonction</label>
                            <input type="text" name="job_title" class="aj-form-control" value="{{ old('job_title', $account->job_title) }}" placeholder="Manager, Agent rÃ©servation...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mot de passe {{ $isEdit ? '(laisser vide pour conserver)' : '' }}</label>
                            <input type="password" name="password" class="aj-form-control" placeholder="Mot de passe temporaire">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Confirmation mot de passe</label>
                            <input type="password" name="password_confirmation" class="aj-form-control" placeholder="RÃ©pÃ©ter le mot de passe">
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $account->is_active ?? true))>
                                <label class="form-check-label fw-bold">Compte actif</label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="can_login" value="1" @checked(old('can_login', $account->agencyEmployee?->can_login ?? true))>
                                <label class="form-check-label fw-bold">Autoriser la connexion</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="send_invitation" value="1" checked>
                                <label class="form-check-label fw-bold">Envoyer une invitation</label>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;flex-wrap:wrap;">
                        <button type="submit" class="aj-btn primary"><i class="bx bx-save"></i> {{ $isEdit ? 'Mettre Ã  jour' : 'CrÃ©er le compte' }}</button>
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

        @if($isEdit)
            <div class="aj-card mb-3">
                <div class="aj-card-body">
                    <strong style="display:block;margin-bottom:10px;">Actions rapides</strong>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        @if(Route::has('admin.agency-accounts.disable'))
                            <form method="POST" action="{{ route('admin.agency-accounts.disable', $account) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="aj-btn">DÃ©sactiver</button>
                            </form>
                        @endif
                        @if(Route::has('admin.agency-accounts.reset-password'))
                            <form method="POST" action="{{ route('admin.agency-accounts.reset-password', $account) }}">
                                @csrf
                                <button type="submit" class="aj-btn">Reset password</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

