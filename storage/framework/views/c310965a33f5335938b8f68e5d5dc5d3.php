<section class="v3-hero-card">
    <div class="v3-hero-thumb<?php echo e(!empty($heroImageUrl) ? ' has-image' : ''); ?>" <?php if(!empty($heroImageUrl)): ?> style="background-image:url('<?php echo e($heroImageUrl); ?>')" <?php endif; ?>>
        <?php if(empty($heroImageUrl)): ?>
            <span class="v3-hero-thumb-badge">Voyage Studio</span>
        <?php endif; ?>
    </div>

    <div class="v3-hero-main">
        <span class="v3-eyebrow"><?php echo e($isCreate ? 'Nouveau voyage' : 'Voyage'); ?></span>
        <div class="v3-hero-title-row">
            <h1 class="v3-hero-title" id="v2-live-title"><?php echo e($headerTitle); ?></h1>
            <span class="v3-status-pill <?php echo e($statusClass); ?>" id="v2-live-status"><?php echo e($statusLabel); ?></span>
        </div>
        <div class="v3-hero-subtitle"><?php echo e($journeySubtitle); ?></div>
        <div class="v3-hero-id" id="v2-live-subtitle"><?php echo e($isCreate ? 'Brouillon a creer au premier enregistrement' : ('ID #' . $veWpId)); ?></div>

        <div class="v3-stat-row">
            <div class="v3-mini-stat">
                <div class="v3-mini-stat__icon"><i class="bx bx-calendar"></i></div>
                <div>
                    <small>Departs programmes</small>
                    <strong><?php echo e($veDatesCount); ?></strong>
                </div>
            </div>
            <div class="v3-mini-stat">
                <div class="v3-mini-stat__icon"><i class="bx bx-wallet"></i></div>
                <div>
                    <small>Prix de base</small>
                    <strong><?php echo e($vePriceLabel ?: 'A definir'); ?></strong>
                </div>
            </div>
            <div class="v3-mini-stat">
                <div class="v3-mini-stat__icon"><i class="bx bx-map"></i></div>
                <div>
                    <small>Destination</small>
                    <strong><?php echo e($veDestination ?: 'A renseigner'); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="v3-hero-actions">
        <a href="<?php echo e(route('admin.circuits.voyages.index')); ?>" class="v3-btn v3-btn-muted">
            <i class="bx bx-arrow-back"></i>
            <span>Retour catalogue</span>
        </a>

        <?php if($frontPreviewUrl): ?>
            <a href="<?php echo e($frontPreviewUrl); ?>" class="v3-btn v3-btn-muted" target="_blank" rel="noopener">
                <i class="bx bx-show-alt"></i>
                <span>Apercu public</span>
            </a>
        <?php else: ?>
            <button type="button" class="v3-btn v3-btn-muted" disabled>
                <i class="bx bx-show-alt"></i>
                <span>Apercu public</span>
            </button>
        <?php endif; ?>

        <?php if(!$isCreate): ?>
            <a href="<?php echo e(route('admin.circuits.voyages.edit', $veWpId)); ?>" class="v3-btn v3-btn-muted" data-v2-classic-link>
                <i class="bx bx-transfer-alt"></i>
                <span>Version classique</span>
            </a>
        <?php endif; ?>

        <button type="button" class="v3-btn v3-btn-primary" data-v2-save>
            <i class="bx bx-save"></i>
            <span><?php echo e($isCreate ? 'Creer le brouillon' : 'Enregistrer maintenant'); ?></span>
        </button>

        <?php if(!empty($nextActionSection)): ?>
            <a href="#<?php echo e($nextActionSection['id']); ?>" class="v3-btn v3-btn-ghost">
                <span>Continuer le workflow</span>
                <i class="bx bx-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
</section>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\v3\_hero.blade.php ENDPATH**/ ?>