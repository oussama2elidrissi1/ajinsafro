<?php
/**
 * Flight card placeholder — same layout as flight card, message "Aller/Retour non disponible".
 *
 * @var string $label 'Vol Aller non disponible' or 'Vol Retour non disponible'
 * @package AjinsafroTourBridge
 */
if (!defined('ABSPATH')) {
    exit;
}

$label = isset($label) && $label !== '' ? $label : __('Vol non disponible', 'ajinsafro-tour-bridge');

echo aj_render_flight_card([], [
    'title' => $label,
    'unavailable' => true,
]);
