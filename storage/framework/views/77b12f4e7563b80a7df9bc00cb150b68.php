<?php $__env->startSection('title', 'Clients'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Clients</h1>
        <p class="text-sm text-gray-500 mt-1">Votre portefeuille clients (filtré par partenaire connecté).</p>
    </div>
    <a href="<?php echo e(route('partner.clients.create')); ?>" class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm">
        Nouveau client
    </a>
</div>

<div class="bg-white p-4 rounded-2xl shadow-custom border border-gray-100 mb-4">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[220px]">
            <input
                type="text"
                name="search"
                value="<?php echo e(request('search')); ?>"
                placeholder="Rechercher (nom, code, email, téléphone)…"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:border-[#0083c4] focus:bg-white transition-colors text-[#0e3a5a] font-medium placeholder-gray-400"
            >
        </div>
        <button class="bg-[#0083c4] hover:bg-[#0e3a5a] text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm">
            Rechercher
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse whitespace-nowrap">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-4 px-6">Client</th>
                    <th class="py-4 px-6">Code</th>
                    <th class="py-4 px-6">Contact</th>
                    <th class="py-4 px-6">Créé le</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6">
                            <div class="font-bold text-[#0e3a5a]"><?php echo e($c->full_name ?? trim(($c->first_name ?? '').' '.($c->last_name ?? '')) ?: '—'); ?></div>
                            <div class="text-[11px] text-gray-400 font-semibold"><?php echo e($c->city ?? '—'); ?></div>
                        </td>
                        <td class="py-4 px-6 text-gray-600 font-semibold"><?php echo e($c->client_code ?? '—'); ?></td>
                        <td class="py-4 px-6">
                            <div class="text-gray-700 font-medium"><?php echo e($c->email ?? '—'); ?></div>
                            <div class="text-[11px] text-gray-500 font-semibold"><?php echo e($c->phone ?? '—'); ?></div>
                        </td>
                        <td class="py-4 px-6 text-gray-500"><?php echo e($c->created_at?->format('d/m/Y')); ?></td>
                        <td class="py-4 px-6 text-right">
                            <a href="<?php echo e(route('partner.clients.show', $c)); ?>" class="text-[#0083c4] font-bold text-xs hover:underline">Voir</a>
                            <span class="text-gray-300 mx-2">|</span>
                            <a href="<?php echo e(route('partner.clients.edit', $c)); ?>" class="text-gray-600 font-bold text-xs hover:underline">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5" class="py-10 px-6 text-center text-gray-500">Aucun client.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-4">
        <?php echo e($clients->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('partner_v2.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\partner\v2\clients\index.blade.php ENDPATH**/ ?>