@extends('layouts.admin-v6')

@section('title', $employee->full_name)

@section('content')
    <x-admin.page-header
        :title="$employee->full_name"
        subtitle="Fiche employe point de vente, rattachement et activite liee aux reservations."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Employes des points de vente', 'url' => route('admin.agency-employees.index')],
            ['label' => $employee->full_name],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.agencies.show', $employee->branch_id) }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-buildings"></i>
                <span>Voir le point de vente</span>
            </a>
            @if($employee->user_id)
                <a href="{{ route('admin.agency-accounts.edit', $employee->user_id) }}" class="aj-btn aj-btn-soft">
                    <i class="bx bx-id-card"></i>
                    <span>Gerer le compte login</span>
                </a>
            @else
                <a href="{{ route('admin.agency-accounts.create', ['employee_id' => $employee->id]) }}" class="aj-btn aj-btn-soft">
                    <i class="bx bx-user-plus"></i>
                    <span>Creer compte login</span>
                </a>
            @endif
            <a href="{{ route('admin.agency-employees.edit', $employee) }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-pencil"></i>
                <span>Modifier</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Informations</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Point de vente</dt><dd class="col-sm-7">{{ $employee->branch?->name ?: '�?"' }}</dd>
                        <dt class="col-sm-5">Poste</dt><dd class="col-sm-7">{{ $employee->position ?: '�?"' }}</dd>
                        <dt class="col-sm-5">Statut</dt><dd class="col-sm-7">{{ \App\Models\AgencyEmployee::statusLabels()[$employee->status] ?? $employee->status }}</dd>
                        <dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $employee->email ?: '�?"' }}</dd>
                        <dt class="col-sm-5">Téléphone</dt><dd class="col-sm-7">{{ $employee->phone ?: '�?"' }}</dd>
                        <dt class="col-sm-5">Login</dt><dd class="col-sm-7">{{ $employee->can_login ? 'Oui' : 'Non' }}</dd>
                        <dt class="col-sm-5">Rôle</dt><dd class="col-sm-7">{{ $employee->user?->roles->pluck('name')->join(', ') ?: '�?"' }}</dd>
                        <dt class="col-sm-5">Dernière connexion</dt><dd class="col-sm-7">{{ $employee->user?->last_login_at?->format('d/m/Y H:i') ?: '�?"' }}</dd>
                        <dt class="col-sm-5">Departement</dt><dd class="col-sm-7">{{ $employee->department ?: '�?"' }}</dd>
                        <dt class="col-sm-5">Type employe</dt><dd class="col-sm-7">{{ $employee->employee_type ?: '�?"' }}</dd>
                        <dt class="col-sm-5">Contrat</dt><dd class="col-sm-7">{{ $employee->contract_type ?: '�?"' }}</dd>
                    </dl>
                    <div class="mt-3">
                        <strong>Note interne</strong>
                        <p class="text-muted mb-0">{{ $employee->notes ?: 'Aucune note.' }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Réservations liées</h5>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Statut</th>
                                    <th>Paiement</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentReservations as $reservation)
                                    <tr>
                                        <td>#{{ $reservation->id }}</td>
                                        <td>{{ trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '�?"' }}</td>
                                        <td>{{ $reservation->status }}</td>
                                        <td>{{ $reservation->payment_type ?: '�?"' }}</td>
                                        <td>{{ $reservation->created_at?->format('d/m/Y H:i') ?: '�?"' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Aucune réservation liée à ce collaborateur.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


