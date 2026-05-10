@extends('layouts.admin-v2')

@section('title', 'Dashboard point de vente')

@section('content')
<div class="aj-page-head" style="margin-bottom:18px;">
    <div>
        <h1>{{ $agency->display_name }}</h1>
        <p>Vue operationnelle du point de vente, des reservations et des comptes lies.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        @if(Route::has('admin.agencies.index'))
            <a href="{{ route('admin.agencies.index') }}" class="aj-btn">Retour points de vente</a>
        @endif
        @if(Route::has('admin.agency-accounts.index'))
            <a href="{{ route('admin.agency-accounts.index', ['branch_id' => $agency->id]) }}" class="aj-btn">Comptes points de vente</a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="aj-card"><div class="aj-card-body"><div class="aj-subtle">Réservations</div><div style="font-size:28px;font-weight:900;color:#172b4d;">{{ (int) $agency->reservations_count }}</div></div></div></div>
    <div class="col-md-3"><div class="aj-card"><div class="aj-card-body"><div class="aj-subtle">Employés actifs</div><div style="font-size:28px;font-weight:900;color:#172b4d;">{{ (int) $agency->agency_employees_count }}</div></div></div></div>
    <div class="col-md-3"><div class="aj-card"><div class="aj-card-body"><div class="aj-subtle">CA point de vente</div><div style="font-size:28px;font-weight:900;color:#172b4d;">{{ number_format($revenueTotal, 0, ',', ' ') }} €</div></div></div></div>
    <div class="col-md-3"><div class="aj-card"><div class="aj-card-body"><div class="aj-subtle">Non affectées</div><div style="font-size:28px;font-weight:900;color:#172b4d;">{{ (int) $unassignedReservationsCount }}</div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="aj-card">
            <div class="aj-card-head"><strong>Dernières réservations</strong></div>
            <div class="aj-card-body" style="padding-top:14px;overflow-x:auto;">
                <table class="aj-table">
                    <thead>
                        <tr><th>#</th><th>Client</th><th>Voyage</th><th>Agent</th><th>Statut</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        @forelse($reservations as $reservation)
                            <tr>
                                <td>#{{ $reservation->id }}</td>
                                <td>{{ trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '—' }}</td>
                                <td>{{ $reservation->tour?->name ?? '—' }}</td>
                                <td>{{ $reservation->agent?->name ?? '—' }}</td>
                                <td>{{ ucfirst((string) $reservation->status) }}</td>
                                <td>{{ $reservation->created_at?->timezone('Africa/Casablanca')?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;color:#71829a;font-weight:700;">Aucune réservation.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="aj-card mb-3">
            <div class="aj-card-head"><strong>Employés actifs</strong></div>
            <div class="aj-card-body">
                @forelse($employees as $employee)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #edf2f7;">
                        <div>
                            <div style="font-weight:900;color:#172b4d;">{{ $employee->full_name }}</div>
                            <div class="aj-subtle">{{ $employee->position ?? '—' }}</div>
                        </div>
                        <span class="aj-badge ok">{{ $employee->user?->roles->first()?->name ?? 'Employé' }}</span>
                    </div>
                @empty
                    <div class="aj-subtle">Aucun employé actif.</div>
                @endforelse
            </div>
        </div>

        <div class="aj-card">
            <div class="aj-card-head"><strong>Indicateurs</strong></div>
            <div class="aj-card-body">
                <div class="aj-subtle">Réservations en attente : {{ $pendingReservationsCount }}</div>
                <div class="aj-subtle">Réservations non affectées : {{ $unassignedReservationsCount }}</div>
                <div class="aj-subtle">Point de vente : {{ $agency->agency_type ? ucfirst($agency->agency_type) : '—' }}</div>
                <div class="aj-subtle">Manager : {{ $agency->manager?->name ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
