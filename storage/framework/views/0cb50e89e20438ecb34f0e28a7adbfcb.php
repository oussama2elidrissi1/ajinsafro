
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['locations', 'level' => 0, 'selectedIds' => [], 'path' => []]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['locations', 'level' => 0, 'selectedIds' => [], 'path' => []]); ?>
<?php foreach (array_filter((['locations', 'level' => 0, 'selectedIds' => [], 'path' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<ul class="wp-location-list destination-tree-list" data-level="<?php echo e($level); ?>" style="padding-left: <?php echo e($level > 0 ? '1.25rem' : '0'); ?>; margin: 0; list-style: none;">
    <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $locPath = array_merge($path, [$location['title']]);
            $pathStr = implode(' �?� ', $locPath);
            $hasChildren = !empty($location['children']);
            $isSelected = in_array($location['id'], $selectedIds);
        ?>
        <li class="wp-location-item destination-tree-item <?php echo e($hasChildren ? 'has-children' : ''); ?>"
            data-id="<?php echo e($location['id']); ?>"
            data-title="<?php echo e(strtolower($location['title'])); ?>"
            data-path="<?php echo e($pathStr); ?>"
            data-has-children="<?php echo e($hasChildren ? '1' : '0'); ?>">
            <div class="destination-tree-row">
                <?php if($hasChildren): ?>
                    <span class="destination-tree-toggle" role="button" aria-expanded="true" title="Replier / Déplier"></span>
                <?php else: ?>
                    <span class="destination-tree-toggle destination-tree-toggle--empty"></span>
                <?php endif; ?>
                <label class="destination-tree-label">
                    <input type="checkbox"
                           name="locations[]"
                           value="<?php echo e($location['id']); ?>"
                           class="location-checkbox destination-checkbox"
                           <?php echo e($isSelected ? 'checked' : ''); ?>

                           data-loc-id="<?php echo e($location['id']); ?>"
                           data-loc-title="<?php echo e(e($location['title'])); ?>">
                    <span class="destination-tree-title"><?php echo e($location['title']); ?></span>
                </label>
            </div>
            <?php if($hasChildren): ?>
                <?php echo $__env->make('admin.circuits.voyages.partials.location-tree', [
                    'locations' => $location['children'],
                    'level' => $level + 1,
                    'selectedIds' => $selectedIds,
                    'path' => $locPath,
                ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
        </li>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</ul>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\location-tree.blade.php ENDPATH**/ ?>