<?php
/**
 * Part: Custom header (topbar + navbar)
 * Header settings managed from Laravel admin /admin/settings/home-page
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
    'facebook'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>',
    'twitter'   => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>',
    'instagram' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
    'youtube'   => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19.1c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.33 29 29 0 00-.46-5.35z"/><polygon points="9.75,15.02 15.5,11.75 9.75,8.48" fill="#fff"/></svg>',
);
?>

<header class="aj-header" id="aj-header">

    <?php if ( ! empty( $hdr['topbar_enabled'] ) ) : ?>
    <div class="aj-topbar">
        <div class="aj-container aj-topbar__inner">
            <div class="aj-topbar__left">
                <?php if ( ! empty( $hdr['phone'] ) ) : ?>
                    <span class="aj-topbar__item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.13.81.36 1.6.68 2.35a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.75.32 1.54.55 2.35.68A2 2 0 0122 16.92z"/></svg>
                        <?php echo esc_html( $hdr['phone'] ); ?>
                    </span>
                <?php endif; ?>
                <?php if ( ! empty( $hdr['email'] ) ) : ?>
                    <span class="aj-topbar__item">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22,4 12,13 2,4"/></svg>
                        <?php echo esc_html( $hdr['email'] ); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="aj-topbar__right">
                <?php foreach ( $social_icons as $key => $svg ) :
                    $url = ! empty( $socials[ $key ] ) ? $socials[ $key ] : '';
                    if ( $url === '' ) continue;
                ?>
                    <a href="<?php echo esc_url( $url ); ?>" class="aj-topbar__social" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ucfirst( $key ) ); ?>">
                        <?php echo $svg; // phpcs:ignore -- inline SVG ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $hdr['navbar_enabled'] ) ) : ?>
    <nav class="aj-navbar" id="aj-navbar">
        <div class="aj-container aj-navbar__inner">

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

            <button type="button" class="aj-navbar__burger aj-header__toggle" id="aj-burger" aria-label="Menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>

            <div class="aj-drawer aj-header__drawer" id="aj-drawer" aria-hidden="true">
                <div class="aj-drawer__header">
                    <span class="aj-drawer__title"><?php esc_html_e( 'Menu', 'ajinsafro-traveler-home' ); ?></span>
                    <button type="button" class="aj-drawer__close" id="aj-drawer-close" aria-label="<?php esc_attr_e( 'Fermer', 'ajinsafro-traveler-home' ); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php if ( ! empty( $hdr['show_auth_links'] ) ) : ?>
                <div class="aj-drawer__auth aj-header__auth--mobile">
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="aj-auth-link aj-auth-link--block"><?php esc_html_e( 'Logout', 'ajinsafro-traveler-home' ); ?></a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( ! empty( $hdr['login_url'] ) ? $hdr['login_url'] : wp_login_url() ); ?>" class="aj-auth-link aj-auth-link--block"><?php esc_html_e( 'Sign In', 'ajinsafro-traveler-home' ); ?></a>
                        <a href="<?php echo esc_url( ! empty( $hdr['signup_url'] ) ? $hdr['signup_url'] : wp_registration_url() ); ?>" class="aj-auth-link aj-auth-link--signup aj-auth-link--block"><?php esc_html_e( 'Sign Up', 'ajinsafro-traveler-home' ); ?></a>
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
                        ) );
                    } else {
                        wp_nav_menu( array(
                            'container'   => false,
                            'menu_class'  => 'aj-nav-list',
                            'depth'       => 2,
                            'fallback_cb' => 'wp_page_menu',
                        ) );
                    }
                    ?>
                <?php else : ?>
                    <ul class="aj-nav-list">
                        <?php
                        $nav_links = ! empty( $hdr['links'] ) && is_array( $hdr['links'] ) ? $hdr['links'] : array();
                        foreach ( $nav_links as $link ) :
                            $label    = ! empty( $link['label'] ) ? $link['label'] : '';
                            $url      = ! empty( $link['url'] ) ? $link['url'] : '#';
                            $children = ! empty( $link['children'] ) && is_array( $link['children'] ) ? $link['children'] : array();
                            $has_sub  = ! empty( $children );
                        ?>
                        <li class="<?php echo $has_sub ? 'aj-has-sub' : ''; ?>">
                            <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?><?php if ( $has_sub ) : ?> <span class="aj-caret">▾</span><?php endif; ?></a>
                            <?php if ( $has_sub ) : ?>
                                <ul class="aj-sub-menu">
                                    <?php foreach ( $children as $child ) : ?>
                                        <li><a href="<?php echo esc_url( ! empty( $child['url'] ) ? $child['url'] : '#' ); ?>"><?php echo esc_html( ! empty( $child['label'] ) ? $child['label'] : '' ); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                </div>
            </div>

            <?php if ( ! empty( $hdr['show_auth_links'] ) ) : ?>
            <div class="aj-navbar__auth aj-header__auth--desktop" id="aj-navbar-auth">
                <?php if ( is_user_logged_in() ) : ?>
                    <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="aj-auth-link"><?php esc_html_e( 'Logout', 'ajinsafro-traveler-home' ); ?></a>
                <?php else : ?>
                    <a href="<?php echo esc_url( ! empty( $hdr['login_url'] ) ? $hdr['login_url'] : wp_login_url() ); ?>" class="aj-auth-link"><?php esc_html_e( 'Sign In', 'ajinsafro-traveler-home' ); ?></a>
                    <a href="<?php echo esc_url( ! empty( $hdr['signup_url'] ) ? $hdr['signup_url'] : wp_registration_url() ); ?>" class="aj-auth-link aj-auth-link--signup"><?php esc_html_e( 'Sign Up', 'ajinsafro-traveler-home' ); ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </nav>
    <?php endif; ?>

</header>
