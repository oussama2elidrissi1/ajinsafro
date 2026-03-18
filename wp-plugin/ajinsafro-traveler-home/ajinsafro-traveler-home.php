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
 * Public login entrypoint (/login)
 * ──────────────────────────────────────────────
 *
 * WordPress only serves as a public entrypoint.
 * Real authentication remains centralized in Laravel (booking subdomain).
 *
 * ajinsafro.net/login  ->  booking.ajinsafro.net/login
 */
function ajth_public_login_redirect() {
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    $path = '';
    if ( isset( $_SERVER['REQUEST_URI'] ) ) {
        $path = wp_parse_url( esc_url_raw( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
    }
    $path = is_string( $path ) ? rtrim( $path, '/' ) : '';

    if ( $path !== '/login' ) {
        return;
    }

    // Central login on Laravel booking domain.
    $dest = 'https://booking.ajinsafro.net/login';

    // Preserve optional ?next=... for analytics/UX (not required by Laravel).
    if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
        $dest .= '?' . ltrim( (string) $_SERVER['QUERY_STRING'], '?' );
    }

    wp_safe_redirect( $dest, 302 );
    exit;
}
add_action( 'template_redirect', 'ajth_public_login_redirect', 0 );

/* ──────────────────────────────────────────────
 * Enqueue front-end assets on home page, pages with [ajth_homepage],
 * or on all pages when header is enabled and "site-wide" is on.
 * ────────────────────────────────────────────── */
function ajth_enqueue_front_assets() {
    $load = is_front_page() || is_home() || is_page( 'voyages' ) || is_post_type_archive( 'st_tours' );

    if ( ! $load && is_singular() ) {
        global $post;
        if ( $post && has_shortcode( $post->post_content, 'ajth_homepage' ) ) {
            $load = true;
        }
    }

    if ( ! $load ) {
        $h = ajth_get_header_settings();
        if ( ( ! empty( $h['enabled'] ) && ! empty( $h['show_header_sitewide'] ) ) || ! empty( $h['show_footer_sitewide'] ) ) {
            $load = true;
        }
    }

    if ( ! $load ) {
        return;
    }

    // FontAwesome for icons
    wp_enqueue_style(
        'ajth-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );

    // Google Fonts - Poppins + Noto Sans Arabic (for RTL promo cards)
    wp_enqueue_style(
        'ajth-google-fonts',
        'https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'ajth-home-css',
        AJTH_URL . 'assets/css/home.css',
        array( 'ajth-fontawesome' ),
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
    $on_home = is_front_page() || is_home();
    $on_voyages = is_page( 'voyages' ) || is_post_type_archive( 'st_tours' );

    $render_header = ! empty( $h['enabled'] ) && ( $on_home || $on_voyages || ! empty( $h['show_header_sitewide'] ) );
    $render_footer = ! empty( $h['show_footer_sitewide'] );

    if ( ! $render_header && ! $render_footer ) {
        return;
    }

    if ( $render_header ) {
        $css = 'body.aj-custom-header #header,body.aj-custom-header .site-header,body.aj-custom-header .topbar,body.aj-custom-header .header-main,body.aj-custom-header>header:not(.aj-header),body.aj-custom-header #masthead{display:none!important}.aj-header{width:100%;z-index:1000;position:relative;font-family:\'Poppins\',\'Segoe UI\',Roboto,sans-serif}.aj-topbar{background:#0e3a5a;color:rgba(255,255,255,.9);font-size:11px;line-height:1}.aj-topbar__inner{display:flex;align-items:center;justify-content:space-between;padding:8px 0;gap:16px}.aj-topbar__left,.aj-topbar__right{display:flex;align-items:center;gap:16px}.aj-topbar__socials{display:flex;align-items:center;gap:12px;font-size:14px}.aj-topbar__social-link{color:rgba(255,255,255,.9);transition:color .2s}.aj-topbar__contact{display:flex;align-items:center;gap:16px;padding-left:16px;border-left:1px solid rgba(255,255,255,.2)}.aj-topbar__item{display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,.9)}.aj-topbar__selector{display:flex;align-items:center;gap:6px;padding:6px 8px;border-radius:4px;cursor:pointer}.aj-topbar__flag{width:16px;height:12px;object-fit:cover;border-radius:1px}.aj-topbar__auth{display:flex;align-items:center;gap:8px;padding-left:12px;margin-left:8px;border-left:1px solid rgba(255,255,255,.2)}.aj-topbar__auth-link{padding:6px 12px;color:rgba(255,255,255,.9);font-weight:500;border-radius:4px}.aj-topbar__auth-link--signup{background:#0083c4;color:#fff;border-radius:20px;padding:6px 16px}.aj-navbar{background:#fff;box-shadow:0 2px 12px rgba(0,0,0,.06);position:sticky;top:0;z-index:999;border-bottom:1px solid rgba(0,0,0,.05)}.aj-navbar__inner{display:flex;align-items:center;justify-content:space-between;gap:24px;min-height:80px;padding-left:16px;padding-right:16px}.aj-container{max-width:1280px;margin:0 auto;padding:0 20px;width:100%}.aj-navbar__burger{display:none}.aj-drawer{display:flex;flex:1;min-width:0}.aj-drawer__header,.aj-drawer__auth,.aj-drawer__lowcost{display:none}.aj-navbar__menu{flex:1 1 auto;min-width:0;display:flex;justify-content:center}.aj-nav-list{list-style:none;margin:0;padding:0;display:flex;align-items:center;gap:4px}.aj-nav-list>li>a{display:flex;align-items:center;gap:6px;padding:8px 12px;font-size:13px;font-weight:600;color:#374151;text-decoration:none;text-transform:uppercase;letter-spacing:.3px;border-radius:8px;transition:color .2s,background .2s}.aj-nav-list>li>a:hover{color:#0083c4;background:rgba(0,131,196,.06)}.aj-navbar__brand{font-size:1.25rem;font-weight:800;color:#0083c4}.aj-navbar__logo-img{max-height:40px;width:auto;height:auto;object-fit:contain;display:block}.aj-lowcost-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:linear-gradient(135deg,#f37a1f,#ef4444);color:#fff;font-size:13px;font-weight:700;text-transform:uppercase;border-radius:20px;box-shadow:0 4px 12px rgba(243,122,31,.3)}';
        echo '<style id="ajth-critical-header">' . $css . '</style>' . "\n";
    }

    $footer_selectors = '#footer,#footer-outer,.site-footer,.footer-wrapper,.footer-widget-area,#colophon,.footer,.st-footer,.footer-top,.footer-bottom,#main-footer,.footer-area,.st-footer-wrap,.footer-wrap,.content-footer,.footer-outer';
    if ( $render_footer ) {
        $footer_css = 'body.aj-custom-footer ' . implode( ',body.aj-custom-footer ', explode( ',', $footer_selectors ) ) . ',body.aj-custom-footer footer:not(.aj-footer-v2):not(.aj-footer-sitewide footer){display:none!important}';
        echo '<style id="ajth-critical-footer">' . $footer_css . '</style>' . "\n";
    }
}
add_action( 'wp_head', 'ajth_critical_header_css', 1 );

/* Preload main stylesheet and critical fonts so header renders correctly on first paint */
function ajth_preload_styles() {
    $load = is_front_page() || is_home() || is_page( 'voyages' ) || is_post_type_archive( 'st_tours' );
    if ( ! $load && is_singular() ) {
        global $post;
        if ( $post && has_shortcode( $post->post_content, 'ajth_homepage' ) ) {
            $load = true;
        }
    }
    if ( ! $load ) {
        $h = ajth_get_header_settings();
        if ( ( ! empty( $h['enabled'] ) && ! empty( $h['show_header_sitewide'] ) ) || ! empty( $h['show_footer_sitewide'] ) ) {
            $load = true;
        }
    }
    if ( ! $load ) {
        return;
    }
    echo '<link rel="preload" href="' . esc_url( AJTH_URL . 'assets/css/home.css' ) . '?ver=' . esc_attr( AJTH_VERSION ) . '" as="style">' . "\n";
    echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" as="style">' . "\n";
    echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">' . "\n";
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
        'phone'            => '+212 5 39 32 38 74',
        'email'            => 'contact@ajinsafro.ma',
        'socials'          => array(
            'facebook'  => '#',
            'twitter'   => '#',
            'instagram' => '#',
            'youtube'   => '#',
            'linkedin'  => '#',
        ),
        'navbar_enabled'   => true,
        'logo_url'         => '',
        'show_auth_links'  => true,
        'login_url'        => 'https://ajinsafro.net/login',
        'signup_url'       => 'https://ajinsafro.net/register',
        'menu_source'           => 'wp_menu',
        'wp_menu_location'      => 'primary',
        'show_header_sitewide'  => false,
        'show_footer_sitewide'  => true,
        'links'                 => array(),
        'lowcost_enabled'       => true,
        'lowcost_text'          => 'Formule low cost',
        'lowcost_url'           => '#',
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
    $on_home = is_front_page() || is_home();
    $on_voyages = is_page( 'voyages' ) || is_post_type_archive( 'st_tours' );

    if ( ! empty( $h['enabled'] ) && ( $on_home || $on_voyages || ! empty( $h['show_header_sitewide'] ) ) ) {
        $classes[] = 'aj-custom-header';
    }
    if ( ! empty( $h['show_footer_sitewide'] ) ) {
        $classes[] = 'aj-custom-footer';
    }
    if ( $on_home ) {
        $classes[] = 'aj-has-bg-pattern';
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
    if ( is_front_page() || is_home() || is_page( 'voyages' ) || is_post_type_archive( 'st_tours' ) ) {
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
 * Output custom footer on pages when site-wide footer is enabled.
 * ────────────────────────────────────────────── */
function ajth_render_footer_sitewide() {
    if ( is_singular() ) {
        global $post;
        if ( $post && has_shortcode( $post->post_content, 'ajth_homepage' ) ) {
            return;
        }
    }
    $h = ajth_get_header_settings();
    if ( empty( $h['show_footer_sitewide'] ) ) {
        return;
    }
    $settings = ajth_get_settings();
    echo '<div class="aj-footer-sitewide">';
    include AJTH_DIR . 'parts/newsletter.php';
    echo '</div>';
}
add_action( 'wp_footer', 'ajth_render_footer_sitewide', 1 );

/* ──────────────────────────────────────────────
 * Helper: get plugin settings with defaults
 * ────────────────────────────────────────────── */
function ajth_get_settings() {
    $defaults = array(
        'hero' => array(
            'type' => 'image',
            'image_url' => '',
            'video_url' => '',
            'title' => 'Partir en vacances au meilleur prix !',
            'subtitle' => '',
            'cta_text' => '',
            'cta_url' => '',
            'overlay' => 0.4,
        ),
        'sections' => array(
            'search' => true,
            'last_minute' => true,
            'accommodations' => true,
            'regions' => true,
            'good_spots' => true,
            'promotions' => true,
            'whatsapp_banner' => true,
            'cruises' => true,
            'newsletter' => true,
        ),
        'search' => array(
            'shortcode' => '[traveler_search]',
        ),
        'last_minute' => array(
            'title' => 'Cap sur les tendances du moment',
            'count' => 4,
            'featured_only' => false,
        ),
        'accommodations' => array(
            'title' => 'Découvrez des séjours uniques',
            'count' => 4,
        ),
        'regions' => array(),
        'good_spots' => array(
            array( 'title' => 'Restaurants', 'subtitle' => 'Où manger ?', 'icon' => 'fas fa-utensils', 'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80', 'link_url' => '#' ),
            array( 'title' => 'Loisirs', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-icons', 'image_url' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?auto=format&fit=crop&w=800&q=80', 'link_url' => '#' ),
            array( 'title' => 'Que faire ?', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-map-marked-alt', 'image_url' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80', 'link_url' => '#' ),
            array( 'title' => 'Shopping', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-shopping-bag', 'image_url' => 'https://images.unsplash.com/photo-1481437156560-3205f6a55735?auto=format&fit=crop&w=800&q=80', 'link_url' => '#' ),
        ),
        'good_spots_title' => 'Les bons coins sur votre destination',
        'promotions' => array(
            'title' => 'Destinations de ce mois',
            'items' => array(),
        ),
        'whatsapp_banner' => array(
            'enabled'   => true,
            'title'    => 'JOIN OUR WHATSAPP CHANNEL FOR THE LATEST TRAVEL UPDATES',
            'subtitle' => 'Stay informed with satguru travel',
            'features' => array( 'Exclusive travel packages', 'Latest news and updates', 'Special offers and promotions' ),
            'button_text' => 'JOIN NOW',
            'button_url'  => '#',
            'qr_code_url' => '',
        ),
        'section_order' => array( 'last_minute', 'accommodations', 'regions', 'good_spots', 'promotions', 'whatsapp_banner', 'cruises', 'newsletter' ),
        'footer' => array(
            'col1_heading' => 'En savoir plus',
            'col2_heading' => 'Société',
            'legal_text' => "Licence N° 489117 | RC: 18989\nPatente: 50411316 | I.C.E: 001585417000035\nAjinSafro Recreation SARL AU",
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
    $settings['sections']['accommodations'] = ! empty( $settings['sections']['accommodations'] );
    $settings['sections']['regions'] = ! empty( $settings['sections']['regions'] );
    $settings['sections']['good_spots'] = ! empty( $settings['sections']['good_spots'] );
    $settings['sections']['promotions'] = ! empty( $settings['sections']['promotions'] );
    $settings['sections']['whatsapp_banner'] = ! empty( $settings['sections']['whatsapp_banner'] );
    $settings['sections']['cruises'] = ! empty( $settings['sections']['cruises'] );
    $settings['sections']['newsletter'] = ! empty( $settings['sections']['newsletter'] );

    return $settings;
}

/* ──────────────────────────────────────────────
 * Destinations par région (Laravel admin, key destinations_by_region)
 * Mirrored to wp_options aj_destinations_by_region — used for 2×4 grid on home.
 * ────────────────────────────────────────────── */
function ajth_get_destinations_by_region() {
    $defaults = array(
        'enabled' => true,
        'title'   => 'Nos destinations',
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

/* ──────────────────────────────────────────────
 * Voyages page URL helper
 * ────────────────────────────────────────────── */
function ajth_get_voyages_page_url() {
    $page = get_page_by_path( 'voyages' );
    if ( $page instanceof WP_Post ) {
        $url = get_permalink( $page );
        if ( $url ) {
            return $url;
        }
    }

    $archive = get_post_type_archive_link( 'st_tours' );
    if ( $archive ) {
        return $archive;
    }

    return home_url( '/?post_type=st_tours' );
}

/* ──────────────────────────────────────────────
 * Vols page URL helper
 * ────────────────────────────────────────────── */
function ajth_get_vols_page_url() {
    $page = get_page_by_path( 'vols' );
    if ( $page instanceof WP_Post ) {
        $url = get_permalink( $page );
        if ( $url ) {
            return $url;
        }
    }
    return home_url( '/vols/' );
}

/* ──────────────────────────────────────────────
 * Ensure "Voyages" page exists (slug: voyages)
 * ────────────────────────────────────────────── */
function ajth_ensure_voyages_page() {
    if ( get_page_by_path( 'voyages' ) ) {
        return;
    }

    wp_insert_post( array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => 'Voyages',
        'post_name'    => 'voyages',
        'post_content' => '',
    ) );
}

/* ──────────────────────────────────────────────
 * Ensure "Vols" page exists (slug: vols)
 * ────────────────────────────────────────────── */
function ajth_ensure_vols_page() {
    if ( get_page_by_path( 'vols' ) ) {
        return;
    }

    wp_insert_post( array(
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_title'   => 'Vols',
        'post_name'    => 'vols',
        'post_content' => '',
    ) );
}

/* ──────────────────────────────────────────────
 * Add Voyages item to WP menu if missing
 * ────────────────────────────────────────────── */
function ajth_add_voyages_menu_item( $items, $args ) {
    if ( empty( $args->theme_location ) || $args->theme_location !== 'primary' ) {
        return $items;
    }

    if ( stripos( wp_strip_all_tags( $items ), 'voyage' ) !== false ) {
        return $items;
    }

    $url = ajth_get_voyages_page_url();
    $active = ( is_page( 'voyages' ) || is_post_type_archive( 'st_tours' ) ) ? ' current-menu-item current_page_item aj-active' : '';

    $items .= '<li class="menu-item menu-item-type-custom menu-item-object-custom' . esc_attr( $active ) . '">'
        . '<a href="' . esc_url( $url ) . '"><i class="fas fa-suitcase-rolling"></i> <span>' . esc_html__( 'Voyages', 'ajinsafro-traveler-home' ) . '</span></a>'
        . '</li>';

    return $items;
}
add_filter( 'wp_nav_menu_items', 'ajth_add_voyages_menu_item', 20, 2 );

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

/* ──────────────────────────────────────────────
 * Custom Walker for WordPress menus with icon support
 *
 * Icons are resolved in this priority order:
 * 1. FontAwesome classes from the menu item's CSS Classes field
 *    (WordPress splits them: e.g. "fas" and "fa-hotel" as separate entries)
 * 2. Auto-mapping based on the menu item's title/label
 *
 * Menu items whose title contains "low cost" are skipped because
 * the plugin renders the Formule Low Cost button separately.
 * ────────────────────────────────────────────── */
class AJTH_Nav_Walker extends Walker_Nav_Menu {

    private static $title_icon_map = array(
        'packages'     => 'fas fa-suitcase-rolling',
        'package'      => 'fas fa-suitcase-rolling',
        'voyages'      => 'fas fa-suitcase-rolling',
        'voyage'       => 'fas fa-suitcase-rolling',
        'hébergement'  => 'fas fa-hotel',
        'hebergement'  => 'fas fa-hotel',
        'hôtel'        => 'fas fa-hotel',
        'hotel'        => 'fas fa-hotel',
        'activités'    => 'fas fa-camera',
        'activites'    => 'fas fa-camera',
        'activité'     => 'fas fa-camera',
        'transfert'    => 'fas fa-car-side',
        'transferts'   => 'fas fa-car-side',
        'hajj & omra'  => 'fas fa-kaaba',
        'hajj'         => 'fas fa-kaaba',
        'omra'         => 'fas fa-kaaba',
        'votre guide'  => 'fas fa-map-signs',
        'guide'        => 'fas fa-map-signs',
        'accueil'      => 'fas fa-home',
        'contact'      => 'fas fa-envelope',
        'blog'         => 'fas fa-blog',
    );

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        $indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

        $title_raw = apply_filters( 'the_title', $item->title, $item->ID );

        // Skip items whose title contains "low cost" (handled by plugin button)
        if ( stripos( $title_raw, 'low cost' ) !== false ) {
            return;
        }

        $classes   = empty( $item->classes ) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        // Extract FontAwesome icon from classes
        // WP stores each CSS class as a separate array element,
        // so "fas" and "fa-hotel" come as two entries.
        $fa_prefix   = '';
        $fa_icon     = '';
        $filtered_classes = array();
        foreach ( $classes as $class ) {
            $class = trim( $class );
            if ( $class === '' ) {
                continue;
            }
            if ( preg_match( '/^(fas|fa|far|fab|fal|fad)$/', $class ) ) {
                $fa_prefix = $class;
            } elseif ( preg_match( '/^fa-/', $class ) ) {
                $fa_icon = $class;
            } elseif ( preg_match( '/^(fas?|far|fab|fal|fad)\s+fa-/', $class ) ) {
                // Full class string in one entry (e.g. "fas fa-hotel")
                $fa_prefix = '';
                $fa_icon   = '';
                $icon_class_full = $class;
                $filtered_classes[] = $class; // will be removed below
            } else {
                $filtered_classes[] = $class;
            }
        }

        // Build icon class
        if ( ! empty( $icon_class_full ) ) {
            $icon_class = trim( $icon_class_full );
        } elseif ( $fa_prefix && $fa_icon ) {
            $icon_class = $fa_prefix . ' ' . $fa_icon;
        } elseif ( $fa_icon ) {
            $icon_class = 'fas ' . $fa_icon;
        } else {
            $icon_class = '';
        }

        // Remove the full FA class from filtered_classes if it was added
        if ( ! empty( $icon_class_full ) ) {
            $filtered_classes = array_filter( $filtered_classes, function( $c ) use ( $icon_class_full ) {
                return $c !== $icon_class_full;
            });
        }

        // Auto-map icon by title if none found from CSS classes
        if ( empty( $icon_class ) && $depth === 0 ) {
            $title_lower = mb_strtolower( trim( $title_raw ), 'UTF-8' );
            if ( isset( self::$title_icon_map[ $title_lower ] ) ) {
                $icon_class = self::$title_icon_map[ $title_lower ];
            }
        }

        // Check for has-children
        if ( in_array( 'menu-item-has-children', $filtered_classes, true ) ) {
            $filtered_classes[] = 'aj-has-sub';
        }

        $args = apply_filters( 'nav_menu_item_args', $args, $item, $depth );

        $class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $filtered_classes ), $item, $args, $depth ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $id_attr = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
        $id_attr = $id_attr ? ' id="' . esc_attr( $id_attr ) . '"' : '';

        $output .= $indent . '<li' . $id_attr . $class_names . '>';

        $atts           = array();
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target ) ? $item->target : '';
        if ( '_blank' === $item->target && empty( $item->xfn ) ) {
            $atts['rel'] = 'noopener';
        } else {
            $atts['rel'] = $item->xfn;
        }
        $atts['href']         = ! empty( $item->url ) ? $item->url : '';
        $atts['aria-current'] = $item->current ? 'page' : '';

        $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
                $value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $title = apply_filters( 'nav_menu_item_title', $title_raw, $item, $args, $depth );

        $item_output  = $args->before ?? '';
        $item_output .= '<a' . $attributes . '>';

        if ( $icon_class && $depth === 0 ) {
            $item_output .= '<i class="' . esc_attr( $icon_class ) . '"></i> ';
        }

        $item_output .= ( $args->link_before ?? '' ) . '<span>' . $title . '</span>' . ( $args->link_after ?? '' );

        if ( in_array( 'menu-item-has-children', $classes, true ) && $depth === 0 ) {
            $item_output .= ' <i class="fas fa-chevron-down aj-caret"></i>';
        }

        $item_output .= '</a>';
        $item_output .= $args->after ?? '';

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
    }
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

    ajth_ensure_voyages_page();
    ajth_ensure_vols_page();
}
register_activation_hook( __FILE__, 'ajth_activate' );
