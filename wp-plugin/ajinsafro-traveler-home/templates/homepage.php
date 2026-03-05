<?php
/**
 * Shortcode template: [ajth_homepage]
 *
 * Renders: header + hero + search + tendances + séjours + destinations +
 *          bons coins + promos + footer
 * Matches the AjinSafro mockup design (index(2).html)
 *
 * Expected variable: $settings (from ajth_get_settings()).
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home">
    <?php include AJTH_DIR . 'parts/header.php'; ?>
    <?php include AJTH_DIR . 'parts/hero.php'; ?>

    <?php if ( ! empty( $settings['sections']['last_minute'] ) ) : ?>
        <?php include AJTH_DIR . 'parts/last-minute.php'; ?>
    <?php endif; ?>

    <?php if ( ! empty( $settings['sections']['accommodations'] ) ) : ?>
        <?php include AJTH_DIR . 'parts/accommodations.php'; ?>
    <?php endif; ?>

    <?php
    $dbr = ajth_get_destinations_by_region();
    if ( ! empty( $dbr['enabled'] ) && ! empty( $dbr['items'] ) ) :
        include AJTH_DIR . 'parts/destinations-by-region.php';
    elseif ( ! empty( $settings['sections']['regions'] ) ) :
        include AJTH_DIR . 'parts/regions.php';
    endif;
    ?>

    <?php if ( ! empty( $settings['sections']['good_spots'] ) ) : ?>
        <?php include AJTH_DIR . 'parts/good-spots.php'; ?>
    <?php endif; ?>

    <?php
    $show_promotions = ! empty( $settings['sections']['promotions'] );
    if ( ! $show_promotions && ! empty( $settings['promotions']['items'] ) && is_array( $settings['promotions']['items'] ) ) {
        $show_promotions = true;
    }
    if ( $show_promotions ) :
        ?>
        <?php include AJTH_DIR . 'parts/promotions.php'; ?>
    <?php endif; ?>

    <?php include AJTH_DIR . 'parts/newsletter.php'; ?>
    </div>
</div>
