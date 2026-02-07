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

$storage_key = 'aj_tb_search';
$saved = [];
if (!empty($_COOKIE[ $storage_key ])) {
    $decoded = json_decode(stripslashes($_COOKIE[ $storage_key ]), true);
    if (is_array($decoded)) {
        $saved = $decoded;
    }
}

$departure_cities = [
    '' => __('Choisir', 'ajinsafro-tour-bridge'),
    'Casablanca' => 'Casablanca',
    'Rabat' => 'Rabat',
    'Tanger' => 'Tanger',
    'Marrakech' => 'Marrakech',
    'Fès' => 'Fès',
    'Agadir' => 'Agadir',
    'Oujda' => 'Oujda',
    'Meknès' => 'Meknès',
    'Tétouan' => 'Tétouan',
];
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
$first_departure = '';
if (!empty($tour['departures']) && is_array($tour['departures'])) {
    foreach ($tour['departures'] as $dep) {
        $d = isset($dep['date']) ? $dep['date'] : (isset($dep['start_date']) ? $dep['start_date'] : '');
        if ($d !== '') {
            $first_departure = $d;
            break;
        }
    }
}
if ($travel_date === '' && $first_departure !== '') {
    $travel_date = $first_departure;
    $travel_date_display = (new DateTime($first_departure))->format('d/m/Y');
}
$travel_date_placeholder = __('Choisir une date', 'ajinsafro-tour-bridge');

$adults = isset($saved['adults']) ? max(1, (int) $saved['adults']) : 2;
$children = isset($saved['children']) ? max(0, (int) $saved['children']) : 0;
$max_people = !empty($tour['max_people']) ? (int) $tour['max_people'] : 20;
$max_adults = $max_people;
$max_children = 10;
?>

<div class="aj-searchbar" id="aj-searchbar" data-tour-id="<?php echo esc_attr($tour['id'] ?? ''); ?>" data-max-adults="<?php echo (int) $max_adults; ?>" data-max-children="<?php echo (int) $max_children; ?>">
    <div class="aj-searchbar__row">
        <!-- 1. Starting from -->
        <div class="aj-searchitem aj-searchitem--from">
            <span class="aj-search-label"><?php esc_html_e('Starting from', 'ajinsafro-tour-bridge'); ?></span>
            <div class="aj-search-value-wrap">
                <svg class="aj-search-icon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <select class="aj-search-select" id="aj-search-from" data-aj-search="from" aria-label="<?php esc_attr_e('Ville de départ', 'ajinsafro-tour-bridge'); ?>">
                    <?php foreach ($departure_cities as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($start_from, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- 2. Travelling on -->
        <div class="aj-searchitem aj-searchitem--date">
            <span class="aj-search-label"><?php esc_html_e('Travelling on', 'ajinsafro-tour-bridge'); ?></span>
            <div class="aj-search-value-wrap aj-search-date-wrap">
                <svg class="aj-search-icon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <input type="date" id="aj-search-date" class="aj-search-date-input" value="<?php echo esc_attr($travel_date); ?>" min="<?php echo esc_attr(date('Y-m-d')); ?>" data-aj-search="date" aria-label="<?php esc_attr_e('Date', 'ajinsafro-tour-bridge'); ?>">
                <span class="aj-search-value" id="aj-search-date-display" data-placeholder="<?php echo esc_attr($travel_date_placeholder); ?>"><?php echo $travel_date_display ? esc_html($travel_date_display) : esc_html($travel_date_placeholder); ?></span>
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
