<?php
/**
 * Part: Custom header (topbar + navbar)
 * Header settings managed from Laravel admin /admin/settings/home-page
 * Design based on AjinSafro modern travel theme
 *
 * @package AjinsafroTravelerHome
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$hdr = ajth_get_header_settings();

if ( empty( $hdr['enabled'] ) ) {
    return;
}

if ( function_exists( 'ajth_normalize_storage_url' ) && ! empty( $hdr['logo_url'] ) ) {
    $hdr['logo_url'] = ajth_normalize_storage_url( $hdr['logo_url'] );
}

$socials = isset( $hdr['socials'] ) && is_array( $hdr['socials'] ) ? $hdr['socials'] : array();

// SVG en ligne plutot que <i class="fab"> : la police FontAwesome Brands (116 Ko) etait
// telechargee pour une seule icone Instagram. Glyphes de FontAwesome Free (CC BY 4.0),
// tires de la police SVG et retournes (les polices SVG ont l'ordonnee vers le haut).
// Taille et couleur suivent le font-size et le color du lien, comme un glyphe.
$social_icons = array(
    'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path transform="scale(1,-1) translate(0,-448)" d="M279.14 160h-74.6895v-224h-100.17v224h-81.3906v92.6602h81.3906v70.6201c0 80.3398 47.8594 124.72 121.08 124.72c35.0693 0 71.75 -6.25977 71.75 -6.25977v-78.8906h-40.4199c-39.8203 0 -52.2402 -24.71 -52.2402 -50.0596v-60.1299h88.9102z"/></svg>',
    'twitter'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path transform="scale(1,-1) translate(0,-448)" d="M459.37 296.284c0.325195 -4.54785 0.325195 -9.09766 0.325195 -13.6455c0 -138.72 -105.583 -298.558 -298.559 -298.558c-59.4521 0 -114.68 17.2188 -161.137 47.1055c8.44727 -0.973633 16.5684 -1.29883 25.3398 -1.29883
c49.0547 0 94.2129 16.5684 130.274 44.832c-46.1318 0.975586 -84.792 31.1885 -98.1123 72.7725c6.49805 -0.974609 12.9951 -1.62402 19.8184 -1.62402c9.4209 0 18.8428 1.2998 27.6133 3.57324c-48.0811 9.74707 -84.1426 51.9795 -84.1426 102.984v1.29883
c13.9688 -7.79688 30.2139 -12.6699 47.4307 -13.3184c-28.2637 18.8428 -46.7803 51.0049 -46.7803 87.3906c0 19.4922 5.19629 37.3604 14.2939 52.9541c51.6543 -63.6748 129.3 -105.258 216.364 -109.807c-1.62402 7.79688 -2.59863 15.918 -2.59863 24.04
c0 57.8271 46.7822 104.934 104.934 104.934c30.2139 0 57.502 -12.6699 76.6709 -33.1367c23.7148 4.54785 46.4551 13.3193 66.5986 25.3398c-7.79785 -24.3662 -24.3662 -44.833 -46.1318 -57.8271c21.1172 2.27344 41.584 8.12207 60.4258 16.2432
c-14.292 -20.791 -32.1611 -39.3086 -52.6279 -54.2529z"/></svg>',
    'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path transform="scale(1,-1) translate(0,-448)" d="M549.655 323.917c11.4121 -42.8672 11.4121 -132.305 11.4121 -132.305s0 -89.4385 -11.4121 -132.306c-6.28125 -23.6494 -24.7871 -41.5 -48.2842 -47.8203c-42.5908 -11.4863 -213.371 -11.4863 -213.371 -11.4863s-170.78 0 -213.371 11.4863
c-23.4971 6.32031 -42.0029 24.1709 -48.2842 47.8203c-11.4121 42.8672 -11.4121 132.306 -11.4121 132.306s0 89.4375 11.4121 132.305c6.28125 23.6504 24.7871 42.2754 48.2842 48.5967c42.5908 11.4863 213.371 11.4863 213.371 11.4863s170.781 0 213.371 -11.4863
c23.4971 -6.32031 42.0029 -24.9463 48.2842 -48.5967zM232.145 110.409l142.739 81.2012l-142.739 81.2051v-162.406z"/></svg>',
    'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path transform="scale(1,-1) translate(0,-448)" d="M224.1 307c63.6006 0 114.9 -51.2998 114.9 -114.9c0 -63.5996 -51.2998 -114.899 -114.9 -114.899c-63.5996 0 -114.899 51.2998 -114.899 114.899c0 63.6006 51.2998 114.9 114.899 114.9zM224.1 117.4c41.1006 0 74.7002 33.5 74.7002 74.6992
c0 41.2002 -33.5 74.7002 -74.7002 74.7002c-41.1992 0 -74.6992 -33.5 -74.6992 -74.7002c0 -41.1992 33.5996 -74.6992 74.6992 -74.6992zM370.5 311.7c0 -14.9004 -12 -26.7998 -26.7998 -26.7998c-14.9004 0 -26.7998 12 -26.7998 26.7998s12 26.7998 26.7998 26.7998
s26.7998 -12 26.7998 -26.7998zM446.6 284.5c2.10059 -37 2.10059 -147.8 0 -184.8c-1.7998 -35.9004 -10 -67.7002 -36.1992 -93.9004c-26.2002 -26.2998 -58 -34.5 -93.9004 -36.2002c-37 -2.09961 -147.9 -2.09961 -184.9 0
c-35.8994 1.80078 -67.5996 10 -93.8994 36.2002s-34.5 58 -36.2002 93.9004c-2.09961 37 -2.09961 147.899 0 184.899c1.7998 35.9004 9.90039 67.7002 36.2002 93.9004s58.0996 34.4004 93.8994 36.0996c37 2.10059 147.9 2.10059 184.9 0
c35.9004 -1.7998 67.7002 -10 93.9004 -36.1992c26.2998 -26.2002 34.5 -58 36.1992 -93.9004zM398.8 60c11.7002 29.4004 9 99.5 9 132.1c0 32.6006 2.7002 102.601 -9 132.101c-7.89941 19.7002 -23 34.7998 -42.5996 42.5996c-29.4004 11.6006 -99.5 9 -132.101 9
c-32.5996 0 -102.6 2.7002 -132.1 -9c-19.7002 -7.89941 -34.7998 -23 -42.5996 -42.5996c-11.6006 -29.4004 -9 -99.5 -9 -132.101c0 -32.5996 -2.7002 -102.6 9 -132.1c7.89941 -19.7002 23 -34.7998 42.5996 -42.5996c29.4004 -11.6006 99.5 -9 132.1 -9
c32.6006 0 102.601 -2.7002 132.101 9c19.7002 7.89941 34.7998 23 42.5996 42.5996z"/></svg>',
    'linkedin'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path transform="scale(1,-1) translate(0,-448)" d="M100.28 0h-92.8799v299.1h92.8799v-299.1zM53.79 339.9c-29.7002 0 -53.79 24.5996 -53.79 54.2998c0 29.6914 24.0977 53.79 53.79 53.79s53.79 -24.0986 53.79 -53.79c0 -29.7002 -24.0996 -54.2998 -53.79 -54.2998zM447.9 0h-92.6807v145.6
c0 34.7002 -0.700195 79.2002 -48.29 79.2002c-48.29 0 -55.6895 -37.7002 -55.6895 -76.7002v-148.1h-92.7803v299.1h89.0801v-40.7998h1.2998c12.4004 23.5 42.6904 48.2998 87.8799 48.2998c94 0 111.28 -61.8994 111.28 -142.3v-164.3h-0.0996094z"/></svg>',
);

$voyages_page_url = function_exists( 'ajth_get_voyages_page_url' )
    ? ajth_get_voyages_page_url()
    : home_url( '/voyages/' );
$hebergement_page_url = function_exists( 'ajth_get_hebergement_page_url' )
    ? ajth_get_hebergement_page_url()
    : home_url( '/hebergement/' );
$activites_page_url = function_exists( 'ajth_get_activites_page_url' )
    ? ajth_get_activites_page_url()
    : home_url( '/activites/' );
$transfert_page_url = function_exists( 'ajth_get_transfert_page_url' )
    ? ajth_get_transfert_page_url()
    : home_url( '/transfert/' );
$group_deals_page_url = function_exists( 'ajth_get_group_deals_url' )
    ? ajth_get_group_deals_url()
    : home_url( '/group-deals/' );
$hajj_omra_page_url = function_exists( 'ajth_get_hajj_omra_page_url' )
    ? ajth_get_hajj_omra_page_url()
    : home_url( '/hajj-omra/' );
$economic_offers_page_url = function_exists( 'ajth_get_economic_offers_page_url' )
    ? ajth_get_economic_offers_page_url()
    : home_url( '/formule-economique/' );
$public_login_url = home_url( '/login/' );
$public_signup_url = home_url( '/register/' );
$maintenance_url = function_exists( 'ajth_get_maintenance_url' ) ? ajth_get_maintenance_url() : home_url( '/maintenance/' );
$resolve_menu_url = static function ( $label, $url ) use ( $maintenance_url, $voyages_page_url, $hebergement_page_url, $activites_page_url, $transfert_page_url, $group_deals_page_url, $hajj_omra_page_url, $economic_offers_page_url ) {
    $url_value = trim( (string) $url );
    $label_value = is_string( $label ) ? trim( wp_strip_all_tags( $label ) ) : '';
    if ( $label_value !== '' && function_exists( 'remove_accents' ) ) {
        $label_value = remove_accents( $label_value );
    }
    $label_value = $label_value !== '' ? mb_strtolower( $label_value, 'UTF-8' ) : '';
    if ( in_array( $label_value, array( 'group deals', 'group deal', 'votre guide', 'guide' ), true ) ) {
        return $group_deals_page_url;
    }
    $is_placeholder = (
        $url_value === '' ||
        $url_value === '#' ||
        strpos( $url_value, '#' ) === 0 ||
        $url_value === 'javascript:void(0)' ||
        $url_value === 'javascript:void(0);'
    );
    if ( $is_placeholder ) {
        if ( in_array( $label_value, array( 'voyages', 'voyage' ), true ) ) {
            return $voyages_page_url;
        }
        if ( in_array( $label_value, array( 'hébergement', 'hebergement', 'hôtel', 'hotel' ), true ) ) {
            return $hebergement_page_url;
        }
        if ( in_array( $label_value, array( 'activités', 'activites', 'activité', 'activite' ), true ) ) {
            return $activites_page_url;
        }
        if ( in_array( $label_value, array( 'transfert', 'transferts' ), true ) ) {
            return $transfert_page_url;
        }
        if ( in_array( $label_value, array( 'hajj & omra', 'hajj', 'omra' ), true ) ) {
            return $hajj_omra_page_url;
        }
        if ( in_array( $label_value, array( 'formule low cost', 'low cost', 'formule economique', 'economique' ), true ) ) {
            return $economic_offers_page_url;
        }
    }
    if ( $is_placeholder && function_exists( 'ajth_is_under_construction_label' ) && ajth_is_under_construction_label( $label ) ) {
        return $maintenance_url;
    }
    return $url_value !== '' ? $url_value : '#';
};

$is_voyages_page     = function_exists( 'ajth_is_voyages_context' )     ? ajth_is_voyages_context()     : ( is_page( 'voyages' ) || is_post_type_archive( 'st_tours' ) );
$is_hebergement_page = function_exists( 'ajth_is_hebergement_context' ) ? ajth_is_hebergement_context() : false;
$is_activites_page   = function_exists( 'ajth_is_activites_context' )   ? ajth_is_activites_context()   : false;
$is_group_deals_page = function_exists( 'ajth_is_group_deals_context' ) ? ajth_is_group_deals_context() : is_page( 'group-deals' );
$is_hajj_omra_page   = function_exists( 'ajth_is_hajj_omra_context' )   ? ajth_is_hajj_omra_context()   : is_page( 'hajj-omra' );
$is_economic_offers_page = function_exists( 'ajth_is_economic_offers_context' ) ? ajth_is_economic_offers_context() : ( is_page( 'formule-economique' ) || is_page( 'low-cost' ) );


$title_icon_map = array(
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
    'group deals'  => 'fas fa-users',
    'group deal'   => 'fas fa-users',
    'votre guide'  => 'fas fa-users',
    'guide'        => 'fas fa-users',
    'formule low cost' => 'fas fa-tags',
    'low cost'        => 'fas fa-tags',
    'formule economique' => 'fas fa-tags',
    'economique'      => 'fas fa-tags',
    'accueil'      => 'fas fa-home',
    'contact'      => 'fas fa-envelope',
    'blog'         => 'fas fa-blog',
);

$default_menu_items = array(
    array(
        'label'    => 'Voyages',
        'url'      => $voyages_page_url,
        'icon'     => 'fas fa-suitcase-rolling',
        'active'   => $is_voyages_page,
        'children' => array(),
    ),
    array(
        'label'    => 'Hébergement',
        'url'      => $hebergement_page_url,
        'icon'     => 'fas fa-hotel',
        'active'   => $is_hebergement_page,
        'children' => array(),
    ),
    array(
        'label'    => 'Activités',
        'url'      => $activites_page_url,
        'icon'     => 'fas fa-camera',
        'active'   => $is_activites_page,
        'children' => array(),
    ),
    array(
        'label'    => 'Hajj & Omra',
        'url'      => $hajj_omra_page_url,
        'icon'     => 'fas fa-kaaba',
        'active'   => $is_hajj_omra_page,
        'children' => array(),
    ),
    array(
        'label'    => 'GROUP DEALS',
        'url'      => $group_deals_page_url,
        'icon'     => 'fas fa-users',
        'active'   => $is_group_deals_page,
        'children' => array(),
    ),
);

/**
 * Masque uniquement l’entrée « Transfert » dans le menu du header (pas la page /transfert/).
 * Une seule inscription du filtre par requête.
 */
if ( empty( $GLOBALS['ajth_header_hide_transfert_nav_filter'] ) ) {
    $GLOBALS['ajth_header_hide_transfert_nav_filter'] = true;
    $ajth_header_menu_location = ! empty( $hdr['wp_menu_location'] ) ? $hdr['wp_menu_location'] : 'primary';
    add_filter(
        'wp_nav_menu_objects',
        static function ( $items, $args ) use ( $ajth_header_menu_location ) {
            if ( empty( $items ) || ! is_array( $items ) ) {
                return $items;
            }
            if ( empty( $args->theme_location ) || $args->theme_location !== $ajth_header_menu_location ) {
                return $items;
            }
            foreach ( $items as $key => $item ) {
                if ( empty( $item->menu_item_parent ) || (int) $item->menu_item_parent !== 0 ) {
                    continue;
                }
                $title = isset( $item->title ) ? wp_strip_all_tags( $item->title ) : '';
                if ( $title !== '' && function_exists( 'remove_accents' ) ) {
                    $title = remove_accents( $title );
                }
                $title = $title !== '' ? mb_strtolower( trim( $title ), 'UTF-8' ) : '';
                if ( in_array( $title, array( 'transfert', 'transferts' ), true ) ) {
                    unset( $items[ $key ] );
                }
            }
            return $items;
        },
        10,
        2
    );
}
?>

<header class="aj-header" id="aj-header">

    <?php if ( ! empty( $hdr['topbar_enabled'] ) ) : ?>
    <!-- Top Bar -->
    <div class="aj-topbar">
        <div class="aj-container aj-topbar__inner">
            <!-- Left: Social + Contact -->
            <div class="aj-topbar__left">
                <!-- Social Icons -->
                <div class="aj-topbar__socials">
                    <?php foreach ( $social_icons as $key => $icon ) :
                        $url = ! empty( $socials[ $key ] ) ? $socials[ $key ] : '';
                        if ( $url === '' ) continue;
                    ?>
                        <a href="<?php echo esc_url( $url ); ?>" class="aj-topbar__social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>">
                            <?php echo $icon; // phpcs:ignore -- inline icon ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <!-- Contact Info -->
                <div class="aj-topbar__contact">
                    <?php if ( ! empty( $hdr['email'] ) ) : ?>
                        <span class="aj-topbar__item">
                            <i class="far fa-envelope"></i>
                            <?php echo esc_html( $hdr['email'] ); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ( ! empty( $hdr['phone'] ) ) : ?>
                        <span class="aj-topbar__item">
                            <i class="fas fa-phone"></i>
                            <?php echo esc_html( $hdr['phone'] ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right: Auth -->
            <div class="aj-topbar__right">
                <!-- Auth Links -->
                <?php if ( ! empty( $hdr['show_auth_links'] ) ) : ?>
                <div class="aj-topbar__auth">
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="aj-topbar__auth-link"><?php esc_html_e( 'SE DÉCONNECTER', 'ajinsafro-traveler-home' ); ?></a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( $public_login_url ); ?>" class="aj-topbar__auth-link"><?php esc_html_e( 'SE CONNECTER', 'ajinsafro-traveler-home' ); ?></a>
                        <a href="<?php echo esc_url( $public_signup_url ); ?>" class="aj-topbar__auth-link aj-topbar__auth-link--signup"><?php esc_html_e( "S'INSCRIRE", 'ajinsafro-traveler-home' ); ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $hdr['navbar_enabled'] ) ) : ?>
    <!-- Main Navigation -->
    <nav class="aj-navbar" id="aj-navbar">
        <div class="aj-container aj-navbar__inner">

            <!-- Logo -->
            <div class="aj-navbar__logo">
                <?php if ( ! empty( $hdr['logo_url'] ) ) : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <img decoding="async" width="191" height="32" src="<?php echo esc_url( $hdr['logo_url'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="aj-navbar__logo-img" loading="eager">
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="aj-navbar__brand">
                        <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button -->
            <button type="button" class="aj-navbar__burger aj-header__toggle" id="aj-burger" aria-label="Menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Drawer (Mobile) / Menu (Desktop) -->
            <div class="aj-drawer aj-header__drawer" id="aj-drawer">
                <div class="aj-drawer__header">
                    <span class="aj-drawer__title"><?php esc_html_e( 'Menu', 'ajinsafro-traveler-home' ); ?></span>
                    <button type="button" class="aj-drawer__close" id="aj-drawer-close" aria-label="<?php esc_attr_e( 'Fermer', 'ajinsafro-traveler-home' ); ?>">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <?php if ( ! empty( $hdr['show_auth_links'] ) ) : ?>
                <div class="aj-drawer__auth aj-header__auth--mobile">
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="aj-auth-link aj-auth-link--block"><?php esc_html_e( 'Se déconnecter', 'ajinsafro-traveler-home' ); ?></a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( $public_login_url ); ?>" class="aj-auth-link aj-auth-link--block"><?php esc_html_e( 'Se connecter', 'ajinsafro-traveler-home' ); ?></a>
                        <a href="<?php echo esc_url( $public_signup_url ); ?>" class="aj-auth-link aj-auth-link--signup aj-auth-link--block"><?php esc_html_e( "S'inscrire", 'ajinsafro-traveler-home' ); ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="aj-navbar__menu" id="aj-nav-menu">
                <?php if ( ! empty( $hdr['menu_source'] ) && $hdr['menu_source'] === 'wp_menu' ) : ?>
                    <?php
                    $menu_location = ! empty( $hdr['wp_menu_location'] ) ? $hdr['wp_menu_location'] : 'primary';
                    if ( has_nav_menu( $menu_location ) ) {
                        wp_nav_menu( array(
                            'theme_location' => $menu_location,
                            'container'      => false,
                            'menu_class'     => 'aj-nav-list',
                            'depth'          => 2,
                            'fallback_cb'    => false,
                            'walker'         => new AJTH_Nav_Walker(),
                        ) );
                    } else {
                        // Fallback: show default menu with icons
                        ?>
                        <ul class="aj-nav-list">
                            <?php foreach ( $default_menu_items as $item ) : ?>
                            <li class="<?php echo ! empty( $item['active'] ) ? 'aj-active' : ''; ?>">
                                <a href="<?php echo esc_url( $item['url'] ); ?>">
                                    <?php if ( ! empty( $item['icon'] ) ) : ?>
                                        <i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
                                    <?php endif; ?>
                                    <span><?php echo esc_html( $item['label'] ); ?></span>
                                    <?php if ( ! empty( $item['children'] ) ) : ?>
                                        <i class="fas fa-chevron-down aj-caret"></i>
                                    <?php endif; ?>
                                </a>
                                <?php if ( ! empty( $item['children'] ) ) : ?>
                                    <ul class="aj-sub-menu">
                                        <?php foreach ( $item['children'] as $child ) : ?>
                                            <li><a href="<?php echo esc_url( $child['url'] ); ?>"><?php echo esc_html( $child['label'] ); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php
                    }
                    ?>
                <?php else : ?>
                    <?php
                    // Use custom links from Laravel admin, or default menu if empty
                    $nav_links = ! empty( $hdr['links'] ) && is_array( $hdr['links'] ) ? $hdr['links'] : array();
                    if ( empty( $nav_links ) ) {
                        $nav_links = $default_menu_items;
                    }
                    // Ne pas afficher « Transfert » dans le menu (la page /transfert/ reste accessible ailleurs).
                    $nav_links = array_values(
                        array_filter(
                            $nav_links,
                            static function ( $link ) {
                                $label = ! empty( $link['label'] ) ? wp_strip_all_tags( $link['label'] ) : '';
                                if ( $label !== '' && function_exists( 'remove_accents' ) ) {
                                    $label = remove_accents( $label );
                                }
                                $label = $label !== '' ? mb_strtolower( trim( $label ), 'UTF-8' ) : '';
                                return ! in_array( $label, array( 'transfert', 'transferts' ), true );
                            }
                        )
                    );
                    ?>
                    <ul class="aj-nav-list">
                        <?php foreach ( $nav_links as $link ) :
                            $label    = ! empty( $link['label'] ) ? $link['label'] : '';
                            $label_key = $label !== '' ? mb_strtolower( remove_accents( wp_strip_all_tags( $label ) ), 'UTF-8' ) : '';
                            if ( in_array( $label_key, array( 'group deals', 'group deal', 'votre guide', 'guide' ), true ) ) {
                                $label = 'GROUP DEALS';
                            }
                            $url      = $resolve_menu_url(
                                ! empty( $link['label'] ) ? $link['label'] : '',
                                ! empty( $link['url'] ) ? $link['url'] : ''
                            );
                            $icon     = ! empty( $link['icon'] ) ? $link['icon'] : '';
                            $children = ! empty( $link['children'] ) && is_array( $link['children'] ) ? $link['children'] : array();
                            $has_sub  = ! empty( $children );
                            $is_active = ! empty( $link['active'] );
                            $is_highlight = ! empty( $link['highlight'] );

                            // Auto-resolve icon from title map
                            if ( empty( $icon ) && $label ) {
                                $label_lower = trim( wp_strip_all_tags( $label ) );
                                if ( $label_lower !== '' && function_exists( 'remove_accents' ) ) {
                                    $label_lower = remove_accents( $label_lower );
                                }
                                $label_lower = $label_lower !== '' ? mb_strtolower( $label_lower, 'UTF-8' ) : '';
                                if ( isset( $title_icon_map[ $label_lower ] ) ) {
                                    $icon = $title_icon_map[ $label_lower ];
                                }
                            }
                        ?>
                        <li class="<?php echo $has_sub ? 'aj-has-sub' : ''; ?><?php echo $is_active ? ' aj-active' : ''; ?><?php echo $is_highlight ? ' aj-highlight' : ''; ?>">
                            <a href="<?php echo esc_url( $url ); ?>" class="<?php echo $is_highlight ? 'aj-nav-highlight' : ''; ?>">
                                <?php if ( $icon ) : ?>
                                    <i class="<?php echo esc_attr( $icon ); ?>"></i>
                                <?php endif; ?>
                                <span><?php echo esc_html( $label ); ?></span>
                                <?php if ( $has_sub ) : ?>
                                    <i class="fas fa-chevron-down aj-caret"></i>
                                <?php endif; ?>
                            </a>
                            <?php if ( $has_sub ) : ?>
                                <ul class="aj-sub-menu">
                                    <?php foreach ( $children as $child ) :
                                        $child_icon = ! empty( $child['icon'] ) ? $child['icon'] : '';
                                    ?>
                                        <li>
                                            <a href="<?php echo esc_url( $resolve_menu_url( ! empty( $child['label'] ) ? $child['label'] : '', ! empty( $child['url'] ) ? $child['url'] : '' ) ); ?>">
                                                <?php if ( $child_icon ) : ?>
                                                    <i class="<?php echo esc_attr( $child_icon ); ?>"></i>
                                                <?php endif; ?>
                                                <?php echo esc_html( ! empty( $child['label'] ) ? $child['label'] : '' ); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                </div>
                
                <!-- Low Cost Button (inside drawer for mobile) -->
                <?php if ( ! empty( $hdr['lowcost_enabled'] ) ) : ?>
                <div class="aj-drawer__lowcost">
                    <a href="<?php echo esc_url( $resolve_menu_url( 'Formule low cost', ! empty( $hdr['lowcost_url'] ) ? $hdr['lowcost_url'] : $economic_offers_page_url ) ); ?>" class="aj-lowcost-btn">
                        <i class="fas fa-fire"></i>
                        <span><?php echo esc_html( ! empty( $hdr['lowcost_text'] ) ? $hdr['lowcost_text'] : 'Formule Économique' ); ?></span>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Low Cost Button (Desktop) -->
            <?php if ( ! empty( $hdr['lowcost_enabled'] ) ) : ?>
            <div class="aj-navbar__lowcost aj-header__lowcost--desktop">
                <a href="<?php echo esc_url( $resolve_menu_url( 'Formule low cost', ! empty( $hdr['lowcost_url'] ) ? $hdr['lowcost_url'] : $economic_offers_page_url ) ); ?>" class="aj-lowcost-btn aj-lowcost-btn--animate">
                    <i class="fas fa-fire aj-lowcost-btn__icon"></i>
                    <span><?php echo esc_html( ! empty( $hdr['lowcost_text'] ) ? $hdr['lowcost_text'] : 'Formule Économique' ); ?></span>
                </a>
            </div>
            <?php endif; ?>

        </div>
    </nav>
    <?php endif; ?>

</header>
