<aside class="v2-sidebar v3-steps-card" id="v2-sidebar">
    <div class="v3-steps-card__head">
        <div class="v3-steps-card__title">
            <p class="v3-card-kicker">Workflow</p>
            <h2 class="v3-card-title">Etapes du voyage</h2>
            <p class="v3-card-subtitle">Validation par etape avec sauvegarde guidee.</p>
        </div>
        <button type="button" id="workflowToggleBtn" class="workflow-toggle-btn" aria-label="Ouvrir ou fermer le workflow">
            <i class="bx bx-menu"></i>
        </button>
    </div>

    <?php $lastGroup = null; ?>
    <?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $sec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($sec['group'] !== $lastGroup): ?>
            <div class="v2-sb-group"><?php echo e($sec['group']); ?></div>
            <?php $lastGroup = $sec['group']; ?>
        <?php endif; ?>
        <?php
            $stepState = $initialStepStates[$sec['id']] ?? 'incomplete';
            $stateLabel = $stepState === 'complete' ? 'Validee' : ($stepState === 'error' ? 'Erreur' : 'A completer');
        ?>
        <button type="button" class="v2-sb-item<?php echo e($i === 0 ? ' active' : ''); ?> state-<?php echo e($stepState); ?>" data-v2-nav="<?php echo e($sec['id']); ?>" data-v2-step-state="<?php echo e($stepState); ?>">
            <span class="v2-sb-index"><?php echo e($i + 1); ?></span>
            <span class="v2-sb-icon"><i class="bx <?php echo e($sec['icon']); ?>"></i></span>
            <span class="v2-sb-label-wrap">
                <span class="v2-sb-label"><?php echo e($sec['label']); ?></span>
                <span class="v2-sb-meta" data-v2-step-meta><?php echo e($stateLabel); ?></span>
            </span>
            <span class="v2-sb-dot"></span>
        </button>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <div class="v2-sb-footer">
        <button type="button" class="v2-sb-save" data-v2-save>
            <i class="bx bx-save"></i>
            <?php echo e($isCreate ? 'Creer le voyage' : 'Enregistrer l etape courante'); ?>

        </button>
    </div>
</aside>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\v3\_steps.blade.php ENDPATH**/ ?>