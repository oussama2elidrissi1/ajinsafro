<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'days' => [],
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'days' => [],
]); ?>
<?php foreach (array_filter(([
    'days' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $days = $days instanceof \Illuminate\Support\Collection ? $days->values() : collect($days)->values();
?>

<?php if($days->isEmpty()): ?>
    <p class="text-gray-600 rounded-lg bg-gray-100 px-4 py-3">L’itinéraire détaillé sera bientôt disponible.</p>
<?php else: ?>
<section id="itinerary" class="itinerary" aria-label="Programme du circuit">
    <div class="flex flex-col lg:flex-row items-start justify-start gap-3 lg:gap-2.5">
        
        <nav class="itinerary__nav day-plan-nav lg:sticky lg:top-24 flex flex-col lg:w-fit lg:max-w-[180px] lg:flex-none lg:shrink-0 py-1 pr-0 mr-0 min-w-0 max-lg:flex-row max-lg:overflow-x-auto max-lg:gap-2 max-lg:pb-2 max-lg:mb-2" aria-label="Plan de séjour">
        <h3 class="day-plan__title text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3 px-0 max-lg:mb-0 max-lg:shrink-0 max-lg:mr-1">Plan de séjour</h3>
            <ul class="day-plan list-none m-0 p-0 pl-5 relative before:content-[''] before:absolute before:left-[5px] before:top-2 before:bottom-2 before:w-px before:bg-gray-200 max-lg:flex max-lg:flex-row max-lg:flex-nowrap max-lg:p-0 max-lg:before:hidden max-lg:shrink-0 max-lg:gap-2">
                <?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $dayNum = $day->day_number ?? ($index + 1);
                        $isFree = ($day->day_type ?? '') === 'libre';
                        $label = $isFree ? 'Jour libre' : (trim($day->title ?? '') !== '' ? \Illuminate\Support\Str::limit($day->title, 24) : 'Jour ' . $dayNum);
                        $isActive = $index === 0;
                    ?>
                    <li class="day-plan__item relative max-lg:shrink-0">
                        <button
                            type="button"
                            class="day-plan__link aj-day-nav-item flex items-center gap-2.5 w-auto min-w-0 py-1.5 pr-2.5 pl-0 border-0 bg-transparent text-left cursor-pointer rounded-full text-sm text-gray-800 transition-colors hover:bg-gray-100 <?php echo e($isActive ? 'active is-active bg-blue-600 text-white hover:bg-blue-600' : ''); ?>"
                            data-day-index="<?php echo e($index); ?>"
                            data-day="<?php echo e($dayNum); ?>"
                            data-aj-nav-day="<?php echo e($dayNum); ?>"
                            id="aj-day-nav-<?php echo e($dayNum); ?>"
                        >
                            <span class="day-plan__dot shrink-0 w-2 h-2 rounded-full <?php echo e($isActive ? 'bg-white' : 'bg-gray-300'); ?>"></span>
                            <span class="day-plan__label truncate"><?php echo e(e($label)); ?></span>
                        </button>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </nav>

        
        <div class="itinerary__content min-w-0 flex-1 space-y-4 pl-0 ml-0">
            <?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $dayNum = $day->day_number ?? ($index + 1);
                ?>
                <div
                    class="ajtb-day-content-panel day-card rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden"
                    id="aj-day-panel-<?php echo e($dayNum); ?>"
                    data-aj-day-panel="<?php echo e($dayNum); ?>"
                    data-day="<?php echo e($dayNum); ?>"
                    data-day-index="<?php echo e($index); ?>"
                    role="tabpanel"
                    aria-labelledby="aj-day-nav-<?php echo e($dayNum); ?>"
                >
                    <?php if (isset($component)) { $__componentOriginal5411422c3f086e16be4b396bed711527 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5411422c3f086e16be4b396bed711527 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.front.itinerary-day','data' => ['day' => $day,'isFirst' => $index === 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('front.itinerary-day'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['day' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($day),'isFirst' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($index === 0)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5411422c3f086e16be4b396bed711527)): ?>
<?php $attributes = $__attributesOriginal5411422c3f086e16be4b396bed711527; ?>
<?php unset($__attributesOriginal5411422c3f086e16be4b396bed711527); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5411422c3f086e16be4b396bed711527)): ?>
<?php $component = $__componentOriginal5411422c3f086e16be4b396bed711527; ?>
<?php unset($__componentOriginal5411422c3f086e16be4b396bed711527); ?>
<?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <script>
        (function() {
            var section = document.getElementById('itinerary');
            if (!section) return;
            var tabs = section.querySelectorAll('[data-aj-nav-day]');
            if (!tabs.length) return;
            function setActive(dayNum) {
                dayNum = String(dayNum);
                tabs.forEach(function(t) {
                    var on = t.getAttribute('data-aj-nav-day') === dayNum;
                    t.classList.toggle('active', on);
                    t.classList.toggle('is-active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                    var dot = t.querySelector('.day-plan__dot');
                    if (dot) {
                        dot.classList.toggle('bg-white', on);
                        dot.classList.toggle('bg-gray-300', !on);
                    }
                });
            }
            function scrollToDay(dayNum) {
                setActive(dayNum);
                var el = document.getElementById('aj-day-panel-' + dayNum);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            tabs.forEach(function(t) {
                t.addEventListener('click', function(e) {
                    e.preventDefault();
                    var day = t.getAttribute('data-aj-nav-day');
                    if (day) scrollToDay(day);
                });
            });
        })();
    </script>
</section>
<?php endif; ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\front\program-day-timeline.blade.php ENDPATH**/ ?>