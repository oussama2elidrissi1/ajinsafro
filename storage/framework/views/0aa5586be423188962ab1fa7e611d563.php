<?php $__env->startSection('title'); ?>
    Dashboard AjinsAfro
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Dashboard AjinsAfro</h4>
                <div class="page-title-right">
                    <?php if($stats['can_see_all_branches'] ?? false): ?>
                        <a href="<?php echo e(route('admin.dashboard.vue-globale')); ?>" class="btn btn-primary btn-sm">Vue globale</a>
                    <?php endif; ?>
                    <ol class="breadcrumb m-0 ms-2">
                        <li class="breadcrumb-item"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row g-3">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                            <i class="bx bx-calendar-check font-size-22"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Réservations</p>
                            <h5 class="mb-0"><?php echo e($stats['reservations_total']); ?></h5>
                        </div>
                    </div>
                    <a href="<?php echo e(route('admin.reservations.index')); ?>" class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded p-2 me-3">
                            <i class="bx bx-user font-size-22"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Clients</p>
                            <h5 class="mb-0"><?php echo e($stats['clients_count']); ?></h5>
                        </div>
                    </div>
                    <a href="<?php echo e(route('admin.customers.clients.index')); ?>" class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 text-info rounded p-2 me-3">
                            <i class="bx bx-time-five font-size-22"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">En cours</p>
                            <h5 class="mb-0"><?php echo e($stats['reservations_en_cours']); ?></h5>
                        </div>
                    </div>
                    <a href="<?php echo e(route('admin.reservations.index', ['status' => 'EN_COURS'])); ?>" class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 text-warning rounded p-2 me-3">
                            <i class="bx bx-check-circle font-size-22"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Validées</p>
                            <h5 class="mb-0"><?php echo e($stats['reservations_validees']); ?></h5>
                        </div>
                    </div>
                    <a href="<?php echo e(route('admin.reservations.index', ['status' => 'VALIDEE'])); ?>" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-2">
                    <h6 class="mb-0">
                        <i class="bx bx-group me-2 text-primary"></i>Group Deals
                    </h6>
                    <a href="<?php echo e(route('admin.group-deals.index')); ?>" class="btn btn-outline-primary btn-sm">Gérer</a>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <h4 class="mb-0 text-primary"><?php echo e($groupDealStats['voyages'] ?? 0); ?></h4>
                            <small class="text-muted">Offres</small>
                        </div>
                        <div class="col-4">
                            <h4 class="mb-0 text-info"><?php echo e($groupDealStats['open'] ?? 0); ?></h4>
                            <small class="text-muted">Offres actives</small>
                        </div>
                        <div class="col-4">
                            <h4 class="mb-0 text-success"><?php echo e($groupDealStats['guaranteed'] ?? 0); ?></h4>
                            <small class="text-muted">Offres garanties</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-0">Tableau de bord selon votre rôle et votre agence. Consultez la <a href="<?php echo e(route('admin.dashboard.vue-globale')); ?>">Vue globale</a> pour les statistiques détaillées.</p>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(URL::asset('build/js/app.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\dashboard\index.blade.php ENDPATH**/ ?>