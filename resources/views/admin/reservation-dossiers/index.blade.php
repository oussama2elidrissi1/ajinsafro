@extends('layouts.admin-v2')

@section('title', 'Dossiers de réservation')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">Dossiers de réservation</h1>
            <div>
                <a href="{{ route('admin.reservations.create') }}" class="btn btn-primary">Créer un dossier</a>
            </div>
        </div>

        <div class="mb-3">
            <div class="row g-2">
                <div class="col-md-3"><strong>Total :</strong> {{ $stats['total'] ?? 0 }}</div>
                <div class="col-md-3"><strong>En attente :</strong> {{ $stats['pending'] ?? 0 }}</div>
                <div class="col-md-3"><strong>Restant :</strong> {{ $stats['remaining'] ?? 0 }}</div>
                <div class="col-md-3"><strong>Payés :</strong> {{ $stats['paid'] ?? 0 }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Dossier</th>
                                <th>Client</th>
                                <th>Offre</th>
                                <th>Départ</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Restant</th>
                                <th>Statut paiement</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dossiers as $dossier)
                                <tr>
                                    <td>{{ $dossier->dossier_number ?? ('#'.$dossier->id) }}</td>
                                    <td>{{ optional($dossier->client)->full_name ?? '—' }}</td>
                                    <td>{{ optional($dossier->mainReservation->offer)->name ?? optional($dossier->reservations->first()->offer)->name ?? '—' }}</td>
                                    <td>{{ optional($dossier->mainReservation->departure)->start_date ? optional($dossier->mainReservation->departure->start_date)->format('d/m/Y') : '—' }}</td>
                                    <td class="text-end">{{ number_format($dossier->total_amount ?? 0, 2, ',', ' ') }} DH</td>
                                    <td class="text-end">{{ number_format($dossier->remaining_amount ?? 0, 2, ',', ' ') }} DH</td>
                                    <td>{{ $dossier->payment_status ?? '—' }}</td>
                                    <td class="text-end"><a href="{{ route('admin.reservation-dossiers.show', $dossier) }}" class="btn btn-sm btn-outline-primary">Voir</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center p-4">Aucun dossier trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $dossiers->links() }}
            </div>
        </div>
    </div>
@endsection
