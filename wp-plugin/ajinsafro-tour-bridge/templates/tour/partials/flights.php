<?php
/**
 * Tour Flights partial – Flight Cards (same UI as admin)
 * Uses session selections: default shows is_default=1; client can Add/Remove via AJAX.
 *
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

$tour_id = isset($tour['id']) ? (int) $tour['id'] : 0;
$flights = isset($tour['flights']) ? $tour['flights'] : [];
$all_flights = isset($tour['all_flights']) ? $tour['all_flights'] : [];
$session_token = isset($tour['_session_token']) ? $tour['_session_token'] : '';

if (empty($flights) && empty($all_flights)) {
    return;
}
?>
<section class="ajtb-section ajtb-section-flights" id="flights">
    <h2 class="ajtb-section-title">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a2.5 2.5 0 0 1 0 5H3"></path>
        </svg>
        <?php esc_html_e('Vols', 'ajinsafro-tour-bridge'); ?>
    </h2>
    <div id="ajtb-flights-container" class="ajtb-flights-container">
        <?php echo ajtb_render_flights_html($tour_id, $flights, $all_flights, $session_token); ?>
    </div>
</section>
