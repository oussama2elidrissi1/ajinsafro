@extends('client.layout')
@section('title', 'Profil')
@section('page_title', 'Mon profil')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('client.profile.update') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <input class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name', $client->first_name) }}">
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <input class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name', $client->last_name) }}">
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $client->phone) }}">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input class="form-control" value="{{ $client->email }}" disabled>
                        <div class="form-text">Pour changer l’email, merci de contacter Ajinsafro.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ville</label>
                        <input class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $client->city) }}">
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Adresse</label>
                        <input class="form-control @error('address_line_1') is-invalid @enderror" name="address_line_1" value="{{ old('address_line_1', $client->address_line_1) }}">
                        @error('address_line_1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
@endsection

