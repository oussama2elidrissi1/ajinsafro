<?php
/**
 * Shortcode template: [ajth_homepage]
 * Uses same section_order and enable/disable as home.php
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $settings ) || ! is_array( $settings ) ) {
    $settings = ajth_get_settings();
}

$default_order = array( 'last_minute', 'accommodations', 'regions', 'good_spots', 'promotions', 'whatsapp_banner', 'cruises', 'newsletter' );
$section_order = ! empty( $settings['section_order'] ) && is_array( $settings['section_order'] )
    ? $settings['section_order']
    : $default_order;
// Newsletter (footer) always last so nothing appears below the footer
$newsletter_key = array_search( 'newsletter', $section_order );
if ( $newsletter_key !== false ) {
    unset( $section_order[ $newsletter_key ] );
    $section_order = array_values( $section_order );
    $section_order[] = 'newsletter';
}
$custom_sections = ! empty( $settings['custom_sections'] ) && is_array( $settings['custom_sections'] )
    ? $settings['custom_sections']
    : array();
$sections = isset( $settings['sections'] ) && is_array( $settings['sections'] ) ? $settings['sections'] : array();
$dbr = ajth_get_destinations_by_region();
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home">
        <?php include AJTH_DIR . 'parts/header.php'; ?>
        <?php include AJTH_DIR . 'parts/hero.php'; ?>

        <?php
        foreach ( $section_order as $key ) {
            $enabled = isset( $sections[ $key ] ) && $sections[ $key ];
            if ( ! $enabled ) {
                continue;
            }
            if ( strpos( $key, 'custom_' ) === 0 && isset( $custom_sections[ $key ] ) ) {
                $custom = $custom_sections[ $key ];
                $title = ! empty( $custom['title'] ) ? $custom['title'] : '';
                $content = ! empty( $custom['content'] ) ? $custom['content'] : '';
                if ( $content !== '' ) {
                    echo '<section class="aj-section aj-custom-section" id="aj-' . esc_attr( $key ) . '">';
                    if ( $title !== '' ) {
                        echo '<div class="aj-container"><h2 class="aj-section-title">' . esc_html( $title ) . '</h2></div>';
                    }
                    echo '<div class="aj-container aj-custom-section__content">' . do_shortcode( wp_kses_post( $content ) ) . '</div></section>';
                }
                continue;
            }
            switch ( $key ) {
                case 'last_minute':
                    include AJTH_DIR . 'parts/last-minute.php';
                    break;
                case 'accommodations':
                    include AJTH_DIR . 'parts/accommodations.php';
                    break;
                case 'regions':
                    if ( ! empty( $dbr['enabled'] ) && ! empty( $dbr['items'] ) ) {
                        include AJTH_DIR . 'parts/destinations-by-region.php';
                    } else {
                        include AJTH_DIR . 'parts/regions.php';
                    }
                    break;
                case 'good_spots':
                    include AJTH_DIR . 'parts/good-spots.php';
                    break;
                case 'promotions':
                    include AJTH_DIR . 'parts/promotions.php';
                    break;
                case 'whatsapp_banner':
                    include AJTH_DIR . 'parts/whatsapp-banner.php';
                    break;
                case 'cruises':
                    include AJTH_DIR . 'parts/cruises.php';
                    break;
                case 'newsletter':
                    include AJTH_DIR . 'parts/newsletter.php';
                    break;
            }
        }
        ?>
    </div>
</div>
