@extends('layouts.master-ajinsafro')

@section('title', 'Réservations')

@section('content')
    <!-- start page title -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">
                    Réservations
                    @if($status === 'EN_COURS') - En attente
                    @elseif($status === 'VALIDEE') - Confirmées
                    @elseif($status === 'ANNULEE') - Annulées
                    @endif
                </h4>
                <div>
                    <a href="{{ route('admin.reservations.create') }}" class="btn btn-primary">
                        Nouvelle réservation
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Passagers</th>
                            <th>Paiement</th>
                            <th>Statut</th>
                            <th>Créée le</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($reservations as $reservation)
                            <tr>
                                <td>{{ $reservation->id }}</td>
                                <td>
                                    {{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: 'Client #'.$reservation->client_external_id }}
                                </td>
                                <td>{{ $reservation->passengers_count }}</td>
                                <td>{{ $reservation->payment_type ?? '-' }}</td>
                                <td><span class="badge bg-secondary">{{ $reservation->status }}</span></td>
                                <td>{{ optional($reservation->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Aucune réservation trouvée.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    @if(method_exists($reservations, 'links'))
                        <div class="mt-3">
                            {{ $reservations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
