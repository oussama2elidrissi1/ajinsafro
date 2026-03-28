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
// Normalize to arrays for multi-vol support (outbound/inbound can be single row or array of rows)
$outboundFlightsList = [];
if (is_array($outboundFlight) && isset($outboundFlight[0]) && is_array($outboundFlight[0])) {
    $outboundFlightsList = $outboundFlight;
} elseif (is_array($outboundFlight) && !empty($outboundFlight) && (isset($outboundFlight['id']) || isset($outboundFlight['flight_type']))) {
    $outboundFlightsList = [$outboundFlight];
}
$inboundFlightsList = [];
if (is_array($inboundFlight) && isset($inboundFlight[0]) && is_array($inboundFlight[0])) {
    $inboundFlightsList = $inboundFlight;
} elseif (is_array($inboundFlight) && !empty($inboundFlight) && (isset($inboundFlight['id']) || isset($inboundFlight['flight_type']))) {
    $inboundFlightsList = [$inboundFlight];
}
$total_days = count($itinerary);
$duration_day = max(1, (int) ($tour['duration_day'] ?? 1));

// Diagnostic (visible in View Source): table name, counts. Si total_rows=0 => sync Laravel→WP ou préfixe table.
$flights_debug = $tour['_flights_debug'] ?? null;
if ($flights_debug !== null && (defined('WP_DEBUG') && WP_DEBUG || !empty($_GET['ajtb_flights_debug']))) {
    $d = $flights_debug;
    echo '<!-- AJTB flights | table=' . esc_attr($d['table'] ?? '') . ' | exists=' . (isset($d['table_exists']) && $d['table_exists'] ? '1' : '0') . ' | tour_id=' . (int) ($d['tour_id'] ?? 0) . ' | total_rows=' . (int) ($d['total_rows'] ?? 0) . ' | outbound=' . (int) ($d['outbound'] ?? 0) . ' | inbound=' . (int) ($d['inbound'] ?? 0) . ' | segments=' . esc_attr(implode(',', isset($d['segments_keys']) ? $d['segments_keys'] : [])) . ' -->' . "\n";
}

// No Laravel days: show flights in programme when present. Fallback "non disponible" only when list is empty.
if (empty($itinerary)) {
    $outbound_count = is_array($outboundFlightsList) ? count($outboundFlightsList) : 0;
    $inbound_count = is_array($inboundFlightsList) ? count($inboundFlightsList) : 0;
    $show_out = $outbound_count > 0;
    $show_in = $inbound_count > 0;
    $has_flights_in_program = $show_out || $show_in;
    $last_day_num = $duration_day;

    if ($has_flights_in_program) {
        ?>
    <section class="ajtb-section" id="itinerary" data-tour-id="<?php echo $tour_id; ?>">
        <h2 class="ajtb-section-title">Programme du Circuit</h2>
        <div class="ajtb-flights-in-programme">
            <div class="ajtb-day-flight-block ajtb-day-flight-outbound" data-aj-day-flight="outbound" data-aj-day-number="1">
                <?php if ($show_out): 
                    $first_out = $outboundFlightsList[0] ?? [];
                    $fo_from = trim((string) ($first_out['from_city'] ?? $first_out['depart_label'] ?? ''));
                    $fo_to   = trim((string) ($first_out['to_city'] ?? $first_out['arrive_label'] ?? ''));
                    $fo_from = $fo_from !== '' ? $fo_from : '—';
                    $fo_to   = $fo_to !== '' ? $fo_to : '—';
                ?>
                    <h4 class="ajtb-day-flight-label"><?php esc_html_e('Vol Aller', 'ajinsafro-tour-bridge'); ?> — Jour 1 • <?php echo esc_html($fo_from); ?> → <?php echo esc_html($fo_to); ?></h4>
                    <?php $flight = $outboundFlightsList[0]; $show_remove = true; include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card.php'; ?>
                <?php else: ?>
                    <h4 class="ajtb-day-flight-label"><?php esc_html_e('Vol Aller', 'ajinsafro-tour-bridge'); ?> — Jour 1</h4>
                    <?php $label = __('Vol Aller non disponible', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card-unavailable.php'; ?>
                <?php endif; ?>
            </div>
            <div class="ajtb-day-flight-block ajtb-day-flight-inbound" data-aj-day-flight="inbound" data-aj-day-number="<?php echo $last_day_num; ?>">
                <?php if ($show_in): 
                    $first_in = $inboundFlightsList[0] ?? [];
                    $fi_from = trim((string) ($first_in['from_city'] ?? $first_in['depart_label'] ?? ''));
                    $fi_to   = trim((string) ($first_in['to_city'] ?? $first_in['arrive_label'] ?? ''));
                    $fi_from = $fi_from !== '' ? $fi_from : '—';
                    $fi_to   = $fi_to !== '' ? $fi_to : '—';
                ?>
                    <h4 class="ajtb-day-flight-label"><?php esc_html_e('Vol Retour', 'ajinsafro-tour-bridge'); ?> — Jour <?php echo $last_day_num; ?> • <?php echo esc_html($fi_from); ?> → <?php echo esc_html($fi_to); ?></h4>
                    <?php foreach ($inboundFlightsList as $flight): $show_remove = true; include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card.php'; endforeach; ?>
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
// Helper: normalize day flight(s) to array (single row or array of rows)
$ajtb_day_flights_list = function ($flight_or_list) {
    if (empty($flight_or_list)) {
        return [];
    }
    $first = is_array($flight_or_list) ? reset($flight_or_list) : null;
    $is_list = $first && is_array($first) && (isset($first['from_city']) || isset($first['flight_type']) || isset($first['depart_label']));
    return $is_list ? $flight_or_list : [$flight_or_list];
};

// Helper: compute "INCLUS : N Vol + N Hôtel + ..." for one day
$ajtb_day_inclus = function ($day, $index, $total_days) use ($ajtb_day_flights_list) {
    $n = (int) ($day['day'] ?? $index + 1);
    $last = $total_days > 0 && $n === (int) $total_days;
    $parts = [];
    if (function_exists('ajtb_flights_have_content') && ajtb_flights_have_content($ajtb_day_flights_list($day['flight'] ?? []))) {
        $cnt = count(array_filter($ajtb_day_flights_list($day['flight'] ?? []), function ($f) { return function_exists('ajtb_flight_has_content') && ajtb_flight_has_content($f); }));
        $parts[] = $cnt . ' ' . _n('Vol', 'Vols', $cnt, 'ajinsafro-tour-bridge');
    }
    if (function_exists('ajtb_flights_have_content') && ajtb_flights_have_content($ajtb_day_flights_list($day['flight_return'] ?? []))) {
        $cnt = count(array_filter($ajtb_day_flights_list($day['flight_return'] ?? []), function ($f) { return function_exists('ajtb_flight_has_content') && ajtb_flight_has_content($f); }));
        $parts[] = $cnt . ' ' . _n('Vol', 'Vols', $cnt, 'ajinsafro-tour-bridge');
    }
    $tr_arr = isset($day['transfer']) && is_array($day['transfer']) ? $day['transfer'] : [];
    $tr_dep = isset($day['transfer_return']) && is_array($day['transfer_return']) ? $day['transfer_return'] : [];
    if (count($tr_arr) + count($tr_dep) > 0) {
        $cnt = count($tr_arr) + count($tr_dep);
        $parts[] = $cnt . ' ' . _n('Transfert', 'Transferts', $cnt, 'ajinsafro-tour-bridge');
    }
    $hotels_list = isset($day['hotels']) && is_array($day['hotels']) ? $day['hotels'] : (!empty($day['hotel']) ? [$day['hotel']] : []);
    if (count($hotels_list) > 0) {
        $parts[] = count($hotels_list) . ' ' . _n('Hôtel', 'Hôtels', count($hotels_list), 'ajinsafro-tour-bridge');
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

// Structured includes per day for Top Bar (flights, transfers, hotels, activities counts)
$ajtb_day_includes_raw = function ($day, $index, $total_days) use ($ajtb_day_flights_list) {
    $n = (int) ($day['day'] ?? $index + 1);
    $last = $total_days > 0 && $n === (int) $total_days;
    $flights = 0;
    $flight_list = $ajtb_day_flights_list($day['flight'] ?? []);
    $return_list = $ajtb_day_flights_list($day['flight_return'] ?? []);
    foreach ($flight_list as $f) { if (function_exists('ajtb_flight_has_content') && ajtb_flight_has_content($f)) $flights++; }
    foreach ($return_list as $f) { if (function_exists('ajtb_flight_has_content') && ajtb_flight_has_content($f)) $flights++; }
    $transfers = 0;
    $tr_arr = isset($day['transfer']) && is_array($day['transfer']) ? $day['transfer'] : [];
    $tr_dep = isset($day['transfer_return']) && is_array($day['transfer_return']) ? $day['transfer_return'] : [];
    $transfers = count($tr_arr) + count($tr_dep);
    $hotels_list_day = isset($day['hotels']) && is_array($day['hotels']) ? $day['hotels'] : (!empty($day['hotel']) ? [$day['hotel']] : []);
    $hotels = count($hotels_list_day);
    $act_count = 0;
    if (!empty($day['activities'])) {
        foreach ($day['activities'] as $a) { if (!empty($a['is_included'])) $act_count++; }
    }
    $meals = !empty(trim((string) ($day['meals'] ?? ''))) ? 1 : 0;
    return ['flights' => $flights, 'transfers' => $transfers, 'hotels' => $hotels, 'activities' => $act_count, 'meals' => $meals];
};
$day_includes_for_js = [];
foreach ($itinerary as $index => $day) {
    $day_num = $day['day'] ?? ($index + 1);
    $day_includes_for_js[(string) $day_num] = $ajtb_day_includes_raw($day, $index, $total_days);
}

// Calculate global totals (all days combined)
$global_totals = [
    'days' => $total_days,
    'flights' => 0,
    'transfers' => 0,
    'hotels' => 0,
    'activities' => 0,
    'meals' => 0,
];
foreach ($itinerary as $index => $day) {
    $day_includes = $ajtb_day_includes_raw($day, $index, $total_days);
    $global_totals['flights'] += $day_includes['flights'];
    $global_totals['transfers'] += $day_includes['transfers'];
    $global_totals['hotels'] += $day_includes['hotels'];
    $global_totals['activities'] += $day_includes['activities'];
    // Count meals
    if (!empty(trim((string) ($day['meals'] ?? '')))) {
        $global_totals['meals']++;
    }
}
?>
<section class="ajtb-section ajtb-tab-panel" id="itinerary" data-tour-id="<?php echo $tour_id; ?>" data-session-token="<?php echo esc_attr($session_token); ?>" data-activities-catalog="<?php echo esc_attr(wp_json_encode($activities_catalog)); ?>" data-day-includes="<?php echo esc_attr(wp_json_encode($day_includes_for_js)); ?>">
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

    <!-- Layout : 1 rectangle tabs | grille 2 col (plan de séjour | header + contenu) -->
    <div class="ajtb-itinerary-layout sticky-itinerary-container" id="sticky-itinerary-container">
        <!-- Bloc 1 : barre manuelle (titre) + onglets, collés et sticky ensemble -->
        <div class="ajtb-tabs-block ajtb-itinerary-block--tabs sticky-itinerary-container__tabs">
            <div class="sticky-itinerary-container__header ajtb-sticky-manual-title-bar" aria-hidden="false">
                <h2 class="ajtb-itinerary-sticky-title"><?php echo esc_html(isset($tour['title']) ? $tour['title'] : get_the_title()); ?></h2>
            </div>
            <div class="ajtb-global-summary-bar" id="ajtb-global-summary-bar" data-ajtb-mode="global">
                <nav class="ajtb-global-summary-nav" aria-label="<?php esc_attr_e('Résumé du séjour', 'ajinsafro-tour-bridge'); ?>">
                    <button type="button" class="ajtb-global-pill active" data-ajtb-global-tab="programme" aria-pressed="true">
                        <span class="ajtb-global-pill-label"><?php echo esc_html($global_totals['days']); ?> DAY PLAN</span>
                    </button>
                    <?php if ($global_totals['flights'] > 0 || $global_totals['transfers'] > 0): ?>
                        <button type="button" class="ajtb-global-pill" data-ajtb-global-tab="flights-transfers" aria-pressed="false">
                            <span class="ajtb-global-pill-label">
                                <?php
                                $parts = [];
                                if ($global_totals['flights'] > 0) $parts[] = $global_totals['flights'] . ' ' . ($global_totals['flights'] > 1 ? __('FLIGHTS', 'ajinsafro-tour-bridge') : __('FLIGHT', 'ajinsafro-tour-bridge'));
                                if ($global_totals['transfers'] > 0) $parts[] = $global_totals['transfers'] . ' ' . ($global_totals['transfers'] > 1 ? __('TRANSFERS', 'ajinsafro-tour-bridge') : __('TRANSFER', 'ajinsafro-tour-bridge'));
                                echo esc_html(implode(' & ', $parts));
                                ?>
                            </span>
                        </button>
                    <?php endif; ?>
                    <?php if ($global_totals['hotels'] > 0): ?>
                        <button type="button" class="ajtb-global-pill" data-ajtb-global-tab="hotels" aria-pressed="false">
                            <span class="ajtb-global-pill-label"><?php echo esc_html($global_totals['hotels']); ?> <?php echo $global_totals['hotels'] > 1 ? __('HOTELS', 'ajinsafro-tour-bridge') : __('HOTEL', 'ajinsafro-tour-bridge'); ?></span>
                        </button>
                    <?php endif; ?>
                    <?php if ($global_totals['activities'] > 0): ?>
                        <button type="button" class="ajtb-global-pill" data-ajtb-global-tab="activities" aria-pressed="false">
                            <span class="ajtb-global-pill-label"><?php echo esc_html($global_totals['activities']); ?> <?php echo $global_totals['activities'] > 1 ? __('ACTIVITIES', 'ajinsafro-tour-bridge') : __('ACTIVITY', 'ajinsafro-tour-bridge'); ?></span>
                        </button>
                    <?php endif; ?>
                    <?php if ($global_totals['meals'] > 0): ?>
                        <button type="button" class="ajtb-global-pill" data-ajtb-global-tab="meals" aria-pressed="false">
                            <span class="ajtb-global-pill-label"><?php echo esc_html($global_totals['meals']); ?> <?php echo $global_totals['meals'] > 1 ? __('MEALS', 'ajinsafro-tour-bridge') : __('MEAL', 'ajinsafro-tour-bridge'); ?></span>
                        </button>
                    <?php endif; ?>
                </nav>
            </div>
            <!-- Rectangle barre jour : collé sous les onglets, pleine largeur -->
            <div class="ajtb-day-bar-row">
                <div class="ajtb-day-details-bar aj-program-stickybar" id="ajtb-day-details-bar" data-ajtb-bar>
                    <div class="ajtb-day-details-bar__left">
                        <span class="ajtb-day-details-bar__day" id="ajtb-day-details-day-label">Jour 1</span>
                        <?php
                        $first_day = $itinerary[0] ?? null;
                        if ($first_day):
                            $inc = $ajtb_day_includes_raw($first_day, 0, $total_days);
                            $day_title_short = !empty($first_day['day_title']) ? $first_day['day_title'] : (!empty($first_day['title']) ? $first_day['title'] : '');
                            $inclus_parts = [];
                            $inclus_parts[] = ['type' => 'transfer', 'n' => (int) ($inc['transfers'] ?? 0), 'singular' => __('Transfert', 'ajinsafro-tour-bridge'), 'plural' => __('Transferts', 'ajinsafro-tour-bridge')];
                            $inclus_parts[] = ['type' => 'activity', 'n' => (int) ($inc['activities'] ?? 0), 'singular' => __('Activité', 'ajinsafro-tour-bridge'), 'plural' => __('Activités', 'ajinsafro-tour-bridge')];
                            $inclus_parts[] = ['type' => 'hotel', 'n' => (int) ($inc['hotels'] ?? 0), 'singular' => __('Hôtel', 'ajinsafro-tour-bridge'), 'plural' => __('Hôtels', 'ajinsafro-tour-bridge')];
                            $inclus_parts[] = ['type' => 'meal', 'n' => (int) ($inc['meals'] ?? 0), 'singular' => __('Repas', 'ajinsafro-tour-bridge'), 'plural' => __('Repas', 'ajinsafro-tour-bridge')];
                            $inclus_parts[] = ['type' => 'flight', 'n' => (int) ($inc['flights'] ?? 0), 'singular' => __('Vol', 'ajinsafro-tour-bridge'), 'plural' => __('Vols', 'ajinsafro-tour-bridge')];
                        ?>
                        <span class="ajtb-day-details-bar__inclus-line" id="ajtb-day-details-inclus-line" data-day-title="<?php echo esc_attr($day_title_short); ?>">
                            <?php if ($day_title_short): ?><span class="ajtb-inclus-location"><?php echo esc_html($day_title_short); ?></span> <?php endif; ?>
                            <strong class="ajtb-inclus-label"><?php esc_html_e('INCLUDED:', 'ajinsafro-tour-bridge'); ?></strong>
                            <?php foreach ($inclus_parts as $part): ?>
                                <?php
                                $label = $part['n'] === 1 ? $part['singular'] : $part['plural'];
                                $text = $part['n'] > 0 ? (int) $part['n'] . ' ' . $label : $label;
                                ?>
                                <span class="ajtb-inclus-item ajtb-inclus-item--<?php echo esc_attr($part['type']); ?>">
                                    <span class="ajtb-inclus-icon" aria-hidden="true"></span>
                                    <span class="ajtb-inclus-text"><?php echo esc_html($text); ?></span>
                                </span>
                            <?php endforeach; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grille : plan de séjour (sticky) | contenu -->
        <div class="ajtb-itinerary-grid ajtb-itinerary-body">
            <aside class="ajtb-plan-sidebar ajtb-itinerary-block ajtb-itinerary-block--sidebar ajtb-plan-sidebar--sticky">
                <h3 class="ajtb-itinerary-sidebar-title"><?php esc_html_e('Day Plan', 'ajinsafro-tour-bridge'); ?></h3>
                <nav class="ajtb-programme-days aj-day-plan-nav" aria-label="<?php esc_attr_e('Plan de séjour', 'ajinsafro-tour-bridge'); ?>">
                    <ul class="day-plan">
                <?php foreach ($itinerary as $index => $day):
                        $day_num = $day['day'] ?? ($index + 1);
                        $day_mode = isset($day['mode']) ? $day['mode'] : 'program';
                        $day_date = isset($day['date']) ? $day['date'] : '';
                        if ($day_mode === 'free') {
                            $day_label = __('Jour libre', 'ajinsafro-tour-bridge');
                        } elseif (!empty($day_date) && is_string($day_date)) {
                            $day_label = date_i18n('d M, D', strtotime($day_date));
                        } elseif (!empty($day['day_title'])) {
                            $day_label = strlen($day['day_title']) > 24 ? wp_trim_words($day['day_title'], 3) : $day['day_title'];
                        } else {
                            $day_label = 'Jour ' . $day_num;
                        }
                        $is_active = $index === 0;
                    ?>
                        <li class="day-plan__item">
                            <button type="button" class="day-plan__link aj-day-nav-item <?php echo $is_active ? 'active is-active' : ''; ?>" data-day-index="<?php echo $index; ?>" data-day="<?php echo $day_num; ?>" data-aj-nav-day="<?php echo $day_num; ?>" id="aj-day-nav-<?php echo $day_num; ?>">
                                <span class="day-plan__dot"></span>
                                <span class="day-plan__label"><?php echo esc_html($day_label); ?></span>
                            </button>
                        </li>
                <?php endforeach; ?>
                    </ul>
                </nav>
            </aside>
            <div class="ajtb-right-column ajtb-itinerary-right">
                <div class="ajtb-day-content-block ajtb-itinerary-block ajtb-itinerary-block--day-content">
                    <div class="ajtb-day-panels aj-day-plan-content">
        <?php foreach ($itinerary as $index => $day): 
            $day_number = $day['day'] ?? ($index + 1);
            $is_first = ($index === 0);
            $is_last = ($index === $total_days - 1);
            $day_flights = $ajtb_day_flights_list($day['flight'] ?? []);
            $day_flights_return = $ajtb_day_flights_list($day['flight_return'] ?? []);
            $day_title_display = !empty($day['day_title']) ? $day['day_title'] : ($day['title'] ?? 'Jour ' . $day_number);
            $day_date_raw = isset($day['date']) ? trim((string) $day['date']) : '';
            $day_date_display = $day_date_raw;
            if ($day_date_raw !== '' && strtotime($day_date_raw) !== false) {
                $day_date_display = date_i18n('d/m/Y', strtotime($day_date_raw));
            }
            $mode = isset($day['mode']) ? $day['mode'] : 'program';
            $activities = isset($day['activities']) ? $day['activities'] : [];
            $day_id = (int) ($day['id'] ?? 0);
            $day_activity_ids = array_map(function ($a) { return (int) ($a['activity_id'] ?? 0); }, $activities);
            $inclus_line = $ajtb_day_inclus($day, $index, $total_days);
            $day_inc = $ajtb_day_includes_raw($day, $index, $total_days);
            $day_inclus_parts = [];
            $day_inclus_parts[] = ['type' => 'transfer', 'n' => (int) ($day_inc['transfers'] ?? 0), 'singular' => __('Transfert', 'ajinsafro-tour-bridge'), 'plural' => __('Transferts', 'ajinsafro-tour-bridge')];
            $day_inclus_parts[] = ['type' => 'activity', 'n' => (int) ($day_inc['activities'] ?? 0), 'singular' => __('Activité', 'ajinsafro-tour-bridge'), 'plural' => __('Activités', 'ajinsafro-tour-bridge')];
            $day_inclus_parts[] = ['type' => 'hotel', 'n' => (int) ($day_inc['hotels'] ?? 0), 'singular' => __('Hôtel', 'ajinsafro-tour-bridge'), 'plural' => __('Hôtels', 'ajinsafro-tour-bridge')];
            $day_inclus_parts[] = ['type' => 'meal', 'n' => (int) ($day_inc['meals'] ?? 0), 'singular' => __('Repas', 'ajinsafro-tour-bridge'), 'plural' => __('Repas', 'ajinsafro-tour-bridge')];
            $day_inclus_parts[] = ['type' => 'flight', 'n' => (int) ($day_inc['flights'] ?? 0), 'singular' => __('Vol', 'ajinsafro-tour-bridge'), 'plural' => __('Vols', 'ajinsafro-tour-bridge')];

            // Libellé du jour aligné sur le Day Plan (colonne de gauche)
            $day_label_for_included = '';
            $day_mode = $mode;
            $day_date_for_label = isset($day['date']) ? $day['date'] : '';
            if ($day_mode === 'free') {
                $day_label_for_included = __('Jour libre', 'ajinsafro-tour-bridge');
            } elseif (!empty($day_date_for_label) && is_string($day_date_for_label)) {
                $day_label_for_included = date_i18n('d M, D', strtotime($day_date_for_label));
            } elseif (!empty($day['day_title'])) {
                $day_label_for_included = strlen($day['day_title']) > 24 ? wp_trim_words($day['day_title'], 3) : $day['day_title'];
            } else {
                $day_label_for_included = 'Jour ' . $day_number;
            }
        ?>
            <div class="ajtb-day-content-panel day-card" id="aj-day-panel-<?php echo $day_number; ?>" data-aj-day-panel="<?php echo $day_number; ?>" data-day="<?php echo $day_number; ?>" data-day-id="<?php echo $day_number; ?>" data-day-title="<?php echo esc_attr('Jour ' . $day_number); ?>" data-day-index="<?php echo $index; ?>" data-day-db-id="<?php echo $day_id; ?>" data-day-activity-ids="<?php echo esc_attr(implode(',', $day_activity_ids)); ?>" role="tabpanel" aria-labelledby="aj-day-nav-<?php echo $day_number; ?>">
                <span id="aj-day-<?php echo $day_number; ?>" class="aj-day-anchor" aria-hidden="true"></span>
                <!-- Header du jour : badge + date + inclus (même infos que la barre sticky) -->
                <div class="ajtb-day-header-mmt">
                    <span class="ajtb-day-details-bar__day ajtb-day-pill">Jour<?php echo (int) $day_number; ?></span>
                    <?php if ($day_date_display !== ''): ?>
                        <span class="ajtb-day-header-mmt__date"><?php echo esc_html($day_date_display); ?></span>
                    <?php endif; ?>
                    <span class="ajtb-day-details-bar__inclus-line ajtb-day-header-mmt__inclus">
                        <?php if (!empty($day_label_for_included)): ?>
                            <span class="ajtb-inclus-location"><?php echo esc_html($day_label_for_included); ?></span>
                        <?php endif; ?>
                        <strong class="ajtb-inclus-label"><?php esc_html_e('INCLUDED:', 'ajinsafro-tour-bridge'); ?></strong>
                        <?php foreach ($day_inclus_parts as $part): ?>
                            <?php
                            $count = (int) ($part['n'] ?? 0);
                            if ($count <= 0) {
                                continue;
                            }
                            $label = $count === 1 ? $part['singular'] : $part['plural'];
                            $text = $count . ' ' . $label;
                            ?>
                            <span class="ajtb-inclus-item ajtb-inclus-item--<?php echo esc_attr($part['type']); ?>">
                                <span class="ajtb-inclus-icon" aria-hidden="true"></span>
                                <span class="ajtb-inclus-text"><?php echo esc_html($text); ?></span>
                            </span>
                        <?php endforeach; ?>
                    </span>
                    <?php if ($mode === 'free'): ?>
                        <span class="badge badge-free-day"><?php esc_html_e('Jour libre', 'ajinsafro-tour-bridge'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="day-body">
                        <?php
                        // —— Programme (notes + bannière) : filtre "Programme"
                        $day_notes = trim((string) ($day['notes'] ?? ''));
                        if ($day_notes === '' && isset($day['description'])) {
                            $day_notes = trim((string) $day['description']);
                        }
                        if ($day_notes === '' && isset($day['content'])) {
                            $day_notes = trim((string) $day['content']);
                        }
                        ?>
                        <div class="ajtb-tab-block" data-ajtb-tab="programme">
                        <div id="aj-day-notes-<?php echo $day_id; ?>" class="aj-day-programme-block aj-day-programme-block--first">
                        <?php
                        if ($day_notes !== ''):
                            $notes_html = wp_kses_post(nl2br($day_notes));
                            $notes_plain = trim(wp_strip_all_tags($day_notes));
                            $notes_length = function_exists('mb_strlen') ? mb_strlen($notes_plain) : strlen($notes_plain);
                            $should_collapse_notes = $notes_length > 320;
                        ?>
                            <div class="aj-day-notes-wrap<?php echo $should_collapse_notes ? ' aj-day-notes-collapsed' : ''; ?>">
                                <div class="aj-day-notes-content"><?php echo $notes_html; ?></div>
                                <?php if ($should_collapse_notes): ?>
                                    <button type="button" class="aj-day-notes-read-more" aria-expanded="false">
                                        <?php esc_html_e('Voir plus', 'ajinsafro-tour-bridge'); ?>
                                    </button>
                                <?php endif; ?>
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
                        </div>

                        <?php
                        // —— Blocs inline : Vol(s), transferts, hôtels (multi-row support).
                        $day_transfer_list = isset($day['transfer']) && is_array($day['transfer']) ? $day['transfer'] : (!empty($day['transfer']) ? [$day['transfer']] : []);
                        $day_transfer_return_list = isset($day['transfer_return']) && is_array($day['transfer_return']) ? $day['transfer_return'] : (!empty($day['transfer_return']) ? [$day['transfer_return']] : []);
                        $day_hotels_list = isset($day['hotels']) && is_array($day['hotels']) ? $day['hotels'] : (!empty($day['hotel']) ? [$day['hotel']] : []);
                        $day_hotel = isset($day_hotels_list[0]) ? $day_hotels_list[0] : null;
                        $day_flights_count = is_array($day_flights) ? count($day_flights) : 0;
                        $day_flights_return_count = is_array($day_flights_return) ? count($day_flights_return) : 0;
                        $has_day_flights = $day_flights_count > 0;
                        $has_day_flights_return = $day_flights_return_count > 0;
                        if ($is_first && $has_day_flights): ?>
                            <div class="ajtb-block-mmt ajtb-block-flight ajtb-tab-block" data-ajtb-tab="flights">
                                <h4 class="ajtb-block-title"><span class="ajtb-block-icon ajtb-block-icon--flight" aria-hidden="true"></span> <?php esc_html_e('Vol', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-day-flight-outbound ajtb-card-wrap" data-aj-day-flight="outbound" data-aj-day-number="1">
                                    <?php $flight = $day_flights[0]; $show_remove = false; include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card.php'; ?>
                                </div>
                            </div>
                        <?php elseif ($is_first): ?>
                            <div class="ajtb-block-mmt ajtb-block-flight ajtb-tab-block" data-ajtb-tab="flights">
                                <h4 class="ajtb-block-title"><span class="ajtb-block-icon ajtb-block-icon--flight" aria-hidden="true"></span> <?php esc_html_e('Vol', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-day-flight-outbound ajtb-card-wrap" data-aj-day-flight="outbound" data-aj-day-number="1">
                                    <?php $label = __('Vol Aller non disponible', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card-unavailable.php'; ?>
                                </div>
                            </div>
                        <?php elseif ($has_day_flights): ?>
                            <div class="ajtb-block-mmt ajtb-block-flight ajtb-tab-block" data-ajtb-tab="flights">
                                <h4 class="ajtb-block-title"><span class="ajtb-block-icon ajtb-block-icon--flight" aria-hidden="true"></span> <?php esc_html_e('Vol(s)', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-card-wrap" data-aj-day-flight="segment" data-aj-day-number="<?php echo (int) $day_number; ?>">
                                    <?php foreach ($day_flights as $flight): $show_remove = false; include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card.php'; endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (($is_first || !empty($day_transfer_list)) && !empty($day_transfer_list)): ?>
                            <div class="ajtb-block-mmt ajtb-block-transfer ajtb-tab-block" data-ajtb-tab="transfers">
                                <h4 class="ajtb-block-title"><span class="ajtb-block-icon ajtb-block-icon--transfer" aria-hidden="true"></span> <?php echo count($day_transfer_list) > 1 ? esc_html__('Transferts Arrivée', 'ajinsafro-tour-bridge') : esc_html__('Transfert Aéroport → Hôtel', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-card-wrap">
                                    <?php foreach ($day_transfer_list as $day_transfer_item): ?>
                                    <div class="ajtb-card-with-image ajtb-card-full-width ajtb-card--transfer">
                                        <div class="ajtb-card-image ajtb-card-image--transfer<?php echo !empty($day_transfer_item['image_url']) ? ' has-image' : ''; ?>"<?php if (!empty($day_transfer_item['image_url'])) { echo ' style="background-image: url(' . esc_attr($day_transfer_item['image_url']) . ')"'; } ?>></div>
                                        <div class="ajtb-card-inner"><?php $transfer = $day_transfer_item; $label = __('Transfert Aéroport → Hôtel', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/transfer-card.php'; ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($day_hotels_list)): ?>
                            <div class="ajtb-block-mmt ajtb-block-hotel ajtb-tab-block" data-ajtb-tab="hotels">
                                <h4 class="ajtb-block-title"><span class="ajtb-block-icon ajtb-block-icon--hotel" aria-hidden="true"></span> <?php echo count($day_hotels_list) > 1 ? esc_html__('Hôtels', 'ajinsafro-tour-bridge') : esc_html__('Hôtel', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-card-wrap">
                                    <?php foreach ($day_hotels_list as $day_hotel_item): ?>
                                    <div class="ajtb-card-with-image ajtb-card-full-width ajtb-card--hotel ajtb-hotel-listing">
                                        <div class="ajtb-hotel-listing__media">
                                            <div class="ajtb-hotel-main-image ajtb-card-image--hotel<?php echo !empty($day_hotel_item['image_url']) ? ' has-image' : ''; ?>"<?php if (!empty($day_hotel_item['image_url'])) { echo ' style="background-image: url(' . esc_attr($day_hotel_item['image_url']) . ')"'; } ?>></div>
                                            <div class="ajtb-hotel-thumbs">
                                                <?php
                                                $thumb_url = !empty($day_hotel_item['image_url']) ? $day_hotel_item['image_url'] : '';
                                                for ($i = 0; $i < 3; $i++) {
                                                    echo '<div class="ajtb-hotel-thumb' . ($thumb_url ? ' has-image' : '') . '"' . ($thumb_url ? ' style="background-image: url(' . esc_attr($thumb_url) . ')"' : '') . '></div>';
                                                }
                                                ?>
                                                <div class="ajtb-hotel-thumb ajtb-hotel-thumb--viewall">1+ <?php esc_html_e('View All', 'ajinsafro-tour-bridge'); ?></div>
                                            </div>
                                        </div>
                                        <div class="ajtb-card-inner ajtb-hotel-listing__body"><?php $hotel = $day_hotel_item; $is_checkout = false; include AJTB_PLUGIN_DIR . 'templates/tour/partials/hotel-card.php'; ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                        <?php if (!empty($day_transfer_return_list)): ?>
                            <div class="ajtb-block-mmt ajtb-block-transfer ajtb-tab-block" data-ajtb-tab="transfers">
                                <h4 class="ajtb-block-title"><span class="ajtb-block-icon ajtb-block-icon--transfer" aria-hidden="true"></span> <?php echo count($day_transfer_return_list) > 1 ? esc_html__('Transferts Hôtel → Aéroport', 'ajinsafro-tour-bridge') : esc_html__('Transfert Hôtel → Aéroport', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-card-wrap">
                                    <?php foreach ($day_transfer_return_list as $day_transfer_ret): ?>
                                    <div class="ajtb-card-with-image ajtb-card-full-width ajtb-card--transfer">
                                        <div class="ajtb-card-image ajtb-card-image--transfer<?php echo !empty($day_transfer_ret['image_url']) ? ' has-image' : ''; ?>"<?php if (!empty($day_transfer_ret['image_url'])) { echo ' style="background-image: url(' . esc_attr($day_transfer_ret['image_url']) . ')"'; } ?>></div>
                                        <div class="ajtb-card-inner"><?php $transfer = $day_transfer_ret; $label = __('Transfert Hôtel → Aéroport', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/transfer-card.php'; ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_last):
                            $hotel_checkout = !empty($day['hotel_checkout']);
                            $show_inbound = $has_day_flights_return;
                        ?>
                            <?php if (!empty($day_hotels_list) && $hotel_checkout): ?>
                            <div class="ajtb-block-mmt ajtb-block-hotel ajtb-tab-block" data-ajtb-tab="hotels">
                                <h4 class="ajtb-block-title"><span class="ajtb-block-icon ajtb-block-icon--hotel" aria-hidden="true"></span> <?php echo count($day_hotels_list) > 1 ? esc_html__('Hôtels (check-out)', 'ajinsafro-tour-bridge') : esc_html__('Hôtel (check-out)', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-card-wrap">
                                    <?php foreach ($day_hotels_list as $day_hotel_last): ?>
                                    <div class="ajtb-card-with-image ajtb-card-full-width ajtb-card--hotel ajtb-hotel-listing">
                                        <div class="ajtb-hotel-listing__media">
                                            <div class="ajtb-hotel-main-image ajtb-card-image--hotel<?php echo !empty($day_hotel_last['image_url']) ? ' has-image' : ''; ?>"<?php if (!empty($day_hotel_last['image_url'])) { echo ' style="background-image: url(' . esc_attr($day_hotel_last['image_url']) . ')"'; } ?>></div>
                                            <div class="ajtb-hotel-thumbs">
                                                <?php
                                                $thumb_url_last = !empty($day_hotel_last['image_url']) ? $day_hotel_last['image_url'] : '';
                                                for ($i = 0; $i < 3; $i++) {
                                                    echo '<div class="ajtb-hotel-thumb' . ($thumb_url_last ? ' has-image' : '') . '"' . ($thumb_url_last ? ' style="background-image: url(' . esc_attr($thumb_url_last) . ')"' : '') . '></div>';
                                                }
                                                ?>
                                                <div class="ajtb-hotel-thumb ajtb-hotel-thumb--viewall">1+ <?php esc_html_e('View All', 'ajinsafro-tour-bridge'); ?></div>
                                            </div>
                                        </div>
                                        <div class="ajtb-card-inner ajtb-hotel-listing__body"><?php $hotel = $day_hotel_last; $is_checkout = true; include AJTB_PLUGIN_DIR . 'templates/tour/partials/hotel-card.php'; ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="ajtb-block-mmt ajtb-block-flight ajtb-tab-block" data-ajtb-tab="flights">
                                <h4 class="ajtb-block-title"><span class="ajtb-block-icon ajtb-block-icon--flight" aria-hidden="true"></span> <?php esc_html_e('Vol Retour', 'ajinsafro-tour-bridge'); ?></h4>
                                <div class="ajtb-day-flight-block ajtb-day-flight-inbound ajtb-card-wrap" data-aj-day-flight="inbound" data-aj-day-number="<?php echo $total_days; ?>">
                                    <?php if ($show_inbound): foreach ($day_flights_return as $flight): $show_remove = true; include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card.php'; endforeach; ?>
                                    <?php else: $label = __('Vol Retour non disponible', 'ajinsafro-tour-bridge'); include AJTB_PLUGIN_DIR . 'templates/tour/partials/flight-card-unavailable.php'; endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Activities container (id stable: JS replaces innerHTML after AJAX) -->
                        <?php
                        $included_count = 0;
                        if (!empty($activities)) {
                            foreach ($activities as $a) { if (!empty($a['is_included'])) $included_count++; }
                        }
                        ?>
                        <div id="aj-day-activities-<?php echo $day_id; ?>" class="ajtb-tab-block" data-ajtb-tab="activities">
                        <h4 class="ajtb-block-title"><span class="ajtb-block-icon ajtb-block-icon--activity" aria-hidden="true"></span> <?php echo $included_count !== 1 ? esc_html__('Activités', 'ajinsafro-tour-bridge') : esc_html__('Activité', 'ajinsafro-tour-bridge'); ?></h4>
                        <ul class="day-activities-list" data-day-id="<?php echo $day_id; ?>">
                            <?php
                            if (!empty($activities)): ?>
                                <?php foreach ($activities as $act): 
                                    if (empty($act['is_included'])) { continue; }
                                    $act_title = isset($act['title']) && (string) $act['title'] !== '' ? $act['title'] : '';
                                    $act_desc = isset($act['description']) && (string) $act['description'] !== '' ? $act['description'] : '';
                                    $act_id = (int) ($act['activity_id'] ?? 0);
                                    $is_mandatory = !empty($act['is_mandatory']);
                                    $show_remove = $can_toggle_activities && !$is_mandatory;
                                ?>
                                    <?php
                                    $act_price = null;
                                    if (isset($act['custom_price']) && $act['custom_price'] !== null) {
                                        $act_price = (float) $act['custom_price'];
                                    } elseif (isset($act['base_price']) && $act['base_price'] !== null) {
                                        $act_price = (float) $act['base_price'];
                                    }
                                    $act_image_url = isset($act['image_url']) ? $act['image_url'] : null;
                                    if (empty($act_image_url) && !empty($act['activity_image_id']) && function_exists('ajtb_get_attachment_image_url')) {
                                        $act_image_url = ajtb_get_attachment_image_url((int) $act['activity_image_id'], 'medium');
                                    }
                                    $act_start_time = $act['start_time'] ?? null;
                                    $act_end_time = $act['end_time'] ?? null;
                                    $day_activity_id = (int) ($act['id'] ?? 0);
                                    ?>
                                    <li class="day-activity-item day-activity-card-pro" data-activity-id="<?php echo $act_id; ?>" data-day-activity-id="<?php echo $day_activity_id; ?>" data-is-mandatory="<?php echo $is_mandatory ? '1' : '0'; ?>">
                                        <div class="day-activity-item-content">
                                            <div class="day-activity-image-wrap">
                                                <?php if ($act_image_url): ?>
                                                    <div class="day-activity-image"><img src="<?php echo esc_url($act_image_url); ?>" alt="<?php echo esc_attr($act_title !== '' ? $act_title : __('Activité', 'ajinsafro-tour-bridge')); ?>" loading="lazy"></div>
                                                <?php else: ?>
                                                    <div class="day-activity-image day-activity-image--placeholder"><span class="day-activity-image-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="40" height="40" stroke="currentColor" fill="none" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></span></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="day-activity-details">
                                                <div class="day-activity-header">
                                                    <span class="activity-title"><?php echo $act_title !== '' ? esc_html($act_title) : esc_html__('Activité', 'ajinsafro-tour-bridge'); ?></span>
                                                    <?php if ($is_mandatory): ?>
                                                        <span class="badge badge-mandatory">Obligatoire</span>
                                                    <?php endif; ?>
                                                    <?php if ($act_price !== null): ?>
                                                        <span class="activity-price"><?php echo number_format($act_price, 0, ',', ' '); ?> DH</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($act_start_time || $act_end_time): ?>
                                                    <div class="activity-time">
                                                        <?php if ($act_start_time): ?><span><?php echo esc_html($act_start_time); ?></span><?php endif; ?>
                                                        <?php if ($act_start_time && $act_end_time): ?><span> – </span><?php endif; ?>
                                                        <?php if ($act_end_time): ?><span><?php echo esc_html($act_end_time); ?></span><?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($act_desc !== ''): ?>
                                                    <div class="activity-description"><?php echo wp_kses_post($act_desc); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="day-activity-actions">
                                                <?php if ($can_toggle_activities && !$is_mandatory): ?>
                                                    <button type="button" class="ajtb-btn-edit-activity" data-day-activity-id="<?php echo $day_activity_id; ?>" data-tour-id="<?php echo $tour_id; ?>" data-day-id="<?php echo $day_id; ?>" data-activity-id="<?php echo $act_id; ?>" aria-label="<?php esc_attr_e('Modifier cette activité', 'ajinsafro-tour-bridge'); ?>">Modifier</button>
                                                    <button type="button" class="ajtb-btn-remove-activity" data-aj-action="remove" data-tour-id="<?php echo $tour_id; ?>" data-day-id="<?php echo $day_id; ?>" data-activity-id="<?php echo $act_id; ?>" aria-label="<?php esc_attr_e('Retirer cette activité', 'ajinsafro-tour-bridge'); ?>">Retirer</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($included_count === 0 && !$can_toggle_activities): ?>
                                <li class="day-activity-item day-no-activities"><?php esc_html_e('Aucune activité', 'ajinsafro-tour-bridge'); ?></li>
                            <?php endif; ?>
                            <?php /* CTA "Add to day" : minimal, bouton seul */ ?>
                            <?php if ($can_toggle_activities && $day_id > 0): ?>
                                <li class="day-activity-item day-activity-empty-card">
                                    <button type="button" class="ajtb-btn-open-activity-modal ajtb-btn-add-to-day" data-tour-id="<?php echo $tour_id; ?>" data-day-id="<?php echo $day_id; ?>">
                                        <span class="ajtb-btn-add-to-day-icon" aria-hidden="true">+</span>
                                        <?php esc_html_e('Add activity', 'ajinsafro-tour-bridge'); ?>
                                    </button>
                                </li>
                            <?php endif; ?>
                        </ul>
                        </div>

                        <!-- Day Details (Laravel: meals, accommodation) - visible in Programme tab -->
                        <?php if ($source === 'laravel'): ?>
                            <div class="day-details ajtb-tab-block" data-ajtb-tab="programme">
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

    <!-- Activity Modals -->
    <?php if ($can_toggle_activities): ?>
        <?php ajtb_get_partial('activity-modal', ['tour_id' => $tour_id]); ?>
        <?php ajtb_get_partial('activity-edit-modal'); ?>
    <?php endif; ?>
</section>
