@extends('partner_v2.layouts.app')
@section('title', 'Modifier agent')

@section('content')
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Modifier agent</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $agent->name }}</p>
    </div>
    <a href="{{ route('partner.agents.index') }}" class="text-sm font-bold text-[#0083c4] hover:underline">Retour</a>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-5">
        <h2 class="font-bold text-[#0e3a5a] mb-4">Informations</h2>
        <form method="POST" action="{{ route('partner.agents.update', $agent) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Nom</label>
                <input name="name" value="{{ old('name', $agent->name) }}" class="mt-1 w-full rounded-xl border-gray-200" required>
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Email</label>
                <input type="email" name="email" value="{{ old('email', $agent->email) }}" class="mt-1 w-full rounded-xl border-gray-200" required>
                @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Telephone</label>
                <input name="phone" value="{{ old('phone', $agent->phone) }}" class="mt-1 w-full rounded-xl border-gray-200">
            </div>
            <button class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white rounded-xl px-5 py-3 text-sm font-bold transition-colors">Enregistrer</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-5">
        <h2 class="font-bold text-[#0e3a5a] mb-4">Reinitialiser le mot de passe</h2>
        <form method="POST" action="{{ route('partner.agents.reset-password', $agent) }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Nouveau mot de passe</label>
                <input type="password" name="password" class="mt-1 w-full rounded-xl border-gray-200" required>
                @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Confirmation</label>
                <input type="password" name="password_confirmation" class="mt-1 w-full rounded-xl border-gray-200" required>
            </div>
            <button class="bg-[#f37a1f] hover:bg-[#0e3a5a] text-white rounded-xl px-5 py-3 text-sm font-bold transition-colors">Reinitialiser</button>
        </form>
    </div>
</div>
@endsection
