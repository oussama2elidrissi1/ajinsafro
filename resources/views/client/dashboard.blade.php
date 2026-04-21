@extends('client.layout')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard client')

@section('content')
    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-2">Bienvenue</h5>
                    <div class="text-muted">
                        {{ $client->full_name ?: trim(($client->first_name ?? '').' '.($client->last_name ?? '')) ?: 'Client' }}
                    </div>
                    <div class="mt-2">
                        <div><span class="text-muted">Email:</span> {{ $client->email ?: '—' }}</div>
                        <div><span class="text-muted">Téléphone:</span> {{ $client->phone ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">Dernières réservations</h5>
                        <a href="{{ route('client.reservations.index') }}" class="btn btn-sm btn-outline-secondary">Voir tout</a>
                    </div>
                    @if($recentReservations->isEmpty())
                        <div class="text-muted">Aucune réservation pour le moment.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th class="text-end">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($recentReservations as $r)
                                    <tr>
                                        <td>#{{ $r->id }}</td>
                                        <td>{{ $r->statusLabelFr() }}</td>
                                        <td>{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-soft-primary" href="{{ route('client.reservations.show', $r) }}">Détail</a>
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
@endsection

