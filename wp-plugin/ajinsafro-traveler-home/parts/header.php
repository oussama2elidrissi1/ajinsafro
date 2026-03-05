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

$socials = isset( $hdr['socials'] ) && is_array( $hdr['socials'] ) ? $hdr['socials'] : array();

$social_icons = array(
    'facebook'  => '<i class="fab fa-facebook-f"></i>',
    'twitter'   => '<i class="fab fa-twitter"></i>',
    'youtube'   => '<i class="fab fa-youtube"></i>',
    'instagram' => '<i class="fab fa-instagram"></i>',
    'linkedin'  => '<i class="fab fa-linkedin-in"></i>',
);

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
    'votre guide'  => 'fas fa-map-signs',
    'guide'        => 'fas fa-map-signs',
    'accueil'      => 'fas fa-home',
    'contact'      => 'fas fa-envelope',
    'blog'         => 'fas fa-blog',
);

$default_menu_items = array(
    array(
        'label'    => 'Packages',
        'url'      => '#packages',
        'icon'     => 'fas fa-suitcase-rolling',
        'active'   => true,
        'children' => array(),
    ),
    array(
        'label'    => 'Hébergement',
        'url'      => '#hebergement',
        'icon'     => 'fas fa-hotel',
        'active'   => false,
        'children' => array(),
    ),
    array(
        'label'    => 'Activités',
        'url'      => '#activites',
        'icon'     => 'fas fa-camera',
        'active'   => false,
        'children' => array(),
    ),
    array(
        'label'    => 'Transfert',
        'url'      => '#transfert',
        'icon'     => 'fas fa-car-side',
        'active'   => false,
        'children' => array(),
    ),
    array(
        'label'    => 'Hajj & Omra',
        'url'      => '#hajj-omra',
        'icon'     => 'fas fa-kaaba',
        'active'   => false,
        'children' => array(),
    ),
    array(
        'label'    => 'Votre guide',
        'url'      => '#guide',
        'icon'     => 'fas fa-map-signs',
        'active'   => false,
        'children' => array(),
    ),
);
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
            
            <!-- Right: Language, Currency, Auth -->
            <div class="aj-topbar__right">
                <!-- Language Selector -->
                <div class="aj-topbar__selector" id="aj-lang-selector">
                    <img src="https://upload.wikimedia.org/wikipedia/en/c/c3/Flag_of_France.svg" alt="FR" class="aj-topbar__flag">
                    <span>FR</span>
                    <i class="fas fa-chevron-down aj-topbar__caret"></i>
                </div>
                
                <!-- Currency Selector -->
                <div class="aj-topbar__selector" id="aj-currency-selector">
                    <span>MAD</span>
                    <i class="fas fa-chevron-down aj-topbar__caret"></i>
                </div>
                
                <!-- Auth Links -->
                <?php if ( ! empty( $hdr['show_auth_links'] ) ) : ?>
                <div class="aj-topbar__auth">
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="aj-topbar__auth-link"><?php esc_html_e( 'SE DÉCONNECTER', 'ajinsafro-traveler-home' ); ?></a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( ! empty( $hdr['login_url'] ) ? $hdr['login_url'] : wp_login_url() ); ?>" class="aj-topbar__auth-link"><?php esc_html_e( 'SE CONNECTER', 'ajinsafro-traveler-home' ); ?></a>
                        <a href="<?php echo esc_url( ! empty( $hdr['signup_url'] ) ? $hdr['signup_url'] : wp_registration_url() ); ?>" class="aj-topbar__auth-link aj-topbar__auth-link--signup"><?php esc_html_e( "S'INSCRIRE", 'ajinsafro-traveler-home' ); ?></a>
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
                        <img src="<?php echo esc_url( $hdr['logo_url'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="aj-navbar__logo-img">
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
            <div class="aj-drawer aj-header__drawer" id="aj-drawer" aria-hidden="true">
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
                        <a href="<?php echo esc_url( ! empty( $hdr['login_url'] ) ? $hdr['login_url'] : wp_login_url() ); ?>" class="aj-auth-link aj-auth-link--block"><?php esc_html_e( 'Se connecter', 'ajinsafro-traveler-home' ); ?></a>
                        <a href="<?php echo esc_url( ! empty( $hdr['signup_url'] ) ? $hdr['signup_url'] : wp_registration_url() ); ?>" class="aj-auth-link aj-auth-link--signup aj-auth-link--block"><?php esc_html_e( "S'inscrire", 'ajinsafro-traveler-home' ); ?></a>
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
                    ?>
                    <ul class="aj-nav-list">
                        <?php foreach ( $nav_links as $link ) :
                            $label    = ! empty( $link['label'] ) ? $link['label'] : '';
                            $url      = ! empty( $link['url'] ) ? $link['url'] : '#';
                            $icon     = ! empty( $link['icon'] ) ? $link['icon'] : '';
                            $children = ! empty( $link['children'] ) && is_array( $link['children'] ) ? $link['children'] : array();
                            $has_sub  = ! empty( $children );
                            $is_active = ! empty( $link['active'] );
                            $is_highlight = ! empty( $link['highlight'] );

                            // Auto-resolve icon from title map
                            if ( empty( $icon ) && $label ) {
                                $label_lower = mb_strtolower( trim( $label ), 'UTF-8' );
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
                                            <a href="<?php echo esc_url( ! empty( $child['url'] ) ? $child['url'] : '#' ); ?>">
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
                    <a href="<?php echo esc_url( ! empty( $hdr['lowcost_url'] ) ? $hdr['lowcost_url'] : '#' ); ?>" class="aj-lowcost-btn">
                        <i class="fas fa-fire"></i>
                        <span><?php echo esc_html( ! empty( $hdr['lowcost_text'] ) ? $hdr['lowcost_text'] : 'Formule low cost' ); ?></span>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Low Cost Button (Desktop) -->
            <?php if ( ! empty( $hdr['lowcost_enabled'] ) ) : ?>
            <div class="aj-navbar__lowcost aj-header__lowcost--desktop">
                <a href="<?php echo esc_url( ! empty( $hdr['lowcost_url'] ) ? $hdr['lowcost_url'] : '#' ); ?>" class="aj-lowcost-btn aj-lowcost-btn--animate">
                    <i class="fas fa-fire aj-lowcost-btn__icon"></i>
                    <span><?php echo esc_html( ! empty( $hdr['lowcost_text'] ) ? $hdr['lowcost_text'] : 'Formule low cost' ); ?></span>
                </a>
            </div>
            <?php endif; ?>

        </div>
    </nav>
    <?php endif; ?>

</header>
