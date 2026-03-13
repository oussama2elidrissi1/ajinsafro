<?php
/**
 * Hotel Card partial – Listing layout (rating, name, location, room, amenities).
 *
 * @var array $hotel Hotel row (hotel_name, stars, address, room_type, meal_plan, notes, image_url, etc.)
 * @var bool  $is_checkout Optional; true = last day (check-out).
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
if ($room_type === '') {
    $room_type = __('Standard room with 2 Single beds', 'ajinsafro-tour-bridge');
}
$rooms = isset($hotel['rooms']) ? (int) $hotel['rooms'] : (isset($hotel['room_count']) ? (int) $hotel['room_count'] : 1);
$meal_plan = isset($hotel['meal_plan']) ? trim((string) $hotel['meal_plan']) : '';
$breakfast_included = !empty($hotel['breakfast_included']) || stripos($meal_plan, 'breakfast') !== false || stripos($meal_plan, 'petit') !== false || stripos($meal_plan, 'bb') !== false;

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
if ($adults === 0) {
    $adults = 2;
}

// Static / fallback for listing display
$rating = isset($hotel['rating']) ? (float) $hotel['rating'] : 4.9;
$rating_count = isset($hotel['rating_count']) ? (int) $hotel['rating_count'] : 7;
$rating_label = __('Excellent', 'ajinsafro-tour-bridge');
$proximity = isset($hotel['proximity']) ? trim((string) $hotel['proximity']) : __('7 minutes walk to Nizami Street', 'ajinsafro-tour-bridge');
$location_line = $city !== '' ? $city . ' | ' . $proximity : $proximity;
$room_size = isset($hotel['room_size']) ? trim((string) $hotel['room_size']) : '215 sq.ft';
$bed_detail = isset($hotel['bed_type']) ? trim((string) $hotel['bed_type']) : __('Single Bed', 'ajinsafro-tour-bridge');
$date_line = '';
if ($checkin !== '' || $checkout !== '') {
    $date_line = $checkin !== '' ? $checkin : '—';
    if ($checkout !== '') {
        $date_line .= ' - ' . $checkout;
    }
}
if ($nights > 0) {
    $date_line .= ($date_line !== '' ? ', ' : '') . $nights . ' ' . _n(__('Night', 'ajinsafro-tour-bridge'), __('Nights', 'ajinsafro-tour-bridge'), $nights);
}

$amenities = [];
if ($breakfast_included) {
    $amenities[] = __('Breakfast buffet', 'ajinsafro-tour-bridge');
}
$amenities[] = __('Free WiFi', 'ajinsafro-tour-bridge');
if (isset($hotel['amenities']) && is_array($hotel['amenities'])) {
    $amenities = array_merge($amenities, $hotel['amenities']);
}
$amenities = array_slice(array_unique($amenities), 0, 5);
?>
<div class="ajtb-hotel-card ajtb-hotel-listing-card" data-hotel-id="<?php echo esc_attr((int) ($hotel['id'] ?? 0)); ?>">
    <div class="ajtb-hotel-listing-card__rating">
        <span class="ajtb-hotel-rating-badge"><?php echo esc_html(number_format($rating, 1)); ?></span>
        <span class="ajtb-hotel-rating-text"><strong><?php echo esc_html($rating_label); ?></strong> (<?php echo (int) $rating_count; ?> <?php echo esc_html(_n(__('Rating', 'ajinsafro-tour-bridge'), __('Ratings', 'ajinsafro-tour-bridge'), $rating_count)); ?>)</span>
    </div>

    <h3 class="ajtb-hotel-listing-card__name">
        <?php echo esc_html($name); ?>
        <?php if ($stars > 0): ?>
            <span class="ajtb-hotel-stars" aria-hidden="true">
                <?php echo str_repeat('&#9733;', min(5, $stars)); ?><?php echo str_repeat('&#9734;', max(0, 5 - $stars)); ?>
            </span>
        <?php endif; ?>
    </h3>

    <p class="ajtb-hotel-listing-card__location"><?php echo esc_html($location_line); ?></p>

    <div class="ajtb-hotel-listing-card__meta">
        <span class="ajtb-hotel-meta-item">
            <svg class="ajtb-hotel-meta-icon" viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <?php echo (int) $rooms; ?> <?php echo esc_html(_n(__('Room', 'ajinsafro-tour-bridge'), __('Rooms', 'ajinsafro-tour-bridge'), $rooms)); ?> | <?php echo (int) $adults; ?> <?php echo esc_html(_n(__('Adult', 'ajinsafro-tour-bridge'), __('Adults', 'ajinsafro-tour-bridge'), $adults)); ?>
        </span>
        <?php if ($date_line !== ''): ?>
        <span class="ajtb-hotel-meta-item">
            <svg class="ajtb-hotel-meta-icon" viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <?php echo esc_html($date_line); ?>
        </span>
        <?php endif; ?>
    </div>

    <div class="ajtb-hotel-listing-card__room">
        <strong><?php echo esc_html($room_type); ?></strong>
        <a href="#" class="ajtb-hotel-more-rooms"><?php esc_html_e('More Room Options', 'ajinsafro-tour-bridge'); ?></a>
    </div>
    <p class="ajtb-hotel-listing-card__room-detail">(<?php echo esc_html($room_size); ?> | <?php echo esc_html($bed_detail); ?>)</p>

    <?php if ($breakfast_included): ?>
    <p class="ajtb-hotel-listing-card__meal">
        <svg class="ajtb-hotel-meal-icon" viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
        <?php esc_html_e('Breakfast is included', 'ajinsafro-tour-bridge'); ?>
    </p>
    <?php endif; ?>

    <ul class="ajtb-hotel-listing-card__amenities">
        <?php foreach ($amenities as $amenity): ?>
        <li><svg class="ajtb-hotel-check" viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><?php echo esc_html($amenity); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
