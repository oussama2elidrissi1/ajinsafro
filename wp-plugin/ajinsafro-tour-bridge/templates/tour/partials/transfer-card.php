<?php
/**
 * Transfer Card partial – MakeMyTrip-style block.
 *
 * @var array $transfer Transfer row (from_label, to_label, pickup_time, dropoff_time, vehicle_type, notes, image_url)
 * @var string $label Optional; e.g. "Transfert Aeroport -> Hotel"
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($transfer) || !is_array($transfer)) {
    return;
}

$dash = '-';
$from = isset($transfer['from_label']) ? trim((string) $transfer['from_label']) : '';
$to = isset($transfer['to_label']) ? trim((string) $transfer['to_label']) : '';
if ($from === '' && $to === '') {
    return;
}
$from = $from !== '' ? $from : $dash;
$to = $to !== '' ? $to : $dash;

$pickup = isset($transfer['pickup_time']) ? trim((string) $transfer['pickup_time']) : '';
$dropoff = isset($transfer['dropoff_time']) ? trim((string) $transfer['dropoff_time']) : '';
$vehicle = isset($transfer['vehicle_type']) ? trim((string) $transfer['vehicle_type']) : '';
$notes = isset($transfer['notes']) ? trim((string) $transfer['notes']) : '';
$card_label = isset($label) && (string) $label !== '' ? (string) $label : __('Transfert', 'ajinsafro-tour-bridge');

$date_label = '';
foreach (['transfer_date_formatted', 'date_formatted', 'transfer_date', 'date', 'pickup_date'] as $key) {
    if (!empty($transfer[$key])) {
        $date_label = trim((string) $transfer[$key]);
        break;
    }
}
if ($date_label === '' && $pickup !== '') {
    $date_label = $pickup;
}

$section_title = '';
foreach (['transfer_title', 'service_title', 'service_name', 'transfer_type', 'type'] as $key) {
    if (!empty($transfer[$key])) {
        $section_title = trim((string) $transfer[$key]);
        break;
    }
}
if ($section_title === '') {
    $section_title = __('Transfert Partage', 'ajinsafro-tour-bridge');
}

$transfer_name = '';
foreach (['name', 'title', 'transfer_name'] as $key) {
    if (!empty($transfer[$key])) {
        $transfer_name = trim((string) $transfer[$key]);
        break;
    }
}
if ($transfer_name === '') {
    $transfer_name = $section_title;
}

$info_items = [];
if (!empty($transfer['pickup_location'])) {
    $info_items[] = (string) $transfer['pickup_location'];
}
if ($vehicle !== '') {
    $info_items[] = __('Vehicule:', 'ajinsafro-tour-bridge') . ' ' . $vehicle;
}

$chips = [];
$cabin = '';
$checkin = '';
foreach (['cabin_kg', 'cabin_baggage', 'baggage_cabin_kg'] as $key) {
    if (!empty($transfer[$key])) {
        $cabin = trim((string) $transfer[$key]);
        break;
    }
}
foreach (['checkin_kg', 'checkin_baggage', 'baggage_checkin_kg'] as $key) {
    if (!empty($transfer[$key])) {
        $checkin = trim((string) $transfer[$key]);
        break;
    }
}
if ($cabin !== '' && $cabin !== $dash) {
    $chips[] = __('Cabine:', 'ajinsafro-tour-bridge') . ' ' . $cabin;
}
if ($checkin !== '' && $checkin !== $dash) {
    $chips[] = __('Soute:', 'ajinsafro-tour-bridge') . ' ' . $checkin;
}
if ($date_label !== '') {
    $chips[] = $date_label;
}

$status_label = '';
foreach (['status_label', 'status', 'state'] as $key) {
    if (!empty($transfer[$key])) {
        $status_label = trim((string) $transfer[$key]);
        break;
    }
}
if ($status_label === '') {
    $status_label = __('Transfert confirme', 'ajinsafro-tour-bridge');
}

$can_edit = current_user_can('edit_posts');
?>
<div class="ajtb-transfer-card" data-transfer-id="<?php echo esc_attr((int) ($transfer['id'] ?? 0)); ?>">
    <div class="ajtb-card-head">
        <div class="ajtb-card-head-left">
            <span class="ajtb-card-head-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                    <path d="M3 17h18"></path>
                    <path d="M5 17a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2"></path>
                    <path d="M7 10h10l2 5H5l2-5z"></path>
                    <circle cx="7.5" cy="18.5" r="1.5"></circle>
                    <circle cx="16.5" cy="18.5" r="1.5"></circle>
                </svg>
            </span>
            <div class="ajtb-card-head-text">
                <div class="ajtb-card-title"><?php echo esc_html($card_label); ?></div>
                <div class="ajtb-card-subtitle"><?php echo esc_html($from); ?> &rarr; <?php echo esc_html($to); ?></div>
            </div>
        </div>
        <?php if ($date_label !== ''): ?>
            <div class="ajtb-card-date"><?php echo esc_html($date_label); ?></div>
        <?php endif; ?>
    </div>

    <div class="ajtb-card-divider"></div>

    <div class="ajtb-card-section">
        <div class="ajtb-card-section-title"><?php echo esc_html($section_title); ?></div>
        <?php if ($can_edit): ?>
            <button type="button" class="ajtb-card-action" data-aj-action="edit-transfer">
                <?php esc_html_e('Modifier', 'ajinsafro-tour-bridge'); ?>
            </button>
        <?php endif; ?>
    </div>

    <div class="ajtb-card-body">
        <div class="ajtb-card-main">
            <div class="ajtb-card-name"><?php echo esc_html($transfer_name); ?></div>
            <div class="ajtb-card-route"><?php echo esc_html($from); ?> &rarr; <?php echo esc_html($to); ?></div>
            <?php if (!empty($info_items)): ?>
                <div class="ajtb-card-info">
                    <?php foreach ($info_items as $info): ?>
                        <div class="ajtb-card-info-item"><?php echo esc_html($info); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($chips)): ?>
            <div class="ajtb-card-chips">
                <?php foreach ($chips as $chip): ?>
                    <span class="ajtb-chip"><?php echo esc_html($chip); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($notes)): ?>
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
