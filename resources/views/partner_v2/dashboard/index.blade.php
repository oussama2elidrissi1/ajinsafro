@extends('partner_v2.layouts.app')
@section('title', 'Tableau de bord')

@section('content')
@php
    $partnerName = $partner?->display_name ?? auth()->user()->name;
    $money = fn ($value) => number_format((float) $value, 0, ',', ' ') . ' DH';
@endphp

<div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Tableau de bord</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $partnerName }} - Portail agence partenaire Ajinsafro.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 lg:gap-6">
    @foreach([
        ['label' => 'Reservations', 'value' => $reservationsCount, 'hint' => $reservationsThisMonth . ' ce mois', 'color' => 'text-[#0083c4]', 'bg' => 'bg-blue-50'],
        ['label' => 'Confirmees', 'value' => $confirmedReservations, 'hint' => 'dossiers valides', 'color' => 'text-green-600', 'bg' => 'bg-green-50'],
        ['label' => 'En attente', 'value' => $pendingReservations, 'hint' => 'a suivre', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-50'],
        ['label' => 'Total ventes', 'value' => $money($salesTotal), 'hint' => 'reservations agence', 'color' => 'text-[#f37a1f]', 'bg' => 'bg-orange-50'],
        ['label' => 'Solde wallet', 'value' => $money($partner->wallet_balance ?? 0), 'hint' => 'apres validations', 'color' => 'text-purple-600', 'bg' => 'bg-purple-50'],
    ] as $card)
        <div class="bg-white p-5 rounded-2xl shadow-custom border border-gray-100">
            <div class="w-12 h-12 rounded-full {{ $card['bg'] }} flex items-center justify-center mb-4">
                <span class="{{ $card['color'] }} font-black text-lg">*</span>
            </div>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">{{ $card['label'] }}</p>
            <h4 class="text-2xl font-black text-[#0e3a5a] leading-tight">{{ $card['value'] }}</h4>
            <p class="text-[11px] text-gray-400 font-semibold mt-1">{{ $card['hint'] }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex flex-col overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a]">Operations wallet</h3>
            @if(auth()->user()->isPartnerAdmin())
                <a href="{{ route('partner.wallet.index') }}" class="text-[10px] font-bold text-[#0083c4] hover:underline uppercase tracking-wider">Wallet</a>
            @endif
        </div>
        <div class="p-5">
            @forelse($recentWalletTransactions as $transaction)
                <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                    <div>
                        <p class="text-sm font-semibold text-[#0e3a5a]">{{ ucfirst($transaction->type) }}</p>
                        <p class="text-xs text-gray-500">{{ $transaction->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-[#0e3a5a]">{{ $money($transaction->amount) }}</p>
                        <span class="text-[10px] font-bold uppercase {{ $transaction->status === 'approved' ? 'text-green-600' : ($transaction->status === 'rejected' ? 'text-red-600' : 'text-yellow-600') }}">{{ $transaction->status }}</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">Aucune operation wallet.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex flex-col overflow-hidden lg:col-span-2">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a]">Dernieres reservations</h3>
            <a href="{{ route('partner.reservations.index') }}" class="text-[10px] font-bold text-[#0083c4] hover:underline uppercase tracking-wider">Voir tout</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-4 px-6">Voyage</th>
                    <th class="py-4 px-6">Client</th>
                    <th class="py-4 px-6">Statut</th>
                    <th class="py-4 px-6">Total</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($recentReservations as $reservation)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6 font-semibold text-gray-800">{{ $reservation->tour?->name ?? '-' }}</td>
                        <td class="py-4 px-6 text-gray-600">{{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '-' }}</td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border bg-gray-50 text-gray-700 border-gray-200">{{ $reservation->statusLabelFr() }}</span>
                        </td>
                        <td class="py-4 px-6 text-gray-600">{{ $money($reservation->effective_total_amount) }}</td>
                        <td class="py-4 px-6 text-right">
                            <a href="{{ route('partner.reservations.show', $reservation) }}" class="text-[#0083c4] font-bold text-xs hover:underline">Ouvrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 px-6 text-center text-gray-500">Aucune reservation.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
