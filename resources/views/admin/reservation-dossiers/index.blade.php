@extends('layouts.admin-v6')

@section('title', 'Dossiers de réservation')
@section('page_title', 'Dossiers de réservation')
@section('hide_admin_footer', '1')
@section('header_primary_action')
    <a href="{{ route('admin.reservations.create') }}" class="aj-v6-primary-btn">
        <i class="bx bx-plus"></i>
        <span>Créer un dossier</span>
    </a>
@endsection

@php
    $breadcrumbs = [
        ['label' => 'Accueil', 'url' => \Illuminate\Support\Facades\Route::has('admin.dashboard.v6') ? route('admin.dashboard.v6') : route('admin.dashboard')],
        ['label' => 'Réservations'],
    ];
@endphp

@php
    use App\Models\Reservation;

    $voyages = $voyages ?? collect();
    $stats = $stats ?? [];
    $filters = $filters ?? [];
    $currentStatus = $currentStatus ?? 'all';

    $reservationBadge = function ($reservation) {
        if ($reservation->needsSharedRoomPairing()) {
            return ['label' => 'En attente de jumelage', 'class' => 'is-pending'];
        }
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
    .reservation-dossiers-page {
        padding-bottom: 24px;
    }

    .reservation-dossiers-page .rd-hero { display: none; }

    .reservation-dossiers-page .rd-hero h1 {
        font-size: clamp(28px, 3vw, 40px);
        line-height: 1.05;
        letter-spacing: -0.03em;
        margin: 0 0 8px;
        color: #0b2545;
        font-weight: 800;
    }

    .reservation-dossiers-page .rd-hero p {
        margin: 0;
        color: #6b7a90;
        font-weight: 600;
        max-width: 720px;
        font-size: 15px;
    }

    .reservation-dossiers-page .rd-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 12px;
        padding: 12px 18px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        border: 1px solid transparent;
        transition: all .2s ease;
        cursor: pointer;
        white-space: nowrap;
    }

    .reservation-dossiers-page .rd-btn-primary {
        color: #fff !important;
        background: linear-gradient(135deg, #0877bd, #073b63);
        box-shadow: 0 8px 20px rgba(8, 119, 189, 0.22);
    }

    .reservation-dossiers-page .rd-page-kpis {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }

    .reservation-dossiers-page .rd-page-kpi {
        background: #fff;
        border: 1px solid #e5edf6;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(16, 42, 67, 0.04);
        padding: 18px;
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .reservation-dossiers-page .rd-page-kpi__icon {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        font-size: 20px;
        flex-shrink: 0;
    }

    .reservation-dossiers-page .rd-page-kpi__label {
        display: block;
        color: #6b7a90;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .reservation-dossiers-page .rd-page-kpi__value {
        display: block;
        color: #102a43;
        font-size: 20px;
        font-weight: 900;
        line-height: 1.1;
    }

    .reservation-dossiers-page .rd-panel {
        background: #fff;
        border: 1px solid #e5edf6;
        border-radius: 20px;
        box-shadow: 0 8px 24px rgba(16, 42, 67, 0.06);
        padding: 22px;
    }

    .reservation-dossiers-page .rd-toolbar {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-bottom: 22px;
    }

    .reservation-dossiers-page .rd-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .reservation-dossiers-page .rd-tab {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        color: #6b7a90;
        background: #f8fbff;
        border: 1px solid #e5edf6;
        transition: all .2s ease;
    }

    .reservation-dossiers-page .rd-tab.active,
    .reservation-dossiers-page .rd-tab:hover {
        background: #07598f;
        color: #fff;
        border-color: #07598f;
    }

    .reservation-dossiers-page .rd-filter-grid {
        display: grid;
        grid-template-columns: 1fr 220px 220px 220px;
        gap: 12px;
        align-items: end;
    }

    .reservation-dossiers-page .rd-filter-grid .full {
        grid-column: 1 / -1;
    }

    .reservation-dossiers-page .rd-card {
        background: #fff;
        border: 1px solid #e5edf6;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(16, 42, 67, 0.04);
        margin-bottom: 14px;
        overflow: hidden;
    }

    .reservation-dossiers-page .rd-card__head {
        display: grid;
        grid-template-columns: 180px 1fr auto;
        gap: 18px;
        align-items: center;
        padding: 16px 20px;
    }

    .reservation-dossiers-page .rd-card__media {
        width: 180px;
        height: 115px;
        border-radius: 14px;
        overflow: hidden;
        background: #f5f8fc;
        flex-shrink: 0;
    }

    .reservation-dossiers-page .rd-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .reservation-dossiers-page .rd-card__placeholder {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        color: #6b7a90;
        font-size: 12px;
    }

    .reservation-dossiers-page .rd-card__placeholder i {
        font-size: 22px;
        margin-bottom: 2px;
    }

    .reservation-dossiers-page .rd-card__main {
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-width: 0;
    }

    .reservation-dossiers-page .rd-card__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .reservation-dossiers-page .rd-card__title h3 {
        font-size: 17px;
        font-weight: 800;
        margin: 0;
        color: #102a43;
        line-height: 1.25;
    }

    .reservation-dossiers-page .rd-card__title p {
        font-size: 13px;
        color: #6b7a90;
        margin: 3px 0 0;
    }

    .reservation-dossiers-page .rd-badge-departure {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 12px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        background: #e8f4ff;
        color: #07598f;
        white-space: nowrap;
        letter-spacing: 0.02em;
    }

    .reservation-dossiers-page .rd-mini-kpis {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .reservation-dossiers-page .rd-mini-kpi {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 700;
        color: #102a43;
        background: #f8fbff;
        border: 1px solid #e5edf6;
        border-radius: 8px;
        padding: 5px 11px;
        white-space: nowrap;
    }

    .reservation-dossiers-page .rd-mini-kpi span {
        color: #6b7a90;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
    }

    .reservation-dossiers-page .rd-card__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        min-width: 0;
    }

    .reservation-dossiers-page .rd-card__detail {
        border-top: 1px solid #e5edf6;
        background: linear-gradient(180deg, #fff, #fbfdff);
        padding: 20px;
    }

    .reservation-dossiers-page .rd-table-wrap {
        overflow-x: auto;
    }

    .reservation-dossiers-page .rd-table {
        width: 100%;
        min-width: 1120px;
        margin: 0;
    }

    .reservation-dossiers-page .rd-table thead th {
        background: #f8fbff;
        color: #6b7a90;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 1px solid #e5edf6;
        padding: 14px 12px;
        white-space: nowrap;
    }

    .reservation-dossiers-page .rd-table tbody td {
        padding: 14px 12px;
        border-bottom: 1px solid #edf2f7;
        vertical-align: middle;
        color: #233c59;
        font-weight: 600;
        font-size: 13px;
    }

    .reservation-dossiers-page .rd-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .reservation-dossiers-page .rd-row-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .reservation-dossiers-page .rd-mini-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 34px;
        padding: 0 12px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid #e5edf6;
        background: #fff;
        color: #073b63;
        white-space: nowrap;
    }

    .reservation-dossiers-page .rd-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .reservation-dossiers-page .rd-badge.is-pending { background: #fff3e8; color: #cb5f12; }
    .reservation-dossiers-page .rd-badge.is-confirmed { background: #eef4ff; color: #2454d6; }
    .reservation-dossiers-page .rd-badge.is-paid { background: #e8fff4; color: #0a8d58; }
    .reservation-dossiers-page .rd-badge.is-unpaid { background: #fff1f2; color: #d12f45; }
    .reservation-dossiers-page .rd-badge.is-follow-up { background: #f3edff; color: #7c3aed; }
    .reservation-dossiers-page .rd-badge.is-follow-up-light { background: #fff7ed; color: #f97316; }
    .reservation-dossiers-page .rd-badge.is-cancelled { background: #f1f5f9; color: #64748b; }
    .reservation-dossiers-page .rd-badge.is-neutral { background: #f1f5f9; color: #64748b; }

    .reservation-dossiers-page .rd-empty {
        display: grid;
        place-items: center;
        padding: 64px 20px;
        text-align: center;
        color: #6b7a90;
    }

    .reservation-dossiers-page .rd-empty i {
        font-size: 56px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }

    .reservation-dossiers-page .rd-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-top: 24px;
        flex-wrap: wrap;
        color: #6b7a90;
        font-size: 13px;
        font-weight: 600;
    }

    .reservation-dossiers-page .rd-pagination .pagination {
        margin: 0;
        gap: 6px;
    }

    .reservation-dossiers-page .rd-pagination .page-link {
        border-radius: 10px !important;
        border: 1px solid #e5edf6;
        min-width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #50637c;
        font-size: 13px;
    }

    .reservation-dossiers-page .rd-pagination .page-item.active .page-link {
        background: #07598f;
        border-color: #07598f;
        color: #fff;
    }

    @media (max-width: 1399.98px) {
        .reservation-dossiers-page .rd-page-kpis {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 1199.98px) {
        .reservation-dossiers-page .rd-card__head {
            grid-template-columns: 160px 1fr auto;
        }
    }

    @media (max-width: 991.98px) {
        .reservation-dossiers-page .rd-filter-grid {
            grid-template-columns: 1fr 1fr;
        }
        .reservation-dossiers-page .rd-card__head {
            grid-template-columns: 140px 1fr;
        }
        .reservation-dossiers-page .rd-card__actions {
            grid-column: 1 / -1;
            justify-content: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .reservation-dossiers-page .rd-page-kpis {
            grid-template-columns: repeat(2, 1fr);
        }
        .reservation-dossiers-page .rd-filter-grid {
            grid-template-columns: 1fr;
        }
        .reservation-dossiers-page .rd-filter-grid .full {
            grid-column: span 1;
        }
        .reservation-dossiers-page .rd-card__head {
            grid-template-columns: 1fr;
        }
        .reservation-dossiers-page .rd-card__media {
            width: 100%;
            height: 180px;
        }
        .reservation-dossiers-page .rd-mini-kpis {
            gap: 6px;
        }
        .reservation-dossiers-page .rd-btn {
            width: 100%;
        }
    }
    .reservation-dossiers-page .rd-mini-btn--danger {
        background: #fff1f2;
        color: #dc2626;
        border-color: #fecdd3;
    }

    .reservation-dossiers-page .rd-mini-btn--danger:hover {
        background: #dc2626;
        color: #ffffff;
        border-color: #dc2626;
    }
</style>
@endpush

@section('content')
<div class="reservation-dossiers-page">
    <div class="rd-hero">
        <div>
            <h1>Dossiers de reservation</h1>
            <p>Vue V3 orientee departs: identifiez d'abord les departs qui bougent, puis ouvrez au clic toutes les reservations qui demandent une action.</p>
        </div>
        <a href="{{ route('admin.reservations.create') }}" class="rd-btn rd-btn-primary">
            <i class="bx bx-plus"></i>
            <span>Creer un dossier</span>
        </a>
    </div>

    <div class="rd-page-kpis">
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#eaf5ff;color:#0877bd;"><i class="bx bx-map"></i></div>
            <div><span class="rd-page-kpi__label">Departs actifs</span><strong class="rd-page-kpi__value">{{ $stats['voyages'] ?? 0 }}</strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#eaf5ff;color:#0877bd;"><i class="bx bx-collection"></i></div>
            <div><span class="rd-page-kpi__label">Reservations</span><strong class="rd-page-kpi__value">{{ $stats['reservations'] ?? 0 }}</strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#fff2e8;color:#f97316;"><i class="bx bx-time-five"></i></div>
            <div><span class="rd-page-kpi__label">En attente</span><strong class="rd-page-kpi__value">{{ $stats['pending'] ?? 0 }}</strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#f3edff;color:#7c3aed;"><i class="bx bx-bell"></i></div>
            <div><span class="rd-page-kpi__label">A suivre</span><strong class="rd-page-kpi__value">{{ $stats['follow_up'] ?? 0 }}</strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#e8fff4;color:#12b76a;"><i class="bx bx-check-circle"></i></div>
            <div><span class="rd-page-kpi__label">Payees</span><strong class="rd-page-kpi__value">{{ $stats['paid'] ?? 0 }}</strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#ffe8e8;color:#ef4444;"><i class="bx bx-wallet"></i></div>
            <div><span class="rd-page-kpi__label">Restant DH</span><strong class="rd-page-kpi__value">{{ number_format((float) ($stats['remaining_amount'] ?? 0), 0, ',', ' ') }}</strong></div>
        </div>
    </div>

    <div class="rd-panel">
        <div class="rd-toolbar">
            <div class="rd-tabs">
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'all'])) }}" class="rd-tab {{ $currentStatus === 'all' ? 'active' : '' }}">Tous</a>
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'pending'])) }}" class="rd-tab {{ $currentStatus === 'pending' ? 'active' : '' }}">En attente</a>
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'paid'])) }}" class="rd-tab {{ $currentStatus === 'paid' ? 'active' : '' }}">Payees</a>
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'follow_up'])) }}" class="rd-tab {{ $currentStatus === 'follow_up' ? 'active' : '' }}">A suivre</a>
            </div>

            <form method="GET" action="{{ route('admin.reservation-dossiers.index') }}" class="rd-filter-grid">
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
                    <label class="form-label">Periode</label>
                    <select name="period" class="form-select" onchange="this.form.submit()">
                        <option value="7d" @selected(($filters['period'] ?? '7d') === '7d')>7 derniers jours</option>
                        <option value="30d" @selected(($filters['period'] ?? '') === '30d')>30 derniers jours</option>
                        <option value="90d" @selected(($filters['period'] ?? '') === '90d')>90 derniers jours</option>
                        <option value="all" @selected(($filters['period'] ?? '') === 'all')>Toutes les periodes</option>
                    </select>
                </div>
            </form>
        </div>

        @if($voyages->count() > 0)
            <div class="rd-list">
                @foreach($voyages as $voyageCard)
                    <article class="rd-card">
                        <div class="rd-card__head">
                            <div class="rd-card__media">
                                @if($voyageCard->image_url)
                                    <img src="{{ $voyageCard->image_url }}" alt="{{ $voyageCard->title }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                    <div class="rd-card__placeholder" style="display:none;">
                                        <div><i class="bx bx-map-pin"></i><span>Voyage</span></div>
                                    </div>
                                @else
                                    <div class="rd-card__placeholder">
                                        <div><i class="bx bx-map-pin"></i><span>Voyage</span></div>
                                    </div>
                                @endif
                            </div>

                            <div class="rd-card__main">
                                <div class="rd-card__top">
                                    <div class="rd-card__title">
                                        <h3>{{ $voyageCard->title }}</h3>
                                        <p>{{ $voyageCard->destination }}</p>
                                    </div>
                                    <span class="rd-badge-departure">{{ $voyageCard->global_badge['label'] }}</span>
                                </div>

                                <div class="rd-mini-kpis">
                                    <div class="rd-mini-kpi"><span>Reservations</span> <strong>{{ $voyageCard->reservations_count }}</strong></div>
                                    <div class="rd-mini-kpi"><span>En attente</span> <strong>{{ $voyageCard->pending_count }}</strong></div>
                                    <div class="rd-mini-kpi"><span>Confirmees</span> <strong>{{ $voyageCard->confirmed_count }}</strong></div>
                                    <div class="rd-mini-kpi"><span>A suivre</span> <strong>{{ $voyageCard->follow_up_count }}</strong></div>
                                    <div class="rd-mini-kpi"><span>Total genere</span> <strong>{{ number_format($voyageCard->total_amount, 0, ',', ' ') }} DH</strong></div>
                                    <div class="rd-mini-kpi"><span>Restant</span> <strong>{{ number_format($voyageCard->remaining_amount, 0, ',', ' ') }} DH</strong></div>
                                </div>
                            </div>

                            <div class="rd-card__actions">
                                <button class="rd-btn rd-btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#voyage-{{ \Illuminate\Support\Str::slug($voyageCard->key) }}" aria-expanded="false">
                                    <i class="bx bx-list-ul"></i>
                                    <span>Voir les reservations</span>
                                </button>
                            </div>
                        </div>

                        <div class="collapse" id="voyage-{{ \Illuminate\Support\Str::slug($voyageCard->key) }}">
                            <div class="rd-card__detail">
                                <p class="mb-3" style="color:#6b7a90;font-size:13px;font-weight:600;">Reservations de ce depart uniquement.</p>
                                <div class="rd-table-wrap">
                                    <table class="table rd-table align-middle">
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
                                            @forelse($voyageCard->reservations as $reservation)
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
                                                    <td>{{ $reservation->client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '?' }}</td>
                                                    <td>{{ $reservation->client?->phone ?: $reservation->client_phone ?: '?' }}</td>
                                                    <td>{{ $reservation->travelDate?->date?->format('d/m/Y') ?? $reservation->departure?->start_date?->format('d/m/Y') ?? '?' }}</td>
                                                    <td>{{ optional($reservation->created_at)->format('d/m/Y H:i') ?? '?' }}</td>
                                                    <td>{{ $actor?->name ?? '?' }}</td>
                                                    <td><span class="rd-badge {{ $resBadge['class'] }}">{{ $resBadge['label'] }}</span></td>
                                                    <td><span class="rd-badge {{ $payBadge['class'] }}">{{ $payBadge['label'] }}</span></td>
                                                    <td>{{ number_format((float) $reservation->effective_total_amount, 2, ',', ' ') }} DH</td>
                                                    <td>{{ number_format((float) $reservation->effective_paid_amount, 2, ',', ' ') }} DH</td>
                                                    <td>
                                                        {{ number_format((float) $reservation->effective_remaining_amount, 2, ',', ' ') }} DH
                                                        @if((float) $reservation->effective_remaining_amount > 0)
                                                            <div class="mt-1"><span class="rd-badge is-follow-up-light">Restant a solder</span></div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="rd-row-actions">
                                                            <a href="{{ $detailUrl }}" class="rd-mini-btn"><i class="bx bx-show"></i><span>Voir</span></a>
                                                            @if($reservation->needsSharedRoomPairing())
                                                                <button type="button" class="rd-mini-btn btn-res-hub-pair" title="Jumeler"
                                                                    data-res-id="{{ $reservation->id }}"
                                                                    data-res-code="{{ $reservation->catalog_source_code ?: ('RES-' . str_pad((string) $reservation->id, 6, '0', STR_PAD_LEFT)) }}"
                                                                >
                                                                    <i class="bx bx-link"></i><span>Jumeler</span>
                                                                </button>
                                                            @endif
                                                            @can('reservations.update')
                                                                @if(in_array($reservation->status, [Reservation::STATUS_PENDING, Reservation::STATUS_OPTION, Reservation::STATUS_SHARED_ROOM_PENDING], true))
                                                                    <form action="{{ route('admin.reservations.validate', $reservation) }}" method="POST">
                                                                        @csrf
                                                                        <button type="submit" class="rd-mini-btn"><i class="bx bx-check"></i><span>Valider</span></button>
                                                                    </form>
                                                                @endif
                                                            @endcan
                                                            <a href="{{ $paymentFollowUrl }}" class="rd-mini-btn"><i class="bx bx-wallet"></i><span>Suivre paiement</span></a>
                                                            <a href="{{ route('admin.reservations.dossier.pdf', $reservation) }}" target="_blank" class="rd-mini-btn"><i class="bx bx-printer"></i><span>Imprimer</span></a>
                                                            <form method="POST" action="{{ route('admin.reservation-dossiers.destroy', $reservation) }}" class="js-delete-reservation-form" style="display:inline;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="rd-mini-btn rd-mini-btn--danger" data-confirm-delete="Voulez-vous vraiment supprimer cette reservation ? Cette action est irreversible.">
                                                                    <i class="bx bx-trash"></i><span>Supprimer</span>
                                                                </button>
                                                            </form>
                                                            @if(config('app.debug'))
                                                                <div class="small text-muted mt-1">
                                                                    pairing={{ $reservation->needsSharedRoomPairing() ? 'yes' : 'no' }} | rooms={{ $reservation->reservationRooms->count() }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="12" class="text-center py-4 text-muted">Aucune reservation pour ce depart</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="rd-pagination">
                <div>
                    Affichage de {{ $voyages->firstItem() ?? 0 }} a {{ $voyages->lastItem() ?? 0 }} sur {{ $voyages->total() }} departs avec reservations
                </div>
                <div>{{ $voyages->links() }}</div>
            </div>
        @else
            <div class="rd-empty">
                <div>
                    <i class="bx bx-map"></i>
                    <h3 class="h5 mb-2">Aucun dossier de reservation trouve</h3>
                    <p class="mb-0">Aucun depart ne correspond aux filtres actuels. Ajustez la periode ou les statuts pour retrouver l'activite reservation.</p>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal Jumelage -->
<div class="modal fade" id="pairingModal" tabindex="-1" aria-labelledby="pairingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pairingModalLabel">Jumeler la réservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" id="pairing-modal-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Recherche des réservations compatibles...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.js-delete-reservation-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var message = form.querySelector('[data-confirm-delete]')?.dataset.confirmDelete
                || 'Voulez-vous vraiment supprimer cette reservation ?';
            if (confirm(message)) {
                form.submit();
            }
        });
    });

    // Pairing modal handler
    var pairingModalEl = document.getElementById('pairingModal');
    var pairingModal = pairingModalEl ? bootstrap.Modal.getOrCreateInstance(pairingModalEl) : null;
    var pairingModalBody = document.getElementById('pairing-modal-body');
    var pairingModalLabel = document.getElementById('pairingModalLabel');

    if (pairingModalEl && pairingModalBody) {
        document.body.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-res-hub-pair');
            if (!btn) return;
            e.preventDefault();
            var resId = btn.getAttribute('data-res-id');
            var resCode = btn.getAttribute('data-res-code');
            if (!resId) return;
            if (pairingModalLabel) {
                pairingModalLabel.textContent = 'Jumeler la réservation ' + (resCode || '#'+resId);
            }
            pairingModalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Recherche des réservations compatibles...</p></div>';
            pairingModal.show();

            var url = '/admin/reservations/' + encodeURIComponent(resId) + '/pairing-candidates';
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (r) {
                return r.json().then(function (data) {
                    if (!r.ok || data.error) {
                        var msg = data.error || data.message || ('Erreur ' + r.status);
                        throw new Error(msg);
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (data.html) {
                    pairingModalBody.innerHTML = data.html;
                } else {
                    pairingModalBody.innerHTML = '<div class="alert alert-warning border-0">Aucune donnée reçue.</div>';
                }
            })
            .catch(function (err) {
                pairingModalBody.innerHTML = '<div class="alert alert-danger border-0">Impossible de charger les candidats de jumelage. ' + (err.message || 'Erreur serveur') + '</div>';
            });
        });
    }
})();
</script>
@endpush

