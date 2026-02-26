<?php
/**
 * Home Page Template
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
$settings = ajth_get_settings();
?>

<div id="aj-home" class="aj-home">
    <?php include AJTH_DIR . 'parts/hero.php'; ?>
    <?php if ( ! empty( $settings['sections']['last_minute'] ) ) : ?>
        <?php include AJTH_DIR . 'parts/last-minute.php'; ?>
    <?php endif; ?>
    <?php if ( ! empty( $settings['sections']['regions'] ) ) : ?>
        <?php include AJTH_DIR . 'parts/regions.php'; ?>
    <?php endif; ?>
    <?php if ( ! empty( $settings['sections']['good_spots'] ) ) : ?>
        <?php include AJTH_DIR . 'parts/good-spots.php'; ?>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
