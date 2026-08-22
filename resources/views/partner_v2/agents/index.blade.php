@extends('partner_v2.layouts.app')
@section('title', 'Mes agents')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Mes agents</h1>
    <p class="text-sm text-gray-500 mt-1">Agents rattaches a {{ $partner->display_name }}.</p>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-5">
        <h2 class="font-bold text-[#0e3a5a] mb-4">Ajouter un agent</h2>
        <form method="POST" action="{{ route('partner.agents.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Nom</label>
                <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-gray-200" required>
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border-gray-200" required>
                @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Telephone</label>
                <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-xl border-gray-200">
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Mot de passe</label>
                <input type="password" name="password" class="mt-1 w-full rounded-xl border-gray-200" required>
                @error('password')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Confirmation</label>
                <input type="password" name="password_confirmation" class="mt-1 w-full rounded-xl border-gray-200" required>
            </div>
            <button class="w-full bg-[#0083c4] hover:bg-[#0e3a5a] text-white rounded-xl px-4 py-3 text-sm font-bold transition-colors">Creer l agent</button>
        </form>
    </div>

    <div class="xl:col-span-2 bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-gray-50/50">
            <h2 class="font-bold text-[#0e3a5a]">Agents partenaire</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Nom</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Statut</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($agents as $agent)
                        <tr>
                            <td class="px-5 py-4 font-semibold text-[#0e3a5a]">{{ $agent->name }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $agent->email }}</td>
                            <td class="px-5 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $agent->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $agent->is_active ? 'Actif' : 'Desactive' }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('partner.agents.edit', $agent) }}" class="text-[#0083c4] font-bold text-xs hover:underline">Modifier</a>
                                @if($agent->is_active)
                                    <form action="{{ route('partner.agents.disable', $agent) }}" method="POST" class="inline">
                                        @csrf
                                        <button class="ml-3 text-red-600 font-bold text-xs hover:underline">Desactiver</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">Aucun agent partenaire.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5">{{ $agents->links("pagination::tailwind") }}</div>
    </div>
</div>
@endsection
