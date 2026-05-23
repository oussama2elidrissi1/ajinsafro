
<div class="v2-section-footer">
    <div>
        <?php if(!empty($prev)): ?>
            <button type="button" class="v2-nav-btn" data-v2-prev="<?php echo e($prev); ?>">
                <i class="bx bx-chevron-left"></i>
                <?php echo e($prevLabel ?? 'Précédent'); ?>

            </button>
        <?php endif; ?>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <button type="button" class="v2-btn v2-btn-primary" data-v2-save style="padding:8px 18px;font-size:13px;">
            <i class="bx bx-save"></i>
            Enregistrer cette étape
        </button>
        <?php if(!empty($next)): ?>
            <button type="button" class="v2-nav-btn" data-v2-next="<?php echo e($next); ?>">
                <?php echo e($nextLabel ?? 'Suivant'); ?>

                <i class="bx bx-chevron-right"></i>
            </button>
        <?php endif; ?>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\v2\_footer.blade.php ENDPATH**/ ?>