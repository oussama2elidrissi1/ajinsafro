<?php
/**
 * Flight card placeholder — same design as flight card, message "Aller/Retour non disponible".
 *
 * @var string $label 'Vol Aller non disponible' or 'Vol Retour non disponible'
 * @package AjinsafroTourBridge
 */
if (!defined('ABSPATH')) {
    exit;
}
$label = isset($label) && $label !== '' ? $label : __('Vol non disponible', 'ajinsafro-tour-bridge');
?>
<div class="aj-flight-card aj-flight-card--unavailable">
    <div class="aj-flight-card__header">
        <span class="aj-flight-card__title"><?php echo esc_html($label); ?></span>
    </div>
    <div class="aj-flight-card__body">
        <div class="aj-flight-card__col aj-flight-card__icon">
            <span class="aj-flight-card__icon-inner" aria-hidden="true">✈</span>
        </div>
        <div class="aj-flight-card__col aj-flight-card__route">
            <div class="aj-flight-card__unavailable-msg">—</div>
        </div>
        <div class="aj-flight-card__col aj-flight-card__baggage">
            <div><?php esc_html_e('Cabin:', 'ajinsafro-tour-bridge'); ?> —</div>
            <div><?php esc_html_e('Check-in:', 'ajinsafro-tour-bridge'); ?> —</div>
        </div>
    </div>
</div>
