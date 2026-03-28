@extends('layouts.master-without-nav')
@section('title')
    Devenir partenaire
@endsection
@section('content')
    <div class="home-btn d-none d-sm-block">
        <a href="{{ url('/') }}" class="text-reset"><i class="fas fa-home h2"></i></a>
    </div>
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card overflow-hidden">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Devenir partenaire</h4>
                            <p class="text-muted">Remplissez le formulaire ci-dessous pour demander l’ouverture d’un compte partenaire. Votre demande sera examinée par notre équipe.</p>

                            <form method="POST" action="{{ route('partner.registration.store') }}" enctype="multipart/form-data">
                                @csrf

                                <h6 class="mb-3 mt-4">Entreprise</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Raison sociale <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('raison_sociale') is-invalid @enderror" name="raison_sociale" value="{{ old('raison_sociale') }}" required maxlength="190">
                                        @error('raison_sociale')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nom commercial</label>
                                        <input type="text" class="form-control @error('nom_commercial') is-invalid @enderror" name="nom_commercial" value="{{ old('nom_commercial') }}" maxlength="190">
                                        @error('nom_commercial')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">ICE</label>
                                        <input type="text" class="form-control @error('ice') is-invalid @enderror" name="ice" value="{{ old('ice') }}" maxlength="50">
                                        @error('ice')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">IF</label>
                                        <input type="text" class="form-control @error('if') is-invalid @enderror" name="if" value="{{ old('if') }}" maxlength="50">
                                        @error('if')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">RC</label>
                                        <input type="text" class="form-control @error('rc') is-invalid @enderror" name="rc" value="{{ old('rc') }}" maxlength="50">
                                        @error('rc')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <h6 class="mb-3 mt-4">Responsable</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nom du responsable <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('nom_responsable') is-invalid @enderror" name="nom_responsable" value="{{ old('nom_responsable') }}" required maxlength="190">
                                        @error('nom_responsable')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required maxlength="190">
                                        @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('telephone') is-invalid @enderror" name="telephone" value="{{ old('telephone') }}" required maxlength="50">
                                        @error('telephone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <h6 class="mb-3 mt-4">Adresse</h6>
                                <div class="mb-3">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" class="form-control @error('adresse') is-invalid @enderror" name="adresse" value="{{ old('adresse') }}" maxlength="500">
                                    @error('adresse')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Ville</label>
                                        <input type="text" class="form-control @error('ville') is-invalid @enderror" name="ville" value="{{ old('ville') }}" maxlength="100">
                                        @error('ville')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Code postal</label>
                                        <input type="text" class="form-control @error('code_postal') is-invalid @enderror" name="code_postal" value="{{ old('code_postal') }}" maxlength="20">
                                        @error('code_postal')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Pays</label>
                                        <input type="text" class="form-control @error('pays') is-invalid @enderror" name="pays" value="{{ old('pays') }}" maxlength="100">
                                        @error('pays')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Pièce justificative (PDF ou image, max 5 Mo)</label>
                                    <input type="file" class="form-control @error('document') is-invalid @enderror" name="document" accept=".pdf,.jpg,.jpeg,.png">
                                    @error('document')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                </div>

                                <h6 class="mb-3 mt-4">Mot de passe</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required minlength="8" autocomplete="new-password">
                                        @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
                                        <small class="text-muted">Minimum 8 caractères</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Confirmation <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" name="password_confirmation" required minlength="8" autocomplete="new-password">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <a href="{{ route('login') }}" class="text-muted">Retour à la connexion</a>
                                    <button type="submit" class="btn btn-primary">Envoyer ma demande</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
