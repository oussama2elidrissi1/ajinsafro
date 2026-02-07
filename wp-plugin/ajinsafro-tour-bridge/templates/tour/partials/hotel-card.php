<?php
/**
 * Hotel Card partial – Main hotel (check-in / check-out). Same visual hierarchy as FlightCard.
 *
 * @var array $hotel Hotel row (hotel_name, stars, address, room_type, meal_plan, notes)
 * @var bool  $is_checkout Optional; true = last day (check-out), false = day 1 (check-in)
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($hotel) || !is_array($hotel)) {
    return;
}

$name = isset($hotel['hotel_name']) ? trim((string) $hotel['hotel_name']) : '';
if ($name === '') {
    $name = __('Hôtel', 'ajinsafro-tour-bridge');
}
$stars = isset($hotel['stars']) ? (int) $hotel['stars'] : 0;
$address = isset($hotel['address']) ? trim((string) $hotel['address']) : '';
$room_type = isset($hotel['room_type']) ? trim((string) $hotel['room_type']) : '';
$meal_plan = isset($hotel['meal_plan']) ? trim((string) $hotel['meal_plan']) : '';
$notes = isset($hotel['notes']) ? trim((string) $hotel['notes']) : '';
$is_checkout = isset($is_checkout) && $is_checkout;
$badge = $is_checkout ? __('Check-out', 'ajinsafro-tour-bridge') : __('Check-in', 'ajinsafro-tour-bridge');
?>
<div class="aj-hotel-card aj-flight-card" data-hotel-id="<?php echo esc_attr((int) ($hotel['id'] ?? 0)); ?>">
    <div class="aj-flight-card__header">
        <span class="aj-flight-card__title"><?php echo esc_html($badge); ?> • <?php echo esc_html($name); ?></span>
    </div>
    <div class="aj-flight-card__body">
        <div class="aj-flight-card__col aj-flight-card__icon">
            <span class="aj-flight-card__icon-inner" aria-hidden="true">🏨</span>
        </div>
        <div class="aj-flight-card__col aj-flight-card__route" style="flex: 1;">
            <div class="aj-hotel-card__name">
                <?php echo esc_html($name); ?>
                <?php if ($stars > 0): ?>
                    <span class="aj-hotel-card__stars" aria-hidden="true"><?php echo str_repeat('★', min(5, $stars)); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($address !== ''): ?>
                <div class="aj-hotel-card__address"><?php echo esc_html($address); ?></div>
            <?php endif; ?>
            <?php if ($room_type !== ''): ?>
                <div class="aj-hotel-card__meta"><?php esc_html_e('Chambre:', 'ajinsafro-tour-bridge'); ?> <?php echo esc_html($room_type); ?></div>
            <?php endif; ?>
            <?php if ($meal_plan !== ''): ?>
                <div class="aj-hotel-card__meta"><?php esc_html_e('Repas:', 'ajinsafro-tour-bridge'); ?> <?php echo esc_html($meal_plan); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($notes !== ''): ?>
        <div class="aj-hotel-card__notes aj-flight-card__badge-wrap">
            <span class="aj-hotel-card__notes-text"><?php echo wp_kses_post(nl2br($notes)); ?></span>
        </div>
    <?php endif; ?>
</div>
