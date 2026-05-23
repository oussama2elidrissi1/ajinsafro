<?php $__env->startSection('title', 'Booking start – AjiNsafro'); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginal0783a137f0e506a7088ffbc77deaba0d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0783a137f0e506a7088ffbc77deaba0d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.front.navbar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('front.navbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0783a137f0e506a7088ffbc77deaba0d)): ?>
<?php $attributes = $__attributesOriginal0783a137f0e506a7088ffbc77deaba0d; ?>
<?php unset($__attributesOriginal0783a137f0e506a7088ffbc77deaba0d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0783a137f0e506a7088ffbc77deaba0d)): ?>
<?php $component = $__componentOriginal0783a137f0e506a7088ffbc77deaba0d; ?>
<?php unset($__componentOriginal0783a137f0e506a7088ffbc77deaba0d); ?>
<?php endif; ?>

    <main class="min-h-screen bg-gray-50">
        <div class="container mx-auto px-4 py-10 max-w-2xl">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6">Booking start</h1>

            <div class="bg-white rounded-lg shadow border border-gray-200 p-6 space-y-4">
                <?php if($item): ?>
                    <div>
                        <span class="text-sm text-gray-500">Voyage</span>
                        <p class="font-medium text-gray-900"><?php echo e($item->name); ?></p>
                    </div>
                <?php else: ?>
                    <div>
                        <span class="text-sm text-gray-500">Type</span>
                        <p class="font-medium text-gray-900"><?php echo e($type ?: '—'); ?></p>
                        <?php if($slug): ?>
                            <p class="text-sm text-gray-500 mt-1">Slug : <?php echo e(e($slug)); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <span class="text-sm text-gray-500">Date</span>
                    <p class="font-medium text-gray-900"><?php echo e($date ?? '—'); ?></p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Adultes</span>
                        <p class="font-medium text-gray-900"><?php echo e($adults); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Enfants</span>
                        <p class="font-medium text-gray-900"><?php echo e($children); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Bébés</span>
                        <p class="font-medium text-gray-900"><?php echo e($infant); ?></p>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <button type="button" class="inline-flex items-center px-4 py-2 bg-brand text-white font-medium rounded-lg hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand">
                        Continue
                    </button>
                    <span class="ml-2 text-sm text-gray-500">(paiement à venir)</span>
                </div>
            </div>
        </div>
    </main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\booking\start.blade.php ENDPATH**/ ?>