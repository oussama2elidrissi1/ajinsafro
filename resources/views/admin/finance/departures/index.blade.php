@extends('layouts.admin-v6')

@section('title', 'Finances departs')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Finances par depart</h4>
                <p class="text-muted mb-0">Entrees issues des paiements de reservations confirmees, sorties issues des charges saisies.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Voyage</label>
                        <select name="voyage_id" class="form-select">
                            <option value="">Tous</option>
                            @foreach($voyages as $voyage)
                                <option value="{{ $voyage->id }}" @selected((int) ($filters['voyage_id'] ?? 0) === (int) $voyage->id)>{{ $voyage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Date depart</label><input type="date" name="departure_date" class="form-control" value="{{ $filters['departure_date'] ?? '' }}"></div>
                    <div class="col-md-2"><label class="form-label">Mois</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] ?? '' }}"></div>
                    <div class="col-md-2">
                        <label class="form-label">Statut</label>
                        <select name="profitability" class="form-select">
                            <option value="">Tous</option>
                            <option value="rentable" @selected(($filters['profitability'] ?? '') === 'rentable')>Rentable</option>
                            <option value="non_rentable" @selected(($filters['profitability'] ?? '') === 'non_rentable')>Non rentable</option>
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Recherche</label><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Voyage ou depart"></div>
                    <div class="col-md-1 d-grid"><button class="btn btn-primary">Filtrer</button></div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">Voyage</th>
                            <th>Date depart</th>
                            <th>Ref.</th>
                            <th class="text-end">Voyageurs</th>
                            <th class="text-end">Dossiers</th>
                            <th class="text-end">Entrees</th>
                            <th class="text-end">Charges</th>
                            <th class="text-end">Solde</th>
                            <th>Rentable</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php($departure = $row['departure'])
                            <tr>
                                <td class="px-3 fw-semibold">{{ $departure->voyage?->name ?: 'Voyage non renseigne' }}</td>
                                <td>{{ $departure->start_date?->format('d/m/Y') ?: '-' }}</td>
                                <td>DEP-{{ $departure->id }}</td>
                                <td class="text-end">{{ $row['travelers_count'] }}</td>
                                <td class="text-end">{{ $row['reservations_count'] }}</td>
                                <td class="text-end">{{ number_format((float) $row['total_entries'], 2, ',', ' ') }} DH</td>
                                <td class="text-end">{{ number_format((float) $row['total_charges'], 2, ',', ' ') }} DH</td>
                                <td class="text-end fw-semibold {{ $row['balance'] > 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $row['balance'], 2, ',', ' ') }} DH</td>
                                <td><span class="badge {{ $row['is_profitable'] ? 'bg-success' : 'bg-danger' }}">{{ $row['is_profitable'] ? 'Oui' : 'Non' }}</span></td>
                                <td class="text-end px-3">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                        <a href="{{ route('admin.finance.departures.show', $departure) }}" class="btn btn-sm btn-outline-secondary">Voir</a>
                                        @can('departures_finance.manage_charges')
                                            <a href="{{ route('admin.finance.departures.charges.create', $departure) }}" class="btn btn-sm btn-outline-primary">Ajouter charge</a>
                                        @endcan
                                        @can('departures_finance.export')
                                            <a href="{{ route('admin.finance.departures.pdf', $departure) }}" class="btn btn-sm btn-outline-danger">PDF</a>
                                            <a href="{{ route('admin.finance.departures.print', $departure) }}" target="_blank" class="btn btn-sm btn-outline-dark">Imprimer</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center py-5 text-muted">Aucun depart financier a afficher.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $rows->links() }}</div>
    </div>
@endsection
