@extends('layouts.admin-v6')
@section('title', 'Nouveau partenaire')

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex align-items-center justify-content-between">
        <h4 class="page-title mb-0 font-size-18">Nouveau partenaire</h4>
        <a href="{{ route('admin.partners.partenaires') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.partners.partenaires.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><h5 class="mb-0">Agence partenaire</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Raison sociale / Nom agence</label>
                            <input name="raison_sociale" class="form-control" value="{{ old('raison_sociale') }}" required>
                            @error('raison_sociale')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Responsable</label>
                            <input name="nom_responsable" class="form-control" value="{{ old('nom_responsable') }}" required>
                            @error('nom_responsable')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email agence</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                            @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telephone</label>
                            <input name="telephone" class="form-control" value="{{ old('telephone') }}" required>
                            @error('telephone')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ville</label>
                            <input name="ville" class="form-control" value="{{ old('ville') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="validated" @selected(old('status', 'validated') === 'validated')>validated</option>
                                <option value="pending" @selected(old('status') === 'pending')>pending</option>
                                <option value="suspended" @selected(old('status') === 'suspended')>suspended</option>
                            </select>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Adresse</label>
                            <textarea name="adresse" class="form-control" rows="3">{{ old('adresse') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Logo optionnel</label>
                            <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            @error('logo')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><h5 class="mb-0">Compte admin partenaire</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nom admin</label>
                        <input name="admin_name" class="form-control" value="{{ old('admin_name') }}" required>
                        @error('admin_name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email admin</label>
                        <input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" required>
                        @error('admin_email')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telephone admin</label>
                        <input name="admin_phone" class="form-control" value="{{ old('admin_phone') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required>
                        @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmation mot de passe</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Creer le partenaire</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
