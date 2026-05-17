<?php $__env->startSection('title', 'Dossiers de reservation'); ?>

<?php
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
?>

<?php $__env->startPush('styles'); ?>
<style>
    .reservation-dossiers-page {
        padding-bottom: 24px;
    }

    .reservation-dossiers-page .rd-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }

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
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid reservation-dossiers-page">
    <div class="rd-hero">
        <div>
            <h1>Dossiers de reservation</h1>
            <p>Vue V3 orientee departs: identifiez d'abord les departs qui bougent, puis ouvrez au clic toutes les reservations qui demandent une action.</p>
        </div>
        <a href="<?php echo e(route('admin.reservations.create')); ?>" class="rd-btn rd-btn-primary">
            <i class="bx bx-plus"></i>
            <span>Creer un dossier</span>
        </a>
    </div>

    <div class="rd-page-kpis">
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#eaf5ff;color:#0877bd;"><i class="bx bx-map"></i></div>
            <div><span class="rd-page-kpi__label">Departs actifs</span><strong class="rd-page-kpi__value"><?php echo e($stats['voyages'] ?? 0); ?></strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#eaf5ff;color:#0877bd;"><i class="bx bx-collection"></i></div>
            <div><span class="rd-page-kpi__label">Reservations</span><strong class="rd-page-kpi__value"><?php echo e($stats['reservations'] ?? 0); ?></strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#fff2e8;color:#f97316;"><i class="bx bx-time-five"></i></div>
            <div><span class="rd-page-kpi__label">En attente</span><strong class="rd-page-kpi__value"><?php echo e($stats['pending'] ?? 0); ?></strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#f3edff;color:#7c3aed;"><i class="bx bx-bell"></i></div>
            <div><span class="rd-page-kpi__label">A suivre</span><strong class="rd-page-kpi__value"><?php echo e($stats['follow_up'] ?? 0); ?></strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#e8fff4;color:#12b76a;"><i class="bx bx-check-circle"></i></div>
            <div><span class="rd-page-kpi__label">Payees</span><strong class="rd-page-kpi__value"><?php echo e($stats['paid'] ?? 0); ?></strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#ffe8e8;color:#ef4444;"><i class="bx bx-wallet"></i></div>
            <div><span class="rd-page-kpi__label">Restant DH</span><strong class="rd-page-kpi__value"><?php echo e(number_format((float) ($stats['remaining_amount'] ?? 0), 0, ',', ' ')); ?></strong></div>
        </div>
    </div>

    <div class="rd-panel">
        <div class="rd-toolbar">
            <div class="rd-tabs">
                <a href="<?php echo e(route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'all']))); ?>" class="rd-tab <?php echo e($currentStatus === 'all' ? 'active' : ''); ?>">Tous</a>
                <a href="<?php echo e(route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'pending']))); ?>" class="rd-tab <?php echo e($currentStatus === 'pending' ? 'active' : ''); ?>">En attente</a>
                <a href="<?php echo e(route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'paid']))); ?>" class="rd-tab <?php echo e($currentStatus === 'paid' ? 'active' : ''); ?>">Payees</a>
                <a href="<?php echo e(route('admin.reservation-dossiers.index', array_merge(request()->except('status'), ['status' => 'follow_up']))); ?>" class="rd-tab <?php echo e($currentStatus === 'follow_up' ? 'active' : ''); ?>">A suivre</a>
            </div>

            <form method="GET" action="<?php echo e(route('admin.reservation-dossiers.index')); ?>" class="rd-filter-grid">
                <?php if($currentStatus !== 'all'): ?>
                    <input type="hidden" name="status" value="<?php echo e($currentStatus); ?>">
                <?php endif; ?>
                <div class="full">
                    <label class="form-label">Recherche voyage / client / dossier</label>
                    <input type="text" name="search" value="<?php echo e($filters['search'] ?? ''); ?>" class="form-control" placeholder="Ex. Dakhla, Oussama, RES-2026-000038">
                </div>
                <div>
                    <label class="form-label">Voyage</label>
                    <select name="voyage_id" class="form-select">
                        <option value="">Tous</option>
                        <?php $__currentLoopData = ($voyageOptions ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($option->id); ?>" <?php if((string) ($filters['voyage_id'] ?? '') === (string) $option->id): echo 'selected'; endif; ?>><?php echo e($option->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Statut reservation</label>
                    <select name="reservation_status" class="form-select">
                        <option value="">Tous</option>
                        <option value="pending" <?php if(($filters['reservation_status'] ?? '') === 'pending'): echo 'selected'; endif; ?>>En attente</option>
                        <option value="confirmed" <?php if(($filters['reservation_status'] ?? '') === 'confirmed'): echo 'selected'; endif; ?>>Confirmee</option>
                        <option value="paid" <?php if(($filters['reservation_status'] ?? '') === 'paid'): echo 'selected'; endif; ?>>Payee</option>
                        <option value="cancelled" <?php if(($filters['reservation_status'] ?? '') === 'cancelled'): echo 'selected'; endif; ?>>Annulee</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Periode</label>
                    <select name="period" class="form-select" onchange="this.form.submit()">
                        <option value="7d" <?php if(($filters['period'] ?? '7d') === '7d'): echo 'selected'; endif; ?>>7 derniers jours</option>
                        <option value="30d" <?php if(($filters['period'] ?? '') === '30d'): echo 'selected'; endif; ?>>30 derniers jours</option>
                        <option value="90d" <?php if(($filters['period'] ?? '') === '90d'): echo 'selected'; endif; ?>>90 derniers jours</option>
                        <option value="all" <?php if(($filters['period'] ?? '') === 'all'): echo 'selected'; endif; ?>>Toutes les periodes</option>
                    </select>
                </div>
            </form>
        </div>

        <?php if($voyages->count() > 0): ?>
            <div class="rd-list">
                <?php $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyageCard): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <article class="rd-card">
                        <div class="rd-card__head">
                            <div class="rd-card__media">
                                <?php if($voyageCard->image_url): ?>
                                    <img src="<?php echo e($voyageCard->image_url); ?>" alt="<?php echo e($voyageCard->title); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                    <div class="rd-card__placeholder" style="display:none;">
                                        <div><i class="bx bx-map-pin"></i><span>Voyage</span></div>
                                    </div>
                                <?php else: ?>
                                    <div class="rd-card__placeholder">
                                        <div><i class="bx bx-map-pin"></i><span>Voyage</span></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="rd-card__main">
                                <div class="rd-card__top">
                                    <div class="rd-card__title">
                                        <h3><?php echo e($voyageCard->title); ?></h3>
                                        <p><?php echo e($voyageCard->destination); ?></p>
                                    </div>
                                    <span class="rd-badge-departure"><?php echo e($voyageCard->global_badge['label']); ?></span>
                                </div>

                                <div class="rd-mini-kpis">
                                    <div class="rd-mini-kpi"><span>Reservations</span> <strong><?php echo e($voyageCard->reservations_count); ?></strong></div>
                                    <div class="rd-mini-kpi"><span>En attente</span> <strong><?php echo e($voyageCard->pending_count); ?></strong></div>
                                    <div class="rd-mini-kpi"><span>Confirmees</span> <strong><?php echo e($voyageCard->confirmed_count); ?></strong></div>
                                    <div class="rd-mini-kpi"><span>A suivre</span> <strong><?php echo e($voyageCard->follow_up_count); ?></strong></div>
                                    <div class="rd-mini-kpi"><span>Total genere</span> <strong><?php echo e(number_format($voyageCard->total_amount, 0, ',', ' ')); ?> DH</strong></div>
                                    <div class="rd-mini-kpi"><span>Restant</span> <strong><?php echo e(number_format($voyageCard->remaining_amount, 0, ',', ' ')); ?> DH</strong></div>
                                </div>
                            </div>

                            <div class="rd-card__actions">
                                <button class="rd-btn rd-btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#voyage-<?php echo e(\Illuminate\Support\Str::slug($voyageCard->key)); ?>" aria-expanded="false">
                                    <i class="bx bx-list-ul"></i>
                                    <span>Voir les reservations</span>
                                </button>
                            </div>
                        </div>

                        <div class="collapse" id="voyage-<?php echo e(\Illuminate\Support\Str::slug($voyageCard->key)); ?>">
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
                                            <?php $__empty_1 = true; $__currentLoopData = $voyageCard->reservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                <?php
                                                    $resBadge = $reservationBadge($reservation);
                                                    $payBadge = $paymentBadge($reservation);
                                                    $actor = $reservation->assignedTo ?? $reservation->agent ?? $reservation->creator ?? null;
                                                    $detailUrl = $reservation->reservation_dossier_id
                                                        ? route('admin.reservation-dossiers.show', $reservation->reservation_dossier_id)
                                                        : route('admin.reservations.show', $reservation);
                                                    $paymentFollowUrl = $detailUrl.'#payment-form';
                                                ?>
                                                <tr>
                                                    <td><?php echo e($reservation->dossier_number ?? ('RES-'.str_pad((string) $reservation->id, 6, '0', STR_PAD_LEFT))); ?></td>
                                                    <td><?php echo e($reservation->client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—'); ?></td>
                                                    <td><?php echo e($reservation->client?->phone ?: $reservation->client_phone ?: '—'); ?></td>
                                                    <td><?php echo e($reservation->travelDate?->date?->format('d/m/Y') ?? $reservation->departure?->start_date?->format('d/m/Y') ?? '—'); ?></td>
                                                    <td><?php echo e(optional($reservation->created_at)->format('d/m/Y H:i') ?? '—'); ?></td>
                                                    <td><?php echo e($actor?->name ?? '—'); ?></td>
                                                    <td><span class="rd-badge <?php echo e($resBadge['class']); ?>"><?php echo e($resBadge['label']); ?></span></td>
                                                    <td><span class="rd-badge <?php echo e($payBadge['class']); ?>"><?php echo e($payBadge['label']); ?></span></td>
                                                    <td><?php echo e(number_format((float) $reservation->effective_total_amount, 2, ',', ' ')); ?> DH</td>
                                                    <td><?php echo e(number_format((float) $reservation->effective_paid_amount, 2, ',', ' ')); ?> DH</td>
                                                    <td>
                                                        <?php echo e(number_format((float) $reservation->effective_remaining_amount, 2, ',', ' ')); ?> DH
                                                        <?php if((float) $reservation->effective_remaining_amount > 0): ?>
                                                            <div class="mt-1"><span class="rd-badge is-follow-up-light">Restant a solder</span></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="rd-row-actions">
                                                            <a href="<?php echo e($detailUrl); ?>" class="rd-mini-btn"><i class="bx bx-show"></i><span>Voir</span></a>
                                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.update')): ?>
                                                                <?php if(in_array($reservation->status, [Reservation::STATUS_PENDING, Reservation::STATUS_OPTION, Reservation::STATUS_SHARED_ROOM_PENDING], true)): ?>
                                                                    <form action="<?php echo e(route('admin.reservations.validate', $reservation)); ?>" method="POST">
                                                                        <?php echo csrf_field(); ?>
                                                                        <button type="submit" class="rd-mini-btn"><i class="bx bx-check"></i><span>Valider</span></button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            <a href="<?php echo e($paymentFollowUrl); ?>" class="rd-mini-btn"><i class="bx bx-wallet"></i><span>Suivre paiement</span></a>
                                                            <a href="<?php echo e(route('admin.reservations.dossier.pdf', $reservation)); ?>" target="_blank" class="rd-mini-btn"><i class="bx bx-printer"></i><span>Imprimer</span></a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                <tr>
                                                    <td colspan="12" class="text-center py-4 text-muted">Aucune reservation pour ce depart</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="rd-pagination">
                <div>
                    Affichage de <?php echo e($voyages->firstItem() ?? 0); ?> a <?php echo e($voyages->lastItem() ?? 0); ?> sur <?php echo e($voyages->total()); ?> departs avec reservations
                </div>
                <div><?php echo e($voyages->links()); ?></div>
            </div>
        <?php else: ?>
            <div class="rd-empty">
                <div>
                    <i class="bx bx-map"></i>
                    <h3 class="h5 mb-2">Aucun dossier de reservation trouve</h3>
                    <p class="mb-0">Aucun depart ne correspond aux filtres actuels. Ajustez la periode ou les statuts pour retrouver l'activite reservation.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservation-dossiers\index.blade.php ENDPATH**/ ?>