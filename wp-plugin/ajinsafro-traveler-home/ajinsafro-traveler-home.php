<?php
/**
 * Plugin Name: Ajinsafro Traveler Home
 * Plugin URI:  https://ajinsafro.com
 * Description: Surcharge la page d'accueil (front page) du thème Traveler avec une mise en page personnalisée : Hero, barre de recherche, offres dernière minute, destinations par région et bons coins.
 * Version:     1.0.0
 * Author:      Ajinsafro
 * Author URI:  https://ajinsafro.com
 * Text Domain: ajinsafro-traveler-home
 * Domain Path: /languages
 * License:     GPLv2 or later
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ──────────────────────────────────────────────
 * Constants
 * ────────────────────────────────────────────── */
define( 'AJTH_VERSION', '1.0.0' );
define( 'AJTH_FILE',    __FILE__ );
define( 'AJTH_DIR',     plugin_dir_path( __FILE__ ) );
define( 'AJTH_URL',     plugin_dir_url( __FILE__ ) );

/* ──────────────────────────────────────────────
 * Autoload includes
 * ────────────────────────────────────────────── */
require_once AJTH_DIR . 'includes/class-template-router.php';
require_once AJTH_DIR . 'includes/class-admin-settings.php';

/* ──────────────────────────────────────────────
 * Boot
 * ────────────────────────────────────────────── */
function ajth_init() {
    // Template routing (front-end)
    new AJTH_Template_Router();

    // Admin settings page
    if ( is_admin() ) {
        new AJTH_Admin_Settings();
    }
}
add_action( 'plugins_loaded', 'ajth_init' );

/* ──────────────────────────────────────────────
 * Enqueue front-end assets ONLY on the home page
 * ────────────────────────────────────────────── */
function ajth_enqueue_front_assets() {
    if ( ! is_front_page() && ! is_home() ) {
        return;
    }

    // Load AFTER the theme CSS so our rules win
    wp_enqueue_style(
        'ajth-home-css',
        AJTH_URL . 'assets/css/home.css',
        array(),
        AJTH_VERSION
    );

    wp_enqueue_script(
        'ajth-home-js',
        AJTH_URL . 'assets/js/home.js',
        array(),
        AJTH_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'ajth_enqueue_front_assets', 999 );

/* ──────────────────────────────────────────────
 * Helper: get plugin settings with defaults
 * ────────────────────────────────────────────── */
function ajth_get_settings() {
    $defaults = array(
        'hero' => array(
            'type' => 'image',
            'image_url' => '',
            'video_url' => '',
            'title' => 'Découvrez le Maroc',
            'subtitle' => 'Voyages, hébergements et activités au meilleur prix',
            'cta_text' => 'Voir les offres',
            'cta_url' => '',
            'overlay' => 0.35,
        ),
        'sections' => array(
            'search' => true,
            'last_minute' => true,
            'regions' => true,
            'good_spots' => true,
        ),
        'search' => array(
            'shortcode' => '[traveler_search]',
        ),
        'last_minute' => array(
            'title' => 'Offres de dernière minute',
            'count' => 6,
            'featured_only' => false,
        ),
        'regions' => array(),
        'good_spots' => array(
            array( 'title' => 'Restaurants', 'image_url' => '', 'link_url' => '#' ),
            array( 'title' => 'Loisirs', 'image_url' => '', 'link_url' => '#' ),
            array( 'title' => 'Que faire ?', 'image_url' => '', 'link_url' => '#' ),
            array( 'title' => 'Shopping', 'image_url' => '', 'link_url' => '#' ),
        ),
    );

    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( 'aj_home_settings', 'options' );
    }

    $raw = get_option( 'aj_home_settings', '{}' );
    $saved = is_string( $raw ) ? json_decode( $raw, true ) : array();

    if ( ! is_array( $saved ) || empty( $saved ) ) {
        $saved = ajth_legacy_settings_to_json();
    }

    $settings = array_replace_recursive( $defaults, $saved );

    $settings['hero']['overlay'] = max( 0, min( 1, floatval( $settings['hero']['overlay'] ) ) );
    $settings['last_minute']['count'] = max( 1, intval( $settings['last_minute']['count'] ) );
    $settings['sections']['search'] = ! empty( $settings['sections']['search'] );
    $settings['sections']['last_minute'] = ! empty( $settings['sections']['last_minute'] );
    $settings['sections']['regions'] = ! empty( $settings['sections']['regions'] );
    $settings['sections']['good_spots'] = ! empty( $settings['sections']['good_spots'] );

    return $settings;
}

function ajth_legacy_settings_to_json() {
    $legacy = get_option( 'ajth_home_settings', array() );

    if ( ! is_array( $legacy ) || empty( $legacy ) ) {
        return array();
    }

    $regions = array();
    if ( ! empty( $legacy['regions'] ) && is_array( $legacy['regions'] ) ) {
        foreach ( $legacy['regions'] as $region ) {
            $img = '';
            if ( ! empty( $region['image'] ) && is_numeric( $region['image'] ) ) {
                $img = wp_get_attachment_image_url( absint( $region['image'] ), 'large' );
            }
            $regions[] = array(
                'title' => $region['title'] ?? '',
                'image_url' => $img ?: '',
                'link_url' => $region['url'] ?? '#',
            );
        }
    }

    $spots = array();
    if ( ! empty( $legacy['good_spots'] ) && is_array( $legacy['good_spots'] ) ) {
        foreach ( $legacy['good_spots'] as $spot ) {
            $img = '';
            if ( ! empty( $spot['image'] ) && is_numeric( $spot['image'] ) ) {
                $img = wp_get_attachment_image_url( absint( $spot['image'] ), 'large' );
            }
            $spots[] = array(
                'title' => $spot['title'] ?? '',
                'image_url' => $img ?: '',
                'link_url' => $spot['url'] ?? '#',
            );
        }
    }

    $heroImage = '';
    if ( ! empty( $legacy['hero_image'] ) && is_numeric( $legacy['hero_image'] ) ) {
        $heroImage = wp_get_attachment_image_url( absint( $legacy['hero_image'] ), 'full' );
    }

    return array(
        'hero' => array(
            'type' => 'image',
            'image_url' => $heroImage ?: '',
            'video_url' => '',
            'title' => $legacy['hero_title'] ?? 'Découvrez le Maroc',
            'subtitle' => $legacy['hero_subtitle'] ?? '',
            'cta_text' => 'Voir les offres',
            'cta_url' => '',
            'overlay' => 0.35,
        ),
        'sections' => array(
            'search' => true,
            'last_minute' => true,
            'regions' => true,
            'good_spots' => true,
        ),
        'search' => array(
            'shortcode' => '[traveler_search]',
        ),
        'last_minute' => array(
            'title' => $legacy['last_minute_title'] ?? 'Offres de dernière minute',
            'count' => 6,
            'featured_only' => false,
        ),
        'regions' => $regions,
        'good_spots' => $spots,
    );
}

function ajth_debug_dump_home_settings_footer() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! isset( $_GET['ajdebug'] ) || '1' !== (string) $_GET['ajdebug'] ) {
        return;
    }

    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( 'aj_home_settings', 'options' );
    }

    echo '<div style="margin:24px auto;max-width:1200px;padding:12px;border:1px dashed #b91c1c;background:#fff;">';
    echo '<strong>AJ DEBUG — get_option(\'aj_home_settings\')</strong>';
    echo '<pre style="white-space:pre-wrap;word-break:break-word;max-height:380px;overflow:auto;">';
    var_dump( get_option( 'aj_home_settings' ) );
    echo '</pre>';
    echo '</div>';
}
add_action( 'wp_footer', 'ajth_debug_dump_home_settings_footer', 9999 );

/* ──────────────────────────────────────────────
 * Activation: set default options if not present
 * ────────────────────────────────────────────── */
function ajth_activate() {
    if ( false === get_option( 'ajth_home_settings' ) ) {
        update_option( 'ajth_home_settings', array() );
    }
}
register_activation_hook( __FILE__, 'ajth_activate' );
