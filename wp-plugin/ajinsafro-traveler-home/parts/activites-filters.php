<?php
if (! defined('ABSPATH')) {
    exit;
}

$page_url = function_exists('ajth_get_activites_page_url')
    ? ajth_get_activites_page_url()
    : home_url('/activites/');

$search_value = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
if ($search_value === '' && isset($_GET['location_name'])) {
    $search_value = sanitize_text_field(wp_unslash($_GET['location_name']));
}
if ($search_value === '' && isset($_GET['s'])) {
    $search_value = sanitize_text_field(wp_unslash($_GET['s']));
}

$address = isset($_GET['address']) ? sanitize_text_field(wp_unslash($_GET['address'])) : '';
$type_activity = isset($_GET['type_activity']) ? sanitize_text_field(wp_unslash($_GET['type_activity'])) : '';
$category = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : '';
$age = isset($_GET['age']) ? absint($_GET['age']) : 0;
$price_min = isset($_GET['price_min']) ? absint($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? absint($_GET['price_max']) : 0;
$duration = isset($_GET['duration']) ? sanitize_text_field(wp_unslash($_GET['duration'])) : '';
$catalog_orderby = isset($_GET['catalog_orderby']) ? sanitize_text_field(wp_unslash($_GET['catalog_orderby'])) : 'date';

global $wpdb;
$activity_table = $wpdb->prefix . 'st_activity';
$postmeta_table = $wpdb->postmeta;

$addresses = $wpdb->get_col("SELECT DISTINCT TRIM(address) FROM {$activity_table} WHERE address IS NOT NULL AND TRIM(address) <> '' ORDER BY address ASC LIMIT 200");
$types = $wpdb->get_col("SELECT DISTINCT TRIM(type_activity) FROM {$activity_table} WHERE type_activity IS NOT NULL AND TRIM(type_activity) <> '' ORDER BY type_activity ASC LIMIT 50");
$durations = $wpdb->get_col("SELECT DISTINCT TRIM(duration) FROM {$activity_table} WHERE duration IS NOT NULL AND TRIM(duration) <> '' ORDER BY duration ASC LIMIT 50");
$categories = $wpdb->get_col($wpdb->prepare(
    "SELECT DISTINCT TRIM(meta_value) FROM {$postmeta_table} WHERE meta_key = %s AND meta_value IS NOT NULL AND TRIM(meta_value) <> '' ORDER BY meta_value ASC LIMIT 50",
    'aj_activity_category'
));
$ages = $wpdb->get_col(
    "SELECT DISTINCT CAST(meta_value AS UNSIGNED)
     FROM {$postmeta_table}
     WHERE meta_key IN ('aj_activity_min_age', 'aj_activity_max_age')
       AND meta_value IS NOT NULL
       AND TRIM(meta_value) <> ''
     ORDER BY CAST(meta_value AS UNSIGNED) ASC"
);
$ages = array_values(array_filter(array_map('absint', (array) $ages)));
?>

<form method="get" action="<?php echo esc_url($page_url); ?>" class="aj-voyages-filters-form">
    <input type="hidden" name="catalog_orderby" value="<?php echo esc_attr($catalog_orderby); ?>">

    <div class="aj-voyages-filters__intro">
        <h3 class="aj-voyages-filters__heading"><?php esc_html_e('Affiner la recherche', 'ajinsafro-traveler-home'); ?></h3>
        <p class="aj-voyages-filters__hint"><?php esc_html_e('Les filtres lisent les lignes reelles de st_activity et les metas publiques utiles.', 'ajinsafro-traveler-home'); ?></p>
    </div>

    <div class="aj-voyages-filters__card">
        <label class="aj-voyages-filters__field">
            <span class="aj-voyages-filters__label"><?php esc_html_e('Recherche', 'ajinsafro-traveler-home'); ?></span>
            <input type="text" name="search" class="aj-voyages-filters__input" value="<?php echo esc_attr($search_value); ?>" placeholder="<?php esc_attr_e('Nom, lieu, type...', 'ajinsafro-traveler-home'); ?>">
        </label>
    </div>

    <div class="aj-voyages-filters__card">
        <div class="aj-voyages-filters__row">
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Lieu', 'ajinsafro-traveler-home'); ?></span>
                <select name="address" class="aj-voyages-filters__select">
                    <option value=""><?php esc_html_e('Tous les lieux', 'ajinsafro-traveler-home'); ?></option>
                    <?php foreach ((array) $addresses as $item) { ?>
                        <option value="<?php echo esc_attr($item); ?>" <?php selected($address, $item); ?>><?php echo esc_html($item); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Type', 'ajinsafro-traveler-home'); ?></span>
                <select name="type_activity" class="aj-voyages-filters__select">
                    <option value=""><?php esc_html_e('Tous les types', 'ajinsafro-traveler-home'); ?></option>
                    <?php foreach ((array) $types as $item) { ?>
                        <option value="<?php echo esc_attr($item); ?>" <?php selected($type_activity, $item); ?>><?php echo esc_html($item); ?></option>
                    <?php } ?>
                </select>
            </label>
        </div>
    </div>

    <?php if (! empty($categories)) { ?>
        <div class="aj-voyages-filters__card">
            <label class="aj-voyages-filters__field">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Categorie', 'ajinsafro-traveler-home'); ?></span>
                <select name="category" class="aj-voyages-filters__select">
                    <option value=""><?php esc_html_e('Toutes les categories', 'ajinsafro-traveler-home'); ?></option>
                    <?php foreach ((array) $categories as $item) { ?>
                        <option value="<?php echo esc_attr($item); ?>" <?php selected($category, $item); ?>><?php echo esc_html($item); ?></option>
                    <?php } ?>
                </select>
            </label>
        </div>
    <?php } ?>

    <div class="aj-voyages-filters__card">
        <div class="aj-voyages-filters__row">
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Duree', 'ajinsafro-traveler-home'); ?></span>
                <select name="duration" class="aj-voyages-filters__select">
                    <option value=""><?php esc_html_e('Toutes', 'ajinsafro-traveler-home'); ?></option>
                    <?php foreach ((array) $durations as $item) { ?>
                        <option value="<?php echo esc_attr($item); ?>" <?php selected($duration, $item); ?>><?php echo esc_html($item); ?></option>
                    <?php } ?>
                </select>
            </label>
            <?php if (! empty($ages)) { ?>
                <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                    <span class="aj-voyages-filters__label"><?php esc_html_e('Age', 'ajinsafro-traveler-home'); ?></span>
                    <select name="age" class="aj-voyages-filters__select">
                        <option value=""><?php esc_html_e('Tous les ages', 'ajinsafro-traveler-home'); ?></option>
                        <?php foreach ($ages as $item) { ?>
                            <option value="<?php echo esc_attr((string) $item); ?>" <?php selected($age, $item); ?>><?php echo esc_html((string) $item); ?>+</option>
                        <?php } ?>
                    </select>
                </label>
            <?php } ?>
        </div>
        <div class="aj-voyages-filters__row">
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Prix min', 'ajinsafro-traveler-home'); ?></span>
                <input type="number" min="0" name="price_min" class="aj-voyages-filters__input" value="<?php echo esc_attr($price_min); ?>" placeholder="0">
            </label>
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Prix max', 'ajinsafro-traveler-home'); ?></span>
                <input type="number" min="0" name="price_max" class="aj-voyages-filters__input" value="<?php echo esc_attr($price_max); ?>" placeholder="0">
            </label>
        </div>
    </div>

    <div class="aj-voyages-filters__actions">
        <button type="submit" class="aj-voyages-filters__submit"><i class="fas fa-search" aria-hidden="true"></i><?php esc_html_e('Appliquer les filtres', 'ajinsafro-traveler-home'); ?></button>
        <a class="aj-voyages-filters__reset" href="<?php echo esc_url($page_url); ?>"><?php esc_html_e('Reinitialiser', 'ajinsafro-traveler-home'); ?></a>
    </div>
</form>
