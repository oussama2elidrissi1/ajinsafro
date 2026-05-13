@extends('layouts.admin-v2')

@section('title', 'Commissions')

@push('styles')
    <style>
        .commission-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .commission-kpi { background: #fff; border: 1px solid #e6edf5; border-radius: 18px; padding: 1.2rem; box-shadow: 0 12px 30px rgba(19, 38, 77, .06); }
        .commission-kpi__label { color: #64748b; font-size: .8rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        .commission-kpi__value { color: #0f172a; font-size: 1.8rem; font-weight: 800; margin-top: .4rem; }
        .commission-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: .32rem .72rem; font-size: .78rem; font-weight: 700; }
        .commission-badge--estimated { background: #fff7ed; color: #c2410c; }
        .commission-badge--confirmed { background: #eff6ff; color: #1d4ed8; }
        .commission-badge--payable { background: #ecfeff; color: #0f766e; }
        .commission-badge--paid { background: #ecfdf3; color: #15803d; }
        .commission-badge--cancelled, .commission-badge--reversed { background: #fef2f2; color: #b91c1c; }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Ledger des commissions agents</h4>
                <p class="text-muted mb-0">Pilotage finance, suivi des statuts et actions de paiement.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.finance.commissions.export.excel', request()->query()) }}" class="btn btn-outline-primary">Export Excel</a>
                <a href="{{ route('admin.finance.commissions.export.pdf', request()->query()) }}" class="btn btn-primary">Export PDF</a>
            </div>
        </div>

        <div class="commission-kpis mb-4">
            <div class="commission-kpi"><div class="commission-kpi__label">Total a payer</div><div class="commission-kpi__value">{{ number_format((float) $kpis['payable_total'], 2, ',', ' ') }} DH</div></div>
            <div class="commission-kpi"><div class="commission-kpi__label">Total paye</div><div class="commission-kpi__value">{{ number_format((float) $kpis['paid_total'], 2, ',', ' ') }} DH</div></div>
            <div class="commission-kpi"><div class="commission-kpi__label">Total en attente</div><div class="commission-kpi__value">{{ number_format((float) $kpis['pending_total'], 2, ',', ' ') }} DH</div></div>
            <div class="commission-kpi"><div class="commission-kpi__label">Annule / reverse</div><div class="commission-kpi__value">{{ number_format((float) $kpis['cancelled_total'], 2, ',', ' ') }} DH</div></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-2"><label class="form-label">Mois</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] ?? '' }}"></div>
                    <div class="col-md-2">
                        <label class="form-label">Statut commission</label>
                        <select name="commission_status" class="form-select">
                            <option value="">Tous</option>
                            @foreach([\App\Models\AgentCommissionEntry::STATUS_ESTIMATED => 'Estimee', \App\Models\AgentCommissionEntry::STATUS_CONFIRMED => 'Confirmee', \App\Models\AgentCommissionEntry::STATUS_PAYABLE => 'Payable', \App\Models\AgentCommissionEntry::STATUS_PAID => 'Payee', \App\Models\AgentCommissionEntry::STATUS_CANCELLED => 'Annulee', \App\Models\AgentCommissionEntry::STATUS_REVERSED => 'Reversee'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['commission_status'] ?? null) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Agent</label>
                        <select name="agent_id" class="form-select">
                            <option value="">Tous</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" @selected((int) ($filters['agent_id'] ?? 0) === (int) $agent->id)>{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Point de vente</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Tous</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) ($filters['branch_id'] ?? 0) === (int) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Agence</label>
                        <select name="agency_type" class="form-select">
                            <option value="">Toutes</option>
                            @foreach(\App\Models\Branch::agencyTypeLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['agency_type'] ?? null) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Voyage</label>
                        <select name="voyage_id" class="form-select">
                            <option value="">Tous</option>
                            @foreach($voyages as $voyage)
                                <option value="{{ $voyage->id }}" @selected((int) ($filters['voyage_id'] ?? 0) === (int) $voyage->id)>{{ $voyage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label">Date depart</label><input type="date" name="departure_date" class="form-control" value="{{ $filters['departure_date'] ?? '' }}"></div>
                    <div class="col-md-2 d-grid"><button class="btn btn-primary">Filtrer</button></div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="mb-3">Top agents du mois</h5>
                <div class="row g-3">
                    @forelse($kpis['top_agents'] as $agentRow)
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="fw-semibold">{{ $agentRow->agent?->name ?: 'Agent supprime' }}</div>
                                <div class="text-muted small mt-1">{{ number_format((float) $agentRow->total_amount, 2, ',', ' ') }} DH</div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted">Aucun agent sur le mois en cours.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">Date</th>
                            <th>Agent</th>
                            <th>Voyage</th>
                            <th>Depart</th>
                            <th>Client</th>
                            <th>Reservation</th>
                            <th>Commission</th>
                            <th>Statut reservation</th>
                            <th>Statut paiement</th>
                            <th>Statut commission</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            @php $departureDate = $entry->reservation?->departure?->start_date?->format('d/m/Y') ?? $entry->travelDate?->date?->format('d/m/Y') ?? '—'; @endphp
                            <tr>
                                <td class="px-3">{{ optional($entry->calculated_at)->format('d/m/Y') }}</td>
                                <td>{{ $entry->agent?->name ?: 'Agent non renseigne' }}</td>
                                <td>{{ $entry->voyage?->name ?: 'Voyage non renseigne' }}</td>
                                <td>{{ $departureDate }}</td>
                                <td>{{ $entry->client_name ?: 'Client non renseigne' }}</td>
                                <td>{{ number_format((float) $entry->reservation_total, 2, ',', ' ') }} DH</td>
                                <td class="fw-semibold">{{ number_format((float) $entry->commission_total, 2, ',', ' ') }} DH</td>
                                <td>{{ $entry->reservation?->statusLabelFr() ?? ucfirst((string) $entry->reservation_status) }}</td>
                                <td>{{ $entry->reservation?->paymentStatusLabelFr() ?? ucfirst((string) $entry->payment_status) }}</td>
                                <td><span class="commission-badge commission-badge--{{ $entry->commission_status }}">{{ $entry->statusLabelFr() }}</span></td>
                                <td class="text-end px-3">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                        <a href="{{ route('admin.finance.commissions.show', $entry) }}" class="btn btn-sm btn-outline-secondary">Voir</a>
                                        @if($entry->commission_status === \App\Models\AgentCommissionEntry::STATUS_ESTIMATED)
                                            <form method="POST" action="{{ route('admin.finance.commissions.confirm', $entry) }}">@csrf<button class="btn btn-sm btn-outline-primary">Confirmer</button></form>
                                        @endif
                                        @if(in_array($entry->commission_status, [\App\Models\AgentCommissionEntry::STATUS_ESTIMATED, \App\Models\AgentCommissionEntry::STATUS_CONFIRMED], true))
                                            <form method="POST" action="{{ route('admin.finance.commissions.payable', $entry) }}">@csrf<button class="btn btn-sm btn-outline-info">Payable</button></form>
                                        @endif
                                        @if($entry->commission_status !== \App\Models\AgentCommissionEntry::STATUS_PAID)
                                            <form method="POST" action="{{ route('admin.finance.commissions.paid', $entry) }}">@csrf<button class="btn btn-sm btn-outline-success">Payer</button></form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">Aucune commission a afficher.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $entries->links() }}
        </div>
    </div>
@endsection
