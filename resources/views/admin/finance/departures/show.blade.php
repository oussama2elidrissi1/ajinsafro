@extends('layouts.admin-v6')

@section('title', 'Detail financier depart')

@section('content')
    @php
        $departure = $data['departure'];
        $summary = $data['summary'];
    @endphp
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Detail financier du depart DEP-{{ $departure->id }}</h4>
                <p class="text-muted mb-0">{{ $data['voyage']?->name ?: 'Voyage non renseigne' }} - {{ $departure->start_date?->format('d/m/Y') ?: '-' }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.finance.departures.index') }}" class="btn btn-outline-secondary">Retour</a>
                @can('departures_finance.manage_charges')
                    <a href="{{ route('admin.finance.departures.charges.create', $departure) }}" class="btn btn-primary">Ajouter charge</a>
                @endcan
                @can('departures_finance.export')
                    <a href="{{ route('admin.finance.departures.pdf', $departure) }}" class="btn btn-outline-danger">Telecharger PDF</a>
                    <a href="{{ route('admin.finance.departures.print', $departure) }}" target="_blank" class="btn btn-outline-dark">Imprimer</a>
                    <a href="{{ route('admin.finance.departures.excel', $departure) }}" class="btn btn-outline-success">Excel</a>
                @endcan
            </div>
        </div>

        <div class="row g-3 mb-4">
            @foreach([
                'Voyageurs' => $summary['travelers_count'],
                'Dossiers' => $summary['reservations_count'],
                'Total entrees' => number_format((float) $summary['total_entries'], 2, ',', ' ').' DH',
                'Total sorties' => number_format((float) $summary['total_charges'], 2, ',', ' ').' DH',
                'Solde' => number_format((float) $summary['balance'], 2, ',', ' ').' DH',
                'Rentable' => $summary['is_profitable'] ? 'Oui' : 'Non',
            ] as $label => $value)
                <div class="col-md-2 col-sm-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase fw-semibold">{{ $label }}</div>
                            <div class="fs-5 fw-bold mt-1">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Entrees automatiques</h5>
                <span class="text-muted small">Depuis les paiements des reservations confirmees</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th class="px-3">Mode</th><th class="text-end">Dossiers</th><th class="text-end">Personnes</th><th class="text-end px-3">Montant</th></tr></thead>
                    <tbody>
                        @foreach($data['entries'] as $entry)
                            <tr>
                                <td class="px-3">{{ $entry['label'] }}</td>
                                <td class="text-end">{{ $entry['dossiers'] }}</td>
                                <td class="text-end">{{ $entry['people'] }}</td>
                                <td class="text-end px-3 fw-semibold">{{ number_format((float) $entry['amount'], 2, ',', ' ') }} DH</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sorties / charges</h5>
                @can('departures_finance.manage_charges')
                    <a href="{{ route('admin.finance.departures.charges.create', $departure) }}" class="btn btn-sm btn-primary">Ajouter charge</a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">Type</th><th>Titre</th><th>Fournisseur</th><th>Mode</th><th>Statut</th><th class="text-end">Montant</th><th>Justificatif</th><th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($charges as $charge)
                            <tr>
                                <td class="px-3">{{ $charge->type?->name ?: 'Autre' }}</td>
                                <td>{{ $charge->title }}</td>
                                <td>{{ $charge->supplier_name ?: '-' }}</td>
                                <td>{{ $paymentMethodLabels[$charge->payment_method] ?? $charge->payment_method }}</td>
                                <td>{{ $paymentStatusLabels[$charge->payment_status] ?? $charge->payment_status }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $charge->amount, 2, ',', ' ') }} {{ $charge->currency }}</td>
                                <td>
                                    @if($charge->attachment)
                                        <a href="{{ route('admin.finance.departures.charges.attachment', [$departure, $charge]) }}" target="_blank">Ouvrir</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    @can('departures_finance.manage_charges')
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('admin.finance.departures.charges.edit', [$departure, $charge]) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                            <form method="POST" action="{{ route('admin.finance.departures.charges.destroy', [$departure, $charge]) }}" onsubmit="return confirm('Supprimer cette charge ?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                                            </form>
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-5 text-muted">Aucune charge saisie pour ce depart.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
