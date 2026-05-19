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
                        <dt class="col-sm-5">Point de vente</dt><dd class="col-sm-7">{{ $employee->branch?->name ?: 'â€”' }}</dd>
                        <dt class="col-sm-5">Poste</dt><dd class="col-sm-7">{{ $employee->position ?: 'â€”' }}</dd>
                        <dt class="col-sm-5">Statut</dt><dd class="col-sm-7">{{ \App\Models\AgencyEmployee::statusLabels()[$employee->status] ?? $employee->status }}</dd>
                        <dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $employee->email ?: 'â€”' }}</dd>
                        <dt class="col-sm-5">TÃ©lÃ©phone</dt><dd class="col-sm-7">{{ $employee->phone ?: 'â€”' }}</dd>
                        <dt class="col-sm-5">Login</dt><dd class="col-sm-7">{{ $employee->can_login ? 'Oui' : 'Non' }}</dd>
                        <dt class="col-sm-5">RÃ´le</dt><dd class="col-sm-7">{{ $employee->user?->roles->pluck('name')->join(', ') ?: 'â€”' }}</dd>
                        <dt class="col-sm-5">DerniÃ¨re connexion</dt><dd class="col-sm-7">{{ $employee->user?->last_login_at?->format('d/m/Y H:i') ?: 'â€”' }}</dd>
                        <dt class="col-sm-5">Departement</dt><dd class="col-sm-7">{{ $employee->department ?: 'â€”' }}</dd>
                        <dt class="col-sm-5">Type employe</dt><dd class="col-sm-7">{{ $employee->employee_type ?: 'â€”' }}</dd>
                        <dt class="col-sm-5">Contrat</dt><dd class="col-sm-7">{{ $employee->contract_type ?: 'â€”' }}</dd>
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
                    <h5 class="mb-3">RÃ©servations liÃ©es</h5>
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
                                        <td>{{ trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: 'â€”' }}</td>
                                        <td>{{ $reservation->status }}</td>
                                        <td>{{ $reservation->payment_type ?: 'â€”' }}</td>
                                        <td>{{ $reservation->created_at?->format('d/m/Y H:i') ?: 'â€”' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Aucune rÃ©servation liÃ©e Ã  ce collaborateur.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

