@extends('layouts.master-ajinsafro')

@section('title', 'Tableau de bord')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $displayName = $user?->name;

    $reservationsListUrl = null;
    if (Route::has('admin.reservations.toutes') && $user->can('reservations.all.view')) {
        $reservationsListUrl = route('admin.reservations.toutes');
    } elseif (Route::has('admin.reservations.en-attente') && $user->can('reservations.pending.view')) {
        $reservationsListUrl = route('admin.reservations.en-attente');
    } elseif (Route::has('admin.reservations.confirmees') && $user->can('reservations.confirmed.view')) {
        $reservationsListUrl = route('admin.reservations.confirmees');
    }

    $canOpenReservation = Route::has('admin.reservations.show') && $user->can('reservations.view');
@endphp

<div class="mb-8 flex flex-col lg:flex-row lg:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Tableau de bord</h1>
        <p class="text-sm text-gray-500 mt-1">Bienvenue dans votre espace agent, {{ $displayName }}.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if(Route::has('admin.reservations.create') && $user->can('reservations.create'))
            <a href="{{ route('admin.reservations.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#0083c4] text-white text-sm font-bold shadow-sm hover:opacity-95 transition-opacity">
                <i class="fas fa-plus-circle"></i>
                Nouvelle réservation
            </a>
        @endif
        @if(Route::has('admin.customers.clients.create') && $user->can('customers.clients.view'))
            <a href="{{ route('admin.customers.clients.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-[#0083c4] text-[#0083c4] text-sm font-bold bg-white hover:bg-[#e6f3fa]/40 transition-colors">
                <i class="fas fa-user-plus"></i>
                Nouveau client
            </a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
            <span class="text-[#0083c4] font-black text-xl">R</span>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Réservations</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['reservations_total'] }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-yellow-50 flex items-center justify-center shrink-0">
            <span class="text-yellow-600 font-black text-xl">⋯</span>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">En cours</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['reservations_en_cours'] }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center shrink-0">
            <span class="text-green-600 font-black text-xl">✓</span>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Validées</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['reservations_validees'] }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
            <span class="text-[#f37a1f] font-black text-xl">C</span>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Clients</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['clients_count'] }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center shrink-0">
            <span class="text-purple-600 font-black text-xl">V</span>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Voyages</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['voyages_count'] }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-indigo-50 flex items-center justify-center shrink-0">
            <span class="text-indigo-600 font-black text-xl">D</span>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Départs à venir</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['departures_upcoming'] }}</h4>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex flex-col overflow-hidden lg:col-span-2">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a]">Dernières réservations</h3>
            @if($reservationsListUrl)
                <a href="{{ $reservationsListUrl }}" class="text-[10px] font-bold text-[#0083c4] hover:underline uppercase tracking-wider">Voir tout</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-4 px-6">#</th>
                    <th class="py-4 px-6">Client</th>
                    <th class="py-4 px-6">Voyage</th>
                    <th class="py-4 px-6">Statut</th>
                    <th class="py-4 px-6">Date</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($recentReservations as $reservation)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6 font-semibold text-gray-800">{{ $reservation->id }}</td>
                        <td class="py-4 px-6 text-gray-600">{{ trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '—' }}</td>
                        <td class="py-4 px-6 font-semibold text-gray-800">{{ $reservation->tour?->name ?? '—' }}</td>
                        <td class="py-4 px-6">
                            @php
                                $status = $reservation->status;
                                $badge = $status === \App\Models\Reservation::STATUS_VALIDEE ? 'bg-green-50 text-green-700 border-green-200' : ($status === \App\Models\Reservation::STATUS_ANNULEE ? 'bg-red-50 text-red-700 border-red-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200');
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $badge }}">{{ $status }}</span>
                        </td>
                        <td class="py-4 px-6 text-gray-500">{{ optional($reservation->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="py-4 px-6 text-right">
                            @if($canOpenReservation)
                                <a href="{{ route('admin.reservations.show', $reservation) }}" class="text-[#0083c4] font-bold text-xs hover:underline">Ouvrir</a>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 px-6 text-center text-gray-500">Aucune réservation trouvée.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex flex-col overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a]">Derniers clients</h3>
            @if(Route::has('admin.customers.clients.index') && $user->can('customers.clients.view'))
                <a href="{{ route('admin.customers.clients.index') }}" class="text-[10px] font-bold text-[#0083c4] hover:underline uppercase tracking-wider">Voir tout</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-4 px-5">Code</th>
                    <th class="py-4 px-5">Nom</th>
                    <th class="py-4 px-5">Contact</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($recentClients as $client)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-5 font-semibold text-gray-800">{{ $client->client_code }}</td>
                        <td class="py-4 px-5 text-gray-700">{{ $client->full_name ?: trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) }}</td>
                        <td class="py-4 px-5 text-gray-500">{{ $client->email ?: ($client->phone ?: '—') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-10 px-5 text-center text-gray-500">Aucun client trouvé.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
