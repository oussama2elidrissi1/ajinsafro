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
 * Enqueue front-end assets on home page, pages with [ajth_homepage],
 * or on all pages when header is enabled and "site-wide" is on.
 * ────────────────────────────────────────────── */
function ajth_enqueue_front_assets() {
    $load = is_front_page() || is_home();

    if ( ! $load && is_singular() ) {
        global $post;
        if ( $post && has_shortcode( $post->post_content, 'ajth_homepage' ) ) {
            $load = true;
        }
    }

    if ( ! $load ) {
        $h = ajth_get_header_settings();
        if ( ! empty( $h['enabled'] ) && ! empty( $h['show_header_sitewide'] ) ) {
            $load = true;
        }
    }

    if ( ! $load ) {
        return;
    }

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
add_action( 'wp_enqueue_scripts', 'ajth_enqueue_front_assets', 5 );

/* ──────────────────────────────────────────────
 * Critical CSS inline in head to avoid FOUC
 * (header styled immediately, full CSS loads after)
 * ────────────────────────────────────────────── */
function ajth_critical_header_css() {
    $h = ajth_get_header_settings();
    if ( empty( $h['enabled'] ) ) {
        return;
    }
    $on_home = is_front_page() || is_home();
    if ( ! $on_home && empty( $h['show_header_sitewide'] ) ) {
        return;
    }
    $css = 'body.aj-custom-header #header,body.aj-custom-header .site-header,body.aj-custom-header .topbar,body.aj-custom-header .header-main,body.aj-custom-header>header:not(.aj-header),body.aj-custom-header #masthead{display:none!important}.aj-header{width:100%;z-index:1000;position:relative}.aj-topbar{background:#0B2C5D;color:rgba(255,255,255,.85);font-size:.78rem;line-height:1}.aj-topbar__inner{display:flex;align-items:center;justify-content:space-between;padding:8px 0}.aj-topbar__left,.aj-topbar__right{display:flex;align-items:center;gap:12px}.aj-topbar__item{display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,.85)}.aj-topbar__social{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:rgba(255,255,255,.12);color:#fff}.aj-navbar{background:#fff;box-shadow:0 2px 12px rgba(0,0,0,.06);position:relative;z-index:999}.aj-navbar__inner{display:flex;align-items:center;justify-content:space-between;gap:24px;min-height:60px}.aj-container{max-width:1100px;margin:0 auto;padding:0 20px;width:100%}.aj-nav-list{list-style:none;margin:0;padding:0;display:flex;align-items:center;gap:0}.aj-nav-list>li>a{display:flex;align-items:center;gap:4px;padding:18px 16px;font-size:.88rem;font-weight:600;color:#222;text-decoration:none}.aj-navbar__brand{font-size:1.25rem;font-weight:800;color:#1a73a7}.aj-navbar__auth{display:flex!important;align-items:center;gap:10px;flex-shrink:0}.aj-auth-link{font-size:.85rem;font-weight:600;color:#222;padding:6px 14px;border-radius:6px;text-decoration:none;white-space:nowrap}.aj-auth-link--signup{background:#1a73a7;color:#fff}';
    echo '<style id="ajth-critical-header">' . $css . '</style>' . "\n";
}
add_action( 'wp_head', 'ajth_critical_header_css', 1 );

/* Preload main stylesheet so it loads as early as possible */
function ajth_preload_styles() {
    $load = is_front_page() || is_home();
    if ( ! $load && is_singular() ) {
        global $post;
        if ( $post && has_shortcode( $post->post_content, 'ajth_homepage' ) ) {
            $load = true;
        }
    }
    if ( ! $load ) {
        $h = ajth_get_header_settings();
        if ( ! empty( $h['enabled'] ) && ! empty( $h['show_header_sitewide'] ) ) {
            $load = true;
        }
    }
    if ( ! $load ) {
        return;
    }
    echo '<link rel="preload" href="' . esc_url( AJTH_URL . 'assets/css/home.css' ) . '?ver=' . esc_attr( AJTH_VERSION ) . '" as="style">' . "\n";
}
add_action( 'wp_head', 'ajth_preload_styles', 0 );

/* ──────────────────────────────────────────────
 * Shortcode: [ajth_homepage]
 * Renders the full homepage inside any page.
 * ────────────────────────────────────────────── */
function ajth_homepage_shortcode( $atts ) {
    ob_start();
    $settings = ajth_get_settings();
    include AJTH_DIR . 'templates/homepage.php';
    return ob_get_clean();
}
add_shortcode( 'ajth_homepage', 'ajth_homepage_shortcode' );

/* ──────────────────────────────────────────────
 * Header settings managed from Laravel admin
 * /admin/settings/home-page  (tab Header)
 *
 * Reads from wp_options key 'aj_header_settings'
 * (mirrored from Laravel settings table, key='wp_header').
 *
 * Cache is invalidated automatically when Laravel
 * updates aj_header_settings_ts in wp_options.
 * Admin can also force-flush with ?ajth_flush=1.
 * ────────────────────────────────────────────── */
function ajth_get_header_settings() {
    $cache_key    = 'ajth_header_settings_v2';
    $cache_ts_key = 'ajth_header_settings_ts';

    if ( isset( $_GET['ajth_flush'] ) && $_GET['ajth_flush'] === '1' && current_user_can( 'manage_options' ) ) {
        delete_transient( $cache_key );
        delete_transient( $cache_ts_key );
    }

    $db_ts     = get_option( 'aj_header_settings_ts', '0' );
    $cached_ts = get_transient( $cache_ts_key );
    $cached    = get_transient( $cache_key );

    if ( is_array( $cached ) && $cached_ts === $db_ts ) {
        return $cached;
    }

    $defaults = array(
        'enabled'          => true,
        'topbar_enabled'   => true,
        'phone'            => '(000) 999 - 656 - 888',
        'email'            => 'contact@ajinsafro.ma',
        'socials'          => array(
            'facebook'  => '',
            'twitter'   => '',
            'instagram' => '',
            'youtube'   => '',
        ),
        'navbar_enabled'   => true,
        'logo_url'         => '',
        'show_auth_links'  => true,
        'login_url'        => '/login',
        'signup_url'       => '/register',
        'menu_source'           => 'wp_menu',
        'wp_menu_location'      => 'primary',
        'show_header_sitewide'  => false,
        'links'                 => array(),
    );

    $raw = get_option( 'aj_header_settings', '' );

    if ( is_string( $raw ) && $raw !== '' ) {
        $decoded = json_decode( $raw, true );
        if ( is_array( $decoded ) ) {
            $settings = array_replace_recursive( $defaults, $decoded );
            set_transient( $cache_key, $settings, 10 * MINUTE_IN_SECONDS );
            set_transient( $cache_ts_key, $db_ts, 10 * MINUTE_IN_SECONDS );
            return $settings;
        }
    }

    set_transient( $cache_key, $defaults, 2 * MINUTE_IN_SECONDS );
    set_transient( $cache_ts_key, $db_ts, 2 * MINUTE_IN_SECONDS );
    return $defaults;
}

/* ──────────────────────────────────────────────
 * Add body class when custom header is active
 * (home only, or all pages if show_header_sitewide)
 * ────────────────────────────────────────────── */
function ajth_body_class_custom_header( $classes ) {
    $h = ajth_get_header_settings();
    if ( empty( $h['enabled'] ) ) {
        return $classes;
    }
    $on_home = is_front_page() || is_home();
    if ( $on_home || ! empty( $h['show_header_sitewide'] ) ) {
        $classes[] = 'aj-custom-header';
    }
    return $classes;
}
add_filter( 'body_class', 'ajth_body_class_custom_header' );

/* ──────────────────────────────────────────────
 * Output custom header on all pages when site-wide is on.
 * Uses get_header (runs before theme header.php) so it works
 * even if the theme does not call wp_body_open().
 * On home the template already includes the header.
 * ────────────────────────────────────────────── */
function ajth_render_header_sitewide() {
    if ( is_front_page() || is_home() ) {
        return;
    }
    $h = ajth_get_header_settings();
    if ( empty( $h['enabled'] ) || empty( $h['show_header_sitewide'] ) ) {
        return;
    }
    include AJTH_DIR . 'parts/header.php';
}
add_action( 'get_header', 'ajth_render_header_sitewide', 5 );

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

/* ──────────────────────────────────────────────
 * Destinations par région (Laravel admin, key destinations_by_region)
 * Mirrored to wp_options aj_destinations_by_region — used for 2×4 grid on home.
 * ────────────────────────────────────────────── */
function ajth_get_destinations_by_region() {
    $defaults = array(
        'enabled' => true,
        'title'   => 'Destinations par région',
        'items'   => array(),
    );

    $raw = get_option( 'aj_destinations_by_region', '' );
    if ( ! is_string( $raw ) || $raw === '' ) {
        return $defaults;
    }

    $decoded = json_decode( $raw, true );
    if ( ! is_array( $decoded ) ) {
        return $defaults;
    }

    $items = isset( $decoded['items'] ) && is_array( $decoded['items'] ) ? $decoded['items'] : array();
    return array(
        'enabled' => ! empty( $decoded['enabled'] ),
        'title'   => isset( $decoded['title'] ) ? (string) $decoded['title'] : $defaults['title'],
        'items'   => $items,
    );
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
