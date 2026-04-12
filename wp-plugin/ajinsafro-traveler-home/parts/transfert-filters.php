<?php
if (! defined('ABSPATH')) {
    exit;
}

$page_url = function_exists('ajth_get_transfert_page_url')
    ? ajth_get_transfert_page_url()
    : home_url('/transfert/');

$search_value = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
if ($search_value === '' && isset($_GET['s'])) {
    $search_value = sanitize_text_field(wp_unslash($_GET['s']));
}

$from = isset($_GET['from']) ? sanitize_text_field(wp_unslash($_GET['from'])) : '';
if ($from === '' && isset($_GET['pickup'])) {
    $from = sanitize_text_field(wp_unslash($_GET['pickup']));
}
if ($from === '' && isset($_GET['departure'])) {
    $from = sanitize_text_field(wp_unslash($_GET['departure']));
}

$to = isset($_GET['to']) ? sanitize_text_field(wp_unslash($_GET['to'])) : '';
if ($to === '' && isset($_GET['dropoff'])) {
    $to = sanitize_text_field(wp_unslash($_GET['dropoff']));
}
if ($to === '' && isset($_GET['arrival'])) {
    $to = sanitize_text_field(wp_unslash($_GET['arrival']));
}

$city = isset($_GET['city']) ? sanitize_text_field(wp_unslash($_GET['city'])) : '';
if ($city === '' && isset($_GET['cars_address'])) {
    $city = sanitize_text_field(wp_unslash($_GET['cars_address']));
}

$type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
$price_min = isset($_GET['price_min']) ? absint($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? absint($_GET['price_max']) : 0;
$catalog_orderby = isset($_GET['catalog_orderby']) ? sanitize_text_field(wp_unslash($_GET['catalog_orderby'])) : 'date';

global $wpdb;
$cars_table = $wpdb->prefix . 'st_cars';
$postmeta_table = $wpdb->postmeta;

$cities = $wpdb->get_col("SELECT DISTINCT TRIM(cars_address) FROM {$cars_table} WHERE cars_address IS NOT NULL AND TRIM(cars_address) <> '' ORDER BY cars_address ASC LIMIT 200");
$types = array_unique(array_merge(
    (array) $wpdb->get_col($wpdb->prepare("SELECT DISTINCT TRIM(meta_value) FROM {$postmeta_table} WHERE meta_key = %s AND meta_value IS NOT NULL AND TRIM(meta_value) <> '' ORDER BY meta_value ASC LIMIT 50", 'aj_transfer_type')),
    (array) $wpdb->get_col($wpdb->prepare("SELECT DISTINCT TRIM(meta_value) FROM {$postmeta_table} WHERE meta_key = %s AND meta_value IS NOT NULL AND TRIM(meta_value) <> '' ORDER BY meta_value ASC LIMIT 50", 'aj_transfer_vehicle_type'))
));
sort($types);
?>

<form method="get" action="<?php echo esc_url($page_url); ?>" class="aj-voyages-filters-form">
    <input type="hidden" name="catalog_orderby" value="<?php echo esc_attr($catalog_orderby); ?>">

    <div class="aj-voyages-filters__intro">
        <h3 class="aj-voyages-filters__heading"><?php esc_html_e('Affiner la recherche', 'ajinsafro-traveler-home'); ?></h3>
        <p class="aj-voyages-filters__hint"><?php esc_html_e('Les filtres exploitent les services Traveler existants et les metas publiques de trajet.', 'ajinsafro-traveler-home'); ?></p>
    </div>

    <div class="aj-voyages-filters__card">
        <label class="aj-voyages-filters__field">
            <span class="aj-voyages-filters__label"><?php esc_html_e('Recherche', 'ajinsafro-traveler-home'); ?></span>
            <input type="text" name="search" class="aj-voyages-filters__input" value="<?php echo esc_attr($search_value); ?>" placeholder="<?php esc_attr_e('Service, ville, trajet...', 'ajinsafro-traveler-home'); ?>">
        </label>
    </div>

    <div class="aj-voyages-filters__card">
        <div class="aj-voyages-filters__row">
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Depart', 'ajinsafro-traveler-home'); ?></span>
                <input type="text" name="from" class="aj-voyages-filters__input" value="<?php echo esc_attr($from); ?>" placeholder="<?php esc_attr_e('Ville ou point de depart', 'ajinsafro-traveler-home'); ?>">
            </label>
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Arrivee', 'ajinsafro-traveler-home'); ?></span>
                <input type="text" name="to" class="aj-voyages-filters__input" value="<?php echo esc_attr($to); ?>" placeholder="<?php esc_attr_e('Ville ou point d arrivee', 'ajinsafro-traveler-home'); ?>">
            </label>
        </div>
    </div>

    <div class="aj-voyages-filters__card">
        <div class="aj-voyages-filters__row">
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Ville catalogue', 'ajinsafro-traveler-home'); ?></span>
                <select name="city" class="aj-voyages-filters__select">
                    <option value=""><?php esc_html_e('Toutes les villes', 'ajinsafro-traveler-home'); ?></option>
                    <?php foreach ((array) $cities as $item) { ?>
                        <option value="<?php echo esc_attr($item); ?>" <?php selected($city, $item); ?>><?php echo esc_html($item); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Type', 'ajinsafro-traveler-home'); ?></span>
                <?php if (! empty($types)) { ?>
                    <select name="type" class="aj-voyages-filters__select">
                        <option value=""><?php esc_html_e('Tous les types', 'ajinsafro-traveler-home'); ?></option>
                        <?php foreach ($types as $item) { ?>
                            <option value="<?php echo esc_attr($item); ?>" <?php selected($type, $item); ?>><?php echo esc_html($item); ?></option>
                        <?php } ?>
                    </select>
                <?php } else { ?>
                    <input type="text" name="type" class="aj-voyages-filters__input" value="<?php echo esc_attr($type); ?>" placeholder="<?php esc_attr_e('Type de transfert ou vehicule', 'ajinsafro-traveler-home'); ?>">
                <?php } ?>
            </label>
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
