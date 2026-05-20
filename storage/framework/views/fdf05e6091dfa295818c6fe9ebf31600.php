<?php
    use App\Models\Reservation;
    use App\Services\ReservationHubTableProfile;

    $hubTableMode = $hubTableMode ?? ReservationHubTableProfile::MODE_OPERATIONS;
    $hubVoyageFiltered = $hubVoyageFiltered ?? false;
    $filterChannel = $filterChannel ?? null;
    $isClientChannel = $filterChannel === 'client';
    $reservationVisibility = is_array($reservationVisibility ?? null) ? $reservationVisibility : [];
    $limitedReservationPresentation = (bool) ($reservationVisibility['limited_presentation'] ?? false);
    $canViewReservationFinancial = (bool) ($reservationVisibility['view_financial'] ?? false);
    $canViewAssignmentContext = (bool) ($reservationVisibility['view_assignment_context'] ?? false);
    $canViewSensitive = (bool) ($reservationVisibility['view_sensitive'] ?? false);
    $showCrossAgencyBranchCol = $hubVoyageFiltered && ($hubTableMode === ReservationHubTableProfile::MODE_AGENCY || $hubTableMode === ReservationHubTableProfile::MODE_OPERATIONS);
    $hubColCount = $limitedReservationPresentation
        ? 7
        : ($isClientChannel
            ? ($canViewReservationFinancial ? 10 : 9)
            : app(ReservationHubTableProfile::class)->tableColumnCount($hubTableMode, $hubVoyageFiltered));

    $sourceLabelFr = static function (string $src): string {
        return match ($src) {
            'agent_id' => 'Agent affecte (agent_id)',
            'created_by' => 'Compte creation (created_by)',
            'created_by_user_id' => 'Compte saisie (created_by_user_id)',
            default => '',
        };
    };
?>

<?php $__empty_1 = true; $__currentLoopData = $reservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $highlightReservationId = $highlightReservationId ?? 0;
        $auditUser = $reservation->resolveAuditCreatorUser();
        $opUser = $reservation->resolveOperationalActorUser();
        $opSrc = $reservation->operationalActorDataSourceLabel();
        $statusClass = match ($reservation->status) {
            Reservation::STATUS_EN_COURS, Reservation::STATUS_PENDING => 'badge res-status-badge res-status-badge--pending',
            Reservation::STATUS_SHARED_ROOM_PENDING => 'badge res-status-badge res-status-badge--pairing',
            Reservation::STATUS_SHARED_ROOM_PAIRED => 'badge res-status-badge res-status-badge--paired',
            Reservation::STATUS_VALIDEE, Reservation::STATUS_CONFIRMED => 'badge res-status-badge res-status-badge--confirmed',
            Reservation::STATUS_ANNULEE, Reservation::STATUS_CANCELLED => 'badge res-status-badge res-status-badge--cancelled',
            default => 'badge res-status-badge res-status-badge--neutral',
        };

        $clientName = $reservation->client
            ? ($reservation->client->full_name ?: '-')
            : (trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '-');
        $clientCode = $reservation->client?->client_code ?: null;
        $reservationCode = $reservation->catalog_source_code ?: ('RES-' . str_pad((string) $reservation->id, 6, '0', STR_PAD_LEFT));
        $offerName = $reservation->offer?->name ?? '-';
        $agencyLabel = $reservation->agency_label ?? '-';
        $names = $reservation->passengers->map(fn ($p) => trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')))->filter()->values();
        $passengerPreview = $names->isEmpty() ? '-' : $names->take(3)->join(', ');
        $pendingSharedSeats = $reservation->status === Reservation::STATUS_SHARED_ROOM_PENDING
            ? (int) $reservation->reservationRooms
                ->filter(function ($rr) {
                    $mode = (string) ($rr->room_mode ?? '');
                    $state = (string) ($rr->shared_room_status ?? 'pending');
                    if ($mode === 'shared_double' && $state !== 'paired') {
                        return true;
                    }
                    return $mode === '' && (string) ($rr->source_room_type ?? '') === 'double' && (int) ($rr->passenger_count ?? 0) === 1;
                })
                ->sum(fn ($rr) => (int) ($rr->passenger_count ?? 0))
            : 0;
        $depDate = $reservation->travelDate?->date ? $reservation->travelDate->date->format('d/m/Y') : '-';
        $createdAt = optional($reservation->created_at)->format('d/m/Y H:i');
        $paymentType = $reservation->payment_type ?: null;
        $salesManagerName = $reservation->salesManager?->name ?: null;
    ?>
    <tr class="<?php echo \Illuminate\Support\Arr::toCssClasses(['res-hub-row-highlight' => $highlightReservationId && (int) $reservation->id === (int) $highlightReservationId]); ?>"
        <?php if($highlightReservationId && (int) $reservation->id === (int) $highlightReservationId): ?> id="res-hub-highlight-row" <?php endif; ?>>
        <td class="ps-3 text-muted small fw-semibold"><?php echo e($reservation->id); ?></td>

        <td>
            <div class="fw-semibold text-dark"><?php echo e($clientName); ?></div>
            <div class="small text-muted d-flex flex-wrap align-items-center gap-2">
                <?php if($clientCode): ?>
                    <span><?php echo e($clientCode); ?></span>
                <?php endif; ?>
                <?php if($isClientChannel): ?>
                    <span><?php echo e($reservationCode); ?></span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Client web</span>
                <?php endif; ?>
            </div>
            <?php if(!$clientCode && !$isClientChannel): ?>
                <div class="small text-muted">-</div>
            <?php endif; ?>
        </td>

        <td>
            <div class="fw-semibold text-dark"><?php echo e($offerName); ?></div>
            <div class="small text-muted">Reservation #<?php echo e($reservation->id); ?></div>
        </td>

        <?php if(!$isClientChannel && !$limitedReservationPresentation): ?>
            <?php if($hubTableMode === ReservationHubTableProfile::MODE_NETWORK): ?>
                <td>
                    <div class="small fw-semibold text-dark"><?php echo e($agencyLabel); ?></div>
                </td>
            <?php elseif($showCrossAgencyBranchCol): ?>
                <td>
                    <div class="small fw-semibold text-dark"><?php echo e($agencyLabel); ?></div>
                </td>
            <?php endif; ?>
        <?php endif; ?>

        <td>
            <div class="fw-semibold text-dark"><?php echo e($depDate); ?></div>
            <?php if($reservation->travel_date_id): ?>
                <div class="small text-muted">TravelDate #<?php echo e($reservation->travel_date_id); ?></div>
            <?php endif; ?>
        </td>

        <td>
            <div class="small text-dark"><?php echo e($passengerPreview); ?></div>
            <?php if($names->count() > 3): ?>
                <div class="small text-muted">+<?php echo e($names->count() - 3); ?> autre(s)</div>
            <?php elseif($names->isEmpty()): ?>
                <div class="small text-muted">Aucun passager detaille</div>
            <?php endif; ?>
        </td>

        <?php if($isClientChannel): ?>
            <?php if($canViewReservationFinancial): ?>
                <td>
                    <?php if($paymentType): ?>
                        <span class="badge bg-light text-dark"><?php echo e($paymentType); ?></span>
                    <?php else: ?>
                        <span class="text-muted small">-</span>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <td>
                <span class="<?php echo e($statusClass); ?>"><?php echo e($reservation->statusLabelFr()); ?></span>
                <?php if($pendingSharedSeats > 0): ?>
                    <div class="small text-muted mt-1"><?php echo e($pendingSharedSeats); ?> place(s) demi-double en attente</div>
                <?php endif; ?>
            </td>
            <?php if($canViewAssignmentContext): ?>
                <td>
                    <div class="small text-dark"><?php echo e($createdAt ?: '-'); ?></div>
                </td>
                <td>
                    <?php if($salesManagerName): ?>
                        <div class="fw-semibold text-dark"><?php echo e($salesManagerName); ?></div>
                    <?php else: ?>
                        <span class="badge bg-light text-secondary border">Non assigne</span>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        <?php else: ?>
            <?php if($limitedReservationPresentation): ?>
                <td>
                    <span class="<?php echo e($statusClass); ?>"><?php echo e($reservation->statusLabelFr()); ?></span>
                    <?php if($pendingSharedSeats > 0): ?>
                        <div class="small text-muted mt-1"><?php echo e($pendingSharedSeats); ?> place(s) demi-double en attente</div>
                    <?php endif; ?>
                </td>
            <?php elseif($hubTableMode === ReservationHubTableProfile::MODE_OPERATIONS): ?>
                <td>
                    <span class="<?php echo e($statusClass); ?>"><?php echo e($reservation->statusLabelFr()); ?></span>
                    <?php if($pendingSharedSeats > 0): ?>
                        <div class="small text-muted mt-1"><?php echo e($pendingSharedSeats); ?> place(s) demi-double en attente</div>
                    <?php endif; ?>
                </td>
                <?php if($canViewReservationFinancial): ?>
                    <td>
                        <?php if($paymentType): ?>
                            <span class="badge bg-light text-dark"><?php echo e($paymentType); ?></span>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            <?php else: ?>
                <?php if($canViewReservationFinancial): ?>
                    <td>
                        <?php if($paymentType): ?>
                            <span class="badge bg-light text-dark"><?php echo e($paymentType); ?></span>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <td>
                    <span class="<?php echo e($statusClass); ?>"><?php echo e($reservation->statusLabelFr()); ?></span>
                    <?php if($pendingSharedSeats > 0): ?>
                        <div class="small text-muted mt-1"><?php echo e($pendingSharedSeats); ?> place(s) demi-double en attente</div>
                    <?php endif; ?>
                </td>
            <?php endif; ?>

            <?php if(!$limitedReservationPresentation && $hubTableMode !== ReservationHubTableProfile::MODE_OPERATIONS && $canViewAssignmentContext): ?>
                <td>
                    <div class="small text-dark"><?php echo e($createdAt ?: '-'); ?></div>
                </td>
            <?php endif; ?>

            <?php if(!$limitedReservationPresentation && $hubTableMode === ReservationHubTableProfile::MODE_NETWORK && $canViewAssignmentContext): ?>
                <td class="small">
                    <?php if($auditUser): ?>
                        <div class="fw-semibold text-dark"><?php echo e($auditUser->name); ?></div>
                        <?php if($auditUser->email): ?>
                            <div class="text-muted"><?php echo e($auditUser->email); ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td class="small">
                    <?php if($opUser): ?>
                        <div class="fw-semibold text-dark"><?php echo e($opUser->name); ?></div>
                        <?php if($opSrc !== ''): ?>
                            <div class="text-muted"><?php echo e($sourceLabelFr($opSrc)); ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td class="small">
                    <?php if($reservation->salesManager): ?>
                        <div class="fw-semibold text-dark"><?php echo e($reservation->salesManager->name); ?></div>
                        <div class="text-muted">Chef commercial</div>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        <?php endif; ?>

        <td class="text-end pe-3">
            <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.view')): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-res-hub-detail" title="Details" data-res-id="<?php echo e($reservation->id); ?>">
                        <i class="bx bx-info-circle"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-res-hub-pax" title="Participants" data-res-id="<?php echo e($reservation->id); ?>">
                        <i class="bx bx-group"></i>
                    </button>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.edit')): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-res-hub-edit" title="Modifier" data-res-id="<?php echo e($reservation->id); ?>">
                        <i class="bx bx-pencil"></i>
                    </button>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.update')): ?>
                    <?php if($reservation->status !== Reservation::STATUS_VALIDEE && $reservation->status !== Reservation::STATUS_CONFIRMED): ?>
                        <form action="<?php echo e(route('admin.reservations.validate', $reservation)); ?>" method="post" class="d-inline res-hub-validate-form">
                            <?php echo csrf_field(); ?>
                            <button
                                type="button"
                                class="btn btn-sm btn-success btn-res-hub-validate"
                                title="Valider"
                                data-res-id="<?php echo e($reservation->id); ?>"
                                data-res-client="<?php echo e($clientName); ?>"
                                data-res-offer="<?php echo e($offerName); ?>"
                                data-res-status="<?php echo e($reservation->statusLabelFr()); ?>"
                                data-res-date="<?php echo e($depDate); ?>"
                            >
                                <i class="bx bx-check"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if($reservation->status === Reservation::STATUS_SHARED_ROOM_PENDING): ?>
                        <form action="<?php echo e(route('admin.reservations.pair-shared-room', $reservation)); ?>" method="post" class="d-inline">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-sm btn-outline-info" title="Jumeler demi-double">
                                <i class="bx bx-link"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.destroy')): ?>
                    <form action="<?php echo e(route('admin.reservations.destroy', $reservation)); ?>" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette reservation ?');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                            <i class="bx bx-trash"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <tr>
        <td colspan="<?php echo e($hubColCount); ?>" class="text-center text-muted py-5">Aucune reservation trouvee.</td>
    </tr>
<?php endif; ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\partials\hub-table-rows.blade.php ENDPATH**/ ?>