@extends('layouts.master-ajinsafro')

@section('title', 'Mon profil')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-profile { padding: 0 18px 28px; }
        .aj-agent-profile-grid { display:grid; grid-template-columns:minmax(280px, .8fr) minmax(0, 1.4fr); gap:16px; align-items:start; }
        .aj-agent-profile-card { background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 6px 16px rgba(15,23,42,.05); padding:20px; }
        .aj-agent-profile-avatar { width:92px; height:92px; border-radius:999px; object-fit:cover; border:4px solid #eef7ff; box-shadow:0 8px 20px rgba(15,23,42,.08); }
        .aj-agent-profile-card h2 { margin:12px 0 4px; color:#0e3a5a; font-size:18px; font-weight:700; }
        .aj-agent-profile-card p { margin:0; color:#64748b; font-size:13px; }
        .aj-agent-profile-form { display:grid; gap:14px; }
        .aj-agent-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .aj-agent-profile-field label { display:block; color:#64748b; font-size:12px; font-weight:700; margin-bottom:6px; }
        .aj-agent-profile-field input, .aj-agent-profile-field textarea { width:100%; border:1px solid #dbe4ee; border-radius:12px; padding:11px 13px; color:#0f172a; background:#fff; }
        .aj-agent-profile-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .aj-agent-profile-separator { border:0; border-top:1px solid #e2e8f0; margin:4px 0; }
        .aj-agent-alert { border-radius:14px; padding:12px 14px; margin-bottom:16px; }
        .aj-agent-alert-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
        .aj-agent-alert-error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        @media(max-width:980px){.aj-agent-profile-grid,.aj-agent-form-grid{grid-template-columns:1fr}.aj-agent-profile{padding:0 12px 24px}}
    </style>
@endpush

@section('content')
<div class="aj-agent-profile">
    <div class="aj-agent-page-head">
        <div class="aj-agent-page-title">
            <h1>Mon profil</h1>
            <p>Gérez vos informations personnelles dans l’espace Agent.</p>
        </div>
        <a href="{{ route('agent.dashboard') }}" class="aj-agent-action-btn">
            <i class="bx bx-arrow-back"></i>
            <span>Tableau de bord</span>
        </a>
    </div>

    @if (session('success'))
        <div class="aj-agent-alert aj-agent-alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="aj-agent-alert aj-agent-alert-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="aj-agent-profile-grid">
        <div class="aj-agent-profile-card" style="text-align:center;">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="aj-agent-profile-avatar">
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->email }}</p>

            <form action="{{ route('agent.profile.avatar') }}" method="POST" enctype="multipart/form-data" style="margin-top:18px;">
                @csrf
                <div class="aj-agent-profile-field" style="text-align:left;">
                    <label for="avatar">Avatar</label>
                    <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/gif" required>
                </div>
                <button type="submit" class="aj-agent-primary-btn" style="margin-top:12px;">Uploader l’avatar</button>
            </form>
        </div>

        <div class="aj-agent-profile-card">
            <form action="{{ route('agent.profile.update') }}" method="POST" class="aj-agent-profile-form">
                @csrf
                @method('PUT')

                <div class="aj-agent-form-grid">
                    <div class="aj-agent-profile-field">
                        <label for="name">Nom *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="aj-agent-profile-field">
                        <label for="email">Email *</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="aj-agent-profile-field">
                        <label for="phone">Téléphone</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" placeholder="Optionnel">
                    </div>
                    <div class="aj-agent-profile-field">
                        <label for="address">Adresse</label>
                        <input type="text" name="address" id="address" value="{{ old('address', $user->address) }}" placeholder="Optionnel">
                    </div>
                </div>

                <hr class="aj-agent-profile-separator">

                <div class="aj-agent-form-grid">
                    <div class="aj-agent-profile-field">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" name="current_password" id="current_password" placeholder="Laisser vide pour conserver">
                    </div>
                    <div class="aj-agent-profile-field">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" name="new_password" id="new_password" placeholder="Laisser vide pour conserver">
                    </div>
                    <div class="aj-agent-profile-field">
                        <label for="new_password_confirmation">Confirmer le mot de passe</label>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" placeholder="Confirmer">
                    </div>
                </div>

                <div class="aj-agent-profile-actions">
                    <button type="submit" class="aj-agent-primary-btn">Mettre à jour</button>
                    <a href="{{ route('agent.dashboard') }}" class="aj-agent-action-btn">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
