<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'statsPersonal' => [],
    'statsTeamOnly' => [],
    'teamAgentStats',
    'directReports',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'statsPersonal' => [],
    'statsTeamOnly' => [],
    'teamAgentStats',
    'directReports',
]); ?>
<?php foreach (array_filter(([
    'statsPersonal' => [],
    'statsTeamOnly' => [],
    'teamAgentStats',
    'directReports',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="space-y-6 mt-8">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2">
        <h2 class="text-lg font-bold text-[#0e3a5a] flex items-center gap-2">
            <i class="fas fa-user-tie text-[#0083c4]"></i>
            Pilotage équipe
        </h2>
        <p class="text-xs text-gray-500">Vue consolidée : vos dossiers + ceux des commerciaux rattachés (même agence).</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-5">
            <h3 class="text-sm font-bold text-[#0e3a5a] mb-4 flex items-center gap-2 border-b border-gray-100 pb-2">
                <i class="fas fa-user text-[#0083c4]"></i> Mes indicateurs
            </h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-gray-100 bg-[#e6f3fa]/30 p-3 text-center">
                    <p class="text-[10px] text-gray-500 font-bold uppercase">Mes résa.</p>
                    <p class="text-2xl font-black text-[#0083c4]"><?php echo e($statsPersonal['reservations_total'] ?? 0); ?></p>
                </div>
                <div class="rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-[10px] text-gray-500 font-bold uppercase">Mes clients</p>
                    <p class="text-2xl font-black text-[#0e3a5a]"><?php echo e($statsPersonal['clients_count'] ?? 0); ?></p>
                </div>
                <div class="rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-[10px] text-gray-500 font-bold uppercase">En cours</p>
                    <p class="text-xl font-bold text-yellow-600"><?php echo e($statsPersonal['reservations_en_cours'] ?? 0); ?></p>
                </div>
                <div class="rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-[10px] text-gray-500 font-bold uppercase">Validées</p>
                    <p class="text-xl font-bold text-green-600"><?php echo e($statsPersonal['reservations_validees'] ?? 0); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-custom border border-amber-100 p-5 bg-amber-50/20">
            <h3 class="text-sm font-bold text-[#0e3a5a] mb-4 flex items-center gap-2 border-b border-amber-100/80 pb-2">
                <i class="fas fa-users text-amber-600"></i> Équipe (hors moi)
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Résa. équipe</p>
                    <p class="text-xl font-black text-[#0e3a5a]"><?php echo e($statsTeamOnly['reservations_total'] ?? 0); ?></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-[10px] text-gray-400 font-bold uppercase">En cours</p>
                    <p class="text-xl font-black text-yellow-600"><?php echo e($statsTeamOnly['reservations_en_cours'] ?? 0); ?></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Validées</p>
                    <p class="text-xl font-black text-green-600"><?php echo e($statsTeamOnly['reservations_validees'] ?? 0); ?></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 p-3 text-center">
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Clients équipe</p>
                    <p class="text-xl font-black text-[#f37a1f]"><?php echo e($statsTeamOnly['clients_count'] ?? 0); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a] flex items-center gap-2">
                <i class="fas fa-id-badge text-[#0083c4]"></i>
                Commerciaux rattachés & volume de dossiers
            </h3>
            <span class="text-xs text-gray-500 font-medium"><?php echo e($directReports->count()); ?> agent(s)</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[640px]">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-3 px-6">Commercial</th>
                    <th class="py-3 px-6">Contact</th>
                    <th class="py-3 px-6 text-center">Total résa.</th>
                    <th class="py-3 px-6 text-center">En cours</th>
                    <th class="py-3 px-6 text-center">Validées</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                <?php $__empty_1 = true; $__currentLoopData = $teamAgentStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $u = $row['user']; ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-6">
                            <span class="font-bold text-[#0e3a5a]"><?php echo e($u->name); ?></span>
                            <?php if($u->job_title): ?>
                                <span class="block text-[10px] text-gray-500"><?php echo e($u->job_title); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-6 text-gray-600 text-xs"><?php echo e($u->email ?: '—'); ?></td>
                        <td class="py-3 px-6 text-center font-bold text-[#0e3a5a]"><?php echo e($row['reservations_total']); ?></td>
                        <td class="py-3 px-6 text-center text-yellow-600 font-semibold"><?php echo e($row['reservations_en_cours']); ?></td>
                        <td class="py-3 px-6 text-center text-green-600 font-semibold"><?php echo e($row['reservations_validees']); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="py-10 px-6 text-center text-gray-500">
                            Aucun commercial rattaché (paramètre « Manager » sur la fiche utilisateur).
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\agent\partials\dashboard-manager-panels.blade.php ENDPATH**/ ?>