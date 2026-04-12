<?php
/**
 * Template Router
 *
 * Hooks into template_include to load our custom home template
 * when the visitor hits the front page. Keeps the Traveler theme
 * header / footer intact.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class AJTH_Template_Router {

    public function __construct() {
        add_filter( 'template_include', array( $this, 'maybe_override_front_page' ), 99 );
    }

    /**
     * Replace the front-page template with our own.
     *
     * @param string $template Current template path.
     * @return string
     */
    public function maybe_override_front_page( $template ) {
        // Handle maintenance page
        if ( is_page( 'maintenance' ) ) {
            $maintenance = AJTH_DIR . 'templates/maintenance.php';
            if ( file_exists( $maintenance ) ) {
                return $maintenance;
            }
        }

        // Handle login page
        if ( is_page( 'login' ) ) {
            $login = AJTH_DIR . 'templates/login.php';
            if ( file_exists( $login ) ) {
                return $login;
            }
        }

        // Handle vols page
        if ( is_page( 'vols' ) ) {
            $vols = AJTH_DIR . 'templates/vols.php';
            if ( file_exists( $vols ) ) {
                return $vols;
            }
        }

        // Handle voyages page, st_tours archive, and st_tours searches
        $is_voyages_context = is_page( 'voyages' ) 
            || is_post_type_archive( 'st_tours' )
            || ( is_search() && get_query_var( 'post_type' ) === 'st_tours' );

        if ( $is_voyages_context ) {
            $voyages = AJTH_DIR . 'templates/voyages.php';
            if ( file_exists( $voyages ) ) {
                return $voyages;
            }
        }

        // is_front_page() handles "Your homepage displays → A static page"
        // is_home() handles "Your homepage displays → Your latest posts"
        if ( is_front_page() || is_home() ) {

            $custom = AJTH_DIR . 'templates/home.php';

            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }

        return $template;
    }
}
