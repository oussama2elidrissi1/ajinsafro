<?php
/**
 * Single Tour V1 Template
 *
 * Dynamic in V1.2:
 * - Post ID / title
 * - Real images when available
 * - Progressive CRUD data (destination, duration, pricing, departures, itinerary)
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

$search_departure = isset($tour_data['search']['departure_place']) ? (string) $tour_data['search']['departure_place'] : 'Casablanca';
$search_date = isset($tour_data['search']['departure_date']) ? (string) $tour_data['search']['departure_date'] : 'Date a confirmer';
$search_guests = isset($tour_data['search']['guests']) ? (string) $tour_data['search']['guests'] : '2 Adultes';

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
$price_note = !empty($tour_data['pricing']['note']) ? (string) $tour_data['pricing']['note'] : 'Tarif indicatif par adulte.';

if (empty($days)) {
    $days = [
        [
            'day' => 1,
            'date_label' => 'Day 1',
            'title' => 'Programme a venir',
            'description' => 'Les details du programme seront visibles des que les donnees itinerary sont disponibles.',
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
                        <a href="#">Voyages</a>
                        <a href="#">Hebergement</a>
                        <a href="#">Activites</a>
                        <a href="#">Group Deals</a>
                    </nav>
                    <a href="#" class="ajtb-v1-lowcost-btn">Formule low cost</a>
                </div>
            </header>
        <?php endif; ?>
    </div>

    <main class="ajtb-v1-main">
        <div class="ajtb-v1-container">
            <section class="ajtb-v1-search-box" id="ajtb-v1-search-box" aria-label="Search box premium">
                <div class="ajtb-v1-search-card">
                    <span class="ajtb-v1-search-label">Ville de depart</span>
                    <span class="ajtb-v1-search-value"><span class="ajtb-v1-search-text"><?php echo esc_html($search_departure); ?></span><strong aria-hidden="true">▾</strong></span>
                </div>
                <div class="ajtb-v1-search-card">
                    <span class="ajtb-v1-search-label">Date de voyage</span>
                    <span class="ajtb-v1-search-value"><span class="ajtb-v1-search-text"><?php echo esc_html($search_date); ?></span><strong aria-hidden="true">▾</strong></span>
                </div>
                <div class="ajtb-v1-search-card">
                    <span class="ajtb-v1-search-label">Chambres et voyageurs</span>
                    <span class="ajtb-v1-search-value"><span class="ajtb-v1-search-text"><?php echo esc_html($search_guests); ?></span><strong aria-hidden="true">▾</strong></span>
                </div>
                <button type="button" class="ajtb-v1-search-btn">Rechercher</button>
            </section>

            <section class="ajtb-v1-hero" aria-label="Hero tour">
                <p class="ajtb-v1-hero-kicker">Ajinsafro Signature Escape</p>
                <h1 class="ajtb-v1-title"><?php echo esc_html($tour_title); ?></h1>

                <div class="ajtb-v1-meta-pills">
                    <span class="ajtb-v1-pill"><?php echo esc_html($duration_label); ?></span>
                    <span class="ajtb-v1-pill"><?php echo esc_html($destination); ?></span>
                    <span class="ajtb-v1-pill">Groupe max <?php echo esc_html((string) $group_size); ?> pers</span>
                    <span class="ajtb-v1-pill"><?php echo esc_html($rating_label); ?></span>
                    <span class="ajtb-v1-pill ajtb-v1-pill--id">ID #<?php echo esc_html((string) $tour_id); ?></span>
                </div>

                <div class="ajtb-v1-gallery <?php echo esc_attr($hero_gallery_class); ?>" data-image-count="<?php echo esc_attr((string) $hero_count); ?>">
                    <?php foreach ($hero_images as $index => $gallery_img): ?>
                        <figure class="ajtb-v1-gallery-item<?php echo $index === 0 ? ' is-featured' : ''; ?>">
                            <img src="<?php echo $safe_image($gallery_img, $default_hero_image); ?>" alt="Galerie voyage <?php echo esc_attr((string) ($index + 1)); ?>" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
                        </figure>
                    <?php endforeach; ?>
                </div>
            </section>

            <nav class="ajtb-v1-tabs" aria-label="Tabs" role="tablist">
                <button type="button" class="ajtb-v1-tab-btn is-active" data-ajtb-tab="itinerary" role="tab" aria-selected="true">Itinerary</button>
                <button type="button" class="ajtb-v1-tab-btn" data-ajtb-tab="policies" role="tab" aria-selected="false">Policies</button>
                <button type="button" class="ajtb-v1-tab-btn" data-ajtb-tab="summary" role="tab" aria-selected="false">Summary</button>
            </nav>

            <section class="ajtb-v1-layout">
                <div class="ajtb-v1-main-column">
                    <section class="ajtb-v1-tab-panel is-active" id="ajtb-v1-panel-itinerary" role="tabpanel">
                        <article class="ajtb-v1-card ajtb-v1-overview-card">
                            <p class="ajtb-v1-kicker">Apercu du voyage</p>
                            <ul class="ajtb-v1-overview-list">
                                <?php foreach ($overview_points as $point): ?>
                                    <li><?php echo esc_html((string) $point); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>

                        <div class="ajtb-v1-included-bar">
                            <?php
                            $chips = !empty($inclusions) ? array_slice($inclusions, 0, 6) : ['Flights', 'Transfers', 'Hotel', 'Meals', 'Activities'];
                            foreach ($chips as $chip):
                            ?>
                                <span class="ajtb-v1-chip"><?php echo esc_html((string) $chip); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="ajtb-v1-stats-grid">
                            <div class="ajtb-v1-stat"><strong><?php echo esc_html((string) (int) $stats['days']); ?></strong><span>Day Plan</span></div>
                            <div class="ajtb-v1-stat"><strong><?php echo esc_html((string) (int) $stats['flights']); ?></strong><span>Flights</span></div>
                            <div class="ajtb-v1-stat"><strong><?php echo esc_html((string) (int) $stats['transfers']); ?></strong><span>Transfers</span></div>
                            <div class="ajtb-v1-stat"><strong><?php echo esc_html((string) (int) $stats['hotels']); ?></strong><span>Hotels</span></div>
                            <div class="ajtb-v1-stat"><strong><?php echo esc_html((string) (int) $stats['activities']); ?></strong><span>Activities</span></div>
                        </div>

                        <div class="ajtb-v1-day-layout">
                            <aside class="ajtb-v1-day-nav" aria-label="Day navigation">
                                <?php foreach ($days as $i => $day): ?>
                                    <button
                                        type="button"
                                        class="ajtb-v1-day-chip<?php echo $i === 0 ? ' is-active' : ''; ?>"
                                        data-ajtb-day-target="ajtb-v1-day-<?php echo esc_attr((string) (int) $day['day']); ?>">
                                        <?php echo esc_html((string) ($day['date_label'] ?? ('Day ' . (int) $day['day']))); ?>
                                    </button>
                                <?php endforeach; ?>
                            </aside>

                            <div class="ajtb-v1-timeline">
                                <?php foreach ($days as $day): ?>
                                    <?php
                                    $day_num = (int) ($day['day'] ?? 1);
                                    $activities = is_array($day['activities'] ?? null) ? $day['activities'] : [];
                                    $flights_out = is_array($day['flights_out'] ?? null) ? $day['flights_out'] : [];
                                    $flights_in = is_array($day['flights_in'] ?? null) ? $day['flights_in'] : [];
                                    $transfers_in = is_array($day['transfers_in'] ?? null) ? $day['transfers_in'] : [];
                                    $transfers_out = is_array($day['transfers_out'] ?? null) ? $day['transfers_out'] : [];
                                    $meals = is_array($day['meals'] ?? null) ? $day['meals'] : [];
                                    $hotel = !empty($day['hotel']) && is_array($day['hotel']) ? $day['hotel'] : null;

                                    $included_parts = [];
                                    if (!empty($flights_out) || !empty($flights_in)) {
                                        $included_parts[] = (count($flights_out) + count($flights_in)) . ' Flight';
                                    }
                                    if (!empty($hotel)) {
                                        $included_parts[] = '1 Hotel';
                                    }
                                    if (!empty($transfers_in) || !empty($transfers_out)) {
                                        $included_parts[] = (count($transfers_in) + count($transfers_out)) . ' Transfer';
                                    }
                                    if (!empty($activities)) {
                                        $included_parts[] = count($activities) . ' Activity';
                                    }
                                    if (!empty($meals)) {
                                        $included_parts[] = count($meals) . ' Meal';
                                    }
                                    $included_label = !empty($included_parts) ? ('Included: ' . implode(' - ', $included_parts)) : 'Included: Program details';
                                    ?>

                                    <article class="ajtb-v1-day-card" id="ajtb-v1-day-<?php echo esc_attr((string) $day_num); ?>">
                                        <div class="ajtb-v1-day-box">
                                            <header class="ajtb-v1-day-head">
                                                <div class="ajtb-v1-day-head-left">
                                                    <span class="ajtb-v1-day-badge">Day <?php echo esc_html((string) $day_num); ?></span>
                                                    <h3><?php echo esc_html((string) ($day['title'] ?? ('Day ' . $day_num))); ?></h3>
                                                    <p><?php echo esc_html($included_label); ?></p>
                                                </div>
                                            </header>
                                            <div class="ajtb-v1-day-content">
                                                <p class="ajtb-v1-day-desc"><?php echo esc_html((string) ($day['description'] ?? '')); ?></p>

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
                                                    <div class="ajtb-v1-service-card ajtb-v1-service-card--flight">
                                                        <div class="ajtb-v1-service-head"><span>Flight - <?php echo esc_html($from . ' to ' . $to); ?></span><span>Confirmed</span></div>
                                                        <div class="ajtb-v1-service-body ajtb-v1-flight-grid">
                                                            <div><strong><?php echo esc_html($dep_time); ?></strong><small><?php echo esc_html($dep_code); ?></small></div>
                                                            <div class="ajtb-v1-flight-line"></div>
                                                            <div><strong><?php echo esc_html($arr_time); ?></strong><small><?php echo esc_html($arr_code); ?></small></div>
                                                            <img src="<?php echo $safe_image($flight_img, $default_flight_image); ?>" alt="Flight card visual" loading="lazy">
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <?php foreach ($transfers_in as $transfer): ?>
                                                    <?php
                                                    $from = $pick($transfer, ['from_label', 'pickup_location', 'from_city'], 'Arrival point');
                                                    $to = $pick($transfer, ['to_label', 'dropoff_location', 'to_city'], 'Hotel');
                                                    $transfer_img = $pick($transfer, ['image_url'], '');
                                                    ?>
                                                    <div class="ajtb-v1-service-card">
                                                        <div class="ajtb-v1-service-head"><span>Transfer - <?php echo esc_html($from . ' to ' . $to); ?></span><span>Included</span></div>
                                                        <div class="ajtb-v1-service-body ajtb-v1-media-row">
                                                            <img src="<?php echo $safe_image($transfer_img, $default_transfer_image); ?>" alt="Transfer visual" loading="lazy">
                                                            <div>
                                                                <h4><?php echo esc_html($pick($transfer, ['transfer_type', 'service_name', 'name'], 'Transfer service')); ?></h4>
                                                                <p><?php echo esc_html($pick($transfer, ['notes'], 'Transfert organise avec assistance locale.')); ?></p>
                                                                <div class="ajtb-v1-meta-line"><span><?php echo esc_html($pick($transfer, ['vehicle_type'], 'Comfort vehicle')); ?></span><span><?php echo esc_html($pick($transfer, ['pickup_time'], 'On time')); ?></span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <?php if (!empty($hotel)): ?>
                                                    <?php
                                                    $hotel_img = $pick($hotel, ['image_url'], '');
                                                    $hotel_name = $pick($hotel, ['hotel_name', 'name', 'title'], 'Hotel');
                                                    $hotel_desc = $pick($hotel, ['notes', 'address'], 'Sejour prevu dans un hebergement selectionne pour le confort du groupe.');
                                                    $hotel_city = $pick($hotel, ['city', 'hotel_city', 'location'], $destination);
                                                    $hotel_room = $pick($hotel, ['room_type'], 'Standard room');
                                                    $hotel_stars = $pick($hotel, ['stars'], '4');
                                                    ?>
                                                    <div class="ajtb-v1-service-card">
                                                        <div class="ajtb-v1-service-head"><span>Hotel - <?php echo esc_html($hotel_city); ?></span><span>View</span></div>
                                                        <div class="ajtb-v1-service-body ajtb-v1-media-row">
                                                            <img src="<?php echo $safe_image($hotel_img, $default_hotel_image); ?>" alt="Hotel visual" loading="lazy">
                                                            <div>
                                                                <h4><?php echo esc_html($hotel_name); ?></h4>
                                                                <p><?php echo esc_html($hotel_desc); ?></p>
                                                                <div class="ajtb-v1-meta-line"><span><?php echo esc_html($hotel_stars); ?>/5</span><span><?php echo esc_html($hotel_city); ?></span><span><?php echo esc_html($hotel_room); ?></span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php foreach (array_slice($activities, 0, 2) as $activity): ?>
                                                    <?php
                                                    $act_img = $pick($activity, ['image_url'], '');
                                                    $act_title = $pick($activity, ['title'], 'Activity');
                                                    $act_desc = $pick($activity, ['description'], 'Activite incluse selon le programme du jour.');
                                                    $act_price = isset($activity['custom_price']) && $activity['custom_price'] !== null
                                                        ? (float) $activity['custom_price']
                                                        : (isset($activity['base_price']) && $activity['base_price'] !== null ? (float) $activity['base_price'] : null);
                                                    ?>
                                                    <div class="ajtb-v1-service-card">
                                                        <div class="ajtb-v1-service-head"><span>Activity - Program</span><span>Included</span></div>
                                                        <div class="ajtb-v1-service-body ajtb-v1-media-row">
                                                            <img src="<?php echo $safe_image($act_img, $default_activity_image); ?>" alt="Activity visual" loading="lazy">
                                                            <div>
                                                                <h4><?php echo esc_html($act_title); ?></h4>
                                                                <p><?php echo esc_html($act_desc); ?></p>
                                                                <div class="ajtb-v1-meta-line">
                                                                    <?php if ($act_price !== null): ?><span><?php echo esc_html(number_format($act_price, 0, ',', ' ') . ' MAD'); ?></span><?php endif; ?>
                                                                    <?php if (!empty($activity['start_time'])): ?><span><?php echo esc_html((string) $activity['start_time']); ?></span><?php endif; ?>
                                                                    <?php if (!empty($activity['end_time'])): ?><span><?php echo esc_html((string) $activity['end_time']); ?></span><?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <?php foreach ($meals as $meal): ?>
                                                    <p class="ajtb-v1-meal">Meal - <?php echo esc_html((string) $meal); ?></p>
                                                <?php endforeach; ?>

                                                <?php if (!empty($day['notes']) && trim((string) $day['notes']) !== trim((string) ($day['description'] ?? ''))): ?>
                                                    <p class="ajtb-v1-note"><?php echo esc_html((string) $day['notes']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>

                    <section class="ajtb-v1-tab-panel" id="ajtb-v1-panel-policies" role="tabpanel" hidden>
                        <article class="ajtb-v1-card">
                            <h2>Policies and Conditions</h2>
                            <p>Conditions importantes a connaitre avant confirmation.</p>
                            <ul class="ajtb-v1-overview-list">
                                <?php foreach ($policy_items as $policy_line): ?>
                                    <li><?php echo esc_html((string) $policy_line); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (!empty($exclusions)): ?>
                                <h3 class="ajtb-v1-subsection-title">Exclusions</h3>
                                <ul class="ajtb-v1-overview-list">
                                    <?php foreach (array_slice($exclusions, 0, 8) as $line): ?>
                                        <li><?php echo esc_html((string) $line); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </article>
                    </section>

                    <section class="ajtb-v1-tab-panel" id="ajtb-v1-panel-summary" role="tabpanel" hidden>
                        <article class="ajtb-v1-card">
                            <h2>Summary</h2>
                            <div class="ajtb-v1-summary-table">
                                <?php foreach ($summary_rows as $row): ?>
                                    <div><strong><?php echo esc_html((string) $row['label']); ?></strong><span><?php echo esc_html((string) $row['text']); ?></span></div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    </section>
                </div>

                <aside class="ajtb-v1-sidebar">
                    <div class="ajtb-v1-side-card ajtb-v1-price-card" id="ajtb-v1-price-card">
                        <h3>Starting price</h3>
                        <p class="ajtb-v1-price"><span><?php echo esc_html($price_amount); ?></span> <?php echo esc_html($price_currency); ?> / adulte</p>
                        <p class="ajtb-v1-price-note"><?php echo esc_html($price_note); ?></p>
                        <ul class="ajtb-v1-price-includes">
                            <?php
                            $price_items = !empty($inclusions) ? array_slice($inclusions, 0, 4) : ['Programme principal', 'Hebergement', 'Support Ajinsafro', 'Configuration flexible'];
                            foreach ($price_items as $line):
                            ?>
                                <li><?php echo esc_html((string) $line); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button">Proceed to payment</button>
                    </div>

                    <div class="ajtb-v1-side-card ajtb-v1-side-highlight">
                        <h3>Prochain depart</h3>
                        <p><?php echo esc_html($search_date); ?> - Depart: <?php echo esc_html($search_departure); ?></p>
                    </div>

                    <?php if (!empty($coupons)): ?>
                        <div class="ajtb-v1-side-card">
                            <h3>Coupons and Offers</h3>
                            <?php foreach ($coupons as $coupon): ?>
                                <div class="ajtb-v1-coupon">
                                    <div>
                                        <strong><?php echo esc_html((string) ($coupon['code'] ?? 'OFFER')); ?></strong>
                                        <p><?php echo esc_html((string) ($coupon['text'] ?? 'Offer available')); ?></p>
                                    </div>
                                    <span><?php echo esc_html((string) ($coupon['value'] ?? '')); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($best_deals)): ?>
                        <div class="ajtb-v1-side-card ajtb-v1-best-deals">
                            <h3>Best Deals For You</h3>
                            <ul>
                                <?php foreach ($best_deals as $deal): ?>
                                    <li><?php echo esc_html((string) $deal); ?></li>
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
                <div><h4>En savoir plus</h4><ul><li><a href="#">A propos</a></li><li><a href="#">FAQ</a></li><li><a href="#">Conditions</a></li></ul></div>
                <div><h4>Societe</h4><ul><li><a href="#">Carriere</a></li><li><a href="#">Partenaires</a></li><li><a href="#">Contact</a></li></ul></div>
                <div><h4>Mentions legales</h4><p>Ajinsafro Recreation SARL AU</p><p>Licence N 489117 | RC 18989</p></div>
                <div><h4>Newsletter</h4><form action="#" method="post"><input type="email" name="email" placeholder="Saisissez votre email" required><button type="submit">S inscrire</button></form></div>
            </div>
        </footer>
    <?php endif; ?>

    <button type="button" class="ajtb-v1-floating-btn" data-ajtb-action="scroll-price">Customize my trip</button>
</div>

<?php get_footer(); ?>
