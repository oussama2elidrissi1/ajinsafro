@extends('layouts.admin-v6')

@section('title', 'Mes commissions')
@section('page_title', 'Commissions')

@push('styles')
    <style>
        .commission-shell { display: grid; gap: 1.25rem; }
        .commission-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; }
        .commission-kpi { background: #fff; border: 1px solid #e5eef5; border-radius: 18px; padding: 1.1rem 1.2rem; box-shadow: 0 12px 30px rgba(15, 35, 95, .06); }
        .commission-kpi__label { color: #6b7280; font-size: .78rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        .commission-kpi__value { color: #0e3a5a; font-size: 1.8rem; font-weight: 800; margin-top: .45rem; }
        .commission-panel { background: #fff; border: 1px solid #e5eef5; border-radius: 20px; padding: 1.3rem; box-shadow: 0 12px 28px rgba(15, 35, 95, .05); }
        .commission-status { display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .32rem .7rem; font-size: .78rem; font-weight: 700; }
        .commission-status--estimated { background: #fff7ed; color: #c2410c; }
        .commission-status--confirmed { background: #eff6ff; color: #1d4ed8; }
        .commission-status--payable { background: #ecfeff; color: #0f766e; }
        .commission-status--paid { background: #ecfdf3; color: #15803d; }
        .commission-status--cancelled, .commission-status--reversed { background: #fef2f2; color: #b91c1c; }

        /* Fix: normalize pagination arrow size and prevent layout blowout */
        .agent-commissions-page nav[role="navigation"] svg,
        .agent-commissions-page .pagination svg {
            width: 16px !important;
            height: 16px !important;
        }
        .agent-commissions-page nav[role="navigation"],
        .agent-commissions-page .pagination {
            margin-bottom: 0;
            flex-wrap: wrap;
            line-height: 1;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-3 agent-commissions-page">
        <div class="commission-shell">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h2 class="mb-1">Commissions agents</h2>
                    <p class="text-muted mb-0">
                        @if($isManagerScope)
                            Vue equipe sur les commissions de votre point de vente.
                        @else
                            Suivi en temps reel de vos commissions et de leur cycle de vie.
                        @endif
                    </p>
                </div>
            </div>

            <div class="commission-kpis">
                <div class="commission-kpi"><div class="commission-kpi__label">Commission du mois</div><div class="commission-kpi__value">{{ number_format((float) $kpis['month_total'], 2, ',', ' ') }} DH</div></div>
                <div class="commission-kpi"><div class="commission-kpi__label">Commission confirmee</div><div class="commission-kpi__value">{{ number_format((float) $kpis['confirmed_total'], 2, ',', ' ') }} DH</div></div>
                <div class="commission-kpi"><div class="commission-kpi__label">Commission en attente</div><div class="commission-kpi__value">{{ number_format((float) $kpis['pending_total'], 2, ',', ' ') }} DH</div></div>
                <div class="commission-kpi"><div class="commission-kpi__label">Commission payable</div><div class="commission-kpi__value">{{ number_format((float) $kpis['payable_total'], 2, ',', ' ') }} DH</div></div>
                <div class="commission-kpi"><div class="commission-kpi__label">Commission payee</div><div class="commission-kpi__value">{{ number_format((float) $kpis['paid_total'], 2, ',', ' ') }} DH</div></div>
                <div class="commission-kpi"><div class="commission-kpi__label">Reservations vendues</div><div class="commission-kpi__value">{{ number_format((int) $kpis['sold_count'], 0, ',', ' ') }}</div></div>
            </div>

            <div class="commission-panel">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Mois</label>
                        <input type="month" name="month" class="form-control" value="{{ $filters['month'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Statut commission</label>
                        <select name="commission_status" class="form-select">
                            <option value="">Tous</option>
                            @foreach([\App\Models\AgentCommissionEntry::STATUS_ESTIMATED => 'Estimee', \App\Models\AgentCommissionEntry::STATUS_CONFIRMED => 'Confirmee', \App\Models\AgentCommissionEntry::STATUS_PAYABLE => 'Payable', \App\Models\AgentCommissionEntry::STATUS_PAID => 'Payee', \App\Models\AgentCommissionEntry::STATUS_CANCELLED => 'Annulee', \App\Models\AgentCommissionEntry::STATUS_REVERSED => 'Reversee'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['commission_status'] ?? null) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Voyage</label>
                        <select name="voyage_id" class="form-select">
                            <option value="">Tous</option>
                            @foreach($voyages as $voyage)
                                <option value="{{ $voyage->id }}" @selected((int) ($filters['voyage_id'] ?? 0) === (int) $voyage->id)>{{ $voyage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date depart</label>
                        <input type="date" name="departure_date" class="form-control" value="{{ $filters['departure_date'] ?? '' }}">
                    </div>
                    <div class="col-md-1 d-grid">
                        <button class="btn btn-primary">Filtrer</button>
                    </div>
                </form>
            </div>

            <div class="commission-panel p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Date</th>
                                <th>Voyage</th>
                                <th>Depart</th>
                                <th>Client</th>
                                <th>Montant reservation</th>
                                <th>Commission</th>
                                <th>Statut reservation</th>
                                <th>Statut paiement</th>
                                <th>Statut commission</th>
                                <th class="text-end px-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $entry)
                                @php
                                    $statusClass = 'commission-status--' . $entry->commission_status;
                                    $departureDate = $entry->reservation?->departure?->start_date?->format('d/m/Y') ?? $entry->travelDate?->date?->format('d/m/Y') ?? '�?"';
                                @endphp
                                <tr>
                                    <td class="px-3">{{ optional($entry->calculated_at)->format('d/m/Y') }}</td>
                                    <td>{{ $entry->voyage?->name ?: 'Voyage non renseigne' }}</td>
                                    <td>{{ $departureDate }}</td>
                                    <td>{{ $entry->client_name ?: 'Client non renseigne' }}</td>
                                    <td>{{ number_format((float) $entry->reservation_total, 2, ',', ' ') }} DH</td>
                                    <td class="fw-semibold">{{ number_format((float) $entry->commission_total, 2, ',', ' ') }} DH</td>
                                    <td>{{ $entry->reservation?->statusLabelFr() ?? ucfirst((string) $entry->reservation_status) }}</td>
                                    <td>{{ $entry->reservation?->paymentStatusLabelFr() ?? ucfirst((string) $entry->payment_status) }}</td>
                                    <td><span class="commission-status {{ $statusClass }}">{{ $entry->statusLabelFr() }}</span></td>
                                    <td class="text-end px-3">
                                        <a href="{{ route('admin.agent.commissions.show', $entry) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">Aucune commission a afficher.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $entries->links() }}
        </div>
    </div>
@endsection

