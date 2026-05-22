<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'topOffers' => ['labels' => [], 'bookings' => [], 'revenue' => []],
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'topOffers' => ['labels' => [], 'bookings' => [], 'revenue' => []],
]); ?>
<?php foreach (array_filter(([
    'topOffers' => ['labels' => [], 'bookings' => [], 'revenue' => []],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $labels = $topOffers['labels'] ?? [];
    $bookings = $topOffers['bookings'] ?? [];
    $revenue = $topOffers['revenue'] ?? [];
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden lg:col-span-2">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div>
                <h3 class="font-bold text-[#0e3a5a] mb-0">Performance</h3>
                <p class="text-[11px] text-gray-500 mb-0 mt-1">Réservations par offre (top 8)</p>
            </div>
        </div>
        <div class="p-5">
            <div class="h-[260px]">
                <canvas id="agentDashboardBookingsChart" data-labels='<?php echo json_encode($labels, 15, 512) ?>' data-series='<?php echo json_encode($bookings, 15, 512) ?>'></canvas>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a] mb-0">Best-selling offers</h3>
            <p class="text-[11px] text-gray-500 mb-0 mt-1">Top offres par volume</p>
        </div>
        <div class="p-4">
            <?php $__empty_1 = true; $__currentLoopData = $labels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 transition-colors">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate mb-0"><?php echo e($name); ?></p>
                        <p class="text-[11px] text-gray-500 mb-0 mt-0.5">
                            <?php echo e((int) ($bookings[$i] ?? 0)); ?> réservations · <?php echo e(number_format((float) ($revenue[$i] ?? 0), 0, ',', ' ')); ?> DH
                        </p>
                    </div>
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#e6f3fa]/60 border border-[#0083c4]/15 text-[#0083c4] font-black text-sm">
                        <?php echo e($i + 1); ?>

                    </span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="py-10 text-center text-gray-500 text-sm">Aucune donnée de performance.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\agent\partials\dashboard-performance.blade.php ENDPATH**/ ?>