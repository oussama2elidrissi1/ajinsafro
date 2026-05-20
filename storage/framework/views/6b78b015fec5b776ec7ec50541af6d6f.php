<?php $__env->startSection('title', 'Client'); ?>

<?php $__env->startSection('content'); ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18"><?php echo e($client->full_name); ?></h4>
                <div>
                    <a href="<?php echo e(route('partner.clients.edit', $client)); ?>" class="btn btn-outline-primary btn-sm">Modifier</a>
                    <a href="<?php echo e(route('partner.clients.index')); ?>" class="btn btn-outline-secondary btn-sm">Retour</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6"><strong>Code client</strong><br><?php echo e($client->client_code); ?></div>
                        <div class="col-md-6"><strong>Nom complet</strong><br><?php echo e($client->full_name); ?></div>
                        <div class="col-md-6"><strong>Email</strong><br><?php echo e($client->email ?? '—'); ?></div>
                        <div class="col-md-6"><strong>Téléphone</strong><br><?php echo e($client->phone ?? '—'); ?></div>
                        <div class="col-md-6"><strong>Ville</strong><br><?php echo e($client->city ?? '—'); ?></div>
                        <div class="col-md-6"><strong>Code postal</strong><br><?php echo e($client->postal_code ?? '—'); ?></div>
                        <div class="col-12"><strong>Adresse</strong><br><?php echo e($client->address_line_1 ?? '—'); ?></div>
                        <div class="col-md-6"><strong>Nationalité</strong><br><?php echo e($client->nationality ?? '—'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.partner', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\clients\show.blade.php ENDPATH**/ ?>