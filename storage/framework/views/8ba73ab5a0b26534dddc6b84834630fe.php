<?php $__env->startSection('title', 'Réservation #'.$reservation->id); ?>
<?php $__env->startSection('page_title', 'Réservation #'.$reservation->id); ?>

<?php $__env->startSection('page_styles'); ?>
<style>
    .aj-res-hero {
        background: linear-gradient(135deg, var(--aj-navy) 0%, #0d2847 100%);
        border-radius: var(--aj-radius);
        padding: 1.75rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(201,145,61,0.2);
        box-shadow: var(--aj-shadow);
        position: relative;
        overflow: hidden;
    }
    .aj-res-hero::before {
        content: '';
        position: absolute;
        right: -20px; top: -20px;
        width: 140px; height: 140px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(201,145,61,0.12) 0%, transparent 70%);
        pointer-events: none;
    }
    .aj-res-hero-id {
        font-family: 'Outfit', sans-serif;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--aj-gold-light);
        margin-bottom: 0.35rem;
    }
    .aj-res-hero-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.65rem;
        font-weight: 600;
        color: #fff;
        margin: 0;
        letter-spacing: 0.01em;
    }
    .aj-passenger-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.9rem 1.75rem;
        border-bottom: 1px solid #F2EFE9;
        transition: background 0.15s;
    }
    .aj-passenger-row:last-child { border-bottom: none; }
    .aj-passenger-row:hover { background: #FAFAF7; }
    .aj-pax-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: #EEF3FA;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--aj-navy);
        flex-shrink: 0;
        letter-spacing: 0.04em;
    }
    .aj-pax-name {
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--aj-text);
        flex: 1;
    }
    .aj-pax-type {
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--aj-muted);
        background: #F3F4F6;
        border-radius: 50px;
        padding: 0.2rem 0.65rem;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php
    $statusKey  = strtolower(str_replace(' ', '_', $reservation->status ?? ''));
    $statusMap  = [
        'confirmed'  => 'aj-badge-confirmed',
        'paid'       => 'aj-badge-confirmed',
        'validée'    => 'aj-badge-confirmed',
        'confirmée'  => 'aj-badge-confirmed',
        'pending'    => 'aj-badge-pending',
        'en_attente' => 'aj-badge-pending',
        'cancelled'  => 'aj-badge-cancelled',
        'annulée'    => 'aj-badge-cancelled',
    ];
    $badgeClass = $statusMap[$statusKey] ?? 'aj-badge-default';
    $voyageTitle = $reservation->tour?->title ?: ($reservation->tour_id ? 'Tour #'.$reservation->tour_id : 'Voyage');
    $departureDate = $reservation->travelDate?->travel_date
        ? $reservation->travelDate->travel_date->format('d/m/Y')
        : ($reservation->attributes['departure_date'] ?? null);
?>


<div class="aj-res-hero">
    <div>
        <div class="aj-res-hero-id">Réservation · #<?php echo e($reservation->id); ?></div>
        <div class="aj-res-hero-title"><?php echo e($voyageTitle); ?></div>
    </div>
    <span class="aj-badge <?php echo e($badgeClass); ?>" style="font-size:0.82rem;padding:0.45rem 1.1rem;">
        <?php echo e($reservation->statusLabelFr()); ?>

    </span>
</div>

<div class="row g-4">

    
    <div class="col-lg-4">
        <div class="aj-card h-100">
            <div style="padding:1.5rem 1.75rem 0.75rem;">
                <h2 class="aj-card-title">Résumé</h2>
            </div>
            <div class="aj-card-body" style="padding-top:0.5rem;">

                <div class="aj-info-row">
                    <div class="aj-info-label">Statut</div>
                    <div class="aj-info-value">
                        <span class="aj-badge <?php echo e($badgeClass); ?>"><?php echo e($reservation->statusLabelFr()); ?></span>
                    </div>
                </div>

                <div class="aj-info-row">
                    <div class="aj-info-label">Voyage</div>
                    <div class="aj-info-value"><?php echo e($voyageTitle); ?></div>
                </div>

                <?php if($departureDate): ?>
                <div class="aj-info-row">
                    <div class="aj-info-label">Départ</div>
                    <div class="aj-info-value">
                        <i class="ri-calendar-line me-1" style="color:var(--aj-gold);font-size:0.85rem;"></i>
                        <?php echo e($departureDate); ?>

                    </div>
                </div>
                <?php endif; ?>

                <div class="aj-info-row">
                    <div class="aj-info-label">Créée le</div>
                    <div class="aj-info-value"><?php echo e($reservation->created_at?->format('d/m/Y à H:i')); ?></div>
                </div>

            </div>
        </div>
    </div>

    
    <div class="col-lg-8">
        <div class="aj-card h-100">
            <div style="padding:1.5rem 1.75rem 0.75rem;display:flex;align-items:center;justify-content:space-between;">
                <h2 class="aj-card-title">Voyageurs</h2>
                <?php if($reservation->passengers->isNotEmpty()): ?>
                    <span style="font-size:0.78rem;color:var(--aj-muted);"><?php echo e($reservation->passengers->count()); ?> personne(s)</span>
                <?php endif; ?>
            </div>

            <?php if($reservation->passengers->isEmpty()): ?>
                <div class="aj-empty" style="padding:2.5rem 1.75rem;">
                    <div class="aj-empty-icon"><i class="ri-group-line"></i></div>
                    <p>Aucun voyageur renseigné.</p>
                </div>
            <?php else: ?>
                <?php $__currentLoopData = $reservation->passengers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $pName     = trim(($p->first_name ?? '').' '.($p->last_name ?? '')) ?: 'Voyageur';
                        $pInitials = collect(explode(' ', $pName))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->implode('');
                    ?>
                    <div class="aj-passenger-row">
                        <div class="aj-pax-avatar"><?php echo e($pInitials); ?></div>
                        <div class="aj-pax-name"><?php echo e($pName); ?></div>
                        <?php if($p->type ?? null): ?>
                            <span class="aj-pax-type"><?php echo e($p->type); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="mt-4">
    <a href="<?php echo e(route('client.reservations.index')); ?>" class="btn-aj-outline">
        <i class="ri-arrow-left-line"></i> Retour aux réservations
    </a>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('client.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\client\reservations\show.blade.php ENDPATH**/ ?>