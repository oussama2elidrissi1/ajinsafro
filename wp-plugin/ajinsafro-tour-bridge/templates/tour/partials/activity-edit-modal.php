<?php
/**
 * Activity Edit Modal - Mini modal pour modifier custom_price, custom_title, custom_description, times
 *
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="ajtb-activity-edit-modal" class="ajtb-modal ajtb-modal-small" role="dialog" aria-labelledby="ajtb-activity-edit-modal-title" aria-hidden="true">
    <div class="ajtb-modal-overlay" data-ajtb-modal-close></div>
    <div class="ajtb-modal-content">
        <div class="ajtb-modal-header">
            <h2 id="ajtb-activity-edit-modal-title" class="ajtb-modal-title"><?php esc_html_e('Modifier l\'activité', 'ajinsafro-tour-bridge'); ?></h2>
            <button type="button" class="ajtb-modal-close" data-ajtb-modal-close aria-label="<?php esc_attr_e('Fermer', 'ajinsafro-tour-bridge'); ?>">
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="ajtb-modal-body">
            <form id="ajtb-activity-edit-form">
                <input type="hidden" id="ajtb-edit-day-activity-id" name="day_activity_id">
                <input type="hidden" id="ajtb-edit-tour-id" name="tour_id">
                <input type="hidden" id="ajtb-edit-day-id" name="day_id">
                <input type="hidden" id="ajtb-edit-activity-id" name="activity_id">

                <div class="ajtb-form-group">
                    <label for="ajtb-edit-custom-title"><?php esc_html_e('Titre personnalisé', 'ajinsafro-tour-bridge'); ?></label>
                    <input type="text" id="ajtb-edit-custom-title" name="custom_title" class="ajtb-form-input" placeholder="<?php esc_attr_e('Laisser vide pour utiliser le titre par défaut', 'ajinsafro-tour-bridge'); ?>">
                </div>

                <div class="ajtb-form-group">
                    <label for="ajtb-edit-custom-description"><?php esc_html_e('Description personnalisée', 'ajinsafro-tour-bridge'); ?></label>
                    <textarea id="ajtb-edit-custom-description" name="custom_description" class="ajtb-form-textarea" rows="4" placeholder="<?php esc_attr_e('Laisser vide pour utiliser la description par défaut', 'ajinsafro-tour-bridge'); ?>"></textarea>
                </div>

                <div class="ajtb-form-group">
                    <label for="ajtb-edit-custom-price"><?php esc_html_e('Prix personnalisé (DH)', 'ajinsafro-tour-bridge'); ?></label>
                    <input type="number" id="ajtb-edit-custom-price" name="custom_price" class="ajtb-form-input" step="0.01" min="0" placeholder="<?php esc_attr_e('Laisser vide pour utiliser le prix par défaut', 'ajinsafro-tour-bridge'); ?>">
                    <small class="ajtb-form-help"><?php esc_html_e('Laissez vide pour utiliser le prix de base de l\'activité', 'ajinsafro-tour-bridge'); ?></small>
                </div>

                <div class="ajtb-form-row">
                    <div class="ajtb-form-group">
                        <label for="ajtb-edit-start-time"><?php esc_html_e('Heure de début', 'ajinsafro-tour-bridge'); ?></label>
                        <input type="time" id="ajtb-edit-start-time" name="start_time" class="ajtb-form-input">
                    </div>
                    <div class="ajtb-form-group">
                        <label for="ajtb-edit-end-time"><?php esc_html_e('Heure de fin', 'ajinsafro-tour-bridge'); ?></label>
                        <input type="time" id="ajtb-edit-end-time" name="end_time" class="ajtb-form-input">
                    </div>
                </div>

                <div class="ajtb-modal-actions">
                    <button type="button" class="ajtb-btn-secondary" data-ajtb-modal-close><?php esc_html_e('Annuler', 'ajinsafro-tour-bridge'); ?></button>
                    <button type="submit" class="ajtb-btn-primary"><?php esc_html_e('Enregistrer', 'ajinsafro-tour-bridge'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
