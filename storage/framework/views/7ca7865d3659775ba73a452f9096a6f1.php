<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['recentActivityReservations']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['recentActivityReservations']); ?>
<?php foreach (array_filter((['recentActivityReservations']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-5 mt-6">
    <h3 class="font-bold text-[#0e3a5a] mb-4 flex items-center gap-2 border-b border-gray-100 pb-2">
        <i class="fas fa-history text-[#0083c4]"></i>
        Activité récente (dossiers)
    </h3>
    <ul class="space-y-3 text-sm">
        <?php $__empty_1 = true; $__currentLoopData = $recentActivityReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <li class="flex items-start justify-between gap-3 py-2 border-b border-gray-50 last:border-0">
                <div>
                    <span class="font-semibold text-[#0e3a5a]">#<?php echo e($r->id); ?></span>
                    <span class="text-gray-500 text-xs ml-2"><?php echo e(trim(($r->client_first_name ?? '') . ' ' . ($r->client_last_name ?? '')) ?: 'Client'); ?></span>
                </div>
                <div class="text-right shrink-0">
                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded border
                        <?php if($r->status === \App\Models\Reservation::STATUS_VALIDEE): ?> bg-green-50 text-green-700 border-green-100
                        <?php elseif($r->status === \App\Models\Reservation::STATUS_ANNULEE): ?> bg-red-50 text-red-700 border-red-100
                        <?php else: ?> bg-yellow-50 text-yellow-800 border-yellow-100 <?php endif; ?>"><?php echo e($r->status); ?></span>
                    <p class="text-[10px] text-gray-400 mt-1"><?php echo e(optional($r->created_at)->diffForHumans()); ?></p>
                </div>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <li class="text-gray-500 text-center py-6">Aucune activité récente.</li>
        <?php endif; ?>
    </ul>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\agent\partials\dashboard-activity.blade.php ENDPATH**/ ?>