<?php $__env->startSection('title', 'AjiNsafro.ma – Let the journey begin'); ?>

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

    <main>
        <?php if (isset($component)) { $__componentOriginala925c8264495a82a0280093b7652fbed = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala925c8264495a82a0280093b7652fbed = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.front.hero-search','data' => ['activeTab' => 'Hotel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('front.hero-search'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['activeTab' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Hotel')]); ?>
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
                <h2 class="text-2xl md:text-3xl font-bold text-center text-gray-900 mb-8 md:mb-12">Top destinations</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-6xl mx-auto">
                    <?php $__currentLoopData = $destinations ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $destination): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if (isset($component)) { $__componentOriginal396c2bd02978fe6b79792de42f611c9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal396c2bd02978fe6b79792de42f611c9d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.front.destination-card','data' => ['title' => $destination['title'],'image' => $destination['image'],'slug' => $destination['slug'] ?? null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('front.destination-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($destination['title']),'image' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($destination['image']),'slug' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($destination['slug'] ?? null)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal396c2bd02978fe6b79792de42f611c9d)): ?>
<?php $attributes = $__attributesOriginal396c2bd02978fe6b79792de42f611c9d; ?>
<?php unset($__attributesOriginal396c2bd02978fe6b79792de42f611c9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal396c2bd02978fe6b79792de42f611c9d)): ?>
<?php $component = $__componentOriginal396c2bd02978fe6b79792de42f611c9d; ?>
<?php unset($__componentOriginal396c2bd02978fe6b79792de42f611c9d); ?>
<?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php if(empty($destinations)): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-6xl mx-auto">
                        <?php $__currentLoopData = [['title' => 'Dubai', 'image' => 'destinations/dubai.jpg'], ['title' => 'Paris', 'image' => 'destinations/paris.jpg'], ['title' => 'Tokyo', 'image' => 'destinations/tokyo.jpg'], ['title' => 'New York', 'image' => 'destinations/newyork.jpg']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if (isset($component)) { $__componentOriginal396c2bd02978fe6b79792de42f611c9d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal396c2bd02978fe6b79792de42f611c9d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.front.destination-card','data' => ['title' => $d['title'],'image' => $d['image']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('front.destination-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($d['title']),'image' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($d['image'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal396c2bd02978fe6b79792de42f611c9d)): ?>
<?php $attributes = $__attributesOriginal396c2bd02978fe6b79792de42f611c9d; ?>
<?php unset($__attributesOriginal396c2bd02978fe6b79792de42f611c9d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal396c2bd02978fe6b79792de42f611c9d)): ?>
<?php $component = $__componentOriginal396c2bd02978fe6b79792de42f611c9d; ?>
<?php unset($__componentOriginal396c2bd02978fe6b79792de42f611c9d); ?>
<?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="container mx-auto px-4 text-center text-sm">
            &copy; <?php echo e(date('Y')); ?> AjiNsafro.ma. All rights reserved.
        </div>
    </footer>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.front', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\front\home.blade.php ENDPATH**/ ?>