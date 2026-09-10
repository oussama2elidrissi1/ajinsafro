@extends('layouts.finance-control')

@section('title', 'Encaissements clients')

@php
    // Formatage monetaire unique pour tout le module.
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
@endphp

@section('finance_content')
<div class="container-fluid">
    <div class="mb-3">
        <h4 class="mb-1">Encaissements clients</h4>
        <p class="text-muted mb-0 small">Paiements deja enregistres sur les reservations. Consultation seule : aucune ressaisie n'est demandee ici.</p>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Du</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Au</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted">Voyage</label>
                    <select name="voyage_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($voyages as $voyage)
                            <option value="{{ $voyage->id }}" @selected($filters['voyage_id'] === $voyage->id)>{{ $voyage->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted">Agence</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Mode</label>
                    <select name="payment_method" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($paymentMethods as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['payment_method'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Recherche</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] }}" placeholder="Dossier / client">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-sm btn-primary">Filtrer</button>
                    <a href="{{ route('admin.finance.control.client-collections.index') }}" class="btn btn-sm btn-outline-secondary">Reinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">Date paiement</th>
                        <th>Reservation</th>
                        <th>Client</th>
                        <th>Voyage / depart</th>
                        <th class="text-end">Total dossier</th>
                        <th class="text-end">Paiement</th>
                        <th class="text-end">Reste dossier</th>
                        <th>Mode</th>
                        <th>Agence</th>
                        <th>Enregistre par</th>
                        <th>Justificatif</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        @php($reservation = $payment->reservation)
                        <tr>
                            <td class="px-3 text-nowrap">{{ $payment->payment_date?->format('d/m/Y') ?: '-' }}</td>
                            <td class="text-nowrap">{{ $reservation?->dossier_number ?: 'RES-'.$payment->reservation_id }}</td>
                            <td>{{ trim(($reservation?->client_first_name ?? '').' '.($reservation?->client_last_name ?? '')) ?: '-' }}</td>
                            <td>
                                {{ $reservation?->departure?->voyage?->name ?: '-' }}
                                <span class="text-muted d-block">{{ $reservation?->departure?->start_date?->format('d/m/Y') }}</span>
                            </td>
                            <td class="text-end">{{ $money($reservation?->total_amount ?? 0) }}</td>
                            <td class="text-end fw-semibold">{{ $money($payment->amount) }}</td>
                            <td class="text-end">{{ $money(max(0, (float) ($reservation?->total_amount ?? 0) - (float) ($reservation?->paid_amount ?? 0))) }}</td>
                            <td>{{ $payment->payment_method }}</td>
                            <td>{{ $reservation?->branch?->name ?: '-' }}</td>
                            <td>{{ $payment->creator?->name ?: '-' }}</td>
                            <td>
                                @if ($payment->proof_file || $payment->financialDocuments->isNotEmpty())
                                    <span class="badge bg-success-subtle text-success">Oui</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Manquant</span>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-body">{{ $reservation?->payment_status ?: '-' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted py-5">Aucun encaissement pour ces criteres.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $payments->links() }}</div>
</div>
@endsection
