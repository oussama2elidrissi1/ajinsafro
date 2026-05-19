
<?php $__env->startSection('title'); ?> Group Deal â€” <?php echo e($voyage->name); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="page-title mb-0 font-size-18"><?php echo e($voyage->name); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.group-deals.trips.index')); ?>">Group Deals</a></li>
                    <li class="breadcrumb-item active"><?php echo e($voyage->name); ?></li>
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

<div class="row g-3">

    
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <span class="text-muted small">Destination :</span>
                    <strong class="ms-1"><?php echo e($voyage->destination ?? 'â€”'); ?></strong>
                    <span class="ms-3 text-muted small">DurÃ©e :</span>
                    <strong class="ms-1"><?php echo e($voyage->duration_text ?? 'â€”'); ?></strong>
                    <span class="ms-3 text-muted small">Prix de base :</span>
                    <strong class="ms-1"><?php echo e($voyage->price_from ? number_format($voyage->price_from, 0, ',', ' ').' â‚¬' : 'â€”'); ?></strong>
                </div>
                <a href="<?php echo e(route('admin.circuits.voyages.edit', $voyage->id)); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-edit me-1"></i> Modifier le voyage
                </a>
            </div>
        </div>
    </div>

    
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0">Paliers de prix</h6>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddTier">
                    <i class="bx bx-plus"></i> Ajouter
                </button>
            </div>
            <div class="card-body p-0">
                <?php if($voyage->pricingTiers->isEmpty()): ?>
                    <div class="p-4 text-center text-muted small">
                        Aucun palier configurÃ©. Ajoutez au moins un palier pour activer la tarification progressive.
                    </div>
                <?php else: ?>
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Min. participants</th>
                                <th>Prix / pers.</th>
                                <th>Label</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $__currentLoopData = $voyage->pricingTiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><strong>â‰¥ <?php echo e($tier->min_participants); ?></strong> pers.</td>
                                <td class="text-success fw-semibold"><?php echo e(number_format($tier->price_per_person, 0, ',', ' ')); ?> â‚¬</td>
                                <td class="text-muted small"><?php echo e($tier->label ?? 'â€”'); ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary me-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditTier<?php echo e($tier->id); ?>">
                                        <i class="bx bx-edit-alt"></i>
                                    </button>
                                    <form method="POST"
                                          action="<?php echo e(route('admin.group-deals.trips.tiers.destroy', [$voyage, $tier])); ?>"
                                          class="d-inline"
                                          onsubmit="return confirm('Supprimer ce palier ?')">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0">DÃ©parts Group Deal (<?php echo e($voyage->departures->count()); ?>)</h6>
                <a href="<?php echo e(route('admin.group-deals.departures.index', ['voyage_id' => $voyage->id])); ?>"
                   class="btn btn-outline-primary btn-sm">Voir tous</a>
            </div>
            <div class="card-body p-0">
                <?php if($voyage->departures->isEmpty()): ?>
                    <div class="p-4 text-center text-muted small">
                        Aucun dÃ©part Group Deal pour ce voyage.
                        <br>Activez l'option Group Deal sur un dÃ©part depuis la <a href="<?php echo e(route('admin.circuits.voyages.show', $voyage->id)); ?>">fiche voyage</a>.
                    </div>
                <?php else: ?>
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-center">Participants</th>
                                <th class="text-center">Seuil</th>
                                <th>Prix actif</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $__currentLoopData = $voyage->departures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="small">
                                    <?php echo e($dep->start_date?->format('d/m/Y') ?? 'â€”'); ?>

                                    <?php if($dep->end_date): ?>
                                        <br><span class="text-muted">â†’ <?php echo e($dep->end_date->format('d/m/Y')); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <strong><?php echo e($dep->confirmed_count ?? 0); ?></strong>
                                    <?php if($dep->total_capacity): ?>
                                        <span class="text-muted">/ <?php echo e($dep->total_capacity); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-muted small"><?php echo e($dep->guaranteed_threshold); ?></td>
                                <td>
                                    <?php if($dep->active_tier_price): ?>
                                        <span class="text-success fw-semibold"><?php echo e(number_format($dep->active_tier_price, 0, ',', ' ')); ?> â‚¬</span>
                                    <?php elseif($dep->sale_price): ?>
                                        <?php echo e(number_format($dep->sale_price, 0, ',', ' ')); ?> â‚¬
                                    <?php else: ?>
                                        â€”
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($dep->is_guaranteed): ?>
                                        <span class="badge bg-success">Garanti</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">En cours</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo e(route('admin.group-deals.departures.show', $dep)); ?>"
                                       class="btn btn-sm btn-outline-primary">DÃ©tail</a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalAddTier" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('admin.group-deals.trips.tiers.store', $voyage)); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h6 class="modal-title">Ajouter un palier</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Participants minimum <span class="text-danger">*</span></label>
                        <input type="number" name="min_participants" class="form-control form-control-sm" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Prix par personne (â‚¬) <span class="text-danger">*</span></label>
                        <input type="number" name="price_per_person" class="form-control form-control-sm" step="0.01" min="0" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Label (optionnel)</label>
                        <input type="text" name="label" class="form-control form-control-sm" placeholder="ex: Prix Groupe">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php $__currentLoopData = $voyage->pricingTiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="modal fade" id="modalEditTier<?php echo e($tier->id); ?>" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('admin.group-deals.trips.tiers.update', [$voyage, $tier])); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                <div class="modal-header">
                    <h6 class="modal-title">Modifier le palier</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Participants minimum <span class="text-danger">*</span></label>
                        <input type="number" name="min_participants" class="form-control form-control-sm"
                               min="1" value="<?php echo e($tier->min_participants); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Prix par personne (â‚¬) <span class="text-danger">*</span></label>
                        <input type="number" name="price_per_person" class="form-control form-control-sm"
                               step="0.01" min="0" value="<?php echo e($tier->price_per_person); ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Label</label>
                        <input type="text" name="label" class="form-control form-control-sm"
                               value="<?php echo e($tier->label); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\group-deals\trips\show.blade.php ENDPATH**/ ?>