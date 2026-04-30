<?php
/**
 * Group Deals Page Template
 *
 * Premium layout with hero, how-it-works, filter sidebar, benefits, stats, and CTA.
 * Uses real data from the shared Laravel `voyages` table filtered by `is_group_deal = 1`.
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
$price_min = isset($_GET['price_min']) ? absint($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? absint($_GET['price_max']) : 0;
$group_size = isset($_GET['group_size']) ? max(0, absint($_GET['group_size'])) : 0;
$featured_only = ! empty($_GET['featured']);
$promo_only = ! empty($_GET['promo']);
$guaranteed_only = ! empty($_GET['guaranteed']);
$selected_services = isset($_GET['service']) ? (array) wp_unslash($_GET['service']) : [];

$service_catalog = [
    'vol' => [
        'label' => 'Vol inclus',
        'keywords' => ['vol', 'flight', 'avion'],
    ],
    'hotel' => [
        'label' => 'Hotel inclus',
        'keywords' => ['hotel', 'hebergement', 'riad', 'resort', 'appartement'],
    ],
    'transfert' => [
        'label' => 'Transfert',
        'keywords' => ['transfert', 'transfer', 'navette'],
    ],
    'guide' => [
        'label' => 'Guide',
        'keywords' => ['guide', 'accompagnateur'],
    ],
    'petit_dejeuner' => [
        'label' => 'Petit-dejeuner',
        'keywords' => ['petit-dejeuner', 'petit déjeuner', 'breakfast'],
    ],
];
$selected_services = array_values(array_intersect(array_map('sanitize_key', $selected_services), array_keys($service_catalog)));

$is_guaranteed_policy = static function (?string $policy): bool {
    $value = function_exists('mb_strtolower')
        ? mb_strtolower((string) $policy, 'UTF-8')
        : strtolower((string) $policy);

    foreach (['garanti', 'garantie', 'confirme', 'confirm', 'depart assure'] as $needle) {
        if ($needle !== '' && strpos($value, $needle) !== false) {
            return true;
        }
    }

    return false;
};

$normalize_list = static function ($raw): array {
    if (is_array($raw)) {
        $items = $raw;
    } elseif (is_string($raw)) {
        $trimmed = trim($raw);
        $decoded = null;
        if ($trimmed !== '' && ($trimmed[0] === '[' || $trimmed[0] === '{')) {
            $decoded = json_decode($trimmed, true);
        }
        if (is_array($decoded)) {
            $items = $decoded;
        } else {
            $items = preg_split('/[\r\n,;|]+/', wp_strip_all_tags($trimmed));
        }
    } else {
        $items = [];
    }

    $items = array_map(static function ($item): string {
        return trim(wp_strip_all_tags((string) $item));
    }, $items);

    $items = array_values(array_filter(array_unique($items), static function ($item): bool {
        return $item !== '';
    }));

    return $items;
};

$infer_service_keys = static function ($raw, array $catalog) use ($normalize_list): array {
    $haystack_parts = $normalize_list($raw);
    $haystack = function_exists('mb_strtolower')
        ? mb_strtolower(implode(' | ', $haystack_parts), 'UTF-8')
        : strtolower(implode(' | ', $haystack_parts));

    $found = [];
    foreach ($catalog as $service_key => $config) {
        foreach ((array) ($config['keywords'] ?? []) as $keyword) {
            $needle = function_exists('mb_strtolower')
                ? mb_strtolower((string) $keyword, 'UTF-8')
                : strtolower((string) $keyword);
            if ($needle !== '' && strpos($haystack, $needle) !== false) {
                $found[] = $service_key;
                break;
            }
        }
    }

    return array_values(array_unique($found));
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

$voyages_table = null;
foreach (['voyages', $wpdb->prefix . 'voyages'] as $candidate) {
    $exists = $wpdb->get_var($wpdb->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s LIMIT 1',
        $candidate
    ));
    if ($exists) {
        $voyages_table = $candidate;
        break;
    }
}

$valid_statuses = ['actif', 'published', 'active', 'publish'];
$status_placeholders = implode(',', array_fill(0, count($valid_statuses), '%s'));

$available_destinations = [];
$available_services = [];
$has_featured_offers = false;
$has_promo_offers = false;
$has_guaranteed_offers = false;
$deals = [];
$found_posts = 0;
$max_num_pages = 1;
$min_price_found = null;

if ($voyages_table !== null) {
    $meta_rows = (array) $wpdb->get_results(
        $wpdb->prepare(
            "SELECT destination, tours_include, departure_policy, is_featured, price_from, old_price
             FROM `{$voyages_table}`
             WHERE status IN ($status_placeholders) AND is_group_deal = 1
             ORDER BY updated_at DESC
             LIMIT 500",
            ...$valid_statuses
        )
    );

    $dest_index = [];
    $service_counts = array_fill_keys(array_keys($service_catalog), 0);
    foreach ($meta_rows as $row) {
        $destination_label = trim((string) ($row->destination ?? ''));
        if ($destination_label !== '') {
            $dest_index[$destination_label] = $destination_label;
        }

        if (! empty($row->is_featured)) {
            $has_featured_offers = true;
        }
        if ((float) ($row->old_price ?? 0) > (float) ($row->price_from ?? 0) && (float) ($row->price_from ?? 0) > 0) {
            $has_promo_offers = true;
        }
        if ($is_guaranteed_policy($row->departure_policy ?? '')) {
            $has_guaranteed_offers = true;
        }

        foreach ($infer_service_keys($row->tours_include ?? null, $service_catalog) as $service_key) {
            $service_counts[$service_key]++;
        }
    }

    $available_destinations = array_values($dest_index);
    sort($available_destinations, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($service_catalog as $service_key => $config) {
        if (($service_counts[$service_key] ?? 0) > 0) {
            $available_services[$service_key] = $config['label'];
        }
    }

    $where_parts = ["status IN ($status_placeholders)", 'is_group_deal = 1'];
    $where_values = $valid_statuses;

    if ($search_text !== '') {
        $like = '%' . $wpdb->esc_like($search_text) . '%';
        $where_parts[] = '(name LIKE %s OR destination LIKE %s OR description LIKE %s OR accroche LIKE %s)';
        array_push($where_values, $like, $like, $like, $like);
    }

    if ($dest !== '') {
        $where_parts[] = 'destination = %s';
        $where_values[] = $dest;
    }

    if ($price_min > 0) {
        $where_parts[] = 'price_from >= %d';
        $where_values[] = $price_min;
    }
    if ($price_max > 0) {
        $where_parts[] = 'price_from <= %d';
        $where_values[] = $price_max;
    }
    if ($group_size > 0) {
        $where_parts[] = '(max_people IS NULL OR max_people = 0 OR max_people >= %d)';
        $where_values[] = $group_size;
    }
    if ($featured_only) {
        $where_parts[] = 'is_featured = 1';
    }
    if ($promo_only) {
        $where_parts[] = '(old_price IS NOT NULL AND old_price > price_from AND price_from > 0)';
    }
    if ($guaranteed_only) {
        $where_parts[] = "(LOWER(COALESCE(departure_policy, '')) LIKE %s OR LOWER(COALESCE(departure_policy, '')) LIKE %s OR LOWER(COALESCE(departure_policy, '')) LIKE %s)";
        array_push($where_values, '%garanti%', '%confirm%', '%confirme%');
    }
    foreach ($selected_services as $service_key) {
        if (! isset($service_catalog[$service_key])) {
            continue;
        }
        $keyword_parts = [];
        foreach ((array) $service_catalog[$service_key]['keywords'] as $keyword) {
            $keyword_parts[] = "LOWER(COALESCE(tours_include, '')) LIKE %s";
            $where_values[] = '%' . strtolower((string) $keyword) . '%';
        }
        if (! empty($keyword_parts)) {
            $where_parts[] = '(' . implode(' OR ', $keyword_parts) . ')';
        }
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where_parts);
    $order_sql = match ($sort) {
        'price_asc' => 'ORDER BY CASE WHEN price_from IS NULL OR price_from = 0 THEN 1 ELSE 0 END ASC, price_from ASC, updated_at DESC',
        'price_desc' => 'ORDER BY CASE WHEN price_from IS NULL OR price_from = 0 THEN 1 ELSE 0 END ASC, price_from DESC, updated_at DESC',
        'discount_desc' => 'ORDER BY CASE WHEN old_price > price_from AND old_price > 0 THEN ((old_price - price_from) / old_price) ELSE 0 END DESC, updated_at DESC',
        'newest' => 'ORDER BY updated_at DESC, id DESC',
        default => 'ORDER BY is_featured DESC, CASE WHEN old_price > price_from AND price_from > 0 THEN 1 ELSE 0 END DESC, updated_at DESC, id DESC',
    };

    $found_posts = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM `{$voyages_table}` {$where_sql}", ...$where_values)
    );
    $max_num_pages = $found_posts > 0 ? (int) ceil($found_posts / $per_page) : 1;

    $rows = (array) $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, name, slug, destination, duration_text, price_from, old_price, currency,
                    featured_image, accroche, description, min_people, max_people, wp_post_id,
                    is_featured, departure_policy, tours_include, updated_at
             FROM `{$voyages_table}`
             {$where_sql}
             {$order_sql}
             LIMIT %d OFFSET %d",
            ...array_merge($where_values, [$per_page, $offset])
        )
    );

    $booking_base = rtrim((string) get_option('ajinsafro_booking_url', 'https://booking.ajinsafro.net'), '/');
    foreach ($rows as $row) {
        $image_url = '';
        if (! empty($row->wp_post_id)) {
            $thumb = get_the_post_thumbnail_url((int) $row->wp_post_id, 'medium_large');
            if ($thumb) {
                $image_url = $thumb;
            }
        }
        if ($image_url === '' && ! empty($row->featured_image)) {
            $featured_image = (string) $row->featured_image;
            if (preg_match('#^https?://#i', $featured_image) || strpos($featured_image, 'data:') === 0) {
                $image_url = $featured_image;
            } else {
                $image_url = $booking_base . '/storage/' . ltrim($featured_image, '/');
            }
        }
        if ($image_url === '') {
            $image_url = AJTH_URL . 'assets/images/fallback-voyage.svg';
        }

        $deal_url = '';
        if (! empty($row->wp_post_id)) {
            $deal_url = (string) get_permalink((int) $row->wp_post_id);
        }
        if ($deal_url === '' && ! empty($row->slug)) {
            $deal_url = home_url('/voyages/' . rawurlencode((string) $row->slug) . '/');
        }

        $price_from = (float) ($row->price_from ?? 0);
        $old_price = (float) ($row->old_price ?? 0);
        $discount_percent = ($old_price > $price_from && $price_from > 0)
            ? (int) round((($old_price - $price_from) / $old_price) * 100)
            : 0;
        $service_keys = $infer_service_keys($row->tours_include ?? null, $service_catalog);
        $services = [];
        foreach ($service_keys as $service_key) {
            if (isset($service_catalog[$service_key])) {
                $services[] = $service_catalog[$service_key]['label'];
            }
        }
        $services = array_slice($services, 0, 4);

        $min_people = (int) ($row->min_people ?? 0);
        $max_people = (int) ($row->max_people ?? 0);
        $threshold_ratio = ($min_people > 0 && $max_people > 0 && $max_people >= $min_people)
            ? min(100, (int) round(($min_people / max(1, $max_people)) * 100))
            : 0;

        $excerpt_source = trim((string) ($row->accroche ?: $row->description ?: ''));
        $policy_text = trim(wp_strip_all_tags((string) ($row->departure_policy ?? '')));
        $is_guaranteed = $is_guaranteed_policy($policy_text);

        $deals[] = [
            'id' => (int) $row->id,
            'title' => (string) $row->name,
            'url' => $deal_url,
            'image_url' => $image_url,
            'destination' => trim((string) ($row->destination ?? '')),
            'duration' => trim((string) ($row->duration_text ?? '')),
            'excerpt' => $excerpt_source !== '' ? wp_trim_words($excerpt_source, 22, '...') : '',
            'policy' => $policy_text !== '' ? wp_trim_words($policy_text, 12, '...') : '',
            'price_from' => $price_from,
            'price_label' => $price_from > 0 ? number_format($price_from, 0, ',', ' ') : '',
            'old_price_label' => $old_price > $price_from && $price_from > 0 ? number_format($old_price, 0, ',', ' ') : '',
            'discount_percent' => $discount_percent,
            'is_featured' => ! empty($row->is_featured),
            'is_guaranteed' => $is_guaranteed,
            'services' => $services,
            'min_people' => $min_people,
            'max_people' => $max_people,
            'threshold_ratio' => $threshold_ratio,
        ];

        if ($price_from > 0) {
            $min_price_found = $min_price_found === null ? $price_from : min($min_price_found, $price_from);
        }
    }
}

// ---------- Mock Group Deals (fallback static data) ----------
$mock_deals_pool = [
    [
        'id' => 1001, 'title' => 'Marrakech & Atlas en groupe',
        'url' => home_url('/group-deals/?deal=marrakech-atlas-groupe'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Maroc', 'duration' => '4 jours / 3 nuits',
        'excerpt' => 'Découvrez Marrakech et l\'Atlas en groupe avec un programme riche et des tarifs préférentiels.',
        'policy' => 'Garanti', 'price_from' => 1290, 'price_label' => '1 290',
        'old_price_label' => '1 690', 'discount_percent' => 24,
        'is_featured' => false, 'is_guaranteed' => true,
        'services' => ['Vol inclus', 'Hôtel inclus', 'Guide'],
        'min_people' => 12, 'max_people' => 20, 'threshold_ratio' => 60,
        'status' => 'guaranteed', 'status_label' => 'Garanti', 'status_class' => 'guaranteed',
        'current_people' => 12, 'progress_percent' => 60,
    ],
    [
        'id' => 1002, 'title' => 'Chefchaouen & Tétouan',
        'url' => home_url('/group-deals/?deal=chefchaouen-tetouan-groupe'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Maroc', 'duration' => '5 jours / 4 nuits',
        'excerpt' => 'Partez à la découverte du bleu de Chefchaouen et de l\'histoire de Tétouan en groupe.',
        'policy' => 'Presque garanti', 'price_from' => 1490, 'price_label' => '1 490',
        'old_price_label' => '1 990', 'discount_percent' => 25,
        'is_featured' => false, 'is_guaranteed' => false,
        'services' => ['Hôtel inclus', 'Transfert', 'Guide'],
        'min_people' => 8, 'max_people' => 12, 'threshold_ratio' => 67,
        'status' => 'almost', 'status_label' => 'Presque garanti', 'status_class' => 'almost',
        'current_people' => 8, 'progress_percent' => 67,
    ],
    [
        'id' => 1003, 'title' => 'Dakhla Surf & Désert',
        'url' => home_url('/group-deals/?deal=dakhla-surf-desert'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Maroc', 'duration' => '4 jours / 3 nuits',
        'excerpt' => 'Kitesurf, dunes et lagons cristallins : l\'aventure Dakhla en groupe limité.',
        'policy' => 'Dernières places', 'price_from' => 2390, 'price_label' => '2 390',
        'old_price_label' => '2 890', 'discount_percent' => 17,
        'is_featured' => false, 'is_guaranteed' => false,
        'services' => ['Hôtel inclus', 'Transfert', 'Petit-déjeuner'],
        'min_people' => 14, 'max_people' => 16, 'threshold_ratio' => 88,
        'status' => 'last', 'status_label' => 'Dernières places', 'status_class' => 'last',
        'current_people' => 14, 'progress_percent' => 88,
    ],
    [
        'id' => 1004, 'title' => 'Istanbul City Break',
        'url' => home_url('/group-deals/?deal=istanbul-city-break'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Turquie', 'duration' => '4 jours / 3 nuits',
        'excerpt' => 'Un city break fascinant entre mosquées, bazars et délices turcs.',
        'policy' => 'Garanti', 'price_from' => 2890, 'price_label' => '2 890',
        'old_price_label' => '3 790', 'discount_percent' => 24,
        'is_featured' => false, 'is_guaranteed' => true,
        'services' => ['Vol inclus', 'Hôtel inclus', 'Transfert', 'Guide'],
        'min_people' => 15, 'max_people' => 20, 'threshold_ratio' => 75,
        'status' => 'guaranteed', 'status_label' => 'Garanti', 'status_class' => 'guaranteed',
        'current_people' => 15, 'progress_percent' => 75,
    ],
    [
        'id' => 1005, 'title' => 'Omra Groupe Économique',
        'url' => home_url('/group-deals/?deal=omra-groupe-economique'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Arabie Saoudite', 'duration' => '10 jours / 9 nuits',
        'excerpt' => 'Un pèlerinage Omra en groupe avec accompagnement spirituel et logistique complet.',
        'policy' => 'Sur demande', 'price_from' => 11900, 'price_label' => '11 900',
        'old_price_label' => '13 500', 'discount_percent' => 12,
        'is_featured' => false, 'is_guaranteed' => false,
        'services' => ['Vol inclus', 'Hôtel inclus', 'Transfert', 'Guide', 'Petit-déjeuner'],
        'min_people' => 18, 'max_people' => 40, 'threshold_ratio' => 45,
        'status' => 'request', 'status_label' => 'Sur demande', 'status_class' => 'request',
        'current_people' => 18, 'progress_percent' => 45,
    ],
    [
        'id' => 1006, 'title' => 'Andalousie en groupe',
        'url' => home_url('/group-deals/?deal=andalousie-groupe'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Espagne', 'duration' => '6 jours / 5 nuits',
        'excerpt' => 'Grenade, Séville, Cordoue : un voyage culturel et festif en groupe.',
        'policy' => 'Promo groupe', 'price_from' => 3490, 'price_label' => '3 490',
        'old_price_label' => '4 290', 'discount_percent' => 19,
        'is_featured' => false, 'is_guaranteed' => false,
        'services' => ['Vol inclus', 'Hôtel inclus', 'Transfert', 'Guide'],
        'min_people' => 9, 'max_people' => 20, 'threshold_ratio' => 45,
        'status' => 'promo', 'status_label' => 'Promo groupe', 'status_class' => 'promo',
        'current_people' => 9, 'progress_percent' => 45,
    ],
    [
        'id' => 1007, 'title' => 'Paris Week-end Groupe',
        'url' => home_url('/group-deals/?deal=paris-weekend-groupe'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'France', 'duration' => '3 jours / 2 nuits',
        'excerpt' => 'Un week-end parisien entre amis ou collègues avec visites et détente.',
        'policy' => 'Presque garanti', 'price_from' => 3990, 'price_label' => '3 990',
        'old_price_label' => '4 690', 'discount_percent' => 15,
        'is_featured' => false, 'is_guaranteed' => false,
        'services' => ['Vol inclus', 'Hôtel inclus', 'Transfert'],
        'min_people' => 10, 'max_people' => 14, 'threshold_ratio' => 71,
        'status' => 'almost', 'status_label' => 'Presque garanti', 'status_class' => 'almost',
        'current_people' => 10, 'progress_percent' => 71,
    ],
    [
        'id' => 1008, 'title' => 'Zanzibar Évasion Groupe',
        'url' => home_url('/group-deals/?deal=zanzibar-evasion-groupe'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Tanzanie', 'duration' => '6 jours / 5 nuits',
        'excerpt' => 'Plages de rêve, épices et détente sur l\'île aux parfums en groupe.',
        'policy' => 'En cours', 'price_from' => 4590, 'price_label' => '4 590',
        'old_price_label' => '6 590', 'discount_percent' => 30,
        'is_featured' => false, 'is_guaranteed' => false,
        'services' => ['Vol inclus', 'Hôtel inclus', 'Transfert', 'Guide'],
        'min_people' => 10, 'max_people' => 18, 'threshold_ratio' => 56,
        'status' => 'progress', 'status_label' => 'En cours', 'status_class' => 'progress',
        'current_people' => 10, 'progress_percent' => 56,
    ],
    [
        'id' => 1009, 'title' => 'Cappadoce & Istanbul',
        'url' => home_url('/group-deals/?deal=cappadoce-istanbul'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Turquie', 'duration' => '7 jours / 6 nuits',
        'excerpt' => 'Montgolfières en Cappadoce et trésors d\'Istanbul : le meilleur de la Turquie.',
        'policy' => 'Garanti', 'price_from' => 5290, 'price_label' => '5 290',
        'old_price_label' => '6 290', 'discount_percent' => 16,
        'is_featured' => false, 'is_guaranteed' => true,
        'services' => ['Vol inclus', 'Hôtel inclus', 'Transfert', 'Guide', 'Petit-déjeuner'],
        'min_people' => 16, 'max_people' => 22, 'threshold_ratio' => 73,
        'status' => 'guaranteed', 'status_label' => 'Garanti', 'status_class' => 'guaranteed',
        'current_people' => 16, 'progress_percent' => 73,
    ],
    [
        'id' => 1010, 'title' => 'Agadir Team Building',
        'url' => home_url('/group-deals/?deal=agadir-team-building'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Maroc', 'duration' => '3 jours / 2 nuits',
        'excerpt' => 'Séminaire, activités nautiques et détente : le séjour entreprise idéal à Agadir.',
        'policy' => 'Sur demande', 'price_from' => 990, 'price_label' => '990',
        'old_price_label' => '1 290', 'discount_percent' => 23,
        'is_featured' => false, 'is_guaranteed' => false,
        'services' => ['Hôtel inclus', 'Transfert', 'Guide'],
        'min_people' => 20, 'max_people' => 50, 'threshold_ratio' => 40,
        'status' => 'request', 'status_label' => 'Entreprise', 'status_class' => 'request',
        'current_people' => 20, 'progress_percent' => 40,
    ],
    [
        'id' => 1011, 'title' => 'Égypte Pyramides & Nil',
        'url' => home_url('/group-deals/?deal=egypte-pyramides-nil'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Égypte', 'duration' => '7 jours / 6 nuits',
        'excerpt' => 'Croisière sur le Nil, pyramides de Gizeh et trésors pharaoniques en groupe.',
        'policy' => 'Promo groupe', 'price_from' => 4990, 'price_label' => '4 990',
        'old_price_label' => '5 990', 'discount_percent' => 17,
        'is_featured' => false, 'is_guaranteed' => false,
        'services' => ['Vol inclus', 'Hôtel inclus', 'Transfert', 'Guide'],
        'min_people' => 11, 'max_people' => 25, 'threshold_ratio' => 44,
        'status' => 'promo', 'status_label' => 'Promo groupe', 'status_class' => 'promo',
        'current_people' => 11, 'progress_percent' => 44,
    ],
    [
        'id' => 1012, 'title' => 'Antalya Resort Groupe',
        'url' => home_url('/group-deals/?deal=antalya-resort-groupe'),
        'image_url' => AJTH_URL . 'assets/images/fallback-voyage.svg',
        'destination' => 'Turquie', 'duration' => '5 jours / 4 nuits',
        'excerpt' => 'All-inclusive, plages et soleil à Antalya : le groupe au complet !',
        'policy' => 'Complet', 'price_from' => 3990, 'price_label' => '3 990',
        'old_price_label' => '4 890', 'discount_percent' => 18,
        'is_featured' => false, 'is_guaranteed' => false,
        'services' => ['Vol inclus', 'Hôtel inclus', 'Transfert', 'Petit-déjeuner'],
        'min_people' => 20, 'max_people' => 20, 'threshold_ratio' => 100,
        'status' => 'full', 'status_label' => 'Complet', 'status_class' => 'full',
        'current_people' => 20, 'progress_percent' => 100,
    ],
];

if (count($deals) < 8) {
    $existing_titles = array_map(static fn ($d) => function_exists('mb_strtolower') ? mb_strtolower($d['title'], 'UTF-8') : strtolower($d['title']), $deals);
    $needed = 12 - count($deals);
    $added = 0;
    foreach ($mock_deals_pool as $mock) {
        if ($added >= $needed) {
            break;
        }
        $mock_title = function_exists('mb_strtolower') ? mb_strtolower($mock['title'], 'UTF-8') : strtolower($mock['title']);
        if (in_array($mock_title, $existing_titles, true)) {
            continue;
        }
        $deals[] = $mock;
        $added++;
    }
    $found_posts = count($deals);
    $max_num_pages = 1;
}

$visible_featured = 0;
$visible_promos = 0;
$visible_guaranteed = 0;
foreach ($deals as $deal) {
    if ($deal['is_featured']) {
        $visible_featured++;
    }
    if ($deal['discount_percent'] > 0) {
        $visible_promos++;
    }
    if ($deal['is_guaranteed']) {
        $visible_guaranteed++;
    }
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
foreach ($selected_services as $service_key) {
    if (! isset($service_catalog[$service_key])) {
        continue;
    }
    $args = $current_args;
    $args['service'] = array_values(array_diff($selected_services, [$service_key]));
    if (empty($args['service'])) {
        unset($args['service']);
    }
    $active_chips[] = ['label' => $service_catalog[$service_key]['label'], 'url' => $build_url($args)];
}

$sort_labels = [
    'recommended' => 'Recommandées',
    'price_asc' => 'Prix croissant',
    'price_desc' => 'Prix décroissant',
    'discount_desc' => 'Réduction la plus forte',
    'newest' => 'Plus récentes',
];

// CTA devis groupe : WhatsApp agent > page contact > fallback
$_cta_agent = get_option('ajth_agent_settings', []);
$cta_devis_url = ! empty($_cta_agent['whatsapp_url']) ? (string) $_cta_agent['whatsapp_url'] : '';
if ($cta_devis_url === '') {
    $_cp = get_page_by_path('contact');
    $cta_devis_url = $_cp ? (string) get_permalink($_cp) : 'https://wa.me/212539323874';
}
$cta_is_external = strpos($cta_devis_url, 'wa.me') !== false || strpos($cta_devis_url, 'whatsapp') !== false;
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-groupdeals-page">
        <?php ajth_render_site_header($settings); ?>

        <div class="aj-groupdeals-fusion" id="aj-groupdeals-fusion">

            <!-- ===== HERO ===== -->
            <section class="ajgd-hero">
                <div class="ajgd-container">
                    <div class="ajgd-hero-grid">
                        <div class="ajgd-hero-copy">
                            <span class="ajgd-eyebrow">&#9992;&#65039; Group Deals Ajinsafro</span>
                            <h1 class="ajgd-hero-title">Plus on est nombreux,<span> plus on voyage malin&nbsp;!</span></h1>
                            <p class="ajgd-hero-sub">Rejoignez des voyageurs comme vous et économisez jusqu'à&nbsp;50&nbsp;%. Plus le groupe grandit, plus le prix baisse pour tout le monde.</p>
                            <div class="ajgd-hero-actions">
                                <a class="ajgd-btn ajgd-btn--orange" href="#ajgd-offres">Découvrir les offres</a>
                                <a class="ajgd-btn ajgd-btn--ghost" href="#ajgd-comment">Comment ça marche&nbsp;?</a>
                            </div>
                        </div>
                        <div class="ajgd-hero-visual" aria-hidden="true">
                            <div class="ajgd-hero-photo"></div>
                            <div class="ajgd-float-card ajgd-float-card--tl">
                                <span class="ajgd-fi ajgd-fi--green">%</span>
                                <div><small>Économies</small><strong>Jusqu'à 50&nbsp;%</strong></div>
                            </div>
                            <div class="ajgd-float-card ajgd-float-card--tr">
                                <span class="ajgd-fi ajgd-fi--blue">&#128100;</span>
                                <div><small>Voyageurs rejoints</small><strong>2&nbsp;458</strong></div>
                            </div>
                            <div class="ajgd-float-card ajgd-float-card--bl">
                                <span class="ajgd-fi ajgd-fi--green">&#10003;</span>
                                <div><small>Départ garanti</small><strong>dès 10 pers.</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== SEARCH PANEL ===== -->
            <div class="ajgd-search-wrap">
                <div class="ajgd-container">
                    <form class="ajgd-search-box" method="get" action="<?php echo esc_url($group_deals_url); ?>">
                        <?php foreach ($current_args as $key => $value) {
                            if (in_array($key, ['s', 'dest', 'price_max', 'group_size', 'paged'], true)) {
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
                            <label for="ajgd-s">Destination ou thème</label>
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
                            <select id="ajgd-type">
                                <option value="">Tous les types</option>
                                <option>Famille</option>
                                <option>Amis</option>
                                <option>Entreprise</option>
                                <option>Association</option>
                                <option>Groupe scolaire</option>
                                <option>Omra groupe</option>
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

            <!-- ===== COMMENT ÇA MARCHE ===== -->
            <section class="ajgd-how" id="ajgd-comment">
                <div class="ajgd-container">
                    <div class="ajgd-section-hd">
                        <h2>Comment ça marche&nbsp;?</h2>
                    </div>
                    <div class="ajgd-how-grid">
                        <article class="ajgd-how-card">
                            <div class="ajgd-how-icon">&#8595;</div>
                            <div>
                                <h3>Le prix baisse</h3>
                                <p>Plus vous êtes nombreux à rejoindre le groupe, plus le prix par personne baisse. Tout le monde y gagne&nbsp;!</p>
                            </div>
                        </article>
                        <article class="ajgd-how-card">
                            <div class="ajgd-how-icon">&#10003;</div>
                            <div>
                                <h3>Départ garanti</h3>
                                <p>Dès que le nombre minimum de participants est atteint, le départ est confirmé et vous êtes informés.</p>
                            </div>
                        </article>
                        <article class="ajgd-how-card">
                            <div class="ajgd-how-icon">&#128200;</div>
                            <div>
                                <h3>Économies transparentes</h3>
                                <p>Vous voyez en temps réel les économies réalisées et le prix final pour chaque participant.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- ===== OFFRES + SIDEBAR ===== -->
            <main class="ajgd-container ajgd-main-grid" id="ajgd-offres">

                <aside class="ajgd-filters" id="ajgd-desktop-filters" aria-label="Filtres Group Deals">
                    <div class="ajgd-promo-card">
                        <span class="ajgd-promo-eyebrow">Conseils groupe</span>
                        <strong>Plus vous êtes nombreux, plus l'offre devient intéressante.</strong>
                        <p>Affinez votre budget, vos services inclus et vos destinations pour trouver le meilleur deal.</p>
                    </div>
                    <div class="ajgd-filter-title">
                        <h2>Filtrer par</h2>
                        <a class="ajgd-clear-link" href="<?php echo esc_url($group_deals_url); ?>">Tout effacer</a>
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
                                <h2><?php echo esc_html(number_format_i18n(count($deals))); ?> offres groupe trouvées</h2>
                                <div class="ajgd-result-count">
                                    <?php echo $min_price_found !== null
                                        ? esc_html('À partir de ' . number_format_i18n((int) $min_price_found) . ' DH par personne')
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
                            <span class="ajgd-stat-pill"><?php echo esc_html(number_format_i18n(count($deals))); ?> offres visibles</span>
                            <span class="ajgd-stat-pill"><?php echo esc_html(number_format_i18n($visible_featured)); ?> sélections Ajinsafro</span>
                            <span class="ajgd-stat-pill"><?php echo esc_html(number_format_i18n($visible_promos)); ?> réductions actives</span>
                            <span class="ajgd-stat-pill"><?php echo esc_html(number_format_i18n($visible_guaranteed)); ?> départs garantis</span>
                        </div>
                        <?php if (! empty($active_chips)) { ?>
                            <div class="ajgd-chips">
                                <?php foreach ($active_chips as $chip) { ?>
                                    <a class="ajgd-chip" href="<?php echo esc_url($chip['url']); ?>">
                                        <?php echo esc_html($chip['label']); ?> &times;
                                    </a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <?php if ($voyages_table === null && empty($deals)) { ?>
                        <div class="ajgd-empty-state">
                            <div class="ajgd-empty-icon">&#128274;</div>
                            <h3>Données indisponibles</h3>
                            <p>La source group deals n'est pas accessible depuis WordPress pour le moment.</p>
                        </div>
                    <?php } elseif (empty($deals)) { ?>
                        <div class="ajgd-empty-state">
                            <div class="ajgd-empty-icon">&#128269;</div>
                            <h3>Aucun group deal trouvé</h3>
                            <p>Essayez une autre destination, un budget plus large ou supprimez certains filtres.</p>
                            <a class="ajgd-btn ajgd-btn--orange" href="<?php echo esc_url($group_deals_url); ?>">Réinitialiser les filtres</a>
                        </div>
                    <?php } else { ?>
                        <div class="ajgd-deals-grid">
                            <?php foreach ($deals as $deal) { ?>
                                <article class="ajgd-card<?php echo $deal['url'] === '' ? ' is-disabled' : ''; ?>">
                                    <div class="ajgd-card__media<?php echo strpos($deal['image_url'], 'fallback-voyage.svg') !== false ? ' is-fallback' : ''; ?>">
                                        <img src="<?php echo esc_url($deal['image_url']); ?>" alt="<?php echo esc_attr($deal['title']); ?>" loading="lazy">
                                        <div class="ajgd-card__badges">
                                            <?php if (!empty($deal['status'])) { ?>
                                                <span class="ajgd-badge ajgd-badge--<?php echo esc_attr($deal['status_class'] ?? $deal['status']); ?>"><?php echo esc_html($deal['status_label'] ?? ''); ?></span>
                                            <?php } ?>
                                            <?php if ($deal['discount_percent'] > 0) { ?>
                                                <span class="ajgd-badge ajgd-badge--orange">-<?php echo esc_html((string) $deal['discount_percent']); ?>%</span>
                                            <?php } ?>
                                            <?php if ($deal['is_guaranteed'] && empty($deal['status'])) { ?>
                                                <span class="ajgd-badge ajgd-badge--green">&#10003; Garanti</span>
                                            <?php } ?>
                                            <?php if ($deal['is_featured']) { ?>
                                                <span class="ajgd-badge ajgd-badge--blue">Sélection Ajinsafro</span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="ajgd-card__body">
                                        <?php if ($deal['destination'] !== '') { ?>
                                            <p class="ajgd-card__dest">&#128205; <?php echo esc_html($deal['destination']); ?></p>
                                        <?php } ?>
                                        <h3 class="ajgd-card__title"><?php echo esc_html($deal['title']); ?></h3>
                                        <div class="ajgd-card__meta">
                                            <?php if ($deal['duration'] !== '') { ?>
                                                <span>&#9201; <?php echo esc_html($deal['duration']); ?></span>
                                            <?php } ?>
                                            <?php if ($deal['max_people'] > 0) { ?>
                                                <span>
                                                    &#128100;
                                                    <?php
                                                    $current_p = $deal['current_people'] ?? $deal['min_people'] ?? 0;
                                                    echo esc_html($current_p . '/' . $deal['max_people'] . ' participants');
                                                    ?>
                                                </span>
                                            <?php } ?>
                                        </div>

                                        <?php if ($deal['excerpt'] !== '') { ?>
                                            <p class="ajgd-card__excerpt"><?php echo esc_html($deal['excerpt']); ?></p>
                                        <?php } ?>

                                        <?php if (! empty($deal['services'])) { ?>
                                            <div class="ajgd-card__tags">
                                                <?php foreach ($deal['services'] as $svc) { ?>
                                                    <span><?php echo esc_html($svc); ?></span>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>

                                        <?php if ($deal['max_people'] > 0) { ?>
                                            <div class="ajgd-card__progress">
                                                <div class="ajgd-card__progress-top">
                                                    <span><?php echo !empty($deal['status_label']) ? esc_html($deal['status_label']) : 'Seuil de départ'; ?></span>
                                                    <span><?php echo esc_html((string) ($deal['current_people'] ?? $deal['min_people'] ?? 0)); ?> / <?php echo esc_html((string) $deal['max_people']); ?></span>
                                                </div>
                                                <div class="ajgd-card__progress-bar">
                                                    <span style="width:<?php echo esc_attr((string) ($deal['progress_percent'] ?? $deal['threshold_ratio'] ?? 0)); ?>%"></span>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <div class="ajgd-card__footer">
                                            <div class="ajgd-card__price">
                                                <?php if ($deal['old_price_label'] !== '') { ?>
                                                    <del><?php echo esc_html($deal['old_price_label']); ?> DH</del>
                                                <?php } ?>
                                                <small>À partir de</small>
                                                <strong><?php echo $deal['price_label'] !== '' ? esc_html($deal['price_label'] . ' DH') : 'Prix sur demande'; ?></strong>
                                                <span>par personne</span>
                                                <span class="ajgd-card__group-hint">Plus le groupe grandit, plus le tarif baisse.</span>
                                            </div>
                                            <div class="ajgd-card__actions">
                                                <?php if ($deal['url'] !== '') { ?>
                                                    <a class="ajgd-btn ajgd-btn--outline-blue ajgd-btn--sm" href="<?php echo esc_url($deal['url']); ?>">Voir l'offre</a>
                                                    <?php if (($deal['status'] ?? '') === 'full') { ?>
                                                        <span class="ajgd-btn ajgd-btn--orange ajgd-btn--sm is-disabled">Complet</span>
                                                    <?php } elseif (($deal['status'] ?? '') === 'request') { ?>
                                                        <a class="ajgd-btn ajgd-btn--orange ajgd-btn--sm" href="<?php echo esc_url($cta_devis_url); ?>" <?php if ($cta_is_external) { echo 'target="_blank" rel="noopener noreferrer"'; } ?>>Demander un devis</a>
                                                    <?php } else { ?>
                                                        <a class="ajgd-btn ajgd-btn--orange ajgd-btn--sm" href="<?php echo esc_url($deal['url']); ?>">Réserver</a>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <span class="ajgd-btn ajgd-btn--outline-blue ajgd-btn--sm is-disabled">Lien indisponible</span>
                                                <?php } ?>
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
                            <nav class="ajgd-pagination" aria-label="Pagination Group Deals">
                                <?php foreach ($pagination as $page_link) {
                                    echo wp_kses_post($page_link);
                                } ?>
                            </nav>
                        <?php } ?>
                    <?php } ?>
                </section>
            </main>

            <!-- ===== POURQUOI AJINSAFRO ===== -->
            <section class="ajgd-benefits">
                <div class="ajgd-container">
                    <div class="ajgd-section-hd">
                        <h2>Pourquoi choisir Ajinsafro Group Deals&nbsp;?</h2>
                    </div>
                    <div class="ajgd-benefits-grid">
                        <div class="ajgd-benefit">
                            <span class="ajgd-benefit-icon">&#128176;</span>
                            <div>
                                <h3>Tarifs négociés</h3>
                                <p>Des prix exclusifs obtenus grâce au pouvoir du groupe.</p>
                            </div>
                        </div>
                        <div class="ajgd-benefit">
                            <span class="ajgd-benefit-icon">&#128736;</span>
                            <div>
                                <h3>Accompagnement Ajinsafro</h3>
                                <p>Une équipe dédiée vous accompagne avant, pendant et après le voyage.</p>
                            </div>
                        </div>
                        <div class="ajgd-benefit">
                            <span class="ajgd-benefit-icon">&#128100;</span>
                            <div>
                                <h3>Groupes privés</h3>
                                <p>Partez entre amis, famille, collègues ou communauté selon vos envies.</p>
                            </div>
                        </div>
                        <div class="ajgd-benefit">
                            <span class="ajgd-benefit-icon">&#128179;</span>
                            <div>
                                <h3>Paiement flexible</h3>
                                <p>Réservez avec un acompte et payez en plusieurs fois.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== CHIFFRES CLÉS ===== -->
            <section class="ajgd-stats">
                <div class="ajgd-container">
                    <h2 class="ajgd-stats-title">Ajinsafro Group Deals en chiffres</h2>
                    <div class="ajgd-stats-grid">
                        <div class="ajgd-stat">
                            <strong>5&nbsp;000+</strong>
                            <span>Voyageurs satisfaits</span>
                        </div>
                        <div class="ajgd-stat">
                            <strong>38&nbsp;%</strong>
                            <span>Économies moyennes</span>
                        </div>
                        <div class="ajgd-stat">
                            <strong>320+</strong>
                            <span>Groupes confirmés</span>
                        </div>
                        <div class="ajgd-stat">
                            <strong>45+</strong>
                            <span>Destinations disponibles</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== CTA FINAL ===== -->
            <section class="ajgd-final-cta">
                <div class="ajgd-container">
                    <div class="ajgd-cta-box">
                        <div class="ajgd-cta-deco" aria-hidden="true"></div>
                        <div class="ajgd-cta-content">
                            <h2>Prêt à voyager en groupe&nbsp;?</h2>
                            <p>Demandez un devis personnalisé et profitez du meilleur tarif.</p>
                        </div>
                        <a class="ajgd-btn ajgd-btn--orange ajgd-btn--lg"
                           href="<?php echo esc_url($cta_devis_url); ?>"
                           <?php if ($cta_is_external) { echo 'target="_blank" rel="noopener noreferrer"'; } ?>>
                            Demander un devis groupe &#8594;
                        </a>
                    </div>
                </div>
            </section>

            <!-- ===== MOBILE FILTER ===== -->
            <button class="ajgd-mobile-filter-btn" type="button" id="ajgd-open-filters">&#9776; Filtres &amp; tri</button>
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

        </div>
    </div>
</div>

<?php get_footer(); ?>
