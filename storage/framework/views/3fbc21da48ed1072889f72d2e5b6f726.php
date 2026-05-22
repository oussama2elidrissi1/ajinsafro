<!-- ========== Left Sidebar Start (AjinsAfro) ========== -->
<!-- DIAG_MARKER: SIDEBAR_AJINSAFRO_RENDER_BEGIN [<?php echo e(\Illuminate\Support\Str::random(6)); ?>] file=resources/views/layouts/partials/sidebar-ajinsafro.blade.php -->
<div class="vertical-menu">
    <div class="h-100">
        <?php echo $__env->make('admin.partials.sidebar-v2', ['sidebarContext' => 'admin-classic'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
</div>
<!-- DIAG_MARKER: SIDEBAR_AJINSAFRO_RENDER_END -->
<!-- Left Sidebar End -->

<?php $__env->startPush('body-end'); ?>
    <script src="<?php echo e(URL::asset('js/admin-sidebar-v2.js')); ?>"></script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\layouts\partials\sidebar-ajinsafro.blade.php ENDPATH**/ ?>