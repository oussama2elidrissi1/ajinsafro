<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('body_class', function ($classes) {
    $classes[] = 'page-hebergement-ajinsafro';
    return $classes;
});

get_header();

$settings = ajth_get_settings();

$page_url = function_exists('ajth_get_hebergement_page_url')
    ? ajth_get_hebergement_page_url()
    : home_url('/hebergement/');

$all_items = array();
if (function_exists('getAjinsafroHebergements')) {
    $all_items = getAjinsafroHebergements(200, array('posts_per_page' => 200));
}

$destinations = array();
$types = array();
foreach ($all_items as $item) {
    if (!empty($item['location'])) {
        $destinations[] = $item['location'];
    }
    if (!empty($item['category'])) {
        $types[] = $item['category'];
    }
}
$destinations = array_values(array_unique($destinations));
$types = array_values(array_unique($types));
sort($destinations);
sort($types);

$current_pack_slug = function_exists('ajth_get_current_accommodation_pack_slug')
    ? ajth_get_current_accommodation_pack_slug()
    : '';
$current_pack = ($current_pack_slug && function_exists('ajth_get_accommodation_package_by_slug'))
    ? ajth_get_accommodation_package_by_slug($current_pack_slug)
    : null;

if (is_singular('st_hotel')) {
    global $wpdb;

    $hotel_id = (int) get_queried_object_id();
    if ($hotel_id <= 0) {
        $hotel_id = (int) get_the_ID();
    }
    if ($hotel_id <= 0) {
        get_template_part('404');
        get_footer();
        return;
    }

    $hotel_post = get_post($hotel_id);
    if (!($hotel_post instanceof WP_Post)) {
        get_template_part('404');
        get_footer();
        return;
    }

    $meta_first = static function (int $post_id, array $keys, $default = '') {
        foreach ($keys as $key) {
            $value = get_post_meta($post_id, $key, true);
            if (is_scalar($value)) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return $default;
    };

    $split_list = static function ($raw): array {
        if (is_array($raw)) {
            $values = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $maybe = maybe_unserialize($raw);
            if (is_array($maybe)) {
                $values = $maybe;
            } else {
                $values = preg_split('/[\r\n,;|]+/', $raw) ?: array();
            }
        } else {
            $values = array();
        }

        $values = array_map(
            static function ($value): string {
                if (!is_scalar($value)) {
                    return '';
                }

                return trim(wp_strip_all_tags((string) $value));
            },
            $values
        );

        return array_values(array_filter(array_unique($values)));
    };

    $beautify_label = static function (string $value): string {
        $label = trim(str_replace(array('_', '-'), ' ', $value));
        if ($label === '') {
            return '';
        }

        $map = array(
            'wifi' => 'Wi-Fi',
            'air conditioning' => 'Climatisation',
            'air conditioner' => 'Climatisation',
            'parking' => 'Parking',
            'spa' => 'Spa',
            'restaurant' => 'Restaurant',
            'gym' => 'Salle de sport',
            'breakfast' => 'Petit-déjeuner',
            'swimming pool' => 'Piscine',
            'pool' => 'Piscine',
            'airport transport' => 'Transfert aéroport',
            'airport transfer' => 'Transfert aéroport',
            'family room' => 'Chambre familiale',
            'sea view' => 'Vue mer',
        );
        $lower = strtolower($label);

        return $map[$lower] ?? mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
    };

    $format_price = static function ($amount, string $suffix = 'MAD'): string {
        if (!is_numeric($amount)) {
            return 'Sur demande';
        }

        return number_format((float) $amount, 0, ',', ' ') . ' ' . $suffix;
    };

    $fallback_image = function_exists('ajth_hebergement_default_card_image_url')
        ? ajth_hebergement_default_card_image_url()
        : AJTH_URL . 'assets/images/default-hotel.svg';
    $fallback_image = $fallback_image !== '' ? $fallback_image : AJTH_URL . 'assets/images/default-hotel.svg';

    $hotel_row = array();
    if (isset($wpdb)) {
        $table = $wpdb->prefix . 'st_hotel';
        $hotel_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT address, min_price, hotel_star, map_lat, map_lng, is_featured, id_location, multi_location FROM {$table} WHERE post_id = %d",
                $hotel_id
            ),
            ARRAY_A
        ) ?: array();
    }

    $location_context = function_exists('ajth_hebergement_resolve_location_context')
        ? ajth_hebergement_resolve_location_context($hotel_id, $hotel_row)
        : array('city' => '', 'destination' => '', 'country' => '');

    $hotel_title = trim((string) get_the_title($hotel_id));
    $hotel_destination = trim((string) ($location_context['destination'] ?? ''));
    $hotel_city = trim((string) ($location_context['city'] ?? ''));
    $hotel_country = trim((string) ($location_context['country'] ?? ''));
    $hotel_address = trim((string) ($hotel_row['address'] ?? $meta_first($hotel_id, array('address', 'hotel_address'))));
    $hotel_location = implode(', ', array_values(array_filter(array_unique(array($hotel_destination, $hotel_city, $hotel_country)))));
    if ($hotel_location === '') {
        $hotel_location = $hotel_address !== '' ? $hotel_address : 'Maroc';
    }

    $hotel_price = $hotel_row['min_price'] ?? $meta_first($hotel_id, array('min_price', 'price', 'adult_price'));
    $hotel_stars = (int) ($hotel_row['hotel_star'] ?? $meta_first($hotel_id, array('hotel_star'), 0));
    $hotel_status = get_post_status($hotel_id) === 'publish' ? 'Disponible' : 'Indisponible';
    $hotel_type = trim((string) $meta_first($hotel_id, array('hotel_type_label', 'hotel_type'), 'Hébergement'));
    $hotel_excerpt = trim((string) get_post_field('post_excerpt', $hotel_id));
    $hotel_content = trim((string) get_post_field('post_content', $hotel_id));
    $hotel_summary = $hotel_excerpt !== '' ? $hotel_excerpt : wp_trim_words(wp_strip_all_tags($hotel_content), 28, '...');
    $hotel_permalink = get_permalink($hotel_id) ?: $page_url;
    $external_booking_enabled = $meta_first($hotel_id, array('_external_booking'), '0') === '1';
    $external_booking_link = trim((string) $meta_first($hotel_id, array('_external_booking_link')));
    $reserve_url = $external_booking_enabled && $external_booking_link !== '' ? $external_booking_link : $hotel_permalink;
    $check_in_value = sanitize_text_field((string) ($_GET['check_in'] ?? ''));
    $check_out_value = sanitize_text_field((string) ($_GET['check_out'] ?? ''));
    $guest_value = max(1, (int) ($_GET['guests'] ?? 2));
    $room_value = max(1, (int) ($_GET['rooms'] ?? 1));
    $hotel_phone = trim((string) $meta_first($hotel_id, array('hotel_phone', 'phone')));
    $hotel_email = trim((string) $meta_first($hotel_id, array('hotel_email', 'email')));
    $hotel_lat = trim((string) ($hotel_row['map_lat'] ?? $meta_first($hotel_id, array('map_lat', 'latitude'))));
    $hotel_lng = trim((string) ($hotel_row['map_lng'] ?? $meta_first($hotel_id, array('map_lng', 'longitude'))));

    $featured_image = function_exists('ajth_hebergement_catalog_card_image_url')
        ? ajth_hebergement_catalog_card_image_url($hotel_id)
        : $fallback_image;
    $featured_image = $featured_image !== '' ? $featured_image : $fallback_image;
    $gallery_items = array();
    $gallery_urls = function_exists('get_gallery_urls') ? get_gallery_urls($hotel_id) : array();
    if ($featured_image !== '') {
        $gallery_items[] = $featured_image;
    }
    foreach ($gallery_urls as $gallery_row) {
        $gallery_url = trim((string) ($gallery_row['url'] ?? ''));
        if ($gallery_url !== '') {
            $gallery_items[] = $gallery_url;
        }
    }
    $gallery_items = array_values(
        array_filter(
            array_unique(
                array_map(
                    static function ($url) use ($fallback_image) {
                        $url = trim((string) $url);
                        return $url !== '' ? $url : $fallback_image;
                    },
                    $gallery_items
                )
            )
        )
    );
    if (empty($gallery_items)) {
        $gallery_items = array($fallback_image);
    }
    $gallery_count = count($gallery_items);
    $gallery_side_items = array_slice($gallery_items, 1, 4);
    $hotel_description_html = '';
    if ($hotel_content !== '') {
        $hotel_description_html = apply_filters('the_content', $hotel_content);
    } else {
        $hotel_description_html = wpautop(esc_html($hotel_summary !== '' ? $hotel_summary : 'Ajinsafro vous accompagne pour préparer ce séjour avec les meilleures conditions de réservation disponibles.'));
    }
    $hotel_description_plain = trim(wp_strip_all_tags($hotel_content !== '' ? $hotel_content : $hotel_summary));
    $hotel_description_is_long = strlen($hotel_description_plain) > 420;

    $amenities_raw = $meta_first($hotel_id, array('hotel_amenities', 'amenities', 'hotel_facilities'));
    $amenities = array_map($beautify_label, $split_list($amenities_raw));
    $amenities = array_values(array_filter(array_unique($amenities)));

    $policies_raw = trim((string) $meta_first($hotel_id, array('hotel_policies', 'policies', 'rules')));
    $rules = $split_list($policies_raw);
    $check_in_rule = trim((string) $meta_first($hotel_id, array('check_in', 'hotel_check_in'), 'À partir de 14:00'));
    $check_out_rule = trim((string) $meta_first($hotel_id, array('check_out', 'hotel_check_out'), 'Jusqu’à 12:00'));
    $children_rule = trim((string) $meta_first($hotel_id, array('children_policy', 'policy_children'), 'Nous consulter selon la chambre sélectionnée.'));
    $cancel_rule = trim((string) $meta_first($hotel_id, array('cancellation_policy', 'policy_cancellation'), 'Conditions communiquées au moment du devis.'));

    $review_items = get_comments(array(
        'post_id' => $hotel_id,
        'status' => 'approve',
        'number' => 3,
    ));

    $room_query = new WP_Query(array(
        'post_parent' => $hotel_id,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => array(
            'menu_order' => 'ASC',
            'date' => 'DESC',
        ),
        'post_type' => 'any',
        'post__not_in' => array($hotel_id),
    ));
    $room_cards = array();
    if ($room_query->have_posts()) {
        foreach ($room_query->posts as $room_post) {
            if (!($room_post instanceof WP_Post)) {
                continue;
            }
            if (in_array($room_post->post_type, array('attachment', 'revision', 'nav_menu_item'), true)) {
                continue;
            }

            $room_id = (int) $room_post->ID;
            $room_image = function_exists('ajth_hebergement_catalog_card_image_url')
                ? ajth_hebergement_catalog_card_image_url($room_id)
                : $fallback_image;
            $room_image = $room_image !== '' ? $room_image : $fallback_image;

            $surface = $meta_first($room_id, array('room_footage', 'size', 'square_feet', 'sqm', 'surface'));
            $beds = $meta_first($room_id, array('bed_number', 'beds', 'bed_type', 'room_beds'));
            $capacity_adults = (int) $meta_first($room_id, array('adult_number', 'capacity_adults', 'max_adults'), 0);
            $capacity_children = (int) $meta_first($room_id, array('children_number', 'capacity_children', 'max_children'), 0);
            $capacity_total = (int) $meta_first($room_id, array('capacity', 'max_people', 'people_number'), 0);
            if ($capacity_total <= 0) {
                $capacity_total = max(0, $capacity_adults + $capacity_children);
            }

            $room_price = $meta_first($room_id, array('price', 'min_price', 'adult_price', 'base_price'));
            $room_amenities = array_map(
                $beautify_label,
                $split_list($meta_first($room_id, array('room_facilities', 'hotel_room_facilities', 'amenities')))
            );
            $room_amenities = array_values(array_filter(array_unique($room_amenities)));
            $room_content = trim((string) $room_post->post_excerpt);
            if ($room_content === '') {
                $room_content = wp_trim_words(wp_strip_all_tags((string) $room_post->post_content), 22, '...');
            }

            $room_cards[] = array(
                'title' => trim((string) get_the_title($room_id)),
                'image' => $room_image,
                'surface' => $surface,
                'beds' => $beds,
                'capacity_total' => $capacity_total,
                'capacity_adults' => $capacity_adults,
                'capacity_children' => $capacity_children,
                'price' => $room_price,
                'excerpt' => $room_content,
                'amenities' => array_slice($room_amenities, 0, 5),
            );
        }
    }
    wp_reset_postdata();

    $map_query = $hotel_lat !== '' && $hotel_lng !== ''
        ? rawurlencode($hotel_lat . ',' . $hotel_lng)
        : rawurlencode($hotel_address !== '' ? $hotel_address : $hotel_location);
    $map_embed_url = 'https://www.google.com/maps?q=' . $map_query . '&z=14&output=embed';
    $map_link_url = 'https://www.google.com/maps/search/?api=1&query=' . $map_query;
    $whatsapp_url = 'https://wa.me/212660683464?text=' . rawurlencode(sprintf('Bonjour Ajinsafro, je souhaite recevoir un devis pour "%s" (%s).', $hotel_title, $hotel_permalink));
    ?>
    <div class="aj-home-wrap">
        <div class="aj-home aj-hebergement-booking aj-hebergement-single">
            <?php ajth_render_site_header($settings); ?>

            <main class="aj-hebergement-shell aj-hebergement-single-shell">
                <div class="aj-hebergement-container">
                    <nav class="aj-hebergement-breadcrumb" aria-label="Fil d’Ariane">
                        <a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a>
                        <span>/</span>
                        <a href="<?php echo esc_url($page_url); ?>">Hébergement</a>
                        <span>/</span>
                        <span><?php echo esc_html($hotel_title); ?></span>
                    </nav>

                    <section class="aj-hotel-hero-card">
                        <div class="aj-hotel-hero-copy">
                            <div class="aj-hotel-topline">
                                <span class="aj-section-kicker">Ajinsafro Hébergement</span>
                                <span class="aj-hotel-status aj-hotel-status--available"><?php echo esc_html($hotel_status); ?></span>
                            </div>
                            <h1><?php echo esc_html($hotel_title); ?></h1>
                            <p class="aj-hotel-hero-location">
                                <span><?php echo esc_html($hotel_location); ?></span>
                                <?php if ($hotel_stars > 0) { ?>
                                    <span class="aj-hotel-stars" aria-label="<?php echo esc_attr($hotel_stars); ?> étoiles">
                                        <?php for ($star = 0; $star < $hotel_stars; $star++) { ?>
                                            <span aria-hidden="true">★</span>
                                        <?php } ?>
                                    </span>
                                <?php } ?>
                            </p>
                            <?php if ($hotel_summary !== '') { ?>
                                <p class="aj-hotel-hero-summary"><?php echo esc_html($hotel_summary); ?></p>
                            <?php } ?>
                        </div>
                        <div class="aj-hotel-hero-price">
                            <span>À partir de</span>
                            <strong><?php echo esc_html($format_price($hotel_price)); ?></strong>
                            <small>par nuit</small>
                        </div>
                    </section>

                    <section class="aj-hotel-gallery<?php echo $gallery_count < 2 ? ' aj-hotel-gallery--single' : ''; ?>" aria-label="Galerie photos">
                        <div class="aj-hotel-gallery-main">
                            <img src="<?php echo esc_url($gallery_items[0]); ?>" alt="<?php echo esc_attr($hotel_title); ?>" onerror="this.onerror=null;this.src='<?php echo esc_url($fallback_image); ?>';">
                        </div>
                        <?php if ($gallery_count > 1) { ?>
                        <div class="aj-hotel-gallery-side">
                            <?php foreach ($gallery_side_items as $index => $image_url) { ?>
                                <figure class="aj-hotel-gallery-thumb">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($hotel_title . ' photo ' . ($index + 2)); ?>" loading="lazy" onerror="this.onerror=null;this.src='<?php echo esc_url($fallback_image); ?>';">
                                </figure>
                            <?php } ?>
                            <?php for ($placeholder_index = count($gallery_side_items); $placeholder_index < 4; $placeholder_index++) { ?>
                                <figure class="aj-hotel-gallery-thumb aj-hotel-gallery-thumb--placeholder" aria-hidden="true">
                                    <span>Ajinsafro</span>
                                    <small>Photos sur demande</small>
                                </figure>
                            <?php } ?>
                        </div>
                        <?php } ?>
                        <?php if ($gallery_count > 1) { ?>
                        <button type="button" class="aj-hotel-gallery-open" data-aj-gallery-open>Voir toutes les photos</button>
                        <?php } ?>
                    </section>

                    <div class="aj-hotel-layout">
                        <div class="aj-hotel-main">
                            <section class="aj-hotel-section">
                                <div class="aj-section-head">
                                    <div>
                                        <span class="aj-section-kicker">Présentation</span>
                                        <h2>À propos de cet hébergement</h2>
                                    </div>
                                </div>
                                <div class="aj-hotel-richtext<?php echo $hotel_description_is_long ? ' aj-hotel-richtext--clamped' : ''; ?>" data-aj-hotel-richtext>
                                    <?php echo $hotel_description_html; ?>
                                </div>
                                <?php if ($hotel_description_is_long) { ?>
                                <button type="button" class="aj-hotel-more" data-aj-hotel-richtext-toggle aria-expanded="false">Lire plus</button>
                                <?php } ?>
                            </section>

                            <?php if (!empty($amenities)) { ?>
                                <section class="aj-hotel-section">
                                    <div class="aj-section-head">
                                        <div>
                                            <span class="aj-section-kicker">Confort</span>
                                            <h2>Équipements</h2>
                                        </div>
                                    </div>
                                    <div class="aj-hotel-amenities-grid">
                                        <?php foreach ($amenities as $amenity) { ?>
                                            <div class="aj-hotel-amenity">
                                                <span class="aj-hotel-amenity-icon" aria-hidden="true">✓</span>
                                                <span><?php echo esc_html($amenity); ?></span>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </section>
                            <?php } ?>

                            <section class="aj-hotel-section">
                                <div class="aj-section-head">
                                    <div>
                                        <span class="aj-section-kicker">Informations utiles</span>
                                        <h2>Règles</h2>
                                    </div>
                                </div>
                                <div class="aj-hotel-rules-card">
                                    <div class="aj-hotel-rule-row"><strong>Check-in</strong><span><?php echo esc_html($check_in_rule); ?></span></div>
                                    <div class="aj-hotel-rule-row"><strong>Check-out</strong><span><?php echo esc_html($check_out_rule); ?></span></div>
                                    <div class="aj-hotel-rule-row"><strong>Politique enfants</strong><span><?php echo esc_html($children_rule); ?></span></div>
                                    <div class="aj-hotel-rule-row"><strong>Annulation</strong><span><?php echo esc_html($cancel_rule); ?></span></div>
                                    <?php if (!empty($rules)) { ?>
                                        <ul class="aj-hotel-rules-list">
                                            <?php foreach ($rules as $rule_line) { ?>
                                                <li><?php echo esc_html($rule_line); ?></li>
                                            <?php } ?>
                                        </ul>
                                    <?php } ?>
                                </div>
                            </section>

                            <section class="aj-hotel-section" id="chambres-disponibles">
                                <div class="aj-section-head">
                                    <div>
                                        <span class="aj-section-kicker">Séjour</span>
                                        <h2>Chambres disponibles</h2>
                                    </div>
                                </div>
                                <?php if (!empty($room_cards)) { ?>
                                    <div class="aj-hotel-room-list">
                                        <?php foreach ($room_cards as $room_card) { ?>
                                            <article class="aj-hotel-room-card">
                                                <div class="aj-hotel-room-media">
                                                    <img src="<?php echo esc_url($room_card['image']); ?>" alt="<?php echo esc_attr($room_card['title']); ?>" loading="lazy" onerror="this.onerror=null;this.src='<?php echo esc_url($fallback_image); ?>';">
                                                </div>
                                                <div class="aj-hotel-room-copy">
                                                    <h3><?php echo esc_html($room_card['title']); ?></h3>
                                                    <?php if ($room_card['excerpt'] !== '') { ?>
                                                        <p><?php echo esc_html($room_card['excerpt']); ?></p>
                                                    <?php } ?>
                                                    <div class="aj-hotel-room-meta">
                                                        <?php if ($room_card['surface'] !== '') { ?><span>Surface : <?php echo esc_html($room_card['surface']); ?></span><?php } ?>
                                                        <?php if ($room_card['beds'] !== '') { ?><span>Lits : <?php echo esc_html($room_card['beds']); ?></span><?php } ?>
                                                        <?php if ($room_card['capacity_total'] > 0) { ?><span>Capacité : <?php echo esc_html((string) $room_card['capacity_total']); ?> pers.</span><?php } ?>
                                                    </div>
                                                    <?php if (!empty($room_card['amenities'])) { ?>
                                                        <div class="aj-hotel-room-tags">
                                                            <?php foreach ($room_card['amenities'] as $room_amenity) { ?>
                                                                <span><?php echo esc_html($room_amenity); ?></span>
                                                            <?php } ?>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                                <div class="aj-hotel-room-actions">
                                                    <div class="aj-hotel-room-price"><?php echo esc_html($format_price($room_card['price'])); ?></div>
                                                    <a class="aj-pack-secondary" href="<?php echo esc_url($hotel_permalink . '#chambres-disponibles'); ?>">Voir prix</a>
                                                    <a class="aj-pack-reserve" href="<?php echo esc_url($reserve_url); ?>"<?php echo $external_booking_enabled && $external_booking_link !== '' ? ' target="_blank" rel="noopener"' : ''; ?>>Réserver</a>
                                                </div>
                                            </article>
                                        <?php } ?>
                                    </div>
                                <?php } else { ?>
                                    <div class="aj-hotel-empty-card">
                                        <strong>Chambres à confirmer</strong>
                                        <p>Les chambres seront confirmées par notre conseiller Ajinsafro. Demandez un devis pour recevoir les disponibilités détaillées.</p>
                                        <a class="aj-pack-reserve aj-pack-reserve--block" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener">Demander un devis</a>
                                    </div>
                                <?php } ?>
                            </section>

                            <?php if ($hotel_address !== '' || $hotel_lat !== '' || $hotel_lng !== '') { ?>
                                <section class="aj-hotel-section">
                                    <div class="aj-section-head">
                                        <div>
                                            <span class="aj-section-kicker">Adresse</span>
                                            <h2>Localisation</h2>
                                        </div>
                                        <a class="aj-hotel-map-link" href="<?php echo esc_url($map_link_url); ?>" target="_blank" rel="noopener">Voir sur la carte</a>
                                    </div>
                                    <div class="aj-hotel-map-card">
                                        <?php if ($hotel_address !== '') { ?>
                                            <p><?php echo esc_html($hotel_address); ?></p>
                                        <?php } ?>
                                        <div class="aj-hotel-map-embed">
                                            <iframe src="<?php echo esc_url($map_embed_url); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Carte <?php echo esc_attr($hotel_title); ?>"></iframe>
                                        </div>
                                    </div>
                                </section>
                            <?php } ?>

                            <?php if (!empty($review_items)) { ?>
                                <section class="aj-hotel-section">
                                    <div class="aj-section-head">
                                        <div>
                                            <span class="aj-section-kicker">Retours voyageurs</span>
                                            <h2>Avis</h2>
                                        </div>
                                    </div>
                                    <div class="aj-hotel-reviews-grid">
                                        <?php foreach ($review_items as $review) { ?>
                                            <article class="aj-hotel-review-card">
                                                <strong><?php echo esc_html((string) $review->comment_author); ?></strong>
                                                <p><?php echo esc_html(wp_trim_words(wp_strip_all_tags((string) $review->comment_content), 28, '...')); ?></p>
                                            </article>
                                        <?php } ?>
                                    </div>
                                </section>
                            <?php } ?>
                        </div>

                        <aside class="aj-hotel-sidebar">
                            <div class="aj-hotel-booking-card">
                                <span class="aj-section-kicker">Réservation</span>
                                <h3><?php echo esc_html($hotel_title); ?></h3>
                                <div class="aj-hotel-booking-price">
                                    <small>À partir de</small>
                                    <strong><?php echo esc_html($format_price($hotel_price, 'DH')); ?></strong>
                                    <span>/ nuit</span>
                                </div>
                                <form class="aj-hotel-booking-form" action="<?php echo esc_url($hotel_permalink); ?>#chambres-disponibles" method="get">
                                    <label>
                                        <span>Check-in</span>
                                        <input type="date" name="check_in" value="<?php echo esc_attr($check_in_value); ?>">
                                    </label>
                                    <label>
                                        <span>Check-out</span>
                                        <input type="date" name="check_out" value="<?php echo esc_attr($check_out_value); ?>">
                                    </label>
                                    <div class="aj-hotel-booking-grid">
                                        <label>
                                            <span>Voyageurs</span>
                                            <input type="number" min="1" max="12" name="guests" value="<?php echo esc_attr((string) $guest_value); ?>">
                                        </label>
                                        <label>
                                            <span>Chambres</span>
                                            <input type="number" min="1" max="6" name="rooms" value="<?php echo esc_attr((string) $room_value); ?>">
                                        </label>
                                    </div>
                                    <button type="submit" class="aj-pack-reserve aj-pack-reserve--block">Vérifier disponibilité</button>
                                </form>
                                <a class="aj-pack-secondary aj-pack-secondary--block" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener">Demander un devis</a>
                                <?php if ($hotel_phone !== '' || $hotel_email !== '') { ?>
                                    <div class="aj-hotel-contact-quick">
                                        <?php if ($hotel_phone !== '') { ?><span>Tél. <?php echo esc_html($hotel_phone); ?></span><?php } ?>
                                        <?php if ($hotel_email !== '') { ?><span><?php echo esc_html($hotel_email); ?></span><?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </aside>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="aj-hotel-gallery-modal" data-aj-gallery-modal hidden>
        <div class="aj-hotel-gallery-modal__backdrop" data-aj-gallery-close></div>
        <div class="aj-hotel-gallery-modal__dialog" role="dialog" aria-modal="true" aria-label="Galerie photos">
            <button type="button" class="aj-hotel-gallery-modal__close" data-aj-gallery-close>×</button>
            <div class="aj-hotel-gallery-modal__grid">
                <?php foreach ($gallery_items as $image_index => $image_url) { ?>
                    <figure>
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($hotel_title . ' photo ' . ($image_index + 1)); ?>" loading="lazy" onerror="this.onerror=null;this.src='<?php echo esc_url($fallback_image); ?>';">
                    </figure>
                <?php } ?>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var modal = document.querySelector('[data-aj-gallery-modal]');
        var openButton = document.querySelector('[data-aj-gallery-open]');
        var richtext = document.querySelector('[data-aj-hotel-richtext]');
        var richtextToggle = document.querySelector('[data-aj-hotel-richtext-toggle]');

        if (modal && openButton) {
            var closeButtons = modal.querySelectorAll('[data-aj-gallery-close]');
            var toggle = function (open) {
                modal.hidden = !open;
                document.documentElement.classList.toggle('aj-gallery-open', open);
            };

            openButton.addEventListener('click', function () {
                toggle(true);
            });

            closeButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    toggle(false);
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.hidden) {
                    toggle(false);
                }
            });
        }

        if (richtext && richtextToggle) {
            richtextToggle.addEventListener('click', function () {
                var expanded = richtext.classList.toggle('is-expanded');
                richtext.classList.toggle('aj-hotel-richtext--clamped', !expanded);
                richtextToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                richtextToggle.textContent = expanded ? 'Lire moins' : 'Lire plus';
            });
        }
    })();
    </script>
    <?php
    get_footer();
    return;
}

if ($current_pack) {
    $pack_title = (string) ($current_pack['title'] ?? 'Pack hébergement Ajinsafro');
    $pack_city = trim((string) ($current_pack['city'] ?? ''));
    $pack_country = trim((string) ($current_pack['country'] ?? 'Maroc'));
    $pack_location = trim(implode(', ', array_filter(array($pack_city, $pack_country))));
    $pack_type = (string) ($current_pack['accommodation_type'] ?? $current_pack['typeLabel'] ?? 'Hébergement');
    $pack_duration = (string) ($current_pack['duration_label'] ?? $current_pack['duration'] ?? '');
    $pack_board = (string) ($current_pack['pension_type'] ?? $current_pack['pensionLabel'] ?? '');
    $pack_price = isset($current_pack['price_from']) ? (float) $current_pack['price_from'] : (isset($current_pack['price']) ? (float) $current_pack['price'] : null);
    $pack_currency = (string) ($current_pack['currency'] ?? 'DH');
    $pack_description = trim((string) ($current_pack['short_description'] ?? $current_pack['description'] ?? ''));
    $pack_image = trim((string) ($current_pack['image_url'] ?? $current_pack['image'] ?? ''));
    $pack_includes = isset($current_pack['includes']) && is_array($current_pack['includes']) ? $current_pack['includes'] : array();
    $pack_url = function_exists('ajth_get_accommodation_package_public_url') ? ajth_get_accommodation_package_public_url($current_pack) : $page_url;
    $reservation_url = function_exists('ajth_get_accommodation_package_reservation_url') ? ajth_get_accommodation_package_reservation_url($current_pack) : $pack_url;
    $advice_url = 'https://wa.me/212660683464?text=' . rawurlencode(sprintf('Bonjour Ajinsafro, j’aimerais être conseillé sur le pack "%s". %s', $pack_title, $pack_url));
    $price_label = $pack_price !== null ? number_format($pack_price, 0, ',', ' ') . ' ' . $pack_currency : 'Sur demande';

    $similar_packs = array();
    if (function_exists('ajth_get_accommodation_packages')) {
        $all_packs = ajth_get_accommodation_packages();
        foreach ($all_packs as $pack_row) {
            $row_slug = sanitize_title((string) ($pack_row['slug'] ?? $pack_row['id'] ?? ''));
            if ($row_slug === $current_pack_slug) {
                continue;
            }

            if ($pack_city !== '' && strtolower((string) ($pack_row['city'] ?? '')) !== strtolower($pack_city)) {
                continue;
            }

            $similar_packs[] = $pack_row;
            if (count($similar_packs) >= 3) {
                break;
            }
        }

        if (count($similar_packs) < 3) {
            foreach ($all_packs as $pack_row) {
                $row_slug = sanitize_title((string) ($pack_row['slug'] ?? $pack_row['id'] ?? ''));
                if ($row_slug === $current_pack_slug) {
                    continue;
                }
                $already = false;
                foreach ($similar_packs as $existing) {
                    $existing_slug = sanitize_title((string) ($existing['slug'] ?? $existing['id'] ?? ''));
                    if ($existing_slug === $row_slug) {
                        $already = true;
                        break;
                    }
                }
                if ($already) {
                    continue;
                }
                $similar_packs[] = $pack_row;
                if (count($similar_packs) >= 3) {
                    break;
                }
            }
        }
    }
    ?>
    <div class="aj-home-wrap">
        <div class="aj-home aj-hebergement-booking-page">
            <?php ajth_render_site_header($settings); ?>

            <main class="aj-hebergement-shell aj-pack-page-shell">
                <div class="aj-hebergement-container aj-pack-page-container">
                    <nav class="aj-hebergement-breadcrumb" aria-label="Fil d'Ariane">
                        <a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a>
                        <span>/</span>
                        <a href="<?php echo esc_url($page_url); ?>">Hébergement</a>
                        <span>/</span>
                        <span><?php echo esc_html($pack_title); ?></span>
                    </nav>

                    <section class="aj-pack-hero">
                        <div class="aj-pack-hero-media">
                            <?php if ($pack_image !== '') { ?>
                                <img src="<?php echo esc_url($pack_image); ?>" alt="<?php echo esc_attr($pack_title); ?>">
                            <?php } else { ?>
                                <div class="aj-pack-hero-fallback">Ajinsafro</div>
                            <?php } ?>
                        </div>
                        <div class="aj-pack-hero-copy">
                            <a class="aj-pack-back" href="<?php echo esc_url($page_url); ?>">← Retour aux packs hébergement</a>
                            <div class="aj-pack-topline">
                                <span class="aj-category-badge"><?php echo esc_html($pack_type); ?></span>
                                <span class="aj-status-badge"><?php echo !empty($current_pack['is_active']) ? 'Disponible' : 'À confirmer'; ?></span>
                                <?php if ($pack_board !== '') { ?>
                                    <span class="aj-category-badge"><?php echo esc_html($pack_board); ?></span>
                                <?php } ?>
                            </div>
                            <h1><?php echo esc_html($pack_title); ?></h1>
                            <p class="aj-pack-hero-meta">
                                <?php echo esc_html($pack_location !== '' ? $pack_location : 'Maroc'); ?>
                                <?php if ($pack_duration !== '') { ?>
                                    <span>•</span>
                                    <?php echo esc_html($pack_duration); ?>
                                <?php } ?>
                            </p>
                            <p class="aj-pack-hero-summary">
                                <?php echo esc_html($pack_description !== '' ? $pack_description : 'Pack hébergement Ajinsafro prêt à réserver avec services inclus et accompagnement personnalisé.'); ?>
                            </p>
                            <div class="aj-pack-hero-actions">
                                <a class="aj-pack-reserve" href="<?php echo esc_url($reservation_url); ?>" target="_blank" rel="noopener">Réserver ce pack</a>
                                <a class="aj-pack-secondary" href="<?php echo esc_url($advice_url); ?>" target="_blank" rel="noopener">Demander conseil</a>
                            </div>
                        </div>
                    </section>

                    <section class="aj-pack-layout">
                        <div class="aj-pack-main">
                            <article class="aj-pack-block">
                                <div class="aj-pack-block-head">
                                    <span class="aj-section-kicker">Informations principales</span>
                                    <h2>Résumé du pack</h2>
                                </div>
                                <div class="aj-pack-specs">
                                    <div class="aj-pack-spec">
                                        <strong>Destination</strong>
                                        <span><?php echo esc_html($pack_location !== '' ? $pack_location : 'Maroc'); ?></span>
                                    </div>
                                    <div class="aj-pack-spec">
                                        <strong>Type d'hébergement</strong>
                                        <span><?php echo esc_html($pack_type); ?></span>
                                    </div>
                                    <div class="aj-pack-spec">
                                        <strong>Durée</strong>
                                        <span><?php echo esc_html($pack_duration !== '' ? $pack_duration : 'À confirmer'); ?></span>
                                    </div>
                                    <div class="aj-pack-spec">
                                        <strong>Pension</strong>
                                        <span><?php echo esc_html($pack_board !== '' ? $pack_board : 'Non précisée'); ?></span>
                                    </div>
                                </div>
                            </article>

                            <article class="aj-pack-block">
                                <div class="aj-pack-block-head">
                                    <span class="aj-section-kicker">Services inclus</span>
                                    <h2>Ce que comprend le pack</h2>
                                </div>
                                <?php if (!empty($pack_includes)) { ?>
                                    <ul class="aj-pack-detail-list aj-pack-services-grid">
                                        <?php foreach ($pack_includes as $line) { ?>
                                            <li><?php echo esc_html((string) $line); ?></li>
                                        <?php } ?>
                                    </ul>
                                <?php } else { ?>
                                    <p class="aj-pack-empty-copy">Les services inclus seront confirmés avec votre conseiller Ajinsafro.</p>
                                <?php } ?>
                            </article>

                            <article class="aj-pack-block">
                                <div class="aj-pack-block-head">
                                    <span class="aj-section-kicker">Description</span>
                                    <h2>Détails du séjour</h2>
                                </div>
                                <div class="aj-pack-description">
                                    <?php echo wpautop(esc_html($pack_description !== '' ? $pack_description : 'Ce pack hébergement a été créé depuis l’administration Ajinsafro. Contactez-nous pour recevoir le programme détaillé, les options de chambre et les conseils personnalisés.')); ?>
                                </div>
                            </article>

                            <?php if (!empty($similar_packs)) { ?>
                                <section class="aj-pack-block">
                                    <div class="aj-pack-block-head">
                                        <span class="aj-section-kicker">Suggestions Ajinsafro</span>
                                        <h2>Packs similaires</h2>
                                    </div>
                                    <div class="aj-pack-similar-grid">
                                        <?php foreach ($similar_packs as $pack_row) {
                                            $row_title = (string) ($pack_row['title'] ?? '');
                                            $row_url = function_exists('ajth_get_accommodation_package_public_url') ? ajth_get_accommodation_package_public_url($pack_row) : $page_url;
                                            $row_image = trim((string) ($pack_row['image_url'] ?? $pack_row['image'] ?? ''));
                                            $row_location = trim(implode(', ', array_filter(array((string) ($pack_row['city'] ?? ''), (string) ($pack_row['country'] ?? '')))));
                                            $row_price = isset($pack_row['price_from']) ? (float) $pack_row['price_from'] : (isset($pack_row['price']) ? (float) $pack_row['price'] : null);
                                            ?>
                                            <article class="aj-featured-card">
                                                <a class="aj-featured-visual aj-featured-visual-link" href="<?php echo esc_url($row_url); ?>">
                                                    <?php if ($row_image !== '') { ?>
                                                        <img src="<?php echo esc_url($row_image); ?>" alt="<?php echo esc_attr($row_title); ?>" loading="lazy">
                                                    <?php } else { ?>
                                                        <div class="aj-pack-hero-fallback">Ajinsafro</div>
                                                    <?php } ?>
                                                    <span class="aj-badge">Pack</span>
                                                    <?php if ($row_price !== null) { ?>
                                                        <div class="aj-featured-price"><small>À partir de</small><?php echo esc_html(number_format($row_price, 0, ',', ' ') . ' DH'); ?></div>
                                                    <?php } ?>
                                                </a>
                                                <div class="aj-featured-content">
                                                    <div class="aj-inline-meta">
                                                        <span><?php echo esc_html($row_location !== '' ? $row_location : 'Maroc'); ?></span>
                                                        <span><?php echo esc_html((string) ($pack_row['accommodation_type'] ?? $pack_row['typeLabel'] ?? 'Hébergement')); ?></span>
                                                    </div>
                                                    <h3><a class="aj-featured-title-link" href="<?php echo esc_url($row_url); ?>"><?php echo esc_html($row_title); ?></a></h3>
                                                    <p style="margin:0;color:var(--aj-muted);font-size:13px;line-height:1.5;"><?php echo esc_html((string) ($pack_row['short_description'] ?? $pack_row['description'] ?? '')); ?></p>
                                                    <a class="aj-featured-link" href="<?php echo esc_url($row_url); ?>">Voir le pack</a>
                                                </div>
                                            </article>
                                        <?php } ?>
                                    </div>
                                </section>
                            <?php } ?>
                        </div>

                        <aside class="aj-pack-sidebar">
                            <div class="aj-pack-summary-card">
                                <span class="aj-section-kicker">Récapitulatif prix</span>
                                <h3><?php echo esc_html($pack_title); ?></h3>
                                <div class="aj-pack-summary-price"><?php echo esc_html($price_label); ?></div>
                                <ul class="aj-pack-summary-list">
                                    <li><strong>Ville</strong><span><?php echo esc_html($pack_city !== '' ? $pack_city : 'N/A'); ?></span></li>
                                    <li><strong>Pays</strong><span><?php echo esc_html($pack_country !== '' ? $pack_country : 'Maroc'); ?></span></li>
                                    <li><strong>Durée</strong><span><?php echo esc_html($pack_duration !== '' ? $pack_duration : 'N/A'); ?></span></li>
                                    <li><strong>Pension</strong><span><?php echo esc_html($pack_board !== '' ? $pack_board : 'N/A'); ?></span></li>
                                    <li><strong>Disponibilité</strong><span class="aj-pack-summary-availability"><?php echo !empty($current_pack['is_active']) ? 'Active' : 'À confirmer'; ?></span></li>
                                </ul>
                                <div class="aj-pack-summary-actions">
                                    <a class="aj-pack-reserve" href="<?php echo esc_url($reservation_url); ?>" target="_blank" rel="noopener">Réserver ce pack</a>
                                    <a class="aj-pack-secondary" href="<?php echo esc_url($advice_url); ?>" target="_blank" rel="noopener">Demander conseil</a>
                                </div>
                            </div>
                        </aside>
                    </section>
                </div>
            </main>
        </div>
    </div>
    <?php
    get_footer();
    return;
}
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-hebergement-booking-page">
        <?php ajth_render_site_header($settings); ?>

        <div class="aj-hebergement-booking" id="aj-hebergement-booking">
            <main class="aj-hebergement-shell">
                <div class="aj-hebergement-container">
                    <nav class="aj-hebergement-breadcrumb" aria-label="Fil d'Ariane">
                        <a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a>
                        <span>/</span>
                        <span>Hébergement</span>
                    </nav>

                    <section class="aj-hero">
                        <div class="aj-hero-copy">
                            <span class="aj-eyebrow">Sélection Ajinsafro</span>
                            <h1>Trouvez l'hébergement idéal</h1>
                            <p>Comparez les hôtels, riads, appartements et villas disponibles avec des filtres avancés et des prix clairs.</p>
                        </div>

                        <form class="aj-hero-search" id="ajhb-search-form" method="get" action="<?php echo esc_url($page_url); ?>">
                            <label class="aj-field">
                                <span>Destination</span>
                                <input id="ajhb-destination" name="destination" type="text" placeholder="Ville, hôtel, quartier..." value="<?php echo esc_attr(isset($_GET['destination']) ? sanitize_text_field(wp_unslash($_GET['destination'])) : ''); ?>">
                            </label>

                            <label class="aj-field">
                                <span>Date</span>
                                <input id="ajhb-date" name="date" type="date" value="<?php echo esc_attr(isset($_GET['date']) ? sanitize_text_field(wp_unslash($_GET['date'])) : ''); ?>">
                            </label>

                            <label class="aj-field">
                                <span>Type d'hébergement</span>
                                <select id="ajhb-type" name="type">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($types as $t) { ?>
                                        <option value="<?php echo esc_attr($t); ?>" <?php selected(isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '', $t); ?>><?php echo esc_html($t); ?></option>
                                    <?php } ?>
                                </select>
                            </label>

                            <label class="aj-field">
                                <span>Étoiles</span>
                                <select id="ajhb-stars" name="stars">
                                    <option value="">Toutes les étoiles</option>
                                    <option value="1">1 étoile</option>
                                    <option value="2">2 étoiles</option>
                                    <option value="3">3 étoiles</option>
                                    <option value="4">4 étoiles</option>
                                    <option value="5">5 étoiles</option>
                                </select>
                            </label>

                            <button class="aj-search-button" type="submit">Rechercher</button>
                        </form>
                    </section>

                    <section class="aj-pack-detail" id="ajhb-pack-detail-section" hidden>
                        <div class="aj-pack-detail-inner" id="ajhb-pack-detail"></div>
                    </section>

                    <section class="aj-featured" id="ajhb-featured-section" aria-labelledby="aj-featured-title">
                        <div class="aj-section-head">
                            <div>
                                <span class="aj-section-kicker">Sélection Ajinsafro</span>
                                <h2 id="aj-featured-title">Packs d'hébergement à la une</h2>
                            </div>
                            <p>Des packs hébergement soigneusement sélectionnés avec services inclus pour un séjour sans souci.</p>
                        </div>
                        <div class="aj-featured-grid" id="ajhb-featured-grid"></div>
                    </section>

                    <section class="aj-catalog" id="ajhb-catalog-section" aria-labelledby="aj-catalog-title" hidden>
                        <div class="aj-section-head aj-section-head--catalog">
                            <div>
                                <span class="aj-section-kicker">Catalogue complet</span>
                                <h2 id="aj-catalog-title">Hébergements disponibles</h2>
                            </div>
                            <div class="aj-results-meta">
                                <strong><span id="ajhb-count">0</span> résultats</strong>
                                <div class="aj-view-toggle" id="ajhb-view-toggle" role="group" aria-label="Mode d'affichage des résultats">
                                    <button type="button" class="aj-view-btn is-active" id="ajhb-view-list" data-view="list" aria-pressed="true">
                                        <span aria-hidden="true">☰</span>
                                        <span>Liste</span>
                                    </button>
                                    <button type="button" class="aj-view-btn" id="ajhb-view-grid" data-view="grid" aria-pressed="false">
                                        <span aria-hidden="true">▦</span>
                                        <span>Grille</span>
                                    </button>
                                </div>
                                <select id="ajhb-sort-select" class="aj-sort-select" aria-label="Trier les hébergements">
                                    <option value="recommended">Recommandés</option>
                                    <option value="price-asc">Prix croissant</option>
                                    <option value="price-desc">Prix décroissant</option>
                                    <option value="rating-desc">Mieux notés</option>
                                    <option value="stars-desc">Étoiles décroissantes</option>
                                </select>
                            </div>
                        </div>

                        <div class="aj-catalog-layout">
                            <aside class="aj-filters" id="ajhb-desktop-filters" aria-label="Filtres hébergements">
                                <div class="aj-filter-card">
                                    <div class="aj-filter-head">
                                        <h3>Filtrer</h3>
                                        <button id="ajhb-reset-filters" type="button">Réinitialiser</button>
                                    </div>

                                    <div class="aj-filter-group">
                                        <label class="aj-filter-label" for="ajhb-filter-name">Rechercher par nom</label>
                                        <input id="ajhb-filter-name" type="text" placeholder="ex: riad, marina, Fès...">
                                    </div>

                                    <div class="aj-filter-group">
                                        <label class="aj-filter-label" for="ajhb-filter-destination">Destination</label>
                                        <select id="ajhb-filter-destination">
                                            <option value="">Toutes les destinations</option>
                                            <?php foreach ($destinations as $d) { ?>
                                                <option value="<?php echo esc_attr($d); ?>"><?php echo esc_html($d); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="aj-filter-group">
                                        <label class="aj-filter-label" for="ajhb-filter-type">Type d'hébergement</label>
                                        <select id="ajhb-filter-type">
                                            <option value="">Tous les types</option>
                                            <?php foreach ($types as $t) { ?>
                                                <option value="<?php echo esc_attr($t); ?>"><?php echo esc_html($t); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="aj-filter-group">
                                        <span class="aj-filter-label">Étoiles</span>
                                        <div class="aj-stars-checks" id="ajhb-filter-stars">
                                            <label class="aj-check"><input type="checkbox" value="1"> <span class="aj-star-label">1 étoile</span></label>
                                            <label class="aj-check"><input type="checkbox" value="2"> <span class="aj-star-label">2 étoiles</span></label>
                                            <label class="aj-check"><input type="checkbox" value="3"> <span class="aj-star-label">3 étoiles</span></label>
                                            <label class="aj-check"><input type="checkbox" value="4"> <span class="aj-star-label">4 étoiles</span></label>
                                            <label class="aj-check"><input type="checkbox" value="5"> <span class="aj-star-label">5 étoiles</span></label>
                                        </div>
                                    </div>

                                    <div class="aj-filter-grid">
                                        <div class="aj-filter-group">
                                            <label class="aj-filter-label" for="ajhb-filter-price-min">Prix min</label>
                                            <input id="ajhb-filter-price-min" type="number" min="0" placeholder="0">
                                        </div>
                                        <div class="aj-filter-group">
                                            <label class="aj-filter-label" for="ajhb-filter-price-max">Prix max</label>
                                            <input id="ajhb-filter-price-max" type="number" min="0" placeholder="10000">
                                        </div>
                                    </div>

                                    <div class="aj-filter-group">
                                        <span class="aj-filter-label">Disponibilité</span>
                                        <label class="aj-check"><input id="ajhb-filter-popular" type="checkbox"> Sélection Ajinsafro</label>
                                        <label class="aj-check"><input id="ajhb-filter-available" type="checkbox"> Disponible uniquement</label>
                                        <label class="aj-check"><input id="ajhb-filter-promo" type="checkbox"> Promotions</label>
                                    </div>

                                    <div class="aj-filter-group">
                                        <span class="aj-filter-label">Services</span>
                                        <label class="aj-check"><input id="ajhb-filter-wifi" type="checkbox" value="wifi"> Wi-Fi</label>
                                        <label class="aj-check"><input id="ajhb-filter-pool" type="checkbox" value="pool"> Piscine</label>
                                        <label class="aj-check"><input id="ajhb-filter-parking" type="checkbox" value="parking"> Parking</label>
                                        <label class="aj-check"><input id="ajhb-filter-breakfast" type="checkbox" value="breakfast"> Petit-déjeuner</label>
                                        <label class="aj-check"><input id="ajhb-filter-ac" type="checkbox" value="air_conditioning"> Climatisation</label>
                                    </div>
                                </div>
                            </aside>

                            <div class="aj-results">
                                <div class="aj-active-filters" id="ajhb-active-filters"></div>
                                <div class="aj-hebergements-grid" id="ajhb-results-grid"></div>
                                <div class="aj-empty-state" id="ajhb-empty-state" hidden>
                                    <h3>Aucun hébergement trouvé pour ces critères.</h3>
                                    <p>Essayez une autre destination, un autre type d'hébergement ou réinitialisez les filtres.</p>
                                    <button id="ajhb-empty-reset" type="button">Réinitialiser les filtres</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>

            <button class="aj-mobile-filter-trigger" id="ajhb-open-filters" type="button">Filtres</button>

            <div class="aj-mobile-backdrop" id="ajhb-mobile-backdrop"></div>
            <aside class="aj-mobile-panel" id="ajhb-mobile-panel" aria-label="Filtres mobile">
                <div class="aj-mobile-panel-head">
                    <h3>Filtres</h3>
                    <button id="ajhb-close-mobile-filters" type="button" aria-label="Fermer">×</button>
                </div>

                <div class="aj-mobile-panel-body">
                    <div class="aj-filter-group">
                        <label class="aj-filter-label" for="ajhb-mobile-name">Rechercher par nom</label>
                        <input id="ajhb-mobile-name" type="text" placeholder="ex: riad, marina, Fès...">
                    </div>

                    <div class="aj-filter-group">
                        <label class="aj-filter-label" for="ajhb-mobile-destination">Destination</label>
                        <select id="ajhb-mobile-destination">
                            <option value="">Toutes les destinations</option>
                            <?php foreach ($destinations as $d) { ?>
                                <option value="<?php echo esc_attr($d); ?>"><?php echo esc_html($d); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="aj-filter-group">
                        <label class="aj-filter-label" for="ajhb-mobile-type">Type d'hébergement</label>
                        <select id="ajhb-mobile-type">
                            <option value="">Tous les types</option>
                            <?php foreach ($types as $t) { ?>
                                <option value="<?php echo esc_attr($t); ?>"><?php echo esc_html($t); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="aj-filter-group">
                        <span class="aj-filter-label">Étoiles</span>
                        <div class="aj-stars-checks" id="ajhb-mobile-stars">
                            <label class="aj-check"><input type="checkbox" value="1"> <span class="aj-star-label">1 étoile</span></label>
                            <label class="aj-check"><input type="checkbox" value="2"> <span class="aj-star-label">2 étoiles</span></label>
                            <label class="aj-check"><input type="checkbox" value="3"> <span class="aj-star-label">3 étoiles</span></label>
                            <label class="aj-check"><input type="checkbox" value="4"> <span class="aj-star-label">4 étoiles</span></label>
                            <label class="aj-check"><input type="checkbox" value="5"> <span class="aj-star-label">5 étoiles</span></label>
                        </div>
                    </div>

                    <div class="aj-filter-grid">
                        <div class="aj-filter-group">
                            <label class="aj-filter-label" for="ajhb-mobile-price-min">Prix min</label>
                            <input id="ajhb-mobile-price-min" type="number" min="0" placeholder="0">
                        </div>
                        <div class="aj-filter-group">
                            <label class="aj-filter-label" for="ajhb-mobile-price-max">Prix max</label>
                            <input id="ajhb-mobile-price-max" type="number" min="0" placeholder="10000">
                        </div>
                    </div>

                    <div class="aj-filter-group">
                        <span class="aj-filter-label">Disponibilité</span>
                        <label class="aj-check"><input id="ajhb-mobile-popular" type="checkbox"> Sélection Ajinsafro</label>
                        <label class="aj-check"><input id="ajhb-mobile-available" type="checkbox"> Disponible uniquement</label>
                        <label class="aj-check"><input id="ajhb-mobile-promo" type="checkbox"> Promotions</label>
                    </div>

                    <div class="aj-filter-group">
                        <span class="aj-filter-label">Services</span>
                        <label class="aj-check"><input id="ajhb-mobile-wifi" type="checkbox" value="wifi"> Wi-Fi</label>
                        <label class="aj-check"><input id="ajhb-mobile-pool" type="checkbox" value="pool"> Piscine</label>
                        <label class="aj-check"><input id="ajhb-mobile-parking" type="checkbox" value="parking"> Parking</label>
                        <label class="aj-check"><input id="ajhb-mobile-breakfast" type="checkbox" value="breakfast"> Petit-déjeuner</label>
                        <label class="aj-check"><input id="ajhb-mobile-ac" type="checkbox" value="air_conditioning"> Climatisation</label>
                    </div>
                </div>

                <div class="aj-mobile-panel-actions">
                    <button id="ajhb-apply-mobile-filters" type="button">Appliquer</button>
                    <button id="ajhb-reset-mobile-filters" type="button">Réinitialiser</button>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php get_footer(); ?>
