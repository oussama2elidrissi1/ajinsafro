<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'filterAgentOptions',
    'filterAgentId' => null,
    'filterReservationStatus' => null,
    'filterClientAgentId' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'filterAgentOptions',
    'filterAgentId' => null,
    'filterReservationStatus' => null,
    'filterClientAgentId' => null,
]); ?>
<?php foreach (array_filter(([
    'filterAgentOptions',
    'filterAgentId' => null,
    'filterReservationStatus' => null,
    'filterClientAgentId' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $statuses = [
        \App\Models\Reservation::STATUS_EN_COURS => 'En cours',
        \App\Models\Reservation::STATUS_VALIDEE => 'Validée',
        \App\Models\Reservation::STATUS_ANNULEE => 'Annulée',
    ];
?>

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-4 sm:p-5 mt-6">
    <form method="get" action="<?php echo e(route('agent.dashboard')); ?>" class="flex flex-col lg:flex-row flex-wrap gap-3 lg:items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Agent / commercial</label>
            <select name="agent_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-[#0e3a5a] font-medium focus:outline-none focus:border-[#0083c4]">
                <option value="">Tous (périmètre visible)</option>
                <?php $__currentLoopData = $filterAgentOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($opt->id); ?>" <?php if((int) $filterAgentId === (int) $opt->id): echo 'selected'; endif; ?>><?php echo e($opt->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="w-full sm:w-auto min-w-[160px]">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Statut réservation</label>
            <select name="res_status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-[#0e3a5a] font-medium focus:outline-none focus:border-[#0083c4]">
                <option value="">Tous</option>
                <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($val); ?>" <?php if($filterReservationStatus === $val): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Clients par agent</label>
            <select name="client_agent_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-[#0e3a5a] font-medium focus:outline-none focus:border-[#0083c4]">
                <option value="">Tous les clients visibles</option>
                <?php $__currentLoopData = $filterAgentOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($opt->id); ?>" <?php if((int) $filterClientAgentId === (int) $opt->id): echo 'selected'; endif; ?>><?php echo e($opt->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#0083c4] text-white text-sm font-bold shadow-sm hover:opacity-95 transition-opacity">
                <i class="fas fa-filter mr-1"></i> Filtrer
            </button>
            <a href="<?php echo e(route('agent.dashboard')); ?>" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-50 transition-colors">Réinitialiser</a>
        </div>
    </form>
    <p class="text-[11px] text-gray-500 mt-3 mb-0">
        Les filtres s’appliquent aux tableaux « Dernières réservations », « Derniers clients », au calendrier et à l’activité récente. Les indicateurs en tête de page restent sur le périmètre complet.
    </p>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\agent\partials\dashboard-filters.blade.php ENDPATH**/ ?>