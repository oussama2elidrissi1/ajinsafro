@extends('layouts.admin-v2')

@section('title', "Employés d'agence")

@section('content')
    <x-admin.page-header
        title="Employés des agences"
        subtitle="Annuaire opérationnel des équipes d’agence et de leurs accès éventuels à l’admin."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Employés des agences'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.agency-employees.create') }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-user-plus"></i>
                <span>Nouvel employé</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="aj-filter-grid" style="grid-template-columns:minmax(220px,1.3fr) repeat(5,minmax(0,.8fr)) auto;">
                    <div class="aj-field">
                        <input type="text" name="search" class="aj-control" placeholder="Nom, email, téléphone, poste..." value="{{ $filters['search'] }}">
                    </div>
                    <div class="aj-field">
                        <select name="branch_id" class="aj-control">
                            <option value="">Toutes les agences</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($filters['branchId'] === $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="position" class="aj-control">
                            <option value="">Tous les postes</option>
                            @foreach($positionOptions as $option)
                                <option value="{{ $option }}" @selected($filters['position'] === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="role_name" class="aj-control">
                            <option value="">Tous les rôles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected($filters['roleName'] === $role->name)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="status" class="aj-control">
                            <option value="">Tous les statuts</option>
                            @foreach($statusLabels as $key => $label)
                                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="city" class="aj-control">
                            <option value="">Toutes les villes</option>
                            @foreach($cityOptions as $option)
                                <option value="{{ $option }}" @selected($filters['city'] === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="aj-btn aj-btn-primary">Filtrer</button>
                        <a href="{{ route('admin.agency-employees.index') }}" class="aj-btn aj-btn-soft">Reset</a>
                    </div>
                </div>
            </form>

            @if($employees->isEmpty())
                <x-admin.empty-state
                    title="Aucun employé"
                    message="Aucun employé d’agence ne correspond aux critères actuels."
                    :action-url="route('admin.agency-employees.create')"
                    action-label="Ajouter un employé"
                />
            @else
                <div class="table-responsive">
                    <table class="aj-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Avatar</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Agence</th>
                                <th>Poste</th>
                                <th>Rôle système</th>
                                <th>Statut</th>
                                <th>Réservations</th>
                                <th>Dernière connexion</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $employee)
                                <tr>
                                    <td>
                                        @if($employee->avatar_url)
                                            <img src="{{ $employee->avatar_url }}" alt="{{ $employee->full_name }}" style="width:40px;height:40px;border-radius:12px;object-fit:cover;">
                                        @else
                                            <span class="aj-badge aj-badge-gray">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</span>
                                        @endif
                                    </td>
                                    <td><a href="{{ route('admin.agency-employees.show', $employee) }}">{{ $employee->full_name }}</a></td>
                                    <td>{{ $employee->email ?: '—' }}</td>
                                    <td>{{ $employee->phone ?: '—' }}</td>
                                    <td>{{ $employee->branch?->name ?: '—' }}</td>
                                    <td>{{ $employee->position ?: '—' }}</td>
                                    <td>{{ $employee->user?->roles->pluck('name')->join(', ') ?: '—' }}</td>
                                    <td>{{ $statusLabels[$employee->status] ?? $employee->status }}</td>
                                    <td>{{ $employee->handled_reservations_count }}</td>
                                    <td>{{ $employee->user?->last_login_at?->format('d/m/Y H:i') ?: '—' }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <a href="{{ route('admin.agency-employees.show', $employee) }}" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;">Voir</a>
                                            <a href="{{ route('admin.agency-employees.edit', $employee) }}" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;">Modifier</a>
                                            <form method="POST" action="{{ route('admin.agency-employees.destroy', $employee) }}" onsubmit="return confirm('Supprimer cet employé ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;color:#d92d20;">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-admin.pagination-footer :paginator="$employees" />
            @endif
        </div>
    </div>
@endsection
