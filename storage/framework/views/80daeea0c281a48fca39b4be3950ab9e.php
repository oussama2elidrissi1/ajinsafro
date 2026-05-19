<?php $__env->startSection('title', 'Tableau de bord'); ?>

<?php $__env->startPush('css'); ?>
    <link href="<?php echo e(URL::asset('css/agent-dashboard.css')); ?>" rel="stylesheet" type="text/css" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    use App\Models\Reservation;
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $displayName = $user?->name ?: 'Agent';
    $agencyLabel = $user?->branch?->name ?: 'Ajinsafro Tanger';

    $catalogueVoyageUrl = Route::has('admin.reservations.workspace')
        ? route('admin.reservations.workspace')
        : url('/admin/reservations/workspace');
?>

<div class="agent-dashboard-page">
    <section class="agent-dashboard-shell agent-dashboard-hero">
        <div class="agent-dashboard-hero__content">
            <div>
                <span class="agent-dashboard-badge"><?php echo e($agencyLabel); ?></span>
                <h1 class="agent-dashboard-title">Welcome back, <?php echo e($displayName); ?></h1>
                <p class="agent-dashboard-subtitle">Votre activité du jour, vos réservations et les actions prioritaires au même endroit.</p>
            </div>
            <div class="agent-dashboard-actions">
                <a href="<?php echo e($catalogueVoyageUrl); ?>" class="btn agent-btn agent-btn-primary agent-dashboard-actions__cta">
                    <i class="bx bx-map-alt align-middle" aria-hidden="true"></i>
                    <span>Catalogue de voyage</span>
                </a>
            </div>
        </div>
    </section>

    <section class="agent-dashboard-grid agent-dashboard-grid--kpis">
        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-briefcase-alt-2"></i></div>
            <div class="agent-kpi-label">Réservations</div>
            <div class="agent-kpi-value"><?php echo e(number_format((int) ($stats['reservations_total'] ?? 0), 0, ',', ' ')); ?></div>
        </article>

        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-check-shield"></i></div>
            <div class="agent-kpi-label">Confirmées</div>
            <div class="agent-kpi-value"><?php echo e(number_format((int) ($stats['reservations_validees'] ?? 0), 0, ',', ' ')); ?></div>
        </article>

        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-time-five"></i></div>
            <div class="agent-kpi-label">En attente</div>
            <div class="agent-kpi-value"><?php echo e(number_format((int) ($stats['reservations_en_cours'] ?? 0), 0, ',', ' ')); ?></div>
        </article>

        <article class="agent-kpi-card">
            <div class="agent-kpi-icon"><i class="bx bx-wallet"></i></div>
            <div class="agent-kpi-label">Revenus</div>
            <div class="agent-kpi-value"><?php echo e(number_format((float) ($stats['revenue_generated'] ?? 0), 0, ',', ' ')); ?> DH</div>
        </article>
    </section>

    <section class="agent-dashboard-grid agent-dashboard-grid--content">
        <div class="agent-panel agent-panel--wide">
            <div class="agent-panel-header">
                <div>
                    <h2>Mes dernières réservations</h2>
                    <p>Une vue rapide sur les dossiers les plus récents.</p>
                </div>

                <form method="GET" action="<?php echo e(route('agent.dashboard')); ?>" class="agent-filter-form">
                    <label for="scope" class="visually-hidden">Filtrer les réservations</label>
                    <select name="scope" id="scope" class="form-select agent-select" <?php echo e($isManager ? '' : 'disabled'); ?>>
                        <option value="mine" <?php echo e(($scope ?? 'mine') === 'mine' ? 'selected' : ''); ?>>Mes réservations</option>
                        <?php if($isManager): ?>
                            <option value="team" <?php echo e(($scope ?? 'mine') === 'team' ? 'selected' : ''); ?>>Mon équipe</option>
                        <?php endif; ?>
                    </select>
                    <?php if (! ($isManager)): ?>
                        <input type="hidden" name="scope" value="mine">
                    <?php endif; ?>
                    <button type="submit" class="btn agent-btn agent-btn-secondary">Filtrer</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table agent-table mb-0">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Voyage</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $recentReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $clientName = trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? ''));
                                $status = $reservation->status;
                                $statusLabel = match ($status) {
                                    Reservation::STATUS_VALIDEE => 'Confirmée',
                                    Reservation::STATUS_EN_COURS => 'En attente',
                                    Reservation::STATUS_ANNULEE => 'Annulée',
                                    default => (string) $status,
                                };
                                $statusClass = match ($status) {
                                    Reservation::STATUS_VALIDEE => 'is-confirmed',
                                    Reservation::STATUS_EN_COURS => 'is-pending',
                                    Reservation::STATUS_ANNULEE => 'is-cancelled',
                                    default => 'is-neutral',
                                };
                                $detailUrl = Route::has('admin.reservations.show') ? route('admin.reservations.show', $reservation) : '#';
                                $displayDate = optional($reservation->travelDate?->date)->format('d/m/Y') ?: optional($reservation->created_at)->format('d/m/Y');
                            ?>
                            <tr>
                                <td data-label="Client"><?php echo e($clientName !== '' ? $clientName : 'Client non renseigné'); ?></td>
                                <td data-label="Voyage"><?php echo e($reservation->tour?->name ?: 'Voyage non renseigné'); ?></td>
                                <td data-label="Date"><?php echo e($displayDate); ?></td>
                                <td data-label="Statut"><span class="agent-status-badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></td>
                                <td data-label="Action" class="text-end">
                                    <a href="<?php echo e($detailUrl); ?>" class="agent-table-link">Voir</a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">Aucune réservation récente à afficher.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <aside class="agent-panel agent-panel--side">
            <div class="agent-panel-header agent-panel-header--stacked">
                <div>
                    <h2>Aujourd'hui</h2>
                    <p>Les chiffres à surveiller en priorité.</p>
                </div>
            </div>

            <div class="agent-today-metrics">
                <div class="agent-today-metric">
                    <span>Réservations du jour</span>
                    <strong><?php echo e(number_format((int) ($todayStats['reservations_today'] ?? 0), 0, ',', ' ')); ?></strong>
                </div>
                <div class="agent-today-metric">
                    <span>En attente aujourd'hui</span>
                    <strong><?php echo e(number_format((int) ($todayStats['pending_today'] ?? 0), 0, ',', ' ')); ?></strong>
                </div>
            </div>

            <div class="agent-notifications">
                <?php $__currentLoopData = ($todayStats['notifications'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="agent-notification-item"><?php echo e($notification); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </aside>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master-ajinsafro', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\agent\dashboard.blade.php ENDPATH**/ ?>