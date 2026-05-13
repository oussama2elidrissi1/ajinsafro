@extends('layouts.admin-v2')

@section('title', 'Dossiers de reservation')

@php
    use App\Models\Reservation;

    $voyages = $voyages ?? collect();
    $stats = $stats ?? [];
    $filters = $filters ?? [];
    $currentStatus = $currentStatus ?? 'all';

    $reservationBadge = function ($reservation) {
        return match ((string) $reservation->status) {
            Reservation::STATUS_PENDING, Reservation::STATUS_OPTION, Reservation::STATUS_SHARED_ROOM_PENDING => ['label' => 'En attente', 'class' => 'is-pending'],
            Reservation::STATUS_CONFIRMED, Reservation::STATUS_SHARED_ROOM_PAIRED, Reservation::STATUS_PARTIALLY_PAID => ['label' => 'Confirmee', 'class' => 'is-confirmed'],
            Reservation::STATUS_PAID => ['label' => 'Payee', 'class' => 'is-paid'],
            Reservation::STATUS_CANCELLED => ['label' => 'Annulee', 'class' => 'is-cancelled'],
            default => ['label' => $reservation->statusLabelFr(), 'class' => 'is-neutral'],
        };
    };

    $paymentBadge = function ($reservation) {
        return match ((string) $reservation->payment_status) {
            Reservation::PAYMENT_STATUS_PAID => ['label' => 'Payee', 'class' => 'is-paid'],
            Reservation::PAYMENT_STATUS_PARTIAL, Reservation::PAYMENT_STATUS_DEPOSIT => ['label' => 'A suivre', 'class' => 'is-follow-up'],
            default => ['label' => 'Non payee', 'class' => 'is-unpaid'],
        };
    };
@endphp

@push('styles')
<style>
    :root {
        --voy-blue-900: #073b63;
        --voy-blue-800: #07598f;
        --voy-blue-700: #0877bd;
        --voy-blue-100: #e8f4ff;
        --voy-orange: #f97316;
        --voy-green: #12b76a;
        --voy-red: #ef4444;
        --voy-violet: #7c3aed;
        --voy-ink: #102a43;
        --voy-muted: #6b7a90;
        --voy-line: #e5edf6;
        --voy-bg: #f5f8fc;
        --voy-white: #ffffff;
        --voy-shadow: 0 18px 36px rgba(16, 42, 67, 0.08);
        --voy-shadow-soft: 0 10px 24px rgba(16, 42, 67, 0.06);
        --voy-radius-xl: 24px;
        --voy-radius-lg: 18px;
        --voy-radius-md: 14px;
    }

    .voy-page {
        display: grid;
        gap: 24px;
    }

    .voy-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        flex-wrap: wrap;
    }

    .voy-hero h1 {
        font-size: clamp(30px, 3vw, 42px);
        line-height: 1.02;
        letter-spacing: -0.04em;
        margin: 0 0 8px;
        color: #0b2545;
        font-weight: 800;
    }

    .voy-hero p {
        margin: 0;
        color: var(--voy-muted);
        font-weight: 600;
        max-width: 760px;
    }

    .voy-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 12px;
        padding: 12px 18px;
        font-weight: 800;
        text-decoration: none;
        border: 1px solid transparent;
        transition: all .2s ease;
    }

    .voy-btn-primary {
        color: #fff !important;
        background: linear-gradient(135deg, var(--voy-blue-700), var(--voy-blue-900));
        box-shadow: 0 12px 24px rgba(8, 119, 189, 0.22);
    }

    .voy-btn-soft {
        color: var(--voy-blue-900);
        background: #fff;
        border-color: var(--voy-line);
    }

    .voy-kpis {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 18px;
    }

    .voy-kpi {
        background: var(--voy-white);
        border: 1px solid var(--voy-line);
        border-radius: var(--voy-radius-lg);
        box-shadow: var(--voy-shadow-soft);
        padding: 20px;
        display: flex;
        gap: 14px;
        align-items: center;
    }

    .voy-kpi__icon {
        width: 52px;
        height: 52px;
        display: grid;
        place-items: center;
        border-radius: 18px;
        font-size: 22px;
    }

    .bg-blue { background: #eaf5ff; color: var(--voy-blue-700); }
    .bg-orange { background: #fff2e8; color: var(--voy-orange); }
    .bg-violet { background: #f3edff; color: var(--voy-violet); }
    .bg-green { background: #e8fff4; color: var(--voy-green); }
    .bg-red { background: #fff1f2; color: var(--voy-red); }

    .voy-kpi span {
        display: block;
        color: var(--voy-muted);
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .voy-kpi strong {
        display: block;
        color: var(--voy-ink);
        font-size: 28px;
        line-height: 1.1;
        font-weight: 900;
    }

    .voy-panel {
        background: rgba(255,255,255,.96);
        border: 1px solid var(--voy-line);
        border-radius: var(--voy-radius-xl);
        box-shadow: var(--voy-shadow);
        padding: 24px;
    }

    .voy-toolbar {
        display: grid;
        gap: 16px;
        margin-bottom: 24px;
    }

    .voy-tabs {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .voy-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 999px;
        background: #f8fafc;
        color: #50637c;
        text-decoration: none;
        font-weight: 800;
    }

    .voy-tab.active {
        background: var(--voy-blue-800);
        color: #fff !important;
        box-shadow: 0 8px 16px rgba(0, 85, 141, 0.2);
    }

    .voy-filter-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .voy-filter-grid .full {
        grid-column: span 2;
    }

    .voy-filter-actions {
        display: flex;
        gap: 10px;
        align-items: end;
    }

    .voy-list {
        display: grid;
        gap: 20px;
    }

    .voy-card {
        background: #fff;
        border: 1px solid var(--voy-line);
        border-radius: 20px;
        box-shadow: var(--voy-shadow-soft);
        overflow: hidden;
    }

    .voy-card__head {
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr) auto;
        gap: 20px;
        padding: 20px;
        align-items: stretch;
    }

    .voy-card__media {
        min-height: 122px;
        border-radius: 18px;
        background: linear-gradient(135deg, #eef2f7, #e5ecf5);
        overflow: hidden;
        position: relative;
    }

    .voy-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .voy-card__placeholder {
        height: 100%;
        display: grid;
        place-items: center;
        color: #90a0b6;
        text-align: center;
        font-weight: 700;
        padding: 14px;
    }

    .voy-card__main {
        display: grid;
        gap: 14px;
        min-width: 0;
    }

    .voy-card__title-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-start;
    }

    .voy-card__title h3 {
        margin: 0 0 6px;
        font-size: 24px;
        line-height: 1.08;
        color: #0e2847;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .voy-card__title p {
        margin: 0;
        color: var(--voy-muted);
        font-weight: 600;
    }

    .voy-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .voy-badge.is-active { background: #eef4ff; color: #2454d6; }
    .voy-badge.is-recent { background: #e8f4ff; color: var(--voy-blue-800); }
    .voy-badge.is-follow-up { background: #f3edff; color: var(--voy-violet); }
    .voy-badge.is-complete { background: #e8fff4; color: var(--voy-green); }
    .voy-badge.is-pending { background: #fff3e8; color: #cb5f12; }
    .voy-badge.is-confirmed { background: #eef4ff; color: #2454d6; }
    .voy-badge.is-paid { background: #e8fff4; color: #0a8d58; }
    .voy-badge.is-unpaid { background: #fff1f2; color: #d12f45; }
    .voy-badge.is-cancelled { background: #f1f5f9; color: #64748b; }
    .voy-badge.is-follow-up-light { background: #fff7ed; color: var(--voy-orange); }
    .voy-badge.is-neutral { background: #f1f5f9; color: #64748b; }

    .voy-stats {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
    }

    .voy-stat {
        background: #f8fbff;
        border: 1px solid var(--voy-line);
        border-radius: 16px;
        padding: 14px 16px;
    }

    .voy-stat span {
        display: block;
        color: #8a9ab0;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .voy-stat strong {
        display: block;
        color: var(--voy-ink);
        font-size: 18px;
        font-weight: 900;
        line-height: 1.15;
    }

    .voy-card__actions {
        display: grid;
        gap: 10px;
        min-width: 210px;
    }

    .voy-card__actions .voy-btn {
        width: 100%;
    }

    .voy-card__detail {
        border-top: 1px solid var(--voy-line);
        background: linear-gradient(180deg, #fff, #fbfdff);
        padding: 20px;
    }

    .voy-table-wrap {
        overflow-x: auto;
    }

    .voy-table {
        width: 100%;
        min-width: 1120px;
        margin: 0;
    }

    .voy-table thead th {
        background: #f8fbff;
        color: #6b7a90;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 1px solid var(--voy-line);
        padding: 14px 12px;
        white-space: nowrap;
    }

    .voy-table tbody td {
        padding: 14px 12px;
        border-bottom: 1px solid #edf2f7;
        vertical-align: middle;
        color: #233c59;
        font-weight: 600;
    }

    .voy-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .voy-row-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .voy-mini-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 34px;
        padding: 0 12px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        border: 1px solid var(--voy-line);
        background: #fff;
        color: var(--voy-blue-900);
    }

    .voy-empty {
        display: grid;
        place-items: center;
        padding: 64px 20px;
        text-align: center;
        color: var(--voy-muted);
    }

    .voy-empty i {
        font-size: 56px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }

    .voy-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-top: 24px;
        flex-wrap: wrap;
    }

    .voy-pagination .pagination {
        margin: 0;
        gap: 8px;
    }

    .voy-pagination .page-link {
        border-radius: 10px !important;
        border: 1px solid var(--voy-line);
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: #50637c;
    }

    .voy-pagination .page-item.active .page-link {
        background: var(--voy-blue-800);
        border-color: var(--voy-blue-800);
        color: #fff;
    }

    @media (max-width: 1399.98px) {
        .voy-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .voy-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 1199.98px) {
        .voy-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .voy-card__head { grid-template-columns: 120px minmax(0, 1fr); }
        .voy-card__actions { grid-column: 1 / -1; grid-template-columns: repeat(2, minmax(0, 1fr)); min-width: 0; }
    }

    @media (max-width: 767.98px) {
        .voy-kpis, .voy-stats, .voy-filter-grid, .voy-card__actions { grid-template-columns: 1fr; }
        .voy-filter-grid .full { grid-column: span 1; }
        .voy-card__head { grid-template-columns: 1fr; }
        .voy-card__media { min-height: 180px; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid voy-page">
    <div class="voy-hero">
        <div>
            <h1>Dossiers de reservation</h1>
            <p>Vue V3 orientee voyages: identifiez d'abord les voyages qui bougent, puis ouvrez au clic toutes les reservations qui demandent une action.</p>
        </div>
        <a href="{{ route('admin.reservations.create') }}" class="voy-btn voy-btn-primary">
            <i class="bx bx-plus"></i>
            <span>Creer un dossier</span>
        </a>
    </div>

    <div class="voy-kpis">
        <div class="voy-kpi">
            <div class="voy-kpi__icon bg-blue"><i class="bx bx-map"></i></div>
            <div><span>Voyages actifs</span><strong>{{ $stats['voyages'] ?? 0 }}</strong></div>
        </div>
        <div class="voy-kpi">
            <div class="voy-kpi__icon bg-blue"><i class="bx bx-collection"></i></div>
            <div><span>Reservations</span><strong>{{ $stats['reservations'] ?? 0 }}</strong></div>
        </div>
        <div class="voy-kpi">
            <div class="voy-kpi__icon bg-orange"><i class="bx bx-time-five"></i></div>
            <div><span>En attente</span><strong>{{ $stats['pending'] ?? 0 }}</strong></div>
        </div>
        <div class="voy-kpi">
            <div class="voy-kpi__icon bg-violet"><i class="bx bx-bell"></i></div>
            <div><span>A suivre</span><strong>{{ $stats['follow_up'] ?? 0 }}</strong></div>
        </div>
        <div class="voy-kpi">
            <div class="voy-kpi__icon bg-green"><i class="bx bx-check-circle"></i></div>
            <div><span>Payees</span><strong>{{ $stats['paid'] ?? 0 }}</strong></div>
        </div>
        <div class="voy-kpi">
            <div class="voy-kpi__icon bg-red"><i class="bx bx-wallet"></i></div>
            <div><span>Restant DH</span><strong>{{ number_format((float) ($stats['remaining_amount'] ?? 0), 0, ',', ' ') }}</strong></div>
        </div>
    </div>

    <div class="voy-panel">
        <div class="voy-toolbar">
            <div class="voy-tabs">
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'all'])) }}" class="voy-tab {{ $currentStatus === 'all' ? 'active' : '' }}">Tous</a>
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'pending'])) }}" class="voy-tab {{ $currentStatus === 'pending' ? 'active' : '' }}">En attente</a>
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'paid'])) }}" class="voy-tab {{ $currentStatus === 'paid' ? 'active' : '' }}">Payees</a>
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'follow_up'])) }}" class="voy-tab {{ $currentStatus === 'follow_up' ? 'active' : '' }}">A suivre</a>
            </div>

            <form method="GET" action="{{ route('admin.reservation-dossiers.index') }}" class="voy-filter-grid">
                @if($currentStatus !== 'all')
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <div class="full">
                    <label class="form-label">Recherche voyage / client / dossier</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Ex. Dakhla, Oussama, RES-2026-000038">
                </div>
                <div>
                    <label class="form-label">Voyage</label>
                    <select name="voyage_id" class="form-select">
                        <option value="">Tous</option>
                        @foreach(($voyageOptions ?? collect()) as $option)
                            <option value="{{ $option->id }}" @selected((string) ($filters['voyage_id'] ?? '') === (string) $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Statut reservation</label>
                    <select name="reservation_status" class="form-select">
                        <option value="">Tous</option>
                        <option value="pending" @selected(($filters['reservation_status'] ?? '') === 'pending')>En attente</option>
                        <option value="confirmed" @selected(($filters['reservation_status'] ?? '') === 'confirmed')>Confirmee</option>
                        <option value="paid" @selected(($filters['reservation_status'] ?? '') === 'paid')>Payee</option>
                        <option value="cancelled" @selected(($filters['reservation_status'] ?? '') === 'cancelled')>Annulee</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Statut paiement</label>
                    <select name="payment_status" class="form-select">
                        <option value="">Tous</option>
                        <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Payee</option>
                        <option value="partial" @selected(($filters['payment_status'] ?? '') === 'partial')>Partielle</option>
                        <option value="deposit" @selected(($filters['payment_status'] ?? '') === 'deposit')>Acompte</option>
                        <option value="non_paid" @selected(($filters['payment_status'] ?? '') === 'non_paid')>Non payee</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Date de depart</label>
                    <input type="date" name="departure_date" value="{{ $filters['departure_date'] ?? '' }}" class="form-control">
                </div>
                <div>
                    <label class="form-label">Periode reservation</label>
                    <select name="period" class="form-select">
                        <option value="today" @selected(($filters['period'] ?? '') === 'today')>Aujourd'hui</option>
                        <option value="7d" @selected(($filters['period'] ?? '7d') === '7d')>7 jours</option>
                        <option value="30d" @selected(($filters['period'] ?? '') === '30d')>30 jours</option>
                        <option value="all" @selected(($filters['period'] ?? '') === 'all')>Tout</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Agent / commercial</label>
                    <select name="agent_id" class="form-select">
                        <option value="">Tous</option>
                        @foreach(($agentOptions ?? collect()) as $option)
                            <option value="{{ $option->id }}" @selected((string) ($filters['agent_id'] ?? '') === (string) $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Point de vente</label>
                    <select name="branch_id" class="form-select">
                        <option value="">Tous</option>
                        @foreach(($branchOptions ?? collect()) as $option)
                            <option value="{{ $option->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Canal</label>
                    <select name="channel" class="form-select">
                        <option value="">Tous</option>
                        <option value="client" @selected(($filters['channel'] ?? '') === 'client')>Client</option>
                        <option value="admin" @selected(($filters['channel'] ?? '') === 'admin')>Admin</option>
                        <option value="agency" @selected(($filters['channel'] ?? '') === 'agency')>Agence</option>
                        <option value="commercial" @selected(($filters['channel'] ?? '') === 'commercial')>Commercial</option>
                    </select>
                </div>
                <div class="voy-filter-actions">
                    <button type="submit" class="voy-btn voy-btn-primary"><i class="bx bx-filter-alt"></i><span>Filtrer</span></button>
                    <a href="{{ route('admin.reservation-dossiers.index') }}" class="voy-btn voy-btn-soft"><i class="bx bx-refresh"></i><span>Reset</span></a>
                </div>
            </form>
        </div>

        @if($voyages->count() > 0)
            <div class="voy-list">
                @foreach($voyages as $voyageCard)
                    <article class="voy-card">
                        <div class="voy-card__head">
                            <div class="voy-card__media">
                                @if($voyageCard->image_url)
                                    <img src="{{ $voyageCard->image_url }}" alt="{{ $voyageCard->title }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                    <div class="voy-card__placeholder" style="display:none;">
                                        <div><i class="bx bx-map-pin fs-1 d-block mb-2"></i><span>Voyage</span></div>
                                    </div>
                                @else
                                    <div class="voy-card__placeholder">
                                        <div><i class="bx bx-map-pin fs-1 d-block mb-2"></i><span>Voyage</span></div>
                                    </div>
                                @endif
                            </div>

                            <div class="voy-card__main">
                                <div class="voy-card__title-row">
                                    <div class="voy-card__title">
                                        <h3>{{ $voyageCard->title }}</h3>
                                        <p>{{ $voyageCard->destination }}</p>
                                    </div>
                                    <span class="voy-badge {{ $voyageCard->global_badge['class'] }}">{{ $voyageCard->global_badge['label'] }}</span>
                                </div>

                                <div class="voy-stats">
                                    <div class="voy-stat"><span>Reservations</span><strong>{{ $voyageCard->reservations_count }}</strong></div>
                                    <div class="voy-stat"><span>En attente</span><strong>{{ $voyageCard->pending_count }}</strong></div>
                                    <div class="voy-stat"><span>Confirmees</span><strong>{{ $voyageCard->confirmed_count }}</strong></div>
                                    <div class="voy-stat"><span>A suivre</span><strong>{{ $voyageCard->follow_up_count }}</strong></div>
                                    <div class="voy-stat"><span>Total genere</span><strong>{{ number_format($voyageCard->total_amount, 0, ',', ' ') }} DH</strong></div>
                                    <div class="voy-stat"><span>Restant</span><strong>{{ number_format($voyageCard->remaining_amount, 0, ',', ' ') }} DH</strong></div>
                                </div>
                            </div>

                            <div class="voy-card__actions">
                                <div class="voy-stat">
                                    <span>Derniere reservation</span>
                                    <strong>{{ optional($voyageCard->latest_reservation?->created_at)->format('d/m/Y H:i') ?? '—' }}</strong>
                                </div>
                                <button class="voy-btn voy-btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#voyage-{{ \Illuminate\Support\Str::slug($voyageCard->key) }}" aria-expanded="false">
                                    <i class="bx bx-list-ul"></i>
                                    <span>Voir les reservations</span>
                                </button>
                            </div>
                        </div>

                        <div class="collapse" id="voyage-{{ \Illuminate\Support\Str::slug($voyageCard->key) }}">
                            <div class="voy-card__detail">
                                <div class="voy-table-wrap">
                                    <table class="table voy-table align-middle">
                                        <thead>
                                            <tr>
                                                <th>Dossier</th>
                                                <th>Client</th>
                                                <th>Telephone</th>
                                                <th>Depart</th>
                                                <th>Reservation</th>
                                                <th>Agent</th>
                                                <th>Statut</th>
                                                <th>Paiement</th>
                                                <th>Total</th>
                                                <th>Paye</th>
                                                <th>Restant</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($voyageCard->reservations as $reservation)
                                                @php
                                                    $resBadge = $reservationBadge($reservation);
                                                    $payBadge = $paymentBadge($reservation);
                                                    $actor = $reservation->assignedTo ?? $reservation->agent ?? $reservation->creator ?? null;
                                                    $detailUrl = $reservation->reservation_dossier_id
                                                        ? route('admin.reservation-dossiers.show', $reservation->reservation_dossier_id)
                                                        : route('admin.reservations.show', $reservation);
                                                    $paymentFollowUrl = $detailUrl.'#payment-form';
                                                @endphp
                                                <tr>
                                                    <td>{{ $reservation->dossier_number ?? ('RES-'.str_pad((string) $reservation->id, 6, '0', STR_PAD_LEFT)) }}</td>
                                                    <td>{{ $reservation->client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}</td>
                                                    <td>{{ $reservation->client?->phone ?: $reservation->client_phone ?: '—' }}</td>
                                                    <td>{{ $reservation->departure?->start_date?->format('d/m/Y') ?? '—' }}</td>
                                                    <td>{{ optional($reservation->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                                    <td>{{ $actor?->name ?? '—' }}</td>
                                                    <td><span class="voy-badge {{ $resBadge['class'] }}">{{ $resBadge['label'] }}</span></td>
                                                    <td><span class="voy-badge {{ $payBadge['class'] }}">{{ $payBadge['label'] }}</span></td>
                                                    <td>{{ number_format((float) $reservation->effective_total_amount, 2, ',', ' ') }} DH</td>
                                                    <td>{{ number_format((float) $reservation->effective_paid_amount, 2, ',', ' ') }} DH</td>
                                                    <td>
                                                        {{ number_format((float) $reservation->effective_remaining_amount, 2, ',', ' ') }} DH
                                                        @if((float) $reservation->effective_remaining_amount > 0)
                                                            <div class="mt-1"><span class="voy-badge is-follow-up-light">Restant a solder</span></div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="voy-row-actions">
                                                            <a href="{{ $detailUrl }}" class="voy-mini-btn"><i class="bx bx-show"></i><span>Voir</span></a>
                                                            @can('reservations.update')
                                                                @if(in_array($reservation->status, [Reservation::STATUS_PENDING, Reservation::STATUS_OPTION, Reservation::STATUS_SHARED_ROOM_PENDING], true))
                                                                    <form action="{{ route('admin.reservations.validate', $reservation) }}" method="POST">
                                                                        @csrf
                                                                        <button type="submit" class="voy-mini-btn"><i class="bx bx-check"></i><span>Valider</span></button>
                                                                    </form>
                                                                @endif
                                                            @endcan
                                                            <a href="{{ $paymentFollowUrl }}" class="voy-mini-btn"><i class="bx bx-wallet"></i><span>Suivre paiement</span></a>
                                                            <a href="{{ route('admin.reservations.dossier.pdf', $reservation) }}" target="_blank" class="voy-mini-btn"><i class="bx bx-printer"></i><span>Imprimer</span></a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="voy-pagination">
                <div>
                    Affichage de {{ $voyages->firstItem() ?? 0 }} a {{ $voyages->lastItem() ?? 0 }} sur {{ $voyages->total() }} voyages avec reservations
                </div>
                <div>{{ $voyages->links() }}</div>
            </div>
        @else
            <div class="voy-empty">
                <div>
                    <i class="bx bx-map"></i>
                    <h3 class="h5 mb-2">Aucun voyage avec reservations</h3>
                    <p class="mb-0">Aucun voyage ne correspond aux filtres actuels. Ajustez la periode ou les statuts pour retrouver l'activite reservation.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
