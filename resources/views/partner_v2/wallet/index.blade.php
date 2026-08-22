@extends('partner_v2.layouts.app')
@section('title', 'Wallet')

@section('content')
@php($money = fn ($value) => number_format((float) $value, 2, ',', ' ') . ' DH')

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Wallet</h1>
    <p class="text-sm text-gray-500 mt-1">Solde et demandes de recharge de votre agence.</p>
</div>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-6">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Solde actuel</p>
            <h2 class="text-4xl font-black text-[#0e3a5a] mt-2">{{ $money($partner->wallet_balance ?? 0) }}</h2>
            <p class="text-xs text-gray-500 mt-2">Le solde change uniquement apres validation Ajinsafro.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-6">
            <h2 class="font-bold text-[#0e3a5a] mb-4">Demander une recharge</h2>
            <form method="POST" action="{{ route('partner.wallet.recharge-request') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Montant</label>
                    <input type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" class="mt-1 w-full rounded-xl border-gray-200" required>
                    @error('amount')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Mode de paiement</label>
                    <select name="payment_method" class="mt-1 w-full rounded-xl border-gray-200" required>
                        @foreach(['cash' => 'Cash', 'virement' => 'Virement', 'cheque' => 'Cheque', 'carte' => 'Carte', 'autre' => 'Autre'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Justificatif</label>
                    <input type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                    @error('proof')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Note</label>
                    <textarea name="note" rows="3" class="mt-1 w-full rounded-xl border-gray-200">{{ old('note') }}</textarea>
                </div>
                <button class="w-full bg-[#0083c4] hover:bg-[#0e3a5a] text-white rounded-xl px-4 py-3 text-sm font-bold transition-colors">Demander une recharge</button>
            </form>
        </div>
    </div>

    <div class="xl:col-span-2 bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-gray-50/50">
            <h2 class="font-bold text-[#0e3a5a]">Historique des operations</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Montant</th>
                        <th class="px-5 py-3">Paiement</th>
                        <th class="px-5 py-3">Statut</th>
                        <th class="px-5 py-3">Justificatif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($transactions as $transaction)
                        <tr>
                            <td class="px-5 py-4 text-gray-600">{{ $transaction->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4 font-semibold text-[#0e3a5a]">{{ $transaction->type }}</td>
                            <td class="px-5 py-4">{{ $money($transaction->amount) }}</td>
                            <td class="px-5 py-4">{{ $transaction->payment_method ?? '-' }}</td>
                            <td class="px-5 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $transaction->status === 'approved' ? 'bg-green-50 text-green-700' : ($transaction->status === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-yellow-50 text-yellow-700') }}">{{ $transaction->status }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if($transaction->proof_path)
                                    <a href="{{ asset('storage/' . $transaction->proof_path) }}" target="_blank" class="text-[#0083c4] font-bold text-xs hover:underline">Voir</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">Aucune operation wallet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5">{{ $transactions->links("pagination::tailwind") }}</div>
    </div>
</div>
@endsection
