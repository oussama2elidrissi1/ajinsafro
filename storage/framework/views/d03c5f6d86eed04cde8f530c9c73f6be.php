<?php $__env->startSection('title', 'Search – AjiNsafro.ma'); ?>

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

    <main class="min-h-screen">
        <?php if (isset($component)) { $__componentOriginala925c8264495a82a0280093b7652fbed = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala925c8264495a82a0280093b7652fbed = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.front.hero-search','data' => ['activeTab' => request()->get('type', 'Hotel'),'location' => request()->get('location'),'checkIn' => request()->get('check_in'),'checkOut' => request()->get('check_out'),'guests' => request()->get('guests', '1 guest, 1 room')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('front.hero-search'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['activeTab' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->get('type', 'Hotel')),'location' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->get('location')),'checkIn' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->get('check_in')),'checkOut' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->get('check_out')),'guests' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->get('guests', '1 guest, 1 room'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala925c8264495a82a0280093b7652fbed)): ?>
<?php $attributes = $__attributesOriginala925c8264495a82a0280093b7652fbed; ?>
<?php unset($__attributesOriginala925c8264495a82a0280093b7652fbed); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala925c8264495a82a0280093b7652fbed)): ?>
<?php $component = $__componentOriginala925c8264495a82a0280093b7652fbed; ?>
<?php unset($__componentOriginala925c8264495a82a0280093b7652fbed); ?>
<?php endif; ?>

        <section class="bg-white py-12 md:py-16">
            <div class="container mx-auto px-4">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-6">Search results</h2>
                <p class="text-gray-600">
                    <?php if(request()->hasAny(['location', 'check_in', 'check_out', 'guests'])): ?>
                        Results for: <strong><?php echo e(request()->get('location', 'Any location')); ?></strong>
                        <?php if(request()->get('check_in')): ?> · Check-in <?php echo e(request()->get('check_in')); ?> <?php endif; ?>
                        <?php if(request()->get('check_out')): ?> · Check-out <?php echo e(request()->get('check_out')); ?> <?php endif; ?>
                        <?php if(request()->get('guests')): ?> · <?php echo e(request()->get('guests')); ?> <?php endif; ?>
                    <?php else: ?>
                        Enter search criteria above to see results.
                    <?php endif; ?>
                </p>
                <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="container mx-auto px-4 text-center text-sm">
            &copy; <?php echo e(date('Y')); ?> AjiNsafro.ma. All rights reserved.
        </div>
    </footer>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\front\search.blade.php ENDPATH**/ ?>