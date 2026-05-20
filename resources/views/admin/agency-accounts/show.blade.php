@extends('layouts.admin-v6')

@section('title', 'Compte point de vente')

@section('content')
<div class="aj-page-head" style="margin-bottom:18px;">
    <div>
        <h1>{{ $account->name }}</h1>
        <p>{{ $account->email }} �?� {{ $account->branch?->display_name ?? 'Aucun point de vente' }}</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        @if(Route::has('admin.agency-accounts.edit'))
            <a href="{{ route('admin.agency-accounts.edit', $account) }}" class="aj-btn primary">�?diter</a>
        @endif
        @if(Route::has('admin.agency-accounts.reset-password'))
            <form method="POST" action="{{ route('admin.agency-accounts.reset-password', $account) }}">
                @csrf
                <button type="submit" class="aj-btn">Reset password</button>
            </form>
        @endif
        @if(Route::has('admin.agency-accounts.disable'))
            <form method="POST" action="{{ route('admin.agency-accounts.disable', $account) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="aj-btn">Désactiver</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="aj-card">
            <div class="aj-card-body text-center">
                <img src="{{ $account->avatar_url }}" alt="{{ $account->name }}" style="width:96px;height:96px;border-radius:50%;object-fit:cover;margin-bottom:14px;">
                <h3 style="margin:0 0 6px;font-weight:900;">{{ $account->name }}</h3>
                <div class="aj-subtle">{{ $account->roles->first()?->name ?? 'Sans rôle' }}</div>
                <div style="margin-top:12px;">
                    @if($account->is_active)
                        <span class="aj-badge ok">Actif</span>
                    @else
                        <span class="aj-badge off">Inactif</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="aj-card mt-3">
            <div class="aj-card-body">
                <strong style="display:block;margin-bottom:10px;">Informations</strong>
                <div class="aj-subtle">Employé lié : {{ $account->agencyEmployee?->full_name ?? 'Aucun' }}</div>
                <div class="aj-subtle">Fonction : {{ $account->job_title ?? '�?"' }}</div>
                <div class="aj-subtle">Téléphone : {{ $account->phone ?? '�?"' }}</div>
                <div class="aj-subtle">Dernière connexion : {{ $account->last_login_at?->timezone('Africa/Casablanca')?->format('d/m/Y H:i') ?? 'Jamais' }}</div>
                <div class="aj-subtle">Réservations affectées : {{ (int) ($account->assigned_reservations_count ?? 0) }}</div>
            </div>
        </div>

        <div class="aj-card mt-3">
            <div class="aj-card-body">
                <strong style="display:block;margin-bottom:10px;">Permissions principales</strong>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @foreach($account->getAllPermissions()->take(10) as $permission)
                        <span class="aj-badge soft">{{ $permission->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="aj-card">
            <div class="aj-card-head">
                <div>
                    <strong style="font-size:16px;">Réservations affectées</strong>
                    <div class="aj-subtle">Dernières réservations liées à ce compte</div>
                </div>
            </div>
            <div class="aj-card-body" style="padding-top:14px;overflow-x:auto;">
                <table class="aj-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Voyage</th>
                            <th>Point de vente</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentReservations as $reservation)
                            <tr>
                                <td>#{{ $reservation->id }}</td>
                                <td>{{ trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '�?"' }}</td>
                                <td>{{ $reservation->tour?->name ?? '�?"' }}</td>
                                <td>{{ $reservation->branch?->display_name ?? '�?"' }}</td>
                                <td>{{ ucfirst((string) $reservation->status) }}</td>
                                <td>{{ $reservation->created_at?->timezone('Africa/Casablanca')?->format('d/m/Y H:i') ?? '�?"' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;color:#71829a;font-weight:700;">Aucune réservation affectée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection


