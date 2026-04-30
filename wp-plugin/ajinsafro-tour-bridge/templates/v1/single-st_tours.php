<?php
/**
 * Single Tour V1 Template.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('ajth_render_header_sitewide')) {
    remove_action('get_header', 'ajth_render_header_sitewide', 5);
}
if (function_exists('ajth_render_footer_sitewide')) {
    remove_action('wp_footer', 'ajth_render_footer_sitewide', 1);
}

$tour_id = (int) get_queried_object_id();
if ($tour_id <= 0) {
    $tour_id = (int) get_the_ID();
}
if ($tour_id <= 0) {
    get_template_part('404');
    return;
}

$tour_data = class_exists('AJTB_V1_Data_Provider')
    ? AJTB_V1_Data_Provider::build($tour_id)
    : [];

$tour_title = isset($tour_data['title']) ? (string) $tour_data['title'] : trim((string) get_the_title($tour_id));
if ($tour_title === '') {
    $tour_title = __('Voyage Ajinsafro', 'ajinsafro-tour-bridge');
}
$tour_title = trim(str_ireplace('Ajinsafro Signature Escape', '', $tour_title));
$tour_title = preg_replace('/\s{2,}/', ' ', (string) $tour_title) ?: $tour_title;

$asset_base = AJTB_PLUGIN_URL . 'assets/images/tour-v1/';
$img = static function (string $name) use ($asset_base): string {
    return esc_url($asset_base . ltrim($name, '/'));
};

$destination = isset($tour_data['destination']) ? (string) $tour_data['destination'] : 'Destination';
$duration_label = isset($tour_data['duration_label']) ? (string) $tour_data['duration_label'] : '5 jours / 4 nuits';
$group_size = isset($tour_data['group_size']) ? (int) $tour_data['group_size'] : 12;
$rating_label = isset($tour_data['rating']) ? (string) $tour_data['rating'] : '4.9 / 5 voyageurs';

$default_hero_image = $img('hero-main.svg');
$default_hotel_image = $img('card-hotel.svg');
$default_flight_image = $img('card-flight.svg');
$default_transfer_image = $img('card-transfer.svg');
$default_activity_image = $img('card-activity.svg');

$hero_images = [];
if (!empty($tour_data['hero']['all']) && is_array($tour_data['hero']['all'])) {
    $hero_images = array_values(array_unique(array_filter(array_map('strval', $tour_data['hero']['all']))));
} else {
    if (!empty($tour_data['hero']['main'])) {
        $hero_images[] = (string) $tour_data['hero']['main'];
    }
    if (!empty($tour_data['hero']['side']) && is_array($tour_data['hero']['side'])) {
        foreach ($tour_data['hero']['side'] as $side_img) {
            $hero_images[] = (string) $side_img;
        }
    }
}
if (!empty($hero_images)) {
    $hero_images = array_values(array_unique(array_filter(array_map('strval', $hero_images))));
}
if (empty($hero_images)) {
    $hero_images = [$default_hero_image];
}
$hero_count = count($hero_images);
$hero_gallery_class = 'ajtb-v1-gallery--count-many';
if ($hero_count <= 1) {
    $hero_gallery_class = 'ajtb-v1-gallery--count-1';
} elseif ($hero_count === 2) {
    $hero_gallery_class = 'ajtb-v1-gallery--count-2';
} elseif ($hero_count === 3) {
    $hero_gallery_class = 'ajtb-v1-gallery--count-3';
} elseif ($hero_count === 4) {
    $hero_gallery_class = 'ajtb-v1-gallery--count-4';
}

$search_departure = isset($tour_data['search']['departure_place']) ? (string) $tour_data['search']['departure_place'] : '';
$search_departure_id = isset($tour_data['search']['departure_place_id']) ? (int) $tour_data['search']['departure_place_id'] : 0;
$search_place_options = !empty($tour_data['search']['place_options']) && is_array($tour_data['search']['place_options'])
    ? $tour_data['search']['place_options']
    : [];
$search_date = isset($tour_data['search']['departure_date']) ? (string) $tour_data['search']['departure_date'] : 'Date à confirmer';
$search_guests = isset($tour_data['search']['guests']) ? (string) $tour_data['search']['guests'] : '2 Adultes';
$guest_config = isset($tour_data['search']['guest_config']) && is_array($tour_data['search']['guest_config'])
    ? $tour_data['search']['guest_config']
    : [];
$guest_adults = isset($guest_config['adults']) ? max(1, (int) $guest_config['adults']) : 2;
$guest_children = isset($guest_config['children']) ? max(0, (int) $guest_config['children']) : 0;
$guest_max_adults = isset($guest_config['max_adults']) ? max(1, (int) $guest_config['max_adults']) : max(1, (int) $group_size);
$guest_max_children = isset($guest_config['max_children']) ? max(0, (int) $guest_config['max_children']) : 8;
$guest_max_total = isset($guest_config['max_total']) ? max(1, (int) $guest_config['max_total']) : max(1, (int) $group_size);
$search_guests = $guest_adults . ' ' . ($guest_adults > 1 ? 'adultes' : 'adulte');
if ($guest_children > 0) {
    $search_guests .= ', ' . $guest_children . ' ' . ($guest_children > 1 ? 'enfants' : 'enfant');
}
$search_date_options = [];
if (!empty($tour_data['search']['date_options']) && is_array($tour_data['search']['date_options'])) {
    foreach ($tour_data['search']['date_options'] as $date_option) {
        if (!is_array($date_option)) {
            continue;
        }
        $value = isset($date_option['value']) ? trim((string) $date_option['value']) : '';
        if ($value === '') {
            continue;
        }
        $display = isset($date_option['display']) && trim((string) $date_option['display']) !== ''
            ? (string) $date_option['display']
            : (isset($date_option['label']) ? (string) $date_option['label'] : $value);
        $search_date_options[] = [
            'value' => $value,
            'display' => $display,
        ];
    }
} elseif (!empty($tour_data['search']['dates']) && is_array($tour_data['search']['dates'])) {
    foreach ($tour_data['search']['dates'] as $date_label) {
        $date_label = trim((string) $date_label);
        if ($date_label === '') {
            continue;
        }
        $search_date_options[] = [
            'value' => $date_label,
            'display' => $date_label,
        ];
    }
}
if (empty($search_date_options) && $search_date !== '') {
    $search_date_options[] = [
        'value' => $search_date,
        'display' => $search_date,
    ];
}
$selected_search_date = !empty($search_date_options) ? (string) $search_date_options[0]['value'] : '';
foreach ($search_date_options as $date_option) {
    if ((string) $date_option['value'] === $search_date || (string) $date_option['display'] === $search_date) {
        $selected_search_date = (string) $date_option['value'];
        break;
    }
}

$stats = $tour_data['stats'] ?? ['days' => 5, 'flights' => 2, 'transfers' => 2, 'hotels' => 1, 'activities' => 2];
$days = !empty($tour_data['days']) && is_array($tour_data['days']) ? $tour_data['days'] : [];

$overview_points = !empty($tour_data['overview_points']) && is_array($tour_data['overview_points'])
    ? $tour_data['overview_points']
    : [
        'Programme construit pour offrir une experience de voyage complete.',
        'Prestations confirmees selon disponibilite au moment de la reservation.',
    ];

$policy_items = !empty($tour_data['policies']) && is_array($tour_data['policies']) ? $tour_data['policies'] : [];
$inclusions = !empty($tour_data['inclusions']) && is_array($tour_data['inclusions']) ? $tour_data['inclusions'] : [];
$exclusions = !empty($tour_data['exclusions']) && is_array($tour_data['exclusions']) ? $tour_data['exclusions'] : [];
$summary_rows = !empty($tour_data['summary_rows']) && is_array($tour_data['summary_rows']) ? $tour_data['summary_rows'] : [];
$coupons = !empty($tour_data['coupons']) && is_array($tour_data['coupons']) ? $tour_data['coupons'] : [];
$best_deals = !empty($tour_data['best_deals']) && is_array($tour_data['best_deals']) ? $tour_data['best_deals'] : [];

$price_amount = $tour_data['pricing']['display_amount'] ?? '12 900';
$price_currency = $tour_data['pricing']['currency_symbol'] ?? 'MAD';
$price_adult = isset($tour_data['pricing']['adult_price']) ? (float) $tour_data['pricing']['adult_price'] : 0.0;
$price_child = isset($tour_data['pricing']['child_price']) ? (float) $tour_data['pricing']['child_price'] : 0.0;
$price_date_map = !empty($tour_data['search']['date_prices']) && is_array($tour_data['search']['date_prices'])
    ? $tour_data['search']['date_prices']
    : [];
$price_date_map_json = wp_json_encode($price_date_map);
$price_note = !empty($tour_data['pricing']['note']) ? (string) $tour_data['pricing']['note'] : 'Tarif indicatif par adulte.';
$client_activity_enabled = !empty($tour_data['session_token']) && !empty($tour_id);
$open_activities = is_array($tour_data['open_activities'] ?? null) ? $tour_data['open_activities'] : [];
$recap_url = class_exists('AJTB_Single_Tour_Page') ? AJTB_Single_Tour_Page::recap_url($tour_id) : '';

if (empty($days)) {
    $days = [
        [
            'day' => 1,
            'date_label' => 'Jour 1',
            'title' => 'Programme à venir',
            'description' => 'Les détails du programme seront visibles dès que les données itinerary sont disponibles.',
            'meals' => [],
            'activities' => [],
            'flights_out' => [],
            'flights_in' => [],
            'transfers_in' => [],
            'transfers_out' => [],
            'hotel' => null,
            'notes' => '',
        ],
    ];
}

$ajth_ready = function_exists('ajth_render_site_header') && function_exists('ajth_get_settings');
$ajth_settings = $ajth_ready ? ajth_get_settings() : [];

$pick = static function (array $row, array $keys, string $default = ''): string {
    foreach ($keys as $key) {
        if (!empty($row[$key])) {
            return trim((string) $row[$key]);
        }
    }
    return $default;
};

$safe_image = static function ($candidate, string $fallback): string {
    $candidate = trim((string) $candidate);
    if ($candidate === '') {
        return esc_url($fallback);
    }
    return esc_url($candidate);
};

$translate_ui = static function (string $text): string {
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $replacements = [
        'Ajinsafro Signature Escape' => '',
        'Best Deals For You' => 'Offres recommandees',
        'Customize my trip' => 'Personnaliser mon voyage',
        'Price total' => 'Prix total',
        'per person' => 'par personne',
        'Included' => 'Inclus',
        'View' => 'Voir',
        'Activity program' => 'Activite du programme',
        'Activity' => 'Activite',
        'Program' => 'Programme',
        'Hotels' => 'Hotels',
        'Hotel' => 'Hotel',
        'Transfers' => 'Transferts',
        'Transfer' => 'Transfert',
        'Flights' => 'Vols',
        'Flight' => 'Vol',
        'Continue' => 'Continuer',
        'Day plan' => 'Programme du jour',
        'Day' => 'Jour',
        'Tue' => 'Mar.',
        'Wed' => 'Mer.',
        'Thu' => 'Jeu.',
        'Fri' => 'Ven.',
        'Mon' => 'Lun.',
        'Sat' => 'Sam.',
        'Sun' => 'Dim.',
        'January' => 'janvier',
        'February' => 'fevrier',
        'March' => 'mars',
        'April' => 'avril',
        'May' => 'mai',
        'June' => 'juin',
        'July' => 'juillet',
        'August' => 'aout',
        'September' => 'septembre',
        'October' => 'octobre',
        'November' => 'novembre',
        'December' => 'decembre',
        'Jan' => 'janv.',
        'Feb' => 'fevr.',
        'Apr' => 'avr.',
        'Aug' => 'aout',
        'Sep' => 'sept.',
        'Oct' => 'oct.',
        'Nov' => 'nov.',
        'Dec' => 'dec.',
    ];

    $text = str_ireplace(array_keys($replacements), array_values($replacements), $text);
    return trim(preg_replace('/\s{2,}/', ' ', $text) ?: $text);
};

get_header();
?>

<div class="ajtb-v1-page" id="ajtb-v1-page">
    <div class="ajtb-v1-site-header" id="ajtb-v1-site-header">
        <?php if ($ajth_ready): ?>
            <?php ajth_render_site_header($ajth_settings); ?>
        <?php else: ?>
            <header class="ajtb-v1-fallback-header">
                <div class="ajtb-v1-container ajtb-v1-fallback-header__inner">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="ajtb-v1-brand" aria-label="Ajinsafro">
                        <span class="ajtb-v1-brand__logo">A</span>
                        <span class="ajtb-v1-brand__text">Ajinsafro</span>
                    </a>
                    <nav class="ajtb-v1-fallback-nav" aria-label="Menu principal Ajinsafro">
                        <a href="#ajtb-v1-panel-itinerary">Programme</a>
                        <a href="#ajtb-v1-summary-card">Tarifs</a>
                        <a href="#ajtb-v1-panel-policies">Conditions</a>
                    </nav>
                    <a href="#" class="ajtb-v1-lowcost-btn">Formule low cost</a>
                </div>
            </header>
        <?php endif; ?>
    </div>

    <main class="ajtb-v1-main">
        <div class="ajtb-v1-container">
            <section class="ajtb-v1-search-box" id="ajtb-v1-search-box" aria-label="Recherche premium">
                <div class="ajtb-v1-search-grid">
                    <div class="ajtb-v1-search-card">
                        <span class="ajtb-v1-search-label">Lieu de départ</span>
                        <?php if (!empty($search_place_options)): ?>
                            <span class="ajtb-v1-search-value ajtb-v1-search-value--select">
                                <select class="ajtb-v1-search-select" id="ajtb-v1-search-from" aria-label="Lieux de départ disponibles">
                                    <?php foreach ($search_place_options as $place_option): ?>
                                        <?php
                                        $place_id = isset($place_option['id']) ? (int) $place_option['id'] : 0;
                                        $place_name = isset($place_option['name']) ? trim((string) $place_option['name']) : '';
                                        $place_code = isset($place_option['code']) ? trim((string) $place_option['code']) : '';
                                        if ($place_name === '') {
                                            continue;
                                        }
                                        $is_selected = ($search_departure_id > 0 && $place_id === $search_departure_id)
                                            || ($search_departure_id <= 0 && $search_departure !== '' && $place_name === $search_departure);
                                        ?>
                                        <option value="<?php echo esc_attr((string) $place_id); ?>" data-place-name="<?php echo esc_attr($place_name); ?>" data-place-code="<?php echo esc_attr($place_code); ?>"<?php selected($is_selected, true); ?>>
                                            <?php echo esc_html($place_name); ?><?php echo $place_code !== '' ? esc_html(' (' . $place_code . ')') : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <strong aria-hidden="true">&#9662;</strong>
                            </span>
                        <?php else: ?>
                            <span class="ajtb-v1-search-value"><span class="ajtb-v1-search-text"><?php echo esc_html($search_departure !== '' ? $search_departure : 'Aucun lieu de départ configuré'); ?></span><strong aria-hidden="true">&#9662;</strong></span>
                        <?php endif; ?>
                    </div>
                    <div class="ajtb-v1-search-card">
                        <span class="ajtb-v1-search-label">Date de départ</span>
                        <?php if (!empty($search_date_options)): ?>
                            <span class="ajtb-v1-search-value ajtb-v1-search-value--select">
                                <select class="ajtb-v1-search-select" id="ajtb-v1-search-date" aria-label="Dates de départ disponibles">
                                    <?php foreach ($search_date_options as $date_option): ?>
                                        <option value="<?php echo esc_attr((string) $date_option['value']); ?>"<?php selected((string) $date_option['value'], $selected_search_date); ?>>
                                            <?php echo esc_html($translate_ui((string) $date_option['display'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <strong aria-hidden="true">&#9662;</strong>
                            </span>
                        <?php else: ?>
                            <span class="ajtb-v1-search-value"><span class="ajtb-v1-search-text"><?php echo esc_html($translate_ui($search_date)); ?></span><strong aria-hidden="true">&#9662;</strong></span>
                        <?php endif; ?>
                    </div>
                    <div class="ajtb-v1-search-card">
                        <span class="ajtb-v1-search-label">Voyageurs</span>
                        <div
                            class="ajtb-v1-guests-picker"
                            data-max-adults="<?php echo esc_attr((string) $guest_max_adults); ?>"
                            data-max-children="<?php echo esc_attr((string) $guest_max_children); ?>"
                            data-max-total="<?php echo esc_attr((string) $guest_max_total); ?>">
                            <button type="button" class="ajtb-v1-guest-trigger" id="ajtb-v1-guest-trigger" aria-expanded="false">
                                <span class="ajtb-v1-search-value">
                                    <span class="ajtb-v1-search-text" id="ajtb-v1-guest-summary"><?php echo esc_html($search_guests); ?></span>
                                    <strong aria-hidden="true">&#9662;</strong>
                                </span>
                            </button>
                            <div class="ajtb-v1-guest-popover" id="ajtb-v1-guest-popover" hidden>
                                <div class="ajtb-v1-guest-row">
                                    <div>
                                        <strong>Adultes</strong>
                                        <span>Âge 12+</span>
                                    </div>
                                    <div class="ajtb-v1-guest-stepper">
                                        <button type="button" data-ajtb-guest-action="minus" data-ajtb-guest-target="adults">-</button>
                                        <span id="ajtb-v1-guest-adults-value"><?php echo esc_html((string) $guest_adults); ?></span>
                                        <button type="button" data-ajtb-guest-action="plus" data-ajtb-guest-target="adults">+</button>
                                    </div>
                                </div>
                                <div class="ajtb-v1-guest-row">
                                    <div>
                                        <strong>Enfants</strong>
                                        <span>Âge 2-11</span>
                                    </div>
                                    <div class="ajtb-v1-guest-stepper">
                                        <button type="button" data-ajtb-guest-action="minus" data-ajtb-guest-target="children">-</button>
                                        <span id="ajtb-v1-guest-children-value"><?php echo esc_html((string) $guest_children); ?></span>
                                        <button type="button" data-ajtb-guest-action="plus" data-ajtb-guest-target="children">+</button>
                                    </div>
                                </div>
                                <button type="button" class="ajtb-v1-guest-apply" id="ajtb-v1-guest-apply">Appliquer</button>
                            </div>
                            <input type="hidden" id="ajtb-v1-guest-adults-input" value="<?php echo esc_attr((string) $guest_adults); ?>">
                            <input type="hidden" id="ajtb-v1-guest-children-input" value="<?php echo esc_attr((string) $guest_children); ?>">
                        </div>
                    </div>
                    <div class="ajtb-v1-search-card ajtb-v1-search-card--request">
                        <span class="ajtb-v1-search-label">Type de demande</span>
                        <span class="ajtb-v1-search-value ajtb-v1-search-value--select">
                            <select class="ajtb-v1-search-select" id="ajtb-v1-request-type" aria-label="Type de demande">
                                <option value="available">Départ disponible</option>
                                <option value="custom">Demande à la carte</option>
                            </select>
                            <strong aria-hidden="true">&#9662;</strong>
                        </span>
                    </div>
                </div>
                <p id="ajtb-v1-custom-message" class="ajtb-v1-request-note" hidden>Votre demande sera traitée par un conseiller Ajinsafro.</p>
            </section>

            <section class="ajtb-v1-hero" aria-label="En-tête du voyage">
                <h1 class="ajtb-v1-title"><?php echo esc_html($tour_title); ?></h1>

                <div class="ajtb-v1-gallery <?php echo esc_attr($hero_gallery_class); ?>" data-image-count="<?php echo esc_attr((string) $hero_count); ?>">
                    <?php foreach ($hero_images as $index => $gallery_img): ?>
                        <figure class="ajtb-v1-gallery-item<?php echo $index === 0 ? ' is-featured' : ''; ?>">
                            <span class="ajtb-v1-gallery-media">
                                <img src="<?php echo $safe_image($gallery_img, $default_hero_image); ?>" alt="Galerie voyage <?php echo esc_attr((string) ($index + 1)); ?>" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
                            </span>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="ajtb-v1-layout">
                <div class="ajtb-v1-main-column">
                    <section class="ajtb-v1-tab-panel is-active" id="ajtb-v1-panel-itinerary" role="tabpanel">
                        <article class="ajtb-v1-card ajtb-v1-overview-card">
                            <p class="ajtb-v1-kicker">Aperçu du voyage</p>
                            <ul class="ajtb-v1-overview-list">
                                <?php foreach ($overview_points as $point): ?>
                                    <li><?php echo esc_html($translate_ui((string) $point)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>

                        <div class="ajtb-v1-included-bar">
                            <?php
                            $chips = !empty($inclusions) ? array_slice($inclusions, 0, 6) : [];
                            foreach ($chips as $chip):
                            ?>
                                <span class="ajtb-v1-chip"><?php echo esc_html($translate_ui((string) $chip)); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="ajtb-v1-stats-grid" data-program-filters>
                            <button type="button" class="ajtb-v1-stat is-active" data-program-filter="all" aria-pressed="true"><strong><?php echo esc_html((string) (int) $stats['days']); ?></strong><span>Programme du jour</span></button>
                            <button type="button" class="ajtb-v1-stat" data-program-filter="flight" aria-pressed="false"><strong><?php echo esc_html((string) (int) $stats['flights']); ?></strong><span>Vols</span></button>
                            <button type="button" class="ajtb-v1-stat" data-program-filter="transfer" aria-pressed="false"><strong><?php echo esc_html((string) (int) $stats['transfers']); ?></strong><span>Transferts</span></button>
                            <button type="button" class="ajtb-v1-stat" data-program-filter="hotel" aria-pressed="false"><strong><?php echo esc_html((string) (int) $stats['hotels']); ?></strong><span>Hôtels</span></button>
                            <button type="button" class="ajtb-v1-stat" data-program-filter="activity" aria-pressed="false"><strong><?php echo esc_html((string) (int) $stats['activities']); ?></strong><span>Activités</span></button>
                        </div>

                        <div class="ajtb-v1-day-layout">
                            <aside class="ajtb-v1-day-nav" aria-label="Navigation du programme">
                                <?php foreach ($days as $i => $day): ?>
                                    <button
                                        type="button"
                                        class="ajtb-v1-day-chip<?php echo $i === 0 ? ' is-active' : ''; ?>"
                                        data-ajtb-day-target="ajtb-v1-day-<?php echo esc_attr((string) (int) $day['day']); ?>">
                                        <?php echo esc_html($translate_ui((string) ($day['date_label'] ?? ('Jour ' . (int) $day['day'])))); ?>
                                    </button>
                                <?php endforeach; ?>
                            </aside>

                            <div class="ajtb-v1-timeline">
                                <?php
                                $has_any_hotel = false;
                                foreach ($days as $day_probe) {
                                    if (!empty($day_probe['hotel']) && is_array($day_probe['hotel'])) {
                                        $has_any_hotel = true;
                                        break;
                                    }
                                }
                                $hotel_displayed_once = false;
                                ?>
                                <?php foreach ($days as $day_index => $day): ?>
                                    <?php
                                    $day_db_id = isset($day['day_id']) ? (int) $day['day_id'] : 0;
                                    $day_num = (int) ($day['day'] ?? 1);
                                    $activities = is_array($day['activities'] ?? null) ? $day['activities'] : [];
                                    $included_activities = [];
                                    $optional_activities = is_array($day['optional_activities'] ?? null) ? $day['optional_activities'] : [];
                                    foreach ($activities as $activity_row) {
                                        if (!empty($activity_row['is_included'])) {
                                            $included_activities[] = $activity_row;
                                        } elseif (empty($optional_activities)) {
                                            $optional_activities[] = $activity_row;
                                        }
                                    }
                                    $flights_out = is_array($day['flights_out'] ?? null) ? $day['flights_out'] : [];
                                    $flights_in = is_array($day['flights_in'] ?? null) ? $day['flights_in'] : [];
                                    $transfers_in = is_array($day['transfers_in'] ?? null) ? $day['transfers_in'] : [];
                                    $transfers_out = is_array($day['transfers_out'] ?? null) ? $day['transfers_out'] : [];
                                    $meals = is_array($day['meals'] ?? null) ? $day['meals'] : [];
                                    $hotel = !empty($day['hotel']) && is_array($day['hotel']) ? $day['hotel'] : null;
                                    $show_hotel_card = false;
                                    $optional_panel_id = 'ajtb-v1-day-options-' . $day_num;
                                    if ($has_any_hotel) {
                                        if (!$hotel_displayed_once && !empty($hotel)) {
                                            $show_hotel_card = true;
                                            $hotel_displayed_once = true;
                                        }
                                    } elseif ((int) $day_index === 0) {
                                        $show_hotel_card = true;
                                    }

                                    $included_parts = [];
                                    if (!empty($flights_out) || !empty($flights_in)) {
                                        $included_parts[] = (count($flights_out) + count($flights_in)) . ' Vol';
                                    }
                                    if ($show_hotel_card) {
                                        $included_parts[] = '1 Hôtel';
                                    }
                                    if (!empty($transfers_in) || !empty($transfers_out)) {
                                        $included_parts[] = (count($transfers_in) + count($transfers_out)) . ' Transfert';
                                    }
                                    if (!empty($included_activities)) {
                                        $included_parts[] = count($included_activities) . ' Activité';
                                    }
                                    if (!empty($meals)) {
                                        $included_parts[] = count($meals) . ' Repas';
                                    }
                                    $included_label = !empty($included_parts) ? ('Inclus : ' . implode(' - ', $included_parts)) : 'Inclus : Détails du programme';
                                    ?>

                                    <article class="ajtb-v1-day-card" id="ajtb-v1-day-<?php echo esc_attr((string) $day_num); ?>" data-program-day-card>
                                        <div class="ajtb-v1-day-box">
                                            <header class="ajtb-v1-day-head">
                                                <div class="ajtb-v1-day-head-left">
                                                    <span class="ajtb-v1-day-badge">Jour <?php echo esc_html((string) $day_num); ?></span>
                                                    <h3><?php echo esc_html($translate_ui((string) ($day['title'] ?? ('Jour ' . $day_num)))); ?></h3>
                                                    <p><?php echo esc_html($included_label); ?></p>
                                                </div>
                                            </header>
                                            <div class="ajtb-v1-day-content">
                                                <p class="ajtb-v1-day-desc" data-ajtb-expandable-text><?php echo esc_html($translate_ui((string) ($day['description'] ?? ''))); ?></p>
                                                <button type="button" class="ajtb-v1-expand-toggle" data-ajtb-expand-toggle hidden>Voir plus</button>

                                                <?php
                                                $flight_cards = array_merge($flights_out, $flights_in);
                                                foreach ($flight_cards as $flight):
                                                    $from = $pick($flight, ['depart_label', 'from_city', 'depart_city'], '---');
                                                    $to = $pick($flight, ['arrive_label', 'to_city', 'arrive_city'], '---');
                                                    $dep_time = $pick($flight, ['depart_time'], '--:--');
                                                    $arr_time = $pick($flight, ['arrive_time'], '--:--');
                                                    $dep_code = strtoupper(substr($pick($flight, ['depart_airport', 'from_city'], 'DEP'), 0, 3));
                                                    $arr_code = strtoupper(substr($pick($flight, ['arrive_airport', 'to_city'], 'ARR'), 0, 3));
                                                    $flight_img = $pick($flight, ['image_url', 'airline_logo', 'logo_url'], '');
                                                ?>
                                                    <div class="ajtb-v1-service-card ajtb-v1-service-card--flight program-item" data-program-type="flight">
                                                        <div class="ajtb-v1-service-head"><span>Vol - <?php echo esc_html($from . ' à ' . $to); ?></span><span>Confirmé</span></div>
                                                        <div class="ajtb-v1-service-body ajtb-v1-flight-grid">
                                                            <div><strong><?php echo esc_html($dep_time); ?></strong><small><?php echo esc_html($dep_code); ?></small></div>
                                                            <div class="ajtb-v1-flight-line"></div>
                                                            <div><strong><?php echo esc_html($arr_time); ?></strong><small><?php echo esc_html($arr_code); ?></small></div>
                                                            <img src="<?php echo $safe_image($flight_img, $default_flight_image); ?>" alt="Visuel vol" loading="lazy">
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <?php foreach ($transfers_in as $transfer): ?>
                                                    <?php
                                                    $from = $pick($transfer, ['from_label', 'pickup_location', 'from_city'], 'Point d\'arrivée');
                                                    $to = $pick($transfer, ['to_label', 'dropoff_location', 'to_city'], 'Hôtel');
                                                    $transfer_img = $pick($transfer, ['image_url'], '');
                                                    ?>
                                                    <div class="ajtb-v1-service-card program-item" data-program-type="transfer">
                                                        <div class="ajtb-v1-service-head"><span>Transfert - <?php echo esc_html($translate_ui($from . ' à ' . $to)); ?></span><span>Inclus</span></div>
                                                        <div class="ajtb-v1-service-body ajtb-v1-media-row">
                                                            <span class="ajtb-v1-media-thumb"><img src="<?php echo $safe_image($transfer_img, $default_transfer_image); ?>" alt="Visuel transfert" loading="lazy"></span>
                                                            <div>
                                                                <h4><?php echo esc_html($pick($transfer, ['transfer_type', 'service_name', 'name'], 'Service de transfert')); ?></h4>
                                                                <p data-ajtb-expandable-text><?php echo esc_html($translate_ui($pick($transfer, ['notes'], 'Transfert organisé avec assistance locale.'))); ?></p>
                                                                <button type="button" class="ajtb-v1-expand-toggle" data-ajtb-expand-toggle hidden>Voir plus</button>
                                                                <div class="ajtb-v1-meta-line"><span><?php echo esc_html($pick($transfer, ['vehicle_type'], 'Véhicule confortable')); ?></span><span><?php echo esc_html($pick($transfer, ['pickup_time'], 'À l\'heure')); ?></span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <?php if ($show_hotel_card): ?>
                                                    <?php
                                                    $hotel_img = $pick((array) $hotel, ['image_url'], '');
                                                    $hotel_available = !empty($hotel);
                                                    $hotel_name = $hotel_available
                                                        ? $pick((array) $hotel, ['hotel_name', 'name', 'title'], 'Hôtel')
                                                        : 'Non disponible';
                                                    $hotel_desc = $hotel_available
                                                        ? $pick((array) $hotel, ['notes', 'address'], 'Hôtel configuré dans la section Hôtel du CRUD.')
                                                        : 'Aucun hôtel configuré dans la section Hôtel du CRUD.';
                                                    $hotel_city = $hotel_available
                                                        ? $pick((array) $hotel, ['city', 'hotel_city', 'location'], $destination)
                                                        : $destination;
                                                    $hotel_room = $hotel_available
                                                        ? $pick((array) $hotel, ['room_type'], 'Chambre standard')
                                                        : 'Non disponible';
                                                    $hotel_stars = $hotel_available
                                                        ? $pick((array) $hotel, ['stars'], '4')
                                                        : '-';
                                                    ?>
                                                    <div class="ajtb-v1-service-card program-item" data-program-type="hotel">
                                                        <div class="ajtb-v1-service-head"><span>Hôtel - <?php echo esc_html($hotel_city); ?></span><span><?php echo $hotel_available ? 'Voir' : 'Non disponible'; ?></span></div>
                                                        <div class="ajtb-v1-service-body ajtb-v1-media-row">
                                                            <span class="ajtb-v1-media-thumb"><img src="<?php echo $safe_image($hotel_img, $default_hotel_image); ?>" alt="Visuel hôtel" loading="lazy"></span>
                                                            <div>
                                                                <h4><?php echo esc_html($translate_ui($hotel_name)); ?></h4>
                                                                <p data-ajtb-expandable-text><?php echo esc_html($translate_ui($hotel_desc)); ?></p>
                                                                <button type="button" class="ajtb-v1-expand-toggle" data-ajtb-expand-toggle hidden>Voir plus</button>
                                                                <div class="ajtb-v1-meta-line">
                                                                    <span><?php echo esc_html($hotel_available ? ((string) $hotel_stars . '/5') : 'Non disponible'); ?></span>
                                                                    <span><?php echo esc_html($hotel_city); ?></span>
                                                                    <span><?php echo esc_html($hotel_room); ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="ajtb-v1-day-activities" data-day-activities-list data-day-id="<?php echo esc_attr((string) $day_db_id); ?>" data-day-number="<?php echo esc_attr((string) $day_num); ?>">
                                                    <?php foreach ($included_activities as $activity): ?>
                                                        <?php
                                                        $act_img = $pick($activity, ['image_url'], '');
                                                        $act_title = $pick($activity, ['title'], 'Activité');
                                                        $act_desc = $pick($activity, ['description'], 'Activité incluse selon le programme du jour.');
                                                        $act_price = isset($activity['custom_price']) && $activity['custom_price'] !== null
                                                            ? (float) $activity['custom_price']
                                                            : (isset($activity['base_price']) && $activity['base_price'] !== null ? (float) $activity['base_price'] : null);
                                                        ?>
                                                        <div class="activity-card ajtb-v1-service-card program-item" data-program-type="activity" data-activity-id="<?php echo esc_attr((string) (int) ($activity['activity_id'] ?? 0)); ?>" data-activity-title="<?php echo esc_attr($act_title); ?>" data-activity-price="<?php echo esc_attr($act_price !== null ? (string) $act_price : ''); ?>" data-client-added="<?php echo !empty($activity['client_added']) ? '1' : '0'; ?>">
                                                            <div class="ajtb-v1-service-head">
                                                                <span>Activité du programme</span>
                                                                <?php if (!empty($activity['client_added'])): ?>
                                                                    <button type="button"
                                                                        class="ajtb-v1-service-remove"
                                                                        data-ajtb-v1-action="remove-program-activity"
                                                                        data-tour-id="<?php echo esc_attr((string) $tour_id); ?>"
                                                                        data-day-id="<?php echo esc_attr((string) $day_db_id); ?>"
                                                                        data-day-number="<?php echo esc_attr((string) $day_num); ?>"
                                                                        data-activity-id="<?php echo esc_attr((string) (int) ($activity['activity_id'] ?? 0)); ?>">
                                                                        Retirer
                                                                    </button>
                                                                <?php else: ?>
                                                                    <span>Inclus</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="ajtb-v1-service-body ajtb-v1-media-row">
                                                                <span class="ajtb-v1-media-thumb"><img src="<?php echo $safe_image($act_img, $default_activity_image); ?>" alt="Visuel activité" loading="lazy"></span>
                                                            <div>
                                                                <h4><?php echo esc_html($translate_ui($act_title)); ?></h4>
                                                                <p data-ajtb-expandable-text><?php echo esc_html($translate_ui($act_desc)); ?></p>
                                                                <button type="button" class="ajtb-v1-expand-toggle" data-ajtb-expand-toggle hidden>Voir plus</button>
                                                                <div class="ajtb-v1-meta-line">
                                                                    <?php if ($act_price !== null): ?><span><?php echo esc_html(number_format($act_price, 0, ',', ' ') . ' MAD'); ?></span><?php endif; ?>
                                                                    <?php if (!empty($activity['start_time'])): ?><span><?php echo esc_html((string) $activity['start_time']); ?></span><?php endif; ?>
                                                                        <?php if (!empty($activity['end_time'])): ?><span><?php echo esc_html((string) $activity['end_time']); ?></span><?php endif; ?>
                                                                    </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                </div>

                                                <?php
                                                // Pass all optional activities (fixed + open) to the modal via data-day-opts.
                                                // JS activityMatchesDay() filters per day; open-scope also covered by window.ajtbOpenActivities fallback.
                                                $day_fixed_optional = $optional_activities;
                                                $has_optional_cta = !empty($day_fixed_optional) || !empty($open_activities);
                                                ?>
                                                <?php if ($day_db_id > 0): ?>
                                                    <?php
                                                    $day_opts_json = wp_json_encode(array_values(array_map(function ($oa) {
                                                        $price = isset($oa['custom_price']) && $oa['custom_price'] !== null
                                                            ? (float) $oa['custom_price']
                                                            : (isset($oa['base_price']) && $oa['base_price'] !== null ? (float) $oa['base_price'] : null);
                                                        $scope = isset($oa['day_scope']) ? (string) $oa['day_scope'] : 'fixed';
                                                        return [
                                                            'activity_id' => (int) ($oa['activity_id'] ?? 0),
                                                            'title' => (string) ($oa['title'] ?? ''),
                                                            'description' => (string) ($oa['description'] ?? ''),
                                                            'image_url' => $oa['image_url'] ?? null,
                                                            'price' => $price,
                                                            'visibility' => $scope === 'open' ? 'all_days' : 'fixed',
                                                            'day_number' => (int) ($oa['day_number'] ?? $day_num),
                                                        ];
                                                    }, $day_fixed_optional)));
                                                    ?>
                                                    <div class="ajtb-v1-optional-cta-wrap">
                                                        <button type="button"
                                                            class="ajtb-v1-optional-trigger"
                                                            data-ajtb-v1-action="open-activity-modal"
                                                            data-day-id="<?php echo esc_attr((string) $day_db_id); ?>"
                                                            data-tour-id="<?php echo esc_attr((string) $tour_id); ?>"
                                                            data-day-number="<?php echo esc_attr((string) $day_num); ?>"
                                                            data-day-opts="<?php echo esc_attr($day_opts_json); ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                                            Ajouter une activité
                                                        </button>
                                                    </div>
                                                <?php endif; ?>

                                                <?php foreach ($meals as $meal): ?>
                                                    <p class="ajtb-v1-meal program-item" data-program-type="meal">Repas - <?php echo esc_html((string) $meal); ?></p>
                                                <?php endforeach; ?>

                                                <?php if (!empty($day['notes']) && trim((string) $day['notes']) !== trim((string) ($day['description'] ?? ''))): ?>
                                                    <p class="ajtb-v1-note"><?php echo esc_html($translate_ui((string) $day['notes'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>

                    <script>
                    window.ajtbOpenActivities = <?php echo wp_json_encode(array_values(array_map(function ($oa) {
                        $price = isset($oa['custom_price']) && $oa['custom_price'] !== null
                            ? (float) $oa['custom_price']
                            : (isset($oa['base_price']) && $oa['base_price'] !== null ? (float) $oa['base_price'] : null);
                        return [
                            'activity_id' => (int) ($oa['activity_id'] ?? 0),
                            'title' => (string) ($oa['title'] ?? ''),
                            'description' => (string) ($oa['description'] ?? ''),
                            'image_url' => $oa['image_url'] ?? null,
                            'price' => $price,
                            'visibility' => 'all_days',
                        ];
                    }, $open_activities))); ?>;
                    window.ajtbTourId = <?php echo (int) $tour_id; ?>;
                    </script>

                    <div id="ajtb-act-modal-overlay" class="ajtb-act-modal-overlay" hidden aria-modal="true" role="dialog" aria-label="Ajouter une activité">
                        <div class="ajtb-act-modal-drawer">
                            <div class="ajtb-act-modal-header">
                                <div>
                                    <h3 class="ajtb-act-modal-title">Ajouter une activité</h3>
                                    <p class="ajtb-act-modal-subtitle">Personnalisez votre programme en ajoutant des activités optionnelles.</p>
                                </div>
                                <button type="button" class="ajtb-act-modal-close" data-ajtb-v1-action="close-activity-modal" aria-label="Fermer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                            <div class="ajtb-act-modal-body" id="ajtb-act-modal-body">
                                <!-- populated by JS -->
                            </div>
                        </div>
                    </div>

                    <section class="ajtb-v1-tab-panel" id="ajtb-v1-panel-policies" role="tabpanel" hidden>
                        <article class="ajtb-v1-card">
                                <h2>Conditions et politiques</h2>
                                <p>Conditions importantes à connaître avant confirmation.</p>
                                <ul class="ajtb-v1-overview-list">
                                    <?php foreach ($policy_items as $policy_line): ?>
                                        <li><?php echo esc_html($translate_ui((string) $policy_line)); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (!empty($exclusions)): ?>
                                    <h3 class="ajtb-v1-subsection-title">Non inclus</h3>
                                    <ul class="ajtb-v1-overview-list">
                                        <?php foreach (array_slice($exclusions, 0, 8) as $line): ?>
                                            <li><?php echo esc_html($translate_ui((string) $line)); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                        </article>
                    </section>

                    <section class="ajtb-v1-tab-panel" id="ajtb-v1-panel-summary" role="tabpanel" hidden>
                        <article class="ajtb-v1-card">
                            <h2>Résumé</h2>
                            <div class="ajtb-v1-summary-table">
                                <?php foreach ($summary_rows as $row): ?>
                                    <div><strong><?php echo esc_html($translate_ui((string) $row['label'])); ?></strong><span><?php echo esc_html($translate_ui((string) $row['text'])); ?></span></div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    </section>
                </div>

                <aside class="ajtb-v1-sidebar">
                    <div
                        class="ajtb-v1-side-card ajtb-v1-summary-card"
                        id="ajtb-v1-summary-card"
                        data-recap-url="<?php echo esc_attr($recap_url); ?>"
                        data-tour-title="<?php echo esc_attr($tour_title); ?>"
                        data-duration-label="<?php echo esc_attr($translate_ui($duration_label)); ?>"
                        data-default-departure="<?php echo esc_attr($search_departure); ?>"
                        data-default-date="<?php echo esc_attr($translate_ui($search_date)); ?>"
                        data-default-guests="<?php echo esc_attr($search_guests); ?>"
                        data-has-flight="<?php echo esc_attr(!empty($tour_data['flights']) ? '1' : '0'); ?>"
                        data-hotel-label="<?php echo esc_attr(!empty($stats['hotels']) ? 'Inclus' : 'A confirmer'); ?>"
                        data-activity-label="<?php echo esc_attr('A confirmer'); ?>"
                        data-inclusions="<?php echo esc_attr(wp_json_encode(array_values(array_filter(array_slice($inclusions, 0, 5))))); ?>"
                        data-options="<?php echo esc_attr(wp_json_encode(array_values(array_filter(array_slice($best_deals, 0, 3))))); ?>"
                        data-availability-label="<?php echo esc_attr(!empty($search_date_options) ? 'Disponible selon la date selectionnee' : 'Sous reserve de disponibilite'); ?>"
                        data-base-adult-price="<?php echo esc_attr((string) $price_adult); ?>"
                        data-base-child-price="<?php echo esc_attr((string) $price_child); ?>"
                        data-currency="<?php echo esc_attr($price_currency); ?>"
                        data-date-prices="<?php echo esc_attr((string) $price_date_map_json); ?>"
                    >
                        <div class="ajtb-v1-summary-head">
                            <div>
                                <h3>Recapitulatif de reservation</h3>
                            </div>
                        </div>

                        <div class="ajtb-v1-summary-price-row">
                            <div>
                                <span class="ajtb-v1-summary-label">Prix total</span>
                                <p class="ajtb-v1-summary-price"><span id="ajtb-v1-price-amount"><?php echo esc_html($price_amount); ?></span> <span id="ajtb-v1-price-currency"><?php echo esc_html($price_currency); ?></span></p>
                            </div>
                            <p class="ajtb-v1-summary-unit"><span id="ajtb-v1-price-per-person"><?php echo esc_html($price_amount); ?> <?php echo esc_html($price_currency); ?></span><small>par personne</small></p>
                        </div>

                        <dl class="ajtb-v1-summary-list">
                            <div>
                                <dt>Voyage</dt>
                                <dd id="ajtb-v1-summary-tour"><?php echo esc_html($tour_title); ?></dd>
                            </div>
                            <div>
                                <dt>Depart</dt>
                                <dd id="ajtb-v1-summary-departure"><?php echo esc_html($search_departure !== '' ? $search_departure : '-'); ?></dd>
                            </div>
                            <div>
                                <dt>Date</dt>
                                <dd id="ajtb-v1-summary-date"><?php echo esc_html($translate_ui($search_date)); ?></dd>
                            </div>
                            <div>
                                <dt>Voyageurs</dt>
                                <dd id="ajtb-v1-summary-guests"><?php echo esc_html($search_guests); ?></dd>
                            </div>
                            <div>
                                <dt>Duree</dt>
                                <dd id="ajtb-v1-summary-duration"><?php echo esc_html($translate_ui($duration_label)); ?></dd>
                            </div>
                            <div>
                                <dt>Hebergement</dt>
                                <dd id="ajtb-v1-summary-hotel"><?php echo esc_html(!empty($stats['hotels']) ? 'Inclus' : 'A confirmer'); ?></dd>
                            </div>
                            <div>
                                <dt>Activites</dt>
                                <dd id="ajtb-v1-summary-activities">A confirmer</dd>
                            </div>
                        </dl>

                        <button type="button" class="ajtb-v1-summary-action" id="ajtb-v1-summary-action">Continuer</button>
                    </div>

                    <div class="ajtb-v1-side-card ajtb-v1-side-highlight">
                        <h3>Dates de depart</h3>
                        <?php if (!empty($search_date_options)): ?>
                            <ul class="ajtb-v1-departure-list">
                                <?php foreach ($search_date_options as $date_option): ?>
                                    <li><?php echo esc_html($translate_ui((string) $date_option['display'])); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <p>Depart : <?php echo esc_html($search_departure); ?></p>
                        <?php else: ?>
                            <p><?php echo esc_html($translate_ui($search_date)); ?> - Depart : <?php echo esc_html($search_departure); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($best_deals)): ?>
                        <div class="ajtb-v1-side-card ajtb-v1-best-deals">
                            <h3>Offres recommandees</h3>
                            <ul>
                                <?php foreach (array_slice($best_deals, 0, 3) as $deal): ?>
                                    <li><?php echo esc_html($translate_ui((string) $deal)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </aside>
            </section>
        </div>
    </main>

    <?php if ($ajth_ready && defined('AJTH_DIR') && file_exists(AJTH_DIR . 'parts/newsletter.php')): ?>
        <div class="ajtb-v1-footer-wrap aj-footer-sitewide">
            <?php
            $settings = $ajth_settings;
            include AJTH_DIR . 'parts/newsletter.php';
            ?>
        </div>
    <?php else: ?>
        <footer class="ajtb-v1-fallback-footer">
            <div class="ajtb-v1-container ajtb-v1-fallback-footer__grid">
                <div><h4>En savoir plus</h4><ul><li><a href="#">À propos</a></li><li><a href="#">FAQ</a></li><li><a href="#">Conditions</a></li></ul></div>
                <div><h4>Société</h4><ul><li><a href="#">Carrière</a></li><li><a href="#">Partenaires</a></li><li><a href="#">Contact</a></li></ul></div>
                <div><h4>Mentions légales</h4><p>Ajinsafro Recreation SARL AU</p><p>Licence N 489117 | RC 18989</p></div>
                <div><h4>Newsletter</h4><form action="#" method="post"><input type="email" name="email" placeholder="Saisissez votre email" required><button type="submit">S'inscrire</button></form></div>
            </div>
        </footer>
    <?php endif; ?>

</div>

<?php get_footer(); ?>

