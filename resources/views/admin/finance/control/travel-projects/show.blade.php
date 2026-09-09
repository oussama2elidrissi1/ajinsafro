@extends('layouts.admin-v6')

@section('title', 'Fiche financiere projet')

@php
    $activeTab = in_array(request('tab'), ['overview', 'collections', 'charges', 'suppliers', 'documents', 'result'], true)
        ? request('tab')
        : 'overview';
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' DH';
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1">{{ $departure->voyage?->name ?: 'Voyage non renseigne' }}</h4>
            <p class="text-muted mb-0 small">
                Depart DEP-{{ $departure->id }} du {{ $departure->start_date?->format('d/m/Y') ?: '-' }}
                @if ($departure->end_date) au {{ $departure->end_date->format('d/m/Y') }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.finance.control.travel-projects.index') }}" class="btn btn-sm btn-outline-secondary">Retour</a>
            <a href="{{ route('admin.finance.control.travel-expenses.create', ['departure_id' => $departure->id]) }}" class="btn btn-sm btn-primary">Ajouter une charge</a>
        </div>
    </div>

    @include('admin.finance.control.partials._flash')

    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-3">
        @include('admin.finance.control.partials._kpi', ['label' => 'Ventes totales', 'value' => $money($summary['sold_amount'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Encaisse', 'value' => $money($summary['collected_amount'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Reste clients', 'value' => $money($summary['client_remaining'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Charges prevues', 'value' => $money($summary['planned_charges'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Charges reelles', 'value' => $money($summary['real_charges'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Charges payees', 'value' => $money($summary['paid_charges'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Reste fournisseurs', 'value' => $money($summary['supplier_remaining'])])
        @include('admin.finance.control.partials._kpi', ['label' => 'Marge previsionnelle', 'value' => $money($summary['planned_margin']), 'tone' => 'auto', 'raw' => $summary['planned_margin']])
        @include('admin.finance.control.partials._kpi', ['label' => 'Marge reelle', 'value' => $money($summary['real_margin']), 'tone' => 'auto', 'raw' => $summary['real_margin']])
        @include('admin.finance.control.partials._kpi', ['label' => 'Taux de marge', 'value' => number_format($summary['margin_rate'], 2, ',', ' ').' %', 'tone' => 'auto', 'raw' => $summary['margin_rate']])
    </div>

    <ul class="nav nav-tabs mb-0" role="tablist">
        @foreach ([
            'overview' => 'Vue d\'ensemble',
            'collections' => 'Encaissements clients',
            'charges' => 'Charges',
            'suppliers' => 'Fournisseurs',
            'documents' => 'Justificatifs',
            'result' => 'Resultat',
        ] as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === $key ? 'active' : '' }}"
                   href="{{ route('admin.finance.control.travel-projects.show', [$departure, 'tab' => $key]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    <div class="card border-0 shadow-sm rounded-top-0">
        <div class="card-body">

            {{-- 1. Vue d'ensemble --}}
            @if ($activeTab === 'overview')
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h6 class="text-muted text-uppercase small mb-2">Cycle client</h6>
                        <table class="table table-sm mb-0">
                            <tr><td>CA vendu</td><td class="text-end">{{ $money($summary['sold_amount']) }}</td></tr>
                            <tr><td>Encaisse</td><td class="text-end">{{ $money($summary['collected_amount']) }}</td></tr>
                            <tr><td>Reste a encaisser</td><td class="text-end fw-semibold">{{ $money($summary['client_remaining']) }}</td></tr>
                            <tr><td>Dossiers / voyageurs</td><td class="text-end">{{ $summary['reservations_count'] }} / {{ $summary['travelers_count'] }}</td></tr>
                        </table>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="text-muted text-uppercase small mb-2">Cycle fournisseur</h6>
                        <table class="table table-sm mb-0">
                            <tr><td>Charges prevues</td><td class="text-end">{{ $money($summary['planned_charges']) }}</td></tr>
                            <tr><td>Charges reelles</td><td class="text-end">{{ $money($summary['real_charges']) }}</td></tr>
                            <tr><td>Charges payees</td><td class="text-end">{{ $money($summary['paid_charges']) }}</td></tr>
                            <tr><td>Reste du aux fournisseurs</td><td class="text-end fw-semibold">{{ $money($summary['supplier_remaining']) }}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="alert alert-light border mt-4 mb-0 small text-muted">
                    Le montant encaisse n'est pas un benefice : il inclut des sommes destinees a payer les fournisseurs.
                    La rentabilite du projet se lit sur la marge reelle, onglet Resultat.
                </div>
            @endif

            {{-- 2. Encaissements clients (lecture seule des paiements existants) --}}
            @if ($activeTab === 'collections')
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reservation</th>
                                <th>Client</th>
                                <th class="text-end">Total dossier</th>
                                <th class="text-end">Encaisse</th>
                                <th class="text-end">Reste</th>
                                <th>Agence</th>
                                <th>Detail des paiements</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reservations as $reservation)
                                @php
                                    $paid = round((float) $reservation->payments->sum('amount'), 2);
                                    $total = (float) ($reservation->total_amount ?? 0);
                                @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $reservation->dossier_number ?: 'RES-'.$reservation->id }}</td>
                                    <td>{{ trim($reservation->client_first_name.' '.$reservation->client_last_name) ?: '-' }}</td>
                                    <td class="text-end">{{ $money($total) }}</td>
                                    <td class="text-end">{{ $money($paid) }}</td>
                                    <td class="text-end fw-semibold">{{ $money($total - $paid) }}</td>
                                    <td>{{ $reservation->branch?->name ?: '-' }}</td>
                                    <td>
                                        @forelse ($reservation->payments as $payment)
                                            <div class="d-flex flex-wrap gap-2 align-items-center border-bottom py-1">
                                                <span class="text-nowrap">{{ $payment->payment_date?->format('d/m/Y') ?: '-' }}</span>
                                                <span class="fw-semibold">{{ $money($payment->amount) }}</span>
                                                <span class="badge bg-light text-body">{{ $payment->payment_method }}</span>
                                                <span class="text-muted small">par {{ $payment->creator?->name ?: 'n/c' }}</span>
                                                @if ($payment->proof_file || $payment->financialDocuments->isNotEmpty())
                                                    <span class="badge bg-success-subtle text-success">Justificatif</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">Sans justificatif</span>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-muted small">Aucun paiement enregistre</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Aucune reservation valide sur ce depart.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mt-3 mb-0">
                    Ces paiements proviennent du workspace reservations. Ils sont affiches ici en lecture seule, sans ressaisie.
                </p>
            @endif

            {{-- 3. Charges du voyage --}}
            @if ($activeTab === 'charges')
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Categorie</th>
                                <th>Libelle</th>
                                <th>Fournisseur</th>
                                <th class="text-end">Prevu</th>
                                <th class="text-end">Reel</th>
                                <th class="text-end">Paye</th>
                                <th class="text-end">Reste</th>
                                <th>Echeance</th>
                                <th>Statut</th>
                                <th>Justif.</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($charges as $charge)
                                <tr class="{{ $charge->status === \App\Models\DepartureCharge::STATUS_CANCELLED ? 'opacity-50' : '' }}">
                                    <td class="text-nowrap">{{ $charge->charge_date?->format('d/m/Y') ?: '-' }}</td>
                                    <td>{{ $charge->type?->name ?: '-' }}</td>
                                    <td>{{ $charge->title }}</td>
                                    <td>{{ $charge->supplier?->name ?: ($charge->supplier_name ?: '-') }}</td>
                                    <td class="text-end">{{ $money($charge->effective_planned_amount) }}</td>
                                    <td class="text-end">{{ $money($charge->amount) }}</td>
                                    <td class="text-end">{{ $money($charge->paid_amount) }}</td>
                                    <td class="text-end fw-semibold">{{ $money($charge->remaining_amount) }}</td>
                                    <td class="text-nowrap">{{ $charge->due_date?->format('d/m/Y') ?: '-' }}</td>
                                    <td><span class="badge bg-light text-body">{{ $charge->status_label }}</span></td>
                                    <td>
                                        @if ($charge->attachment || $charge->documents->isNotEmpty())
                                            <span class="badge bg-success-subtle text-success">Oui</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Non</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @can('finance.expenses.manage')
                                            <a href="{{ route('admin.finance.control.travel-expenses.edit', $charge) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                            @if ($charge->status !== \App\Models\DepartureCharge::STATUS_CANCELLED)
                                                <form method="POST" action="{{ route('admin.finance.control.travel-expenses.cancel', $charge) }}" class="d-inline"
                                                      onsubmit="return confirm('Annuler cette charge ? Elle sera conservee dans l\'historique.');">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-danger">Annuler</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="12" class="text-center text-muted py-4">Aucune charge saisie sur ce projet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- 4. Fournisseurs --}}
            @if ($activeTab === 'suppliers')
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fournisseur</th>
                                <th class="text-end">Nb charges</th>
                                <th class="text-end">Total facture</th>
                                <th class="text-end">Total paye</th>
                                <th class="text-end">Reste du</th>
                                <th class="text-end">Justificatifs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($suppliers as $line)
                                <tr>
                                    <td>{{ $line['supplier'] }}</td>
                                    <td class="text-end">{{ $line['charges_count'] }}</td>
                                    <td class="text-end">{{ $money($line['billed']) }}</td>
                                    <td class="text-end">{{ $money($line['paid']) }}</td>
                                    <td class="text-end fw-semibold">{{ $money($line['remaining']) }}</td>
                                    <td class="text-end">{{ $line['documents_count'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Aucun fournisseur rattache a ce projet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- 5. Justificatifs --}}
            @if ($activeTab === 'documents')
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th class="text-end">Montant</th>
                                <th>Fournisseur / client</th>
                                <th>Agence</th>
                                <th>Statut</th>
                                <th class="text-end">Fichier</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                <tr>
                                    <td class="text-nowrap">{{ $document->document_date?->format('d/m/Y') ?: '-' }}</td>
                                    <td>{{ $document->type_label }}</td>
                                    <td>{{ $document->reference ?: '-' }}</td>
                                    <td class="text-end">{{ $document->amount !== null ? $money($document->amount) : '-' }}</td>
                                    <td>{{ $document->supplier?->name ?: ($document->client_name ?: '-') }}</td>
                                    <td>{{ $document->branch?->name ?: '-' }}</td>
                                    <td><span class="badge bg-light text-body">{{ $document->status_label }}</span></td>
                                    <td class="text-end">
                                        @if ($document->file_path)
                                            <a href="{{ route('admin.finance.control.documents.download', $document) }}" class="btn btn-sm btn-outline-secondary">Telecharger</a>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">Aucun justificatif rattache a ce projet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('admin.finance.control.documents.index', ['departure_id' => $departure->id]) }}" class="btn btn-sm btn-outline-secondary mt-3">Ouvrir le centre de justificatifs</a>
            @endif

            {{-- 6. Resultat --}}
            @if ($activeTab === 'result')
                <div class="row g-4">
                    <div class="col-lg-7">
                        <table class="table table-sm mb-0">
                            <tr><td>CA vendu (reservations valides)</td><td class="text-end">{{ $money($summary['sold_amount']) }}</td></tr>
                            <tr><td>Charges reelles du projet</td><td class="text-end">- {{ $money($summary['real_charges']) }}</td></tr>
                            <tr class="table-light">
                                <td class="fw-semibold">Marge du projet</td>
                                <td class="text-end fw-semibold {{ $summary['real_margin'] < 0 ? 'text-danger' : 'text-success' }}">{{ $money($summary['real_margin']) }}</td>
                            </tr>
                            <tr><td>Taux de marge</td><td class="text-end">{{ number_format($summary['margin_rate'], 2, ',', ' ') }} %</td></tr>
                        </table>
                    </div>
                    <div class="col-lg-5">
                        <div class="alert alert-light border mb-0 small text-muted">
                            <div class="fw-semibold text-body mb-1">Lecture de la marge</div>
                            La marge compare la vente aux charges reelles du projet. Elle ne depend ni du rythme
                            d'encaissement client ni du rythme de paiement fournisseur : ces deux flux sont suivis
                            separement dans la tresorerie.
                            Les charges de structure de l'agence ne sont pas imputees ici.
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
