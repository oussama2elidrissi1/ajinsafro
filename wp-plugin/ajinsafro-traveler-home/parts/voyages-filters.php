<?php
/**
 * Voyages filters partial.
 */
if (! defined('ABSPATH')) {
    exit;
}
?>

<form method="get" action="<?php echo esc_url($voyages_page_url); ?>" class="aj-voyages-booking-filters-form">
    <input type="hidden" name="sort" value="<?php echo esc_attr($sort); ?>">

    <details class="accordion" open>
        <summary>Recherche</summary>
        <div class="filter-body">
            <input class="filter-search" type="text" name="s" value="<?php echo esc_attr($search_text); ?>" placeholder="Nom, destination...">

            <select name="destination" class="filter-select">
                <option value="">Toutes les destinations</option>
                <?php foreach ((array) $destinations as $destination_option) { ?>
                    <option value="<?php echo esc_attr($destination_option['value']); ?>" <?php selected($dest, $destination_option['value']); ?>>
                        <?php echo esc_html($destination_option['label']); ?>
                    </option>
                <?php } ?>
            </select>

            <input type="date" name="date_depart" class="filter-search" value="<?php echo esc_attr($depart_date); ?>">
        </div>
    </details>

    <details class="accordion" open>
        <summary>Budget</summary>
        <div class="filter-body">
            <div class="mini-inputs">
                <input type="number" min="0" name="budget_min" value="<?php echo esc_attr($price_min > 0 ? (string) $price_min : ''); ?>" placeholder="Min DH">
                <input type="number" min="0" name="budget_max" value="<?php echo esc_attr($price_max > 0 ? (string) $price_max : ''); ?>" placeholder="Max DH">
            </div>
        </div>
    </details>

    <details class="accordion" open>
        <summary>Duree et voyageurs</summary>
        <div class="filter-body">
            <div class="mini-inputs">
                <input type="number" min="0" name="duration_min" value="<?php echo esc_attr($duration_min > 0 ? (string) $duration_min : ''); ?>" placeholder="Jours min">
                <input type="number" min="0" name="duration_max" value="<?php echo esc_attr($duration_max > 0 ? (string) $duration_max : ''); ?>" placeholder="Jours max">
            </div>
            <input type="number" min="1" name="voyageurs" value="<?php echo esc_attr($guests_min > 0 ? (string) $guests_min : ''); ?>" placeholder="Voyageurs minimum">
        </div>
    </details>

    <?php if (! empty($catalog_themes) || ! empty($catalog_tour_types) || ! empty($catalog_tags)) { ?>
        <details class="accordion" open>
            <summary>Type et themes</summary>
            <div class="filter-body">
                <?php if (! empty($catalog_themes)) { ?>
                    <select name="cat" class="filter-select">
                        <option value="">Tous les themes</option>
                        <?php foreach ((array) $catalog_themes as $theme_term) { ?>
                            <?php if (! $theme_term instanceof WP_Term) { continue; } ?>
                            <option value="<?php echo esc_attr($theme_term->slug); ?>" <?php selected($category_slug, $theme_term->slug); ?>>
                                <?php echo esc_html($theme_term->name); ?>
                            </option>
                        <?php } ?>
                    </select>
                <?php } ?>

                <?php if (! empty($catalog_tour_types)) { ?>
                    <select name="tour_type" class="filter-select">
                        <option value="">Tous les types</option>
                        <?php foreach ((array) $catalog_tour_types as $type_term) { ?>
                            <?php if (! $type_term instanceof WP_Term) { continue; } ?>
                            <option value="<?php echo esc_attr($type_term->slug); ?>" <?php selected($tour_type_slug, $type_term->slug); ?>>
                                <?php echo esc_html($type_term->name); ?>
                            </option>
                        <?php } ?>
                    </select>
                <?php } ?>

                <?php if (! empty($catalog_tags)) { ?>
                    <select name="tag" class="filter-select">
                        <option value="">Tous les services et tags</option>
                        <?php foreach ((array) $catalog_tags as $tag_term) { ?>
                            <?php if (! $tag_term instanceof WP_Term) { continue; } ?>
                            <option value="<?php echo esc_attr($tag_term->slug); ?>" <?php selected($tag_slug, $tag_term->slug); ?>>
                                <?php echo esc_html($tag_term->name); ?>
                            </option>
                        <?php } ?>
                    </select>
                <?php } ?>
            </div>
        </details>
    <?php } ?>

    <details class="accordion" open>
        <summary>Note client</summary>
        <div class="filter-body">
            <?php foreach ([0 => 'Toutes les notes', 8 => 'Tres bien 8+', 9 => 'Excellent 9+'] as $rating_value => $rating_label) { ?>
                <label class="radio-row">
                    <input type="radio" name="min_rating" value="<?php echo esc_attr((string) $rating_value); ?>" <?php checked((float) $min_rating, (float) $rating_value); ?>>
                    <span><?php echo esc_html($rating_label); ?></span>
                </label>
            <?php } ?>
        </div>
    </details>

    <details class="accordion" open>
        <summary>Disponibilite</summary>
        <div class="filter-body">
            <label class="check-row">
                <input type="checkbox" name="featured" value="1" <?php checked($featured_only); ?>>
                <span>Selection Ajinsafro</span>
            </label>
            <label class="check-row">
                <input type="checkbox" name="promo_only" value="1" <?php checked($promo_only); ?>>
                <span>Promotions uniquement</span>
            </label>
            <label class="check-row">
                <input type="checkbox" name="available_only" value="1" <?php checked($available_only); ?>>
                <span>Places disponibles</span>
            </label>
        </div>
    </details>

    <div class="aj-voyages-filter-actions">
        <button type="submit" class="primary-btn">Appliquer les filtres</button>
        <a class="secondary-btn aj-voyages-filter-actions__reset" href="<?php echo esc_url($voyages_page_url); ?>">Reinitialiser</a>
    </div>
</form>
