@extends('partner_v2.layouts.app')
@section('title', 'Tableau de bord')

@section('content')
@php
    $partnerName = $partner?->display_name ?? auth()->user()->name;
@endphp

<div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Tableau de bord</h1>
        <p class="text-sm text-gray-500 mt-1">Bienvenue dans votre espace partenaire, {{ $partnerName }}.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-6">
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
            <span class="text-[#0083c4] font-black text-xl">R</span>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Réservations (mois)</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $reservationsThisMonth }}</h4>
            <p class="text-[11px] text-gray-400 font-semibold mt-1">Total: {{ $reservationsCount }}</p>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
            <span class="text-[#f37a1f] font-black text-xl">C</span>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Clients</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $clientsCount }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center shrink-0">
            <span class="text-green-600 font-black text-xl">DH</span>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Commissions (validées + payées)</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ number_format($commissionsTotal, 0, ',', ' ') }} DH</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center shrink-0">
            <span class="text-purple-600 font-black text-xl">⏳</span>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">En attente</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ number_format($commissionsPending, 0, ',', ' ') }} DH</h4>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex flex-col overflow-hidden col-span-1">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a]">Top voyages</h3>
            <a href="{{ route('partner.catalogue.index') }}" class="text-[10px] font-bold text-[#0083c4] hover:underline uppercase tracking-wider">Catalogue</a>
        </div>
        <div class="p-5">
            @if(empty($topVoyages) || $topVoyages->isEmpty())
                <p class="text-sm text-gray-500">Aucune donnée.</p>
            @else
                <ul class="space-y-2">
                    @foreach($topVoyages as $item)
                        <li class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                            <span class="text-sm font-semibold text-[#0e3a5a] truncate">{{ $item->tour?->name ?? ('Voyage #' . $item->tour_id) }}</span>
                            <span class="text-[11px] font-bold text-[#0083c4]">{{ $item->cnt }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex flex-col overflow-hidden col-span-2">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a]">Dernières réservations</h3>
            <a href="{{ route('partner.reservations.index') }}" class="text-[10px] font-bold text-[#0083c4] hover:underline uppercase tracking-wider">Voir tout</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-4 px-6">Voyage</th>
                    <th class="py-4 px-6">Client</th>
                    <th class="py-4 px-6">Statut</th>
                    <th class="py-4 px-6">Date</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($recentReservations as $reservation)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6 font-semibold text-gray-800">{{ $reservation->tour?->name ?? '—' }}</td>
                        <td class="py-4 px-6 text-gray-600">{{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}</td>
                        <td class="py-4 px-6">
                            @php
                                $status = $reservation->status;
                                $badge = $status === \App\Models\Reservation::STATUS_VALIDEE ? 'bg-green-50 text-green-700 border-green-200' : ($status === \App\Models\Reservation::STATUS_ANNULEE ? 'bg-red-50 text-red-700 border-red-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200');
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $badge }}">{{ $status }}</span>
                        </td>
                        <td class="py-4 px-6 text-gray-500">{{ $reservation->created_at?->format('d/m/Y') }}</td>
                        <td class="py-4 px-6 text-right">
                            <a href="{{ route('partner.reservations.show', $reservation) }}" class="text-[#0083c4] font-bold text-xs hover:underline">Ouvrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 px-6 text-center text-gray-500">Aucune réservation.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

