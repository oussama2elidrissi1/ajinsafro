@extends('partner_v2.layouts.app')
@section('title', 'Profil agence')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Profil agence</h1>
    <p class="text-sm text-gray-500 mt-1">Logo, contacts et informations publiques de votre agence partenaire.</p>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-6">
    <form method="POST" action="{{ route('partner.profile-agency.update') }}" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @method('PUT')

        <div class="lg:col-span-1">
            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 text-center">
                <img src="{{ $partner->logo_url }}" alt="{{ $partner->display_name }}" class="w-28 h-28 object-contain bg-white rounded-xl border border-gray-100 mx-auto mb-4">
                <label class="text-xs font-bold text-gray-500 uppercase">Logo agence</label>
                <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                @error('logo')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Raison sociale</label>
                <input name="raison_sociale" value="{{ old('raison_sociale', $partner->raison_sociale) }}" class="mt-1 w-full rounded-xl border-gray-200" required>
                @error('raison_sociale')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Nom commercial</label>
                <input name="nom_commercial" value="{{ old('nom_commercial', $partner->nom_commercial) }}" class="mt-1 w-full rounded-xl border-gray-200">
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Responsable</label>
                <input name="nom_responsable" value="{{ old('nom_responsable', $partner->responsible_name) }}" class="mt-1 w-full rounded-xl border-gray-200" required>
                @error('nom_responsable')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Email</label>
                <input type="email" name="email" value="{{ old('email', $partner->email) }}" class="mt-1 w-full rounded-xl border-gray-200" required>
                @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Telephone</label>
                <input name="telephone" value="{{ old('telephone', $partner->phone_number) }}" class="mt-1 w-full rounded-xl border-gray-200" required>
                @error('telephone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Ville</label>
                <input name="ville" value="{{ old('ville', $partner->city_name) }}" class="mt-1 w-full rounded-xl border-gray-200">
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-bold text-gray-500 uppercase">Adresse</label>
                <textarea name="adresse" rows="3" class="mt-1 w-full rounded-xl border-gray-200">{{ old('adresse', $partner->address_line) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <button class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white rounded-xl px-5 py-3 text-sm font-bold transition-colors">Enregistrer le profil agence</button>
            </div>
        </div>
    </form>
</div>
@endsection
