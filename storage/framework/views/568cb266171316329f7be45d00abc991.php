<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'stats' => [],
    'subtitle' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'stats' => [],
    'subtitle' => null,
]); ?>
<?php foreach (array_filter(([
    'stats' => [],
    'subtitle' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $fmtMoney = function ($value) {
        $num = is_numeric($value) ? (float) $value : 0.0;
        return number_format($num, 0, ',', ' ') . ' DH';
    };
?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center shrink-0 text-[#0083c4] text-xl">
            <i class="fas fa-suitcase-rolling"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Réservations</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none"><?php echo e($stats['reservations_total'] ?? 0); ?></h4>
            <?php if($subtitle): ?>
                <p class="text-[10px] text-gray-400 mt-1"><?php echo e($subtitle); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-yellow-50 flex items-center justify-center shrink-0 text-yellow-600 text-xl">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">En attente</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none"><?php echo e($stats['reservations_en_cours'] ?? 0); ?></h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center shrink-0 text-green-600 text-xl">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Validées</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none"><?php echo e($stats['reservations_validees'] ?? 0); ?></h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center shrink-0 text-emerald-700 text-xl">
            <i class="fas fa-coins"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Revenu généré</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none"><?php echo e($fmtMoney($stats['revenue_generated'] ?? 0)); ?></h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-indigo-50 flex items-center justify-center shrink-0 text-indigo-700 text-xl">
            <i class="fas fa-hand-holding-dollar"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Commission</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none"><?php echo e($fmtMoney($stats['commission_earned'] ?? 0)); ?></h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-orange-50 flex items-center justify-center shrink-0 text-[#f37a1f] text-xl">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Clients</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none"><?php echo e($stats['clients_count'] ?? 0); ?></h4>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\agent\partials\dashboard-kpis.blade.php ENDPATH**/ ?>