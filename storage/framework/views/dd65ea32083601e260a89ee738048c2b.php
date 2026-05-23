<?php $__env->startSection('title', 'Mes réservations'); ?>
<?php $__env->startSection('page_title', 'Mes réservations'); ?>

<?php $__env->startSection('page_styles'); ?>
<style>
    .aj-res-id-chip {
        display: inline-flex;
        align-items: center;
        font-family: 'Outfit', monospace;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--aj-navy);
        background: #EEF3FA;
        border-radius: 6px;
        padding: 0.22rem 0.6rem;
        letter-spacing: 0.03em;
    }
    .aj-voyage-name {
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--aj-text);
        max-width: 260px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }
    .aj-date-cell {
        font-size: 0.82rem;
        color: var(--aj-muted);
        white-space: nowrap;
    }
    /* Override bootstrap pagination */
    .pagination .page-link {
        font-family: 'Outfit', sans-serif;
        font-size: 0.8rem;
        color: var(--aj-navy);
        border-color: var(--aj-border);
        border-radius: 6px !important;
        margin: 0 2px;
    }
    .pagination .page-item.active .page-link {
        background: var(--aj-navy);
        border-color: var(--aj-navy);
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php
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

<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="aj-section-heading">Mes réservations</h1>
        <p class="aj-section-sub">Historique complet de vos voyages réservés</p>
    </div>
</div>

<div class="aj-card">

    <?php if($reservations->isEmpty()): ?>
        <div class="aj-empty">
            <div class="aj-empty-icon"><i class="ri-calendar-line"></i></div>
            <strong style="color:var(--aj-navy);font-family:'Cormorant Garamond',serif;font-size:1.15rem;">Aucune réservation</strong>
            <p>Vous n'avez encore effectué aucune réservation.</p>
        </div>

    <?php else: ?>
        <div class="table-responsive" style="margin: 0;">
            <table class="aj-table">
                <thead>
                    <tr>
                        <th style="padding-left:1.75rem;">Réf.</th>
                        <th>Voyage</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th style="text-align:right;padding-right:1.75rem;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $reservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $statusKey  = strtolower(str_replace(' ', '_', $r->status ?? ''));
                            $badgeClass = $statusMap[$statusKey] ?? 'aj-badge-default';
                        ?>
                        <tr>
                            <td style="padding-left:1.75rem;">
                                <span class="aj-res-id-chip">#<?php echo e($r->id); ?></span>
                            </td>
                            <td>
                                <span class="aj-voyage-name">
                                    <?php echo e($r->tour?->title ?: ($r->tour_id ? 'Tour #'.$r->tour_id : '—')); ?>

                                </span>
                            </td>
                            <td>
                                <span class="aj-badge <?php echo e($badgeClass); ?>"><?php echo e($r->statusLabelFr()); ?></span>
                            </td>
                            <td>
                                <span class="aj-date-cell"><?php echo e($r->created_at?->format('d/m/Y')); ?></span>
                            </td>
                            <td style="text-align:right;padding-right:1.75rem;">
                                <a class="btn-aj-primary" href="<?php echo e(route('client.reservations.show', $r)); ?>" style="padding:0.4rem 1rem;font-size:0.78rem;">
                                    Détail <i class="ri-arrow-right-s-line"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <?php if($reservations->hasPages()): ?>
            <div style="padding:1.25rem 1.75rem;border-top:1px solid var(--aj-border);">
                <?php echo e($reservations->links()); ?>

            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('client.layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\client\reservations\index.blade.php ENDPATH**/ ?>