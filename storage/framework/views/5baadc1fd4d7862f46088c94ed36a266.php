<?php $__env->startSection('title', 'Réservations'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Réservations</h1>
        <p class="text-sm text-gray-500 mt-1">Suivez et gérez vos réservations (uniquement vos données).</p>
    </div>
    <a href="<?php echo e(route('partner.reservations.create')); ?>" class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm">
        Nouvelle réservation
    </a>
</div>

<div class="bg-white p-4 rounded-2xl shadow-custom border border-gray-100 mb-4 flex flex-wrap items-center gap-3">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <select name="status" class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#0083c4] text-[#0e3a5a] font-medium cursor-pointer">
            <option value="">Tous les statuts</option>
            <option value="EN_COURS" <?php echo e(request('status') === 'EN_COURS' ? 'selected' : ''); ?>>En cours</option>
            <option value="VALIDEE" <?php echo e(request('status') === 'VALIDEE' ? 'selected' : ''); ?>>Validée</option>
            <option value="ANNULEE" <?php echo e(request('status') === 'ANNULEE' ? 'selected' : ''); ?>>Annulée</option>
        </select>
        <button class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-5 py-2 rounded-xl text-sm font-bold transition-colors shadow-sm">
            Filtrer
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-4 px-6">Offre</th>
                    <th class="py-4 px-6">Créée par</th>
                    <th class="py-4 px-6">Agence</th>
                    <th class="py-4 px-6">Client</th>
                    <th class="py-4 px-6">Statut</th>
                    <th class="py-4 px-6">Créée le</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php $__empty_1 = true; $__currentLoopData = $reservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $status = $reservation->status;
                        $badge = $status === \App\Models\Reservation::STATUS_VALIDEE ? 'bg-green-50 text-green-700 border-green-200' : ($status === \App\Models\Reservation::STATUS_ANNULEE ? 'bg-red-50 text-red-700 border-red-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200');
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6 font-semibold text-gray-800"><?php echo e($reservation->offer?->name ?? '—'); ?></td>
                        <td class="py-4 px-6 text-gray-600"><?php echo e($reservation->creator?->name ?? '—'); ?></td>
                        <td class="py-4 px-6 text-gray-600"><?php echo e($reservation->agency_label ?? '—'); ?></td>
                        <td class="py-4 px-6 text-gray-600"><?php echo e(trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—'); ?></td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border <?php echo e($badge); ?>"><?php echo e($status); ?></span>
                        </td>
                        <td class="py-4 px-6 text-gray-500"><?php echo e($reservation->created_at?->format('d/m/Y H:i')); ?></td>
                        <td class="py-4 px-6 text-right">
                            <a href="<?php echo e(route('partner.reservations.show', $reservation)); ?>" class="text-[#0083c4] font-bold text-xs hover:underline">Ouvrir</a>
                            <span class="text-gray-300 mx-2">|</span>
                            <a href="<?php echo e(route('partner.reservations.edit', $reservation)); ?>" class="text-gray-600 font-bold text-xs hover:underline">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="py-10 px-6 text-center text-gray-500">Aucune réservation.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4">
        <?php echo e($reservations->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('partner_v2.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\v2\reservations\index.blade.php ENDPATH**/ ?>