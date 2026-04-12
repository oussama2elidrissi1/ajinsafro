<?php

/**
 * Plugin Name: Ajinsafro Traveler Home
 * Plugin URI:  https://ajinsafro.com
 * Description: Surcharge la page d'accueil (front page) du thème Traveler avec une mise en page personnalisée : Hero, barre de recherche, offres dernière minute, destinations par région et bons coins.
 * Version:     1.0.9
 * Author:      Ajinsafro
 * Author URI:  https://ajinsafro.com
 * Text Domain: ajinsafro-traveler-home
 * Domain Path: /languages
 * License:     GPLv2 or later
 * Requires PHP: 7.4
 */
if (! defined('ABSPATH')) {
    exit;
}

/* ──────────────────────────────────────────────
 * Constants
 * ────────────────────────────────────────────── */
define('AJTH_VERSION', '1.0.9');
define('AJTH_FILE', __FILE__);
define('AJTH_DIR', plugin_dir_path(__FILE__));
define('AJTH_URL', plugin_dir_url(__FILE__));

if (! defined('AJINSAFRO_HOME_DIR')) {
    define('AJINSAFRO_HOME_DIR', AJTH_DIR);
}
if (! defined('AJINSAFRO_HOME_URL')) {
    define('AJINSAFRO_HOME_URL', AJTH_URL);
}

/* ──────────────────────────────────────────────
 * Autoload includes
 * ────────────────────────────────────────────── */
require_once AJTH_DIR.'includes/class-template-router.php';
require_once AJTH_DIR.'includes/class-catalog-cache-invalidate.php';
require_once AJTH_DIR.'includes/class-admin-settings.php';
require_once AJTH_DIR.'includes/tour-category-defaults.php';
require_once AJTH_DIR.'includes/voyages-routing.php';

register_activation_hook(AJTH_FILE, 'ajth_activate_default_tour_categories');

/* ──────────────────────────────────────────────
 * Boot
 * ────────────────────────────────────────────── */
function ajth_init()
{
    // Template routing (front-end)
    new AJTH_Template_Router;

    // Admin settings page
    if (is_admin()) {
        new AJTH_Admin_Settings;
    }
}
add_action('plugins_loaded', 'ajth_init');

/* ──────────────────────────────────────────────
 * Enqueue front-end assets on home page, pages with [ajth_homepage],
 * or on all pages when header is enabled and "site-wide" is on.
 * ────────────────────────────────────────────── */
function ajth_enqueue_front_assets()
{
    $load = is_front_page() || is_home() || ajth_is_catalog_context();
    $load_home_sections = is_front_page() || is_home();

    if (! $load && is_singular()) {
        global $post;
        if ($post && has_shortcode($post->post_content, 'ajth_homepage')) {
            $load = true;
            $load_home_sections = true;
        }
    }

    if (! $load) {
        $h = ajth_get_header_settings();
        if ((! empty($h['enabled']) && ! empty($h['show_header_sitewide'])) || ! empty($h['show_footer_sitewide'])) {
            $load = true;
        }
    }

    if (! $load) {
        return;
    }

    // FontAwesome for icons
    wp_enqueue_style(
        'ajth-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        [],
        '6.4.0'
    );

    // Google Fonts - Poppins + Noto Sans Arabic (for RTL promo cards)
    wp_enqueue_style(
        'ajth-google-fonts',
        'https://fonts.googleapis.com/css2?family=Cairo:wght@700;900&family=Noto+Sans+Arabic:wght@400;600;700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'ajth-home-css',
        AJTH_URL.'assets/css/home.css',
        ['ajth-fontawesome'],
        AJTH_VERSION
    );

    wp_enqueue_script(
        'ajth-home-js',
        AJTH_URL.'assets/js/home.js',
        [],
        AJTH_VERSION,
        true
    );

    if ($load_home_sections) {
        wp_enqueue_style(
            'ajth-home-reference-accordion-css',
            AJTH_URL.'assets/css/home-reference-accordion.css',
            ['ajth-home-css'],
            AJTH_VERSION
        );

        wp_enqueue_script(
            'ajth-home-reference-accordion-js',
            AJTH_URL.'assets/js/home-reference-accordion.js',
            [],
            AJTH_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'ajth_enqueue_front_assets', 5);

/* ──────────────────────────────────────────────
 * Critical CSS inline in head to avoid FOUC
 * (header styled immediately, full CSS loads after)
 * ────────────────────────────────────────────── */
function ajth_critical_header_css()
{
    $h = ajth_get_header_settings();
    $on_home = is_front_page() || is_home();
    $on_voyages = ajth_is_catalog_context();

    $render_header = ! empty($h['enabled']) && ($on_home || $on_voyages || ! empty($h['show_header_sitewide']));
    $render_footer = ! empty($h['show_footer_sitewide']);

    if (! $render_header && ! $render_footer) {
        return;
    }

    if ($render_header) {
        $css = 'body.aj-custom-header #header,body.aj-custom-header .site-header,body.aj-custom-header .topbar,body.aj-custom-header .header-main,body.aj-custom-header>header:not(.aj-header),body.aj-custom-header #masthead{display:none!important}.aj-header{width:100%;z-index:1000;position:relative;font-family:\'Poppins\',\'Segoe UI\',Roboto,sans-serif}.aj-topbar{background:#0e3a5a;color:rgba(255,255,255,.9);font-size:11px;line-height:1}.aj-topbar__inner{display:flex;align-items:center;justify-content:space-between;padding:8px 0;gap:16px}.aj-topbar__left{display:flex;align-items:center;gap:16px}.aj-topbar__right{display:flex;align-items:center;gap:8px;margin-left:auto;justify-content:flex-end;flex-wrap:wrap}.aj-topbar__socials{display:flex;align-items:center;gap:12px;font-size:14px}.aj-topbar__social-link{color:rgba(255,255,255,.9);transition:color .2s}.aj-topbar__contact{display:flex;align-items:center;gap:16px;padding-left:16px;border-left:1px solid rgba(255,255,255,.2)}.aj-topbar__item{display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,.9)}.aj-topbar__auth{display:flex;align-items:center;gap:8px;padding-left:12px;margin-left:8px;border-left:1px solid rgba(255,255,255,.2)}.aj-topbar__auth-link{padding:6px 12px;color:rgba(255,255,255,.9);font-weight:500;border-radius:4px}.aj-topbar__auth-link--signup{background:#0083c4;color:#fff;border-radius:20px;padding:6px 16px}.aj-navbar{background:#fff;box-shadow:0 2px 12px rgba(0,0,0,.06);position:sticky;top:0;z-index:999;border-bottom:1px solid rgba(0,0,0,.05)}.aj-navbar__inner{display:flex;align-items:center;justify-content:space-between;gap:24px;min-height:80px;padding-left:16px;padding-right:16px}.aj-container{max-width:1280px;margin:0 auto;padding:0 20px;width:100%}.aj-navbar__burger{display:none}.aj-drawer{display:flex;flex:1;min-width:0}.aj-drawer__header,.aj-drawer__auth,.aj-drawer__lowcost{display:none}.aj-navbar__menu{flex:1 1 auto;min-width:0;display:flex;justify-content:center}.aj-nav-list{list-style:none;margin:0;padding:0;display:flex;align-items:center;gap:4px}.aj-nav-list>li>a{display:flex;align-items:center;gap:6px;padding:8px 12px;font-size:13px;font-weight:600;color:#374151;text-decoration:none;text-transform:uppercase;letter-spacing:.3px;border-radius:8px;transition:color .2s,background .2s}.aj-nav-list>li>a:hover{color:#0083c4;background:rgba(0,131,196,.06)}.aj-navbar__brand{font-size:1.25rem;font-weight:800;color:#0083c4}.aj-navbar__logo-img{max-height:40px;width:auto;height:auto;object-fit:contain;display:block}.aj-lowcost-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:linear-gradient(135deg,#f37a1f,#ef4444);color:#fff;font-size:13px;font-weight:700;text-transform:uppercase;border-radius:20px;box-shadow:0 4px 12px rgba(243,122,31,.3)}';
        echo '<style id="ajth-critical-header">'.$css.'</style>'."\n";
    }

    $footer_selectors = '#footer,#footer-outer,.site-footer,.footer-wrapper,.footer-widget-area,#colophon,.footer,.st-footer,.footer-top,.footer-bottom,#main-footer,.footer-area,.st-footer-wrap,.footer-wrap,.content-footer,.footer-outer';
    if ($render_footer) {
        $footer_css = 'body.aj-custom-footer '.implode(',body.aj-custom-footer ', explode(',', $footer_selectors)).',body.aj-custom-footer footer:not(.aj-footer-v2):not(.aj-footer-sitewide footer){display:none!important}';
        echo '<style id="ajth-critical-footer">'.$footer_css.'</style>'."\n";
    }
}
add_action('wp_head', 'ajth_critical_header_css', 1);

/* Preload main stylesheet and critical fonts so header renders correctly on first paint */
function ajth_preload_styles()
{
    $load = is_front_page() || is_home() || ajth_is_catalog_context();
    if (! $load && is_singular()) {
        global $post;
        if ($post && has_shortcode($post->post_content, 'ajth_homepage')) {
            $load = true;
        }
    }
    if (! $load) {
        $h = ajth_get_header_settings();
        if ((! empty($h['enabled']) && ! empty($h['show_header_sitewide'])) || ! empty($h['show_footer_sitewide'])) {
            $load = true;
        }
    }
    if (! $load) {
        return;
    }
    echo '<link rel="preload" href="'.esc_url(AJTH_URL.'assets/css/home.css').'?ver='.esc_attr(AJTH_VERSION).'" as="style">'."\n";
    echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Cairo:wght@700;900&family=Noto+Sans+Arabic:wght@400;600;700&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" as="style">'."\n";
    echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">'."\n";
}
add_action('wp_head', 'ajth_preload_styles', 0);

/* ──────────────────────────────────────────────
 * Shortcode: [ajth_homepage]
 * Renders the full homepage inside any page.
 * ────────────────────────────────────────────── */
function ajth_homepage_shortcode($atts)
{
    ob_start();
    $settings = ajth_get_settings();
    include AJTH_DIR.'templates/homepage.php';

    return ob_get_clean();
}
add_shortcode('ajth_homepage', 'ajth_homepage_shortcode');

/**
 * Render the shared Ajinsafro site header.
 *
 * Includes:
 * - topbar
 * - navbar
 * - low cost CTA
 *
 * @param  array|null  $settings
 * @return void
 */
function ajth_render_site_header(?array $settings = null): void
{
    if (! is_array($settings)) {
        $settings = ajth_get_settings();
    }

    include AJTH_DIR.'parts/header.php';
}

/**
 * Render the home-specific hero block.
 *
 * Includes:
 * - hero visual
 * - floating search bar
 *
 * @param  array|null  $settings
 * @return void
 */
function ajth_render_home_hero(?array $settings = null): void
{
    if (! is_array($settings)) {
        $settings = ajth_get_settings();
    }

    include AJTH_DIR.'parts/hero.php';
}

/**
 * Backward-compatible helper for pages that need the full home stack.
 *
 * @param  array|null  $settings
 * @return void
 */
function ajth_render_primary_front_header(?array $settings = null): void
{
    ajth_render_site_header($settings);
    ajth_render_home_hero($settings);
}

/**
 * Render the standalone homepage accordion section based on the approved reference.
 *
 * @return void
 */
function ajth_render_reference_accordion_section()
{
    $settings = ajth_get_settings();
    $enabled = ! empty($settings['accordion_slider']['enabled']);
    $enabled = apply_filters('ajth_reference_accordion_enabled', $enabled, $settings);
    if (! $enabled) {
        return;
    }

    include AJTH_DIR.'parts/reference-accordion.php';
}

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
function ajth_get_header_settings()
{
    $cache_key = 'ajth_header_settings_v2';
    $cache_ts_key = 'ajth_header_settings_ts';

    if (isset($_GET['ajth_flush']) && $_GET['ajth_flush'] === '1' && current_user_can('manage_options')) {
        delete_transient($cache_key);
        delete_transient($cache_ts_key);
    }

    $db_ts = get_option('aj_header_settings_ts', '0');
    $cached_ts = get_transient($cache_ts_key);
    $cached = get_transient($cache_key);

    if (is_array($cached) && $cached_ts === $db_ts) {
        // Normalize even cached values to avoid legacy wp-login.php links.
        $cached = ajth_normalize_auth_urls($cached);

        return $cached;
    }

    $defaults = [
        'enabled' => true,
        'topbar_enabled' => true,
        'phone' => '+212 5 39 32 38 74',
        'email' => 'contact@ajinsafro.ma',
        'socials' => [
            'facebook' => '#',
            'twitter' => '#',
            'instagram' => '#',
            'youtube' => '#',
            'linkedin' => '#',
        ],
        'navbar_enabled' => true,
        'logo_url' => '',
        'show_auth_links' => true,
        'login_url' => '/login',
        'signup_url' => '/register',
        'menu_source' => 'wp_menu',
        'wp_menu_location' => 'primary',
        'show_header_sitewide' => false,
        'show_footer_sitewide' => true,
        'links' => [],
        'lowcost_enabled' => true,
        'lowcost_text' => 'Formule low cost',
        'lowcost_url' => '#',
    ];

    $raw = get_option('aj_header_settings', '');

    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $settings = array_replace_recursive($defaults, $decoded);
            $settings = ajth_normalize_auth_urls($settings);
            set_transient($cache_key, $settings, 10 * MINUTE_IN_SECONDS);
            set_transient($cache_ts_key, $db_ts, 10 * MINUTE_IN_SECONDS);

            return $settings;
        }
    }

    $defaults = ajth_normalize_auth_urls($defaults);
    set_transient($cache_key, $defaults, 2 * MINUTE_IN_SECONDS);
    set_transient($cache_ts_key, $db_ts, 2 * MINUTE_IN_SECONDS);

    return $defaults;
}

/**
 * Normalize storage URLs to point to the correct domain (booking.ajinsafro.net).
 *
 * The Laravel admin stores images in storage/app/public and generates URLs
 * using Storage::disk('public')->url(). If APP_URL or ADMIN_URL is misconfigured,
 * URLs may point to ajinsafro.net/storage/... instead of booking.ajinsafro.net/storage/...
 *
 * This helper fixes those URLs so images load correctly on the front-end.
 *
 * @param  string  $url  The URL to normalize.
 * @return string The normalized URL.
 */
function ajth_normalize_storage_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    $booking_host = 'booking.ajinsafro.net';
    $wrong_patterns = [
        '#^https?://ajinsafro\.net/storage/#i',
        '#^https?://www\.ajinsafro\.net/storage/#i',
        '#^//ajinsafro\.net/storage/#i',
    ];

    foreach ($wrong_patterns as $pattern) {
        if (preg_match($pattern, $url)) {
            $url = preg_replace($pattern, 'https://'.$booking_host.'/storage/', $url);
            break;
        }
    }

    if (preg_match('#^storage/#', $url)) {
        $url = 'https://'.$booking_host.'/'.$url;
    }

    return $url;
}

/**
 * Keep front auth links on native WP endpoints by default.
 * Prevents accidental routing to custom /login flows that break username/email auth.
 */
function ajth_normalize_auth_urls(array $settings): array
{
    $login_raw = isset($settings['login_url']) ? trim((string) $settings['login_url']) : '';
    $signup_raw = isset($settings['signup_url']) ? trim((string) $settings['signup_url']) : '';

    if ($login_raw === '' || str_contains($login_raw, 'wp-login.php')) {
        $settings['login_url'] = home_url('/login/');
    }

    if ($signup_raw === '' || str_contains($signup_raw, 'wp-login.php')) {
        $settings['signup_url'] = home_url('/register/');
    }

    return $settings;
}

function ajth_public_login_endpoint(): string
{
    return apply_filters('ajth_public_login_endpoint', 'https://booking.ajinsafro.net/auth/public-login');
}

/* ──────────────────────────────────────────────
 * Add body class when custom header is active
 * (home only, or all pages if show_header_sitewide)
 * ────────────────────────────────────────────── */
function ajth_body_class_custom_header($classes)
{
    $h = ajth_get_header_settings();
    $on_home = is_front_page() || is_home();
    $on_voyages = ajth_is_catalog_context();

    if (! empty($h['enabled']) && ($on_home || $on_voyages || ! empty($h['show_header_sitewide']))) {
        $classes[] = 'aj-custom-header';
    }
    if (! empty($h['show_footer_sitewide'])) {
        $classes[] = 'aj-custom-footer';
    }
    if ($on_home) {
        $classes[] = 'aj-has-bg-pattern';
    }

    return $classes;
}
add_filter('body_class', 'ajth_body_class_custom_header');

/* ──────────────────────────────────────────────
 * Output custom header on all pages when site-wide is on.
 * Uses get_header (runs before theme header.php) so it works
 * even if the theme does not call wp_body_open().
 * On home the template already includes the header.
 * ────────────────────────────────────────────── */
function ajth_render_header_sitewide()
{
    if (is_front_page() || is_home() || ajth_is_catalog_context()) {
        return;
    }
    $h = ajth_get_header_settings();
    if (empty($h['enabled']) || empty($h['show_header_sitewide'])) {
        return;
    }
    include AJTH_DIR.'parts/header.php';
}
add_action('get_header', 'ajth_render_header_sitewide', 5);

/* ──────────────────────────────────────────────
 * Output custom footer on pages when site-wide footer is enabled.
 * ────────────────────────────────────────────── */
function ajth_render_footer_sitewide()
{
    if (is_singular()) {
        global $post;
        if ($post && has_shortcode($post->post_content, 'ajth_homepage')) {
            return;
        }
    }
    $h = ajth_get_header_settings();
    if (empty($h['show_footer_sitewide'])) {
        return;
    }
    $settings = ajth_get_settings();
    echo '<div class="aj-footer-sitewide">';
    include AJTH_DIR.'parts/newsletter.php';
    echo '</div>';
}
add_action('wp_footer', 'ajth_render_footer_sitewide', 1);

/**
 * Strip prototype placeholder links (#) so panels are not wrapped in empty anchors.
 *
 * @param  string  $url  Raw URL from settings.
 * @return string Sanitized URL or empty string.
 */
/* ──────────────────────────────────────────────
 * Helper: get plugin settings with defaults
 * ────────────────────────────────────────────── */
function ajth_get_settings()
{
    $defaults = [
        'hero' => [
            'type' => 'image',
            'image_url' => '',
            'video_url' => '',
            'title' => 'Partir en vacances au meilleur prix !',
            'subtitle' => '',
            'cta_text' => '',
            'cta_url' => '',
            'overlay' => 0.4,
        ],
        'sections' => [
            'search' => true,
            'last_minute' => true,
            'accommodations' => true,
            'holiday_theme' => true,
            'regions' => true,
            'good_spots' => true,
            'whatsapp_banner' => true,
            'cruises' => true,
            'newsletter' => true,
        ],
        'search' => [
            'shortcode' => '[traveler_search]',
        ],
        'last_minute' => [
            'title' => 'Cap sur les tendances du moment',
            'count' => 4,
            'featured_only' => false,
        ],
        'accommodations' => [
            'title' => 'Découvrez des séjours uniques',
            'count' => 4,
        ],
        'holiday_theme' => [
            'enabled' => true,
            'eyebrow' => 'Voyages par thème',
            'title_line_1' => 'Explorez',
            'title_line_2' => 'les voyages',
            'title_line_3' => 'par thème',
            'subtitle' => 'Des idées d’évasion pensées pour chaque envie.',
            'left_image_url' => '',
            'deco_image_url' => '',
            'button_text' => 'VOIR PLUS',
            'button_url' => '#',
            'items' => [],
        ],
        'regions' => [],
        'good_spots' => [
            ['title' => 'Restaurants', 'subtitle' => 'Où manger ?', 'icon' => 'fas fa-utensils', 'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80', 'link_url' => '#'],
            ['title' => 'Loisirs', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-icons', 'image_url' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?auto=format&fit=crop&w=800&q=80', 'link_url' => '#'],
            ['title' => 'Que faire ?', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-map-marked-alt', 'image_url' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80', 'link_url' => '#'],
            ['title' => 'Shopping', 'subtitle' => 'Lorem ipsum dolor sit amet', 'icon' => 'fas fa-shopping-bag', 'image_url' => 'https://images.unsplash.com/photo-1481437156560-3205f6a55735?auto=format&fit=crop&w=800&q=80', 'link_url' => '#'],
        ],
        'good_spots_title' => 'Les bons coins sur votre destination',
        'accordion_slider' => [
            'enabled' => true,
            'autoplay' => true,
            'autoplay_speed' => 5000,
            'slides' => ajth_default_accordion_slider_slides(),
        ],
        'whatsapp_banner' => [
            'enabled' => true,
            'title' => 'Rejoignez notre chaîne WhatsApp',
            'subtitle' => 'Recevez nos offres, actus et inspirations voyage.',
            'features' => ['Promos', 'Nouveautés', 'Conseils'],
            'button_text' => 'Rejoindre',
            'button_url' => '#',
            'qr_code_url' => '',
        ],
        'whatsapp_banner' => [
            'enabled' => true,
            'title' => 'Rejoignez notre chaîne WhatsApp pour suivre nos actualités voyage',
            'subtitle' => 'Restez informé avec AjinSafro',
            'features' => [],
            'button_text' => 'Rejoindre',
            'button_url' => '#',
            'qr_code_url' => '',
        ],
        'cruises' => [
            'enabled' => true,
            'title' => 'Croisières',
            'image_url' => '',
            'button_text' => 'Découvrir',
            'button_url' => '#',
        ],
        'section_order' => ['last_minute', 'accommodations', 'holiday_theme', 'regions', 'good_spots', 'whatsapp_banner', 'cruises', 'newsletter'],
        'footer' => [
            'col1_heading' => 'En savoir plus',
            'col2_heading' => 'Société',
            'legal_text' => "Licence N° 489117 | RC: 18989\nPatente: 50411316 | I.C.E: 001585417000035\nAjinSafro Recreation SARL AU",
        ],
    ];

    if (function_exists('wp_cache_delete')) {
        wp_cache_delete('aj_home_settings', 'options');
    }

    $raw = get_option('aj_home_settings', '{}');
    $saved = is_string($raw) ? json_decode($raw, true) : [];

    if (! is_array($saved) || empty($saved)) {
        $saved = ajth_legacy_settings_to_json();
    }
    $needs_cleanup = false;
    if (isset($saved['promotions'])) {
        unset($saved['promotions']);
        $needs_cleanup = true;
    }
    if (isset($saved['sections']['promotions'])) {
        unset($saved['sections']['promotions']);
        $needs_cleanup = true;
    }
    if (isset($saved['section_order']) && is_array($saved['section_order'])) {
        $clean_order = array_values(array_filter($saved['section_order'], static fn ($key) => $key !== 'promotions'));
        if ($clean_order !== $saved['section_order']) {
            $saved['section_order'] = $clean_order;
            $needs_cleanup = true;
        }
    }
    if ($needs_cleanup) {
        update_option('aj_home_settings', wp_json_encode($saved, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), false);
    }

    $settings = array_replace_recursive($defaults, $saved);
    $settings['holiday_theme'] = ajth_normalize_holiday_theme_settings(
        isset($settings['holiday_theme']) ? $settings['holiday_theme'] : [],
        $defaults['holiday_theme']
    );
    $settings['accordion_slider'] = ajth_normalize_accordion_slider_settings(
        isset($settings['accordion_slider']) ? $settings['accordion_slider'] : [],
        $defaults['accordion_slider']
    );
    $settings['section_order'] = ajth_normalize_section_order_with_holiday_theme(
        isset($settings['section_order']) ? $settings['section_order'] : [],
        ! empty($settings['holiday_theme']['enabled'])
    );

    $settings['hero']['overlay'] = max(0, min(1, floatval($settings['hero']['overlay'])));
    $settings['last_minute']['count'] = max(1, intval($settings['last_minute']['count']));
    $settings['sections']['search'] = ! empty($settings['sections']['search']);
    $settings['sections']['last_minute'] = ! empty($settings['sections']['last_minute']);
    $settings['sections']['accommodations'] = ! empty($settings['sections']['accommodations']);
    $settings['sections']['holiday_theme'] = ! empty($settings['sections']['holiday_theme']) || ! empty($settings['holiday_theme']['enabled']);
    $settings['sections']['regions'] = ! empty($settings['sections']['regions']);
    $settings['sections']['good_spots'] = ! empty($settings['sections']['good_spots']);
    $settings['sections']['whatsapp_banner'] = ! empty($settings['sections']['whatsapp_banner']);
    $settings['sections']['cruises'] = ! empty($settings['sections']['cruises']);
    $settings['sections']['newsletter'] = ! empty($settings['sections']['newsletter']);

    return $settings;
}

function ajth_truthy($value): bool
{
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) || is_float($value)) {
        return (int) $value === 1;
    }
    if (is_string($value)) {
        $v = strtolower(trim($value));

        return in_array($v, ['1', 'true', 'on', 'yes'], true);
    }

    return ! empty($value);
}

function ajth_normalize_holiday_theme_settings($theme, array $defaults): array
{
    if (is_string($theme)) {
        $decoded = json_decode($theme, true);
        $theme = is_array($decoded) ? $decoded : [];
    }
    if (! is_array($theme)) {
        $theme = [];
    }
    $theme = array_replace_recursive($defaults, $theme);
    $theme['enabled'] = ajth_truthy($theme['enabled'] ?? true);

    $items = $theme['items'] ?? [];
    if ((! is_array($items) || empty($items)) && ! empty($theme['cards']) && is_array($theme['cards'])) {
        $items = $theme['cards'];
    }
    if (is_string($items)) {
        $decoded = json_decode($items, true);
        $items = is_array($decoded) ? $decoded : [];
    }
    if (! is_array($items)) {
        $items = [];
    }

    $normalized = [];
    foreach ($items as $idx => $item) {
        if (is_string($item)) {
            $decoded = json_decode($item, true);
            $item = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($item)) {
            continue;
        }
        $title = trim((string) ($item['title'] ?? ''));
        if ($title === '') {
            continue;
        }
        $item['title'] = $title;
        $image_url = trim((string) ($item['image_url'] ?? ''));
        if ($image_url === '') {
            $image_url = trim((string) ($item['image'] ?? ''));
        }
        $item['image_url'] = $image_url;
        $item['image'] = $image_url;
        $item['badge'] = trim((string) ($item['badge'] ?? ''));
        $item['description'] = trim((string) ($item['description'] ?? ''));
        $item['active'] = ajth_truthy($item['active'] ?? true);
        $item['order'] = isset($item['order']) ? (int) $item['order'] : (int) $idx;
        $normalized[] = $item;
    }

    usort($normalized, static function ($a, $b) {
        return ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
    });
    $theme['items'] = $normalized;

    return $theme;
}

function ajth_default_accordion_slider_slides(): array
{
    return [
        [
            'title' => 'PROGRAMME DE FIDELITE',
            'subtitle' => '',
            'image' => AJTH_URL.'assets/img/slide-1.png',
            'link' => 'https://www.ajinsafro.ma/fidelite',
            'button_text' => "S'inscrire !",
            'button_style' => 'orange',
            'overlay_color' => 'linear-gradient(to bottom, rgba(0, 163, 224, 0.10), rgba(0, 129, 188, 0.10))',
            'order' => 1,
        ],
        [
            'title' => 'GROUP DEALS TRAVEL',
            'subtitle' => '',
            'image' => AJTH_URL.'assets/img/slide-2.png',
            'link' => '#',
            'button_text' => '',
            'button_style' => 'orange',
            'overlay_color' => 'linear-gradient(to bottom, rgba(74, 222, 128, 0.05), rgba(22, 163, 74, 0.05))',
            'order' => 2,
        ],
        [
            'title' => "L'7AJZ BKRI B'DHAB MCHRI",
            'subtitle' => '',
            'image' => AJTH_URL.'assets/img/slide-3.png',
            'link' => '#',
            'button_text' => 'احجز الآن',
            'button_style' => 'white-arabic',
            'overlay_color' => 'linear-gradient(to bottom, rgba(27, 92, 140, 0.05), rgba(14, 58, 90, 0.05))',
            'order' => 3,
        ],
        [
            'title' => 'Programme BZTAM eSFAR',
            'subtitle' => '',
            'image' => AJTH_URL.'assets/img/slide-4.png',
            'link' => '#',
            'button_text' => '',
            'button_style' => 'orange',
            'overlay_color' => 'linear-gradient(to bottom, rgba(250, 204, 21, 0.10), rgba(249, 115, 22, 0.10))',
            'order' => 4,
        ],
        [
            'title' => 'IMPORTANT UPDATES',
            'subtitle' => '',
            'image' => '',
            'link' => '#',
            'button_text' => '',
            'button_style' => 'orange',
            'overlay_color' => '',
            'order' => 5,
        ],
    ];
}

function ajth_normalize_accordion_slider_settings($accordion, array $defaults): array
{
    if (is_string($accordion)) {
        $decoded = json_decode($accordion, true);
        $accordion = is_array($decoded) ? $decoded : [];
    }
    if (! is_array($accordion)) {
        $accordion = [];
    }

    $accordion = array_replace_recursive($defaults, $accordion);
    $accordion['enabled'] = ajth_truthy($accordion['enabled'] ?? true);
    $accordion['autoplay'] = ajth_truthy($accordion['autoplay'] ?? true);
    $accordion['autoplay_speed'] = max(2000, min(30000, (int) ($accordion['autoplay_speed'] ?? 5000)));

    $slides = $accordion['slides'] ?? [];
    if (is_string($slides)) {
        $decoded = json_decode($slides, true);
        $slides = is_array($decoded) ? $decoded : [];
    }
    if (! is_array($slides)) {
        $slides = [];
    }

    $normalized = [];
    foreach ($slides as $idx => $slide) {
        if (is_string($slide)) {
            $decoded = json_decode($slide, true);
            $slide = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($slide)) {
            continue;
        }

        $title = trim((string) ($slide['title'] ?? ''));
        if ($title === '') {
            continue;
        }

        $normalized[] = [
            'title' => $title,
            'subtitle' => trim((string) ($slide['subtitle'] ?? '')),
            'image' => trim((string) ($slide['image'] ?? '')),
            'link' => trim((string) ($slide['link'] ?? '#')),
            'button_text' => trim((string) ($slide['button_text'] ?? '')),
            'button_style' => trim((string) ($slide['button_style'] ?? 'orange')),
            'overlay_color' => trim((string) ($slide['overlay_color'] ?? '')),
            'order' => isset($slide['order']) ? (int) $slide['order'] : ($idx + 1),
        ];
    }

    usort($normalized, static function ($a, $b) {
        return ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
    });
    $accordion['slides'] = $normalized;

    return $accordion;
}

function ajth_normalize_section_order_with_holiday_theme($order, bool $holidayEnabled): array
{
    $fallback = ['last_minute', 'accommodations', 'holiday_theme', 'regions', 'good_spots', 'whatsapp_banner', 'cruises'];
    if (! is_array($order)) {
        $order = $fallback;
    }

    $normalized = [];
    foreach ($order as $key) {
        if (! is_string($key) || $key === '') {
            continue;
        }
        if (! in_array($key, $normalized, true)) {
            $normalized[] = $key;
        }
    }
    $normalized = array_values(array_filter($normalized, static fn ($key) => $key !== 'promotions'));

    if ($holidayEnabled && ! in_array('holiday_theme', $normalized, true)) {
        $after = array_search('accommodations', $normalized, true);
        if ($after === false) {
            array_unshift($normalized, 'holiday_theme');
        } else {
            array_splice($normalized, $after + 1, 0, ['holiday_theme']);
        }
    }

    if (! in_array('whatsapp_banner', $normalized, true)) {
        $normalized[] = 'whatsapp_banner';
    }

    return $normalized;
}

/* ──────────────────────────────────────────────
 * Destinations par région (Laravel admin, key destinations_by_region)
 * Mirrored to wp_options aj_destinations_by_region — used for 2×4 grid on home.
 * ────────────────────────────────────────────── */
function ajth_get_destinations_by_region()
{
    $defaults = [
        'enabled' => true,
        'title' => 'Nos destinations',
        'items' => [],
    ];

    $raw = get_option('aj_destinations_by_region', '');
    if (! is_string($raw) || $raw === '') {
        return $defaults;
    }

    $decoded = json_decode($raw, true);
    if (! is_array($decoded)) {
        return $defaults;
    }

    $items = isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : [];

    return [
        'enabled' => ! empty($decoded['enabled']),
        'title' => isset($decoded['title']) ? (string) $decoded['title'] : $defaults['title'],
        'items' => $items,
    ];
}

/* ──────────────────────────────────────────────
 * Voyages page URL helper
 * ────────────────────────────────────────────── */
function ajth_get_voyages_page_url()
{
    $page = get_page_by_path('voyages');
    if ($page instanceof WP_Post) {
        $url = get_permalink($page);
        if ($url) {
            return $url;
        }
    }

    $archive = get_post_type_archive_link('st_tours');
    if ($archive) {
        return $archive;
    }

    return home_url('/voyages/');
}

/* ──────────────────────────────────────────────
 * Vols page URL helper
 * ────────────────────────────────────────────── */
function ajth_get_vols_page_url()
{
    $page = get_page_by_path('vols');
    if ($page instanceof WP_Post) {
        $url = get_permalink($page);
        if ($url) {
            return $url;
        }
    }

    return home_url('/vols/');
}

function ajth_get_hebergement_page_url()
{
    $page = get_page_by_path('hebergement');
    if ($page instanceof WP_Post) {
        $url = get_permalink($page);
        if ($url) {
            return $url;
        }
    }

    $archive = get_post_type_archive_link('st_hotel');
    if ($archive) {
        return $archive;
    }

    return home_url('/hebergement/');
}

function ajth_get_activites_page_url()
{
    $page = get_page_by_path('activites');
    if ($page instanceof WP_Post) {
        $url = get_permalink($page);
        if ($url) {
            return $url;
        }
    }

    $archive = get_post_type_archive_link('st_activity');
    if ($archive) {
        return $archive;
    }

    return home_url('/activites/');
}

function ajth_get_transfert_page_url()
{
    $page = get_page_by_path('transfert');
    if ($page instanceof WP_Post) {
        $url = get_permalink($page);
        if ($url) {
            return $url;
        }
    }

    $archive = get_post_type_archive_link('st_cars');
    if ($archive) {
        return $archive;
    }

    return home_url('/transfert/');
}

function ajth_is_voyages_context()
{
    return is_page('voyages') || is_post_type_archive('st_tours') || (is_search() && get_query_var('post_type') === 'st_tours');
}

function ajth_is_hebergement_context()
{
    return is_page('hebergement') || is_post_type_archive('st_hotel') || (is_search() && get_query_var('post_type') === 'st_hotel');
}

function ajth_is_activites_context()
{
    return is_page('activites') || is_post_type_archive('st_activity') || (is_search() && get_query_var('post_type') === 'st_activity');
}

function ajth_is_transfert_context()
{
    return is_page('transfert') || is_post_type_archive('st_cars') || (is_search() && get_query_var('post_type') === 'st_cars');
}

function ajth_is_catalog_context()
{
    return ajth_is_voyages_context()
        || ajth_is_hebergement_context()
        || ajth_is_activites_context()
        || ajth_is_transfert_context();
}

/* ──────────────────────────────────────────────
 * Ensure "Voyages" page exists (slug: voyages)
 * ────────────────────────────────────────────── */
function ajth_ensure_voyages_page()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pages = get_posts([
        'post_type' => 'page',
        'name' => 'voyages',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'suppress_filters' => true,
    ]);

    if (! empty($pages)) {
        $p = $pages[0];
        if ($p->post_status === 'trash') {
            wp_untrash_post((int) $p->ID);
            $p = get_post((int) $p->ID);
        }
        if ($p instanceof WP_Post && $p->post_status !== 'publish') {
            wp_update_post([
                'ID' => (int) $p->ID,
                'post_status' => 'publish',
            ]);
        }

        return;
    }

    wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'Voyages',
        'post_name' => 'voyages',
        'post_content' => '<!-- Ajinsafro Traveler Home : template catalogue voyages (plugin). -->',
    ]);
}

/**
 * Première exécution après mise à jour : rafraîchir les permaliens pour que /voyages/ soit résolu.
 */
function ajth_maybe_flush_rewrite_rules_once(): void
{
    if (get_option('ajth_voyages_routing_flush_v1')) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('ajth_voyages_routing_flush_v1', '1', true);
}
add_action('init', 'ajth_ensure_voyages_page', 10);
add_action('init', 'ajth_maybe_flush_rewrite_rules_once', 99);

/* ──────────────────────────────────────────────
 * Ensure "Vols" page exists (slug: vols)
 * ────────────────────────────────────────────── */
function ajth_ensure_vols_page()
{
    if (get_page_by_path('vols')) {
        return;
    }

    wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'Vols',
        'post_name' => 'vols',
        'post_content' => '',
    ]);
}

function ajth_ensure_catalog_page(string $slug, string $title, string $content = ''): void
{
    $page = get_page_by_path($slug);
    if ($page instanceof WP_Post) {
        if ($page->post_status === 'trash') {
            wp_untrash_post((int) $page->ID);
        }
        if ($page->post_status !== 'publish') {
            wp_update_post([
                'ID' => (int) $page->ID,
                'post_status' => 'publish',
            ]);
        }

        return;
    }

    wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $content,
    ]);
}

function ajth_ensure_hebergement_page(): void
{
    ajth_ensure_catalog_page('hebergement', 'Hébergement', '<!-- Ajinsafro Traveler Home : template hebergement (plugin). -->');
}

function ajth_ensure_activites_page(): void
{
    ajth_ensure_catalog_page('activites', 'Activités', '<!-- Ajinsafro Traveler Home : template activites (plugin). -->');
}

function ajth_ensure_transfert_page(): void
{
    ajth_ensure_catalog_page('transfert', 'Transfert', '<!-- Ajinsafro Traveler Home : template transfert (plugin). -->');
}

function ajth_ensure_login_page()
{
    if (get_page_by_path('login')) {
        return;
    }

    wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'Connexion',
        'post_name' => 'login',
        'post_content' => '',
    ]);
}

function ajth_get_maintenance_url(): string
{
    return home_url('/maintenance/');
}

function ajth_is_under_construction_label($label): bool
{
    $label = is_string($label) ? trim($label) : '';
    if ($label === '') {
        return false;
    }
    $key = function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
    $targets = [
        'voyages',
        'hébergement',
        'hebergement',
        'activités',
        'activites',
        'votre guide',
        'hajj & omra',
        'hajj',
        'omra',
        'transfert',
        'formule low cost',
    ];

    return in_array($key, $targets, true);
}

function ajth_ensure_maintenance_page()
{
    if (get_page_by_path('maintenance')) {
        return;
    }

    wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'Maintenance',
        'post_name' => 'maintenance',
        'post_content' => '',
    ]);
}

/* ──────────────────────────────────────────────
 * Add Voyages item to WP menu if missing
 * ────────────────────────────────────────────── */
function ajth_add_voyages_menu_item($items, $args)
{
    if (empty($args->theme_location) || $args->theme_location !== 'primary') {
        return $items;
    }

    if (stripos(wp_strip_all_tags($items), 'voyage') !== false) {
        return $items;
    }

    $url = ajth_get_voyages_page_url();
    $active = ajth_is_voyages_context() ? ' current-menu-item current_page_item aj-active' : '';

    $items .= '<li class="menu-item menu-item-type-custom menu-item-object-custom'.esc_attr($active).'">'
        .'<a href="'.esc_url($url).'"><i class="fas fa-suitcase-rolling"></i> <span>'.esc_html__('Voyages', 'ajinsafro-traveler-home').'</span></a>'
        .'</li>';

    return $items;
}
add_filter('wp_nav_menu_items', 'ajth_add_voyages_menu_item', 20, 2);

function ajth_legacy_settings_to_json()
{
    $legacy = get_option('ajth_home_settings', []);

    if (! is_array($legacy) || empty($legacy)) {
        return [];
    }

    $regions = [];
    if (! empty($legacy['regions']) && is_array($legacy['regions'])) {
        foreach ($legacy['regions'] as $region) {
            $img = '';
            if (! empty($region['image']) && is_numeric($region['image'])) {
                $img = wp_get_attachment_image_url(absint($region['image']), 'large');
            }
            $regions[] = [
                'title' => $region['title'] ?? '',
                'image_url' => $img ?: '',
                'link_url' => $region['url'] ?? '#',
            ];
        }
    }

    $spots = [];
    if (! empty($legacy['good_spots']) && is_array($legacy['good_spots'])) {
        foreach ($legacy['good_spots'] as $spot) {
            $img = '';
            if (! empty($spot['image']) && is_numeric($spot['image'])) {
                $img = wp_get_attachment_image_url(absint($spot['image']), 'large');
            }
            $spots[] = [
                'title' => $spot['title'] ?? '',
                'image_url' => $img ?: '',
                'link_url' => $spot['url'] ?? '#',
            ];
        }
    }

    $heroImage = '';
    if (! empty($legacy['hero_image']) && is_numeric($legacy['hero_image'])) {
        $heroImage = wp_get_attachment_image_url(absint($legacy['hero_image']), 'full');
    }

    return [
        'hero' => [
            'type' => 'image',
            'image_url' => $heroImage ?: '',
            'video_url' => '',
            'title' => $legacy['hero_title'] ?? 'Découvrez le Maroc',
            'subtitle' => $legacy['hero_subtitle'] ?? '',
            'cta_text' => 'Voir les offres',
            'cta_url' => '',
            'overlay' => 0.35,
        ],
        'sections' => [
            'search' => true,
            'last_minute' => true,
            'regions' => true,
            'good_spots' => true,
        ],
        'search' => [
            'shortcode' => '[traveler_search]',
        ],
        'last_minute' => [
            'title' => $legacy['last_minute_title'] ?? 'Offres de dernière minute',
            'count' => 6,
            'featured_only' => false,
        ],
        'regions' => $regions,
        'good_spots' => $spots,
    ];
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
class AJTH_Nav_Walker extends Walker_Nav_Menu
{
    private static $title_icon_map = [
        'packages' => 'fas fa-suitcase-rolling',
        'package' => 'fas fa-suitcase-rolling',
        'voyages' => 'fas fa-suitcase-rolling',
        'voyage' => 'fas fa-suitcase-rolling',
        'hébergement' => 'fas fa-hotel',
        'hebergement' => 'fas fa-hotel',
        'hôtel' => 'fas fa-hotel',
        'hotel' => 'fas fa-hotel',
        'activités' => 'fas fa-camera',
        'activites' => 'fas fa-camera',
        'activité' => 'fas fa-camera',
        'transfert' => 'fas fa-car-side',
        'transferts' => 'fas fa-car-side',
        'hajj & omra' => 'fas fa-kaaba',
        'hajj' => 'fas fa-kaaba',
        'omra' => 'fas fa-kaaba',
        'votre guide' => 'fas fa-map-signs',
        'guide' => 'fas fa-map-signs',
        'accueil' => 'fas fa-home',
        'contact' => 'fas fa-envelope',
        'blog' => 'fas fa-blog',
    ];

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        if (isset($args->item_spacing) && $args->item_spacing === 'discard') {
            $t = '';
            $n = '';
        } else {
            $t = "\t";
            $n = "\n";
        }
        $indent = ($depth) ? str_repeat($t, $depth) : '';

        $title_raw = apply_filters('the_title', $item->title, $item->ID);

        // Skip items whose title contains "low cost" (handled by plugin button)
        if (stripos($title_raw, 'low cost') !== false) {
            return;
        }

        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-'.$item->ID;

        // Extract FontAwesome icon from classes
        // WP stores each CSS class as a separate array element,
        // so "fas" and "fa-hotel" come as two entries.
        $fa_prefix = '';
        $fa_icon = '';
        $filtered_classes = [];
        foreach ($classes as $class) {
            $class = trim($class);
            if ($class === '') {
                continue;
            }
            if (preg_match('/^(fas|fa|far|fab|fal|fad)$/', $class)) {
                $fa_prefix = $class;
            } elseif (preg_match('/^fa-/', $class)) {
                $fa_icon = $class;
            } elseif (preg_match('/^(fas?|far|fab|fal|fad)\s+fa-/', $class)) {
                // Full class string in one entry (e.g. "fas fa-hotel")
                $fa_prefix = '';
                $fa_icon = '';
                $icon_class_full = $class;
                $filtered_classes[] = $class; // will be removed below
            } else {
                $filtered_classes[] = $class;
            }
        }

        // Build icon class
        if (! empty($icon_class_full)) {
            $icon_class = trim($icon_class_full);
        } elseif ($fa_prefix && $fa_icon) {
            $icon_class = $fa_prefix.' '.$fa_icon;
        } elseif ($fa_icon) {
            $icon_class = 'fas '.$fa_icon;
        } else {
            $icon_class = '';
        }

        // Remove the full FA class from filtered_classes if it was added
        if (! empty($icon_class_full)) {
            $filtered_classes = array_filter($filtered_classes, function ($c) use ($icon_class_full) {
                return $c !== $icon_class_full;
            });
        }

        // Auto-map icon by title if none found from CSS classes
        if (empty($icon_class) && $depth === 0) {
            $title_lower = mb_strtolower(trim($title_raw), 'UTF-8');
            if (isset(self::$title_icon_map[$title_lower])) {
                $icon_class = self::$title_icon_map[$title_lower];
            }
        }

        // Check for has-children
        if (in_array('menu-item-has-children', $filtered_classes, true)) {
            $filtered_classes[] = 'aj-has-sub';
        }

        $args = apply_filters('nav_menu_item_args', $args, $item, $depth);

        $class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($filtered_classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="'.esc_attr($class_names).'"' : '';

        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-'.$item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="'.esc_attr($id_attr).'"' : '';

        $output .= $indent.'<li'.$id_attr.$class_names.'>';

        $atts = [];
        $atts['title'] = ! empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = ! empty($item->target) ? $item->target : '';
        if ($item->target === '_blank' && empty($item->xfn)) {
            $atts['rel'] = 'noopener';
        } else {
            $atts['rel'] = $item->xfn;
        }
        $href = ! empty($item->url) ? (string) $item->url : '';
        $is_placeholder = (
            $href === '' ||
            $href === '#' ||
            strpos($href, '#') === 0 ||
            $href === 'javascript:void(0)' ||
            $href === 'javascript:void(0);'
        );
        if ($is_placeholder && function_exists('ajth_is_under_construction_label') && ajth_is_under_construction_label($title_raw)) {
            $href = function_exists('ajth_get_maintenance_url') ? ajth_get_maintenance_url() : home_url('/maintenance/');
        }
        $atts['href'] = $href;
        $atts['aria-current'] = $item->current ? 'page' : '';

        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (is_scalar($value) && $value !== '' && $value !== false) {
                $value = ($attr === 'href') ? esc_url($value) : esc_attr($value);
                $attributes .= ' '.$attr.'="'.$value.'"';
            }
        }

        $title = apply_filters('nav_menu_item_title', $title_raw, $item, $args, $depth);

        $item_output = $args->before ?? '';
        $item_output .= '<a'.$attributes.'>';

        if ($icon_class && $depth === 0) {
            $item_output .= '<i class="'.esc_attr($icon_class).'"></i> ';
        }

        $item_output .= ($args->link_before ?? '').'<span>'.$title.'</span>'.($args->link_after ?? '');

        if (in_array('menu-item-has-children', $classes, true) && $depth === 0) {
            $item_output .= ' <i class="fas fa-chevron-down aj-caret"></i>';
        }

        $item_output .= '</a>';
        $item_output .= $args->after ?? '';

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
}

function ajth_debug_dump_home_settings_footer()
{
    if (! current_user_can('manage_options')) {
        return;
    }

    if (! isset($_GET['ajdebug']) || (string) $_GET['ajdebug'] !== '1') {
        return;
    }

    if (function_exists('wp_cache_delete')) {
        wp_cache_delete('aj_home_settings', 'options');
    }

    echo '<div style="margin:24px auto;max-width:1200px;padding:12px;border:1px dashed #b91c1c;background:#fff;">';
    echo '<strong>AJ DEBUG — get_option(\'aj_home_settings\')</strong>';
    echo '<pre style="white-space:pre-wrap;word-break:break-word;max-height:380px;overflow:auto;">';
    var_dump(get_option('aj_home_settings'));
    echo '</pre>';
    echo '</div>';
}
add_action('wp_footer', 'ajth_debug_dump_home_settings_footer', 9999);

/* ──────────────────────────────────────────────
 * Activation: set default options if not present
 * ────────────────────────────────────────────── */
function ajth_activate()
{
    if (get_option('ajth_home_settings') === false) {
        update_option('ajth_home_settings', []);
    }

    ajth_ensure_voyages_page();
    ajth_ensure_hebergement_page();
    ajth_ensure_activites_page();
    ajth_ensure_transfert_page();
    ajth_ensure_vols_page();
    ajth_ensure_login_page();
    ajth_ensure_maintenance_page();
}
register_activation_hook(__FILE__, 'ajth_activate');

add_action('init', 'ajth_ensure_login_page', 20);
add_action('init', 'ajth_ensure_maintenance_page', 20);
add_action('init', 'ajth_ensure_hebergement_page', 20);
add_action('init', 'ajth_ensure_activites_page', 20);
add_action('init', 'ajth_ensure_transfert_page', 20);
