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

    <!-- TEST SECTION - À SUPPRIMER -->
    <section style="background:#ffff00 !important;padding:50px 20px !important;margin:30px 0 !important;border:5px solid #ff0000 !important;display:block !important;visibility:visible !important;">
        <div style="max-width:1280px;margin:0 auto;text-align:center;">
            <h2 style="color:#ff0000 !important;font-size:3rem !important;font-weight:bold !important;margin:0 0 20px !important;">🔴 TEST SYNCHRONISATION 🔴</h2>
            <p style="color:#000 !important;font-size:1.5rem !important;">Si vous voyez ce texte, la synchronisation fonctionne !</p>
            <p style="color:#666 !important;font-size:1rem !important;">Date: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </section>
    <!-- FIN TEST SECTION -->

    <?php include AJTH_DIR . 'parts/promotions.php'; ?>

    <?php include AJTH_DIR . 'parts/newsletter.php'; ?>
    </div>
</div>
