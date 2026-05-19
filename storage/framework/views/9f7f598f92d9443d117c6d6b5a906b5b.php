<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page_title', 'Espace client'); ?>

<?php $__env->startSection('page_styles'); ?>
<style>
    /* ── Hero welcome card ── */
    .aj-hero-card {
        background: linear-gradient(135deg, var(--aj-navy) 0%, #0d2847 60%, #162f4e 100%);
        border-radius: var(--aj-radius);
        padding: 2.5rem 2rem 2rem;
        position: relative;
        overflow: hidden;
        color: #fff;
        border: 1px solid rgba(201,145,61,0.2);
        box-shadow: var(--aj-shadow);
    }
    .aj-hero-card::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(201,145,61,0.15) 0%, transparent 70%);
        pointer-events: none;
    }
    .aj-hero-card::after {
        content: '';
        position: absolute;
        bottom: -30px; left: 30%;
        width: 150px; height: 150px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(201,145,61,0.08) 0%, transparent 70%);
        pointer-events: none;
    }
    .aj-avatar {
        width: 58px; height: 58px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--aj-gold) 0%, #e8a84a 100%);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.4rem; font-weight: 700;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(201,145,61,0.4);
        letter-spacing: 0.04em;
    }
    .aj-hero-name {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.6rem;
        font-weight: 600;
        color: #fff;
        margin: 0 0 0.2rem;
        letter-spacing: 0.01em;
    }
    .aj-hero-label {
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--aj-gold-light);
    }
    .aj-hero-meta {
        display: flex;
        gap: 1.5rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }
    .aj-hero-meta-item {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.82rem;
        color: rgba(255,255,255,0.72);
    }
    .aj-hero-meta-item i {
        color: var(--aj-gold);
        font-size: 1rem;
    }

    /* ── Stat tiles ── */
    .aj-stat-tile {
        background: var(--aj-card);
        border-radius: var(--aj-radius);
        border: 1px solid var(--aj-border);
        box-shadow: var(--aj-shadow);
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.2rem;
        transition: box-shadow 0.25s;
    }
    .aj-stat-tile:hover { box-shadow: var(--aj-shadow-hover); }
    .aj-stat-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .aj-stat-icon-navy  { background: #EEF3FA; color: var(--aj-navy); }
    .aj-stat-icon-gold  { background: #FDF4E7; color: var(--aj-gold); }
    .aj-stat-icon-green { background: #ECFDF5; color: #059669; }
    .aj-stat-val {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        font-weight: 700;
        color: var(--aj-navy);
        line-height: 1;
        margin: 0;
    }
    .aj-stat-label {
        font-size: 0.75rem;
        font-weight: 500;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--aj-muted);
        margin-top: 0.2rem;
    }

    /* ── Reservation row card ── */
    .aj-res-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.1rem 1.75rem;
        border-bottom: 1px solid #F2EFE9;
        transition: background 0.15s;
        flex-wrap: wrap;
    }
    .aj-res-row:last-child { border-bottom: none; }
    .aj-res-row:hover { background: #FAFAF7; }
    .aj-res-num {
        font-family: 'Outfit', sans-serif;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--aj-navy);
        min-width: 52px;
    }
    .aj-res-voyage {
        flex: 1;
        min-width: 0;
        font-size: 0.9rem;
        color: var(--aj-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .aj-res-date {
        font-size: 0.78rem;
        color: var(--aj-muted);
        white-space: nowrap;
    }
    .aj-res-actions { margin-left: auto; flex-shrink: 0; }
    .aj-note-item {
        display: flex;
        gap: 0.85rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #F2EFE9;
    }
    .aj-note-item:last-child { border-bottom: none; }
    .aj-note-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-top: 0.45rem;
        flex-shrink: 0;
        background: var(--aj-gold);
    }
    .aj-mini-link {
        color: var(--aj-navy);
        text-decoration: none;
        font-weight: 600;
    }
    .aj-mini-link:hover { color: var(--aj-gold); }

    @media (max-width: 576px) {
        .aj-hero-card { padding: 1.75rem 1.25rem 1.5rem; }
        .aj-res-voyage { display: none; }
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php
    $name = $client->full_name ?: trim(($client->first_name ?? '').' '.($client->last_name ?? '')) ?: 'Client';
    $initials = collect(explode(' ', $name))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->implode('');

    $totalRes   = $recentReservations->count();
    $confirmed  = $recentReservations->filter(fn($r) => in_array($r->status ?? '', ['confirmed','paid','validée','confirmée']))->count();
    $pending    = $recentReservations->filter(fn($r) => in_array($r->status ?? '', ['pending','en_attente','en attente']))->count();

    $statusMap = [
        'confirmed'  => 'aj-badge-confirmed',
        'paid'       => 'aj-badge-confirmed',
        'validée'    => 'aj-badge-confirmed',
        'confirmée'  => 'aj-badge-confirmed',
        'pending'    => 'aj-badge-pending',
        'en_attente' => 'aj-badge-pending',
        'cancelled'  => 'aj-badge-cancelled',
        'annulée'    => 'aj-badge-cancelled',
    ];
?>


<div class="aj-hero-card mb-4">
    <div class="d-flex align-items-center gap-3 mb-1">
        <div class="aj-avatar"><?php echo e($initials); ?></div>
        <div>
            <div class="aj-hero-label">Bienvenue</div>
            <div class="aj-hero-name"><?php echo e($name); ?></div>
        </div>
    </div>
    <div class="aj-hero-meta">
        <?php if($client->email): ?>
        <div class="aj-hero-meta-item">
            <i class="ri-mail-line"></i>
            <?php echo e($client->email); ?>

        </div>
        <?php endif; ?>
        <?php if($client->phone): ?>
        <div class="aj-hero-meta-item">
            <i class="ri-phone-line"></i>
            <?php echo e($client->phone); ?>

        </div>
        <?php endif; ?>
        <?php if($client->city): ?>
        <div class="aj-hero-meta-item">
            <i class="ri-map-pin-line"></i>
            <?php echo e($client->city); ?>

        </div>
        <?php endif; ?>
    </div>
</div>


<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="aj-stat-tile">
            <div class="aj-stat-icon aj-stat-icon-navy"><i class="ri-suitcase-3-line"></i></div>
            <div>
                <div class="aj-stat-val"><?php echo e($recentReservations->count()); ?></div>
                <div class="aj-stat-label">Réservations</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="aj-stat-tile">
            <div class="aj-stat-icon aj-stat-icon-green"><i class="ri-checkbox-circle-line"></i></div>
            <div>
                <div class="aj-stat-val"><?php echo e($confirmed); ?></div>
                <div class="aj-stat-label">Confirmées</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="aj-stat-tile">
            <div class="aj-stat-icon aj-stat-icon-gold"><i class="ri-time-line"></i></div>
            <div>
                <div class="aj-stat-val"><?php echo e($pending); ?></div>
                <div class="aj-stat-label">En attente</div>
            </div>
        </div>
    </div>
</div>


<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="aj-card h-100">
            <div class="aj-card-header" style="padding: 1.5rem 1.75rem 1rem;">
                <div><h2 class="aj-card-title">Mes Group Deals</h2></div>
            </div>
            <?php if(($recentGroupDeals ?? collect())->isEmpty()): ?>
                <div class="aj-empty">
                    <div class="aj-empty-icon"><i class="ri-group-line"></i></div>
                    <p>Aucune participation Group Deal pour le moment.</p>
                </div>
            <?php else: ?>
                <?php $__currentLoopData = $recentGroupDeals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="aj-note-item">
                        <span class="aj-note-dot"></span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark"><?php echo e($item->groupDeal?->title ?: 'Group Deal'); ?></div>
                            <div class="small text-muted">
                                <?php echo e($item->participants_count); ?> personne(s) • <?php echo e($item->status_label); ?>

                                <?php if($item->groupDeal?->current_price): ?>
                                    • <?php echo e(number_format((float) $item->groupDeal->current_price, 0, ',', ' ')); ?> DH
                                <?php endif; ?>
                            </div>
                            <?php if($item->groupDeal?->slug): ?>
                                <a class="aj-mini-link small" href="<?php echo e(route('front.group-deals.show', $item->groupDeal->slug)); ?>">Voir l'offre</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="aj-card h-100">
            <div class="aj-card-header" style="padding: 1.5rem 1.75rem 1rem;">
                <div><h2 class="aj-card-title">Notifications</h2></div>
            </div>
            <?php if(($notifications ?? collect())->isEmpty()): ?>
                <div class="aj-empty">
                    <div class="aj-empty-icon"><i class="ri-notification-3-line"></i></div>
                    <p>Aucune notification récente.</p>
                </div>
            <?php else: ?>
                <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="aj-note-item">
                        <span class="aj-note-dot" style="background: <?php echo e($notification->is_read ? '#d1d5db' : 'var(--aj-gold)'); ?>;"></span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark"><?php echo e($notification->title); ?></div>
                            <div class="small text-muted"><?php echo e($notification->message); ?></div>
                            <div class="small text-muted mt-1"><?php echo e($notification->created_at?->diffForHumans()); ?></div>
                            <?php if($notification->link): ?>
                                <a href="<?php echo e($notification->link); ?>" class="aj-mini-link small">Ouvrir</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="aj-card">
    <div class="aj-card-header" style="padding: 1.5rem 1.75rem 1rem;">
        <div>
            <h2 class="aj-card-title">Dernières réservations</h2>
        </div>
        <a href="<?php echo e(route('client.reservations.index')); ?>" class="btn-aj-outline">
            Voir tout <i class="ri-arrow-right-line"></i>
        </a>
    </div>

    <?php if($recentReservations->isEmpty()): ?>
        <div class="aj-empty">
            <div class="aj-empty-icon"><i class="ri-suitcase-3-line"></i></div>
            <strong style="color:var(--aj-navy);font-family:'Cormorant Garamond',serif;font-size:1.15rem;">Aucune réservation</strong>
            <p>Vos voyages réservés apparaîtront ici.</p>
        </div>
    <?php else: ?>
        <?php $__currentLoopData = $recentReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $statusLabel = $r->statusLabelFr();
                $statusKey   = strtolower(str_replace(' ', '_', $r->status ?? ''));
                $badgeClass  = $statusMap[$statusKey] ?? 'aj-badge-default';
            ?>
            <div class="aj-res-row">
                <span class="aj-res-num">#<?php echo e($r->id); ?></span>
                <span class="aj-res-voyage">
                    <?php echo e($r->tour?->title ?: ($r->tour_id ? 'Tour #'.$r->tour_id : 'Voyage')); ?>

                </span>
                <span class="aj-badge <?php echo e($badgeClass); ?>"><?php echo e($statusLabel); ?></span>
                <span class="aj-res-date"><?php echo e($r->created_at?->format('d/m/Y')); ?></span>
                <div class="aj-res-actions">
                    <a class="btn-aj-primary" href="<?php echo e(route('client.reservations.show', $r)); ?>" style="padding:0.38rem 1rem;font-size:0.78rem;">
                        Détail <i class="ri-arrow-right-s-line"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('client.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\client\dashboard.blade.php ENDPATH**/ ?>