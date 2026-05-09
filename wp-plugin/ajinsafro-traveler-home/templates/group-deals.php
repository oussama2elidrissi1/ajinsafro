<?php
/**
 * Group Deals Page Template
 *
 * @package AjinsafroTravelerHome
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

global $wpdb;

$settings = ajth_get_settings();
$group_deals_url = function_exists('ajth_get_group_deals_url')
    ? ajth_get_group_deals_url()
    : home_url('/group-deals/');

$paged = max(
    1,
    absint(get_query_var('paged')),
    absint(get_query_var('page')),
    absint($_GET['paged'] ?? 0)
);
$per_page = 12;
$offset = ($paged - 1) * $per_page;

$sort = isset($_GET['catalog_orderby']) ? sanitize_text_field(wp_unslash($_GET['catalog_orderby'])) : 'recommended';
$allowed_sorts = ['recommended', 'price_asc', 'price_desc', 'discount_desc', 'newest'];
if (! in_array($sort, $allowed_sorts, true)) {
    $sort = 'recommended';
}

$search_text = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$dest = isset($_GET['dest']) ? sanitize_text_field(wp_unslash($_GET['dest'])) : '';
$selected_category = isset($_GET['category']) ? sanitize_key(wp_unslash($_GET['category'])) : '';
$price_min = isset($_GET['price_min']) ? absint($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? absint($_GET['price_max']) : 0;
$group_size = isset($_GET['group_size']) ? max(0, absint($_GET['group_size'])) : 0;
$featured_only = ! empty($_GET['featured']);
$promo_only = ! empty($_GET['promo']);
$guaranteed_only = ! empty($_GET['guaranteed']);
$selected_services = array_values(array_filter(array_map('sanitize_key', (array) wp_unslash($_GET['service'] ?? []))));

$service_key = static function (string $value): string {
    return sanitize_key(remove_accents($value));
};

$build_url = static function (array $args) use ($group_deals_url): string {
    $clean = [];
    foreach ($args as $key => $value) {
        if (is_array($value)) {
            $value = array_values(array_filter($value, static fn ($item) => $item !== '' && $item !== null));
            if (! empty($value)) {
                $clean[$key] = $value;
            }
            continue;
        }
        if ($value !== '' && $value !== null && $value !== false && $value !== 0 && $value !== '0') {
            $clean[$key] = $value;
        }
    }

    return empty($clean) ? $group_deals_url : add_query_arg($clean, $group_deals_url);
};

$current_args = array_filter([
    's' => $search_text,
    'dest' => $dest,
    'category' => $selected_category,
    'price_min' => $price_min > 0 ? (string) $price_min : '',
    'price_max' => $price_max > 0 ? (string) $price_max : '',
    'group_size' => $group_size > 0 ? (string) $group_size : '',
    'featured' => $featured_only ? '1' : '',
    'promo' => $promo_only ? '1' : '',
    'guaranteed' => $guaranteed_only ? '1' : '',
    'service' => $selected_services,
    'catalog_orderby' => $sort !== 'recommended' ? $sort : '',
], static function ($value): bool {
    if (is_array($value)) {
        return ! empty($value);
    }

    return $value !== '' && $value !== null;
});

$resolve_table = static function (string $name) use ($wpdb): ?string {
    foreach ([$name, $wpdb->prefix . $name] as $candidate) {
        $exists = $wpdb->get_var($wpdb->prepare(
            'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s LIMIT 1',
            $candidate
        ));
        if ($exists) {
            return $candidate;
        }
    }

    return null;
};

$group_deals_table = $resolve_table('group_deals');
$tiers_table = $resolve_table('group_deal_pricing_tiers');
$services_table = $resolve_table('group_deal_services');
$categories_table = $resolve_table('group_deal_categories');
$pivot_table = $resolve_table('group_deal_category_group_deal');
$source_available = $group_deals_table !== null && $tiers_table !== null && $services_table !== null;

$available_destinations = [];
$available_services = [];
$available_categories = [];
$all_deals = [];
$deals = [];
$found_posts = 0;
$visible_count = 0;
$max_num_pages = 1;
$min_price_found = null;
$visible_featured = 0;
$visible_promos = 0;
$visible_guaranteed = 0;
$has_featured_offers = false;
$has_promo_offers = false;
$has_guaranteed_offers = false;
$hero_max_discount = 0;
$hero_total_travelers = 0;
$hero_min_threshold = 0;
$stats_average_discount = 0;
$stats_destination_count = 0;
$stats_total_guaranteed = 0;
$booking_base = rtrim((string) get_option('ajinsafro_booking_url', 'https://booking.ajinsafro.net'), '/');

if ($source_available) {
    $raw_deals = (array) $wpdb->get_results(
        "SELECT * FROM `{$group_deals_table}` WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC, departure_date ASC, id DESC"
    );

    $raw_tiers = (array) $wpdb->get_results(
        "SELECT group_deal_id, min_participants, min_people, max_people, price_per_person, label, sort_order
         FROM `{$tiers_table}`
         WHERE group_deal_id IS NOT NULL
         ORDER BY sort_order ASC, COALESCE(min_people, min_participants) ASC, id ASC"
    );

    $raw_services = (array) $wpdb->get_results(
        "SELECT group_deal_id, name, type, sort_order
         FROM `{$services_table}`
         ORDER BY sort_order ASC, id ASC"
    );

    $raw_categories = [];
    if ($categories_table !== null && $pivot_table !== null) {
        $raw_categories = (array) $wpdb->get_results(
            "SELECT pivot.group_deal_id, cat.name, cat.slug, cat.sort_order
             FROM `{$pivot_table}` AS pivot
             INNER JOIN `{$categories_table}` AS cat ON cat.id = pivot.group_deal_category_id
             WHERE cat.is_active = 1
             ORDER BY cat.sort_order ASC, cat.name ASC"
        );
    }

    $tiers_by_deal = [];
    foreach ($raw_tiers as $row) {
        $tiers_by_deal[(int) $row->group_deal_id][] = [
            'min_people' => (int) ($row->min_people ?: $row->min_participants),
            'max_people' => $row->max_people !== null ? (int) $row->max_people : null,
            'price_per_person' => (float) $row->price_per_person,
            'label' => trim((string) ($row->label ?? '')),
        ];
    }

    $services_by_deal = [];
    foreach ($raw_services as $row) {
        $services_by_deal[(int) $row->group_deal_id][] = [
            'name' => trim((string) $row->name),
            'type' => (string) $row->type,
        ];
    }

    $categories_by_deal = [];
    foreach ($raw_categories as $row) {
        $categories_by_deal[(int) $row->group_deal_id][] = [
            'name' => trim((string) $row->name),
            'slug' => sanitize_key((string) $row->slug),
        ];
    }

    foreach ($raw_deals as $row) {
        $deal_id = (int) $row->id;
        $tiers = $tiers_by_deal[$deal_id] ?? [];
        usort($tiers, static fn ($a, $b) => $a['min_people'] <=> $b['min_people']);

        $current_people = (int) ($row->current_participants ?? 0);
        $min_people = (int) ($row->min_participants ?? 0);
        $max_people = (int) ($row->max_participants ?? 0);
        $active_tier = null;
        foreach ($tiers as $tier) {
            if ($current_people >= $tier['min_people'] && ($tier['max_people'] === null || $current_people <= $tier['max_people'])) {
                $active_tier = $tier;
            }
        }
        if ($active_tier === null && ! empty($tiers)) {
            $active_tier = $tiers[0];
        }

        $starting_price = (float) ($row->starting_price ?? 0);
        if ($starting_price <= 0 && ! empty($tiers)) {
            $starting_price = (float) $tiers[0]['price_per_person'];
        }
        $current_price = $active_tier['price_per_person'] ?? (float) ($row->current_price ?? 0);
        $discount_percent = (int) ($row->discount_percent ?? 0);
        if ($discount_percent <= 0 && $starting_price > 0 && $current_price > 0 && $current_price < $starting_price) {
            $discount_percent = (int) round((($starting_price - $current_price) / $starting_price) * 100);
        }

        $image = trim((string) ($row->image ?? ''));
        $image_url = AJTH_URL . 'assets/images/fallback-voyage.svg';
        if ($image !== '') {
            $image_url = preg_match('#^https?://#i', $image) || strpos($image, 'data:') === 0
                ? $image
                : $booking_base . '/storage/' . ltrim($image, '/');
        }

        $images = [];
        $raw_images = $row->images ?? '';
        if (is_string($raw_images) && $raw_images !== '') {
            $decoded = json_decode($raw_images, true);
            if (is_array($decoded)) {
                $images = $decoded;
            } else {
                $images = array_values(array_filter(array_map('trim', explode("\n", $raw_images))));
            }
        } elseif (is_array($raw_images)) {
            $images = $raw_images;
        }
        $gallery = [];
        foreach ($images as $img) {
            $img = trim((string) $img);
            if ($img === '') {
                continue;
            }
            $gallery[] = preg_match('#^https?://#i', $img) || strpos($img, 'data:') === 0
                ? $img
                : $booking_base . '/storage/' . ltrim($img, '/');
        }
        if (empty($gallery) && $image_url !== AJTH_URL . 'assets/images/fallback-voyage.svg') {
            $gallery[] = $image_url;
        }

        $services = $services_by_deal[$deal_id] ?? [];
        $included_services = [];
        $excluded_services = [];
        $service_keys = [];
        foreach ($services as $service) {
            if ($service['name'] === '') {
                continue;
            }
            if (($service['type'] ?? '') === 'included') {
                $included_services[] = $service['name'];
                $key = $service_key($service['name']);
                $service_keys[] = $key;
                $available_services[$key] = $service['name'];
            } elseif (($service['type'] ?? '') === 'not_included') {
                $excluded_services[] = $service['name'];
            }
        }

        $categories = $categories_by_deal[$deal_id] ?? [];
        $category_slugs = [];
        $category_names = [];
        foreach ($categories as $category) {
            $category_slugs[] = $category['slug'];
            $category_names[] = $category['name'];
            $available_categories[$category['slug']] = $category['name'];
        }

        $destination_label = trim((string) ($row->destination ?? ''));
        if ($destination_label !== '') {
            $available_destinations[$destination_label] = $destination_label;
        }

        $duration = '';
        $duration_days = (int) ($row->duration_days ?? 0);
        $duration_nights = (int) ($row->duration_nights ?? 0);
        if ($duration_days > 0 && $duration_nights > 0) {
            $duration = $duration_days . ' jours / ' . $duration_nights . ' nuits';
        } elseif ($duration_days > 0) {
            $duration = $duration_days . ' jours';
        }

        $is_guaranteed = $current_people >= max(1, $min_people) || (string) ($row->status ?? '') === 'guaranteed';
        $is_full = $max_people > 0 && $current_people >= $max_people;
        $remaining_to_guarantee = max(0, $min_people - $current_people);
        $status_label = trim((string) ($row->badge_label ?? ''));
        if ($status_label === '') {
            if ($is_full || (string) ($row->status ?? '') === 'closed') {
                $status_label = 'Complet';
            } elseif ($is_guaranteed) {
                $status_label = 'Garanti';
            } elseif ($remaining_to_guarantee <= 2) {
                $status_label = 'Presque garanti';
            } else {
                $status_label = 'En cours';
            }
        }

        $status_class = match (sanitize_key(remove_accents($status_label))) {
            'garanti', 'depart-garanti' => 'guaranteed',
            'presque-garanti' => 'almost',
            'dernieres-places' => 'last',
            'promo-groupe' => 'promo',
            'sur-demande', 'entreprise' => 'request',
            'complet' => 'full',
            default => 'progress',
        };

        $all_deals[] = [
            'id' => $deal_id,
            'title' => (string) $row->title,
            'slug' => (string) $row->slug,
            'url' => home_url('/group-deals/' . rawurlencode((string) $row->slug) . '/'),
            'image_url' => $image_url,
            'gallery' => $gallery,
            'destination' => $destination_label,
            'country' => trim((string) ($row->country ?? '')),
            'city' => trim((string) ($row->city ?? '')),
            'duration' => $duration,
            'excerpt' => wp_trim_words(wp_strip_all_tags((string) ($row->short_description ?: $row->description ?: '')), 22, '...'),
            'program' => trim((string) ($row->program ?? '')),
            'conditions' => trim((string) ($row->conditions ?? '')),
            'price_from' => $current_price,
            'price_label' => $current_price > 0 ? number_format($current_price, 0, ',', ' ') : '',
            'old_price_label' => $starting_price > $current_price && $current_price > 0 ? number_format($starting_price, 0, ',', ' ') : '',
            'discount_percent' => $discount_percent,
            'is_featured' => ! empty($row->is_featured),
            'is_guaranteed' => $is_guaranteed,
            'services' => array_slice($included_services, 0, 4),
            'included_services' => $included_services,
            'excluded_services' => $excluded_services,
            'service_keys' => array_values(array_unique($service_keys)),
            'category_slugs' => array_values(array_unique($category_slugs)),
            'category_names' => array_values(array_unique($category_names)),
            'min_people' => $min_people,
            'max_people' => $max_people,
            'status' => sanitize_key((string) ($row->status ?? '')),
            'status_label' => $status_label,
            'status_class' => $status_class,
            'current_people' => $current_people,
            'progress_percent' => $max_people > 0 ? (int) min(100, round(($current_people / max(1, $max_people)) * 100)) : 0,
            'sort_order' => (int) ($row->sort_order ?? 0),
            'departure_date' => (string) ($row->departure_date ?? ''),
        ];
    }

    ksort($available_destinations, SORT_NATURAL | SORT_FLAG_CASE);
    asort($available_services, SORT_NATURAL | SORT_FLAG_CASE);
    asort($available_categories, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($all_deals as $deal) {
        $has_featured_offers = $has_featured_offers || $deal['is_featured'];
        $has_promo_offers = $has_promo_offers || $deal['discount_percent'] > 0;
        $has_guaranteed_offers = $has_guaranteed_offers || $deal['is_guaranteed'];
        $hero_total_travelers += (int) $deal['current_people'];
        $hero_max_discount = max($hero_max_discount, (int) $deal['discount_percent']);
        if ((int) $deal['min_people'] > 0) {
            $hero_min_threshold = $hero_min_threshold > 0
                ? min($hero_min_threshold, (int) $deal['min_people'])
                : (int) $deal['min_people'];
        }
    }

    $filtered = array_values(array_filter($all_deals, static function (array $deal) use (
        $search_text,
        $dest,
        $selected_category,
        $price_min,
        $price_max,
        $group_size,
        $featured_only,
        $promo_only,
        $guaranteed_only,
        $selected_services
    ): bool {
        if ($search_text !== '') {
            $haystack = strtolower(implode(' | ', [
                $deal['title'],
                $deal['destination'],
                $deal['country'],
                $deal['city'],
                $deal['excerpt'],
                implode(' ', $deal['category_names']),
            ]));
            if (strpos($haystack, strtolower($search_text)) === false) {
                return false;
            }
        }

        if ($dest !== '' && $deal['destination'] !== $dest) {
            return false;
        }
        if ($selected_category !== '' && ! in_array($selected_category, $deal['category_slugs'], true)) {
            return false;
        }
        if ($price_min > 0 && (float) $deal['price_from'] < $price_min) {
            return false;
        }
        if ($price_max > 0 && (float) $deal['price_from'] > $price_max) {
            return false;
        }
        if ($group_size > 0 && (int) $deal['max_people'] > 0 && (int) $deal['max_people'] < $group_size) {
            return false;
        }
        if ($featured_only && empty($deal['is_featured'])) {
            return false;
        }
        if ($promo_only && (int) $deal['discount_percent'] <= 0) {
            return false;
        }
        if ($guaranteed_only && empty($deal['is_guaranteed'])) {
            return false;
        }
        foreach ($selected_services as $service_slug) {
            if (! in_array($service_slug, $deal['service_keys'], true)) {
                return false;
            }
        }

        return true;
    }));

    usort($filtered, static function (array $a, array $b) use ($sort): int {
        return match ($sort) {
            'price_asc' => [(float) $a['price_from'], (int) $a['sort_order']] <=> [(float) $b['price_from'], (int) $b['sort_order']],
            'price_desc' => [(float) $b['price_from'], (int) $a['sort_order']] <=> [(float) $a['price_from'], (int) $b['sort_order']],
            'discount_desc' => [(int) $b['discount_percent'], (float) $b['price_from']] <=> [(int) $a['discount_percent'], (float) $a['price_from']],
            'newest' => strcmp((string) $b['departure_date'], (string) $a['departure_date']),
            default => [empty($b['is_featured']) ? 0 : 1, (int) $b['discount_percent'], empty($b['is_guaranteed']) ? 0 : 1, (int) $a['sort_order']] <=> [empty($a['is_featured']) ? 0 : 1, (int) $a['discount_percent'], empty($a['is_guaranteed']) ? 0 : 1, (int) $b['sort_order']],
        };
    });

    $found_posts = count($filtered);
    $visible_featured = count(array_filter($filtered, static fn ($deal) => ! empty($deal['is_featured'])));
    $visible_promos = count(array_filter($filtered, static fn ($deal) => (int) $deal['discount_percent'] > 0));
    $visible_guaranteed = count(array_filter($filtered, static fn ($deal) => ! empty($deal['is_guaranteed'])));
    $price_values = array_values(array_filter(array_map(static fn ($deal) => (float) $deal['price_from'], $filtered)));
    $min_price_found = ! empty($price_values) ? min($price_values) : null;
    $max_num_pages = $found_posts > 0 ? (int) ceil($found_posts / $per_page) : 1;
    $deals = array_slice($filtered, $offset, $per_page);
    $visible_count = count($deals);
    $stats_total_guaranteed = count(array_filter($all_deals, static fn ($deal) => ! empty($deal['is_guaranteed'])));
    $stats_destination_count = count($available_destinations);
    $discount_values = array_values(array_filter(array_map(static fn ($deal) => (int) $deal['discount_percent'], $all_deals)));
    $stats_average_discount = ! empty($discount_values) ? (int) round(array_sum($discount_values) / count($discount_values)) : 0;
}

$active_chips = [];
if ($search_text !== '') {
    $args = $current_args;
    unset($args['s']);
    $active_chips[] = ['label' => 'Recherche: ' . $search_text, 'url' => $build_url($args)];
}
if ($dest !== '') {
    $args = $current_args;
    unset($args['dest']);
    $active_chips[] = ['label' => 'Destination: ' . $dest, 'url' => $build_url($args)];
}
if ($selected_category !== '') {
    $args = $current_args;
    unset($args['category']);
    $active_chips[] = ['label' => 'Type: ' . ($available_categories[$selected_category] ?? $selected_category), 'url' => $build_url($args)];
}
if ($price_min > 0) {
    $args = $current_args;
    unset($args['price_min']);
    $active_chips[] = ['label' => 'Min ' . number_format($price_min, 0, ',', ' ') . ' DH', 'url' => $build_url($args)];
}
if ($price_max > 0) {
    $args = $current_args;
    unset($args['price_max']);
    $active_chips[] = ['label' => 'Max ' . number_format($price_max, 0, ',', ' ') . ' DH', 'url' => $build_url($args)];
}
if ($group_size > 0) {
    $args = $current_args;
    unset($args['group_size']);
    $active_chips[] = ['label' => 'Groupe min: ' . $group_size, 'url' => $build_url($args)];
}
if ($featured_only) {
    $args = $current_args;
    unset($args['featured']);
    $active_chips[] = ['label' => 'Selection Ajinsafro', 'url' => $build_url($args)];
}
if ($promo_only) {
    $args = $current_args;
    unset($args['promo']);
    $active_chips[] = ['label' => 'Promotions', 'url' => $build_url($args)];
}
if ($guaranteed_only) {
    $args = $current_args;
    unset($args['guaranteed']);
    $active_chips[] = ['label' => 'Departs garantis', 'url' => $build_url($args)];
}
foreach ($selected_services as $service_slug) {
    if (! isset($available_services[$service_slug])) {
        continue;
    }
    $args = $current_args;
    $args['service'] = array_values(array_diff($selected_services, [$service_slug]));
    if (empty($args['service'])) {
        unset($args['service']);
    }
    $active_chips[] = ['label' => $available_services[$service_slug], 'url' => $build_url($args)];
}

$sort_labels = [
    'recommended' => 'Recommandees',
    'price_asc' => 'Prix croissant',
    'price_desc' => 'Prix decroissant',
    'discount_desc' => 'Reduction la plus forte',
    'newest' => 'Plus recentes',
];

$_cta_agent = get_option('ajth_agent_settings', []);
$cta_devis_url = ! empty($_cta_agent['whatsapp_url']) ? (string) $_cta_agent['whatsapp_url'] : '';
if ($cta_devis_url === '') {
    $_cp = get_page_by_path('contact');
    $cta_devis_url = $_cp ? (string) get_permalink($_cp) : 'https://wa.me/212539323874';
}
$cta_is_external = strpos($cta_devis_url, 'wa.me') !== false || strpos($cta_devis_url, 'whatsapp') !== false;

$current_group_deal_slug = function_exists('ajth_get_current_group_deal_slug')
    ? ajth_get_current_group_deal_slug()
    : '';
$current_group_deal = null;

if ($current_group_deal_slug !== '' && ! empty($all_deals)) {
    foreach ($all_deals as $deal_row) {
        if (($deal_row['slug'] ?? '') === $current_group_deal_slug) {
            $current_group_deal = $deal_row;
            break;
        }
    }
}
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-groupdeals-page">
        <?php ajth_render_site_header($settings); ?>

        <div class="aj-groupdeals-fusion ajinsafro-page-container" id="aj-groupdeals-fusion">
            <?php if ($current_group_deal) {
                $cd = $current_group_deal;
                $cd_slug = $cd['slug'];
                $cd_gallery = $cd['gallery'] ?? [];
                $cd_tiers = $tiers_by_deal[$cd['id']] ?? [];
                $cd_active_tier = $cd['active_tier'] ?? null;
                $cd_remaining_guarantee = max(0, (int) $cd['min_people'] - (int) $cd['current_people']);
                $cd_remaining_places = max(0, (int) $cd['max_people'] - (int) $cd['current_people']);
                $cd_is_full = (int) $cd['max_people'] > 0 && (int) $cd['current_people'] >= (int) $cd['max_people'];
                $cd_can_participate = ! $cd_is_full && ! in_array($cd['status'], ['closed', 'cancelled'], true);
            ?>
                <main class="ajgd-container" style="padding-top:2rem;padding-bottom:4rem;">
                    <nav class="aj-activities-breadcrumb" aria-label="Fil d Ariane" style="margin-bottom:1.5rem;">
                        <a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a>
                        <span>/</span>
                        <a href="<?php echo esc_url($group_deals_url); ?>">Group Deals</a>
                        <span>/</span>
                        <span><?php echo esc_html($cd['title']); ?></span>
                    </nav>

                    <div id="ajgd-notifications" style="margin-bottom:1.5rem;"></div>

                    <section class="ajgd-card ajgd-single-layout">
                        <div>
                            <?php if (! empty($cd_gallery)) { ?>
                                <div class="ajgd-gallery" style="margin-bottom:1.5rem;">
                                    <?php if (count($cd_gallery) === 1) { ?>
                                        <div class="ajgd-gallery__hero">
                                            <img src="<?php echo esc_url($cd_gallery[0]); ?>" alt="<?php echo esc_attr($cd['title']); ?>" loading="lazy">
                                        </div>
                                    <?php } else { ?>
                                        <div class="ajgd-gallery__grid">
                                            <div class="ajgd-gallery__main">
                                                <img src="<?php echo esc_url($cd_gallery[0]); ?>" alt="<?php echo esc_attr($cd['title']); ?>" loading="lazy">
                                            </div>
                                            <div class="ajgd-gallery__side">
                                                <?php for ($gi = 1; $gi < min(count($cd_gallery), 4); $gi++) { ?>
                                                    <div class="ajgd-gallery__thumb">
                                                        <img src="<?php echo esc_url($cd_gallery[$gi]); ?>" alt="" loading="lazy">
                                                    </div>
                                                <?php } ?>
                                                <?php if (count($cd_gallery) > 4) { ?>
                                                    <div class="ajgd-gallery__more">
                                                        <span>+<?php echo esc_html((string) (count($cd_gallery) - 4)); ?> photos</span>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="ajgd-card__media is-fallback" style="height:320px;border-radius:24px;overflow:hidden;margin-bottom:1.5rem;">
                                    <img src="<?php echo esc_url($cd['image_url']); ?>" alt="<?php echo esc_attr($cd['title']); ?>" loading="lazy">
                                </div>
                            <?php } ?>

                            <div class="ajgd-card__badges" style="margin-bottom:1rem;">
                                <span class="ajgd-badge ajgd-badge--<?php echo esc_attr($cd['status_class']); ?>"><?php echo esc_html($cd['status_label']); ?></span>
                                <?php if (! empty($cd['is_featured'])) { ?><span class="ajgd-badge ajgd-badge--blue">Selection Ajinsafro</span><?php } ?>
                                <?php if ((int) $cd['discount_percent'] > 0) { ?><span class="ajgd-badge ajgd-badge--orange">-<?php echo esc_html((string) $cd['discount_percent']); ?>%</span><?php } ?>
                            </div>
                            <h1 style="font-size:clamp(2rem,4vw,3.2rem);line-height:1.05;margin:0 0 1rem;color:#123b69;"><?php echo esc_html($cd['title']); ?></h1>
                            <p style="margin:0 0 1rem;color:#475569;font-size:1rem;line-height:1.8;"><?php echo esc_html($cd['excerpt']); ?></p>
                            <div class="aj-inline-meta" style="margin-bottom:1rem;">
                                <?php if ($cd['destination'] !== '') { ?><span><?php echo esc_html($cd['destination']); ?></span><?php } ?>
                                <?php if ($cd['country'] !== '') { ?><span><?php echo esc_html($cd['country']); ?></span><?php } ?>
                                <?php if ($cd['duration'] !== '') { ?><span><?php echo esc_html($cd['duration']); ?></span><?php } ?>
                            </div>
                            <?php if (! empty($cd['services'])) { ?>
                                <div class="ajgd-card__tags" style="margin-bottom:1.5rem;">
                                    <?php foreach ($cd['services'] as $service_name) { ?><span><?php echo esc_html($service_name); ?></span><?php } ?>
                                </div>
                            <?php } ?>

                            <?php if ($cd['program'] !== '') { ?>
                                <div style="margin-bottom:1.5rem;">
                                    <h2 style="font-size:1.4rem;font-weight:900;color:#123b69;margin:0 0 .75rem;">Programme du voyage</h2>
                                    <div style="color:#475569;font-size:.98rem;line-height:1.8;white-space:pre-line;"><?php echo nl2br(esc_html($cd['program'])); ?></div>
                                </div>
                            <?php } ?>

                            <div style="display:grid;gap:1.5rem;grid-template-columns:repeat(2,1fr);margin-bottom:1.5rem;">
                                <?php if (! empty($cd['included_services'])) { ?>
                                    <div>
                                        <h3 style="font-size:1.1rem;font-weight:900;color:#123b69;margin:0 0 .5rem;">Services inclus</h3>
                                        <ul style="margin:0;padding:0;list-style:none;">
                                            <?php foreach ($cd['included_services'] as $s) { ?>
                                                <li style="display:flex;align-items:flex-start;gap:.5rem;margin-bottom:.35rem;color:#475569;font-size:.95rem;">
                                                    <span style="width:18px;height:18px;border-radius:50%;background:#20A86B;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;margin-top:1px;">&#10003;</span>
                                                    <?php echo esc_html($s); ?>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                                <?php if (! empty($cd['excluded_services'])) { ?>
                                    <div>
                                        <h3 style="font-size:1.1rem;font-weight:900;color:#123b69;margin:0 0 .5rem;">Non inclus</h3>
                                        <ul style="margin:0;padding:0;list-style:none;">
                                            <?php foreach ($cd['excluded_services'] as $s) { ?>
                                                <li style="display:flex;align-items:flex-start;gap:.5rem;margin-bottom:.35rem;color:#475569;font-size:.95rem;">
                                                    <span style="width:18px;height:18px;border-radius:50%;background:#F7941D;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;margin-top:1px;">&#10007;</span>
                                                    <?php echo esc_html($s); ?>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php } ?>
                            </div>

                            <?php if ($cd['conditions'] !== '') { ?>
                                <div style="margin-bottom:1.5rem;">
                                    <h2 style="font-size:1.2rem;font-weight:900;color:#123b69;margin:0 0 .5rem;">Conditions</h2>
                                    <div style="color:#475569;font-size:.95rem;line-height:1.7;white-space:pre-line;"><?php echo nl2br(esc_html($cd['conditions'])); ?></div>
                                </div>
                            <?php } ?>

                            <a class="ajgd-btn ajgd-btn--outline-blue" href="<?php echo esc_url($group_deals_url); ?>">&#8592; Retour aux offres</a>
                        </div>

                        <aside>
                            <div class="ajgd-card" style="padding:1.5rem;position:sticky;top:88px;">
                                <div class="ajgd-card__price" style="margin-bottom:1rem;">
                                    <?php if ($cd['old_price_label'] !== '') { ?><del><?php echo esc_html($cd['old_price_label']); ?> DH</del><?php } ?>
                                    <small>Prix actuel</small>
                                    <strong><?php echo $cd['price_label'] !== '' ? esc_html($cd['price_label'] . ' DH') : 'Prix sur demande'; ?></strong>
                                    <span>par personne</span>
                                </div>

                                <?php if (! empty($cd_tiers)) { ?>
                                    <div style="margin-bottom:1.25rem;">
                                        <div style="font-size:12px;font-weight:900;color:#123b69;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Paliers de prix</div>
                                        <div style="display:grid;gap:.4rem;">
                                            <?php foreach ($cd_tiers as $tier) {
                                                $is_active = $cd_active_tier && (float) ($tier['price_per_person'] ?? 0) === (float) ($cd_active_tier['price_per_person'] ?? -1) && (int) $tier['min_people'] === (int) ($cd_active_tier['min_people'] ?? -1);
                                            ?>
                                                <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.55rem .7rem;border-radius:10px;font-size:.92rem;"
                                                    class="<?php echo $is_active ? 'ajgd-tier--active' : 'ajgd-tier'; ?>"
                                                >
                                                    <span><?php echo esc_html((string) $tier['min_people'] . ' - ' . ($tier['max_people'] ?? '∞') . ' pers.'); ?></span>
                                                    <span style="font-weight:900;"><?php echo esc_html(number_format_i18n((int) $tier['price_per_person']) . ' DH'); ?></span>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>

                                <div class="ajgd-card__progress" style="margin-bottom:1.25rem;">
                                    <div class="ajgd-card__progress-top">
                                        <span><?php echo esc_html($cd['status_label']); ?></span>
                                        <span><?php echo esc_html((string) $cd['current_people']); ?> / <?php echo esc_html((string) $cd['max_people']); ?></span>
                                    </div>
                                    <div class="ajgd-card__progress-bar"><span style="width:<?php echo esc_attr((string) $cd['progress_percent']); ?>%"></span></div>
                                </div>
                                <div style="display:grid;gap:.6rem;margin-bottom:1.25rem;color:#475569;font-size:.95rem;">
                                    <div><strong style="color:#0f172a;">Participants:</strong> <?php echo esc_html((string) $cd['current_people']); ?> / <?php echo esc_html((string) $cd['max_people']); ?></div>
                                    <div><strong style="color:#0f172a;">Minimum garanti:</strong> <?php echo esc_html((string) $cd['min_people']); ?> personnes</div>
                                    <div><strong style="color:#0f172a;">Places restantes:</strong> <?php echo esc_html((string) $cd_remaining_places); ?></div>
                                    <div><strong style="color:#0f172a;">Statut:</strong> <?php echo esc_html($cd['status_label']); ?></div>
                                </div>

                                <div style="margin-bottom:1.25rem;padding:.75rem 1rem;border-radius:12px;font-size:.92rem;font-weight:700;"
                                    class="<?php echo $cd['is_guaranteed'] ? 'ajgd-msg--success' : 'ajgd-msg--info'; ?>"
                                >
                                    <?php if ($cd['is_guaranteed']) { ?>
                                        Bonne nouvelle, ce voyage est maintenant garanti.
                                    <?php } elseif ($cd_remaining_guarantee > 0) { ?>
                                        Il reste <?php echo esc_html((string) $cd_remaining_guarantee); ?> personne(s) pour garantir ce voyage.
                                    <?php } else { ?>
                                        Le voyage n'est pas encore garanti. Invitez votre groupe pour atteindre le seuil.
                                    <?php } ?>
                                </div>

                                <div class="ajgd-card__actions" style="display:flex;flex-direction:column;gap:.75rem;">
                                    <?php if ($cd_can_participate) { ?>
                                        <button type="button" class="ajgd-btn ajgd-btn--orange" data-open-participation data-deal-slug="<?php echo esc_attr($cd_slug); ?>" data-deal-title="<?php echo esc_attr($cd['title']); ?>">Je participe</button>
                                    <?php } else { ?>
                                        <span class="ajgd-btn ajgd-btn--orange is-disabled"><?php echo $cd_is_full ? 'Complet' : 'Inscriptions fermées'; ?></span>
                                    <?php } ?>
                                    <button type="button" class="ajgd-btn ajgd-btn--outline-blue" data-share-deal data-share-url="<?php echo esc_attr($cd['url']); ?>" data-share-title="<?php echo esc_attr($cd['title']); ?>">Partager l'offre</button>
                                </div>
                            </div>
                        </aside>
                    </section>
                </main>
            <?php } else { ?>
            <section class="ajgd-hero">
                <div class="ajgd-container">
                    <div class="ajgd-hero-grid">
                        <div class="ajgd-hero-copy">
                            <span class="ajgd-eyebrow">Offres de voyage en groupe Ajinsafro</span>
                            <h1 class="ajgd-hero-title">Voyagez en groupe et profitez de tarifs avantageux</h1>
                            <p class="ajgd-hero-sub">Decouvrez nos offres de voyages en groupe soigneusement selectionnees. Rejoignez d'autres voyageurs, beneficiez de tarifs degressifs et profitez d'un depart confirme des que le nombre minimum de participants est atteint.</p>
                            <div class="ajgd-hero-actions">
                                <a class="ajgd-btn ajgd-btn--orange" href="#ajgd-offres">Decouvrir les offres</a>
                                <a class="ajgd-btn ajgd-btn--ghost" href="#ajgd-comment">Comment ca fonctionne ?</a>
                            </div>
                        </div>
                        <div class="ajgd-hero-visual" aria-hidden="true">
                            <div class="ajgd-hero-photo"></div>
                            <div class="ajgd-float-card ajgd-float-card--tl">
                                <span class="ajgd-fi ajgd-fi--green">%</span>
                                <div><small>Reduction max</small><strong>Jusqu'a <?php echo esc_html(number_format_i18n($hero_max_discount)); ?>&nbsp;%</strong></div>
                            </div>
                            <div class="ajgd-float-card ajgd-float-card--tr">
                                <span class="ajgd-fi ajgd-fi--blue">&#128100;</span>
                                <div><small>Voyageurs inscrits</small><strong><?php echo esc_html(number_format_i18n($hero_total_travelers)); ?></strong></div>
                            </div>
                            <div class="ajgd-float-card ajgd-float-card--bl">
                                <span class="ajgd-fi ajgd-fi--green">&#10003;</span>
                                <div><small>Seuil minimum</small><strong>dès <?php echo esc_html(number_format_i18n(max(1, $hero_min_threshold))); ?> pers.</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="ajgd-search-wrap">
                <div class="ajgd-container">
                    <form class="ajgd-search-box" method="get" action="<?php echo esc_url($group_deals_url); ?>">
                        <?php foreach ($current_args as $key => $value) {
                            if (in_array($key, ['s', 'dest', 'category', 'price_max', 'group_size', 'paged'], true)) {
                                continue;
                            }
                            if (is_array($value)) {
                                foreach ($value as $item) { ?>
                                    <input type="hidden" name="<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr((string) $item); ?>">
                                <?php }
                                continue;
                            } ?>
                            <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr((string) $value); ?>">
                        <?php } ?>
                        <div class="ajgd-sf ajgd-sf--wide">
                            <label for="ajgd-s">Destination ou theme</label>
                            <input id="ajgd-s" name="s" type="text" value="<?php echo esc_attr($search_text); ?>" placeholder="Marrakech, Istanbul, Andalousie...">
                        </div>
                        <div class="ajgd-sf">
                            <label for="ajgd-dest">Destination</label>
                            <select id="ajgd-dest" name="dest">
                                <option value="">Toutes</option>
                                <?php foreach ($available_destinations as $d_opt) { ?>
                                    <option value="<?php echo esc_attr($d_opt); ?>" <?php selected($dest, $d_opt); ?>><?php echo esc_html($d_opt); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="ajgd-sf">
                            <label for="ajgd-type">Type de groupe</label>
                            <select id="ajgd-type" name="category">
                                <option value="">Tous les types</option>
                                <?php foreach ($available_categories as $cat_slug => $cat_name) { ?>
                                    <option value="<?php echo esc_attr($cat_slug); ?>" <?php selected($selected_category, $cat_slug); ?>><?php echo esc_html($cat_name); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="ajgd-sf">
                            <label for="ajgd-travelers">Participants</label>
                            <input id="ajgd-travelers" name="group_size" type="number" min="0" value="<?php echo $group_size > 0 ? esc_attr((string) $group_size) : ''; ?>" placeholder="Nb de voyageurs">
                        </div>
                        <div class="ajgd-sf">
                            <label for="ajgd-budget">Budget max (DH)</label>
                            <input id="ajgd-budget" name="price_max" type="number" min="0" value="<?php echo $price_max > 0 ? esc_attr((string) $price_max) : ''; ?>" placeholder="Budget max">
                        </div>
                        <button class="ajgd-search-btn" type="submit">Voir les offres</button>
                    </form>
                </div>
            </div>

            <section class="ajgd-how" id="ajgd-comment">
                <div class="ajgd-container">
                    <div class="ajgd-section-hd">
                        <h2>Comment ca marche&nbsp;?</h2>
                    </div>
                    <div class="ajgd-how-grid">
                        <article class="ajgd-how-card"><div class="ajgd-how-icon">&#8595;</div><div><h3>Des tarifs degressifs</h3><p>Le prix par personne evolue en fonction du nombre total de participants. Plus le groupe se remplit, plus le tarif devient avantageux.</p></div></article>
                        <article class="ajgd-how-card"><div class="ajgd-how-icon">&#10003;</div><div><h3>Depart confirme</h3><p>Le voyage est confirme des que le nombre minimum de participants requis est atteint. Vous etes informe a chaque etape.</p></div></article>
                        <article class="ajgd-how-card"><div class="ajgd-how-icon">&#128200;</div><div><h3>Suivi en temps reel</h3><p>Consultez a tout moment le niveau de remplissage du groupe, les places restantes et l'evolution du tarif propose.</p></div></article>
                    </div>
                </div>
            </section>

            <main class="ajgd-container ajgd-main-grid" id="ajgd-offres">
                <aside class="ajgd-filters" id="ajgd-desktop-filters" aria-label="Filtres Group Deals">
                    <div class="ajgd-promo-card">
                        <span class="ajgd-promo-eyebrow">Conseils groupe</span>
                        <strong>Trouvez l'offre de groupe qui vous correspond</strong>
                        <p>Affinez votre recherche selon la destination, le budget, le type de voyage ou les services inclus.</p>
                    </div>
                    <div class="ajgd-filter-title">
                        <h2>Filtrer par</h2>
                        <a class="ajgd-clear-link" href="<?php echo esc_url($group_deals_url); ?>">Reinitialiser</a>
                    </div>
                    <?php
                    $group_deals_filter_prefix = 'ajgd-desktop';
                    include AJTH_DIR . 'parts/group-deals-filters.php';
                    ?>
                </aside>

                <section class="ajgd-results">
                    <div class="ajgd-results-head">
                        <div class="ajgd-results-topline">
                            <div>
                                <h2><?php echo esc_html(number_format_i18n($found_posts)); ?> offres groupe trouvees</h2>
                                <div class="ajgd-result-count">
                                    <?php echo $min_price_found !== null
                                        ? esc_html('A partir de ' . number_format_i18n((int) $min_price_found) . ' DH par personne')
                                        : 'Tarifs disponibles selon l\'offre'; ?>
                                </div>
                            </div>
                            <form class="ajgd-sort-wrap" method="get" action="<?php echo esc_url($group_deals_url); ?>">
                                <?php foreach ($current_args as $key => $value) {
                                    if ($key === 'catalog_orderby') {
                                        continue;
                                    }
                                    if (is_array($value)) {
                                        foreach ($value as $item) { ?>
                                            <input type="hidden" name="<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr((string) $item); ?>">
                                        <?php }
                                        continue;
                                    } ?>
                                    <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr((string) $value); ?>">
                                <?php } ?>
                                <span>Trier par</span>
                                <select name="catalog_orderby" onchange="this.form.submit()">
                                    <?php foreach ($sort_labels as $sort_key => $sort_label) { ?>
                                        <option value="<?php echo esc_attr($sort_key); ?>" <?php selected($sort, $sort_key); ?>><?php echo esc_html($sort_label); ?></option>
                                    <?php } ?>
                                </select>
                            </form>
                        </div>
                        <div class="ajgd-stat-pills">
                            <span class="ajgd-stat-pill"><?php echo esc_html(number_format_i18n($visible_count)); ?> offres visibles</span>
                            <span class="ajgd-stat-pill"><?php echo esc_html(number_format_i18n($visible_featured)); ?> selections Ajinsafro</span>
                            <span class="ajgd-stat-pill"><?php echo esc_html(number_format_i18n($visible_promos)); ?> reductions actives</span>
                            <span class="ajgd-stat-pill"><?php echo esc_html(number_format_i18n($visible_guaranteed)); ?> departs garantis</span>
                        </div>
                        <?php if (! empty($active_chips)) { ?>
                            <div class="ajgd-chips">
                                <?php foreach ($active_chips as $chip) { ?>
                                    <a class="ajgd-chip" href="<?php echo esc_url($chip['url']); ?>"><?php echo esc_html($chip['label']); ?> &times;</a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <?php if (! $source_available) { ?>
                        <div class="ajgd-empty-state"><div class="ajgd-empty-icon">&#128274;</div><h3>Acces temporairement indisponible</h3><p>Les informations des offres groupe ne sont pas accessibles pour le moment. Veuillez reessayer dans quelques instants.</p></div>
                    <?php } elseif (empty($deals)) { ?>
                        <div class="ajgd-empty-state"><div class="ajgd-empty-icon">&#128269;</div><h3>Aucune offre ne correspond a votre recherche</h3><p>Essayez d'elargir vos criteres de recherche, de modifier le budget ou de supprimer certains filtres pour decouvrir plus d'offres.</p><a class="ajgd-btn ajgd-btn--orange" href="<?php echo esc_url($group_deals_url); ?>">Reinitialiser les filtres</a></div>
                    <?php } else { ?>
                        <div class="ajgd-deals-grid">
                            <?php foreach ($deals as $deal) { ?>
                                <article class="ajgd-card" data-url="<?php echo esc_url($deal['url']); ?>">
                                    <div class="ajgd-card__media<?php echo strpos($deal['image_url'], 'fallback-voyage.svg') !== false ? ' is-fallback' : ''; ?>">
                                        <img src="<?php echo esc_url($deal['image_url']); ?>" alt="<?php echo esc_attr($deal['title']); ?>" loading="lazy">
                                        <div class="ajgd-card__badges">
                                            <span class="ajgd-badge ajgd-badge--<?php echo esc_attr($deal['status_class']); ?>"><?php echo esc_html($deal['status_label']); ?></span>
                                            <?php if ($deal['discount_percent'] > 0) { ?><span class="ajgd-badge ajgd-badge--orange">-<?php echo esc_html((string) $deal['discount_percent']); ?>%</span><?php } ?>
                                            <?php if ($deal['is_featured']) { ?><span class="ajgd-badge ajgd-badge--blue">Selection Ajinsafro</span><?php } ?>
                                        </div>
                                    </div>
                                    <div class="ajgd-card__body">
                                        <?php if ($deal['destination'] !== '') { ?><p class="ajgd-card__dest">&#128205; <?php echo esc_html($deal['destination']); ?></p><?php } ?>
                                        <h3 class="ajgd-card__title"><?php echo esc_html($deal['title']); ?></h3>
                                        <div class="ajgd-card__meta">
                                            <?php if ($deal['duration'] !== '') { ?><span>&#9201; <?php echo esc_html($deal['duration']); ?></span><?php } ?>
                                            <?php if ($deal['max_people'] > 0) { ?><span>&#128100; <?php echo esc_html($deal['current_people'] . '/' . $deal['max_people'] . ' participants inscrits'); ?></span><?php } ?>
                                        </div>
                                        <?php if ($deal['excerpt'] !== '') { ?><p class="ajgd-card__excerpt"><?php echo esc_html($deal['excerpt']); ?></p><?php } ?>
                                        <?php if (! empty($deal['services'])) { ?>
                                            <div class="ajgd-card__tags">
                                                <?php foreach ($deal['services'] as $service_name) { ?><span><?php echo esc_html($service_name); ?></span><?php } ?>
                                            </div>
                                        <?php } ?>
                                        <?php if ($deal['max_people'] > 0) { ?>
                                            <div class="ajgd-card__progress">
                                                <div class="ajgd-card__progress-top">
                                                    <span><?php echo esc_html($deal['status_label']); ?></span>
                                                    <span><?php echo esc_html((string) $deal['current_people']); ?> / <?php echo esc_html((string) $deal['max_people']); ?></span>
                                                </div>
                                                <div class="ajgd-card__progress-bar"><span style="width:<?php echo esc_attr((string) $deal['progress_percent']); ?>%"></span></div>
                                            </div>
                                        <?php } ?>
                                        <div class="ajgd-card__footer">
                                            <div class="ajgd-card__price">
                                                <?php if ($deal['old_price_label'] !== '') { ?><del><?php echo esc_html($deal['old_price_label']); ?> DH</del><?php } ?>
                                                <small>A partir de</small>
                                                <strong><?php echo $deal['price_label'] !== '' ? esc_html($deal['price_label'] . ' DH') : 'Prix sur demande'; ?></strong>
                                                <span>par personne</span>
                                                <span class="ajgd-card__group-hint">Offre ideale pour voyager en groupe a tarif optimise.</span>
                                            </div>
                                            <div class="ajgd-card__actions">
                                                <a class="ajgd-btn ajgd-btn--outline-blue ajgd-btn--sm" href="<?php echo esc_url($deal['url']); ?>">Voir l'offre</a>
                                                <?php
                                                    $deal_is_full = (int) $deal['max_people'] > 0 && (int) $deal['current_people'] >= (int) $deal['max_people'];
                                                    $deal_can_participate = ! $deal_is_full && ! in_array($deal['status'] ?? '', ['closed', 'cancelled'], true);
                                                ?>
                                                <?php if ($deal_can_participate) { ?>
                                                    <button type="button" class="ajgd-btn ajgd-btn--orange ajgd-btn--sm" data-open-participation data-deal-slug="<?php echo esc_attr($deal['slug']); ?>" data-deal-title="<?php echo esc_attr($deal['title']); ?>">Je participe</button>
                                                <?php } else { ?>
                                                    <span class="ajgd-btn ajgd-btn--orange ajgd-btn--sm is-disabled"><?php echo $deal_is_full ? 'Complet' : 'Ferme'; ?></span>
                                                <?php } ?>
                                                <button type="button" class="ajgd-btn ajgd-btn--outline-blue ajgd-btn--sm" data-share-deal data-share-url="<?php echo esc_attr($deal['url']); ?>" data-share-title="<?php echo esc_attr($deal['title']); ?>">Partager</button>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php } ?>
                        </div>

                        <?php
                        $pagination = paginate_links([
                            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                            'format' => '?paged=%#%',
                            'current' => $paged,
                            'total' => $max_num_pages,
                            'type' => 'array',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'add_args' => $current_args,
                        ]);
                        if (! empty($pagination)) { ?>
                            <nav class="ajgd-pagination" aria-label="Pagination Group Deals"><?php foreach ($pagination as $page_link) { echo wp_kses_post($page_link); } ?></nav>
                        <?php } ?>
                    <?php } ?>
                </section>
            </main>

            <section class="ajgd-benefits">
                <div class="ajgd-container">
                    <div class="ajgd-section-hd"><h2>Pourquoi choisir Ajinsafro Group Deals&nbsp;?</h2></div>
                    <div class="ajgd-benefits-grid">
                        <div class="ajgd-benefit"><span class="ajgd-benefit-icon">&#128176;</span><div><h3>Tarifs negocies</h3><p>Profitez de tarifs avantageux grace a la force du voyage en groupe.</p></div></div>
                        <div class="ajgd-benefit"><span class="ajgd-benefit-icon">&#128736;</span><div><h3>Accompagnement Ajinsafro</h3><p>Notre equipe vous accompagne avant, pendant et apres votre reservation.</p></div></div>
                        <div class="ajgd-benefit"><span class="ajgd-benefit-icon">&#128100;</span><div><h3>Offres adaptees a chaque profil</h3><p>Familles, amis, couples, groupes prives ou voyageurs individuels : chacun trouve l'offre qui lui convient.</p></div></div>
                        <div class="ajgd-benefit"><span class="ajgd-benefit-icon">&#128179;</span><div><h3>Reservation simple et flexible</h3><p>Une experience de reservation claire, rapide et pensee pour faciliter votre inscription.</p></div></div>
                    </div>
                </div>
            </section>

            <section class="ajgd-stats">
                <div class="ajgd-container">
                    <h2 class="ajgd-stats-title">Ajinsafro Group Deals en chiffres</h2>
                    <div class="ajgd-stats-grid">
                        <div class="ajgd-stat"><strong><?php echo esc_html(number_format_i18n($hero_total_travelers)); ?></strong><span>Participants inscrits</span></div>
                        <div class="ajgd-stat"><strong><?php echo esc_html(number_format_i18n($stats_average_discount)); ?>&nbsp;%</strong><span>Reduction moyenne</span></div>
                        <div class="ajgd-stat"><strong><?php echo esc_html(number_format_i18n($stats_total_guaranteed)); ?></strong><span>Groupes confirmes</span></div>
                        <div class="ajgd-stat"><strong><?php echo esc_html(number_format_i18n($stats_destination_count)); ?></strong><span>Destinations disponibles</span></div>
                    </div>
                </div>
            </section>

            <section class="ajgd-final-cta">
                <div class="ajgd-container">
                    <div class="ajgd-cta-box">
                        <div class="ajgd-cta-deco" aria-hidden="true"></div>
                        <div class="ajgd-cta-content">
                            <h2>Pret a organiser votre prochain voyage en groupe ?</h2>
                            <p>Decouvrez nos offres disponibles ou demandez un accompagnement personnalise pour votre projet de voyage en groupe.</p>
                        </div>
                        <a class="ajgd-btn ajgd-btn--orange ajgd-btn--lg" href="#ajgd-offres">Voir les offres</a>
                        <a class="ajgd-btn ajgd-btn--outline-blue ajgd-btn--lg" href="<?php echo esc_url($cta_devis_url); ?>" <?php if ($cta_is_external) { echo 'target="_blank" rel="noopener noreferrer"'; } ?>>Demander un devis groupe</a>
                    </div>
                </div>
            </section>

            <button class="ajgd-mobile-filter-btn" type="button" id="ajgd-open-filters">&#9776; Filtrer les offres</button>
            <div class="drawer-backdrop" id="ajgd-drawer-backdrop"></div>
            <aside class="ajgd-mobile-drawer" id="ajgd-mobile-drawer" aria-label="Filtres mobile">
                <div class="ajgd-drawer-head">
                    <h3>Filtres</h3>
                    <button type="button" id="ajgd-close-filters" aria-label="Fermer">&#215;</button>
                </div>
                <?php
                $group_deals_filter_prefix = 'ajgd-mobile';
                include AJTH_DIR . 'parts/group-deals-filters.php';
                ?>
            </aside>
            <?php } ?>
        </div>
    </div>
</div>

<?php if ($current_group_deal) { ?>
<!-- Modal: Je participe -->
<div class="gd-modal-overlay" id="ajgd-modal-overlay" aria-hidden="true">
    <div class="gd-modal" role="dialog" aria-modal="true" aria-labelledby="ajgd-modal-title">
        <!-- Formulaire -->
        <div id="ajgd-modal-form-state">
            <div class="gd-modal-header">
                <div>
                    <h2 id="ajgd-modal-title">Rejoindre ce Group Deal</h2>
                    <p>Remplissez vos informations pour participer a cette offre de groupe.</p>
                </div>
                <button type="button" class="gd-modal-close" id="ajgd-modal-close" aria-label="Fermer">&times;</button>
            </div>
            <form class="gd-join-form" id="ajgd-participation-form">
                <input type="hidden" name="deal_slug" id="ajgd-modal-slug" value="<?php echo esc_attr($cd_slug); ?>">
                <div class="gd-form-grid">
                    <div class="gd-form-group">
                        <label for="ajgd-p-name">Nom complet <span class="gd-required">*</span></label>
                        <input type="text" id="ajgd-p-name" name="full_name" placeholder="Votre nom et prenom">
                        <span class="gd-field-error" id="ajgd-error-name"></span>
                    </div>
                    <div class="gd-form-group">
                        <label for="ajgd-p-phone">Telephone <span class="gd-required">*</span></label>
                        <input type="tel" id="ajgd-p-phone" name="phone" placeholder="Ex: 06 12 34 56 78">
                        <span class="gd-field-error" id="ajgd-error-phone"></span>
                    </div>
                    <div class="gd-form-group">
                        <label for="ajgd-p-email">Email</label>
                        <input type="email" id="ajgd-p-email" name="email" placeholder="votre@email.com">
                        <span class="gd-field-error" id="ajgd-error-email"></span>
                    </div>
                    <div class="gd-form-group">
                        <label for="ajgd-p-count">Nombre de personnes <span class="gd-required">*</span></label>
                        <input type="number" id="ajgd-p-count" name="participants_count" min="1" max="10000" value="1">
                        <span class="gd-field-error" id="ajgd-error-count"></span>
                    </div>
                    <div class="gd-form-group full">
                        <label for="ajgd-p-city">Ville</label>
                        <input type="text" id="ajgd-p-city" name="city" placeholder="Votre ville">
                        <span class="gd-field-error" id="ajgd-error-city"></span>
                    </div>
                    <div class="gd-form-group full">
                        <label for="ajgd-p-remark">Remarque / Question</label>
                        <textarea id="ajgd-p-remark" name="remark" rows="3" placeholder="Une remarque ou une question ?"></textarea>
                        <span class="gd-field-error" id="ajgd-error-remark"></span>
                    </div>
                </div>
                <label class="gd-checkbox">
                    <input type="checkbox" id="ajgd-p-accept" name="accept_conditions">
                    <span>J'accepte les conditions de participation. <span class="gd-required">*</span></span>
                </label>
                <span class="gd-field-error" id="ajgd-error-accept"></span>
                <div class="gd-form-error" id="ajgd-modal-error" role="alert"></div>
                <div class="gd-modal-actions">
                    <button type="button" class="gd-btn-secondary" id="ajgd-modal-cancel">Annuler</button>
                    <button type="submit" class="gd-btn-primary" id="ajgd-modal-submit">Envoyer ma participation</button>
                </div>
            </form>
        </div>
        <!-- Succes -->
        <div class="gd-modal-success" id="ajgd-modal-success" style="display:none;">
            <div class="gd-modal-header" style="margin-bottom: 0;">
                <div>
                    <h2>Votre participation est bien enregistree !</h2>
                    <p>Merci pour votre interet. Notre equipe vous contactera dans les plus brefs delais pour confirmer votre place et vous accompagner dans votre inscription.</p>
                </div>
                <button type="button" class="gd-modal-close" id="ajgd-modal-close-success" aria-label="Fermer">&times;</button>
            </div>
            <div class="gd-success-body">
                <div class="gd-success-icon">&#10003;</div>
                <div class="gd-success-text" id="ajgd-success-message">Merci pour votre inscription.</div>
                <div class="gd-success-stats" id="ajgd-success-stats"></div>
            </div>
            <div class="gd-modal-actions" style="margin-top: 0; padding-top: 20px;">
                <button type="button" class="gd-btn-secondary" id="ajgd-modal-close-ok">Fermer</button>
                <a class="gd-btn-primary" href="<?php echo esc_url($group_deals_url); ?>">Voir les autres offres</a>
            </div>
        </div>
    </div>
</div>
<script>
window.ajgdConfig = {
    apiBase: <?php echo wp_json_encode($booking_base . '/api'); ?>,
    homeUrl: <?php echo wp_json_encode(home_url('/group-deals/')); ?>,
    currentSlug: <?php echo wp_json_encode($cd_slug); ?>
};
</script>
<?php } ?>

<?php get_footer(); ?>
