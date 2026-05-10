@extends('layouts.admin-v2')

@section('title', 'Agences')

@section('content')
    <x-admin.page-header
        title="Agences"
        subtitle="Pilotage des agences Ajinsafro, de leurs responsables et de leur activité commerciale."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Agences'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.agencies.performance') }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-bar-chart-alt-2"></i>
                <span>Performance</span>
            </a>
            <a href="{{ route('admin.agencies.create') }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-plus"></i>
                <span>Nouvelle agence</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />
    <x-admin.kpi-cards :kpis="$kpis" />

    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="aj-filter-grid" style="grid-template-columns:minmax(220px,1.4fr) repeat(5,minmax(0,.8fr)) auto;">
                    <div class="aj-field">
                        <input type="text" name="search" class="aj-control" placeholder="Nom, code, ville, email..." value="{{ $filters['search'] }}">
                    </div>
                    <div class="aj-field">
                        <select name="city" class="aj-control">
                            <option value="">Toutes les villes</option>
                            @foreach($cityOptions as $option)
                                <option value="{{ $option }}" @selected($filters['city'] === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="country" class="aj-control">
                            <option value="">Tous les pays</option>
                            @foreach($countryOptions as $option)
                                <option value="{{ $option }}" @selected($filters['country'] === $option)>{{ $option }}</option>
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
                        <select name="agency_type" class="aj-control">
                            <option value="">Tous les types</option>
                            @foreach($agencyTypeLabels as $key => $label)
                                <option value="{{ $key }}" @selected($filters['agencyType'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="manager_id" class="aj-control">
                            <option value="">Tous les managers</option>
                            @foreach($managerOptions as $manager)
                                <option value="{{ $manager->id }}" @selected($filters['managerId'] === $manager->id)>{{ $manager->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="aj-btn aj-btn-primary">
                            <i class="bx bx-filter-alt"></i>
                            <span>Filtrer</span>
                        </button>
                        <a href="{{ route('admin.agencies.index') }}" class="aj-btn aj-btn-soft">
                            <i class="bx bx-reset"></i>
                            <span>Reset</span>
                        </a>
                    </div>
                </div>
            </form>

            @if($agencies->isEmpty())
                <x-admin.empty-state
                    title="Aucune agence"
                    message="Aucune agence ne correspond aux filtres actuels."
                    :action-url="route('admin.agencies.create')"
                    action-label="Créer une agence"
                />
            @else
                <div class="table-responsive">
                    <table class="aj-table" style="width:100%;border-collapse:separate;border-spacing:0;">
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Agence</th>
                                <th>Ville</th>
                                <th>Pays</th>
                                <th>Téléphone</th>
                                <th>Email</th>
                                <th>Manager</th>
                                <th>Employés</th>
                                <th>Réservations</th>
                                <th>CA</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agencies as $agency)
                                @php
                                    $statusType = match($agency->status) {
                                        \App\Models\Branch::STATUS_ACTIVE => 'success',
                                        \App\Models\Branch::STATUS_SUSPENDED => 'danger',
                                        default => 'warning',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        @if($agency->logo_url)
                                            <img src="{{ $agency->logo_url }}" alt="{{ $agency->name }}" style="width:42px;height:42px;border-radius:12px;object-fit:cover;">
                                        @else
                                            <span class="aj-badge aj-badge-info" style="display:inline-flex;min-width:42px;justify-content:center;">{{ strtoupper(substr($agency->code, 0, 2)) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.agencies.show', $agency) }}" class="fw-semibold text-decoration-none">{{ $agency->name }}</a>
                                        <div class="text-muted small">{{ $agency->code }} · {{ $agencyTypeLabels[$agency->agency_type] ?? $agency->agency_type }}</div>
                                    </td>
                                    <td>{{ $agency->city ?: '—' }}</td>
                                    <td>{{ $agency->country ?: '—' }}</td>
                                    <td>{{ $agency->phone ?: '—' }}</td>
                                    <td>{{ $agency->email ?: '—' }}</td>
                                    <td>{{ $agency->manager?->name ?: '—' }}</td>
                                    <td>{{ $agency->agency_employees_count }}</td>
                                    <td>{{ $agency->reservations_count }}</td>
                                    <td>{{ number_format((float) ($agency->revenue_total ?? 0), 0, ',', ' ') }} DH</td>
                                    <td><x-admin.badge :type="$statusType" :label="$statusLabels[$agency->status] ?? $agency->status" /></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <form method="POST" action="{{ route('admin.agencies.toggle-status', $agency) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;">
                                                    {{ $agency->status === \App\Models\Branch::STATUS_ACTIVE ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.agencies.show', $agency) }}" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;">Voir</a>
                                            <a href="{{ route('admin.agencies.edit', $agency) }}" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;">Modifier</a>
                                            <form method="POST" action="{{ route('admin.agencies.destroy', $agency) }}" onsubmit="return confirm('Archiver cette agence ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="aj-btn aj-btn-soft" style="min-height:34px;padding:0 10px;font-size:12px;color:#d92d20;">Archiver</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-admin.pagination-footer :paginator="$agencies" />
            @endif
        </div>
    </div>
@endsection
