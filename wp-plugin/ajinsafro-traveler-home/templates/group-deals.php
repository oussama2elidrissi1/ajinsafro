<?php
/**
 * Group Deals Page Template
 *
 * Clone visuel de voyages.php — mêmes classes CSS, même structure.
 * La seule différence est la source de données : table Laravel `voyages`
 * filtrée sur is_group_deal = 1 (pas de WP_Query).
 *
 * @package AjinsafroTravelerHome
 */
if (! defined('ABSPATH')) {
    exit;
}

get_header();

global $wpdb;

$settings = ajth_get_settings();

/* ── Pagination ─────────────────────────────────────────────────────────────── */
$paged = max(
    1,
    absint(get_query_var('paged')),
    absint(get_query_var('page')),
    absint($_GET['paged'] ?? 0)
);
$per_page = 12;
$offset   = ($paged - 1) * $per_page;

/* ── Filters from GET (miroir voyages-filters.php) ─────────────────────────── */
$search_text   = isset($_GET['s'])           ? sanitize_text_field(wp_unslash($_GET['s']))           : '';
$dest          = isset($_GET['dest'])         ? sanitize_text_field(wp_unslash($_GET['dest']))         : '';
$depart_date   = isset($_GET['depart_date'])  ? sanitize_text_field(wp_unslash($_GET['depart_date']))  : '';
$price_min     = isset($_GET['price_min'])    ? absint($_GET['price_min'])    : 0;
$price_max     = isset($_GET['price_max'])    ? absint($_GET['price_max'])    : 0;
$group_size    = isset($_GET['group_size'])   ? max(2, absint($_GET['group_size'])) : 0;
$catalog_orderby = isset($_GET['catalog_orderby']) ? sanitize_text_field(wp_unslash($_GET['catalog_orderby'])) : 'date';
if (! in_array($catalog_orderby, ['date', 'title', 'title_desc'], true)) {
    $catalog_orderby = 'date';
}

$is_search = $search_text !== '';
$has_any_filter = $is_search
    || $dest !== ''
    || ($depart_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $depart_date))
    || $price_min > 0
    || $price_max > 0
    || $group_size > 0;

/* ── Detect voyages table (Laravel shares same DB) ──────────────────────────── */
$voyages_table = null;
foreach (['voyages', $wpdb->prefix . 'voyages'] as $_candidate) {
    $exists = $wpdb->get_var($wpdb->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s LIMIT 1',
        $_candidate
    ));
    if ($exists) {
        $voyages_table = $_candidate;
        break;
    }
}

/* ── Query group deals ───────────────────────────────────────────────────────── */
$deals      = [];
$found_posts = 0;
$max_num_pages = 1;

if ($voyages_table !== null) {
    $valid_statuses  = ['actif', 'published', 'active', 'publish'];
    $status_ph       = implode(',', array_fill(0, count($valid_statuses), '%s'));
    $where_parts     = ["status IN ($status_ph)", 'is_group_deal = 1'];
    $where_values    = $valid_statuses;

    // Search filter
    if ($search_text !== '') {
        $like = '%' . $wpdb->esc_like($search_text) . '%';
        $where_parts[]  = '(name LIKE %s OR destination LIKE %s OR description LIKE %s)';
        $where_values[] = $like;
        $where_values[] = $like;
        $where_values[] = $like;
    }

    // Destination filter
    if ($dest !== '') {
        $where_parts[]  = 'destination = %s';
        $where_values[] = $dest;
    }

    // Price filter
    if ($price_min > 0) {
        $where_parts[]  = 'price_from >= %f';
        $where_values[] = (float) $price_min;
    }
    if ($price_max > 0) {
        $where_parts[]  = 'price_from <= %f';
        $where_values[] = (float) $price_max;
    }

    // Group size: keep voyages with max_people >= group_size OR max_people = 0
    if ($group_size > 0) {
        $where_parts[]  = '(max_people = 0 OR max_people IS NULL OR max_people >= %d)';
        $where_values[] = $group_size;
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where_parts);

    // Order
    $order_sql = match ($catalog_orderby) {
        'title'      => 'ORDER BY name ASC',
        'title_desc' => 'ORDER BY name DESC',
        default      => 'ORDER BY updated_at DESC',
    };

    // Count
    $found_posts = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM `{$voyages_table}` {$where_sql}", ...$where_values)
    );
    $max_num_pages = $found_posts > 0 ? (int) ceil($found_posts / $per_page) : 1;

    // Rows
    $deals = (array) $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, name, slug, destination, duration_text, price_from, currency,
                    featured_image, accroche, min_people, max_people, wp_post_id
             FROM `{$voyages_table}` {$where_sql} {$order_sql} LIMIT %d OFFSET %d",
            ...array_merge($where_values, [$per_page, $offset])
        )
    );
}

/* ── Pagination args (preserve all filters) ─────────────────────────────────── */
$gd_pagination_args = array_filter([
    's'               => $search_text,
    'dest'            => $dest,
    'depart_date'     => $depart_date,
    'price_min'       => $price_min > 0  ? (string) $price_min  : '',
    'price_max'       => $price_max > 0  ? (string) $price_max  : '',
    'group_size'      => $group_size > 0 ? (string) $group_size : '',
    'catalog_orderby' => $catalog_orderby !== 'date' ? $catalog_orderby : '',
], static fn ($v) => $v !== '' && $v !== null);

/* ── Page URL ────────────────────────────────────────────────────────────────── */
$group_deals_url = function_exists('ajth_get_group_deals_url')
    ? ajth_get_group_deals_url()
    : home_url('/group-deals/');

/* ── Helpers ─────────────────────────────────────────────────────────────────── */
$booking_base = rtrim(get_option('ajinsafro_booking_url', 'https://booking.ajinsafro.net'), '/');

$get_deal_image = static function (object $deal) use ($booking_base): string {
    if (! empty($deal->wp_post_id)) {
        $thumb = get_the_post_thumbnail_url((int) $deal->wp_post_id, 'medium_large');
        if ($thumb) return $thumb;
    }
    if (! empty($deal->featured_image)) {
        return $booking_base . '/storage/' . ltrim($deal->featured_image, '/');
    }
    return '';
};

$get_deal_url = static function (object $deal): string {
    if (! empty($deal->wp_post_id)) {
        $url = get_permalink((int) $deal->wp_post_id);
        if ($url) return $url;
    }
    if (! empty($deal->slug)) {
        return home_url('/voyages/' . $deal->slug . '/');
    }
    return '';
};
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-voyages-page">
        <?php ajth_render_site_header($settings); ?>

        <section class="aj-voyages-catalog">
            <div class="aj-container aj-voyages-catalog__container">
                <input type="checkbox" id="aj-voyages-filters-toggle" class="aj-voyages-filters-toggle" tabindex="-1" aria-hidden="true">
                <label for="aj-voyages-filters-toggle" class="aj-voyages-filters-backdrop" aria-hidden="true"></label>

                <div class="aj-voyages-catalog__grid">

                    <!-- ── Sidebar filters ────────────────────────────────────────── -->
                    <aside class="aj-voyages-filters-sidebar" id="aj-voyages-filters-panel"
                           aria-label="<?php esc_attr_e('Filtres Group Deals', 'ajinsafro-traveler-home'); ?>">
                        <label for="aj-voyages-filters-toggle" class="aj-voyages-filters-close"
                               aria-label="<?php esc_attr_e('Fermer les filtres', 'ajinsafro-traveler-home'); ?>">
                            <span aria-hidden="true">&times;</span>
                        </label>
                        <?php include AJTH_DIR . 'parts/group-deals-filters.php'; ?>
                    </aside>

                    <!-- ── Main catalog ───────────────────────────────────────────── -->
                    <main class="aj-voyages-catalog__main">

                        <label for="aj-voyages-filters-toggle" class="aj-voyages-filters-mobile-trigger">
                            <i class="fas fa-sliders-h" aria-hidden="true"></i>
                            <?php esc_html_e('Filtres', 'ajinsafro-traveler-home'); ?>
                        </label>

                        <!-- Toolbar : titre + count + tri — identique à voyages -->
                        <div class="aj-voyages-toolbar">
                            <div class="aj-voyages-toolbar__left">
                                <h2 class="aj-voyages-toolbar__title">
                                    <?php echo $has_any_filter
                                        ? esc_html__('Offres correspondantes', 'ajinsafro-traveler-home')
                                        : esc_html__('Group Deals', 'ajinsafro-traveler-home'); ?>
                                </h2>
                                <p class="aj-voyages-toolbar__count">
                                    <?php printf(
                                        esc_html(_n('%d résultat', '%d résultats', $found_posts, 'ajinsafro-traveler-home')),
                                        $found_posts
                                    ); ?>
                                </p>
                            </div>
                            <div class="aj-voyages-toolbar__sort">
                                <form method="get" class="aj-voyages-sort-form"
                                      action="<?php echo esc_url($group_deals_url); ?>">
                                    <?php foreach ($gd_pagination_args as $pk => $pv) {
                                        if ($pk === 'catalog_orderby') continue; ?>
                                        <input type="hidden" name="<?php echo esc_attr($pk); ?>"
                                               value="<?php echo esc_attr($pv); ?>">
                                    <?php } ?>
                                    <label class="aj-voyages-sort-form__label" for="gd-catalog-orderby">
                                        <?php esc_html_e('Trier par', 'ajinsafro-traveler-home'); ?>
                                    </label>
                                    <select name="catalog_orderby" id="gd-catalog-orderby"
                                            class="aj-voyages-sort-form__select" onchange="this.form.submit()">
                                        <option value="date"       <?php selected($catalog_orderby, 'date'); ?>><?php esc_html_e('Plus récents',   'ajinsafro-traveler-home'); ?></option>
                                        <option value="title"      <?php selected($catalog_orderby, 'title'); ?>><?php esc_html_e('Titre (A–Z)',     'ajinsafro-traveler-home'); ?></option>
                                        <option value="title_desc" <?php selected($catalog_orderby, 'title_desc'); ?>><?php esc_html_e('Titre (Z–A)', 'ajinsafro-traveler-home'); ?></option>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <?php if ($voyages_table === null) { ?>
                            <!-- DB non disponible -->
                            <div class="aj-voyages-empty">
                                <i class="fas fa-database" style="font-size:48px;color:#cbd5e1;margin-bottom:16px;"></i>
                                <p style="font-size:18px;font-weight:600;margin-bottom:8px;">
                                    <?php esc_html_e('Données non disponibles', 'ajinsafro-traveler-home'); ?>
                                </p>
                                <p style="color:#94a3b8;">
                                    <?php esc_html_e('La table des voyages est inaccessible depuis WordPress.', 'ajinsafro-traveler-home'); ?>
                                </p>
                            </div>

                        <?php } elseif (empty($deals)) { ?>
                            <!-- Empty state — identique à voyages -->
                            <div class="aj-voyages-empty">
                                <i class="fas fa-users" style="font-size:48px;color:#cbd5e1;margin-bottom:16px;"></i>
                                <p style="font-size:18px;font-weight:600;margin-bottom:8px;">
                                    <?php esc_html_e('Aucun Group Deal trouvé', 'ajinsafro-traveler-home'); ?>
                                </p>
                                <p style="color:#94a3b8;">
                                    <?php echo $has_any_filter
                                        ? esc_html__("Essayez avec d'autres critères.", 'ajinsafro-traveler-home')
                                        : esc_html__("Aucune offre groupe disponible pour le moment.", 'ajinsafro-traveler-home'); ?>
                                </p>
                            </div>

                        <?php } else { ?>
                            <!-- Grid — structure identique à aj-voyages-grid dans voyages.php -->
                            <div class="aj-voyages-grid">
                                <?php foreach ($deals as $deal) {
                                    $img_url   = $get_deal_image($deal);
                                    $deal_url  = $get_deal_url($deal);
                                    $dest_txt  = esc_html(trim($deal->destination ?? ''));
                                    $duration  = esc_html(trim($deal->duration_text ?? ''));
                                    $excerpt   = wp_trim_words(strip_tags($deal->accroche ?? ''), 18, '…');
                                    $price     = (float) ($deal->price_from ?? 0);
                                    $currency  = esc_html(trim($deal->currency ?: 'DHS'));
                                    $min_ppl   = (int) ($deal->min_people ?? 0);
                                    $max_ppl   = (int) ($deal->max_people ?? 0);

                                    $price_display = $price > 0
                                        ? number_format($price, 0, ',', ' ')
                                        : '';
                                    ?>
                                    <article class="aj-voyages-grid__item">
                                        <?php if ($deal_url) { ?>
                                            <a href="<?php echo esc_url($deal_url); ?>" class="aj-card2 aj-hover-glass">
                                        <?php } else { ?>
                                            <div class="aj-card2">
                                        <?php } ?>

                                            <!-- Image — même structure que voyages -->
                                            <div class="aj-card2__image">
                                                <?php if ($img_url) { ?>
                                                    <img src="<?php echo esc_url($img_url); ?>"
                                                         alt="<?php echo esc_attr($deal->name); ?>"
                                                         loading="lazy">
                                                <?php } else { ?>
                                                    <div class="aj-voyages-image-fallback"></div>
                                                <?php } ?>

                                                <!-- Durée — badge top-right comme voyages -->
                                                <?php if ($duration) { ?>
                                                    <span class="aj-card2__badge aj-card2__badge--info">
                                                        <i class="far fa-clock" aria-hidden="true"></i>
                                                        <?php echo $duration; ?>
                                                    </span>
                                                <?php } ?>
                                            </div>

                                            <!-- Body — même structure que voyages -->
                                            <div class="aj-card2__body">
                                                <?php if ($dest_txt) { ?>
                                                    <p class="aj-card2__location" style="font-size:0.75rem;color:#64748b;margin-bottom:4px;display:flex;align-items:center;gap:4px;">
                                                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                                        <?php echo $dest_txt; ?>
                                                    </p>
                                                <?php } ?>

                                                <h3 class="aj-card2__title"><?php echo esc_html($deal->name); ?></h3>

                                                <?php if ($excerpt) { ?>
                                                    <p class="aj-card2__desc"><?php echo esc_html($excerpt); ?></p>
                                                <?php } ?>

                                                <?php if ($min_ppl > 0) { ?>
                                                    <p style="font-size:0.75rem;color:#64748b;margin-bottom:6px;display:flex;align-items:center;gap:4px;">
                                                        <i class="fas fa-users" aria-hidden="true"></i>
                                                        <?php
                                                        if ($max_ppl > 0) {
                                                            printf(
                                                                esc_html__('%d–%d personnes', 'ajinsafro-traveler-home'),
                                                                $min_ppl, $max_ppl
                                                            );
                                                        } else {
                                                            printf(
                                                                esc_html__('%d+ personnes', 'ajinsafro-traveler-home'),
                                                                $min_ppl
                                                            );
                                                        }
                                                        ?>
                                                    </p>
                                                <?php } ?>

                                                <!-- Footer prix + CTA — identique à voyages -->
                                                <div class="aj-card2__footer">
                                                    <div>
                                                        <?php if ($price_display) { ?>
                                                            <span class="aj-card2__price-label">
                                                                <?php esc_html_e('à partir de', 'ajinsafro-traveler-home'); ?>
                                                            </span>
                                                            <div class="aj-card2__price">
                                                                <?php echo esc_html($price_display); ?>
                                                                <span class="aj-card2__price-currency"><?php echo $currency; ?></span>
                                                            </div>
                                                            <span class="aj-card2__price-note">
                                                                <?php esc_html_e('prix par personne', 'ajinsafro-traveler-home'); ?>
                                                            </span>
                                                        <?php } else { ?>
                                                            <span class="aj-card2__price-label">
                                                                <?php esc_html_e('Devis sur demande', 'ajinsafro-traveler-home'); ?>
                                                            </span>
                                                        <?php } ?>
                                                    </div>
                                                    <?php if ($deal_url) { ?>
                                                        <span class="aj-card2__cta">
                                                            <?php esc_html_e("VOIR L'OFFRE", 'ajinsafro-traveler-home'); ?>
                                                        </span>
                                                    <?php } ?>
                                                </div>
                                            </div>

                                        <?php if ($deal_url) { ?>
                                            </a>
                                        <?php } else { ?>
                                            </div>
                                        <?php } ?>
                                    </article>
                                <?php } /* end foreach */ ?>
                            </div>

                            <!-- Pagination — identique à voyages -->
                            <?php
                            $pagination = paginate_links([
                                'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                                'format'    => '?paged=%#%',
                                'current'   => $paged,
                                'total'     => $max_num_pages,
                                'type'      => 'array',
                                'prev_text' => '«',
                                'next_text' => '»',
                                'add_args'  => $gd_pagination_args,
                            ]);
                            if (! empty($pagination)) { ?>
                                <nav class="aj-voyages-pagination"
                                     aria-label="<?php esc_attr_e('Pagination Group Deals', 'ajinsafro-traveler-home'); ?>">
                                    <?php foreach ($pagination as $page_link) {
                                        echo wp_kses_post($page_link);
                                    } ?>
                                </nav>
                            <?php } ?>

                        <?php } /* end if deals */ ?>

                    </main><!-- /.aj-voyages-catalog__main -->
                </div><!-- /.aj-voyages-catalog__grid -->
            </div><!-- /.aj-container -->
        </section><!-- /.aj-voyages-catalog -->

    </div><!-- /#aj-home -->
</div><!-- /.aj-home-wrap -->

<?php get_footer(); ?>
