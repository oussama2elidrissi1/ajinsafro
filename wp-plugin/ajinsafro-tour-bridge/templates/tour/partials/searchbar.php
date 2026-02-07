<?php
/**
 * Search Bar Partial - MakeMyTrip style bar (Starting from / Travelling on / Rooms & Guests)
 * Placed between Hero and tabs. Drives booking form; state persisted in cookie.
 *
 * @var array $tour Tour data
 * @package AjinsafroTourBridge
 */

if (!defined('ABSPATH')) {
    exit;
}

$cookie_name = 'aj_tb_search';
$saved = [];
if (!empty($_COOKIE[ $cookie_name ])) {
    $decoded = json_decode(stripslashes($_COOKIE[ $cookie_name ]), true);
    if (is_array($decoded)) {
        $saved = $decoded;
    }
}

// Starting from: cookie → meta (address / departure_city) → placeholder
$starting_from = isset($saved['starting_from']) ? $saved['starting_from'] : '';
if ($starting_from === '' && !empty($tour['address'])) {
    $starting_from = $tour['address'];
}
if ($starting_from === '') {
    $starting_from = '—';
}
$starting_from_placeholder = $starting_from === '—' ? __('Choisir', 'ajinsafro-tour-bridge') : '';

// Travelling on: cookie → first departure date (if module) → placeholder
$travelling_on = isset($saved['travelling_on']) ? $saved['travelling_on'] : '';
$travelling_on_display = $travelling_on;
if ($travelling_on !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $travelling_on)) {
    $dt = new DateTime($travelling_on);
    $travelling_on_display = $dt->format('d/m/Y');
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
if ($travelling_on === '' && $first_departure !== '') {
    $travelling_on = $first_departure;
    $travelling_on_display = (new DateTime($first_departure))->format('d/m/Y');
}
$travelling_on_placeholder = $travelling_on === '' ? __('Choisir une date', 'ajinsafro-tour-bridge') : '';

// Guests: cookie → default 2 adults, 0 children
$adults = isset($saved['adults']) ? max(1, (int) $saved['adults']) : 2;
$children = isset($saved['children']) ? max(0, (int) $saved['children']) : 0;
$max_people = !empty($tour['max_people']) ? (int) $tour['max_people'] : 20;
$max_adults = $max_people;
$max_children = 10;
?>

<div class="aj-searchbar" id="aj-searchbar" data-tour-id="<?php echo esc_attr($tour['id'] ?? ''); ?>">
    <div class="aj-searchbar__inner">
        <div class="aj-searchbar__item aj-searchbar__from">
            <span class="aj-searchbar__label"><?php esc_html_e('Starting from', 'ajinsafro-tour-bridge'); ?></span>
            <span class="aj-searchbar__value" id="aj-searchbar-from" data-placeholder="<?php echo esc_attr($starting_from_placeholder); ?>">
                <?php echo esc_html($starting_from); ?>
            </span>
            <?php if ($starting_from_placeholder): ?>
                <span class="aj-searchbar__hint"><?php echo esc_html($starting_from_placeholder); ?></span>
            <?php endif; ?>
        </div>

        <div class="aj-searchbar__item aj-searchbar__date">
            <span class="aj-searchbar__label"><?php esc_html_e('Travelling on', 'ajinsafro-tour-bridge'); ?></span>
            <div class="aj-searchbar__date-wrap">
                <input type="date"
                       id="aj-searchbar-date"
                       class="aj-searchbar__input aj-searchbar__input-date"
                       value="<?php echo esc_attr($travelling_on); ?>"
                       min="<?php echo esc_attr(date('Y-m-d')); ?>"
                       placeholder="<?php echo esc_attr($travelling_on_placeholder); ?>"
                       aria-label="<?php esc_attr_e('Date de départ', 'ajinsafro-tour-bridge'); ?>">
                <span class="aj-searchbar__value aj-searchbar__value-date" id="aj-searchbar-date-display" data-placeholder="<?php echo esc_attr($travelling_on_placeholder); ?>">
                    <?php echo $travelling_on_display ? esc_html($travelling_on_display) : esc_html($travelling_on_placeholder); ?>
                </span>
            </div>
        </div>

        <div class="aj-searchbar__item aj-searchbar__guests">
            <span class="aj-searchbar__label"><?php esc_html_e('Rooms & Guests', 'ajinsafro-tour-bridge'); ?></span>
            <div class="aj-searchbar__guests-inner">
                <div class="aj-searchbar__guest-row">
                    <span class="aj-searchbar__guest-label"><?php esc_html_e('Adultes', 'ajinsafro-tour-bridge'); ?></span>
                    <div class="aj-searchbar__qty">
                        <button type="button" class="aj-searchbar__btn aj-searchbar__btn-minus" data-target="adults" aria-label="<?php esc_attr_e('Moins', 'ajinsafro-tour-bridge'); ?>">−</button>
                        <span class="aj-searchbar__num" id="aj-searchbar-adults"><?php echo (int) $adults; ?></span>
                        <button type="button" class="aj-searchbar__btn aj-searchbar__btn-plus" data-target="adults" aria-label="<?php esc_attr_e('Plus', 'ajinsafro-tour-bridge'); ?>">+</button>
                    </div>
                </div>
                <div class="aj-searchbar__guest-row">
                    <span class="aj-searchbar__guest-label"><?php esc_html_e('Enfants', 'ajinsafro-tour-bridge'); ?></span>
                    <div class="aj-searchbar__qty">
                        <button type="button" class="aj-searchbar__btn aj-searchbar__btn-minus" data-target="children" aria-label="<?php esc_attr_e('Moins', 'ajinsafro-tour-bridge'); ?>">−</button>
                        <span class="aj-searchbar__num" id="aj-searchbar-children"><?php echo (int) $children; ?></span>
                        <button type="button" class="aj-searchbar__btn aj-searchbar__btn-plus" data-target="children" aria-label="<?php esc_attr_e('Plus', 'ajinsafro-tour-bridge'); ?>">+</button>
                    </div>
                </div>
            </div>
            <input type="hidden" id="aj-searchbar-adults-val" value="<?php echo (int) $adults; ?>" data-max="<?php echo (int) $max_adults; ?>">
            <input type="hidden" id="aj-searchbar-children-val" value="<?php echo (int) $children; ?>" data-max="<?php echo (int) $max_children; ?>">
        </div>
    </div>
</div>
