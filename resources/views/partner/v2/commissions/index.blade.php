@extends('partner_v2.layouts.app')
@section('title', 'Commissions')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Commissions</h1>
    <p class="text-sm text-gray-500 mt-1">Récapitulatif et détail des commissions calculées sur vos ventes.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6 mb-6">
    <div class="bg-white p-5 rounded-2xl shadow-custom border border-gray-100">
        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Validées</div>
        <div class="text-2xl font-black text-[#0083c4] mt-1">{{ number_format($totalValidated, 0, ',', ' ') }} DH</div>
        <div class="text-[11px] text-gray-400 font-semibold mt-1">En attente de paiement</div>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-custom border border-gray-100">
        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Payées</div>
        <div class="text-2xl font-black text-green-700 mt-1">{{ number_format($totalPaid, 0, ',', ' ') }} DH</div>
        <div class="text-[11px] text-gray-400 font-semibold mt-1">Historique</div>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-custom border border-gray-100">
        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">En attente</div>
        <div class="text-2xl font-black text-[#f37a1f] mt-1">{{ number_format($totalPending, 0, ',', ' ') }} DH</div>
        <div class="text-[11px] text-gray-400 font-semibold mt-1">Réservations non confirmées</div>
    </div>
</div>

<div class="bg-white p-4 rounded-2xl shadow-custom border border-gray-100 mb-4">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <select name="status" class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#0083c4] text-[#0e3a5a] font-medium cursor-pointer">
            <option value="">Tous les statuts</option>
            <option value="calculated" {{ request('status') === 'calculated' ? 'selected' : '' }}>Calculée</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
            <option value="validated" {{ request('status') === 'validated' ? 'selected' : '' }}>Validée</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payée</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulée</option>
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
                <th class="py-4 px-6">Réservation / Voyage</th>
                <th class="py-4 px-6">Montant résa.</th>
                <th class="py-4 px-6">Commission</th>
                <th class="py-4 px-6">Statut</th>
                <th class="py-4 px-6">Date</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
            @forelse($commissions as $c)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-4 px-6">
                        <a class="font-bold text-[#0083c4] hover:underline" href="{{ route('partner.reservations.show', $c->reservation) }}">#{{ $c->reservation_id }}</a>
                        @if($c->reservation && $c->reservation->tour)
                            <div class="text-[11px] text-gray-500 font-semibold mt-1">{{ $c->reservation->tour->name }}</div>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-gray-700 font-semibold">{{ number_format($c->reservation_total, 0, ',', ' ') }} DH</td>
                    <td class="py-4 px-6 font-black text-[#0e3a5a]">{{ number_format($c->amount, 0, ',', ' ') }} DH</td>
                    <td class="py-4 px-6 text-gray-600 font-semibold">{{ $c->status }}</td>
                    <td class="py-4 px-6 text-gray-500">{{ $c->created_at?->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-10 px-6 text-center text-gray-500">Aucune commission.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">
        {{ $commissions->links("pagination::tailwind") }}
    </div>
</div>
@endsection

