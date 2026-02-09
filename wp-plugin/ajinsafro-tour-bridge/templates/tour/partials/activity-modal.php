<?php
/**
 * Activity Modal - Modal pour sélectionner et ajouter une activité à un jour
 *
 * @var int $tour_id Tour ID
 * @var int $day_id Day ID (optionnel, pour exclure les activités déjà ajoutées)
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="ajtb-activity-modal" class="ajtb-modal" role="dialog" aria-labelledby="ajtb-activity-modal-title" aria-hidden="true">
    <div class="ajtb-modal-overlay" data-ajtb-modal-close></div>
    <div class="ajtb-modal-content">
        <div class="ajtb-modal-header">
            <h2 id="ajtb-activity-modal-title" class="ajtb-modal-title"><?php esc_html_e('Choisir une activité', 'ajinsafro-tour-bridge'); ?></h2>
            <button type="button" class="ajtb-modal-close" data-ajtb-modal-close aria-label="<?php esc_attr_e('Fermer', 'ajinsafro-tour-bridge'); ?>">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="ajtb-modal-body">
            <!-- Search bar -->
            <div class="ajtb-activity-modal-search">
                <input type="text" id="ajtb-activity-search" class="ajtb-activity-search-input" placeholder="<?php esc_attr_e('Rechercher une activité...', 'ajinsafro-tour-bridge'); ?>" autocomplete="off">
                <svg class="ajtb-search-icon" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </div>

            <!-- Loading skeleton -->
            <div id="ajtb-activity-loading" class="ajtb-activity-loading" style="display: none;">
                <div class="ajtb-activity-skeleton-grid">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="ajtb-activity-skeleton-card">
                            <div class="ajtb-skeleton-image"></div>
                            <div class="ajtb-skeleton-title"></div>
                            <div class="ajtb-skeleton-price"></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Activities grid -->
            <div id="ajtb-activity-grid" class="ajtb-activity-grid"></div>

            <!-- Empty state -->
            <div id="ajtb-activity-empty" class="ajtb-activity-empty" style="display: none;">
                <p><?php esc_html_e('Aucune activité trouvée.', 'ajinsafro-tour-bridge'); ?></p>
            </div>

            <!-- Pagination -->
            <div id="ajtb-activity-pagination" class="ajtb-activity-pagination" style="display: none;"></div>
        </div>
    </div>
</div>
