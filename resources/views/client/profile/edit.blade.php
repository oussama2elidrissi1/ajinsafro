@extends('client.layout')
@section('title', 'Mon profil')
@section('page_title', 'Mon profil')

@section('page_styles')
<style>
    .aj-form-field label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--aj-muted);
        margin-bottom: 0.45rem;
        display: block;
    }
    .aj-form-field .form-control {
        font-family: 'Outfit', sans-serif;
        font-size: 0.9rem;
        border: 1.5px solid var(--aj-border);
        border-radius: var(--aj-radius-sm);
        padding: 0.65rem 1rem;
        color: var(--aj-text);
        background: #FAFAF7;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .aj-form-field .form-control:focus {
        border-color: var(--aj-navy);
        box-shadow: 0 0 0 3px rgba(26,58,92,0.08);
        background: #fff;
        outline: none;
    }
    .aj-form-field .form-control:disabled {
        background: #F3F4F6;
        color: var(--aj-muted);
        cursor: not-allowed;
        border-color: #E5E0D5;
    }
    .aj-form-field .form-control.is-invalid {
        border-color: #DC2626;
    }
    .aj-form-field .invalid-feedback {
        font-size: 0.78rem;
        color: #DC2626;
        margin-top: 0.3rem;
    }
    .aj-form-field .form-text {
        font-size: 0.75rem;
        color: var(--aj-muted);
        margin-top: 0.3rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }
    .aj-profile-header {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--aj-border);
        flex-wrap: wrap;
    }
    .aj-profile-avatar-lg {
        width: 70px; height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--aj-navy) 0%, #234d7a 100%);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.6rem; font-weight: 700;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 4px 14px rgba(26,58,92,0.2);
        letter-spacing: 0.04em;
    }
    .aj-profile-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--aj-navy);
        margin: 0 0 0.2rem;
    }
    .aj-profile-email {
        font-size: 0.82rem;
        color: var(--aj-muted);
        display: flex; align-items: center; gap: 0.4rem;
    }
    .aj-section-sep {
        font-family: 'Outfit', sans-serif;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--aj-gold);
        margin: 0 0 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .aj-section-sep::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--aj-border);
    }
</style>
@endsection

@section('content')

@php
    $name = $client->full_name ?: trim(($client->first_name ?? '').' '.($client->last_name ?? '')) ?: 'Client';
    $initials = collect(explode(' ', $name))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->implode('');
@endphp

<div style="max-width:760px;">

    <div class="aj-card">
        <div class="aj-card-body">

            {{-- Profile header --}}
            <div class="aj-profile-header">
                <div class="aj-profile-avatar-lg">{{ $initials }}</div>
                <div>
                    <div class="aj-profile-title">{{ $name }}</div>
                    <div class="aj-profile-email">
                        <i class="ri-mail-line" style="color:var(--aj-gold);"></i>
                        {{ $client->email ?: '—' }}
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('client.profile.update') }}">
                @csrf

                {{-- Identité --}}
                <div class="aj-section-sep mb-3">Identité</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="aj-form-field">
                            <label for="first_name">Prénom</label>
                            <input
                                class="form-control @error('first_name') is-invalid @enderror"
                                id="first_name" name="first_name"
                                value="{{ old('first_name', $client->first_name) }}"
                                placeholder="Votre prénom"
                            >
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="aj-form-field">
                            <label for="last_name">Nom de famille</label>
                            <input
                                class="form-control @error('last_name') is-invalid @enderror"
                                id="last_name" name="last_name"
                                value="{{ old('last_name', $client->last_name) }}"
                                placeholder="Votre nom"
                            >
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Contact --}}
                <div class="aj-section-sep mb-3">Contact</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="aj-form-field">
                            <label for="phone">Téléphone</label>
                            <input
                                class="form-control @error('phone') is-invalid @enderror"
                                id="phone" name="phone"
                                value="{{ old('phone', $client->phone) }}"
                                placeholder="+212 6xx xxx xxx"
                            >
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="aj-form-field">
                            <label>Adresse email</label>
                            <input class="form-control" value="{{ $client->email }}" disabled>
                            <div class="form-text">
                                <i class="ri-lock-line"></i>
                                Pour modifier l'email, contactez Ajinsafro.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Adresse --}}
                <div class="aj-section-sep mb-3">Adresse</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-5">
                        <div class="aj-form-field">
                            <label for="city">Ville</label>
                            <input
                                class="form-control @error('city') is-invalid @enderror"
                                id="city" name="city"
                                value="{{ old('city', $client->city) }}"
                                placeholder="Casablanca, Rabat…"
                            >
                            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="aj-form-field">
                            <label for="address_line_1">Adresse</label>
                            <input
                                class="form-control @error('address_line_1') is-invalid @enderror"
                                id="address_line_1" name="address_line_1"
                                value="{{ old('address_line_1', $client->address_line_1) }}"
                                placeholder="N° et nom de rue"
                            >
                            @error('address_line_1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="d-flex align-items-center gap-3 pt-2" style="border-top:1px solid var(--aj-border);padding-top:1.25rem!important;">
                    <button class="btn-aj-gold" type="submit">
                        <i class="ri-save-line me-1"></i> Enregistrer les modifications
                    </button>
                    <a href="{{ route('client.dashboard') }}" class="btn-aj-outline">Annuler</a>
                </div>

            </form>
        </div>
    </div>

    <div class="aj-card mt-4">
        <div class="aj-card-body">
            <div class="aj-section-sep mb-3">Sécurité</div>

            <form method="POST" action="{{ route('client.profile.update') }}">
                @csrf
                <input type="hidden" name="change_password" value="1">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="aj-form-field">
                            <label for="current_password">Mot de passe actuel</label>
                            <input
                                type="password"
                                class="form-control @error('current_password') is-invalid @enderror"
                                id="current_password"
                                name="current_password"
                                autocomplete="current-password"
                                placeholder="Votre mot de passe actuel"
                            >
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                <i class="ri-shield-keyhole-line"></i>
                                Requis si vous avez déjà défini un mot de passe.
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6"></div>
                    <div class="col-md-6">
                        <div class="aj-form-field">
                            <label for="password">Nouveau mot de passe</label>
                            <input
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                autocomplete="new-password"
                                placeholder="Minimum 8 caractères"
                            >
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="aj-form-field">
                            <label for="password_confirmation">Confirmer le nouveau mot de passe</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password_confirmation"
                                name="password_confirmation"
                                autocomplete="new-password"
                                placeholder="Retapez le nouveau mot de passe"
                            >
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 pt-2" style="border-top:1px solid var(--aj-border);padding-top:1.25rem!important;">
                    <button class="btn-aj-gold" type="submit">
                        <i class="ri-lock-password-line me-1"></i> Mettre à jour le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
