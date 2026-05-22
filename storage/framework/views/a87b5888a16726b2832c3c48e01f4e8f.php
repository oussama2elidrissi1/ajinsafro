<aside class="v2-rail v3-rail" id="v2-rail">
    <div class="v2-rail-card v3-rail-card">
        <p class="v2-rail-kicker">Progression</p>
        <h3 class="v2-rail-title">Workflow</h3>
        <div class="v3-progress-big"><?php echo e($completedSteps); ?> <span>/ <?php echo e($sectionsCount); ?> etapes validees</span></div>
        <div class="v2-progress" role="progressbar" aria-valuenow="<?php echo e($progressPercent); ?>" aria-valuemin="0" aria-valuemax="100">
            <span id="v2-progress-bar" style="width: <?php echo e($progressPercent); ?>%"></span>
        </div>
        <div class="v3-progress-text-row">
            <p class="v2-progress-text" id="v2-progress-text"><?php echo e($completedSteps); ?> / <?php echo e($sectionsCount); ?> etapes validees</p>
            <strong><?php echo e($progressPercent); ?>%</strong>
        </div>
        <?php if(!empty($nextActionSection)): ?>
            <a href="#<?php echo e($nextActionSection['id']); ?>" class="v3-inline-link">Reprendre le workflow <i class="bx bx-right-arrow-alt"></i></a>
        <?php endif; ?>
    </div>

    <div class="v2-rail-card v3-rail-card" id="v2-save-card" data-state="idle">
        <p class="v2-rail-kicker">Enregistrement</p>
        <h3 class="v2-rail-title" id="v2-save-state">Pret</h3>
        <p class="v2-save-help" id="v2-save-help">Sauvegarde automatique a la navigation entre etapes.</p>
        <div class="v2-rail-actions">
            <button type="button" class="v2-btn v2-btn-primary v2-btn-full" data-v2-save>
                <i class="bx bx-save"></i>
                <span><?php echo e($isCreate ? 'Creer le brouillon' : 'Enregistrer maintenant'); ?></span>
            </button>
            <button type="button" class="v2-btn v2-btn-ghost v2-btn-full" data-v2-save>
                <i class="bx bx-cloud-upload"></i>
                <span>Enregistrer l etape</span>
            </button>
        </div>
    </div>

    <div class="v2-rail-card v3-rail-card">
        <p class="v2-rail-kicker">Actions rapides</p>
        <h3 class="v2-rail-title">Raccourcis d edition</h3>
        <div class="v3-quick-list">
            <?php $__currentLoopData = $v3QuickActions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="#<?php echo e($action['step']); ?>" class="v3-quick-item">
                    <span><i class="bx <?php echo e($action['icon']); ?>"></i><?php echo e($action['label']); ?></span>
                    <i class="bx bx-chevron-right"></i>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <div class="v2-rail-card v3-rail-card">
        <p class="v2-rail-kicker">Resume</p>
        <h3 class="v2-rail-title" id="v2-rail-id"><?php echo e($isCreate ? 'Nouveau voyage' : ('ID #' . $veWpId)); ?></h3>
        <ul class="v2-rail-list">
            <li><span>Statut</span><strong id="v2-rail-status"><?php echo e($statusLabel); ?></strong></li>
            <li><span>Destination</span><strong id="v2-rail-destination"><?php echo e($veDestination ?: '-'); ?></strong></li>
            <li><span>Departs programmes</span><strong><?php echo e($veDatesCount); ?></strong></li>
            <li><span>Prix de base</span><strong><?php echo e($vePriceLabel ?: 'A definir'); ?></strong></li>
        </ul>
    </div>

    <?php if(!empty($alertSection)): ?>
        <div class="v2-rail-card v3-rail-card">
            <div class="v3-alert-head">
                <h3 class="v2-rail-title mb-0">Alertes</h3>
                <span class="v3-alert-count">1</span>
            </div>
            <div class="v3-alert-box">
                <div class="v3-alert-icon"><i class="bx bx-error"></i></div>
                <div>
                    <div class="v3-alert-title">Completer l etape <?php echo e($alertSection['label']); ?></div>
                    <div class="v3-alert-desc">Cette etape demande encore une verification avant publication.</div>
                    <a href="#<?php echo e($alertSection['id']); ?>" class="v3-inline-link">Aller a l etape <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</aside>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\v3\_rail.blade.php ENDPATH**/ ?>