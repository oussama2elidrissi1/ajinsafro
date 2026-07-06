@extends('layouts.master-ajinsafro')

@section('title', 'Mes reservations')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-list-page {
            padding: 0 18px 28px;
        }

        .aj-agent-list-shell {
            max-width: 1340px;
            margin: 0 auto;
        }

        .aj-agent-list-head {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) auto;
            gap: 20px;
            align-items: center;
            margin-bottom: 18px;
            padding: 24px 26px;
            border-radius: 24px;
            border: 1px solid #d7e4ef;
            background:
                radial-gradient(circle at top right, rgba(255, 122, 26, .10), transparent 30%),
                linear-gradient(135deg, #083b5b 0%, #0b537f 52%, #0f7db4 100%);
            box-shadow: 0 18px 40px rgba(14, 58, 90, .16);
        }

        .aj-agent-list-head::after {
            content: "";
            position: absolute;
            right: -46px;
            bottom: -60px;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .08);
        }

        .aj-agent-list-head > * {
            position: relative;
            z-index: 1;
        }

        .aj-agent-list-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #d9efff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .aj-agent-list-head h1 {
            margin: 0;
            color: #fff;
            font-weight: 800;
            font-size: 34px;
            line-height: 1.05;
        }

        .aj-agent-list-head p {
            margin: 8px 0 0;
            color: #d7e9f8;
            font-size: 14px;
            font-weight: 600;
            max-width: 58ch;
        }

        .aj-agent-list-head .aj-agent-primary-btn {
            align-self: start;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .16);
        }

        .aj-agent-kpi-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .aj-agent-kpi-box {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfe 100%);
            border: 1px solid #dbe7f1;
            border-radius: 18px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
            padding: 16px 18px;
        }

        .aj-agent-kpi-box span {
            display: block;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .aj-agent-kpi-box strong {
            display: block;
            color: #0f172a;
            font-size: 24px;
            font-weight: 800;
            line-height: 1;
        }

        .aj-agent-kpi-box small {
            display: block;
            margin-top: 8px;
            color: #5b708b;
            font-size: 12px;
            font-weight: 600;
        }

        .aj-agent-filter-card {
            background: linear-gradient(180deg, #ffffff 0%, #f9fbfe 100%);
            border: 1px solid #dbe7f1;
            border-radius: 20px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
            padding: 18px;
            margin-bottom: 18px;
        }

        .aj-agent-filter-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .aj-agent-filter-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .aj-agent-filter-head p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .aj-agent-filter-grid {
            display: grid;
            grid-template-columns: minmax(260px, 2.2fr) minmax(150px, 1fr) minmax(170px, 1fr) auto auto;
            gap: 12px;
            align-items: end;
        }

        .aj-agent-field label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            margin-bottom: 6px;
        }

        .aj-agent-field input,
        .aj-agent-field select {
            width: 100%;
            min-height: 44px;
            border: 1px solid #d4e3ef;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
            color: #0f172a;
            background: #fff;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, .03);
        }

        .aj-agent-field input:focus,
        .aj-agent-field select:focus {
            outline: none;
            border-color: #0083c4;
            box-shadow: 0 0 0 4px rgba(0, 131, 196, .10);
        }

        .aj-agent-filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .aj-agent-table-card {
            background: linear-gradient(180deg, #ffffff 0%, #f9fbfe 100%);
            border: 1px solid #dbe7f1;
            border-radius: 20px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
            overflow: hidden;
        }

        .aj-agent-table-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px 12px;
            border-bottom: 1px solid #e7eef5;
        }

        .aj-agent-table-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .aj-agent-table-head p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .aj-agent-table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .aj-agent-res-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 980px;
        }

        .aj-agent-res-table th {
            background: #f4f8fb;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 13px 14px;
            text-align: left;
            border-bottom: 1px solid #e2ebf3;
            font-weight: 800;
        }

        .aj-agent-res-table td {
            padding: 16px 14px;
            border-bottom: 1px solid #edf2f7;
            color: #0f172a;
            font-size: 13px;
            vertical-align: top;
            background: rgba(255, 255, 255, .88);
        }

        .aj-agent-res-table tbody tr:hover td {
            background: #f9fcff;
        }

        .aj-agent-res-table tr:last-child td {
            border-bottom: 0;
        }

        .aj-agent-ref {
            color: #0e3a5a;
            font-weight: 800;
            font-size: 15px;
            line-height: 1.2;
        }

        .aj-agent-voyage-name {
            max-width: 60ch;
            color: #0f172a;
            font-weight: 700;
            line-height: 1.35;
        }

        .aj-agent-muted {
            color: #64748b;
            font-size: 12px;
            line-height: 1.35;
        }

        .aj-agent-row-main {
            color: #0f172a;
            font-weight: 700;
            line-height: 1.3;
        }

        .aj-agent-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .aj-agent-status-pill::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
            opacity: .85;
        }

        .aj-agent-status-pill.is-pending {
            background: #fff4e8;
            color: #c25b06;
            border-color: #fed7aa;
        }

        .aj-agent-status-pill.is-success {
            background: #ecfdf5;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .aj-agent-status-pill.is-danger {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .aj-agent-status-pill.is-neutral {
            background: #edf4fb;
            color: #365472;
            border-color: #d7e4ef;
        }

        .aj-agent-money {
            color: #0e3a5a;
            font-size: 18px;
            font-weight: 800;
            line-height: 1.1;
            white-space: nowrap;
        }

        .aj-agent-open-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 124px;
            min-height: 40px;
            padding: 9px 14px;
            border-radius: 12px;
            border: 1px solid #cfe0ee;
            background: #fff;
            color: #0e3a5a;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition: .18s ease;
        }

        .aj-agent-open-btn:hover {
            border-color: #0083c4;
            background: #eff8fc;
            color: #0074ad;
        }

        .aj-agent-row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: flex-start;
            min-width: 330px;
        }

        .aj-agent-row-actions form {
            margin: 0;
        }

        .aj-agent-row-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 38px;
            padding: 8px 12px;
            border-radius: 12px;
            border: 1px solid #cfe0ee;
            background: #fff;
            color: #0e3a5a;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
            transition: .18s ease;
        }

        .aj-agent-row-btn:hover {
            border-color: #0083c4;
            background: #eff8fc;
            color: #0074ad;
        }

        .aj-agent-row-btn.is-primary {
            border-color: #0b7fc2;
            background: #0b7fc2;
            color: #fff;
            box-shadow: 0 8px 18px rgba(11, 127, 194, .18);
        }

        .aj-agent-row-btn.is-success {
            border-color: #16a34a;
            background: #16a34a;
            color: #fff;
            box-shadow: 0 8px 18px rgba(22, 163, 74, .16);
        }

        .aj-agent-row-btn.is-soft {
            background: #f8fbfe;
        }

        .aj-agent-empty {
            padding: 44px 24px;
            text-align: center;
            color: #64748b;
        }

        .aj-agent-empty h2 {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 20px;
            font-weight: 800;
        }

        .aj-agent-empty p {
            margin: 0 0 18px;
            font-size: 14px;
        }

        .aj-agent-pagination {
            padding: 16px 18px 18px;
            border-top: 1px solid #e7eef5;
            background: rgba(248, 251, 254, .78);
        }

        @media (max-width: 1100px) {
            .aj-agent-kpi-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .aj-agent-filter-grid {
                grid-template-columns: 1fr 1fr;
            }

            .aj-agent-filter-actions {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 700px) {
            .aj-agent-list-page {
                padding: 0 12px 24px;
            }

            .aj-agent-list-head {
                grid-template-columns: 1fr;
                padding: 20px 18px;
            }

            .aj-agent-list-head h1 {
                font-size: 28px;
            }

            .aj-agent-kpi-strip,
            .aj-agent-filter-grid {
                grid-template-columns: 1fr;
            }

            .aj-agent-filter-head,
            .aj-agent-table-head {
                flex-direction: column;
            }

            .aj-agent-filter-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
@endpush

@section('content')
@php
    $pageReservations = $reservations->getCollection();
    $totalRows = $pageReservations->count();
    $pendingRows = $pageReservations->filter(fn ($reservation) => in_array((string) $reservation->status, [
        \App\Models\Reservation::STATUS_PENDING,
        \App\Models\Reservation::STATUS_OPTION,
        \App\Models\Reservation::STATUS_SHARED_ROOM_PENDING,
        \App\Models\Reservation::STATUS_PARTIALLY_PAID,
    ], true))->count();
    $confirmedRows = $pageReservations->filter(fn ($reservation) => in_array((string) $reservation->status, [
        \App\Models\Reservation::STATUS_CONFIRMED,
        \App\Models\Reservation::STATUS_PAID,
    ], true))->count();
    $salesTotal = (float) $pageReservations->sum(fn ($reservation) => (float) ($reservation->total_amount ?? 0));
@endphp
<div class="aj-agent-list-page">
    <div class="aj-agent-list-shell">
        <div class="aj-agent-list-head">
            <div>
                <div class="aj-agent-list-kicker">Pilotage agent</div>
                <h1>Mes reservations</h1>
                <p>Vue de suivi plus nette pour lire les dossiers, distinguer les statuts et ouvrir rapidement les details importants.</p>
            </div>
            <a href="{{ route('agent.catalogue') }}" class="aj-agent-primary-btn">
                <i class="bx bx-map-alt"></i>
                <span>Catalogue</span>
            </a>
        </div>

        <div class="aj-agent-kpi-strip">
            <div class="aj-agent-kpi-box">
                <span>Dossiers affiches</span>
                <strong>{{ number_format($totalRows, 0, ',', ' ') }}</strong>
                <small>Resultat de la page courante</small>
            </div>
            <div class="aj-agent-kpi-box">
                <span>En attente</span>
                <strong>{{ number_format($pendingRows, 0, ',', ' ') }}</strong>
                <small>A suivre en priorite</small>
            </div>
            <div class="aj-agent-kpi-box">
                <span>Confirmees</span>
                <strong>{{ number_format($confirmedRows, 0, ',', ' ') }}</strong>
                <small>Dossiers stabilises</small>
            </div>
            <div class="aj-agent-kpi-box">
                <span>Montant cumule</span>
                <strong>{{ number_format($salesTotal, 0, ',', ' ') }}</strong>
                <small>Valeur estimee sur cette page</small>
            </div>
        </div>

        <form method="GET" action="{{ route('agent.reservations.index') }}" class="aj-agent-filter-card">
            <div class="aj-agent-filter-head">
                <div>
                    <h2>Filtres de recherche</h2>
                    <p>Affinez la liste par client, statut ou date sans quitter la vue de travail.</p>
                </div>
            </div>
            <div class="aj-agent-filter-grid">
                <div class="aj-agent-field">
                    <label for="search">Recherche client</label>
                    <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Client, telephone, dossier...">
                </div>
                <div class="aj-agent-field">
                    <label for="status">Statut</label>
                    <select id="status" name="status">
                        <option value="">Tous</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="aj-agent-field">
                    <label for="date">Date</label>
                    <input id="date" type="date" name="date" value="{{ $filters['date'] ?? '' }}">
                </div>
                <div class="aj-agent-filter-actions">
                    <button type="submit" class="aj-agent-primary-btn">Filtrer</button>
                    <a href="{{ route('agent.reservations.index') }}" class="aj-agent-action-btn">Reinitialiser</a>
                </div>
            </div>
        </form>

        <div class="aj-agent-table-card">
            @if($reservations->count())
                <div class="aj-agent-table-head">
                    <div>
                        <h2>Liste des reservations</h2>
                        <p>Lecture compacte des dossiers avec acces direct au detail.</p>
                    </div>
                </div>
                <div class="aj-agent-table-responsive">
                    <table class="aj-agent-res-table">
                        <thead>
                            <tr>
                                <th>Reference / dossier</th>
                                <th>Client</th>
                                <th>Voyage</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Montant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $reservation)
                                @php
                                    $clientName = trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? ''));
                                    $date = $reservation->travelDate?->date ?? $reservation->departure?->start_date ?? $reservation->created_at;
                                    $statusClass = match ((string) $reservation->status) {
                                        \App\Models\Reservation::STATUS_CONFIRMED, \App\Models\Reservation::STATUS_PAID => 'is-success',
                                        \App\Models\Reservation::STATUS_CANCELLED, \App\Models\Reservation::STATUS_REFUNDED, \App\Models\Reservation::STATUS_EXPIRED => 'is-danger',
                                        \App\Models\Reservation::STATUS_PENDING, \App\Models\Reservation::STATUS_OPTION, \App\Models\Reservation::STATUS_SHARED_ROOM_PENDING, \App\Models\Reservation::STATUS_PARTIALLY_PAID => 'is-pending',
                                        default => 'is-neutral',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="aj-agent-ref">{{ $reservation->dossier_number ?: 'RES-' . $reservation->id }}</div>
                                        <div class="aj-agent-muted">#{{ $reservation->id }}</div>
                                    </td>
                                    <td>
                                        <div class="aj-agent-row-main">{{ $clientName !== '' ? $clientName : 'Client non renseigne' }}</div>
                                        <div class="aj-agent-muted">{{ $reservation->client_phone ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="aj-agent-voyage-name">{{ $reservation->tour?->name ?: 'Voyage non renseigne' }}</div>
                                    </td>
                                    <td>
                                        <div class="aj-agent-row-main">{{ $date ? $date->format('d/m/Y') : '-' }}</div>
                                        <div class="aj-agent-muted">{{ $date ? $date->format('H:i') : '' }}</div>
                                    </td>
                                    <td>
                                        <span class="aj-agent-status-pill {{ $statusClass }}">{{ $statusOptions[$reservation->status] ?? $reservation->status }}</span>
                                    </td>
                                    <td>
                                        <div class="aj-agent-money">{{ number_format((float) ($reservation->total_amount ?? 0), 0, ',', ' ') }} DH</div>
                                    </td>
                                    <td>
                                        <div class="aj-agent-row-actions">
                                            <a href="{{ route('agent.reservations.show', $reservation) }}" class="aj-agent-row-btn is-primary">
                                                <i class="bx bx-show"></i>
                                                <span>Voir details</span>
                                            </a>
                                            @if($canManageReservations ?? false)
                                                @if(!in_array((string) $reservation->status, [\App\Models\Reservation::STATUS_VALIDEE, \App\Models\Reservation::STATUS_CONFIRMED, \App\Models\Reservation::STATUS_PAID], true))
                                                    <form method="POST" action="{{ route('agent.reservations.validate', $reservation) }}" onsubmit="return confirm('Valider cette reservation ?');">
                                                        @csrf
                                                        <button type="submit" class="aj-agent-row-btn is-success">
                                                            <i class="bx bx-check"></i>
                                                            <span>Valider</span>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('agent.reservations.show', $reservation) }}#suivi-paiement" class="aj-agent-row-btn is-soft">
                                                    <i class="bx bx-credit-card"></i>
                                                    <span>Suivre paiement</span>
                                                </a>
                                                <a href="{{ route('agent.reservations.dossier.pdf', $reservation) }}" target="_blank" class="aj-agent-row-btn is-soft">
                                                    <i class="bx bx-printer"></i>
                                                    <span>Imprimer</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="aj-agent-pagination">{{ $reservations->links() }}</div>
            @else
                <div class="aj-agent-empty">
                    <h2>Aucune reservation trouvee</h2>
                    <p>Vos reservations creees ou assignees apparaitront ici.</p>
                    <a href="{{ route('agent.catalogue') }}" class="aj-agent-primary-btn">Consulter le catalogue</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
