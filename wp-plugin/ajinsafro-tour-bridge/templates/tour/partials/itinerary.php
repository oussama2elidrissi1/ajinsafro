<?php
/**
 * Itinerary Partial - By day: aj_tour_days (notes) + aj_tour_day_activities. No WP tours_program in a day.
 * Fallback: when no Laravel days, show WP tours_program list (Traveler style).
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$itinerary = $tour['itinerary'] ?? [];
$wp_program = $tour['wp_program'] ?? ['style' => 'style1', 'items' => []];
$source = $tour['_sources']['itinerary'] ?? 'wordpress';
$session_token = $tour['_session_token'] ?? '';
$tour_id = (int) ($tour['id'] ?? 0);
$activities_catalog = $tour['activities_catalog'] ?? [];
$can_toggle_activities = ($source === 'laravel' && !empty($session_token) && $tour_id > 0);
$outboundFlight = $tour['outboundFlight'] ?? null;
$inboundFlight = $tour['inboundFlight'] ?? null;
$total_days = count($itinerary);
$duration_day = max(1, (int) ($tour['duration_day'] ?? 1));

// No Laravel days: show flights in programme when present, then WP programme or "non disponible"
if (empty($itinerary)) {
    $show_out = !empty($outboundFlight) && function_exists('ajtb_flight_has_content') && ajtb_flight_has_content($outboundFlight);
    $show_in = !empty($inboundFlight) && function_exists('ajtb_flight_has_content') && ajtb_flight_has_content($inboundFlight);
    $has_flights_in_program = $show_out || $show_in;
    $last_day_num = $duration_day;

    if ($has_flights_in_program) {
        // Programme avec uniquement les vols (Jour 1 + dernier jour) quand pas de jours Laravel — toujours les deux blocs (vol ou non disponible)
        ?>
    <section class="ajtb-section" id="itinerary" data-tour-id="<?php echo $tour_id; ?>">
        <h2 class="ajtb-section-title">Programme du Circuit</h2>
        <div class="ajtb-flights-in-programme">
            <div class="ajtb-day-flight-block ajtb-day-flight-outbound" data-aj-day-flight="outbound" data-aj-day-number="1">
                <?php if ($show_out): 
                    $fo_from = trim((string) ($outboundFlight['from_city'] ?? $outboundFlight['depart_label'] ?? ''));
                    $fo_to   = trim((string) ($outboundFlight['to_city'] ?? $outboundFlight['arrive_label'] ?? ''));
                    $fo_from = $fo_from !== '' ? $fo_from : '—';
                    $fo_to   = $fo_to !== '' ? $fo_to : '—';
                ?>
                    <h4 class="ajtb-day-flight-label"><?php esc_html_e('Vol Aller', 'ajinsafro-tour-bridge'); ?> — Jour 1 • <?php echo esc_html($fo_from); ?> → <?php echo esc_html($fo_to); ?></h4>
                    <?php $flight = $outboundFlight; $show_remove = true; include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card.php'; ?>
                <?php else: ?>
                    <h4 class="ajtb-day-flight-label"><?php esc_html_e('Vol Aller', 'ajinsafro-tour-bridge'); ?> — Jour 1</h4>
                    <?php $label = __('Vol Aller non disponible', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card-unavailable.php'; ?>
                <?php endif; ?>
            </div>
            <div class="ajtb-day-flight-block ajtb-day-flight-inbound" data-aj-day-flight="inbound" data-aj-day-number="<?php echo $last_day_num; ?>">
                <?php if ($show_in): 
                    $fi_from = trim((string) ($inboundFlight['from_city'] ?? $inboundFlight['depart_label'] ?? ''));
                    $fi_to   = trim((string) ($inboundFlight['to_city'] ?? $inboundFlight['arrive_label'] ?? ''));
                    $fi_from = $fi_from !== '' ? $fi_from : '—';
                    $fi_to   = $fi_to !== '' ? $fi_to : '—';
                ?>
                    <h4 class="ajtb-day-flight-label"><?php esc_html_e('Vol Retour', 'ajinsafro-tour-bridge'); ?> — Jour <?php echo $last_day_num; ?> • <?php echo esc_html($fi_from); ?> → <?php echo esc_html($fi_to); ?></h4>
                    <?php $flight = $inboundFlight; $show_remove = true; include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card.php'; ?>
                <?php else: ?>
                    <h4 class="ajtb-day-flight-label"><?php esc_html_e('Vol Retour', 'ajinsafro-tour-bridge'); ?> — Jour <?php echo $last_day_num; ?></h4>
                    <?php $label = __('Vol Retour non disponible', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card-unavailable.php'; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($wp_program['items'])): 
            $program_style = isset($wp_program['style']) ? sanitize_html_class($wp_program['style']) : 'style1';
            if ($program_style === '') { $program_style = 'style1'; }
        ?>
        <div class="aj-program-list program-style-<?php echo esc_attr($program_style); ?> mt-4">
            <?php foreach ($wp_program['items'] as $item): 
                $title = isset($item['title']) ? trim((string) $item['title']) : '';
                $desc = isset($item['desc']) ? trim((string) $item['desc']) : '';
            ?>
                <div class="aj-program-item">
                    <?php if ($title !== ''): ?><h4 class="aj-program-item-title"><?php echo esc_html($title); ?></h4><?php endif; ?>
                    <?php if ($desc !== ''): ?><div class="aj-program-item-desc"><?php echo wp_kses_post(nl2br($desc)); ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="itinerary-actions">
            <button type="button" class="btn-outline" onclick="window.print();"><?php esc_html_e('Imprimer le programme', 'ajinsafro-tour-bridge'); ?></button>
        </div>
    </section>
    <?php
        return;
    }

    if (!empty($wp_program['items'])) {
        $program_style = isset($wp_program['style']) ? sanitize_html_class($wp_program['style']) : 'style1';
        if ($program_style === '') { $program_style = 'style1'; }
        ?>
    <section class="ajtb-section" id="itinerary">
        <h2 class="ajtb-section-title">Programme du Circuit</h2>
        <div class="aj-program-list program-style-<?php echo esc_attr($program_style); ?>">
            <?php foreach ($wp_program['items'] as $item): 
                $title = isset($item['title']) ? trim((string) $item['title']) : '';
                $desc = isset($item['desc']) ? trim((string) $item['desc']) : '';
            ?>
                <div class="aj-program-item">
                    <?php if ($title !== ''): ?><h4 class="aj-program-item-title"><?php echo esc_html($title); ?></h4><?php endif; ?>
                    <?php if ($desc !== ''): ?><div class="aj-program-item-desc"><?php echo wp_kses_post(nl2br($desc)); ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="itinerary-actions">
            <button type="button" class="btn-outline" onclick="window.print();"><?php esc_html_e('Imprimer le programme', 'ajinsafro-tour-bridge'); ?></button>
        </div>
    </section>
    <?php
    } else {
        ?>
    <section class="ajtb-section" id="itinerary">
        <h2 class="ajtb-section-title">Programme du Circuit</h2>
        <p class="aj-program-unavailable"><?php esc_html_e('Programme non disponible.', 'ajinsafro-tour-bridge'); ?></p>
    </section>
    <?php
    }
    return;
}
?>

<?php
// Helper: compute "INCLUS : N Vol + N Hôtel + ..." for one day
$ajtb_day_inclus = function ($day, $index, $total_days) {
    $n = (int) ($day['day'] ?? $index + 1);
    $last = $total_days > 0 && $n === (int) $total_days;
    $parts = [];
    if (!empty($day['flight']) && function_exists('ajtb_flight_has_content') && ajtb_flight_has_content($day['flight'])) {
        $parts[] = '1 ' . __('Vol', 'ajinsafro-tour-bridge');
    }
    if (!empty($day['flight_return']) && function_exists('ajtb_flight_has_content') && ajtb_flight_has_content($day['flight_return'])) {
        $parts[] = '1 ' . __('Vol', 'ajinsafro-tour-bridge');
    }
    if (!empty($day['transfer'])) {
        $parts[] = '1 ' . __('Transfert', 'ajinsafro-tour-bridge');
    }
    if (!empty($day['transfer_return'])) {
        $parts[] = '1 ' . __('Transfert', 'ajinsafro-tour-bridge');
    }
    if (!empty($day['hotel'])) {
        $parts[] = '1 ' . __('Hôtel', 'ajinsafro-tour-bridge');
    }
    $act_count = 0;
    if (!empty($day['activities'])) {
        foreach ($day['activities'] as $a) {
            if (!empty($a['is_included'])) $act_count++;
        }
    }
    if ($act_count > 0) {
        $parts[] = $act_count . ' ' . _n('Activité', 'Activités', $act_count, 'ajinsafro-tour-bridge');
    }
    if (!empty(trim((string) ($day['meals'] ?? '')))) {
        $parts[] = '1 ' . __('Repas', 'ajinsafro-tour-bridge');
    }
    return implode(' + ', $parts);
};
?>
<section class="ajtb-section" id="itinerary" data-tour-id="<?php echo $tour_id; ?>" data-session-token="<?php echo esc_attr($session_token); ?>" data-activities-catalog="<?php echo esc_attr(wp_json_encode($activities_catalog)); ?>">
    <h2 class="ajtb-section-title">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
            <path d="M14.5 10c-.83 0-1.5-.67-1.5-1.5v-5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5z"></path>
            <path d="M20.5 10H19V8.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"></path>
            <path d="M9.5 14c.83 0 1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5S8 21.33 8 20.5v-5c0-.83.67-1.5 1.5-1.5z"></path>
            <path d="M3.5 14H5v1.5c0 .83-.67 1.5-1.5 1.5S2 16.33 2 15.5 2.67 14 3.5 14z"></path>
            <path d="M14 14.5c0-.83.67-1.5 1.5-1.5h5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-5c-.83 0-1.5-.67-1.5-1.5z"></path>
            <path d="M15.5 19H14v1.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5-.67-1.5-1.5-1.5z"></path>
            <path d="M10 9.5C10 8.67 9.33 8 8.5 8h-5C2.67 8 2 8.67 2 9.5S2.67 11 3.5 11h5c.83 0 1.5-.67 1.5-1.5z"></path>
            <path d="M8.5 5H10V3.5C10 2.67 9.33 2 8.5 2S7 2.67 7 3.5 7.67 5 8.5 5z"></path>
        </svg>
        Programme du Circuit
        <span class="section-badge"><?php echo count($itinerary); ?> jour<?php echo count($itinerary) > 1 ? 's' : ''; ?></span>
    </h2>

    <!-- Layout MakeMyTrip : 3 colonnes (jours sticky | contenu du jour | sidebar prix déjà en page) -->
    <div class="ajtb-programme-mmt programme-container">
        <nav class="ajtb-programme-days aj-day-plan-nav" aria-label="<?php esc_attr_e('Plan de séjour', 'ajinsafro-tour-bridge'); ?>">
            <div class="ajtb-programme-days-title"><?php esc_html_e('Plan de séjour', 'ajinsafro-tour-bridge'); ?></div>
            <?php foreach ($itinerary as $index => $day): 
                $day_num = $day['day'] ?? ($index + 1);
                $day_mode = isset($day['mode']) ? $day['mode'] : 'program';
                $day_title_short = !empty($day['day_title']) ? $day['day_title'] : ('Jour ' . $day_num);
                if ($day_mode === 'free') {
                    $day_title_short = __('Jour libre', 'ajinsafro-tour-bridge');
                } elseif (strlen($day_title_short) > 28) {
                    $day_title_short = wp_trim_words($day_title_short, 4);
                }
            ?>
                <button type="button" class="aj-day-link aj-day-nav-item <?php echo $index === 0 ? 'active is-active' : ''; ?>" data-day-index="<?php echo $index; ?>" data-day="<?php echo $day_num; ?>" data-aj-nav-day="<?php echo $day_num; ?>">
                    <span class="aj-day-link-num"><?php echo $day_num; ?></span>
                    <span class="aj-day-link-title"><?php echo esc_html($day_title_short); ?></span>
                </button>
            <?php endforeach; ?>
        </nav>
        <div class="ajtb-programme-center aj-day-plan-content">
            <div class="ajtb-day-panels">
        <?php foreach ($itinerary as $index => $day): 
            $day_number = $day['day'] ?? ($index + 1);
            $is_first = ($index === 0);
            $is_last = ($index === $total_days - 1);
            $day_title_display = !empty($day['day_title']) ? $day['day_title'] : ($day['title'] ?? 'Jour ' . $day_number);
            $mode = isset($day['mode']) ? $day['mode'] : 'program';
            $activities = isset($day['activities']) ? $day['activities'] : [];
            $day_id = (int) ($day['id'] ?? 0);
            $day_activity_ids = array_map(function ($a) { return (int) ($a['activity_id'] ?? 0); }, $activities);
            $inclus_line = $ajtb_day_inclus($day, $index, $total_days);
        ?>
            <div class="ajtb-day-content-panel day-card <?php echo $index === 0 ? 'is-selected' : ''; ?>" id="aj-day-panel-<?php echo $day_number; ?>" data-aj-day-panel="<?php echo $day_number; ?>" data-day="<?php echo $day_number; ?>" data-day-index="<?php echo $index; ?>" data-day-id="<?php echo $day_id; ?>" data-day-activity-ids="<?php echo esc_attr(implode(',', $day_activity_ids)); ?>" role="tabpanel" aria-labelledby="aj-day-nav-<?php echo $day_number; ?>" <?php echo $index !== 0 ? 'hidden' : ''; ?>>
                <!-- Header du jour (badge + titre + INCLUS) -->
                <div class="ajtb-day-header-mmt">
                    <span class="ajtb-day-badge">Jour <?php echo $day_number; ?></span>
                    <?php if ($mode === 'free'): ?>
                        <span class="badge badge-free-day"><?php esc_html_e('Jour libre', 'ajinsafro-tour-bridge'); ?></span>
                    <?php endif; ?>
                    <h3 class="ajtb-day-title-mmt"><?php echo esc_html($day_title_display); ?></h3>
                    <?php if ($inclus_line !== ''): ?>
                        <p class="ajtb-day-inclus">INCLUS : <?php echo esc_html($inclus_line); ?></p>
                    <?php endif; ?>
                </div>
                <div class="day-body">
                        <?php
                        // —— Description du jour en premier (toujours affichée en tête)
                        $day_notes = trim((string) ($day['notes'] ?? ''));
                        if ($day_notes === '' && isset($day['description'])) {
                            $day_notes = trim((string) $day['description']);
                        }
                        if ($day_notes === '' && isset($day['content'])) {
                            $day_notes = trim((string) $day['content']);
                        }
                        ?>
                        <div id="aj-day-notes-<?php echo $day_id; ?>" class="aj-day-programme-block aj-day-programme-block--first">
                        <?php
                        if ($day_notes !== ''):
                            $notes_html = wp_kses_post(nl2br($day_notes));
                        ?>
                            <div class="aj-day-notes-wrap">
                                <div class="aj-day-notes-content"><?php echo $notes_html; ?></div>
                            </div>
                        <?php elseif ($mode === 'free'): ?>
                            <p class="aj-day-notes day-notes day-description aj-day-free-label"><?php esc_html_e('Jour libre', 'ajinsafro-tour-bridge'); ?></p>
                        <?php endif; ?>
                        </div>

                        <?php if (!empty($day['image'])): ?>
                        <div class="ajtb-day-banner">
                            <img src="<?php echo esc_url($day['image']); ?>" alt="Jour <?php echo $day_number; ?>" loading="lazy">
                        </div>
                        <?php endif; ?>
                        <?php
                        // —— Blocs inline (pas de sections repliables) : Vol, Transfert, Hôtel dans le contenu du jour
                        if ($is_first):
                            $day_flight = $day['flight'] ?? null;
                            $day_transfer = $day['transfer'] ?? null;
                            $day_hotel = $day['hotel'] ?? null;
                            $show_outbound = !empty($day_flight) && function_exists('ajtb_flight_has_content') && ajtb_flight_has_content($day_flight);
                        ?>
                            <div class="ajtb-block-mmt ajtb-block-flight">
                                <h4 class="ajtb-block-title"><span class="ajtb-block-icon">✈</span> <?php esc_html_e('Vol', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-day-flight-outbound ajtb-card-wrap" data-aj-day-flight="outbound" data-aj-day-number="1">
                                    <?php if ($show_outbound): $flight = $day_flight; $show_remove = false; include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card.php'; ?>
                                    <?php else: $label = __('Vol Aller non disponible', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card-unavailable.php'; endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($day_transfer)): ?>
                            <div class="ajtb-block-mmt ajtb-block-transfer">
                                <h4 class="ajtb-block-title"><?php esc_html_e('Transfert Aéroport → Hôtel', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-card-wrap">
                                    <div class="ajtb-card-with-image ajtb-card-full-width">
                                        <div class="ajtb-card-image ajtb-card-image--transfer"<?php if (!empty($day_transfer['image_url'])) { echo ' style="background-image: url(' . esc_attr($day_transfer['image_url']) . ')"'; } ?>></div>
                                        <div class="ajtb-card-inner"><?php $transfer = $day_transfer; $label = __('Transfert Aéroport → Hôtel', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/transfer-card.php'; ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($day_hotel)): ?>
                            <div class="ajtb-block-mmt ajtb-block-hotel">
                                <h4 class="ajtb-block-title"><?php esc_html_e('Hôtel', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-card-wrap">
                                    <div class="ajtb-card-with-image ajtb-card-full-width">
                                        <div class="ajtb-card-image ajtb-card-image--hotel"<?php if (!empty($day_hotel['image_url'])) { echo ' style="background-image: url(' . esc_attr($day_hotel['image_url']) . ')"'; } ?>></div>
                                        <div class="ajtb-card-inner"><?php $hotel = $day_hotel; $is_checkout = false; include AJTB_PLUGIN_DIR . 'templates/tour/partials/hotel-card.php'; ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($is_last):
                            $day_flight_return = $day['flight_return'] ?? null;
                            $day_transfer_return = $day['transfer_return'] ?? null;
                            $day_hotel_last = $day['hotel'] ?? null;
                            $hotel_checkout = !empty($day['hotel_checkout']);
                            $show_inbound = !empty($day_flight_return) && function_exists('ajtb_flight_has_content') && ajtb_flight_has_content($day_flight_return);
                        ?>
                            <?php if (!empty($day_hotel_last) && $hotel_checkout): ?>
                            <div class="ajtb-block-mmt ajtb-block-hotel">
                                <h4 class="ajtb-block-title"><?php esc_html_e('Hôtel (check-out)', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-card-wrap">
                                    <div class="ajtb-card-with-image ajtb-card-full-width">
                                        <div class="ajtb-card-image ajtb-card-image--hotel"<?php if (!empty($day_hotel_last['image_url'])) { echo ' style="background-image: url(' . esc_attr($day_hotel_last['image_url']) . ')"'; } ?>></div>
                                        <div class="ajtb-card-inner"><?php $hotel = $day_hotel_last; $is_checkout = true; include AJTB_PLUGIN_DIR . 'templates/tour/partials/hotel-card.php'; ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($day_transfer_return)): ?>
                            <div class="ajtb-block-mmt ajtb-block-transfer">
                                <h4 class="ajtb-block-title"><?php esc_html_e('Transfert Hôtel → Aéroport', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-card-wrap">
                                    <div class="ajtb-card-with-image ajtb-card-full-width">
                                        <div class="ajtb-card-image ajtb-card-image--transfer"<?php if (!empty($day_transfer_return['image_url'])) { echo ' style="background-image: url(' . esc_attr($day_transfer_return['image_url']) . ')"'; } ?>></div>
                                        <div class="ajtb-card-inner"><?php $transfer = $day_transfer_return; $label = __('Transfert Hôtel → Aéroport', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/transfer-card.php'; ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="ajtb-block-mmt ajtb-block-flight">
                                <h4 class="ajtb-block-title"><span class="ajtb-block-icon">✈</span> <?php esc_html_e('Vol Retour', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-day-flight-inbound ajtb-card-wrap" data-aj-day-flight="inbound" data-aj-day-number="<?php echo $total_days; ?>">
                                    <?php if ($show_inbound): $flight = $day_flight_return; $show_remove = true; include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card.php'; ?>
                                    <?php else: $label = __('Vol Retour non disponible', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card-unavailable.php'; endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Activities container (id stable: JS replaces innerHTML after AJAX) -->
                        <div id="aj-day-activities-<?php echo $day_id; ?>">
                        <ul class="day-activities-list" data-day-id="<?php echo $day_id; ?>">
                            <?php 
                            $included_count = 0;
                            if (!empty($activities)): ?>
                                <?php foreach ($activities as $act): 
                                    if (empty($act['is_included'])) { continue; }
                                    $included_count++;
                                    $act_title = isset($act['title']) && (string) $act['title'] !== '' ? $act['title'] : '';
                                    $act_desc = isset($act['description']) && (string) $act['description'] !== '' ? $act['description'] : '';
                                    $act_id = (int) ($act['activity_id'] ?? 0);
                                    $is_mandatory = !empty($act['is_mandatory']);
                                    $show_remove = $can_toggle_activities && !$is_mandatory;
                                ?>
                                    <li class="day-activity-item" data-activity-id="<?php echo $act_id; ?>" data-is-mandatory="<?php echo $is_mandatory ? '1' : '0'; ?>">
                                        <span class="activity-title"><?php echo $act_title !== '' ? esc_html($act_title) : esc_html__('Activité', 'ajinsafro-tour-bridge'); ?></span>
                                        <?php if ($is_mandatory): ?>
                                            <span class="badge badge-mandatory">Obligatoire</span>
                                        <?php endif; ?>
                                        <?php if ($show_remove): ?>
                                            <button type="button" class="ajtb-btn-remove-activity" data-aj-action="remove" data-tour-id="<?php echo $tour_id; ?>" data-day-id="<?php echo $day_id; ?>" data-activity-id="<?php echo $act_id; ?>" aria-label="<?php esc_attr_e('Retirer cette activité', 'ajinsafro-tour-bridge'); ?>">Retirer</button>
                                        <?php endif; ?>
                                        <?php if ($act_desc !== ''): ?>
                                            <div class="activity-description"><?php echo wp_kses_post($act_desc); ?></div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($included_count === 0): ?>
                                <li class="day-activity-item day-no-activities"><?php esc_html_e('Aucune activité', 'ajinsafro-tour-bridge'); ?></li>
                            <?php endif; ?>
                        </ul>
                        <?php if ($can_toggle_activities && $day_id > 0): ?>
                            <div class="day-add-activity" data-day-id="<?php echo $day_id; ?>">
                                <label for="aj-add-select-<?php echo $day_id; ?>"><?php esc_html_e('Ajouter une activité', 'ajinsafro-tour-bridge'); ?></label>
                                <select id="aj-add-select-<?php echo $day_id; ?>" class="ajtb-add-activity-select" data-day-id="<?php echo $day_id; ?>">
                                    <option value="">— <?php esc_html_e('Choisir', 'ajinsafro-tour-bridge'); ?> —</option>
                                    <?php
                                    $in_day = $day_activity_ids;
                                    foreach ($activities_catalog as $c): 
                                        $cid = (int) ($c['id'] ?? 0);
                                        if ($cid && !in_array($cid, $in_day, true)): ?>
                                            <option value="<?php echo $cid; ?>"><?php echo esc_html($c['title'] ?? ''); ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                                <button type="button" class="ajtb-btn-add-activity" data-aj-action="add" data-tour-id="<?php echo $tour_id; ?>" data-day-id="<?php echo $day_id; ?>" data-select-id="aj-add-select-<?php echo $day_id; ?>"><?php esc_html_e('Ajouter', 'ajinsafro-tour-bridge'); ?></button>
                            </div>
                        <?php endif; ?>
                        </div>

                        <!-- Day Details (Laravel: meals, accommodation) -->
                        <?php if ($source === 'laravel'): ?>
                            <div class="day-details">
                                <?php if (!empty($day['meals'])): ?>
                                    <div class="detail-item">
                                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
                                            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                            <line x1="6" y1="1" x2="6" y2="4"></line>
                                            <line x1="10" y1="1" x2="10" y2="4"></line>
                                            <line x1="14" y1="1" x2="14" y2="4"></line>
                                        </svg>
                                        <span class="detail-label">Repas:</span>
                                        <span class="detail-value"><?php echo esc_html($day['meals']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($day['accommodation'])): ?>
                                    <div class="detail-item">
                                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                            <polyline points="9,22 9,12 15,12 15,22"></polyline>
                                        </svg>
                                        <span class="detail-label">Hébergement:</span>
                                        <span class="detail-value"><?php echo esc_html($day['accommodation']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Print/Download Itinerary -->
    <div class="itinerary-actions">
        <button type="button" class="btn-outline" onclick="window.print();">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                <polyline points="6,9 6,2 18,2 18,9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Imprimer le programme
        </button>
        
        <button type="button" class="btn-text" id="expand-all-days">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2">
                <polyline points="15,3 21,3 21,9"></polyline>
                <polyline points="9,21 3,21 3,15"></polyline>
                <line x1="21" y1="3" x2="14" y2="10"></line>
                <line x1="3" y1="21" x2="10" y2="14"></line>
            </svg>
            Tout déplier
        </button>
    </div>
</section>
