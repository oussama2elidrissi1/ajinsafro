<?php $__env->startSection('title', 'Hôtel du circuit — ' . $tour->post_title); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Hôtel du circuit</h4>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?php echo e(route('admin.circuits.tour-hotels.edit', $tour->ID)); ?>" class="btn btn-outline-primary btn-sm">Modifier</a>
                    <a href="<?php echo e(route('admin.circuits.tour-hotels.index')); ?>" class="btn btn-outline-secondary btn-sm">Retour</a>
                </div>
            </div>
            <ol class="breadcrumb mb-0 mt-1">
                <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                <li class="breadcrumb-item"><a href="<?php echo e(route('admin.circuits.index')); ?>">Circuits</a></li>
                <li class="breadcrumb-item"><a href="<?php echo e(route('admin.circuits.tour-hotels.index')); ?>">Hôtels</a></li>
                <li class="breadcrumb-item active"><?php echo e(\Str::limit($tour->post_title, 40)); ?></li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light d-flex align-items-center">
                    <h5 class="mb-0">Voyage lié</h5>
                    <span class="badge bg-primary ms-auto">ID voyage <?php echo e($tour->ID); ?></span>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong><?php echo e($tour->post_title); ?></strong>
                    </p>
                    <p class="small text-muted mb-2">Cet hôtel circuit est rattaché à ce voyage (tour_id = <?php echo e($tour->ID); ?>).</p>
                    <a href="<?php echo e(route('admin.circuits.voyages.edit', $tour->ID)); ?>" class="btn btn-soft-primary btn-sm me-1">Modifier le voyage</a>
                    <a href="<?php echo e(route('admin.circuits.voyages.show', $tour->ID)); ?>" class="btn btn-outline-secondary btn-sm">Voir la fiche voyage</a>
                </div>
            </div>

            <?php if($hotel): ?>
                
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light d-flex align-items-center">
                        <h5 class="mb-0">Informations hôtel</h5>
                        <?php if($hotel->stars): ?>
                            <span class="badge bg-warning text-dark ms-auto">★ <?php echo e($hotel->stars); ?> étoiles</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr><td class="text-nowrap pe-3 fw-medium text-muted" style="width:140px;">ID hôtel circuit</td><td><code><?php echo e($hotel->id); ?></code></td></tr>
                                <tr><td class="pe-3 fw-medium text-muted">Nom</td><td><?php echo e($hotel->hotel_name ?? '—'); ?></td></tr>
                                <tr><td class="pe-3 fw-medium text-muted">Adresse</td><td><?php echo e($hotel->address ?? '—'); ?></td></tr>
                                <tr><td class="pe-3 fw-medium text-muted">Type de chambre</td><td><?php echo e($hotel->room_type ?? '—'); ?></td></tr>
                                <tr><td class="pe-3 fw-medium text-muted">Formule repas</td><td><?php echo e($hotel->meal_plan ?? '—'); ?></td></tr>
                                <?php if($hotel->check_in_day !== null || $hotel->check_out_day !== null): ?>
                                    <tr><td class="pe-3 fw-medium text-muted">Jours</td><td>Check-in jour <?php echo e($hotel->check_in_day ?? '—'); ?> / Check-out jour <?php echo e($hotel->check_out_day ?? '—'); ?></td></tr>
                                <?php endif; ?>
                                <tr><td class="pe-3 fw-medium text-muted">Optionnel</td><td><?php echo e($hotel->is_optional ? 'Oui' : 'Non'); ?></td></tr>
                                <tr><td class="pe-3 fw-medium text-muted">Ordre</td><td><?php echo e($hotel->sort_order ?? 0); ?></td></tr>
                            </tbody>
                        </table>
                        <?php if($hotel->notes): ?>
                            <hr>
                            <p class="mb-0 small"><strong>Notes :</strong> <?php echo e($hotel->notes); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm mb-3 border-warning">
                    <div class="card-body text-center text-muted py-4">
                        <i class="bx bxs-hotel display-6 d-block mb-2"></i>
                        Aucun hôtel renseigné pour ce circuit.
                        <a href="<?php echo e(route('admin.circuits.tour-hotels.edit', $tour->ID)); ?>" class="btn btn-primary btn-sm mt-2">Renseigner l'hôtel</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <?php if($hotel): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Résumé</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>Voyage</strong> : <?php echo e(\Str::limit($tour->post_title, 35)); ?></li>
                            <li><strong>ID voyage</strong> : <?php echo e($tour->ID); ?></li>
                            <li><strong>Hôtel</strong> : <?php echo e($hotel->hotel_name ?: '—'); ?></li>
                            <li><strong>Types de chambres</strong> : <?php echo e($hotel->rooms->count()); ?></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <?php if($hotel && $hotel->rooms->isNotEmpty()): ?>
        <div class="row mt-2">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Types de chambres</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Libellé</th>
                                        <th>Code</th>
                                        <th>Quantité</th>
                                        <th>Capacité</th>
                                        <th>Supplément</th>
                                        <th>Description</th>
                                        <th>Défaut</th>
                                        <th>Actif</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $hotel->rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><strong><?php echo e($room->room_type); ?></strong></td>
                                            <td><?php echo e($room->room_label ?? '—'); ?></td>
                                            <td><code class="small"><?php echo e($room->room_code ?? '—'); ?></code></td>
                                            <td><?php echo e($room->room_count); ?></td>
                                            <td><?php echo e($room->capacity_adults); ?>A / <?php echo e($room->capacity_children); ?>E (<?php echo e($room->capacity_total); ?> total)</td>
                                            <td><?php echo e($room->supplement ? number_format((float) $room->supplement, 0, ',', ' ') . ' DH' : '—'); ?></td>
                                            <td class="small text-muted" style="max-width:180px;"><?php echo e($room->description ? \Str::limit($room->description, 50) : '—'); ?></td>
                                            <td><?php if($room->is_default): ?><span class="badge bg-success">Oui</span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
                                            <td><?php if($room->is_active): ?><span class="badge bg-success">Oui</span><?php else: ?><span class="badge bg-secondary">Non</span><?php endif; ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <p class="small text-muted mt-2 mb-0">La gestion détaillée des chambres se fait dans l’édition du voyage (onglet programme / hôtels).</p>
            </div>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\tour-hotels\show.blade.php ENDPATH**/ ?>