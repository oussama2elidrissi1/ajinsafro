<?php
/**
 * Flight Card partial – MakeMyTrip-style UI.
 * Compatible Laravel (voyage_flights toDisplayArray) et ancien format WP (depart_label, cabin_baggage, etc.).
 *
 * @var array $flight Flight row
 * @var bool  $show_remove Optional; show REMOVE button (e.g. when tentative or client choice)
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($flight) || !is_array($flight)) {
    return;
}
if (function_exists('ajtb_flight_has_content') && !ajtb_flight_has_content($flight)) {
    return;
}

$is_tentative = !empty($flight['is_tentative']);
$show_remove = isset($show_remove) ? (bool) $show_remove : $is_tentative;

echo aj_render_flight_card($flight, [
    'show_remove' => $show_remove,
]);

