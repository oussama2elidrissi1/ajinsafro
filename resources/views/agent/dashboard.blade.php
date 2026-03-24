@extends('layouts.master-agent')

@section('title')
    Dashboard Agent
@endsection

@section('content')
    @php
        $user = auth()->user();
        $roleLabel = $user?->getRoleNames()->first() ?? ($user?->is_admin ? 'admin' : 'utilisateur');
        $roleLabel = \Illuminate\Support\Str::title(\Illuminate\Support\Str::replace('_', ' ', (string) $roleLabel));
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Dashboard Agent</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('agent.dashboard') }}">Agent</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-2">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-1">{{ $user?->name }}</h5>
                    <p class="text-muted mb-0">{{ $roleLabel }}@if($user?->branch) - {{ $user->branch->name }} @endif</p>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap gap-2">
                    @if(Route::has('admin.reservations.create') && auth()->user()->can('reservations.create'))
                        <a href="{{ route('admin.reservations.create') }}" class="btn btn-primary btn-sm">
                            <i class="bx bx-plus-circle me-1"></i> Nouvelle réservation
                        </a>
                    @endif
                    @if(Route::has('admin.customers.clients.create') && auth()->user()->can('customers.clients.view'))
                        <a href="{{ route('admin.customers.clients.create') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bx bx-user-plus me-1"></i> Nouveau client
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Réservations</p>
                    <h5 class="mb-0">{{ $stats['reservations_total'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1 small">En cours</p>
                    <h5 class="mb-0">{{ $stats['reservations_en_cours'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Validées</p>
                    <h5 class="mb-0">{{ $stats['reservations_validees'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Clients</p>
                    <h5 class="mb-0">{{ $stats['clients_count'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Voyages</p>
                    <h5 class="mb-0">{{ $stats['voyages_count'] }}</h5>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Départs à venir</p>
                    <h5 class="mb-0">{{ $stats['departures_upcoming'] }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0">Dernières réservations</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Voyage</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentReservations as $reservation)
                                    <tr>
                                        <td>{{ $reservation->id }}</td>
                                        <td>{{ trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '-' }}</td>
                                        <td>{{ $reservation->tour?->name ?? '-' }}</td>
                                        <td><span class="badge bg-light text-dark">{{ $reservation->status }}</span></td>
                                        <td>{{ optional($reservation->created_at)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted text-center">Aucune réservation trouvée.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0">Derniers clients</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Nom</th>
                                    <th>Contact</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentClients as $client)
                                    <tr>
                                        <td>{{ $client->client_code }}</td>
                                        <td>{{ $client->full_name ?: trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) }}</td>
                                        <td>{{ $client->email ?: ($client->phone ?: '-') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted text-center">Aucun client trouvé.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
