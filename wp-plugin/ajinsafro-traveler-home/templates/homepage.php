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

$default_order = array( 'last_minute', 'accommodations', 'holiday_theme', 'regions', 'good_spots', 'whatsapp_banner', 'cruises' );
$section_order = ! empty( $settings['section_order'] ) && is_array( $settings['section_order'] )
    ? $settings['section_order']
    : $default_order;
// Footer is rendered by wp_footer (ajth_render_footer_sitewide or theme footer), not as a section inside content.
$newsletter_key = array_search( 'newsletter', $section_order );
if ( $newsletter_key !== false ) {
    unset( $section_order[ $newsletter_key ] );
    $section_order = array_values( $section_order );
}
$custom_sections = ! empty( $settings['custom_sections'] ) && is_array( $settings['custom_sections'] )
    ? $settings['custom_sections']
    : array();
$sections = isset( $settings['sections'] ) && is_array( $settings['sections'] ) ? $settings['sections'] : array();
$dbr = ajth_get_destinations_by_region();
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home">
        <?php ajth_render_site_header( $settings ); ?>
        <?php ajth_render_home_hero( $settings ); ?>

        <?php
        // Ensure whatsapp_banner is in section_order even if not saved yet
        if ( ! in_array( 'whatsapp_banner', $section_order ) ) {
            $section_order[] = 'whatsapp_banner';
        }
        $section_order = function_exists( 'ajth_normalize_section_order_with_holiday_theme' )
            ? ajth_normalize_section_order_with_holiday_theme( $section_order, ! empty( $settings['holiday_theme']['enabled'] ) )
            : $section_order;
        foreach ( $section_order as $key ) {
            // WhatsApp banner: show by default when section key is missing; otherwise respect sections.whatsapp_banner or whatsapp_banner.enabled
            if ( $key === 'whatsapp_banner' ) {
                $enabled = ! array_key_exists( 'whatsapp_banner', $sections )
                    || ! empty( $sections['whatsapp_banner'] )
                    || ! empty( $settings['whatsapp_banner']['enabled'] );
            } elseif ( $key === 'holiday_theme' ) {
                $enabled = array_key_exists( 'holiday_theme', $sections )
                    ? ! empty( $sections['holiday_theme'] )
                    : ! empty( $settings['holiday_theme']['enabled'] );
            } else {
                $enabled = isset( $sections[ $key ] ) && $sections[ $key ];
            }
            if ( ! $enabled ) {
                continue;
            }
            if ( $key === 'cruises' && function_exists( 'ajth_render_reference_accordion_section' ) ) {
                ajth_render_reference_accordion_section();
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
                case 'holiday_theme':
                    include AJTH_DIR . 'parts/holiday-theme.php';
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
                case 'whatsapp_banner':
                    include AJTH_DIR . 'parts/whatsapp-banner.php';
                    break;
                case 'cruises':
                    include AJTH_DIR . 'parts/cruises.php';
                    break;
            }
        }
        ?>
    </div>
</div>
