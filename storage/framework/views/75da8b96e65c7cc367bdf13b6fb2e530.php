<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'day',
    'isFirst' => false,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'day',
    'isFirst' => false,
]); ?>
<?php foreach (array_filter(([
    'day',
    'isFirst' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $open = $isFirst;
?>
<details class="group border border-gray-200 rounded-lg overflow-hidden" <?php echo e($open ? 'open' : ''); ?>>
    <summary class="flex items-center justify-between gap-4 px-4 py-4 cursor-pointer list-none bg-white hover:bg-gray-50 transition-colors [&::-webkit-details-marker]:hidden">
        <span class="font-semibold text-gray-900">
            Jour <?php echo e($day->day_number); ?> – <?php echo e(e($day->title)); ?>

        </span>
        <span class="flex items-center gap-2 shrink-0">
            <?php if($day->city): ?>
                <span class="text-sm text-gray-500"><?php echo e(e($day->city)); ?></span>
            <?php endif; ?>
            <?php if($day->day_label_badge): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                    <?php echo e(e($day->day_label_badge)); ?>

                </span>
            <?php endif; ?>
            <svg class="w-5 h-5 text-gray-500 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </span>
    </summary>
    <div class="border-t border-gray-200">
        <div class="px-4 py-4 bg-gray-50/50 prose prose-sm max-w-none text-gray-700">
            <?php if($day->content_html): ?>
                <?php echo $day->content_html; ?>

            <?php elseif($day->description): ?>
                <?php echo nl2br(e($day->description)); ?>

            <?php endif; ?>
            <?php if($day->hasMealBreakfast() || $day->hasMealLunch() || $day->hasMealDinner()): ?>
                <p class="mt-3 text-sm text-gray-600">
                    Repas :
                    <?php if($day->hasMealBreakfast()): ?> <span class="inline-block mr-2">Petit-déjeuner</span> <?php endif; ?>
                    <?php if($day->hasMealLunch()): ?> <span class="inline-block mr-2">Déjeuner</span> <?php endif; ?>
                    <?php if($day->hasMealDinner()): ?> <span class="inline-block">Dîner</span> <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</details>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\front\itinerary-day.blade.php ENDPATH**/ ?>