<?php
if (! defined('ABSPATH')) {
    exit;
}

$hebergement_page_url = function_exists('ajth_get_hebergement_page_url')
    ? ajth_get_hebergement_page_url()
    : home_url('/hebergement/');

$search_value = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
if ($search_value === '' && isset($_GET['location_name'])) {
    $search_value = sanitize_text_field(wp_unslash($_GET['location_name']));
}
if ($search_value === '' && isset($_GET['s'])) {
    $search_value = sanitize_text_field(wp_unslash($_GET['s']));
}

$address = isset($_GET['address']) ? sanitize_text_field(wp_unslash($_GET['address'])) : '';
$hotel_type = isset($_GET['hotel_type']) ? sanitize_text_field(wp_unslash($_GET['hotel_type'])) : '';
$stars = isset($_GET['stars']) ? absint($_GET['stars']) : 0;
$price_min = isset($_GET['price_min']) ? absint($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? absint($_GET['price_max']) : 0;
$featured = isset($_GET['featured']) && (string) $_GET['featured'] === '1';
$catalog_orderby = isset($_GET['catalog_orderby']) ? sanitize_text_field(wp_unslash($_GET['catalog_orderby'])) : 'date';

global $wpdb;
$addresses = $wpdb->get_col(
    "SELECT DISTINCT TRIM(address)
     FROM {$wpdb->prefix}st_hotel
     WHERE address IS NOT NULL AND TRIM(address) <> ''
     ORDER BY address ASC
     LIMIT 200"
);
$hotel_types = taxonomy_exists('hotel_type')
    ? get_terms([
        'taxonomy' => 'hotel_type',
        'hide_empty' => true,
    ])
    : [];
?>

<form method="get" action="<?php echo esc_url($hebergement_page_url); ?>" class="aj-voyages-filters-form">
    <input type="hidden" name="catalog_orderby" value="<?php echo esc_attr($catalog_orderby); ?>">

    <div class="aj-voyages-filters__intro">
        <h3 class="aj-voyages-filters__heading"><?php esc_html_e('Affiner la recherche', 'ajinsafro-traveler-home'); ?></h3>
        <p class="aj-voyages-filters__hint"><?php esc_html_e('Filtrez les hébergements réellement disponibles dans la base.', 'ajinsafro-traveler-home'); ?></p>
    </div>

    <div class="aj-voyages-filters__card">
        <h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Recherche', 'ajinsafro-traveler-home'); ?></h4>
        <label class="aj-voyages-filters__field">
            <span class="aj-voyages-filters__label"><?php esc_html_e('Nom ou mot-clé', 'ajinsafro-traveler-home'); ?></span>
            <input type="text" name="search" class="aj-voyages-filters__input" value="<?php echo esc_attr($search_value); ?>" placeholder="<?php esc_attr_e('Hôtel, ville, adresse…', 'ajinsafro-traveler-home'); ?>">
        </label>
    </div>

    <div class="aj-voyages-filters__card">
        <h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Localisation', 'ajinsafro-traveler-home'); ?></h4>
        <label class="aj-voyages-filters__field">
            <span class="aj-voyages-filters__label"><?php esc_html_e('Ville / destination', 'ajinsafro-traveler-home'); ?></span>
            <span class="aj-voyages-filters__control">
                <select name="address" class="aj-voyages-filters__select">
                    <option value=""><?php esc_html_e('Toutes les localisations', 'ajinsafro-traveler-home'); ?></option>
                    <?php foreach ((array) $addresses as $item) { ?>
                        <option value="<?php echo esc_attr($item); ?>" <?php selected($address, $item); ?>><?php echo esc_html($item); ?></option>
                    <?php } ?>
                </select>
            </span>
        </label>
    </div>

    <?php if (! empty($hotel_types) && ! is_wp_error($hotel_types)) { ?>
        <div class="aj-voyages-filters__card">
            <h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Type', 'ajinsafro-traveler-home'); ?></h4>
            <label class="aj-voyages-filters__field">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Type d’hébergement', 'ajinsafro-traveler-home'); ?></span>
                <span class="aj-voyages-filters__control">
                    <select name="hotel_type" class="aj-voyages-filters__select">
                        <option value=""><?php esc_html_e('Tous les types', 'ajinsafro-traveler-home'); ?></option>
                        <?php foreach ($hotel_types as $term) { ?>
                            <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($hotel_type, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                        <?php } ?>
                    </select>
                </span>
            </label>
        </div>
    <?php } ?>

    <div class="aj-voyages-filters__card">
        <h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Prix & standing', 'ajinsafro-traveler-home'); ?></h4>
        <div class="aj-voyages-filters__row">
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Étoiles', 'ajinsafro-traveler-home'); ?></span>
                <select name="stars" class="aj-voyages-filters__select">
                    <option value=""><?php esc_html_e('Toutes', 'ajinsafro-traveler-home'); ?></option>
                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                        <option value="<?php echo esc_attr((string) $i); ?>" <?php selected($stars, $i); ?>><?php echo esc_html($i); ?> ★</option>
                    <?php } ?>
                </select>
            </label>
            <label class="aj-voyages-filters__checkbox">
                <input type="checkbox" name="featured" value="1" <?php checked($featured); ?>>
                <span><?php esc_html_e('À la une uniquement', 'ajinsafro-traveler-home'); ?></span>
            </label>
        </div>
        <div class="aj-voyages-filters__row">
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Prix min', 'ajinsafro-traveler-home'); ?></span>
                <input type="number" min="0" name="price_min" class="aj-voyages-filters__input" value="<?php echo esc_attr($price_min); ?>" placeholder="—">
            </label>
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Prix max', 'ajinsafro-traveler-home'); ?></span>
                <input type="number" min="0" name="price_max" class="aj-voyages-filters__input" value="<?php echo esc_attr($price_max); ?>" placeholder="—">
            </label>
        </div>
    </div>

    <div class="aj-voyages-filters__actions">
        <button type="submit" class="aj-voyages-filters__submit">
            <i class="fas fa-search" aria-hidden="true"></i>
            <?php esc_html_e('Appliquer les filtres', 'ajinsafro-traveler-home'); ?>
        </button>
        <a class="aj-voyages-filters__reset" href="<?php echo esc_url($hebergement_page_url); ?>">
            <?php esc_html_e('Réinitialiser', 'ajinsafro-traveler-home'); ?>
        </a>
    </div>
</form>
