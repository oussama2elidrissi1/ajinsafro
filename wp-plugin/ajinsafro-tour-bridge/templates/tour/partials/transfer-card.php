<?php
/**
 * Transfer Card partial – One transfer block (TRANSFER • From → To).
 * Same visual hierarchy as FlightCard.
 *
 * @var array $transfer Transfer row (from_label, to_label, pickup_time, dropoff_time, vehicle_type, notes)
 * @var string $label Optional; e.g. "Transfert Aéroport → Hôtel"
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($transfer) || !is_array($transfer)) {
    return;
}

$dash = '—';
$from = isset($transfer['from_label']) ? trim((string) $transfer['from_label']) : '';
$to = isset($transfer['to_label']) ? trim((string) $transfer['to_label']) : '';
if ($from === '' && $to === '') {
    return;
}
$from = $from !== '' ? $from : $dash;
$to = $to !== '' ? $to : $dash;
$pickup = isset($transfer['pickup_time']) && trim((string) $transfer['pickup_time']) !== '' ? (string) $transfer['pickup_time'] : $dash;
$dropoff = isset($transfer['dropoff_time']) && trim((string) $transfer['dropoff_time']) !== '' ? (string) $transfer['dropoff_time'] : $dash;
$vehicle = isset($transfer['vehicle_type']) && trim((string) $transfer['vehicle_type']) !== '' ? (string) $transfer['vehicle_type'] : $dash;
$notes = isset($transfer['notes']) ? trim((string) $transfer['notes']) : '';
$card_label = isset($label) && (string) $label !== '' ? (string) $label : __('TRANSFERT', 'ajinsafro-tour-bridge');
?>
<div class="aj-transfer-card aj-flight-card" data-transfer-id="<?php echo esc_attr((int) ($transfer['id'] ?? 0)); ?>">
    <div class="aj-flight-card__header">
        <span class="aj-flight-card__title"><?php echo esc_html($card_label); ?> • <?php echo esc_html($from); ?> → <?php echo esc_html($to); ?></span>
    </div>
    <div class="aj-flight-card__body">
        <div class="aj-flight-card__col aj-flight-card__icon">
            <span class="aj-flight-card__icon-inner" aria-hidden="true">🚐</span>
        </div>
        <div class="aj-flight-card__col aj-flight-card__route">
            <div class="aj-flight-card__dep">
                <span class="aj-flight-card__date"><?php esc_html_e('Prise en charge:', 'ajinsafro-tour-bridge'); ?> <?php echo esc_html($pickup); ?></span>
                <span class="aj-flight-card__place"><?php echo esc_html($from); ?></span>
            </div>
            <span class="aj-flight-card__arrow" aria-hidden="true">→</span>
            <div class="aj-flight-card__arr">
                <span class="aj-flight-card__date"><?php esc_html_e('Arrivée:', 'ajinsafro-tour-bridge'); ?> <?php echo esc_html($dropoff); ?></span>
                <span class="aj-flight-card__place"><?php echo esc_html($to); ?></span>
            </div>
        </div>
        <div class="aj-flight-card__col aj-flight-card__baggage">
            <div><?php esc_html_e('Véhicule:', 'ajinsafro-tour-bridge'); ?> <?php echo esc_html($vehicle); ?></div>
        </div>
    </div>
    <?php if ($notes !== ''): ?>
        <div class="aj-transfer-card__notes aj-flight-card__badge-wrap">
            <span class="aj-transfer-card__notes-text"><?php echo wp_kses_post(nl2br($notes)); ?></span>
        </div>
    <?php endif; ?>
</div>
