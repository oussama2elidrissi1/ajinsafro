@extends('layouts.admin-v6')

@section('title', 'Projets de voyage')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1">Projets de voyage</h4>
            <p class="text-muted mb-0 small">Un projet financier correspond a un depart reel. Ventes, encaissements, charges et marge sont calcules separement.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.finance.control.exports.index') }}" class="btn btn-sm btn-outline-secondary">Exports</a>
            <a href="{{ route('admin.finance.control.travel-expenses.create') }}" class="btn btn-sm btn-primary">Ajouter une charge</a>
        </div>
    </div>

    @include('admin.finance.control.partials._flash')

    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-3">
        @include('admin.finance.control.partials._kpi', ['label' => 'CA vendu', 'value' => number_format($totals['sold_amount'], 2, ',', ' ').' DH'])
        @include('admin.finance.control.partials._kpi', ['label' => 'Encaisse', 'value' => number_format($totals['collected_amount'], 2, ',', ' ').' DH'])
        @include('admin.finance.control.partials._kpi', ['label' => 'Reste clients', 'value' => number_format($totals['client_remaining'], 2, ',', ' ').' DH'])
        @include('admin.finance.control.partials._kpi', ['label' => 'Charges reelles', 'value' => number_format($totals['real_charges'], 2, ',', ' ').' DH'])
        @include('admin.finance.control.partials._kpi', ['label' => 'Reste fournisseurs', 'value' => number_format($totals['supplier_remaining'], 2, ',', ' ').' DH'])
        @include('admin.finance.control.partials._kpi', ['label' => 'Marge reelle', 'value' => number_format($totals['real_margin'], 2, ',', ' ').' DH', 'tone' => 'auto', 'raw' => $totals['real_margin'], 'hint' => $totals['margin_rate'].' % de marge'])
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Du</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Au</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted">Voyage</label>
                    <select name="voyage_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach ($voyages as $voyage)
                            <option value="{{ $voyage->id }}" @selected($filters['voyage_id'] === $voyage->id)>{{ $voyage->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted">Agence</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Statut depart</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        @foreach (array_unique($departureStatuses) as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Rentabilite</label>
                    <select name="profitability" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="rentable" @selected($filters['profitability'] === 'rentable')>Rentable</option>
                        <option value="deficitaire" @selected($filters['profitability'] === 'deficitaire')>Deficitaire</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Recherche voyage</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] }}" placeholder="Nom du voyage">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button class="btn btn-sm btn-primary">Filtrer</button>
                    <a href="{{ route('admin.finance.control.travel-projects.index') }}" class="btn btn-sm btn-outline-secondary">Reinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">Voyage</th>
                        <th>Depart</th>
                        <th class="text-end">Dossiers</th>
                        <th class="text-end">Voyageurs</th>
                        <th class="text-end">CA vendu</th>
                        <th class="text-end">Encaisse</th>
                        <th class="text-end">Reste clients</th>
                        <th class="text-end">Ch. prevues</th>
                        <th class="text-end">Ch. reelles</th>
                        <th class="text-end">Ch. payees</th>
                        <th class="text-end">Reste fourn.</th>
                        <th class="text-end">Marge prev.</th>
                        <th class="text-end">Marge reelle</th>
                        <th class="text-end">Taux</th>
                        <th>Statut</th>
                        <th class="text-end px-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($departure = $row['departure'])
                        <tr>
                            <td class="px-3">{{ $departure->voyage?->name ?: 'Voyage non renseigne' }}</td>
                            <td class="text-nowrap">{{ $departure->start_date?->format('d/m/Y') ?: '-' }}</td>
                            <td class="text-end">{{ $row['reservations_count'] }}</td>
                            <td class="text-end">{{ $row['travelers_count'] }}</td>
                            <td class="text-end">{{ number_format($row['sold_amount'], 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($row['collected_amount'], 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($row['client_remaining'], 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($row['planned_charges'], 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($row['real_charges'], 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($row['paid_charges'], 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($row['supplier_remaining'], 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($row['planned_margin'], 2, ',', ' ') }}</td>
                            <td class="text-end fw-semibold {{ $row['real_margin'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['real_margin'], 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($row['margin_rate'], 2, ',', ' ') }} %</td>
                            <td>
                                <span class="badge {{ $row['is_profitable'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $row['is_profitable'] ? 'Rentable' : 'Deficitaire' }}
                                </span>
                            </td>
                            <td class="text-end px-3">
                                <a href="{{ route('admin.finance.control.travel-projects.show', $departure) }}" class="btn btn-sm btn-outline-secondary">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="16" class="text-center text-muted py-5">Aucun projet de voyage pour ces criteres.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $rows->links() }}</div>
</div>
@endsection
