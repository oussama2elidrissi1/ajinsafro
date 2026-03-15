@extends('layouts.partner')
@section('title', 'Dashboard')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title mb-0 font-size-18">Tableau de bord</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md rounded bg-primary bg-opacity-10 me-3">
                            <i class="bx bx-calendar-check font-size-24 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Réservations (mois)</h6>
                            <h4 class="mb-0">{{ $reservationsThisMonth }} <small class="text-muted">/ {{ $reservationsCount }} total</small></h4>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-2">
                    <a href="{{ route('partner.reservations.index') }}" class="text-primary small">Voir tout <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md rounded bg-success bg-opacity-10 me-3">
                            <i class="bx bx-user font-size-24 text-success"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Mes clients</h6>
                            <h4 class="mb-0">{{ $clientsCount }}</h4>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-2">
                    <a href="{{ route('partner.clients.index') }}" class="text-primary small">Voir tout <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md rounded bg-info bg-opacity-10 me-3">
                            <i class="bx bx-wallet font-size-24 text-info"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Commissions (validées + payées)</h6>
                            <h4 class="mb-0">{{ number_format($commissionsTotal, 0, ',', ' ') }} DH</h4>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-2">
                    <a href="{{ route('partner.commissions.index') }}" class="text-primary small">Détail <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md rounded bg-warning bg-opacity-10 me-3">
                            <i class="bx bx-time-five font-size-24 text-warning"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">En attente</h6>
                            <h4 class="mb-0">{{ number_format($commissionsPending, 0, ',', ' ') }} DH</h4>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top py-2">
                    <a href="{{ route('partner.commissions.index') }}" class="text-primary small">Voir <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Dernières réservations</h5>
                </div>
                <div class="card-body p-0">
                    @if($recentReservations->isEmpty())
                        <p class="text-muted p-4 mb-0">Aucune réservation.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Voyage</th>
                                        <th>Client</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentReservations as $reservation)
                                        <tr>
                                            <td class="ps-3">{{ $reservation->tour?->name ?? '—' }}</td>
                                            <td>{{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}</td>
                                            <td><span class="badge bg-{{ $reservation->status === \App\Models\Reservation::STATUS_VALIDEE ? 'success' : ($reservation->status === \App\Models\Reservation::STATUS_ANNULEE ? 'danger' : 'warning text-dark') }}">{{ $reservation->status }}</span></td>
                                            <td>{{ $reservation->created_at?->format('d/m/Y') }}</td>
                                            <td class="text-end pe-3">
                                                <a href="{{ route('partner.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-show"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(isset($topVoyages) && $topVoyages->isNotEmpty())
    <div class="row mt-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Top voyages vendus</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($topVoyages as $item)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $item->tour?->name ?? 'Voyage #'.$item->tour_id }}
                                <span class="badge bg-primary rounded-pill">{{ $item->cnt }} résa.</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection
