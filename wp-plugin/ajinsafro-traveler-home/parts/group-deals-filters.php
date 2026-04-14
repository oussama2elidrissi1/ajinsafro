<?php
/**
 * Part: Group Deals filters — left sidebar (catalog)
 * Calqué sur parts/voyages-filters.php — mêmes classes CSS.
 */
if (! defined('ABSPATH')) {
    exit;
}

$group_deals_url = function_exists('ajth_get_group_deals_url')
    ? ajth_get_group_deals_url()
    : home_url('/group-deals/');

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

// Fetch destinations from Laravel voyages table
global $wpdb;
$gd_destinations = [];
foreach (['voyages', $wpdb->prefix . 'voyages'] as $_t) {
    $exists = $wpdb->get_var($wpdb->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s LIMIT 1',
        $_t
    ));
    if ($exists) {
        $valid_statuses = ['actif', 'published', 'active', 'publish'];
        $ph = implode(',', array_fill(0, count($valid_statuses), '%s'));
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT TRIM(destination) FROM `{$_t}` WHERE status IN ($ph) AND is_group_deal = 1 AND destination IS NOT NULL AND TRIM(destination) <> '' ORDER BY destination ASC LIMIT 300",
            ...$valid_statuses
        ));
        foreach ((array) $rows as $label) {
            $label = is_string($label) ? trim($label) : '';
            if ($label !== '') {
                $gd_destinations[] = $label;
            }
        }
        break;
    }
}
?>

<form method="get" action="<?php echo esc_url($group_deals_url); ?>" class="aj-voyages-filters-form">
    <input type="hidden" name="catalog_orderby" value="<?php echo esc_attr($catalog_orderby); ?>">

    <div class="aj-voyages-filters__intro">
        <h3 class="aj-voyages-filters__heading"><?php esc_html_e('Affiner la recherche', 'ajinsafro-traveler-home'); ?></h3>
        <p class="aj-voyages-filters__hint"><?php esc_html_e('Trouvez le voyage de groupe idéal.', 'ajinsafro-traveler-home'); ?></p>
    </div>

    <div class="aj-voyages-filters__card">
        <h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Mots-clés', 'ajinsafro-traveler-home'); ?></h4>
        <label class="aj-voyages-filters__field">
            <span class="aj-voyages-filters__label"><?php esc_html_e('Recherche', 'ajinsafro-traveler-home'); ?></span>
            <input type="text" name="s" class="aj-voyages-filters__input"
                   value="<?php echo esc_attr($search_text); ?>"
                   placeholder="<?php esc_attr_e('Nom, destination…', 'ajinsafro-traveler-home'); ?>"
                   autocomplete="off">
        </label>
    </div>

    <div class="aj-voyages-filters__card">
        <h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Destination & dates', 'ajinsafro-traveler-home'); ?></h4>
        <label class="aj-voyages-filters__field">
            <span class="aj-voyages-filters__label"><?php esc_html_e('Destination', 'ajinsafro-traveler-home'); ?></span>
            <span class="aj-voyages-filters__control">
                <select name="dest" class="aj-voyages-filters__select">
                    <option value=""><?php esc_html_e('Toutes les destinations', 'ajinsafro-traveler-home'); ?></option>
                    <?php foreach ($gd_destinations as $d) { ?>
                        <option value="<?php echo esc_attr($d); ?>" <?php selected($dest, $d); ?>>
                            <?php echo esc_html($d); ?>
                        </option>
                    <?php } ?>
                </select>
            </span>
        </label>
        <?php if (empty($gd_destinations)) { ?>
            <p class="aj-voyages-filters__muted"><?php esc_html_e('Aucune destination disponible.', 'ajinsafro-traveler-home'); ?></p>
        <?php } ?>
        <label class="aj-voyages-filters__field">
            <span class="aj-voyages-filters__label"><?php esc_html_e('Date de départ', 'ajinsafro-traveler-home'); ?></span>
            <input type="date" name="depart_date" class="aj-voyages-filters__input"
                   value="<?php echo esc_attr($depart_date); ?>">
        </label>
    </div>

    <div class="aj-voyages-filters__card">
        <h4 class="aj-voyages-filters__card-title"><?php esc_html_e('Groupe & budget', 'ajinsafro-traveler-home'); ?></h4>
        <label class="aj-voyages-filters__field">
            <span class="aj-voyages-filters__label"><?php esc_html_e('Taille du groupe (pers.)', 'ajinsafro-traveler-home'); ?></span>
            <input type="number" min="2" max="200" name="group_size" class="aj-voyages-filters__input"
                   value="<?php echo $group_size > 0 ? esc_attr($group_size) : ''; ?>"
                   placeholder="<?php esc_attr_e('ex: 10', 'ajinsafro-traveler-home'); ?>">
        </label>
        <div class="aj-voyages-filters__row">
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Prix min (DHS)', 'ajinsafro-traveler-home'); ?></span>
                <input type="number" min="0" name="price_min" class="aj-voyages-filters__input"
                       value="<?php echo esc_attr($price_min); ?>" placeholder="—">
            </label>
            <label class="aj-voyages-filters__field aj-voyages-filters__field--half">
                <span class="aj-voyages-filters__label"><?php esc_html_e('Prix max (DHS)', 'ajinsafro-traveler-home'); ?></span>
                <input type="number" min="0" name="price_max" class="aj-voyages-filters__input"
                       value="<?php echo esc_attr($price_max); ?>" placeholder="—">
            </label>
        </div>
    </div>

    <div class="aj-voyages-filters__actions">
        <button type="submit" class="aj-voyages-filters__submit">
            <i class="fas fa-search" aria-hidden="true"></i>
            <?php esc_html_e('Appliquer les filtres', 'ajinsafro-traveler-home'); ?>
        </button>
        <a class="aj-voyages-filters__reset" href="<?php echo esc_url($group_deals_url); ?>">
            <?php esc_html_e('Réinitialiser', 'ajinsafro-traveler-home'); ?>
        </a>
    </div>
</form>
