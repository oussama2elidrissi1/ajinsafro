

<?php $__env->startSection('title', 'Fiche Group Deal'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1"><?php echo e($groupDeal->title); ?></h4>
            <p class="text-muted mb-0"><?php echo e($groupDeal->destination ?: 'Destination non renseignÃ©e'); ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('front.group-deals.show', $groupDeal->slug)); ?>" target="_blank" class="btn btn-light">Voir la page publique</a>
            <a href="<?php echo e(route('admin.group-deals.edit', $groupDeal)); ?>" class="btn btn-primary">Modifier</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body"><div class="text-muted small">Inscrits actuels</div><div class="h3 mb-0"><?php echo e($stats['current_participants']); ?></div></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body"><div class="text-muted small">Prix actuel</div><div class="h3 mb-0"><?php echo e($stats['current_price'] ? number_format($stats['current_price'], 0, ',', ' ') . ' DH' : 'N/A'); ?></div></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body"><div class="text-muted small">Places restantes</div><div class="h3 mb-0"><?php echo e($stats['remaining_places']); ?></div></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body"><div class="text-muted small">Garantie</div><div class="h5 mb-0"><?php echo e($stats['is_guaranteed'] ? 'Voyage garanti' : 'En attente'); ?></div></div></div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Progression</h5>
                    <form method="POST" action="<?php echo e(route('admin.group-deals.recalculate', $groupDeal)); ?>">
                        <?php echo csrf_field(); ?>
                        <button class="btn btn-sm btn-outline-primary">Recalculer</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height:12px;">
                        <div class="progress-bar <?php echo e($stats['is_guaranteed'] ? 'bg-success' : 'bg-warning'); ?>" style="width: <?php echo e($stats['progress_percent']); ?>%"></div>
                    </div>
                    <div class="row g-3 small">
                        <div class="col-md-4"><strong>Minimum garanti:</strong> <?php echo e($groupDeal->min_participants); ?></div>
                        <div class="col-md-4"><strong>Maximum:</strong> <?php echo e($groupDeal->max_participants); ?></div>
                        <div class="col-md-4"><strong>Statut:</strong> <?php echo e($groupDeal->status_label); ?></div>
                    </div>
                    <div class="mt-3 text-muted">
                        <?php if($stats['remaining_to_guarantee'] > 0): ?>
                            Il reste <?php echo e($stats['remaining_to_guarantee']); ?> personne(s) pour garantir le dÃ©part.
                        <?php else: ?>
                            Le voyage est garanti.
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Paliers de prix</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                            <tr>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Prix</th>
                                <th>LibellÃ©</th>
                                <th>Ordre</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $__currentLoopData = $groupDeal->pricingTiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="<?php echo e(optional($stats['active_tier'])->id === $tier->id ? 'table-warning' : ''); ?>">
                                    <td><input type="number" name="min_participants" form="tier-form-<?php echo e($tier->id); ?>" class="form-control" value="<?php echo e($tier->min_participants); ?>"></td>
                                    <td><input type="number" name="max_people" form="tier-form-<?php echo e($tier->id); ?>" class="form-control" value="<?php echo e($tier->max_people); ?>"></td>
                                    <td><input type="number" step="0.01" name="price_per_person" form="tier-form-<?php echo e($tier->id); ?>" class="form-control" value="<?php echo e($tier->price_per_person); ?>"></td>
                                    <td><input type="text" name="label" form="tier-form-<?php echo e($tier->id); ?>" class="form-control" value="<?php echo e($tier->label); ?>"></td>
                                    <td><input type="number" name="sort_order" form="tier-form-<?php echo e($tier->id); ?>" class="form-control" value="<?php echo e($tier->sort_order); ?>"></td>
                                    <td class="text-nowrap">
                                        <form id="tier-form-<?php echo e($tier->id); ?>" method="POST" action="<?php echo e(route('admin.group-deals.tiers.update', [$groupDeal, $tier])); ?>" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PUT'); ?>
                                            <button class="btn btn-sm btn-primary">OK</button>
                                        </form>
                                        <form method="POST" action="<?php echo e(route('admin.group-deals.tiers.destroy', [$groupDeal, $tier])); ?>" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button class="btn btn-sm btn-light" onclick="return confirm('Supprimer ce palier ?')">Suppr.</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><input type="number" name="min_participants" form="tier-create-form" class="form-control" placeholder="4"></td>
                                <td><input type="number" name="max_people" form="tier-create-form" class="form-control" placeholder="8"></td>
                                <td><input type="number" step="0.01" name="price_per_person" form="tier-create-form" class="form-control" placeholder="9000"></td>
                                <td><input type="text" name="label" form="tier-create-form" class="form-control" placeholder="Palier"></td>
                                <td><input type="number" name="sort_order" form="tier-create-form" class="form-control" placeholder="1"></td>
                                <td>
                                    <form id="tier-create-form" method="POST" action="<?php echo e(route('admin.group-deals.tiers.store', $groupDeal)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button class="btn btn-sm btn-success">Ajouter</button>
                                    </form>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Participants</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>Participant</th>
                                <th>QtÃ©</th>
                                <th>Prix saisi</th>
                                <th>Statut</th>
                                <th>Paiement</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $groupDeal->participants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $participant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($participant->full_name); ?></div>
                                        <div class="small text-muted"><?php echo e($participant->email); ?></div>
                                    </td>
                                    <td><?php echo e($participant->participants_count); ?></td>
                                    <td><?php echo e($participant->selected_price ? number_format((float) $participant->selected_price, 0, ',', ' ') . ' DH' : 'N/A'); ?></td>
                                    <td>
                                        <select name="status" form="participant-form-<?php echo e($participant->id); ?>" class="form-select form-select-sm">
                                            <?php $__currentLoopData = ['pending' => 'En attente', 'confirmed' => 'ConfirmÃ©', 'paid' => 'PayÃ©', 'cancelled' => 'AnnulÃ©']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($key); ?>" <?php if($participant->status === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="payment_status" form="participant-form-<?php echo e($participant->id); ?>" class="form-select form-select-sm">
                                            <?php $__currentLoopData = ['pending' => 'En attente', 'paid' => 'PayÃ©', 'cancelled' => 'AnnulÃ©']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($key); ?>" <?php if($participant->payment_status === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </td>
                                    <td><?php echo e(optional($participant->created_at)->format('d/m/Y H:i')); ?></td>
                                    <td>
                                        <form id="participant-form-<?php echo e($participant->id); ?>" method="POST" action="<?php echo e(route('admin.group-deals.participants.update', [$groupDeal, $participant])); ?>">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <button class="btn btn-sm btn-outline-primary">Mettre Ã  jour</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Aucun participant pour lâ€™instant.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Ajouter un participant</h5></div>
                <div class="card-body">
                    <form method="POST" action="<?php echo e(route('admin.group-deals.participants.store', $groupDeal)); ?>" class="vstack gap-3">
                        <?php echo csrf_field(); ?>
                        <div>
                            <label class="form-label">Client existant</label>
                            <select name="client_id" class="form-select">
                                <option value="">Saisie libre</option>
                                <?php $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($client->id); ?>"><?php echo e($client->full_name ?: $client->email); ?><?php echo e($client->email ? ' Â· '.$client->email : ''); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Nom complet</label>
                            <input type="text" name="full_name" class="form-control">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">TÃ©lÃ©phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nb personnes</label>
                                <input type="number" min="1" name="participants_count" class="form-control" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Statut</label>
                                <select name="status" class="form-select">
                                    <option value="pending">En attente</option>
                                    <option value="confirmed">ConfirmÃ©</option>
                                    <option value="paid">PayÃ©</option>
                                    <option value="cancelled">AnnulÃ©</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Paiement</label>
                                <select name="payment_status" class="form-select">
                                    <option value="pending">En attente</option>
                                    <option value="paid">PayÃ©</option>
                                    <option value="cancelled">AnnulÃ©</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-primary">Ajouter le participant</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">RÃ©sumÃ©</h5></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 vstack gap-2">
                        <li><strong>DÃ©part:</strong> <?php echo e(optional($groupDeal->start_date)->format('d/m/Y') ?: 'N/A'); ?></li>
                        <li><strong>Retour:</strong> <?php echo e(optional($groupDeal->end_date)->format('d/m/Y') ?: 'N/A'); ?></li>
                        <li><strong>Deadline:</strong> <?php echo e(optional($groupDeal->registration_deadline)->format('d/m/Y') ?: 'N/A'); ?></li>
                        <li><strong>Partage client:</strong> <?php echo e($groupDeal->share_enabled ? 'ActivÃ©' : 'DÃ©sactivÃ©'); ?></li>
                        <li><strong>Meilleur prix:</strong> <?php echo e(optional($stats['best_tier'])->price_per_person ? number_format((float) $stats['best_tier']->price_per_person, 0, ',', ' ') . ' DH' : 'N/A'); ?></li>
                        <li><strong>Prochain palier:</strong>
                            <?php if($stats['next_tier']): ?>
                                <?php echo e($stats['next_tier']->min_participants); ?> pers. â†’ <?php echo e(number_format((float) $stats['next_tier']->price_per_person, 0, ',', ' ')); ?> DH
                            <?php else: ?>
                                Aucun
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if($groupDeal->image_url): ?>
                <div class="card">
                    <div class="card-body p-2">
                        <img src="<?php echo e($groupDeal->image_url); ?>" alt="<?php echo e($groupDeal->title); ?>" class="img-fluid rounded">
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\group-deals\offers\show.blade.php ENDPATH**/ ?>