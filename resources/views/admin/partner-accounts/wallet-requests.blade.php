@extends('layouts.admin-v6')
@section('title', 'Demandes wallet')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="page-title mb-0 font-size-18">Demandes recharge wallet</h4>
            <a href="{{ route('admin.partners.partenaires') }}" class="btn btn-outline-secondary btn-sm">Partenaires</a>
        </div>
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
        <form method="GET" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        <option value="pending" @selected(request('status') === 'pending')>En attente</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approuve</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Refuse</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm">Filtrer</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Partenaire</th>
                        <th>Montant</th>
                        <th>Mode paiement</th>
                        <th>Justificatif</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->partner?->display_name ?? '-' }}</td>
                            <td>{{ number_format((float) $transaction->amount, 2, ',', ' ') }} DH</td>
                            <td>{{ $transaction->payment_method ?? '-' }}</td>
                            <td>
                                @if($transaction->proof_path)
                                    <a href="{{ asset('storage/' . $transaction->proof_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">Voir</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $transaction->created_at?->format('d/m/Y H:i') }}</td>
                            <td><span class="badge {{ $transaction->status === 'approved' ? 'bg-success' : ($transaction->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $transaction->status }}</span></td>
                            <td class="text-end">
                                @if($transaction->partner)
                                    <a href="{{ route('admin.partners.wallet', $transaction->partner) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                                @endif
                                @if($transaction->isPending())
                                    <form action="{{ route('admin.partners.wallet-requests.approve', $transaction) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success">Valider</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject-wallet-{{ $transaction->id }}">Refuser</button>
                                    <div class="modal fade" id="reject-wallet-{{ $transaction->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <form class="modal-content" method="POST" action="{{ route('admin.partners.wallet-requests.reject', $transaction) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Refuser la recharge</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <label class="form-label">Note admin</label>
                                                    <textarea name="admin_note" class="form-control" rows="4"></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                                                    <button class="btn btn-danger">Refuser</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucune demande wallet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
