<?php
/**
 * Flight Card partial – One flight block (FLIGHT • From → To).
 * Compatible Laravel (voyage_flights toDisplayArray) et ancien format WP (depart_label, cabin_baggage, etc.).
 *
 * @var array $flight Flight row (from_city, to_city, depart_date_formatted, arrive_date_formatted, cabin_baggage_display, checkin_baggage_display, is_tentative, etc.)
 * @var bool  $show_remove Optional; show REMOVE button (e.g. when tentative or client choice)
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($flight) || !is_array($flight)) {
    return;
}

$dash = '—';
$from = isset($flight['from_city']) ? (string) $flight['from_city'] : (string) ($flight['depart_label'] ?? '');
$to = isset($flight['to_city']) ? (string) $flight['to_city'] : (string) ($flight['arrive_label'] ?? '');
$from = trim($from) !== '' ? $from : $dash;
$to = trim($to) !== '' ? $to : $dash;
$dep_date = isset($flight['depart_date_formatted']) && trim((string) $flight['depart_date_formatted']) !== '' ? (string) $flight['depart_date_formatted'] : $dash;
$arr_date = isset($flight['arrive_date_formatted']) && trim((string) $flight['arrive_date_formatted']) !== '' ? (string) $flight['arrive_date_formatted'] : $dep_date;
if ($arr_date === $dash && $dep_date !== $dash) {
    $arr_date = $dep_date;
}
$cabin_display = isset($flight['cabin_baggage_display']) ? (string) $flight['cabin_baggage_display'] : (string) ($flight['cabin_baggage'] ?? $dash);
$checkin_display = isset($flight['checkin_baggage_display']) ? (string) $flight['checkin_baggage_display'] : (string) ($flight['checkin_baggage'] ?? $dash);
if (trim($cabin_display) === '') { $cabin_display = $dash; }
if (trim($checkin_display) === '') { $checkin_display = $dash; }
$is_tentative = !empty($flight['is_tentative']);
$show_remove = isset($show_remove) ? (bool) $show_remove : $is_tentative;
?>
<div class="aj-flight-card" data-flight-id="<?php echo esc_attr((int) ($flight['id'] ?? 0)); ?>">
    <div class="aj-flight-card__header">
        <span class="aj-flight-card__title"><?php esc_html_e('FLIGHT', 'ajinsafro-tour-bridge'); ?> • <?php echo esc_html($from); ?> → <?php echo esc_html($to); ?></span>
        <?php if ($show_remove): ?>
            <button type="button" class="aj-flight-card__remove" data-aj-flight-remove aria-label="<?php esc_attr_e('Retirer', 'ajinsafro-tour-bridge'); ?>"><?php esc_html_e('REMOVE', 'ajinsafro-tour-bridge'); ?></button>
        <?php endif; ?>
    </div>
    <div class="aj-flight-card__body">
        <div class="aj-flight-card__col aj-flight-card__icon">
            <span class="aj-flight-card__icon-inner" aria-hidden="true">✈</span>
        </div>
        <div class="aj-flight-card__col aj-flight-card__route">
            <div class="aj-flight-card__dep">
                <span class="aj-flight-card__date"><?php echo esc_html($dep_date); ?></span>
                <span class="aj-flight-card__place"><?php echo esc_html($from); ?></span>
            </div>
            <span class="aj-flight-card__arrow" aria-hidden="true">→</span>
            <div class="aj-flight-card__arr">
                <span class="aj-flight-card__date"><?php echo esc_html($arr_date); ?></span>
                <span class="aj-flight-card__place"><?php echo esc_html($to); ?></span>
            </div>
        </div>
        <div class="aj-flight-card__col aj-flight-card__baggage">
            <div><?php esc_html_e('Cabin:', 'ajinsafro-tour-bridge'); ?> <?php echo esc_html($cabin_display); ?></div>
            <div><?php esc_html_e('Check-in:', 'ajinsafro-tour-bridge'); ?> <?php echo esc_html($checkin_display); ?></div>
        </div>
    </div>
    <?php if ($is_tentative): ?>
        <div class="aj-flight-card__badge-wrap">
            <span class="aj-flight-card__badge aj-flight-card__badge--tentative"><?php esc_html_e('Tentative Flight', 'ajinsafro-tour-bridge'); ?></span>
        </div>
    <?php endif; ?>
</div>
