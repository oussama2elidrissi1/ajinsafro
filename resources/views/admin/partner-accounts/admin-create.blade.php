@extends('layouts.admin-v6')
@section('title', 'Creer admin partenaire')

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex align-items-center justify-content-between">
        <div>
            <h4 class="page-title mb-0 font-size-18">Creer admin partenaire</h4>
            <p class="text-muted mb-0">{{ $partner->display_name }}</p>
        </div>
        <a href="{{ route('admin.partners.show', $partner) }}" class="btn btn-outline-secondary btn-sm">Retour</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.partners.admin.store', $partner) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input name="name" class="form-control" value="{{ old('name') }}" required>
                        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telephone</label>
                        <input name="phone" class="form-control" value="{{ old('phone') }}">
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
                    <button class="btn btn-primary">Creer admin partenaire</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
