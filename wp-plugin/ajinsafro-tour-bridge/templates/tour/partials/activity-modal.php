<?php
/**
 * Activity Modal - Choisir une activité (reconstruit)
 * @package AjinsafroTourBridge
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="ajtb-activity-modal" class="ajtb-actmod" role="dialog" aria-labelledby="ajtb-actmod-title" aria-hidden="true">
    <div class="ajtb-actmod-backdrop" data-ajtb-modal-close></div>
    <div class="ajtb-actmod-box">
        <header class="ajtb-actmod-header">
            <h2 id="ajtb-actmod-title" class="ajtb-actmod-title"><?php esc_html_e('Choisir une activité', 'ajinsafro-tour-bridge'); ?></h2>
            <button type="button" class="ajtb-actmod-close" data-ajtb-modal-close aria-label="<?php esc_attr_e('Fermer', 'ajinsafro-tour-bridge'); ?>">&times;</button>
        </header>
        <div class="ajtb-actmod-body">
            <div class="ajtb-actmod-search-wrap">
                <input type="text" id="ajtb-actmod-search" class="ajtb-actmod-search" placeholder="<?php esc_attr_e('Rechercher...', 'ajinsafro-tour-bridge'); ?>" autocomplete="off">
            </div>
            <div id="ajtb-actmod-loading" class="ajtb-actmod-loading" hidden>
                <span class="ajtb-actmod-spinner"></span>
                <span><?php esc_html_e('Chargement...', 'ajinsafro-tour-bridge'); ?></span>
            </div>
            <div id="ajtb-actmod-list" class="ajtb-actmod-list"></div>
            <div id="ajtb-actmod-empty" class="ajtb-actmod-empty" hidden>
                <p><?php esc_html_e('Aucune activité trouvée.', 'ajinsafro-tour-bridge'); ?></p>
            </div>
            <div id="ajtb-actmod-pagination" class="ajtb-actmod-pagination" hidden></div>
        </div>
    </div>
</div>
