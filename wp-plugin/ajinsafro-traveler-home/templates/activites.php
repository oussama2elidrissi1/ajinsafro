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
$type_activity = isset($_GET['type_activity']) ? sanitize_text_field(wp_unslash($_GET['type_activity'])) : '';
$category = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : '';
$duration = isset($_GET['duration']) ? sanitize_text_field(wp_unslash($_GET['duration'])) : '';
$age = isset($_GET['age']) ? absint($_GET['age']) : 0;
$price_min = isset($_GET['price_min']) ? absint($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? absint($_GET['price_max']) : 0;
$catalog_orderby = isset($_GET['catalog_orderby']) ? sanitize_text_field(wp_unslash($_GET['catalog_orderby'])) : 'date';

global $wpdb;
$posts_table = $wpdb->posts;
$activity_table = $wpdb->prefix . 'st_activity';
$postmeta_table = $wpdb->postmeta;

$sql = "SELECT DISTINCT p.ID
        FROM {$posts_table} p
        INNER JOIN {$activity_table} a ON a.post_id = p.ID
        WHERE p.post_type = 'st_activity' AND p.post_status = 'publish'";
$params = [];

if ($search !== '') {
    $like = '%' . $wpdb->esc_like($search) . '%';
    $sql .= " AND (p.post_title LIKE %s OR p.post_name LIKE %s OR a.address LIKE %s OR a.type_activity LIKE %s)";
    array_push($params, $like, $like, $like, $like);
}
if ($address !== '') {
    $sql .= " AND a.address = %s";
    $params[] = $address;
}
if ($type_activity !== '') {
    $sql .= " AND a.type_activity = %s";
    $params[] = $type_activity;
}
if ($duration !== '') {
    $sql .= " AND a.duration = %s";
    $params[] = $duration;
}
if ($price_min > 0) {
    $sql .= " AND CAST(COALESCE(NULLIF(a.adult_price, ''), NULLIF(a.min_price, ''), '0') AS DECIMAL(10,2)) >= %d";
    $params[] = $price_min;
}
if ($price_max > 0) {
    $sql .= " AND CAST(COALESCE(NULLIF(a.adult_price, ''), NULLIF(a.min_price, ''), '0') AS DECIMAL(10,2)) <= %d";
    $params[] = $price_max;
}
if ($category !== '') {
    $sql .= $wpdb->prepare(
        " AND EXISTS (
            SELECT 1 FROM {$postmeta_table} pm
            WHERE pm.post_id = p.ID
              AND pm.meta_key = %s
              AND pm.meta_value = %s
        )",
        'aj_activity_category',
        $category
    );
}
if ($age > 0) {
    $sql .= $wpdb->prepare(
        " AND (
            NOT EXISTS (
                SELECT 1 FROM {$postmeta_table} pm_min_missing
                WHERE pm_min_missing.post_id = p.ID
                  AND pm_min_missing.meta_key = %s
                  AND TRIM(pm_min_missing.meta_value) <> ''
            )
            OR EXISTS (
                SELECT 1 FROM {$postmeta_table} pm_min
                WHERE pm_min.post_id = p.ID
                  AND pm_min.meta_key = %s
                  AND CAST(pm_min.meta_value AS UNSIGNED) <= %d
            )
        )
        AND (
            NOT EXISTS (
                SELECT 1 FROM {$postmeta_table} pm_max_missing
                WHERE pm_max_missing.post_id = p.ID
                  AND pm_max_missing.meta_key = %s
                  AND TRIM(pm_max_missing.meta_value) <> ''
            )
            OR EXISTS (
                SELECT 1 FROM {$postmeta_table} pm_max
                WHERE pm_max.post_id = p.ID
                  AND pm_max.meta_key = %s
                  AND CAST(pm_max.meta_value AS UNSIGNED) >= %d
            )
        )",
        'aj_activity_min_age',
        'aj_activity_min_age',
        $age,
        'aj_activity_max_age',
        'aj_activity_max_age',
        $age
    );
}

$sql .= ' ORDER BY p.post_date DESC';
$matching_ids = array_values(array_filter(array_map(
    'absint',
    $params ? $wpdb->get_col($wpdb->prepare($sql, $params)) : $wpdb->get_col($sql)
)));

$query_args = [
    'post_type' => 'st_activity',
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
$activity_rows = [];
if (! empty($current_ids)) {
    $detail_sql = "SELECT * FROM {$activity_table} WHERE post_id IN (" . implode(',', $current_ids) . ')';
    foreach ((array) $wpdb->get_results($detail_sql, ARRAY_A) as $row) {
        $activity_rows[(int) $row['post_id']] = $row;
    }
}

$page_url = function_exists('ajth_get_activites_page_url') ? ajth_get_activites_page_url() : home_url('/activites/');
$pagination_args = array_filter([
    'search' => $search,
    'address' => $address,
    'type_activity' => $type_activity,
    'category' => $category,
    'duration' => $duration,
    'age' => $age ? (string) $age : '',
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
                        <?php include AJTH_DIR . 'parts/activites-filters.php'; ?>
                    </aside>

                    <main class="aj-voyages-catalog__main">
                        <label for="aj-voyages-filters-toggle" class="aj-voyages-filters-mobile-trigger"><i class="fas fa-sliders-h" aria-hidden="true"></i><?php esc_html_e('Filtres', 'ajinsafro-traveler-home'); ?></label>
                        <div class="aj-voyages-toolbar">
                            <div class="aj-voyages-toolbar__left">
                                <h2 class="aj-voyages-toolbar__title"><?php esc_html_e('Catalogue activites', 'ajinsafro-traveler-home'); ?></h2>
                                <p class="aj-voyages-toolbar__count"><?php echo esc_html(sprintf(_n('%d resultat', '%d resultats', intval($q->found_posts), 'ajinsafro-traveler-home'), intval($q->found_posts))); ?></p>
                            </div>
                            <div class="aj-voyages-toolbar__sort">
                                <form method="get" class="aj-voyages-sort-form" action="<?php echo esc_url($page_url); ?>">
                                    <?php foreach ($pagination_args as $key => $value) { if ($key === 'catalog_orderby') { continue; } ?>
                                        <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                                    <?php } ?>
                                    <label class="aj-voyages-sort-form__label" for="aj-activites-orderby"><?php esc_html_e('Trier par', 'ajinsafro-traveler-home'); ?></label>
                                    <select name="catalog_orderby" id="aj-activites-orderby" class="aj-voyages-sort-form__select" onchange="this.form.submit()">
                                        <option value="date" <?php selected($catalog_orderby, 'date'); ?>><?php esc_html_e('Plus recentes', 'ajinsafro-traveler-home'); ?></option>
                                        <option value="title" <?php selected($catalog_orderby, 'title'); ?>><?php esc_html_e('Titre (A-Z)', 'ajinsafro-traveler-home'); ?></option>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <?php if ($q->have_posts()) { ?>
                            <div class="aj-voyages-grid">
                                <?php while ($q->have_posts()) { $q->the_post();
                                    $detail = $activity_rows[get_the_ID()] ?? [];
                                    $place_meta = get_post_meta(get_the_ID(), 'aj_activity_place_text', true);
                                    $place = $detail['address'] ?? '';
                                    if ($place === '' || $place === null) {
                                        $place = is_string($place_meta) ? $place_meta : '';
                                    }
                                    $type = $detail['type_activity'] ?? '';
                                    $adult_price = $detail['adult_price'] ?? ($detail['min_price'] ?? '');
                                    $child_price = $detail['child_price'] ?? '';
                                    $duration_text = $detail['duration'] ?? '';
                                    $category_label = get_post_meta(get_the_ID(), 'aj_activity_category', true);
                                    $age_min = get_post_meta(get_the_ID(), 'aj_activity_min_age', true);
                                    $age_max = get_post_meta(get_the_ID(), 'aj_activity_max_age', true);
                                    $excerpt = get_the_excerpt() ? wp_trim_words(get_the_excerpt(), 18, '...') : wp_trim_words(get_the_content(), 18, '...');
                                    ?>
                                    <article class="aj-voyages-grid__item">
                                        <a href="<?php the_permalink(); ?>" class="aj-card2 aj-hover-glass">
                                            <div class="aj-card2__image">
                                                <?php if (has_post_thumbnail()) { the_post_thumbnail('medium_large', ['loading' => 'lazy']); } else { ?><div class="aj-voyages-image-fallback"></div><?php } ?>
                                                <?php if ($duration_text) { ?><span class="aj-card2__badge aj-card2__badge--info"><i class="far fa-clock"></i> <?php echo esc_html($duration_text); ?></span><?php } ?>
                                            </div>
                                            <div class="aj-card2__body">
                                                <h3 class="aj-card2__title"><?php the_title(); ?></h3>
                                                <div class="aj-card2__location"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($place ?: __('Lieu non renseigne', 'ajinsafro-traveler-home')); ?></div>
                                                <div class="aj-card2__meta">
                                                    <span class="aj-card2__category"><?php echo esc_html($category_label ?: ($type ?: __('Activite', 'ajinsafro-traveler-home'))); ?></span>
                                                    <?php if ($age_min !== '' || $age_max !== '') { ?>
                                                        <span class="aj-card2__category">
                                                            <?php echo esc_html(trim(($age_min !== '' ? $age_min . '+' : '') . ($age_max !== '' ? ' / ' . $age_max : ''))); ?>
                                                        </span>
                                                    <?php } ?>
                                                </div>
                                                <p class="aj-voyages-excerpt"><?php echo esc_html($excerpt); ?></p>
                                                <div class="aj-card2__footer">
                                                    <div>
                                                        <?php if ($adult_price !== '') { ?><span class="aj-card2__price-label"><?php esc_html_e('Adulte', 'ajinsafro-traveler-home'); ?></span><div class="aj-card2__price"><?php echo esc_html(number_format((float) $adult_price, 0, ',', ' ')); ?> <span class="aj-card2__price-currency">MAD</span></div><?php } ?>
                                                        <?php if ($child_price !== '') { ?><span class="aj-card2__price-note"><?php echo esc_html__('Enfant : ', 'ajinsafro-traveler-home') . esc_html(number_format((float) $child_price, 0, ',', ' ')) . ' MAD'; ?></span><?php } ?>
                                                    </div>
                                                    <span class="aj-card2__cta"><?php esc_html_e("Voir l'activite", 'ajinsafro-traveler-home'); ?></span>
                                                </div>
                                            </div>
                                        </a>
                                    </article>
                                <?php } ?>
                            </div>
                            <div class="aj-voyages-pagination"><?php echo paginate_links(['total' => (int) $q->max_num_pages, 'current' => $paged, 'type' => 'list', 'add_args' => $pagination_args]); ?></div>
                        <?php } else { ?>
                            <div class="aj-voyages-empty"><h3><?php esc_html_e('Aucune activite trouvee', 'ajinsafro-traveler-home'); ?></h3><p><?php esc_html_e('Essayez un autre filtre ou elargissez votre recherche.', 'ajinsafro-traveler-home'); ?></p></div>
                        <?php } wp_reset_postdata(); ?>
                    </main>
                </div>
            </div>
        </section>
    </div>
</div>

<?php get_footer(); ?>
