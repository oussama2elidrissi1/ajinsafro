

<?php $__env->startSection('title', 'Compte point de vente'); ?>

<?php $__env->startSection('content'); ?>
<div class="aj-page-head" style="margin-bottom:18px;">
    <div>
        <h1><?php echo e($account->name); ?></h1>
        <p><?php echo e($account->email); ?> �?� <?php echo e($account->branch?->display_name ?? 'Aucun point de vente'); ?></p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <?php if(Route::has('admin.agency-accounts.edit')): ?>
            <a href="<?php echo e(route('admin.agency-accounts.edit', $account)); ?>" class="aj-btn primary">�?diter</a>
        <?php endif; ?>
        <?php if(Route::has('admin.agency-accounts.reset-password')): ?>
            <form method="POST" action="<?php echo e(route('admin.agency-accounts.reset-password', $account)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="aj-btn">Reset password</button>
            </form>
        <?php endif; ?>
        <?php if(Route::has('admin.agency-accounts.disable')): ?>
            <form method="POST" action="<?php echo e(route('admin.agency-accounts.disable', $account)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <button type="submit" class="aj-btn">Désactiver</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="aj-card">
            <div class="aj-card-body text-center">
                <img src="<?php echo e($account->avatar_url); ?>" alt="<?php echo e($account->name); ?>" style="width:96px;height:96px;border-radius:50%;object-fit:cover;margin-bottom:14px;">
                <h3 style="margin:0 0 6px;font-weight:900;"><?php echo e($account->name); ?></h3>
                <div class="aj-subtle"><?php echo e($account->roles->first()?->name ?? 'Sans rôle'); ?></div>
                <div style="margin-top:12px;">
                    <?php if($account->is_active): ?>
                        <span class="aj-badge ok">Actif</span>
                    <?php else: ?>
                        <span class="aj-badge off">Inactif</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="aj-card mt-3">
            <div class="aj-card-body">
                <strong style="display:block;margin-bottom:10px;">Informations</strong>
                <div class="aj-subtle">Employé lié : <?php echo e($account->agencyEmployee?->full_name ?? 'Aucun'); ?></div>
                <div class="aj-subtle">Fonction : <?php echo e($account->job_title ?? '�?"'); ?></div>
                <div class="aj-subtle">Téléphone : <?php echo e($account->phone ?? '�?"'); ?></div>
                <div class="aj-subtle">Dernière connexion : <?php echo e($account->last_login_at?->timezone('Africa/Casablanca')?->format('d/m/Y H:i') ?? 'Jamais'); ?></div>
                <div class="aj-subtle">Réservations affectées : <?php echo e((int) ($account->assigned_reservations_count ?? 0)); ?></div>
            </div>
        </div>

        <div class="aj-card mt-3">
            <div class="aj-card-body">
                <strong style="display:block;margin-bottom:10px;">Permissions principales</strong>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php $__currentLoopData = $account->getAllPermissions()->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span class="aj-badge soft"><?php echo e($permission->name); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="aj-card">
            <div class="aj-card-head">
                <div>
                    <strong style="font-size:16px;">Réservations affectées</strong>
                    <div class="aj-subtle">Dernières réservations liées à ce compte</div>
                </div>
            </div>
            <div class="aj-card-body" style="padding-top:14px;overflow-x:auto;">
                <table class="aj-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Voyage</th>
                            <th>Point de vente</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $recentReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>#<?php echo e($reservation->id); ?></td>
                                <td><?php echo e(trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '�?"'); ?></td>
                                <td><?php echo e($reservation->tour?->name ?? '�?"'); ?></td>
                                <td><?php echo e($reservation->branch?->display_name ?? '�?"'); ?></td>
                                <td><?php echo e(ucfirst((string) $reservation->status)); ?></td>
                                <td><?php echo e($reservation->created_at?->timezone('Africa/Casablanca')?->format('d/m/Y H:i') ?? '�?"'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="6" style="text-align:center;color:#71829a;font-weight:700;">Aucune réservation affectée.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin-v6', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\agency-accounts\show.blade.php ENDPATH**/ ?>