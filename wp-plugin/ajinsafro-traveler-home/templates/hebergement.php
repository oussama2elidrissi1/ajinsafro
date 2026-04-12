<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$settings = ajth_get_settings();

$paged = max(1, absint(get_query_var('paged')), absint(get_query_var('page')));
$search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
if ($search === '' && isset($_GET['location_name'])) {
    $search = sanitize_text_field(wp_unslash($_GET['location_name']));
}
if ($search === '' && isset($_GET['s'])) {
    $search = sanitize_text_field(wp_unslash($_GET['s']));
}

$address = isset($_GET['address']) ? sanitize_text_field(wp_unslash($_GET['address'])) : '';
$hotel_type = isset($_GET['hotel_type']) ? sanitize_text_field(wp_unslash($_GET['hotel_type'])) : '';
$stars = isset($_GET['stars']) ? absint($_GET['stars']) : 0;
$featured = isset($_GET['featured']) && (string) $_GET['featured'] === '1';
$price_min = isset($_GET['price_min']) ? absint($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? absint($_GET['price_max']) : 0;
$catalog_orderby = isset($_GET['catalog_orderby']) ? sanitize_text_field(wp_unslash($_GET['catalog_orderby'])) : 'date';

global $wpdb;
$posts_table = $wpdb->posts;
$hotel_table = $wpdb->prefix . 'st_hotel';

$sql = "SELECT DISTINCT p.ID
        FROM {$posts_table} p
        INNER JOIN {$hotel_table} h ON h.post_id = p.ID
        WHERE p.post_type = 'st_hotel' AND p.post_status = 'publish'";
$params = [];

if ($search !== '') {
    $like = '%' . $wpdb->esc_like($search) . '%';
    $sql .= " AND (p.post_title LIKE %s OR p.post_name LIKE %s OR h.address LIKE %s)";
    array_push($params, $like, $like, $like);
}
if ($address !== '') {
    $sql .= " AND h.address = %s";
    $params[] = $address;
}
if ($stars > 0) {
    $sql .= " AND CAST(h.hotel_star AS UNSIGNED) = %d";
    $params[] = $stars;
}
if ($price_min > 0) {
    $sql .= " AND CAST(COALESCE(NULLIF(h.min_price, ''), '0') AS DECIMAL(10,2)) >= %d";
    $params[] = $price_min;
}
if ($price_max > 0) {
    $sql .= " AND CAST(COALESCE(NULLIF(h.min_price, ''), '0') AS DECIMAL(10,2)) <= %d";
    $params[] = $price_max;
}
if ($featured) {
    $sql .= " AND h.is_featured = 'on'";
}
if ($hotel_type !== '' && taxonomy_exists('hotel_type')) {
    $ids = get_posts([
        'post_type' => 'st_hotel',
        'fields' => 'ids',
        'posts_per_page' => -1,
        'tax_query' => [[
            'taxonomy' => 'hotel_type',
            'field' => 'slug',
            'terms' => [$hotel_type],
        ]],
    ]);
    $ids = array_values(array_filter(array_map('absint', (array) $ids)));
    $sql .= ' AND p.ID IN (' . (! empty($ids) ? implode(',', $ids) : '0') . ')';
}

$sql .= ' ORDER BY p.post_date DESC';
$matching_ids = array_values(array_filter(array_map(
    'absint',
    $params ? $wpdb->get_col($wpdb->prepare($sql, $params)) : $wpdb->get_col($sql)
)));

$query_args = [
    'post_type' => 'st_hotel',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => $paged,
    'post__in' => ! empty($matching_ids) ? $matching_ids : [0],
];

if ($catalog_orderby === 'title') {
    $query_args['orderby'] = 'title';
    $query_args['order'] = 'ASC';
} else {
    $query_args['orderby'] = 'date';
    $query_args['order'] = 'DESC';
}

$q = new WP_Query($query_args);
$current_ids = array_values(array_filter(array_map('absint', wp_list_pluck((array) $q->posts, 'ID'))));
$hotel_rows = [];
if (! empty($current_ids)) {
    $detail_sql = "SELECT * FROM {$hotel_table} WHERE post_id IN (" . implode(',', $current_ids) . ')';
    foreach ((array) $wpdb->get_results($detail_sql, ARRAY_A) as $row) {
        $hotel_rows[(int) $row['post_id']] = $row;
    }
}

$page_url = function_exists('ajth_get_hebergement_page_url') ? ajth_get_hebergement_page_url() : home_url('/hebergement/');
$pagination_args = array_filter([
    'search' => $search,
    'address' => $address,
    'hotel_type' => $hotel_type,
    'stars' => $stars ? (string) $stars : '',
    'featured' => $featured ? '1' : '',
    'price_min' => $price_min ? (string) $price_min : '',
    'price_max' => $price_max ? (string) $price_max : '',
    'catalog_orderby' => $catalog_orderby,
]);
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-voyages-page">
        <?php ajth_render_site_header($settings); ?>

        <section class="aj-voyages-catalog">
            <div class="aj-container aj-voyages-catalog__container">
                <input type="checkbox" id="aj-voyages-filters-toggle" class="aj-voyages-filters-toggle" tabindex="-1" aria-hidden="true">
                <label for="aj-voyages-filters-toggle" class="aj-voyages-filters-backdrop" aria-hidden="true"></label>

                <div class="aj-voyages-catalog__grid">
                    <aside class="aj-voyages-filters-sidebar" id="aj-voyages-filters-panel">
                        <label for="aj-voyages-filters-toggle" class="aj-voyages-filters-close"><span aria-hidden="true">&times;</span></label>
                        <?php include AJTH_DIR . 'parts/hebergement-filters.php'; ?>
                    </aside>

                    <main class="aj-voyages-catalog__main">
                        <label for="aj-voyages-filters-toggle" class="aj-voyages-filters-mobile-trigger">
                            <i class="fas fa-sliders-h" aria-hidden="true"></i>
                            <?php esc_html_e('Filtres', 'ajinsafro-traveler-home'); ?>
                        </label>

                        <div class="aj-voyages-toolbar">
                            <div class="aj-voyages-toolbar__left">
                                <h2 class="aj-voyages-toolbar__title"><?php esc_html_e('Catalogue hebergement', 'ajinsafro-traveler-home'); ?></h2>
                                <p class="aj-voyages-toolbar__count"><?php echo esc_html(sprintf(_n('%d resultat', '%d resultats', intval($q->found_posts), 'ajinsafro-traveler-home'), intval($q->found_posts))); ?></p>
                            </div>
                            <div class="aj-voyages-toolbar__sort">
                                <form method="get" class="aj-voyages-sort-form" action="<?php echo esc_url($page_url); ?>">
                                    <?php foreach ($pagination_args as $key => $value) { if ($key === 'catalog_orderby') { continue; } ?>
                                        <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                                    <?php } ?>
                                    <label class="aj-voyages-sort-form__label" for="aj-hebergement-orderby"><?php esc_html_e('Trier par', 'ajinsafro-traveler-home'); ?></label>
                                    <select name="catalog_orderby" id="aj-hebergement-orderby" class="aj-voyages-sort-form__select" onchange="this.form.submit()">
                                        <option value="date" <?php selected($catalog_orderby, 'date'); ?>><?php esc_html_e('Plus recents', 'ajinsafro-traveler-home'); ?></option>
                                        <option value="title" <?php selected($catalog_orderby, 'title'); ?>><?php esc_html_e('Titre (A-Z)', 'ajinsafro-traveler-home'); ?></option>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <?php if ($q->have_posts()) { ?>
                            <div class="aj-voyages-grid">
                                <?php while ($q->have_posts()) { $q->the_post();
                                    $detail = $hotel_rows[get_the_ID()] ?? [];
                                    $hotel_address = $detail['address'] ?? '';
                                    $hotel_star = isset($detail['hotel_star']) ? (int) $detail['hotel_star'] : 0;
                                    $min_price_value = $detail['min_price'] ?? '';
                                    $excerpt = get_the_excerpt() ? wp_trim_words(get_the_excerpt(), 18, '...') : wp_trim_words(get_the_content(), 18, '...');
                                    $terms = get_the_terms(get_the_ID(), 'hotel_type');
                                    $type_label = (! empty($terms) && ! is_wp_error($terms)) ? $terms[0]->name : __('Hotel', 'ajinsafro-traveler-home');
                                    ?>
                                    <article class="aj-voyages-grid__item">
                                        <a href="<?php the_permalink(); ?>" class="aj-card2 aj-hover-glass">
                                            <div class="aj-card2__image">
                                                <?php ajth_render_catalog_card_image( get_the_ID() ); ?>
                                            </div>
                                            <div class="aj-card2__body">
                                                <h3 class="aj-card2__title"><?php the_title(); ?></h3>
                                                <div class="aj-card2__location"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($hotel_address ?: __('Localisation non renseignee', 'ajinsafro-traveler-home')); ?></div>
                                                <div class="aj-card2__meta">
                                                    <span class="aj-card2__category"><?php echo esc_html($type_label); ?></span>
                                                    <?php if ($hotel_star > 0) { ?>
                                                        <span class="aj-card2__category"><?php echo esc_html($hotel_star); ?> *</span>
                                                    <?php } ?>
                                                </div>
                                                <p class="aj-voyages-excerpt"><?php echo esc_html($excerpt); ?></p>
                                                <div class="aj-card2__footer">
                                                    <div>
                                                        <?php if ($min_price_value !== '') { ?>
                                                            <span class="aj-card2__price-label"><?php esc_html_e('A partir de', 'ajinsafro-traveler-home'); ?></span>
                                                            <div class="aj-card2__price"><?php echo esc_html(number_format((float) $min_price_value, 0, ',', ' ')); ?> <span class="aj-card2__price-currency">MAD</span></div>
                                                        <?php } ?>
                                                    </div>
                                                    <span class="aj-card2__cta"><?php esc_html_e("Voir l'offre", 'ajinsafro-traveler-home'); ?></span>
                                                </div>
                                            </div>
                                        </a>
                                    </article>
                                <?php } ?>
                            </div>

                            <div class="aj-voyages-pagination">
                                <?php
                                echo paginate_links([
                                    'total' => (int) $q->max_num_pages,
                                    'current' => $paged,
                                    'type' => 'list',
                                    'add_args' => $pagination_args,
                                ]);
                                ?>
                            </div>
                        <?php } else { ?>
                            <div class="aj-voyages-empty">
                                <h3><?php esc_html_e('Aucun hebergement trouve', 'ajinsafro-traveler-home'); ?></h3>
                                <p><?php esc_html_e('Essayez un autre filtre ou elargissez votre recherche.', 'ajinsafro-traveler-home'); ?></p>
                            </div>
                        <?php } wp_reset_postdata(); ?>
                    </main>
                </div>
            </div>
        </section>
    </div>
</div>

<?php get_footer(); ?>
