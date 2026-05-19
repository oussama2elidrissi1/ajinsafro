@extends('layouts.admin-v6')

@section('title', 'Performance points de vente')

@section('content')
    <x-admin.page-header
        title="Performance points de vente"
        subtitle="Comparatif des rÃ©servations, du chiffre dâ€™affaires et des commissions estimÃ©es par point de vente."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Points de vente', 'url' => route('admin.agencies.index')],
            ['label' => 'Performance'],
        ]"
    />

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="aj-filter-grid" style="grid-template-columns:repeat(5,minmax(0,1fr)) auto;">
                    <div class="aj-field">
                        <select name="period" class="aj-control">
                            <option value="7" @selected($filters['period'] === '7')>7 jours</option>
                            <option value="30" @selected($filters['period'] === '30')>30 jours</option>
                            <option value="90" @selected($filters['period'] === '90')>90 jours</option>
                            <option value="365" @selected($filters['period'] === '365')>12 mois</option>
                        </select>
                    </div>
                    <div class="aj-field">
                        <select name="agency_id" class="aj-control">
                            <option value="">Tous les points de vente</option>
                            @foreach($agencies as $agency)
                                <option value="{{ $agency->id }}" @selected($filters['agencyId'] === $agency->id)>{{ $agency->name }}</option>
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
                    <div class="aj-field">
                        <input type="text" name="prestation_type" class="aj-control" placeholder="Type produit" value="{{ $filters['prestationType'] }}">
                    </div>
                    <div></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="aj-btn aj-btn-primary">Filtrer</button>
                        <a href="{{ route('admin.agencies.performance') }}" class="aj-btn aj-btn-soft">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Comparatif par point de vente</h5>
                    <div class="table-responsive">
                        <table class="aj-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Point de vente</th>
                                    <th>RÃ©servations</th>
                                    <th>ValidÃ©es</th>
                                    <th>En attente</th>
                                    <th>AnnulÃ©es</th>
                                    <th>CA</th>
                                    <th>Commission</th>
                                    <th>Conversion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr>
                                        <td><a href="{{ route('admin.agencies.show', $row['agency']) }}">{{ $row['agency']->name }}</a></td>
                                        <td>{{ $row['total'] }}</td>
                                        <td>{{ $row['confirmed'] }}</td>
                                        <td>{{ $row['pending'] }}</td>
                                        <td>{{ $row['cancelled'] }}</td>
                                        <td>{{ number_format($row['revenue'], 0, ',', ' ') }} DH</td>
                                        <td>{{ number_format($row['estimated_commission'], 0, ',', ' ') }} DH</td>
                                        <td>{{ number_format($row['conversion_rate'], 1, ',', ' ') }}%</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted">Aucune donnÃ©e disponible.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Top employÃ©s</h5>
                    <div class="d-flex flex-column gap-3">
                        @forelse($topEmployees as $row)
                            <div class="d-flex justify-content-between align-items-center border rounded-3 px-3 py-2">
                                <div>
                                    <div class="fw-semibold">{{ $row['employee']->full_name }}</div>
                                    <div class="text-muted small">
                                        {{ $row['employee']->branch?->name ?: 'â€”' }} Â· {{ $row['employee']->position ?: 'â€”' }}
                                    </div>
                                </div>
                                <span class="aj-badge aj-badge-info">{{ $row['count'] }} rÃ©sa</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Aucun employÃ© disponible pour cette pÃ©riode.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

