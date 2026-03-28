@extends('partner_v2.layouts.app')
@section('title', 'Réservations')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Réservations</h1>
        <p class="text-sm text-gray-500 mt-1">Suivez et gérez vos réservations (uniquement vos données).</p>
    </div>
    <a href="{{ route('partner.reservations.create') }}" class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm">
        Nouvelle réservation
    </a>
</div>

<div class="bg-white p-4 rounded-2xl shadow-custom border border-gray-100 mb-4 flex flex-wrap items-center gap-3">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <select name="status" class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#0083c4] text-[#0e3a5a] font-medium cursor-pointer">
            <option value="">Tous les statuts</option>
            <option value="EN_COURS" {{ request('status') === 'EN_COURS' ? 'selected' : '' }}>En cours</option>
            <option value="VALIDEE" {{ request('status') === 'VALIDEE' ? 'selected' : '' }}>Validée</option>
            <option value="ANNULEE" {{ request('status') === 'ANNULEE' ? 'selected' : '' }}>Annulée</option>
        </select>
        <button class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-5 py-2 rounded-xl text-sm font-bold transition-colors shadow-sm">
            Filtrer
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-4 px-6">Voyage</th>
                    <th class="py-4 px-6">Client</th>
                    <th class="py-4 px-6">Statut</th>
                    <th class="py-4 px-6">Créée le</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($reservations as $reservation)
                    @php
                        $status = $reservation->status;
                        $badge = $status === \App\Models\Reservation::STATUS_VALIDEE ? 'bg-green-50 text-green-700 border-green-200' : ($status === \App\Models\Reservation::STATUS_ANNULEE ? 'bg-red-50 text-red-700 border-red-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200');
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6 font-semibold text-gray-800">{{ $reservation->tour?->name ?? '—' }}</td>
                        <td class="py-4 px-6 text-gray-600">{{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}</td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $badge }}">{{ $status }}</span>
                        </td>
                        <td class="py-4 px-6 text-gray-500">{{ $reservation->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="py-4 px-6 text-right">
                            <a href="{{ route('partner.reservations.show', $reservation) }}" class="text-[#0083c4] font-bold text-xs hover:underline">Ouvrir</a>
                            <span class="text-gray-300 mx-2">|</span>
                            <a href="{{ route('partner.reservations.edit', $reservation) }}" class="text-gray-600 font-bold text-xs hover:underline">Modifier</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 px-6 text-center text-gray-500">Aucune réservation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">
        {{ $reservations->links() }}
    </div>
</div>
@endsection

