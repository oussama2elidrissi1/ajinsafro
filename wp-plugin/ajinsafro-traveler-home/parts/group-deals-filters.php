<?php
/**
 * Group Deals filter sidebar / drawer.
 *
 * Expects the parent template to provide the current filter variables.
 *
 * @package AjinsafroTravelerHome
 */
if (! defined('ABSPATH')) {
    exit;
}

$group_deals_filter_prefix = isset($group_deals_filter_prefix) ? (string) $group_deals_filter_prefix : 'ajgd';
$group_deals_url = isset($group_deals_url) ? (string) $group_deals_url : home_url('/group-deals/');
$available_destinations = isset($available_destinations) && is_array($available_destinations) ? $available_destinations : [];
$available_services = isset($available_services) && is_array($available_services) ? $available_services : [];
?>

<form method="get" action="<?php echo esc_url($group_deals_url); ?>" class="group-filters-form">
    <input type="hidden" name="catalog_orderby" value="<?php echo esc_attr($sort ?? 'recommended'); ?>">

    <details class="accordion" open>
        <summary>Recherche</summary>
        <div class="filter-body">
            <label class="filter-field" for="<?php echo esc_attr($group_deals_filter_prefix . '-search'); ?>">
                <span>Nom ou mot-cle</span>
                <input
                    id="<?php echo esc_attr($group_deals_filter_prefix . '-search'); ?>"
                    class="filter-search"
                    type="text"
                    name="s"
                    value="<?php echo esc_attr($search_text ?? ''); ?>"
                    placeholder="Ville, pays, destination..."
                    autocomplete="off"
                >
            </label>

            <label class="filter-field" for="<?php echo esc_attr($group_deals_filter_prefix . '-dest'); ?>">
                <span>Destination</span>
                <select id="<?php echo esc_attr($group_deals_filter_prefix . '-dest'); ?>" name="dest" class="filter-select">
                    <option value="">Toutes les destinations</option>
                    <?php foreach ($available_destinations as $destination_option) { ?>
                        <option value="<?php echo esc_attr($destination_option); ?>" <?php selected(($dest ?? ''), $destination_option); ?>>
                            <?php echo esc_html($destination_option); ?>
                        </option>
                    <?php } ?>
                </select>
            </label>
        </div>
    </details>

    <details class="accordion" open>
        <summary>Budget et groupe</summary>
        <div class="filter-body">
            <div class="mini-inputs">
                <input type="number" min="0" name="price_min" value="<?php echo ! empty($price_min) ? esc_attr((string) $price_min) : ''; ?>" placeholder="Min DH">
                <input type="number" min="0" name="price_max" value="<?php echo ! empty($price_max) ? esc_attr((string) $price_max) : ''; ?>" placeholder="Max DH">
            </div>
            <label class="filter-field" for="<?php echo esc_attr($group_deals_filter_prefix . '-group-size'); ?>">
                <span>Voyageurs minimum</span>
                <input
                    id="<?php echo esc_attr($group_deals_filter_prefix . '-group-size'); ?>"
                    type="number"
                    min="0"
                    name="group_size"
                    value="<?php echo ! empty($group_size) ? esc_attr((string) $group_size) : ''; ?>"
                    placeholder="2 voyageurs"
                >
            </label>
        </div>
    </details>

    <?php if ($has_featured_offers || $has_promo_offers || $has_guaranteed_offers) { ?>
        <details class="accordion" open>
            <summary>Offres speciales</summary>
            <div class="filter-body">
                <?php if ($has_featured_offers) { ?>
                    <label class="check-row">
                        <input type="checkbox" name="featured" value="1" <?php checked(! empty($featured_only)); ?>>
                        Selection Ajinsafro
                    </label>
                <?php } ?>
                <?php if ($has_promo_offers) { ?>
                    <label class="check-row">
                        <input type="checkbox" name="promo" value="1" <?php checked(! empty($promo_only)); ?>>
                        Promotions actives
                    </label>
                <?php } ?>
                <?php if ($has_guaranteed_offers) { ?>
                    <label class="check-row">
                        <input type="checkbox" name="guaranteed" value="1" <?php checked(! empty($guaranteed_only)); ?>>
                        Departs garantis
                    </label>
                <?php } ?>
            </div>
        </details>
    <?php } ?>

    <?php if (! empty($available_services)) { ?>
        <details class="accordion" open>
            <summary>Services inclus</summary>
            <div class="filter-body">
                <?php foreach ($available_services as $service_key => $service_label) { ?>
                    <label class="check-row">
                        <input type="checkbox" name="service[]" value="<?php echo esc_attr($service_key); ?>" <?php checked(in_array($service_key, $selected_services ?? [], true)); ?>>
                        <?php echo esc_html($service_label); ?>
                    </label>
                <?php } ?>
            </div>
        </details>
    <?php } ?>

    <div class="filter-actions">
        <button type="submit" class="primary-btn">Appliquer les filtres</button>
        <a class="secondary-btn" href="<?php echo esc_url($group_deals_url); ?>">Reinitialiser</a>
    </div>
</form>
