<?php
/**
 * Search Bar - 3 horizontal blocks (Starting from / Travelling on / Rooms & Guests)
 * Design: white card, labels uppercase, separators. State: localStorage (start_from, travel_date, adults, children).
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get departure places and available dates from Laravel DB
// Note: travel_id in Laravel tables = WordPress post ID (not Laravel voyage ID)
$wp_post_id = get_the_ID();
$departure_places = [];
$available_dates = [];

// Direct database queries (no repository needed)
global $wpdb;

// Get table names (Laravel tables: wp_prefix + aj_ + table_name)
$places_table = $wpdb->prefix . 'aj_travel_departure_places';
$tour_flights_table = $wpdb->prefix . 'aj_tour_flights';
$legacy_flights_table = $wpdb->prefix . 'aj_travel_departure_flights';
$dates_table = $wpdb->prefix . 'aj_travel_dates';

try {
    $places = [];
    if ($wpdb->get_var($wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s", $places_table))) {
        $places = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$places_table} 
             WHERE travel_id = %d 
             AND is_active = 1 
             ORDER BY sort_order ASC, id ASC",
            $wp_post_id
        ), ARRAY_A);
    }

    $tour_flights_has_place_id = (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'departure_place_id'",
        $tour_flights_table
    ));
    $tour_flights_has_place_name = (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'departure_place_name'",
        $tour_flights_table
    ));
    $tour_flights_has_flight_type = (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'flight_type'",
        $tour_flights_table
    ));
    $tour_flights_has_segment_number = (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'segment_number'",
        $tour_flights_table
    ));
    $outbound_conditions = [];
    if ($tour_flights_has_flight_type) {
        $outbound_conditions[] = "LOWER(TRIM(COALESCE(flight_type, ''))) IN ('outbound', 'aller', 'departure')";
    }
    if ($tour_flights_has_segment_number) {
        $outbound_conditions[] = "segment_number = 1";
    }
    $outbound_sql = !empty($outbound_conditions)
        ? ' AND (' . implode(' OR ', $outbound_conditions) . ')'
        : '';

    if ($places) {
        foreach ($places as &$place) {
            $flights = [];
            if ($tour_flights_has_place_id) {
                $flights = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, from_city, to_city, flight_type, segment_number, flight_number, depart_date, depart_time, arrive_date, arrive_time 
                     FROM {$tour_flights_table} 
                     WHERE tour_id = %d AND departure_place_id = %d 
                     {$outbound_sql}
                     ORDER BY sort_order ASC, id ASC",
                    $wp_post_id,
                    $place['id']
                ), ARRAY_A);
            }
            if (empty($flights) && $wpdb->get_var($wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s", $legacy_flights_table))) {
                $flights = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$legacy_flights_table} 
                     WHERE departure_place_id = %d 
                     ORDER BY sort_order ASC, id ASC",
                    $place['id']
                ), ARRAY_A);
            }
            if (!empty($flights)) {
                $place['flights'] = $flights;
                $departure_places[] = $place;
            }
        }
    }

    // Always merge "Starting from" from backend flights section (aj_tour_flights).
    // This is the source of truth for the Front "Starting from" select.
    if ($tour_flights_has_place_name && $wpdb->get_var($wpdb->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s", $tour_flights_table))) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT departure_place_name, departure_place_code 
             FROM {$tour_flights_table} 
             WHERE tour_id = %d 
             AND departure_place_name IS NOT NULL 
             AND TRIM(COALESCE(departure_place_name, '')) != '' 
             ORDER BY departure_place_name ASC, departure_place_code ASC",
            $wp_post_id
        ), ARRAY_A);
        foreach ($rows as $row) {
            $name = trim($row['departure_place_name']);
            $code = isset($row['departure_place_code']) ? trim($row['departure_place_code']) : '';
            $place_id = $code !== '' ? $name . '|' . $code : $name;
            $flights_raw = $wpdb->get_results($wpdb->prepare(
                "SELECT id, from_city, to_city, flight_type, segment_number, flight_number, depart_date, depart_time, arrive_date, arrive_time 
                 FROM {$tour_flights_table} 
                 WHERE tour_id = %d AND TRIM(COALESCE(departure_place_name,'')) = %s 
                 AND (COALESCE(departure_place_code,'') = %s OR (%s = '' AND (departure_place_code IS NULL OR TRIM(COALESCE(departure_place_code,'')) = '')))
                 {$outbound_sql}
                 ORDER BY sort_order ASC, id ASC",
                $wp_post_id,
                $name,
                $code,
                $code
            ), ARRAY_A);
            // Format attendu par le JS : from_airport/to_airport ou from_city/to_city, flight_number, depart_time, arrive_time
            $flights = [];
            foreach ($flights_raw ?: [] as $f) {
                $flights[] = [
                    'id' => isset($f['id']) ? $f['id'] : null,
                    'from_city' => isset($f['from_city']) ? $f['from_city'] : '',
                    'to_city' => isset($f['to_city']) ? $f['to_city'] : '',
                    'from_airport' => isset($f['from_city']) ? $f['from_city'] : '',
                    'to_airport' => isset($f['to_city']) ? $f['to_city'] : '',
                    'flight_number' => isset($f['flight_number']) ? $f['flight_number'] : '',
                    'depart_date' => isset($f['depart_date']) ? $f['depart_date'] : '',
                    'depart_time' => isset($f['depart_time']) ? $f['depart_time'] : '',
                    'arrive_date' => isset($f['arrive_date']) ? $f['arrive_date'] : '',
                    'arrive_time' => isset($f['arrive_time']) ? $f['arrive_time'] : '',
                ];
            }
            $departure_places[] = [
                'id' => $place_id,
                'name' => $name,
                'code' => $code,
                'flights' => $flights,
            ];
        }

        // Remove duplicates and prefer entries coming from backend flights.
        if (!empty($departure_places)) {
            $deduped_places = [];
            foreach ($departure_places as $place) {
                $place_name = isset($place['name']) ? trim((string) $place['name']) : '';
                $place_code = isset($place['code']) ? trim((string) $place['code']) : '';
                $place_id = isset($place['id']) ? trim((string) $place['id']) : '';
                $key = $place_code !== ''
                    ? strtolower($place_name . '|' . $place_code)
                    : strtolower($place_name !== '' ? $place_name : $place_id);
                if ($key === '') {
                    continue;
                }
                $has_backend_name = $place_name !== '';
                if (!isset($deduped_places[$key])) {
                    $deduped_places[$key] = $place;
                    continue;
                }
                $existing_name = isset($deduped_places[$key]['name']) ? trim((string) $deduped_places[$key]['name']) : '';
                if ($has_backend_name && $existing_name === '') {
                    $deduped_places[$key] = $place;
                }
            }
            $departure_places = array_values($deduped_places);
        }
    }
    
    // Get available travel dates
    $dates = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$dates_table} 
         WHERE travel_id = %d 
         AND is_active = 1 
         ORDER BY date ASC",
        $wp_post_id
    ), ARRAY_A);
    
    if ($dates) {
        $available_dates = array_map(function($dateRow) {
            return $dateRow['date'];
        }, $dates);
    }
} catch (\Exception $e) {
    // Silently fail if tables don't exist
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[AJTB Searchbar] Error loading departure places/dates: ' . $e->getMessage());
    }
    $departure_places = [];
    $available_dates = [];
}

$storage_key = 'aj_tb_search';
$saved = [];
if (!empty($_COOKIE[ $storage_key ])) {
    $decoded = json_decode(stripslashes($_COOKIE[ $storage_key ]), true);
    if (is_array($decoded)) {
        $saved = $decoded;
    }
}

// Build departure cities select options
$departure_cities = [];
if (!empty($departure_places)) {
    foreach ($departure_places as $place) {
        $departure_cities[$place['id']] = $place['name'];
    }
} else {
    // Fallback to static cities if no data configured
    $departure_cities = [
        '' => __('Choisir', 'ajinsafro-tour-bridge'),
        'Casablanca' => 'Casablanca',
        'Rabat' => 'Rabat',
        'Tanger' => 'Tanger',
        'Marrakech' => 'Marrakech',
    ];
}

$start_from = isset($saved['start_from']) ? $saved['start_from'] : (isset($saved['starting_from']) ? $saved['starting_from'] : '');
if ($start_from === '' && !empty($tour['address'])) {
    $start_from = $tour['address'];
}
// Only keep start_from if it exists in current backend places; otherwise default to first place so JS doesn't restore an obsolete value
if ($start_from !== '' && !isset($departure_cities[ $start_from ])) {
    $start_from = '';
}
if ($start_from === '' && !empty($departure_cities)) {
    $place_ids = array_keys($departure_cities);
    $start_from = $place_ids[0];
}

$travel_date = isset($saved['travel_date']) ? $saved['travel_date'] : (isset($saved['travelling_on']) ? $saved['travelling_on'] : '');
$travel_date_display = $travel_date;
if ($travel_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $travel_date)) {
    $dt = new DateTime($travel_date);
    $travel_date_display = $dt->format('d/m/Y');
}

// If available dates exist, default to first date if none selected
$first_available_date = '';
if (!empty($available_dates)) {
    $first_available_date = $available_dates[0];
    if ($travel_date === '' && $first_available_date !== '') {
        $travel_date = $first_available_date;
        $travel_date_display = (new DateTime($first_available_date))->format('d/m/Y');
    }
}

$travel_date_placeholder = !empty($available_dates) 
    ? __('Choisir une date', 'ajinsafro-tour-bridge') 
    : __('No dates available', 'ajinsafro-tour-bridge');

$adults = isset($saved['adults']) ? max(1, (int) $saved['adults']) : 2;
$children = isset($saved['children']) ? max(0, (int) $saved['children']) : 0;
$max_people = !empty($tour['max_people']) ? (int) $tour['max_people'] : 20;
$max_adults = $max_people;
$max_children = 10;

// Prepare JSON data for JavaScript
$departure_places_json = wp_json_encode($departure_places);
$available_dates_json = wp_json_encode($available_dates);
$in_top_bar = !empty($in_top_bar);
?>

<?php if ($in_top_bar): ?><div class="ajtb-top-search-bar__row"><?php endif; ?>
<div class="aj-searchbar" id="aj-searchbar" 
     data-tour-id="<?php echo esc_attr($tour['id'] ?? ''); ?>" 
     data-max-adults="<?php echo (int) $max_adults; ?>" 
     data-max-children="<?php echo (int) $max_children; ?>"
     data-departure-places="<?php echo esc_attr($departure_places_json); ?>"
     data-available-dates="<?php echo esc_attr($available_dates_json); ?>">
    <div class="aj-searchbar__row">
        <!-- 1. Starting from -->
        <div class="aj-searchitem aj-searchitem--from">
            <span class="aj-search-label"><?php esc_html_e('Starting from', 'ajinsafro-tour-bridge'); ?></span>
            <div class="aj-search-value-wrap">
                <svg class="aj-search-icon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <?php
                $select_places = $departure_places;
                if (!empty($select_places)): ?>
                    <select class="aj-search-select" id="aj-search-from" data-aj-search="from" aria-label="<?php esc_attr_e('Ville de départ', 'ajinsafro-tour-bridge'); ?>">
                        <option value=""><?php esc_html_e('Choisir', 'ajinsafro-tour-bridge'); ?></option>
                        <?php foreach ($select_places as $place): ?>
                            <?php
                            $place_name = isset($place['name']) ? (string) $place['name'] : '';
                            $place_id = isset($place['id']) ? (string) $place['id'] : '';
                            $place_code = isset($place['code']) ? (string) $place['code'] : '';
                            $opt_value = $place_id !== '' ? $place_id : $place_name;
                            $is_selected = ($start_from === $place_name || $start_from === $place_id);
                            ?>
                            <option value="<?php echo esc_attr($opt_value); ?>" data-id="<?php echo esc_attr($place_id); ?>" data-code="<?php echo esc_attr($place_code); ?>"<?php if ($is_selected) echo ' selected="selected"'; ?>>
                                <?php echo esc_html($place_name !== '' ? $place_name : $place_id); ?>
                                <?php if ($place_code !== ''): ?>
                                    (<?php echo esc_html($place_code); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <select class="aj-search-select" id="aj-search-from" data-aj-search="from" disabled aria-label="<?php esc_attr_e('Ville de départ', 'ajinsafro-tour-bridge'); ?>">
                        <option value=""><?php esc_html_e('No departure places configured', 'ajinsafro-tour-bridge'); ?></option>
                    </select>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Travelling on -->
        <div class="aj-searchitem aj-searchitem--date">
            <span class="aj-search-label"><?php esc_html_e('Travelling on', 'ajinsafro-tour-bridge'); ?></span>
            <div class="aj-search-value-wrap aj-search-date-wrap">
                <svg class="aj-search-icon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <?php if (!empty($available_dates)): ?>
                    <input type="hidden" id="aj-search-date" class="aj-search-date-input" value="<?php echo esc_attr($travel_date); ?>" data-aj-search="date">
                    <span class="aj-search-value aj-search-date-trigger aj-search-date-value" id="aj-search-date-display" data-placeholder="<?php echo esc_attr($travel_date_placeholder); ?>"><?php echo $travel_date_display ? esc_html($travel_date_display) : esc_html($travel_date_placeholder); ?></span>
                    <svg class="aj-search-date-chevron" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2" aria-hidden="true"><polyline points="6,9 12,15 18,9"></polyline></svg>
                <?php else: ?>
                    <span class="aj-search-value aj-search-value--disabled"><?php esc_html_e('No dates available', 'ajinsafro-tour-bridge'); ?></span>
                <?php endif; ?>
            </div>
            <div class="aj-date-popover" id="aj-date-popover" hidden></div>
        </div>

        <!-- 3. Rooms & Guests -->
        <div class="aj-searchitem aj-searchitem--guests">
            <span class="aj-search-label"><?php esc_html_e('Rooms & Guests', 'ajinsafro-tour-bridge'); ?></span>
            <button type="button" class="aj-guest-trigger" id="aj-guest-trigger" data-aj-search="guests-trigger" aria-expanded="false" aria-haspopup="true">
                <svg class="aj-search-icon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span class="aj-guest-summary" id="aj-guest-summary"><?php echo (int) $adults; ?> <?php echo (int) $adults === 1 ? __('Adulte', 'ajinsafro-tour-bridge') : __('Adultes', 'ajinsafro-tour-bridge'); ?><?php if ($children > 0): ?>, <?php echo (int) $children; ?> <?php echo $children === 1 ? __('Enfant', 'ajinsafro-tour-bridge') : __('Enfants', 'ajinsafro-tour-bridge'); ?><?php endif; ?></span>
                <svg class="aj-guest-chevron" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2"><polyline points="6,9 12,15 18,9"></polyline></svg>
            </button>
            <div class="aj-guests-panel" id="aj-guests-panel" role="dialog" aria-label="<?php esc_attr_e('Voyageurs', 'ajinsafro-tour-bridge'); ?>" hidden>
                <div class="aj-guests-row">
                    <div class="aj-guests-label">
                        <span><?php esc_html_e('Adultes', 'ajinsafro-tour-bridge'); ?></span>
                        <small><?php esc_html_e('Above 12 years', 'ajinsafro-tour-bridge'); ?></small>
                    </div>
                    <div class="aj-counter" data-aj-search="counter" data-target="adults" data-min="1" data-max="<?php echo (int) $max_adults; ?>">
                        <button type="button" class="aj-counter-btn aj-counter-minus" data-aj-search="minus" aria-label="<?php esc_attr_e('Moins', 'ajinsafro-tour-bridge'); ?>">−</button>
                        <span class="aj-counter-num" id="aj-panel-adults"><?php echo (int) $adults; ?></span>
                        <button type="button" class="aj-counter-btn aj-counter-plus" data-aj-search="plus" aria-label="<?php esc_attr_e('Plus', 'ajinsafro-tour-bridge'); ?>">+</button>
                    </div>
                </div>
                <div class="aj-guests-row">
                    <div class="aj-guests-label">
                        <span><?php esc_html_e('Enfants', 'ajinsafro-tour-bridge'); ?></span>
                        <small><?php esc_html_e('Below 12 years', 'ajinsafro-tour-bridge'); ?></small>
                    </div>
                    <div class="aj-counter" data-aj-search="counter" data-target="children" data-min="0" data-max="<?php echo (int) $max_children; ?>">
                        <button type="button" class="aj-counter-btn aj-counter-minus" data-aj-search="minus" aria-label="<?php esc_attr_e('Moins', 'ajinsafro-tour-bridge'); ?>">−</button>
                        <span class="aj-counter-num" id="aj-panel-children"><?php echo (int) $children; ?></span>
                        <button type="button" class="aj-counter-btn aj-counter-plus" data-aj-search="plus" aria-label="<?php esc_attr_e('Plus', 'ajinsafro-tour-bridge'); ?>">+</button>
                    </div>
                </div>
                <button type="button" class="aj-guests-apply" id="aj-guests-apply" data-aj-search="guests-apply"><?php esc_html_e('Apply', 'ajinsafro-tour-bridge'); ?></button>
            </div>
        </div>
    </div>
</div>
<?php if ($in_top_bar): ?></div><!-- close ajtb-top-search-bar__row --><?php endif; ?>
<?php if (!empty($departure_places) && !$in_top_bar): ?>
<!-- Flight Details Section (Appears when departure place is selected) -->
<div class="aj-flight-details" id="aj-flight-details" style="display: none;">
    <div class="aj-flight-details__inner">
        <h4 class="aj-flight-title">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="7.5,4.21 12,6.81 16.5,4.21"></polyline>
                <line x1="12" y1="22" x2="12" y2="7"></line>
            </svg>
            <span id="aj-flight-place-name"></span>
        </h4>
        <div id="aj-flight-cards-container" class="aj-flight-cards"></div>
    </div>
</div>
<?php endif; ?>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // Get data from attributes
    var searchbar = document.getElementById('aj-searchbar');
    if (!searchbar) {
        console.log('[AJTB] Searchbar element not found');
        return;
    }
    
    var departurePlacesData = JSON.parse(searchbar.getAttribute('data-departure-places') || '[]');
    var availableDatesData = JSON.parse(searchbar.getAttribute('data-available-dates') || '[]');
    
    console.log('[AJTB] Available dates:', availableDatesData);
    
    var fromSelect = document.getElementById('aj-search-from');
    var dateInput = document.getElementById('aj-search-date');
    var flightDetailsSection = document.getElementById('aj-flight-details');
    var flightCardsContainer = document.getElementById('aj-flight-cards-container');
    var flightPlaceName = document.getElementById('aj-flight-place-name');
    
    // Handle departure place selection
    if (fromSelect && flightDetailsSection) {
        fromSelect.addEventListener('change', function() {
            var placeId = this.value;
            
            if (!placeId) {
                flightDetailsSection.style.display = 'none';
                return;
            }
            
            // Find selected place
            var selectedPlace = departurePlacesData.find(function(place) {
                return place.id == placeId;
            });
            
            if (!selectedPlace || !selectedPlace.flights || selectedPlace.flights.length === 0) {
                flightDetailsSection.style.display = 'none';
                return;
            }
            
            // Update place name
            if (flightPlaceName) {
                flightPlaceName.textContent = 'Vols depuis ' + selectedPlace.name;
            }
            
            // Build a single flight card for the selected departure place
            var html = '';
            var selectedFlight = null;
            selectedPlace.flights.some(function(flight, index) {
                var from = flight.from_airport || flight.from_city || '';
                var to = flight.to_airport || flight.to_city || '';
                if (!from && !to && !flight.flight_number) return false;
                if (index === 0) {
                    console.log('[AJTB] selectedPlace.flights[0]:', flight);
                }
                selectedFlight = flight;
                return true;
            });

            if (selectedFlight) {
                var from = selectedFlight.from_airport || selectedFlight.from_city || '';
                var to = selectedFlight.to_airport || selectedFlight.to_city || '';
                var cardPlaceId = (selectedFlight.departure_place_id != null && String(selectedFlight.departure_place_id).trim() !== '') ? String(selectedFlight.departure_place_id).trim() : (selectedPlace.id != null ? String(selectedPlace.id).trim() : '');
                var cardPlaceName = (selectedFlight.departure_place_name != null && String(selectedFlight.departure_place_name).trim() !== '') ? String(selectedFlight.departure_place_name).trim() : (selectedPlace.name != null ? String(selectedPlace.name).trim() : '');
                var cardPlaceCode = (selectedFlight.departure_place_code != null && String(selectedFlight.departure_place_code).trim() !== '') ? String(selectedFlight.departure_place_code).trim() : (selectedPlace.code != null ? String(selectedPlace.code).trim() : '');
                html += '<div class="aj-flight-card" data-departure-place-id="' + escapeHtml(cardPlaceId) + '" data-departure-place-name="' + escapeHtml(cardPlaceName) + '" data-departure-place-code="' + escapeHtml(cardPlaceCode) + '">';
                html += '<div class="aj-flight-card__row">';
                if (selectedFlight.airline) {
                    html += '<div class="aj-flight-info"><span class="aj-flight-label">Compagnie:</span> <strong>' + escapeHtml(selectedFlight.airline) + '</strong></div>';
                }
                if (selectedFlight.flight_number) {
                    html += '<div class="aj-flight-info"><span class="aj-flight-label">Vol:</span> <strong>' + escapeHtml(selectedFlight.flight_number) + '</strong></div>';
                }
                html += '</div>';
                html += '<div class="aj-flight-card__row">';
                if (from) {
                    html += '<div class="aj-flight-info"><span class="aj-flight-label">Départ:</span> ' + escapeHtml(from);
                    if (selectedFlight.depart_time) html += ' à <strong>' + escapeHtml(selectedFlight.depart_time) + '</strong>';
                    html += '</div>';
                }
                if (to) {
                    html += '<div class="aj-flight-info"><span class="aj-flight-label">Arrivée:</span> ' + escapeHtml(to);
                    if (selectedFlight.arrive_time) html += ' à <strong>' + escapeHtml(selectedFlight.arrive_time) + '</strong>';
                    html += '</div>';
                }
                html += '</div>';
                if (selectedFlight.notes) {
                    html += '<div class="aj-flight-card__notes">' + escapeHtml(selectedFlight.notes) + '</div>';
                }
                html += '</div>';
            }
            if (flightCardsContainer) {
                flightCardsContainer.innerHTML = html;
            }
            // Show section only if we have at least one card
            flightDetailsSection.style.display = html ? 'block' : 'none';

            // Programme: show only outbound flight(s) from the selected city (Jour 1)
            filterProgrammeOutboundFlightsByPlace(placeId);
        });

        // Filter programme outbound flight cards by selected "Starting from" place
        function filterProgrammeOutboundFlightsByPlace(placeId) {
            var cards = document.querySelectorAll('.ajtb-day-flight-block.ajtb-day-flight-outbound[data-aj-day-number="1"] .aj-flight-card[data-departure-place-name], .ajtb-day-flight-block.ajtb-day-flight-outbound[data-aj-day-number="1"] .aj-flight-card[data-departure-place-id]');
            if (!cards.length) return;
            var isNumeric = placeId !== '' && /^\d+$/.test(String(placeId));
            var firstMatchShown = false;
            cards.forEach(function(card) {
                var show = false;
                if (!placeId) {
                    show = true;
                } else if (isNumeric) {
                    var pid = card.getAttribute('data-departure-place-id');
                    show = pid !== null && pid !== '' && String(pid) === String(placeId);
                } else {
                    var name = (card.getAttribute('data-departure-place-name') || '').trim();
                    var code = (card.getAttribute('data-departure-place-code') || '').trim();
                    var placeKey = code ? name + '|' + code : name;
                    show = (name === placeId) || (placeKey === placeId);
                }
                if (placeId && show) {
                    if (firstMatchShown) {
                        show = false;
                    } else {
                        firstMatchShown = true;
                    }
                }
                card.style.display = show ? '' : 'none';
            });
        }

        // Trigger on page load if already selected
        if (fromSelect.value) {
            fromSelect.dispatchEvent(new Event('change'));
        } else {
            filterProgrammeOutboundFlightsByPlace('');
        }

        // Sync with booking form
        fromSelect.addEventListener('change', function() {
            var bookingDepartureInput = document.getElementById('booking-departure-place');
            if (bookingDepartureInput) {
                bookingDepartureInput.value = this.value;
            }
        });
    }
    
    // Date popover (dropdown like Rooms & Guests)
    var dateDisplay = document.getElementById('aj-search-date-display');
    var hiddenDateInput = document.getElementById('aj-search-date');
    var datePopover = document.getElementById('aj-date-popover');
    if (dateDisplay && hiddenDateInput && datePopover && availableDatesData.length > 0) {
        function initDatepickerPopover() {
            if (typeof $.fn.datepicker !== 'function') return;
            
            // Create input inside popover
            var popoverInput = document.createElement('input');
            popoverInput.type = 'text';
            popoverInput.id = 'aj-datepicker-input';
            popoverInput.setAttribute('tabindex', '-1');
            popoverInput.style.cssText = 'position:absolute;left:-9999px;width:1px;height:1px;opacity:0;pointer-events:none;';
            datePopover.appendChild(popoverInput);

            var mois = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
            var moisCourt = ['Janv.','Févr.','Mars','Avr.','Mai','Juin','Juil.','Août','Sept.','Oct.','Nov.','Déc.'];
            var jours = ['Di','Lu','Ma','Me','Je','Ve','Sa'];

            $(popoverInput).datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                firstDay: 1,
                showOtherMonths: true,
                monthNames: mois,
                monthNamesShort: moisCourt,
                dayNamesMin: jours,
                prevText: '‹ Préc.',
                nextText: 'Suiv. ›',
                showMonthAfterYear: false,
                beforeShow: function(input, inst) {
                    // Force le datepicker à apparaître dans notre popover
                    setTimeout(function() {
                        if (inst.dpDiv && inst.dpDiv.length) {
                            datePopover.appendChild(inst.dpDiv[0]);
                            // Reset position pour annuler jQuery UI positioning
                            inst.dpDiv.css({
                                position: 'static',
                                left: '0',
                                top: '0',
                                transform: 'none'
                            });
                        }
                    }, 1);
                },
                beforeShowDay: function(date) {
                    var dateString = $.datepicker.formatDate('yy-mm-dd', date);
                    var isAvailable = availableDatesData.indexOf(dateString) !== -1;
                    return [isAvailable, isAvailable ? 'aj-available-date' : 'aj-unavailable-date', isAvailable ? '' : 'Date non disponible'];
                },
                onSelect: function(dateText) {
                    hiddenDateInput.value = dateText;
                    var dateObj = new Date(dateText + 'T00:00:00');
                    dateDisplay.textContent = dateObj.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                    var bookingDateInput = document.getElementById('booking-date');
                    if (bookingDateInput) bookingDateInput.value = dateText;
                    closeDatePopover();
                }
            });

            function openDatePopover() {
                datePopover.removeAttribute('hidden');
                if (hiddenDateInput.value && availableDatesData.indexOf(hiddenDateInput.value) !== -1) {
                    $(popoverInput).datepicker('setDate', hiddenDateInput.value);
                } else if (availableDatesData.length > 0) {
                    $(popoverInput).datepicker('setDate', availableDatesData[0]);
                }
                $(popoverInput).datepicker('show');
            }

            function closeDatePopover() {
                $(popoverInput).datepicker('hide');
                datePopover.setAttribute('hidden', '');
            }

            // Click handlers
            $(dateDisplay).on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openDatePopover();
            });
            
            var dateWrap = dateDisplay.closest('.aj-search-date-wrap');
            if (dateWrap) {
                $(dateWrap).on('click', function(e) {
                    if ($(e.target).closest('svg').length) return;
                    e.preventDefault();
                    e.stopPropagation();
                    openDatePopover();
                });
            }

            // Keep popover open when clicking inside
            $(datePopover).on('click', function(e) { 
                e.stopPropagation(); 
            });
            
            // Keep popover open when clicking on datepicker elements
            $(document).on('click', '.ui-datepicker, .ui-datepicker *, .aj-date-popover, .aj-date-popover *', function(e) {
                e.stopPropagation();
            });
            
            // Close when clicking outside
            $(document).on('click.ajdatepopover', function(e) {
                if ($(e.target).closest('#aj-date-popover, .aj-search-date-wrap, .ui-datepicker').length) return;
                closeDatePopover();
            });
        }

        // Initialize with retry logic
        var initAttempts = 0;
        function tryInit() {
            initAttempts++;
            if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined' && typeof $.fn.datepicker === 'function') {
                initDatepickerPopover();
            } else if (initAttempts < 10) {
                setTimeout(tryInit, 200);
            }
        }
        setTimeout(tryInit, 100);
    } else if (dateDisplay && availableDatesData.length === 0) {
        dateDisplay.style.cursor = 'not-allowed';
        dateDisplay.style.opacity = '0.6';
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>

<style>
/* Travelling on: popover ancré au champ (dropdown comme Rooms & Guests) */
.aj-searchitem--date {
    position: relative;
}
.aj-date-popover {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    z-index: 99999;
}
.aj-date-popover[hidden] {
    display: none !important;
}

/* Forcer le calendrier dans le popover - position static pour annuler jQuery UI */
.aj-date-popover .ui-datepicker {
    position: static !important;
    top: auto !important;
    left: auto !important;
    transform: none !important;
    margin: 0 !important;
}
@media (max-width: 992px) {
    .aj-date-popover {
        left: 0;
        right: 0;
        max-width: 100%;
    }
}

.ui-datepicker {
    scroll-margin: 0 !important;
}
.ui-datepicker * {
    scroll-margin: 0 !important;
}

.aj-flight-details {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.aj-flight-details__inner {
    max-width: none;
}

.aj-flight-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
}

.aj-flight-title svg {
    flex-shrink: 0;
}

.aj-flight-cards {
    display: grid;
    gap: 1rem;
}

.aj-flight-card {
    padding: 1rem;
    background: white;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}

.aj-flight-card__row {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 0.75rem;
}

.aj-flight-card__row:last-of-type {
    margin-bottom: 0;
}

.aj-flight-info {
    flex: 1;
    min-width: 200px;
}

.aj-flight-label {
    color: #6c757d;
    font-size: 0.875rem;
}

.aj-flight-card__notes {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: #e7f3ff;
    border-left: 3px solid #0066cc;
    font-size: 0.875rem;
    color: #495057;
}

.aj-search-value--disabled {
    color: #6c757d;
    font-style: italic;
}

.aj-search-date-wrap {
    cursor: pointer;
}

.aj-search-date-value {
    flex: 1;
    min-width: 0;
    pointer-events: auto;
    cursor: pointer;
    user-select: none;
}

.aj-search-date-value:hover {
    color: var(--ajtb-primary, #0066cc);
}

.aj-search-date-chevron {
    flex-shrink: 0;
    color: var(--ajtb-primary, #0066cc);
}

.aj-search-date-trigger {
    cursor: pointer;
    user-select: none;
}

.aj-search-date-trigger:hover {
    opacity: 0.8;
}

/* ========== Agenda / Datepicker – style moderne ========== */
.ui-datepicker {
    font-family: inherit;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(13, 71, 161, 0.15);
    background: #fff;
    padding: 0;
    overflow: hidden;
    z-index: 1;
    visibility: visible;
    opacity: 1;
    min-width: 320px;
}

/* En-tête : mois + Précédent / Suivant – boutons toujours bien visibles */
.ui-datepicker .ui-datepicker-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
    color: #fff;
    border: none;
    padding: 14px 16px;
    margin: 0;
    min-height: 52px;
    flex-wrap: nowrap;
    border-radius: 12px 12px 0 0;
}

.ui-datepicker .ui-datepicker-title {
    flex: 1;
    margin: 0;
    font-weight: 600;
    font-size: 1.05rem;
    letter-spacing: 0.02em;
    text-align: center;
    order: 1;
}

.ui-datepicker .ui-datepicker-prev,
.ui-datepicker .ui-datepicker-next {
    position: static !important;
    float: none !important;
    cursor: pointer;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: rgba(255,255,255,0.95) !important;
    text-decoration: none !important;
    transition: all 0.2s ease;
    flex-shrink: 0;
    white-space: nowrap;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    min-width: auto;
}

.ui-datepicker .ui-datepicker-prev {
    order: 0;
}

.ui-datepicker .ui-datepicker-next {
    order: 2;
}

.ui-datepicker .ui-datepicker-prev:hover,
.ui-datepicker .ui-datepicker-next:hover {
    background: rgba(255,255,255,0.25);
    color: #fff !important;
    border-color: rgba(255,255,255,0.4);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.ui-datepicker .ui-datepicker-prev:active,
.ui-datepicker .ui-datepicker-next:active {
    transform: translateY(0);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.ui-datepicker .ui-datepicker-prev .ui-icon,
.ui-datepicker .ui-datepicker-next .ui-icon {
    display: none;
}

.ui-datepicker .ui-datepicker-prev span,
.ui-datepicker .ui-datepicker-next span {
    display: inline-block;
    margin: 0;
    color: inherit;
    font-size: inherit;
    font-weight: inherit;
    line-height: 1;
}

/* Zone calendrier */
.ui-datepicker .ui-datepicker-calendar {
    width: 100%;
    border-collapse: separate;
    border-spacing: 5px;
    padding: 14px;
    background: #fafbfc;
}

.ui-datepicker .ui-datepicker-calendar td {
    padding: 0;
}

.ui-datepicker .ui-datepicker-calendar td a {
    text-align: center;
    padding: 11px 0;
    display: block;
    border-radius: 10px;
    transition: all 0.2s ease;
    font-weight: 500;
    font-size: 0.95rem;
    border: 2px solid transparent !important;
    box-sizing: border-box;
}

/* Jours de la semaine */
.ui-datepicker .ui-datepicker-calendar thead th {
    padding: 10px 0;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #5c6b7a;
    background: transparent;
}

/* Dates d’autres mois (gris léger) */
.ui-datepicker .ui-datepicker-calendar td.ui-datepicker-other-month .ui-state-default {
    color: #b0bec5 !important;
    background: transparent !important;
}

/* Dates non disponibles – grisées, non cliquables */
.ui-datepicker .aj-unavailable-date,
.ui-datepicker .aj-unavailable-date a {
    color: #b0bec5 !important;
    background: #f1f3f5 !important;
    cursor: not-allowed !important;
    opacity: 0.7;
    text-decoration: none !important;
    border-color: transparent !important;
}

.ui-datepicker .aj-unavailable-date:hover,
.ui-datepicker .aj-unavailable-date:hover a {
    background: #f1f3f5 !important;
    color: #b0bec5 !important;
    border-color: transparent !important;
}

/* Dates disponibles – style par défaut */
.ui-datepicker .aj-available-date,
.ui-datepicker .aj-available-date a {
    color: #1e3a5f !important;
    background: #ffffff !important;
    cursor: pointer !important;
    font-weight: 500;
    border-color: transparent !important;
}

.ui-datepicker .aj-available-date:hover,
.ui-datepicker .aj-available-date:hover a {
    background: #e3f2fd !important;
    color: #1565c0 !important;
    border-color: transparent !important;
    transform: scale(1.05);
}

/* Date sélectionnée – un seul style (bleu, pas de bordure jaune/blanche) */
.ui-datepicker .ui-state-active.aj-available-date,
.ui-datepicker .ui-state-active.aj-available-date a,
.ui-datepicker .aj-available-date.ui-state-active,
.ui-datepicker .aj-available-date.ui-state-active a {
    background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%) !important;
    color: #fff !important;
    font-weight: 700 !important;
    border: 2px solid transparent !important;
    box-shadow: 0 3px 12px rgba(21, 101, 192, 0.4);
    transform: scale(1.05);
}

/* Désactiver tout style de focus/active par défaut (bordures jaunes etc.) */
.ui-datepicker .ui-state-active,
.ui-datepicker .ui-state-active:hover,
.ui-datepicker .ui-state-active a,
.ui-datepicker .ui-state-active a:hover {
    border-color: transparent !important;
    outline: none !important;
}

/* Jours désactivés (passés) */
.ui-datepicker .ui-datepicker-calendar .ui-state-disabled {
    color: #cfd8dc !important;
    opacity: 0.5;
    cursor: not-allowed !important;
}

.ui-datepicker .ui-datepicker-calendar .ui-state-disabled a {
    background: transparent !important;
    border-color: transparent !important;
}

/* === FIX NAV BUTTONS (Préc / Suiv) === */
.ui-datepicker .ui-datepicker-header {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    flex-wrap: nowrap !important;
    gap: 12px !important;
    overflow: visible !important;
    padding-left: 14px !important;
    padding-right: 14px !important;
}

/* === FIX CLIPPING Préc/Suiv (force real button width) === */
.ui-datepicker .ui-datepicker-prev,
.ui-datepicker .ui-datepicker-next {
    position: static !important;      /* kill absolute positioning */
    top: auto !important;
    left: auto !important;
    right: auto !important;

    width: auto !important;           /* kill fixed square size */
    max-width: none !important;
    min-width: 72px !important;       /* empêche la coupe du texte */
    height: auto !important;

    overflow: visible !important;      /* stop clipping */
    box-sizing: border-box !important;
    clip: auto !important;

    margin: 0 !important;
    padding: 8px 14px !important;
    line-height: 1 !important;

    display: inline-flex !important;  /* stable alignment */
    align-items: center !important;
    justify-content: center !important;
    white-space: nowrap !important;

    text-indent: 0 !important;        /* certains thèmes cachent le texte */
}

.ui-datepicker .ui-datepicker-prev span,
.ui-datepicker .ui-datepicker-next span {
    position: static !important;
    overflow: visible !important;
    text-indent: 0 !important;
    white-space: nowrap !important;
    display: inline-block !important;
}

.ui-datepicker .ui-datepicker-title {
    flex: 1 1 auto !important;
    text-align: center !important;
    margin: 0 12px !important;
}
</style>
