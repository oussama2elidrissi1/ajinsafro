<?php
/**
 * Hotel Card partial – Premium summary.
 *
 * @var array $hotel Hotel row (hotel_name, stars, address, room_type, meal_plan, notes, image_url)
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
    $name = isset($hotel['name']) ? trim((string) $hotel['name']) : '';
}
if ($name === '') {
    $name = __('Hotel', 'ajinsafro-tour-bridge');
}

$stars = isset($hotel['stars']) ? (int) $hotel['stars'] : 0;
$address = isset($hotel['address']) ? trim((string) $hotel['address']) : '';
$city = '';
foreach (['city', 'hotel_city', 'location'] as $key) {
    if (!empty($hotel[$key])) {
        $city = trim((string) $hotel[$key]);
        break;
    }
}

$room_type = isset($hotel['room_type']) ? trim((string) $hotel['room_type']) : '';
$rooms = isset($hotel['rooms']) ? (int) $hotel['rooms'] : (isset($hotel['room_count']) ? (int) $hotel['room_count'] : 0);
$meal_plan = isset($hotel['meal_plan']) ? trim((string) $hotel['meal_plan']) : '';
$breakfast_included = !empty($hotel['breakfast_included']);
$notes = isset($hotel['notes']) ? trim((string) $hotel['notes']) : '';

$checkin = '';
$checkout = '';
foreach (['checkin_date_formatted', 'checkin_date', 'arrival_date'] as $key) {
    if (!empty($hotel[$key])) {
        $checkin = trim((string) $hotel[$key]);
        break;
    }
}
foreach (['checkout_date_formatted', 'checkout_date', 'departure_date'] as $key) {
    if (!empty($hotel[$key])) {
        $checkout = trim((string) $hotel[$key]);
        break;
    }
}

$nights = 0;
foreach (['nights', 'nights_count', 'duration_nights'] as $key) {
    if (!empty($hotel[$key])) {
        $nights = (int) $hotel[$key];
        break;
    }
}

$adults = 0;
foreach (['adults', 'pax_adults', 'guests_adults'] as $key) {
    if (!empty($hotel[$key])) {
        $adults = (int) $hotel[$key];
        break;
    }
}

$header_title = __('Hotel', 'ajinsafro-tour-bridge');
$header_parts = [];
if ($nights > 0) {
    $header_parts[] = $nights . ' ' . _n('nuit', 'nuits', $nights, 'ajinsafro-tour-bridge');
}
if ($city !== '') {
    $header_parts[] = __('a', 'ajinsafro-tour-bridge') . ' ' . $city;
}
if (!empty($header_parts)) {
    $header_title .= ' • ' . implode(' ', $header_parts);
}

$status_label = '';
foreach (['status_label', 'status', 'state'] as $key) {
    if (!empty($hotel[$key])) {
        $status_label = trim((string) $hotel[$key]);
        break;
    }
}
if ($status_label === '') {
    $status_label = __('Reservation confirmee', 'ajinsafro-tour-bridge');
}

$info_items = [];
if ($rooms > 0) {
    $info_items[] = __('Chambre:', 'ajinsafro-tour-bridge') . ' ' . $rooms;
} elseif ($room_type !== '') {
    $info_items[] = __('Chambre:', 'ajinsafro-tour-bridge') . ' ' . $room_type;
}

if ($breakfast_included || stripos($meal_plan, 'breakfast') !== false || stripos($meal_plan, 'petit') !== false || stripos($meal_plan, 'bb') !== false) {
    $info_items[] = __('Petit-dejeuner inclus', 'ajinsafro-tour-bridge');
}

if ($meal_plan !== '' && stripos($meal_plan, 'breakfast') === false && stripos($meal_plan, 'petit') === false && stripos($meal_plan, 'bb') === false) {
    $info_items[] = __('Repas:', 'ajinsafro-tour-bridge') . ' ' . $meal_plan;
}

$date_line = '';
if ($checkin !== '' || $checkout !== '') {
    $date_line = $checkin !== '' ? $checkin : '—';
    if ($checkout !== '') {
        $date_line .= ' -> ' . $checkout;
    }
}
if ($adults > 0) {
    $date_line .= ($date_line !== '' ? ' • ' : '') . $adults . ' ' . _n('adulte', 'adultes', $adults, 'ajinsafro-tour-bridge');
}
?>
<div class="ajtb-hotel-card" data-hotel-id="<?php echo esc_attr((int) ($hotel['id'] ?? 0)); ?>">
    <div class="ajtb-card-head">
        <div class="ajtb-card-head-left">
            <span class="ajtb-card-head-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                    <rect x="3" y="7" width="18" height="13" rx="2"></rect>
                    <path d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"></path>
                    <path d="M7 11h3"></path>
                    <path d="M14 11h3"></path>
                </svg>
            </span>
            <div class="ajtb-card-head-text">
                <div class="ajtb-card-title"><?php echo esc_html($header_title); ?></div>
                <?php if ($address !== ''): ?>
                    <div class="ajtb-card-subtitle"><?php echo esc_html($address); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ajtb-card-divider"></div>

    <div class="ajtb-hotel-info">
        <div class="ajtb-hotel-name-row">
            <span class="ajtb-hotel-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
                    <rect x="4" y="7" width="16" height="12" rx="2"></rect>
                    <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </span>
            <div class="ajtb-hotel-name">
                <span class="ajtb-hotel-name-text"><?php echo esc_html($name); ?></span>
                <?php if ($stars > 0): ?>
                    <span class="ajtb-hotel-stars" aria-hidden="true">
                        <?php echo str_repeat('&#9733;', min(5, $stars)); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($date_line !== ''): ?>
            <div class="ajtb-hotel-dates"><?php echo esc_html($date_line); ?></div>
        <?php endif; ?>

        <?php if (!empty($info_items)): ?>
            <div class="ajtb-card-info">
                <?php foreach ($info_items as $info): ?>
                    <div class="ajtb-card-info-item"><?php echo esc_html($info); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($notes !== ''): ?>
        <div class="ajtb-card-notes"><?php echo wp_kses_post(nl2br($notes)); ?></div>
    <?php endif; ?>

    <div class="ajtb-card-footer">
        <span class="ajtb-card-status">
            <?php echo esc_html($status_label); ?>
            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </span>
    </div>
</div>
