@extends('layouts.admin-v6')

@section('title', $agency->name)

@section('content')
    <x-admin.page-header
        :title="$agency->name"
        subtitle="Vue détaillée du point de vente, de ses équipes et de son activité."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente', 'url' => route('admin.agencies.index')],
            ['label' => $agency->name],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.agency-employees.create', ['agency_id' => $agency->id]) }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-user-plus"></i>
                <span>Ajouter un employe</span>
            </a>
            <a href="{{ route('admin.agencies.performance', ['agency_id' => $agency->id]) }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-bar-chart-alt-2"></i>
                <span>Performance</span>
            </a>
            <a href="{{ route('admin.agencies.edit', $agency) }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-pencil"></i>
                <span>Modifier</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <x-admin.kpi-cards :kpis="[
        ['label' => 'Réservations', 'value' => number_format($totals['reservations_total'], 0, ',', ' '), 'icon' => 'bx bx-calendar-check', 'color' => '-blue', 'note' => 'Depuis l?Touverture'],
        ['label' => 'Ce mois', 'value' => number_format($totals['reservations_month'], 0, ',', ' '), 'icon' => 'bx bx-time-five', 'color' => '-green', 'note' => 'Activité mensuelle'],
        ['label' => 'CA', 'value' => number_format($totals['revenue_total'], 0, ',', ' ') . ' DH', 'icon' => 'bx bx-line-chart', 'color' => '-orange', 'note' => 'Montant estimé'],
        ['label' => 'Commission', 'value' => number_format($totals['estimated_commission'], 0, ',', ' ') . ' DH', 'icon' => 'bx bx-wallet', 'color' => '-violet', 'note' => 'Projection'],
        ['label' => 'Employés actifs', 'value' => number_format($totals['employees_active'], 0, ',', ' '), 'icon' => 'bx bx-user-check', 'color' => '-blue', 'note' => 'Comptes opérationnels'],
        ['label' => 'Clients traités', 'value' => number_format($totals['clients_handled'], 0, ',', ' '), 'icon' => 'bx bx-group', 'color' => '-green', 'note' => 'Clients distincts'],
    ]" />

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Informations générales</h5>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Code</dt><dd class="col-sm-7">{{ $agency->code }}</dd>
                        <dt class="col-sm-5">Type</dt><dd class="col-sm-7">{{ \App\Models\Branch::agencyTypeLabels()[$agency->agency_type] ?? $agency->agency_type }}</dd>
                        <dt class="col-sm-5">Statut</dt><dd class="col-sm-7">{{ \App\Models\Branch::statusLabels()[$agency->status] ?? $agency->status }}</dd>
                        <dt class="col-sm-5">Ville</dt><dd class="col-sm-7">{{ $agency->city ?: '?' }}</dd>
                        <dt class="col-sm-5">Pays</dt><dd class="col-sm-7">{{ $agency->country ?: '?' }}</dd>
                        <dt class="col-sm-5">Téléphone</dt><dd class="col-sm-7">{{ $agency->phone ?: '?' }}</dd>
                        <dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $agency->email ?: '?' }}</dd>
                        <dt class="col-sm-5">Manager</dt><dd class="col-sm-7">{{ $agency->manager?->name ?: '?' }}</dd>
                        <dt class="col-sm-5">Commission</dt><dd class="col-sm-7">{{ $agency->default_commission_value ? number_format((float) $agency->default_commission_value, 2, ',', ' ') . ' ' . (\App\Models\Branch::commissionTypeLabels()[$agency->default_commission_type] ?? '') : ($agency->default_commission_rate ? number_format($agency->default_commission_rate, 2, ',', ' ') . '%' : '?') }}</dd>
                        <dt class="col-sm-5">Devise</dt><dd class="col-sm-7">{{ $agency->currency ?: 'MAD' }}</dd>
                        <dt class="col-sm-5">Objectif CA</dt><dd class="col-sm-7">{{ $agency->monthly_revenue_target ? number_format((float) $agency->monthly_revenue_target, 0, ',', ' ') . ' ' . ($agency->currency ?: 'MAD') : '?' }}</dd>
                        <dt class="col-sm-5">Objectif reservations</dt><dd class="col-sm-7">{{ $agency->monthly_reservations_target ?: '?' }}</dd>
                    </dl>
                    @if($agency->address)
                        <div class="mt-3">
                            <strong>Adresse</strong>
                            <div class="text-muted">{{ $agency->address }}</div>
                        </div>
                    @endif
                    @if($agency->business_hours)
                        <div class="mt-3">
                            <strong>Horaires</strong>
                            <div class="text-muted" style="white-space:pre-line;">{{ $agency->business_hours }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Documents & notes</h5>
                    @if(!empty($agency->documents))
                        <div class="d-flex flex-column gap-2 mb-3">
                            @foreach($agency->documents as $document)
                                <a href="{{ asset('storage/' . $document['path']) }}" target="_blank" class="aj-btn aj-btn-soft justify-content-start">
                                    <i class="bx bx-file"></i>
                                    <span>{{ $document['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-3">Aucun document chargé.</p>
                    @endif
                    <strong>Notes internes</strong>
                    <p class="text-muted mb-0" style="white-space:pre-line;">{{ $agency->internal_notes ?: 'Aucune note interne.' }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Performance mensuelle</h5>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Mois</th>
                                    <th>Réservations</th>
                                    <th>CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlySeries['labels'] as $index => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td>{{ $monthlySeries['reservations'][$index] }}</td>
                                        <td>{{ number_format($monthlySeries['revenue'][$index], 0, ',', ' ') }} DH</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Employes du point de vente</h5>
                        <a href="{{ route('admin.agency-employees.index', ['branch_id' => $agency->id]) }}" class="aj-btn aj-btn-soft">Voir tous</a>
                    </div>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Poste</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $employee)
                                    <tr>
                                        <td><a href="{{ route('admin.agency-employees.show', $employee) }}">{{ $employee->full_name }}</a></td>
                                        <td>{{ $employee->position ?: '?' }}</td>
                                        <td>{{ $employee->user?->roles->pluck('name')->join(', ') ?: '?' }}</td>
                                        <td>{{ \App\Models\AgencyEmployee::statusLabels()[$employee->status] ?? $employee->status }}</td>
                                        <td>{{ $employee->email ?: '?' }}</td>
                                        <td>{{ $employee->phone ?: '?' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Aucun employé rattaché.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Dernières réservations</h5>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Statut</th>
                                    <th>Paiement</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentReservations as $reservation)
                                    <tr>
                                        <td>#{{ $reservation->id }}</td>
                                        <td>{{ trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '?' }}</td>
                                        <td>{{ $reservation->status }}</td>
                                        <td>{{ $reservation->payment_type ?: '?' }}</td>
                                        <td>{{ number_format((float) $reservation->paid_amount, 0, ',', ' ') }} DH</td>
                                        <td>{{ $reservation->created_at?->format('d/m/Y H:i') ?: '?' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Aucune réservation liée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Commissions estimées</h5>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Réservation</th>
                                    <th>CA</th>
                                    <th>Taux</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentReservations as $reservation)
                                    @php
                                        $reservationRevenue = (float) ($reservation->paid_amount ?? 0);
                                        $rate = (float) ($agency->default_commission_rate ?? 0);
                                    @endphp
                                    <tr>
                                        <td>#{{ $reservation->id }}</td>
                                        <td>{{ number_format($reservationRevenue, 0, ',', ' ') }} DH</td>
                                        <td>{{ $rate ? number_format($rate, 2, ',', ' ') . '%' : '?' }}</td>
                                        <td>{{ $rate ? number_format($reservationRevenue * ($rate / 100), 0, ',', ' ') . ' DH' : '?' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Aucune donnée de commission.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


