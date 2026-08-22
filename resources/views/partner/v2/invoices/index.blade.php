@extends('partner_v2.layouts.app')
@section('title', 'Factures & Devis')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Factures & Devis</h1>
    <p class="text-sm text-gray-500 mt-1">Historique des réservations et pièces associées (reçus, documents visa).</p>
</div>

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
            <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                <th class="py-4 px-6">Réservation</th>
                <th class="py-4 px-6">Voyage</th>
                <th class="py-4 px-6">Statut</th>
                <th class="py-4 px-6">Créée le</th>
                <th class="py-4 px-6">Pièces</th>
                <th class="py-4 px-6 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
            @forelse($reservations as $r)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-4 px-6 font-bold text-[#0e3a5a]">#{{ $r->id }}</td>
                    <td class="py-4 px-6 text-gray-700 font-semibold">{{ $r->tour?->name ?? '—' }}</td>
                    <td class="py-4 px-6">
                        @php
                            $status = $r->status;
                            $badge = $status === \App\Models\Reservation::STATUS_VALIDEE ? 'bg-green-50 text-green-700 border-green-200' : ($status === \App\Models\Reservation::STATUS_ANNULEE ? 'bg-red-50 text-red-700 border-red-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200');
                        @endphp
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $badge }}">{{ $status }}</span>
                    </td>
                    <td class="py-4 px-6 text-gray-500">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="py-4 px-6">
                        <div class="flex flex-wrap gap-2">
                            @if(!empty($r->payment_receipt))
                                <a class="text-[11px] font-bold text-[#0083c4] hover:underline" target="_blank" rel="noopener"
                                   href="{{ route('partner.invoices.file', ['reservation' => $r->id, 'path' => $r->payment_receipt]) }}">
                                    Reçu paiement
                                </a>
                            @endif
                            @if(!empty($r->visa_document))
                                <a class="text-[11px] font-bold text-[#0083c4] hover:underline" target="_blank" rel="noopener"
                                   href="{{ route('partner.invoices.file', ['reservation' => $r->id, 'path' => $r->visa_document]) }}">
                                    Doc visa
                                </a>
                            @endif
                            @if(empty($r->payment_receipt) && empty($r->visa_document))
                                <span class="text-[11px] text-gray-400 font-semibold">—</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-4 px-6 text-right">
                        <a href="{{ route('partner.reservations.show', $r) }}" class="text-[#0083c4] font-bold text-xs hover:underline">Ouvrir</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-10 px-6 text-center text-gray-500">Aucune réservation.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4">
        {{ $reservations->links("pagination::tailwind") }}
    </div>
</div>
@endsection

