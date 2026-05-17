<?php
    $formId = $formId ?? 'edit-voyage-form';
    $currentStatus = $currentStatus ?? old('post_status', $voyage->post_status ?? 'draft');
    $cancelUrl = $cancelUrl ?? route('admin.circuits.voyages.index');
    $deleteFormId = $deleteFormId ?? 'delete-voyage-form';
    $pageTitle = $isCreate ? 'Creer un voyage' : ($voyage->post_title ?? $voyage->name);
    $statusLabel = match ($currentStatus) {
        'publish' => 'Publié',
        'draft' => 'Brouillon',
        'private' => 'Archivé',
        default => 'En attente',
    };
    $pageSubtitle = $isCreate
        ? 'Complétez la fiche, les départs, le programme et les services.'
        : 'Fiche voyage — départs, programme et options.';
    $lastUpdated = !$isCreate && $voyage->post_modified
        ? \Carbon\Carbon::parse($voyage->post_modified)->locale('fr')->translatedFormat('d M Y H:i')
        : null;

    $publicShowUrl = (!$isCreate && $laravelV && !empty($laravelV->slug))
        ? url('/voyages/' . $laravelV->slug)
        : null;
?>

<div class="ve-page-header">
    <div class="ve-header-topbar">
        <ul class="ve-breadcrumb mb-0">
            <li><a href="<?php echo e(route('admin.dashboard')); ?>"><i class="bx bx-home-alt"></i> Admin</a></li>
            <li><a href="<?php echo e(route('admin.circuits.index')); ?>">Circuits</a></li>
            <li><a href="<?php echo e(route('admin.circuits.voyages.index')); ?>">Voyages</a></li>
            <li class="active"><?php echo e($isCreate ? 'Creation' : 'Edition'); ?></li>
        </ul>

        <div class="ve-header-topbar__meta">
            <span class="ve-meta-pill ve-meta-pill--soft"><?php echo e($isCreate ? 'Nouveau dossier' : 'Edition en cours'); ?></span>
            <?php if(!$isCreate && $veWpId): ?>
                <span class="ve-meta-pill ve-meta-pill--muted">Ref. #<?php echo e($veWpId); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="ve-header-main ve-header-main--compact">
        <div class="ve-header-main__content">
            <h1 class="ve-page-title mb-1"><?php echo e($pageTitle); ?></h1>
            <p class="ve-page-subtitle mb-0"><?php echo e($pageSubtitle); ?></p>

            <div class="ve-header-meta-line">
                <?php if($veDestination): ?>
                    <span class="ve-meta-pill"><i class="bx bx-map"></i> <?php echo e(Str::limit($veDestination, 42)); ?></span>
                <?php endif; ?>

                <?php if($vePriceLabel): ?>
                    <span class="ve-meta-pill ve-meta-pill--accent"><i class="bx bx-purchase-tag"></i> <?php echo e($vePriceLabel); ?></span>
                <?php endif; ?>

                <span class="ve-meta-pill"><i class="bx bx-calendar"></i> <?php echo e($veDatesCount); ?> départ(s)</span>

                <?php if($lastUpdated): ?>
                    <span class="ve-meta-pill ve-meta-pill--muted"><i class="bx bx-time"></i> Maj <?php echo e($lastUpdated); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="ve-header-main__actions">
            <span class="ve-status-badge status-<?php echo e($currentStatus); ?> align-self-center">
                <span class="status-dot"></span>
                <?php echo e($statusLabel); ?>

            </span>

            <?php if($publicShowUrl): ?>
                <a href="<?php echo e($publicShowUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                    <i class="bx bx-link-external"></i> Voir la page client
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="ve-header-quickbar" aria-label="Vue rapide voyage">
        <div class="ve-quick-item">
            <span>Statut</span>
            <strong><?php echo e($statusLabel); ?></strong>
        </div>
        <div class="ve-quick-item">
            <span>Départs</span>
            <strong><?php echo e($veDatesCount); ?></strong>
        </div>
        <div class="ve-quick-item">
            <span>Destination</span>
            <strong><?php echo e($veDestination ? Str::limit($veDestination, 28) : 'À définir'); ?></strong>
        </div>
        <div class="ve-quick-item">
            <span>Prix</span>
            <strong><?php echo e($vePriceLabel ?: 'À définir'); ?></strong>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\partials\_voyage_page_header.blade.php ENDPATH**/ ?>