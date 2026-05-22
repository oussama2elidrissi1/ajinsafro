
<?php $__env->startSection('title'); ?> Group Deals �?" Départs <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="page-title mb-0 font-size-18">Group Deals �?" Départs</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo e(route('admin.group-deals.trips.index')); ?>">Group Deals</a></li>
                    <li class="breadcrumb-item active">Départs</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        
        <form method="get" class="row g-2 align-items-end mb-4">
            <div class="col-md-3">
                <label class="form-label small mb-1">Voyage</label>
                <select name="voyage_id" class="form-select form-select-sm">
                    <option value="">Tous les voyages</option>
                    <?php $__currentLoopData = $voyageOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($v->id); ?>" <?php if(request('voyage_id') == $v->id): echo 'selected'; endif; ?>><?php echo e($v->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Statut départ</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="open"    <?php if(request('status') === 'open'): echo 'selected'; endif; ?>>Ouvert</option>
                    <option value="limited" <?php if(request('status') === 'limited'): echo 'selected'; endif; ?>>Limité</option>
                    <option value="full"    <?php if(request('status') === 'full'): echo 'selected'; endif; ?>>Complet</option>
                    <option value="closed"  <?php if(request('status') === 'closed'): echo 'selected'; endif; ?>>Fermé</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Garanti</label>
                <select name="guaranteed" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="1" <?php if(request('guaranteed') === '1'): echo 'selected'; endif; ?>>Oui</option>
                    <option value="0" <?php if(request('guaranteed') === '0'): echo 'selected'; endif; ?>>Non</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                <a href="<?php echo e(route('admin.group-deals.departures.index')); ?>" class="btn btn-outline-secondary btn-sm ms-1">Réinitialiser</a>
            </div>
        </form>

        <h5 class="mb-3"><?php echo e($departures->total()); ?> départ(s)</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Voyage</th>
                        <th>Date</th>
                        <th class="text-center">Participants</th>
                        <th class="text-center">Seuil garanti</th>
                        <th>Prix actif</th>
                        <th>Garanti</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $departures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <a href="<?php echo e(route('admin.group-deals.trips.show', $dep->voyage)); ?>"
                               class="text-body fw-semibold"><?php echo e($dep->voyage?->name ?? '�?"'); ?></a>
                            <?php if($dep->voyage?->destination): ?>
                                <br><small class="text-muted"><?php echo e($dep->voyage->destination); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php echo e($dep->start_date?->format('d/m/Y') ?? '�?"'); ?>

                            <?php if($dep->end_date): ?>
                                <br><span class="text-muted">�?' <?php echo e($dep->end_date->format('d/m/Y')); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php $pct = $dep->guaranteed_threshold > 0 ? min(100, round(($dep->confirmed_count / $dep->guaranteed_threshold) * 100)) : 0; ?>
                            <strong><?php echo e($dep->confirmed_count); ?></strong>
                            <div class="progress mt-1" style="height:4px;width:60px;margin:0 auto;">
                                <div class="progress-bar bg-<?php echo e($dep->is_guaranteed ? 'success' : 'warning'); ?>"
                                     style="width:<?php echo e($pct); ?>%"></div>
                            </div>
                        </td>
                        <td class="text-center text-muted small"><?php echo e($dep->guaranteed_threshold); ?></td>
                        <td>
                            <?php if($dep->active_tier_price): ?>
                                <span class="text-success fw-semibold"><?php echo e(number_format($dep->active_tier_price, 0, ',', ' ')); ?> �,�</span>
                            <?php elseif($dep->sale_price): ?>
                                <?php echo e(number_format($dep->sale_price, 0, ',', ' ')); ?> �,�
                            <?php else: ?>
                                �?"
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($dep->is_guaranteed): ?>
                                <span class="badge bg-success">Oui</span>
                                <?php if($dep->guaranteed_at): ?>
                                    <br><small class="text-muted"><?php echo e($dep->guaranteed_at->format('d/m/Y')); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border">Non</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo e(match($dep->status) {
                                'open'    => 'success',
                                'limited' => 'warning text-dark',
                                'full'    => 'danger',
                                'closed'  => 'secondary',
                                default   => 'secondary'
                            }); ?>"><?php echo e($dep->status_label); ?></span>
                        </td>
                        <td>
                            <a href="<?php echo e(route('admin.group-deals.departures.show', $dep)); ?>"
                               class="btn btn-sm btn-outline-primary">Détail</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Aucun départ Group Deal trouvé.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($departures->hasPages()): ?>
            <div class="mt-3"><?php echo e($departures->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\group-deals\departures\index.blade.php ENDPATH**/ ?>