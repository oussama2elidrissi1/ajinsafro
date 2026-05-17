<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'voyage',
    'nextDeparture' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'voyage',
    'nextDeparture' => null,
]); ?>
<?php foreach (array_filter(([
    'voyage',
    'nextDeparture' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="sticky top-[140px] rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-100 bg-gray-50/50">
        <?php if($voyage->price_from !== null): ?>
            <p class="text-sm text-gray-500">À partir de</p>
            <p class="text-2xl font-bold text-gray-900">
                <?php echo e(number_format($voyage->price_from, 0, ',', ' ')); ?> <span class="text-lg font-normal"><?php echo e($voyage->currency_symbol); ?></span>
            </p>
            <?php if($voyage->old_price && $voyage->old_price > $voyage->price_from): ?>
                <p class="mt-1 text-sm text-gray-500">
                    <span class="line-through"><?php echo e(number_format($voyage->old_price, 0, ',', ' ')); ?> <?php echo e($voyage->currency_symbol); ?></span>
                    <span class="text-green-600 font-medium">-<?php echo e($voyage->discount_percent); ?>%</span>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-lg font-semibold text-gray-900">Sur demande</p>
        <?php endif; ?>
    </div>

    <div class="p-5 space-y-4">
        <?php if($nextDeparture): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prochain départ</label>
                <p class="text-gray-900"><?php echo e($nextDeparture->start_date->format('d/m/Y')); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adultes</label>
                <select class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand focus:border-brand" aria-label="Nombre d'adultes">
                    <?php $__currentLoopData = range(1, 8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($n); ?>"><?php echo e($n); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Enfants</label>
                <select class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand focus:border-brand" aria-label="Nombre d'enfants">
                    <?php $__currentLoopData = range(0, 6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($n); ?>"><?php echo e($n); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>

        <a
            href="<?php echo e(route('front.voyages.index')); ?>#contact"
            class="block w-full text-center py-3 px-4 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark transition"
        >
            Demander un devis
        </a>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\front\price-box.blade.php ENDPATH**/ ?>