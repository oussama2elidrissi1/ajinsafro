
<?php $__env->startSection('title'); ?>
    Fiche client <?php echo e($client->client_code); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Fiche client �?" <?php echo e($client->full_name); ?></h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.customers.index')); ?>">Clients</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.customers.clients.index')); ?>">Liste clients</a></li>
                        <li class="breadcrumb-item active"><?php echo e($client->client_code); ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-12">
            <a href="<?php echo e(route('admin.customers.clients.edit', $client)); ?>" class="btn btn-primary btn-sm"><i class="bx bx-edit me-1"></i> Modifier</a>
            <a href="<?php echo e(route('admin.customers.clients.index')); ?>" class="btn btn-outline-secondary btn-sm">Retour à la liste</a>
            <form action="<?php echo e(route('admin.customers.clients.destroy', $client)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Mettre ce client en corbeille ?');">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Résumé</h5>
                    <p class="mb-1"><strong>Code :</strong> <code><?php echo e($client->client_code); ?></code></p>
                    <p class="mb-1"><strong>Type :</strong>
                        <span class="badge <?php echo e($client->client_type === 'company' ? 'bg-info' : ($client->client_type === 'agency' ? 'bg-secondary' : 'bg-light text-dark')); ?>">
                            <?php echo e($client->client_type === 'individual' ? 'Particulier' : ($client->client_type === 'company' ? 'Société' : 'Agence')); ?>

                        </span>
                    </p>
                    <p class="mb-1"><strong>Statut :</strong>
                        <?php
                            $statusBadge = match($client->status) {
                                'active' => 'bg-success',
                                'inactive' => 'bg-warning text-dark',
                                'blocked' => 'bg-danger',
                                'vip' => 'bg-primary',
                                default => 'bg-secondary',
                            };
                        ?>
                        <span class="badge <?php echo e($statusBadge); ?>"><?php echo e($client->status); ?></span>
                    </p>
                    <?php if($client->source): ?>
                        <p class="mb-1"><strong>Source :</strong> <?php echo e($client->source); ?></p>
                    <?php endif; ?>
                    <?php if($client->assignedTo): ?>
                        <p class="mb-1"><strong>Assigné à :</strong> <?php echo e($client->assignedTo->name); ?></p>
                    <?php endif; ?>
                    <p class="mb-0 small text-muted">Créé le <?php echo e($client->created_at->format('d/m/Y H:i')); ?>

                        <?php if($client->updated_at && $client->updated_at != $client->created_at): ?>
                            · Modifié le <?php echo e($client->updated_at->format('d/m/Y H:i')); ?>

                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Coordonnées</h5>
                    <?php if($client->email): ?><p class="mb-1"><a href="mailto:<?php echo e($client->email); ?>"><?php echo e($client->email); ?></a></p><?php endif; ?>
                    <?php if($client->phone): ?><p class="mb-1">Tél : <?php echo e($client->phone); ?></p><?php endif; ?>
                    <?php if($client->whatsapp_number): ?><p class="mb-1">WhatsApp : <?php echo e($client->whatsapp_number); ?></p><?php endif; ?>
                    <?php if($client->address_line_1 || $client->city): ?>
                        <p class="mb-0"><?php echo e($client->address_line_1); ?><?php echo e($client->address_line_2 ? ', ' . $client->address_line_2 : ''); ?><br>
                            <?php echo e($client->city); ?><?php echo e($client->postal_code ? ' ' . $client->postal_code : ''); ?><br>
                            <?php echo e($client->country_of_residence ?? ''); ?></p>
                    <?php endif; ?>
                    <?php if(!$client->email && !$client->phone && !$client->address_line_1): ?>
                        <p class="text-muted mb-0">�?"</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Documents & Visa</h5>
                    <p class="mb-1"><strong>Passeport :</strong> <?php echo e($client->passport_number ?? '�?"'); ?> <?php if($client->passport_expiry_date): ?> (exp. <?php echo e($client->passport_expiry_date->format('d/m/Y')); ?>) <?php endif; ?></p>
                    <p class="mb-1"><strong>CIN / ID :</strong> <?php echo e($client->national_id_number ?? '�?"'); ?></p>
                    <p class="mb-0"><strong>Visa :</strong> <?php echo e($client->visa_required ? 'Requis �?" ' . $client->visa_status : 'Non requis'); ?></p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Préférences voyage</h5>
                    <p class="mb-1"><strong>Catégorie :</strong> <?php echo e($client->traveler_category ?? '�?"'); ?></p>
                    <p class="mb-1"><strong>Destination préférée :</strong> <?php echo e($client->preferred_destination ?? '�?"'); ?></p>
                    <p class="mb-1"><strong>Budget :</strong> <?php echo e($client->budget_display ?? '�?"'); ?></p>
                    <?php if($client->special_requests): ?>
                        <p class="mb-0"><strong>Demandes spéciales :</strong><br><?php echo e($client->special_requests); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($client->company_name || $client->billing_name): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Facturation</h5>
                        <?php if($client->company_name): ?><p class="mb-1"><strong>Société :</strong> <?php echo e($client->company_name); ?></p><?php endif; ?>
                        <p class="mb-1"><strong>Facturation :</strong> <?php echo e($client->billing_name ?? $client->full_name); ?></p>
                        <?php if($client->billing_email): ?><p class="mb-0"><?php echo e($client->billing_email); ?></p><?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($client->internal_notes): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Notes internes</h5>
                        <p class="mb-0 text-muted"><?php echo e($client->internal_notes); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            
            <div class="card mb-3 border-secondary">
                <div class="card-body">
                    <h5 class="card-title text-muted">Réservations / Devis / Paiements</h5>
                    <p class="text-muted small mb-0">�? venir : liens vers les réservations, devis et paiements associés à ce client.</p>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\customers\clients\show.blade.php ENDPATH**/ ?>