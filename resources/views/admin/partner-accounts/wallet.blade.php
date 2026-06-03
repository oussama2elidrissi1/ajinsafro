@extends('layouts.admin-v6')
@section('title', 'Wallet partenaire')

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex align-items-center justify-content-between">
        <div>
            <h4 class="page-title mb-0 font-size-18">Wallet - {{ $partner->display_name }}</h4>
            <p class="text-muted mb-0">Solde: <strong>{{ number_format((float) ($partner->wallet_balance ?? 0), 2, ',', ' ') }} DH</strong></p>
        </div>
        <a href="{{ route('admin.partners.show', $partner) }}" class="btn btn-outline-secondary btn-sm">Retour</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Paiement</th>
                        <th>Statut</th>
                        <th>Solde avant</th>
                        <th>Solde apres</th>
                        <th>Justificatif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $transaction->type }}</td>
                            <td>{{ number_format((float) $transaction->amount, 2, ',', ' ') }} DH</td>
                            <td>{{ $transaction->payment_method ?? '-' }}</td>
                            <td><span class="badge {{ $transaction->status === 'approved' ? 'bg-success' : ($transaction->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $transaction->status }}</span></td>
                            <td>{{ $transaction->balance_before !== null ? number_format((float) $transaction->balance_before, 2, ',', ' ') . ' DH' : '-' }}</td>
                            <td>{{ $transaction->balance_after !== null ? number_format((float) $transaction->balance_after, 2, ',', ' ') . ' DH' : '-' }}</td>
                            <td>
                                @if($transaction->proof_path)
                                    <a href="{{ asset('storage/' . $transaction->proof_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Voir</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Aucune operation wallet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
