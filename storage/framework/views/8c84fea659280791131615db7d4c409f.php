<?php $__env->startSection('title', 'Détail partenaire'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Compte partenaire – <?php echo e($partner->display_name); ?></h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.partner-accounts.index')); ?>">Revendeurs</a></li>
                        <li class="breadcrumb-item active">Détail</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Informations société</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6"><strong>Raison sociale</strong><br><?php echo e($partner->raison_sociale); ?></div>
                        <div class="col-md-6"><strong>Nom commercial</strong><br><?php echo e($partner->nom_commercial ?? '—'); ?></div>
                        <div class="col-md-6"><strong>Responsable</strong><br><?php echo e($partner->nom_responsable); ?></div>
                        <div class="col-md-6"><strong>Email</strong><br><?php echo e($partner->email); ?></div>
                        <div class="col-md-6"><strong>Téléphone</strong><br><?php echo e($partner->telephone ?? '—'); ?></div>
                        <div class="col-12"><strong>Adresse</strong><br><?php echo e($partner->adresse ?? '—'); ?>, <?php echo e($partner->code_postal ?? ''); ?> <?php echo e($partner->ville ?? ''); ?>, <?php echo e($partner->pays ?? '—'); ?></div>
                        <div class="col-md-4"><strong>ICE</strong><br><?php echo e($partner->ice ?? '—'); ?></div>
                        <div class="col-md-4"><strong>IF</strong><br><?php echo e($partner->if ?? '—'); ?></div>
                        <div class="col-md-4"><strong>RC</strong><br><?php echo e($partner->rc ?? '—'); ?></div>
                        <?php if($partner->partner_type ?? null): ?>
                            <div class="col-md-6"><strong>Type partenaire</strong><br><?php echo e($partner->partner_type_label ?? $partner->partner_type); ?></div>
                        <?php endif; ?>
                        <?php if($partner->rib_iban ?? null): ?>
                            <div class="col-md-6"><strong>RIB / IBAN</strong><br><?php echo e($partner->rib_iban); ?></div>
                        <?php endif; ?>
                        <?php if($partner->rib_bic ?? null): ?>
                            <div class="col-md-6"><strong>BIC</strong><br><?php echo e($partner->rib_bic); ?></div>
                        <?php endif; ?>
                        <?php if($partner->payment_mode ?? null): ?>
                            <div class="col-md-6"><strong>Mode de paiement</strong><br><?php echo e($partner->payment_mode); ?></div>
                        <?php endif; ?>
                        <?php if($partner->contract_path ?? null): ?>
                            <div class="col-12">
                                <strong>Contrat</strong><br>
                                <a href="<?php echo e(asset('storage/' . $partner->contract_path)); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bx bx-file"></i> Voir le contrat</a>
                            </div>
                        <?php endif; ?>
                        <?php if($partner->document_path): ?>
                            <div class="col-12">
                                <strong>Pièce justificative</strong><br>
                                <a href="<?php echo e(asset('storage/' . $partner->document_path)); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bx bx-file"></i> Voir le document</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Statut & validation</h5>
                </div>
                <div class="card-body">
                    <p><strong>Statut</strong><br>
                        <?php
                            $badge = match($partner->status) {
                                'pending' => 'badge bg-warning text-dark',
                                'validated' => 'badge bg-success',
                                'rejected' => 'badge bg-danger',
                                'suspended' => 'badge bg-secondary',
                                default => 'badge bg-light text-dark',
                            };
                        ?>
                        <span class="<?php echo e($badge); ?>"><?php echo e($partner->status); ?></span>
                    </p>
                    <p><strong>Inscrit le</strong><br><?php echo e($partner->created_at?->format('d/m/Y H:i')); ?></p>
                    <?php if($partner->validated_at): ?>
                        <p><strong>Validé le</strong><br><?php echo e($partner->validated_at->format('d/m/Y H:i')); ?></p>
                        <?php if($partner->validatedByUser): ?>
                            <p><strong>Validé par</strong><br><?php echo e($partner->validatedByUser->name); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if($partner->rejected_at): ?>
                        <p><strong>Refusé le</strong><br><?php echo e($partner->rejected_at->format('d/m/Y H:i')); ?></p>
                        <?php if($partner->rejected_reason): ?>
                            <p><strong>Motif</strong><br><?php echo e($partner->rejected_reason); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if($partner->isPending()): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form action="<?php echo e(route('admin.partner-accounts.validate', $partner)); ?>" method="post" class="mb-2">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-success w-100"><i class="bx bx-check me-1"></i> Valider le partenaire</button>
                        </form>
                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#reject-modal-<?php echo e($partner->id); ?>">
                            <i class="bx bx-x me-1"></i> Refuser
                        </button>
                        <?php echo $__env->make('admin.partner-accounts._reject_modal', ['partner' => $partner], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if(isset($voyages) && $partner->isValidated()): ?>
    <div class="row mt-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Accès voyages</h5>
                    <span class="small text-muted">Vide = tous les voyages</span>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('admin.partner-accounts.voyage-access', $partner)); ?>" method="post">
                        <?php echo csrf_field(); ?>
                        <div class="row g-2" style="max-height: 300px; overflow-y: auto;">
                            <?php $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="voyage_ids[]" value="<?php echo e($v->id); ?>" id="voyage-<?php echo e($v->id); ?>"
                                            <?php echo e($partner->voyageAccess->contains('id', $v->id) ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="voyage-<?php echo e($v->id); ?>"><?php echo e($v->name); ?></label>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <p class="small text-muted mt-2">Ne cochez rien pour laisser l’accès à tous les voyages. Cochez des voyages pour restreindre l’accès.</p>
                        <button type="submit" class="btn btn-primary btn-sm">Enregistrer l’accès</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row mt-3">
        <div class="col-12">
            <a href="<?php echo e(route('admin.partner-accounts.index')); ?>" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i> Retour à la liste</a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partner-accounts\show.blade.php ENDPATH**/ ?>