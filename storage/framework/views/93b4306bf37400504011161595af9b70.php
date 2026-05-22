
<?php if(config('app.debug') && isset($catalogMeta)): ?>
<div id="ws-debug-fab-wrap" class="ws-debug-fab-wrap">
    <button type="button" id="ws-debug-modal-open" class="ws-debug-fab-btn" title="Ouvrir le panneau debug catalogue (APP_DEBUG)">
        <i class="fas fa-bug" aria-hidden="true"></i>
        <span>Debug test</span>
    </button>
</div>

<div id="ws-debug-modal" class="ws-debug-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="ws-debug-modal-title" aria-hidden="true">
    <div class="ws-debug-modal__backdrop" data-ws-debug-close tabindex="-1" aria-hidden="true"></div>
    <div class="ws-debug-modal__shell">
        <div class="ws-debug-modal__panel">
            <header class="ws-debug-modal__header">
                <h2 id="ws-debug-modal-title" class="ws-debug-modal__title">DEBUG CATALOGUE</h2>
                <button type="button" class="ws-debug-modal__close" data-ws-debug-close aria-label="Fermer">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </header>
            <div class="ws-debug-modal__body">
                <?php if(!empty($catalogMeta['wp_connection_failed'])): ?>
                    <p class="ws-debug-modal__alert">Connexion WordPress indisponible �?" aucune ligne catalogue.</p>
                <?php else: ?>
                    <p class="ws-debug-modal__intro">
                        <strong>wp_tours</strong> = nombre de tours WordPress (comme Circuits / voyages). <strong>total_rows</strong> = packages + vols + hébergements dans le tableau.
                    </p>
                    <p class="ws-debug-modal__mono">
                        wp_tours=<?php echo e((int) ($catalogMeta['wp_tour_count'] ?? 0)); ?>,
                        laravel_matched=<?php echo e((int) ($catalogMeta['laravel_voyage_matched'] ?? 0)); ?>,
                        packages=<?php echo e((int) ($catalogMeta['package_rows'] ?? 0)); ?>,
                        vols=<?php echo e((int) ($catalogMeta['vol_rows'] ?? 0)); ?>,
                        hébergements=<?php echo e((int) ($catalogMeta['hebergement_rows'] ?? 0)); ?>,
                        total_rows=<?php echo e((int) ($catalogMeta['total_rows'] ?? ($catalogTotalCount ?? $catalogRows->count()))); ?>

                    </p>
                    <?php
                        $p = (int) ($catalogMeta['package_rows'] ?? 0);
                        $v = (int) ($catalogMeta['vol_rows'] ?? 0);
                        $h = (int) ($catalogMeta['hebergement_rows'] ?? 0);
                        $chk = $p + $v + $h;
                        $tot = (int) ($catalogMeta['total_rows'] ?? $catalogRows->count());
                    ?>
                    <?php if($chk === $tot && $p > 0): ?>
                        <p class="ws-debug-modal__ok">Vérification : <?php echo e($p); ?> + <?php echo e($v); ?> + <?php echo e($h); ?> = <?php echo e($tot); ?> (cohérent).</p>
                    <?php endif; ?>
                    <?php if(!empty($catalogMeta['wp_tour_ids'])): ?>
                        <p class="ws-debug-modal__mono ws-debug-modal__break"><span class="ws-debug-modal__label">IDs WP (ordre Circuits / voyages, desc ID)</span> : <?php echo e(implode(', ', $catalogMeta['wp_tour_ids'])); ?></p>
                    <?php endif; ?>
                    <?php if(!empty($catalogMeta['laravel_wp_post_ids_matched'])): ?>
                        <p class="ws-debug-modal__mono ws-debug-modal__break"><span class="ws-debug-modal__label">wp_post_id Laravel liés</span> : <?php echo e(implode(', ', $catalogMeta['laravel_wp_post_ids_matched'])); ?></p>
                    <?php endif; ?>
                    <?php if(!empty($catalogMeta['wp_tour_ids_without_laravel'])): ?>
                        <p class="ws-debug-modal__mono ws-debug-modal__break"><span class="ws-debug-modal__label">Tours WP sans fiche Laravel</span> : <?php echo e(implode(', ', $catalogMeta['wp_tour_ids_without_laravel'])); ?></p>
                    <?php endif; ?>
                    <?php if(!empty($catalogMeta['laravel_duplicates_wp_post_id'])): ?>
                        <p class="ws-debug-modal__warn-title">Doublons voyages.wp_post_id (plusieurs lignes Laravel) :</p>
                        <pre class="ws-debug-modal__pre"><?php echo e(json_encode($catalogMeta['laravel_duplicates_wp_post_id'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    <?php endif; ?>
                    <?php if(!empty($catalogMeta['package_price_debug'])): ?>
                        <p class="ws-debug-modal__section-title">Prix packages (meta WP <code>adult_price</code> = colonne Prix Adulte Circuits / voyages)</p>
                        <div class="ws-debug-table-wrap">
                            <table class="ws-debug-table">
                                <thead>
                                    <tr>
                                        <th>WP ID</th>
                                        <th>meta brut</th>
                                        <th>parsé</th>
                                        <th>Laravel price_from</th>
                                        <th>Affiché</th>
                                        <th>Source</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $catalogMeta['package_price_debug']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($d['wp_post_id'] ?? ''); ?></td>
                                            <td class="ws-debug-table__cell-tight"><?php echo e(is_scalar($d['adult_price_meta_raw'] ?? null) ? $d['adult_price_meta_raw'] : json_encode($d['adult_price_meta_raw'])); ?></td>
                                            <td><?php echo e($d['parsed_wp_adult'] ?? '�?"'); ?></td>
                                            <td><?php echo e($d['laravel_price_from'] ?? '�?"'); ?></td>
                                            <td><strong><?php echo e($d['price_label_final'] ?? '�?"'); ?></strong></td>
                                            <td><?php echo e($d['price_source'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <?php if(!empty($catalogMeta['package_departure_debug'])): ?>
                        <?php if(!empty($catalogMeta['package_departure_source_doc'])): ?>
                            <p class="ws-debug-modal__doc"><?php echo e($catalogMeta['package_departure_source_doc']); ?></p>
                        <?php endif; ?>
                        <p class="ws-debug-modal__section-title">Départs packages (Disponibilité = <code>aj_travel_dates</code> / <code>TravelDate</code>)</p>
                        <div class="ws-debug-table-wrap">
                            <table class="ws-debug-table">
                                <thead>
                                    <tr>
                                        <th>WP ID</th>
                                        <th>Laravel #</th>
                                        <th>Dates actives</th>
                                        <th>ID retenu</th>
                                        <th>Date affichée</th>
                                        <th>Passé</th>
                                        <th>Flags</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $catalogMeta['package_departure_debug']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dd): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($dd['wp_post_id'] ?? ''); ?></td>
                                            <td><?php echo e($dd['laravel_voyage_id'] ?? '�?"'); ?></td>
                                            <td class="ws-debug-table__cell-tight"><?php echo e(!empty($dd['active_travel_dates_ymd']) ? implode(', ', $dd['active_travel_dates_ymd']) : '�?"'); ?></td>
                                            <td><?php echo e($dd['picked_travel_date_id'] ?? '�?"'); ?></td>
                                            <td><strong><?php echo e($dd['picked_date_ymd'] ?? '�?"'); ?></strong></td>
                                            <td><?php echo e(!empty($dd['workspace_display_is_past']) ? 'oui' : 'non'); ?></td>
                                            <td>
                                                <?php if(!empty($dd['no_laravel_voyage'])): ?><span class="ws-debug-tag ws-debug-tag--warn">sans Laravel</span><?php endif; ?>
                                                <?php if(!empty($dd['no_availability_rows'])): ?><span class="ws-debug-tag ws-debug-tag--err">aucune dispo</span><?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <?php if(!empty($catalogMeta['package_places_debug'])): ?>
                        <?php if(!empty($catalogMeta['package_places_source_doc'])): ?>
                            <p class="ws-debug-modal__doc"><?php echo e($catalogMeta['package_places_source_doc']); ?></p>
                        <?php endif; ?>
                        <p class="ws-debug-modal__section-title">Places / chambres (échantillon max 8 packages Laravel �?" même calcul que l�?Tédition voyage)</p>
                        <pre class="ws-debug-modal__pre ws-debug-modal__pre--json"><?php echo e(json_encode($catalogMeta['package_places_debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <footer class="ws-debug-modal__footer">
                <button type="button" class="ws-debug-modal__footer-btn" data-ws-debug-close>Fermer</button>
            </footer>
        </div>
    </div>
</div>

<script>
(function () {
    function setupWsDebugModal() {
        var root = document.getElementById('ws-debug-modal');
        var openBtn = document.getElementById('ws-debug-modal-open');
        if (!root || !openBtn) return;
        function lockScroll() {
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
        }
        function unlockScroll() {
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        }
        function closeModal() {
            root.style.display = 'none';
            root.setAttribute('aria-hidden', 'true');
            unlockScroll();
        }
        function openModal() {
            root.style.display = 'flex';
            root.setAttribute('aria-hidden', 'false');
            lockScroll();
        }
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openModal();
        });
        root.querySelectorAll('[data-ws-debug-close]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                closeModal();
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape' || !root) return;
            if (root.style.display === 'none' || root.getAttribute('aria-hidden') === 'true') return;
            closeModal();
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupWsDebugModal);
    } else {
        setupWsDebugModal();
    }
})();
</script>
<?php endif; ?>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\workspace\partials\debug-catalog-panel.blade.php ENDPATH**/ ?>