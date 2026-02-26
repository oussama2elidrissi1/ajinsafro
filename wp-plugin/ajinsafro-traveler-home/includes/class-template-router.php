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
