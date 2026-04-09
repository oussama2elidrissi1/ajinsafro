<?php
/**
 * Part: Voyages filters — left sidebar (catalog)
 */
if (! defined('ABSPATH')) {
    exit;
}

$voyages_page_url = function_exists('ajth_get_voyages_page_url')
    ? ajth_get_voyages_page_url()
    : home_url('/voyages/');

$search_text = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$location_name = isset($_GET['location_name']) ? sanitize_text_field(wp_unslash($_GET['location_name'])) : '';
$keyword_value = $location_name !== '' ? $location_name : $search_text;

$catalog_orderby = isset($_GET['catalog_orderby']) ? sanitize_text_field(wp_unslash($_GET['catalog_orderby'])) : 'date';
if (! in_array($catalog_orderby, ['date', 'title', 'title_desc'], true)) {
    $catalog_orderby = 'date';
}

$category_slug = isset($_GET['cat']) ? sanitize_text_field(wp_unslash($_GET['cat'])) : '';
$tag_slug = isset($_GET['tag']) ? sanitize_text_field(wp_unslash($_GET['tag'])) : '';
$featured = isset($_GET['featured']) && (string) $_GET['featured'] === '1';

$dest = isset($_GET['dest']) ? sanitize_text_field(wp_unslash($_GET['dest'])) : '';

$depart_date = isset($_GET['depart_date']) ? sanitize_text_field(wp_unslash($_GET['depart_date'])) : '';
$duration_min = isset($_GET['duration_min']) ? absint($_GET['duration_min']) : 0;
$duration_max = isset($_GET['duration_max']) ? absint($_GET['duration_max']) : 0;
$price_min = isset($_GET['price_min']) ? absint($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? absint($_GET['price_max']) : 0;

$catalog_themes = function_exists('ajth_get_catalog_tour_category_terms_ordered')
    ? ajth_get_catalog_tour_category_terms_ordered()
    : [];
$tags = get_terms(
    [
        'taxonomy' => 'tour_tag',
        'hide_empty' => true,
    ]
);

global $wpdb;
$destinations = [];
try {
    $postmeta = $wpdb->postmeta;
    $posts = $wpdb->posts;
    $rows = $wpdb->get_col(
        "
        SELECT DISTINCT TRIM(pm.meta_value) AS dest_label
        FROM {$postmeta} pm
        INNER JOIN {$posts} p ON p.ID = pm.post_id
        WHERE p.post_type = 'st_tours'
          AND p.post_status = 'publish'
          AND pm.meta_key IN ('address', 'aj_catalog_destination')
          AND pm.meta_value IS NOT NULL
          AND TRIM(pm.meta_value) <> ''
        ORDER BY dest_label ASC
        LIMIT 300
    "
    );
    foreach ((array) $rows as $label) {
        $label = is_string($label) ? trim($label) : '';
        if ($label === '') {
            continue;
        }
        $destinations[] = [
            'value' => $label,
            'label' => $label,
        ];
    }
} catch (\Throwable $e) {
    $destinations = [];
}
?>

<form method="get" action="<?php echo esc_url($voyages_page_url); ?>" class="aj-voyages-filters-form">
	<input type="hidden" name="catalog_orderby" value="<?php echo esc_attr($catalog_orderby); ?>">

	<div class="aj-voyages-filters__intro">
		<h3 class="aj-voyages-filters__heading"><?php esc_html_e('Affiner la recherche', 'ajinsafro-traveler-home'); ?></h3>
		<p class="aj-voyages-filters__hint"><?php esc_html_e('Combinez les critères pour trouver l’offre idéale.', 'ajinsafro-traveler-home'); ?></p>
	</div>

	<div class="aj-voyages-filters__card">
		<h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Mots-clés', 'ajinsafro-traveler-home'); ?></h4>
		<label class="aj-voyages-filters__field">
			<span class="aj-voyages-filters__label"><?php esc_html_e('Recherche', 'ajinsafro-traveler-home'); ?></span>
			<input type="text" name="s" class="aj-voyages-filters__input" value="<?php echo esc_attr($keyword_value); ?>" placeholder="<?php esc_attr_e('Nom, thème…', 'ajinsafro-traveler-home'); ?>" autocomplete="off">
		</label>
	</div>

	<div class="aj-voyages-filters__card">
		<h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Thème du voyage', 'ajinsafro-traveler-home'); ?></h4>
		<label class="aj-voyages-filters__field">
			<span class="aj-voyages-filters__label"><?php esc_html_e('Type de voyage', 'ajinsafro-traveler-home'); ?></span>
			<span class="aj-voyages-filters__control">
				<select name="cat" class="aj-voyages-filters__select" aria-label="<?php esc_attr_e('Filtrer par type de voyage', 'ajinsafro-traveler-home'); ?>">
					<option value=""><?php esc_html_e('Toutes les catégories', 'ajinsafro-traveler-home'); ?></option>
					<?php foreach ((array) $catalog_themes as $c) { ?>
						<?php
                        if (! $c instanceof WP_Term) {
                            continue;
                        }
					    ?>
						<option value="<?php echo esc_attr($c->slug); ?>" <?php selected($category_slug, $c->slug); ?>>
							<?php echo esc_html($c->name); ?>
						</option>
					<?php } ?>
				</select>
			</span>
		</label>
	</div>

	<div class="aj-voyages-filters__card">
		<h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Destination & dates', 'ajinsafro-traveler-home'); ?></h4>
		<label class="aj-voyages-filters__field">
			<span class="aj-voyages-filters__label"><?php esc_html_e('Destination', 'ajinsafro-traveler-home'); ?></span>
			<span class="aj-voyages-filters__control">
				<select name="dest" class="aj-voyages-filters__select">
					<option value=""><?php esc_html_e('Toutes les destinations', 'ajinsafro-traveler-home'); ?></option>
					<?php foreach ((array) $destinations as $d) { ?>
						<option value="<?php echo esc_attr($d['value']); ?>" <?php selected($dest, $d['value']); ?>>
							<?php echo esc_html($d['label']); ?>
						</option>
					<?php } ?>
				</select>
			</span>
		</label>
		<?php if (empty($destinations)) { ?>
			<p class="aj-voyages-filters__muted"><?php esc_html_e('Aucune destination renseignée pour le moment.', 'ajinsafro-traveler-home'); ?></p>
		<?php } ?>
		<label class="aj-voyages-filters__field">
			<span class="aj-voyages-filters__label"><?php esc_html_e('Date de départ', 'ajinsafro-traveler-home'); ?></span>
			<input type="date" name="depart_date" class="aj-voyages-filters__input" value="<?php echo esc_attr($depart_date); ?>">
		</label>
	</div>

	<div class="aj-voyages-filters__card">
		<h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Durée & budget', 'ajinsafro-traveler-home'); ?></h4>
		<div class="aj-voyages-filters__row">
			<label class="aj-voyages-filters__field aj-voyages-filters__field--half">
				<span class="aj-voyages-filters__label"><?php esc_html_e('Durée min (j)', 'ajinsafro-traveler-home'); ?></span>
				<input type="number" min="0" name="duration_min" class="aj-voyages-filters__input" value="<?php echo esc_attr($duration_min); ?>" placeholder="—">
			</label>
			<label class="aj-voyages-filters__field aj-voyages-filters__field--half">
				<span class="aj-voyages-filters__label"><?php esc_html_e('Durée max (j)', 'ajinsafro-traveler-home'); ?></span>
				<input type="number" min="0" name="duration_max" class="aj-voyages-filters__input" value="<?php echo esc_attr($duration_max); ?>" placeholder="—">
			</label>
		</div>
		<div class="aj-voyages-filters__row">
			<label class="aj-voyages-filters__field aj-voyages-filters__field--half">
				<span class="aj-voyages-filters__label"><?php esc_html_e('Prix min (DHS)', 'ajinsafro-traveler-home'); ?></span>
				<input type="number" min="0" name="price_min" class="aj-voyages-filters__input" value="<?php echo esc_attr($price_min); ?>" placeholder="—">
			</label>
			<label class="aj-voyages-filters__field aj-voyages-filters__field--half">
				<span class="aj-voyages-filters__label"><?php esc_html_e('Prix max (DHS)', 'ajinsafro-traveler-home'); ?></span>
				<input type="number" min="0" name="price_max" class="aj-voyages-filters__input" value="<?php echo esc_attr($price_max); ?>" placeholder="—">
			</label>
		</div>
	</div>

	<div class="aj-voyages-filters__card">
		<h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Tags & options', 'ajinsafro-traveler-home'); ?></h4>
		<label class="aj-voyages-filters__field">
			<span class="aj-voyages-filters__label"><?php esc_html_e('Tag', 'ajinsafro-traveler-home'); ?></span>
			<span class="aj-voyages-filters__control">
				<select name="tag" class="aj-voyages-filters__select">
					<option value=""><?php esc_html_e('Tous les tags', 'ajinsafro-traveler-home'); ?></option>
					<?php foreach ((array) $tags as $t) { ?>
						<option value="<?php echo esc_attr($t->slug); ?>" <?php selected($tag_slug, $t->slug); ?>>
							<?php echo esc_html($t->name); ?>
						</option>
					<?php } ?>
				</select>
			</span>
		</label>
		<label class="aj-voyages-filters__checkbox">
			<input type="checkbox" name="featured" value="1" <?php checked($featured); ?>>
			<span><?php esc_html_e('Offres mises en avant uniquement', 'ajinsafro-traveler-home'); ?></span>
		</label>
	</div>

	<div class="aj-voyages-filters__actions">
		<button type="submit" class="aj-voyages-filters__submit">
			<i class="fas fa-search" aria-hidden="true"></i>
			<?php esc_html_e('Appliquer les filtres', 'ajinsafro-traveler-home'); ?>
		</button>
		<a class="aj-voyages-filters__reset" href="<?php echo esc_url($voyages_page_url); ?>">
			<?php esc_html_e('Réinitialiser', 'ajinsafro-traveler-home'); ?>
		</a>
	</div>
</form>
