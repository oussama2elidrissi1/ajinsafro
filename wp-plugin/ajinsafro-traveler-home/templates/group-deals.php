<?php
/**
 * Group Deals Page Template
 *
 * Displays group-deal voyages from the Laravel voyages table.
 * Visually identical to the Voyages catalog page.
 *
 * @package AjinsafroTravelerHome
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

global $wpdb;

$settings = ajth_get_settings();

/* ── Filters from GET ───────────────────────────────────────────────────────── */
$search      = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$dest        = isset($_GET['dest']) ? sanitize_text_field(wp_unslash($_GET['dest'])) : '';
$group_size  = isset($_GET['group_size']) ? max(2, absint($_GET['group_size'])) : 0;
$orderby_raw = isset($_GET['catalog_orderby']) ? sanitize_text_field(wp_unslash($_GET['catalog_orderby'])) : 'date';
$paged       = max(1, absint(get_query_var('paged')), absint(get_query_var('page')), absint($_GET['paged'] ?? 1));
$per_page    = 12;
$offset      = ($paged - 1) * $per_page;

$has_any_filter = $search !== '' || $dest !== '';

/* ── Detect voyages table ───────────────────────────────────────────────────── */
// The Laravel voyages table can share the same DB as WordPress.
// We try the raw table name first, then with the WP prefix.
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

$deals          = [];
$total_deals    = 0;
$destinations   = [];
$db_available   = ($voyages_table !== null);

if ($db_available) {
    $valid_statuses = ['actif', 'published', 'active', 'publish'];
    $status_placeholders = implode(',', array_fill(0, count($valid_statuses), '%s'));

    /* ── Base WHERE ────────────────────────────────────────────────────── */
    $where_parts  = ["status IN ($status_placeholders)", 'is_group_deal = 1'];
    $where_values = $valid_statuses;

    if ($search !== '') {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where_parts[]  = '(name LIKE %s OR destination LIKE %s OR description LIKE %s)';
        $where_values[] = $like;
        $where_values[] = $like;
        $where_values[] = $like;
    }

    if ($dest !== '') {
        $where_parts[]  = 'destination = %s';
        $where_values[] = $dest;
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where_parts);

    /* ── ORDER ─────────────────────────────────────────────────────────── */
    $order_sql = match ($orderby_raw) {
        'title'      => 'ORDER BY name ASC',
        'title_desc' => 'ORDER BY name DESC',
        default      => 'ORDER BY updated_at DESC',
    };

    /* ── Count ─────────────────────────────────────────────────────────── */
    $count_sql   = "SELECT COUNT(*) FROM `{$voyages_table}` {$where_sql}";
    $total_deals = (int) $wpdb->get_var($wpdb->prepare($count_sql, ...$where_values));

    /* ── Rows ──────────────────────────────────────────────────────────── */
    $rows_sql = "
        SELECT id, name, slug, destination, duration_text, price_from, currency,
               featured_image, accroche, min_people, max_people, wp_post_id
        FROM `{$voyages_table}`
        {$where_sql}
        {$order_sql}
        LIMIT %d OFFSET %d
    ";
    $row_values = array_merge($where_values, [$per_page, $offset]);
    $deals      = $wpdb->get_results($wpdb->prepare($rows_sql, ...$row_values));

    /* ── Destinations list for filter ──────────────────────────────────── */
    $dest_sql    = "SELECT DISTINCT destination FROM `{$voyages_table}` WHERE status IN ($status_placeholders) AND is_group_deal = 1 AND destination IS NOT NULL AND destination != '' ORDER BY destination ASC";
    $destinations = $wpdb->get_col($wpdb->prepare($dest_sql, ...$valid_statuses));
}

$max_pages       = $total_deals > 0 ? (int) ceil($total_deals / $per_page) : 1;
$group_deals_url = function_exists('ajth_get_group_deals_url') ? ajth_get_group_deals_url() : home_url('/group-deals/');

/* ── Helpers ────────────────────────────────────────────────────────────────── */
$format_price = static function ($price, $currency = '') {
    $p = (float) $price;
    if ($p <= 0) return '';
    $sym = $currency ?: 'MAD';
    return number_format($p, 0, ',', ' ') . ' ' . esc_html($sym);
};

// Build image URL: prefer WordPress thumbnail, fall back to Laravel storage
$booking_base = defined('AJTH_BOOKING_URL')
    ? rtrim(AJTH_BOOKING_URL, '/')
    : rtrim(get_option('ajinsafro_booking_url', 'https://booking.ajinsafro.net'), '/');

$get_image_url = static function ($deal) use ($booking_base) {
    // 1. WordPress thumbnail (most reliable)
    if (! empty($deal->wp_post_id)) {
        $thumb = get_the_post_thumbnail_url((int) $deal->wp_post_id, 'medium_large');
        if ($thumb) return $thumb;
    }
    // 2. Laravel storage path via booking domain /storage/...
    if (! empty($deal->featured_image)) {
        return $booking_base . '/storage/' . ltrim($deal->featured_image, '/');
    }
    return '';
};

// Pagination args to preserve filters
$pagination_args_raw = [
    's'               => $search,
    'dest'            => $dest,
    'group_size'      => $group_size > 0 ? (string) $group_size : '',
    'catalog_orderby' => $orderby_raw !== 'date' ? $orderby_raw : '',
];
$pagination_args = array_filter($pagination_args_raw, static fn ($v) => $v !== '' && $v !== null);
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-voyages-page">
        <?php ajth_render_site_header($settings); ?>

        <section class="aj-voyages-catalog">
            <div class="aj-container aj-voyages-catalog__container">
                <input type="checkbox" id="aj-voyages-filters-toggle" class="aj-voyages-filters-toggle" tabindex="-1" aria-hidden="true">
                <label for="aj-voyages-filters-toggle" class="aj-voyages-filters-backdrop" aria-hidden="true"></label>

                <div class="aj-voyages-catalog__grid">

                    <!-- ── Sidebar filters ─────────────────────────────────────────────── -->
                    <aside class="aj-voyages-filters-sidebar" id="aj-voyages-filters-panel"
                           aria-label="<?php esc_attr_e('Filtres Group Deals', 'ajinsafro-traveler-home'); ?>">

                        <label for="aj-voyages-filters-toggle" class="aj-voyages-filters-close"
                               aria-label="<?php esc_attr_e('Fermer les filtres', 'ajinsafro-traveler-home'); ?>">
                            <span aria-hidden="true">&times;</span>
                        </label>

                        <form method="get" action="<?php echo esc_url($group_deals_url); ?>" class="aj-voyages-filters-form">
                            <h3 class="aj-voyages-filters-title"><?php esc_html_e('Filtres', 'ajinsafro-traveler-home'); ?></h3>

                            <!-- Recherche -->
                            <div class="aj-voyages-filter-field">
                                <label for="gd-filter-s" class="aj-voyages-filter-label">
                                    <?php esc_html_e('Recherche', 'ajinsafro-traveler-home'); ?>
                                </label>
                                <input type="text" name="s" id="gd-filter-s"
                                       value="<?php echo esc_attr($search); ?>"
                                       class="aj-voyages-filter-input"
                                       placeholder="<?php esc_attr_e('Nom, destination…', 'ajinsafro-traveler-home'); ?>"
                                       autocomplete="off">
                            </div>

                            <!-- Destination -->
                            <?php if (! empty($destinations)) { ?>
                            <div class="aj-voyages-filter-field">
                                <label for="gd-filter-dest" class="aj-voyages-filter-label">
                                    <?php esc_html_e('Destination', 'ajinsafro-traveler-home'); ?>
                                </label>
                                <select name="dest" id="gd-filter-dest" class="aj-voyages-filter-input">
                                    <option value=""><?php esc_html_e('Toutes les destinations', 'ajinsafro-traveler-home'); ?></option>
                                    <?php foreach ($destinations as $d) { ?>
                                        <option value="<?php echo esc_attr($d); ?>" <?php selected($dest, $d); ?>>
                                            <?php echo esc_html($d); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <?php } ?>

                            <!-- Taille du groupe -->
                            <div class="aj-voyages-filter-field">
                                <label for="gd-filter-group-size" class="aj-voyages-filter-label">
                                    <?php esc_html_e('Taille du groupe', 'ajinsafro-traveler-home'); ?>
                                </label>
                                <input type="number" name="group_size" id="gd-filter-group-size"
                                       min="2" max="200"
                                       value="<?php echo $group_size > 0 ? esc_attr($group_size) : ''; ?>"
                                       class="aj-voyages-filter-input"
                                       placeholder="<?php esc_attr_e('ex: 10', 'ajinsafro-traveler-home'); ?>">
                            </div>

                            <!-- Actions -->
                            <div class="aj-voyages-filter-actions">
                                <button type="submit" class="aj-voyages-filter-btn aj-voyages-filter-btn--primary">
                                    <?php esc_html_e('Appliquer', 'ajinsafro-traveler-home'); ?>
                                </button>
                                <?php if ($has_any_filter) { ?>
                                    <a href="<?php echo esc_url($group_deals_url); ?>"
                                       class="aj-voyages-filter-btn aj-voyages-filter-btn--ghost">
                                        <?php esc_html_e('Réinitialiser', 'ajinsafro-traveler-home'); ?>
                                    </a>
                                <?php } ?>
                            </div>
                        </form>
                    </aside>

                    <!-- ── Main catalog area ───────────────────────────────────────────── -->
                    <main class="aj-voyages-catalog__main">

                        <label for="aj-voyages-filters-toggle" class="aj-voyages-filters-mobile-trigger">
                            <i class="fas fa-sliders-h" aria-hidden="true"></i>
                            <?php esc_html_e('Filtres', 'ajinsafro-traveler-home'); ?>
                        </label>

                        <!-- Toolbar -->
                        <div class="aj-voyages-toolbar">
                            <div class="aj-voyages-toolbar__left">
                                <h2 class="aj-voyages-toolbar__title">
                                    <?php echo $has_any_filter
                                        ? esc_html__('Offres correspondantes', 'ajinsafro-traveler-home')
                                        : esc_html__('Group Deals', 'ajinsafro-traveler-home'); ?>
                                </h2>
                                <p class="aj-voyages-toolbar__count">
                                    <?php printf(
                                        esc_html(_n('%d résultat', '%d résultats', $total_deals, 'ajinsafro-traveler-home')),
                                        $total_deals
                                    ); ?>
                                </p>
                            </div>
                            <div class="aj-voyages-toolbar__sort">
                                <form method="get" class="aj-voyages-sort-form" action="<?php echo esc_url($group_deals_url); ?>">
                                    <?php foreach ($pagination_args as $pk => $pv) {
                                        if ($pk === 'catalog_orderby') continue; ?>
                                        <input type="hidden" name="<?php echo esc_attr($pk); ?>" value="<?php echo esc_attr($pv); ?>">
                                    <?php } ?>
                                    <label class="aj-voyages-sort-form__label" for="gd-catalog-orderby">
                                        <?php esc_html_e('Trier par', 'ajinsafro-traveler-home'); ?>
                                    </label>
                                    <select name="catalog_orderby" id="gd-catalog-orderby"
                                            class="aj-voyages-sort-form__select" onchange="this.form.submit()">
                                        <option value="date" <?php selected($orderby_raw, 'date'); ?>><?php esc_html_e('Plus récents', 'ajinsafro-traveler-home'); ?></option>
                                        <option value="title" <?php selected($orderby_raw, 'title'); ?>><?php esc_html_e('Titre (A–Z)', 'ajinsafro-traveler-home'); ?></option>
                                        <option value="title_desc" <?php selected($orderby_raw, 'title_desc'); ?>><?php esc_html_e('Titre (Z–A)', 'ajinsafro-traveler-home'); ?></option>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <?php if (! $db_available) { ?>
                            <!-- DB not available -->
                            <div class="aj-voyages-empty">
                                <p style="font-size:18px;font-weight:600;margin-bottom:8px;">
                                    <?php esc_html_e('Données non disponibles', 'ajinsafro-traveler-home'); ?>
                                </p>
                                <p style="color:#94a3b8;">
                                    <?php esc_html_e('Impossible de se connecter à la base de données des voyages.', 'ajinsafro-traveler-home'); ?>
                                </p>
                            </div>

                        <?php } elseif (empty($deals)) { ?>
                            <!-- Empty state -->
                            <div class="aj-voyages-empty">
                                <i class="fas fa-users" style="font-size:48px;color:#cbd5e1;margin-bottom:16px;"></i>
                                <p style="font-size:18px;font-weight:600;margin-bottom:8px;">
                                    <?php esc_html_e('Aucun Group Deal trouvé', 'ajinsafro-traveler-home'); ?>
                                </p>
                                <p style="color:#94a3b8;">
                                    <?php echo $has_any_filter
                                        ? esc_html__("Essayez avec d'autres critères.", 'ajinsafro-traveler-home')
                                        : esc_html__("Aucune offre groupe n'est disponible pour le moment.", 'ajinsafro-traveler-home'); ?>
                                </p>
                                <?php if ($has_any_filter) { ?>
                                    <a href="<?php echo esc_url($group_deals_url); ?>" class="aj-btn aj-btn-primary" style="margin-top:16px;display:inline-block;">
                                        <?php esc_html_e('Voir toutes les offres', 'ajinsafro-traveler-home'); ?>
                                    </a>
                                <?php } ?>
                            </div>

                        <?php } else { ?>
                            <!-- Grid cards -->
                            <div class="aj-voyages-grid">
                                <?php foreach ($deals as $deal) {
                                    $img_url       = $get_image_url($deal);
                                    $price_str     = $format_price($deal->price_from, $deal->currency);
                                    $deal_dest     = esc_html(trim($deal->destination ?? ''));
                                    $deal_duration = esc_html(trim($deal->duration_text ?? ''));
                                    $excerpt       = esc_html(wp_trim_words(strip_tags($deal->accroche ?? ''), 18, '…'));
                                    $min_ppl       = (int) ($deal->min_people ?? 0);
                                    $max_ppl       = (int) ($deal->max_people ?? 0);

                                    // Public URL: use WordPress permalink if wp_post_id exists, else WP slug
                                    if (! empty($deal->wp_post_id)) {
                                        $deal_url = get_permalink((int) $deal->wp_post_id);
                                    } elseif (! empty($deal->slug)) {
                                        $deal_url = home_url('/voyages/' . $deal->slug . '/');
                                    } else {
                                        $deal_url = '';
                                    }
                                    ?>
                                    <article class="aj-voyages-grid__item">
                                        <?php if ($deal_url) { ?>
                                            <a href="<?php echo esc_url($deal_url); ?>" class="aj-card2 aj-hover-glass">
                                        <?php } else { ?>
                                            <div class="aj-card2">
                                        <?php } ?>

                                            <div class="aj-card2__image">
                                                <?php if ($img_url) { ?>
                                                    <img src="<?php echo esc_url($img_url); ?>"
                                                         alt="<?php echo esc_attr($deal->name); ?>"
                                                         loading="lazy">
                                                <?php } else { ?>
                                                    <div class="aj-voyages-image-fallback"></div>
                                                <?php } ?>

                                                <!-- "Group Deal" badge -->
                                                <span class="aj-card2__badge aj-card2__badge--info" style="left:0.75rem;right:auto;">
                                                    <i class="fas fa-users" aria-hidden="true"></i>
                                                    <?php esc_html_e('Group Deal', 'ajinsafro-traveler-home'); ?>
                                                </span>

                                                <?php if ($deal_duration) { ?>
                                                    <span class="aj-card2__badge aj-card2__badge--info" style="right:0.75rem;left:auto;">
                                                        <i class="far fa-clock" aria-hidden="true"></i>
                                                        <?php echo $deal_duration; ?>
                                                    </span>
                                                <?php } ?>
                                            </div>

                                            <div class="aj-card2__body">
                                                <?php if ($deal_dest) { ?>
                                                    <p class="aj-card2__location">
                                                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                                        <?php echo $deal_dest; ?>
                                                    </p>
                                                <?php } ?>

                                                <h3 class="aj-card2__title"><?php echo esc_html($deal->name); ?></h3>

                                                <?php if ($excerpt) { ?>
                                                    <p class="aj-card2__desc"><?php echo $excerpt; ?></p>
                                                <?php } ?>

                                                <?php if ($min_ppl > 0) { ?>
                                                    <p class="aj-card2__meta" style="font-size:0.78rem;color:#64748b;margin-bottom:0.5rem;">
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

                                                <div class="aj-card2__footer">
                                                    <div>
                                                        <?php if ($price_str) { ?>
                                                            <span class="aj-card2__price-label">
                                                                <?php esc_html_e('à partir de', 'ajinsafro-traveler-home'); ?>
                                                            </span>
                                                            <div class="aj-card2__price">
                                                                <?php echo $price_str; ?>
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
                                <?php } ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($max_pages > 1) {
                                $pagination = paginate_links([
                                    'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                                    'format'    => '?paged=%#%',
                                    'current'   => $paged,
                                    'total'     => $max_pages,
                                    'type'      => 'array',
                                    'prev_text' => '«',
                                    'next_text' => '»',
                                    'add_args'  => $pagination_args,
                                ]);
                                if (! empty($pagination)) { ?>
                                    <nav class="aj-voyages-pagination" aria-label="<?php esc_attr_e('Pagination Group Deals', 'ajinsafro-traveler-home'); ?>">
                                        <?php foreach ($pagination as $page_link) {
                                            echo wp_kses_post($page_link);
                                        } ?>
                                    </nav>
                                <?php }
                            } ?>

                        <?php } // end if deals ?>

                    </main><!-- /.aj-voyages-catalog__main -->
                </div><!-- /.aj-voyages-catalog__grid -->
            </div><!-- /.aj-container -->
        </section><!-- /.aj-voyages-catalog -->

    </div><!-- /#aj-home -->
</div><!-- /.aj-home-wrap -->

<?php get_footer(); ?>
