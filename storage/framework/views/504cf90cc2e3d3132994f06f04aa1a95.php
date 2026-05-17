<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0 font-size-18"><?php echo e($title ?? ''); ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <?php if(!empty($li_1 ?? null)): ?>
                        <li class="breadcrumb-item"><?php echo $li_1; ?></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active"><?php echo e($title ?? ''); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\components\breadcrumb.blade.php ENDPATH**/ ?>