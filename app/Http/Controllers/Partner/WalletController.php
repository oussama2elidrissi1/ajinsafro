<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerWalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $partner = $request->user()->partner ?: $request->user()->ownedPartner;
        $transactions = $partner->walletTransactions()
            ->with(['requester:id,name', 'validator:id,name'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('partner_v2.wallet.index', compact('partner', 'transactions'));
    }

    public function rechargeRequest(Request $request): RedirectResponse
    {
        $partner = $request->user()->partner ?: $request->user()->ownedPartner;
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'virement', 'cheque', 'carte', 'autre'])],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('partner-wallet-proofs', 'public');
        }

        PartnerWalletTransaction::create([
            'partner_id' => $partner->id,
            'type' => PartnerWalletTransaction::TYPE_RECHARGE,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'proof_path' => $proofPath,
            'status' => PartnerWalletTransaction::STATUS_PENDING,
            'note' => $data['note'] ?? null,
            'requested_by' => $request->user()->id,
        ]);

        return redirect()->route('partner.wallet.index')
            ->with('success', 'Demande de recharge envoyee. Elle reste en attente de validation Ajinsafro.');
    }
}
