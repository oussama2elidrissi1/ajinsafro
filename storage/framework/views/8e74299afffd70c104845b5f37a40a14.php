<?php
    $node = $node ?? [];
    $children = $node['children'] ?? [];
    $hasChildren = $children !== [];
    $itemClasses = $hasChildren && ($node['open'] ?? false) ? 'mm-active' : '';
    $toggleClasses = trim(($hasChildren ? 'has-arrow waves-effect ' : 'waves-effect ') . (($node['active'] ?? false) ? 'mm-active active' : ''));
?>

<li class="<?php echo e($itemClasses); ?>" data-menu-key="<?php echo e($node['key'] ?? ''); ?>" data-menu-open="<?php echo e(!empty($node['open']) ? '1' : '0'); ?>">
    <?php if($hasChildren): ?>
        <a href="javascript:void(0);" class="<?php echo e($toggleClasses); ?>" aria-expanded="<?php echo e(!empty($node['open']) ? 'true' : 'false'); ?>">
            <?php if(!empty($node['icon'])): ?>
                <i class="<?php echo e($node['icon']); ?>"></i>
            <?php endif; ?>
            <span><?php echo e($node['label']); ?></span>
        </a>
        <ul class="sub-menu mm-collapse <?php echo e(!empty($node['open']) ? 'mm-show' : ''); ?>" data-menu-open="<?php echo e(!empty($node['open']) ? '1' : '0'); ?>">
            <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('layouts.partials.sidebar-ajinsafro-node', ['node' => $child], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    <?php else: ?>
        <a href="<?php echo e($node['href'] ?? 'javascript:void(0);'); ?>"
           class="<?php echo e($toggleClasses); ?>"
           data-menu-active="<?php echo e(!empty($node['active']) ? '1' : '0'); ?>">
            <?php if(!empty($node['icon'])): ?>
                <i class="<?php echo e($node['icon']); ?>"></i>
            <?php endif; ?>
            <span><?php echo e($node['label']); ?></span>
            <?php if(($node['key'] ?? null) === 'messagerie' && ($unreadCount ?? 0) > 0): ?>
                <span class="badge rounded-pill bg-primary float-end"><?php echo e($unreadCount); ?></span>
            <?php endif; ?>
        </a>
    <?php endif; ?>
</li>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\partials\sidebar-ajinsafro-node.blade.php ENDPATH**/ ?>