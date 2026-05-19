
<?php $__env->startSection('title'); ?> DÃ©part Group Deal â€” <?php echo e($departure->start_date?->format('d/m/Y')); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="page-title mb-0 font-size-18">
                DÃ©part â€” <?php echo e($departure->voyage?->name); ?>

                <small class="text-muted fw-normal ms-2"><?php echo e($departure->start_date?->format('d/m/Y')); ?></small>
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.group-deals.trips.index')); ?>">Group Deals</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.group-deals.departures.index')); ?>">DÃ©parts</a></li>
                    <li class="breadcrumb-item active">DÃ©tail</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if($errors->any()): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo e($errors->first()); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>


<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Participants confirmÃ©s</p>
                <h3 class="mb-0 text-primary"><?php echo e($stats['confirmed_count']); ?></h3>
                <small class="text-muted">seuil : <?php echo e($stats['threshold']); ?></small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Prix actif</p>
                <h3 class="mb-0 text-success">
                    <?php echo e($stats['current_price'] ? number_format($stats['current_price'], 0, ',', ' ').' â‚¬' : 'â€”'); ?>

                </h3>
                <?php if($stats['active_tier']): ?>
                    <small class="text-muted"><?php echo e($stats['active_tier']->label ?? 'Palier actif'); ?></small>
                <?php else: ?>
                    <small class="text-muted">Prix de base</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Prochain palier</p>
                <?php if($stats['next_tier']): ?>
                    <h3 class="mb-0"><?php echo e(number_format($stats['next_tier']->price_per_person, 0, ',', ' ')); ?> â‚¬</h3>
                    <small class="text-muted">Ã  <?php echo e($stats['next_tier']->min_participants); ?> participants</small>
                <?php else: ?>
                    <h3 class="mb-0 text-muted">â€”</h3>
                    <small class="text-muted">Dernier palier atteint</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Statut garanti</p>
                <?php if($departure->is_guaranteed): ?>
                    <span class="badge bg-success fs-6 px-3 py-2">Garanti</span>
                    <?php if($departure->guaranteed_at): ?>
                        <br><small class="text-muted"><?php echo e($departure->guaranteed_at->format('d/m/Y')); ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">Non garanti</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-semibold">Progression vers le seuil garanti</span>
            <span class="small text-muted"><?php echo e($stats['confirmed_count']); ?> / <?php echo e($stats['threshold']); ?> participants</span>
        </div>
        <div class="progress" style="height: 18px; border-radius: 9px;">
            <div class="progress-bar <?php echo e($departure->is_guaranteed ? 'bg-success' : 'bg-warning'); ?>"
                 style="width: <?php echo e($stats['progression_pct']); ?>%; border-radius: 9px; transition: width .4s ease;">
                <?php if($stats['progression_pct'] >= 15): ?>
                    <?php echo e($stats['progression_pct']); ?>%
                <?php endif; ?>
            </div>
        </div>
        <?php if(! $departure->is_guaranteed && $stats['threshold'] > $stats['confirmed_count']): ?>
            <p class="text-muted small mt-2 mb-0">
                Il manque <strong><?php echo e($stats['threshold'] - $stats['confirmed_count']); ?></strong> participant(s) pour que ce dÃ©part soit garanti.
            </p>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">

    
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0">Participants (<?php echo e($stats['confirmed_count']); ?> confirmÃ©s)</h6>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddParticipant">
                    <i class="bx bx-plus"></i> Ajouter
                </button>
            </div>
            <div class="card-body p-0">
                <?php if($departure->groupDealParticipants->isEmpty()): ?>
                    <div class="p-4 text-center text-muted small">Aucun participant pour ce dÃ©part.</div>
                <?php else: ?>
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Client</th>
                                <th>Inscrit le</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $__currentLoopData = $departure->groupDealParticipants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <?php if($p->client): ?>
                                        <strong><?php echo e($p->client->first_name); ?> <?php echo e($p->client->last_name); ?></strong>
                                        <br><small class="text-muted"><?php echo e($p->client->email); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">â€”</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?php echo e($p->joined_at->format('d/m/Y H:i')); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo e(match($p->status) {
                                        'confirmed' => 'success',
                                        'pending'   => 'warning text-dark',
                                        'cancelled' => 'danger',
                                        default     => 'secondary'
                                    }); ?>">
                                        <?php echo e(match($p->status) {
                                            'confirmed' => 'ConfirmÃ©',
                                            'pending'   => 'En attente',
                                            'cancelled' => 'AnnulÃ©',
                                            default     => $p->status
                                        }); ?>

                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Action
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php $__currentLoopData = ['confirmed' => 'Confirmer', 'pending' => 'Mettre en attente', 'cancelled' => 'Annuler']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php if($p->status !== $s): ?>
                                                    <li>
                                                        <form method="POST"
                                                              action="<?php echo e(route('admin.group-deals.departures.participants.update', [$departure, $p])); ?>">
                                                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                                            <input type="hidden" name="status" value="<?php echo e($s); ?>">
                                                            <button type="submit" class="dropdown-item"><?php echo e($label); ?></button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Informations</h6>
            </div>
            <div class="card-body small">
                <table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted">Voyage</td><td><strong><?php echo e($departure->voyage?->name); ?></strong></td></tr>
                    <tr><td class="text-muted">Date dÃ©but</td><td><?php echo e($departure->start_date?->format('d/m/Y') ?? 'â€”'); ?></td></tr>
                    <tr><td class="text-muted">Date fin</td><td><?php echo e($departure->end_date?->format('d/m/Y') ?? 'â€”'); ?></td></tr>
                    <tr><td class="text-muted">CapacitÃ© totale</td><td><?php echo e($departure->total_capacity ?? 'â€”'); ?></td></tr>
                    <tr><td class="text-muted">Seuil garanti</td><td><?php echo e($departure->guaranteed_threshold); ?></td></tr>
                    <tr><td class="text-muted">Statut dÃ©part</td><td><?php echo e($departure->status_label); ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Actions</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <form method="POST" action="<?php echo e(route('admin.group-deals.departures.recalculate', $departure)); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bx bx-refresh me-1"></i> Recalculer prix & statut
                    </button>
                </form>
                <a href="<?php echo e(route('admin.circuits.voyages.show', $departure->voyage_id)); ?>"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-link-external me-1"></i> Voir le voyage
                </a>
            </div>
        </div>
    </div>

</div>


<div class="modal fade" id="modalAddParticipant" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('admin.group-deals.departures.participants.store', $departure)); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h6 class="modal-title">Ajouter un participant</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">ID Client <span class="text-danger">*</span></label>
                        <input type="number" name="client_id" class="form-control form-control-sm"
                               required placeholder="ID du client">
                        <div class="form-text">Entrez l'identifiant numÃ©rique du client.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">ID RÃ©servation (optionnel)</label>
                        <input type="number" name="reservation_id" class="form-control form-control-sm"
                               placeholder="ID de la rÃ©servation liÃ©e">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\group-deals\departures\show.blade.php ENDPATH**/ ?>