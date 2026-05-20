<?php
    $adminV2BrandName = $adminV2BrandName ?? \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
?>
<footer class="aj-footer">
    <div>© <?php echo e(now()->year); ?> <?php echo e($adminV2BrandName); ?> �?" Tous droits réservés.</div>
    <div>Admin V2</div>
</footer>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\partials\footer-v2.blade.php ENDPATH**/ ?>