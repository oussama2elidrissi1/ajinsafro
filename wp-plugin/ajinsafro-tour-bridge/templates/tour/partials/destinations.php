<?php
/**
 * Destinations Partial - Countries and cities linked to this tour.
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

$locations = isset($tour['locations']) && is_array($tour['locations']) ? $tour['locations'] : [];
$locations = apply_filters('ajtb_tour_destinations', $locations, $tour);

if (empty($locations)) {
    return;
}

$normalized = [];
foreach ($locations as $location) {
    $path = isset($location['path']) ? trim((string) $location['path']) : '';
    if ($path === '') {
        $path = isset($location['name']) ? trim((string) $location['name']) : '';
    }
    if ($path === '') {
        continue;
    }

    $country = isset($location['country']) ? trim((string) $location['country']) : '';
    $city = isset($location['city']) ? trim((string) $location['city']) : '';
    if ($country === '' && strpos($path, '>') !== false) {
        $parts = array_map('trim', explode('>', $path));
        $parts = array_values(array_filter($parts, function ($item) {
            return $item !== '';
        }));
        if (!empty($parts)) {
            $country = $parts[0];
            $city = count($parts) > 1 ? $parts[count($parts) - 1] : '';
        }
    }

    $normalized[] = [
        'path' => $path,
        'country' => $country !== '' ? $country : $path,
        'city' => $city,
    ];
}

if (empty($normalized)) {
    return;
}

$visible = array_slice($normalized, 0, 3);
$hidden = array_slice($normalized, 3);
$extra_count = count($hidden);
// translators: %d = number of destinations
$destinations_label = sprintf(_n('%d destination', '%d destinations', count($normalized), 'ajinsafro-tour-bridge'), count($normalized));

do_action('ajtb_before_destinations_section', $tour, $normalized);
?>
<section class="ajtb-section ajtb-destinations-section" id="destinations">
    <h2 class="ajtb-section-title">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
        </svg>
        Destinations
    </h2>
    <p class="ajtb-destinations-intro"><?php echo esc_html($destinations_label); ?></p>

    <div class="ajtb-destination-chips" aria-label="<?php esc_attr_e('Destinations du circuit', 'ajinsafro-tour-bridge'); ?>">
        <?php foreach ($visible as $location): ?>
            <span class="ajtb-destination-chip" title="<?php echo esc_attr($location['path']); ?>">
                <span class="ajtb-destination-country"><?php echo esc_html($location['country']); ?></span>
                <?php if ($location['city'] !== ''): ?>
                    <span class="ajtb-destination-sep" aria-hidden="true">&rsaquo;</span>
                    <span class="ajtb-destination-city"><?php echo esc_html($location['city']); ?></span>
                <?php endif; ?>
            </span>
        <?php endforeach; ?>

        <?php if ($extra_count > 0): ?>
            <details class="ajtb-destination-more">
                <summary class="ajtb-destination-chip ajtb-destination-chip-more">+<?php echo (int) $extra_count; ?></summary>
                <div class="ajtb-destination-more-list">
                    <?php foreach ($hidden as $location): ?>
                        <span class="ajtb-destination-more-item"><?php echo esc_html($location['path']); ?></span>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>
    </div>
</section>
<?php do_action('ajtb_after_destinations_section', $tour, $normalized); ?>
