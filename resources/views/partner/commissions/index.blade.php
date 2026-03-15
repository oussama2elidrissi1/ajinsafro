@extends('layouts.partner')
@section('title', 'Commissions')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title mb-0 font-size-18">Mes commissions</h4>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h6 class="text-muted">Validées (en attente de paiement)</h6>
                    <h4 class="text-primary">{{ number_format($totalValidated, 0, ',', ' ') }} DH</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <h6 class="text-muted">Payées</h6>
                    <h4 class="text-success">{{ number_format($totalPaid, 0, ',', ' ') }} DH</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <h6 class="text-muted">En attente (résa. non confirmée)</h6>
                    <h4 class="text-warning">{{ number_format($totalPending, 0, ',', ' ') }} DH</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <select name="status" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tous les statuts</option>
                            <option value="calculated" {{ request('status') === 'calculated' ? 'selected' : '' }}>Calculée</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                            <option value="validated" {{ request('status') === 'validated' ? 'selected' : '' }}>Validée</option>
                            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payée</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Réservation / Voyage</th>
                            <th>Montant résa.</th>
                            <th>Commission</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $c)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('partner.reservations.show', $c->reservation) }}">#{{ $c->reservation_id }}</a>
                                    @if($c->reservation && $c->reservation->tour)
                                        <br><span class="text-muted small">{{ $c->reservation->tour->name }}</span>
                                    @endif
                                </td>
                                <td>{{ number_format($c->reservation_total, 0, ',', ' ') }} DH</td>
                                <td><strong>{{ number_format($c->amount, 0, ',', ' ') }} DH</strong></td>
                                <td>
                                    @php
                                        $badge = match($c->status) {
                                            'validated' => 'badge bg-primary',
                                            'paid' => 'badge bg-success',
                                            'cancelled' => 'badge bg-danger',
                                            default => 'badge bg-warning text-dark',
                                        };
                                    @endphp
                                    <span class="{{ $badge }}">{{ $c->status }}</span>
                                </td>
                                <td>{{ $c->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Aucune commission.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($commissions, 'links'))
                <div class="d-flex justify-content-center mt-3">{{ $commissions->links() }}</div>
            @endif
        </div>
    </div>
@endsection
