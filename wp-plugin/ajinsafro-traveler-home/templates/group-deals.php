<?php
/**
 * Group Deals Page Template
 *
 * Premium hybrid layout mixing Ajinsafro booking UI and a modern group-deal grid.
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
    'recommended' => 'Recommandees',
    'price_asc' => 'Prix croissant',
    'price_desc' => 'Prix decroissant',
    'discount_desc' => 'Reduction la plus forte',
    'newest' => 'Plus recentes',
];
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-groupdeals-page">
        <?php ajth_render_site_header($settings); ?>

        <div class="aj-groupdeals-fusion" id="aj-groupdeals-fusion">
            <section class="hero">
                <div class="container">
                    <div class="hero-copy">
                        <span class="hero-eyebrow">Group Deals Ajinsafro</span>
                        <h1 class="hero-title">Voyagez en groupe, payez moins</h1>
                        <p class="hero-subtitle">Parcourez nos departs groupes, reperez les meilleures reductions et reservez des sejours penses pour les voyageurs Ajinsafro.</p>
                    </div>

                    <form class="search-panel" method="get" action="<?php echo esc_url($group_deals_url); ?>">
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
                        <div class="search-field search-field--wide">
                            <label for="ajgd-search">Recherche</label>
                            <input id="ajgd-search" name="s" type="text" value="<?php echo esc_attr($search_text); ?>" placeholder="Ville, pays, destination, theme...">
                        </div>
                        <div class="search-field">
                            <label for="ajgd-destination">Destination</label>
                            <select id="ajgd-destination" name="dest">
                                <option value="">Toutes les destinations</option>
                                <?php foreach ($available_destinations as $destination_option) { ?>
                                    <option value="<?php echo esc_attr($destination_option); ?>" <?php selected($dest, $destination_option); ?>><?php echo esc_html($destination_option); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="search-field">
                            <label for="ajgd-travelers">Voyageurs</label>
                            <input id="ajgd-travelers" name="group_size" type="number" min="0" value="<?php echo $group_size > 0 ? esc_attr((string) $group_size) : ''; ?>" placeholder="2 voyageurs">
                        </div>
                        <div class="search-field">
                            <label for="ajgd-budget-max">Budget max</label>
                            <input id="ajgd-budget-max" name="price_max" type="number" min="0" value="<?php echo $price_max > 0 ? esc_attr((string) $price_max) : ''; ?>" placeholder="Budget max">
                        </div>
                        <button class="search-btn" type="submit">Rechercher</button>
                    </form>
                </div>
            </section>

            <main class="container main-grid">
                <aside class="filters" id="ajgd-desktop-filters" aria-label="Filtres Group Deals">
                    <div class="promo-card promo-card--soft">
                        <span class="promo-card__eyebrow">Conseils groupe</span>
                        <strong>Plus vous etes nombreux, plus l'offre devient interessante.</strong>
                        <p>Affinez votre budget, vos services inclus et vos destinations pour trouver le meilleur deal.</p>
                    </div>
                    <div class="filter-title">
                        <h2>Filtrer par</h2>
                        <a class="clear-link" href="<?php echo esc_url($group_deals_url); ?>">Tout effacer</a>
                    </div>
                    <?php
                    $group_deals_filter_prefix = 'ajgd-desktop';
                    include AJTH_DIR . 'parts/group-deals-filters.php';
                    ?>
                </aside>

                <section class="results">
                    <div class="results-head">
                        <div class="results-topline">
                            <div>
                                <h2><?php echo esc_html(number_format_i18n($found_posts)); ?> group deals trouves</h2>
                                <div class="result-count">
                                    <?php echo $min_price_found !== null
                                        ? esc_html('A partir de ' . number_format_i18n((int) $min_price_found) . ' DH par personne')
                                        : esc_html('Tarifs disponibles selon l offre'); ?>
                                </div>
                            </div>
                            <form class="sort-wrap" method="get" action="<?php echo esc_url($group_deals_url); ?>">
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
                        <div class="stat-pills">
                            <span class="stat-pill"><?php echo esc_html(number_format_i18n(count($deals))); ?> offres visibles</span>
                            <span class="stat-pill"><?php echo esc_html(number_format_i18n($visible_featured)); ?> selections Ajinsafro</span>
                            <span class="stat-pill"><?php echo esc_html(number_format_i18n($visible_promos)); ?> reductions actives</span>
                            <span class="stat-pill"><?php echo esc_html(number_format_i18n($visible_guaranteed)); ?> departs garantis</span>
                        </div>
                        <?php if (! empty($active_chips)) { ?>
                            <div class="chips">
                                <?php foreach ($active_chips as $chip) { ?>
                                    <a class="chip" href="<?php echo esc_url($chip['url']); ?>">
                                        <span><?php echo esc_html($chip['label']); ?></span>
                                        <span aria-hidden="true">x</span>
                                    </a>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="deal-strip">
                        <div>
                            <strong>Offres groupe selectionnees pour la communaute Ajinsafro</strong>
                            <span>Reperez rapidement les voyages mis en avant, les promos en cours et les offres avec services inclus.</span>
                        </div>
                        <a href="#ajgd-results">Explorer</a>
                    </div>

                    <?php if ($voyages_table === null) { ?>
                        <div class="empty-state empty-state--visible">
                            <h3>Donnees indisponibles</h3>
                            <p>La source group deals n'est pas accessible depuis WordPress pour le moment.</p>
                        </div>
                    <?php } elseif (empty($deals)) { ?>
                        <div class="empty-state empty-state--visible">
                            <h3>Aucun group deal trouve</h3>
                            <p>Essayez une autre destination, un budget plus large ou supprimez certains filtres.</p>
                            <a class="primary-btn" href="<?php echo esc_url($group_deals_url); ?>">Reinitialiser les filtres</a>
                        </div>
                    <?php } else { ?>
                        <div class="deals-grid" id="ajgd-results">
                            <?php foreach ($deals as $deal) { ?>
                                <article class="group-card<?php echo $deal['url'] === '' ? ' is-disabled' : ''; ?>">
                                    <div class="group-card__media<?php echo strpos($deal['image_url'], 'fallback-voyage.svg') !== false ? ' is-fallback' : ''; ?>">
                                        <img src="<?php echo esc_url($deal['image_url']); ?>" alt="<?php echo esc_attr($deal['title']); ?>" loading="lazy">
                                        <div class="group-card__badges">
                                            <?php if ($deal['discount_percent'] > 0) { ?><span class="badge badge--discount">-<?php echo esc_html((string) $deal['discount_percent']); ?>%</span><?php } ?>
                                            <?php if ($deal['is_guaranteed']) { ?><span class="badge badge--success">Garanti</span><?php } ?>
                                            <?php if ($deal['is_featured']) { ?><span class="badge badge--dark">Ajinsafro selection</span><?php } ?>
                                        </div>
                                    </div>
                                    <div class="group-card__body">
                                        <div class="group-card__head">
                                            <div>
                                                <?php if ($deal['destination'] !== '') { ?><p class="group-card__location"><?php echo esc_html($deal['destination']); ?></p><?php } ?>
                                                <h3><?php echo esc_html($deal['title']); ?></h3>
                                            </div>
                                            <span class="group-card__type">Group deal</span>
                                        </div>

                                        <div class="group-card__meta">
                                            <?php if ($deal['duration'] !== '') { ?><span><?php echo esc_html($deal['duration']); ?></span><?php } ?>
                                            <?php if ($deal['min_people'] > 0 || $deal['max_people'] > 0) { ?>
                                                <span>
                                                    <?php
                                                    if ($deal['min_people'] > 0 && $deal['max_people'] > 0) {
                                                        echo esc_html($deal['min_people'] . '-' . $deal['max_people'] . ' participants');
                                                    } elseif ($deal['max_people'] > 0) {
                                                        echo esc_html('Jusqu a ' . $deal['max_people'] . ' participants');
                                                    } else {
                                                        echo esc_html('A partir de ' . $deal['min_people'] . ' participants');
                                                    }
                                                    ?>
                                                </span>
                                            <?php } ?>
                                        </div>

                                        <?php if ($deal['excerpt'] !== '') { ?><p class="group-card__description"><?php echo esc_html($deal['excerpt']); ?></p><?php } ?>

                                        <?php if ($deal['policy'] !== '') { ?><p class="group-card__policy"><?php echo esc_html($deal['policy']); ?></p><?php } ?>

                                        <?php if (! empty($deal['services'])) { ?>
                                            <div class="group-card__tags">
                                                <?php foreach ($deal['services'] as $service_label) { ?>
                                                    <span><?php echo esc_html($service_label); ?></span>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>

                                        <?php if ($deal['threshold_ratio'] > 0) { ?>
                                            <div class="group-card__progress">
                                                <div class="group-card__progress-top">
                                                    <span>Seuil de depart</span>
                                                    <span><?php echo esc_html((string) $deal['min_people']); ?> / <?php echo esc_html((string) $deal['max_people']); ?></span>
                                                </div>
                                                <div class="group-card__progress-bar"><span style="width: <?php echo esc_attr((string) $deal['threshold_ratio']); ?>%"></span></div>
                                            </div>
                                        <?php } ?>

                                        <div class="group-card__footer">
                                            <div class="group-card__price-block">
                                                <?php if ($deal['old_price_label'] !== '') { ?><span class="group-card__old-price"><?php echo esc_html($deal['old_price_label']); ?> DH</span><?php } ?>
                                                <small>A partir de</small>
                                                <strong><?php echo $deal['price_label'] !== '' ? esc_html($deal['price_label'] . ' DH') : 'Prix sur demande'; ?></strong>
                                                <span>par personne</span>
                                            </div>
                                            <div class="group-card__actions">
                                                <?php if ($deal['url'] !== '') { ?>
                                                    <a class="secondary-btn" href="<?php echo esc_url($deal['url']); ?>">Voir l'offre</a>
                                                    <a class="primary-btn" href="<?php echo esc_url($deal['url']); ?>">Reserver</a>
                                                <?php } else { ?>
                                                    <span class="secondary-btn is-disabled">Lien indisponible</span>
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
                            <nav class="pagination" aria-label="Pagination Group Deals">
                                <?php foreach ($pagination as $page_link) {
                                    echo wp_kses_post($page_link);
                                } ?>
                            </nav>
                        <?php } ?>
                    <?php } ?>
                </section>
            </main>

            <button class="mobile-filter-btn" type="button" id="ajgd-open-filters">Filtres & tri</button>
            <div class="drawer-backdrop" id="ajgd-drawer-backdrop"></div>
            <aside class="mobile-drawer" id="ajgd-mobile-drawer" aria-label="Filtres mobile">
                <div class="drawer-head">
                    <h3>Filtres</h3>
                    <button type="button" id="ajgd-close-filters">x</button>
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
