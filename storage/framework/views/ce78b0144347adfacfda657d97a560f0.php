

<?php $__env->startSection('title', 'Dashboard point de vente'); ?>

<?php $__env->startSection('content'); ?>
<div class="aj-page-head" style="margin-bottom:18px;">
    <div>
        <h1><?php echo e($agency->display_name); ?></h1>
        <p>Vue operationnelle du point de vente, des reservations et des comptes lies.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <?php if(Route::has('admin.agencies.index')): ?>
            <a href="<?php echo e(route('admin.agencies.index')); ?>" class="aj-btn">Retour points de vente</a>
        <?php endif; ?>
        <?php if(Route::has('admin.agency-accounts.index')): ?>
            <a href="<?php echo e(route('admin.agency-accounts.index', ['branch_id' => $agency->id])); ?>" class="aj-btn">Comptes points de vente</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="aj-card"><div class="aj-card-body"><div class="aj-subtle">RÃ©servations</div><div style="font-size:28px;font-weight:900;color:#172b4d;"><?php echo e((int) $agency->reservations_count); ?></div></div></div></div>
    <div class="col-md-3"><div class="aj-card"><div class="aj-card-body"><div class="aj-subtle">EmployÃ©s actifs</div><div style="font-size:28px;font-weight:900;color:#172b4d;"><?php echo e((int) $agency->agency_employees_count); ?></div></div></div></div>
    <div class="col-md-3"><div class="aj-card"><div class="aj-card-body"><div class="aj-subtle">CA point de vente</div><div style="font-size:28px;font-weight:900;color:#172b4d;"><?php echo e(number_format($revenueTotal, 0, ',', ' ')); ?> â‚¬</div></div></div></div>
    <div class="col-md-3"><div class="aj-card"><div class="aj-card-body"><div class="aj-subtle">Non affectÃ©es</div><div style="font-size:28px;font-weight:900;color:#172b4d;"><?php echo e((int) $unassignedReservationsCount); ?></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="aj-card">
            <div class="aj-card-head"><strong>DerniÃ¨res rÃ©servations</strong></div>
            <div class="aj-card-body" style="padding-top:14px;overflow-x:auto;">
                <table class="aj-table">
                    <thead>
                        <tr><th>#</th><th>Client</th><th>Voyage</th><th>Agent</th><th>Statut</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $reservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>#<?php echo e($reservation->id); ?></td>
                                <td><?php echo e(trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: 'â€”'); ?></td>
                                <td><?php echo e($reservation->tour?->name ?? 'â€”'); ?></td>
                                <td><?php echo e($reservation->agent?->name ?? 'â€”'); ?></td>
                                <td><?php echo e(ucfirst((string) $reservation->status)); ?></td>
                                <td><?php echo e($reservation->created_at?->timezone('Africa/Casablanca')?->format('d/m/Y H:i') ?? 'â€”'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="6" style="text-align:center;color:#71829a;font-weight:700;">Aucune rÃ©servation.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="aj-card mb-3">
            <div class="aj-card-head"><strong>EmployÃ©s actifs</strong></div>
            <div class="aj-card-body">
                <?php $__empty_1 = true; $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #edf2f7;">
                        <div>
                            <div style="font-weight:900;color:#172b4d;"><?php echo e($employee->full_name); ?></div>
                            <div class="aj-subtle"><?php echo e($employee->position ?? 'â€”'); ?></div>
                        </div>
                        <span class="aj-badge ok"><?php echo e($employee->user?->roles->first()?->name ?? 'EmployÃ©'); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="aj-subtle">Aucun employÃ© actif.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="aj-card">
            <div class="aj-card-head"><strong>Indicateurs</strong></div>
            <div class="aj-card-body">
                <div class="aj-subtle">RÃ©servations en attente : <?php echo e($pendingReservationsCount); ?></div>
                <div class="aj-subtle">RÃ©servations non affectÃ©es : <?php echo e($unassignedReservationsCount); ?></div>
                <div class="aj-subtle">Point de vente : <?php echo e($agency->agency_type ? ucfirst($agency->agency_type) : 'â€”'); ?></div>
                <div class="aj-subtle">Manager : <?php echo e($agency->manager?->name ?? 'â€”'); ?></div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\agencies\dashboard.blade.php ENDPATH**/ ?>