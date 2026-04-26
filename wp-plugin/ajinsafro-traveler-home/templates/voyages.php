<?php
/**
 * Voyages Page Template
 *
 * Professional catalog experience for st_tours with shareable GET filters.
 */
if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('ajinsafro_get_tour_image')) {
    function ajinsafro_get_tour_image(int $post_id): string
    {
        ob_start();
        ajth_render_catalog_card_image($post_id);
        return (string) ob_get_clean();
    }
}

if (! function_exists('ajinsafro_set_voyages_context')) {
    function ajinsafro_set_voyages_context(array $context): void
    {
        $GLOBALS['ajinsafro_voyages_context'] = $context;
    }
}

if (! function_exists('ajinsafro_get_voyages_context')) {
    function ajinsafro_get_voyages_context(): array
    {
        return isset($GLOBALS['ajinsafro_voyages_context']) && is_array($GLOBALS['ajinsafro_voyages_context'])
            ? $GLOBALS['ajinsafro_voyages_context']
            : [];
    }
}

if (! function_exists('ajinsafro_get_tour_destination')) {
    function ajinsafro_get_tour_destination(int $post_id, array $meta = []): string
    {
        if ($meta === []) {
            $context = ajinsafro_get_voyages_context();
            $meta = isset($context['meta_cache'][$post_id]) && is_array($context['meta_cache'][$post_id])
                ? $context['meta_cache'][$post_id]
                : get_post_meta($post_id);
        }

        $taxonomy_names = wp_get_post_terms($post_id, 'location', ['fields' => 'names']);
        if (is_array($taxonomy_names) && ! empty($taxonomy_names)) {
            $name = trim((string) $taxonomy_names[0]);
            if ($name !== '') {
                return $name;
            }
        }

        foreach (['aj_catalog_destination', 'address'] as $key) {
            $value = isset($meta[$key][0]) ? trim((string) $meta[$key][0]) : '';
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

if (! function_exists('ajinsafro_get_tour_price')) {
    function ajinsafro_get_tour_price(int $post_id, ?array $selected_departure = null, ?array $next_departure = null): array
    {
        $context = ajinsafro_get_voyages_context();
        $meta = isset($context['meta_cache'][$post_id]) && is_array($context['meta_cache'][$post_id])
            ? $context['meta_cache'][$post_id]
            : get_post_meta($post_id);
        $departures = isset($context['departure_index'][$post_id]) && is_array($context['departure_index'][$post_id])
            ? $context['departure_index'][$post_id]
            : [];

        $meta_float = static function (array $source, string $key): float {
            if (! isset($source[$key][0])) {
                return 0.0;
            }
            return (float) $source[$key][0];
        };

        $regular_candidates = array_filter([
            $meta_float($meta, 'adult_price'),
            $meta_float($meta, 'min_price'),
            $meta_float($meta, 'price'),
            $meta_float($meta, 'base_price'),
        ], static fn ($value) => $value > 0);

        $sale_price = $meta_float($meta, 'sale_price');
        $regular_price = ! empty($regular_candidates) ? min($regular_candidates) : 0.0;
        $min_departure_price = null;

        foreach ($departures as $departure_row) {
            $row_price = $departure_row['price'] ?? null;
            if ($row_price !== null && (float) $row_price > 0) {
                $min_departure_price = $min_departure_price === null
                    ? (float) $row_price
                    : min($min_departure_price, (float) $row_price);
            }
        }

        $price_from = null;
        $price_source = '';
        if ($selected_departure !== null && ($selected_departure['price'] ?? null) !== null && (float) $selected_departure['price'] > 0) {
            $price_from = (float) $selected_departure['price'];
            $price_source = (string) ($selected_departure['price_source'] ?? 'departure:selected');
        } elseif ($next_departure !== null && ($next_departure['price'] ?? null) !== null && (float) $next_departure['price'] > 0) {
            $price_from = (float) $next_departure['price'];
            $price_source = (string) ($next_departure['price_source'] ?? 'departure:next');
        } elseif ($sale_price > 0) {
            $price_from = $sale_price;
            $price_source = 'meta:sale_price';
        } elseif ($min_departure_price !== null && $min_departure_price > 0) {
            $price_from = $min_departure_price;
            $price_source = 'departure:min';
        } elseif ($regular_price > 0) {
            $price_from = $regular_price;
            $price_source = 'meta:regular';
        }

        $price_reference = $regular_price > 0 ? $regular_price : null;
        if ($price_reference !== null && $price_from !== null && $price_reference <= $price_from) {
            $price_reference = null;
        }

        return [
            'price_from' => $price_from,
            'price_reference' => $price_reference,
            'is_promo' => $price_reference !== null && $price_from !== null && $price_reference > $price_from,
            'price_source' => $price_source,
        ];
    }
}

if (! function_exists('ajinsafro_get_tour_next_departure')) {
    function ajinsafro_get_tour_next_departure(int $post_id, string $today, string $date_filter = '', int $travellers = 0): ?array
    {
        $context = ajinsafro_get_voyages_context();
        $departures = isset($context['departure_index'][$post_id]) && is_array($context['departure_index'][$post_id])
            ? $context['departure_index'][$post_id]
            : [];
        $min_date = $today;
        if ($date_filter !== '' && $date_filter > $min_date) {
            $min_date = $date_filter;
        }

        foreach ($departures as $row) {
            if (empty($row['is_active'])) {
                continue;
            }
            if (($row['date'] ?? '') < $min_date) {
                continue;
            }

            $remaining = $row['remaining_capacity'] ?? null;
            if ($travellers > 0 && ($remaining === null || (int) $remaining < $travellers)) {
                continue;
            }

            return $row;
        }

        return null;
    }
}

if (! function_exists('ajinsafro_get_tour_availability')) {
    function ajinsafro_get_tour_availability(int $post_id, ?array $target_departure, string $today): array
    {
        if ($target_departure === null) {
            return ['label' => 'Sur demande', 'tone' => 'neutral'];
        }

        $date = (string) ($target_departure['date'] ?? '');
        $remaining = $target_departure['remaining_capacity'] ?? null;
        $status_raw = strtolower(trim((string) ($target_departure['status_raw'] ?? '')));

        if ($date !== '' && $date < $today) {
            return ['label' => 'Expire', 'tone' => 'muted'];
        }

        if (in_array($status_raw, ['full', 'closed', 'canceled', 'cancelled'], true)) {
            return ['label' => 'Complet', 'tone' => 'muted'];
        }

        if ($remaining === null) {
            return ['label' => 'Sur demande', 'tone' => 'neutral'];
        }

        if ((int) $remaining <= 0) {
            return ['label' => 'Complet', 'tone' => 'muted'];
        }

        if ((int) $remaining <= 5) {
            return ['label' => 'Places limitees', 'tone' => 'warning'];
        }

        if ($status_raw === 'limited') {
            return ['label' => 'Places limitees', 'tone' => 'warning'];
        }

        return ['label' => 'Disponible', 'tone' => 'success'];
    }
}

get_header();

$settings = ajth_get_settings();
$paged = max(1, absint(get_query_var('paged')), absint(get_query_var('page')), isset($_GET['paged']) ? absint($_GET['paged']) : 0);
$per_page = 12;
$today = current_time('Y-m-d');
$voyages_page_url = function_exists('ajth_get_voyages_page_url')
    ? ajth_get_voyages_page_url()
    : home_url('/voyages/');

$get_text = static function (string $key, string $default = ''): string {
    if (! isset($_GET[$key])) {
        return $default;
    }

    $value = wp_unslash($_GET[$key]);
    if (is_array($value)) {
        return $default;
    }

    return sanitize_text_field((string) $value);
};

$get_int = static function (string $key): int {
    if (! isset($_GET[$key])) {
        return 0;
    }

    return absint($_GET[$key]);
};

$get_bool = static function (string $key): bool {
    return isset($_GET[$key]) && (string) wp_unslash($_GET[$key]) === '1';
};

$get_text_alias = static function (array $keys, string $default = '') use ($get_text): string {
    foreach ($keys as $key) {
        $value = $get_text($key);
        if ($value !== '') {
            return $value;
        }
    }
    return $default;
};

$get_int_alias = static function (array $keys) use ($get_int): int {
    foreach ($keys as $key) {
        $value = $get_int($key);
        if ($value > 0) {
            return $value;
        }
    }
    return 0;
};

$search_text = $get_text('s');
$location_name = $get_text('location_name');
$keyword = $location_name !== '' ? $location_name : $search_text;
$category_slug = $get_text('cat');
$tag_slug = $get_text('tag');
$tour_type_slug = $get_text('tour_type');
$location_id = $get_int('location_id');
$dest = $get_text_alias(['destination', 'dest']);
$depart_date = $get_text_alias(['date_depart', 'depart_date']);
$duration_min = $get_int('duration_min');
$duration_max = $get_int('duration_max');
$price_min = $get_int_alias(['budget_min', 'price_min']);
$price_max = $get_int_alias(['budget_max', 'price_max']);
$guests_min = $get_int_alias(['voyageurs', 'guests_min']);
$featured_only = $get_bool('featured');
$promo_only = $get_bool('promo_only');
$available_only = $get_bool('available_only');
$min_rating = (float) $get_text('min_rating');
$sort = $get_text('sort', $get_text('catalog_orderby', 'recommended'));
$allowed_sorts = ['recommended', 'price_asc', 'price_desc', 'duration_asc', 'duration_desc', 'departure_soonest'];
if (! in_array($sort, $allowed_sorts, true)) {
    $sort = 'recommended';
}
if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $depart_date)) {
    $depart_date = '';
}

$debug_voyages = current_user_can('manage_options')
    && ((defined('WP_DEBUG') && WP_DEBUG) || $get_bool('debug_voyages'));

$current_filters = array_filter([
    's' => $search_text,
    'location_name' => $location_name,
    'cat' => $category_slug,
    'tag' => $tag_slug,
    'tour_type' => $tour_type_slug,
    'location_id' => $location_id > 0 ? (string) $location_id : '',
    'destination' => $dest,
    'date_depart' => $depart_date,
    'duration_min' => $duration_min > 0 ? (string) $duration_min : '',
    'duration_max' => $duration_max > 0 ? (string) $duration_max : '',
    'budget_min' => $price_min > 0 ? (string) $price_min : '',
    'budget_max' => $price_max > 0 ? (string) $price_max : '',
    'voyageurs' => $guests_min > 0 ? (string) $guests_min : '',
    'min_rating' => $min_rating > 0 ? rtrim(rtrim(number_format($min_rating, 1, '.', ''), '0'), '.') : '',
    'featured' => $featured_only ? '1' : '',
    'promo_only' => $promo_only ? '1' : '',
    'available_only' => $available_only ? '1' : '',
    'debug_voyages' => $debug_voyages ? '1' : '',
    'sort' => $sort,
], static fn ($value) => $value !== '' && $value !== null);

$build_url = static function (array $overrides = [], array $remove = []) use ($current_filters, $voyages_page_url): string {
    $args = $current_filters;

    foreach ($remove as $key) {
        unset($args[$key]);
    }

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($args[$key]);
            continue;
        }
        $args[$key] = (string) $value;
    }

    return empty($args) ? $voyages_page_url : add_query_arg($args, $voyages_page_url);
};

$query_args = [
    'post_type' => 'st_tours',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'no_found_rows' => true,
    'ignore_sticky_posts' => true,
    'orderby' => 'date',
    'order' => 'DESC',
];

$tax_query = [];
if ($category_slug !== '') {
    $tax_query[] = [
        'taxonomy' => 'tours_cat',
        'field' => 'slug',
        'terms' => [$category_slug],
    ];
}
if ($tag_slug !== '') {
    $tax_query[] = [
        'taxonomy' => 'tour_tag',
        'field' => 'slug',
        'terms' => [$tag_slug],
    ];
}
if ($tour_type_slug !== '') {
    $tax_query[] = [
        'taxonomy' => 'st_tour_type',
        'field' => 'slug',
        'terms' => [$tour_type_slug],
    ];
}
if (! empty($tax_query)) {
    $query_args['tax_query'] = array_merge(['relation' => 'AND'], $tax_query);
}

$meta_query = [];
if ($featured_only) {
    $meta_query[] = [
        'key' => 'is_featured',
        'value' => 'on',
        'compare' => '=',
    ];
}
if ($location_id > 0) {
    $meta_query[] = [
        'relation' => 'OR',
        ['key' => 'st_location_id', 'value' => (string) $location_id, 'compare' => '='],
        ['key' => 'location_id', 'value' => (string) $location_id, 'compare' => '='],
        ['key' => 'id_location', 'value' => (string) $location_id, 'compare' => '='],
        ['key' => 'multi_location', 'value' => '_' . $location_id . '_', 'compare' => 'LIKE'],
        ['key' => 'multi_location', 'value' => (string) $location_id, 'compare' => 'LIKE'],
    ];
}
if (! empty($meta_query)) {
    $query_args['meta_query'] = array_merge(['relation' => 'AND'], $meta_query);
}

$synced_sample_ids = get_posts([
    'post_type' => 'st_tours',
    'post_status' => 'publish',
    'fields' => 'ids',
    'numberposts' => 1,
    'meta_query' => [[
        'key' => '_aj_laravel_voyage_id',
        'value' => 0,
        'type' => 'NUMERIC',
        'compare' => '>',
    ]],
]);

if (! empty($synced_sample_ids)) {
    $query_args['meta_query'][] = [
        'key' => '_aj_laravel_voyage_id',
        'value' => 0,
        'type' => 'NUMERIC',
        'compare' => '>',
    ];
}

$base_query = new WP_Query($query_args);
$post_ids = array_values(array_filter(array_map('absint', (array) $base_query->posts)));

$departure_index = [];
$departure_debug = [];
$meta_cache = [];
$available_departure_dates = [];
if (! empty($post_ids)) {
    global $wpdb;

    $table_exists = static function (string $table_name) use ($wpdb): bool {
        if ($table_name === '') {
            return false;
        }

        return (bool) $wpdb->get_var($wpdb->prepare(
            'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
            $table_name
        ));
    };

    $get_existing_table = static function (array $candidates) use ($table_exists): string {
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '' && $table_exists($candidate)) {
                return $candidate;
            }
        }

        return '';
    };

    $get_columns = static function (string $table_name) use ($wpdb): array {
        if ($table_name === '') {
            return [];
        }

        $rows = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}", ARRAY_A);
        $columns = [];
        foreach ((array) $rows as $row) {
            if (! empty($row['Field'])) {
                $columns[] = (string) $row['Field'];
            }
        }

        return $columns;
    };

    $merge_departure_row = static function (int $post_id, array $incoming) use (&$departure_index, &$departure_debug): void {
        $travel_date_id = isset($incoming['travel_date_id']) ? (int) $incoming['travel_date_id'] : 0;
        $date_value = isset($incoming['date']) ? trim((string) $incoming['date']) : '';
        if ($post_id <= 0 || $date_value === '') {
            return;
        }

        $row_key = $travel_date_id > 0 ? 'id:' . $travel_date_id : 'date:' . $date_value;
        if (! isset($departure_index[$post_id][$row_key])) {
            $departure_index[$post_id][$row_key] = [
                'travel_date_id' => $travel_date_id,
                'date' => $date_value,
                'is_active' => true,
                'remaining_capacity' => null,
                'total_capacity' => null,
                'reserved_capacity' => null,
                'price' => null,
                'status_raw' => '',
                'price_source' => '',
                'capacity_source' => '',
                'departure_source' => '',
            ];
        }

        foreach (['travel_date_id', 'date'] as $required_key) {
            if (! empty($incoming[$required_key])) {
                $departure_index[$post_id][$row_key][$required_key] = $incoming[$required_key];
            }
        }

        if (array_key_exists('is_active', $incoming)) {
            $departure_index[$post_id][$row_key]['is_active'] = (bool) $incoming['is_active'];
        }

        foreach (['total_capacity', 'reserved_capacity'] as $numeric_key) {
            if (array_key_exists($numeric_key, $incoming) && $incoming[$numeric_key] !== null && $incoming[$numeric_key] !== '') {
                $departure_index[$post_id][$row_key][$numeric_key] = $incoming[$numeric_key];
            }
        }

        if (array_key_exists('remaining_capacity', $incoming) && $incoming['remaining_capacity'] !== null && $incoming['remaining_capacity'] !== '') {
            $current_source = (string) ($departure_index[$post_id][$row_key]['capacity_source'] ?? '');
            if (
                $departure_index[$post_id][$row_key]['remaining_capacity'] === null
                || $current_source === ''
                || strpos($current_source, 'travel_dates') === 0
            ) {
                $departure_index[$post_id][$row_key]['remaining_capacity'] = $incoming['remaining_capacity'];
            }
        }

        if (array_key_exists('price', $incoming) && $incoming['price'] !== null && $incoming['price'] !== '') {
            $current_source = (string) ($departure_index[$post_id][$row_key]['price_source'] ?? '');
            if (
                $departure_index[$post_id][$row_key]['price'] === null
                || $current_source === ''
                || strpos($current_source, 'travel_dates') === 0
            ) {
                $departure_index[$post_id][$row_key]['price'] = $incoming['price'];
            }
        }

        foreach (['status_raw', 'price_source', 'capacity_source', 'departure_source'] as $string_key) {
            if (! empty($incoming[$string_key])) {
                $departure_index[$post_id][$row_key][$string_key] = (string) $incoming[$string_key];
            }
        }

        $departure_debug[$post_id][$row_key] = [
            'departure_source' => (string) ($departure_index[$post_id][$row_key]['departure_source'] ?? ''),
            'price_source' => (string) ($departure_index[$post_id][$row_key]['price_source'] ?? ''),
            'capacity_source' => (string) ($departure_index[$post_id][$row_key]['capacity_source'] ?? ''),
            'status_raw' => (string) ($departure_index[$post_id][$row_key]['status_raw'] ?? ''),
        ];
    };

    $ids_sql = implode(',', array_map('intval', $post_ids));
    $travel_date_lookup = [];

    $travel_dates_table = $get_existing_table([
        $wpdb->prefix . 'aj_travel_dates',
        'aj_travel_dates',
        $wpdb->prefix . 'travel_dates',
        'travel_dates',
    ]);

    if ($travel_dates_table !== '' && $ids_sql !== '') {
        $columns = $get_columns($travel_dates_table);
        $date_column = in_array('date', $columns, true) ? 'date' : (in_array('start_date', $columns, true) ? 'start_date' : '');
        $tour_column = in_array('travel_id', $columns, true) ? 'travel_id' : (in_array('tour_id', $columns, true) ? 'tour_id' : '');
        $active_column = in_array('is_active', $columns, true) ? 'is_active' : (in_array('active', $columns, true) ? 'active' : '');
        $capacity_column = '';
        foreach (['stock', 'seats', 'places', 'available_seats', 'seats_available'] as $candidate_column) {
            if (in_array($candidate_column, $columns, true)) {
                $capacity_column = $candidate_column;
                break;
            }
        }
        $price_column = '';
        foreach (['specific_price', 'price_override', 'adult_price', 'price'] as $candidate_column) {
            if (in_array($candidate_column, $columns, true)) {
                $price_column = $candidate_column;
                break;
            }
        }

        if ($date_column !== '' && $tour_column !== '') {
            $select_parts = ['id', "{$tour_column} AS tour_post_id", "{$date_column} AS departure_date"];
            if ($active_column !== '') {
                $select_parts[] = "{$active_column} AS is_active_value";
            }
            if ($capacity_column !== '') {
                $select_parts[] = "{$capacity_column} AS capacity_value";
            }
            if ($price_column !== '') {
                $select_parts[] = "{$price_column} AS price_value";
            }

            $rows = $wpdb->get_results(
                "SELECT " . implode(', ', $select_parts) . " FROM {$travel_dates_table} WHERE {$tour_column} IN ({$ids_sql}) ORDER BY {$date_column} ASC, id ASC",
                ARRAY_A
            );

            foreach ((array) $rows as $row) {
                $post_id = isset($row['tour_post_id']) ? (int) $row['tour_post_id'] : 0;
                $date_value = isset($row['departure_date']) ? trim((string) $row['departure_date']) : '';
                $travel_date_id = isset($row['id']) ? (int) $row['id'] : 0;
                if ($post_id <= 0 || $date_value === '') {
                    continue;
                }

                $travel_date_lookup[$travel_date_id] = [
                    'post_id' => $post_id,
                    'date' => $date_value,
                ];

                $merge_departure_row($post_id, [
                    'travel_date_id' => $travel_date_id,
                    'date' => $date_value,
                    'is_active' => ! array_key_exists('is_active_value', $row) || ! empty($row['is_active_value']),
                    'remaining_capacity' => array_key_exists('capacity_value', $row) && $row['capacity_value'] !== '' && $row['capacity_value'] !== null ? max(0, (int) $row['capacity_value']) : null,
                    'price' => array_key_exists('price_value', $row) && $row['price_value'] !== '' && $row['price_value'] !== null ? (float) $row['price_value'] : null,
                    'price_source' => $price_column !== '' ? 'travel_dates:' . $price_column : '',
                    'capacity_source' => $capacity_column !== '' ? 'travel_dates:' . $capacity_column : '',
                    'departure_source' => 'travel_dates',
                ]);
            }
        }
    }

    $room_availability_table = $get_existing_table([
        $wpdb->prefix . 'aj_tour_hotel_room_date_availabilities',
        'aj_tour_hotel_room_date_availabilities',
    ]);

    if ($room_availability_table !== '' && $ids_sql !== '') {
        $rows = $wpdb->get_results(
            "SELECT tour_id, travel_date_id, SUM(available_places) AS total_available_places, MIN(status) AS room_status
             FROM {$room_availability_table}
             WHERE tour_id IN ({$ids_sql})
             GROUP BY tour_id, travel_date_id",
            ARRAY_A
        );

        foreach ((array) $rows as $row) {
            $post_id = isset($row['tour_id']) ? (int) $row['tour_id'] : 0;
            $travel_date_id = isset($row['travel_date_id']) ? (int) $row['travel_date_id'] : 0;
            $lookup = $travel_date_lookup[$travel_date_id] ?? null;
            $date_value = is_array($lookup) ? (string) ($lookup['date'] ?? '') : '';
            if ($post_id <= 0 || $date_value === '') {
                continue;
            }

            $merge_departure_row($post_id, [
                'travel_date_id' => $travel_date_id,
                'date' => $date_value,
                'remaining_capacity' => isset($row['total_available_places']) ? max(0, (int) $row['total_available_places']) : null,
                'status_raw' => isset($row['room_status']) ? trim((string) $row['room_status']) : '',
                'capacity_source' => 'room_availability:available_places',
                'departure_source' => 'travel_dates+room_availability',
            ]);
        }
    }

    $departures_table = $get_existing_table([
        'departures',
        $wpdb->prefix . 'departures',
    ]);

    if ($departures_table !== '' && $ids_sql !== '') {
        $columns = $get_columns($departures_table);
        $date_column = in_array('start_date', $columns, true) ? 'start_date' : (in_array('date', $columns, true) ? 'date' : '');
        $tour_column = in_array('voyage_id', $columns, true) ? 'voyage_id' : (in_array('tour_id', $columns, true) ? 'tour_id' : '');

        if ($date_column !== '' && $tour_column !== '') {
            $select_parts = ['id', "{$tour_column} AS tour_post_id", "{$date_column} AS departure_date"];
            foreach (['wp_travel_date_id', 'status', 'total_capacity', 'reserved_capacity', 'available_capacity', 'base_price', 'sale_price'] as $candidate_column) {
                if (in_array($candidate_column, $columns, true)) {
                    $select_parts[] = $candidate_column;
                }
            }

            $rows = $wpdb->get_results(
                "SELECT " . implode(', ', $select_parts) . " FROM {$departures_table} WHERE {$tour_column} IN ({$ids_sql}) ORDER BY {$date_column} ASC, id ASC",
                ARRAY_A
            );

            foreach ((array) $rows as $row) {
                $post_id = isset($row['tour_post_id']) ? (int) $row['tour_post_id'] : 0;
                $date_value = isset($row['departure_date']) ? trim((string) $row['departure_date']) : '';
                if ($post_id <= 0 || $date_value === '') {
                    continue;
                }

                $price = null;
                $price_source = '';
                if (isset($row['sale_price']) && $row['sale_price'] !== '' && $row['sale_price'] !== null && (float) $row['sale_price'] > 0) {
                    $price = (float) $row['sale_price'];
                    $price_source = 'departures:sale_price';
                } elseif (isset($row['base_price']) && $row['base_price'] !== '' && $row['base_price'] !== null && (float) $row['base_price'] > 0) {
                    $price = (float) $row['base_price'];
                    $price_source = 'departures:base_price';
                }

                $status_raw = isset($row['status']) ? trim((string) $row['status']) : '';
                $merge_departure_row($post_id, [
                    'travel_date_id' => isset($row['wp_travel_date_id']) ? (int) $row['wp_travel_date_id'] : 0,
                    'date' => $date_value,
                    'is_active' => ! in_array($status_raw, ['draft', 'closed', 'canceled', 'cancelled'], true),
                    'remaining_capacity' => isset($row['available_capacity']) && $row['available_capacity'] !== '' && $row['available_capacity'] !== null ? max(0, (int) $row['available_capacity']) : null,
                    'total_capacity' => isset($row['total_capacity']) && $row['total_capacity'] !== '' && $row['total_capacity'] !== null ? max(0, (int) $row['total_capacity']) : null,
                    'reserved_capacity' => isset($row['reserved_capacity']) && $row['reserved_capacity'] !== '' && $row['reserved_capacity'] !== null ? max(0, (int) $row['reserved_capacity']) : null,
                    'price' => $price,
                    'status_raw' => $status_raw,
                    'price_source' => $price_source,
                    'capacity_source' => isset($row['available_capacity']) ? 'departures:available_capacity' : '',
                    'departure_source' => 'departures',
                ]);
            }
        }
    }

    foreach ($departure_index as $post_id => $rows_by_key) {
        $normalized_rows = array_values($rows_by_key);
        usort($normalized_rows, static fn ($left, $right): int => strcmp((string) ($left['date'] ?? ''), (string) ($right['date'] ?? '')));
        $departure_index[$post_id] = $normalized_rows;

        foreach ($normalized_rows as $row) {
            if (! empty($row['is_active'])) {
                $available_departure_dates[(string) $row['date']] = (string) $row['date'];
            }
        }
    }

    wp_get_object_terms($post_ids, ['tours_cat', 'tour_tag', 'location', 'st_tour_type'], ['fields' => 'all']);
    update_meta_cache('post', $post_ids);

    foreach ($post_ids as $post_id) {
        $meta_cache[$post_id] = get_post_meta($post_id);
    }
}

ajinsafro_set_voyages_context([
    'meta_cache' => $meta_cache,
    'departure_index' => $departure_index,
    'departure_debug' => $departure_debug,
]);

$catalog_themes = function_exists('ajth_get_catalog_tour_category_terms_ordered')
    ? ajth_get_catalog_tour_category_terms_ordered()
    : [];
$catalog_tags = get_terms([
    'taxonomy' => 'tour_tag',
    'hide_empty' => true,
]);
$catalog_tour_types = get_terms([
    'taxonomy' => 'st_tour_type',
    'hide_empty' => true,
]);

$destinations = [];
foreach ($post_ids as $post_id) {
    $label = ajinsafro_get_tour_destination($post_id, $meta_cache[$post_id] ?? []);
    if ($label === '') {
        continue;
    }
    $destinations[$label] = [
        'value' => $label,
        'label' => $label,
    ];
}
$destinations = array_values($destinations);
usort($destinations, static fn ($left, $right): int => strcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? '')));
sort($available_departure_dates);
$upcoming_departure_dates = array_values(array_filter(
    $available_departure_dates,
    static fn (string $date_value): bool => $date_value >= $today
));

$format_price = static function (?float $price): string {
    if ($price === null || $price <= 0) {
        return '';
    }

    return number_format($price, 0, ',', ' ');
};

$format_date = static function (string $date_value): string {
    $timestamp = strtotime($date_value);
    if (! $timestamp) {
        return $date_value;
    }

    return date_i18n('d M Y', $timestamp);
};

$build_duration_label = static function (int $days, string $duration_text): string {
    if ($days > 0) {
        return sprintf('%d jours / %d nuits', $days, max(0, $days - 1));
    }
    if ($duration_text !== '') {
        return $duration_text;
    }

    return 'Programme a confirmer';
};

$cards = [];
foreach ($post_ids as $post_id) {
    $meta = $meta_cache[$post_id] ?? get_post_meta($post_id);
    $meta_value = static function (array $source, string $key): string {
        if (! isset($source[$key][0])) {
            return '';
        }
        return trim((string) $source[$key][0]);
    };
    $first_meta_value = static function (array $source, array $keys) use ($meta_value): string {
        foreach ($keys as $key) {
            $value = $meta_value($source, $key);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    };

    $post_date = get_post_field('post_date', $post_id);
    $title = get_the_title($post_id);
    $address = $meta_value($meta, 'address');
    $catalog_destination = $meta_value($meta, 'aj_catalog_destination');
    $duration_days = (int) $meta_value($meta, 'duration_day');
    $duration_text = $meta_value($meta, 'duration');
    $max_people = (int) $meta_value($meta, 'max_people');
    $departure_city = $first_meta_value($meta, ['departure_city', 'depart_city', 'start_city', 'origin_city', 'flight_departure_city', 'aj_departure_city', 'aj_tour_departure_city']);
    $review_score = (float) $meta_value($meta, 'review_score');
    $rate_review = (float) $meta_value($meta, 'rate_review');
    $rating_score = max($review_score, $rate_review);
    $comment_count = (int) get_comments_number($post_id);
    $date_rows = $departure_index[$post_id] ?? [];
    $active_dates = array_values(array_filter($date_rows, static fn ($row) => ! empty($row['is_active'])));

    $all_future_departures = array_values(array_filter(
        $active_dates,
        static fn ($row): bool => (string) ($row['date'] ?? '') >= $today
    ));

    $selected_departure = ajinsafro_get_tour_next_departure($post_id, $today, $depart_date, $guests_min);
    if ($depart_date !== '' && $selected_departure === null) {
        continue;
    }

    $next_departure = ajinsafro_get_tour_next_departure($post_id, $today, '', 0);
    if ($next_departure === null && ! empty($active_dates)) {
        $next_departure = $active_dates[count($active_dates) - 1];
    }

    $has_available_departure = false;
    foreach ($all_future_departures as $future_row) {
        $remaining = $future_row['remaining_capacity'] ?? null;
        if ($remaining === null || (int) $remaining > 0) {
            $has_available_departure = true;
            break;
        }
    }

    if ($available_only) {
        $has_real_available = false;
        foreach ($all_future_departures as $future_row) {
            $remaining = $future_row['remaining_capacity'] ?? null;
            if ($remaining !== null && (int) $remaining > 0) {
                $has_real_available = true;
                break;
            }
        }
        if (! $has_real_available) {
            continue;
        }
    }

    if ($guests_min > 0) {
        $fits_requested_guests = false;
        foreach ($all_future_departures as $future_row) {
            $remaining = $future_row['remaining_capacity'] ?? null;
            if ($remaining !== null && (int) $remaining >= $guests_min) {
                $fits_requested_guests = true;
                break;
            }
        }
        if (! $fits_requested_guests) {
            continue;
        }
    }

    $pricing = ajinsafro_get_tour_price($post_id, $selected_departure, $next_departure);
    $price_from = $pricing['price_from'];
    $price_reference = $pricing['price_reference'];
    $is_promo = (bool) $pricing['is_promo'];
    $theme_terms = wp_get_post_terms($post_id, 'tours_cat', ['fields' => 'all']);
    $tag_terms = wp_get_post_terms($post_id, 'tour_tag', ['fields' => 'all']);
    $tour_type_terms = wp_get_post_terms($post_id, 'st_tour_type', ['fields' => 'all']);
    $theme_names = [];
    foreach ((array) $theme_terms as $term) {
        if ($term instanceof WP_Term) {
            $theme_names[] = $term->name;
        }
    }
    $tag_names = [];
    foreach ((array) $tag_terms as $term) {
        if ($term instanceof WP_Term) {
            $tag_names[] = $term->name;
        }
    }
    $tour_type_names = [];
    foreach ((array) $tour_type_terms as $term) {
        if ($term instanceof WP_Term) {
            $tour_type_names[] = $term->name;
        }
    }
    $discovery_pool = strtolower(implode(' ', array_filter(array_merge($theme_names, $tag_names, [$title]))));
    $service_chips = [];
    $service_keywords = [
        'Vol inclus' => ['vol', 'flight'],
        'Hotel inclus' => ['hotel', 'hebergement', 'riad', 'resort'],
        'Transfert' => ['transfert', 'transfer', 'navette'],
        'Guide' => ['guide', 'accompagne'],
        'Visa' => ['visa'],
        'Activites' => ['activite', 'excursion', 'visite'],
        'Low cost' => ['low cost', 'economique', 'budget'],
        'Premium' => ['premium', 'luxe', 'vip'],
        'Famille' => ['famille', 'family'],
        'Couple' => ['couple', 'honeymoon'],
        'Groupe' => ['groupe', 'group'],
        'Omra' => ['omra'],
        'Hajj' => ['hajj'],
    ];
    foreach ($service_keywords as $service_label => $keywords) {
        foreach ($keywords as $keyword_match) {
            if ($keyword_match !== '' && strpos($discovery_pool, $keyword_match) !== false) {
                $service_chips[] = $service_label;
                break;
            }
        }
    }
    $service_chips = array_values(array_slice(array_unique($service_chips), 0, 4));

    $destination_label = ajinsafro_get_tour_destination($post_id, $meta);
    $search_pool = strtolower(implode(' ', array_filter([
        $title,
        $destination_label,
        $address,
        $catalog_destination,
        implode(' ', $theme_names),
        implode(' ', $tag_names),
        implode(' ', $tour_type_names),
    ])));

    if ($keyword !== '' && strpos($search_pool, strtolower($keyword)) === false) {
        continue;
    }
    if ($dest !== '' && strpos(strtolower($destination_label), strtolower($dest)) === false) {
        continue;
    }
    if ($duration_min > 0 && $duration_days > 0 && $duration_days < $duration_min) {
        continue;
    }
    if ($duration_max > 0 && $duration_days > 0 && $duration_days > $duration_max) {
        continue;
    }
    if ($tour_type_slug !== '') {
        $type_match = false;
        foreach ((array) $tour_type_terms as $term) {
            if ($term instanceof WP_Term && $term->slug === $tour_type_slug) {
                $type_match = true;
                break;
            }
        }
        if (! $type_match) {
            continue;
        }
    }

    if ($price_min > 0 && ($price_from === null || $price_from < $price_min)) {
        continue;
    }
    if ($price_max > 0 && ($price_from === null || $price_from > $price_max)) {
        continue;
    }
    if ($min_rating > 0 && $rating_score < $min_rating) {
        continue;
    }
    if ($promo_only && ! $is_promo) {
        continue;
    }

    $stock_source = $selected_departure ?? $next_departure;
    $stock_badge = ajinsafro_get_tour_availability($post_id, $stock_source, $today);

    $cards[] = [
        'id' => $post_id,
        'title' => $title,
        'permalink' => get_permalink($post_id),
        'excerpt' => get_the_excerpt($post_id) !== ''
            ? wp_trim_words(get_the_excerpt($post_id), 22, '...')
            : wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $post_id)), 22, '...'),
        'destination' => $destination_label,
        'duration_days' => $duration_days,
        'duration_label' => $build_duration_label($duration_days, $duration_text),
        'departure_city' => $departure_city,
        'max_people' => $max_people,
        'rating' => $rating_score,
        'reviews' => $comment_count,
        'price_from' => $price_from,
        'price_from_label' => $format_price($price_from),
        'price_reference' => $price_reference,
        'price_reference_label' => $format_price($price_reference),
        'is_promo' => $is_promo,
        'price_source' => (string) ($pricing['price_source'] ?? ''),
        'is_featured' => strtolower($meta_value($meta, 'is_featured')) === 'on',
        'next_departure' => $next_departure,
        'next_departure_label' => $next_departure ? $format_date((string) $next_departure['date']) : '',
        'selected_departure' => $selected_departure,
        'has_available_departure' => $has_available_departure,
        'stock_badge' => $stock_badge,
        'remaining_capacity' => $stock_source['remaining_capacity'] ?? null,
        'total_capacity' => $stock_source['total_capacity'] ?? null,
        'reserved_capacity' => $stock_source['reserved_capacity'] ?? null,
        'departure_source' => (string) ($stock_source['departure_source'] ?? ''),
        'capacity_source' => (string) ($stock_source['capacity_source'] ?? ''),
        'themes' => array_slice($theme_names, 0, 2),
        'tags' => array_slice($tag_names, 0, 3),
        'tour_types' => array_slice($tour_type_names, 0, 2),
        'service_chips' => $service_chips,
        'post_timestamp' => $post_date ? strtotime($post_date) : 0,
        'departure_timestamp' => $next_departure ? strtotime((string) $next_departure['date']) : 0,
        'debug_meta_keys' => array_values(array_intersect(
            ['adult_price', 'min_price', 'price', 'base_price', 'sale_price', 'duration_day', 'max_people', 'address', 'aj_catalog_destination'],
            array_keys($meta)
        )),
    ];
}

usort($cards, static function (array $left, array $right) use ($sort): int {
    $compare_numbers = static function ($a, $b, bool $asc = true): int {
        $a = $a ?? 0;
        $b = $b ?? 0;
        return $asc ? ($a <=> $b) : ($b <=> $a);
    };

    switch ($sort) {
        case 'price_asc':
            return $compare_numbers($left['price_from'] ?? PHP_INT_MAX, $right['price_from'] ?? PHP_INT_MAX, true)
                ?: strcasecmp($left['title'], $right['title']);

        case 'price_desc':
            return $compare_numbers($left['price_from'], $right['price_from'], false)
                ?: strcasecmp($left['title'], $right['title']);

        case 'duration_asc':
            return $compare_numbers($left['duration_days'] ?: PHP_INT_MAX, $right['duration_days'] ?: PHP_INT_MAX, true)
                ?: $compare_numbers($left['price_from'] ?? PHP_INT_MAX, $right['price_from'] ?? PHP_INT_MAX, true);

        case 'duration_desc':
            return $compare_numbers($left['duration_days'], $right['duration_days'], false)
                ?: $compare_numbers($left['price_from'], $right['price_from'], true);

        case 'departure_soonest':
            $left_departure = ! empty($left['departure_timestamp']) ? (int) $left['departure_timestamp'] : PHP_INT_MAX;
            $right_departure = ! empty($right['departure_timestamp']) ? (int) $right['departure_timestamp'] : PHP_INT_MAX;
            return $compare_numbers($left_departure, $right_departure, true)
                ?: $compare_numbers($left['price_from'], $right['price_from'], true);

        case 'recommended':
        default:
            return $compare_numbers((int) $left['is_featured'], (int) $right['is_featured'], false)
                ?: $compare_numbers((int) $left['has_available_departure'], (int) $right['has_available_departure'], false)
                ?: $compare_numbers((int) $left['is_promo'], (int) $right['is_promo'], false)
                ?: $compare_numbers($left['rating'], $right['rating'], false)
                ?: $compare_numbers($left['price_from'] ?? PHP_INT_MAX, $right['price_from'] ?? PHP_INT_MAX, true)
                ?: strcasecmp($left['title'], $right['title']);
    }
});

$total_results = count($cards);
$total_pages = max(1, (int) ceil($total_results / $per_page));
$paged = min($paged, $total_pages);
$cards_page = array_slice($cards, ($paged - 1) * $per_page, $per_page);
$min_price_found = null;
foreach ($cards as $card) {
    if (($card['price_from'] ?? 0) > 0) {
        $min_price_found = $min_price_found === null ? (float) $card['price_from'] : min($min_price_found, (float) $card['price_from']);
    }
}

$results_target = $dest !== '' ? $dest : ($keyword !== '' ? $keyword : 'votre selection');
$results_headline = sprintf('%d voyages trouves', $total_results);
if ($results_target !== 'votre selection') {
    $results_headline .= ' pour ' . $results_target;
}

$active_filters = [];
if ($keyword !== '') {
    $active_filters[] = ['label' => 'Recherche: ' . $keyword, 'url' => $build_url(['s' => '', 'location_name' => ''])];
}
if ($category_slug !== '') {
    foreach ((array) $catalog_themes as $term) {
        if ($term instanceof WP_Term && $term->slug === $category_slug) {
            $active_filters[] = ['label' => 'Theme: ' . $term->name, 'url' => $build_url(['cat' => ''])];
            break;
        }
    }
}
if ($tag_slug !== '') {
    foreach ((array) $catalog_tags as $term) {
        if ($term instanceof WP_Term && $term->slug === $tag_slug) {
            $active_filters[] = ['label' => 'Tag: ' . $term->name, 'url' => $build_url(['tag' => ''])];
            break;
        }
    }
}
if ($tour_type_slug !== '') {
    foreach ((array) $catalog_tour_types as $term) {
        if ($term instanceof WP_Term && $term->slug === $tour_type_slug) {
            $active_filters[] = ['label' => 'Type: ' . $term->name, 'url' => $build_url(['tour_type' => ''])];
            break;
        }
    }
}
if ($dest !== '') {
    $active_filters[] = ['label' => 'Destination: ' . $dest, 'url' => $build_url(['destination' => ''])];
}
if ($depart_date !== '') {
    $active_filters[] = ['label' => 'Depart: ' . $format_date($depart_date), 'url' => $build_url(['date_depart' => ''])];
}
if ($price_min > 0 || $price_max > 0) {
    $budget_label = 'Budget';
    if ($price_min > 0 && $price_max > 0) {
        $budget_label .= ': ' . number_format($price_min, 0, ',', ' ') . ' - ' . number_format($price_max, 0, ',', ' ') . ' DH';
    } elseif ($price_min > 0) {
        $budget_label .= ': min ' . number_format($price_min, 0, ',', ' ') . ' DH';
    } else {
        $budget_label .= ': max ' . number_format($price_max, 0, ',', ' ') . ' DH';
    }
    $active_filters[] = ['label' => $budget_label, 'url' => $build_url(['budget_min' => '', 'budget_max' => ''])];
}
if ($duration_min > 0 || $duration_max > 0) {
    $duration_filter_label = 'Duree';
    if ($duration_min > 0 && $duration_max > 0) {
        $duration_filter_label .= ': ' . $duration_min . '-' . $duration_max . ' j';
    } elseif ($duration_min > 0) {
        $duration_filter_label .= ': min ' . $duration_min . ' j';
    } else {
        $duration_filter_label .= ': max ' . $duration_max . ' j';
    }
    $active_filters[] = ['label' => $duration_filter_label, 'url' => $build_url(['duration_min' => '', 'duration_max' => ''])];
}
if ($guests_min > 0) {
    $active_filters[] = ['label' => 'Voyageurs: ' . $guests_min . '+', 'url' => $build_url(['voyageurs' => ''])];
}
if ($min_rating > 0) {
    $active_filters[] = ['label' => 'Note: ' . $min_rating . '+', 'url' => $build_url(['min_rating' => ''])];
}
if ($featured_only) {
    $active_filters[] = ['label' => 'Recommande Ajinsafro', 'url' => $build_url(['featured' => ''])];
}
if ($promo_only) {
    $active_filters[] = ['label' => 'Promotions uniquement', 'url' => $build_url(['promo_only' => ''])];
}
if ($available_only) {
    $active_filters[] = ['label' => 'Disponibilite immediate', 'url' => $build_url(['available_only' => ''])];
}

$sort_options = [
    'recommended' => 'Recommandes',
    'price_asc' => 'Prix croissant',
    'price_desc' => 'Prix decroissant',
    'duration_asc' => 'Duree courte',
    'duration_desc' => 'Duree longue',
    'departure_soonest' => 'Departs les plus proches',
];

$rating_label = static function (float $rating): string {
    if ($rating >= 9) {
        return 'Exceptionnel';
    }
    if ($rating >= 8) {
        return 'Excellent';
    }
    if ($rating >= 7) {
        return 'Tres bien';
    }

    return 'Correct';
};
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-voyages-page aj-voyages-booking-page">
        <?php ajth_render_site_header($settings); ?>

        <div class="aj-voyages-booking" id="aj-voyages-booking">
            <section class="hero">
                <div class="container">
                    <h1 class="hero-title">Voyages, sejours et circuits</h1>
                    <p class="hero-subtitle">Comparez nos offres, choisissez votre destination et reservez votre prochain voyage avec Ajinsafro.</p>

                    <form class="search-panel" method="get" action="<?php echo esc_url($voyages_page_url); ?>">
                        <input type="hidden" name="sort" value="<?php echo esc_attr($sort); ?>">
                        <div class="search-field">
                            <label for="ajvb-destination">Destination</label>
                            <select id="ajvb-destination" name="destination">
                                <option value="">Toutes les destinations</option>
                                <?php foreach ((array) $destinations as $destination_option) { ?>
                                    <option value="<?php echo esc_attr($destination_option['value']); ?>" <?php selected($dest, $destination_option['value']); ?>>
                                        <?php echo esc_html($destination_option['label']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="search-field">
                            <label for="ajvb-depart-date">Date de depart</label>
                            <input id="ajvb-depart-date" name="date_depart" type="date" value="<?php echo esc_attr($depart_date); ?>">
                        </div>
                        <div class="search-field">
                            <label for="ajvb-travelers">Voyageurs</label>
                            <input id="ajvb-travelers" name="voyageurs" type="number" min="1" value="<?php echo esc_attr($guests_min > 0 ? (string) $guests_min : ''); ?>" placeholder="2">
                        </div>
                        <div class="search-field">
                            <label for="ajvb-budget">Budget max</label>
                            <input id="ajvb-budget" name="budget_max" type="number" min="0" value="<?php echo esc_attr($price_max > 0 ? (string) $price_max : ''); ?>" placeholder="12000">
                        </div>
                        <button class="search-btn" type="submit">Rechercher</button>
                    </form>
                </div>
            </section>

            <main class="container main-grid">
                <aside class="filters" aria-label="Filtres voyages">
                    <div class="map-card">
                        <button type="button">Conseils Ajinsafro</button>
                    </div>
                    <div class="filter-title">
                        <h2>Filtrer par</h2>
                        <a class="clear-link" href="<?php echo esc_url($voyages_page_url); ?>">Tout effacer</a>
                    </div>
                    <?php include AJTH_DIR . 'parts/voyages-filters.php'; ?>
                </aside>

                <section class="results">
                    <div class="results-head">
                        <div class="results-topline">
                            <div>
                                <h2><?php echo esc_html($results_headline); ?></h2>
                                <div class="result-count">
                                    <?php if ($min_price_found !== null) { ?>
                                        A partir de <?php echo esc_html($format_price($min_price_found)); ?> DH par personne
                                    <?php } else { ?>
                                        Tarifs disponibles sur demande
                                    <?php } ?>
                                </div>
                            </div>
                            <form method="get" action="<?php echo esc_url($voyages_page_url); ?>" class="sort-wrap">
                                <?php foreach ($current_filters as $key => $value) { ?>
                                    <?php if ($key === 'sort' || $key === 'paged') { continue; } ?>
                                    <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                                <?php } ?>
                                Trier par
                                <select name="sort" id="ajvb-sort-select" onchange="this.form.submit()">
                                    <?php foreach ($sort_options as $sort_value => $sort_label) { ?>
                                        <option value="<?php echo esc_attr($sort_value); ?>" <?php selected($sort, $sort_value); ?>><?php echo esc_html($sort_label); ?></option>
                                    <?php } ?>
                                </select>
                            </form>
                        </div>
                        <div class="chips">
                            <span class="chip"><?php echo esc_html((string) $total_results); ?> voyages visibles</span>
                            <span class="chip"><?php echo esc_html((string) count($destinations)); ?> destinations</span>
                            <span class="chip"><?php echo esc_html((string) count($upcoming_departure_dates)); ?> departs a venir</span>
                            <?php foreach ($active_filters as $active_filter) { ?>
                                <a href="<?php echo esc_url($active_filter['url']); ?>" class="chip">
                                    <span><?php echo esc_html($active_filter['label']); ?></span>
                                </a>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="deal-strip">
                        <div>
                            <strong>Voyages selectionnes avec l'accompagnement Ajinsafro</strong>
                            <span>Departures, tarifs et disponibilites mis a jour depuis vos offres WordPress.</span>
                        </div>
                        <a href="<?php echo esc_url($voyages_page_url); ?>" class="deal-strip__link">Explorer</a>
                    </div>

                    <?php if (! empty($cards_page)) { ?>
                        <div class="hotel-list">
                            <?php foreach ($cards_page as $card) { ?>
                                <article class="hotel-card aj-voyage-card">
                                    <a href="<?php echo esc_url($card['permalink']); ?>" class="photo-wrap" aria-label="<?php echo esc_attr($card['title']); ?>">
                                        <?php echo ajinsafro_get_tour_image((int) $card['id']); ?>
                                        <button class="fav" type="button" aria-label="Ajouter aux favoris">♡</button>
                                        <div class="photo-badges">
                                            <?php if ($card['is_featured']) { ?>
                                                <span class="photo-badge">Selection Ajinsafro</span>
                                            <?php } ?>
                                            <?php if ($card['is_promo']) { ?>
                                                <span class="photo-badge photo-badge--promo">Promo</span>
                                            <?php } ?>
                                            <?php if (! empty($card['stock_badge'])) { ?>
                                                <span class="photo-badge photo-badge--stock"><?php echo esc_html($card['stock_badge']['label']); ?></span>
                                            <?php } ?>
                                        </div>
                                    </a>

                                    <div class="hotel-main">
                                        <div class="meta meta--caps">
                                            <span><?php echo esc_html(! empty($card['tour_types']) ? implode(' / ', $card['tour_types']) : (! empty($card['themes']) ? implode(' / ', $card['themes']) : 'Selection Ajinsafro')); ?></span>
                                        </div>
                                        <h3><a href="<?php echo esc_url($card['permalink']); ?>"><?php echo esc_html($card['title']); ?></a></h3>
                                        <div class="location">
                                            <?php if ($card['destination'] !== '') { ?><span>Destination: <?php echo esc_html($card['destination']); ?></span><?php } ?>
                                            <?php if ($card['duration_days'] > 0 || $card['duration_label'] !== '') { ?><span><?php echo esc_html($card['duration_label']); ?></span><?php } ?>
                                        </div>
                                        <div class="meta">
                                            <?php if ($card['departure_city'] !== '') { ?><span>Depart: <?php echo esc_html($card['departure_city']); ?></span><?php } ?>
                                            <?php if ($card['next_departure_label'] !== '') { ?><span>Date: <?php echo esc_html($card['next_departure_label']); ?></span><?php } ?>
                                            <?php if ($card['max_people'] > 0) { ?><span><?php echo esc_html((string) $card['max_people']); ?> voyageurs max</span><?php } ?>
                                        </div>
                                        <p class="description"><?php echo esc_html($card['excerpt']); ?></p>
                                        <div class="amenities">
                                            <?php foreach ($card['service_chips'] as $service_chip) { ?>
                                                <span class="amenity"><?php echo esc_html($service_chip); ?></span>
                                            <?php } ?>
                                            <?php foreach ($card['tags'] as $tag_name) { ?>
                                                <span class="amenity amenity--subtle"><?php echo esc_html($tag_name); ?></span>
                                            <?php } ?>
                                        </div>
                                        <div class="good-note">Support Ajinsafro · Disponibilites verifiees · Reservation rapide</div>
                                    </div>

                                    <aside class="hotel-side">
                                        <div class="rating-box">
                                            <div class="rating-text">
                                                <?php if ($card['rating'] > 0) { ?>
                                                    <strong><?php echo esc_html($rating_label((float) $card['rating'])); ?></strong>
                                                    <span><?php echo esc_html($card['reviews'] > 0 ? sprintf('%d avis', $card['reviews']) : 'Sans avis'); ?></span>
                                                <?php } else { ?>
                                                    <strong>Avis clients</strong>
                                                    <span>En cours de collecte</span>
                                                <?php } ?>
                                            </div>
                                            <div class="rating-score"><?php echo esc_html($card['rating'] > 0 ? number_format((float) $card['rating'], 1, '.', '') : '--'); ?></div>
                                        </div>

                                        <div class="price-area">
                                            <small>A partir de</small>
                                            <div>
                                                <?php if ($card['price_reference_label'] !== '') { ?>
                                                    <span class="old-price"><?php echo esc_html($card['price_reference_label']); ?> DH</span>
                                                <?php } ?>
                                                <span class="price"><?php echo esc_html($card['price_from_label'] !== '' ? $card['price_from_label'] . ' DH' : 'Prix sur demande'); ?></span>
                                            </div>
                                            <div class="tax"><?php echo esc_html($card['price_from_label'] !== '' ? 'par personne' : 'selon disponibilite'); ?></div>
                                        </div>

                                        <div class="card-actions">
                                            <a class="secondary-btn" href="<?php echo esc_url($card['permalink']); ?>">Voir le voyage</a>
                                            <a class="primary-btn" href="<?php echo esc_url($card['permalink']); ?>#ajtb-v1-summary-card">Reserver</a>
                                        </div>

                                        <?php if ($debug_voyages) { ?>
                                            <div class="tax" style="margin-top:8px;text-align:left;line-height:1.35;">
                                                ID #<?php echo esc_html((string) $card['id']); ?>
                                                · prix=<?php echo esc_html($card['price_from_label'] !== '' ? $card['price_from_label'] . ' DH' : 'n/a'); ?>
                                                · depart=<?php echo esc_html($card['next_departure_label'] !== '' ? $card['next_departure_label'] : 'n/a'); ?>
                                                · restant=<?php echo esc_html($card['remaining_capacity'] !== null ? (string) $card['remaining_capacity'] : 'n/a'); ?>
                                            </div>
                                        <?php } ?>
                                    </aside>
                                </article>
                            <?php } ?>
                        </div>

                        <?php
                        $pagination_base = str_replace(
                            '%25%23%25',
                            '%#%',
                            add_query_arg(array_merge($current_filters, ['paged' => '%#%']), $voyages_page_url)
                        );
                        $pagination = paginate_links([
                            'base' => $pagination_base,
                            'format' => '',
                            'current' => $paged,
                            'total' => $total_pages,
                            'type' => 'array',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                        ]);
                        ?>

                        <?php if (! empty($pagination)) { ?>
                            <nav class="aj-voyages-pagination" aria-label="Pagination voyages">
                                <?php foreach ($pagination as $page_link) { ?>
                                    <?php echo wp_kses_post($page_link); ?>
                                <?php } ?>
                            </nav>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="empty-state" style="display:block;">
                            <h3>Aucun voyage trouve</h3>
                            <p>Essayez de modifier votre budget, vos dates ou votre destination.</p>
                            <a class="primary-btn" href="<?php echo esc_url($voyages_page_url); ?>">Reinitialiser les filtres</a>
                        </div>
                    <?php } ?>
                </section>

                <aside class="ad-col" aria-label="Promotions voyages">
                    <div class="ad-box">
                        <strong>Departs verifies sur nos sejours phares</strong>
                        <button type="button">Voir les offres</button>
                    </div>
                    <div class="ad-box">
                        <strong>Circuits, omra et escapades selectionnes par Ajinsafro</strong>
                        <button type="button">Reserver</button>
                    </div>
                </aside>
            </main>

            <button class="mobile-filter-btn" type="button" id="ajvb-open-filters">Filtres</button>
            <div class="drawer-backdrop" id="ajvb-drawer-backdrop"></div>
            <aside class="mobile-drawer" id="ajvb-mobile-drawer" aria-label="Filtres mobile">
                <div class="drawer-head">
                    <h3>Filtres</h3>
                    <button type="button" id="ajvb-close-filters">x</button>
                </div>
                <?php include AJTH_DIR . 'parts/voyages-filters.php'; ?>
            </aside>
        </div>
    </div>
</div>

<?php
wp_reset_postdata();
get_footer();
