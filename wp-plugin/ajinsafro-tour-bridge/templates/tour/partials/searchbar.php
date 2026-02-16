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
// Use WordPress post ID directly (travel_id in Laravel tables = WordPress post ID)
$tour_id = get_the_ID();
$departure_places = [];
$available_dates = [];

if ($tour_id) {
    // Get service instances
    $extras_repo = new \AjinsafroBridge\Repositories\LaravelExtrasRepository();
    
    // Get departure places with flights
    $places_data = $extras_repo->getDeparturePlaces((int) $tour_id);
    if (!empty($places_data)) {
        $departure_places = $places_data;
        error_log('Departure places loaded: ' . count($departure_places) . ' places for tour ID ' . $tour_id);
    } else {
        error_log('No departure places found for tour ID ' . $tour_id);
    }
    
    // Get available travel dates
    $dates_data = $extras_repo->getAvailableDatesArray((int) $tour_id);
    if (!empty($dates_data)) {
        $available_dates = $dates_data;
        error_log('Available dates loaded: ' . count($available_dates) . ' dates for tour ID ' . $tour_id);
    } else {
        error_log('No available dates found for tour ID ' . $tour_id);
    }
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
if ($start_from !== '' && !isset($departure_cities[ $start_from ])) {
    $departure_cities[ $start_from ] = $start_from;
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
?>

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
                <?php if (!empty($departure_places)): ?>
                    <select class="aj-search-select" id="aj-search-from" data-aj-search="from" aria-label="<?php esc_attr_e('Ville de départ', 'ajinsafro-tour-bridge'); ?>">
                        <option value=""><?php esc_html_e('Choisir', 'ajinsafro-tour-bridge'); ?></option>
                        <?php foreach ($departure_places as $place): ?>
                            <option value="<?php echo esc_attr($place['id']); ?>" <?php selected($start_from, $place['id']); ?>>
                                <?php echo esc_html($place['name']); ?>
                                <?php if (!empty($place['code'])): ?>
                                    (<?php echo esc_html($place['code']); ?>)
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
                    <input type="date" id="aj-search-date" class="aj-search-date-input" value="<?php echo esc_attr($travel_date); ?>" min="<?php echo esc_attr(date('Y-m-d')); ?>" data-aj-search="date" aria-label="<?php esc_attr_e('Date', 'ajinsafro-tour-bridge'); ?>">
                    <span class="aj-search-value" id="aj-search-date-display" data-placeholder="<?php echo esc_attr($travel_date_placeholder); ?>"><?php echo $travel_date_display ? esc_html($travel_date_display) : esc_html($travel_date_placeholder); ?></span>
                <?php else: ?>
                    <span class="aj-search-value aj-search-value--disabled"><?php esc_html_e('No dates available', 'ajinsafro-tour-bridge'); ?></span>
                <?php endif; ?>
            </div>
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
<?php if (!empty($departure_places)): ?>
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

<script>
(function() {
    'use strict';
    
    // Get data from attributes
    var searchbar = document.getElementById('aj-searchbar');
    if (!searchbar) return;
    
    var departurePlacesData = JSON.parse(searchbar.getAttribute('data-departure-places') || '[]');
    var availableDatesData = JSON.parse(searchbar.getAttribute('data-available-dates') || '[]');
    
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
            
            // Build flight cards
            var html = '';
            selectedPlace.flights.forEach(function(flight) {
                html += '<div class="aj-flight-card">';
                html += '<div class="aj-flight-card__row">';
                
                if (flight.airline) {
                    html += '<div class="aj-flight-info"><span class="aj-flight-label">Compagnie:</span> <strong>' + escapeHtml(flight.airline) + '</strong></div>';
                }
                
                if (flight.flight_number) {
                    html += '<div class="aj-flight-info"><span class="aj-flight-label">Vol:</span> <strong>' + escapeHtml(flight.flight_number) + '</strong></div>';
                }
                
                html += '</div>';
                html += '<div class="aj-flight-card__row">';
                
                if (flight.from_airport) {
                    html += '<div class="aj-flight-info"><span class="aj-flight-label">Départ:</span> ' + escapeHtml(flight.from_airport);
                    if (flight.depart_time) {
                        html += ' à <strong>' + escapeHtml(flight.depart_time) + '</strong>';
                    }
                    html += '</div>';
                }
                
                if (flight.to_airport) {
                    html += '<div class="aj-flight-info"><span class="aj-flight-label">Arrivée:</span> ' + escapeHtml(flight.to_airport);
                    if (flight.arrive_time) {
                        html += ' à <strong>' + escapeHtml(flight.arrive_time) + '</strong>';
                    }
                    html += '</div>';
                }
                
                html += '</div>';
                
                if (flight.notes) {
                    html += '<div class="aj-flight-card__notes">' + escapeHtml(flight.notes) + '</div>';
                }
                
                html += '</div>';
            });
            
            if (flightCardsContainer) {
                flightCardsContainer.innerHTML = html;
            }
            
            // Show section
            flightDetailsSection.style.display = 'block';
        });
        
            // Trigger on page load if already selected
        if (fromSelect.value) {
            fromSelect.dispatchEvent(new Event('change'));
        }
        
        // Sync with booking form
        fromSelect.addEventListener('change', function() {
            var bookingDepartureInput = document.getElementById('booking-departure-place');
            if (bookingDepartureInput) {
                bookingDepartureInput.value = this.value;
            }
        });
    }
    
    // Handle date selection - restrict to available dates
    if (dateInput && availableDatesData.length > 0) {
        // Sync with booking form
        dateInput.addEventListener('change', function() {
            var bookingDateInput = document.getElementById('booking-date');
            if (bookingDateInput) {
                bookingDateInput.value = this.value;
            }
        });
        
        // Disable dates not in available list
        dateInput.addEventListener('input', function() {
            var selectedDate = this.value;
            
            if (selectedDate && availableDatesData.indexOf(selectedDate) === -1) {
                alert('Cette date n\'est pas disponible pour ce voyage. Veuillez choisir parmi les dates disponibles.');
                this.value = '';
            }
        });
        
        // For better UX, could integrate with a datepicker library that supports disabling specific dates
        // Example with beforeShowDay callback for jQuery UI datepicker (if available)
        if (typeof jQuery !== 'undefined' && jQuery.fn.datepicker) {
            jQuery(dateInput).datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                beforeShowDay: function(date) {
                    var dateString = jQuery.datepicker.formatDate('yy-mm-dd', date);
                    var isAvailable = availableDatesData.indexOf(dateString) !== -1;
                    return [isAvailable, isAvailable ? 'available-date' : 'unavailable-date', ''];
                }
            });
        }
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
})();
</script>

<style>
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
</style>
<?php endif; ?>