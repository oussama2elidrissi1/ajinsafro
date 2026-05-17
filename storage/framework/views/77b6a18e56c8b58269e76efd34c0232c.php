<?php
    $lm = ($laravelVoyage ?? null) ? ($laravelVoyage->logistics_meta ?? []) : [];
    $logisticsTransportTypeOpts = \App\Services\BusinessReferentialService::logisticsTransportTypes();
    $transport = $lm['transport'] ?? [];
    $transportTypeVal = old('logistics_meta.transport.type', $transport['type'] ?? '');
?>
<div class="tab-pane" id="logistics" role="tabpanel" data-ve-pane-title="Logistique">
    <div class="ve-logistics-shell card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-lg-3 border-end bg-light bg-opacity-50 ve-logistics-nav-col">
                    <div class="p-3 pb-2">
                        <p class="text-uppercase text-muted small fw-bold mb-2 letter-spacing-1">Sections</p>
                        <p class="small text-muted mb-0">Choisissez un mode, puis renseignez le formulaire.</p>
                    </div>
                    <nav class="nav nav-pills flex-column px-3 pb-4 gap-1" id="ve-logistics-nav" role="tablist">
                        <button type="button" class="nav-link nav-link-ve active text-start" data-bs-toggle="tab" data-bs-target="#logistics-pane-train" role="tab" aria-controls="logistics-pane-train" aria-selected="true">
                            <i class="bx bx-train me-2"></i> Train
                        </button>
                        <button type="button" class="nav-link nav-link-ve text-start" data-bs-toggle="tab" data-bs-target="#logistics-pane-boat" role="tab">
                            <i class="bx bx-water me-2"></i> Bateau
                        </button>
                        <button type="button" class="nav-link nav-link-ve text-start" data-bs-toggle="tab" data-bs-target="#logistics-pane-transport" role="tab">
                            <i class="bx bx-bus me-2"></i> Transport
                        </button>
                    </nav>
                </div>
                <div class="col-lg-9">
                    <div class="p-3 p-lg-4">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="logistics-pane-train" role="tabpanel">
                                <?php $train = $lm['train'] ?? []; ?>
                                <div class="ve-logistics-pane-card card border-0 shadow-sm">
                                    <div class="card-header bg-white border-bottom py-3">
                                        <h5 class="mb-0 fw-semibold">Train</h5>
                                        <p class="text-muted small mb-0 mt-1">Renseignements voyage (à relier au dossier si besoin).</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Ligne / référence</label>
                                                <input type="text" class="form-control" name="logistics_meta[train][reference]" value="<?php echo e(old('logistics_meta.train.reference', $train['reference'] ?? '')); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Classe</label>
                                                <input type="text" class="form-control" name="logistics_meta[train][class]" value="<?php echo e(old('logistics_meta.train.class', $train['class'] ?? '')); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Notes</label>
                                                <textarea class="form-control" name="logistics_meta[train][notes]" rows="3"><?php echo e(old('logistics_meta.train.notes', $train['notes'] ?? '')); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="logistics-pane-boat" role="tabpanel">
                                <?php $boat = $lm['boat'] ?? []; ?>
                                <div class="ve-logistics-pane-card card border-0 shadow-sm">
                                    <div class="card-header bg-white border-bottom py-3">
                                        <h5 class="mb-0 fw-semibold">Bateau</h5>
                                        <p class="text-muted small mb-0 mt-1">Traversée / ferry.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Route / traversée</label>
                                                <input type="text" class="form-control" name="logistics_meta[boat][route]" value="<?php echo e(old('logistics_meta.boat.route', $boat['route'] ?? '')); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Compagnie</label>
                                                <input type="text" class="form-control" name="logistics_meta[boat][company]" value="<?php echo e(old('logistics_meta.boat.company', $boat['company'] ?? '')); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Notes</label>
                                                <textarea class="form-control" name="logistics_meta[boat][notes]" rows="3"><?php echo e(old('logistics_meta.boat.notes', $boat['notes'] ?? '')); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="logistics-pane-transport" role="tabpanel">
                                <?php $transport = $lm['transport'] ?? []; ?>
                                <div class="ve-logistics-pane-card card border-0 shadow-sm">
                                    <div class="card-header bg-white border-bottom py-3">
                                        <h5 class="mb-0 fw-semibold">Transport</h5>
                                        <p class="text-muted small mb-0 mt-1">Bus, minibus, véhicule dédié, etc.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Type</label>
                                                <select class="form-select" name="logistics_meta[transport][type]">
                                                    <option value="">— Choisir —</option>
                                                    <?php $__currentLoopData = $logisticsTransportTypeOpts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($tto['value']); ?>" <?php if($transportTypeVal === $tto['value']): echo 'selected'; endif; ?>><?php echo e($tto['label']); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                                <p class="text-muted small mb-0 mt-1">Liste pilotée depuis Référence métier &gt; Types de transport.</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Capacité</label>
                                                <input type="text" class="form-control" name="logistics_meta[transport][capacity]" value="<?php echo e(old('logistics_meta.transport.capacity', $transport['capacity'] ?? '')); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Notes</label>
                                                <textarea class="form-control" name="logistics_meta[transport][notes]" rows="3"><?php echo e(old('logistics_meta.transport.notes', $transport['notes'] ?? '')); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('styles'); ?>
<style>
.ve-logistics-shell .nav-link-ve.nav-link {
    color: #334155;
    border-radius: 0.5rem;
    padding: 0.65rem 0.85rem;
    font-weight: 600;
}
.ve-logistics-shell .nav-link-ve.nav-link:hover {
    background: rgba(13, 110, 253, 0.08);
    color: #0d6efd;
}
.ve-logistics-shell .nav-link-ve.nav-link.active {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    color: #fff !important;
    box-shadow: 0 4px 14px rgba(13, 110, 253, 0.25);
}
.ve-logistics-nav-col { min-height: 260px; }
.ve-logistics-pane-card .card-header { border-radius: 0.5rem 0.5rem 0 0 !important; }
</style>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\tabs\_logistics.blade.php ENDPATH**/ ?>